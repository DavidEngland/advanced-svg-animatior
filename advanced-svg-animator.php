<?php
/**
 * Plugin Name: Advanced SVG Animator
 * Plugin URI: https://github.com/DavidEngland/advanced-svg-animator
 * Description: A comprehensive WordPress plugin for advanced SVG animations with security scanner and real estate icon library. Built by Real Estate Intelligence Agency (REIA) for professional WordPress sites.
 * Version: 1.0.0
 * Author: David England
 * Author URI: https://realestate-huntsville.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: advanced-svg-animator
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * GitHub Plugin URI: DavidEngland/advanced-svg-animator
 */

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ASA_VERSION', '1.0.0');
define('ASA_PLUGIN_FILE', __FILE__);
define('ASA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ASA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ASA_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('ASA_INCLUDES_DIR', ASA_PLUGIN_DIR . 'includes/');
define('ASA_FUNCTIONS_DIR', ASA_PLUGIN_DIR . 'functions/');

// Load Composer autoloader if available
if (file_exists(ASA_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once ASA_PLUGIN_DIR . 'vendor/autoload.php';
}
define('ASA_ASSETS_URL', ASA_PLUGIN_URL . 'assets/');
define('ASA_TEXT_DOMAIN', 'advanced-svg-animator');

// Debug mode - can be enabled in wp-config.php with: define('ASA_DEBUG', true);
if (!defined('ASA_DEBUG')) {
    define('ASA_DEBUG', false);
}

// Load logging functions
require_once ASA_FUNCTIONS_DIR . 'logging-functions.php';

/**
 * Main plugin class for Advanced SVG Animator
 */
class ASA_Plugin {

    /**
     * Single instance of the plugin
     * @var ASA_Plugin
     */
    private static $instance = null;

    /**
     * Admin settings instance
     * @var ASA_Admin_Settings
     */
    private $admin_settings = null;

    /**
     * SVG Scanner Admin instance
     * @var ASA_SVG_Scanner_Admin
     */
    private $svg_scanner_admin = null;

    /**
     * Get the single instance of the plugin
     * @return ASA_Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - Set up plugin hooks
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Core plugin hooks
        add_action('init', array($this, 'init_plugin'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Block registration hooks
        add_action('init', array($this, 'register_blocks'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));

        // REST API hooks
        add_action('rest_api_init', array($this, 'register_rest_routes'));

        // SVG support hooks (conditional to avoid conflicts)
        add_action('init', array($this, 'maybe_enable_svg_support'));

        // Security hooks (always enabled for safety)
        add_filter('wp_handle_upload_prefilter', array($this, 'asa_sanitize_svg_on_upload'), 10, 1);
        add_filter('wp_handle_upload_prefilter', array($this, 'enhanced_svg_scan_on_upload'), 15, 1);
        add_filter('wp_handle_upload_prefilter', array($this, 'check_svg_upload_security'), 20, 1);

        // Scheduled scanning hooks
        add_action('asa_daily_svg_scan', array($this, 'perform_scheduled_scan'));
        add_action('asa_scheduled_svg_scan', array($this, 'perform_enhanced_scheduled_scan'));
        add_action('init', array($this, 'schedule_svg_scans'));
        add_action('init', array($this, 'register_custom_cron_schedules'));

        // Plugin lifecycle hooks
        register_activation_hook(ASA_PLUGIN_FILE, array($this, 'activate_plugin'));
        register_deactivation_hook(ASA_PLUGIN_FILE, array($this, 'deactivate_plugin'));
        register_uninstall_hook(ASA_PLUGIN_FILE, array('ASA_Plugin', 'uninstall_plugin'));
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Load SVG sanitization library
        $this->load_svg_sanitizer();

        // Load core includes
        if (is_admin()) {
            $this->load_admin_dependencies();
        }

        $this->load_frontend_dependencies();
    }

    /**
     * Load SVG sanitization library
     */
    private function load_svg_sanitizer() {
        // Check if the library is available via Composer autoloader
        if (file_exists(ASA_PLUGIN_DIR . 'vendor/autoload.php')) {
            require_once ASA_PLUGIN_DIR . 'vendor/autoload.php';
        } else {
            // Fallback: Load our custom implementation
            require_once ASA_INCLUDES_DIR . 'class-asa-svg-sanitizer.php';
        }
    }

    /**
     * Load admin-specific dependencies
     */
    private function load_admin_dependencies() {
        // Load Simple History logger integration (only if file exists)
        if (file_exists(ASA_INCLUDES_DIR . 'class-asa-simple-history-logger.php')) {
            require_once ASA_INCLUDES_DIR . 'class-asa-simple-history-logger.php';
        }
        
        // Load admin settings page
        require_once ASA_INCLUDES_DIR . 'class-asa-admin-settings.php';
        $this->admin_settings = new ASA_Admin_Settings();

        // Load SVG security scanner admin
        require_once ASA_INCLUDES_DIR . 'class-asa-svg-scanner-admin.php';
        $this->svg_scanner_admin = new ASA_SVG_Scanner_Admin();
    }

    /**
     * Load frontend dependencies
     */
    private function load_frontend_dependencies() {
        // Frontend dependencies will be loaded here
        // Example: require_once ASA_INCLUDES_DIR . 'frontend/class-asa-frontend.php';
    }

    /**
     * Initialize the plugin
     */
    public function init_plugin() {
        // Load text domain for translations
        load_plugin_textdomain(
            ASA_TEXT_DOMAIN,
            false,
            dirname(ASA_PLUGIN_BASENAME) . '/languages'
        );

        // Initialize plugin components
        $this->init_components();
    }

    /**
     * Conditionally enable SVG support to avoid conflicts
     */
    public function maybe_enable_svg_support() {
        // Check if we should enable SVG support
        if ($this->should_enable_svg_support()) {
            // Add SVG support hooks
            add_filter('upload_mimes', array($this, 'enable_svg_mime_types'));
            add_action('admin_head', array($this, 'enable_svg_media_previews'));
            add_filter('wp_check_filetype_and_ext', array($this, 'fix_svg_mime_type'), 10, 5);
            
            asa_log('Advanced SVG Animator: SVG support enabled', 'info');
        } else {
            asa_log('Advanced SVG Animator: SVG support disabled to avoid conflicts', 'info');
        }
    }

    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Initialize main plugin components
        // This will be expanded as components are added
    }

    /**
     * Admin initialization
     */
    public function admin_init() {
        // Admin-specific initialization
        $this->check_plugin_requirements();
        $this->check_svg_plugin_conflicts();
    }

    /**
     * Check if plugin requirements are met
     */
    private function check_plugin_requirements() {
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            add_action('admin_notices', array($this, 'wp_version_notice'));
            return false;
        }

        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', array($this, 'php_version_notice'));
            return false;
        }

        return true;
    }

    /**
     * Check for SVG plugin conflicts and show admin notices
     */
    private function check_svg_plugin_conflicts() {
        // Get stored conflicts from should_enable_svg_support() check
        $active_conflicts = get_option('asa_detected_svg_conflicts', array());
        
        // Check if SVG is already supported
        $existing_mimes = get_allowed_mime_types();
        $svg_already_supported = isset($existing_mimes['svg']) || in_array('image/svg+xml', $existing_mimes);

        if (!empty($active_conflicts)) {
            add_action('admin_notices', function() use ($active_conflicts) {
                echo '<div class="notice notice-warning is-dismissible">';
                echo '<p><strong>' . esc_html__('Advanced SVG Animator - Plugin Conflicts Detected', ASA_TEXT_DOMAIN) . '</strong></p>';
                echo '<p>' . sprintf(
                    esc_html__('Detected active SVG plugins: %s', ASA_TEXT_DOMAIN),
                    implode(', ', array_map('esc_html', $active_conflicts))
                ) . '</p>';
                echo '<p>' . esc_html__('SVG upload support has been automatically disabled to prevent conflicts. Animation and security features will still work.', ASA_TEXT_DOMAIN) . '</p>';
                echo '<p>';
                echo '<a href="' . esc_url(admin_url('options-general.php?page=asa-settings')) . '" class="button button-secondary">' . esc_html__('Manage Settings', ASA_TEXT_DOMAIN) . '</a> ';
                echo '<a href="' . esc_url(admin_url('tools.php?page=asa-svg-scanner')) . '" class="button button-secondary">' . esc_html__('SVG Scanner', ASA_TEXT_DOMAIN) . '</a>';
                echo '</p>';
                echo '</div>';
            });
        } elseif ($svg_already_supported) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-info is-dismissible">';
                echo '<p><strong>' . esc_html__('Advanced SVG Animator - SVG Support Detected', ASA_TEXT_DOMAIN) . '</strong></p>';
                echo '<p>' . esc_html__('SVG uploads are already enabled by your theme or another plugin. ASA\'s animation and security features are active.', ASA_TEXT_DOMAIN) . '</p>';
                echo '<p>';
                echo '<a href="' . esc_url(admin_url('options-general.php?page=asa-settings')) . '" class="button button-secondary">' . esc_html__('Configure Settings', ASA_TEXT_DOMAIN) . '</a>';
                echo '</p>';
                echo '</div>';
            });
        }
    }

    /**
     * Register Gutenberg blocks
     */
    public function register_blocks() {
        // Check if function exists (WordPress 5.0+)
        if (!function_exists('register_block_type')) {
            return;
        }

        // Register the original SVG Animator block (if block.json exists)
        if (file_exists(ASA_PLUGIN_DIR . 'blocks/svg-animator/block.json')) {
            register_block_type(ASA_PLUGIN_DIR . 'blocks/svg-animator');
        }

        // Register the simple SVG Animator block
        register_block_type('advanced-svg-animator/svg-animator', array(
            'editor_script' => 'asa-svg-animator-simple-block',
            'editor_style' => 'asa-svg-animator-block-editor',
            'style' => 'asa-svg-animator-frontend',
            'render_callback' => array($this, 'render_simple_svg_block')
        ));
    }

    /**
     * Enqueue block editor assets
     */
    public function enqueue_block_editor_assets() {
        // Enqueue a simpler, working block
        wp_enqueue_script(
            'asa-svg-animator-simple-block',
            ASA_PLUGIN_URL . 'assets/js/simple-svg-block.js',
            array(
                'wp-blocks',
                'wp-i18n',
                'wp-element',
                'wp-components',
                'wp-block-editor',
                'wp-api-fetch'
            ),
            ASA_VERSION,
            true
        );

        // Enqueue block editor CSS
        wp_enqueue_style(
            'asa-svg-animator-block-editor',
            ASA_PLUGIN_URL . 'assets/css/svg-animator-block.css',
            array('wp-edit-blocks'),
            ASA_VERSION
        );

        // Localize script with plugin data
        wp_localize_script('asa-svg-animator-block', 'asaBlockData', array(
            'pluginUrl' => ASA_PLUGIN_URL,
            'restUrl' => rest_url('wp/v2/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'canUploadSVG' => $this->can_user_upload_svgs(),
            'animations' => $this->get_available_animations(),
            'triggerOptions' => array(
                array('label' => __('On Load', ASA_TEXT_DOMAIN), 'value' => 'onLoad'),
                array('label' => __('On Scroll', ASA_TEXT_DOMAIN), 'value' => 'onScroll'),
                array('label' => __('On Hover', ASA_TEXT_DOMAIN), 'value' => 'onHover'),
                array('label' => __('On Click', ASA_TEXT_DOMAIN), 'value' => 'onClick'),
            ),
            'hoverTargetOptions' => array(
                array('label' => __('SVG Element', ASA_TEXT_DOMAIN), 'value' => 'svg'),
                array('label' => __('Block Container', ASA_TEXT_DOMAIN), 'value' => 'block'),
                array('label' => __('Custom Selector', ASA_TEXT_DOMAIN), 'value' => 'custom'),
            ),
        ));
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on relevant admin pages
        if (!in_array($hook, array('post.php', 'post-new.php', 'edit.php'))) {
            return;
        }

        // Check if we're on a page that supports the block editor
        if (!function_exists('use_block_editor_for_post_type')) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || !use_block_editor_for_post_type($screen->post_type)) {
            return;
        }

        // Enqueue admin-specific styles if needed
        wp_enqueue_style(
            'asa-admin-style',
            ASA_PLUGIN_URL . 'blocks/svg-animator/editor.css',
            array(),
            ASA_VERSION
        );
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        // Only enqueue if the page contains SVG animator blocks
        if (!has_block('asa/svg-animator') && !has_block('advanced-svg-animator/svg-animator')) {
            return;
        }

        // Enqueue frontend CSS for simple SVG animator block
        wp_enqueue_style(
            'asa-svg-animator-frontend',
            ASA_PLUGIN_URL . 'assets/css/svg-animator-block.css',
            array(),
            ASA_VERSION
        );

        // Enqueue legacy frontend CSS if legacy block exists
        if (has_block('asa/svg-animator')) {
            wp_enqueue_style(
                'asa-frontend-style',
                ASA_PLUGIN_URL . 'blocks/svg-animator/style.css',
                array(),
                ASA_VERSION
            );
        }

        // Enqueue frontend JavaScript
        wp_enqueue_script(
            'asa-frontend-script',
            ASA_PLUGIN_URL . 'blocks/svg-animator/frontend.js',
            array(),
            ASA_VERSION,
            true
        );

        // Localize script with animation data
        wp_localize_script('asa-frontend-script', 'asaFrontend', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('asa_frontend_nonce'),
            'animations' => $this->get_available_animations(),
            'reducedMotion' => get_option('asa_respect_reduced_motion', true)
        ));
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        // Register route for SVG content fetching
        register_rest_route('asa/v1', '/svg/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_svg_content_rest'),
            'permission_callback' => array($this, 'check_svg_permissions'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));

        // Register route for animation presets
        register_rest_route('asa/v1', '/animations', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_animations_rest'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * REST API callback for SVG content
     */
    public function get_svg_content_rest($request) {
        $attachment_id = intval($request['id']);

        if (!$attachment_id) {
            return new WP_Error('invalid_id', 'Invalid attachment ID', array('status' => 400));
        }

        $attachment = get_post($attachment_id);
        if (!$attachment || $attachment->post_type !== 'attachment') {
            return new WP_Error('not_found', 'Attachment not found', array('status' => 404));
        }

        $file_path = get_attached_file($attachment_id);
        if (!$file_path || !file_exists($file_path)) {
            return new WP_Error('file_not_found', 'SVG file not found', array('status' => 404));
        }

        $svg_content = file_get_contents($file_path);
        if ($svg_content === false) {
            return new WP_Error('read_error', 'Could not read SVG file', array('status' => 500));
        }

        return array(
            'content' => $svg_content,
            'filename' => basename($file_path),
            'url' => wp_get_attachment_url($attachment_id)
        );
    }

    /**
     * REST API callback for animations
     */
    public function get_animations_rest($request) {
        return $this->get_available_animations();
    }

    /**
     * Check permissions for SVG REST endpoints
     */
    public function check_svg_permissions($request) {
        return current_user_can('edit_posts');
    }

    /**
     * Enable SVG MIME types for WordPress uploads
     *
     * @param array $mimes Existing MIME types
     * @return array Modified MIME types with SVG support
     */
    public function enable_svg_mime_types($mimes) {
        // Check if SVG support is already enabled by another plugin/theme
        if (isset($mimes['svg']) && $mimes['svg'] === 'image/svg+xml') {
            // SVG already supported, just return existing mimes
            return $mimes;
        }

        // Only allow SVG uploads for users with proper capabilities
        if (!$this->can_user_upload_svgs()) {
            return $mimes;
        }

        // Check if we should enable SVG support (setting to disable conflicts)
        if (!$this->should_enable_svg_support()) {
            return $mimes;
        }

        $mimes['svg'] = 'image/svg+xml';
        $mimes['svgz'] = 'image/svg+xml';

        return $mimes;
    }

    /**
     * Fix SVG MIME type detection
     *
     * @param array $data File data
     * @param string $file File path
     * @param string $filename File name
     * @param array $mimes Allowed MIME types
     * @param string $real_mime Actual MIME type
     * @return array Modified file data
     */
    public function fix_svg_mime_type($data, $file, $filename, $mimes, $real_mime) {
        if (!$this->can_user_upload_svgs()) {
            return $data;
        }

        $file_ext = pathinfo($filename, PATHINFO_EXTENSION);

        if ($file_ext === 'svg') {
            $data['ext'] = 'svg';
            $data['type'] = 'image/svg+xml';
        }

        return $data;
    }

    /**
     * Enable SVG previews in the media library
     */
    public function enable_svg_media_previews() {
        if (!$this->is_media_screen()) {
            return;
        }

        echo '<style>
            .attachment-266x266,
            .thumbnail img {
                width: 100% !important;
                height: auto !important;
            }

            .media-icon img[src$=".svg"] {
                width: 100%;
                height: auto;
            }

            .attachment .thumbnail img[src$=".svg"] {
                width: 100%;
                height: auto;
            }
        </style>';
    }

    /**
     * Sanitize SVG files on upload using robust sanitization library
     *
     * @param array $file Upload file data
     * @return array File data (potentially modified)
     */
    public function asa_sanitize_svg_on_upload($file) {
        // Only process SVG files
        if (!isset($file['type']) || $file['type'] !== 'image/svg+xml') {
            return $file;
        }

        // Check if another plugin already processed this file
        if (isset($file['asa_processed'])) {
            return $file;
        }

        // Mark as processed to avoid double processing
        $file['asa_processed'] = true;

        // Check if user has permission to upload SVGs
        if (!$this->can_user_upload_svgs()) {
            $file['error'] = $this->get_svg_upload_error_message();
            return $file;
        }

        // Check if sanitization is enabled
        if (!$this->is_svg_sanitization_enabled()) {
            asa_log('SVG sanitization is disabled - uploading without sanitization', 'warning');
            return $file;
        }

        // Check if file exists
        if (!file_exists($file['tmp_name'])) {
            $file['error'] = __('SVG file could not be found for processing.', ASA_TEXT_DOMAIN);
            return $file;
        }

        try {
            // Load SVG content
            $svg_content = file_get_contents($file['tmp_name']);

            if (empty($svg_content)) {
                $file['error'] = __('SVG file appears to be empty or corrupted.', ASA_TEXT_DOMAIN);
                return $file;
            }

            // Sanitize the SVG content
            $sanitized_content = $this->sanitize_svg_content_with_library($svg_content);

            if ($sanitized_content === false) {
                $file['error'] = __('SVG file contains potentially harmful content and cannot be sanitized safely.', ASA_TEXT_DOMAIN);
                asa_log('SVG sanitization failed for file: ' . $file['name'], 'error');
                return $file;
            }

            // Write sanitized content back to the temporary file
            $bytes_written = file_put_contents($file['tmp_name'], $sanitized_content);

            if ($bytes_written === false) {
                $file['error'] = __('Failed to save sanitized SVG content.', ASA_TEXT_DOMAIN);
                asa_log('Failed to write sanitized SVG content for file: ' . $file['name'], 'error');
                return $file;
            }

            // Update file size if it changed
            $file['size'] = strlen($sanitized_content);

            // Log successful sanitization
            asa_log(sprintf(
                'Successfully sanitized SVG file: %s (Original: %d bytes, Sanitized: %d bytes)',
                $file['name'],
                strlen($svg_content),
                $bytes_written
            ), 'info');

            // Log to Simple History
            if (class_exists('ASA_Simple_History_Logger')) {
                ASA_Simple_History_Logger::log_svg_upload(
                    0, // Attachment ID not available yet at this point
                    $file['name'],
                    array(
                        'size' => $file['size'],
                        'original_size' => strlen($svg_content),
                        'sanitized' => true
                    )
                );
            }

        } catch (Exception $e) {
            $file['error'] = sprintf(
                __('SVG sanitization failed: %s', ASA_TEXT_DOMAIN),
                $e->getMessage()
            );
            asa_log('SVG sanitization exception: ' . $e->getMessage(), 'error');
            return $file;
        }

        return $file;
    }

    /**
     * Sanitize SVG content using robust sanitization library
     *
     * @param string $svg_content Raw SVG content
     * @return string|false Sanitized SVG content or false on failure
     */
    private function sanitize_svg_content_with_library($svg_content) {
        // Get sanitization level from settings
        $sanitization_level = $this->get_sanitization_level();

        // Try to use enshrined/svg-sanitize if available
        if (class_exists('\enshrined\svgSanitize\Sanitizer')) {
            return $this->sanitize_with_enshrined_library($svg_content, $sanitization_level);
        }

        // Fallback to our custom sanitizer
        return $this->sanitize_with_custom_sanitizer($svg_content, $sanitization_level);
    }

    /**
     * Sanitize SVG using enshrined/svg-sanitize library
     *
     * @param string $svg_content Raw SVG content
     * @param string $level Sanitization level
     * @return string|false Sanitized SVG content or false on failure
     */
    private function sanitize_with_enshrined_library($svg_content, $level = 'advanced') {
        try {
            // Check if the library is available
            if (!class_exists('enshrined\svgSanitize\Sanitizer')) {
                asa_log('Enshrined SVG Sanitizer library not found, falling back to custom sanitizer', 'warning');
                return false;
            }

            // Create sanitizer instance
            $sanitizer = new \enshrined\svgSanitize\Sanitizer();

            // Remove XML declaration and DOCTYPE for web safety
            $sanitizer->removeXMLTag(true);

            // The library comes with sensible defaults for SVG elements and attributes
            // We can customize it further if needed by extending the allowed tags/attributes
            // For animation support, the library already includes most necessary elements/attributes

            // Sanitize the content
            $sanitized = $sanitizer->sanitize($svg_content);

            // Validate the result
            if (empty($sanitized) || !$this->is_valid_svg_content($sanitized)) {
                asa_log('SVG sanitization failed: Invalid result', 'error');
                return false;
            }

            asa_log("SVG successfully sanitized with Enshrined library (level: {$level})", 'info');
            return $sanitized;

        } catch (Exception $e) {
            asa_log('Enshrined SVG sanitizer error: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Fallback custom SVG sanitizer
     *
     * @param string $svg_content Raw SVG content
     * @param string $level Sanitization level
     * @return string|false Sanitized SVG content or false on failure
     */
    private function sanitize_with_custom_sanitizer($svg_content, $level = 'advanced') {
        try {
            // Load DOM
            $dom = new DOMDocument();
            $dom->formatOutput = false;
            $dom->preserveWhiteSpace = true;

            // Suppress errors for malformed XML
            libxml_use_internal_errors(true);

            if (!$dom->loadXML($svg_content)) {
                return false;
            }

            libxml_clear_errors();

            // Get allowed elements and attributes based on level
            $config = $this->get_sanitization_config($level);
            $allowed_elements = $config['allowed_elements'];
            $allowed_attributes = $config['allowed_attributes'];

            // Remove dangerous elements
            $dangerous_elements = array('script', 'iframe', 'object', 'embed', 'link', 'meta', 'style');
            foreach ($dangerous_elements as $element) {
                $nodes = $dom->getElementsByTagName($element);
                $nodesToRemove = array();
                foreach ($nodes as $node) {
                    $nodesToRemove[] = $node;
                }
                foreach ($nodesToRemove as $node) {
                    if ($node->parentNode) {
                        $node->parentNode->removeChild($node);
                    }
                }
            }

            // Clean attributes on all elements
            $xpath = new DOMXPath($dom);
            $all_elements = $xpath->query('//*');

            foreach ($all_elements as $element) {
                // Skip if not a DOMElement
                if (!($element instanceof DOMElement)) {
                    continue;
                }

                // Check if element is allowed
                if (!in_array($element->nodeName, $allowed_elements)) {
                    if ($element->parentNode) {
                        $element->parentNode->removeChild($element);
                    }
                    continue;
                }

                // Clean attributes
                $attributes_to_remove = array();
                if ($element->hasAttributes()) {
                    foreach ($element->attributes as $attr) {
                        $attr_name = $attr->nodeName;
                        $attr_value = $attr->nodeValue;

                        // Remove if not in allowed list
                        if (!in_array($attr_name, $allowed_attributes)) {
                            $attributes_to_remove[] = $attr_name;
                            continue;
                        }

                        // Check for dangerous content in attribute values
                        if (preg_match('/javascript:|data:|vbscript:|on\w+=/i', $attr_value)) {
                            $attributes_to_remove[] = $attr_name;
                            continue;
                        }
                    }

                    // Remove dangerous attributes
                    foreach ($attributes_to_remove as $attr_name) {
                        if ($element->hasAttribute($attr_name)) {
                            $element->removeAttribute($attr_name);
                        }
                    }
                }
            }

            $result = $dom->saveXML();

            // Remove XML declaration if present
            $result = preg_replace('/<\?xml[^>]*\?>\s*/', '', $result);

            asa_log("SVG successfully sanitized with custom sanitizer (level: {$level})", 'info');
            return $result;

        } catch (Exception $e) {
            asa_log('Custom SVG sanitizer error: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Validate that content is still valid SVG after sanitization
     *
     * @param string $content Sanitized content
     * @return bool True if valid SVG
     */
    private function is_valid_svg_content($content) {
        if (empty($content)) {
            return false;
        }

        // Check if it contains SVG element
        if (strpos($content, '<svg') === false) {
            return false;
        }

        // Try to parse as XML
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $result = $dom->loadXML($content);
        libxml_clear_errors();

        return $result !== false;
    }

    /**
     * Check SVG upload security (legacy method, now runs after sanitization)
     *
     * @param array $file Upload file data
     * @return array File data (potentially with errors)
     */
    public function check_svg_upload_security($file) {
        // Check if this is an SVG file
        if (!isset($file['type']) || $file['type'] !== 'image/svg+xml') {
            return $file;
        }

        // Check user capabilities
        if (!$this->can_user_upload_svgs()) {
            $file['error'] = __('You do not have permission to upload SVG files.', ASA_TEXT_DOMAIN);
            return $file;
        }

        // Sanitize SVG content
        if (!$this->sanitize_svg_file($file['tmp_name'])) {
            $file['error'] = __('SVG file contains potentially harmful content and cannot be uploaded.', ASA_TEXT_DOMAIN);
            return $file;
        }

        return $file;
    }

    /**
     * Get SVG scanner instance
     *
     * @return ASA_SVG_Security_Scanner|null Scanner instance or null if not loaded
     */
    public function get_svg_scanner() {
        if (!class_exists('ASA_SVG_Security_Scanner')) {
            require_once ASA_INCLUDES_DIR . 'class-asa-svg-security-scanner.php';
        }

        return new ASA_SVG_Security_Scanner();
    }

    /**
     * Scan SVG on upload (enhanced with scanner)
     *
     * @param array $file Upload file data
     * @return array File data (potentially modified)
     */
    public function enhanced_svg_scan_on_upload($file) {
        // Run the standard sanitization first
        $file = $this->asa_sanitize_svg_on_upload($file);

        // If sanitization failed, don't continue with security scan
        if (isset($file['error'])) {
            return $file;
        }

        // Only process SVG files that passed sanitization
        if (!isset($file['type']) || $file['type'] !== 'image/svg+xml') {
            return $file;
        }

        // Get the scanner and perform security scan
        try {
            $scanner = $this->get_svg_scanner();

            // We can't scan by attachment ID yet (file not in media library)
            // But we can analyze the file content directly
            $svg_content = file_get_contents($file['tmp_name']);

            if (!empty($svg_content)) {
                // Use the scanner's quick security check method
                $temp_scan_result = $scanner->quick_security_check($svg_content);

                // If critical threats found, block the upload
                if ($temp_scan_result['threat_level'] === 'critical') {
                    $file['error'] = __('SVG file contains critical security threats and cannot be uploaded.', ASA_TEXT_DOMAIN);

                    // Log the blocked upload
                    asa_log(sprintf(
                        'Critical SVG upload blocked: %s (Threats: %s)',
                        $file['name'],
                        wp_json_encode(array_column($temp_scan_result['threats'], 'type'))
                    ), 'warning');
                }
            }
        } catch (Exception $e) {
            asa_log('SVG security scan on upload failed: ' . $e->getMessage(), 'error');
            // Don't block upload on scanner errors, but log them
        }

        return $file;
    }

    /**
     * Schedule automatic SVG scans
     */
    public function schedule_svg_scans() {
        // Schedule daily security scan if not already scheduled
        if (!wp_next_scheduled('asa_daily_svg_scan')) {
            wp_schedule_event(time(), 'daily', 'asa_daily_svg_scan');
        }
    }

    /**
     * Perform scheduled SVG security scan
     */
    public function perform_scheduled_scan() {
        try {
            $scanner = $this->get_svg_scanner();
            $results = $scanner->scan_media_library(array(
                'force_rescan' => false,
                'limit' => 100 // Limit for performance
            ));

            // Log scan results
            asa_log(sprintf(
                'Scheduled SVG scan completed: %d files scanned, %d threats found',
                $results['total_scanned'],
                $results['threats_found']
            ), 'info');

            // If critical threats found, notify administrators
            if (!empty($results['files_with_issues'])) {
                $this->notify_administrators_of_threats($results);
            }

        } catch (Exception $e) {
            asa_log('Scheduled SVG scan failed: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Register custom cron schedules for SVG scanning
     */
    public function register_custom_cron_schedules($schedules) {
        if (!is_array($schedules)) {
            $schedules = array();
        }

        // Add weekly schedule if it doesn't exist
        if (!isset($schedules['weekly'])) {
            $schedules['weekly'] = array(
                'interval' => 604800, // 7 days
                'display'  => __('Once Weekly', ASA_TEXT_DOMAIN),
            );
        }

        // Add monthly schedule if it doesn't exist
        if (!isset($schedules['monthly'])) {
            $schedules['monthly'] = array(
                'interval' => 2635200, // 30.5 days
                'display'  => __('Once Monthly', ASA_TEXT_DOMAIN),
            );
        }

        return $schedules;
    }

    /**
     * Perform enhanced scheduled scan with performance monitoring
     */
    public function perform_enhanced_scheduled_scan() {
        try {
            $scanner = $this->get_svg_scanner();
            $results = $scanner->process_scheduled_scan();

            if ($results) {
                asa_log(sprintf(
                    'Enhanced scheduled SVG scan completed: %d files scanned, %d threats found in %s seconds',
                    $results['total_scanned'],
                    $results['threats_found'],
                    $results['scan_duration']
                ), 'info');
            }

        } catch (Exception $e) {
            asa_log('Enhanced scheduled SVG scan failed: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Notify administrators of security threats found
     *
     * @param array $scan_results Scan results with threats
     */
    private function notify_administrators_of_threats($scan_results) {
        $critical_files = array_filter($scan_results['files_with_issues'], function($file) {
            return $file['threat_level'] === 'critical';
        });

        if (empty($critical_files)) {
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
        $subject = sprintf(__('[%s] Critical SVG Security Threats Detected', ASA_TEXT_DOMAIN), get_bloginfo('name'));

        $message = sprintf(__('The SVG Security Scanner has detected %d critical security threats in your media library.', ASA_TEXT_DOMAIN), count($critical_files)) . "\n\n";

        $message .= __('Affected files:', ASA_TEXT_DOMAIN) . "\n";
        foreach ($critical_files as $file) {
            $message .= "- " . basename($file['file_path']) . " (" . count($file['threats']) . " threats)\n";
        }

        $message .= "\n" . sprintf(__('Please review these files immediately in your admin dashboard: %s', ASA_TEXT_DOMAIN), admin_url('tools.php?page=asa-svg-scanner'));

        // Send notification
        wp_mail($admin_emails, $subject, $message);
    }

    /**
     * Plugin activation hook
     */
    public function activate_plugin() {
        // Set up default options
        add_option('asa_scanner_enabled', true);
        add_option('asa_scan_on_upload', true);
        add_option('asa_scheduled_scan_enabled', true);
        add_option('asa_scan_frequency', 'daily');
        add_option('asa_quarantine_enabled', true);
        add_option('asa_notify_admin', true);
        add_option('asa_respect_reduced_motion', true);
        
        // Schedule default scans
        if (!wp_next_scheduled('asa_daily_svg_scan')) {
            wp_schedule_event(time(), 'daily', 'asa_daily_svg_scan');
        }
        
        // Create necessary database tables or options if needed
        $this->create_plugin_tables();
        
        // Log activation to Simple History
        if (class_exists('ASA_Simple_History_Logger')) {
            ASA_Simple_History_Logger::log_plugin_status('activated');
        }
        
        // Log activation
        if (defined('ASA_DEBUG') && ASA_DEBUG) {
            error_log('Advanced SVG Animator: Plugin activated');
        }
    }

    /**
     * Plugin deactivation hook
     */
    public function deactivate_plugin() {
        // Clear scheduled events
        wp_clear_scheduled_hook('asa_daily_svg_scan');
        wp_clear_scheduled_hook('asa_scheduled_svg_scan');
        
        // Log deactivation to Simple History
        if (class_exists('ASA_Simple_History_Logger')) {
            ASA_Simple_History_Logger::log_plugin_status('deactivated');
        }
        
        // Log deactivation
        if (defined('ASA_DEBUG') && ASA_DEBUG) {
            error_log('Advanced SVG Animator: Plugin deactivated');
        }
    }

    /**
     * Plugin uninstallation hook
     */
    public function uninstall_plugin() {
        // Remove all plugin options
        delete_option('asa_scanner_enabled');
        delete_option('asa_scan_on_upload');
        delete_option('asa_scheduled_scan_enabled');
        delete_option('asa_scan_frequency');
        delete_option('asa_quarantine_enabled');
        delete_option('asa_notify_admin');
        delete_option('asa_respect_reduced_motion');
        delete_option('asa_last_scan_time');
        delete_option('asa_scan_results');
        
        // Clear any remaining scheduled events
        wp_clear_scheduled_hook('asa_daily_svg_scan');
        wp_clear_scheduled_hook('asa_scheduled_svg_scan');
        
        // Remove any custom database tables if created
        $this->remove_plugin_tables();
        
        // Log uninstallation
        if (defined('ASA_DEBUG') && ASA_DEBUG) {
            error_log('Advanced SVG Animator: Plugin uninstalled');
        }
    }

    /**
     * Create plugin-specific database tables if needed
     */
    private function create_plugin_tables() {
        // This method can be expanded if custom tables are needed in the future
        // For now, we're using WordPress options
    }

    /**
     * Remove plugin-specific database tables if they exist
     */
    private function remove_plugin_tables() {
        // This method can be expanded if custom tables need to be removed
        // For now, we're using WordPress options which are handled above
    }

    /**
     * Helper Methods
     */

    /**
     * Check if current screen is media library
     */
    private function is_media_screen() {
        $screen = get_current_screen();
        return $screen && (
            $screen->id === 'upload' ||
            $screen->id === 'media' ||
            $screen->base === 'upload' ||
            $screen->base === 'media'
        );
    }

    /**
     * Check if user can upload SVG files
     */
    private function can_user_upload_svgs() {
        return current_user_can('upload_files') && current_user_can('edit_posts');
    }

    /**
     * Get available animations list
     */
    private function get_available_animations() {
        return array(
            array('label' => __('None', ASA_TEXT_DOMAIN), 'value' => 'none'),
            array('label' => __('Fade In', ASA_TEXT_DOMAIN), 'value' => 'fadeIn'),
            array('label' => __('Scale Up', ASA_TEXT_DOMAIN), 'value' => 'scaleUp'),
            array('label' => __('Rotate', ASA_TEXT_DOMAIN), 'value' => 'rotate'),
            array('label' => __('Bounce', ASA_TEXT_DOMAIN), 'value' => 'bounce'),
            array('label' => __('Slide In Left', ASA_TEXT_DOMAIN), 'value' => 'slideInLeft'),
            array('label' => __('Slide In Right', ASA_TEXT_DOMAIN), 'value' => 'slideInRight'),
            array('label' => __('Pulse', ASA_TEXT_DOMAIN), 'value' => 'pulse')
        );
    }

    /**
     * Get SVG upload error message
     */
    private function get_svg_upload_error_message() {
        return __('You do not have permission to upload SVG files.', ASA_TEXT_DOMAIN);
    }

    /**
     * Check if SVG sanitization is enabled
     */
    private function is_svg_sanitization_enabled() {
        return get_option('asa_sanitize_svg', true);
    }

    /**
     * Check if plugin should enable SVG support (to avoid conflicts)
     */
    private function should_enable_svg_support() {
        // Check if disabled via setting
        $options = get_option('asa_plugin_options', array());
        if (isset($options['enable_svg_support']) && !$options['enable_svg_support']) {
            asa_log('ASA SVG support manually disabled in settings', 'info');
            return false;
        }

        // Check if SVG MIME type is already supported by WordPress
        $existing_mimes = get_allowed_mime_types();
        if (isset($existing_mimes['svg']) || in_array('image/svg+xml', $existing_mimes)) {
            asa_log('SVG MIME type already enabled by WordPress, theme, or another plugin', 'info');
            // SVG is already supported - check if we should override or coexist
            if (isset($options['force_svg_support']) && $options['force_svg_support']) {
                asa_log('Force SVG support enabled - will add ASA hooks anyway', 'info');
                return true;
            }
            return false;
        }

        // Check if another popular SVG plugin is active
        $conflicting_plugins = array(
            'safe-svg/safe-svg.php' => 'Safe SVG',
            'svg-support/svg-support.php' => 'SVG Support', 
            'enable-svg-uploads/enable-svg-uploads.php' => 'Enable SVG Uploads',
            'wp-svg-icons/wp-svg-icons.php' => 'WP SVG Icons',
            'svg-upload-and-sanitizer/svg-upload-and-sanitizer.php' => 'SVG Upload and Sanitizer',
            'easy-svg/easy-svg.php' => 'Easy SVG',
            'wp-svg-upload/wp-svg-upload.php' => 'WP SVG Upload',
            'simple-svg-upload/simple-svg-upload.php' => 'Simple SVG Upload'
        );

        $active_conflicts = array();
        foreach ($conflicting_plugins as $plugin => $name) {
            if (is_plugin_active($plugin)) {
                $active_conflicts[] = $name;
                asa_log("SVG plugin conflict detected: {$name} ({$plugin})", 'warning');
            }
        }

        if (!empty($active_conflicts)) {
            // Store conflicting plugins for admin notice
            update_option('asa_detected_svg_conflicts', $active_conflicts);
            return false;
        } else {
            // Clear any previous conflicts
            delete_option('asa_detected_svg_conflicts');
        }

        // Check if theme has SVG support through upload_mimes filter
        $theme_mimes = apply_filters('upload_mimes', array());
        if (isset($theme_mimes['svg']) || in_array('image/svg+xml', $theme_mimes)) {
            asa_log('Theme or plugin has added SVG support via upload_mimes filter', 'info');
            return false;
        }

        // Check if theme declares SVG support
        if (current_theme_supports('svg')) {
            asa_log('Theme declares SVG support', 'info');
            return false;
        }

        // All checks passed - safe to enable SVG support
        asa_log('No SVG conflicts detected - enabling ASA SVG support', 'info');
        return true;
    }

    /**
     * Get sanitization level
     */
    private function get_sanitization_level() {
        return get_option('asa_sanitization_level', 'advanced');
    }

    /**
     * Get sanitization configuration
     */
    private function get_sanitization_config($level = 'advanced') {
        $base_config = array(
            'allowed_elements' => array(
                'svg', 'g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon',
                'text', 'tspan', 'defs', 'clipPath', 'mask', 'pattern', 'image', 'switch',
                'foreignObject', 'marker', 'symbol', 'use', 'view'
            ),
            'allowed_attributes' => array(
                'id', 'class', 'style', 'transform', 'fill', 'stroke', 'stroke-width',
                'stroke-linecap', 'stroke-linejoin', 'stroke-dasharray', 'stroke-dashoffset',
                'fill-opacity', 'stroke-opacity', 'opacity', 'visibility', 'display',
                'x', 'y', 'x1', 'y1', 'x2', 'y2', 'cx', 'cy', 'r', 'rx', 'ry',
                'width', 'height', 'viewBox', 'preserveAspectRatio', 'xmlns',
                'd', 'points', 'dx', 'dy', 'font-family', 'font-size', 'font-weight',
                'text-anchor', 'alignment-baseline'
            )
        );

        if ($level === 'animation') {
            // Add animation-related elements and attributes
            $animation_elements = array(
                'animate', 'animateColor', 'animateMotion', 'animateTransform',
                'set', 'mpath', 'feGaussianBlur', 'filter'
            );
            $animation_attributes = array(
                'attributeName', 'begin', 'dur', 'values', 'from', 'to', 'repeatCount', 'type',
                'calcMode', 'keyTimes', 'keySplines', 'additive', 'accumulate', 'by', 'end',
                'min', 'max', 'restart', 'repeatDur', 'fill-rule', 'clip-rule', 'transform-origin'
            );

            $base_config['allowed_elements'] = array_merge($base_config['allowed_elements'], $animation_elements);
            $base_config['allowed_attributes'] = array_merge($base_config['allowed_attributes'], $animation_attributes);
        }

        return $base_config;
    }

    /**
     * Sanitize SVG file (legacy method)
     */
    private function sanitize_svg_file($file_path) {
        if (!file_exists($file_path)) {
            return false;
        }

        $content = file_get_contents($file_path);
        if (empty($content)) {
            return false;
        }

        $sanitized = $this->sanitize_svg_content_with_library($content);
        if ($sanitized === false) {
            return false;
        }

        return file_put_contents($file_path, $sanitized) !== false;
    }

    /**
     * Render callback for the simple SVG animator block
     */
    public function render_simple_svg_block($attributes, $content) {
        $svg_id = isset($attributes['svgId']) ? intval($attributes['svgId']) : 0;
        $svg_url = isset($attributes['svgUrl']) ? esc_url($attributes['svgUrl']) : '';
        $animation_type = isset($attributes['animationType']) ? sanitize_text_field($attributes['animationType']) : 'none';
        $duration = isset($attributes['duration']) ? intval($attributes['duration']) : 1000;

        if (!$svg_id || !$svg_url) {
            return '';
        }

        // Get SVG content
        $svg_content = '';
        $attachment = get_post($svg_id);
        if ($attachment && $attachment->post_mime_type === 'image/svg+xml') {
            $svg_file = get_attached_file($svg_id);
            if ($svg_file && file_exists($svg_file)) {
                $svg_content = file_get_contents($svg_file);
                // Basic sanitization
                $svg_content = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $svg_content);
                $svg_content = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $svg_content);
            }
        }

        if (empty($svg_content)) {
            $svg_content = '<img src="' . esc_url($svg_url) . '" alt="SVG Image" />';
        }

        // Log SVG animation block usage to Simple History
        if (class_exists('ASA_Simple_History_Logger')) {
            $post_id = get_the_ID();
            if ($post_id && $animation_type !== 'none') {
                ASA_Simple_History_Logger::log_animation_block_usage(
                    $post_id,
                    $animation_type,
                    array(
                        'svg_id' => $svg_id,
                        'duration' => $duration,
                        'svg_url' => $svg_url
                    )
                );
            }
        }

        $classes = array('asa-svg-animator');
        if ($animation_type !== 'none') {
            $classes[] = 'asa-animate-' . $animation_type;
        }

        $style_attr = '';
        if ($animation_type !== 'none') {
            $style_attr = ' style="--asa-duration: ' . $duration . 'ms"';
        }

        return '<div class="' . esc_attr(implode(' ', $classes)) . '"' . $style_attr . '>' . $svg_content . '</div>';
    }

    /**
     * Display WordPress version notice
     */
    public function wp_version_notice() {
        echo '<div class="notice notice-error"><p>';
        echo sprintf(__('Advanced SVG Animator requires WordPress 5.0 or higher. You are running version %s.', ASA_TEXT_DOMAIN), get_bloginfo('version'));
        echo '</p></div>';
    }

    /**
     * Display PHP version notice
     */
    public function php_version_notice() {
        echo '<div class="notice notice-error"><p>';
        echo sprintf(__('Advanced SVG Animator requires PHP 7.4 or higher. You are running version %s.', ASA_TEXT_DOMAIN), PHP_VERSION);
        echo '</p></div>';
    }
}

/**
 * Initialize the plugin when WordPress is ready
 */
function asa_init_plugin() {
        return ASA_Plugin::get_instance();
    }

    /**
     * Plugin activation hook
     */
    function asa_activate() {
        ASA_Plugin::get_instance()->activate_plugin();
    }

    /**
     * Plugin deactivation hook
     */
    function asa_deactivate() {
        ASA_Plugin::get_instance()->deactivate_plugin();
    }

    /**
     * Plugin uninstallation hook
     */
    function asa_uninstall() {
        ASA_Plugin::get_instance()->uninstall_plugin();
    }

// Initialize plugin on WordPress init
add_action('init', 'asa_init_plugin');

// Register activation, deactivation, and uninstall hooks
register_activation_hook(__FILE__, 'asa_activate');
register_deactivation_hook(__FILE__, 'asa_deactivate');
register_uninstall_hook(__FILE__, 'asa_uninstall');

