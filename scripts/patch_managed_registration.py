path = r"c:\xampp\htdocs\inkjin\resources\views\public\managed-book.blade.php"
with open(path, "r", encoding="utf-8") as f:
    c = f.read()

t = "d" + "iv"

# Fix accidental motion tag on reg-3
c = c.replace('<motion class="question-div" data-reg="3"', f'<{t} class="question-div" data-reg="3"', 1)

c = c.replace('class="tf-screen" data-reg="1"', 'class="question-div" data-reg="1"', 1)

# Error messages
c = c.replace(
    'id="bdName" placeholder="Your full name"',
    'id="bdName" placeholder="Your full name"',
)
if 'id="bdNameError"' not in c:
    c = c.replace(
        'id="bdName" placeholder="Your full name" class="w-full border',
        'id="bdName" placeholder="Your full name" class="w-full border',
    )
    c = c.replace(
        '<input type="text" id="bdName" placeholder="Your full name" class="w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">\n          <div class="flex items-center justify-between mt-6"><button onclick="nextReg()"',
        '<input type="text" id="bdName" placeholder="Your full name" class="w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">\n          <p id="bdNameError" class="text-sm text-error mt-2 hidden">This field is required.</p>\n          <div class="flex items-center justify-between mt-6"><button onclick="nextReg()"',
        1,
    )
    c = c.replace(
        '<input type="email" id="bdEmail" placeholder="you@example.com" class="w-full border border-outline-variant/30 bg-white rounded-xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">',
        '<input type="email" id="bdEmail" placeholder="you@example.com" class="w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">',
    )
    c = c.replace(
        '<input type="email" id="bdEmail" placeholder="you@example.com" class="w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">\n          <div class="flex items-center justify-between mt-6"><button onclick="nextReg()"',
        '<input type="email" id="bdEmail" placeholder="you@example.com" class="w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">\n          <p id="bdEmailError" class="text-sm text-error mt-2 hidden">This field is required.</p>\n          <motion class="flex items-center justify-between mt-6"><button onclick="nextReg()"',
    )
    c = c.replace('<motion class="flex items-center justify-between mt-6"><button onclick="nextReg()"', '<div class="flex items-center justify-between mt-6"><button onclick="nextReg()"', 1)
    c = c.replace(
        '<input type="tel" id="bdPhone" placeholder="+30 694 123 4567" class="w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">\n          <div class="flex items-center justify-between mt-6"><button onclick="nextReg()"',
        '<input type="tel" id="bdPhone" placeholder="+30 694 123 4567" class="w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">\n          <p id="bdPhoneError" class="text-sm text-error mt-2 hidden">This field is required.</p>\n          <div class="flex items-center justify-between mt-6"><button onclick="nextReg()"',
        1,
    )

