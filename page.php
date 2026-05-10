<?php
require_once 'config.php';
require_once 'modules/CarModels.php';
require_once 'modules/UserManager.php';
require_once 'modules/OrderManager.php';

$carModel = new CarModels();
$userMan = new UserManager();
$orderMan = new OrderManager();

// Определяем авторизацию
$isLoggedIn = isset($_SESSION['auto_user_id']);
$userData = $isLoggedIn ? $userMan->getUserById($_SESSION['auto_user_id']) : null;
$lastOrder = $isLoggedIn ? $orderMan->getLastOrderByUser($_SESSION['auto_user_id']) : null;

// Получаем данные для выпадающих списков

$models = $carModel->getAllModels();
$packages = $carModel->getAllPackages();
$services = $carModel->getAllServices();

// Передаём данные в форму
$formData = [
    'full_name' => $userData ? $userData['full_name'] : '',
    'email'     => $userData ? $userData['email'] : '',
    'phone'     => $userData ? $userData['phone'] : '',
    'consent'   => $userData ? (bool)$userData['consent'] : false,
    'model_id'  => $lastOrder ? $lastOrder['model_id'] : ($models ? $models[0]['id'] : 1),
    'package_id'=> $lastOrder ? $lastOrder['package_id'] : null,
    'services'  => $lastOrder ? $lastOrder['service_ids'] : [],
    'total_price' => $lastOrder ? (float)$lastOrder['total_price'] : 0
];

