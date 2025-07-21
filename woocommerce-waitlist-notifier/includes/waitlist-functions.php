<?php
// Show the waitlist button on out-of-stock products
add_action('woocommerce_single_product_summary', 'wwn_show_waitlist_button', 35);
function wwn_show_waitlist_button() {
    global $product;
    if ( !$product->is_in_stock() ) {
        include WWN_PATH . 'templates/waitlist-button.php';
    }
}

// Handle AJAX request to join waitlist
add_action('wp_ajax_wwn_join_waitlist', 'wwn_join_waitlist');
add_action('wp_ajax_nopriv_wwn_join_waitlist', 'wwn_join_waitlist');

function wwn_join_waitlist() {
    // Validate input
    if ( ! isset($_POST['product_id']) || empty($_POST['product_id']) ) {
        wp_send_json_error(['message' => 'Product ID is missing']);
    }

    $product_id = intval($_POST['product_id']);
    $user_id = get_current_user_id();

    if ( is_user_logged_in() ) {
        $email = wp_get_current_user()->user_email;
    } else {
        if ( ! isset($_POST['email']) || ! is_email($_POST['email']) ) {
            wp_send_json_error(['message' => 'Valid email is required']);
        }
        $email = sanitize_email($_POST['email']);
    }

    $date = current_time('mysql');

    global $wpdb;
    $table = $wpdb->prefix . 'wwn_waitlist';

    // Save to waitlist table
    $wpdb->insert($table, [
        'user_id'    => $user_id,
        'email'      => $email,
        'product_id' => $product_id,
        'date'       => $date,
        'state'      => 'waiting'
    ]);

    // Get recipient email from settings
    $to = get_option('wwn_notify_email');
    if ( empty($to) ) {
        $to = get_option('admin_email'); // fallback
    }

    // Send notification email
    if ( $to ) {
        $product = wc_get_product($product_id);
        $subject = 'New Enquiry';
        $body = "A new customer has joined the enquiry:\n\n";
        $body .= "Email: $email\n";
        $body .= "Product: " . $product->get_name() . "\n";
        $body .= "Date: $date\n";

        wp_mail($to, $subject, $body);
    }

    wp_send_json_success([
        'message' => 'You have been added to the enquiry.',
        'redirect_url' => wc_get_account_endpoint_url('waitlists'),
    ]);
    
}
