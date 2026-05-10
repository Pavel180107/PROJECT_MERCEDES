<?php
require_once 'config.php';
require_once 'modules/Database.php';
require_once 'modules/CarModels.php';
require_once 'modules/UserManager.php';
require_once 'modules/OrderManager.php';

session_start();

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($isAjax) {
    header('Content-Type: application/json');
}

// Получаем входные данные
$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    parse_str(file_get_contents('php://input'), $putData);
    $input = $putData;
} else {
    $input = $_POST;
}

// Если данные пришли как JSON (fetch), прочитаем raw input
$raw = file_get_contents('php://input');
if ($raw && $raw[0] === '{') {
    $jsonData = json_decode($raw, true);
    if ($jsonData) {
        $input = $jsonData;
    }
}

// Валидация обязательных полей
$required = ['full_name', 'email', 'phone', 'consent', 'model_id', 'package_id', 'services', 'total_price'];
foreach ($required as $field) {
    if (!isset($input[$field])) {
        if ($isAjax) {
            http_response_code(422);
            echo json_encode(['error' => "Missing field: $field"]);
            exit;
        } else {
            $_SESSION['flash'] = "Ошибка: не заполнено поле $field";
            header('Location: index.php');
            exit;
        }
    }
}

$full_name = trim($input['full_name']);
$email = trim($input['email']);
$phone = trim($input['phone']);
$consent = (bool)$input['consent'];
$modelId = (int)$input['model_id'];
$packageId = !empty($input['package_id']) ? (int)$input['package_id'] : null;
$serviceIds = isset($input['services']) ? array_map('intval', (array)$input['services']) : [];
$totalPrice = (float)$input['total_price'];

$userMan = new UserManager();
$orderMan = new OrderManager();
$userId = isset($_SESSION['auto_user_id']) ? $_SESSION['auto_user_id'] : null;
$isEdit = ($_SERVER['REQUEST_METHOD'] === 'PUT') || (isset($input['_method']) && $input['_method'] === 'PUT');

try {
    if ($isEdit) {
        // Обновление заказа
        if (!$userId) {
            if ($isAjax) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                exit;
            } else {
                $_SESSION['flash'] = 'Необходимо авторизоваться для редактирования';
                header('Location: login.php');
                exit;
            }
        }
        $lastOrder = $orderMan->getLastOrderByUser($userId);
        if (!$lastOrder) {
            if ($isAjax) {
                http_response_code(404);
                echo json_encode(['error' => 'No order found']);
                exit;
            } else {
                $_SESSION['flash'] = 'Заказ не найден';
                header('Location: index.php');
                exit;
            }
        }
        $orderMan->updateOrder($lastOrder['id'], $modelId, $packageId, $serviceIds, $totalPrice);
        if ($isAjax) {
            echo json_encode(['message' => 'Order updated']);
            exit;
        } else {
            $_SESSION['flash'] = 'Заказ обновлён';
            header('Location: index.php');
            exit;
        }
    } else {
        // Создание заказа
        if (!$userId) {
            // Новый пользователь
            $newUser = $userMan->createUser($full_name, $email, $phone, $consent);
            if (!$newUser) {
                throw new Exception('Не удалось создать пользователя');
            }
            $userId = $newUser['id'];
            $_SESSION['auto_user_id'] = $userId;
            $_SESSION['auto_user_login'] = $newUser['login'];
            $orderId = $orderMan->createOrder($userId, $modelId, $packageId, $serviceIds, $totalPrice);
            if ($isAjax) {
                echo json_encode([
                    'message' => 'Order created',
                    'order_id' => $orderId,
                    'login' => $newUser['login'],
                    'password' => $newUser['password']
                ]);
                exit;
            } else {
                $_SESSION['flash'] = "Заказ создан! Ваш логин: {$newUser['login']}, пароль: {$newUser['password']}";
                header('Location: index.php');
                exit;
            }
        } else {
            // Уже авторизован
            $orderId = $orderMan->createOrder($userId, $modelId, $packageId, $serviceIds, $totalPrice);
            if ($isAjax) {
                echo json_encode(['message' => 'Order created', 'order_id' => $orderId]);
                exit;
            } else {
                $_SESSION['flash'] = 'Заказ создан';
                header('Location: index.php');
                exit;
            }
        }
    }
} catch (Exception $e) {
    if ($isAjax) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    } else {
        $_SESSION['flash'] = 'Ошибка: ' . $e->getMessage();
        header('Location: index.php');
        exit;
    }
}