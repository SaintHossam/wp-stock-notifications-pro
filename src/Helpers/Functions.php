<?php
/**
 * Helper Functions
 *
 * @package WPStockNotificationsPro
 */

namespace WPStockNotificationsPro\Helpers;

use WPStockNotificationsPro\Database\Schema;

/**
 * Class Functions
 *
 * Provides helper functions for the plugin.
 */
class Functions {

    /**
     * Get default plugin options
     *
     * @return array
     */
    public static function get_defaults() {
        $domain = parse_url(home_url(), PHP_URL_HOST);
        
        return array(
            'enable_smtp'      => 0,
            'smtp_host'        => '',
            'smtp_port'        => 587,
            'smtp_user'        => '',
            'smtp_pass'        => '',
            'smtp_secure'      => 'tls',
            'from_email'       => 'no-reply@' . $domain,
            'from_name'        => get_bloginfo('name'),
            'reply_to'         => get_option('admin_email'),
            'list_unsub'       => 1,
            'unsub_url'        => home_url('/?stock_unsub=1'),
            'subject_tpl'      => '%site% – توفر المنتج: %product%',
            'success_message'  => 'تم تسجيل طلب التنبيه بنجاح. سيتم إشعارك عند توفر المنتج.',
            'error_message'    => 'حدث خطأ أثناء التسجيل. يرجى المحاولة مرة أخرى.',
            'button_text'      => 'نبهني عند التوفير',
            'show_badge'       => 1,
        );
    }

    /**
     * Get plugin option(s)
     *
     * @param string|null $key Optional. Specific option key to retrieve.
     * @return mixed
     */
    public static function get_option($key = null) {
        $options = get_option('snp_options', array());
        $options = wp_parse_args($options, self::get_defaults());
        
        return $key ? ($options[$key] ?? null) : $options;
    }

    /**
     * Update plugin options
     *
     * @param array $data New options data.
     * @return bool
     */
    public static function update_option($data) {
        $current = self::get_option();
        $new = wp_parse_args($data, $current);
        return update_option('snp_options', $new);
    }

    /**
     * Get the table name
     *
     * @return string
     */
    public static function get_table_name() {
        return Schema::get_table_name();
    }
}
