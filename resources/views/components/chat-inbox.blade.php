@props([
  'role' => 'user',
  'streamConfigured' => false,
  'hasOpenBooking' => false,
  'hasConversations' => false,
  'skin' => 'default',
])

@php
  $isBookpay = $skin === 'bookpay';
  $showChatInbox = $streamConfigured && (
    $hasConversations
    || $hasOpenBooking
    || request()->filled('artist')
    || request()->filled('client')
    || request()->filled('studio')
    || request()->filled('booking')
    || request()->filled('booking_request')
    || request()->filled('custom_request')
  );
  $bookingsIndexUrl = match ($role) {
    'artist' => route('artist.bookings.index'),
    'studio' => route('studio.bookings'),
    default => route('user.bookings.index'),
  };
  $inboxPartnerLabel = match ($role) {
    'artist' => 'clients and studios',
    'studio' => 'artists',
    default => 'artists',
  };
@endphp

@if ($showChatInbox)
<style>
  .bp-inbox .hidden{display:none!important}
  .bp-inbox .row{display:flex;align-items:center;gap:12px}
  .bp-inbox .in{
    background:#fff;border:1px solid #E8DFEA;border-radius:10px;padding:10px 13px;font-size:13.5px;
    display:flex;align-items:center;gap:8px;min-height:40px;width:100%;color:#1A1A1A;outline:none;box-sizing:border-box
  }
  .bp-inbox .in .ms{font-size:18px;color:#9A929E}
  .bp-inbox .ms{
    font-family:'Material Symbols Outlined';font-weight:normal;font-style:normal;font-size:20px;line-height:1;
    display:inline-block;-webkit-font-smoothing:antialiased;
    font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;vertical-align:middle
  }
  .bp-inbox .card{background:#fff;border:1px solid #E8DFEA;border-radius:16px}
  .bp-inbox .card.pad{padding:20px 22px}
  .bp-inbox .ic{width:38px;height:38px;border-radius:10px;background:#F0EDF2;display:flex;align-items:center;justify-content:center}
  .bp-inbox .conversation-item{padding:12px 14px;border-radius:12px;cursor:pointer;transition:background .15s ease}
  .bp-inbox .conversation-item:hover{background:#FAF0FB}
  .bp-inbox .conversation-item.active{background:#F3E8FF}
  .bp-inbox .msg-bubble-other{background:#F4F0F6;border-radius:14px 14px 14px 4px;color:#1A1A1A}
  .bp-inbox .msg-bubble-self{background:#3E007C;color:#fff;border-radius:14px 14px 4px 14px}
  #chatMessages{scroll-behavior:smooth}
  .bp-inbox .presence-dot{position:absolute;bottom:0;right:0;width:10px;height:10px;border-radius:9999px;border:2px solid #fff}
  .bp-inbox .presence-dot--online{background:#00A650}
  .bp-inbox .presence-dot--offline{background:#CAC4D3}
  .bp-inbox .chat-unread-dot{
    width:8px;height:8px;min-width:8px;padding:0;border-radius:50%;background:#7A1FC4;border:0;
    position:static;display:inline-block;flex-shrink:0
  }
  .bp-inbox .chat-unread-dot[data-count]:not([data-count=""]){font-size:0}
  .typing-dots{display:inline-flex;align-items:center;gap:4px;height:16px}
  .typing-dots span{width:6px;height:6px;border-radius:9999px;background:#9A929E;animation:typingBounce 1.2s infinite ease-in-out}
  .typing-dots span:nth-child(2){animation-delay:.15s}
  .typing-dots span:nth-child(3){animation-delay:.3s}
  @keyframes typingBounce{0%,60%,100%{transform:translateY(0);opacity:.45}30%{transform:translateY(-4px);opacity:1}}
  .bp-inbox .ibwrap{position:relative;flex-shrink:0}
  .bp-inbox .ibsel{
    display:flex;align-items:center;gap:10px;background:#fff;border:1px solid #E8DFEA;border-radius:10px;
    padding:6px 10px;cursor:pointer;font:inherit;text-align:left;max-width:280px
  }
  .bp-inbox .ibsel>span:first-child{display:flex;flex-direction:column;min-width:0}
  .bp-inbox .ibsel b{font-size:12.5px;font-weight:700;color:#1A1A1A}
  .bp-inbox .ibsel .ibsub{font-size:11.5px;color:#6F6874;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:220px}
  .bp-inbox .ibsel .ms{font-size:18px;color:#9A929E;flex-shrink:0}
  .bp-inbox .ibsel.has-other-unread{border-color:#D4B8F0;background:#FBF5FF}
  .bp-inbox .ibmenu{
    position:absolute;right:0;top:calc(100% + 6px);background:#fff;border:1px solid #E8DFEA;border-radius:12px;
    box-shadow:0 14px 40px rgba(40,10,60,.18);width:300px;max-height:320px;overflow-y:auto;padding:6px;z-index:30
  }
  .bp-inbox .ibo{
    display:flex;flex-direction:column;align-items:flex-start;width:100%;border:0;background:none;font:inherit;
    text-align:left;padding:9px 10px;border-radius:9px;cursor:pointer
  }
  .bp-inbox .ibo:hover,.bp-inbox .ibo.on{background:#F3E8FF}
  .bp-inbox .ibo.has-unread{background:#3E007C;color:#fff}
  .bp-inbox .ibo.has-unread:hover{background:#4A1490}
  .bp-inbox .ibo b{font-size:13px;font-weight:700}
  .bp-inbox .ibo span,.bp-inbox .ibo .chat-booking-meta,.bp-inbox .ibo .chat-booking-preview{font-size:11.5px;color:#6F6874}
  .bp-inbox .ibo.has-unread span,.bp-inbox .ibo.has-unread .chat-booking-meta,.bp-inbox .ibo.has-unread .chat-booking-preview{color:rgba(255,255,255,.9)}
  .bp-inbox .chat-booking-select-dot{
    position:absolute;top:-4px;right:-4px;width:10px;height:10px;border-radius:9999px;background:#7A1FC4;border:2px solid #fff
  }
  .bp-inbox .bp-inbox-card{
    display:grid;grid-template-columns:320px 1fr;margin-top:24px;height:620px;overflow:hidden;
    background:#fff;border:1px solid #E8DFEA;border-radius:16px
  }
  .bp-inbox .bp-conv-pane{border-right:1px solid #E8DFEA;padding:14px;display:flex;flex-direction:column;min-height:0}
  .bp-inbox .bp-chat-pane{display:none;flex-direction:column;min-width:0;min-height:0}
  @media (min-width:1024px){
    .bp-inbox .bp-chat-pane{display:flex}
  }
  .bp-inbox .bp-search.in{margin-bottom:10px}
  .bp-inbox .bp-search input{
    border:0;outline:0;font:inherit;flex:1;min-width:0;background:none;color:inherit;padding:0;margin:0;box-shadow:none
  }
  .bp-inbox .bp-composer .in{flex:1;min-width:0}
  .bp-inbox .bp-composer .in input{
    border:0;outline:0;font:inherit;flex:1;min-width:0;background:none;color:inherit;padding:0;margin:0;box-shadow:none;width:100%
  }
  .bp-inbox #chatLockedField.hidden{display:none!important}
  .bp-inbox #chatLockedField:not(.hidden){display:flex!important}
  .bp-inbox #chatMessageInput.hidden{display:none!important}
  .bp-inbox #chatBookingSelectWrap.hidden,
  .bp-inbox #chatBookingStatic.hidden,
  .bp-inbox #chatViewBookingBtn.hidden,
  .bp-inbox #chatBookingSelectMenu.hidden,
  .bp-inbox #chatTypingIndicator.hidden,
  .bp-inbox #chatHeaderLock.hidden,
  .bp-inbox #chatBookingSelectUnreadHint.hidden,
  .bp-inbox #chatBookingSelectDot.hidden{display:none!important}
  .bp-inbox #chatBookingSelectWrap:not(.hidden){display:block}
  .bp-inbox #chatBookingStatic:not(.hidden){display:flex!important}
  .bp-inbox #chatViewBookingBtn:not(.hidden){display:inline-flex!important}
  .bp-inbox #chatBookingSelectMenu:not(.hidden){display:block}
  .bp-inbox .bp-send.btn{padding:10px 12px}
  .bp-inbox .ini{
    width:34px;height:34px;border-radius:50%;background:#1A1A1A;color:#fff;font-size:12px;font-weight:700;
    display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden
  }
  .bp-inbox .ini img{width:100%;height:100%;object-fit:cover}
  .bp-inbox .faint{color:#9A929E}
  .bp-inbox .btn.sm.ghost{
    display:inline-flex;align-items:center;gap:6px;background:#fff;color:#1A1A1A;border:1px solid #E8DFEA;
    border-radius:8px;padding:6px 11px;font-weight:600;font-size:12.5px;white-space:nowrap;cursor:pointer;font:inherit;text-decoration:none
  }
  .bp-inbox .btn.sm.ghost .ms{font-size:16px}
  @media (max-width:1023px){
    .bp-inbox .bp-inbox-card{grid-template-columns:1fr;height:auto;min-height:70vh}
    .bp-inbox .bp-conv-pane{border-right:0;border-bottom:1px solid #E8DFEA;max-height:42vh}
    #chatView.mobile-open{display:flex!important;position:fixed;inset:0;top:0;z-index:40;background:#fff;flex-direction:column}
    #chatView.mobile-open ~ #convList,
    .chat-layout.mobile-thread #convList{display:none!important}
    #chatView.mobile-open #chatBackBtn{display:inline-flex!important}
    .bp-inbox .ibmenu{width:260px}
  }
</style>
@endif

@if ($isBookpay)
  <div class="bp-inbox">
    <div class="head">
      <div>
        <h1>Inbox</h1>
        <div class="sub">
          @if ($showChatInbox)
            <span id="chatInboxSubtitle">Messages with your {{ $inboxPartnerLabel }}</span>
          @else
            Messages with your {{ $inboxPartnerLabel }}
          @endif
        </div>
      </div>
    </div>
@else
  <main class="main-content flex-1 min-h-screen bp-inbox">
  <div class="p-6 md:p-10 lg:p-12 max-w-6xl">
    <div class="mb-8">
      <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Inbox</h2>
      <p class="text-on-surface-variant mt-1">
        @if ($showChatInbox)
          <span id="chatInboxSubtitle">Messages with your {{ $inboxPartnerLabel }}</span>
        @else
          Messages with your {{ $inboxPartnerLabel }}
        @endif
      </p>
    </div>
@endif

    @if (! $streamConfigured)
      <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900" style="margin-top:24px">
        Chat is not configured yet. Add <code class="font-mono text-xs">STREAM_API_KEY</code> and <code class="font-mono text-xs">STREAM_API_SECRET</code> to your <code class="font-mono text-xs">.env</code> file.
      </div>
    @elseif (! $showChatInbox)
      <div class="card pad" style="margin-top:24px;text-align:center;padding:48px 24px">
        <div class="ic" style="margin:0 auto 16px;width:56px;height:56px;border-radius:14px"><span class="ms">forum</span></div>
        <p style="font-weight:700;font-size:16px;margin-bottom:6px">No conversation is there</p>
        <p class="faint" style="font-size:13.5px;max-width:420px;margin:0 auto">
          @if ($role === 'studio')
            You can message an artist when you have an open booking at your studio.
          @elseif ($role === 'artist')
            You can message a client or studio when you have an open request or booking.
          @else
            You can message your artist when you have an open request or booking.
          @endif
        </p>
      </div>
    @else
      <div id="chatInboxApp"
        data-skin="bookpay"
        data-api-base="{{ url('/api/chat') }}"
        data-role="{{ $role }}"
        data-user-id="{{ Auth::id() }}"
        data-open-artist="{{ request('artist', '') }}"
        data-open-client="{{ request('client', '') }}"
        data-open-studio="{{ request('studio', '') }}"
        data-open-booking="{{ request('booking', '') }}"
        data-open-booking-request="{{ request('booking_request', '') }}"
        data-open-custom-request="{{ request('custom_request', '') }}"
        data-csrf="{{ csrf_token() }}"
        data-bookings-url="{{ $bookingsIndexUrl }}"
        data-locked-message="This chat is read-only. You cannot send new messages for this booking.">

        <div class="bp-inbox-card chat-layout" id="chatLayout">
        <div class="bp-conv-pane" id="convList">
          <div class="in bp-search">
            <span class="ms">search</span>
            <input type="search" id="chatSearch" placeholder="Search conversations" autocomplete="off" aria-label="Search conversations">
              </div>
          <div id="conversationList" style="flex:1;min-height:0;overflow-y:auto">
            <div style="padding:32px 16px;text-align:center;font-size:13.5px;color:#6F6874" id="conversationEmpty">Loading conversations…</div>
          </div>
                </div>

        <div class="bp-chat-pane" id="chatView">
          <div class="row" style="padding:14px 20px;border-bottom:1px solid #E8DFEA;gap:12px;flex-wrap:wrap">
            <button type="button" id="chatBackBtn" class="lg:hidden" style="background:none;border:0;cursor:pointer;padding:0;color:#6F6874;display:none" aria-label="Back to conversations">
              <span class="ms">arrow_back</span>
            </button>
            <div id="chatHeaderAvatar" class="ini">?</div>
            <div style="flex:1;min-width:0">
              <div class="row" style="gap:6px;justify-content:flex-start">
                <b id="chatHeaderName" style="font-size:14px">Select a conversation</b>
                <span id="chatHeaderLock" class="hidden ms" style="font-size:18px;color:#9A929E" title="Read-only conversation">lock</span>
              </div>
              <div class="faint" style="font-size:12px" id="chatHeaderMeta"></div>
            </div>

            <div class="row" style="gap:10px;flex-shrink:0;margin-left:auto" id="chatBookingWrap">
              <div class="ibwrap hidden" id="chatBookingSelectWrap">
                <button type="button" class="ibsel" id="chatBookingSelectBtn" aria-haspopup="listbox">
                  <span>
                    <b id="chatBookingSelectLabel">—</b>
                    <span class="ibsub" id="chatBookingSelectMeta"></span>
                    <span class="hidden" id="chatBookingSelectUnreadHint" style="font-size:10px;color:#3E007C;font-weight:600"></span>
                    </span>
                  <span style="position:relative;flex-shrink:0;display:flex;align-items:center">
                    <span class="ms">expand_more</span>
                      <span id="chatBookingSelectDot" class="chat-booking-select-dot hidden" aria-hidden="true"></span>
                    </span>
                  </button>
                <div id="chatBookingSelectMenu" class="ibmenu hidden" hidden></div>
                </div>
              <div class="hidden" id="chatBookingStatic" style="align-items:center;gap:8px;background:#FAF0FB;border-radius:10px;padding:6px 10px">
                <span class="ms" style="font-size:16px;color:#3E007C">calendar_today</span>
                <div style="min-width:0">
                  <div style="font-size:12.5px;font-weight:700" id="chatBookingTitle"></div>
                  <div class="faint" style="font-size:11.5px" id="chatBookingDate"></div>
                </div>
              </div>
              <a href="#" id="chatViewBookingBtn" class="btn sm ghost hidden">
                <span class="ms">visibility</span>View booking
              </a>
            </div>
            </div>

          <div style="flex:1;padding:20px;display:flex;flex-direction:column;gap:10px;justify-content:flex-end;overflow-y:auto;min-height:0" id="chatMessages">
            <div style="display:flex;align-items:center;justify-content:center;height:100%;color:#6F6874;font-size:13.5px;align-self:stretch">Choose a conversation to start messaging</div>
                </div>

          <div id="chatTypingIndicator" class="hidden" style="padding:0 20px 8px">
            <div style="display:flex;align-items:flex-end;gap:8px;max-width:75%">
              <div class="ini" style="width:28px;height:28px;font-size:9px" id="chatTypingAvatar">?</div>
              <div class="msg-bubble-other" style="padding:10px 14px">
                  <span class="typing-dots" aria-label="Typing"><span></span><span></span><span></span></span>
              </div>
            </div>
                  </div>

          <div class="row bp-composer" style="padding:14px 20px;border-top:1px solid #E8DFEA;gap:10px" id="chatInputWrap">
            <span class="ms faint" style="color:#9A929E">attach_file</span>
            <form id="chatSendForm" class="row" style="flex:1;gap:10px;min-width:0">
              <div class="in" style="flex:1;position:relative">
                <input type="text" id="chatMessageInput" placeholder="Type a message" autocomplete="off">
                <div id="chatLockedField" class="hidden" style="width:100%;align-items:center;gap:8px;color:#6F6874;font-size:13.5px">
                  <span class="ms" style="font-size:18px;color:#9A929E">lock</span>
                  <span id="chatLockedMessage"></span>
                </div>
              </div>
              <button type="submit" id="chatSendBtn" class="btn bp-send" style="background:#1A1A1A;color:#fff;border:0;border-radius:10px;padding:10px 12px;cursor:pointer;display:inline-flex;align-items:center">
                <span id="chatSendIcon" class="ms" style="font-size:18px;color:#fff">send</span>
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>

      <script src="https://cdn.jsdelivr.net/npm/stream-chat@8.40.0/dist/browser.full-bundle.min.js"></script>
      <script src="{{ asset('js/stream-chat-inbox.js') }}?v=23"></script>
    @endif

@if ($isBookpay)
  </div>
@else
  </div>
</main>
@endif
