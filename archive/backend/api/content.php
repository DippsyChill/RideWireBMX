<?php
declare(strict_types=1);
require dirname(__DIR__) . '/private/content-store.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$section = (string)($_GET['section'] ?? '');
if (!in_array($section, ['news','spotlight','rider'], true)) { http_response_code(400); echo '{"error":"Invalid section"}'; exit; }
$q = $pdo->prepare("SELECT id,section,slug,title,category,summary,body_html,image,image_alt,image_caption,country,discipline,highlight,source_url,published_at,status,sort_order FROM rw_content_items WHERE section=? AND status='published' ORDER BY sort_order,published_at DESC,id DESC");
$q->execute([$section]);
echo json_encode($q->fetchAll(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
