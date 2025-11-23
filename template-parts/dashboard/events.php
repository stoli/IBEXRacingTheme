<?php
/**
 * Dashboard view for managing events.
 *
 * @package IbexRacingChild
 */

if (!defined('ABSPATH')) {
  exit;
}

$settings = isset($args['settings']) && is_array($args['settings']) ? $args['settings'] : [];

if (!current_user_can('edit_race_events')) {
  wp_die(
    esc_html__('You do not have permission to access events.', 'ibex-racing-child'),
    esc_html__('Forbidden', 'ibex-racing-child'),
    ['response' => 403]
  );
}

$form_available = function_exists('acf_form');
$current_user   = wp_get_current_user();
$can_manage_all = current_user_can('manage_options') || current_user_can('edit_others_race_events');

$dashboard_base_url = get_permalink();
$dashboard_base_url = remove_query_arg(['event_id', 'mode', 'event_submitted'], $dashboard_base_url);

$requested_event_id = isset($_GET['event_id']) ? (int) $_GET['event_id'] : 0;
$requested_mode     = isset($_GET['mode']) ? sanitize_key((string) $_GET['mode']) : '';

$view_mode  = 'index';
$event_post = null;

if ($requested_event_id > 0) {
  $candidate = get_post($requested_event_id);
  if ($candidate && $candidate->post_type === 'race_event') {
    if ((int) $candidate->post_author !== $current_user->ID && !$can_manage_all) {
      wp_die(
        esc_html__('You do not have permission to edit this event.', 'ibex-racing-child'),
        esc_html__('Forbidden', 'ibex-racing-child'),
        ['response' => 403]
      );
    }
    $event_post = $candidate;
    $view_mode  = 'edit';
  } else {
    wp_die(
      esc_html__('The requested event could not be found.', 'ibex-racing-child'),
      esc_html__('Not Found', 'ibex-racing-child'),
      ['response' => 404]
    );
  }
} elseif ($requested_mode === 'create') {
  $view_mode = 'create';
}

$submission_notice = '';
if (!empty($_GET['event_submitted'])) {
  $submitted_id = (int) $_GET['event_submitted'];
  $submitted_post = get_post($submitted_id);
  
  if ($submitted_post && $submitted_post->post_type === 'race_event') {
    $submission_notice = sprintf(
      /* translators: %s: event title */
      esc_html__('Event "%s" has been saved successfully.', 'ibex-racing-child'),
      esc_html($submitted_post->post_title)
    );
  }
}

$query_args = [
  'post_type'      => 'race_event',
  'posts_per_page' => -1,
  'post_status'    => 'any',
  'orderby'        => 'meta_value',
  'meta_key'       => 'event_start_date',
  'meta_type'      => 'DATE', // Ensure proper date sorting
  'order'          => 'ASC', // Ascending: earliest events first
];

if (!$can_manage_all) {
  $query_args['author'] = $current_user->ID;
}

$events_query = new WP_Query($query_args);
$user_events  = $events_query->posts;

$create_link = add_query_arg(['mode' => 'create'], $dashboard_base_url);
$view_link   = $event_post ? get_permalink($event_post->ID) : '';

$form_config = [
  'post_id'      => $event_post ? $event_post->ID : 'new_post',
  'post_title'   => true,
  'post_content' => [
    'editor_settings' => [
      'media_buttons' => false,
      'textarea_rows' => 15,
      'teeny'         => false,
      'quicktags'     => true,
    ],
  ],
  'new_post'     => [
    'post_type'   => 'race_event',
    'post_status' => 'publish',
  ],
  'return'       => add_query_arg(['event_submitted' => '%post_id%'], $dashboard_base_url),
  'submit_value' => $view_mode === 'edit'
    ? __('Update Event', 'ibex-racing-child')
    : __('Create Event', 'ibex-racing-child'),
  'updated_message' => false,
  'html_submit_button' => '<button type="submit" class="acf-button button button-primary button-large ibex-button">%s</button>',
  'uploader'     => 'wp',
  'fields'       => [
    'event_start_date',
    'event_end_date',
    'event_location',
    'event_registration_url',
    'event_registration_label',
    'event_summary',
    'event_featured_image',
    'create_media_gallery',
  ],
  'field_groups' => ['group_ibex_event_details'],
];

