<?php

/**
 * Admin Requests Handler
 *
 * @package StockNotificationsPro
 */

namespace StockNotificationsPro\Admin;

use StockNotificationsPro\Database\Schema;

/**
 * Class Requests
 *
 * Handles requests list and management.
 */
class Requests
{
    /**
     * Render requests page.
     *
     * @return void
     */
    public function render()
    {
        global $wpdb;

        $table = Schema::get_table_name();

        // Read-only filters from GET (view context).
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Using GET parameters only to filter the view.
        $status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : 'all';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Using GET parameters only to filter the view.
        $query  = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';

        // تحضير قيمة LIKE لو فيه بحث.
        $like = '';
        if ('' !== $query) {
            $like = '%' . $wpdb->esc_like($query) . '%';
        }

        /**
         * نبني الـ SQL بشكل صريح لكل حالة عشان:
         * - PHPCS يفهم الـ placeholders.
         * - ما يبقاش فيه prepare مع array params (UnfinishedPrepare).
         */
        switch ($status) {
            case 'pending':
                if ('' !== $query) {
                    // مع حالة pending + بحث بالاسم/الإيميل.
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $sql = $wpdb->prepare(
                        "SELECT * FROM {$table} WHERE is_notified = 0 AND unsubscribed = 0 AND (user_email LIKE %s OR user_name LIKE %s) ORDER BY date_registered DESC LIMIT 100",
                        $like,
                        $like
                    );
                } else {
                    // pending فقط بدون بحث.
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $sql = "SELECT * FROM {$table} WHERE is_notified = 0 AND unsubscribed = 0 ORDER BY date_registered DESC LIMIT 100";
                }
                break;

            case 'sent':
                if ('' !== $query) {
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $sql = $wpdb->prepare(
                        "SELECT * FROM {$table} WHERE is_notified = 1 AND (user_email LIKE %s OR user_name LIKE %s) ORDER BY date_registered DESC LIMIT 100",
                        $like,
                        $like
                    );
                } else {
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $sql = "SELECT * FROM {$table} WHERE is_notified = 1 ORDER BY date_registered DESC LIMIT 100";
                }
                break;

            case 'unsub':
                if ('' !== $query) {
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $sql = $wpdb->prepare(
                        "SELECT * FROM {$table} WHERE unsubscribed = 1 AND (user_email LIKE %s OR user_name LIKE %s) ORDER BY date_registered DESC LIMIT 100",
                        $like,
                        $like
                    );
                } else {
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $sql = "SELECT * FROM {$table} WHERE unsubscribed = 1 ORDER BY date_registered DESC LIMIT 100";
                }
                break;

            case 'all':
            default:
                if ('' !== $query) {
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $sql = $wpdb->prepare(
                        "SELECT * FROM {$table} WHERE (user_email LIKE %s OR user_name LIKE %s) ORDER BY date_registered DESC LIMIT 100",
                        $like,
                        $like
                    );
                } else {
                    // كل الطلبات بدون أي فلتر.
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $sql = "SELECT * FROM {$table} ORDER BY date_registered DESC LIMIT 100";
                }
                break;
        }

        // تنفيذ الاستعلام النهائي.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.PreparedSQL.NotPrepared
        $rows = $wpdb->get_results($sql);

        $base = admin_url('admin.php?page=snp-stock&tab=requests');

        echo '<div class="snp-card"><h2>' . esc_html__('إدارة الطلبات', 'stock-notifications-pro') . '</h2>';

        // Filter form.
        echo '<form method="get" style="margin:0 0 12px 0">';
        echo '<input type="hidden" name="page" value="snp-stock" />';
        echo '<input type="hidden" name="tab" value="requests" />';

        // Status field.
        echo '<div class="snp-row">';
        echo '<label class="snp-label" for="snp-status-filter">' . esc_html__('حالة', 'stock-notifications-pro') . '</label>';
        echo '<select class="snp-input" name="status" id="snp-status-filter">';
        echo '<option value="all" ' . selected($status, 'all', false) . '>' . esc_html__('الكل', 'stock-notifications-pro') . '</option>';
        echo '<option value="pending" ' . selected($status, 'pending', false) . '>' . esc_html__('في الانتظار', 'stock-notifications-pro') . '</option>';
        echo '<option value="sent" ' . selected($status, 'sent', false) . '>' . esc_html__('تم الإرسال', 'stock-notifications-pro') . '</option>';
        echo '<option value="unsub" ' . selected($status, 'unsub', false) . '>' . esc_html__('ألغى الاشتراك', 'stock-notifications-pro') . '</option>';
        echo '</select>';
        echo '</div>';

        // Search field.
        echo '<div class="snp-row">';
        echo '<label class="snp-label" for="snp-search-q">' . esc_html__('بحث', 'stock-notifications-pro') . '</label>';
        echo '<input class="snp-input" type="text" id="snp-search-q" name="q" value="' . esc_attr($query) . '" placeholder="' . esc_attr__('اسم أو بريد', 'stock-notifications-pro') . '" />';
        echo '</div>';

        echo '<p><button class="snp-btn" type="submit">' . esc_html__('تحديث', 'stock-notifications-pro') . '</button></p>';
        echo '</form>';

        // Bulk delete form.
        echo '<form method="post">';
        wp_nonce_field('snp_bulk_delete');

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th style="width:30px"><input type="checkbox" onclick="jQuery(\'.snp-chk\').prop(\'checked\', this.checked);" /></th>';
        echo '<th>' . esc_html__('المنتج', 'stock-notifications-pro') . '</th>';
        echo '<th>' . esc_html__('العميل', 'stock-notifications-pro') . '</th>';
        echo '<th>' . esc_html__('البريد', 'stock-notifications-pro') . '</th>';
        echo '<th>' . esc_html__('الهاتف', 'stock-notifications-pro') . '</th>';
        echo '<th>' . esc_html__('التاريخ', 'stock-notifications-pro') . '</th>';
        echo '<th>' . esc_html__('إجراء', 'stock-notifications-pro') . '</th>';
        echo '</tr></thead><tbody>';

        if ($rows) {
            foreach ($rows as $row) {
                $product = wc_get_product($row->product_id);
                $id      = (int) $row->id;

                $delete_url = wp_nonce_url(
                    add_query_arg(
                        array(
                            'action' => 'snp_delete',
                            'id'     => $id,
                        ),
                        $base
                    ),
                    'snp_delete_' . $id
                );

                $date = '';
                if (! empty($row->date_registered)) {
                    $timestamp = strtotime($row->date_registered);
                    if ($timestamp) {
                        $date = wp_date('Y-m-d H:i', $timestamp);
                    }
                }

                echo '<tr>';
                echo '<td><input class="snp-chk" type="checkbox" name="ids[]" value="' . esc_attr($id) . '" /></td>';
                echo '<td>' . ($product ? esc_html($product->get_name()) : esc_html__('منتج محذوف', 'stock-notifications-pro')) . '</td>';
                echo '<td>' . esc_html((string) $row->user_name) . '</td>';
                echo '<td>' . esc_html((string) $row->user_email) . '</td>';
                echo '<td>' . ($row->phone ? esc_html((string) $row->phone) : esc_html('-')) . '</td>';
                echo '<td>' . esc_html($date) . '</td>';
                echo '<td><a class="snp-danger" href="' . esc_url($delete_url) . '" onclick="return confirm(\'' . esc_js(__('حذف هذا الطلب؟', 'stock-notifications-pro')) . '\');">' . esc_html__('حذف', 'stock-notifications-pro') . '</a></td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="7">' . esc_html__('لا توجد نتائج.', 'stock-notifications-pro') . '</td></tr>';
        }

        echo '</tbody></table>';
        echo '<p><button class="snp-btn" type="submit" name="snp_bulk_delete" value="1" onclick="return confirm(\'' . esc_js(__('حذف كل المحدد؟', 'stock-notifications-pro')) . '\');">' . esc_html__('حذف المحدد', 'stock-notifications-pro') . '</button></p>';
        echo '</form></div>';
    }

    /**
     * Handle single delete action.
     *
     * @return void
     */
    public function handle_delete_action()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Action handler validates via nonce below.
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if ('snp-stock' !== $page) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Action handler validates via nonce below.
        $action = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : '';
        if ('snp_delete' !== $action) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Action handler validates via nonce below.
        $id = isset($_GET['id']) ? absint(wp_unslash($_GET['id'])) : 0;
        if (! $id) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is verified, not used for output.
        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
        if (! $nonce || ! wp_verify_nonce($nonce, 'snp_delete_' . $id)) {
            return;
        }

        global $wpdb;

        $table = Schema::get_table_name();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
        $wpdb->delete(
            $table,
            array( 'id' => $id ),
            array( '%d' )
        );

        $redirect_url = add_query_arg(
            array(
                'page'    => 'snp-stock',
                'tab'     => 'requests',
                'deleted' => 1,
            ),
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Handle bulk delete action.
     *
     * @return void
     */
    public function handle_bulk_delete()
    {
        if (! isset($_POST['snp_bulk_delete'])) {
            return;
        }

        if (! check_admin_referer('snp_bulk_delete')) {
            return;
        }

        // التحقق من nonce تم بالفعل، وننظّف الـ input في نفس السطر.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified via check_admin_referer above.
        $ids = isset($_POST['ids']) ? array_map('absint', (array) wp_unslash($_POST['ids'])) : array();
        $ids = array_filter($ids);

        if (empty($ids)) {
            return;
        }

        global $wpdb;

        $table = Schema::get_table_name();

        // استخدام wpdb::delete لكل ID لتفادي مشاكل prepare مع IN().
        foreach ($ids as $id) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
            $wpdb->delete(
                $table,
                array( 'id' => $id ),
                array( '%d' )
            );
        }

        $redirect_url = add_query_arg(
            array(
                'page'    => 'snp-stock',
                'tab'     => 'requests',
                'deleted' => count($ids),
            ),
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect_url);
        exit;
    }
}
