<?php
declare(strict_types=1);
require dirname(__DIR__) . '/private/content-store.php';
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
$payload = json_decode((string)file_get_contents('php://input'), true);
$path = is_array($payload) ? (string)($payload['path'] ?? '') : '';
$knownPages=['/','/index.html','/Pages/index.html','/Pages/news.html','/Pages/spotlight.html','/Pages/videos.html','/Pages/riders.html','/Pages/about.html','/Pages/privacy.html','/Pages/terms.html','/Pages/cookies.html'];
$isArticle=(bool)preg_match('~^/articles/(news|spotlight|rider)/[a-z0-9-]{1,180}(?:\.html)?$~',$path);
if (!in_array($path,$knownPages,true) && !$isArticle) { http_response_code(204); exit; }
$day = gmdate('Y-m-d');
if (($db['driver'] ?? '') === 'sqlite') {
    $sql = 'INSERT INTO rw_page_views(view_day,page_path,view_count) VALUES(?,?,1) ON CONFLICT(view_day,page_path) DO UPDATE SET view_count=view_count+1';
} else {
    $sql = 'INSERT INTO rw_page_views(view_day,page_path,view_count) VALUES(?,?,1) ON DUPLICATE KEY UPDATE view_count=view_count+1';
}
$pdo->prepare($sql)->execute([$day,$path]);
http_response_code(204);
