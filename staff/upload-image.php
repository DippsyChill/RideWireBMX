<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
function upload_reply(int $status,array $payload): void { http_response_code($status); echo json_encode($payload,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); exit; }
$user=staff_user();
if(!$user){upload_reply(401,['error'=>'Your staff session ended. Sign in again and retry.']);}
if((int)$user['must_change_password']){upload_reply(403,['error'=>'Change your staff password before uploading images.']);}
$section=(string)($_GET['section']??'');
$sectionCaps=['news'=>'news.edit','spotlight'=>'spotlight.edit','rider'=>'riders.edit'];
if(!isset($sectionCaps[$section])||!staff_can($user,$sectionCaps[$section])){upload_reply(403,['error'=>'You do not have permission to upload images for this editor.']);}
if($_SERVER['REQUEST_METHOD']!=='POST'){upload_reply(405,['error'=>'Use the image upload form.']);}
try{check_csrf();}catch(Throwable){upload_reply(403,['error'=>'The upload form expired. Reload the editor and try again.']);}
$file=$_FILES['image_file']??null;
if(!is_array($file)||!isset($file['tmp_name'],$file['error'],$file['size'])||(int)$file['error']!==UPLOAD_ERR_OK){upload_reply(400,['error'=>'The image did not reach the server. Check the size limit and try again.']);}
$maxFile=8*1024*1024;$maxStorage=1024*1024*1024;
if((int)$file['size']<1||(int)$file['size']>$maxFile){upload_reply(413,['error'=>'The prepared image must be 8 MB or smaller.']);}
$imageInfo=@getimagesize((string)$file['tmp_name']);
$signature=@file_get_contents((string)$file['tmp_name'],false,null,0,12);
if(!$imageInfo||($imageInfo['mime']??'')!=='image/webp'||!is_string($signature)||substr($signature,0,4)!=='RIFF'||substr($signature,8,4)!=='WEBP'||(int)$imageInfo[0]>2200||(int)$imageInfo[1]>2200){upload_reply(415,['error'=>'Only resized WebP images up to 2200 pixels are accepted. Choose the image again so the editor can prepare it.']);}
$uploadDir=dirname(__DIR__).'/assets/uploads';
$used=0;
if(is_dir($uploadDir)){
    $iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadDir,FilesystemIterator::SKIP_DOTS));
    foreach($iterator as $entry)if($entry->isFile()&&!$entry->isLink())$used+=$entry->getSize();
}
if($used+(int)$file['size']>$maxStorage){upload_reply(507,['error'=>'The 1 GB RideWire image upload budget is full. Ask the Owner to review uploaded images.']);}
if(!is_dir($uploadDir)&&!mkdir($uploadDir,0755,true)&&!is_dir($uploadDir)){upload_reply(500,['error'=>'The upload folder could not be created. Check the website file permissions.']);}
$filename=bin2hex(random_bytes(16)).'.webp';
$destination=$uploadDir.'/'.$filename;
if(!move_uploaded_file((string)$file['tmp_name'],$destination)){upload_reply(500,['error'=>'The image could not be saved. Check the website file permissions.']);}
$newSize=filesize($destination)?:0;
audit((int)$user['id'],'content.image.uploaded',null);
$used+=$newSize;
upload_reply(201,['url'=>'/assets/uploads/'.$filename,'bytes'=>$newSize,'storage_used'=>number_format(round($used/1048576,1),1).' MB','storage_limit'=>'1 GB']);
