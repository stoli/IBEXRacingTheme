# Events & Galleries

## Event Post Type (`race_event`)
- Registered in `wp-content/themes/ibex-racing-child/functions.php` with archive slug `events`, REST support enabled, and UI icon `dashicons-calendar-alt`.
- Supports core fields: title, content/editor, featured image, excerpt.
- Custom fields provided via ACF group `group_ibex_event_details`.

## Event Metadata (ACF)
- `event_start_date` (required) — primary date for ordering and determining “upcoming” status; stored as `Y-m-d`, displayed as `F j, Y`.
- `event_end_date` (optional) — `Y-m-d`; when empty the event is treated as single-day.
- `event_location` (required) — freeform location text shown on cards/templates.
- `event_registration_url` and `event_registration_label` (optional) — drive CTA button.
- `event_summary` (optional) — short teaser; falls back to excerpt when empty.

## Event Templates & Usage
- Archive view handled by `archive-race_event.php`; splits upcoming vs past based on start/end dates and sorts accordingly.
- Single event page uses `single-race_event.php`; surfaces metadata, registration CTA, and back-to-archive link.
- Front page (`front-page.php`) pulls top three upcoming events (fills from past if needed) for the home section.

## Media Gallery Requirements (in progress)
- Gallery index should display cover image, title, and event date (single day or range).
- Optional association to an event: when linked, show CTA back to the event detail page; also surface the gallery on the event page whenever a link exists.
- Need gallery post type (working name `media_gallery`) with fields for:
  - Cover/hero image.
  - Date range (single day by default).
  - Relationship field pointing to an optional `race_event`.
  - Gallery description and per-asset captions.
  - Location tags (default inherit from linked event, override per gallery if needed).
  - Photographer credits.
  - Download toggle (per gallery and/or per asset).
- Assets should support mixed media:
  - Direct image/video uploads stored in the media library for playback.
  - External embeds (e.g., YouTube/Vimeo) captured via URL or oEmbed.

## Creation & Permissions Workflow
- A limited set of authenticated users can create events and media galleries; each item is owned by its creator.
- Deletion restricted to the original author or administrators (need to enforce via capabilities or custom checks).
- Event creation flow should offer an option to auto-create and link a new gallery simultaneously.
- Gallery creation flow should allow selecting an existing event (or none) and optionally backfill event metadata.
- Support both front-end forms and classic WP admin; single-page front-end form is acceptable if UX meets needs.
- Allow image uploads (JPG, PNG) and common video formats; no size limit enforced initially.
- Downloads are open to all visitors (no gating by role).

## Display & UX
- Gallery listing page uses grid layout on desktop; mobile can switch to carousel while retaining grid preview.
- Individual gallery view: start with grid thumbnails; clicking an item opens a carousel/lightbox supporting both images and videos.
- Ensure download option respects per-asset flags; surface photographer credit near media captions.
- On linked event pages, surface the associated gallery (hero thumbnail + CTA, or inline grid) once populated.

## Automation & Front-End Creation
- Goal: allow authenticated users to create events/galleries via the site UI using REST endpoints or custom forms.
- When an event is created (admin or front end), auto-generate a paired gallery stub tied to that event ID if requested by the creator.
- Define workflow for uploading gallery assets post-event, including media limits, accepted formats, and moderation.

## Field Structure (`media_gallery`)

### Base Fields (post-level)
- `media_gallery_cover_image` (featured image) — required hero.
- `media_gallery_start_date` (date) — required; aligns with event start.
- `media_gallery_end_date` (date) — optional.
- `media_gallery_related_event` (post object) — optional link to `race_event`.
- `media_gallery_location` (text) — defaults from related event, editable.
- `media_gallery_overview` (wysiwyg/textarea) — gallery description.
- `media_gallery_photographer` (text) — default credit applied to all assets (can be overridden per item).
- `media_gallery_allow_downloads` (true/false) — global toggle (defaults to true).
- `media_gallery_additional_notes` (textarea) — internal notes/log (non public).

### Asset Collection (ACF Flexible Content Repeater)
- Field key: `media_gallery_items` (flexible content, ordered list).
- Layouts:
  - **Image Upload**
    - `item_type` (hidden) → `image_upload`.
    - `image_file` (image upload, required).
    - `caption` (textarea).
    - `photographer_override` (text, optional).
    - `download_allowed` (true/false, defaults to inherit global toggle).
    - `is_featured` (true/false) — optional; could drive hero selection if no cover set.
  - **Video Upload**
    - `item_type` → `video_upload`.
    - `video_file` (file upload; restrict to mp4, mov, webm, etc.).
    - `poster_image` (image upload, optional for playback preview).
    - `caption` (textarea).
    - `photographer_override` (text).
    - `download_allowed` (true/false).
  - **External Embed**
    - `item_type` → `video_embed`.
    - `embed_provider` (select: YouTube, Vimeo, Other).
    - `embed_url` (url, required).
    - `caption` (textarea).
    - `photographer_override` (text).
    - `thumbnail_image` (image upload, optional fallback if oEmbed thumbnail unavailable).
    - `download_allowed` (true/false, defaults false).
- Shared meta:
  - `display_order` managed by flexible content order.
  - `display_on_event_page` (true/false) — optionally hide specific assets from event embed.

### Taxonomy / Tags
- Leverage existing WP tags or register `media_location` taxonomy if granular location filtering needed.
- Consider `media_keywords` taxonomy for filtering (optional future enhancement).

### Front-End Form Mapping
- Single-page form with sections:
  1. Gallery basics (title, cover, dates, related event, location, overview).
  2. Photographer & download settings.
  3. Asset builder (dynamic repeater with add-image, add-video, add-embed buttons).
  4. Review & submit (confirm download policy, preview sort order).
