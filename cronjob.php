<?php
// Schedule the cron job based on admin setting
function schedule_currency_cron_job()
{
    $cron_timing = get_option('currency_cron_timing', 60); // Default to 60 minutes if not set

    // Clear existing cron job
    $timestamp = wp_next_scheduled('currency_price_update');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'currency_price_update');
    }

    // Schedule new cron job
    if (!wp_next_scheduled('currency_price_update')) {
        wp_schedule_event(time(), 'custom_interval', 'currency_price_update');
    }

    // Add custom interval
    add_filter('cron_schedules', function ($schedules) use ($cron_timing) {
        $schedules['custom_interval'] = array(
            'interval' => $cron_timing * 60, // Convert minutes to seconds
            'display'  => 'Custom Interval'
        );
        return $schedules;
    });
}
add_action('admin_init', 'schedule_currency_cron_job');

// Currency price update function
function currency_price_update()
{
    // دریافت تمامی پست‌های ارز
    $currencies = get_posts(array('post_type' => 'currency', 'numberposts' => -1));

    foreach ($currencies as $currency) {
        $site_url = get_post_meta($currency->ID, 'currency_site_url', true);
        $html_xpath = get_post_meta($currency->ID, 'currency_html_xpath', true);
        $price = fetch_price_from_site($site_url, $html_xpath);

        if ($price) {
            // Retain the existing is_rial and is_round_down meta values
            $is_rial = get_post_meta($currency->ID, 'is_rial', true);
            $is_round_down = get_post_meta($currency->ID, 'is_round_down', true);

            // Update the currency price
            update_post_meta($currency->ID, 'currency_price', $price);

            // Update the previous modified date
            $current_modified_date = get_post_field('post_modified', $currency->ID);
            update_post_meta($currency->ID, 'previous_modified_date', $current_modified_date);

            // Update the post modified date
            $updated_post = array(
                'ID' => $currency->ID,
                'post_modified' => current_time('mysql'),
                'post_modified_gmt' => current_time('mysql', 1)
            );
            wp_update_post($updated_post);

            // Ensure the is_rial and is_round_down meta values are retained
            if ($is_rial) {
                update_post_meta($currency->ID, 'is_rial', 'on');
            } else {
                delete_post_meta($currency->ID, 'is_rial');
            }

            if ($is_round_down) {
                update_post_meta($currency->ID, 'is_round_down', 'on');
            } else {
                delete_post_meta($currency->ID, 'is_round_down');
            }
        }
    }

    // فراخوانی تابع برای به‌روزرسانی تمامی قیمت محصولات
    update_all_product_prices();
}

add_action('currency_price_update', 'currency_price_update');