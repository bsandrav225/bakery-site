<?php
// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $slug = slugify($title);
        $excerpt = trim($_POST['excerpt'] ?? '');
        $content = $_POST['content'] ?? '';
        $meta_title = trim($_POST['meta_title'] ?? '');
        $meta_description = trim($_POST['meta_description'] ?? '');
        $is_published = isset($_POST['is_published']) ? 1 : 0;
        $published_at = $_POST['published_at'] ?? date('Y-m-d H:i:s');
        
        $image = $_POST['current_image'] ?? '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadFile($_FILES['image'], 'articles');
            if ($upload['success']) {
                $image = $upload['filename'];
            }
        }
        
        if ($title) {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO articles (title, slug, excerpt, content, image, meta_title, meta_description, is_published, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $slug, $excerpt, $content, $image, $meta_title, $meta_description, $is_published, $published_at]);
                flashMessage('Статья добавлена', 'success');
            } else {
                $stmt = $pdo->prepare("UPDATE articles SET title = ?, slug = ?, excerpt = ?, content = ?, image = ?, meta_title = ?, meta_description = ?, is_published = ?, published_at = ? WHERE id = ?");
                $stmt->execute([$title, $slug, $excerpt, $content, $image, $meta_title, $meta_description, $is_published, $published_at, $id]);
                flashMessage('Статья обновлена', 'success');
            }
            redirect('?page=articles');
        } else {
            $formError = 'Заполните название статьи';
        }
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM articles WHERE id = ?");
        $stmt->execute([$id]);
        flashMessage('Статья удалена', 'success');
        redirect('?page=articles');
    }
    
    if ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE articles SET is_published = NOT is_published WHERE id = ?");
        $stmt->execute([$id]);
        redirect('?page=articles');
    }
}

$articles = $pdo->query("SELECT * FROM articles ORDER BY published_at DESC")->fetchAll();

$editArticle = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $editArticle = $stmt->fetch();
}
?>

<?php $flash = getFlashMessage(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>" style="background:<?= $flash['type'] === 'success' ? '#E8F5E9' : '#FEE' ?>;color:<?= $flash['type'] === 'success' ? '#2E7D32' : '#C0392B' ?>;padding:12px 16px;border-radius:8px;margin-bottom:20px;">
        <?= $flash['message'] ?>
    </div>
<?php endif; ?>

<!-- Форма -->
<div class="form-section">
    <h3><?= $editArticle ? 'Редактировать статью' : 'Добавить статью' ?></h3>
    
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?= $editArticle ? 'edit' : 'add' ?>">
        <?php if ($editArticle): ?>
            <input type="hidden" name="id" value="<?= $editArticle['id'] ?>">
            <input type="hidden" name="current_image" value="<?= $editArticle['image'] ?>">
        <?php endif; ?>
        
        <div class="form-group">
            <label>Заголовок *</label>
            <input type="text" name="title" value="<?= $editArticle ? escape($editArticle['title']) : '' ?>" required>
        </div>
        
        <div class="form-group">
            <label>Краткое описание</label>
            <input type="text" name="excerpt" placeholder="Краткий анонс статьи" value="<?= $editArticle ? escape($editArticle['excerpt']) : '' ?>">
        </div>
        
        <div class="form-group">
            <label>Содержание</label>
            <textarea name="content" rows="10"><?= $editArticle ? escape($editArticle['content']) : '' ?></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Изображение</label>
                <?php if ($editArticle && $editArticle['image']): ?>
                    <div style="margin-bottom:8px;">
                        <img src="<?= SITE_URL ?>uploads/<?= $editArticle['image'] ?>" style="max-width:150px;border-radius:8px;">
                    </div>
                <?php endif; ?>
                <input type="file" name="image" accept="image/*">
            </div>
            <div class="form-group">
                <label>Дата публикации</label>
                <input type="datetime-local" name="published_at" value="<?= $editArticle ? date('Y-m-d\TH:i', strtotime($editArticle['published_at'])) : date('Y-m-d\TH:i') ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>SEO Заголовок</label>
                <input type="text" name="meta_title" placeholder="Мета-заголовок" value="<?= $editArticle ? escape($editArticle['meta_title']) : '' ?>">
            </div>
            <div class="form-group">
                <label>SEO Описание</label>
                <input type="text" name="meta_description" placeholder="Мета-описание" value="<?= $editArticle ? escape($editArticle['meta_description']) : '' ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                <input type="checkbox" name="is_published" <?= ($editArticle && !$editArticle['is_published']) ? '' : 'checked' ?>>
                Опубликована
            </label>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $editArticle ? 'Сохранить' : 'Добавить' ?></button>
            <?php if ($editArticle): ?>
                <a href="?page=articles" class="btn btn-outline">Отмена</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Список -->
<div class="table-container">
    <div class="table-header">
        <h3>Статьи</h3>
        <span style="font-size:0.85rem;color:var(--text-light);">Всего: <?= count($articles) ?></span>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Изображение</th>
                    <th>Заголовок</th>
                    <th>Статус</th>
                    <th>Дата</th>
                    <th>Просмотры</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($articles) > 0): ?>
                    <?php foreach ($articles as $article): ?>
                        <tr>
                            <td>
                                <?php if ($article['image']): ?>
                                    <img src="<?= SITE_URL ?>uploads/<?= $article['image'] ?>" style="width:50px;height:50px;object-fit:cover;border-radius:8px;">
                                <?php else: ?>
                                    <div style="width:50px;height:50px;background:var(--bg);border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--text-light);font-size:0.7rem;">Нет</div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= escape($article['title']) ?></strong></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= $article['id'] ?>">
                                    <button type="submit" style="background:none;border:none;cursor:pointer;color:<?= $article['is_published'] ? '#2E7D32' : '#C0392B' ?>;">
                                        <?= $article['is_published'] ? 'Опубликована' : 'Черновик' ?>
                                    </button>
                                </form>
                            </td>
                            <td><?= $article['published_at'] ? formatDate($article['published_at']) : '-' ?></td>
                            <td><?= $article['views'] ?? 0 ?></td>
                            <td>
                                <a href="?page=articles&edit=<?= $article['id'] ?>" class="btn btn-outline" style="padding:4px 12px;font-size:0.75rem;">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить статью?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $article['id'] ?>">
                                    <button type="submit" class="btn btn-danger" style="padding:4px 12px;font-size:0.75rem;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;color:var(--text-light);padding:40px;">Статей не найдено</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>