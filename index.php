<?php
// index.php - единая точка входа
session_start();
require_once 'modules/Database.php';
require_once 'modules/CarModels.php';
require_once 'modules/UserManager.php';
require_once 'modules/OrderManager.php';

// Определяем, AJAX ли запрос
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Обработка POST-запроса (API)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAjax) {
    header('Content-Type: application/json');
    
    // Получаем входные данные (JSON)
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }
    
    // Валидация
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
    $userId = isset($_SESSION['auto_user_id']) ? $_SESSION['auto_user_id'] : null;
    
    // Определяем, редактирование (PUT) или создание (POST)
    $isEdit = ($_SERVER['REQUEST_METHOD'] === 'PUT') || (isset($input['_method']) && $input['_method'] === 'PUT');
    
    try {
        if ($isEdit) {
            if (!$userId) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                exit;
            }
            $lastOrder = $orderMan->getLastOrderByUser($userId);
            if (!$lastOrder) {
                http_response_code(404);
                echo json_encode(['error' => 'No order found']);
                exit;
            }
            $orderMan->updateOrder($lastOrder['id'], $modelId, $packageId, $serviceIds, $totalPrice);
            echo json_encode(['message' => 'Order updated']);
        } else {
            if (!$userId) {
                $newUser = $userMan->createUser($full_name, $email, $phone, $consent);
                if (!$newUser) {
                    throw new Exception('User creation failed');
                }
                $userId = $newUser['id'];
                $_SESSION['auto_user_id'] = $userId;
                $_SESSION['auto_user_login'] = $newUser['login'];
                $orderId = $orderMan->createOrder($userId, $modelId, $packageId, $serviceIds, $totalPrice);
                echo json_encode([
                    'message' => 'Order created',
                    'order_id' => $orderId,
                    'login' => $newUser['login'],
                    'password' => $newUser['password']
                ]);
            } else {
                $orderId = $orderMan->createOrder($userId, $modelId, $packageId, $serviceIds, $totalPrice);
                echo json_encode([
                    'message' => 'Order created',
                    'order_id' => $orderId
                ]);
            }
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Обработка обычного POST (fallback) – редирект (не AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isAjax) {
    // Здесь можно обработать обычную отправку формы (без JS)
    // Или просто перенаправить с flash-сообщением
    $_SESSION['flash'] = 'Для оформления заказа включите JavaScript.';
    header('Location: /');
    exit;
}

// Иначе – отображаем страницу
require_once 'page.php';