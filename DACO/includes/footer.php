<?php
/**
 * DCO — Shared Footer
 * Include at the bottom of every page, just before </body>:
 *   include('includes/footer.php');
 */
?>
<footer class="site-footer">
    <div class="footer-top">

        <div class="footer-brand">
            <span class="footer-logo">DCO</span>
            <p class="footer-tagline">Elevated essentials crafted for the discerning few.</p>
        </div>

        <div class="footer-col">
            <span class="footer-col-title">Shop</span>
            <a href="index.php">Home</a>
            <a href="products.php">All Products</a>
            <a href="index.php">New Arrivals</a> 
        </div>

        <div class="footer-col">
            <span class="footer-col-title">Support</span>
            <a href="contact.php">Contact Us</a>
            <a href="profile.php">My Profile</a>
            <a href="checkout.php">Checkout</a>
        </div>

        <div class="footer-col">
            <span class="footer-col-title">Get in Touch</span>
            <a href="mailto:hello@dco.com">dco@gmail.com</a>
            <a href="tel:+639170000000">+63 9692899764</a>
            <span class="footer-text">Calamba, Laguna, PH</span>
        </div>

    </div>

    <div class="footer-bottom">
        <span class="footer-copy">&copy; <?php echo date('Y'); ?> DCO. All rights reserved.</span>
        <div class="footer-bottom-links">
            <a href="contact.php">Contact</a>
            <span class="footer-sep">/</span>
            <a href="register.php">Register</a>
            <span class="footer-sep">/</span>
            <a href="login.php">Login</a>
        </div>
    </div>
</footer>
