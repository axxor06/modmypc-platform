(function () {
  var css = '#mmpc-widget{position:fixed;top:14px;right:14px;z-index:99999;'
    + 'font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;'
    + 'display:flex;gap:8px;align-items:center;}'
    + '#mmpc-widget a{background:#0071e3;color:#fff;text-decoration:none;'
    + 'padding:8px 14px;border-radius:999px;font-size:13px;font-weight:600;'
    + 'box-shadow:0 2px 10px rgba(0,0,0,0.15);display:inline-flex;align-items:center;gap:6px;'
    + 'transition:transform .15s;}'
    + '#mmpc-widget a:hover{transform:translateY(-1px);}'
    + '#mmpc-widget a.mmpc-outline{background:#fff;color:#0071e3;border:1.5px solid #0071e3;}'
    + '#mmpc-widget .mmpc-badge{background:#fff;color:#0071e3;border-radius:999px;'
    + 'font-size:11px;padding:0 6px;font-weight:800;}'
    + '@media (max-width:480px){#mmpc-widget{top:8px;right:8px;}#mmpc-widget a{padding:7px 10px;font-size:12px;}}';
  var style = document.createElement('style');
  style.textContent = css;
  document.head.appendChild(style);

  var box = document.createElement('div');
  box.id = 'mmpc-widget';
  box.innerHTML = '<a href="/products.php" class="mmpc-outline">Shop</a>';
  document.body.appendChild(box);

  fetch('/auth/status.php', { credentials: 'same-origin' })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      var html = '<a href="/products.php" class="mmpc-outline">Shop</a>';
      html += '<a href="/cart.php">Cart' + (data.cart_count > 0 ? ' <span class="mmpc-badge">' + data.cart_count + '</span>' : '') + '</a>';
      if (data.logged_in) {
        html += '<a href="/auth/profile.php" class="mmpc-outline">' + (data.name || 'Account') + '</a>';
      } else {
        html += '<a href="/auth/login.php" class="mmpc-outline">Login</a>';
      }
      box.innerHTML = html;
    })
    .catch(function () { /* fail silently, static page still works fine */ });
})();
