<?php
/**
 * Dashboard view for managing team members.
 *
 * @package IbexRacingChild
 */

if (!defined('ABSPATH')) {
  exit;
}

$settings = isset($args['settings']) && is_array($args['settings']) ? $args['settings'] : [];

if (!current_user_can('edit_posts')) {
  wp_die(
    esc_html__('You do not have permission to access team profiles.', 'ibex-racing-child'),
    esc_html__('Forbidden', 'ibex-racing-child'),
    ['response' => 403]
  );
}

$form_available = function_exists('acf_form');
$current_user   = wp_get_current_user();
$can_manage_all = current_user_can('manage_options') || current_user_can('edit_others_posts');

$dashboard_base_url = get_permalink();
$dashboard_base_url = remove_query_arg(['team_id', 'mode', 'team_submitted'], $dashboard_base_url);

$requested_team_id = isset($_GET['team_id']) ? (int) $_GET['team_id'] : 0;
$requested_mode    = isset($_GET['mode']) ? sanitize_key((string) $_GET['mode']) : '';

$view_mode = 'index';
$team_post = null;

if ($requested_team_id > 0) {
  $candidate = get_post($requested_team_id);
  if ($candidate && $candidate->post_type === 'team_member') {
    if ((int) $candidate->post_author !== $current_user->ID && !$can_manage_all) {
      wp_die(
        esc_html__('You do not have permission to edit this profile.', 'ibex-racing-child'),
        esc_html__('Forbidden', 'ibex-racing-child'),
        ['response' => 403]
      );
    }
    $team_post = $candidate;
    $view_mode = 'edit';
  } else {
    wp_die(
      esc_html__('The requested team member could not be found.', 'ibex-racing-child'),
      esc_html__('Not Found', 'ibex-racing-child'),
      ['response' => 404]
    );
  }
} elseif ($requested_mode === 'create') {
  $view_mode = 'create';
}

$submission_notice = '';
if (!empty($_GET['team_submitted'])) {
  $submitted_id  = (int) $_GET['team_submitted'];
  $submitted_post = get_post($submitted_id);
  if ($submitted_post && $submitted_post->post_type === 'team_member') {
    $submission_notice = sprintf(
      /* translators: %s: team member title */
      esc_html__('Profile "%s" has been saved successfully.', 'ibex-racing-child'),
      esc_html($submitted_post->post_title)
    );
  }
}

$query_args = [
  'post_type'      => 'team_member',
  'posts_per_page' => -1,
  'post_status'    => 'any',
  'orderby'        => 'modified',
  'order'          => 'DESC',
];

if (!$can_manage_all) {
  $query_args['author'] = $current_user->ID;
}

$team_query = new WP_Query($query_args);
$team_posts = $team_query->posts;

$create_link = add_query_arg(['mode' => 'create'], $dashboard_base_url);
$view_link   = $team_post ? get_permalink($team_post->ID) : '';

$form_config = [
  'post_id'      => $team_post ? $team_post->ID : 'new_post',
  'post_title'   => true,
  'post_content' => true,
  'post_excerpt' => true,
  'new_post'     => [
    'post_type'   => 'team_member',
    'post_status' => 'publish',
  ],
  'return'       => add_query_arg(['team_submitted' => '%post_id%'], $dashboard_base_url),
  'submit_value' => $view_mode === 'edit'
    ? __('Update Profile', 'ibex-racing-child')
    : __('Create Profile', 'ibex-racing-child'),
  'updated_message' => false,
  'html_submit_button' => '<button type="submit" class="acf-button button button-primary button-large ibex-button">%s</button>',
  'uploader'     => 'wp',
  'field_groups' => ['group_ibex_team_member_details'],
];

if ($team_post) {
  $form_config['post_id'] = $team_post->ID;
}
?>

<div class="ibex-dashboard__panels">
  <aside class="ibex-dashboard__panel ibex-dashboard__panel--sidebar" aria-label="<?php esc_attr_e('Team members', 'ibex-racing-child'); ?>">
    <div class="ibex-dashboard__panel-header">
      <h2 class="ibex-dashboard__panel-title"><?php esc_html_e('Team', 'ibex-racing-child'); ?></h2>
      <a class="ibex-button ibex-button--outline ibex-dashboard__panel-action" href="<?php echo esc_url($create_link); ?>">
        <?php esc_html_e('New Profile', 'ibex-racing-child'); ?>
      </a>
    </div>

    <?php if ($submission_notice) : ?>
      <div class="ibex-dashboard__notice ibex-dashboard__notice--success">
        <?php echo esc_html($submission_notice); ?>
      </div>
    <?php endif; ?>

    <?php if ($team_posts) : ?>
      <ul class="ibex-dashboard__list">
        <?php foreach ($team_posts as $item) : ?>
          <?php
          $item_id   = (int) $item->ID;
          $item_url  = add_query_arg(['team_id' => $item_id], $dashboard_base_url);
          $is_active = $team_post && $team_post->ID === $item_id;
          $status    = get_post_status_object($item->post_status);
          $status_label = $status ? $status->label : ucfirst($item->post_status);
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
                <span><?php echo esc_html(get_the_modified_date('M j, Y', $item)); ?></span>
              </span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else : ?>
      <p class="ibex-dashboard__empty">
        <?php esc_html_e('No team profiles yet. Create a new one to get started.', 'ibex-racing-child'); ?>
      </p>
    <?php endif; ?>
  </aside>

  <section class="ibex-dashboard__panel ibex-dashboard__panel--primary">
    <?php if (!$form_available) : ?>
      <div class="ibex-dashboard__empty-state">
        <h2><?php esc_html_e('ACF form unavailable', 'ibex-racing-child'); ?></h2>
        <p><?php esc_html_e('Advanced Custom Fields is required to manage team profiles.', 'ibex-racing-child'); ?></p>
      </div>
    <?php elseif ($view_mode === 'index') : ?>
      <div class="ibex-dashboard__empty-state">
        <h2><?php esc_html_e('Select a profile to begin', 'ibex-racing-child'); ?></h2>
        <p><?php esc_html_e('Choose a team member from the list or create a new profile.', 'ibex-racing-child'); ?></p>
        <a class="ibex-button ibex-dashboard__cta" href="<?php echo esc_url($create_link); ?>">
          <?php esc_html_e('Create Profile', 'ibex-racing-child'); ?>
        </a>
      </div>
    <?php else : ?>
      <div class="ibex-dashboard__editor">
        <div class="ibex-dashboard__editor-bar">
          <a class="ibex-dashboard__backlink" href="<?php echo esc_url($dashboard_base_url); ?>">
            <?php esc_html_e('← All Team', 'ibex-racing-child'); ?>
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
                /* translators: %s: profile title */
                esc_html__('Edit: %s', 'ibex-racing-child'),
                esc_html($team_post->post_title)
              );
            } else {
              esc_html_e('Create New Profile', 'ibex-racing-child');
            }
            ?>
          </h2>
          <p class="ibex-dashboard__editor-intro">
            <?php
            if ($view_mode === 'edit') {
              esc_html_e('Update the team member’s details and bio below.', 'ibex-racing-child');
            } else {
              esc_html_e('Fill in the profile details below. Profiles publish immediately once saved.', 'ibex-racing-child');
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
        </div>
      </div>
    <?php endif; ?>
  </section>
</div>


