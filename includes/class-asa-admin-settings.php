<?php
/**
 * Admin Settings Page for Advanced SVG Animator
 * 
 * @package AdvancedSVGAnimator
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ASA_Admin_Settings
 * 
 * Handles the admin settings page and WordPress Settings API integration
 */
class ASA_Admin_Settings {

    /**
     * Settings page slug
     */
    const PAGE_SLUG = 'asa-settings';

    /**
     * Settings group name
     */
    const SETTINGS_GROUP = 'asa_settings_group';

    /**
     * Settings option name
     */
    const OPTION_NAME = 'asa_plugin_options';

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Add settings page to WordPress admin menu
     */
    public function add_settings_page() {
        // Debug: Check current user capabilities
        if (ASA_DEBUG) {
            error_log('ASA Debug: Current user can manage_options: ' . (current_user_can('manage_options') ? 'YES' : 'NO'));
            error_log('ASA Debug: Current user ID: ' . get_current_user_id());
            error_log('ASA Debug: Current user roles: ' . implode(', ', wp_get_current_user()->roles ?? []));
        }
        
        // Use more flexible capability check for better compatibility
        $capability = 'manage_options';
        
        $page_hook = add_options_page(
            __('Advanced SVG Animator Settings', ASA_TEXT_DOMAIN),
            __('SVG Animator', ASA_TEXT_DOMAIN),
            $capability,
            self::PAGE_SLUG,
            array($this, 'render_settings_page')
        );
        
        // Debug: Check if page was added successfully
        if (ASA_DEBUG) {
            error_log('ASA Debug: Settings page hook: ' . ($page_hook ? $page_hook : 'FAILED'));
        }
        
        // If standard method failed, try alternative method
        if (!$page_hook && $this->current_user_can_manage_plugin()) {
            // Add as a top-level menu as fallback
            $page_hook = add_menu_page(
                __('SVG Animator Settings', ASA_TEXT_DOMAIN),
                __('SVG Animator', ASA_TEXT_DOMAIN),
                'read', // Lower capability requirement
                self::PAGE_SLUG,
                array($this, 'render_settings_page'),
                'dashicons-art',
                30
            );
            
            if (ASA_DEBUG) {
                error_log('ASA Debug: Fallback menu page hook: ' . ($page_hook ? $page_hook : 'ALSO FAILED'));
            }
        }
    }

    /**
     * Register settings using WordPress Settings API
     */
    public function register_settings() {
        // Register the settings
        register_setting(
            self::SETTINGS_GROUP,
            self::OPTION_NAME,
            array($this, 'sanitize_settings')
        );

        // Add settings sections
        add_settings_section(
            'asa_svg_upload_section',
            __('SVG Upload Permissions', ASA_TEXT_DOMAIN),
            array($this, 'svg_upload_section_callback'),
            self::PAGE_SLUG
        );

        add_settings_section(
            'asa_svg_support_section',
            __('SVG Support Settings', ASA_TEXT_DOMAIN),
            array($this, 'svg_support_section_callback'),
            self::PAGE_SLUG
        );

        add_settings_section(
            'asa_security_section',
            __('Security Settings', ASA_TEXT_DOMAIN),
            array($this, 'security_section_callback'),
            self::PAGE_SLUG
        );

        // Add settings fields
        add_settings_field(
            'allowed_roles',
            __('Allowed User Roles', ASA_TEXT_DOMAIN),
            array($this, 'allowed_roles_callback'),
            self::PAGE_SLUG,
            'asa_svg_upload_section'
        );

        add_settings_field(
            'enable_svg_support',
            __('Enable SVG Upload Support', ASA_TEXT_DOMAIN),
            array($this, 'enable_svg_support_callback'),
            self::PAGE_SLUG,
            'asa_svg_support_section'
        );

        add_settings_field(
            'force_svg_support',
            __('Force SVG Support', ASA_TEXT_DOMAIN),
            array($this, 'force_svg_support_callback'),
            self::PAGE_SLUG,
            'asa_svg_support_section'
        );

        add_settings_field(
            'enable_svg_sanitization',
            __('Enable SVG Sanitization', ASA_TEXT_DOMAIN),
            array($this, 'enable_sanitization_callback'),
            self::PAGE_SLUG,
            'asa_security_section'
        );

        add_settings_field(
            'sanitization_level',
            __('Sanitization Level', ASA_TEXT_DOMAIN),
            array($this, 'sanitization_level_callback'),
            self::PAGE_SLUG,
            'asa_security_section'
        );

        add_settings_field(
            'debug_logging',
            __('Debug Logging', ASA_TEXT_DOMAIN),
            array($this, 'debug_logging_callback'),
            self::PAGE_SLUG,
            'asa_security_section'
        );
    }

