# Advanced SVG Animator - Installation Guide

## 🚀 Quick Install (End Users)

### WordPress.org Installation
1. Install from WordPress admin: **Plugins > Add New > Search "Advanced SVG Animator"**
2. Activate the plugin
3. Dependencies are pre-bundled - no additional setup required!

### Manual Installation
1. Download the plugin zip file
2. Upload via **Plugins > Add New > Upload Plugin**
3. Activate the plugin
4. Dependencies are included - ready to use!

## 🔧 Developer Installation

### From GitHub (Development)
```bash
# Clone the repository
git clone https://github.com/DavidEngland/advanced-svg-animatior.git

# Navigate to plugin directory
cd advanced-svg-animatior

# Install Composer dependencies
composer install

# Plugin is ready for development
```

### Local Development Setup
```bash
# If working in WordPress development environment
cd wp-content/plugins/
git clone https://github.com/DavidEngland/advanced-svg-animatior.git advanced-svg-animator
cd advanced-svg-animator
composer install
```

## 📦 Dependencies

### Bundled Dependencies (Included)
- **enshrined/svg-sanitize**: SVG sanitization and security scanning
- **WordPress core libraries**: Block editor, REST API, admin framework

### System Requirements
- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher (via WordPress)

## 🔍 Troubleshooting

### Missing Dependencies Error
If you see errors about missing classes:

```bash
# Run in plugin directory
composer install --no-dev --optimize-autoloader
```

### SVG Sanitizer Not Found
1. Check that `vendor/` directory exists in plugin folder
2. Verify `vendor/autoload.php` is present
3. Plugin will fall back to custom sanitizer if library unavailable

### Plugin Conflicts
The plugin includes automatic conflict detection for:
- Safe SVG
- SVG Support  
- Enable SVG Uploads
- WP SVG Icons
- And other popular SVG plugins

## ⚙️ Configuration

### Initial Setup
1. **Go to Settings > SVG Animator**
2. **Configure SVG support settings** (auto-detected)
3. **Review security settings** (recommended defaults)
4. **Test with SVG upload** in Media Library

### Advanced Configuration
- **Conflict Resolution**: Automatic detection and smart handling
- **Security Levels**: Strict, Basic, or Advanced sanitization
- **Logging**: Simple History integration for audit trails
- **Performance**: Optimized for large media libraries

## 🛡️ Security Features

### Automatic Security
- **SVG Sanitization**: All uploads automatically cleaned
- **Threat Detection**: Malicious content identification
- **Quarantine System**: Suspicious files isolated
- **Admin Notifications**: Security alerts for administrators

### Manual Security Tools
- **SVG Scanner**: Tools > SVG Scanner
- **Bulk Scanning**: Check existing media library
- **Security Reports**: Detailed threat analysis
- **Conflict Monitor**: Real-time plugin compatibility

## 📞 Support

### Documentation
- **User Guide**: Complete feature documentation
- **Technical Guide**: Developer and admin reference  
- **REIA Guide**: Real estate specific implementation
- **Troubleshooting**: Common issues and solutions

### Community Support
- **GitHub Issues**: https://github.com/DavidEngland/advanced-svg-animatior/issues
- **WordPress Support**: Plugin support forum (when published)
- **REIA Support**: Real estate professional assistance

### Professional Support
- **Custom Implementation**: REIA consultation services
- **Enterprise Features**: Advanced customization options
- **Priority Support**: Fast-track issue resolution

---

**Developed by Real Estate Intelligence Agency (REIA)**  
Professional WordPress solutions for real estate professionals.
