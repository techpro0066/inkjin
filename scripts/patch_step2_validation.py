path = r"c:\xampp\htdocs\inkjin\resources\views\public\managed-book.blade.php"
with open(path, "r", encoding="utf-8") as f:
    c = f.read()

t = "div"

c = c.replace(
    "onclick=\"this.classList.toggle('selected')\"",
    "onclick=\"toggleDayPref(this)\"",
)

c = c.replace(
    '<motion><label class="text-xs font-semibold text-on-surface-variant mb-2 block">Preferred days of the week</label><motion class="flex flex-wrap gap-1.5" id="dayPills">',
    f'<{t} data-step2-field="days"><label class="text-xs font-semibold text-on-surface-variant mb-2 block">Preferred days of the week <span class="text-error">*</span></label><{t} class="flex flex-wrap gap-1.5" id="dayPills">',
    1,
)
c = c.replace("<motion", f"<{t}>").replace("</motion>", f"</{t}>")
