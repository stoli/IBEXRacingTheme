<?php
/**
 * Archive template for IBEX Racing Events.
 *
 * @package ibex-racing-child
 */

get_header();

$archive_intro = '';
$intro_page = apply_filters('ibex_events_archive_intro_page', get_page_by_path('events'));

if ($intro_page instanceof WP_Post) {
  $raw_intro = $intro_page->post_excerpt ?: $intro_page->post_content;
  if ($raw_intro) {
    $archive_intro = apply_filters(
      'ibex_events_archive_intro_content',
      wp_kses_post(wpautop($raw_intro))
    );
  }
}

$today = current_time('Y-m-d');
$events_query = new WP_Query([
  'post_type'      => 'race_event',
  'posts_per_page' => -1,
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
    $reg_label  = function_exists('get_field') ? get_field('event_registration_label', $event_id) : '';

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

    $event_payload = [
      'id'         => $event_id,
      'start'      => $start_date,
      'end'        => $end_date,
      'location'   => $location,
      'summary'    => $summary,
      'reg_url'    => $reg_url,
      'reg_label'  => $reg_label ?: __('Register Now', 'ibex-racing-child'),
    ];

    if ($is_upcoming) {
      $upcoming_events[] = $event_payload;
    } else {
      $past_events[] = $event_payload;
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

$archive_description = get_the_archive_description();
if (!$archive_description && $archive_intro) {
  $archive_description = $archive_intro;
}
if (!$archive_description) {
  $archive_description = '<p>' . esc_html__('Follow the IBEX Racing schedule for upcoming track events and hospitality experiences.', 'ibex-racing-child') . '</p>';
}

$media_archive = get_post_type_archive_link('media_gallery');
?>

<main id="primary" class="site-main ibex-events-archive">
  <header class="ibex-archive-hero ibex-archive-hero--events">
    <div class="ibex-archive-hero__overlay">
      <span class="ibex-archive-hero__eyebrow"><?php esc_html_e('Race Calendar', 'ibex-racing-child'); ?></span>
      <h1 class="ibex-archive-hero__title"><?php the_archive_title(); ?></h1>
      <div class="ibex-archive-hero__intro">
        <?php echo wp_kses_post($archive_description); ?>
      </div>
      <div class="ibex-archive-hero__cta">
        <?php if ($media_archive) : ?>
          <a class="ibex-button ibex-button--outline" href="<?php echo esc_url($media_archive); ?>">
            <?php esc_html_e('View Media Galleries', 'ibex-racing-child'); ?>
          </a>
        <?php endif; ?>
        <?php if (is_user_logged_in() && current_user_can('edit_race_events')) : ?>
          <?php
          $dashboard_url = ibex_get_page_link_by_template('page-event-dashboard.php');
          if ($dashboard_url) :
            ?>
            <a class="ibex-button" href="<?php echo esc_url($dashboard_url); ?>">
              <?php esc_html_e('Manage Events', 'ibex-racing-child'); ?>
            </a>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <section class="ibex-archive-section ibex-event-section ibex-event-section--upcoming">
    <div class="ibex-event-section__header">
      <h2><?php esc_html_e('Upcoming Events', 'ibex-racing-child'); ?></h2>
      <?php if (!$upcoming_events) : ?>
        <p class="ibex-event-section__empty">
          <?php esc_html_e('We are finalizing our upcoming schedule. Check back soon!', 'ibex-racing-child'); ?>
        </p>
      <?php endif; ?>
    </div>
    <?php if ($upcoming_events) : ?>
      <div class="ibex-event-grid">
        <?php foreach ($upcoming_events as $event) : ?>
          <?php
            $permalink     = get_permalink($event['id']);
            $title         = get_the_title($event['id']);
            $thumbnail     = get_the_post_thumbnail($event['id'], 'large', ['class' => 'ibex-event-card__image']);
            $date_label    = $format_event_dates($event['start'], $event['end']);
            $location      = $event['location'] ?: '';
            $summary       = $event['summary'];
            $registration  = $event['reg_url'];
            $registration_label = $event['reg_label'];
          ?>
          <article class="ibex-event-card ibex-event-card--upcoming">
            <a class="ibex-event-card__media" href="<?php echo esc_url($permalink); ?>">
              <?php if ($thumbnail) : ?>
                <?php echo $thumbnail; ?>
              <?php else : ?>
                <div class="ibex-event-card__placeholder" aria-hidden="true"></div>
              <?php endif; ?>
            </a>
            <div class="ibex-event-card__content">
              <div class="ibex-event-card__meta">
                <?php if ($date_label) : ?>
                  <span class="ibex-event-card__date"><?php echo esc_html($date_label); ?></span>
                <?php endif; ?>
                <?php if ($location) : ?>
                  <span class="ibex-event-card__location"><?php echo esc_html($location); ?></span>
                <?php endif; ?>
              </div>
              <h3 class="ibex-event-card__title">
                <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
              </h3>
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
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="ibex-archive-section ibex-event-section ibex-event-section--past">
    <div class="ibex-event-section__header">
      <h2><?php esc_html_e('Past Events', 'ibex-racing-child'); ?></h2>
      <?php if (!$past_events) : ?>
        <p class="ibex-event-section__empty">
          <?php esc_html_e('No past races yet—but we’re just getting started.', 'ibex-racing-child'); ?>
        </p>
      <?php endif; ?>
    </div>
    <?php if ($past_events) : ?>
      <div class="ibex-event-grid ibex-event-grid--compact">
        <?php foreach ($past_events as $event) : ?>
          <?php
            $permalink     = get_permalink($event['id']);
            $title         = get_the_title($event['id']);
            $thumbnail     = get_the_post_thumbnail($event['id'], 'medium_large', ['class' => 'ibex-event-card__image']);
            $date_label    = $format_event_dates($event['start'], $event['end']);
            $location      = $event['location'] ?: '';
            $summary       = $event['summary'];
          ?>
          <article class="ibex-event-card ibex-event-card--past">
            <a class="ibex-event-card__media" href="<?php echo esc_url($permalink); ?>">
              <?php if ($thumbnail) : ?>
                <?php echo $thumbnail; ?>
              <?php else : ?>
                <div class="ibex-event-card__placeholder" aria-hidden="true"></div>
              <?php endif; ?>
            </a>
            <div class="ibex-event-card__content">
              <div class="ibex-event-card__meta">
                <?php if ($date_label) : ?>
                  <span class="ibex-event-card__date"><?php echo esc_html($date_label); ?></span>
                <?php endif; ?>
                <?php if ($location) : ?>
                  <span class="ibex-event-card__location"><?php echo esc_html($location); ?></span>
                <?php endif; ?>
              </div>
              <h3 class="ibex-event-card__title">
                <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
              </h3>
              <?php if ($summary) : ?>
                <p class="ibex-event-card__summary"><?php echo wp_kses_post($summary); ?></p>
              <?php endif; ?>
              <div class="ibex-event-card__actions">
                <a class="ibex-event-card__link" href="<?php echo esc_url($permalink); ?>">
                  <?php esc_html_e('View Recap', 'ibex-racing-child'); ?>
                </a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</main>

<?php
get_footer();

