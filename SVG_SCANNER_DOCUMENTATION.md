# SVG Security Scanner Documentation

## Overview

The Advanced SVG Animator plugin includes a robust, PHP-based SVG content scanner that provides comprehensive security auditing of SVG files in the WordPress media library. The scanner goes beyond basic sanitization to detect sophisticated malicious patterns and provides administrators with tools to quarantine or delete suspicious files.

## Key Features

### 1. **Comprehensive Media Library Scanning**
- Scans all existing SVG files in the WordPress media library
- Accessible via admin interface under "Tools" → "SVG Security" or "Media" → "SVG Security"
- Supports both full library scans and individual file scanning
- Provides force rescan option to bypass cached results

### 2. **Advanced Threat Detection**
The scanner detects multiple categories of suspicious patterns:

#### **Script-based Threats (Critical)**
- `<script>` tags that can execute JavaScript
- `javascript:` URLs in href/src attributes
- `eval()` functions for arbitrary code execution
- `document.write()` calls that can modify page content

#### **Server-side Threats (Critical)**
- PHP execution tags (`<?php`, `<?=`)
- Server-side script references

#### **Event Handler Threats (High)**
- JavaScript event handlers (`onload`, `onclick`, `onerror`, etc.)
- Event-driven malicious code execution

#### **Encoding-based Obfuscation (High/Critical)**
- Base64 encoded malicious content
- URL encoded suspicious patterns
- HTML entity encoded threats
- Hexadecimal string obfuscation

#### **External References (Medium/High)**
- Links to non-media external resources
- Suspicious file types (`.php`, `.exe`, `.dll`, etc.)
- URLs with dangerous query parameters

#### **Structural Threats (Medium/High)**
- Dangerous HTML elements (`iframe`, `object`, `embed`)
- Foreign object elements that can embed arbitrary content
- Nested SVG elements for obfuscation
- Malformed XML that could bypass parsers

### 3. **Database Logging & Reporting**
- Custom database table (`wp_asa_svg_scan_results`) stores all scan results
- Tracks threat levels, file hashes, scan dates, and status
- Maintains audit trail of all security actions
- Supports filtering and searching of results

### 4. **Administrative Actions**
- **Quarantine**: Moves suspicious files to secure quarantine directory
- **Delete**: Permanently removes files via WordPress built-in functions
- **Rescan**: Force rescan of individual files
- Real-time AJAX interface for seamless user experience

## DOMDocument Implementation & XML Parsing

### Why DOMDocument?

The scanner uses PHP's `DOMDocument` class for robust XML parsing because:

1. **Standards Compliance**: DOMDocument follows W3C DOM specifications
2. **Error Handling**: Provides detailed XML parsing error reporting
3. **XPath Support**: Enables powerful element/attribute querying
4. **Memory Efficiency**: Handles large XML files efficiently
5. **Security**: Built-in protection against XML entity attacks

### Implementation Details

```php
/**
 * Analyze SVG DOM structure using DOMDocument
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
            'embed' => 'high'
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

        // Analyze all attributes across all elements
        $all_elements = $xpath->query('//*');
        foreach ($all_elements as $element) {
            if ($element->hasAttributes()) {
                foreach ($element->attributes as $attr) {
                    $this->analyze_attribute($attr, $scan_result);
                }
            }
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
```

### Error Handling Best Practices

#### 1. **Enable Internal Error Handling**
```php
// Enable user error handling before parsing
libxml_use_internal_errors(true);
```

#### 2. **Check Parse Results**
```php
if (!$dom->loadXML($svg_content)) {
    $xml_errors = libxml_get_errors();
    // Handle parsing errors
    foreach ($xml_errors as $error) {
        // Log error details: $error->message, $error->line, $error->column
    }
    libxml_clear_errors();
    return false; // Cannot continue parsing
}
```

#### 3. **Always Clean Up**
```php
finally {
    // Always clear libxml errors to prevent memory leaks
    libxml_clear_errors();
}
```

#### 4. **Validate DOM Structure**
```php
// Verify we have a valid SVG root element
$svg_elements = $xpath->query('//svg');
if ($svg_elements->length === 0) {
    // Not a valid SVG file
    return false;
}
```

