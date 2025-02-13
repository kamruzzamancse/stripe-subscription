<?php
if (!defined('ABSPATH')) {
    exit;
}

// Fetch Subscriptions from WP Database
global $wpdb;
$table = $wpdb->prefix . 'stripe_subscriptions';
$subscriptions = $wpdb->get_results("SELECT * FROM $table");

?>
<div class="wrap">
    <h1>Stripe Subscriptions</h1>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>User ID</th>
                <th>Subscription ID</th>
                <th>Plan</th>
                <th>Status</th>
                <th>Next Billing Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($subscriptions as $sub): ?>
                <tr>
                    <td><?= esc_html($sub->user_id); ?></td>
                    <td><?= esc_html($sub->subscription_id); ?></td>
                    <td><?= esc_html($sub->plan_id); ?></td>
                    <td><?= esc_html($sub->status); ?></td>
                    <td><?= esc_html($sub->next_billing_date); ?></td>
                    <td><button class="cancel-subscription" data-id="<?= esc_attr($sub->subscription_id); ?>">Cancel</button></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    $('.cancel-subscription').on('click', function() {
        let subscriptionId = $(this).data('id');

        $.post("<?= admin_url('admin-ajax.php'); ?>", {
            action: 'cancel_subscription',
            subscription_id: subscriptionId
        }, function(response) {
            if (response.success) {
                alert('Subscription canceled successfully.');
                location.reload(); // Refresh the page
            } else {
                alert('Failed to cancel subscription: ' + response.data);
            }
        });
    });
});
</script>
