<?php
/*
Plugin Name: Stock Notifications Pro (AR)
Plugin URI: https://sainthossam.com/wp-stock-notifications-pro
Description: إشعار العملاء عند توفر المنتج مع لوحة تحكم وإعداد SMTP.
Version: 1.0.0
Author: Saint Hossam
Author URI: https://github.com/SaintHossam/
Text Domain: stock-notifier
*/

if (!defined('ABSPATH')) exit;

function snp_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'stock_notifications';
}

function snp_defaults() {
    $domain = parse_url(home_url(), PHP_URL_HOST);
    return array(
        'enable_smtp' => 0,
        'smtp_host' => '',
        'smtp_port' => 587,
        'smtp_user' => '',
        'smtp_pass' => '',
        'smtp_secure' => 'tls',
        'from_email' => 'no-reply@' . $domain,
        'from_name' => get_bloginfo('name'),
        'reply_to' => get_option('admin_email'),
        'list_unsub' => 1,
        'unsub_url' => home_url('/?stock_unsub=1'),
        'subject_tpl' => '%site% – توفر المنتج: %product%',
        'success_message' => 'تم تسجيل طلب التنبيه بنجاح. سيتم إشعارك عند توفر المنتج.',
        'error_message' => 'حدث خطأ أثناء التسجيل. يرجى المحاولة مرة أخرى.',
        'button_text' => 'نبهني عند التوفير',
        'show_badge' => 1,
    );
}

function snp_get_option($key = null) {
    $opts = get_option('snp_options', array());
    $opts = wp_parse_args($opts, snp_defaults());
    return $key ? ($opts[$key] ?? null) : $opts;
}

function snp_update_option($data) {
    $cur = snp_get_option();
    $new = wp_parse_args($data, $cur);
    update_option('snp_options', $new);
}

function snp_activate() {
    global $wpdb;
    $table = snp_table_name();
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $sql = "CREATE TABLE {$table} (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        product_id bigint(20) NOT NULL,
        variation_id bigint(20) DEFAULT 0,
        user_email varchar(100) NOT NULL,
        user_name varchar(100) DEFAULT '',
        phone varchar(20) DEFAULT '',
        date_registered datetime DEFAULT CURRENT_TIMESTAMP,
        is_notified tinyint(1) DEFAULT 0,
        unsubscribed tinyint(1) DEFAULT 0,
        PRIMARY KEY (id),
        KEY product_id (product_id),
        KEY user_email (user_email)
    ) {$charset};";
    dbDelta($sql);
    if (!get_option('snp_options')) update_option('snp_options', snp_defaults());
}
register_activation_hook(__FILE__, 'snp_activate');

add_action('init', function () {
    if (!get_transient('snp_table_checked')) {
        snp_activate();
        set_transient('snp_table_checked', 1, 12 * HOUR_IN_SECONDS);
    }
});

add_filter('wp_mail_from', function () { return sanitize_email(snp_get_option('from_email')); });
add_filter('wp_mail_from_name', function () { return wp_specialchars_decode(snp_get_option('from_name'), ENT_QUOTES); });

add_action('phpmailer_init', function($phpmailer){
    $o = snp_get_option();
    if (!$o['enable_smtp']) return;

    $phpmailer->isSMTP();
    $phpmailer->Host       = $o['smtp_host'];
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Port       = (int) $o['smtp_port'];

    // استخدم قيم نصية للتشفير لتفادي الثوابت
    if ($o['smtp_secure'] === 'ssl') {
        $phpmailer->SMTPSecure = 'ssl';
    } elseif ($o['smtp_secure'] === 'tls') {
        $phpmailer->SMTPSecure = 'tls';
    } else {
        $phpmailer->SMTPSecure = '';
    }

    $phpmailer->Username   = $o['smtp_user'];
    $phpmailer->Password   = $o['smtp_pass'];

    $from_email = sanitize_email($o['from_email']);
    $from_name  = wp_specialchars_decode($o['from_name'], ENT_QUOTES);
    $phpmailer->setFrom($from_email, $from_name);

    if (!empty($o['reply_to'])) {
        $phpmailer->addReplyTo(sanitize_email($o['reply_to']));
    }

    $phpmailer->AltBody = 'المنتج الذي اشتركت للتنبيه عنه متوفر الآن. تفضل بزيارة رابط المنتج للشراء.';
});


