<?php
// Получение статистики
$stats = [
    'orders' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'new_orders' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'new'")->fetchColumn(),
    'products' => $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn(),
    'portfolio' => $pdo->query("SELECT COUNT(*) FROM portfolio WHERE is_active = 1")->fetchColumn(),
    'reviews' => $pdo->query("SELECT COUNT(*) FROM reviews WHERE is_approved = 0")->fetchColumn(),
    'articles' => $pdo->query("SELECT COUNT(*) FROM articles WHERE is_published = 1")->fetchColumn(),
    'promotions' => $pdo->query("SELECT COUNT(*) FROM promotions WHERE is_active = 1 AND (end_date IS NULL OR end_date >= CURDATE())")->fetchColumn(),
];

// Получение последних заявок
$recentOrders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Получение последних отзывов
$recentReviews = $pdo->query("SELECT * FROM reviews ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Активные акции
$activePromotions = $pdo->query("SELECT * FROM promotions WHERE is_active = 1 AND (end_date IS NULL OR end_date >= CURDATE()) LIMIT 3")->fetchAll();

// Статистика по месяцам
$monthlyStats = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM orders GROUP BY month ORDER BY month DESC LIMIT 12")->fetchAll();
?>

<!-- Статистика -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-list-check"></i></div>
        <div class="stat-number"><?= $stats['orders'] ?></div>
        <div class="stat-label">Всего заявок</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#E74C3C;"><i class="fas fa-bell"></i></div>
        <div class="stat-number"><?= $stats['new_orders'] ?></div>
        <div class="stat-label">Новых заявок</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#2E7DB5;"><i class="fas fa-boxes-stacked"></i></div>
        <div class="stat-number"><?= $stats['products'] ?></div>
        <div class="stat-label">Товаров в каталоге</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#D4872B;"><i class="fas fa-images"></i></div>
        <div class="stat-number"><?= $stats['portfolio'] ?></div>
        <div class="stat-label">Работ в портфолио</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#F39C12;"><i class="fas fa-star"></i></div>
        <div class="stat-number"><?= $stats['reviews'] ?></div>
        <div class="stat-label">Отзывов на модерации</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#2E7D32;"><i class="fas fa-newspaper"></i></div>
        <div class="stat-number"><?= $stats['articles'] ?></div>
        <div class="stat-label">Опубликованных статей</div>
    </div>
</div>

<div class="dashboard-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
    <!-- Последние заявки -->
    <div class="table-container">
        <div class="table-header">
            <h3><i class="fas fa-clock" style="margin-right:8px;"></i>Последние заявки</h3>
            <a href="?page=orders" class="btn btn-outline" style="padding:6px 16px;font-size:0.8rem;">Все заявки →</a>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Клиент</th>
                        <th>Тип</th>
                        <th>Статус</th>
                        <th>Дата</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recentOrders) > 0): ?>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>#<?= $order['id'] ?></td>
                                <td><?= escape($order['customer_name']) ?></td>
                                <td><?= getOrderTypes()[$order['order_type']] ?? $order['order_type'] ?></td>
                                <td>
                                    <span class="status-badge status-<?= $order['status'] ?>">
                                        <?= getOrderStatuses()[$order['status']] ?? $order['status'] ?>
                                    </span>
                                </td>
                                <td><?= formatDate($order['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center;color:var(--text-light);padding:24px;">Нет заявок</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Последние отзывы -->
    <div class="table-container">
        <div class="table-header">
            <h3><i class="fas fa-star" style="margin-right:8px;"></i>Новые отзывы</h3>
            <a href="?page=reviews" class="btn btn-outline" style="padding:6px 16px;font-size:0.8rem;">Все отзывы →</a>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Клиент</th>
                        <th>Оценка</th>
                        <th>Статус</th>
                        <th>Дата</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recentReviews) > 0): ?>
                        <?php foreach ($recentReviews as $review): ?>
                            <tr>
                                <td><?= escape($review['customer_name']) ?></td>
                                <td>
                                    <?= str_repeat('⭐', $review['rating']) ?>
                                </td>
                                <td>
                                    <?php if ($review['is_approved']): ?>
                                        <span style="color:#2E7D32;">✓ Одобрен</span>
                                    <?php else: ?>
                                        <span style="color:#F39C12;">⏳ На модерации</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= formatDate($review['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center;color:var(--text-light);padding:24px;">Нет отзывов</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Активные акции -->
<?php if (count($activePromotions) > 0): ?>
    <div class="table-container" style="margin-top:24px;">
        <div class="table-header">
            <h3><i class="fas fa-percent" style="margin-right:8px;"></i>Активные акции</h3>
            <a href="?page=promotions" class="btn btn-outline" style="padding:6px 16px;font-size:0.8rem;">Все акции →</a>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;padding:16px;">
            <?php foreach ($activePromotions as $promo): ?>
                <div style="background:var(--bg);padding:16px;border-radius:8px;border-left:3px solid #E74C3C;">
                    <strong><?= escape($promo['title']) ?></strong>
                    <div style="font-size:0.85rem;color:var(--text-light);margin-top:4px;">
                        <?= $promo['discount'] ?: 'Скидка' ?>
                        <?php if ($promo['end_date']): ?>
                            <br>до <?= formatDate($promo['end_date'], 'd.m.Y') ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- График заявок -->
<?php if (count($monthlyStats) > 0): ?>
    <div class="table-container" style="margin-top:24px;">
        <div class="table-header">
            <h3><i class="fas fa-chart-line" style="margin-right:8px;"></i>Динамика заявок</h3>
        </div>
        <div style="padding:20px;">
            <div style="display:flex;align-items:flex-end;gap:12px;height:150px;padding-top:10px;">
                <?php 
                $maxCount = max(array_column($monthlyStats, 'count')) ?: 1;
                foreach (array_reverse($monthlyStats) as $stat): 
                    $height = ($stat['count'] / $maxCount) * 120;
                ?>
                    <div style="flex:1;display:flex;flex-direction:column;align-items:center;">
                        <div style="height:<?= $height ?>px;width:100%;max-width:40px;background:var(--primary-light);border-radius:4px 4px 0 0;min-height:4px;"></div>
                        <div style="font-size:0.65rem;color:var(--text-light);margin-top:4px;text-align:center;">
                            <?= date('m.Y', strtotime($stat['month'] . '-01')) ?>
                        </div>
                        <div style="font-size:0.7rem;font-weight:600;"><?= $stat['count'] ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
.dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}
@media (max-width: 992px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}
</style>