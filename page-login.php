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

// Determine current action (login, lostpassword, or reset password)
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'login';
$reset_key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';
$reset_login = isset($_GET['login']) ? sanitize_user($_GET['login']) : '';

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

// Handle lost password form submission
if (isset($_POST['ibex_lostpassword_submit']) && $action === 'lostpassword') {
  check_admin_referer('ibex_lostpassword_action', 'ibex_lostpassword_nonce');
  
  $user_login = isset($_POST['user_login']) ? sanitize_text_field($_POST['user_login']) : '';
  
  if (empty($user_login)) {
    $login_error = __('Please enter a username or email address.', 'ibex-racing-child');
  } else {
    $result = retrieve_password($user_login);
    
    if (is_wp_error($result)) {
      $login_error = $result->get_error_message();
    } else {
      $login_success = __('Check your email for the password reset link.', 'ibex-racing-child');
      $action = 'login'; // Switch back to login view
    }
  }
}

// Handle password reset form submission
if (isset($_POST['ibex_resetpass_submit']) && $action === 'rp') {
  check_admin_referer('ibex_resetpass_action', 'ibex_resetpass_nonce');
  
  $reset_key = isset($_POST['rp_key']) ? sanitize_text_field($_POST['rp_key']) : '';
  $reset_login = isset($_POST['rp_login']) ? sanitize_user($_POST['rp_login']) : '';
  $new_password = isset($_POST['pass1']) ? $_POST['pass1'] : '';
  $new_password_confirm = isset($_POST['pass2']) ? $_POST['pass2'] : '';
  
  if (empty($reset_key) || empty($reset_login)) {
    $login_error = __('Invalid reset link. Please request a new password reset.', 'ibex-racing-child');
    $action = 'lostpassword';
  } elseif (empty($new_password)) {
    $login_error = __('Please enter a new password.', 'ibex-racing-child');
  } elseif ($new_password !== $new_password_confirm) {
    $login_error = __('The passwords do not match.', 'ibex-racing-child');
  } elseif (strlen($new_password) < 8) {
    $login_error = __('Password must be at least 8 characters long.', 'ibex-racing-child');
  } else {
    $user = check_password_reset_key($reset_key, $reset_login);
    
    if (is_wp_error($user)) {
      $login_error = $user->get_error_message();
    } else {
      reset_password($user, $new_password);
      $login_success = __('Your password has been reset. Please log in.', 'ibex-racing-child');
      $action = 'login';
      $reset_key = '';
      $reset_login = '';
    }
  }
}

// Check for password reset success message
if (isset($_GET['password-reset']) && $_GET['password-reset'] === 'success') {
  $login_success = __('Your password has been reset. Please log in.', 'ibex-racing-child');
  $action = 'login';
}

get_header();

$redirect_to = isset($_GET['redirect_to']) ? esc_url_raw($_GET['redirect_to']) : home_url('/');
?>

