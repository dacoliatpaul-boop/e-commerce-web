-- Run this once against your existing dcoweb database to add
-- product archiving (soft-delete) support.
--
-- mysql -u youruser -p dcoweb < migrations/add_product_archive.sql

ALTER TABLE `products`
  ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `created_at`;
