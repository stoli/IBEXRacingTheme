<?php
/**
 * Single template for IBEX Racing Events.
 *
 * @package ibex-racing-child
 */

get_header();

$format_event_dates = static function (?string $start, ?string $end): string {
  if (function_exists('ibex_format_date_range')) {
    return ibex_format_date_range($start, $end);
  }

  if (!$start) {
    return '';
  }

  $start_time = strtotime($start);
  $end_time   = $end ? strtotime($end) : false;

  if (!$start_time) {
    return '';
  }

  $start_label = wp_date('F j, Y', $start_time);

  if ($end_time && $end_time !== $start_time) {
    $end_label = wp_date('F j, Y', $end_time);
    return sprintf('%s – %s', $start_label, $end_label);
  }

  return $start_label;
};

$event_background_url = '';
if (have_posts()) {
  the_post();
  $event_background_url = has_post_thumbnail() ? get_the_post_thumbnail_url(null, 'full') : '';
  rewind_posts();
}
?>

<main id="primary" class="site-main ibex-event-single"<?php echo $event_background_url ? ' style="--ibex-event-background:url(' . esc_url($event_background_url) . ')"' : ''; ?>>
  <?php
  while (have_posts()) :
    the_post();

    $event_id       = get_the_ID();
    $start_date     = function_exists('get_field') ? get_field('event_start_date', $event_id) : '';
    $end_date       = function_exists('get_field') ? get_field('event_end_date', $event_id) : '';
    $location       = function_exists('get_field') ? get_field('event_location', $event_id) : '';
    $registration   = function_exists('get_field') ? get_field('event_registration_url', $event_id) : '';
    $registration_label = function_exists('get_field') ? get_field('event_registration_label', $event_id) : '';
    $summary        = function_exists('get_field') ? get_field('event_summary', $event_id) : '';

    if (!$registration_label) {
      $registration_label = __('Register Now', 'ibex-racing-child');
    }

    $date_label = $format_event_dates($start_date, $end_date);

    $status_label = '';
    $today        = current_time('Y-m-d');
    
    // Determine if event is past
    $is_past = false;
    if ($end_date) {
      // If end_date exists, event is past if end_date is before today
      $is_past = ($end_date < $today);
    } elseif ($start_date) {
      // If only start_date exists, event is past if start_date is before today
      $is_past = ($start_date < $today);
    }
    // If no dates exist, assume event is not past (show registration button)

    // Determine status label
    if ($start_date && $start_date > $today) {
      $status_label = __('Upcoming', 'ibex-racing-child');
    } elseif ($end_date && $end_date >= $today) {
      $status_label = __('In Progress', 'ibex-racing-child');
    } elseif ($start_date && $start_date <= $today && !$end_date) {
      $status_label = ($start_date === $today)
        ? __('Today', 'ibex-racing-child')
        : __('Completed', 'ibex-racing-child');
    } else {
      $status_label = __('Completed', 'ibex-racing-child');
    }

    $gallery_data = null;
    $gallery_post = function_exists('ibex_get_event_gallery') ? ibex_get_event_gallery($event_id, true) : null;

    if ($gallery_post instanceof WP_Post) {
      $gallery_id      = $gallery_post->ID;
      $gallery_status  = get_post_status($gallery_post);
      $can_edit_gallery = current_user_can('edit_post', $gallery_id);
      $dashboard_url   = function_exists('ibex_get_page_link_by_template')
        ? ibex_get_page_link_by_template('page-media-gallery-dashboard.php')
        : null;

      $raw_items   = function_exists('get_field') ? get_field('media_gallery_items', $gallery_id) : [];
      $event_items = [];

      if (is_array($raw_items)) {
        foreach ($raw_items as $item) {
          $display = !isset($item['display_on_event_page']) || (bool) $item['display_on_event_page'];
          if ($display) {
            $event_items[] = $item;
          }
        }
      }

      $preview_items = function_exists('ibex_get_gallery_preview_items')
        ? ibex_get_gallery_preview_items($gallery_id, 4)
        : [];

      $gallery_date = function_exists('get_field')
        ? $format_event_dates(get_field('media_gallery_start_date', $gallery_id), get_field('media_gallery_end_date', $gallery_id))
        : '';

      $gallery_overview = function_exists('get_field')
        ? get_field('media_gallery_overview', $gallery_id)
        : '';

      $gallery_cover = has_post_thumbnail($gallery_id)
        ? get_the_post_thumbnail_url($gallery_id, 'large')
        : '';

      if (!$gallery_cover && $preview_items) {
        $first_preview = $preview_items[0];
        if (!empty($first_preview['image'])) {
          $gallery_cover = $first_preview['image'];
        }
      }

      $event_item_count = count($event_items);
      $preview_count    = count($preview_items);
      $remaining        = max(0, $event_item_count - $preview_count);
      $is_live          = ($gallery_status === 'publish') && $event_item_count > 0;

      $gallery_data = [
        'post'        => $gallery_post,
        'id'          => $gallery_id,
        'title'       => get_the_title($gallery_id),
        'url'         => get_permalink($gallery_id),
        'status'      => $gallery_status,
        'is_live'     => $is_live,
        'can_edit'    => $can_edit_gallery,
        'preview'     => $preview_items,
        'date'        => $gallery_date,
        'overview'    => $gallery_overview,
        'cover'       => $gallery_cover,
        'remaining'   => $remaining,
        'total_event' => $event_item_count,
        'dashboard'   => $dashboard_url,
      ];
    }
    ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class('ibex-event-single__article'); ?>>
      <header class="ibex-event-single__hero">
        <div class="ibex-event-single__hero-overlay">
          <div class="ibex-event-single__meta">
            <?php if ($status_label) : ?>
              <span class="ibex-event-single__status"><?php echo esc_html($status_label); ?></span>
            <?php endif; ?>
            <?php if ($date_label) : ?>
              <span class="ibex-event-single__date"><?php echo esc_html($date_label); ?></span>
            <?php endif; ?>
            <?php if ($location) : ?>
              <span class="ibex-event-single__location"><?php echo esc_html($location); ?></span>
            <?php endif; ?>
          </div>
          <h1 class="ibex-event-single__title"><?php the_title(); ?></h1>
          <?php if ($summary) : ?>
            <p class="ibex-event-single__summary"><?php echo wp_kses_post($summary); ?></p>
          <?php elseif (has_excerpt()) : ?>
            <p class="ibex-event-single__summary"><?php echo wp_kses_post(get_the_excerpt()); ?></p>
          <?php endif; ?>
          <div class="ibex-event-single__cta-group">
            <a class="ibex-event-single__cta ibex-event-single__cta--primary" href="#event-details">
              <?php esc_html_e('Event Details', 'ibex-racing-child'); ?>
            </a>
            <?php if ($registration && !$is_past) : ?>
              <a class="ibex-event-single__cta ibex-event-single__cta--accent" href="<?php echo esc_url($registration); ?>" target="_blank" rel="noopener">
                <?php echo esc_html($registration_label); ?>
              </a>
            <?php endif; ?>
          </div>
        </div>
      </header>

      <div class="ibex-event-single__body">
        <div class="ibex-event-single__content">
          <?php the_content(); ?>
        </div>

        <aside id="event-details" class="ibex-event-single__sidebar" aria-label="<?php esc_attr_e('Event quick facts', 'ibex-racing-child'); ?>">
          <div class="ibex-event-single__sidebar-card">
            <h2><?php esc_html_e('Event Quick Facts', 'ibex-racing-child'); ?></h2>
            <ul class="ibex-event-single__facts">
              <?php if ($date_label) : ?>
                <li>
                  <span class="ibex-event-single__fact-label"><?php esc_html_e('Date', 'ibex-racing-child'); ?></span>
                  <span class="ibex-event-single__fact-value"><?php echo esc_html($date_label); ?></span>
                </li>
              <?php endif; ?>
              <?php if ($location) : ?>
                <li>
                  <span class="ibex-event-single__fact-label"><?php esc_html_e('Location', 'ibex-racing-child'); ?></span>
                  <span class="ibex-event-single__fact-value"><?php echo esc_html($location); ?></span>
                </li>
              <?php endif; ?>
              <?php if ($status_label) : ?>
                <li>
                  <span class="ibex-event-single__fact-label"><?php esc_html_e('Status', 'ibex-racing-child'); ?></span>
                  <span class="ibex-event-single__fact-value"><?php echo esc_html($status_label); ?></span>
                </li>
              <?php endif; ?>
            </ul>
            <?php if ($registration && !$is_past) : ?>
              <a class="ibex-event-single__cta ibex-event-single__cta--accent" href="<?php echo esc_url($registration); ?>" target="_blank" rel="noopener">
                <?php echo esc_html($registration_label); ?>
              </a>
            <?php endif; ?>
          </div>

          <div class="ibex-event-single__sidebar-card ibex-event-single__sidebar-card--secondary">
            <h2><?php esc_html_e('Stay in the Loop', 'ibex-racing-child'); ?></h2>
            <p><?php esc_html_e('Join our paddock list for hospitality updates, ticket drops, and activation invites.', 'ibex-racing-child'); ?></p>
            <?php
            $event_title = get_the_title();
            $email_subject = sprintf('[%s] information', $event_title);
            $mailto_url = 'mailto:info@ibexracing.com?subject=' . rawurlencode($email_subject);
            ?>
            <a class="ibex-event-single__cta ibex-event-single__cta--outline" href="<?php echo esc_url($mailto_url); ?>">
              <?php esc_html_e('Contact Us', 'ibex-racing-child'); ?>
            </a>
          </div>

          <?php
          // Show edit CTA for event owner or admins
          if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $can_edit = (int) get_post_field('post_author', $event_id) === $current_user->ID || current_user_can('edit_others_race_events');
            
            if ($can_edit) {
              $dashboard_url = ibex_get_page_link_by_template('page-event-dashboard.php');
              if ($dashboard_url) {
                $edit_url = add_query_arg(['event_id' => $event_id], $dashboard_url);
                ?>
                <div class="ibex-event-single__sidebar-card ibex-event-single__edit-card">
                  <h2><?php esc_html_e('Manage This Event', 'ibex-racing-child'); ?></h2>
                  <p><?php esc_html_e('Update event details, dates, location, and registration settings.', 'ibex-racing-child'); ?></p>
                  <a class="ibex-event-single__cta" href="<?php echo esc_url($edit_url); ?>">
                    <?php esc_html_e('Edit Event', 'ibex-racing-child'); ?>
                  </a>
                </div>
                <?php
              }
            }
          }

          /**
           * Placeholder for future team member roster block.
           *
           * Hook here with add_action( 'ibex_event_sidebar_after', 'callback' ) when ready.
           */
          do_action('ibex_event_sidebar_after', $event_id);
          ?>
        </aside>
      </div>

      <?php if ($gallery_data) : ?>
        <?php if ($gallery_data['is_live']) : ?>
          <section class="ibex-event-single__gallery"<?php echo $gallery_data['cover'] ? ' style="--ibex-event-gallery-cover:url(' . esc_url($gallery_data['cover']) . ')"' : ''; ?>>
            <div class="ibex-event-single__gallery-header">
              <div class="ibex-event-single__gallery-heading">
                <h2><?php esc_html_e('Event Media Gallery', 'ibex-racing-child'); ?></h2>
                <?php if ($gallery_data['date']) : ?>
                  <p class="ibex-event-single__gallery-meta"><?php echo esc_html($gallery_data['date']); ?></p>
                <?php endif; ?>
              </div>
              <?php if (!empty($gallery_data['overview'])) : ?>
                <div class="ibex-event-single__gallery-summary">
                  <?php echo wp_kses_post(wpautop($gallery_data['overview'])); ?>
                </div>
              <?php endif; ?>
            </div>

            <?php if (!empty($gallery_data['preview'])) : ?>
              <div class="ibex-event-single__gallery-grid">
                <?php foreach ($gallery_data['preview'] as $item) : ?>
                  <?php
                  $tile_classes = ['ibex-event-single__gallery-item'];
                  $tile_classes[] = 'ibex-event-single__gallery-item--' . sanitize_html_class($item['type'] ?? 'image');
                  ?>
                  <a class="<?php echo esc_attr(implode(' ', $tile_classes)); ?>" href="<?php echo esc_url($gallery_data['url']); ?>" aria-label="<?php echo esc_attr(sprintf(__('View %s in the full gallery', 'ibex-racing-child'), $gallery_data['title'])); ?>">
                    <?php if (!empty($item['image'])) : ?>
                      <img src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['alt'] ?? $gallery_data['title']); ?>" loading="lazy" />
                    <?php else : ?>
                      <span class="ibex-event-single__gallery-item-placeholder" aria-hidden="true">
                        <?php echo esc_html__('Media', 'ibex-racing-child'); ?>
                      </span>
                    <?php endif; ?>
                    <?php if (!empty($item['type']) && $item['type'] !== 'image') : ?>
                      <span class="ibex-event-single__gallery-flag">
                        <?php echo $item['type'] === 'video' ? esc_html__('Video', 'ibex-racing-child') : esc_html__('Embed', 'ibex-racing-child'); ?>
                      </span>
                    <?php endif; ?>
                  </a>
                <?php endforeach; ?>

                <?php if ($gallery_data['remaining'] > 0) : ?>
                  <a class="ibex-event-single__gallery-item ibex-event-single__gallery-item--more" href="<?php echo esc_url($gallery_data['url']); ?>" aria-label="<?php echo esc_attr(sprintf(_n('View %d more item', 'View %d more items', $gallery_data['remaining'], 'ibex-racing-child'), $gallery_data['remaining'])); ?>">
                    <span class="ibex-event-single__gallery-more-count">+<?php echo esc_html($gallery_data['remaining']); ?></span>
                  </a>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <div class="ibex-event-single__gallery-actions">
              <a class="ibex-event-single__cta ibex-event-single__cta--primary" href="<?php echo esc_url($gallery_data['url']); ?>">
                <?php esc_html_e('View Full Gallery', 'ibex-racing-child'); ?>
              </a>
              <?php if ($gallery_data['can_edit'] && $gallery_data['dashboard']) : ?>
                <a class="ibex-event-single__cta ibex-event-single__cta--outline" href="<?php echo esc_url(add_query_arg('gallery_id', $gallery_data['id'], $gallery_data['dashboard'])); ?>">
                  <?php esc_html_e('Edit Gallery', 'ibex-racing-child'); ?>
                </a>
              <?php endif; ?>
            </div>
          </section>
        <?php elseif ($gallery_data['can_edit']) : ?>
          <section class="ibex-event-single__gallery ibex-event-single__gallery--empty">
            <div class="ibex-event-single__gallery-header">
              <div class="ibex-event-single__gallery-heading">
                <h2><?php esc_html_e('Event Media Gallery', 'ibex-racing-child'); ?></h2>
                <p class="ibex-event-single__gallery-meta">
                  <?php esc_html_e('This gallery is not yet published or does not have assets to display.', 'ibex-racing-child'); ?>
                </p>
              </div>
            </div>
            <?php if ($gallery_data['dashboard']) : ?>
              <div class="ibex-event-single__gallery-actions">
                <a class="ibex-event-single__cta ibex-event-single__cta--accent" href="<?php echo esc_url(add_query_arg('gallery_id', $gallery_data['id'], $gallery_data['dashboard'])); ?>">
                  <?php esc_html_e('Add Media to Gallery', 'ibex-racing-child'); ?>
                </a>
              </div>
            <?php endif; ?>
          </section>
        <?php endif; ?>
      <?php endif; ?>

      <footer class="ibex-event-single__footer">
        <a class="ibex-event-single__backlink" href="<?php echo esc_url(get_post_type_archive_link('race_event')); ?>">
          <?php esc_html_e('Back to Events', 'ibex-racing-child'); ?>
        </a>
      </footer>
    </article>
  <?php endwhile; ?>
</main>

<?php
get_footer();

