function uiLabel(value){return {"Active": "نشط", "Paused": "متوقف", "Paid": "مدفوع", "Free": "مجاني", "Training": "تدريب", "Workout": "تمرين", "Nutrition": "تغذية", "Mindset": "عادات وأهداف", "Business Goals": "أهداف العمل", "Sent": "مسجل", "Signed": "تم التوقيع", "Pending": "قيد الانتظار", "Lead": "عميل محتمل", "Contacted": "تم التواصل", "Qualified": "مؤهّل", "Won": "مكتمل", "Lost": "متوقف", "Scheduled": "محدد", "Completed": "مكتمل", "Cancelled": "ملغى"}[value]||value;}

// ==========================================
// STORAGE
// ==========================================
const DATA_KEY = 'penguins_platform_data';
let db = null;

function defaultData() { return PenguinsCore.normalize({}); }

function blankData(name, email, niche) {
  const d = defaultData();
  d.account = { name: name || 'New Coach', email: email || '', niche: niche || 'لياقة وقوة', bio: '', rate: 0 };
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
  d.metrics = {};
  d.referrals = { clicks: 0, signups: 0 };
  d.businessGoals = { revenueTarget: 0, clientsTarget: 0 };
  d.seenWelcome = false;
  return d;
}

const sync = new PenguinsStore(penguinsClient, (state,message) => {
 const status=document.getElementById('sync-status');status.textContent=message;status.dataset.state=state;
 const alert=document.getElementById('sync-alert');alert.hidden=!['error','offline','conflict'].includes(state);
 document.getElementById('sync-alert-message').textContent=message;
});
async function logOut() {
 if(sync.dirty && !confirm('Some changes are not saved. Export a backup before signing out. Sign out anyway?'))return;
 const {error}=await penguinsClient.auth.signOut();
 if(error){toast(error.message);return;}location.assign('auth.html');
}
async function loadData() {
 try {
  db=await sync.load(blankData);
  if(sync.draft) {
    const banner=document.getElementById('draft-banner');banner.hidden=false;
    document.getElementById('restore-draft').onclick=()=>{
      if(!confirm('Restore the unsaved device draft? A cloud backup is exported first.'))return;
      exportWorkspace();db=PenguinsCore.normalize(sync.draft.data);renderAll();applyTheme();applyColorMode();applyTranslations();
      if(sync.draft.baseVersion!==sync.version){sync.conflict=true;sync.queue(db);toast('Draft restored for review. Export it before reloading; cloud data was not overwritten.');}
      else saveData();banner.hidden=true;
    };
    document.getElementById('discard-draft').onclick=()=>{if(confirm('Discard this unsaved device draft?')){localStorage.removeItem(sync.draftKey);banner.hidden=true;}};
  }
 }catch(error){
  const main=document.createElement('main');main.className='recovery-card';
  const heading=document.createElement('h1');heading.textContent='Your workspace could not load';
  const message=document.createElement('p');message.textContent=error.message;
  const button=document.createElement('button');button.textContent='Try again';button.onclick=()=>location.reload();
  const link=document.createElement('a');link.href='auth.html';link.textContent='Back to login';
  main.append(heading,message,button,link);document.body.className='recovery-page';document.body.replaceChildren(main);throw error;
 }
}
function saveData(){try{sync.queue(db);}catch(error){toast(error.message);}}

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
    { l:'Total Revenue', v:'$'+Math.round(totalRevenue).toLocaleString(), i:'💰', c: pendingInvoices ? pendingInvoices+' invoice'+(pendingInvoices>1?'s':'')+' pending' : 'كل الفواتير مسددة' },
    { l:'العملاء النشطون', v: activeClients, i:'👥', c: activeClients ? activeClients+' being coached' : 'أضف أول سجل عميل' },
    { l:'Plans Sent', v: (db.plans||[]).length, i:'▤', c: (db.plans||[]).length ? 'Total sent' : 'جهّز خطة في باني الخطط' },
    { l:'Library Resources', v: (db.library||[]).length, i:'▦', c: paidResources+' paid' }
  ].map(s=>`<div class="card rounded-2xl p-6"><div class="flex justify-between mb-3"><span class="text-muted text-sm">${s.l}</span><span class="text-xl">${s.i}</span></div><h3 class="display text-3xl font-bold text-white">${s.v}</h3><span class="text-muted text-xs">${s.c}</span></div>`).join('');

  document.getElementById('dash-activity').innerHTML = db.feedback.length
    ? db.feedback.slice(0,4).map(f=>`<div class="flex items-start gap-3"><div class="w-8 h-8 rounded-full bg-accent/15 flex items-center justify-center text-accent text-xs font-bold shrink-0">${f.client[0]}</div><div><p class="text-sm text-white">${f.client}</p><p class="text-xs text-muted">${f.msg.substring(0,50)}...</p></div></div>`).join('')
    : '<p class="text-muted text-sm text-center py-6">لا توجد تقييمات في السجل بعد.</p>';

  // Plans
  document.getElementById('list-plans').innerHTML = db.plans.length ? db.plans.map(p=>`
    <div class="bg-black border border-border rounded-xl p-4">

      <div class="flex justify-between items-start">

        <div>

          <div class="flex items-center gap-2"><h4 class="text-white font-semibold">${p.title}</h4><span class="text-[10px] px-2 py-0.5 rounded-full bg-accent/15 text-accent">${uiLabel(p.type)}</span></div>

          <p class="text-accent text-xs mt-1">→ Sent to: ${p.client}</p>

          <p class="text-muted text-xs mt-1">${p.sent}</p>

          <p class="text-text text-sm mt-2 whitespace-pre-line">${window.PenguinsExerciseStudio?.summary(p.details)||p.details}</p>

        </div>

        <button onclick="del('plans',${p.id})" class="text-red-400/60 hover:text-red-400 text-xs">إزالة</button>

      </div>

    </div>`).join('') : '<p class="text-muted text-sm text-center py-10">لا توجد خطط في السجل بعد.</p>';

  // Live
  document.getElementById('list-live').innerHTML = db.sessions.length ? db.sessions.map(s=>`
    <div class="bg-black border border-border rounded-xl p-4 flex justify-between items-center">

      <div><h4 class="text-white font-semibold">${s.topic}</h4><p class="text-muted text-xs">${new Date(s.time).toLocaleString()} · ${s.access}</p></div>

      <div class="flex gap-2"><button class="px-3 py-1 bg-accent/15 text-accent text-xs rounded-lg">ابدأ</button><button onclick="del('sessions',${s.id})" class="text-red-400 text-xs">إلغاء</button></div>

    </div>`).join('') : '<p class="text-muted text-sm text-center py-10">لا توجد جلسات محددة.</p>';

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

        <span class="text-muted text-xs">${r.downloads} تنزيل</span>

        <button onclick="del('library',${r.id})" class="text-red-400/60 hover:text-red-400 text-xs">إزالة</button>

      </div>

    </div>`).join('') : '<p class="text-muted text-sm text-center py-10 col-span-2">لا توجد موارد مسجلة.</p>';

  // Feedback
  document.getElementById('list-feedback').innerHTML = db.feedback.map(f=>`
    <div class="bg-black border border-border rounded-xl p-4">

      <div class="flex justify-between"><div><h4 class="text-white font-semibold">${f.client}</h4><p class="text-muted text-xs">${f.date}</p></div><span class="text-gold text-sm">${'★'.repeat(f.rating)}${'☆'.repeat(5-f.rating)}</span></div>

      <p class="text-text text-sm mt-2">"${f.msg}"</p>

      <div class="mt-3 flex gap-2"><input type="text" placeholder="Reply..." class="flex-1 input-dark rounded-lg px-3 py-1.5 text-xs"><button class="px-3 py-1.5 bg-accent/15 text-accent text-xs rounded-lg">إرسال</button></div>

    </div>`).join('');

  // Store
  renderShop();

  // Community
  document.getElementById('list-community').innerHTML = db.community.map(p=>`
    <div class="card rounded-2xl p-6">

      <div class="flex justify-between items-start"><div><h4 class="text-white font-semibold">${p.user}</h4><span class="text-[10px] px-2 py-0.5 rounded-full ${p.type==='تحدٍّ'?'bg-gold/15 text-gold':p.type==='إعلان'?'bg-accent/15 text-accent':'bg-border text-muted'}">${uiLabel(p.type)}</span></div><span class="text-muted text-xs">${p.date}</span></div>

      <p class="text-text mt-3">${p.text}</p>

      <div class="mt-3 flex justify-between items-center"><button onclick="likePost(${p.id})" class="text-sm text-accent hover:underline">❤️ ${p.likes}</button>${p.user!=='Coach'?`<button onclick="del('community',${p.id})" class="text-xs text-red-400/60 hover:text-red-400">إزالة المنشور</button>`:''}</div>

    </div>`).join('');

  // Submissions
  document.getElementById('list-submissions').innerHTML = db.submissions.map(s=>`
    <div class="bg-black border border-border rounded-xl p-3 cursor-pointer hover:border-accent/50 transition" onclick="loadSubmission(${s.id})">

      <h4 class="text-white text-sm font-semibold">${s.client}</h4>

      <p class="text-muted text-xs">${s.exercise}</p>

      <span class="text-[10px] px-2 py-0.5 rounded-full mt-1 inline-block ${s.status==='Pending Review'?'bg-gold/15 text-gold':'bg-green-500/15 text-green-400'}">${uiLabel(s.status)}</span>

    </div>`).join('');

  // Points
  document.getElementById('total-points').innerText = db.pointsClients.reduce((a,b)=>a+b.points,0);
  document.getElementById('points-leaderboard').innerHTML = db.pointsClients.map((c,i)=>`
    <div class="flex items-center p-4 bg-black border border-border rounded-xl">

      <span class="text-lg font-bold text-muted w-8">${i+1}</span>

      <div class="w-10 h-10 rounded-full bg-gradient-to-br from-accent/40 to-indigo-700/40 flex items-center justify-center text-white text-sm font-bold ml-2">${c.name[0]}</div>

      <div class="ml-4 flex-1"><p class="text-white text-sm font-medium">${c.name}</p><p class="text-muted text-xs">${c.streak} أيام متواصلة · ${c.completion}٪ إنجاز</p></div>

      <div class="text-right"><p class="text-accent font-bold">${c.points} نقطة</p></div>

    </div>`).join('');

  // Readiness
  document.getElementById('readiness-list').innerHTML = db.readiness.map(r=>{
    const colors = { green:'border-green-500/30 bg-green-500/5', yellow:'border-gold/30 bg-gold/5', red:'border-red-500/30 bg-red-500/5' };
    const labels = { green:'🟢 Go Hard', yellow:'🟡 Moderate', red:'🔴 Recovery' };
    const recs = { green:'Keep today\'s heavy compounds. Push for PR.', yellow:'Drop weight 10%. Focus on form & tempo.', red:'Auto-swapped to 30min mobility flow.' };
    return `<div class="p-4 border rounded-xl ${colors[r.status]}">

      <div class="flex justify-between items-center"><h4 class="text-white font-semibold">${r.name}</h4><span class="text-sm">${labels[r.status]}</span></div>

      <div class="flex gap-4 mt-2 text-xs text-muted"><span>النوم: ${r.sleep}/10</span><span>الإجهاد: ${r.sore}/10</span><span>الضغط: ${r.stress}/10</span></div>

      <p class="text-text text-sm mt-2">${recs[r.status]}</p>

      <button class="mt-2 text-xs text-accent hover:underline">تعديل المؤشر</button>

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
window.copyRef = () => { const el=document.getElementById('ref-link'); navigator.clipboard.writeText(el.value); event.target.innerText='Copied!'; setTimeout(()=>event.target.innerText='نسخ',2000); };

