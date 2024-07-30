<?php

// ثبت نوع پست سفارشی برای ارز
function create_currency_post_type()
{
    register_post_type('currency',
        array(
            'labels' => array(
                'name' => __('ارزها'),
                'singular_name' => __('ارز')
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => array('title')
        )
    );
}

add_action('init', 'create_currency_post_type');

// اضافه کردن متاباکس‌ها برای نوع پست ارز
function add_currency_meta_boxes()
{
    add_meta_box('currency_meta', 'جزئیات ارز', 'currency_meta_callback', 'currency', 'normal', 'high');
}

add_action('add_meta_boxes', 'add_currency_meta_boxes');

function currency_meta_callback($post)
{
    $price = get_post_meta($post->ID, 'currency_price', true);
    $site_url = get_post_meta($post->ID, 'currency_site_url', true);
    $html_xpath = get_post_meta($post->ID, 'currency_html_xpath', true);
    $is_rial = get_post_meta($post->ID, 'is_rial', true);
    $is_round_down = get_post_meta($post->ID, 'is_round_down', true);
    ?>
    <label for="currency_price">قیمت:</label>
    <input type="text" id="currency_price" name="currency_price" value="<?php echo esc_attr($price); ?>"/>
    <button type="button" id="update_price_button">به‌روزرسانی</button><br/>

    <label for="currency_site_url">آدرس سایت:</label>
    <input type="text" id="currency_site_url" name="currency_site_url" value="<?php echo esc_attr($site_url); ?>"/><br/>

    <label for="currency_html_xpath">XPath HTML:</label>
    <input type="text" id="currency_html_xpath" name="currency_html_xpath" value="<?php echo esc_attr($html_xpath); ?>"/><br/>

    <label for="is_rial">ریال است:</label>
    <input type="checkbox" id="is_rial" name="is_rial" <?php checked($is_rial, 'on'); ?> /><br/>

    <label for="is_round_down">به پایین گرد شود:</label>
    <input type="checkbox" id="is_round_down" name="is_round_down" <?php checked($is_round_down, 'on'); ?> /><br/>

    <script type="text/javascript">
        document.getElementById('update_price_button').addEventListener('click', function () {
            var siteUrl = document.getElementById('currency_site_url').value;
            var htmlXpath = document.getElementById('currency_html_xpath').value;

            var data = {
                'action': 'update_currency_price',
                'site_url': siteUrl,
                'html_xpath': htmlXpath,
                'post_id': '<?php echo $post->ID; ?>'
            };

            jQuery.post(ajaxurl, data, function (response) {
                document.getElementById('currency_price').value = response;
            });
        });
    </script>
    <?php
}

function save_currency_meta($post_id)
{
    if (isset($_POST['currency_price'])) {
        update_post_meta($post_id, 'currency_price', sanitize_text_field($_POST['currency_price']));
    }
    if (isset($_POST['currency_site_url'])) {
        update_post_meta($post_id, 'currency_site_url', esc_url_raw($_POST['currency_site_url']));
    }
    if (isset($_POST['currency_html_xpath'])) {
        update_post_meta($post_id, 'currency_html_xpath', sanitize_text_field($_POST['currency_html_xpath']));
    }
    if (isset($_POST['is_rial'])) {
        update_post_meta($post_id, 'is_rial', 'on');
    } elseif (!defined('DOING_CRON')) {
        // Only delete meta if not in a cron job
        delete_post_meta($post_id, 'is_rial');
    }
    if (isset($_POST['is_round_down'])) {
        update_post_meta($post_id, 'is_round_down', 'on');
    } elseif (!defined('DOING_CRON')) {
        // Only delete meta if not in a cron job
        delete_post_meta($post_id, 'is_round_down');
    }
}

add_action('save_post', 'save_currency_meta');

// پردازش درخواست AJAX برای به‌روزرسانی قیمت ارز
function update_currency_price()
{
    $site_url = esc_url_raw($_POST['site_url']);
    $html_xpath = sanitize_text_field($_POST['html_xpath']);
    $post_id = intval($_POST['post_id']);

    // استفاده از site_url و html_xpath برای دریافت قیمت
    $price = fetch_price_from_site($site_url, $html_xpath);

    if ($price) {
        update_post_meta($post_id, 'currency_price', sanitize_text_field($price));
        echo sanitize_text_field($price);
    } else {
        echo 'خطا در دریافت قیمت';
    }

    wp_die();
}

add_action('wp_ajax_update_currency_price', 'update_currency_price');

// Add custom columns to the currency post type
function set_custom_currency_columns($columns)
{
    $columns = array(
        'cb' => '<input type="checkbox" />',
        'title' => __('عنوان'),
        'currency_price' => __('قیمت ارز'),
        'previous_modified_date' => __('قیمت قبلی'),
        'last_update' => __('آخرین به‌روزرسانی'),
    );
    return $columns;
}
add_filter('manage_currency_posts_columns', 'set_custom_currency_columns');

// Populate custom columns with data
function custom_currency_column($column, $post_id)
{
    switch ($column) {
        case 'currency_price':
            $currency_price = get_post_meta($post_id, 'currency_price', true);
            echo $currency_price ? esc_html($currency_price) : 'نا مشخص';
            break;
        case 'previous_modified_date':
            $previous_modified_date = get_post_meta($post_id, 'previous_modified_date', true);
            echo $previous_modified_date ? esc_html($previous_modified_date) : 'نا مشخص';
            break;
        case 'last_update':
            $last_update = get_post_meta($post_id, '_edit_last', true);
            $last_update_date = $last_update ? get_post_field('post_modified', $post_id) : 'نا مشخص';
            echo esc_html($last_update_date);
            break;
    }
}
add_action('manage_currency_posts_custom_column', 'custom_currency_column', 10, 2);

// Make custom columns sortable
function set_custom_currency_sortable_columns($columns)
{
    $columns['currency_price'] = 'currency_price';
    $columns['previous_modified_date'] = 'previous_modified_date';
    $columns['last_update'] = 'last_update';
    return $columns;
}
add_filter('manage_edit-currency_sortable_columns', 'set_custom_currency_sortable_columns');

// Add custom sorting logic for sortable columns
function custom_currency_orderby($query)
{
    if (!is_admin()) {
        return;
    }

    $orderby = $query->get('orderby');

    if ('currency_price' == $orderby) {
        $query->set('meta_key', 'currency_price');
        $query->set('orderby', 'meta_value_num');
    }

    if ('previous_modified_date' == $orderby) {
        $query->set('meta_key', 'previous_modified_date');
        $query->set('orderby', 'meta_value');
    }

    if ('last_update' == $orderby) {
        $query->set('orderby', 'modified');
    }
}
add_action('pre_get_posts', 'custom_currency_orderby');

// Add custom buttons to the top of the currency list table
function add_custom_currency_buttons() {
    global $typenow;
    if ($typenow == 'currency') {
        echo '<div class="alignleft actions">';
        echo '<form method="post" action="">';
        echo '<input type="hidden" name="custom_action" value="update_prices">';
        submit_button('به‌روزرسانی قیمت محصولات', 'primary', 'update_prices_button', false);
        echo ' ';
        echo '<input type="hidden" name="custom_action" value="update_all_currencies">';
        submit_button('به‌روزرسانی تمام نرخ‌های ارز', 'primary', 'update_all_currencies_button', false);
        echo '</form>';
        echo '</div>';
    }
}
add_action('restrict_manage_posts', 'add_custom_currency_buttons');

// Handle form submissions
function handle_custom_actions() {
    if (isset($_POST['custom_action'])) {
        if ($_POST['custom_action'] == 'update_prices') {
            $count = update_all_product_prices();
            wp_redirect(add_query_arg('updated_prices_count', $count, wp_get_referer()));
            exit;
        }
        if ($_POST['custom_action'] == 'update_all_currencies') {
            do_action('currency_price_update');
            wp_redirect(add_query_arg('updated_currencies', 'true', wp_get_referer()));
            exit;
        }
    }
}
add_action('admin_init', 'handle_custom_actions');

// Display admin notices
function custom_admin_notices() {
    if (isset($_GET['updated_prices_count'])) {
        $count = intval($_GET['updated_prices_count']);
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>' . sprintf('قیمت‌های %d محصول با موفقیت به‌روزرسانی شدند.', $count) . '</p>';
        echo '</div>';
    }
    if (isset($_GET['updated_currencies'])) {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>تمام نرخ‌های ارز با موفقیت به‌روزرسانی شدند.</p>';
        echo '</div>';
    }
}
add_action('admin_notices', 'custom_admin_notices');