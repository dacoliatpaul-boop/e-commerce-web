-- Run this in phpMyAdmin (or your MySQL client) on the `dcoweb` database
-- before using the updated checkout / profile / admin pages.

-- 1. Add a saved address field to users (used to prefill checkout)
ALTER TABLE users
    ADD COLUMN address TEXT NULL AFTER phone;

-- 2. Add shipping address + payment method to each order
ALTER TABLE orders
    ADD COLUMN shipping_address TEXT NULL AFTER status,
    ADD COLUMN payment_method VARCHAR(30) NULL AFTER shipping_address;
