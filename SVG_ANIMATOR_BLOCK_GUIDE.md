# SVG Animator Block User Guide

## How to Use the SVG Animator Block

The **SVG Animator Block** allows you to select SVG files from your WordPress media library and add beautiful animations to them directly in the Gutenberg editor.

### Step-by-Step Usage:

#### 1. **Add the Block**
- In the WordPress editor, click the `+` button to add a new block
- Search for "SVG Animator" or find it in the "Media" category
- Click to add the block to your page/post

#### 2. **Select Your SVG**
- Click the "Select SVG" button in the placeholder
- Choose an SVG file from your media library
- If you don't have SVG files uploaded, upload one first
- The SVG will appear in the editor with a preview

#### 3. **Configure Animations**
In the **Block Settings Panel** (right sidebar), you'll find these options:

##### **Animation Settings:**
- **Animation Type**: Choose from multiple animation types:
  - `fadeIn` - Fade in effect
  - `slideUp/Down/Left/Right` - Slide animations
  - `scale` - Scaling effect
  - `rotate` - Rotation animation
  - `bounce` - Bouncing effect
  - `drawLine` - Line drawing effect
  - `drawSVGPaths` - Path drawing animation
  - `morphPath` - Path morphing

##### **Timing Controls:**
- **Duration**: How long the animation takes (0.1-10 seconds)
- **Delay**: Delay before animation starts (0-5 seconds)
- **Iteration Count**: How many times to repeat (1, 2, 3, or "infinite")
- **Timing Function**: Animation easing (ease, linear, ease-in-out, etc.)

##### **Animation Triggers:**
- **onLoad**: Animate when page loads (default)
- **onScroll**: Animate when scrolled into view
- **onHover**: Animate on mouse hover
- **onClick**: Animate when clicked

##### **Advanced Options:**
- **Target Selector**: Animate specific SVG elements (e.g., `#my-path`, `.my-group`)
- **Custom CSS Class**: Add custom CSS classes
- **Width/Height**: Control SVG dimensions
- **Alignment**: Left, center, right alignment

#### 4. **Preview Animation**
- Use the "Preview Animation" button in the settings to test your animation
- The animation will play in the editor so you can see how it looks

#### 5. **Publish**
- Once satisfied with your settings, publish or update your page/post
- The animated SVG will appear on the frontend with your chosen animations

### Example Use Cases:

#### **Real Estate Website:**
1. Upload your real estate icons (house, key, location pin)
2. Add SVG Animator blocks for each icon
3. Set animations:
   - House: `scale` animation with `onScroll` trigger
   - Key: `rotate` animation with `infinite` iterations
   - Location pin: `bounce` animation with `onHover` trigger

#### **Logo Animation:**
1. Upload your company logo as SVG
2. Add fadeIn animation with 2-second duration
3. Set trigger to `onLoad` for immediate animation

#### **Icon Animations:**
1. Upload icon sets as individual SVG files
2. Use `slideUp` animations with staggered delays (0s, 0.2s, 0.4s)
3. Set to animate `onScroll` for progressive revelation

### Troubleshooting:

**Block not appearing:**
- Make sure the plugin is activated
- Check that you're using WordPress 5.0+ with Gutenberg

**SVG not loading:**
- Ensure the file is actually an SVG (has `.svg` extension)
- Check that SVG uploads are enabled in WordPress
- Verify file permissions

**Animation not working:**
- Check that animation type is not set to "none"
- Verify your browser supports CSS animations
- Test with simple animations first (fadeIn, scale)

**Performance issues:**
- Avoid too many animated SVGs on one page
- Use shorter durations for better performance
- Consider using `onScroll` or `onHover` triggers instead of `onLoad`

### Technical Notes:

- The block uses CSS animations for optimal performance
- SVG content is fetched via WordPress REST API
- All animations respect user's `prefers-reduced-motion` setting for accessibility
- The block supports WordPress alignment controls and spacing options

### Advanced Features:

**Target Selector Examples:**
- `path` - Animate all paths in the SVG
- `#myElement` - Animate element with specific ID
- `.myClass` - Animate elements with specific class
- `g.icon` - Animate groups with "icon" class

**Custom CSS Integration:**
- Add custom CSS classes for additional styling
- Use CSS custom properties for dynamic animations
- Combine with theme styles for consistent design

This block provides a powerful, user-friendly way to add engaging SVG animations to your WordPress site without needing to write code!
