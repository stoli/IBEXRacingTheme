<?php
// v2025-10-18 — enqueue parent theme styles
add_action('wp_enqueue_scripts', function() {
  $theme_version = wp_get_theme()->get('Version');
  $stylesheet_dir = get_stylesheet_directory_uri();
  $stylesheet_path = get_stylesheet_directory();
  
  // Enqueue parent theme style
  wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css', [], wp_get_theme(get_template())->get('Version'));
  
  // Enqueue child theme style with explicit path verification
  $child_style_path = $stylesheet_path . '/style.css';
  
  // Always try to enqueue - WordPress should handle errors gracefully
  // But use explicit URL construction to avoid path issues
  $theme_slug = get_stylesheet(); // Gets the actual theme directory name
  $child_style_url = content_url('themes/' . $theme_slug . '/style.css');
  
  // Get version from file if it exists, otherwise use theme version
  $child_style_version = $theme_version;
  if (file_exists($child_style_path) && is_readable($child_style_path)) {
    $child_style_version = filemtime($child_style_path);
  }
  
  // Ensure URL doesn't have double slashes and is properly formatted
  $child_style_url = str_replace(['/./', '//'], '/', $child_style_url);
  $child_style_url = preg_replace('#([^:])//+#', '$1/', $child_style_url);
  
  wp_enqueue_style(
    'ibex-racing-child-style',
    $child_style_url,
    ['parent-style'],
    $child_style_version
  );

  if (is_singular('listing') || is_singular('media_gallery')) {
    // Use get_stylesheet_directory_uri() for more reliable path
    $gallery_js_url = get_stylesheet_directory_uri() . '/assets/js/ibex-gallery.js';
    
    // Get version from file if it exists, otherwise use theme version
    $gallery_js_path = $stylesheet_path . '/assets/js/ibex-gallery.js';
    $gallery_js_version = $theme_version;
    if (file_exists($gallery_js_path) && is_readable($gallery_js_path)) {
      $gallery_js_version = filemtime($gallery_js_path);
    }
    
    wp_enqueue_script(
      'ibex-gallery',
      $gallery_js_url,
      [],
      $gallery_js_version,
      true
    );
    
    // DEBUG: Uncomment below for debugging script enqueue
    // error_log('IBEX Gallery: Enqueuing script at ' . $gallery_js_url . ' (version: ' . $gallery_js_version . ')');
  }
}, 10);

// Disable GeneratePress entry header for pages (we use custom hero layout)
add_filter('generate_show_entry_header', function($show) {
  if (is_page()) {
    return false;
  }
  return $show;
});

// Disable GeneratePress page title (we show it in our custom hero)
add_filter('generate_show_title', function($show) {
  if (is_page()) {
    return false;
  }
  return $show;
});

