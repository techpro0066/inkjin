@extends('layouts.new-artist-dashboard-layout')

@section('title', 'Client Flow')

@php
  $helpBody = trim((string) ($textBlocks['when_to_get_help']['body'] ?? ''));
  $sectionByKey = collect($sections ?? [])->keyBy('key');
  $leftKeys = ['first_24_hours', 'keeping_it_clean'];
  $rightKeys = ['moisturizing', 'next_2_4_weeks'];
@endphp

@section('styles')
<style>
  .ac-page{max-width:1112px}
  .ac-page .ac-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;align-items:start}
  .ac-page .ac-grid > .ac-full{grid-column:1/-1}
  .ac-page .acitem{display:flex;align-items:center;gap:12px;margin-bottom:8px}
  .ac-page .acitem .in{
    flex:1;min-height:36px;padding:8px 12px;font-size:13px;font:inherit;color:var(--ink);
    background:#fff;border:1px solid var(--line);border-radius:10px;outline:none;width:100%
  }
  .ac-page .acitem .in:focus{border-color:#3E007C;box-shadow:0 0 0 3px #F3E8FF}
  .ac-page .acitem .ac-rm{
    border:0;background:none;padding:0;cursor:pointer;color:var(--faint);display:inline-flex;line-height:1
  }
  .ac-page .acitem .ac-rm .ms{font-size:20px}
  .ac-page .acitem .ac-rm:hover{color:var(--red)}
  .ac-page .acadd{
    display:inline-flex;align-items:center;gap:6px;font-weight:600;font-size:13px;margin-top:4px;
    cursor:pointer;border:0;background:none;padding:0;color:var(--ink);font:inherit
  }
  .ac-page .acadd .ms{font-size:17px}
  .ac-page #achelp{
    width:100%;min-height:74px;resize:none;overflow:hidden;font:inherit;font-size:13px;line-height:1.5;
    color:var(--ink);background:#fff;border:1px solid var(--line);border-radius:10px;padding:10px 13px;outline:none
  }
  .ac-page #achelp:focus{border-color:#3E007C;box-shadow:0 0 0 3px #F3E8FF}
  .ac-page .tog,.ac-page .toggle-switch{
    width:38px;height:22px;border-radius:12px;background:#D9D2DD;position:relative;flex-shrink:0;
    cursor:pointer;border:0;padding:0;display:inline-block
  }
  .ac-page .tog:after,.ac-page .toggle-switch:after{
    content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;top:3px;left:3px;transition:left .15s
  }
  .ac-page .tog.on,.ac-page .tog.active,.ac-page .toggle-switch.active{background:var(--pri)}
  .ac-page .tog.on:after,.ac-page .tog.active:after,.ac-page .toggle-switch.active:after{left:19px}
  .ac-page .foot{display:flex;justify-content:flex-start;gap:12px;margin-top:4px;flex-wrap:wrap;align-items:center}
  .ac-page .field-err{color:var(--red);font-size:13px;font-weight:600}
  .ac-page .cf-icon-btn{
    width:28px;height:28px;border:0;background:none;border-radius:8px;cursor:pointer;
    display:inline-flex;align-items:center;justify-content:center;color:var(--faint);padding:0
  }
  .ac-page .cf-icon-btn .ms{font-size:18px}
  .ac-page .cf-icon-btn:hover{background:#F7F3F9;color:var(--ink)}
  .ac-page .mp-toast{
    position:fixed;top:22px;right:24px;background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;
    font-weight:600;font-size:14px;z-index:400;box-shadow:0 10px 30px rgba(0,0,0,.22);
    display:flex;gap:10px;align-items:center;opacity:0;transform:translateX(calc(100% + 40px));
    transition:opacity .25s,transform .35s;pointer-events:none
  }
  .ac-page .mp-toast.show{opacity:1;transform:none;pointer-events:auto}
  #aftercarePreviewModal{
    position:fixed;inset:0;background:rgba(26,26,26,.45);z-index:300;
    align-items:center;justify-content:center;padding:24px;display:none
  }
  #aftercarePreviewModal:not(.hidden){display:flex!important}
  .ac-page .cs-modal-panel{
    background:#fff;border-radius:16px;border:1px solid var(--line);width:100%;max-width:640px;
    max-height:calc(100vh - 48px);overflow:hidden;box-shadow:0 18px 50px rgba(0,0,0,.18)
  }
  @media (max-width:900px){
    .ac-page .ac-grid{grid-template-columns:1fr}
    .ac-page .mp-toast{left:16px;right:16px;top:74px}
  }
