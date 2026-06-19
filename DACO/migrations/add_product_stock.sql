-- Run this once against your existing dcoweb database to add
-- stock-quantity tracking to products.
--
-- mysql -u youruser -p dcoweb < migrations/add_product_stock.sql

ALTER TABLE `products`
  ADD COLUMN `stock` INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `wide`;
