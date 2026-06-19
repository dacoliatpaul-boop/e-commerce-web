<?php
require_once 'config/app.php';
include 'includes/nav.php';


if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$errors  = [];
$success = '';

// ── Fetch user details ────────────────────────────────────────────────────
try {
    $stmt = $pdo->prepare('SELECT id, email, full_name, phone, address, created_at FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $user = ['id' => $userId, 'email' => $_SESSION['email'] ?? '', 'full_name' => '', 'phone' => '', 'address' => '', 'created_at' => null];
}

// ── Handle profile update ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone    = trim($_POST['phone']     ?? '');
    $address  = trim($_POST['address']   ?? '');

    try {
        $stmt = $pdo->prepare('UPDATE users SET full_name = ?, phone = ?, address = ? WHERE id = ?');
        $stmt->execute([$fullName, $phone, $address, $userId]);
        $user['full_name'] = $fullName;
        $user['phone']     = $phone;
        $user['address']   = $address;
        $success = 'Profile updated.';
    } catch (PDOException $e) {
        // full_name / phone / address columns may not exist yet — handled gracefully
        $errors[] = 'Could not update profile. Make sure the full_name, phone, and address columns exist in the users table.';
    }
}

// ── Fetch orders ──────────────────────────────────────────────────────────
try {
    $stmt = $pdo->prepare('
        SELECT o.id, o.total_amount, o.status, o.created_at,
               o.shipping_address, o.payment_method,
               COUNT(oi.id) AS item_count
        FROM   orders o
        LEFT   JOIN order_items oi ON oi.order_id = o.id
        WHERE  o.user_id = ?
        GROUP  BY o.id
        ORDER  BY o.created_at DESC
    ');
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
    $errors[] = 'Could not load orders.';
}

// ── Fetch order items for expanded view ──────────────────────────────────
$orderItems = [];
if (!empty($orders)) {
    $orderIds   = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    try {
        $stmt = $pdo->prepare("
            SELECT oi.order_id, oi.quantity, oi.unit_price,
                   p.name, p.category, p.image
            FROM   order_items oi
            JOIN   products    p ON p.id = oi.product_id
            WHERE  oi.order_id IN ($placeholders)
            ORDER  BY oi.id ASC
        ");
        $stmt->execute($orderIds);
        foreach ($stmt->fetchAll() as $row) {
            $orderItems[$row['order_id']][] = $row;
        }
    } catch (PDOException $e) {
        // silently skip
    }
}

// ── Status meta ───────────────────────────────────────────────────────────
function statusMeta($status) {
    $map = [
        'pending'    => ['label' => 'Pending',     'class' => 'status-pending',    'icon' => '◷'],
        'confirmed'  => ['label' => 'Confirmed',   'class' => 'status-confirmed',  'icon' => '✓'],
        'processing' => ['label' => 'Processing',  'class' => 'status-processing', 'icon' => '⚙'],
        'shipped'    => ['label' => 'In Delivery', 'class' => 'status-shipped',    'icon' => '⟶'],
        'delivered'  => ['label' => 'Delivered',   'class' => 'status-delivered',  'icon' => '✓'],
        'cancelled'  => ['label' => 'Cancelled',   'class' => 'status-cancelled',  'icon' => '✕'],
    ];
    return $map[$status] ?? ['label' => ucfirst($status), 'class' => 'status-pending', 'icon' => '·'];
}

function paymentMethodLabel($method) {
    $map = [
        'bank_transfer' => 'Bank Transfer',
        'cod'           => 'Cash on Delivery',
        'gcash'         => 'GCash',
    ];
    return $map[$method] ?? ($method ? ucfirst(str_replace('_', ' ', $method)) : 'N/A');
}

$memberSince = $user['created_at'] ? date('F Y', strtotime($user['created_at'])) : 'N/A';
$totalOrders = count($orders);
$totalSpent  = array_sum(array_column($orders, 'total_amount'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <title>My Profile — DCO</title>
    <style>
        /* ── Layout ── */
        .profile-wrapper {
            max-width: 980px;
            margin: 0 auto;
            padding: 48px 24px 96px;
        }

        .profile-header {
            margin-bottom: 48px;
            border-bottom: 1px solid #e5e5e5;
            padding-bottom: 32px;
        }

        .profile-eyebrow {
            font-size: .7rem;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: #888;
            margin-bottom: 8px;
        }

        .profile-title {
            font-size: clamp(1.8rem, 4vw, 2.6rem);
            font-weight: 700;
            letter-spacing: -.02em;
            color: #171717;
            margin-bottom: 0;
        }

        /* ── Stats bar ── */
        .stats-bar {
            display: flex;
            gap: 0;
            margin-bottom: 56px;
            border: 1px solid #e5e5e5;
        }

        .stat-cell {
            flex: 1;
            padding: 24px 28px;
            border-right: 1px solid #e5e5e5;
        }

        .stat-cell:last-child { border-right: none; }

        .stat-label {
            font-size: .68rem;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: #888;
            margin-bottom: 6px;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #171717;
            letter-spacing: -.02em;
        }

        /* ── Two-column grid ── */
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1.6fr;
            gap: 32px;
            align-items: start;
        }

        @media (max-width: 700px) {
            .profile-grid { grid-template-columns: 1fr; }
            .stats-bar { flex-direction: column; }
            .stat-cell { border-right: none; border-bottom: 1px solid #e5e5e5; }
            .stat-cell:last-child { border-bottom: none; }
        }

        /* ── Panel ── */
        .panel {
            border: 1px solid #e5e5e5;
        }

        .panel-head {
            padding: 18px 24px;
            border-bottom: 1px solid #e5e5e5;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .panel-title {
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: #171717;
        }

        .panel-body { padding: 24px; }

        /* ── Form ── */
        .field-group {
            margin-bottom: 16px;
        }

        .field-group label {
            display: block;
            font-size: .65rem;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #888;
            margin-bottom: 6px;
        }

        .field-group input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #d4d4d4;
            font-size: .88rem;
            color: #171717;
            background: #fafafa;
            outline: none;
            box-sizing: border-box;
            transition: border-color .2s;
            font-family: inherit;
        }

        .field-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #d4d4d4;
            font-size: .88rem;
            color: #171717;
            background: #fafafa;
            outline: none;
            box-sizing: border-box;
            transition: border-color .2s;
            font-family: inherit;
            resize: vertical;
        }

        .field-group textarea:focus { border-color: #171717; background: #fff; }
        .field-group input:focus { border-color: #171717; background: #fff; }
        .field-group input[readonly] { background: #f5f5f5; color: #555; cursor: not-allowed; }

        .btn-save {
            width: 100%;
            padding: 13px;
            background: #171717;
            color: #fff;
            border: none;
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            cursor: pointer;
            margin-top: 8px;
            transition: background .2s;
            font-family: inherit;
        }

        .btn-save:hover { background: #333; }

        .success-msg {
            font-size: .8rem;
            color: #166534;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 10px 14px;
            margin-bottom: 16px;
            letter-spacing: .02em;
        }

        .error-msg {
            font-size: .8rem;
            color: #991b1b;
            background: #fef2f2;
            border: 1px solid #fecaca;
            padding: 10px 14px;
            margin-bottom: 16px;
        }

        /* ── Orders section ── */
        .orders-section { margin-top: 48px; }

        .orders-section .panel-head { margin-bottom: 0; }

        .order-row {
            border-bottom: 1px solid #f0f0f0;
        }

        .order-row:last-child { border-bottom: none; }

        .order-summary-row {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 20px 24px;
            cursor: pointer;
            transition: background .15s;
        }

        .order-summary-row:hover { background: #fafafa; }

        .order-num {
            font-size: .75rem;
            font-weight: 700;
            color: #171717;
            letter-spacing: .06em;
            min-width: 60px;
        }

        .order-date {
            font-size: .78rem;
            color: #888;
            flex: 1;
        }

        .order-amount {
            font-size: .88rem;
            font-weight: 700;
            color: #171717;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: .65rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 0;
        }

        .status-pending    { background: #fef9c3; color: #854d0e; }
        .status-confirmed  { background: #dbeafe; color: #1e40af; }
        .status-processing { background: #ede9fe; color: #5b21b6; }
        .status-shipped    { background: #e0f2fe; color: #075985; }
        .status-delivered  { background: #dcfce7; color: #166534; }
        .status-cancelled  { background: #fee2e2; color: #991b1b; }

        .order-chevron {
            font-size: .9rem;
            color: #aaa;
            transition: transform .2s;
        }

        .order-row.expanded .order-chevron { transform: rotate(90deg); }

        /* ── Order detail expand ── */
        .order-detail {
            display: none;
            padding: 0 24px 20px;
            background: #fafafa;
            border-top: 1px solid #f0f0f0;
        }

        .order-row.expanded .order-detail { display: block; }

        .order-item-line {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 0;
            border-bottom: 1px solid #ebebeb;
        }

        .order-item-line:last-child { border-bottom: none; }

        .order-item-thumb {
            width: 48px;
            height: 48px;
            background: #e5e5e5;
            flex-shrink: 0;
            overflow: hidden;
        }

        .order-item-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .order-item-name {
            flex: 1;
            font-size: .83rem;
            color: #171717;
        }

        .order-item-cat {
            font-size: .68rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #888;
        }

        .order-item-price {
            font-size: .83rem;
            font-weight: 700;
            color: #171717;
            white-space: nowrap;
        }

        /* ── Status timeline ── */
        .order-timeline {
            display: flex;
            gap: 0;
            margin-top: 16px;
            margin-bottom: 4px;
        }

        .timeline-step {
            flex: 1;
            text-align: center;
            position: relative;
            font-size: .6rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #bbb;
            padding-top: 28px;
        }

        .timeline-step::before {
            content: '';
            position: absolute;
            top: 9px;
            left: 50%;
            width: 12px;
            height: 12px;
            background: #e5e5e5;
            border-radius: 50%;
            transform: translateX(-50%);
        }

        .timeline-step::after {
            content: '';
            position: absolute;
            top: 14px;
            left: calc(50% + 6px);
            right: 0;
            height: 1px;
            background: #e5e5e5;
        }

        .timeline-step:last-child::after { display: none; }

        .timeline-step.done { color: #171717; }
        .timeline-step.done::before { background: #171717; }
        .timeline-step.done::after  { background: #171717; }
        .timeline-step.active { color: #171717; font-weight: 700; }
        .timeline-step.active::before { background: #171717; box-shadow: 0 0 0 3px #d1d5db; }

        .empty-orders {
            padding: 48px 24px;
            text-align: center;
            color: #888;
            font-size: .85rem;
            letter-spacing: .04em;
        }

        .empty-orders a {
            display: inline-block;
            margin-top: 16px;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #171717;
            text-decoration: none;
            border-bottom: 1px solid #171717;
            padding-bottom: 2px;
        }

        .logout-row {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #e5e5e5;
        }

        .btn-logout {
            display: block;
            width: 100%;
            padding: 13px;
            background: transparent;
            color: #991b1b;
            border: 1px solid #fecaca;
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: background .2s, color .2s;
            font-family: inherit;
        }

        .btn-logout:hover { background: #fef2f2; }

        /* ── Responsive ── */
        @media (max-width: 600px) {
            .profile-wrapper { padding: 32px 16px 72px; }
            .profile-header { margin-bottom: 32px; padding-bottom: 24px; }

            .panel-body { padding: 16px; }
            .panel-head { padding: 14px 16px; }

            .order-summary-row {
                flex-wrap: wrap;
                gap: 8px 12px;
                padding: 16px;
            }
            .order-date { flex-basis: 100%; order: 3; }
            .order-amount { margin-left: auto; }

            .order-detail { padding: 0 16px 16px; }

            .order-item-line { flex-wrap: wrap; gap: 8px 14px; }
            .order-item-name { flex-basis: calc(100% - 48px - 14px); }
            .order-item-price { margin-left: auto; }

            .order-timeline { flex-wrap: wrap; gap: 12px 0; }
            .timeline-step { flex: 1 1 33%; }
        }

        @media (max-width: 400px) {
            .stats-bar { font-size: .95em; }
            .stat-cell { padding: 18px 16px; }
            .order-num { min-width: auto; }
            .timeline-step { font-size: .55rem; }
        }
    </style>
</head>
<body>
<div class="topbar-spacer"></div>

<div class="profile-wrapper">

    <!-- Header -->
    <div class="profile-header">
        <p class="profile-eyebrow">My Account</p>
        <h1 class="profile-title">Profile</h1>
    </div>

    <!-- Stats bar -->
    <div class="stats-bar">
        <div class="stat-cell">
            <p class="stat-label">Member Since</p>
            <p class="stat-value"><?php echo $memberSince; ?></p>
        </div>
        <div class="stat-cell">
            <p class="stat-label">Total Orders</p>
            <p class="stat-value"><?php echo $totalOrders; ?></p>
        </div>
        <div class="stat-cell">
            <p class="stat-label">Total Spent</p>
            <p class="stat-value">&#8369; <?php echo number_format($totalSpent); ?></p>
        </div>
    </div>

    <!-- Profile grid -->
    <div class="profile-grid">

        <!-- Account details -->
        <div>
            <div class="panel">
                <div class="panel-head">
                    <span class="panel-title">Account Details</span>
                </div>
                <div class="panel-body">

                    <?php if ($success): ?>
                        <p class="success-msg"><?php echo htmlspecialchars($success); ?></p>
                    <?php endif; ?>
                    <?php foreach ($errors as $err): ?>
                        <p class="error-msg"><?php echo htmlspecialchars($err); ?></p>
                    <?php endforeach; ?>

                    <form method="POST" action="profile.php">
                        <div class="field-group">
                            <label>Email</label>
                            <input type="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" readonly>
                        </div>
                        <div class="field-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" placeholder="Your name"
                                value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                        </div>
                        <div class="field-group">
                            <label>Phone</label>
                            <input type="text" name="phone" placeholder="+63 9XX XXX XXXX"
                                value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        <div class="field-group">
                            <label>Address</label>
                            <textarea name="address" rows="3" placeholder="House/Unit No., Street, Barangay, City, Province"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" name="update_profile" class="btn-save">Save Changes</button>
                    </form>

                    <div class="logout-row">
                        <a class="btn-logout" href="logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders panel (right side, tall) -->
        <div>
            <div class="panel orders-section">
                <div class="panel-head">
                    <span class="panel-title">Order History</span>
                    <span style="font-size:.7rem;color:#888;"><?php echo $totalOrders; ?> order<?php echo $totalOrders !== 1 ? 's' : ''; ?></span>
                </div>

                <?php if (empty($orders)): ?>
                    <div class="empty-orders">
                        <p>No orders yet.</p>
                        <a href="products.php">Browse Products</a>
                    </div>
                <?php else: ?>

                <?php
                // Timeline steps mapping
                $timelineSteps = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
                $stepLabels    = ['Placed', 'Confirmed', 'Processing', 'In Delivery', 'Delivered'];

                foreach ($orders as $order):
                    $meta     = statusMeta($order['status']);
                    $date     = date('M d, Y', strtotime($order['created_at']));
                    $items    = $orderItems[$order['id']] ?? [];
                    $isCancelled = $order['status'] === 'cancelled';

                    // Determine timeline active index
                    $activeIdx = array_search($order['status'], $timelineSteps);
                    if ($activeIdx === false) $activeIdx = -1;
                ?>
                <div class="order-row" id="order-row-<?php echo $order['id']; ?>">
                    <div class="order-summary-row" onclick="toggleOrder(<?php echo $order['id']; ?>)">
                        <span class="order-num">#<?php echo $order['id']; ?></span>
                        <span class="order-date"><?php echo $date; ?> &middot; <?php echo $order['item_count']; ?> item<?php echo $order['item_count'] != 1 ? 's' : ''; ?></span>
                        <span class="order-amount">&#8369; <?php echo number_format($order['total_amount']); ?></span>
                        <span class="status-badge <?php echo $meta['class']; ?>"><?php echo $meta['icon']; ?> <?php echo $meta['label']; ?></span>
                        <span class="order-chevron">&#9656;</span>
                    </div>

                    <div class="order-detail" id="order-detail-<?php echo $order['id']; ?>">

                        <?php if (!$isCancelled): ?>
                        <!-- Timeline -->
                        <div class="order-timeline">
                            <?php foreach ($timelineSteps as $i => $step): ?>
                            <?php
                                $cls = '';
                                if ($i < $activeIdx)       $cls = 'done';
                                elseif ($i === $activeIdx)  $cls = 'done active';
                            ?>
                            <div class="timeline-step <?php echo $cls; ?>"><?php echo $stepLabels[$i]; ?></div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p style="font-size:.75rem;color:#991b1b;padding:12px 0 4px;letter-spacing:.04em;">This order was cancelled.</p>
                        <?php endif; ?>

                        <!-- Items -->
                        <?php foreach ($items as $item): ?>
                        <div class="order-item-line">
                            <div class="order-item-thumb">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>"
                                         alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <?php endif; ?>
                            </div>
                            <div style="flex:1;">
                                <p class="order-item-cat"><?php echo htmlspecialchars($item['category']); ?></p>
                                <p class="order-item-name"><?php echo htmlspecialchars($item['name']); ?></p>
                            </div>
                            <span class="order-item-price">
                                &#8369; <?php echo number_format($item['unit_price'] * $item['quantity']); ?>
                                <?php if ($item['quantity'] > 1): ?>
                                    <span style="font-weight:400;color:#888;font-size:.75rem;">&times;<?php echo $item['quantity']; ?></span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>

                        <!-- Delivery & Payment -->
                        <div style="display:flex;gap:24px;flex-wrap:wrap;padding:14px 0 4px;border-top:1px solid #f0f0f0;margin-top:8px;font-size:.78rem;">
                            <div style="flex:1;min-width:180px;">
                                <p style="color:#888;letter-spacing:.08em;text-transform:uppercase;font-size:.65rem;margin-bottom:4px;">Delivery Address</p>
                                <p style="color:#171717;"><?php echo $order['shipping_address'] ? nl2br(htmlspecialchars($order['shipping_address'])) : 'N/A'; ?></p>
                            </div>
                            <div>
                                <p style="color:#888;letter-spacing:.08em;text-transform:uppercase;font-size:.65rem;margin-bottom:4px;">Payment Method</p>
                                <p style="color:#171717;font-weight:600;"><?php echo htmlspecialchars(paymentMethodLabel($order['payment_method'])); ?></p>
                            </div>
                        </div>

                        <!-- Row total -->
                        <div style="display:flex;justify-content:flex-end;padding:14px 0 4px;font-size:.8rem;">
                            <span style="color:#888;margin-right:16px;">Order Total</span>
                            <span style="font-weight:700;color:#171717;">&#8369; <?php echo number_format($order['total_amount']); ?></span>
                        </div>

                    </div>
                </div>
                <?php endforeach; ?>

                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<script>
function toggleOrder(id) {
    var row    = document.getElementById('order-row-'    + id);
    var detail = document.getElementById('order-detail-' + id);
    row.classList.toggle('expanded');
}
</script>

</body>
</html>