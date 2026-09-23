<?php
require_once 'admin/config.php';
require_once 'admin/functions.php';

$products = getProducts();
$categories = getCategories(0);
$allCategories = getCategories();
$portfolio = getPortfolio(6);
$promotions = getActivePromotions();
$articles = getArticles(true);

// Получение страниц для меню и подвала
$pages = $pdo->query("SELECT * FROM pages WHERE is_visible = 1 ORDER BY sort_order ASC")->fetchAll();

$homeReviews = $pdo->query("SELECT * FROM reviews WHERE is_approved = 1 AND is_visible = 1 ORDER BY created_at DESC LIMIT 6")->fetchAll();

// Настройки сайта
$siteName = getSetting('site_name') ?? 'Домашний хлеб';
$sitePhone = getSetting('site_phone') ?? '+7 (499) 123-45-67';
$siteEmail = getSetting('site_email') ?? 'info@bakery.ru';
$siteAddress = getSetting('site_address') ?? 'ул. Хлебная, 15, Москва';
$workHours = getSetting('work_hours') ?? 'Ежедневно с 6:00 до 22:00';
$whatsapp = getSetting('whatsapp') ?? '+7 (999) 123-45-67';
$telegram = getSetting('telegram') ?? '@bakery_bot';
$instagram = getSetting('instagram') ?? '#';
$vk = getSetting('vk') ?? '#';
$youtube = getSetting('youtube') ?? '#';

$seoTitle = getSetting('seo_title') ?? 'Домашний хлеб — пекарня в Москве';
$seoDescription = getSetting('seo_description') ?? 'Семейная пекарня: свежий хлеб, булочки и пирожки. Доставка по Москве.';

$faqItems = [
    [
        'q' => 'С какого часа можно заказать свежую выпечку?',
        'a' => 'Печь запускается до рассвета: первая партия хлеба и булочек готова к 6:00. Заказы на доставку принимаем ежедневно в течение всего рабочего дня — до 22:00.',
    ],
    [
        'q' => 'Как быстро доставляете по Москве?',
        'a' => 'В пределах МКАД — обычно от 30 до 90 минут в зависимости от района и загруженности. Для крупных корпоративных и праздничных заказов согласуем удобный слот заранее.',
    ],
    [
        'q' => 'Какие ингредиенты вы используете?',
        'a' => 'Мука высшего сорта от проверенных мельниц, сливочное масло, свежие яйца, натуральная закваска. Без усилителей вкуса и растительных заменителей масла в базовой линейке хлеба.',
    ],
    [
        'q' => 'Можно ли заказать торт или набор к празднику?',
        'a' => 'Да. Принимаем предзаказы на торты, пироги и подарочные боксы за 24–48 часов. Для свадеб и корпоративов — индивидуальный расчёт и дегустация по договорённости.',
    ],
    [
        'q' => 'Есть ли самовывоз?',
        'a' => 'Да, самовывоз из пекарни по адресу «' . $siteAddress . '». Заказ можно забрать тёплым — мы соберём его к указанному времени.',
    ],
    [
        'q' => 'Сколько хранится ваш хлеб?',
        'a' => 'Пшеничный хлеб остаётся мягким двое суток, ржаной на закваске — до пяти дней. Мы не используем консерванты, поэтому храните хлеб в бумаге или льняном мешке, а не в полиэтилене.',
    ],
    [
        'q' => 'Есть ли изделия без сахара, лактозы или дрожжей?',
        'a' => 'В линейке есть хлеб на закваске без прессованных дрожжей, постные пирожки без молочных продуктов и несладкие багеты. Полный состав и список аллергенов подскажет менеджер при оформлении заказа.',
    ],
    [
        'q' => 'Работаете ли вы с кофейнями и офисами?',
        'a' => 'Да, у нас есть оптовое направление: ежедневные поставки хлеба и сдобы по графику, отсрочка платежа и договор. Напишите нам на ' . $siteEmail . ' — пришлём прайс и организуем дегустацию.',
    ],
];

// Категории для фильтрации
$categoryMap = [];
foreach ($allCategories as $cat) {
    $categoryMap[$cat['id']] = $cat['name'];
}

