<?php
// Конфигурация базы данных
define('DB_HOST', 'localhost');
define('DB_NAME', 'bookstore');
define('DB_USER', 'root');
define('DB_PASS', '');

// Подключение к базе данных
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Функция для проверки авторизации
function checkAuth() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

// Функция для проверки прав администратора
function checkAdmin() {
    checkAuth();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: index.php');
        exit();
    }
}

// Функция для проверки просроченных аренд
function checkOverdueRentals($pdo) {
    $stmt = $pdo->prepare("UPDATE rentals SET status = 'overdue' WHERE end_date < CURDATE() AND status = 'active'");
    $stmt->execute();
}

// Функция для безопасного вывода HTML
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Функция для форматирования цены
function formatPrice($price) {
    return number_format($price, 2, '.', ' ') . ' ₽';
}

// Функция для получения времени назад
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'только что';
    if ($time < 3600) return floor($time/60) . ' мин. назад';
    if ($time < 86400) return floor($time/3600) . ' ч. назад';
    if ($time < 2592000) return floor($time/86400) . ' дн. назад';
    if ($time < 31536000) return floor($time/2592000) . ' мес. назад';
    return floor($time/31536000) . ' г. назад';
}

// Функция для получения текста статуса
function getStatusText($status) {
    switch ($status) {
        case 'available':
            return 'Доступна';
        case 'unavailable':
            return 'Недоступна';
        case 'rented':
            return 'Арендована';
        default:
            return 'Неизвестно';
    }
}
?>