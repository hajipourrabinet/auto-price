<?php
/*
Plugin Name: به‌روزرسانی قیمت ارز
Description: پلاگینی برای به‌روزرسانی قیمت محصولات ووکامرس بر اساس نرخ ارز.
Version: 1.0
Author: mohtavanegar
*/

include_once('currency.php');
include_once('settings.php');
include_once('cronjob.php');
include_once('fix_price.php');

// اضافه کردن متاباکس‌ها برای محصولات ووکامرس
function add_woocommerce_product_meta_boxes()
{
    add_meta_box('woocommerce_currency_meta', 'گزینه‌های قیمت ارز', 'woocommerce_currency_meta_callback', 'product', 'side', 'default');
}

add_action('add_meta_boxes', 'add_woocommerce_product_meta_boxes');

function woocommerce_currency_meta_callback($post)
{
    $auto_price = get_post_meta($post->ID, 'auto_price', true);
    $selected_currency = get_post_meta($post->ID, 'selected_currency', true);
    $profit_percent = get_post_meta($post->ID, 'profit_percent', true);
    $base_price = get_post_meta($post->ID, 'base_price', true);
    $discounted_price = get_post_meta($post->ID, 'discounted_price', true);

    $currencies = get_posts(array('post_type' => 'currency', 'numberposts' => -1));
    ?>
    <label for="auto_price">فعال کردن قیمت خودکار:</label>
    <input type="checkbox" name="auto_price" <?php checked($auto_price, 'on'); ?> /><br/>

    <label for="selected_currency">انتخاب ارز:</label>
    <select name="selected_currency">
        <?php foreach ($currencies as $currency) : ?>
            <option value="<?php echo $currency->ID; ?>" <?php selected($selected_currency, $currency->ID); ?>><?php echo $currency->post_title; ?></option>
        <?php endforeach; ?>
    </select><br/>

    <label for="profit_percent">درصد سود:</label>
    <input type="number" name="profit_percent" class="short wc_input_price" value="<?php echo $profit_percent; ?>"/>
    <br/>

    <label for="base_price">قیمت پایه:</label>
    <input type="number" step="0.01" name="base_price" class="short wc_input_price" value="<?php echo $base_price; ?>"/>
    <br/>

    <label for="discounted_price">قیمت تخفیف‌دار:</label>
    <input type="number" step="0.01" name="discounted_price" class="short wc_input_price"
           value="<?php echo $discounted_price; ?>"/><br/>

    <button type="button" id="update_price_button">به‌روزرسانی قیمت</button>

    <script type="text/javascript">
        document.getElementById('update_price_button').addEventListener('click', function () {
            var data = {
                'action': 'update_single_product_price',
                'product_id': '<?php echo $post->ID; ?>'
            };

            jQuery.post(ajaxurl, data, function (response) {
                alert(response);
            });
        });
    </script>
    <?php
}

// پردازش درخواست AJAX برای به‌روزرسانی قیمت یک محصول
function update_single_product_price()
{
    $product_id = $_POST['product_id'];
    $rs = modify_and_update_woocommerce_price($product_id);
    echo json_encode($rs);
    wp_die();
}

add_action('wp_ajax_update_single_product_price', 'update_single_product_price');
function save_woocommerce_currency_meta($post_id)
{
    if (isset($_POST['auto_price'])) {
        update_post_meta($post_id, 'auto_price', $_POST['auto_price']);
    } else {
        delete_post_meta($post_id, 'auto_price');
    }
    if (isset($_POST['selected_currency'])) {
        update_post_meta($post_id, 'selected_currency', $_POST['selected_currency']);
    }
    if (isset($_POST['profit_percent'])) {
        update_post_meta($post_id, 'profit_percent', $_POST['profit_percent']);
    }
    if (isset($_POST['base_price'])) {
        update_post_meta($post_id, 'base_price', $_POST['base_price']);
    }
    if (isset($_POST['discounted_price'])) {
        update_post_meta($post_id, 'discounted_price', $_POST['discounted_price']);
    }
}

