<?php
require_once 'config.php';
require_once 'functions.php';

// Проверка авторизации
requireLogin();

// Получение статистики
$stats = [
    'orders' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'new_orders' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'new'")->fetchColumn(),
    'products' => $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn(),
    'portfolio' => $pdo->query("SELECT COUNT(*) FROM portfolio WHERE is_active = 1")->fetchColumn(),
    'reviews' => $pdo->query("SELECT COUNT(*) FROM reviews WHERE is_approved = 0")->fetchColumn(),
    'articles' => $pdo->query("SELECT COUNT(*) FROM articles WHERE is_published = 1")->fetchColumn(),
];

// Получение последних заявок
$recentOrders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Получение последних отзывов
$recentReviews = $pdo->query("SELECT * FROM reviews ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Получение активных акций
$activePromotions = $pdo->query("SELECT * FROM promotions WHERE is_active = 1 AND (end_date IS NULL OR end_date >= CURDATE())")->fetchAll();

// Статистика по месяцам
$monthlyStats = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM orders GROUP BY month ORDER BY month DESC LIMIT 12")->fetchAll();

// Текущий раздел
$page = $_GET['page'] ?? 'dashboard';

// Заголовок
$pageTitle = 'Панель управления';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель — Домашний хлеб</title>
    <link rel="stylesheet" href="css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Onest:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="admin-wrapper">
        <aside class="admin-sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-wheat-awn"></i>
                <span>Админ-панель</span>
            </div>
            
            <nav class="sidebar-nav">
                <a href="?page=dashboard" class="<?= $page === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-chart-simple"></i>
                    <span>Обзор</span>
                </a>
                <a href="?page=orders" class="<?= $page === 'orders' ? 'active' : '' ?>">
                    <i class="fas fa-list-check"></i>
                    <span>Заявки</span>
                    <?php if ($stats['new_orders'] > 0): ?>
                        <span class="badge"><?= $stats['new_orders'] ?></span>
                    <?php endif; ?>
                </a>
                <a href="?page=products" class="<?= $page === 'products' ? 'active' : '' ?>">
                    <i class="fas fa-boxes-stacked"></i>
                    <span>Каталог</span>
                </a>
                <a href="?page=portfolio" class="<?= $page === 'portfolio' ? 'active' : '' ?>">
                    <i class="fas fa-images"></i>
                    <span>Портфолио</span>
                </a>
                <a href="?page=reviews" class="<?= $page === 'reviews' ? 'active' : '' ?>">
                    <i class="fas fa-star"></i>
                    <span>Отзывы</span>
                    <?php if ($stats['reviews'] > 0): ?>
                        <span class="badge"><?= $stats['reviews'] ?></span>
                    <?php endif; ?>
                </a>
                <a href="?page=articles" class="<?= $page === 'articles' ? 'active' : '' ?>">
                    <i class="fas fa-newspaper"></i>
                    <span>Статьи</span>
                </a>
                <a href="?page=promotions" class="<?= $page === 'promotions' ? 'active' : '' ?>">
                    <i class="fas fa-percent"></i>
                    <span>Акции</span>
                </a>
                <a href="?page=pages" class="<?= $page === 'pages' ? 'active' : '' ?>">
                    <i class="fas fa-file-lines"></i>
                    <span>Страницы</span>
                </a>
                <a href="?page=settings" class="<?= $page === 'settings' ? 'active' : '' ?>">
                    <i class="fas fa-gear"></i>
                    <span>Настройки</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <span class="user-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?></span>
                    <div>
                        <div class="user-name"><?= $_SESSION['full_name'] ?? 'Администратор' ?></div>
                        <div class="user-role"><?= $_SESSION['role'] ?? 'admin' ?></div>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Выход</span>
                </a>
            </div>
        </aside>
        
        <main class="admin-main">
            <div class="admin-header">
                <div class="header-left">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1><?= $pageTitle ?></h1>
                </div>
                <div class="header-right">
                    <a href="<?= SITE_URL ?>" target="_blank" class="btn btn-outline">
                        <i class="fas fa-globe"></i>
                        <span>Перейти на сайт</span>
                    </a>
                </div>
            </div>
            
            <div class="admin-content">
                <?php
                // Включение соответствующего раздела
                $pageFile = __DIR__ . '/includes/' . $page . '.php';
                if (file_exists($pageFile)) {
                    include $pageFile;
                } else {
                    include 'includes/dashboard.php';
                }
                ?>
            </div>
        </main>
    </div>
    
    <script src="js/admin.js"></script>
</body>
</html>