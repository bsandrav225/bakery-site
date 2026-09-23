<?php
// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'approve' || $action === 'unapprove') {
        $id = intval($_POST['id'] ?? 0);
        $approved = $action === 'approve' ? 1 : 0;
        $stmt = $pdo->prepare("UPDATE reviews SET is_approved = ?, approved_at = NOW() WHERE id = ?");
        $stmt->execute([$approved, $id]);
        flashMessage($approved ? 'Отзыв одобрен' : 'Отзыв скрыт', 'success');
        redirect('?page=reviews');
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$id]);
        flashMessage('Отзыв удален', 'success');
        redirect('?page=reviews');
    }
}

$filter = $_GET['filter'] ?? 'all';
$sql = "SELECT * FROM reviews";
if ($filter === 'pending') {
    $sql .= " WHERE is_approved = 0";
} elseif ($filter === 'approved') {
    $sql .= " WHERE is_approved = 1";
}
$sql .= " ORDER BY created_at DESC";
$reviews = $pdo->query($sql)->fetchAll();
?>

<?php $flash = getFlashMessage(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>" style="background:<?= $flash['type'] === 'success' ? '#E8F5E9' : '#FEE' ?>;color:<?= $flash['type'] === 'success' ? '#2E7D32' : '#C0392B' ?>;padding:12px 16px;border-radius:8px;margin-bottom:20px;">
        <?= $flash['message'] ?>
    </div>
<?php endif; ?>

<div class="toolbar">
    <a href="?page=reviews&filter=all" class="btn <?= $filter === 'all' ? 'btn-primary' : 'btn-outline' ?>">Все</a>
    <a href="?page=reviews&filter=pending" class="btn <?= $filter === 'pending' ? 'btn-primary' : 'btn-outline' ?>">
        На модерации <span class="badge" style="background:#F39C12;color:white;padding:0 8px;border-radius:10px;font-size:0.7rem;">
            <?= $pdo->query("SELECT COUNT(*) FROM reviews WHERE is_approved = 0")->fetchColumn() ?>
        </span>
    </a>
    <a href="?page=reviews&filter=approved" class="btn <?= $filter === 'approved' ? 'btn-primary' : 'btn-outline' ?>">Одобренные</a>
</div>

<div class="table-container">
    <div class="table-header">
        <h3>Отзывы</h3>
        <span style="font-size:0.85rem;color:var(--text-light);">Всего: <?= count($reviews) ?></span>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Клиент</th>
                    <th>Оценка</th>
                    <th>Отзыв</th>
                    <th>Дата</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($reviews) > 0): ?>
                    <?php foreach ($reviews as $review): ?>
                        <tr>
                            <td><strong><?= escape($review['customer_name']) ?></strong></td>
                            <td><?= str_repeat('⭐', $review['rating']) ?></td>
                            <td style="max-width:300px;font-size:0.9rem;"><?= mb_substr(escape($review['text']), 0, 100) . (mb_strlen($review['text']) > 100 ? '...' : '') ?></td>
                            <td><?= formatDate($review['created_at']) ?></td>
                            <td>
                                <?php if ($review['is_approved']): ?>
                                    <span style="color:#2E7D32;">Одобрен</span>
                                <?php else: ?>
                                    <span style="color:#F39C12;">⏳ На модерации</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$review['is_approved']): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="id" value="<?= $review['id'] ?>">
                                        <button type="submit" class="btn btn-success" style="padding:4px 12px;font-size:0.75rem;">
                                            <i class="fas fa-check"></i> Одобрить
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="unapprove">
                                        <input type="hidden" name="id" value="<?= $review['id'] ?>">
                                        <button type="submit" class="btn btn-warning" style="padding:4px 12px;font-size:0.75rem;">
                                            <i class="fas fa-eye-slash"></i> Скрыть
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить отзыв?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $review['id'] ?>">
                                    <button type="submit" class="btn btn-danger" style="padding:4px 12px;font-size:0.75rem;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;color:var(--text-light);padding:40px;">Отзывов не найдено</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>