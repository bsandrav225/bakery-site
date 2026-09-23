<?php
// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $slug = slugify($title);
        $description = $_POST['description'] ?? '';
        $discount = trim($_POST['discount'] ?? '');
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $sort_order = intval($_POST['sort_order'] ?? 0);
        
        $image = $_POST['current_image'] ?? '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadFile($_FILES['image'], 'promotions');
            if ($upload['success']) {
                $image = $upload['filename'];
            }
        }
        
        if ($title) {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO promotions (title, slug, description, image, discount, start_date, end_date, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $slug, $description, $image, $discount, $start_date, $end_date, $is_active, $sort_order]);
                flashMessage('Акция добавлена', 'success');
            } else {
                $stmt = $pdo->prepare("UPDATE promotions SET title = ?, slug = ?, description = ?, image = ?, discount = ?, start_date = ?, end_date = ?, is_active = ?, sort_order = ? WHERE id = ?");
                $stmt->execute([$title, $slug, $description, $image, $discount, $start_date, $end_date, $is_active, $sort_order, $id]);
                flashMessage('Акция обновлена', 'success');
            }
            redirect('?page=promotions');
        } else {
            $formError = 'Заполните название акции';
        }
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM promotions WHERE id = ?");
        $stmt->execute([$id]);
        flashMessage('Акция удалена', 'success');
        redirect('?page=promotions');
    }
    
    if ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE promotions SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$id]);
        redirect('?page=promotions');
    }
}

$promotions = $pdo->query("SELECT * FROM promotions ORDER BY sort_order ASC, id DESC")->fetchAll();

$editPromo = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM promotions WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $editPromo = $stmt->fetch();
}
?>

<?php $flash = getFlashMessage(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>" style="background:<?= $flash['type'] === 'success' ? '#E8F5E9' : '#FEE' ?>;color:<?= $flash['type'] === 'success' ? '#2E7D32' : '#C0392B' ?>;padding:12px 16px;border-radius:8px;margin-bottom:20px;">
        <?= $flash['message'] ?>
    </div>
<?php endif; ?>

<!-- Форма -->
<div class="form-section">
    <h3><?= $editPromo ? 'Редактировать акцию' : 'Добавить акцию' ?></h3>
    
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?= $editPromo ? 'edit' : 'add' ?>">
        <?php if ($editPromo): ?>
            <input type="hidden" name="id" value="<?= $editPromo['id'] ?>">
            <input type="hidden" name="current_image" value="<?= $editPromo['image'] ?>">
        <?php endif; ?>
        
        <div class="form-row">
            <div class="form-group">
                <label>Название *</label>
                <input type="text" name="title" value="<?= $editPromo ? escape($editPromo['title']) : '' ?>" required>
            </div>
            <div class="form-group">
                <label>Скидка / Текст</label>
                <input type="text" name="discount" placeholder="-20% или Скидка 20%" value="<?= $editPromo ? escape($editPromo['discount']) : '' ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label>Описание</label>
            <textarea name="description" rows="3"><?= $editPromo ? escape($editPromo['description']) : '' ?></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Дата начала</label>
                <input type="date" name="start_date" value="<?= $editPromo && $editPromo['start_date'] ? $editPromo['start_date'] : '' ?>">
            </div>
            <div class="form-group">
                <label>Дата окончания</label>
                <input type="date" name="end_date" value="<?= $editPromo && $editPromo['end_date'] ? $editPromo['end_date'] : '' ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Изображение</label>
                <?php if ($editPromo && $editPromo['image']): ?>
                    <div style="margin-bottom:8px;">
                        <img src="<?= SITE_URL ?>uploads/<?= $editPromo['image'] ?>" style="max-width:150px;border-radius:8px;">
                    </div>
                <?php endif; ?>
                <input type="file" name="image" accept="image/*">
            </div>
            <div class="form-group">
                <label>Порядок сортировки</label>
                <input type="number" name="sort_order" value="<?= $editPromo ? $editPromo['sort_order'] : 0 ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                <input type="checkbox" name="is_active" <?= ($editPromo && !$editPromo['is_active']) ? '' : 'checked' ?>>
                Активна
            </label>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $editPromo ? 'Сохранить' : 'Добавить' ?></button>
            <?php if ($editPromo): ?>
                <a href="?page=promotions" class="btn btn-outline">Отмена</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Список -->
<div class="table-container">
    <div class="table-header">
        <h3>Акции</h3>
        <span style="font-size:0.85rem;color:var(--text-light);">Всего: <?= count($promotions) ?></span>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Изображение</th>
                    <th>Название</th>
                    <th>Скидка</th>
                    <th>Период</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($promotions) > 0): ?>
                    <?php foreach ($promotions as $promo): ?>
                        <tr>
                            <td>
                                <?php if ($promo['image']): ?>
                                    <img src="<?= SITE_URL ?>uploads/<?= $promo['image'] ?>" style="width:50px;height:50px;object-fit:cover;border-radius:8px;">
                                <?php else: ?>
                                    <div style="width:50px;height:50px;background:var(--bg);border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--text-light);font-size:0.7rem;">Нет</div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= escape($promo['title']) ?></strong></td>
                            <td><?= escape($promo['discount'] ?: '-') ?></td>
                            <td style="font-size:0.85rem;">
                                <?php if ($promo['start_date']): ?>
                                    <?= formatDate($promo['start_date'], 'd.m.Y') ?>
                                <?php endif; ?>
                                <?php if ($promo['end_date']): ?>
                                    — <?= formatDate($promo['end_date'], 'd.m.Y') ?>
                                <?php endif; ?>
                                <?php if (!$promo['start_date'] && !$promo['end_date']): ?>
                                    Постоянная
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= $promo['id'] ?>">
                                    <button type="submit" style="background:none;border:none;cursor:pointer;color:<?= $promo['is_active'] ? '#2E7D32' : '#C0392B' ?>;">
                                        <?= $promo['is_active'] ? 'Активна' : 'Скрыта' ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <a href="?page=promotions&edit=<?= $promo['id'] ?>" class="btn btn-outline" style="padding:4px 12px;font-size:0.75rem;">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить акцию?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $promo['id'] ?>">
                                    <button type="submit" class="btn btn-danger" style="padding:4px 12px;font-size:0.75rem;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;color:var(--text-light);padding:40px;">Акций не найдено</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>