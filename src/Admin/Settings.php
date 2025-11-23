<?php
/**
 * Admin Settings Handler
 *
 * @package WPStockNotificationsPro
 */

namespace WPStockNotificationsPro\Admin;

use WPStockNotificationsPro\Helpers\Functions;

/**
 * Class Settings
 *
 * Handles plugin settings management.
 */
class Settings {

    /**
     * Register hooks
     *
     * @return void
     */
    public function register_hooks() {
        // Settings are saved via form submission, handled in render method
    }

    /**
     * Render settings page
     *
     * @return void
     */
    public function render() {
        $options = Functions::get_option();

        if (!empty($_POST['snp_save']) && check_admin_referer('snp_save_settings')) {
            $data = array(
                'enable_smtp'     => isset($_POST['enable_smtp']) ? 1 : 0,
                'smtp_host'       => sanitize_text_field($_POST['smtp_host'] ?? ''),
                'smtp_port'       => intval($_POST['smtp_port'] ?? 587),
                'smtp_user'       => sanitize_text_field($_POST['smtp_user'] ?? ''),
                'smtp_pass'       => sanitize_text_field($_POST['smtp_pass'] ?? ''),
                'smtp_secure'     => in_array($_POST['smtp_secure'] ?? 'tls', array('none', 'ssl', 'tls'), true) 
                                     ? $_POST['smtp_secure'] : 'tls',
                'from_email'      => sanitize_email($_POST['from_email'] ?? ''),
                'from_name'       => sanitize_text_field($_POST['from_name'] ?? ''),
                'reply_to'        => sanitize_email($_POST['reply_to'] ?? ''),
                'list_unsub'      => isset($_POST['list_unsub']) ? 1 : 0,
                'unsub_url'       => esc_url_raw($_POST['unsub_url'] ?? ''),
                'subject_tpl'     => wp_kses_post($_POST['subject_tpl'] ?? ''),
                'success_message' => wp_kses_post($_POST['success_message'] ?? ''),
                'error_message'   => wp_kses_post($_POST['error_message'] ?? ''),
                'button_text'     => sanitize_text_field($_POST['button_text'] ?? ''),
                'show_badge'      => isset($_POST['show_badge']) ? 1 : 0,
            );

            Functions::update_option($data);
            $options = Functions::get_option();
            echo '<div class="notice notice-success"><p>تم الحفظ.</p></div>';
        }

        echo '<form method="post" class="snp-card">';
        wp_nonce_field('snp_save_settings');
        echo '<h2>الإعدادات العامة والبريد</h2>';

        // SMTP Enable
        echo '<div class="snp-row">';
        echo '<label class="snp-label">تفعيل SMTP</label>';
        echo '<input type="checkbox" name="enable_smtp" ' . checked($options['enable_smtp'], 1, false) . ' />';
        echo '</div>';

        // SMTP Host
        echo '<div class="snp-row">';
        echo '<label class="snp-label">SMTP Host</label>';
        echo '<input class="snp-input" type="text" name="smtp_host" value="' . esc_attr($options['smtp_host']) . '">';
        echo '</div>';

        // SMTP Port
        echo '<div class="snp-row">';
        echo '<label class="snp-label">SMTP Port</label>';
        echo '<input class="snp-input" type="number" name="smtp_port" value="' . esc_attr($options['smtp_port']) . '">';
        echo '</div>';

        // SMTP Username
        echo '<div class="snp-row">';
        echo '<label class="snp-label">SMTP Username</label>';
        echo '<input class="snp-input" type="text" name="smtp_user" value="' . esc_attr($options['smtp_user']) . '">';
        echo '</div>';

        // SMTP Password
        echo '<div class="snp-row">';
        echo '<label class="snp-label">SMTP Password</label>';
        echo '<input class="snp-input" type="password" name="smtp_pass" value="' . esc_attr($options['smtp_pass']) . '">';
        echo '</div>';

        // SMTP Encryption
        echo '<div class="snp-row">';
        echo '<label class="snp-label">التشفير</label>';
        echo '<select class="snp-input" name="smtp_secure">';
        echo '<option value="none" ' . selected($options['smtp_secure'], 'none', false) . '>None</option>';
        echo '<option value="ssl" ' . selected($options['smtp_secure'], 'ssl', false) . '>SSL</option>';
        echo '<option value="tls" ' . selected($options['smtp_secure'], 'tls', false) . '>TLS</option>';
        echo '</select>';
        echo '</div>';

        // From Email
        echo '<div class="snp-row">';
        echo '<label class="snp-label">From Email</label>';
        echo '<input class="snp-input" type="email" name="from_email" value="' . esc_attr($options['from_email']) . '">';
        echo '</div>';

        // From Name
        echo '<div class="snp-row">';
        echo '<label class="snp-label">From Name</label>';
        echo '<input class="snp-input" type="text" name="from_name" value="' . esc_attr($options['from_name']) . '">';
        echo '</div>';

        // Reply-To
        echo '<div class="snp-row">';
        echo '<label class="snp-label">Reply-To</label>';
        echo '<input class="snp-input" type="email" name="reply_to" value="' . esc_attr($options['reply_to']) . '">';
        echo '</div>';

        // List-Unsubscribe
        echo '<div class="snp-row">';
        echo '<label class="snp-label">List-Unsubscribe</label>';
        echo '<input type="checkbox" name="list_unsub" ' . checked($options['list_unsub'], 1, false) . ' />';
        echo '<span class="snp-note">يوصى بتفعيله</span>';
        echo '</div>';

        // Unsubscribe URL
        echo '<div class="snp-row">';
        echo '<label class="snp-label">رابط إلغاء الاشتراك</label>';
        echo '<input class="snp-input" type="text" name="unsub_url" value="' . esc_attr($options['unsub_url']) . '">';
        echo '</div>';

        // Subject Template
        echo '<div class="snp-row">';
        echo '<label class="snp-label">عنوان الرسالة</label>';
        echo '<input class="snp-input" type="text" name="subject_tpl" value="' . esc_attr($options['subject_tpl']) . '">';
        echo '<span class="snp-note">%site% و %product%</span>';
        echo '</div>';

        // Success Message
        echo '<div class="snp-row">';
        echo '<label class="snp-label">نص النجاح</label>';
        echo '<input class="snp-input" type="text" name="success_message" value="' . esc_attr($options['success_message']) . '">';
        echo '</div>';

        // Error Message
        echo '<div class="snp-row">';
        echo '<label class="snp-label">نص الخطأ</label>';
        echo '<input class="snp-input" type="text" name="error_message" value="' . esc_attr($options['error_message']) . '">';
        echo '</div>';

        // Button Text
        echo '<div class="snp-row">';
        echo '<label class="snp-label">نص زر الأرشيف</label>';
        echo '<input class="snp-input" type="text" name="button_text" value="' . esc_attr($options['button_text']) . '">';
        echo '</div>';

        // Show Badge
        echo '<div class="snp-row">';
        echo '<label class="snp-label">شارة نفاد الكمية</label>';
        echo '<input type="checkbox" name="show_badge" ' . checked($options['show_badge'], 1, false) . ' />';
        echo '</div>';

        echo '<p><button class="snp-btn" type="submit" name="snp_save" value="1">حفظ</button></p>';
        echo '</form>';
    }

