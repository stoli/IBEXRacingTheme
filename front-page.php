<?php
/**
 * Front page template for IBEX Racing.
 *
 * Inspired by Wayne Taylor Racing layout: hero, event grid, feature sidebar.
 *
 * @package ibex-racing-child
 */

get_header();

$hero_title          = apply_filters('ibex_home_hero_title', get_bloginfo('name'));
$hero_subtitle       = apply_filters('ibex_home_hero_subtitle', get_bloginfo('description'));
$hero_primary_cta    = apply_filters('ibex_home_hero_primary_cta', [
  'label' => __('View Events', 'ibex-racing-child'),
  'url'   => get_post_type_archive_link('race_event'),
]);
$hero_secondary_cta  = apply_filters('ibex_home_hero_secondary_cta', [
  'label' => __('Hospitality & Programs', 'ibex-racing-child'),
  'url'   => home_url('/contact'),
]);
$hero_background_url = apply_filters('ibex_home_hero_background', '');

$today          = current_time('Y-m-d');
$events_query   = new WP_Query([
  'post_type'      => 'race_event',
  'posts_per_page' => 8,
  'post_status'    => 'publish',
  'meta_key'       => 'event_start_date',
  'orderby'        => 'meta_value',
  'order'          => 'ASC',
  'meta_type'      => 'DATE',
]);

$upcoming_events = [];
$past_events     = [];

if ($events_query->have_posts()) {
  while ($events_query->have_posts()) {
    $events_query->the_post();

    $event_id   = get_the_ID();
    $start_date = function_exists('get_field') ? get_field('event_start_date', $event_id) : '';
    $end_date   = function_exists('get_field') ? get_field('event_end_date', $event_id) : '';
    $location   = function_exists('get_field') ? get_field('event_location', $event_id) : '';
    $summary    = function_exists('get_field') ? get_field('event_summary', $event_id) : '';
    $reg_url    = function_exists('get_field') ? get_field('event_registration_url', $event_id) : '';
    $reg_label  = function_exists('get_field') ? get_field('event_registration_label', $event_id) : __('Register Now', 'ibex-racing-child');

    if (!$summary) {
      $summary = has_excerpt($event_id) ? get_the_excerpt($event_id) : '';
    }

    $is_upcoming = false;
    if ($start_date) {
      $is_upcoming = $start_date >= $today;
    }
    if (!$is_upcoming && $end_date) {
      $is_upcoming = $end_date >= $today;
    }

    $payload = [
      'id'        => $event_id,
      'start'     => $start_date,
      'end'       => $end_date,
      'location'  => $location,
      'summary'   => $summary,
      'reg_url'   => $reg_url,
      'reg_label' => $reg_label ?: __('Register Now', 'ibex-racing-child'),
    ];

    if ($is_upcoming) {
      $upcoming_events[] = $payload;
    } else {
      $past_events[] = $payload;
    }
  }
  wp_reset_postdata();
}

