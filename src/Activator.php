<?php
/**
 * Plugin Activation Handler
 *
 * @package StockNotificationsPro
 */

namespace StockNotificationsPro;

use StockNotificationsPro\Database\Schema;
use StockNotificationsPro\Helpers\Functions;

/**
 * Class Activator
 *
 * Handles plugin activation tasks.
 */
class Activator {

    /**
     * Activate the plugin
     *
     * Creates database table and sets default options.
     *
     * @return void
     */
    public static function activate() {
        // Create database table
        Schema::create_table();

        // Set default options if not exists
        if (!get_option('snp_options')) {
            update_option('snp_options', Functions::get_defaults());
        }

        // Clear any existing transients
        delete_transient('snp_table_checked');
    }
}
