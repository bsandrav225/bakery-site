<?php
require_once 'config.php';

// Проверка авторизации
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

// Проверка прав администратора
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Требование авторизации
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Хеширование пароля
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Проверка пароля
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Безопасный вывод
function escape($data) {
    return htmlspecialchars(strip_tags((string)($data ?? '')), ENT_QUOTES, 'UTF-8');
}

// Загрузка файла
function uploadFile($file, $directory, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']) {
    $uploadDir = UPLOAD_DIR . $directory . '/';
    
    // Создаем директорию если не существует
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowedTypes)) {
        return ['success' => false, 'error' => 'Недопустимый тип файла'];
    }
    
    $filename = uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $directory . '/' . $filename];
    }
    
    return ['success' => false, 'error' => 'Ошибка загрузки файла'];
}

// Получение настроек
function getSetting($key) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : null;
}

// Обновление настроек
function updateSetting($key, $value) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    return $stmt->execute([$key, $value, $value]);
}

// Получение всех заявок
function getOrders($status = null) {
    global $pdo;
    $sql = "SELECT * FROM orders";
    if ($status) {
        $sql .= " WHERE status = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$status]);
    } else {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }
    return $stmt->fetchAll();
}

// Получение заявки по ID
function getOrder($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Статусы заявок
function getOrderStatuses() {
    return [
        'new' => 'Новая',
        'in_progress' => 'В работе',
        'measure_scheduled' => 'Готовится',
        'closed' => 'Закрыта'
    ];
}

// Типы заявок
function getOrderTypes() {
    return [
        'call' => 'Обратный звонок',
        'measure' => 'Предзаказ',
        'calculate' => 'Расчёт заказа',
        'order' => 'Заказ с сайта',
        'calculator' => 'Корпоративный заказ'
    ];
}

// Получение категорий
function getCategories($parentId = null) {
    global $pdo;
    $sql = "SELECT * FROM categories WHERE is_active = 1";
    if ($parentId !== null) {
        $sql .= " AND parent_id " . ($parentId === 0 ? "IS NULL" : "= ?");
    }
    $sql .= " ORDER BY sort_order ASC";
    $stmt = $pdo->prepare($sql);
    if ($parentId !== null && $parentId !== 0) {
        $stmt->execute([$parentId]);
    } else {
        $stmt->execute();
    }
    return $stmt->fetchAll();
}

// Получение товаров
function getProducts($categoryId = null) {
    global $pdo;
    $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.is_active = 1";
    if ($categoryId) {
        $sql .= " AND p.category_id = ?";
        $sql .= " ORDER BY p.sort_order ASC, p.id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$categoryId]);
    } else {
        $sql .= " ORDER BY p.sort_order ASC, p.id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }
    return $stmt->fetchAll();
}

// Получение товара по ID
function getProduct($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Получение материалов
function getMaterials($brand = null) {
    global $pdo;
    $sql = "SELECT * FROM materials WHERE is_active = 1";
    if ($brand) {
        $sql .= " AND brand = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$brand]);
    } else {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }
    return $stmt->fetchAll();
}

// Получение портфолио
function getPortfolio($limit = null) {
    global $pdo;
    $sql = "SELECT * FROM portfolio WHERE is_active = 1 ORDER BY sort_order ASC";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Получение отзывов
function getReviews($approved = true) {
    global $pdo;
    $sql = "SELECT * FROM reviews WHERE is_visible = 1";
    if ($approved) {
        $sql .= " AND is_approved = 1";
    }
    $sql .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Получение акций
function getActivePromotions() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM promotions WHERE is_active = 1 AND (end_date IS NULL OR end_date >= CURDATE()) ORDER BY sort_order ASC");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Получение статей
function getArticles($published = true) {
    global $pdo;
    $sql = "SELECT * FROM articles";
    if ($published) {
        $sql .= " WHERE is_published = 1";
    }
    $sql .= " ORDER BY published_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Получение страницы по slug
function getPage($slug) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ? AND is_visible = 1");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

// Генерация URL для админ-панели
function adminUrl($path = '') {
    return ADMIN_URL . ltrim($path, '/');
}

// Перенаправление
function redirect($url) {
    header('Location: ' . $url);
    exit();
}

// Сообщение об успехе/ошибке
function flashMessage($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

// Пагинация
function paginate($total, $perPage = 20, $currentPage = 1) {
    $totalPages = ceil($total / $perPage);
    $offset = ($currentPage - 1) * $perPage;
    return [
        'total' => $total,
        'perPage' => $perPage,
        'currentPage' => $currentPage,
        'totalPages' => $totalPages,
        'offset' => $offset,
        'hasPrev' => $currentPage > 1,
        'hasNext' => $currentPage < $totalPages
    ];
}

// Форматирование даты
function formatDate($date, $format = 'd.m.Y H:i') {
    return date($format, strtotime($date));
}

// Транслитерация для URL
function transliterate($text) {
    $cyr = ['а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я','А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П','Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я'];
    $lat = ['a','b','v','g','d','e','e','zh','z','i','y','k','l','m','n','o','p','r','s','t','u','f','h','ts','ch','sh','shch','','y','','e','yu','ya','A','B','V','G','D','E','E','Zh','Z','I','Y','K','L','M','N','O','P','R','S','T','U','F','H','Ts','Ch','Sh','Shch','','Y','','E','Yu','Ya'];
    return str_replace($cyr, $lat, $text);
}

function slugify($text) {
    $text = transliterate($text);
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}