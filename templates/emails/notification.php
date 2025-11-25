<?php
/**
 * Email Template - Stock Notification
 *
 * @package StockNotificationsPro
 * @var object $subscriber Subscriber data
 * @var \WC_Product $product Product object
 * @var string $site_name Site name
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div style="font-family:Arial,sans-serif;direction:rtl;max-width:600px;margin:0 auto;">
    <div style="background:#f0f0f0;padding:20px;text-align:center;">
        <h2 style="margin:0;color:#333;"><?php echo esc_html($site_name); ?></h2>
    </div>
    <div style="padding:20px;">
        <p>مرحباً <?php echo esc_html($subscriber->user_name ?: ''); ?>،</p>
        <p>المنتج الذي طلبت التنبيه عليه أصبح متاحاً الآن.</p>
        <p>المنتج: <strong><?php echo esc_html($product->get_name()); ?></strong></p>
        <p>السعر: <?php echo wp_kses_post($product->get_price_html()); ?></p>
        <p style="text-align:center;">
            <a href="<?php echo esc_url($product->get_permalink()); ?>" 
               style="background:#0b74de;color:#fff;padding:12px 20px;text-decoration:none;border-radius:6px;display:inline-block;font-weight:600;">
                اشتري الآن
            </a>
        </p>
        <p style="color:#555;font-size:12px;">الكميات محدودة.</p>
    </div>
    <div style="background:#ddd;padding:15px;text-align:center;font-size:12px;color:#444;">
        تم إرسال هذا الإشعار بناءً على طلبك. © <?php echo esc_html($site_name); ?>
    </div>
</div>
