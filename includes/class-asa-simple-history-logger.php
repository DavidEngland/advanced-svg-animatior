<?php
/**
 * Simple History Integration for Advanced SVG Animator
 * 
 * Provides logging integration with the Simple History plugin
 * to track SVG-related activities and security events.
 * 
 * @package Advanced_SVG_Animator
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Simple History Logger Class
 * 
 * Handles logging of SVG Animator events to Simple History plugin
 */
class ASA_Simple_History_Logger {

    /**
     * Check if Simple History plugin is active and available
     * 
     * @return bool True if Simple History is available
     */
    public static function is_simple_history_available() {
        return function_exists('simple_history_log') || 
               class_exists('SimpleHistory') ||
               did_action('simple_history_loaded');
    }

    /**
     * Log SVG file upload event
     * 
     * @param int $attachment_id Attachment ID
     * @param string $filename Original filename
     * @param array $file_data Additional file data
     */
    public static function log_svg_upload($attachment_id, $filename, $file_data = array()) {
        if (!self::is_simple_history_available()) {
            return;
        }

        $message = sprintf(
            __('SVG file "%s" uploaded via Advanced SVG Animator', 'advanced-svg-animator'),
            $filename
        );

        $context = array(
            'action' => 'svg_file_uploaded',
            'object_type' => 'SVG File',
            'object_name' => $filename,
            'object_id' => $attachment_id,
            'user_id' => get_current_user_id(),
            'description' => $message,
            'file_size' => isset($file_data['size']) ? $file_data['size'] : 0,
            'mime_type' => 'image/svg+xml',
            'plugin' => 'Advanced SVG Animator'
        );

        do_action('simple_history_log', $context);
    }

    /**
     * Log SVG security scan event
     * 
     * @param int $attachment_id Attachment ID
     * @param string $filename Filename
     * @param array $scan_results Scan results
     */
    public static function log_security_scan($attachment_id, $filename, $scan_results) {
        if (!self::is_simple_history_available()) {
            return;
        }

        $threat_level = isset($scan_results['threat_level']) ? $scan_results['threat_level'] : 'low';
        $threats_found = isset($scan_results['threats_found']) ? count($scan_results['threats_found']) : 0;

        $message = sprintf(
            __('SVG security scan completed for "%s" - Threat Level: %s (%d threats found)', 'advanced-svg-animator'),
            $filename,
            strtoupper($threat_level),
            $threats_found
        );

        $context = array(
            'action' => 'svg_security_scan',
            'object_type' => 'Security Scan',
            'object_name' => $filename,
            'object_id' => $attachment_id,
            'user_id' => get_current_user_id(),
            'description' => $message,
            'threat_level' => $threat_level,
            'threats_found' => $threats_found,
            'scan_status' => $threats_found > 0 ? 'threats_detected' : 'clean',
            'plugin' => 'Advanced SVG Animator'
        );

        do_action('simple_history_log', $context);
    }

    /**
     * Log SVG quarantine event
     * 
     * @param int $attachment_id Attachment ID
     * @param string $filename Filename
     * @param string $reason Quarantine reason
     */
    public static function log_svg_quarantine($attachment_id, $filename, $reason = '') {
        if (!self::is_simple_history_available()) {
            return;
        }

        $message = sprintf(
            __('SVG file "%s" quarantined for security reasons', 'advanced-svg-animator'),
            $filename
        );

        if ($reason) {
            $message .= ': ' . $reason;
        }

        $context = array(
            'action' => 'svg_file_quarantined',
            'object_type' => 'SVG File',
            'object_name' => $filename,
            'object_id' => $attachment_id,
            'user_id' => get_current_user_id(),
            'description' => $message,
            'quarantine_reason' => $reason,
            'security_action' => 'quarantine',
            'plugin' => 'Advanced SVG Animator'
        );

        do_action('simple_history_log', $context);
    }

