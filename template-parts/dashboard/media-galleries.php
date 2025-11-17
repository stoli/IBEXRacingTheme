<?php
/**
 * Dashboard view for managing media galleries.
 *
 * @package IbexRacingChild
 */

if (!defined('ABSPATH')) {
  exit;
}

$settings = isset($args['settings']) && is_array($args['settings']) ? $args['settings'] : [];

if (!current_user_can('edit_media_galleries')) {
  wp_die(
    esc_html__('You do not have permission to access media galleries.', 'ibex-racing-child'),
    esc_html__('Forbidden', 'ibex-racing-child'),
    ['response' => 403]
  );
}

$form_available = function_exists('acf_form');
$current_user   = wp_get_current_user();
$can_manage_all = current_user_can('manage_options') || current_user_can('edit_others_media_galleries');

$dashboard_base_url = get_permalink();
$dashboard_base_url = remove_query_arg(['gallery_id', 'mode', 'gallery_submitted', 'event_id'], $dashboard_base_url);

$requested_gallery_id = isset($_GET['gallery_id']) ? (int) $_GET['gallery_id'] : 0;
$requested_mode       = isset($_GET['mode']) ? sanitize_key((string) $_GET['mode']) : '';
$related_event_id     = isset($_GET['event_id']) ? (int) $_GET['event_id'] : 0;

$view_mode    = 'index';
$gallery_post = null;

if ($requested_gallery_id > 0) {
  $candidate = get_post($requested_gallery_id);
  if ($candidate && $candidate->post_type === 'media_gallery') {
    if ((int) $candidate->post_author !== $current_user->ID && !$can_manage_all) {
      wp_die(
        esc_html__('You do not have permission to edit this gallery.', 'ibex-racing-child'),
        esc_html__('Forbidden', 'ibex-racing-child'),
        ['response' => 403]
      );
    }
    $gallery_post = $candidate;
    $view_mode    = 'edit';
  } else {
    wp_die(
      esc_html__('The requested gallery could not be found.', 'ibex-racing-child'),
      esc_html__('Not Found', 'ibex-racing-child'),
      ['response' => 404]
    );
  }
} elseif ($requested_mode === 'create') {
  $view_mode = 'create';
}

$submission_notice = '';
if (!empty($_GET['gallery_submitted'])) {
  $submission_notice = esc_html__('Gallery saved successfully.', 'ibex-racing-child');
  if (!$gallery_post && $requested_gallery_id) {
    $candidate = get_post($requested_gallery_id);
    if ($candidate && $candidate->post_type === 'media_gallery') {
      $gallery_post = $candidate;
      $view_mode    = 'edit';
    }
  }
}

$list_query_args = [
  'post_type'      => 'media_gallery',
  'post_status'    => $can_manage_all ? ['publish', 'draft', 'pending', 'private'] : ['publish', 'draft', 'pending'],
  'posts_per_page' => 50,
  'orderby'        => 'modified',
  'order'          => 'DESC',
  'no_found_rows'  => true,
];

if (!$can_manage_all) {
  $list_query_args['author'] = $current_user->ID;
}

$existing_galleries = get_posts($list_query_args);

$form_id        = $view_mode === 'edit' ? 'ibex-edit-media-gallery-form' : 'ibex-new-media-gallery-form';
$submit_label   = $view_mode === 'edit' ? esc_html__('Update Gallery', 'ibex-racing-child') : esc_html__('Create Gallery', 'ibex-racing-child');
$return_url     = add_query_arg(
  [
    'gallery_submitted' => 1,
    'gallery_id'        => '%post_id%',
  ],
  $dashboard_base_url
);

