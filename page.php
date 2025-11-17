<?php
/**
 * Default page template for IBEX Racing.
 *
 * Follows the same hero + content layout pattern used throughout the theme.
 *
 * @package ibex-racing-child
 */

get_header();

while (have_posts()) :
  the_post();

  $page_id = get_the_ID();
  $page_title = get_the_title();
  
  // Check for hero intro text (ACF field or excerpt)
  $page_excerpt = '';
  if (function_exists('get_field')) {
    $page_excerpt = get_field('page_hero_intro', $page_id) ?: '';
  }
  // Fallback to manual excerpt if ACF field not set
  if (!$page_excerpt && has_excerpt()) {
    $page_excerpt = get_the_excerpt();
  }
  
  $has_featured_image = has_post_thumbnail();
  $featured_image_url = $has_featured_image ? get_the_post_thumbnail_url($page_id, 'full') : '';
  
  // Check for custom eyebrow text (ACF field)
  $eyebrow_text = '';
  if (function_exists('get_field')) {
    $eyebrow_text = get_field('page_eyebrow', $page_id) ?: '';
  }
  
  // Default eyebrow based on page title or parent
  if (!$eyebrow_text) {
    $parent_id = wp_get_post_parent_id($page_id);
    if ($parent_id) {
      $eyebrow_text = get_the_title($parent_id);
    }
  }
  
  // Check for custom CTA button
  $show_cta = false;
  $cta_label = '';
  $cta_url = '';
  
  if (function_exists('get_field')) {
    $cta_label = get_field('page_cta_label', $page_id) ?: '';
    $cta_url = get_field('page_cta_url', $page_id) ?: '';
    $show_cta = $cta_label && $cta_url;
  }
  
  ?>

  <main id="primary" class="site-main ibex-page">
    <header class="ibex-archive-hero ibex-archive-hero--page" <?php echo $featured_image_url ? 'style="background-image: url(' . esc_url($featured_image_url) . ');"' : ''; ?>>
      <?php if ($featured_image_url) : ?>
        <div class="ibex-archive-hero__bg-overlay"></div>
      <?php endif; ?>
      <div class="ibex-archive-hero__overlay">
        <?php if ($eyebrow_text) : ?>
          <span class="ibex-archive-hero__eyebrow"><?php echo esc_html($eyebrow_text); ?></span>
        <?php endif; ?>
        <h1 class="ibex-archive-hero__title"><?php echo esc_html($page_title); ?></h1>
        <?php if ($page_excerpt) : ?>
          <div class="ibex-archive-hero__intro">
            <p><?php echo wp_kses_post($page_excerpt); ?></p>
          </div>
        <?php endif; ?>
        <?php if ($show_cta) : ?>
          <div class="ibex-archive-hero__cta">
            <a class="ibex-button ibex-button--outline" href="<?php echo esc_url($cta_url); ?>">
              <?php echo esc_html($cta_label); ?>
            </a>
          </div>
        <?php endif; ?>
      </div>
    </header>

    <article class="ibex-archive-section ibex-page-section">
      <div class="ibex-page-content">
        <?php
        the_content();

        wp_link_pages([
          'before' => '<nav class="ibex-page__navigation" aria-label="' . esc_attr__('Page navigation', 'ibex-racing-child') . '">',
          'after'  => '</nav>',
          'link_before' => '<span class="ibex-page__page-link">',
          'link_after'  => '</span>',
        ]);
        ?>
      </div>
    </article>

    <?php
    // If comments are open or there are comments, load the comment template.
    if (comments_open() || get_comments_number()) :
      ?>
      <div class="ibex-archive-section ibex-page-comments">
        <?php comments_template(); ?>
      </div>
    <?php endif; ?>
  </main>

<?php
endwhile; // End of the loop.

get_footer();