<main id="primary" class="site-main ibex-login-page">
  <div class="ibex-login-container">
    <div class="ibex-login-card">
      
      <?php
      // Verify reset key if on reset page
      $reset_user = null;
      if ($action === 'rp' && !empty($reset_key) && !empty($reset_login)) {
        $reset_user = check_password_reset_key($reset_key, $reset_login);
        if (is_wp_error($reset_user)) {
          $login_error = $reset_user->get_error_message();
          $action = 'lostpassword';
          $reset_key = '';
          $reset_login = '';
          $reset_user = null;
        }
      }
      ?>

      <div class="ibex-login-header">
        <?php if ($action === 'lostpassword') : ?>
          <h1 class="ibex-login-title"><?php esc_html_e('Reset Password', 'ibex-racing-child'); ?></h1>
          <p class="ibex-login-subtitle"><?php esc_html_e('Enter your username or email to receive a reset link', 'ibex-racing-child'); ?></p>
        <?php elseif ($action === 'rp' && !empty($reset_key) && !empty($reset_login) && $reset_user && !is_wp_error($reset_user)) : ?>
          <h1 class="ibex-login-title"><?php esc_html_e('Set New Password', 'ibex-racing-child'); ?></h1>
          <p class="ibex-login-subtitle"><?php esc_html_e('Enter your new password below', 'ibex-racing-child'); ?></p>
        <?php else : ?>
          <h1 class="ibex-login-title"><?php esc_html_e('Welcome Back', 'ibex-racing-child'); ?></h1>
          <p class="ibex-login-subtitle"><?php esc_html_e('Sign in to your account', 'ibex-racing-child'); ?></p>
        <?php endif; ?>
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

      <?php if ($action === 'lostpassword') : ?>
        <!-- Lost Password Form -->
        <form class="ibex-login-form" method="post" action="">
          <?php wp_nonce_field('ibex_lostpassword_action', 'ibex_lostpassword_nonce'); ?>
          
          <div class="ibex-login-field">
            <label for="user_login" class="ibex-login-label">
              <?php esc_html_e('Username or Email', 'ibex-racing-child'); ?>
            </label>
            <input 
              type="text" 
              name="user_login" 
              id="user_login" 
              class="ibex-login-input" 
              value="<?php echo isset($_POST['user_login']) ? esc_attr($_POST['user_login']) : ''; ?>"
              required
              autocomplete="username"
            >
          </div>

          <button type="submit" name="ibex_lostpassword_submit" class="ibex-login-submit">
            <?php esc_html_e('Get Reset Link', 'ibex-racing-child'); ?>
          </button>
        </form>

        <div class="ibex-login-footer">
          <a href="<?php echo esc_url(remove_query_arg('action')); ?>" class="ibex-login-link">
            <?php esc_html_e('← Back to login', 'ibex-racing-child'); ?>
          </a>
        </div>

      <?php elseif ($action === 'rp' && !empty($reset_key) && !empty($reset_login) && $reset_user && !is_wp_error($reset_user)) : ?>
        <!-- Password Reset Form -->
        <form class="ibex-login-form" method="post" action="" id="ibex-resetpass-form">
          <?php wp_nonce_field('ibex_resetpass_action', 'ibex_resetpass_nonce'); ?>
          <input type="hidden" name="rp_key" value="<?php echo esc_attr($reset_key); ?>">
          <input type="hidden" name="rp_login" value="<?php echo esc_attr($reset_login); ?>">
          
          <div class="ibex-login-field">
            <label for="pass1" class="ibex-login-label">
              <?php esc_html_e('New Password', 'ibex-racing-child'); ?>
            </label>
            <input 
              type="password" 
              name="pass1" 
              id="pass1" 
              class="ibex-login-input" 
              required
              autocomplete="new-password"
              minlength="8"
            >
            <small style="display: block; margin-top: 0.5rem; opacity: 0.7; font-size: 0.875rem;">
              <?php esc_html_e('Must be at least 8 characters', 'ibex-racing-child'); ?>
            </small>
          </div>

          <div class="ibex-login-field">
            <label for="pass2" class="ibex-login-label">
              <?php esc_html_e('Confirm New Password', 'ibex-racing-child'); ?>
            </label>
            <input 
              type="password" 
              name="pass2" 
              id="pass2" 
              class="ibex-login-input" 
              required
              autocomplete="new-password"
              minlength="8"
            >
          </div>

          <button type="submit" name="ibex_resetpass_submit" class="ibex-login-submit">
            <?php esc_html_e('Reset Password', 'ibex-racing-child'); ?>
          </button>
        </form>

        <script>
        // Simple password match validation
        document.getElementById('ibex-resetpass-form')?.addEventListener('submit', function(e) {
          var pass1 = document.getElementById('pass1').value;
          var pass2 = document.getElementById('pass2').value;
          if (pass1 !== pass2) {
            e.preventDefault();
            alert('<?php echo esc_js(__('The passwords do not match.', 'ibex-racing-child')); ?>');
          }
        });
        </script>

      <?php else : ?>
        <!-- Login Form -->
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
          <a href="<?php echo esc_url(add_query_arg('action', 'lostpassword')); ?>" class="ibex-login-link">
            <?php esc_html_e('Forgot your password?', 'ibex-racing-child'); ?>
          </a>
          
          <?php if (get_option('users_can_register')) : ?>
            <span class="ibex-login-separator">•</span>
            <a href="<?php echo esc_url(wp_registration_url()); ?>" class="ibex-login-link">
              <?php esc_html_e('Create an account', 'ibex-racing-child'); ?>
            </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    </div>
  </div>
</main>

<?php
get_footer();


