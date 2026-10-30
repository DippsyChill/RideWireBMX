<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/staff/bootstrap.php';
function ask(string $label,bool $secret=false):string{fwrite(STDOUT,$label);$hide=$secret&&DIRECTORY_SEPARATOR==='/'&&function_exists('shell_exec');if($hide)@shell_exec('stty -echo');$v=trim((string)fgets(STDIN));if($hide){@shell_exec('stty echo');fwrite(STDOUT,PHP_EOL);}return $v;}
if((int)$pdo->query("SELECT COUNT(*) FROM rw_staff_users WHERE role='owner'")->fetchColumn()){fwrite(STDERR,"Owner already exists; no changes made.\n");exit(1);}
$name=ask('Owner name: ');$email=strtolower(ask('Owner email: '));$pass=ask('Owner password (12+ characters): ',true);$confirm=ask('Confirm password: ',true);
if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($pass)<12||!hash_equals($pass,$confirm)){fwrite(STDERR,"Invalid details or password mismatch.\n");exit(1);}
$q=$pdo->prepare("INSERT INTO rw_staff_users(email,display_name,password_hash,role,permissions,is_active,must_change_password,created_at) VALUES(?,?,?,'owner','[]',1,0,".$nowSql.")");$q->execute([$email,$name,password_hash($pass,PASSWORD_DEFAULT)]);fwrite(STDOUT,"Owner created. Sign in at /staff/.\n");
