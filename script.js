// ГЛОБАЛЬНЫЕ ФУНКЦИИ - ОПРЕДЕЛЯЕМ СРАЗУ В ГЛОБАЛЬНОЙ ОБЛАСТИ!
function showAdminTab(tabName) {
    console.log('showAdminTab called with:', tabName);
    
    // Скрыть все вкладки
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => tab.classList.remove('active'));
    
    // Убрать активный класс у всех кнопок
    const buttons = document.querySelectorAll('.admin-tabs .tab-button');
    buttons.forEach(button => button.classList.remove('active'));
    
    // Показать выбранную вкладку
    const targetTab = document.getElementById(tabName);
    if (targetTab) {
        targetTab.classList.add('active');
    }
    
    // Активировать кнопку
    const clickedButton = event && event.target ? event.target.closest('.tab-button') : null;
    if (clickedButton) {
        clickedButton.classList.add('active');
    }
}

function showTab(tabName) {
    console.log('showTab called with:', tabName);
    
    // Скрыть все вкладки
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => tab.classList.remove('active'));
    
    // Убрать активный класс у всех кнопок
    const buttons = document.querySelectorAll('.profile-tabs .tab-button');
    buttons.forEach(button => button.classList.remove('active'));
    
    // Показать выбранную вкладку
    const targetTab = document.getElementById(tabName);
    if (targetTab) {
        targetTab.classList.add('active');
    }
    
    // Активировать кнопку
    const clickedButton = event && event.target ? event.target.closest('.tab-button') : null;
    if (clickedButton) {
        clickedButton.classList.add('active');
    }
}

function toggleFilters() {
    const filtersContent = document.getElementById('filtersContent');
    if (filtersContent) {
        filtersContent.classList.toggle('active');
    }
}

// Глобальные переменные
let currentBookId = null;
let currentModal = null;

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    // Инициализация обработчиков событий
    initializeEventListeners();
    
    // Анимация появления карточек книг
    animateBookCards();
    
    // Автоматическое обновление статуса аренд
    setInterval(updateRentalStatus, 300000); // каждые 5 минут
    
    // Инициализация уведомлений
    initializeNotifications();
}

function initializeEventListeners() {
    // Закрытие модальных окон по клику вне их
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            closeModal(event.target.id);
        }
    });
    
    // Закрытие модальных окон по Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && currentModal) {
            closeModal(currentModal);
        }
    });
    
    // Обработка формы книги (для админки)
    const bookForm = document.getElementById('bookForm');
    if (bookForm) {
        bookForm.addEventListener('submit', handleBookFormSubmit);
    }
    
    // Обработка поиска в реальном времени
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                // Можно добавить живой поиск
            }, 500);
        });
    }
}

// Функции для модальных окон
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        currentModal = modalId;
        document.body.style.overflow = 'hidden';
        
        // Анимация появления
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        currentModal = null;
        document.body.style.overflow = 'auto';
        modal.classList.remove('show');
    }
}

