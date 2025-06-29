# Birthday Bot - Telegram Bot

Telegram бот для управления днями рождения с уведомлениями.

## Структура проекта

### Основные файлы
- `index.php` - точка входа приложения
- `composer.json` - зависимости и автозагрузка
- `.env` - конфигурационный файл (создается из env.example)
- `env.example` - пример конфигурационного файла

### Классы (папка Classes/)

#### Config.php
Класс для работы с конфигурацией:
- Загрузка переменных из .env файла
- Получение настроек базы данных
- Получение токена бота

#### Database.php
Класс для работы с базой данных:
- Сохранение пользователей
- Управление днями рождения
- Получение списка именинников
- Получение сегодняшних дней рождения

#### TelegramBot.php
Класс для работы с Telegram API:
- Отправка сообщений
- Обработка callback запросов
- Получение webhook обновлений

#### UserStateManager.php
Класс для управления состояниями пользователей:
- Установка состояний (awaiting_name, awaiting_date)
- Сохранение временных данных
- Очистка состояний

#### BirthdayManager.php
Класс для управления днями рождения:
- Добавление новых именинников
- Показ списка именинников
- Удаление именинников
- Валидация дат

#### NotificationService.php
Класс для отправки уведомлений:
- Отправка ежедневных уведомлений о днях рождения

#### WebhookHandler.php
Класс для обработки входящих webhook'ов:
- Обработка сообщений
- Обработка callback запросов
- Маршрутизация команд

#### Webhook.php
Обертка для обработки webhook'ов

#### Notification.php
Обертка для отправки уведомлений

#### PDO.php
Обертка для работы с базой данных

## Команды бота

- `/add` - добавить нового именинника
- `/list` - показать список именинников с возможностью удаления

## Установка и настройка

1. Установите зависимости:
```bash
composer install
```

2. Настройте конфигурацию:
```bash
cp env.example .env
```

Отредактируйте файл `.env` и укажите ваши настройки:
```env
# Database Configuration
DB_HOST=localhost
DB_NAME=birthday_bot
DB_USER=your_db_user
DB_PASS=your_db_password

# Telegram Bot Configuration
BOT_TOKEN=your_bot_token_here
```

3. Настройте базу данных MySQL:
```sql
CREATE DATABASE birthday_bot;
USE birthday_bot;

CREATE TABLE users (
    user_id BIGINT PRIMARY KEY,
    chat_id BIGINT NOT NULL
);

CREATE TABLE birthdays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    birth_date DATE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE user_states (
    user_id BIGINT PRIMARY KEY,
    state VARCHAR(50) NOT NULL,
    temp_name VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
```

4. Настройте webhook для вашего бота

5. Настройте cron для ежедневных уведомлений:
```bash
0 9 * * * php /path/to/birthday-bot.loc/index.php
```

## Безопасность

- Файл `.env` добавлен в `.gitignore` и не будет загружен в репозиторий
- Все конфиденциальные данные хранятся в переменных окружения
- Используйте `env.example` как шаблон для создания вашего `.env` файла

## Архитектура

Проект использует объектно-ориентированный подход с разделением ответственности:

- **Config** - управление конфигурацией
- **Database** - работа с данными
- **TelegramBot** - взаимодействие с Telegram API
- **UserStateManager** - управление состояниями пользователей
- **BirthdayManager** - бизнес-логика дней рождения
- **NotificationService** - отправка уведомлений
- **WebhookHandler** - обработка входящих запросов

Каждый класс имеет четко определенную ответственность и может быть легко протестирован и модифицирован. 