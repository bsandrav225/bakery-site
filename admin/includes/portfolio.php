<?php

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $slug = slugify($title);
        $description = $_POST['description'] ?? '';
        $category = $_POST['category'] ?? '';
        $material_id = intval($_POST['material_id'] ?? 0);
        $price_approx = floatval($_POST['price_approx'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $sort_order = intval($_POST['sort_order'] ?? 0);
        
        $image = $_POST['current_image'] ?? '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadFile($_FILES['image'], 'portfolio');
            if ($upload['success']) {
                $image = $upload['filename'];
            }
        }
        
        if ($title) {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO portfolio (title, slug, description, category, material_id, image, price_approx, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $slug, $description, $category, $material_id, $image, $price_approx, $is_active, $sort_order]);
                flashMessage('Работа добавлена', 'success');
            } else {
                $stmt = $pdo->prepare("UPDATE portfolio SET title = ?, slug = ?, description = ?, category = ?, material_id = ?, image = ?, price_approx = ?, is_active = ?, sort_order = ? WHERE id = ?");
                $stmt->execute([$title, $slug, $description, $category, $material_id, $image, $price_approx, $is_active, $sort_order, $id]);
                flashMessage('Работа обновлена', 'success');
            }
            redirect('?page=portfolio');
        } else {
            $formError = 'Заполните название';
        }
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM portfolio WHERE id = ?");
        $stmt->execute([$id]);
        flashMessage('Работа удалена', 'success');
        redirect('?page=portfolio');
    }
}

$portfolio = $pdo->query("SELECT * FROM portfolio ORDER BY sort_order ASC, id DESC")->fetchAll();

$editPortfolio = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM portfolio WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $editPortfolio = $stmt->fetch();
}
?>

<?php $flash = getFlashMessage(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>" style="background:<?= $flash['type'] === 'success' ? '#E8F5E9' : '#FEE' ?>;color:<?= $flash['type'] === 'success' ? '#2E7D32' : '#C0392B' ?>;padding:12px 16px;border-radius:8px;margin-bottom:20px;">
        <?= $flash['message'] ?>
    </div>
<?php endif; ?>

<!-- Форма -->
<div class="form-section">
    <h3><?= $editPortfolio ? 'Редактировать работу' : 'Добавить работу' ?></h3>
    
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?= $editPortfolio ? 'edit' : 'add' ?>">
        <?php if ($editPortfolio): ?>
            <input type="hidden" name="id" value="<?= $editPortfolio['id'] ?>">
            <input type="hidden" name="current_image" value="<?= $editPortfolio['image'] ?>">
        <?php endif; ?>
        
        <div class="form-row">
            <div class="form-group">
                <label>Название *</label>
                <input type="text" name="title" value="<?= $editPortfolio ? escape($editPortfolio['title']) : '' ?>" required>
            </div>
            <div class="form-group">
                <label>Категория</label>
                <input type="text" name="category" placeholder="Хлеб, торт, сдоба" value="<?= $editPortfolio ? escape($editPortfolio['category']) : '' ?>">
            </div>
        </div>
        
        <input type="hidden" name="material_id" value="<?= $editPortfolio ? (int)$editPortfolio['material_id'] : 0 ?>">
        <div class="form-group">
            <label>Стоимость (₽)</label>
            <input type="number" name="price_approx" step="0.01" value="<?= $editPortfolio ? $editPortfolio['price_approx'] : '' ?>">
        </div>
        
        <div class="form-group">
            <label>Описание</label>
            <textarea name="description" rows="3"><?= $editPortfolio ? escape($editPortfolio['description']) : '' ?></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Изображение</label>
                <?php if ($editPortfolio && $editPortfolio['image']): ?>
                    <div style="margin-bottom:8px;">
                        <img src="<?= SITE_URL ?>uploads/<?= $editPortfolio['image'] ?>" style="max-width:150px;border-radius:8px;">
                    </div>
                <?php endif; ?>
                <input type="file" name="image" accept="image/*">
            </div>
            <div class="form-group">
                <label>Порядок сортировки</label>
                <input type="number" name="sort_order" value="<?= $editPortfolio ? $editPortfolio['sort_order'] : 0 ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                <input type="checkbox" name="is_active" <?= ($editPortfolio && !$editPortfolio['is_active']) ? '' : 'checked' ?>>
                Активен
            </label>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $editPortfolio ? 'Сохранить' : 'Добавить' ?></button>
            <?php if ($editPortfolio): ?>
                <a href="?page=portfolio" class="btn btn-outline">Отмена</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Список -->
<div class="table-container">
    <div class="table-header">
        <h3>Портфолио</h3>
        <span style="font-size:0.85rem;color:var(--text-light);">Всего: <?= count($portfolio) ?></span>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Изображение</th>
                    <th>Название</th>
                    <th>Категория</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($portfolio) > 0): ?>
                    <?php foreach ($portfolio as $item): ?>
                        <tr>
                            <td>
                                <?php if ($item['image']): ?>
                                    <img src="<?= SITE_URL ?>uploads/<?= $item['image'] ?>" style="width:50px;height:50px;object-fit:cover;border-radius:8px;">
                                <?php else: ?>
                                    <div style="width:50px;height:50px;background:var(--bg);border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--text-light);font-size:0.7rem;">Нет</div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= escape($item['title']) ?></strong></td>
                            <td><?= escape($item['category'] ?? '-') ?></td>
                            <td><?= $item['is_active'] ? 'Активен' : 'Скрыт' ?></td>
                            <td>
                                <a href="?page=portfolio&edit=<?= $item['id'] ?>" class="btn btn-outline" style="padding:4px 12px;font-size:0.75rem;">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить работу?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                    <button type="submit" class="btn btn-danger" style="padding:4px 12px;font-size:0.75rem;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center;color:var(--text-light);padding:40px;">Работ не найдено</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>