add_action('save_post', 'save_woocommerce_currency_meta');



// تابع جایگزین برای دریافت قیمت - نیاز به پیاده‌سازی واقعی دارد
function fetch_price_from_site($site_url, $html_xpath)
{
    // دریافت محتوای HTML از آدرس سایت
    $response = wp_remote_get($site_url);

    if (is_wp_error($response)) {
        return false;
    }

    $body = wp_remote_retrieve_body($response);

    // بارگذاری HTML به یک DOMDocument
    $dom = new DOMDocument();
    @$dom->loadHTML($body);

    // ایجاد یک شیء XPath جدید
    $xpath = new DOMXPath($dom);

    // پرسش از DOM با استفاده از XPath ارائه شده
    $nodes = $xpath->query($html_xpath);

    if ($nodes->length > 0) {
        // بازگشت محتوای اولین نود پیدا شده
        $price = $nodes->item(0)->nodeValue;
        // پاکسازی قیمت (حذف فضاهای اضافی، نمادهای ارزی و غیره)
        $price = preg_replace('/[^\d.]/', '', $price);
        return floatval($price);
    } else {
        return false;
    }
}

// به‌روزرسانی تمامی قیمت‌های محصولات بر اساس آخرین نرخ ارز
function update_all_product_prices() {
    // دریافت تمامی محصولات با متای auto_price فعال
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1, // اطمینان از عدم محدودیت در تعداد محصولات دریافت شده
        'meta_query' => array(
            array(
                'key' => 'auto_price',
                'value' => 'on',
                'compare' => '='
            )
        )
    );

    $products = new WP_Query($args);
    $count = 0;
    if ($products->have_posts()) {
        while ($products->have_posts()) {
            $products->the_post();
            $product_id = get_the_ID();
            modify_and_update_woocommerce_price($product_id);
            $count++;
        }
        wp_reset_postdata();
    }
    return $count;
}


// اصلاح و به‌روزرسانی قیمت محصول ووکامرس
function modify_and_update_woocommerce_price($product_id)
{
    $auto_price = get_post_meta($product_id, 'auto_price', true);
    $selected_currency = get_post_meta($product_id, 'selected_currency', true);
    $profit_percent = get_post_meta($product_id, 'profit_percent', true);

    if ($profit_percent === '' || $profit_percent === null) {
        $profit_percent = 0; // تعیین درصد سود پیش‌فرض اگر تنظیم نشده باشد
    }

    $rs = [];
    if ($auto_price && $selected_currency) {
        $currency_price = get_post_meta($selected_currency, 'currency_price', true);
        if ($currency_price) {
            $base_price = get_post_meta($product_id, 'base_price', true);
            $discounted_price = get_post_meta($product_id, 'discounted_price', true);
            $is_rial = get_post_meta($selected_currency, 'is_rial', true);
            $is_round_down = get_post_meta($selected_currency, 'is_round_down', true);
            if ($base_price) {
                $new_regular_price = $base_price * $currency_price * (1 + $profit_percent / 100);
                if ($is_rial === 'on') {
                    $new_regular_price /= 10;
                }
                if ($is_round_down === 'on') {
                    $new_regular_price = floor($new_regular_price);
                }
                update_post_meta($product_id, '_regular_price', $new_regular_price);
                update_post_meta($product_id, '_price', $new_regular_price);
                $rs['new_regular_price'] = $new_regular_price;
            }

            if ($discounted_price) {
                $new_sale_price = $discounted_price * $currency_price * (1 + $profit_percent / 100);
                if ($is_rial === 'on') {
                    $new_sale_price /= 10;
                }
                if ($is_round_down === 'on') {
                    $new_sale_price = floor($new_sale_price);
                }
                update_post_meta($product_id, '_sale_price', $new_sale_price);
                update_post_meta($product_id, '_price', $new_sale_price);
                $rs['new_sale_price'] = $new_sale_price;
            } else {
                update_post_meta($product_id, '_sale_price', "");
            }
        }
    }
    return $rs;
}

