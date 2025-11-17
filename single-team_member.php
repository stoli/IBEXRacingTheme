<?php
/**
 * Single template for IBEX Racing Team Members.
 *
 * @package ibex-racing-child
 */

get_header();

$contact_page = get_permalink(get_page_by_path('contact'));
?>

<main id="primary" class="site-main ibex-team-member-single">
  <?php
  while (have_posts()) :
    the_post();
    $team_id             = get_the_ID();
    $role                = '';
    $summary             = '';
    $summary_from_field  = false;
    $hero_image_markup   = '';
    $hero_has_image      = false;
    $social_links        = function_exists('ibex_get_team_social_links') ? ibex_get_team_social_links($team_id) : [];

    if (function_exists('get_field')) {
      $role = (string) get_field('team_member_title', $team_id) ?: '';

      $summary_field = get_field('team_member_summary', $team_id);
      if (is_string($summary_field) && trim($summary_field) !== '') {
        $summary = $summary_field;
        $summary_from_field = true;
      }

      $hero_field = get_field('team_member_hero_image', $team_id);

      if (is_array($hero_field)) {
        $hero_id = isset($hero_field['ID']) ? (int) $hero_field['ID'] : 0;
        if ($hero_id) {
          $hero_image_markup = wp_get_attachment_image(
            $hero_id,
            'large',
            false,
            [
              'class'   => 'ibex-team-member__hero-image',
              'loading' => 'lazy',
            ]
          );
        } elseif (!empty($hero_field['url'])) {
          $alt = isset($hero_field['alt']) && is_string($hero_field['alt'])
            ? $hero_field['alt']
            : get_the_title($team_id);
          $hero_image_markup = sprintf(
            '<img src="%1$s" alt="%2$s" class="ibex-team-member__hero-image" loading="lazy" />',
            esc_url($hero_field['url']),
            esc_attr($alt)
          );
        }
      }
    }

    if ($role === '' && has_excerpt()) {
      $role = wp_strip_all_tags(get_the_excerpt($team_id));
    }

    if ($summary === '') {
      $content_preview = get_post_field('post_content', $team_id);
      if ($content_preview) {
        $summary = wp_trim_words(wp_strip_all_tags($content_preview), 36, '...');
      }
    }

    if (!$hero_image_markup && has_post_thumbnail()) {
      $hero_image_markup = get_the_post_thumbnail($team_id, 'large', ['class' => 'ibex-team-member__hero-image']);
    }

    $hero_has_image = $hero_image_markup !== '';

    $raw_content   = get_post_field('post_content', $team_id);
    $rendered_body = $raw_content ? apply_filters('the_content', $raw_content) : '';
    $has_content   = $rendered_body && trim(wp_strip_all_tags($rendered_body)) !== '';
    ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class('ibex-team-member'); ?>>
      <header class="ibex-team-member__hero">
        <div class="ibex-team-member__hero-media">
          <?php if ($hero_has_image) : ?>
            <?php echo $hero_image_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
          <?php else : ?>
            <div class="ibex-team-member__hero-placeholder" aria-hidden="true">
              <?php esc_html_e('Team Member', 'ibex-racing-child'); ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="ibex-team-member__hero-content">
          <span class="ibex-team-member__eyebrow"><?php esc_html_e('Team', 'ibex-racing-child'); ?></span>
          <h1 class="ibex-team-member__title"><?php the_title(); ?></h1>

          <?php if ($role) : ?>
            <p class="ibex-team-member__role"><?php echo esc_html($role); ?></p>
          <?php endif; ?>

          <?php if ($summary) : ?>
            <div class="ibex-team-member__summary">
              <?php
              if ($summary_from_field) {
                echo wp_kses_post(nl2br($summary));
              } else {
                echo esc_html($summary);
              }
              ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($social_links)) : ?>
            <div class="ibex-team-member__hero-socials">
              <p class="ibex-team-member__follow-label"><?php esc_html_e('Follow', 'ibex-racing-child'); ?></p>
              <ul class="ibex-card__socials" aria-label="<?php esc_attr_e('Social links', 'ibex-racing-child'); ?>">
                <?php foreach ($social_links as $link) : ?>
                  <?php if (empty($link['icon']) || empty($link['url'])) : ?>
                    <?php continue; ?>
                  <?php endif; ?>
                  <li>
                    <a
                      class="ibex-card__social-link"
                      href="<?php echo esc_url($link['url']); ?>"
                      target="_blank"
                      rel="noopener"
                      aria-label="<?php echo esc_attr($link['label']); ?>"
                    >
                      <?php echo $link['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                      <span class="screen-reader-text"><?php echo esc_html($link['label']); ?></span>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <?php if ($contact_page) : ?>
            <div class="ibex-team-member__hero-actions">
              <a class="ibex-button ibex-button--outline" href="<?php echo esc_url($contact_page); ?>">
                <?php esc_html_e('Connect With Us', 'ibex-racing-child'); ?>
              </a>
            </div>
          <?php endif; ?>
        </div>
      </header>

      <section class="ibex-archive-section ibex-team-member__body">
        <div class="ibex-team-member__layout">
          <div class="ibex-team-member__content">
            <?php
            if ($has_content) {
              echo $rendered_body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            } elseif ($summary) {
              echo '<p>';
              if ($summary_from_field) {
                echo wp_kses_post(nl2br($summary));
              } else {
                echo esc_html($summary);
              }
              echo '</p>';
            } else {
              echo '<p>' . esc_html__('Profile details coming soon.', 'ibex-racing-child') . '</p>';
            }

            wp_link_pages([
              'before' => '<nav class="ibex-team-member__pages" aria-label="' . esc_attr__('Page navigation', 'ibex-racing-child') . '">',
              'after'  => '</nav>',
              'link_before' => '<span class="ibex-team-member__page-link">',
              'link_after'  => '</span>',
            ]);
            ?>
          </div>

          <aside class="ibex-team-member__sidebar" aria-label="<?php esc_attr_e('Team member quick facts', 'ibex-racing-child'); ?>">
            <h2><?php esc_html_e('Quick Facts', 'ibex-racing-child'); ?></h2>
            <ul class="ibex-team-member__facts">
              <?php if ($role) : ?>
                <li>
                  <span class="ibex-team-member__fact-label"><?php esc_html_e('Role', 'ibex-racing-child'); ?></span>
                  <span class="ibex-team-member__fact-value"><?php echo esc_html($role); ?></span>
                </li>
              <?php endif; ?>
            </ul>

            <?php if (!empty($social_links)) : ?>
              <div class="ibex-team-member__sidebar-socials">
                <p class="ibex-team-member__follow-label"><?php esc_html_e('Follow', 'ibex-racing-child'); ?></p>
                <ul class="ibex-card__socials" aria-label="<?php esc_attr_e('Social links', 'ibex-racing-child'); ?>">
                  <?php foreach ($social_links as $link) : ?>
                    <?php if (empty($link['icon']) || empty($link['url'])) : ?>
                      <?php continue; ?>
                    <?php endif; ?>
                    <li>
                      <a
                        class="ibex-card__social-link"
                        href="<?php echo esc_url($link['url']); ?>"
                        target="_blank"
                        rel="noopener"
                        aria-label="<?php echo esc_attr($link['label']); ?>"
                      >
                        <?php echo $link['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <span class="screen-reader-text"><?php echo esc_html($link['label']); ?></span>
                      </a>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <?php if ($contact_page) : ?>
              <a class="ibex-button ibex-button--ghost ibex-team-member__sidebar-cta" href="<?php echo esc_url($contact_page); ?>">
                <?php esc_html_e('Get In Touch', 'ibex-racing-child'); ?>
              </a>
            <?php endif; ?>

            <?php
            // Front-end manage link for owners/admins
            if (is_user_logged_in()) {
              $current_user = wp_get_current_user();
              $can_edit = (int) get_post_field('post_author', $team_id) === $current_user->ID || current_user_can('edit_others_posts');
              if ($can_edit && function_exists('ibex_get_page_link_by_template')) {
                $dashboard_url = ibex_get_page_link_by_template('page-team-dashboard.php');
                if ($dashboard_url) {
                  $edit_url = add_query_arg(['team_id' => $team_id], $dashboard_url);
                  echo '<a class="ibex-button ibex-button--outline ibex-team-member__sidebar-cta" href="' . esc_url($edit_url) . '">' . esc_html__('Edit Profile', 'ibex-racing-child') . '</a>';
                }
              }
            }
            ?>
          </aside>
        </div>
      </section>
    </article>
  <?php endwhile; ?>
</main>

<?php
get_footer();

?>


