<?php
require_once 'config.php';

// Проверяем авторизацию для некоторых действий
$protected_actions = ['rent_book', 'purchase_book', 'toggle_favorite', 'get_favorites'];
$action = $_POST['action'] ?? '';

if (in_array($action, $protected_actions)) {
    checkAuth();
}

header('Content-Type: application/json');

switch ($action) {
    case 'get_book_details':
        $stmt = $pdo->prepare("
            SELECT b.*, c.name as category_name, c.icon as category_icon, c.color as category_color,
            (SELECT AVG(rating) FROM reviews WHERE book_id = b.id) as avg_rating,
            (SELECT COUNT(*) FROM reviews WHERE book_id = b.id) as review_count
            FROM books b 
            LEFT JOIN categories c ON b.category_id = c.id 
            WHERE b.id = ?
        ");
        $stmt->execute([$_POST['id']]);
        $book = $stmt->fetch();
        echo json_encode($book);
        break;

    case 'rent_book':
        $book_id = $_POST['book_id'];
        $rental_type = $_POST['rental_type'];
        $user_id = $_SESSION['user_id'];
        
        try {
            // Проверяем доступность книги
            $check_stmt = $pdo->prepare("SELECT status FROM books WHERE id = ?");
            $check_stmt->execute([$book_id]);
            $book_status = $check_stmt->fetchColumn();
            
            if ($book_status !== 'available') {
                echo json_encode(['success' => false, 'message' => 'Книга недоступна для аренды']);
                break;
            }
            
            // Получаем цену аренды
            $stmt = $pdo->prepare("SELECT rental_price_2weeks, rental_price_month, rental_price_3months FROM books WHERE id = ?");
            $stmt->execute([$book_id]);
            $book = $stmt->fetch();
            
            $price = 0;
            $days = 0;
            
            switch ($rental_type) {
                case '2weeks':
                    $price = $book['rental_price_2weeks'];
                    $days = 14;
                    break;
                case 'month':
                    $price = $book['rental_price_month'];
                    $days = 30;
                    break;
                case '3months':
                    $price = $book['rental_price_3months'];
                    $days = 90;
                    break;
                default:
                    echo json_encode(['success' => false, 'message' => 'Неверный тип аренды']);
                    exit;
            }
            
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d', strtotime("+{$days} days"));
            
            $pdo->beginTransaction();
            
            // Добавляем аренду
            $stmt = $pdo->prepare("INSERT INTO rentals (user_id, book_id, rental_type, start_date, end_date, price) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $book_id, $rental_type, $start_date, $end_date, $price]);
            
            // Обновляем статус книги
            $stmt = $pdo->prepare("UPDATE books SET status = 'rented' WHERE id = ?");
            $stmt->execute([$book_id]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Книга успешно арендована']);
        } catch (Exception $e) {
            $pdo->rollback();
            echo json_encode(['success' => false, 'message' => 'Ошибка при аренде книги: ' . $e->getMessage()]);
        }
        break;

    case 'purchase_book':
        $book_id = $_POST['book_id'];
        $user_id = $_SESSION['user_id'];
        
        try {
            // Проверяем доступность книги
            $check_stmt = $pdo->prepare("SELECT status, price FROM books WHERE id = ?");
            $check_stmt->execute([$book_id]);
            $book = $check_stmt->fetch();
            
            if (!$book) {
                echo json_encode(['success' => false, 'message' => 'Книга не найдена']);
                break;
            }
            
            $pdo->beginTransaction();
            
            // Добавляем покупку
            $stmt = $pdo->prepare("INSERT INTO purchases (user_id, book_id, purchase_date, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $book_id, date('Y-m-d'), $book['price']]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Книга успешно куплена']);
        } catch (Exception $e) {
            $pdo->rollback();
            echo json_encode(['success' => false, 'message' => 'Ошибка при покупке книги: ' . $e->getMessage()]);
        }
        break;

    case 'toggle_favorite':
        $book_id = $_POST['book_id'];
        $user_id = $_SESSION['user_id'];
        
        try {
            // Проверяем, есть ли книга в избранном
            $check_stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND book_id = ?");
            $check_stmt->execute([$user_id, $book_id]);
            $exists = $check_stmt->fetch();
            
            if ($exists) {
                // Удаляем из избранного
                $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND book_id = ?");
                $stmt->execute([$user_id, $book_id]);
                echo json_encode(['success' => true, 'message' => 'Книга удалена из избранного', 'added' => false]);
            } else {
                // Добавляем в избранное
                $stmt = $pdo->prepare("INSERT INTO favorites (user_id, book_id) VALUES (?, ?)");
                $stmt->execute([$user_id, $book_id]);
                echo json_encode(['success' => true, 'message' => 'Книга добавлена в избранное', 'added' => true]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Ошибка при работе с избранным: ' . $e->getMessage()]);
        }
        break;

    case 'get_favorites':
        $user_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT book_id FROM favorites WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $favorites = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode($favorites);
        break;

    case 'update_views':
        $book_id = $_POST['book_id'];
        $stmt = $pdo->prepare("UPDATE books SET views = views + 1 WHERE id = ?");
        $stmt->execute([$book_id]);
        echo json_encode(['success' => true]);
        break;

    case 'add_review':
        $book_id = $_POST['book_id'];
        $rating = $_POST['rating'];
        $comment = $_POST['comment'];
        $user_id = $_SESSION['user_id'];
        
        try {
            // Проверяем, не оставлял ли пользователь уже отзыв
            $check_stmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND book_id = ?");
            $check_stmt->execute([$user_id, $book_id]);
            
            if ($check_stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Вы уже оставляли отзыв на эту книгу']);
                break;
            }
            
            $stmt = $pdo->prepare("INSERT INTO reviews (user_id, book_id, rating, comment) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $book_id, $rating, $comment]);
            
            // Обновляем средний рейтинг книги
            $avg_stmt = $pdo->prepare("UPDATE books SET rating = (SELECT AVG(rating) FROM reviews WHERE book_id = ?) WHERE id = ?");
            $avg_stmt->execute([$book_id, $book_id]);
            
            echo json_encode(['success' => true, 'message' => 'Отзыв успешно добавлен']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Ошибка при добавлении отзыва: ' . $e->getMessage()]);
        }
        break;

    case 'get_reviews':
        $book_id = $_POST['book_id'];
        $stmt = $pdo->prepare("
            SELECT r.*, u.username, u.full_name 
            FROM reviews r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.book_id = ? 
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$book_id]);
        $reviews = $stmt->fetchAll();
        echo json_encode($reviews);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Неизвестное действие']);
}
?>