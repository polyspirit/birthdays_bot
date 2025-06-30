-- Create database with UTF-8 support
CREATE DATABASE IF NOT EXISTS birthday_bot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE birthday_bot;

CREATE TABLE users (
  user_id BIGINT PRIMARY KEY,
  chat_id BIGINT NOT NULL
);

CREATE TABLE user_states (
  user_id BIGINT PRIMARY KEY,
  state VARCHAR(50),
  temp_name VARCHAR(255),
  temp_username VARCHAR(255),
  temp_birthday_chat_id BIGINT
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE birthdays (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT,
  name VARCHAR(255),
  telegram_username VARCHAR(255),
  birthday_chat_id BIGINT,
  birth_date DATE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;