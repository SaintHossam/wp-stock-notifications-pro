=== Stock Notifications Pro ===
Contributors: sainthossam
Donate link:
Tags: woocommerce, stock, back-in-stock, email, notifications
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html


Back-in-stock alerts for WooCommerce. Let customers subscribe to out-of-stock products and automatically notify them when items are restocked.

== Description ==

**Stock Notifications Pro** is a powerful WooCommerce extension that helps you capture lost sales by allowing customers to subscribe for back-in-stock notifications. When a product becomes available again, subscribers receive automatic email alerts with product details and a convenient "Buy Now" button.

= Key Features =

* 📧 **Automatic Email Notifications** - Customers receive instant alerts when products are back in stock
* 📊 **Admin Dashboard** - View statistics and manage all subscription requests from one place
* 🔒 **SMTP Configuration** - Built-in SMTP settings for reliable email delivery
* 📱 **Mobile Responsive** - Works perfectly on all devices
* 🌍 **Translation Ready** - Fully translatable with included language files
* 🛡️ **GDPR Friendly** - Collects only essential information
* ⚡ **Lightweight** - Clean code with minimal impact on site performance
* 🎨 **Customizable** - Configure email templates, messages, and button text

= How It Works =

**For Customers:**

1. Customer visits an out-of-stock product page
2. A notification form appears asking for name, email, and optional phone number
3. Customer submits the subscription request
4. When product is restocked, customer receives an automatic email notification
5. Email includes product details, price, and "Buy Now" button
6. Customers can unsubscribe via link in the email

**For Store Owners:**

1. View subscription statistics in the admin dashboard
2. Manage all requests (search, filter, delete)
3. Configure SMTP settings for reliable delivery
4. Customize email templates and messages
5. Send test emails to verify configuration
6. Track which notifications have been sent

= Admin Features =

* **Dashboard** - View total subscriptions, pending requests, and sent notifications
* **Requests Management** - Browse, search, filter, and delete subscription requests
* **Settings Panel** - Configure SMTP, email templates, and notification preferences
* **Test Email** - Verify your SMTP configuration works correctly
* **Bulk Actions** - Delete multiple requests at once

= Requirements =

* WordPress 5.8 or higher
* WooCommerce 5.0 or higher
* PHP 7.4 or higher

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Go to **Plugins > Add New**
3. Search for "Stock Notifications Pro"
4. Click **Install Now** and then **Activate**

= Manual Installation =

1. Download the plugin ZIP file
2. Log in to your WordPress admin panel
3. Go to **Plugins > Add New > Upload Plugin**
4. Choose the downloaded ZIP file and click **Install Now**
5. After installation, click **Activate Plugin**

= After Activation =

1. Make sure WooCommerce is installed and active
2. Go to **Stock Notifications** in your admin menu
3. Configure your SMTP settings in the **Settings** tab
4. Customize email templates and messages
5. Send a test email to verify everything works
6. Mark a product as out of stock to test the subscription form

== Frequently Asked Questions ==

= Do I need WooCommerce for this plugin to work? =

Yes, this plugin requires WooCommerce to be installed and active. It is specifically designed to work with WooCommerce products.

= How are notifications sent? =

The plugin automatically monitors WooCommerce product stock changes. When a product's stock status changes to "In Stock", it sends email notifications to all subscribers who haven't been notified yet.

= Can I customize the email template? =

Yes! You can customize the email subject template, success/error messages, button text, and other notification preferences from the Settings page. Advanced users can also override email templates by copying them to their theme directory.

= What happens when I deactivate the plugin? =

Deactivation only clears temporary data (transients). Your subscription data and settings are preserved so you can reactivate the plugin without losing data.

= What happens when I delete the plugin? =

When you delete (uninstall) the plugin from the WordPress admin, all plugin data is permanently removed, including:
* Custom database table (stock_notifications)
* Plugin settings (snp_options)
* All subscription requests
* Temporary data (transients)

This ensures a clean removal if you decide the plugin isn't right for you.

