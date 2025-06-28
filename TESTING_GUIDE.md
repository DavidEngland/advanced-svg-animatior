# Testing the SVG Animator Block

## Quick Test Steps

### 1. Activate the Plugin
1. Go to **Plugins > Installed Plugins** in WordPress admin
2. Find "Advanced SVG Animator" and click **Activate**
3. Verify no errors appear

### 2. Configure Settings (Optional)
1. Go to **Settings > SVG Animator**
2. Check your user role is allowed to upload SVGs
3. Ensure "Enable SVG Sanitization" is checked
4. Save settings

### 3. Upload Test SVG
1. Go to **Media > Add New**
2. Upload one of the test SVGs from `/test-samples/` directory
3. Verify the SVG appears correctly in media library

### 4. Create Test Post
1. Go to **Posts > Add New**
2. Click **+** to add a block
3. Search for "SVG Animator" and add it
4. Select your uploaded SVG
5. Try different animations and settings

### 5. Test Frontend
1. Preview or publish the post
2. Check that animations work on the frontend
3. Test scroll-triggered animations
4. Verify responsive behavior

## Expected Behavior

### In Editor:
- ✅ Block appears in Media category
- ✅ Media selector only shows SVG files
- ✅ SVG renders inline immediately after selection
- ✅ Animation preview works in editor
- ✅ All controls update the preview
- ✅ Error messages appear for invalid files

### On Frontend:
- ✅ SVG displays correctly
- ✅ Animations trigger on scroll
- ✅ CSS custom properties are applied
- ✅ Responsive design works
- ✅ Accessibility features function

## Animation Test Matrix

| Animation | Expected Effect | Special Notes |
|-----------|----------------|---------------|
| Fade In | Opacity 0→1 | Basic test |
| Slide Up | Moves from below | Check transform |
| Slide Down | Moves from above | Check transform |
| Slide Left | Moves from right | Check transform |
| Slide Right | Moves from left | Check transform |
| Scale | Grows from 0→1 | Check transform-origin |
| Rotate | 360° rotation + fade | Combined effects |
| Bounce | Cubic-bezier bounce | Complex timing |
| Draw Line | Path stroke animation | SVG paths only |

## Browser Testing Checklist

- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile browsers

## Responsive Testing

- [ ] Desktop (1200px+)
- [ ] Tablet (768px-1199px)
- [ ] Mobile (320px-767px)

## Accessibility Testing

- [ ] Test with screen reader
- [ ] Test keyboard navigation
- [ ] Check with `prefers-reduced-motion: reduce`
- [ ] Verify high contrast mode
- [ ] Test focus indicators

## Performance Testing

- [ ] Multiple SVGs on same page
- [ ] Large SVG files (>100KB)
- [ ] Complex animations
- [ ] Scroll performance
- [ ] Memory usage

## Troubleshooting Common Issues

### Block Doesn't Appear
- Check WordPress version (5.0+ required)
- Verify plugin is activated
- Check console for JavaScript errors

### SVG Won't Upload
- Check user permissions in Settings > SVG Animator
- Verify file is valid SVG format
- Check file size limits

### Animation Not Working
- Check browser support for CSS animations
- Verify CSS files are loaded
- Test in incognito/private browsing

### Permission Errors
- Check user role settings
- Verify current user has upload_files capability
- Test with administrator account

## Debug Information

### Check Plugin Status
1. Go to **Tools > Site Health**
2. Look for any plugin-related issues
3. Check PHP error logs

### Enable Debug Mode
Add to `wp-config.php`:
```php
define('ASA_DEBUG', true);
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Check REST API
Test the SVG content endpoint:
```
GET /wp-json/advanced-svg-animator/v1/svg-content/{id}
```

## Sample Test Data

Use the provided test SVGs in `/test-samples/`:
- `simple-circle.svg` - Basic shapes
- `animated-logo.svg` - Complex paths
- `icon-set.svg` - Multiple elements
- `line-drawing.svg` - Perfect for Draw Line animation

## Report Issues

When reporting issues, include:
1. WordPress version
2. Browser and version
3. Console errors (if any)
4. Steps to reproduce
5. Expected vs actual behavior
6. SVG file used for testing

---

*This testing guide ensures the SVG Animator Block works correctly across all supported environments and use cases.*
