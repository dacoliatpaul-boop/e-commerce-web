<?php
// includes/nav.php
if (session_status() === PHP_SESSION_NONE) session_start();
$isLoggedIn = !empty($_SESSION['user_id']);
$userEmail  = $_SESSION['email'] ?? '';
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="css/style.css">

<!-- ── Nav Overlay ── -->
<div class="nav-overlay" id="nav-overlay"></div>

<!-- ── Sidebar ── -->
<nav class="sidebar" id="sidebar" aria-label="Main navigation">
    <button class="sidebar-close" id="sidebar-close" aria-label="Close menu">&#x2715;</button>
    <div class="sidebar-logo">DCO</div>
    <div class="sidebar-nav">
        <a href="index.php"    class="sidebar-link">Home</a>
        <a href="products.php" class="sidebar-link">Products</a>
        <a href="#"            class="sidebar-link">About</a>
        <a href="#"            class="sidebar-link">Contact</a>
    </div>
    <div class="sidebar-footer">
        <?php if ($isLoggedIn): ?>
            <div class="sidebar-user-info">
                <div class="sidebar-user-email"><?= htmlspecialchars($userEmail) ?></div>
            </div>
            <a href="logout.php" class="sidebar-auth-btn logout-btn">Logout</a>
        <?php else: ?>
            <a href="login.php"    class="sidebar-auth-btn outline">Login</a>
            <a href="register.php" class="sidebar-auth-btn">Register</a>
        <?php endif; ?>
    </div>
</nav>

<!-- ── Topbar ── -->
<header class="topbar nav-visible" id="topbar">
    <div id="topbar-left">
        <button class="hamburger" id="hamburger-btn" aria-label="Open menu">
            <span></span><span></span><span></span>
        </button>
    </div>

    <div id="topbar-center">
        <a href="index.php" class="topbar-logo">DCO</a>
    </div>

    <div id="topbar-right">
        <?php if ($isLoggedIn): ?>
            <span class="topbar-user-email"><?= htmlspecialchars($userEmail) ?></span>
            <span class="auth-sep">|</span>
            <a href="logout.php" class="auth-link logout-link">Logout</a>
        <?php else: ?>
            <a href="login.php"    class="auth-link">Login</a>
            <span class="auth-sep">|</span>
            <a href="register.php" class="auth-link">Register</a>
        <?php endif; ?>
        <span class="auth-sep">|</span>
        <button id="topbar-cart-btn" aria-label="Cart">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <path d="M16 10a4 4 0 01-8 0"/>
            </svg>
            <span id="cart-badge">0</span>
        </button>
    </div>
</header>

<!-- ── Cart Drawer ── -->
<aside id="cart-drawer" aria-label="Shopping cart">
    <div id="cart-drawer-header">
        <h2 id="cart-drawer-title">Cart</h2>
        <button id="cart-drawer-close" aria-label="Close cart">&#x2715;</button>
    </div>
    <div id="cart-items-list">
        <p class="cart-empty-msg" id="cart-empty-msg">Your cart is empty</p>
    </div>
    <div id="cart-drawer-footer">
        <div id="cart-total-row">
            <span id="cart-total-label">Total</span>
            <span id="cart-total-amount">₱ 0</span>
        </div>
        <button id="cart-checkout-btn">Proceed to Checkout</button>
    </div>
</aside>

<script>
/* ── Scroll-hide / hover-show topbar ── */
(function() {
    const topbar = document.getElementById('topbar');
    let lastY = 0;
    let ticking = false;
    let isHovered = false;

    topbar.addEventListener('mouseenter', () => {
        isHovered = true;
        topbar.classList.remove('nav-hidden');
        topbar.classList.add('nav-visible');
    });
    topbar.addEventListener('mouseleave', () => {
        isHovered = false;
    });

    window.addEventListener('scroll', () => {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(() => {
            const currentY = window.scrollY;
            if (currentY <= 10) {
                topbar.classList.remove('nav-hidden');
                topbar.classList.add('nav-visible');
            } else if (!isHovered) {
                if (currentY > lastY) {
                    // scrolling down — hide
                    topbar.classList.add('nav-hidden');
                    topbar.classList.remove('nav-visible');
                } else {
                    // scrolling up — show
                    topbar.classList.remove('nav-hidden');
                    topbar.classList.add('nav-visible');
                }
            }
            lastY = currentY;
            ticking = false;
        });
    });
})();

