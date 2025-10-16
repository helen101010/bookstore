<?php
require_once 'config.php';
session_start();

header('Content-Type: application/json');

echo json_encode([
    'authenticated' => isset($_SESSION['user_id']),
    'role' => $_SESSION['role'] ?? null,
    'username' => $_SESSION['username'] ?? null,
    'user_id' => $_SESSION['user_id'] ?? null
]);
?>