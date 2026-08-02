/**
 * ModMyPC — Interaction layer v2
 */
(function () {
  // ---- Scroll progress bar ----
  var bar = document.createElement('div');
  bar.id = 'scrollProgress';
  document.body.appendChild(bar);
  function updateProgress() {
    var h = document.documentElement;
    var scrolled = (h.scrollTop) / ((h.scrollHeight - h.clientHeight) || 1) * 100;
    bar.style.width = scrolled + '%';
  }
  window.addEventListener('scroll', updateProgress, { passive: true });
  updateProgress();

  // ---- Navbar: transparent -> glass on scroll, hide on scroll down / show on scroll up ----
  var header = document.querySelector('.site-header');
  if (header) {
    var lastY = window.scrollY;
    window.addEventListener('scroll', function () {
      var y = window.scrollY;
      header.classList.toggle('scrolled', y > 30);
      if (y > lastY && y > 120) header.classList.add('hide-nav');
      else header.classList.remove('hide-nav');
      lastY = y;
    }, { passive: true });
  }

  // ---- Theme toggle state (persisted) ----
  var THEME_KEY = 'mmpc_theme';
  var saved = localStorage.getItem(THEME_KEY) || 'dark';
  document.body.setAttribute('data-theme', saved);

  // ---- Expandable Quick Actions FAB (replaces old stacked circles) ----
  var cluster = document.createElement('div');
  cluster.id = 'fabCluster';
  cluster.innerHTML =
    '<div id="fabOptions">' +
      '<div class="fab-option" id="fabTheme"><span class="fo-icon"><i class="fas fa-sun"></i></span><span id="fabThemeLabel">Light Mode</span></div>' +
      '<div class="fab-option" id="fabTop"><span class="fo-icon"><i class="fas fa-arrow-up"></i></span>Back to Top</div>' +
      '<div class="fab-option" id="fabWhatsapp"><span class="fo-icon"><i class="fab fa-whatsapp"></i></span>Chat With Us</div>' +
    '</div>' +
    '<button id="fabMain" aria-label="Quick actions"><i class="fas fa-plus"></i></button>';
  document.body.appendChild(cluster);

  var fabMain = document.getElementById('fabMain');
  var fabOptions = document.getElementById('fabOptions');
  var optionEls = fabOptions.querySelectorAll('.fab-option');
  var fabOpen = false;
  function setFab(open) {
    fabOpen = open;
    fabMain.classList.toggle('open', open);
    fabOptions.classList.toggle('open', open);
    optionEls.forEach(function (el, i) {
      setTimeout(function () { el.classList.toggle('show', open); }, open ? i * 60 : 0);
    });
  }
  fabMain.addEventListener('click', function () { setFab(!fabOpen); });
  document.addEventListener('click', function (e) {
    if (fabOpen && !cluster.contains(e.target)) setFab(false);
  });

  function renderThemeLabel() {
    var t = document.body.getAttribute('data-theme');
    document.getElementById('fabThemeLabel').textContent = t === 'light' ? 'Dark Mode' : 'Light Mode';
    document.querySelector('#fabTheme i').className = t === 'light' ? 'fas fa-moon' : 'fas fa-sun';
  }
  renderThemeLabel();
  document.getElementById('fabTheme').addEventListener('click', function () {
    var next = document.body.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
    document.body.setAttribute('data-theme', next);
    localStorage.setItem(THEME_KEY, next);
    renderThemeLabel();
  });
  document.getElementById('fabTop').addEventListener('click', function () {
    window.scrollTo({ top: 0, behavior: 'smooth' });
    setFab(false);
  });
  document.getElementById('fabWhatsapp').addEventListener('click', function () {
    window.open('https://wa.me/918089637705', '_blank');
  });

  // ---- Toast notifications ----
  var stack = document.createElement('div');
  stack.id = 'toastStack';
  document.body.appendChild(stack);
  window.mmpcToast = function (message, type) {
    var t = document.createElement('div');
    t.className = 'toast' + (type ? ' ' + type : '');
    t.textContent = message;
    stack.appendChild(t);
    setTimeout(function () {
      t.classList.add('hide');
      setTimeout(function () { t.remove(); }, 250);
    }, 3500);
  };

  // ---- Cursor spotlight (desktop only) ----
  if (window.matchMedia('(pointer: fine)').matches) {
    var glow = document.createElement('div');
    glow.className = 'spotlight';
    document.body.appendChild(glow);
    var glowTimer;
    document.addEventListener('mousemove', function (e) {
      glow.style.setProperty('--mx', e.clientX + 'px');
      glow.style.setProperty('--my', e.clientY + 'px');
      glow.classList.add('active');
      clearTimeout(glowTimer);
      glowTimer = setTimeout(function () { glow.classList.remove('active'); }, 400);
    });
  }

  // ---- Magnetic buttons (desktop only) ----
  if (window.matchMedia('(pointer: fine)').matches) {
    document.querySelectorAll('.btn').forEach(function (btn) {
      btn.addEventListener('mousemove', function (e) {
        var r = btn.getBoundingClientRect();
        var x = (e.clientX - r.left - r.width / 2) * 0.25;
        var y = (e.clientY - r.top - r.height / 2) * 0.35;
        btn.style.transform = 'translate(' + x + 'px,' + y + 'px)';
      });
      btn.addEventListener('mouseleave', function () { btn.style.transform = ''; });
    });
  }

  // ---- Button ripple effect ----
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.btn');
    if (!btn) return;
    var r = btn.getBoundingClientRect();
    var ripple = document.createElement('span');
    var size = Math.max(r.width, r.height);
    ripple.className = 'ripple';
    ripple.style.width = ripple.style.height = size + 'px';
    ripple.style.left = (e.clientX - r.left - size / 2) + 'px';
    ripple.style.top = (e.clientY - r.top - size / 2) + 'px';
    btn.style.position = btn.style.position || 'relative';
    btn.appendChild(ripple);
    setTimeout(function () { ripple.remove(); }, 650);
  });

  // ---- Product card tilt (desktop only) ----
  if (window.matchMedia('(pointer: fine)').matches) {
    document.addEventListener('mousemove', function (e) {
      var card = e.target.closest('.product-card');
      if (!card) return;
      var r = card.getBoundingClientRect();
      var px = (e.clientX - r.left) / r.width - 0.5;
      var py = (e.clientY - r.top) / r.height - 0.5;
      card.style.transform = 'perspective(700px) rotateY(' + (px * 8) + 'deg) rotateX(' + (-py * 8) + 'deg) translateY(-4px)';
    });
    document.addEventListener('mouseout', function (e) {
      var card = e.target.closest('.product-card');
      if (card && !card.contains(e.relatedTarget)) card.style.transform = '';
    });
  }

  // ---- Animated number counters (elements with [data-counter]) ----
  function animateCounter(el) {
    var target = parseInt(el.getAttribute('data-counter'), 10) || 0;
    var duration = 1400, start = null;
    function step(ts) {
      if (!start) start = ts;
      var progress = Math.min((ts - start) / duration, 1);
      el.textContent = Math.floor(progress * target).toLocaleString('en-IN') + (el.getAttribute('data-suffix') || '');
      if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }
  var counters = document.querySelectorAll('[data-counter]');
  if (counters.length && 'IntersectionObserver' in window) {
    var cio = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { animateCounter(en.target); cio.unobserve(en.target); } });
    }, { threshold: 0.4 });
    counters.forEach(function (c) { cio.observe(c); });
  }

  // ---- Lightweight confetti burst (no external library) ----
  window.mmpcConfetti = function () {
    var colors = ['#ff3860', '#ff8a3d', '#7c5cff', '#22c55e', '#fbbf24'];
    for (var i = 0; i < 40; i++) {
      var p = document.createElement('div');
      var color = colors[Math.floor(Math.random() * colors.length)];
      var size = 6 + Math.random() * 6;
      p.style.cssText = 'position:fixed;top:50%;left:50%;width:' + size + 'px;height:' + size + 'px;' +
        'background:' + color + ';z-index:10001;border-radius:' + (Math.random() > 0.5 ? '50%' : '2px') + ';pointer-events:none;';
      document.body.appendChild(p);
      var angle = Math.random() * Math.PI * 2;
      var dist = 120 + Math.random() * 180;
      var x = Math.cos(angle) * dist, y = Math.sin(angle) * dist - 80;
      p.animate([
        { transform: 'translate(0,0) rotate(0deg)', opacity: 1 },
        { transform: 'translate(' + x + 'px,' + y + 'px) rotate(' + (Math.random() * 720 - 360) + 'deg)', opacity: 0 }
      ], { duration: 900 + Math.random() * 500, easing: 'cubic-bezier(.2,.8,.3,1)' }).onfinish = function () { p.remove(); };
    }
  };

  // ---- Scroll reveal fallback (used if AOS CDN isn't loaded) ----
  if (typeof AOS === 'undefined') {
    var targets = document.querySelectorAll('[data-aos]');
    if (targets.length && 'IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
          if (e.isIntersecting) { e.target.classList.add('aos-in'); io.unobserve(e.target); }
        });
      }, { threshold: 0.15 });
      targets.forEach(function (el) { io.observe(el); });
    } else {
      targets.forEach(function (el) { el.classList.add('aos-in'); });
    }
  }
})();