// Обработка формы заявки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_submit'])) {
    $name = trim($_POST['customerName'] ?? '');
    $phone = trim($_POST['customerPhone'] ?? '');
    $email = trim($_POST['customerEmail'] ?? '');
    $address = trim($_POST['customerAddress'] ?? '');
    $comment = trim($_POST['customerComment'] ?? '');
    $orderType = $_POST['order_type'] ?? 'order';
    
    // Состав корзины приходит скрытым полем из JS
    $cartItems = json_decode($_POST['cart_items'] ?? '[]', true);
    if (is_array($cartItems) && count($cartItems) > 0) {
        $lines = [];
        $total = 0;
        foreach ($cartItems as $item) {
            $itemName = trim((string)($item['name'] ?? ''));
            $qty = max(1, intval($item['quantity'] ?? 1));
            $price = floatval($item['price'] ?? 0);
            if ($itemName === '') continue;
            $total += $price * $qty;
            $lines[] = "{$itemName} x{$qty} = " . number_format($price * $qty, 0, '', ' ') . " руб.";
        }
        if ($lines) {
            $comment = trim($comment . "\n\nСостав заказа:\n" . implode("\n", $lines) .
                "\nИтого: " . number_format($total, 0, '', ' ') . " руб.");
        }
    }

    if (!empty($name) && !empty($phone)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO orders (order_type, customer_name, customer_phone, customer_email, customer_address, comment) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$orderType, $name, $phone, $email, $address, $comment]);
            
            $orderId = $pdo->lastInsertId();
            
            // Отправка уведомления менеджеру
            $adminEmail = getSetting('site_email') ?? 'admin@bakery.ru';
            $subject = "Новая заявка #{$orderId}";
            $message = "Новая заявка с сайта:\n\n";
            $message .= "Имя: {$name}\n";
            $message .= "Телефон: {$phone}\n";
            if ($email) $message .= "Email: {$email}\n";
            if ($address) $message .= "Адрес: {$address}\n";
            if ($comment) $message .= "Комментарий: {$comment}\n";
            $message .= "\nТип заявки: " . (getOrderTypes()[$orderType] ?? $orderType);
            
            // Отправка email (если настроено)
            // mail($adminEmail, $subject, $message, "From: {$siteEmail}\r\n");
            
            // Запись в сессию для отображения успеха
            $_SESSION['form_success'] = true;
            
            // Перенаправление для предотвращения повторной отправки
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?success=1#order');
            exit();
        } catch (PDOException $e) {
            $formError = 'Ошибка при отправке заявки. Пожалуйста, попробуйте позже.';
        }
    } else {
        $formError = 'Пожалуйста, заполните имя и телефон.';
    }
}

$success = isset($_GET['success']) && $_GET['success'] == 1;

// Товары для каталога на главной — берём все активные из БД (с категориями),
// чтобы фильтр по категориям работал по реальным данным
$popularProducts = $products;

// Данные для JS (корзина и фильтрация)
$productsForJs = [];
foreach ($products as $p) {
    $productsForJs[] = [
        'id'            => (int)$p['id'],
        'name'          => $p['name'],
        'price'         => (float)($p['price_from'] ?? 0),
        'category_id'   => (int)$p['category_id'],
        'category_slug' => $p['category_slug'] ?? '',
        'category_name' => $p['category_name'] ?? 'Выпечка',
        'image'         => !empty($p['image']) ? SITE_URL . 'uploads/' . $p['image'] : '',
    ];
}

