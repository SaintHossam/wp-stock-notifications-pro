<?php
/**
 * Plugin Deactivation Handler
 *
 * @package WPStockNotificationsPro
 */

namespace WPStockNotificationsPro;

/**
 * Class Deactivator
 *
 * Handles plugin deactivation tasks.
 */
class Deactivator {

    /**
     * Deactivate the plugin
     *
     * Cleans up transients and temporary data.
     *
     * @return void
     */
    public static function deactivate() {
        // Clear transients
        delete_transient('snp_table_checked');

        // Note: We don't delete the database table or options here
        // That's handled in uninstall.php if the plugin is uninstalled
    }
}
