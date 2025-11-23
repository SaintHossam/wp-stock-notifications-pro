<?php
/**
 * Admin Dashboard Handler
 *
 * @package WPStockNotificationsPro
 */

namespace WPStockNotificationsPro\Admin;

use WPStockNotificationsPro\Database\Schema;

/**
 * Class Dashboard
 *
 * Handles admin dashboard display.
 */
class Dashboard {

    /**
     * Render dashboard
     *
     * @return void
     */
    public function render() {
        global $wpdb;
        $table = Schema::get_table_name();

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        $pending = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE is_notified=0 AND unsubscribed=0");
        $sent = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE is_notified=1");

        $rows_pending = $wpdb->get_results("SELECT * FROM {$table} WHERE is_notified=0 AND unsubscribed=0 ORDER BY date_registered DESC LIMIT 20");
        $rows_sent = $wpdb->get_results("SELECT * FROM {$table} WHERE is_notified=1 ORDER BY date_registered DESC LIMIT 20");

        $base = admin_url('admin.php?page=snp-stock&tab=dashboard');

        // Stats grid
        echo '<div class="snp-grid">';
        echo '<div class="snp-stat"><div>إجمالي الطلبات</div><h2>' . $total . '</h2></div>';
        echo '<div class="snp-stat"><div>في الانتظار</div><h2>' . $pending . '</h2></div>';
        echo '<div class="snp-stat"><div>تم الإرسال</div><h2>' . $sent . '</h2></div>';
        echo '</div>';

        // Pending requests
        echo '<div class="snp-card"><h2>أحدث الطلبات المعلقة</h2>';
        $this->render_table($rows_pending, $base);
        echo '</div>';

        // Sent requests
        echo '<div class="snp-card"><h2>أحدث الطلبات التي تم إرسالها</h2>';
        $this->render_table($rows_sent, $base);
        echo '</div>';
    }

    /**
     * Render data table
     *
     * @param array $rows Data rows.
     * @param string $base Base URL.
     * @return void
     */
    private function render_table($rows, $base) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>المنتج</th><th>العميل</th><th>البريد</th><th>الهاتف</th><th>التاريخ</th><th>إجراء</th></tr></thead>';
        echo '<tbody>';

        if ($rows) {
            foreach ($rows as $row) {
                $product = wc_get_product($row->product_id);
                $delete_url = wp_nonce_url(
                    $base . '&action=snp_delete&id=' . $row->id,
                    'snp_delete_' . $row->id
                );

                echo '<tr>';
                echo '<td>' . ($product ? esc_html($product->get_name()) : 'منتج محذوف') . '</td>';
                echo '<td>' . esc_html($row->user_name) . '</td>';
                echo '<td>' . esc_html($row->user_email) . '</td>';
                echo '<td>' . ($row->phone ? esc_html($row->phone) : '-') . '</td>';
                echo '<td>' . esc_html(date('Y-m-d H:i', strtotime($row->date_registered))) . '</td>';
                echo '<td><a class="snp-danger" href="' . esc_url($delete_url) . '" onclick="return confirm(\'حذف هذا الطلب؟\');">حذف</a></td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="6">لا توجد نتائج.</td></tr>';
        }

        echo '</tbody></table>';
    }
}