document.getElementById('form-plan').onsubmit = e => {
  e.preventDefault();
  let f = new FormData(e.target);
  const workoutText = builtWorkout.length ? builtWorkout.map(ex => `${ex.name}: ${ex.scheme}`).join('\n') : '';
  const notes = f.get('details') || '';
  let details;try{const combined=[workoutText,notes].filter(Boolean).join('\n\n');details=window.PenguinsCoachPlan?window.PenguinsCoachPlan.serialize(combined):(combined||'No details added.');}catch(error){toast(error.message);return;}
  db.plans.unshift({id:Date.now(),client:f.get('client'),title:f.get('title'),type:f.get('type'),details,sent:'الآن'});
  saveData(); e.target.reset(); window.PenguinsCoachPlan?.clear(); builtWorkout = []; renderAll();
  toast('✅ Plan recorded for ' + f.get('client') + '.');
};
document.getElementById('form-shop').onsubmit = e => {
  e.preventDefault();
  let f = new FormData(e.target);
  db.shop.push({
    id: Date.now(), name: f.get('name'), price: parseFloat(f.get('price')),
    salePrice: f.get('salePrice') ? parseFloat(f.get('salePrice')) : null,
    category: f.get('category'), image: f.get('image'),
    stock: f.get('stock') ? parseInt(f.get('stock')) : null,
    featured: f.get('featured') === 'on', مباع: 0
  });
  saveData(); e.target.reset(); renderAll();
  toast('✅ Product added to shop.');
};

