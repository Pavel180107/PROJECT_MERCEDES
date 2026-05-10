<?php
require_once __DIR__ . 'Database.php';

class CarModels {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Получить все модели
    public function getAllModels() {
        $stmt = $this->db->query("SELECT * FROM auto_car_models ORDER BY base_price");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Получить модель по ID
    public function getModelById($id) {
        $stmt = $this->db->prepare("SELECT * FROM auto_car_models WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Все пакеты
    public function getAllPackages() {
        $stmt = $this->db->query("SELECT * FROM auto_packages ORDER BY price_add");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Все доп. услуги
    public function getAllServices() {
        $stmt = $this->db->query("SELECT * FROM auto_additional_services ORDER BY price_add");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}