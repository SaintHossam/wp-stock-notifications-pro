<?php

/**
 * Admin Settings Handler
 *
 * @package StockNotificationsPro
 */

namespace StockNotificationsPro\Admin;

use StockNotificationsPro\Helpers\Functions;

/**
 * Class Settings
 *
 * Handles plugin settings management.
 */
class Settings
{
    /**
     * Register hooks
     *
     * @return void
     */
    public function register_hooks()
    {
        // Settings are saved via form submission, handled in render method.
    }

    /**
     * Render settings page
     *
     * @return void
     */
    public function render()
    {
        $options = Functions::get_option();

        if (! empty($_POST['snp_save']) && check_admin_referer('snp_save_settings')) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified by check_admin_referer() above.
            $post = wp_unslash($_POST);

            $smtp_secure_raw = isset($post['smtp_secure']) ? sanitize_key($post['smtp_secure']) : 'tls';
            $allowed_secure  = array( 'none', 'ssl', 'tls' );
            $smtp_secure     = in_array($smtp_secure_raw, $allowed_secure, true) ? $smtp_secure_raw : 'tls';

            $data = array(
                'enable_smtp'     => ! empty($post['enable_smtp']) ? 1 : 0,
                'smtp_host'       => isset($post['smtp_host']) ? sanitize_text_field($post['smtp_host']) : '',
                'smtp_port'       => isset($post['smtp_port']) ? (int) $post['smtp_port'] : 587,
                'smtp_user'       => isset($post['smtp_user']) ? sanitize_text_field($post['smtp_user']) : '',
                'smtp_pass'       => isset($post['smtp_pass']) ? sanitize_text_field($post['smtp_pass']) : '',
                'smtp_secure'     => $smtp_secure,
                'from_email'      => isset($post['from_email']) ? sanitize_email($post['from_email']) : '',
                'from_name'       => isset($post['from_name']) ? sanitize_text_field($post['from_name']) : '',
                'reply_to'        => isset($post['reply_to']) ? sanitize_email($post['reply_to']) : '',
                'list_unsub'      => ! empty($post['list_unsub']) ? 1 : 0,
                'unsub_url'       => isset($post['unsub_url']) ? esc_url_raw($post['unsub_url']) : '',
                'subject_tpl'     => isset($post['subject_tpl']) ? wp_kses_post($post['subject_tpl']) : '',
                'success_message' => isset($post['success_message']) ? wp_kses_post($post['success_message']) : '',
                'error_message'   => isset($post['error_message']) ? wp_kses_post($post['error_message']) : '',
                'button_text'     => isset($post['button_text']) ? sanitize_text_field($post['button_text']) : '',
                'show_badge'      => ! empty($post['show_badge']) ? 1 : 0,
            );

            Functions::update_option($data);
            $options = Functions::get_option();

            echo '<div class="notice notice-success"><p>' . esc_html__('تم الحفظ.', 'stock-notifications-pro') . '</p></div>';
        }

        echo '<form method="post" class="snp-card">';
        wp_nonce_field('snp_save_settings');
        echo '<h2>' . esc_html__('الإعدادات العامة والبريد', 'stock-notifications-pro') . '</h2>';

        // SMTP Enable.
        echo '<div class="snp-row">';
        echo '<label class="snp-label" for="snp-enable-smtp">' . esc_html__('تفعيل SMTP', 'stock-notifications-pro') . '</label>';
        echo '<input id="snp-enable-smtp" type="checkbox" name="enable_smtp" ' . checked($options['enable_smtp'], 1, false) . ' />';
        echo '</div>';

        // SMTP Host.
        echo '<div class="snp-row">';
        echo '<label class="snp-label" for="snp-smtp-host">' . esc_html__('SMTP Host', 'stock-notifications-pro') . '</label>';
        echo '<input class="snp-input" id="snp-smtp-host" type="text" name="smtp_host" value="' . esc_attr($options['smtp_host']) . '">';
        echo '</div>';

        // SMTP Port.
        echo '<div class="snp-row">';
        echo '<label class="snp-label" for="snp-smtp-port">' . esc_html__('SMTP Port', 'stock-notifications-pro') . '</label>';
        echo '<input class="snp-input" id="snp-smtp-port" type="number" name="smtp_port" value="' . esc_attr($options['smtp_port']) . '">';
        echo '</div>';

