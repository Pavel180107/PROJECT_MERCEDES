<?php
// index.php – единая точка входа
session_start();

require_once 'config.php';
require_once 'modules/Database.php';
require_once 'modules/CarModels.php';
require_once 'modules/UserManager.php';
require_once 'modules/OrderManager.php';

function sendJson($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// AJAX-запрос?
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($isAjax && ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT')) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) sendJson(['error' => 'Invalid JSON'], 400);

    $method = $_SERVER['REQUEST_METHOD'];
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

    if ($method === 'POST') {
        if (!$userId) {
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
            } else sendJson(['error' => 'Failed to create order'], 500);
        } else {
            $orderId = $orderMan->createOrder($userId, $modelId, $packageId, $serviceIds, $totalPrice);
            if ($orderId) sendJson(['message' => 'Order created', 'order_id' => $orderId], 201);
            else sendJson(['error' => 'Failed to create order'], 500);
        }
    } elseif ($method === 'PUT') {
        if (!$userId) sendJson(['error' => 'Unauthorized'], 401);
        $lastOrder = $orderMan->getLastOrderByUser($userId);
        if (!$lastOrder) sendJson(['error' => 'No order found'], 404);
        $updated = $orderMan->updateOrder($lastOrder['id'], $modelId, $packageId, $serviceIds, $totalPrice);
        if ($updated) sendJson(['message' => 'Order updated'], 200);
        else sendJson(['error' => 'Failed to update order'], 500);
    }
    exit;
}

// Не AJAX – показываем HTML-страницу
include 'page.php';