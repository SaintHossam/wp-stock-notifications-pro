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

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

use WPStockNotificationsPro\Database\Schema;

// Delete plugin options
delete_option('snp_options');

// Delete transients
delete_transient('snp_table_checked');

// Drop database table
Schema::drop_table();

// Delete any post meta if stored
// (Currently not used, but available for future use)
