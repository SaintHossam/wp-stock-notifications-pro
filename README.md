# Stock Notifications Pro (WordPress/WooCommerce)

Back-in-stock alerts for WooCommerce. Let shoppers subscribe to out-of-stock products and automatically email them when items are restocked. Includes a clean admin settings panel and SMTP configuration for reliable delivery.

- **Author:** Hossam Hamdy (SaintHossam)  
- **Plugin URI:** https://github.com/SaintHossam/wp-stock-notifications-pro  
- **Author URI:** https://github.com/SaintHossam/  
- **Text Domain:** `stock-notifier`
- **Version:** 1.0.0
- **License:** GPL-2.0-or-later

---

## 🎯 Recent Updates (v1.0.0)

**Major Refactoring - PSR-4 Compliant Architecture**

The plugin has been completely refactored into a modern, maintainable structure following PSR-4 autoloading standards and WordPress best practices. This update maintains 100% backward compatibility while providing a solid foundation for future enhancements.

### What's New

- ✅ **PSR-4 Autoloading**: All classes organized under `WPStockNotificationsPro` namespace
- ✅ **Modular Architecture**: Clean separation of concerns (Admin, Public, Mail, Database, Helpers)
- ✅ **Composer Integration**: Professional dependency management
- ✅ **Template System**: Email and form templates in dedicated directories
- ✅ **Improved Code Quality**: Better docblocks and coding standards
- ✅ **Proper Uninstall**: Clean removal of data when plugin is deleted
- ✅ **Developer-Friendly**: Easy to extend and maintain

---

## Features

- ✨ "Notify me when available" subscription on product pages
- 📧 Automatic email alerts when stock returns
- 📊 Admin dashboard to view/manage subscriptions
- 🔒 Built-in SMTP settings for reliable delivery
- 🛡️ GDPR-friendly: collects only what's needed
- 🌍 Translation-ready (`stock-notifier` text domain)
- 🏗️ Modern PSR-4 architecture

## Requirements

- WordPress 5.8 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher
- Composer (for development or manual installation)

## Installation

### Standard Installation (Production)

1. Download or clone the repository into:  
   `wp-content/plugins/wp-stock-notifications-pro/`

2. Run Composer to generate the autoloader:
   ```bash
   cd wp-content/plugins/wp-stock-notifications-pro/
   composer install --no-dev
   ```

3. In **WP Admin → Plugins**, activate **Stock Notifications Pro**

4. Navigate to **إشعارات المخزون** in the admin menu to configure settings

### Development Installation

```bash
git clone https://github.com/SaintHossam/wp-stock-notifications-pro.git
cd wp-stock-notifications-pro
composer install
```

Then activate the plugin through WordPress admin panel.

---

## Directory Structure

```
wp-stock-notifications-pro/
├── src/                          # PSR-4 namespaced source code
│   ├── Admin/                    # Admin-related classes
│   │   ├── Dashboard.php         # Dashboard display
│   │   ├── Menu.php              # Admin menu registration
│   │   ├── Requests.php          # Requests management
│   │   └── Settings.php          # Settings page
│   ├── Database/                 # Database operations
│   │   └── Schema.php            # Table creation/management
│   ├── Helpers/                  # Helper functions
│   │   └── Functions.php         # Utility functions
│   ├── Mail/                     # Email handling
│   │   └── Mailer.php            # SMTP and email sending
│   ├── Public/                   # Public-facing classes
│   │   └── Frontend.php          # Frontend hooks and display
│   ├── Activator.php             # Plugin activation handler
│   ├── Deactivator.php           # Plugin deactivation handler
│   └── Plugin.php                # Main plugin bootstrap class
├── templates/                     # Template files
│   ├── emails/                   # Email templates
│   │   └── notification.php      # Stock notification email
│   └── notification-form.php     # Frontend notification form
├── assets/                        # Static assets (CSS/JS currently inline)
│   ├── css/
│   ├── js/
│   └── images/
├── languages/                     # Translation files (.pot, .po, .mo)
├── vendor/                        # Composer dependencies (git-ignored)
├── composer.json                  # Composer configuration
├── uninstall.php                  # Uninstall cleanup script
├── wp-stock-notifications-pro.php # Main plugin file (bootstrap)
├── .gitignore                     # Git ignore rules
└── README.md                      # This file
```

---

## Architecture Overview

### Namespace Structure

All classes use the `WPStockNotificationsPro` namespace following PSR-4:

```php
WPStockNotificationsPro\
├── Plugin                        # Singleton main class
├── Activator                     # Activation logic
├── Deactivator                   # Deactivation logic
├── Admin\
│   ├── Dashboard                 # Dashboard view
│   ├── Menu                      # Menu registration & routing
│   ├── Requests                  # Request management
│   └── Settings                  # Settings management
├── Database\
│   └── Schema                    # Database schema
├── Helpers\
│   └── Functions                 # Utility functions
├── Mail\
│   └── Mailer                    # Email handling
└── Public\
    └── Frontend                  # Public-facing functionality
```

