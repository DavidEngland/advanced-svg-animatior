<?php
/**
 * SVG Security Scanner Admin Interface
 * 
 * Provides WordPress admin interface for SVG security scanning
 * 
 * @package Advanced_SVG_Animator
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SVG Security Scanner Admin Class
 */
class ASA_SVG_Scanner_Admin {

    /**
     * Scanner instance
     */
    private $scanner;

    /**
     * Constructor
     */
    public function __construct() {
        // Load scanner class
        require_once ASA_INCLUDES_DIR . 'class-asa-svg-security-scanner.php';
        $this->scanner = new ASA_SVG_Security_Scanner();

        // Add admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_asa_scan_svg_library', array($this, 'ajax_scan_library'));
        add_action('wp_ajax_asa_scan_single_svg', array($this, 'ajax_scan_single'));
        add_action('wp_ajax_asa_quarantine_svg', array($this, 'ajax_quarantine_file'));
        add_action('wp_ajax_asa_delete_svg', array($this, 'ajax_delete_file'));
        add_action('wp_ajax_asa_get_scan_results', array($this, 'ajax_get_scan_results'));
        add_action('wp_ajax_asa_batch_action', array($this, 'ajax_batch_action'));

        // Add notice for scan results
        add_action('admin_notices', array($this, 'display_scan_notices'));
        
        // Handle scheduled scan settings
        add_action('admin_init', array($this, 'handle_scheduled_scan_settings'));
    }

