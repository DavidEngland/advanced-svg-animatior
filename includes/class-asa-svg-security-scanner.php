<?php
/**
 * Advanced SVG Security Scanner
 * 
 * Provides comprehensive scanning of SVG files for malicious content
 * beyond basic sanitization. Designed for WordPress media library auditing.
 * 
 * @package Advanced_SVG_Animator
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SVG Security Scanner Class
 * 
 * Performs in-depth analysis of SVG files to detect potentially malicious content
 */
class ASA_SVG_Security_Scanner {

    /**
     * Scanner version for tracking improvements
     */
    const SCANNER_VERSION = '1.0.0';

    /**
     * Database table name for scan results
     */
    private $table_name;

    /**
     * Scan results for current session
     */
    private $scan_results = array();

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'asa_svg_scan_results';
        
        // Create database table if it doesn't exist
        $this->create_scan_results_table();
    }

    /**
     * Create database table for storing scan results
     */
    private function create_scan_results_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            attachment_id bigint(20) NOT NULL,
            file_path varchar(255) NOT NULL,
            scan_date datetime DEFAULT CURRENT_TIMESTAMP,
            scanner_version varchar(20) NOT NULL,
            threat_level enum('low','medium','high','critical') NOT NULL DEFAULT 'low',
            threats_found text NOT NULL,
            file_size bigint(20) NOT NULL,
            file_hash varchar(64) NOT NULL,
            status enum('active','quarantined','deleted','resolved') NOT NULL DEFAULT 'active',
            notes text,
            PRIMARY KEY (id),
            KEY attachment_id (attachment_id),
            KEY scan_date (scan_date),
            KEY threat_level (threat_level),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Scan all SVG files in the WordPress media library
     * 
     * @param array $options Scan options
     * @return array Scan results summary
     */
    public function scan_media_library($options = array()) {
        $defaults = array(
            'force_rescan' => false,
            'limit' => 0, // 0 = no limit
            'offset' => 0,
            'include_quarantined' => false
        );
        
        $options = wp_parse_args($options, $defaults);

        // Get all SVG attachments
        $svg_attachments = $this->get_svg_attachments($options);
        
        $scan_summary = array(
            'total_scanned' => 0,
            'threats_found' => 0,
            'files_with_issues' => array(),
            'scan_start_time' => current_time('mysql'),
            'scan_duration' => 0,
            'scanner_version' => self::SCANNER_VERSION
        );

        $start_time = microtime(true);

        foreach ($svg_attachments as $attachment) {
            $scan_result = $this->scan_svg_file($attachment->ID, $options['force_rescan']);
            
            if ($scan_result) {
                $scan_summary['total_scanned']++;
                
                if (!empty($scan_result['threats'])) {
                    $scan_summary['threats_found'] += count($scan_result['threats']);
                    $scan_summary['files_with_issues'][] = array(
                        'attachment_id' => $attachment->ID,
                        'file_path' => $scan_result['file_path'],
                        'threat_level' => $scan_result['threat_level'],
                        'threats' => $scan_result['threats']
                    );
                }
            }

            // Allow for memory management on large libraries
            if ($scan_summary['total_scanned'] % 50 === 0) {
                wp_cache_flush();
            }
        }

        $scan_summary['scan_duration'] = round(microtime(true) - $start_time, 2);
        
        // Log scan summary
        $this->log_scan_summary($scan_summary);
        
        return $scan_summary;
    }

    /**
     * Scan a single SVG file for security threats
     * 
     * @param int $attachment_id WordPress attachment ID
     * @param bool $force_rescan Force rescan even if recently scanned
     * @return array|false Scan results or false on failure
     */
    public function scan_svg_file($attachment_id, $force_rescan = false) {
        // Get attachment details
        $attachment = get_post($attachment_id);
        if (!$attachment || $attachment->post_type !== 'attachment') {
            return false;
        }

        // Verify it's an SVG file
        $mime_type = get_post_mime_type($attachment_id);
        if ($mime_type !== 'image/svg+xml') {
            return false;
        }

        // Get file path
        $file_path = get_attached_file($attachment_id);
        if (!$file_path || !file_exists($file_path)) {
            return false;
        }

        // Check if we need to rescan
        if (!$force_rescan && $this->has_recent_scan($attachment_id, $file_path)) {
            return $this->get_cached_scan_result($attachment_id);
        }

        // Load and parse SVG content
        $svg_content = file_get_contents($file_path);
        if (empty($svg_content)) {
            return false;
        }

        // Initialize scan result
        $scan_result = array(
            'attachment_id' => $attachment_id,
            'file_path' => $file_path,
            'file_size' => filesize($file_path),
            'file_hash' => hash('sha256', $svg_content),
            'threats' => array(),
            'threat_level' => 'low',
            'scan_date' => current_time('mysql'),
            'scanner_version' => self::SCANNER_VERSION
        );

        // Perform comprehensive security analysis
        $this->analyze_svg_content($svg_content, $scan_result);
        $this->analyze_svg_structure($svg_content, $scan_result);
        $this->analyze_external_references($svg_content, $scan_result);
        $this->analyze_encoded_content($svg_content, $scan_result);
        $this->analyze_suspicious_patterns($svg_content, $scan_result);

        // Determine overall threat level
        $scan_result['threat_level'] = $this->calculate_threat_level($scan_result['threats']);

        // Store scan result in database
        $this->store_scan_result($scan_result);

        return $scan_result;
    }

    /**
     * Analyze SVG content for malicious patterns
     * 
     * @param string $svg_content SVG file content
     * @param array &$scan_result Scan result array (passed by reference)
     */
    private function analyze_svg_content($svg_content, &$scan_result) {
        // Check for script tags
        if (preg_match('/<script[^>]*>/i', $svg_content)) {
            $scan_result['threats'][] = array(
                'type' => 'script_tag',
                'severity' => 'critical',
                'description' => 'Contains <script> tags which can execute JavaScript',
                'pattern' => 'script tag detected'
            );
        }

        // Check for PHP tags
        if (preg_match('/<\?php/i', $svg_content) || preg_match('/<\?=/i', $svg_content)) {
            $scan_result['threats'][] = array(
                'type' => 'php_tag',
                'severity' => 'critical',
                'description' => 'Contains PHP execution tags',
                'pattern' => 'PHP tag detected'
            );
        }

        // Check for JavaScript event handlers
        $js_events = array(
            'onload', 'onerror', 'onclick', 'onmouseover', 'onmouseout', 
            'onfocus', 'onblur', 'onchange', 'onsubmit', 'onreset'
        );
        
        foreach ($js_events as $event) {
            if (preg_match('/' . $event . '\s*=/i', $svg_content)) {
                $scan_result['threats'][] = array(
                    'type' => 'js_event_handler',
                    'severity' => 'high',
                    'description' => "Contains JavaScript event handler: {$event}",
                    'pattern' => $event
                );
            }
        }

        // Check for document.write
        if (preg_match('/document\.write/i', $svg_content)) {
            $scan_result['threats'][] = array(
                'type' => 'document_write',
                'severity' => 'high',
                'description' => 'Contains document.write() which can modify page content',
                'pattern' => 'document.write detected'
            );
        }

        // Check for eval() function
        if (preg_match('/\beval\s*\(/i', $svg_content)) {
            $scan_result['threats'][] = array(
                'type' => 'eval_function',
                'severity' => 'critical',
                'description' => 'Contains eval() function which can execute arbitrary code',
                'pattern' => 'eval() detected'
            );
        }
    }

    /**
     * Analyze SVG DOM structure using DOMDocument
     * 
     * @param string $svg_content SVG file content
     * @param array &$scan_result Scan result array (passed by reference)
     */
    private function analyze_svg_structure($svg_content, &$scan_result) {
        // Create DOMDocument instance
        $dom = new DOMDocument();
        $dom->formatOutput = false;
        $dom->preserveWhiteSpace = true;

        // Enable user error handling for XML parsing
        libxml_use_internal_errors(true);
        
        try {
            // Attempt to load SVG as XML
            if (!$dom->loadXML($svg_content)) {
                $xml_errors = libxml_get_errors();
                $scan_result['threats'][] = array(
                    'type' => 'malformed_xml',
                    'severity' => 'medium',
                    'description' => 'SVG contains malformed XML that could be used to bypass parsers',
                    'pattern' => 'XML parsing errors: ' . count($xml_errors)
                );
                libxml_clear_errors();
                return; // Can't continue structure analysis
            }

            // Clear any XML errors
            libxml_clear_errors();

            // Create XPath for querying
            $xpath = new DOMXPath($dom);

            // Check for dangerous elements
            $dangerous_elements = array(
                'script' => 'critical',
                'iframe' => 'critical', 
                'object' => 'high',
                'embed' => 'high',
                'link' => 'medium',
                'meta' => 'medium',
                'style' => 'medium'
            );

            foreach ($dangerous_elements as $element => $severity) {
                $nodes = $xpath->query("//{$element}");
                if ($nodes->length > 0) {
                    $scan_result['threats'][] = array(
                        'type' => 'dangerous_element',
                        'severity' => $severity,
                        'description' => "Contains potentially dangerous <{$element}> element(s)",
                        'pattern' => "{$element} element ({$nodes->length} found)"
                    );
                }
            }

            // Check for suspicious attributes across all elements
            $all_elements = $xpath->query('//*');
            foreach ($all_elements as $element) {
                if ($element->hasAttributes()) {
                    foreach ($element->attributes as $attr) {
                        $this->analyze_attribute($attr, $scan_result);
                    }
                }
            }

            // Check for nested SVG elements (can be used for obfuscation)
            $nested_svgs = $xpath->query('//svg//svg');
            if ($nested_svgs->length > 0) {
                $scan_result['threats'][] = array(
                    'type' => 'nested_svg',
                    'severity' => 'medium',
                    'description' => 'Contains nested SVG elements which could be used for obfuscation',
                    'pattern' => "nested SVG ({$nested_svgs->length} found)"
                );
            }

            // Check for foreign object elements
            $foreign_objects = $xpath->query('//foreignObject');
            if ($foreign_objects->length > 0) {
                $scan_result['threats'][] = array(
                    'type' => 'foreign_object',
                    'severity' => 'high',
                    'description' => 'Contains foreignObject elements which can embed arbitrary content',
                    'pattern' => "foreignObject ({$foreign_objects->length} found)"
                );
            }

        } catch (Exception $e) {
            $scan_result['threats'][] = array(
                'type' => 'parsing_exception',
                'severity' => 'medium',
                'description' => 'Exception occurred during XML parsing: ' . $e->getMessage(),
                'pattern' => 'parsing exception'
            );
        } finally {
            // Always clear libxml errors
            libxml_clear_errors();
        }
    }

    /**
     * Analyze individual attributes for suspicious content
     * 
     * @param DOMAttr $attr Attribute to analyze
     * @param array &$scan_result Scan result array (passed by reference)
     */
    private function analyze_attribute($attr, &$scan_result) {
        $attr_name = $attr->nodeName;
        $attr_value = $attr->nodeValue;

        // Check for JavaScript in href attributes
        if (in_array($attr_name, array('href', 'xlink:href')) && 
            preg_match('/^javascript:/i', $attr_value)) {
            $scan_result['threats'][] = array(
                'type' => 'javascript_href',
                'severity' => 'critical',
                'description' => "JavaScript URL in {$attr_name} attribute",
                'pattern' => "javascript: in {$attr_name}"
            );
        }

        // Check for data URIs that aren't images
        if (preg_match('/^data:(?!image\/)/i', $attr_value)) {
            $scan_result['threats'][] = array(
                'type' => 'suspicious_data_uri',
                'severity' => 'high',
                'description' => "Non-image data URI in {$attr_name} attribute",
                'pattern' => "data: URI in {$attr_name}"
            );
        }

        // Check for external references to non-media resources
        if (in_array($attr_name, array('href', 'xlink:href', 'src')) &&
            preg_match('/^https?:\/\//i', $attr_value) &&
            !preg_match('/\.(jpg|jpeg|png|gif|svg|mp3|mp4|wav|ogg)$/i', $attr_value)) {
            $scan_result['threats'][] = array(
                'type' => 'external_non_media',
                'severity' => 'medium',
                'description' => "External reference to non-media resource in {$attr_name}",
                'pattern' => "external URL in {$attr_name}"
            );
        }

        // Check for expression() in style attributes (IE-specific but still dangerous)
        if ($attr_name === 'style' && preg_match('/expression\s*\(/i', $attr_value)) {
            $scan_result['threats'][] = array(
                'type' => 'css_expression',
                'severity' => 'high',
                'description' => 'CSS expression() found in style attribute',
                'pattern' => 'expression() in style'
            );
        }
    }

    /**
     * Analyze external references in SVG content
     * 
     * @param string $svg_content SVG file content
     * @param array &$scan_result Scan result array (passed by reference)
     */
    private function analyze_external_references($svg_content, &$scan_result) {
        // Find all URLs in the content
        preg_match_all('/https?:\/\/[^\s\'"<>]+/i', $svg_content, $matches);
        
        foreach ($matches[0] as $url) {
            // Check if URL points to suspicious file types
            if (preg_match('/\.(php|asp|jsp|py|pl|sh|bat|exe|dll)$/i', $url)) {
                $scan_result['threats'][] = array(
                    'type' => 'suspicious_external_file',
                    'severity' => 'high',
                    'description' => 'Reference to potentially executable external file',
                    'pattern' => "suspicious URL: {$url}"
                );
            }

            // Check for URLs with suspicious query parameters
            if (preg_match('/[?&](eval|exec|system|cmd|shell)/i', $url)) {
                $scan_result['threats'][] = array(
                    'type' => 'suspicious_url_params',
                    'severity' => 'high',
                    'description' => 'URL with suspicious query parameters',
                    'pattern' => "suspicious params in: {$url}"
                );
            }
        }
    }

    /**
     * Analyze encoded content that might hide malicious code
     * 
     * @param string $svg_content SVG file content
     * @param array &$scan_result Scan result array (passed by reference)
     */
    private function analyze_encoded_content($svg_content, &$scan_result) {
        // Check for base64 encoded content
        if (preg_match_all('/[A-Za-z0-9+\/]{20,}={0,2}/', $svg_content, $matches)) {
            foreach ($matches[0] as $encoded_string) {
                // Try to decode and analyze
                $decoded = base64_decode($encoded_string, true);
                if ($decoded !== false && strlen($decoded) > 10) {
                    // Check if decoded content contains suspicious patterns
                    if (preg_match('/<script|javascript:|php|eval\(|document\.write/i', $decoded)) {
                        $scan_result['threats'][] = array(
                            'type' => 'malicious_base64',
                            'severity' => 'critical',
                            'description' => 'Base64 encoded content contains malicious patterns',
                            'pattern' => 'suspicious base64: ' . substr($encoded_string, 0, 50) . '...'
                        );
                    }
                }
            }
        }

        // Check for URL encoded content
        if (preg_match('/%[0-9a-f]{2}/i', $svg_content)) {
            $url_decoded = urldecode($svg_content);
            if ($url_decoded !== $svg_content) {
                // Check if URL decoded content contains new suspicious patterns
                if (preg_match('/<script|javascript:|eval\(/i', $url_decoded) &&
                    !preg_match('/<script|javascript:|eval\(/i', $svg_content)) {
                    $scan_result['threats'][] = array(
                        'type' => 'hidden_url_encoded',
                        'severity' => 'high',
                        'description' => 'URL encoded content reveals hidden malicious patterns',
                        'pattern' => 'suspicious URL encoding'
                    );
                }
            }
        }

        // Check for HTML entity encoding that might hide malicious content
        if (preg_match('/&#x?[0-9a-f]+;/i', $svg_content)) {
            $entity_decoded = html_entity_decode($svg_content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($entity_decoded !== $svg_content) {
                if (preg_match('/<script|javascript:|eval\(/i', $entity_decoded) &&
                    !preg_match('/<script|javascript:|eval\(/i', $svg_content)) {
                    $scan_result['threats'][] = array(
                        'type' => 'hidden_html_entities',
                        'severity' => 'high',
                        'description' => 'HTML entity encoding hides malicious patterns',
                        'pattern' => 'suspicious HTML entities'
                    );
                }
            }
        }
    }

    /**
     * Analyze content for various suspicious patterns
     * 
     * @param string $svg_content SVG file content
     * @param array &$scan_result Scan result array (passed by reference)
     */
    private function analyze_suspicious_patterns($svg_content, &$scan_result) {
        // Check for obfuscated JavaScript patterns
        $obfuscation_patterns = array(
            '/String\.fromCharCode/i' => 'String.fromCharCode obfuscation',
            '/\[\'\\x[0-9a-f]{2}\'\]/' => 'Hexadecimal string obfuscation',
            '/unescape\s*\(/i' => 'unescape() function',
            '/setTimeout\s*\(/i' => 'setTimeout() function',
            '/setInterval\s*\(/i' => 'setInterval() function'
        );

        foreach ($obfuscation_patterns as $pattern => $description) {
            if (preg_match($pattern, $svg_content)) {
                $scan_result['threats'][] = array(
                    'type' => 'obfuscation_pattern',
                    'severity' => 'high',
                    'description' => "Potential obfuscation: {$description}",
                    'pattern' => $description
                );
            }
        }

        // Check for suspicious function calls
        $dangerous_functions = array(
            'alert', 'confirm', 'prompt', 'open', 'close', 'focus', 'blur',
            'print', 'navigate', 'execScript', 'attachEvent', 'detachEvent'
        );

        foreach ($dangerous_functions as $func) {
            if (preg_match('/\b' . preg_quote($func) . '\s*\(/i', $svg_content)) {
                $scan_result['threats'][] = array(
                    'type' => 'suspicious_function',
                    'severity' => 'medium',
                    'description' => "Suspicious function call: {$func}()",
                    'pattern' => "{$func}() detected"
                );
            }
        }

        // Check for attempts to access browser APIs
        $browser_apis = array(
            'window\.', 'document\.', 'location\.', 'navigator\.', 
            'history\.', 'screen\.', 'localStorage\.', 'sessionStorage\.'
        );

        foreach ($browser_apis as $api) {
            if (preg_match('/' . $api . '/i', $svg_content)) {
                $scan_result['threats'][] = array(
                    'type' => 'browser_api_access',
                    'severity' => 'medium',
                    'description' => "Access to browser API: {$api}",
                    'pattern' => trim($api, '\\.')
                );
            }
        }
    }

    /**
     * Calculate overall threat level based on individual threats
     * 
     * @param array $threats Array of threats found
     * @return string Threat level (low, medium, high, critical)
     */
    private function calculate_threat_level($threats) {
        if (empty($threats)) {
            return 'low';
        }

        $has_critical = false;
        $has_high = false;
        $has_medium = false;

        foreach ($threats as $threat) {
            switch ($threat['severity']) {
                case 'critical':
                    $has_critical = true;
                    break;
                case 'high':
                    $has_high = true;
                    break;
                case 'medium':
                    $has_medium = true;
                    break;
            }
        }

        if ($has_critical) {
            return 'critical';
        } elseif ($has_high) {
            return 'high';
        } elseif ($has_medium) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Store scan result in database
     * 
     * @param array $scan_result Scan result to store
     * @return int|false Insert ID or false on failure
     */
    private function store_scan_result($scan_result) {
        global $wpdb;

        $data = array(
            'attachment_id' => $scan_result['attachment_id'],
            'file_path' => $scan_result['file_path'],
            'scan_date' => $scan_result['scan_date'],
            'scanner_version' => $scan_result['scanner_version'],
            'threat_level' => $scan_result['threat_level'],
            'threats_found' => json_encode($scan_result['threats']),
            'file_size' => $scan_result['file_size'],
            'file_hash' => $scan_result['file_hash'],
            'status' => 'active'
        );

        $formats = array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s');

        $result = $wpdb->insert($this->table_name, $data, $formats);

        if ($result === false) {
            asa_log('Failed to store SVG scan result: ' . $wpdb->last_error, 'error');
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Get all SVG attachments from media library
     * 
     * @param array $options Query options
     * @return array Array of attachment objects
     */
    private function get_svg_attachments($options = array()) {
        $args = array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image/svg+xml',
            'post_status' => 'inherit',
            'posts_per_page' => $options['limit'] ?: -1,
            'offset' => $options['offset'],
            'fields' => 'ids'
        );

        $attachments = get_posts($args);
        
        // Convert IDs back to objects for compatibility
        $attachment_objects = array();
        foreach ($attachments as $id) {
            $attachment_objects[] = (object) array('ID' => $id);
        }

        return $attachment_objects;
    }

    /**
     * Check if attachment has been scanned recently
     * 
     * @param int $attachment_id Attachment ID
     * @param string $file_path File path for hash comparison
     * @return bool True if recently scanned
     */
    private function has_recent_scan($attachment_id, $file_path) {
        global $wpdb;

        // Check if file exists and get current hash
        if (!file_exists($file_path)) {
            return false;
        }

        $current_hash = hash_file('sha256', $file_path);
        
        // Check for recent scan with matching hash (within last 24 hours)
        $recent_scan = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$this->table_name} 
             WHERE attachment_id = %d 
             AND file_hash = %s 
             AND scan_date > DATE_SUB(NOW(), INTERVAL 24 HOUR)
             ORDER BY scan_date DESC LIMIT 1",
            $attachment_id,
            $current_hash
        ));

        return !empty($recent_scan);
    }

    /**
     * Get cached scan result
     * 
     * @param int $attachment_id Attachment ID
     * @return array|false Cached scan result or false
     */
    private function get_cached_scan_result($attachment_id) {
        global $wpdb;

        $cached_result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE attachment_id = %d 
             ORDER BY scan_date DESC LIMIT 1",
            $attachment_id
        ), ARRAY_A);

        if (!$cached_result) {
            return false;
        }

        // Convert back to scan result format
        return array(
            'attachment_id' => $cached_result['attachment_id'],
            'file_path' => $cached_result['file_path'],
            'file_size' => $cached_result['file_size'],
            'file_hash' => $cached_result['file_hash'],
            'threats' => json_decode($cached_result['threats_found'], true) ?: array(),
            'threat_level' => $cached_result['threat_level'],
            'scan_date' => $cached_result['scan_date'],
            'scanner_version' => $cached_result['scanner_version']
        );
    }

    /**
     * Log scan summary
     * 
     * @param array $scan_summary Summary of scan results
     */
    private function log_scan_summary($scan_summary) {
        $message = sprintf(
            'SVG Security Scan completed: %d files scanned, %d threats found in %s seconds',
            $scan_summary['total_scanned'],
            $scan_summary['threats_found'],
            $scan_summary['scan_duration']
        );

        asa_log($message, 'info');

        // Store summary as WordPress option for admin display
        update_option('asa_last_svg_scan_summary', $scan_summary);
    }

    /**
     * Quarantine a suspicious SVG file
     * 
     * @param int $attachment_id Attachment ID to quarantine
     * @return bool Success status
     */
    public function quarantine_file($attachment_id) {
        $file_path = get_attached_file($attachment_id);
        
        if (!$file_path || !file_exists($file_path)) {
            return false;
        }

        // Create quarantine directory if it doesn't exist
        $quarantine_dir = wp_upload_dir()['basedir'] . '/asa-quarantine';
        if (!is_dir($quarantine_dir)) {
            wp_mkdir_p($quarantine_dir);
            
            // Add .htaccess to prevent direct access
            file_put_contents(
                $quarantine_dir . '/.htaccess',
                "deny from all\n"
            );
        }

        // Move file to quarantine with timestamp
        $quarantine_filename = basename($file_path, '.svg') . '_' . time() . '.svg';
        $quarantine_path = $quarantine_dir . '/' . $quarantine_filename;

        if (rename($file_path, $quarantine_path)) {
            // Update database status
            global $wpdb;
            $wpdb->update(
                $this->table_name,
                array('status' => 'quarantined', 'notes' => 'Moved to: ' . $quarantine_path),
                array('attachment_id' => $attachment_id),
                array('%s', '%s'),
                array('%d')
            );

            asa_log("SVG file quarantined: {$file_path} -> {$quarantine_path}", 'info');
            return true;
        }

        return false;
    }

    /**
     * Delete a suspicious SVG file permanently
     * 
     * @param int $attachment_id Attachment ID to delete
     * @return bool Success status
     */
    public function delete_file($attachment_id) {
        // Use WordPress built-in function for proper cleanup
        $result = wp_delete_attachment($attachment_id, true);

        if ($result) {
            // Update database status
            global $wpdb;
            $wpdb->update(
                $this->table_name,
                array('status' => 'deleted'),
                array('attachment_id' => $attachment_id),
                array('%s'),
                array('%d')
            );

            asa_log("SVG file deleted: attachment ID {$attachment_id}", 'info');
            return true;
        }

        return false;
    }

    /**
     * Get scan results with filtering options
     * 
     * @param array $filters Filter options
     * @return array Scan results
     */
    public function get_scan_results($filters = array()) {
        global $wpdb;

        $defaults = array(
            'threat_level' => '',
            'status' => '',
            'limit' => 50,
            'offset' => 0,
            'order_by' => 'scan_date',
            'order' => 'DESC'
        );

        $filters = wp_parse_args($filters, $defaults);

        $where_clauses = array();
        $where_values = array();

        if (!empty($filters['threat_level'])) {
            $where_clauses[] = 'threat_level = %s';
            $where_values[] = $filters['threat_level'];
        }

        if (!empty($filters['status'])) {
            $where_clauses[] = 'status = %s';
            $where_values[] = $filters['status'];
        }

        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }

        $order_sql = sprintf(
            'ORDER BY %s %s',
            sanitize_sql_orderby($filters['order_by']),
            ($filters['order'] === 'ASC') ? 'ASC' : 'DESC'
        );

        $limit_sql = $wpdb->prepare('LIMIT %d OFFSET %d', $filters['limit'], $filters['offset']);

        $query = "SELECT * FROM {$this->table_name} {$where_sql} {$order_sql} {$limit_sql}";

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        $results = $wpdb->get_results($query, ARRAY_A);

        // Decode JSON threats for each result
        foreach ($results as &$result) {
            $result['threats_found'] = json_decode($result['threats_found'], true) ?: array();
        }

        return $results;
    }

    /**
     * Get scan statistics
     * 
     * @return array Statistics summary
     */
    public function get_scan_statistics() {
        global $wpdb;

        $stats = array();

        // Total scans
        $stats['total_scans'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");

        // Threat level breakdown
        $threat_levels = $wpdb->get_results(
            "SELECT threat_level, COUNT(*) as count FROM {$this->table_name} GROUP BY threat_level",
            ARRAY_A
        );

        $stats['threat_levels'] = array();
        foreach ($threat_levels as $level) {
            $stats['threat_levels'][$level['threat_level']] = $level['count'];
        }

        // Status breakdown
        $statuses = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM {$this->table_name} GROUP BY status",
            ARRAY_A
        );

        $stats['statuses'] = array();
        foreach ($statuses as $status) {
            $stats['statuses'][$status['status']] = $status['count'];
        }

        // Recent scan activity (last 7 days)
        $stats['recent_activity'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} 
             WHERE scan_date > DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );

        return $stats;
    }

    /**
     * Quick security check for upload-time validation
     * 
     * @param string $svg_content SVG content to check
     * @return array Check result with threat level and threats found
     */
    public function quick_security_check($svg_content) {
        $scan_result = array(
            'threats' => array(),
            'threat_level' => 'low'
        );

        // Run quick critical threat checks
        $this->analyze_svg_content($svg_content, $scan_result);
        
        // Determine overall threat level
        $scan_result['threat_level'] = $this->calculate_threat_level($scan_result['threats']);

        return $scan_result;
    }

    /**
     * Scan media library with performance optimizations
     * 
     * @param array $options Scan options
     * @return array Scan results summary
     */
    public function scan_media_library_optimized($options = array()) {
        $defaults = array(
            'force_rescan' => false,
            'limit' => 0, // 0 = no limit
            'offset' => 0,
            'include_quarantined' => false,
            'batch_size' => 25, // Process in batches to manage memory
            'max_execution_time' => 0, // 0 = no limit
            'memory_limit_mb' => 128, // Memory limit per batch
        );
        
        $options = wp_parse_args($options, $defaults);

        // Set execution time limit if specified
        if ($options['max_execution_time'] > 0) {
            @set_time_limit($options['max_execution_time']);
        }

        $start_time = microtime(true);
        $memory_start = memory_get_usage();
        
        // Get all SVG attachments
        $svg_attachments = $this->get_svg_attachments($options);
        
        $scan_summary = array(
            'total_files' => count($svg_attachments),
            'total_scanned' => 0,
            'threats_found' => 0,
            'files_with_issues' => array(),
            'scan_start_time' => current_time('mysql'),
            'scan_duration' => 0,
            'memory_usage' => 0,
            'batches_processed' => 0,
            'errors' => array()
        );

        $batch_count = 0;
        $files_processed = 0;
        
        // Process files in batches to manage memory usage
        for ($i = 0; $i < count($svg_attachments); $i += $options['batch_size']) {
            $batch = array_slice($svg_attachments, $i, $options['batch_size']);
            $batch_count++;
            
            // Check memory usage before processing batch
            $current_memory = memory_get_usage() / 1024 / 1024; // MB
            if ($current_memory > $options['memory_limit_mb']) {
                asa_log("Memory limit reached ({$current_memory}MB), stopping scan", 'warning');
                $scan_summary['errors'][] = "Memory limit reached after processing {$files_processed} files";
                break;
            }

            foreach ($batch as $attachment) {
                try {
                    $scan_result = $this->scan_svg_file($attachment->ID, $options['force_rescan']);
                    
                    if ($scan_result) {
                        $scan_summary['total_scanned']++;
                        
                        if (!empty($scan_result['threats'])) {
                            $scan_summary['threats_found'] += count($scan_result['threats']);
                            $scan_summary['files_with_issues'][] = $scan_result;
                        }
                    }
                    
                    $files_processed++;
                    
                } catch (Exception $e) {
                    $error_msg = "Failed to scan file ID {$attachment->ID}: " . $e->getMessage();
                    asa_log($error_msg, 'error');
                    $scan_summary['errors'][] = $error_msg;
                }
            }
            
            // Clear memory after each batch
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
            
            // Optional: Add small delay between batches to reduce server load
            if ($batch_count % 5 === 0) {
                usleep(100000); // 0.1 second pause every 5 batches
            }
        }

        $scan_summary['scan_duration'] = round(microtime(true) - $start_time, 2);
        $scan_summary['memory_usage'] = round((memory_get_peak_usage() - $memory_start) / 1024 / 1024, 2);
        $scan_summary['batches_processed'] = $batch_count;

        // Log scan summary
        $this->log_scan_summary($scan_summary);

        return $scan_summary;
    }

    /**
     * Process scheduled scan with performance monitoring
     */
    public function process_scheduled_scan() {
        $start_time = time();
        $max_execution_time = 300; // 5 minutes max for scheduled scans
        
        // Get scheduled scan options
        $scheduled_options = get_option('asa_scheduled_scan_options', array());
        
        if (empty($scheduled_options['enable_scheduled_scans'])) {
            return false;
        }

        // Configure scan options for performance
        $scan_options = array(
            'force_rescan' => false,
            'batch_size' => 15, // Smaller batches for scheduled scans
            'max_execution_time' => $max_execution_time,
            'memory_limit_mb' => 64, // Conservative memory limit
            'include_quarantined' => false
        );

        try {
            $results = $this->scan_media_library_optimized($scan_options);
            
            // Check if we should send notifications
            if ($this->should_send_notification($results, $scheduled_options)) {
                $this->send_threat_notification($results, $scheduled_options);
            }
            
            // Update last scan timestamp
            update_option('asa_last_scheduled_scan', current_time('mysql'));
            
            asa_log(sprintf(
                'Scheduled SVG scan completed: %d files scanned, %d threats found in %s seconds (%d batches, %sMB memory)',
                $results['total_scanned'],
                $results['threats_found'],
                $results['scan_duration'],
                $results['batches_processed'],
                $results['memory_usage']
            ), 'info');

            return $results;
            
        } catch (Exception $e) {
            asa_log('Scheduled SVG scan failed: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Check if notification should be sent based on scan results
     */
    private function should_send_notification($results, $options) {
        if (empty($options['email_notifications'])) {
            return false;
        }

        if (empty($results['files_with_issues'])) {
            return false;
        }

        $threshold = $options['notification_threshold'] ?? 'high';
        $threat_levels = array('critical', 'high', 'medium', 'low');
        $threshold_index = array_search($threshold, $threat_levels);
        
        // Check if any threats meet the notification threshold
        foreach ($results['files_with_issues'] as $file) {
            $file_threat_index = array_search($file['threat_level'], $threat_levels);
            if ($file_threat_index <= $threshold_index) {
                return true;
            }
        }

        return false;
    }

    /**
     * Send email notification about threats found
     */
    private function send_threat_notification($results, $options) {
        $threshold = $options['notification_threshold'] ?? 'high';
        
        // Filter files by notification threshold
        $notable_files = array_filter($results['files_with_issues'], function($file) use ($threshold) {
            $threat_levels = array('critical', 'high', 'medium', 'low');
            $threshold_index = array_search($threshold, $threat_levels);
            $file_threat_index = array_search($file['threat_level'], $threat_levels);
            return $file_threat_index <= $threshold_index;
        });

        if (empty($notable_files)) {
            return;
        }

        // Get administrator emails
        $admin_emails = array();
        $admins = get_users(array('role' => 'administrator'));
        foreach ($admins as $admin) {
            $admin_emails[] = $admin->user_email;
        }

        if (empty($admin_emails)) {
            return;
        }

        // Prepare email content
        $site_name = get_bloginfo('name');
        $subject = sprintf(__('[%s] SVG Security Alert - %s Threats Detected', ASA_TEXT_DOMAIN), $site_name, ucfirst($threshold));
        
        $message = sprintf(__('The SVG Security Scanner has detected %d files with %s or higher security threats.', ASA_TEXT_DOMAIN), 
                          count($notable_files), $threshold) . "\n\n";

        $message .= __('Affected files:', ASA_TEXT_DOMAIN) . "\n";
        foreach ($notable_files as $file) {
            $threat_count = count($file['threats']);
            $message .= sprintf("- %s (%s threat level, %d threats)\n", 
                              basename($file['file_path']), 
                              $file['threat_level'], 
                              $threat_count);
        }

        $message .= "\n" . __('Scan Summary:', ASA_TEXT_DOMAIN) . "\n";
        $message .= sprintf("- Total files scanned: %d\n", $results['total_scanned']);
        $message .= sprintf("- Total threats found: %d\n", $results['threats_found']);
        $message .= sprintf("- Scan duration: %s seconds\n", $results['scan_duration']);
        $message .= sprintf("- Memory usage: %sMB\n", $results['memory_usage']);

        $message .= "\n" . sprintf(__('Please review these files immediately: %s', ASA_TEXT_DOMAIN), 
                                   admin_url('tools.php?page=asa-svg-scanner'));

        $headers = array('Content-Type: text/plain; charset=UTF-8');

        // Send notification
        $sent = wp_mail($admin_emails, $subject, $message, $headers);
        
        if ($sent) {
            asa_log("SVG security notification sent to " . count($admin_emails) . " administrators", 'info');
        } else {
            asa_log("Failed to send SVG security notification", 'error');
        }
    }
}
