<?php
require_once 'config/app.php';   // starts session

// Clear this user's cart (it's DB-backed now, not sessionStorage — see cart.js)
if (!empty($_SESSION['user_id'])) {
    $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?')->execute([(int) $_SESSION['user_id']]);
}

// Destroy the session completely
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Logging out…</title>
</head>
<body>
<script>
    window.location.replace('index.php');
</script>
</body>
</html>