// ==========================================
// SHOP (metallic UI, filters, stock, featured)
// ==========================================
let shopFilter = 'All';
const SHOP_CATEGORIES = ['All','منتج','منتج رقمي','باقة تدريب','تعاون تجاري'];
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
          ${p.featured ? '<span class="metal-badge-gold text-[9px] font-bold px-2 py-0.5 rounded-full">★ مميّز</span>' : ''}
          ${onSale ? '<span class="metal-badge-silver text-[9px] font-bold px-2 py-0.5 rounded-full">عرض</span>' : ''}
        </div>
        ${outOfStock ? '<div class="absolute inset-0 bg-black/70 flex items-center justify-center"><span class="text-red-400 text-xs font-bold tracking-wide">نفد المخزون</span></div>' : ''}
      </div>

      <div class="p-4">

        <h4 class="text-white font-semibold text-sm">${p.name}</h4>

        <div class="flex items-center gap-2 mt-1">
          ${onSale ? `<span class="text-muted text-xs line-through">$${p.price.toFixed(2)}</span><span class="text-accent font-bold text-sm">$${p.salePrice.toFixed(2)}</span>` : `<span class="text-accent font-bold text-sm">$${p.price.toFixed(2)}</span>`}
        </div>

        <p class="text-muted text-[10px] mt-1">${uiLabel(p.category)} · ${p.sold||0} مباع</p>
        ${stockPct !== null ? `
          <div class="mt-2">

            <div class="flex justify-between text-[10px] text-muted mb-1"><span>${p.stock} في المخزون</span>${lowStockFlag?'<span class="text-gold">مخزون منخفض</span>':''}</div>

            <div class="metal-stock-bar"><div class="metal-stock-fill" style="width:${stockPct}%"></div></div>

          </div>` : '<p class="text-muted text-[10px] mt-2">مخزون غير محدود</p>'}
        <div class="flex gap-2 mt-3">

          <button onclick="openEditProduct(${p.id})" class="flex-1 py-1.5 bg-accent/15 text-accent text-xs font-semibold rounded-lg hover:bg-accent/25 transition">تعديل</button>

          <button onclick="del('shop',${p.id})" class="px-3 py-1.5 text-red-400/60 hover:text-red-400 text-xs">إزالة</button>

        </div>

      </div>

    </div>`;
  }).join('') : '<p class="text-muted text-sm text-center py-10 col-span-2">لا توجد منتجات في هذا القسم.</p>';
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
document.getElementById('form-community').onsubmit = e => { e.preventDefault(); let f=new FormData(e.target); db.community.unshift({id:Date.now(),user:'Coach',type:f.get('type'),text:f.get('post'),likes:0,date:'الآن'}); saveData(); e.target.reset(); renderAll(); };
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
  db.pendingAccountChange = { ...requested, submittedAt: 'الآن' };
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

          <span class="text-[10px] px-2 py-0.5 rounded-full bg-gold/15 text-gold font-semibold">طلب تعديل في السجل</span>

          <span class="text-muted text-xs">تم التسجيل ${p.submittedAt}</span>

        </div>

        <p class="text-text text-sm mb-3">طلب مسجل داخل مساحة العمل فقط. الاعتماد الفعلي من قسم الحساب والشهادات.</p>

        <div class="bg-black border border-border rounded-lg p-3 text-xs text-muted space-y-1 mb-4">

          <p><span class="text-white">الاسم:</span> ${p.name}</p>

          <p><span class="text-white">البريد:</span> ${p.email || '—'}</p>

          <p><span class="text-white">المجال:</span> ${p.niche}</p>

          <p><span class="text-white">السعر:</span> $${p.rate}/hr</p>

        </div>

        <div class="flex gap-2">

          <button onclick="cancelAccountRequest()" class="flex-1 py-2 bg-black border border-border text-muted text-xs font-medium rounded-lg hover:text-white transition">سحب الطلب</button>

          <button onclick="approveAccountChange()" class="flex-1 py-2 bg-accent/15 text-accent text-xs font-semibold rounded-lg hover:bg-accent/25 transition">محاكاة تحديث السجل فقط</button>

        </div>

      </div>`;
  } else {
    fields.forEach(f => f.disabled = false);
    if (btn) { btn.disabled = false; btn.innerText = 'حفظ طلب تعديل في السجل'; btn.classList.remove('opacity-50','cursor-not-allowed'); }
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
    تنزيل: 0
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

          <p class="text-muted text-xs truncate">${last ? (last.from==='me'?'You: ':'') + last.text : 'لا توجد رسائل مسجلة'}</p>

        </div>

      </div>

    </button>`;
  }).join('');
}
function renderChatThread() {
  const name = db.activeChat;
  if (!name) {
    document.getElementById('chat-header-info').textContent = 'اختار عميلًا';
    document.getElementById('chat-thread').textContent = 'أضف عميلًا للبدء.';
    return;
  }
  const thread = db.chats[name] || [];
  document.getElementById('chat-header-info').innerHTML = `<div class="w-9 h-9 rounded-full bg-accent/20 flex items-center justify-center text-accent text-xs font-bold mr-3">${name[0]}</div><div><p class="text-white text-sm font-semibold">${name}</p><p class="text-muted text-xs">عميل</p></div>`;
  document.getElementById('chat-thread').innerHTML = thread.length ? thread.map(m => `
    <div class="flex ${m.from==='me'?'justify-end':'justify-start'}">

      <div class="max-w-[70%] px-4 py-2 text-sm ${m.from==='me'?'chat-bubble-me':'chat-bubble-them'}">
        ${m.text}
        <div class="text-[10px] opacity-60 mt-1">${m.time}</div>

      </div>

    </div>`).join('') : '<p class="text-muted text-sm text-center py-10">ابدأ سجل المحادثة.</p>';
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
  db.chats[name].push({ from:'me', text, time: 'الآن' });
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
  db.chats[name].push({ from:'me', text: input.value.trim(), time: 'الآن' });
  input.value = '';
  saveData(); renderChatList(); renderChatThread();
};

// ==========================================
// ملخصات السجل
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
  unsoldPaid.forEach(r => items.push({ color:'bg-accent', text: `"${r.title}" hasn't مباع yet — try promoting it in Community or Messages.` }));

  const overdue = db.invoices.filter(i => i.status === 'Overdue');
  if (overdue.length) items.push({ color:'bg-red-400', text: `${overdue.length} invoice${overdue.length>1?'s are':' is'} overdue, totalling $${overdue.reduce((a,b)=>a+b.amount,0).toFixed(2)}.` });

  const topClient = [...db.pointsClients].sort((a,b)=>b.streak-a.streak)[0];
  if (topClient) items.push({ color:'bg-green-400', text: `${topClient.name} is on a ${topClient.streak}-day streak — a shoutout in Community would land well.` });

  const openLeads = db.leads.filter(l => l.stage !== 'Won' && l.stage !== 'Lost');
  if (openLeads.length) items.push({ color:'bg-accent', text: `${openLeads.length} lead${openLeads.length>1?'s':''} still open in your pipeline worth $${openLeads.reduce((a,b)=>a+(b.value||0),0).toLocaleString()}.` });

  el.innerHTML = `
    <div class="flex items-center justify-between mb-3">

      <h3 class="display text-sm font-semibold text-white tracking-wide uppercase">ملخصات السجل</h3>

      <span class="text-[10px] text-muted">من بيانات مساحة العمل</span>

    </div>

    <div class="space-y-2.5">
      ${items.length ? items.slice(0,5).map(i => `<div class="flex items-start gap-3"><span class="insight-dot ${i.color} mt-1.5"></span><p class="text-sm text-text">${i.text}</p></div>`).join('') : '<p class="text-muted text-sm">لا توجد ملاحظات مسجلة حاليًا.</p>'}
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
  const filtered = db.clients.filter(c => [c.name,c.email,c.tag].join(' ').toLowerCase().includes(q) && (statusFilter==='All' || c.status===statusFilter));
  el.innerHTML = filtered.length ? filtered.map(c => {
    const pts = db.pointsClients.find(p => p.name === c.name);
    const rdy = db.readiness.find(r => r.name === c.name);
    const rdyColor = rdy ? ({green:'bg-green-400',yellow:'bg-gold',red:'bg-red-400'})[rdy.status] : 'bg-muted';
    return `<button onclick="openClientModal('${c.name.replace(/'/g,"\\'")}')" class="text-left card rounded-2xl p-5 hover:border-accent/50 transition">

      <div class="flex items-center justify-between mb-3">

        <div class="w-11 h-11 rounded-full bg-accent/20 flex items-center justify-center text-accent font-bold">${c.name[0]}</div>

        <span class="w-2.5 h-2.5 rounded-full ${rdyColor}" title="الجاهزية"></span>

      </div>

      <h4 class="text-white font-semibold">${c.name}</h4>

      <p class="text-muted text-xs mt-0.5">${c.tag} · ${uiLabel(c.status)}</p>

      <div class="flex justify-between text-xs text-muted mt-3 pt-3 border-t border-border">

        <span>${pts ? pts.streak+'d streak' : '—'}</span>

        <span>منذ ${c.joined}</span>

      </div>

    </button>`;
  }).join('') : '<p class="text-muted text-sm text-center py-10 sm:col-span-2 lg:col-span-3">لا توجد سجلات عملاء مطابقة.</p>';
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

          <p class="text-muted text-sm">${c.tag} · ${uiLabel(c.status)} · عميل منذ ${c.joined}</p>

          <button onclick="toggleNutritionSub('${c.name.replace(/'/g,"\\'")}')" class="mt-1.5 inline-block text-[10px] px-2 py-0.5 rounded-full font-medium transition ${c.nutrition ? 'bg-accent/15 text-accent' : 'bg-border text-muted hover:text-accent'}">${c.nutrition ? '◍ Nutrition Enabled' : '◍ Enable Nutrition'}</button>

        </div>

      </div>

      <button onclick="closeClientModal()" class="text-muted hover:text-white text-xl leading-none">×</button>

    </div>

    <div class="grid grid-cols-3 gap-3 mb-5">

      <div class="bg-black border border-border rounded-xl p-3 text-center"><p class="text-muted text-[11px]">النقاط</p><p class="text-white font-bold text-lg">${pts ? pts.points : '—'}</p></div>

      <div class="bg-black border border-border rounded-xl p-3 text-center"><p class="text-muted text-[11px]">الاستمرارية</p><p class="text-white font-bold text-lg">${pts ? pts.streak+'d' : '—'}</p></div>

      <div class="bg-black border border-border rounded-xl p-3 text-center"><p class="text-muted text-[11px]">الجاهزية</p><p class="text-white font-bold text-sm mt-1">${rdyLabel}</p></div>

    </div>
    ${c.email ? `<p class="text-muted text-xs mb-4">✉ ${c.email}</p>` : ''}
    ${c.notes ? `<div class="mb-5"><p class="text-[11px] text-muted uppercase tracking-wide mb-1">ملاحظات الكوتش</p><p class="text-sm text-text">${c.notes}</p></div>` : ''}

    <div class="mb-5">

      <div class="flex items-center justify-between mb-2">

        <p class="text-[11px] text-muted uppercase tracking-wide">الإنجازات (${sortedPRs.length})</p>

        <button onclick="openClientPRModal()" class="text-accent text-[11px] hover:underline">+ إضافة إنجاز</button>

      </div>
      ${sortedPRs.length ? sortedPRs.slice(0,4).map(p=>`
        <div class="bg-black border border-border rounded-lg p-3 mb-2 flex items-center justify-between">

          <div><p class="text-sm text-white font-medium">${p.exercise} 🏆</p><p class="text-muted text-[11px] mt-0.5">${new Date(p.date).toLocaleDateString('ar-EG',{month:'short',day:'numeric'})}</p></div>

          <div class="flex items-center gap-2"><span class="text-accent font-bold text-sm">${p.weight}kg × ${p.reps}</span><button onclick="delClientPR(${p.id})" class="text-red-400/50 hover:text-red-400 text-xs">✕</button></div>

        </div>`).join('') : '<p class="text-muted text-sm">لا توجد إنجازات شخصية مسجلة.</p>'}
    </div>

    <div class="mb-5">

      <div class="flex items-center justify-between mb-2">

        <p class="text-[11px] text-muted uppercase tracking-wide">الأهداف (${goals.length})</p>

        <button onclick="openClientGoalModal()" class="text-accent text-[11px] hover:underline">+ إضافة هدف</button>

      </div>
      ${goals.length ? goals.map(g => {
        const start = g.startValue !== undefined ? g.startValue : g.current;
        const isIncreasing = g.target >= start;
        let pct = isIncreasing ? ((g.current - start) / ((g.target - start) || 1)) * 100 : ((start - g.current) / ((start - g.target) || 1)) * 100;
        pct = Math.max(0, Math.min(100, pct));
        return `<div class="bg-black border border-border rounded-lg p-3 mb-2">

          <div class="flex justify-between items-center mb-1"><p class="text-sm text-white font-medium">${g.title}${pct>=100?' 🏆':''}</p><button onclick="delClientGoal(${g.id})" class="text-red-400/50 hover:text-red-400 text-xs">✕</button></div>

          <div class="flex justify-between text-[11px] text-muted mb-1"><span>${g.current} ${g.unit}</span><span>الهدف: ${g.target} ${g.unit}</span></div>

          <div class="w-full h-1.5 bg-border rounded-full overflow-hidden"><div class="h-full bg-accent rounded-full" style="width:${pct}%"></div></div>

        </div>`;
      }).join('') : '<p class="text-muted text-sm">لا توجد أهداف مسجلة.</p>'}
    </div>

    <div class="mb-5">

      <p class="text-[11px] text-muted uppercase tracking-wide mb-2">الخطط الأخيرة (${plans.length})</p>
      ${plans.length ? plans.slice(0,3).map(p=>`<div class="bg-black border border-border rounded-lg p-3 mb-2"><p class="text-sm text-white font-medium">${p.title}</p><p class="text-muted text-xs">${uiLabel(p.type)} · ${p.sent}</p></div>`).join('') : '<p class="text-muted text-sm">لا توجد خطط في السجل بعد.</p>'}
    </div>

    <div class="mb-5">

      <p class="text-[11px] text-muted uppercase tracking-wide mb-2">التقييمات (${fb.length})</p>
      ${fb.length ? fb.map(f=>`<div class="bg-black border border-border rounded-lg p-3 mb-2"><p class="text-sm text-text">"${f.msg}"</p><p class="text-gold text-xs mt-1">${'★'.repeat(f.rating)}${'☆'.repeat(5-f.rating)}</p></div>`).join('') : '<p class="text-muted text-sm">لا توجد تقييمات مسجلة بعد.</p>'}
    </div>

    <div class="mb-5">

      <p class="text-[11px] text-muted uppercase tracking-wide mb-2">ملاحظات الجلسة الخاصة <span class="normal-case text-muted">(تظهر لك فقط)</span></p>

      <div id="session-notes-list" class="space-y-2 mb-2 max-h-40 overflow-y-auto thin-scroll"></div>

      <form onsubmit="addSessionNote(event,'${c.name.replace(/'/g,"\\'")}')" class="flex gap-2">

        <input type="text" id="session-note-input" placeholder="Add a private note…" class="flex-1 input-dark rounded-lg px-3 py-1.5 text-xs">

        <button type="submit" class="px-3 py-1.5 bg-accent/15 text-accent text-xs font-semibold rounded-lg hover:bg-accent/25 transition">إضافة</button>

      </form>

    </div>

    <div class="flex gap-2">

      <button onclick="closeClientModal();openChat('${c.name.replace(/'/g,"\\'")}');navigateTo('messages')" class="flex-1 py-2.5 bg-accent text-white text-sm font-semibold rounded-lg hover:bg-indigo-500 transition">سجل محادثة ${c.name.split(' ')[0]}</button>

      <button onclick="closeClientModal()" class="px-4 py-2.5 bg-black border border-border text-muted text-sm font-medium rounded-lg hover:text-white transition">إغلاق</button>

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

    </div>`).join('') : '<p class="text-muted text-xs">لا توجد ملاحظات خاصة بعد.</p>';
}
window.addSessionNote = (e, name) => {
  e.preventDefault();
  const input = document.getElementById('session-note-input');
  if (!input.value.trim()) return;
  if (!db.sessionNotes[name]) db.sessionNotes[name] = [];
  db.sessionNotes[name].push({ date: 'الآن', text: input.value.trim() });
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
    return `<div class="kanban-col p-3" data-stage="${uiLabel(stage)}" ondragover="onDragOver(event)" ondragleave="onDragLeave(event)" ondrop="onDrop(event)">

      <div class="flex items-center justify-between mb-3 px-1">

        <h4 class="text-white text-xs font-semibold uppercase tracking-wide">${uiLabel(stage)}</h4>

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
  db.leads.unshift({ id: Date.now(), name: f.get('name'), email: f.get('email'), stage: 'New', value: parseFloat(f.get('value'))||0, notes: f.get('notes'), date: 'الآن' });
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

        <p class="text-muted text-xs mt-0.5">${i.client} · الاستحقاق ${i.due}</p>

      </div>

      <div class="flex items-center gap-3 shrink-0">

        <span class="text-white font-semibold text-sm">$${i.amount.toFixed(2)}</span>

        <span class="text-[10px] px-2 py-0.5 rounded-full font-medium ${statusColor[i.status]}">${uiLabel(i.status)}</span>
        ${i.status!=='Paid' ? `<button onclick="markPaid(${i.id})" class="text-accent text-xs hover:underline">تسجيل السداد</button>` : ''}
        <button onclick="del('invoices',${i.id})" class="text-red-400/50 hover:text-red-400 text-xs">✕</button>

      </div>

    </div>`).join('') : '<p class="text-muted text-sm text-center py-10">لا توجد فواتير مسجلة.</p>';
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
const EXERCISE_LIBRARY = PenguinsExerciseStudio.catalog.map(ex=>({name:ex.name,muscle:ex.category,default:'3 × 10'}));
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

    </div>`).join('') || '<p class="text-muted text-xs text-center py-4">لا توجد تمارين مطابقة.</p>';
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
    el.innerHTML = '<p class="text-muted text-xs" id="built-workout-empty">اختار تمرينًا من المكتبة لإضافته هنا.</p>';
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

      <div class="flex items-center gap-2 mb-1"><h4 class="text-white font-semibold text-sm">${t.title}</h4><span class="text-[10px] px-2 py-0.5 rounded-full bg-accent/15 text-accent">${uiLabel(t.type)}</span></div>

      <p class="text-muted text-xs whitespace-pre-line line-clamp-3">${window.PenguinsExerciseStudio?.summary(t.details)||t.details}</p>

      <div class="flex gap-2 mt-3">

        <button onclick="useTemplate(${t.id})" class="flex-1 py-1.5 bg-accent/15 text-accent text-xs font-semibold rounded-lg hover:bg-accent/25 transition">استخدام القالب</button>

        <button onclick="del('planTemplates',${t.id})" class="px-3 py-1.5 text-red-400/60 hover:text-red-400 text-xs">حذف</button>

      </div>

    </div>`).join('') : '<p class="text-muted text-sm text-center py-10">لا توجد قوالب بعد. جهّز خطة واضغط حفظ كقالب.</p>';
}
window.saveAsTemplate = () => {
  const form = document.getElementById('form-plan');
  const f = new FormData(form);
  const title = f.get('title');
  if (!title) { toast('Add a plan title first.'); return; }
  const workoutText = builtWorkout.length ? builtWorkout.map(ex => `${ex.name}: ${ex.scheme}`).join('\n') : '';
  const notes = f.get('details') || '';
  let details;try{const combined=[workoutText,notes].filter(Boolean).join('\n\n');details=window.PenguinsCoachPlan?window.PenguinsCoachPlan.serialize(combined):(combined||'No details added.');}catch(error){toast(error.message);return;}
  db.planTemplates.unshift({ id: Date.now(), title, type: f.get('type'), details });
  saveData(); renderTemplates();
  toast('✅ Saved as template.');
};
window.useTemplate = (id) => {
  const tpl = db.planTemplates.find(t => t.id === id);
  if (!tpl) return;
  document.querySelector('#form-plan [name="title"]').value = tpl.title;
  document.querySelector('#form-plan [name="type"]').value = tpl.type;
  const parsed=window.PenguinsCoachPlan?.load(tpl.details);if(!parsed)window.PenguinsCoachPlan?.clear();document.querySelector('#form-plan [name="details"]').value = parsed?parsed.notes:tpl.details;
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
  if (btn) btn.innerText = 'ابدأ';
};
window.toggleTimer = () => {
  const btn = document.getElementById('timer-toggle-btn');
  if (timerRunning) {
    clearInterval(timerInterval); timerRunning = false;
    if (btn) btn.innerText = 'ابدأ';
    return;
  }
  timerRunning = true;
  if (btn) btn.innerText = 'Pause';
  timerInterval = setInterval(() => {
    timerSeconds--;
    renderTimerDisplay();
    if (timerSeconds <= 0) {
      clearInterval(timerInterval); timerRunning = false;
      if (btn) btn.innerText = 'ابدأ';
      toast('⏱️ Rest مكتمل.');
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
  if (btn) btn.innerText = 'ابدأ';
};

// ==========================================
// أهداف العمل
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

      <h3 class="display text-sm font-semibold text-white tracking-wide uppercase">أهداف العمل</h3>

      <button onclick="openGoalsModal()" class="text-[11px] text-accent hover:underline">تعديل الأهداف</button>

    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

      <div>

        <div class="flex justify-between text-xs text-muted mb-1"><span>الإيراد الشهري</span><span>$${Math.round(earnings).toLocaleString()} / $${g.revenueTarget.toLocaleString()}</span></div>

        <div class="w-full h-2 bg-border rounded-full overflow-hidden"><div class="h-full bg-accent rounded-full transition-all duration-500" style="width:${revenuePct}%"></div></div>

      </div>

      <div>

        <div class="flex justify-between text-xs text-muted mb-1"><span>العملاء النشطون</span><span>${db.clients.length} / ${g.clientsTarget}</span></div>

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

      <p class="text-[10px] text-muted uppercase">${d.toLocaleDateString('ar-EG',{weekday:'short'})}</p>

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
        ${!s.booked ? `<span class="text-[10px] px-2 py-0.5 rounded-full bg-green-500/15 text-green-400 font-medium">متاح</span>` : `<span class="text-[10px] px-2 py-0.5 rounded-full bg-gold/15 text-gold font-medium">محجوز</span>`}
        <button onclick="del('slots',${s.id})" class="text-red-400/60 hover:text-red-400 text-xs">إزالة</button>

      </div>

    </div>`).join('') : '<p class="text-muted text-sm text-center py-6">لا توجد مواعيد متاحة.</p>';
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

        <button onclick="del('cohorts',${c.id})" class="text-red-400/50 hover:text-red-400 text-xs">إزالة</button>

      </div>

      <div class="flex flex-wrap gap-2">
        ${c.members.map(m=>`<span class="text-xs px-2.5 py-1 rounded-full bg-accent/15 text-accent">${m}</span>`).join('') || '<span class="text-muted text-xs">لا يوجد أعضاء بعد</span>'}
      </div>

    </div>`).join('') : '<p class="text-muted text-sm text-center py-10 lg:col-span-2">لا توجد مجموعات بعد. أضف أول مجموعة.</p>';
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

        <p class="text-white text-sm font-medium">عندما <span class="text-accent">${a.trigger}</span></p>

        <p class="text-muted text-xs mt-0.5">→ ${a.action}</p>

      </div>

      <div class="flex items-center gap-3 shrink-0">

        <div class="toggle-switch ${a.enabled?'on':''}" onclick="toggleAutomation(${a.id})"></div>

        <button onclick="del('automations',${a.id})" class="text-red-400/50 hover:text-red-400 text-xs">✕</button>

      </div>

    </div>`).join('') : '<p class="text-muted text-sm text-center py-10">لا توجد قواعد مسجلة.</p>';
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

        <span class="text-[10px] px-2 py-0.5 rounded-full font-medium ${c.status==='Signed'?'bg-green-500/15 text-green-400':'bg-gold/15 text-gold'}">${uiLabel(c.status)}</span>
        ${c.status!=='Signed' ? `<button onclick="markSigned(${c.id})" class="text-accent text-xs hover:underline">محاكاة التوقيع فقط</button>` : ''}
      </div>

    </div>`).join('') : '<p class="text-muted text-sm text-center py-10">لا توجد اتفاقيات مسجلة.</p>';
}
window.markSigned = (id) => { const c = db.contracts.find(x=>x.id===id); if(c){ c.status='Signed'; saveData(); renderAll(); } };
document.getElementById('form-contract').onsubmit = e => {
  e.preventDefault();
  const f = new FormData(e.target);
  db.contracts.unshift({ id: Date.now(), client: f.get('client'), title: f.get('title'), status: 'Sent', date: 'الآن' });
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
  db.metrics[client].push({ date: new Date().toLocaleDateString('ar-EG',{month:'short',day:'numeric'}), weight: parseFloat(f.get('weight')), waist: parseFloat(f.get('waist'))||null });
  saveData(); e.target.reset();
  document.getElementById('metrics-client-select').value = client;
  renderMetricsChart();
  toast('✅ Metrics logged.');
};
function renderMetricsChart() {
  const canvas = document.getElementById('metricsChart');
  const select = document.getElementById('metrics-client-select');
  if (!canvas || !select) return;
  const client = select.value || '';
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
    { l:'العملاء النشطون', v: active, i:'●' },
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
  const name = db.clients[0] ? db.clients[0].name : 'Client';
  const { pts, rdy, plans, fb } = clientSummary(name);
  const freeResources = db.library.filter(r=>r.tier==='Free');
  document.getElementById('clientPreviewBody').innerHTML = `
    <div class="flex items-center justify-between mb-5">

      <div class="flex items-center gap-3">

        <div class="w-11 h-11 rounded-full bg-accent/20 flex items-center justify-center text-accent font-bold">${name[0]}</div>

        <div><p class="text-white font-semibold">مساحة ${name}</p><p class="text-muted text-xs">معاينة محلية لواجهة العميل</p></div>

      </div>

      <button onclick="closeClientPreview()" class="text-muted hover:text-white text-xl leading-none">×</button>

    </div>

    <div class="grid grid-cols-2 gap-3 mb-5">

      <div class="bg-black border border-border rounded-xl p-3 text-center"><p class="text-muted text-[11px]">النقاط</p><p class="text-white font-bold text-lg">${pts?pts.points:0}</p></div>

      <div class="bg-black border border-border rounded-xl p-3 text-center"><p class="text-muted text-[11px]">الاستمرارية</p><p class="text-white font-bold text-lg">${pts?pts.streak+'d':'0d'}</p></div>

    </div>

    <p class="text-[11px] text-muted uppercase tracking-wide mb-2">خطتي الحالية</p>
    ${plans[0] ? `<div class="bg-black border border-border rounded-lg p-3 mb-5"><p class="text-sm text-white font-medium">${plans[0].title}</p><p class="text-muted text-xs whitespace-pre-line mt-1">${plans[0].details}</p></div>` : '<p class="text-muted text-sm mb-5">لم تُضف خطة في هذا السجل بعد.</p>'}
    <p class="text-[11px] text-muted uppercase tracking-wide mb-2">الموارد المجانية</p>

    <div class="space-y-2 mb-5">${freeResources.map(r=>`<div class="bg-black border border-border rounded-lg p-3 text-sm text-white">${r.title}</div>`).join('') || '<p class="text-muted text-sm">غير متاح حاليًا.</p>'}</div>

    <p class="text-[11px] text-muted uppercase tracking-wide mb-2">Readiness اليوم</p>

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
  { label:'الرئيسية', icon:'◆', text:'Your home base — earnings chart, Smart Insights (auto-flagged things needing attention), a getting-started checklist, Business Goals (set your own revenue/client targets and track progress), and your task list.' },
  { label:'الرسائل', icon:'✉', text:'Direct chat with each client. Pick a client on the left, type in the box, hit send.' },
  { label:'العملاء', icon:'◇', text:'Every client in one place — the full dossier. Click a card to see their plans, feedback, points, readiness, personal records, goals with progress bars, and private session notes only you can see.' },
  { label:'باني الخطط', icon:'▤', text:'Build a structured program using the searchable exercise library on the left (click an exercise to add it), or just add freeform notes. Save any plan as a reusable template so you don\'t have to rebuild it next time — switch to the Templates tab to reuse or delete one.' },
  { label:'Nutrition', icon:'◍', text:'Set macro targets (calories, protein, carbs, fats) for clients on the Nutrition add-on. Clients without the add-on won\'t appear here until you enable it for them.' },
  { label:'الجلسات المباشرة', icon:'●', text:'Schedule 1:1 or group calls, or open bookable time slots your clients can claim themselves. Switch to Calendar view to see the week at a glance.' },
  { label:'مكتبة الموارد', icon:'▦', text:'Upload PDF guides and programs. Mark each Free or Paid with a price. Note: this is a demo uploader — actual file hosting needs a backend.' },
  { label:'تقييمات العملاء', icon:'★', text:'Reviews and messages clients leave you. Reply inline to keep the conversation going.' },
  { label:'مراجعة الأداء', icon:'▶', text:'Review a client\'s submitted form-check video, draw annotations directly on the frame, then send your notes back.' },
  { label:'النقاط والاستمرارية', icon:'◈', text:'A leaderboard of client habit completion — total points, day streaks, and completion percentage.' },
  { label:'الجاهزية', icon:'◎', text:'See each client\'s daily readiness (sleep, soreness, stress) and the auto-suggested adjustment to today\'s plan.' },
  { label:'سجل التقدّم', icon:'▣', text:'Before/after photo comparisons, plus a body-metrics log (weight, waist) with a trend chart per client.' },
  { label:'المجتمع', icon:'◐', text:'Your private client community feed. Post announcements or challenges, moderate what clients post.' },
  { label:'المجموعات', icon:'⬢', text:'Group coaching — bundle multiple clients into a shared program with its own schedule.' },
  { label:'التنظيم التلقائي', icon:'⚡', text:'Set "when X happens, do Y" rules — e.g. auto-remind a client who misses a check-in. Toggle rules on/off anytime.' },
  { label:'التقارير', icon:'▩', text:'Business-level view: active vs paused clients, average engagement, retention trend, and per-client completion rates.' },
  { label:'العقود', icon:'✎', text:'Send agreements or waivers to a client and track whether they\'ve signed.' },
  { label:'العملاء المحتملون', icon:'⬡', text:'Your sales funnel for prospective clients — drag cards between New → Contacted → Proposal Sent → Won/Lost.' },
  { label:'الفواتير', icon:'▥', text:'Create invoices, track what\'s outstanding, paid, or overdue, and mark them paid.' },
  { label:'المتجر', icon:'▨', text:'List merch, digital products, or brand collabs for sale.' },
  { label:'الإحالات', icon:'↗', text:'Your referral link — track clicks, signups, and the revenue they generated.' },
  { label:'التحقق', icon:'✓', text:'Your identity/certification verification status. Read-only — managed by the Penguins team.' },
  { label:'الحساب', icon:'◉', text:'Your coach profile: name, niche, bio, hourly rate — plus an Appearance section to pick your own accent color for the whole dashboard (purple by default).' },
  { label:'معاينة واجهة العميل', icon:'◑', text:'Preview what a client sees in their portal — their plan, points, free resources, and today\'s readiness note.' },
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
  db.library.filter(r=>r.tier==='Paid' && r.downloads===0).forEach(r => list.push(`"${r.title}" hasn't مباع yet.`));

  const dot = document.getElementById('notif-dot');
  if (dot) dot.classList.toggle('hidden', list.length === 0);
  const panelList = document.getElementById('notif-list');
  if (panelList) {
    panelList.innerHTML = list.length ? list.slice(0,8).map(t => `<div class="px-2 py-2 text-xs text-text border-b border-border last:border-0">${t}</div>`).join('') : '<div class="px-2 py-4 text-xs text-muted text-center">You\'re all caught up.</div>';
  }
}

