# SVG Animator Block Documentation

## Overview

The **SVG Animator Block** is a powerful Gutenberg block that allows users to insert SVG files from their media library and apply stunning CSS animations. This block provides an intuitive interface for creating engaging animated graphics without writing any code.

## Features

### 🎯 Core Functionality
- **Media Library Integration**: Select SVG files directly from WordPress media library
- **Inline SVG Rendering**: SVGs are embedded as inline code to allow CSS targeting of internal elements
- **Real-time Preview**: See your animations as you configure them in the editor
- **Responsive Design**: SVGs automatically adapt to different screen sizes

### 🎨 Animation Types
The block includes 9 predefined animation types:

1. **Fade In**: Smooth opacity transition from 0 to 1
2. **Slide Up**: Enters from below with vertical movement
3. **Slide Down**: Enters from above with vertical movement  
4. **Slide Left**: Enters from right with horizontal movement
5. **Slide Right**: Enters from left with horizontal movement
6. **Scale**: Grows from scale(0) to scale(1)
7. **Rotate**: Spins 360 degrees while fading in
8. **Bounce**: Energetic bouncing effect with cubic-bezier timing
9. **Draw Line**: Special animation for SVG paths using stroke-dasharray

### ⚙️ Animation Controls
- **Duration**: 0.1 to 10 seconds (adjustable in 0.1s increments)
- **Delay**: 0 to 5 seconds before animation starts
- **Iteration Count**: Number of times to repeat (supports "infinite")
- **Timing Function**: Easing options (ease, linear, ease-in, ease-out, ease-in-out, custom cubic-bezier)
- **Custom CSS Class**: Add your own CSS classes for advanced animations

### 📐 Layout Options
- **Width/Height Control**: Set custom dimensions or use "auto"
- **Alignment**: Left, center, right, wide, full-width support
- **Spacing**: Margin and padding controls via block supports

## How to Use

### 1. Adding the Block
1. In the Gutenberg editor, click the **+** (Add Block) button
2. Search for "SVG Animator" or find it in the **Media** category
3. Click to add the block to your page

### 2. Selecting an SVG
1. Click **"Select SVG"** in the placeholder
2. Choose an SVG file from your media library (upload if needed)
3. The SVG will render inline immediately for preview

### 3. Configuring Animation
Open the block settings panel on the right to access:

#### SVG Settings
- **Change/Remove SVG**: Replace or remove the current SVG
- **Width**: Set custom width (e.g., "300px", "50%", "auto")
- **Height**: Set custom height (e.g., "200px", "auto")

#### Animation Settings
- **Animation Type**: Choose from dropdown of predefined animations
- **Duration**: Use slider to set animation duration (0.1-10s)
- **Delay**: Add delay before animation starts (0-5s)
- **Iteration Count**: Enter number (1, 2, 3...) or "infinite"
- **Timing Function**: Select easing from dropdown
- **Custom CSS Class**: Add additional CSS classes

### 4. Preview & Publish
- Preview animations directly in the editor
- Animation info displays below the SVG in edit mode
- Publish to see full frontend experience with scroll triggers

## Technical Implementation

### SVG Rendering Method
The block renders SVGs **inline** rather than as `<img>` tags. This approach provides several advantages:

1. **CSS Targeting**: Can style individual SVG elements (paths, circles, etc.)
2. **Animation Support**: CSS animations work on internal SVG elements
3. **Performance**: No additional HTTP requests for SVG files
4. **Flexibility**: Full control over SVG appearance and behavior

### How Inline Rendering Works

#### In the Editor (edit.js)
```javascript
const fetchSVGContent = async (attachmentId) => {
    const response = await apiFetch({
        path: `/advanced-svg-animator/v1/svg-content/${attachmentId}`,
    });
    // SVG content is fetched via REST API and stored in block attributes
};
```

#### In the Frontend (save.js)
```javascript
const processSVGContent = () => {
    const parser = new DOMParser();
    const svgDoc = parser.parseFromString(svgContent, 'image/svg+xml');
    const svgElement = svgDoc.querySelector('svg');
    
    // Add animation classes and data attributes
    if (animationType !== 'none') {
        svgElement.classList.add(`asa-animation-${animationType}`);
    }
    
    return svgElement.outerHTML;
};
```

### CSS Animation System

The block uses CSS variables and classes for flexible animations:

```css
.asa-animation-fadeIn {
    animation-name: asa-fadeIn;
    animation-duration: var(--asa-duration, 1s);
    animation-delay: var(--asa-delay, 0s);
    animation-iteration-count: var(--asa-iteration, 1);
    animation-timing-function: var(--asa-timing, ease);
    animation-fill-mode: both;
}

@keyframes asa-fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
```

### REST API Integration

The block includes a custom REST endpoint for secure SVG content fetching:

