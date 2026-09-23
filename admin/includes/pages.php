<?php
// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $slug = slugify($title);
        $content = $_POST['content'] ?? '';
        $meta_title = trim($_POST['meta_title'] ?? '');
        $meta_description = trim($_POST['meta_description'] ?? '');
        $is_visible = isset($_POST['is_visible']) ? 1 : 0;
        $sort_order = intval($_POST['sort_order'] ?? 0);
        
        if ($title) {
            // Проверка уникальности slug
            $stmt = $pdo->prepare("SELECT id FROM pages WHERE slug = ?" . ($action === 'edit' ? " AND id != ?" : ""));
            $params = [$slug];
            if ($action === 'edit') {
                $params[] = $id;
            }
            $stmt->execute($params);
            if ($stmt->fetch()) {
                $formError = 'Страница с таким адресом уже существует';
            } else {
                if ($action === 'add') {
                    $stmt = $pdo->prepare("INSERT INTO pages (title, slug, content, meta_title, meta_description, is_visible, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $slug, $content, $meta_title, $meta_description, $is_visible, $sort_order]);
                    flashMessage('Страница добавлена', 'success');
                } else {
                    $stmt = $pdo->prepare("UPDATE pages SET title = ?, slug = ?, content = ?, meta_title = ?, meta_description = ?, is_visible = ?, sort_order = ? WHERE id = ?");
                    $stmt->execute([$title, $slug, $content, $meta_title, $meta_description, $is_visible, $sort_order, $id]);
                    flashMessage('Страница обновлена', 'success');
                }
                redirect('?page=pages');
            }
        } else {
            $formError = 'Заполните название страницы';
        }
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM pages WHERE id = ?");
        $stmt->execute([$id]);
        flashMessage('Страница удалена', 'success');
        redirect('?page=pages');
    }
}

$pages = $pdo->query("SELECT * FROM pages ORDER BY sort_order ASC, id ASC")->fetchAll();

$editPage = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $editPage = $stmt->fetch();
}
?>

<?php $flash = getFlashMessage(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>" style="background:<?= $flash['type'] === 'success' ? '#E8F5E9' : '#FEE' ?>;color:<?= $flash['type'] === 'success' ? '#2E7D32' : '#C0392B' ?>;padding:12px 16px;border-radius:8px;margin-bottom:20px;">
        <?= $flash['message'] ?>
    </div>
<?php endif; ?>

<!-- Форма -->
<div class="form-section">
    <h3><?= $editPage ? 'Редактировать страницу' : 'Добавить страницу' ?></h3>
    
    <?php if (isset($formError)): ?>
        <div class="alert alert-danger" style="background:#FEE;color:#C0392B;padding:12px 16px;border-radius:8px;margin-bottom:16px;">
            <?= $formError ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <input type="hidden" name="action" value="<?= $editPage ? 'edit' : 'add' ?>">
        <?php if ($editPage): ?>
            <input type="hidden" name="id" value="<?= $editPage['id'] ?>">
        <?php endif; ?>
        
        <div class="form-row">
            <div class="form-group">
                <label>Заголовок *</label>
                <input type="text" name="title" value="<?= $editPage ? escape($editPage['title']) : '' ?>" required>
            </div>
            <div class="form-group">
                <label>Адрес (slug)</label>
                <input type="text" value="<?= $editPage ? escape($editPage['slug']) : 'auto' ?>" disabled style="background:#f5f5f5;">
                <span style="font-size:0.75rem;color:var(--text-light);">Автоматически генерируется из заголовка</span>
            </div>
        </div>
        
        <div class="form-group">
            <label>Содержание</label>
            <textarea name="content" rows="10"><?= $editPage ? escape($editPage['content']) : '' ?></textarea>
            <span style="font-size:0.75rem;color:var(--text-light);">Поддерживается HTML</span>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>SEO Заголовок</label>
                <input type="text" name="meta_title" placeholder="Мета-заголовок" value="<?= $editPage ? escape($editPage['meta_title']) : '' ?>">
            </div>
            <div class="form-group">
                <label>SEO Описание</label>
                <input type="text" name="meta_description" placeholder="Мета-описание" value="<?= $editPage ? escape($editPage['meta_description']) : '' ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Порядок сортировки</label>
                <input type="number" name="sort_order" value="<?= $editPage ? $editPage['sort_order'] : 0 ?>">
            </div>
            <div class="form-group" style="display:flex;align-items:center;padding-top:24px;">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="is_visible" <?= ($editPage && !$editPage['is_visible']) ? '' : 'checked' ?>>
                    Отображать на сайте
                </label>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $editPage ? 'Сохранить' : 'Добавить' ?></button>
            <?php if ($editPage): ?>
                <a href="?page=pages" class="btn btn-outline">Отмена</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Список -->
<div class="table-container">
    <div class="table-header">
        <h3>Страницы сайта</h3>
        <span style="font-size:0.85rem;color:var(--text-light);">Всего: <?= count($pages) ?></span>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Заголовок</th>
                    <th>Адрес</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($pages) > 0): ?>
                    <?php foreach ($pages as $page): ?>
                        <tr>
                            <td><strong><?= escape($page['title']) ?></strong></td>
                            <td><code><?= escape($page['slug']) ?></code></td>
                            <td><?= $page['is_visible'] ? 'Отображается' : 'Скрыта' ?></td>
                            <td>
                                <a href="?page=pages&edit=<?= $page['id'] ?>" class="btn btn-outline" style="padding:4px 12px;font-size:0.75rem;">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if (!in_array($page['slug'], ['about', 'quality', 'delivery', 'privacy'])): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить страницу?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $page['id'] ?>">
                                        <button type="submit" class="btn btn-danger" style="padding:4px 12px;font-size:0.75rem;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center;color:var(--text-light);padding:40px;">Страниц не найдено</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>