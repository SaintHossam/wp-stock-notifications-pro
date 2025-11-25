<?php

/**
 * Admin Dashboard Handler
 *
 * @package StockNotificationsPro
 */

namespace StockNotificationsPro\Admin;

use StockNotificationsPro\Database\Schema;

/**
 * Class Dashboard
 *
 * Shows overview stats and recent requests.
 */
class Dashboard
{
    /**
     * Render dashboard page.
     *
     * @return void
     */
    public function render()
    {
        global $wpdb;

        $table = Schema::get_table_name();

        // Queries for stats (no user input involved).
        $sql_total   = 'SELECT COUNT(*) FROM ' . $table;
        $sql_pending = 'SELECT COUNT(*) FROM ' . $table . ' WHERE is_notified = 0 AND unsubscribed = 0';
        $sql_sent    = 'SELECT COUNT(*) FROM ' . $table . ' WHERE is_notified = 1';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
        $total   = (int) $wpdb->get_var($sql_total);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
        $pending = (int) $wpdb->get_var($sql_pending);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
        $sent    = (int) $wpdb->get_var($sql_sent);

        // Recent pending / sent rows.
        $sql_recent_pending = 'SELECT * FROM ' . $table . ' WHERE is_notified = 0 AND unsubscribed = 0 ORDER BY date_registered DESC LIMIT 20';
        $sql_recent_sent    = 'SELECT * FROM ' . $table . ' WHERE is_notified = 1 ORDER BY date_registered DESC LIMIT 20';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
        $recent_pending = $wpdb->get_results($sql_recent_pending);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
        $recent_sent    = $wpdb->get_results($sql_recent_sent);

        echo '<div class="snp-card snp-dashboard">';

        echo '<h2>' . esc_html__('نظرة عامة', 'stock-notifications-pro') . '</h2>';

        echo '<div class="snp-grid snp-grid-3">';
        // Total.
        echo '<div class="snp-stat">';
        echo '<div class="snp-stat-label">' . esc_html__('إجمالي الاشتراكات', 'stock-notifications-pro') . '</div>';
        echo '<div class="snp-stat-value">' . esc_html((string) $total) . '</div>';
        echo '</div>';

        // Pending.
        echo '<div class="snp-stat">';
        echo '<div class="snp-stat-label">' . esc_html__('في الانتظار', 'stock-notifications-pro') . '</div>';
        echo '<div class="snp-stat-value">' . esc_html((string) $pending) . '</div>';
        echo '</div>';

        // Sent.
        echo '<div class="snp-stat">';
        echo '<div class="snp-stat-label">' . esc_html__('تم الإرسال', 'stock-notifications-pro') . '</div>';
        echo '<div class="snp-stat-value">' . esc_html((string) $sent) . '</div>';
        echo '</div>';

        echo '</div>'; // .snp-grid

        // Recent pending.
        echo '<h2>' . esc_html__('أحدث الطلبات (في الانتظار)', 'stock-notifications-pro') . '</h2>';
        $this->render_table($recent_pending, false);

        // Recent sent.
        echo '<h2>' . esc_html__('أحدث الطلبات (تم الإرسال)', 'stock-notifications-pro') . '</h2>';
        $this->render_table($recent_sent, true);

        echo '</div>'; // .snp-card
    }

    /**
     * Render table of requests.
     *
     * @param array $rows   Rows from DB.
     * @param bool  $sent   Whether these rows are "sent" notifications.
     * @return void
     */
    private function render_table($rows, $sent)
    {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('المنتج', 'stock-notifications-pro') . '</th>';
        echo '<th>' . esc_html__('العميل', 'stock-notifications-pro') . '</th>';
        echo '<th>' . esc_html__('البريد', 'stock-notifications-pro') . '</th>';
        echo '<th>' . esc_html__('الهاتف', 'stock-notifications-pro') . '</th>';
        echo '<th>' . esc_html__('التاريخ', 'stock-notifications-pro') . '</th>';
        echo '</tr></thead><tbody>';

        if (! empty($rows)) {
            foreach ($rows as $row) {
                $product = wc_get_product($row->product_id);

                $date_text = '';
                if (! empty($row->date_registered)) {
                    $timestamp = strtotime($row->date_registered);
                    if ($timestamp) {
                        // استخدم wp_date بدلاً من date().
                        $date_text = wp_date('Y-m-d H:i', $timestamp);
                    }
                }

                echo '<tr>';
                echo '<td>' . ($product ? esc_html($product->get_name()) : esc_html__('منتج محذوف', 'stock-notifications-pro')) . '</td>';
                echo '<td>' . esc_html((string) $row->user_name) . '</td>';
                echo '<td>' . esc_html((string) $row->user_email) . '</td>';
                echo '<td>' . ($row->phone ? esc_html((string) $row->phone) : esc_html('-')) . '</td>';
                echo '<td>' . esc_html($date_text) . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="5">' . esc_html__('لا توجد نتائج.', 'stock-notifications-pro') . '</td></tr>';
        }

        echo '</tbody></table>';
    }
}
