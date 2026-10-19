<?php
require_once __DIR__ . '/db.php';
penguins_secure_session_start();
penguins_security_headers();
// If already logged in, no reason to see the auth screen again
if (!empty($_SESSION['user_id'])) { header('Location: dashboard.php'); exit; }
$csrfToken = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="description" content="Penguins brings client management, training plans, scheduling and coaching business tools into one workspace.">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Penguins — Log in or sign up</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        space:  '#05050A',
        panel:  '#0B0B14',
        line:   '#1E1E2E',
        accent: '#6C5CE7',
        nebula: '#8B5CF6',
        gold:   '#F5B942',
        ink:    '#F5F5F7',
        dim:    '#9494A6',
        danger: '#F55151'
      },
      fontFamily: {
        display: ['Space Grotesk','sans-serif'],
        sans: ['Inter','sans-serif']
      }
    }
  }
}
</script>
<style>
  body { background:#05050A; font-family:'Inter',sans-serif; }
  .display { font-family:'Space Grotesk',sans-serif; }

  #stars { position:fixed; inset:0; z-index:0; overflow:hidden; pointer-events:none; }
  .star { position:absolute; background:#fff; border-radius:50%; opacity:0.5; animation:twinkle linear infinite; }
  @keyframes twinkle { 0%,100%{ opacity:.15; } 50%{ opacity:.85; } }

  .glow { position:absolute; border-radius:50%; filter:blur(90px); pointer-events:none; }
  .glow-1 { width:560px; height:560px; background:radial-gradient(circle, rgba(108,92,231,0.35), transparent 70%); top:-200px; left:-160px; }
  .glow-2 { width:480px; height:480px; background:radial-gradient(circle, rgba(245,185,66,0.14), transparent 70%); bottom:-180px; right:-160px; }

  .glass { background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); backdrop-filter:blur(14px); }
  .glass-strong { background:rgba(255,255,255,0.045); border:1px solid rgba(255,255,255,0.1); backdrop-filter:blur(20px); }

  .grad-text { background:linear-gradient(120deg,#F5F5F7 20%,#B9AEFF 55%,#F5B942 85%); -webkit-background-clip:text; background-clip:text; color:transparent; }

  .btn-primary { background:linear-gradient(135deg,#6C5CE7,#8B5CF6); box-shadow:0 0 0 1px rgba(255,255,255,0.08) inset, 0 12px 30px -10px rgba(108,92,231,0.55); }
  .btn-primary:hover { box-shadow:0 0 0 1px rgba(255,255,255,0.12) inset, 0 16px 38px -8px rgba(108,92,231,0.7); }
  .btn-primary:disabled { opacity:.55; cursor:not-allowed; box-shadow:none; }
  .btn-metal:disabled { opacity:.55; cursor:not-allowed; }
  .btn-metal:disabled::before { display:none; }

  .field { background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.1); color:#F5F5F7; transition:border-color .15s ease, box-shadow .15s ease; }
  .field:focus { outline:none; border-color:#8B5CF6; box-shadow:0 0 0 3px rgba(139,92,246,0.18); }
  .field.field-error { border-color:#F55151; }
  .field.field-error:focus { box-shadow:0 0 0 3px rgba(245,81,81,0.18); }

  .tab-btn { transition: color .15s ease, background .15s ease; position:relative; z-index:1; }
  .tab-btn.active { background:linear-gradient(160deg,#3a3a44,#1c1c22); color:#fff; box-shadow:inset 0 1px 0 rgba(255,255,255,0.15), 0 4px 12px -4px rgba(0,0,0,0.5); border:1px solid rgba(255,255,255,0.1); }

  .strength-bar { height:4px; border-radius:999px; background:#1E1E2E; overflow:hidden; }
  .strength-fill { height:100%; border-radius:999px; transition:width .2s ease, background .2s ease; }

  /* ===== Metallic surfaces (shared with index.html) ===== */
  .metal-card { position:relative; overflow:hidden; background:linear-gradient(150deg,#1c1c22 0%,#0a0a0c 45%,#17171c 100%); border:1px solid rgba(255,255,255,0.1); box-shadow:inset 0 1px 0 rgba(255,255,255,0.08), inset 0 -1px 0 rgba(0,0,0,0.5), 0 20px 50px -20px rgba(0,0,0,0.8); }
  .metal-icon-plate { background:linear-gradient(150deg,#2a2a32,#111114); border:1px solid rgba(255,255,255,0.12); box-shadow:inset 0 1px 0 rgba(255,255,255,0.1); }
  .btn-metal { position:relative; overflow:hidden; background:linear-gradient(160deg,#3a3a44 0%,#1c1c22 45%,#2a2a32 100%); border:1px solid rgba(255,255,255,0.16); box-shadow:inset 0 1px 0 rgba(255,255,255,0.18), inset 0 -1px 0 rgba(0,0,0,0.5), 0 10px 26px -12px rgba(0,0,0,0.7); transition:border-color .2s ease; }
  .btn-metal::before { content:''; position:absolute; top:0; left:-160%; width:55%; height:100%; background:linear-gradient(120deg, transparent, rgba(255,255,255,0.22), transparent); transform:skewX(-20deg); transition:left .7s ease; }
  .btn-metal:hover::before { left:160%; }
  .btn-metal:hover { border-color:rgba(139,92,246,0.5); }
  .tab-wrap { background:linear-gradient(180deg,rgba(255,255,255,0.02),rgba(0,0,0,0.2)); border:1px solid rgba(255,255,255,0.06); border-radius:16px; }

  ::selection { background:#6C5CE7; color:#fff; }

  @media (prefers-reduced-motion: reduce) {
    .star { animation:none; opacity:.4; }
  }
  @media (max-width: 767px) {
    #form-login .field, #form-signup .field, #form-login button, #form-signup button { font-size:16px; } /* prevents iOS auto-zoom on focus */
  }
</style>
<style>
  :focus-visible { outline: 3px solid #a79bff; outline-offset: 4px; }
  @media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { animation: none !important; scroll-behavior: auto !important; transition: none !important; }
  }
</style>
</head>
<body class="text-ink antialiased relative min-h-screen" data-csrf="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">

<div id="stars"></div>
<div class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
  <div class="glow glow-1"></div>
  <div class="glow glow-2"></div>
</div>

<div class="relative z-10 min-h-screen flex flex-col">

  <header class="max-w-7xl mx-auto w-full px-6 md:px-8 pt-6">
    <a href="index.html" class="display text-lg font-bold text-white inline-flex items-center gap-2">
      Penguins <span class="text-base">🐧</span><span class="text-nebula">.</span>
    </a>
  </header>

  <main class="flex-1 flex items-center justify-center px-6 py-12">
    <div class="w-full max-w-md">

      <div class="text-center mb-8">
        <h1 class="display text-3xl font-bold text-white">Welcome to <span class="grad-text">Penguins</span></h1>
        <p class="text-dim text-sm mt-2">Run your coaching business from one command center.</p>
      </div>

      <div class="metal-card rounded-2xl p-2">
        <div class="flex gap-1 mb-2">
          <button id="tab-login" onclick="setMode('login')" class="tab-btn active flex-1 py-2.5 rounded-xl text-sm font-medium text-dim">Log in</button>
          <button id="tab-signup" onclick="setMode('signup')" class="tab-btn flex-1 py-2.5 rounded-xl text-sm font-medium text-dim">Sign up</button>
        </div>

        <div class="p-6 pt-4">

          <!-- ============ LOGIN FORM ============ -->
          <form id="form-login" class="space-y-4" novalidate>
            <div>
              <label class="text-xs text-dim mb-1.5 block">Email</label>
              <input type="email" id="login-email" name="email" autocomplete="email" placeholder="you@example.com" class="field w-full rounded-xl px-4 py-3 text-sm">
              <p class="error-text hidden text-danger text-xs mt-1.5"></p>
            </div>
            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label class="text-xs text-dim">Password</label>
                <a href="#" onclick="return false" class="text-xs text-nebula hover:text-white transition">Forgot password?</a>
              </div>
              <div class="relative">
                <input type="password" id="login-password" name="password" autocomplete="current-password" placeholder="••••••••" class="field w-full rounded-xl px-4 py-3 text-sm pr-11">
                <button type="button" onclick="toggleVisibility('login-password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-dim hover:text-white text-xs">Show</button>
              </div>
              <p class="error-text hidden text-danger text-xs mt-1.5"></p>
            </div>
            <label class="flex items-center gap-2 text-xs text-dim">
              <input type="checkbox" id="login-remember" class="accent-accent rounded"> Remember me on this device
            </label>
            <button type="submit" id="login-submit" class="btn-metal w-full text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2">
              <span id="login-submit-label">Log in</span>
            </button>
            <p id="login-form-error" class="hidden text-danger text-xs text-center"></p>
          </form>

          <!-- ============ SIGNUP FORM ============ -->
          <form id="form-signup" class="space-y-4 hidden" novalidate>
            <div>
              <label class="text-xs text-dim mb-1.5 block">Full name</label>
              <input type="text" id="signup-name" name="name" autocomplete="name" placeholder="Jordan Diaz" class="field w-full rounded-xl px-4 py-3 text-sm">
              <p class="error-text hidden text-danger text-xs mt-1.5"></p>
            </div>
            <div>
              <label class="text-xs text-dim mb-1.5 block">Email</label>
              <input type="email" id="signup-email" name="email" autocomplete="email" placeholder="you@example.com" class="field w-full rounded-xl px-4 py-3 text-sm">
              <p class="error-text hidden text-danger text-xs mt-1.5"></p>
            </div>
            <div>
              <label class="text-xs text-dim mb-1.5 block">Coaching focus</label>
              <select id="signup-niche" class="field w-full rounded-xl px-4 py-3 text-sm">
                <option>Fitness / Strength</option>
                <option>Nutrition</option>
                <option>Life &amp; Mindset</option>
                <option>Business / Executive</option>
                <option>Other</option>
              </select>
            </div>
            <div>
              <label class="text-xs text-dim mb-1.5 block">Password</label>
              <div class="relative">
                <input type="password" id="signup-password" name="password" autocomplete="new-password" placeholder="At least 10 characters" class="field w-full rounded-xl px-4 py-3 text-sm pr-11">
                <button type="button" onclick="toggleVisibility('signup-password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-dim hover:text-white text-xs">Show</button>
              </div>
              <div class="strength-bar mt-2"><div id="strength-fill" class="strength-fill" style="width:0%"></div></div>
              <p id="strength-label" class="text-dim text-[11px] mt-1">Password strength</p>
              <p class="error-text hidden text-danger text-xs mt-1.5"></p>
            </div>
            <div>
              <label class="text-xs text-dim mb-1.5 block">Confirm password</label>
              <input type="password" id="signup-confirm" name="confirm" autocomplete="new-password" placeholder="••••••••" class="field w-full rounded-xl px-4 py-3 text-sm">
              <p class="error-text hidden text-danger text-xs mt-1.5"></p>
            </div>
            <label class="flex items-start gap-2 text-xs text-dim">
              <input type="checkbox" id="signup-terms" class="accent-accent rounded mt-0.5">
              <span>I agree to the <a href="#" onclick="return false" class="text-nebula hover:text-white transition">Terms</a> and <a href="#" onclick="return false" class="text-nebula hover:text-white transition">Privacy Policy</a></span>
            </label>
            <p class="error-text hidden text-danger text-xs -mt-2" id="terms-error"></p>
            <button type="submit" id="signup-submit" class="btn-metal w-full text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2">
              <span id="signup-submit-label">Create account</span>
            </button>
            <p id="signup-form-error" class="hidden text-danger text-xs text-center"></p>
          </form>

          <div class="flex items-center gap-3 my-5">
            <div class="flex-1 h-px bg-line"></div>
            <span class="text-dim text-[11px] uppercase tracking-wide">or</span>
            <div class="flex-1 h-px bg-line"></div>
          </div>

          <button type="button" onclick="toast('Google sign-in isn\'t wired up in this demo yet.')" class="metal-icon-plate w-full text-white text-sm font-medium py-3 rounded-xl hover:border-white/20 transition flex items-center justify-center gap-2">
            <span>Continue with Google</span>
          </button>
        </div>
      </div>

      <p class="text-center text-dim text-xs mt-6">
        By continuing you agree to Penguins'
        <a href="#" onclick="return false" class="text-nebula hover:text-white transition">Terms of Service</a>.
      </p>
    </div>
  </main>
</div>

<div id="toastWrap" class="fixed bottom-6 right-6 z-50 flex flex-col gap-2"></div>

<script>
// ==========================================
// Starfield (visual only)
// ==========================================
(function initStars() {
  const wrap = document.getElementById('stars');
  const count = window.innerWidth < 640 ? 50 : 100;
  for (let i = 0; i < count; i++) {
    const s = document.createElement('div');
    s.className = 'star';
    const size = Math.random() * 2 + 0.6;
    s.style.width = size + 'px';
    s.style.height = size + 'px';
    s.style.left = Math.random() * 100 + '%';
    s.style.top = Math.random() * 100 + '%';
    s.style.animationDuration = (Math.random() * 4 + 2.5) + 's';
    s.style.animationDelay = (Math.random() * 4) + 's';
    wrap.appendChild(s);
  }
})();

// ==========================================
// Toast
// ==========================================
function toast(msg) {
  const wrap = document.getElementById('toastWrap');
  const el = document.createElement('div');
  el.className = 'glass-strong text-white text-sm px-4 py-3 rounded-xl shadow-lg';
  el.style.animation = 'fadeSlideIn .2s ease';
  el.innerText = msg;
  wrap.appendChild(el);
  setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity .3s'; setTimeout(() => el.remove(), 300); }, 3200);
}

// ==========================================
// Tab switching
// ==========================================
function setMode(mode) {
  const isLogin = mode === 'login';
  document.getElementById('tab-login').classList.toggle('active', isLogin);
  document.getElementById('tab-signup').classList.toggle('active', !isLogin);
  document.getElementById('form-login').classList.toggle('hidden', !isLogin);
  document.getElementById('form-signup').classList.toggle('hidden', isLogin);
}

// Open on the right tab based on ?mode=signup in the link from index.html
(function initModeFromURL() {
  const params = new URLSearchParams(window.location.search);
  if (params.get('mode') === 'signup') setMode('signup');
})();

// ==========================================
// Show/hide password
// ==========================================
function toggleVisibility(inputId, btn) {
  const input = document.getElementById(inputId);
  const isPw = input.type === 'password';
  input.type = isPw ? 'text' : 'password';
  btn.innerText = isPw ? 'Hide' : 'Show';
}

// ==========================================
// Field error helpers
// ==========================================
function setFieldError(input, message) {
  const errEl = input.parentElement.querySelector('.error-text') || input.closest('div').querySelector('.error-text');
  input.classList.add('field-error');
  if (errEl) { errEl.innerText = message; errEl.classList.remove('hidden'); }
}
function clearFieldError(input) {
  const wrapper = input.parentElement.classList.contains('relative') ? input.parentElement.parentElement : input.parentElement;
  const errEl = wrapper.querySelector('.error-text');
  input.classList.remove('field-error');
  if (errEl) errEl.classList.add('hidden');
}
function isValidEmail(v) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
}

// ==========================================
// LOGIN
// ==========================================
const loginEmail = document.getElementById('login-email');
const loginPassword = document.getElementById('login-password');
[loginEmail, loginPassword].forEach(el => el.addEventListener('input', () => clearFieldError(el)));

document.getElementById('form-login').addEventListener('submit', async e => {
  e.preventDefault();
  let valid = true;
  document.getElementById('login-form-error').classList.add('hidden');

  if (!loginEmail.value.trim()) { setFieldError(loginEmail, 'Enter your email.'); valid = false; }
  else if (!isValidEmail(loginEmail.value.trim())) { setFieldError(loginEmail, 'That email doesn\'t look right.'); valid = false; }

  if (!loginPassword.value) { setFieldError(loginPassword, 'Enter your password.'); valid = false; }

  if (!valid) return;

  const btn = document.getElementById('login-submit');
  const label = document.getElementById('login-submit-label');
  btn.disabled = true;
  label.innerText = 'Logging in…';

  try {
    const res = await fetch('login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.body.dataset.csrf },
      body: JSON.stringify({ email: loginEmail.value.trim(), password: loginPassword.value })
    });
    const data = await res.json();

    if (!data.ok) {
      btn.disabled = false;
      label.innerText = 'Log in';
      const errEl = document.getElementById('login-form-error');
      errEl.innerText = data.error || 'Something went wrong. Try again.';
      errEl.classList.remove('hidden');
      if (data.field === 'email') setFieldError(loginEmail, data.error);
      if (data.field === 'password') setFieldError(loginPassword, data.error);
      return;
    }

    label.innerText = 'Success — redirecting…';
    toast('✅ Welcome back, ' + data.name + '.');
    setTimeout(() => { window.location.href = 'dashboard.php'; }, 700);
  } catch (err) {
    btn.disabled = false;
    label.innerText = 'Log in';
    const errEl = document.getElementById('login-form-error');
    errEl.innerText = 'Could not reach the server. Is it running?';
    errEl.classList.remove('hidden');
  }
});

// ==========================================
// SIGNUP
// ==========================================
const signupName = document.getElementById('signup-name');
const signupEmail = document.getElementById('signup-email');
const signupPassword = document.getElementById('signup-password');
const signupConfirm = document.getElementById('signup-confirm');
const signupTerms = document.getElementById('signup-terms');

[signupName, signupEmail, signupPassword, signupConfirm].forEach(el => el.addEventListener('input', () => clearFieldError(el)));

function passwordStrength(pw) {
  let score = 0;
  if (pw.length >= 8) score++;
  if (pw.length >= 12) score++;
  if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) score++;
  if (/[0-9]/.test(pw)) score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;
  return Math.min(score, 4);
}
signupPassword.addEventListener('input', () => {
  const score = passwordStrength(signupPassword.value);
  const fill = document.getElementById('strength-fill');
  const label = document.getElementById('strength-label');
  const levels = [
    { w: '0%',   c: '#1E1E2E', t: 'Password strength' },
    { w: '25%',  c: '#F55151', t: 'Weak' },
    { w: '50%',  c: '#F5B942', t: 'Okay' },
    { w: '75%',  c: '#8B5CF6', t: 'Good' },
    { w: '100%', c: '#3BE07A', t: 'Strong' }
  ];
  const lvl = levels[signupPassword.value ? score : 0];
  fill.style.width = lvl.w;
  fill.style.background = lvl.c;
  label.innerText = lvl.t;
  label.style.color = signupPassword.value ? lvl.c : '';
});

document.getElementById('form-signup').addEventListener('submit', async e => {
  e.preventDefault();
  let valid = true;
  document.getElementById('signup-form-error').classList.add('hidden');
  document.getElementById('terms-error').classList.add('hidden');

  if (!signupName.value.trim()) { setFieldError(signupName, 'Enter your name.'); valid = false; }

  if (!signupEmail.value.trim()) { setFieldError(signupEmail, 'Enter your email.'); valid = false; }
  else if (!isValidEmail(signupEmail.value.trim())) { setFieldError(signupEmail, 'That email doesn\'t look right.'); valid = false; }

  if (!signupPassword.value) { setFieldError(signupPassword, 'Create a password.'); valid = false; }
  else if (signupPassword.value.length < 10) { setFieldError(signupPassword, 'Use at least 10 characters.'); valid = false; }

  if (!signupConfirm.value) { setFieldError(signupConfirm, 'Confirm your password.'); valid = false; }
  else if (signupConfirm.value !== signupPassword.value) { setFieldError(signupConfirm, 'Passwords don\'t match.'); valid = false; }

  if (!signupTerms.checked) {
    const t = document.getElementById('terms-error');
    t.innerText = 'You need to agree to the Terms to continue.';
    t.classList.remove('hidden');
    valid = false;
  }

  if (!valid) return;

  const btn = document.getElementById('signup-submit');
  const label = document.getElementById('signup-submit-label');
  btn.disabled = true;
  label.innerText = 'Creating account…';

  try {
    const res = await fetch('register.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.body.dataset.csrf },
      body: JSON.stringify({
        name: signupName.value.trim(),
        email: signupEmail.value.trim(),
        niche: document.getElementById('signup-niche').value,
        password: signupPassword.value
      })
    });
    const data = await res.json();

    if (!data.ok) {
      btn.disabled = false;
      label.innerText = 'Create account';
      const errEl = document.getElementById('signup-form-error');
      errEl.innerText = data.error || 'Something went wrong. Try again.';
      errEl.classList.remove('hidden');
      if (data.field === 'name') setFieldError(signupName, data.error);
      if (data.field === 'email') setFieldError(signupEmail, data.error);
      if (data.field === 'password') setFieldError(signupPassword, data.error);
      return;
    }

    label.innerText = 'Success — redirecting…';
    toast('✅ Account created — welcome, ' + data.name + '.');
    setTimeout(() => { window.location.href = 'dashboard.php'; }, 700);
  } catch (err) {
    btn.disabled = false;
    label.innerText = 'Create account';
    const errEl = document.getElementById('signup-form-error');
    errEl.innerText = 'Could not reach the server. Is it running?';
    errEl.classList.remove('hidden');
  }
});
</script>

<style>
  @keyframes fadeSlideIn { from{ opacity:0; transform:translateX(16px);} to{ opacity:1; transform:translateX(0);} }
</style>

</body>
</html>