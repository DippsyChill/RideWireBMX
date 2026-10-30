<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
header('Cache-Control: no-store, private');
$user=staff_user(); if(!$user){header('Location: index.php');exit;}
if((int)$user['must_change_password']){header('Location: index.php?view=password');exit;}
$section=(string)($_GET['section']??'news');
$settings=['news'=>['News','news.edit','news.publish','news.delete'],'spotlight'=>['Spotlight','spotlight.edit','spotlight.publish','spotlight.delete'],'rider'=>['Riders','riders.edit','riders.publish','riders.delete']];
if(!isset($settings[$section])){http_response_code(404);exit('Editor not found');}
[$label,$editCap,$publishCap,$deleteCap]=$settings[$section];
if(!staff_can($user,$editCap)){http_response_code(403);exit('You do not have access to this editor.');}
$error='';$notice='';$id=(int)($_GET['id']??0);$item=null;
function e(string $v):string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function clean_body(string $html):string{
 $allowed=['p','h2','h3','ul','ol','li','strong','em','b','i','blockquote','figure','figcaption','img','a','br','hr','iframe'];
 $dom=new DOMDocument('1.0','UTF-8');libxml_use_internal_errors(true);$dom->loadHTML('<?xml encoding="utf-8"?><div id="root">'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);libxml_clear_errors();$root=$dom->getElementById('root');if(!$root)return '';
 $walk=function(DOMNode $node)use(&$walk,$allowed):void{for($child=$node->firstChild;$child;){$next=$child->nextSibling;if($child instanceof DOMElement){$tag=strtolower($child->tagName);if(!in_array($tag,$allowed,true)){$parent=$child->parentNode;while($child->firstChild)$parent->insertBefore($child->firstChild,$child);$parent->removeChild($child);}else{$keep=[];if($tag==='a')$keep=['href','target','rel'];if($tag==='img')$keep=['src','alt','loading'];if($tag==='iframe')$keep=['src','title','allow','allowfullscreen','loading'];foreach(iterator_to_array($child->attributes) as $attr){$name=strtolower($attr->name);if(!in_array($name,$keep,true)||str_starts_with($name,'on'))$child->removeAttributeNode($attr);}$url=$child->getAttribute('href')?:$child->getAttribute('src');if($url!==''&&!preg_match('~^(https://|/|\.\./|assets/)~i',$url)){$child->removeAttribute('href');$child->removeAttribute('src');}if($tag==='iframe'&&!preg_match('~^https://(www\.)?(youtube(-nocookie)?\.com|youtu\.be|vimeo\.com|pinkbike\.com)/~i',$child->getAttribute('src')))$child->parentNode->removeChild($child);else{$walk($child);if($tag==='a')$child->setAttribute('rel','noopener noreferrer');}}} $child=$next;}};
 $walk($root);$out='';foreach($root->childNodes as $n)$out.=$dom->saveHTML($n);return $out;
}
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{check_csrf();$action=(string)($_POST['action']??'');$id=(int)($_POST['id']??0);
  if($action==='archive'){if(!staff_can($user,$deleteCap))throw new RuntimeException('You cannot remove entries.');$q=$pdo->prepare("UPDATE rw_content_items SET status='archived',updated_at=".$nowSql." WHERE id=? AND section=?");$q->execute([$id,$section]);audit((int)$user['id'],$section.'.archived',$id);$notice='Entry removed from the public site. It remains archived and can be restored.';}
  elseif($action==='save'){
   $title=trim((string)($_POST['title']??''));$slug=strtolower(trim((string)($_POST['slug']??'')));$summary=trim((string)($_POST['summary']??''));$status=(string)($_POST['status']??'draft');$image=trim((string)($_POST['image']??''));$source=trim((string)($_POST['source_url']??''));
   if($title===''||mb_strlen($title)>220||!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/',$slug))throw new RuntimeException('Enter a title and a URL slug using lowercase letters, numbers and hyphens.');
   $existingStatus=null;if($id){$existing=$pdo->prepare('SELECT status FROM rw_content_items WHERE id=? AND section=?');$existing->execute([$id,$section]);$existingStatus=$existing->fetchColumn()?:null;}
   if(!staff_can($user,$publishCap))$status=$existingStatus??'draft';
   if(!in_array($status,['draft','published'],true)||($status==='published'&&!staff_can($user,$publishCap)&&$existingStatus!=='published'))throw new RuntimeException('You do not have permission to publish.');
   if($existingStatus==='published'&&$status==='draft'&&!staff_can($user,$deleteCap))throw new RuntimeException('You do not have permission to unpublish this entry.');
   if($image!==''&&!preg_match('~^(https://|/|\.\./|assets/)~i',$image))throw new RuntimeException('Image must be an existing site path or HTTPS URL.');
   if($source!==''&&!filter_var($source,FILTER_VALIDATE_URL))throw new RuntimeException('Source link must be a full URL.');
   $date=trim((string)($_POST['published_at']??''));$date=($date===''&&$driver==='mysql')?null:$date;
   $fields=[$section,$slug,$title,trim((string)($_POST['category']??'')),$summary,clean_body((string)($_POST['body_html']??'')),$image,trim((string)($_POST['image_alt']??'')),trim((string)($_POST['image_caption']??'')),trim((string)($_POST['country']??'')),trim((string)($_POST['discipline']??'')),trim((string)($_POST['highlight']??'')),$source,$date,$status,(int)($_POST['sort_order']??0)];
   if($id){$sql='UPDATE rw_content_items SET section=?,slug=?,title=?,category=?,summary=?,body_html=?,image=?,image_alt=?,image_caption=?,country=?,discipline=?,highlight=?,source_url=?,published_at=?,status=?,sort_order=?,updated_at='.$nowSql.' WHERE id=? AND section=?';$fields[]=$id;$fields[]=$section;$pdo->prepare($sql)->execute($fields);audit((int)$user['id'],$section.'.updated',$id);}else{$sql='INSERT INTO rw_content_items(section,slug,title,category,summary,body_html,image,image_alt,image_caption,country,discipline,highlight,source_url,published_at,status,sort_order,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'.$nowSql.','.$nowSql.')';$pdo->prepare($sql)->execute($fields);$id=(int)$pdo->lastInsertId();audit((int)$user['id'],$section.'.created',$id);}
   header('Location: editor.php?section='.rawurlencode($section).'&id='.$id.'&saved=1');exit;
  }
 }catch(PDOException $ex){$error='Could not save. The URL slug may already be in use.';error_log('RideWire content database error: '.$ex->getMessage());}catch(RuntimeException $ex){$error=$ex->getMessage();}
}
if(isset($_GET['saved']))$notice='Changes saved.';
if($id){$q=$pdo->prepare('SELECT * FROM rw_content_items WHERE id=? AND section=?');$q->execute([$id,$section]);$item=$q->fetch()?:null;}
$q=$pdo->prepare('SELECT id,title,slug,status,published_at FROM rw_content_items WHERE section=? ORDER BY sort_order,published_at DESC,id DESC');$q->execute([$section]);$items=$q->fetchAll();
define('RIDEWIRE_STAFF_EDITOR',true);
require __DIR__ . '/editor-view.php';