function snp_headers() {
    $o = snp_get_option();
    $h = array('Content-Type: text/html; charset=UTF-8');
    if ($o['list_unsub']) {
        $mailto = 'mailto:' . sanitize_email($o['from_email']) . '?subject=unsubscribe';
        $url = esc_url($o['unsub_url']);
        $h[] = 'List-Unsubscribe: <' . $mailto . '>, <' . $url . '>';
        $h[] = 'List-Unsubscribe-Post: List-Unsubscribe=One-Click';
    }
    if (!empty($o['reply_to'])) $h[] = 'Reply-To: ' . sanitize_email($o['reply_to']);
    return $h;
}

add_filter('woocommerce_loop_add_to_cart_link', function ($html, $product) {
    $o = snp_get_option();
    if (!$product->is_in_stock() || !$product->is_purchasable()) {
        $url = get_permalink($product->get_id());
        return '<a href="' . esc_url($url) . '" class="button product_type_simple readmore-btn snp-notify-btn" data-product-id="' . esc_attr($product->get_id()) . '" rel="nofollow">' . esc_html($o['button_text']) . '</a>';
    }
    return $html;
}, 10, 2);

add_action('woocommerce_after_shop_loop_item_title', function () {
    $o = snp_get_option();
    global $product;
    if ($o['show_badge'] && $product && (!$product->is_in_stock() || !$product->is_purchasable())) {
        echo '<div class="snp-oos">نفدت الكمية</div>';
    }
}, 9);

add_filter('post_class', function ($classes, $class, $post_id) {
    $product = (get_post_type($post_id) === 'product') ? wc_get_product($post_id) : null;
    if ($product && (!$product->is_in_stock() || !$product->is_purchasable())) $classes[] = 'snp-is-oos';
    return $classes;
}, 10, 3);

add_action('woocommerce_single_product_summary', function () {
    global $product;
    if ($product && (!$product->is_in_stock() || !$product->is_purchasable())) {
        $o = snp_get_option();
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
        <?php
    }
}, 35);

add_action('wp_ajax_snp_register', 'snp_register');
add_action('wp_ajax_nopriv_snp_register', 'snp_register');

function snp_register() {
    if (empty($_POST['snp_nonce_field']) || !wp_verify_nonce($_POST['snp_nonce_field'], 'snp_nonce')) wp_send_json_error('خطأ في الأمان');
    global $wpdb;
    $table = snp_table_name();
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $variation_id = isset($_POST['variation_id']) ? intval($_POST['variation_id']) : 0;
    $user_email = isset($_POST['user_email']) ? sanitize_email($_POST['user_email']) : '';
    $user_name = isset($_POST['user_name']) ? sanitize_text_field($_POST['user_name']) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    if ($product_id <= 0) wp_send_json_error('معرّف المنتج غير صالح');
    if (!is_email($user_email)) wp_send_json_error('بريد إلكتروني غير صحيح');
    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE product_id=%d AND variation_id=%d AND user_email=%s AND is_notified=0 AND unsubscribed=0", $product_id, $variation_id, $user_email));
    if ($exists) wp_send_json_error('لقد تم تسجيلك مسبقاً لهذا المنتج');
    $ins = $wpdb->insert($table, array(
        'product_id' => $product_id,
        'variation_id' => $variation_id,
        'user_email' => $user_email,
        'user_name' => $user_name,
        'phone' => $phone,
        'date_registered' => current_time('mysql'),
        'is_notified' => 0,
        'unsubscribed' => 0,
    ), array('%d','%d','%s','%s','%s','%s','%d','%d'));
    if ($ins) {
        wp_send_json_success(snp_get_option('success_message'));
    } else {
        error_log('[SNP] DB Insert Error: ' . $wpdb->last_error);
        wp_send_json_error(snp_get_option('error_message'));
    }
}

