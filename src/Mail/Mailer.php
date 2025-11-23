<?php
/**
 * Email Handler
 *
 * @package WPStockNotificationsPro
 */

namespace WPStockNotificationsPro\Mail;

use WPStockNotificationsPro\Helpers\Functions;
use WPStockNotificationsPro\Database\Schema;

/**
 * Class Mailer
 *
 * Handles SMTP configuration and email sending.
 */
class Mailer {

    /**
     * Register hooks
     *
     * @return void
     */
    public function register_hooks() {
        // Configure email sender
        add_filter('wp_mail_from', array($this, 'set_mail_from'));
        add_filter('wp_mail_from_name', array($this, 'set_mail_from_name'));
        
        // Configure SMTP if enabled
        add_action('phpmailer_init', array($this, 'configure_smtp'));
        
        // Stock change hooks
        add_action('woocommerce_product_set_stock', array($this, 'maybe_send_notifications'), 10, 1);
        add_action('woocommerce_variation_set_stock', array($this, 'maybe_send_notifications'), 10, 1);
        
        // Log mail failures
        add_action('wp_mail_failed', array($this, 'log_mail_failure'));
    }

    /**
     * Set mail from address
     *
     * @return string
     */
    public function set_mail_from() {
        return sanitize_email(Functions::get_option('from_email'));
    }

    /**
     * Set mail from name
     *
     * @return string
     */
    public function set_mail_from_name() {
        return wp_specialchars_decode(Functions::get_option('from_name'), ENT_QUOTES);
    }

    /**
     * Configure SMTP settings
     *
     * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer instance.
     * @return void
     */
    public function configure_smtp($phpmailer) {
        $options = Functions::get_option();
        
        if (!$options['enable_smtp']) {
            return;
        }

        $phpmailer->isSMTP();
        $phpmailer->Host = $options['smtp_host'];
        $phpmailer->SMTPAuth = true;
        $phpmailer->Port = (int) $options['smtp_port'];

        // Set encryption
        if ($options['smtp_secure'] === 'ssl') {
            $phpmailer->SMTPSecure = 'ssl';
        } elseif ($options['smtp_secure'] === 'tls') {
            $phpmailer->SMTPSecure = 'tls';
        } else {
            $phpmailer->SMTPSecure = '';
        }

        $phpmailer->Username = $options['smtp_user'];
        $phpmailer->Password = $options['smtp_pass'];

        $from_email = sanitize_email($options['from_email']);
        $from_name = wp_specialchars_decode($options['from_name'], ENT_QUOTES);
        $phpmailer->setFrom($from_email, $from_name);

        if (!empty($options['reply_to'])) {
            $phpmailer->addReplyTo(sanitize_email($options['reply_to']));
        }

        $phpmailer->AltBody = 'المنتج الذي اشتركت للتنبيه عنه متوفر الآن. تفضل بزيارة رابط المنتج للشراء.';
    }

    /**
     * Check if product is back in stock and trigger notifications
     *
     * @param \WC_Product $product Product object.
     * @return void
     */
    public function maybe_send_notifications($product) {
        if (!$product instanceof \WC_Product) {
            return;
        }

        $qty = $product->get_stock_quantity();
        $in_stock = $product->get_stock_status() === 'instock' || (is_numeric($qty) && $qty > 0);

        if ($in_stock) {
            $this->send_notifications($product->get_id());
        }
    }

    /**
     * Send notifications for a product
     *
     * @param int $product_id Product ID.
     * @param int $variation_id Variation ID.
     * @return void
     */
    public function send_notifications($product_id, $variation_id = 0) {
        global $wpdb;
        
        $table = Schema::get_table_name();
        $subscribers = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE product_id=%d AND variation_id=%d AND is_notified=0 AND unsubscribed=0",
                $product_id,
                $variation_id
            )
        );

        if (!$subscribers) {
            return;
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            return;
        }

        foreach ($subscribers as $subscriber) {
            $this->send_email($subscriber, $product);
            $wpdb->update(
                $table,
                array('is_notified' => 1),
                array('id' => $subscriber->id),
                array('%d'),
                array('%d')
            );
        }
    }

    /**
     * Send email to subscriber
     *
     * @param object $subscriber Subscriber data.
     * @param \WC_Product $product Product object.
     * @return bool
     */
    public function send_email($subscriber, $product) {
        $options = Functions::get_option();
        
        $subject = strtr($options['subject_tpl'], array(
            '%site%' => wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES),
            '%product%' => $product->get_name(),
        ));

        $html = $this->get_email_html($subscriber, $product);
        $headers = $this->get_email_headers();

        return wp_mail($subscriber->user_email, $subject, $html, $headers);
    }

    /**
     * Get email HTML content
     *
     * @param object $subscriber Subscriber data.
     * @param \WC_Product $product Product object.
     * @return string
     */
    private function get_email_html($subscriber, $product) {
        $site_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        
        ob_start();
        include plugin_dir_path(dirname(__FILE__)) . '../templates/emails/notification.php';
        return ob_get_clean();
    }

    /**
     * Get email headers
     *
     * @return array
     */
    private function get_email_headers() {
        $options = Functions::get_option();
        $headers = array('Content-Type: text/html; charset=UTF-8');

        if ($options['list_unsub']) {
            $mailto = 'mailto:' . sanitize_email($options['from_email']) . '?subject=unsubscribe';
            $url = esc_url($options['unsub_url']);
            $headers[] = 'List-Unsubscribe: <' . $mailto . '>, <' . $url . '>';
            $headers[] = 'List-Unsubscribe-Post: List-Unsubscribe=One-Click';
        }

        if (!empty($options['reply_to'])) {
            $headers[] = 'Reply-To: ' . sanitize_email($options['reply_to']);
        }

        return $headers;
    }

    /**
     * Log email failures
     *
     * @param \WP_Error $error Error object.
     * @return void
     */
    public function log_mail_failure($error) {
        error_log('[SNP] wp_mail_failed: ' . $error->get_error_message());
    }
}