if ($event_post) {
  $form_config['post_id'] = $event_post->ID;
}

// Check if a gallery already exists for this event
$existing_gallery = null;
$gallery_link = '';
if ($event_post && function_exists('ibex_get_event_gallery') && function_exists('ibex_get_page_link_by_template')) {
  $existing_gallery = ibex_get_event_gallery($event_post->ID, true);
  if ($existing_gallery) {
    // Hide the create_media_gallery field if gallery exists
    add_filter('acf/prepare_field/name=create_media_gallery', function($field) {
      return false; // Hide the field
    });
    
    // Get the gallery dashboard URL
    $gallery_dashboard_url = ibex_get_page_link_by_template('page-media-gallery-dashboard.php');
    if ($gallery_dashboard_url) {
      $gallery_link = add_query_arg(
        ['gallery_id' => $existing_gallery->ID],
        $gallery_dashboard_url
      );
    }
  }
}
?>

<div class="ibex-dashboard__panels">
  <aside class="ibex-dashboard__panel ibex-dashboard__panel--sidebar" aria-label="<?php esc_attr_e('Events', 'ibex-racing-child'); ?>">
    <div class="ibex-dashboard__panel-header">
      <h2 class="ibex-dashboard__panel-title"><?php esc_html_e('Events', 'ibex-racing-child'); ?></h2>
      <a class="ibex-button ibex-button--outline ibex-dashboard__panel-action" href="<?php echo esc_url($create_link); ?>">
        <?php esc_html_e('New Event', 'ibex-racing-child'); ?>
      </a>
    </div>

    <?php if ($user_events) : ?>
      <ul class="ibex-dashboard__list">
        <?php foreach ($user_events as $item) : ?>
          <?php
          $item_id       = (int) $item->ID;
          $item_url      = add_query_arg(['event_id' => $item_id], $dashboard_base_url);
          $is_active     = $event_post && $event_post->ID === $item_id;
          $status_object = get_post_status_object($item->post_status);
          $status_label  = $status_object ? $status_object->label : ucfirst($item->post_status);
          $modified_time = get_post_modified_time('U', false, $item);
          $modified_diff = $modified_time ? human_time_diff($modified_time, current_time('timestamp')) : '';

          $start_date = function_exists('get_field') ? get_field('event_start_date', $item_id) : '';
          $location = function_exists('get_field') ? get_field('event_location', $item_id) : '';
          
          $date_display = '';
          if ($start_date) {
            $date_display = date_i18n('M j, Y', strtotime($start_date));
          }
          ?>
          <li class="ibex-dashboard__list-item<?php echo $is_active ? ' is-active' : ''; ?>">
            <a class="ibex-dashboard__list-link" href="<?php echo esc_url($item_url); ?>">
              <span class="ibex-dashboard__list-heading">
                <span class="ibex-dashboard__list-title"><?php echo esc_html(get_the_title($item)); ?></span>
                <span class="ibex-dashboard__list-status status-<?php echo esc_attr($item->post_status); ?>">
                  <?php echo esc_html($status_label); ?>
                </span>
              </span>
              <span class="ibex-dashboard__list-meta">
                <?php if ($date_display) : ?>
                  <span><?php echo esc_html($date_display); ?></span>
                <?php endif; ?>
                <?php if ($location) : ?>
                  <span><?php echo esc_html($location); ?></span>
                <?php endif; ?>
                <?php if ($modified_diff) : ?>
                  <span><?php echo esc_html(sprintf(esc_html__('Updated %s ago', 'ibex-racing-child'), $modified_diff)); ?></span>
                <?php endif; ?>
              </span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else : ?>
      <p class="ibex-dashboard__empty">
        <?php esc_html_e('No events yet. Create a new event to get started.', 'ibex-racing-child'); ?>
      </p>
    <?php endif; ?>
  </aside>

  <section class="ibex-dashboard__panel ibex-dashboard__panel--primary">
    <?php if (!$form_available) : ?>
      <div class="ibex-dashboard__empty-state">
        <h2><?php esc_html_e('ACF form unavailable', 'ibex-racing-child'); ?></h2>
        <p><?php esc_html_e('Advanced Custom Fields is required to manage events.', 'ibex-racing-child'); ?></p>
      </div>
    <?php elseif ($view_mode === 'index') : ?>
      <div class="ibex-dashboard__empty-state">
        <h2><?php esc_html_e('Select an event to begin', 'ibex-racing-child'); ?></h2>
        <p><?php esc_html_e('Choose an event from the list or start a new one to edit details and settings.', 'ibex-racing-child'); ?></p>
        <a class="ibex-button ibex-dashboard__cta" href="<?php echo esc_url($create_link); ?>">
          <?php esc_html_e('Create Event', 'ibex-racing-child'); ?>
        </a>
      </div>
    <?php else : ?>
      <div class="ibex-dashboard__editor">
        <div class="ibex-dashboard__editor-bar">
          <a class="ibex-dashboard__backlink" href="<?php echo esc_url($dashboard_base_url); ?>">
            <?php esc_html_e('← All Events', 'ibex-racing-child'); ?>
          </a>
          <?php if ($view_mode === 'edit' && $view_link) : ?>
            <a class="ibex-button ibex-button--outline ibex-dashboard__view-link" href="<?php echo esc_url($view_link); ?>" target="_blank" rel="noopener">
              <?php esc_html_e('View Live', 'ibex-racing-child'); ?>
            </a>
          <?php endif; ?>
        </div>

        <header class="ibex-dashboard__editor-header">
          <h2>
            <?php
            if ($view_mode === 'edit') {
              printf(
                /* translators: %s: event title */
                esc_html__('Edit: %s', 'ibex-racing-child'),
                esc_html($event_post->post_title)
              );
            } else {
              esc_html_e('Create New Event', 'ibex-racing-child');
            }
            ?>
          </h2>
          <p class="ibex-dashboard__editor-intro">
            <?php
            if ($view_mode === 'edit') {
              esc_html_e('Update your event details, dates, location, and registration information below.', 'ibex-racing-child');
            } else {
              esc_html_e('Fill in the event details below. All events publish immediately once saved.', 'ibex-racing-child');
            }
            ?>
          </p>
        </header>

        <?php if ($submission_notice) : ?>
          <div class="ibex-dashboard__notice ibex-dashboard__notice--success">
            <?php echo esc_html($submission_notice); ?>
          </div>
        <?php endif; ?>

        <div class="ibex-dashboard__form">
          <?php acf_form($form_config); ?>
          
          <?php if ($view_mode === 'edit' && $event_post) : ?>
            <?php
            // Check if user can delete this event (admin or creator)
            $can_delete = current_user_can('manage_options') || (int) $event_post->post_author === $current_user->ID;
            if ($can_delete) :
            ?>
              <script type="text/template" class="ibex-delete-button-template">
                <button type="button" 
                        class="ibex-button ibex-button--danger ibex-delete-event-btn" 
                        data-event-id="<?php echo esc_attr($event_post->ID); ?>"
                        data-event-title="<?php echo esc_attr($event_post->post_title); ?>">
                  <?php esc_html_e('Delete Event', 'ibex-racing-child'); ?>
                </button>
              </script>
            <?php endif; ?>
          <?php endif; ?>
          
          <?php if ($existing_gallery && $gallery_link) : ?>
            <div class="ibex-dashboard__notice ibex-dashboard__notice--info" style="margin-top: 1.5rem;">
              <p style="margin: 0;">
                <?php
                printf(
                  /* translators: %1$s: gallery title, %2$s: link to gallery */
                  esc_html__('A media gallery already exists for this event: %1$s', 'ibex-racing-child'),
                  sprintf(
                    '<a href="%s">%s</a>',
                    esc_url($gallery_link),
                    esc_html($existing_gallery->post_title)
                  )
                );
                ?>
              </p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </section>
</div>