# Replace bdAuthCreate block (password flow -> OTP flow)
old_auth = """          <div id="bdAuthCreate">
            <motion class="text-center mb-6"><span class="material-symbols-outlined text-primary text-4xl mb-2">person_add</span><h2 class="text-2xl sm:text-3xl font-bold text-on-surface mb-2">Create your free account</h2><p class="text-on-surface-variant">Track your bookings, message artists, and manage appointments.</p></motion>
            <div class="flex items-center gap-2 bg-surface-container rounded-xl px-4 py-3 mb-5"><span class="material-symbols-outlined text-primary text-[18px]">mail</span><span class="text-sm text-on-surface" id="bdAuthEmail">you@example.com</span><span class="material-symbols-outlined text-green-500 text-[16px] ml-auto">check_circle</span></motion>
            <div class="mb-2"><input type="password" id="bdPassword" placeholder="Create a password" class="w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30"></motion>
            <p class="text-xs text-on-surface-variant mb-3">At least 8 characters</p>
            <div class="mb-5">
              <label class="text-sm font-semibold text-on-surface-variant ml-1" for="bd_referral_source">How did you hear about us? <span class="text-xs text-on-surface-variant font-normal">(optional)</span></label>
              <select id="bd_referral_source" name="referral_source" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 mt-1.5">
                <option value="">Select...</option>
                <option value="instagram">Instagram</option>
                <option value="tiktok">TikTok</option>
                <option value="google">Google Search</option>
                <option value="friend">Friend / Referral</option>
                <option value="convention">Tattoo Convention</option>
                <option value="blog">Blog / Article</option>
                <option value="other">Other</option>
              </select>
            </motion>
            <button onclick="finishRegister()" class="w-full py-3.5 bg-primary text-on-primary rounded-full font-bold text-sm hover:bg-primary-container transition-colors shadow-lg shadow-primary/20 mb-4">Create Account & Continue</button>
            <div class="flex items-center gap-3 mb-4"><div class="flex-1 h-px bg-outline-variant/30"></div><span class="text-sm text-on-surface-variant">or</span><div class="flex-1 h-px bg-outline-variant/30"></motion></motion>
            <div class="space-y-2 mb-5">
              <button class="social-btn" onclick="finishRegister()"><svg class="w-5 h-5" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg> Continue with Google</button>
              <button class="social-btn" onclick="finishRegister()"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg> Continue with Apple</button>
            </motion>
            <p class="text-center text-sm text-on-surface-variant">Already have an account? <span class="auth-toggle" onclick="toggleBdAuth()">Log in</span></p>
          </motion>"""

# Read actual bdAuthCreate from file
start = c.find('<motion id="bdAuthCreate">')
if start < 0:
    start = c.find('<div id="bdAuthCreate">')
end = c.find('<div id="bdAuthLogin"', start)
if start < 0 or end < 0:
    raise SystemExit("bdAuthCreate block not found")
old_auth = c[start:end]

new_auth = f'''          <{t} id="bdAuthCreate">
            <{t} class="text-center mb-6"><span class="material-symbols-outlined text-primary text-4xl mb-2">mark_email_read</span><h2 class="text-2xl sm:text-3xl font-bold text-on-surface mb-2">Verify your email</h2><p class="text-on-surface-variant">We are sending a secure 4-digit code to your email—check your inbox (and spam). You can resend below if you need a new code.</p></{t}>
            <{t} class="mb-4 hidden">
              <label class="text-sm font-semibold text-on-surface-variant ml-1 mb-1 inline-block" for="bdOtpEmail">Email</label>
              <input type="email" id="bdOtpEmail" placeholder="you@example.com" class="w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-base text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30" readonly>
            </{t}>
            <{t} class="mb-4">
              <label class="text-sm font-semibold text-on-surface-variant ml-1 mb-1 inline-block" for="bdOtpCode">4-digit code</label>
              <input type="text" id="bdOtpCode" maxlength="4" inputmode="numeric" placeholder="1234" class="w-full border border-outline-variant/30 bg-white rounded-2xl px-6 py-4 text-lg tracking-[0.3em] text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
              <p id="bdOtpError" class="text-sm text-error mt-2 hidden">Please enter a valid 4-digit code.</p>
            </{t}>
            <p id="bdOtpStatus" class="hidden items-center gap-2 text-sm text-green-700 bg-green-50 border border-green-200 rounded-xl px-3 py-2 mb-3"></p>
            <{t} class="mb-5">
              <label class="text-sm font-semibold text-on-surface-variant ml-1" for="bd_referral_source">How did you hear about us? <span class="text-xs text-on-surface-variant font-normal">(optional)</span></label>
              <select id="bd_referral_source" name="referral_source" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 mt-1.5">
                <option value="">Select...</option>
                <option value="instagram">Instagram</option>
                <option value="tiktok">TikTok</option>
                <option value="google">Google Search</option>
                <option value="friend">Friend / Referral</option>
                <option value="convention">Tattoo Convention</option>
                <option value="blog">Blog / Article</option>
                <option value="other">Other</option>
              </select>
            </{t}>
            <{t} class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
              <button id="bdSendOtpBtn" type="button" onclick="sendBookingOtp()" class="w-full py-3.5 bg-surface-container-high text-on-surface rounded-full font-bold text-sm hover:bg-surface-container transition-colors">Resend code</button>
              <button id="bdVerifyOtpBtn" type="button" onclick="verifyBookingOtp()" class="w-full py-3.5 bg-primary text-on-primary rounded-full font-bold text-sm hover:bg-primary-container transition-colors shadow-lg shadow-primary/20">Verify & Continue</button>
            </{t}>
            <p id="bdConnectedUser" class="hidden text-center text-sm text-green-600 mb-4">Already connected user.</p>
            <p class="text-center text-sm text-on-surface-variant">Email verified once will stay connected for this booking session.</p>
          </{t}>
'''

