<?php
session_start();
require_once '../config.php';

// Строгая проверка прав администратора
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    // Логируем попытку несанкционированного доступа
    logAction('Unauthorized access attempt to admin panel', 'User ID: ' . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set'));
    header("Location: ../pages/products.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ панель</title>
    <link rel="stylesheet" href="../accets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .admin-section {
            padding: 40px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .admin-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .admin-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        .admin-card:hover {
            transform: translateY(-5px);
        }
        .admin-card i {
            font-size: 3em;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .admin-card h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.5em;
        }
        .admin-card p {
            color: #666;
            margin-bottom: 20px;
        }
        .admin-button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #2c3e50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .admin-button:hover {
            background-color: #1a252f;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">PWL</div>
        <div class="links">
            <a href="../index.html" class="link">Главная</a>
            <a href="../pages/about.html" class="link">О проекте</a>
            <a href="../pages/products.php" class="link">Товары</a>
            <a href="../pages/profile.php" class="link">Профиль</a>
            <a href="admin_panel.php" class="link">Админ панель</a>
        </div>
    </header>

    <section class="admin-section">
        <div class="admin-container">
            <div class="admin-card">
                <i class="fas fa-users"></i>
                <h3>Управление пользователями</h3>
                <p>Управление учетными записями, ролями и разрешениями</p>
                <a href="manage_users.php" class="admin-button">Управление пользователями</a>
            </div>
            <div class="admin-card">
                <i class="fas fa-box"></i>
                <h3>Управление товарами</h3>
                <p>Добавление, редактирование и удаление товаров из каталога</p>
                <a href="manage_products.php" class="admin-button">Управление товарами</a>
            </div>
            <div class="admin-card">
                <i class="fas fa-tags"></i>
                <h3>Управление категориями</h3>
                <p>Организация товаров по категориям</p>
                <a href="manage_categories.php" class="admin-button">Управление категориями</a>
            </div>
            <div class="admin-card">
                <i class="fas fa-clipboard-list"></i>
                <h3>Системные логи</h3>
                <p>Просмотр активности системы и журналов ошибок</p>
                <a href="admin_logs.php" class="admin-button">Просмотр логов</a>
            </div>
        </div>
    </section>

    <div class="section-why">
        <div class="why-content">
            <h1 class="why-title">Управление системой</h1>
            <p class="why-text">
                Здесь вы можете управлять всеми аспектами платформы.<br>
                Будьте внимательны при внесении изменений.
            </p>
        </div>
        <div class="divider"></div>
        <footer class="footer-bold">Products Without limits — ваш мир без границ. ✅</footer>
    </div>
</body>
</html> 