### Key Classes

#### Plugin (Main Bootstrap)
- **Location**: `src/Plugin.php`
- **Purpose**: Singleton class that initializes all components
- **Key Methods**:
  - `get_instance()`: Returns singleton instance
  - `init()`: Initializes components and hooks
  - `load_textdomain()`: Loads translations

#### Activator
- **Location**: `src/Activator.php`
- **Purpose**: Handles plugin activation tasks
- Creates database table and sets default options

#### Deactivator
- **Location**: `src/Deactivator.php`
- **Purpose**: Handles plugin deactivation cleanup
- Cleans up transients (data preserved for reactivation)

#### Frontend (Public)
- **Location**: `src/Public/Frontend.php`
- **Purpose**: Manages all public-facing functionality
- **Responsibilities**:
  - Modifies WooCommerce templates for out-of-stock products
  - Handles AJAX subscription requests
  - Enqueues scripts and styles
  - Displays notification forms
  - Manages unsubscribe requests

#### Mailer
- **Location**: `src/Mail/Mailer.php`
- **Purpose**: Handles all email-related functionality
- **Responsibilities**:
  - Configures SMTP settings
  - Sends stock notification emails
  - Monitors stock status changes via WooCommerce hooks
  - Formats email templates
  - Logs email failures

#### Admin Classes

**Menu** (`src/Admin/Menu.php`)
- Registers admin menu and routes tab requests
- Handles admin page rendering
- Coordinates between Dashboard, Requests, and Settings

**Dashboard** (`src/Admin/Dashboard.php`)
- Displays statistics (total, pending, sent)
- Shows recent pending and sent notifications
- Provides quick access to manage requests

**Requests** (`src/Admin/Requests.php`)
- Lists all subscription requests
- Provides filtering (status, search)
- Handles single and bulk deletion
- Manages request lifecycle

**Settings** (`src/Admin/Settings.php`)
- Manages plugin configuration
- Handles SMTP settings
- Email template configuration
- Test email functionality

### Database Schema

The plugin creates a custom table `wp_stock_notifications`:

```sql
CREATE TABLE wp_stock_notifications (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    product_id bigint(20) NOT NULL,
    variation_id bigint(20) DEFAULT 0,
    user_email varchar(100) NOT NULL,
    user_name varchar(100) DEFAULT '',
    phone varchar(20) DEFAULT '',
    date_registered datetime DEFAULT CURRENT_TIMESTAMP,
    is_notified tinyint(1) DEFAULT 0,
    unsubscribed tinyint(1) DEFAULT 0,
    PRIMARY KEY (id),
    KEY product_id (product_id),
    KEY user_email (user_email)
);
```

---

## Quick Setup

1. **SMTP Configuration**:
   - Navigate to **إشعارات المخزون → الإعدادات**
   - Enable SMTP and enter your mail server details
   - Or use an SMTP plugin like WP Mail SMTP

2. **Customize Messages**:
   - Configure email subject template (use `%site%` and `%product%` placeholders)
   - Set success/error messages for subscription form
   - Customize button text and notification preferences

3. **Test the Setup**:
   - Go to **إشعارات المخزون → اختبار البريد**
   - Send a test email to verify SMTP configuration
   - Mark a product as out of stock and subscribe
   - Change stock status to "In Stock" and verify email delivery

---

## How It Works

### For Shoppers:

1. Customer visits an out-of-stock product page
2. A notification form appears asking for name, email, and optional phone
3. Customer submits the form (AJAX request)
4. Subscription is saved in the database
5. When product comes back in stock, customer receives an email automatically
6. Email includes product details, price, and "Buy Now" button
7. Customer can unsubscribe via link in email

### For Administrators:

1. **Dashboard**: View statistics and recent activity
2. **Requests**: Manage all subscriptions (search, filter, delete)
3. **Settings**: Configure SMTP, email templates, and notifications
4. **Test**: Send test emails to verify configuration

### Technical Flow:

```
Product Stock Change
    ↓
WooCommerce Hook (woocommerce_product_set_stock)
    ↓
Mailer::maybe_send_notifications()
    ↓
Check if stock is available
    ↓
Query unnotified subscribers
    ↓
Send email to each subscriber
    ↓
Mark as notified in database
```

---

## Developer Notes

### Extending the Plugin

The plugin is designed to be easily extensible:

```php
// Example: Add custom filter for email subject
add_filter('snp_email_subject', function($subject, $product) {
    return 'Custom: ' . $subject;
}, 10, 2);

// Example: Hook after notification sent
add_action('snp_notification_sent', function($product_id, $subscriber) {
    // Your custom logic here
}, 10, 2);
```

### Creating Custom Templates

Email templates are located in `templates/emails/`. To override:

