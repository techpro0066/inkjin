<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Session details — Bookpay</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="icon" href="{{ asset('assets/img/favicon/favicon.png') }}">
  <link href="{{ asset('assets/design/css/inkjin_main.css') }}" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            "primary": "#310f7a",
            "primary-container": "#482d91",
            "on-primary": "#ffffff",
            "on-surface": "#1c1b21",
            "on-surface-variant": "#494552",
            "outline-variant": "#cac4d3",
            "surface-container-low": "#f8f1fb",
            "error": "#ba1a1a",
          },
          fontFamily: { sans: ["Plus Jakarta Sans", "sans-serif"] },
        },
      },
    }
  </script>
  <style>
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    .question-div { display: none; }
    .question-div.active { display: block; animation: tfSlideIn 0.35s ease-out; }
    @keyframes tfSlideIn { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
    .question-kicker {
      display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.35rem 0.85rem;
      border-radius: 9999px; border: 1px solid #ddd0ff; background: #f8f1fb;
      color: #310f7a; font-size: 0.78rem; font-weight: 700; margin-bottom: 0.75rem;
    }
    .question-kicker .dot { width: 0.45rem; height: 0.45rem; border-radius: 9999px; background: #310f7a; }
    .single-choice-radio-button { padding: 0.75rem 1.5rem; border-radius: 9999px; border: 2px solid #cac4d3; font-size: 0.95rem; font-weight: 600; color: #494552; cursor: pointer; transition: all 0.15s; background: white; }
    .single-choice-radio-button:hover { border-color: #310f7a; color: #310f7a; }
    .single-choice-radio-button.selected { background: #310f7a; color: white; border-color: #310f7a; }
    .single-choice-radio-button.option-other { background: #f2ecf5; border-color: #b69fff; color: #310f7a; }
    .style-other-modal { position: fixed; inset: 0; z-index: 200; display: flex; align-items: center; justify-content: center; padding: 1rem; }
    .style-other-modal.hidden { display: none !important; }
    .style-other-modal-backdrop { position: absolute; inset: 0; background: rgba(28, 27, 33, 0.55); }
    .style-other-modal-panel { position: relative; z-index: 1; width: 100%; max-width: min(42rem, calc(100vw - 2rem)); background: #ffffff; border-radius: 1.25rem; padding: 1.25rem; max-height: min(90vh, 40rem); display: flex; flex-direction: column; }
    .style-other-results { overflow-y: auto; border: 1px solid #ece6ef; border-radius: 1rem; padding: 0.75rem; display: flex; flex-wrap: wrap; gap: 0.5rem; }
    .style-other-result-item { padding: 0.65rem 1rem; font-size: 0.875rem; font-weight: 600; color: #494552; background: white; border: 2px solid #cac4d3; border-radius: 9999px; cursor: pointer; }
    .style-other-result-item.selected, .style-other-result-item:hover { background: #310f7a; color: #fff; border-color: #310f7a; }
    .q-toggle-row { display: flex; align-items: flex-start; gap: 0.85rem; padding: 1rem; border: 1px solid rgba(122,117,131,0.32); border-radius: 0.9rem; background: #fff; }
    .q-toggle-control { position: relative; display: inline-flex; width: 54px; min-width: 54px; height: 31px; }
    .q-toggle-input { position: absolute; opacity: 0; width: 0; height: 0; }
    .q-toggle-ui { position: relative; display: inline-block; width: 54px; height: 31px; border-radius: 9999px; background: #a8c7ff; cursor: pointer; }
    .q-toggle-ui::after { content: ""; position: absolute; top: 3px; left: 3px; width: 25px; height: 25px; border-radius: 50%; background: #fff; transition: transform 0.2s ease; }
    .q-toggle-input:checked + .q-toggle-ui { background: #1e6bff; }
    .q-toggle-input:checked + .q-toggle-ui::after { transform: translateX(23px); }
  </style>
</head>
<body class="min-h-screen bg-[#fdf7ff] font-sans flex items-center justify-center p-4">
  <div class="w-full max-w-xl bg-white rounded-2xl border border-outline-variant/30 p-6 sm:p-8 shadow-sm">
    <p class="text-sm font-semibold tracking-tight text-on-surface-variant mb-5" style="font-family: 'Space Grotesk', sans-serif;">bookpay</p>

    @if(!empty($linkExpired))
      <div class="flex items-center justify-center w-12 h-12 rounded-2xl bg-error-container/60 text-error mb-4">
        <span class="material-symbols-outlined text-3xl">event_busy</span>
      </div>
      <h1 class="text-xl font-bold text-on-surface mb-2">This link has expired</h1>
      <p class="text-sm text-on-surface-variant leading-relaxed">The payment link is no longer valid, so session details can’t be submitted.</p>
    @elseif($alreadySubmitted)
      <div class="flex items-center gap-3 mb-4">
        <div class="flex items-center justify-center w-8 h-8 rounded-full bg-[#e6f4ea]">
          <span class="material-symbols-outlined text-[18px] text-[#1b7f3a]" style="font-variation-settings: 'FILL' 1, 'wght' 600, 'GRAD' 0, 'opsz' 24;">check</span>
        </div>
        <h1 class="text-xl font-bold text-on-surface">Details received</h1>
      </div>
      <p class="text-sm text-on-surface-variant leading-relaxed">Thanks — {{ $artistHeader['name'] }} has everything they need for your session.</p>
    @elseif(count($questions) === 0)
      <h1 class="text-xl font-bold text-on-surface mb-2">You're all set</h1>
      <p class="text-sm text-on-surface-variant leading-relaxed">{{ $artistHeader['name'] }} doesn't need any extra details right now.</p>
    @else
      <h1 class="text-xl font-bold text-on-surface mb-1">A few quick questions</h1>
      <p class="text-sm text-on-surface-variant mb-6">For your session with {{ $artistHeader['name'] }}. Takes about two minutes.</p>
      <div id="questionsMount"></div>
      <p id="formError" class="hidden text-sm text-error mt-4"></p>
    @endif
  </div>

  @unless(!empty($linkExpired) || $alreadySubmitted || count($questions) === 0)
    <div id="styleOtherModal" class="style-other-modal hidden" aria-hidden="true">
      <div class="style-other-modal-backdrop js-style-other-close"></div>
      <div class="style-other-modal-panel">
        <div class="flex items-center justify-between gap-4 mb-4">
          <h3 id="styleOtherModalTitle" class="style-other-modal-title font-bold">Choose a style</h3>
          <button type="button" class="js-style-other-close w-9 h-9 rounded-full bg-[#f2ecf5]" aria-label="Close">
            <span class="material-symbols-outlined text-[20px]">close</span>
          </button>
        </div>
        <div class="js-style-other-results style-other-results"></div>
      </div>
    </div>
  @endunless

  @unless(!empty($linkExpired) || $alreadySubmitted || count($questions) === 0)
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  @include('public.partials.question-image-upload')
  <script>
  (function () {
    var csrfToken = @json(csrf_token());
    var storeUrl = @json($storeUrl);
    var imageUrl = @json($imageUrl);
    var serverQuestions = @json($questions);
    var hiddenStyleOptions = @json($hiddenStyleOptions ?? []);
    var hiddenPlacementOptions = @json($hiddenPlacementOptions ?? []);
    var questionAnswers = {};
    var currentQuestionIndex = 0;
    var styleOtherModalContext = null;
    var submitting = false;

    var questionDefinitions = (Array.isArray(serverQuestions) ? serverQuestions : []).map(function (q) {
      var typeMap = { text: 'input', free: 'input', images: 'image', checkbox: 'toggle' };
      var normalizedType = typeMap[q.type] || q.type || 'input';
      var opts = Array.isArray(q.options) ? q.options : [];
      if (normalizedType === 'toggle' && !opts.length) opts = ['Yes', 'No'];
      return {
        id: q.id,
        title: q.question || 'Question',
        subtitle: q.description || 'Please answer this question.',
        type: normalizedType,
        options: opts,
        placeholder: q.placeholder || '',
        required: !!q.is_required
      };
    });

    function escapeHtml(str) {
      return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function getQuestionType(qId) {
      var def = questionDefinitions.find(function (q) { return String(q.id) === String(qId); });
      return def ? String(def.type || '') : '';
    }
    function isCatalogChoiceQuestion(qId) {
      var type = getQuestionType(qId);
      return type === 'style' || type === 'placement';
    }
    function getHiddenCatalogOptions(qId) {
      return getQuestionType(qId) === 'placement' ? hiddenPlacementOptions : hiddenStyleOptions;
    }

    function buildStructuredQuestionAnswers() {
      var output = {};
      questionDefinitions.forEach(function (q) {
        if (!q || typeof q.id === 'undefined' || q.id === null) return;
        var answer = questionAnswers[q.id];
        if (typeof answer === 'string') answer = answer.trim();
        if (answer === undefined || answer === null || answer === '') return;
        if (Array.isArray(answer) && answer.length === 0) return;
        output[String(q.id)] = {
          id: q.id,
          question: String(q.title || ''),
          type: String(q.type || 'input'),
          answer: answer
        };
      });
      return output;
    }

    async function uploadQuestionImage(file, questionId, progressCb) {
      var formData = new FormData();
      formData.append('image', file);
      formData.append('question_id', String(questionId || ''));
      return await new Promise(function(resolve, reject) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', imageUrl);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        if (xhr.upload && typeof progressCb === 'function') {
          xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) progressCb(Math.round((e.loaded / e.total) * 100));
          });
        }
        xhr.onload = function() {
          try {
            var data = JSON.parse(xhr.responseText);
            if (xhr.status >= 200 && xhr.status < 300 && data && data.success) {
              resolve(data.file_url || data.file_path || '');
            } else {
              reject(new Error((data && data.message) || 'Image upload failed.'));
            }
          } catch(e) { reject(new Error('Image upload failed.')); }
        };
        xhr.onerror = function() { reject(new Error('Network error during upload.')); };
        xhr.send(formData);
      });
    }

    if (window.QuestionImageField) {
      window.QuestionImageField.setUploadHandler(uploadQuestionImage);
    }

    function renderQuestions() {
      var html = '';
      questionDefinitions.forEach(function (q, idx) {
        var isFirst = idx === 0;
        var isLast = idx === questionDefinitions.length - 1;
        var body = '';
        if (q.type === 'radio' || q.type === 'style' || q.type === 'placement' || q.type === 'sizes') {
          var radioButtons = q.options.map(function (opt) {
            var isOther = String(opt || '').trim().toLowerCase() === 'other';
            return '<button type="button" class="single-choice-radio-button' + (isOther && isCatalogChoiceQuestion(q.id) ? ' option-other' : '') + '" data-value="' + escapeHtml(opt) + '">' + escapeHtml(opt) + '</button>';
          }).join('');
          body = '<div class="flex flex-wrap gap-2 single-choice-group">' + radioButtons + '</div>';
        } else if (q.type === 'select') {
          body = '<select class="w-full js-select2-question" data-question-id="' + q.id + '"><option value="">Choose an option</option>' +
            q.options.map(function (opt) { return '<option value="' + escapeHtml(opt) + '">' + escapeHtml(opt) + '</option>'; }).join('') + '</select>';
        } else if (q.type === 'input') {
          body = '<input type="text" placeholder="' + escapeHtml(q.placeholder) + '" data-question-id="' + q.id + '" class="js-question-input w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">';
        } else if (q.type === 'textarea') {
          body = '<textarea rows="4" placeholder="' + escapeHtml(q.placeholder) + '" data-question-id="' + q.id + '" class="js-question-input w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>';
        } else if (q.type === 'image') {
          body = window.QuestionImageField ? window.QuestionImageField.buildHtml(q.id) : '';
        } else if (q.type === 'toggle') {
          body = '<label class="q-toggle-row"><span class="q-toggle-control"><input type="checkbox" data-question-id="' + q.id + '" class="q-toggle-input js-question-toggle"><span class="q-toggle-ui"></span></span><span class="q-toggle-label">' + escapeHtml(q.subtitle) + '</span></label>';
        }
        var navButton = isLast
          ? '<button type="button" class="js-submit-details inline-flex items-center gap-2 px-6 py-3 bg-[#1c1b21] text-white rounded-xl font-bold text-sm">Submit details</button>'
          : '<button type="button" class="js-next-question inline-flex items-center gap-2 px-6 py-3 bg-[#1c1b21] text-white rounded-xl font-bold text-sm">Next <span class="material-symbols-outlined text-[18px]">arrow_forward</span></button>';
        html +=
          '<div class="question-div' + (isFirst ? ' active' : '') + '" data-q="' + idx + '" data-question-id="' + q.id + '" data-question-type="' + q.type + '" data-required="' + (q.required ? '1' : '0') + '">' +
            (isFirst ? '' : '<button type="button" class="js-prev-question flex items-center gap-1 text-sm text-on-surface-variant hover:text-on-surface mb-4"><span class="material-symbols-outlined text-[18px]">arrow_back</span> Back</button>') +
            '<p class="question-kicker"><span class="dot"></span>Question ' + (idx + 1) + ' of ' + questionDefinitions.length + '</p>' +
            '<h2 class="text-2xl font-bold text-on-surface mb-2">' + escapeHtml(q.title) + '</h2>' +
            '<p class="text-on-surface-variant mb-6">' + escapeHtml(q.subtitle) + (q.required ? ' <span class="text-error">*</span>' : '') + '</p>' +
            body +
            '<p class="text-sm text-error hidden mt-3 js-question-error">Please answer this required question.</p>' +
            '<div class="flex items-center justify-end mt-6">' + navButton + '</div>' +
          '</div>';
      });
      $('#questionsMount').html(html);
      if (window.QuestionImageField) window.QuestionImageField.initIn($('#questionsMount'));
      $('.js-select2-question').select2({ width: '100%' });
    }

    function getCurrentQuestionDiv() {
      return $('div.question-div.active[data-q]');
    }

    function validateActiveQuestion() {
      var $active = getCurrentQuestionDiv();
      if (!$active.length) return true;
      if (String($active.data('required')) !== '1') {
        $active.find('.js-question-error').addClass('hidden');
        return true;
      }
      var qType = String($active.data('question-type') || '');
      var qId = $active.data('question-id');
      var hasValue = false;
      if (qType === 'radio' || qType === 'style' || qType === 'placement' || qType === 'sizes') {
        var $selected = $active.find('.single-choice-radio-button.selected');
        hasValue = $selected.length > 0;
        if (hasValue && isCatalogChoiceQuestion(qId) && String($selected.data('value') || '').trim().toLowerCase() === 'other') {
          hasValue = !!String(questionAnswers[qId] || '').trim();
        }
      } else if (qType === 'select') {
        hasValue = !!String($active.find('.js-select2-question').val() || '').trim();
      } else if (qType === 'input' || qType === 'textarea') {
        hasValue = !!String($active.find('.js-question-input').val() || '').trim();
      } else if (qType === 'image') {
        var imageAnswer = questionAnswers[qId];
        hasValue = Array.isArray(imageAnswer) ? imageAnswer.length > 0 : !!String(imageAnswer || '').trim();
      } else if (qType === 'toggle') {
        hasValue = $active.find('.js-question-toggle').is(':checked');
      } else {
        hasValue = !!questionAnswers[qId];
      }
      $active.find('.js-question-error').toggleClass('hidden', hasValue);
      return hasValue;
    }

    function showQuestion(index) {
      var questions = $('div.question-div[data-q]');
      if (index < 0) index = 0;
      if (index >= questions.length) index = questions.length - 1;
      questions.removeClass('active');
      questions.filter('[data-q="' + index + '"]').addClass('active');
      currentQuestionIndex = index;
    }

    function nextQuestion() {
      if (!validateActiveQuestion()) return;
      if (currentQuestionIndex >= questionDefinitions.length - 1) {
        submitDetails();
        return;
      }
      showQuestion(currentQuestionIndex + 1);
    }

    async function submitDetails() {
      if (submitting) return;
      if (!validateActiveQuestion()) return;
      submitting = true;
      var $btn = $('.js-submit-details');
      $btn.prop('disabled', true).text('Saving...');
      $('#formError').addClass('hidden').text('');
      try {
        var response = await fetch(storeUrl, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({ questions_answers: buildStructuredQuestionAnswers() })
        });
        var data = await response.json();
        if (!response.ok || !data || !data.saved) {
          throw new Error((data && data.message) || 'Could not save your answers.');
        }
        window.location.reload();
      } catch (err) {
        $('#formError').removeClass('hidden').text(err.message || 'Could not save your answers.');
        $btn.prop('disabled', false).text('Submit details');
        submitting = false;
      }
    }

    $(document).on('qimages:updated', '.q-image-upload', function (_event, questionId, urls) {
      if (!questionId) return;
      questionAnswers[questionId] = Array.isArray(urls) ? urls.slice() : [];
    });

    $(document).on('click', '.single-choice-radio-button', function () {
      var $btn = $(this);
      var $div = $btn.closest('.question-div');
      var qId = $div.data('question-id');
      var value = String($btn.data('value') || '');
      $div.find('.single-choice-radio-button').removeClass('selected');
      $btn.addClass('selected');
      $div.find('.js-question-error').addClass('hidden');
      if (isCatalogChoiceQuestion(qId) && value.trim().toLowerCase() === 'other') {
        delete questionAnswers[qId];
        styleOtherModalContext = { qId: qId, $questionDiv: $div };
        var $modal = $('#styleOtherModal');
        $modal.find('.style-other-modal-title').text(getQuestionType(qId) === 'placement' ? 'Choose a placement' : 'Choose a style');
        var opts = getHiddenCatalogOptions(qId) || [];
        $modal.find('.js-style-other-results').html(opts.length
          ? opts.map(function (name) { return '<button type="button" class="js-style-other-result-item style-other-result-item" data-value="' + escapeHtml(name) + '">' + escapeHtml(name) + '</button>'; }).join('')
          : '<p class="text-sm text-on-surface-variant py-3 text-center">No options available.</p>');
        $modal.removeClass('hidden');
        return;
      }
      questionAnswers[qId] = value;
      setTimeout(nextQuestion, 180);
    });

    $(document).on('click', '.js-style-other-close', function () {
      $('#styleOtherModal').addClass('hidden');
      styleOtherModalContext = null;
    });
    $(document).on('click', '.js-style-other-result-item', function () {
      var name = String($(this).data('value') || '');
      if (!styleOtherModalContext || !name) return;
      questionAnswers[styleOtherModalContext.qId] = name;
      styleOtherModalContext.$questionDiv.find('.js-question-error').addClass('hidden');
      $('#styleOtherModal').addClass('hidden');
      styleOtherModalContext = null;
      setTimeout(nextQuestion, 180);
    });

    $(document).on('click', '.js-prev-question', function () { showQuestion(currentQuestionIndex - 1); });
    $(document).on('click', '.js-next-question', function () { nextQuestion(); });
    $(document).on('click', '.js-submit-details', function () { submitDetails(); });
    $(document).on('change', '.js-select2-question, .js-question-toggle', function () {
      var $q = $(this).closest('.question-div');
      var qId = $q.data('question-id');
      if (!qId) return;
      questionAnswers[qId] = $(this).hasClass('js-question-toggle') ? $(this).is(':checked') : String($(this).val() || '').trim();
      $q.find('.js-question-error').addClass('hidden');
    });
    $(document).on('input', '.js-question-input', function () {
      var $q = $(this).closest('.question-div');
      var qId = $q.data('question-id');
      if (qId) questionAnswers[qId] = String($(this).val() || '').trim();
      $q.find('.js-question-error').addClass('hidden');
    });

    renderQuestions();
  })();
  </script>
  @endunless
</body>
</html>
