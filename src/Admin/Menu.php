<?php

/**
 * Admin Menu Handler
 *
 * @package StockNotificationsPro
 */

namespace StockNotificationsPro\Admin;

use StockNotificationsPro\Admin\Dashboard;
use StockNotificationsPro\Admin\Settings;
use StockNotificationsPro\Admin\Requests;

/**
 * Class Menu
 *
 * Handles admin menu registration and routing.
 */
class Menu
{
    /**
     * Dashboard instance.
     *
     * @var Dashboard
     */
    private $dashboard;

    /**
     * Requests instance.
     *
     * @var Requests
     */
    private $requests;

    /**
     * Settings instance.
     *
     * @var Settings
     */
    private $settings;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->dashboard = new Dashboard();
        $this->requests  = new Requests();
        $this->settings  = new Settings();
    }

    /**
     * Register hooks.
     *
     * @return void
     */
    public function register_hooks()
    {
        add_action('admin_menu', array( $this, 'register_menu' ));

        // هنا مفيش تعامل مباشر مع $_GET/$_POST، بس بنوصل لـ handle_actions().
        add_action('admin_init', array( $this, 'handle_actions' ));

        add_action('admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ));
    }

    /**
     * Register admin menu.
     *
     * @return void
     */
    public function register_menu()
    {
        add_menu_page(
            __('إشعارات المخزون', 'stock-notifications-pro'),
            __('إشعارات المخزون', 'stock-notifications-pro'),
            'manage_woocommerce',
            'snp-stock',
            array( $this, 'render_page' ),
            'dashicons-bell',
            56
        );
    }

    /**
     * Enqueue admin styles for this plugin page only.
     *
     * @param string $hook_suffix Current admin page hook suffix.
     * @return void
     */
    public function enqueue_admin_assets($hook_suffix)
    {
        if ('toplevel_page_snp-stock' !== $hook_suffix) {
            return;
        }

        wp_enqueue_style(
            'stock-notifications-pro-admin',
            stock_notifications_PRO_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            defined('stock_notifications_PRO_VERSION') ? stock_notifications_PRO_VERSION : '1.0.0'
        );
    }

    /**
     * Render admin page.
     *
     * @return void
     */
    public function render_page()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab selection is read-only context.
        $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'dashboard';

        echo '<div class="wrap snp-wrap"><h1>' . esc_html__('إشعارات المخزون', 'stock-notifications-pro') . '</h1>';

        // Show delete notice if present (value already sanitized as int).
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Notice is based on a prior action that should already be nonce-verified.
        if (isset($_GET['deleted'])) {
            $deleted = absint(wp_unslash($_GET['deleted']));

            if ($deleted) {
                echo '<div class="notice notice-success"><p>';
                echo esc_html(
                    sprintf(
                        /* translators: %d: number of deleted requests */
                        __('تم حذف %d طلب.', 'stock-notifications-pro'),
                        $deleted
                    )
                );
                echo '</p></div>';
            }
        }

        // Render tabs.
        $this->render_tabs($tab);

        // Render tab content.
        switch ($tab) {
            case 'settings':
                $this->settings->render();
                break;
            case 'test':
                $this->settings->render_test_email();
                break;
            case 'requests':
                $this->requests->render();
                break;
            default:
                $this->dashboard->render();
                break;
        }

        echo '</div>';
    }

    /**
     * Render navigation tabs.
     *
     * @param string $current_tab Current active tab.
     * @return void
     */
    private function render_tabs($current_tab)
    {
        echo '<h2 class="nav-tab-wrapper">';

        $tabs = array(
            'dashboard' => __('لوحة التحكم', 'stock-notifications-pro'),
            'requests'  => __('الطلبات', 'stock-notifications-pro'),
            'settings'  => __('الإعدادات', 'stock-notifications-pro'),
            'test'      => __('اختبار البريد', 'stock-notifications-pro'),
        );

        foreach ($tabs as $tab => $label) {
            $url    = admin_url('admin.php?page=snp-stock&tab=' . $tab);
            $active = ($current_tab === $tab) ? 'nav-tab-active' : '';

            printf(
                '<a href="%1$s" class="nav-tab %2$s">%3$s</a>',
                esc_url($url),
                esc_attr($active),
                esc_html($label)
            );
        }

        echo '</h2>';
    }

    /**
     * Handle admin actions.
     *
     * @return void
     */
    public function handle_actions()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonces are verified inside Requests::handle_delete_action() and Requests::handle_bulk_delete().
        if (! current_user_can('manage_woocommerce')) {
            return;
        }

        $this->requests->handle_delete_action();
        $this->requests->handle_bulk_delete();
    }
}
