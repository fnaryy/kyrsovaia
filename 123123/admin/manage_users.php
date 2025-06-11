<?php
session_start();
require_once '../config.php';

// Проверка авторизации и прав администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: ../pages/products.php");
    exit();
}

// Обработка действий с пользователями
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $user_id = $_POST['user_id'];
        
        switch ($_POST['action']) {
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND id != ?");
                $stmt->execute([$user_id, $_SESSION['user_id']]);
                break;
                
            case 'toggle_role':
                $stmt = $pdo->prepare("UPDATE users SET role = NOT role WHERE id = ? AND id != ?");
                $stmt->execute([$user_id, $_SESSION['user_id']]);
                break;
        }
    }
}

// Получение списка пользователей
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="../accets/css/style.css">
    <link rel="stylesheet" href="../accets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .users-table th, .users-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .users-table th {
            background-color: #2c3e50;
            color: white;
        }
        .users-table tr {
            transition: all 0.3s ease;
        }
        .users-table tr:hover {
            background-color: #2c3e50;
        }
        .users-table tr:hover td {
            color: #ffffff;
        }
        .users-table tr:hover .btn {
            background-color: #ffffff;
            color: #2c3e50;
        }
        .users-table tr:hover .btn-warning {
            background-color: #ffffff;
            color: #ffc107;
        }
        .users-table tr:hover .btn-danger {
            background-color: #ffffff;
            color: #dc3545;
        }
        .users-table tr:hover .admin-badge {
            background-color: #ffffff;
            color: #28a745;
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
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .btn-warning {
            background-color: #ffc107;
            color: black;
        }
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        .admin-badge {
            background-color: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .admin-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
            color: #333;
        }
        .admin-item:hover {
            background-color: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .admin-item:hover .item-name,
        .admin-item:hover .item-email,
        .admin-item:hover .item-role {
            color: #2c3e50;
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
        .modal-content input[type="email"],
        .modal-content input[type="password"],
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
        .modal-content input[type="email"]:focus,
        .modal-content input[type="password"]:focus,
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
        <h1>Управление пользователями</h1>

        
        <table class="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Аватар</th>
                    <th>Имя</th>
                    <th>Email</th>
                    <th>Роль</th>
                    <th>Дата регистрации</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                        <td>
                            <?php if (!empty($user['avatar'])): ?>
                                <img src="../<?php echo htmlspecialchars($user['avatar']); ?>" alt="Аватар" class="user-avatar">
                            <?php else: ?>
                                <img src="../uploads/avatars/default.png" alt="Аватар" class="user-avatar">
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <?php if ($user['role'] == 1): ?>
                                <span class="admin-badge">Администратор</span>
                            <?php else: ?>
                                Пользователь
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                        <td class="action-buttons">
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="action" value="toggle_role">
                                    <button type="submit" class="btn btn-warning">
                                        <?php echo $user['role'] == 1 ? 'Снять админа' : 'Назначить админом'; ?>
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Вы уверены, что хотите удалить этого пользователя?');">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-danger">Удалить</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
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
        
        function showEditModal(user) {
            document.getElementById('edit_id').value = user.id;
            document.getElementById('edit_name').value = user.name;
            document.getElementById('edit_email').value = user.email;
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