// ==========================================
// خطوات البداية CHECKLIST
// ==========================================
function renderChecklist() {
  const el = document.getElementById('dash-checklist');
  if (!el) return;
  const items = [
    { done: db.clients.length > 0, label: 'أضف أول سجل عميل', page:'clients' },
    { done: db.plans.length > 0, label: 'حفظ خطة في السجل', page:'planner' },
    { done: db.library.length > 0, label: 'إضافة مورد', page:'library' },
    { done: db.invoices.length > 0, label: 'إنشاء فاتورة', page:'invoices' },
    { done: db.cohorts.length > 0, label: 'جرّب المجموعات والتنظيم', page:'cohorts' },
  ];
  const doneCount = items.filter(i=>i.done).length;
  if (doneCount === items.length) { el.innerHTML = ''; el.classList.add('hidden'); return; }
  el.classList.remove('hidden');
  el.innerHTML = `
    <div class="flex items-center justify-between mb-3">

      <h3 class="display text-sm font-semibold text-white tracking-wide uppercase">خطوات البداية</h3>

      <span class="text-[10px] text-muted">${doneCount}/${items.length} مكتمل</span>

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

    </div>`).join('') : '<p class="text-muted text-sm text-center py-6">لا توجد مهام بعد. أضف مهمة.</p>';
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

        <button onclick="toggleNutritionSub('${c.name.replace(/'/g,"\\'")}')" class="text-red-400/60 hover:text-red-400 text-[11px]">إيقاف</button>

      </div>
      ${tgt ? `
        <div class="grid grid-cols-4 gap-2 text-center mb-3">

          <div class="bg-black border border-border rounded-lg py-2"><p class="text-white text-sm font-bold">${tgt.calories}</p><p class="text-muted text-[10px]">kcal</p></div>

          <div class="bg-black border border-border rounded-lg py-2"><p class="text-white text-sm font-bold">${tgt.protein}g</p><p class="text-muted text-[10px]">protein</p></div>

          <div class="bg-black border border-border rounded-lg py-2"><p class="text-white text-sm font-bold">${tgt.carbs}g</p><p class="text-muted text-[10px]">carbs</p></div>

          <div class="bg-black border border-border rounded-lg py-2"><p class="text-white text-sm font-bold">${tgt.fats}g</p><p class="text-muted text-[10px]">fats</p></div>

        </div>
        ${last ? `<p class="text-muted text-[11px] mb-3">Last logged (${last.date}): ${last.calories} kcal, P${last.protein} C${last.carbs} F${last.fats}</p>` : '<p class="text-muted text-[11px] mb-3">لا توجد سجلات تغذية بعد.</p>'}
      ` : '<p class="text-muted text-xs mb-3">لا توجد أهداف تغذية مسجلة.</p>'}
      <button onclick="openNutritionModal('${c.name.replace(/'/g,"\\'")}')" class="w-full py-2 bg-accent/15 text-accent text-xs font-semibold rounded-lg hover:bg-accent/25 transition">${tgt ? 'تعديل الأهداف' : 'Set Targets'}</button>

    </div>`;
  }).join('') : '<p class="text-muted text-sm text-center py-8 sm:col-span-2 lg:col-span-3">لا توجد سجلات تغذية مفعّلة.</p>';

  unsubEl.innerHTML = unsubscribed.length ? unsubscribed.map(c => `
    <div class="card rounded-2xl p-5 opacity-70">

      <div class="flex items-center gap-3 mb-3">

        <div class="w-10 h-10 rounded-full bg-black border border-border flex items-center justify-center text-muted font-bold">${c.name[0]}</div>

        <div><p class="text-white font-semibold text-sm">${c.name}</p><p class="text-muted text-xs">${c.tag}</p></div>

      </div>

      <button onclick="toggleNutritionSub('${c.name.replace(/'/g,"\\'")}')" class="w-full py-2 bg-black border border-dashed border-border text-muted text-xs font-medium rounded-lg hover:border-accent/50 hover:text-accent transition">+ تفعيل سجل التغذية</button>

    </div>`).join('') : '<p class="text-muted text-sm text-center py-8 sm:col-span-2 lg:col-span-3">سجل التغذية مفعّل لكل العملاء.</p>';
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
    "sidebar.search":"بحث سريع","sidebar.help":"المساعدة والدليل","sidebar.overview":"نظرة عامة",
    "nav.dashboard":"الرئيسية","nav.messages":"الرسائل","nav.clients":"العملاء",
    "sidebar.coachingTools":"أدوات التدريب","nav.planner":"باني الخطط","nav.nutrition":"Nutrition",
    "nav.live":"الجلسات المباشرة","nav.library":"مكتبة الموارد","nav.feedback":"تقييمات العملاء","nav.review":"مراجعة الأداء",
    "sidebar.clientMonitoring":"متابعة العملاء","nav.points":"النقاط والاستمرارية","nav.readiness":"الجاهزية",
    "nav.vault":"سجل التقدّم","nav.community":"المجتمع","nav.cohorts":"المجموعات",
    "sidebar.automation":"التنظيم والتقارير","nav.automations":"التنظيم التلقائي","nav.reports":"التقارير","nav.contracts":"العقود",
    "sidebar.business":"إدارة الأعمال","nav.pipeline":"العملاء المحتملون","nav.invoices":"الفواتير","nav.shop":"المتجر",
    "nav.referrals":"الإحالات","nav.verification":"التحقق","nav.account":"الحساب",
    "sidebar.viewAsClient":"معاينة واجهة العميل"
  },
  de: {
    "sidebar.search":"Schnellsuche","sidebar.help":"Hilfe & Anleitung","sidebar.overview":"Übersicht",
    "nav.dashboard":"الرئيسية","nav.messages":"Nachrichten","nav.clients":"Klienten",
    "sidebar.coachingTools":"Coaching-Tools","nav.planner":"Plan-Ersteller","nav.nutrition":"Ernährung",
    "nav.live":"Live-Sitzungen","nav.library":"Ressourcen","nav.feedback":"Kundenfeedback","nav.review":"Video-Analyse",
    "sidebar.clientMonitoring":"Klienten-Überwachung","nav.points":"Punkte & Serien","nav.readiness":"Bereitschaft",
    "nav.vault":"Fortschritt","nav.community":"المجتمع","nav.cohorts":"Gruppen",
    "sidebar.automation":"Automatisierung","nav.automations":"Automatisierungen","nav.reports":"Berichte","nav.contracts":"Verträge",
    "sidebar.business":"Geschäftliches","nav.pipeline":"العملاء المحتملون","nav.invoices":"Rechnungen","nav.shop":"المتجر",
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
    "nav.dashboard":"Tableau de bord","nav.messages":"الرسائل","nav.clients":"العملاء",
    "sidebar.coachingTools":"Outils de coaching","nav.planner":"Créateur de programme","nav.nutrition":"Nutrition",
    "nav.live":"Séances en direct","nav.library":"Bibliothèque","nav.feedback":"Avis clients","nav.review":"Analyse vidéo",
    "sidebar.clientMonitoring":"Suivi des clients","nav.points":"Points et séries","nav.readiness":"Forme",
    "nav.vault":"Suivi des progrès","nav.community":"Communauté","nav.cohorts":"Groupes",
    "sidebar.automation":"Automatisation","nav.automations":"Automatisations","nav.reports":"Rapports","nav.contracts":"Contrats",
    "sidebar.business":"Affaires","nav.pipeline":"العملاء المحتملون","nav.invoices":"Factures","nav.shop":"Boutique",
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
    "sidebar.business":"Negocio","nav.pipeline":"العملاء المحتملون","nav.invoices":"Facturas","nav.shop":"Tienda",
    "nav.referrals":"Referidos","nav.verification":"Verificación","nav.account":"Cuenta",
    "sidebar.viewAsClient":"Ver como cliente"
  }
};

function t(key) {
  const lang = 'ar';
  return (I18N[lang] && I18N[lang][key]) || I18N.en[key] || key;
}
function applyTranslations() {
  if(window.PenguinsLanguage){window.PenguinsLanguage.apply();return;}
  const lang = 'ar';
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
  if(window.PenguinsLanguage){window.PenguinsLanguage.set(lang);return;}
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
    ['dashboard','الرئيسية','◆'],['messages','الرسائل','✉'],['clients','العملاء','◇'],
    ['planner','باني الخطط','▤'],['nutrition','Nutrition','◍'],['live','الجلسات المباشرة','●'],['library','مكتبة الموارد','▦'],
    ['feedback','تقييمات العملاء','★'],['review','مراجعة الأداء','▶'],['points','النقاط والاستمرارية','◈'],
    ['readiness','الجاهزية','◎'],['vault','سجل التقدّم','▣'],['community','المجتمع','◐'],
    ['cohorts','المجموعات','⬢'],['automations','التنظيم التلقائي','⚡'],['reports','التقارير','▩'],['contracts','العقود','✎'],
    ['pipeline','العملاء المحتملون','⬡'],['invoices','الفواتير','▥'],['shop','المتجر','▨'],
    ['referrals','الإحالات','↗'],['verification','التحقق','✓'],['account','الحساب','◉']
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

    </button>`).join('') : '<p class="text-muted text-sm px-5 py-6 text-center">لا توجد نتائج مطابقة.</p>';
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
window.sendAnnotation=()=>{ toast('Annotation prepared. Client delivery is not connected.'); };

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
    months.push({ label: d.toLocaleDateString('ar-EG', { month: 'short' }), year: d.getFullYear(), month: d.getMonth() });
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
    const initialPage=location.hash.slice(1);
    if(document.getElementById('page-'+initialPage))navigateTo(initialPage);
  } catch (err) {
    console.error('Startup error:', err);
    if (!db) return;
    try { renderAll(); } catch (e2) { console.error('Fallback render also failed:', e2); }
  } finally {
    const veil = document.getElementById('loadingVeil');
    if (veil) { veil.style.opacity = '0'; setTimeout(()=>veil.remove(), 300); }
  }
})();

