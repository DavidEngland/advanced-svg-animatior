<?php
/**
 * SVG Sanitizer Configuration Examples
 * 
 * This file demonstrates how to configure the SVG sanitizer for different
 * animation use cases. These configurations can be applied in the plugin
 * or as part of a custom implementation.
 * 
 * @package AdvancedSVGAnimator
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Basic SVG Animation Configuration
 * 
 * Allows most common SVG elements and basic animation attributes
 */
function asa_get_basic_animation_config() {
    return array(
        'allowed_elements' => array(
            // Basic SVG shapes
            'svg', 'g', 'path', 'circle', 'ellipse', 'line', 'rect', 'polyline', 'polygon',
            
            // Text elements
            'text', 'tspan', 'textPath',
            
            // Container and grouping
            'defs', 'use', 'symbol', 'marker',
            
            // Basic animation elements
            'animate', 'animateTransform', 'set',
            
            // Metadata
            'title', 'desc'
        ),
        
        'allowed_attributes' => array(
            // Core attributes
            'id', 'class', 'style',
            
            // Basic styling
            'fill', 'stroke', 'stroke-width', 'opacity',
            
            // Position and size
            'x', 'y', 'width', 'height', 'cx', 'cy', 'r', 'rx', 'ry',
            
            // Path data
            'd', 'points',
            
            // Transform
            'transform',
            
            // ViewBox
            'viewBox', 'preserveAspectRatio',
            
            // Basic animation attributes
            'attributeName', 'begin', 'dur', 'from', 'to', 'values',
            'repeatCount', 'type'
        )
    );
}

/**
 * Advanced SVG Animation Configuration
 * 
 * Includes more complex animation features and SMIL attributes
 */
function asa_get_advanced_animation_config() {
    return array(
        'allowed_elements' => array(
            // All basic elements
            'svg', 'g', 'path', 'circle', 'ellipse', 'line', 'rect', 'polyline', 'polygon',
            'text', 'tspan', 'textPath', 'defs', 'use', 'symbol', 'marker', 'title', 'desc',
            
            // Advanced animation elements
            'animate', 'animateTransform', 'animateMotion', 'set',
            
            // Gradients and patterns
            'linearGradient', 'radialGradient', 'stop', 'pattern',
            
            // Clipping and masking
            'clipPath', 'mask',
            
            // Filters (limited set)
            'filter', 'feGaussianBlur', 'feOffset', 'feColorMatrix',
            
            // Additional elements
            'image', 'foreignObject', 'switch'
        ),
        
        'allowed_attributes' => array(
            // Core attributes
            'id', 'class', 'style',
            
            // Enhanced styling
            'fill', 'stroke', 'stroke-width', 'stroke-dasharray', 'stroke-dashoffset',
            'stroke-linecap', 'stroke-linejoin', 'fill-opacity', 'stroke-opacity',
            'opacity', 'fill-rule', 'clip-rule',
            
            // Position and dimensions
            'x', 'y', 'x1', 'y1', 'x2', 'y2', 'cx', 'cy', 'r', 'rx', 'ry',
            'width', 'height', 'dx', 'dy',
            
            // Path and shape data
            'd', 'points', 'pathLength',
            
            // Transform and positioning
            'transform', 'transform-origin',
            
            // ViewBox and aspect ratio
            'viewBox', 'preserveAspectRatio',
            
            // Text attributes
            'font-family', 'font-size', 'font-weight', 'font-style',
            'text-anchor', 'dominant-baseline', 'alignment-baseline',
            
            // Gradient attributes
            'gradientUnits', 'gradientTransform', 'fx', 'fy',
            'offset', 'stop-color', 'stop-opacity',
            
            // Advanced animation attributes (SMIL)
            'attributeName', 'attributeType', 'begin', 'dur', 'end',
            'min', 'max', 'restart', 'repeatCount', 'repeatDur',
            'fill', 'values', 'keyTimes', 'keySplines',
            'from', 'to', 'by', 'additive', 'accumulate', 'calcMode',
            'type', 'path', 'rotate', 'origin',
            
            // CSS Animation support (when used as attributes)
            'animation', 'animation-name', 'animation-duration',
            'animation-timing-function', 'animation-delay',
            'animation-iteration-count', 'animation-direction',
            'animation-fill-mode', 'animation-play-state',
            
            // Transition attributes
            'transition', 'transition-property', 'transition-duration',
            'transition-timing-function', 'transition-delay',
            
            // Clipping and masking
            'clip-path', 'mask',
            
            // Filter attributes
            'filter', 'stdDeviation', 'in', 'result',
            
            // Other useful attributes
            'vector-effect', 'shape-rendering', 'text-rendering',
            'color-rendering', 'image-rendering', 'visibility', 'display'
        )
    );
}

