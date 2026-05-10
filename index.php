<?php
session_start();
require_once 'config.php';
require_once 'modules/Database.php';
require_once 'modules/CarModels.php';
require_once 'modules/UserManager.php';
require_once 'modules/OrderManager.php';

function sendJson($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}


$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Обработка AJAX
if ($isAjax && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) sendJson(['error' => 'Invalid JSON'], 400);

    $method = isset($input['_method']) && $input['_method'] === 'PUT' ? 'PUT' : 'POST';

    $required = ['full_name', 'email', 'phone', 'consent', 'model_id', 'services', 'total_price'];
    foreach ($required as $field) {
        if (!isset($input[$field])) sendJson(['error' => "Missing field: $field"], 422);
    }

    $full_name = trim($input['full_name']);
    $email = trim($input['email']);
    $phone = trim($input['phone']);
    $consent = (bool)$input['consent'];
    $modelId = (int)$input['model_id'];
    $packageId = isset($input['package_id']) && $input['package_id'] !== '' ? (int)$input['package_id'] : null;
    $serviceIds = array_map('intval', $input['services']);
    $totalPrice = (float)$input['total_price'];

    $userMan = new UserManager();
    $orderMan = new OrderManager();
    $userId = $_SESSION['auto_user_id'] ?? null;

    if ($method === 'POST') {
        // СОЗДАНИЕ НОВОГО ЗАКАЗА
        if (!$userId) {
            $newUser = $userMan->createUser($full_name, $email, $phone, $consent);
            if (!$newUser['success']) {
                sendJson(['errors' => $newUser['errors']], 422);
            }
            $userId = $newUser['id'];
            $_SESSION['auto_user_id'] = $userId;
            $_SESSION['auto_user_login'] = $newUser['login'];
            $orderId = $orderMan->createOrder($userId, $modelId, $packageId, $serviceIds, $totalPrice);
            if ($orderId) {
                sendJson([
                    'message' => 'Order created',
                    'order_id' => $orderId,
                    'login' => $newUser['login'],
                    'password' => $newUser['password']
                ], 201);
            } else {
                sendJson(['error' => 'Failed to create order'], 500);
            }
        } else {
            // Обновляем данные пользователя
            $update = $userMan->updateUser($userId, $full_name, $email, $phone, $consent);
            if (!$update['success']) {
                sendJson(['errors' => $update['errors']], 422);
            }
            $orderId = $orderMan->createOrder($userId, $modelId, $packageId, $serviceIds, $totalPrice);
            if ($orderId) {
                sendJson(['message' => 'Order created', 'order_id' => $orderId], 201);
            } else {
                sendJson(['error' => 'Failed to create order'], 500);
            }
        }
    } else { // PUT (обновление)
        if (!$userId) sendJson(['error' => 'Unauthorized'], 401);
        // Обновляем данные пользователя
        $update = $userMan->updateUser($userId, $full_name, $email, $phone, $consent);
        if (!$update['success']) {
            sendJson(['errors' => $update['errors']], 422);
        }
        $lastOrder = $orderMan->getLastOrderByUser($userId);
        if (!$lastOrder) {
            $orderId = $orderMan->createOrder($userId, $modelId, $packageId, $serviceIds, $totalPrice);
            if ($orderId) sendJson(['message' => 'Order created (no existing)', 'order_id' => $orderId], 201);
            else sendJson(['error' => 'Failed to create order'], 500);
        } else {
            $updated = $orderMan->updateOrder($lastOrder['id'], $modelId, $packageId, $serviceIds, $totalPrice);
            if ($updated) sendJson(['message' => 'Order updated'], 200);
            else sendJson(['error' => 'Failed to update order'], 500);
        }
    }
}

// Fallback для отключённого JS (без AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isAjax) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $consent = isset($_POST['consent']);
    $modelId = (int)($_POST['model_id'] ?? 0);
    $packageId = !empty($_POST['package_id']) ? (int)$_POST['package_id'] : null;
    $serviceIds = isset($_POST['services']) ? array_map('intval', $_POST['services']) : [];
    $totalPrice = (float)($_POST['total_price'] ?? 0);

    $userMan = new UserManager();
    $orderMan = new OrderManager();
    $userId = $_SESSION['auto_user_id'] ?? null;

    if (!$userId) {
        $newUser = $userMan->createUser($full_name, $email, $phone, $consent);
        if (!$newUser['success']) {
            $_SESSION['flash'] = 'Ошибка: ' . implode(', ', $newUser['errors']);
            header('Location: index.php');
            exit;
        }
        $_SESSION['auto_user_id'] = $newUser['id'];
        $_SESSION['auto_user_login'] = $newUser['login'];
        $orderMan->createOrder($newUser['id'], $modelId, $packageId, $serviceIds, $totalPrice);
        $_SESSION['flash'] = "Заказ создан! Ваш логин: {$newUser['login']}, пароль: {$newUser['password']}";
    } else {
        $update = $userMan->updateUser($userId, $full_name, $email, $phone, $consent);
        if (!$update['success']) {
            $_SESSION['flash'] = 'Ошибка: ' . implode(', ', $update['errors']);
            header('Location: index.php');
            exit;
        }
        $lastOrder = $orderMan->getLastOrderByUser($userId);
        if ($lastOrder) {
            $orderMan->updateOrder($lastOrder['id'], $modelId, $packageId, $serviceIds, $totalPrice);
            $_SESSION['flash'] = 'Заказ обновлён';
        } else {
            $orderMan->createOrder($userId, $modelId, $packageId, $serviceIds, $totalPrice);
            $_SESSION['flash'] = 'Заказ создан';
        }
    }
    header('Location: index.php');
    exit;
}