add_action('woocommerce_product_set_stock', 'snp_maybe_send', 10, 1);
add_action('woocommerce_variation_set_stock', 'snp_maybe_send', 10, 1);

function snp_maybe_send($product) {
    if (!$product instanceof WC_Product) return;
    $qty = $product->get_stock_quantity();
    $in_stock = $product->get_stock_status() === 'instock' || (is_numeric($qty) && $qty > 0);
    if ($in_stock) snp_send_notifications($product->get_id());
}

function snp_send_notifications($product_id, $variation_id = 0) {
    global $wpdb;
    $table = snp_table_name();
    $subs = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE product_id=%d AND variation_id=%d AND is_notified=0 AND unsubscribed=0", $product_id, $variation_id));
    if (!$subs) return;
    $product = wc_get_product($product_id);
    if (!$product) return;
    foreach ($subs as $s) {
        snp_send_email($s, $product);
        $wpdb->update($table, array('is_notified' => 1), array('id' => $s->id), array('%d'), array('%d'));
    }
}

function snp_email_html($subscriber, $product) {
    $site = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
    ob_start(); ?>
    <div style="font-family:Arial,sans-serif;direction:rtl;max-width:600px;margin:0 auto;">
      <div style="background:#f0f0f0;padding:20px;text-align:center;">
        <h2 style="margin:0;color:#333;"><?php echo esc_html($site); ?></h2>
      </div>
      <div style="padding:20px;">
        <p>مرحباً <?php echo esc_html($subscriber->user_name ?: ''); ?>،</p>
        <p>المنتج الذي طلبت التنبيه عليه أصبح متاحاً الآن.</p>
        <p>المنتج: <strong><?php echo esc_html($product->get_name()); ?></strong></p>
        <p>السعر: <?php echo wp_kses_post($product->get_price_html()); ?></p>
        <p style="text-align:center;">
          <a href="<?php echo esc_url($product->get_permalink()); ?>" style="background:#0b74de;color:#fff;padding:12px 20px;text-decoration:none;border-radius:6px;display:inline-block;font-weight:600;">اشتري الآن</a>
        </p>
        <p style="color:#555;font-size:12px;">الكميات محدودة.</p>
      </div>
      <div style="background:#ddd;padding:15px;text-align:center;font-size:12px;color:#444;">
        تم إرسال هذا الإشعار بناءً على طلبك. © <?php echo esc_html($site); ?>
      </div>
    </div>
    <?php
    return ob_get_clean();
}

function snp_send_email($subscriber, $product) {
    $o = snp_get_option();
    $subject = strtr($o['subject_tpl'], array(
        '%site%' => wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES),
        '%product%' => $product->get_name(),
    ));
    $html = snp_email_html($subscriber, $product);
    wp_mail($subscriber->user_email, $subject, $html, snp_headers());
}

add_action('template_redirect', function () {
    if (isset($_GET['stock_unsub'])) {
        $email = isset($_GET['email']) ? sanitize_email($_GET['email']) : '';
        if ($email && is_email($email)) {
            global $wpdb;
            $table = snp_table_name();
            $wpdb->query($wpdb->prepare("UPDATE {$table} SET unsubscribed=1 WHERE user_email=%s", $email));
            wp_die('<div style="font-family:Arial;direction:rtl;text-align:center;padding:40px;">تم إلغاء الاشتراك بنجاح.</div>', 'إلغاء الاشتراك');
        } else {
            wp_die('<div style="font-family:Arial;direction:rtl;text-align:center;padding:40px;">بريد غير صالح.</div>', 'إلغاء الاشتراك');
        }
        exit;
    }
});

