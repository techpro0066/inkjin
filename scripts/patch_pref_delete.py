import re

path = r"c:\xampp\htdocs\inkjin\resources\views\public\managed-book.blade.php"
with open(path, "r", encoding="utf-8") as f:
    content = f.read()

d = "div"

helpers = f"""
    const PREF_TIME_PILLS = '<{d} class="flex flex-wrap gap-1.5"><button type="button" class="time-pref-pill" data-value="Morning" onclick="toggleTimePref(this)">Morning</button><button type="button" class="time-pref-pill" data-value="Afternoon" onclick="toggleTimePref(this)">Afternoon</button><button type="button" class="time-pref-pill" data-value="Evening" onclick="toggleTimePref(this)">Evening</button></{d}>';

    function prefRemoveBtnHtml(containerId) {{
      return '<button type="button" class="pref-remove-btn" onclick="removePreferenceBlock(this, \\'' + containerId + '\\')" aria-label="Remove preference"><span class="material-symbols-outlined text-[16px]">close</span> Remove</button>';
    }}

    function prefHeaderHtml(num, containerId, deletable, required) {{
      const req = required ? ' <span class="text-error">*</span>' : '';
      let html = '<{d} class="pref-block-header"><p class="text-xs font-bold text-primary uppercase tracking-wider pref-block-label">Preference ' + num + req + '</p>';
      if (deletable) html += prefRemoveBtnHtml(containerId);
      return html + '</{d}>';
    }}

    function prefFieldsHtml(dateInputClass) {{
      return '<{d} class="grid grid-cols-1 sm:grid-cols-2 gap-3"><{d}><label class="text-xs font-semibold text-on-surface-variant mb-1 block">Date</label><input type="date" class="' + dateInputClass + ' w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"></{d}><{d}><label class="text-xs font-semibold text-on-surface-variant mb-1 block">Time of day</label>' + PREF_TIME_PILLS + '</{d}></{d}>';
    }}

    function buildPreferenceBlockHtml(num, containerId, dateInputClass, deletable, required) {{
      return prefHeaderHtml(num, containerId, deletable, required) + prefFieldsHtml(dateInputClass);
    }}

    function renumberPreferenceBlocks(containerId) {{
      const blocks = document.querySelectorAll('#' + containerId + ' .pref-block');
      blocks.forEach(function(block, index) {{
        block.dataset.pref = String(index);
        const label = block.querySelector('.pref-block-label');
        if (label) {{
          label.innerHTML = 'Preference ' + (index + 1) + (index === 0 ? ' <span class="text-error">*</span>' : '');
        }}
        const removeBtn = block.querySelector('.pref-remove-btn');
        if (index > 0 && !removeBtn) {{
          const header = block.querySelector('.pref-block-header');
          if (header) header.insertAdjacentHTML('beforeend', prefRemoveBtnHtml(containerId));
        }} else if (index === 0 && removeBtn) {{
          removeBtn.remove();
        }}
      }});
      return blocks.length;
    }}

    window.removePreferenceBlock = function(btn, containerId) {{
      const container = document.getElementById(containerId);
      const block = btn.closest('.pref-block');
      if (!container || !block) return;
      const blocks = container.querySelectorAll('.pref-block');
      const index = Array.prototype.indexOf.call(blocks, block);
      if (index <= 0) return;
      block.remove();
      const count = renumberPreferenceBlocks(containerId);
      if (containerId === 'prefBlocks') {{
        prefCount = count;
        document.getElementById('addPrefBtn').classList.remove('hidden');
      }} else {{
        mcPrefCount = count;
        document.getElementById('mcAddPrefBtn').classList.remove('hidden');
      }}
    }}

"""

m = re.search(
    r"    let prefCount = 1;\n    window\.addPreference = function\(\) \{.*?    \};\n",
    content,
    re.DOTALL,
)
if not m:
    raise SystemExit("addPreference block not found")

new_add = (
    helpers
    + f"""    let prefCount = 1;
    window.addPreference = function() {{
      if (prefCount >= 5) return;
      prefCount++;
      const block = document.createElement('{d}');
      block.className = 'pref-block';
      block.dataset.pref = String(prefCount - 1);
      block.innerHTML = buildPreferenceBlockHtml(prefCount, 'prefBlocks', 'pref-date', true, false);
      document.getElementById('prefBlocks').appendChild(block);
      if (prefCount >= 5) document.getElementById('addPrefBtn').classList.add('hidden');
    }};
"""
)

content = content.replace(m.group(0), new_add, 1)

m2 = re.search(
    r"    let mcPrefCount = 2;\n    window\.addMcPreference = function\(\) \{.*?    \};\n",
    content,
    re.DOTALL,
)
if not m2:
    raise SystemExit("addMcPreference block not found")

new_mc = f"""    let mcPrefCount = 2;
    window.addMcPreference = function() {{
      if (mcPrefCount >= 5) return;
      mcPrefCount++;
      const block = document.createElement('{d}');
      block.className = 'pref-block';
      block.dataset.pref = String(mcPrefCount - 1);
      block.innerHTML = buildPreferenceBlockHtml(mcPrefCount, 'mcPrefBlocks', 'mc-pref-date', true, false);
      document.getElementById('mcPrefBlocks').appendChild(block);
      if (mcPrefCount >= 5) document.getElementById('mcAddPrefBtn').classList.add('hidden');
    }};
"""

content = content.replace(m2.group(0), new_mc, 1)

with open(path, "w", encoding="utf-8", newline="\n") as f:
    f.write(content)
print("OK")