    /**
     * Sanitize settings before saving
     * 
     * @param array $input Raw input data
     * @return array Sanitized data
     */
    public function sanitize_settings($input) {
        $sanitized = array();

        // Sanitize allowed roles
        if (isset($input['allowed_roles']) && is_array($input['allowed_roles'])) {
            $valid_roles = $this->get_available_roles();
            $sanitized['allowed_roles'] = array();
            
            foreach ($input['allowed_roles'] as $role) {
                if (array_key_exists($role, $valid_roles)) {
                    $sanitized['allowed_roles'][] = sanitize_text_field($role);
                }
            }
            
            // Ensure administrator is always included
            if (!in_array('administrator', $sanitized['allowed_roles'])) {
                $sanitized['allowed_roles'][] = 'administrator';
            }
        } else {
            // Default to administrator only
            $sanitized['allowed_roles'] = array('administrator');
        }

        // Sanitize boolean settings
        $sanitized['enable_svg_support'] = isset($input['enable_svg_support']) ? 1 : 0;
        $sanitized['force_svg_support'] = isset($input['force_svg_support']) ? 1 : 0;
        $sanitized['enable_svg_sanitization'] = isset($input['enable_svg_sanitization']) ? 1 : 0;
        $sanitized['debug_logging'] = isset($input['debug_logging']) ? 1 : 0;

        // Sanitize sanitization level
        $valid_levels = array('strict', 'basic', 'advanced');
        $sanitized['sanitization_level'] = in_array($input['sanitization_level'], $valid_levels) 
            ? sanitize_text_field($input['sanitization_level']) 
            : 'advanced';

        // Add version and timestamp
        $sanitized['version'] = ASA_VERSION;
        $sanitized['last_updated'] = current_time('timestamp');

        return $sanitized;
    }

