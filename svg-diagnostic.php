<?php
/**
 * SVG Support Diagnostic Script for Advanced SVG Animator
 * 
 * Run this script on your staging/live site to diagnose SVG support conflicts
 * 
 * Usage: 
 * 1. Upload this file to your WordPress root directory or wp-content/plugins/advanced-svg-animator/
 * 2. Access it via browser: https://yoursite.com/svg-diagnostic.php
 * 3. Remove the file after diagnosis for security
 * 
 * @package AdvancedSVGAnimator
 */

// Basic security - only run if WordPress is loaded or if accessed directly with basic auth
if (!defined('ABSPATH')) {
    // If accessed directly, load WordPress
    $wp_load_paths = array(
        dirname(__FILE__) . '/../../../../wp-load.php',  // If in plugin directory
        dirname(__FILE__) . '/wp-load.php',              // If in WordPress root
        dirname(__FILE__) . '/../wp-load.php',           // If in subdirectory
    );
    
    $wp_loaded = false;
    foreach ($wp_load_paths as $wp_load_path) {
        if (file_exists($wp_load_path)) {
            require_once $wp_load_path;
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        die('WordPress not found. Please upload this file to your WordPress root directory or plugin directory.');
    }
}

// Check if user has admin privileges when running through WordPress
if (defined('ABSPATH') && !current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to run this diagnostic.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Advanced SVG Animator - SVG Support Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .diagnostic-section { background: #f9f9f9; padding: 15px; margin: 15px 0; border-left: 4px solid #0073aa; }
        .warning { border-left-color: #dc3232; background: #fff3f3; }
        .success { border-left-color: #46b450; background: #f3fff3; }
        .info { border-left-color: #ffb900; background: #fffbf0; }
        .status-good { color: #46b450; font-weight: bold; }
        .status-warning { color: #dc3232; font-weight: bold; }
        .status-info { color: #ffb900; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .code { background: #f0f0f0; padding: 2px 4px; font-family: monospace; }
    </style>
</head>
<body>
    <h1>Advanced SVG Animator - SVG Support Diagnostic</h1>
    <p><strong>Site:</strong> <?php echo esc_html(get_bloginfo('name')); ?> (<?php echo esc_html(get_bloginfo('url')); ?>)</p>
    <p><strong>Date:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>

    <?php
    // 1. Check current MIME types
    echo '<div class="diagnostic-section">';
    echo '<h2>Current MIME Type Support</h2>';
    
    $allowed_mimes = get_allowed_mime_types();
    $svg_supported = false;
    $svg_mime_entries = array();
    
    foreach ($allowed_mimes as $extension => $mime_type) {
        if (strpos($extension, 'svg') !== false || strpos($mime_type, 'svg') !== false) {
            $svg_supported = true;
            $svg_mime_entries[] = array('extension' => $extension, 'mime' => $mime_type);
        }
    }
    
    if ($svg_supported) {
        echo '<p class="status-good">✓ SVG MIME type support is ENABLED</p>';
        echo '<table>';
        echo '<tr><th>File Extension</th><th>MIME Type</th></tr>';
        foreach ($svg_mime_entries as $entry) {
            echo '<tr><td class="code">' . esc_html($entry['extension']) . '</td><td class="code">' . esc_html($entry['mime']) . '</td></tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="status-warning">✗ SVG MIME type support is DISABLED</p>';
        echo '<p>No SVG MIME types found in WordPress allowed mime types.</p>';
    }
    echo '</div>';

    // 2. Check active plugins
    echo '<div class="diagnostic-section">';
    echo '<h2>SVG-Related Plugins</h2>';
    
    $svg_related_plugins = array(
        'advanced-svg-animator/advanced-svg-animator.php' => 'Advanced SVG Animator',
        'safe-svg/safe-svg.php' => 'Safe SVG',
        'svg-support/svg-support.php' => 'SVG Support',
        'enable-svg-uploads/enable-svg-uploads.php' => 'Enable SVG Uploads',
        'wp-svg-icons/wp-svg-icons.php' => 'WP SVG Icons',
        'svg-upload-and-sanitizer/svg-upload-and-sanitizer.php' => 'SVG Upload and Sanitizer',
        'easy-svg/easy-svg.php' => 'Easy SVG',
        'wp-svg-upload/wp-svg-upload.php' => 'WP SVG Upload',
        'simple-svg-upload/simple-svg-upload.php' => 'Simple SVG Upload'
    );
    
    $active_svg_plugins = array();
    $all_active_plugins = get_option('active_plugins', array());
    
    foreach ($svg_related_plugins as $plugin_file => $plugin_name) {
        if (in_array($plugin_file, $all_active_plugins)) {
            $active_svg_plugins[] = $plugin_name;
        }
    }
    
    if (!empty($active_svg_plugins)) {
        echo '<p class="status-info">⚠ Active SVG-related plugins found:</p>';
        echo '<ul>';
        foreach ($active_svg_plugins as $plugin_name) {
            echo '<li><strong>' . esc_html($plugin_name) . '</strong></li>';
        }
        echo '</ul>';
        
        if (count($active_svg_plugins) > 1) {
            echo '<p class="status-warning">⚠ Multiple SVG plugins detected - this may cause conflicts!</p>';
        }
    } else {
        echo '<p class="status-warning">✗ No SVG-related plugins found.</p>';
    }
    echo '</div>';

    // 3. Check theme support
    echo '<div class="diagnostic-section">';
    echo '<h2>Theme SVG Support</h2>';
    
    $theme = wp_get_theme();
    echo '<p><strong>Active Theme:</strong> ' . esc_html($theme->get('Name')) . ' v' . esc_html($theme->get('Version')) . '</p>';
    
    if (current_theme_supports('svg')) {
        echo '<p class="status-good">✓ Theme declares SVG support</p>';
    } else {
        echo '<p class="status-info">⚠ Theme does not explicitly declare SVG support</p>';
    }
    
    // Test if theme adds SVG support via filters
    $base_mimes = array('jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png');
    $theme_mimes = apply_filters('upload_mimes', $base_mimes);
    $theme_adds_svg = false;
    
    foreach ($theme_mimes as $ext => $mime) {
        if (strpos($ext, 'svg') !== false || strpos($mime, 'svg') !== false) {
            $theme_adds_svg = true;
            break;
        }
    }
    
    if ($theme_adds_svg) {
        echo '<p class="status-info">⚠ Theme or plugin adds SVG support via upload_mimes filter</p>';
    }
    echo '</div>';

    // 4. Check WordPress configuration
    echo '<div class="diagnostic-section">';
    echo '<h2>WordPress Configuration</h2>';
    
    echo '<table>';
    echo '<tr><th>Setting</th><th>Value</th><th>Status</th></tr>';
    
    $wp_version = get_bloginfo('version');
    $wp_version_ok = version_compare($wp_version, '5.0', '>=');
    echo '<tr><td>WordPress Version</td><td>' . esc_html($wp_version) . '</td><td class="' . ($wp_version_ok ? 'status-good">✓ OK' : 'status-warning">✗ Outdated') . '</td></tr>';
    
    $php_version_ok = version_compare(PHP_VERSION, '7.4', '>=');
    echo '<tr><td>PHP Version</td><td>' . esc_html(PHP_VERSION) . '</td><td class="' . ($php_version_ok ? 'status-good">✓ OK' : 'status-warning">✗ Outdated') . '</td></tr>';
    
    $max_upload = wp_max_upload_size();
    echo '<tr><td>Max Upload Size</td><td>' . esc_html(size_format($max_upload)) . '</td><td class="status-info">ℹ Info</td></tr>';
    
    $multisite = is_multisite();
    echo '<tr><td>Multisite</td><td>' . ($multisite ? 'Yes' : 'No') . '</td><td class="status-info">ℹ Info</td></tr>';
    
    echo '</table>';
    echo '</div>';

    // 5. Advanced SVG Animator specific checks
    if (defined('ASA_VERSION')) {
        echo '<div class="diagnostic-section success">';
        echo '<h2>Advanced SVG Animator Status</h2>';
        
        echo '<table>';
        echo '<tr><th>Component</th><th>Status</th></tr>';
        
        echo '<tr><td>Plugin Version</td><td class="code">' . esc_html(ASA_VERSION) . '</td></tr>';
        
        // Check if SVG support should be enabled
        $asa_plugin = ASA_Plugin::get_instance();
        if (method_exists($asa_plugin, 'should_enable_svg_support')) {
            // Use reflection to access private method for diagnostic
            $reflection = new ReflectionClass($asa_plugin);
            $method = $reflection->getMethod('should_enable_svg_support');
            $method->setAccessible(true);
            $should_enable = $method->invoke($asa_plugin);
            
            echo '<tr><td>Should Enable SVG Support</td><td class="' . ($should_enable ? 'status-good">✓ Yes' : 'status-warning">✗ No') . '</td></tr>';
        }
        
        // Check plugin options
        $options = get_option('asa_plugin_options', array());
        $svg_support_enabled = isset($options['enable_svg_support']) ? $options['enable_svg_support'] : 1;
        echo '<tr><td>SVG Support Setting</td><td class="' . ($svg_support_enabled ? 'status-good">✓ Enabled' : 'status-warning">✗ Disabled') . '</td></tr>';
        
        $force_svg_support = isset($options['force_svg_support']) ? $options['force_svg_support'] : 0;
        echo '<tr><td>Force SVG Support</td><td class="' . ($force_svg_support ? 'status-info">⚠ Enabled' : 'status-good">✗ Disabled') . '</td></tr>';
        
        // Check for detected conflicts
        $conflicts = get_option('asa_detected_svg_conflicts', array());
        if (!empty($conflicts)) {
            echo '<tr><td>Detected Conflicts</td><td class="status-warning">✗ ' . implode(', ', array_map('esc_html', $conflicts)) . '</td></tr>';
        } else {
            echo '<tr><td>Detected Conflicts</td><td class="status-good">✓ None</td></tr>';
        }
        
        echo '</table>';
        echo '</div>';
    } else {
        echo '<div class="diagnostic-section warning">';
        echo '<h2>Advanced SVG Animator Status</h2>';
        echo '<p class="status-warning">✗ Advanced SVG Animator is not active or not properly loaded.</p>';
        echo '</div>';
    }

    // 6. Recommendations
    echo '<div class="diagnostic-section info">';
    echo '<h2>Recommendations</h2>';
    
    echo '<ul>';
    
    if (count($active_svg_plugins) > 1) {
        echo '<li><strong>Multiple SVG plugins detected:</strong> Consider deactivating conflicting plugins to avoid issues.</li>';
    }
    
    if ($svg_supported && !in_array('Advanced SVG Animator', $active_svg_plugins)) {
        echo '<li><strong>SVG support is enabled without ASA:</strong> Another plugin or theme is providing SVG support. ASA can still provide animation and security features.</li>';
    }
    
    if (!$svg_supported && empty($active_svg_plugins)) {
        echo '<li><strong>No SVG support found:</strong> Consider activating an SVG plugin like Advanced SVG Animator to enable SVG uploads.</li>';
    }
    
    if (!$wp_version_ok) {
        echo '<li><strong>WordPress version:</strong> Update to WordPress 5.0+ for better block editor support.</li>';
    }
    
    if (!$php_version_ok) {
        echo '<li><strong>PHP version:</strong> Update to PHP 7.4+ for better security and performance.</li>';
    }
    
    echo '</ul>';
    echo '</div>';

    // Security note
    echo '<div class="diagnostic-section warning">';
    echo '<h2>Security Notice</h2>';
    echo '<p><strong>Important:</strong> Delete this diagnostic file after use for security reasons. It should not remain on your live site.</p>';
    echo '<p>File location: <code>' . esc_html(__FILE__) . '</code></p>';
    echo '</div>';
    ?>

    <p><small>Generated by Advanced SVG Animator Diagnostic Tool</small></p>
</body>
</html>
