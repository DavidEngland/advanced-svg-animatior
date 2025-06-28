# SVG Security Scanner - User Guide

The Advanced SVG Animator plugin includes a comprehensive SVG security scanner that helps protect your WordPress site from potentially malicious SVG files. This guide covers all features and functionality.

## Accessing the SVG Scanner

The SVG Security Scanner can be accessed in two ways:

1. **Via Tools Menu**: Go to **Tools > SVG Security** in your WordPress admin
2. **Via Media Menu**: Go to **Media > SVG Security** in your WordPress admin

## Main Features

### 1. Scanner Dashboard

The main dashboard provides an overview of your SVG security status:

#### Statistics Overview
- **Total Scans**: Number of scans performed
- **Critical Threats**: Files with critical security risks
- **High Risk**: Files with high-risk threats
- **Medium Risk**: Files with medium-risk threats
- **Quarantined**: Files that have been quarantined

#### Last Scan Summary
Shows details of the most recent scan including:
- Number of files scanned
- Scan duration
- Date and time of scan
- Number of threats found

### 2. Manual Scanning

#### Scan All SVG Files
- Click **"Scan All SVG Files"** to scan your entire media library
- Shows real-time progress with a progress bar
- Displays scan results when complete

#### Force Rescan All
- Click **"Force Rescan All"** to rescan all files, including previously scanned ones
- Useful when scanner definitions have been updated
- Bypasses scan cache for complete fresh analysis

#### Scan Options
- **Include quarantined files**: Option to include previously quarantined files in the scan

### 3. Batch Actions

The scanner supports powerful batch operations for managing multiple files:

#### Selecting Files
- Use checkboxes next to individual files to select them
- Click the header checkbox to "Select All" files
- Selected count is displayed in the batch actions bar

#### Available Batch Actions
- **Quarantine Selected**: Move selected suspicious files to quarantine
- **Delete Selected**: Permanently delete selected files (irreversible)
- **Rescan Selected**: Re-analyze selected files

#### Using Batch Actions
1. Select files using checkboxes
2. Choose an action from the "Bulk Actions" dropdown
3. Click "Apply" to execute the action
4. Confirm when prompted for destructive actions

### 4. Scheduled Scanning

Automated scanning helps maintain ongoing security:

#### Enabling Scheduled Scans
1. Check "Automatically scan SVG files on a schedule"
2. Choose scan frequency:
   - **Daily**: Scans every 24 hours
   - **Weekly**: Scans once per week
   - **Monthly**: Scans once per month

#### Email Notifications
- Enable "Send email notifications when threats are found"
- Set notification threshold:
  - **Critical threats only**: Only notify for critical risks
  - **High and Critical threats**: Notify for high and critical risks
  - **Medium, High and Critical threats**: Notify for medium, high, and critical risks

#### Managing Scheduled Scans
- View next scheduled scan time in the settings
- Settings are saved immediately when you click "Save Scheduled Scan Settings"
- Scans run automatically via WordPress Cron

### 5. Scan Results and File Management

#### Viewing Results
- Use filters to view specific threat levels or file statuses
- Sort results by clicking column headers
- View detailed threat information by expanding threat details

#### Threat Levels
- **Critical**: Immediate security risk, should be quarantined/deleted
- **High**: High security risk, review and quarantine recommended
- **Medium**: Moderate risk, review recommended
- **Low**: Low risk, monitoring sufficient

#### File Actions
For individual files, you can:
- **Rescan**: Re-analyze a specific file
- **Quarantine**: Move file to quarantine (removes from active use)
- **Delete**: Permanently remove file from server
- **Edit**: Open file in WordPress media editor

#### File Statuses
- **Active**: File is available for use
- **Quarantined**: File has been moved to quarantine
- **Deleted**: File has been permanently removed

### 6. Performance Features

The scanner includes several performance optimizations:

#### Batch Processing
- Large libraries are processed in batches to prevent timeouts
- Default batch size is 25 files (configurable)
- Memory usage is monitored and controlled

#### Memory Management
- Automatic memory limit checking (default 128MB per batch)
- Scan stops gracefully if memory limits are approached
- Memory usage statistics displayed after scans

#### Execution Time Limits
- Protection against PHP execution timeouts
- Graceful handling of time limit approaches
- Scan resumption capabilities for very large libraries

## Understanding Threats

### Common SVG Threats Detected

1. **JavaScript/Script Tags**: Embedded JavaScript code
2. **External Resource Loading**: Links to external malicious resources
3. **Event Handlers**: Malicious event handling attributes
4. **PHP Code**: Embedded PHP code execution attempts
5. **Data URIs**: Suspicious data URI schemes
6. **Foreign Objects**: Embedded foreign content