    /**
     * Log SVG animation block usage
     * 
     * @param int $post_id Post ID where block was used
     * @param string $animation_type Animation type
     * @param array $block_data Block configuration data
     */
    public static function log_animation_block_usage($post_id, $animation_type, $block_data = array()) {
        if (!self::is_simple_history_available()) {
            return;
        }

        $post_title = get_the_title($post_id);
        $message = sprintf(
            __('SVG Animation block added to "%s" with %s animation', 'advanced-svg-animator'),
            $post_title,
            $animation_type
        );

        $context = array(
            'action' => 'svg_animation_block_added',
            'object_type' => 'Gutenberg Block',
            'object_name' => 'SVG Animator Block',
            'object_id' => $post_id,
            'user_id' => get_current_user_id(),
            'description' => $message,
            'animation_type' => $animation_type,
            'post_title' => $post_title,
            'plugin' => 'Advanced SVG Animator'
        );

        do_action('simple_history_log', $context);
    }

    /**
     * Log SVG file deletion event
     * 
     * @param int $attachment_id Attachment ID
     * @param string $filename Filename
     * @param string $reason Deletion reason
     */
    public static function log_svg_deletion($attachment_id, $filename, $reason = 'user_action') {
        if (!self::is_simple_history_available()) {
            return;
        }

        $message = sprintf(
            __('SVG file "%s" deleted', 'advanced-svg-animator'),
            $filename
        );

        $context = array(
            'action' => 'svg_file_deleted',
            'object_type' => 'SVG File',
            'object_name' => $filename,
            'object_id' => $attachment_id,
            'user_id' => get_current_user_id(),
            'description' => $message,
            'deletion_reason' => $reason,
            'plugin' => 'Advanced SVG Animator'
        );

        do_action('simple_history_log', $context);
    }

    /**
     * Log plugin activation/deactivation
     * 
     * @param string $action 'activated' or 'deactivated'
     */
    public static function log_plugin_status($action) {
        if (!self::is_simple_history_available()) {
            return;
        }

        $message = sprintf(
            __('Advanced SVG Animator plugin %s', 'advanced-svg-animator'),
            $action
        );

        $context = array(
            'action' => 'plugin_' . $action,
            'object_type' => 'Plugin',
            'object_name' => 'Advanced SVG Animator',
            'user_id' => get_current_user_id(),
            'description' => $message,
            'plugin_version' => ASA_VERSION,
            'plugin' => 'Advanced SVG Animator'
        );

        do_action('simple_history_log', $context);
    }

    /**
     * Log settings changes
     * 
     * @param array $old_settings Previous settings
     * @param array $new_settings New settings
     */
    public static function log_settings_change($old_settings, $new_settings) {
        if (!self::is_simple_history_available()) {
            return;
        }

        $changed_settings = array();
        foreach ($new_settings as $key => $value) {
            if (!isset($old_settings[$key]) || $old_settings[$key] !== $value) {
                $changed_settings[$key] = array(
                    'old' => isset($old_settings[$key]) ? $old_settings[$key] : null,
                    'new' => $value
                );
            }
        }

        if (empty($changed_settings)) {
            return;
        }

        $message = sprintf(
            __('Advanced SVG Animator settings updated (%d changes)', 'advanced-svg-animator'),
            count($changed_settings)
        );

        $context = array(
            'action' => 'settings_updated',
            'object_type' => 'Settings',
            'object_name' => 'Advanced SVG Animator Settings',
            'user_id' => get_current_user_id(),
            'description' => $message,
            'changed_settings' => $changed_settings,
            'plugin' => 'Advanced SVG Animator'
        );

        do_action('simple_history_log', $context);
    }

    /**
     * Log bulk scan operations
     * 
     * @param array $scan_summary Summary of bulk scan results
     */
    public static function log_bulk_scan($scan_summary) {
        if (!self::is_simple_history_available()) {
            return;
        }

        $total_files = isset($scan_summary['total_files']) ? $scan_summary['total_files'] : 0;
        $threats_found = isset($scan_summary['total_threats']) ? $scan_summary['total_threats'] : 0;

        $message = sprintf(
            __('Bulk SVG security scan completed: %d files scanned, %d threats found', 'advanced-svg-animator'),
            $total_files,
            $threats_found
        );

        $context = array(
            'action' => 'bulk_security_scan',
            'object_type' => 'Security Scan',
            'object_name' => 'Bulk SVG Scan',
            'user_id' => get_current_user_id(),
            'description' => $message,
            'total_files' => $total_files,
            'threats_found' => $threats_found,
            'scan_duration' => isset($scan_summary['duration']) ? $scan_summary['duration'] : 0,
            'plugin' => 'Advanced SVG Animator'
        );

        do_action('simple_history_log', $context);
    }
}
