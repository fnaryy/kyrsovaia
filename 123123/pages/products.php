<?php
session_start();
require_once '../config.php';

// Проверка авторизации для защищенных действий
if (isset($_GET['my_products']) && !isset($_SESSION['user_id'])) {
    header("Location: /123123/auth/login.php");
    exit();
}

// Получение параметров фильтрации
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
$price_min = isset($_GET['price_min']) ? (float)$_GET['price_min'] : 0;
$price_max = isset($_GET['price_max']) ? (float)$_GET['price_max'] : 0;
$my_products = isset($_GET['my_products']) && $_GET['my_products'] == '1';

// Получение списка категорий
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Формирование SQL запроса
$sql = "SELECT p.*, c.name as category_name, 
        u.id as seller_id, u.name as seller_name, u.avatar as seller_avatar,
        COALESCE(AVG(r.rating), 0) as seller_rating,
        COUNT(r.id) as review_count
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN reviews r ON u.id = r.seller_id
        WHERE 1=1";

$params = [];

if ($my_products && isset($_SESSION['user_id'])) {
    $sql .= " AND p.user_id = ?";
    $params[] = $_SESSION['user_id'];
}

if ($category_id > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category_id;
}

if (!empty($search)) {
    $sql .= " AND p.name LIKE ?";
    $params[] = "%$search%";
}

if ($price_min > 0) {
    $sql .= " AND p.price >= ?";
    $params[] = $price_min;
}

if ($price_max > 0) {
    $sql .= " AND p.price <= ?";
    $params[] = $price_max;
}

// Добавление сортировки
$sort_mapping = [
    'newest' => ['field' => 'created_at', 'order' => 'DESC'],
    'oldest' => ['field' => 'created_at', 'order' => 'ASC'],
    'price_asc' => ['field' => 'price', 'order' => 'ASC'],
    'price_desc' => ['field' => 'price', 'order' => 'DESC']
];

$sort_config = $sort_mapping[$sort] ?? $sort_mapping['newest'];
$sql .= " GROUP BY p.id ORDER BY p.{$sort_config['field']} {$sort_config['order']}";

