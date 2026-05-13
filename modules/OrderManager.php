<?php
require_once __DIR__ . '/Database.php';

class OrderManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Получить последний заказ пользователя (для отображения в форме на главной)
     */
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

    /**
     * Получить все заказы пользователя с порядковыми номерами
     */
    public function getUserOrders($userId) {
        $stmt = $this->db->prepare("
            SELECT o.*,
                   ROW_NUMBER() OVER (PARTITION BY o.user_id ORDER BY o.id) as order_num,
                   m.name as model_name,
                   p.name as package_name,
                   GROUP_CONCAT(s.name SEPARATOR ', ') as services_list,
                   GROUP_CONCAT(s.id SEPARATOR ',') as service_ids
            FROM auto_orders o
            JOIN auto_car_models m ON o.model_id = m.id
            LEFT JOIN auto_packages p ON o.package_id = p.id
            LEFT JOIN auto_order_services os ON o.id = os.order_id
            LEFT JOIN auto_additional_services s ON os.service_id = s.id
            WHERE o.user_id = ?
            GROUP BY o.id
            ORDER BY o.id
        ");
        $stmt->execute([$userId]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($orders as &$order) {
            $order['service_ids'] = $order['service_ids'] ? explode(',', $order['service_ids']) : [];
        }
        return $orders;
    }

    /**
     * Получить конкретный заказ по ID (с данными пользователя)
     */
    public function getOrderById($orderId) {
        $stmt = $this->db->prepare("
            SELECT o.*, u.full_name, u.email, u.phone,
                   (SELECT GROUP_CONCAT(service_id) FROM auto_order_services WHERE order_id = o.id) as service_ids
            FROM auto_orders o
            JOIN auto_users u ON o.user_id = u.id
            WHERE o.id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($order && $order['service_ids']) {
            $order['service_ids'] = explode(',', $order['service_ids']);
        } else {
            $order['service_ids'] = [];
        }
        return $order;
    }

    /**
     * Создать новый заказ
     */
    public function createOrder($userId, $modelId, $packageId, $serviceIds, $totalPrice) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO auto_orders (user_id, model_id, package_id, total_price, status)
                VALUES (?, ?, ?, ?, 'new')
            ");
            $stmt->execute([$userId, $modelId, $packageId, $totalPrice]);
            $orderId = $this->db->lastInsertId();

            if (!empty($serviceIds)) {
                $ins = $this->db->prepare("INSERT INTO auto_order_services (order_id, service_id) VALUES (?, ?)");
                foreach ($serviceIds as $sid) {
                    $ins->execute([$orderId, $sid]);
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

    /**
     * Обновить существующий заказ
     */
    public function updateOrder($orderId, $modelId, $packageId, $serviceIds, $totalPrice) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                UPDATE auto_orders 
                SET model_id = ?, package_id = ?, total_price = ?
                WHERE id = ?
            ");
            $stmt->execute([$modelId, $packageId, $totalPrice, $orderId]);

            $this->db->prepare("DELETE FROM auto_order_services WHERE order_id = ?")->execute([$orderId]);
            if (!empty($serviceIds)) {
                $ins = $this->db->prepare("INSERT INTO auto_order_services (order_id, service_id) VALUES (?, ?)");
                foreach ($serviceIds as $sid) {
                    $ins->execute([$orderId, $sid]);
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

    /**
     * Удалить заказ (с проверкой, что он принадлежит пользователю)
     */
    public function deleteOrder($orderId, $userId) {
        $stmt = $this->db->prepare("SELECT id FROM auto_orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$orderId, $userId]);
        if (!$stmt->fetch()) return false;
        $this->db->prepare("DELETE FROM auto_order_services WHERE order_id = ?")->execute([$orderId]);
        $this->db->prepare("DELETE FROM auto_orders WHERE id = ?")->execute([$orderId]);
        return true;
    }
}