
// Starfield generation
(function initStars() {
  const wrap = document.getElementById('stars');
  const count = window.innerWidth < 640 ? 60 : 130;
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

// Mobile nav toggle
function toggleMobileNav() {
  const panel = document.getElementById('mobileNavPanel');
  const btn = document.getElementById('mobileNavBtn');
  const isHidden = panel.classList.contains('hidden');
  panel.classList.toggle('hidden');
  btn.innerText = isHidden ? '×' : '☰';
}

// FAQ accordion
document.querySelectorAll('.faq-trigger').forEach(btn => {
  btn.addEventListener('click', () => {
    const item = btn.closest('.faq-item');
    const panel = item.querySelector('.faq-panel');
    const icon = item.querySelector('.faq-icon');
    const isOpen = !panel.classList.contains('hidden');
    document.querySelectorAll('.faq-panel').forEach(p => p.classList.add('hidden'));
    document.querySelectorAll('.faq-icon').forEach(i => i.style.transform = 'rotate(0deg)');
    if (!isOpen) {
      panel.classList.remove('hidden');
      icon.style.transform = 'rotate(45deg)';
    }
  });
});

// Smooth scroll for in-page anchors
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', e => {
    const id = a.getAttribute('href');
    if (id.length > 1) {
      const target = document.querySelector(id);
      if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    }
  });
});
