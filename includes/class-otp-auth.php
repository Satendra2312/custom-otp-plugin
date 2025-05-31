<?php
if (!defined('ABSPATH')) {
    exit;
}

class Custom_OTP_Auth {
    private $otp_length;
    private $otp_expiry;
    private $max_attempts;

    public function __construct() {
        $this->otp_length = get_option('otp_login_length', 6);
        $this->otp_expiry = get_option('otp_login_expiry', 300); // 5 minutes
        $this->max_attempts = get_option('otp_login_max_attempts', 3);

        // Hooks
        add_filter('authenticate', [$this, 'authenticate_user'], 30, 3);
        add_action('init', [$this, 'register_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'render_otp_verification_page']);
        add_action('wp_login_failed', [$this, 'clear_otp_on_failed_login']);
        add_action('wp_login', [$this, 'cleanup_otp_data'], 10, 2);
    }

    // Intercept login process
    public function authenticate_user($user, $username, $password) {
        if (is_wp_error($user)) {
            return $user;
        }

        // Check rate limit
        $attempts = get_transient('otp_attempts_' . $user->ID);
        if ($attempts && $attempts >= $this->max_attempts) {
            return new WP_Error('otp_limit_exceeded', __('Too many OTP attempts. Please try again later.', 'custom-otp-login'));
        }

        // Generate OTP
        $otp = $this->generate_otp();
        update_user_meta($user->ID, 'otp_code', wp_hash_password($otp));
        update_user_meta($user->ID, 'otp_expiry', time() + $this->otp_expiry);
        update_user_meta($user->ID, 'otp_attempts', 0);

        // Send OTP
        $delivery = new Custom_OTP_Delivery();
        $sent = $delivery->send_otp($user, $otp);
        if ($sent) {
            set_transient('pending_otp_user_' . $user->ID, $user->ID, $this->otp_expiry);
            wp_redirect(home_url('/otp-verification/?user_id=' . $user->ID . '&nonce=' . wp_create_nonce('otp_verification')));
            exit;
        } else {
            error_log('Custom OTP Login: Failed to send OTP for user ' . $user->ID);
            return new WP_Error('otp_send_failed', __('Failed to send OTP. Please try again.', 'custom-otp-login'));
        }
    }

    // Generate OTP
    private function generate_otp() {
        $characters = '0123456789';
        $otp = '';
        for ($i = 0; $i < $this->otp_length; $i++) {
            $otp .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $otp;
    }

    // Register rewrite rules
    public function register_rewrite_rules() {
        add_rewrite_rule(
            'otp-verification/?$',
            'index.php?otp_verification=1',
            'top'
        );
    }

    // Add query vars
    public function add_query_vars($vars) {
        $vars[] = 'otp_verification';
        return $vars;
    }

    // Render OTP verification page
    public function render_otp_verification_page() {
        if (!get_query_var('otp_verification')) {
            return;
        }

        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        $nonce = isset($_GET['nonce']) ? sanitize_text_field($_GET['nonce']) : '';

        // Verify nonce and user
        if (!wp_verify_nonce($nonce, 'otp_verification') || !$user_id || !get_transient('pending_otp_user_' . $user_id)) {
            wp_die(__('Invalid or expired OTP session.', 'custom-otp-login'), __('Error', 'custom-otp-login'), ['response' => 403]);
        }

        // Handle OTP submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp_code']) && isset($_POST['otp_nonce']) && wp_verify_nonce($_POST['otp_nonce'], 'otp_submit')) {
            $submitted_otp = sanitize_text_field($_POST['otp_code']);
            $stored_otp = get_user_meta($user_id, 'otp_code', true);
            $otp_expiry = get_user_meta($user_id, 'otp_expiry', true);
            $attempts = (int) get_user_meta($user_id, 'otp_attempts', true);

            if (time() > $otp_expiry) {
                $this->cleanup_otp_data(null, $user_id);
                wp_die(__('OTP has expired. Please try logging in again.', 'custom-otp-login'), __('Expired', 'custom-otp-login'));
            }

            if ($attempts >= $this->max_attempts) {
                $this->cleanup_otp_data(null, $user_id);
                set_transient('otp_attempts_' . $user_id, $this->max_attempts, 3600);
                wp_die(__('Too many incorrect OTP attempts. Please try again later.', 'custom-otp-login'), __('Limit Exceeded', 'custom-otp-login'));
            }

            if (wp_check_password($submitted_otp, $stored_otp)) {
                wp_set_auth_cookie($user_id);
                $this->cleanup_otp_data(null, $user_id);
                wp_redirect(admin_url());
                exit;
            } else {
                update_user_meta($user_id, 'otp_attempts', $attempts + 1);
                wp_die(__('Invalid OTP. Please try again.', 'custom-otp-login'), __('Invalid OTP', 'custom-otp-login'));
            }
        }

        // Load OTP verification template
        wp_enqueue_style('otp-login-style', plugins_url('assets/css/otp-style.css', dirname(__FILE__)));
        include plugin_dir_path(dirname(__FILE__)) . 'templates/otp-verification.php';
        exit;
    }

    // Clean up OTP data
    public function cleanup_otp_data($user_login, $user_id = null) {
        $user_id = $user_id ?: get_current_user_id();
        delete_user_meta($user_id, 'otp_code');
        delete_user_meta($user_id, 'otp_expiry');
        delete_user_meta($user_id, 'otp_attempts');
        delete_transient('pending_otp_user_' . $user_id);
        delete_transient('otp_attempts_' . $user_id);
    }

    // Clear OTP data on failed login
    public function clear_otp_on_failed_login($username) {
        $user = get_user_by('login', $username) ?: get_user_by('email', $username);
        if ($user) {
            $this->cleanup_otp_data(null, $user->ID);
        }
    }
}
?>