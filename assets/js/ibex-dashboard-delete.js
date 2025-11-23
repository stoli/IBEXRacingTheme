/* global jQuery */
(function($, window) {
  'use strict';

  var debugNs = 'IbexDelete';

  function log() {
    var args = Array.prototype.slice.call(arguments);
    args.unshift('%c' + debugNs, 'color:#ff6b6b;font-weight:600;');
    if (window.console && typeof window.console.log === 'function') {
      window.console.log.apply(window.console, args);
    }
  }

  function clearFormDirtyState() {
    // Aggressively remove all beforeunload handlers first
    // Remove jQuery-based handlers
    $(window).off('beforeunload');
    
    // Remove native beforeunload handler
    window.onbeforeunload = null;
    
    // Also try to remove ACF-specific handlers
    $(window).off('beforeunload.acf');
    
    // Try to clear ACF form state if ACF is available
    // Use try-catch to prevent errors if ACF API is different
    try {
      if (typeof window.acf !== 'undefined' && window.acf !== null) {
        // Try to clear via ACF's global form tracking (most reliable method)
        if (typeof window.acf.get === 'function') {
          var forms = window.acf.get('forms');
          if (forms && typeof forms === 'object') {
            Object.keys(forms).forEach(function(formId) {
              try {
                var form = forms[formId];
                if (form && typeof form.set === 'function') {
                  form.set('changed', false);
                }
              } catch (e) {
                // Ignore individual form errors
              }
            });
          }
        }
        
        // Force ACF to recognize the form is clean
        if (typeof window.acf.doAction === 'function') {
          window.acf.doAction('sync', false);
        }
      }
    } catch (e) {
      // If ACF API is not available or different, just continue
      // The beforeunload handler removal is the most important part
    }
    
    // Override beforeunload to always return null (prevent re-attachment)
    window.onbeforeunload = function() {
      return null;
    };
  }

  function deleteEvent(eventId, eventTitle) {
    if (!confirm('Are you sure you want to delete "' + eventTitle + '"? This action cannot be undone.')) {
      return;
    }

    var $btn = $('.ibex-delete-event-btn[data-event-id="' + eventId + '"]');
    var originalText = $btn.text();
    $btn.prop('disabled', true).text('Deleting...');

    $.ajax({
      url: window.ibexDelete.ajaxUrl,
      type: 'POST',
      data: {
        action: 'ibex_delete_event',
        event_id: eventId,
        nonce: window.ibexDelete.eventNonce
      },
      success: function(response) {
        if (response.success) {
          // Clear form state before redirecting to prevent "leave site" warning
          clearFormDirtyState();
          
          // Small delay to ensure handlers are cleared
          setTimeout(function() {
            if (response.data.redirect_url) {
              window.location.href = response.data.redirect_url;
            } else {
              window.location.reload();
            }
          }, 50);
        } else {
          alert(response.data.message || 'Failed to delete event.');
          $btn.prop('disabled', false).text(originalText);
        }
      },
      error: function(xhr, status, error) {
        log('Delete event error:', error);
        alert('An error occurred while deleting the event. Please try again.');
        $btn.prop('disabled', false).text(originalText);
      }
    });
  }

  function deleteGallery(galleryId, galleryTitle) {
    if (!confirm('Are you sure you want to delete "' + galleryTitle + '"? The gallery entry will be removed, but all media files will be preserved. This action cannot be undone.')) {
      return;
    }

    var $btn = $('.ibex-delete-gallery-btn[data-gallery-id="' + galleryId + '"]');
    var originalText = $btn.text();
    $btn.prop('disabled', true).text('Deleting...');

    $.ajax({
      url: window.ibexDelete.ajaxUrl,
      type: 'POST',
      data: {
        action: 'ibex_delete_gallery',
        gallery_id: galleryId,
        nonce: window.ibexDelete.galleryNonce
      },
      success: function(response) {
        if (response.success) {
          // Clear form state before redirecting to prevent "leave site" warning
          clearFormDirtyState();
          
          // Small delay to ensure handlers are cleared
          setTimeout(function() {
            if (response.data.redirect_url) {
              window.location.href = response.data.redirect_url;
            } else {
              window.location.reload();
            }
          }, 50);
        } else {
          alert(response.data.message || 'Failed to delete gallery.');
          $btn.prop('disabled', false).text(originalText);
        }
      },
      error: function(xhr, status, error) {
        log('Delete gallery error:', error);
        alert('An error occurred while deleting the gallery. Please try again.');
        $btn.prop('disabled', false).text(originalText);
      }
    });
  }

  function deleteListing(listingId, listingTitle) {
    if (!confirm('Are you sure you want to delete "' + listingTitle + '"? The listing entry will be removed, but all media files will be preserved. This action cannot be undone.')) {
      return;
    }

    var $btn = $('.ibex-delete-listing-btn[data-listing-id="' + listingId + '"]');
    var originalText = $btn.text();
    $btn.prop('disabled', true).text('Deleting...');

    $.ajax({
      url: window.ibexDelete.ajaxUrl,
      type: 'POST',
      data: {
        action: 'ibex_delete_listing',
        listing_id: listingId,
        nonce: window.ibexDelete.listingNonce
      },
      success: function(response) {
        if (response.success) {
          // Clear form state before redirecting to prevent "leave site" warning
          clearFormDirtyState();
          
          // Small delay to ensure handlers are cleared
          setTimeout(function() {
            if (response.data.redirect_url) {
              window.location.href = response.data.redirect_url;
            } else {
              window.location.reload();
            }
          }, 50);
        } else {
          alert(response.data.message || 'Failed to delete listing.');
          $btn.prop('disabled', false).text(originalText);
        }
      },
      error: function(xhr, status, error) {
        log('Delete listing error:', error);
        alert('An error occurred while deleting the listing. Please try again.');
        $btn.prop('disabled', false).text(originalText);
      }
    });
  }

  function injectDeleteButton() {
    // Find the delete button template
    var $template = $('.ibex-delete-button-template');
    if (!$template.length) {
      return;
    }

    // Find the ACF submit button
    var $submitBtn = $('.acf-form-submit button[type="submit"], .acf-form .acf-button[type="submit"], .ibex-dashboard__submit');
    if (!$submitBtn.length) {
      // Try alternative selectors
      $submitBtn = $('.ibex-dashboard__form button[type="submit"]');
    }

    if (!$submitBtn.length) {
      log('Submit button not found, retrying...');
      // Retry after a short delay in case ACF hasn't rendered yet
      setTimeout(injectDeleteButton, 100);
      return;
    }

    // Check if delete button already exists
    if ($submitBtn.siblings('.ibex-delete-event-btn, .ibex-delete-gallery-btn, .ibex-delete-listing-btn').length) {
      return;
    }

    // Get the button HTML from template
    var buttonHtml = $template.html();
    
    // Wrap submit button and delete button in a flex container if not already wrapped
    var $parent = $submitBtn.parent();
    if (!$parent.hasClass('ibex-form-actions')) {
      $submitBtn.wrap('<div class="ibex-form-actions" style="display: flex; gap: 1rem; align-items: center; margin-top: 1rem;"></div>');
      $parent = $submitBtn.parent();
    }
    
    // Add delete button next to submit button
    $submitBtn.after(buttonHtml);
    
    // Remove the template
    $template.remove();
    
    log('Delete button injected next to submit button.');
  }

  // Intercept dashboard navigation links to clear form state before navigation
  // This prevents "leave site" warnings when clicking between items in the dashboard
  function handleDashboardNavigation(e) {
    var $link = $(this);
    var href = $link.attr('href');
    
    // Only handle internal links (not external, delete buttons, or form submissions)
    if (!href || href === '#' || $link.attr('target') || 
        $link.hasClass('ibex-delete-event-btn') || 
        $link.hasClass('ibex-delete-gallery-btn') || 
        $link.hasClass('ibex-delete-listing-btn') ||
        $link.closest('form').length) {
      return;
    }
    
    // Check if we're on a dashboard page and this is a navigation link
    var isDashboardPage = $('.ibex-dashboard, .ibex-event-dashboard, .ibex-media-gallery-dashboard, .ibex-listing-dashboard').length > 0;
    
    if (isDashboardPage) {
      // Clear form state before navigation
      clearFormDirtyState();
      
      // Small delay to ensure handlers are cleared before navigation
      e.preventDefault();
      setTimeout(function() {
        window.location.href = href;
      }, 10);
      return false;
    }
  }
  
  // More aggressive handler for any link click when on a dashboard page
  // This catches navigation menu links, header links, etc.
  function handleAnyNavigation(e) {
    var $link = $(this);
    var href = $link.attr('href');
    
    // Skip if it's not a valid link or is external
    if (!href || href === '#' || $link.attr('target') === '_blank' || 
        href.indexOf('http') === 0 && href.indexOf(window.location.hostname) === -1) {
      return;
    }
    
    // Skip delete buttons and form elements
    if ($link.hasClass('ibex-delete-event-btn') || 
        $link.hasClass('ibex-delete-gallery-btn') || 
        $link.hasClass('ibex-delete-listing-btn') ||
        $link.closest('form').length ||
        $link.is('button')) {
      return;
    }
    
    // Check if we're on a dashboard page
    var isDashboardPage = $('.ibex-dashboard, .ibex-event-dashboard, .ibex-media-gallery-dashboard, .ibex-listing-dashboard').length > 0;
    
    if (isDashboardPage) {
      // Check if this link is going to a different page (not just changing query params)
      var currentPath = window.location.pathname;
      var linkPath = href.split('?')[0].split('#')[0];
      
      // If it's a different page, clear form state
      if (linkPath !== currentPath || href.indexOf('?') !== -1) {
        clearFormDirtyState();
        
        // Small delay to ensure handlers are cleared
        e.preventDefault();
        setTimeout(function() {
          window.location.href = href;
        }, 10);
        return false;
      }
    }
  }

  $(function() {
    log('Document ready; initializing delete handlers.');

    // Clear form state on page load if we're in index mode (viewing list, not editing)
    // This prevents warnings when navigating between items
    var isIndexMode = $('.ibex-dashboard__empty-state').length > 0;
    if (isIndexMode) {
      clearFormDirtyState();
      // Clear again after ACF initializes
      if (typeof window.acf !== 'undefined') {
        window.acf.addAction('ready', function() {
          setTimeout(clearFormDirtyState, 100);
        });
      }
    }

    // Inject delete buttons next to submit buttons
    injectDeleteButton();
    
    // Also try after ACF is ready (if ACF is available)
    if (typeof window.acf !== 'undefined') {
      window.acf.addAction('ready', function() {
        setTimeout(injectDeleteButton, 50);
      });
    }

    // Intercept dashboard navigation links (specific dashboard links)
    $(document).on('click', '.ibex-dashboard__list-link, .ibex-dashboard__backlink, .ibex-dashboard__panel-action, .ibex-dashboard__nav-link', handleDashboardNavigation);
    
    // Intercept any navigation when on dashboard pages (catches menu links, header links, etc.)
    // Use a more specific selector to avoid interfering with form submissions
    $(document).on('click', 'a[href]', function(e) {
      var $link = $(this);
      
      // Check if we're on a dashboard page
      var isDashboardPage = $('.ibex-dashboard, .ibex-event-dashboard, .ibex-media-gallery-dashboard, .ibex-listing-dashboard').length > 0;
      
      if (!isDashboardPage) {
        return; // Not on a dashboard page, let normal navigation happen
      }
      
      // Skip if it's inside a form (except dashboard navigation)
      if ($link.closest('form').length && !$link.closest('.ibex-dashboard__nav').length) {
        return;
      }
      
      // Skip delete buttons
      if ($link.hasClass('ibex-delete-event-btn') || 
          $link.hasClass('ibex-delete-gallery-btn') || 
          $link.hasClass('ibex-delete-listing-btn')) {
        return;
      }
      
      // Skip ACF buttons
      if ($link.hasClass('acf-button')) {
        return;
      }
      
      // Skip if it's already handled by dashboard navigation handler
      if ($link.hasClass('ibex-dashboard__list-link') || 
          $link.hasClass('ibex-dashboard__backlink') || 
          $link.hasClass('ibex-dashboard__panel-action') ||
          $link.hasClass('ibex-dashboard__nav-link')) {
        return; // Already handled by handleDashboardNavigation
      }
      
      // Handle the navigation
      handleAnyNavigation.call(this, e);
    });

    // Event delete handler
    $(document).on('click', '.ibex-delete-event-btn', function(e) {
      e.preventDefault();
      var $btn = $(this);
      var eventId = $btn.data('event-id');
      var eventTitle = $btn.data('event-title') || 'this event';
      deleteEvent(eventId, eventTitle);
    });

    // Gallery delete handler
    $(document).on('click', '.ibex-delete-gallery-btn', function(e) {
      e.preventDefault();
      var $btn = $(this);
      var galleryId = $btn.data('gallery-id');
      var galleryTitle = $btn.data('gallery-title') || 'this gallery';
      deleteGallery(galleryId, galleryTitle);
    });

    // Listing delete handler
    $(document).on('click', '.ibex-delete-listing-btn', function(e) {
      e.preventDefault();
      var $btn = $(this);
      var listingId = $btn.data('listing-id');
      var listingTitle = $btn.data('listing-title') || 'this listing';
      deleteListing(listingId, listingTitle);
    });

    log('Delete handlers initialized.');
  });
})(jQuery, window);