    /**
     * Render test email form
     *
     * @return void
     */
    public function render_test_email() {
        if (!empty($_POST['snp_test_send']) && check_admin_referer('snp_test_mail')) {
            $to = sanitize_email($_POST['snp_test_to'] ?? '');
            
            if ($to && is_email($to)) {
                $site = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
                $subject = $site . ' – اختبار إعدادات البريد';
                $html = '<div style="font-family:Arial;direction:rtl;padding:20px">';
                $html .= '<h2>اختبار البريد</h2>';
                $html .= '<p>هذه رسالة اختبارية من ' . esc_html($site) . '.</p>';
                $html .= '</div>';

                $options = Functions::get_option();
                $headers = array('Content-Type: text/html; charset=UTF-8');
                
                if (!empty($options['reply_to'])) {
                    $headers[] = 'Reply-To: ' . sanitize_email($options['reply_to']);
                }

                $sent = wp_mail($to, $subject, $html, $headers);
                
                if ($sent) {
                    echo '<div class="notice notice-success"><p>تم إرسال رسالة اختبار إلى ' . esc_html($to) . '.</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>فشل الإرسال. راجع إعدادات SMTP وDNS.</p></div>';
                }
            } else {
                echo '<div class="notice notice-error"><p>بريد غير صالح.</p></div>';
            }
        }

        echo '<form method="post" class="snp-card">';
        wp_nonce_field('snp_test_mail');
        echo '<h2>إرسال رسالة اختبار</h2>';
        echo '<div class="snp-row">';
        echo '<label class="snp-label">أرسل إلى</label>';
        echo '<input class="snp-input" type="email" name="snp_test_to" placeholder="you@example.com">';
        echo '</div>';
        echo '<p><button class="snp-btn" type="submit" name="snp_test_send" value="1">إرسال الاختبار</button></p>';
        echo '</form>';
    }
}
