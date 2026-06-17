<?php

require_once 'config/app.php';   // $pdo + session


define('ADMIN_EMAILS', ['dco@admin.com', 'owner@dco.com']); 

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$adminCheck = $pdo->prepare('SELECT email FROM users WHERE id = ?');
$adminCheck->execute([$_SESSION['user_id']]);
$adminUser = $adminCheck->fetch();

if (!$adminUser || !in_array($adminUser['email'], ADMIN_EMAILS)) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><title>403 – Forbidden</title>
    <style>body{font-family:sans-serif;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f7f7f5}
    h1{font-size:3rem;margin:0}p{color:#888}a{color:#171717}</style></head>
    <body><h1>403</h1><p>You do not have admin access.</p><a href="index.php">← Back to store</a></body></html>';
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    $orderId   = (int) $_POST['order_id'];
    $newStatus = $_POST['status'] ?? '';
    $allowed   = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if ($orderId && in_array($newStatus, $allowed)) {
        $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')
            ->execute([$newStatus, $orderId]);
    }
    header('Location: admin.php?tab=orders&updated=' . $orderId);
    exit;
}


$stats = [];

$stats['total_users']   = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$stats['total_orders']  = $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$stats['total_revenue'] = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
$stats['pending_orders']= $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();


$users = $pdo->query('
    SELECT u.id, u.email, u.created_at,
           COUNT(DISTINCT o.id)               AS order_count,
           COALESCE(SUM(o.total_amount), 0)   AS total_spent
    FROM users u
    LEFT JOIN orders o ON o.user_id = u.id AND o.status != "cancelled"
    GROUP BY u.id
    ORDER BY u.created_at DESC
')->fetchAll();


$orders = $pdo->query('
    SELECT o.id, o.total_amount, o.status, o.created_at,
           u.email
    FROM orders o
    JOIN users u ON u.id = o.user_id
    ORDER BY o.created_at DESC
')->fetchAll();


$orderItems = $pdo->query('
    SELECT oi.order_id, oi.quantity, oi.unit_price,
           p.name, p.category, p.image
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    ORDER BY oi.order_id DESC
')->fetchAll();


$itemsByOrder = [];
foreach ($orderItems as $oi) {
    $itemsByOrder[$oi['order_id']][] = $oi;
}


$products = $pdo->query('
    SELECT p.id, p.name, p.category, p.price, p.image,
           COALESCE(SUM(oi.quantity), 0) AS units_sold,
           COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS revenue
    FROM products p
    LEFT JOIN order_items oi ON oi.product_id = p.id
    LEFT JOIN orders o ON o.id = oi.order_id AND o.status != "cancelled"
    GROUP BY p.id
    ORDER BY units_sold DESC
')->fetchAll();


$cartSummary = $pdo->query('
    SELECT u.email, COUNT(*) AS items, SUM(ci.quantity) AS qty
    FROM cart_items ci
    JOIN users u ON u.id = ci.user_id
    GROUP BY ci.user_id
    ORDER BY qty DESC
')->fetchAll();

$activeTab = $_GET['tab'] ?? 'overview';
$updatedId = isset($_GET['updated']) ? (int)$_GET['updated'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin — DCO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --ink:    #171717;
            --mid:    #888;
            --faint:  #e8e8e8;
            --bg:     #f7f7f5;
            --white:  #ffffff;
            --green:  #16a34a;
            --amber:  #d97706;
            --red:    #dc2626;
            --blue:   #2563eb;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: var(--bg);
            color: var(--ink);
            min-height: 100vh;
        }

        /* ── Topbar ── */
        .admin-topbar {
            position: sticky;
            top: 0;
            z-index: 100;
            height: 56px;
            background: var(--ink);
            display: flex;
            align-items: center;
            padding: 0 32px;
            gap: 24px;
        }
        .admin-logo {
            font-family: 'Cormorant Garamond', serif;
            font-size: 26px;
            font-weight: 300;
            color: #fff;
            letter-spacing: 0.12em;
            text-decoration: none;
            margin-right: auto;
        }
        .admin-topbar-label {
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.35);
        }
        .admin-topbar a.nav-store {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.55);
            text-decoration: none;
            border: 1px solid rgba(255,255,255,0.15);
            padding: 6px 14px;
            transition: color 0.2s, border-color 0.2s;
        }
        .admin-topbar a.nav-store:hover { color: #fff; border-color: rgba(255,255,255,0.5); }

        /* ── Layout ── */
        .admin-shell {
            display: grid;
            grid-template-columns: 220px 1fr;
            min-height: calc(100vh - 56px);
        }

        /* ── Sidebar ── */
        .admin-sidebar {
            background: var(--white);
            border-right: 1px solid var(--faint);
            padding: 32px 0;
            position: sticky;
            top: 56px;
            height: calc(100vh - 56px);
            overflow-y: auto;
        }
        .sidebar-section-label {
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--mid);
            padding: 0 20px;
            margin-bottom: 8px;
        }
        .admin-sidebar nav { display: flex; flex-direction: column; gap: 2px; padding: 0 12px; }
        .admin-nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 0.06em;
            color: #444;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.15s, color 0.15s;
        }
        .admin-nav-link:hover { background: var(--bg); color: var(--ink); }
        .admin-nav-link.active { background: var(--ink); color: #fff; }
        .admin-nav-link svg { width: 14px; height: 14px; flex-shrink: 0; opacity: 0.7; }
        .admin-nav-link.active svg { opacity: 1; }
        .admin-sidebar .divider { border: none; border-top: 1px solid var(--faint); margin: 20px 20px; }

        /* ── Main content ── */
        .admin-main { padding: 40px 40px 80px; min-width: 0; }

        .page-heading {
            font-family: 'Cormorant Garamond', serif;
            font-size: 36px;
            font-weight: 300;
            letter-spacing: 0.04em;
            margin-bottom: 8px;
        }
        .page-sub {
            font-size: 10px;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--mid);
            margin-bottom: 40px;
        }

        /* ── Stat cards ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 48px;
        }
        .stat-card {
            background: var(--white);
            border: 1px solid var(--faint);
            padding: 24px;
        }
        .stat-label {
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--mid);
            margin-bottom: 10px;
        }
        .stat-value {
            font-family: 'Cormorant Garamond', serif;
            font-size: 42px;
            font-weight: 300;
            color: var(--ink);
            line-height: 1;
        }
        .stat-value.currency::before { content: '₱'; font-size: 20px; vertical-align: top; margin-top: 8px; display: inline-block; opacity: 0.4; margin-right: 2px; }

        /* ── Tables ── */
        .section-block { margin-bottom: 48px; }
        .section-block-header {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--faint);
        }
        .section-block-title {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--ink);
        }
        .section-block-count {
            font-size: 10px;
            letter-spacing: 0.1em;
            color: var(--mid);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .data-table th {
            text-align: left;
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--mid);
            padding: 8px 12px;
            border-bottom: 1px solid var(--faint);
            white-space: nowrap;
        }
        .data-table td {
            padding: 12px 12px;
            border-bottom: 1px solid #f2f2f2;
            vertical-align: middle;
            color: #333;
        }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover td { background: #fafafa; }
        .data-table tbody { background: var(--white); }

        /* ── Status badge ── */
        .status-badge {
            display: inline-block;
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 2px;
        }
        .status-pending    { background: #fef3c7; color: #92400e; }
        .status-processing { background: #dbeafe; color: #1e40af; }
        .status-shipped    { background: #ede9fe; color: #5b21b6; }
        .status-delivered  { background: #dcfce7; color: #166534; }
        .status-cancelled  { background: #fee2e2; color: #991b1b; }

        /* ── Product thumbnail ── */
        .prod-thumb {
            width: 40px;
            height: 40px;
            object-fit: cover;
            background: var(--bg);
            display: block;
        }
        .prod-thumb-placeholder {
            width: 40px;
            height: 40px;
            background: var(--faint);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ── Order expand ── */
        .order-row { cursor: pointer; }
        .order-items-row { display: none; }
        .order-items-row.open { display: table-row; }
        .order-items-inner {
            padding: 16px 20px 20px;
            background: var(--bg);
            border-left: 3px solid var(--ink);
        }
        .order-items-inner table { width: 100%; font-size: 11px; border-collapse: collapse; }
        .order-items-inner th { font-size: 9px; letter-spacing: 0.15em; text-transform: uppercase; color: var(--mid); padding: 4px 8px; text-align: left; }
        .order-items-inner td { padding: 6px 8px; color: #555; border-bottom: 1px solid #ebebeb; }

        /* ── Update form ── */
        .status-form { display: flex; align-items: center; gap: 8px; }
        .status-select {
            font-family: 'Montserrat', sans-serif;
            font-size: 10px;
            letter-spacing: 0.1em;
            padding: 5px 8px;
            border: 1px solid var(--faint);
            background: var(--white);
            color: var(--ink);
            cursor: pointer;
            outline: none;
        }
        .status-select:focus { border-color: var(--ink); }
        .btn-update {
            font-family: 'Montserrat', sans-serif;
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            padding: 5px 12px;
            background: var(--ink);
            color: #fff;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-update:hover { background: #333; }

        /* ── Flash ── */
        .flash-updated {
            display: inline-block;
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--green);
            padding: 2px 8px;
            background: #dcfce7;
            margin-left: 8px;
        }

        /* ── Empty ── */
        .empty-row td {
            text-align: center;
            padding: 40px;
            color: var(--mid);
            font-size: 11px;
            letter-spacing: 0.1em;
        }

        /* ── Tab visibility ── */
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* ── Revenue bar ── */
        .rev-bar-bg { background: var(--faint); height: 4px; border-radius: 2px; margin-top: 6px; min-width: 60px; }
        .rev-bar    { background: var(--ink); height: 4px; border-radius: 2px; }

        /* ── Responsive ── */
        @media (max-width: 900px) {
            .admin-shell { grid-template-columns: 1fr; }
            .admin-sidebar { display: none; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .admin-main { padding: 24px 16px 60px; }
        }
        @media (max-width: 500px) {
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>


<header class="admin-topbar">
    <a class="admin-logo" href="admin.php">DCO</a>
    <span class="admin-topbar-label">Admin</span>
    <a class="nav-store" href="index.php">← Store</a>
    <a class="nav-store" href="logout.php" style="border-color:rgba(220,38,38,0.4);color:rgba(220,38,38,0.8);">Logout</a>
</header>

<div class="admin-shell">

    
    <aside class="admin-sidebar">
        <div class="sidebar-section-label">Dashboard</div>
        <nav>
            <a class="admin-nav-link <?= $activeTab === 'overview'  ? 'active' : '' ?>" href="?tab=overview">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="1" width="6" height="6" rx=".5"/><rect x="9" y="1" width="6" height="6" rx=".5"/><rect x="1" y="9" width="6" height="6" rx=".5"/><rect x="9" y="9" width="6" height="6" rx=".5"/></svg>
                Overview
            </a>
            <a class="admin-nav-link <?= $activeTab === 'orders'    ? 'active' : '' ?>" href="?tab=orders">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 2h12v12H2z"/><path d="M5 6h6M5 9h4"/></svg>
                Orders
                <?php if ($stats['pending_orders'] > 0): ?>
                    <span style="margin-left:auto;background:#fef3c7;color:#92400e;font-size:9px;font-weight:700;padding:2px 6px;border-radius:2px;"><?= $stats['pending_orders'] ?></span>
                <?php endif; ?>
            </a>
            <a class="admin-nav-link <?= $activeTab === 'products'  ? 'active' : '' ?>" href="?tab=products">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="2" width="5" height="5" rx=".5"/><rect x="9" y="2" width="5" height="5" rx=".5"/><rect x="2" y="9" width="5" height="5" rx=".5"/><path d="M9 11.5h5M11.5 9v5"/></svg>
                Products
            </a>
            <a class="admin-nav-link <?= $activeTab === 'users'     ? 'active' : '' ?>" href="?tab=users">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="5" r="3"/><path d="M2 14c0-3 2.7-5 6-5s6 2 6 5"/></svg>
                Users
            </a>
            <a class="admin-nav-link <?= $activeTab === 'carts'     ? 'active' : '' ?>" href="?tab=carts">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 2h2l2 8h6l2-5H5"/><circle cx="7" cy="13" r="1"/><circle cx="12" cy="13" r="1"/></svg>
                Active Carts
            </a>
        </nav>
    </aside>

    <!-- ── Main ── -->
    <main class="admin-main">

        <!-- ══ OVERVIEW ══════════════════════════════════════════════════ -->
        <div class="tab-panel <?= $activeTab === 'overview' ? 'active' : '' ?>">
            <h1 class="page-heading">Overview</h1>
            <p class="page-sub">Store performance at a glance</p>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-value currency"><?= number_format($stats['total_revenue']) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Orders Placed</div>
                    <div class="stat-value"><?= $stats['total_orders'] ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Registered Users</div>
                    <div class="stat-value"><?= $stats['total_users'] ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Pending Orders</div>
                    <div class="stat-value" style="color:<?= $stats['pending_orders'] > 0 ? '#d97706' : 'inherit' ?>"><?= $stats['pending_orders'] ?></div>
                </div>
            </div>

            <!-- Recent orders -->
            <div class="section-block">
                <div class="section-block-header">
                    <span class="section-block-title">Recent Orders</span>
                    <a href="?tab=orders" style="font-size:10px;letter-spacing:.1em;color:var(--mid);text-decoration:none;">View all →</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $recentOrders = array_slice($orders, 0, 8); ?>
                        <?php if (empty($recentOrders)): ?>
                        <tr class="empty-row"><td colspan="5">No orders yet.</td></tr>
                        <?php else: foreach ($recentOrders as $o): ?>
                        <tr>
                            <td style="font-weight:600;">#<?= $o['id'] ?></td>
                            <td><?= htmlspecialchars($o['email']) ?></td>
                            <td>₱<?= number_format($o['total_amount']) ?></td>
                            <td><span class="status-badge status-<?= $o['status'] ?>"><?= $o['status'] ?></span></td>
                            <td style="color:var(--mid);"><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Top products -->
            <div class="section-block">
                <div class="section-block-header">
                    <span class="section-block-title">Top Products by Revenue</span>
                    <a href="?tab=products" style="font-size:10px;letter-spacing:.1em;color:var(--mid);text-decoration:none;">View all →</a>
                </div>
                <?php
                $topProducts = array_slice($products, 0, 5);
                $maxRev = max(1, max(array_column($topProducts ?: [['revenue'=>1]], 'revenue')));
                ?>
                <table class="data-table">
                    <thead><tr><th></th><th>Product</th><th>Units Sold</th><th>Revenue</th></tr></thead>
                    <tbody>
                        <?php if (empty($topProducts)): ?>
                        <tr class="empty-row"><td colspan="4">No sales data yet.</td></tr>
                        <?php else: foreach ($topProducts as $p): ?>
                        <tr>
                            <td style="width:44px;">
                                <?php if (!empty($p['image'])): ?>
                                    <img class="prod-thumb" src="<?= htmlspecialchars($p['image']) ?>" alt="">
                                <?php else: ?>
                                    <div class="prod-thumb-placeholder"></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight:500;"><?= htmlspecialchars($p['name']) ?></div>
                                <div style="font-size:10px;color:var(--mid);letter-spacing:.08em;"><?= htmlspecialchars($p['category']) ?></div>
                            </td>
                            <td><?= $p['units_sold'] ?></td>
                            <td>
                                ₱<?= number_format($p['revenue']) ?>
                                <div class="rev-bar-bg"><div class="rev-bar" style="width:<?= round($p['revenue'] / $maxRev * 100) ?>%"></div></div>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ══ ORDERS ════════════════════════════════════════════════════ -->
        <div class="tab-panel <?= $activeTab === 'orders' ? 'active' : '' ?>">
            <h1 class="page-heading">Orders</h1>
            <p class="page-sub"><?= count($orders) ?> total orders — click a row to see items</p>

            <?php if ($updatedId): ?>
                <p style="font-size:10px;letter-spacing:.12em;color:var(--green);margin-bottom:20px;">
                    ✓ Order #<?= $updatedId ?> status updated.
                </p>
            <?php endif; ?>

            <table class="data-table" id="orders-table">
                <thead>
                    <tr>
                        <th style="width:32px;"></th>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Update Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                    <tr class="empty-row"><td colspan="8">No orders yet.</td></tr>
                    <?php else: foreach ($orders as $o):
                        $itsItems = $itemsByOrder[$o['id']] ?? [];
                    ?>
                    <tr class="order-row" data-order-id="<?= $o['id'] ?>">
                        <td style="color:var(--mid);font-size:10px;">▸</td>
                        <td style="font-weight:600;">#<?= $o['id'] ?></td>
                        <td><?= htmlspecialchars($o['email']) ?></td>
                        <td style="color:var(--mid);"><?= count($itsItems) ?> item<?= count($itsItems) !== 1 ? 's' : '' ?></td>
                        <td style="font-weight:600;">₱<?= number_format($o['total_amount']) ?></td>
                        <td><span class="status-badge status-<?= $o['status'] ?>"><?= $o['status'] ?></span>
                            <?php if ($updatedId === (int)$o['id']): ?><span class="flash-updated">Updated</span><?php endif; ?>
                        </td>
                        <td style="color:var(--mid);white-space:nowrap;"><?= date('M j, Y · g:ia', strtotime($o['created_at'])) ?></td>
                        <td onclick="event.stopPropagation()">
                            <form method="POST" action="?tab=orders" class="status-form">
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                <select name="status" class="status-select">
                                    <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $s): ?>
                                        <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="update_order_status" class="btn-update">Save</button>
                            </form>
                        </td>
                    </tr>
                    <tr class="order-items-row" id="expand-<?= $o['id'] ?>">
                        <td colspan="8" style="padding:0;">
                            <div class="order-items-inner">
                                <table>
                                    <thead><tr><th></th><th>Product</th><th>Category</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
                                    <tbody>
                                        <?php if (empty($itsItems)): ?>
                                        <tr><td colspan="6" style="color:var(--mid);padding:8px;">No line items found.</td></tr>
                                        <?php else: foreach ($itsItems as $li): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($li['image'])): ?>
                                                    <img class="prod-thumb" src="<?= htmlspecialchars($li['image']) ?>" alt="" style="width:32px;height:32px;">
                                                <?php else: ?>
                                                    <div class="prod-thumb-placeholder" style="width:32px;height:32px;"></div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($li['name']) ?></td>
                                            <td style="color:var(--mid);"><?= htmlspecialchars($li['category']) ?></td>
                                            <td><?= $li['quantity'] ?></td>
                                            <td>₱<?= number_format($li['unit_price']) ?></td>
                                            <td style="font-weight:600;">₱<?= number_format($li['unit_price'] * $li['quantity']) ?></td>
                                        </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ══ PRODUCTS ═════════════════════════════════════════════════ -->
        <div class="tab-panel <?= $activeTab === 'products' ? 'active' : '' ?>">
            <h1 class="page-heading">Products</h1>
            <p class="page-sub"><?= count($products) ?> products — sorted by units sold</p>

            <?php
            $totalRevAll = max(1, max(array_column($products ?: [['revenue'=>1]], 'revenue')));
            ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Units Sold</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                    <tr class="empty-row"><td colspan="6">No products in database.</td></tr>
                    <?php else: foreach ($products as $p): ?>
                    <tr>
                        <td>
                            <?php if (!empty($p['image'])): ?>
                                <img class="prod-thumb" src="<?= htmlspecialchars($p['image']) ?>" alt="">
                            <?php else: ?>
                                <div class="prod-thumb-placeholder"></div>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight:500;"><?= htmlspecialchars($p['name']) ?></td>
                        <td style="color:var(--mid);font-size:10px;letter-spacing:.08em;text-transform:uppercase;"><?= htmlspecialchars($p['category']) ?></td>
                        <td>₱<?= number_format($p['price']) ?></td>
                        <td><?= $p['units_sold'] ?></td>
                        <td>
                            ₱<?= number_format($p['revenue']) ?>
                            <div class="rev-bar-bg"><div class="rev-bar" style="width:<?= round($p['revenue'] / $totalRevAll * 100) ?>%"></div></div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ══ USERS ═════════════════════════════════════════════════════ -->
        <div class="tab-panel <?= $activeTab === 'users' ? 'active' : '' ?>">
            <h1 class="page-heading">Users</h1>
            <p class="page-sub"><?= count($users) ?> registered accounts</p>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Orders</th>
                        <th>Total Spent</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr class="empty-row"><td colspan="5">No users yet.</td></tr>
                    <?php else: foreach ($users as $u): ?>
                    <tr>
                        <td style="color:var(--mid);"><?= $u['id'] ?></td>
                        <td style="font-weight:500;"><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= $u['order_count'] ?></td>
                        <td><?= $u['total_spent'] > 0 ? '₱' . number_format($u['total_spent']) : '—' ?></td>
                        <td style="color:var(--mid);"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ══ ACTIVE CARTS ══════════════════════════════════════════════ -->
        <div class="tab-panel <?= $activeTab === 'carts' ? 'active' : '' ?>">
            <h1 class="page-heading">Active Carts</h1>
            <p class="page-sub">Users with items in cart not yet checked out</p>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Distinct Items</th>
                        <th>Total Qty</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cartSummary)): ?>
                    <tr class="empty-row"><td colspan="3">No active carts right now.</td></tr>
                    <?php else: foreach ($cartSummary as $c): ?>
                    <tr>
                        <td style="font-weight:500;"><?= htmlspecialchars($c['email']) ?></td>
                        <td><?= $c['items'] ?></td>
                        <td><?= $c['qty'] ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>

<script>
// Expand order rows on click
document.querySelectorAll('.order-row').forEach(function(row) {
    row.addEventListener('click', function() {
        var id     = row.dataset.orderId;
        var expand = document.getElementById('expand-' + id);
        var arrow  = row.querySelector('td:first-child');
        if (!expand) return;
        var isOpen = expand.classList.toggle('open');
        arrow.textContent = isOpen ? '▾' : '▸';
    });
});
</script>

</body>
</html>
