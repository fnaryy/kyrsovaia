<?php
require_once '../config.php';

// Email пользователя, которого нужно сделать администратором
$email = 'ваш_email@example.com'; // Замените на нужный email

try {
    $stmt = $pdo->prepare("UPDATE users SET role = 1 WHERE email = ?");
    $result = $stmt->execute([$email]);
    
    if ($result) {
        echo "Пользователь с email $email успешно назначен администратором";
    } else {
        echo "Ошибка при назначении администратора";
    }
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
?>
 