$acf_form_args = [
  'id'                 => $form_id,
  'post_id'            => $view_mode === 'edit' && $gallery_post ? $gallery_post->ID : 'new_post',
  'new_post'           => [
    'post_type'   => 'media_gallery',
    'post_status' => 'publish',
    'post_author' => $current_user->ID,
  ],
  'return'             => $return_url,
  'field_groups'       => ['group_ibex_media_gallery_details'],
  'post_title'         => true,
  'post_content'       => true,
  'post_excerpt'       => true,
  'submit_value'       => $submit_label,
  'html_submit_button' => sprintf(
    '<button type="submit" class="ibex-button ibex-dashboard__submit">%s</button>',
    esc_html($submit_label)
  ),
  'uploader'           => 'wp',
  'html_before_fields' => '<div class="ibex-dashboard__acf">',
  'html_after_fields'  => '</div>',
  'html_updated_message' => '<div class="ibex-dashboard__notice ibex-dashboard__notice--success">' .
    esc_html__('Gallery saved successfully.', 'ibex-racing-child') .
    '</div>',
];

if ($view_mode !== 'edit' && $related_event_id) {
  $linked_event = get_post($related_event_id);
  if ($linked_event && $linked_event->post_type === 'race_event') {
    add_filter('acf/load_value/name=media_gallery_related_event', static function ($value) use ($related_event_id) {
      return $related_event_id;
    });
    add_filter('acf/load_value/name=media_gallery_start_date', static function ($value) use ($related_event_id) {
      $event_value = get_field('event_start_date', $related_event_id);
      return $event_value ?: $value;
    });
    add_filter('acf/load_value/name=media_gallery_end_date', static function ($value) use ($related_event_id) {
      $event_value = get_field('event_end_date', $related_event_id);
      return $event_value ?: $value;
    });
    add_filter('acf/load_value/name=media_gallery_location', static function ($value) use ($related_event_id) {
      $event_value = get_field('event_location', $related_event_id);
      return $event_value ?: $value;
    });
  }
}

$gallery_title   = $gallery_post ? get_the_title($gallery_post) : '';
$gallery_status  = $gallery_post ? get_post_status_object($gallery_post->post_status) : null;
$gallery_updated = $gallery_post ? get_post_modified_time('U', false, $gallery_post) : 0;
$gallery_assets  = 0;

if ($gallery_post && function_exists('get_field')) {
  $items = get_field('media_gallery_items', $gallery_post->ID);
  if (is_array($items)) {
    $gallery_assets = count($items);
  }
}

$edit_link = $gallery_post ? add_query_arg('gallery_id', $gallery_post->ID, $dashboard_base_url) : '';
$create_link = add_query_arg('mode', 'create', $dashboard_base_url);
$view_link   = $gallery_post ? get_permalink($gallery_post) : '';

