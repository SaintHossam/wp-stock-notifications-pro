<?php
/**
 * Uninstall Script
 *
 * Fired when the plugin is uninstalled.
 *
 * @package WPStockNotificationsPro
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('snp_options');

// Delete transients
delete_transient('snp_table_checked');

// Drop database table
// Try to use the Schema class if autoloader is available, otherwise use direct SQL
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    
    if (class_exists('WPStockNotificationsPro\Database\Schema')) {
        WPStockNotificationsPro\Database\Schema::drop_table();
    } else {
        // Fallback to direct SQL if class cannot be loaded
        global $wpdb;
        $table_name = $wpdb->prefix . 'stock_notifications';
        $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
    }
} else {
    // Fallback to direct SQL when autoloader is not available
    global $wpdb;
    $table_name = $wpdb->prefix . 'stock_notifications';
    $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
}

// Delete any post meta if stored
// (Currently not used, but available for future use)
