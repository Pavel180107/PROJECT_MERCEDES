<?php
require_once 'config.php';
require_once 'modules/CarModels.php';
require_once 'modules/UserManager.php';
require_once 'modules/OrderManager.php';

// Обрабатываем POST и редиректим
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

$full_name = trim($_POST['full_name']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);
$consent = isset($_POST['consent']);
$model_id = (int)$_POST['model_id'];
$package_id = !empty($_POST['package_id']) ? (int)$_POST['package_id'] : null;
$services = isset($_POST['services']) ? array_map('intval', $_POST['services']) : [];
$total_price = (float)$_POST['total_price']; // можно пересчитать на сервере

$userMan = new UserManager();
$orderMan = new OrderManager();

if (isset($_SESSION['auto_user_id'])) {
    $userId = $_SESSION['auto_user_id'];
    $lastOrder = $orderMan->getLastOrderByUser($userId);
    if ($lastOrder) {
        $orderMan->updateOrder($lastOrder['id'], $model_id, $package_id, $services, $total_price);
        $_SESSION['flash'] = 'Заказ обновлён';
    } else {
        $orderMan->createOrder($userId, $model_id, $package_id, $services, $total_price);
        $_SESSION['flash'] = 'Заказ создан';
    }
    header('Location: /');
    exit;
} else {
    $newUser = $userMan->createUser($full_name, $email, $phone, $consent);
    if ($newUser) {
        $_SESSION['auto_user_id'] = $newUser['id'];
        $_SESSION['auto_user_login'] = $newUser['login'];
        $orderMan->createOrder($newUser['id'], $model_id, $package_id, $services, $total_price);
        $_SESSION['flash'] = "Заказ создан! Ваш логин: {$newUser['login']}, пароль: {$newUser['password']}";
    } else {
        $_SESSION['flash'] = 'Ошибка при создании заказа';
    }
    header('Location: /');
    exit;
}