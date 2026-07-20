<script>
(function () {
  var HOST_RULES = {
    instagram: [/^(?:www\.)?instagram\.com$/i],
    tiktok: [/^(?:www\.)?tiktok\.com$/i],
    youtube: [/^(?:www\.)?(?:youtube\.com|youtu\.be)$/i, /^m\.youtube\.com$/i],
    facebook: [/^(?:www\.)?(?:facebook|fb)\.com$/i, /^m\.facebook\.com$/i, /^web\.facebook\.com$/i],
  };

  var EXAMPLES = {
    instagram: 'https://www.instagram.com/yourhandle',
    tiktok: 'https://www.tiktok.com/@yourhandle',
    youtube: 'https://www.youtube.com/@yourchannel',
    facebook: 'https://www.facebook.com/yourpage',
  };

  var LABELS = {
    instagram: 'Instagram',
    tiktok: 'TikTok',
    youtube: 'YouTube',
    facebook: 'Facebook',
  };

  function hostMatches(platform, host) {
    var patterns = HOST_RULES[platform] || [];
    for (var i = 0; i < patterns.length; i++) {
      if (patterns[i].test(host)) return true;
    }
    return false;
  }

  function isValidHandle(value) {
    return /^@?[a-zA-Z0-9._-]+$/.test(String(value || '').trim());
  }

  function validatePlatform(platform, value) {
    var raw = String(value || '').trim();
    if (!raw) return { ok: true };

    if (/^https?:\/\//i.test(raw)) {
      try {
        var url = new URL(raw);
        if (url.protocol !== 'http:' && url.protocol !== 'https:') {
          return { ok: false, message: errorMessage(platform) };
        }
        if (!hostMatches(platform, url.hostname)) {
          return { ok: false, message: errorMessage(platform) };
        }
        return { ok: true };
      } catch (e) {
        return { ok: false, message: errorMessage(platform) };
      }
    }

    if (platform === 'facebook') {
      return { ok: false, message: errorMessage(platform) };
    }

    if (isValidHandle(raw)) {
      return { ok: true };
    }

    return { ok: false, message: errorMessage(platform) };
  }

  function errorMessage(platform) {
    return 'Please enter a valid ' + (LABELS[platform] || platform) + ' URL (e.g. ' + (EXAMPLES[platform] || '') + ').';
  }

  function validateWebsite(value) {
    var raw = String(value || '').trim();
    if (!raw) return { ok: true };
    if (!/^https?:\/\//i.test(raw)) {
      return { ok: false, message: 'Website must start with http:// or https://' };
    }
    try {
      var url = new URL(raw);
      if (url.protocol !== 'http:' && url.protocol !== 'https:') {
        return { ok: false, message: 'Please enter a valid website URL.' };
      }
      return { ok: true };
    } catch (e) {
      return { ok: false, message: 'Please enter a valid website URL.' };
    }
  }

  window.SocialLinkValidation = {
    validatePlatform: validatePlatform,
    validateWebsite: validateWebsite,
    errorMessage: errorMessage,
  };
})();
</script>
