/* global jQuery */
(function($, window) {
  'use strict';

  var debugNs = 'IbexFlex';

  function log() {
    var args = Array.prototype.slice.call(arguments);
    args.unshift('%c' + debugNs, 'color:#61dafb;font-weight:600;');
    if (window.console && typeof window.console.log === 'function') {
      window.console.log.apply(window.console, args);
    }
  }

  function updateCollapseLabel($layout) {
    if (!$layout || !$layout.length) {
      return;
    }

    var $btn = $layout.data('ibexCollapseBtn');
    if (!$btn || !$btn.length) {
      return;
    }

    var collapsed = $layout.hasClass('-collapsed');
    $btn.attr('data-state', collapsed ? 'collapsed' : 'expanded');
    $btn.find('.ibex-acf-label').text(collapsed ? 'Expand' : 'Collapse');
  }

  function styleNativeControls($field) {
    if (!$field || !$field.length) {
      log('No flexible content field in context, skipping.');
      return;
    }

    var styledCount = 0;

    var $flex = $field.find('.acf-flexible-content').first();
    if (!$flex.length) {
      log('No .acf-flexible-content container found inside field.');
      return;
    }

    var $layouts = $flex.find('> .values > .layout');
    if (!$layouts.length) {
      log('No layouts inside .values; searching for .acf-fc-layout within context.');
      $layouts = $flex.find('.acf-fc-layout');
    }

    $layouts.each(function() {
      var $layout = $(this);

      if ($layout.hasClass('acf-clone')) {
        return;
      }

      var $actionsWrap = $layout.find('.acf-fc-layout-actions-wrap').first();
      var $handle = $actionsWrap.find('.acf-fc-layout-handle').first();
      var $controls = $actionsWrap.find('.acf-fc-layout-controls').first();

      if (!$controls.length || !$handle.length) {
        return;
      }

      $actionsWrap.addClass('ibex-acf-actions');
      $handle.addClass('ibex-acf-handle');
      $controls.addClass('ibex-acf-controls');

      if (!$layout.data('ibexFlexStyled')) {
        $layout.data('ibexFlexStyled', true);
        styledCount += 1;
      }

      var actionMap = [
        { name: 'add-layout', label: 'Add' },
        { name: 'duplicate-layout', label: 'Clone' },
        { name: 'remove-layout', label: 'Delete', modifier: 'danger' },
        { name: 'more-layout-actions', label: '', modifier: 'icon-only' },
        { name: 'collapse-layout', label: 'Collapse' }
      ];

      actionMap.forEach(function(action) {
        var $btn = $controls.find('[data-name="' + action.name + '"]').first();
        if (!$btn.length || $btn.data('ibexEnhanced')) {
          return;
        }

        $btn.data('ibexEnhanced', true);
        $btn.addClass('ibex-acf-btn');

        if (action.modifier) {
          $btn.addClass('is-' + action.modifier);
        }

        var $icon = $btn.find('.acf-icon');
        if ($icon.length) {
          $icon.addClass('ibex-acf-icon');
        }

        if (!$btn.find('.ibex-acf-label').length) {
          if (action.label) {
            var $label = $('<span class="ibex-acf-label"></span>').text(action.label);
            $btn.append($label);
          } else if (action.modifier === 'icon-only') {
            $btn.addClass('ibex-acf-icon-only');
          }
        }

        if (action.name === 'collapse-layout') {
          $layout.data('ibexCollapseBtn', $btn);
          updateCollapseLabel($layout);
        }
      });
    });

    if (styledCount) {
      log('Styled', styledCount, 'layout(s) within context:', $field.get(0));
    } else {
      log('No new layouts needed styling in context:', $field.get(0));
    }
  }

  function register(acfInstance) {
    if (!acfInstance || typeof acfInstance.addAction !== 'function') {
      log('ACF instance missing addAction; aborting registration.');
      return;
    }

    if (acfInstance.ibexFlexStyled) {
      log('Register already called; skipping duplicate registration.');
      return;
    }

    acfInstance.ibexFlexStyled = true;

    acfInstance.addAction('ready_field/type=flexible_content', function(field) {
      log('ACF ready_field for flex content triggered.');
      styleNativeControls(field.$el || field);
    });

    acfInstance.addAction('append_layout', function(layout) {
      log('ACF append_layout triggered.');
      styleNativeControls($(layout).closest('.acf-field-flexible-content'));
      updateCollapseLabel($(layout));
    });

    acfInstance.addAction('show_layout', function($layout) {
      log('ACF show_layout triggered.');
      updateCollapseLabel($($layout));
    });

    acfInstance.addAction('hide_layout', function($layout) {
      log('ACF hide_layout triggered.');
      updateCollapseLabel($($layout));
    });
  }

  $(function() {
    log('Document ready; styling existing flexible content fields.');
    var $fields = $('.acf-field-flexible-content');
    log('Found', $fields.length, 'flexible field(s) on page.');
    $fields.each(function() {
      log('Styling field', this);
      styleNativeControls($(this));
    });

    if (typeof window.acf !== 'undefined') {
      log('ACF detected; registering hooks.');
      register(window.acf);
    } else {
      log('ACF not detected on window; hooks not registered.');
    }
  });
})(jQuery, window);