        // SMTP Username.
        echo '<div class="snp-row">';
        echo '<label class="snp-label" for="snp-smtp-user">' . esc_html__('SMTP Username', 'stock-notifications-pro') . '</label>';
        echo '<input class="snp-input" id="snp-smtp-user" type="text" name="smtp_user" value="' . esc_attr($options['smtp_user']) . '">';
        echo '</div>';

        // SMTP Password.
        echo '<div class="snp-row">';
        echo '<label class="snp-label" for="snp-smtp-pass">' . esc_html__('SMTP Password', 'stock-notifications-pro') . '</label>';
        echo '<input class="snp-input" id="snp-smtp-pass" type="password" name="smtp_pass" value="' . esc_attr($options['smtp_pass']) . '">';
        echo '</div>';

        // SMTP Encryption.
        echo '<div class="snp-row">';
        echo '<label class="snp-label" for="snp-smtp-secure">' . esc_html__('التشفير', 'stock-notifications-pro') . '</label>';
        echo '<select class="snp-input" name="smtp_secure" id="snp-smtp-secure">';
        echo '<option value="none" ' . selected($options['smtp_secure'], 'none', false) . '>' . esc_html__('None', 'stock-notifications-pro') . '</option>';
        echo '<option value="ssl" ' . selected($options['smtp_secure'], 'ssl', false) . '>' . esc_html__('SSL', 'stock-notifications-pro') . '</option>';
        echo '<option value="tls" ' . selected($options['smtp_secure'], 'tls', false) . '>' . esc_html__('TLS', 'stock-notifications-pro') . '</option>';
        echo '</select>';
        echo '</div>';

        // From Email.
        echo '<div class="snp-row">';
        echo '<label class="snp-label" for="snp-from-email">' . esc_html__('From Email', 'stock-notifications-pro') . '</label>';
        echo '<input class="snp-input" id="snp-from-email" type="email" name="from_email" value="' . esc_attr($options['from_email']) . '">';
        echo '</div>';

        // From Name.
        echo '<div class="snp-row">';
        echo '<label class="snp-label" for="snp-from-name">' . esc_html__('From Name', 'stock-notifications-pro') . '</label>';
        echo '<input class="snp-input" id="snp-from-name" type="text" name="from_name" value="' . esc_attr($options['from_name']) . '">';
        echo '</div>';

        // Reply-To.
        echo '<div class="snp-row">';
        echo '<label class="snp-label" for="snp-reply-to">' . esc_html__('Reply-To', 'stock-notifications-pro') . '</label>';
        echo '<input class="snp-input" id="snp-reply-to" type="email" name="reply_to" value="' . esc_attr($options['reply_to']) . '">';
        echo '</div>';

        // List-Unsubscribe.
        echo '<div class="snp-row">';
        echo '<label class="snp-label" for="snp-list-unsub">' . esc_html__('List-Unsubscribe', 'stock-notifications-pro') . '</label>';
        echo '<input id="snp-list-unsub" type="checkbox" name="list_unsub" ' . checked($options['list_unsub'], 1, false) . ' />';
        echo '<span class="snp-note">' . esc_html__('يوصى بتفعيله', 'stock-notifications-pro') . '</span>';
        echo '</div>';

        // Unsubscribe URL.
        echo '<div class="snp-row">';
        echo '<label class="snp-label" for="snp-unsub-url">' . esc_html__('رابط إلغاء الاشتراك', 'stock-notifications-pro') . '</label>';
        echo '<input class="snp-input" id="snp-unsub-url" type="text" name="unsub_url" value="' . esc_attr($options['unsub_url']) . '">';
        echo '</div>';

        // Subject Template.
        echo '<div class="snp-row">';
        echo '<label class="snp-label" for="snp-subject-tpl">' . esc_html__('عنوان الرسالة', 'stock-notifications-pro') . '</label>';
        echo '<input class="snp-input" id="snp-subject-tpl" type="text" name="subject_tpl" value="' . esc_attr($options['subject_tpl']) . '">';
        /* translators: %site% is the site name, %product% is the product name. */
        echo '<span class="snp-note">' . esc_html__('%site% و %product%', 'stock-notifications-pro') . '</span>';
        echo '</div>';

