path = r"c:\xampp\htdocs\inkjin\resources\views\public\managed-book.blade.php"
with open(path, "r", encoding="utf-8") as f:
    content = f.read()

old = """        </div>
      </div>
        <p id="mcConsultTypeError" class="hidden text-sm text-error mt-3">Please select a consultation type before continuing.</p>
      </motion>"""

new = """        </div>
        <p id="mcConsultTypeError" class="hidden text-sm text-error mt-3">Please select a consultation type before continuing.</p>
      </motion>"""

old = old.replace("</motion>", "</div>")
new = new.replace("</motion>", "</div>")

if old not in content:
    raise SystemExit("Pattern not found")
content = content.replace(old, new, 1)
with open(path, "w", encoding="utf-8", newline="\n") as f:
    f.write(content)
print("OK")