/* ── Sidebar toggle ── */
(function() {
    const hamburger = document.getElementById('hamburger-btn');
    const sidebar   = document.getElementById('sidebar');
    const overlay   = document.getElementById('nav-overlay');
    const closeBtn  = document.getElementById('sidebar-close');

    function openSidebar()  { sidebar.classList.add('open');  overlay.classList.add('active'); }
    function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('active'); }

    hamburger.addEventListener('click', openSidebar);
    closeBtn.addEventListener('click',  closeSidebar);
    overlay.addEventListener('click',   closeSidebar);
})();

/* ── Cart logic ── */
(function() {
    let cart = JSON.parse(localStorage.getItem('dco_cart') || '[]');

    const drawer      = document.getElementById('cart-drawer');
    const overlay     = document.getElementById('nav-overlay');
    const openBtn     = document.getElementById('topbar-cart-btn');
    const closeBtn    = document.getElementById('cart-drawer-close');
    const itemsList   = document.getElementById('cart-items-list');
    const emptyMsg    = document.getElementById('cart-empty-msg');
    const badge       = document.getElementById('cart-badge');
    const totalAmount = document.getElementById('cart-total-amount');

    const placeholderSVG = `<svg viewBox="0 0 40 40" fill="none" stroke="#171717" stroke-width="1.2"><rect x="4" y="8" width="32" height="26" rx="1"/><path d="M4 14h32"/><circle cx="14" cy="24" r="4"/><path d="M22 20h10M22 24h8M22 28h6"/></svg>`;

    function save() { localStorage.setItem('dco_cart', JSON.stringify(cart)); }

    function formatPrice(n) {
        return '₱ ' + Number(n).toLocaleString('en-PH');
    }

    function render() {
        // Badge
        const total = cart.reduce((s, i) => s + i.qty, 0);
        badge.textContent = total;
        badge.classList.toggle('visible', total > 0);

        // Items
        itemsList.innerHTML = '';
        if (cart.length === 0) {
            itemsList.appendChild(emptyMsg);
            emptyMsg.style.display = '';
            totalAmount.textContent = '₱ 0';
            return;
        }

        const grand = cart.reduce((s, i) => s + i.price * i.qty, 0);
        totalAmount.textContent = formatPrice(grand);

        cart.forEach((item, idx) => {
            const div = document.createElement('div');
            div.className = 'cart-item';
            div.innerHTML = `
                <div class="cart-item-thumb">${placeholderSVG}</div>
                <div class="cart-item-info">
                    <div class="cart-item-cat">${item.category}</div>
                    <div class="cart-item-name">${item.name}${item.qty > 1 ? ' ×' + item.qty : ''}</div>
                    <div class="cart-item-price">${formatPrice(item.price * item.qty)}</div>
                </div>
                <button class="cart-item-remove" data-idx="${idx}" aria-label="Remove">&#x2715;</button>
            `;
            itemsList.appendChild(div);
        });

        itemsList.querySelectorAll('.cart-item-remove').forEach(btn => {
            btn.addEventListener('click', () => {
                cart.splice(Number(btn.dataset.idx), 1);
                save(); render();
            });
        });
    }

    function openCart()  { drawer.classList.add('open');  overlay.classList.add('active'); }
    function closeCart() { drawer.classList.remove('open'); overlay.classList.remove('active'); }

    openBtn.addEventListener('click',  openCart);
    closeBtn.addEventListener('click', closeCart);
    overlay.addEventListener('click',  closeCart);

    // Expose addToCart globally for product buttons
    window.DCO_addToCart = function(name, category, price) {
        const existing = cart.find(i => i.name === name);
        if (existing) {
            existing.qty++;
        } else {
            cart.push({ name, category, price: parseFloat(price), qty: 1 });
        }
        save(); render();
        openCart();
    };

    render();
})();
</script>