// Получение товаров
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Товары</title>
    <link rel="stylesheet" href="../accets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .products-section {
            padding: 40px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .products-container {
            display: flex;
            gap: 30px;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .products-filters {
            width: 300px;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            position: sticky;
            top: 20px;
            height: fit-content;
            margin-right: 20px;
        }
        .filters-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .filter-group {
            flex: 1;
            min-width: 200px;
            width: 100%;
        }
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        .filter-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
            color: #333;
            background-color: white;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23333' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 16px;
            padding-right: 30px;
        }
        .filter-group select option {
            color: #333;
            background-color: white;
            padding: 10px;
        }
        .filter-group select:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.25);
        }
        .filter-group select:hover {
            border-color: #28a745;
        }
        .filter-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
            color: #333;
            background-color: white;
        }
        .filter-group input:focus {
            outline: none;
            border-color: #2c3e50;
            box-shadow: 0 0 0 2px rgba(44, 62, 80, 0.1);
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #2c3e50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s ease;
        }
        .button:hover {
            background-color: #1a252f;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .sort-options {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 15px;
        }
        .sort-options a {
            color: #333;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .sort-options a:hover {
            background-color: #f0f0f0;
        }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-top: 30px;
        }
        .product-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            max-height: 400px;
            position: relative;
        }
        .edit-product-button {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #28a745;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9em;
            z-index: 2;
            transition: all 0.3s ease;
        }
        .edit-product-button:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .product-image {
            width: 100%;
            aspect-ratio: 16/9;
            object-fit: cover;
            max-height: 200px;
        }
        .product-info {
            padding: 15px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .product-title {
            font-size: 1.1em;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        .product-price {
            font-size: 1.3em;
            font-weight: bold;
            color: #2c3e50;
            margin: 8px 0;
        }
        .product-category {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 8px;
        }
        .product-description {
            color: #666;
            margin: 8px 0;
            font-size: 0.9em;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            flex-shrink: 0;
        }
        .product-date {
            color: #888;
            font-size: 0.85em;
            margin: 10px 0;
        }
        .seller-info {
            margin-top: auto;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        .seller-name {
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
        }
        .seller-rating {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9em;
            color: #666;
        }
        .rating-stars {
            display: flex;
            gap: 2px;
        }
        .rating-star {
            color: #ffd700;
        }
        .rating-count {
            color: #888;
            font-size: 0.85em;
        }
        .seller-link {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
            text-decoration: none;
            color: #2c3e50;
        }
        .seller-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            object-fit: cover;
        }
        .seller-products {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        .seller-contacts {
            margin-top: 10px;
        }
        .contact-item {
            display: flex;
            align-items: center;
            margin: 5px 0;
            color: #666;
            font-size: 0.9em;
        }
        .contact-item i {
            margin-right: 8px;
            color: #2c3e50;
        }
        .social-links {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .social-link {
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 4px;
            color: white;
            text-decoration: none;
            font-size: 0.9em;
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
            margin-right: 5px;
        }
        .buy-button {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #28a745;
            color: white;
            text-align: center;
            border: none;
            border-radius: 4px;
            font-size: 1.1em;
            font-weight: 500;
            margin-top: 15px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .buy-button:hover {
            background-color: #218838;
        }
        .divider {
            height: 1px;
            background-color: #eee;
            margin: 15px 0;
        }
        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .filter-group {
            margin-bottom: 20px;
        }
        .filter-group:last-child {
            margin-bottom: 0;
        }
        .filter-group h3 {
            color: #333;
            font-size: 1.2em;
            margin-bottom: 10px;
            font-weight: 500;
        }
        .filter-options {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .filter-option {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #444;
            font-size: 1.1em;
        }
        .filter-option input[type="checkbox"],
        .filter-option input[type="radio"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .filter-option label {
            cursor: pointer;
            color: #333;
        }
        .price-range {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .price-input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
            color: #333;
            background: #fff;
        }
        .price-input:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.25);
        }
        .apply-filters {
            width: 100%;
            padding: 12px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1.1em;
            margin-top: 20px;
            transition: background-color 0.3s;
        }
        .apply-filters:hover {
            background-color: #218838;
        }
        .no-products {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .add-product-button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        .add-product-button:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .products-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
            padding: 0 20px;
        }
        .products-header .title {
            margin-bottom: 10px;
            font-size: 2em;
            color: #ffffff;
        }
        .products-header .subtitle {
            color: #ffffff;
            margin-bottom: 20px;
            font-size: 1.1em;
            line-height: 1.5;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .filter-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
        }
        .sort-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
            margin-top: 10px;
        }
        .sort-button {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background-color: #2c3e50;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            text-decoration: none;
            font-size: 1em;
            width: 100%;
        }
        .sort-button:hover {
            background-color: #1a252f;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .sort-button i {
            font-size: 0.9em;
        }
        @media (max-width: 1200px) {
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
        }
        .review-button {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 12px;
            background-color: #2c3e50;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 0.9em;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            margin-top: 10px;
        }
        .review-button:hover {
            background-color: #1a252f;
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .review-form {
            display: none;
            margin-top: 10px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .review-form.active {
            display: block;
        }
        .rating-input {
            display: flex;
            gap: 5px;
            margin-bottom: 10px;
        }
        .rating-input input[type="radio"] {
            display: none;
        }
        .rating-input label {
            cursor: pointer;
            font-size: 1.5em;
            color: #ddd;
        }
        .rating-input label:hover,
        .rating-input label:hover ~ label,
        .rating-input input[type="radio"]:checked ~ label {
            color: #ffd700;
        }
        .review-text {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
            resize: vertical;
            min-height: 80px;
        }
        .submit-review {
            padding: 8px 16px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .submit-review:hover {
            background-color: #218838;
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

    <div class="products-section">
        <div class="products-header">
            <h1 class="title">Наши товары</h1>
            <p class="subtitle">Выберите из нашего широкого ассортимента товаров</p>
        </div>

        <div class="products-container">
            <div class="products-filters">
                <div class="filter-section">
                    <form method="GET" class="filter-container">
                        <div class="filter-group">
                            <label for="category">Категория</label>
                            <select name="category" id="category">
                                <option value="">Все категории</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo isset($_GET['category']) && $_GET['category'] == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="sort">Сортировка</label>
                            <select name="sort" id="sort">
                                <option value="newest" <?php echo isset($_GET['sort']) && $_GET['sort'] == 'newest' ? 'selected' : ''; ?>>Сначала новые</option>
                                <option value="oldest" <?php echo isset($_GET['sort']) && $_GET['sort'] == 'oldest' ? 'selected' : ''; ?>>Сначала старые</option>
                                <option value="price_asc" <?php echo isset($_GET['sort']) && $_GET['sort'] == 'price_asc' ? 'selected' : ''; ?>>По возрастанию цены</option>
                                <option value="price_desc" <?php echo isset($_GET['sort']) && $_GET['sort'] == 'price_desc' ? 'selected' : ''; ?>>По убыванию цены</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="price_min">Цена от</label>
                            <input type="number" name="price_min" id="price_min" value="<?php echo isset($_GET['price_min']) ? htmlspecialchars($_GET['price_min']) : ''; ?>" placeholder="Мин. цена" min="0">
                        </div>

                        <div class="filter-group">
                            <label for="price_max">Цена до</label>
                            <input type="number" name="price_max" id="price_max" value="<?php echo isset($_GET['price_max']) ? htmlspecialchars($_GET['price_max']) : ''; ?>" placeholder="Макс. цена" min="0">
                        </div>

                        <div class="sort-group">
                            <button type="submit" class="sort-button">
                                <i class="fas fa-filter"></i>
                                Применить фильтры
                            </button>
                            <a href="products.php" class="sort-button">
                                <i class="fas fa-times"></i>
                                Сбросить
                            </a>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="products.php?my_products=1" class="sort-button" style="background-color: #28a745;">
                                    <i class="fas fa-box"></i>
                                    Мои товары
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="../seller/add_product.php" class="button" style="background-color: #28a745; margin-top: 20px; display: block; text-align: center; text-decoration: none;">Создать товар</a>
                <?php endif; ?>
            </div>
            
            <div class="products-grid">
                <?php if (empty($products)): ?>
                    <div class="no-products">
                        <p>Товары не найдены</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <?php if (isset($_SESSION['user_id']) && $product['user_id'] == $_SESSION['user_id']): ?>
                                <a href="../seller/edit_product.php?id=<?php echo $product['id']; ?>" class="edit-product-button">
                                    <i class="fas fa-edit"></i> Редактировать
                                </a>
                            <?php endif; ?>
                            <a href="product.php?id=<?php echo $product['id']; ?>" style="text-decoration: none; color: inherit;">
                                <?php if ($product['image']): ?>
                                    <img src="../<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                                <?php endif; ?>
                                <div class="product-info">
                                    <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <p class="product-price"><?php echo number_format($product['price'], 2); ?> ₽</p>
                                    <p class="product-category">Категория: <?php echo htmlspecialchars($product['category_name']); ?></p>
                                    <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                                    
                                    <div class="seller-info">
                                        <div class="seller-link">
                                            <?php if ($product['seller_avatar']): ?>
                                                <img src="../<?php echo htmlspecialchars($product['seller_avatar']); ?>" alt="Seller Avatar" class="seller-avatar">
                                            <?php else: ?>
                                                <img src="../uploads/avatars/default.png" alt="Default Avatar" class="seller-avatar">
                                            <?php endif; ?>
                                            <p class="seller-name"><?php echo htmlspecialchars($product['seller_name']); ?></p>
                                        </div>
                                        <div class="seller-rating">
                                            <div class="rating-stars">
                                                <?php
                                                $rating = round($product['seller_rating']);
                                                for ($i = 1; $i <= 5; $i++) {
                                                    echo '<span class="rating-star">' . ($i <= $rating ? '★' : '☆') . '</span>';
                                                }
                                                ?>
                                            </div>
                                            <span class="rating-count">(<?php echo $product['review_count']; ?> отзывов)</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="section-why">
        <div class="why-content">
            <h1 class="why-title">Наши товары</h1>
            <p class="why-text">
                Здесь вы можете найти все доступные товары.<br>
                Используйте фильтры для удобного поиска.
            </p>
        </div>
        <div class="divider"></div>
        <footer class="footer-bold">Products Without limits — ваш мир без границ. ✅</footer>
    </div>

    <script>
    function toggleReviewForm(productId) {
        const form = document.getElementById('reviewForm' + productId);
        form.classList.toggle('active');
    }
    </script>
</body>
</html> 