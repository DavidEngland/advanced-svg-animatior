<?php
/**
 * Quick Admin Access Test
 * 
 * Simple test to check if you can access the SVG Animator settings
 * Access via: yoursite.com/wp-content/plugins/advanced-svg-animator/admin-access-test.php
 */

// Try to load WordPress
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
    die('Could not load WordPress');
}

// Only show to logged in users
if (!is_user_logged_in()) {
    die('Please log in first');
}

$user = wp_get_current_user();
?>
<!DOCTYPE html>
<html>
<head>
    <title>SVG Animator Admin Access Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .pass { color: green; font-weight: bold; }
        .fail { color: red; font-weight: bold; }
        .info { background: #f0f8ff; padding: 10px; border-left: 4px solid #0073aa; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>SVG Animator Admin Access Test</h1>
    
    <div class="info">
        <strong>Current User:</strong> <?php echo $user->user_login; ?> (ID: <?php echo $user->ID; ?>)<br>
        <strong>Roles:</strong> <?php echo implode(', ', $user->roles ?? ['none']); ?>
    </div>
    
    <h2>Capability Tests:</h2>
    <p>manage_options: <span class="<?php echo current_user_can('manage_options') ? 'pass' : 'fail'; ?>"><?php echo current_user_can('manage_options') ? 'PASS' : 'FAIL'; ?></span></p>
    <p>activate_plugins: <span class="<?php echo current_user_can('activate_plugins') ? 'pass' : 'fail'; ?>"><?php echo current_user_can('activate_plugins') ? 'PASS' : 'FAIL'; ?></span></p>
    
    <h2>Direct Access Links:</h2>
    <p><a href="<?php echo admin_url('options-general.php?page=asa-settings'); ?>">Settings Page (Submenu)</a></p>
    <p><a href="<?php echo admin_url('admin.php?page=asa-settings-fallback'); ?>">Settings Page (Fallback)</a></p>
    <p><a href="<?php echo admin_url(); ?>">WordPress Admin Dashboard</a></p>
    
    <h2>Plugin Status:</h2>
    <p>Plugin Active: <span class="<?php echo is_plugin_active('advanced-svg-animator/advanced-svg-animator.php') ? 'pass' : 'fail'; ?>"><?php echo is_plugin_active('advanced-svg-animator/advanced-svg-animator.php') ? 'YES' : 'NO'; ?></span></p>
    
    <?php
    // Try to manually instantiate admin settings
    if (class_exists('ASA_Admin_Settings')) {
        $admin_settings = new ASA_Admin_Settings();
        if (method_exists($admin_settings, 'current_user_can_manage_plugin')) {
            $can_manage = $admin_settings->current_user_can_manage_plugin();
            echo '<p>Custom Permission Check: <span class="' . ($can_manage ? 'pass' : 'fail') . '">' . ($can_manage ? 'PASS' : 'FAIL') . '</span></p>';
        }
    } else {
        echo '<p>ASA_Admin_Settings Class: <span class="fail">NOT LOADED</span></p>';
    }
    ?>
    
    <div class="info">
        <strong>Next Steps:</strong><br>
        1. Try clicking the direct access links above<br>
        2. If they don't work, check WordPress debug log for errors<br>
        3. Try deactivating/reactivating the plugin
    </div>

</body>
</html>
