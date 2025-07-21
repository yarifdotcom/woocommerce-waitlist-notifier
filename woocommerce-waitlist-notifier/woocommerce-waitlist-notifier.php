<?php
/**
 * Plugin Name: WooCommerce Waitlist Notifier
 * Description: Adds a waitlist button for out-of-stock products and notifies the admin via email.
 * Version: 1.0
 * Author: GrintByte
 * Author URI: https://grintbyte.com
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define('WWN_PATH', plugin_dir_path(__FILE__));

// Load core functions
require_once WWN_PATH . 'includes/waitlist-functions.php';

// Register settings tab for Waitlist
add_filter('woocommerce_get_settings_pages', 'wwn_add_settings_tab');

function wwn_add_settings_tab($settings_tabs) {
    require_once WWN_PATH . 'includes/class-wwn-settings-page.php';
    $settings_page = new WWN_Waitlist_Settings_Page();
    $settings_tabs[] = $settings_page;

    // Delay save action until plugins_loaded to avoid early execution
    add_action('plugins_loaded', function () use ($settings_page) {
        add_action('woocommerce_update_options_' . $settings_page->id, [$settings_page, 'save']);
    });

    return $settings_tabs;
}


// Create waitlist table on plugin activation
register_activation_hook(__FILE__, 'wwn_create_table');
if ( ! function_exists('wwn_create_table') ) {
    function wwn_create_table() {
        global $wpdb;
    
        $table = $wpdb->prefix . 'wwn_waitlist';
        $charset = $wpdb->get_charset_collate();
    
        $sql = "CREATE TABLE $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED DEFAULT 0,
            email VARCHAR(100) NOT NULL,
            product_id BIGINT(20) UNSIGNED NOT NULL,
            date DATETIME DEFAULT CURRENT_TIMESTAMP,
            state VARCHAR(20) DEFAULT 'waiting',
            PRIMARY KEY (id)
        ) $charset;";
    
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Set default notification email on first install
        if (get_option('wwn_notify_email') === false) {
            update_option('wwn_notify_email', get_option('admin_email'));
        }
    }
}


// Add Waitlist tab in My Account
add_filter('woocommerce_account_menu_items', 'wwn_add_waitlist_account_tab');
function wwn_add_waitlist_account_tab($items) {
    $items['waitlists'] = __('My Enquiry', 'woocommerce');
    return $items;
}

add_action('init', function () {
    add_rewrite_endpoint('waitlists', EP_ROOT | EP_PAGES);
});

// Change the page title on the "waitlists" tab
add_filter('the_title', 'wwn_custom_account_title_for_waitlist', 10, 2);
function wwn_custom_account_title_for_waitlist($title, $post_id) {
    if (is_account_page() && get_the_ID() === $post_id && is_user_logged_in()) {
        global $wp;

        // If the current endpoint is "waitlists", change the title
        if (isset($wp->query_vars['waitlists'])) {
            $title = __('My Enquiry List', 'woocommerce');
        }
    }
    return $title;
}

// Optional: Style the waitlist output
add_action('wp_enqueue_scripts', function () {
    wp_add_inline_style('woocommerce-layout', '
        .woocommerce-waitlist { list-style: disc; margin-left: 20px; }
        .woocommerce-waitlist li { margin-bottom: 8px; }
    ');
});


// 🔐 Rewrite rules for endpoint
register_activation_hook(__FILE__, function () {
    add_rewrite_endpoint('waitlists', EP_ROOT | EP_PAGES);
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});



add_action('woocommerce_account_waitlists_endpoint', 'wwn_account_waitlist_content');
function wwn_account_waitlist_content() {
    if (!is_user_logged_in()) {
        echo '<p>' . esc_html__('You must be logged in to view your waitlist.', 'woocommerce') . '</p>';
        return;
    }

    $user_id = get_current_user_id();
    global $wpdb;
    $table = $wpdb->prefix . 'wwn_waitlist';

    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table WHERE user_id = %d ORDER BY date DESC", $user_id
    ));

    if (!$results) {
        echo '<p>' . esc_html__('You have no waitlisted products.', 'woocommerce') . '</p>';
        return;
    }

    echo '<table class="woocommerce-table woocommerce-waitlist-table">';
    echo '<thead><tr>';
    echo '<th>' . esc_html__('Product', 'woocommerce') . '</th>';
    echo '<th>' . esc_html__('Date', 'woocommerce') . '</th>';
    echo '<th>' . esc_html__('Status', 'woocommerce') . '</th>';
    echo '<th>' . esc_html__('Action', 'woocommerce') . '</th>';
    echo '</tr></thead>';
    echo '<tbody>';
    foreach ($results as $row) {
        $product = wc_get_product($row->product_id);
        if ($product) {
            echo '<tr>';
            echo '<td><a href="' . esc_url(get_permalink($product->get_id())) . '">' . esc_html($product->get_name()) . '</a></td>';
            echo '<td>' . esc_html($row->date) . '</td>';
            echo '<td>' . esc_html($row->state) . '</td>';
            echo '<td><a href="#" class="wwn-remove-waitlist button" data-id="' . esc_attr($row->id) . '">' . esc_html__('Remove', 'woocommerce') . '</a></td>';
            echo '</tr>';
        }
    }
    echo '</tbody></table>';
}

add_action('wp_ajax_wwn_remove_waitlist', 'wwn_remove_waitlist');
function wwn_remove_waitlist() {
    if (!is_user_logged_in() || empty($_POST['id'])) {
        wp_send_json_error();
    }

    global $wpdb;
    $table = $wpdb->prefix . 'wwn_waitlist';
    $user_id = get_current_user_id();
    $id = intval($_POST['id']);

    $deleted = $wpdb->delete($table, ['id' => $id, 'user_id' => $user_id]);

    if ($deleted) {
        wp_send_json_success();
    } else {
        wp_send_json_error();
    }
}

add_action('wp_enqueue_scripts', 'wwn_enqueue_assets');
function wwn_enqueue_assets() {
    if (is_account_page()) {
        wp_enqueue_style(
            'wwn-waitlist-style',
            plugins_url('assets/css/waitlist.css', __FILE__),
            [],
            '1.0'
        );

        wp_enqueue_script(
            'wwn-waitlist-script',
            plugins_url('assets/js/waitlist.js', __FILE__),
            ['jquery'],
            '1.0',
            true
        );

        wp_localize_script('wwn-waitlist-script', 'wwn_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
        ]);
    }
}
