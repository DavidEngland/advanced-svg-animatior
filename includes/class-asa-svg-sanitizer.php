<?php
/**
 * SVG Sanitizer Class for Advanced SVG Animator
 * 
 * Basic SVG sanitization when Composer library is not available
 * 
 * @package AdvancedSVGAnimator
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ASA SVG Sanitizer Class
 * 
 * Provides basic SVG sanitization functionality
 */
class ASA_SVG_Sanitizer {

    /**
     * Sanitize SVG content
     * 
     * @param string $svg_content The SVG content to sanitize
     * @return string|false Sanitized SVG content or false on failure
     */
    public static function sanitize($svg_content) {
        // Basic validation
        if (empty($svg_content) || !is_string($svg_content)) {
            return false;
        }

        // Remove potential malicious content
        $svg_content = self::remove_malicious_content($svg_content);
        
        // Validate SVG structure
        if (!self::is_valid_svg($svg_content)) {
            return false;
        }

        return $svg_content;
    }

    /**
     * Remove malicious content from SVG
     * 
     * @param string $svg_content
     * @return string
     */
    private static function remove_malicious_content($svg_content) {
        // Remove script tags
        $svg_content = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $svg_content);
        
        // Remove on* event handlers
        $svg_content = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $svg_content);
        
        // Remove javascript: URLs
        $svg_content = preg_replace('/javascript\s*:/i', '', $svg_content);
        
        // Remove data URLs that might contain scripts
        $svg_content = preg_replace('/data\s*:\s*[^;]*;[^,]*,/i', '', $svg_content);
        
        return $svg_content;
    }

    /**
     * Basic SVG validation
     * 
     * @param string $svg_content
     * @return bool
     */
    private static function is_valid_svg($svg_content) {
        // Check if it starts and ends with SVG tags
        $svg_content = trim($svg_content);
        
        // Must contain opening SVG tag
        if (strpos($svg_content, '<svg') === false) {
            return false;
        }
        
        // Must contain closing SVG tag
        if (strpos($svg_content, '</svg>') === false) {
            return false;
        }
        
        // Basic XML validation
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $valid = $dom->loadXML($svg_content);
        libxml_clear_errors();
        
        return $valid !== false;
    }
}
