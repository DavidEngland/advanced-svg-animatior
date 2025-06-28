<?php
/**
 * Core utility functions for Advanced SVG Animator
 * 
 * @package AdvancedSVGAnimator
 * @subpackage Functions
 */

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get plugin option with default fallback
 * 
 * @param string $key Option key
 * @param mixed $default Default value if option doesn't exist
 * @return mixed Option value or default
 */
function asa_get_option($key, $default = null) {
    $options = get_option('asa_plugin_options', array());
    return isset($options[$key]) ? $options[$key] : $default;
}

/**
 * Update plugin option
 * 
 * @param string $key Option key
 * @param mixed $value Option value
 * @return bool True on success, false on failure
 */
function asa_update_option($key, $value) {
    $options = get_option('asa_plugin_options', array());
    $options[$key] = $value;
    return update_option('asa_plugin_options', $options);
}

/**
 * Check if SVG uploads are enabled
 * 
 * @return bool True if enabled, false otherwise
 */
function asa_is_svg_upload_enabled() {
    return asa_get_option('enable_svg_uploads', false);
}

/**
 * Get the required capability for SVG uploads
 * 
 * @return string Capability required for SVG uploads
 */
function asa_get_svg_upload_capability() {
    return asa_get_option('svg_upload_capability', 'manage_options');
}

/**
 * Check if current user can upload SVGs
 * 
 * @return bool True if user can upload SVGs, false otherwise
 */
function asa_current_user_can_upload_svgs() {
    $capability = asa_get_svg_upload_capability();
    return current_user_can($capability);
}

/**
 * Sanitize SVG content
 * 
 * @param string $svg_content SVG content to sanitize
 * @return string|false Sanitized SVG content or false on failure
 */
function asa_sanitize_svg_content($svg_content) {
    if (empty($svg_content)) {
        return false;
    }

    // Create DOMDocument instance
    $dom = new DOMDocument();
    $dom->formatOutput = false;
    $dom->preserveWhiteSpace = true;
    
    // Suppress errors for malformed HTML/XML
    libxml_use_internal_errors(true);
    
    // Load SVG content
    if (!$dom->loadXML($svg_content)) {
        return false;
    }
    
    // Clear libxml errors
    libxml_clear_errors();
    
    // Remove dangerous elements and attributes
    $dangerous_elements = array('script', 'iframe', 'object', 'embed', 'link', 'meta');
    $dangerous_attributes = array('onload', 'onclick', 'onmouseover', 'onerror');
    
    $xpath = new DOMXPath($dom);
    
    // Remove dangerous elements
    foreach ($dangerous_elements as $element) {
        $nodes = $xpath->query('//' . $element);
        foreach ($nodes as $node) {
            $node->parentNode->removeChild($node);
        }
    }
    
    // Remove dangerous attributes from all elements
    $all_elements = $xpath->query('//*');
    foreach ($all_elements as $element) {
        foreach ($dangerous_attributes as $attr) {
            if ($element->hasAttribute($attr)) {
                $element->removeAttribute($attr);
            }
        }
        
        // Check for javascript: in href attributes
        if ($element->hasAttribute('href')) {
            $href = $element->getAttribute('href');
            if (stripos($href, 'javascript:') === 0) {
                $element->removeAttribute('href');
            }
        }
        
        // Check for javascript: in xlink:href attributes
        if ($element->hasAttribute('xlink:href')) {
            $href = $element->getAttribute('xlink:href');
            if (stripos($href, 'javascript:') === 0) {
                $element->removeAttribute('xlink:href');
            }
        }
    }
    
    return $dom->saveXML();
}

/**
 * Log plugin events for debugging
 * 
 * @param string $message Log message
 * @param string $level Log level (debug, info, warning, error)
 */
function asa_log($message, $level = 'info') {
    if (!asa_get_option('enable_logging', false)) {
        return;
    }
    
    if (function_exists('error_log')) {
        $formatted_message = sprintf(
            '[ASA %s] %s: %s',
            strtoupper($level),
            current_time('Y-m-d H:i:s'),
            $message
        );
        error_log($formatted_message);
    }
}

/**
 * Get SVG file dimensions
 * 
 * @param string $file_path Path to SVG file
 * @return array|false Array with width and height or false on failure
 */
function asa_get_svg_dimensions($file_path) {
    if (!file_exists($file_path)) {
        return false;
    }
    
    $svg_content = file_get_contents($file_path);
    if (!$svg_content) {
        return false;
    }
    
    // Try to extract dimensions from SVG
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    
    if (!$dom->loadXML($svg_content)) {
        return false;
    }
    
    $svg = $dom->documentElement;
    
    $width = $svg->getAttribute('width');
    $height = $svg->getAttribute('height');
    
    // If width/height attributes don't exist, try viewBox
    if (empty($width) || empty($height)) {
        $viewBox = $svg->getAttribute('viewBox');
        if ($viewBox) {
            $values = explode(' ', $viewBox);
            if (count($values) >= 4) {
                $width = $values[2];
                $height = $values[3];
            }
        }
    }
    
    // Convert to numeric values
    $width = is_numeric($width) ? (float)$width : 0;
    $height = is_numeric($height) ? (float)$height : 0;
    
    return array(
        'width' => $width,
        'height' => $height
    );
}

/**
 * Check if file is a valid SVG
 * 
 * @param string $file_path Path to file
 * @return bool True if valid SVG, false otherwise
 */
function asa_is_valid_svg($file_path) {
    if (!file_exists($file_path)) {
        return false;
    }
    
    $content = file_get_contents($file_path);
    if (!$content) {
        return false;
    }
    
    // Check if content contains SVG elements
    if (strpos($content, '<svg') === false) {
        return false;
    }
    
    // Try to parse as XML
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $result = $dom->loadXML($content);
    libxml_clear_errors();
    
    return $result !== false;
}