c = c.replace(old_auth, new_auth, 1)

# Replace registration JS block
old_js = """    // ── Register ──
    function showRegScreen(idx) {
      document.querySelectorAll('#stepRegister .tf-screen').forEach(s => s.classList.remove('active','reverse'));
      currentReg = idx;
      const t = document.getElementById('reg-' + idx);
      if (t) t.classList.add('active');
      if (idx === 3) { const e = document.getElementById('bdEmail').value.trim(); document.getElementById('bdAuthEmail').textContent = e; document.getElementById('bdAuthLoginEmail').textContent = e; }
      updateTopProgress();
    }
    window.nextReg = function() {
      if (currentReg === 0 && !document.getElementById('bdName').value.trim()) { shakeInput(document.getElementById('bdName')); return; }
      if (currentReg === 1) { const e = document.getElementById('bdEmail').value.trim(); if (!e || !e.includes('@')) { shakeInput(document.getElementById('bdEmail')); return; } }
      if (currentReg === 2 && !document.getElementById('bdPhone').value.trim()) { shakeInput(document.getElementById('bdPhone')); return; }
      if (currentReg + 1 < totalRegs) showRegScreen(currentReg + 1);
    };
    window.prevReg = function() { if (currentReg <= 0) { goToStep(2, true); return; } showRegScreen(currentReg - 1); };
    window.finishRegister = function() { goToStep(4); };
    function shakeInput(el) { el.style.animation = 'none'; el.offsetHeight; el.style.animation = 'shake 0.4s ease'; el.style.borderColor = '#ba1a1a'; setTimeout(() => { el.style.borderColor = ''; el.style.animation = ''; }, 800); }
    window.toggleBdAuth = function() { document.getElementById('bdAuthCreate').classList.toggle('hidden'); document.getElementById('bdAuthLogin').classList.toggle('hidden'); };"""

new_js = open(r"c:\xampp\htdocs\inkjin\scripts\managed_registration.js", "w", encoding="utf-8")
new_js.write("")  # placeholder - embed below
new_js.close()

