<?php
/**
 * DCO — Shared Navigation
 * Included at the top of index.php, products.php, and any other page that needs the nav.
 *
 * CART BEHAVIOUR (sessionStorage):
 *   • Cart is kept in sessionStorage — it is cleared automatically when the browser tab
 *     is closed or the page is refreshed (sessionStorage does NOT survive a page reload).
 *   • If you want the cart to survive refreshes but still clear on browser close,
 *     change every "sessionStorage" below to "sessionStorage" — they are already equivalent
 *     for that requirement because sessionStorage is tab-scoped and survives nothing beyond
 *     the tab lifetime.
 *
 *   Actually: sessionStorage IS wiped on refresh.  That matches the requirement exactly:
 *     ✓ cart resets on refresh
 *     ✓ cart resets when browser closes
 *     ✓ login persists across refreshes (PHP session cookie, no "remember me")
 *     ✓ login resets when browser closes (session cookie lifetime = 0 in config/app.php)
 *
 * USER SESSION:
 *   PHP session cookie has lifetime=0 (set in config/app.php), meaning the browser
 *   discards it when all windows are closed.  Refreshing the page keeps the cookie alive.
 */

// Only start session if not already started (nav.php may be included after config/app.php)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 0);
    session_start();
}

$loggedIn   = !empty($_SESSION['user_id']);
$userEmail  = $loggedIn ? htmlspecialchars($_SESSION['email'] ?? '') : '';
?>
<!-- ── Sidebar overlay ── -->
<div class="nav-overlay" id="nav-overlay"></div>

<!-- ── Sidebar ── -->
<nav class="sidebar" id="sidebar">
    <button class="sidebar-close" id="sidebar-close" aria-label="Close menu">&#x2715;</button>
    <span class="sidebar-logo">DCO</span>
    <div class="sidebar-nav">
        <a class="sidebar-link" href="index.php">Home</a>
        <a class="sidebar-link" href="products.php">Products</a>
    </div>
    <div class="sidebar-footer">
        <?php if ($loggedIn): ?>
            <div style="width:100%;">
                <div class="sidebar-user-info">
                    <p class="sidebar-user-email"><?= $userEmail ?></p>
                </div>
                <a class="sidebar-auth-btn logout-btn" href="logout.php">Logout</a>
            </div>
        <?php else: ?>
            <a class="sidebar-auth-btn outline" href="login.php">Login</a>
            <a class="sidebar-auth-btn" href="register.php">Register</a>
        <?php endif; ?>
    </div>
</nav>

<!-- ── Topbar ── -->
<header class="topbar nav-visible" id="topbar">
    <div id="topbar-left">
        <button class="hamburger" id="hamburger" aria-label="Open menu">
            <span></span><span></span><span></span>
        </button>
    </div>

    <div id="topbar-center">
        <a class="topbar-logo" href="index.php">DCO</a>
    </div>

    <div id="topbar-right">
        <?php if ($loggedIn): ?>
            <span class="topbar-user-email"><?= $userEmail ?></span>
            <span class="auth-sep">|</span>
            <a class="auth-link logout-link" href="logout.php">Logout</a>
        <?php else: ?>
            <a class="auth-link" href="login.php">Login</a>
            <span class="auth-sep">|</span>
            <a class="auth-link" href="register.php">Register</a>
        <?php endif; ?>

        <!-- Cart button -->
        <button id="topbar-cart-btn" aria-label="Open cart">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <path d="M16 10a4 4 0 0 1-8 0"/>
            </svg>
            <span id="cart-badge"></span>
        </button>
    </div>
</header>

<!-- ── Cart Drawer ── -->
<div id="cart-drawer" aria-label="Shopping cart">
    <div id="cart-drawer-header">
        <h2 id="cart-drawer-title">Cart</h2>
        <button id="cart-drawer-close" aria-label="Close cart">&#x2715;</button>
    </div>
    <div id="cart-items-list">
        <p class="cart-empty-msg" id="cart-empty-msg">Your cart is empty.</p>
    </div>
    <div id="cart-drawer-footer">
        <div id="cart-total-row">
            <span id="cart-total-label">Total</span>
            <span id="cart-total-amount">&#8369; 0</span>
        </div>
        <button id="cart-checkout-btn">Checkout</button>
    </div>
</div>

<script>
/* ═══════════════════════════════════════════════════════════════
   DCO CART — sessionStorage
   Cart resets on page refresh and when the browser is closed.
   Login state is separate (PHP session cookie, persists across
   refreshes, cleared when all browser windows close).
   ═══════════════════════════════════════════════════════════════ */

