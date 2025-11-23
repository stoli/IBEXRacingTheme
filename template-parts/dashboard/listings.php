<?php
/**
 * Dashboard view for managing For Sale listings.
 *
 * @package IbexRacingChild
 */

if (!defined('ABSPATH')) {
  exit;
}

$settings = isset($args['settings']) && is_array($args['settings']) ? $args['settings'] : [];

if (!current_user_can('edit_listings')) {
  wp_die(
    esc_html__('You do not have permission to access listings.', 'ibex-racing-child'),
    esc_html__('Forbidden', 'ibex-racing-child'),
    ['response' => 403]
  );
}

$form_available = function_exists('acf_form');
$current_user   = wp_get_current_user();
$can_manage_all = current_user_can('manage_options') || current_user_can('edit_others_listings');

$dashboard_base_url = get_permalink();
$dashboard_base_url = remove_query_arg(['listing_id', 'mode', 'listing_submitted'], $dashboard_base_url);

$requested_listing_id = isset($_GET['listing_id']) ? (int) $_GET['listing_id'] : 0;
$requested_mode       = isset($_GET['mode']) ? sanitize_key((string) $_GET['mode']) : '';

$view_mode    = 'index';
$listing_post = null;

if ($requested_listing_id > 0) {
  $candidate = get_post($requested_listing_id);
  if ($candidate && $candidate->post_type === 'listing') {
    if ((int) $candidate->post_author !== $current_user->ID && !$can_manage_all) {
      wp_die(
        esc_html__('You do not have permission to edit this listing.', 'ibex-racing-child'),
        esc_html__('Forbidden', 'ibex-racing-child'),
        ['response' => 403]
      );
    }
    $listing_post = $candidate;
    $view_mode    = 'edit';
  } else {
    wp_die(
      esc_html__('The requested listing could not be found.', 'ibex-racing-child'),
      esc_html__('Not Found', 'ibex-racing-child'),
      ['response' => 404]
    );
  }
} elseif ($requested_mode === 'create') {
  $view_mode = 'create';
}

$submission_notice = '';
if (!empty($_GET['listing_submitted'])) {
  $submitted_id = (int) $_GET['listing_submitted'];
  $submitted_post = get_post($submitted_id);
  
  if ($submitted_post && $submitted_post->post_type === 'listing') {
    $submission_notice = sprintf(
      /* translators: %s: listing title */
      esc_html__('Listing "%s" has been saved successfully.', 'ibex-racing-child'),
      esc_html($submitted_post->post_title)
    );
  }
}

$query_args = [
  'post_type'      => 'listing',
  'posts_per_page' => -1,
  'post_status'    => 'any',
  'orderby'        => 'modified',
  'order'          => 'DESC',
];

if (!$can_manage_all) {
  $query_args['author'] = $current_user->ID;
}

$listings_query = new WP_Query($query_args);
$user_listings  = $listings_query->posts;

$create_link = add_query_arg(['mode' => 'create'], $dashboard_base_url);
$view_link   = $listing_post ? get_permalink($listing_post->ID) : '';

$form_config = [
  'post_id'      => $listing_post ? $listing_post->ID : 'new_post',
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
    'post_type'   => 'listing',
    'post_status' => 'publish',
  ],
  'return'       => add_query_arg(['listing_submitted' => '%post_id%'], $dashboard_base_url),
  'submit_value' => $view_mode === 'edit'
    ? __('Update Listing', 'ibex-racing-child')
    : __('Create Listing', 'ibex-racing-child'),
  'updated_message' => false,
  'html_submit_button' => '<button type="submit" class="acf-button button button-primary button-large ibex-button">%s</button>',
  'uploader'     => 'wp',
  'fields'       => [
    'listing_price',
    'listing_status',
    'listing_summary',
    'listing_gallery',
    'listing_contact_email',
    'listing_contact_label',
  ],
  'field_groups' => ['group_ibex_listing_details'],
];

if ($listing_post) {
  $form_config['post_id'] = $listing_post->ID;
}
?>

