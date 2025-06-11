<?php
session_start();
require_once '../config.php';

// Проверка авторизации и прав администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: ../pages/products.php");
    exit();
}

// Обработка действий с товарами
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $price = floatval($_POST['price']);
                $category_id = intval($_POST['category_id']);
                
                // Обработка загрузки изображения
                $image = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $max_size = 5 * 1024 * 1024; // 5MB
                    
                    if (in_array($_FILES['image']['type'], $allowed_types) && $_FILES['image']['size'] <= $max_size) {
                        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                        $new_filename = uniqid() . '.' . $file_extension;
                        $upload_dir = '../uploads/products/';
                        $upload_path = $upload_dir . $new_filename;
                        
                        // Проверяем существование директории
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        // Проверяем права на запись
                        if (!is_writable($upload_dir)) {
                            error_log("Directory is not writable: " . $upload_dir);
                            die("Ошибка: нет прав на запись в директорию");
                        }
                        
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                            $image = 'uploads/products/' . $new_filename;
                            error_log("File uploaded successfully: " . $upload_path);
                        } else {
                            error_log("Failed to move uploaded file to: " . $upload_path);
                            error_log("Upload error: " . $_FILES['image']['error']);
                            die("Ошибка при загрузке файла");
                        }
                    } else {
                        error_log("Invalid file type or size: " . $_FILES['image']['type'] . ", " . $_FILES['image']['size']);
                        die("Ошибка: недопустимый тип файла или размер превышает 5MB");
                    }
                }
                
                $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category_id, image) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $description, $price, $category_id, $image]);
                break;
                
            case 'edit':
                $id = intval($_POST['id']);
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $price = floatval($_POST['price']);
                $category_id = intval($_POST['category_id']);
                
                $sql = "UPDATE products SET name = ?, description = ?, price = ?, category_id = ?";
                $params = [$name, $description, $price, $category_id];
                
                // Обработка загрузки нового изображения
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $max_size = 5 * 1024 * 1024; // 5MB
                    
                    if (in_array($_FILES['image']['type'], $allowed_types) && $_FILES['image']['size'] <= $max_size) {
                        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                        $new_filename = uniqid() . '.' . $file_extension;
                        $upload_path = '../uploads/products/' . $new_filename;
                        
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                            // Удаляем старое изображение
                            $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
                            $stmt->execute([$id]);
                            $old_image = $stmt->fetchColumn();
                            if ($old_image && file_exists($old_image)) {
                                unlink($old_image);
                            }
                            
                            $sql .= ", image = ?";
                            $params[] = 'uploads/products/' . $new_filename;
                        }
                    }
                }
                
                $sql .= " WHERE id = ?";
                $params[] = $id;
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                
                // Удаляем изображение
                $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
                $stmt->execute([$id]);
                $image = $stmt->fetchColumn();
                if ($image && file_exists($image)) {
                    unlink($image);
                }
                
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$id]);
                break;
        }
    }
}

// Получение списка товаров с категориями
$stmt = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.created_at DESC
");
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
    <title>Управление товарами</title>
    <link rel="stylesheet" href="../accets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .admin-container {
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
        .modal-content input[type="text"],
        .modal-content input[type="number"],
        .modal-content textarea,
        .modal-content select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
            color: #333;
            background-color: white;
        }
        .modal-content input[type="text"]:focus,
        .modal-content input[type="number"]:focus,
        .modal-content textarea:focus,
        .modal-content select:focus {
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
        <h1>Управление товарами</h1>
        
        <button class="btn btn-primary" onclick="showAddModal()">Добавить товар</button>
        
        <table class="products-table">
            <thead>
                <tr>
                    <th>ID</th>
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
                        <td><?php echo htmlspecialchars($product['id']); ?></td>
                        <td>
                            <?php if (!empty($product['image'])): ?>
                                <img src="../<?php echo htmlspecialchars($product['image']); ?>" alt="Изображение товара" class="product-image">
                            <?php else: ?>
                                <img src="../uploads/products/default.png" alt="Изображение товара" class="product-image">
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo htmlspecialchars($product['description']); ?></td>
                        <td><?php echo number_format($product['price'], 2); ?> ₽</td>
                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'Без категории'); ?></td>
                        <td class="action-buttons">
                            <button class="btn btn-warning" onclick="showEditModal(<?php echo htmlspecialchars(json_encode($product)); ?>)">Редактировать</button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Вы уверены, что хотите удалить этот товар?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                <button type="submit" class="btn btn-danger">Удалить</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Модальное окно добавления товара -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h2>Добавить товар</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="name">Название:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Описание:</label>
                    <textarea id="description" name="description" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="price">Цена:</label>
                    <input type="number" id="price" name="price" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="category_id">Категория:</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Выберите категорию</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="image">Изображение:</label>
                    <input type="file" id="image" name="image" accept="image/*">
                </div>
                
                <button type="submit" class="btn btn-primary">Добавить</button>
                <button type="button" class="btn btn-danger" onclick="hideAddModal()">Отмена</button>
            </form>
        </div>
    </div>

    <!-- Модальное окно редактирования товара -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h2>Редактировать товар</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label for="edit_name">Название:</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_description">Описание:</label>
                    <textarea id="edit_description" name="description" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit_price">Цена:</label>
                    <input type="number" id="edit_price" name="price" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_category_id">Категория:</label>
                    <select id="edit_category_id" name="category_id" required>
                        <option value="">Выберите категорию</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_image">Изображение:</label>
                    <input type="file" id="edit_image" name="image" accept="image/*">
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
        
        function showEditModal(product) {
            document.getElementById('edit_id').value = product.id;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_description').value = product.description;
            document.getElementById('edit_price').value = product.price;
            document.getElementById('edit_category_id').value = product.category_id;
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