<?php
session_start();
require_once '../config.php';

// Проверяем наличие ID товара
if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$product_id = (int)$_GET['id'];

// Получаем информацию о товаре
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name, u.name as seller_name, u.telegram, u.vk, u.email, u.avatar as seller_avatar, u.id as seller_id
    FROM products p
    JOIN categories c ON p.category_id = c.id
    JOIN users u ON p.user_id = u.id
    WHERE p.id = ?
");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

// Если товар не найден, перенаправляем на страницу товаров
if (!$product) {
    header("Location: products.php");
    exit();
}

// Получаем средний рейтинг продавца
$stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE seller_id = ?");
$stmt->execute([$product['seller_id']]);
$avg_rating = $stmt->fetch()['avg_rating'] ?? 0;

// Получаем количество товаров продавца
$stmt = $pdo->prepare("SELECT COUNT(*) as product_count FROM products WHERE user_id = ?");
$stmt->execute([$product['seller_id']]);
$product_count = $stmt->fetch()['product_count'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?></title>
    <link rel="stylesheet" href="../accets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .product-section {
            padding: 40px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .product-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .product-image-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .product-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 8px;
        }
        .product-info {
            display: flex;
            flex-direction: column;
        }
        .product-title {
            font-size: 2em;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }
        .product-price {
            font-size: 2.5em;
            font-weight: bold;
            color: #2c3e50;
            margin: 20px 0;
        }
        .product-category {
            color: #666;
            font-size: 1.1em;
            margin-bottom: 15px;
        }
        .product-description {
            color: #666;
            font-size: 1.1em;
            line-height: 1.6;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .product-date {
            color: #888;
            font-size: 0.9em;
            margin: 10px 0;
        }
        .divider {
            height: 1px;
            background-color: #eee;
            margin: 20px 0;
        }
        .seller-info {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .seller-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        .seller-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .seller-name {
            font-size: 1.2em;
            font-weight: 500;
            color: #333;
        }
        .seller-rating {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 5px;
        }
        .stars {
            color: #ffc107;
        }
        .view-reviews {
            color: #2c3e50;
            text-decoration: none;
            color: #666;
            font-size: 1em;
            margin-bottom: 10px;
        }
        .seller-products {
            color: #666;
            font-size: 1em;
            margin-bottom: 15px;
        }
        .seller-contacts {
            margin-top: 20px;
        }
        .contact-item {
            display: flex;
            align-items: center;
            margin: 10px 0;
            color: #666;
            font-size: 1em;
        }
        .contact-item i {
            margin-right: 10px;
            color: #2c3e50;
            font-size: 1.2em;
        }
        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        .social-link {
            display: inline-flex;
            align-items: center;
            padding: 12px 20px;
            border-radius: 6px;
            color: white;
            text-decoration: none;
            font-size: 1.1em;
            transition: opacity 0.3s;
        }
        .social-link:hover {
            opacity: 0.9;
        }
        .social-link.telegram {
            background-color: #0088cc;
        }
        .social-link.vk {
            background-color: #4C75A3;
        }
        .social-link i {
            margin-right: 8px;
            font-size: 1.2em;
        }
        .buy-button {
            display: block;
            width: 100%;
            padding: 15px;
            background-color: #28a745;
            color: white;
            text-align: center;
            border: none;
            border-radius: 6px;
            font-size: 1.2em;
            font-weight: 500;
            margin-top: 30px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .buy-button:hover {
            background-color: #218838;
        }
        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 20px;
            transition: background-color 0.3s;
        }
        .back-button:hover {
            background-color: #5a6268;
        }
        @media (max-width: 768px) {
            .product-container {
                grid-template-columns: 1fr;
            }
            .product-image {
                height: 300px;
            }
        }
        .review-section {
            margin-top: 30px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .review-form {
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        .review-form h3 {
            margin-bottom: 15px;
            color: #333;
            font-size: 1.2em;
        }
        .rating-input {
            display: flex;
            flex-direction: row-reverse;
            gap: 5px;
            margin-bottom: 15px;
        }
        .rating-input input[type="radio"] {
            display: none;
        }
        .rating-input label {
            cursor: pointer;
            font-size: 1.5em;
            color: #ddd;
            transition: color 0.2s;
        }
        .rating-input label:hover,
        .rating-input label:hover ~ label,
        .rating-input input[type="radio"]:checked ~ label {
            color: #ffd700;
        }
        .review-text {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 15px;
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
            color: #333;
            background-color: #fff;
        }
        .review-text:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.25);
        }
        .submit-review {
            padding: 12px 24px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 1em;
            width: 100%;
        }
        .submit-review:hover {
            background-color: #218838;
        }
        .message {
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            font-size: 1em;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .reviews-list {
            margin-top: 30px;
        }
        .reviews-list h3 {
            margin-bottom: 20px;
            color: #333;
        }
        .review-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .review-item:last-child {
            border-bottom: none;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .reviewer-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .reviewer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .reviewer-name {
            font-weight: 500;
            color: #333;
        }
        .review-date {
            color: #888;
            font-size: 0.9em;
        }
        .review-rating {
            display: flex;
            gap: 2px;
            margin: 5px 0;
            color: #ffd700;
            font-size: 1.2em;
        }
        .rating-star {
            font-size: 1.2em;
            color: #ffd700;
        }
        .rating-star:not(.filled) {
            color: #ddd;
        }
        .review-content {
            color: #444;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">PWL</div>
        <div class="links">
            <a href="../index.html" class="link">Главная</a>
            <a href="about.html" class="link">О проекте</a>
            <a href="products.php" class="link">Товары</a>
            <a href="profile.php" class="link">Профиль</a>
        </div>
    </header>

    <div class="product-section">
        <a href="products.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Назад к товарам
        </a>

        <div class="product-container">
            <div class="product-image-container">
                <?php if ($product['image']): ?>
                    <img src="../<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                <?php endif; ?>

                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $product['seller_id']): ?>
                    <div class="review-form">
                        <h3>Оставить отзыв о продавце</h3>
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="message error">
                                <?php 
                                echo htmlspecialchars($_SESSION['error']);
                                unset($_SESSION['error']);
                                ?>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="message success">
                                <?php 
                                echo htmlspecialchars($_SESSION['success']);
                                unset($_SESSION['success']);
                                ?>
                            </div>
                        <?php endif; ?>
                        <form action="../seller/add_review.php" method="POST">
                            <input type="hidden" name="seller_id" value="<?php echo $product['seller_id']; ?>">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            
                            <div class="rating-input">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" required>
                                    <label for="star<?php echo $i; ?>">★</label>
                                <?php endfor; ?>
                            </div>
                            
                            <textarea name="review_text" class="review-text" placeholder="Напишите ваш отзыв о продавце..." required></textarea>
                            
                            <button type="submit" class="submit-review">Отправить отзыв</button>
                        </form>
                    </div>
                <?php endif; ?>

                <div class="reviews-list">
                    <h3>Отзывы о продавце</h3>
                    <?php
                    $reviews_sql = "SELECT r.*, u.name as reviewer_name, u.avatar as reviewer_avatar 
                                   FROM reviews r 
                                   LEFT JOIN users u ON r.user_id = u.id 
                                   WHERE r.seller_id = ? 
                                   ORDER BY r.created_at DESC";
                    $reviews_stmt = $pdo->prepare($reviews_sql);
                    $reviews_stmt->execute([$product['seller_id']]);
                    $reviews = $reviews_stmt->fetchAll();

                    if (empty($reviews)): ?>
                        <p>Пока нет отзывов о продавце</p>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="reviewer-info">
                                        <?php if ($review['reviewer_avatar']): ?>
                                            <img src="../<?php echo htmlspecialchars($review['reviewer_avatar']); ?>" alt="Аватар" class="reviewer-avatar">
                                        <?php else: ?>
                                            <img src="../uploads/avatars/default.png" alt="Аватар" class="reviewer-avatar">
                                        <?php endif; ?>
                                        <span class="reviewer-name"><?php echo htmlspecialchars($review['reviewer_name']); ?></span>
                                    </div>
                                    <span class="review-date"><?php echo date('d.m.Y', strtotime($review['created_at'])); ?></span>
                                </div>
                                
                                <div class="review-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="rating-star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>">
                                            <?php echo $i <= $review['rating'] ? '★' : '☆'; ?>
                                        </span>
                                    <?php endfor; ?>
                                </div>
                                
                                <div class="review-content">
                                    <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="product-info">
                <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="product-price"><?php echo number_format($product['price'], 2); ?> ₽</p>
                <p class="product-category">Категория: <?php echo htmlspecialchars($product['category_name']); ?></p>
                <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                <p class="product-date">Добавлено: <?php echo date('d.m.Y', strtotime($product['created_at'])); ?></p>

                <div class="seller-info">
                    <div class="seller-header">
                        <div class="seller-avatar">
                            <?php if ($product['seller_avatar']): ?>
                                <img src="../<?php echo htmlspecialchars($product['seller_avatar']); ?>" alt="Avatar" style="width: 100%; height: 100%; border-radius: 50%;">
                            <?php else: ?>
                                <img src="../uploads/avatars/default.png" alt="Avatar" style="width: 100%; height: 100%; border-radius: 50%;">
                            <?php endif; ?>
                        </div>
                        <div>
                            <p class="seller-name">Продавец: <?php echo htmlspecialchars($product['seller_name']); ?></p>
                            <div class="seller-rating">
                                <span class="stars">
                                    <?php
                                    $full_stars = floor($avg_rating);
                                    $half_star = $avg_rating - $full_stars >= 0.5;
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $full_stars) {
                                            echo '<i class="fas fa-star"></i>';
                                        } elseif ($i == $full_stars + 1 && $half_star) {
                                            echo '<i class="fas fa-star-half-alt"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                    ?>
                                </span>
                                <a href="seller_reviews.php?id=<?php echo $product['seller_id']; ?>" class="view-reviews">
                                    <?php echo number_format($avg_rating, 1); ?> из 5
                                </a>
                            </div>
                        </div>
                    </div>
                    <p class="seller-products">Товаров: <?php echo $product_count; ?></p>

                    <div class="seller-contacts">
                        <?php if ($product['email']): ?>
                            <div class="contact-item">
                                <i class="fas fa-envelope"></i>
                                <?php echo htmlspecialchars($product['email']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="social-links">
                            <?php if ($product['telegram']): ?>
                                <a href="https://t.me/<?php echo ltrim(htmlspecialchars($product['telegram']), '@'); ?>" class="social-link telegram" target="_blank">
                                    <i class="fab fa-telegram-plane"></i>
                                    Telegram
                                </a>
                            <?php endif; ?>

                            <?php if ($product['vk']): ?>
                                <a href="https://vk.com/<?php echo htmlspecialchars($product['vk']); ?>" class="social-link vk" target="_blank">
                                    <i class="fab fa-vk"></i>
                                    VK
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="section-why">
        <div class="why-content">
            <h1 class="why-title">Подробная информация о товаре</h1>
            <p class="why-text">
                Здесь вы можете ознакомиться с полным описанием товара и связаться с продавцом.<br>
                Для покупки используйте контактные данные продавца в социальных сетях.
            </p>
        </div>
        <div class="divider"></div>
        <footer class="footer-bold">Products Without limits — ваш мир без границ. ✅</footer>
    </div>
</body>
</html> 