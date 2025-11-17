<?php
/**
 * Front-end event submission form.
 *
 * Handles both creation and editing of `race_event` posts for logged-in users.
 *
 * Expected usage: include from a page template (e.g. `page-event-dashboard.php`).
 *
 * @package IbexRacingChild
 */

if (!defined('ABSPATH')) {
  exit;
}

if (!is_user_logged_in()) {
  auth_redirect();
  return;
}

if (!function_exists('acf_form')) {
  echo '<p class="ibex-event-form__notice">' .
    esc_html__('Event form unavailable. Advanced Custom Fields is required.', 'ibex-racing-child') .
    '</p>';
  return;
}

$current_user = wp_get_current_user();
$is_edit      = false;
$event_post   = null;

if (isset($_GET['event_id'])) {
  $candidate = get_post((int) $_GET['event_id']);
  if ($candidate && $candidate->post_type === 'race_event') {
    $event_post = $candidate;
    $is_edit    = true;

    if ((int) $event_post->post_author !== $current_user->ID && !current_user_can('manage_options')) {
      wp_die(
        esc_html__('You do not have permission to edit this event.', 'ibex-racing-child'),
        esc_html__('Forbidden', 'ibex-racing-child'),
        ['response' => 403]
      );
    }
  }
}

$form_id      = $is_edit ? 'ibex-edit-event-form' : 'ibex-new-event-form';
$submit_label = $is_edit ? esc_html__('Update Event', 'ibex-racing-child') : esc_html__('Create Event', 'ibex-racing-child');
$redirect_url = add_query_arg(
  [
    'event_submitted' => 1,
    'event_id'        => '%post_id%',
  ],
  remove_query_arg(['event_submitted','event_id'])
);

$acf_form_args = [
  'id'                 => $form_id,
  'post_id'            => $is_edit ? $event_post->ID : 'new_post',
  'new_post'           => [
    'post_type'   => 'race_event',
    'post_status' => 'publish',
    'post_author' => $current_user->ID,
  ],
  'return'             => $redirect_url,
  'field_groups'       => ['group_ibex_event_details'],
  'post_title'         => true,
  'post_content'       => true,
  'post_excerpt'       => true,
  'submit_value'       => $submit_label,
  'html_submit_button' => sprintf(
    '<button type="submit" class="button button-primary ibex-event-form__submit">%s</button>',
    esc_html($submit_label)
  ),
  'uploader'           => 'wp',
  'html_updated_message' => '<div class="ibex-event-form__notice ibex-event-form__notice--success">' .
    esc_html__('Event saved successfully.', 'ibex-racing-child') .
    '</div>',
];

?>
<div class="ibex-event-form">
  <header class="ibex-event-form__header">
    <h1 class="ibex-event-form__title">
      <?php echo $is_edit ? esc_html__('Edit Event', 'ibex-racing-child') : esc_html__('Create Event', 'ibex-racing-child'); ?>
    </h1>
    <p class="ibex-event-form__intro">
      <?php esc_html_e('Provide the event details below. Saved events start in draft state until published by you or an admin.', 'ibex-racing-child'); ?>
    </p>
  </header>

  <?php acf_form($acf_form_args); ?>
</div>

