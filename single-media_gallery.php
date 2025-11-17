<?php
/**
 * Single template for media galleries.
 *
 * @package ibex-racing-child
 */

get_header();
?>

<main id="primary" class="site-main ibex-media-gallery">
  <?php if (have_posts()) : ?>
    <?php while (have_posts()) : ?>
      <?php
      the_post();

      $gallery_id = get_the_ID();
      $cover_image = has_post_thumbnail($gallery_id) ? get_the_post_thumbnail_url($gallery_id, 'full') : '';

      $get_field_safe = static function (string $field, $format_value = true) use ($gallery_id) {
        if (function_exists('get_field')) {
          return get_field($field, $gallery_id, $format_value);
        }

        $meta = get_post_meta($gallery_id, $field, true);
        return $format_value ? $meta : $meta;
      };

      $start_date          = $get_field_safe('media_gallery_start_date');
      $end_date            = $get_field_safe('media_gallery_end_date');
      $location            = $get_field_safe('media_gallery_location');
      $overview            = $get_field_safe('media_gallery_overview');
      $default_photographer = $get_field_safe('media_gallery_photographer');
      $allow_downloads     = (bool) $get_field_safe('media_gallery_allow_downloads');
      $media_items_raw     = $get_field_safe('media_gallery_items') ?: [];
      $related_event_id    = (int) $get_field_safe('media_gallery_related_event');
      $date_label          = function_exists('ibex_format_date_range')
        ? ibex_format_date_range($start_date, $end_date)
        : '';

      $related_event_url   = $related_event_id ? get_permalink($related_event_id) : '';
      $related_event_title = $related_event_id ? get_the_title($related_event_id) : '';

      $archive_link  = get_post_type_archive_link('media_gallery');
      $asset_counter = is_array($media_items_raw) ? count($media_items_raw) : 0;

      $prepared_items = [];

      if (is_array($media_items_raw)) {
        foreach ($media_items_raw as $index => $item) {
          $layout = $item['acf_fc_layout'] ?? '';
          $caption = $item['caption'] ?? '';
          $caption_html = $caption ? wpautop($caption) : '';
          $credit = !empty($item['photographer_override']) ? $item['photographer_override'] : $default_photographer;

          $download_allowed = $allow_downloads;
          if (array_key_exists('download_allowed', $item) && $item['download_allowed'] !== '') {
            $download_allowed = (bool) $item['download_allowed'];
          }

          if ($layout === 'image_upload') {
            $image_id = $item['image_file'] ?? 0;
            if (!$image_id) {
              continue;
            }

            $thumb_html = wp_get_attachment_image($image_id, 'large', false, [
              'class'   => 'ibex-media-gallery__image',
              'loading' => 'lazy',
            ]);
            $full_url  = wp_get_attachment_url($image_id) ?: '';

            $prepared_items[] = [
              'type'             => 'image',
              'media'            => $thumb_html,
              'full_url'         => $full_url,
              'caption'          => $caption_html,
              'credit'           => $credit,
              'download_allowed' => $download_allowed && $full_url,
              'download_name'    => $full_url ? basename($full_url) : '',
            ];
            if (!$cover_image && $image_id) {
              $cover_image = wp_get_attachment_image_url($image_id, 'full');
            }
            continue;
          }

          if ($layout === 'video_upload') {
            $video_id  = $item['video_file'] ?? 0;
            $video_url = $video_id ? wp_get_attachment_url($video_id) : '';

            if (!$video_url) {
              continue;
            }

            $poster_id  = $item['poster_image'] ?? 0;
            $poster_url = $poster_id ? wp_get_attachment_image_url($poster_id, 'large') : '';

            $video_html = wp_video_shortcode(array_filter([
              'src'    => $video_url,
              'poster' => $poster_url,
            ]));

            $prepared_items[] = [
              'type'             => 'video',
              'media'            => $video_html,
              'full_url'         => $video_url,
              'caption'          => $caption_html,
              'credit'           => $credit,
              'download_allowed' => $download_allowed && $video_url,
              'download_name'    => $video_url ? basename($video_url) : '',
            ];
            if (!$cover_image && $poster_url) {
              $cover_image = $poster_url;
            }
            continue;
          }

          if ($layout === 'video_embed') {
            $embed_value = $item['embed_url'] ?? '';
            if (!$embed_value) {
              continue;
            }

            $embed_url  = '';
            $embed_html = '';

            if (is_array($embed_value)) {
              $embed_url  = isset($embed_value['url']) && is_string($embed_value['url']) ? trim($embed_value['url']) : '';
              $embed_html = isset($embed_value['html']) && is_string($embed_value['html']) ? $embed_value['html'] : '';

              if (!$embed_html && $embed_url) {
                $embed_html = wp_oembed_get($embed_url) ?: '';
              }
            } elseif (is_string($embed_value)) {
              $embed_candidate = trim($embed_value);

              if ($embed_candidate !== '') {
                if (preg_match('/<(iframe|blockquote)\b/i', $embed_candidate)) {
                  $embed_html = $embed_candidate;
                } elseif (filter_var($embed_candidate, FILTER_VALIDATE_URL)) {
                  $embed_url  = $embed_candidate;
                  $embed_html = wp_oembed_get($embed_url) ?: '';
                } else {
                  $embed_html = $embed_candidate;
                }
              }
            }

            if (!$embed_html && $embed_url) {
              global $wp_embed;
              if ($wp_embed instanceof \WP_Embed) {
                $embed_html = $wp_embed->shortcode([], $embed_url);
              }
            }

            if (!$embed_html) {
              $embed_html = sprintf(
                '<p class="ibex-media-gallery__embed-fallback">%s</p>',
                esc_html__('Video unavailable.', 'ibex-racing-child')
              );
            }

            $full_url = '';
            if ($embed_url && filter_var($embed_url, FILTER_VALIDATE_URL)) {
              $full_url = $embed_url;
            }

            $prepared_items[] = [
              'type'             => 'embed',
              'media'            => sprintf(
                '<div class="ibex-media-gallery__embed">%s</div>',
                trim($embed_html)
              ),
              'full_url'         => $full_url,
              'caption'          => $caption_html,
              'credit'           => $credit,
              'download_allowed' => false,
              'provider'         => $item['embed_provider'] ?? 'other',
            ];
            if (!$cover_image && !empty($item['thumbnail_image'])) {
              $cover_image = wp_get_attachment_image_url($item['thumbnail_image'], 'full');
            }
            continue;
          }
        }
      }

      if (!$cover_image && $prepared_items) {
        foreach ($prepared_items as $media) {
          if (!empty($media['type']) && $media['type'] === 'image' && !empty($media['full_url'])) {
            $cover_image = $media['full_url'];
            break;
          }
        }
      }
      ?>

      <article id="post-<?php the_ID(); ?>" <?php post_class('ibex-media-gallery__article'); ?>>
        <header class="ibex-media-gallery__hero"<?php echo $cover_image ? ' style="--ibex-media-gallery-cover:url(' . esc_url($cover_image) . ')"' : ''; ?>>
          <div class="ibex-media-gallery__hero-overlay">
            <div class="ibex-media-gallery__hero-header">
              <span class="ibex-media-gallery__eyebrow"><?php esc_html_e('Media Gallery', 'ibex-racing-child'); ?></span>
              <h1 class="ibex-media-gallery__title"><?php the_title(); ?></h1>
            </div>

            <ul class="ibex-media-gallery__meta">
              <?php if ($date_label) : ?>
                <li>
                  <span class="ibex-media-gallery__meta-label"><?php esc_html_e('Date', 'ibex-racing-child'); ?></span>
                  <span class="ibex-media-gallery__meta-value"><?php echo esc_html($date_label); ?></span>
                </li>
              <?php endif; ?>
              <?php if ($location) : ?>
                <li>
                  <span class="ibex-media-gallery__meta-label"><?php esc_html_e('Location', 'ibex-racing-child'); ?></span>
                  <span class="ibex-media-gallery__meta-value"><?php echo esc_html($location); ?></span>
                </li>
              <?php endif; ?>
              <li>
                <span class="ibex-media-gallery__meta-label"><?php esc_html_e('Assets', 'ibex-racing-child'); ?></span>
                <span class="ibex-media-gallery__meta-value">
                  <?php
                  printf(
                    esc_html(_n('%d item', '%d items', $asset_counter, 'ibex-racing-child')),
                    absint($asset_counter)
                  );
                  ?>
                </span>
              </li>
            </ul>

            <div class="ibex-media-gallery__hero-actions">
              <?php if ($related_event_url) : ?>
                <a class="ibex-media-gallery__cta ibex-media-gallery__cta--accent" href="<?php echo esc_url($related_event_url); ?>">
                  <?php echo esc_html(sprintf(__('View Event: %s', 'ibex-racing-child'), $related_event_title)); ?>
                </a>
              <?php endif; ?>
              <?php if ($archive_link) : ?>
                <a class="ibex-media-gallery__cta" href="<?php echo esc_url($archive_link); ?>">
                  <?php esc_html_e('All Galleries', 'ibex-racing-child'); ?>
                </a>
              <?php endif; ?>
            </div>
          </div>
        </header>

        <div class="ibex-media-gallery__body">
          <?php if (has_excerpt()) : ?>
            <div class="ibex-media-gallery__summary">
              <?php the_excerpt(); ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($overview)) : ?>
            <div class="ibex-media-gallery__overview">
              <?php echo wp_kses_post($overview); ?>
            </div>
          <?php endif; ?>

          <?php if ($prepared_items) : ?>
            <section class="ibex-media-gallery__collection" aria-label="<?php esc_attr_e('Gallery assets', 'ibex-racing-child'); ?>">
              <div class="ibex-media-gallery__grid">
                <?php foreach ($prepared_items as $media) : ?>
                  <?php
                  $item_classes = ['ibex-media-gallery__item'];
                  $item_classes[] = 'ibex-media-gallery__item--' . sanitize_html_class($media['type']);
                  ?>
                  <figure class="<?php echo esc_attr(implode(' ', $item_classes)); ?>">
                    <div class="ibex-media-gallery__media">
                      <?php
                      if ($media['type'] === 'image') {
                        if (!empty($media['full_url'])) {
                          printf(
                            '<a href="%1$s" class="ibex-media-gallery__media-link"%2$s>%3$s</a>',
                            esc_url($media['full_url']),
                            $media['download_allowed'] ? ' download="' . esc_attr($media['download_name']) . '"' : '',
                            $media['media']
                          );
                        } else {
                          echo $media['media']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        }
                      } elseif ($media['type'] === 'video') {
                        echo $media['media']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                      } else {
                        echo $media['media']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                      }
                      ?>
                    </div>
                    <?php if (!empty($media['caption']) || !empty($media['credit']) || !empty($media['download_allowed'])) : ?>
                      <figcaption class="ibex-media-gallery__meta-block">
                        <?php if (!empty($media['caption'])) : ?>
                          <div class="ibex-media-gallery__caption">
                            <?php echo wp_kses_post($media['caption']); ?>
                          </div>
                        <?php endif; ?>
                        <div class="ibex-media-gallery__meta-footer">
                          <?php if (!empty($media['credit'])) : ?>
                            <span class="ibex-media-gallery__credit">
                              <?php echo esc_html($media['credit']); ?>
                            </span>
                          <?php endif; ?>
                          <?php if (!empty($media['download_allowed']) && !empty($media['full_url'])) : ?>
                            <a class="ibex-media-gallery__download" href="<?php echo esc_url($media['full_url']); ?>" download="<?php echo esc_attr($media['download_name']); ?>">
                              <?php esc_html_e('Download', 'ibex-racing-child'); ?>
                            </a>
                          <?php endif; ?>
                        </div>
                      </figcaption>
                    <?php endif; ?>
                  </figure>
                <?php endforeach; ?>
              </div>
            </section>
          <?php else : ?>
            <p class="ibex-media-gallery__empty">
              <?php esc_html_e('Gallery assets are being prepared. Check back soon for photos and video.', 'ibex-racing-child'); ?>
            </p>
          <?php endif; ?>
        </div>
      </article>
    <?php endwhile; ?>
  <?php else : ?>
    <p class="ibex-media-gallery__empty">
      <?php esc_html_e('No gallery found.', 'ibex-racing-child'); ?>
    </p>
  <?php endif; ?>
</main>

<?php
get_footer();

