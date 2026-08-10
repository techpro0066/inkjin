@extends('layouts.user_dashboard_layout')

@section('title', 'Settings')

@section('content')
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-2xl">
    <div class="mb-8">
      <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Settings</h2>
      <p class="text-on-surface-variant mt-1 text-sm">Update your profile photo and account password.</p>
    </div>

    @if (session('status') === 'avatar-updated')
      <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 text-sm font-medium">
        Profile photo updated.
      </div>
    @endif

    @if ($errors->has('avatar'))
      <div class="mb-6 rounded-xl border border-error/30 bg-error/10 text-error px-4 py-3 text-sm">
        {{ $errors->first('avatar') }}
      </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 p-6 md:p-8 mb-6">
      <div class="flex items-center gap-3 mb-5">
        <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center">
          <span class="material-symbols-outlined text-primary text-lg">account_circle</span>
        </div>
        <div>
          <h3 class="font-bold text-on-surface">Profile photo</h3>
          <p class="text-xs text-on-surface-variant">Shown in your client dashboard and emails where we use your avatar.</p>
        </div>
      </div>
      <div class="flex flex-col sm:flex-row sm:items-center gap-6">
        <div class="shrink-0">
          <img src="{{ $avatarUrl }}" alt="" width="96" height="96" class="w-24 h-24 rounded-full object-cover ring-2 ring-primary/15 border border-outline-variant/20" id="userSettingsAvatarPreview">
        </div>
        <form method="post" action="{{ route('user.settings.avatar') }}" enctype="multipart/form-data" class="flex-1 min-w-0 space-y-4">
          @csrf
          <div>
            <label for="avatar" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Upload image</label>
            <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp" required
              class="block w-full text-sm text-on-surface file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/15 cursor-pointer border border-outline-variant/30 rounded-xl bg-surface-container-low/50 px-2 py-2">
            <p class="text-xs text-on-surface-variant mt-2">JPG, PNG or WebP. Up to 4&nbsp;MB.</p>
          </div>
          <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary text-on-primary px-5 py-2.5 text-sm font-semibold hover:opacity-95 transition-opacity">
            <span class="material-symbols-outlined text-lg">upload</span>
            Save photo
          </button>
        </form>
      </div>
    </div>

    <div id="password-success-alert" class="hidden mb-6 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 text-sm font-medium"></div>
    <div id="password-error-alert" class="hidden mb-6 rounded-xl border border-error/30 bg-error/10 text-error px-4 py-3 text-sm"></div>

    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 p-6 md:p-8">
      <div class="flex items-center gap-3 mb-5">
        <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center">
          <span class="material-symbols-outlined text-primary text-lg">lock</span>
        </div>
        <div>
          <h3 class="font-bold text-on-surface">Password</h3>
          <p class="text-xs text-on-surface-variant">Use a strong password you do not reuse elsewhere. Changing it signs you out of other devices.</p>
        </div>
      </div>
      <form id="updatePasswordForm" method="post" action="{{ route('password.update') }}" class="space-y-4" novalidate>
        @csrf
        @method('put')
        <div>
          <label for="current_password" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Current password</label>
          <div class="relative">
            <input type="password" id="current_password" name="current_password" autocomplete="current-password" required
              class="password-field w-full rounded-xl border border-outline-variant/40 pl-3 pr-11 py-2.5 text-sm text-on-surface focus:ring-2 focus:ring-primary/30 focus:border-primary">
            <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-outline-variant hover:text-on-surface-variant eye-toggle" data-target="#current_password" aria-label="Toggle current password visibility">
              <span class="material-symbols-outlined text-[20px]">visibility</span>
            </button>
          </div>
          <p id="current_password_error" class="hidden text-error text-xs mt-1.5"></p>
        </div>
        <div>
          <label for="password" class="block text-xs font-semibold text-on-surface-variant mb-1.5">New password</label>
          <div class="relative">
            <input type="password" id="password" name="password" autocomplete="new-password" required
              class="password-field w-full rounded-xl border border-outline-variant/40 pl-3 pr-11 py-2.5 text-sm text-on-surface focus:ring-2 focus:ring-primary/30 focus:border-primary">
            <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-outline-variant hover:text-on-surface-variant eye-toggle" data-target="#password" aria-label="Toggle new password visibility">
              <span class="material-symbols-outlined text-[20px]">visibility</span>
            </button>
          </div>
          <p id="password_error" class="hidden text-error text-xs mt-1.5"></p>
        </div>
        <div>
          <label for="password_confirmation" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Confirm new password</label>
          <div class="relative">
            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required
              class="password-field w-full rounded-xl border border-outline-variant/40 pl-3 pr-11 py-2.5 text-sm text-on-surface focus:ring-2 focus:ring-primary/30 focus:border-primary">
            <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-outline-variant hover:text-on-surface-variant eye-toggle" data-target="#password_confirmation" aria-label="Toggle confirm password visibility">
              <span class="material-symbols-outlined text-[20px]">visibility</span>
            </button>
          </div>
          <p id="password_confirmation_error" class="hidden text-error text-xs mt-1.5"></p>
        </div>
        <button type="submit" id="updatePasswordBtn" class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary text-on-primary px-5 py-2.5 text-sm font-semibold hover:opacity-95 transition-opacity disabled:opacity-60">
          <span class="material-symbols-outlined text-lg">save</span>
          <span class="btn-label">Update password</span>
        </button>
      </form>
    </div>
  </div>
