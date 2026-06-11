<?php
include('includes/nav.php');
include('products_config.php');

if (!isset($products)) { $products = []; }
// Pull only featured products for the homepage showcase
$featured = array_filter($products, function($p) { return !empty($p['featured']); });
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <title>DCO</title>
</head>
<body>
<div class="topbar-spacer"></div>

<!-- HERO -->
<section id="hero">
    <p id="hero-eyebrow">New Collection &mdash; 2026</p>
    <h1 id="hero-title">DCO</h1>
    <p id="hero-sub">Elevated essentials crafted for the discerning few. Clothes, devices, fragrance &amp; more.</p>
    <a id="hero-cta" href="products.php">Shop Now</a>
    <div id="hero-scroll-hint">Scroll</div>
</section>

<!-- PRODUCT SHOWCASE -->
<section id="showcase-section">
    <p class="section-label">Featured</p>
    <h2 class="section-title">New Arrivals</h2>

    <div id="showcase-grid">

        <?php
        $placeholder_svg  = '<svg class="product-placeholder-icon" viewBox="0 0 40 40"';
        $placeholder_svg .= ' fill="none" stroke="#171717" stroke-width="1.2">';
        $placeholder_svg .= '<rect x="4" y="8" width="32" height="26" rx="1"/>';
        $placeholder_svg .= '<path d="M4 14h32"/>';
        $placeholder_svg .= '<circle cx="14" cy="24" r="4"/>';
        $placeholder_svg .= '<path d="M22 20h10M22 24h8M22 28h6"/>';
        $placeholder_svg .= '</svg>';

        foreach ($featured as $p) {
            $display   = '&#8369; ' . number_format($p['price']);
            $cardClass = 'product-card' . (!empty($p['wide']) ? ' wide' : '');
            $jsName    = addslashes($p['name']);
            $jsCat     = addslashes($p['category']);
            $productId = (int) ($p['id'] ?? 0);
            $cardId    = 'showcase-card-' . strtolower(preg_replace('/\s+/', '-', $p['name']));
            $hasImg    = !empty($p['image']);
        ?>
        <div class="<?php echo $cardClass; ?>" id="<?php echo $cardId; ?>">
            <a href="products.php" class="product-img-wrap" style="display:block;">
                <?php if ($hasImg) { ?>
                    <img src="<?php echo htmlspecialchars($p['image']); ?>"
                         alt="<?php echo htmlspecialchars($p['name']); ?>"
                         style="width:100%;height:100%;object-fit:cover;display:block;">
                <?php } else { ?>
                    <div class="product-placeholder">
                        <?php echo $placeholder_svg; ?>
                        <span class="product-placeholder-label">No image</span>
                    </div>
                <?php } ?>
            </a>
            <span class="product-category"><?php echo htmlspecialchars($p['category']); ?></span>
            <span class="product-name"><?php echo htmlspecialchars($p['name']); ?></span>
            <span class="product-price"><?php echo $display; ?></span>
            <button class="btn-add-cart-index"
                onclick="DCO_addToCart('<?php echo $jsName; ?>','<?php echo $jsCat; ?>',<?php echo $p['price']; ?>,<?php echo $productId; ?>)">
                + Add to Cart
            </button>
        </div>
        <?php } ?>

    </div>
</section>

<!-- DARK BANNER -->
<div id="banner-strip">
    <p class="section-label">The DCO Choice</p>
    <h2 class="section-title">Crafted Without Compromise</h2>
    <p id="banner-sub">Every piece in the DCO collection is selected for quality, longevity, and quiet distinction.</p>
    <a id="banner-cta" href="products.php">Explore All Products</a>
</div>

<!-- CATEGORIES -->
<section id="categories-section">
    <p class="section-label">Browse By</p>
    <h2 class="section-title">Categories</h2>

    <div id="categories-grid">
        <a href="products.php" class="cat-card" id="cat-clothes">
            <span class="cat-num">01</span>
            <svg class="cat-icon" viewBox="0 0 36 36" fill="none" stroke="#171717" stroke-width="1.2"><path d="M10 6c0 0 2 4 8 4s8-4 8-4l3 6v20H7V12l3-6z"/></svg>
            <span class="cat-name">Clothes</span>
            <span class="cat-count">7 items</span>
        </a>
        <a href="products.php" class="cat-card" id="cat-accessories">
            <span class="cat-num">02</span>
            <svg class="cat-icon" viewBox="0 0 36 36" fill="none" stroke="#171717" stroke-width="1.2"><rect x="6" y="12" width="24" height="18" rx="1"/><path d="M12 12V9a6 6 0 0 1 12 0v3"/></svg>
            <span class="cat-name">Accessories</span>
            <span class="cat-count">3 items</span>
        </a>
        <a href="products.php" class="cat-card" id="cat-devices">
            <span class="cat-num">03</span>
            <svg class="cat-icon" viewBox="0 0 36 36" fill="none" stroke="#171717" stroke-width="1.2"><rect x="5" y="8" width="26" height="20" rx="2"/><path d="M5 14h26M13 8v6M23 8v6"/></svg>
            <span class="cat-name">Devices</span>
            <span class="cat-count">2 items</span>
        </a>
        <a href="products.php" class="cat-card" id="cat-fragrance">
            <span class="cat-num">04</span>
            <svg class="cat-icon" viewBox="0 0 36 36" fill="none" stroke="#171717" stroke-width="1.2"><path d="M18 6c0 0-8 6-8 14a8 8 0 0 0 16 0c0-8-14-8-14z"/><path d="M18 18v8"/></svg>
            <span class="cat-name">Fragrance</span>
            <span class="cat-count">2 items</span>
        </a>
    </div>
</section>

</body>
</html>