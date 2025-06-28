# Advanced SVG Animator - Settings Page Documentation

## Overview

The Advanced SVG Animator plugin now includes a comprehensive settings page accessible via **WordPress Admin > Settings > SVG Animator**. This settings page allows administrators to configure SVG upload permissions, security settings, and debug options.

## Settings Page Features

### 1. SVG Upload Permissions

**Location:** Settings > SVG Animator > SVG Upload Permissions

Configure which user roles are allowed to upload SVG files:

- **Administrator** (always enabled for security)
- **Editor**
- **Author** 
- **Contributor**
- **Subscriber**

**Default:** Only Administrator role is allowed

**Security Note:** It's recommended to limit SVG uploads to trusted users only, as SVG files can contain executable code.

### 2. Security Settings

#### Enable SVG Sanitization
- **Default:** Enabled
- **Recommendation:** Always keep enabled
- **Warning:** Disabling this poses significant security risks

#### Sanitization Level
- **Strict:** Maximum security, limited animation features
- **Basic:** Good security with essential animation support
- **Advanced:** Balanced security with full animation support (default)

#### Debug Logging
- **Default:** Disabled
- **Purpose:** Logs SVG processing events for troubleshooting
- **Log Location:** `wp-content/plugins/advanced-svg-animator/logs/debug.log`

## Usage Examples

### Allowing Editors to Upload SVGs

1. Navigate to **Settings > SVG Animator**
2. In the "Allowed User Roles" section, check "Editor"
3. Click "Save Changes"

### Enabling Debug Mode

1. Go to **Settings > SVG Animator**
2. Scroll to "Security Settings"
3. Check "Enable debug logging for SVG processing"
4. Click "Save Changes"

### Changing Sanitization Level

For environments requiring maximum security:
1. Set "Sanitization Level" to "Strict"
2. Save changes
3. Test SVG uploads to ensure required features still work

## Settings API Integration

The plugin uses WordPress Settings API for proper integration:

### Accessing Settings Programmatically

```php
// Get plugin options
$options = get_option('asa_plugin_options', array());

// Check if user role is allowed
$allowed_roles = $options['allowed_roles'];
$user_can_upload = in_array('editor', $allowed_roles);

// Check if sanitization is enabled
$sanitization_enabled = !empty($options['enable_svg_sanitization']);

// Get sanitization level
$level = $options['sanitization_level']; // 'strict', 'basic', or 'advanced'
```

### Filter Hooks

Customize behavior with filter hooks:

```php
// Modify allowed roles programmatically
add_filter('asa_can_upload_svgs', function($can_upload) {
    // Custom logic here
    return $can_upload && current_user_can('edit_posts');
});
```

## Error Messages

The plugin provides detailed error messages when SVG uploads fail:

### Permission Denied
```
Your user role (contributor) is not permitted to upload SVG files. 
Allowed roles: administrator, editor. 
Please contact an administrator if you need access.
```

### Sanitization Failed
```
SVG file contains potentially harmful content and cannot be sanitized safely.
```

### File Processing Error
```
SVG file appears to be empty or corrupted.
```

## Status Information

The settings page displays real-time status information:

- **SVG Sanitizer Library:** Available/Not Available
- **Composer Dependencies:** Installed/Not Installed  
- **PHP Version:** Current version with compatibility status
- **WordPress Version:** Current version with compatibility status

## Security Best Practices

### Recommended Settings

1. **Upload Permissions:** Limit to Administrator and Editor roles only
2. **Sanitization:** Always enabled with "Advanced" level
3. **Debug Logging:** Enable only when troubleshooting

### User Role Guidelines

- **Administrator:** Full access (cannot be disabled)
- **Editor:** Safe for trusted content managers
- **Author:** Use with caution, only for very trusted users
- **Contributor/Subscriber:** Not recommended for SVG uploads

### Monitoring

1. Enable debug logging when investigating issues
2. Regularly review uploaded SVG files
3. Monitor logs for sanitization failures
4. Keep the plugin and dependencies updated

## Troubleshooting

### Settings Not Saving
1. Check user permissions (need `manage_options` capability)
2. Verify WordPress Settings API is functioning
3. Check for plugin conflicts

### SVG Uploads Failing
1. Enable debug logging
2. Check user role permissions
3. Verify sanitization settings
4. Review log files for specific errors

### Performance Issues
1. Disable debug logging in production
2. Choose appropriate sanitization level
3. Monitor server resources during uploads

## Migration from Previous Versions

When upgrading from earlier versions:

1. Default settings are automatically applied
2. Administrator role is always included in allowed roles
3. Sanitization remains enabled by default
4. Debug logging is disabled by default

Previous custom configurations may need to be recreated through the settings page.
