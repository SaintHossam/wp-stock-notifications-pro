<?php
/**
 * Notification Form Template
 *
 * @package StockNotificationsPro
 * @global \WC_Product $product
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="snp-form">
    <h4>نبهني عند توفر هذا المنتج</h4>
    <form id="snp-form">
        <p><input type="text" name="user_name" placeholder="الاسم" required></p>
        <p><input type="email" name="user_email" placeholder="البريد الإلكتروني" required></p>
        <p><input type="tel" name="phone" placeholder="رقم الهاتف (اختياري)"></p>
        <p><button type="submit">تسجيل التنبيه</button></p>
        <input type="hidden" name="product_id" value="<?php echo esc_attr($product->get_id()); ?>">
        <input type="hidden" name="variation_id" value="0">
        <input type="hidden" name="action" value="snp_register">
        <?php wp_nonce_field('snp_nonce', 'snp_nonce_field'); ?>
    </form>
    <div id="snp-msg" style="display:none;"></div>
</div>
