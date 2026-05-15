<?php
session_start();
require_once 'config.php';
require_once 'modules/Database.php';
require_once 'modules/CarModels.php';
require_once 'modules/UserManager.php';
require_once 'modules/OrderManager.php';

if (!isset($_SESSION['auto_user_id'])) {
    header('Location: login.php?redirect=profile');
    exit;
}

$userMan = new UserManager();
$orderMan = new OrderManager();
$carModel = new CarModels();

$userId = $_SESSION['auto_user_id'];
$user = $userMan->getUserById($userId);
$orders = $orderMan->getUserOrders($userId);

// Получение данных для формы редактирования/создания
$allModels = $carModel->getAllModels();
$allPackages = $carModel->getAllPackages();
$allServices = $carModel->getAllServices();

$flash = '';
$error = '';

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        if ($action === 'delete_order' && isset($_POST['order_id'])) {
            $orderId = (int)$_POST['order_id'];
            if ($orderMan->deleteOrder($orderId, $userId)) {
                $flash = 'Заказ удалён.';
                // Обновляем список заказов
                $orders = $orderMan->getUserOrders($userId);
            } else {
                $error = 'Не удалось удалить заказ.';
            }
        } elseif ($action === 'edit_order' && isset($_POST['order_id'])) {
            $orderId = (int)$_POST['order_id'];
            $modelId = (int)$_POST['model_id'];
            $packageId = !empty($_POST['package_id']) ? (int)$_POST['package_id'] : null;
            $serviceIds = isset($_POST['services']) ? array_map('intval', $_POST['services']) : [];
            $totalPrice = (float)$_POST['total_price'];

            $updated = $orderMan->updateOrder($orderId, $modelId, $packageId, $serviceIds, $totalPrice);
            if ($updated) {
                $flash = 'Заказ обновлён.';
                $orders = $orderMan->getUserOrders($userId);
            } else {
                $error = 'Ошибка обновления заказа.';
            }
        } elseif ($action === 'create_order') {
            $modelId = (int)$_POST['model_id'];
            $packageId = !empty($_POST['package_id']) ? (int)$_POST['package_id'] : null;
            $serviceIds = isset($_POST['services']) ? array_map('intval', $_POST['services']) : [];
            $totalPrice = (float)$_POST['total_price'];

            $orderId = $orderMan->createOrder($userId, $modelId, $packageId, $serviceIds, $totalPrice);
            if ($orderId) {
                $flash = 'Новый заказ создан.';
                $orders = $orderMan->getUserOrders($userId);
            } else {
                $error = 'Ошибка создания заказа.';
            }
        }
    }
}

