CREATE TABLE users (
  user_id BIGINT PRIMARY KEY,
  chat_id BIGINT NOT NULL
);

CREATE TABLE user_states (
  user_id BIGINT PRIMARY KEY,
  state VARCHAR(50),
  temp_name VARCHAR(255)
);

CREATE TABLE birthdays (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT,
  name VARCHAR(255),
  birth_date DATE
);