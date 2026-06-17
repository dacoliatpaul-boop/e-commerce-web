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

// ── Fetch current cart ───────────────────────────────────────────────────
function fetchCart(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare('
        SELECT ci.quantity,
               p.id AS product_id, p.name, p.category, p.price, p.image
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

// ── Place order on POST ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {

    if (empty($cartItems)) {
        $errors[] = 'Your cart is empty.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // 1. Create the order header
            $stmt = $pdo->prepare('
                INSERT INTO orders (user_id, total_amount, status)
                VALUES (?, ?, "pending")
            ');
            $stmt->execute([$userId, $total]);
            $orderId = (int) $pdo->lastInsertId();

            // 2. Insert each order item (snapshot price)
            $itemStmt = $pdo->prepare('
                INSERT INTO order_items (order_id, product_id, quantity, unit_price)
                VALUES (?, ?, ?, ?)
            ');
            foreach ($cartItems as $item) {
                $itemStmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price'],
                ]);
            }

            // 3. Clear the cart
            $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?')->execute([$userId]);

            $pdo->commit();

            $success   = true;
            $order     = ['id' => $orderId, 'total' => $total, 'items' => $cartItems];
            $cartItems = [];
            $total     = 0;

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
    <!-- ── ORDER CONFIRMED ─────────────────────────────────────────── -->
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
                <?php foreach ($cartItems as $item):
                    $lineTotal = $item['price'] * $item['quantity'];
                ?>
                <div class="cart-item">
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

                <form method="POST" action="checkout.php">
                    <button type="submit" name="place_order" class="btn-place-order">
                        Place Order
                    </button>
                </form>

                <a href="products.php" class="btn-keep-shopping">← Keep Shopping</a>
            </div>

        </div>
    <?php endif; ?>
    <?php endif; ?>

</div>

<script>
// Remove item via AJAX without full page reload
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