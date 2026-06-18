<?php
require_once 'config/app.php';
include('includes/nav.php');

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '') {
        $errors[] = 'Please enter your name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($message === '') {
        $errors[] = 'Please enter a message.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('
                INSERT INTO contact_messages (name, email, subject, message)
                VALUES (?, ?, ?, ?)
            ');
            $stmt->execute([$name, $email, $subject, $message]);
            $success = true;
        } catch (PDOException $e) {
            $errors[] = 'Could not send message: ' . $e->getMessage();
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
    <title>Contact — DCO</title>
</head>
<body>
<div class="topbar-spacer"></div>

<!-- PAGE HEADER -->
<div id="contact-header">
    <p id="contact-eyebrow">Get In Touch</p>
    <h1 id="contact-title">Contact Us</h1>
</div>

<!-- CONTACT SECTION -->
<div id="contact-section">

    <!-- Info column -->
    <div class="contact-info">
        <h2 class="contact-info-title">Let's Talk</h2>
        <p class="contact-info-text">
            Have a question about an order, a product, or anything else?
            Send us a message and we'll get back to you as soon as we can.
        </p>

        <div class="contact-detail">
            <span class="contact-detail-label">Email</span>
            <a class="contact-detail-value" href="mailto:hello@dco.com">dco@gmail.com</a>
        </div>
        <div class="contact-detail">
            <span class="contact-detail-label">Phone</span>
            <a class="contact-detail-value" href="tel:+639170000000">+63 9692899764</a>
        </div>
        <div class="contact-detail">
            <span class="contact-detail-label">Location</span>
            <span class="contact-detail-value">Calamba, Laguna, Philippines</span>
        </div>
    </div>

    <!-- Form column -->
    <div class="contact-form">

        <?php if ($success): ?>
            <p class="contact-success-msg">Thanks — your message has been sent. We'll be in touch soon.</p>
        <?php endif; ?>

        <?php foreach ($errors as $err): ?>
            <p class="error-msg"><?= htmlspecialchars($err) ?></p>
        <?php endforeach; ?>

        <form method="POST" action="contact.php">
            <div class="form">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" placeholder="Your name" required
                    value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>
            <div class="form">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="you@example.com" required
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form">
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" placeholder="What's this about?"
                    value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>">
            </div>
            <div class="form">
                <label for="message">Message</label>
                <textarea id="message" name="message" placeholder="Write your message here..." required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
            </div>
            <button type="submit" name="send_message" class="btn-send">Send Message</button>
        </form>
    </div>

</div>

<?php include('includes/footer.php'); ?>

</body>
</html>
