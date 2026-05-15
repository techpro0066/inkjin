path = r"c:\xampp\htdocs\inkjin\resources\views\public\managed-book.blade.php"
with open(path, "r", encoding="utf-8") as f:
    c = f.read()

TAG = "d" + "iv"

def once(old, new, name):
    global c
    if old not in c:
        raise SystemExit(f"Missing [{name}]")
    c = c.replace(old, new, 1)

once(
    "Sun</button></" + TAG + "></" + TAG + ">\n          <" + TAG + '><label class="text-xs font-semibold text-on-surface-variant mb-1 block">Any dates to avoid?</label><input type="text" id="managedAvoid"',
    "Sun</button></" + TAG + '><p id="managedDayError" class="hidden text-sm text-error mt-2">Please select at least one preferred day.</p></' + TAG + ">\n          <" + TAG + '><label class="text-xs font-semibold text-on-surface-variant mb-1 block">Any dates to avoid?</label><input type="text" id="managedAvoid"',
    "managedDayError",
)

once(
    "<" + TAG + '><label class="text-xs font-semibold text-on-surface-variant mb-2 block">How flexible are you?</label><' + TAG + ' class="flex flex-wrap gap-2" id="flexPills">',
    "<" + TAG + ' data-step2-field="flex"><label class="text-xs font-semibold text-on-surface-variant mb-2 block">How flexible are you? <span class="text-error">*</span></label><' + TAG + ' class="flex flex-wrap gap-2" id="flexPills">',
    "flexOpen",
)

once(
    "onclick=\"selectPill(this,'flexPills')\">These are my only options</button></" + TAG + "></" + TAG + ">\n          <" + TAG + '><label class="text-xs font-semibold text-on-surface-variant mb-2 block">Urgency</label>',
    "onclick=\"selectPill(this,'flexPills')\">These are my only options</button></" + TAG + '><p id="managedFlexError" class="hidden text-sm text-error mt-2">Please select how flexible you are.</p></' + TAG + ">\n          <" + TAG + ' data-step2-field="urgency"><label class="text-xs font-semibold text-on-surface-variant mb-2 block">Urgency <span class="text-error">*</span></label>',
    "flexClose",
)

once(
    "onclick=\"selectPill(this,'urgencyPills')\">ASAP</button></" + TAG + "></" + TAG + ">\n        </" + TAG + ">",
    "onclick=\"selectPill(this,'urgencyPills')\">ASAP</button></" + TAG + '><p id="managedUrgencyError" class="hidden text-sm text-error mt-2">Please select your urgency.</p></' + TAG + ">\n        </" + TAG + ">",
    "urgencyClose",
)

once(
    "<" + TAG + '><label class="text-xs font-semibold text-on-surface-variant mb-2 block">Preferred days of the week <span class="text-error">*</span></label><' + TAG + ' class="flex flex-wrap gap-1.5" id="mcDayPills" data-step2-field="days">',
    "<" + TAG + ' data-step2-field="days"><label class="text-xs font-semibold text-on-surface-variant mb-2 block">Preferred days of the week <span class="text-error">*</span></label><' + TAG + ' class="flex flex-wrap gap-1.5" id="mcDayPills">',
    "mcDaysOpen",
)

once(
  "Sun</button></" + TAG + "></" + TAG + ">\n            <" + TAG + '><label class="text-xs font-semibold text-on-surface-variant mb-2 block">How flexible are you?</label><' + TAG + ' class="flex flex-wrap gap-2" id="mcFlexPills">',
  "Sun</button></" + TAG + '><p id="mcDayError" class="hidden text-sm text-error mt-2">Please select at least one preferred day.</p></' + TAG + ">\n            <" + TAG + '><label class="text-xs font-semibold text-on-surface-variant mb-2 block">How flexible are you?</label><' + TAG + ' class="flex flex-wrap gap-2" id="mcFlexPills">',
  "mcDayError",
)

once(
    "<" + TAG + '><label class="text-xs font-semibold text-on-surface-variant mb-2 block">How flexible are you?</label><' + TAG + ' class="flex flex-wrap gap-2" id="mcFlexPills">',
    "<" + TAG + ' data-step2-field="flex"><label class="text-xs font-semibold text-on-surface-variant mb-2 block">How flexible are you? <span class="text-error">*</span></label><' + TAG + ' class="flex flex-wrap gap-2" id="mcFlexPills">',
    "mcFlexOpen",
)

once(
    "onclick=\"selectPill(this,'mcFlexPills')\">These are my only options</button></" + TAG + "></" + TAG + ">\n            <" + TAG + '><label class="text-xs font-semibold text-on-surface-variant mb-2 block">How soon after the consultation would you like your tattoo session?</label>',
    "onclick=\"selectPill(this,'mcFlexPills')\">These are my only options</button></" + TAG + '><p id="mcFlexError" class="hidden text-sm text-error mt-2">Please select how flexible you are.</p></' + TAG + ">\n            <" + TAG + ' data-step2-field="gap"><label class="text-xs font-semibold text-on-surface-variant mb-2 block">How soon after the consultation would you like your tattoo session? <span class="text-error">*</span></label>',
    "mcFlexClose",
)

once(
    "onclick=\"selectPill(this,'mcGapPills')\">I'm flexible</button></" + TAG + "></" + TAG + ">\n          </" + TAG + ">",
    "onclick=\"selectPill(this,'mcGapPills')\">I'm flexible</button></" + TAG + '><p id="mcGapError" class="hidden text-sm text-error mt-2">Please select when you would like your tattoo session after the consultation.</p></' + TAG + ">\n          </" + TAG + ">",
    "mcGapClose",
)

with open(path, "w", encoding="utf-8", newline="\n") as f:
    f.write(c)
print("OK")
