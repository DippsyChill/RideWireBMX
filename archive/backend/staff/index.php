<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
header('Cache-Control: no-store, private');
$error=''; $notice=''; $user=staff_user();

if ($_SERVER['REQUEST_METHOD']==='POST') {
 check_csrf(); $action=(string)($_POST['action']??'');
 try {
  if ($action==='login' && !$user) {
   $email=strtolower(trim((string)($_POST['email']??''))); $pass=(string)($_POST['password']??'');
   $fp=hash_hmac('sha256',$email.'|'.($_SERVER['REMOTE_ADDR']??''),$config['security_key']); $now=time();
   $q=$pdo->prepare('SELECT window_started,failures FROM rw_staff_login_limits WHERE fingerprint=?'); $q->execute([$fp]); $limit=$q->fetch();
   if ($limit && $now-(int)$limit['window_started']<900 && (int)$limit['failures']>=8) throw new RuntimeException('Sign-in is temporarily limited. Try again in 15 minutes.');
   $q=$pdo->prepare('SELECT id,password_hash FROM rw_staff_users WHERE email=? AND is_active=1 LIMIT 1'); $q->execute([$email]); $found=$q->fetch();
   if ($found && password_verify($pass,$found['password_hash'])) {
    $pdo->prepare('DELETE FROM rw_staff_login_limits WHERE fingerprint=?')->execute([$fp]); session_regenerate_id(true); $_SESSION['staff_id']=(int)$found['id']; $_SESSION['csrf']=bin2hex(random_bytes(32));
    $pdo->prepare('UPDATE rw_staff_users SET last_login_at='.$nowSql.' WHERE id=?')->execute([(int)$found['id']]); header('Location: index.php'); exit;
   }
   if (!$limit || $now-(int)$limit['window_started']>=900) {
    $sql=$driver==='sqlite'?'INSERT INTO rw_staff_login_limits(fingerprint,window_started,failures) VALUES(?,?,1) ON CONFLICT(fingerprint) DO UPDATE SET window_started=excluded.window_started,failures=1':'INSERT INTO rw_staff_login_limits(fingerprint,window_started,failures) VALUES(?,?,1) ON DUPLICATE KEY UPDATE window_started=VALUES(window_started),failures=1';
    $pdo->prepare($sql)->execute([$fp,$now]);
   } else $pdo->prepare('UPDATE rw_staff_login_limits SET failures=failures+1 WHERE fingerprint=?')->execute([$fp]);
   throw new RuntimeException('Email or password is incorrect.');
  }
  $user=staff_user(); if (!$user) throw new RuntimeException('Please sign in again.');
  if ($action==='logout') { $_SESSION=[]; session_destroy(); header('Location: index.php'); exit; }
  if ($action==='password') {
   if (!(int)$user['must_change_password']) { $q=$pdo->prepare('SELECT password_hash FROM rw_staff_users WHERE id=?'); $q->execute([(int)$user['id']]); if (!password_verify((string)($_POST['current_password']??''),(string)$q->fetchColumn())) throw new RuntimeException('Current password is incorrect.'); }
   $new=(string)($_POST['new_password']??''); $confirm=(string)($_POST['confirm_password']??'');
   if (strlen($new)<12 || !hash_equals($new,$confirm)) throw new RuntimeException('Use at least 12 characters and make sure both passwords match.');
   $pdo->prepare('UPDATE rw_staff_users SET password_hash=?,must_change_password=0 WHERE id=?')->execute([password_hash($new,PASSWORD_DEFAULT),(int)$user['id']]); audit((int)$user['id'],'staff.password.changed',(int)$user['id']); $_SESSION['csrf']=bin2hex(random_bytes(32)); header('Location: index.php'); exit;
  }
  if (!staff_can($user,'staff.manage')) throw new RuntimeException('You do not have permission to manage staff.');
  if ($action==='create') {
   $name=trim((string)($_POST['display_name']??'')); $email=strtolower(trim((string)($_POST['email']??''))); $role=(string)($_POST['role']??'editor'); $temp=(string)($_POST['temporary_password']??'');
   if ($name==='' || mb_strlen($name)>120 || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($temp)<12) throw new RuntimeException('Enter a name, valid email and temporary password of at least 12 characters.');
   if (!in_array($role,['admin','editor'],true) || ($user['role']!=='owner' && $role!=='editor')) throw new RuntimeException('Your role cannot create that account type.');
   $chosen=[]; foreach ((array)($_POST['permissions']??[]) as $cap) if (isset(STAFF_CAPS[$cap]) && ($user['role']==='owner'||staff_can($user,$cap))) $chosen[]=$cap;
   $q=$pdo->prepare('INSERT INTO rw_staff_users(email,display_name,password_hash,role,permissions,is_active,must_change_password,created_at) VALUES(?,?,?,?,?,1,1,'.$nowSql.')');
   $q->execute([$email,$name,password_hash($temp,PASSWORD_DEFAULT),$role,json_encode(array_values(array_unique($chosen)),JSON_THROW_ON_ERROR)]);
   $id=(int)$pdo->lastInsertId(); audit((int)$user['id'],'staff.created.'.$role,$id); $notice='Account created. Share the temporary password directly; it must be changed at first sign-in.';
  } elseif ($action==='permissions') {
   $id=(int)($_POST['user_id']??0); $q=$pdo->prepare('SELECT id,role FROM rw_staff_users WHERE id=?'); $q->execute([$id]); $target=$q->fetch();
   if (!$target || $target['role']==='owner' || ($user['role']!=='owner' && $target['role']!=='editor')) throw new RuntimeException('Your role cannot change that account.');
   $chosen=[]; foreach ((array)($_POST['permissions']??[]) as $cap) if (isset(STAFF_CAPS[$cap]) && ($user['role']==='owner'||staff_can($user,$cap))) $chosen[]=$cap;
   $pdo->prepare('UPDATE rw_staff_users SET permissions=? WHERE id=?')->execute([json_encode(array_values(array_unique($chosen)),JSON_THROW_ON_ERROR),$id]); audit((int)$user['id'],'staff.permissions.updated',$id); $notice='Permissions updated.';
  } elseif ($action==='status') {
   $id=(int)($_POST['user_id']??0); $q=$pdo->prepare('SELECT id,role,is_active FROM rw_staff_users WHERE id=?'); $q->execute([$id]); $target=$q->fetch();
   if (!$target || $target['role']==='owner' || $id===(int)$user['id'] || ($user['role']!=='owner' && $target['role']!=='editor')) throw new RuntimeException('Your role cannot change that account.');
   $active=(int)!(int)$target['is_active']; $pdo->prepare('UPDATE rw_staff_users SET is_active=? WHERE id=?')->execute([$active,$id]); audit((int)$user['id'],$active?'staff.activated':'staff.deactivated',$id); $notice=$active?'Account reactivated.':'Account deactivated.';
  }
 } catch (PDOException $e) { $error=(string)$e->getCode()==='23000'?'That email already has a staff account.':'Could not save the change; check database setup and retry.'; error_log('RideWire staff database error: '.$e->getMessage()); }
 catch (RuntimeException $e) { $error=$e->getMessage(); }
 $user=staff_user();
}
function start_page(string $title):void { echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.h($title).' | RideWire BMX</title><link rel="stylesheet" href="staff.css"></head><body><header><a href="index.php"><img src="../assets/ridewire-logo-v5.png" alt="RideWire BMX"></a><span>STAFF PORTAL</span></header><main>'; }
function end_page():void { echo '</main><footer>RideWire BMX · Staff access</footer></body></html>'; }

