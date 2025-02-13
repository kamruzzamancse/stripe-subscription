<?php
if (!defined('ABSPATH')) {
    exit;
}

// Process Checkout Request
function stripe_create_subscription() {
    $price_id = isset($_POST['plan']) ? sanitize_text_field($_POST['plan']) : '';
    $trial_days = 7;

    $response = wp_remote_post('https://api.stripe.com/v1/checkout/sessions', [
        'headers' => [
            'Authorization' => 'Bearer ' . STRIPE_SECRET_KEY,
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ],
        'body' => [
            'payment_method_types[]' => 'card',
            'mode'                  => 'subscription',
            'line_items[0][price]'  => $price_id,
            'line_items[0][quantity]' => 1,
            'subscription_data[trial_period_days]' => $trial_days,
            'success_url'           => home_url('/subscription-success'),
            'cancel_url'            => home_url('/subscription-cancel'),
        ],
    ]);

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (!empty($body['url'])) {
        wp_redirect($body['url']);
        exit;
    }
}
add_action('admin_post_create_subscription', 'stripe_create_subscription');
add_action('admin_post_nopriv_create_subscription', 'stripe_create_subscription');

//cencel subscription from stripe
function stripe_cancel_subscription() {
    if (!isset($_POST['subscription_id'])) {
        wp_send_json_error('Invalid request.');
    }

    $sub_id = sanitize_text_field($_POST['subscription_id']);

    $response = wp_remote_post("https://api.stripe.com/v1/subscriptions/$sub_id", [
        'method'  => 'DELETE',
        'headers' => [
            'Authorization' => 'Bearer ' . STRIPE_SECRET_KEY,
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ],
    ]);

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($body['status']) && $body['status'] === 'canceled') {
        global $wpdb;
        $table = $wpdb->prefix . 'stripe_subscriptions';
        $wpdb->delete($table, ['subscription_id' => $sub_id]);
        
        wp_send_json_success('Subscription canceled.');
    } else {
        wp_send_json_error('Failed to cancel subscription.');
    }
}
add_action('wp_ajax_cancel_subscription', 'stripe_cancel_subscription');
add_action('wp_ajax_nopriv_cancel_subscription', 'stripe_cancel_subscription');

//fetch invoices from stripe
function stripe_fetch_invoices() {
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in.');
    }

    $user_id = get_current_user_id();
    global $wpdb;
    $table = $wpdb->prefix . 'stripe_subscriptions';
    $subscription = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE user_id = %d", $user_id));

    if (!$subscription) {
        wp_send_json_error('No subscription found.');
    }

    $response = wp_remote_get("https://api.stripe.com/v1/invoices?subscription={$subscription->subscription_id}", [
        'headers' => [
            'Authorization' => 'Bearer ' . STRIPE_SECRET_KEY,
        ],
    ]);

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!isset($body['data'])) {
        wp_send_json_error('Failed to fetch invoices.');
    }

    $invoices = [];
    foreach ($body['data'] as $invoice) {
        $invoices[] = [
            'date'  => date('Y-m-d', $invoice['created']),
            'total' => number_format($invoice['amount_due'] / 100, 2) . ' ' . strtoupper($invoice['currency']),
            'pdf'   => $invoice['invoice_pdf'],
        ];
    }

    wp_send_json_success($invoices);
}
add_action('wp_ajax_fetch_invoices', 'stripe_fetch_invoices');
add_action('wp_ajax_nopriv_fetch_invoices', 'stripe_fetch_invoices');

//create checkout session
function stripe_create_checkout_session() {
    if (!is_user_logged_in() || !isset($_POST['plan'])) {
        wp_send_json_error('Invalid request.');
    }

    $user_id = get_current_user_id();
    $plan_id = sanitize_text_field($_POST['plan']);

    // Create Stripe Checkout Session
    $response = wp_remote_post('https://api.stripe.com/v1/checkout/sessions', [
        'headers' => [
            'Authorization' => 'Bearer ' . STRIPE_SECRET_KEY,
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ],
        'body' => [
            'payment_method_types[]' => 'card',
            'mode'                  => 'subscription',
            'line_items[0][price]'  => $plan_id,
            'line_items[0][quantity]' => 1,
            'subscription_data[trial_period_days]' => 7,  // Customize trial days if needed
            'customer_email'         => wp_get_current_user()->user_email,
            'success_url'            => home_url('/subscription-success'),
            'cancel_url'             => home_url('/subscription-cancel'),
        ],
    ]);

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!empty($body['url'])) {
        wp_send_json_success(['checkout_url' => $body['url']]);
    } else {
        wp_send_json_error('Failed to create checkout session.');
    }
}
add_action('wp_ajax_create_checkout', 'stripe_create_checkout_session');
add_action('wp_ajax_nopriv_create_checkout', 'stripe_create_checkout_session');


?>
