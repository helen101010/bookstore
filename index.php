<?php
require_once 'config.php';
session_start();

// Проверка просроченных аренд
checkOverdueRentals($pdo);

// Получение фильтров
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$author_filter = isset($_GET['author']) ? $_GET['author'] : '';
$year_filter = isset($_GET['year']) ? $_GET['year'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'title';

// Построение SQL запроса
$sql = "SELECT b.*, c.name as category_name, c.icon as category_icon, c.color as category_color,
        (SELECT AVG(rating) FROM reviews WHERE book_id = b.id) as avg_rating,
        (SELECT COUNT(*) FROM reviews WHERE book_id = b.id) as review_count
        FROM books b 
        LEFT JOIN categories c ON b.category_id = c.id 
        WHERE 1=1";

$params = [];

if (!empty($category_filter)) {
    $sql .= " AND b.category_id = :category";
    $params[':category'] = $category_filter;
}

if (!empty($author_filter)) {
    $sql .= " AND b.author LIKE :author";
    $params[':author'] = '%' . $author_filter . '%';
}

if (!empty($year_filter)) {
    $sql .= " AND b.year = :year";
    $params[':year'] = $year_filter;
}

if (!empty($search)) {
    $sql .= " AND (b.title LIKE :search OR b.author LIKE :search2 OR b.description LIKE :search3)";
    $params[':search'] = '%' . $search . '%';
    $params[':search2'] = '%' . $search . '%';
    $params[':search3'] = '%' . $search . '%';
}

$sql .= " ORDER BY " . $sort_by;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

// Получение категорий для фильтра
$categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $categories_stmt->fetchAll();

// Получение авторов для фильтра
$authors_stmt = $pdo->query("SELECT DISTINCT author FROM books ORDER BY author");
$authors = $authors_stmt->fetchAll();

// Статистика для пользователя
$user_stats = null;
if (isset($_SESSION['user_id'])) {
    $stats_stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM purchases WHERE user_id = ?) as purchases_count,
            (SELECT COUNT(*) FROM rentals WHERE user_id = ? AND status = 'active') as active_rentals,
            (SELECT COUNT(*) FROM favorites WHERE user_id = ?) as favorites_count
    ");
    $stats_stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
    $user_stats = $stats_stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📚 BookStore - Лучший книжный магазин</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1><i class="fas fa-book-open"></i> BookStore</h1>
                    <span class="tagline">Мир книг в ваших руках</span>
                </div>
                
                <div class="search-bar">
                    <form method="GET" class="search-form">
                        <div class="search-input-group">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" value="<?php echo h($search); ?>" 
                                   placeholder="Поиск книг, авторов...">
                            <button type="submit" class="search-btn">Найти</button>
                        </div>
                        <?php if ($category_filter || $author_filter || $year_filter || $sort_by !== 'title'): ?>
                            <input type="hidden" name="category" value="<?php echo h($category_filter); ?>">
                            <input type="hidden" name="author" value="<?php echo h($author_filter); ?>">
                            <input type="hidden" name="year" value="<?php echo h($year_filter); ?>">
                            <input type="hidden" name="sort" value="<?php echo h($sort_by); ?>">
                        <?php endif; ?>
                    </form>
                </div>

                <nav class="nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="user-menu">
                            <div class="user-avatar">
                                <img src="<?php echo h($_SESSION['avatar_url'] ?? 'https://images.pexels.com/photos/771742/pexels-photo-771742.jpeg?auto=compress&cs=tinysrgb&w=150'); ?>" 
                                     alt="Avatar">
                                <span class="user-name"><?php echo h($_SESSION['username']); ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="dropdown-menu">
                                <a href="profile.php"><i class="fas fa-user"></i> Профиль</a>
                                <?php if ($user_stats): ?>
                                    <div class="user-stats">
                                        <span><i class="fas fa-shopping-cart"></i> Покупки: <?php echo $user_stats['purchases_count']; ?></span>
                                        <span><i class="fas fa-clock"></i> Аренды: <?php echo $user_stats['active_rentals']; ?></span>
                                        <span><i class="fas fa-heart"></i> Избранное: <?php echo $user_stats['favorites_count']; ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                    <a href="admin.php" class="admin-link"><i class="fas fa-cog"></i> Админка</a>
                                <?php endif; ?>
                                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Выйти</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Войти
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <!-- Фильтры -->
            <div class="filters-section">
                <div class="filters-header">
                    <h2><i class="fas fa-filter"></i> Фильтры и сортировка</h2>
                    <button class="filters-toggle" onclick="toggleFilters()">
                        <i class="fas fa-sliders-h"></i>
                    </button>
                </div>
                        <i class="fas fa-undo"></i> Сбросить
                <div class="filters-content" id="filtersContent">
                    <form method="GET" class="filters-form">
                        <input type="hidden" name="search" value="<?php echo h($search); ?>">
                        
                        <div class="filter-group">
                            <label><i class="fas fa-tags"></i> Категория:</label>
                            <select name="category" class="filter-select">
                                <option value="">Все категории</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                        <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo $category['icon'] . ' ' . h($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label><i class="fas fa-user-edit"></i> Автор:</label>
                            <input type="text" name="author" value="<?php echo h($author_filter); ?>" 
                                   placeholder="Поиск по автору" class="filter-input">
                        </div>

                        <div class="filter-group">
                            <label><i class="fas fa-calendar"></i> Год:</label>
                            <input type="number" name="year" value="<?php echo h($year_filter); ?>" 
                                   placeholder="Год издания" class="filter-input">
                        </div>

                        <div class="filter-group">
                            <label><i class="fas fa-sort"></i> Сортировать:</label>
                            <select name="sort" class="filter-select">
                                <option value="title" <?php echo $sort_by == 'title' ? 'selected' : ''; ?>>По названию</option>
                                <option value="author" <?php echo $sort_by == 'author' ? 'selected' : ''; ?>>По автору</option>
                                <option value="year DESC" <?php echo $sort_by == 'year DESC' ? 'selected' : ''; ?>>По году (новые)</option>
                                <option value="year ASC" <?php echo $sort_by == 'year ASC' ? 'selected' : ''; ?>>По году (старые)</option>
                                <option value="price ASC" <?php echo $sort_by == 'price ASC' ? 'selected' : ''; ?>>По цене (дешевые)</option>
                                <option value="price DESC" <?php echo $sort_by == 'price DESC' ? 'selected' : ''; ?>>По цене (дорогие)</option>
                                <option value="rating DESC" <?php echo $sort_by == 'rating DESC' ? 'selected' : ''; ?>>По рейтингу</option>
                            </select>
                        </div>

                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Применить
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Сбросить
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Результаты -->
            <div class="results-info">
                <span class="results-count">Найдено книг: <strong><?php echo count($books); ?></strong></span>
                <?php if ($search): ?>
                    <span class="search-query">по запросу: "<strong><?php echo h($search); ?></strong>"</span>
                <?php endif; ?>
            </div>

            <!-- Сетка книг -->
            <div class="books-grid" id="booksGrid">
                <?php if (empty($books)): ?>
                    <div class="no-books">
                        <i class="fas fa-book-open"></i>
                        <h3>Книги не найдены</h3>
                        <p>Попробуйте изменить параметры поиска</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($books as $book): ?>
                        <div class="book-card" data-book-id="<?php echo $book['id']; ?>">
                            <div class="book-image">
                                <img src="<?php echo h($book['image_url']); ?>" alt="<?php echo h($book['title']); ?>" loading="lazy">
                                <div class="book-overlay">
                                    <button class="btn-icon" onclick="toggleFavorite(<?php echo $book['id']; ?>)" title="В избранное">
                                        <i class="fas fa-heart"></i>
                                    </button>
                                    <button class="btn-icon" onclick="showBookDetails(<?php echo $book['id']; ?>)" title="Подробнее">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="book-status status-<?php echo $book['status']; ?>">
                                    <?php
                                    switch ($book['status']) {
                                        case 'available':
                                            echo '<i class="fas fa-check-circle"></i> Доступна';
                                            break;
                                        case 'unavailable':
                                            echo '<i class="fas fa-times-circle"></i> Недоступна';
                                            break;
                                        case 'rented':
                                            echo '<i class="fas fa-clock"></i> Арендована';
                                            break;
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <div class="book-content">
                                <div class="book-category" style="color: <?php echo h($book['category_color']); ?>">
                                    <?php echo $book['category_icon'] . ' ' . h($book['category_name']); ?>
                                </div>
                                
                                <h3 class="book-title"><?php echo h($book['title']); ?></h3>
                                <p class="book-author">
                                    <i class="fas fa-user-edit"></i> <?php echo h($book['author']); ?>
                                </p>
                                <p class="book-year">
                                    <i class="fas fa-calendar"></i> <?php echo $book['year']; ?>
                                </p>
                                
                                <?php if ($book['avg_rating']): ?>
                                    <div class="book-rating">
                                        <div class="stars">
                                            <?php
                                            $rating = round($book['avg_rating']);
                                            for ($i = 1; $i <= 5; $i++) {
                                                echo $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                            }
                                            ?>
                                        </div>
                                        <span class="rating-text"><?php echo number_format($book['avg_rating'], 1); ?> (<?php echo $book['review_count']; ?>)</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="book-prices">
                                    <div class="price-buy">
                                        <span class="price-label">Купить:</span>
                                        <span class="price-value"><?php echo formatPrice($book['price']); ?></span>
                                    </div>
                                    <div class="price-rent">
                                        <span class="price-label">Аренда от:</span>
                                        <span class="price-value"><?php echo formatPrice($book['rental_price_2weeks']); ?></span>
                                    </div>
                                </div>
                                
                                <?php if ($book['status'] === 'available' && isset($_SESSION['user_id'])): ?>
                                    <div class="book-actions">
                                        <button onclick="showRentalModal(<?php echo $book['id']; ?>)" class="btn btn-rental">
                                            <i class="fas fa-clock"></i> Арендовать
                                        </button>
                                        <button onclick="purchaseBook(<?php echo $book['id']; ?>)" class="btn btn-purchase">
                                            <i class="fas fa-shopping-cart"></i> Купить
                                        </button>
                                    </div>
                                <?php elseif (!isset($_SESSION['user_id'])): ?>
                                    <div class="book-actions">
                                        <a href="login.php" class="btn btn-primary">
                                            <i class="fas fa-sign-in-alt"></i> Войти для покупки
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Модальные окна -->
    <?php include 'modals.php'; ?>

    <script src="script.js"></script>
</body>
</html>