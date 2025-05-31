<?php
/*
Plugin Name: Custom OTP Login
Description: Adds secure OTP verification to WordPress dashboard login with email/SMS support.
Version: 1.0.0
Author: Satendra kumar
Email: sk.gautam9673@gmail.com
Website: satendra.inceptionspark.com
License: GPL-2.0+
Text Domain: custom-otp-login
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load Composer autoloader for Twilio (if exists)
if (file_exists(plugin_dir_path(__FILE__) . 'vendor/autoload.php')) {
    require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
}

// Include plugin classes
require_once plugin_dir_path(__FILE__) . 'includes/class-otp-auth.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-otp-delivery.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-otp-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-otp-user.php';

// Initialize classes
function custom_otp_login_init() {
    $otp_auth = new Custom_OTP_Auth();
    $otp_delivery = new Custom_OTP_Delivery();
    $otp_settings = new Custom_OTP_Settings();
    $otp_user = new Custom_OTP_User();
}
add_action('plugins_loaded', 'custom_otp_login_init');

// Flush rewrite rules on activation/deactivation
register_activation_hook(__FILE__, function() {
    flush_rewrite_rules();
});
register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});
?>