// Вычисляем базовую цену выбранной модели для JS (можно передать через data-атрибуты)
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <link rel="icon" href="convertio.in_Mercedes-Logo.svg.ico" type="image/ico">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mercedes-Benz - Элегантность и инновации</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Open+Sans:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">  
    <link rel="stylesheet" href="blog.css">  
    <link rel="stylesheet" href="eqs.css">  
    <link rel="stylesheet" href="jeneva.css">  
    <link rel="stylesheet" href="amgsl63.css">  
    <link rel="stylesheet" href="avatar.css">  
    <link rel="stylesheet" href="ecalss2025.css">  
    <link rel="stylesheet" href="modal.css">  
    <style>
        /* Дополнительные стили для формы заказа */
        .order-form-section {
            background: var(--bg-light);
            border-radius: 8px;
            padding: 2rem;
            margin-top: 2rem;
            border: 1px solid var(--border-color);
        }
        .order-form-section .form-row {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .order-form-section .form-group {
            flex: 1;
            min-width: 200px;
        }
        .services-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        .service-check {
            background: var(--bg-lighter);
            padding: 0.5rem;
            border-radius: 4px;
        }
        .calculator-result {
            background: var(--bg-darker);
            padding: 1rem;
            text-align: center;
            border-radius: 8px;
            margin-top: 1rem;
        }
        .total-price {
            font-size: 2rem;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <!-- Header (копия из index.html) -->
    <header>
        <div class="video-container">
            <video autoplay muted loop>
                <source src="vid.mp4" type="video/mp4">
                Ваш браузер не поддерживает видео.
            </video>
            <div class="overlay"></div>
        </div>
        
        <nav>
            <a href="#" class="logo">Mercedes<span>Elite</span></a>
            <ul class="nav-links">
                <li><a href="#">Главная</a></li>
                <li>
                    <a href="#models">Модели</a>
                    <ul class="dropdown">
                        <li><a href="#a-class">A-Class</a></li>
                        <li><a href="#c-class">C-Class</a></li>
                        <li><a href="#e-class">E-Class</a></li>
                        <li><a href="#s-class">S-Class</a></li>
                    </ul>
                </li>
                <li><a href="#calculator">Калькулятор</a></li>
                <li><a href="#gallery">Галерея</a></li>
                <li><a href="#contact">Заказать</a></li>
                <li><a href="#" id="order-modal-btn" class="btn">Заказать авто</a></li>
            </ul>
            <div class="burger">
                <div></div>
                <div></div>
                <div></div>
            </div>
        </nav>
        
        <div class="hero">
            <h1>Mercedes-Benz</h1>
            <p>Исключительная элегантность и передовые технологии в каждом автомобиле</p>
            <a href="#models" class="btn">Исследовать модели</a>
        </div>
    </header>

    <!-- Секция моделей (без изменений) -->
    <section id="models">
        <div class="section-title">
            <h2>Наши модели</h2>
            <p>Выберите идеальный Mercedes-Benz из нашего премиального каталога</p>
        </div>
        <div class="models-grid">
            <!-- динамические карточки моделей – оставим статическими, чтобы не усложнять -->
            <div class="model-card" id="a-class">
                <div class="model-img">
                    <img src="black-a-class.jfif" alt="Mercedes A-Class" id="model-1-img">
                </div>
                <div class="model-info">
                    <h3>Mercedes A-Class</h3>
                    <p>Компактный премиальный автомобиль с инновационными технологиями и динамичным дизайном.</p>
                    <div class="model-price">от 3 200 000 ₽</div>
                    <div class="color-picker">
                        <h4>Выберите цвет:</h4>
                        <div class="color-options">
                            <div class="color-option active" style="background-color: #000000;" data-color="#000000" data-model="1" data-color-name="Черный"></div>
                            <div class="color-option" style="background-color: #C0C0C0;" data-color="#C0C0C0" data-model="1" data-color-name="Серебристый"></div>
                            <div class="color-option" style="background-color: #003DA5;" data-color="#003DA5" data-model="1" data-color-name="Синий"></div>
                            <div class="color-option" style="background-color: #FFFFFF; border: 1px solid #eee;" data-color="#FFFFFF" data-model="1" data-color-name="Белый"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="model-card" id="c-class">
                <div class="model-img">
                    <img src="black-c-class.jfif" alt="Mercedes C-Class" id="model-2-img">
                </div>
                <div class="model-info">
                    <h3>Mercedes C-Class</h3>
                    <p>Бизнес-седаны с элегантным дизайном и передовыми технологиями комфорта.</p>
                    <div class="model-price">от 4 500 000 ₽</div>
                    
                    <div class="color-picker">
                        <h4>Выберите цвет:</h4>
                        <div class="color-options">
                            <div class="color-option active" style="background-color: #000000;" data-color="#000000" data-model="2" data-color-name="Черный"></div>
                            <div class="color-option" style="background-color: #C0C0C0;" data-color="#C0C0C0" data-model="2" data-color-name="Серебристый"></div>
                            <div class="color-option" style="background-color: #003DA5;" data-color="#003DA5" data-model="2" data-color-name="Синий"></div>
                            <div class="color-option" style="background-color: #FFFFFF; border: 1px solid #eee;" data-color="#FFFFFF" data-model="2" data-color-name="Белый"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="model-card" id="e-class">
                <div class="model-img">
                    <img src="black-e-class.jfif" alt="Mercedes E-Class" id="model-3-img">
                </div>
                <div class="model-info">
                    <h3>Mercedes E-Class</h3>
                    <p>Идеальное сочетание роскоши, комфорта и инновационных технологий.</p>
                    <div class="model-price">от 5 800 000 ₽</div>
                    
                    <div class="color-picker">
                        <h4>Выберите цвет:</h4>
                        <div class="color-options">
                            <div class="color-option active" style="background-color: #000000;" data-color="#000000" data-model="3" data-color-name="Черный"></div>
                            <div class="color-option" style="background-color: #C0C0C0;" data-color="#C0C0C0" data-model="3" data-color-name="Серебристый"></div>
                            <div class="color-option" style="background-color: #003DA5;" data-color="#003DA5" data-model="3" data-color-name="Синий"></div>
                            <div class="color-option" style="background-color: #FFFFFF; border: 1px solid #eee;" data-color="#FFFFFF" data-model="3" data-color-name="Белый"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Таблица AMG (без изменений) -->
    <section class="performance-models">
 <div class="section-title">
            <h2>Самые мощные Mercedes-AMG</h2>
            <p>Рейтинг самых производительных моделей Mercedes-AMG по динамическим характеристикам</p>
        </div>
        
        <table class="performance-table">
            <thead>
                <tr>
                    <th>Модель</th>
                    <th>Разгон 0-100 км/ч</th>
                    <th>Макс. скорость</th>
                    <th>Двигатель</th>
                    <th>Мощность</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Mercedes-AMG GT Black Series</td>
                    <td>3.2 с</td>
                    <td>325 км/ч</td>
                    <td>4.0-литровый V8 с twin-turbo</td>
                    <td>730 л.с.</td>
                </tr>
                <tr>
                    <td>Mercedes-AMG GT 63 S E Performance</td>
                    <td>2.9 с</td>
                    <td>316 км/ч</td>
                    <td>4.0-литровый V8 с гибридной системой</td>
                    <td>843 л.с.</td>
                </tr>
                <tr>
                    <td>Mercedes-AMG SL 63</td>
                    <td>3.6 с</td>
                    <td>315 км/ч</td>
                    <td>4.0-литровый V8 с twin-turbo</td>
                    <td>585 л.с.</td>
                </tr>
                <tr>
                    <td>Mercedes-AMG E 63 S 4MATIC+</td>
                    <td>3.4 с</td>
                    <td>300 км/ч</td>
                    <td>4.0-литровый V8 с twin-turbo</td>
                    <td>612 л.с.</td>
                </tr>
                <tr>
                    <td>Mercedes-AMG C 63 S E Performance</td>
                    <td>3.4 с</td>
                    <td>280 км/ч</td>
                    <td>2.0-литровый с гибридной системой</td>
                    <td>671 л.с.</td>
                </tr>
                <tr>
                    <td>Mercedes-AMG G 63</td>
                    <td>4.5 с</td>
                    <td>220 км/ч</td>
                    <td>4.0-литровый V8 с twin-turbo</td>
                    <td>585 л.с.</td>
                </tr>
            </tbody>
        </table>
    </section>

    <!-- Галерея (без изменений) -->
    <section id="gallery">
<div class="section-title">
            <h2>Галерея Mercedes-Benz</h2>
            <p>Эксклюзивные модели Mercedes-Benz в премиальных комплектациях</p>
        </div>
        
        <div class="gallery-container">
            <div class="gallery-slider">
                <div class="gallery-slide">
                    <img src="amg-gt.jpg">
                    <div class="slide-content">
                        <h3>Mercedes-AMG GT</h3>
                        <p>Спортивное купе с двигателем V8 и инновационной технологией AMG Performance 4MATIC+</p>
                    </div>
                </div>
                <div class="gallery-slide">
                    <img src="s-class.jpg">
                    <div class="slide-content">
                        <h3>Mercedes-Benz S-Class</h3>
                        <p>Флагманский седан с технологиями автономного вождения и роскошным интерьером</p>
                    </div>
                </div>
                <div class="gallery-slide">
                    <img src="g-class.jpg">
                    <div class="slide-content">
                        <h3>Mercedes-Benz G-Class</h3>
                        <p>Легендарный внедорожник с современными технологиями и неизменным дизайном</p>
                    </div>
                </div>
            </div>
            
            <div class="gallery-controls">
                <button class="gallery-btn prev-btn">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="gallery-btn next-btn">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            
            <div class="gallery-dots">
                <span class="gallery-dot active" data-slide="0"></span>
                <span class="gallery-dot" data-slide="1"></span>
                <span class="gallery-dot" data-slide="2"></span>
            </div>
        </div>

    </section>

    <!-- Блог (без изменений) -->
    <section id="blog">
<div class="section-title">
        <h2>Блог & Новости</h2>
        <p>Актуальные новости, обзоры и события Mercedes-Benz</p>
    </div>
    
    <div class="blog-controls">
        <div class="blog-categories">
            <button class="category-btn active" data-category="all">Все новости</button>
            <button class="category-btn" data-category="news">Новости</button>
            <button class="category-btn" data-category="reviews">Обзоры</button>
            <button class="category-btn" data-category="events">События</button>
            <button class="category-btn" data-category="technology">Технологии</button>
        </div>
        
        <div class="blog-search">
            <input type="text" id="blog-search" placeholder="Поиск по статьям...">
            <button class="search-btn">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </div>
    
    <div class="blog-grid" id="blog-grid">
        
    </div>
    
    <div class="blog-load-more">
        <button class="btn" id="load-more-btn">
            <span>Загрузить больше</span>
            <div class="loading-spinner" style="display: none;"></div>
        </button>
    </div>
    
    
    </section>

   <section id="order-form-section" class="order-form-section">
        <div class="section-title">
            <h2>Оформить заказ</h2>
            <p>Заполните форму, и наш менеджер свяжется с вами</p>
        </div>
        
        <form id="order-form" action="/project/index.php" method="post">
            <div class="form-group">
                <label>ФИО</label>
                <input type="text" name="full_name" id="full_name" value="<?= htmlspecialchars($formData['full_name']) ?>" required>
                <div class="field-error" id="error-full_name"></div>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="email" value="<?= htmlspecialchars($formData['email']) ?>" required>
                <div class="field-error" id="error-email"></div>
            </div>
            <div class="form-group">
                <label>Телефон</label>
                <input type="tel" name="phone" id="phone" value="<?= htmlspecialchars($formData['phone']) ?>" placeholder="+7XXXXXXXXXX" required>
                <div class="field-error" id="error-phone"></div>
            </div>
            <div class="form-group checkbox">
                <label>
                    <input type="checkbox" name="consent" id="consent" value="1" <?= $formData['consent'] ? 'checked' : '' ?> required>
                    Согласие на обработку персональных данных
                </label>
                <div class="field-error" id="error-consent"></div>
            </div>

            <div class="form-group">
                <label>Модель</label>
                <select name="model_id" id="model_id">
                    <?php foreach ($models as $m): ?>
                        <option value="<?= $m['id'] ?>" data-price="<?= $m['base_price'] ?>" <?= ($formData['model_id'] == $m['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['name']) ?> – <?= number_format($m['base_price'], 0, '', ' ') ?> ₽
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Пакет опций</label>
                <select name="package_id" id="package_id">
                    <option value="">Без пакета</option>
                    <?php foreach ($packages as $p): ?>
                        <option value="<?= $p['id'] ?>" data-price="<?= $p['price_add'] ?>" <?= ($formData['package_id'] == $p['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['name']) ?> (+<?= number_format($p['price_add'], 0, '', ' ') ?> ₽)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Дополнительные услуги</label>
                <div class="services-group">
                    <?php foreach ($services as $s): ?>
                        <label>
                            <input type="checkbox" name="services[]" value="<?= $s['id'] ?>" data-price="<?= $s['price_add'] ?>" <?= in_array($s['id'], $formData['services']) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($s['name']) ?> (+<?= number_format($s['price_add'], 0, '', ' ') ?> ₽)
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="calculator-result">
                <h3>Итоговая стоимость</h3>
                <div class="total-price" id="total-price">0 ₽</div>
            </div>

            <button type="submit" class="btn"><?= $isLoggedIn ? 'Обновить заказ' : 'Оформить заказ' ?></button>
            <div id="form-message" class="form-message" style="display:none;"></div>
        </form>

        <?php if ($isLoggedIn): ?>
            <div style="text-align:center; margin-top:1rem;">
                Вы авторизованы как <?= htmlspecialchars($_SESSION['auto_user_login']) ?>
                <a href="logout.php">Выйти</a>
            </div>
        <?php endif; ?>
    </section>

            <div class="calculator-result">
                <h3>Итоговая стоимость</h3>
                <div class="total-price" id="total-price">0 ₽</div>
            </div>

            <button type="submit" class="btn"><?= $isLoggedIn ? 'Обновить заказ' : 'Оформить заказ' ?></button>
            <div id="form-message" class="form-message" style="display:none;"></div>
        </form>

        <?php if ($isLoggedIn): ?>
            <div class="logged-in-info">
                Вы авторизованы как <?= htmlspecialchars($_SESSION['auto_user_login']) ?>
                <a href="logout.php">Выйти</a>
            </div>
        <?php endif; ?>
    </section>

    <?php if (isset($_SESSION['flash'])): ?>
    <div class="container" style="margin-top:1rem;">
        <div class="success-message"><?= htmlspecialchars($_SESSION['flash']) ?></div>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>


    <!-- Footer (без изменений) -->
    <footer>
         <div class="footer-content">
            <div class="footer-logo">Mercedes<span>Elite</span></div>
            <ul class="footer-links">
                <li><a href="#">Главная</a></li>
                <li><a href="#models">Модели</a></li>
                <li><a href="#calculator">Калькулятор</a></li>
                
                <li><a href="#gallery">Галерея</a></li>
                <li><a href="#contact">Контакты</a></li>
            </ul>
            
            <div class="quote-section">
                <p class="inspiration-quote">
                    «Лучшее или ничего» — Gottlieb Daimler
                </p>
            </div>
            
            <div class="copyright">Проект - Артём Мотыжёв & Павел Месропян</div>
        </div>
    </footer>

    <!-- Модальное окно для формы заказа -->
    <div class="modal" id="order-modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Заказать автомобиль</h2>
            <?php include 'templates/order_form.php'; ?>
        </div>
    </div>

    <script src="script.js" defer></script>
    <script src="blog.js" defer></script>
    <script src="public/js/order.js" defer></script>
    <script>
        // Передаём данные для калькулятора в JavaScript
        window.carData = {
            models: <?= json_encode($models) ?>,
            packages: <?= json_encode($packages) ?>,
            services: <?= json_encode($services) ?>,
            lastOrder: <?= json_encode($lastOrder) ?>
        };
    </script>
</body>
</html>