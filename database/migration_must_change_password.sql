-- Migration: add must_change_password column to users table
-- Run once on the production DB

ALTER TABLE users
  ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0
  AFTER password_hash;
