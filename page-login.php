<?php
/**
 * Template Name: Login Page
 *
 * Custom login page for IBEX Racing.
 *
 * @package IbexRacingChild
 */

if (!defined('ABSPATH')) {
  exit;
}

// Redirect if already logged in
if (is_user_logged_in()) {
  $redirect_to = isset($_GET['redirect_to']) ? esc_url_raw($_GET['redirect_to']) : home_url('/');
  wp_safe_redirect($redirect_to);
  exit;
}

// Handle login form submission
$login_error = '';
$login_success = '';

if (isset($_POST['ibex_login_submit'])) {
  // Verify nonce
  if (!isset($_POST['ibex_login_nonce']) || !wp_verify_nonce($_POST['ibex_login_nonce'], 'ibex_login_action')) {
    $login_error = __('Security check failed. Please try again.', 'ibex-racing-child');
  } else {
    $username = isset($_POST['log']) ? sanitize_user($_POST['log']) : '';
    $password = isset($_POST['pwd']) ? $_POST['pwd'] : '';
    $remember = isset($_POST['rememberme']) ? true : false;
    
    if (empty($username) || empty($password)) {
      $login_error = __('Please enter both username and password.', 'ibex-racing-child');
    } else {
      $credentials = [
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => $remember,
      ];
      
      $user = wp_signon($credentials, is_ssl());
      
      if (is_wp_error($user)) {
        $login_error = $user->get_error_message();
      } else {
        $redirect_to = isset($_POST['redirect_to']) ? esc_url_raw($_POST['redirect_to']) : home_url('/');
        wp_safe_redirect($redirect_to);
        exit;
      }
    }
  }
}

get_header();

$redirect_to = isset($_GET['redirect_to']) ? esc_url_raw($_GET['redirect_to']) : home_url('/');
?>

<main id="primary" class="site-main ibex-login-page">
  <div class="ibex-login-container">
    <div class="ibex-login-card">
      <div class="ibex-login-header">
        <h1 class="ibex-login-title"><?php esc_html_e('Welcome Back', 'ibex-racing-child'); ?></h1>
        <p class="ibex-login-subtitle"><?php esc_html_e('Sign in to your account', 'ibex-racing-child'); ?></p>
      </div>

      <?php if ($login_error) : ?>
        <div class="ibex-login-message ibex-login-message--error">
          <?php echo esc_html($login_error); ?>
        </div>
      <?php endif; ?>

      <?php if ($login_success) : ?>
        <div class="ibex-login-message ibex-login-message--success">
          <?php echo esc_html($login_success); ?>
        </div>
      <?php endif; ?>

      <form class="ibex-login-form" method="post" action="">
        <?php wp_nonce_field('ibex_login_action', 'ibex_login_nonce'); ?>
        <input type="hidden" name="redirect_to" value="<?php echo esc_url($redirect_to); ?>">

        <div class="ibex-login-field">
          <label for="user_login" class="ibex-login-label">
            <?php esc_html_e('Username or Email', 'ibex-racing-child'); ?>
          </label>
          <input 
            type="text" 
            name="log" 
            id="user_login" 
            class="ibex-login-input" 
            value="<?php echo isset($_POST['log']) ? esc_attr($_POST['log']) : ''; ?>"
            required
            autocomplete="username"
          >
        </div>

        <div class="ibex-login-field">
          <label for="user_pass" class="ibex-login-label">
            <?php esc_html_e('Password', 'ibex-racing-child'); ?>
          </label>
          <input 
            type="password" 
            name="pwd" 
            id="user_pass" 
            class="ibex-login-input" 
            required
            autocomplete="current-password"
          >
        </div>

        <div class="ibex-login-remember">
          <label class="ibex-login-checkbox-label">
            <input 
              type="checkbox" 
              name="rememberme" 
              value="forever"
              <?php echo isset($_POST['rememberme']) ? 'checked' : ''; ?>
            >
            <span><?php esc_html_e('Remember Me', 'ibex-racing-child'); ?></span>
          </label>
        </div>

        <button type="submit" name="ibex_login_submit" class="ibex-login-submit">
          <?php esc_html_e('Sign In', 'ibex-racing-child'); ?>
        </button>
      </form>

      <div class="ibex-login-footer">
        <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="ibex-login-link">
          <?php esc_html_e('Forgot your password?', 'ibex-racing-child'); ?>
        </a>
        
        <?php if (get_option('users_can_register')) : ?>
          <span class="ibex-login-separator">•</span>
          <a href="<?php echo esc_url(wp_registration_url()); ?>" class="ibex-login-link">
            <?php esc_html_e('Create an account', 'ibex-racing-child'); ?>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<?php
get_footer();