    /**
     * Add admin menu for SVG scanner
     */
    public function add_admin_menu() {
        add_management_page(
            __('SVG Security Scanner', ASA_TEXT_DOMAIN),
            __('SVG Security', ASA_TEXT_DOMAIN),
            'manage_options',
            'asa-svg-scanner',
            array($this, 'render_scanner_page')
        );

        // Add submenu under Media if preferred
        add_submenu_page(
            'upload.php',
            __('SVG Security Scanner', ASA_TEXT_DOMAIN),
            __('SVG Security', ASA_TEXT_DOMAIN),
            'manage_options',
            'asa-svg-scanner-media',
            array($this, 'render_scanner_page')
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (!in_array($hook, array('tools_page_asa-svg-scanner', 'media_page_asa-svg-scanner-media'))) {
            return;
        }

        wp_enqueue_script(
            'asa-svg-scanner-admin',
            ASA_PLUGIN_URL . 'assets/js/svg-scanner-admin.js',
            array('jquery', 'wp-util'),
            ASA_VERSION,
            true
        );

        wp_enqueue_style(
            'asa-svg-scanner-admin',
            ASA_PLUGIN_URL . 'assets/css/svg-scanner-admin.css',
            array(),
            ASA_VERSION
        );

        wp_localize_script('asa-svg-scanner-admin', 'asaScannerAjax', array(
            'nonce' => wp_create_nonce('asa_svg_scanner'),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'strings' => array(
                'scanning' => __('Scanning...', ASA_TEXT_DOMAIN),
                'scan_complete' => __('Scan Complete', ASA_TEXT_DOMAIN),
                'scan_error' => __('Scan Error', ASA_TEXT_DOMAIN),
                'confirm_quarantine' => __('Are you sure you want to quarantine this file?', ASA_TEXT_DOMAIN),
                'confirm_delete' => __('Are you sure you want to permanently delete this file? This cannot be undone.', ASA_TEXT_DOMAIN),
                'confirm_batch_quarantine' => __('Are you sure you want to quarantine the selected files?', ASA_TEXT_DOMAIN),
                'confirm_batch_delete' => __('Are you sure you want to permanently delete the selected files? This cannot be undone.', ASA_TEXT_DOMAIN),
                'no_items_selected' => __('Please select at least one item.', ASA_TEXT_DOMAIN),
                'batch_processing' => __('Processing batch action...', ASA_TEXT_DOMAIN),
            )
        ));
    }

    /**
     * Handle scheduled scan settings form submission
     */
    public function handle_scheduled_scan_settings() {
        if (isset($_POST['save_scheduled_settings']) && wp_verify_nonce($_POST['asa_scheduled_scan_nonce'], 'asa_scheduled_scan_settings')) {
            if (!current_user_can('manage_options')) {
                return;
            }

            $options = array(
                'enable_scheduled_scans' => isset($_POST['enable_scheduled_scans']),
                'scan_frequency' => sanitize_text_field($_POST['scan_frequency']),
                'email_notifications' => isset($_POST['email_notifications']),
                'notification_threshold' => sanitize_text_field($_POST['notification_threshold'])
            );

            update_option('asa_scheduled_scan_options', $options);

            // Clear existing scheduled scans
            wp_clear_scheduled_hook('asa_scheduled_svg_scan');

            // Schedule new scan if enabled
            if ($options['enable_scheduled_scans']) {
                $this->schedule_recurring_scan($options['scan_frequency']);
            }

            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . 
                     __('Scheduled scan settings saved successfully.', ASA_TEXT_DOMAIN) . '</p></div>';
            });
        }
    }

    /**
     * Schedule recurring scan based on frequency
     */
    private function schedule_recurring_scan($frequency) {
        $schedules = array(
            'daily' => 'daily',
            'weekly' => 'weekly', 
            'monthly' => 'monthly'
        );

        if (isset($schedules[$frequency])) {
            wp_schedule_event(time(), $schedules[$frequency], 'asa_scheduled_svg_scan');
        }
    }

    /**
     * Render main scanner page
     */
    public function render_scanner_page() {
        $stats = $this->scanner->get_scan_statistics();
        $last_scan = get_option('asa_last_svg_scan_summary', array());
        ?>
        <div class="wrap">
            <h1><?php _e('SVG Security Scanner', ASA_TEXT_DOMAIN); ?></h1>
            
            <div class="asa-scanner-dashboard">
                <!-- Scanner Statistics -->
                <div class="asa-scanner-stats">
                    <h2><?php _e('Scanner Statistics', ASA_TEXT_DOMAIN); ?></h2>
                    <div class="asa-stats-grid">
                        <div class="asa-stat-card">
                            <h3><?php _e('Total Scans', ASA_TEXT_DOMAIN); ?></h3>
                            <span class="asa-stat-number"><?php echo esc_html($stats['total_scans'] ?? 0); ?></span>
                        </div>
                        
                        <div class="asa-stat-card critical">
                            <h3><?php _e('Critical Threats', ASA_TEXT_DOMAIN); ?></h3>
                            <span class="asa-stat-number"><?php echo esc_html($stats['threat_levels']['critical'] ?? 0); ?></span>
                        </div>
                        
                        <div class="asa-stat-card high">
                            <h3><?php _e('High Risk', ASA_TEXT_DOMAIN); ?></h3>
                            <span class="asa-stat-number"><?php echo esc_html($stats['threat_levels']['high'] ?? 0); ?></span>
                        </div>
                        
                        <div class="asa-stat-card medium">
                            <h3><?php _e('Medium Risk', ASA_TEXT_DOMAIN); ?></h3>
                            <span class="asa-stat-number"><?php echo esc_html($stats['threat_levels']['medium'] ?? 0); ?></span>
                        </div>

                        <div class="asa-stat-card quarantined">
                            <h3><?php _e('Quarantined', ASA_TEXT_DOMAIN); ?></h3>
                            <span class="asa-stat-number"><?php echo esc_html($stats['statuses']['quarantined'] ?? 0); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Last Scan Summary -->
                <?php if (!empty($last_scan)): ?>
                <div class="asa-last-scan">
                    <h2><?php _e('Last Scan Summary', ASA_TEXT_DOMAIN); ?></h2>
                    <p>
                        <?php printf(
                            __('Scanned %d files in %s seconds on %s. Found %d threats.', ASA_TEXT_DOMAIN),
                            esc_html($last_scan['total_scanned']),
                            esc_html($last_scan['scan_duration']),
                            esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_scan['scan_start_time']))),
                            esc_html($last_scan['threats_found'])
                        ); ?>
                    </p>
                </div>
                <?php endif; ?>

                <!-- Scan Controls -->
                <div class="asa-scan-controls">
                    <h2><?php _e('Scan Controls', ASA_TEXT_DOMAIN); ?></h2>
                    
                    <div class="asa-scan-buttons">
                        <button type="button" class="button button-primary" id="asa-scan-library">
                            <?php _e('Scan All SVG Files', ASA_TEXT_DOMAIN); ?>
                        </button>
                        
                        <button type="button" class="button" id="asa-scan-library-force">
                            <?php _e('Force Rescan All', ASA_TEXT_DOMAIN); ?>
                        </button>
                    </div>

                    <div class="asa-scan-options">
                        <label>
                            <input type="checkbox" id="asa-scan-include-quarantined" />
                            <?php _e('Include quarantined files', ASA_TEXT_DOMAIN); ?>
                        </label>
                    </div>

                    <div id="asa-scan-progress" class="asa-scan-progress" style="display: none;">
                        <div class="asa-progress-bar">
                            <div class="asa-progress-fill"></div>
                        </div>
                        <div class="asa-progress-text"></div>
                    </div>
                </div>

                <!-- Scheduled Scan Settings -->
                <div class="asa-scheduled-scans">
                    <h2><?php _e('Scheduled Scanning', ASA_TEXT_DOMAIN); ?></h2>
                    
                    <?php $scheduled_options = get_option('asa_scheduled_scan_options', array()); ?>
                    <form method="post" action="" id="asa-scheduled-scan-form">
                        <?php wp_nonce_field('asa_scheduled_scan_settings', 'asa_scheduled_scan_nonce'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Enable Scheduled Scans', ASA_TEXT_DOMAIN); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="enable_scheduled_scans" value="1" 
                                               <?php checked(isset($scheduled_options['enable_scheduled_scans']) && $scheduled_options['enable_scheduled_scans']); ?> />
                                        <?php _e('Automatically scan SVG files on a schedule', ASA_TEXT_DOMAIN); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Scan Frequency', ASA_TEXT_DOMAIN); ?></th>
                                <td>
                                    <select name="scan_frequency">
                                        <option value="daily" <?php selected($scheduled_options['scan_frequency'] ?? 'daily', 'daily'); ?>>
                                            <?php _e('Daily', ASA_TEXT_DOMAIN); ?>
                                        </option>
                                        <option value="weekly" <?php selected($scheduled_options['scan_frequency'] ?? 'daily', 'weekly'); ?>>
                                            <?php _e('Weekly', ASA_TEXT_DOMAIN); ?>
                                        </option>
                                        <option value="monthly" <?php selected($scheduled_options['scan_frequency'] ?? 'daily', 'monthly'); ?>>
                                            <?php _e('Monthly', ASA_TEXT_DOMAIN); ?>
                                        </option>
                                    </select>
                                    <p class="description">
                                        <?php _e('How often to automatically scan for new threats', ASA_TEXT_DOMAIN); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Email Notifications', ASA_TEXT_DOMAIN); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="email_notifications" value="1" 
                                               <?php checked(isset($scheduled_options['email_notifications']) && $scheduled_options['email_notifications']); ?> />
                                        <?php _e('Send email notifications when threats are found', ASA_TEXT_DOMAIN); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Notification Threshold', ASA_TEXT_DOMAIN); ?></th>
                                <td>
                                    <select name="notification_threshold">
                                        <option value="critical" <?php selected($scheduled_options['notification_threshold'] ?? 'high', 'critical'); ?>>
                                            <?php _e('Critical threats only', ASA_TEXT_DOMAIN); ?>
                                        </option>
                                        <option value="high" <?php selected($scheduled_options['notification_threshold'] ?? 'high', 'high'); ?>>
                                            <?php _e('High and Critical threats', ASA_TEXT_DOMAIN); ?>
                                        </option>
                                        <option value="medium" <?php selected($scheduled_options['notification_threshold'] ?? 'high', 'medium'); ?>>
                                            <?php _e('Medium, High and Critical threats', ASA_TEXT_DOMAIN); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        
                        <div class="asa-schedule-info">
                            <?php 
                            $next_scan = wp_next_scheduled('asa_scheduled_svg_scan');
                            if ($next_scan): ?>
                                <p><strong><?php _e('Next scheduled scan:', ASA_TEXT_DOMAIN); ?></strong> 
                                   <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_scan); ?>
                                </p>
                            <?php else: ?>
                                <p><?php _e('No scans currently scheduled.', ASA_TEXT_DOMAIN); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary" name="save_scheduled_settings">
                                <?php _e('Save Scheduled Scan Settings', ASA_TEXT_DOMAIN); ?>
                            </button>
                        </p>
                    </form>
                </div>

                <!-- Scan Results -->
                <div class="asa-scan-results">
                    <h2><?php _e('Scan Results', ASA_TEXT_DOMAIN); ?></h2>
                    
                    <div class="asa-results-filters">
                        <select id="asa-filter-threat-level">
                            <option value=""><?php _e('All Threat Levels', ASA_TEXT_DOMAIN); ?></option>
                            <option value="critical"><?php _e('Critical', ASA_TEXT_DOMAIN); ?></option>
                            <option value="high"><?php _e('High', ASA_TEXT_DOMAIN); ?></option>
                            <option value="medium"><?php _e('Medium', ASA_TEXT_DOMAIN); ?></option>
                            <option value="low"><?php _e('Low', ASA_TEXT_DOMAIN); ?></option>
                        </select>
                        
                        <select id="asa-filter-status">
                            <option value=""><?php _e('All Statuses', ASA_TEXT_DOMAIN); ?></option>
                            <option value="active"><?php _e('Active', ASA_TEXT_DOMAIN); ?></option>
                            <option value="quarantined"><?php _e('Quarantined', ASA_TEXT_DOMAIN); ?></option>
                            <option value="deleted"><?php _e('Deleted', ASA_TEXT_DOMAIN); ?></option>
                        </select>
                        
                        <button type="button" class="button" id="asa-load-results">
                            <?php _e('Load Results', ASA_TEXT_DOMAIN); ?>
                        </button>
                    </div>

                    <!-- Batch Actions -->
                    <div class="asa-batch-actions" id="asa-batch-actions" style="display: none;">
                        <div class="asa-batch-controls">
                            <span class="asa-selected-count">
                                <span id="asa-selected-count">0</span> <?php _e('items selected', ASA_TEXT_DOMAIN); ?>
                            </span>
                            
                            <select id="asa-batch-action">
                                <option value=""><?php _e('Bulk Actions', ASA_TEXT_DOMAIN); ?></option>
                                <option value="quarantine"><?php _e('Quarantine Selected', ASA_TEXT_DOMAIN); ?></option>
                                <option value="delete"><?php _e('Delete Selected', ASA_TEXT_DOMAIN); ?></option>
                                <option value="rescan"><?php _e('Rescan Selected', ASA_TEXT_DOMAIN); ?></option>
                            </select>
                            
                            <button type="button" class="button" id="asa-apply-batch-action">
                                <?php _e('Apply', ASA_TEXT_DOMAIN); ?>
                            </button>
                            
                            <button type="button" class="button" id="asa-clear-selection">
                                <?php _e('Clear Selection', ASA_TEXT_DOMAIN); ?>
                            </button>
                        </div>
                    </div>

                    <div id="asa-results-container">
                        <!-- Results will be loaded here via AJAX -->
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX handler for scanning entire library
     */
    public function ajax_scan_library() {
        check_ajax_referer('asa_svg_scanner', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', ASA_TEXT_DOMAIN));
        }

        $force_rescan = isset($_POST['force_rescan']) && $_POST['force_rescan'] === 'true';
        $include_quarantined = isset($_POST['include_quarantined']) && $_POST['include_quarantined'] === 'true';

        $options = array(
            'force_rescan' => $force_rescan,
            'include_quarantined' => $include_quarantined
        );

        try {
            $scan_results = $this->scanner->scan_media_library($options);
            wp_send_json_success($scan_results);
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => sprintf(__('Scan failed: %s', ASA_TEXT_DOMAIN), $e->getMessage())
            ));
        }
    }

    /**
     * AJAX handler for scanning single file
     */
    public function ajax_scan_single() {
        check_ajax_referer('asa_svg_scanner', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', ASA_TEXT_DOMAIN));
        }

        $attachment_id = intval($_POST['attachment_id']);
        $force_rescan = isset($_POST['force_rescan']) && $_POST['force_rescan'] === 'true';

        if (!$attachment_id) {
            wp_send_json_error(array('message' => __('Invalid attachment ID', ASA_TEXT_DOMAIN)));
        }

        try {
            $scan_result = $this->scanner->scan_svg_file($attachment_id, $force_rescan);
            
            if ($scan_result) {
                wp_send_json_success($scan_result);
            } else {
                wp_send_json_error(array('message' => __('Failed to scan file', ASA_TEXT_DOMAIN)));
            }
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => sprintf(__('Scan failed: %s', ASA_TEXT_DOMAIN), $e->getMessage())
            ));
        }
    }

    /**
     * AJAX handler for quarantining file
     */
    public function ajax_quarantine_file() {
        check_ajax_referer('asa_svg_scanner', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', ASA_TEXT_DOMAIN));
        }

        $attachment_id = intval($_POST['attachment_id']);

        if (!$attachment_id) {
            wp_send_json_error(array('message' => __('Invalid attachment ID', ASA_TEXT_DOMAIN)));
        }

        try {
            $result = $this->scanner->quarantine_file($attachment_id);
            
            if ($result) {
                wp_send_json_success(array(
                    'message' => __('File successfully quarantined', ASA_TEXT_DOMAIN)
                ));
            } else {
                wp_send_json_error(array('message' => __('Failed to quarantine file', ASA_TEXT_DOMAIN)));
            }
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => sprintf(__('Quarantine failed: %s', ASA_TEXT_DOMAIN), $e->getMessage())
            ));
        }
    }

    /**
     * AJAX handler for deleting file
     */
    public function ajax_delete_file() {
        check_ajax_referer('asa_svg_scanner', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', ASA_TEXT_DOMAIN));
        }

        $attachment_id = intval($_POST['attachment_id']);

        if (!$attachment_id) {
            wp_send_json_error(array('message' => __('Invalid attachment ID', ASA_TEXT_DOMAIN)));
        }

        try {
            $result = $this->scanner->delete_file($attachment_id);
            
            if ($result) {
                wp_send_json_success(array(
                    'message' => __('File successfully deleted', ASA_TEXT_DOMAIN)
                ));
            } else {
                wp_send_json_error(array('message' => __('Failed to delete file', ASA_TEXT_DOMAIN)));
            }
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => sprintf(__('Delete failed: %s', ASA_TEXT_DOMAIN), $e->getMessage())
            ));
        }
    }

    /**
     * AJAX handler for batch actions
     */
    public function ajax_batch_action() {
        check_ajax_referer('asa_svg_scanner', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', ASA_TEXT_DOMAIN));
        }

        $action = sanitize_text_field($_POST['batch_action']);
        $attachment_ids = array_map('intval', $_POST['attachment_ids']);

        if (empty($attachment_ids)) {
            wp_send_json_error(array('message' => __('No files selected', ASA_TEXT_DOMAIN)));
        }

        $results = array(
            'success' => 0,
            'failed' => 0,
            'messages' => array()
        );

        foreach ($attachment_ids as $attachment_id) {
            try {
                switch ($action) {
                    case 'quarantine':
                        $result = $this->scanner->quarantine_file($attachment_id);
                        break;
                    case 'delete':
                        $result = $this->scanner->delete_file($attachment_id);
                        break;
                    case 'rescan':
                        $result = $this->scanner->scan_svg_file($attachment_id, true);
                        break;
                    default:
                        $result = false;
                }

                if ($result) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                }
            } catch (Exception $e) {
                $results['failed']++;
                $results['messages'][] = sprintf(__('Failed to %s file ID %d: %s', ASA_TEXT_DOMAIN), $action, $attachment_id, $e->getMessage());
            }
        }

        $message = sprintf(
            __('Batch action completed: %d successful, %d failed', ASA_TEXT_DOMAIN),
            $results['success'],
            $results['failed']
        );

        wp_send_json_success(array(
            'message' => $message,
            'results' => $results
        ));
    }

    /**
     * AJAX handler for getting scan results
     */
    public function ajax_get_scan_results() {
        check_ajax_referer('asa_svg_scanner', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', ASA_TEXT_DOMAIN));
        }

        $filters = array(
            'threat_level' => sanitize_text_field($_POST['threat_level'] ?? ''),
            'status' => sanitize_text_field($_POST['status'] ?? ''),
            'limit' => intval($_POST['limit'] ?? 50),
            'offset' => intval($_POST['offset'] ?? 0)
        );

        try {
            $results = $this->scanner->get_scan_results($filters);
            $html = $this->render_results_table($results);
            
            wp_send_json_success(array(
                'html' => $html,
                'count' => count($results)
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => sprintf(__('Failed to load results: %s', ASA_TEXT_DOMAIN), $e->getMessage())
            ));
        }
    }

    /**
     * Render results table HTML
     * 
     * @param array $results Scan results
     * @return string HTML table
     */
    private function render_results_table($results) {
        if (empty($results)) {
            return '<p>' . __('No scan results found.', ASA_TEXT_DOMAIN) . '</p>';
        }

        ob_start();
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-1"><?php _e('Select All', ASA_TEXT_DOMAIN); ?></label>
                        <input id="cb-select-all-1" type="checkbox" />
                    </td>
                    <th><?php _e('File', ASA_TEXT_DOMAIN); ?></th>
                    <th><?php _e('Threat Level', ASA_TEXT_DOMAIN); ?></th>
                    <th><?php _e('Threats Found', ASA_TEXT_DOMAIN); ?></th>
                    <th><?php _e('Scan Date', ASA_TEXT_DOMAIN); ?></th>
                    <th><?php _e('Status', ASA_TEXT_DOMAIN); ?></th>
                    <th><?php _e('Actions', ASA_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $result): ?>
                <tr data-attachment-id="<?php echo esc_attr($result['attachment_id']); ?>">
                    <th scope="row" class="check-column">
                        <input type="checkbox" name="attachment_ids[]" value="<?php echo esc_attr($result['attachment_id']); ?>" 
                               class="asa-result-checkbox" />
                    </th>
                    <td>
                        <strong><?php echo esc_html(basename($result['file_path'])); ?></strong>
                        <br><small><?php echo esc_html($result['file_path']); ?></small>
                    </td>
                    <td>
                        <span class="asa-threat-level asa-threat-<?php echo esc_attr($result['threat_level']); ?>">
                            <?php echo esc_html(ucfirst($result['threat_level'])); ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!empty($result['threats_found'])): ?>
                            <div class="asa-threats-summary">
                                <strong><?php echo count($result['threats_found']); ?> <?php _e('threats', ASA_TEXT_DOMAIN); ?></strong>
                                <details>
                                    <summary><?php _e('View Details', ASA_TEXT_DOMAIN); ?></summary>
                                    <ul class="asa-threats-list">
                                        <?php foreach ($result['threats_found'] as $threat): ?>
                                        <li>
                                            <span class="asa-threat-type"><?php echo esc_html($threat['type']); ?></span>
                                            <span class="asa-threat-severity asa-severity-<?php echo esc_attr($threat['severity']); ?>">
                                                <?php echo esc_html($threat['severity']); ?>
                                            </span>
                                            <p><?php echo esc_html($threat['description']); ?></p>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </details>
                            </div>
                        <?php else: ?>
                            <span class="asa-no-threats"><?php _e('No threats', ASA_TEXT_DOMAIN); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($result['scan_date']))); ?>
                    </td>
                    <td>
                        <span class="asa-status asa-status-<?php echo esc_attr($result['status']); ?>">
                            <?php echo esc_html(ucfirst($result['status'])); ?>
                        </span>
                    </td>
                    <td>
                        <div class="asa-actions">
                            <?php if ($result['status'] === 'active'): ?>
                                <button type="button" class="button button-small asa-rescan-btn" 
                                        data-attachment-id="<?php echo esc_attr($result['attachment_id']); ?>">
                                    <?php _e('Rescan', ASA_TEXT_DOMAIN); ?>
                                </button>
                                
                                <?php if (!empty($result['threats_found'])): ?>
                                    <button type="button" class="button button-small asa-quarantine-btn" 
                                            data-attachment-id="<?php echo esc_attr($result['attachment_id']); ?>">
                                        <?php _e('Quarantine', ASA_TEXT_DOMAIN); ?>
                                    </button>
                                    
                                    <button type="button" class="button button-small button-link-delete asa-delete-btn" 
                                            data-attachment-id="<?php echo esc_attr($result['attachment_id']); ?>">
                                        <?php _e('Delete', ASA_TEXT_DOMAIN); ?>
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <a href="<?php echo esc_url(admin_url('post.php?post=' . $result['attachment_id'] . '&action=edit')); ?>" 
                               class="button button-small">
                                <?php _e('Edit', ASA_TEXT_DOMAIN); ?>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }

    /**
     * Display admin notices for scan results
     */
    public function display_scan_notices() {
        $last_scan = get_option('asa_last_svg_scan_summary', array());
        
        if (empty($last_scan)) {
            return;
        }

        // Only show on relevant admin pages
        $screen = get_current_screen();
        if (!in_array($screen->id, array('tools_page_asa-svg-scanner', 'media_page_asa-svg-scanner-media', 'upload'))) {
            return;
        }

        // Check if scan found threats
        if (!empty($last_scan['threats_found']) && $last_scan['threats_found'] > 0) {
            $message = sprintf(
                __('SVG Security Scanner found %d potential threats in %d files. <a href="%s">Review scan results</a>.', ASA_TEXT_DOMAIN),
                $last_scan['threats_found'],
                count($last_scan['files_with_issues']),
                admin_url('tools.php?page=asa-svg-scanner')
            );
            
            echo '<div class="notice notice-warning"><p>' . $message . '</p></div>';
        }
    }
}
