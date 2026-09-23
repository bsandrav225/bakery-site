<?php

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $orderId = intval($_POST['order_id'] ?? 0);
        
        switch ($_POST['action']) {
            case 'update_status':
                $status = $_POST['status'] ?? 'new';
                $adminComment = trim($_POST['admin_comment'] ?? '');
                $stmt = $pdo->prepare("UPDATE orders SET status = ?, admin_comment = ? WHERE id = ?");
                $stmt->execute([$status, $adminComment, $orderId]);
                flashMessage('Статус заявки обновлен', 'success');
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
                $stmt->execute([$orderId]);
                flashMessage('Заявка удалена', 'success');
                break;
        }
        redirect('?page=orders');
    }
}

// Фильтры
$statusFilter = $_GET['status'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';

// Построение запроса
$sql = "SELECT * FROM orders WHERE 1=1";
$params = [];

if ($statusFilter) {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
}

if ($typeFilter) {
    $sql .= " AND order_type = ?";
    $params[] = $typeFilter;
}

if ($search) {
    $sql .= " AND (customer_name LIKE ? OR customer_phone LIKE ? OR customer_email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$statuses = getOrderStatuses();
$types = getOrderTypes();

// Получение одной заявки для просмотра
$viewOrder = null;
if (isset($_GET['view'])) {
    $viewOrder = getOrder(intval($_GET['view']));
}
?>

<!-- Уведомления -->
<?php $flash = getFlashMessage(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>" style="background:<?= $flash['type'] === 'success' ? '#E8F5E9' : '#FEE' ?>;color:<?= $flash['type'] === 'success' ? '#2E7D32' : '#C0392B' ?>;padding:12px 16px;border-radius:8px;margin-bottom:20px;">
        <?= $flash['message'] ?>
    </div>
<?php endif; ?>

<!-- Просмотр заявки -->
<?php if ($viewOrder): ?>
    <div class="form-section">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h3>Заявка #<?= $viewOrder['id'] ?></h3>
            <a href="?page=orders" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Назад</a>
        </div>
        
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div>
                <strong>Клиент:</strong> <?= escape($viewOrder['customer_name']) ?><br>
                <strong>Телефон:</strong> <?= escape($viewOrder['customer_phone']) ?><br>
                <?php if ($viewOrder['customer_email']): ?>
                    <strong>Email:</strong> <?= escape($viewOrder['customer_email']) ?><br>
                <?php endif; ?>
                <?php if ($viewOrder['customer_address']): ?>
                    <strong>Адрес:</strong> <?= escape($viewOrder['customer_address']) ?><br>
                <?php endif; ?>
            </div>
            <div>
                <strong>Тип заявки:</strong> <?= $types[$viewOrder['order_type']] ?? $viewOrder['order_type'] ?><br>
                <strong>Статус:</strong> <span class="status-badge status-<?= $viewOrder['status'] ?>"><?= $statuses[$viewOrder['status']] ?? $viewOrder['status'] ?></span><br>
                <strong>Дата:</strong> <?= formatDate($viewOrder['created_at']) ?><br>
            </div>
        </div>
        
        <?php if ($viewOrder['comment']): ?>
            <div style="margin-top:12px;padding:12px;background:var(--bg);border-radius:8px;">
                <strong>Комментарий клиента:</strong><br>
                <?= nl2br(escape($viewOrder['comment'])) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($viewOrder['admin_comment']): ?>
            <div style="margin-top:12px;padding:12px;background:#E3F0FF;border-radius:8px;">
                <strong>Комментарий администратора:</strong><br>
                <?= nl2br(escape($viewOrder['admin_comment'])) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" style="margin-top:20px;border-top:1px solid var(--border);padding-top:20px;">
            <input type="hidden" name="order_id" value="<?= $viewOrder['id'] ?>">
            <input type="hidden" name="action" value="update_status">
            
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label>Статус</label>
                    <select name="status">
                        <?php foreach ($statuses as $key => $label): ?>
                            <option value="<?= $key ?>" <?= $viewOrder['status'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Комментарий</label>
                    <input type="text" name="admin_comment" placeholder="Комментарий к заявке" value="<?= escape($viewOrder['admin_comment']) ?>">
                </div>
            </div>
            
            <div style="display:flex;gap:12px;margin-top:12px;">
                <button type="submit" class="btn btn-primary">Сохранить</button>
                <button type="submit" name="action" value="delete" class="btn btn-danger" onclick="return confirm('Удалить заявку?')">Удалить</button>
            </div>
        </form>
    </div>

<?php else: ?>
    <!-- Список заявок -->
    <div class="table-container">
        <div class="table-header">
            <h3>Все заявки</h3>
            <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <input type="hidden" name="page" value="orders">
                    <select name="status" onchange="this.form.submit()">
                        <option value="">Все статусы</option>
                        <?php foreach ($statuses as $key => $label): ?>
                            <option value="<?= $key ?>" <?= $statusFilter === $key ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="type" onchange="this.form.submit()">
                        <option value="">Все типы</option>
                        <?php foreach ($types as $key => $label): ?>
                            <option value="<?= $key ?>" <?= $typeFilter === $key ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="search" placeholder="Поиск..." value="<?= escape($search) ?>" style="padding:8px 14px;border:2px solid var(--border);border-radius:8px;font-size:0.9rem;">
                    <button type="submit" class="btn btn-primary" style="padding:8px 16px;font-size:0.85rem;">Найти</button>
                    <?php if ($search || $statusFilter || $typeFilter): ?>
                        <a href="?page=orders" class="btn btn-outline" style="padding:8px 16px;font-size:0.85rem;">Сбросить</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Клиент</th>
                        <th>Телефон</th>
                        <th>Тип</th>
                        <th>Статус</th>
                        <th>Дата</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($orders) > 0): ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?= $order['id'] ?></td>
                                <td><?= escape($order['customer_name']) ?></td>
                                <td><?= escape($order['customer_phone']) ?></td>
                                <td><?= $types[$order['order_type']] ?? $order['order_type'] ?></td>
                                <td>
                                    <span class="status-badge status-<?= $order['status'] ?>">
                                        <?= $statuses[$order['status']] ?? $order['status'] ?>
                                    </span>
                                </td>
                                <td><?= formatDate($order['created_at']) ?></td>
                                <td>
                                    <a href="?page=orders&view=<?= $order['id'] ?>" class="btn btn-outline" style="padding:4px 12px;font-size:0.75rem;">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center;color:var(--text-light);padding:40px;">Заявок не найдено</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div style="padding:12px 16px;border-top:1px solid var(--border);font-size:0.85rem;color:var(--text-light);">
            Всего: <?= count($orders) ?> заявок
        </div>
    </div>
<?php endif; ?>