- Validation:
  - At least one asset required.
  - If related event chosen, auto-fill start/end/location defaults but allow override prior to submit.
  - Enforce file type constraints client- and server-side.

## Asset Field Implementation Options
- **ACF Gallery Field + Extras**
  - Pros: quick setup, native UI familiar to editors, handles image uploads out of the box.
  - Cons: limited support for mixed media—requires custom hooks to store/play videos or embeds; less granular per-asset metadata (would need sub-fields).
  - Possible enhancement: use ACF Gallery for images and add a repeater for external video embeds.
- **Custom Repeater (ACF Flexible Content or custom metabox) — Selected**
  - Pros: precise control over each media item (type selector: image upload, video upload, external embed), rich metadata per item (captions, photographer credit, download toggle).
  - Cons: more development effort; front-end form needs custom UI for uploads and embed handling.
  - Recommended if mixed media and per-item fields are critical from day one.
- **Custom DB/Table or CPT for Media Items**
  - Pros: scalable for very large galleries, reusable items across galleries, REST-ready records.
  - Cons: highest complexity; likely overkill unless galleries become large/interactive objects.

## Open Questions
- Finalize archive layout for `media_gallery` (grid hero, filters, pagination).
- Evaluate need for lightbox/carousel experience on gallery detail pages.
- Decide on download logging/licensing notices and potential watermark workflow.
- Plan moderation or approval flow for user-generated media prior to publish.

## Front-End Implementation (2025-11-11 → 2025-11-15)

### Dashboard Templates
- **Event Dashboard** (`page-event-dashboard.php` + `template-parts/dashboard/events.php`): Logged-in users create/edit events via front-end ACF form; hero image syncs with featured image; submissions auto-publish when permissions allow; optional toggle creates linked gallery stub.
- **Media Gallery Dashboard** (`page-media-gallery-dashboard.php` + `template-parts/dashboard/media-galleries.php`): Full-featured gallery builder with:
  - Two-column layout: sidebar for gallery list/navigation, main panel for editing
  - ACF flexible content for mixed media items (image uploads, video uploads, external embeds via oEmbed)
  - Event selection auto-populates dates/location via AJAX (`ibex-media-gallery-form.js`)
  - Native ACF controls enhanced with custom styling (`ibex-dashboard-flex.js`)
  - Submissions publish immediately with proper permissions

### Dashboard Styling (2025-11-15)
- **Dark Theme Implementation**: Complete dark mode styling for media gallery dashboard matching site brand
  - ACF flexible content toolbar: custom-styled native controls (Add, Clone, Delete, Collapse/Expand) with orange accent colors
  - Button styling: rounded pill buttons with orange background, dark text, hover effects
  - Layout controls: dark backgrounds, proper icon visibility, colored badges (red for delete, dark for collapse)
  - Form fields: dark-themed inputs, textareas, and WYSIWYG editor (Text mode)
  - oEmbed previews: dark background for YouTube/Vimeo embeds
- **Responsive Design**: 
  - Desktop: full-width two-column layout (sidebar + main content)
  - Mobile (<62rem/992px): stacks to single column, sidebar on top
  - Flexible content toolbar: stacks vertically on mobile with reduced font sizes
  - Buttons and controls scale appropriately for touch targets
- **Custom JavaScript**: 
  - `ibex-dashboard-flex.js`: Enhances ACF flexible content native controls with helper classes and labels
  - Syncs collapse/expand button states with layout visibility
  - Skips ACF clone templates to avoid duplicate styling
  - Debug logging for troubleshooting selector issues

### Public Views
- **Event Single** (`single-race_event.php`): Surfaces associated gallery with preview tiles (respecting `display_on_event_page` flag), date range, summary, and edit CTA for owners; draft/empty galleries show private prompt.
- **Gallery Single** (`single-media_gallery.php`): Delivers hero, event linkage, overview, and full media grid (images, uploaded videos, embeds) with per-item captions, photographer credit, and download buttons honoring global/override settings.
- **Archive Pages** (`archive-media_gallery.php`, `archive-race_event.php`, `archive-listing.php`): Unified hero component, navigation CTA, and dark base background. Navigation includes `media_gallery` archive via WP menu.

### Technical Implementation Details
- **ACF Field Group**: `group_ibex_media_gallery_details` with flexible content field `media_gallery_items` (min: 1 item required)
- **Asset Types Supported**:
  - Image Upload: JPG/PNG with caption, photographer override, download toggle, featured flag
  - Video Upload: MP4/MOV/WEBM with poster image, caption, metadata
  - External Embed: YouTube/Vimeo/Other via oEmbed URL with provider selection, fallback thumbnail
- **Permissions**: Custom capabilities for `media_gallery` post type; administrators and editors have full access; custom roles can be configured
- **Scripts**: Enqueued in correct order (`ibex-dashboard-flex.js` before `ibex-media-gallery-form.js`) with versioned timestamps for cache busting
- **Styling**: Comprehensive CSS in `style.css` covering dashboard panels, ACF controls, buttons, form fields, and responsive breakpoints

### Current Status (2025-11-15)
✅ Media gallery dashboard fully functional with dark theme
✅ ACF flexible content toolbar styled with proper colors and icons
✅ Mobile-responsive layout with appropriate stacking
✅ All form controls (buttons, inputs, editors) themed consistently
✅ oEmbed integration working with dark styling
✅ Event relationship working with AJAX auto-population
✅ Mixed media support (images, videos, embeds) operational

### Known Limitations
- TinyMCE Visual editor not styled (Text editor recommended for content field)
- Download functionality implemented but tracking/logging not built
- Lightbox/carousel for public gallery view not yet implemented