if (!$user) { start_page('Staff Login'); ?>
<section class="panel login"><p class="eyebrow">PRIVATE ACCESS</p><h1>Staff Login</h1><p>For RideWire Admins and Editors.</p><?php if($error):?><p class="notice error" role="alert"><?=h($error)?></p><?php endif;?>
<form method="post" action="index.php"><?=csrf()?><input type="hidden" name="action" value="login"><label for="email">Email address</label><input id="email" name="email" type="email" autocomplete="username" required maxlength="190"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required><button class="button" type="submit">Sign in →</button></form><a class="back" href="../Pages/index.html">Return to RideWire</a></section>
<?php end_page(); exit; }

if ((int)$user['must_change_password'] || ($_GET['view']??'')==='password') { start_page('Change password'); ?>
<section class="panel login"><p class="eyebrow">ACCOUNT SECURITY</p><h1>Change password</h1><p>Choose a personal password of at least 12 characters.</p><?php if($error):?><p class="notice error" role="alert"><?=h($error)?></p><?php endif;?>
<form method="post"><?=csrf()?><input type="hidden" name="action" value="password"><?php if(!(int)$user['must_change_password']):?><label for="current_password">Current password</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" required><?php endif;?><label for="new_password">New password</label><input id="new_password" name="new_password" type="password" minlength="12" autocomplete="new-password" required><label for="confirm_password">Confirm password</label><input id="confirm_password" name="confirm_password" type="password" minlength="12" autocomplete="new-password" required><button class="button" type="submit">Save password →</button></form></section>
<?php end_page(); exit; }

