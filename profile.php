<?php
require_once 'config.php';
checkAuth();

$user_id = $_SESSION['user_id'];

// Получение данных пользователя
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();

// Обработка обновления профиля
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    
    $update_stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
    $success = $update_stmt->execute([$full_name, $email, $phone, $address, $user_id]);
    
    if ($success) {
        $message = "Профиль успешно обновлен!";
        // Обновляем данные пользователя
        $user_stmt->execute([$user_id]);
        $user = $user_stmt->fetch();
    } else {
        $error = "Ошибка при обновлении профиля";
    }
}

// Получение статистики пользователя
$stats_stmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM purchases WHERE user_id = ?) as total_purchases,
        (SELECT SUM(price) FROM purchases WHERE user_id = ?) as total_spent_purchases,
        (SELECT COUNT(*) FROM rentals WHERE user_id = ?) as total_rentals,
        (SELECT SUM(price) FROM rentals WHERE user_id = ?) as total_spent_rentals,
        (SELECT COUNT(*) FROM rentals WHERE user_id = ? AND status = 'active') as active_rentals,
        (SELECT COUNT(*) FROM rentals WHERE user_id = ? AND status = 'overdue') as overdue_rentals,
        (SELECT COUNT(*) FROM favorites WHERE user_id = ?) as favorites_count,
        (SELECT COUNT(*) FROM reviews WHERE user_id = ?) as reviews_count
");
$stats_stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
$stats = $stats_stmt->fetch();

