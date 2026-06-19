<?php
require_once 'config/app.php';  

header('Content-Type: application/json');


if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to use the cart.']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? ($_POST['action'] ?? 'view');

function getCart(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare('
        SELECT ci.id, ci.quantity,
               p.id AS product_id, p.name, p.category, p.price, p.image
        FROM   cart_items ci
        JOIN   products   p  ON p.id = ci.product_id
        WHERE  ci.user_id = ?
        ORDER  BY ci.added_at DESC
    ');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getCartTotal(array $items): float {
    return array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));
}

try {
    switch ($action) {

        case 'add': {
            $productId = (int) ($_POST['product_id'] ?? 0);
            $qty       = max(1, (int) ($_POST['quantity'] ?? 1));

            if (!$productId) {
                echo json_encode(['success' => false, 'message' => 'Invalid product.']);
                exit;
            }

      
            $check = $pdo->prepare('SELECT id FROM products WHERE id = ? AND deleted_at IS NULL');
            $check->execute([$productId]);
            if (!$check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Product not found.']);
                exit;
            }

            $stmt = $pdo->prepare('
                INSERT INTO cart_items (user_id, product_id, quantity)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
            ');
            $stmt->execute([$userId, $productId, $qty]);

            $items = getCart($pdo, $userId);
            echo json_encode([
                'success'    => true,
                'message'    => 'Added to cart.',
                'cart'       => $items,
                'cart_count' => array_sum(array_column($items, 'quantity')),
                'cart_total' => getCartTotal($items),
            ]);
            break;
        }

       
        case 'remove': {
            $productId = (int) ($_POST['product_id'] ?? 0);

            $stmt = $pdo->prepare('
                DELETE FROM cart_items WHERE user_id = ? AND product_id = ?
            ');
            $stmt->execute([$userId, $productId]);

            $items = getCart($pdo, $userId);
            echo json_encode([
                'success'    => true,
                'message'    => 'Item removed.',
                'cart'       => $items,
                'cart_count' => array_sum(array_column($items, 'quantity')),
                'cart_total' => getCartTotal($items),
            ]);
            break;
        }

       
        case 'clear': {
            $stmt = $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?');
            $stmt->execute([$userId]);

            echo json_encode([
                'success'    => true,
                'message'    => 'Cart cleared.',
                'cart'       => [],
                'cart_count' => 0,
                'cart_total' => 0,
            ]);
            break;
        }

       
        default: {
            $items = getCart($pdo, $userId);
            echo json_encode([
                'success'    => true,
                'cart'       => $items,
                'cart_count' => array_sum(array_column($items, 'quantity')),
                'cart_total' => getCartTotal($items),
            ]);
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}