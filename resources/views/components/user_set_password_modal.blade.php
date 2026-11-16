{{-- Shown for role=user until must_set_password is cleared; blocks the rest of the UI. --}}
@if(auth()->check() && auth()->user()->role === 'user' && auth()->user()->must_set_password)
<div
  id="clientInitialPasswordModal"
  class="fixed inset-0 z-[300] flex items-center justify-center p-4 sm:p-6 bg-black/55 backdrop-blur-[2px]"
  role="dialog"
  aria-modal="true"
  aria-labelledby="clientInitialPasswordTitle"
>
  <div class="w-full max-w-md rounded-2xl bg-white border border-outline-variant/20 shadow-2xl shadow-primary/10 overflow-hidden">
    <div class="p-6 sm:p-8">
      <div class="w-14 h-14 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center mb-5">
        <span class="material-symbols-outlined text-primary text-3xl" aria-hidden="true">lock</span>
      </div>
      <h2 id="clientInitialPasswordTitle" class="text-xl font-bold text-on-surface tracking-tight">Set your password</h2>
      <p class="text-sm text-on-surface-variant mt-3 leading-relaxed">
        Your booking is confirmed. Create a password for your Inkjin account so you can sign in anytime and manage your appointments.
      </p>

      @if ($errors->any())
        <div class="mt-4 rounded-xl border border-error/30 bg-error-container/40 px-4 py-3 text-sm text-error" role="alert">
          <ul class="list-disc pl-4 space-y-1">
            @foreach ($errors->all() as $err)
              <li>{{ $err }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ route('user.password.booking-initial.store') }}" class="mt-6 space-y-4">
        @csrf
        <div>
          <label for="client_initial_password" class="block text-xs font-bold text-on-surface-variant mb-1.5">New password</label>
          <div class="relative">
            <input
              type="password"
              name="password"
              id="client_initial_password"
              required
              autofocus
              autocomplete="new-password"
              class="w-full text-sm border border-outline-variant/30 rounded-xl pl-4 pr-11 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30"
            />
            <button
              type="button"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-outline-variant hover:text-on-surface-variant client-pw-eye"
              data-target="#client_initial_password"
              aria-label="Show password"
            >
              <span class="material-symbols-outlined text-[20px]">visibility</span>
            </button>
          </div>
        </div>
        <div>
          <label for="client_initial_password_confirmation" class="block text-xs font-bold text-on-surface-variant mb-1.5">Confirm password</label>
          <div class="relative">
            <input
              type="password"
              name="password_confirmation"
              id="client_initial_password_confirmation"
              required
              autocomplete="new-password"
              class="w-full text-sm border border-outline-variant/30 rounded-xl pl-4 pr-11 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30"
            />
            <button
              type="button"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-outline-variant hover:text-on-surface-variant client-pw-eye"
              data-target="#client_initial_password_confirmation"
              aria-label="Show confirm password"
            >
              <span class="material-symbols-outlined text-[20px]">visibility</span>
            </button>
          </div>
        </div>
        <button
          type="submit"
          class="w-full inline-flex items-center justify-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3.5 px-6 rounded-xl shadow-lg shadow-primary/20 hover:opacity-95 transition-opacity text-sm"
        >
          <span class="material-symbols-outlined text-lg" aria-hidden="true">check_circle</span>
          Save password & continue
        </button>
      </form>
    </div>
  </div>
</div>
<script>
(function () {
  document.querySelectorAll('#clientInitialPasswordModal .client-pw-eye').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var target = document.querySelector(btn.getAttribute('data-target'));
      if (!target) return;
      var icon = btn.querySelector('.material-symbols-outlined');
      var show = target.getAttribute('type') === 'password';
      target.setAttribute('type', show ? 'text' : 'password');
      if (icon) icon.textContent = show ? 'visibility_off' : 'visibility';
      btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
  });
})();
</script>
@endif