// Appended to the dashboard script so the existing views share one workspace.
function downloadFile(name,contents,type='application/json'){
 const url=URL.createObjectURL(new Blob([contents],{type})),link=document.createElement('a');link.href=url;link.download=name;link.click();setTimeout(()=>URL.revokeObjectURL(url),1000);
}
function exportWorkspace(){if(!db)return;downloadFile('penguins-backup-'+new Date().toISOString().slice(0,10)+'.json',JSON.stringify({format:'penguins-workspace',schemaVersion:1,exportedAt:new Date().toISOString(),data:db},null,2));}
function logActivity(message){if(!db)return;db.activity??=[];db.activity.unshift({id:Date.now(),message,at:new Date().toISOString()});db.activity=db.activity.slice(0,100);}
const dialog=document.getElementById('pg-dialog'),dialogBody=document.getElementById('pg-dialog-body');
function showDialog(title){document.getElementById('pg-dialog-title').textContent=title;dialogBody.replaceChildren();dialog.showModal();return dialogBody;}
function addText(parent,tag,text){const element=document.createElement(tag);element.textContent=text;parent.append(element);return element;}
function addButton(parent,label,action){const button=addText(parent,'button',label);button.type='button';button.onclick=action;return button;}
document.getElementById('pg-dialog-close').onclick=()=>dialog.close();
document.getElementById('pg-export').onclick=exportWorkspace;
document.getElementById('pg-export-alert').onclick=exportWorkspace;
document.getElementById('pg-retry').onclick=()=>{if(sync.conflict){toast('Export your changes, then reload to resolve the conflict.');return;}sync.flush();};
document.getElementById('pg-print').onclick=()=>window.print();
document.getElementById('pg-import').onclick=()=>document.getElementById('pg-import-file').click();
document.getElementById('pg-import-file').onchange=async event=>{
 const file=event.target.files[0];event.target.value='';if(!file)return;
 try{
  if(file.size>2000000)throw new Error('Choose a JSON backup smaller than 2 MB.');
  const parsed=JSON.parse(await file.text());const data=PenguinsCore.normalize(parsed.format==='penguins-workspace'?parsed.data:parsed);
  const body=showDialog('استعادة نسخة احتياطية');
  addText(body,'p',`${data.clients.length} clients · ${data.plans.length} plans · ${data.invoices.length} invoices · ${data.tasks.length} tasks`);
  addText(body,'p','This replaces your workspace. Your current data is downloaded first. Login email and account access will stay with the signed-in user.');
  addButton(body,'تصدير النسخة الحالية والاستعادة',()=>{
    exportWorkspace();data.account.email=sync.user.email;db=data;logActivity('Restored a JSON backup');saveData();applyTheme();applyColorMode();applyTranslations();renderAll();dialog.close();toast('تمت الاستعادة. راجع حالة الحفظ.');
  });
 }catch(error){toast(error.message||'تعذر قراءة النسخة الاحتياطية.');}
};
document.getElementById('pg-history').onclick=async()=>{
 const body=showDialog('النسخ الأخيرة والنشاط');const loading=addText(body,'p','جارٍ تحميل النسخ المحفوظة…');
 try{
  const history=await sync.history();loading.remove();
  if(!history.length)addText(body,'p','تظهر النسخ بعد أول حفظ، حتى ١٠ نسخ.');
  for(const entry of history){
   const row=document.createElement('div');row.className='history-entry';body.append(row);
   addText(row,'span','Version '+entry.version+' · '+new Date(entry.created_at).toLocaleString());
   addButton(row,'استعادة',async()=>{
    if(!confirm('Restore this cloud version? Your current workspace is downloaded first.'))return;
    try{const snapshot=await sync.snapshot(entry.version);exportWorkspace();db=snapshot;logActivity('Restored cloud version '+entry.version);saveData();applyTheme();applyColorMode();applyTranslations();renderAll();dialog.close();}catch(error){toast(error.message);}
   });
  }
 }catch(error){loading.textContent='Could not load history: '+error.message;}
 addText(body,'h3','نشاط مساحة العمل');
 for(const entry of (db.activity||[]).slice(0,15))addText(body,'p',new Date(entry.at).toLocaleString()+' — '+entry.message);
};
document.getElementById('pg-csv').onclick=()=>{
 const body=showDialog('تصدير جدول');addText(body,'p','نزّل سجلاتك بصيغة جدول CSV.');
 for(const [collection,fields] of [['clients',['name','email','status','tag','joined','rate','notes']],['invoices',['client','amount','status','due','desc']],['leads',['name','email','stage','value','source']],['tasks',['text','done','due','priority']]]){
  addButton(body,'Export '+collection,()=>downloadFile('penguins-'+collection+'.csv',PenguinsCore.csv(db[collection],fields),'text/csv;charset=utf-8'));
 }
};
function clientEditor(name){
 const client=name?db.clients.find(item=>item.name===name):null;
 const body=showDialog(client?'تعديل سجل العميل':'إضافة سجل عميل');const form=document.createElement('form');body.append(form);
 const fields={};
 for(const [key,label,type] of [['name','الاسم الكامل','text'],['email','البريد الإلكتروني','email'],['tag','مجال المتابعة','text'],['rate','السعر الشهري','number'],['notes','ملاحظات','textarea']]){
  const labelElement=addText(form,'label',label);labelElement.htmlFor='edit-client-'+key;
  const input=document.createElement(type==='textarea'?'textarea':'input');if(type!=='textarea')input.type=type;
  input.id='edit-client-'+key;input.name=key;input.value=client?.[key]??(key==='rate'?0:'');input.style.cssText='display:block;width:100%;background:#090910;color:white;border:1px solid #444;padding:10px;border-radius:8px;margin:6px 0 14px';
  if(key==='name'){input.required=true;input.maxLength=80;}if(key==='rate'){input.min='0';input.step='0.01';}
  form.append(input);fields[key]=input;
 }
 const status=document.createElement('select');status.setAttribute('aria-label','Client status');for(const value of ['Active','Paused']){const option=new Option(({Normal:'عادية',High:'عالية',Low:'منخفضة',Active:'نشط',Paused:'متوقف'})[value]||value,value);status.add(option);}status.value=client?.status||'Active';form.append(status);
 const nutrition=document.createElement('input');nutrition.type='checkbox';nutrition.checked=Boolean(client?.nutrition);nutrition.id='edit-client-nutrition';form.append(nutrition);const nutritionLabel=addText(form,'label',' يشمل سجل التغذية');nutritionLabel.htmlFor=nutrition.id;
 const submit=addText(form,'button',client?'حفظ العميل':'إضافة العميل');submit.type='submit';
 const message=addText(form,'p','');message.setAttribute('role','alert');
 form.onsubmit=event=>{
  event.preventDefault();const newName=fields.name.value.trim();
  if(!newName)return;
  if(db.clients.some(item=>item.name.toLowerCase()===newName.toLowerCase()&&item!==client)){message.textContent='يوجد سجل عميل بهذا الاسم.';return;}
  const values={id:client?.id||Date.now(),name:newName,email:fields.email.value.trim(),tag:fields.tag.value.trim()||'Coaching',rate:Number(fields.rate.value)||0,notes:fields.notes.value.trim(),status:status.value,nutrition:nutrition.checked,joined:client?.joined||new Date().toLocaleDateString('ar-EG',{month:'short',year:'numeric'})};
  if(client){
   if(client.name!==newName){
    for(const key of ['chats','metrics','nutritionTargets','nutritionLogs','sessionNotes','clientPRs','clientGoals'])if(Object.hasOwn(db[key],client.name)){db[key][newName]=db[key][client.name];delete db[key][client.name];}
    for(const key of ['plans','invoices','sessions','feedback','submissions','contracts','slots'])for(const item of db[key])if(item.client===client.name)item.client=newName;
    for(const key of ['pointsClients','readiness'])for(const item of db[key])if(item.name===client.name)item.name=newName;
    for(const item of db.cohorts)if(Array.isArray(item.members))item.members=item.members.map(value=>value===client.name?newName:value);
    if(db.activeChat===client.name)db.activeChat=newName;
   }
   Object.assign(client,values);
  }else{db.clients.push(values);db.chats[newName]=[];}
  logActivity((client?'Updated client: ':'Added client: ')+newName);saveData();renderAll();dialog.close();toast('تم حفظ سجل العميل.');
 };
 setTimeout(()=>fields.name.focus(),0);
}
document.getElementById('pg-add-client').onclick=()=>clientEditor();
const originalOpenClientModal=window.openClientModal;
window.openClientModal=name=>{
 originalOpenClientModal(name);const body=document.getElementById('clientModalBody');
 const button=document.createElement('button');button.className='pg-button';button.textContent='تعديل سجل العميل';button.onclick=()=>{closeClientModal();clientEditor(name);};body.prepend(button);
};
let undoState=null,undoTimer;
const originalDelete=window.del;
window.del=(type,id)=>{
 if(!Array.isArray(db[type]))return;
 const index=db[type].findIndex(item=>item.id===id);if(index<0)return;
 undoState={type,index,item:structuredClone(db[type][index])};originalDelete(type,id);logActivity('Removed an item from '+type);saveData();
 document.getElementById('pg-undo').hidden=false;clearTimeout(undoTimer);undoTimer=setTimeout(()=>{document.getElementById('pg-undo').hidden=true;undoState=null;},12000);
};
document.getElementById('pg-undo-button').onclick=()=>{
 if(!undoState)return;db[undoState.type].splice(undoState.index,0,undoState.item);logActivity('Undid removal from '+undoState.type);saveData();renderAll();undoState=null;document.getElementById('pg-undo').hidden=true;clearTimeout(undoTimer);
};
const taskForm=document.getElementById('form-task');
const due=document.createElement('input');due.type='date';due.name='due';due.setAttribute('aria-label','موعد المهمة');due.className='input-dark rounded-lg px-2 text-xs';
const priority=document.createElement('select');priority.name='priority';priority.setAttribute('aria-label','أولوية المهمة');priority.className='input-dark rounded-lg px-2 text-xs';for(const value of ['Normal','High','Low'])priority.add(new Option(({Normal:'عادية',High:'عالية',Low:'منخفضة',Active:'نشط',Paused:'متوقف'})[value]||value,value));
taskForm.style.flexWrap='wrap';taskForm.insertBefore(due,taskForm.lastElementChild);taskForm.insertBefore(priority,taskForm.lastElementChild);
taskForm.onsubmit=event=>{event.preventDefault();const data=new FormData(taskForm);const text=String(data.get('text')||'').trim();if(!text)return;db.tasks.unshift({id:Date.now(),text,done:false,due:data.get('due'),priority:data.get('priority')});logActivity('Added a task');saveData();taskForm.reset();renderTasks();};
renderTasks=function(){
 const list=document.getElementById('list-tasks');if(!list)return;list.replaceChildren();
 if(!db.tasks.length){addText(list,'p','No tasks yet — add your first task above.');return;}
 const today=new Date().toLocaleDateString('en-CA');
 const ordered=[...db.tasks].sort((a,b)=>Number(a.done)-Number(b.done)||Number(b.priority==='High')-Number(a.priority==='High')||String(a.due||'9999').localeCompare(b.due||'9999'));
 for(const task of ordered){
  const row=document.createElement('div');row.className='flex items-center gap-3 bg-black border border-border rounded-lg px-3 py-2.5';list.append(row);
  const checkbox=document.createElement('input');checkbox.type='checkbox';checkbox.checked=task.done;checkbox.setAttribute('aria-label','Complete '+task.text);checkbox.onchange=()=>toggleTask(task.id);row.append(checkbox);
  const label=addText(row,'span',task.text);label.className='text-sm flex-1'+(task.done?' line-through text-muted':'');
  const detail=addText(row,'span',[({Normal:'عادية',High:'عالية',Low:'منخفضة'})[task.priority||'Normal'],task.due||'',!task.done&&task.due&&task.due<today?'متأخر':''].filter(Boolean).join(' · '));detail.className='text-xs text-muted';if(!task.done&&task.due&&task.due<today)detail.style.color='#ffb15a';
  addButton(row,'إزالة',()=>window.del('tasks',task.id));
 }
};
function refreshClientChoices(){
 document.querySelectorAll('select[name="client"],#metrics-client-select').forEach(select=>{
  const previous=select.value;select.replaceChildren();select.add(new Option(db.clients.length?'Select client':'Add a client first',''));
  for(const client of db.clients)select.add(new Option(client.name,client.name));if(db.clients.some(client=>client.name===previous))select.value=previous;
 });
}
const originalRenderAll=renderAll;
renderAll=function(){refreshClientChoices();originalRenderAll();};
const originalPipeline=renderPipeline;
renderPipeline=function(){
 originalPipeline();
 document.querySelectorAll('#pipeline-board .kanban-col').forEach(column=>{
  column.ondragover=event=>onDragOver(event);column.ondragleave=event=>onDragLeave(event);column.ondrop=event=>onDrop(event);
 });
 document.querySelectorAll('#pipeline-board .kanban-card').forEach(card=>{card.ondragstart=event=>onDragStart(event);card.ondragend=event=>onDragEnd(event);});
};
const originalNavigate=navigateTo;
navigateTo=function(page){originalNavigate(page);history.replaceState(null,'','#'+page);};
document.addEventListener('keydown',event=>{
 if((event.ctrlKey||event.metaKey)&&event.key.toLowerCase()==='s'){event.preventDefault();sync.flush();}
});
window.addEventListener('online',()=>toast('Back online — retrying unsaved changes.'));