<div class="ibex-dashboard__panels">
  <aside class="ibex-dashboard__panel ibex-dashboard__panel--sidebar" aria-label="<?php esc_attr_e('For Sale listings', 'ibex-racing-child'); ?>">
    <div class="ibex-dashboard__panel-header">
      <h2 class="ibex-dashboard__panel-title"><?php esc_html_e('Listings', 'ibex-racing-child'); ?></h2>
      <a class="ibex-button ibex-button--outline ibex-dashboard__panel-action" href="<?php echo esc_url($create_link); ?>">
        <?php esc_html_e('New Listing', 'ibex-racing-child'); ?>
      </a>
    </div>

    <?php if ($user_listings) : ?>
      <ul class="ibex-dashboard__list">
        <?php foreach ($user_listings as $item) : ?>
          <?php
          $item_id       = (int) $item->ID;
          $item_url      = add_query_arg(['listing_id' => $item_id], $dashboard_base_url);
          $is_active     = $listing_post && $listing_post->ID === $item_id;
          $status_object = get_post_status_object($item->post_status);
          $status_label  = $status_object ? $status_object->label : ucfirst($item->post_status);
          $modified_time = get_post_modified_time('U', false, $item);
          $modified_diff = $modified_time ? human_time_diff($modified_time, current_time('timestamp')) : '';

          $listing_status = function_exists('get_field') ? get_field('listing_status', $item_id) : '';
          $availability_label = '';
          
          if ($listing_status === 'sold') {
            $availability_label = __('Sold', 'ibex-racing-child');
          } elseif ($listing_status === 'reserved') {
            $availability_label = __('Reserved', 'ibex-racing-child');
          } elseif ($listing_status === 'available') {
            $availability_label = __('Available', 'ibex-racing-child');
          }

          $price = function_exists('get_field') ? get_field('listing_price', $item_id) : '';
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
                <?php if ($availability_label) : ?>
                  <span><?php echo esc_html($availability_label); ?></span>
                <?php endif; ?>
                <?php if ($price) : ?>
                  <span><?php echo esc_html($price); ?></span>
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
        <?php esc_html_e('No listings yet. Create a new listing to get started.', 'ibex-racing-child'); ?>
      </p>
    <?php endif; ?>
  </aside>

  <section class="ibex-dashboard__panel ibex-dashboard__panel--primary">
    <?php if (!$form_available) : ?>
      <div class="ibex-dashboard__empty-state">
        <h2><?php esc_html_e('ACF form unavailable', 'ibex-racing-child'); ?></h2>
        <p><?php esc_html_e('Advanced Custom Fields is required to manage listings.', 'ibex-racing-child'); ?></p>
      </div>
    <?php elseif ($view_mode === 'index') : ?>
      <div class="ibex-dashboard__empty-state">
        <h2><?php esc_html_e('Select a listing to begin', 'ibex-racing-child'); ?></h2>
        <p><?php esc_html_e('Choose a listing from the list or start a new one to edit details and upload images.', 'ibex-racing-child'); ?></p>
        <a class="ibex-button ibex-dashboard__cta" href="<?php echo esc_url($create_link); ?>">
          <?php esc_html_e('Create Listing', 'ibex-racing-child'); ?>
        </a>
      </div>
    <?php else : ?>
      <div class="ibex-dashboard__editor">
        <div class="ibex-dashboard__editor-bar">
          <a class="ibex-dashboard__backlink" href="<?php echo esc_url($dashboard_base_url); ?>">
            <?php esc_html_e('← All Listings', 'ibex-racing-child'); ?>
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
                /* translators: %s: listing title */
                esc_html__('Edit: %s', 'ibex-racing-child'),
                esc_html($listing_post->post_title)
              );
            } else {
              esc_html_e('Create New Listing', 'ibex-racing-child');
            }
            ?>
          </h2>
          <p class="ibex-dashboard__editor-intro">
            <?php
            if ($view_mode === 'edit') {
              esc_html_e('Update your listing details, pricing, images, and availability below.', 'ibex-racing-child');
            } else {
              esc_html_e('Fill in the listing details below. All listings publish immediately once saved.', 'ibex-racing-child');
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
          
          <?php if ($view_mode === 'edit' && $listing_post) : ?>
            <?php
            // Check if user can delete this listing (admin or creator)
            $can_delete = current_user_can('manage_options') || (int) $listing_post->post_author === $current_user->ID;
            if ($can_delete) :
            ?>
              <script type="text/template" class="ibex-delete-button-template">
                <button type="button" 
                        class="ibex-button ibex-button--danger ibex-delete-listing-btn" 
                        data-listing-id="<?php echo esc_attr($listing_post->ID); ?>"
                        data-listing-title="<?php echo esc_attr($listing_post->post_title); ?>">
                  <?php esc_html_e('Delete Listing', 'ibex-racing-child'); ?>
                </button>
              </script>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </section>
</div>