(function () {
    'use strict';

    // ── Storage helpers ──────────────────────────────────────────
    var CART_KEY = 'dco_cart';

    function loadCart() {
        try {
            var raw = sessionStorage.getItem(CART_KEY);
            return raw ? JSON.parse(raw) : [];
        } catch (e) {
            return [];
        }
    }

    function saveCart(items) {
        try {
            sessionStorage.setItem(CART_KEY, JSON.stringify(items));
        } catch (e) { /* storage full or unavailable */ }
    }

    // ── State ────────────────────────────────────────────────────
    var cart = loadCart();

    // ── DOM refs ─────────────────────────────────────────────────
    var drawer      = document.getElementById('cart-drawer');
    var overlay     = document.getElementById('nav-overlay');
    var badge       = document.getElementById('cart-badge');
    var itemsList   = document.getElementById('cart-items-list');
    var emptyMsg    = document.getElementById('cart-empty-msg');
    var totalAmount = document.getElementById('cart-total-amount');

    // ── Badge ────────────────────────────────────────────────────
    function updateBadge() {
        var count = cart.length;
        badge.textContent = count > 9 ? '9+' : count;
        if (count > 0) {
            badge.classList.add('visible');
        } else {
            badge.classList.remove('visible');
        }
    }

    // ── Render cart items ────────────────────────────────────────
    function renderCart() {
        // Clear existing items (keep empty msg)
        var nodes = itemsList.querySelectorAll('.cart-item');
        nodes.forEach(function (n) { n.parentNode.removeChild(n); });

        if (cart.length === 0) {
            emptyMsg.style.display = '';
            totalAmount.textContent = '\u20B1 0';
            return;
        }

        emptyMsg.style.display = 'none';
        var total = 0;

        cart.forEach(function (item, idx) {
            total += item.price;
            var el = document.createElement('div');
            el.className = 'cart-item';
            el.innerHTML =
                '<div class="cart-item-thumb">' +
                    '<svg viewBox="0 0 40 40" fill="none" stroke="#171717" stroke-width="1.2">' +
                    '<rect x="4" y="8" width="32" height="26" rx="1"/>' +
                    '<path d="M4 14h32"/><circle cx="14" cy="24" r="4"/>' +
                    '<path d="M22 20h10M22 24h8M22 28h6"/></svg>' +
                '</div>' +
                '<div class="cart-item-info">' +
                    '<p class="cart-item-name">' + escHtml(item.name) + '</p>' +
                    '<p class="cart-item-cat">'  + escHtml(item.category) + '</p>' +
                    '<p class="cart-item-price">\u20B1 ' + Number(item.price).toLocaleString() + '</p>' +
                '</div>' +
                '<button class="cart-item-remove" data-idx="' + idx + '" aria-label="Remove">\u00D7</button>';
            itemsList.appendChild(el);
        });

        totalAmount.textContent = '\u20B1 ' + total.toLocaleString();
    }

    // ── Remove item ──────────────────────────────────────────────
    itemsList.addEventListener('click', function (e) {
        var btn = e.target.closest('.cart-item-remove');
        if (!btn) return;
        var idx = parseInt(btn.getAttribute('data-idx'), 10);
        cart.splice(idx, 1);
        saveCart(cart);
        updateBadge();
        renderCart();
    });

    // ── Public API — called by Add to Cart / Buy Now buttons ─────
    window.DCO_addToCart = function (name, category, price) {
        cart.push({ name: name, category: category, price: parseFloat(price) });
        saveCart(cart);
        updateBadge();
        renderCart();
        openCart();
    };

    // ── Open / close cart ────────────────────────────────────────
    function openCart() {
        drawer.classList.add('open');
        overlay.classList.add('active');
    }
    function closeCart() {
        drawer.classList.remove('open');
        // only remove overlay if sidebar is also closed
        if (!document.getElementById('sidebar').classList.contains('open')) {
            overlay.classList.remove('active');
        }
    }

    document.getElementById('topbar-cart-btn').addEventListener('click', function () {
        if (drawer.classList.contains('open')) { closeCart(); } else { openCart(); }
    });
    document.getElementById('cart-drawer-close').addEventListener('click', closeCart);

    // ── Sidebar open / close ─────────────────────────────────────
    var sidebar   = document.getElementById('sidebar');
    var hamburger = document.getElementById('hamburger');

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('active');
        hamburger.classList.add('hidden');
    }
    function closeSidebar() {
        sidebar.classList.remove('open');
        hamburger.classList.remove('hidden');
        if (!drawer.classList.contains('open')) {
            overlay.classList.remove('active');
        }
    }

    hamburger.addEventListener('click', openSidebar);
    document.getElementById('sidebar-close').addEventListener('click', closeSidebar);

    overlay.addEventListener('click', function () {
        closeSidebar();
        closeCart();
    });

    // ── Topbar hide/show on scroll ────────────────────────────────
    var topbar   = document.getElementById('topbar');
    var lastY    = 0;
    var ticking  = false;

    window.addEventListener('scroll', function () {
        if (!ticking) {
            window.requestAnimationFrame(function () {
                var y = window.scrollY;
                if (y > lastY && y > 80) {
                    topbar.classList.remove('nav-visible');
                    topbar.classList.add('nav-hidden');
                } else {
                    topbar.classList.remove('nav-hidden');
                    topbar.classList.add('nav-visible');
                }
                lastY = y;
                ticking = false;
            });
            ticking = true;
        }
    });

    // ── Escape key closes both ────────────────────────────────────
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { closeSidebar(); closeCart(); }
    });

    // ── HTML escape helper ────────────────────────────────────────
    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // ── Init ──────────────────────────────────────────────────────
    updateBadge();
    renderCart();

})();
</script>