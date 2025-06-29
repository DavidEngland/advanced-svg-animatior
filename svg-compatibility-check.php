<?php
/**
 * SVG Plugin Compatibility Checker
 * 
 * Run this script to check for SVG plugin conflicts before installing
 * Advanced SVG Animator on your staging/production site.
 * 
 * Usage: Place this file in your WordPress root and visit it in browser
 * or run: php svg-compatibility-check.php
 */

// Load WordPress
if (file_exists('./wp-config.php')) {
    require_once('./wp-config.php');
    require_once('./wp-load.php');
} else {
    die('WordPress not found. Place this file in your WordPress root directory.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>SVG Plugin Compatibility Check</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .code { background: #f4f4f4; padding: 10px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <h1>SVG Plugin Compatibility Check</h1>
    <p>Checking for potential conflicts with Advanced SVG Animator plugin...</p>

    <?php
    // Define known SVG plugins that might conflict
    $svg_plugins = array(
        'safe-svg/safe-svg.php' => 'Safe SVG',
        'svg-support/svg-support.php' => 'SVG Support',
        'enable-svg-uploads/enable-svg-uploads.php' => 'Enable SVG Uploads',
        'wp-svg-icons/wp-svg-icons.php' => 'WP SVG Icons',
        'svg-upload-and-sanitizer/svg-upload-and-sanitizer.php' => 'SVG Upload and Sanitizer',
        'scalable-vector-graphics-svg/scalable-vector-graphics-svg.php' => 'Scalable Vector Graphics (SVG)',
        'wp-svg-images/wp-svg-images.php' => 'WP SVG Images',
        'svg-vector-icon-plugin/svg-vector-icon-plugin.php' => 'SVG Vector Icon Plugin'
    );

    $active_svg_plugins = array();
    $has_conflicts = false;

    // Check for active SVG plugins
    foreach ($svg_plugins as $plugin_file => $plugin_name) {
        if (is_plugin_active($plugin_file)) {
            $active_svg_plugins[] = array(
                'name' => $plugin_name,
                'file' => $plugin_file
            );
            $has_conflicts = true;
        }
    }

    // Check current upload mimes
    $current_mimes = get_allowed_mime_types();
    $svg_already_supported = isset($current_mimes['svg']) || array_search('image/svg+xml', $current_mimes);

    // Check if theme supports SVG
    $theme_svg_support = current_theme_supports('svg');
    $current_theme = wp_get_theme();

    // Display results
    if ($has_conflicts): ?>
        <div class="status warning">
            <h3>⚠️ Potential Conflicts Detected</h3>
            <p>The following SVG plugins are currently active and may conflict with Advanced SVG Animator:</p>
            <ul>
                <?php foreach ($active_svg_plugins as $plugin): ?>
                    <li><strong><?php echo esc_html($plugin['name']); ?></strong> (<?php echo esc_html($plugin['file']); ?>)</li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else: ?>
        <div class="status success">
            <h3>✅ No SVG Plugin Conflicts Detected</h3>
            <p>No known conflicting SVG plugins are currently active.</p>
        </div>
    <?php endif; ?>

    <!-- SVG Support Status -->
    <h2>Current SVG Support Status</h2>
    <table>
        <tr>
            <th>Check</th>
            <th>Status</th>
            <th>Details</th>
        </tr>
        <tr>
            <td>SVG MIME Type Support</td>
            <td><?php echo $svg_already_supported ? '<span style="color: green;">✅ Enabled</span>' : '<span style="color: red;">❌ Disabled</span>'; ?></td>
            <td><?php echo $svg_already_supported ? 'SVG uploads are currently supported' : 'SVG uploads are not supported'; ?></td>
        </tr>
        <tr>
            <td>Theme SVG Support</td>
            <td><?php echo $theme_svg_support ? '<span style="color: green;">✅ Yes</span>' : '<span style="color: gray;">➖ No</span>'; ?></td>
            <td>Current theme: <?php echo esc_html($current_theme->get('Name')); ?></td>
        </tr>
        <tr>
            <td>WordPress Version</td>
            <td><?php 
                $wp_version = get_bloginfo('version');
                echo version_compare($wp_version, '5.0', '>=') ? '<span style="color: green;">✅ Compatible</span>' : '<span style="color: red;">❌ Too Old</span>';
            ?></td>
            <td>Version <?php echo esc_html($wp_version); ?> (Requires 5.0+)</td>
        </tr>
        <tr>
            <td>PHP Version</td>
            <td><?php 
                echo version_compare(PHP_VERSION, '7.4', '>=') ? '<span style="color: green;">✅ Compatible</span>' : '<span style="color: red;">❌ Too Old</span>';
            ?></td>
            <td>Version <?php echo esc_html(PHP_VERSION); ?> (Requires 7.4+)</td>
        </tr>
    </table>

    <!-- Recommendations -->
    <h2>Recommendations</h2>
    <?php if ($has_conflicts): ?>
        <div class="status info">
            <h3>🔧 Conflict Resolution Options</h3>
            <p><strong>Option 1: Use Both Plugins (Recommended)</strong></p>
            <p>Advanced SVG Animator will automatically detect conflicts and disable its SVG upload functionality while keeping the animation and security features active. This is the safest approach.</p>
            
            <p><strong>Option 2: Replace Current Plugin</strong></p>
            <p>If you want to use Advanced SVG Animator's enhanced security features:</p>
            <ol>
                <li>Deactivate the current SVG plugin(s)</li>
                <li>Install Advanced SVG Animator</li>
                <li>Test SVG uploads and functionality</li>
            </ol>
        </div>
    <?php else: ?>
        <div class="status success">
            <h3>🚀 Ready for Installation</h3>
            <p>No conflicts detected. Advanced SVG Animator can be safely installed with full functionality enabled.</p>
        </div>
    <?php endif; ?>

    <!-- Installation Instructions -->
    <h2>Installation Instructions</h2>
    <div class="status info">
        <h3>For Staging Site Testing:</h3>
        <ol>
            <li>Download the latest version from GitHub</li>
            <li>Upload to <code>wp-content/plugins/advanced-svg-animator/</code></li>
            <li>Activate the plugin in WordPress admin</li>
            <li>Check for any error messages or conflicts</li>
            <li>Test SVG uploads and block functionality</li>
        </ol>

        <h3>Environment Setup:</h3>
        <div class="code">
            // Add to wp-config.php for debugging:
            define('WP_DEBUG', true);
            define('WP_DEBUG_LOG', true);
            define('ASA_DEBUG', true);
        </div>
    </div>

    <!-- Plugin Features -->
    <h2>Advanced SVG Animator Features</h2>
    <table>
        <tr>
            <th>Feature</th>
            <th>Description</th>
            <th>Conflict Impact</th>
        </tr>
        <tr>
            <td>SVG Upload Support</td>
            <td>Enables SVG file uploads with security</td>
            <td><?php echo $has_conflicts ? 'Will be disabled if conflicts detected' : 'Fully available'; ?></td>
        </tr>
        <tr>
            <td>SVG Security Scanner</td>
            <td>Scans for malicious content in SVG files</td>
            <td>Always available (no conflicts)</td>
        </tr>
        <tr>
            <td>Animation Block</td>
            <td>Gutenberg block for SVG animations</td>
            <td>Always available (no conflicts)</td>
        </tr>
        <tr>
            <td>Real Estate Icons</td>
            <td>20+ professional real estate SVG icons</td>
            <td>Always available (no conflicts)</td>
        </tr>
        <tr>
            <td>Simple History Integration</td>
            <td>Activity logging for compliance</td>
            <td>Optional (works with or without)</td>
        </tr>
    </table>

    <div style="margin-top: 40px; padding: 20px; background: #f9f9f9; border-radius: 5px;">
        <p><strong>Need Help?</strong></p>
        <p>For support or questions about Advanced SVG Animator:</p>
        <ul>
            <li>GitHub Repository: <a href="https://github.com/DavidEngland/advanced-svg-animator" target="_blank">advanced-svg-animator</a></li>
            <li>Documentation: Available in plugin repository</li>
            <li>Real Estate Intelligence Agency: <a href="https://realestate-huntsville.com" target="_blank">realestate-huntsville.com</a></li>
        </ul>
    </div>

    <script>
        // Auto-refresh every 30 seconds when testing
        if (window.location.search.includes('autorefresh')) {
            setTimeout(() => window.location.reload(), 30000);
        }
    </script>
</body>
</html>
