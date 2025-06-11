<?php
session_start();
require_once '../config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получение данных товара
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND user_id = ?");
$stmt->execute([$product_id, $_SESSION['user_id']]);
$product = $stmt->fetch();

// Проверка существования товара и прав доступа
if (!$product) {
    header("Location: products.php");
    exit();
}

// Получение списка категорий
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$errors = [];
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $category_id = (int)$_POST['category_id'];
    
    // Валидация
    if (empty($name)) {
        $errors[] = "Название товара обязательно для заполнения";
    }
    
    if (empty($description)) {
        $errors[] = "Описание товара обязательно для заполнения";
    }
    
    if ($price <= 0) {
        $errors[] = "Цена должна быть больше нуля";
    }
    
    if ($category_id <= 0) {
        $errors[] = "Выберите категорию";
    }
    
    // Обработка загрузки изображения
    $image_path = $product['image']; // Сохраняем текущий путь к изображению
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $errors[] = "Недопустимый формат файла. Разрешены: " . implode(', ', $allowed);
        } else {
            $upload_dir = '../uploads/products/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Удаляем старое изображение
                if (!empty($product['image']) && file_exists('../' . $product['image'])) {
                    unlink('../' . $product['image']);
                }
                $image_path = 'uploads/products/' . $new_filename;
            } else {
                $errors[] = "Ошибка при загрузке изображения";
            }
        }
    }
    
    if (empty($errors)) {
        $update_data = [];
        $sql = "UPDATE products SET name = ?, description = ?, price = ?, category_id = ?, image = ?";
        $update_data[] = $name;
        $update_data[] = $description;
        $update_data[] = $price;
        $update_data[] = $category_id;
        $update_data[] = $image_path;
        
        $sql .= " WHERE id = ? AND user_id = ?";
        $update_data[] = $product_id;
        $update_data[] = $_SESSION['user_id'];
        
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($update_data)) {
            $success = true;
            $_SESSION['success'] = "Товар успешно обновлен";
            header("Location: ../pages/products.php");
            exit();
        } else {
            $errors[] = "Ошибка при обновлении товара";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование товара</title>
    <link rel="stylesheet" href="../accets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .edit-section {
            padding: 40px 20px;
            max-width: 800px;
            margin: 40px auto 0;
        }
        .edit-form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
            color: #333;
            background-color: white;
        }
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.25);
        }
        .current-image {
            max-width: 200px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s ease;
        }
        .button:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
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

    <div class="edit-section">
        <div class="edit-form">
            <h2>Редактирование товара</h2>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    Товар успешно обновлен!
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Название товара:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Описание:</label>
                    <textarea id="description" name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="price">Цена:</label>
                    <input type="number" id="price" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="category_id">Категория:</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Выберите категорию</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $product['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="image">Изображение:</label>
                    <?php if (!empty($product['image'])): ?>
                        <div>
                            <img src="../<?php echo htmlspecialchars($product['image']); ?>" alt="Текущее изображение" class="current-image">
                        </div>
                    <?php endif; ?>
                    <input type="file" id="image" name="image" accept="image/*">
                    <small>Оставьте пустым, чтобы сохранить текущее изображение</small>
                </div>
                
                <button type="submit" class="button">Сохранить изменения</button>
                <a href="products.php" class="button" style="background-color: #6c757d; margin-left: 10px; text-decoration: none;">Отмена</a>
            </form>
        </div>
    </div>
</body>
</html> 