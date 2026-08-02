<?php
$__page_title = 'Compare Products | ModMyPC';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container" style="max-width:1100px;">
  <h1><i class="fas fa-code-compare" style="color:var(--primary);"></i> Compare Products</h1>
  <p class="muted">Add up to 4 products from the catalog to compare specs side by side.</p>
  <div id="compareWrap" style="overflow-x:auto; margin-top:20px;"></div>
  <div id="compareEmpty" class="empty-cart" style="display:none;">
    <i class="fas fa-code-compare" style="font-size:60px; color:var(--primary); opacity:.4;"></i>
    <h2>Nothing to compare yet</h2>
    <p>Use the compare icon on any product card to add it here.</p>
    <a href="/products.php" class="btn">Browse Products</a>
  </div>
</div>
<script>
(function () {
  const wrap = document.getElementById('compareWrap');
  const empty = document.getElementById('compareEmpty');
  const ids = JSON.parse(localStorage.getItem('mmpc_compare') || '[]');

  if (ids.length === 0) { empty.style.display = 'block'; return; }

  fetch('/api_products_lookup.php?ids=' + ids.join(','))
    .then(r => r.json())
    .then(products => {
      if (products.length === 0) { empty.style.display = 'block'; return; }
      const rows = [
        ['Image', p => p.image ? `<img src="/${p.image}" style="width:70px;height:70px;object-fit:contain;">` : '—'],
        ['Name', p => p.name],
        ['Brand', p => p.brand || '—'],
        ['Category', p => p.category || '—'],
        ['Price', p => '&#8377;' + Number(p.price).toLocaleString('en-IN')],
        ['Availability', p => p.stock > 0 ? `<span class="stock-tag in-stock">In Stock</span>` : `<span class="stock-tag low-stock">Out of Stock</span>`],
      ];
      let html = '<table class="data-table" style="min-width:600px;"><tbody>';
      rows.forEach(([label, fn]) => {
        html += `<tr><th style="width:140px;">${label}</th>` + products.map(p => `<td>${fn(p)}</td>`).join('') + '</tr>';
      });
      html += `<tr><th></th>` + products.map(p => `<td>
        <a href="/product.php?id=${p.id}" class="btn btn-sm btn-outline">View</a>
        <button class="btn btn-sm btn-danger" onclick="removeFromCompare(${p.id})">Remove</button>
      </td>`).join('') + '</tr>';
      html += '</tbody></table>';
      wrap.innerHTML = html;
    })
    .catch(() => { wrap.innerHTML = '<p class="muted">Could not load comparison right now.</p>'; });
})();

function removeFromCompare(id) {
  let list = JSON.parse(localStorage.getItem('mmpc_compare') || '[]');
  list = list.filter(x => x !== id);
  localStorage.setItem('mmpc_compare', JSON.stringify(list));
  location.reload();
}
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
