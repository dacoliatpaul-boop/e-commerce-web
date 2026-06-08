<?php
include('includes/nav.php');
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DCO — Products</title>
    <style>
        /* ── Page header ── */
        #products-header {
            padding: 72px 48px 48px;
            background: #f7f7f5;
            text-align: center;
        }
        #products-eyebrow {
            font-family: 'Montserrat', sans-serif;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.3em;
            color: #aaa;
            text-transform: uppercase;
            margin-bottom: 12px;
        }
        #products-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(40px, 7vw, 80px);
            font-weight: 300;
            color: #171717;
            letter-spacing: 0.05em;
            margin: 0;
        }

        /* ── Filter bar ── */
        #filter-bar {
            background: #f0efec;
            padding: 24px 48px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            border-bottom: 1px solid #e8e8e8;
            position: sticky;
            top: 60px;
            z-index: 100;
        }
        #search-wrap { width: 100%; max-width: 760px; }
        #search-input {
            width: 100%;
            padding: 16px 24px;
            font-family: 'Montserrat', sans-serif;
            font-size: 12px;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: #171717;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 60px;
            outline: none;
            transition: border-color 0.2s;
        }
        #search-input::placeholder { color: #bbb; }
        #search-input:focus { border-color: #171717; }

        #filter-row {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
        }
        #filter-label {
            font-family: 'Montserrat', sans-serif;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.2em;
            color: #aaa;
            text-transform: uppercase;
        }
        .filter-btn {
            font-family: 'Montserrat', sans-serif;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: #555;
            background: transparent;
            border: 1px solid #ccc;
            padding: 8px 18px;
            cursor: pointer;
            transition: background 0.2s, color 0.2s, border-color 0.2s;
        }
        .filter-btn:hover, .filter-btn.active {
            background: #171717;
            color: #fff;
            border-color: #171717;
        }

        /* ── Products section ── */
        #products-section {
            padding: 60px 48px 100px;
            background: #ffffff;
            max-width: 1400px;
            margin: 0 auto;
        }
        #results-count {
            font-family: 'Montserrat', sans-serif;
            font-size: 10px;
            letter-spacing: 0.15em;
            color: #aaa;
            text-transform: uppercase;
            margin-bottom: 32px;
        }
        #products-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 40px 24px;
        }

        /* ── Product card ── */
        .product-card {
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: inherit;
        }
        .product-card:hover .product-img-wrap::after { opacity: 1; }
        .product-img-wrap {
            position: relative;
            width: 100%;
            padding-bottom: 125%;
            background: #f0efec;
            overflow: hidden;
            margin-bottom: 14px;
            display: block;
        }
        .product-img-wrap::after {
            content: 'VIEW';
            position: absolute;
            inset: 0;
            background: rgba(23,23,23,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Montserrat', sans-serif;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.25em;
            color: #fff;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .product-placeholder {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .product-placeholder-icon { width: 40px; height: 40px; opacity: 0.18; }
        .product-placeholder-label {
            font-family: 'Montserrat', sans-serif;
            font-size: 9px;
            letter-spacing: 0.2em;
            color: #888;
            text-transform: uppercase;
        }
        .product-category {
            font-family: 'Montserrat', sans-serif;
            font-size: 9px;
            font-weight: 500;
            letter-spacing: 0.2em;
            color: #aaa;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .product-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 19px;
            font-weight: 400;
            color: #171717;
            margin-bottom: 5px;
        }
        .product-price {
            font-family: 'Montserrat', sans-serif;
            font-size: 12px;
            font-weight: 500;
            color: #555;
            margin-bottom: 12px;
        }

        /* ── Product buttons ── */
        .product-actions {
            display: flex;
            gap: 8px;
            margin-top: auto;
        }
        .btn-buy-now {
            flex: 1;
            padding: 10px 8px;
            background: #171717;
            color: #fff;
            font-family: 'Montserrat', sans-serif;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            border: 1px solid #171717;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-buy-now:hover { background: #333; border-color: #333; }
        .btn-add-cart {
            flex: 1;
            padding: 10px 8px;
            background: transparent;
            color: #171717;
            font-family: 'Montserrat', sans-serif;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            border: 1px solid #d0d0d0;
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s, color 0.2s;
        }
        .btn-add-cart:hover {
            background: #f5f5f5;
            border-color: #999;
        }

        /* ── Responsive ── */
        @media (max-width: 1000px) { #products-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 700px) {
            #products-grid { grid-template-columns: repeat(2, 1fr); }
            #products-section, #filter-bar { padding-left: 20px; padding-right: 20px; }
            #products-header { padding: 60px 20px 36px; }
        }
        @media (max-width: 420px) { #products-grid { grid-template-columns: 1fr; } }
    </style>
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