    /**
     * Render the main settings page
     */
    public function render_settings_page() {
        // Check user capabilities with detailed error message
        if (!$this->current_user_can_manage_plugin()) {
            $user = wp_get_current_user();
            $error_message = sprintf(
                __('Access Denied: You do not have sufficient permissions to access this page. Current user: %s (ID: %d), Roles: %s. Required capability: manage_options', ASA_TEXT_DOMAIN),
                $user->user_login,
                $user->ID,
                implode(', ', $user->roles ?? ['none'])
            );
            
            if (ASA_DEBUG) {
                error_log('ASA Debug: Settings page access denied - ' . $error_message);
            }
            
            wp_die($error_message);
        }

        $options = $this->get_plugin_options();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php settings_errors(); ?>
            
            <div class="asa-settings-container">
                <div class="asa-settings-main">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields(self::SETTINGS_GROUP);
                        do_settings_sections(self::PAGE_SLUG);
                        submit_button();
                        ?>
                    </form>
                </div>
                
                <div class="asa-settings-sidebar">
                    <?php $this->render_info_boxes(); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * SVG Upload section callback
     */
    public function svg_upload_section_callback() {
        echo '<p>' . esc_html__('Configure which user roles are allowed to upload SVG files. For security reasons, it is recommended to limit this to trusted users only.', ASA_TEXT_DOMAIN) . '</p>';
    }

    /**
     * Security section callback
     */
    public function security_section_callback() {
        echo '<p>' . esc_html__('Configure security settings for SVG file handling and processing.', ASA_TEXT_DOMAIN) . '</p>';
    }

    /**
     * SVG Support section callback
     */
    public function svg_support_section_callback() {
        echo '<p>' . esc_html__('Configure SVG upload support. The plugin will automatically detect conflicts with other SVG plugins and themes.', ASA_TEXT_DOMAIN) . '</p>';
        
        // Show conflict detection status
        $conflicts = get_option('asa_detected_svg_conflicts', array());
        if (!empty($conflicts)) {
            echo '<div class="notice notice-warning inline">';
            echo '<p><strong>' . esc_html__('SVG Plugin Conflicts Detected:', ASA_TEXT_DOMAIN) . '</strong></p>';
            echo '<ul>';
            foreach ($conflicts as $plugin_name) {
                echo '<li>' . esc_html($plugin_name) . '</li>';
            }
            echo '</ul>';
            echo '<p>' . esc_html__('SVG upload support has been automatically disabled to prevent conflicts.', ASA_TEXT_DOMAIN) . '</p>';
            echo '</div>';
        }

        // Show current SVG support status
        $existing_mimes = get_allowed_mime_types();
        $svg_supported = isset($existing_mimes['svg']) || in_array('image/svg+xml', $existing_mimes);
        
        if ($svg_supported) {
            echo '<div class="notice notice-info inline">';
            echo '<p><strong>' . esc_html__('Current Status:', ASA_TEXT_DOMAIN) . '</strong> ';
            echo esc_html__('SVG uploads are already enabled by WordPress, your theme, or another plugin.', ASA_TEXT_DOMAIN) . '</p>';
            echo '</div>';
        }
    }

    /**
     * Allowed roles field callback
     */
    public function allowed_roles_callback() {
        $options = $this->get_plugin_options();
        $allowed_roles = isset($options['allowed_roles']) ? $options['allowed_roles'] : array('administrator');
        $available_roles = $this->get_available_roles();

        echo '<fieldset>';
        echo '<legend class="screen-reader-text">' . esc_html__('Select user roles that can upload SVG files', ASA_TEXT_DOMAIN) . '</legend>';
        
        foreach ($available_roles as $role_key => $role_name) {
            $checked = in_array($role_key, $allowed_roles) ? 'checked="checked"' : '';
            $disabled = ($role_key === 'administrator') ? 'disabled="disabled"' : '';
            
            printf(
                '<label><input type="checkbox" name="%s[allowed_roles][]" value="%s" %s %s /> %s</label><br>',
                esc_attr(self::OPTION_NAME),
                esc_attr($role_key),
                $checked,
                $disabled,
                esc_html($role_name)
            );
        }
        
        // Hidden field to ensure administrator is always selected
        printf(
            '<input type="hidden" name="%s[allowed_roles][]" value="administrator" />',
            esc_attr(self::OPTION_NAME)
        );
        
        echo '<p class="description">' . esc_html__('Administrator role is always allowed and cannot be disabled for security reasons.', ASA_TEXT_DOMAIN) . '</p>';
        echo '</fieldset>';
    }

    /**
     * Enable sanitization field callback
     */
    public function enable_sanitization_callback() {
        $options = $this->get_plugin_options();
        $enabled = isset($options['enable_svg_sanitization']) ? $options['enable_svg_sanitization'] : 1;

        printf(
            '<label><input type="checkbox" name="%s[enable_svg_sanitization]" value="1" %s /> %s</label>',
            esc_attr(self::OPTION_NAME),
            checked(1, $enabled, false),
            esc_html__('Enable automatic SVG sanitization on upload', ASA_TEXT_DOMAIN)
        );

        echo '<div class="asa-warning-box" style="margin-top: 10px; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">';
        echo '<strong>' . esc_html__('Security Warning:', ASA_TEXT_DOMAIN) . '</strong> ';
        echo esc_html__('Disabling SVG sanitization poses significant security risks. SVG files can contain malicious JavaScript code that could compromise your website. Only disable this if you fully understand the security implications and have alternative security measures in place.', ASA_TEXT_DOMAIN);
        echo '</div>';
    }

    /**
     * Sanitization level field callback
     */
    public function sanitization_level_callback() {
        $options = $this->get_plugin_options();
        $level = isset($options['sanitization_level']) ? $options['sanitization_level'] : 'advanced';

        $levels = array(
            'strict' => __('Strict - Maximum security, limited features', ASA_TEXT_DOMAIN),
            'basic' => __('Basic - Good security with essential animation support', ASA_TEXT_DOMAIN),
            'advanced' => __('Advanced - Balanced security with full animation support', ASA_TEXT_DOMAIN)
        );

        echo '<select name="' . esc_attr(self::OPTION_NAME) . '[sanitization_level]" id="sanitization_level">';
        foreach ($levels as $value => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($value),
                selected($level, $value, false),
                esc_html($label)
            );
        }
        echo '</select>';

        echo '<p class="description">' . esc_html__('Choose the level of SVG sanitization. Higher security levels may remove some animation features.', ASA_TEXT_DOMAIN) . '</p>';
    }

