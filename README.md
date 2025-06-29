# Advanced SVG Animator WordPress Plugin

## 🏢 About

**Developed by:** David England  
**Organization:** Real Estate Intelligence Agency (REIA)  
**Website:** [https://realestate-huntsville.com](https://realestate-huntsville.com)  
**Repository:** [https://github.com/DavidEngland/advanced-svg-animator](https://github.com/DavidEngland/advanced-svg-animator)

## Overview
The Advanced SVG Animator is a comprehensive WordPress plugin for creating and managing advanced SVG animations with security scanning, real estate icon library, and easy-to-use Gutenberg blocks. Perfect for real estate websites and professional WordPress sites.

## Key Features

🎯 **Gutenberg Block Integration**: Drag-and-drop SVG animation block for the WordPress editor  
🔒 **Secure SVG Upload**: Role-based permissions with robust SVG sanitization  
🛡️ **SVG Security Scanner**: Advanced threat detection with automated scanning and batch actions  
🎨 **15+ Animation Presets**: Fade, slide, scale, rotate, bounce, flip, zoom, typewriter, and path drawing animations  
⚙️ **Advanced Controls**: Duration, delay, iteration, timing functions, and custom CSS  
🎨 **Color Customization**: Background colors, SVG fill colors, gradients, and multi-element color targeting  
📝 **Text Animation**: Typewriter effects, character-by-character reveals, and staggered text animations  
🎯 **Precise Targeting**: CSS selectors for animating specific SVG elements  
📱 **Responsive Design**: Mobile-friendly animations with accessibility support  
🚀 **Performance Optimized**: Scroll-triggered animations and reduced motion support  

## File Structure

```
advanced-svg-animator/
├── advanced-svg-animator.php          # Main plugin file with SVG & block support
├── functions/                         # Core utility functions
│   ├── core-functions.php            # Core helper functions
│   ├── animation-functions.php       # Animation-specific functions
│   └── logging-functions.php         # Debug logging system
├── includes/                          # Core classes
│   ├── class-asa-component.php       # Base component class
│   ├── class-asa-svg-handler.php     # SVG handling functionality
│   ├── class-asa-admin-settings.php  # Admin settings page
│   ├── class-asa-svg-config.php      # SVG configuration management
│   ├── class-asa-svg-security-scanner.php  # SVG security scanner engine
│   ├── class-asa-svg-scanner-admin.php     # Scanner admin interface
│   └── class-asa-svg-scanner-testing.php   # Scanner testing utilities
├── blocks/                           # Gutenberg blocks
│   └── svg-animator/                 # SVG Animator block
│       ├── block.json               # Block configuration
│       ├── index.js                 # Block registration
│       ├── edit.js                  # Editor interface
│       ├── save.js                  # Frontend output
│       ├── animations.js            # Animation definitions
│       ├── editor.css               # Editor styles
│       ├── style.css                # Frontend styles
│       └── frontend.js              # Frontend JavaScript
├── assets/                           # Additional plugin assets
│   ├── css/
│   │   └── svg-scanner-admin.css    # Scanner admin interface styles
│   └── js/
│       └── svg-scanner-admin.js     # Scanner admin interface JavaScript
├── test-samples/                     # Sample SVG files for testing
│   ├── clean-animation-demo.svg     # Reference clean SVG for testing
│   ├── real-estate-house-icon.svg   # Animated house icon (color)
│   ├── real-estate-for-sale-sign.svg # Animated for sale sign (color)
│   ├── real-estate-key-icon.svg     # Animated key icon (color)
│   ├── real-estate-apartment-building.svg # Animated apartment (color)
│   ├── real-estate-location-pin.svg # Animated location pin (color)
│   ├── line-house-icon.svg          # Minimalist house (line style)
│   ├── line-for-sale-icon.svg       # Minimalist for sale sign (line style)
│   ├── line-key-icon.svg            # Minimalist key (line style)
│   ├── line-apartment-icon.svg      # Minimalist apartment (line style)
│   └── line-location-pin-icon.svg   # Minimalist location pin (line style)
├── composer.json                     # Composer dependencies (SVG sanitization)
├── README.md                        # This file
├── SECURITY.md                      # Security documentation
├── INSTALL.md                       # Installation guide
├── SETTINGS.md                      # Settings documentation
├── BLOCK_DOCUMENTATION.md           # Complete block documentation
└── TESTING_GUIDE.md                # Testing procedures
```

## Features Implemented

### ✨ Gutenberg Block System
- ✅ **SVG Animator Block**: Complete Gutenberg block for SVG animations
- ✅ **Media Library Integration**: Select SVG files directly from WordPress media
- ✅ **Inline SVG Rendering**: SVGs embedded as inline code for CSS targeting
- ✅ **15+ Predefined Animations**: Fade, slide, scale, rotate, bounce, flip, zoom, typewriter, and draw effects
- ✅ **Advanced Controls**: Duration, delay, iteration count, timing functions
- ✅ **Color Customization**: Background colors, SVG fill colors, gradients, and precise color targeting
- ✅ **Text Animation System**: Typewriter effects, character reveals, and staggered animations
- ✅ **Real-time Preview**: See animations as you configure them in the editor
- ✅ **Custom CSS Classes**: Support for advanced user animations
- ✅ **Responsive Design**: Mobile-friendly with accessibility features
- ✅ **REST API Integration**: Secure SVG content fetching

### Core Plugin Features
- ✅ Main plugin class (`ASA_Plugin`) with singleton pattern
- ✅ Proper WordPress hooks integration
- ✅ Plugin lifecycle management (activation, deactivation, uninstall)
- ✅ Security checks and requirements validation
- ✅ Constants for paths and URLs
- ✅ Admin settings page with role-based permissions
- ✅ Debug logging system

### SVG Support Features
- ✅ SVG MIME type support for WordPress uploads
- ✅ SVG media library previews
- ✅ Robust SVG sanitization using `enshrined/svg-sanitize` library
- ✅ Role-based SVG upload permissions
- ✅ Multiple sanitization levels (strict, basic, advanced)
- ✅ SVG file validation and security checks

### Animation System
- ✅ Animation preset system
- ✅ CSS animation generation
- ✅ Timeline creation framework
- ✅ Animation configuration validation
- ✅ SVG element extraction for animation targeting

### Component Architecture
- ✅ Base component class for extensibility
- ✅ Dependency checking system
- ✅ Component enable/disable functionality
- ✅ Hook management system

## Security Features

### SVG Upload Security
- **Admin-only uploads**: Only users with `manage_options` capability can upload SVGs by default
- **Content sanitization**: Removes dangerous elements (`<script>`, `<iframe>`, etc.)
- **Attribute filtering**: Strips event handlers and JavaScript links
- **File validation**: Ensures uploaded files are valid XML/SVG format

### General Security
- **Direct access prevention**: All files check for `ABSPATH` constant
- **Nonce verification**: AJAX endpoints use WordPress nonces
- **Capability checks**: Proper permission verification throughout
- **Input sanitization**: All user inputs are properly sanitized

## Key Functions

### Core Functions (`functions/core-functions.php`)
- `asa_get_option()` - Get plugin options with defaults
- `asa_update_option()` - Update plugin options
- `asa_sanitize_svg_content()` - Sanitize SVG content using enshrined/svg-sanitize
- `asa_get_svg_dimensions()` - Extract SVG dimensions
- `asa_is_valid_svg()` - Validate SVG files

### Animation Functions (`functions/animation-functions.php`)
- `asa_get_animation_presets()` - Get available animation presets
- `asa_generate_animation_css()` - Generate CSS from animation presets
- `asa_add_animations_to_svg()` - Add animation classes to SVG elements
- `asa_create_timeline()` - Create animation timeline data
- `asa_validate_animation_config()` - Validate animation configurations

## SVG Security & Sanitization

This plugin integrates the robust `enshrined/svg-sanitize` library for secure SVG file handling:

### Features
- **Automatic SVG Sanitization** - All uploaded SVG files are sanitized before storage
- **Whitelist-based Filtering** - Only safe elements and attributes are allowed
- **Animation Support** - Preserves animation-related attributes while maintaining security
- **Fallback Sanitizer** - Custom DOM-based sanitizer when library unavailable

### Supported Animation Attributes
- **SMIL Animation**: `animate`, `animateTransform`, `animateMotion`, `set`
- **Styling**: `stroke-dasharray`, `stroke-dashoffset`, `transform`, `opacity`
- **CSS Animations**: `animation`, `transition` properties
- **Timing**: `begin`, `dur`, `repeatCount`, `keyTimes`, `values`

### Security Implementation
```php
// Hooked into upload process
add_filter('wp_handle_upload_prefilter', array($this, 'asa_sanitize_svg_on_upload'));

// Uses enshrined/svg-sanitize library with animation-safe defaults
$sanitizer = new \enshrined\svgSanitize\Sanitizer();
$clean_svg = $sanitizer->sanitize($svg_content);
```

For detailed security information, see [SECURITY.md](SECURITY.md).

## Plugin Configuration

### Default Options
```php
$default_options = array(
    'version' => ASA_VERSION,
    'svg_upload_capability' => 'manage_options',
    'enable_svg_sanitization' => true,
);
```

### Installation Requirements
1. Install Composer dependencies: `composer install --no-dev`
2. Ensure PHP 7.4+ and WordPress 5.0+
3. Admin privileges for SVG uploads

### Filter Hooks Available
- `asa_can_upload_svgs` - Control SVG upload permissions
- `asa_animation_presets` - Modify available animation presets
- `asa_svg_sanitizer_config` - Customize sanitization rules

## Animation Presets

The plugin includes comprehensive built-in animation presets:

### **Basic Animations**
- **Fade In**: Opacity transition from 0 to 1
- **Slide In Left/Right/Up/Down**: Transform translateX/Y animations
- **Scale/Zoom In**: Transform scale animation with elastic easing
- **Rotate 360°**: Continuous rotation animation

### **Advanced Animations**
- **Bounce**: Elastic bounce effect with spring timing
- **Flip X/Y**: 3D flip transformations
- **Slide In Bounce**: Combined slide and bounce effects
- **Fade In Up**: Fade with upward movement

### **SVG-Specific Animations**
- **Draw Line**: Progressive line drawing effect
- **Draw SVG Paths**: Animate SVG path strokes
- **Morph Path**: Transform between different path shapes

### **Text Animations**
- **Typewriter**: Character-by-character reveal
- **Staggered Text**: Character/word delays
- **Text Fade In**: Progressive text appearance

## Usage Examples

### Checking SVG Upload Permissions
```php
if (asa_current_user_can_upload_svgs()) {
    // User can upload SVGs
}
```

### Getting Animation Presets
```php
$presets = asa_get_animation_presets();
foreach ($presets as $id => $preset) {
    echo $preset['name'];
}
```

### Generating Animation CSS
```php
$css = asa_generate_animation_css('fade_in', array(
    'duration' => 2000,
    'easing' => 'ease-in-out'
));
```

## Quick Start

### 1. Installation
1. Upload the plugin to `/wp-content/plugins/`
2. Activate via WordPress admin
3. Install dependencies: `composer install` (if using SVG sanitization)

### 2. Configure Settings  
1. Go to **Settings > SVG Animator**
2. Set user role permissions for SVG uploads
3. Configure sanitization level
4. Enable debug logging if needed

### 3. Using the Block
1. Create a new post/page
2. Add the **SVG Animator** block
3. Select an SVG from your media library
4. Choose animation type and configure settings
5. Preview and publish!

### 4. Advanced Usage
- Add custom CSS classes for complex animations
- Use the REST API endpoint for programmatic access
- Implement custom animation keyframes
- Integrate with theme templates

## Documentation

- 📖 **[Complete Block Documentation](BLOCK_DOCUMENTATION.md)** - Detailed guide for the SVG Animator block
- 🔧 **[Settings Guide](SETTINGS.md)** - Admin settings and configuration
- 🔒 **[Security Documentation](SECURITY.md)** - Security features and best practices  
- �️ **[SVG Scanner User Guide](SVG_SECURITY_SCANNER_USER_GUIDE.md)** - Complete scanner usage guide
- 🎯 **[Scanner Demo Guide](SVG_SCANNER_DEMO_GUIDE.md)** - Quick start and testing instructions
- �📋 **[Testing Guide](TESTING_GUIDE.md)** - Testing procedures and troubleshooting
- 🚀 **[Installation Guide](INSTALL.md)** - Step-by-step installation instructions

## Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **User Capability**: `manage_options` for SVG uploads

## Development Notes

### Extensibility
The plugin is built with extensibility in mind:
- Component-based architecture allows easy feature additions
- Filter hooks enable customization without core modifications
- Separate includes and functions directories for organized development

### Performance Considerations
- SVG optimization reduces file sizes
- Lazy loading of admin-only components
- Efficient DOM manipulation for SVG processing
- Minimal frontend footprint when animations not in use

### Future Development
The foundation is set for additional features:
- Advanced animation editor interface
- Timeline-based animation controls
- Interactive SVG elements
- Animation export/import functionality
- Performance monitoring and optimization tools

## Installation

1. Upload the plugin directory to `/wp-content/plugins/`
2. Activate the plugin through the WordPress admin
3. Configure SVG upload permissions if needed
4. Start uploading and animating SVG files

The plugin is now ready for further development and customization based on specific project requirements.

### SVG Security Scanner ✅
- ✅ **Advanced Threat Detection**: 25+ malicious pattern recognition system
- ✅ **Admin Interface**: Dedicated dashboard with real-time progress indicators
- ✅ **Batch Actions**: Multi-select operations for quarantine, delete, and rescan
- ✅ **Scheduled Scanning**: Automated daily/weekly/monthly scans via WordPress Cron
- ✅ **Email Notifications**: Configurable threat level alerts
- ✅ **Performance Optimized**: Memory-managed batch processing for large libraries
- ✅ **Upload-time Scanning**: Automatic security checks for new SVG uploads
- ✅ **Quarantine System**: Safe file isolation without permanent deletion

## 🎨 Animated SVG Icon Library

The plugin includes a comprehensive collection of animated SVG icons designed specifically for real estate websites. These icons are production-ready and optimized for web use.

### Color Real Estate Icons ✅

**`/test-samples/real-estate-house-icon.svg`** - Vibrant house with comprehensive animations
- Color gradient house with roof, door, windows, and chimney
- Roof color transitions, window lighting effects, door pulse
- Smoke animation from chimney and floating movement
- Perfect for home page hero sections or property showcases

**`/test-samples/real-estate-for-sale-sign.svg`** - Professional "For Sale" sign with movement
- Realistic sign post with color gradients and shadow
- Gentle swaying animation and text highlight effects  
- Sign board color transitions and shadow breathing
- Ideal for property listings and sales indicators

**`/test-samples/real-estate-key-icon.svg`** - Elegant key with metallic effects
- Gradient key design with realistic metallic appearance
- Smooth rotation and scale animations with sparkle effects
- Ring pulse and overall floating movement
- Great for access control, security, or rental features

**`/test-samples/real-estate-apartment-building.svg`** - Multi-story building with detailed animations
- Colorful apartment building with gradient walls
- Individual window lighting effects and roof color transitions
- Antenna rotation and overall building pulse
- Perfect for multi-unit property listings

**`/test-samples/real-estate-location-pin.svg`** - Dynamic location marker with effects
- Vibrant gradient pin with pulsing animations
- Expanding ripple effects and color transitions
- Shadow breathing and floating movement
- Ideal for property maps and location features

### Minimalist Line-Style Icons (FontAwesome Style) ✅

**`/test-samples/line-house-icon.svg`** - Clean house outline with subtle animations
- Simple geometric house shape with line art styling
- Gentle scale pulsing and opacity breathing effects
- Stroke weight variations and window highlights
- Perfect for navigation, icons, or minimalist designs

**`/test-samples/line-for-sale-icon.svg`** - Professional sign outline with movement
- Minimalist post and sign structure with clean lines
- Subtle swaying animation and highlight effects
- Typography placeholder areas for custom text
- Ideal for status indicators and clean UI elements

**`/test-samples/line-key-icon.svg`** - Modern key silhouette with rotation
- Elegant key outline with decorative head pattern
- Smooth rotation and scale animations
- Stroke opacity variations and geometric precision
- Great for security, access, or service icons

**`/test-samples/line-apartment-icon.svg`** - Structured building outline with window effects
- Multi-story building with roof detail and clean lines
- Staggered window lighting effects and building pulse
- Antenna animation and stroke weight variations
- Perfect for property type categorization

**`/test-samples/line-location-pin-icon.svg`** - Location marker with ripple animations
- Classic map pin design with inner dot indicator
- Pulse animations with expanding ripple effects
- Shadow indicators and floating movement
- Ideal for mapping, location services, and property finding

### Icon Usage Guidelines

- **Color Icons**: Best for hero sections, featured content, and visual impact areas
- **Line Icons**: Perfect for navigation, toolbars, lists, and minimalist designs
- **Scalability**: All icons are vector-based and scale perfectly at any size
- **Performance**: Optimized animations with efficient keyframes and timing
- **Accessibility**: Support for `currentColor` in line icons for theme integration
- **Browser Support**: Compatible with all modern browsers supporting SVG animations