// v2025-10-18 — IBEX Racing CPTs (Team, Events, Listings)
add_action('init', function () {
  register_post_type('team_member', [
    'label' => 'Why IBEX?','public' => true,'show_in_rest' => true,
    'menu_icon' => 'dashicons-groups','supports' => ['title','editor','thumbnail','excerpt'],
    'has_archive' => true,'rewrite' => ['slug' => 'team']
  ]);
  register_post_type('race_event', [
    'label' => 'Events',
    'labels' => [
      'name' => 'Events',
      'singular_name' => 'Event',
      'add_new_item' => 'Add New Event',
      'edit_item' => 'Edit Event',
      'new_item' => 'New Event',
      'view_item' => 'View Event',
      'search_items' => 'Search Events',
      'not_found' => 'No events found',
      'not_found_in_trash' => 'No events found in Trash',
    ],
    'public' => true,
    'show_in_rest' => true,
    'menu_icon' => 'dashicons-calendar-alt',
    'supports' => ['title','editor','thumbnail','excerpt'],
    'has_archive' => true,
    'rewrite' => ['slug' => 'events'],
    'capability_type' => ['race_event','race_events'],
    'map_meta_cap' => true,
    'show_in_nav_menus' => true,
  ]);
  register_post_type('listing', [
    'label' => 'IBEX Store',
    'labels' => [
      'name' => 'Listings',
      'singular_name' => 'Listing',
      'add_new_item' => 'Add New Listing',
      'edit_item' => 'Edit Listing',
      'new_item' => 'New Listing',
      'view_item' => 'View Listing',
      'search_items' => 'Search Listings',
      'not_found' => 'No listings found',
      'not_found_in_trash' => 'No listings found in Trash',
    ],
    'public' => true,
    'show_in_rest' => true,
    'menu_icon' => 'dashicons-cart',
    'supports' => ['title','editor','thumbnail','excerpt'],
    'has_archive' => true,
    'rewrite' => ['slug' => 'for-sale'],
    'capability_type' => ['listing','listings'],
    'map_meta_cap' => true,
    'show_in_nav_menus' => true,
  ]);
  register_post_type('media_gallery', [
    'label' => 'Media Galleries','labels' => [
      'name' => 'Media Galleries',
      'singular_name' => 'Media Gallery',
      'add_new_item' => 'Add New Media Gallery',
      'edit_item' => 'Edit Media Gallery',
      'new_item' => 'New Media Gallery',
      'view_item' => 'View Media Gallery',
      'search_items' => 'Search Media Galleries',
      'not_found' => 'No media galleries found',
      'not_found_in_trash' => 'No media galleries found in Trash',
    ],
    'public' => true,
    'show_in_rest' => true,
    'menu_icon' => 'dashicons-format-gallery',
    'supports' => ['title','editor','thumbnail','excerpt'],
    'has_archive' => true,
    'rewrite' => ['slug' => 'media-gallery'],
    'capability_type' => ['media_gallery','media_galleries'],
    'map_meta_cap' => true,
    'show_in_nav_menus' => true,
  ]);
});
add_action('wp_enqueue_scripts', function () {
  if (!is_user_logged_in()) {
    return;
  }

  $theme_version = wp_get_theme()->get('Version');
  $ts = isset($_SERVER['REQUEST_TIME']) ? (int) $_SERVER['REQUEST_TIME'] : time();

  if (is_page_template('page-media-gallery-dashboard.php')) {
    wp_enqueue_script(
      'ibex-dashboard-flex',
      get_stylesheet_directory_uri() . '/assets/js/ibex-dashboard-flex.js',
      ['jquery', 'acf-input'],
      $theme_version . '.' . $ts,
      true
    );

    wp_enqueue_script(
      'ibex-media-gallery-form',
      get_stylesheet_directory_uri() . '/assets/js/ibex-media-gallery-form.js',
      ['ibex-dashboard-flex'],
      $theme_version . '.' . $ts,
      true
    );

    wp_localize_script('ibex-media-gallery-form', 'ibexMediaGalleryForm', [
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce'   => wp_create_nonce('ibex_event_gallery_lookup'),
    ]);

    wp_enqueue_script(
      'ibex-dashboard-delete',
      get_stylesheet_directory_uri() . '/assets/js/ibex-dashboard-delete.js',
      ['jquery'],
      $theme_version . '.' . $ts,
      true
    );

    wp_localize_script('ibex-dashboard-delete', 'ibexDelete', [
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'eventNonce' => wp_create_nonce('ibex_delete_event'),
      'galleryNonce' => wp_create_nonce('ibex_delete_gallery'),
      'listingNonce' => wp_create_nonce('ibex_delete_listing'),
    ]);
  }

  if (is_page_template('page-listing-dashboard.php')) {
    wp_enqueue_script(
      'ibex-dashboard-flex',
      get_stylesheet_directory_uri() . '/assets/js/ibex-dashboard-flex.js',
      ['jquery', 'acf-input'],
      $theme_version . '.' . $ts,
      true
    );

    wp_enqueue_script(
      'ibex-dashboard-delete',
      get_stylesheet_directory_uri() . '/assets/js/ibex-dashboard-delete.js',
      ['jquery'],
      $theme_version . '.' . $ts,
      true
    );

    wp_localize_script('ibex-dashboard-delete', 'ibexDelete', [
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'eventNonce' => wp_create_nonce('ibex_delete_event'),
      'galleryNonce' => wp_create_nonce('ibex_delete_gallery'),
      'listingNonce' => wp_create_nonce('ibex_delete_listing'),
    ]);

    // Hide "Add Media" button in listing dashboard content editor
    $custom_css = '
      .ibex-listing-dashboard #insert-media-button,
      .ibex-listing-dashboard .insert-media.add_media {
        display: none !important;
      }
    ';
    wp_add_inline_style('generatepress', $custom_css);
  }

  if (is_page_template('page-event-dashboard.php')) {
    wp_enqueue_script(
      'ibex-dashboard-flex',
      get_stylesheet_directory_uri() . '/assets/js/ibex-dashboard-flex.js',
      ['jquery', 'acf-input'],
      $theme_version . '.' . $ts,
      true
    );

    wp_enqueue_script(
      'ibex-dashboard-delete',
      get_stylesheet_directory_uri() . '/assets/js/ibex-dashboard-delete.js',
      ['jquery'],
      $theme_version . '.' . $ts,
      true
    );

    wp_localize_script('ibex-dashboard-delete', 'ibexDelete', [
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'eventNonce' => wp_create_nonce('ibex_delete_event'),
      'galleryNonce' => wp_create_nonce('ibex_delete_gallery'),
    ]);

    // Hide "Add Media" button in event dashboard content editor
    $custom_css = '
      .ibex-event-dashboard #insert-media-button,
      .ibex-event-dashboard .insert-media.add_media {
        display: none !important;
      }
    ';
    wp_add_inline_style('generatepress', $custom_css);
  }

  if (is_page_template('page-team-dashboard.php')) {
    wp_enqueue_script(
      'ibex-dashboard-flex',
      get_stylesheet_directory_uri() . '/assets/js/ibex-dashboard-flex.js',
      ['jquery', 'acf-input'],
      $theme_version . '.' . $ts,
      true
    );
  }
}, 20);
// v2025-11-11 — Ensure CPT capabilities for event/media workflows
add_action('init', function () {
  $roles = [
    'administrator' => [
      'read_media_gallery',
      'read_private_media_galleries',
      'edit_media_gallery',
      'edit_media_galleries',
      'edit_others_media_galleries',
      'publish_media_galleries',
      'delete_media_gallery',
      'delete_media_galleries',
      'delete_others_media_galleries',
    ],
    'editor' => [
      'read_media_gallery',
      'read_private_media_galleries',
      'edit_media_gallery',
      'edit_media_galleries',
      'publish_media_galleries',
      'delete_media_gallery',
    ],
    'author' => [
      'read_media_gallery',
      'edit_media_gallery',
      'edit_media_galleries',
      'publish_media_galleries',
      'delete_media_gallery',
    ],
  ];

  foreach ($roles as $role_key => $caps) {
    $role = get_role($role_key);
    if (!$role) {
      continue;
    }
    foreach ($caps as $cap) {
      if (!$role->has_cap($cap)) {
        $role->add_cap($cap);
      }
    }
  }

  // Add listing capabilities
  $listing_roles = [
    'administrator' => [
      'read_listing',
      'read_private_listings',
      'edit_listing',
      'edit_listings',
      'edit_others_listings',
      'publish_listings',
      'delete_listing',
      'delete_listings',
      'delete_others_listings',
    ],
    'editor' => [
      'read_listing',
      'read_private_listings',
      'edit_listing',
      'edit_listings',
      'publish_listings',
      'delete_listing',
    ],
    'author' => [
      'read_listing',
      'edit_listing',
      'edit_listings',
      'publish_listings',
      'delete_listing',
    ],
  ];

  foreach ($listing_roles as $role_key => $caps) {
    $role = get_role($role_key);
    if (!$role) {
      continue;
    }
    foreach ($caps as $cap) {
      if (!$role->has_cap($cap)) {
        $role->add_cap($cap);
      }
    }
  }

  // Add event capabilities
  $event_roles = [
    'administrator' => [
      'read_race_event',
      'read_private_race_events',
      'edit_race_event',
      'edit_race_events',
      'edit_others_race_events',
      'publish_race_events',
      'delete_race_event',
      'delete_race_events',
      'delete_others_race_events',
    ],
    'editor' => [
      'read_race_event',
      'read_private_race_events',
      'edit_race_event',
      'edit_race_events',
      'publish_race_events',
      'delete_race_event',
    ],
    'author' => [
      'read_race_event',
      'edit_race_event',
      'edit_race_events',
      'publish_race_events',
      'delete_race_event',
    ],
  ];

  foreach ($event_roles as $role_key => $caps) {
    $role = get_role($role_key);
    if (!$role) {
      continue;
    }
    foreach ($caps as $cap) {
      if (!$role->has_cap($cap)) {
        $role->add_cap($cap);
      }
    }
  }
}, 20);
// v2025-11-11 — Restrict deletions to authors or admins for events/galleries
add_filter('map_meta_cap', function (array $caps, string $cap, int $user_id, array $args) {
  // Handle delete_post capability
  if ($cap === 'delete_post' && !empty($args[0])) {
    $post = get_post((int) $args[0]);
    if ($post && in_array($post->post_type, ['race_event','media_gallery'], true)) {
      if ((int) $post->post_author === $user_id) {
        return ['delete_posts'];
      }

      if (user_can($user_id, 'manage_options')) {
        return ['delete_others_posts'];
      }

      return ['do_not_allow'];
    }
  }

  // Handle edit_post capability for media_gallery to allow admins to attach files
  if ($cap === 'edit_post' && !empty($args[0])) {
    $post = get_post((int) $args[0]);
    if ($post && $post->post_type === 'media_gallery') {
      // If user is the author, they can edit
      if ((int) $post->post_author === $user_id) {
        return ['edit_media_galleries'];
      }

      // Admins can always edit (and thus attach files to) any media gallery
      if (user_can($user_id, 'manage_options') || user_can($user_id, 'edit_others_media_galleries')) {
        return ['edit_others_media_galleries'];
      }
    }
  }

  return $caps;
}, 10, 4);
// v2025-11-09 — Strip archive prefixes from titles
add_filter('get_the_archive_title', function ($title) {
  return preg_replace('/^[^:]+:\s*/', '', $title);
});
// v2025-11-09 — ACF field group for race events
add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }
  acf_add_local_field_group([
    'key' => 'group_ibex_event_details',
    'title' => 'Event Details',
    'fields' => [
      [
        'key' => 'field_ibex_event_start_date',
        'label' => 'Start Date',
        'name' => 'event_start_date',
        'type' => 'date_picker',
        'required' => 1,
        'display_format' => 'F j, Y',
        'return_format' => 'Y-m-d',
        'first_day' => 1,
        'instructions' => 'Select the first day of the event. Used for ordering and “upcoming” status.',
      ],
      [
        'key' => 'field_ibex_event_end_date',
        'label' => 'End Date',
        'name' => 'event_end_date',
        'type' => 'date_picker',
        'required' => 0,
        'display_format' => 'F j, Y',
        'return_format' => 'Y-m-d',
        'first_day' => 1,
        'instructions' => 'Optional. Leave blank for single-day events.',
      ],
      [
        'key' => 'field_ibex_event_location',
        'label' => 'Location',
        'name' => 'event_location',
        'type' => 'text',
        'required' => 1,
        'instructions' => 'City, state, or venue name that should appear with the event.',
        'placeholder' => 'Misano World Circuit, Italy',
      ],
      [
        'key' => 'field_ibex_event_registration_url',
        'label' => 'Registration URL',
        'name' => 'event_registration_url',
        'type' => 'url',
        'required' => 0,
        'instructions' => 'Paste a full URL (https://…) if attendees should register online.',
      ],
      [
        'key' => 'field_ibex_event_registration_label',
        'label' => 'Registration Button Label',
        'name' => 'event_registration_label',
        'type' => 'text',
        'required' => 0,
        'instructions' => 'Optional custom text for the registration button (defaults to “Register Now”).',
        'placeholder' => 'Reserve Hospitality Pass',
      ],
      [
        'key' => 'field_ibex_event_summary',
        'label' => 'Card Summary',
        'name' => 'event_summary',
        'type' => 'textarea',
        'required' => 0,
        'new_lines' => 'br',
        'rows' => 3,
        'instructions' => '1–2 sentence teaser for cards and previews. Leave blank to use the Excerpt.',
      ],
      [
        'key' => 'field_ibex_event_featured_image',
        'label' => 'Featured Image',
        'name' => 'event_featured_image',
        'type' => 'image',
        'required' => 0,
        'return_format' => 'id',
        'preview_size' => 'medium',
        'library' => 'all',
        'instructions' => 'Upload a hero image used on event listings and single pages.',
      ],
      [
        'key' => 'field_ibex_event_create_gallery',
        'label' => 'Create Linked Media Gallery',
        'name' => 'create_media_gallery',
        'type' => 'true_false',
        'message' => 'Create a media gallery for this event after saving.',
        'ui' => 0,
        'default_value' => 0,
        'instructions' => 'Recommended when you plan to upload photos or videos from this event.',
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'race_event',
        ],
      ],
    ],
    'position' => 'normal',
    'style' => 'default',
    'label_placement' => 'top',
    'instruction_placement' => 'label',
    'active' => true,
    'description' => 'Guided fields for IBEX Racing event posts.',
  ]);
  acf_add_local_field_group([
    'key' => 'group_ibex_listing_details',
    'title' => 'Listing Details',
    'fields' => [
      [
        'key' => 'field_ibex_listing_price',
        'label' => 'Display Price',
        'name' => 'listing_price',
        'type' => 'text',
        'required' => 0,
        'instructions' => 'Enter the price exactly as it should display (e.g. $245,000 or “Call for pricing”).',
        'placeholder' => '$245,000',
      ],
      [
        'key' => 'field_ibex_listing_status',
        'label' => 'Status',
        'name' => 'listing_status',
        'type' => 'select',
        'required' => 0,
        'choices' => [
          'available' => 'Available',
          'reserved'  => 'Reserved',
          'sold'      => 'Sold',
        ],
        'default_value' => 'available',
        'ui' => 1,
        'instructions' => 'Set the current availability badge for this listing.',
      ],
      [
        'key' => 'field_ibex_listing_summary',
        'label' => 'Card Summary',
        'name' => 'listing_summary',
        'type' => 'textarea',
        'required' => 0,
        'new_lines' => 'br',
        'rows' => 3,
        'instructions' => 'Optional 1–2 sentence teaser for cards. Leave blank to use the Excerpt.',
      ],
      [
        'key' => 'field_ibex_listing_gallery',
        'label' => 'Image Gallery',
        'name' => 'listing_gallery',
        'type' => 'gallery',
        'required' => 0,
        'instructions' => 'Upload up to 25 supporting images. The featured image will be used as the hero.',
        'max' => 25,
        'preview_size' => 'medium',
        'insert' => 'append',
      ],
      [
        'key' => 'field_ibex_listing_contact_email',
        'label' => 'Inquiry Email',
        'name' => 'listing_contact_email',
        'type' => 'email',
        'required' => 1,
        'instructions' => 'Email address used when site visitors click the “Inquire” button.',
        'placeholder' => 'sales@example.com',
      ],
      [
        'key' => 'field_ibex_listing_contact_label',
        'label' => 'Contact Button Label',
        'name' => 'listing_contact_label',
        'type' => 'text',
        'required' => 0,
        'instructions' => 'Optional custom text for the contact button. Defaults to “Inquire”.',
        'placeholder' => 'Request Build Sheet',
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'listing',
        ],
      ],
    ],
    'position' => 'normal',
    'style' => 'default',
    'label_placement' => 'top',
    'instruction_placement' => 'label',
    'active' => true,
    'description' => 'Price, gallery, and inquiry settings for IBEX For Sale posts.',
  ]);
  acf_add_local_field_group([
    'key' => 'group_ibex_team_member_details',
    'title' => 'Team Member Details',
    'fields' => [
      [
        'key' => 'field_ibex_team_member_title',
        'label' => 'Title / Role',
        'name' => 'team_member_title',
        'type' => 'text',
        'required' => 0,
        'instructions' => 'Job title or primary role shown on cards and profiles.',
        'placeholder' => 'Chief Engineer',
      ],
      [
        'key' => 'field_ibex_team_member_summary',
        'label' => 'Card Summary',
        'name' => 'team_member_summary',
        'type' => 'textarea',
        'required' => 0,
        'new_lines' => 'br',
        'rows' => 3,
        'instructions' => '1–2 sentence bio used on archive cards. Leave blank to pull from the main content.',
      ],
      [
        'key' => 'field_ibex_team_member_hero_image',
        'label' => 'Hero Image',
        'name' => 'team_member_hero_image',
        'type' => 'image',
        'required' => 0,
        'return_format' => 'array',
        'preview_size' => 'medium',
        'library' => 'all',
        'instructions' => 'Optional hero image for cards and featured modules. Defaults to the featured image if not set.',
      ],
      [
        'key' => 'field_ibex_team_member_instagram',
        'label' => 'Instagram URL',
        'name' => 'team_member_instagram',
        'type' => 'url',
        'required' => 0,
        'instructions' => 'Optional link to the team member’s Instagram profile.',
        'placeholder' => 'https://www.instagram.com/username',
      ],
      [
        'key' => 'field_ibex_team_member_facebook',
        'label' => 'Facebook URL',
        'name' => 'team_member_facebook',
        'type' => 'url',
        'required' => 0,
        'instructions' => 'Optional link to the team member’s Facebook page or profile.',
        'placeholder' => 'https://www.facebook.com/username',
      ],
      [
        'key' => 'field_ibex_team_member_youtube',
        'label' => 'YouTube URL',
        'name' => 'team_member_youtube',
        'type' => 'url',
        'required' => 0,
        'instructions' => 'Optional link to the team member’s YouTube channel.',
        'placeholder' => 'https://www.youtube.com/@channel',
      ],
      [
        'key' => 'field_ibex_team_member_x',
        'label' => 'X (Twitter) URL',
        'name' => 'team_member_x',
        'type' => 'url',
        'required' => 0,
        'instructions' => 'Optional link to the team member’s X profile.',
        'placeholder' => 'https://twitter.com/username',
      ],
      [
        'key' => 'field_ibex_team_member_tiktok',
        'label' => 'TikTok URL',
        'name' => 'team_member_tiktok',
        'type' => 'url',
        'required' => 0,
        'instructions' => 'Optional link to the team member’s TikTok profile.',
        'placeholder' => 'https://www.tiktok.com/@username',
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'team_member',
        ],
      ],
    ],
    'position' => 'normal',
    'style' => 'default',
    'label_placement' => 'top',
    'instruction_placement' => 'label',
    'active' => true,
    'description' => 'Role, summary, and imagery for IBEX Team Member profiles.',
  ]);
  acf_add_local_field_group([
    'key' => 'group_ibex_media_gallery_details',
    'title' => 'Media Gallery Details',
    'fields' => [
      [
        'key' => 'field_ibex_media_gallery_cover_image',
        'label' => 'Cover Image',
        'name' => 'media_gallery_cover_image',
        'type' => 'image',
        'required' => 0,
        'return_format' => 'id',
        'preview_size' => 'medium',
        'library' => 'all',
        'instructions' => 'Hero image shown on gallery cards and listings. Recommended 16:9 crop.',
      ],
      [
        'key' => 'field_ibex_media_gallery_related_event',
        'label' => 'Related Event',
        'name' => 'media_gallery_related_event',
        'type' => 'post_object',
        'required' => 0,
        'post_type' => ['race_event'],
        'post_status' => ['publish','future','draft','pending'],
        'return_format' => 'id',
        'allow_null' => 1,
        'instructions' => 'Select the event this gallery is associated with. Used for linking and default metadata.',
      ],
      [
        'key' => 'field_ibex_media_gallery_start_date',
        'label' => 'Start Date',
        'name' => 'media_gallery_start_date',
        'type' => 'date_picker',
        'required' => 1,
        'display_format' => 'F j, Y',
        'return_format' => 'Y-m-d',
        'first_day' => 1,
        'width' => 50,
        'instructions' => 'Primary date displayed with the gallery. Defaults from related event when selected.',
      ],
      [
        'key' => 'field_ibex_media_gallery_end_date',
        'label' => 'End Date',
        'name' => 'media_gallery_end_date',
        'type' => 'date_picker',
        'required' => 0,
        'display_format' => 'F j, Y',
        'return_format' => 'Y-m-d',
        'first_day' => 1,
        'width' => 50,
        'instructions' => 'Optional; fill in for multi-day galleries.',
      ],
      [
        'key' => 'field_ibex_media_gallery_location',
        'label' => 'Location',
        'name' => 'media_gallery_location',
        'type' => 'text',
        'required' => 0,
        'instructions' => 'Defaults from the related event if linked; override as needed.',
        'placeholder' => 'Misano World Circuit, Italy',
      ],
      [
        'key' => 'field_ibex_media_gallery_overview',
        'label' => 'Overview',
        'name' => 'media_gallery_overview',
        'type' => 'wysiwyg',
        'tabs' => 'all',
        'toolbar' => 'basic',
        'media_upload' => 0,
        'required' => 0,
        'instructions' => 'Intro paragraph that appears above the gallery grid.',
      ],
      [
        'key' => 'field_ibex_media_gallery_author',
        'label' => 'Creator',
        'name' => 'media_gallery_author',
        'type' => 'user',
        'required' => 0,
        'role' => ['all'],
        'return_format' => 'id',
        'allow_null' => 1,
        'instructions' => 'Select the user who created this gallery. This will be displayed as "Created By" on the gallery page.',
      ],
      [
        'key' => 'field_ibex_media_gallery_photographer',
        'label' => 'Photographer Credit',
        'name' => 'media_gallery_photographer',
        'type' => 'text',
        'required' => 0,
        'instructions' => 'Credit applied to all images in the gallery.',
        'placeholder' => 'Photo: John Doe',
      ],
      [
        'key' => 'field_ibex_media_gallery_notes',
        'label' => 'Internal Notes',
        'name' => 'media_gallery_notes',
        'type' => 'textarea',
        'required' => 0,
        'instructions' => 'Optional notes for coordinators (not shown publicly).',
        'rows' => 3,
        'new_lines' => '',
      ],
      [
        'key' => 'field_ibex_media_gallery_items',
        'label' => 'Media Items',
        'name' => 'media_gallery_items',
        'type' => 'flexible_content',
        'instructions' => 'Add photos, videos, or embeds in the order they should appear.',
        'button_label' => 'Add Media Item',
        'min' => 1,
        'layouts' => [
          'layout_ibex_media_gallery_image' => [
            'key' => 'layout_ibex_media_gallery_image',
            'name' => 'image_upload',
            'label' => 'Image Gallery',
            'display' => 'block',
            'sub_fields' => [
              [
                'key' => 'field_ibex_media_gallery_image_file',
                'label' => 'Images',
                'name' => 'image_gallery',
                'type' => 'gallery',
                'required' => 1,
                'return_format' => 'id',
                'preview_size' => 'medium',
                'insert' => 'append',
                'library' => 'all',
                'instructions' => 'Select multiple images at once. Photographer credit applies to all images from the gallery-level field above.',
              ],
            ],
          ],
          'layout_ibex_media_gallery_video_upload' => [
            'key' => 'layout_ibex_media_gallery_video_upload',
            'name' => 'video_upload',
            'label' => 'Video Upload',
            'display' => 'block',
            'sub_fields' => [
              [
                'key' => 'field_ibex_media_gallery_video_file',
                'label' => 'Video',
                'name' => 'video_file',
                'type' => 'file',
                'required' => 1,
                'return_format' => 'id',
                'library' => 'all',
                'mime_types' => 'mp4,webm,mov',
                'instructions' => 'Upload an MP4, MOV, or WEBM video.',
              ],
              [
                'key' => 'field_ibex_media_gallery_video_poster',
                'label' => 'Poster Image',
                'name' => 'poster_image',
                'type' => 'image',
                'return_format' => 'id',
                'preview_size' => 'medium',
                'instructions' => 'Optional fallback image shown before playback.',
              ],
              [
                'key' => 'field_ibex_media_gallery_video_caption',
                'label' => 'Caption',
                'name' => 'caption',
                'type' => 'textarea',
                'rows' => 3,
                'new_lines' => 'br',
                'instructions' => 'Optional caption displayed with the video.',
              ],
              [
                'key' => 'field_ibex_media_gallery_video_photographer',
                'label' => 'Photographer Override',
                'name' => 'photographer_override',
                'type' => 'text',
                'instructions' => 'Leave blank to use the default gallery credit.',
              ],
              [
                'key' => 'field_ibex_media_gallery_video_display_event',
                'label' => 'Display on Event Page',
                'name' => 'display_on_event_page',
                'type' => 'true_false',
                'default_value' => 1,
                'instructions' => 'Uncheck to hide this asset when embedding the gallery on the related event page.',
              ],
            ],
          ],
          'layout_ibex_media_gallery_embed' => [
            'key' => 'layout_ibex_media_gallery_embed',
            'name' => 'video_embed',
            'label' => 'External Embed',
            'display' => 'block',
            'sub_fields' => [
              [
                'key' => 'field_ibex_media_gallery_embed_provider',
                'label' => 'Provider',
                'name' => 'embed_provider',
                'type' => 'select',
                'choices' => [
                  'youtube' => 'YouTube',
                  'vimeo' => 'Vimeo',
                  'other' => 'Other',
                ],
                'default_value' => 'youtube',
                'instructions' => 'Used for iconography/styling when rendering embeds.',
              ],
              [
                'key' => 'field_ibex_media_gallery_embed_url',
                'label' => 'Embed URL',
                'name' => 'embed_url',
                'type' => 'oembed',
                'required' => 1,
                'instructions' => 'Paste a full URL (https://) to the hosted video.',
              ],
              [
                'key' => 'field_ibex_media_gallery_embed_thumbnail',
                'label' => 'Fallback Thumbnail',
                'name' => 'thumbnail_image',
                'type' => 'image',
                'return_format' => 'id',
                'preview_size' => 'medium',
                'instructions' => 'Optional thumbnail if the provider does not supply one.',
              ],
              [
                'key' => 'field_ibex_media_gallery_embed_caption',
                'label' => 'Caption',
                'name' => 'caption',
                'type' => 'textarea',
                'rows' => 3,
                'new_lines' => 'br',
                'instructions' => 'Optional caption displayed with the embed.',
              ],
              [
                'key' => 'field_ibex_media_gallery_embed_photographer',
                'label' => 'Photographer Override',
                'name' => 'photographer_override',
                'type' => 'text',
                'instructions' => 'Leave blank to use the default gallery credit.',
              ],
              [
                'key' => 'field_ibex_media_gallery_embed_display_event',
                'label' => 'Display on Event Page',
                'name' => 'display_on_event_page',
                'type' => 'true_false',
                'default_value' => 1,
                'instructions' => 'Uncheck to hide this asset when embedding the gallery on the related event page.',
              ],
            ],
          ],
        ],
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'media_gallery',
        ],
      ],
    ],
    'position' => 'normal',
    'style' => 'default',
    'label_placement' => 'top',
    'instruction_placement' => 'label',
    'active' => true,
    'description' => 'Date, relationships, and media assets for IBEX media galleries.',
  ]);
});
// v2025-11-11 — Auto-create media gallery draft when requested on event save
add_action('acf/save_post', function ($post_id) {
  if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
    return;
  }

  $post = get_post($post_id);
  if (!$post || $post->post_type !== 'race_event') {
    return;
  }

  if ($post->post_status === 'draft') {
    $can_publish = current_user_can('publish_post', $post_id) || current_user_can('publish_posts');
    wp_update_post([
      'ID'          => $post_id,
      'post_status' => $can_publish ? 'publish' : 'pending',
    ]);
  }

  $featured_image_id = get_field('event_featured_image', $post_id);
  if ($featured_image_id) {
    set_post_thumbnail($post_id, (int) $featured_image_id);
  } elseif (isset($_POST['acf']['field_ibex_event_featured_image'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
    delete_post_thumbnail($post_id);
  }

  $create_gallery = (bool) get_field('create_media_gallery', $post_id);
  delete_field('create_media_gallery', $post_id);

  if (!$create_gallery) {
    return;
  }

  $existing_gallery = get_posts([
    'post_type'      => 'media_gallery',
    'posts_per_page' => 1,
    'fields'         => 'ids',
    'meta_key'       => 'media_gallery_related_event',
    'meta_value'     => $post_id,
  ]);

  if ($existing_gallery) {
    return;
  }

  $author_id     = (int) $post->post_author ?: get_current_user_id();
  $event_title   = get_the_title($post_id);
  $gallery_title = sprintf(
    /* translators: %s: Event title */
    __('%s Media Gallery', 'ibex-racing-child'),
    $event_title
  );

  $gallery_id = wp_insert_post([
    'post_type'   => 'media_gallery',
    'post_title'  => $gallery_title,
    'post_status' => 'publish',
    'post_author' => $author_id,
  ]);

  if (!$gallery_id || is_wp_error($gallery_id)) {
    return;
  }

  $start_date = get_field('event_start_date', $post_id);
  $end_date   = get_field('event_end_date', $post_id);
  $location   = get_field('event_location', $post_id);

  update_field('media_gallery_related_event', $post_id, $gallery_id);
  if ($start_date) {
    update_field('media_gallery_start_date', $start_date, $gallery_id);
  }
  if ($end_date) {
    update_field('media_gallery_end_date', $end_date, $gallery_id);
  }
  if ($location) {
    update_field('media_gallery_location', $location, $gallery_id);
  }

  // Sync default photographer credit if provided via filter.
  $photographer = apply_filters('ibex_event_default_photographer', '', $post_id);
  if ($photographer) {
    update_field('media_gallery_photographer', $photographer, $gallery_id);
  }
}, 20);
add_action('wp_ajax_ibex_event_gallery_details', function () {
  // Verify nonce - use die=false to handle error gracefully
  if (!check_ajax_referer('ibex_event_gallery_lookup', 'nonce', false)) {
    wp_send_json_error(['message' => esc_html__('Security check failed. Please refresh the page and try again.', 'ibex-racing-child')], 403);
    return;
  }

  if (!is_user_logged_in()) {
    wp_send_json_error(['message' => esc_html__('You must be logged in.', 'ibex-racing-child')], 401);
    return;
  }

  $event_id = isset($_POST['eventId']) ? (int) $_POST['eventId'] : 0;
  if (!$event_id) {
    wp_send_json_error(['message' => esc_html__('Missing event ID.', 'ibex-racing-child')], 400);
    return;
  }

  $event = get_post($event_id);
  if (!$event || $event->post_type !== 'race_event') {
    wp_send_json_error(['message' => esc_html__('Invalid event selected.', 'ibex-racing-child')], 404);
    return;
  }

  // Allow access if user can read the event OR can edit media galleries
  // This allows gallery editors to pull event data even if they can't edit the event
  $can_read_event = current_user_can('read_post', $event_id);
  $can_edit_galleries = current_user_can('edit_media_galleries');
  
  if (!$can_read_event && !$can_edit_galleries) {
    wp_send_json_error(['message' => esc_html__('You do not have permission to access this event.', 'ibex-racing-child')], 403);
    return;
  }

  $payload = [
    'start_date' => get_field('event_start_date', $event_id, false) ?: '',
    'start_date_display' => get_field('event_start_date', $event_id) ?: '',
    'end_date'   => get_field('event_end_date', $event_id, false) ?: '',
    'end_date_display' => get_field('event_end_date', $event_id) ?: '',
    'location'   => get_field('event_location', $event_id) ?: '',
  ];

  wp_send_json_success($payload);
});

// AJAX handler for deleting events
add_action('wp_ajax_ibex_delete_event', function () {
  // Verify nonce
  if (!check_ajax_referer('ibex_delete_event', 'nonce', false)) {
    wp_send_json_error(['message' => esc_html__('Security check failed. Please refresh the page and try again.', 'ibex-racing-child')], 403);
    return;
  }

  if (!is_user_logged_in()) {
    wp_send_json_error(['message' => esc_html__('You must be logged in.', 'ibex-racing-child')], 401);
    return;
  }

  $event_id = isset($_POST['event_id']) ? (int) $_POST['event_id'] : 0;
  if (!$event_id) {
    wp_send_json_error(['message' => esc_html__('Missing event ID.', 'ibex-racing-child')], 400);
    return;
  }

  $event = get_post($event_id);
  if (!$event || $event->post_type !== 'race_event') {
    wp_send_json_error(['message' => esc_html__('Invalid event.', 'ibex-racing-child')], 404);
    return;
  }

  $current_user = wp_get_current_user();
  // Check permissions: admin or creator
  $can_delete = current_user_can('manage_options') || (int) $event->post_author === $current_user->ID;
  
  if (!$can_delete) {
    wp_send_json_error(['message' => esc_html__('You do not have permission to delete this event.', 'ibex-racing-child')], 403);
    return;
  }

  // Delete the event (force delete, not trash)
  $deleted = wp_delete_post($event_id, true);
  
  if (!$deleted) {
    wp_send_json_error(['message' => esc_html__('Failed to delete event.', 'ibex-racing-child')], 500);
    return;
  }

  wp_send_json_success([
    'message' => esc_html__('Event deleted successfully.', 'ibex-racing-child'),
    'redirect_url' => esc_url_raw(remove_query_arg(['event_id', 'mode', 'event_submitted'], get_permalink()))
  ]);
});

// AJAX handler for deleting galleries (preserving media files)
add_action('wp_ajax_ibex_delete_gallery', function () {
  // Verify nonce
  if (!check_ajax_referer('ibex_delete_gallery', 'nonce', false)) {
    wp_send_json_error(['message' => esc_html__('Security check failed. Please refresh the page and try again.', 'ibex-racing-child')], 403);
    return;
  }

  if (!is_user_logged_in()) {
    wp_send_json_error(['message' => esc_html__('You must be logged in.', 'ibex-racing-child')], 401);
    return;
  }

  $gallery_id = isset($_POST['gallery_id']) ? (int) $_POST['gallery_id'] : 0;
  if (!$gallery_id) {
    wp_send_json_error(['message' => esc_html__('Missing gallery ID.', 'ibex-racing-child')], 400);
    return;
  }

  $gallery = get_post($gallery_id);
  if (!$gallery || $gallery->post_type !== 'media_gallery') {
    wp_send_json_error(['message' => esc_html__('Invalid gallery.', 'ibex-racing-child')], 404);
    return;
  }

  $current_user = wp_get_current_user();
  // Check permissions: admin or creator
  $can_delete = current_user_can('manage_options') || (int) $gallery->post_author === $current_user->ID;
  
  if (!$can_delete) {
    wp_send_json_error(['message' => esc_html__('You do not have permission to delete this gallery.', 'ibex-racing-child')], 403);
    return;
  }

  // Get media items before deletion (for reference, but we won't delete them)
  // ACF stores attachment IDs in fields, not as child posts, so they're safe
  // But we'll explicitly prevent any attachment deletion just to be sure
  
  // Delete the gallery post (force delete, not trash)
  // Media files (attachments) are NOT deleted because:
  // 1. They're stored as separate posts with post_type 'attachment'
  // 2. ACF stores them as IDs in fields, not as child posts
  // 3. We're not calling wp_delete_attachment() on them
  $deleted = wp_delete_post($gallery_id, true);
  
  if (!$deleted) {
    wp_send_json_error(['message' => esc_html__('Failed to delete gallery.', 'ibex-racing-child')], 500);
    return;
  }

  wp_send_json_success([
    'message' => esc_html__('Gallery deleted successfully. Media files have been preserved.', 'ibex-racing-child'),
    'redirect_url' => esc_url_raw(remove_query_arg(['gallery_id', 'mode', 'gallery_submitted', 'event_id'], get_permalink()))
  ]);
});

// AJAX handler for deleting listings
add_action('wp_ajax_ibex_delete_listing', function () {
  // Verify nonce
  if (!check_ajax_referer('ibex_delete_listing', 'nonce', false)) {
    wp_send_json_error(['message' => esc_html__('Security check failed. Please refresh the page and try again.', 'ibex-racing-child')], 403);
    return;
  }

  if (!is_user_logged_in()) {
    wp_send_json_error(['message' => esc_html__('You must be logged in.', 'ibex-racing-child')], 401);
    return;
  }

  $listing_id = isset($_POST['listing_id']) ? (int) $_POST['listing_id'] : 0;
  if (!$listing_id) {
    wp_send_json_error(['message' => esc_html__('Missing listing ID.', 'ibex-racing-child')], 400);
    return;
  }

  $listing = get_post($listing_id);
  if (!$listing || $listing->post_type !== 'listing') {
    wp_send_json_error(['message' => esc_html__('Invalid listing.', 'ibex-racing-child')], 404);
    return;
  }

  $current_user = wp_get_current_user();
  // Check permissions: admin or creator
  $can_delete = current_user_can('manage_options') || (int) $listing->post_author === $current_user->ID;
  
  if (!$can_delete) {
    wp_send_json_error(['message' => esc_html__('You do not have permission to delete this listing.', 'ibex-racing-child')], 403);
    return;
  }

  // Delete the listing (force delete, not trash)
  // Media files (attachments) are NOT deleted because:
  // 1. They're stored as separate posts with post_type 'attachment'
  // 2. ACF stores them as IDs in fields, not as child posts
  // 3. We're not calling wp_delete_attachment() on them
  $deleted = wp_delete_post($listing_id, true);
  
  if (!$deleted) {
    wp_send_json_error(['message' => esc_html__('Failed to delete listing.', 'ibex-racing-child')], 500);
    return;
  }

  wp_send_json_success([
    'message' => esc_html__('Listing deleted successfully. Media files have been preserved.', 'ibex-racing-child'),
    'redirect_url' => esc_url_raw(remove_query_arg(['listing_id', 'mode', 'listing_submitted'], get_permalink()))
  ]);
});

add_filter('acf/load_value/name=event_featured_image', function ($value, $post_id) {
  if ($value) {
    return $value;
  }

  $thumbnail_id = get_post_thumbnail_id((int) $post_id);
  return $thumbnail_id ?: $value;
}, 10, 2);
add_filter('acf/validate_value/key=field_ibex_media_gallery_related_event', function ($valid, $value, $field, $input) {
  if ($valid !== true) {
    return $valid;
  }

  if (!$value) {
    return $valid;
  }

  // Try multiple methods to get the current post ID
  $current_post_id = 0;
  
  // Method 1: ACF form data (most reliable)
  if (function_exists('acf_get_form_data')) {
    $form_post_id = acf_get_form_data('post_id');
    if ($form_post_id && is_numeric($form_post_id)) {
      $current_post_id = (int) $form_post_id;
    }
  }
  
  // Method 2: POST data
  if ($current_post_id === 0) {
    $post_id_from_post = acf_maybe_get_POST('post_id'); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if ($post_id_from_post && is_numeric($post_id_from_post)) {
      $current_post_id = (int) $post_id_from_post;
    }
  }
  
  // Method 3: Check if we're editing an existing gallery by checking the current related event
  if ($current_post_id === 0 && isset($_GET['gallery_id'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $gallery_id = (int) $_GET['gallery_id']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $gallery = get_post($gallery_id);
    if ($gallery && $gallery->post_type === 'media_gallery') {
      $current_post_id = $gallery_id;
    }
  }

  $query_args = [
    'post_type'      => 'media_gallery',
    'post_status'    => ['publish','draft','pending','future','private'],
    'posts_per_page' => 1,
    'fields'         => 'ids',
    'meta_key'       => 'media_gallery_related_event',
    'meta_value'     => $value,
  ];

  // Exclude the current gallery from the check
  if ($current_post_id > 0) {
    $query_args['post__not_in'] = [$current_post_id];
    
    // Also verify: if the current gallery already has this event assigned, allow it
    $current_gallery_event = get_field('media_gallery_related_event', $current_post_id);
    if ($current_gallery_event && (int) $current_gallery_event === (int) $value) {
      // This gallery already has this event - no conflict
      return $valid;
    }
  }

  $existing = get_posts($query_args);
  if ($existing) {
    return esc_html__('A media gallery already exists for the selected event. Please edit that gallery or choose a different event.', 'ibex-racing-child');
  }

  return $valid;
}, 10, 4);

/**
 * Format an inclusive date range for display.
 */
function ibex_format_date_range(?string $start, ?string $end, string $format = 'F j, Y'): string
{
  if (!$start) {
    return '';
  }

  $start_time = strtotime($start);
  $end_time   = $end ? strtotime($end) : false;

  if (!$start_time) {
    return '';
  }

  $start_label = wp_date($format, $start_time);

  if ($end_time && $end_time !== $start_time) {
    $end_label = wp_date($format, $end_time);
    return sprintf('%s – %s', $start_label, $end_label);
  }

  return $start_label;
}

/**
 * Resolve the first media gallery associated with an event.
 */
function ibex_get_event_gallery(int $event_id, bool $include_editable = false): ?WP_Post
{
  if (!$event_id) {
    return null;
  }

  $statuses = ['publish'];

  if ($include_editable && current_user_can('edit_post', $event_id)) {
    $statuses = ['publish', 'future', 'draft', 'pending', 'private'];
  }

  $galleries = get_posts([
    'post_type'      => 'media_gallery',
    'post_status'    => $statuses,
    'posts_per_page' => 1,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'meta_query'     => [
      [
        'key'   => 'media_gallery_related_event',
        'value' => $event_id,
      ],
    ],
  ]);

  return $galleries ? get_post($galleries[0]) : null;
}

/**
 * Build preview data for gallery assets.
 *
 * @return array<int,array<string,mixed>>
 */
function ibex_get_gallery_preview_items(int $gallery_id, int $limit = 4): array
{
  if (!function_exists('get_field')) {
    return [];
  }

  $items = get_field('media_gallery_items', $gallery_id);
  if (!$items || !is_array($items)) {
    return [];
  }

  $preview = [];

  foreach ($items as $item) {
    if (count($preview) >= $limit) {
      break;
    }

    $layout = $item['acf_fc_layout'] ?? '';
    $display_on_event = array_key_exists('display_on_event_page', $item)
      ? (bool) $item['display_on_event_page']
      : true;

    if (!$display_on_event) {
      continue;
    }

    if ($layout === 'image_upload') {
      $image_gallery = $item['image_gallery'] ?? [];
      if (is_array($image_gallery) && !empty($image_gallery)) {
        foreach ($image_gallery as $image_id) {
          if (!$image_id) {
            continue;
          }
          $preview[] = [
            'type'    => 'image',
            'image'   => wp_get_attachment_image_url((int) $image_id, 'medium_large'),
            'alt'     => trim((string) get_post_meta((int) $image_id, '_wp_attachment_image_alt', true)) ?: get_the_title((int) $image_id),
            'caption' => '',
          ];
          if (count($preview) >= $limit) {
            break 2;
          }
        }
      }
      continue;
    }

    if ($layout === 'video_upload') {
      $poster_id = $item['poster_image'] ?? 0;
      $image     = $poster_id ? wp_get_attachment_image_url($poster_id, 'medium_large') : '';

      if (!$image && !empty($item['video_file'])) {
        $image = wp_get_attachment_image_url($item['video_file'], 'medium_large');
      }

      $preview[] = [
        'type'    => 'video',
        'image'   => $image ?: '',
        'alt'     => $image ? trim((string) get_post_meta($poster_id, '_wp_attachment_image_alt', true)) : __('Video asset', 'ibex-racing-child'),
        'caption' => $item['caption'] ?? '',
      ];
      continue;
    }

    if ($layout === 'video_embed') {
      $thumbnail_id = $item['thumbnail_image'] ?? 0;
      $image        = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium_large') : '';

      $preview[] = [
        'type'      => 'embed',
        'provider'  => $item['embed_provider'] ?? 'other',
        'image'     => $image ?: '',
        'alt'       => $image ? trim((string) get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true)) : __('Embedded video', 'ibex-racing-child'),
        'caption'   => $item['caption'] ?? '',
      ];
      continue;
    }
  }

  return $preview;
}

/**
 * Retrieve the permalink for the first page using a given template.
 */
function ibex_get_page_link_by_template(string $template): ?string
{
  static $cache = [];

  if (array_key_exists($template, $cache)) {
    return $cache[$template];
  }

  $pages = get_pages([
    'number'     => 1,
    'meta_key'   => '_wp_page_template',
    'meta_value' => $template,
  ]);

  $cache[$template] = $pages ? get_permalink($pages[0]->ID) : null;

  return $cache[$template];
}

/**
 * Retrieve dashboard sections available to the current user.
 *
 * @return array<string,array<string,string>>
 */
function ibex_get_dashboard_sections(): array
{
  $sections = [
    'media-galleries' => [
      'label'        => __('Galleries', 'ibex-racing-child'),
      'template'     => 'page-media-gallery-dashboard.php',
      'capabilities' => ['edit_media_galleries'],
    ],
    'listings' => [
      'label'        => __('For Sale', 'ibex-racing-child'),
      'template'     => 'page-listing-dashboard.php',
      'capabilities' => ['edit_listings'],
    ],
    'events' => [
      'label'        => __('Events', 'ibex-racing-child'),
      'template'     => 'page-event-dashboard.php',
      'capabilities' => ['edit_race_events'],
    ],
    'team' => [
      'label'        => __('Team Profiles', 'ibex-racing-child'),
      'template'     => 'page-team-dashboard.php',
      'capabilities' => ['edit_posts'],
    ],
  ];

  /**
   * Filter the available dashboard sections.
   *
   * @param array $sections
   */
  $sections = apply_filters('ibex_dashboard_sections', $sections);

  $available = [];

  foreach ($sections as $key => $section) {
    $key = sanitize_key($key);
    if (!$key) {
      continue;
    }

    $capabilities = isset($section['capabilities']) ? (array) $section['capabilities'] : [];
    $has_access   = true;

    foreach ($capabilities as $cap) {
      if (!current_user_can($cap)) {
        $has_access = false;
        break;
      }
    }

    if (!$has_access) {
      continue;
    }

    $url = $section['url'] ?? '';

    if (!$url && !empty($section['template'])) {
      $url = ibex_get_page_link_by_template((string) $section['template']);
    }

    if (!$url) {
      continue;
    }

    $available[$key] = [
      'label' => isset($section['label']) ? wp_strip_all_tags((string) $section['label']) : ucfirst($key),
      'url'   => esc_url($url),
    ];
  }

  return $available;
}

/**
 * Determine if current request is rendering a front-end dashboard template.
 */
function ibex_is_frontend_dashboard(): bool
{
  if (is_admin()) {
    return false;
  }

  $templates = [
    'page-media-gallery-dashboard.php',
    'page-listing-dashboard.php',
    'page-event-dashboard.php',
    'page-team-dashboard.php',
  ];

  foreach ($templates as $template) {
    if (is_page_template($template)) {
      return true;
    }
  }

  return false;
}

/**
 * Shared renderer for Ibex front-end dashboards.
 *
 * @param array<string,mixed> $args
 * @param callable            $content_callback
 */
function ibex_render_dashboard(array $args, callable $content_callback): void
{
  $defaults = [
    'page_title' => '',
    'page_intro' => '',
    'active_key' => '',
    'actions'    => [],
  ];

  $settings  = wp_parse_args($args, $defaults);
  $sections  = ibex_get_dashboard_sections();
  $active_key = sanitize_key($settings['active_key']);

  if (!$active_key) {
    $active_key = array_key_first($sections) ?: '';
  }

  $actions = array_filter(array_map(static function ($action) {
    if (empty($action['label']) || empty($action['url'])) {
      return null;
    }

    return [
      'label'   => wp_strip_all_tags((string) $action['label']),
      'url'     => esc_url($action['url']),
      'variant' => isset($action['variant']) ? sanitize_html_class((string) $action['variant']) : 'primary',
    ];
  }, (array) $settings['actions']));

  ?>
  <div class="ibex-dashboard">
    <?php if (!empty($settings['page_title']) || $actions) : ?>
      <div class="ibex-dashboard__masthead">
        <?php if (!empty($settings['page_title'])) : ?>
          <div class="ibex-dashboard__heading">
            <h1 class="ibex-dashboard__title"><?php echo esc_html($settings['page_title']); ?></h1>
            <?php if (!empty($settings['page_intro'])) : ?>
              <div class="ibex-dashboard__intro">
                <?php echo wp_kses_post($settings['page_intro']); ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if ($actions) : ?>
          <div class="ibex-dashboard__actions">
            <?php foreach ($actions as $action) : ?>
              <?php
              $classes = ['ibex-dashboard__action', 'ibex-button'];
              if ($action['variant'] === 'outline') {
                $classes[] = 'ibex-button--outline';
              } elseif ($action['variant'] === 'ghost') {
                $classes[] = 'ibex-button--ghost';
              }
              ?>
              <a class="<?php echo esc_attr(implode(' ', $classes)); ?>" href="<?php echo esc_url($action['url']); ?>">
                <?php echo esc_html($action['label']); ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($sections)) : ?>
      <nav class="ibex-dashboard__nav" aria-label="<?php echo esc_attr__('Dashboard sections', 'ibex-racing-child'); ?>">
        <ul class="ibex-dashboard__nav-list">
          <?php foreach ($sections as $key => $section) : ?>
            <?php
            $item_classes = ['ibex-dashboard__nav-item'];
            if ($key === $active_key) {
              $item_classes[] = 'is-active';
            }
            ?>
            <li class="<?php echo esc_attr(implode(' ', $item_classes)); ?>">
              <a class="ibex-dashboard__nav-link" href="<?php echo esc_url($section['url']); ?>"<?php echo $key === $active_key ? ' aria-current="page"' : ''; ?>>
                <?php echo esc_html($section['label']); ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </nav>
    <?php endif; ?>

    <div class="ibex-dashboard__content">
      <?php call_user_func($content_callback, $settings, $sections); ?>
    </div>
  </div>
  <?php
}

/**
 * Map of social icons used for team member profiles.
 *
 * @return array<string,string>
 */
function ibex_get_team_social_icon_map(): array
{
  return [
    'instagram' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 2C4.243 2 2 4.243 2 7v10c0 2.757 2.243 5 5 5h10c2.757 0 5-2.243 5-5V7c0-2.757-2.243-5-5-5H7zm0 2h10c1.654 0 3 1.346 3 3v10c0 1.654-1.346 3-3 3H7c-1.654 0-3-1.346-3-3V7c0-1.654 1.346-3 3-3zm5 3a5 5 0 100 10 5 5 0 000-10zm0 2a3 3 0 110 6 3 3 0 010-6zm6.5-.25a1.25 1.25 0 11-2.5 0 1.25 1.25 0 012.5 0z"/></svg>',
    'facebook'  => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M14 3c-2.8 0-4.5 1.8-4.5 4.7V10H7v4h2.5v7h4v-7H17l.5-4h-4V7.9c0-.9.4-1.4 1.5-1.4H18V3.2A33.6 33.6 0 0014 3z"/></svg>',
    'youtube'   => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M21.8 8s-.2-1.5-.8-2.1c-.6-.7-1.2-.9-2-.9C16.4 5 12 5 12 5s-4.4 0-7 .1c-.8 0-1.4.2-2 .9C2.4 6.5 2.2 8 2.2 8S2 9.7 2 11.4v1.3c0 1.7.2 3.4.2 3.4s.2 1.5.8 2.1c.6.7 1.4.8 2.2.9 1.6.2 6.8.2 6.8.2s4.4 0 7-.2c.8 0 1.4-.2 2-.9.6-.6.8-2.1.8-2.1s.2-1.7.2-3.4v-1.3C22 9.7 21.8 8 21.8 8zM10 14.6V9.4l5 2.6-5 2.6z"/></svg>',
    'x'         => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M19.5 4.5h-2.5l-4 5.4-4.4-5.4H4.5l6.4 7.6-6 7.9h2.5l4.5-6 4.9 6h3.2l-6.6-8.1 5.1-6.4z"/></svg>',
    'tiktok'    => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M19 7.5c-1.5-.1-3-.7-4.1-1.8V15a4.5 4.5 0 11-4.5-4.5c.3 0 .6 0 .9.1V7.7a8 8 0 00-.9-.1A6.5 6.5 0 1017 13V8.8c1.1.8 2.4 1.3 3.8 1.4V7.6a5 5 0 01-1.8-.1z"/></svg>',
  ];
}

/**
 * Human-readable labels for team member social platforms.
 *
 * @return array<string,string>
 */
function ibex_get_team_social_label_map(): array
{
  return [
    'instagram' => __('Instagram', 'ibex-racing-child'),
    'facebook'  => __('Facebook', 'ibex-racing-child'),
    'youtube'   => __('YouTube', 'ibex-racing-child'),
    'x'         => __('X (Twitter)', 'ibex-racing-child'),
    'tiktok'    => __('TikTok', 'ibex-racing-child'),
  ];
}

/**
 * Collect social links for a team member with label and icon metadata.
 *
 * @return array<int,array{platform:string,url:string,label:string,icon:string}>
 */
function ibex_get_team_social_links(int $team_id): array
{
  if (!$team_id || !function_exists('get_field')) {
    return [];
  }

  $fields = [
    'instagram' => get_field('team_member_instagram', $team_id),
    'facebook'  => get_field('team_member_facebook', $team_id),
    'youtube'   => get_field('team_member_youtube', $team_id),
    'x'         => get_field('team_member_x', $team_id),
    'tiktok'    => get_field('team_member_tiktok', $team_id),
  ];

  $icons  = ibex_get_team_social_icon_map();
  $labels = ibex_get_team_social_label_map();
  $links  = [];

  foreach ($fields as $platform => $url) {
    if (!is_string($url)) {
      continue;
    }

    $url = trim($url);
    if ($url === '') {
      continue;
    }

    $links[] = [
      'platform' => $platform,
      'url'      => $url,
      'label'    => $labels[$platform] ?? ucfirst($platform),
      'icon'     => $icons[$platform] ?? '',
    ];
  }

  return $links;
}

add_action('acf/save_post', function ($post_id) {
  if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
    return;
  }

  $post = get_post($post_id);
  if (!$post || $post->post_type !== 'media_gallery') {
    return;
  }

  // Update post author if creator field is set
  // Check POST data first (during save), then fallback to get_field
  $creator_id = null;
  if (isset($_POST['acf']['field_ibex_media_gallery_author'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $creator_id = (int) $_POST['acf']['field_ibex_media_gallery_author']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
  } else {
    $creator_id = get_field('media_gallery_author', $post_id);
    if ($creator_id) {
      $creator_id = (int) $creator_id;
    }
  }

  if ($creator_id && $creator_id > 0) {
    // Only update if user exists and current user has permission
    $user = get_userdata($creator_id);
    if ($user && (current_user_can('edit_others_media_galleries') || current_user_can('manage_options'))) {
      wp_update_post([
        'ID' => $post_id,
        'post_author' => $creator_id,
      ]);
    }
  }

  $cover_image_id = get_field('media_gallery_cover_image', $post_id);
  if ($cover_image_id) {
    set_post_thumbnail($post_id, (int) $cover_image_id);
  } elseif (isset($_POST['acf']['field_ibex_media_gallery_cover_image'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
    delete_post_thumbnail($post_id);
  }
}, 25);

add_filter('acf/load_value/name=media_gallery_cover_image', function ($value, $post_id) {
  if ($value) {
    return $value;
  }

  $thumbnail_id = get_post_thumbnail_id((int) $post_id);
  return $thumbnail_id ?: $value;
}, 10, 2);

// v2025-01-XX — FileBird integration for organized media folders
/**
 * Create a FileBird folder for a post (gallery or team member).
 * 
 * @param int    $post_id Post ID
 * @param string $folder_name Folder name (will be sanitized)
 * @return int|false Folder ID on success, false on failure
 */
function ibex_create_filebird_folder(int $post_id, string $folder_name): int|false {
  // Check if FileBird is active
  if (!class_exists('FileBird\Folder') && !function_exists('njt_fb_create_folder') && !taxonomy_exists('nt_wmc_folder')) {
    return false;
  }

  // Sanitize folder name (shorten if too long, remove special chars)
  $sanitized_name = sanitize_file_name($folder_name);
  // Limit to 50 characters to keep folder names manageable
  if (mb_strlen($sanitized_name) > 50) {
    $sanitized_name = mb_substr($sanitized_name, 0, 47) . '...';
  }

  // Check if folder already exists for this post
  $existing_folder_id = ibex_get_filebird_folder_id($post_id);
  if ($existing_folder_id) {
    return $existing_folder_id;
  }

  try {
    // Method 1: FileBird class-based API (newer versions)
    if (class_exists('FileBird\Folder')) {
      if (method_exists('FileBird\Folder', 'createFolder')) {
        $folder_id = \FileBird\Folder::createFolder($sanitized_name, 0);
        if ($folder_id) {
          update_post_meta($post_id, '_ibex_filebird_folder_id', (int) $folder_id);
          return (int) $folder_id;
        }
      }
    }
    
    // Method 2: FileBird function-based API
    if (function_exists('njt_fb_create_folder')) {
      $folder_id = njt_fb_create_folder($sanitized_name, 0);
      if ($folder_id) {
        update_post_meta($post_id, '_ibex_filebird_folder_id', (int) $folder_id);
        return (int) $folder_id;
      }
    }
    
    // Method 3: Direct taxonomy approach (FileBird uses 'nt_wmc_folder' taxonomy)
    if (taxonomy_exists('nt_wmc_folder')) {
      // Check if folder with this name already exists
      $existing_term = get_term_by('name', $sanitized_name, 'nt_wmc_folder');
      if ($existing_term) {
        update_post_meta($post_id, '_ibex_filebird_folder_id', (int) $existing_term->term_id);
        return (int) $existing_term->term_id;
      }
      
      $term = wp_insert_term($sanitized_name, 'nt_wmc_folder');
      if (!is_wp_error($term) && isset($term['term_id'])) {
        update_post_meta($post_id, '_ibex_filebird_folder_id', (int) $term['term_id']);
        return (int) $term['term_id'];
      }
    }
    
    // Method 4: Try FileBird's action hook
    do_action('filebird_create_folder', $sanitized_name, 0, $post_id);
    
  } catch (Exception $e) {
    // Log error but don't break the save process
    error_log('FileBird folder creation failed: ' . $e->getMessage());
    return false;
  }

  return false;
}

/**
 * Get FileBird folder ID for a post.
 */
function ibex_get_filebird_folder_id(int $post_id): ?int {
  $folder_id = get_post_meta($post_id, '_ibex_filebird_folder_id', true);
  return $folder_id ? (int) $folder_id : null;
}

// Create FileBird folder when media gallery is saved
add_action('acf/save_post', function ($post_id) {
  if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
    return;
  }

  $post = get_post($post_id);
  if (!$post) {
    return;
  }

  // Handle media gallery
  if ($post->post_type === 'media_gallery') {
    // Check if folder already exists
    $existing_folder_id = ibex_get_filebird_folder_id($post_id);
    if ($existing_folder_id) {
      return; // Folder already created
    }

    // Create folder based on gallery title
    $gallery_title = get_the_title($post_id);
    if ($gallery_title) {
      ibex_create_filebird_folder($post_id, $gallery_title);
    }
  }

  // Handle team member
  if ($post->post_type === 'team_member') {
    // Check if folder already exists
    $existing_folder_id = ibex_get_filebird_folder_id($post_id);
    if ($existing_folder_id) {
      return; // Folder already created
    }

    // Create folder based on team member name
    $member_name = get_the_title($post_id);
    if ($member_name) {
      ibex_create_filebird_folder($post_id, $member_name);
    }
  }
}, 30); // Run after other save_post hooks

// Filter ACF media library to default to the relevant folder
add_filter('acf/fields/gallery/query', function ($args, $field, $post_id) {
  // Only filter for media gallery items field
  if ($field['name'] !== 'media_gallery_items') {
    return $args;
  }

  // Get the gallery's folder ID
  $gallery_id = $post_id;
  if (!$gallery_id || get_post_type($gallery_id) !== 'media_gallery') {
    // Try to get gallery ID from form context
    if (function_exists('acf_get_form_data')) {
      $form_post_id = acf_get_form_data('post_id');
      if ($form_post_id && get_post_type($form_post_id) === 'media_gallery') {
        $gallery_id = (int) $form_post_id;
      }
    }
  }

  if ($gallery_id) {
    $folder_id = ibex_get_filebird_folder_id($gallery_id);
    if ($folder_id) {
      // FileBird filters attachments by folder using meta query
      // FileBird stores folder assignment in attachment meta: '_filebird_folder' or 'nt_wmc_folder'
      if (!isset($args['meta_query'])) {
        $args['meta_query'] = [];
      }
      
      // Try different meta keys FileBird might use
      $args['meta_query'][] = [
        'relation' => 'OR',
        [
          'key' => '_filebird_folder',
          'value' => $folder_id,
          'compare' => '=',
        ],
        [
          'key' => 'nt_wmc_folder',
          'value' => $folder_id,
          'compare' => '=',
        ],
      ];
    }
  }

  return $args;
}, 10, 3);

// Filter ACF media library queries to default to the relevant folder
// Note: This sets the initial view to the folder, but users can still navigate to other folders via FileBird's UI
add_filter('ajax_query_attachments_args', function ($query) {
  // Get current post being edited
  $current_post_id = 0;
  
  // Try to get from ACF form context
  if (function_exists('acf_get_form_data')) {
    $form_post_id = acf_get_form_data('post_id');
    if ($form_post_id && is_numeric($form_post_id)) {
      $current_post_id = (int) $form_post_id;
    }
  }

  // Try to get from POST data (ACF media picker)
  if (!$current_post_id && isset($_POST['post_id']) && is_numeric($_POST['post_id'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $current_post_id = (int) $_POST['post_id']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
  }

  // Try to get from GET parameter (some ACF contexts)
  if (!$current_post_id && isset($_GET['post_id']) && is_numeric($_GET['post_id'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $current_post_id = (int) $_GET['post_id']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
  }

  if ($current_post_id) {
    $post_type = get_post_type($current_post_id);
    $folder_id = null;

    // Get folder ID based on post type
    if ($post_type === 'media_gallery' || $post_type === 'team_member') {
      $folder_id = ibex_get_filebird_folder_id($current_post_id);
    }

    if ($folder_id) {
      // FileBird stores folder assignments in attachment meta or taxonomy
      // We'll set the initial filter, but FileBird's UI will still allow navigation
      
      // Method 1: Taxonomy-based (FileBird uses 'nt_wmc_folder' taxonomy)
      if (taxonomy_exists('nt_wmc_folder')) {
        if (!isset($query['tax_query'])) {
          $query['tax_query'] = [];
        }
        // Add folder filter - this will show files in this folder by default
        $query['tax_query'][] = [
          'taxonomy' => 'nt_wmc_folder',
          'field' => 'term_id',
          'terms' => $folder_id,
        ];
      } else {
        // Method 2: Meta-based
        if (!isset($query['meta_query'])) {
          $query['meta_query'] = [];
        }
        $query['meta_query'][] = [
          'relation' => 'OR',
          [
            'key' => '_filebird_folder',
            'value' => $folder_id,
            'compare' => '=',
          ],
          [
            'key' => 'nt_wmc_folder',
            'value' => $folder_id,
            'compare' => '=',
          ],
        ];
      }
      
      // Store folder ID in query for FileBird to use
      $query['filebird_folder'] = $folder_id;
    }
  }

  return $query;
}, 10, 1);

// Also filter when attachments are uploaded - assign to folder automatically
add_filter('wp_generate_attachment_metadata', function ($metadata, $attachment_id) {
  // Get the current post context
  $current_post_id = 0;
  
  if (function_exists('acf_get_form_data')) {
    $form_post_id = acf_get_form_data('post_id');
    if ($form_post_id && is_numeric($form_post_id)) {
      $current_post_id = (int) $form_post_id;
    }
  }

  // Try to get from POST data
  if (!$current_post_id && isset($_POST['post_id']) && is_numeric($_POST['post_id'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $current_post_id = (int) $_POST['post_id']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
  }

  if ($current_post_id) {
    $post_type = get_post_type($current_post_id);
    
    if ($post_type === 'media_gallery' || $post_type === 'team_member') {
      $folder_id = ibex_get_filebird_folder_id($current_post_id);
      
      if ($folder_id) {
        // Assign attachment to folder
        // FileBird uses taxonomy or meta
        if (taxonomy_exists('nt_wmc_folder')) {
          wp_set_object_terms($attachment_id, [$folder_id], 'nt_wmc_folder');
        } else {
          update_post_meta($attachment_id, '_filebird_folder', $folder_id);
          update_post_meta($attachment_id, 'nt_wmc_folder', $folder_id);
        }
      }
    }
  }

  return $metadata;
}, 10, 2);

// v2025-01-XX — Header social media icons
/**
 * Render social media icons in the header next to the logo.
 */
function ibex_render_header_social_icons(): void
{
  // Prevent duplicate output if multiple hooks fire
  static $rendered = false;
  if ($rendered) {
    return;
  }
  $rendered = true;

  if (!function_exists('ibex_get_team_social_icon_map') || !function_exists('ibex_get_team_social_label_map')) {
    return;
  }

  $icons = ibex_get_team_social_icon_map();
  $links = [
    'instagram' => 'https://www.instagram.com/ibexracing',
    'youtube'   => 'https://www.youtube.com/@ibexracing1874/videos',
  ];

  $labels = ibex_get_team_social_label_map();

  if (empty($icons) || empty($links)) {
    return;
  }

  ?>
  <div class="ibex-header-social">
    <?php foreach ($links as $platform => $url) : ?>
      <?php if (!empty($icons[$platform]) && !empty($url)) : ?>
        <a
          class="ibex-header-social__link"
          href="<?php echo esc_url($url); ?>"
          target="_blank"
          rel="noopener noreferrer"
          aria-label="<?php echo esc_attr($labels[$platform] ?? ucfirst($platform)); ?>"
        >
          <span class="ibex-header-social__icon">
            <?php echo $icons[$platform]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
          </span>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
  <?php
}

// Add social icons in a vertical stack to the left of the logo
// Using generate_before_logo hook to place icons before the logo
add_action('generate_before_logo', 'ibex_render_header_social_icons', 15);

// v2025-01-XX — Frontend-only login redirects
/**
 * Redirect all logins to the frontend instead of wp-admin.
 * This keeps users on the site and shields them from the WordPress backend.
 */
add_filter('login_redirect', function ($redirect_to, $requested_redirect_to, $user) {
  // If a specific redirect was requested and it's safe, use it
  if ($requested_redirect_to && filter_var($requested_redirect_to, FILTER_VALIDATE_URL)) {
    $parsed = wp_parse_url($requested_redirect_to);
    // Allow frontend redirects only (not wp-admin)
    if (!empty($parsed['path']) && strpos($parsed['path'], '/wp-admin') === false) {
      return esc_url_raw($requested_redirect_to);
    }
  }

  // Default: redirect to homepage (or first dashboard section if available)
  $dashboard_url = ibex_get_page_link_by_template('page-media-gallery-dashboard.php');
  if ($dashboard_url && is_user_logged_in()) {
    // Check if user has access to at least one dashboard section
    $sections = ibex_get_dashboard_sections();
    if (!empty($sections)) {
      return esc_url_raw(reset($sections)['url']);
    }
  }

  return home_url('/');
}, 10, 3);

/**
 * Block non-administrators from accessing wp-admin entirely.
 * Administrators can still access the backend when needed.
 */
add_action('admin_init', function () {
  // Allow administrators full access
  if (current_user_can('manage_options')) {
    return;
  }

  // Allow AJAX requests to pass through (needed for frontend functionality)
  if (wp_doing_ajax()) {
    return;
  }

  // Redirect everyone else to the homepage
  wp_safe_redirect(home_url('/'));
  exit;
}, 1);

/**
 * Redirect the default WordPress login URL (wp-login.php) to the custom login page.
 * This ensures users always use the branded frontend login experience.
 */
add_filter('login_url', function ($login_url, $redirect) {
  $login_page = ibex_get_page_link_by_template('page-login.php');
  
  if ($login_page) {
    $login_url = $login_page;
    
    // Append redirect parameter if provided
    if ($redirect) {
      $login_url = add_query_arg('redirect_to', urlencode($redirect), $login_url);
    }
  }
  
  return $login_url;
}, 10, 2);

/**
 * Redirect password reset/lost password URLs to the frontend login page.
 * This keeps password reset flows on the frontend.
 */
add_filter('lostpassword_url', function ($lostpassword_url, $redirect) {
  $login_page = ibex_get_page_link_by_template('page-login.php');
  
  if ($login_page) {
    $lostpassword_url = add_query_arg('action', 'lostpassword', $login_page);
    
    if ($redirect) {
      $lostpassword_url = add_query_arg('redirect_to', urlencode($redirect), $lostpassword_url);
    }
  }
  
  return $lostpassword_url;
}, 10, 2);

/**
 * Modify password reset email links to point to the frontend login page.
 * When users click "Reset Password" in emails, they'll go to your custom login page.
 */
add_filter('retrieve_password_message', function ($message, $key, $user_login, $user_data) {
  $login_page = ibex_get_page_link_by_template('page-login.php');
  
  if ($login_page) {
    // Build reset link pointing to frontend login page
    $reset_link = add_query_arg(
      [
        'action'     => 'rp',
        'key'        => $key,
        'login'      => rawurlencode($user_login),
      ],
      $login_page
    );
    
    // Replace the default wp-login.php reset link with frontend link
    $message = str_replace(
      network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login)),
      $reset_link,
      $message
    );
  }
  
  return $message;
}, 10, 4);

/**
 * Redirect users after successful password reset to the frontend (not wp-admin).
 * This completes the frontend-only login experience.
 */
add_filter('password_reset_redirect', function ($redirect_to, $requested_redirect_to) {
  // If a frontend redirect was requested, use it
  if ($requested_redirect_to && filter_var($requested_redirect_to, FILTER_VALIDATE_URL)) {
    $parsed = wp_parse_url($requested_redirect_to);
    if (!empty($parsed['path']) && strpos($parsed['path'], '/wp-admin') === false) {
      return esc_url_raw($requested_redirect_to);
    }
  }
  
  // Default: redirect to login page with success message
  $login_page = ibex_get_page_link_by_template('page-login.php');
  if ($login_page) {
    return add_query_arg('password-reset', 'success', $login_page);
  }
  
  return home_url('/');
}, 10, 2);

/**
 * Customize new user welcome emails to use frontend login/reset links.
 * When you create users in WP Admin, their welcome emails will point to the frontend.
 */
add_filter('wp_new_user_notification_email', function ($wp_new_user_notification_email, $user, $blogname) {
  $login_page = ibex_get_page_link_by_template('page-login.php');
  
  if ($login_page && isset($wp_new_user_notification_email['message'])) {
    // Generate a password reset key for the new user
    $key = get_password_reset_key($user);
    if (!is_wp_error($key)) {
      $reset_link = add_query_arg(
        [
          'action' => 'rp',
          'key'    => $key,
          'login'  => rawurlencode($user->user_login),
        ],
        $login_page
      );
      
      // Replace wp-login.php links with frontend login page links
      $wp_new_user_notification_email['message'] = str_replace(
        network_site_url('wp-login.php'),
        $login_page,
        $wp_new_user_notification_email['message']
      );
      
      // Replace password reset links with frontend version
      if (strpos($wp_new_user_notification_email['message'], 'wp-login.php?action=rp') !== false) {
        $old_reset_pattern = network_site_url('wp-login.php?action=rp');
        $wp_new_user_notification_email['message'] = preg_replace(
          '#(' . preg_quote($old_reset_pattern, '#') . '[^\s<>"\']+)#',
          $reset_link,
          $wp_new_user_notification_email['message']
        );
      }
    }
  }
  
  return $wp_new_user_notification_email;
}, 10, 3);

