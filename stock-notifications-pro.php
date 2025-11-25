<?php

/**
 * Plugin Name: Stock Notifications Pro
 * Description: Back-in-stock alerts for WooCommerce with admin settings and SMTP delivery.
 * Version: 1.0.0
 * Author: Hossam Hamdy (SaintHossam)
 * Plugin URI: https://github.com/SaintHossam/stock-notifications-pro
 * Author URI: https://github.com/SaintHossam/
 * Text Domain: stock-notifications-pro
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
if (! defined('ABSPATH')) {
    exit;
}

// Define plugin constants.
define('stock_notifications_PRO_VERSION', '1.0.0');
define('stock_notifications_PRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('stock_notifications_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));

// Check for Composer autoloader.
if (! file_exists(__DIR__ . '/vendor/autoload.php')) {
    add_action(
        'admin_notices',
        function () {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p><strong>' . esc_html__('Stock Notifications Pro:', 'stock-notifications-pro') . '</strong> ';
            echo esc_html__('Missing dependencies. This plugin requires the Composer autoloader.', 'stock-notifications-pro');
            echo '</p>';

            echo '<p><strong>' . esc_html__('For Users:', 'stock-notifications-pro') . '</strong> ';
            echo esc_html__('Please download the pre-built release from', 'stock-notifications-pro');
            echo ' <a href="' . esc_url('https://github.com/SaintHossam/stock-notifications-pro/releases') . '" target="_blank">';
            echo esc_html__('GitHub Releases', 'stock-notifications-pro');
            echo '</a> ';
            echo esc_html__('instead of the repository source ZIP.', 'stock-notifications-pro');
            echo '</p>';

            echo '<p><strong>' . esc_html__('For Developers:', 'stock-notifications-pro') . '</strong> ';
            echo esc_html__('Run', 'stock-notifications-pro');
            echo ' <code>composer install --no-dev</code> ';
            echo esc_html__('in the plugin directory:', 'stock-notifications-pro');
            echo ' <code>' . esc_html(plugin_dir_path(__FILE__)) . '</code></p>';
            echo '</div>';
        }
    );

    // Deactivate the plugin.
    add_action(
        'admin_init',
        function () {
            deactivate_plugins(plugin_basename(__FILE__));

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only unsetting the 'activate' flag to suppress the activation message.
            if (isset($_GET['activate'])) {
                unset($_GET['activate']);
            }
        }
    );

    return;
}

// Load Composer autoloader.
require_once __DIR__ . '/vendor/autoload.php';

// Activation hook.
register_activation_hook(__FILE__, array( 'StockNotificationsPro\Activator', 'activate' ));

// Deactivation hook.
register_deactivation_hook(__FILE__, array( 'StockNotificationsPro\Deactivator', 'deactivate' ));

// Initialize the plugin.
add_action(
    'plugins_loaded',
    function () {
        // Check if WooCommerce is active.
        if (! class_exists('WooCommerce')) {
            add_action(
                'admin_notices',
                function () {
                    echo '<div class="notice notice-error"><p>';
                    echo esc_html__('Stock Notifications Pro requires WooCommerce to be installed and active.', 'stock-notifications-pro');
                    echo '</p></div>';
                }
            );

            return;
        }

        // Initialize the main plugin class.
        \StockNotificationsPro\Plugin::get_instance();
    }
);
