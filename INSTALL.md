# Advanced SVG Animator - Installation Guide

## Prerequisites

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Composer (for dependency management)
- Administrator access to WordPress

## Installation Steps

### 1. Install the Plugin

Download or clone this plugin to your WordPress plugins directory:
```bash
cd wp-content/plugins/
git clone [your-repo-url] advanced-svg-animator
# OR extract the plugin ZIP file
```

### 2. Install Dependencies

Navigate to the plugin directory and install Composer dependencies:
```bash
cd advanced-svg-animator
composer install --no-dev
```

This will install the `enshrined/svg-sanitize` library required for secure SVG handling.

### 3. Activate the Plugin

1. Log in to your WordPress admin dashboard
2. Navigate to **Plugins > Installed Plugins**
3. Find "Advanced SVG Animator" and click **Activate**

### 4. Verify Installation

After activation, the plugin will:
- Enable SVG MIME type support for file uploads
- Hook into the upload process for automatic SVG sanitization
- Set default security options

### 5. Configuration Options

#### Enable Debug Logging (Optional)
Add to your `wp-config.php` file:
```php
define('ASA_DEBUG', true);
```

#### Customize Upload Permissions
By default, only administrators can upload SVG files. To modify this:
```php
add_filter('asa_can_upload_svgs', function($can_upload) {
    // Allow editors to upload SVGs
    return current_user_can('edit_pages');
});
```

## Testing the Installation

### 1. Test SVG Upload
1. Go to **Media > Add New**
2. Try uploading the test files from `test-samples/` directory
3. Verify that malicious content is removed while animations are preserved

### 2. Test Sanitization
Upload `test-svg-with-malicious-content.svg` and verify:
- Script tags are removed
- Event handlers (onload, onclick) are stripped
- Safe animation attributes are preserved
- File uploads successfully

### 3. Test Animation Support
Upload `clean-animation-demo.svg` and verify:
- All animations work correctly
- Gradients and paths are preserved
- No content is unnecessarily removed

## Troubleshooting

### Composer Dependencies Not Found
```bash
# Ensure Composer is installed
composer --version

# Reinstall dependencies
rm -rf vendor/
composer install --no-dev
```

### SVG Upload Fails
1. Check user permissions (must be administrator by default)
2. Verify SVG content doesn't contain blocked elements
3. Check error logs for sanitization failures

### Debug Information
Enable debug mode and check logs at:
- Plugin logs: `wp-content/plugins/advanced-svg-animator/logs/debug.log`
- WordPress logs: `wp-content/debug.log` (if WP_DEBUG_LOG is enabled)

### PHP Errors
Ensure your server meets requirements:
```bash
php -v  # Should be 7.4+
php -m | grep dom  # Should show DOM extension
php -m | grep libxml  # Should show libxml extension
```

## Security Notes

- Only upload SVG files from trusted sources
- Review uploaded SVGs if they come from external users
- Monitor the debug logs for sanitization failures
- Keep the plugin and its dependencies updated

## Next Steps

After successful installation:
1. Review the [SECURITY.md](SECURITY.md) for detailed security information
2. Explore configuration options in [README.md](README.md)
3. Test with your own SVG animations
4. Consider implementing custom sanitization rules if needed

## Support

If you encounter issues:
1. Check the logs for error messages
2. Verify all requirements are met
3. Test with the provided sample files
4. Review the security documentation for advanced configuration
