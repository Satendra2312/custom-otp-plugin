<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo get_locale(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _e('OTP Verification', 'custom-otp-login'); ?></title>
    <?php wp_head(); ?>
</head>
<body>
    <div class="otp-container">
        <h2><?php _e('Enter Your OTP', 'custom-otp-login'); ?></h2>
        <?php if (isset($_GET['error'])) : ?>
            <p class="error"><?php echo esc_html($_GET['error']); ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="otp_code" placeholder="<?php _e('Enter OTP', 'custom-otp-login'); ?>" required autocomplete="off">
            <input type="hidden" name="otp_nonce" value="<?php echo wp_create_nonce('otp_submit'); ?>">
            <button type="submit"><?php _e('Verify OTP', 'custom-otp-login'); ?></button>
        </form>
        <a href="<?php echo wp_login_url(); ?>" class="resend"><?php _e('Resend OTP', 'custom-otp-login'); ?></a>
    </div>
    <?php wp_footer(); ?>
</body>
</html>
?>