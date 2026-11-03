@extends('layouts.studio-dashboard-layout')

@section('title', 'Bookings · Requests')

@push('styles')
<style>
  .bk .tabs a{display:inline-flex;align-items:center;gap:7px}
  .bk .tabs .n{background:var(--chip);color:var(--muted);border-radius:10px;font-size:11px;padding:1px 7px;font-weight:600}
  .bk .tabs .n.zero{background:#FFF3DE;color:#B86E00}
  .bk .tabs a.on .n{background:var(--ink);color:#fff}
  .bk .tabs a.on .n.zero{background:#FFF3DE;color:#B86E00}
  .bk .pill{gap:5px}
  .bk .pill:before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor}
  .bk .pill.nd:before{display:none}
  .bk .pill.b{background:#E8F0FF;color:#1E5BD8}
  .bk .pill.r{background:#FDECEC;color:#C62828}
  .bk .avatar{width:36px;height:36px;border-radius:50%;background:#EDE6F0;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#1A1A1A;flex-shrink:0}
  .bk .req-card{margin-bottom:10px}
  .bk .req-card .row{gap:14px;padding:14px 18px;flex-wrap:wrap}
  .bk .empty{padding:48px 22px;text-align:center;color:var(--muted)}
  .bk .note{border:1.5px dashed #B99BE0;background:#FBF5FF;border-radius:12px;padding:10px 14px;font-size:12.5px;color:#5B2A94;display:flex;gap:8px;align-items:center;margin-top:14px}
  .bk .note .ms{font-size:18px}
  .bk .card-actions{display:flex;align-items:center;gap:8px;flex-shrink:0;flex-wrap:wrap;justify-content:flex-end;margin-left:auto}
  .bk .ans,.smodal .ans{border:1px solid var(--line);border-radius:12px;padding:10px 12px;margin-top:8px;font-size:13px}
  .bk .ans b,.smodal .ans b{display:block;font-size:12px;color:var(--muted);font-weight:600;margin-bottom:2px}

  .smodal{position:fixed;inset:0;background:rgba(20,10,30,.45);display:flex;align-items:center;justify-content:center;z-index:400;padding:24px}
  .smodal[hidden]{display:none!important}
  .smodal .smbox{background:#fff;border-radius:18px;width:560px;max-width:100%;max-height:calc(100vh - 48px);max-height:calc(100dvh - 48px);display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.25)}
  .smodal .smh{display:flex;gap:12px;align-items:flex-start;padding:20px 24px 14px;border-bottom:1px solid var(--line)}
  .smodal .smh h2{font-size:19px;font-weight:800;margin:0}
  .smodal .smh .ic{width:38px;height:38px;border-radius:10px;background:#F3E8FF;color:#3E007C;display:flex;align-items:center;justify-content:center;flex-shrink:0}
  .smodal .smh .x{margin-left:auto;border:0;background:none;cursor:pointer;padding:2px;color:var(--muted);width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0}
  .smodal .smh .x:hover{background:#F4F0F6}
  .smodal .smb{padding:18px 24px;overflow:auto;min-height:0}
  .smodal .smf{display:flex;gap:10px;justify-content:flex-end;padding:14px 24px;border-top:1px solid var(--line);flex-wrap:wrap;flex-shrink:0}
  .smodal .tint{background:var(--tint,#FAF0FB);border:1px solid var(--tintline,#F1E7F3);border-radius:12px;padding:12px 14px}
  .smodal .link-answers{font-size:12.5px;display:inline-block;margin-top:6px;color:#3E007C;font-weight:600;background:none;border:0;padding:0;cursor:pointer;font:inherit}
  .smodal .link-answers:hover{text-decoration:underline}
  .smodal select.in, .smodal textarea.in{font:inherit;font-size:13.5px;width:100%;outline:none}
  .smodal textarea.in{min-height:86px;resize:vertical}
  .smodal .answers-panel[hidden]{display:none!important}
  .smodal .detail-row{display:flex;justify-content:space-between;gap:16px;padding:8px 0;border-bottom:1px solid #F3EEF4;font-size:13.5px}
  .smodal .detail-row:last-child{border-bottom:0}
  .smodal .img-grid{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px}
  .smodal .img-grid a{display:block;width:72px;height:72px;border-radius:10px;overflow:hidden;border:1px solid var(--line);background:#F7F3F9}
  .smodal .img-grid img{width:100%;height:100%;object-fit:cover;display:block}
</style>
@endpush

@section('content')
<div class="bk">
  <div class="head">
    <div>
      <h1>Bookings</h1>
      <div class="sub">Bookings and requests for your artists at {{ $studioName }}.</div>
    </div>
  </div>

  @include('studio.bookings.partials.tabs')

  @if(session('success'))
    <div class="note" style="border-style:solid;background:#E6F8EE;color:#00A650;margin-bottom:14px">
      <span class="ms">check_circle</span>{{ session('success') }}
    </div>
  @endif
  @if(session('error'))
    <div class="note" style="border-style:solid;background:#FDECEC;color:#C62828;margin-bottom:14px">
      <span class="ms">error</span>{{ session('error') }}
    </div>
  @endif

  @if($requestCards->isEmpty())
    <div class="card">
      <div class="empty">
        <div style="font-weight:700;color:var(--ink);margin-bottom:4px">No requests yet</div>
        <div>When someone submits a tattoo request on your studio page, it appears here.</div>
      </div>
    </div>
  @else
    @foreach($requestCards as $card)
      <div class="card req-card" @if(!empty($card['card_border'])) style="border-color:{{ $card['card_border'] }}" @endif>
        <div class="row" style="align-items:center">
          <div class="avatar">{{ $card['initials'] }}</div>
          <div style="flex:1;min-width:220px">
            <div class="row" style="gap:8px;flex-wrap:wrap">
              <b>{{ $card['client'] }}</b>
              <span class="pill nd k">{{ $card['type_label'] }}</span>
              <span class="pill {{ $card['status_pill'] }}">{{ $card['status_label'] }}</span>
            </div>
            <div class="muted" style="font-size:12.5px;margin-top:3px">{{ $card['subtitle'] }}</div>
          </div>
          <div class="card-actions">
            @if($card['right_hint'] && !$card['cta'])
              <span class="faint" style="font-size:12px">{{ $card['right_hint'] }}</span>
            @elseif($card['right_hint'] && ($card['status_key'] ?? '') === 'waiting_artist')
              <span class="faint" style="font-size:12px">{{ $card['right_hint'] }}</span>
            @endif
            <button type="button" class="btn sm ghost js-view-request" data-id="{{ $card['id'] }}">View</button>
            @if(!empty($card['can_withdraw']))
              <form method="POST" action="{{ route('studio.bookings.requests.withdraw', $card['id']) }}" onsubmit="return confirm('Withdraw this request?');" style="display:inline">
                @csrf
                <button type="submit" class="btn sm ghost">Withdraw</button>
              </form>
            @endif
            @if($card['cta'])
              <button
                type="button"
                class="btn sm js-assign"
                data-id="{{ $card['id'] }}"
                data-client="{{ $card['client'] }}"
                data-idea="{{ $card['idea'] }}"
                data-preselect="{{ $card['preselect_artist_id'] }}"
              ><span class="ms">person_add</span>{{ $card['cta'] }}</button>
            @endif
          </div>
        </div>
      </div>
      <script type="application/json" id="req-data-{{ $card['id'] }}">@json($card)</script>
    @endforeach
  @endif

  <div class="note">
    <span class="ms">info</span>
    Assign an artist to send them an offer. A custom request is created only after they accept. If they send it back, assign someone else. You can withdraw while waiting for the artist.
  </div>
</div>

{{-- View request modal --}}
<div class="smodal" id="m-view" hidden>
  <div class="smbox" role="dialog" aria-modal="true" aria-labelledby="m-view-t">
    <div class="smh">
      <div class="ic" style="background:#F0EDF2;color:#1A1A1A"><span class="ms">visibility</span></div>
      <div style="flex:1">
        <h2 id="m-view-t">Request</h2>
        <div class="muted" id="m-view-sub" style="font-size:13.5px;margin-top:4px"></div>
      </div>
      <button type="button" class="x" data-close aria-label="Close"><span class="ms">close</span></button>
    </div>
    <div class="smb" id="m-view-body"></div>
    <div class="smf">
      <button type="button" class="btn ghost" data-close>Close</button>
      <button type="button" class="btn" id="m-view-assign" hidden><span class="ms">person_add</span>Assign artist</button>
    </div>
  </div>
</div>

{{-- Assign artist modal (matches S04b template) --}}
<div class="smodal" id="m-assign" hidden>
  <div class="smbox" role="dialog" aria-modal="true" aria-labelledby="m-assign-t">
    <div class="smh">
      <div class="ic" style="background:#F3E8FF;color:#3E007C"><span class="ms">person_add</span></div>
      <div>
        <h2 id="m-assign-t">Assign an artist</h2>
        <div class="muted" style="font-size:13.5px;margin-top:4px">The artist gets the request and replies to the client, or sends it back to you.</div>
      </div>
      <button type="button" class="x" data-close aria-label="Close"><span class="ms">close</span></button>
    </div>
    <form method="POST" id="assignForm" action="#">
      @csrf
      <div class="smb">
        <div class="tint">
          <b id="as-name">Client</b>
          <div class="muted" style="font-size:12.5px;margin-top:3px" id="as-idea"></div>
          <button type="button" class="link-answers" id="as-see-answers">See all answers</button>
          <div class="answers-panel" id="as-answers" hidden></div>
        </div>

        <span class="fl" style="margin-top:16px">Artist <span style="color:#C62828">*</span></span>
        <div style="position:relative">
          <select class="in" name="artist_user_id" id="artist_user_id" required aria-label="Artist">
            <option value="">Choose an artist</option>
            @foreach($assignArtists as $artist)
              <option value="{{ $artist['user_id'] }}">{{ $artist['option_label'] ?? $artist['name'] }}</option>
            @endforeach
          </select>
        </div>

        <span class="fl" style="margin-top:14px">Note to the artist (optional)</span>
        <textarea class="in" name="note" id="assign_note" placeholder="e.g. She asked for someone who does fine line"></textarea>
        <div class="help">The client isn't told who was picked until the artist replies.</div>
      </div>
      <div class="smf">
        <button type="button" class="btn ghost" data-close>Cancel</button>
        <button type="submit" class="btn"><span class="ms">send</span>Send to artist</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
@include('studio.partials.bp-select')
<script>
(function () {
  var assignModal = document.getElementById('m-assign');
  var viewModal = document.getElementById('m-view');
  var form = document.getElementById('assignForm');
  var assignUrlBase = @json(url('/studio/bookings/requests'));
  var pendingAssignBtn = null;

  [assignModal, viewModal].forEach(function (m) {
    if (m && m.parentNode !== document.body) document.body.appendChild(m);
  });
  if (typeof window.bpSelectEnhance === 'function') window.bpSelectEnhance(document);

  function esc(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function loadCard(id) {
    try {
      var el = document.getElementById('req-data-' + id);
      return el ? JSON.parse(el.textContent || '{}') : {};
    } catch (e) { return {}; }
  }

  function renderAnswers(wrap, answers) {
    wrap.innerHTML = '';
    if (!answers || !answers.length) {
      wrap.innerHTML = '<div class="muted" style="font-size:12.5px;margin-top:8px">No answers submitted.</div>';
      return;
    }
    answers.forEach(function (a) {
      var d = document.createElement('div');
      d.className = 'ans';
      d.innerHTML = '<b></b><span></span>';
      d.querySelector('b').textContent = a.q || 'Question';
      d.querySelector('span').textContent = a.a || '';
      wrap.appendChild(d);
    });
  }

  function openAssign(btn) {
    if (!assignModal || !form || !btn) return;
    var id = btn.getAttribute('data-id');
    var data = loadCard(id);
    form.action = assignUrlBase + '/' + id + '/assign';
    document.getElementById('as-name').textContent = btn.getAttribute('data-client') || data.client || 'Client';
    document.getElementById('as-idea').textContent = btn.getAttribute('data-idea') || data.idea || '';
    var pre = btn.getAttribute('data-preselect') || String(data.preselect_artist_id || '');
    document.getElementById('artist_user_id').value = pre && pre !== '0' ? pre : '';
    document.getElementById('assign_note').value = '';
    var answersPanel = document.getElementById('as-answers');
    answersPanel.hidden = true;
    document.getElementById('as-see-answers').textContent = 'See all answers';
    renderAnswers(answersPanel, data.answers || []);
    if (viewModal) viewModal.hidden = true;
    assignModal.hidden = false;
    if (typeof window.bpSelectEnhance === 'function') window.bpSelectEnhance(assignModal);
    setTimeout(function () {
      try {
        var sel = document.getElementById('artist_user_id');
        if (sel && sel.__bps) sel.__bps.focus();
        else if (sel) sel.focus();
      } catch (e) {}
    }, 30);
  }

  function openView(btn) {
    if (!viewModal) return;
    var data = loadCard(btn.getAttribute('data-id'));
    document.getElementById('m-view-t').textContent = data.client || 'Request';
    document.getElementById('m-view-sub').textContent =
      '#' + (data.id || '') + (data.created_label ? ' · ' + data.created_label : '');

    var html = '';
    html += '<div class="detail-row"><span class="muted">Status</span><b>' + esc(data.status_label || '—') + '</b></div>';
    html += '<div class="detail-row"><span class="muted">Email</span><b>' + esc(data.email || '—') + '</b></div>';
    html += '<div class="detail-row"><span class="muted">Phone</span><b>' + esc(data.phone || '—') + '</b></div>';
    html += '<div class="detail-row"><span class="muted">Artist</span><b>' + esc(data.artist_name || 'Not assigned') + '</b></div>';
    if (data.client_artist_choice) {
      html += '<div class="detail-row"><span class="muted">Client chose</span><b>' + esc(data.client_artist_choice) + '</b></div>';
    }
    if (data.notes) {
      html += '<div class="ans" style="margin-top:12px"><b>Notes</b><span>' + esc(data.notes) + '</span></div>';
    }
    if (data.send_back_history && data.send_back_history.length) {
      html += '<div class="fl" style="margin-top:16px">Send-back / withdraw history</div>';
      data.send_back_history.slice().reverse().forEach(function (entry) {
        var title = (entry.action_label || 'Update') + ' · ' + (entry.actor_name || entry.artist_name || '—');
        if (entry.at_label) title += ' · ' + entry.at_label;
        html += '<div class="ans"><b>' + esc(title) + '</b><span>' +
          esc(entry.reason || 'No reason given') + '</span></div>';
      });
    }
    if (data.answers && data.answers.length) {
      html += '<div class="fl" style="margin-top:16px">Answers</div>';
      data.answers.forEach(function (a) {
        html += '<div class="ans"><b>' + esc(a.q || 'Question') + '</b><span>' + esc(a.a || '') + '</span></div>';
      });
    }
    if (data.images && data.images.length) {
      html += '<div class="fl" style="margin-top:16px">Reference images</div><div class="img-grid">';
      data.images.forEach(function (src) {
        html += '<a href="' + esc(src) + '" target="_blank" rel="noopener"><img src="' + esc(src) + '" alt=""></a>';
      });
      html += '</div>';
    }
    document.getElementById('m-view-body').innerHTML = html;

    var assignBtn = document.getElementById('m-view-assign');
    pendingAssignBtn = null;
    if (data.cta) {
      pendingAssignBtn = document.querySelector('.js-assign[data-id="' + data.id + '"]');
      assignBtn.hidden = false;
    } else {
      assignBtn.hidden = true;
    }
    viewModal.hidden = false;
  }

  document.querySelectorAll('.js-assign').forEach(function (btn) {
    btn.addEventListener('click', function () { openAssign(btn); });
  });
  document.querySelectorAll('.js-view-request').forEach(function (btn) {
    btn.addEventListener('click', function () { openView(btn); });
  });

  var seeAnswers = document.getElementById('as-see-answers');
  if (seeAnswers) {
    seeAnswers.addEventListener('click', function () {
      var panel = document.getElementById('as-answers');
      var open = panel.hidden;
      panel.hidden = !open;
      seeAnswers.textContent = open ? 'Hide answers' : 'See all answers';
    });
  }

  var viewAssign = document.getElementById('m-view-assign');
  if (viewAssign) {
    viewAssign.addEventListener('click', function () {
      if (pendingAssignBtn) openAssign(pendingAssignBtn);
    });
  }

  function closeModal(modal) {
    if (!modal) return;
    modal.hidden = true;
    if (typeof window.bpSelectClose === 'function') window.bpSelectClose();
  }

  [assignModal, viewModal].forEach(function (modal) {
    if (!modal) return;
    modal.querySelectorAll('[data-close]').forEach(function (el) {
      el.addEventListener('click', function () { closeModal(modal); });
    });
    modal.addEventListener('click', function (e) {
      if (e.target === modal) closeModal(modal);
    });
  });
})();
</script>
@endpush
