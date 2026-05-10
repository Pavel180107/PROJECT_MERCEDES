<?php
session_start();
require_once 'config.php';
require_once 'modules/Database.php';
require_once 'modules/UserManager.php';

if (isset($_SESSION['auto_user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$loginInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginInput = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $userMan = new UserManager();
    $userId = $userMan->authenticate($loginInput, $password);
    if ($userId) {
        $_SESSION['auto_user_id'] = $userId;
        $_SESSION['auto_user_login'] = $loginInput;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Неверный логин или пароль';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход – Mercedes-Benz</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-container { max-width: 400px; margin: 3rem auto; padding: 2rem; background: #1a1a1a; border-radius: 12px; }
    </style>
</head>
<body>
<div class="login-container">
    <h1>Вход в аккаунт</h1>
    <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="form-group">
            <label>Логин</label>
            <input type="text" name="login" value="<?= htmlspecialchars($loginInput) ?>" required>
        </div>
        <div class="form-group">
            <label>Пароль</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn">Войти</button>
    </form>
    <div style="text-align:center; margin-top:1rem;">
        <a href="index.php">← Вернуться к форме</a>
    </div>
</div>
</body>
</html>