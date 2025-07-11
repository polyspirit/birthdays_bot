# Birthday Bot - Laravel Telegram Bot

Telegram бот для управления днями рождения с уведомлениями, построенный на Laravel.

## Возможности

- Добавление именинников с Telegram username (опционально)
- Управление списком именинников
- Ежедневные уведомления о днях рождения
- Отправка поздравлений напрямую именинникам (если username указан)
- Удаление именинников из списка

## Команды бота

- `/add` - добавить нового именинника (требует имя, дату рождения, Telegram username опционально)
- `/list` - показать список именинников с возможностью удаления
- `/upcoming` - показать 3 ближайших дня рождения
- `/skip` - пропустить ввод Telegram username при добавлении именинника

## Установка и настройка

### 1. Клонирование и установка зависимостей

```bash
git clone <repository-url>
cd birthday-bot-laravel
composer install
```

### 2. Настройка окружения

Скопируйте файл `.env.example` в `.env` и настройте переменные окружения:

```bash
cp .env.example .env
```

Отредактируйте файл `.env` и укажите ваши настройки:

```env
# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=birthday_bot
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Telegram Bot Configuration
TELEGRAM_BOT_TOKEN=your_bot_token_here
```

### 3. Генерация ключа приложения

```bash
php artisan key:generate
```

### 4. Настройка базы данных

Создайте базу данных MySQL:

```sql
CREATE DATABASE birthday_bot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Запустите миграции:

```bash
php artisan migrate
```

### 5. Настройка webhook для Telegram бота

#### Способ 1: Через команду Artisan (рекомендуется)

```bash
# Установить webhook
php artisan telegram:set-webhook https://your-domain.com/telegram/webhook

# Проверить текущий webhook
php artisan telegram:get-webhook
```

#### Способ 2: Через браузер

Откройте в браузере:
```
https://api.telegram.org/bot{YOUR_BOT_TOKEN}/setWebhook?url=https://your-domain.com/telegram/webhook
```

#### Способ 3: Через curl

```bash
curl "https://api.telegram.org/bot{YOUR_BOT_TOKEN}/setWebhook?url=https://your-domain.com/telegram/webhook"
```

**Важно:** Замените `{YOUR_BOT_TOKEN}` на ваш токен бота и `your-domain.com` на ваш реальный домен.

### 6. Настройка cron для ежедневных уведомлений

Добавьте в crontab:

```bash
0 9 * * * cd /path/to/birthday-bot-laravel && php artisan birthday:send-notifications
```

## Использование

### Тестирование уведомлений

#### Через браузер

Откройте в браузере:
```
http://your-domain.com/telegram/test-notifications
```

#### Через API

```bash
curl http://your-domain.com/telegram/send-notifications
```

### Добавление именинников без Telegram username

Теперь вы можете добавлять именинников без указания Telegram username:

1. Отправьте команду `/add`
2. Введите имя именинника
3. Введите Telegram username или отправьте `/skip` чтобы пропустить этот шаг
4. Введите дату рождения

**Примечание:** Если Telegram username не указан, кнопки для отправки поздравлений не будут отображаться в уведомлениях.

### Получение chat_id пользователей

Если у пользователя нет публичного username или он не работает:

1. Попросите пользователя написать боту любое сообщение
2. Запустите команду:
```bash
php artisan telegram:get-chat-id
```
3. Найдите в выводе Chat ID пользователя
4. Используйте этот Chat ID при добавлении именинника

### Команды Artisan

```bash
# Настройка webhook
php artisan telegram:set-webhook https://your-domain.com/telegram/webhook
php artisan telegram:get-webhook

# Отправка ежедневных уведомлений
php artisan birthday:send-notifications

# Получение chat_id пользователей
php artisan telegram:get-chat-id
```

## Структура проекта

### Модели

- `TelegramUser` - пользователи Telegram
- `Birthday` - дни рождения
- `UserState` - состояния пользователей

### Сервисы

- `TelegramBotService` - работа с Telegram API
- `BirthdayService` - управление днями рождения
- `UserStateService` - управление состояниями пользователей
- `NotificationService` - отправка уведомлений
- `WebhookHandlerService` - обработка webhook'ов

### Контроллеры

- `TelegramWebhookController` - обработка webhook'ов от Telegram

### Команды

- `SendBirthdayNotifications` - отправка ежедневных уведомлений
- `GetChatId` - получение chat_id пользователей

## Безопасность

- Файл `.env` добавлен в `.gitignore` и не будет загружен в репозиторий
- Все конфиденциальные данные хранятся в переменных окружения
- Используйте `env.example` как шаблон

## Поддержка

При возникновении проблем:

1. Проверьте логи в `storage/logs/laravel.log`
2. Убедитесь, что webhook настроен правильно
3. Проверьте настройки базы данных
4. Убедитесь, что токен бота указан верно