// Получение последних покупок
$purchases_stmt = $pdo->prepare("
    SELECT p.*, b.title, b.author, b.image_url 
    FROM purchases p 
    JOIN books b ON p.book_id = b.id 
    WHERE p.user_id = ? 
    ORDER BY p.created_at DESC 
    LIMIT 5
");
$purchases_stmt->execute([$user_id]);
$recent_purchases = $purchases_stmt->fetchAll();

// Получение активных аренд
$rentals_stmt = $pdo->prepare("
    SELECT r.*, b.title, b.author, b.image_url 
    FROM rentals r 
    JOIN books b ON r.book_id = b.id 
    WHERE r.user_id = ? AND r.status IN ('active', 'overdue')
    ORDER BY r.end_date ASC
");
$rentals_stmt->execute([$user_id]);
$active_rentals = $rentals_stmt->fetchAll();

// Получение избранных книг
$favorites_stmt = $pdo->prepare("
    SELECT f.*, b.title, b.author, b.image_url, b.price, b.status 
    FROM favorites f 
    JOIN books b ON f.book_id = b.id 
    WHERE f.user_id = ? 
    ORDER BY f.created_at DESC
");
$favorites_stmt->execute([$user_id]);
$favorites = $favorites_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль - BookStore</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="index.php">
                        <h1><i class="fas fa-book-open"></i> BookStore</h1>
                    </a>
                </div>
                <nav class="nav">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> К каталогу
                    </a>
                    <a href="logout.php" class="btn btn-primary">
                        <i class="fas fa-sign-out-alt"></i> Выйти
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main profile-main">
        <div class="container">
            <div class="profile-header">
                <div class="profile-avatar">
                    <img src="<?php echo h($user['avatar_url']); ?>" alt="Avatar">
                    <div class="avatar-overlay">
                        <i class="fas fa-camera"></i>
                    </div>
                </div>
                <div class="profile-info">
                    <h1><?php echo h($user['full_name'] ?: $user['username']); ?></h1>
                    <p class="profile-username">@<?php echo h($user['username']); ?></p>
                    <p class="profile-member-since">
                        <i class="fas fa-calendar"></i> 
                        Участник с <?php echo date('d.m.Y', strtotime($user['created_at'])); ?>
                    </p>
                </div>
            </div>

            <?php if (isset($message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="profile-tabs">
                <button class="tab-button active" onclick="showTab('overview')">
                    <i class="fas fa-chart-line"></i> Обзор
                </button>
                <button class="tab-button" onclick="showTab('purchases')">
                    <i class="fas fa-shopping-cart"></i> Покупки
                </button>
                <button class="tab-button" onclick="showTab('rentals')">
                    <i class="fas fa-clock"></i> Аренды
                </button>
                <button class="tab-button" onclick="showTab('favorites')">
                    <i class="fas fa-heart"></i> Избранное
                </button>
                <button class="tab-button" onclick="showTab('settings')">
                    <i class="fas fa-cog"></i> Настройки
                </button>
            </div>

            <!-- Обзор -->
            <div id="overview" class="tab-content active">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_purchases']; ?></h3>
                            <p>Куплено книг</p>
                            <span class="stat-amount"><?php echo formatPrice($stats['total_spent_purchases'] ?: 0); ?></span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_rentals']; ?></h3>
                            <p>Всего аренд</p>
                            <span class="stat-amount"><?php echo formatPrice($stats['total_spent_rentals'] ?: 0); ?></span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['favorites_count']; ?></h3>
                            <p>В избранном</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['reviews_count']; ?></h3>
                            <p>Отзывов написано</p>
                        </div>
                    </div>
                </div>

                <?php if ($stats['active_rentals'] > 0 || $stats['overdue_rentals'] > 0): ?>
                    <div class="active-rentals-section">
                        <h2><i class="fas fa-exclamation-triangle"></i> Активные аренды</h2>
                        <div class="rentals-list">
                            <?php foreach ($active_rentals as $rental): ?>
                                <div class="rental-item <?php echo $rental['status'] === 'overdue' ? 'overdue' : ''; ?>">
                                    <img src="<?php echo h($rental['image_url']); ?>" alt="<?php echo h($rental['title']); ?>">
                                    <div class="rental-info">
                                        <h4><?php echo h($rental['title']); ?></h4>
                                        <p><?php echo h($rental['author']); ?></p>
                                        <div class="rental-dates">
                                            <span class="rental-type"><?php echo $rental['rental_type']; ?></span>
                                            <span class="end-date">
                                                <?php if ($rental['status'] === 'overdue'): ?>
                                                    <i class="fas fa-exclamation-triangle"></i> Просрочено с <?php echo date('d.m.Y', strtotime($rental['end_date'])); ?>
                                                <?php else: ?>
                                                    <i class="fas fa-calendar"></i> До <?php echo date('d.m.Y', strtotime($rental['end_date'])); ?>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="rental-price">
                                        <?php echo formatPrice($rental['price']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Покупки -->
            <div id="purchases" class="tab-content">
                <h2><i class="fas fa-shopping-cart"></i> История покупок</h2>
                <?php if (empty($recent_purchases)): ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>Пока нет покупок</h3>
                        <p>Купите первую книгу в нашем каталоге!</p>
                        <a href="index.php" class="btn btn-primary">Перейти к каталогу</a>
                    </div>
                <?php else: ?>
                    <div class="purchases-list">
                        <?php foreach ($recent_purchases as $purchase): ?>
                            <div class="purchase-item">
                                <img src="<?php echo h($purchase['image_url']); ?>" alt="<?php echo h($purchase['title']); ?>">
                                <div class="purchase-info">
                                    <h4><?php echo h($purchase['title']); ?></h4>
                                    <p><?php echo h($purchase['author']); ?></p>
                                    <span class="purchase-date">
                                        <i class="fas fa-calendar"></i> <?php echo date('d.m.Y', strtotime($purchase['purchase_date'])); ?>
                                    </span>
                                </div>
                                <div class="purchase-price">
                                    <?php echo formatPrice($purchase['price']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Аренды -->
            <div id="rentals" class="tab-content">
                <h2><i class="fas fa-clock"></i> Аренды</h2>
                <?php if (empty($active_rentals)): ?>
                    <div class="empty-state">
                        <i class="fas fa-clock"></i>
                        <h3>Нет активных аренд</h3>
                        <p>Арендуйте книгу на удобный срок!</p>
                        <a href="index.php" class="btn btn-primary">Перейти к каталогу</a>
                    </div>
                <?php else: ?>
                    <div class="rentals-list">
                        <?php foreach ($active_rentals as $rental): ?>
                            <div class="rental-item <?php echo $rental['status'] === 'overdue' ? 'overdue' : ''; ?>">
                                <img src="<?php echo h($rental['image_url']); ?>" alt="<?php echo h($rental['title']); ?>">
                                <div class="rental-info">
                                    <h4><?php echo h($rental['title']); ?></h4>
                                    <p><?php echo h($rental['author']); ?></p>
                                    <div class="rental-dates">
                                        <span class="rental-type"><?php echo $rental['rental_type']; ?></span>
                                        <span class="rental-period">
                                            <?php echo date('d.m.Y', strtotime($rental['start_date'])); ?> - 
                                            <?php echo date('d.m.Y', strtotime($rental['end_date'])); ?>
                                        </span>
                                        <?php if ($rental['status'] === 'overdue'): ?>
                                            <span class="overdue-badge">
                                                <i class="fas fa-exclamation-triangle"></i> Просрочено
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="rental-price">
                                    <?php echo formatPrice($rental['price']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Избранное -->
            <div id="favorites" class="tab-content">
                <h2><i class="fas fa-heart"></i> Избранные книги</h2>
                <?php if (empty($favorites)): ?>
                    <div class="empty-state">
                        <i class="fas fa-heart"></i>
                        <h3>Избранное пусто</h3>
                        <p>Добавьте книги в избранное, чтобы не потерять их!</p>
                        <a href="index.php" class="btn btn-primary">Перейти к каталогу</a>
                    </div>
                <?php else: ?>
                    <div class="favorites-grid">
                        <?php foreach ($favorites as $favorite): ?>
                            <div class="favorite-item">
                                <img src="<?php echo h($favorite['image_url']); ?>" alt="<?php echo h($favorite['title']); ?>">
                                <div class="favorite-info">
                                    <h4><?php echo h($favorite['title']); ?></h4>
                                    <p><?php echo h($favorite['author']); ?></p>
                                    <div class="favorite-price"><?php echo formatPrice($favorite['price']); ?></div>
                                    <div class="favorite-actions">
                                        <button onclick="showBookDetails(<?php echo $favorite['book_id']; ?>)" class="btn btn-small">
                                            <i class="fas fa-eye"></i> Подробнее
                                        </button>
                                        <button onclick="toggleFavorite(<?php echo $favorite['book_id']; ?>)" class="btn btn-small btn-danger">
                                            <i class="fas fa-heart-broken"></i> Удалить
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Настройки -->
            <div id="settings" class="tab-content">
                <h2><i class="fas fa-cog"></i> Настройки профиля</h2>
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Полное имя:</label>
                        <input type="text" name="full_name" value="<?php echo h($user['full_name']); ?>" class="form-input">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email:</label>
                        <input type="email" name="email" value="<?php echo h($user['email']); ?>" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Телефон:</label>
                        <input type="tel" name="phone" value="<?php echo h($user['phone']); ?>" class="form-input">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Адрес:</label>
                        <textarea name="address" class="form-textarea"><?php echo h($user['address']); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Сохранить изменения
                    </button>
                </form>
            </div>
        </div>
    </main>

    <!-- Модальные окна -->
    <?php include 'modals.php'; ?>

    <script src="script.js"></script>
</body>
</html>