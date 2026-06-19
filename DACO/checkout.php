<?php
require_once 'config/app.php';   
include 'includes/nav.php';

// Must be logged in
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId  = (int) $_SESSION['user_id'];
$errors  = [];
$success = false;
$order   = null;

try {
    $stmt = $pdo->prepare('SELECT address FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $savedAddress = $stmt->fetchColumn() ?: '';
} catch (PDOException $e) {
    $savedAddress = '';
}

$paymentMethods = [
    'bank_transfer' => 'Bank Transfer',
    'cod'           => 'Cash on Delivery',
    'gcash'         => 'GCash',
];


$shippingAddress = $_POST['shipping_address'] ?? $savedAddress;
$paymentMethod   = $_POST['payment_method']   ?? '';


function fetchCart(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare('
        SELECT ci.quantity,
               p.id AS product_id, p.name, p.category, p.price, p.image, p.stock
        FROM   cart_items ci
        JOIN   products   p ON p.id = ci.product_id
        WHERE  ci.user_id = ?
        ORDER  BY ci.added_at DESC
    ');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

try {
    $cartItems = fetchCart($pdo, $userId);
} catch (PDOException $e) {
    $cartItems = [];
    $errors[]  = 'Could not load cart: ' . $e->getMessage();
}

$total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cartItems));


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {

    $shippingAddress = trim($_POST['shipping_address'] ?? '');
    $paymentMethod   = trim($_POST['payment_method']   ?? '');

    if (empty($cartItems)) {
        $errors[] = 'Your cart is empty.';
    }

    foreach ($cartItems as $item) {
        if ((int) $item['stock'] < (int) $item['quantity']) {
            $errors[] = $item['name'] . ' only has ' . (int) $item['stock'] . ' left in stock. Please update your cart.';
        }
    }

    if ($shippingAddress === '') {
        $errors[] = 'Please enter a delivery address.';
    }

    if (!array_key_exists($paymentMethod, $paymentMethods)) {
        $errors[] = 'Please select a valid payment method.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Re-check stock for every cart item right before committing the order,
            // and lock those rows so two simultaneous checkouts can't both succeed
            // on the last few units.
            $stockStmt = $pdo->prepare('SELECT stock, name FROM products WHERE id = ? FOR UPDATE');
            foreach ($cartItems as $item) {
                $stockStmt->execute([$item['product_id']]);
                $row = $stockStmt->fetch();

                if (!$row) {
                    throw new RuntimeException($item['name'] . ' is no longer available.');
                }
                if ((int) $row['stock'] < (int) $item['quantity']) {
                    throw new RuntimeException(
                        'Not enough stock for ' . $item['name'] . ' (only ' . (int) $row['stock'] . ' left). ' .
                        'Please update your cart and try again.'
                    );
                }
            }

            // 1. Create the order header
            $stmt = $pdo->prepare('
                INSERT INTO orders (user_id, total_amount, status, shipping_address, payment_method)
                VALUES (?, ?, "pending", ?, ?)
            ');
            $stmt->execute([$userId, $total, $shippingAddress, $paymentMethod]);
            $orderId = (int) $pdo->lastInsertId();

            // 2. Insert each order item (snapshot price) and decrement stock
            $itemStmt = $pdo->prepare('
                INSERT INTO order_items (order_id, product_id, quantity, unit_price)
                VALUES (?, ?, ?, ?)
            ');
            $stockUpdateStmt = $pdo->prepare('
                UPDATE products SET stock = stock - ? WHERE id = ?
            ');
            foreach ($cartItems as $item) {
                $itemStmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price'],
                ]);
                $stockUpdateStmt->execute([$item['quantity'], $item['product_id']]);
            }

            // 3. Clear the cart
            $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?')->execute([$userId]);

            $pdo->commit();

            $success   = true;
            $order     = [
                'id'      => $orderId,
                'total'   => $total,
                'items'   => $cartItems,
                'address' => $shippingAddress,
                'payment' => $paymentMethods[$paymentMethod],
            ];
            $cartItems = [];
            $total     = 0;

        } catch (RuntimeException $e) {
            $pdo->rollBack();
            $errors[] = $e->getMessage();
        } catch (PDOException $e) {
            $pdo->rollBack();
            // Shows the real DB error so you can diagnose it
            $errors[] = 'Order failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <link rel="stylesheet" type="text/css" href="css/checkout.css">
    <title>Checkout — DCO</title>
</head>
<body>
<div class="topbar-spacer"></div>

<div class="checkout-wrapper">

    <?php if ($success && $order): ?>

    <div class="order-confirmed">
        <div class="confirmed-icon">✓</div>
        <h1>Order Placed!</h1>
        <p class="confirmed-sub">Thank you for your purchase. Your order <strong>#<?= $order['id'] ?></strong> has been received.</p>

        <div class="order-summary-box">
            <h3>Order Summary</h3>
            <?php foreach ($order['items'] as $item): ?>
            <div class="summary-row">
                <span><?= htmlspecialchars($item['name']) ?> × <?= $item['quantity'] ?></span>
                <span>&#8369; <?= number_format($item['price'] * $item['quantity']) ?></span>
            </div>
            <?php endforeach; ?>
            <div class="summary-row total-row">
                <span>Total</span>
                <span>&#8369; <?= number_format($order['total']) ?></span>
            </div>
        </div>

        <div class="order-summary-box">
            <h3>Delivery & Payment</h3>
            <div class="summary-row" style="display:block;">
                <span style="display:block;color:#888;font-size:.75rem;letter-spacing:.05em;text-transform:uppercase;margin-bottom:4px;">Delivery Address</span>
                <span style="display:block;"><?= nl2br(htmlspecialchars($order['address'])) ?></span>
            </div>
            <div class="summary-row total-row" style="display:block;">
                <span style="display:block;color:#888;font-size:.75rem;letter-spacing:.05em;text-transform:uppercase;margin-bottom:4px;font-weight:400;">Payment Method</span>
                <span style="display:block;font-weight:600;"><?= htmlspecialchars($order['payment']) ?></span>
            </div>
        </div>

        <a class="btn-continue" href="products.php">Continue Shopping</a>
    </div>

    <?php else: ?>
    <!-- ── CHECKOUT FORM ───────────────────────────────────────────── -->
    <h1 class="checkout-title">Checkout</h1>

    <?php foreach ($errors as $err): ?>
        <p class="error-msg"><?= htmlspecialchars($err) ?></p>
    <?php endforeach; ?>

    <?php if (empty($cartItems)): ?>
        <div class="empty-cart">
            <p>Your cart is empty.</p>
            <a href="products.php">Browse Products</a>
        </div>

    <?php else: ?>
        <div class="checkout-grid">

            <!-- Cart items -->
            <div class="cart-items-list">
                <h2>Your Cart</h2>
                <?php
                $hasStockIssue = false;
                foreach ($cartItems as $item):
                    $lineTotal     = $item['price'] * $item['quantity'];
                    $itemStock     = (int) $item['stock'];
                    $insufficient  = $itemStock < (int) $item['quantity'];
                    if ($insufficient) { $hasStockIssue = true; }
                ?>
                <div class="cart-item<?= $insufficient ? ' stock-issue' : '' ?>">
                    <div class="cart-item-img">
                        <?php if (!empty($item['image'])): ?>
                            <img src="<?= htmlspecialchars($item['image']) ?>"
                                 alt="<?= htmlspecialchars($item['name']) ?>">
                        <?php else: ?>
                            <div class="img-placeholder"></div>
                        <?php endif; ?>
                    </div>
                    <div class="cart-item-info">
                        <span class="ci-category"><?= htmlspecialchars($item['category']) ?></span>
                        <span class="ci-name"><?= htmlspecialchars($item['name']) ?></span>
                        <span class="ci-qty">Qty: <?= $item['quantity'] ?></span>
                        <?php if ($insufficient): ?>
                            <span class="ci-stock-warning">
                                <?= $itemStock <= 0 ? 'Now out of stock' : 'Only ' . $itemStock . ' left — reduce quantity' ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="cart-item-price">
                        &#8369; <?= number_format($lineTotal) ?>
                    </div>
                    <!-- Remove button (AJAX) -->
                    <button class="btn-remove-item"
                            data-product-id="<?= $item['product_id'] ?>"
                            title="Remove">✕</button>
                </div>
                <?php endforeach; ?>

                <!-- Delivery & Payment -->
                <div class="delivery-panel">
                    <h2>Delivery & Payment</h2>

                    <div class="field-group">
                        <label for="shipping_address">Delivery Address</label>
                        <textarea id="shipping_address" name="shipping_address" form="place-order-form"
                                  rows="3" placeholder="House/Unit No., Street, Barangay, City, Province"
                                  required><?= htmlspecialchars($shippingAddress) ?></textarea>
                    </div>

                    <div class="field-group">
                        <label for="payment_method">Payment Method</label>
                        <select id="payment_method" name="payment_method" form="place-order-form" required>
                            <option value="" disabled <?= $paymentMethod === '' ? 'selected' : '' ?>>Select payment method</option>
                            <?php foreach ($paymentMethods as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $paymentMethod === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Order total & place order -->
            <div class="order-panel">
                <h2>Order Total</h2>
                <div class="totals">
                    <div class="total-row">
                        <span>Subtotal</span>
                        <span id="subtotal-display">&#8369; <?= number_format($total) ?></span>
                    </div>
                    <div class="total-row grand">
                        <span>Total</span>
                        <span id="total-display">&#8369; <?= number_format($total) ?></span>
                    </div>
                </div>

                <form method="POST" action="checkout.php" id="place-order-form">
                    <button type="submit" name="place_order" class="btn-place-order" <?= $hasStockIssue ? 'disabled' : '' ?>>
                        Place Order
                    </button>
                    <?php if ($hasStockIssue): ?>
                        <p class="stock-issue-note">Update the quantities above before placing your order.</p>
                    <?php endif; ?>
                </form>

                <a href="products.php" class="btn-keep-shopping">← Keep Shopping</a>
            </div>

        </div>
    <?php endif; ?>
    <?php endif; ?>

</div>

<script>

document.querySelectorAll('.btn-remove-item').forEach(btn => {
    btn.addEventListener('click', async () => {
        const productId = btn.dataset.productId;
        const fd = new FormData();
        fd.append('action', 'remove');
        fd.append('product_id', productId);

        const res  = await fetch('cart.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
            btn.closest('.cart-item').remove();

            const fmt = n => '₱ ' + n.toLocaleString('en-PH', { minimumFractionDigits: 0 });
            document.getElementById('subtotal-display').textContent = fmt(data.cart_total);
            document.getElementById('total-display').textContent    = fmt(data.cart_total);

            if (data.cart_count === 0) {
                location.reload();
            }
        }
    });
});
</script>

<?php include('includes/footer.php'); ?>

</body>
</html>