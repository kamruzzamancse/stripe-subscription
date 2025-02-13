<?php

function stripe_user_dashboard() {
    if (!is_user_logged_in()) {
        return '<p>You must be logged in to manage your subscription.</p>';
    }

    $user_id = get_current_user_id();
    global $wpdb;
    $table = $wpdb->prefix . 'stripe_subscriptions';
    $subscription = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE user_id = %d", $user_id));

    if (!$subscription) {
        return '<p>You have no active subscriptions.</p>';
    }

    ob_start();
    ?>
    <div class="stripe-dashboard">
        <h2>Your Subscription</h2>
        <p><strong>Plan ID:</strong> <?= esc_html($subscription->plan_id); ?></p>
        <p><strong>Status:</strong> <?= esc_html($subscription->status); ?></p>
        <p><strong>Next Billing Date:</strong> <?= esc_html($subscription->next_billing_date); ?></p>

        <form id="cancel-subscription-form">
            <input type="hidden" name="subscription_id" value="<?= esc_attr($subscription->subscription_id); ?>">
            <button type="submit">Cancel Subscription</button>
        </form>

        <h3>Invoices</h3>
        <div id="invoice-list"></div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('stripe_user_dashboard', 'stripe_user_dashboard');

?>