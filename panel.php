<?php
// panel.php – Админ-панель с тремя разделами (кубики)
session_start();
require_once 'config.php';
require_once 'modules/Database.php';
require_once 'modules/CarModels.php';
require_once 'modules/UserManager.php';
require_once 'modules/OrderManager.php';

$db = Database::getInstance()->getConnection();

// ========== HTTP Basic Auth ==========
if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
    header('WWW-Authenticate: Basic realm="Mercedes Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    echo '<h1 style="text-align:center;">Доступ запрещён</h1><p>Введите логин и пароль администратора.</p>';
    exit;
}
$stmt = $db->prepare("SELECT password_hash FROM admin WHERE login = ?");
$stmt->execute([$_SERVER['PHP_AUTH_USER']]);
$admin = $stmt->fetch();
if (!$admin || !password_verify($_SERVER['PHP_AUTH_PW'], $admin['password_hash'])) {
    header('WWW-Authenticate: Basic realm="Mercedes Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    echo '<h1 style="text-align:center;">Неверный логин или пароль!</h1>';
    exit;
}

$carModel = new CarModels();
$orderMan = new OrderManager();
$userMan = new UserManager();

// Получаем все модели, пакеты, услуги для select'ов
$allModels = $carModel->getAllModels();
$allPackages = $carModel->getAllPackages();
$allServices = $carModel->getAllServices();

// Переменная для хранения сообщений (успех/ошибка)
$message = '';

// ========== ОБРАБОТКА ДЕЙСТВИЙ ==========
$action = $_GET['action'] ?? '';

// 1. Удаление заказа
if (isset($_GET['delete_order'])) {
    $orderId = (int)$_GET['delete_order'];
    try {
        $db->prepare("DELETE FROM auto_order_services WHERE order_id = ?")->execute([$orderId]);
        $db->prepare("DELETE FROM auto_orders WHERE id = ?")->execute([$orderId]);
        $message = '<div class="success-message">Заказ №' . $orderId . ' успешно удалён</div>';
    } catch (Exception $e) {
        $message = '<div class="error-message">Ошибка удаления: ' . $e->getMessage() . '</div>';
    }
}

// 2. Редактирование заказа (POST)
$editOrder = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_order_id'])) {
    $orderId = (int)$_POST['edit_order_id'];
    $userId = (int)$_POST['user_id'] ?? 0;
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $modelId = (int)$_POST['model_id'];
    $packageId = !empty($_POST['package_id']) ? (int)$_POST['package_id'] : null;
    $serviceIds = isset($_POST['services']) ? array_map('intval', $_POST['services']) : [];
    $totalPrice = (float)$_POST['total_price'];
    $status = $_POST['status'] ?? 'new';

    // Валидация ФИО, email, телефона
    $errors = [];
    if ($err = UserManager::validateFullName($full_name)) $errors['full_name'] = $err;
    if ($err = UserManager::validateEmail($email)) $errors['email'] = $err;
    if ($err = UserManager::validatePhone($phone)) $errors['phone'] = $err;

    if (!empty($errors)) {
        $message = '<div class="error-message">Исправьте ошибки: ' . implode(', ', $errors) . '</div>';
        // Для отображения формы редактирования сохраним введённые данные
        $editOrder = [
            'id' => $orderId,
            'user_id' => $userId,
            'full_name' => $full_name,
            'email' => $email,
            'phone' => $phone,
            'model_id' => $modelId,
            'package_id' => $packageId,
            'services' => $serviceIds,
            'total_price' => $totalPrice,
            'status' => $status
        ];
    } else {
        try {
            // Обновляем данные пользователя
            $db->prepare("UPDATE auto_users SET full_name=?, email=?, phone=? WHERE id=?")
                ->execute([$full_name, $email, $phone, $userId]);
            // Обновляем заказ
            $db->prepare("UPDATE auto_orders SET model_id=?, package_id=?, total_price=?, status=? WHERE id=?")
                ->execute([$modelId, $packageId, $totalPrice, $status, $orderId]);
            // Обновляем услуги
            $db->prepare("DELETE FROM auto_order_services WHERE order_id=?")->execute([$orderId]);
            if (!empty($serviceIds)) {
                $ins = $db->prepare("INSERT INTO auto_order_services (order_id, service_id) VALUES (?, ?)");
                foreach ($serviceIds as $sid) $ins->execute([$orderId, $sid]);
            }
            $message = '<div class="success-message">Заказ №' . $orderId . ' успешно обновлён</div>';
        } catch (Exception $e) {
            $message = '<div class="error-message">Ошибка обновления: ' . $e->getMessage() . '</div>';
        }
    }
}

