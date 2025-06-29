<?php
/**
 * Advanced SVG Animator - Admin Permission Diagnostic Tool
 * 
 * This script helps diagnose admin permission and menu access issues.
 * Place this file in the plugin directory and access it via:
 * yoursite.com/wp-content/plugins/advanced-svg-animator/admin-permission-diagnostic.php
 * 
 * @package AdvancedSVGAnimator
 */

// Basic WordPress environment check
if (!defined('ABSPATH')) {
    // Try to load WordPress if not already loaded
    $wp_load_paths = [
        '../../../wp-load.php',
        '../../../../wp-load.php',
        '../../../../../wp-load.php'
    ];
    
    $wp_loaded = false;
    foreach ($wp_load_paths as $path) {
        if (file_exists(__DIR__ . '/' . $path)) {
            require_once __DIR__ . '/' . $path;
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        die('Error: Could not load WordPress. Please ensure this file is in the plugin directory.');
    }
}

// Ensure user is logged in and has appropriate permissions
if (!is_user_logged_in()) {
    wp_die('Please log in to WordPress admin first, then access this diagnostic tool.');
}

// Only allow administrators to run this diagnostic
if (!current_user_can('manage_options')) {
    wp_die('Access denied. This diagnostic tool requires administrator privileges.');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced SVG Animator - Admin Permission Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .diagnostic-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .diagnostic-section h2 { margin-top: 0; color: #333; }
        .success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
        .warning { background-color: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .test-result { padding: 5px 10px; margin: 5px 0; border-radius: 3px; }
        .pass { background-color: #d4edda; color: #155724; }
        .fail { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <h1>Advanced SVG Animator - Admin Permission Diagnostic</h1>
    
    <div class="diagnostic-section info">
        <h2>🔍 Running Diagnostic Tests</h2>
        <p>This tool will help identify why you might not be able to access the SVG Animator settings page.</p>
        <p><strong>Current Time:</strong> <?php echo current_time('mysql'); ?></p>
        <p><strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?></p>
    </div>

    <?php
    // Test 1: Current User Information
    $current_user = wp_get_current_user();
    ?>
    <div class="diagnostic-section">
        <h2>👤 Current User Information</h2>
        <p><strong>User ID:</strong> <?php echo $current_user->ID; ?></p>
        <p><strong>Username:</strong> <?php echo $current_user->user_login; ?></p>
        <p><strong>Email:</strong> <?php echo $current_user->user_email; ?></p>
        <p><strong>Roles:</strong> <?php echo implode(', ', $current_user->roles ?? ['none']); ?></p>
        <p><strong>Capabilities:</strong></p>
        <pre><?php 
        $caps = $current_user->allcaps ?? [];
        ksort($caps);
        foreach ($caps as $cap => $value) {
            if ($value) echo $cap . "\n";
        }
        ?></pre>
    </div>

    <?php
    // Test 2: Plugin Status
    ?>
    <div class="diagnostic-section">
        <h2>🔌 Plugin Status</h2>
        <?php
        $plugin_file = 'advanced-svg-animator/advanced-svg-animator.php';
        $is_active = is_plugin_active($plugin_file);
        $plugin_data = get_plugin_data(__DIR__ . '/advanced-svg-animator.php');
        ?>
        <div class="test-result <?php echo $is_active ? 'pass' : 'fail'; ?>">
            Plugin Active: <?php echo $is_active ? 'YES' : 'NO'; ?>
        </div>
        <?php if (!empty($plugin_data)): ?>
        <p><strong>Plugin Name:</strong> <?php echo $plugin_data['Name']; ?></p>
        <p><strong>Version:</strong> <?php echo $plugin_data['Version']; ?></p>
        <p><strong>Description:</strong> <?php echo $plugin_data['Description']; ?></p>
        <?php endif; ?>
    </div>

    <?php
    // Test 3: Capability Tests
    $capabilities_to_test = [
        'manage_options',
        'activate_plugins', 
        'edit_plugins',
        'install_plugins',
        'update_plugins',
        'administrator'
    ];
    ?>
    <div class="diagnostic-section">
        <h2>🔐 Capability Tests</h2>
        <?php foreach ($capabilities_to_test as $capability): ?>
        <div class="test-result <?php echo current_user_can($capability) ? 'pass' : 'fail'; ?>">
            <?php echo $capability; ?>: <?php echo current_user_can($capability) ? 'PASS' : 'FAIL'; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <?php
    // Test 4: Constants and Definitions
    ?>
    <div class="diagnostic-section">
        <h2>📋 Plugin Constants</h2>
        <?php
        $constants_to_check = [
            'ASA_DEBUG',
            'ASA_PLUGIN_VERSION', 
            'ASA_TEXT_DOMAIN',
            'ASA_PLUGIN_URL',
            'ASA_PLUGIN_PATH'
        ];
        
        foreach ($constants_to_check as $constant):
        ?>
        <div class="test-result <?php echo defined($constant) ? 'pass' : 'fail'; ?>">
            <?php echo $constant; ?>: <?php 
            if (defined($constant)) {
                echo 'DEFINED (' . constant($constant) . ')';
            } else {
                echo 'NOT DEFINED';
            }
            ?>
        </div>
        <?php endforeach; ?>
    </div>

    <?php
    // Test 5: Admin Menu Check
    global $menu, $submenu;
    ?>
    <div class="diagnostic-section">
        <h2>📋 Admin Menu Analysis</h2>
        <?php
        $svg_menu_found = false;
        $settings_submenu_found = false;
        
        // Check top-level menus
        if (isset($menu) && is_array($menu)) {
            foreach ($menu as $menu_item) {
                if (is_array($menu_item) && isset($menu_item[2])) {
                    if (strpos($menu_item[2], 'asa-settings') !== false || 
                        strpos($menu_item[0], 'SVG Animator') !== false) {
                        $svg_menu_found = true;
                        break;
                    }
                }
            }
        }
        
        // Check Settings submenus
        if (isset($submenu['options-general.php']) && is_array($submenu['options-general.php'])) {
            foreach ($submenu['options-general.php'] as $submenu_item) {
                if (is_array($submenu_item) && isset($submenu_item[2])) {
                    if (strpos($submenu_item[2], 'asa-settings') !== false ||
                        strpos($submenu_item[0], 'SVG Animator') !== false) {
                        $settings_submenu_found = true;
                        break;
                    }
                }
            }
        }
        ?>
        
        <div class="test-result <?php echo $svg_menu_found ? 'pass' : 'fail'; ?>">
            Top-level SVG Animator menu: <?php echo $svg_menu_found ? 'FOUND' : 'NOT FOUND'; ?>
        </div>
        
        <div class="test-result <?php echo $settings_submenu_found ? 'pass' : 'fail'; ?>">
            Settings submenu: <?php echo $settings_submenu_found ? 'FOUND' : 'NOT FOUND'; ?>
        </div>
        
        <?php if (!$svg_menu_found && !$settings_submenu_found): ?>
        <div class="error">
            <strong>Issue Detected:</strong> No SVG Animator menu items found in admin menu. This suggests the admin menu hook is not firing properly.
        </div>
        <?php endif; ?>
    </div>

    <?php
    // Test 6: Class and File Checks
    ?>
    <div class="diagnostic-section">
        <h2>📁 File and Class Checks</h2>
        <?php
        $files_to_check = [
            'Main Plugin File' => __DIR__ . '/advanced-svg-animator.php',
            'Admin Settings Class' => __DIR__ . '/includes/class-asa-admin-settings.php',
            'SVG Handler Class' => __DIR__ . '/includes/class-asa-svg-handler.php',
            'SVG Sanitizer Class' => __DIR__ . '/includes/class-asa-svg-sanitizer.php'
        ];
        
        foreach ($files_to_check as $name => $file_path):
        ?>
        <div class="test-result <?php echo file_exists($file_path) ? 'pass' : 'fail'; ?>">
            <?php echo $name; ?>: <?php echo file_exists($file_path) ? 'EXISTS' : 'MISSING'; ?>
        </div>
        <?php endforeach; ?>
        
        <?php
        $classes_to_check = [
            'ASA_Admin_Settings',
            'ASA_SVG_Handler', 
            'ASA_SVG_Sanitizer'
        ];
        
        foreach ($classes_to_check as $class_name):
        ?>
        <div class="test-result <?php echo class_exists($class_name) ? 'pass' : 'fail'; ?>">
            Class <?php echo $class_name; ?>: <?php echo class_exists($class_name) ? 'LOADED' : 'NOT LOADED'; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <?php
    // Test 7: WordPress Hooks and Actions
    ?>
    <div class="diagnostic-section">
        <h2>🎣 WordPress Hooks Analysis</h2>
        <?php
        global $wp_filter;
        
        $hooks_to_check = [
            'admin_menu',
            'admin_init', 
            'init',
            'plugins_loaded'
        ];
        
        foreach ($hooks_to_check as $hook):
            $callbacks = isset($wp_filter[$hook]) ? count($wp_filter[$hook]->callbacks ?? []) : 0;
        ?>
        <div class="test-result <?php echo $callbacks > 0 ? 'pass' : 'fail'; ?>">
            Hook "<?php echo $hook; ?>": <?php echo $callbacks; ?> callbacks registered
        </div>
        <?php endforeach; ?>
        
        <?php
        // Check if our admin_menu callback is registered
        $admin_menu_found = false;
        if (isset($wp_filter['admin_menu'])) {
            foreach ($wp_filter['admin_menu']->callbacks as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    $callback_info = '';
                    if (is_array($callback['function'])) {
                        if (is_object($callback['function'][0])) {
                            $callback_info = get_class($callback['function'][0]) . '::' . $callback['function'][1];
                        } else {
                            $callback_info = $callback['function'][0] . '::' . $callback['function'][1];
                        }
                    } else {
                        $callback_info = $callback['function'];
                    }
                    
                    if (strpos($callback_info, 'ASA_Admin_Settings') !== false || 
                        strpos($callback_info, 'add_settings_page') !== false) {
                        $admin_menu_found = true;
                        break 2;
                    }
                }
            }
        }
        ?>
        
        <div class="test-result <?php echo $admin_menu_found ? 'pass' : 'fail'; ?>">
            ASA admin_menu callback: <?php echo $admin_menu_found ? 'REGISTERED' : 'NOT REGISTERED'; ?>
        </div>
    </div>

    <?php
    // Test 8: Manual Admin Settings Test
    ?>
    <div class="diagnostic-section">
        <h2>🧪 Manual Admin Settings Test</h2>
        <?php
        try {
            if (class_exists('ASA_Admin_Settings')) {
                $admin_settings = new ASA_Admin_Settings();
                if (method_exists($admin_settings, 'current_user_can_manage_plugin')) {
                    $can_manage = $admin_settings->current_user_can_manage_plugin();
                    ?>
                    <div class="test-result <?php echo $can_manage ? 'pass' : 'fail'; ?>">
                        Manual permission check: <?php echo $can_manage ? 'PASS' : 'FAIL'; ?>
                    </div>
                    <?php
                } else {
                    ?>
                    <div class="test-result fail">
                        Method current_user_can_manage_plugin: NOT FOUND
                    </div>
                    <?php
                }
            } else {
                ?>
                <div class="test-result fail">
                    ASA_Admin_Settings class: NOT AVAILABLE for testing
                </div>
                <?php
            }
        } catch (Exception $e) {
            ?>
            <div class="test-result fail">
                Error testing admin settings: <?php echo esc_html($e->getMessage()); ?>
            </div>
            <?php
        }
        ?>
    </div>

    <?php
    // Test 9: Debug Information
    ?>
    <div class="diagnostic-section">
        <h2>🐛 Debug Information</h2>
        <p><strong>WordPress Debug Mode:</strong> <?php echo defined('WP_DEBUG') && WP_DEBUG ? 'ENABLED' : 'DISABLED'; ?></p>
        <p><strong>Plugin Debug Mode:</strong> <?php echo defined('ASA_DEBUG') && ASA_DEBUG ? 'ENABLED' : 'DISABLED'; ?></p>
        <p><strong>Error Log Location:</strong> <?php echo ini_get('log_errors') ? ini_get('error_log') : 'Not configured'; ?></p>
        
        <?php
        // Check for recent WordPress errors
        $debug_log_path = WP_CONTENT_DIR . '/debug.log';
        if (file_exists($debug_log_path) && is_readable($debug_log_path)):
        ?>
        <p><strong>WordPress Debug Log:</strong> Available at <?php echo $debug_log_path; ?></p>
        <p><strong>Last 10 lines of debug log:</strong></p>
        <pre style="max-height: 200px; overflow-y: auto;"><?php
        $log_lines = file($debug_log_path);
        $recent_lines = array_slice($log_lines, -10);
        echo esc_html(implode('', $recent_lines));
        ?></pre>
        <?php else: ?>
        <p><strong>WordPress Debug Log:</strong> Not available or not readable</p>
        <?php endif; ?>
    </div>

    <div class="diagnostic-section success">
        <h2>✅ Next Steps</h2>
        <ol>
            <li><strong>Review the test results above</strong> - Any "FAIL" results indicate potential issues.</li>
            <li><strong>Check WordPress admin menu</strong> - Look for "SVG Animator" under Settings or as a top-level menu.</li>
            <li><strong>Try accessing the settings directly:</strong> 
                <a href="<?php echo admin_url('options-general.php?page=asa-settings'); ?>" target="_blank">
                    <?php echo admin_url('options-general.php?page=asa-settings'); ?>
                </a>
            </li>
            <li><strong>If still having issues:</strong> Share the results of this diagnostic with your developer.</li>
        </ol>
    </div>

    <div class="diagnostic-section info">
        <h2>🔧 Quick Fixes to Try</h2>
        <ul>
            <li><strong>Deactivate and reactivate the plugin</strong> - This can resolve initialization issues.</li>
            <li><strong>Clear any caching</strong> - Object cache or page cache might be interfering.</li>
            <li><strong>Check for plugin conflicts</strong> - Temporarily deactivate other plugins to test.</li>
            <li><strong>Switch to a default theme</strong> - Theme conflicts can affect admin functionality.</li>
        </ul>
    </div>

    <div style="margin-top: 40px; padding: 15px; background: #f0f0f0; border-radius: 5px;">
        <p><small><strong>Diagnostic completed at:</strong> <?php echo current_time('mysql'); ?></small></p>
        <p><small>If you need further assistance, please share these diagnostic results with your developer or support team.</small></p>
    </div>

</body>
</html>
