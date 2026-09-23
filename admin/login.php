<?php
require_once 'config.php';
require_once 'functions.php';

// Если уже авторизован, перенаправляем в админку
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && verifyPassword($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            // Обновляем время последнего входа
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
            
            header('Location: index.php');
            exit();
        } else {
            $error = 'Неверное имя пользователя или пароль';
        }
    } else {
        $error = 'Заполните все поля';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в панель управления</title>
    <link rel="stylesheet" href="css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Onest:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-brand">
                <i class="fas fa-wheat-awn"></i>
                <h1>Домашний хлеб</h1>
                <p>Панель управления</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Имя пользователя</label>
                    <div class="input-wrap">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" placeholder="admin" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <div class="input-wrap">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Войти</span>
                </button>
            </form>
            
            <div class="login-footer">
                <p>© 2026 Пекарня «Домашний хлеб»</p>
            </div>
        </div>
    </div>
</body>
</html>