<?php
/**
 * Logging Functions for Advanced SVG Animator
 * 
 * @package AdvancedSVGAnimator
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Log messages for debugging and monitoring
 * 
 * @param string $message Log message
 * @param string $level Log level (info, warning, error)
 */
function asa_log($message, $level = 'info') {
    // Check if logging is enabled via plugin settings
    $logging_enabled = false;
    
    // Try to get setting from plugin options
    $plugin_options = get_option('asa_plugin_options', array());
    if (isset($plugin_options['debug_logging'])) {
        $logging_enabled = !empty($plugin_options['debug_logging']);
    }
    
    // Also check the ASA_DEBUG constant for backwards compatibility
    if (defined('ASA_DEBUG') && ASA_DEBUG) {
        $logging_enabled = true;
    }
    
    if (!$logging_enabled) {
        return;
    }
    
    $timestamp = current_time('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    
    $log_file = ASA_PLUGIN_DIR . 'logs/debug.log';
    
    // Create logs directory if it doesn't exist
    $log_dir = dirname($log_file);
    if (!file_exists($log_dir)) {
        wp_mkdir_p($log_dir);
    }
    
    // Write to log file
    error_log($log_entry, 3, $log_file);
    
    // Also log to WordPress debug log if available
    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        error_log("ASA Plugin: [{$level}] {$message}");
    }
}
