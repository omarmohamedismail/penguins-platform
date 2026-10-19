<?php
require_once __DIR__ . '/db.php';
penguins_secure_session_start();
penguins_security_headers();
if (empty($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}
$csrfToken = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="description" content="Penguins brings client management, training plans, scheduling and coaching business tools into one workspace.">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Penguins | Coach Platform</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&family=Noto+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        bg:     '#000000',
        card:   '#0D0D0D',
        card2:  '#131313',
        border: '#1E1E1E',
        accent: '#6C5CE7',
        gold:   '#F5B942',
        text:   '#F5F5F5',
        muted:  '#8A8A8E'
      },
      fontFamily: {
        display: ['Space Grotesk','sans-serif'],
        sans: ['Inter','sans-serif'],
        mono: ['JetBrains Mono','monospace']
      }
    }
  }
}
</script>
<style>
  :root {
    --accent-color:#6C5CE7; --gold-color:#F5B942;
    --bg-c:#000000; --card-c:#0D0D0D; --card2-c:#131313; --border-c:#1E1E1E; --text-c:#F5F5F5; --muted-c:#8A8A8E;
  }
  html[data-theme="light"] {
    --bg-c:#FAFAFA; --card-c:#FFFFFF; --card2-c:#F2F2F4; --border-c:#E3E3E7; --text-c:#111114; --muted-c:#6B6B72;
  }
  body { background:var(--bg-c); color:var(--text-c); font-family:'Inter',sans-serif; transition:background .2s ease, color .2s ease; }
  .bg-black{background-color:var(--bg-c)!important;}
  .bg-card{background-color:var(--card-c)!important;}
  .bg-card2{background-color:var(--card2-c)!important;}
  .border-border{border-color:var(--border-c)!important;}
  .text-white{color:var(--text-c)!important;}
  .text-text{color:var(--text-c)!important;}
  .text-muted{color:var(--muted-c)!important;}
  .hover\:text-white:hover{color:var(--text-c)!important;}
  .bg-accent{background-color:var(--accent-color)!important;}
  .text-accent{color:var(--accent-color)!important;}
  .border-accent{border-color:var(--accent-color)!important;}
  .accent-accent{accent-color:var(--accent-color)!important;}
  .bg-accent\/15{background-color:color-mix(in srgb, var(--accent-color) 15%, transparent)!important;}
  .bg-accent\/20{background-color:color-mix(in srgb, var(--accent-color) 20%, transparent)!important;}
  .border-accent\/40{border-color:color-mix(in srgb, var(--accent-color) 40%, transparent)!important;}
  .hover\:bg-accent\/25:hover{background-color:color-mix(in srgb, var(--accent-color) 25%, transparent)!important;}
  .hover\:border-accent\/50:hover{border-color:color-mix(in srgb, var(--accent-color) 50%, transparent)!important;}
  .hover\:text-accent:hover{color:var(--accent-color)!important;}
  .hover\:bg-indigo-500:hover{background-color:color-mix(in srgb, var(--accent-color) 85%, white)!important;}
  .bg-gold{background-color:var(--gold-color)!important;}
  .text-gold{color:var(--gold-color)!important;}
  .bg-gold\/15{background-color:color-mix(in srgb, var(--gold-color) 15%, transparent)!important;}
  .bg-gold\/5{background-color:color-mix(in srgb, var(--gold-color) 5%, transparent)!important;}
  .border-gold\/30{border-color:color-mix(in srgb, var(--gold-color) 30%, transparent)!important;}
  .border-gold\/40{border-color:color-mix(in srgb, var(--gold-color) 40%, transparent)!important;}
  .glow{box-shadow:0 0 0 1px color-mix(in srgb, var(--accent-color) 25%, transparent), 0 8px 28px -12px color-mix(in srgb, var(--accent-color) 35%, transparent)!important;}
  .input-dark:focus{border-color:var(--accent-color)!important; box-shadow:0 0 0 2px color-mix(in srgb, var(--accent-color) 20%, transparent)!important;}
  .nav-link.active{box-shadow: inset 2px 0 0 var(--accent-color)!important;}
  .theme-swatch { width:38px; height:38px; border-radius:50%; cursor:pointer; border:2px solid transparent; transition:.15s; position:relative; }
  .theme-swatch:hover { transform:scale(1.08); }
  .theme-swatch.selected { border-color:#fff; }
  .theme-swatch.selected::after { content:'✓'; position:absolute; inset:0; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; text-shadow:0 1px 2px rgba(0,0,0,0.6); }
  .display { font-family:'Space Grotesk',sans-serif; }
  .card { background:var(--card-c); border:1px solid var(--border-c); }
  .glow { box-shadow:0 0 0 1px rgba(108,92,231,0.25), 0 8px 28px -12px rgba(108,92,231,0.35); }
  .input-dark { background:var(--bg-c); border:1px solid var(--border-c); color:var(--text-c); }
  .input-dark:focus { border-color:var(--accent-color); outline:none; box-shadow:0 0 0 2px rgba(108,92,231,0.2); }
  .page { display:none; }
  .page.active { display:block; animation:rise .3s ease; }
  @keyframes rise { from{opacity:0; transform:translateY(8px);} to{opacity:1; transform:translateY(0);} }
  .nav-link.active { background:#0D0D0D; color:#fff !important; box-shadow: inset 2px 0 0 var(--accent-color); }
  .scrollbar-hide::-webkit-scrollbar { display:none; }
  .thin-scroll::-webkit-scrollbar { width:6px; }
  .thin-scroll::-webkit-scrollbar-thumb { background:#1E1E1E; border-radius:4px; }
  .thin-scroll { -webkit-overflow-scrolling:touch; }
  .vault-container { position:relative; width:100%; aspect-ratio:16/9; background:#0D0D0D; border-radius:10px; overflow:hidden; border:1px solid #1E1E1E; }
  .vault-img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; }
  .vault-after { clip-path:inset(0 0 0 50%); }
  .vault-slider { position:absolute; inset:0; width:100%; height:100%; opacity:0; cursor:ew-resize; z-index:10; }
  .vault-line { position:absolute; top:0; bottom:0; width:2px; background:var(--accent-color); left:50%; transform:translateX(-50%); pointer-events:none; box-shadow:0 0 10px rgba(108,92,231,0.6); }
  .vault-handle { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:28px; height:28px; background:var(--accent-color); border-radius:50%; pointer-events:none; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; box-shadow:0 0 12px rgba(108,92,231,0.7); }
  .verified-badge { background:linear-gradient(135deg,var(--accent-color) 0%,#4834D4 100%); }
  .loading-veil { position:fixed; inset:0; background:#000; display:flex; align-items:center; justify-content:center; z-index:50; transition:opacity .3s ease; }
  .chat-bubble-me { background:var(--accent-color); color:#fff; border-radius:14px 14px 3px 14px; }
  .chat-bubble-them { background:#131313; border:1px solid #1E1E1E; color:#F5F5F5; border-radius:14px 14px 14px 3px; }
  .auth-bg { background: radial-gradient(circle at 30% 20%, rgba(108,92,231,0.12) 0%, transparent 45%), radial-gradient(circle at 80% 80%, rgba(245,185,66,0.06) 0%, transparent 40%), #000; }
  .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.7); backdrop-filter:blur(4px); z-index:60; display:flex; align-items:flex-start; justify-content:center; padding:6vh 16px; overflow-y:auto; -webkit-overflow-scrolling:touch; }
  .kanban-col { background:#0D0D0D; border:1px solid #1E1E1E; border-radius:14px; min-height:120px; }
  .kanban-card { background:#131313; border:1px solid #1E1E1E; border-radius:10px; cursor:grab; }
  .kanban-card.dragging { opacity:0.4; }
  .kanban-col.drag-over { border-color:var(--accent-color); background:rgba(108,92,231,0.06); }
  .insight-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
  kbd { background:#1E1E1E; border:1px solid #2A2A2A; border-radius:4px; padding:1px 6px; font-size:11px; font-family:'JetBrains Mono',monospace; color:#8A8A8E; }
  .cal-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:8px; }
  @media (max-width: 640px) {
    .cal-grid-wrap { overflow-x:auto; }
    .cal-grid { grid-template-columns:repeat(7, minmax(96px,1fr)); min-width:720px; }
  }
  .cal-day { background:#0D0D0D; border:1px solid #1E1E1E; border-radius:10px; min-height:90px; padding:6px; }
  .cal-day.today { border-color:var(--accent-color); }
  .exercise-chip { background:#131313; border:1px solid #1E1E1E; border-radius:8px; padding:8px 10px; cursor:pointer; transition:.15s; }
  .exercise-chip:hover { border-color:var(--accent-color); }
  .toggle-switch { width:38px; height:22px; background:#1E1E1E; border-radius:999px; position:relative; cursor:pointer; transition:.2s; flex-shrink:0; }
  .toggle-switch.on { background:var(--accent-color); }
  .toggle-switch::after { content:''; position:absolute; top:2px; left:2px; width:18px; height:18px; background:#fff; border-radius:50%; transition:.2s; }
  .toggle-switch.on::after { transform:translateX(16px); }
  .sig-box { border:1px dashed #2A2A2A; border-radius:10px; padding:16px; text-align:center; }
  .quick-reply-chip { white-space:nowrap; padding:6px 14px; border-radius:999px; background:var(--card2-c); border:1px solid var(--border-c); color:var(--text-c); font-size:12px; cursor:pointer; transition:.15s; flex-shrink:0; }
  .quick-reply-chip:hover { border-color:var(--accent-color); color:var(--accent-color); }
  .metal-card { position:relative; overflow:hidden; border-radius:16px; background:linear-gradient(150deg,#1c1c22 0%,#0a0a0c 45%,#17171c 100%); border:1px solid #3a3a44; box-shadow: inset 0 1px 0 rgba(255,255,255,0.09), inset 0 -1px 0 rgba(0,0,0,0.5), 0 10px 26px -12px rgba(0,0,0,0.7); transition:transform .2s ease, box-shadow .2s ease; }
  .metal-card:hover { transform:translateY(-3px); box-shadow: inset 0 1px 0 rgba(255,255,255,0.12), inset 0 -1px 0 rgba(0,0,0,0.5), 0 16px 34px -14px rgba(0,0,0,0.8); }
  .metal-card::before { content:''; position:absolute; top:0; left:-160%; width:55%; height:100%; background:linear-gradient(120deg, transparent, rgba(255,255,255,0.10), transparent); transform:skewX(-20deg); transition:left .7s ease; pointer-events:none; }
  .metal-card:hover::before { left:160%; }
  .metal-badge-gold { background:linear-gradient(135deg,#F9D77E 0%,#F5B942 40%,#B9791C 100%); color:#2a1c02; box-shadow:0 1px 0 rgba(255,255,255,0.4) inset, 0 2px 6px -2px rgba(0,0,0,0.6); }
  .metal-badge-silver { background:linear-gradient(135deg,#E8E8EE 0%,#B8B8C4 45%,#7A7A86 100%); color:#1a1a1f; box-shadow:0 1px 0 rgba(255,255,255,0.5) inset, 0 2px 6px -2px rgba(0,0,0,0.6); }
  .metal-stock-bar { background:linear-gradient(90deg,#2a2a30,#3a3a44); border-radius:999px; overflow:hidden; height:6px; }
  .metal-stock-fill { background:linear-gradient(90deg,#8A8A92,#E8E8EE,#8A8A92); height:100%; border-radius:999px; }
  .metal-tab { background:linear-gradient(180deg,#1c1c22,#0d0d10); border:1px solid #34343c; }
  .metal-tab.active { background:linear-gradient(180deg,#3a3a44,#1c1c22); border-color:#6a6a76; box-shadow:0 1px 0 rgba(255,255,255,0.15) inset; }
  .metal-icon-plate { background:linear-gradient(150deg,#2a2a32,#111114); border:1px solid #3a3a44; box-shadow: inset 0 1px 0 rgba(255,255,255,0.1); }
  .client-preview-bar { position:fixed; top:0; left:0; right:0; background:var(--accent-color); color:#fff; text-align:center; font-size:12px; font-weight:600; padding:6px; z-index:70; }
  .toast-wrap { position:fixed; bottom:20px; right:20px; z-index:80; display:flex; flex-direction:column; gap:8px; }
  .toast { background:#131313; border:1px solid #2A2A2A; border-left:3px solid var(--accent-color); color:#F5F5F5; padding:12px 16px; border-radius:10px; font-size:13px; box-shadow:0 10px 30px -10px rgba(0,0,0,0.6); animation:toastIn .25s ease; max-width:300px; }
  @keyframes toastIn { from{opacity:0; transform:translateX(20px);} to{opacity:1; transform:translateX(0);} }
  .notif-dot { position:absolute; top:-2px; right:-2px; width:9px; height:9px; background:var(--gold-color); border-radius:50%; border:2px solid #000; }
  .notif-panel { position:absolute; top:48px; right:0; width:320px; max-width:calc(100vw - 32px); max-height:400px; overflow-y:auto; z-index:35; }
  html[dir="rtl"] .notif-panel { right:auto; left:0; }
  html[dir="rtl"] body, html[dir="rtl"] .display { font-family:'Noto Sans Arabic','Inter',sans-serif; }
  html[dir="rtl"] .nav-link.active { box-shadow: inset -2px 0 0 var(--accent-color, var(--accent-color)); }
  html[dir="rtl"] .mr-3, html[dir="rtl"] .mr-2 { margin-right:0; margin-left:0.75rem; }
  html[dir="rtl"] .ml-3 { margin-left:0; margin-right:0.75rem; }
  html[dir="rtl"] #sidebar { border-right:none; border-left:1px solid var(--border-c, #1E1E1E); }
  html[dir="rtl"] .chat-bubble-me { border-radius:14px 14px 14px 3px; }
  html[dir="rtl"] .chat-bubble-them { border-radius:14px 14px 3px 14px; }
  @media (max-width: 767px) {
    html[dir="rtl"] #sidebar { transform:translateX(100%); left:auto; right:0; }
    html[dir="rtl"] #sidebar.open { transform:translateX(0); }
  }
  .lang-option { opacity:0.45; }
  .lang-option-active { opacity:1; background:rgba(108,92,231,0.18); }
  #lang-panel { animation:langPop .15s ease; transform-origin:top right; }
  html[dir="rtl"] #lang-panel { right:auto; left:0; transform-origin:top left; }
  @keyframes langPop { from{ opacity:0; transform:scale(0.9) translateY(-4px);} to{ opacity:1; transform:scale(1) translateY(0);} }
  .checklist-item { display:flex; align-items:center; gap:10px; padding:8px 0; }
  .checklist-check { width:20px; height:20px; border-radius:6px; border:1.5px solid #2A2A2A; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:12px; }
  .checklist-check.done { background:var(--accent-color); border-color:var(--accent-color); color:#fff; }
  .info-btn { width:20px; height:20px; border-radius:50%; border:1px solid #2A2A2A; color:#8A8A8E; font-size:11px; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; flex-shrink:0; transition:.15s; }
  .info-btn:hover { border-color:var(--accent-color); color:var(--accent-color); }
  #sidebar { transition: transform .25s ease; }
  @media (max-width: 767px) {
    #sidebar { position:fixed; top:0; left:0; z-index:50; transform:translateX(-100%); width:78vw; max-width:300px; height:100vh; height:var(--app-height, 100dvh); }
    #sidebar.open { transform:translateX(0); }
    #sidebar nav { -webkit-overflow-scrolling:touch; overscroll-behavior:contain; touch-action:pan-y; }
  }
  @media (max-width: 767px) {
    #app { height:100vh; height:var(--app-height, 100dvh); }
  }
  html, body { overscroll-behavior-y: none; -webkit-tap-highlight-color: transparent; }
  * { -webkit-tap-highlight-color: transparent; }
  button, a, .checklist-check { touch-action: manipulation; }
  button:active, .nav-link:active, .checklist-check:active { transform: scale(0.96); }
  @media (max-width: 767px) {
    button, .nav-link, input, select, textarea { font-size: 16px; } /* prevents iOS auto-zoom on focus */
    main { padding-bottom: 88px !important; }
    #quickActionsWrap { bottom: calc(80px + env(safe-area-inset-bottom, 0px)); }
  }

  /* Bottom tab bar (mobile only) */
  #bottomNav { position:fixed; bottom:0; left:0; right:0; z-index:45; background:var(--card-c); border-top:1px solid var(--border-c); padding-bottom:env(safe-area-inset-bottom, 0px); display:flex; }
  .bottom-nav-item { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:2px; padding:8px 2px 10px; color:var(--muted-c); font-size:10px; font-weight:500; transition:.15s; position:relative; }
  .bottom-nav-item .bn-icon { font-size:19px; line-height:1; }
  .bottom-nav-item.active { color:var(--accent-color); }
  .bottom-nav-item.active .bn-icon { transform:translateY(-1px); }
  .bottom-nav-dot { position:absolute; top:4px; right:calc(50% - 16px); width:7px; height:7px; background:var(--gold-color); border-radius:50%; border:1.5px solid var(--card-c); }
  @media (min-width: 768px) { #bottomNav { display:none; } }

  /* Quick Actions FAB */
  #quick-actions-fab { transition:transform .2s ease, box-shadow .2s ease; box-shadow:0 8px 24px -6px rgba(108,92,231,0.6); }
  #quick-actions-fab:hover { box-shadow:0 10px 30px -6px rgba(108,92,231,0.8); }
  #quick-actions-fab.rotate-45 { transform:rotate(45deg); }
  #quick-actions-menu { animation:fabPop .18s ease; }
  @keyframes fabPop { from{ opacity:0; transform:translateY(8px) scale(0.9);} to{ opacity:1; transform:translateY(0) scale(1);} }
  .fab-item { box-shadow:0 6px 18px -6px rgba(0,0,0,0.6); }

  /* ===== Cosmic + Metallic design system ===== */
  :root { --bg-c:#05050A; --card-c:#0B0B14; --card2-c:#131320; --border-c:#20202E; }
  #stars { position:fixed; inset:0; z-index:0; overflow:hidden; pointer-events:none; }
  .cs-star { position:absolute; background:#fff; border-radius:50%; opacity:.5; animation:cs-twinkle linear infinite; }
  @keyframes cs-twinkle { 0%,100%{opacity:.15;} 50%{opacity:.85;} }
  .cs-glow { position:fixed; z-index:0; border-radius:50%; filter:blur(90px); pointer-events:none; }
  .cs-glow-1 { width:520px; height:520px; background:radial-gradient(circle, color-mix(in srgb, var(--accent-color) 30%, transparent), transparent 70%); top:-180px; left:-140px; }
  .cs-glow-2 { width:460px; height:460px; background:radial-gradient(circle, color-mix(in srgb, var(--gold-color) 14%, transparent), transparent 70%); bottom:-160px; right:-160px; }
  html[data-theme="light"] #stars, html[data-theme="light"] .cs-glow { display:none; }

  .metal-card, .card { position:relative; overflow:hidden; background:linear-gradient(150deg,#1c1c26 0%,#0a0a0f 45%,#171722 100%)!important; border:1px solid rgba(255,255,255,0.09)!important; box-shadow:inset 0 1px 0 rgba(255,255,255,0.08), inset 0 -1px 0 rgba(0,0,0,0.5), 0 12px 30px -16px rgba(0,0,0,0.7); }
  .metal-card::before, .card::before { content:''; position:absolute; top:0; left:-160%; width:55%; height:100%; background:linear-gradient(120deg, transparent, rgba(255,255,255,0.07), transparent); transform:skewX(-20deg); transition:left .7s ease; pointer-events:none; }
  .metal-card:hover::before, .card:hover::before { left:160%; }
  html[data-theme="light"] .metal-card, html[data-theme="light"] .card { background:var(--card-c)!important; box-shadow:none; }
  html[data-theme="light"] .metal-card::before, html[data-theme="light"] .card::before { display:none; }

  .bg-accent.glow, .glow { position:relative; overflow:hidden; }
  button.bg-accent, a.bg-accent, .btn-metal { position:relative; overflow:hidden; }
  button.bg-accent::before, a.bg-accent::before { content:''; position:absolute; top:0; left:-160%; width:55%; height:100%; background:linear-gradient(120deg, transparent, rgba(255,255,255,0.35), transparent); transform:skewX(-20deg); transition:left .6s ease; pointer-events:none; }
  button.bg-accent:hover::before, a.bg-accent:hover::before { left:160%; }
</style>

<style>
  :focus-visible { outline: 3px solid #a79bff; outline-offset: 4px; }
  @media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { animation: none !important; scroll-behavior: auto !important; transition: none !important; }
  }
</style>
</head>
<body class="text-text antialiased" data-csrf="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">

<div id="stars"></div>
<div class="cs-glow cs-glow-1"></div>
<div class="cs-glow cs-glow-2"></div>

<div id="loadingVeil" class="loading-veil">
  <div class="text-center">
    <div class="display text-3xl text-white mb-2">Penguins 🐧<span class="text-accent">.</span></div>
    <div class="text-muted text-xs tracking-widest uppercase">Loading dashboard…</div>
  </div>
</div>

<!-- ================= APP ================= -->
<div id="app" class="flex h-screen overflow-hidden relative z-10">

  <!-- SIDEBAR BACKDROP (mobile only) -->
  <div id="sidebarBackdrop" onclick="closeSidebar()" class="hidden fixed inset-0 bg-black/60 z-40 md:hidden"></div>

  <!-- SIDEBAR -->
  <aside id="sidebar" class="w-64 bg-card border-r border-border flex-col flex shrink-0">
    <div class="p-6 border-b border-border flex items-center justify-between">
      <div>
        <h1 class="display text-2xl font-bold text-white">Penguins 🐧<span class="text-accent">.</span></h1>
        <p class="text-[11px] text-muted mt-1 tracking-wide uppercase">Coach Platform</p>
      </div>
      <button onclick="closeSidebar()" class="md:hidden w-8 h-8 flex items-center justify-center text-muted hover:text-white text-lg">×</button>
    </div>
    <div class="px-6 pt-4 space-y-2">
      <button onclick="openPalette()" class="w-full flex items-center justify-between px-3 py-2 bg-black border border-border rounded-lg text-muted text-xs hover:border-accent/50 transition">
        <span class="flex items-center"><span class="mr-2">⌕</span><span data-i18n="sidebar.search">Quick search</span></span>
        <kbd>⌘K</kbd>
      </button>
      <button onclick="openHelp()" class="w-full flex items-center justify-between px-3 py-2 bg-black border border-border rounded-lg text-muted text-xs hover:border-accent/50 transition">
        <span class="flex items-center"><span class="mr-2">?</span><span data-i18n="sidebar.help">Help & Guide</span></span>
      </button>
    </div>
    <nav class="flex-1 p-4 space-y-1 overflow-y-auto scrollbar-hide min-h-0">
      <p class="text-[10px] text-muted uppercase tracking-widest px-4 mb-2 mt-1" data-i18n="sidebar.overview">Overview</p>
      <a href="#" data-page="dashboard" class="nav-link active flex items-center px-4 py-2.5 text-sm font-medium text-muted rounded-lg transition-all"><span class="mr-3">◆</span><span data-i18n="nav.dashboard">Dashboard</span></a>
      <a href="#" data-page="messages" class="nav-link flex items-center justify-between px-4 py-2.5 text-sm font-medium text-muted rounded-lg transition-all"><span class="flex items-center"><span class="mr-3">✉</span><span data-i18n="nav.messages">Messages</span></span><span id="unread-badge" class="hidden bg-accent text-white text-[10px] rounded-full px-1.5 py-0.5"></span></a>
      <a href="#" data-page="clients" class="nav-link flex items-center px-4 py-2.5 text-sm font-medium text-muted rounded-lg transition-all"><span class="mr-3">◇</span><span data-i18n="nav.clients">Clients</span></a>

      <p class="text-[10px] text-muted uppercase tracking-widest px-4 mb-2 mt-4" data-i18n="sidebar.coachingTools">Coaching Tools</p>
      <a href="#" data-page="planner" class="nav-link flex items-center px-4 py-2.5 text-sm font-medium text-muted rounded-lg transition-all"><span class="mr-3">▤</span><span data-i18n="nav.planner">Plan Builder</span></a>
      <a href="#" data-page="nutrition" class="nav-link flex items-center px-4 py-2.5 text-sm font-medium text-muted rounded-lg transition-all"><span class="mr-3">◍</span><span data-i18n="nav.nutrition">Nutrition</span></a>
      <a href="#" data-page="live" class="nav-link flex items-center px-4 py-2.5 text-sm font-medium text-muted rounded-lg transition-all"><span class="mr-3">●</span><span data-i18n="nav.live">Live Sessions</span></a>
      <a href="#" data-page="library" class="nav-link flex items-center px-4 py-2.5 text-sm font-medium text-muted rounded-lg transition-all"><span class="mr-3">▦</span><span data-i18n="nav.library">Resource Library</span></a>
      <a href="#" data-page="feedback" class="nav-link flex items-center px-4 py-2.5 text-sm font-medium text-muted rounded-lg transition-all"><span class="mr-3">★</span><span data-i18n="nav.feedback">Client Feedback</span></a>
      <a href="#" data-page="review" class="nav-link flex items-center px-4 py-2.5 text-sm font-medium text-muted rounded-lg transition-all"><span class="mr-3">▶</span><span data-i18n="nav.review">Vision Review</span></a>

      <p class="text-[10px] text-muted uppercase tracking-widest px-4 mb-2 mt-4" data-i18n="sidebar.clientMonitoring">Client Monitoring</p>
      <a href="#" data-page="points" class="nav-link flex items-center px-4 py-2.5 text-sm font-medium text-muted rounded-lg transition-all"><span class="mr-3">◈</span><span data-i18n="nav.points">Points & Streaks</span></a>
      <a href="#" data-page="readiness" class="nav-link flex items-center px-4 py-2.5 text-sm font-medium text-muted rounded-lg transition-all"><span class="mr-3">◎</span><span data-i18n="nav.readiness">Readiness</span></a>
      <a href="#" data-page="vault" class="nav-link flex items-center px-4 py-2.5 text-sm font-medium text-muted rounded-lg transition-all"><span class="mr-3">▣</span><span data-i18n="nav.vault">Progress Vault</span></a>
      <a href="#" data-page="community" class="nav-link flex items-center px-4 py-2.5 text-sm font-medium text-muted rounded-lg transition-all"><span class="mr-3">◐</span><span data-i18n="nav.community">Community</span></a>
      <a href="#" data-page="cohorts" class="nav-link flex items-center px-4 py-2.5 text-sm font-medium text-muted rounded-lg transition-all"><span class="mr-3">⬢</span><span data-i18n="nav.cohorts">Cohorts</span></a>

      <p class="text-[10px] text-muted uppercase tracking-widest px-4 mb-2 mt-4" data-i18n="sidebar.automation">Automation & Insight</p>
      <a href="#" data-page="automations" class="nav-link flex items-center px-4 py-2.5 text-sm font-medium text-muted rounded-lg transition-all"><span class="mr-3">⚡</span><span data-i18n="nav.automations">Automations</span></a>
      <a href="#" data-page="reports" class="nav-link flex items-center px-4 py-2.5 text-sm font-medium text-muted rounded-lg transition-all"><span class="mr-3">▩</span><span data-i18n="nav.reports">Reports</span></a>
      <a href="#" data-page="contracts" class="nav-link flex items-center px-4 py-2.5 text-sm font-medium text-muted rounded-lg transition-all"><span class="mr-3">✎</span><span data-i18n="nav.contracts">Contracts</span></a>

      <p class="text-[10px] text-muted uppercase tracking-widest px-4 mb-2 mt-4" data-i18n="sidebar.business">Business</p>
      <a href="#" data-page="pipeline" class="nav-link flex items-center px-4 py-2.5 text-sm font-medium text-muted rounded-lg transition-all"><span class="mr-3">⬡</span><span data-i18n="nav.pipeline">Pipeline</span></a>
      <a href="#" data-page="invoices" class="nav-link flex items-center px-4 py-2.5 text-sm font-medium text-muted rounded-lg transition-all"><span class="mr-3">▥</span><span data-i18n="nav.invoices">Invoices</span></a>
      <a href="#" data-page="shop" class="nav-link flex items-center px-4 py-2.5 text-sm font-medium text-muted rounded-lg transition-all"><span class="mr-3">▨</span><span data-i18n="nav.shop">Shop</span></a>
      <a href="#" data-page="referrals" class="nav-link flex items-center px-4 py-2.5 text-sm font-medium text-muted rounded-lg transition-all"><span class="mr-3">↗</span><span data-i18n="nav.referrals">Referrals</span></a>
      <a href="#" data-page="verification" class="nav-link flex items-center px-4 py-2.5 text-sm font-medium text-muted rounded-lg transition-all"><span class="mr-3">✓</span><span data-i18n="nav.verification">Verification</span></a>
      <a href="#" data-page="account" class="nav-link flex items-center px-4 py-2.5 text-sm font-medium text-muted rounded-lg transition-all"><span class="mr-3">◉</span><span data-i18n="nav.account">Account</span></a>
    </nav>
    <div class="px-4 pb-2">
      <button onclick="openClientPreview()" class="w-full flex items-center justify-center gap-2 px-3 py-2 bg-black border border-border rounded-lg text-muted text-xs hover:border-accent/50 hover:text-white transition">
        <span>◑</span> <span data-i18n="sidebar.viewAsClient">View as Client</span>
      </button>
    </div>
    <div class="p-4 border-t border-border flex items-center gap-2">
      <a href="#" onclick="goToAccount(event)" class="flex items-center flex-1 min-w-0 text-left hover:bg-card2 rounded-lg p-2 transition">
        <div id="sidebar-avatar" class="w-10 h-10 rounded-full bg-accent flex items-center justify-center text-white font-bold text-sm shrink-0">JD</div>
        <div class="ml-3 flex-1 min-w-0"><p id="sidebar-name" class="text-sm font-medium text-white truncate">Jordan Diaz</p><p class="text-xs text-muted">View account</p></div>
      </a>
      <a href="#" onclick="event.preventDefault(); logOut()" title="Log out" class="w-9 h-9 rounded-lg flex items-center justify-center text-muted hover:text-white hover:bg-card2 transition shrink-0">⏻</a>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="flex-1 flex flex-col overflow-hidden">
    <!-- APP TOPBAR (always visible, every screen size) -->
    <div class="flex items-center gap-3 px-4 md:px-8 py-3 border-b border-border bg-card shrink-0">
      <button onclick="openSidebar()" class="md:hidden w-9 h-9 flex items-center justify-center text-white text-xl leading-none">☰</button>
      <h1 class="md:hidden display text-lg font-bold text-white">Penguins 🐧<span class="text-accent">.</span></h1>
      <div class="flex-1"></div>
      <div class="relative" id="langSwitchWrap">
        <button onclick="toggleLangPanel(event)" class="w-10 h-10 rounded-full bg-black border border-border flex items-center justify-center text-sm hover:border-accent/50 transition">
          <span id="lang-current-flag">🇬🇧</span>
        </button>
        <div id="lang-panel" class="hidden absolute top-12 right-0 flex items-center gap-1.5 p-1.5 rounded-full bg-black border border-border z-40 shadow-2xl">
          <button onclick="setLang('en')" data-lang="en" class="lang-option w-8 h-8 rounded-full flex items-center justify-center text-sm transition">🇬🇧</button>
          <button onclick="setLang('de')" data-lang="de" class="lang-option w-8 h-8 rounded-full flex items-center justify-center text-sm transition">🇩🇪</button>
          <button onclick="setLang('ar')" data-lang="ar" class="lang-option w-8 h-8 rounded-full flex items-center justify-center text-sm transition">🇸🇦</button>
          <button onclick="setLang('fr')" data-lang="fr" class="lang-option w-8 h-8 rounded-full flex items-center justify-center text-sm transition">🇫🇷</button>
          <button onclick="setLang('es')" data-lang="es" class="lang-option w-8 h-8 rounded-full flex items-center justify-center text-sm transition">🇪🇸</button>
        </div>
      </div>
      <button onclick="toggleColorMode()" id="colorModeBtn" class="w-10 h-10 rounded-full bg-black border border-border flex items-center justify-center text-white hover:border-accent/50 transition">🌙</button>
      <div class="relative" id="notifBellWrap">
        <button onclick="toggleNotifPanel()" class="relative w-10 h-10 rounded-full bg-black border border-border flex items-center justify-center text-white hover:border-accent/50 transition">
          🔔
          <span id="notif-dot" class="notif-dot hidden"></span>
        </button>
        <div id="notif-panel" class="notif-panel card rounded-2xl p-3 thin-scroll hidden">
          <p class="text-xs text-muted uppercase tracking-wide px-2 py-1">Notifications</p>
          <div id="notif-list" class="space-y-1"></div>
        </div>
      </div>
    </div>
    <main class="flex-1 overflow-y-auto p-4 md:p-8">

    <!-- ===== DASHBOARD ===== -->
    <div id="page-dashboard" class="page active">
      <header class="mb-8">
        <h2 class="display text-3xl font-bold text-white">Welcome back, <span id="dash-name">Jordan</span></h2>
        <p class="text-muted mt-1">Here's your practice at a glance.</p>
      </header>
      <div id="dash-checklist" class="card rounded-2xl p-5 mb-6"></div>
      <div id="dash-insights" class="card rounded-2xl p-5 mb-6"></div>
      <div id="dash-business-goals" class="card rounded-2xl p-5 mb-6"></div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8" id="dash-stats"></div>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 card rounded-2xl p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="display text-lg font-semibold text-white">Revenue</h3>
            <span class="text-xs text-muted bg-black/40 border border-border rounded-lg px-3 py-1">Last 6 months</span>
          </div>
          <div class="h-64"><canvas id="earningsChart"></canvas></div>
        </div>
        <div class="card rounded-2xl p-6">
          <h3 class="display text-lg font-semibold text-white mb-4">Recent Activity</h3>
          <div id="dash-activity" class="space-y-3"></div>
        </div>
      </div>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        <div class="lg:col-span-3 card rounded-2xl p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="display text-lg font-semibold text-white">My Tasks</h3>
          </div>
          <form id="form-task" class="flex gap-2 mb-4">
            <input type="text" name="text" required placeholder="Add a task…" class="flex-1 input-dark rounded-lg px-4 py-2 text-sm">
            <button type="submit" class="px-4 py-2 bg-accent text-white text-sm font-semibold rounded-lg hover:bg-indigo-500 transition">Add</button>
          </form>
          <div id="list-tasks" class="space-y-2"></div>
        </div>
      </div>
    </div>

    <!-- ===== MESSAGES ===== -->
    <div id="page-messages" class="page">
      <header class="mb-6"><h2 class="display text-3xl font-bold text-white">Messages</h2><p class="text-muted mt-1">Chat directly with your clients.</p></header>
      <div class="card rounded-2xl overflow-hidden" style="height:70vh;">
        <div class="flex h-full">
          <div class="w-full sm:w-64 border-r border-border overflow-y-auto thin-scroll shrink-0" id="chat-list-panel"><div id="chat-client-list"></div></div>
          <div class="hidden sm:flex flex-1 flex-col" id="chat-thread-panel">
            <div class="p-4 border-b border-border flex items-center" id="chat-header">
              <button onclick="backToChatList()" class="sm:hidden mr-3 text-white text-lg leading-none">←</button>
              <div id="chat-header-info" class="flex items-center flex-1"></div>
            </div>
            <div class="flex-1 overflow-y-auto thin-scroll p-4 space-y-3 min-h-0" id="chat-thread"></div>
            <div id="quick-replies" class="px-3 pt-2 flex gap-2 overflow-x-auto thin-scroll"></div>
            <form id="form-chat" class="p-3 border-t border-border flex gap-2">
              <input type="text" id="chat-input" placeholder="Write a message…" class="flex-1 input-dark rounded-lg px-4 py-2 text-sm">
              <button type="submit" class="px-5 py-2 bg-accent text-white text-sm font-semibold rounded-lg hover:bg-indigo-500 transition">Send</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== PLAN BUILDER ===== -->
    <div id="page-planner" class="page">
      <header class="mb-8"><h2 class="display text-3xl font-bold text-white">Plan Builder</h2><p class="text-muted mt-1">Build structured programs from the exercise library, or write freeform. Send to any client.</p></header>
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <div class="card rounded-2xl p-6">
          <h3 class="display text-lg font-semibold text-white mb-3">Exercise Library</h3>
          <input type="text" id="exercise-search" oninput="renderExerciseLibrary()" placeholder="Search exercises…" class="w-full input-dark rounded-lg px-4 py-2 text-sm mb-3">
          <div id="exercise-library" class="space-y-2 max-h-[420px] overflow-y-auto thin-scroll pr-1"></div>
        </div>

        <div class="lg:col-span-2 card rounded-2xl p-6">
          <h3 class="display text-lg font-semibold text-white mb-4">Create & Assign Plan</h3>
          <form id="form-plan" class="space-y-4">
            <div><label class="text-xs text-muted mb-1 block">Assign to Client</label><select name="client" class="w-full input-dark rounded-lg px-4 py-2 text-sm"><option>Alex M.</option><option>Sarah K.</option><option>Mike T.</option><option>Emma R.</option></select></div>
            <div><label class="text-xs text-muted mb-1 block">Plan Title</label><input type="text" name="title" required class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="e.g. Week 3 - Hypertrophy"></div>
            <div><label class="text-xs text-muted mb-1 block">Type</label><select name="type" class="w-full input-dark rounded-lg px-4 py-2 text-sm"><option>Training</option><option>Nutrition</option><option>Mindset</option><option>Business Goals</option></select></div>
            <div>
              <div class="flex items-center justify-between mb-1"><label class="text-xs text-muted block">Built Workout (from library)</label><button type="button" onclick="clearBuiltWorkout()" class="text-[11px] text-muted hover:text-red-400">Clear</button></div>
              <div id="built-workout" class="space-y-2 mb-2 min-h-[40px] bg-black border border-border rounded-lg p-3">
                <p class="text-muted text-xs" id="built-workout-empty">Click exercises on the left to add them here.</p>
              </div>
            </div>
            <div><label class="text-xs text-muted mb-1 block">Additional Notes / Freeform Details</label><textarea name="details" rows="3" class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="Nutrition notes, cues, anything else..."></textarea></div>
            <div class="flex gap-2">
              <button type="submit" class="flex-1 py-2.5 bg-accent text-white font-semibold rounded-lg hover:bg-indigo-500 transition glow">Send to Client →</button>
              <button type="button" onclick="saveAsTemplate()" class="px-4 py-2.5 bg-black border border-border text-muted text-sm font-medium rounded-lg hover:border-accent/50 hover:text-accent transition whitespace-nowrap">Save as Template</button>
            </div>
          </form>
        </div>

        <div class="card rounded-2xl p-6">
          <div class="flex bg-black border border-border rounded-lg p-1 text-xs mb-4">
            <button id="plan-tab-sent" onclick="setPlanTab('sent')" class="flex-1 px-3 py-1.5 rounded-md font-medium transition">Sent Plans</button>
            <button id="plan-tab-templates" onclick="setPlanTab('templates')" class="flex-1 px-3 py-1.5 rounded-md font-medium transition">Templates</button>
          </div>
          <div id="plan-view-sent"><div id="list-plans" class="space-y-3 max-h-[500px] overflow-y-auto thin-scroll pr-2"></div></div>
          <div id="plan-view-templates" class="hidden"><div id="list-templates" class="space-y-3 max-h-[500px] overflow-y-auto thin-scroll pr-2"></div></div>
        </div>
      </div>

      <div class="card rounded-2xl p-6 mt-6 max-w-sm">
        <h3 class="display text-sm font-semibold text-white tracking-wide uppercase mb-4">Rest Timer</h3>
        <div class="text-center">
          <p id="timer-display" class="display text-5xl font-bold text-white mb-4 tabular-nums">01:30</p>
          <div class="flex justify-center gap-2 mb-4">
            <button onclick="setTimerPreset(30)" class="px-3 py-1.5 bg-black border border-border rounded-lg text-xs text-muted hover:border-accent/50 hover:text-white transition">30s</button>
            <button onclick="setTimerPreset(60)" class="px-3 py-1.5 bg-black border border-border rounded-lg text-xs text-muted hover:border-accent/50 hover:text-white transition">60s</button>
            <button onclick="setTimerPreset(90)" class="px-3 py-1.5 bg-black border border-border rounded-lg text-xs text-muted hover:border-accent/50 hover:text-white transition">90s</button>
            <button onclick="setTimerPreset(120)" class="px-3 py-1.5 bg-black border border-border rounded-lg text-xs text-muted hover:border-accent/50 hover:text-white transition">120s</button>
          </div>
          <div class="flex gap-2">
            <button id="timer-toggle-btn" onclick="toggleTimer()" class="flex-1 py-2.5 bg-accent text-white font-semibold rounded-lg hover:bg-indigo-500 transition glow">Start</button>
            <button onclick="resetTimer()" class="px-4 py-2.5 bg-black border border-border text-muted rounded-lg hover:text-white transition">↺</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== NUTRITION (subscribed clients only) ===== -->
    <div id="page-nutrition" class="page">
      <header class="mb-6"><h2 class="display text-3xl font-bold text-white">Nutrition</h2><p class="text-muted mt-1">Set macro targets and track intake — only for clients subscribed to the Nutrition add-on.</p></header>

      <div class="mb-8">
        <h3 class="display text-sm font-semibold text-white tracking-wide uppercase mb-3">Subscribed Clients</h3>
        <div id="list-nutrition-subscribed" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"></div>
      </div>

      <div>
        <h3 class="display text-sm font-semibold text-white tracking-wide uppercase mb-3">Not Subscribed</h3>
        <p class="text-muted text-xs mb-3">These clients don't have the Nutrition add-on. Enable it to start setting macro targets for them.</p>
        <div id="list-nutrition-unsubscribed" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"></div>
      </div>
    </div>

    <!-- ===== LIVE SESSIONS ===== -->
    <div id="page-live" class="page">
      <header class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div><h2 class="display text-3xl font-bold text-white">Live Sessions</h2><p class="text-muted mt-1">Schedule calls, or open slots for clients to self-book.</p></div>
        <div class="flex bg-black border border-border rounded-lg p-1 text-xs">
          <button id="live-tab-list" onclick="setLiveView('list')" class="px-3 py-1.5 rounded-md font-medium transition">List</button>
          <button id="live-tab-cal" onclick="setLiveView('cal')" class="px-3 py-1.5 rounded-md font-medium transition">Calendar</button>
        </div>
      </header>

      <div id="live-view-list" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="card rounded-2xl p-6">
          <h3 class="display text-lg font-semibold text-white mb-4">Schedule a Session</h3>
          <form id="form-live" class="space-y-3">
            <input type="text" name="topic" required placeholder="Session Topic" class="w-full input-dark rounded-lg px-4 py-2 text-sm">
            <input type="datetime-local" name="time" required class="w-full input-dark rounded-lg px-4 py-2 text-sm">
            <select name="access" class="w-full input-dark rounded-lg px-4 py-2 text-sm"><option>All Clients</option><option>Paid Clients Only</option></select>
            <button type="submit" class="w-full py-2.5 bg-accent text-white font-semibold rounded-lg hover:bg-indigo-500 transition glow">Schedule</button>
          </form>
          <hr class="border-border my-5">
          <h3 class="display text-sm font-semibold text-white mb-3">Open a Bookable Slot</h3>
          <form id="form-slot" class="space-y-3">
            <input type="datetime-local" name="time" required class="w-full input-dark rounded-lg px-4 py-2 text-sm">
            <button type="submit" class="w-full py-2 bg-black border border-border text-white text-sm font-medium rounded-lg hover:border-accent/50 transition">+ Add Open Slot</button>
          </form>
        </div>
        <div class="lg:col-span-2 card rounded-2xl p-6">
          <h3 class="display text-lg font-semibold text-white mb-4">Scheduled Sessions</h3>
          <div id="list-live" class="space-y-3 mb-6"></div>
          <h3 class="display text-lg font-semibold text-white mb-4">Open Slots (clients can self-book)</h3>
          <div id="list-slots" class="space-y-3"></div>
        </div>
      </div>

      <div id="live-view-cal" class="card rounded-2xl p-6 hidden">
        <h3 class="display text-lg font-semibold text-white mb-4">This Week</h3>
        <div class="cal-grid-wrap"><div class="cal-grid" id="live-calendar"></div></div>
      </div>
    </div>

    <!-- ===== RESOURCE LIBRARY (PDF upload, free/paid) ===== -->
    <div id="page-library" class="page">
      <header class="mb-8"><h2 class="display text-3xl font-bold text-white">Resource Library</h2><p class="text-muted mt-1">Upload PDF guides and programs. Set each as free or paid.</p></header>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="card rounded-2xl p-6">
          <h3 class="display text-lg font-semibold text-white mb-4">Upload a Resource</h3>
          <form id="form-library" class="space-y-4">
            <div>
              <label class="text-xs text-muted mb-1 block">PDF File</label>
              <input type="file" name="file" accept=".pdf" required class="w-full input-dark rounded-lg px-3 py-2 text-xs file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0 file:bg-accent file:text-white file:text-xs">
            </div>
            <div><label class="text-xs text-muted mb-1 block">Title</label><input type="text" name="title" required class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="e.g. 30-Day Foundations Guide"></div>
            <div><label class="text-xs text-muted mb-1 block">Description</label><textarea name="desc" rows="3" class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="What's inside this resource?"></textarea></div>
            <div class="flex gap-3">
              <div class="flex-1"><label class="text-xs text-muted mb-1 block">Access</label>
                <select name="tier" id="library-tier" onchange="toggleLibraryPrice()" class="w-full input-dark rounded-lg px-4 py-2 text-sm"><option value="Free">Free</option><option value="Paid">Paid</option></select>
              </div>
              <div class="flex-1 hidden" id="library-price-wrap"><label class="text-xs text-muted mb-1 block">Price ($)</label><input type="number" step="0.01" name="price" id="library-price" class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="19.99"></div>
            </div>
            <button type="submit" class="w-full py-2.5 bg-accent text-white font-semibold rounded-lg hover:bg-indigo-500 transition glow">Upload Resource</button>
            <p class="text-muted text-[11px]">This is a demo uploader — the file name and details are saved, but hosting real PDF downloads needs a backend.</p>
          </form>
        </div>
        <div class="lg:col-span-2 card rounded-2xl p-6">
          <h3 class="display text-lg font-semibold text-white mb-4">Your Library</h3>
          <div id="list-library" class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-[560px] overflow-y-auto thin-scroll pr-2"></div>
        </div>
      </div>
    </div>

    <!-- ===== FEEDBACK ===== -->
    <div id="page-feedback" class="page">
      <header class="mb-8"><h2 class="display text-3xl font-bold text-white">Client Feedback</h2><p class="text-muted mt-1">Reviews and messages from your clients.</p></header>
      <div class="card rounded-2xl p-6"><div id="list-feedback" class="space-y-4"></div></div>
    </div>

    <!-- ===== VISION REVIEW ===== -->
    <div id="page-review" class="page">
      <header class="mb-8"><h2 class="display text-3xl font-bold text-white">Vision Review</h2><p class="text-muted mt-1">Review client-submitted form videos. Draw annotations and send corrections.</p></header>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="card rounded-2xl p-6">
          <h3 class="display text-lg font-semibold text-white mb-4">Client Submissions</h3>
          <div id="list-submissions" class="space-y-3"></div>
        </div>
        <div class="lg:col-span-2 card rounded-2xl p-6">
          <h3 class="display text-lg font-semibold text-white mb-4">Annotation Tool</h3>
          <div class="flex flex-wrap gap-3 mb-4">
            <input type="color" id="draw-color" value="#6C5CE7" class="w-10 h-10 bg-transparent border-none rounded cursor-pointer">
            <input type="range" id="draw-size" min="1" max="20" value="4" class="flex-1 accent-accent">
            <button onclick="clearCanvas()" class="px-4 py-2 bg-red-500/15 text-red-400 text-sm rounded-lg hover:bg-red-500/25">Clear</button>
            <button onclick="sendAnnotation()" class="px-4 py-2 bg-accent text-white text-sm font-semibold rounded-lg glow">Send to Client</button>
          </div>
          <div class="relative w-full aspect-video bg-black rounded-xl overflow-hidden border border-border">
            <canvas id="drawCanvas" class="absolute top-0 left-0 w-full h-full cursor-crosshair"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== POINTS & STREAKS ===== -->
    <div id="page-points" class="page">
      <header class="mb-8"><h2 class="display text-3xl font-bold text-white">Points & Streaks</h2><p class="text-muted mt-1">Monitor your clients' daily habit completion.</p></header>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <div class="card rounded-2xl p-6 text-center"><p class="text-muted text-sm">Total Points (All Clients)</p><h3 class="display text-4xl font-bold text-accent mt-2" id="total-points">0</h3></div>
        <div class="card rounded-2xl p-6 text-center"><p class="text-muted text-sm">Avg Daily Completion</p><h3 class="display text-4xl font-bold text-white mt-2">78%</h3></div>
        <div class="card rounded-2xl p-6 text-center"><p class="text-muted text-sm">Top Client</p><h3 class="display text-4xl font-bold text-green-400 mt-2">Alex M.</h3></div>
      </div>
      <div class="card rounded-2xl p-6">
        <h3 class="display text-lg font-semibold text-white mb-4">Client Leaderboard</h3>
        <div id="points-leaderboard" class="space-y-3"></div>
      </div>
    </div>

    <!-- ===== READINESS ===== -->
    <div id="page-readiness" class="page">
      <header class="mb-8"><h2 class="display text-3xl font-bold text-white">Readiness</h2><p class="text-muted mt-1">View client readiness scores. Override auto-adjustments if needed.</p></header>
      <div class="card rounded-2xl p-6">
        <h3 class="display text-lg font-semibold text-white mb-4">Today's Client Readiness</h3>
        <div id="readiness-list" class="space-y-4"></div>
      </div>
    </div>

    <!-- ===== PROGRESS VAULT ===== -->
    <div id="page-vault" class="page">
      <header class="mb-8"><h2 class="display text-3xl font-bold text-white">Progress Vault</h2><p class="text-muted mt-1">View client transformation progress (Day 1 vs Day 90).</p></header>
      <div class="card rounded-2xl p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="display text-lg font-semibold text-white">Alex M. — 90 Day Progress</h3>
          <span class="text-xs text-muted">Uploaded by client</span>
        </div>
        <div class="vault-container">
          <img src="https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=800&q=80" class="vault-img" alt="Before">
          <img src="https://images.unsplash.com/photo-1532029837206-abbe2b7620e3?w=800&q=80" class="vault-img vault-after" id="vaultAfter" alt="After">
          <div class="vault-line" id="vaultLine"></div>
          <div class="vault-handle" id="vaultHandle">⟺</div>
          <input type="range" min="0" max="100" value="50" class="vault-slider" id="vaultSlider">
        </div>
        <div class="flex justify-center gap-4 mt-4">
          <span class="px-4 py-2 bg-black border border-border rounded-lg text-sm text-muted">Day 1</span>
          <span class="px-4 py-2 bg-accent/15 border border-accent/40 rounded-lg text-sm text-accent font-bold">Day 90</span>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        <div class="card rounded-2xl p-6">
          <h3 class="display text-lg font-semibold text-white mb-4">Log Body Metrics</h3>
          <form id="form-metrics" class="space-y-3">
            <select name="client" class="w-full input-dark rounded-lg px-4 py-2 text-sm"><option>Alex M.</option><option>Sarah K.</option><option>Mike T.</option><option>Emma R.</option></select>
            <input type="number" step="0.1" name="weight" required placeholder="Weight (lbs)" class="w-full input-dark rounded-lg px-4 py-2 text-sm">
            <input type="number" step="0.1" name="waist" placeholder="Waist (in, optional)" class="w-full input-dark rounded-lg px-4 py-2 text-sm">
            <button type="submit" class="w-full py-2.5 bg-accent text-white font-semibold rounded-lg hover:bg-indigo-500 transition glow">Log Entry</button>
          </form>
        </div>
        <div class="lg:col-span-2 card rounded-2xl p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="display text-lg font-semibold text-white">Weight Trend</h3>
            <select id="metrics-client-select" onchange="renderMetricsChart()" class="input-dark text-xs rounded-lg px-3 py-1"><option>Alex M.</option><option>Sarah K.</option><option>Mike T.</option><option>Emma R.</option></select>
          </div>
          <div class="h-56"><canvas id="metricsChart"></canvas></div>
        </div>
      </div>
    </div>

    <!-- ===== COMMUNITY ===== -->
    <div id="page-community" class="page">
      <header class="mb-8"><h2 class="display text-3xl font-bold text-white">Community</h2><p class="text-muted mt-1">Moderate your private client community. Post challenges & announcements.</p></header>
      <div class="card rounded-2xl p-6 mb-6">
        <form id="form-community" class="flex gap-3">
          <select name="type" class="input-dark rounded-lg px-4 py-3 text-sm"><option>Announcement</option><option>Challenge</option><option>General Post</option></select>
          <input type="text" name="post" required placeholder="Post to the community..." class="flex-1 input-dark rounded-lg px-4 py-3 text-sm">
          <button type="submit" class="px-6 py-3 bg-accent text-white font-semibold rounded-lg hover:bg-indigo-500 transition glow">Post</button>
        </form>
      </div>
      <div id="list-community" class="space-y-4"></div>
    </div>

    <!-- ===== SHOP ===== -->
    <div id="page-shop" class="page">
      <header class="mb-6"><h2 class="display text-3xl font-bold text-white">Shop</h2><p class="text-muted mt-1">Manage your merch, digital products, coaching packages, and brand collaborations.</p></header>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="metal-card p-5"><p class="text-muted text-xs uppercase tracking-wide">Shop Revenue</p><p class="display text-2xl font-bold text-white mt-1" id="shop-stat-revenue">$0</p></div>
        <div class="metal-card p-5"><p class="text-muted text-xs uppercase tracking-wide">Units Sold</p><p class="display text-2xl font-bold text-white mt-1" id="shop-stat-sold">0</p></div>
        <div class="metal-card p-5"><p class="text-muted text-xs uppercase tracking-wide">Low Stock</p><p class="display text-2xl font-bold text-white mt-1" id="shop-stat-lowstock">0</p></div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="metal-card p-6">
          <h3 class="display text-lg font-semibold text-white mb-4">Add Product</h3>
          <form id="form-shop" class="space-y-3">
            <input type="text" name="name" required class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="Product Name">
            <div class="grid grid-cols-2 gap-3">
              <input type="number" name="price" step="0.01" required class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="Price ($)">
              <input type="number" name="salePrice" step="0.01" class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="Sale price (optional)">
            </div>
            <select name="category" class="w-full input-dark rounded-lg px-4 py-2 text-sm"><option>Merch</option><option>Digital Product</option><option>Coaching Package</option><option>Brand Collab</option></select>
            <div class="grid grid-cols-2 gap-3">
              <input type="number" name="stock" class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="Stock (blank = unlimited)">
              <label class="flex items-center gap-2 px-3 bg-black border border-border rounded-lg text-xs text-muted"><input type="checkbox" name="featured" class="accent-accent"> Featured</label>
            </div>
            <input type="url" name="image" class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="Image URL (optional)">
            <button type="submit" class="w-full py-2.5 bg-accent text-white font-semibold rounded-lg hover:bg-indigo-500 transition glow">Add Product</button>
          </form>
        </div>

        <div class="lg:col-span-2">
          <div class="flex flex-wrap gap-2 mb-4" id="shop-filter-tabs"></div>
          <div id="list-shop" class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-[560px] overflow-y-auto thin-scroll pr-2"></div>
        </div>
      </div>
    </div>

    <!-- ===== REFERRALS ===== -->
    <div id="page-referrals" class="page">
      <header class="mb-8"><h2 class="display text-3xl font-bold text-white">Referrals</h2><p class="text-muted mt-1">Your referral link. Clients share it, you both earn rewards.</p></header>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="card rounded-2xl p-6 text-center"><p class="text-muted text-sm">Total Clicks</p><h3 class="display text-4xl font-bold text-white mt-2" id="ref-clicks">0</h3></div>
        <div class="card rounded-2xl p-6 text-center"><p class="text-muted text-sm">Signups</p><h3 class="display text-4xl font-bold text-accent mt-2" id="ref-signups">0</h3></div>
        <div class="card rounded-2xl p-6 text-center"><p class="text-muted text-sm">Revenue</p><h3 class="display text-4xl font-bold text-green-400 mt-2" id="ref-rev">$0</h3></div>
      </div>
      <div class="card rounded-2xl p-6">
        <h3 class="display text-lg font-semibold text-white mb-4">Your Referral Link</h3>
        <div class="flex gap-3">
          <input type="text" id="ref-link" value="penguins.app/join/jordan-diaz" readonly class="flex-1 input-dark rounded-lg px-4 py-3 text-sm font-mono">
          <button onclick="copyRef()" class="px-6 py-3 bg-accent text-white font-semibold rounded-lg hover:bg-indigo-500 transition glow">Copy</button>
        </div>
      </div>
    </div>

    <!-- ===== VERIFICATION ===== -->
    <div id="page-verification" class="page">
      <header class="mb-8"><h2 class="display text-3xl font-bold text-white">Verification Status</h2><p class="text-muted mt-1">Your account verification is managed entirely by the Penguins team.</p></header>
      <div class="max-w-2xl">
        <div class="card rounded-2xl p-8 text-center mb-6">
          <div class="w-20 h-20 rounded-full verified-badge flex items-center justify-center mx-auto mb-4">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
          </div>
          <h3 class="display text-2xl font-bold text-white mb-2">Verified by Penguins</h3>
          <p class="text-muted">Your identity and certifications have been reviewed and approved by the Penguins verification team.</p>
          <p class="text-accent text-sm mt-3 font-medium">Verified on: January 15, 2025</p>
        </div>
        <div class="card rounded-2xl p-6">
          <h3 class="display text-lg font-semibold text-white mb-4">Documents on File (Managed by Penguins)</h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between p-4 bg-black border border-border rounded-xl">
              <div class="flex items-center"><span class="text-green-400 mr-3">✓</span><div><p class="text-white text-sm font-medium">Personal ID</p><p class="text-muted text-xs">Reviewed by Penguins Admin</p></div></div>
              <span class="px-3 py-1 bg-green-500/15 text-green-400 text-xs rounded-full font-medium">Approved</span>
            </div>
            <div class="flex items-center justify-between p-4 bg-black border border-border rounded-xl">
              <div class="flex items-center"><span class="text-green-400 mr-3">✓</span><div><p class="text-white text-sm font-medium">Coaching Certification</p><p class="text-muted text-xs">Reviewed by Penguins Admin</p></div></div>
              <span class="px-3 py-1 bg-green-500/15 text-green-400 text-xs rounded-full font-medium">Approved</span>
            </div>
            <div class="flex items-center justify-between p-4 bg-black border border-border rounded-xl">
              <div class="flex items-center"><span class="text-green-400 mr-3">✓</span><div><p class="text-white text-sm font-medium">Background Check</p><p class="text-muted text-xs">Reviewed by Penguins Admin</p></div></div>
              <span class="px-3 py-1 bg-green-500/15 text-green-400 text-xs rounded-full font-medium">Approved</span>
            </div>
          </div>
          <div class="mt-6 p-4 bg-black border border-border rounded-xl">
            <p class="text-muted text-sm text-center">⚠️ You cannot upload or modify verification documents. Contact support@penguins.app for changes.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== ACCOUNT ===== -->
    <div id="page-account" class="page">
      <header class="mb-8"><h2 class="display text-3xl font-bold text-white">Account</h2><p class="text-muted mt-1">Manage your coach profile and business details.</p></header>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="card rounded-2xl p-6 text-center">
          <div id="account-avatar" class="w-24 h-24 rounded-full bg-accent flex items-center justify-center text-white font-bold text-3xl mx-auto mb-4">JD</div>
          <h3 id="account-name-display" class="text-white font-semibold text-lg">Jordan Diaz</h3>
          <p id="account-niche-display" class="text-muted text-sm">Fitness / Strength</p>
          <div class="mt-4 pt-4 border-t border-border text-left space-y-2">
            <div class="flex justify-between text-sm"><span class="text-muted">Plan</span><span class="text-white font-medium">Free</span></div>
            <div class="flex justify-between text-sm"><span class="text-muted">Clients</span><span id="account-clients-count" class="text-white font-medium">0 active</span></div>
            <div class="flex justify-between text-sm"><span class="text-muted">Member since</span><span id="account-member-since" class="text-white font-medium">—</span></div>
          </div>
        </div>
        <div class="lg:col-span-2 space-y-6">
          <div id="account-pending-banner" class="hidden"></div>
          <div class="card rounded-2xl p-6">
            <h3 class="display text-lg font-semibold text-white mb-1">Profile Settings</h3>
            <p class="text-muted text-xs mb-4">Changes here are submitted as a request — nothing updates on your live profile until Penguins reviews and approves it.</p>
            <form id="form-account" class="space-y-4">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="text-xs text-muted mb-1 block">Full Name</label><input type="text" name="name" class="w-full input-dark rounded-lg px-4 py-2 text-sm"></div>
                <div><label class="text-xs text-muted mb-1 block">Email</label><input type="email" name="email" class="w-full input-dark rounded-lg px-4 py-2 text-sm"></div>
              </div>
              <div><label class="text-xs text-muted mb-1 block">Coaching Niche</label>
                <select name="niche" class="w-full input-dark rounded-lg px-4 py-2 text-sm"><option>Fitness / Strength</option><option>Life & Mindset</option><option>Business / Executive</option><option>Nutrition</option><option>Other</option></select>
              </div>
              <div><label class="text-xs text-muted mb-1 block">Bio</label><textarea name="bio" rows="4" class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="Tell clients about your approach..."></textarea></div>
              <div><label class="text-xs text-muted mb-1 block">Hourly Rate ($)</label><input type="number" name="rate" step="1" class="w-full input-dark rounded-lg px-4 py-2 text-sm"></div>
              <button type="submit" id="account-submit-btn" class="px-6 py-2.5 bg-accent text-white font-semibold rounded-lg hover:bg-indigo-500 transition glow">Submit for Approval</button>
            </form>
          </div>
          <div class="card rounded-2xl p-6">
            <h3 class="display text-lg font-semibold text-white mb-1">Appearance</h3>
            <p class="text-muted text-xs mb-4">Pick an accent color for your dashboard. Purple is the default — pick another any time.</p>
            <div id="theme-swatches" class="flex flex-wrap gap-3"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== CLIENTS (CRM) ===== -->
    <div id="page-clients" class="page">
      <header class="mb-6"><h2 class="display text-3xl font-bold text-white">Clients</h2><p class="text-muted mt-1">Every client, in one place. Click a card for the full profile.</p></header>
      <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <input type="text" id="client-search" oninput="renderClients()" placeholder="Search clients…" class="flex-1 input-dark rounded-lg px-4 py-2 text-sm">
        <select id="client-filter" onchange="renderClients()" class="input-dark rounded-lg px-4 py-2 text-sm">
          <option value="All">All Statuses</option>
          <option value="Active">Active</option>
          <option value="Paused">Paused</option>
        </select>
      </div>
      <div id="list-clients" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"></div>
    </div>

    <!-- ===== PIPELINE (LEADS) ===== -->
    <div id="page-pipeline" class="page">
      <header class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div><h2 class="display text-3xl font-bold text-white">Pipeline</h2><p class="text-muted mt-1">Track prospects from first contact to signed client. Drag cards between stages.</p></div>
        <button onclick="openAddLead()" class="px-4 py-2 bg-accent text-white text-sm font-semibold rounded-lg hover:bg-indigo-500 transition glow whitespace-nowrap">+ Add Lead</button>
      </header>
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4" id="pipeline-board"></div>
    </div>

    <!-- ===== INVOICES ===== -->
    <div id="page-invoices" class="page">
      <header class="mb-8"><h2 class="display text-3xl font-bold text-white">Invoices</h2><p class="text-muted mt-1">Track what's owed, paid, and overdue.</p></header>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
        <div class="card rounded-2xl p-6 text-center"><p class="text-muted text-sm">Outstanding</p><h3 class="display text-3xl font-bold text-gold mt-2" id="inv-outstanding">$0</h3></div>
        <div class="card rounded-2xl p-6 text-center"><p class="text-muted text-sm">Paid This Month</p><h3 class="display text-3xl font-bold text-green-400 mt-2" id="inv-paid">$0</h3></div>
        <div class="card rounded-2xl p-6 text-center"><p class="text-muted text-sm">Overdue</p><h3 class="display text-3xl font-bold text-red-400 mt-2" id="inv-overdue">$0</h3></div>
      </div>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="card rounded-2xl p-6">
          <h3 class="display text-lg font-semibold text-white mb-4">New Invoice</h3>
          <form id="form-invoice" class="space-y-4">
            <div><label class="text-xs text-muted mb-1 block">Client</label><select name="client" class="w-full input-dark rounded-lg px-4 py-2 text-sm"><option>Alex M.</option><option>Sarah K.</option><option>Mike T.</option><option>Emma R.</option></select></div>
            <div><label class="text-xs text-muted mb-1 block">Description</label><input type="text" name="desc" required class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="e.g. March coaching package"></div>
            <div><label class="text-xs text-muted mb-1 block">Amount ($)</label><input type="number" name="amount" step="0.01" required class="w-full input-dark rounded-lg px-4 py-2 text-sm"></div>
            <div><label class="text-xs text-muted mb-1 block">Due Date</label><input type="date" name="due" required class="w-full input-dark rounded-lg px-4 py-2 text-sm"></div>
            <button type="submit" class="w-full py-2.5 bg-accent text-white font-semibold rounded-lg hover:bg-indigo-500 transition glow">Create Invoice</button>
          </form>
        </div>
        <div class="lg:col-span-2 card rounded-2xl p-6">
          <h3 class="display text-lg font-semibold text-white mb-4">All Invoices</h3>
          <div id="list-invoices" class="space-y-3 max-h-[520px] overflow-y-auto thin-scroll pr-2"></div>
        </div>
      </div>
    </div>

    <!-- ===== COHORTS (GROUP COACHING) ===== -->
    <div id="page-cohorts" class="page">
      <header class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div><h2 class="display text-3xl font-bold text-white">Cohorts</h2><p class="text-muted mt-1">Run group programs alongside your 1:1 clients.</p></div>
        <button onclick="openAddCohort()" class="px-4 py-2 bg-accent text-white text-sm font-semibold rounded-lg hover:bg-indigo-500 transition glow whitespace-nowrap">+ New Cohort</button>
      </header>
      <div id="list-cohorts" class="grid grid-cols-1 lg:grid-cols-2 gap-5"></div>
    </div>

    <!-- ===== AUTOMATIONS ===== -->
    <div id="page-automations" class="page">
      <header class="mb-8"><h2 class="display text-3xl font-bold text-white">Automations</h2><p class="text-muted mt-1">Set rules once, let the platform handle the follow-up.</p></header>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="card rounded-2xl p-6">
          <h3 class="display text-lg font-semibold text-white mb-4">New Rule</h3>
          <form id="form-automation" class="space-y-4">
            <div><label class="text-xs text-muted mb-1 block">When</label>
              <select name="trigger" class="w-full input-dark rounded-lg px-4 py-2 text-sm">
                <option>Client misses a check-in</option>
                <option>Readiness flagged red</option>
                <option>Invoice becomes overdue</option>
                <option>New lead enters pipeline</option>
                <option>Client hits a 7-day streak</option>
              </select>
            </div>
            <div><label class="text-xs text-muted mb-1 block">Then</label>
              <select name="action" class="w-full input-dark rounded-lg px-4 py-2 text-sm">
                <option>Send a check-in reminder message</option>
                <option>Send an encouragement message</option>
                <option>Notify me only</option>
                <option>Send a payment reminder</option>
                <option>Post a shoutout in Community</option>
              </select>
            </div>
            <button type="submit" class="w-full py-2.5 bg-accent text-white font-semibold rounded-lg hover:bg-indigo-500 transition glow">Create Rule</button>
          </form>
        </div>
        <div class="lg:col-span-2 card rounded-2xl p-6">
          <h3 class="display text-lg font-semibold text-white mb-4">Active Rules</h3>
          <div id="list-automations" class="space-y-3"></div>
        </div>
      </div>
    </div>

    <!-- ===== REPORTS ===== -->
    <div id="page-reports" class="page">
      <header class="mb-8"><h2 class="display text-3xl font-bold text-white">Reports</h2><p class="text-muted mt-1">Retention, engagement, and revenue at a glance.</p></header>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6" id="reports-stats"></div>
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card rounded-2xl p-6">
          <h3 class="display text-lg font-semibold text-white mb-4">Client Retention</h3>
          <div class="h-56"><canvas id="retentionChart"></canvas></div>
        </div>
        <div class="card rounded-2xl p-6">
          <h3 class="display text-lg font-semibold text-white mb-4">Engagement (Avg Habit Completion)</h3>
          <div class="h-56"><canvas id="engagementChart"></canvas></div>
        </div>
      </div>
    </div>

    <!-- ===== CONTRACTS ===== -->
    <div id="page-contracts" class="page">
      <header class="mb-8"><h2 class="display text-3xl font-bold text-white">Contracts</h2><p class="text-muted mt-1">Send agreements and waivers for clients to sign electronically.</p></header>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="card rounded-2xl p-6">
          <h3 class="display text-lg font-semibold text-white mb-4">Send a Contract</h3>
          <form id="form-contract" class="space-y-4">
            <select name="client" class="w-full input-dark rounded-lg px-4 py-2 text-sm"><option>Alex M.</option><option>Sarah K.</option><option>Mike T.</option><option>Emma R.</option></select>
            <select name="title" class="w-full input-dark rounded-lg px-4 py-2 text-sm">
              <option>Coaching Agreement</option>
              <option>Liability Waiver</option>
              <option>Nutrition Disclaimer</option>
              <option>Custom Agreement</option>
            </select>
            <button type="submit" class="w-full py-2.5 bg-accent text-white font-semibold rounded-lg hover:bg-indigo-500 transition glow">Send for Signature</button>
          </form>
        </div>
        <div class="lg:col-span-2 card rounded-2xl p-6">
          <h3 class="display text-lg font-semibold text-white mb-4">Sent Contracts</h3>
          <div id="list-contracts" class="space-y-3"></div>
        </div>
      </div>
    </div>

  </main>
  </div>
</div>

<!-- ================= ADD COHORT MODAL ================= -->
<div id="cohortModal" class="modal-overlay hidden" onclick="if(event.target===this)closeAddCohort()">
  <div class="card rounded-2xl w-full max-w-md mx-4 p-6">
    <h3 class="display text-lg font-semibold text-white mb-4">New Cohort</h3>
    <form id="form-cohort" class="space-y-4">
      <input type="text" name="name" required class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="Cohort name, e.g. Spring Shred Squad">
      <input type="text" name="schedule" class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="Schedule, e.g. Tue & Thu, 6PM">
      <div>
        <label class="text-xs text-muted mb-1 block">Members</label>
        <div class="space-y-1.5" id="cohort-member-checks"></div>
      </div>
      <div class="flex gap-2">
        <button type="button" onclick="closeAddCohort()" class="flex-1 py-2.5 bg-black border border-border text-muted font-medium rounded-lg hover:text-white transition">Cancel</button>
        <button type="submit" class="flex-1 py-2.5 bg-accent text-white font-semibold rounded-lg hover:bg-indigo-500 transition glow">Create</button>
      </div>
    </form>
  </div>
</div>

<!-- ================= NUTRITION TARGETS MODAL ================= -->
<div id="nutritionModal" class="modal-overlay hidden" onclick="if(event.target===this)closeNutritionModal()">
  <div class="card rounded-2xl w-full max-w-md mx-4 p-6">
    <h3 class="display text-lg font-semibold text-white mb-1">Set Macro Targets</h3>
    <p class="text-muted text-xs mb-4" id="nutrition-modal-client"></p>
    <form id="form-nutrition-targets" class="space-y-4">
      <div class="grid grid-cols-2 gap-3">
        <div><label class="text-xs text-muted mb-1 block">Calories</label><input type="number" name="calories" required class="w-full input-dark rounded-lg px-4 py-2 text-sm"></div>
        <div><label class="text-xs text-muted mb-1 block">Protein (g)</label><input type="number" name="protein" required class="w-full input-dark rounded-lg px-4 py-2 text-sm"></div>
        <div><label class="text-xs text-muted mb-1 block">Carbs (g)</label><input type="number" name="carbs" required class="w-full input-dark rounded-lg px-4 py-2 text-sm"></div>
        <div><label class="text-xs text-muted mb-1 block">Fats (g)</label><input type="number" name="fats" required class="w-full input-dark rounded-lg px-4 py-2 text-sm"></div>
      </div>
      <div><label class="text-xs text-muted mb-1 block">Notes for client</label><textarea name="notes" rows="2" class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="Any guidance to include..."></textarea></div>
      <div class="flex gap-2">
        <button type="button" onclick="closeNutritionModal()" class="flex-1 py-2.5 bg-black border border-border text-muted font-medium rounded-lg hover:text-white transition">Cancel</button>
        <button type="submit" class="flex-1 py-2.5 bg-accent text-white font-semibold rounded-lg hover:bg-indigo-500 transition glow">Save Targets</button>
      </div>
    </form>
  </div>
</div>

<!-- ================= CLIENT PR MODAL ================= -->
<div id="clientPrModal" class="modal-overlay hidden" onclick="if(event.target===this)closeClientPRModal()">
  <div class="card rounded-2xl w-full max-w-md mx-4 p-6">
    <h3 class="display text-lg font-semibold text-white mb-4">Log a Personal Record</h3>
    <form id="form-client-pr" class="space-y-4">
      <input type="text" name="exercise" required class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="Exercise, e.g. Deadlift">
      <div class="grid grid-cols-2 gap-3">
        <input type="number" step="0.5" name="weight" required class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="Weight (kg)">
        <input type="number" name="reps" required class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="Reps">
      </div>
      <div class="flex gap-2">
        <button type="button" onclick="closeClientPRModal()" class="flex-1 py-2.5 bg-black border border-border text-muted font-medium rounded-lg hover:text-white transition">Cancel</button>
        <button type="submit" class="flex-1 py-2.5 bg-accent text-white font-semibold rounded-lg hover:bg-indigo-500 transition glow">Save PR</button>
      </div>
    </form>
  </div>
</div>

<!-- ================= CLIENT GOAL MODAL ================= -->
<div id="clientGoalModal" class="modal-overlay hidden" onclick="if(event.target===this)closeClientGoalModal()">
  <div class="card rounded-2xl w-full max-w-md mx-4 p-6">
    <h3 class="display text-lg font-semibold text-white mb-4">Add a Client Goal</h3>
    <form id="form-client-goal" class="space-y-4">
      <input type="text" name="title" required class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="Goal, e.g. Bench Press 100kg">
      <div class="grid grid-cols-2 gap-3">
        <input type="number" step="0.1" name="current" required class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="Current value">
        <input type="number" step="0.1" name="target" required class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="Target value">
      </div>
      <input type="text" name="unit" class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="Unit (e.g. kg, days, reps)">
      <div class="flex gap-2">
        <button type="button" onclick="closeClientGoalModal()" class="flex-1 py-2.5 bg-black border border-border text-muted font-medium rounded-lg hover:text-white transition">Cancel</button>
        <button type="submit" class="flex-1 py-2.5 bg-accent text-white font-semibold rounded-lg hover:bg-indigo-500 transition glow">Add Goal</button>
      </div>
    </form>
  </div>
</div>

<!-- ================= EDIT PRODUCT MODAL ================= -->
<div id="editProductModal" class="modal-overlay hidden" onclick="if(event.target===this)closeEditProduct()">
  <div class="card rounded-2xl w-full max-w-md mx-4 p-6">
    <h3 class="display text-lg font-semibold text-white mb-4">Edit Product</h3>
    <form id="form-edit-product" class="space-y-3">
      <input type="text" name="name" required class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="Product Name">
      <div class="grid grid-cols-2 gap-3">
        <input type="number" name="price" step="0.01" required class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="Price ($)">
        <input type="number" name="salePrice" step="0.01" class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="Sale price">
      </div>
      <select name="category" class="w-full input-dark rounded-lg px-4 py-2 text-sm"><option>Merch</option><option>Digital Product</option><option>Coaching Package</option><option>Brand Collab</option></select>
      <div class="grid grid-cols-2 gap-3">
        <input type="number" name="stock" class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="Stock">
        <label class="flex items-center gap-2 px-3 bg-black border border-border rounded-lg text-xs text-muted"><input type="checkbox" name="featured" class="accent-accent"> Featured</label>
      </div>
      <input type="url" name="image" class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="Image URL">
      <div class="flex gap-2">
        <button type="button" onclick="closeEditProduct()" class="flex-1 py-2.5 bg-black border border-border text-muted font-medium rounded-lg hover:text-white transition">Cancel</button>
        <button type="submit" class="flex-1 py-2.5 bg-accent text-white font-semibold rounded-lg hover:bg-indigo-500 transition glow">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- ================= BUSINESS GOALS MODAL ================= -->
<div id="goalsModal" class="modal-overlay hidden" onclick="if(event.target===this)closeGoalsModal()">
  <div class="card rounded-2xl w-full max-w-sm mx-4 p-6">
    <h3 class="display text-lg font-semibold text-white mb-4">Set Business Targets</h3>
    <form id="form-business-goals" class="space-y-4">
      <div><label class="text-xs text-muted mb-1 block">Monthly Revenue Target ($)</label><input type="number" name="revenueTarget" required class="w-full input-dark rounded-lg px-4 py-2 text-sm"></div>
      <div><label class="text-xs text-muted mb-1 block">Active Clients Target</label><input type="number" name="clientsTarget" required class="w-full input-dark rounded-lg px-4 py-2 text-sm"></div>
      <div class="flex gap-2">
        <button type="button" onclick="closeGoalsModal()" class="flex-1 py-2.5 bg-black border border-border text-muted font-medium rounded-lg hover:text-white transition">Cancel</button>
        <button type="submit" class="flex-1 py-2.5 bg-accent text-white font-semibold rounded-lg hover:bg-indigo-500 transition glow">Save</button>
      </div>
    </form>
  </div>
</div>
<div id="clientPreviewBar" class="client-preview-bar hidden">You're viewing Penguins as a client would see it — <button onclick="closeClientPreview()" class="underline font-bold">Exit Preview</button></div>
<div id="clientPreviewOverlay" class="modal-overlay hidden" style="padding-top:4vh;">
  <div class="card rounded-2xl w-full max-w-lg mx-4 max-h-[88vh] overflow-y-auto thin-scroll">
    <div id="clientPreviewBody" class="p-6"></div>
  </div>
</div>

<!-- ================= WELCOME / FIRST-TIME TOUR ================= -->
<div id="welcomeModal" class="modal-overlay hidden">
  <div class="card rounded-2xl w-full max-w-lg mx-4 p-7">
    <h2 class="display text-2xl font-bold text-white mb-1">Welcome to Penguins 🐧</h2>
    <p class="text-muted text-sm mb-5">Here's a 30-second overview before you dive in.</p>
    <div class="space-y-3 mb-6">
      <div class="flex items-start gap-3"><span class="text-accent">◆</span><p class="text-sm text-text"><b class="text-white">Dashboard</b> shows your earnings, activity, and Smart Insights — auto-flagged things worth your attention.</p></div>
      <div class="flex items-start gap-3"><span class="text-accent">◇</span><p class="text-sm text-text"><b class="text-white">Clients</b> is your CRM — click any client for their full history in one place.</p></div>
      <div class="flex items-start gap-3"><span class="text-accent">▤</span><p class="text-sm text-text"><b class="text-white">Plan Builder, Cohorts, Automations</b> and more help you run your coaching business day to day.</p></div>
      <div class="flex items-start gap-3"><span class="text-accent">?</span><p class="text-sm text-text">Stuck on a page? Click <b class="text-white">Help & Guide</b> in the sidebar any time, or press <kbd>⌘K</kbd> to jump anywhere fast.</p></div>
    </div>
    <button onclick="closeWelcome()" class="w-full py-2.5 bg-accent text-white font-semibold rounded-lg hover:bg-indigo-500 transition glow">Let's go →</button>
  </div>
</div>

<!-- ================= HELP & GUIDE ================= -->
<div id="helpOverlay" class="modal-overlay hidden" onclick="if(event.target===this)closeHelp()">
  <div class="card rounded-2xl w-full max-w-2xl mx-4 max-h-[80vh] overflow-y-auto thin-scroll p-6">
    <div class="flex items-center justify-between mb-5">
      <h3 class="display text-xl font-bold text-white">Help & Guide</h3>
      <button onclick="closeHelp()" class="text-muted hover:text-white text-xl leading-none">×</button>
    </div>
    <div id="help-content" class="space-y-4"></div>
  </div>
</div>

<!-- ================= TOASTS ================= -->
<div id="toastWrap" class="toast-wrap"></div>

<!-- ================= CLIENT DETAIL MODAL ================= -->
<div id="clientModal" class="modal-overlay hidden" onclick="if(event.target===this)closeClientModal()">
  <div class="card rounded-2xl w-full max-w-2xl mx-4 max-h-[80vh] overflow-y-auto thin-scroll">
    <div id="clientModalBody" class="p-6"></div>
  </div>
</div>

<!-- ================= ADD LEAD MODAL ================= -->
<div id="leadModal" class="modal-overlay hidden" onclick="if(event.target===this)closeAddLead()">
  <div class="card rounded-2xl w-full max-w-md mx-4 p-6">
    <h3 class="display text-lg font-semibold text-white mb-4">Add Lead</h3>
    <form id="form-lead" class="space-y-4">
      <input type="text" name="name" required class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="Full name">
      <input type="email" name="email" class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="Email (optional)">
      <input type="number" name="value" step="0.01" class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="Potential value ($)">
      <textarea name="notes" rows="3" class="w-full input-dark rounded-lg px-4 py-2 text-sm" placeholder="Notes"></textarea>
      <div class="flex gap-2">
        <button type="button" onclick="closeAddLead()" class="flex-1 py-2.5 bg-black border border-border text-muted font-medium rounded-lg hover:text-white transition">Cancel</button>
        <button type="submit" class="flex-1 py-2.5 bg-accent text-white font-semibold rounded-lg hover:bg-indigo-500 transition glow">Add Lead</button>
      </div>
    </form>
  </div>
</div>

<!-- ================= COMMAND PALETTE ================= -->
<div id="paletteOverlay" class="modal-overlay hidden" onclick="if(event.target===this)closePalette()">
  <div class="card rounded-2xl w-full max-w-lg overflow-hidden">
    <input id="paletteInput" type="text" placeholder="Search pages, clients, actions…" class="w-full bg-card px-5 py-4 text-white text-sm outline-none border-b border-border sticky top-0" oninput="filterPalette()">
    <div id="paletteResults" class="py-2"></div>
  </div>
</div>

<script>
// ==========================================
// STORAGE
// ==========================================
const DATA_KEY = 'penguins_platform_data';
let db = null;

function defaultData() {
  return {
    seenWelcome: false,
    colorMode: 'dark',
    theme: { accent: '#6C5CE7', gold: '#F5B942' },
    lang: 'en',
    tasks: [
      { id: 1, text: 'Review this week\'s check-ins', done: false },
      { id: 2, text: 'Follow up with Liv Andersen (Pipeline)', done: false }
    ],
    account: { name: 'Jordan Diaz', email: '', niche: 'Fitness / Strength', bio: '', rate: 75 },
    pendingAccountChange: null,
    plans: [
      { id: 1, client: 'Alex M.', title: 'Week 3 - Hypertrophy Push', type: 'Training', details: 'Bench Press 4x8\nOHP 3x10\nLateral Raises 3x15\nTricep Dips 3x12', sent: '2 days ago' },
      { id: 2, client: 'Sarah K.', title: 'Nutrition Plan - Cut Phase', type: 'Nutrition', details: 'Calories: 1,800\nProtein: 150g\nCarbs: 120g\nFats: 55g', sent: '5 days ago' }
    ],
    shop: [
      { id: 1, name: 'Elite Shaker Bottle', price: 29.99, salePrice: null, category: 'Merch', image: '', stock: 14, featured: true, sold: 37 },
      { id: 2, name: '12-Week PDF Program', price: 49.99, salePrice: 34.99, category: 'Digital Product', image: '', stock: null, featured: true, sold: 52 },
      { id: 3, name: '1:1 Coaching — Monthly', price: 300, salePrice: null, category: 'Coaching Package', image: '', stock: null, featured: false, sold: 9 }
    ],
    sessions: [
      { id: 1, topic: 'Monthly Q&A - Form Check', time: '2025-02-15T18:00', access: 'All Clients' }
    ],
    feedback: [
      { id: 1, client: 'Alex M.', msg: 'The new push plan is insane, feeling the pump every session!', rating: 5, date: '2h ago' },
      { id: 2, client: 'Sarah K.', msg: 'Can we swap deadlifts for RDLs? Lower back is tight.', rating: 4, date: '1d ago' },
      { id: 3, client: 'Mike T.', msg: 'Down 4 lbs this week! Let\'s keep the momentum going.', rating: 5, date: '2d ago' }
    ],
    community: [
      { id: 1, user: 'Coach', type: 'Challenge', text: '🏆 Weekend Challenge: 10,000 steps Saturday AND Sunday. Post screenshots!', likes: 24, date: '3h ago' },
      { id: 2, user: 'Sarah K.', type: 'General Post', text: 'Just hit a new PR on bench! 135 lbs for 5! 🎉', likes: 18, date: '1h ago' },
      { id: 3, user: 'Mike T.', type: 'General Post', text: 'Meal prep Sunday done. 5 days of chicken & rice ready. 💪', likes: 9, date: '5h ago' }
    ],
    submissions: [
      { id: 1, client: 'Alex M.', exercise: 'Barbell Squat', date: '1h ago', status: 'Pending Review' },
      { id: 2, client: 'Sarah K.', exercise: 'Romanian Deadlift', date: '3h ago', status: 'Reviewed' },
      { id: 3, client: 'Mike T.', exercise: 'Overhead Press', date: '1d ago', status: 'Reviewed' }
    ],
    pointsClients: [
      { name: 'Alex M.', points: 340, streak: 12, completion: 92 },
      { name: 'Sarah K.', points: 285, streak: 8, completion: 85 },
      { name: 'Mike T.', points: 210, streak: 5, completion: 71 },
      { name: 'Emma R.', points: 190, streak: 4, completion: 68 }
    ],
    readiness: [
      { name: 'Alex M.', sleep: 8, sore: 3, stress: 2, status: 'green' },
      { name: 'Sarah K.', sleep: 5, sore: 7, stress: 6, status: 'red' },
      { name: 'Mike T.', sleep: 7, sore: 5, stress: 4, status: 'yellow' },
      { name: 'Emma R.', sleep: 9, sore: 2, stress: 3, status: 'green' }
    ],
    referrals: { clicks: 247, signups: 31 },
    clients: [
      { name: 'Alex M.', email: 'alex.m@example.com', phone: '', status: 'Active', tag: 'Fitness', joined: 'Jan 2025', notes: 'Prefers morning sessions. Training for a powerlifting meet.', nutrition: false },
      { name: 'Sarah K.', email: 'sarah.k@example.com', phone: '', status: 'Active', tag: 'Nutrition', joined: 'Feb 2025', notes: 'Cutting phase, sensitive lower back — avoid heavy deadlifts.', nutrition: true },
      { name: 'Mike T.', email: 'mike.t@example.com', phone: '', status: 'Active', tag: 'Fitness', joined: 'Nov 2024', notes: 'Consistent, responds well to public accountability.', nutrition: false },
      { name: 'Emma R.', email: 'emma.r@example.com', phone: '', status: 'Paused', tag: 'Mindset', joined: 'Mar 2025', notes: 'Paused for travel, resuming next month.', nutrition: false }
    ],
    nutritionTargets: {
      'Sarah K.': { calories: 1800, protein: 150, carbs: 120, fats: 55, notes: 'Cut phase — prioritize protein, keep carbs around training days.' }
    },
    nutritionLogs: {
      'Sarah K.': [
        { date: 'Jul 30', calories: 1780, protein: 148, carbs: 115, fats: 52 },
        { date: 'Jul 31', calories: 1820, protein: 155, carbs: 125, fats: 54 }
      ]
    },
    planTemplates: [
      { id: 1, title: 'Beginner Full Body (3x/wk)', type: 'Training', details: 'Barbell Back Squat: 3 x 8\nBench Press: 3 x 8\nBarbell Row: 3 x 8\nPlank: 3 x 45s' },
      { id: 2, title: 'Cut Phase Nutrition Baseline', type: 'Nutrition', details: 'Calories: 1,800\nProtein: 150g\nCarbs: 120g\nFats: 55g' }
    ],
    sessionNotes: {
      'Alex M.': [ { date: '3 days ago', text: 'Squat form improving — depth still slightly short, cue "sit back more."' } ]
    },
    clientPRs: {
      'Alex M.': [ { id: 1, exercise: 'Back Squat', weight: 120, reps: 3, date: '2026-07-15' }, { id: 2, exercise: 'Bench Press', weight: 85, reps: 5, date: '2026-07-22' } ]
    },
    clientGoals: {
      'Alex M.': [ { id: 1, title: 'Back Squat 1RM', current: 130, startValue: 110, target: 150, unit: 'kg' } ]
    },
    businessGoals: { revenueTarget: 8000, clientsTarget: 6 },
    leads: [
      { id: 1, name: 'Priya Shah', email: 'priya@example.com', stage: 'New', value: 450, notes: 'Found us via Instagram.', date: '2 days ago' },
      { id: 2, name: 'Daniel Cho', email: 'daniel@example.com', stage: 'Contacted', value: 900, notes: 'Interested in the 12-week program.', date: '4 days ago' },
      { id: 3, name: 'Liv Andersen', email: 'liv@example.com', stage: 'Proposal Sent', value: 1200, notes: 'Sent quote for quarterly package.', date: '1 week ago' },
      { id: 4, name: 'Marcus Webb', email: 'marcus@example.com', stage: 'Won', value: 600, notes: 'Signed up, onboarding next week.', date: '2 weeks ago' }
    ],
    invoices: [
      { id: 1, client: 'Alex M.', desc: 'March coaching package', amount: 300, status: 'Paid', due: '2025-03-01' },
      { id: 2, client: 'Sarah K.', desc: 'Nutrition consult', amount: 150, status: 'Unpaid', due: '2026-08-05' },
      { id: 3, client: 'Mike T.', desc: 'April coaching package', amount: 300, status: 'Overdue', due: '2026-06-15' }
    ],
    cohorts: [
      { id: 1, name: 'Spring Shred Squad', schedule: 'Tue & Thu, 6PM', members: ['Alex M.','Mike T.'] },
      { id: 2, name: 'Mindset Mornings', schedule: 'Daily, 7AM', members: ['Sarah K.','Emma R.'] }
    ],
    automations: [
      { id: 1, trigger: 'Client misses a check-in', action: 'Send a check-in reminder message', enabled: true },
      { id: 2, trigger: 'Readiness flagged red', action: 'Notify me only', enabled: true },
      { id: 3, trigger: 'Invoice becomes overdue', action: 'Send a payment reminder', enabled: false }
    ],
    contracts: [
      { id: 1, client: 'Alex M.', title: 'Coaching Agreement', status: 'Signed', date: '2 weeks ago' },
      { id: 2, client: 'Emma R.', title: 'Liability Waiver', status: 'Sent', date: '3 days ago' }
    ],
    metrics: {
      'Alex M.': [ {date:'Jun 1', weight:182, waist:34}, {date:'Jun 15', weight:179, waist:33.5}, {date:'Jul 1', weight:176.5, waist:33}, {date:'Jul 15', weight:174, waist:32.5} ],
      'Sarah K.': [ {date:'Jun 1', weight:148, waist:29}, {date:'Jun 15', weight:146, waist:28.5}, {date:'Jul 1', weight:144.5, waist:28} ],
      'Mike T.': [ {date:'Jun 1', weight:210, waist:38}, {date:'Jul 1', weight:206, waist:37} ],
      'Emma R.': [ {date:'Jun 1', weight:135, waist:27} ]
    },
    slots: [
      { id: 1, time: '2026-08-01T09:00', booked: false, client: null },
      { id: 2, time: '2026-08-01T14:00', booked: true, client: 'Sarah K.' }
    ],
    library: [
      { id: 1, title: 'Foundations: First 30 Days', desc: 'A starter guide for new clients.', tier: 'Free', price: 0, filename: 'foundations-30-days.pdf', downloads: 58 },
      { id: 2, title: 'Advanced Strength Blueprint', desc: '12-week periodized strength program.', tier: 'Paid', price: 39.99, filename: 'advanced-strength-blueprint.pdf', downloads: 21 }
    ],
    chats: {
      'Alex M.': [
        { from: 'them', text: 'Hey! Quick question about today\'s session.', time: '9:12 AM' },
        { from: 'me', text: 'Of course, what\'s up?', time: '9:14 AM' },
        { from: 'them', text: 'Should I go heavier on squats this week?', time: '9:15 AM' }
      ],
      'Sarah K.': [
        { from: 'them', text: 'Loved the new nutrition plan, thank you!', time: 'Yesterday' }
      ],
      'Mike T.': [
        { from: 'them', text: 'Down 4 lbs this week 🎉', time: '2 days ago' }
      ],
      'Emma R.': []
    },
    activeChat: 'Alex M.'
  };
}

function blankData(name, email, niche) {
  const d = defaultData();
  d.account = { name: name || 'New Coach', email: email || '', niche: niche || 'Fitness / Strength', bio: '', rate: 0 };
  d.pendingAccountChange = null;
  d.clients = [];
  d.leads = [];
  d.invoices = [];
  d.plans = [];
  d.planTemplates = [];
  d.sessions = [];
  d.slots = [];
  d.feedback = [];
  d.submissions = [];
  d.pointsClients = [];
  d.readiness = [];
  d.cohorts = [];
  d.automations = [];
  d.contracts = [];
  d.community = [];
  d.shop = [];
  d.library = [];
  d.chats = {};
  d.activeChat = null;
  d.nutritionTargets = {};
  d.nutritionLogs = {};
  d.sessionNotes = {};
  d.clientPRs = {};
  d.clientGoals = {};
  d.tasks = [];
  d.referrals = { clicks: 0, signups: 0 };
  d.businessGoals = { revenueTarget: 0, clientsTarget: 0 };
  d.seenWelcome = false;
  return d;
}

let csrfToken = document.body.dataset.csrf || '';
let dataLoaded = false, unsavedChanges = false;
window.addEventListener('beforeunload', event => {
  if (unsavedChanges) { event.preventDefault(); event.returnValue = ''; }
});
async function logOut() {
  if (unsavedChanges && !confirm('Some changes have not saved. Log out anyway?')) return;
  const response = await fetch('logout.php', { method: 'POST', headers: { 'X-CSRF-Token': csrfToken } });
  if (response.ok) { unsavedChanges = false; location.href = 'auth.php'; }
  else toast('Could not log out. Please try again.');
}

async function loadData() {
  try {
    const res = await fetch('get_data.php', { credentials: 'same-origin' });
    if (res.status === 401) { window.location.href = 'auth.php'; return; }
    if (!res.ok) throw new Error('Failed to load: ' + res.status);
    db = await res.json();
    dataLoaded = true;
    if (db._csrf) { csrfToken = db._csrf; delete db._csrf; }
  } catch (e) {
    console.error('Could not load dashboard data from the server:', e);
    toast('⚠️ Could not reach the server — check that PHP is running.');
    document.body.replaceChildren();
    const panel = document.createElement('main');
    panel.style.cssText = 'max-width:600px;margin:15vh auto;padding:32px;font-family:system-ui;color:white';
    const heading = document.createElement('h1'); heading.textContent = 'Your workspace could not load';
    const message = document.createElement('p'); message.textContent = 'Your saved data has not been changed. Check your connection and try again.';
    const retry = document.createElement('button'); retry.textContent = 'Try again'; retry.onclick = () => location.reload();
    panel.append(heading, message, retry); document.body.append(panel);
    throw e;
  }
  if (!db.lang) db.lang = 'en';
  if (!db.colorMode) db.colorMode = 'dark';
  if (!db.theme) db.theme = { accent: '#6C5CE7', gold: '#F5B942' };
  if (!db.nutritionTargets) db.nutritionTargets = {};
  if (!db.nutritionLogs) db.nutritionLogs = {};
  if (!db.planTemplates) db.planTemplates = [];
  if (!db.sessionNotes) db.sessionNotes = {};
  if (!db.businessGoals) db.businessGoals = { revenueTarget: 0, clientsTarget: 0 };
  if (!db.clientPRs) db.clientPRs = {};
  if (!db.clientGoals) db.clientGoals = {};
  if (db.shop) db.shop.forEach(p => {
    if (p.salePrice === undefined) p.salePrice = null;
    if (p.stock === undefined) p.stock = null;
    if (p.featured === undefined) p.featured = false;
    if (p.sold === undefined) p.sold = 0;
  });
}
let saveInFlight = false, savePending = false;
async function saveData() {
  if (!dataLoaded) return;
  unsavedChanges = true;
  if (saveInFlight) { savePending = true; return; }
  saveInFlight = true;
  try {
    const res = await fetch('save_data.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
      body: JSON.stringify(db)
    });
    if (res.status === 401) { window.location.href = 'auth.php'; return; }
    if (res.status === 403) { toast('⚠️ Security check expired — refreshing…'); setTimeout(()=>location.reload(), 1200); return; }
    if (!res.ok) throw new Error('Save failed: ' + res.status);
    const result = await res.json();
    if (!result.ok) throw new Error('Save was rejected');
    unsavedChanges = savePending;
  } catch (e) {
    console.error('Storage error:', e);
    toast('⚠️ Changes may not have saved — check your connection.');
  } finally {
    saveInFlight = false;
    if (savePending) { savePending = false; saveData(); }
  }
}

// ==========================================
// APP ENTRY
// ==========================================
async function enterApp() {
  await loadData();
  applyTheme();
  applyColorMode();
  renderAll();
  buildChart();
  setLiveView('list');
  setPlanTab('sent');
  maybeShowWelcome();
}

// ==========================================
// THEME (accent color picker)
// ==========================================
const THEME_PRESETS = [
  { accent:'#6C5CE7', gold:'#F5B942' },
  { accent:'#4E9EFF', gold:'#F5B942' },
  { accent:'#2ECC71', gold:'#F5F5F5' },
  { accent:'#FF5A4E', gold:'#F5F5F5' },
  { accent:'#2DD4BF', gold:'#F5B942' },
  { accent:'#EC4899', gold:'#F5F5F5' },
];
function applyTheme() {
  const theme = (db && db.theme) || THEME_PRESETS[0];
  document.documentElement.style.setProperty('--accent-color', theme.accent);
  document.documentElement.style.setProperty('--gold-color', theme.gold);
}
function renderThemeSwatches() {
  const el = document.getElementById('theme-swatches');
  if (!el) return;
  const current = ((db && db.theme) || THEME_PRESETS[0]).accent;
  el.innerHTML = THEME_PRESETS.map(p => `
    <button type="button" onclick="setTheme('${p.accent}','${p.gold}')" class="theme-swatch ${p.accent.toLowerCase()===current.toLowerCase()?'selected':''}" style="background:${p.accent}" title="${p.accent}"></button>
  `).join('');
}
window.setTheme = (accent, gold) => {
  db.theme = { accent, gold };
  saveData();
  applyTheme();
  renderThemeSwatches();
  buildChart();
  toast('🎨 Theme updated.');
};

// ==========================================
// COLOR MODE (light / dark)
// ==========================================
function applyColorMode() {
  const mode = (db && db.colorMode) || 'dark';
  document.documentElement.setAttribute('data-theme', mode);
  const btn = document.getElementById('colorModeBtn');
  if (btn) btn.innerText = mode === 'light' ? '☀️' : '🌙';
}
window.toggleColorMode = () => {
  db.colorMode = (db.colorMode === 'light') ? 'dark' : 'light';
  saveData();
  applyColorMode();
};

// ==========================================
// QUICK ACTIONS (floating action button)
// ==========================================
window.toggleQuickActions = () => {
  document.getElementById('quick-actions-menu').classList.toggle('hidden');
  document.getElementById('quick-actions-fab').classList.toggle('rotate-45');
};
window.quickAction = (action) => {
  document.getElementById('quick-actions-menu').classList.add('hidden');
  document.getElementById('quick-actions-fab').classList.remove('rotate-45');
  if (action === 'plan') navigateTo('planner');
  else if (action === 'invoice') navigateTo('invoices');
  else if (action === 'lead') { navigateTo('pipeline'); setTimeout(openAddLead, 150); }
  else if (action === 'message') navigateTo('messages');
};
document.addEventListener('click', e => {
  const wrap = document.getElementById('quickActionsWrap');
  if (wrap && !wrap.contains(e.target)) {
    document.getElementById('quick-actions-menu').classList.add('hidden');
    document.getElementById('quick-actions-fab').classList.remove('rotate-45');
  }
});

// ==========================================
// ROUTER
// ==========================================
window.openSidebar = () => {
  document.getElementById('sidebar').classList.add('open');
  document.getElementById('sidebarBackdrop').classList.remove('hidden');
};
window.closeSidebar = () => {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarBackdrop').classList.add('hidden');
};
window.goToAccount = (e) => { e.preventDefault(); navigateTo('account'); closeSidebar(); };
document.querySelectorAll('.nav-link').forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    navigateTo(link.dataset.page);
    closeSidebar();
  });
});

// ==========================================
// RENDER
// ==========================================
function initials(name) { return name.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase(); }

function renderAll() {
  // Account
  document.getElementById('dash-name').innerText = db.account.name.split(' ')[0];
  document.getElementById('sidebar-name').innerText = db.account.name;
  document.getElementById('sidebar-avatar').innerText = initials(db.account.name);
  document.getElementById('account-avatar').innerText = initials(db.account.name);
  document.getElementById('account-name-display').innerText = db.account.name;
  document.getElementById('account-niche-display').innerText = db.account.niche;
  const acc = document.getElementById('account-clients-count'); if (acc) acc.innerText = ((db.clients||[]).length) + ' active';
  const ams = document.getElementById('account-member-since'); if (ams) ams.innerText = db.account.memberSince || '—';
  const af = document.querySelector('#form-account [name="name"]'); if (af) af.value = db.account.name;
  const ae = document.querySelector('#form-account [name="email"]'); if (ae) ae.value = db.account.email;
  const an = document.querySelector('#form-account [name="niche"]'); if (an) an.value = db.account.niche;
  const ab = document.querySelector('#form-account [name="bio"]'); if (ab) ab.value = db.account.bio;
  const ar = document.querySelector('#form-account [name="rate"]'); if (ar) ar.value = db.account.rate;
  renderAccountApproval();

  // Dashboard
  const paidRevenue = (db.invoices||[]).filter(i=>i.status==='Paid').reduce((a,b)=>a+(b.amount||0),0);
  const shopRevenue = (db.shop||[]).reduce((a,b)=>a+((b.salePrice ?? b.price)*(b.sold||0)),0);
  const totalRevenue = paidRevenue + shopRevenue;
  const activeClients = (db.clients||[]).filter(c=>c.status==='Active').length;
  const pendingInvoices = (db.invoices||[]).filter(i=>i.status!=='Paid').length;
  const paidResources = (db.library||[]).filter(l=>l.tier==='Paid').length;
  document.getElementById('dash-stats').innerHTML = [
    { l:'Total Revenue', v:'$'+Math.round(totalRevenue).toLocaleString(), i:'💰', c: pendingInvoices ? pendingInvoices+' invoice'+(pendingInvoices>1?'s':'')+' pending' : 'All invoices paid' },
    { l:'Active Clients', v: activeClients, i:'👥', c: activeClients ? activeClients+' being coached' : 'Add your first client' },
    { l:'Plans Sent', v: (db.plans||[]).length, i:'▤', c: (db.plans||[]).length ? 'Total sent' : 'Build one in Plan Builder' },
    { l:'Library Resources', v: (db.library||[]).length, i:'▦', c: paidResources+' paid' }
  ].map(s=>`<div class="card rounded-2xl p-6"><div class="flex justify-between mb-3"><span class="text-muted text-sm">${s.l}</span><span class="text-xl">${s.i}</span></div><h3 class="display text-3xl font-bold text-white">${s.v}</h3><span class="text-muted text-xs">${s.c}</span></div>`).join('');

  document.getElementById('dash-activity').innerHTML = db.feedback.length
    ? db.feedback.slice(0,4).map(f=>`<div class="flex items-start gap-3"><div class="w-8 h-8 rounded-full bg-accent/15 flex items-center justify-center text-accent text-xs font-bold shrink-0">${f.client[0]}</div><div><p class="text-sm text-white">${f.client}</p><p class="text-xs text-muted">${f.msg.substring(0,50)}...</p></div></div>`).join('')
    : '<p class="text-muted text-sm text-center py-6">Nothing yet — client feedback will show up here.</p>';

  // Plans
  document.getElementById('list-plans').innerHTML = db.plans.length ? db.plans.map(p=>`
    <div class="bg-black border border-border rounded-xl p-4">
      <div class="flex justify-between items-start">
        <div>
          <div class="flex items-center gap-2"><h4 class="text-white font-semibold">${p.title}</h4><span class="text-[10px] px-2 py-0.5 rounded-full bg-accent/15 text-accent">${p.type}</span></div>
          <p class="text-accent text-xs mt-1">→ Sent to: ${p.client}</p>
          <p class="text-muted text-xs mt-1">${p.sent}</p>
          <p class="text-text text-sm mt-2 whitespace-pre-line">${p.details}</p>
        </div>
        <button onclick="del('plans',${p.id})" class="text-red-400/60 hover:text-red-400 text-xs">Remove</button>
      </div>
    </div>`).join('') : '<p class="text-muted text-sm text-center py-10">No plans sent yet.</p>';

  // Live
  document.getElementById('list-live').innerHTML = db.sessions.length ? db.sessions.map(s=>`
    <div class="bg-black border border-border rounded-xl p-4 flex justify-between items-center">
      <div><h4 class="text-white font-semibold">${s.topic}</h4><p class="text-muted text-xs">${new Date(s.time).toLocaleString()} · ${s.access}</p></div>
      <div class="flex gap-2"><button class="px-3 py-1 bg-accent/15 text-accent text-xs rounded-lg">Start</button><button onclick="del('sessions',${s.id})" class="text-red-400 text-xs">Cancel</button></div>
    </div>`).join('') : '<p class="text-muted text-sm text-center py-10">No sessions scheduled.</p>';

  // Library
  document.getElementById('list-library').innerHTML = db.library.length ? db.library.map(r=>`
    <div class="bg-black border border-border rounded-xl p-4">
      <div class="flex justify-between items-start mb-2">
        <span class="text-2xl">📄</span>
        <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold ${r.tier==='Free'?'bg-green-500/15 text-green-400':'bg-gold/15 text-gold'}">${r.tier==='Free'?'FREE':'$'+r.price.toFixed(2)}</span>
      </div>
      <h4 class="text-white font-semibold text-sm">${r.title}</h4>
      <p class="text-muted text-xs mt-1">${r.desc||''}</p>
      <p class="text-muted text-[10px] mt-2 font-mono">${r.filename}</p>
      <div class="flex justify-between items-center mt-3">
        <span class="text-muted text-xs">${r.downloads} downloads</span>
        <button onclick="del('library',${r.id})" class="text-red-400/60 hover:text-red-400 text-xs">Remove</button>
      </div>
    </div>`).join('') : '<p class="text-muted text-sm text-center py-10 col-span-2">No resources uploaded yet.</p>';

  // Feedback
  document.getElementById('list-feedback').innerHTML = db.feedback.map(f=>`
    <div class="bg-black border border-border rounded-xl p-4">
      <div class="flex justify-between"><div><h4 class="text-white font-semibold">${f.client}</h4><p class="text-muted text-xs">${f.date}</p></div><span class="text-gold text-sm">${'★'.repeat(f.rating)}${'☆'.repeat(5-f.rating)}</span></div>
      <p class="text-text text-sm mt-2">"${f.msg}"</p>
      <div class="mt-3 flex gap-2"><input type="text" placeholder="Reply..." class="flex-1 input-dark rounded-lg px-3 py-1.5 text-xs"><button class="px-3 py-1.5 bg-accent/15 text-accent text-xs rounded-lg">Send</button></div>
    </div>`).join('');

  // Store
  renderShop();

  // Community
  document.getElementById('list-community').innerHTML = db.community.map(p=>`
    <div class="card rounded-2xl p-6">
      <div class="flex justify-between items-start"><div><h4 class="text-white font-semibold">${p.user}</h4><span class="text-[10px] px-2 py-0.5 rounded-full ${p.type==='Challenge'?'bg-gold/15 text-gold':p.type==='Announcement'?'bg-accent/15 text-accent':'bg-border text-muted'}">${p.type}</span></div><span class="text-muted text-xs">${p.date}</span></div>
      <p class="text-text mt-3">${p.text}</p>
      <div class="mt-3 flex justify-between items-center"><button onclick="likePost(${p.id})" class="text-sm text-accent hover:underline">❤️ ${p.likes}</button>${p.user!=='Coach'?`<button onclick="del('community',${p.id})" class="text-xs text-red-400/60 hover:text-red-400">Remove Post</button>`:''}</div>
    </div>`).join('');

  // Submissions
  document.getElementById('list-submissions').innerHTML = db.submissions.map(s=>`
    <div class="bg-black border border-border rounded-xl p-3 cursor-pointer hover:border-accent/50 transition" onclick="loadSubmission(${s.id})">
      <h4 class="text-white text-sm font-semibold">${s.client}</h4>
      <p class="text-muted text-xs">${s.exercise}</p>
      <span class="text-[10px] px-2 py-0.5 rounded-full mt-1 inline-block ${s.status==='Pending Review'?'bg-gold/15 text-gold':'bg-green-500/15 text-green-400'}">${s.status}</span>
    </div>`).join('');

  // Points
  document.getElementById('total-points').innerText = db.pointsClients.reduce((a,b)=>a+b.points,0);
  document.getElementById('points-leaderboard').innerHTML = db.pointsClients.map((c,i)=>`
    <div class="flex items-center p-4 bg-black border border-border rounded-xl">
      <span class="text-lg font-bold text-muted w-8">${i+1}</span>
      <div class="w-10 h-10 rounded-full bg-gradient-to-br from-accent/40 to-indigo-700/40 flex items-center justify-center text-white text-sm font-bold ml-2">${c.name[0]}</div>
      <div class="ml-4 flex-1"><p class="text-white text-sm font-medium">${c.name}</p><p class="text-muted text-xs">${c.streak} day streak · ${c.completion}% completion</p></div>
      <div class="text-right"><p class="text-accent font-bold">${c.points} pts</p></div>
    </div>`).join('');

  // Readiness
  document.getElementById('readiness-list').innerHTML = db.readiness.map(r=>{
    const colors = { green:'border-green-500/30 bg-green-500/5', yellow:'border-gold/30 bg-gold/5', red:'border-red-500/30 bg-red-500/5' };
    const labels = { green:'🟢 Go Hard', yellow:'🟡 Moderate', red:'🔴 Recovery' };
    const recs = { green:'Keep today\'s heavy compounds. Push for PR.', yellow:'Drop weight 10%. Focus on form & tempo.', red:'Auto-swapped to 30min mobility flow.' };
    return `<div class="p-4 border rounded-xl ${colors[r.status]}">
      <div class="flex justify-between items-center"><h4 class="text-white font-semibold">${r.name}</h4><span class="text-sm">${labels[r.status]}</span></div>
      <div class="flex gap-4 mt-2 text-xs text-muted"><span>Sleep: ${r.sleep}/10</span><span>Sore: ${r.sore}/10</span><span>Stress: ${r.stress}/10</span></div>
      <p class="text-text text-sm mt-2">${recs[r.status]}</p>
      <button class="mt-2 text-xs text-accent hover:underline">Override Adjustment</button>
    </div>`;
  }).join('');

  // Referrals
  document.getElementById('ref-clicks').innerText = db.referrals.clicks;
  document.getElementById('ref-signups').innerText = db.referrals.signups;
  document.getElementById('ref-rev').innerText = '$'+(db.referrals.signups*50).toLocaleString();

  // Chat
  renderChatList();
  renderChatThread();

  // New: insights, clients, pipeline, invoices
  renderInsights();
  renderClients();
  renderPipeline();
  renderInvoices();

  // New: builder, calendar, cohorts, automations, reports, contracts, metrics
  renderExerciseLibrary();
  renderBuiltWorkout();
  renderSlots();
  if (currentLiveView === 'cal') renderLiveCalendar();
  renderCohorts();
  renderAutomations();
  renderContracts();
  renderReports();
  renderMetricsChart();

  // New: onboarding UX
  renderChecklist();
  renderTasks();
  renderNotifBell();

  renderNutritionPage();
  renderTemplates();
  renderBusinessGoals();
  renderThemeSwatches();
  renderTimerDisplay();
  applyTranslations();
}

// ==========================================
// ACTIONS
// ==========================================
window.del = (t,id) => { db[t]=db[t].filter(i=>i.id!==id); saveData(); renderAll(); };
window.likePost = (id) => { let p=db.community.find(x=>x.id===id); if(p){p.likes++;saveData();renderAll();} };
window.loadSubmission = (id) => { let s=db.submissions.find(x=>x.id===id); if(s){s.status='Reviewed';saveData();renderAll();initCanvas();} };
window.copyRef = () => { const el=document.getElementById('ref-link'); navigator.clipboard.writeText(el.value); event.target.innerText='Copied!'; setTimeout(()=>event.target.innerText='Copy',2000); };

document.getElementById('form-plan').onsubmit = e => {
  e.preventDefault();
  let f = new FormData(e.target);
  const workoutText = builtWorkout.length ? builtWorkout.map(ex => `${ex.name}: ${ex.scheme}`).join('\n') : '';
  const notes = f.get('details') || '';
  const details = [workoutText, notes].filter(Boolean).join('\n\n') || 'No details added.';
  db.plans.unshift({id:Date.now(),client:f.get('client'),title:f.get('title'),type:f.get('type'),details,sent:'Just now'});
  saveData(); e.target.reset(); builtWorkout = []; renderAll();
  toast('✅ Plan sent to ' + f.get('client') + '.');
};
document.getElementById('form-shop').onsubmit = e => {
  e.preventDefault();
  let f = new FormData(e.target);
  db.shop.push({
    id: Date.now(), name: f.get('name'), price: parseFloat(f.get('price')),
    salePrice: f.get('salePrice') ? parseFloat(f.get('salePrice')) : null,
    category: f.get('category'), image: f.get('image'),
    stock: f.get('stock') ? parseInt(f.get('stock')) : null,
    featured: f.get('featured') === 'on', sold: 0
  });
  saveData(); e.target.reset(); renderAll();
  toast('✅ Product added to shop.');
};

// ==========================================
// SHOP (metallic UI, filters, stock, featured)
// ==========================================
let shopFilter = 'All';
const SHOP_CATEGORIES = ['All','Merch','Digital Product','Coaching Package','Brand Collab'];
window.setShopFilter = (cat) => { shopFilter = cat; renderShop(); };

function renderShop() {
  const statsEl = document.getElementById('shop-stat-revenue');
  if (!statsEl) return;
  const revenue = db.shop.reduce((a,p) => a + ((p.salePrice ?? p.price) * (p.sold||0)), 0);
  const unitsSold = db.shop.reduce((a,p) => a + (p.sold||0), 0);
  const lowStock = db.shop.filter(p => p.stock !== null && p.stock !== undefined && p.stock <= 5).length;
  document.getElementById('shop-stat-revenue').innerText = '$' + revenue.toLocaleString(undefined,{maximumFractionDigits:0});
  document.getElementById('shop-stat-sold').innerText = unitsSold;
  document.getElementById('shop-stat-lowstock').innerText = lowStock;

  const tabsEl = document.getElementById('shop-filter-tabs');
  if (tabsEl) {
    tabsEl.innerHTML = SHOP_CATEGORIES.map(cat => `
      <button onclick="setShopFilter('${cat}')" class="metal-tab ${shopFilter===cat?'active':''} px-4 py-1.5 rounded-full text-xs font-medium text-white transition">${cat}</button>
    `).join('');
  }

  const el = document.getElementById('list-shop');
  if (!el) return;
  const items = shopFilter === 'All' ? db.shop : db.shop.filter(p => p.category === shopFilter);
  el.innerHTML = items.length ? items.map(p => {
    const onSale = p.salePrice !== null && p.salePrice !== undefined && p.salePrice < p.price;
    const outOfStock = p.stock !== null && p.stock !== undefined && p.stock <= 0;
    const lowStockFlag = p.stock !== null && p.stock !== undefined && p.stock > 0 && p.stock <= 5;
    const stockPct = p.stock !== null && p.stock !== undefined ? Math.min(100, (p.stock / 20) * 100) : null;
    return `
    <div class="metal-card overflow-hidden">
      <div class="h-28 metal-icon-plate flex items-center justify-center text-3xl relative">
        ${p.image ? `<img src="${p.image}" class="w-full h-full object-cover">` : '📦'}
        <div class="absolute top-2 left-2 flex gap-1.5">
          ${p.featured ? '<span class="metal-badge-gold text-[9px] font-bold px-2 py-0.5 rounded-full">★ FEATURED</span>' : ''}
          ${onSale ? '<span class="metal-badge-silver text-[9px] font-bold px-2 py-0.5 rounded-full">SALE</span>' : ''}
        </div>
        ${outOfStock ? '<div class="absolute inset-0 bg-black/70 flex items-center justify-center"><span class="text-red-400 text-xs font-bold tracking-wide">OUT OF STOCK</span></div>' : ''}
      </div>
      <div class="p-4">
        <h4 class="text-white font-semibold text-sm">${p.name}</h4>
        <div class="flex items-center gap-2 mt-1">
          ${onSale ? `<span class="text-muted text-xs line-through">$${p.price.toFixed(2)}</span><span class="text-accent font-bold text-sm">$${p.salePrice.toFixed(2)}</span>` : `<span class="text-accent font-bold text-sm">$${p.price.toFixed(2)}</span>`}
        </div>
        <p class="text-muted text-[10px] mt-1">${p.category} · ${p.sold||0} sold</p>
        ${stockPct !== null ? `
          <div class="mt-2">
            <div class="flex justify-between text-[10px] text-muted mb-1"><span>${p.stock} in stock</span>${lowStockFlag?'<span class="text-gold">Low stock</span>':''}</div>
            <div class="metal-stock-bar"><div class="metal-stock-fill" style="width:${stockPct}%"></div></div>
          </div>` : '<p class="text-muted text-[10px] mt-2">Unlimited stock</p>'}
        <div class="flex gap-2 mt-3">
          <button onclick="openEditProduct(${p.id})" class="flex-1 py-1.5 bg-accent/15 text-accent text-xs font-semibold rounded-lg hover:bg-accent/25 transition">Edit</button>
          <button onclick="del('shop',${p.id})" class="px-3 py-1.5 text-red-400/60 hover:text-red-400 text-xs">Remove</button>
        </div>
      </div>
    </div>`;
  }).join('') : '<p class="text-muted text-sm text-center py-10 col-span-2">No products in this category.</p>';
}

let editingProductId = null;
window.openEditProduct = (id) => {
  const p = db.shop.find(x => x.id === id);
  if (!p) return;
  editingProductId = id;
  const form = document.getElementById('form-edit-product');
  form.querySelector('[name="name"]').value = p.name;
  form.querySelector('[name="price"]').value = p.price;
  form.querySelector('[name="salePrice"]').value = p.salePrice ?? '';
  form.querySelector('[name="category"]').value = p.category;
  form.querySelector('[name="stock"]').value = p.stock ?? '';
  form.querySelector('[name="featured"]').checked = !!p.featured;
  form.querySelector('[name="image"]').value = p.image || '';
  document.getElementById('editProductModal').classList.remove('hidden');
};
window.closeEditProduct = () => document.getElementById('editProductModal').classList.add('hidden');
document.getElementById('form-edit-product').onsubmit = e => {
  e.preventDefault();
  const p = db.shop.find(x => x.id === editingProductId);
  if (!p) return;
  const f = new FormData(e.target);
  p.name = f.get('name');
  p.price = parseFloat(f.get('price'));
  p.salePrice = f.get('salePrice') ? parseFloat(f.get('salePrice')) : null;
  p.category = f.get('category');
  p.stock = f.get('stock') ? parseInt(f.get('stock')) : null;
  p.featured = f.get('featured') === 'on';
  p.image = f.get('image');
  saveData(); closeEditProduct(); renderShop();
  toast('✅ Product updated.');
};

document.getElementById('form-live').onsubmit = e => { e.preventDefault(); let f=new FormData(e.target); db.sessions.push({id:Date.now(),topic:f.get('topic'),time:f.get('time'),access:f.get('access')}); saveData(); e.target.reset(); renderAll(); toast('✅ Session scheduled.'); };
document.getElementById('form-community').onsubmit = e => { e.preventDefault(); let f=new FormData(e.target); db.community.unshift({id:Date.now(),user:'Coach',type:f.get('type'),text:f.get('post'),likes:0,date:'Just now'}); saveData(); e.target.reset(); renderAll(); };
document.getElementById('form-account').onsubmit = e => {
  e.preventDefault();
  if (db.pendingAccountChange) { toast('You already have a request awaiting approval.'); return; }
  let f = new FormData(e.target);
  const requested = {
    name: f.get('name') || db.account.name,
    email: f.get('email') || db.account.email,
    niche: f.get('niche') || db.account.niche,
    bio: f.get('bio') || '',
    rate: parseFloat(f.get('rate')) || db.account.rate
  };
  const changed = JSON.stringify(requested) !== JSON.stringify({ name: db.account.name, email: db.account.email, niche: db.account.niche, bio: db.account.bio, rate: db.account.rate });
  if (!changed) { toast('No changes to submit.'); return; }
  db.pendingAccountChange = { ...requested, submittedAt: 'Just now' };
  saveData(); renderAll();
  toast('Submitted — awaiting Penguins approval.');
};

function renderAccountApproval() {
  const banner = document.getElementById('account-pending-banner');
  const btn = document.getElementById('account-submit-btn');
  const form = document.getElementById('form-account');
  if (!banner || !form) return;
  const fields = form.querySelectorAll('input, select, textarea');
  if (db.pendingAccountChange) {
    const p = db.pendingAccountChange;
    fields.forEach(f => f.disabled = true);
    if (btn) { btn.disabled = true; btn.innerText = 'Pending Approval'; btn.classList.add('opacity-50','cursor-not-allowed'); }
    banner.classList.remove('hidden');
    banner.innerHTML = `
      <div class="card rounded-2xl p-5 border-gold/40">
        <div class="flex items-center gap-2 mb-3">
          <span class="text-[10px] px-2 py-0.5 rounded-full bg-gold/15 text-gold font-semibold">PENDING APPROVAL</span>
          <span class="text-muted text-xs">Submitted ${p.submittedAt}</span>
        </div>
        <p class="text-text text-sm mb-3">Your profile changes have been sent to the Penguins team for review. Nothing on your live profile changes until it's approved.</p>
        <div class="bg-black border border-border rounded-lg p-3 text-xs text-muted space-y-1 mb-4">
          <p><span class="text-white">Name:</span> ${p.name}</p>
          <p><span class="text-white">Email:</span> ${p.email || '—'}</p>
          <p><span class="text-white">Niche:</span> ${p.niche}</p>
          <p><span class="text-white">Rate:</span> $${p.rate}/hr</p>
        </div>
        <div class="flex gap-2">
          <button onclick="cancelAccountRequest()" class="flex-1 py-2 bg-black border border-border text-muted text-xs font-medium rounded-lg hover:text-white transition">Withdraw Request</button>
          <button onclick="approveAccountChange()" class="flex-1 py-2 bg-accent/15 text-accent text-xs font-semibold rounded-lg hover:bg-accent/25 transition">Simulate Penguins Approval</button>
        </div>
      </div>`;
  } else {
    fields.forEach(f => f.disabled = false);
    if (btn) { btn.disabled = false; btn.innerText = 'Submit for Approval'; btn.classList.remove('opacity-50','cursor-not-allowed'); }
    banner.classList.add('hidden');
    banner.innerHTML = '';
  }
}
window.cancelAccountRequest = () => { db.pendingAccountChange = null; saveData(); renderAll(); toast('Request withdrawn.'); };
window.approveAccountChange = () => {
  if (!db.pendingAccountChange) return;
  const { name, email, niche, bio, rate } = db.pendingAccountChange;
  db.account = { name, email, niche, bio, rate };
  db.pendingAccountChange = null;
  saveData(); renderAll();
  toast('✅ Approved — profile updated.');
};

window.toggleLibraryPrice = () => {
  const tier = document.getElementById('library-tier').value;
  document.getElementById('library-price-wrap').classList.toggle('hidden', tier !== 'Paid');
};
document.getElementById('form-library').onsubmit = e => {
  e.preventDefault();
  const f = new FormData(e.target);
  const file = f.get('file');
  const tier = f.get('tier');
  db.library.unshift({
    id: Date.now(),
    title: f.get('title'),
    desc: f.get('desc'),
    tier: tier,
    price: tier === 'Paid' ? (parseFloat(f.get('price')) || 0) : 0,
    filename: file && file.name ? file.name : 'resource.pdf',
    downloads: 0
  });
  saveData(); e.target.reset();
  document.getElementById('library-price-wrap').classList.add('hidden');
  renderAll();
  toast('✅ Resource uploaded.');
};

// ==========================================
// CHAT
// ==========================================
function renderChatList() {
  const names = Object.keys(db.chats);
  document.getElementById('chat-client-list').innerHTML = names.map(name => {
    const thread = db.chats[name];
    const last = thread.length ? thread[thread.length-1] : null;
    const active = name === db.activeChat;
    return `<button onclick="openChat('${name.replace(/'/g,"\\'")}')" class="w-full text-left p-4 border-b border-border hover:bg-card2 transition ${active?'bg-card2':''}">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-full bg-accent/20 flex items-center justify-center text-accent text-xs font-bold shrink-0">${name[0]}</div>
        <div class="flex-1 min-w-0">
          <p class="text-white text-sm font-medium truncate">${name}</p>
          <p class="text-muted text-xs truncate">${last ? (last.from==='me'?'You: ':'') + last.text : 'No messages yet'}</p>
        </div>
      </div>
    </button>`;
  }).join('');
}
function renderChatThread() {
  const name = db.activeChat;
  const thread = db.chats[name] || [];
  document.getElementById('chat-header-info').innerHTML = `<div class="w-9 h-9 rounded-full bg-accent/20 flex items-center justify-center text-accent text-xs font-bold mr-3">${name[0]}</div><div><p class="text-white text-sm font-semibold">${name}</p><p class="text-muted text-xs">Client</p></div>`;
  document.getElementById('chat-thread').innerHTML = thread.length ? thread.map(m => `
    <div class="flex ${m.from==='me'?'justify-end':'justify-start'}">
      <div class="max-w-[70%] px-4 py-2 text-sm ${m.from==='me'?'chat-bubble-me':'chat-bubble-them'}">
        ${m.text}
        <div class="text-[10px] opacity-60 mt-1">${m.time}</div>
      </div>
    </div>`).join('') : '<p class="text-muted text-sm text-center py-10">Start the conversation.</p>';
  const el = document.getElementById('chat-thread'); el.scrollTop = el.scrollHeight;
  renderQuickReplies();
}
function renderQuickReplies() {
  const el = document.getElementById('quick-replies');
  if (!el) return;
  const replies = ['Great work this week! 💪', 'Let\'s go over this on our next call', 'How are you feeling today?', 'Keep it up — you\'re making real progress'];
  el.innerHTML = replies.map(r => `<button type="button" class="quick-reply-chip" onclick="sendQuickReply('${r.replace(/'/g,"\\'")}')">${r}</button>`).join('');
}
window.sendQuickReply = (text) => {
  const name = db.activeChat;
  if (!db.chats[name]) db.chats[name] = [];
  db.chats[name].push({ from:'me', text, time: 'Just now' });
  saveData(); renderChatThread();
};
window.openChat = (name) => {
  db.activeChat = name; renderChatList(); renderChatThread();
  if (window.innerWidth < 640) {
    document.getElementById('chat-list-panel').classList.add('hidden');
    const panel = document.getElementById('chat-thread-panel');
    panel.classList.remove('hidden'); panel.classList.add('flex');
  }
};
window.backToChatList = () => {
  document.getElementById('chat-list-panel').classList.remove('hidden');
  const panel = document.getElementById('chat-thread-panel');
  panel.classList.add('hidden'); panel.classList.remove('flex');
};
document.getElementById('form-chat').onsubmit = e => {
  e.preventDefault();
  const input = document.getElementById('chat-input');
  if (!input.value.trim()) return;
  const name = db.activeChat;
  if (!db.chats[name]) db.chats[name] = [];
  db.chats[name].push({ from:'me', text: input.value.trim(), time: 'Just now' });
  input.value = '';
  saveData(); renderChatList(); renderChatThread();
};

// ==========================================
// SMART INSIGHTS
// ==========================================
function renderInsights() {
  const el = document.getElementById('dash-insights');
  if (!el) return;
  const items = [];

  const redClients = db.readiness.filter(r => r.status === 'red');
  redClients.forEach(r => items.push({ color:'bg-red-400', text: `${r.name} is flagged red on readiness today — consider a lighter session or a check-in.` }));

  const lowFeedback = db.feedback.filter(f => f.rating <= 3);
  lowFeedback.forEach(f => items.push({ color:'bg-gold', text: `${f.client} left a ${f.rating}-star review — worth a personal follow-up.` }));

  const unsoldPaid = db.library.filter(r => r.tier === 'Paid' && r.downloads === 0);
  unsoldPaid.forEach(r => items.push({ color:'bg-accent', text: `"${r.title}" hasn't sold yet — try promoting it in Community or Messages.` }));

  const overdue = db.invoices.filter(i => i.status === 'Overdue');
  if (overdue.length) items.push({ color:'bg-red-400', text: `${overdue.length} invoice${overdue.length>1?'s are':' is'} overdue, totalling $${overdue.reduce((a,b)=>a+b.amount,0).toFixed(2)}.` });

  const topClient = [...db.pointsClients].sort((a,b)=>b.streak-a.streak)[0];
  if (topClient) items.push({ color:'bg-green-400', text: `${topClient.name} is on a ${topClient.streak}-day streak — a shoutout in Community would land well.` });

  const openLeads = db.leads.filter(l => l.stage !== 'Won' && l.stage !== 'Lost');
  if (openLeads.length) items.push({ color:'bg-accent', text: `${openLeads.length} lead${openLeads.length>1?'s':''} still open in your pipeline worth $${openLeads.reduce((a,b)=>a+(b.value||0),0).toLocaleString()}.` });

  el.innerHTML = `
    <div class="flex items-center justify-between mb-3">
      <h3 class="display text-sm font-semibold text-white tracking-wide uppercase">Smart Insights</h3>
      <span class="text-[10px] text-muted">Auto-generated from your data</span>
    </div>
    <div class="space-y-2.5">
      ${items.length ? items.slice(0,5).map(i => `<div class="flex items-start gap-3"><span class="insight-dot ${i.color} mt-1.5"></span><p class="text-sm text-text">${i.text}</p></div>`).join('') : '<p class="text-muted text-sm">All clear — nothing needs your attention right now.</p>'}
    </div>`;
}

// ==========================================
// CLIENTS (CRM)
// ==========================================
function renderClients() {
  const el = document.getElementById('list-clients');
  if (!el) return;
  const q = (document.getElementById('client-search')?.value || '').toLowerCase();
  const statusFilter = document.getElementById('client-filter')?.value || 'All';
  const filtered = db.clients.filter(c => c.name.toLowerCase().includes(q) && (statusFilter==='All' || c.status===statusFilter));
  el.innerHTML = filtered.length ? filtered.map(c => {
    const pts = db.pointsClients.find(p => p.name === c.name);
    const rdy = db.readiness.find(r => r.name === c.name);
    const rdyColor = rdy ? ({green:'bg-green-400',yellow:'bg-gold',red:'bg-red-400'})[rdy.status] : 'bg-muted';
    return `<button onclick="openClientModal('${c.name.replace(/'/g,"\\'")}')" class="text-left card rounded-2xl p-5 hover:border-accent/50 transition">
      <div class="flex items-center justify-between mb-3">
        <div class="w-11 h-11 rounded-full bg-accent/20 flex items-center justify-center text-accent font-bold">${c.name[0]}</div>
        <span class="w-2.5 h-2.5 rounded-full ${rdyColor}" title="Readiness"></span>
      </div>
      <h4 class="text-white font-semibold">${c.name}</h4>
      <p class="text-muted text-xs mt-0.5">${c.tag} · ${c.status}</p>
      <div class="flex justify-between text-xs text-muted mt-3 pt-3 border-t border-border">
        <span>${pts ? pts.streak+'d streak' : '—'}</span>
        <span>Since ${c.joined}</span>
      </div>
    </button>`;
  }).join('') : '<p class="text-muted text-sm text-center py-10 sm:col-span-2 lg:col-span-3">No clients match your search.</p>';
}

function clientSummary(name) {
  const c = db.clients.find(x => x.name === name) || { name, email:'', status:'—', tag:'—', joined:'—', notes:'' };
  const pts = db.pointsClients.find(p => p.name === name);
  const rdy = db.readiness.find(r => r.name === name);
  const plans = db.plans.filter(p => p.client === name);
  const fb = db.feedback.filter(f => f.client === name);
  const prs = db.clientPRs[name] || [];
  const goals = db.clientGoals[name] || [];
  return { c, pts, rdy, plans, fb, prs, goals };
}

let currentModalClient = null;

window.openClientModal = (name) => {
  currentModalClient = name;
  const { c, pts, rdy, plans, fb, prs, goals } = clientSummary(name);
  const rdyLabel = rdy ? ({green:'🟢 Go Hard',yellow:'🟡 Moderate',red:'🔴 Recovery'})[rdy.status] : '—';
  const sortedPRs = [...prs].sort((a,b) => new Date(b.date) - new Date(a.date));
  document.getElementById('clientModalBody').innerHTML = `
    <div class="flex items-start justify-between mb-5">
      <div class="flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-accent/20 flex items-center justify-center text-accent font-bold text-xl">${c.name[0]}</div>
        <div>
          <h3 class="display text-xl font-bold text-white">${c.name}</h3>
          <p class="text-muted text-sm">${c.tag} · ${c.status} · Client since ${c.joined}</p>
          <button onclick="toggleNutritionSub('${c.name.replace(/'/g,"\\'")}')" class="mt-1.5 inline-block text-[10px] px-2 py-0.5 rounded-full font-medium transition ${c.nutrition ? 'bg-accent/15 text-accent' : 'bg-border text-muted hover:text-accent'}">${c.nutrition ? '◍ Nutrition Enabled' : '◍ Enable Nutrition'}</button>
        </div>
      </div>
      <button onclick="closeClientModal()" class="text-muted hover:text-white text-xl leading-none">×</button>
    </div>
    <div class="grid grid-cols-3 gap-3 mb-5">
      <div class="bg-black border border-border rounded-xl p-3 text-center"><p class="text-muted text-[11px]">Points</p><p class="text-white font-bold text-lg">${pts ? pts.points : '—'}</p></div>
      <div class="bg-black border border-border rounded-xl p-3 text-center"><p class="text-muted text-[11px]">Streak</p><p class="text-white font-bold text-lg">${pts ? pts.streak+'d' : '—'}</p></div>
      <div class="bg-black border border-border rounded-xl p-3 text-center"><p class="text-muted text-[11px]">Readiness</p><p class="text-white font-bold text-sm mt-1">${rdyLabel}</p></div>
    </div>
    ${c.email ? `<p class="text-muted text-xs mb-4">✉ ${c.email}</p>` : ''}
    ${c.notes ? `<div class="mb-5"><p class="text-[11px] text-muted uppercase tracking-wide mb-1">Coach Notes</p><p class="text-sm text-text">${c.notes}</p></div>` : ''}

    <div class="mb-5">
      <div class="flex items-center justify-between mb-2">
        <p class="text-[11px] text-muted uppercase tracking-wide">Personal Records (${sortedPRs.length})</p>
        <button onclick="openClientPRModal()" class="text-accent text-[11px] hover:underline">+ Log PR</button>
      </div>
      ${sortedPRs.length ? sortedPRs.slice(0,4).map(p=>`
        <div class="bg-black border border-border rounded-lg p-3 mb-2 flex items-center justify-between">
          <div><p class="text-sm text-white font-medium">${p.exercise} 🏆</p><p class="text-muted text-[11px] mt-0.5">${new Date(p.date).toLocaleDateString('en-US',{month:'short',day:'numeric'})}</p></div>
          <div class="flex items-center gap-2"><span class="text-accent font-bold text-sm">${p.weight}kg × ${p.reps}</span><button onclick="delClientPR(${p.id})" class="text-red-400/50 hover:text-red-400 text-xs">✕</button></div>
        </div>`).join('') : '<p class="text-muted text-sm">No PRs logged yet.</p>'}
    </div>

    <div class="mb-5">
      <div class="flex items-center justify-between mb-2">
        <p class="text-[11px] text-muted uppercase tracking-wide">Goals (${goals.length})</p>
        <button onclick="openClientGoalModal()" class="text-accent text-[11px] hover:underline">+ Add Goal</button>
      </div>
      ${goals.length ? goals.map(g => {
        const start = g.startValue !== undefined ? g.startValue : g.current;
        const isIncreasing = g.target >= start;
        let pct = isIncreasing ? ((g.current - start) / ((g.target - start) || 1)) * 100 : ((start - g.current) / ((start - g.target) || 1)) * 100;
        pct = Math.max(0, Math.min(100, pct));
        return `<div class="bg-black border border-border rounded-lg p-3 mb-2">
          <div class="flex justify-between items-center mb-1"><p class="text-sm text-white font-medium">${g.title}${pct>=100?' 🏆':''}</p><button onclick="delClientGoal(${g.id})" class="text-red-400/50 hover:text-red-400 text-xs">✕</button></div>
          <div class="flex justify-between text-[11px] text-muted mb-1"><span>${g.current} ${g.unit}</span><span>Goal: ${g.target} ${g.unit}</span></div>
          <div class="w-full h-1.5 bg-border rounded-full overflow-hidden"><div class="h-full bg-accent rounded-full" style="width:${pct}%"></div></div>
        </div>`;
      }).join('') : '<p class="text-muted text-sm">No goals set yet.</p>'}
    </div>

    <div class="mb-5">
      <p class="text-[11px] text-muted uppercase tracking-wide mb-2">Recent Plans (${plans.length})</p>
      ${plans.length ? plans.slice(0,3).map(p=>`<div class="bg-black border border-border rounded-lg p-3 mb-2"><p class="text-sm text-white font-medium">${p.title}</p><p class="text-muted text-xs">${p.type} · ${p.sent}</p></div>`).join('') : '<p class="text-muted text-sm">No plans sent yet.</p>'}
    </div>
    <div class="mb-5">
      <p class="text-[11px] text-muted uppercase tracking-wide mb-2">Feedback (${fb.length})</p>
      ${fb.length ? fb.map(f=>`<div class="bg-black border border-border rounded-lg p-3 mb-2"><p class="text-sm text-text">"${f.msg}"</p><p class="text-gold text-xs mt-1">${'★'.repeat(f.rating)}${'☆'.repeat(5-f.rating)}</p></div>`).join('') : '<p class="text-muted text-sm">No feedback yet.</p>'}
    </div>
    <div class="mb-5">
      <p class="text-[11px] text-muted uppercase tracking-wide mb-2">Private Session Notes <span class="normal-case text-muted">(only visible to you)</span></p>
      <div id="session-notes-list" class="space-y-2 mb-2 max-h-40 overflow-y-auto thin-scroll"></div>
      <form onsubmit="addSessionNote(event,'${c.name.replace(/'/g,"\\'")}')" class="flex gap-2">
        <input type="text" id="session-note-input" placeholder="Add a private note…" class="flex-1 input-dark rounded-lg px-3 py-1.5 text-xs">
        <button type="submit" class="px-3 py-1.5 bg-accent/15 text-accent text-xs font-semibold rounded-lg hover:bg-accent/25 transition">Add</button>
      </form>
    </div>
    <div class="flex gap-2">
      <button onclick="closeClientModal();openChat('${c.name.replace(/'/g,"\\'")}');navigateTo('messages')" class="flex-1 py-2.5 bg-accent text-white text-sm font-semibold rounded-lg hover:bg-indigo-500 transition">Message ${c.name.split(' ')[0]}</button>
      <button onclick="closeClientModal()" class="px-4 py-2.5 bg-black border border-border text-muted text-sm font-medium rounded-lg hover:text-white transition">Close</button>
    </div>`;
  document.getElementById('clientModal').classList.remove('hidden');
  renderSessionNotes(name);
};
window.closeClientModal = () => document.getElementById('clientModal').classList.add('hidden');

// ---- Client PRs ----
window.openClientPRModal = () => document.getElementById('clientPrModal').classList.remove('hidden');
window.closeClientPRModal = () => document.getElementById('clientPrModal').classList.add('hidden');
document.getElementById('form-client-pr').onsubmit = e => {
  e.preventDefault();
  const f = new FormData(e.target);
  if (!currentModalClient) return;
  if (!db.clientPRs[currentModalClient]) db.clientPRs[currentModalClient] = [];
  db.clientPRs[currentModalClient].unshift({ id: Date.now(), exercise: f.get('exercise'), weight: parseFloat(f.get('weight')), reps: parseInt(f.get('reps')), date: new Date().toISOString().slice(0,10) });
  saveData(); e.target.reset(); closeClientPRModal();
  openClientModal(currentModalClient);
  toast('🏆 PR logged.');
};
window.delClientPR = (id) => {
  if (!currentModalClient) return;
  db.clientPRs[currentModalClient] = (db.clientPRs[currentModalClient]||[]).filter(p => p.id !== id);
  saveData(); openClientModal(currentModalClient);
};

// ---- Client Goals ----
window.openClientGoalModal = () => document.getElementById('clientGoalModal').classList.remove('hidden');
window.closeClientGoalModal = () => document.getElementById('clientGoalModal').classList.add('hidden');
document.getElementById('form-client-goal').onsubmit = e => {
  e.preventDefault();
  const f = new FormData(e.target);
  if (!currentModalClient) return;
  if (!db.clientGoals[currentModalClient]) db.clientGoals[currentModalClient] = [];
  const current = parseFloat(f.get('current'));
  db.clientGoals[currentModalClient].push({ id: Date.now(), title: f.get('title'), current, startValue: current, target: parseFloat(f.get('target')), unit: f.get('unit')||'' });
  saveData(); e.target.reset(); closeClientGoalModal();
  openClientModal(currentModalClient);
  toast('✅ Goal added.');
};
window.delClientGoal = (id) => {
  if (!currentModalClient) return;
  db.clientGoals[currentModalClient] = (db.clientGoals[currentModalClient]||[]).filter(g => g.id !== id);
  saveData(); openClientModal(currentModalClient);
};

function renderSessionNotes(name) {
  const el = document.getElementById('session-notes-list');
  if (!el) return;
  const notes = db.sessionNotes[name] || [];
  el.innerHTML = notes.length ? [...notes].reverse().map(n => `
    <div class="bg-black border border-border rounded-lg p-2.5">
      <p class="text-text text-xs">${n.text}</p>
      <p class="text-muted text-[10px] mt-1">${n.date}</p>
    </div>`).join('') : '<p class="text-muted text-xs">No private notes yet.</p>';
}
window.addSessionNote = (e, name) => {
  e.preventDefault();
  const input = document.getElementById('session-note-input');
  if (!input.value.trim()) return;
  if (!db.sessionNotes[name]) db.sessionNotes[name] = [];
  db.sessionNotes[name].push({ date: 'Just now', text: input.value.trim() });
  input.value = '';
  saveData(); renderSessionNotes(name);
};

// ==========================================
// PIPELINE (LEADS, DRAG & DROP KANBAN)
// ==========================================
const PIPELINE_STAGES = ['New','Contacted','Proposal Sent','Won','Lost'];

function renderPipeline() {
  const board = document.getElementById('pipeline-board');
  if (!board) return;
  board.innerHTML = PIPELINE_STAGES.map(stage => {
    const items = db.leads.filter(l => l.stage === stage);
    const total = items.reduce((a,b)=>a+(b.value||0),0);
    return `<div class="kanban-col p-3" data-stage="${stage}" ondragover="onDragOver(event)" ondragleave="onDragLeave(event)" ondrop="onDrop(event)">
      <div class="flex items-center justify-between mb-3 px-1">
        <h4 class="text-white text-xs font-semibold uppercase tracking-wide">${stage}</h4>
        <span class="text-muted text-[10px]">$${total.toLocaleString()}</span>
      </div>
      <div class="space-y-2">
        ${items.map(l => `
          <div class="kanban-card p-3" draggable="true" data-id="${l.id}" ondragstart="onDragStart(event)" ondragend="onDragEnd(event)">
            <p class="text-white text-sm font-medium">${l.name}</p>
            <p class="text-muted text-[11px] mt-0.5">${l.notes||''}</p>
            <div class="flex justify-between items-center mt-2">
              <span class="text-accent text-xs font-semibold">$${(l.value||0).toLocaleString()}</span>
              <button onclick="del('leads',${l.id})" class="text-red-400/50 hover:text-red-400 text-[11px]">✕</button>
            </div>
          </div>`).join('')}
      </div>
    </div>`;
  }).join('');
}

let draggedLeadId = null;
window.onDragStart = (e) => { draggedLeadId = parseInt(e.target.closest('.kanban-card').dataset.id); e.target.closest('.kanban-card').classList.add('dragging'); };
window.onDragEnd = (e) => { e.target.closest('.kanban-card').classList.remove('dragging'); };
window.onDragOver = (e) => { e.preventDefault(); e.currentTarget.classList.add('drag-over'); };
window.onDragLeave = (e) => { e.currentTarget.classList.remove('drag-over'); };
window.onDrop = (e) => {
  e.preventDefault();
  e.currentTarget.classList.remove('drag-over');
  const stage = e.currentTarget.dataset.stage;
  const lead = db.leads.find(l => l.id === draggedLeadId);
  if (lead) { lead.stage = stage; saveData(); renderPipeline(); renderInsights(); }
};

window.openAddLead = () => document.getElementById('leadModal').classList.remove('hidden');
window.closeAddLead = () => document.getElementById('leadModal').classList.add('hidden');
document.getElementById('form-lead').onsubmit = e => {
  e.preventDefault();
  const f = new FormData(e.target);
  db.leads.unshift({ id: Date.now(), name: f.get('name'), email: f.get('email'), stage: 'New', value: parseFloat(f.get('value'))||0, notes: f.get('notes'), date: 'Just now' });
  saveData(); e.target.reset(); closeAddLead(); renderAll();
  toast('✅ Lead added to Pipeline.');
};

// ==========================================
// INVOICES
// ==========================================
function renderInvoices() {
  const list = document.getElementById('list-invoices');
  if (!list) return;
  const outstanding = db.invoices.filter(i=>i.status!=='Paid').reduce((a,b)=>a+b.amount,0);
  const paid = db.invoices.filter(i=>i.status==='Paid').reduce((a,b)=>a+b.amount,0);
  const overdue = db.invoices.filter(i=>i.status==='Overdue').reduce((a,b)=>a+b.amount,0);
  document.getElementById('inv-outstanding').innerText = '$'+outstanding.toFixed(2);
  document.getElementById('inv-paid').innerText = '$'+paid.toFixed(2);
  document.getElementById('inv-overdue').innerText = '$'+overdue.toFixed(2);

  const statusColor = { Paid:'bg-green-500/15 text-green-400', Unpaid:'bg-gold/15 text-gold', Overdue:'bg-red-500/15 text-red-400' };
  list.innerHTML = db.invoices.length ? db.invoices.map(i => `
    <div class="bg-black border border-border rounded-xl p-4 flex items-center justify-between gap-3">
      <div class="min-w-0">
        <p class="text-white font-medium text-sm truncate">${i.desc}</p>
        <p class="text-muted text-xs mt-0.5">${i.client} · Due ${i.due}</p>
      </div>
      <div class="flex items-center gap-3 shrink-0">
        <span class="text-white font-semibold text-sm">$${i.amount.toFixed(2)}</span>
        <span class="text-[10px] px-2 py-0.5 rounded-full font-medium ${statusColor[i.status]}">${i.status}</span>
        ${i.status!=='Paid' ? `<button onclick="markPaid(${i.id})" class="text-accent text-xs hover:underline">Mark Paid</button>` : ''}
        <button onclick="del('invoices',${i.id})" class="text-red-400/50 hover:text-red-400 text-xs">✕</button>
      </div>
    </div>`).join('') : '<p class="text-muted text-sm text-center py-10">No invoices yet.</p>';
}
window.markPaid = (id) => { const inv = db.invoices.find(i=>i.id===id); if(inv){ inv.status='Paid'; saveData(); renderAll(); } };
document.getElementById('form-invoice').onsubmit = e => {
  e.preventDefault();
  const f = new FormData(e.target);
  db.invoices.unshift({ id: Date.now(), client: f.get('client'), desc: f.get('desc'), amount: parseFloat(f.get('amount'))||0, status: 'Unpaid', due: f.get('due') });
  saveData(); e.target.reset(); renderAll();
  toast('✅ Invoice created.');
};

// ==========================================
// EXERCISE LIBRARY + WORKOUT BUILDER
// ==========================================
const EXERCISE_LIBRARY = [
  { name:'Barbell Back Squat', muscle:'Legs', default:'4 x 8' },
  { name:'Romanian Deadlift', muscle:'Hamstrings/Glutes', default:'3 x 10' },
  { name:'Bench Press', muscle:'Chest', default:'4 x 6' },
  { name:'Overhead Press', muscle:'Shoulders', default:'3 x 10' },
  { name:'Barbell Row', muscle:'Back', default:'4 x 8' },
  { name:'Pull-Up', muscle:'Back', default:'3 x AMRAP' },
  { name:'Walking Lunge', muscle:'Legs', default:'3 x 12/leg' },
  { name:'Lateral Raise', muscle:'Shoulders', default:'3 x 15' },
  { name:'Tricep Dip', muscle:'Arms', default:'3 x 12' },
  { name:'Bicep Curl', muscle:'Arms', default:'3 x 12' },
  { name:'Plank', muscle:'Core', default:'3 x 45s' },
  { name:'Hanging Leg Raise', muscle:'Core', default:'3 x 12' },
  { name:'Kettlebell Swing', muscle:'Full Body', default:'4 x 15' },
  { name:'Box Jump', muscle:'Power', default:'4 x 6' },
  { name:'Farmer\'s Carry', muscle:'Full Body', default:'3 x 40m' },
];
let builtWorkout = [];

function renderExerciseLibrary() {
  const el = document.getElementById('exercise-library');
  if (!el) return;
  const q = (document.getElementById('exercise-search')?.value || '').toLowerCase();
  const list = EXERCISE_LIBRARY.filter(ex => ex.name.toLowerCase().includes(q) || ex.muscle.toLowerCase().includes(q));
  el.innerHTML = list.map(ex => `
    <div class="exercise-chip" onclick="addExerciseToWorkout('${ex.name.replace(/'/g,"\\'")}','${ex.default}')">
      <p class="text-white text-sm font-medium">${ex.name}</p>
      <p class="text-muted text-[11px]">${ex.muscle} · ${ex.default}</p>
    </div>`).join('') || '<p class="text-muted text-xs text-center py-4">No exercises match.</p>';
}
window.addExerciseToWorkout = (name, scheme) => {
  builtWorkout.push({ name, scheme });
  renderBuiltWorkout();
};
window.removeBuiltExercise = (i) => { builtWorkout.splice(i,1); renderBuiltWorkout(); };
window.clearBuiltWorkout = () => { builtWorkout = []; renderBuiltWorkout(); };
function renderBuiltWorkout() {
  const el = document.getElementById('built-workout');
  if (!el) return;
  if (!builtWorkout.length) {
    el.innerHTML = '<p class="text-muted text-xs" id="built-workout-empty">Click exercises on the left to add them here.</p>';
    return;
  }
  el.innerHTML = builtWorkout.map((ex,i) => `
    <div class="flex items-center justify-between bg-card2 rounded-lg px-3 py-2">
      <div><p class="text-white text-sm">${ex.name}</p><p class="text-muted text-[11px]">${ex.scheme}</p></div>
      <button type="button" onclick="removeBuiltExercise(${i})" class="text-red-400/60 hover:text-red-400 text-xs">✕</button>
    </div>`).join('');
}

// ==========================================
// PLAN TEMPLATES
// ==========================================
window.setPlanTab = (tab) => {
  document.getElementById('plan-view-sent').classList.toggle('hidden', tab !== 'sent');
  document.getElementById('plan-view-templates').classList.toggle('hidden', tab !== 'templates');
  document.getElementById('plan-tab-sent').className = 'flex-1 px-3 py-1.5 rounded-md font-medium transition ' + (tab==='sent' ? 'bg-accent text-white' : 'text-muted');
  document.getElementById('plan-tab-templates').className = 'flex-1 px-3 py-1.5 rounded-md font-medium transition ' + (tab==='templates' ? 'bg-accent text-white' : 'text-muted');
};
function renderTemplates() {
  const el = document.getElementById('list-templates');
  if (!el) return;
  el.innerHTML = db.planTemplates.length ? db.planTemplates.map(t => `
    <div class="bg-black border border-border rounded-xl p-4">
      <div class="flex items-center gap-2 mb-1"><h4 class="text-white font-semibold text-sm">${t.title}</h4><span class="text-[10px] px-2 py-0.5 rounded-full bg-accent/15 text-accent">${t.type}</span></div>
      <p class="text-muted text-xs whitespace-pre-line line-clamp-3">${t.details}</p>
      <div class="flex gap-2 mt-3">
        <button onclick="useTemplate(${t.id})" class="flex-1 py-1.5 bg-accent/15 text-accent text-xs font-semibold rounded-lg hover:bg-accent/25 transition">Use Template</button>
        <button onclick="del('planTemplates',${t.id})" class="px-3 py-1.5 text-red-400/60 hover:text-red-400 text-xs">Delete</button>
      </div>
    </div>`).join('') : '<p class="text-muted text-sm text-center py-10">No templates saved yet — build a plan and click "Save as Template".</p>';
}
window.saveAsTemplate = () => {
  const form = document.getElementById('form-plan');
  const f = new FormData(form);
  const title = f.get('title');
  if (!title) { toast('Add a plan title first.'); return; }
  const workoutText = builtWorkout.length ? builtWorkout.map(ex => `${ex.name}: ${ex.scheme}`).join('\n') : '';
  const notes = f.get('details') || '';
  const details = [workoutText, notes].filter(Boolean).join('\n\n') || 'No details added.';
  db.planTemplates.unshift({ id: Date.now(), title, type: f.get('type'), details });
  saveData(); renderTemplates();
  toast('✅ Saved as template.');
};
window.useTemplate = (id) => {
  const tpl = db.planTemplates.find(t => t.id === id);
  if (!tpl) return;
  document.querySelector('#form-plan [name="title"]').value = tpl.title;
  document.querySelector('#form-plan [name="type"]').value = tpl.type;
  document.querySelector('#form-plan [name="details"]').value = tpl.details;
  builtWorkout = [];
  renderBuiltWorkout();
  setPlanTab('sent');
  toast('Template loaded — pick a client and send.');
};

// ==========================================
// REST TIMER
// ==========================================
let timerSeconds = 90, timerTotal = 90, timerInterval = null, timerRunning = false;
function formatTimer(s) {
  const m = Math.floor(s / 60), sec = s % 60;
  return `${String(m).padStart(2,'0')}:${String(sec).padStart(2,'0')}`;
}
function renderTimerDisplay() {
  const el = document.getElementById('timer-display');
  if (el) el.innerText = formatTimer(timerSeconds);
}
window.setTimerPreset = (s) => {
  clearInterval(timerInterval); timerRunning = false;
  timerSeconds = s; timerTotal = s;
  renderTimerDisplay();
  const btn = document.getElementById('timer-toggle-btn');
  if (btn) btn.innerText = 'Start';
};
window.toggleTimer = () => {
  const btn = document.getElementById('timer-toggle-btn');
  if (timerRunning) {
    clearInterval(timerInterval); timerRunning = false;
    if (btn) btn.innerText = 'Start';
    return;
  }
  timerRunning = true;
  if (btn) btn.innerText = 'Pause';
  timerInterval = setInterval(() => {
    timerSeconds--;
    renderTimerDisplay();
    if (timerSeconds <= 0) {
      clearInterval(timerInterval); timerRunning = false;
      if (btn) btn.innerText = 'Start';
      toast('⏱️ Rest complete.');
      timerSeconds = timerTotal;
      renderTimerDisplay();
    }
  }, 1000);
};
window.resetTimer = () => {
  clearInterval(timerInterval); timerRunning = false;
  timerSeconds = timerTotal;
  renderTimerDisplay();
  const btn = document.getElementById('timer-toggle-btn');
  if (btn) btn.innerText = 'Start';
};

// ==========================================
// BUSINESS GOALS
// ==========================================
function renderBusinessGoals() {
  const el = document.getElementById('dash-business-goals');
  if (!el) return;
  const g = db.businessGoals;
  const earnings = db.shop.reduce((a,b)=>a+b.price,0)*10 + db.plans.length*150 + db.library.reduce((a,b)=>a+(b.tier==='Paid'?b.price*10:0),0);
  const revenuePct = Math.min(100, Math.round((earnings / (g.revenueTarget||1)) * 100));
  const clientsPct = Math.min(100, Math.round((db.clients.length / (g.clientsTarget||1)) * 100));
  el.innerHTML = `
    <div class="flex items-center justify-between mb-4">
      <h3 class="display text-sm font-semibold text-white tracking-wide uppercase">Business Goals</h3>
      <button onclick="openGoalsModal()" class="text-[11px] text-accent hover:underline">Edit Targets</button>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
      <div>
        <div class="flex justify-between text-xs text-muted mb-1"><span>Monthly Revenue</span><span>$${Math.round(earnings).toLocaleString()} / $${g.revenueTarget.toLocaleString()}</span></div>
        <div class="w-full h-2 bg-border rounded-full overflow-hidden"><div class="h-full bg-accent rounded-full transition-all duration-500" style="width:${revenuePct}%"></div></div>
      </div>
      <div>
        <div class="flex justify-between text-xs text-muted mb-1"><span>Active Clients</span><span>${db.clients.length} / ${g.clientsTarget}</span></div>
        <div class="w-full h-2 bg-border rounded-full overflow-hidden"><div class="h-full bg-gold rounded-full transition-all duration-500" style="width:${clientsPct}%"></div></div>
      </div>
    </div>`;
}
window.openGoalsModal = () => {
  document.querySelector('#form-business-goals [name="revenueTarget"]').value = db.businessGoals.revenueTarget;
  document.querySelector('#form-business-goals [name="clientsTarget"]').value = db.businessGoals.clientsTarget;
  document.getElementById('goalsModal').classList.remove('hidden');
};
window.closeGoalsModal = () => document.getElementById('goalsModal').classList.add('hidden');
document.getElementById('form-business-goals').onsubmit = e => {
  e.preventDefault();
  const f = new FormData(e.target);
  db.businessGoals = { revenueTarget: parseFloat(f.get('revenueTarget'))||0, clientsTarget: parseFloat(f.get('clientsTarget'))||0 };
  saveData(); closeGoalsModal(); renderBusinessGoals();
  toast('✅ Targets updated.');
};

// ==========================================
// LIVE SESSIONS: CALENDAR VIEW + BOOKABLE SLOTS
// ==========================================
let currentLiveView = 'list';
window.setLiveView = (v) => {
  currentLiveView = v;
  document.getElementById('live-view-list').classList.toggle('hidden', v!=='list');
  document.getElementById('live-view-cal').classList.toggle('hidden', v!=='cal');
  document.getElementById('live-tab-list').className = 'px-3 py-1.5 rounded-md font-medium transition ' + (v==='list' ? 'bg-accent text-white' : 'text-muted');
  document.getElementById('live-tab-cal').className = 'px-3 py-1.5 rounded-md font-medium transition ' + (v==='cal' ? 'bg-accent text-white' : 'text-muted');
  if (v === 'cal') renderLiveCalendar();
};
function renderLiveCalendar() {
  const el = document.getElementById('live-calendar');
  if (!el) return;
  const today = new Date();
  const startOfWeek = new Date(today); startOfWeek.setDate(today.getDate() - today.getDay());
  const days = Array.from({length:7}, (_,i) => { const d = new Date(startOfWeek); d.setDate(startOfWeek.getDate()+i); return d; });
  el.innerHTML = days.map(d => {
    const dayStr = d.toDateString();
    const isToday = dayStr === today.toDateString();
    const daySessions = db.sessions.filter(s => new Date(s.time).toDateString() === dayStr);
    const daySlots = db.slots.filter(s => new Date(s.time).toDateString() === dayStr);
    return `<div class="cal-day ${isToday?'today':''}">
      <p class="text-[10px] text-muted uppercase">${d.toLocaleDateString('en-US',{weekday:'short'})}</p>
      <p class="text-white text-sm font-semibold mb-2">${d.getDate()}</p>
      ${daySessions.map(s=>`<div class="bg-accent/15 text-accent text-[10px] rounded px-1.5 py-0.5 mb-1 truncate">${s.topic}</div>`).join('')}
      ${daySlots.map(s=>`<div class="${s.booked?'bg-gold/15 text-gold':'bg-green-500/15 text-green-400'} text-[10px] rounded px-1.5 py-0.5 mb-1 truncate">${s.booked?'Booked: '+s.client:'Open slot'}</div>`).join('')}
    </div>`;
  }).join('');
}
function renderSlots() {
  const el = document.getElementById('list-slots');
  if (!el) return;
  el.innerHTML = db.slots.length ? db.slots.map(s => `
    <div class="bg-black border border-border rounded-xl p-4 flex justify-between items-center">
      <div><p class="text-white text-sm font-medium">${new Date(s.time).toLocaleString()}</p><p class="text-muted text-xs">${s.booked ? 'Booked by '+s.client : 'Open — clients can self-book'}</p></div>
      <div class="flex gap-2">
        ${!s.booked ? `<span class="text-[10px] px-2 py-0.5 rounded-full bg-green-500/15 text-green-400 font-medium">Open</span>` : `<span class="text-[10px] px-2 py-0.5 rounded-full bg-gold/15 text-gold font-medium">Booked</span>`}
        <button onclick="del('slots',${s.id})" class="text-red-400/60 hover:text-red-400 text-xs">Remove</button>
      </div>
    </div>`).join('') : '<p class="text-muted text-sm text-center py-6">No open slots yet.</p>';
}
document.getElementById('form-slot').onsubmit = e => {
  e.preventDefault();
  const f = new FormData(e.target);
  db.slots.push({ id: Date.now(), time: f.get('time'), booked: false, client: null });
  saveData(); e.target.reset(); renderAll();
  toast('✅ Open slot added.');
};

// ==========================================
// COHORTS
// ==========================================
function renderCohorts() {
  const el = document.getElementById('list-cohorts');
  if (!el) return;
  el.innerHTML = db.cohorts.length ? db.cohorts.map(c => `
    <div class="card rounded-2xl p-6">
      <div class="flex justify-between items-start mb-3">
        <div><h4 class="text-white font-semibold">${c.name}</h4><p class="text-muted text-xs mt-0.5">${c.schedule}</p></div>
        <button onclick="del('cohorts',${c.id})" class="text-red-400/50 hover:text-red-400 text-xs">Remove</button>
      </div>
      <div class="flex flex-wrap gap-2">
        ${c.members.map(m=>`<span class="text-xs px-2.5 py-1 rounded-full bg-accent/15 text-accent">${m}</span>`).join('') || '<span class="text-muted text-xs">No members yet</span>'}
      </div>
    </div>`).join('') : '<p class="text-muted text-sm text-center py-10 lg:col-span-2">No cohorts yet — create your first group program.</p>';
}
window.openAddCohort = () => {
  document.getElementById('cohort-member-checks').innerHTML = db.clients.map(c => `
    <label class="flex items-center gap-2 text-sm text-text"><input type="checkbox" name="member" value="${c.name}" class="accent-accent"> ${c.name}</label>`).join('');
  document.getElementById('cohortModal').classList.remove('hidden');
};
window.closeAddCohort = () => document.getElementById('cohortModal').classList.add('hidden');
document.getElementById('form-cohort').onsubmit = e => {
  e.preventDefault();
  const f = new FormData(e.target);
  const members = Array.from(e.target.querySelectorAll('input[name="member"]:checked')).map(i=>i.value);
  db.cohorts.push({ id: Date.now(), name: f.get('name'), schedule: f.get('schedule'), members });
  saveData(); e.target.reset(); closeAddCohort(); renderAll();
  toast('✅ Cohort created.');
};

// ==========================================
// AUTOMATIONS
// ==========================================
function renderAutomations() {
  const el = document.getElementById('list-automations');
  if (!el) return;
  el.innerHTML = db.automations.length ? db.automations.map(a => `
    <div class="bg-black border border-border rounded-xl p-4 flex items-center justify-between gap-3">
      <div class="min-w-0">
        <p class="text-white text-sm font-medium">When <span class="text-accent">${a.trigger}</span></p>
        <p class="text-muted text-xs mt-0.5">→ ${a.action}</p>
      </div>
      <div class="flex items-center gap-3 shrink-0">
        <div class="toggle-switch ${a.enabled?'on':''}" onclick="toggleAutomation(${a.id})"></div>
        <button onclick="del('automations',${a.id})" class="text-red-400/50 hover:text-red-400 text-xs">✕</button>
      </div>
    </div>`).join('') : '<p class="text-muted text-sm text-center py-10">No automations yet.</p>';
}
window.toggleAutomation = (id) => { const a = db.automations.find(x=>x.id===id); if(a){ a.enabled = !a.enabled; saveData(); renderAutomations(); } };
document.getElementById('form-automation').onsubmit = e => {
  e.preventDefault();
  const f = new FormData(e.target);
  db.automations.unshift({ id: Date.now(), trigger: f.get('trigger'), action: f.get('action'), enabled: true });
  saveData(); e.target.reset(); renderAll();
  toast('✅ Automation created.');
};

// ==========================================
// CONTRACTS
// ==========================================
function renderContracts() {
  const el = document.getElementById('list-contracts');
  if (!el) return;
  el.innerHTML = db.contracts.length ? db.contracts.map(c => `
    <div class="bg-black border border-border rounded-xl p-4 flex items-center justify-between gap-3">
      <div><p class="text-white text-sm font-medium">${c.title}</p><p class="text-muted text-xs mt-0.5">${c.client} · ${c.date}</p></div>
      <div class="flex items-center gap-3">
        <span class="text-[10px] px-2 py-0.5 rounded-full font-medium ${c.status==='Signed'?'bg-green-500/15 text-green-400':'bg-gold/15 text-gold'}">${c.status}</span>
        ${c.status!=='Signed' ? `<button onclick="markSigned(${c.id})" class="text-accent text-xs hover:underline">Simulate Sign</button>` : ''}
      </div>
    </div>`).join('') : '<p class="text-muted text-sm text-center py-10">No contracts sent yet.</p>';
}
window.markSigned = (id) => { const c = db.contracts.find(x=>x.id===id); if(c){ c.status='Signed'; saveData(); renderAll(); } };
document.getElementById('form-contract').onsubmit = e => {
  e.preventDefault();
  const f = new FormData(e.target);
  db.contracts.unshift({ id: Date.now(), client: f.get('client'), title: f.get('title'), status: 'Sent', date: 'Just now' });
  saveData(); e.target.reset(); renderAll();
  toast('✅ Contract sent for signature.');
};

// ==========================================
// BODY METRICS
// ==========================================
let metricsChartInstance = null;
document.getElementById('form-metrics').onsubmit = e => {
  e.preventDefault();
  const f = new FormData(e.target);
  const client = f.get('client');
  if (!db.metrics[client]) db.metrics[client] = [];
  db.metrics[client].push({ date: new Date().toLocaleDateString('en-US',{month:'short',day:'numeric'}), weight: parseFloat(f.get('weight')), waist: parseFloat(f.get('waist'))||null });
  saveData(); e.target.reset();
  document.getElementById('metrics-client-select').value = client;
  renderMetricsChart();
  toast('✅ Metrics logged.');
};
function renderMetricsChart() {
  const canvas = document.getElementById('metricsChart');
  const select = document.getElementById('metrics-client-select');
  if (!canvas || !select) return;
  const client = select.value || 'Alex M.';
  const data = db.metrics[client] || [];
  if (metricsChartInstance) metricsChartInstance.destroy();
  const cctx = canvas.getContext('2d');
  metricsChartInstance = new Chart(cctx, { type:'line', data:{ labels: data.map(d=>d.date), datasets:[{ label:'Weight (lbs)', data: data.map(d=>d.weight), borderColor:cssVar('--accent-color'), backgroundColor:cssVar('--accent-color')+'26', borderWidth:2.5, fill:true, tension:0.35, pointRadius:3, pointBackgroundColor:'#000', pointBorderColor:cssVar('--accent-color') }] }, options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ labels:{ color:'#8A8A8E', font:{size:11} } } }, scales:{ x:{ grid:{color:'rgba(255,255,255,0.03)'}, ticks:{color:'#8A8A8E',font:{size:10}} }, y:{ grid:{color:'rgba(255,255,255,0.03)'}, ticks:{color:'#8A8A8E',font:{size:10}} } } } });
}

// ==========================================
// REPORTS
// ==========================================
let retentionChartInstance = null, engagementChartInstance = null;
function renderReports() {
  const statsEl = document.getElementById('reports-stats');
  if (!statsEl) return;
  const active = db.clients.filter(c=>c.status==='Active').length;
  const paused = db.clients.filter(c=>c.status==='Paused').length;
  const avgCompletion = db.pointsClients.length ? Math.round(db.pointsClients.reduce((a,b)=>a+b.completion,0)/db.pointsClients.length) : 0;
  const wonLeads = db.leads.filter(l=>l.stage==='Won').length;
  statsEl.innerHTML = [
    { l:'Active Clients', v: active, i:'●' },
    { l:'Paused Clients', v: paused, i:'○' },
    { l:'Avg Engagement', v: avgCompletion+'%', i:'◈' },
    { l:'Leads Won (all-time)', v: wonLeads, i:'⬡' }
  ].map(s=>`<div class="card rounded-2xl p-6"><div class="flex justify-between mb-2"><span class="text-muted text-sm">${s.l}</span><span class="text-lg">${s.i}</span></div><h3 class="display text-3xl font-bold text-white">${s.v}</h3></div>`).join('');

  const retCanvas = document.getElementById('retentionChart');
  if (retCanvas) {
    if (retentionChartInstance) retentionChartInstance.destroy();
    retentionChartInstance = new Chart(retCanvas.getContext('2d'), { type:'line', data:{ labels:['Feb','Mar','Apr','May','Jun','Jul'], datasets:[{ label:'Retention %', data:[88,91,89,94,92,96], borderColor:cssVar('--accent-color'), backgroundColor:cssVar('--accent-color')+'26', borderWidth:2.5, fill:true, tension:0.35, pointRadius:3 }] }, options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{labels:{color:'#8A8A8E',font:{size:11}}}}, scales:{ x:{grid:{color:'rgba(255,255,255,0.03)'},ticks:{color:'#8A8A8E',font:{size:10}}}, y:{grid:{color:'rgba(255,255,255,0.03)'},ticks:{color:'#8A8A8E',font:{size:10}}} } } });
  }
  const engCanvas = document.getElementById('engagementChart');
  if (engCanvas) {
    if (engagementChartInstance) engagementChartInstance.destroy();
    engagementChartInstance = new Chart(engCanvas.getContext('2d'), { type:'bar', data:{ labels: db.pointsClients.map(c=>c.name), datasets:[{ label:'Completion %', data: db.pointsClients.map(c=>c.completion), backgroundColor:cssVar('--accent-color'), borderRadius:6 }] }, options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{ x:{grid:{display:false},ticks:{color:'#8A8A8E',font:{size:10}}}, y:{grid:{color:'rgba(255,255,255,0.03)'},ticks:{color:'#8A8A8E',font:{size:10}}} } } });
  }
}

// ==========================================
// VIEW AS CLIENT (PREVIEW MODE)
// ==========================================
window.openClientPreview = () => {
  const name = db.clients[0] ? db.clients[0].name : 'Alex M.';
  const { pts, rdy, plans, fb } = clientSummary(name);
  const freeResources = db.library.filter(r=>r.tier==='Free');
  document.getElementById('clientPreviewBody').innerHTML = `
    <div class="flex items-center justify-between mb-5">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-full bg-accent/20 flex items-center justify-center text-accent font-bold">${name[0]}</div>
        <div><p class="text-white font-semibold">${name}'s Portal</p><p class="text-muted text-xs">Simulated client view</p></div>
      </div>
      <button onclick="closeClientPreview()" class="text-muted hover:text-white text-xl leading-none">×</button>
    </div>
    <div class="grid grid-cols-2 gap-3 mb-5">
      <div class="bg-black border border-border rounded-xl p-3 text-center"><p class="text-muted text-[11px]">Points</p><p class="text-white font-bold text-lg">${pts?pts.points:0}</p></div>
      <div class="bg-black border border-border rounded-xl p-3 text-center"><p class="text-muted text-[11px]">Streak</p><p class="text-white font-bold text-lg">${pts?pts.streak+'d':'0d'}</p></div>
    </div>
    <p class="text-[11px] text-muted uppercase tracking-wide mb-2">My Current Plan</p>
    ${plans[0] ? `<div class="bg-black border border-border rounded-lg p-3 mb-5"><p class="text-sm text-white font-medium">${plans[0].title}</p><p class="text-muted text-xs whitespace-pre-line mt-1">${plans[0].details}</p></div>` : '<p class="text-muted text-sm mb-5">No plan assigned yet.</p>'}
    <p class="text-[11px] text-muted uppercase tracking-wide mb-2">Free Resources</p>
    <div class="space-y-2 mb-5">${freeResources.map(r=>`<div class="bg-black border border-border rounded-lg p-3 text-sm text-white">${r.title}</div>`).join('') || '<p class="text-muted text-sm">None available.</p>'}</div>
    <p class="text-[11px] text-muted uppercase tracking-wide mb-2">Readiness Today</p>
    <p class="text-sm text-text">${rdy ? ({green:'🟢 Cleared for a full session.',yellow:'🟡 Take it a bit lighter today.',red:'🔴 Recovery day recommended.'})[rdy.status] : 'Not checked in yet.'}</p>
  `;
  document.getElementById('clientPreviewOverlay').classList.remove('hidden');
  document.getElementById('clientPreviewBar').classList.remove('hidden');
};
window.closeClientPreview = () => {
  document.getElementById('clientPreviewOverlay').classList.add('hidden');
  document.getElementById('clientPreviewBar').classList.add('hidden');
};

// ==========================================
// TOAST NOTIFICATIONS
// ==========================================
window.toast = (msg) => {
  const wrap = document.getElementById('toastWrap');
  if (!wrap) return;
  const el = document.createElement('div');
  el.className = 'toast';
  el.innerText = msg;
  wrap.appendChild(el);
  setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity .3s'; setTimeout(()=>el.remove(), 300); }, 2800);
};

// ==========================================
// WELCOME / FIRST-TIME TOUR
// ==========================================
window.closeWelcome = () => {
  document.getElementById('welcomeModal').classList.add('hidden');
  db.seenWelcome = true;
  saveData();
};
function maybeShowWelcome() {
  if (!db.seenWelcome) document.getElementById('welcomeModal').classList.remove('hidden');
}

// ==========================================
// HELP & GUIDE
// ==========================================
const PAGE_INFO = [
  { label:'Dashboard', icon:'◆', text:'Your home base — earnings chart, Smart Insights (auto-flagged things needing attention), a getting-started checklist, Business Goals (set your own revenue/client targets and track progress), and your task list.' },
  { label:'Messages', icon:'✉', text:'Direct chat with each client. Pick a client on the left, type in the box, hit send.' },
  { label:'Clients', icon:'◇', text:'Every client in one place — the full dossier. Click a card to see their plans, feedback, points, readiness, personal records, goals with progress bars, and private session notes only you can see.' },
  { label:'Plan Builder', icon:'▤', text:'Build a structured program using the searchable exercise library on the left (click an exercise to add it), or just add freeform notes. Save any plan as a reusable template so you don\'t have to rebuild it next time — switch to the Templates tab to reuse or delete one.' },
  { label:'Nutrition', icon:'◍', text:'Set macro targets (calories, protein, carbs, fats) for clients on the Nutrition add-on. Clients without the add-on won\'t appear here until you enable it for them.' },
  { label:'Live Sessions', icon:'●', text:'Schedule 1:1 or group calls, or open bookable time slots your clients can claim themselves. Switch to Calendar view to see the week at a glance.' },
  { label:'Resource Library', icon:'▦', text:'Upload PDF guides and programs. Mark each Free or Paid with a price. Note: this is a demo uploader — actual file hosting needs a backend.' },
  { label:'Client Feedback', icon:'★', text:'Reviews and messages clients leave you. Reply inline to keep the conversation going.' },
  { label:'Vision Review', icon:'▶', text:'Review a client\'s submitted form-check video, draw annotations directly on the frame, then send your notes back.' },
  { label:'Points & Streaks', icon:'◈', text:'A leaderboard of client habit completion — total points, day streaks, and completion percentage.' },
  { label:'Readiness', icon:'◎', text:'See each client\'s daily readiness (sleep, soreness, stress) and the auto-suggested adjustment to today\'s plan.' },
  { label:'Progress Vault', icon:'▣', text:'Before/after photo comparisons, plus a body-metrics log (weight, waist) with a trend chart per client.' },
  { label:'Community', icon:'◐', text:'Your private client community feed. Post announcements or challenges, moderate what clients post.' },
  { label:'Cohorts', icon:'⬢', text:'Group coaching — bundle multiple clients into a shared program with its own schedule.' },
  { label:'Automations', icon:'⚡', text:'Set "when X happens, do Y" rules — e.g. auto-remind a client who misses a check-in. Toggle rules on/off anytime.' },
  { label:'Reports', icon:'▩', text:'Business-level view: active vs paused clients, average engagement, retention trend, and per-client completion rates.' },
  { label:'Contracts', icon:'✎', text:'Send agreements or waivers to a client and track whether they\'ve signed.' },
  { label:'Pipeline', icon:'⬡', text:'Your sales funnel for prospective clients — drag cards between New → Contacted → Proposal Sent → Won/Lost.' },
  { label:'Invoices', icon:'▥', text:'Create invoices, track what\'s outstanding, paid, or overdue, and mark them paid.' },
  { label:'Shop', icon:'▨', text:'List merch, digital products, or brand collabs for sale.' },
  { label:'Referrals', icon:'↗', text:'Your referral link — track clicks, signups, and the revenue they generated.' },
  { label:'Verification', icon:'✓', text:'Your identity/certification verification status. Read-only — managed by the Penguins team.' },
  { label:'Account', icon:'◉', text:'Your coach profile: name, niche, bio, hourly rate — plus an Appearance section to pick your own accent color for the whole dashboard (purple by default).' },
  { label:'View as Client', icon:'◑', text:'Preview what a client sees in their portal — their plan, points, free resources, and today\'s readiness note.' },
];
window.openHelp = () => {
  document.getElementById('help-content').innerHTML = PAGE_INFO.map(p => `
    <div class="flex items-start gap-3 pb-3 border-b border-border last:border-0">
      <span class="text-accent w-5 text-center shrink-0">${p.icon}</span>
      <div><p class="text-white text-sm font-semibold">${p.label}</p><p class="text-muted text-xs mt-0.5">${p.text}</p></div>
    </div>`).join('');
  document.getElementById('helpOverlay').classList.remove('hidden');
};
window.closeHelp = () => document.getElementById('helpOverlay').classList.add('hidden');

// ==========================================
// NOTIFICATION BELL
// ==========================================
window.toggleNotifPanel = () => document.getElementById('notif-panel').classList.toggle('hidden');
function renderNotifBell() {
  const list = [];
  db.readiness.filter(r=>r.status==='red').forEach(r => list.push(`${r.name} flagged red on readiness.`));
  db.feedback.filter(f=>f.rating<=3).forEach(f => list.push(`${f.client} left a ${f.rating}-star review.`));
  db.invoices.filter(i=>i.status==='Overdue').forEach(i => list.push(`Invoice for ${i.client} is overdue.`));
  db.library.filter(r=>r.tier==='Paid' && r.downloads===0).forEach(r => list.push(`"${r.title}" hasn't sold yet.`));

  const dot = document.getElementById('notif-dot');
  if (dot) dot.classList.toggle('hidden', list.length === 0);
  const panelList = document.getElementById('notif-list');
  if (panelList) {
    panelList.innerHTML = list.length ? list.slice(0,8).map(t => `<div class="px-2 py-2 text-xs text-text border-b border-border last:border-0">${t}</div>`).join('') : '<div class="px-2 py-4 text-xs text-muted text-center">You\'re all caught up.</div>';
  }
}

// ==========================================
// GETTING STARTED CHECKLIST
// ==========================================
function renderChecklist() {
  const el = document.getElementById('dash-checklist');
  if (!el) return;
  const items = [
    { done: db.clients.length > 0, label: 'Add your first client', page:'clients' },
    { done: db.plans.length > 0, label: 'Send a plan', page:'planner' },
    { done: db.library.length > 0, label: 'Upload a resource', page:'library' },
    { done: db.invoices.length > 0, label: 'Create an invoice', page:'invoices' },
    { done: db.cohorts.length > 0, label: 'Try a cohort or automation', page:'cohorts' },
  ];
  const doneCount = items.filter(i=>i.done).length;
  if (doneCount === items.length) { el.innerHTML = ''; el.classList.add('hidden'); return; }
  el.classList.remove('hidden');
  el.innerHTML = `
    <div class="flex items-center justify-between mb-3">
      <h3 class="display text-sm font-semibold text-white tracking-wide uppercase">Getting Started</h3>
      <span class="text-[10px] text-muted">${doneCount}/${items.length} complete</span>
    </div>
    <div class="divide-y divide-border">
      ${items.map(i => `
        <button onclick="navigateTo('${i.page}')" class="checklist-item w-full text-left">
          <span class="checklist-check ${i.done?'done':''}">${i.done?'✓':''}</span>
          <span class="text-sm ${i.done?'text-muted line-through':'text-text'}">${i.label}</span>
        </button>`).join('')}
    </div>`;
}

// ==========================================
// TASKS
// ==========================================
function renderTasks() {
  const el = document.getElementById('list-tasks');
  if (!el) return;
  el.innerHTML = db.tasks.length ? db.tasks.map(t => `
    <div class="flex items-center gap-3 bg-black border border-border rounded-lg px-3 py-2.5">
      <span class="checklist-check ${t.done?'done':''} cursor-pointer" onclick="toggleTask(${t.id})">${t.done?'✓':''}</span>
      <span class="text-sm flex-1 ${t.done?'text-muted line-through':'text-text'}">${t.text}</span>
      <button onclick="del('tasks',${t.id})" class="text-red-400/50 hover:text-red-400 text-xs">✕</button>
    </div>`).join('') : '<p class="text-muted text-sm text-center py-6">No tasks yet — add one above.</p>';
}
window.toggleTask = (id) => { const t = db.tasks.find(x=>x.id===id); if(t){ t.done = !t.done; saveData(); renderTasks(); } };
document.getElementById('form-task').onsubmit = e => {
  e.preventDefault();
  const f = new FormData(e.target);
  db.tasks.unshift({ id: Date.now(), text: f.get('text'), done: false });
  saveData(); e.target.reset(); renderTasks();
};

// ==========================================
// NUTRITION (add-on, subscribed clients only)
// ==========================================
function renderNutritionPage() {
  const subEl = document.getElementById('list-nutrition-subscribed');
  const unsubEl = document.getElementById('list-nutrition-unsubscribed');
  if (!subEl || !unsubEl) return;

  const subscribed = db.clients.filter(c => c.nutrition);
  const unsubscribed = db.clients.filter(c => !c.nutrition);

  subEl.innerHTML = subscribed.length ? subscribed.map(c => {
    const tgt = db.nutritionTargets[c.name];
    const logs = db.nutritionLogs[c.name] || [];
    const last = logs[logs.length - 1];
    return `<div class="card rounded-2xl p-5">
      <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-accent/20 flex items-center justify-center text-accent font-bold">${c.name[0]}</div>
          <div><p class="text-white font-semibold text-sm">${c.name}</p><p class="text-muted text-xs">${c.tag}</p></div>
        </div>
        <button onclick="toggleNutritionSub('${c.name.replace(/'/g,"\\'")}')" class="text-red-400/60 hover:text-red-400 text-[11px]">Disable</button>
      </div>
      ${tgt ? `
        <div class="grid grid-cols-4 gap-2 text-center mb-3">
          <div class="bg-black border border-border rounded-lg py-2"><p class="text-white text-sm font-bold">${tgt.calories}</p><p class="text-muted text-[10px]">kcal</p></div>
          <div class="bg-black border border-border rounded-lg py-2"><p class="text-white text-sm font-bold">${tgt.protein}g</p><p class="text-muted text-[10px]">protein</p></div>
          <div class="bg-black border border-border rounded-lg py-2"><p class="text-white text-sm font-bold">${tgt.carbs}g</p><p class="text-muted text-[10px]">carbs</p></div>
          <div class="bg-black border border-border rounded-lg py-2"><p class="text-white text-sm font-bold">${tgt.fats}g</p><p class="text-muted text-[10px]">fats</p></div>
        </div>
        ${last ? `<p class="text-muted text-[11px] mb-3">Last logged (${last.date}): ${last.calories} kcal, P${last.protein} C${last.carbs} F${last.fats}</p>` : '<p class="text-muted text-[11px] mb-3">No intake logged by client yet.</p>'}
      ` : '<p class="text-muted text-xs mb-3">No macro targets set yet.</p>'}
      <button onclick="openNutritionModal('${c.name.replace(/'/g,"\\'")}')" class="w-full py-2 bg-accent/15 text-accent text-xs font-semibold rounded-lg hover:bg-accent/25 transition">${tgt ? 'Edit Targets' : 'Set Targets'}</button>
    </div>`;
  }).join('') : '<p class="text-muted text-sm text-center py-8 sm:col-span-2 lg:col-span-3">No clients subscribed to Nutrition yet.</p>';

  unsubEl.innerHTML = unsubscribed.length ? unsubscribed.map(c => `
    <div class="card rounded-2xl p-5 opacity-70">
      <div class="flex items-center gap-3 mb-3">
        <div class="w-10 h-10 rounded-full bg-black border border-border flex items-center justify-center text-muted font-bold">${c.name[0]}</div>
        <div><p class="text-white font-semibold text-sm">${c.name}</p><p class="text-muted text-xs">${c.tag}</p></div>
      </div>
      <button onclick="toggleNutritionSub('${c.name.replace(/'/g,"\\'")}')" class="w-full py-2 bg-black border border-dashed border-border text-muted text-xs font-medium rounded-lg hover:border-accent/50 hover:text-accent transition">+ Enable Nutrition Add-on</button>
    </div>`).join('') : '<p class="text-muted text-sm text-center py-8 sm:col-span-2 lg:col-span-3">Every client is subscribed to Nutrition.</p>';
}
window.toggleNutritionSub = (name) => {
  const c = db.clients.find(x => x.name === name);
  if (c) {
    c.nutrition = !c.nutrition; saveData(); renderAll();
    toast(c.nutrition ? `✅ Nutrition enabled for ${name}.` : `Nutrition disabled for ${name}.`);
    if (!document.getElementById('clientModal').classList.contains('hidden')) openClientModal(name);
  }
};
window.openNutritionModal = (name) => {
  document.getElementById('nutrition-modal-client').innerText = name;
  const form = document.getElementById('form-nutrition-targets');
  form.dataset.client = name;
  const tgt = db.nutritionTargets[name];
  form.querySelector('[name="calories"]').value = tgt ? tgt.calories : '';
  form.querySelector('[name="protein"]').value = tgt ? tgt.protein : '';
  form.querySelector('[name="carbs"]').value = tgt ? tgt.carbs : '';
  form.querySelector('[name="fats"]').value = tgt ? tgt.fats : '';
  form.querySelector('[name="notes"]').value = tgt ? (tgt.notes || '') : '';
  document.getElementById('nutritionModal').classList.remove('hidden');
};
window.closeNutritionModal = () => document.getElementById('nutritionModal').classList.add('hidden');
document.getElementById('form-nutrition-targets').onsubmit = e => {
  e.preventDefault();
  const f = new FormData(e.target);
  const name = e.target.dataset.client;
  db.nutritionTargets[name] = {
    calories: parseFloat(f.get('calories')) || 0,
    protein: parseFloat(f.get('protein')) || 0,
    carbs: parseFloat(f.get('carbs')) || 0,
    fats: parseFloat(f.get('fats')) || 0,
    notes: f.get('notes') || ''
  };
  saveData(); closeNutritionModal(); renderNutritionPage();
  toast(`✅ Macro targets saved for ${name}.`);
};

// ==========================================
// I18N (English / German / Arabic / French / Spanish)
// ==========================================
const I18N = {
  en: {
    "sidebar.search":"Quick search","sidebar.help":"Help & Guide","sidebar.overview":"Overview",
    "nav.dashboard":"Dashboard","nav.messages":"Messages","nav.clients":"Clients",
    "sidebar.coachingTools":"Coaching Tools","nav.planner":"Plan Builder","nav.nutrition":"Nutrition",
    "nav.live":"Live Sessions","nav.library":"Resource Library","nav.feedback":"Client Feedback","nav.review":"Vision Review",
    "sidebar.clientMonitoring":"Client Monitoring","nav.points":"Points & Streaks","nav.readiness":"Readiness",
    "nav.vault":"Progress Vault","nav.community":"Community","nav.cohorts":"Cohorts",
    "sidebar.automation":"Automation & Insight","nav.automations":"Automations","nav.reports":"Reports","nav.contracts":"Contracts",
    "sidebar.business":"Business","nav.pipeline":"Pipeline","nav.invoices":"Invoices","nav.shop":"Shop",
    "nav.referrals":"Referrals","nav.verification":"Verification","nav.account":"Account",
    "sidebar.viewAsClient":"View as Client"
  },
  de: {
    "sidebar.search":"Schnellsuche","sidebar.help":"Hilfe & Anleitung","sidebar.overview":"Übersicht",
    "nav.dashboard":"Dashboard","nav.messages":"Nachrichten","nav.clients":"Klienten",
    "sidebar.coachingTools":"Coaching-Tools","nav.planner":"Plan-Ersteller","nav.nutrition":"Ernährung",
    "nav.live":"Live-Sitzungen","nav.library":"Ressourcen","nav.feedback":"Kundenfeedback","nav.review":"Video-Analyse",
    "sidebar.clientMonitoring":"Klienten-Überwachung","nav.points":"Punkte & Serien","nav.readiness":"Bereitschaft",
    "nav.vault":"Fortschritt","nav.community":"Community","nav.cohorts":"Gruppen",
    "sidebar.automation":"Automatisierung","nav.automations":"Automatisierungen","nav.reports":"Berichte","nav.contracts":"Verträge",
    "sidebar.business":"Geschäftliches","nav.pipeline":"Pipeline","nav.invoices":"Rechnungen","nav.shop":"Shop",
    "nav.referrals":"Empfehlungen","nav.verification":"Verifizierung","nav.account":"Konto",
    "sidebar.viewAsClient":"Als Klient ansehen"
  },
  ar: {
    "sidebar.search":"بحث سريع","sidebar.help":"المساعدة والدليل","sidebar.overview":"نظرة عامة",
    "nav.dashboard":"الرئيسية","nav.messages":"الرسائل","nav.clients":"العملاء",
    "sidebar.coachingTools":"أدوات التدريب","nav.planner":"باني الخطط","nav.nutrition":"التغذية",
    "nav.live":"الجلسات المباشرة","nav.library":"مكتبة الموارد","nav.feedback":"تقييمات العملاء","nav.review":"مراجعة الأداء",
    "sidebar.clientMonitoring":"متابعة العملاء","nav.points":"النقاط والسلاسل","nav.readiness":"الجاهزية",
    "nav.vault":"خزينة التقدم","nav.community":"المجتمع","nav.cohorts":"المجموعات",
    "sidebar.automation":"الأتمتة والرؤى","nav.automations":"الأتمتة","nav.reports":"التقارير","nav.contracts":"العقود",
    "sidebar.business":"الأعمال","nav.pipeline":"خط المبيعات","nav.invoices":"الفواتير","nav.shop":"المتجر",
    "nav.referrals":"الإحالات","nav.verification":"التحقق","nav.account":"الحساب",
    "sidebar.viewAsClient":"عرض كعميل"
  },
  fr: {
    "sidebar.search":"Recherche rapide","sidebar.help":"Aide et guide","sidebar.overview":"Aperçu",
    "nav.dashboard":"Tableau de bord","nav.messages":"Messages","nav.clients":"Clients",
    "sidebar.coachingTools":"Outils de coaching","nav.planner":"Créateur de programme","nav.nutrition":"Nutrition",
    "nav.live":"Séances en direct","nav.library":"Bibliothèque","nav.feedback":"Avis clients","nav.review":"Analyse vidéo",
    "sidebar.clientMonitoring":"Suivi des clients","nav.points":"Points et séries","nav.readiness":"Forme",
    "nav.vault":"Suivi des progrès","nav.community":"Communauté","nav.cohorts":"Groupes",
    "sidebar.automation":"Automatisation","nav.automations":"Automatisations","nav.reports":"Rapports","nav.contracts":"Contrats",
    "sidebar.business":"Affaires","nav.pipeline":"Pipeline","nav.invoices":"Factures","nav.shop":"Boutique",
    "nav.referrals":"Parrainage","nav.verification":"Vérification","nav.account":"Compte",
    "sidebar.viewAsClient":"Voir comme client"
  },
  es: {
    "sidebar.search":"Búsqueda rápida","sidebar.help":"Ayuda y guía","sidebar.overview":"Resumen",
    "nav.dashboard":"Inicio","nav.messages":"Mensajes","nav.clients":"Clientes",
    "sidebar.coachingTools":"Herramientas","nav.planner":"Creador de planes","nav.nutrition":"Nutrición",
    "nav.live":"Sesiones en vivo","nav.library":"Biblioteca","nav.feedback":"Comentarios","nav.review":"Revisión técnica",
    "sidebar.clientMonitoring":"Seguimiento de clientes","nav.points":"Puntos y rachas","nav.readiness":"Preparación",
    "nav.vault":"Progreso","nav.community":"Comunidad","nav.cohorts":"Grupos",
    "sidebar.automation":"Automatización","nav.automations":"Automatizaciones","nav.reports":"Informes","nav.contracts":"Contratos",
    "sidebar.business":"Negocio","nav.pipeline":"Pipeline","nav.invoices":"Facturas","nav.shop":"Tienda",
    "nav.referrals":"Referidos","nav.verification":"Verificación","nav.account":"Cuenta",
    "sidebar.viewAsClient":"Ver como cliente"
  }
};

function t(key) {
  const lang = (db && db.lang) || 'en';
  return (I18N[lang] && I18N[lang][key]) || I18N.en[key] || key;
}
function applyTranslations() {
  const lang = (db && db.lang) || 'en';
  document.documentElement.lang = lang;
  document.documentElement.dir = lang === 'ar' ? 'rtl' : 'ltr';
  document.querySelectorAll('[data-i18n]').forEach(el => {
    el.innerText = t(el.getAttribute('data-i18n'));
  });
  document.querySelectorAll('.lang-option').forEach(el => {
    el.classList.toggle('lang-option-active', el.dataset.lang === lang);
  });
  const flags = { en:'🇬🇧', de:'🇩🇪', ar:'🇸🇦', fr:'🇫🇷', es:'🇪🇸' };
  const flagEl = document.getElementById('lang-current-flag');
  if (flagEl) flagEl.innerText = flags[lang] || flags.en;
}
window.toggleLangPanel = (e) => {
  if (e) e.stopPropagation();
  document.getElementById('lang-panel').classList.toggle('hidden');
};
document.addEventListener('click', e => {
  const wrap = document.getElementById('langSwitchWrap');
  if (wrap && !wrap.contains(e.target)) {
    const panel = document.getElementById('lang-panel');
    if (panel) panel.classList.add('hidden');
  }
});
window.setLang = (lang) => {
  db.lang = lang;
  saveData();
  document.getElementById('lang-panel').classList.add('hidden');
  applyTranslations();
  const msgs = { en:'🌐 Language set to English', de:'🌐 Sprache auf Deutsch eingestellt', ar:'🌐 تم تعيين اللغة إلى العربية', fr:'🌐 Langue définie sur le français', es:'🌐 Idioma establecido en español' };
  toast(msgs[lang] || msgs.en);
};

// ==========================================
// COMMAND PALETTE
// ==========================================
function navigateTo(page) {
  document.querySelectorAll('.nav-link').forEach(l => { l.classList.remove('active','text-white'); l.classList.add('text-muted'); });
  const link = document.querySelector(`.nav-link[data-page="${page}"]`);
  if (link) { link.classList.add('active','text-white'); link.classList.remove('text-muted'); }
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  const pageEl = document.getElementById('page-'+page);
  if (pageEl) pageEl.classList.add('active');
  document.querySelectorAll('.bottom-nav-item').forEach(b => b.classList.remove('active'));
  const bnMatch = ['dashboard','clients','messages','pipeline'].includes(page) ? page : null;
  if (bnMatch) { const bn = document.querySelector(`.bottom-nav-item[data-bn="${bnMatch}"]`); if (bn) bn.classList.add('active'); }
  const main = document.querySelector('main'); if (main) main.scrollTop = 0;
  renderAll();
  if (page === 'review') setTimeout(initCanvas, 100);
}

function paletteItems() {
  const pages = [
    ['dashboard','Dashboard','◆'],['messages','Messages','✉'],['clients','Clients','◇'],
    ['planner','Plan Builder','▤'],['nutrition','Nutrition','◍'],['live','Live Sessions','●'],['library','Resource Library','▦'],
    ['feedback','Client Feedback','★'],['review','Vision Review','▶'],['points','Points & Streaks','◈'],
    ['readiness','Readiness','◎'],['vault','Progress Vault','▣'],['community','Community','◐'],
    ['cohorts','Cohorts','⬢'],['automations','Automations','⚡'],['reports','Reports','▩'],['contracts','Contracts','✎'],
    ['pipeline','Pipeline','⬡'],['invoices','Invoices','▥'],['shop','Shop','▨'],
    ['referrals','Referrals','↗'],['verification','Verification','✓'],['account','Account','◉']
  ].map(([page,label,icon]) => ({ type:'page', page, label, icon, sub:'Go to page' }));

  const clients = db.clients.map(c => ({ type:'client', name:c.name, label:c.name, icon:'◇', sub:'Open client profile' }));
  return pages.concat(clients);
}

window.openPalette = () => {
  document.getElementById('paletteOverlay').classList.remove('hidden');
  const input = document.getElementById('paletteInput');
  input.value = '';
  filterPalette();
  if (window.innerWidth >= 768) setTimeout(()=>input.focus(), 50);
};
window.closePalette = () => document.getElementById('paletteOverlay').classList.add('hidden');

window.filterPalette = () => {
  const q = document.getElementById('paletteInput').value.toLowerCase();
  const items = paletteItems().filter(i => i.label.toLowerCase().includes(q));
  document.getElementById('paletteResults').innerHTML = items.length ? items.map(i => `
    <button onclick="paletteSelect('${i.type}','${(i.page||i.name).replace(/'/g,"\\'")}')" class="w-full flex items-center gap-3 px-5 py-2.5 hover:bg-card2 text-left transition">
      <span class="text-accent w-5 text-center">${i.icon}</span>
      <span class="text-white text-sm flex-1">${i.label}</span>
      <span class="text-muted text-[11px]">${i.sub}</span>
    </button>`).join('') : '<p class="text-muted text-sm px-5 py-6 text-center">No matches.</p>';
};
window.paletteSelect = (type, value) => {
  closePalette();
  if (type === 'page') navigateTo(value);
  else openClientModal(value);
};
document.addEventListener('keydown', e => {
  if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); openPalette(); }
  if (e.key === 'Escape') { closePalette(); closeClientModal(); closeAddLead(); }
});

// ==========================================
// VISION REVIEW CANVAS
// ==========================================
let canvas, ctx2d, drawing=false;
function initCanvas() {
  canvas = document.getElementById('drawCanvas');
  if (!canvas) return;
  const rect = canvas.parentElement.getBoundingClientRect();
  canvas.width = rect.width; canvas.height = rect.height;
  ctx2d = canvas.getContext('2d');
  ctx2d.fillStyle='#0D0D0D'; ctx2d.fillRect(0,0,canvas.width,canvas.height);
  ctx2d.fillStyle='#1E1E1E'; ctx2d.font='16px Inter'; ctx2d.textAlign='center';
  ctx2d.fillText('Client video frame loads here. Draw annotations below.',canvas.width/2,canvas.height/2);
  ctx2d.textAlign='start'; ctx2d.lineCap='round';
  canvas.onmousedown=e=>{drawing=true;ctx2d.beginPath();ctx2d.moveTo(e.offsetX,e.offsetY);};
  canvas.onmousemove=e=>{if(!drawing)return;ctx2d.strokeStyle=document.getElementById('draw-color').value;ctx2d.lineWidth=document.getElementById('draw-size').value;ctx2d.lineTo(e.offsetX,e.offsetY);ctx2d.stroke();};
  canvas.onmouseup=()=>drawing=false;
  canvas.onmouseleave=()=>drawing=false;
}
window.clearCanvas=()=>{ if(ctx2d){ ctx2d.clearRect(0,0,canvas.width,canvas.height); initCanvas(); } };
window.sendAnnotation=()=>{ toast('✅ Annotation sent to client.'); };

// ==========================================
// VAULT SLIDER
// ==========================================
document.getElementById('vaultSlider').addEventListener('input', e => {
  const v = e.target.value;
  document.getElementById('vaultAfter').style.clipPath = `inset(0 0 0 ${v}%)`;
  document.getElementById('vaultLine').style.left = v+'%';
  document.getElementById('vaultHandle').style.left = v+'%';
});

// ==========================================
// CHART
// ==========================================
let earningsChartInstance = null;
function cssVar(name) { return getComputedStyle(document.documentElement).getPropertyValue(name).trim() || '#6C5CE7'; }
function buildChart() {
  const el = document.getElementById('earningsChart');
  if (!el) return;
  if (earningsChartInstance) earningsChartInstance.destroy();
  const accent = cssVar('--accent-color');
  const cctx = el.getContext('2d');
  const grad = cctx.createLinearGradient(0,0,0,300);
  grad.addColorStop(0, accent+'4D'); grad.addColorStop(1, accent+'00');

  // Build the last 6 months as real labels, then sum this coach's actual paid invoices per month.
  const months = [];
  const now = new Date();
  for (let i = 5; i >= 0; i--) {
    const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
    months.push({ label: d.toLocaleDateString('en-US', { month: 'short' }), year: d.getFullYear(), month: d.getMonth() });
  }
  const paid = (db.invoices || []).filter(inv => inv.status === 'Paid' && inv.due);
  const monthlyTotals = months.map(m => {
    return paid.reduce((sum, inv) => {
      const d = new Date(inv.due);
      return (d.getFullYear() === m.year && d.getMonth() === m.month) ? sum + (inv.amount || 0) : sum;
    }, 0);
  });
  const hasAnyRevenue = monthlyTotals.some(v => v > 0);

  earningsChartInstance = new Chart(cctx,{type:'line',data:{labels: months.map(m=>m.label),datasets:[
    {label: hasAnyRevenue ? 'Paid invoices' : 'No revenue yet', data: monthlyTotals, borderColor:accent, backgroundColor:grad, borderWidth:3, fill:true, tension:0.35, pointRadius:hasAnyRevenue?4:0, pointBackgroundColor:'#000', pointBorderColor:accent, pointBorderWidth:2}
  ]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{color:'#8A8A8E',usePointStyle:true,pointStyle:'circle',font:{size:11,family:'Inter'}}}},scales:{x:{grid:{color:'rgba(255,255,255,0.03)'},ticks:{color:'#8A8A8E',font:{size:11}}},y:{beginAtZero:true,grid:{color:'rgba(255,255,255,0.03)'},ticks:{color:'#8A8A8E',callback:v=>'$'+v,font:{size:11}}}}}});
}

// ==========================================
// INIT
// ==========================================
// ---- Mobile viewport height fix (iOS Safari address-bar / keyboard resize bug) ----
function syncAppHeight() {
  const h = (window.visualViewport ? window.visualViewport.height : window.innerHeight);
  document.documentElement.style.setProperty('--app-height', h + 'px');
}
syncAppHeight();
window.addEventListener('resize', syncAppHeight);
window.addEventListener('orientationchange', () => setTimeout(syncAppHeight, 200));
if (window.visualViewport) window.visualViewport.addEventListener('resize', syncAppHeight);

// ==========================================
// STARFIELD (cosmic background)
// ==========================================
(function initStars() {
  const wrap = document.getElementById('stars');
  if (!wrap) return;
  const count = window.innerWidth < 640 ? 45 : 90;
  for (let i = 0; i < count; i++) {
    const s = document.createElement('div');
    s.className = 'cs-star';
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

(async function init() {
  try {
    await enterApp();
  } catch (err) {
    console.error('Startup error:', err);
    db = db || blankData('Coach', '', 'Fitness / Strength');
    try { renderAll(); } catch (e2) { console.error('Fallback render also failed:', e2); }
  } finally {
    const veil = document.getElementById('loadingVeil');
    if (veil) { veil.style.opacity = '0'; setTimeout(()=>veil.remove(), 300); }
  }
})();
</script>

<!-- ================= QUICK ACTIONS FAB ================= -->
<div id="quickActionsWrap" class="fixed bottom-6 right-6 z-40 flex flex-col items-end gap-3">
  <div id="quick-actions-menu" class="hidden flex flex-col items-end gap-2.5 mb-1">
    <button onclick="quickAction('plan')" class="fab-item flex items-center gap-2 pl-4 pr-1.5 py-1.5 bg-card border border-border rounded-full text-white text-xs font-medium hover:border-accent/50 transition"><span>New Plan</span><span class="w-8 h-8 rounded-full bg-accent/15 flex items-center justify-center">▤</span></button>
    <button onclick="quickAction('invoice')" class="fab-item flex items-center gap-2 pl-4 pr-1.5 py-1.5 bg-card border border-border rounded-full text-white text-xs font-medium hover:border-accent/50 transition"><span>New Invoice</span><span class="w-8 h-8 rounded-full bg-accent/15 flex items-center justify-center">▥</span></button>
    <button onclick="quickAction('lead')" class="fab-item flex items-center gap-2 pl-4 pr-1.5 py-1.5 bg-card border border-border rounded-full text-white text-xs font-medium hover:border-accent/50 transition"><span>Add Lead</span><span class="w-8 h-8 rounded-full bg-accent/15 flex items-center justify-center">⬡</span></button>
    <button onclick="quickAction('message')" class="fab-item flex items-center gap-2 pl-4 pr-1.5 py-1.5 bg-card border border-border rounded-full text-white text-xs font-medium hover:border-accent/50 transition"><span>Message Client</span><span class="w-8 h-8 rounded-full bg-accent/15 flex items-center justify-center">✉</span></button>
  </div>
  <button id="quick-actions-fab" onclick="toggleQuickActions()" class="w-14 h-14 rounded-full bg-accent text-white text-2xl flex items-center justify-center hover:bg-indigo-500 transition">+</button>
</div>

<!-- ================= BOTTOM NAV (mobile) ================= -->
<div id="bottomNav">
  <a href="#" data-bn="dashboard" onclick="event.preventDefault();navigateTo('dashboard')" class="bottom-nav-item active"><span class="bn-icon">◆</span><span>Dashboard</span></a>
  <a href="#" data-bn="clients" onclick="event.preventDefault();navigateTo('clients')" class="bottom-nav-item"><span class="bn-icon">◇</span><span>Clients</span></a>
  <a href="#" data-bn="messages" onclick="event.preventDefault();navigateTo('messages')" class="bottom-nav-item"><span class="bn-icon">✉</span><span id="bn-msg-dot-wrap"></span><span>Messages</span></a>
  <a href="#" data-bn="pipeline" onclick="event.preventDefault();navigateTo('pipeline')" class="bottom-nav-item"><span class="bn-icon">⬡</span><span>Pipeline</span></a>
  <a href="#" data-bn="more" onclick="event.preventDefault();openSidebar()" class="bottom-nav-item"><span class="bn-icon">☰</span><span>Menu</span></a>
</div>

</body>
</html>