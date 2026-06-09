<?php
/**
 * ╔══════════════════════════════════════════════════════════════╗
 *  DCO — PRODUCT CONFIGURATION
 *  Edit this file to manage all your products.
 *  Changes here automatically update the homepage and products page.
 * ╚══════════════════════════════════════════════════════════════╝
 *
 *  HOW TO ADD / EDIT A PRODUCT
 *  ───────────────────────────
 *  Each product is one entry inside $products = [ ... ]
 *
 *  'name'     → Product name shown on the site
 *  'category' → Must be one of: Clothes | Accessories | Devices | Fragrance
 *  'price'    → Number only, no ₱ sign  (e.g. 4800)
 *  'image'    → Path to your image file (e.g. 'images/my-coat.jpg')
 *               Use '' (empty string) to show the default placeholder
 *  'featured' → true  = appears on the homepage showcase
 *               false = products page only
 *  'wide'     → true  = wide card on homepage (span 2 columns), only for one featured item
 *               false = normal card
 *
 *  HOW TO ADD YOUR IMAGE
 *  ─────────────────────
 *  1. Put your image file in the  images/  folder (create it if it doesn't exist).
 *  2. Set 'image' => 'images/your-filename.jpg'
 *  3. Recommended size: at least 800×800 px, square or portrait crops best.
 */

$products = [

    // ── CLOTHES ─────────────────────────────────────────────────
    [
        'name'     => 'White Executive Longsleeve',
        'category' => 'Clothes',
        'price'    => 4800,
        'image'    => 'img/shirt2.png',          // e.g. 'images/linen-coat.jpg'
        'featured' => true,
        'wide'     => true,        // big card on homepage
    ],
    [
        'name'     => 'Black Executive Longsleeve',
        'category' => 'Clothes',
        'price'    => 4800,
        'image'    => 'img/shirt1.png',          // e.g. 'images/linen-coat.jpg'
        'featured' => false,
        'wide'     => true,        // big card on homepage
    ],
    [
        'name'     => 'Dark Blue Executive Longsleeve',
        'category' => 'Clothes',
        'price'    => 4800,
        'image'    => 'img/shirt3.png',          // e.g. 'images/linen-coat.jpg'
        'featured' => false,
        'wide'     => true,        // big card on homepage
    ],
    [
        'name'     => 'Biege Executive Longsleeve',
        'category' => 'Clothes',
        'price'    => 4800,
        'image'    => 'img/shirt4.png',          // e.g. 'images/linen-coat.jpg'
        'featured' => false,
        'wide'     => true,        // big card on homepage
    ],
    [
        'name'     => 'Raw Hem Denim Trousers',
        'category' => 'Clothes',
        'price'    => 2100,
        'image'    => 'img/baggy1.jpeg',
        'featured' => true,
        'wide'     => false,
    ],
    [
        'name'     => 'Washed Cotton Tee',
        'category' => 'Clothes',
        'price'    => 890,
        'image'    => 'img/cotton1.jpeg',
        'featured' => false,
        'wide'     => false,
    ],
    [
        'name'     => 'Merino Wool Crewneck',
        'category' => 'Clothes',
        'price'    => 3400,
        'image'    => 'img/wool1.jpeg',
        'featured' => false,
        'wide'     => false,
    ],

    // ── ACCESSORIES ─────────────────────────────────────────────
    [
        'name'     => 'Minimal Leather Bag',
        'category' => 'Accessories',
        'price'    => 3200,
        'image'    => 'img/bag1.jpeg',
        'featured' => true,
        'wide'     => false,
    ],
    [
        'name'     => 'Woven Leather Belt',
        'category' => 'Accessories',
        'price'    => 1100,
        'image'    => 'img/belt1.jpeg',
        'featured' => false,
        'wide'     => false,
    ],
    [
        'name'     => 'Brushed Silver Ring',
        'category' => 'Accessories',
        'price'    => 980,
        'image'    => 'img/ring1.jpeg',
        'featured' => false,
        'wide'     => false,
    ],

    // ── DEVICES ─────────────────────────────────────────────────
    [
        'name'     => 'DCO Wireless Speaker',
        'category' => 'Devices',
        'price'    => 5500,
        'image'    => 'img/speaker1.jpeg',
        'featured' => true,
        'wide'     => false,
    ],
    [
        'name'     => 'Noise-Cancel Earphones',
        'category' => 'Devices',
        'price'    => 4200,
        'image'    => 'img/device1.jpeg',
        'featured' => false,
        'wide'     => false,
    ],

    // ── FRAGRANCE ───────────────────────────────────────────────
    [
        'name'     => 'Noir Santal Eau de Parfum',
        'category' => 'Fragrance',
        'price'    => 12600,
        'image'    => 'img/perfume1.jpeg',
        'featured' => true,
        'wide'     => false,
    ],
    [
        'name'     => 'Amber & Oud Diffuser Set',
        'category' => 'Fragrance',
        'price'    => 11800,
        'image'    => 'img/perfume2.jpeg',
        'featured' => false,
        'wide'     => false,
    ],

];
?>