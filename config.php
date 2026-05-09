<?php
// config.php – параметры подключения к БД
define('DB_HOST', 'localhost');
define('DB_USER', 'u82316');
define('DB_PASS', '1579856');
define('DB_NAME', 'u82316');

// Время жизни сессии (30 дней)
ini_set('session.gc_maxlifetime', 2592000);
session_start();

// Режим отображения ошибок (для разработки – включить, на проде – выключить)
error_reporting(E_ALL);
ini_set('display_errors', 0);        // 1 – для отладки, 0 – для продакшена
ini_set('log_errors', 1);