<?php

require 'vendor/autoload.php';

use Classes\{
    Config,
    Database,
    TelegramBot,
    NotificationService,
    NotificationTester
};

// Load configuration from .env file
Config::load();

// Initialize services
$database = new Database();
$telegramBot = new TelegramBot(Config::getBotToken());
$notificationService = new NotificationService($database, $telegramBot);

// Create tester
$tester = new NotificationTester($notificationService, $database);

// Test notifications
$result = $tester->testNotifications();

// Display results
echo "<h2>Notification Test Results</h2>";
echo "<p><strong>Status:</strong> " . ($result['success'] ? '✅ Success' : '❌ Failed') . "</p>";
echo "<p><strong>Message:</strong> " . htmlspecialchars($result['message']) . "</p>";
echo "<p><strong>Birthdays Found:</strong> " . $result['birthdays_found'] . "</p>";
echo "<p><strong>Notifications Sent:</strong> " . $result['notifications_sent'] . "</p>";

if (!empty($result['errors'])) {
    echo "<h3>Errors:</h3>";
    echo "<ul>";
    foreach ($result['errors'] as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
}

// Show today's birthdays
$birthdays = $tester->getTodaysBirthdays();
if (!empty($birthdays)) {
    echo "<h3>Today's Birthdays:</h3>";
    echo "<ul>";
    foreach ($birthdays as $birthday) {
        echo "<li>" . htmlspecialchars($birthday['name']) . " (Chat ID: " . $birthday['chat_id'] . ")</li>";
    }
    echo "</ul>";
} else {
    echo "<p><em>No birthdays found for today.</em></p>";
}

echo "<hr>";
echo "<p><a href='index.php'>Back to main page</a></p>";