// Показать детали книги
function showBookDetails(bookId) {
    showModal('bookModal');
    
    // Показываем загрузку
    document.getElementById('bookDetails').innerHTML = `
        <div class="loading">
            <i class="fas fa-spinner fa-spin"></i> Загрузка...
        </div>
    `;
    
    fetch('book_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_book_details&id=' + bookId
    })
    .then(response => response.json())
    .then(book => {
        if (book) {
            // Увеличиваем счетчик просмотров
            updateBookViews(bookId);
            
            const detailsHtml = `
                <div class="book-details-content">
                    <div class="book-details-image">
                        <img src="${book.image_url}" alt="${book.title}">
                    </div>
                    <div class="book-details-info">
                        <h2>${book.title}</h2>
                        <p><strong><i class="fas fa-user-edit"></i> Автор:</strong> <span>${book.author}</span></p>
                        <p><strong><i class="fas fa-tags"></i> Категория:</strong> <span>${book.category_name || 'Не указана'}</span></p>
                        <p><strong><i class="fas fa-calendar"></i> Год издания:</strong> <span>${book.year}</span></p>
                        ${book.isbn ? `<p><strong><i class="fas fa-barcode"></i> ISBN:</strong> <span>${book.isbn}</span></p>` : ''}
                        ${book.pages ? `<p><strong><i class="fas fa-file-alt"></i> Страниц:</strong> <span>${book.pages}</span></p>` : ''}
                        ${book.language ? `<p><strong><i class="fas fa-language"></i> Язык:</strong> <span>${book.language}</span></p>` : ''}
                        ${book.publisher ? `<p><strong><i class="fas fa-building"></i> Издательство:</strong> <span>${book.publisher}</span></p>` : ''}
                        
                        <div class="price-section">
                            <p><strong><i class="fas fa-ruble-sign"></i> Цена покупки:</strong> <span>${formatPrice(book.price)}</span></p>
                            <p><strong><i class="fas fa-clock"></i> Аренда:</strong></p>
                            <ul>
                                <li>2 недели: ${formatPrice(book.rental_price_2weeks)}</li>
                                <li>1 месяц: ${formatPrice(book.rental_price_month)}</li>
                                <li>3 месяца: ${formatPrice(book.rental_price_3months)}</li>
                            </ul>
                        </div>
                        
                        ${book.description ? `<p><strong><i class="fas fa-align-left"></i> Описание:</strong></p><p>${book.description}</p>` : ''}
                        
                        <div class="book-status-section">
                            <p><strong><i class="fas fa-info-circle"></i> Статус:</strong> <span>
                                <span class="status-badge status-${book.status}">
                                    ${getStatusText(book.status)}
                                </span>
                            </span></p>
                        </div>
                        
                        ${book.avg_rating ? `
                            <div class="rating-section">
                                <p><strong><i class="fas fa-star"></i> Рейтинг:</strong></p>
                                <div class="book-rating">
                                    <div class="stars">
                                        ${generateStars(book.avg_rating)}
                                    </div>
                                    <span class="rating-text">${parseFloat(book.avg_rating).toFixed(1)} (${book.review_count || 0} отзывов)</span>
                                </div>
                            </div>
                        ` : ''}
                        
                        <div class="book-actions-modal">
                            ${book.status === 'available' ? `
                                <button onclick="showRentalModal(${book.id})" class="btn btn-rental">
                                    <i class="fas fa-clock"></i> Арендовать
                                </button>
                                <button onclick="purchaseBook(${book.id})" class="btn btn-purchase">
                                    <i class="fas fa-shopping-cart"></i> Купить
                                </button>
                            ` : ''}
                            <button onclick="toggleFavorite(${book.id})" class="btn btn-secondary">
                                <i class="fas fa-heart"></i> В избранное
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('bookDetails').innerHTML = detailsHtml;
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        document.getElementById('bookDetails').innerHTML = `
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                Ошибка при загрузке данных
            </div>
        `;
    });
}

// Показать модальное окно аренды
function showRentalModal(bookId) {
    currentBookId = bookId;
    showModal('rentalModal');
    
    // Показываем загрузку
    document.getElementById('rentalOptions').innerHTML = `
        <div class="loading">
            <i class="fas fa-spinner fa-spin"></i> Загрузка...
        </div>
    `;
    
    fetch('book_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_book_details&id=' + bookId
    })
    .then(response => response.json())
    .then(book => {
        if (book) {
            const optionsHtml = `
                <div class="rental-options-grid">
                    <div class="rental-option" onclick="rentBook('2weeks')">
                        <div class="rental-option-header">
                            <h4><i class="fas fa-clock"></i> 2 недели</h4>
                            <div class="rental-price">${formatPrice(book.rental_price_2weeks)}</div>
                        </div>
                        <p>Срок аренды: 14 дней</p>
                        <div class="rental-features">
                            <span><i class="fas fa-check"></i> Быстрое чтение</span>
                            <span><i class="fas fa-check"></i> Экономично</span>
                        </div>
                    </div>
                    
                    <div class="rental-option recommended" onclick="rentBook('month')">
                        <div class="rental-badge">Популярно</div>
                        <div class="rental-option-header">
                            <h4><i class="fas fa-calendar-alt"></i> 1 месяц</h4>
                            <div class="rental-price">${formatPrice(book.rental_price_month)}</div>
                        </div>
                        <p>Срок аренды: 30 дней</p>
                        <div class="rental-features">
                            <span><i class="fas fa-check"></i> Оптимальный срок</span>
                            <span><i class="fas fa-check"></i> Лучшее соотношение</span>
                        </div>
                    </div>
                    
                    <div class="rental-option" onclick="rentBook('3months')">
                        <div class="rental-option-header">
                            <h4><i class="fas fa-calendar"></i> 3 месяца</h4>
                            <div class="rental-price">${formatPrice(book.rental_price_3months)}</div>
                        </div>
                        <p>Срок аренды: 90 дней</p>
                        <div class="rental-features">
                            <span><i class="fas fa-check"></i> Максимальный срок</span>
                            <span><i class="fas fa-check"></i> Для неспешного чтения</span>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('rentalOptions').innerHTML = optionsHtml;
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        showNotification('Ошибка при загрузке данных', 'error');
    });
}

