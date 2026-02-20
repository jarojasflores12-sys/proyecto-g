(function () {
  'use strict';

  function initBackgroundSelector() {
    var selectButton = document.getElementById('catgame_select_background');
    var clearButton = document.getElementById('catgame_clear_background');
    var idInput = document.getElementById('catgame_background_image_id');
    var urlInput = document.getElementById('catgame_background_image_url');

    if (!selectButton || !clearButton || !idInput || !urlInput) {
      return;
    }

    if (!window.wp || !wp.media) {
      return;
    }

    var frame = null;

    selectButton.addEventListener('click', function (event) {
      event.preventDefault();

      if (!frame) {
        frame = wp.media({
          title: 'Seleccionar imagen de fondo',
          library: { type: 'image' },
          button: { text: 'Usar imagen' },
          multiple: false
        });

        frame.on('select', function () {
          var selection = frame.state().get('selection').first();

          if (!selection) {
            return;
          }

          var attachment = selection.toJSON();
          idInput.value = attachment.id || '';
          urlInput.value = attachment.url || '';
        });
      }

      frame.open();
    });

    clearButton.addEventListener('click', function (event) {
      event.preventDefault();
      idInput.value = '';
      urlInput.value = '';
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBackgroundSelector);
    return;
  }

  initBackgroundSelector();
})();
