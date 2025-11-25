<?php

/**
 * Uninstall Script
 *
 * Fired when the plugin is uninstalled.
 *
 * @package StockNotificationsPro
 */

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options.
delete_option('snp_options');

// Delete transients.
delete_transient('snp_table_checked');

// Drop database table.
global $wpdb;

// Prefixed global var name for table.
$stock_notifications_pro_table_name = $wpdb->prefix . 'stock_notifications';

// If autoloader available, try to use Schema::drop_table().
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';

    if (class_exists('StockNotificationsPro\Database\Schema')) {
        \StockNotificationsPro\Database\Schema::drop_table();
    } else {
        // Table name مبني من $wpdb->prefix فقط (بدون user input)،
        // فنستخدم PHPCS ignore عشان نوع التحليل الآلي هنا مش فاهم الحالة دي.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.SchemaChange,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.PreparedSQL.NotPrepared
        $wpdb->query('DROP TABLE IF EXISTS `' . $stock_notifications_pro_table_name . '`');
    }
} else {
    // Fallback: direct drop by name.
    // برضه اسم الجدول آمن، فبنضيف NotPrepared للـ ignore.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.SchemaChange,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.PreparedSQL.NotPrepared
    $wpdb->query('DROP TABLE IF EXISTS `' . $stock_notifications_pro_table_name . '`');
}

// Extra cleanup (if needed in future) can be added here.