/**
 * Strict SVG Animation Configuration
 * 
 * Very limited set for high-security environments
 */
function asa_get_strict_animation_config() {
    return array(
        'allowed_elements' => array(
            'svg', 'g', 'path', 'circle', 'rect',
            'animate', 'animateTransform',
            'title', 'desc'
        ),
        
        'allowed_attributes' => array(
            'id', 'class',
            'fill', 'stroke', 'stroke-width', 'opacity',
            'x', 'y', 'width', 'height', 'cx', 'cy', 'r',
            'd', 'transform', 'viewBox',
            'attributeName', 'begin', 'dur', 'from', 'to',
            'repeatCount', 'type'
        )
    );
}

/**
 * Apply configuration to Enshrined SVG Sanitizer
 * 
 * @param \enshrined\svgSanitize\Sanitizer $sanitizer
 * @param array $config Configuration array
 */
function asa_apply_sanitizer_config($sanitizer, $config) {
    if (!($sanitizer instanceof \enshrined\svgSanitize\Sanitizer)) {
        return false;
    }
    
    try {
        // Note: The actual implementation depends on the library's API
        // This is a conceptual example - check library documentation
        
        if (isset($config['allowed_elements'])) {
            // The library may require a different format for setting allowed tags
            // This is a placeholder for the actual implementation
        }
        
        if (isset($config['allowed_attributes'])) {
            // Similar placeholder for allowed attributes
        }
        
        return true;
        
    } catch (Exception $e) {
        asa_log('Error applying sanitizer configuration: ' . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Example usage in a WordPress context
 */
function asa_example_usage() {
    /*
    // Get the configuration you want to use
    $config = asa_get_advanced_animation_config();
    
    // Create sanitizer instance (if library is available)
    if (class_exists('\enshrined\svgSanitize\Sanitizer')) {
        $sanitizer = new \enshrined\svgSanitize\Sanitizer();
        
        // Apply configuration
        asa_apply_sanitizer_config($sanitizer, $config);
        
        // Use the sanitizer
        $clean_svg = $sanitizer->sanitize($svg_content);
    }
    */
}

/**
 * Custom filter hook for modifying sanitizer configuration
 * 
 * Allows other plugins or themes to modify the SVG sanitization rules
 */
function asa_get_sanitizer_config($type = 'advanced') {
    switch ($type) {
        case 'basic':
            $config = asa_get_basic_animation_config();
            break;
        case 'strict':
            $config = asa_get_strict_animation_config();
            break;
        case 'advanced':
        default:
            $config = asa_get_advanced_animation_config();
            break;
    }
    
    // Allow filtering of the configuration
    return apply_filters('asa_svg_sanitizer_config', $config, $type);
}

/**
 * Validation helper for configuration
 * 
 * @param array $config Configuration to validate
 * @return bool True if valid
 */
function asa_validate_sanitizer_config($config) {
    if (!is_array($config)) {
        return false;
    }
    
    if (!isset($config['allowed_elements']) || !is_array($config['allowed_elements'])) {
        return false;
    }
    
    if (!isset($config['allowed_attributes']) || !is_array($config['allowed_attributes'])) {
        return false;
    }
    
    // Check that svg element is always included
    if (!in_array('svg', $config['allowed_elements'])) {
        return false;
    }
    
    return true;
}