// 3. Получение заказа для редактирования (если передан GET edit)
$editOrderId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
if ($editOrderId > 0 && empty($editOrder)) {
    $stmt = $db->prepare("
        SELECT o.*, u.full_name, u.email, u.phone 
        FROM auto_orders o
        JOIN auto_users u ON o.user_id = u.id
        WHERE o.id = ?
    ");
    $stmt->execute([$editOrderId]);
    $orderData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($orderData) {
        // Получаем услуги
        $stmt = $db->prepare("SELECT service_id FROM auto_order_services WHERE order_id = ?");
        $stmt->execute([$editOrderId]);
        $serviceIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $editOrder = [
            'id' => $orderData['id'],
            'user_id' => $orderData['user_id'],
            'full_name' => $orderData['full_name'],
            'email' => $orderData['email'],
            'phone' => $orderData['phone'],
            'model_id' => $orderData['model_id'],
            'package_id' => $orderData['package_id'],
            'services' => $serviceIds,
            'total_price' => $orderData['total_price'],
            'status' => $orderData['status']
        ];
    }
}

// ========== Получение данных для разделов ==========
// 1. Все заказы (с данными пользователя)
$allOrders = [];
$stmt = $db->query("
    SELECT o.id, o.total_price, o.status, o.created_at,
           u.full_name, u.email, u.phone,
           m.name as model_name,
           p.name as package_name,
           GROUP_CONCAT(s.name SEPARATOR ', ') as services_list
    FROM auto_orders o
    JOIN auto_users u ON o.user_id = u.id
    JOIN auto_car_models m ON o.model_id = m.id
    LEFT JOIN auto_packages p ON o.package_id = p.id
    LEFT JOIN auto_order_services os ON o.id = os.order_id
    LEFT JOIN auto_additional_services s ON os.service_id = s.id
    GROUP BY o.id
    ORDER BY o.id DESC
");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $allOrders[] = $row;
}

// 2. Поиск клиента (если форма отправлена)
$searchResults = [];
$searchQuery = '';
if (isset($_GET['search'])) {
    $searchQuery = trim($_GET['q'] ?? '');
    if ($searchQuery !== '') {
        $stmt = $db->prepare("
            SELECT u.id, u.full_name, u.email, u.phone,
                   o.id as order_id, o.total_price, o.status, o.created_at,
                   m.name as model_name
            FROM auto_users u
            LEFT JOIN auto_orders o ON u.id = o.user_id
            LEFT JOIN auto_car_models m ON o.model_id = m.id
            WHERE u.email LIKE ? OR u.phone LIKE ?
            ORDER BY u.id
        ");
        $like = "%$searchQuery%";
        $stmt->execute([$like, $like]);
        $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель Mercedes</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Красивые кубики-разделы */
        .admin-dashboard {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            justify-content: center;
            margin: 2rem 0;
        }
        .admin-card {
            background: linear-gradient(145deg, #1a1a1a, #0d0d0d);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            width: 280px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid #333;
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .admin-card:hover {
            transform: translateY(-10px);
            border-color: #00A0E3;
            box-shadow: 0 20px 30px rgba(0,160,227,0.2);
        }
        .admin-card i {
            font-size: 3rem;
            color: #00A0E3;
            margin-bottom: 1rem;
        }
        .admin-card h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #fff;
        }
        .admin-card p {
            color: #aaa;
            font-size: 0.9rem;
        }
        .section-content {
            background: #1a1a1a;
            border-radius: 16px;
            padding: 1.5rem;
            margin-top: 2rem;
            border: 1px solid #333;
        }
        .section-title {
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            color: #00A0E3;
            border-left: 4px solid #00A0E3;
            padding-left: 1rem;
        }
        .table-responsive {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #0d0d0d;
            border-radius: 12px;
            overflow: hidden;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #333;
        }
        th {
            background: #00A0E3;
            color: #fff;
        }
        tr:hover td {
            background: #2a2a2a;
        }
        .btn-sm {
            padding: 4px 12px;
            font-size: 0.85rem;
            background: #00A0E3;
            border-radius: 20px;
            color: white;
            text-decoration: none;
            margin: 0 4px;
            display: inline-block;
        }
        .btn-sm.danger {
            background: #c62828;
        }
        .btn-sm:hover {
            opacity: 0.8;
        }
        .search-form {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .search-form input {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #333;
            background: #0d0d0d;
            color: white;
        }
        .edit-form-group {
            margin-bottom: 1rem;
        }
        .edit-form-group label {
            display: block;
            margin-bottom: 0.3rem;
            color: #aaa;
        }
        .edit-form-group input, .edit-form-group select, .edit-form-group textarea {
            width: 100%;
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #333;
            background: #0d0d0d;
            color: white;
        }
        .services-checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
        }
        .services-checkbox-group label {
            background: #2a2a2a;
            padding: 5px 10px;
            border-radius: 20px;
            cursor: pointer;
        }
        .back-link {
            margin-top: 2rem;
            text-align: center;
        }
        .back-link a {
            color: #00A0E3;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="container">
    <h1 style="text-align:center;">🔧 Админ-панель Mercedes</h1>
    <p style="text-align:center;">Авторизован как <strong><?= htmlspecialchars($_SERVER['PHP_AUTH_USER']) ?></strong></p>

    <?= $message ?>

    <!-- Кубики-разделы -->
    <div class="admin-dashboard">
        <a href="?section=orders" class="admin-card" id="card-orders">
            <i class="fas fa-clipboard-list"></i>
            <h3>Все заказы</h3>
            <p>Просмотр всех анкет клиентов</p>
        </a>
        <a href="?section=search" class="admin-card" id="card-search">
            <i class="fas fa-search"></i>
            <h3>Поиск клиента</h3>
            <p>По номеру телефона или email</p>
        </a>
        <a href="?section=edit" class="admin-card" id="card-edit">
            <i class="fas fa-edit"></i>
            <h3>Редактирование</h3>
            <p>Изменить или удалить заказ</p>
        </a>
    </div>

    <?php
    $section = $_GET['section'] ?? '';
    if ($section === 'orders' || $section === '' && !isset($_GET['search']) && !isset($_GET['edit'])) {
        // Раздел "Все заказы"
        ?>
        <div class="section-content">
            <div class="section-title"><i class="fas fa-clipboard-list"></i> Все заказы</div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>ID</th><th>Клиент</th><th>Email</th><th>Телефон</th><th>Модель</th><th>Пакет</th><th>Услуги</th><th>Стоимость</th><th>Статус</th><th>Дата</th><th>Действия</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($allOrders)): ?>
                            <tr><td colspan="11" style="text-align:center;">Заказов пока нет</td></tr>
                        <?php else: ?>
                            <?php foreach ($allOrders as $order): ?>
                                <tr>
                                    <td><?= $order['id'] ?></td>
                                    <td><?= htmlspecialchars($order['full_name']) ?></td>
                                    <td><?= htmlspecialchars($order['email']) ?></td>
                                    <td><?= htmlspecialchars($order['phone']) ?></td>
                                    <td><?= htmlspecialchars($order['model_name']) ?></td>
                                    <td><?= $order['package_name'] ? htmlspecialchars($order['package_name']) : '—' ?></td>
                                    <td><?= htmlspecialchars($order['services_list'] ?: '—') ?></td>
                                    <td><?= number_format($order['total_price'], 0, '', ' ') ?> ₽</td>
                                    <td><?= htmlspecialchars($order['status']) ?></td>
                                    <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                                    <td>
                                        <a href="?section=edit&edit=<?= $order['id'] ?>" class="btn-sm">✏️ Ред.</a>
                                        <a href="?delete_order=<?= $order['id'] ?>" class="btn-sm danger" onclick="return confirm('Удалить заказ №<?= $order['id'] ?>?')">🗑 Удалить</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    } elseif ($section === 'search') {
        // Раздел "Поиск клиента"
        ?>
        <div class="section-content">
            <div class="section-title"><i class="fas fa-search"></i> Поиск клиента</div>
            <form method="get" class="search-form">
                <input type="hidden" name="section" value="search">
                <input type="text" name="q" placeholder="Введите email или номер телефона" value="<?= htmlspecialchars($searchQuery) ?>">
                <button type="submit" name="search" value="1" class="btn">Искать</button>
            </form>
            <?php if (isset($_GET['search'])): ?>
                <?php if (empty($searchResults)): ?>
                    <p>Ничего не найдено.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead><tr><th>ID клиента</th><th>ФИО</th><th>Email</th><th>Телефон</th><th>ID заказа</th><th>Модель</th><th>Стоимость</th><th>Статус</th><th>Действия</th></tr></thead>
                            <tbody>
                                <?php foreach ($searchResults as $res): ?>
                                    <tr>
                                        <td><?= $res['id'] ?></td>
                                        <td><?= htmlspecialchars($res['full_name']) ?></td>
                                        <td><?= htmlspecialchars($res['email']) ?></td>
                                        <td><?= htmlspecialchars($res['phone']) ?></td>
                                        <td><?= $res['order_id'] ?? '—' ?></td>
                                        <td><?= $res['model_name'] ?? '—' ?></td>
                                        <td><?= $res['total_price'] ? number_format($res['total_price'], 0, '', ' ') . ' ₽' : '—' ?></td>
                                        <td><?= $res['status'] ?? '—' ?></td>
                                        <td>
                                            <?php if ($res['order_id']): ?>
                                                <a href="?section=edit&edit=<?= $res['order_id'] ?>" class="btn-sm">Редактировать</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    } elseif ($section === 'edit') {
        // Раздел "Редактирование/удаление"
        ?>
        <div class="section-content">
            <div class="section-title"><i class="fas fa-edit"></i> Редактирование заказа</div>
            <?php if ($editOrder): ?>
                <form method="post">
                    <input type="hidden" name="edit_order_id" value="<?= $editOrder['id'] ?>">
                    <input type="hidden" name="user_id" value="<?= $editOrder['user_id'] ?>">
                    
                    <div class="edit-form-group">
                        <label>ФИО</label>
                        <input type="text" name="full_name" value="<?= htmlspecialchars($editOrder['full_name']) ?>" required>
                    </div>
                    <div class="edit-form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($editOrder['email']) ?>" required>
                    </div>
                    <div class="edit-form-group">
                        <label>Телефон</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($editOrder['phone']) ?>" required>
                    </div>
                    <div class="edit-form-group">
                        <label>Модель</label>
                        <select name="model_id">
                            <?php foreach ($allModels as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= ($editOrder['model_id'] == $m['id']) ? 'selected' : '' ?>><?= htmlspecialchars($m['name']) ?> (<?= number_format($m['base_price'], 0, '', ' ') ?> ₽)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="edit-form-group">
                        <label>Пакет опций</label>
                        <select name="package_id">
                            <option value="">Без пакета</option>
                            <?php foreach ($allPackages as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= ($editOrder['package_id'] == $p['id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?> (+<?= number_format($p['price_add'], 0, '', ' ') ?> ₽)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="edit-form-group">
                        <label>Дополнительные услуги</label>
                        <div class="services-checkbox-group">
                            <?php foreach ($allServices as $s): ?>
                                <label>
                                    <input type="checkbox" name="services[]" value="<?= $s['id'] ?>" <?= in_array($s['id'], $editOrder['services']) ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($s['name']) ?> (+<?= number_format($s['price_add'], 0, '', ' ') ?> ₽)
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="edit-form-group">
                        <label>Итоговая стоимость</label>
                        <input type="number" step="0.01" name="total_price" value="<?= $editOrder['total_price'] ?>" required>
                    </div>
                    <div class="edit-form-group">
                        <label>Статус заказа</label>
                        <select name="status">
                            <option value="new" <?= ($editOrder['status'] == 'new') ? 'selected' : '' ?>>Новый</option>
                            <option value="processed" <?= ($editOrder['status'] == 'processed') ? 'selected' : '' ?>>Обработан</option>
                            <option value="completed" <?= ($editOrder['status'] == 'completed') ? 'selected' : '' ?>>Завершён</option>
                            <option value="cancelled" <?= ($editOrder['status'] == 'cancelled') ? 'selected' : '' ?>>Отменён</option>
                        </select>
                    </div>
                    <button type="submit" class="btn">Сохранить изменения</button>
                    <a href="?section=edit" class="btn" style="background:#555;">Отмена</a>
                </form>
                <div style="margin-top: 20px;">
                    <a href="?delete_order=<?= $editOrder['id'] ?>" class="btn" style="background:#c62828;" onclick="return confirm('Удалить заказ №<?= $editOrder['id'] ?>?')">🗑 Удалить этот заказ</a>
                </div>
            <?php else: ?>
                <p>Введите ID заказа для редактирования:</p>
                <form method="get">
                    <input type="hidden" name="section" value="edit">
                    <div class="search-form">
                        <input type="number" name="edit" placeholder="ID заказа" required>
                        <button type="submit" class="btn">Найти заказ</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }
    ?>
    <div class="back-link">
        <a href="index.php">← Вернуться на главную</a>
    </div>
</div>
</body>
</html>