$homeArticles = array_slice($articles ?: [], 0, 3);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($seoTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($seoDescription) ?>">
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Onest:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="preloader" id="preloader">
        <div class="preloader-bread">
            <i class="fas fa-wheat-awn"></i>
            <span><?= htmlspecialchars($siteName) ?></span>
        </div>
        <div class="preloader-bar">
            <div class="preloader-progress"></div>
        </div>
    </div>

    <div class="cursor-glow" id="cursorGlow"></div>

    <div class="scroll-progress" id="scrollProgress"></div>
    <div class="scroll-steam-layer" id="scrollSteamLayer" aria-hidden="true"></div>
    <div class="crumb-layer" id="crumbLayer" aria-hidden="true"></div>

    <nav class="nav" id="nav">
        <div class="nav-container">
            <a href="#" class="nav-logo">
                <i class="fas fa-wheat-awn"></i>
                <span><?= htmlspecialchars($siteName) ?></span>
            </a>
            
            <div class="nav-links" id="navLinks">
                <a href="#menu" class="nav-link">Меню</a>
                <a href="#about" class="nav-link">О нас</a>
                <a href="#process" class="nav-link">Как печём</a>
                <a href="#delivery-info" class="nav-link">Доставка</a>
                <a href="#faq" class="nav-link">Вопросы</a>
                <a href="#contacts" class="nav-link">Контакты</a>
                <?php if (count($pages) > 0): ?>
                    <div class="nav-dropdown">
                        <button type="button" class="nav-link nav-dropdown-btn" aria-expanded="false">
                            Ещё <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="nav-dropdown-menu">
                            <?php foreach ($pages as $page): ?>
                                <a href="page.php?slug=<?= htmlspecialchars($page['slug']) ?>"><?= htmlspecialchars($page['title']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="nav-actions">
                <button class="nav-cart" id="cartToggle">
                    <i class="fas fa-bag-shopping"></i>
                    <span class="cart-badge" id="cartBadge">0</span>
                </button>
                <button class="nav-menu-btn" id="menuBtn">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </nav>

    <!-- Витрина -->
    <section class="hero" id="hero">
        <div class="hero-bg">
            <div class="hero-bg-gradient"></div>
            <div class="hero-bg-particles" id="particles"></div>
        </div>

        <div class="hero-container">
            <div class="hero-content">
                <div class="hero-badge">
                    <span class="hero-badge-dot"></span>
                    Свежая выпечка с 6 утра
                </div>
                
                <h1 class="hero-title">
                    <span class="hero-title-line">Пекарня</span>
                    <span class="hero-title-line highlight"><?= htmlspecialchars($siteName) ?></span>
                </h1>

                <p class="hero-description">
                    Семейная пекарня в Москве: хлеб на закваске, булочки и пирожки
                    по рецептам, которые мы бережём с 2014 года. Печём несколько
                    партий в день — к вам приезжает ещё тёплая выпечка.
                </p>

                <div class="hero-actions">
                    <a href="#menu" class="btn btn-primary">
                        <span>Смотреть меню</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="#order" class="btn btn-ghost">
                        <i class="fas fa-truck-fast"></i>
                        <span>Заказать</span>
                    </a>
                </div>

                <div class="hero-stats">
                    <div class="hero-stat">
                        <span class="hero-stat-number"><?= max(count($products), 1) ?>+</span>
                        <span class="hero-stat-label">Видов выпечки</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-number"><?= date('H:i', strtotime('6:00')) ?></span>
                        <span class="hero-stat-label">Начало работы</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-number">100%</span>
                        <span class="hero-stat-label">Натурально</span>
                    </div>
                </div>
            </div>

            <div class="hero-visual">
                <div class="hero-visual-card">
                    <div class="steam" aria-hidden="true">
                        <span></span><span></span><span></span><span></span><span></span>
                    </div>
                    <div class="hero-visual-image">
                        <?php 
                        $heroImage = getSetting('hero_image') ?? 'uploads/products/breadmain.jpg';
                        ?>
                        <img src="<?= htmlspecialchars($heroImage) ?>" alt="Свежий хлеб">
                    </div>
                    <div class="hero-visual-floating">
                        <div class="floating-card floating-card-1">
                            <i class="fas fa-star"></i>
                            <span>4.9</span>
                        </div>
                        <div class="floating-card floating-card-2">
                            <i class="fas fa-clock"></i>
                            <span>Свежий</span>
                        </div>
                        <div class="floating-card floating-card-3">
                            <i class="fas fa-heart"></i>
                            <span>Любовь</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="hero-scroll">
            <span>Прокрутите вниз</span>
            <i class="fas fa-chevron-down"></i>
        </div>
    </section>

    <!-- О пекарне -->
    <section class="about has-bite" id="about">
        <div class="bite-divider" style="--bite-color: var(--color-white);" aria-hidden="true"></div>
        <div class="container">
            <div class="about-grid" data-reveal>
                <div class="about-visual">
                    <div class="about-visual-inner">
                        <?php 
                        $aboutImage = getSetting('about_image') ?? 'uploads/products/breadmain.jpg';
                        ?>
                        <img src="<?= htmlspecialchars($aboutImage) ?>" alt="Пекарня" loading="lazy">
                        <div class="about-visual-experience">
                            <span class="about-visual-number">12</span>
                            <span class="about-visual-text">лет<br>опыта</span>
                        </div>
                    </div>
                </div>

                <div class="about-content">
                    <span class="section-tag">О нас</span>
                    <h2 class="section-title"><?= getSetting('about_title') ?? 'Печём с душой<br>с 2014 года' ?></h2>
                    <p class="about-text">
                        <?= getSetting('about_text') ?? 'Мы — семейная пекарня в центре Москвы, где каждый день начинается с ночного замеса и долгой ферментации. Используем только натуральные ингредиенты и передаём тепло наших рук каждой буханке.' ?>
                    </p>
                    <p class="about-text about-text-more">
                        За 12 лет мы выросли из маленькой печи у дома в пекарню с доставкой по всему городу,
                        но сохранили ручной труд: формовка, надрезы и выпечка — без конвейерной спешки.
                        Гости ценят нас за хрустящую корочку, мягкий мякиш и честный вкус без «улучшителей».
                    </p>

                    <div class="about-features">
                        <div class="about-feature">
                            <div class="about-feature-icon">
                                <i class="fas fa-seedling"></i>
                            </div>
                            <div>
                                <h4>Натуральные ингредиенты</h4>
                                <p>Мука высшего сорта, свежие яйца, сливочное масло и живая закваска</p>
                            </div>
                        </div>
                        <div class="about-feature">
                            <div class="about-feature-icon">
                                <i class="fas fa-hand-holding-heart"></i>
                            </div>
                            <div>
                                <h4>Семейные рецепты</h4>
                                <p>Рецепты, которые передаются из поколения в поколение и проверены тысячами гостей</p>
                            </div>
                        </div>
                        <div class="about-feature">
                            <div class="about-feature-icon">
                                <i class="fas fa-fire"></i>
                            </div>
                            <div>
                                <h4>Свежая выпечка</h4>
                                <p>Готовим несколько партий в день — от утреннего хлеба до вечерних пирожков</p>
                            </div>
                        </div>
                    </div>

                    <div class="crust-note">
                        <i class="fas fa-wheat-awn"></i>
                        <p>
                            Наша закваска живёт с первого дня пекарни: её кормят дважды в сутки,
                            а тесто на ней выдерживает 16–18 часов холодной ферментации. Именно поэтому
                            хлеб дольше остаётся мягким, легче усваивается и пахнет так, как пахла
                            бабушкина кухня в воскресное утро.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Меню -->
    <section class="menu has-bite" id="menu">
        <div class="bite-divider" style="--bite-color: var(--color-bg);" aria-hidden="true"></div>
        <div class="container">
            <div class="menu-header" data-reveal>
                <span class="section-tag">Меню</span>
                <h2 class="section-title">Выберите свою<br>любимую выпечку</h2>
                <p class="menu-subtitle">
                    Каждый день мы печём для вас свежий хлеб на закваске, ароматные булочки,
                    сытные пирожки и десерты к чаю. Фильтруйте по категориям и собирайте заказ в корзину —
                    доставим тёплым или подготовим к самовывозу.
                </p>
            </div>

            <div class="menu-filters">
                <button class="filter-btn active" data-category="all">Всё меню</button>
                <?php foreach ($categories as $category): ?>
                    <button type="button" class="filter-btn" data-category="<?= htmlspecialchars($category['slug']) ?>"><?= htmlspecialchars($category['name']) ?></button>
                <?php endforeach; ?>
            </div>

            <div class="menu-grid" id="menuGrid">
                <?php if (count($popularProducts) > 0): ?>
                    <?php foreach ($popularProducts as $product): ?>
                        <div class="menu-item" data-category="<?= htmlspecialchars($product['category_slug'] ?? '') ?>" data-id="<?= (int)$product['id'] ?>">
                            <?php if (!empty($product['image'])): ?>
                            <img src="<?= htmlspecialchars(SITE_URL . 'uploads/' . $product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="menu-item-image" loading="lazy">
                            <?php endif; ?>
                            <div class="menu-item-content">
                                <span class="menu-item-tag"><?= htmlspecialchars($product['category_name'] ?? ($categoryMap[$product['category_id']] ?? 'Выпечка')) ?></span>
                                <h3 class="menu-item-name"><?= htmlspecialchars($product['name']) ?></h3>
                                <?php if (!empty($product['description'])): ?>
                                    <p class="menu-item-desc"><?= htmlspecialchars(mb_strimwidth(strip_tags($product['description']), 0, 90, '…')) ?></p>
                                <?php endif; ?>
                                <div class="menu-item-price"><?= number_format((float)($product['price_from'] ?? 0), 0, '', ' ') ?> ₽</div>
                                <button class="menu-item-btn" data-id="<?= $product['id'] ?>">
                                    <i class="fas fa-plus"></i>
                                    <span>В корзину</span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="grid-column:1/-1;text-align:center;color:var(--text-light);padding:40px 0;">Товары появятся после добавления в админке.</p>
                <?php endif; ?>
            </div>

            <div class="crust-note" data-reveal>
                <i class="fas fa-bread-slice"></i>
                <p>
                    Расписание печи: пшеничный и ржаной хлеб выходят к 6:00, сдоба и булочки — к 9:00,
                    пирожки и штрудели — к 13:00, а вечерняя партия багетов ждёт вас с 17:00.
                    Если нужного изделия нет в наличии, напишите нам — испечём в ближайшую закладку
                    и придержим заказ до вашего визита.
                </p>
            </div>

            <blockquote class="pull-quote" data-reveal>
                Хлеб не терпит спешки. Ему нужно время, тепло рук и немного тишины —
                всё остальное сделает закваска.
                <cite>Анна Ковалёва, шеф-пекарь</cite>
            </blockquote>
        </div>
    </section>

    <!-- Акции -->
    <?php if (count($promotions) > 0): ?>
        <section class="promotions" style="padding:60px 0;background:var(--color-white);">
            <div class="container">
                <div class="menu-header">
                    <span class="section-tag">Акции</span>
                    <h2 class="section-title">Специальные предложения</h2>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:24px;">
                    <?php foreach ($promotions as $promo): ?>
                        <div style="background:var(--color-bg);border-radius:var(--radius-lg);padding:24px;border-left:4px solid #E74C3C;">
                            <?php if (!empty($promo['image'])): ?>
                                <img src="uploads/<?= htmlspecialchars($promo['image']) ?>" alt="<?= htmlspecialchars($promo['title']) ?>" style="width:100%;height:160px;object-fit:cover;border-radius:8px;margin-bottom:12px;">
                            <?php endif; ?>
                            <h3 style="font-family:var(--font-serif);font-size:1.2rem;"><?= htmlspecialchars($promo['title']) ?></h3>
                            <?php if ($promo['discount']): ?>
                                <div style="color:#E74C3C;font-weight:700;font-size:1.1rem;margin:4px 0;"><?= htmlspecialchars($promo['discount']) ?></div>
                            <?php endif; ?>
                            <p style="color:var(--text-light);font-size:0.9rem;"><?= nl2br(htmlspecialchars($promo['description'])) ?></p>
                            <?php if ($promo['end_date']): ?>
                                <div style="font-size:0.8rem;color:var(--text-lighter);margin-top:8px;">До: <?= date('d.m.Y', strtotime($promo['end_date'])) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Фото работ -->
    <?php if (count($portfolio) > 0): ?>
        <section class="portfolio" style="padding:60px 0;background:var(--color-bg);">
            <div class="container">
                <div class="menu-header">
                    <span class="section-tag">Наши работы</span>
                    <h2 class="section-title">Примеры выпечки</h2>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:20px;">
                    <?php foreach ($portfolio as $item): ?>
                        <div style="background:var(--color-white);border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow-sm);">
                            <?php if (!empty($item['image'])): ?>
                                <img src="uploads/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" style="width:100%;height:200px;object-fit:cover;">
                            <?php endif; ?>
                            <div style="padding:16px;">
                                <h4 style="font-family:var(--font-serif);font-size:1rem;"><?= htmlspecialchars($item['title']) ?></h4>
                                <?php if ($item['category']): ?>
                                    <div style="font-size:0.8rem;color:var(--text-light);"><?= htmlspecialchars($item['category']) ?></div>
                                <?php endif; ?>
                                <?php if ($item['price_approx']): ?>
                                    <div style="font-weight:600;color:var(--color-brown);margin-top:4px;">от <?= number_format($item['price_approx'], 0, '', ' ') ?> ₽</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Отзывы -->
    <?php if (count($homeReviews) > 0): ?>
        <section class="reviews" style="padding:60px 0;background:var(--color-white);">
            <div class="container">
                <div class="menu-header">
                    <span class="section-tag">Отзывы</span>
                    <h2 class="section-title">Что говорят наши клиенты</h2>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:24px;">
                    <?php foreach ($homeReviews as $review): ?>
                        <div style="background:var(--color-bg);border-radius:var(--radius-lg);padding:20px;">
                            <div style="color:#F39C12;font-size:1.1rem;margin-bottom:8px;">
                                <?= str_repeat('⭐', $review['rating']) ?>
                            </div>
                            <p style="font-size:0.95rem;color:var(--text);line-height:1.6;"><?= nl2br(htmlspecialchars($review['text'])) ?></p>
                            <div style="margin-top:12px;font-weight:600;color:var(--color-brown);">
                                — <?= htmlspecialchars($review['customer_name']) ?>
                            </div>
                            <div style="font-size:0.8rem;color:var(--text-lighter);">
                                <?= date('d.m.Y', strtotime($review['created_at'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Преимущества -->
    <section class="advantages has-bite" id="advantages">
        <div class="bite-divider" style="--bite-color: var(--color-white);" aria-hidden="true"></div>
        <div class="container">
            <div class="menu-header" data-reveal>
                <span class="section-tag">Преимущества</span>
                <h2 class="section-title">Почему выбирают<br>«<?= htmlspecialchars($siteName) ?>»</h2>
                <p class="menu-subtitle">
                    Современные пекарни в 2026 году сочетают ремесленный подход, прозрачный состав
                    и удобный заказ онлайн — именно так мы работаем каждый день.
                </p>
            </div>
            <p class="section-lead" data-reveal>
                Мы не гонимся за объёмом: суточная загрузка печи ограничена, чтобы каждая партия
                успевала отдохнуть и остыть правильно. Такой подход стоит дороже промышленного,
                зато вы получаете хлеб, который хочется доесть до последней крошки.
            </p>
            <div class="advantages-grid">
                <article class="advantage-card" data-reveal="zoom">
                    <div class="advantage-icon"><i class="fas fa-wheat-awn"></i></div>
                    <h3>Ремесленный хлеб</h3>
                    <p>Долгая ферментация, ручная формовка и выпечка на камне — корочка хрустит, мякиш остаётся влажным до вечера.</p>
                </article>
                <article class="advantage-card" data-reveal="zoom">
                    <div class="advantage-icon"><i class="fas fa-leaf"></i></div>
                    <h3>Честный состав</h3>
                    <p>Без искусственных ароматизаторов и гидрогенизированных жиров. Состав каждого изделия можно уточнить у пекаря.</p>
                </article>
                <article class="advantage-card" data-reveal="zoom">
                    <div class="advantage-icon"><i class="fas fa-motorcycle"></i></div>
                    <h3>Доставка «из печи»</h3>
                    <p>Курьеры забирают заказ сразу после выпечки. Сохраняем тепло специальной упаковкой и бережной логистикой по Москве.</p>
                </article>
                <article class="advantage-card" data-reveal="zoom">
                    <div class="advantage-icon"><i class="fas fa-calendar-check"></i></div>
                    <h3>Предзаказ к событию</h3>
                    <p>Корпоративные завтраки, дни рождения и свадебные столы — соберём меню под ваш формат и бюджет.</p>
                </article>
            </div>
        </div>
    </section>

    <!-- Как печём -->
    <section class="process has-bite" id="process">
        <div class="bite-divider" style="--bite-color: var(--color-bg);" aria-hidden="true"></div>
        <div class="container">
            <div class="menu-header" data-reveal>
                <span class="section-tag">Процесс</span>
                <h2 class="section-title">Как мы печём<br>каждый день</h2>
                <p class="menu-subtitle">Четыре шага от зерна до вашей корзины — без спешки и компромиссов.</p>
            </div>
            <div class="process-timeline">
                <div class="process-step" data-reveal>
                    <span class="process-num">01</span>
                    <h3>Ночной замес</h3>
                    <p>
                        В 23:00 пекарь ставит опару и убирает тесто в холод. Дальше время работает
                        за нас: 16 часов ферментации раскрывают вкус зерна и делают хлеб мягче.
                    </p>
                </div>
                <div class="process-step" data-reveal>
                    <span class="process-num">02</span>
                    <h3>Формовка вручную</h3>
                    <p>
                        Каждую буханку и булочку формуем руками, не выбивая пузырьки воздуха.
                        Надрез делаем лезвием под углом — так корочка раскрывается «ушком».
                    </p>
                </div>
                <div class="process-step" data-reveal>
                    <span class="process-num">03</span>
                    <h3>Выпечка партиями</h3>
                    <p>
                        Подовая печь с паром: первые минуты хлеб «поднимается» во влажном жаре,
                        затем сушится до золотого цвета. Четыре закладки в день, никаких заморозок.
                    </p>
                </div>
                <div class="process-step" data-reveal>
                    <span class="process-num">04</span>
                    <h3>Доставка или самовывоз</h3>
                    <p>
                        Хлебу дают остыть на решётке 40 минут — иначе он «запарится» в пакете.
                        Только после этого мы упаковываем заказ в крафт и передаём курьеру.
                    </p>
                </div>
            </div>

            <div class="crust-note" data-reveal>
                <i class="fas fa-temperature-half"></i>
                <p>
                    Как хранить: пшеничный хлеб держите в льняном мешке или бумаге при комнатной
                    температуре — до двух суток. Ржаной на закваске живёт до пяти дней и с каждым днём
                    становится ароматнее. Подсохший ломоть верните к жизни: сбрызните водой и отправьте
                    в духовку на 5 минут при 180 °C.
                </p>
            </div>
        </div>
    </section>

    <!-- Доставка -->
    <section class="delivery-info has-bite" id="delivery-info">
        <div class="bite-divider" style="--bite-color: var(--color-white);" aria-hidden="true"></div>
        <div class="container">
            <div class="delivery-info-grid">
                <div class="delivery-info-content" data-reveal="left">
                    <span class="section-tag">Доставка по Москве</span>
                    <h2 class="section-title">Свежая выпечка<br>у вас дома</h2>
                    <p>
                        Работаем по всей Москве и ближайшему Подмосковью. Минимальный заказ для доставки —
                        от 500 ₽. При заказе от 1000 ₽ — домашнее печенье в подарок. Оплата картой курьеру,
                        онлайн или наличными.
                    </p>
                    <p>
                        Заказы, оформленные до 11:00, доставляем в тот же день. Для офисных завтраков
                        и мероприятий согласуем точный интервал: привезём выпечку к началу встречи,
                        нарежем и разложим по коробкам, если это нужно.
                    </p>
                    <ul class="delivery-zones">
                        <li><i class="fas fa-check"></i> Центр и Садовое кольцо — от 30 минут</li>
                        <li><i class="fas fa-check"></i> В пределах МКАД — обычно до 90 минут</li>
                        <li><i class="fas fa-check"></i> За МКАД — по согласованию, фиксированный слот</li>
                        <li><i class="fas fa-check"></i> Самовывоз: <?= htmlspecialchars($siteAddress) ?></li>
                    </ul>
                    <a href="#order" class="btn btn-primary">
                        <span>Оформить доставку</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="delivery-info-aside" data-reveal="right">
                    <div class="delivery-stat">
                        <strong>6:00</strong>
                        <span>Первая партия из печи</span>
                    </div>
                    <div class="delivery-stat">
                        <strong>2 ч</strong>
                        <span>Среднее время доставки</span>
                    </div>
                    <div class="delivery-stat">
                        <strong>500 ₽</strong>
                        <span>Минимальный заказ</span>
                    </div>
                    <div class="delivery-stat">
                        <strong>100%</strong>
                        <span>Гарантия свежести дня</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Заказ -->
    <section class="order" id="order">
        <div class="container">
            <div class="order-wrapper">
                <div class="order-content">
                    <span class="section-tag">Доставка</span>
                    <h2 class="section-title">Оформите<br>заказ сейчас</h2>
                    <p class="order-text">
                        Заполните форму — менеджер подтвердит состав, время и адрес.
                        Доставим свежую выпечку обычно в течение 1–2 часов по Москве
                        или подготовим заказ к самовывозу к удобному слоту.
                    </p>

                    <div class="order-benefits">
                        <div class="order-benefit">
                            <div class="order-benefit-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <h4>Быстрая доставка</h4>
                                <p>От 30 минут в пределах города</p>
                            </div>
                        </div>
                        <div class="order-benefit">
                            <div class="order-benefit-icon">
                                <i class="fas fa-gift"></i>
                            </div>
                            <div>
                                <h4>Подарок к заказу</h4>
                                <p>При заказе от 1000 ₽ — печенье в подарок</p>
                            </div>
                        </div>
                        <div class="order-benefit">
                            <div class="order-benefit-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div>
                                <h4>Гарантия свежести</h4>
                                <p>Выпечка только из печи в день доставки</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="order-form-wrapper">
                    <?php if ($success): ?>
                        <div class="alert alert-success" style="background:#E8F5E9;color:#2E7D32;padding:16px;border-radius:8px;margin-bottom:20px;">
                            <i class="fas fa-check-circle"></i> Спасибо! Ваш заказ принят. Мы свяжемся с вами в ближайшее время.
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($formError)): ?>
                        <div class="alert alert-danger" style="background:#FEE;color:#C0392B;padding:16px;border-radius:8px;margin-bottom:20px;">
                            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($formError) ?>
                        </div>
                    <?php endif; ?>
                    
                    <form class="order-form" id="orderForm" method="POST">
                        <input type="hidden" name="order_type" value="order">
                        <input type="hidden" name="cart_items" id="cartItemsField" value="[]">
                        <h3 class="order-form-title">Детали заказа</h3>
                        
                        <div class="form-group">
                            <label for="customerName">Ваше имя</label>
                            <div class="form-input-wrap">
                                <i class="fas fa-user"></i>
                                <input type="text" id="customerName" name="customerName" placeholder="Иван Петров" required>
                            </div>
                            <span class="form-error">Пожалуйста, введите ваше имя</span>
                        </div>

                        <div class="form-group">
                            <label for="customerPhone">Телефон</label>
                            <div class="form-input-wrap">
                                <i class="fas fa-phone"></i>
                                <input type="tel" id="customerPhone" name="customerPhone" placeholder="+7 (999) 999-99-99" required>
                            </div>
                            <span class="form-error">Введите корректный номер телефона</span>
                        </div>

                        <div class="form-group">
                            <label for="customerAddress">Адрес доставки</label>
                            <div class="form-input-wrap">
                                <i class="fas fa-location-dot"></i>
                                <input type="text" id="customerAddress" name="customerAddress" placeholder="Улица, дом, квартира" required>
                            </div>
                            <span class="form-error">Введите адрес доставки</span>
                        </div>

                        <div class="form-group">
                            <label for="customerEmail">Email</label>
                            <div class="form-input-wrap">
                                <i class="fas fa-envelope"></i>
                                <input type="email" id="customerEmail" name="customerEmail" placeholder="ivan@mail.ru">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="orderComment">Комментарий к заказу</label>
                            <div class="form-input-wrap">
                                <i class="fas fa-comment"></i>
                                <input type="text" id="orderComment" name="customerComment" placeholder="Дополнительные пожелания">
                            </div>
                        </div>

                        <button type="submit" name="order_submit" class="btn btn-primary btn-full">
                            <i class="fas fa-paper-plane"></i>
                            <span>Оформить заказ</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Вопросы -->
    <section class="faq has-bite" id="faq">
        <div class="bite-divider" style="--bite-color: var(--color-bg);" aria-hidden="true"></div>
        <div class="container">
            <div class="menu-header" data-reveal>
                <span class="section-tag">FAQ</span>
                <h2 class="section-title">Частые вопросы</h2>
                <p class="menu-subtitle">Ответы, которые помогают быстрее оформить первый заказ.</p>
            </div>
            <div class="faq-list" data-reveal>
                <?php foreach ($faqItems as $i => $faq): ?>
                    <details class="faq-item" <?= $i === 0 ? 'open' : '' ?>>
                        <summary><?= htmlspecialchars($faq['q']) ?></summary>
                        <p><?= htmlspecialchars($faq['a']) ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php if (count($homeArticles) > 0): ?>
    <section class="articles" id="articles">
        <div class="container">
            <div class="menu-header">
                <span class="section-tag">Блог</span>
                <h2 class="section-title">Советы и новости<br>пекарни</h2>
                <p class="menu-subtitle">Рецепты, сезонные новинки и истории о хлебе — для тех, кто любит выпечку так же, как мы.</p>
            </div>
            <div class="articles-grid">
                <?php foreach ($homeArticles as $article): ?>
                    <article class="article-card" data-reveal>
                        <?php if (!empty($article['image'])): ?>
                            <img src="<?= htmlspecialchars(SITE_URL . 'uploads/' . $article['image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>" loading="lazy">
                        <?php endif; ?>
                        <div class="article-card-body">
                            <h3><?= htmlspecialchars($article['title']) ?></h3>
                            <p><?= htmlspecialchars(mb_strimwidth(strip_tags($article['excerpt'] ?? $article['content'] ?? ''), 0, 140, '…')) ?></p>
                            <?php if (!empty($article['published_at'])): ?>
                                <time datetime="<?= date('Y-m-d', strtotime($article['published_at'])) ?>"><?= date('d.m.Y', strtotime($article['published_at'])) ?></time>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Контакты -->
    <section class="contacts has-bite" id="contacts">
        <div class="bite-divider" style="--bite-color: var(--color-bg);" aria-hidden="true"></div>
        <div class="container">
            <div class="contacts-header" data-reveal>
                <span class="section-tag">Контакты</span>
                <h2 class="section-title">Свяжитесь<br>с нами</h2>
                <p class="menu-subtitle contacts-lead">
                    Приезжайте в пекарню за тёплым хлебом или напишите нам — ответим в мессенджерах
                    и по телефону в рабочее время <?= htmlspecialchars($workHours) ?>.
                </p>
            </div>

            <div class="contacts-grid">
                <div class="contacts-info">
                    <div class="contact-item">
                        <div class="contact-item-icon">
                            <i class="fas fa-location-dot"></i>
                        </div>
                        <div>
                            <h4>Адрес</h4>
                            <p><?= htmlspecialchars($siteAddress) ?></p>
                            <a href="#" class="contact-link">Открыть в картах →</a>
                        </div>
                    </div>

                    <div class="contact-item">
                        <div class="contact-item-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <h4>Время работы</h4>
                            <p><?= htmlspecialchars($workHours) ?></p>
                            <span class="contact-badge">Без выходных</span>
                        </div>
                    </div>

                    <div class="contact-item">
                        <div class="contact-item-icon whatsapp">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <div>
                            <h4>WhatsApp</h4>
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $whatsapp) ?>" class="contact-link"><?= htmlspecialchars($whatsapp) ?></a>
                            <p class="contact-hint">Напишите нам в любое время</p>
                        </div>
                    </div>

                    <div class="contact-item">
                        <div class="contact-item-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div>
                            <h4>Телефон</h4>
                            <a href="tel:<?= preg_replace('/[^0-9+]/', '', $sitePhone) ?>" class="contact-link"><?= htmlspecialchars($sitePhone) ?></a>
                            <p class="contact-hint">Звоните с 6:00 до 22:00</p>
                        </div>
                    </div>
                </div>

                <div class="contacts-map">
                    <script type="text/javascript" charset="utf-8" async src="https://api-maps.yandex.ru/services/constructor/1.0/js/?um=constructor%3Adfd0004b4c91f4263c83ddb9b11858aace4354bd4ecab9c15eb58737c36d8e51&amp;width=500&amp;height=400&amp;lang=ru_RU&amp;scroll=true"></script>
                </div>
            </div>

            <div class="crust-note" data-reveal>
                <i class="fas fa-shop"></i>
                <p>
                    Заходите в гости: витрина обновляется четыре раза в день, а запах свежего хлеба
                    слышно ещё от входа. У нас можно взять кофе с собой, попробовать хлеб перед покупкой
                    и забрать «вчерашнюю» выпечку со скидкой 30% — мы не выбрасываем хорошее,
                    а отдаём его дешевле или передаём в фонд помощи.
                </p>
            </div>
        </div>
    </section>

    <!-- Подвал -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <a href="#" class="footer-logo">
                        <i class="fas fa-wheat-awn"></i>
                        <span><?= htmlspecialchars($siteName) ?></span>
                    </a>
                    <p class="footer-description">
                        Семейная пекарня в Москве:<br>
                        свежий хлеб, булочки и пирожки<br>
                        каждый день с 2014 года
                    </p>
                    <div class="footer-socials">
                        <a href="<?= htmlspecialchars($instagram) ?>" class="footer-social"><i class="fab fa-instagram"></i></a>
                        <a href="<?= htmlspecialchars($telegram) ?>" class="footer-social"><i class="fab fa-telegram"></i></a>
                        <a href="<?= htmlspecialchars($vk) ?>" class="footer-social"><i class="fab fa-vk"></i></a>
                        <a href="<?= htmlspecialchars($youtube) ?>" class="footer-social"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>

                <div class="footer-links">
                    <div class="footer-links-column">
                        <h4>Меню</h4>
                        <?php foreach ($categories as $category): ?>
                            <a href="#menu"><?= htmlspecialchars($category['name']) ?></a>
                        <?php endforeach; ?>
                    </div>
                    <div class="footer-links-column">
                        <h4>Информация</h4>
                        <?php foreach ($pages as $page): ?>
                            <a href="page.php?slug=<?= $page['slug'] ?>"><?= htmlspecialchars($page['title']) ?></a>
                        <?php endforeach; ?>
                        <a href="#about">О нас</a>
                        <a href="#process">Как печём</a>
                        <a href="#delivery-info">Доставка</a>
                        <a href="#faq">Вопросы</a>
                        <a href="#order">Заказать</a>
                        <a href="#contacts">Контакты</a>
                    </div>
                    <div class="footer-links-column">
                        <h4>Контакты</h4>
                        <a href="tel:<?= preg_replace('/[^0-9+]/', '', $sitePhone) ?>"><?= htmlspecialchars($sitePhone) ?></a>
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $whatsapp) ?>">WhatsApp</a>
                        <a href="mailto:<?= htmlspecialchars($siteEmail) ?>"><?= htmlspecialchars($siteEmail) ?></a>
                    </div>
                </div>
            </div>

            <div class="footer-about">
                <p>
                    Пекарня «<?= htmlspecialchars($siteName) ?>» — это хлеб на закваске, сдоба, пирожки
                    и торты на заказ в Москве. Мы печём с 2014 года, работаем ежедневно
                    <?= htmlspecialchars(mb_strtolower($workHours)) ?> и доставляем свежую выпечку
                    по городу и ближайшему Подмосковью. Забрать заказ можно самостоятельно
                    по адресу <?= htmlspecialchars($siteAddress) ?>.
                </p>
            </div>

            <div class="footer-bottom">
                <span>© <?= date('Y') ?> Пекарня «<?= htmlspecialchars($siteName) ?>». Все права защищены.</span>
                <span>Печём с любовью каждый день</span>
            </div>
        </div>
    </footer>

    <!-- Корзина -->
    <div class="cart-overlay" id="cartOverlay"></div>
    <div class="cart-sidebar" id="cartSidebar">
        <div class="cart-header">
            <h3 class="cart-title">
                <i class="fas fa-bag-shopping"></i>
                Корзина
            </h3>
            <button class="cart-close" id="cartClose">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="cart-body" id="cartBody">
            <div class="cart-empty">
                <div class="cart-empty-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h4>Корзина пуста</h4>
                <p>Добавьте товары из меню</p>
            </div>
        </div>

        <div class="cart-footer">
            <div class="cart-total">
                <span>Итого</span>
                <span class="cart-total-price" id="cartTotalPrice">0 ₽</span>
            </div>
            <button class="btn btn-primary btn-full" id="checkoutBtn">
                <span>Оформить заказ</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </div>

    <!-- Уведомления -->
    <div class="toast-container" id="toastContainer"></div>

    <script>
    // Передаем данные из БД в JavaScript (корзина + фильтр по категориям)
    window.productsData = <?= json_encode($productsForJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    </script>
    <script src="js/script.js"></script>
</body>
</html>