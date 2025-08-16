-- Minimal init script: ensure schema exists; can add seed tables later
CREATE DATABASE IF NOT EXISTS `presence` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- mysql image already creates DB and user per env vars; this is a no-op safety