// ====================== GET (отображение страницы) ======================
$carModel = new CarModels();
$userMan = new UserManager();
$orderMan = new OrderManager();

$isLoggedIn = isset($_SESSION['auto_user_id']);
$userData = $isLoggedIn ? $userMan->getUserById($_SESSION['auto_user_id']) : null;
$lastOrder = $isLoggedIn ? $orderMan->getLastOrderByUser($_SESSION['auto_user_id']) : null;

$models = $carModel->getAllModels();
$packages = $carModel->getAllPackages();
$services = $carModel->getAllServices();

$formData = [
    'full_name' => $userData ? $userData['full_name'] : '',
    'email'     => $userData ? $userData['email'] : '',
    'phone'     => $userData ? $userData['phone'] : '',
    'consent'   => $userData ? (bool)$userData['consent'] : false,
    'model_id'  => $lastOrder ? $lastOrder['model_id'] : ($models ? $models[0]['id'] : 1),
    'package_id'=> $lastOrder ? $lastOrder['package_id'] : null,
    'services'  => $lastOrder ? $lastOrder['service_ids'] : [],
    'total_price'=> $lastOrder ? (float)$lastOrder['total_price'] : 0
];

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Mercedes-Benz – Заказ автомобиля</title>
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
        .order-section {
            background: #1a1a1a;
            border-radius: 12px;
            padding: 2rem;
            margin: 2rem auto;
            max-width: 800px;
        }
        .services-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px,1fr));
            gap: 0.5rem;
        }
        .calculator-result {
            background: #0d0d0d;
            padding: 1rem;
            text-align: center;
            border-radius: 8px;
            margin-top: 1rem;
        }
        .total-price {
            font-size: 2rem;
            color: #00A0E3;
        }
        .success-message, .error-message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
        }
        .success-message { background: #2e7d32; color: white; }
        .error-message { background: #c62828; color: white; }
         .error-border {
            border: 1px solid #ff8a80 !important;
        }
        .field-error { color: #ff8a80; font-size: 0.8rem; margin-top: 0.3rem; }
    </style>
</head>
<body>
     <header>
        <div class="video-container">
            <video autoplay muted loop>
                <source src="vid.mp4" type="video/mp4">
                Ваш браузер не поддерживает видео.
            </video>
            <div class="overlay"></div>
        </div>
        
        <nav>
          <a href="panel.php" class="logo">Mercedes<span>Elite</span></a>
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
    <!-- ...  ... -->

     <section class="order-section" id="order-form-section">
        <h2 style="text-align:center;">Оформить заказ</h2>
        <p style="text-align:center;">Заполните форму, и менеджер свяжется с вами</p>

        <?php if ($flash): ?>
            <div class="success-message"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>

        <form id="order-form" action="index.php" method="post">
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
                <div class="total-price" id="total-price"><?= number_format($formData['total_price'], 0, '', ' ') ?> ₽</div>
            </div>

            <input type="hidden" name="total_price" id="total_price_hidden" value="<?= $formData['total_price'] ?>">
            <button type="submit" class="btn"><?= $isLoggedIn ? 'Обновить заказ' : 'Оформить заказ' ?></button>
            <div id="form-message" style="display:none;"></div>
        </form>

        <?php if ($isLoggedIn): ?>
            <div style="text-align:center; margin-top:1rem;" id="user-logged-indicator">
                Вы авторизованы как <?= htmlspecialchars($_SESSION['auto_user_login']) ?>
                <a href="logout.php">Выйти</a>
            </div>
        <?php endif; ?>
    </section>


    <!-- Остальной footer -->
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

    <script src="script.js" defer></script>
    <script src="blog.js" defer></script>
    <script src="public/js/order.js" defer></script>
    
</body>
</html>