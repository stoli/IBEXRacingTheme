<?php
/**
 * Single template for IBEX Racing Listings.
 *
 * @package ibex-racing-child
 */

get_header();

$status_labels = [
  'available' => __('Available', 'ibex-racing-child'),
  'reserved'  => __('Reserved', 'ibex-racing-child'),
  'sold'      => __('Sold', 'ibex-racing-child'),
];

$status_class_map = [
  'available' => 'ibex-listing-hero__badge--available',
  'reserved'  => 'ibex-listing-hero__badge--reserved',
  'sold'      => 'ibex-listing-hero__badge--sold',
];

$background_url = '';
if (have_posts()) {
  the_post();
  $background_url = has_post_thumbnail() ? get_the_post_thumbnail_url(null, 'full') : '';
  rewind_posts();
}
?>

<main id="primary" class="site-main ibex-listing-single"<?php echo $background_url ? ' style="--ibex-listing-background:url(' . esc_url($background_url) . ')"' : ''; ?>>
  <?php
  while (have_posts()) :
    the_post();

    $listing_id    = get_the_ID();
    $price         = function_exists('get_field') ? get_field('listing_price', $listing_id) : '';
    $status_key    = function_exists('get_field') ? get_field('listing_status', $listing_id) : '';
    $summary       = function_exists('get_field') ? get_field('listing_summary', $listing_id) : '';
    $gallery       = function_exists('get_field') ? get_field('listing_gallery', $listing_id) : [];
    $contact_email = function_exists('get_field') ? get_field('listing_contact_email', $listing_id) : '';
    $contact_label = function_exists('get_field') ? get_field('listing_contact_label', $listing_id) : '';

    if (!$summary && has_excerpt()) {
      $summary = get_the_excerpt();
    }

    $contact_label = $contact_label ?: __('Inquire', 'ibex-racing-child');

    $mailto = '';
    if ($contact_email) {
      $subject = sprintf(
        __('Inquiry: %s', 'ibex-racing-child'),
        get_the_title()
      );
      $body = sprintf(
        "%s\n\n%s\n\n%s",
        __('Hello IBEX Racing Team,', 'ibex-racing-child'),
        __('I’m interested in learning more about the listing referenced above. Please get in touch when convenient.', 'ibex-racing-child'),
        sprintf(__('Listing URL: %s', 'ibex-racing-child'), get_permalink())
      );

      $mailto = sprintf(
        'mailto:%s?subject=%s&body=%s',
        antispambot($contact_email),
        rawurlencode($subject),
        rawurlencode($body)
      );
    }

    $status_label = $status_key && isset($status_labels[$status_key])
      ? $status_labels[$status_key]
      : '';

    $status_class = $status_key && isset($status_class_map[$status_key])
      ? $status_class_map[$status_key]
      : '';
    ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class('ibex-listing-single__article'); ?>>
      <header class="ibex-listing-hero">
        <div class="ibex-listing-hero__inner">
          <?php if ($status_label) : ?>
            <span class="ibex-listing-hero__badge <?php echo esc_attr($status_class); ?>">
              <?php echo esc_html($status_label); ?>
            </span>
          <?php endif; ?>
          <?php if ($price) : ?>
            <span class="ibex-listing-hero__price"><?php echo esc_html($price); ?></span>
          <?php endif; ?>
          <h1 class="ibex-listing-hero__title"><?php the_title(); ?></h1>
          <?php if ($summary) : ?>
            <p class="ibex-listing-hero__summary"><?php echo wp_kses_post($summary); ?></p>
          <?php endif; ?>
          <div class="ibex-listing-hero__actions">
            <a class="ibex-listing-hero__cta ibex-listing-hero__cta--secondary" href="#listing-details">
              <?php esc_html_e('View Details', 'ibex-racing-child'); ?>
            </a>
            <?php if ($mailto) : ?>
              <a class="ibex-listing-hero__cta ibex-listing-hero__cta--primary" href="<?php echo esc_url($mailto); ?>">
                <?php echo esc_html($contact_label); ?>
              </a>
            <?php endif; ?>
          </div>
        </div>
      </header>

      <div class="ibex-listing-single__body" id="listing-details">
        <div class="ibex-listing-single__content">
          <?php the_content(); ?>
        </div>

        <aside class="ibex-listing-single__sidebar" aria-label="<?php esc_attr_e('Listing inquiry', 'ibex-racing-child'); ?>">
          <div class="ibex-listing-single__sidebar-card">
            <h2><?php esc_html_e('Connect With Sales', 'ibex-racing-child'); ?></h2>
            <p><?php esc_html_e('Ready to talk logistics, transport, or inspections? Reach out and the IBEX team will coordinate next steps.', 'ibex-racing-child'); ?></p>
            <?php if ($mailto) : ?>
              <a class="ibex-listing-single__cta" href="<?php echo esc_url($mailto); ?>">
                <?php echo esc_html($contact_label); ?>
              </a>
            <?php endif; ?>
          </div>

          <?php
          // Show edit CTA for listing owner or admins
          if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $can_edit = (int) get_post_field('post_author', $listing_id) === $current_user->ID || current_user_can('edit_others_listings');
            
            if ($can_edit) {
              $dashboard_url = ibex_get_page_link_by_template('page-listing-dashboard.php');
              if ($dashboard_url) {
                $edit_url = add_query_arg(['listing_id' => $listing_id], $dashboard_url);
                ?>
                <div class="ibex-listing-single__sidebar-card ibex-listing-single__edit-card">
                  <h2><?php esc_html_e('Manage This Listing', 'ibex-racing-child'); ?></h2>
                  <p><?php esc_html_e('Update details, images, pricing, or availability.', 'ibex-racing-child'); ?></p>
                  <a class="ibex-listing-single__cta" href="<?php echo esc_url($edit_url); ?>">
                    <?php esc_html_e('Edit Listing', 'ibex-racing-child'); ?>
                  </a>
                </div>
                <?php
              }
            }
          }

          do_action('ibex_listing_sidebar_after', $listing_id);
          ?>
        </aside>
      </div>

      <?php if (!empty($gallery)) : ?>
        <section class="ibex-listing-gallery" aria-label="<?php esc_attr_e('Listing gallery', 'ibex-racing-child'); ?>">
          <div class="ibex-listing-gallery__header">
            <h2><?php esc_html_e('Gallery', 'ibex-racing-child'); ?></h2>
            <span class="ibex-listing-gallery__count">
              <?php
              printf(
                /* translators: %d: image count */
                esc_html(_n('%d image', '%d images', count($gallery), 'ibex-racing-child')),
                count($gallery)
              );
              ?>
            </span>
          </div>
          <div class="ibex-listing-gallery__grid">
            <?php foreach ($gallery as $image) : ?>
              <?php
              $full_url = wp_get_attachment_image_url($image['ID'], 'full');
              ?>
              <figure class="ibex-listing-gallery__item">
                <a class="ibex-listing-gallery__link" href="<?php echo esc_url($full_url); ?>" data-ibex-gallery="listing">
                  <?php echo wp_get_attachment_image($image['ID'], 'large', false, ['class' => 'ibex-listing-gallery__image']); ?>
                </a>
                <?php if (!empty($image['caption'])) : ?>
                  <figcaption class="ibex-listing-gallery__caption">
                    <?php echo esc_html($image['caption']); ?>
                  </figcaption>
                <?php endif; ?>
              </figure>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endif; ?>

      <footer class="ibex-listing-single__footer">
        <a class="ibex-listing-single__backlink" href="<?php echo esc_url(get_post_type_archive_link('listing')); ?>">
          <?php esc_html_e('Back to Listings', 'ibex-racing-child'); ?>
        </a>
      </footer>
    </article>
  <?php endwhile; ?>
</main>

<?php
get_footer();

