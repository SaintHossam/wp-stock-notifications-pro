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
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WP_STOCK_NOTIFICATIONS_PRO_VERSION', '1.0.0');
define('WP_STOCK_NOTIFICATIONS_PRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_STOCK_NOTIFICATIONS_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load Composer autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Activation hook
register_activation_hook(__FILE__, array('WPStockNotificationsPro\Activator', 'activate'));

// Deactivation hook
register_deactivation_hook(__FILE__, array('WPStockNotificationsPro\Deactivator', 'deactivate'));

// Initialize the plugin
add_action('plugins_loaded', function () {
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('Stock Notifications Pro requires WooCommerce to be installed and active.', 'stock-notifier');
            echo '</p></div>';
        });
        return;
    }

    // Initialize the main plugin class
    \WPStockNotificationsPro\Plugin::get_instance();
});

