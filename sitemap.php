<?php
header('Content-Type: application/xml; charset=utf-8');

require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/admin/functions.php';

$base = rtrim(SITE_URL, '/');
$today = date('Y-m-d');

$urls = [
    [
        'loc'        => $base . '/',
        'lastmod'    => $today,
        'changefreq' => 'daily',
        'priority'   => '1.0',
    ],
];

try {
    $pages = $pdo->query("SELECT slug, updated_at FROM pages WHERE is_visible = 1 ORDER BY sort_order ASC")->fetchAll();
    foreach ($pages as $page) {
        $urls[] = [
            'loc'        => $base . '/page.php?slug=' . rawurlencode($page['slug']),
            'lastmod'    => !empty($page['updated_at']) ? date('Y-m-d', strtotime($page['updated_at'])) : $today,
            'changefreq' => 'monthly',
            'priority'   => '0.7',
        ];
    }
} catch (Throwable $e) {
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $u): ?>
  <url>
    <loc><?= htmlspecialchars($u['loc'], ENT_XML1) ?></loc>
    <lastmod><?= htmlspecialchars($u['lastmod'], ENT_XML1) ?></lastmod>
    <changefreq><?= htmlspecialchars($u['changefreq'], ENT_XML1) ?></changefreq>
    <priority><?= htmlspecialchars($u['priority'], ENT_XML1) ?></priority>
  </url>
<?php endforeach; ?>
</urlset>
