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
  .q-image-upload-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(104px, 1fr));
    gap: 0.75rem;
    padding: 1rem 1rem 0;
  }
  .q-image-upload-list:empty {
    display: none;
    padding: 0;
  }
  .q-image-thumb {
    position: relative;
    aspect-ratio: 1;
    border-radius: 0.75rem;
    overflow: hidden;
    background: #f2ecf5;
    border: 1px solid rgba(122, 117, 131, 0.2);
  }
  .q-image-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    pointer-events: none;
  }
  .q-image-thumb-uploading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    background: #f2ecf5;
  }
  .q-image-thumb-uploading .material-symbols-outlined {
    font-size: 1.75rem;
    color: #7a7583;
    margin-bottom: 0.25rem;
    animation: q-pulse 1.5s ease-in-out infinite;
  }
  .q-image-thumb-progress {
    width: 70%;
    height: 4px;
    border-radius: 2px;
    background: rgba(122, 117, 131, 0.2);
    overflow: hidden;
    margin-top: 0.35rem;
  }
  .q-image-thumb-progress-bar {
    height: 100%;
    border-radius: 2px;
    background: #310f7a;
    width: 0%;
    transition: width 0.2s ease;
  }
  .q-image-thumb-pct {
    font-size: 0.65rem;
    color: #7a7583;
    margin-top: 0.2rem;
    font-weight: 600;
  }
  @keyframes q-pulse {
    0%, 100% { opacity: 0.5; }
    50% { opacity: 1; }
  }
  .q-image-thumb-remove {
    position: absolute;
    top: 0.35rem;
    right: 0.35rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.65rem;
    height: 1.65rem;
    border-radius: 9999px;
    border: none;
    background: rgba(28, 27, 33, 0.78);
    color: #fff;
    cursor: pointer;
    transition: background 0.15s ease;
    z-index: 2;
  }
  .q-image-thumb-remove:hover {
    background: rgba(186, 26, 26, 0.92);
  }
  .q-image-thumb-remove .material-symbols-outlined {
    font-size: 1rem;
    line-height: 1;
    pointer-events: none;
  }
  .q-image-upload-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 2rem 1.5rem;
    min-height: 140px;
    text-align: center;
    cursor: pointer;
    margin: 0;
  }
  .q-image-upload.has-images .q-image-upload-empty {
    min-height: 112px;
    padding: 1.25rem 1.5rem 1.5rem;
  }
  .q-image-upload.is-full .q-image-upload-empty {
    display: none;
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
  .q-image-upload-meta {
    font-size: 0.75rem;
    color: #7a7583;
    text-align: center;
    padding: 0 1rem 1rem;
    margin: 0;
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
  var MAX_IMAGES = 5;
  var ALLOWED_TYPES = { 'image/jpeg': true, 'image/jpg': true, 'image/png': true };

  function escapeHtml(str) {
    return String(str || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
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
    maxImages: MAX_IMAGES,
    _uploadHandler: null,

    setUploadHandler: function(handler) {
      this._uploadHandler = typeof handler === 'function' ? handler : null;
    },

    buildHtml: function(questionId) {
      var inputId = 'q-image-input-' + String(questionId);
      return '' +
        '<div class="q-image-upload relative" data-question-id="' + questionId + '">' +
          '<input type="file" id="' + inputId + '" accept="image/png,image/jpeg,image/jpg" multiple data-question-id="' + questionId + '" class="q-image-upload-input js-question-file">' +
          '<div class="q-image-upload-list" aria-live="polite"></div>' +
          '<label for="' + inputId + '" class="q-image-upload-empty">' +
            '<span class="material-symbols-outlined" aria-hidden="true">cloud_upload</span>' +
            '<p class="q-image-upload-text q-image-upload-text-primary">Drop images here or <span class="q-image-upload-browse">browse</span></p>' +
            '<p class="q-image-upload-text q-image-upload-text-more hidden">Add more images</p>' +
            '<p class="q-image-upload-hint">PNG, JPG up to 10MB each</p>' +
          '</label>' +
          '<p class="q-image-upload-meta"><span class="q-image-upload-count">0</span>/' + MAX_IMAGES + ' images</p>' +
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

    getUrls: function($zone) {
      var urls = $zone.data('imageUrls');
      return Array.isArray(urls) ? urls.slice() : [];
    },

    setUrls: function($zone, urls) {
      var next = (Array.isArray(urls) ? urls : []).slice(0, MAX_IMAGES);
      $zone.data('imageUrls', next);
      this.render($zone);
      this.notify($zone);
    },

    notify: function($zone) {
      var qId = $zone.data('question-id');
      $zone.trigger('qimages:updated', [qId, this.getUrls($zone)]);
    },

    render: function($zone) {
      if (!$zone || !$zone.length) return;

      var urls = this.getUrls($zone);
      var $list = $zone.find('.q-image-upload-list');
      $list.empty();

      urls.forEach(function(url, index) {
        $list.append(
          '<div class="q-image-thumb" data-index="' + index + '">' +
            '<img src="' + escapeHtml(url) + '" alt="Uploaded reference image ' + (index + 1) + '">' +
            '<button type="button" class="q-image-thumb-remove" data-index="' + index + '" aria-label="Remove image ' + (index + 1) + '">' +
              '<span class="material-symbols-outlined">close</span>' +
            '</button>' +
          '</div>'
        );
      });

      $zone.toggleClass('has-images', urls.length > 0);
      $zone.toggleClass('is-full', urls.length >= MAX_IMAGES);
      $zone.find('.q-image-upload-count').text(String(urls.length));
      $zone.find('.q-image-upload-text-primary').toggleClass('hidden', urls.length > 0);
      $zone.find('.q-image-upload-text-more').toggleClass('hidden', urls.length === 0);
    },

    clear: function($zone) {
      if (!$zone || !$zone.length) return;
      $zone.removeClass('is-dragover');
      $zone.find('.js-question-file').val('');
      this.setUrls($zone, []);
    },

    removeAt: function($zone, index) {
      var urls = this.getUrls($zone);
      if (index < 0 || index >= urls.length) return;
      urls.splice(index, 1);
      this.setUrls($zone, urls);
    },

    showError: function($zone, message) {
      $zone.closest('.question-div').find('.js-question-error')
        .removeClass('hidden')
        .text(message || 'Unable to upload image.');
    },

    clearError: function($zone) {
      $zone.closest('.question-div').find('.js-question-error').addClass('hidden');
    },

    _buildPlaceholder: function(id) {
      return '<div class="q-image-thumb" data-upload-id="' + id + '">' +
        '<div class="q-image-thumb-uploading">' +
          '<span class="material-symbols-outlined" aria-hidden="true">image</span>' +
          '<div class="q-image-thumb-progress"><div class="q-image-thumb-progress-bar"></div></div>' +
          '<span class="q-image-thumb-pct">0%</span>' +
        '</div></div>';
    },

    _updatePlaceholder: function($zone, id, pct) {
      var $ph = $zone.find('[data-upload-id="' + id + '"]');
      if (!$ph.length) return;
      $ph.find('.q-image-thumb-progress-bar').css('width', pct + '%');
      $ph.find('.q-image-thumb-pct').text(Math.round(pct) + '%');
    },

    _removePlaceholder: function($zone, id) {
      $zone.find('[data-upload-id="' + id + '"]').remove();
    },

    processFiles: async function($zone, files) {
      if (!$zone || !$zone.length) return;

      var self = this;
      var urls = this.getUrls($zone);
      var remaining = MAX_IMAGES - urls.length;

      if (remaining <= 0) {
        this.showError($zone, 'You can upload up to ' + MAX_IMAGES + ' images.');
        return;
      }

      var fileList = Array.from(files || []).slice(0, remaining);
      if (!fileList.length) return;

      if (!this._uploadHandler) {
        this.showError($zone, 'Image upload is not available.');
        return;
      }

      var qId = $zone.data('question-id');
      var hadError = false;
      var $list = $zone.find('.q-image-upload-list');

      for (var i = 0; i < fileList.length; i++) {
        var file = fileList[i];
        var fileError = this.validateFile(file);
        if (fileError) {
          hadError = true;
          this.showError($zone, fileError);
          continue;
        }

        var uploadId = 'upl_' + Date.now() + '_' + i;
        $list.append(self._buildPlaceholder(uploadId));
        $zone.addClass('has-images');
        $zone.find('.q-image-upload-count').text(String(urls.length + 1));

        try {
          var progressCb = function(pct) { self._updatePlaceholder($zone, uploadId, pct); };
          var imageUrl = await this._uploadHandler(file, qId, progressCb);
          self._removePlaceholder($zone, uploadId);
          if (imageUrl) {
            urls.push(imageUrl);
            this.setUrls($zone, urls);
            this.clearError($zone);
          }
        } catch (error) {
          self._removePlaceholder($zone, uploadId);
          hadError = true;
          this.showError($zone, (error && error.message) ? error.message : 'Image upload failed. Please try again.');
        }
      }

      if (!hadError) {
        this.clearError($zone);
      }

      $zone.find('.js-question-file').val('');
    },

    initIn: function($root) {
      var self = this;

      ($root || $(document)).find('.q-image-upload').each(function() {
        var $zone = $(this);
        if ($zone.data('qImageInit')) return;
        $zone.data('qImageInit', true);
        $zone.data('imageUrls', self.getUrls($zone));

        var $input = $zone.find('.js-question-file');

        $input.on('change', function() {
          self.processFiles($zone, this.files);
        });

        $zone.on('click', '.q-image-thumb-remove', function(event) {
          event.preventDefault();
          event.stopPropagation();
          var index = parseInt($(this).data('index'), 10);
          if (isNaN(index)) return;
          self.removeAt($zone, index);
          self.clearError($zone);
        });

        $zone.on('dragover dragenter', function(event) {
          event.preventDefault();
          event.stopPropagation();
          if (self.getUrls($zone).length < MAX_IMAGES) {
            $zone.addClass('is-dragover');
          }
        });

        $zone.on('dragleave dragend drop', function(event) {
          event.preventDefault();
          event.stopPropagation();
          $zone.removeClass('is-dragover');
        });

        $zone.on('drop', function(event) {
          var dt = event.originalEvent && event.originalEvent.dataTransfer
            ? event.originalEvent.dataTransfer.files
            : null;
          if (!dt || !dt.length) return;
          self.processFiles($zone, dt);
        });

        self.render($zone);
      });
    }
  };

  $(function() {
    window.QuestionImageField.initIn($(document));
  });
})(window, jQuery);
</script>
