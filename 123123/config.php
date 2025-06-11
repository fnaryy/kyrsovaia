<?php
$host = 'localhost';
$dbname = 'pwl_db';
$username = 'root';
$password = '';

try {
    // Сначала подключаемся к серверу MySQL
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Создаем базу данных, если она не существует
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    $pdo->exec("USE $dbname");
    
    // Создаем таблицу пользователей с правильной структурой
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role TINYINT(1) NOT NULL DEFAULT 0,
        avatar VARCHAR(255) DEFAULT NULL,
        telegram VARCHAR(100) DEFAULT NULL,
        vk VARCHAR(100) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $pdo->exec($sql);
    
    // Таблица категорий
    $sql = "CREATE TABLE IF NOT EXISTS categories (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);

    // Таблица товаров
    $sql = "CREATE TABLE IF NOT EXISTS products (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        category_id INT(6) UNSIGNED,
        image VARCHAR(255),
        user_id INT(6) UNSIGNED,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    $pdo->exec($sql);
    
    // Создание таблицы отзывов
    $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(6) UNSIGNED NOT NULL,
        seller_id INT(6) UNSIGNED NOT NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        review_text TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Устанавливаем кодировку UTF-8
    $pdo->exec("SET NAMES utf8");
    $pdo->exec("SET CHARACTER SET utf8");

} catch(PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Функция для проверки авторизации
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /123123/auth/login.php");
        exit();
    }
}

// Функция для проверки прав администратора
function checkAdmin() {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
        header("Location: /123123/pages/products.php");
        exit();
    }
}

// Функция для логирования действий
function logAction($action, $details = '') {
    $log_file = __DIR__ . '/logs/system.log';
    $timestamp = date('Y-m-d H:i:s');
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'guest';
    $log_message = "[$timestamp] [User: $user_id] $action" . ($details ? " - $details" : "") . "\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

// Функция для преобразования Markdown в HTML
function markdownToHtml($text) {
    // Заменяем **текст** на <strong>текст</strong>
    $text = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $text);
    
    // Заменяем *текст* на <em>текст</em>
    $text = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $text);
    
    // Заменяем переносы строк на <br>
    $text = nl2br($text);
    
    // Сохраняем пробелы и табуляции
    $text = str_replace(' ', '&nbsp;', $text);
    $text = str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', $text);
    
    return $text;
}
?> 