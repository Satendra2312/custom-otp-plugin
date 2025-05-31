<?php
if (!defined('ABSPATH')) {
    exit;
}

class Custom_OTP_User {
    public function __construct() {
        add_action('show_user_profile', [$this, 'add_phone_field']);
        add_action('edit_user_profile', [$this, 'add_phone_field']);
        add_action('personal_options_update', [$this, 'save_phone_field']);
        add_action('edit_user_profile_update', [$this, 'save_phone_field']);
    }

    // Add phone number field
    public function add_phone_field($user) {
        ?>
        <h3><?php _e('Phone Number for OTP', 'custom-otp-login'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="phone_number"><?php _e('Phone Number', 'custom-otp-login'); ?></label></th>
                <td>
                    <input type="text" name="phone_number" id="phone_number" value="<?php echo esc_attr(get_user_meta($user->ID, 'phone_number', true)); ?>" class="regular-text" placeholder="+1234567890" />
                    <p class="description"><?php _e('Enter phone number in international format (e.g., +1234567890) for SMS OTP.', 'custom-otp-login'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    // Save phone number
    public function save_phone_field($user_id) {
        if (current_user_can('edit_user', $user_id)) {
            $phone = isset($_POST['phone_number']) ? sanitize_text_field($_POST['phone_number']) : '';
            if (!empty($phone) && !preg_match('/^\+\d{10,15}$/', $phone)) {
                add_action('user_profile_update_errors', function($errors) {
                    $errors->add('phone_invalid', __('Phone number must be in international format (e.g., +1234567890).', 'custom-otp-login'));
                });
                return;
            }
            update_user_meta($user_id, 'phone_number', $phone);
        }
    }
}
?>