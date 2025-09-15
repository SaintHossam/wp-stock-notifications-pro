# Stock Notifications Pro (WordPress/WooCommerce)

Back-in-stock alerts for WooCommerce. Let shoppers subscribe to out-of-stock products and automatically email them when items are restocked. Includes a clean admin settings panel and SMTP configuration for reliable delivery.

- **Author:** Hossam Hamdy (SaintHossam)  
- **Plugin URI / Repository:** https://github.com/SaintHossam/wp-stock-notifications-pro  
- **Author URI:** https://github.com/SaintHossam/  
- **Text Domain:** `stock-notifier`

---

## Features
- “Notify me when available” subscription on product pages
- Automatic email alerts when stock returns
- Admin dashboard to view/manage subscriptions
- Built-in SMTP settings (or use your site’s SMTP plugin)
- GDPR-friendly: collects only what’s needed to deliver the alert
- Translation-ready (`stock-notifier`)

## Requirements
- WordPress 5.8+
- WooCommerce 5.0+
- PHP 7.4+

## Installation
1. Download or clone the repo into:  
   `wp-content/plugins/stock-notifications-pro/`
2. In **WP Admin → Plugins**, activate **Stock Notifications Pro**.
3. Go to **Settings → Stock Notifier** (or **WooCommerce → Settings → Stock Notifier**) to configure.

## Quick Setup
1. **SMTP**: Enter your SMTP host, port, username, and encryption in the plugin settings (or rely on an SMTP plugin like WP Mail SMTP).  
2. **Email Template**: Customize subject, heading, and body placeholders (e.g., `{product_name}`, `{product_url}`) if provided in your build.  
3. **Test**: Subscribe to an out-of-stock product, then mark it **In stock** and confirm you receive the email.

## How it Works
- Shoppers enter their email on an out-of-stock product page.
- When the product’s stock status changes to **in stock**, an email is sent automatically.
- The subscription is optionally removed after notification (recommended to avoid duplicates).

## Screenshots (placeholders)
- `assets/screenshot-1.png` — Product page subscription form  
- `assets/screenshot-2.png` — Admin: subscriptions list  
- `assets/screenshot-3.png` — Example notification email

## Developer Notes

### Plugin Header (put this in your main PHP file)
```php
<?php
/**
 * Plugin Name: Stock Notifications Pro
 * Description: Back-in-stock alerts for WooCommerce with admin settings and SMTP delivery.
 * Version: 1.0.0
 * Author: Hossam Hamdy (SaintHossam)
 * Plugin URI: https://github.com/SaintHossam/wp-stock-notifications-pro
 * Author URI: https://github.com/SaintHossam/
 * Text Domain: stock-notifier
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 */
```
### Translations
- Text domain: `stock-notifier`  
- Load from `/languages`.  
- Generate a POT file (example):  
```bash
wp i18n make-pot . languages/stock-notifier.pot
```

### (Optional) Hooks — adjust to match your code
```php
/**
 * Filters the email subject before sending.
 * @param string $subject
 * @param WC_Product $product
 * @param array $subscriber
 */
apply_filters('stock_notifier_email_subject', $subject, $product, $subscriber);

/**
 * Fires after a notification email is sent.
 * @param int $product_id
 * @param string $email
 */
do_action('stock_notifier_notification_sent', $product_id, $email);
```

## Privacy
This plugin stores the minimum subscriber data required to deliver one-time back-in-stock emails and provides a one-click unsubscribe link (if enabled).

## Troubleshooting
- **No emails arriving** → verify SMTP credentials and try a test email.  
- **Emails in spam** → set a valid From name/address and configure SPF/DKIM on your domain.  
- **Large queues** → ensure WP-Cron is running or set a real server cron to call `wp-cron.php`.

## Contributing
Issues and PRs are welcome. Use feature branches and clear commit messages.

## License
GPL-2.0-or-later
