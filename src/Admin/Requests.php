<?php
/**
 * Admin Requests Handler
 *
 * @package WPStockNotificationsPro
 */

namespace WPStockNotificationsPro\Admin;

use WPStockNotificationsPro\Database\Schema;

/**
 * Class Requests
 *
 * Handles requests list and management.
 */
class Requests {

    /**
     * Render requests page
     *
     * @return void
     */
    public function render() {
        global $wpdb;
        $table = Schema::get_table_name();

        $status = isset($_GET['status']) ? sanitize_key($_GET['status']) : 'all';
        $query = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';

        $where = '1=1';
        if ($status === 'pending') {
            $where .= ' AND is_notified=0 AND unsubscribed=0';
        } elseif ($status === 'sent') {
            $where .= ' AND is_notified=1';
        } elseif ($status === 'unsub') {
            $where .= ' AND unsubscribed=1';
        }

        if ($query) {
            $where .= $wpdb->prepare(
                ' AND (user_email LIKE %s OR user_name LIKE %s)',
                '%' . $wpdb->esc_like($query) . '%',
                '%' . $wpdb->esc_like($query) . '%'
            );
        }

        $rows = $wpdb->get_results("SELECT * FROM {$table} WHERE {$where} ORDER BY date_registered DESC LIMIT 100");
        $base = admin_url('admin.php?page=snp-stock&tab=requests');

        echo '<div class="snp-card"><h2>إدارة الطلبات</h2>';

        // Filter form
        echo '<form method="get" style="margin:0 0 12px 0">';
        echo '<input type="hidden" name="page" value="snp-stock">';
        echo '<input type="hidden" name="tab" value="requests">';
        
        echo '<div class="snp-row">';
        echo '<label class="snp-label">حالة</label>';
        echo '<select class="snp-input" name="status">';
        echo '<option value="all" ' . selected($status, 'all', false) . '>الكل</option>';
        echo '<option value="pending" ' . selected($status, 'pending', false) . '>في الانتظار</option>';
        echo '<option value="sent" ' . selected($status, 'sent', false) . '>تم الإرسال</option>';
        echo '<option value="unsub" ' . selected($status, 'unsub', false) . '>ألغى الاشتراك</option>';
        echo '</select>';
        echo '</div>';

        echo '<div class="snp-row">';
        echo '<label class="snp-label">بحث</label>';
        echo '<input class="snp-input" type="text" name="q" value="' . esc_attr($query) . '" placeholder="اسم أو بريد">';
        echo '</div>';

        echo '<p><button class="snp-btn" type="submit">تحديث</button></p>';
        echo '</form>';

        // Bulk delete form
        echo '<form method="post">';
        wp_nonce_field('snp_bulk_delete');
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th style="width:30px"><input type="checkbox" onclick="jQuery(\'.snp-chk\').prop(\'checked\',this.checked)"></th>';
        echo '<th>المنتج</th><th>العميل</th><th>البريد</th><th>الهاتف</th><th>التاريخ</th><th>إجراء</th>';
        echo '</tr></thead><tbody>';

        if ($rows) {
            foreach ($rows as $row) {
                $product = wc_get_product($row->product_id);
                $delete_url = wp_nonce_url(
                    $base . '&action=snp_delete&id=' . $row->id,
                    'snp_delete_' . $row->id
                );

                echo '<tr>';
                echo '<td><input class="snp-chk" type="checkbox" name="ids[]" value="' . esc_attr($row->id) . '"></td>';
                echo '<td>' . ($product ? esc_html($product->get_name()) : 'منتج محذوف') . '</td>';
                echo '<td>' . esc_html($row->user_name) . '</td>';
                echo '<td>' . esc_html($row->user_email) . '</td>';
                echo '<td>' . ($row->phone ? esc_html($row->phone) : '-') . '</td>';
                echo '<td>' . esc_html(date('Y-m-d H:i', strtotime($row->date_registered))) . '</td>';
                echo '<td><a class="snp-danger" href="' . esc_url($delete_url) . '" onclick="return confirm(\'حذف هذا الطلب؟\');">حذف</a></td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="7">لا توجد نتائج.</td></tr>';
        }

        echo '</tbody></table>';
        echo '<p><button class="snp-btn" type="submit" name="snp_bulk_delete" value="1" onclick="return confirm(\'حذف كل المحدد؟\');">حذف المحدد</button></p>';
        echo '</form></div>';
    }

    /**
     * Handle single delete action
     *
     * @return void
     */
    public function handle_delete_action() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'snp-stock') {
            return;
        }

        if (!isset($_GET['action']) || $_GET['action'] !== 'snp_delete') {
            return;
        }

        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if (!$id) {
            return;
        }

        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'snp_delete_' . $id)) {
            return;
        }

        global $wpdb;
        $wpdb->delete(Schema::get_table_name(), array('id' => $id), array('%d'));

        wp_safe_redirect(
            remove_query_arg(array('action', 'id', '_wpnonce')) . '&deleted=1'
        );
        exit;
    }

    /**
     * Handle bulk delete action
     *
     * @return void
     */
    public function handle_bulk_delete() {
        if (!isset($_POST['snp_bulk_delete'])) {
            return;
        }

        if (!check_admin_referer('snp_bulk_delete')) {
            return;
        }

        $ids = array_map('intval', $_POST['ids'] ?? array());
        
        if (empty($ids)) {
            return;
        }

        global $wpdb;
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM " . Schema::get_table_name() . " WHERE id IN ($placeholders)",
                $ids
            )
        );

        wp_safe_redirect(
            admin_url('admin.php?page=snp-stock&tab=requests&deleted=' . count($ids))
        );
        exit;
    }
}