### XPath Query Examples

#### Finding Dangerous Elements
```php
// Find all script tags anywhere in document
$scripts = $xpath->query('//script');

// Find elements with specific attributes
$js_handlers = $xpath->query('//*[@onclick or @onload or @onerror]');

// Find external references
$external_refs = $xpath->query('//[@href[starts-with(., "http")] or @src[starts-with(., "http")]]');
```

#### Analyzing Attributes
```php
foreach ($element->attributes as $attr) {
    $attr_name = $attr->nodeName;
    $attr_value = $attr->nodeValue;
    
    // Check for JavaScript URLs
    if (in_array($attr_name, array('href', 'xlink:href')) && 
        preg_match('/^javascript:/i', $attr_value)) {
        // Flag as critical threat
    }
}
```

## Database Schema

The scanner creates a dedicated table for storing scan results:

```sql
CREATE TABLE wp_asa_svg_scan_results (
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
);
```

## Usage Guide

### Administrative Interface

1. **Access Scanner**: Navigate to "Tools" → "SVG Security" in WordPress admin
2. **Start Scan**: Click "Scan Media Library" to scan all SVG files
3. **View Results**: Review detected threats in the results table
4. **Take Action**: Use Quarantine/Delete buttons for suspicious files
5. **Filter Results**: Use threat level and status filters to focus on specific issues

### Programmatic Usage

#### Scan Single File
```php
$scanner = new ASA_SVG_Security_Scanner();
$result = $scanner->scan_svg_file($attachment_id, $force_rescan = false);

if ($result && !empty($result['threats'])) {
    // Handle threats found
    foreach ($result['threats'] as $threat) {
        error_log("Threat found: " . $threat['description']);
    }
}
```

#### Scan Entire Library
```php
$options = array(
    'force_rescan' => false,
    'limit' => 0, // No limit
    'include_quarantined' => false
);

$summary = $scanner->scan_media_library($options);
echo "Scanned: " . $summary['total_scanned'] . " files";
echo "Threats: " . $summary['threats_found'];
```

#### Get Scan Statistics
```php
$stats = $scanner->get_scan_statistics();
print_r($stats['threat_levels']); // Breakdown by threat level
print_r($stats['statuses']); // Breakdown by file status
```

## Security Considerations

### File Quarantine
- Quarantined files are moved to `/wp-content/uploads/asa-quarantine/`
- Directory is protected with `.htaccess deny from all`
- Files are renamed with timestamps to prevent conflicts
- Original file references are preserved in database

### Upload-time Scanning
- Critical threats block file upload immediately
- Non-critical threats are logged but allow upload
- Integrates with existing sanitization process
- Provides real-time protection against malicious uploads

### Performance Optimization
- Caches scan results for 24 hours based on file hash
- Supports batched scanning with memory management
- Uses efficient XPath queries for DOM analysis
- Implements pagination for large result sets

## Scheduled Scanning

The plugin automatically schedules daily scans via WordPress cron:

```php
// Schedule daily security scan
if (!wp_next_scheduled('asa_daily_svg_scan')) {
    wp_schedule_event(time(), 'daily', 'asa_daily_svg_scan');
}
```

Results are logged and can trigger admin notifications for critical findings.

## Extensibility

### Custom Threat Detection
Developers can extend threat detection by hooking into the scanning process:

```php
add_filter('asa_svg_custom_threats', function($threats, $svg_content, $attachment_id) {
    // Add custom threat detection logic
    if (strpos($svg_content, 'custom_malicious_pattern') !== false) {
        $threats[] = array(
            'type' => 'custom_threat',
            'severity' => 'high',
            'description' => 'Custom malicious pattern detected'
        );
    }
    return $threats;
}, 10, 3);
```

### Custom Actions
Add custom actions for scan results:

```php
add_action('asa_svg_threat_detected', function($scan_result) {
    if ($scan_result['threat_level'] === 'critical') {
        // Send email notification
        wp_mail(get_option('admin_email'), 
                'Critical SVG Threat Detected', 
                'A critical threat was found in: ' . $scan_result['file_path']);
    }
});
```

This comprehensive scanner provides enterprise-level security auditing for SVG files while maintaining ease of use for WordPress administrators.
