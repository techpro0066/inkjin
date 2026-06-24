<style>
  #waitlistNotifyModal { opacity: 0; pointer-events: none; transition: opacity 0.2s ease; }
  #waitlistNotifyModal.is-open { opacity: 1; pointer-events: auto; }
  #waitlistNotifyModal .waitlist-notify-dialog { transform: scale(0.96) translateY(8px); transition: transform 0.2s ease; }
  #waitlistNotifyModal.is-open .waitlist-notify-dialog { transform: scale(1) translateY(0); }
</style>

<div id="waitlistNotifyModal" class="fixed inset-0 z-[120] hidden flex items-center justify-center p-4" aria-hidden="true" role="dialog" aria-labelledby="waitlistNotifyModalTitle" aria-modal="true">
  <button type="button" id="waitlistNotifyModalBackdrop" class="absolute inset-0 bg-black/45 border-0 cursor-default" aria-label="Close dialog"></button>
  <div class="waitlist-notify-dialog relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
    <div class="p-6 pb-5">
      <div id="waitlistNotifyModalIntro" class="hidden mb-4 rounded-xl bg-green-50 border border-green-200 px-4 py-3">
        <p class="text-sm font-semibold text-green-900">Your books are open</p>
        <p class="text-xs text-green-800 mt-1">Booking status saved. Clients can book with you again.</p>
      </div>
      <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center mb-4">
        <span class="material-symbols-outlined text-primary text-2xl">mail</span>
      </div>
      <h3 id="waitlistNotifyModalTitle" class="text-xl font-bold text-on-surface mb-2">Send waitlist email?</h3>
      <p class="text-sm text-on-surface-variant leading-relaxed">
        We'll email <span id="waitlistNotifyModalCount" class="font-semibold text-on-surface">0</span>
        <span id="waitlistNotifyModalCountLabel">pending subscribers</span> that your books are open and they can book on your artist page.
      </p>
      <div class="mt-4 rounded-xl bg-surface-container-low border border-outline-variant/15 px-4 py-3 text-xs text-on-surface-variant leading-relaxed">
        Subscribers who have already been notified will not receive another email.
      </div>
    </div>
    <div class="flex flex-col-reverse sm:flex-row gap-3 px-6 py-4 bg-surface-container-low/50 border-t border-outline-variant/15">
      <button type="button" id="waitlistNotifyModalCancel" class="flex-1 px-4 py-2.5 rounded-xl border border-outline-variant/30 bg-white text-on-surface text-sm font-semibold hover:bg-surface-container-low transition-colors">
        Cancel
      </button>
      <button type="button" id="waitlistNotifyModalConfirm" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary-container transition-colors">
        <span class="material-symbols-outlined text-lg">send</span>
        Send email
      </button>
    </div>
  </div>
</div>
