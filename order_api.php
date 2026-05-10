<?php
// order_api.php – обработчик AJAX-запросов
require_once 'config.php';
require_once 'modules/Database.php';
require_once 'modules/CarModels.php';
require_once 'modules/UserManager.php';
require_once 'modules/OrderManager.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// Для PUT-запросов читаем входной поток
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
} else {
    $input = $_POST; // или тоже json, но для простоты пусть ожидает JSON
    // Если данные пришли как JSON, то переопределим
    $raw = file_get_contents('php://input');
    if ($raw && $raw[0] === '{') {
        $input = json_decode($raw, true);
    }
}

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Валидация обязательных полей
$required = ['full_name', 'email', 'phone', 'consent', 'model_id', 'package_id', 'services', 'total_price'];
foreach ($required as $field) {
    if (!isset($input[$field])) {
        http_response_code(422);
        echo json_encode(['error' => "Missing field: $field"]);
        exit;
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

$userMan = new UserManager();
$orderMan = new OrderManager();

// Определяем, авторизован ли пользователь
$userId = isset($_SESSION['auto_user_id']) ? $_SESSION['auto_user_id'] : null;

if ($method === 'POST') {
    // Создание нового заказа
    if (!$userId) {
        // Неавторизован – создаём пользователя
        $newUser = $userMan->createUser($full_name, $email, $phone, $consent);
        if (!$newUser) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create user']);
            exit;
        }
        $userId = $newUser['id'];
        $_SESSION['auto_user_id'] = $userId;
        $_SESSION['auto_user_login'] = $newUser['login'];

        $orderId = $orderMan->createOrder($userId, $modelId, $packageId, $serviceIds, $totalPrice);
        if ($orderId) {
            http_response_code(201);
            echo json_encode([
                'message' => 'Order created',
                'order_id' => $orderId,
                'login' => $newUser['login'],
                'password' => $newUser['password']
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create order']);
        }
    } else {
        // Уже авторизован – создаём заказ для него
        $orderId = $orderMan->createOrder($userId, $modelId, $packageId, $serviceIds, $totalPrice);
        if ($orderId) {
            http_response_code(201);
            echo json_encode(['message' => 'Order created', 'order_id' => $orderId]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create order']);
        }
    }
} elseif ($method === 'PUT') {
    // Обновление существующего заказа (требуется авторизация)
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    $lastOrder = $orderMan->getLastOrderByUser($userId);
    if (!$lastOrder) {
        http_response_code(404);
        echo json_encode(['error' => 'No order found for this user']);
        exit;
    }
    $updated = $orderMan->updateOrder($lastOrder['id'], $modelId, $packageId, $serviceIds, $totalPrice);
    if ($updated) {
        http_response_code(200);
        echo json_encode(['message' => 'Order updated']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update order']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}