    /**
     * Debug logging field callback
     */
    public function debug_logging_callback() {
        $options = $this->get_plugin_options();
        $enabled = isset($options['debug_logging']) ? $options['debug_logging'] : 0;

        printf(
            '<label><input type="checkbox" name="%s[debug_logging]" value="1" %s /> %s</label>',
            esc_attr(self::OPTION_NAME),
            checked(1, $enabled, false),
            esc_html__('Enable debug logging for SVG processing', ASA_TEXT_DOMAIN)
        );

        echo '<p class="description">' . esc_html__('When enabled, detailed logs will be written to help troubleshoot SVG upload and sanitization issues.', ASA_TEXT_DOMAIN) . '</p>';
    }

    /**
     * Render information boxes in sidebar
     */
    private function render_info_boxes() {
        ?>
        <div class="asa-info-box">
            <h3><?php esc_html_e('Security Information', ASA_TEXT_DOMAIN); ?></h3>
            <p><?php esc_html_e('SVG files can contain executable code that poses security risks. This plugin automatically sanitizes uploaded SVG files to remove potentially dangerous content while preserving animation capabilities.', ASA_TEXT_DOMAIN); ?></p>
            <p><strong><?php esc_html_e('Recommendation:', ASA_TEXT_DOMAIN); ?></strong> <?php esc_html_e('Only allow trusted users to upload SVG files and always keep sanitization enabled.', ASA_TEXT_DOMAIN); ?></p>
        </div>

        <div class="asa-info-box">
            <h3><?php esc_html_e('Supported Features', ASA_TEXT_DOMAIN); ?></h3>
            <ul>
                <li><?php esc_html_e('SMIL Animations (animate, animateTransform)', ASA_TEXT_DOMAIN); ?></li>
                <li><?php esc_html_e('CSS Animations and Transitions', ASA_TEXT_DOMAIN); ?></li>
                <li><?php esc_html_e('Gradients and Patterns', ASA_TEXT_DOMAIN); ?></li>
                <li><?php esc_html_e('Path Animations', ASA_TEXT_DOMAIN); ?></li>
                <li><?php esc_html_e('Transform Animations', ASA_TEXT_DOMAIN); ?></li>
            </ul>
        </div>

        <div class="asa-info-box">
            <h3><?php esc_html_e('Plugin Status', ASA_TEXT_DOMAIN); ?></h3>
            <?php $this->render_status_info(); ?>
        </div>
        <?php
    }

    /**
     * Render plugin status information
     */
    private function render_status_info() {
        $sanitizer_available = class_exists('enshrined\svgSanitize\Sanitizer');
        $composer_available = file_exists(ASA_PLUGIN_DIR . 'vendor/autoload.php');
        
        echo '<ul>';
        
        // SVG Sanitizer Library Status
        printf(
            '<li><span class="asa-status %s"></span> %s: %s</li>',
            $sanitizer_available ? 'enabled' : 'disabled',
            esc_html__('SVG Sanitizer Library', ASA_TEXT_DOMAIN),
            $sanitizer_available ? esc_html__('Available', ASA_TEXT_DOMAIN) : esc_html__('Not Available', ASA_TEXT_DOMAIN)
        );
        
        // Composer Status
        printf(
            '<li><span class="asa-status %s"></span> %s: %s</li>',
            $composer_available ? 'enabled' : 'disabled',
            esc_html__('Composer Dependencies', ASA_TEXT_DOMAIN),
            $composer_available ? esc_html__('Installed', ASA_TEXT_DOMAIN) : esc_html__('Not Installed', ASA_TEXT_DOMAIN)
        );

        // PHP Version
        $php_version_ok = version_compare(PHP_VERSION, '7.4', '>=');
        printf(
            '<li><span class="asa-status %s"></span> %s: %s</li>',
            $php_version_ok ? 'enabled' : 'disabled',
            esc_html__('PHP Version', ASA_TEXT_DOMAIN),
            PHP_VERSION
        );

        // WordPress Version
        $wp_version_ok = version_compare(get_bloginfo('version'), '5.0', '>=');
        printf(
            '<li><span class="asa-status %s"></span> %s: %s</li>',
            $wp_version_ok ? 'enabled' : 'disabled',
            esc_html__('WordPress Version', ASA_TEXT_DOMAIN),
            get_bloginfo('version')
        );

        echo '</ul>';
    }

