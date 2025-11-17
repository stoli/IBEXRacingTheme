<?php
/**
 * Template Name: Listing Dashboard
 *
 * Front-end interface for managing For Sale listings.
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
  get_template_part('template-parts/dashboard/listings', null, ['settings' => $settings]);
};

?>
<main id="primary" class="site-main ibex-listing-dashboard">
  <?php
  ibex_render_dashboard(
    [
      'page_title' => $page_title ?: __('For Sale Listings', 'ibex-racing-child'),
      'page_intro' => $page_intro,
      'active_key' => 'listings',
    ],
    $dashboard_callback
  );
  ?>
</main>

<?php
get_footer();

?>

