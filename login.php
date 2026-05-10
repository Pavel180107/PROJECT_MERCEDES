<?php
require_once 'config.php';
require_once 'modules/UserManager.php';

if (isset($_SESSION['auto_user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login']);
    $pass = $_POST['password'];
    $userMan = new UserManager();
    $userId = $userMan->authenticate($login, $pass);
    if ($userId) {
        $_SESSION['auto_user_id'] = $userId;
        $_SESSION['auto_user_login'] = $login;
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
    <title>Вход – Mercedes-Benz Elite</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container" style="max-width:500px; margin:3rem auto;">
    <h1>Вход в аккаунт</h1>
    <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="form-group">
            <label>Логин</label>
            <input type="text" name="login" required>
        </div>
        <div class="form-group">
            <label>Пароль</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn">Войти</button>
    </form>
    <div class="back-link">
        <a href="index.php">← Вернуться на главную</a>
    </div>
</div>
</body>
</html>