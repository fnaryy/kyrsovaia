<?php
session_start();
require_once '../config.php';

// Проверка прав администратора
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header("Location: ../pages/products.php");
    exit();
}

// Обработка действий с категориями
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = trim($_POST['name']);
                $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                $stmt->execute([$name]);
                break;
                
            case 'edit':
                $id = intval($_POST['id']);
                $name = trim($_POST['name']);
                $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
                $stmt->execute([$name, $id]);
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                
                // Проверяем, есть ли товары в этой категории
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
                $stmt->execute([$id]);
                $product_count = $stmt->fetchColumn();
                
                if ($product_count > 0) {
                    $_SESSION['error'] = "Невозможно удалить категорию, так как в ней есть товары";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                    $stmt->execute([$id]);
                }
                break;
        }
    }
}

// Получение списка категорий
$stmt = $pdo->query("SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id ORDER BY c.name");
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление категориями</title>
    <link rel="stylesheet" href="../accets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .categories-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .categories-table th, .categories-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .categories-table th {
            background-color: #2c3e50;
            color: white;
        }
        .categories-table tr {
            transition: all 0.3s ease;
        }
        .categories-table tr:hover {
            background-color: #2c3e50;
        }
        .categories-table tr:hover td {
            color: #ffffff;
        }
        .categories-table tr:hover .btn {
            background-color: #ffffff;
            color: #2c3e50;
        }
        .categories-table tr:hover .btn-warning {
            background-color: #ffffff;
            color: #ffc107;
        }
        .categories-table tr:hover .btn-danger {
            background-color: #ffffff;
            color: #dc3545;
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
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal.active {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            color: #333;
            width: 90%;
            max-width: 500px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            margin: 0;
        }
        .modal-content h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .modal-content label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        .modal-content input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
            color: #333;
            background-color: white;
        }
        .modal-content input[type="text"]:focus {
            outline: none;
            border-color: #2c3e50;
            box-shadow: 0 0 0 2px rgba(44, 62, 80, 0.1);
        }
        .modal-content button {
            background-color: #2c3e50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            margin-top: 15px;
        }
        .modal-content button:hover {
            background-color: #1a252f;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .category-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
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

        </div>
    </header>

    <div class="admin-container">
        <h1>Управление категориями</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>
        
        <button class="btn btn-primary" onclick="showAddModal()">Добавить категорию</button>
        
        <table class="categories-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Изображение</th>
                    <th>Количество товаров</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($category['id']); ?></td>
                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                        <td>
                            <?php if (!empty($category['image'])): ?>
                                <img src="../<?php echo htmlspecialchars($category['image']); ?>" alt="Изображение категории" class="category-image">
                            <?php else: ?>
                                <img src="../uploads/categories/default.png" alt="Изображение категории" class="category-image">
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($category['product_count']); ?></td>
                        <td class="action-buttons">
                            <button class="btn btn-warning" onclick="showEditModal(<?php echo htmlspecialchars(json_encode($category)); ?>)">Редактировать</button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Вы уверены, что хотите удалить эту категорию?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                <button type="submit" class="btn btn-danger">Удалить</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Модальное окно добавления категории -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h2>Добавить категорию</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="name">Название:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Добавить</button>
                <button type="button" class="btn btn-danger" onclick="hideAddModal()">Отмена</button>
            </form>
        </div>
    </div>

    <!-- Модальное окно редактирования категории -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h2>Редактировать категорию</h2>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label for="edit_name">Название:</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Сохранить</button>
                <button type="button" class="btn btn-danger" onclick="hideEditModal()">Отмена</button>
            </form>
        </div>
    </div>

    <script>
        function showAddModal() {
            const modal = document.getElementById('addModal');
            modal.style.display = 'block';
            setTimeout(() => modal.classList.add('active'), 10);
        }
        
        function hideAddModal() {
            const modal = document.getElementById('addModal');
            modal.classList.remove('active');
            setTimeout(() => modal.style.display = 'none', 300);
        }
        
        function showEditModal(category) {
            document.getElementById('edit_id').value = category.id;
            document.getElementById('edit_name').value = category.name;
            const modal = document.getElementById('editModal');
            modal.style.display = 'block';
            setTimeout(() => modal.classList.add('active'), 10);
        }
        
        function hideEditModal() {
            const modal = document.getElementById('editModal');
            modal.classList.remove('active');
            setTimeout(() => modal.style.display = 'none', 300);
        }
        
        // Закрытие модальных окон при клике вне их области
        window.onclick = function(event) {
            const addModal = document.getElementById('addModal');
            const editModal = document.getElementById('editModal');
            
            if (event.target === addModal) {
                hideAddModal();
            }
            if (event.target === editModal) {
                hideEditModal();
            }
        }

        // Предотвращение закрытия при клике на само модальное окно
        document.querySelectorAll('.modal-content').forEach(content => {
            content.onclick = function(event) {
                event.stopPropagation();
            }
        });
    </script>
</body>
</html> 