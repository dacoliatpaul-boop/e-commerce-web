<link rel="stylesheet" type="text/css" href="css/nav.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">

<!-- Overlay -->
<div class="nav-overlay" id="navOverlay" onclick="closeNav()"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <button class="sidebar-close" onclick="closeNav()" aria-label="Close menu">&#x2715;</button>
    <nav class="sidebar-nav">
        <a href="index.php" class="sidebar-link">Home</a>
        <a href="products.php" class="sidebar-link">Products</a>
        <a href="about.php" class="sidebar-link">About</a>
        <a href="contact.php" class="sidebar-link">Contact</a>
    </nav>
    <div class="sidebar-footer">
        <a href="login.php" class="sidebar-auth-btn">Login</a>
        <a href="register.php" class="sidebar-auth-btn outline">Register</a>
    </div>
</aside>

<!-- Top Bar -->
<header class="topbar">
    <div class="topbar-left">
        <button class="hamburger" id="hamburger" onclick="openNav()" aria-label="Open menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <a href="index.php" class="topbar-logo">DCO</a>
    </div>
    <div class="topbar-right">
        <a href="login.php" class="auth-link">Login</a>
        <span class="auth-sep">/</span>
        <a href="register.php" class="auth-link">Register</a>
    </div>
</header>

<!-- Spacer so page content doesn't hide under fixed topbar -->
<div class="topbar-spacer"></div>

<script>
function openNav() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('navOverlay').classList.add('active');
    document.getElementById('hamburger').classList.add('hidden');
    document.body.style.overflow = 'hidden';
}
function closeNav() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('navOverlay').classList.remove('active');
    document.getElementById('hamburger').classList.remove('hidden');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeNav();
});
</script>