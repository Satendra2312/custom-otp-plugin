<?php
if (!defined('ABSPATH')) {
    exit;
}

use Twilio\Rest\Client;

class Custom_OTP_Delivery {
    private $delivery_method;

    public function __construct() {
        $this->delivery_method = get_option('otp_login_delivery', 'email');
    }

    // Send OTP
    public function send_otp($user, $otp) {
        if ($this->delivery_method === 'sms' && get_user_meta($user->ID, 'phone_number', true)) {
            return $this->send_otp_sms($user, $otp);
        } else {
            return $this->send_otp_email($user, $otp);
        }
    }

    // Send OTP via email
    private function send_otp_email($user, $otp) {
        $to = $user->user_email;
        $subject = __('Your OTP for Login', 'custom-otp-login');
        $message = sprintf(
            __('Your one-time password (OTP) is: <strong>%s</strong>. It is valid for %d minutes.', 'custom-otp-login'),
            $otp,
            get_option('otp_login_expiry', 300) / 60
        );
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        return wp_mail($to, $subject, $message, $headers);
    }

    // Send OTP via SMS
    private function send_otp_sms($user, $otp) {
        $phone = get_user_meta($user->ID, 'phone_number', true);
        if (empty($phone)) {
            error_log('Custom OTP Login: No phone number for user ' . $user->ID);
            return false;
        }

        $sid = get_option('otp_login_twilio_sid', '');
        $token = get_option('otp_login_twilio_token', '');
        $twilio_number = get_option('otp_login_twilio_number', '');

        if (empty($sid) || empty($token) || empty($twilio_number)) {
            error_log('Custom OTP Login: Twilio credentials missing');
            return false;
        }

        try {
            $client = new Client($sid, $token);
            $client->messages->create(
                $phone,
                [
                    'from' => $twilio_number,
                    'body' => sprintf(__('Your OTP for login is: %s. Valid for %d minutes.', 'custom-otp-login'), $otp, get_option('otp_login_expiry', 300) / 60)
                ]
            );
            return true;
        } catch (Exception $e) {
            error_log('Custom OTP Login: Twilio error - ' . $e->getMessage());
            return false;
        }
    }
}
?>