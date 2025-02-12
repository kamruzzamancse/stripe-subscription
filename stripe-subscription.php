<?php
/**
 * Plugin Name: Stripe Subscription
 * Plugin URI: https://sparktech.agency/
 * Description: A WordPress plugin to handle monthly subscription payments using Stripe.
 * Version: 1.0.0
 * Author: BSTA
 * Author URI: https://sparktech.agency/
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define Stripe API keys (Set these in wp-config.php for security)
define('STRIPE_SECRET_KEY', 'sk_test_51QrjhxP2TAKWKutbqjCgnNlxt7Ad9bHvxYxUvzJMXNIfvi6aXEqGE4hq5Rt4AJYSBINLfpZkDoeIoi7GeRYC8C2700NGzc6ZYu'); 
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_51QrjhxP2TAKWKutbQDwB9VouVSKquOUGnZDZpe5AjMxHwG7v4xOjuwbXc1m5GD8YlBuCzSn3b2yXk94raZqbNWI400IUkCW0HS');
define('STRIPE_WEBHOOK_SECRET', 'your-stripe-webhook-secret');

// Enqueue Scripts and Styles
function stripe_subscription_enqueue_scripts() {
    wp_enqueue_style('stripe-subscription-style', plugins_url('css/style.css', __FILE__));
    wp_enqueue_script('stripe-subscription-script', plugins_url('js/script.js', __FILE__), array('jquery'), null, true);

    wp_localize_script('stripe-subscription-script', 'stripe_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'stripe_pk' => STRIPE_PUBLISHABLE_KEY,
        'nonce' => wp_create_nonce('stripe_subscription')
    ));
}
add_action('wp_enqueue_scripts', 'stripe_subscription_enqueue_scripts');

// Shortcode to Display Subscription Form
function stripe_subscription_form() {
    ob_start();
    ?>
    <form id="stripe-subscription-form" action="" method="POST">
        <?php wp_nonce_field('stripe_subscription', 'stripe_subscription_nonce'); ?>
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="card-element">Credit or Debit Card</label>
            <div id="card-element"></div>
            <div id="card-errors" role="alert" style="color: red;"></div>
        </div>
        <button type="submit">Subscribe</button>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('stripe_subscription_form', 'stripe_subscription_form');

// Handle AJAX Subscription Request
function stripe_subscription_handle_form() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !check_ajax_referer('stripe_subscription', 'nonce', false)) {
        wp_send_json_error('Invalid request.');
        return;
    }

    $token = sanitize_text_field($_POST['stripeToken']);
    $email = sanitize_email($_POST['email']);
    $stripe_secret_key = STRIPE_SECRET_KEY;

    // Create Stripe Customer
    $response = wp_remote_post('https://api.stripe.com/v1/customers', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $stripe_secret_key,
            'Content-Type' => 'application/x-www-form-urlencoded',
        ),
        'body' => array(
            'email' => $email,
            'source' => $token,
        ),
    ));

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
        return;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (!isset($body['id'])) {
        wp_send_json_error('Failed to create customer.');
        return;
    }

    $customer_id = $body['id'];

    // Create Subscription
    $subscription_response = wp_remote_post('https://api.stripe.com/v1/subscriptions', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $stripe_secret_key,
            'Content-Type' => 'application/x-www-form-urlencoded',
        ),
        'body' => array(
            'customer' => $customer_id,
            'items[0][price]' => 'prctbl_1Qrkd8P2TAKWKutbiDGbwUd5', // Replace with your Stripe Price ID
        ),
    ));

    if (is_wp_error($subscription_response)) {
        wp_send_json_error($subscription_response->get_error_message());
    } else {
        wp_send_json_success('Subscription created successfully!');
    }
}
add_action('wp_ajax_stripe_subscription_handle_form', 'stripe_subscription_handle_form');
add_action('wp_ajax_nopriv_stripe_subscription_handle_form', 'stripe_subscription_handle_form');

// Stripe Webhook Handler
function stripe_subscription_webhook() {
    $payload = file_get_contents('php://input');
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
    $endpoint_secret = STRIPE_WEBHOOK_SECRET;

    try {
        $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
    } catch (\Exception $e) {
        http_response_code(400);
        exit();
    }

    switch ($event->type) {
        case 'invoice.payment_succeeded':
            // Handle successful payment
            break;
        case 'invoice.payment_failed':
            // Handle failed payment
            break;
        case 'customer.subscription.deleted':
            // Handle subscription cancellation
            break;
    }

    http_response_code(200);
    exit();
}
add_action('wp_ajax_stripe_subscription_webhook', 'stripe_subscription_webhook');
add_action('wp_ajax_nopriv_stripe_subscription_webhook', 'stripe_subscription_webhook');

// Stripe Pricing Table Shortcode
function stripe_pricing_table_shortcode() {
    return '<script async src="https://js.stripe.com/v3/pricing-table.js"></script>
    <stripe-pricing-table pricing-table-id="prctbl_1Qrkd8P2TAKWKutbiDGbwUd5"
    publishable-key="' . STRIPE_PUBLISHABLE_KEY . '"></stripe-pricing-table>';
}
add_shortcode('stripe_pricing_table', 'stripe_pricing_table_shortcode');

?>
