<style>
  /* Match client-book .upl / .thumbs / .thumb */
  .q-image-upload {
    width: 100%;
  }
  .q-image-upload-trigger {
    display: block;
    width: 100%;
    border: 1.5px dashed var(--line2, #DCCFE3);
    border-radius: 12px;
    background: #fff;
    padding: 22px;
    text-align: center;
    color: var(--muted, #6E6475);
    font-size: 13px;
    cursor: pointer;
    box-sizing: border-box;
    transition: border-color .15s, background .15s;
  }
  .q-image-upload-trigger:hover,
  .q-image-upload.is-dragover .q-image-upload-trigger {
    border-color: var(--p2, #5A238E);
    background: var(--pl, #F5ECFB);
  }
  .q-image-upload.is-full .q-image-upload-trigger {
    display: none;
  }
  .q-image-upload-trigger .ms {
    font-size: 28px;
    color: var(--p, #3E007C);
    display: block;
    margin: 0 auto 6px;
    line-height: 1;
  }
  .q-image-upload-trigger b {
    color: var(--ink, #1D1A21);
    font-weight: 700;
  }
  .q-image-upload-list {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 10px;
  }
  .q-image-upload-list:empty {
    display: none;
    margin: 0;
  }
  .q-image-thumb {
    width: 64px;
    height: 64px;
    border-radius: 10px;
    position: relative;
    background: linear-gradient(135deg, #E9D6F5, #C7A6E0);
    overflow: visible;
    flex: none;
  }
  .q-image-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    border-radius: 10px;
    pointer-events: none;
  }
  .q-image-thumb-uploading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    border-radius: 10px;
    background: linear-gradient(135deg, #E9D6F5, #C7A6E0);
    padding: 6px;
    box-sizing: border-box;
  }
  .q-image-thumb-uploading .material-symbols-outlined {
    font-size: 18px;
    color: #3E007C;
    margin-bottom: 4px;
    opacity: .7;
  }
  .q-image-thumb-progress {
    width: 70%;
    height: 3px;
    border-radius: 2px;
    background: rgba(62, 0, 124, 0.15);
    overflow: hidden;
  }
  .q-image-thumb-progress-bar {
    height: 100%;
    border-radius: 2px;
    background: #3E007C;
    width: 0%;
    transition: width 0.2s ease;
  }
  .q-image-thumb-pct {
    font-size: 9px;
    color: #3E007C;
    margin-top: 3px;
    font-weight: 700;
  }
  .q-image-thumb-remove {
    position: absolute;
    top: -6px;
    right: -6px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #1D1A21;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 0;
    padding: 0;
    cursor: pointer;
    z-index: 2;
  }
  .q-image-thumb-remove:hover {
    background: #C0262D;
  }
  .q-image-thumb-remove .material-symbols-outlined {
    font-size: 13px;
    line-height: 1;
    pointer-events: none;
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
  .q-image-upload-hint {
    font-size: 12px;
    color: var(--faint, #9C92A3);
    margin-top: 8px;
  }
  .q-image-upload.is-full .q-image-upload-hint {
    display: none;
  }
</style>
<script>
(function(window, $) {
  'use strict';
  if (!$) return;

  var MAX_IMAGES = 5;
  var MAX_BYTES = 10 * 1024 * 1024;
  var UPLOAD_TARGET_BYTES = 1.6 * 1024 * 1024;
  var ALLOWED_MIME = {
    'image/jpeg': true,
    'image/jpg': true,
    'image/png': true,
    'image/webp': true,
    'image/heic': true,
    'image/heif': true
  };
  var ALLOWED_EXT = { jpg: true, jpeg: true, png: true, webp: true, heic: true, heif: true };

  // Keep compressed uploads under common live PHP/nginx 1–2MB caps.
  function escapeHtml(str) {
    return String(str == null ? '' : str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function fileExtension(file) {
    var name = String((file && file.name) || '');
    var i = name.lastIndexOf('.');
    return i >= 0 ? name.slice(i + 1).toLowerCase() : '';
  }

  function safeUploadFilename(originalName, fallbackExt) {
    var raw = String(originalName || 'upload');
    var ext = '';
    var dot = raw.lastIndexOf('.');
    if (dot >= 0) {
      ext = raw.slice(dot + 1).toLowerCase().replace(/[^a-z0-9]/g, '');
      raw = raw.slice(0, dot);
    }
    if (!ext) ext = String(fallbackExt || 'jpg').toLowerCase();
    var base = raw.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    if (!base) base = 'upload';
    return base.slice(0, 40) + '.' + ext;
  }

  function blobToJpegFile(blob, name) {
    try {
      return new File([blob], safeUploadFilename(name, 'jpg'), { type: 'image/jpeg' });
    } catch (e) {
      blob.name = safeUploadFilename(name, 'jpg');
      return blob;
    }
  }

  window.QuestionImageField = {
    maxBytes: MAX_BYTES,
    maxImages: MAX_IMAGES,
    _uploadHandler: null,

    setUploadHandler: function(handler) {
      this._uploadHandler = typeof handler === 'function' ? handler : null;
    },

    appendImageToFormData: function(formData, file) {
      var mime = String((file && file.type) || '').toLowerCase();
      var fallbackExt = mime.indexOf('png') !== -1 ? 'png' : (mime.indexOf('webp') !== -1 ? 'webp' : 'jpg');
      var name = safeUploadFilename((file && file.name) || ('upload.' + fallbackExt), fallbackExt);
      formData.append('image', file, name);
    },

    parseUploadXhrError: function(xhr) {
      var fallback = 'Image upload failed. Please try a JPG photo under 10MB.';
      if (!xhr) return fallback;
      if (xhr.status === 0) {
        return 'Network error during upload. Please check your connection and try again.';
      }
      if (xhr.status === 413) {
        return 'Image is too large for the server upload limit. Please try a smaller photo.';
      }
      if (xhr.status === 419) {
        return 'Your session expired. Please refresh the page and try again.';
      }
      if (xhr.status === 403 || xhr.status === 401) {
        return 'Upload was blocked by the server. Try again, or rename the file (avoid apostrophes/spaces) and re-upload.';
      }
      var data = null;
      var raw = String(xhr.responseText || '');
      try {
        data = raw ? JSON.parse(raw) : null;
      } catch (e) {
        if (xhr.status >= 500) {
          return 'Server error while uploading (HTTP ' + xhr.status + '). Please try again.';
        }
        if (/request entity too large|413/i.test(raw)) {
          return 'Image is too large for the server upload limit. Please try a smaller photo.';
        }
        return 'Image upload failed (HTTP ' + xhr.status + '). Please refresh and try a JPG under 10MB.';
      }
      if (data && data.message) return String(data.message);
      if (data && data.errors) {
        var first = null;
        Object.keys(data.errors).some(function(key) {
          if (data.errors[key] && data.errors[key][0]) {
            first = data.errors[key][0];
            return true;
          }
          return false;
        });
        if (first) return String(first);
      }
      if (xhr.status) {
        return 'Image upload failed (HTTP ' + xhr.status + '). Please try a JPG photo under 10MB.';
      }
      return fallback;
    },

    buildHtml: function(questionId) {
      var inputId = 'q-image-input-' + String(questionId);
      return '' +
        '<div class="q-image-upload relative" data-question-id="' + questionId + '">' +
          '<input type="file" id="' + inputId + '" accept="image/*,image/heic,image/heif,.heic,.heif,.jpg,.jpeg,.png,.webp" multiple data-question-id="' + questionId + '" class="q-image-upload-input js-question-file">' +
          '<label for="' + inputId + '" class="q-image-upload-trigger">' +
            '<span class="material-symbols-outlined ms" aria-hidden="true">add_photo_alternate</span>' +
            '<b>Tap to add a photo</b><br>JPG or PNG, up to 10 MB each' +
          '</label>' +
          '<div class="q-image-upload-list" aria-live="polite"></div>' +
          '<p class="q-image-upload-hint">Add up to ' + MAX_IMAGES + ' photos.</p>' +
        '</div>';
    },

    validateFile: function(file) {
      if (!file) return 'Please choose an image.';
      if (file.size > MAX_BYTES) {
        return 'Image must be 10MB or smaller.';
      }

      var mime = String(file.type || '').toLowerCase();
      var ext = fileExtension(file);

      if (mime && ALLOWED_MIME[mime]) return '';
      if (ext && ALLOWED_EXT[ext]) return '';
      if (!mime && !ext) return '';
      if (mime === 'application/octet-stream' && (!ext || ALLOWED_EXT[ext])) return '';
      if (mime.indexOf('image/') === 0) return '';

      return 'Please upload a photo (JPG, PNG, or HEIC).';
    },

    prepareFileForUpload: function(file) {
      return new Promise(function(resolve, reject) {
        if (!file) {
          reject(new Error('Please choose an image.'));
          return;
        }

        var url = URL.createObjectURL(file);
        var img = new Image();
        img.onload = function() {
          URL.revokeObjectURL(url);
          var maxEdge = 2400;
          var width = img.naturalWidth || img.width || 1;
          var height = img.naturalHeight || img.height || 1;
          var scale = Math.min(1, maxEdge / Math.max(width, height));
          var canvas = document.createElement('canvas');
          canvas.width = Math.max(1, Math.round(width * scale));
          canvas.height = Math.max(1, Math.round(height * scale));
          var ctx = canvas.getContext('2d');
          if (!ctx) {
            reject(new Error('Unable to process this image. Please try a JPG instead.'));
            return;
          }
          ctx.fillStyle = '#ffffff';
          ctx.fillRect(0, 0, canvas.width, canvas.height);
          ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

          var quality = file.size > UPLOAD_TARGET_BYTES ? 0.82 : 0.9;
          var tryExport = function() {
            canvas.toBlob(function(blob) {
              if (!blob) {
                reject(new Error('Unable to process this image. Please try a JPG instead.'));
                return;
              }
              if (blob.size > UPLOAD_TARGET_BYTES && quality > 0.5) {
                quality = Math.max(0.5, quality - 0.1);
                tryExport();
                return;
              }
              if (blob.size > MAX_BYTES) {
                reject(new Error('Image is still too large after compression. Please choose a smaller photo.'));
                return;
              }
              resolve(blobToJpegFile(blob, file.name || 'photo.jpg'));
            }, 'image/jpeg', quality);
          };
          tryExport();
        };
        img.onerror = function() {
          URL.revokeObjectURL(url);
          var ext = fileExtension(file);
          var mime = String(file.type || '').toLowerCase();
          if (ext === 'heic' || ext === 'heif' || mime.indexOf('heic') !== -1 || mime.indexOf('heif') !== -1) {
            reject(new Error('This iPhone photo format could not be read. Please choose JPG, or set Camera to Most Compatible.'));
            return;
          }
          reject(new Error('Could not read this image. Please try another JPG or PNG.'));
        };
        img.decoding = 'async';
        img.src = url;
      });
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
            '<img src="' + escapeHtml(url) + '" alt="Uploaded photo ' + (index + 1) + '">' +
            '<button type="button" class="q-image-thumb-remove" data-index="' + index + '" aria-label="Remove photo">' +
              '<span class="material-symbols-outlined">close</span>' +
            '</button>' +
          '</div>'
        );
      });

      $zone.toggleClass('has-images', urls.length > 0);
      $zone.toggleClass('is-full', urls.length >= MAX_IMAGES);
      var $hint = $zone.find('.q-image-upload-hint');
      if ($hint.length) {
        $hint.text(urls.length >= MAX_IMAGES
          ? ''
          : (urls.length ? 'Add up to ' + (MAX_IMAGES - urls.length) + ' more.' : 'Add up to ' + MAX_IMAGES + ' photos.'));
      }
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
      var $err = $zone.closest('.question-div').find('.js-question-error');
      var $text = $err.find('.js-question-error-text');
      if ($text.length) $text.text(message || 'Unable to upload image.');
      else $err.text(message || 'Unable to upload image.');
      $err.removeClass('hidden');
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

        try {
          var progressCb = function(pct) { self._updatePlaceholder($zone, uploadId, pct); };
          var readyFile = await this.prepareFileForUpload(file);
          var imageUrl = await this._uploadHandler(readyFile, qId, progressCb);
          self._removePlaceholder($zone, uploadId);
          if (imageUrl) {
            urls.push(imageUrl);
            this.setUrls($zone, urls);
            this.clearError($zone);
          } else {
            hadError = true;
            this.showError($zone, 'Image upload failed. Please try again.');
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
          $zone.addClass('is-dragover');
        });

        $zone.on('dragleave dragend drop', function(event) {
          event.preventDefault();
          event.stopPropagation();
          $zone.removeClass('is-dragover');
        });

        $zone.on('drop', function(event) {
          var dt = event.originalEvent && event.originalEvent.dataTransfer;
          if (!dt || !dt.files || !dt.files.length) return;
          self.processFiles($zone, dt.files);
        });

        self.render($zone);
      });
    }
  };

  $(function() {
    window.QuestionImageField.initIn($(document));
  });
})(window, window.jQuery);
</script>
