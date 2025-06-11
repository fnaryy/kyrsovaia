<?php
session_start();
require_once '../config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $telegram = trim($_POST['telegram']);
    $vk = trim($_POST['vk']);
    
    // Проверка паролей
    if ($password !== $confirm_password) {
        $error = 'Пароли не совпадают';
    } else {
        // Проверка существования email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $error = 'Этот email уже зарегистрирован';
        } else {
            // Обработка загрузки аватара
            $avatar_path = null;
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['avatar']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed)) {
                    $upload_dir = '../uploads/avatars/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $new_filename = uniqid() . '.' . $ext;
                    $avatar_path = 'uploads/avatars/' . $new_filename;
                    
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_dir . $new_filename)) {
                        // Файл успешно загружен
                    } else {
                        $error = 'Ошибка при загрузке аватара';
                    }
                } else {
                    $error = 'Недопустимый формат файла. Разрешены: jpg, jpeg, png, gif';
                }
            }
            
            if (empty($error)) {
                // Регистрация пользователя
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, telegram, vk, avatar) VALUES (?, ?, ?, ?, ?, ?)");
                
                if ($stmt->execute([$name, $email, $hashed_password, $telegram, $vk, $avatar_path])) {
                    // Получаем ID нового пользователя
                    $user_id = $pdo->lastInsertId();
                    
                    // Автоматически входим в систему
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_role'] = 0; // По умолчанию обычный пользователь
                    
                    $_SESSION['success'] = "Регистрация успешна! Добро пожаловать!";
                    header("Location: ../pages/profile.php");
                    exit();
                } else {
                    $error = 'Ошибка при регистрации';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link rel="stylesheet" href="../accets/css/style.css">
    <style>
        .register-section {
            padding: 40px 20px;
            max-width: 500px;
            margin: 40px auto 0;
        }
        .register-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            width: 100%;
            position: relative;
            animation: modalFadeIn 0.3s ease;
        }
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .close-button {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 24px;
            color: #666;
            cursor: pointer;
            padding: 5px;
            line-height: 1;
        }
        .close-button:hover {
            color: #333;
        }
        .register-title {
            font-size: 2em;
            font-weight: 600;
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
            color: #333;
        }
        .form-group input:focus {
            border-color: #2c3e50;
            outline: none;
        }
        .form-group input::placeholder {
            color: #999;
        }
        .avatar-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin: 10px 0;
            display: none;
        }
        .social-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .register-button {
            width: 100%;
            padding: 12px;
            background-color: #2c3e50;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .register-button:hover {
            background-color: #34495e;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        .login-link a {
            color: #2c3e50;
            text-decoration: none;
            font-weight: 500;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
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

    <div class="register-section">
        <div class="register-container">
            <button class="close-button" onclick="window.location.href='index.html'">&times;</button>
            <h1 class="register-title">Регистрация</h1>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Имя</label>
                    <input type="text" id="name" name="name" required placeholder="Введите ваше имя">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required placeholder="Введите ваш email">
                </div>

                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" required placeholder="Введите пароль">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Подтвердите пароль</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Повторите пароль">
                </div>

                <div class="form-group">
                    <label for="avatar">Аватар (необязательно)</label>
                    <input type="file" id="avatar" name="avatar" accept="image/*" onchange="previewAvatar(this)">
                    <img id="avatar-preview" class="avatar-preview" src="#" alt="Preview">
                </div>

                <div class="form-group">
                    <label>Социальные сети (необязательно)</label>
                    <div class="social-fields">
                        <input type="text" name="telegram" placeholder="Telegram (@username)" pattern="@[a-zA-Z0-9_]{5,32}">
                        <input type="text" name="vk" placeholder="VK (id или username)">
                    </div>
                </div>

                <button type="submit" class="register-button">Зарегистрироваться</button>
            </form>

            <div class="login-link">
                Уже есть аккаунт? <a href="login.php">Войти</a>
            </div>
        </div>
    </div>

    <script>
        function previewAvatar(input) {
            const preview = document.getElementById('avatar-preview');
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