<?php
/**
 * Animation helper functions for Advanced SVG Animator
 * 
 * @package AdvancedSVGAnimator
 * @subpackage Functions
 */

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register animation presets
 * 
 * @return array Array of animation presets
 */
function asa_get_animation_presets() {
    $presets = array(
        'fade_in' => array(
            'name' => __('Fade In', 'advanced-svg-animator'),
            'type' => 'opacity',
            'duration' => 1000,
            'from' => 0,
            'to' => 1,
            'easing' => 'ease-in-out'
        ),
        'slide_in_left' => array(
            'name' => __('Slide In Left', 'advanced-svg-animator'),
            'type' => 'transform',
            'duration' => 800,
            'from' => 'translateX(-100%)',
            'to' => 'translateX(0)',
            'easing' => 'ease-out'
        ),
        'scale_up' => array(
            'name' => __('Scale Up', 'advanced-svg-animator'),
            'type' => 'transform',
            'duration' => 600,
            'from' => 'scale(0)',
            'to' => 'scale(1)',
            'easing' => 'elastic'
        ),
        'rotate_360' => array(
            'name' => __('Rotate 360°', 'advanced-svg-animator'),
            'type' => 'transform',
            'duration' => 2000,
            'from' => 'rotate(0deg)',
            'to' => 'rotate(360deg)',
            'easing' => 'linear',
            'loop' => true
        )
    );
    
    return apply_filters('asa_animation_presets', $presets);
}

/**
 * Generate CSS animation from preset
 * 
 * @param string $preset_id Preset identifier
 * @param array $options Override options
 * @return string CSS animation rules
 */
function asa_generate_animation_css($preset_id, $options = array()) {
    $presets = asa_get_animation_presets();
    
    if (!isset($presets[$preset_id])) {
        return '';
    }
    
    $preset = wp_parse_args($options, $presets[$preset_id]);
    
    $css = '';
    $animation_name = 'asa-' . $preset_id . '-' . uniqid();
    
    // Generate keyframes
    $css .= "@keyframes {$animation_name} {\n";
    $css .= "  0% {\n";
    
    if ($preset['type'] === 'opacity') {
        $css .= "    opacity: {$preset['from']};\n";
    } elseif ($preset['type'] === 'transform') {
        $css .= "    transform: {$preset['from']};\n";
    }
    
    $css .= "  }\n";
    $css .= "  100% {\n";
    
    if ($preset['type'] === 'opacity') {
        $css .= "    opacity: {$preset['to']};\n";
    } elseif ($preset['type'] === 'transform') {
        $css .= "    transform: {$preset['to']};\n";
    }
    
    $css .= "  }\n";
    $css .= "}\n\n";
    
    // Generate animation rule
    $duration = $preset['duration'] . 'ms';
    $easing = $preset['easing'];
    $iteration = isset($preset['loop']) && $preset['loop'] ? 'infinite' : '1';
    
    $css .= ".{$animation_name} {\n";
    $css .= "  animation: {$animation_name} {$duration} {$easing} {$iteration};\n";
    $css .= "}\n";
    
    return $css;
}

/**
 * Parse SVG and add animation classes
 * 
 * @param string $svg_content SVG content
 * @param array $animations Array of animations to apply
 * @return string Modified SVG content
 */
function asa_add_animations_to_svg($svg_content, $animations = array()) {
    if (empty($svg_content) || empty($animations)) {
        return $svg_content;
    }
    
    $dom = new DOMDocument();
    $dom->formatOutput = true;
    $dom->preserveWhiteSpace = false;
    
    libxml_use_internal_errors(true);
    
    if (!$dom->loadXML($svg_content)) {
        return $svg_content;
    }
    
    libxml_clear_errors();
    
    foreach ($animations as $animation) {
        if (!isset($animation['selector']) || !isset($animation['class'])) {
            continue;
        }
        
        $xpath = new DOMXPath($dom);
        $elements = $xpath->query($animation['selector']);
        
        foreach ($elements as $element) {
            $existing_class = $element->getAttribute('class');
            $new_class = trim($existing_class . ' ' . $animation['class']);
            $element->setAttribute('class', $new_class);
        }
    }
    
    return $dom->saveXML();
}

/**
 * Create timeline data structure
 * 
 * @param array $timeline_config Timeline configuration
 * @return array Formatted timeline data
 */
function asa_create_timeline($timeline_config) {
    $default_config = array(
        'duration' => 5000,
        'loop' => false,
        'autoplay' => false,
        'events' => array()
    );
    
    $config = wp_parse_args($timeline_config, $default_config);
    
    // Sort events by time
    usort($config['events'], function($a, $b) {
        return ($a['time'] ?? 0) - ($b['time'] ?? 0);
    });
    
    return $config;
}

/**
 * Validate animation configuration
 * 
 * @param array $config Animation configuration
 * @return array|WP_Error Valid configuration or error
 */
function asa_validate_animation_config($config) {
    $errors = array();
    
    // Required fields
    $required_fields = array('type', 'duration');
    foreach ($required_fields as $field) {
        if (!isset($config[$field])) {
            $errors[] = sprintf(__('Missing required field: %s', 'advanced-svg-animator'), $field);
        }
    }
    
    // Validate duration
    if (isset($config['duration']) && (!is_numeric($config['duration']) || $config['duration'] <= 0)) {
        $errors[] = __('Duration must be a positive number', 'advanced-svg-animator');
    }
    
    // Validate easing
    $valid_easings = array('linear', 'ease', 'ease-in', 'ease-out', 'ease-in-out', 'cubic-bezier');
    if (isset($config['easing'])) {
        $easing = $config['easing'];
        $is_valid = in_array($easing, $valid_easings) || strpos($easing, 'cubic-bezier(') === 0;
        
        if (!$is_valid) {
            $errors[] = __('Invalid easing function', 'advanced-svg-animator');
        }
    }
    
    if (!empty($errors)) {
        return new WP_Error('invalid_config', implode(', ', $errors));
    }
    
    return $config;
}
