<?php
/**
 * Admin Menu Handler
 *
 * @package WPStockNotificationsPro
 */

namespace WPStockNotificationsPro\Admin;

use WPStockNotificationsPro\Admin\Dashboard;
use WPStockNotificationsPro\Admin\Settings;
use WPStockNotificationsPro\Admin\Requests;

/**
 * Class Menu
 *
 * Handles admin menu registration and routing.
 */
class Menu {

    /**
     * Dashboard instance
     *
     * @var Dashboard
     */
    private $dashboard;

    /**
     * Requests instance
     *
     * @var Requests
     */
    private $requests;

    /**
     * Settings instance
     *
     * @var Settings
     */
    private $settings;

    /**
     * Constructor
     */
    public function __construct() {
        $this->dashboard = new Dashboard();
        $this->requests = new Requests();
        $this->settings = new Settings();
    }

    /**
     * Register hooks
     *
     * @return void
     */
    public function register_hooks() {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_init', array($this, 'handle_actions'));
    }

    /**
     * Register admin menu
     *
     * @return void
     */
    public function register_menu() {
        add_menu_page(
            'إشعارات المخزون',
            'إشعارات المخزون',
            'manage_woocommerce',
            'snp-stock',
            array($this, 'render_page'),
            'dashicons-bell',
            56
        );
    }

    /**
     * Render admin page
     *
     * @return void
     */
    public function render_page() {
        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'dashboard';

        echo '<div class="wrap snp-wrap"><h1>إشعارات المخزون</h1>';

        if (isset($_GET['deleted'])) {
            echo '<div class="notice notice-success"><p>تم حذف ' . intval($_GET['deleted']) . ' طلب.</p></div>';
        }

        // Render tabs
        $this->render_tabs($tab);

        // Add admin styles
        $this->add_admin_styles();

        // Render tab content
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
     * Render navigation tabs
     *
     * @param string $current_tab Current active tab.
     * @return void
     */
    private function render_tabs($current_tab) {
        echo '<h2 class="nav-tab-wrapper">';
        
        $tabs = array(
            'dashboard' => 'لوحة التحكم',
            'requests' => 'الطلبات',
            'settings' => 'الإعدادات',
            'test' => 'اختبار البريد',
        );

        foreach ($tabs as $tab => $label) {
            $url = admin_url('admin.php?page=snp-stock&tab=' . $tab);
            $active = ($current_tab === $tab) ? 'nav-tab-active' : '';
            echo sprintf(
                '<a href="%s" class="nav-tab %s">%s</a>',
                esc_url($url),
                $active,
                esc_html($label)
            );
        }
        
        echo '</h2>';
    }

    /**
     * Add admin styles
     *
     * @return void
     */
    private function add_admin_styles() {
        echo '<style>
        .snp-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin:20px 0;box-shadow:0 2px 12px rgba(0,0,0,.04)}
        .snp-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
        .snp-stat{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:16px;text-align:center}
        .snp-input{width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px}
        .snp-label{font-weight:600;margin-bottom:6px;display:block}
        .snp-btn{background:#0b74de;color:#fff;border:0;padding:10px 16px;border-radius:8px;cursor:pointer;font-weight:600}
        .snp-row{display:grid;grid-template-columns:1fr 2fr;gap:12px;margin-bottom:12px}
        table.widefat td,table.widefat th{text-align:right}
        .snp-note{font-size:12px;color:#64748b}
        .snp-danger{background:#ef4444;color:#fff;border:0;padding:6px 10px;border-radius:6px}
        </style>';
    }

    /**
     * Handle admin actions
     *
     * @return void
     */
    public function handle_actions() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        $this->requests->handle_delete_action();
        $this->requests->handle_bulk_delete();
    }
}