    /**
     * Enqueue admin assets
     * 
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our settings page
        if ('settings_page_' . self::PAGE_SLUG !== $hook) {
            return;
        }

        // Add inline CSS for the settings page
        wp_add_inline_style('wp-admin', $this->get_admin_css());
    }

    /**
     * Get admin CSS styles
     * 
     * @return string CSS styles
     */
    private function get_admin_css() {
        return '
            .asa-settings-container {
                display: flex;
                gap: 20px;
                margin-top: 20px;
            }
            
            .asa-settings-main {
                flex: 2;
            }
            
            .asa-settings-sidebar {
                flex: 1;
                max-width: 300px;
            }
            
            .asa-info-box {
                background: #fff;
                border: 1px solid #ccd0d4;
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 4px;
            }
            
            .asa-info-box h3 {
                margin-top: 0;
                margin-bottom: 10px;
                font-size: 14px;
                font-weight: 600;
            }
            
            .asa-info-box ul {
                margin: 0;
                padding-left: 20px;
            }
            
            .asa-info-box li {
                margin-bottom: 5px;
            }
            
            .asa-warning-box {
                margin-top: 10px;
                padding: 10px;
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                border-radius: 4px;
            }
            
            .asa-status {
                display: inline-block;
                width: 8px;
                height: 8px;
                border-radius: 50%;
                margin-right: 8px;
            }
            
            .asa-status.enabled {
                background-color: #46b450;
            }
            
            .asa-status.disabled {
                background-color: #dc3232;
            }
            
            @media (max-width: 782px) {
                .asa-settings-container {
                    flex-direction: column;
                }
                
                .asa-settings-sidebar {
                    max-width: none;
                }
            }
        ';
    }

    /**
     * Get available WordPress user roles
     * 
     * @return array Array of role_key => role_name
     */
    private function get_available_roles() {
        global $wp_roles;
        
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        
        return $wp_roles->get_names();
    }

    /**
     * Get plugin options with defaults
     * 
     * @return array Plugin options
     */
    public function get_plugin_options() {
        $defaults = array(
            'allowed_roles' => array('administrator'),
            'enable_svg_sanitization' => 1,
            'sanitization_level' => 'advanced',
            'debug_logging' => 0,
            'version' => ASA_VERSION,
        );

        $options = get_option(self::OPTION_NAME, $defaults);
        
        // Ensure defaults are merged for any missing keys
        return wp_parse_args($options, $defaults);
    }

    /**
     * Check if a user role is allowed to upload SVGs
     * 
     * @param string $role User role to check
     * @return bool True if role is allowed, false otherwise
     */
    public function is_role_allowed($role) {
        $options = $this->get_plugin_options();
        $allowed_roles = isset($options['allowed_roles']) ? $options['allowed_roles'] : array('administrator');
        
        return in_array($role, $allowed_roles);
    }

    /**
     * Check if current user is allowed to upload SVGs based on settings
     * 
     * @return bool True if user can upload SVGs, false otherwise
     */
    public function can_current_user_upload_svgs() {
        if (!is_user_logged_in()) {
            return false;
        }

        $user = wp_get_current_user();
        $user_roles = $user->roles;

        $options = $this->get_plugin_options();
        $allowed_roles = isset($options['allowed_roles']) ? $options['allowed_roles'] : array('administrator');

        // Check if user has any of the allowed roles
        foreach ($user_roles as $role) {
            if (in_array($role, $allowed_roles)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if current user has admin capabilities
     * Tries multiple capability checks for compatibility
     */
    public function current_user_can_manage_plugin() {
        // Try multiple capabilities in order of preference
        $capabilities = [
            'manage_options',           // Standard admin capability
            'activate_plugins',         // Plugin management capability
            'edit_plugins',            // Plugin editing capability
            'administrator'            // Direct role check (fallback)
        ];
        
        foreach ($capabilities as $capability) {
            if (current_user_can($capability)) {
                if (ASA_DEBUG) {
                    error_log("ASA Debug: User has capability: $capability");
                }
                return true;
            }
        }
        
        // Final fallback: check if user is in administrator role
        $user = wp_get_current_user();
        if (in_array('administrator', $user->roles ?? [])) {
            if (ASA_DEBUG) {
                error_log('ASA Debug: User has administrator role');
            }
            return true;
        }
        
        if (ASA_DEBUG) {
            error_log('ASA Debug: User does not have required capabilities');
        }
        return false;
    }
}
