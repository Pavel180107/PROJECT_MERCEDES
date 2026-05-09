<?php
require_once 'config.php';
require_once 'modules/Database.php';
require_once 'modules/CarModels.php';
require_once 'modules/UserManager.php';
require_once 'modules/OrderManager.php';

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Простой роутинг внутри api.php
if ($path === '/api/order' && ($method === 'POST' || $method === 'PUT')) {
    handleOrderRequest($method);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
}

function handleOrderRequest($method) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        sendError(400, 'Invalid JSON');
        return;
    }

    // Валидация обязательных полей
    $required = ['full_name', 'email', 'phone', 'consent', 'model_id', 'package_id', 'services', 'total_price'];
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            sendError(422, "Missing field: $field");
            return;
        }
    }

    $full_name = trim($input['full_name']);
    $email = trim($input['email']);
    $phone = trim($input['phone']);
    $consent = (bool)$input['consent'];
    $modelId = (int)$input['model_id'];
    $packageId = !empty($input['package_id']) ? (int)$input['package_id'] : null;
    $serviceIds = array_map('intval', $input['services']);
    $totalPrice = (float)$input['total_price'];

    // Получаем менеджеров
    $userMan = new UserManager();
    $orderMan = new OrderManager();

    // Проверяем, авторизован ли пользователь
    $userId = null;
    if (isset($_SESSION['auto_user_id'])) {
        $userId = $_SESSION['auto_user_id'];
    }

    if ($method === 'POST') {
        // Создание нового заказа (неавторизованный пользователь – создаём нового)
        if (!$userId) {
            // Создаём нового пользователя
            $newUser = $userMan->createUser($full_name, $email, $phone, $consent);
            if (!$newUser) {
                sendError(500, 'Failed to create user');
                return;
            }
            $userId = $newUser['id'];
            $_SESSION['auto_user_id'] = $userId;
            $_SESSION['auto_user_login'] = $newUser['login'];

            $orderId = $orderMan->createOrder($userId, $modelId, $packageId, $serviceIds, $totalPrice);
            if ($orderId) {
                sendSuccess(201, [
                    'message' => 'Order created',
                    'order_id' => $orderId,
                    'login' => $newUser['login'],
                    'password' => $newUser['password']
                ]);
            } else {
                sendError(500, 'Failed to create order');
            }
        } else {
            // Уже авторизован – создаём новый заказ для этого пользователя
            $orderId = $orderMan->createOrder($userId, $modelId, $packageId, $serviceIds, $totalPrice);
            if ($orderId) {
                sendSuccess(201, [
                    'message' => 'Order created',
                    'order_id' => $orderId
                ]);
            } else {
                sendError(500, 'Failed to create order');
            }
        }
    }
    elseif ($method === 'PUT') {
        // Обновление – требуется авторизация
        if (!$userId) {
            sendError(401, 'Unauthorized');
            return;
        }
        // Получаем последний заказ пользователя
        $lastOrder = $orderMan->getLastOrderByUser($userId);
        if (!$lastOrder) {
            sendError(404, 'No order found for this user');
            return;
        }
        $updated = $orderMan->updateOrder($lastOrder['id'], $modelId, $packageId, $serviceIds, $totalPrice);
        if ($updated) {
            sendSuccess(200, ['message' => 'Order updated']);
        } else {
            sendError(500, 'Failed to update order');
        }
    }
}

function sendSuccess($code, $data) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function sendError($code, $message) {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}