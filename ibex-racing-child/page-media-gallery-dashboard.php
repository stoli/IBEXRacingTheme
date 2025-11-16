<?php
/**
 * Template Name: Media Gallery Dashboard
 *
 * Front-end interface for managing media galleries.
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
}

get_header();

$dashboard_callback = static function ($settings) {
  get_template_part('template-parts/dashboard/media-galleries', null, ['settings' => $settings]);
};

?>
<main id="primary" class="site-main ibex-media-gallery-dashboard">
  <?php
  ibex_render_dashboard(
    [
      'page_title' => $page_title ?: __('Media Galleries', 'ibex-racing-child'),
      'page_intro' => $page_intro,
      'active_key' => 'media-galleries',
    ],
    $dashboard_callback
  );
  ?>
</main>

<?php
get_footer();

?>

