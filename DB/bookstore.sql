-- Создание базы данных книжного магазина
CREATE DATABASE IF NOT EXISTS bookstore;
USE bookstore;

-- Таблица пользователей
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица категорий
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

-- Таблица книг
CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    category_id INT,
    year INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    rental_price_2weeks DECIMAL(10, 2) NOT NULL,
    rental_price_month DECIMAL(10, 2) NOT NULL,
    rental_price_3months DECIMAL(10, 2) NOT NULL,
    status ENUM('available', 'unavailable', 'rented') DEFAULT 'available',
    availability INT DEFAULT 1,
    image_url VARCHAR(500) DEFAULT 'https://images.pexels.com/photos/159866/books-book-pages-read-literature-159866.jpeg?auto=compress&cs=tinysrgb&w=400',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Таблица аренды
CREATE TABLE rentals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    book_id INT,
    rental_type ENUM('2weeks', 'month', '3months') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'completed', 'overdue') DEFAULT 'active',
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (book_id) REFERENCES books(id)
);

-- Таблица покупок
CREATE TABLE purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    book_id INT,
    purchase_date DATE NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (book_id) REFERENCES books(id)
);

-- Вставка категорий
INSERT INTO categories (name) VALUES 
('Художественная литература'),
('Научная фантастика'),
('Детективы'),
('Биографии'),
('Наука'),
('История'),
('Философия'),
('Психология');

-- Вставка администратора
INSERT INTO users (username, password, email, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@bookstore.com', 'admin'),
('user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user@bookstore.com', 'user');

-- Вставка книг
INSERT INTO books (title, author, category_id, year, price, rental_price_2weeks, rental_price_month, rental_price_3months, description) VALUES 
('Война и мир', 'Лев Толстой', 1, 1869, 850.00, 120.00, 200.00, 350.00, 'Эпический роман о русском обществе в эпоху наполеоновских войн'),
('Преступление и наказание', 'Федор Достоевский', 1, 1866, 750.00, 100.00, 180.00, 320.00, 'Психологический роман о студенте Раскольникове'),
('1984', 'Джордж Оруэлл', 2, 1949, 650.00, 90.00, 150.00, 280.00, 'Антиутопия о тоталитарном государстве'),
('Убийство в Восточном экспрессе', 'Агата Кристи', 3, 1934, 550.00, 80.00, 140.00, 250.00, 'Детективный роман с участием Эркюля Пуаро'),
('Краткая история времени', 'Стивен Хокинг', 5, 1988, 900.00, 130.00, 220.00, 400.00, 'Популярная книга о космологии и физике'),
('Сапиенс', 'Юваль Ной Харари', 6, 2011, 800.00, 110.00, 190.00, 340.00, 'Краткая история человечества'),
('Думай медленно... решай быстро', 'Даниэль Канеман', 8, 2011, 700.00, 95.00, 160.00, 290.00, 'Книга о принятии решений и когнитивных искажениях'),
('Мастер и Маргарита', 'Михаил Булгаков', 1, 1967, 720.00, 105.00, 175.00, 310.00, 'Философский роман о добре и зле');