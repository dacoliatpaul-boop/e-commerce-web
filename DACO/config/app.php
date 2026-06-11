<?php
// config/app.php — PDO connection, included by login.php and register.php

session_start();

// ── Database Configuration ──────────────────────────────────────────────────
define('DB_HOST',     'localhost');
define('DB_NAME',     'dcoweb');       // ← new database name
define('DB_USER',     'root');
define('DB_PASSWORD', '');             // change if your MySQL root has a password
define('DB_CHARSET',  'utf8mb4');

// ── PDO Connection ──────────────────────────────────────────────────────────
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