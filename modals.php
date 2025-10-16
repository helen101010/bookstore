<!-- Модальное окно для деталей книги -->
<div id="bookModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3><i class="fas fa-book"></i> Подробная информация</h3>
            <span class="close" onclick="closeModal('bookModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div id="bookDetails" class="book-details-content">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Загрузка...
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для аренды -->
<div id="rentalModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-clock"></i> Выберите срок аренды</h3>
            <span class="close" onclick="closeModal('rentalModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div id="rentalOptions" class="rental-options">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Загрузка...
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для добавления/редактирования книги (админ) -->
<div id="bookFormModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3 id="bookFormTitle"><i class="fas fa-plus"></i> Добавить книгу</h3>
            <span class="close" onclick="closeModal('bookFormModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="bookForm" class="book-form">
                <input type="hidden" id="bookId" name="id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-book"></i> Название:</label>
                        <input type="text" id="bookTitle" name="title" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-user-edit"></i> Автор:</label>
                        <input type="text" id="bookAuthor" name="author" required class="form-input">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-tags"></i> Категория:</label>
                        <select id="bookCategory" name="category_id" required class="form-select">
                            <!-- Опции будут загружены динамически -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Год:</label>
                        <input type="number" id="bookYear" name="year" required class="form-input">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-ruble-sign"></i> Цена покупки:</label>
                        <input type="number" id="bookPrice" name="price" step="0.01" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-image"></i> URL изображения:</label>
                        <input type="url" id="bookImageUrl" name="image_url" class="form-input">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Аренда (2 недели):</label>
                        <input type="number" id="bookRental2weeks" name="rental_price_2weeks" step="0.01" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Аренда (месяц):</label>
                        <input type="number" id="bookRentalMonth" name="rental_price_month" step="0.01" required class="form-input">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Аренда (3 месяца):</label>
                        <input type="number" id="bookRental3months" name="rental_price_3months" step="0.01" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-info-circle"></i> Статус:</label>
                        <select id="bookStatus" name="status" class="form-select">
                            <option value="available">Доступна</option>
                            <option value="unavailable">Недоступна</option>
                            <option value="rented">Арендована</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-barcode"></i> ISBN:</label>
                        <input type="text" id="bookIsbn" name="isbn" class="form-input">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-file-alt"></i> Страниц:</label>
                        <input type="number" id="bookPages" name="pages" class="form-input">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-language"></i> Язык:</label>
                        <input type="text" id="bookLanguage" name="language" class="form-input" value="Русский">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-building"></i> Издательство:</label>
                        <input type="text" id="bookPublisher" name="publisher" class="form-input">
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Описание:</label>
                    <textarea id="bookDescription" name="description" class="form-textarea" rows="4"></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Сохранить
                    </button>
                    <button type="button" onclick="closeModal('bookFormModal')" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Отмена
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно подтверждения -->
<div id="confirmModal" class="modal">
    <div class="modal-content modal-small">
        <div class="modal-header">
            <h3 id="confirmTitle"><i class="fas fa-question-circle"></i> Подтверждение</h3>
        </div>
        <div class="modal-body">
            <p id="confirmMessage">Вы уверены?</p>
            <div class="modal-actions">
                <button id="confirmYes" class="btn btn-danger">
                    <i class="fas fa-check"></i> Да
                </button>
                <button onclick="closeModal('confirmModal')" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Отмена
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Уведомления -->
<div id="notifications" class="notifications"></div>