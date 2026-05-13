<?php
require_once __DIR__ . '/Database.php';

class UserManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Валидация ФИО (только буквы, пробелы, дефис)
    public static function validateFullName($name) {
        if (empty($name)) return 'ФИО обязательно для заполнения.';
        if (!preg_match('/^[а-яА-ЯёЁa-zA-Z\s\-]+$/u', $name)) 
            return 'ФИО может содержать только буквы (русские или латинские), пробелы и дефис.';
        if (strlen($name) > 150) return 'ФИО не должно превышать 150 символов.';
        return null;
    }

    public static function validatePhone($phone) {
        if (empty($phone)) return 'Телефон обязателен.';
        $clean = preg_replace('/[^\d+]/', '', $phone);
        if (!preg_match('/^(\+7|8)?\d{10}$/', $clean)) 
            return 'Телефон должен быть в формате +7XXXXXXXXXX или 8XXXXXXXXXX (10 цифр после кода).';
        return null;
    }

    public static function validateEmail($email) {
        if (empty($email)) return 'Email обязателен.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) 
            return 'Введите корректный email (например, name@example.com).';
        return null;
    }

    public static function validateConsent($consent) {
        if (!$consent) return 'Необходимо подтвердить согласие на обработку персональных данных.';
        return null;
    }

    // Проверка существования пользователя по email или телефону
    public function findUserByEmailOrPhone($email, $phone) {
        $stmt = $this->db->prepare("SELECT id, login FROM auto_users WHERE email = ? OR phone = ?");
        $stmt->execute([$email, $phone]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
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

    public function generatePassword($len = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        return substr(str_shuffle($chars), 0, $len);
    }

    // Создание нового пользователя (без проверки дублей – проверка должна быть вызвана отдельно)
    public function createUser($full_name, $email, $phone, $consent) {
        $errors = [];
        if ($err = self::validateFullName($full_name)) $errors['full_name'] = $err;
        if ($err = self::validateEmail($email)) $errors['email'] = $err;
        if ($err = self::validatePhone($phone)) $errors['phone'] = $err;
        if ($err = self::validateConsent($consent)) $errors['consent'] = $err;
        if (!empty($errors)) return ['success' => false, 'errors' => $errors];

        $login = $this->generateUniqueLogin();
        $plainPass = $this->generatePassword();
        $passHash = password_hash($plainPass, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare("
            INSERT INTO auto_users (full_name, email, phone, consent, login, password_hash)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$full_name, $email, $phone, $consent ? 1 : 0, $login, $passHash]);
        return [
            'success' => true,
            'id' => $this->db->lastInsertId(),
            'login' => $login,
            'password' => $plainPass
        ];
    }

    // Обновление пользователя
    public function updateUser($userId, $full_name, $email, $phone, $consent) {
        $errors = [];
        if ($err = self::validateFullName($full_name)) $errors['full_name'] = $err;
        if ($err = self::validateEmail($email)) $errors['email'] = $err;
        if ($err = self::validatePhone($phone)) $errors['phone'] = $err;
        if ($err = self::validateConsent($consent)) $errors['consent'] = $err;
        if (!empty($errors)) return ['success' => false, 'errors' => $errors];

        $stmt = $this->db->prepare("
            UPDATE auto_users 
            SET full_name = ?, email = ?, phone = ?, consent = ?
            WHERE id = ?
        ");
        $stmt->execute([$full_name, $email, $phone, $consent ? 1 : 0, $userId]);
        return ['success' => true];
    }

    public function authenticate($login, $password) {
        $stmt = $this->db->prepare("SELECT id, login, password_hash FROM auto_users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user['id'];
        }
        return false;
    }

    public function getUserById($id) {
        $stmt = $this->db->prepare("SELECT id, full_name, email, phone, consent, login FROM auto_users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}