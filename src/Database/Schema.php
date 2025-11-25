<?php

/**
 * Database Schema Handler
 *
 * @package StockNotificationsPro
 */

namespace StockNotificationsPro\Database;

/**
 * Class Schema
 *
 * Handles database table creation and management.
 */
class Schema
{
    /**
     * Get the table name with WordPress prefix.
     *
     * @return string
     */
    public static function get_table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'stock_notifications';
    }

    /**
     * Create the database table.
     *
     * @return void
     */
    public static function create_table()
    {
        global $wpdb;

        $table_name      = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE {$table_name} (
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
        ) {$charset_collate};";

        // dbDelta is the recommended way to handle schema changes in WordPress.
        dbDelta($sql);
    }

    /**
     * Drop the database table.
     *
     * @return void
     */
    public static function drop_table()
    {
        global $wpdb;

        $table_name = esc_sql(self::get_table_name());

        // Table name is constructed from the trusted $wpdb->prefix and a hardcoded string.
        // This direct query is acceptable here as part of uninstall/schema management.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
        $wpdb->query("DROP TABLE IF EXISTS `{$table_name}`");
    }
}
