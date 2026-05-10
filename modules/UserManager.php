<?php
require_once __DIR__ . 'Database.php';

class UserManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Генерация уникального логина
    public function generateUniqueLogin() {
        do {
            $login = 'client_' . substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 8);
            $stmt = $this->db->prepare("SELECT id FROM auto_users WHERE login = ?");
            $stmt->execute([$login]);
        } while ($stmt->fetch());
        return $login;
    }

    // Генерация случайного пароля
    public function generatePassword($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        return substr(str_shuffle($chars), 0, $length);
    }

    // Создание нового пользователя
    public function createUser($full_name, $email, $phone, $consent) {
        $login = $this->generateUniqueLogin();
        $plainPass = $this->generatePassword();
        $passHash = password_hash($plainPass, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare("
            INSERT INTO auto_users (full_name, email, phone, consent, login, password_hash)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$full_name, $email, $phone, $consent ? 1 : 0, $login, $passHash]);
        $userId = $this->db->lastInsertId();

        return [
            'id' => $userId,
            'login' => $login,
            'password' => $plainPass
        ];
    }

    // Проверка логина/пароля
    public function authenticate($login, $password) {
        $stmt = $this->db->prepare("SELECT id, login, password_hash FROM auto_users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user['id'];
        }
        return false;
    }

    // Получить пользователя по ID
    public function getUserById($id) {
        $stmt = $this->db->prepare("SELECT id, full_name, email, phone, consent, login FROM auto_users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}