- **Endpoint**: `/wp-json/advanced-svg-animator/v1/svg-content/{id}`
- **Method**: GET
- **Permissions**: Requires SVG upload permissions
- **Response**: JSON with SVG content, URL, and metadata

## Block Attributes

```json
{
    "svgId": "number",           // WordPress attachment ID
    "svgUrl": "string",          // Direct URL to SVG file
    "svgContent": "string",      // Raw SVG markup
    "animationType": "string",   // Animation type enum
    "duration": "number",        // Animation duration in seconds
    "delay": "number",           // Animation delay in seconds
    "iterationCount": "string",  // Repeat count or "infinite"
    "timingFunction": "string",  // CSS timing function
    "customCSSClass": "string",  // Additional CSS classes
    "width": "string",           // SVG width
    "height": "string",          // SVG height
    "align": "string"            // Block alignment
}
```

## Frontend Features

### Scroll-Triggered Animations
The frontend JavaScript (`frontend.js`) adds advanced features:

- **Intersection Observer**: Triggers animations when blocks enter viewport
- **Performance Optimization**: Only animates visible elements
- **Accessibility**: Respects `prefers-reduced-motion` user preference
- **Draw Line Special Handling**: Calculates path lengths for stroke animations

### Public API
The frontend exposes a global API for controlling animations:

```javascript
// Restart all animations on the page
window.ASVGAnimator.restart();

// Pause all running animations
window.ASVGAnimator.pause();

// Resume paused animations
window.ASVGAnimator.resume();

// Re-initialize animation system
window.ASVGAnimator.init();
```

## Customization Guide

### Adding Custom Animations

1. **Define Keyframes** in `animations.js`:
```javascript
{
    label: 'My Custom Animation',
    value: 'myCustom',
    keyframes: `
        @keyframes asa-myCustom {
            0% { transform: scale(0) rotate(0deg); }
            50% { transform: scale(1.2) rotate(180deg); }
            100% { transform: scale(1) rotate(360deg); }
        }
    `,
}
```

2. **Add CSS Class** in `style.css`:
```css
.asa-animation-myCustom {
    animation-name: asa-myCustom;
    animation-duration: var(--asa-duration, 1s);
    /* ... other properties ... */
}
```

3. **Update Enum** in `block.json`:
```json
"enum": ["none", "fadeIn", "slideUp", ..., "myCustom"]
```

### Custom CSS Classes
Users can add custom CSS classes via the "Custom CSS Class" field:

```css
/* Example: Hover effects */
.my-hover-effect:hover {
    transform: scale(1.1);
    transition: transform 0.3s ease;
}

/* Example: Color animations */
.color-cycle {
    animation: colorCycle 3s infinite;
}

@keyframes colorCycle {
    0% { filter: hue-rotate(0deg); }
    100% { filter: hue-rotate(360deg); }
}
```

## Browser Support

- **Modern Browsers**: Full support in Chrome, Firefox, Safari, Edge
- **CSS Variables**: IE11+ (with fallbacks)
- **Intersection Observer**: Polyfill available for older browsers
- **SVG Support**: All modern browsers

## Accessibility Features

- **Reduced Motion**: Respects `prefers-reduced-motion: reduce`
- **High Contrast**: Supports `prefers-contrast: high`
- **Keyboard Navigation**: Full keyboard accessibility
- **Screen Readers**: Proper ARIA attributes and semantic HTML

## Performance Considerations

- **Lazy Loading**: Animations only trigger when in viewport
- **CSS-Only**: No JavaScript required for basic animations
- **Optimized SVGs**: Sanitization removes unnecessary elements
- **Debounced Events**: Resize and scroll events are optimized

## Troubleshooting

### Common Issues

1. **SVG Not Displaying**
   - Check if user has SVG upload permissions
   - Verify SVG file is valid and not corrupted
   - Ensure SVG sanitization hasn't removed required elements

2. **Animation Not Working**
   - Check browser console for JavaScript errors
   - Verify CSS classes are applied correctly
   - Test with reduced motion settings disabled

3. **Permission Errors**
   - Check plugin settings for allowed user roles
   - Verify user has appropriate WordPress capabilities
   - Check REST API permissions

### Debug Mode
Enable debug logging in `wp-config.php`:
```php
define('ASA_DEBUG', true);
```

This logs SVG processing and animation events to help diagnose issues.

## Security

- **SVG Sanitization**: All uploaded SVGs are sanitized using `enshrined/svg-sanitize`
- **Permission Checks**: Upload permissions enforced at multiple levels
- **REST API Security**: Endpoints require proper authentication
- **XSS Prevention**: All content is properly escaped and validated

---

*This documentation covers the comprehensive SVG Animator Block implementation. For additional support or customization requests, please refer to the plugin's main documentation or contact the development team.*
