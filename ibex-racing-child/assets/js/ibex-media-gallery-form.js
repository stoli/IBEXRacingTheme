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

    if (typeof field.setValue === 'function') {
      field.setValue(sanitized);
    } else if (typeof field.val === 'function') {
      field.val(sanitized);
    }

    if (field.$input && field.$input.length) {
      field.$input.val(sanitized).trigger('change');
    }

    if (field.get('type') === 'date_picker') {
      const $displayInput = field.$el.find('input[type="text"]').first();
      const formattedDisplay = displayValue || formatDisplayValue(sanitized, field);
      if ($displayInput.length) {
        $displayInput.val(formattedDisplay);
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