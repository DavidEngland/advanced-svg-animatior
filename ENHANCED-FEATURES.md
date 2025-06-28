# Advanced SVG Animator - Enhanced Features Documentation

## Overview

The Advanced SVG Animator block has been enhanced with powerful new capabilities for element targeting, animation presets, and trigger options. This document explains how to use these features effectively.

## New Features

### 1. Element Targeting

Element targeting allows you to animate specific parts of your SVG rather than the entire SVG element.

#### CSS Selectors Supported

- **ID Selectors**: `#my-path-id` - Target a specific element with an ID
- **Class Selectors**: `.my-group-class` - Target elements with a specific class
- **Element Selectors**: `path`, `circle`, `g` - Target all elements of a specific type
- **Attribute Selectors**: `[fill="red"]` - Target elements with specific attributes
- **Complex Selectors**: `g.group1 path` - Target paths within a group with class "group1"

#### Usage Examples

```html
<!-- SVG with IDs for targeting -->
<svg>
  <path id="line1" d="M10,10 L100,100" stroke="black" stroke-width="2" fill="none"/>
  <circle id="circle1" cx="50" cy="50" r="20" fill="blue"/>
  <g class="group1">
    <path d="M20,20 L80,80" stroke="red" stroke-width="2" fill="none"/>
  </g>
</svg>
```

**Targeting Examples:**
- Target specific path: `#line1`
- Target specific circle: `#circle1`
- Target all paths in a group: `.group1 path`
- Target all circles: `circle`

### 2. Animation Presets

#### Draw SVG Animation

The "Draw SVG" animation creates a drawing effect using `stroke-dasharray` and `stroke-dashoffset`.

**SVG Preparation Requirements:**
1. **Paths must have stroke properties**:
   ```html
   <path d="M10,10 L100,100" stroke="#333" stroke-width="2" fill="none"/>
   ```

2. **Remove or set fill to "none"** for best effect:
   ```html
   <path ... fill="none"/>
   ```

3. **Set appropriate stroke-width**:
   ```html
   <path ... stroke-width="2"/>
   ```

