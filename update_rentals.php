<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Обновляем просроченные аренды
    $stmt = $pdo->prepare("UPDATE rentals SET status = 'overdue' WHERE end_date < CURDATE() AND status = 'active'");
    $stmt->execute();
    
    $updated_count = $stmt->rowCount();
    
    echo json_encode([
        'success' => true, 
        'message' => "Обновлено {$updated_count} записей",
        'updated_count' => $updated_count
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>