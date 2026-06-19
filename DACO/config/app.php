<?php

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();


define('DB_HOST',     'localhost');
define('DB_NAME',     'dcoweb');       
define('DB_USER',     'root');
define('DB_PASSWORD', '');             
define('DB_CHARSET',  'utf8mb4');


try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASSWORD
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,   false);
} catch (PDOException $e) {
    die("<h2 style='color:red;font-family:sans-serif;padding:20px;'>
         Database connection failed: " . htmlspecialchars($e->getMessage()) . "
         </h2>");
}


if (!empty($_SESSION['user_id']) && !empty($_SESSION['login_expiry'])) {
    if (time() > $_SESSION['login_expiry']) {
        $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?')
            ->execute([$_SESSION['user_id']]);

   
        $_SESSION = [];
        session_destroy();
        setcookie('user_id',  '', time() - 3600, '/', '', false, true);
        setcookie('username', '', time() - 3600, '/', '', false, true);
        header('Location: login.php');
        exit;
    }
}
