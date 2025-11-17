<?php
/**
 * Archive template for IBEX Racing Team Members.
 *
 * @package ibex-racing-child
 */

get_header();

$paged        = max(1, get_query_var('paged') ?: get_query_var('page') ?: 1);
$per_page     = 12;
$team_query   = new WP_Query([
  'post_type'      => 'team_member',
  'post_status'    => 'publish',
  'posts_per_page' => $per_page,
  'paged'          => $paged,
  'orderby'        => [
    'menu_order' => 'ASC',
    'title'      => 'ASC',
  ],
]);

$archive_intro = '';
$intro_page = apply_filters('ibex_team_archive_intro_page', get_page_by_path('team'));

if ($intro_page instanceof WP_Post) {
  $raw_intro = $intro_page->post_excerpt ?: $intro_page->post_content;
  if ($raw_intro) {
    $archive_intro = apply_filters(
      'ibex_team_archive_intro_content',
      wp_kses_post(wpautop($raw_intro))
    );
  }
}

$archive_description = get_the_archive_description();
if (!$archive_description && $archive_intro) {
  $archive_description = $archive_intro;
}
if (!$archive_description) {
  $archive_description = '<p>' . esc_html__('Drivers, crew, and hospitality experts representing IBEX Racing on and off the circuit.', 'ibex-racing-child') . '</p>';
}

$contact_page = get_permalink(get_page_by_path('contact'));
?>

<main id="primary" class="site-main ibex-team-archive">
  <header class="ibex-archive-hero ibex-archive-hero--team">
    <div class="ibex-archive-hero__overlay">
      <span class="ibex-archive-hero__eyebrow"><?php esc_html_e('Team', 'ibex-racing-child'); ?></span>
      <h1 class="ibex-archive-hero__title"><?php post_type_archive_title(); ?></h1>
      <div class="ibex-archive-hero__intro">
        <?php echo wp_kses_post($archive_description); ?>
      </div>
      <?php if ($contact_page || (is_user_logged_in() && current_user_can('edit_posts'))) : ?>
        <div class="ibex-archive-hero__cta">
          <?php if ($contact_page) : ?>
            <a class="ibex-button ibex-button--outline" href="<?php echo esc_url($contact_page); ?>">
              <?php esc_html_e('Connect With Us', 'ibex-racing-child'); ?>
            </a>
          <?php endif; ?>
          <?php
          if (is_user_logged_in() && current_user_can('edit_posts')) {
            $dashboard_url = function_exists('ibex_get_page_link_by_template')
              ? ibex_get_page_link_by_template('page-team-dashboard.php')
              : '';

            if ($dashboard_url) :
              ?>
              <a class="ibex-button" href="<?php echo esc_url($dashboard_url); ?>">
                <?php esc_html_e('Manage Team', 'ibex-racing-child'); ?>
              </a>
              <?php
            endif;
          }
          ?>
        </div>
      <?php endif; ?>
    </div>
  </header>

  <section class="ibex-archive-section ibex-team-section">
    <header class="ibex-archive-section__header">
      <div>
        <h2><?php esc_html_e('Team Roster', 'ibex-racing-child'); ?></h2>
        <p><?php esc_html_e('Meet the drivers, engineers, strategists, and hospitality leads behind IBEX Racing.', 'ibex-racing-child'); ?></p>
      </div>
    </header>

    <?php if ($team_query->have_posts()) : ?>
      <div class="ibex-card-grid ibex-card-grid--team">
        <?php
        while ($team_query->have_posts()) :
          $team_query->the_post();
          $team_id  = get_the_ID();
          $role     = '';
          $summary  = '';
          $summary_from_field = false;
          $hero_image = null;
          $social_links = [];

          if (function_exists('get_field')) {
            $role = (string) get_field('team_member_title', $team_id) ?: '';

            $summary_field = get_field('team_member_summary', $team_id);
            if (is_string($summary_field) && trim($summary_field) !== '') {
              $summary = $summary_field;
              $summary_from_field = true;
            }

            $hero_image = get_field('team_member_hero_image', $team_id);
          }

          if (function_exists('ibex_get_team_social_links')) {
            $social_links = ibex_get_team_social_links($team_id);
          }

          if ($role === '' && has_excerpt()) {
            $role = wp_strip_all_tags(get_the_excerpt($team_id));
          }

          if ($summary === '') {
            $content_raw = get_post_field('post_content', $team_id);
            if ($content_raw) {
              $summary = wp_trim_words(wp_strip_all_tags($content_raw), 32, '...');
            }
          }

          $card_media = '';
          if (is_array($hero_image) && !empty($hero_image['ID'])) {
            $card_media = wp_get_attachment_image(
              (int) $hero_image['ID'],
              'large',
              false,
              [
                'class' => 'ibex-card__image',
                'loading' => 'lazy',
              ]
            );
          } elseif (is_array($hero_image) && !empty($hero_image['url'])) {
            $card_media = sprintf(
              '<img src="%1$s" alt="%2$s" class="ibex-card__image" loading="lazy" />',
              esc_url($hero_image['url']),
              esc_attr($hero_image['alt'] ?? get_the_title($team_id))
            );
          } elseif (has_post_thumbnail()) {
            $card_media = get_the_post_thumbnail($team_id, 'large', ['class' => 'ibex-card__image']);
          }

          $hero_placeholder = $card_media === '';
          if (!$summary_from_field && $summary !== '') {
            $summary = wp_strip_all_tags($summary);
          }
          ?>

          <article <?php post_class('ibex-card ibex-card--team'); ?>>
            <a class="ibex-card__media" href="<?php the_permalink(); ?>">
              <?php if ($card_media) : ?>
                <?php echo $card_media; ?>
              <?php elseif ($hero_placeholder) : ?>
                <span class="ibex-card__placeholder"><?php esc_html_e('Team Member', 'ibex-racing-child'); ?></span>
              <?php endif; ?>
            </a>

            <div class="ibex-card__body">
              <h2 class="ibex-card__title">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
              </h2>

              <?php if ($role) : ?>
                <ul class="ibex-card__meta">
                  <li>
                    <span class="ibex-card__meta-label"><?php esc_html_e('Role', 'ibex-racing-child'); ?></span>
                    <span class="ibex-card__meta-value"><?php echo esc_html($role); ?></span>
                  </li>
                </ul>
              <?php endif; ?>

              <?php if ($summary) : ?>
                <p class="ibex-card__summary">
                  <?php
                  if ($summary_from_field) {
                    echo wp_kses_post(nl2br($summary));
                  } else {
                    echo esc_html($summary);
                  }
                  ?>
                </p>
              <?php endif; ?>

              <?php if (!empty($social_links)) : ?>
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
              <?php endif; ?>

              <div class="ibex-card__actions">
                <a class="ibex-button" href="<?php the_permalink(); ?>">
                  <?php esc_html_e('View Profile', 'ibex-racing-child'); ?>
                </a>
              </div>
            </div>
          </article>
        <?php endwhile; ?>
      </div>

      <?php
      $total_pages = (int) $team_query->max_num_pages;
      if ($total_pages > 1) :
        ?>
        <nav class="ibex-pagination" aria-label="<?php esc_attr_e('Team roster pagination', 'ibex-racing-child'); ?>">
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
    <?php else : ?>
      <p class="ibex-archive-empty">
        <?php esc_html_e('Our team roster is being updated. Please check back shortly to meet the crew.', 'ibex-racing-child'); ?>
      </p>
    <?php endif; ?>
    <?php wp_reset_postdata(); ?>
  </section>
</main>

<?php
get_footer();

?>

