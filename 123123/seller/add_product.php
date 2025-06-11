<?php
session_start();
require_once '../config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: /123123/auth/login.php");
    exit();
}

// Создаем директории для загрузки, если они не существуют
$upload_dirs = ['../uploads', '../uploads/products', '../uploads/avatars'];
foreach ($upload_dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category_id = intval($_POST['category_id']);
    $user_id = $_SESSION['user_id'];
    
    $errors = [];
    
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
        $errors[] = "Выберите категорию товара";
    }
    
    // Обработка загрузки изображения
    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $errors[] = "Недопустимый формат файла. Разрешены только JPG, PNG и GIF.";
        } elseif ($_FILES['image']['size'] > $max_size) {
            $errors[] = "Размер файла не должен превышать 5MB.";
        } else {
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = '../uploads/products/' . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image = 'uploads/products/' . $new_filename;
            } else {
                $errors[] = "Ошибка при загрузке файла. Проверьте права доступа к директории uploads/products.";
            }
        }
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category_id, image, user_id) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $description, $price, $category_id, $image, $user_id])) {
                $_SESSION['success'] = "Товар успешно добавлен";
                header("Location: ../pages/products.php");
                exit();
            } else {
                $errors[] = "Ошибка при добавлении товара";
            }
        } catch (PDOException $e) {
            $errors[] = "Ошибка базы данных: " . $e->getMessage();
        }
    }
}

// Получение списка категорий
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавление товара</title>
    <link rel="stylesheet" href="../accets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .add-product-section {
            padding: 40px 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        .add-product-container {
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
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #999;
        }
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
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
        .image-preview {
            max-width: 300px;
            margin-top: 10px;
            display: none;
        }
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
        select {
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
        select option {
            color: #333;
            background-color: white;
            padding: 10px;
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

    <div class="add-product-section">
        <div class="add-product-container">
            <h2>Добавление нового товара</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p style="color: #721c24;"><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Название товара:</label>
                    <input type="text" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                    <small style="color: #666; font-size: 0.9em; display: block; margin-top: 5px;">Поддерживается форматирование: **жирный текст**, *курсив*</small>
                </div>
                
                <div class="form-group">
                    <label for="description">Описание товара:</label>
                    <textarea id="description" name="description" required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                    <small style="color: #666; font-size: 0.9em; display: block; margin-top: 5px;">Поддерживается форматирование: **жирный текст**, *курсив*. Можно использовать эмодзи 😊</small>
                </div>
                
                <div class="form-group">
                    <label for="price">Цена:</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo isset($price) ? htmlspecialchars($price) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="category_id">Категория:</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Выберите категорию</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo isset($category_id) && $category_id == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="image">Изображение товара:</label>
                    <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(this)">
                    <img id="preview" class="image-preview">
                </div>
                
                <button type="submit" class="button">Добавить товар</button>
            </form>
        </div>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html> 