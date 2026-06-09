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
        'name'     => 'Oversized Linen Coat',
        'category' => 'Clothes',
        'price'    => 4800,
        'image'    => '',          // e.g. 'images/linen-coat.jpg'
        'featured' => true,
        'wide'     => true,        // big card on homepage
    ],
    [
        'name'     => 'Raw Hem Denim Trousers',
        'category' => 'Clothes',
        'price'    => 2100,
        'image'    => '',
        'featured' => true,
        'wide'     => false,
    ],
    [
        'name'     => 'Washed Cotton Tee',
        'category' => 'Clothes',
        'price'    => 890,
        'image'    => '',
        'featured' => false,
        'wide'     => false,
    ],
    [
        'name'     => 'Merino Wool Crewneck',
        'category' => 'Clothes',
        'price'    => 3400,
        'image'    => '',
        'featured' => false,
        'wide'     => false,
    ],

    // ── ACCESSORIES ─────────────────────────────────────────────
    [
        'name'     => 'Minimal Leather Bag',
        'category' => 'Accessories',
        'price'    => 3200,
        'image'    => '',
        'featured' => true,
        'wide'     => false,
    ],
    [
        'name'     => 'Titanium Card Holder',
        'category' => 'Accessories',
        'price'    => 1400,
        'image'    => '',
        'featured' => true,
        'wide'     => false,
    ],
    [
        'name'     => 'Woven Leather Belt',
        'category' => 'Accessories',
        'price'    => 1100,
        'image'    => '',
        'featured' => false,
        'wide'     => false,
    ],
    [
        'name'     => 'Brushed Silver Ring',
        'category' => 'Accessories',
        'price'    => 980,
        'image'    => '',
        'featured' => false,
        'wide'     => false,
    ],

    // ── DEVICES ─────────────────────────────────────────────────
    [
        'name'     => 'DCO Wireless Speaker',
        'category' => 'Devices',
        'price'    => 5500,
        'image'    => '',
        'featured' => true,
        'wide'     => false,
    ],
    [
        'name'     => 'Noise-Cancel Earphones',
        'category' => 'Devices',
        'price'    => 4200,
        'image'    => '',
        'featured' => false,
        'wide'     => false,
    ],

    // ── FRAGRANCE ───────────────────────────────────────────────
    [
        'name'     => 'Noir Santal Eau de Parfum',
        'category' => 'Fragrance',
        'price'    => 2600,
        'image'    => '',
        'featured' => true,
        'wide'     => false,
    ],
    [
        'name'     => 'Amber & Oud Diffuser Set',
        'category' => 'Fragrance',
        'price'    => 1800,
        'image'    => '',
        'featured' => false,
        'wide'     => false,
    ],

];
