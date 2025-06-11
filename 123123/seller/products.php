<?php
session_start();
require_once '../config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Получение списка товаров продавца
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$products = $stmt->fetchAll();

// Получение списка категорий
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои товары</title>
    <link rel="stylesheet" href="../accets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .seller-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .products-table th, .products-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .products-table th {
            background-color: #2c3e50;
            color: white;
        }
        .products-table tr {
            transition: all 0.3s ease;
        }
        .products-table tr:hover {
            background-color: #2c3e50;
        }
        .products-table tr:hover td {
            color: #ffffff;
        }
        .products-table tr:hover .btn {
            background-color: #ffffff;
            color: #2c3e50;
        }
        .products-table tr:hover .btn-warning {
            background-color: #ffffff;
            color: #ffc107;
        }
        .products-table tr:hover .btn-danger {
            background-color: #ffffff;
            color: #dc3545;
        }
        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-warning {
            background-color: #ffc107;
            color: black;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
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
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1): ?>
                <a href="../admin/admin_panel.php" class="link">Админ панель</a>
            <?php endif; ?>
        </div>
    </header>

    <div class="seller-container">
        <h1>Мои товары</h1>
        
        <a href="add_product.php" class="btn btn-primary">Добавить товар</a>
        
        <table class="products-table">
            <thead>
                <tr>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1): ?>
                        <th>ID</th>
                    <?php endif; ?>
                    <th>Изображение</th>
                    <th>Название</th>
                    <th>Описание</th>
                    <th>Цена</th>
                    <th>Категория</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1): ?>
                            <td><?php echo htmlspecialchars($product['id']); ?></td>
                        <?php endif; ?>
                        <td>
                            <?php if (!empty($product['image'])): ?>
                                <img src="../<?php echo htmlspecialchars($product['image']); ?>" alt="Изображение товара" class="product-image">
                            <?php else: ?>
                                <img src="../uploads/products/default.png" alt="Изображение товара" class="product-image">
                            <?php endif; ?>
                        </td>
                        <td><?php echo markdownToHtml(htmlspecialchars($product['name'])); ?></td>
                        <td><?php echo markdownToHtml(htmlspecialchars($product['description'])); ?></td>
                        <td><?php echo number_format($product['price'], 2); ?> ₽</td>
                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'Без категории'); ?></td>
                        <td class="action-buttons">
                            <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-warning">Редактировать</a>
                            <form method="POST" action="delete_product.php" style="display: inline;" onsubmit="return confirm('Вы уверены, что хотите удалить этот товар?');">
                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                <button type="submit" class="btn btn-danger">Удалить</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html> 