REG_JS = r'''
    let bookingOtpVerified = false;
    let bookingConnectedEmail = '';
    let bookingConnectedName = '';
    let bookingOtpResendRemaining = 0;
    let bookingOtpResendEmail = '';
    let bookingOtpResendTimer = null;
    const mbCsrfToken = @json(csrf_token());

    function showRegScreen(index) {
      const regs = document.querySelectorAll('#stepRegister .question-div[data-reg]');
      if (!regs.length) return;
      if (index < 0) index = 0;
      if (index >= regs.length) index = regs.length - 1;
      regs.forEach(function(el) { el.classList.remove('active', 'reverse'); });
      const target = document.querySelector('#stepRegister .question-div[data-reg="' + index + '"]');
      if (target) target.classList.add('active');
      currentReg = index;
      if (index === 3) {
        const currentEmail = String(document.getElementById('bdEmail')?.value || '').trim();
        const otpEmail = document.getElementById('bdOtpEmail');
        if (otpEmail && currentEmail && !otpEmail.value.trim()) otpEmail.value = currentEmail;
        if (typeof window.mbUpdateConnectedUi === 'function') window.mbUpdateConnectedUi();
      }
      updateTopProgress();
    }

    function clearRegError(inputId, errorId) {
      const input = document.getElementById(inputId);
      const err = document.getElementById(errorId);
      if (input) { input.classList.remove('border-error'); input.style.borderColor = ''; }
      if (err) { err.classList.add('hidden'); err.textContent = 'This field is required.'; }
    }

    function setRegError(inputId, errorId, message) {
      const input = document.getElementById(inputId);
      const err = document.getElementById(errorId);
      if (input) { input.classList.add('border-error'); input.style.borderColor = '#ba1a1a'; }
      if (err) { err.classList.remove('hidden'); err.textContent = message; }
    }

    function isValidEmail(email) {
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(email || '').trim());
    }

    function isValidPhoneWithCountryCode(phone) {
      return /^\+[0-9][0-9\s\-()]{5,}$/.test(String(phone || '').trim());
    }

    async function validateBookingEmailRole(email) {
      const res = await fetch('/api/public/check-email-availability?email=' + encodeURIComponent(email), {
        method: 'GET',
        headers: { 'Accept': 'application/json' }
      });
      if (!res.ok) throw new Error('Unable to validate email right now. Please try again.');
      const data = await res.json();
      if (typeof data.allowed === 'boolean') return data.allowed;
      if (!data.exists) return true;
      return !!data.is_user;
    }

    window.mbUpdateConnectedUi = function() {
      const connected = document.getElementById('bdConnectedUser');
      const status = document.getElementById('bdOtpStatus');
      const codeWrap = document.getElementById('bdOtpCode')?.closest('.mb-4') || document.getElementById('bdOtpCode')?.parentElement;
      const sendBtn = document.getElementById('bdSendOtpBtn');
      const verifyBtn = document.getElementById('bdVerifyOtpBtn');
      if (bookingOtpVerified) {
        const label = bookingConnectedName ? bookingConnectedName + ' (' + bookingConnectedEmail + ')' : bookingConnectedEmail;
        if (connected) { connected.classList.remove('hidden'); connected.textContent = 'Already connected user: ' + label; }
        if (status) {
          status.classList.remove('hidden');
          status.classList.add('flex');
          status.innerHTML = '<span class="material-symbols-outlined text-[18px] text-green-600">verified</span><span>Email already verified for this booking.</span>';
        }
        if (codeWrap) codeWrap.classList.add('hidden');
        if (sendBtn) sendBtn.classList.add('hidden');
        if (verifyBtn) { verifyBtn.textContent = 'Continue'; verifyBtn.disabled = false; }
      } else {
        if (connected) { connected.classList.add('hidden'); connected.textContent = 'Already connected user.'; }
        if (codeWrap) codeWrap.classList.remove('hidden');
        if (sendBtn) sendBtn.classList.remove('hidden');
        if (verifyBtn) verifyBtn.textContent = 'Verify & Continue';
      }
    };

    function formatSecondsToMMSS(seconds) {
      const s = Math.max(0, parseInt(seconds || 0, 10) || 0);
      const mm = String(Math.floor(s / 60)).padStart(2, '0');
      const ss = String(s % 60).padStart(2, '0');
      return mm + ':' + ss;
    }

    function applyOtpResendUi() {
      const sendBtn = document.getElementById('bdSendOtpBtn');
      if (!sendBtn) return;
      const currentEmail = String(document.getElementById('bdOtpEmail')?.value || '').trim().toLowerCase();
      if (bookingOtpResendRemaining > 0 && bookingOtpResendEmail && bookingOtpResendEmail === currentEmail) {
        sendBtn.disabled = true;
        sendBtn.textContent = 'Resend in ' + formatSecondsToMMSS(bookingOtpResendRemaining);
      } else {
        sendBtn.disabled = false;
        sendBtn.textContent = 'Resend code';
      }
    }

    function startOtpResendCountdown(seconds) {
      bookingOtpResendRemaining = Math.max(0, parseInt(seconds || 0, 10) || 0);
      if (bookingOtpResendTimer) { clearInterval(bookingOtpResendTimer); bookingOtpResendTimer = null; }
      applyOtpResendUi();
      if (bookingOtpResendRemaining <= 0) return;
      bookingOtpResendTimer = setInterval(function() {
        bookingOtpResendRemaining = Math.max(0, bookingOtpResendRemaining - 1);
        applyOtpResendUi();
        if (bookingOtpResendRemaining <= 0 && bookingOtpResendTimer) {
          clearInterval(bookingOtpResendTimer);
          bookingOtpResendTimer = null;
        }
      }, 1000);
    }

    window.sendBookingOtp = async function() {
      const email = String(document.getElementById('bdOtpEmail')?.value || '').trim();
      const otpError = document.getElementById('bdOtpError');
      const otpStatus = document.getElementById('bdOtpStatus');
      const sendBtn = document.getElementById('bdSendOtpBtn');
      if (otpError) otpError.classList.add('hidden');
      if (bookingOtpResendRemaining > 0 && bookingOtpResendEmail === email.toLowerCase()) {
        if (otpError) { otpError.classList.remove('hidden'); otpError.textContent = 'Please wait ' + formatSecondsToMMSS(bookingOtpResendRemaining) + ' before requesting another code.'; }
        return;
      }
      if (!isValidEmail(email)) {
        if (otpError) { otpError.classList.remove('hidden'); otpError.textContent = 'Please enter a valid email first.'; }
        return;
      }
      if (sendBtn) { sendBtn.disabled = true; sendBtn.textContent = 'Sending...'; }
      try {
        const res = await fetch('/api/public/send-booking-otp', {
          method: 'POST',
          headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': mbCsrfToken },
          body: JSON.stringify({ email: email })
        });
        const data = await res.json();
        if (!res.ok) {
          if (data && data.resend_available_in_seconds) {
            bookingOtpResendEmail = email.toLowerCase();
            startOtpResendCountdown(data.resend_available_in_seconds);
          }
          throw new Error((data && data.message) || 'Could not send verification code.');
        }
        if (otpStatus) {
          otpStatus.classList.remove('hidden');
          otpStatus.classList.add('flex');
          otpStatus.innerHTML = '<span class="material-symbols-outlined text-[18px] text-green-600">mark_email_read</span><span>4-digit code sent to your email.</span>';
        }
        bookingOtpResendEmail = email.toLowerCase();
        startOtpResendCountdown(data && data.resend_available_in_seconds ? data.resend_available_in_seconds : 60);
      } catch (err) {
        if (otpError) { otpError.classList.remove('hidden'); otpError.textContent = err.message || 'Could not send verification code.'; }
      } finally {
        if (bookingOtpResendRemaining <= 0 && sendBtn) { sendBtn.disabled = false; sendBtn.textContent = 'Resend code'; }
        else applyOtpResendUi();
      }
    };

    window.verifyBookingOtp = async function() {
      if (bookingOtpVerified) { window.finishRegister(); return; }
      const email = String(document.getElementById('bdOtpEmail')?.value || '').trim();
      const code = String(document.getElementById('bdOtpCode')?.value || '').trim();
      const name = String(document.getElementById('bdName')?.value || '').trim();
      const otpError = document.getElementById('bdOtpError');
      const verifyBtn = document.getElementById('bdVerifyOtpBtn');
      if (otpError) otpError.classList.add('hidden');
      if (!isValidEmail(email)) {
        if (otpError) { otpError.classList.remove('hidden'); otpError.textContent = 'Please enter a valid email.'; }
        return;
      }
      if (!/^\d{4}$/.test(code)) {
        if (otpError) { otpError.classList.remove('hidden'); otpError.textContent = 'Please enter a valid 4-digit code.'; }
        return;
      }
      if (verifyBtn) { verifyBtn.disabled = true; verifyBtn.textContent = 'Verifying...'; }
      try {
        const res = await fetch('/api/public/verify-booking-otp', {
          method: 'POST',
          headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': mbCsrfToken },
          body: JSON.stringify({ email: email, code: code, name: name })
        });
        const data = await res.json();
        if (!res.ok || !data || !data.verified) throw new Error((data && data.message) || 'Verification failed.');
        bookingOtpVerified = true;
        bookingConnectedEmail = (data.user && data.user.email) ? data.user.email : email;
        bookingConnectedName = (data.user && data.user.name) ? data.user.name : '';
        const bdEmail = document.getElementById('bdEmail');
        if (bdEmail) bdEmail.value = bookingConnectedEmail;
        window.mbUpdateConnectedUi();
        window.finishRegister();
      } catch (err) {
        if (otpError) { otpError.classList.remove('hidden'); otpError.textContent = err.message || 'Verification failed.'; }
      } finally {
        if (verifyBtn) { verifyBtn.disabled = false; verifyBtn.textContent = 'Verify & Continue'; }
      }
    };

    window.nextReg = async function() {
      const active = document.querySelector('#stepRegister .question-div.active[data-reg]');
      const activeIndex = active ? parseInt(active.getAttribute('data-reg'), 10) : currentReg;
      if (!isNaN(activeIndex)) currentReg = activeIndex;
      clearRegError('bdName', 'bdNameError');
      clearRegError('bdEmail', 'bdEmailError');
      clearRegError('bdPhone', 'bdPhoneError');
      if (currentReg === 0) {
        const nameVal = String(document.getElementById('bdName')?.value || '').trim();
        if (!nameVal) { setRegError('bdName', 'bdNameError', 'This field is required.'); return; }
      }
      if (currentReg === 1) {
        const emailVal = String(document.getElementById('bdEmail')?.value || '').trim();
        if (!emailVal) { setRegError('bdEmail', 'bdEmailError', 'This field is required.'); return; }
        if (!isValidEmail(emailVal)) { setRegError('bdEmail', 'bdEmailError', 'Please enter a valid email address.'); return; }
        try {
          const allowed = await validateBookingEmailRole(emailVal);
          if (!allowed) { setRegError('bdEmail', 'bdEmailError', 'Please use another email.'); return; }
        } catch (err) {
          setRegError('bdEmail', 'bdEmailError', err.message || 'Unable to validate email right now. Please try again.');
          return;
        }
      }
      if (currentReg === 2) {
        const phoneVal = String(document.getElementById('bdPhone')?.value || '').trim();
        if (!phoneVal) { setRegError('bdPhone', 'bdPhoneError', 'This field is required.'); return; }
        if (!isValidPhoneWithCountryCode(phoneVal)) {
          setRegError('bdPhone', 'bdPhoneError', 'Phone must start with country code, e.g. +30 694 123 4567.');
          return;
        }
      }
      const regs = document.querySelectorAll('#stepRegister .question-div[data-reg]');
      const nextIndex = currentReg + 1;
      if (nextIndex >= regs.length) { goToStep(4); return; }
      showRegScreen(nextIndex);
      if (nextIndex === 3 && !bookingOtpVerified) await window.sendBookingOtp();
    };

    window.prevReg = function() {
      const active = document.querySelector('#stepRegister .question-div.active[data-reg]');
      const activeIndex = active ? parseInt(active.getAttribute('data-reg'), 10) : currentReg;
      if (!isNaN(activeIndex)) currentReg = activeIndex;
      if (currentReg <= 0) { goToStep(2, true); return; }
      showRegScreen(currentReg - 1);
    };

    window.finishRegister = function() {
      if (!bookingOtpVerified) {
        const otpError = document.getElementById('bdOtpError');
        if (otpError) { otpError.classList.remove('hidden'); otpError.textContent = 'Please verify your email to continue.'; }
        return;
      }
      goToStep(4);
    };

    window.toggleBdAuth = function() {
      document.getElementById('bdAuthCreate')?.classList.toggle('hidden');
      document.getElementById('bdAuthLogin')?.classList.toggle('hidden');
    };

    ['bdName', 'bdEmail', 'bdPhone'].forEach(function(id) {
      const el = document.getElementById(id);
      if (!el) return;
      el.addEventListener('input', function() {
        clearRegError(id, id + 'Error');
      });
    });
    const otpEmailEl = document.getElementById('bdOtpEmail');
    if (otpEmailEl) {
      otpEmailEl.addEventListener('input', function() {
        const otpError = document.getElementById('bdOtpError');
        const otpStatus = document.getElementById('bdOtpStatus');
        if (otpError) otpError.classList.add('hidden');
        if (otpStatus) { otpStatus.textContent = ''; otpStatus.classList.add('hidden'); otpStatus.classList.remove('flex'); }
        applyOtpResendUi();
        if (String(this.value || '').trim().toLowerCase() !== String(bookingConnectedEmail || '').toLowerCase()) {
          bookingOtpVerified = false;
          bookingConnectedEmail = '';
          bookingConnectedName = '';
        }
        window.mbUpdateConnectedUi();
      });
    }
    const otpCodeEl = document.getElementById('bdOtpCode');
    if (otpCodeEl) {
      otpCodeEl.addEventListener('input', function() {
        this.value = String(this.value || '').replace(/\D/g, '').slice(0, 4);
        const otpError = document.getElementById('bdOtpError');
        if (otpError) otpError.classList.add('hidden');
      });
    }
'''

