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
  // Keep compressed uploads under common live PHP/nginx 1–2MB caps.
  var UPLOAD_TARGET_BYTES = 900 * 1024;
  var ALLOWED_MIME = {
    'image/jpeg': true,
    'image/jpg': true,
    'image/pjpeg': true,
    'image/png': true,
    'image/webp': true,
    'image/heic': true,
    'image/heif': true,
    'image/heic-sequence': true,
    'image/heif-sequence': true
  };
  var ALLOWED_EXT = {
    jpg: true,
    jpeg: true,
    png: true,
    webp: true,
    heic: true,
    heif: true
  };

  function escapeHtml(str) {
    return String(str || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function fileExtension(file) {
    var name = String((file && file.name) || '');
    var match = name.toLowerCase().match(/\.([a-z0-9]+)$/);
    return match ? match[1] : '';
  }

  // Hosts/WAFs often 403 filenames with quotes, spaces, or unicode (e.g. Investor's Night...).
  function safeUploadFilename(originalName, fallbackExt) {
    var raw = String(originalName || 'upload');
    var extMatch = raw.toLowerCase().match(/\.([a-z0-9]+)$/);
    var ext = (extMatch ? extMatch[1] : '') || String(fallbackExt || 'jpg');
    if (!/^(jpe?g|png|webp|heic|heif)$/i.test(ext)) {
      ext = 'jpg';
    }
    if (ext === 'jpeg') ext = 'jpg';
    var base = raw.replace(/\.[^.]+$/, '');
    base = base
      .replace(/['’`"]/g, '')
      .replace(/[^A-Za-z0-9._-]+/g, '_')
      .replace(/_+/g, '_')
      .replace(/^[._-]+|[._-]+$/g, '');
    if (!base) base = 'upload';
    if (base.length > 80) base = base.slice(0, 80);
    return base + '.' + ext;
  }

  function blobToJpegFile(blob, originalName) {
    var filename = safeUploadFilename(originalName, 'jpg').replace(/\.[^.]+$/, '') + '.jpg';
    try {
      return new File([blob], filename, { type: 'image/jpeg', lastModified: Date.now() });
    } catch (e) {
      try {
        blob.name = filename;
        blob.lastModifiedDate = new Date();
        blob.lastModified = Date.now();
      } catch (ignored) {}
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
      // Always send a sanitized filename — apostrophes/spaces trigger live 403s.
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
          '<div class="q-image-upload-list" aria-live="polite"></div>' +
          '<label for="' + inputId + '" class="q-image-upload-empty">' +
            '<span class="material-symbols-outlined" aria-hidden="true">cloud_upload</span>' +
            '<p class="q-image-upload-text q-image-upload-text-primary">Drop images here or <span class="q-image-upload-browse">browse</span></p>' +
            '<p class="q-image-upload-text q-image-upload-text-more hidden">Add more images</p>' +
            '<p class="q-image-upload-hint">Photos up to 10MB each (JPG, PNG, HEIC)</p>' +
          '</label>' +
          '<p class="q-image-upload-meta"><span class="q-image-upload-count">0</span>/' + MAX_IMAGES + ' images</p>' +
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
      // iOS camera / Drive may omit both; allow and try canvas convert next.
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
          // White background so transparent PNGs become valid JPEGs.
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
