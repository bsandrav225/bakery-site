<?php

// Обработка сохранения
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $settings = $_POST['settings'] ?? [];
    
    foreach ($settings as $key => $value) {
        updateSetting($key, trim($value));
    }
    
    flashMessage('Настройки сохранены', 'success');
    redirect('?page=settings');
}

// Получение всех настроек
$settingGroups = [
    'general' => ['site_name' => 'Название сайта', 'site_phone' => 'Телефон', 'site_email' => 'Email', 'site_address' => 'Адрес', 'work_hours' => 'Время работы'],
    'social' => ['whatsapp' => 'WhatsApp', 'telegram' => 'Telegram', 'instagram' => 'Instagram', 'vk' => 'ВКонтакте', 'youtube' => 'YouTube'],
    'seo' => ['seo_title' => 'SEO Заголовок', 'seo_description' => 'SEO Описание', 'seo_keywords' => 'SEO Ключевые слова'],
    'contacts' => ['map_center' => 'Центр карты', 'map_zoom' => 'Зум карты']
];

$allSettings = [];
foreach ($settingGroups as $group => $keys) {
    foreach ($keys as $key => $label) {
        $allSettings[$key] = getSetting($key) ?? '';
    }
}
?>

<?php $flash = getFlashMessage(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>" style="background:<?= $flash['type'] === 'success' ? '#E8F5E9' : '#FEE' ?>;color:<?= $flash['type'] === 'success' ? '#2E7D32' : '#C0392B' ?>;padding:12px 16px;border-radius:8px;margin-bottom:20px;">
        <?= $flash['message'] ?>
    </div>
<?php endif; ?>

<form method="POST">
    <?php foreach ($settingGroups as $group => $keys): ?>
        <div class="form-section">
            <h3 style="text-transform:capitalize;"><?= $group === 'general' ? 'Общие' : ($group === 'social' ? 'Социальные сети' : ($group === 'seo' ? 'SEO настройки' : 'Контакты')) ?></h3>
            
            <div class="form-row">
                <?php foreach ($keys as $key => $label): ?>
                    <div class="form-group">
                        <label for="setting_<?= $key ?>"><?= $label ?></label>
                        <input type="text" id="setting_<?= $key ?>" name="settings[<?= $key ?>]" value="<?= escape($allSettings[$key] ?? '') ?>" 
                               <?= in_array($key, ['site_phone', 'whatsapp', 'telegram']) ? 'placeholder="+7 (999) 123-45-67"' : '' ?>>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
    
    <div class="form-actions">
        <button type="submit" name="save_settings" class="btn btn-primary">
            <i class="fas fa-save"></i> Сохранить все настройки
        </button>
    </div>
</form>

<!-- Дополнительные настройки -->
<div class="form-section">
    <h3>Дополнительные настройки</h3>
    
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <div>
            <h4 style="font-size:0.9rem;margin-bottom:8px;">SMTP настройки</h4>
            <div class="form-group">
                <label>SMTP Хост</label>
                <input type="text" value="<?= SMTP_HOST ?>" disabled style="background:#f5f5f5;">
            </div>
            <div class="form-group">
                <label>SMTP Порт</label>
                <input type="text" value="<?= SMTP_PORT ?>" disabled style="background:#f5f5f5;">
            </div>
            <div class="form-group">
                <label>SMTP Пользователь</label>
                <input type="text" value="<?= SMTP_USER ?>" disabled style="background:#f5f5f5;">
            </div>
            <p style="font-size:0.8rem;color:var(--text-light);">SMTP задаётся в admin/config.local.php</p>
        </div>
        
        <div>
            <h4 style="font-size:0.9rem;margin-bottom:8px;">Информация о сайте</h4>
            <div class="form-group">
                <label>URL сайта</label>
                <input type="text" value="<?= SITE_URL ?>" disabled style="background:#f5f5f5;">
            </div>
            <div class="form-group">
                <label>Путь к админ-панели</label>
                <input type="text" value="<?= ADMIN_URL ?>" disabled style="background:#f5f5f5;">
            </div>
            <div class="form-group">
                <label>Версия PHP</label>
                <input type="text" value="<?= phpversion() ?>" disabled style="background:#f5f5f5;">
            </div>
        </div>
    </div>
</div>

<!-- Сброс кэша -->
<div class="form-section" style="border:1px solid #FEE;background:#FFF8F8;">
    <h3 style="color:#C0392B;">Инструменты</h3>
    
    <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <form method="POST" onsubmit="return confirm('Очистить кэш?')">
            <input type="hidden" name="action" value="clear_cache">
            <button type="submit" class="btn btn-warning">
                <i class="fas fa-broom"></i> Очистить кэш
            </button>
        </form>
        
        <form method="POST" onsubmit="return confirm('Создать бекап базы данных?')">
            <input type="hidden" name="action" value="backup">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-database"></i> Создать бекап БД
            </button>
        </form>
    </div>
</div>