# Fix blade syntax in JS - use actual php injection
REG_JS = REG_JS.replace('@json(csrf_token())', '{{ csrf_inject }}')
# Actually embed via python
import re
csrf_line = 'const mbCsrfToken = ' + repr(
    __import__('json').dumps(
        # read from file - blade will be processed by laravel; we need literal in blade file
        None
    )
)

# Simpler: use same as book - const mbCsrfToken = @json(csrf_token()); in the blade file directly
REG_JS_BLADE = REG_JS.replace("const mbCsrfToken = @json(csrf_token());", "const mbCsrfToken = @json(csrf_token());")

if old_js not in c:
    raise SystemExit("old registration JS not found")

# Write REG_JS to separate file and include - actually splice with blade token
blade_reg = REG_JS.replace(
    "const mbCsrfToken = @json(csrf_token());",
    "'__CSRF_PLACEHOLDER__'"
)
# Read csrf from existing file
import re as re_mod
m = re_mod.search(r"var csrfToken = (.+?);\n", c)
if not m:
    m = re_mod.search(r"const csrfToken = (.+?);\n", c)
csrf_expr = m.group(1) if m else "@json(csrf_token())"
blade_reg = REG_JS.replace("const mbCsrfToken = @json(csrf_token());", "const mbCsrfToken = " + csrf_expr + ";")

c = c.replace(old_js, blade_reg, 1)

# buildManagedReview email
c = c.replace(
    "const email = document.getElementById('bdEmail').value.trim() || '—';",
    "const email = (bookingConnectedEmail || document.getElementById('bdEmail').value.trim()) || '—';",
    1,
)

# Init otp email on load
init_snip = "if (consultationRequired) configureMcConsultTypeCards();"
init_new = """const _bdOtp = document.getElementById('bdOtpEmail');
    const _bdEm = document.getElementById('bdEmail');
    if (_bdOtp && _bdEm) {
      _bdOtp.value = String(_bdEm.value || '').trim();
      if (typeof window.mbUpdateConnectedUi === 'function') window.mbUpdateConnectedUi();
    }

    if (consultationRequired) configureMcConsultTypeCards();"""
if init_snip in c:
    c = c.replace(init_snip, init_new, 1)

# goToStep showReg uses question-div
c = c.replace(
    "document.querySelectorAll('#stepRegister .tf-screen')",
    "document.querySelectorAll('#stepRegister .question-div[data-reg]')",
)

with open(path, "w", encoding="utf-8", newline="\n") as f:
    f.write(c)
print("OK")