?>
<div class="ibex-dashboard__panels">
  <aside class="ibex-dashboard__panel ibex-dashboard__panel--sidebar" aria-label="<?php esc_attr_e('Media galleries', 'ibex-racing-child'); ?>">
    <div class="ibex-dashboard__panel-header">
      <h2 class="ibex-dashboard__panel-title"><?php esc_html_e('Galleries', 'ibex-racing-child'); ?></h2>
      <a class="ibex-button ibex-button--outline ibex-dashboard__panel-action" href="<?php echo esc_url($create_link); ?>">
        <?php esc_html_e('New Gallery', 'ibex-racing-child'); ?>
      </a>
    </div>

    <?php if ($existing_galleries) : ?>
      <ul class="ibex-dashboard__list">
        <?php foreach ($existing_galleries as $item) : ?>
          <?php
          $item_id       = (int) $item->ID;
          $is_active     = $view_mode === 'edit' && $gallery_post && $gallery_post->ID === $item_id;
          $status_object = get_post_status_object($item->post_status);
          $status_label  = $status_object ? $status_object->label : ucfirst($item->post_status);
          $modified_time = get_post_modified_time('U', false, $item);
          $modified_diff = $modified_time ? human_time_diff($modified_time, current_time('timestamp')) : '';

          $item_media    = function_exists('get_field') ? get_field('media_gallery_items', $item_id) : [];
          $item_assets   = is_array($item_media) ? count($item_media) : 0;
          $item_assets_label = sprintf(
            esc_html(_n('%d asset', '%d assets', $item_assets, 'ibex-racing-child')),
            $item_assets
          );

          $item_link     = add_query_arg('gallery_id', $item_id, $dashboard_base_url);
          ?>
          <li class="ibex-dashboard__list-item<?php echo $is_active ? ' is-active' : ''; ?>">
            <a class="ibex-dashboard__list-link" href="<?php echo esc_url($item_link); ?>">
              <span class="ibex-dashboard__list-heading">
                <span class="ibex-dashboard__list-title"><?php echo esc_html(get_the_title($item)); ?></span>
                <span class="ibex-dashboard__list-status status-<?php echo esc_attr($item->post_status); ?>">
                  <?php echo esc_html($status_label); ?>
                </span>
              </span>
              <span class="ibex-dashboard__list-meta">
                <span><?php echo esc_html($item_assets_label); ?></span>
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
        <?php esc_html_e('No galleries yet. Create a new gallery to get started.', 'ibex-racing-child'); ?>
      </p>
    <?php endif; ?>
  </aside>

  <section class="ibex-dashboard__panel ibex-dashboard__panel--primary">
    <?php if (!$form_available) : ?>
      <div class="ibex-dashboard__empty-state">
        <h2><?php esc_html_e('ACF form unavailable', 'ibex-racing-child'); ?></h2>
        <p><?php esc_html_e('Advanced Custom Fields is required to manage media galleries.', 'ibex-racing-child'); ?></p>
      </div>
    <?php elseif ($view_mode === 'index') : ?>
      <div class="ibex-dashboard__empty-state">
        <h2><?php esc_html_e('Select a gallery to begin', 'ibex-racing-child'); ?></h2>
        <p><?php esc_html_e('Choose a gallery from the list or start a new one to upload media assets.', 'ibex-racing-child'); ?></p>
        <a class="ibex-button ibex-dashboard__cta" href="<?php echo esc_url($create_link); ?>">
          <?php esc_html_e('Create Gallery', 'ibex-racing-child'); ?>
        </a>
      </div>
    <?php else : ?>
      <div class="ibex-dashboard__editor">
        <div class="ibex-dashboard__editor-bar">
          <a class="ibex-dashboard__backlink" href="<?php echo esc_url($dashboard_base_url); ?>">
            <?php esc_html_e('← All Galleries', 'ibex-racing-child'); ?>
          </a>
          <?php if ($view_mode === 'edit' && $view_link) : ?>
            <a class="ibex-button ibex-button--outline ibex-dashboard__view-link" href="<?php echo esc_url($view_link); ?>" target="_blank" rel="noopener">
              <?php esc_html_e('View Live', 'ibex-racing-child'); ?>
            </a>
          <?php endif; ?>
        </div>

        <header class="ibex-dashboard__editor-header">
          <h2 class="ibex-dashboard__editor-title">
            <?php
            if ($view_mode === 'edit' && $gallery_title) {
              printf(
                /* translators: %s: Gallery title */
                esc_html__('Editing: %s', 'ibex-racing-child'),
                esc_html($gallery_title)
              );
            } else {
              esc_html_e('Create Media Gallery', 'ibex-racing-child');
            }
            ?>
          </h2>

          <div class="ibex-dashboard__editor-meta">
            <?php if ($view_mode === 'edit' && $gallery_status) : ?>
              <span><?php echo esc_html(sprintf(esc_html__('Status: %s', 'ibex-racing-child'), $gallery_status->label)); ?></span>
            <?php endif; ?>

            <?php if ($view_mode === 'edit' && $gallery_assets) : ?>
              <span><?php echo esc_html(sprintf(esc_html__('%d assets', 'ibex-racing-child'), $gallery_assets)); ?></span>
            <?php endif; ?>

            <?php if ($view_mode === 'edit' && $gallery_updated) : ?>
              <span>
                <?php
                printf(
                  esc_html__('Updated %s ago', 'ibex-racing-child'),
                  esc_html(human_time_diff($gallery_updated, current_time('timestamp')))
                );
                ?>
              </span>
            <?php endif; ?>
          </div>
        </header>

        <?php if ($submission_notice) : ?>
          <div class="ibex-dashboard__notice ibex-dashboard__notice--success">
            <?php echo esc_html($submission_notice); ?>
          </div>
        <?php endif; ?>

        <div class="ibex-dashboard__form">
          <?php acf_form($acf_form_args); ?>
        </div>
      </div>
    <?php endif; ?>
  </section>
</div>

