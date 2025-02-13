<?php
if (!defined('ABSPATH')) {
    exit;
}

// Define available plans (Replace with actual Stripe Price IDs)
$plans = [
    'basic' => ['id' => 'price_1Qru9gP2TAKWKutbIiOJA09w', 'name' => 'Basic Plan', 'price' => '$10/month', 'trial_days' => 7],
    'premium' => ['id' => 'price_1Qru9gP2TAKWKutbIiOJA09w1', 'name' => 'Premium Plan', 'price' => '$20/month', 'trial_days' => 14],
];

// Render Checkout Form
function stripe_checkout_page() {
    global $plans;
    
    if (!is_user_logged_in()) {
        return '<p>You must be logged in to subscribe.</p>';
    }

    // Get selected plan from URL (if provided)
    $selected_plan = isset($_GET['plan']) ? sanitize_text_field($_GET['plan']) : '';

    ob_start();
    ?>
    <div class="stripe-checkout">
        <h2>Select a Subscription Plan</h2>
        <form id="stripe-checkout-form">
            <?php foreach ($plans as $key => $plan): ?>
                <label>
                    <input type="radio" name="plan" value="<?= esc_attr($plan['id']); ?>" 
                        <?= ($selected_plan === $plan['id']) ? 'checked' : ''; ?> required>
                    <?= esc_html($plan['name']) . " - " . esc_html($plan['price']); ?>
                </label><br>
            <?php endforeach; ?>
            <button type="submit">Subscribe Now</button>
        </form>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#stripe-checkout-form').submit(function(e) {
            e.preventDefault();
            
            let plan = $('input[name="plan"]:checked').val();
            if (!plan) {
                alert('Please select a plan.');
                return;
            }

            $.post("<?= admin_url('admin-ajax.php'); ?>", {
                action: 'create_checkout',
                plan: plan
            }, function(response) {
                if (response.success) {
                    window.location.href = response.data.checkout_url;
                } else {
                    alert('Error: ' + response.data);
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX Error:", textStatus, errorThrown);
                alert("Failed to send request. Check console for details.");
            });
        });
    });
    </script>

    <?php
    return ob_get_clean();
}
add_shortcode('stripe_checkout', 'stripe_checkout_page');
?>