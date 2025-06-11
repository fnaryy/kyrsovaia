<?php
session_start();
require_once '../config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Проверка учетных данных
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        
        // Проверяем роль пользователя и перенаправляем соответственно
        if ($user['role'] == 1) {
            header("Location: ../admin/admin_panel.php");
        } else {
            header("Location: ../pages/profile.php");
        }
        exit();
    } else {
        $error = 'Неверный email или пароль';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
    <link rel="stylesheet" href="../accets/css/style.css">
    <style>
        .login-section {
            padding: 40px 20px;
            max-width: 500px;
            margin: 40px auto 0;
        }
        .login-container {
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
        .login-title {
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
        .login-button {
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
        .login-button:hover {
            background-color: #34495e;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        .register-link a {
            color: #2c3e50;
            text-decoration: none;
            font-weight: 500;
        }
        .register-link a:hover {
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

    <div class="login-section">
        <div class="login-container">
            <button class="close-button" onclick="window.location.href='index.html'">&times;</button>
            <h1 class="login-title">Вход</h1>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required placeholder="Введите ваш email">
                </div>

                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" required placeholder="Введите пароль">
                </div>

                <button type="submit" class="login-button">Войти</button>
            </form>

            <div class="register-link">
                Нет аккаунта? <a href="register.php">Зарегистрироваться</a>
            </div>
        </div>
    </div>
</body>
</html> 