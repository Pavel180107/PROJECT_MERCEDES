<?php
require_once __DIR__ . '/Database.php';

class CarModels {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAllModels() {
        $stmt = $this->db->query("SELECT * FROM auto_car_models ORDER BY base_price");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllPackages() {
        $stmt = $this->db->query("SELECT * FROM auto_packages ORDER BY price_add");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllServices() {
        $stmt = $this->db->query("SELECT id, name, price_add FROM auto_additional_services ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}