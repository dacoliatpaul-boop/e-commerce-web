<?php
include('includes/nav.php');
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DCO</title>
    <style>
        /* ── Hero ── */
        #hero {
            min-height: 92vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 80px 24px 60px;
            background: #f7f7f5;
            position: relative;
            overflow: hidden;
        }
        #hero::before {
            content: 'DCO';
            position: absolute;
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(180px, 30vw, 400px);
            font-weight: 300;
            color: rgba(0,0,0,0.04);
            letter-spacing: 0.1em;
            user-select: none;
            pointer-events: none;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            white-space: nowrap;
        }
        #hero-eyebrow {
            font-family: 'Montserrat', sans-serif;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: #888;
            margin-bottom: 24px;
        }
        #hero-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(56px, 10vw, 120px);
            font-weight: 300;
            color: #171717;
            line-height: 1;
            letter-spacing: 0.05em;
            margin: 0 0 32px;
        }
        #hero-sub {
            font-family: 'Montserrat', sans-serif;
            font-size: 12px;
            font-weight: 400;
            letter-spacing: 0.2em;
            color: #666;
            text-transform: uppercase;
            max-width: 380px;
            line-height: 1.8;
            margin-bottom: 48px;
        }
        #hero-cta {
            display: inline-block;
            font-family: 'Montserrat', sans-serif;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: #fff;
            background: #171717;
            padding: 16px 40px;
            text-decoration: none;
            transition: background 0.25s;
        }
        #hero-cta:hover { background: #333; }
        #hero-scroll-hint {
            position: absolute;
            bottom: 32px;
            left: 50%;
            transform: translateX(-50%);
            font-family: 'Montserrat', sans-serif;
            font-size: 10px;
            letter-spacing: 0.2em;
            color: #aaa;
            text-transform: uppercase;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        #hero-scroll-hint::after {
            content: '';
            display: block;
            width: 1px;
            height: 40px;
            background: #ccc;
            animation: scrollLine 1.6s ease-in-out infinite;
        }
        @keyframes scrollLine {
            0%,100% { opacity: 0.3; } 50% { opacity: 1; }
        }

        /* ── Section headings ── */
        .section-label {
            font-family: 'Montserrat', sans-serif;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: #aaa;
            text-align: center;
            margin-bottom: 16px;
        }
        .section-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(32px, 5vw, 56px);
            font-weight: 400;
            color: #171717;
            text-align: center;
            margin: 0 0 60px;
            letter-spacing: 0.03em;
        }

        /* ── Showcase ── */
        #showcase-section {
            padding: 100px 48px;
            background: #ffffff;
        }
        #showcase-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            max-width: 1300px;
            margin: 0 auto;
        }

        /* ── Product card shared ── */
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
            margin-bottom: 10px;
        }

        /* ── Add to cart (index) ── */
        .btn-add-cart-index {
            display: block;
            width: 100%;
            padding: 10px;
            background: transparent;
            border: 1px solid #d0d0d0;
            font-family: 'Montserrat', sans-serif;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #171717;
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s, color 0.2s;
            margin-top: 4px;
        }
        .btn-add-cart-index:hover {
            background: #171717;
            border-color: #171717;
            color: #fff;
        }

        /* ── Wide card ── */
        .product-card.wide { grid-column: span 2; }
        .product-card.wide .product-img-wrap { padding-bottom: 60%; }

        /* ── Banner ── */
        #banner-strip {
            background: #171717;
            color: #fff;
            padding: 72px 48px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 20px;
        }
        #banner-strip .section-label { color: rgba(255,255,255,0.4); }
        #banner-strip .section-title { color: #fff; margin-bottom: 20px; }
        #banner-sub {
            font-family: 'Montserrat', sans-serif;
            font-size: 12px;
            letter-spacing: 0.15em;
            color: rgba(255,255,255,0.55);
            text-transform: uppercase;
            max-width: 420px;
            line-height: 1.9;
        }
        #banner-cta {
            display: inline-block;
            margin-top: 16px;
            font-family: 'Montserrat', sans-serif;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: #171717;
            background: #fff;
            padding: 14px 36px;
            text-decoration: none;
            transition: opacity 0.2s;
        }
        #banner-cta:hover { opacity: 0.85; }

        /* ── Categories ── */
        #categories-section {
            padding: 100px 48px;
            background: #f7f7f5;
        }
        #categories-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            max-width: 1300px;
            margin: 0 auto;
        }
        .cat-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            padding: 40px 20px 32px;
            background: #fff;
            border: 1px solid #e8e8e8;
            text-decoration: none;
            color: inherit;
            gap: 12px;
            min-height: 200px;
            position: relative;
            overflow: hidden;
            transition: border-color 0.25s;
        }
        .cat-card:hover { border-color: #171717; }
        .cat-num {
            position: absolute;
            top: 16px; right: 20px;
            font-family: 'Cormorant Garamond', serif;
            font-size: 64px;
            font-weight: 300;
            color: #171717;
            opacity: 0.04;
            line-height: 1;
            user-select: none;
            transition: opacity 0.25s;
        }
        .cat-card:hover .cat-num { opacity: 0.07; }
        .cat-icon { width: 36px; height: 36px; opacity: 0.6; }
        .cat-name {
            font-family: 'Montserrat', sans-serif;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: #171717;
        }
        .cat-count {
            font-family: 'Montserrat', sans-serif;
            font-size: 10px;
            color: #aaa;
            letter-spacing: 0.1em;
        }

        /* ── Responsive ── */
        @media (max-width: 900px) {
            #showcase-grid { grid-template-columns: repeat(2, 1fr); }
            .product-card.wide { grid-column: span 2; }
            #categories-grid { grid-template-columns: repeat(2, 1fr); }
            #showcase-section, #categories-section { padding: 60px 24px; }
        }
        @media (max-width: 560px) {
            #showcase-grid { grid-template-columns: 1fr; }
            .product-card.wide { grid-column: span 1; }
            .product-card.wide .product-img-wrap { padding-bottom: 100%; }
            #categories-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
<div class="topbar-spacer"></div>

<!-- ══════════════ HERO ══════════════ -->
<section id="hero">
    <p id="hero-eyebrow">New Collection — 2025</p>
    <h1 id="hero-title">DCO</h1>
    <p id="hero-sub">Elevated essentials crafted for the discerning few. Clothes, devices, fragrance &amp; more.</p>
    <a id="hero-cta" href="products.php">Shop Now</a>
    <div id="hero-scroll-hint">Scroll</div>
</section>

<!-- ══════════════ PRODUCT SHOWCASE ══════════════ -->
<section id="showcase-section">
    <p class="section-label">Featured</p>
    <h2 class="section-title">New Arrivals</h2>

    <div id="showcase-grid">

        <?php
        $svg = '<svg class="product-placeholder-icon" viewBox="0 0 40 40" fill="none" stroke="#171717" stroke-width="1.2"><rect x="4" y="8" width="32" height="26" rx="1"/><path d="M4 14h32"/><circle cx="14" cy="24" r="4"/><path d="M22 20h10M22 24h8M22 28h6"/></svg>';

        $featured = [
            ['Oversized Linen Coat',     'Clothes',     '4800',  'wide'],
            ['Minimal Leather Bag',      'Accessories', '3200',  ''],
            ['Noir Santal Eau de Parfum','Fragrance',   '2600',  ''],
            ['DCO Wireless Speaker',     'Devices',     '5500',  ''],
            ['Raw Hem Denim Trousers',   'Clothes',     '2100',  ''],
            ['Titanium Card Holder',     'Accessories', '1400',  ''],
        ];

        foreach ($featured as [$name, $cat, $rawPrice, $wide]):
            $display = '₱ ' . number_format((int)$rawPrice);
            $cardClass = 'product-card' . ($wide ? ' wide' : '');
            $jsName  = addslashes($name);
            $jsCat   = addslashes($cat);
        ?>
        <div class="<?= $cardClass ?>" id="showcase-card-<?= strtolower(preg_replace('/\s+/','-',$name)) ?>">
            <a href="products.php" class="product-img-wrap" style="display:block;">
                <div class="product-placeholder">
                    <?= $svg ?>
                    <span class="product-placeholder-label">No image</span>
                </div>
            </a>
            <span class="product-category"><?= $cat ?></span>
            <span class="product-name"><?= $name ?></span>
            <span class="product-price"><?= $display ?></span>
            <button class="btn-add-cart-index"
                onclick="DCO_addToCart('<?= $jsName ?>','<?= $jsCat ?>','<?= $rawPrice ?>')">
                + Add to Cart
            </button>
        </div>
        <?php endforeach; ?>

    </div>
</section>

<!-- ══════════════ DARK BANNER ══════════════ -->
<div id="banner-strip">
    <p class="section-label">The DCO Edit</p>
    <h2 class="section-title">Crafted Without Compromise</h2>
    <p id="banner-sub">Every piece in the DCO collection is selected for quality, longevity, and quiet distinction.</p>
    <a id="banner-cta" href="products.php">Explore All Products</a>
</div>

<!-- ══════════════ CATEGORIES ══════════════ -->
<section id="categories-section">
    <p class="section-label">Browse By</p>
    <h2 class="section-title">Categories</h2>

    <div id="categories-grid">
        <a href="products.php" class="cat-card" id="cat-clothes">
            <span class="cat-num">01</span>
            <svg class="cat-icon" viewBox="0 0 36 36" fill="none" stroke="#171717" stroke-width="1.2"><path d="M10 6c0 0 2 4 8 4s8-4 8-4l3 6v20H7V12l3-6z"/></svg>
            <span class="cat-name">Clothes</span>
            <span class="cat-count">12 items</span>
        </a>
        <a href="products.php" class="cat-card" id="cat-accessories">
            <span class="cat-num">02</span>
            <svg class="cat-icon" viewBox="0 0 36 36" fill="none" stroke="#171717" stroke-width="1.2"><rect x="6" y="12" width="24" height="18" rx="1"/><path d="M12 12V9a6 6 0 0 1 12 0v3"/></svg>
            <span class="cat-name">Accessories</span>
            <span class="cat-count">8 items</span>
        </a>
        <a href="products.php" class="cat-card" id="cat-devices">
            <span class="cat-num">03</span>
            <svg class="cat-icon" viewBox="0 0 36 36" fill="none" stroke="#171717" stroke-width="1.2"><rect x="5" y="8" width="26" height="20" rx="2"/><path d="M5 14h26M13 8v6M23 8v6"/></svg>
            <span class="cat-name">Devices</span>
            <span class="cat-count">6 items</span>
        </a>
        <a href="products.php" class="cat-card" id="cat-fragrance">
            <span class="cat-num">04</span>
            <svg class="cat-icon" viewBox="0 0 36 36" fill="none" stroke="#171717" stroke-width="1.2"><path d="M18 6c0 0-8 6-8 14a8 8 0 0 0 16 0c0-8-8-14-8-14z"/><path d="M18 18v8"/></svg>
            <span class="cat-name">Fragrance</span>
            <span class="cat-count">5 items</span>
        </a>
    </div>
</section>

</body>
</html>