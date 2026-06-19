<?php
include('includes/nav.php');
include('products_config.php');
if (!isset($products)) { $products = []; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <title>DCO &mdash; Products</title>
</head>
<body>
<div class="topbar-spacer"></div>

<!-- PAGE HEADER -->
<div id="products-header">
    <p id="products-eyebrow">DCO Collection</p>
    <h1 id="products-title">All Products</h1>
</div>

<!-- FILTER BAR -->
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

<!-- PRODUCTS -->
<div id="products-section">
    <p id="results-count">Showing <?php echo count($products); ?> products</p>
    <div id="products-grid">

        <?php
        $placeholder_svg  = '<svg class="product-placeholder-icon" viewBox="0 0 40 40"';
        $placeholder_svg .= ' fill="none" stroke="#171717" stroke-width="1.2">';
        $placeholder_svg .= '<rect x="4" y="8" width="32" height="26" rx="1"/>';
        $placeholder_svg .= '<path d="M4 14h32"/>';
        $placeholder_svg .= '<circle cx="14" cy="24" r="4"/>';
        $placeholder_svg .= '<path d="M22 20h10M22 24h8M22 28h6"/>';
        $placeholder_svg .= '</svg>';

        foreach ($products as $i => $p) {
            $slug      = strtolower($p['category']);
            $display   = '&#8369; ' . number_format($p['price']);
            $jsName    = addslashes($p['name']);
            $jsCat     = addslashes($p['category']);
            $productId = (int) ($p['id'] ?? 0);
            $cardId    = 'product-' . ($i + 1);
            $hasImg    = !empty($p['image']);
            $stock     = (int) ($p['stock'] ?? 0);
            $outOfStock = $stock <= 0;
        ?>
        <div class="product-card<?php echo $outOfStock ? ' out-of-stock' : ''; ?>" id="<?php echo $cardId; ?>" data-category="<?php echo $slug; ?>">
            <a href="#" class="product-img-wrap">
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
                <?php if ($outOfStock) { ?>
                    <span class="out-of-stock-badge">Out of Stock</span>
                <?php } elseif ($stock <= 5) { ?>
                    <span class="low-stock-badge">Only <?php echo $stock; ?> left</span>
                <?php } ?>
            </a>
            <span class="product-category"><?php echo htmlspecialchars($p['category']); ?></span>
            <span class="product-name"><?php echo htmlspecialchars($p['name']); ?></span>
            <span class="product-price"><?php echo $display; ?></span>
            <div class="product-actions">
                <?php if ($outOfStock) { ?>
                    <button class="btn-buy-now" disabled>Sold Out</button>
                    <button class="btn-add-cart" disabled>Sold Out</button>
                <?php } else { ?>
                    <button class="btn-buy-now"
                        onclick="DCO_addToCart('<?php echo $jsName; ?>','<?php echo $jsCat; ?>',<?php echo $p['price']; ?>,<?php echo $productId; ?>)">
                        Buy Now
                    </button>
                    <button class="btn-add-cart"
                        onclick="DCO_addToCart('<?php echo $jsName; ?>','<?php echo $jsCat; ?>',<?php echo $p['price']; ?>,<?php echo $productId; ?>)">
                        + Cart
                    </button>
                <?php } ?>
            </div>
        </div>
        <?php } ?>

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
        countEl.textContent = 'Showing ' + visible + ' product' + (visible !== 1 ? 's' : '');
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

<?php include('includes/footer.php'); ?>

</body>
</html>