<?php

// Hook to the admin menu action
add_action('admin_menu', 'wpg_item_count_menu');

function wpg_item_count_menu() {
    add_menu_page('WPG Item Count', 'WPG Item Count', 'manage_options', 'wpg-item-count', 'wpg_item_count_page', 'dashicons-admin-generic');
}

function wpg_item_count_page() {
    if (isset($_POST['wpg_item_count_update'])) {
        wpg_item_count_update();
    }

    ?>
    <div class="wrap">
        <h1>WPG Item Count</h1>
        <form method="post">
            <input type="hidden" name="wpg_item_count_update" value="1">
            <button type="submit" class="button button-primary">Update Item Count</button>
        </form>
    </div>
    <?php
}

function wpg_item_count_update() {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
    );
    $products = get_posts($args);

    foreach ($products as $product) {
     //   $price = get_post_meta($product->ID, '_price', true);
      //  update_post_meta($product->ID, 'base_price', $price);
        update_post_meta($product->ID, 'auto_price', "on");
        update_post_meta($product->ID, 'selected_currency', "4765");

    }

    echo '<div class="notice notice-success is-dismissible"><p>Item count updated successfully.</p></div>'.count($products);
}