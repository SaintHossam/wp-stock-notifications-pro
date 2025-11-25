<?php

/**
 * Public Frontend Handler
 *
 * @package StockNotificationsPro
 */

namespace StockNotificationsPro\Public;

use StockNotificationsPro\Helpers\Functions;
use StockNotificationsPro\Database\Schema;
use StockNotificationsPro\Plugin;

/**
 * Class Frontend
 *
 * Handles all public-facing functionality.
 */
class Frontend
{
    /**
     * Register hooks.
     *
     * @return void
     */
    public function register_hooks()
    {
        // Modify add to cart button for out of stock products.
        add_filter('woocommerce_loop_add_to_cart_link', array( $this, 'modify_add_to_cart_button' ), 10, 2);

        // Add out of stock badge.
        add_action('woocommerce_after_shop_loop_item_title', array( $this, 'add_out_of_stock_badge' ), 9);

        // Add class to out of stock products.
        add_filter('post_class', array( $this, 'add_product_class' ), 10, 3);

        // Display notification form on single product page.
        add_action('woocommerce_single_product_summary', array( $this, 'display_notification_form' ), 35);

        // Handle AJAX registration.
        add_action('wp_ajax_snp_register', array( $this, 'handle_registration' ));
        add_action('wp_ajax_nopriv_snp_register', array( $this, 'handle_registration' ));

        // Handle unsubscribe.
        add_action('template_redirect', array( $this, 'handle_unsubscribe' ));

        // Enqueue scripts and styles.
        add_action('wp_enqueue_scripts', array( $this, 'enqueue_scripts' ));
    }

    /**
     * Modify add to cart button for out of stock products.
     *
     * @param string      $html    Original HTML.
     * @param \WC_Product $product Product object.
     * @return string
     */
    public function modify_add_to_cart_button($html, $product)
    {
        $options = Functions::get_option();

        if (! $product->is_in_stock() || ! $product->is_purchasable()) {
            $url = get_permalink($product->get_id());

            return sprintf(
                '<a href="%s" class="button product_type_simple readmore-btn snp-notify-btn" data-product-id="%d" rel="nofollow">%s</a>',
                esc_url($url),
                (int) $product->get_id(),
                esc_html($options['button_text'])
            );
        }

        return $html;
    }

    /**
     * Add out of stock badge.
     *
     * @return void
     */
    public function add_out_of_stock_badge()
    {
        $options = Functions::get_option();
        global $product;

        if (! empty($options['show_badge']) && $product && (! $product->is_in_stock() || ! $product->is_purchasable())) {
            echo '<div class="snp-oos">' . esc_html__('نفدت الكمية', 'stock-notifications-pro') . '</div>';
        }
    }

    /**
     * Add class to out of stock products.
     *
     * @param array        $classes Current classes.
     * @param string|array $class   Additional classes.
     * @param int          $post_id Post ID.
     * @return array
     */
    public function add_product_class($classes, $class, $post_id) // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
    {
        $product = ('product' === get_post_type($post_id)) ? wc_get_product($post_id) : null;

        if ($product && (! $product->is_in_stock() || ! $product->is_purchasable())) {
            $classes[] = 'snp-is-oos';
        }

        return $classes;
    }

    /**
     * Display notification form on single product page.
     *
     * @return void
     */
    public function display_notification_form()
    {
        global $product;

        if ($product && (! $product->is_in_stock() || ! $product->is_purchasable())) {
            // Template path: plugin-root/templates/notification-form.php (one level above src).
            include plugin_dir_path(dirname(__FILE__)) . '../templates/notification-form.php';
        }
    }

