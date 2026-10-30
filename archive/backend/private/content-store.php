<?php
declare(strict_types=1);
// Public content and first-party aggregate statistics share the configured database
// without opening a staff session on public page requests.
$configPath = __DIR__ . '/config.php';
if (!is_file($configPath)) { http_response_code(503); exit('RideWire content is not configured.'); }
$config = require $configPath;
$db = $config['db'] ?? [];
$driver = $db['driver'] ?? 'mysql';
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
function rw_sanitize_html(string $html): string {
    $allowed=['p','h2','h3','ul','ol','li','strong','em','b','i','blockquote','figure','figcaption','img','a','br','hr','iframe'];
    $dom=new DOMDocument('1.0','UTF-8');libxml_use_internal_errors(true);$dom->loadHTML('<?xml encoding="utf-8"?><div id="root">'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);libxml_clear_errors();$root=$dom->getElementById('root');if(!$root)return '';
    $walk=function(DOMNode $node)use(&$walk,$allowed):void{for($child=$node->firstChild;$child;){$next=$child->nextSibling;if($child instanceof DOMElement){$tag=strtolower($child->tagName);if(!in_array($tag,$allowed,true)){$parent=$child->parentNode;while($child->firstChild)$parent->insertBefore($child->firstChild,$child);$parent->removeChild($child);}else{$keep=$tag==='a'?['href','target','rel']:($tag==='img'?['src','alt','loading']:($tag==='iframe'?['src','title','allow','allowfullscreen','loading']:[]));foreach(iterator_to_array($child->attributes) as $attr)if(!in_array(strtolower($attr->name),$keep,true))$child->removeAttributeNode($attr);$url=$child->getAttribute('href')?:$child->getAttribute('src');if($url!==''&&!preg_match('~^(https://|/|\.\./|assets/)~i',$url)){$child->removeAttribute('href');$child->removeAttribute('src');}if($tag==='iframe'&&!preg_match('~^https://(www\.)?(youtube(-nocookie)?\.com|youtu\.be|vimeo\.com|pinkbike\.com)/~i',$child->getAttribute('src')))$child->parentNode->removeChild($child);else{$walk($child);if($tag==='a')$child->setAttribute('rel','noopener noreferrer');}}}$child=$next;}};$walk($root);$out='';foreach($root->childNodes as $node)$out.=$dom->saveHTML($node);return $out;
}
if ($driver === 'sqlite') {
    $path = $db['path'];
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0700, true);
    $pdo = new PDO('sqlite:' . $path, null, null, $options);
    $pdo->exec("CREATE TABLE IF NOT EXISTS rw_content_items(id INTEGER PRIMARY KEY AUTOINCREMENT,section TEXT NOT NULL,slug TEXT NOT NULL UNIQUE,title TEXT NOT NULL,category TEXT NOT NULL DEFAULT '',summary TEXT NOT NULL DEFAULT '',body_html TEXT NOT NULL DEFAULT '',image TEXT NOT NULL DEFAULT '',image_alt TEXT NOT NULL DEFAULT '',image_caption TEXT NOT NULL DEFAULT '',country TEXT NOT NULL DEFAULT '',discipline TEXT NOT NULL DEFAULT '',highlight TEXT NOT NULL DEFAULT '',source_url TEXT NOT NULL DEFAULT '',published_at TEXT NOT NULL DEFAULT '',status TEXT NOT NULL DEFAULT 'draft',sort_order INTEGER NOT NULL DEFAULT 0,created_at TEXT NOT NULL,updated_at TEXT NOT NULL)");
    $pdo->exec('CREATE INDEX IF NOT EXISTS ix_rw_content_section_status ON rw_content_items(section,status,sort_order)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS rw_page_views(view_day TEXT NOT NULL,page_path TEXT NOT NULL,view_count INTEGER NOT NULL DEFAULT 0,PRIMARY KEY(view_day,page_path))');
    $nowSql = 'CURRENT_TIMESTAMP';
} elseif ($driver === 'mysql') {
    $pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['name'] . ';charset=utf8mb4', $db['user'], $db['password'], $options + [PDO::ATTR_EMULATE_PREPARES => false]);
    $nowSql = 'UTC_TIMESTAMP()';
} else { http_response_code(503); exit('Unsupported database driver.'); }
