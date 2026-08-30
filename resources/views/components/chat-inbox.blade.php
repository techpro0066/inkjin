@props(['role' => 'user', 'streamConfigured' => false, 'hasOpenBooking' => false, 'hasConversations' => false])

@php
  $showChatInbox = $streamConfigured && (
    $hasConversations
    || $hasOpenBooking
    || request()->filled('artist')
    || request()->filled('client')
  );
@endphp

@if ($showChatInbox)
<style>
  .conversation-item { transition: background 0.15s ease; cursor: pointer; }
  .conversation-item:hover { background: #f8f1fb; }
  .conversation-item.active { background: #f2ecf5; border-left: 3px solid #310f7a; }
  .msg-bubble-other { background: #f8f1fb; border-radius: 18px 18px 18px 4px; }
  .msg-bubble-self { background: #310f7a; color: white; border-radius: 18px 18px 4px 18px; }
  #chatMessages { scroll-behavior: smooth; }
  .presence-dot {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 11px;
    height: 11px;
    border-radius: 9999px;
    border: 2px solid #fff;
  }
  .presence-dot--online { background: #22c55e; }
  .presence-dot--offline { background: #cac4d3; }
  .chat-unread-dot {
    position: absolute;
    top: -6px;
    right: -6px;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    background: #310f7a;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    line-height: 1;
    border: 2px solid #fff;
  }
  .typing-dots { display: inline-flex; align-items: center; gap: 4px; height: 16px; }
  .typing-dots span {
    width: 6px; height: 6px; border-radius: 9999px; background: #7a7583;
    animation: typingBounce 1.2s infinite ease-in-out;
  }
  .typing-dots span:nth-child(2) { animation-delay: 0.15s; }
  .typing-dots span:nth-child(3) { animation-delay: 0.3s; }
  @keyframes typingBounce {
    0%, 60%, 100% { transform: translateY(0); opacity: 0.45; }
    30% { transform: translateY(-4px); opacity: 1; }
  }
  #chatBookingSelectMenu {
    box-shadow: 0 12px 40px rgba(28, 27, 33, 0.12);
    max-height: 280px;
    overflow-y: auto;
  }
  .chat-booking-option { transition: background 0.12s ease; }
  .chat-booking-option:hover { background: #f8f1fb; }
  .chat-booking-option .chat-booking-ref { color: #1c1b21; }
  .chat-booking-option .chat-booking-meta { color: #494552; }
  .chat-booking-option.active { background: #f2ecf5; border-left: 3px solid #310f7a; }
  .chat-booking-option.has-unread {
    background: #310f7a;
    border-left: 3px solid #664db1;
    color: #fff;
  }
  .chat-booking-option.has-unread:hover { background: #482d91; }
  .chat-booking-option.active.has-unread {
    background: #310f7a;
    box-shadow: inset 0 0 0 2px rgba(255, 255, 255, 0.35);
  }
  .chat-booking-option.has-unread .chat-booking-ref,
  .chat-booking-option.has-unread .chat-booking-meta,
  .chat-booking-option.has-unread .chat-booking-preview {
    color: #fff;
  }
  .chat-booking-option.has-unread .chat-booking-meta,
  .chat-booking-option.has-unread .chat-booking-preview {
    opacity: 0.92;
  }
  #chatBookingSelectBtn.has-other-unread {
    border: 1px solid rgba(49, 15, 122, 0.22);
    background: #f8f1fb;
  }
  #chatBookingSelectUnreadHint { line-height: 1.3; }
  .chat-booking-select-dot {
    position: absolute;
    top: -4px;
    right: -4px;
    width: 10px;
    height: 10px;
    border-radius: 9999px;
    background: #310f7a;
    border: 2px solid #fff;
  }
  @media (max-width: 1023px) {
    #chatView.mobile-open { display: flex !important; position: fixed; inset: 0; top: 70px; z-index: 40; background: #fdf7ff; flex-direction: column; }
    #chatView.mobile-open ~ #convList,
    .chat-layout.mobile-thread #convList { display: none !important; }
  }
</style>
@endif

<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-6xl">

    <div class="mb-8">
      <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Inbox</h2>
      <p class="text-on-surface-variant mt-1">
        @if ($showChatInbox)
          <span id="chatInboxSubtitle">Messages with your {{ $role === 'artist' ? 'clients' : 'artists' }}</span>
        @else
          Messages with your {{ $role === 'artist' ? 'clients' : 'artists' }}
        @endif
      </p>
    </div>

    @if (! $streamConfigured)
      <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
        Chat is not configured yet. Add <code class="font-mono text-xs">STREAM_API_KEY</code> and <code class="font-mono text-xs">STREAM_API_SECRET</code> to your <code class="font-mono text-xs">.env</code> file.
      </div>
    @elseif (! $showChatInbox)
      <div class="rounded-2xl border border-outline-variant/20 bg-white shadow-sm px-6 py-20 text-center">
        <div class="mx-auto mb-5 flex h-24 w-24 items-center justify-center rounded-2xl border-2 border-dashed border-outline-variant/40 bg-surface-container-low">
          <span class="material-symbols-outlined text-outline text-5xl">forum</span>
        </div>
        <p class="text-lg font-semibold text-on-surface">No conversation is there</p>
        <p class="mx-auto mt-2 max-w-md text-sm text-on-surface-variant">
          You can message {{ $role === 'artist' ? 'a client' : 'your artist' }} when you have an open booking.
        </p>
      </div>
    @else
      <div id="chatInboxApp"
        class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 overflow-hidden"
        style="min-height: 600px;"
        data-api-base="{{ url('/api/chat') }}"
        data-role="{{ $role }}"
        data-user-id="{{ Auth::id() }}"
        data-open-artist="{{ request('artist', '') }}"
        data-open-client="{{ request('client', '') }}"
        data-open-booking="{{ request('booking', '') }}"
        data-csrf="{{ csrf_token() }}"
        data-locked-message="{{ $role === 'artist'
          ? 'This chat is read-only. You cannot send new messages for this booking.'
          : 'This chat is read-only. You cannot send new messages for this booking.' }}">

        <div class="flex h-[600px] chat-layout" id="chatLayout">
          <div class="w-full lg:w-[340px] flex-shrink-0 border-r border-outline-variant/15 flex flex-col" id="convList">
            <div class="p-4 border-b border-outline-variant/10">
              <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">search</span>
                <input type="text" id="chatSearch" placeholder="Search conversations..." class="w-full text-sm border border-outline-variant/30 rounded-xl pl-10 pr-4 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
              </div>
            </div>
            <div class="flex-1 overflow-y-auto" id="conversationList">
              <div class="p-8 text-center text-sm text-on-surface-variant" id="conversationEmpty">Loading conversations…</div>
            </div>
          </div>

          <div class="hidden lg:flex flex-1 flex-col" id="chatView">
            <div class="px-6 py-4 border-b border-outline-variant/10 flex items-center justify-between gap-3">
              <div class="flex items-center gap-3 min-w-0">
                <button type="button" id="chatBackBtn" class="lg:hidden material-symbols-outlined text-on-surface-variant">arrow_back</button>
                <div id="chatHeaderAvatar" class="w-9 h-9 rounded-full bg-gradient-to-br from-primary to-primary-container flex items-center justify-center flex-shrink-0">
                  <span class="text-white text-xs font-bold">?</span>
                </div>
                <div class="min-w-0">
                  <div class="flex items-center gap-1.5 min-w-0">
                    <p class="font-bold text-on-surface text-sm truncate" id="chatHeaderName">Select a conversation</p>
                    <span id="chatHeaderLock" class="hidden material-symbols-outlined text-[18px] text-outline flex-shrink-0" title="Read-only conversation">lock</span>
                  </div>
                  <p class="text-xs text-on-surface-variant truncate" id="chatHeaderMeta"></p>
                </div>
              </div>
              <div class="flex flex-col items-end gap-1 flex-shrink-0 min-w-0 max-w-[220px] sm:max-w-[280px]" id="chatBookingWrap">
                <div class="hidden relative w-full" id="chatBookingSelectWrap">
                  <button type="button" id="chatBookingSelectBtn" class="relative w-full flex items-center justify-between gap-2 bg-surface-container-low rounded-lg px-3 py-2 text-left hover:bg-surface-container transition-colors border border-transparent">
                    <span class="min-w-0">
                      <span class="block text-xs font-bold text-on-surface truncate" id="chatBookingSelectLabel">INK-FL-00000</span>
                      <span class="block text-[11px] text-on-surface-variant truncate" id="chatBookingSelectMeta"></span>
                      <span class="hidden text-[10px] text-primary font-semibold truncate mt-0.5" id="chatBookingSelectUnreadHint"></span>
                    </span>
                    <span class="relative flex-shrink-0 flex items-center">
                      <span class="material-symbols-outlined text-outline text-lg">expand_more</span>
                      <span id="chatBookingSelectDot" class="chat-booking-select-dot hidden" aria-hidden="true"></span>
                    </span>
                  </button>
                  <div id="chatBookingSelectMenu" class="hidden absolute right-0 top-full mt-1 z-50 w-[min(100vw-2rem,320px)] rounded-xl border border-outline-variant/20 bg-white py-1"></div>
                </div>
                <div class="hidden items-center gap-2 bg-surface-container-low rounded-lg px-3 py-2 w-full" id="chatBookingStatic">
                  <span class="material-symbols-outlined text-primary text-sm flex-shrink-0">calendar_today</span>
                  <div class="min-w-0">
                    <p class="text-xs font-bold text-on-surface truncate" id="chatBookingTitle"></p>
                    <p class="text-[11px] text-on-surface-variant truncate" id="chatBookingDate"></p>
                  </div>
                </div>
              </div>
            </div>

            <div class="flex-1 overflow-y-auto p-6 space-y-4" id="chatMessages">
              <div class="flex items-center justify-center h-full text-sm text-on-surface-variant">Choose a conversation to start messaging</div>
            </div>
            <div id="chatTypingIndicator" class="hidden px-6 pb-2">
              <div class="flex items-end gap-2 max-w-[75%]">
                <div class="w-7 h-7 rounded-full bg-gradient-to-br from-primary to-primary-container flex items-center justify-center flex-shrink-0" id="chatTypingAvatar">
                  <span class="text-white text-[9px] font-bold">?</span>
                </div>
                <div class="msg-bubble-other px-4 py-3">
                  <span class="typing-dots" aria-label="Typing"><span></span><span></span><span></span></span>
                </div>
              </div>
            </div>

            <div class="px-6 py-4 border-t border-outline-variant/10" id="chatInputWrap">
              <form id="chatSendForm" class="flex items-center gap-3">
                <div class="relative flex-1">
                  <input type="text" id="chatMessageInput" placeholder="Type a message..." class="w-full text-sm border border-outline-variant/30 rounded-full pl-5 pr-5 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30" autocomplete="off">
                  <div id="chatLockedField" class="hidden w-full text-sm border border-outline-variant/30 rounded-full pl-4 pr-5 py-3 bg-surface-container-low text-on-surface-variant flex items-center gap-2.5 min-h-[46px]">
                    <span class="material-symbols-outlined text-outline text-lg flex-shrink-0">lock</span>
                    <span id="chatLockedMessage" class="leading-snug"></span>
                  </div>
                </div>
                <button type="submit" id="chatSendBtn" class="w-10 h-10 rounded-full bg-primary flex items-center justify-center hover:bg-primary-container transition-colors flex-shrink-0 disabled:opacity-50 disabled:bg-surface-container-highest">
                  <span id="chatSendIcon" class="material-symbols-outlined text-white text-xl">send</span>
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>

      <script src="https://cdn.jsdelivr.net/npm/stream-chat@8.40.0/dist/browser.full-bundle.min.js"></script>
      <script src="{{ asset('js/stream-chat-inbox.js') }}?v=18"></script>
    @endif
  </div>
</main>
