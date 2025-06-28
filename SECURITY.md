# SVG Security and Sanitization Guide

## Overview

This guide explains the security considerations and implementation details for SVG file handling in the Advanced SVG Animator WordPress plugin.

## Security Considerations

### Why SVG Sanitization is Critical

SVG files are XML-based and can contain:
- JavaScript code (`<script>` tags)
- External resource references (potential data exfiltration)
- Event handlers (onclick, onload, etc.)
- CSS that can affect the entire page
- Base64-encoded content that may hide malicious code

### Attack Vectors

1. **Cross-Site Scripting (XSS)**
   ```svg
   <svg><script>alert('XSS')</script></svg>
   ```

2. **Event Handler Injection**
   ```svg
   <svg onload="alert('XSS')"><circle /></svg>
   ```

3. **CSS Injection**
   ```svg
   <svg><style>body { display: none; }</style></svg>
   ```

4. **External Resource Loading**
   ```svg
   <svg><image href="http://malicious.com/track.png" /></svg>
   ```

## Sanitization Strategy

### 1. Library Integration

The plugin uses `enshrined/svg-sanitize` as the primary sanitization library:

```php
// Check if library is available
if (class_exists('enshrined\svgSanitize\Sanitizer')) {
    $sanitizer = new \enshrined\svgSanitize\Sanitizer();
    $clean_svg = $sanitizer->sanitize($svg_content);
}
```

### 2. Fallback Custom Sanitizer

If the library is not available, a custom DOM-based sanitizer is used:

```php
// Load SVG into DOM
$dom = new DOMDocument();
$dom->loadXML($svg_content);

// Remove dangerous elements
$dangerous_elements = ['script', 'iframe', 'object', 'embed'];
// Clean attributes and validate content
```

### 3. Whitelist Approach

Only explicitly allowed elements and attributes are permitted:

#### Safe Elements for Animation
- Basic shapes: `circle`, `rect`, `path`, `polygon`
- Containers: `svg`, `g`, `defs`, `use`
- Animation: `animate`, `animateTransform`, `animateMotion`
- Text: `text`, `tspan`
- Gradients: `linearGradient`, `radialGradient`, `stop`

#### Safe Attributes for Animation
- Core: `id`, `class`, `style`
- Styling: `fill`, `stroke`, `opacity`
- Animation: `attributeName`, `begin`, `dur`, `from`, `to`
- Transform: `transform`, `transform-origin`

## Implementation Details

### File Upload Process

1. **User uploads SVG file**
2. **MIME type validation** - Ensure it's `image/svg+xml`
3. **User capability check** - Only users with appropriate permissions
4. **Content sanitization** - Remove/clean dangerous content
5. **Validation** - Ensure result is still valid SVG
6. **File storage** - Save sanitized version

### Code Flow

```php
// Hook into upload process
add_filter('wp_handle_upload_prefilter', array($this, 'asa_sanitize_svg_on_upload'));

public function asa_sanitize_svg_on_upload($file) {
    if ($file['type'] === 'image/svg+xml') {
        // Load content
        $content = file_get_contents($file['tmp_name']);
        
        // Sanitize
        $clean_content = $this->sanitize_svg_content($content);
        
        if ($clean_content === false) {
            $file['error'] = 'SVG contains harmful content';
            return $file;
        }
        
        // Overwrite with clean content
        file_put_contents($file['tmp_name'], $clean_content);
    }
    
    return $file;
}
```

## Configuration Options

### Basic Configuration
For simple animations with minimal security risk:
```php
$config = asa_get_basic_animation_config();
```

### Advanced Configuration
For complex animations with comprehensive element support:
```php
$config = asa_get_advanced_animation_config();
```

### Strict Configuration
For high-security environments:
```php
$config = asa_get_strict_animation_config();
```

## Installation and Setup

### 1. Install Dependencies

```bash
cd wp-content/plugins/advanced-svg-animator
composer install --no-dev
```

### 2. Verify Library Installation

```php
if (class_exists('enshrined\svgSanitize\Sanitizer')) {
    echo "SVG Sanitizer library is available";
} else {
    echo "Using fallback sanitizer";
}
```

### 3. Plugin Activation

The plugin automatically:
- Enables SVG MIME type support
- Hooks into the upload process
- Applies sanitization rules
- Provides media library previews

## Customization

### Modifying Allowed Elements

```php
add_filter('asa_svg_sanitizer_config', function($config, $type) {
    if ($type === 'custom') {
        $config['allowed_elements'][] = 'my-custom-element';
    }
    return $config;
}, 10, 2);
```

### Custom Validation Rules

```php
add_filter('asa_svg_content_valid', function($is_valid, $content) {
    // Add custom validation logic
    return $is_valid && my_custom_validation($content);
}, 10, 2);
```

## Testing and Validation

### 1. Test Uploads

Upload these test files to verify sanitization:

**Malicious Script Test:**
```svg
<svg><script>alert('test')</script><circle cx="50" cy="50" r="40"/></svg>
```
Expected: Script tag removed, circle preserved

**Event Handler Test:**
```svg
<svg onload="alert('test')"><rect width="100" height="100"/></svg>
```
Expected: onload attribute removed, rect preserved

### 2. Animation Functionality Test

```svg
<svg viewBox="0 0 100 100">
    <circle cx="50" cy="50" r="20" fill="blue">
        <animate attributeName="r" from="20" to="40" dur="2s" repeatCount="indefinite"/>
    </circle>
</svg>
```
Expected: Animation works correctly after sanitization

## Best Practices

### 1. User Permissions
- Only allow trusted users to upload SVGs
- Consider administrator-only uploads for maximum security

### 2. Content Validation
- Always validate SVG content after sanitization
- Log sanitization failures for security monitoring

### 3. Regular Updates
- Keep the sanitization library updated
- Monitor security advisories for SVG-related vulnerabilities

### 4. Fallback Handling
- Ensure graceful degradation when sanitization fails
- Provide clear error messages to users

## Monitoring and Logging

### Error Logging
```php
// Log sanitization attempts
asa_log('SVG sanitization started for file: ' . $filename, 'info');

// Log failures
asa_log('SVG sanitization failed: harmful content detected', 'warning');

// Log library issues
asa_log('Enshrined library not available, using fallback', 'notice');
```

### Security Monitoring
- Monitor failed upload attempts
- Track sanitization failures
- Regular review of uploaded SVG files

## Troubleshooting

### Common Issues

1. **Library Not Found**
   - Run `composer install`
   - Check autoloader inclusion
   - Verify file permissions

2. **Animations Not Working**
   - Check if animation elements are in whitelist
   - Verify required attributes are allowed
   - Test with basic animation first

3. **Upload Failures**
   - Check user capabilities
   - Verify MIME type detection
   - Review error logs

### Debug Mode

Enable debug logging:
```php
define('ASA_DEBUG', true);
```

This will provide detailed logs of the sanitization process.

## Security Checklist

- [ ] SVG sanitization library installed and working
- [ ] User capability checks implemented
- [ ] Whitelist-based element/attribute filtering
- [ ] Script and event handler removal
- [ ] External resource blocking
- [ ] Upload validation and error handling
- [ ] Regular security updates scheduled
- [ ] Monitoring and logging configured
