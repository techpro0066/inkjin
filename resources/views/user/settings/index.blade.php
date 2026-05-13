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

    @if (session('status') === 'password-updated')
      <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 text-sm font-medium">
        Password updated successfully.
      </div>
    @endif

    @if ($errors->updatePassword->any())
      <div class="mb-6 rounded-xl border border-error/30 bg-error/10 text-error px-4 py-3 text-sm">
        <ul class="list-disc pl-5 space-y-1">
          @foreach ($errors->updatePassword->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
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

    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 p-6 md:p-8">
      <div class="flex items-center gap-3 mb-5">
        <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center">
          <span class="material-symbols-outlined text-primary text-lg">lock</span>
        </div>
        <div>
          <h3 class="font-bold text-on-surface">Password</h3>
          <p class="text-xs text-on-surface-variant">Use a strong password you do not reuse elsewhere.</p>
        </div>
      </div>
      <form method="post" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        @method('put')
        <div>
          <label for="current_password" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Current password</label>
          <input type="password" id="current_password" name="current_password" autocomplete="current-password" required
            class="w-full rounded-xl border border-outline-variant/40 px-3 py-2.5 text-sm text-on-surface focus:ring-2 focus:ring-primary/30 focus:border-primary">
        </div>
        <div>
          <label for="password" class="block text-xs font-semibold text-on-surface-variant mb-1.5">New password</label>
          <input type="password" id="password" name="password" autocomplete="new-password" required
            class="w-full rounded-xl border border-outline-variant/40 px-3 py-2.5 text-sm text-on-surface focus:ring-2 focus:ring-primary/30 focus:border-primary">
        </div>
        <div>
          <label for="password_confirmation" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Confirm new password</label>
          <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required
            class="w-full rounded-xl border border-outline-variant/40 px-3 py-2.5 text-sm text-on-surface focus:ring-2 focus:ring-primary/30 focus:border-primary">
        </div>
        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary text-on-primary px-5 py-2.5 text-sm font-semibold hover:opacity-95 transition-opacity">
          <span class="material-symbols-outlined text-lg">save</span>
          Update password
        </button>
      </form>
    </div>
  </div>
</main>
@endsection

@section('scripts')
<script>
(function () {
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
