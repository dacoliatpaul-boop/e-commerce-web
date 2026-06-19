<?php

require_once 'config/app.php';   


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

function paymentMethodLabel($method) {
    $map = [
        'bank_transfer' => 'Bank Transfer',
        'cod'           => 'Cash on Delivery',
        'gcash'         => 'GCash',
    ];
    return $map[$method] ?? ($method ? ucfirst(str_replace('_', ' ', $method)) : 'N/A');
}

function regenerateProductsConfig(PDO $pdo): bool {
    $rows = $pdo->query('SELECT id, name, category, price, image, featured, wide, stock FROM products WHERE deleted_at IS NULL ORDER BY id')->fetchAll();

    $lines = [];
    $lines[] = '<?php';
    $lines[] = '// Auto-generated from the products database table — see admin.php.';
    $lines[] = '// Manage products via Admin → Products instead of editing this file directly.';
    $lines[] = '$products = [';

    foreach ($rows as $r) {
        $price    = (float) $r['price'];
        $priceOut = (fmod($price, 1) === 0.0) ? (string) (int) $price : (string) $price;

        $lines[] = '    [';
        $lines[] = "        'id'       => " . (int) $r['id'] . ',';
        $lines[] = "        'name'     => '" . addslashes($r['name']) . "',";
        $lines[] = "        'category' => '" . addslashes($r['category']) . "',";
        $lines[] = "        'price'    => " . $priceOut . ',';
        $lines[] = "        'image'    => '" . addslashes($r['image'] ?? '') . "',";
        $lines[] = "        'featured' => " . ($r['featured'] ? 'true' : 'false') . ',';
        $lines[] = "        'wide'     => " . ($r['wide'] ? 'true' : 'false') . ',';
        $lines[] = "        'stock'    => " . (int) $r['stock'] . ',';
        $lines[] = '    ],';
    }

    $lines[] = '];';

    return @file_put_contents(__DIR__ . '/products_config.php', implode("\n", $lines) . "\n") !== false;
}

