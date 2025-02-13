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

// Define API Keys (Replace with your actual keys)
define('STRIPE_SECRET_KEY', 'sk_test_51QrjhxP2TAKWKutbqjCgnNlxt7Ad9bHvxYxUvzJMXNIfvi6aXEqGE4hq5Rt4AJYSBINLfpZkDoeIoi7GeRYC8C2700NGzc6ZYu');
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_51QrjhxP2TAKWKutbQDwB9VouVSKquOUGnZDZpe5AjMxHwG7v4xOjuwbXc1m5GD8YlBuCzSn3b2yXk94raZqbNWI400IUkCW0HS');

// Include necessary files
require_once plugin_dir_path(__FILE__) . 'includes/api-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/webhook-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/user-dashboard.php';
require_once plugin_dir_path(__FILE__) . 'includes/checkout-handler.php';

// Add Admin Menu
function stripe_subscription_menu() {
    add_menu_page('Stripe Subscriptions', 'Stripe Subscriptions', 'manage_options', 'stripe-subscriptions', 'stripe_subscription_admin_page');
}
add_action('admin_menu', 'stripe_subscription_menu');

// Load Admin Panel UI
function stripe_subscription_admin_page() {
    require_once plugin_dir_path(__FILE__) . 'admin/admin-page.php';
}

// Load JS for Admin Panel
function stripe_enqueue_scripts($hook) {
    if ($hook !== 'toplevel_page_stripe-subscriptions') return;
    wp_enqueue_script('stripe-subscription-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), null, true);
}
add_action('admin_enqueue_scripts', 'stripe_enqueue_scripts');

// Subscription active message
function stripe_success_page() {
    return "<h2>Thank you! Your subscription is active.</h2>";
}
add_shortcode('stripe_success', 'stripe_success_page');

// Subscription cancel message
function stripe_cancel_page() {
    return "<h2>Your subscription was not completed.</h2>";
}
add_shortcode('stripe_cancel', 'stripe_cancel_page');

function stripe_create_subscriptions_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'stripe_subscriptions';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        subscription_id VARCHAR(255) NOT NULL,
        plan_id VARCHAR(255) NOT NULL,
        status VARCHAR(100) NOT NULL,
        next_billing_date DATETIME DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY subscription_id (subscription_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

// Ensure this hook is in the main plugin file, NOT an included file.
//register_activation_hook(__FILE__, 'stripe_create_subscriptions_table');
add_action('init', 'stripe_create_subscriptions_table');



?>
