<?php
if (!defined('ABSPATH')) {
    exit;
}

// Handle Stripe Webhook Events
function stripe_handle_webhook() {
    global $wpdb;

    // Read raw input
    $payload = file_get_contents("php://input");
    $event = json_decode($payload, true);

    if (!isset($event['type'])) {
        http_response_code(400);
        exit;
    }

    // Log event type for debugging
    error_log("Webhook received: " . $event['type']);

    // Handle successful checkout
    if ($event['type'] === 'checkout.session.completed') {
        $session = $event['data']['object'];

        // Get user by email
        $user = get_user_by('email', $session['customer_email']);
        if (!$user) {
            error_log("User not found for email: " . $session['customer_email']);
            http_response_code(400);
            exit;
        }

        $user_id = $user->ID;
        $subscription_id = $session['subscription']; // Stripe Subscription ID
        $plan_id = $session['metadata']['plan_id'];  // Plan ID stored in metadata
        $status = 'active';
        $next_billing_date = date('Y-m-d H:i:s', strtotime("+1 month")); // Adjust as needed

        // Store in the database
        $table_name = $wpdb->prefix . 'stripe_subscriptions';
        $wpdb->insert($table_name, [
            'user_id' => $user_id,
            'subscription_id' => $subscription_id,
            'plan_id' => $plan_id,
            'status' => $status,
            'next_billing_date' => $next_billing_date,
            'created_at' => current_time('mysql')
        ]);

        // Debugging log
        if ($wpdb->last_error) {
            error_log("Database Insert Error: " . $wpdb->last_error);
        } else {
            error_log("Subscription stored successfully for User ID: $user_id");
        }

        // Update user meta (optional)
        update_user_meta($user_id, 'stripe_subscription_id', $subscription_id);
        update_user_meta($user_id, 'stripe_plan_id', $plan_id);
        update_user_meta($user_id, 'subscription_status', $status);
    }

    http_response_code(200);
    exit;
}

// Add Webhook Endpoint
add_action('rest_api_init', function() {
    register_rest_route('stripe/v1', '/webhook', [
        'methods'  => 'POST',
        'callback' => 'stripe_handle_webhook',
    ]);
});