$errors    = [];
$formData  = ['id' => 0, 'name' => '', 'category' => 'Clothes', 'price' => '', 'stock' => '', 'featured' => false, 'wide' => false];
$openModal = false;


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


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $productId = (int) $_POST['product_id'];
    if ($productId) {
        try {
            $pdo->prepare('UPDATE products SET deleted_at = NOW() WHERE id = ?')->execute([$productId]);
            regenerateProductsConfig($pdo);
            header('Location: admin.php?tab=products&deleted=' . $productId);
            exit;
        } catch (PDOException $e) {
            header('Location: admin.php?tab=products&delete_error=' . $productId);
            exit;
        }
    }
    header('Location: admin.php?tab=products');
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_product'])) {
    $productId = (int) $_POST['product_id'];
    if ($productId) {
        $pdo->prepare('UPDATE products SET deleted_at = NULL WHERE id = ?')->execute([$productId]);
        regenerateProductsConfig($pdo);
        header('Location: admin.php?tab=products&view=archived&restored=' . $productId);
        exit;
    }
    header('Location: admin.php?tab=products&view=archived');
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product_permanent'])) {
    $productId = (int) $_POST['product_id'];
    if ($productId) {
        try {
            $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$productId]);
            regenerateProductsConfig($pdo);
            header('Location: admin.php?tab=products&view=archived&perm_deleted=' . $productId);
            exit;
        } catch (PDOException $e) {
            header('Location: admin.php?tab=products&view=archived&perm_delete_error=' . $productId);
            exit;
        }
    }
    header('Location: admin.php?tab=products&view=archived');
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_message'])) {
    $messageId = (int) $_POST['message_id'];
    if ($messageId) {
        $pdo->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$messageId]);
    }
    header('Location: admin.php?tab=messages&deleted_message=' . $messageId);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $formData['id']       = (int) ($_POST['product_id'] ?? 0);
    $formData['name']     = trim($_POST['name'] ?? '');
    $formData['category'] = trim($_POST['category'] ?? '');
    $formData['price']    = trim($_POST['price'] ?? '');
    $formData['stock']    = trim($_POST['stock'] ?? '');
    $formData['featured'] = isset($_POST['featured']);
    $formData['wide']     = isset($_POST['wide']);

    $priceVal = (float) $formData['price'];
    $stockVal = (int) $formData['stock'];

    if ($formData['name'] === '')     $errors[] = 'Product name is required.';
    if ($formData['category'] === '') $errors[] = 'Category is required.';
    if ($priceVal <= 0)                $errors[] = 'Price must be greater than 0.';
    if ($formData['stock'] === '' || $stockVal < 0) $errors[] = 'Stock must be a whole number of 0 or more.';

    $imagePath = null; // null = leave image unchanged
    if (empty($errors) && !empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext        = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            $errors[] = 'Image must be JPG, PNG, GIF, or WEBP.';
        } else {
            $safeName = trim(preg_replace('/[^a-z0-9]+/i', '-', strtolower($formData['name'])), '-');
            $fileName = ($safeName ?: 'product') . '-' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], __DIR__ . '/img/' . $fileName)) {
                $imagePath = 'img/' . $fileName;
            } else {
                $errors[] = 'Could not save the uploaded image — check that the img/ folder is writable.';
            }
        }
    }

    if (empty($errors)) {
        try {
            if ($formData['id']) {
                if ($imagePath !== null) {
                    $pdo->prepare('UPDATE products SET name=?, category=?, price=?, image=?, featured=?, wide=?, stock=? WHERE id=?')
                        ->execute([$formData['name'], $formData['category'], $priceVal, $imagePath, (int)$formData['featured'], (int)$formData['wide'], $stockVal, $formData['id']]);
                } else {
                    $pdo->prepare('UPDATE products SET name=?, category=?, price=?, featured=?, wide=?, stock=? WHERE id=?')
                        ->execute([$formData['name'], $formData['category'], $priceVal, (int)$formData['featured'], (int)$formData['wide'], $stockVal, $formData['id']]);
                }
                $flashParam = 'product_updated=' . $formData['id'];
            } else {
                $stmt = $pdo->prepare('INSERT INTO products (name, category, price, image, featured, wide, stock) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$formData['name'], $formData['category'], $priceVal, $imagePath ?? '', (int)$formData['featured'], (int)$formData['wide'], $stockVal]);
                $flashParam = 'product_added=' . $pdo->lastInsertId();
            }

            $configOk = regenerateProductsConfig($pdo);
            header('Location: admin.php?tab=products&' . $flashParam . ($configOk ? '' : '&config_warning=1'));
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        $openModal = true;
    }
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
           o.shipping_address, o.payment_method,
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
    SELECT p.id, p.name, p.category, p.price, p.image, p.featured, p.wide, p.stock,
           COALESCE(SUM(oi.quantity), 0) AS units_sold,
           COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS revenue
    FROM products p
    LEFT JOIN order_items oi ON oi.product_id = p.id
    LEFT JOIN orders o ON o.id = oi.order_id AND o.status != "cancelled"
    WHERE p.deleted_at IS NULL
    GROUP BY p.id
    ORDER BY units_sold DESC
')->fetchAll();

$archivedProducts = $pdo->query('
    SELECT id, name, category, price, image, featured, wide, stock, deleted_at
    FROM products
    WHERE deleted_at IS NOT NULL
    ORDER BY deleted_at DESC
')->fetchAll();


$cartSummary = $pdo->query('
    SELECT u.email, COUNT(*) AS items, SUM(ci.quantity) AS qty
    FROM cart_items ci
    JOIN users u ON u.id = ci.user_id
    GROUP BY ci.user_id
    ORDER BY qty DESC
')->fetchAll();


$messages = $pdo->query('
    SELECT id, name, email, subject, message, created_at
    FROM contact_messages
    ORDER BY created_at DESC
')->fetchAll();

$activeTab = $_GET['tab'] ?? 'overview';
$productsView = ($_GET['view'] ?? '') === 'archived' ? 'archived' : 'active';
$updatedId = isset($_GET['updated']) ? (int)$_GET['updated'] : 0;
$deletedId = isset($_GET['deleted']) ? (int)$_GET['deleted'] : 0;
$deleteErrorId    = isset($_GET['delete_error']) ? (int)$_GET['delete_error'] : 0;
$restoredId       = isset($_GET['restored']) ? (int)$_GET['restored'] : 0;
$permDeletedId    = isset($_GET['perm_deleted']) ? (int)$_GET['perm_deleted'] : 0;
$permDeleteErrorId = isset($_GET['perm_delete_error']) ? (int)$_GET['perm_delete_error'] : 0;
$productAddedId   = isset($_GET['product_added']) ? (int)$_GET['product_added'] : 0;
$productUpdatedId = isset($_GET['product_updated']) ? (int)$_GET['product_updated'] : 0;
$configWarning     = isset($_GET['config_warning']);
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

        /* ── Stock badge ── */
        .stock-badge {
            display: inline-block;
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 2px;
            white-space: nowrap;
        }
        .stock-ok   { background: #dcfce7; color: #166534; }
        .stock-low  { background: #fef3c7; color: #92400e; }
        .stock-out  { background: #fee2e2; color: #991b1b; }

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

        .btn-delete {
            font-family: 'Montserrat', sans-serif;
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            padding: 5px 12px;
            background: var(--red);
            color: #fff;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-delete:hover { background: #b91c1c; }

        /* ── Product Modal ── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: var(--white);
            padding: 32px;
            max-width: 440px;
            width: 100%;
            max-height: 85vh;
            overflow-y: auto;
            position: relative;
        }
        .modal-box h2 {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-size: 26px;
            margin-bottom: 22px;
        }
        .modal-close {
            position: absolute;
            top: 16px; right: 16px;
            background: none;
            border: none;
            font-size: 16px;
            cursor: pointer;
            color: var(--mid);
        }
        .pf-row { margin-bottom: 16px; display: flex; flex-direction: column; gap: 6px; }
        .pf-row label {
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--mid);
        }
        .pf-row input[type="text"],
        .pf-row input[type="number"],
        .pf-row input[type="file"],
        .pf-row select {
            padding: 9px 12px;
            border: 1px solid var(--faint);
            font-size: 13px;
            font-family: inherit;
            background: var(--white);
            color: var(--ink);
        }
        .pf-checkboxes { flex-direction: row; gap: 24px; }
        .pf-checkboxes label {
            flex-direction: row;
            align-items: center;
            gap: 6px;
            display: flex;
            text-transform: none;
            font-weight: 500;
            color: var(--ink);
            font-size: 12px;
            letter-spacing: normal;
        }
        .pf-current-image { font-size: 10px; color: var(--mid); margin: 4px 0 0; }
        .pf-error {
            font-size: 11px;
            color: var(--red);
            margin-bottom: 10px;
        }

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

            /* Sidebar becomes a horizontal, scrollable tab strip instead of
               disappearing — tabs are plain links so this keeps every
               section reachable without JS. */
            .admin-sidebar {
                position: sticky;
                top: 56px;
                height: auto;
                width: 100%;
                padding: 12px 0;
                border-right: none;
                border-bottom: 1px solid var(--faint);
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                z-index: 90;
            }
            .sidebar-section-label { display: none; }
            .admin-sidebar nav {
                flex-direction: row;
                gap: 6px;
                padding: 0 16px;
                width: max-content;
            }
            .admin-nav-link { padding: 8px 14px; white-space: nowrap; }
            .admin-sidebar .divider { display: none; }

            .admin-main { padding: 24px 16px 60px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }

            /* Tables scroll horizontally instead of squeezing columns */
            .section-block { overflow-x: auto; -webkit-overflow-scrolling: touch; }
            .data-table { min-width: 560px; }
        }
        @media (max-width: 500px) {
            .stats-grid { grid-template-columns: 1fr; }
            .admin-topbar { padding: 0 16px; gap: 14px; }
            .admin-topbar-label { display: none; }
            .page-heading { font-size: 28px; }

            .section-block-header { flex-wrap: wrap; gap: 4px; }
            .status-form { flex-wrap: wrap; }
            .modal-box { padding: 24px 20px; max-height: 90vh; }
            .pf-checkboxes { flex-wrap: wrap; gap: 12px; }
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
            <a class="admin-nav-link <?= $activeTab === 'messages'  ? 'active' : '' ?>" href="?tab=messages">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3h12v9H7l-3 2.5V12H2z"/></svg>
                Messages
                <?php if (count($messages) > 0): ?>
                    <span style="margin-left:auto;background:#fef3c7;color:#92400e;font-size:9px;font-weight:700;padding:2px 6px;border-radius:2px;"><?= count($messages) ?></span>
                <?php endif; ?>
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
                        <th>Address</th>
                        <th>Payment</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Update Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                    <tr class="empty-row"><td colspan="10">No orders yet.</td></tr>
                    <?php else: foreach ($orders as $o):
                        $itsItems = $itemsByOrder[$o['id']] ?? [];
                    ?>
                    <tr class="order-row" data-order-id="<?= $o['id'] ?>">
                        <td style="color:var(--mid);font-size:10px;">▸</td>
                        <td style="font-weight:600;">#<?= $o['id'] ?></td>
                        <td><?= htmlspecialchars($o['email']) ?></td>
                        <td style="color:var(--mid);"><?= count($itsItems) ?> item<?= count($itsItems) !== 1 ? 's' : '' ?></td>
                        <td style="color:var(--mid);max-width:220px;white-space:normal;font-size:11px;line-height:1.4;">
                            <?= $o['shipping_address'] ? nl2br(htmlspecialchars($o['shipping_address'])) : '<span style="color:#ccc;">—</span>' ?>
                        </td>
                        <td style="white-space:nowrap;"><?= htmlspecialchars(paymentMethodLabel($o['payment_method'])) ?></td>
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
                        <td colspan="10" style="padding:0;">
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
            <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap;">
                <div>
                    <h1 class="page-heading">Products</h1>
                    <p class="page-sub"><?= count($products) ?> products — sorted by units sold</p>
                </div>
                <button type="button" class="btn-update" id="add-product-btn" style="padding:9px 18px;">+ Add Product</button>
            </div>

            <div style="display:flex;gap:8px;margin-bottom:20px;">
                <a href="?tab=products" style="padding:7px 14px;font-size:10px;letter-spacing:.1em;text-transform:uppercase;text-decoration:none;border-radius:3px;<?= $productsView === 'active' ? 'background:#171717;color:#fff;' : 'background:#f1f1ef;color:var(--mid);' ?>">
                    Active (<?= count($products) ?>)
                </a>
                <a href="?tab=products&view=archived" style="padding:7px 14px;font-size:10px;letter-spacing:.1em;text-transform:uppercase;text-decoration:none;border-radius:3px;<?= $productsView === 'archived' ? 'background:#171717;color:#fff;' : 'background:#f1f1ef;color:var(--mid);' ?>">
                    Archived (<?= count($archivedProducts) ?>)
                </a>
            </div>

            <?php if ($deletedId): ?>
                <p style="font-size:10px;letter-spacing:.12em;color:var(--green);margin-bottom:20px;">
                    ✓ Product #<?= $deletedId ?> archived. You can restore it from the Archived tab.
                </p>
            <?php endif; ?>
            <?php if ($deleteErrorId): ?>
                <p style="font-size:10px;letter-spacing:.12em;color:var(--red);margin-bottom:20px;">
                    ✕ Could not archive product #<?= $deleteErrorId ?>.
                </p>
            <?php endif; ?>
            <?php if ($restoredId): ?>
                <p style="font-size:10px;letter-spacing:.12em;color:var(--green);margin-bottom:20px;">
                    ✓ Product #<?= $restoredId ?> restored.
                </p>
            <?php endif; ?>
            <?php if ($permDeletedId): ?>
                <p style="font-size:10px;letter-spacing:.12em;color:var(--green);margin-bottom:20px;">
                    ✓ Product #<?= $permDeletedId ?> permanently deleted.
                </p>
            <?php endif; ?>
            <?php if ($permDeleteErrorId): ?>
                <p style="font-size:10px;letter-spacing:.12em;color:var(--red);margin-bottom:20px;">
                    ✕ Could not permanently delete product #<?= $permDeleteErrorId ?> — it's still referenced by existing orders or carts.
                </p>
            <?php endif; ?>
            <?php if ($productAddedId): ?>
                <p style="font-size:10px;letter-spacing:.12em;color:var(--green);margin-bottom:20px;">
                    ✓ Product #<?= $productAddedId ?> added.
                </p>
            <?php endif; ?>
            <?php if ($productUpdatedId): ?>
                <p style="font-size:10px;letter-spacing:.12em;color:var(--green);margin-bottom:20px;">
                    ✓ Product #<?= $productUpdatedId ?> updated.
                </p>
            <?php endif; ?>
            <?php if ($configWarning): ?>
                <p style="font-size:10px;letter-spacing:.12em;color:var(--amber);margin-bottom:20px;">
                    ⚠ Saved to the database, but products_config.php could not be rewritten automatically — check file permissions.
                </p>
            <?php endif; ?>

            <?php if ($productsView === 'archived'): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Archived</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($archivedProducts)): ?>
                    <tr class="empty-row"><td colspan="6">No archived products.</td></tr>
                    <?php else: foreach ($archivedProducts as $p): ?>
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
                        <td style="color:var(--mid);font-size:11px;"><?= htmlspecialchars($p['deleted_at']) ?></td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <form method="POST" action="?tab=products&view=archived">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <button type="submit" name="restore_product" class="btn-update" style="padding:7px 12px;">Restore</button>
                                </form>
                                <form method="POST" action="?tab=products&view=archived"
                                      onsubmit="return confirm('Permanently delete &quot;<?= htmlspecialchars(addslashes($p['name'])) ?>&quot;? This cannot be undone.');">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <button type="submit" name="delete_product_permanent" class="btn-delete">Delete Permanently</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
            <?php else: ?>

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
                        <th>Stock</th>
                        <th>Units Sold</th>
                        <th>Revenue</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                    <tr class="empty-row"><td colspan="8">No products in database.</td></tr>
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
                        <td>
                            <?php if ((int)$p['stock'] <= 0): ?>
                                <span class="stock-badge stock-out">Out of stock</span>
                            <?php elseif ((int)$p['stock'] <= 5): ?>
                                <span class="stock-badge stock-low"><?= (int)$p['stock'] ?> left</span>
                            <?php else: ?>
                                <span class="stock-badge stock-ok"><?= (int)$p['stock'] ?> in stock</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $p['units_sold'] ?></td>
                        <td>
                            ₱<?= number_format($p['revenue']) ?>
                            <div class="rev-bar-bg"><div class="rev-bar" style="width:<?= round($p['revenue'] / $totalRevAll * 100) ?>%"></div></div>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <button type="button" class="btn-update btn-edit"
                                    data-id="<?= $p['id'] ?>"
                                    data-name="<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>"
                                    data-category="<?= htmlspecialchars($p['category'], ENT_QUOTES) ?>"
                                    data-price="<?= htmlspecialchars($p['price'], ENT_QUOTES) ?>"
                                    data-stock="<?= htmlspecialchars($p['stock'], ENT_QUOTES) ?>"
                                    data-image="<?= htmlspecialchars($p['image'] ?? '', ENT_QUOTES) ?>"
                                    data-featured="<?= !empty($p['featured']) ? '1' : '0' ?>"
                                    data-wide="<?= !empty($p['wide']) ? '1' : '0' ?>">Edit</button>
                                <form method="POST" action="?tab=products"
                                      onsubmit="return confirm('Archive &quot;<?= htmlspecialchars(addslashes($p['name'])) ?>&quot;? You can restore it later from the Archived tab.');">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <button type="submit" name="delete_product" class="btn-delete">Archive</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <!-- ── Add / Edit Product Modal ── -->
            <div class="modal-overlay" id="product-modal">
                <div class="modal-box">
                    <button type="button" class="modal-close" id="close-product-modal">&#x2715;</button>
                    <h2 id="product-modal-title">Add Product</h2>

                    <?php foreach ($errors as $err): ?>
                        <p class="pf-error">⚠ <?= htmlspecialchars($err) ?></p>
                    <?php endforeach; ?>

                    <form method="POST" action="?tab=products" enctype="multipart/form-data" id="product-form">
                        <input type="hidden" name="product_id" id="pf-id" value="<?= $formData['id'] ?: '' ?>">

                        <div class="pf-row">
                            <label for="pf-name">Name</label>
                            <input type="text" name="name" id="pf-name" value="<?= htmlspecialchars($formData['name']) ?>" required>
                        </div>

                        <div class="pf-row">
                            <label for="pf-category">Category</label>
                            <select name="category" id="pf-category" required>
                                <?php foreach (['Clothes', 'Accessories', 'Devices', 'Fragrance'] as $cat): ?>
                                    <option value="<?= $cat ?>" <?= $formData['category'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="pf-row">
                            <label for="pf-price">Price (₱)</label>
                            <input type="number" name="price" id="pf-price" min="0" step="0.01" value="<?= htmlspecialchars($formData['price']) ?>" required>
                        </div>

                        <div class="pf-row">
                            <label for="pf-stock">Stock</label>
                            <input type="number" name="stock" id="pf-stock" min="0" step="1" value="<?= htmlspecialchars($formData['stock']) ?>" required>
                        </div>

                        <div class="pf-row">
                            <label for="pf-image-file">Image</label>
                            <input type="file" name="image_file" id="pf-image-file" accept="image/*">
                            <p class="pf-current-image" id="pf-current-image"></p>
                        </div>

                        <div class="pf-row pf-checkboxes">
                            <label><input type="checkbox" name="featured" id="pf-featured" value="1" <?= $formData['featured'] ? 'checked' : '' ?>> Featured</label>
                            <label><input type="checkbox" name="wide" id="pf-wide" value="1" <?= $formData['wide'] ? 'checked' : '' ?>> Wide card</label>
                        </div>

                        <button type="submit" name="save_product" class="btn-update" id="pf-submit" style="width:100%;padding:11px;">Add Product</button>
                    </form>
                </div>
            </div>
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

        <!-- ══ MESSAGES ══════════════════════════════════════════════════ -->
        <div class="tab-panel <?= $activeTab === 'messages' ? 'active' : '' ?>">
            <h1 class="page-heading">Messages</h1>
            <p class="page-sub"><?= count($messages) ?> messages from the contact form</p>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($messages)): ?>
                    <tr class="empty-row"><td colspan="6">No messages yet.</td></tr>
                    <?php else: foreach ($messages as $m): ?>
                    <tr>
                        <td style="color:var(--mid);white-space:nowrap;"><?= date('M j, Y', strtotime($m['created_at'])) ?></td>
                        <td style="font-weight:500;white-space:nowrap;"><?= htmlspecialchars($m['name']) ?></td>
                        <td><a href="mailto:<?= htmlspecialchars($m['email']) ?>" style="color:var(--blue);text-decoration:none;"><?= htmlspecialchars($m['email']) ?></a></td>
                        <td><?= htmlspecialchars($m['subject'] ?: '—') ?></td>
                        <td style="white-space:pre-wrap;max-width:360px;"><?= htmlspecialchars($m['message']) ?></td>
                        <td>
                            <form method="POST" action="?tab=messages"
                                  onsubmit="return confirm('Delete this message? This cannot be undone.');">
                                <input type="hidden" name="message_id" value="<?= $m['id'] ?>">
                                <button type="submit" name="delete_message" class="btn-delete">Delete</button>
                            </form>
                        </td>
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

// ── Add / Edit Product Modal ──────────────────────────────────────
(function () {
    var modal      = document.getElementById('product-modal');
    var form       = document.getElementById('product-form');
    var titleEl    = document.getElementById('product-modal-title');
    var submitBtn  = document.getElementById('pf-submit');
    var idField    = document.getElementById('pf-id');
    var nameField  = document.getElementById('pf-name');
    var catField   = document.getElementById('pf-category');
    var priceField = document.getElementById('pf-price');
    var stockField = document.getElementById('pf-stock');
    var featField  = document.getElementById('pf-featured');
    var wideField  = document.getElementById('pf-wide');
    var imgInfo    = document.getElementById('pf-current-image');

    function openModal()  { modal.classList.add('active'); }
    function closeModal() { modal.classList.remove('active'); }

    function showModalWithData(data) {
        data = data || {};
        form.reset();
        idField.value     = data.id || '';
        nameField.value   = data.name || '';
        catField.value    = data.category || 'Clothes';
        priceField.value  = data.price || '';
        stockField.value  = (data.stock !== undefined && data.stock !== null && data.stock !== '') ? data.stock : '0';
        featField.checked = data.featured === '1';
        wideField.checked = data.wide === '1';

        if (data.id) {
            titleEl.textContent   = 'Edit Product';
            submitBtn.textContent = 'Save Changes';
            imgInfo.textContent   = data.image ? ('Current image: ' + data.image + ' — choose a file to replace it') : 'No current image';
        } else {
            titleEl.textContent   = 'Add Product';
            submitBtn.textContent = 'Add Product';
            imgInfo.textContent   = '';
        }
        openModal();
    }

    document.getElementById('add-product-btn').addEventListener('click', function () {
        showModalWithData({});
    });
    document.querySelectorAll('.btn-edit').forEach(function (btn) {
        btn.addEventListener('click', function () { showModalWithData(btn.dataset); });
    });
    document.getElementById('close-product-modal').addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });

    <?php if ($openModal): ?>
    showModalWithData({
        id:       '<?= addslashes((string)($formData['id'] ?: '')) ?>',
        name:     '<?= addslashes($formData['name']) ?>',
        category: '<?= addslashes($formData['category']) ?>',
        price:    '<?= addslashes((string)$formData['price']) ?>',
        stock:    '<?= addslashes((string)$formData['stock']) ?>',
        featured: '<?= $formData['featured'] ? '1' : '0' ?>',
        wide:     '<?= $formData['wide'] ? '1' : '0' ?>'
    });
    <?php endif; ?>
})();
</script>

</body>
</html>