$format_event_dates = static function (?string $start, ?string $end): string {
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

$sort_events = static function (array &$events, bool $ascending = true): void {
  usort(
    $events,
    static function (array $a, array $b) use ($ascending) {
      $a_start = $a['start'] ?: ($ascending ? '9999-12-31' : '0000-01-01');
      $b_start = $b['start'] ?: ($ascending ? '9999-12-31' : '0000-01-01');

      if ($a_start === $b_start) {
        return 0;
      }

      if ($ascending) {
        return ($a_start < $b_start) ? -1 : 1;
      }

      return ($a_start > $b_start) ? -1 : 1;
    }
  );
};

$sort_events($upcoming_events, true);
$sort_events($past_events, false);

// Get first upcoming event for sidebar
$next_event = !empty($upcoming_events) ? $upcoming_events[0] : null;

// Query most recent 4 galleries for main section
$galleries_query = new WP_Query([
  'post_type'      => 'media_gallery',
  'post_status'    => 'publish',
  'posts_per_page' => 4,
  'orderby'        => 'date',
  'order'          => 'DESC',
]);

$recent_galleries = [];
if ($galleries_query->have_posts()) {
  while ($galleries_query->have_posts()) {
    $galleries_query->the_post();
    $gallery_id   = get_the_ID();
    $cover_src    = has_post_thumbnail() ? get_the_post_thumbnail_url($gallery_id, 'large') : '';
    $start_date   = function_exists('get_field') ? get_field('media_gallery_start_date', $gallery_id) : '';
    $end_date     = function_exists('get_field') ? get_field('media_gallery_end_date', $gallery_id) : '';
    $location     = function_exists('get_field') ? get_field('media_gallery_location', $gallery_id) : '';
    $overview     = function_exists('get_field') ? get_field('media_gallery_overview', $gallery_id) : '';
    
    if (!$cover_src && function_exists('ibex_get_gallery_preview_items')) {
      $preview = ibex_get_gallery_preview_items($gallery_id, 1);
      if (!empty($preview[0]['image'])) {
        $cover_src = $preview[0]['image'];
      }
    }
    
    if (!$overview && has_excerpt($gallery_id)) {
      $overview = get_the_excerpt($gallery_id);
    }
    
    $date_label = function_exists('ibex_format_date_range')
      ? ibex_format_date_range($start_date, $end_date)
      : '';
    
    $recent_galleries[] = [
      'id'        => $gallery_id,
      'title'     => get_the_title($gallery_id),
      'permalink' => get_permalink($gallery_id),
      'cover_src' => $cover_src,
      'date'      => $date_label,
      'location'  => $location ?: '',
      'overview'  => $overview ?: '',
    ];
  }
  wp_reset_postdata();
}

// Collect sidebar highlights.
$gallery_highlight = null;
$gallery_page      = get_page_by_path('photo-gallery');
if (!$gallery_page) {
  $gallery_page = get_page_by_path('gallery');
}
if ($gallery_page) {
  $gallery_highlight = [
    'title'   => get_the_title($gallery_page),
    'excerpt' => get_the_excerpt($gallery_page),
    'link'    => get_permalink($gallery_page),
    'image'   => get_the_post_thumbnail($gallery_page, 'large', ['class' => 'ibex-home-sidebar-card__image']),
  ];
}

// Get most recent listing for sidebar
$recent_listing = null;
$listing_query  = new WP_Query([
  'post_type'      => 'listing',
  'posts_per_page' => 1,
  'post_status'    => 'publish',
  'orderby'        => 'date',
  'order'          => 'DESC',
]);

if ($listing_query->have_posts()) {
  while ($listing_query->have_posts()) {
    $listing_query->the_post();
    $listing_id   = get_the_ID();
    $price        = function_exists('get_field') ? get_field('listing_price', $listing_id) : '';
    $status_key   = function_exists('get_field') ? get_field('listing_status', $listing_id) : '';
    $summary      = function_exists('get_field') ? get_field('listing_summary', $listing_id) : '';
    $contact_email = function_exists('get_field') ? get_field('listing_contact_email', $listing_id) : '';
    $contact_label = function_exists('get_field') ? get_field('listing_contact_label', $listing_id) : '';
    
    if (!$summary && has_excerpt($listing_id)) {
      $summary = get_the_excerpt($listing_id);
    }
    
    $status_labels = [
      'available' => __('Available', 'ibex-racing-child'),
      'reserved'  => __('Reserved', 'ibex-racing-child'),
      'sold'      => __('Sold', 'ibex-racing-child'),
    ];
    
    $status_label = $status_key && isset($status_labels[$status_key]) ? $status_labels[$status_key] : '';
    $contact_label = $contact_label ?: __('Inquire', 'ibex-racing-child');
    
    $mailto = '';
    if ($contact_email) {
      $subject = sprintf(
        '%s: %s',
        get_bloginfo('name'),
        get_the_title($listing_id)
      );
      $mailto = sprintf('mailto:%s?subject=%s', antispambot($contact_email), rawurlencode($subject));
    }
    
    $recent_listing = [
      'id'            => $listing_id,
      'title'         => get_the_title($listing_id),
      'permalink'     => get_permalink($listing_id),
      'thumbnail'     => get_the_post_thumbnail($listing_id, 'large', ['class' => 'ibex-event-card__image']),
      'price'         => $price ?: '',
      'status'        => $status_label,
      'status_key'    => $status_key ?: '',
      'summary'       => $summary ?: '',
      'contact_url'   => $mailto ?: get_permalink($listing_id),
      'contact_label' => $contact_label,
    ];
  }
  wp_reset_postdata();
}

$team_members = [];
$team_query   = new WP_Query([
  'post_type'      => 'team_member',
  'posts_per_page' => 4,
  'orderby'        => 'menu_order title',
  'order'          => 'ASC',
  'post_status'    => 'publish',
]);

if ($team_query->have_posts()) {
  while ($team_query->have_posts()) {
    $team_query->the_post();
    $team_id        = get_the_ID();
    $team_role      = '';
    $team_avatar    = '';
    $hero_image_obj = null;

    if (function_exists('get_field')) {
      $team_role = (string) get_field('team_member_title', $team_id) ?: '';
      $hero_image_obj = get_field('team_member_hero_image', $team_id);
    }

    if ($team_role === '' && has_excerpt()) {
      $team_role = wp_strip_all_tags(get_the_excerpt());
    }

    if (is_array($hero_image_obj) && !empty($hero_image_obj['ID'])) {
      $team_avatar = wp_get_attachment_image(
        (int) $hero_image_obj['ID'],
        'thumbnail',
        false,
        ['class' => 'ibex-home-team__avatar']
      );
    } elseif (is_array($hero_image_obj) && !empty($hero_image_obj['url'])) {
      $team_avatar = sprintf(
        '<img src="%1$s" alt="%2$s" class="ibex-home-team__avatar" loading="lazy" />',
        esc_url($hero_image_obj['url']),
        esc_attr($hero_image_obj['alt'] ?? get_the_title())
      );
    } else {
      $team_avatar = get_the_post_thumbnail(null, 'thumbnail', ['class' => 'ibex-home-team__avatar']);
    }

    $team_members[] = [
      'name'  => get_the_title(),
      'role'  => $team_role,
      'link'  => get_permalink(),
      'image' => $team_avatar,
    ];
  }
  wp_reset_postdata();
}

$sidebar_cards = apply_filters(
  'ibex_home_sidebar_cards',
  array_filter([
    $next_event ? [
      'type'    => 'event',
      'title'   => __('Our Events', 'ibex-racing-child'),
      'excerpt' => __('Stay up to date with our upcoming race events and experiences.', 'ibex-racing-child'),
      'link'    => get_post_type_archive_link('race_event'),
      'cta'     => __('All Events', 'ibex-racing-child'),
      'event'   => $next_event,
    ] : null,
    $recent_listing ? [
      'type'    => 'listing',
      'title'   => __('IBEX Gear', 'ibex-racing-child'),
      'excerpt' => __('Explore our latest track-ready inventory and hospitality packages.', 'ibex-racing-child'),
      'link'    => get_post_type_archive_link('listing'),
      'cta'     => __('View All Listings', 'ibex-racing-child'),
      'listing' => $recent_listing,
    ] : null,
    $team_members ? [
      'type'        => 'team',
      'title'       => __('Meet The Team', 'ibex-racing-child'),
      'excerpt'     => __('Drivers, crew, and support staff powering the IBEX Racing program.', 'ibex-racing-child'),
      'link'        => get_post_type_archive_link('team_member'),
      'team_members'=> $team_members,
      'cta'         => __('Full Roster', 'ibex-racing-child'),
    ] : null,
  ])
);
?>

<main id="primary" class="site-main ibex-home">
  <section class="ibex-home-hero" style="<?php echo $hero_background_url ? 'background-image: url(' . esc_url($hero_background_url) . ');' : ''; ?>">
    <div class="ibex-home-hero__overlay"></div>
    <div class="ibex-home-hero__content">
      <span class="ibex-home-hero__eyebrow"><?php esc_html_e('Motorsport Hospitality & Competition', 'ibex-racing-child'); ?></span>
      <h1 class="ibex-home-hero__title"><?php echo esc_html($hero_title); ?></h1>
      <p class="ibex-home-hero__subtitle"><?php echo esc_html($hero_subtitle ?: __('Track experiences and winning pedigree for friends, family, and guests.', 'ibex-racing-child')); ?></p>
      <div class="ibex-home-hero__cta-group">
        <?php if (!empty($hero_primary_cta['url'])) : ?>
          <a class="ibex-home-hero__cta ibex-home-hero__cta--primary" href="<?php echo esc_url($hero_primary_cta['url']); ?>">
            <?php echo esc_html($hero_primary_cta['label'] ?: __('View Events', 'ibex-racing-child')); ?>
          </a>
        <?php endif; ?>
        <?php if (!empty($hero_secondary_cta['url'])) : ?>
          <a class="ibex-home-hero__cta ibex-home-hero__cta--outline" href="<?php echo esc_url($hero_secondary_cta['url']); ?>">
            <?php echo esc_html($hero_secondary_cta['label'] ?: __('Connect With Us', 'ibex-racing-child')); ?>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <div class="ibex-home-layout">
    <section class="ibex-home-primary">
      <div class="ibex-home-primary__header">
        <h2><?php esc_html_e('Photo Gallery', 'ibex-racing-child'); ?></h2>
        <a class="ibex-home-primary__archive-link" href="<?php echo esc_url(get_post_type_archive_link('media_gallery')); ?>">
          <?php esc_html_e('All Galleries', 'ibex-racing-child'); ?>
        </a>
      </div>

      <?php if ($recent_galleries) : ?>
        <div class="ibex-event-grid ibex-event-grid--feature">
          <?php foreach ($recent_galleries as $gallery) : ?>
            <article class="ibex-event-card ibex-event-card--feature">
              <a class="ibex-event-card__media" href="<?php echo esc_url($gallery['permalink']); ?>">
                <?php if ($gallery['cover_src']) : ?>
                  <img src="<?php echo esc_url($gallery['cover_src']); ?>" alt="<?php echo esc_attr($gallery['title']); ?>" class="ibex-event-card__image" loading="lazy" />
                <?php else : ?>
                  <div class="ibex-event-card__placeholder" aria-hidden="true"></div>
                <?php endif; ?>
              </a>
              <div class="ibex-event-card__content">
                <div class="ibex-event-card__meta">
                  <?php if ($gallery['date']) : ?>
                    <span class="ibex-event-card__date"><?php echo esc_html($gallery['date']); ?></span>
                  <?php endif; ?>
                  <?php if ($gallery['location']) : ?>
                    <span class="ibex-event-card__location"><?php echo esc_html($gallery['location']); ?></span>
                  <?php endif; ?>
                </div>
                <h3 class="ibex-event-card__title">
                  <a href="<?php echo esc_url($gallery['permalink']); ?>"><?php echo esc_html($gallery['title']); ?></a>
                </h3>
                <?php if ($gallery['overview']) : ?>
                  <p class="ibex-event-card__summary"><?php echo wp_kses_post(wp_trim_words($gallery['overview'], 30, '&hellip;')); ?></p>
                <?php endif; ?>
                <div class="ibex-event-card__actions">
                  <a class="ibex-event-card__link" href="<?php echo esc_url($gallery['permalink']); ?>">
                    <?php esc_html_e('View Gallery', 'ibex-racing-child'); ?>
                  </a>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else : ?>
        <p class="ibex-home-primary__empty"><?php esc_html_e('Our media galleries are being curated. Check back soon for photos and video highlights.', 'ibex-racing-child'); ?></p>
      <?php endif; ?>
    </section>

    <?php if ($sidebar_cards) : ?>
      <aside class="ibex-home-sidebar" aria-label="<?php esc_attr_e('Featured content', 'ibex-racing-child'); ?>">
        <?php foreach ($sidebar_cards as $card) : ?>
          <?php if ($card['type'] === 'event' && !empty($card['event'])) : ?>
            <?php
              $event = $card['event'];
              $permalink     = get_permalink($event['id']);
              $title         = get_the_title($event['id']);
              $thumbnail     = get_the_post_thumbnail($event['id'], 'large', ['class' => 'ibex-home-sidebar-card__image']);
              $date_label    = $format_event_dates($event['start'], $event['end']);
              $location      = $event['location'] ?: '';
              $summary       = $event['summary'];
              $registration  = $event['reg_url'];
              $registration_label = $event['reg_label'];
            ?>
            <article class="ibex-home-sidebar-card ibex-home-sidebar-card--event ibex-home-sidebar-card--full-width">
              <div class="ibex-home-sidebar-card__content">
                <div class="ibex-home-sidebar-card__header">
                  <h3 class="ibex-home-sidebar-card__title">
                    <a href="<?php echo esc_url($card['link']); ?>"><?php echo esc_html($card['title']); ?></a>
                  </h3>
                  <?php if (!empty($card['cta'])) : ?>
                    <a class="ibex-home-sidebar-card__archive-link" href="<?php echo esc_url($card['link']); ?>">
                      <?php echo esc_html($card['cta']); ?>
                    </a>
                  <?php endif; ?>
                </div>
                <?php if (!empty($card['excerpt'])) : ?>
                  <p class="ibex-home-sidebar-card__excerpt"><?php echo wp_kses_post($card['excerpt']); ?></p>
                <?php endif; ?>
                
                <div class="ibex-event-card ibex-event-card--feature ibex-event-card--full-width">
                  <?php if ($thumbnail) : ?>
                    <a class="ibex-event-card__media" href="<?php echo esc_url($permalink); ?>">
                      <?php echo $thumbnail; ?>
                    </a>
                  <?php endif; ?>
                  <div class="ibex-event-card__content">
                    <div class="ibex-event-card__meta">
                      <?php if ($date_label) : ?>
                        <span class="ibex-event-card__date"><?php echo esc_html($date_label); ?></span>
                      <?php endif; ?>
                      <?php if ($location) : ?>
                        <span class="ibex-event-card__location"><?php echo esc_html($location); ?></span>
                      <?php endif; ?>
                    </div>
                    <h4 class="ibex-event-card__title">
                      <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
                    </h4>
                    <?php if ($summary) : ?>
                      <p class="ibex-event-card__summary"><?php echo wp_kses_post($summary); ?></p>
                    <?php endif; ?>
                    <div class="ibex-event-card__actions">
                      <a class="ibex-event-card__link" href="<?php echo esc_url($permalink); ?>">
                        <?php esc_html_e('View Event', 'ibex-racing-child'); ?>
                      </a>
                      <?php if ($registration) : ?>
                        <a class="ibex-event-card__cta" href="<?php echo esc_url($registration); ?>" target="_blank" rel="noopener">
                          <?php echo esc_html($registration_label); ?>
                        </a>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            </article>
          <?php elseif ($card['type'] === 'listing' && !empty($card['listing'])) : ?>
            <?php
              $listing = $card['listing'];
              $permalink     = $listing['permalink'];
              $title         = $listing['title'];
              $thumbnail     = $listing['thumbnail'];
              $price         = $listing['price'];
              $status        = $listing['status'];
              $status_key    = $listing['status_key'];
              $summary       = $listing['summary'];
              $contact_url   = $listing['contact_url'];
              $contact_label = $listing['contact_label'];
            ?>
            <article class="ibex-home-sidebar-card ibex-home-sidebar-card--listing ibex-home-sidebar-card--full-width">
              <div class="ibex-home-sidebar-card__content">
                <div class="ibex-home-sidebar-card__header">
                  <h3 class="ibex-home-sidebar-card__title">
                    <a href="<?php echo esc_url($card['link']); ?>"><?php echo esc_html($card['title']); ?></a>
                  </h3>
                  <?php if (!empty($card['cta'])) : ?>
                    <a class="ibex-home-sidebar-card__archive-link" href="<?php echo esc_url($card['link']); ?>">
                      <?php echo esc_html($card['cta']); ?>
                    </a>
                  <?php endif; ?>
                </div>
                <?php if (!empty($card['excerpt'])) : ?>
                  <p class="ibex-home-sidebar-card__excerpt"><?php echo wp_kses_post($card['excerpt']); ?></p>
                <?php endif; ?>
                
                <div class="ibex-event-card ibex-event-card--feature ibex-event-card--full-width">
                  <?php if ($thumbnail) : ?>
                    <a class="ibex-event-card__media" href="<?php echo esc_url($permalink); ?>">
                      <?php echo $thumbnail; ?>
                      <?php if ($status && $status_key) : ?>
                        <span class="ibex-event-card__badge ibex-event-card__badge--<?php echo esc_attr($status_key); ?>">
                          <?php echo esc_html($status); ?>
                        </span>
                      <?php endif; ?>
                    </a>
                  <?php endif; ?>
                  <div class="ibex-event-card__content">
                    <div class="ibex-event-card__meta">
                      <?php if ($price) : ?>
                        <span class="ibex-event-card__price"><?php echo esc_html($price); ?></span>
                      <?php endif; ?>
                      <?php if ($status) : ?>
                        <span class="ibex-event-card__status"><?php echo esc_html($status); ?></span>
                      <?php endif; ?>
                    </div>
                    <h4 class="ibex-event-card__title">
                      <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
                    </h4>
                    <?php if ($summary) : ?>
                      <p class="ibex-event-card__summary"><?php echo wp_kses_post(wp_trim_words($summary, 30, '&hellip;')); ?></p>
                    <?php endif; ?>
                    <div class="ibex-event-card__actions">
                      <a class="ibex-event-card__link" href="<?php echo esc_url($permalink); ?>">
                        <?php esc_html_e('View Listing', 'ibex-racing-child'); ?>
                      </a>
                      <?php if ($contact_url) : ?>
                        <a class="ibex-event-card__cta" href="<?php echo esc_url($contact_url); ?>" <?php echo strpos($contact_url, 'mailto:') === 0 ? '' : 'rel="noopener"'; ?>>
                          <?php echo esc_html($contact_label); ?>
                        </a>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            </article>
          <?php else : ?>
            <article class="ibex-home-sidebar-card ibex-home-sidebar-card--<?php echo esc_attr($card['type']); ?>">
              <?php if (!empty($card['image'])) : ?>
                <a class="ibex-home-sidebar-card__media" href="<?php echo esc_url($card['link']); ?>">
                  <?php echo $card['image']; ?>
                </a>
              <?php endif; ?>
              <div class="ibex-home-sidebar-card__content">
                <h3 class="ibex-home-sidebar-card__title">
                  <a href="<?php echo esc_url($card['link']); ?>"><?php echo esc_html($card['title']); ?></a>
                </h3>
                <?php if (!empty($card['excerpt'])) : ?>
                  <p class="ibex-home-sidebar-card__excerpt"><?php echo wp_kses_post($card['excerpt']); ?></p>
                <?php endif; ?>

                <?php if (!empty($card['team_members'])) : ?>
                  <ul class="ibex-home-team">
                    <?php foreach ($card['team_members'] as $member) : ?>
                      <li class="ibex-home-team__item">
                        <a class="ibex-home-team__profile" href="<?php echo esc_url($member['link']); ?>">
                          <?php if (!empty($member['image'])) : ?>
                            <?php echo $member['image']; ?>
                          <?php else : ?>
                            <span class="ibex-home-team__avatar ibex-home-team__avatar--placeholder" aria-hidden="true"></span>
                          <?php endif; ?>
                          <span class="ibex-home-team__name"><?php echo esc_html($member['name']); ?></span>
                          <?php if (!empty($member['role'])) : ?>
                            <span class="ibex-home-team__role"><?php echo esc_html($member['role']); ?></span>
                          <?php endif; ?>
                        </a>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>

                <?php if (!empty($card['cta'])) : ?>
                  <a class="ibex-home-sidebar-card__cta" href="<?php echo esc_url($card['link']); ?>">
                    <?php echo esc_html($card['cta']); ?>
                  </a>
                <?php endif; ?>
              </div>
            </article>
          <?php endif; ?>
        <?php endforeach; ?>
      </aside>
    <?php endif; ?>
  </div>
</main>

<?php
get_footer();

