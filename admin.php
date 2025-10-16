<?php
require_once 'config.php';
checkAdmin();

// Получение всех книг с дополнительной информацией
$books_stmt = $pdo->query("
    SELECT b.*, c.name as category_name, c.icon as category_icon, c.color as category_color,
    (SELECT COUNT(*) FROM rentals WHERE book_id = b.id) as total_rentals,
    (SELECT COUNT(*) FROM purchases WHERE book_id = b.id) as total_purchases
    FROM books b 
    LEFT JOIN categories c ON b.category_id = c.id 
    ORDER BY b.created_at DESC
");
$books = $books_stmt->fetchAll();

// Получение категорий
$categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $categories_stmt->fetchAll();

// Получение просроченных аренд
$overdue_stmt = $pdo->query("
    SELECT r.*, b.title, b.image_url, u.username, u.email, u.full_name
    FROM rentals r 
    JOIN books b ON r.book_id = b.id 
    JOIN users u ON r.user_id = u.id 
    WHERE r.status = 'overdue' 
    ORDER BY r.end_date ASC
");
$overdue_rentals = $overdue_stmt->fetchAll();

// Получение активных аренд
$active_stmt = $pdo->query("
    SELECT r.*, b.title, b.image_url, u.username, u.email, u.full_name
    FROM rentals r 
    JOIN books b ON r.book_id = b.id 
    JOIN users u ON r.user_id = u.id 
    WHERE r.status = 'active' 
    ORDER BY r.end_date ASC
");
$active_rentals = $active_stmt->fetchAll();

// Получение статистики
$stats_stmt = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM books) as total_books,
        (SELECT COUNT(*) FROM users WHERE role = 'user') as total_users,
        (SELECT COUNT(*) FROM rentals WHERE status = 'active') as active_rentals,
        (SELECT COUNT(*) FROM rentals WHERE status = 'overdue') as overdue_rentals,
        (SELECT SUM(price) FROM rentals) as total_revenue,
        (SELECT SUM(price) FROM purchases) as purchase_revenue,
        (SELECT COUNT(*) FROM purchases) as total_purchases
");
$stats = $stats_stmt->fetch();

// Получение популярных книг
$popular_stmt = $pdo->query("
    SELECT b.title, b.author, b.image_url, COUNT(r.id) as rental_count,
    (SELECT COUNT(*) FROM purchases WHERE book_id = b.id) as purchase_count
    FROM books b 
    LEFT JOIN rentals r ON b.id = r.book_id 
    GROUP BY b.id 
    ORDER BY rental_count DESC, purchase_count DESC
    LIMIT 5
");
$popular_books = $popular_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админка - BookStore</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1><i class="fas fa-cog"></i> Админка BookStore</h1>
                    <span class="tagline">Панель управления</span>
                </div>
                <nav class="nav">
                    <span class="admin-greeting">
                        <i class="fas fa-user-shield"></i>
                        Привет, <?php echo h($_SESSION['username']); ?>!
                    </span>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-store"></i> Каталог
                    </a>
                    <a href="logout.php" class="btn btn-primary">
                        <i class="fas fa-sign-out-alt"></i> Выйти
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <!-- Статистика -->
            <div class="admin-stats">
                <div class="stats-grid">
                    <div class="stat-card books">
                        <div class="stat-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_books']; ?></h3>
                            <p>Всего книг</p>
                        </div>
                    </div>

                    <div class="stat-card users">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_users']; ?></h3>
                            <p>Пользователей</p>
                        </div>
                    </div>

                    <div class="stat-card rentals">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['active_rentals']; ?></h3>
                            <p>Активных аренд</p>
                        </div>
                    </div>

                    <div class="stat-card overdue">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['overdue_rentals']; ?></h3>
                            <p>Просрочено</p>
                        </div>
                    </div>

                    <div class="stat-card revenue">
                        <div class="stat-icon">
                            <i class="fas fa-ruble-sign"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo formatPrice($stats['total_revenue'] + $stats['purchase_revenue']); ?></h3>
                            <p>Общий доход</p>
                        </div>
                    </div>

                    <div class="stat-card purchases">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_purchases']; ?></h3>
                            <p>Продаж</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Вкладки -->
            <div class="admin-tabs">
                <button class="tab-button active" onclick="showAdminTab('dashboard')">
                    <i class="fas fa-chart-line"></i> Дашборд
                </button>
                <button class="tab-button" onclick="showAdminTab('books')">
                    <i class="fas fa-book"></i> Книги
                </button>
                <button class="tab-button" onclick="showAdminTab('rentals')">
                    <i class="fas fa-clock"></i> Аренды
                </button>
                <button class="tab-button" onclick="showAdminTab('overdue')">
                    <i class="fas fa-exclamation-triangle"></i> Просроченные
                </button>
                <button class="tab-button" onclick="showAdminTab('categories')">
                    <i class="fas fa-tags"></i> Категории
                </button>
            </div>

            <!-- Дашборд -->
            <div id="dashboard" class="tab-content active">
                <h2><i class="fas fa-chart-line"></i> Обзор системы</h2>
                
                <div class="dashboard-grid">
                    <div class="dashboard-card">
                        <h3><i class="fas fa-fire"></i> Популярные книги</h3>
                        <div class="popular-books">
                            <?php foreach ($popular_books as $book): ?>
                                <div class="popular-book-item">
                                    <img src="<?php echo h($book['image_url']); ?>" alt="<?php echo h($book['title']); ?>">
                                    <div class="book-info">
                                        <h4><?php echo h($book['title']); ?></h4>
                                        <p><?php echo h($book['author']); ?></p>
                                        <div class="book-stats">
                                            <span><i class="fas fa-clock"></i> <?php echo $book['rental_count']; ?> аренд</span>
                                            <span><i class="fas fa-shopping-cart"></i> <?php echo $book['purchase_count']; ?> продаж</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="dashboard-card">
                        <h3><i class="fas fa-exclamation-triangle"></i> Требуют внимания</h3>
                        <div class="attention-items">
                            <?php if ($stats['overdue_rentals'] > 0): ?>
                                <div class="attention-item overdue">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <div>
                                        <strong><?php echo $stats['overdue_rentals']; ?> просроченных аренд</strong>
                                        <p>Необходимо отправить напоминания</p>
                                    </div>
                                    <button onclick="sendReminders()" class="btn btn-warning btn-small">
                                        Отправить
                                    </button>
                                </div>
                            <?php endif; ?>
                            
                            <div class="attention-item info">
                                <i class="fas fa-info-circle"></i>
                                <div>
                                    <strong>Система работает нормально</strong>
                                    <p>Все процессы функционируют корректно</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Управление книгами -->
            <div id="books" class="tab-content">
                <div class="admin-header">
                    <h2><i class="fas fa-book"></i> Управление книгами</h2>
                    <button onclick="showAddBookModal()" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Добавить книгу
                    </button>
                </div>
                
                <div class="books-table">
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Изображение</th>
                                    <th>Название</th>
                                    <th>Автор</th>
                                    <th>Категория</th>
                                    <th>Год</th>
                                    <th>Цена</th>
                                    <th>Статус</th>
                                    <th>Статистика</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($books as $book): ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo h($book['image_url']); ?>" alt="<?php echo h($book['title']); ?>" class="book-thumb">
                                        </td>
                                        <td>
                                            <strong><?php echo h($book['title']); ?></strong>
                                        </td>
                                        <td><?php echo h($book['author']); ?></td>
                                        <td>
                                            <span class="category-badge" style="color: <?php echo h($book['category_color']); ?>">
                                                <?php echo $book['category_icon'] . ' ' . h($book['category_name']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $book['year']; ?></td>
                                        <td><?php echo formatPrice($book['price']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $book['status']; ?>">
                                                <?php echo getStatusText($book['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="book-stats-mini">
                                                <span title="Аренды"><i class="fas fa-clock"></i> <?php echo $book['total_rentals']; ?></span>
                                                <span title="Продажи"><i class="fas fa-shopping-cart"></i> <?php echo $book['total_purchases']; ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button onclick="showBookDetails(<?php echo $book['id']; ?>)" class="btn btn-small btn-secondary" title="Подробнее">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button onclick="editBook(<?php echo $book['id']; ?>)" class="btn btn-small btn-primary" title="Редактировать">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="deleteBook(<?php echo $book['id']; ?>)" class="btn btn-small btn-danger" title="Удалить">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Активные аренды -->
            <div id="rentals" class="tab-content">
                <h2><i class="fas fa-clock"></i> Активные аренды</h2>
                <div class="rentals-table">
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Книга</th>
                                    <th>Пользователь</th>
                                    <th>Срок</th>
                                    <th>Дата начала</th>
                                    <th>Дата окончания</th>
                                    <th>Цена</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($active_rentals as $rental): ?>
                                    <tr>
                                        <td>
                                            <div class="rental-book">
                                                <img src="<?php echo h($rental['image_url']); ?>" alt="<?php echo h($rental['title']); ?>" class="book-thumb">
                                                <div>
                                                    <strong><?php echo h($rental['title']); ?></strong>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="user-info">
                                                <strong><?php echo h($rental['full_name'] ?: $rental['username']); ?></strong>
                                                <small><?php echo h($rental['email']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="rental-type-badge"><?php echo $rental['rental_type']; ?></span>
                                        </td>
                                        <td><?php echo date('d.m.Y', strtotime($rental['start_date'])); ?></td>
                                        <td>
                                            <?php 
                                            $end_date = strtotime($rental['end_date']);
                                            $today = time();
                                            $days_left = ceil(($end_date - $today) / (60 * 60 * 24));
                                            ?>
                                            <div class="end-date">
                                                <?php echo date('d.m.Y', $end_date); ?>
                                                <?php if ($days_left > 0): ?>
                                                    <small class="days-left">(<?php echo $days_left; ?> дн.)</small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo formatPrice($rental['price']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button onclick="completeRental(<?php echo $rental['id']; ?>)" class="btn btn-small btn-success" title="Завершить">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button onclick="sendReminder(<?php echo $rental['id']; ?>)" class="btn btn-small btn-warning" title="Напомнить">
                                                    <i class="fas fa-bell"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Просроченные аренды -->
            <div id="overdue" class="tab-content">
                <div class="admin-header">
                    <h2><i class="fas fa-exclamation-triangle"></i> Просроченные аренды</h2>
                    <?php if (!empty($overdue_rentals)): ?>
                        <button onclick="sendReminders()" class="btn btn-warning">
                            <i class="fas fa-bell"></i> Отправить напоминания всем
                        </button>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($overdue_rentals)): ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <h3>Нет просроченных аренд</h3>
                        <p>Все аренды возвращены вовремя!</p>
                    </div>
                <?php else: ?>
                    <div class="rentals-table">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Книга</th>
                                        <th>Пользователь</th>
                                        <th>Просрочено с</th>
                                        <th>Дней просрочки</th>
                                        <th>Цена</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($overdue_rentals as $rental): ?>
                                        <tr class="overdue-row">
                                            <td>
                                                <div class="rental-book">
                                                    <img src="<?php echo h($rental['image_url']); ?>" alt="<?php echo h($rental['title']); ?>" class="book-thumb">
                                                    <div>
                                                        <strong><?php echo h($rental['title']); ?></strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="user-info">
                                                    <strong><?php echo h($rental['full_name'] ?: $rental['username']); ?></strong>
                                                    <small><?php echo h($rental['email']); ?></small>
                                                </div>
                                            </td>
                                            <td><?php echo date('d.m.Y', strtotime($rental['end_date'])); ?></td>
                                            <td>
                                                <?php 
                                                $days_overdue = floor((time() - strtotime($rental['end_date'])) / (60 * 60 * 24));
                                                ?>
                                                <span class="overdue-days"><?php echo $days_overdue; ?> дн.</span>
                                            </td>
                                            <td><?php echo formatPrice($rental['price']); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button onclick="sendReminder(<?php echo $rental['id']; ?>)" class="btn btn-small btn-warning" title="Напомнить">
                                                        <i class="fas fa-bell"></i>
                                                    </button>
                                                    <button onclick="completeRental(<?php echo $rental['id']; ?>)" class="btn btn-small btn-success" title="Завершить">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Категории -->
            <div id="categories" class="tab-content">
                <div class="admin-header">
                    <h2><i class="fas fa-tags"></i> Управление категориями</h2>
                    <button onclick="showAddCategoryModal()" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Добавить категорию
                    </button>
                </div>
                
                <div class="categories-grid">
                    <?php foreach ($categories as $category): ?>
                        <div class="category-card">
                            <div class="category-header" style="background: <?php echo h($category['color']); ?>">
                                <span class="category-icon"><?php echo $category['icon']; ?></span>
                                <h3><?php echo h($category['name']); ?></h3>
                            </div>
                            <div class="category-body">
                                <?php
                                $book_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE category_id = ?");
                                $book_count_stmt->execute([$category['id']]);
                                $book_count = $book_count_stmt->fetchColumn();
                                ?>
                                <p><i class="fas fa-book"></i> <?php echo $book_count; ?> книг</p>
                                <div class="category-actions">
                                    <button onclick="editCategory(<?php echo $category['id']; ?>)" class="btn btn-small btn-primary">
                                        <i class="fas fa-edit"></i> Редактировать
                                    </button>
                                    <?php if ($book_count == 0): ?>
                                        <button onclick="deleteCategory(<?php echo $category['id']; ?>)" class="btn btn-small btn-danger">
                                            <i class="fas fa-trash"></i> Удалить
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Модальные окна -->
    <?php include 'modals.php'; ?>

    <script src="script.js"></script>
</body>
</html>