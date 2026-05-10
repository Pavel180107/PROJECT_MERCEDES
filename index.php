<?php
// index.php – единая точка входа (эмуляция PUT через _method)
session_start();

require_once 'config.php';
require_once 'modules/Database.php';
require_once 'modules/CarModels.php';
require_once 'modules/UserManager.php';
require_once 'modules/OrderManager.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

function sendJson($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Проверяем, AJAX ли запрос
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($isAjax && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) sendJson(['error' => 'Invalid JSON'], 400);

    // Эмуляция PUT через поле _method
    $realMethod = 'POST';
    if (isset($input['_method']) && $input['_method'] === 'PUT') {
        $realMethod = 'PUT';
    }

    // Валидация обязательных полей
    $required = ['full_name', 'email', 'phone', 'consent', 'model_id', 'package_id', 'services', 'total_price'];
    foreach ($required as $field) {
        if (!isset($input[$field])) sendJson(['error' => "Missing field: $field"], 422);
    }

    $full_name = trim($input['full_name']);
    $email = trim($input['email']);
    $phone = trim($input['phone']);
    $consent = (bool)$input['consent'];
    $modelId = (int)$input['model_id'];
    $packageId = !empty($input['package_id']) ? (int)$input['package_id'] : null;
    $serviceIds = array_map('intval', $input['services']);
    $totalPrice = (float)$input['total_price'];

    $userMan = new UserManager();
    $orderMan = new OrderManager();
    $userId = $_SESSION['auto_user_id'] ?? null;

    if ($realMethod === 'POST') {
        // СОЗДАНИЕ НОВОГО ЗАКАЗА
        if (!$userId) {
            // Неавторизован – создаём пользователя
            $newUser = $userMan->createUser($full_name, $email, $phone, $consent);
            if (!$newUser) sendJson(['error' => 'Failed to create user'], 500);
            $userId = $newUser['id'];
            $_SESSION['auto_user_id'] = $userId;
            $_SESSION['auto_user_login'] = $newUser['login'];
            $orderId = $orderMan->createOrder($userId, $modelId, $packageId, $serviceIds, $totalPrice);
            if ($orderId) {
                sendJson([
                    'message' => 'Order created',
                    'order_id' => $orderId,
                    'login' => $newUser['login'],
                    'password' => $newUser['password']
                ], 201);
            } else {
                sendJson(['error' => 'Failed to create order'], 500);
            }
        } else {
            // Авторизован – просто создаём заказ
            $orderId = $orderMan->createOrder($userId, $modelId, $packageId, $serviceIds, $totalPrice);
            if ($orderId) {
                sendJson(['message' => 'Order created', 'order_id' => $orderId], 201);
            } else {
                sendJson(['error' => 'Failed to create order'], 500);
            }
        }
    } 
    elseif ($realMethod === 'PUT') {
        // ОБНОВЛЕНИЕ СУЩЕСТВУЮЩЕГО ЗАКАЗА
        if (!$userId) sendJson(['error' => 'Unauthorized'], 401);
        $lastOrder = $orderMan->getLastOrderByUser($userId);
        if (!$lastOrder) {
            // Если заказа нет – создаём новый (как при POST)
            $orderId = $orderMan->createOrder($userId, $modelId, $packageId, $serviceIds, $totalPrice);
            if ($orderId) {
                sendJson(['message' => 'Order created (no existing)', 'order_id' => $orderId], 201);
            } else {
                sendJson(['error' => 'Failed to create order'], 500);
            }
        } else {
            // Обновляем существующий заказ
            $updated = $orderMan->updateOrder($lastOrder['id'], $modelId, $packageId, $serviceIds, $totalPrice);
            if ($updated) {
                sendJson(['message' => 'Order updated'], 200);
            } else {
                sendJson(['error' => 'Failed to update order'], 500);
            }
        }
    }
    exit;
}

// Не AJAX – показываем HTML-страницу
include 'page.php';