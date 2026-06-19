<?php
require_once 'config/app.php';

setcookie('user_id',    '', time() - 3600, '/', '', false, true);
setcookie('username',   '', time() - 3600, '/', '', false, true);
setcookie('dco_expiry', '', time() - 3600, '/');


$_SESSION = [];
session_destroy();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

header('Location: index.php');
exit();