<?php
if (!defined('ABSPATH')) {
    exit;
}

class Custom_OTP_Settings {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    // Add settings page
    public function add_settings_page() {
        add_options_page(
            __('OTP Login Settings', 'custom-otp-login'),
            __('OTP Login', 'custom-otp-login'),
            'manage_options',
            'otp-login-settings',
            [$this, 'render_settings_page']
        );
    }

    // Register settings
    public function register_settings() {
        register_setting('otp_login_settings_group', 'otp_login_length', ['sanitize_callback' => 'intval']);
        register_setting('otp_login_settings_group', 'otp_login_expiry', ['sanitize_callback' => 'intval']);
        register_setting('otp_login_settings_group', 'otp_login_delivery', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('otp_login_settings_group', 'otp_login_twilio_sid', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('otp_login_settings_group', 'otp_login_twilio_token', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('otp_login_settings_group', 'otp_login_twilio_number', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('otp_login_settings_group', 'otp_login_max_attempts', ['sanitize_callback' => 'intval']);
    }

    // Render settings page
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('OTP Login Settings', 'custom-otp-login'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('otp_login_settings_group'); ?>
                <?php do_settings_sections('otp_login_settings_group'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="otp_login_length"><?php _e('OTP Length', 'custom-otp-login'); ?></label></th>
                        <td>
                            <input type="number" name="otp_login_length" id="otp_login_length" value="<?php echo esc_attr(get_option('otp_login_length', 6)); ?>" min="4" max="10" />
                            <p class="description"><?php _e('Number of digits in OTP (4-10).', 'custom-otp-login'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="otp_login_expiry"><?php _e('OTP Expiry (seconds)', 'custom-otp-login'); ?></label></th>
                        <td>
                            <input type="number" name="otp_login_expiry" id="otp_login_expiry" value="<?php echo esc_attr(get_option('otp_login_expiry', 300)); ?>" min="60" max="3600" />
                            <p class="description"><?php _e('Time in seconds before OTP expires (60-3600).', 'custom-otp-login'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="otp_login_delivery"><?php _e('Delivery Method', 'custom-otp-login'); ?></label></th>
                        <td>
                            <select name="otp_login_delivery" id="otp_login_delivery">
                                <option value="email" <?php selected(get_option('otp_login_delivery', 'email'), 'email'); ?>><?php _e('Email', 'custom-otp-login'); ?></option>
                                <option value="sms" <?php selected(get_option('otp_login_delivery'), 'sms'); ?>><?php _e('SMS (Twilio)', 'custom-otp-login'); ?></option>
                            </select>
                            <p class="description"><?php _e('Choose how to send OTPs.', 'custom-otp-login'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="otp_login_twilio_sid"><?php _e('Twilio SID', 'custom-otp-login'); ?></label></th>
                        <td>
                            <input type="text" name="otp_login_twilio_sid" id="otp_login_twilio_sid" value="<?php echo esc_attr(get_option('otp_login_twilio_sid', '')); ?>" class="regular-text" />
                            <p class="description"><?php _e('Your Twilio Account SID.', 'custom-otp-login'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="otp_login_twilio_token"><?php _e('Twilio Auth Token', 'custom-otp-login'); ?></label></th>
                        <td>
                            <input type="text" name="otp_login_twilio_token" id="otp_login_twilio_token" value="<?php echo esc_attr(get_option('otp_login_twilio_token', '')); ?>" class="regular-text" />
                            <p class="description"><?php _e('Your Twilio Auth Token.', 'custom-otp-login'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="otp_login_twilio_number"><?php _e('Twilio Phone Number', 'custom-otp-login'); ?></label></th>
                        <td>
                            <input type="text" name="otp_login_twilio_number" id="otp_login_twilio_number" value="<?php echo esc_attr(get_option('otp_login_twilio_number', '')); ?>" class="regular-text" />
                            <p class="description"><?php _e('Your Twilio phone number (e.g., +1234567890).', 'custom-otp-login'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="otp_login_max_attempts"><?php _e('Max OTP Attempts', 'custom-otp-login'); ?></label></th>
                        <td>
                            <input type="number" name="otp_login_max_attempts" id="otp_login_max_attempts" value="<?php echo esc_attr(get_option('otp_login_max_attempts', 3)); ?>" min="1" max="10" />
                            <p class="description"><?php _e('Maximum incorrect OTP attempts before temporary lockout.', 'custom-otp-login'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
?>