</main>
@endsection

@section('scripts')
<script>
(function () {
  document.querySelectorAll('.eye-toggle').forEach(function (toggleBtn) {
    toggleBtn.addEventListener('click', function () {
      var target = document.querySelector(toggleBtn.getAttribute('data-target'));
      if (!target) return;
      var icon = toggleBtn.querySelector('.material-symbols-outlined');
      var show = target.getAttribute('type') === 'password';
      target.setAttribute('type', show ? 'text' : 'password');
      if (icon) icon.textContent = show ? 'visibility_off' : 'visibility';
    });
  });

  var form = document.getElementById('updatePasswordForm');
  var btn = document.getElementById('updatePasswordBtn');
  var successAlert = document.getElementById('password-success-alert');
  var errorAlert = document.getElementById('password-error-alert');
  var fields = ['current_password', 'password', 'password_confirmation'];
  var defaultBtnHtml = btn ? btn.innerHTML : '';

  function clearFieldError(name) {
    var input = document.getElementById(name);
    var error = document.getElementById(name + '_error');
    if (input) input.classList.remove('border-error');
    if (error) {
      error.textContent = '';
      error.classList.add('hidden');
    }
  }

  function setFieldError(name, message) {
    var input = document.getElementById(name);
    var error = document.getElementById(name + '_error');
    if (input) input.classList.add('border-error');
    if (error) {
      error.textContent = message;
      error.classList.remove('hidden');
    }
  }

  function clearAllErrors() {
    fields.forEach(clearFieldError);
    if (errorAlert) {
      errorAlert.textContent = '';
      errorAlert.classList.add('hidden');
    }
  }

  fields.forEach(function (name) {
    var el = document.getElementById(name);
    if (el) el.addEventListener('input', function () { clearFieldError(name); });
  });

  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      clearAllErrors();
      if (successAlert) successAlert.classList.add('hidden');

      btn.disabled = true;
      btn.innerHTML = '<span class="material-symbols-outlined text-lg">hourglass_top</span> <span class="btn-label">Updating...</span>';

      var body = new FormData(form);
      body.set('_method', 'PUT');

      fetch(form.action, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || form.querySelector('input[name="_token"]').value,
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: body
      })
        .then(function (response) {
          return response.json().then(function (data) {
            return { ok: response.ok, status: response.status, data: data };
          }).catch(function () {
            return { ok: response.ok, status: response.status, data: {} };
          });
        })
        .then(function (result) {
          if (result.ok && result.data && result.data.success) {
            form.reset();
            fields.forEach(function (name) {
              var input = document.getElementById(name);
              if (input) input.setAttribute('type', 'password');
            });
            document.querySelectorAll('.eye-toggle .material-symbols-outlined').forEach(function (icon) {
              icon.textContent = 'visibility';
            });
            if (successAlert) {
              successAlert.textContent = result.data.message || 'Password updated successfully. Other sessions have been signed out.';
              successAlert.classList.remove('hidden');
              successAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
          }

          if (result.status === 422 && result.data && result.data.errors) {
            var errors = result.data.errors;
            fields.forEach(function (name) {
              var key = Object.keys(errors).find(function (k) {
                return k === name || k.endsWith('.' + name);
              });
              if (key && errors[key] && errors[key][0]) {
                setFieldError(name, errors[key][0]);
              }
            });
            return;
          }

          if (errorAlert) {
            errorAlert.textContent = (result.data && result.data.message) || 'Something went wrong. Please try again.';
            errorAlert.classList.remove('hidden');
          }
        })
        .catch(function () {
          if (errorAlert) {
            errorAlert.textContent = 'Network error. Please try again.';
            errorAlert.classList.remove('hidden');
          }
        })
        .finally(function () {
          btn.disabled = false;
          btn.innerHTML = defaultBtnHtml;
        });
    });
  }

  var input = document.getElementById('avatar');
  var preview = document.getElementById('userSettingsAvatarPreview');
  if (!input || !preview) return;
  input.addEventListener('change', function () {
    var f = input.files && input.files[0];
    if (!f || !f.type || !f.type.startsWith('image/')) return;
    var url = URL.createObjectURL(f);
    preview.onload = function () { URL.revokeObjectURL(url); };
    preview.src = url;
  });
})();
</script>
@endsection
