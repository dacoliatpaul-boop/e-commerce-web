<?php

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 0);
    session_start();
}

$loggedIn   = !empty($_SESSION['user_id']);
$userEmail  = $loggedIn ? htmlspecialchars($_SESSION['email'] ?? '') : '';

// Admin check — mirrors the ADMIN_EMAILS list in admin.php
$_NAV_ADMIN_EMAILS = ['dco@admin.com', 'owner@dco.com'];
$isAdmin = $loggedIn && in_array($_SESSION['email'] ?? '', $_NAV_ADMIN_EMAILS, true);
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
        <a class="sidebar-link" href="contact.php">Contact</a>
    </div>
    <div class="sidebar-footer">
        <?php if ($loggedIn): ?>
            <div style="width:100%;">
                <a class="sidebar-user-info" href="profile.php" style="display:block;text-decoration:none;">
                    <p class="sidebar-user-email"><?= $userEmail ?></p>
                </a>
                <a class="sidebar-auth-btn" href="profile.php">My Profile</a>
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
            <a class="topbar-user-email" href="profile.php"><?= $userEmail ?></a>
            <span class="auth-sep">|</span>
            <?php if ($isAdmin): ?>
                <a class="auth-link admin-link" href="admin.php">Admin</a>
                <span class="auth-sep">|</span>
            <?php endif; ?>
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
   DCO CART — Server-side via cart.php API
   Cart is stored in the DB (cart_items table) per logged-in user.
   Unauthenticated users are redirected to login.php on add.
   ═══════════════════════════════════════════════════════════════ */

(function () {
    'use strict';

    // ── DOM refs ─────────────────────────────────────────────────
    var drawer      = document.getElementById('cart-drawer');
    var overlay     = document.getElementById('nav-overlay');
    var badge       = document.getElementById('cart-badge');
    var itemsList   = document.getElementById('cart-items-list');
    var emptyMsg    = document.getElementById('cart-empty-msg');
    var totalAmount = document.getElementById('cart-total-amount');

    // ── Badge ────────────────────────────────────────────────────
    function updateBadge(count) {
        count = count || 0;
        badge.textContent = count > 9 ? '9+' : (count > 0 ? count : '');
        if (count > 0) {
            badge.classList.add('visible');
        } else {
            badge.classList.remove('visible');
        }
    }

    // ── Render cart items from server data ───────────────────────
    function renderCart(items, total) {
        var nodes = itemsList.querySelectorAll('.cart-item');
        nodes.forEach(function (n) { n.parentNode.removeChild(n); });

        if (!items || items.length === 0) {
            emptyMsg.style.display = '';
            totalAmount.textContent = '\u20B1 0';
            return;
        }

        emptyMsg.style.display = 'none';

        items.forEach(function (item) {
            var el = document.createElement('div');
            el.className = 'cart-item';
            el.innerHTML =
                '<div class="cart-item-thumb">' +
                    (item.image
                        ? '<img src="' + escHtml(item.image) + '" alt="' + escHtml(item.name) + '" style="width:100%;height:100%;object-fit:cover;">'
                        : '<svg viewBox="0 0 40 40" fill="none" stroke="#171717" stroke-width="1.2"><rect x="4" y="8" width="32" height="26" rx="1"/><path d="M4 14h32"/><circle cx="14" cy="24" r="4"/><path d="M22 20h10M22 24h8M22 28h6"/></svg>'
                    ) +
                '</div>' +
                '<div class="cart-item-info">' +
                    '<p class="cart-item-name">' + escHtml(item.name) + '</p>' +
                    '<p class="cart-item-cat">'  + escHtml(item.category) + '</p>' +
                    '<p class="cart-item-price">\u20B1 ' + Number(item.price).toLocaleString() + '</p>' +
                    (item.quantity > 1 ? '<p class="cart-item-qty">Qty: ' + item.quantity + '</p>' : '') +
                '</div>' +
                '<button class="cart-item-remove" data-product-id="' + item.product_id + '" aria-label="Remove">\u00D7</button>';
            itemsList.appendChild(el);
        });

        totalAmount.textContent = '\u20B1 ' + Number(total).toLocaleString();
    }

    // ── Fetch cart from server ───────────────────────────────────
    function refreshCart() {
        fetch('cart.php?action=view')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    renderCart(data.cart, data.cart_total);
                    updateBadge(data.cart_count);
                }
            })
            .catch(function () { /* not logged in or network error */ });
    }

    // ── Remove item via API ──────────────────────────────────────
    itemsList.addEventListener('click', function (e) {
        var btn = e.target.closest('.cart-item-remove');
        if (!btn) return;
        var productId = btn.getAttribute('data-product-id');
        var fd = new FormData();
        fd.append('action', 'remove');
        fd.append('product_id', productId);

        fetch('cart.php', { method: 'POST', body: fd })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    renderCart(data.cart, data.cart_total);
                    updateBadge(data.cart_count);
                }
            });
    });

    // ── Public API — called by Add to Cart / Buy Now buttons ─────
    // Signature matches what products.php and index.php already pass:
    // DCO_addToCart(name, category, price, productId, qty)
    window.DCO_addToCart = function (name, category, price, productId, qty) {
        if (!productId) {
            console.error('DCO_addToCart: productId is required');
            return;
        }
        qty = qty || 1;

        var fd = new FormData();
        fd.append('action',     'add');
        fd.append('product_id', productId);
        fd.append('quantity',   qty);

        fetch('cart.php', { method: 'POST', body: fd })
            .then(function (res) {
                // Not logged in → redirect to login
                if (res.status === 401) {
                    window.location.href = 'login.php';
                    return null;
                }
                return res.json();
            })
            .then(function (data) {
                if (!data) return;
                if (data.success) {
                    renderCart(data.cart, data.cart_total);
                    updateBadge(data.cart_count);
                    openCart();
                } else {
                    alert(data.message || 'Could not add to cart.');
                }
            })
            .catch(function (err) {
                console.error('Cart error:', err);
            });
    };

    // ── Checkout button ──────────────────────────────────────────
    document.getElementById('cart-checkout-btn').addEventListener('click', function () {
        window.location.href = 'checkout.php';
    });

    // ── Open / close cart ────────────────────────────────────────
    function openCart() {
        drawer.classList.add('open');
        overlay.classList.add('active');
    }
    function closeCart() {
        drawer.classList.remove('open');
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
    var topbar  = document.getElementById('topbar');
    var lastY   = 0;
    var ticking = false;

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

    // ── Session-scoped cart ───────────────────────────────────────
    // sessionStorage is wiped on refresh AND on browser/tab close.
    // If the token is absent it means the browser was refreshed or
    

})();
</script>