**Configuration Options:**
- **Stroke Color**: Color of the drawn line (default: #333333)
- **Stroke Width**: Width of the drawn line (default: 2)
- **Draw Direction**: `forward` or `reverse`
- **Simultaneous Paths**: Draw all paths at once or stagger them

#### Morph Path Animation

Creates a morphing effect with scale and opacity transitions.

### 3. Trigger Options

#### On Load (Default)
Animation starts immediately when the page loads.

```javascript
// Animation triggers automatically with optional delay
```

#### On Scroll (Intersection Observer)
Animation triggers when the element enters the viewport.

**Configuration:**
- **Scroll Offset**: Distance from viewport edge (in pixels)
- **Scroll Threshold**: Percentage of element visible (0.0 - 1.0)

```javascript
// Uses Intersection Observer API for performance
const observer = new IntersectionObserver(callback, {
  threshold: 0.1, // 10% visible
  rootMargin: '0px 0px -100px 0px' // 100px offset
});
```

#### On Hover
Animation triggers when user hovers over the specified target.

**Hover Targets:**
- **SVG Element**: Hover over the SVG itself
- **Block Container**: Hover over the entire block
- **Custom Selector**: Use element targeting selector as hover target

#### On Click
Animation triggers when user clicks the SVG element.

## Implementation Details

### Frontend JavaScript Architecture

The frontend JavaScript has been restructured to handle the new features:

```javascript
// Main initialization
function initSVGAnimations() {
  // Process each SVG block
  svgBlocks.forEach(block => {
    const svg = block.querySelector('svg[data-asa-animation]');
    if (svg) {
      setupSVGAnimation(svg, block);
    }
  });
}

// Setup based on trigger type
function setupSVGAnimation(svg, block) {
  const trigger = svg.getAttribute('data-asa-trigger');
  
  switch (trigger) {
    case 'onLoad':
      triggerAnimation(svg, block, animationType, targetSelector);
      break;
    case 'onScroll':
      setupScrollTrigger(svg, block, animationType, targetSelector);
      break;
    case 'onHover':
      setupHoverTrigger(svg, block, animationType, targetSelector);
      break;
    case 'onClick':
      setupClickTrigger(svg, block, animationType, targetSelector);
      break;
  }
}
```

### Data Attributes

The block outputs the following data attributes for frontend processing:

```html
<div class="svg-animator-block" 
     data-animation-type="drawSVGPaths"
     data-animation-trigger="onScroll"
     data-target-selector="#my-path"
     data-scroll-offset="100"
     data-scroll-threshold="0.1"
     data-hover-target="svg"
     data-draw-svg-settings='{"strokeColor":"#333","strokeWidth":2}'
     data-advanced-settings='{"staggerDelay":0.2}'>
  
  <svg data-asa-animation="drawSVGPaths"
       data-asa-trigger="onScroll"
       data-asa-target-selector="#my-path">
    <!-- SVG content -->
  </svg>
</div>
```

### Performance Optimizations

1. **Conditional Script Loading**: Frontend JavaScript only loads when the block is present
2. **Intersection Observer**: Efficient scroll-based triggers
3. **Reduced Motion Support**: Respects `prefers-reduced-motion` media query
4. **Event Delegation**: Minimal event listeners for better performance

## CSS Classes and Animations

### New Animation Classes

```css
/* Draw SVG Paths Animation */
.asa-animation-drawSVGPaths path,
.asa-animation-drawSVGPaths line,
.asa-animation-drawSVGPaths circle {
  animation-name: asa-drawSVGPaths;
  animation-duration: var(--asa-duration, 2s);
  animation-timing-function: var(--asa-timing, ease);
  animation-fill-mode: both;
}

@keyframes asa-drawSVGPaths {
  from { stroke-dashoffset: var(--path-length, 1000); }
  to { stroke-dashoffset: 0; }
}

/* Morph Path Animation */
.asa-animation-morphPath {
  animation-name: asa-morphPath;
}

@keyframes asa-morphPath {
  0% { opacity: 0; transform: scale(0.8); }
  50% { opacity: 0.5; transform: scale(1.1); }
  100% { opacity: 1; transform: scale(1); }
}
```

### Scroll Hidden State

```css
.asa-scroll-hidden {
  opacity: 0 !important;
  transform: translateY(20px) !important;
  transition: none !important;
}
```

### Trigger-Specific Styles

```css
/* Hover trigger feedback */
.svg-animator-block[data-animation-trigger="onHover"]:hover svg {
  transform: scale(1.05);
}

/* Click trigger feedback */
.svg-animator-block[data-animation-trigger="onClick"] svg {
  cursor: pointer;
}

.svg-animator-block[data-animation-trigger="onClick"] svg:hover {
  transform: scale(1.02);
}
```

## Best Practices

### SVG Preparation for Draw Animations

1. **Optimize your SVG**:
   - Remove unnecessary elements
   - Combine paths where possible
   - Set appropriate viewBox

2. **Add stroke properties**:
   ```html
   <path d="M10,10 L100,100" 
         stroke="#333" 
         stroke-width="2" 
         fill="none"
         id="my-path"/>
   ```

3. **Use meaningful IDs and classes**:
   ```html
   <g class="logo-text">
     <path id="letter-a" .../>
     <path id="letter-b" .../>
   </g>
   ```

### Element Targeting Guidelines

1. **Be specific**: Use IDs for single elements, classes for groups
2. **Test selectors**: Ensure your CSS selector matches the intended elements
3. **Consider animation order**: Use classes for staggered animations

### Performance Considerations

1. **Limit complex animations**: Too many simultaneous animations can impact performance
2. **Use appropriate triggers**: Scroll triggers are more performant than continuous hover effects
3. **Test on mobile devices**: Ensure animations work well on touch devices

## Accessibility

The enhanced block includes several accessibility features:

1. **Reduced Motion Support**: Automatically disables animations when user prefers reduced motion
2. **Keyboard Navigation**: Click triggers work with keyboard interaction
3. **Screen Reader Friendly**: Animations don't interfere with screen reader functionality

```css
@media (prefers-reduced-motion: reduce) {
  .svg-animator-block [class*="asa-animation-"] {
    animation: none !important;
  }
}
```

## Browser Support

- **Modern Browsers**: Full support for all features
- **Intersection Observer**: IE11 requires polyfill
- **CSS Custom Properties**: IE11 has limited support
- **SVG Animations**: Supported in all modern browsers

## Troubleshooting

### Common Issues

1. **Animation not triggering**: Check data attributes and console for errors
2. **Draw animation not working**: Ensure SVG has stroke properties
3. **Element targeting fails**: Verify CSS selector matches SVG structure
4. **Performance issues**: Reduce animation complexity or stagger timing

### Debug Mode

Enable debug mode by setting `ASA_DEBUG` to `true` in wp-config.php:

```php
define('ASA_DEBUG', true);
```

This will log additional information to the browser console.

## API Reference

### JavaScript API

```javascript
// Public API available on window.ASVGAnimator
window.ASVGAnimator.restart(); // Restart all animations
window.ASVGAnimator.pause();   // Pause all animations
window.ASVGAnimator.resume();  // Resume all animations
window.ASVGAnimator.init();    // Re-initialize animations
```

### Block Attributes

See `block.json` for complete attribute definitions including:
- `targetSelector`: CSS selector for element targeting
- `animationTrigger`: Trigger type (onLoad, onScroll, onHover, onClick)
- `scrollOffset`: Scroll trigger offset in pixels
- `scrollThreshold`: Scroll trigger threshold (0.0-1.0)
- `hoverTarget`: Hover trigger target (svg, block, custom)
- `drawSVGSettings`: Draw animation configuration
- `advancedSettings`: Advanced animation options

## Examples

### Basic Draw Animation

```html
<!-- In SVG content -->
<svg viewBox="0 0 200 200">
  <path id="signature" 
        d="M10,50 Q50,10 100,50 T190,50" 
        stroke="#333" 
        stroke-width="3" 
        fill="none"/>
</svg>
```

**Block Settings:**
- Animation Type: Draw SVG Paths
- Target Selector: `#signature`
- Trigger: On Scroll
- Stroke Width: 3
- Stroke Color: #333333

### Multiple Element Animation

```html
<svg viewBox="0 0 300 200">
  <g class="letters">
    <path id="letter-h" d="..." stroke="#e74c3c" stroke-width="4" fill="none"/>
    <path id="letter-e" d="..." stroke="#e74c3c" stroke-width="4" fill="none"/>
    <path id="letter-l" d="..." stroke="#e74c3c" stroke-width="4" fill="none"/>
    <path id="letter-o" d="..." stroke="#e74c3c" stroke-width="4" fill="none"/>
  </g>
</svg>
```

**Block Settings:**
- Animation Type: Draw SVG Paths
- Target Selector: `.letters path`
- Trigger: On Hover
- Simultaneous Paths: false (for stagger effect)
- Hover Target: Block Container

This creates a staggered drawing effect of the word "HELLO" when hovering over the block.
