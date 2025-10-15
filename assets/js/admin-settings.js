(function () {
  'use strict';

  jQuery(function ($) {
    if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
      return;
    }

    // Для файла
    $('.file-select').each(function () {
      var $this = $(this),
        file_select_button = $this.find('.file-select-button'),
        file_delete_button = $this.find('.file-delete-button'),
        file_select_input = $this.find('.file-select-input'),
        wp_media_file_select = null;
      file_select_button.on('click', function (event) {
        event.preventDefault();
        if (wp_media_file_select) {
          wp_media_file_select.open();
          return;
        }
        wp_media_file_select = wp.media({
          title: 'Выбор файла',
          button: {
            text: 'Выбрать файл'
          },
          multiple: false
        });
        wp_media_file_select.on('select', function () {
          var attachment = wp_media_file_select.state().get('selection').first().toJSON();
          file_delete_button.removeClass('hidden');
          file_select_button.html('Изменить').attr('title', 'Изменить');
          if (typeof strt_admin_settings !== 'undefined' && ['url', 'id'].includes(strt_admin_settings.attachment)) {
            file_select_input.val(attachment[strt_admin_settings.attachment]);
          } else {
            file_select_input.val(attachment.url);
          }
        });
        wp_media_file_select.open();
      });
      file_delete_button.on('click', function (event) {
        event.preventDefault();
        $(this).addClass('hidden');
        file_select_button.text('Добавить').attr('title', 'Добавить');
        file_select_input.val('');
      });
    });

    // Для изображения
    $('.image-select').each(function () {
      var $this = $(this),
        image_select_button = $this.find('.image-select-button'),
        image_delete_button = $this.find('.image-delete-button'),
        image_select_input = $this.find('.image-select-input'),
        image_container = $this.find('.image-container'),
        wp_media_image_select = null;
      image_select_button.on('click', function (event) {
        event.preventDefault();
        if (wp_media_image_select) {
          wp_media_image_select.open();
          return;
        }
        wp_media_image_select = wp.media({
          title: 'Выбор изображения',
          button: {
            text: 'Выбрать изображение'
          },
          library: {
            type: 'image'
          },
          multiple: false
        });
        wp_media_image_select.on('select', function () {
          var attachment = wp_media_image_select.state().get('selection').first().toJSON();
          var thumbUrl = attachment.url;
          if (attachment.sizes && attachment.sizes.thumbnail) {
            thumbUrl = attachment.sizes.thumbnail.url;
          }
          image_container.html('<img src="' + thumbUrl + '" alt="" style="max-width:100%;"/>');
          image_delete_button.removeClass('hidden');
          image_select_button.html('Изменить').attr('title', 'Изменить');
          image_select_input.val(attachment.id);
        });
        wp_media_image_select.open();
      });
      image_delete_button.on('click', function (event) {
        event.preventDefault();
        image_container.html('');
        $(this).addClass('hidden');
        image_select_button.text('Добавить').attr('title', 'Добавить');
        image_select_input.val('');
      });
    });
  });

})();
//# sourceMappingURL=admin-settings.js.map
