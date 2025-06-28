/**
 * SVG Security Scanner Admin JavaScript
 * 
 * Handles AJAX interactions for the SVG security scanner admin interface
 */

(function($) {
    'use strict';

    const AsaSvgScanner = {
        
        /**
         * Initialize scanner functionality
         */
        init: function() {
            this.bindEvents();
            this.loadInitialResults();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Scan buttons
            $('#asa-scan-library').on('click', this.scanLibrary.bind(this, false));
            $('#asa-scan-library-force').on('click', this.scanLibrary.bind(this, true));
            
            // Filter controls
            $('#asa-load-results').on('click', this.loadResults.bind(this));
            $('#asa-filter-threat-level, #asa-filter-status').on('change', this.loadResults.bind(this));
            
            // Result actions (delegated events for dynamic content)
            $(document).on('click', '.asa-rescan-btn', this.rescanFile.bind(this));
            $(document).on('click', '.asa-quarantine-btn', this.quarantineFile.bind(this));
            $(document).on('click', '.asa-delete-btn', this.deleteFile.bind(this));
            
            // Batch action controls
            $(document).on('change', '#cb-select-all-1', this.toggleSelectAll.bind(this));
            $(document).on('change', '.asa-result-checkbox', this.updateBatchSelection.bind(this));
            $('#asa-apply-batch-action').on('click', this.applyBatchAction.bind(this));
            $('#asa-clear-selection').on('click', this.clearSelection.bind(this));
        },

        /**
         * Load initial scan results
         */
        loadInitialResults: function() {
            this.loadResults();
        },

        /**
         * Scan entire media library
         */
        scanLibrary: function(forceRescan) {
            const $button = forceRescan ? $('#asa-scan-library-force') : $('#asa-scan-library');
            const $progress = $('#asa-scan-progress');
            const $progressFill = $('.asa-progress-fill');
            const $progressText = $('.asa-progress-text');

            // Disable buttons and show progress
            $('#asa-scan-library, #asa-scan-library-force').prop('disabled', true);
            $progress.show();
            $progressText.text(asaScannerAjax.strings.scanning);
            
            // Animate progress bar
            $progressFill.css('width', '0%');
            this.animateProgress($progressFill, 90, 5000); // Animate to 90% over 5 seconds

            const data = {
                action: 'asa_scan_svg_library',
                nonce: asaScannerAjax.nonce,
                force_rescan: forceRescan,
                include_quarantined: $('#asa-scan-include-quarantined').is(':checked')
            };

            $.post(asaScannerAjax.ajaxurl, data)
                .done(function(response) {
                    if (response.success) {
                        // Complete progress animation
                        $progressFill.css('width', '100%');
                        $progressText.text(asaScannerAjax.strings.scan_complete);
                        
                        // Show scan summary
                        AsaSvgScanner.displayScanSummary(response.data);
                        
                        // Reload results
                        setTimeout(function() {
                            AsaSvgScanner.loadResults();
                            $progress.hide();
                        }, 2000);
                        
                        // Show success notice
                        AsaSvgScanner.showNotice('success', 
                            `Scan completed: ${response.data.total_scanned} files scanned, ${response.data.threats_found} threats found.`
                        );
                    } else {
                        AsaSvgScanner.showNotice('error', response.data.message || asaScannerAjax.strings.scan_error);
                        $progress.hide();
                    }
                })
                .fail(function() {
                    AsaSvgScanner.showNotice('error', asaScannerAjax.strings.scan_error);
                    $progress.hide();
                })
                .always(function() {
                    $('#asa-scan-library, #asa-scan-library-force').prop('disabled', false);
                });
        },

        /**
         * Animate progress bar
         */
        animateProgress: function($element, targetWidth, duration) {
            $element.animate({
                width: targetWidth + '%'
            }, duration, 'linear');
        },

        /**
         * Display scan summary
         */
        displayScanSummary: function(scanData) {
            // Update statistics on the page
            if (scanData.files_with_issues && scanData.files_with_issues.length > 0) {
                const $summary = $('<div class="notice notice-warning is-dismissible">')
                    .html(`<p><strong>Scan Results:</strong> Found ${scanData.threats_found} threats in ${scanData.files_with_issues.length} files.</p>`);
                
                $('.asa-scanner-dashboard').prepend($summary);
            } else {
                const $summary = $('<div class="notice notice-success is-dismissible">')
                    .html(`<p><strong>Scan Results:</strong> No threats found in ${scanData.total_scanned} files scanned.</p>`);
                
                $('.asa-scanner-dashboard').prepend($summary);
            }
        },

        /**
         * Load scan results with current filters
         */
        loadResults: function() {
            const data = {
                action: 'asa_get_scan_results',
                nonce: asaScannerAjax.nonce,
                threat_level: $('#asa-filter-threat-level').val(),
                status: $('#asa-filter-status').val(),
                limit: 50,
                offset: 0
            };

            const $container = $('#asa-results-container');
            $container.html('<div class="asa-loading">Loading results...</div>');

            $.post(asaScannerAjax.ajaxurl, data)
                .done(function(response) {
                    if (response.success) {
                        $container.html(response.data.html);
                    } else {
                        $container.html('<p class="error">Failed to load results: ' + response.data.message + '</p>');
                    }
                })
                .fail(function() {
                    $container.html('<p class="error">Failed to load results.</p>');
                });
        },

        /**
         * Rescan individual file
         */
        rescanFile: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const attachmentId = $button.data('attachment-id');
            const $row = $button.closest('tr');

            $button.prop('disabled', true).text('Scanning...');

            const data = {
                action: 'asa_scan_single_svg',
                nonce: asaScannerAjax.nonce,
                attachment_id: attachmentId,
                force_rescan: true
            };

            $.post(asaScannerAjax.ajaxurl, data)
                .done(function(response) {
                    if (response.success) {
                        AsaSvgScanner.showNotice('success', 'File rescanned successfully.');
                        AsaSvgScanner.loadResults(); // Reload to show updated results
                    } else {
                        AsaSvgScanner.showNotice('error', response.data.message || 'Rescan failed.');
                    }
                })
                .fail(function() {
                    AsaSvgScanner.showNotice('error', 'Rescan failed.');
                })
                .always(function() {
                    $button.prop('disabled', false).text('Rescan');
                });
        },

        /**
         * Quarantine file
         */
        quarantineFile: function(e) {
            e.preventDefault();
            
            if (!confirm(asaScannerAjax.strings.confirm_quarantine)) {
                return;
            }

            const $button = $(e.currentTarget);
            const attachmentId = $button.data('attachment-id');
            const $row = $button.closest('tr');

            $button.prop('disabled', true).text('Quarantining...');

            const data = {
                action: 'asa_quarantine_svg',
                nonce: asaScannerAjax.nonce,
                attachment_id: attachmentId
            };

            $.post(asaScannerAjax.ajaxurl, data)
                .done(function(response) {
                    if (response.success) {
                        AsaSvgScanner.showNotice('success', 'File quarantined successfully.');
                        $row.find('.asa-status').removeClass().addClass('asa-status asa-status-quarantined').text('Quarantined');
                        $row.find('.asa-actions').html('<span class="description">File quarantined</span>');
                    } else {
                        AsaSvgScanner.showNotice('error', response.data.message || 'Quarantine failed.');
                        $button.prop('disabled', false).text('Quarantine');
                    }
                })
                .fail(function() {
                    AsaSvgScanner.showNotice('error', 'Quarantine failed.');
                    $button.prop('disabled', false).text('Quarantine');
                });
        },

        /**
         * Delete file permanently
         */
        deleteFile: function(e) {
            e.preventDefault();
            
            if (!confirm(asaScannerAjax.strings.confirm_delete)) {
                return;
            }

            const $button = $(e.currentTarget);
            const attachmentId = $button.data('attachment-id');
            const $row = $button.closest('tr');

            $button.prop('disabled', true).text('Deleting...');

            const data = {
                action: 'asa_delete_svg',
                nonce: asaScannerAjax.nonce,
                attachment_id: attachmentId
            };

            $.post(asaScannerAjax.ajaxurl, data)
                .done(function(response) {
                    if (response.success) {
                        AsaSvgScanner.showNotice('success', 'File deleted successfully.');
                        $row.fadeOut(400, function() {
                            $row.remove();
                        });
                    } else {
                        AsaSvgScanner.showNotice('error', response.data.message || 'Delete failed.');
                        $button.prop('disabled', false).text('Delete');
                    }
                })
                .fail(function() {
                    AsaSvgScanner.showNotice('error', 'Delete failed.');
                    $button.prop('disabled', false).text('Delete');
                });
        },

        /**
         * Toggle select all checkboxes
         */
        toggleSelectAll: function(e) {
            const isChecked = $(e.target).is(':checked');
            $('.asa-result-checkbox').prop('checked', isChecked);
            this.updateBatchSelection();
        },

        /**
         * Update batch selection UI
         */
        updateBatchSelection: function() {
            const $selected = $('.asa-result-checkbox:checked');
            const count = $selected.length;
            
            $('#asa-selected-count').text(count);
            
            if (count > 0) {
                $('#asa-batch-actions').show();
            } else {
                $('#asa-batch-actions').hide();
            }
            
            // Update select all checkbox state
            const $allCheckboxes = $('.asa-result-checkbox');
            const $selectAll = $('#cb-select-all-1');
            
            if (count === 0) {
                $selectAll.prop('indeterminate', false).prop('checked', false);
            } else if (count === $allCheckboxes.length) {
                $selectAll.prop('indeterminate', false).prop('checked', true);
            } else {
                $selectAll.prop('indeterminate', true);
            }
        },

        /**
         * Apply batch action to selected items
         */
        applyBatchAction: function() {
            const action = $('#asa-batch-action').val();
            const $selected = $('.asa-result-checkbox:checked');
            
            if (!action) {
                this.showNotice('error', asaScannerAjax.strings.no_items_selected);
                return;
            }
            
            if ($selected.length === 0) {
                this.showNotice('error', asaScannerAjax.strings.no_items_selected);
                return;
            }
            
            // Confirm destructive actions
            let confirmMessage = '';
            if (action === 'quarantine') {
                confirmMessage = asaScannerAjax.strings.confirm_batch_quarantine;
            } else if (action === 'delete') {
                confirmMessage = asaScannerAjax.strings.confirm_batch_delete;
            }
            
            if (confirmMessage && !confirm(confirmMessage)) {
                return;
            }
            
            // Collect attachment IDs
            const attachmentIds = [];
            $selected.each(function() {
                attachmentIds.push($(this).val());
            });
            
            // Show progress
            const $button = $('#asa-apply-batch-action');
            const originalText = $button.text();
            $button.prop('disabled', true).text(asaScannerAjax.strings.batch_processing);
            
            // Perform batch action
            const data = {
                action: 'asa_batch_action',
                nonce: asaScannerAjax.nonce,
                batch_action: action,
                attachment_ids: attachmentIds
            };
            
            $.post(asaScannerAjax.ajaxurl, data)
                .done(function(response) {
                    if (response.success) {
                        AsaSvgScanner.showNotice('success', response.data.message);
                        AsaSvgScanner.loadResults(); // Refresh results
                        AsaSvgScanner.clearSelection();
                    } else {
                        AsaSvgScanner.showNotice('error', response.data.message);
                    }
                })
                .fail(function() {
                    AsaSvgScanner.showNotice('error', 'Batch action failed due to network error.');
                })
                .always(function() {
                    $button.prop('disabled', false).text(originalText);
                });
        },

        /**
         * Clear all selections
         */
        clearSelection: function() {
            $('.asa-result-checkbox, #cb-select-all-1').prop('checked', false);
            $('#asa-batch-actions').hide();
            $('#asa-selected-count').text('0');
        },

        /**
         * Show admin notice
         */
        showNotice: function(type, message) {
            const $notice = $('<div class="notice notice-' + type + ' is-dismissible">')
                .html('<p>' + message + '</p>')
                .hide();

            $('.asa-scanner-dashboard').prepend($notice);
            $notice.slideDown();

            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.slideUp(function() {
                    $notice.remove();
                });
            }, 5000);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        AsaSvgScanner.init();
    });

})(jQuery);