// Арендовать книгу
function rentBook(rentalType) {
    if (!currentBookId) return;
    
    const button = event.target.closest('.rental-option');
    button.style.opacity = '0.6';
    button.style.pointerEvents = 'none';
    
    fetch('book_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=rent_book&book_id=${currentBookId}&rental_type=${rentalType}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Книга успешно арендована!', 'success');
            closeModal('rentalModal');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification('Ошибка: ' + data.message, 'error');
            button.style.opacity = '1';
            button.style.pointerEvents = 'auto';
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        showNotification('Произошла ошибка при аренде', 'error');
        button.style.opacity = '1';
        button.style.pointerEvents = 'auto';
    });
}

// Купить книгу
function purchaseBook(bookId) {
    showConfirmModal(
        'Подтверждение покупки',
        'Вы уверены, что хотите купить эту книгу?',
        () => {
            fetch('book_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=purchase_book&book_id=${bookId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Книга успешно куплена!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('Ошибка: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                showNotification('Произошла ошибка при покупке', 'error');
            });
        }
    );
}

// Добавить/удалить из избранного
function toggleFavorite(bookId) {
    fetch('book_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=toggle_favorite&book_id=${bookId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            // Обновляем иконку избранного
        } else {
            showNotification('Ошибка: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        showNotification('Произошла ошибка', 'error');
    });
}

// Админские функции
function showAddBookModal() {
    document.getElementById('bookFormTitle').innerHTML = '<i class="fas fa-plus"></i> Добавить книгу';
    document.getElementById('bookForm').reset();
    document.getElementById('bookId').value = '';
    loadCategories();
    showModal('bookFormModal');
}

function editBook(bookId) {
    fetch('admin_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_book&id=' + bookId
    })
    .then(response => response.json())
    .then(book => {
        if (book) {
            document.getElementById('bookFormTitle').innerHTML = '<i class="fas fa-edit"></i> Редактировать книгу';
            
            // Заполняем форму
            document.getElementById('bookId').value = book.id;
            document.getElementById('bookTitle').value = book.title;
            document.getElementById('bookAuthor').value = book.author;
            document.getElementById('bookYear').value = book.year;
            document.getElementById('bookPrice').value = book.price;
            document.getElementById('bookImageUrl').value = book.image_url || '';
            document.getElementById('bookRental2weeks').value = book.rental_price_2weeks;
            document.getElementById('bookRentalMonth').value = book.rental_price_month;
            document.getElementById('bookRental3months').value = book.rental_price_3months;
            document.getElementById('bookStatus').value = book.status;
            document.getElementById('bookIsbn').value = book.isbn || '';
            document.getElementById('bookPages').value = book.pages || '';
            document.getElementById('bookLanguage').value = book.language || '';
            document.getElementById('bookPublisher').value = book.publisher || '';
            document.getElementById('bookDescription').value = book.description || '';
            
            loadCategories(book.category_id);
            showModal('bookFormModal');
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        showNotification('Ошибка при загрузке данных книги', 'error');
    });
}

function deleteBook(bookId) {
    showConfirmModal(
        'Удаление книги',
        'Вы уверены, что хотите удалить эту книгу? Это действие нельзя отменить.',
        () => {
            fetch('admin_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=delete_book&id=' + bookId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Книга успешно удалена!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('Ошибка при удалении книги', 'error');
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                showNotification('Произошла ошибка', 'error');
            });
        }
    );
}

function completeRental(rentalId) {
    showConfirmModal(
        'Завершение аренды',
        'Завершить аренду?',
        () => {
            fetch('admin_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=complete_rental&id=' + rentalId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Аренда завершена!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('Ошибка при завершении аренды', 'error');
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                showNotification('Произошла ошибка', 'error');
            });
        }
    );
}

function sendReminder(rentalId) {
    fetch('admin_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=send_reminder&id=' + rentalId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
        } else {
            showNotification('Ошибка: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        showNotification('Произошла ошибка', 'error');
    });
}

function sendReminders() {
    showConfirmModal(
        'Отправка напоминаний',
        'Отправить напоминания всем пользователям с просроченными арендами?',
        () => {
            fetch('admin_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=send_all_reminders'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                } else {
                    showNotification('Ошибка: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                showNotification('Произошла ошибка', 'error');
            });
        }
    );
}

// Обработка формы книги
function handleBookFormSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const bookId = formData.get('id');
    const action = bookId ? 'edit_book' : 'add_book';
    formData.append('action', action);
    
    const submitButton = e.target.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сохранение...';
    submitButton.disabled = true;
    
    fetch('admin_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Книга успешно сохранена!', 'success');
            closeModal('bookFormModal');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification('Ошибка при сохранении книги', 'error');
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        showNotification('Произошла ошибка', 'error');
    })
    .finally(() => {
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    });
}

