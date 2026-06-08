<?php
// Register logic goes here
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $terms    = isset($_POST['terms']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid Gmail address.';
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

    // If no errors, continue with registration logic...
    // if (empty($errors)) { ... }
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

<div class="login-page">
    <h1>Register</h1>
    <p class="message">CREATE YOUR DCO ACCOUNT</p>

    <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $err): ?>
            <p style="color:#c0392b;font-size:10px;letter-spacing:1px;text-align:center;margin-bottom:8px;">
                <?= htmlspecialchars($err) ?>
            </p>
        <?php endforeach; ?>
    <?php endif; ?>

    <form method="POST" action="register.php">

        <div class="form">
            <label for="email"></label>
            <input type="email" id="email" name="email"
                placeholder="GMAIL ADDRESS" required
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div class="form">
            <label for="password"></label>
            <input type="password" id="password" name="password"
                placeholder="PASSWORD" required>
        </div>

        <div class="form">
            <label for="confirm_password"></label>
            <input type="password" id="confirm_password" name="confirm_password"
                placeholder="CONFIRM PASSWORD" required>
        </div>

        <!-- Terms & Conditions checkbox -->
        <div class="terms-row">
            <input type="checkbox" id="terms" name="terms"
                <?= isset($_POST['terms']) ? 'checked' : '' ?> required>
            <label for="terms">
                I AGREE TO THE <span id="open-terms">TERMS AND CONDITIONS</span>
            </label>
        </div>

        <div class="form">
            <button type="submit">CREATE ACCOUNT</button>
        </div>

        <p class="register">ALREADY HAVE AN ACCOUNT? <a href="login.php">LOGIN</a></p>
        <p class="back-link"><a href="index.php">← BACK TO HOME</a></p>

    </form>
</div>

<!-- ── Terms & Conditions Modal ── -->
<div class="modal-overlay" id="terms-modal">
    <div class="modal-box">
        <button class="modal-close" id="close-terms">&#x2715;</button>
        <h2>TERMS AND CONDITIONS</h2>
        <p>Last updated: June 2025</p>
        <ol>
            <li>
                <strong>Acceptance of Terms</strong><br>
                By creating an account, you agree to be bound by these Terms and Conditions. If you do not agree, please do not register.
            </li>
            <li>
                <strong>Account Responsibility</strong><br>
                You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.
            </li>
            <li>
                <strong>Use of Service</strong><br>
                You agree to use DCO only for lawful purposes and in a manner that does not infringe the rights of others or restrict their use of the service.
            </li>
            <li>
                <strong>Privacy</strong><br>
                Your personal information is collected and used in accordance with our Privacy Policy. We do not sell your data to third parties.
            </li>
            <li>
                <strong>Modifications</strong><br>
                DCO reserves the right to modify these terms at any time. Continued use of the service after changes constitutes acceptance of the updated terms.
            </li>
            <li>
                <strong>Termination</strong><br>
                We reserve the right to suspend or terminate accounts that violate these terms without prior notice.
            </li>
            <li>
                <strong>Limitation of Liability</strong><br>
                DCO is not liable for any indirect, incidental, or consequential damages arising from your use of the service.
            </li>
            <li>
                <strong>Governing Law</strong><br>
                These terms are governed by applicable law. Any disputes shall be resolved through appropriate legal channels.
            </li>
        </ol>
    </div>
</div>

<script>
    const modal   = document.getElementById('terms-modal');
    const openBtn = document.getElementById('open-terms');
    const closeBtn = document.getElementById('close-terms');

    openBtn.addEventListener('click', () => modal.classList.add('active'));
    closeBtn.addEventListener('click', () => modal.classList.remove('active'));
    modal.addEventListener('click', (e) => {
        if (e.target === modal) modal.classList.remove('active');
    });
</script>

</body>
</html>