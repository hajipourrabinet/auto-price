<?php
// Initialize the settings
// Initialize the settings
function currency_settings_init()
{
    // Register the setting so it gets saved and loaded properly
    register_setting('currency_settings', 'currency_cron_timing');
}

// Add the menu item and submenu items
function add_currency_settings_menu()
{
    // Add a new top-level menu
    add_menu_page(
        'تنظیمات افزونه ارز خودکار',  // Page title
        'تنظیمات ارز خودکار',          // Menu title
        'manage_options',              // Capability
        'currency-settings',           // Menu slug
        'currency_settings_page',      // Callback function
        'dashicons-admin-generic',     // Icon
        80                             // Position
    );

    // Add a submenu item under the new top-level menu
    add_submenu_page(
        'currency-settings',           // Parent slug
        'تنظیمات ارز خودکار',          // Page title
        'تنظیمات',                    // Menu title
        'manage_options',              // Capability
        'currency-settings',           // Menu slug
        'currency_settings_page'       // Callback function
    );
}
add_action('admin_menu', 'add_currency_settings_menu');

// Display the settings page
function currency_settings_page()
{
    ?>
    <div class="wrap">
        <h1>تنظیمات افزونه ارز خودکار</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('currency_settings');
            do_settings_sections('currency-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Add the settings section and field to the new settings page
function currency_settings_section_init()
{
    // Add a new section in the new settings page
    add_settings_section(
        'currency_settings_section',
        'تنظیمات ارز',
        'currency_settings_section_callback',
        'currency-settings'
    );

    // Add a new field in the new settings section
    add_settings_field(
        'currency_cron_timing',
        'زمان‌بندی کرون جاب (دقیقه)',
        'currency_cron_timing_callback',
        'currency-settings',
        'currency_settings_section'
    );
}
add_action('admin_init', 'currency_settings_init');
add_action('admin_init', 'currency_settings_section_init');

// Section callback function
function currency_settings_section_callback()
{
    echo '<p>تنظیمات برای به‌روزرسانی قیمت ارز.</p>';
}

// Field callback function
function currency_cron_timing_callback()
{
    $value = get_option('currency_cron_timing', '');
    echo '<input type="number" id="currency_cron_timing" name="currency_cron_timing" value="' . esc_attr($value) . '" />';
}
