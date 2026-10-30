<?php
// Copy to private/config.php on IONOS and fill in the database values.
// Generate security_key with: php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
return [
 'db'=>['driver'=>'mysql','host'=>'YOUR_IONOS_DATABASE_HOST','name'=>'YOUR_DATABASE_NAME','user'=>'YOUR_DATABASE_USER','password'=>'YOUR_DATABASE_PASSWORD'],
 'security_key'=>'REPLACE_WITH_A_RANDOM_64_CHARACTER_HEX_KEY',
 'session_name'=>'ridewire_staff',
];