</style>
@endsection

@section('content')
<div class="ac-page">
  <div class="head">
    <div>
      <h1>
        Client Flow
        <a class="help-q" href="https://help.inkjin.com/en/articles/17201402-dashboard-client-flow-aftercare" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page">
          <span class="ms">help</span>
        </a>
      </h1>
      <div class="sub">What clients fill in and receive, from first request to healed tattoo.</div>
    </div>
  </div>

  @include('artist.partials.client-flow-tabs', ['clientFlowTab' => 'aftercare'])

  <div class="ac-grid">
    <div class="ac-full">
      <div class="card" style="margin-bottom:0" id="aftercareAutoSendPanel">
        <div class="ch">
          <div>
            <h3>Send aftercare automatically</h3>
            <div class="faint" style="font-size:12.5px;margin-top:2px">Sent to the client right after the appointment ends</div>
          </div>
        </div>
        <div style="padding:18px 22px">
          <div class="row" style="justify-content:space-between">
            <div class="row">
              <span class="ms">schedule_send</span>
              <span style="font-size:13.5px">Email · right after the session</span>
            </div>
            <div class="row" style="gap:10px">
              <button type="button" class="btn sm ghost" id="btnPreviewAftercare"><span class="ms" style="font-size:16px">visibility</span>Preview</button>
              <div
                id="toggleAftercareAutoSend"
                class="tog toggle-switch {{ !empty($sendAutomatically) ? 'on active' : '' }}"
                role="switch"
                aria-checked="{{ !empty($sendAutomatically) ? 'true' : 'false' }}"
                title="Send aftercare automatically"
              ></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div>
      @foreach($leftKeys as $key)
        @php $section = $sectionByKey->get($key); @endphp
        @if($section)
          @include('artist.aftercare.partials.section-card', ['section' => $section])
        @endif
      @endforeach
    </div>

    <div>
      @foreach($rightKeys as $key)
        @php $section = $sectionByKey->get($key); @endphp
        @if($section)
          @include('artist.aftercare.partials.section-card', ['section' => $section])
        @endif
      @endforeach

      <div class="card" style="margin-bottom:14px" data-text-key="when_to_get_help">
        <div class="ch">
          <div>
            <h3>When to get help</h3>
            <div class="faint" style="font-size:12.5px;margin-top:2px">Shown at the end</div>
          </div>
        </div>
        <div style="padding:18px 22px">
          <textarea id="achelp" maxlength="2000" aria-label="When to get help">{{ $helpBody }}</textarea>
        </div>
      </div>
    </div>
  </div>

  {{-- Preserve email text blocks not shown in 08c UI --}}
  @foreach(['what_is_normal', 'once_healed', 'questions'] as $hiddenKey)
    <input type="hidden" class="ac-hidden-text" data-text-key="{{ $hiddenKey }}" value="{{ $textBlocks[$hiddenKey]['body'] ?? '' }}">
  @endforeach

  <div class="foot">
    <button type="button" class="btn" id="btnSaveAftercare"><span class="ms">save</span>Save changes</button>
    <p id="aftercareSaveStatus" class="field-err hidden" role="status" style="margin:0"></p>
  </div>

  <div id="aftercarePreviewModal" class="hidden" role="dialog" aria-modal="true" aria-labelledby="aftercarePreviewTitle">
    <div class="cs-modal-panel" style="display:flex;flex-direction:column;max-height:calc(100vh - 48px)" onclick="event.stopPropagation()">
      <div class="row" style="justify-content:space-between;padding:18px 22px 14px;border-bottom:1px solid #F0EAF2;flex-shrink:0">
        <h3 id="aftercarePreviewTitle" style="font-size:18px;font-weight:800;margin:0">Preview · Aftercare email</h3>
        <button type="button" class="cf-icon-btn" id="btnCloseAftercarePreview" aria-label="Close"><span class="ms">close</span></button>
      </div>
      <div id="aftercarePreviewBody" style="padding:16px 22px;overflow-y:auto;min-height:0;flex:1"></div>
      <div class="row" style="justify-content:flex-end;gap:10px;padding:14px 22px;border-top:1px solid #F0E9F2;flex-shrink:0">
        <button type="button" class="btn" id="btnCloseAftercarePreview2">Close</button>
      </div>
    </div>
  </div>

  <div class="mp-toast" id="acToast" role="status"><span class="ms">check_circle</span><span id="acToastText">Saved</span></div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script>
  (function () {
    var AFTERCARE_UPDATE_URL = @json(route('artist.aftercare.update'));
    var ARTIST_LABEL = @json($artistLabel ?? 'your artist');
    var $toggle = $("#toggleAftercareAutoSend");
    var $saveStatus = $("#aftercareSaveStatus");
    var $toast = $("#acToast");
    var $toastText = $("#acToastText");

    function esc(s) {
      return String(s == null ? "" : s)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
    }

    function showToast(msg) {
      $toastText.text(msg || "Saved");
      $toast.addClass("show");
      clearTimeout($toast.data("timer"));
      $toast.data("timer", setTimeout(function () { $toast.removeClass("show"); }, 2600));
    }

    function syncToggleUi() {
      var on = $toggle.hasClass("on") || $toggle.hasClass("active");
      $toggle.toggleClass("on", on).toggleClass("active", on);
      $toggle.attr("aria-checked", on ? "true" : "false");
    }

    function isAutoOn() {
      return $toggle.hasClass("on") || $toggle.hasClass("active");
    }

    function buildRow(value) {
      var $row = $('<div class="acitem row"></div>');
      var $input = $('<input type="text" class="in included-item-input" maxlength="500" placeholder="Add an aftercare tip">');
      if (value) $input.val(value);
      var $remove = $('<button type="button" class="ac-rm" title="Remove" aria-label="Remove item"><span class="ms">close</span></button>');
      $row.append($input, $remove);
      return $row;
    }

    function collectPayload(includeContent) {
      var payload = { send_automatically: isAutoOn() };
      if (!includeContent) return payload;

      var sections = {};
      $(".aftercare-section").each(function () {
        var key = String($(this).data("section-key") || "");
        if (!key) return;
        var items = [];
        $(this).find(".included-item-input").each(function () {
          var v = $.trim($(this).val());
          if (v) items.push(v);
        });
        sections[key] = items;
      });

      var textBlocks = {};
      $("[data-text-key]").each(function () {
        var key = String($(this).data("text-key") || "");
        if (!key) return;
        if ($(this).is("input[type=hidden]")) {
          textBlocks[key] = String($(this).val() || "");
          return;
        }
        textBlocks[key] = $.trim($(this).find("textarea").val() || "");
      });

      payload.sections = sections;
      payload.text_blocks = textBlocks;
      return payload;
    }

    function saveAftercare(payload) {
      return fetch(AFTERCARE_UPDATE_URL, {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
          "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") || "",
          "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify(payload),
        credentials: "same-origin"
      }).then(function (res) {
        var ct = res.headers.get("content-type") || "";
        if (ct.indexOf("application/json") !== -1) {
          return res.json().then(function (data) {
            return { ok: res.ok, status: res.status, data: data };
          });
        }
        return res.text().then(function () {
          return { ok: false, status: res.status, data: {} };
        });
      });
    }

    function showStatus(message, isError) {
      if (!message) {
        $saveStatus.addClass("hidden").text("");
        return;
      }
      $saveStatus
        .removeClass("hidden")
        .css("color", isError ? "var(--red)" : "var(--green)")
        .text(message);
      clearTimeout($saveStatus.data("timer"));
      $saveStatus.data("timer", setTimeout(function () {
        $saveStatus.addClass("hidden").text("");
      }, 3500));
    }

    function buildPreviewHtml() {
      var html = '<div style="border:1px solid #F0E9F2;border-radius:14px;overflow:hidden">';
      html += '<div style="background:#1A1A1A;color:#fff;padding:16px 20px">';
      html += '<div style="font-weight:800;font-size:16px">Caring for your new tattoo</div>';
      html += '<div style="font-size:12.5px;opacity:.75;margin-top:2px">From ' + esc(ARTIST_LABEL) + ' · sent right after your session</div>';
      html += '</div><div style="padding:6px 20px 16px">';

      $(".aftercare-section").each(function () {
        var title = $.trim($(this).find("h3").first().text() || "");
        var items = [];
        $(this).find(".included-item-input").each(function () {
          var v = $.trim($(this).val());
          if (v) items.push(v);
        });
        if (!items.length || !title) return;
        html += '<div style="font-weight:700;font-size:14px;margin-top:14px">' + esc(title) + "</div>";
        html += '<ul style="margin:6px 0 0 18px;padding:0;font-size:13px;line-height:1.6">';
        items.forEach(function (v) {
          html += "<li>" + esc(v) + "</li>";
        });
        html += "</ul>";
      });

      var help = $.trim($("#achelp").val() || "");
      if (help) {
        html += '<div style="margin-top:16px;padding:12px 14px;background:#FFF3DC;border-radius:10px;font-size:12.5px;line-height:1.5"><b>When to get help.</b> ' + esc(help) + "</div>";
      }
      html += "</div></div>";
      return html;
    }

    function openPreview() {
      $("#aftercarePreviewBody").html(buildPreviewHtml());
      $("#aftercarePreviewModal").removeClass("hidden");
      document.body.style.overflow = "hidden";
    }

    function closePreview() {
      $("#aftercarePreviewModal").addClass("hidden");
      document.body.style.overflow = "";
    }

    $toggle.on("click", function () {
      var $t = $(this);
      var wasOn = isAutoOn();
      $t.toggleClass("on active");
      syncToggleUi();
      var nowOn = isAutoOn();
      $t.css({ opacity: 0.6, pointerEvents: "none" });

      saveAftercare({ send_automatically: nowOn }).then(function (result) {
        if (result.ok && result.data && result.data.success) {
          showToast(result.data.message || "Saved.");
        } else {
          if (wasOn) $t.addClass("on active");
          else $t.removeClass("on active");
          syncToggleUi();
          showStatus((result.data && result.data.message) || "Could not update auto-send. Please try again.", true);
        }
      }).catch(function () {
        if (wasOn) $t.addClass("on active");
        else $t.removeClass("on active");
        syncToggleUi();
        showStatus("Could not update auto-send. Please try again.", true);
      }).finally(function () {
        $t.css({ opacity: "", pointerEvents: "" });
      });
    });

    $(document).on("click", ".btn-add-aftercare-item", function () {
      var $list = $(this).closest(".aftercare-section").find(".aftercare-item-list");
      $list.append(buildRow(""));
      $list.find(".acitem:last .included-item-input").trigger("focus");
    });

    $(document).on("click", ".aftercare-item-list .ac-rm", function () {
      var $row = $(this).closest(".acitem");
      var $list = $row.closest(".aftercare-item-list");
      if ($list.find(".acitem").length <= 1) {
        $row.find(".included-item-input").val("").trigger("focus");
        return;
      }
      $row.remove();
    });

    $("#btnSaveAftercare").on("click", function () {
      var $btn = $(this);
      var btnHtml = $btn.html();
      $btn.prop("disabled", true).html('<span class="ms">hourglass_empty</span> Saving…');

      saveAftercare(collectPayload(true)).then(function (result) {
        if (result.ok && result.data && result.data.success) {
          showStatus("");
          showToast(result.data.message || "Changes saved successfully");
        } else {
          showStatus((result.data && result.data.message) || "Could not save. Please try again.", true);
        }
      }).catch(function () {
        showStatus("Could not save. Please try again.", true);
      }).finally(function () {
        $btn.prop("disabled", false).html(btnHtml);
      });
    });

    $("#btnPreviewAftercare").on("click", openPreview);
    $("#btnCloseAftercarePreview, #btnCloseAftercarePreview2, #aftercarePreviewModal").on("click", function (e) {
      if (e.target === this || $(e.target).closest("#btnCloseAftercarePreview, #btnCloseAftercarePreview2").length) {
        closePreview();
      }
    });
    $(document).on("keydown", function (e) {
      if (e.key === "Escape" && !$("#aftercarePreviewModal").hasClass("hidden")) closePreview();
    });

    function autosizeHelp() {
      var el = document.getElementById("achelp");
      if (!el) return;
      el.style.height = "auto";
      el.style.height = Math.max(74, el.scrollHeight) + "px";
    }
    $("#achelp").on("input", autosizeHelp);
    autosizeHelp();

    syncToggleUi();
  })();
</script>
@endsection
