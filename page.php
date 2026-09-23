<?php
require_once 'admin/config.php';
require_once 'admin/functions.php';

$slug = $_GET['slug'] ?? '';
$page = getPage($slug);

if (!$page) {
    header('Location: index.php');
    exit();
}

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

$pages = $pdo->query("SELECT * FROM pages WHERE is_visible = 1 ORDER BY sort_order ASC")->fetchAll();
$categories = getCategories(0);

$seoTitle = $page['meta_title'] ?: ($page['title'] . ' — ' . $siteName);
$seoDescription = $page['meta_description'] ?: ($page['title'] . ' — пекарня «' . $siteName . '»');
$phoneTel = preg_replace('/[^0-9+]/', '', $sitePhone);
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
    <div class="scroll-progress" id="scrollProgress"></div>
    <div class="scroll-steam-layer" id="scrollSteamLayer" aria-hidden="true"></div>
    <div class="crumb-layer" id="crumbLayer" aria-hidden="true"></div>

    <nav class="nav" id="nav">
        <div class="nav-container">
            <a href="index.php" class="nav-logo">
                <i class="fas fa-wheat-awn"></i>
                <span><?= htmlspecialchars($siteName) ?></span>
            </a>

            <div class="nav-links" id="navLinks">
                <a href="index.php#menu" class="nav-link">Меню</a>
                <a href="index.php#about" class="nav-link">О нас</a>
                <a href="index.php#process" class="nav-link">Как печём</a>
                <a href="index.php#delivery-info" class="nav-link">Доставка</a>
                <a href="index.php#faq" class="nav-link">Вопросы</a>
                <a href="index.php#contacts" class="nav-link">Контакты</a>
                <?php if (count($pages) > 0): ?>
                    <div class="nav-dropdown">
                        <button type="button" class="nav-link nav-dropdown-btn" aria-expanded="false">
                            Ещё <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="nav-dropdown-menu">
                            <?php foreach ($pages as $navPage): ?>
                                <a href="page.php?slug=<?= htmlspecialchars($navPage['slug']) ?>"><?= htmlspecialchars($navPage['title']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="nav-actions">
                <a href="index.php#order" class="nav-cart" title="К заказу">
                    <i class="fas fa-bag-shopping"></i>
                </a>
                <button class="nav-menu-btn" id="menuBtn" type="button">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </nav>

    <section class="static-page">
        <div class="container">
            <nav class="breadcrumbs" aria-label="Хлебные крошки">
                <a href="index.php">Главная</a>
                <span>/</span>
                <span><?= htmlspecialchars($page['title']) ?></span>
            </nav>

            <h1 class="static-page-title"><?= htmlspecialchars($page['title']) ?></h1>

            <div class="static-page-content">
                <?php if (trim(strip_tags((string)$page['content'])) !== ''): ?>
                    <?= $page['content'] ?>
                <?php else: ?>
                    <p>
                        Страница «<?= htmlspecialchars($page['title']) ?>» пекарни «<?= htmlspecialchars($siteName) ?>».
                        Мы печём свежий хлеб, булочки и пирожки ежедневно с 6:00 и доставляем по Москве.
                    </p>
                    <p>
                        Адрес: <?= htmlspecialchars($siteAddress) ?>. Режим работы: <?= htmlspecialchars($workHours) ?>.
                        Телефон: <a href="tel:<?= htmlspecialchars($phoneTel) ?>"><?= htmlspecialchars($sitePhone) ?></a>,
                        email: <a href="mailto:<?= htmlspecialchars($siteEmail) ?>"><?= htmlspecialchars($siteEmail) ?></a>.
                    </p>
                    <p>
                        Чтобы оформить заказ, перейдите в <a href="index.php#menu">меню</a>
                        или сразу к <a href="index.php#order">форме доставки</a>.
                        По вопросам состава, аллергенов и предзаказов к празднику — напишите нам в WhatsApp
                        или позвоните менеджеру.
                    </p>
                <?php endif; ?>
            </div>

            <div class="static-page-cta">
                <a href="index.php#menu" class="btn btn-primary">
                    <span>Смотреть меню</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
                <a href="index.php#order" class="btn btn-ghost">
                    <i class="fas fa-truck-fast"></i>
                    <span>Заказать доставку</span>
                </a>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <a href="index.php" class="footer-logo">
                        <i class="fas fa-wheat-awn"></i>
                        <span><?= htmlspecialchars($siteName) ?></span>
                    </a>
                    <p class="footer-description">
                        Семейная пекарня в Москве:<br>
                        свежий хлеб и выпечка с 2014 года
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
                        <?php if (count($categories) > 0): ?>
                            <?php foreach ($categories as $category): ?>
                                <a href="index.php#menu"><?= htmlspecialchars($category['name']) ?></a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <a href="index.php#menu">Хлеб</a>
                            <a href="index.php#menu">Булочки</a>
                            <a href="index.php#menu">Пирожки</a>
                            <a href="index.php#menu">Торты</a>
                        <?php endif; ?>
                    </div>
                    <div class="footer-links-column">
                        <h4>Информация</h4>
                        <?php foreach ($pages as $navPage): ?>
                            <a href="page.php?slug=<?= htmlspecialchars($navPage['slug']) ?>"><?= htmlspecialchars($navPage['title']) ?></a>
                        <?php endforeach; ?>
                        <a href="index.php#about">О нас</a>
                        <a href="index.php#order">Доставка</a>
                        <a href="index.php#contacts">Контакты</a>
                    </div>
                    <div class="footer-links-column">
                        <h4>Контакты</h4>
                        <a href="tel:<?= htmlspecialchars($phoneTel) ?>"><?= htmlspecialchars($sitePhone) ?></a>
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $whatsapp) ?>">WhatsApp</a>
                        <a href="mailto:<?= htmlspecialchars($siteEmail) ?>"><?= htmlspecialchars($siteEmail) ?></a>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <span>© <?= date('Y') ?> Пекарня «<?= htmlspecialchars($siteName) ?>». Все права защищены.</span>
                <span>Свежая выпечка каждый день</span>
            </div>
        </div>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>
