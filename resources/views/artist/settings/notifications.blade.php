@extends('layouts.artist_dashboard_layout')

@section('styles')
<style>
    .notif-toggle { position: relative; width: 44px; height: 24px; background: #cac4d3; border-radius: 12px; cursor: pointer; transition: background 0.2s; flex-shrink: 0; }
    .notif-toggle.active { background: #310f7a; }
    .notif-toggle::after { content: ''; position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; background: white; border-radius: 50%; transition: transform 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
    .notif-toggle.active::after { transform: translateX(20px); }

    .notif-row { display: flex; align-items: center; justify-content: space-between; padding: 14px 0; border-bottom: 1px solid rgba(202, 196, 211, 0.3); }
    .notif-row:last-child { border-bottom: none; }

    @media (max-width: 1023px) {
      .main-content { overflow-x: hidden; padding: 16px; padding-top: 70px; }
      body { overflow-x: hidden; }
    }
</style>
@endsection

@section('content')
 <!-- Main Content -->
 <main class="main-content flex-1 min-h-screen flex flex-col">
    <div class="flex-1 p-6 md:p-10 lg:p-12 max-w-4xl">

      <!-- Settings Tabs -->
      <div class="flex items-center gap-1 mb-6 border-b border-outline-variant/20 pb-0 overflow-x-auto">
        <a href="settings-profile.html" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Profile</a>
        <a href="settings-styles.html" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Styles &amp; Social</a>
        <a href="settings-studio.html" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Studio</a>
        <a href="settings-preferences.html" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Preferences</a>
        <a href="settings-calendar.html" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Calendar</a>
        <a href="settings-payments.html" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Payments</a>
        {{-- <a href="settings-notifications.html" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-primary text-primary hover:text-on-surface hover:border-outline-variant transition-all">Notifications</a> --}}
      </div>


      <!-- Page Header -->
      <div class="mb-8">
        <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Notification Settings</h2>
        <p class="text-on-surface-variant mt-1">Choose how and when you want to be notified.</p>
      </div>

      <div class="space-y-8">

        <!-- Email Notifications -->
        <div class="bg-surface-container-low rounded-2xl p-6">
          <div class="flex items-center gap-3 mb-5">
            <span class="material-symbols-outlined text-primary">mail</span>
            <h3 class="text-base font-bold text-on-surface">Email</h3>
          </div>
          <div>
            <div class="notif-row">
              <span class="text-sm text-on-surface">New booking received</span>
              <div class="notif-toggle active" onclick="this.classList.toggle('active')" role="switch" aria-checked="true"></div>
            </div>
            <div class="notif-row">
              <span class="text-sm text-on-surface">New custom request received</span>
              <div class="notif-toggle active" onclick="this.classList.toggle('active')" role="switch" aria-checked="true"></div>
            </div>
            <div class="notif-row">
              <span class="text-sm text-on-surface">New message from client</span>
              <div class="notif-toggle active" onclick="this.classList.toggle('active')" role="switch" aria-checked="true"></div>
            </div>
            <div class="notif-row">
              <span class="text-sm text-on-surface">Booking cancelled or rescheduled</span>
              <div class="notif-toggle active" onclick="this.classList.toggle('active')" role="switch" aria-checked="true"></div>
            </div>
            <div class="notif-row">
              <span class="text-sm text-on-surface">Payment received</span>
              <div class="notif-toggle active" onclick="this.classList.toggle('active')" role="switch" aria-checked="true"></div>
            </div>
            <div class="notif-row">
              <span class="text-sm text-on-surface">Client left a review</span>
              <div class="notif-toggle active" onclick="this.classList.toggle('active')" role="switch" aria-checked="true"></div>
            </div>
          </div>
        </div>

        <!-- Push Notifications -->
        <div class="bg-surface-container-low rounded-2xl p-6">
          <div class="flex items-center gap-3 mb-5">
            <span class="material-symbols-outlined text-primary">notifications</span>
            <h3 class="text-base font-bold text-on-surface">Push</h3>
          </div>
          <div>
            <div class="notif-row">
              <span class="text-sm text-on-surface">New booking received</span>
              <div class="notif-toggle active" onclick="this.classList.toggle('active')" role="switch" aria-checked="true"></div>
            </div>
            <div class="notif-row">
              <span class="text-sm text-on-surface">New message from client</span>
              <div class="notif-toggle active" onclick="this.classList.toggle('active')" role="switch" aria-checked="true"></div>
            </div>
            <div class="notif-row">
              <span class="text-sm text-on-surface">Booking reminder (1 hour before)</span>
              <div class="notif-toggle active" onclick="this.classList.toggle('active')" role="switch" aria-checked="true"></div>
            </div>
          </div>
        </div>

        <!-- SMS Notifications -->
        <div class="bg-surface-container-low rounded-2xl p-6">
          <div class="flex items-center gap-3 mb-5">
            <span class="material-symbols-outlined text-primary">sms</span>
            <h3 class="text-base font-bold text-on-surface">SMS</h3>
          </div>
          <div>
            <div class="notif-row">
              <span class="text-sm text-on-surface">Booking reminder (1 hour before)</span>
              <div class="notif-toggle" onclick="this.classList.toggle('active')" role="switch" aria-checked="false"></div>
            </div>
            <div class="notif-row">
              <span class="text-sm text-on-surface">New booking received</span>
              <div class="notif-toggle" onclick="this.classList.toggle('active')" role="switch" aria-checked="false"></div>
            </div>
          </div>
          <p class="text-xs text-on-surface-variant mt-3">Standard messaging rates may apply.</p>
        </div>

        <!-- Marketing & Updates -->
        <div class="bg-surface-container-low rounded-2xl p-6">
          <div class="flex items-center gap-3 mb-5">
            <span class="material-symbols-outlined text-primary">campaign</span>
            <h3 class="text-base font-bold text-on-surface">Marketing & Updates</h3>
          </div>
          <div>
            <div class="notif-row">
              <span class="text-sm text-on-surface">Platform updates and tips</span>
              <div class="notif-toggle active" onclick="this.classList.toggle('active')" role="switch" aria-checked="true"></div>
            </div>
            <div class="notif-row">
              <span class="text-sm text-on-surface">New feature announcements</span>
              <div class="notif-toggle active" onclick="this.classList.toggle('active')" role="switch" aria-checked="true"></div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Footer: Save Changes -->
    <div class="sticky bottom-0 bg-surface border-t border-outline-variant/10 px-6 md:px-10 lg:px-12 py-5 flex items-center justify-end">
      <button type="button" onclick="showSaveToast()" class="inline-flex items-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]">
        <span class="material-symbols-outlined text-lg">save</span> Save Changes
      </button>
    </div>
  </main>
@endsection

@section('scripts')
<script>
    
</script>
@endsection