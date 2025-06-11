<?php
session_start();
require_once '../config.php';

// Проверяем авторизацию
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Необходимо авторизоваться для оставления отзыва';
    header('Location: ../auth/login.php');
    exit();
}

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Неверный метод запроса';
    header('Location: ../pages/products.php');
    exit();
}

// Получаем данные из формы
$seller_id = isset($_POST['seller_id']) ? (int)$_POST['seller_id'] : 0;
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$review_text = isset($_POST['review_text']) ? trim($_POST['review_text']) : '';

// Проверяем валидность данных
if ($seller_id <= 0 || $product_id <= 0 || $rating < 1 || $rating > 5 || empty($review_text)) {
    $_SESSION['error'] = 'Пожалуйста, заполните все поля корректно';
    header('Location: ../pages/product.php?id=' . $product_id);
    exit();
}

// Проверяем, не пытается ли пользователь оставить отзыв самому себе
if ($seller_id === $_SESSION['user_id']) {
    $_SESSION['error'] = 'Вы не можете оставить отзыв самому себе';
    header('Location: ../pages/product.php?id=' . $product_id);
    exit();
}

try {
    // Проверяем существование продавца
    $check_seller = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $check_seller->execute([$seller_id]);
    if (!$check_seller->fetch()) {
        $_SESSION['error'] = 'Продавец не найден';
        header('Location: ../pages/product.php?id=' . $product_id);
        exit();
    }

    // Проверяем, не оставлял ли пользователь уже отзыв этому продавцу
    $check_review = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND seller_id = ?");
    $check_review->execute([$_SESSION['user_id'], $seller_id]);
    
    if ($check_review->fetch()) {
        $_SESSION['error'] = 'Вы уже оставляли отзыв этому продавцу';
        header('Location: ../pages/product.php?id=' . $product_id);
        exit();
    }

    // Добавляем отзыв
    $stmt = $pdo->prepare("
        INSERT INTO reviews (user_id, seller_id, rating, review_text, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    $result = $stmt->execute([
        $_SESSION['user_id'],
        $seller_id,
        $rating,
        $review_text
    ]);

    if ($result) {
        $_SESSION['success'] = 'Отзыв успешно добавлен';
    } else {
        throw new Exception('Ошибка при сохранении отзыва');
    }
} catch(Exception $e) {
    $_SESSION['error'] = 'Произошла ошибка при сохранении отзыва: ' . $e->getMessage();
}

header('Location: ../pages/product.php?id=' . $product_id);
exit(); 