        // Success Message.
        echo '<div class="snp-row">';
        echo '<label class="snp-label" for="snp-success-message">' . esc_html__('نص النجاح', 'stock-notifications-pro') . '</label>';
        echo '<input class="snp-input" id="snp-success-message" type="text" name="success_message" value="' . esc_attr($options['success_message']) . '">';
        echo '</div>';

        // Error Message.
        echo '<div class="snp-row">';
        echo '<label class="snp-label" for="snp-error-message">' . esc_html__('نص الخطأ', 'stock-notifications-pro') . '</label>';
        echo '<input class="snp-input" id="snp-error-message" type="text" name="error_message" value="' . esc_attr($options['error_message']) . '">';
        echo '</div>';

        // Button Text.
        echo '<div class="snp-row">';
        echo '<label class="snp-label" for="snp-button-text">' . esc_html__('نص زر الأرشيف', 'stock-notifications-pro') . '</label>';
        echo '<input class="snp-input" id="snp-button-text" type="text" name="button_text" value="' . esc_attr($options['button_text']) . '">';
        echo '</div>';

        // Show Badge.
        echo '<div class="snp-row">';
        echo '<label class="snp-label" for="snp-show-badge">' . esc_html__('شارة نفاد الكمية', 'stock-notifications-pro') . '</label>';
        echo '<input id="snp-show-badge" type="checkbox" name="show_badge" ' . checked($options['show_badge'], 1, false) . ' />';
        echo '</div>';

        echo '<p><button class="snp-btn" type="submit" name="snp_save" value="1">' . esc_html__('حفظ', 'stock-notifications-pro') . '</button></p>';
        echo '</form>';
    }

    /**
     * Render test email form
     *
     * @return void
     */
    public function render_test_email()
    {
        if (! empty($_POST['snp_test_send']) && check_admin_referer('snp_test_mail')) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified by check_admin_referer() above.
            $post = wp_unslash($_POST);

            $to = isset($post['snp_test_to']) ? sanitize_email($post['snp_test_to']) : '';

            if ($to && is_email($to)) {
                $site    = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
                $subject = sprintf(
                    /* translators: %s: site name */
                    __('%s – اختبار إعدادات البريد', 'stock-notifications-pro'),
                    $site
                );

                $html  = '<div style="font-family:Arial;direction:rtl;padding:20px">';
                $html .= '<h2>' . esc_html__('اختبار البريد', 'stock-notifications-pro') . '</h2>';
                $html .= '<p>' . sprintf(
                    /* translators: %s: site name */
                    esc_html__('هذه رسالة اختبارية من %s.', 'stock-notifications-pro'),
                    esc_html($site)
                ) . '</p>';
                $html .= '</div>';

                $options = Functions::get_option();
                $headers = array( 'Content-Type: text/html; charset=UTF-8' );

                if (! empty($options['reply_to'])) {
                    $headers[] = 'Reply-To: ' . sanitize_email($options['reply_to']);
                }

                $sent = wp_mail($to, $subject, $html, $headers);

                if ($sent) {
                    echo '<div class="notice notice-success"><p>' . sprintf(
                        /* translators: %s: email address */
                        esc_html__('تم إرسال رسالة اختبار إلى %s.', 'stock-notifications-pro'),
                        esc_html($to)
                    ) . '</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__('فشل الإرسال. راجع إعدادات SMTP وDNS.', 'stock-notifications-pro') . '</p></div>';
                }
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html__('بريد غير صالح.', 'stock-notifications-pro') . '</p></div>';
            }
        }

        echo '<form method="post" class="snp-card">';
        wp_nonce_field('snp_test_mail');
        echo '<h2>' . esc_html__('إرسال رسالة اختبار', 'stock-notifications-pro') . '</h2>';
        echo '<div class="snp-row">';
        echo '<label class="snp-label" for="snp-test-to">' . esc_html__('أرسل إلى', 'stock-notifications-pro') . '</label>';
        echo '<input class="snp-input" id="snp-test-to" type="email" name="snp_test_to" placeholder="you@example.com">';
        echo '</div>';
        echo '<p><button class="snp-btn" type="submit" name="snp_test_send" value="1">' . esc_html__('إرسال الاختبار', 'stock-notifications-pro') . '</button></p>';
        echo '</form>';
    }
}
