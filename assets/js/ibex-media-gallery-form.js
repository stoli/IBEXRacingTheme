(function($, acf, window, document, undefined) {
  'use strict';

  if (typeof acf === 'undefined' || typeof ibexMediaGalleryForm === 'undefined') {
    return;
  }

  const FIELD_KEYS = {
    relatedEvent: 'field_ibex_media_gallery_related_event',
    startDate: 'field_ibex_media_gallery_start_date',
    endDate: 'field_ibex_media_gallery_end_date',
    location: 'field_ibex_media_gallery_location',
  };

  const requestCache = new Map();

  function fetchEventDetails(eventId) {
    if (!eventId) {
      return Promise.resolve(null);
    }

    if (requestCache.has(eventId)) {
      return Promise.resolve(requestCache.get(eventId));
    }

    return $.ajax({
      url: ibexMediaGalleryForm.ajaxUrl,
      type: 'POST',
      dataType: 'json',
      data: {
        action: 'ibex_event_gallery_details',
        nonce: ibexMediaGalleryForm.nonce,
        eventId,
      },
    }).then(function(response) {
      if (!response || !response.success) {
        const message = response && response.data && response.data.message ?
          response.data.message :
          acf.__('Unable to fetch event details.');
        acf.newNotice({ type: 'error', text: message });
        return null;
      }

      requestCache.set(eventId, response.data);
      return response.data;
    }).catch(function() {
      acf.newNotice({ type: 'error', text: acf.__('Unable to fetch event details.') });
      return null;
    });
  }

  function formatDisplayValue(rawValue, field) {
    if (!rawValue) {
      return '';
    }

    const hasFormatter = typeof acf.formatDate === 'function';
    const $hidden = field.$input;
    const displayFormat = ($hidden && $hidden.attr('data-display_format')) || '';

    if (hasFormatter && displayFormat) {
      try {
        return acf.formatDate(rawValue, displayFormat);
      } catch (e) {
        return rawValue;
      }
    }

    return rawValue;
  }

  function setFieldValue(fieldKey, value, displayValue) {
    const field = acf.getField(fieldKey);
    if (!field) {
      return;
    }

    const sanitized = value || '';
    const fieldType = field.get('type');

    // For date pickers, use ACF's proper API
    if (fieldType === 'date_picker') {
      // ACF date pickers store raw value (Y-m-d) in hidden input and display formatted value
      const $hiddenInput = field.$el.find('input[type="hidden"]').first();
      const $displayInput = field.$el.find('input[type="text"]').first();
      
      if ($hiddenInput.length) {
        // Set the raw value in the hidden input
        $hiddenInput.val(sanitized);
        
        // Set the display value - use provided displayValue or format it
        if ($displayInput.length) {
          if (displayValue) {
            $displayInput.val(displayValue);
          } else if (sanitized) {
            // Format using ACF's formatter if available
            const formatted = formatDisplayValue(sanitized, field);
            $displayInput.val(formatted);
          } else {
            $displayInput.val('');
          }
        }
        
        // Trigger ACF's change event on the hidden input to update the field
        $hiddenInput.trigger('change');
        
        // Also try using ACF's field API if available
        if (typeof field.val === 'function') {
          field.val(sanitized);
        }
        
        // Trigger ACF field change event
        if (typeof field.trigger === 'function') {
          field.trigger('change');
        } else if (typeof acf.doAction === 'function') {
          acf.doAction('change', field.$el);
        }
      }
    } else {
      // For other field types, use standard ACF API
      if (typeof field.setValue === 'function') {
        field.setValue(sanitized);
      } else if (typeof field.val === 'function') {
        field.val(sanitized);
      }

      if (field.$input && field.$input.length) {
        field.$input.val(sanitized).trigger('change');
      }
    }
  }

  function resetFields() {
    setFieldValue(FIELD_KEYS.startDate, '');
    setFieldValue(FIELD_KEYS.endDate, '');
    setFieldValue(FIELD_KEYS.location, '');
  }

  function hydrateFromEvent(eventId) {
    if (!eventId) {
      resetFields();
      return;
    }

    fetchEventDetails(eventId).then(function(details) {
      if (!details) {
        resetFields();
        return;
      }

      // Always set all values, even if empty, to clear old values when switching events
      // This ensures fields are cleared if the new event doesn't have those values
      setFieldValue(FIELD_KEYS.startDate, details.start_date || '', details.start_date_display || '');
      setFieldValue(FIELD_KEYS.endDate, details.end_date || '', details.end_date_display || '');
      setFieldValue(FIELD_KEYS.location, details.location || '');
    });
  }

  function onEventFieldReady(field) {
    const value = field.val();
    if (value) {
      hydrateFromEvent(value);
    }

    attachChangeHandler(field);
  }
  function attachChangeHandler(field) {
    const handler = function() {
      const selected = field.val();
      hydrateFromEvent(selected);
    };

    if (field.$input && field.$input.length) {
      field.$input.on('change ibex:update', handler);
      field.$input.on('select2:select select2:unselect select2:clear', handler);
    } else {
      field.on('change', handler);
    }
  }

  acf.addAction('ready_field/key=' + FIELD_KEYS.relatedEvent, onEventFieldReady);

  // Clear form dirty state and remove beforeunload warning
  function clearFormDirtyState() {
    // Get all ACF forms on the page
    const $forms = $('.acf-form');
    
    $forms.each(function() {
      const $form = $(this);
      const formId = $form.attr('id') || $form.data('id');
      
      if (formId) {
        // Get the ACF form instance
        const form = acf.getForm(formId);
        if (form && typeof form.set === 'function') {
          // Mark form as unchanged - this clears ACF's dirty state
          form.set('changed', false);
        }
      }
    });

    // Also try to clear via ACF's global form tracking
    if (typeof acf !== 'undefined') {
      const forms = acf.get('forms');
      if (forms && typeof forms === 'object') {
        Object.keys(forms).forEach(function(formId) {
          const form = forms[formId];
          if (form && typeof form.set === 'function') {
            form.set('changed', false);
          }
        });
      }
    }

    // Aggressively remove all beforeunload handlers
    // Remove jQuery-based handlers
    $(window).off('beforeunload');
    
    // Remove native beforeunload handler
    window.onbeforeunload = null;
    
    // Also try to remove ACF-specific handlers
    $(window).off('beforeunload.acf');
    
    // Force ACF to recognize the form is clean
    if (typeof acf !== 'undefined' && typeof acf.doAction === 'function') {
      acf.doAction('sync', false);
    }
  }

  // Intercept form submission to clear state immediately
  // This is the most important part - remove handler BEFORE form submits
  $(document).on('submit', '.acf-form', function(e) {
    // Immediately remove beforeunload handler when form is submitted
    window.onbeforeunload = null;
    $(window).off('beforeunload');
    
    // Clear the form state
    clearFormDirtyState();
    
    // Set a flag that we've submitted, so we can clear again after redirect
    sessionStorage.setItem('acf_form_submitted', '1');
    
    // Also override beforeunload to always return null (prevent re-attachment)
    window.onbeforeunload = function() {
      return null;
    };
  });

  // Clear form state if we're on a success page (gallery_submitted parameter)
  // This handles the case where the form was submitted and we're redirected back
  if (window.location.search.indexOf('gallery_submitted') !== -1 || sessionStorage.getItem('acf_form_submitted') === '1') {
    $(document).ready(function() {
      // Clear the session flag
      sessionStorage.removeItem('acf_form_submitted');
      
      // Wait for ACF to fully initialize, then clear the dirty state
      setTimeout(function() {
        clearFormDirtyState();
      }, 100);
      
      // Also clear again after a longer delay to catch any late initialization
      setTimeout(clearFormDirtyState, 500);
    });
  }

  // Clear on successful form submission (fires after save)
  acf.addAction('save_post', function() {
    clearFormDirtyState();
    // Also clear again after a short delay
    setTimeout(clearFormDirtyState, 50);
  });

  // Clear when form validation passes and submission is about to succeed
  acf.addAction('submit', function($form) {
    clearFormDirtyState();
  });

  // Persistent check: if we're on a success page, keep clearing the handler
  // This prevents ACF from re-attaching it after page load
  if (window.location.search.indexOf('gallery_submitted') !== -1) {
    // Override beforeunload to always return null (no warning)
    // This is more aggressive but ensures the warning never shows
    const originalBeforeUnload = window.onbeforeunload;
    window.onbeforeunload = function() {
      return null; // Always return null to prevent warning
    };
    
    // Also set up a short interval to keep clearing it (in case ACF re-attaches)
    let checkCount = 0;
    const maxChecks = 10; // Check for up to 1 second (10 * 100ms)
    
    const persistentClear = setInterval(function() {
      checkCount++;
      
      // Keep removing the handler
      window.onbeforeunload = function() {
        return null;
      };
      $(window).off('beforeunload');
      
      // Stop checking after max attempts
      if (checkCount >= maxChecks) {
        clearInterval(persistentClear);
      }
    }, 100);
  }

})(jQuery, window.acf, window, document);


(function($, window) {
  'use strict';

  $(function() {
    if (typeof window.acf === 'undefined') {
      console.warn('ACF not available for flex toolbar');
      return;
    }

    var namespace = window.ibexDashboardFlex;
    if (!namespace || typeof namespace.register !== 'function') {
      console.warn('ibexDashboardFlex helper not available');
      return;
    }

    namespace.register(window.acf);
  });
})(jQuery, window);