= Does this work with variable products? =

Yes, the plugin supports both simple and variable products. Customers can subscribe to specific product variations.

= Is this plugin GDPR compliant? =

The plugin is designed with privacy in mind. It collects only essential information (name, email, optional phone) needed for notifications. Customers can unsubscribe at any time via the link in notification emails. All data is removed when the plugin is uninstalled.

= Can customers unsubscribe? =

Yes, every notification email includes an unsubscribe link that allows customers to opt out of future notifications for that product.

= What if emails aren't being delivered? =

1. Verify your SMTP settings in the Settings tab
2. Use the Test Email feature to check your configuration
3. Check your WordPress debug.log for any errors
4. Make sure your SMTP credentials are correct
5. Try using an SMTP plugin like WP Mail SMTP as an alternative

= Can I use this without SMTP? =

Yes, but we highly recommend configuring SMTP for reliable delivery. Without SMTP, the plugin will use WordPress's default wp_mail() function, which may not be as reliable depending on your hosting setup.

= Is there a limit to how many subscribers can be notified? =

No, there's no built-in limit. However, sending many emails at once depends on your SMTP provider's limits. Most providers allow thousands of emails per day.

= Will this work with my theme? =

Yes, the plugin is designed to work with any properly coded WordPress theme. The subscription form integrates seamlessly with WooCommerce product pages.

= Can I translate the plugin? =

Absolutely! The plugin is translation-ready with the `stock-notifier` text domain. You can use tools like Loco Translate or Poedit to create translations, or place translation files in the plugin's `/languages` directory.

== Screenshots ==

1. Subscription form displayed on out-of-stock product pages
2. Admin dashboard showing statistics and recent activity
3. Requests management page with search and filter options
4. Settings page for SMTP configuration and email templates
5. Test email feature for verifying SMTP setup
6. Example notification email received by customers

== Changelog ==

= 1.0.0 - 2025-11-20 =
* **Major Release** - Complete plugin refactoring
* Implemented PSR-4 compliant architecture
* Added proper namespace structure (StockNotificationsPro)
* Organized code into modular classes (Admin, Public, Mail, Database, Helpers)
* Introduced Composer for dependency management
* Created dedicated template system for emails and forms
* Improved code documentation with comprehensive docblocks
* Enhanced uninstall cleanup for proper data removal
* Fixed textdomain loading for translation compatibility
* Improved SMTP configuration and email delivery
* Added admin dashboard with statistics
* Enhanced subscription form with better validation
* Improved error handling throughout the plugin
* Better compatibility with WordPress and WooCommerce standards
* Maintained 100% backward compatibility with previous versions

== Upgrade Notice ==

= 1.0.0 =
Major architectural improvements with PSR-4 structure and enhanced reliability. Fully backward compatible. Update recommended for better performance and maintainability.

== Privacy Policy ==

Stock Notifications Pro collects and stores the following customer information when they subscribe for back-in-stock notifications:

* Email address (required) - Used to send notification emails
* Name (required) - Used for email personalization
* Phone number (optional) - Stored but not currently used
* Product ID - To know which product the customer is interested in
* Subscription date - For record keeping
* Notification status - To track if notification has been sent

This data is:
* Stored in a custom database table (stock_notifications)
* Used solely for sending one-time back-in-stock notifications
* Not shared with any third parties
* Permanently deleted when the plugin is uninstalled
* Can be removed at customer request via unsubscribe link

== Support ==

For support, bug reports, or feature requests:

* **GitHub Issues**: https://github.com/SaintHossam/stock-notifications-pro/issues
* **Author**: https://github.com/SaintHossam/

== Developer Information ==

This plugin follows WordPress and WooCommerce coding standards and uses a modern PSR-4 autoloading structure. Developers can extend the plugin using standard WordPress hooks and filters.

For more information, visit: https://github.com/SaintHossam/stock-notifications-pro

== Credits ==

Developed by Hossam Hamdy (SaintHossam)