    /**
     * Handle AJAX registration.
     *
     * @return void
     */
    public function handle_registration()
    {
        // Verify nonce first.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified immediately and not used for output.
        $raw_post = $_POST;

        $nonce = isset($raw_post['snp_nonce_field']) ? wp_unslash($raw_post['snp_nonce_field']) : '';
        if (! $nonce || ! wp_verify_nonce($nonce, 'snp_nonce')) {
            wp_send_json_error(__('خطأ في الأمان', 'stock-notifications-pro'));
        }

        // Now safely work with unslashed $_POST data.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Input is processed after nonce verification.
        $post = wp_unslash($raw_post);

        global $wpdb;
        $table = Schema::get_table_name();

        $product_id   = isset($post['product_id']) ? (int) $post['product_id'] : 0;
        $variation_id = isset($post['variation_id']) ? (int) $post['variation_id'] : 0;
        $user_email   = isset($post['user_email']) ? sanitize_email($post['user_email']) : '';
        $user_name    = isset($post['user_name']) ? sanitize_text_field($post['user_name']) : '';
        $phone        = isset($post['phone']) ? sanitize_text_field($post['phone']) : '';

        // Validate.
        if ($product_id <= 0) {
            wp_send_json_error(__('معرّف المنتج غير صالح', 'stock-notifications-pro'));
        }

        if (! is_email($user_email)) {
            wp_send_json_error(__('بريد إلكتروني غير صحيح', 'stock-notifications-pro'));
        }

        // Check if already subscribed.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE product_id = %d AND variation_id = %d AND user_email = %s AND is_notified = 0 AND unsubscribed = 0",
                $product_id,
                $variation_id,
                $user_email
            )
        );

        if ($exists) {
            wp_send_json_error(__('لقد تم تسجيلك مسبقاً لهذا المنتج', 'stock-notifications-pro'));
        }

        // Insert subscriber.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
        $inserted = $wpdb->insert(
            $table,
            array(
                'product_id'      => $product_id,
                'variation_id'    => $variation_id,
                'user_email'      => $user_email,
                'user_name'       => $user_name,
                'phone'           => $phone,
                'date_registered' => current_time('mysql'),
                'is_notified'     => 0,
                'unsubscribed'    => 0,
            ),
            array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d' )
        );

        if ($inserted) {
            wp_send_json_success(Functions::get_option('success_message'));
        } else {
            /**
             * Fires when the stock notification insert query fails.
             *
             * @param string $last_error The last DB error.
             */
            do_action('stock_notifications_pro_db_insert_failed', $wpdb->last_error);
            wp_send_json_error(Functions::get_option('error_message'));
        }
    }

    /**
     * Handle unsubscribe requests.
     *
     * @return void
     */
    public function handle_unsubscribe()
    {
        // This is triggered via one-click email link, a nonce is not practical here.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (! isset($_GET['stock_unsub'])) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $get   = wp_unslash($_GET);
        $email = isset($get['email']) ? sanitize_email($get['email']) : '';

        if ($email && is_email($email)) {
            global $wpdb;
            $table = Schema::get_table_name();

            // تحديث آمن باستخدام prepare.
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$table} SET unsubscribed = 1 WHERE user_email = %s",
                    $email
                )
            );

            $message = '<div style="font-family:Arial;direction:rtl;text-align:center;padding:40px;">' .
                esc_html__('تم إلغاء الاشتراك بنجاح.', 'stock-notifications-pro') .
                '</div>';

            wp_die(
                wp_kses_post($message),
                esc_html__('إلغاء الاشتراك', 'stock-notifications-pro')
            );
        } else {
            $message = '<div style="font-family:Arial;direction:rtl;text-align:center;padding:40px;">' .
                esc_html__('بريد غير صالح.', 'stock-notifications-pro') .
                '</div>';

            wp_die(
                wp_kses_post($message),
                esc_html__('إلغاء الاشتراك', 'stock-notifications-pro')
            );
        }
    }

    /**
     * Enqueue scripts and styles.
     *
     * @return void
     */
    public function enqueue_scripts()
    {
        if (! (function_exists('is_woocommerce') && is_woocommerce())
            && ! is_cart()
            && ! is_shop()
            && ! is_product_category()
            && ! is_product()
        ) {
            return;
        }

        wp_enqueue_script('jquery');

        $options  = Functions::get_option();
        $msg_ok   = esc_js($options['success_message']);
        $msg_err  = esc_js($options['error_message']);
        $ajax_url = esc_url_raw(admin_url('admin-ajax.php'));

        $js = "
		jQuery(document).ready(function(jQuery){
			jQuery('#snp-form').on('submit', function(e){
				e.preventDefault();
				var f = jQuery(this), b = f.find('button[type=submit]'), m = jQuery('#snp-msg');
				b.prop('disabled', true).text('جاري التسجيل...');
				jQuery.ajax({
					url: '{$ajax_url}',
					type: 'POST',
					dataType: 'json',
					data: f.serialize(),
					success: function(r){
						if (r && r.success) {
							m.html('<div class=\"snp-ok\">' + r.data + '</div>').show();
							if (f[0]) { f[0].reset(); }
						} else {
							var t = (r && r.data) ? r.data : '{$msg_err}';
							m.html('<div class=\"snp-err\">' + t + '</div>').show();
						}
					},
					error: function(){
						m.html('<div class=\"snp-err\">{$msg_err}</div>').show();
					},
					complete: function(){
						b.prop('disabled', false).text('تسجيل التنبيه');
						setTimeout(function(){ m.fadeOut(); }, 5000);
					}
				});
			});
			jQuery(document).on('click', '.snp-notify-btn', function(e){
				var form = jQuery('.snp-form');
				if (form.length){
					e.preventDefault();
					jQuery('html, body').animate({scrollTop: form.offset().top - 100}, 500);
					setTimeout(function(){ form.find('input[name=user_name]').focus(); }, 600);
				}
			});
		});";

        $version = Plugin::get_version();

        // Register and enqueue inline script with version to avoid caching issues.
        wp_register_script('snp-inline', '', array( 'jquery' ), $version, true);
        wp_enqueue_script('snp-inline');
        wp_add_inline_script('snp-inline', $js);

        // Enqueue inline CSS with version.
        $css = $this->get_inline_css();
        wp_register_style('snp-inline', false, array(), $version);
        wp_enqueue_style('snp-inline');
        wp_add_inline_style('snp-inline', $css);
    }

    /**
     * Get inline CSS.
     *
     * @return string
     */
    private function get_inline_css()
    {
        return '.snp-form{background:#f9f9f9;border:1px solid #ddd;border-radius:8px;padding:20px;margin:20px 0;max-width:420px}
		.snp-form h4{margin-top:0;color:#2c3e50;font-size:18px;border-bottom:2px solid #3498db;padding-bottom:10px}
		.snp-form input[type=text],.snp-form input[type=email],.snp-form input[type=tel]{width:100%;padding:12px;border:1px solid #ddd;border-radius:4px;font-size:14px;box-sizing:border-box;transition:border-color .3s;margin-bottom:10px}
		.snp-form input:focus{border-color:#3498db;outline:0;box-shadow:0 0 5px rgba(52,152,219,.3)}
		.snp-form button{background:#3498db;color:#fff;border:0;padding:12px 24px;border-radius:4px;cursor:pointer;font-size:16px;font-weight:500;transition:background .3s;width:100%}
		.snp-form button:hover{background:#2980b9}
		.snp-form button:disabled{background:#bdc3c7;cursor:not-allowed}
		.snp-ok{background:#d4edda;color:#155724;padding:12px;border:1px solid #c3e6cb;border-radius:4px;margin:10px 0}
		.snp-err{background:#f8d7da;color:#721c24;padding:12px;border:1px solid #f5c6cb;border-radius:4px;margin:10px 0}
		.snp-oos{background:#e74c3c;color:#fff;padding:8px 12px;border-radius:4px;font-size:14px;font-weight:500;margin:10px 0;text-align:center;display:inline-block}
		.snp-notify-btn{background:#f39c12 !important;border-color:#f39c12 !important}
		.snp-notify-btn:hover{background:#e67e22 !important;border-color:#e67e22 !important}
		.snp-notify-btn:before{content:"🔔";margin-left:8px}
		.snp-is-oos{position:relative}
		.snp-is-oos:after{content:"نفدت الكمية";position:absolute;top:10px;right:10px;background:rgba(231,76,60,.9);color:#fff;padding:4px 8px;border-radius:4px;font-size:12px;font-weight:700;z-index:2}
		.snp-is-oos img{filter:grayscale(50%);opacity:.7}
		@media(max-width:768px){.snp-form{margin:15px 0;padding:15px;max-width:100%}.snp-form h4{font-size:16px}}';
    }
}
