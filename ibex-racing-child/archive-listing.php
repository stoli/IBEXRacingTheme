<?php
/**
 * Archive template for IBEX Racing Listings.
 *
 * @package ibex-racing-child
 */

get_header();

$intro_html = '';
$intro_page = apply_filters('ibex_listing_archive_intro_page', get_page_by_path('for-sale'));

if ($intro_page instanceof WP_Post) {
  $raw_intro = $intro_page->post_excerpt ?: $intro_page->post_content;
  if ($raw_intro) {
    $intro_html = apply_filters(
      'ibex_listing_archive_intro_content',
      wp_kses_post(wpautop($raw_intro))
    );
  }
}

$status_labels = [
  'available' => __('Available', 'ibex-racing-child'),
  'reserved'  => __('Reserved', 'ibex-racing-child'),
  'sold'      => __('Sold', 'ibex-racing-child'),
];

$archive_description = get_the_archive_description();
if (!$archive_description && $intro_html) {
  $archive_description = $intro_html;
}
if (!$archive_description) {
  $archive_description = '<p>' . esc_html__('Track-ready chassis, support equipment, and hospitality assets available from IBEX Racing.', 'ibex-racing-child') . '</p>';
}

$contact_page = get_permalink(get_page_by_path('contact'));
?>

<main id="primary" class="site-main ibex-listings-archive">
  <header class="ibex-archive-hero ibex-archive-hero--listings">
    <div class="ibex-archive-hero__overlay">
      <span class="ibex-archive-hero__eyebrow"><?php esc_html_e('Store', 'ibex-racing-child'); ?></span>
      <h1 class="ibex-archive-hero__title"><?php the_archive_title(); ?></h1>
      <div class="ibex-archive-hero__intro">
        <?php echo wp_kses_post($archive_description); ?>
      </div>
      <div class="ibex-archive-hero__cta">
        <?php if ($contact_page) : ?>
          <a class="ibex-button ibex-button--outline" href="<?php echo esc_url($contact_page); ?>">
            <?php esc_html_e('Contact Sales', 'ibex-racing-child'); ?>
          </a>
        <?php endif; ?>
        <?php if (is_user_logged_in() && current_user_can('edit_listings')) : ?>
          <?php
          $dashboard_url = ibex_get_page_link_by_template('page-listing-dashboard.php');
          if ($dashboard_url) :
            ?>
            <a class="ibex-button" href="<?php echo esc_url($dashboard_url); ?>">
              <?php esc_html_e('Manage Listings', 'ibex-racing-child'); ?>
            </a>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <section class="ibex-archive-section ibex-listing-section">
    <?php if (have_posts()) : ?>
      <div class="ibex-listing-grid">
        <?php
        while (have_posts()) :
          the_post();

          $listing_id     = get_the_ID();
          $price          = function_exists('get_field') ? get_field('listing_price', $listing_id) : '';
          $status_key     = function_exists('get_field') ? get_field('listing_status', $listing_id) : '';
          $summary        = function_exists('get_field') ? get_field('listing_summary', $listing_id) : '';
          $contact_email  = function_exists('get_field') ? get_field('listing_contact_email', $listing_id) : '';
          $contact_label  = function_exists('get_field') ? get_field('listing_contact_label', $listing_id) : '';

          if (!$summary) {
            $summary = has_excerpt() ? get_the_excerpt() : '';
          }

          $status_label = $status_key && isset($status_labels[$status_key])
            ? $status_labels[$status_key]
            : '';

          $status_class = $status_key ? ' ibex-listing-card__status--' . esc_attr($status_key) : '';

          $mailto = '';
          if ($contact_email) {
            $subject = sprintf(
              '%s: %s',
              get_bloginfo('name'),
              get_the_title()
            );
            $mailto = sprintf('mailto:%s?subject=%s', antispambot($contact_email), rawurlencode($subject));
          }

          $contact_label = $contact_label ?: __('Inquire', 'ibex-racing-child');
          ?>

          <article <?php post_class('ibex-listing-card'); ?>>
            <a class="ibex-listing-card__media" href="<?php the_permalink(); ?>">
              <?php if (has_post_thumbnail()) : ?>
                <?php the_post_thumbnail('large', ['class' => 'ibex-listing-card__image']); ?>
              <?php else : ?>
                <div class="ibex-listing-card__placeholder" aria-hidden="true"></div>
              <?php endif; ?>
              <?php if ($status_label) : ?>
                <span class="ibex-listing-card__status<?php echo $status_class; ?>">
                  <?php echo esc_html($status_label); ?>
                </span>
              <?php endif; ?>
            </a>
            <div class="ibex-listing-card__content">
              <div class="ibex-listing-card__header">
                <h2 class="ibex-listing-card__title">
                  <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h2>
                <?php if ($price) : ?>
                  <span class="ibex-listing-card__price"><?php echo esc_html($price); ?></span>
                <?php endif; ?>
              </div>
              <?php if ($summary) : ?>
                <p class="ibex-listing-card__summary"><?php echo wp_kses_post($summary); ?></p>
              <?php endif; ?>
              <div class="ibex-listing-card__actions">
                <a class="ibex-listing-card__link" href="<?php the_permalink(); ?>">
                  <?php esc_html_e('View Details', 'ibex-racing-child'); ?>
                </a>
                <?php if ($mailto) : ?>
                  <a class="ibex-listing-card__cta" href="<?php echo esc_url($mailto); ?>">
                    <?php echo esc_html($contact_label); ?>
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </article>
        <?php endwhile; ?>
      </div>

      <?php the_posts_pagination([
        'mid_size'  => 2,
        'prev_text' => __('« Previous', 'ibex-racing-child'),
        'next_text' => __('Next »', 'ibex-racing-child'),
      ]); ?>
    <?php else : ?>
      <p class="ibex-listings-archive__empty">
        <?php esc_html_e('No listings are available right now. Reach out to our team for upcoming inventory.', 'ibex-racing-child'); ?>
      </p>
    <?php endif; ?>
  </section>
</main>

<?php
get_footer();

?>