add_action('wp_enqueue_scripts', function () {
    if (is_woocommerce() || is_cart() || is_shop() || is_product_category() || is_product()) {
        wp_enqueue_script('jquery');
        $msg_ok = esc_js(snp_get_option('success_message'));
        $msg_err = esc_js(snp_get_option('error_message'));
        $js = 'jQuery(document).ready(function(jQuery){
            jQuery("#snp-form").on("submit", function(e){
                e.preventDefault();
                var f=jQuery(this),b=f.find("button[type=submit]"),m=jQuery("#snp-msg");
                b.prop("disabled",true).text("جاري التسجيل...");
                jQuery.ajax({
                    url:"' . esc_url(admin_url('admin-ajax.php')) . '",
                    type:"POST",
                    dataType:"json",
                    data:f.serialize(),
                    success:function(r){
                        if(r && r.success){ m.html("<div class=\"snp-ok\">"+r.data+"</div>").show(); f[0].reset(); }
                        else{ var t=(r && r.data)?r.data:"' . $msg_err . '"; m.html("<div class=\"snp-err\">"+t+"</div>").show(); }
                    },
                    error:function(){ m.html("<div class=\"snp-err\">' . $msg_err . '</div>").show(); },
                    complete:function(){ b.prop("disabled",false).text("تسجيل التنبيه"); setTimeout(function(){ m.fadeOut(); },5000); }
                });
            });
            jQuery(document).on("click",".snp-notify-btn",function(e){
                var form=jQuery(".snp-form");
                if(form.length){ e.preventDefault(); jQuery("html, body").animate({scrollTop:form.offset().top-100},500); setTimeout(function(){ form.find("input[name=user_name]").focus(); },600); }
            });
        });';
        wp_register_script('snp-inline', '', array('jquery'), null, true);
        wp_enqueue_script('snp-inline');
        wp_add_inline_script('snp-inline', $js);

        $css = '.snp-form{background:#f9f9f9;border:1px solid #ddd;border-radius:8px;padding:20px;margin:20px 0;max-width:420px}
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
        wp_register_style('snp-inline', false);
        wp_enqueue_style('snp-inline');
        wp_add_inline_style('snp-inline', $css);
    }
});

add_action('admin_menu', function () {
    add_menu_page('إشعارات المخزون', 'إشعارات المخزون', 'manage_woocommerce', 'snp-stock', 'snp_admin_router', 'dashicons-bell', 56);
});

function snp_admin_router() {
    $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'dashboard';
    echo '<div class="wrap snp-wrap"><h1>إشعارات المخزون</h1>';
    if (isset($_GET['deleted'])) echo '<div class="notice notice-success"><p>تم حذف '.intval($_GET['deleted']).' طلب.</p></div>';
    echo '<h2 class="nav-tab-wrapper">';
    echo '<a href="'.esc_url(admin_url('admin.php?page=snp-stock&tab=dashboard')).'" class="nav-tab '.($tab==='dashboard'?'nav-tab-active':'').'">لوحة التحكم</a>';
    echo '<a href="'.esc_url(admin_url('admin.php?page=snp-stock&tab=requests')).'" class="nav-tab '.($tab==='requests'?'nav-tab-active':'').'">الطلبات</a>';
    echo '<a href="'.esc_url(admin_url('admin.php?page=snp-stock&tab=settings')).'" class="nav-tab '.($tab==='settings'?'nav-tab-active':'').'">الإعدادات</a>';
    echo '<a href="'.esc_url(admin_url('admin.php?page=snp-stock&tab=test')).'" class="nav-tab '.($tab==='test'?'nav-tab-active':'').'">اختبار البريد</a>';
    echo '</h2>';
    echo '<style>.snp-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin:20px 0;box-shadow:0 2px 12px rgba(0,0,0,.04)}.snp-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}.snp-stat{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:16px;text-align:center}.snp-input{width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px} .snp-label{font-weight:600;margin-bottom:6px;display:block} .snp-btn{background:#0b74de;color:#fff;border:0;padding:10px 16px;border-radius:8px;cursor:pointer;font-weight:600} .snp-row{display:grid;grid-template-columns:1fr 2fr;gap:12px;margin-bottom:12px} table.widefat td,table.widefat th{text-align:right} .snp-note{font-size:12px;color:#64748b} .snp-danger{background:#ef4444;color:#fff;border:0;padding:6px 10px;border-radius:6px}</style>';
    if ($tab === 'settings') snp_admin_settings();
    elseif ($tab === 'test') snp_admin_test();
    elseif ($tab === 'requests') snp_admin_requests();
    else snp_admin_dashboard();
    echo '</div>';
}


