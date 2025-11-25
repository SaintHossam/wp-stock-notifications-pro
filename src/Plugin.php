<?php

/**
 * Main Plugin Class
 *
 * @package StockNotificationsPro
 */

namespace StockNotificationsPro;

use StockNotificationsPro\Admin\Menu;
use StockNotificationsPro\Admin\Settings;
use StockNotificationsPro\Public\Frontend;
use StockNotificationsPro\Mail\Mailer;

/**
 * Class Plugin
 *
 * Main plugin bootstrap class that initializes and registers all components.
 */
class Plugin
{
    /**
     * Plugin version.
     *
     * @var string
     */
    public const VERSION = '1.0.0';

    /**
     * Plugin text domain.
     *
     * @var string
     */
    public const TEXT_DOMAIN = 'stock-notifications-pro';

    /**
     * Plugin instance.
     *
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * Frontend instance.
     *
     * @var Frontend
     */
    private $frontend;

    /**
     * Admin Menu instance.
     *
     * @var Menu
     */
    private $admin_menu;

    /**
     * Settings instance.
     *
     * @var Settings
     */
    private $settings;

    /**
     * Mailer instance.
     *
     * @var Mailer
     */
    private $mailer;

    /**
     * Get plugin instance (Singleton pattern).
     *
     * @return Plugin
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Plugin constructor - private to enforce singleton.
     */
    private function __construct()
    {
        $this->init();
    }

    /**
     * Initialize the plugin.
     *
     * @return void
     */
    private function init()
    {
        // Initialize components.
        $this->mailer    = new Mailer();
        $this->frontend  = new Frontend();
        $this->settings  = new Settings();
        $this->admin_menu = new Menu();

        // Register hooks.
        $this->register_hooks();

        // Check and create/update database table periodically.
        add_action('init', array( $this, 'check_database_table' ));
    }

    /**
     * Register all plugin hooks.
     *
     * @return void
     */
    private function register_hooks()
    {
        // Frontend hooks.
        $this->frontend->register_hooks();

        // Admin hooks.
        $this->admin_menu->register_hooks();
        $this->settings->register_hooks();

        // Mailer hooks.
        $this->mailer->register_hooks();
    }

    /**
     * Check and ensure database table exists.
     *
     * @return void
     */
    public function check_database_table()
    {
        if (! get_transient('snp_table_checked')) {
            \StockNotificationsPro\Activator::activate();
            set_transient('snp_table_checked', 1, 12 * HOUR_IN_SECONDS);
        }
    }

    /**
     * Get plugin version.
     *
     * @return string
     */
    public static function get_version()
    {
        return self::VERSION;
    }

    /**
     * Get plugin text domain.
     *
     * @return string
     */
    public static function get_text_domain()
    {
        return self::TEXT_DOMAIN;
    }
}
