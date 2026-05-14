<?php
session_start();
require_once 'config.php';
require_once 'modules/UserManager.php';

if (isset($_SESSION['auto_user_id'])) {
    $redirect = $_GET['redirect'] ?? 'profile';
    header('Location: ' . ($redirect === 'profile' ? 'profile.php' : 'index.php'));
    exit;
}

$error = '';
$loginInput = '';
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginInput = trim($_POST['login']);
    $password = $_POST['password'];
    $userMan = new UserManager();
    $userId = $userMan->authenticate($loginInput, $password);
    if ($userId) {
        $_SESSION['auto_user_id'] = $userId;
        $userData = $userMan->getUserById($userId);
        $_SESSION['auto_user_login'] = $userData['login'];
        header('Location: ' . ($_POST['redirect'] ?? 'profile.php'));
        exit;
    } else {
        $error = 'Неверный логин, email, телефон или пароль.';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход – Mercedes Elite</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-container { max-width: 400px; margin: 3rem auto; padding: 2rem; background: #1a1a1a; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.3); }
        .error-message { background: #c62828; color: white; padding: 0.8rem; border-radius: 8px; margin-bottom: 1rem; text-align: center; }
        .hint { text-align: center; margin-top: 1rem; font-size: 0.85rem; color: #aaa; }
        .hint a { color: #00A0E3; text-decoration: none; }
        .hint a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="login-container">
    <h1 style="text-align:center;">Вход в аккаунт</h1>
    <p style="text-align:center; margin-bottom:1.5rem;">Введите логин, email или номер телефона</p>

    <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
        <div class="form-group">
            <label>Логин / Email / Телефон</label>
            <input type="text" name="login" value="<?= htmlspecialchars($loginInput) ?>" required autofocus>
        </div>
        <div class="form-group">
            <label>Пароль</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn" style="width:100%;">Войти</button>
    </form>

    <div class="hint">
        <a href="index.php">← Вернуться к форме заказа</a>
    </div>
    <div class="hint">
        Нет аккаунта? Заполните форму на главной странице — логин и пароль будут сгенерированы автоматически.
    </div>
</div>
</body>
</html>