if (($_GET['view'] ?? '') !== 'permissions') {
 start_page('Dashboard'); ?>
<section class="panel"><p class="eyebrow">RIDEWIRE BMX / STAFF</p><h1>Dashboard</h1><p>Welcome, <?=h($user['display_name'])?>. Signed in as <strong><?=h(ucfirst($user['role']))?></strong>.</p><?php if($notice):?><p class="notice success" role="status"><?=h($notice)?></p><?php endif;?><?php if($error):?><p class="notice error" role="alert"><?=h($error)?></p><?php endif;?><a class="back" href="../Pages/index.html">Open the public website</a><a class="back" href="index.php?view=password">Change password</a>
<div class="dashboard-grid"><?php if(staff_can($user,'site.stats.view')):?><a class="dashboard-card" href="stats.php"><span>REPORTING</span><h2>Website Statistics</h2><p>Daily aggregate page views and top pages.</p></a><?php endif;?>
<?php foreach(['news'=>['News Editor','news.edit'],'spotlight'=>['Spotlight Editor','spotlight.edit'],'rider'=>['Rider Editor','riders.edit']] as $section=>[$title,$cap]):if(staff_can($user,$cap)):?><a class="dashboard-card" href="editor.php?section=<?=h($section)?>"><span>CONTENT</span><h2><?=h($title)?></h2><p>Review existing entries and create or update content.</p></a><?php endif;endforeach;?>
<?php if(staff_can($user,'staff.manage')):?><a class="dashboard-card" href="index.php?view=permissions"><span>SETUP</span><h2>Staff Permissions</h2><p>Create staff accounts and control their access.</p></a><?php endif;?></div>
<form method="post" class="logout"><?=csrf()?><input type="hidden" name="action" value="logout"><button class="link" type="submit">Sign out</button></form></section><style>.dashboard-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,230px),1fr));gap:16px;margin:30px 0}.dashboard-card{min-height:170px;padding:22px;border:1px solid #dedfe1;border-top:4px solid #f20c1b;color:#131313;text-decoration:none;background:#fff}.dashboard-card:hover{box-shadow:0 8px 24px #00000012;transform:translateY(-2px)}.dashboard-card span{color:#b10b17;font-size:.7rem;letter-spacing:.15em;font-weight:700}.dashboard-card h2{margin:12px 0 4px;font-size:1.45rem}.dashboard-card p{margin:0;color:#626a72}</style>
<?php end_page(); exit; }
start_page('Staff permissions'); ?>
<section class="panel"><p class="eyebrow">RIDEWIRE BMX / STAFF</p><h1>Welcome, <?=h($user['display_name'])?>.</h1><p>Signed in as <strong><?=h(ucfirst($user['role']))?></strong>.</p><?php if($notice):?><p class="notice success" role="status"><?=h($notice)?></p><?php endif;?><?php if($error):?><p class="notice error" role="alert"><?=h($error)?></p><?php endif;?>
<p class="notice">Create staff accounts and grant each person only the permissions they need.</p><a class="back" href="../Pages/index.html">Open the public website</a><a class="back" href="index.php?view=password">Change password</a>
<?php if(staff_can($user,'staff.manage')): ?>
<h2>Setup / Staff permissions</h2><p>Create staff accounts and grant only the actions each person needs. Deactivate accounts to remove access while retaining audit history.</p>
<form method="post" class="create"><?=csrf()?><input type="hidden" name="action" value="create"><label for="display_name">Name</label><input id="display_name" name="display_name" required maxlength="120"><label for="new_email">Email address</label><input id="new_email" name="email" type="email" required maxlength="190"><label for="role">Role</label><select id="role" name="role"><?php if($user['role']==='owner'):?><option value="admin">Admin</option><?php endif;?><option value="editor">Editor</option></select><label for="temporary_password">Temporary password (12+ characters)</label><input id="temporary_password" name="temporary_password" type="password" minlength="12" autocomplete="new-password" required><fieldset><legend>Permissions</legend><?php foreach(STAFF_CAPS as $cap=>$label):if($user['role']!=='owner'&&!staff_can($user,$cap))continue;?><label class="check"><input type="checkbox" name="permissions[]" value="<?=h($cap)?>"> <?=h($label)?></label><?php endforeach;?></fieldset><button class="button" type="submit">Create staff account</button></form>
<h2>Current staff</h2><?php $members=$pdo->query('SELECT id,email,display_name,role,permissions,is_active FROM rw_staff_users ORDER BY CASE role WHEN "owner" THEN 0 WHEN "admin" THEN 1 ELSE 2 END,display_name')->fetchAll(); foreach($members as $m): ?>
<article class="member"><h3><?=h($m['display_name'])?> <small><?=h(ucfirst($m['role']))?> · <?=((int)$m['is_active']?'Active':'Inactive')?></small></h3><p><?=h($m['email'])?></p>
<?php if($m['role']!=='owner'&&($user['role']==='owner'||$m['role']==='editor')):$selected=json_decode((string)$m['permissions'],true)?:[]; ?>
<form method="post" class="permission-form"><?=csrf()?><input type="hidden" name="action" value="permissions"><input type="hidden" name="user_id" value="<?=(int)$m['id']?>"><fieldset><legend>Allowed actions</legend><?php foreach(STAFF_CAPS as $cap=>$label):if($user['role']!=='owner'&&!staff_can($user,$cap))continue;?><label class="check"><input type="checkbox" name="permissions[]" value="<?=h($cap)?>" <?=in_array($cap,$selected,true)?'checked':''?>> <?=h($label)?></label><?php endforeach;?></fieldset><button class="small" type="submit">Save permissions</button></form>
<?php if((int)$m['id']!==(int)$user['id']):?><form method="post"><?=csrf()?><input type="hidden" name="action" value="status"><input type="hidden" name="user_id" value="<?=(int)$m['id']?>"><button class="link" type="submit"><?=((int)$m['is_active']?'Deactivate':'Reactivate')?> account</button></form><?php endif;endif;?></article>
<?php endforeach; endif; ?>
<form method="post" class="logout"><?=csrf()?><input type="hidden" name="action" value="logout"><button class="link" type="submit">Sign out</button></form></section><?php end_page(); ?>