function snp_admin_requests() {
    global $wpdb; $t = snp_table_name();
    $status = isset($_GET['status']) ? sanitize_key($_GET['status']) : 'all';
    $q = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
    $where = '1=1';
    if ($status==='pending') $where .= ' AND is_notified=0 AND unsubscribed=0';
    elseif ($status==='sent') $where .= ' AND is_notified=1';
    elseif ($status==='unsub') $where .= ' AND unsubscribed=1';
    if ($q) $where .= $wpdb->prepare(' AND (user_email LIKE %s OR user_name LIKE %s)', '%'.$q.'%', '%'.$q.'%');
    $rows = $wpdb->get_results("SELECT * FROM {$t} WHERE {$where} ORDER BY date_registered DESC LIMIT 100");
    $base = admin_url('admin.php?page=snp-stock&tab=requests');
    echo '<div class="snp-card"><h2>إدارة الطلبات</h2>';
    echo '<form method="get" style="margin:0 0 12px 0">';
    echo '<input type="hidden" name="page" value="snp-stock"><input type="hidden" name="tab" value="requests">';
    echo '<div class="snp-row"><label class="snp-label">حالة</label><select class="snp-input" name="status"><option value="all" '.selected($status,'all',false).'>الكل</option><option value="pending" '.selected($status,'pending',false).'>في الانتظار</option><option value="sent" '.selected($status,'sent',false).'>تم الإرسال</option><option value="unsub" '.selected($status,'unsub',false).'>ألغى الاشتراك</option></select></div>';
    echo '<div class="snp-row"><label class="snp-label">بحث</label><input class="snp-input" type="text" name="q" value="'.esc_attr($q).'" placeholder="اسم أو بريد"></div>';
    echo '<p><button class="snp-btn" type="submit">تحديث</button></p>';
    echo '</form>';
    echo '<form method="post">';
    wp_nonce_field('snp_bulk_delete');
    echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th style="width:30px"><input type="checkbox" onclick="jQuery(\'.snp-chk\').prop(\'checked\',this.checked)"></th><th>المنتج</th><th>العميل</th><th>البريد</th><th>الهاتف</th><th>التاريخ</th><th>إجراء</th></tr></thead><tbody>';
    if ($rows) {
        foreach ($rows as $r) {
            $p = wc_get_product($r->product_id);
            $del = wp_nonce_url($base.'&action=snp_delete&id='.$r->id, 'snp_delete_'.$r->id);
            echo '<tr>';
            echo '<td><input class="snp-chk" type="checkbox" name="ids[]" value="'.esc_attr($r->id).'"></td>';
            echo '<td>'.($p?esc_html($p->get_name()):'منتج محذوف').'</td>';
            echo '<td>'.esc_html($r->user_name).'</td>';
            echo '<td>'.esc_html($r->user_email).'</td>';
            echo '<td>'.($r->phone?esc_html($r->phone):'-').'</td>';
            echo '<td>'.esc_html(date('Y-m-d H:i', strtotime($r->date_registered))).'</td>';
            echo '<td><a class="snp-danger" href="'.esc_url($del).'" onclick="return confirm(\'حذف هذا الطلب؟\');">حذف</a></td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="7">لا توجد نتائج.</td></tr>';
    }
    echo '</tbody></table>';
    echo '<p><button class="snp-btn" type="submit" name="snp_bulk_delete" value="1" onclick="return confirm(\'حذف كل المحدد؟\');">حذف المحدد</button></p>';
    echo '</form></div>';
}


function snp_admin_dashboard() {
    global $wpdb;
    $t = snp_table_name();
    $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t}");
    $pending = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE is_notified=0 AND unsubscribed=0");
    $sent = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE is_notified=1");
    $rows_pending = $wpdb->get_results("SELECT * FROM {$t} WHERE is_notified=0 AND unsubscribed=0 ORDER BY date_registered DESC LIMIT 20");
    $rows_sent = $wpdb->get_results("SELECT * FROM {$t} WHERE is_notified=1 ORDER BY date_registered DESC LIMIT 20");
    $base = admin_url('admin.php?page=snp-stock&tab=dashboard');
    echo '<div class="snp-grid">';
    echo '<div class="snp-stat"><div>إجمالي الطلبات</div><h2>'.$total.'</h2></div>';
    echo '<div class="snp-stat"><div>في الانتظار</div><h2>'.$pending.'</h2></div>';
    echo '<div class="snp-stat"><div>تم الإرسال</div><h2>'.$sent.'</h2></div>';
    echo '</div>';
    echo '<div class="snp-card"><h2>أحدث الطلبات المعلقة</h2>';
    echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>المنتج</th><th>العميل</th><th>البريد</th><th>الهاتف</th><th>التاريخ</th><th>إجراء</th></tr></thead><tbody>';
    if ($rows_pending) {
        foreach ($rows_pending as $r) {
            $p = wc_get_product($r->product_id);
            $del = wp_nonce_url($base.'&action=snp_delete&id='.$r->id, 'snp_delete_'.$r->id);
            echo '<tr>';
            echo '<td>'.($p?esc_html($p->get_name()):'منتج محذوف').'</td>';
            echo '<td>'.esc_html($r->user_name).'</td>';
            echo '<td>'.esc_html($r->user_email).'</td>';
            echo '<td>'.($r->phone?esc_html($r->phone):'-').'</td>';
            echo '<td>'.esc_html(date('Y-m-d H:i', strtotime($r->date_registered))).'</td>';
            echo '<td><a class="snp-danger" href="'.esc_url($del).'" onclick="return confirm(\'حذف هذا الطلب؟\');">حذف</a></td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="6">لا توجد طلبات معلّقة.</td></tr>';
    }
    echo '</tbody></table></div>';
    echo '<div class="snp-card"><h2>أحدث الطلبات التي تم إرسالها</h2>';
    echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>المنتج</th><th>العميل</th><th>البريد</th><th>الهاتف</th><th>التاريخ</th><th>إجراء</th></tr></thead><tbody>';
    if ($rows_sent) {
        foreach ($rows_sent as $r) {
            $p = wc_get_product($r->product_id);
            $del = wp_nonce_url($base.'&action=snp_delete&id='.$r->id, 'snp_delete_'.$r->id);
            echo '<tr>';
            echo '<td>'.($p?esc_html($p->get_name()):'منتج محذوف').'</td>';
            echo '<td>'.esc_html($r->user_name).'</td>';
            echo '<td>'.esc_html($r->user_email).'</td>';
            echo '<td>'.($r->phone?esc_html($r->phone):'-').'</td>';
            echo '<td>'.esc_html(date('Y-m-d H:i', strtotime($r->date_registered))).'</td>';
            echo '<td><a class="snp-danger" href="'.esc_url($del).'" onclick="return confirm(\'حذف هذا الطلب؟\');">حذف</a></td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="6">لا توجد طلبات مُرسلة بعد.</td></tr>';
    }
    echo '</tbody></table></div>';
}



function snp_admin_settings() {
    $o = snp_get_option();
    if (!empty($_POST['snp_save']) && check_admin_referer('snp_save_settings')) {
        $data = array(
            'enable_smtp' => isset($_POST['enable_smtp']) ? 1 : 0,
            'smtp_host' => sanitize_text_field($_POST['smtp_host'] ?? ''),
            'smtp_port' => intval($_POST['smtp_port'] ?? 587),
            'smtp_user' => sanitize_text_field($_POST['smtp_user'] ?? ''),
            'smtp_pass' => sanitize_text_field($_POST['smtp_pass'] ?? ''),
            'smtp_secure' => in_array($_POST['smtp_secure'] ?? 'tls', array('none','ssl','tls'), true) ? $_POST['smtp_secure'] : 'tls',
            'from_email' => sanitize_email($_POST['from_email'] ?? ''),
            'from_name' => sanitize_text_field($_POST['from_name'] ?? ''),
            'reply_to' => sanitize_email($_POST['reply_to'] ?? ''),
            'list_unsub' => isset($_POST['list_unsub']) ? 1 : 0,
            'unsub_url' => esc_url_raw($_POST['unsub_url'] ?? ''),
            'subject_tpl' => wp_kses_post($_POST['subject_tpl'] ?? ''),
            'success_message' => wp_kses_post($_POST['success_message'] ?? ''),
            'error_message' => wp_kses_post($_POST['error_message'] ?? ''),
            'button_text' => sanitize_text_field($_POST['button_text'] ?? ''),
            'show_badge' => isset($_POST['show_badge']) ? 1 : 0,
        );
        snp_update_option($data);
        $o = snp_get_option();
        echo '<div class="notice notice-success"><p>تم الحفظ.</p></div>';
    }
    echo '<form method="post" class="snp-card">';
    wp_nonce_field('snp_save_settings');
    echo '<h2>الإعدادات العامة والبريد</h2>';
    echo '<div class="snp-row"><label class="snp-label">تفعيل SMTP</label><input type="checkbox" name="enable_smtp" '.checked($o['enable_smtp'],1,false).' /></div>';
    echo '<div class="snp-row"><label class="snp-label">SMTP Host</label><input class="snp-input" type="text" name="smtp_host" value="'.esc_attr($o['smtp_host']).'"></div>';
    echo '<div class="snp-row"><label class="snp-label">SMTP Port</label><input class="snp-input" type="number" name="smtp_port" value="'.esc_attr($o['smtp_port']).'"></div>';
    echo '<div class="snp-row"><label class="snp-label">SMTP Username</label><input class="snp-input" type="text" name="smtp_user" value="'.esc_attr($o['smtp_user']).'"></div>';
    echo '<div class="snp-row"><label class="snp-label">SMTP Password</label><input class="snp-input" type="password" name="smtp_pass" value="'.esc_attr($o['smtp_pass']).'"></div>';
    echo '<div class="snp-row"><label class="snp-label">التشفير</label><select class="snp-input" name="smtp_secure"><option value="none" '.selected($o['smtp_secure'],'none',false).'>None</option><option value="ssl" '.selected($o['smtp_secure'],'ssl',false).'>SSL</option><option value="tls" '.selected($o['smtp_secure'],'tls',false).'>TLS</option></select></div>';
    echo '<div class="snp-row"><label class="snp-label">From Email</label><input class="snp-input" type="email" name="from_email" value="'.esc_attr($o['from_email']).'"></div>';
    echo '<div class="snp-row"><label class="snp-label">From Name</label><input class="snp-input" type="text" name="from_name" value="'.esc_attr($o['from_name']).'"></div>';
    echo '<div class="snp-row"><label class="snp-label">Reply-To</label><input class="snp-input" type="email" name="reply_to" value="'.esc_attr($o['reply_to']).'"></div>';
    echo '<div class="snp-row"><label class="snp-label">List-Unsubscribe</label><input type="checkbox" name="list_unsub" '.checked($o['list_unsub'],1,false).' /><span class="snp-note">يوصى بتفعيله</span></div>';
    echo '<div class="snp-row"><label class="snp-label">رابط إلغاء الاشتراك</label><input class="snp-input" type="text" name="unsub_url" value="'.esc_attr($o['unsub_url']).'"></div>';
    echo '<div class="snp-row"><label class="snp-label">عنوان الرسالة</label><input class="snp-input" type="text" name="subject_tpl" value="'.esc_attr($o['subject_tpl']).'"><span class="snp-note">%site% و %product%</span></div>';
    echo '<div class="snp-row"><label class="snp-label">نص النجاح</label><input class="snp-input" type="text" name="success_message" value="'.esc_attr($o['success_message']).'"></div>';
    echo '<div class="snp-row"><label class="snp-label">نص الخطأ</label><input class="snp-input" type="text" name="error_message" value="'.esc_attr($o['error_message']).'"></div>';
    echo '<div class="snp-row"><label class="snp-label">نص زر الأرشيف</label><input class="snp-input" type="text" name="button_text" value="'.esc_attr($o['button_text']).'"></div>';
    echo '<div class="snp-row"><label class="snp-label">شارة نفاد الكمية</label><input type="checkbox" name="show_badge" '.checked($o['show_badge'],1,false).' /></div>';
    echo '<p><button class="snp-btn" type="submit" name="snp_save" value="1">حفظ</button></p>';
    echo '</form>';
}

function snp_admin_test() {
    if (!empty($_POST['snp_test_send']) && check_admin_referer('snp_test_mail')) {
        $to = sanitize_email($_POST['snp_test_to'] ?? '');
        if ($to && is_email($to)) {
            $site = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
            $subject = $site . ' – اختبار إعدادات البريد';
            $html = '<div style="font-family:Arial;direction:rtl;padding:20px"><h2>اختبار البريد</h2><p>هذه رسالة اختبارية من '.esc_html($site).'.</p></div>';
            $sent = wp_mail($to, $subject, $html, snp_headers());
            echo $sent ? '<div class="notice notice-success"><p>تم إرسال رسالة اختبار إلى '.$to.'.</p></div>' : '<div class="notice notice-error"><p>فشل الإرسال. راجع إعدادات SMTP وDNS.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>بريد غير صالح.</p></div>';
        }
    }
    echo '<form method="post" class="snp-card">';
    wp_nonce_field('snp_test_mail');
    echo '<h2>إرسال رسالة اختبار</h2>';
    echo '<div class="snp-row"><label class="snp-label">أرسل إلى</label><input class="snp-input" type="email" name="snp_test_to" placeholder="you@example.com"></div>';
    echo '<p><button class="snp-btn" type="submit" name="snp_test_send" value="1">إرسال الاختبار</button></p>';
    echo '</form>';
}

add_action('wp_mail_failed', function($wp_error){
    error_log('[SNP] wp_mail_failed: ' . $wp_error->get_error_message());
});


add_action('admin_init','snp_admin_handle_delete');
function snp_admin_handle_delete(){
    if (!current_user_can('manage_woocommerce')) return;
    if (isset($_GET['page']) && $_GET['page']==='snp-stock' && isset($_GET['action']) && $_GET['action']==='snp_delete') {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'snp_delete_'.$id)) {
            global $wpdb; $wpdb->delete(snp_table_name(), array('id'=>$id), array('%d'));
            wp_safe_redirect(remove_query_arg(array('action','id','_wpnonce')).'&deleted=1'); exit;
        }
    }
    if (isset($_POST['snp_bulk_delete']) && check_admin_referer('snp_bulk_delete')) {
        $ids = array_map('intval', $_POST['ids'] ?? array());
        if ($ids) {
            global $wpdb;
            $ph = implode(',', array_fill(0,count($ids),'%d'));
            $wpdb->query($wpdb->prepare("DELETE FROM ".snp_table_name()." WHERE id IN ($ph)", $ids));
            wp_safe_redirect(admin_url('admin.php?page=snp-stock&tab=requests&deleted='.count($ids))); exit;
        }
    }
}