1. Copy the template to your theme: `your-theme/wp-stock-notifications-pro/emails/notification.php`
2. Modify as needed
3. Template variables: `$subscriber`, `$product`, `$site_name`

### Adding New Features

The modular structure makes it easy to add new features:

1. Create new class in appropriate namespace (`src/Admin/`, `src/Public/`, etc.)
2. Register hooks in the class
3. Initialize in `Plugin.php` if needed

### Plugin Header

```php
/**
 * Plugin Name: Stock Notifications Pro
 * Description: Back-in-stock alerts for WooCommerce with admin settings and SMTP delivery.
 * Version: 1.0.0
 * Author: Hossam Hamdy (SaintHossam)
 * Text Domain: stock-notifier
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 */
```

### Translations

- Text domain: `stock-notifier`
- Load from `/languages`
- Generate POT file:
  ```bash
  wp i18n make-pot . languages/stock-notifier.pot
  ```

---

## Hooks & Filters

### Available Hooks

```php
// Fires after plugin components are initialized
do_action('snp_plugin_loaded');

// Fires after a notification is sent
do_action('snp_notification_sent', $product_id, $subscriber);

// Filter email subject
apply_filters('snp_email_subject', $subject, $product, $subscriber);

// Filter email HTML content
apply_filters('snp_email_html', $html, $product, $subscriber);

// Filter email headers
apply_filters('snp_email_headers', $headers, $product, $subscriber);
```

---

## Privacy & GDPR

This plugin stores the following user data:
- Email address (required for notifications)
- Name (required for personalization)
- Phone number (optional)
- Subscription date
- Notification status

Data is used solely for sending one-time back-in-stock notifications. Users can unsubscribe at any time via the email link. All data is removed when the plugin is uninstalled.

---

## Troubleshooting

### Emails Not Being Sent

1. **Check SMTP Settings**: Verify credentials in Settings → إعدادات
2. **Test Email**: Use the test email feature in اختبار البريد tab
3. **Check Logs**: Review WordPress debug.log for errors
4. **WP Cron**: Ensure WP-Cron is running (or set up real cron)
5. **Conflict**: Try disabling other SMTP plugins temporarily

### Emails Going to Spam

1. **SPF/DKIM**: Configure DNS records for your domain
2. **From Address**: Use a valid email from your domain
3. **Authentication**: Enable SMTP authentication
4. **Content**: Avoid spam trigger words in email templates

### Notifications Not Triggering

1. **Stock Status**: Ensure product stock status changes to "In Stock"
2. **Hooks**: Check if other plugins might be interfering with WooCommerce hooks
3. **Database**: Verify subscriptions exist in the database
4. **Already Notified**: Check if subscribers were already notified

### Plugin Conflicts

1. Test with default WordPress theme (Twenty Twenty-Four)
2. Deactivate other plugins one by one
3. Check for JavaScript errors in browser console
4. Enable WordPress debug mode for detailed error messages

---

## Changelog

### Version 1.0.0 (2025-11-20)

**Major Refactoring**
- Complete restructure to PSR-4 compliant architecture
- Introduced namespace `WPStockNotificationsPro`
- Separated concerns into modular classes
- Added Composer for dependency management
- Extracted templates into dedicated directory
- Improved code documentation with docblocks
- Added proper uninstall cleanup
- Maintained 100% backward compatibility

**New Structure:**
- `src/Plugin.php`: Main bootstrap class
- `src/Admin/`: Admin functionality (Menu, Dashboard, Requests, Settings)
- `src/Public/`: Public-facing functionality
- `src/Mail/`: Email handling
- `src/Database/`: Database schema management
- `src/Helpers/`: Utility functions
- `templates/`: Email and form templates
- `uninstall.php`: Proper cleanup on plugin deletion

---

## Contributing

Issues and Pull Requests are welcome!

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

Please ensure:
- Code follows WordPress Coding Standards
- All classes are properly documented
- Changes are backward compatible
- Update README if needed

---

## Testing

After installing or updating:

1. **Test Subscription Form**:
   - Mark a product as out of stock
   - Visit the product page
   - Verify notification form appears
   - Submit a test subscription
   - Check confirmation message

2. **Test Email Notification**:
   - Update product to "In Stock"
   - Wait for notification email
   - Verify email content and formatting
   - Test unsubscribe link

3. **Test Admin Panel**:
   - View dashboard statistics
   - Filter and search requests
   - Test bulk deletion
   - Modify settings
   - Send test email

---

## Support

For issues, questions, or feature requests:
- **GitHub Issues**: https://github.com/SaintHossam/wp-stock-notifications-pro/issues
- **Author**: https://github.com/SaintHossam/

---

## License

GPL-2.0-or-later

This plugin is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

---

## Credits

Developed by **Hossam Hamdy (SaintHossam)**
- GitHub: https://github.com/SaintHossam/
- Plugin Repository: https://github.com/SaintHossam/wp-stock-notifications-pro
