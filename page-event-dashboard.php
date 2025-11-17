<?php
/**
 * Template Name: Event Dashboard
 *
 * Front-end interface for managing events.
 *
 * @package IbexRacingChild
 */

if (!defined('ABSPATH')) {
  exit;
}

acf_form_head();

if (!is_user_logged_in()) {
  auth_redirect();
}

$page_title = '';
$page_intro = '';

if (have_posts()) {
  the_post();
  $page_title = get_the_title();
  $page_intro = get_the_excerpt();
}

get_header();

$dashboard_callback = static function ($settings) {
  get_template_part('template-parts/dashboard/events', null, ['settings' => $settings]);
};

?>
<main id="primary" class="site-main ibex-event-dashboard">
  <?php
  ibex_render_dashboard(
    [
      'page_title' => $page_title ?: __('Events', 'ibex-racing-child'),
      'page_intro' => $page_intro,
      'active_key' => 'events',
    ],
    $dashboard_callback
  );
  ?>
</main>

<?php
get_footer();

