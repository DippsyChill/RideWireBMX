<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
$configPath = dirname(__DIR__) . '/private/config.php';
if (!is_file($configPath)) { http_response_code(503); exit('Staff portal is not configured yet.'); }
$config = require $configPath;
if (!is_array($config) || empty($config['db']) || empty($config['security_key'])) { http_response_code(503); exit('Staff portal configuration is incomplete.'); }
$dbConfig = $config['db'];
$driver = $dbConfig['driver'] ?? 'mysql';
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
if ($driver === 'sqlite') {
    $path = $dbConfig['path'];
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0700, true);
    $pdo = new PDO('sqlite:' . $path, null, null, $options);
    $pdo->exec('PRAGMA foreign_keys=ON');
    $pdo->exec('CREATE TABLE IF NOT EXISTS rw_staff_users(id INTEGER PRIMARY KEY AUTOINCREMENT,email TEXT NOT NULL UNIQUE,display_name TEXT NOT NULL,password_hash TEXT NOT NULL,role TEXT NOT NULL,permissions TEXT NOT NULL,is_active INTEGER NOT NULL DEFAULT 1,must_change_password INTEGER NOT NULL DEFAULT 1,created_at TEXT NOT NULL,last_login_at TEXT NULL)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS rw_staff_audit(id INTEGER PRIMARY KEY AUTOINCREMENT,actor_id INTEGER NULL,action TEXT NOT NULL,target_id INTEGER NULL,created_at TEXT NOT NULL)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS rw_staff_login_limits(fingerprint TEXT PRIMARY KEY,window_started INTEGER NOT NULL,failures INTEGER NOT NULL DEFAULT 0)');
    $pdo->exec("CREATE TABLE IF NOT EXISTS rw_content_items(id INTEGER PRIMARY KEY AUTOINCREMENT,section TEXT NOT NULL,slug TEXT NOT NULL UNIQUE,title TEXT NOT NULL,category TEXT NOT NULL DEFAULT '',summary TEXT NOT NULL DEFAULT '',body_html TEXT NOT NULL DEFAULT '',image TEXT NOT NULL DEFAULT '',image_alt TEXT NOT NULL DEFAULT '',image_caption TEXT NOT NULL DEFAULT '',country TEXT NOT NULL DEFAULT '',discipline TEXT NOT NULL DEFAULT '',highlight TEXT NOT NULL DEFAULT '',source_url TEXT NOT NULL DEFAULT '',published_at TEXT NOT NULL DEFAULT '',status TEXT NOT NULL DEFAULT 'draft',sort_order INTEGER NOT NULL DEFAULT 0,created_at TEXT NOT NULL,updated_at TEXT NOT NULL)");
    $pdo->exec('CREATE INDEX IF NOT EXISTS ix_rw_content_section_status ON rw_content_items(section,status,sort_order)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS rw_page_views(view_day TEXT NOT NULL,page_path TEXT NOT NULL,view_count INTEGER NOT NULL DEFAULT 0,PRIMARY KEY(view_day,page_path))');
    $nowSql = 'CURRENT_TIMESTAMP';
} elseif ($driver === 'mysql') {
    $pdo = new PDO('mysql:host=' . $dbConfig['host'] . ';dbname=' . $dbConfig['name'] . ';charset=utf8mb4', $dbConfig['user'], $dbConfig['password'], $options + [PDO::ATTR_EMULATE_PREPARES => false]);
    $nowSql = 'UTC_TIMESTAMP()';
} else { http_response_code(503); exit('Unsupported database driver.'); }

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name($config['session_name'] ?? 'ridewire_staff');
    $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params(['lifetime'=>0, 'path'=>'/', 'secure'=>$https, 'httponly'=>true, 'samesite'=>'Lax']);
    session_start();
}
const STAFF_CAPS = [
 'news.edit'=>'Create and edit News','news.publish'=>'Publish News','news.delete'=>'Archive News',
 'spotlight.edit'=>'Create and edit Spotlight','spotlight.publish'=>'Publish Spotlight','spotlight.delete'=>'Archive Spotlight',
 'videos.edit'=>'Add and edit Videos','videos.publish'=>'Feature Videos','videos.delete'=>'Remove Videos',
 'riders.edit'=>'Create and edit Riders','riders.publish'=>'Publish Riders','riders.delete'=>'Archive Riders',
 'site.stats.view'=>'View site statistics','staff.manage'=>'Manage staff accounts'
];
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return '<input type="hidden" name="csrf" value="'.h($_SESSION['csrf']).'">'; }
function check_csrf(): void { if (!is_string($_POST['csrf']??null) || !hash_equals($_SESSION['csrf']??'', $_POST['csrf'])) { http_response_code(403); exit('Form expired. Please try again.'); } }
function staff_user(): ?array { global $pdo; if (empty($_SESSION['staff_id'])) return null; $q=$pdo->prepare('SELECT * FROM rw_staff_users WHERE id=?'); $q->execute([(int)$_SESSION['staff_id']]); $u=$q->fetch(); if (!$u || !(int)$u['is_active']) { $_SESSION=[]; return null; } return $u; }
function staff_can(array $u,string $cap): bool { if ($u['role']==='owner') return true; $p=json_decode((string)$u['permissions'],true); return is_array($p) && in_array($cap,$p,true); }
function audit(int $actor,string $action,?int $target=null): void { global $pdo,$nowSql; $q=$pdo->prepare('INSERT INTO rw_staff_audit(actor_id,action,target_id,created_at) VALUES(?,?,?,'.$nowSql.')'); $q->execute([$actor,$action,$target]); }