// Получение заказа для редактирования (если передан GET edit_order)
$editOrder = null;
if (isset($_GET['edit_order'])) {
    $orderId = (int)$_GET['edit_order'];
    $editOrder = $orderMan->getOrderById($orderId);
    if (!$editOrder || $editOrder['user_id'] != $userId) {
        $editOrder = null;
        $error = 'Заказ не найден.';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Личный кабинет – Mercedes Elite</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 2rem auto;
            background: #1a1a1a;
            border-radius: 24px;
            padding: 2rem;
        }
        .user-info {
            background: #0d0d0d;
            padding: 1rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            text-align: center;
        }
        .orders-grid {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .order-card {
            background: #0d0d0d;
            border-radius: 16px;
            padding: 1rem;
            border: 1px solid #333;
            transition: 0.2s;
        }
        .order-card:hover {
            border-color: #00A0E3;
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            border-bottom: 1px solid #333;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }
        .order-num {
            font-size: 1.3rem;
            font-weight: bold;
            color: #00A0E3;
        }
        .order-actions {
            display: flex;
            gap: 0.8rem;
        }
        .btn-sm {
            padding: 0.3rem 1rem;
            border-radius: 20px;
            background: #00A0E3;
            color: white;
            text-decoration: none;
            font-size: 0.8rem;
            border: none;
            cursor: pointer;
        }
        .btn-sm.danger {
            background: #c62828;
        }
        .edit-form {
            background: #2a2a2a;
            border-radius: 16px;
            padding: 1rem;
            margin-top: 1rem;
        }
        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .form-group {
            flex: 1;
            min-width: 150px;
        }
        .services-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .services-group label {
            background: #1a1a1a;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            cursor: pointer;
        }
        .btn {
            background: #00A0E3;
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 30px;
            cursor: pointer;
        }
        .btn-secondary {
            background: #555;
        }
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .success { background: #2e7d32; color: white; }
        .error { background: #c62828; color: white; }
        .back-link {
            margin-top: 2rem;
            text-align: center;
        }
        .back-link a {
            background: #00A0E3;
            color: white;
            padding: 10px 20px;
            border-radius: 30px;
            text-decoration: none;
            display: inline-block;
        }
        .back-link a:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
<div class="profile-container">
    <h1 style="text-align:center;">👤 Личный кабинет</h1>
    <div class="user-info">
        <p><strong><?= htmlspecialchars($user['full_name']) ?></strong> (<?= htmlspecialchars($user['login']) ?>)</p>
        <p><?= htmlspecialchars($user['email']) ?> | <?= htmlspecialchars($user['phone']) ?></p>
        <a href="logout.php" class="btn-sm" style="background:#555;">Выйти</a>
        <a href="index.php" class="btn-sm">На главную</a>
    </div>

    <?php if ($flash): ?>
        <div class="message success"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <h2>Мои заказы</h2>
    <div class="orders-grid">
        <?php foreach ($orders as $order): ?>
            <div class="order-card" id="order-<?= $order['id'] ?>">
                <div class="order-header">
                    <span class="order-num">Заказ №<?= $order['order_num'] ?></span>
                    <div class="order-actions">
                        <button class="btn-sm" onclick="toggleEditForm(<?= $order['id'] ?>)">✏️ Редактировать</button>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="delete_order">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <button type="submit" class="btn-sm danger" onclick="return confirm('Удалить заказ №<?= $order['order_num'] ?>?')">🗑 Удалить</button>
                        </form>
                    </div>
                </div>
                <div><strong>Модель:</strong> <?= htmlspecialchars($order['model_name']) ?></div>
                <div><strong>Пакет:</strong> <?= $order['package_name'] ? htmlspecialchars($order['package_name']) : '—' ?></div>
                <div><strong>Услуги:</strong> <?= htmlspecialchars($order['services_list'] ?: '—') ?></div>
                <div><strong>Стоимость:</strong> <?= number_format($order['total_price'], 0, '', ' ') ?> ₽</div>
                <div><strong>Статус:</strong> <?= $order['status'] ?></div>
                <div><strong>Дата:</strong> <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></div>

                <div id="edit-form-<?= $order['id'] ?>" style="display: none;" class="edit-form">
                    <h4>Редактирование заказа</h4>
                    <form method="post">
                        <input type="hidden" name="action" value="edit_order">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Модель</label>
                                <select name="model_id">
                                    <?php foreach ($allModels as $m): ?>
                                        <option value="<?= $m['id'] ?>" <?= ($order['model_id'] == $m['id']) ? 'selected' : '' ?>><?= htmlspecialchars($m['name']) ?> (<?= number_format($m['base_price'], 0, '', ' ') ?> ₽)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Пакет опций</label>
                                <select name="package_id">
                                    <option value="">Без пакета</option>
                                    <?php foreach ($allPackages as $p): ?>
                                        <option value="<?= $p['id'] ?>" <?= ($order['package_id'] == $p['id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?> (+<?= number_format($p['price_add'], 0, '', ' ') ?> ₽)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Доп. услуги</label>
                            <div class="services-group">
                                <?php foreach ($allServices as $s): ?>
                                    <label>
                                        <input type="checkbox" name="services[]" value="<?= $s['id'] ?>" <?= in_array($s['id'], $order['service_ids']) ? 'checked' : '' ?>>
                                        <?= htmlspecialchars($s['name']) ?> (+<?= number_format($s['price_add'], 0, '', ' ') ?> ₽)
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Итоговая стоимость (₽)</label>
                                <input type="number" name="total_price" value="<?= $order['total_price'] ?>" step="0.01" required>
                            </div>
                        </div>
                        <button type="submit" class="btn">Сохранить</button>
                        <button type="button" class="btn btn-secondary" onclick="toggleEditForm(<?= $order['id'] ?>)">Отмена</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($orders)): ?>
            <p>У вас пока нет заказов. Создайте первый через форму на главной странице.</p>
        <?php endif; ?>
    </div>

    <h2>Создать новый заказ</h2>
    <div class="edit-form">
        <form method="post">
            <input type="hidden" name="action" value="create_order">
            <div class="form-row">
                <div class="form-group">
                    <label>Модель</label>
                    <select name="model_id" id="new_model_id">
                        <?php foreach ($allModels as $m): ?>
                            <option value="<?= $m['id'] ?>" data-price="<?= $m['base_price'] ?>"><?= htmlspecialchars($m['name']) ?> (<?= number_format($m['base_price'], 0, '', ' ') ?> ₽)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Пакет опций</label>
                    <select name="package_id" id="new_package_id">
                        <option value="">Без пакета</option>
                        <?php foreach ($allPackages as $p): ?>
                            <option value="<?= $p['id'] ?>" data-price="<?= $p['price_add'] ?>"><?= htmlspecialchars($p['name']) ?> (+<?= number_format($p['price_add'], 0, '', ' ') ?> ₽)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Доп. услуги</label>
                <div class="services-group" id="new_services_group">
                    <?php foreach ($allServices as $s): ?>
                        <label>
                            <input type="checkbox" name="services[]" value="<?= $s['id'] ?>" data-price="<?= $s['price_add'] ?>">
                            <?= htmlspecialchars($s['name']) ?> (+<?= number_format($s['price_add'], 0, '', ' ') ?> ₽)
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group">
                <label>Итоговая стоимость (авторасчёт)</label>
                <input type="text" id="new_total_price" readonly style="background:#333;color:#00A0E3;font-weight:bold;">
                <input type="hidden" name="total_price" id="new_total_price_hidden">
            </div>
            <button type="submit" class="btn">Создать заказ</button>
        </form>
    </div>

    <div class="back-link">
        <a href="index.php">← Вернуться на главную</a>
    </div>
</div>

<script>
    function toggleEditForm(orderId) {
        const form = document.getElementById(`edit-form-${orderId}`);
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }

    // Авторасчёт для нового заказа
    function updateNewTotalPrice() {
        const modelSelect = document.getElementById('new_model_id');
        const packageSelect = document.getElementById('new_package_id');
        const checkboxes = document.querySelectorAll('#new_services_group input[type="checkbox"]:checked');
        let total = parseFloat(modelSelect.options[modelSelect.selectedIndex].getAttribute('data-price') || 0);
        total += parseFloat(packageSelect.options[packageSelect.selectedIndex].getAttribute('data-price') || 0);
        checkboxes.forEach(cb => total += parseFloat(cb.getAttribute('data-price') || 0));
        document.getElementById('new_total_price').value = total.toLocaleString('ru-RU') + ' ₽';
        document.getElementById('new_total_price_hidden').value = total;
    }
    document.getElementById('new_model_id').addEventListener('change', updateNewTotalPrice);
    document.getElementById('new_package_id').addEventListener('change', updateNewTotalPrice);
    document.querySelectorAll('#new_services_group input').forEach(cb => cb.addEventListener('change', updateNewTotalPrice));
    updateNewTotalPrice();
</script>
</body>
</html>