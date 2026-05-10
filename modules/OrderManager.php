<?php
require_once __DIR__ . 'Database.php';

class OrderManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Получить последний заказ пользователя
    public function getLastOrderByUser($userId) {
        $stmt = $this->db->prepare("
            SELECT o.*, 
                   (SELECT GROUP_CONCAT(service_id) FROM auto_order_services WHERE order_id = o.id) as service_ids
            FROM auto_orders o
            WHERE o.user_id = ?
            ORDER BY o.id DESC LIMIT 1
        ");
        $stmt->execute([$userId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($order && $order['service_ids']) {
            $order['service_ids'] = explode(',', $order['service_ids']);
        } else {
            $order['service_ids'] = [];
        }
        return $order;
    }

    // Создать новый заказ
    public function createOrder($userId, $modelId, $packageId, $serviceIds, $totalPrice) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO auto_orders (user_id, model_id, package_id, total_price, status)
                VALUES (?, ?, ?, ?, 'new')
            ");
            $stmt->execute([$userId, $modelId, $packageId ?: null, $totalPrice]);
            $orderId = $this->db->lastInsertId();

            if (!empty($serviceIds)) {
                $stmt = $this->db->prepare("INSERT INTO auto_order_services (order_id, service_id) VALUES (?, ?)");
                foreach ($serviceIds as $sid) {
                    $stmt->execute([$orderId, $sid]);
                }
            }
            $this->db->commit();
            return $orderId;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }

    // Обновить существующий заказ (удаляем старый, создаём новый – упрощённо)
    public function updateOrder($orderId, $modelId, $packageId, $serviceIds, $totalPrice) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                UPDATE auto_orders SET model_id = ?, package_id = ?, total_price = ?
                WHERE id = ?
            ");
            $stmt->execute([$modelId, $packageId ?: null, $totalPrice, $orderId]);

            // Обновляем услуги
            $this->db->prepare("DELETE FROM auto_order_services WHERE order_id = ?")->execute([$orderId]);
            if (!empty($serviceIds)) {
                $stmt = $this->db->prepare("INSERT INTO auto_order_services (order_id, service_id) VALUES (?, ?)");
                foreach ($serviceIds as $sid) {
                    $stmt->execute([$orderId, $sid]);
                }
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }

    // Получить заказ по ID (для отображения при редактировании)
    public function getOrderById($id) {
        $stmt = $this->db->prepare("
            SELECT o.*, 
                   (SELECT GROUP_CONCAT(service_id) FROM auto_order_services WHERE order_id = o.id) as service_ids
            FROM auto_orders o WHERE o.id = ?
        ");
        $stmt->execute([$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($order && $order['service_ids']) {
            $order['service_ids'] = explode(',', $order['service_ids']);
        } else {
            $order['service_ids'] = [];
        }
        return $order;
    }
}