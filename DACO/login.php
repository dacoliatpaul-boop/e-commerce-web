<?php
require_once 'config/app.php';   

$errors = [];

if (!empty($_SESSION['user_id'])) {
    $adminEmails = ['dco@admin.com', 'owner@dco.com'];
    header('Location: ' . (in_array($_SESSION['email'] ?? '', $adminEmails) ? 'admin.php' : 'index.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (empty($password)) {
        $errors[] = 'Password is required.';
    } else {
        $stmt = $pdo->prepare('SELECT id, email, password_hash FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email']   = $user['email'];

            // Set login timeout — change $timeout to however many seconds you want
            $timeout = 5;
            $_SESSION['login_expiry'] = time() + $timeout;
            setcookie('user_id',  $user['id'],    time() + $timeout, '/', '', false, true);
            setcookie('username', $user['email'], time() + $timeout, '/', '', false, true);

            // Wipe any leftover cart from a previous browser session —
            // logging in again means the old session (and its cart) is over.
            $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?')->execute([$user['id']]);

            $adminEmails = ['dco@admin.com', 'owner@dco.com'];
            header('Location: ' . (in_array($user['email'], $adminEmails) ? 'admin.php' : 'index.php'));
            exit;
        } else {
            $errors[] = 'Incorrect email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="css/auth.css">
    <title>Login — DCO</title>
</head>
<body>

<a class="DCO" href="index.php">DCO</a>

<div class="page-wrapper">
    <a class="btn-back" href="index.php">&#8592; BACK</a>

    <div class="login-page">
        <h1>Login</h1>
        <p class="message">WELCOME TO DCO</p>

        <?php foreach ($errors as $err): ?>
            <p class="error-msg"><?= htmlspecialchars($err) ?></p>
        <?php endforeach; ?>

        <form method="POST" action="login.php">
            <div class="form">
                <input type="email" id="email" name="email"
                    placeholder="EMAIL" required
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form">
                <input type="password" id="password" name="password"
                    placeholder="PASSWORD" required>
                <a href="forgot-password.php">FORGOT PASSWORD?</a>
            </div>
            <div class="form">
                <button type="submit">LOGIN</button>
            </div>
            <p class="register">DON'T HAVE AN ACCOUNT? <a href="register.php">REGISTER</a></p>
        </form>
    </div>
</div>

</body>
</html>