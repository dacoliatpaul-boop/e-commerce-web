<?php
require_once 'config/app.php';   // starts session, defines $pdo

$errors = [];

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']            ?? '');
    $password =      $_POST['password']         ?? '';
    $confirm  =      $_POST['confirm_password'] ?? '';
    $terms    = isset($_POST['terms']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if (!$terms) {
        $errors[] = 'You must agree to the Terms and Conditions.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'That email address is already registered.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
        $stmt->execute([$email, $hash]);

        session_regenerate_id(true);
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['email']   = $email;
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="css/auth.css">
    <title>Register — DCO</title>
</head>
<body>

<a class="DCO" href="index.php">DCO</a>

<div class="page-wrapper">
    <a class="btn-back" href="index.php">&#8592; BACK</a>

    <div class="login-page">
        <h1>Register</h1>
        <p class="message">CREATE YOUR DCO ACCOUNT</p>

        <?php foreach ($errors as $err): ?>
            <p class="error-msg"><?= htmlspecialchars($err) ?></p>
        <?php endforeach; ?>

        <form method="POST" action="register.php">
            <div class="form">
                <input type="email" id="email" name="email"
                    placeholder="EMAIL ADDRESS" required
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form">
                <input type="password" id="password" name="password"
                    placeholder="PASSWORD" required>
            </div>
            <div class="form">
                <input type="password" id="confirm_password" name="confirm_password"
                    placeholder="CONFIRM PASSWORD" required>
            </div>
            <div class="terms-row">
                <input type="checkbox" id="terms" name="terms"
                    <?= isset($_POST['terms']) ? 'checked' : '' ?> required>
                <label for="terms">
                    I AGREE TO THE
                    <button type="button" class="terms-link" id="open-terms">TERMS AND CONDITIONS</button>
                </label>
            </div>
            <div class="form">
                <button type="submit">CREATE ACCOUNT</button>
            </div>
            <p class="register">ALREADY HAVE AN ACCOUNT? <a href="login.php">LOGIN</a></p>
        </form>
    </div>
</div>

<!-- Terms Modal -->
<div class="modal-overlay" id="terms-modal">
    <div class="modal-box">
        <button class="modal-close" id="close-terms">&#x2715;</button>
        <h2>TERMS AND CONDITIONS</h2>
        <p class="modal-date">Last updated: June 2025</p>
        <ol>
            <li><strong>Acceptance of Terms</strong><br>By creating an account, you agree to be bound by these Terms and Conditions.</li>
            <li><strong>Account Responsibility</strong><br>You are responsible for maintaining the confidentiality of your credentials.</li>
            <li><strong>Use of Service</strong><br>You agree to use DCO only for lawful purposes.</li>
            <li><strong>Privacy</strong><br>Your personal information is used in accordance with our Privacy Policy. We do not sell your data.</li>
            <li><strong>Modifications</strong><br>DCO reserves the right to modify these terms at any time.</li>
            <li><strong>Termination</strong><br>We reserve the right to suspend or terminate accounts that violate these terms.</li>
            <li><strong>Limitation of Liability</strong><br>DCO is not liable for any indirect or consequential damages.</li>
            <li><strong>Governing Law</strong><br>These terms are governed by applicable law.</li>
        </ol>
    </div>
</div>

<script>
    const modal    = document.getElementById('terms-modal');
    const openBtn  = document.getElementById('open-terms');
    const closeBtn = document.getElementById('close-terms');
    openBtn.addEventListener('click', () => modal.classList.add('active'));
    closeBtn.addEventListener('click', () => modal.classList.remove('active'));
    modal.addEventListener('click', (e) => { if (e.target === modal) modal.classList.remove('active'); });
</script>

</body>
</html>