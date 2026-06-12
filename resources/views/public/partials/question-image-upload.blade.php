<style>
  .q-image-upload {
    border: 2px dashed rgba(122, 117, 131, 0.35);
    border-radius: 1rem;
    background: #ffffff;
    transition: border-color 0.2s ease, background 0.2s ease;
    overflow: hidden;
  }
  .q-image-upload:hover,
  .q-image-upload.is-dragover {
    border-color: rgba(49, 15, 122, 0.45);
    background: #f8f1fb;
  }
  .q-image-upload-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 2.75rem 1.5rem;
    min-height: 176px;
    text-align: center;
    cursor: pointer;
    margin: 0;
  }
  .q-image-upload-empty .material-symbols-outlined {
    font-size: 2.25rem;
    color: #7a7583;
    line-height: 1;
    pointer-events: none;
  }
  .q-image-upload-text {
    font-size: 0.95rem;
    color: #494552;
    margin: 0;
    line-height: 1.5;
    pointer-events: none;
  }
  .q-image-upload-browse {
    font-weight: 700;
    color: #310f7a;
  }
  .q-image-upload-hint {
    font-size: 0.8rem;
    color: #7a7583;
    margin: 0;
    line-height: 1.4;
    pointer-events: none;
  }
  .q-image-upload-preview {
    display: none;
    position: relative;
    padding: 1rem;
    cursor: pointer;
  }
  .q-image-upload.has-file .q-image-upload-empty {
    display: none;
  }
  .q-image-upload.has-file .q-image-upload-preview {
    display: block;
  }
  .q-image-upload-preview img {
    max-height: 240px;
    width: auto;
    max-width: 100%;
    margin: 0 auto;
    display: block;
    border-radius: 0.75rem;
    object-fit: contain;
    pointer-events: none;
  }
  .q-image-upload-remove {
    position: absolute;
    top: 1.25rem;
    right: 1.25rem;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.35rem 0.65rem;
    border-radius: 9999px;
    border: none;
    background: rgba(28, 27, 33, 0.72);
    color: #fff;
    font-size: 0.72rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s ease;
    z-index: 2;
  }
  .q-image-upload-remove:hover {
    background: rgba(28, 27, 33, 0.88);
  }
  .q-image-upload-input {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
  }
</style>
<script>
(function(window, $) {
  'use strict';

  var MAX_BYTES = 10 * 1024 * 1024;
  var ALLOWED_TYPES = { 'image/jpeg': true, 'image/jpg': true, 'image/png': true };

  function revokeObjectUrl($zone) {
    var prev = $zone.data('objectUrl');
    if (prev) {
      URL.revokeObjectURL(prev);
      $zone.removeData('objectUrl');
    }
  }

  function openFilePicker($input) {
    if (!$input || !$input.length) return;
    var el = $input[0];
    if (el && typeof el.click === 'function') {
      el.click();
    }
  }

  window.QuestionImageField = {
    maxBytes: MAX_BYTES,

    buildHtml: function(questionId) {
      var inputId = 'q-image-input-' + String(questionId);
      return '' +
        '<div class="q-image-upload relative" data-question-id="' + questionId + '">' +
          '<input type="file" id="' + inputId + '" accept="image/png,image/jpeg,image/jpg" data-question-id="' + questionId + '" class="q-image-upload-input js-question-file">' +
          '<label for="' + inputId + '" class="q-image-upload-empty">' +
            '<span class="material-symbols-outlined" aria-hidden="true">cloud_upload</span>' +
            '<p class="q-image-upload-text">Drop images here or <span class="q-image-upload-browse">browse</span></p>' +
            '<p class="q-image-upload-hint">PNG, JPG up to 10MB each</p>' +
          '</label>' +
          '<div class="q-image-upload-preview" role="button" tabindex="0" aria-label="Replace uploaded image">' +
            '<img src="" alt="Uploaded image preview">' +
            '<button type="button" class="q-image-upload-remove" aria-label="Remove image">' +
              '<span class="material-symbols-outlined text-[14px]">close</span> Remove' +
            '</button>' +
          '</div>' +
        '</div>';
    },

    validateFile: function(file) {
      if (!file) return 'Please choose an image.';
      if (!ALLOWED_TYPES[String(file.type || '').toLowerCase()]) {
        return 'Only PNG and JPG images are allowed.';
      }
      if (file.size > MAX_BYTES) {
        return 'Image must be 10MB or smaller.';
      }
      return '';
    },

    showPreview: function($zone, url) {
      if (!$zone || !$zone.length || !url) return;
      $zone.find('.q-image-upload-preview img').attr('src', url);
      $zone.addClass('has-file');
    },

    showLocalPreview: function($zone, file) {
      if (!$zone || !$zone.length || !file) return;
      revokeObjectUrl($zone);
      var objectUrl = URL.createObjectURL(file);
      $zone.data('objectUrl', objectUrl);
      this.showPreview($zone, objectUrl);
    },

    clear: function($zone) {
      if (!$zone || !$zone.length) return;
      revokeObjectUrl($zone);
      $zone.removeClass('has-file is-dragover');
      $zone.find('.q-image-upload-preview img').attr('src', '');
      $zone.find('.js-question-file').val('');
    },

    initIn: function($root) {
      ($root || $(document)).find('.q-image-upload').each(function() {
        var $zone = $(this);
        if ($zone.data('qImageInit')) return;
        $zone.data('qImageInit', true);

        var $input = $zone.find('.js-question-file');
        var $preview = $zone.find('.q-image-upload-preview');

        $preview.on('click', function(event) {
          if ($(event.target).closest('.q-image-upload-remove').length) return;
          openFilePicker($input);
        });

        $preview.on('keydown', function(event) {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            openFilePicker($input);
          }
        });

        $zone.on('dragover dragenter', function(event) {
          event.preventDefault();
          event.stopPropagation();
          $zone.addClass('is-dragover');
        });

        $zone.on('dragleave dragend', function(event) {
          event.preventDefault();
          event.stopPropagation();
          $zone.removeClass('is-dragover');
        });

        $zone.on('drop', function(event) {
          event.preventDefault();
          event.stopPropagation();
          $zone.removeClass('is-dragover');
          var files = event.originalEvent && event.originalEvent.dataTransfer
            ? event.originalEvent.dataTransfer.files
            : null;
          if (!files || !files.length || !$input[0]) return;
          try {
            var dt = new DataTransfer();
            dt.items.add(files[0]);
            $input[0].files = dt.files;
          } catch (err) {
            return;
          }
          $input.trigger('change');
        });

        $zone.find('.q-image-upload-remove').on('click', function(event) {
          event.preventDefault();
          event.stopPropagation();
          window.QuestionImageField.clear($zone);
          $zone.closest('.question-div').find('.js-question-error').addClass('hidden');
          $input.trigger('change');
        });
      });
    }
  };

  $(function() {
    window.QuestionImageField.initIn($(document));
  });
})(window, jQuery);
</script>
