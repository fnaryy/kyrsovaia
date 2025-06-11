<?php
session_start();
require_once '../config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Получение данных пользователя
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Проверка роли администратора
$is_admin = isset($user['role']) && $user['role'] == 1;

// Обновляем роль в сессии
$_SESSION['user_role'] = $user['role'];

// Получаем отзывы о пользователе
$stmt = $pdo->prepare("
    SELECT r.*, u.name as reviewer_name, u.avatar as reviewer_avatar
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.seller_id = ?
    ORDER BY r.created_at DESC
    LIMIT 3
");
$stmt->execute([$_SESSION['user_id']]);
$reviews = $stmt->fetchAll();

// Получаем средний рейтинг
$stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE seller_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$avg_rating = $stmt->fetch()['avg_rating'] ?? 0;

// Обработка обновления профиля
$errors = [];
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    $telegram = trim($_POST['telegram']);
    $vk = trim($_POST['vk']);
    
    // Обработка загрузки аватара
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['avatar']['type'], $allowed_types)) {
            $errors[] = "Недопустимый формат файла. Разрешены только JPG, PNG и GIF.";
        } elseif ($_FILES['avatar']['size'] > $max_size) {
            $errors[] = "Размер файла не должен превышать 5MB.";
        } else {
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($ext, $allowed_extensions)) {
                $upload_dir = '../uploads/avatars/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $new_filename = uniqid() . '.' . $ext;
                $avatar_path = 'uploads/avatars/' . $new_filename;
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_dir . $new_filename)) {
                    // Удаляем старый аватар, если он существует
                    if (!empty($user['avatar']) && file_exists($user['avatar'])) {
                        unlink($user['avatar']);
                    }
                    $avatar = $avatar_path;
                } else {
                    $errors[] = "Ошибка при загрузке файла.";
                }
            } else {
                $errors[] = "Недопустимый формат файла. Разрешены только JPG, PNG и GIF.";
            }
        }
    }
    
    // Валидация
    if (empty($name)) {
        $errors[] = "Имя обязательно для заполнения";
    }
    
    if (empty($email)) {
        $errors[] = "Email обязателен для заполнения";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Неверный формат email";
    }
    
    // Валидация социальных сетей
    if (!empty($telegram) && !preg_match('/^@?[a-zA-Z0-9_]{5,32}$/', $telegram)) {
        $errors[] = "Неверный формат Telegram. Используйте @username или username";
    }
    
    if (!empty($vk) && !preg_match('/^[a-zA-Z0-9._-]{2,32}$/', $vk)) {
        $errors[] = "Неверный формат VK. Используйте короткое имя пользователя";
    }
    
    // Проверка email на уникальность
    if ($email != $user['email']) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Этот email уже используется другим пользователем";
        }
    }
    
    // Проверка нового пароля
    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            $errors[] = "Новый пароль должен содержать минимум 6 символов";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "Новые пароли не совпадают";
        }
    }
    
    if (empty($errors)) {
        $update_data = [];
        $sql = "UPDATE users SET name = ?, email = ?, telegram = ?, vk = ?";
        $update_data[] = $name;
        $update_data[] = $email;
        $update_data[] = $telegram;
        $update_data[] = $vk;
        
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql .= ", password = ?";
            $update_data[] = $hashed_password;
        }
        
        if (isset($avatar)) {
            $sql .= ", avatar = ?";
            $update_data[] = $avatar;
        }
        
        $sql .= " WHERE id = ?";
        $update_data[] = $user_id;
        
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($update_data)) {
            $success = true;
            $_SESSION['success'] = "Профиль успешно обновлен";
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            header("Location: profile.php");
            exit();
        } else {
            $errors[] = "Ошибка при обновлении профиля";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль пользователя</title>
    <link rel="stylesheet" href="../accets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .profile-section {
            padding: 40px 20px;
            max-width: 1200px;
            margin: 40px auto 0;
            min-height: calc(100vh - 60px);
        }
        .profile-container {
            display: flex;
            gap: 30px;
        }
        .profile-sidebar {
            width: 300px;
            flex-shrink: 0;
        }
        .profile-content {
            flex: 1;
        }
        .profile-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
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
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
            color: #333;
            background-color: white;
        }
        .form-group input::placeholder {
            color: #999;
        }
        .form-group input:focus {
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
        .user-info {
            margin-bottom: 30px;
        }
        .user-info p {
            margin: 10px 0;
            color: #333;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .user-info strong {
            color: #2c3e50;
            margin-right: 10px;
        }
        .admin-badge {
            background: #28a745;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .admin-panel {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 12px;
            margin-top: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .admin-panel h3 {
            color: #2c3e50;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #2c3e50;
            font-size: 1.5em;
            text-align: center;
        }
        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card i {
            font-size: 2em;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .stat-card h4 {
            color: #666;
            margin-bottom: 10px;
            font-size: 1.1em;
        }
        .stat-card p {
            font-size: 2em;
            font-weight: bold;
            color: #2c3e50;
            margin: 0;
        }
        .admin-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .admin-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px;
            background: #2c3e50;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1em;
        }
        .admin-button i {
            font-size: 1.2em;
        }
        .admin-button:hover {
            background: #1a252f;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .avatar-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .avatar-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 3px solid #2c3e50;
        }
        .avatar-upload {
            display: inline-block;
            padding: 10px 20px;
            background-color: #2c3e50;
            color: white !important;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        .avatar-upload:hover {
            background-color: #1a252f;
        }
        .avatar-upload input[type="file"] {
            display: none;
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
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
        .profile-info {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .profile-info p {
            color: #666;
            margin: 5px 0;
        }
        .logout-button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s ease;
            text-decoration: none;
            margin-top: 20px;
        }
        .logout-button:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .reviews-section {
            margin-top: 30px;
        }
        .reviews-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .reviews-title {
            font-size: 1.2em;
            font-weight: 600;
            color: #333;
        }
        .view-all-reviews {
            color: #2c3e50;
            text-decoration: none;
            font-size: 0.9em;
        }
        .view-all-reviews:hover {
            text-decoration: underline;
        }
        .reviews-container {
            display: grid;
            gap: 15px;
        }
        .review-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .review-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
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
            color: #666;
            font-size: 0.8em;
        }
        .review-rating {
            color: #ffc107;
            margin-bottom: 5px;
        }
        .review-comment {
            color: #666;
            font-size: 0.9em;
            line-height: 1.4;
        }
        .seller-rating {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        .stars {
            color: #ffc107;
        }
        .rating-value {
            color: #666;
            font-size: 0.9em;
        }
        .profile-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        .edit-profile-button,
        .my-products-button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #2c3e50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .edit-profile-button:hover,
        .my-products-button:hover {
            background-color: #1a252f;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .my-products-button {
            background-color: #28a745;
        }
        .my-products-button:hover {
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

    <div class="profile-section">
        <div class="profile-container">
            <div class="profile-sidebar">
                <div class="profile-card">
                    <div class="avatar-container">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="../<?php echo htmlspecialchars($user['avatar']); ?>" alt="Аватар" class="avatar-preview">
                        <?php else: ?>
                            <img src="../uploads/avatars/default.png" alt="Аватар" class="avatar-preview">
                        <?php endif; ?>
                    </div>
                    <div class="user-info">
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1): ?>
                            <div class="admin-badge">Администратор</div>
                        <?php endif; ?>
                        <p><strong>Имя:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><strong>Роль:</strong> <?php echo $user['role'] == 1 ? 'Администратор' : 'Пользователь'; ?></p>
                        <p><strong>Дата регистрации:</strong> <?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></p>
                        <?php if (!empty($user['telegram'])): ?>
                            <p><strong>Telegram:</strong> <a href="https://t.me/<?php echo ltrim(htmlspecialchars($user['telegram']), '@'); ?>" target="_blank"><?php echo htmlspecialchars($user['telegram']); ?></a></p>
                        <?php endif; ?>
                        <?php if (!empty($user['vk'])): ?>
                            <p><strong>VK:</strong> <a href="https://vk.com/<?php echo htmlspecialchars($user['vk']); ?>" target="_blank"><?php echo htmlspecialchars($user['vk']); ?></a></p>
                        <?php endif; ?>
                        <a href="../auth/logout.php" class="button" style="background-color: #dc3545; margin-top: 20px; display: block; text-align: center; text-decoration: none;">Выйти</a>
                    </div>
                </div>
            </div>

            <div class="profile-content">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        Профиль успешно обновлен!
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="profile-card">
                    <h2>Редактирование профиля</h2>
                    <form method="POST" action="profile.php" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="avatar">Аватар</label>
                            <label class="avatar-upload">
                                Выбрать файл
                                <input type="file" name="avatar" id="avatar" accept="image/*">
                            </label>
                        </div>
                        <div class="form-group">
                            <label for="name">Имя:</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="telegram">Telegram (например: @username):</label>
                            <input type="text" id="telegram" name="telegram" value="<?php echo htmlspecialchars($user['telegram'] ?? ''); ?>" placeholder="@username">
                        </div>

                        <div class="form-group">
                            <label for="vk">VK (короткое имя пользователя):</label>
                            <input type="text" id="vk" name="vk" value="<?php echo htmlspecialchars($user['vk'] ?? ''); ?>" placeholder="username">
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">Новый пароль (оставьте пустым, если не хотите менять):</label>
                            <input type="password" id="new_password" name="new_password">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Подтвердите новый пароль:</label>
                            <input type="password" id="confirm_password" name="confirm_password">
                        </div>
                        
                        <button type="submit" class="button">Сохранить изменения</button>
                    </form>
                </div>

                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1): ?>
                    <div class="profile-card admin-panel">
                        <h3>Панель администратора</h3>
                        
                        <div class="admin-stats">
                            <?php
                            // Получаем статистику
                            $users_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                            $products_count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
                            $categories_count = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
                            ?>
                            
                            <div class="stat-card">
                                <i class="fas fa-users"></i>
                                <h4>Пользователей</h4>
                                <p><?php echo $users_count; ?></p>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-box"></i>
                                <h4>Товаров</h4>
                                <p><?php echo $products_count; ?></p>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-tags"></i>
                                <h4>Категорий</h4>
                                <p><?php echo $categories_count; ?></p>
                            </div>
                        </div>

                        <div class="admin-actions">
                            <a href="../admin/manage_users.php" class="admin-button">
                                <i class="fas fa-user-cog"></i>
                                <span>Управление пользователями</span>
                            </a>
                            <a href="../admin/manage_products.php" class="admin-button">
                                <i class="fas fa-box-open"></i>
                                <span>Управление товарами</span>
                            </a>
                            <a href="../admin/manage_categories.php" class="admin-button">
                                <i class="fas fa-tag"></i>
                                <span>Управление категориями</span>
                            </a>
                            <a href="../admin/admin_logs.php" class="admin-button">
                                <i class="fas fa-history"></i>
                                <span>Логи системы</span>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="profile-actions">

                    <a href="../seller/products.php" class="my-products-button">
                        <i class="fas fa-box"></i>
                        Редактировать товары
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="section-why">
        <div class="why-content">
            <h1 class="why-title">Управление профилем</h1>
            <p class="why-text">
                Здесь вы можете управлять своими данными и настройками аккаунта.<br>
                <?php if ($is_admin): ?>
                    Как администратор, вы имеете доступ к дополнительным функциям управления системой.
                <?php endif; ?>
            </p>
        </div>
        <div class="divider"></div>
        <footer class="footer-bold">Products Without limits — ваш мир без границ. ✅</footer>
    </div>

    <div class="reviews-section">
        <div class="reviews-header">
            <h3 class="reviews-title">Последние отзывы</h3>
            <a href="seller_reviews.php?id=<?php echo $_SESSION['user_id']; ?>" class="view-all-reviews">Все отзывы</a>
        </div>
        <div class="reviews-container">
            <?php if (empty($reviews)): ?>
                <p>Пока нет отзывов.</p>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <img src="<?php echo !empty($review['reviewer_avatar']) ? '../' . htmlspecialchars($review['reviewer_avatar']) : '../uploads/avatars/default.png'; ?>" 
                                 alt="Аватар пользователя" class="reviewer-avatar">
                            <div>
                                <div class="reviewer-name"><?php echo htmlspecialchars($review['reviewer_name']); ?></div>
                                <div class="review-date"><?php echo date('d.m.Y H:i', strtotime($review['created_at'])); ?></div>
                            </div>
                        </div>
                        <div class="review-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star" <?php echo $i <= $review['rating'] ? '' : 'style="color: #ddd;"'; ?>></i>
                            <?php endfor; ?>
                        </div>
                        <div class="review-comment">
                            <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html> 