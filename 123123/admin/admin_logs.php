<?php
session_start();
require_once '../config.php';

// Проверка прав администратора
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header("Location: ../pages/products.php");
    exit();
}

// Получение логов из файла
$log_file = '../logs/system.log';
$logs = [];

if (file_exists($log_file)) {
    $logs = array_reverse(file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
}

// Фильтрация логов
$filter = isset($_GET['filter']) ? strtolower($_GET['filter']) : '';
if ($filter) {
    $logs = array_filter($logs, function($log) use ($filter) {
        return strpos(strtolower($log), $filter) !== false;
    });
}

// Пагинация
$per_page = 50;
$total_logs = count($logs);
$total_pages = ceil($total_logs / $per_page);
$current_page = isset($_GET['page']) ? max(1, min($total_pages, intval($_GET['page']))) : 1;
$offset = ($current_page - 1) * $per_page;
$logs = array_slice($logs, $offset, $per_page);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Логи системы</title>
    <link rel="stylesheet" href="../accets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .logs-container {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            font-family: monospace;
            white-space: pre-wrap;
            max-height: 600px;
            overflow-y: auto;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .log-entry {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            color: #333;
            font-size: 14px;
            line-height: 1.5;
        }
        .log-entry:hover {
            background-color: #f8f9fa;
        }
        .search-form {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        .search-form input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 300px;
            font-size: 14px;
        }
        .search-form button {
            padding: 10px 20px;
            background-color: #2c3e50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .search-form button:hover {
            background-color: #1a252f;
        }
        .pagination {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: center;
        }
        .pagination a {
            padding: 8px 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #2c3e50;
            transition: all 0.3s;
        }
        .pagination a:hover {
            background-color: #f8f9fa;
            border-color: #2c3e50;
        }
        .pagination .active {
            background-color: #2c3e50;
            color: white;
            border-color: #2c3e50;
        }
        .log-level {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-right: 10px;
        }
        .log-level-error {
            background-color: #dc3545;
            color: white;
        }
        .log-level-warning {
            background-color: #ffc107;
            color: black;
        }
        .log-level-info {
            background-color: #17a2b8;
            color: white;
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
        <h1>Логи системы</h1>
        
        <form class="search-form" method="GET">
            <input type="text" name="filter" placeholder="Поиск в логах..." value="<?php echo htmlspecialchars($filter); ?>">
            <button type="submit">Поиск</button>
        </form>
        
        <div class="logs-container">
            <?php foreach ($logs as $log): ?>
                <div class="log-entry">
                    <?php
                    // Определение уровня лога и добавление соответствующего стиля
                    if (strpos($log, 'ERROR') !== false) {
                        echo '<span class="log-level log-level-error">ERROR</span>';
                    } elseif (strpos($log, 'WARNING') !== false) {
                        echo '<span class="log-level log-level-warning">WARNING</span>';
                    } elseif (strpos($log, 'INFO') !== false) {
                        echo '<span class="log-level log-level-info">INFO</span>';
                    }
                    echo htmlspecialchars($log);
                    ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($current_page > 1): ?>
                    <a href="?page=<?php echo $current_page - 1; ?>&filter=<?php echo urlencode($filter); ?>">Предыдущая</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&filter=<?php echo urlencode($filter); ?>" 
                       class="<?php echo $i == $current_page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($current_page < $total_pages): ?>
                    <a href="?page=<?php echo $current_page + 1; ?>&filter=<?php echo urlencode($filter); ?>">Следующая</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 