### Threat Severity Levels

- **Critical**: Immediate exploitation possible, definite malicious content
- **High**: High likelihood of malicious intent, significant risk
- **Medium**: Suspicious patterns, potential security risk
- **Low**: Minor concerns, likely false positive

## WordPress Cron Setup

### Verifying Cron Functionality

WordPress Cron powers the scheduled scanning feature. To ensure it works:

1. **Check Cron Status**: Use plugins like "WP Crontrol" to verify cron jobs
2. **Look for**: `asa_scheduled_svg_scan` in your cron schedule
3. **Manual Trigger**: You can manually trigger scans via WP-CLI or cron plugins

### Troubleshooting Cron Issues

If scheduled scans aren't running:

1. **Server Cron**: Set up real server cron job:
   ```bash
   */15 * * * * wget -q -O - https://yoursite.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1
   ```

2. **Disable WP Cron**: Add to `wp-config.php`:
   ```php
   define('DISABLE_WP_CRON', true);
   ```

3. **Plugin Conflicts**: Temporarily deactivate other plugins to test

### Alternative Cron Setup

For reliable scheduled scanning on high-traffic sites:

1. **Use WP-CLI**:
   ```bash
   */30 * * * * wp cron event run --due-now --path=/path/to/wordpress/
   ```

2. **Direct PHP Execution**:
   ```bash
   0 2 * * * php /path/to/wordpress/wp-content/plugins/advanced-svg-animator/cron-scanner.php
   ```

## Security Best Practices

### Recommended Workflow

1. **Initial Scan**: Run a complete scan of your media library
2. **Review Results**: Examine all threats found, starting with critical
3. **Quarantine Suspicious**: Quarantine files you're unsure about
4. **Delete Malicious**: Delete confirmed malicious files
5. **Enable Scheduled Scans**: Set up daily or weekly automatic scans
6. **Monitor Notifications**: Review email notifications promptly

### File Upload Security

The scanner also checks files during upload:
- New SVG uploads are automatically scanned
- Suspicious files are flagged immediately
- Admins receive notifications of upload-time threats

### Regular Maintenance

1. **Weekly Reviews**: Check scan results weekly
2. **Update Scanner**: Keep the plugin updated for latest threat definitions
3. **Monitor Logs**: Review scanner logs for patterns or issues
4. **Backup Strategy**: Ensure quarantined files are included in backups

## Performance Considerations

### Large Media Libraries

For sites with thousands of SVG files:

1. **Scheduled Scans**: Use scheduled scans instead of manual full scans
2. **Batch Processing**: Default settings should handle most libraries
3. **Server Resources**: Monitor server performance during scans
4. **Time Limits**: Consider running scans during low-traffic periods

### Optimization Settings

The scanner automatically optimizes for:
- Available server memory
- PHP execution time limits
- Database query efficiency
- File system performance

### Troubleshooting Performance

If scans are slow or failing:

1. **Increase PHP Memory**: `ini_set('memory_limit', '256M')`
2. **Increase Time Limit**: `ini_set('max_execution_time', 300)`
3. **Reduce Batch Size**: Contact support for custom batch sizing
4. **Server Resources**: Ensure adequate server resources

## Frequently Asked Questions

### Q: Are quarantined files deleted?
A: No, quarantined files are moved to a secure location but not deleted. They can be restored if needed.

### Q: Can I whitelist certain files?
A: Currently, you can mark files as "reviewed" by rescanning them. Future versions may include whitelist functionality.

### Q: Do scans affect site performance?
A: Scans are designed to run efficiently. Scheduled scans run during low-activity periods to minimize impact.

### Q: What happens if WordPress cron isn't working?
A: Manual scans will still work. Set up server-level cron or use a cron management plugin.

### Q: Can I export scan results?
A: Scan results are logged and can be exported through the WordPress database or via future export features.

### Q: Are there false positives?
A: Yes, some legitimate SVG features may be flagged. Review all results and use the "Rescan" feature for verification.

## Support and Troubleshooting

For additional support:

1. **Plugin Documentation**: Check the main plugin documentation
2. **WordPress Logs**: Review WordPress error logs for issues
3. **Scanner Logs**: Check the plugin's internal logging system
4. **Community Support**: Use WordPress.org plugin support forums
5. **Professional Support**: Contact plugin developers for advanced assistance

---

*This guide covers the current version of the SVG Security Scanner. Features may be updated in future versions.*
