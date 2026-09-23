<?php
// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $slug = slugify($name);
        $description = $_POST['description'] ?? '';
        $price_from = floatval($_POST['price_from'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $sort_order = intval($_POST['sort_order'] ?? 0);
        
        // Загрузка изображения
        $image = $_POST['current_image'] ?? '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadFile($_FILES['image'], 'products');
            if ($upload['success']) {
                $image = $upload['filename'];
            }
        }
        
        if ($name && $category_id) {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO products (category_id, name, slug, description, price_from, image, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$category_id, $name, $slug, $description, $price_from, $image, $is_active, $sort_order]);
                flashMessage('Товар добавлен', 'success');
            } else {
                $stmt = $pdo->prepare("UPDATE products SET category_id = ?, name = ?, slug = ?, description = ?, price_from = ?, image = ?, is_active = ?, sort_order = ? WHERE id = ?");
                $stmt->execute([$category_id, $name, $slug, $description, $price_from, $image, $is_active, $sort_order, $id]);
                flashMessage('Товар обновлен', 'success');
            }
            redirect('?page=products');
        } else {
            $formError = 'Заполните обязательные поля';
        }
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        flashMessage('Товар удален', 'success');
        redirect('?page=products');
    }
    
    if ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE products SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$id]);
        redirect('?page=products');
    }
}

// Получение данных
$categories = getCategories(0);
$products = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.sort_order ASC, p.id DESC")->fetchAll();

// Редактирование
$editProduct = null;
if (isset($_GET['edit'])) {
    $editProduct = getProduct(intval($_GET['edit']));
}
?>

<?php $flash = getFlashMessage(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>" style="background:<?= $flash['type'] === 'success' ? '#E8F5E9' : '#FEE' ?>;color:<?= $flash['type'] === 'success' ? '#2E7D32' : '#C0392B' ?>;padding:12px 16px;border-radius:8px;margin-bottom:20px;">
        <?= $flash['message'] ?>
    </div>
<?php endif; ?>

<!-- Форма добавления/редактирования -->
<div class="form-section">
    <h3><?= $editProduct ? 'Редактировать товар' : 'Добавить товар' ?></h3>
    
    <?php if (isset($formError)): ?>
        <div class="alert alert-danger" style="background:#FEE;color:#C0392B;padding:12px 16px;border-radius:8px;margin-bottom:16px;">
            <?= $formError ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?= $editProduct ? 'edit' : 'add' ?>">
        <?php if ($editProduct): ?>
            <input type="hidden" name="id" value="<?= $editProduct['id'] ?>">
            <input type="hidden" name="current_image" value="<?= $editProduct['image'] ?>">
        <?php endif; ?>
        
        <div class="form-row">
            <div class="form-group">
                <label>Категория *</label>
                <select name="category_id" required>
                    <option value="">Выберите категорию</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($editProduct && $editProduct['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                            <?= escape($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Название *</label>
                <input type="text" name="name" value="<?= $editProduct ? escape($editProduct['name']) : '' ?>" required>
            </div>
        </div>
        
        <div class="form-group">
            <label>Описание</label>
            <textarea name="description" rows="3"><?= $editProduct ? escape($editProduct['description']) : '' ?></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Цена "от" (₽)</label>
                <input type="number" name="price_from" step="0.01" value="<?= $editProduct ? $editProduct['price_from'] : '' ?>">
            </div>
            <div class="form-group">
                <label>Порядок сортировки</label>
                <input type="number" name="sort_order" value="<?= $editProduct ? $editProduct['sort_order'] : 0 ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label>Изображение</label>
            <?php if ($editProduct && $editProduct['image']): ?>
                <div style="margin-bottom:8px;">
                    <img src="<?= SITE_URL ?>uploads/<?= $editProduct['image'] ?>" style="max-width:150px;border-radius:8px;">
                </div>
            <?php endif; ?>
            <input type="file" name="image" accept="image/*">
        </div>
        
        <div class="form-group">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                <input type="checkbox" name="is_active" <?= ($editProduct && !$editProduct['is_active']) ? '' : 'checked' ?>>
                Активен
            </label>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $editProduct ? 'Сохранить' : 'Добавить' ?></button>
            <?php if ($editProduct): ?>
                <a href="?page=products" class="btn btn-outline">Отмена</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Список товаров -->
<div class="table-container">
    <div class="table-header">
        <h3>Список товаров</h3>
        <span style="font-size:0.85rem;color:var(--text-light);">Всего: <?= count($products) ?></span>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Изображение</th>
                    <th>Название</th>
                    <th>Категория</th>
                    <th>Цена</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <?php if ($product['image']): ?>
                                    <img src="<?= SITE_URL ?>uploads/<?= $product['image'] ?>" style="width:50px;height:50px;object-fit:cover;border-radius:8px;">
                                <?php else: ?>
                                    <div style="width:50px;height:50px;background:var(--bg);border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--text-light);font-size:0.7rem;">Нет</div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= escape($product['name']) ?></strong></td>
                            <td><?= escape($product['category_name'] ?? '-') ?></td>
                            <td><?= $product['price_from'] ? number_format($product['price_from'], 0, '', ' ') . ' ₽' : '-' ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                    <button type="submit" style="background:none;border:none;cursor:pointer;color:<?= $product['is_active'] ? '#2E7D32' : '#C0392B' ?>;">
                                        <?= $product['is_active'] ? 'Активен' : 'Скрыт' ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <a href="?page=products&edit=<?= $product['id'] ?>" class="btn btn-outline" style="padding:4px 12px;font-size:0.75rem;">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить товар?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                    <button type="submit" class="btn btn-danger" style="padding:4px 12px;font-size:0.75rem;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;color:var(--text-light);padding:40px;">Товаров не найдено</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>