// Загрузка категорий для формы
function loadCategories(selectedId = null) {
    fetch('admin_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_categories'
    })
    .then(response => response.json())
    .then(categories => {
        const select = document.getElementById('bookCategory');
        if (select && categories) {
            select.innerHTML = '';
            categories.forEach(category => {
                const option = document.createElement('option');
                option.value = category.id;
                option.textContent = `${category.icon} ${category.name}`;
                if (selectedId && category.id == selectedId) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
        }
    })
    .catch(error => {
        console.error('Ошибка при загрузке категорий:', error);
    });
}

// Модальное окно подтверждения
function showConfirmModal(title, message, onConfirm) {
    document.getElementById('confirmTitle').innerHTML = `<i class="fas fa-question-circle"></i> ${title}`;
    document.getElementById('confirmMessage').textContent = message;
    
    const confirmButton = document.getElementById('confirmYes');
    confirmButton.onclick = () => {
        closeModal('confirmModal');
        onConfirm();
    };
    
    showModal('confirmModal');
}

// Система уведомлений
function initializeNotifications() {
    // Создаем контейнер для уведомлений если его нет
    if (!document.getElementById('notifications')) {
        const container = document.createElement('div');
        container.id = 'notifications';
        container.className = 'notifications';
        document.body.appendChild(container);
    }
}

function showNotification(message, type = 'info', duration = 5000) {
    const container = document.getElementById('notifications');
    if (!container) return;
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    
    const icon = getNotificationIcon(type);
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${icon} ${type}"></i>
            <span>${message}</span>
        </div>
    `;
    
    container.appendChild(notification);
    
    // Автоматическое удаление
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }
    }, duration);
    
    // Удаление по клику
    notification.addEventListener('click', () => {
        notification.remove();
    });
}

function getNotificationIcon(type) {
    switch (type) {
        case 'success': return 'fa-check-circle';
        case 'error': return 'fa-exclamation-circle';
        case 'warning': return 'fa-exclamation-triangle';
        default: return 'fa-info-circle';
    }
}

// Вспомогательные функции
function formatPrice(price) {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB'
    }).format(price);
}

function getStatusText(status) {
    switch (status) {
        case 'available': return 'Доступна';
        case 'unavailable': return 'Недоступна';
        case 'rented': return 'Арендована';
        default: return 'Неизвестно';
    }
}

function generateStars(rating) {
    let stars = '';
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 >= 0.5;
    
    for (let i = 1; i <= 5; i++) {
        if (i <= fullStars) {
            stars += '<i class="fas fa-star"></i>';
        } else if (i === fullStars + 1 && hasHalfStar) {
            stars += '<i class="fas fa-star-half-alt"></i>';
        } else {
            stars += '<i class="far fa-star"></i>';
        }
    }
    
    return stars;
}

function updateBookViews(bookId) {
    fetch('book_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_views&book_id=${bookId}`
    }).catch(error => {
        console.error('Ошибка при обновлении просмотров:', error);
    });
}

function updateRentalStatus() {
    fetch('update_rentals.php', {
        method: 'POST'
    }).catch(error => {
        console.error('Ошибка при обновлении статуса аренд:', error);
    });
}

function animateBookCards() {
    const bookCards = document.querySelectorAll('.book-card');
    bookCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

// Функции для работы с избранным (расширенные)
function loadFavorites() {
    fetch('book_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_favorites'
    })
    .then(response => response.json())
    .then(favorites => {
        // Обновляем иконки избранного на странице
        favorites.forEach(bookId => {
            const favoriteButtons = document.querySelectorAll(`[onclick="toggleFavorite(${bookId})"] i`);
            favoriteButtons.forEach(icon => {
                icon.className = 'fas fa-heart';
                icon.style.color = '#e91e63';
            });
        });
    })
    .catch(error => {
        console.error('Ошибка при загрузке избранного:', error);
    });
}

// Функция для плавной прокрутки
function smoothScrollTo(element) {
    element.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
    });
}

// Обработка ошибок глобально
window.addEventListener('error', function(e) {
    console.error('Глобальная ошибка:', e.error);
    showNotification('Произошла неожиданная ошибка', 'error');
});

// Обработка ошибок fetch глобально
window.addEventListener('unhandledrejection', function(e) {
    console.error('Необработанная ошибка Promise:', e.reason);
    showNotification('Ошибка сети или сервера', 'error');
});

// Инициализация избранного при загрузке страницы
if (document.querySelector('.book-card')) {
    document.addEventListener('DOMContentLoaded', function() {
        loadFavorites();
    });
}

// ПРОВЕРЯЕМ ЧТО ФУНКЦИЯ ОПРЕДЕЛЕНА
console.log('Script loaded successfully! showAdminTab is available:', typeof showAdminTab);

// Экспортируем функции в глобальную область для доступа из HTML
window.showAdminTab = showAdminTab;
window.showTab = showTab;
window.toggleFilters = toggleFilters;