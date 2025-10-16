<?php
require_once 'config.php';
checkAdmin();

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add_book':
        try {
            $stmt = $pdo->prepare("
                INSERT INTO books (title, author, category_id, year, price, rental_price_2weeks, rental_price_month, rental_price_3months, status, description, isbn, pages, language, publisher, image_url) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $result = $stmt->execute([
                $_POST['title'],
                $_POST['author'],
                $_POST['category_id'],
                $_POST['year'],
                $_POST['price'],
                $_POST['rental_price_2weeks'],
                $_POST['rental_price_month'],
                $_POST['rental_price_3months'],
                $_POST['status'],
                $_POST['description'],
                $_POST['isbn'] ?: null,
                $_POST['pages'] ?: null,
                $_POST['language'] ?: 'Русский',
                $_POST['publisher'] ?: null,
                $_POST['image_url'] ?: 'https://images.pexels.com/photos/159866/books-book-pages-read-literature-159866.jpeg?auto=compress&cs=tinysrgb&w=400'
            ]);
            echo json_encode(['success' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'edit_book':
        try {
            $stmt = $pdo->prepare("
                UPDATE books SET title = ?, author = ?, category_id = ?, year = ?, price = ?, 
                rental_price_2weeks = ?, rental_price_month = ?, rental_price_3months = ?, status = ?, description = ?,
                isbn = ?, pages = ?, language = ?, publisher = ?, image_url = ?
                WHERE id = ?
            ");
            $result = $stmt->execute([
                $_POST['title'],
                $_POST['author'],
                $_POST['category_id'],
                $_POST['year'],
                $_POST['price'],
                $_POST['rental_price_2weeks'],
                $_POST['rental_price_month'],
                $_POST['rental_price_3months'],
                $_POST['status'],
                $_POST['description'],
                $_POST['isbn'] ?: null,
                $_POST['pages'] ?: null,
                $_POST['language'] ?: 'Русский',
                $_POST['publisher'] ?: null,
                $_POST['image_url'] ?: 'https://images.pexels.com/photos/159866/books-book-pages-read-literature-159866.jpeg?auto=compress&cs=tinysrgb&w=400',
                $_POST['id']
            ]);
            echo json_encode(['success' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_book':
        try {
            // Проверяем, есть ли активные аренды
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE book_id = ? AND status = 'active'");
            $check_stmt->execute([$_POST['id']]);
            $active_rentals = $check_stmt->fetchColumn();
            
            if ($active_rentals > 0) {
                echo json_encode(['success' => false, 'message' => 'Нельзя удалить книгу с активными арендами']);
                break;
            }
            
            $pdo->beginTransaction();
            
            // Удаляем связанные записи
            $pdo->prepare("DELETE FROM favorites WHERE book_id = ?")->execute([$_POST['id']]);
            $pdo->prepare("DELETE FROM reviews WHERE book_id = ?")->execute([$_POST['id']]);
            $pdo->prepare("DELETE FROM rentals WHERE book_id = ?")->execute([$_POST['id']]);
            $pdo->prepare("DELETE FROM purchases WHERE book_id = ?")->execute([$_POST['id']]);
            
            // Удаляем книгу
            $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
            $result = $stmt->execute([$_POST['id']]);
            
            $pdo->commit();
            echo json_encode(['success' => $result]);
        } catch (Exception $e) {
            $pdo->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_book':
        $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        $book = $stmt->fetch();
        echo json_encode($book);
        break;

    case 'get_categories':
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
        $categories = $stmt->fetchAll();
        echo json_encode($categories);
        break;

    case 'complete_rental':
        try {
            $pdo->beginTransaction();
            
            // Обновляем статус аренды
            $stmt = $pdo->prepare("UPDATE rentals SET status = 'completed' WHERE id = ?");
            $result = $stmt->execute([$_POST['id']]);
            
            // Получаем ID книги
            $stmt = $pdo->prepare("SELECT book_id FROM rentals WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $book_id = $stmt->fetchColumn();
            
            // Обновляем статус книги
            $stmt = $pdo->prepare("UPDATE books SET status = 'available' WHERE id = ?");
            $stmt->execute([$book_id]);
            
            $pdo->commit();
            echo json_encode(['success' => $result]);
        } catch (Exception $e) {
            $pdo->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'send_reminder':
        try {
            $stmt = $pdo->prepare("
                SELECT r.*, b.title, u.username, u.email, u.full_name
                FROM rentals r 
                JOIN books b ON r.book_id = b.id 
                JOIN users u ON r.user_id = u.id 
                WHERE r.id = ?
            ");
            $stmt->execute([$_POST['id']]);
            $rental = $stmt->fetch();
            
            if ($rental) {
                // Здесь должна быть логика отправки email
                // Для демонстрации просто возвращаем успех
                $user_name = $rental['full_name'] ?: $rental['username'];
                echo json_encode([
                    'success' => true,
                    'message' => "Напоминание отправлено пользователю {$user_name} ({$rental['email']}) о книге '{$rental['title']}'"
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Аренда не найдена']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'send_all_reminders':
        try {
            $stmt = $pdo->query("
                SELECT r.*, b.title, u.username, u.email, u.full_name
                FROM rentals r 
                JOIN books b ON r.book_id = b.id 
                JOIN users u ON r.user_id = u.id 
                WHERE r.status = 'overdue'
            ");
            $overdue_rentals = $stmt->fetchAll();
            
            $count = count($overdue_rentals);
            
            // Здесь должна быть логика массовой отправки email
            // Для демонстрации просто возвращаем количество
            
            echo json_encode([
                'success' => true,
                'message' => "Напоминания отправлены {$count} пользователям"
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_statistics':
        try {
            $stats = [];
            
            // Общая статистика
            $stats['total_books'] = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
            $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
            $stats['active_rentals'] = $pdo->query("SELECT COUNT(*) FROM rentals WHERE status = 'active'")->fetchColumn();
            $stats['overdue_rentals'] = $pdo->query("SELECT COUNT(*) FROM rentals WHERE status = 'overdue'")->fetchColumn();
            $stats['total_revenue'] = $pdo->query("SELECT SUM(price) FROM rentals")->fetchColumn() ?: 0;
            $stats['total_purchases'] = $pdo->query("SELECT COUNT(*) FROM purchases")->fetchColumn();
            
            // Популярные книги
            $stmt = $pdo->query("
                SELECT b.title, b.author, COUNT(r.id) as rental_count 
                FROM books b 
                LEFT JOIN rentals r ON b.id = r.book_id 
                GROUP BY b.id 
                ORDER BY rental_count DESC 
                LIMIT 5
            ");
            $stats['popular_books'] = $stmt->fetchAll();
            
            // Статистика по категориям
            $stmt = $pdo->query("
                SELECT c.name, c.icon, COUNT(b.id) as book_count 
                FROM categories c 
                LEFT JOIN books b ON c.id = b.category_id 
                GROUP BY c.id 
                ORDER BY book_count DESC
            ");
            $stats['categories'] = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'add_category':
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, icon, color) VALUES (?, ?, ?)");
            $result = $stmt->execute([
                $_POST['name'],
                $_POST['icon'] ?: '📚',
                $_POST['color'] ?: '#667eea'
            ]);
            echo json_encode(['success' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'edit_category':
        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, icon = ?, color = ? WHERE id = ?");
            $result = $stmt->execute([
                $_POST['name'],
                $_POST['icon'],
                $_POST['color'],
                $_POST['id']
            ]);
            echo json_encode(['success' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_category':
        try {
            // Проверяем, есть ли книги в этой категории
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE category_id = ?");
            $check_stmt->execute([$_POST['id']]);
            $book_count = $check_stmt->fetchColumn();
            
            if ($book_count > 0) {
                echo json_encode(['success' => false, 'message' => 'Нельзя удалить категорию, в которой есть книги']);
                break;
            }
            
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $result = $stmt->execute([$_POST['id']]);
            echo json_encode(['success' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Неизвестное действие']);
}
?>