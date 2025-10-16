<?php
require_once 'config.php';
session_start();

// Если пользователь уже авторизован, перенаправляем
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin.php');
    } else {
        header('Location: index.php');
    }
    exit();
}

$error = '';
$success = '';

if ($_POST) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Заполните все поля';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['avatar_url'] = $user['avatar_url'];
            
            if ($user['role'] === 'admin') {
                header('Location: admin.php');
            } else {
                header('Location: index.php');
            }
            exit();
        } else {
            $error = 'Неверный логин или пароль';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - BookStore</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <h1><i class="fas fa-book-open"></i> BookStore</h1>
                    <p>Добро пожаловать в мир книг</p>
                </div>
            </div>
            
            <div class="login-form-container">
                <h2><i class="fas fa-sign-in-alt"></i> Вход в систему</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo h($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo h($success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="login-form">
                    <div class="form-group">
                        <label for="username">
                            <i class="fas fa-user"></i> Логин или Email
                        </label>
                        <input type="text" id="username" name="username" required 
                               value="<?php echo h($_POST['username'] ?? ''); ?>"
                               class="form-input" placeholder="Введите логин или email">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i> Пароль
                        </label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" required 
                                   class="form-input" placeholder="Введите пароль">
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="passwordIcon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-full">
                        <i class="fas fa-sign-in-alt"></i> Войти
                    </button>
                </form>
                
                <div class="login-divider">
                    <span>или</span>
                </div>
                
                <div class="demo-accounts">
                    <h3><i class="fas fa-users"></i> Демо-аккаунты</h3>
                    <div class="demo-buttons">
                        <button onclick="fillDemo('admin', 'password')" class="btn btn-secondary btn-demo">
                            <i class="fas fa-user-shield"></i> Войти как Админ
                        </button>
                        <button onclick="fillDemo('user', 'password')" class="btn btn-secondary btn-demo">
                            <i class="fas fa-user"></i> Войти как Пользователь
                        </button>
                    </div>
                    <div class="demo-info">
                        <p><strong>Админ:</strong> Полный доступ к управлению</p>
                        <p><strong>Пользователь:</strong> Просмотр, аренда и покупка книг</p>
                    </div>
                </div>
                
                <div class="login-footer">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Назад к каталогу
                    </a>
                </div>
            </div>
        </div>
        
        <div class="login-features">
            <h3><i class="fas fa-star"></i> Возможности BookStore</h3>
            <div class="features-grid">
                <div class="feature-item">
                    <i class="fas fa-book-reader"></i>
                    <h4>Большой каталог</h4>
                    <p>Тысячи книг разных жанров</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-clock"></i>
                    <h4>Гибкая аренда</h4>
                    <p>От 2 недель до 3 месяцев</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-shopping-cart"></i>
                    <h4>Покупка книг</h4>
                    <p>Станьте владельцем любимых книг</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-heart"></i>
                    <h4>Избранное</h4>
                    <p>Сохраняйте интересные книги</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('passwordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                passwordIcon.className = 'fas fa-eye';
            }
        }
        
        function fillDemo(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
            
            // Добавляем визуальный эффект
            const form = document.querySelector('.login-form');
            form.style.transform = 'scale(1.02)';
            setTimeout(() => {
                form.style.transform = 'scale(1)';
            }, 200);
        }
        
        // Анимация появления
        document.addEventListener('DOMContentLoaded', function() {
            const loginCard = document.querySelector('.login-card');
            const features = document.querySelector('.login-features');
            
            loginCard.style.opacity = '0';
            loginCard.style.transform = 'translateY(20px)';
            features.style.opacity = '0';
            features.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                loginCard.style.transition = 'all 0.6s ease';
                loginCard.style.opacity = '1';
                loginCard.style.transform = 'translateY(0)';
            }, 100);
            
            setTimeout(() => {
                features.style.transition = 'all 0.6s ease';
                features.style.opacity = '1';
                features.style.transform = 'translateY(0)';
            }, 300);
        });
    </script>
</body>
</html>