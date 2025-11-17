<?php
/**
 * Archive template for media galleries.
 *
 * @package ibex-racing-child
 */

get_header();

$paged          = max(1, get_query_var('paged') ?: get_query_var('page') ?: 1);
$galleries_per_page = 12;
$gallery_query  = new WP_Query([
  'post_type'      => 'media_gallery',
  'post_status'    => 'publish',
  'posts_per_page' => $galleries_per_page,
  'paged'          => $paged,
  'orderby'        => 'date',
  'order'          => 'DESC',
]);

?>

<main id="primary" class="site-main ibex-media-archive">
  <header class="ibex-archive-hero ibex-archive-hero--media">
    <div class="ibex-archive-hero__overlay">
      <div class="ibex-archive-hero__eyebrow"><?php esc_html_e('Media Archive', 'ibex-racing-child'); ?></div>
      <h1 class="ibex-archive-hero__title"><?php post_type_archive_title(); ?></h1>
      <p class="ibex-archive-hero__intro">
        <?php esc_html_e('Relive the action with curated photo and video highlights from IBEX Racing events, hospitality suites, and behind-the-scenes moments.', 'ibex-racing-child'); ?>
      </p>
      <div class="ibex-archive-hero__cta">
        <a class="ibex-button ibex-button--outline" href="<?php echo esc_url(home_url('/events')); ?>">
          <?php esc_html_e('View Events', 'ibex-racing-child'); ?>
        </a>
        <?php if (is_user_logged_in() && current_user_can('edit_media_galleries')) : ?>
          <?php
          $dashboard_url = ibex_get_page_link_by_template('page-media-gallery-dashboard.php');
          if ($dashboard_url) :
            ?>
            <a class="ibex-button" href="<?php echo esc_url($dashboard_url); ?>">
              <?php esc_html_e('Manage Galleries', 'ibex-racing-child'); ?>
            </a>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <section class="ibex-archive-section ibex-archive-section--media">
    <header class="ibex-archive-section__header">
      <div>
        <h2><?php esc_html_e('Latest Galleries', 'ibex-racing-child'); ?></h2>
        <p><?php esc_html_e('Captured moments from the paddock, hospitality experiences, and track action.', 'ibex-racing-child'); ?></p>
      </div>
    </header>

    <?php if ($gallery_query->have_posts()) : ?>
      <div class="ibex-card-grid ibex-card-grid--media">
        <?php
        while ($gallery_query->have_posts()) :
          $gallery_query->the_post();

          $gallery_id   = get_the_ID();
          $cover_src    = has_post_thumbnail() ? get_the_post_thumbnail_url($gallery_id, 'large') : '';
          $start_date   = function_exists('get_field') ? get_field('media_gallery_start_date', $gallery_id) : '';
          $end_date     = function_exists('get_field') ? get_field('media_gallery_end_date', $gallery_id) : '';
          $location     = function_exists('get_field') ? get_field('media_gallery_location', $gallery_id) : '';
          $overview     = function_exists('get_field') ? get_field('media_gallery_overview', $gallery_id) : '';
          $event_id     = function_exists('get_field') ? (int) get_field('media_gallery_related_event', $gallery_id) : 0;
          $event_title  = $event_id ? get_the_title($event_id) : '';
          $event_link   = $event_id ? get_permalink($event_id) : '';
          $date_label   = function_exists('ibex_format_date_range')
            ? ibex_format_date_range($start_date, $end_date)
            : '';
          if (!$cover_src && function_exists('ibex_get_gallery_preview_items')) {
            $preview = ibex_get_gallery_preview_items($gallery_id, 1);
            if (!empty($preview[0]['image'])) {
              $cover_src = $preview[0]['image'];
            }
          }
          ?>

          <article class="ibex-card ibex-card--media">
            <a class="ibex-card__media" href="<?php the_permalink(); ?>">
              <?php if ($cover_src) : ?>
                <img src="<?php echo esc_url($cover_src); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="lazy" class="ibex-card__image" />
              <?php else : ?>
                <span class="ibex-card__placeholder"><?php esc_html_e('Media Gallery', 'ibex-racing-child'); ?></span>
              <?php endif; ?>
            </a>
            <div class="ibex-card__body">
              <h2 class="ibex-card__title">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
              </h2>
              <ul class="ibex-card__meta">
                <?php if ($date_label) : ?>
                  <li>
                    <span class="ibex-card__meta-label"><?php esc_html_e('Date', 'ibex-racing-child'); ?></span>
                    <span class="ibex-card__meta-value"><?php echo esc_html($date_label); ?></span>
                  </li>
                <?php endif; ?>
                <?php if ($location) : ?>
                  <li>
                    <span class="ibex-card__meta-label"><?php esc_html_e('Location', 'ibex-racing-child'); ?></span>
                    <span class="ibex-card__meta-value"><?php echo esc_html($location); ?></span>
                  </li>
                <?php endif; ?>
              </ul>
              <?php if ($overview) : ?>
                <p class="ibex-card__summary">
                  <?php echo wp_kses_post(wp_trim_words($overview, 30, '&hellip;')); ?>
                </p>
              <?php elseif (has_excerpt()) : ?>
                <p class="ibex-card__summary">
                  <?php echo wp_kses_post(get_the_excerpt()); ?>
                </p>
              <?php endif; ?>

              <div class="ibex-card__actions">
                <a class="ibex-button" href="<?php the_permalink(); ?>">
                  <?php esc_html_e('View Gallery', 'ibex-racing-child'); ?>
                </a>
                <?php if ($event_link) : ?>
                  <a class="ibex-button ibex-button--ghost" href="<?php echo esc_url($event_link); ?>">
                    <?php echo esc_html(sprintf(__('Event: %s', 'ibex-racing-child'), $event_title)); ?>
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </article>
        <?php endwhile; ?>
      </div>

      <?php
      $total_pages = (int) $gallery_query->max_num_pages;
      if ($total_pages > 1) :
        ?>
        <nav class="ibex-pagination" aria-label="<?php esc_attr_e('Media gallery pagination', 'ibex-racing-child'); ?>">
          <?php
          $big = 999999999;
          echo paginate_links([
            'base'      => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
            'format'    => get_option('permalink_structure') ? 'page/%#%/' : '&paged=%#%',
            'total'     => $total_pages,
            'current'   => $paged,
            'prev_text' => __('Previous', 'ibex-racing-child'),
            'next_text' => __('Next', 'ibex-racing-child'),
          ]);
          ?>
        </nav>
      <?php endif; ?>
      <?php wp_reset_postdata(); ?>
    <?php else : ?>
      <p class="ibex-archive-empty">
        <?php esc_html_e('Our media galleries are being curated. Check back soon for photos and video highlights.', 'ibex-racing-child'); ?>
      </p>
    <?php endif; ?>
  </section>
</main>

<?php
get_footer();

