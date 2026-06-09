<?php
include('includes/nav.php');
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <title>DCO</title>
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