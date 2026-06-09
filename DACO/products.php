<?php
include('includes/nav.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <title>DCO — Products</title>
</head>
<body>
<div class="topbar-spacer"></div>

<!-- ══════════════ PAGE HEADER ══════════════ -->
<div id="products-header">
    <p id="products-eyebrow">DCO Collection</p>
    <h1 id="products-title">All Products</h1>
</div>

<!-- ══════════════ FILTER BAR ══════════════ -->
<div id="filter-bar">
    <div id="search-wrap">
        <input id="search-input" type="text" placeholder="Search">
    </div>
    <div id="filter-row">
        <span id="filter-label">Filter:</span>
        <button class="filter-btn active" data-filter="all">All</button>
        <button class="filter-btn" data-filter="clothes">Clothes</button>
        <button class="filter-btn" data-filter="accessories">Accessories</button>
        <button class="filter-btn" data-filter="devices">Devices</button>
        <button class="filter-btn" data-filter="fragrance">Fragrance</button>
    </div>
</div>

<!-- ══════════════ PRODUCTS ══════════════ -->
<div id="products-section">
    <p id="results-count">Showing 12 products</p>
    <div id="products-grid">

        <?php
        $products = [
            ['Oversized Linen Coat',          'Clothes',     4800],
            ['Raw Hem Denim Trousers',         'Clothes',     2100],
            ['Washed Cotton Tee',              'Clothes',      890],
            ['Merino Wool Crewneck',           'Clothes',     3400],
            ['Minimal Leather Bag',            'Accessories', 3200],
            ['Titanium Card Holder',           'Accessories', 1400],
            ['Woven Leather Belt',             'Accessories', 1100],
            ['Brushed Silver Ring',            'Accessories',  980],
            ['DCO Wireless Speaker',           'Devices',     5500],
            ['Noise-Cancel Earphones',         'Devices',     4200],
            ['Noir Santal Eau de Parfum',      'Fragrance',   2600],
            ['Amber & Oud Diffuser Set',       'Fragrance',   1800],
        ];

        $svg = '<svg class="product-placeholder-icon" viewBox="0 0 40 40" fill="none" stroke="#171717" stroke-width="1.2"><rect x="4" y="8" width="32" height="26" rx="1"/><path d="M4 14h32"/><circle cx="14" cy="24" r="4"/><path d="M22 20h10M22 24h8M22 28h6"/></svg>';

        foreach ($products as $i => [$name, $cat, $price]):
            $slug    = strtolower($cat);
            $display = '₱ ' . number_format($price);
            $jsName  = addslashes($name);
            $jsCat   = addslashes($cat);
            $cardId  = 'product-' . ($i + 1);
        ?>
        <div class="product-card" id="<?= $cardId ?>" data-category="<?= $slug ?>">
            <a href="#" class="product-img-wrap">
                <div class="product-placeholder">
                    <?= $svg ?>
                    <span class="product-placeholder-label">No image</span>
                </div>
            </a>
            <span class="product-category"><?= htmlspecialchars($cat) ?></span>
            <span class="product-name"><?= htmlspecialchars($name) ?></span>
            <span class="product-price"><?= $display ?></span>
            <div class="product-actions">
                <button class="btn-buy-now"
                    onclick="DCO_addToCart('<?= $jsName ?>','<?= $jsCat ?>','<?= $price ?>')">
                    Buy Now
                </button>
                <button class="btn-add-cart"
                    onclick="DCO_addToCart('<?= $jsName ?>','<?= $jsCat ?>','<?= $price ?>')">
                    + Cart
                </button>
            </div>
        </div>
        <?php endforeach; ?>

    </div>
</div>

<script>
    const grid    = document.getElementById('products-grid');
    const cards   = [...grid.querySelectorAll('.product-card')];
    const countEl = document.getElementById('results-count');
    const search  = document.getElementById('search-input');
    const filters = document.querySelectorAll('.filter-btn');

    let activeFilter = 'all';

    function applyFilters() {
        const q = search.value.toLowerCase();
        let visible = 0;
        cards.forEach(card => {
            const matchCat  = activeFilter === 'all' || card.dataset.category === activeFilter;
            const matchText = card.querySelector('.product-name').textContent.toLowerCase().includes(q);
            const show = matchCat && matchText;
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        countEl.textContent = `Showing ${visible} product${visible !== 1 ? 's' : ''}`;
    }

    filters.forEach(btn => {
        btn.addEventListener('click', () => {
            filters.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activeFilter = btn.dataset.filter;
            applyFilters();
        });
    });

    search.addEventListener('input', applyFilters);
</script>

</body>
</html>