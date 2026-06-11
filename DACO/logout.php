<?php
require_once 'config/app.php';   // starts session

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

// Don't redirect with PHP header() — instead render a tiny page that
// clears sessionStorage (cart) in the browser first, then redirects.
// This guarantees the cart is wiped on the client side before landing
// on the homepage.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Logging out…</title>
</head>
<body>
<script>
    sessionStorage.removeItem('dco_cart');
    window.location.replace('index.php');
</script>
</body>
</html>