<?php
// Copy to private/config.php for localhost use. Requires PHP with PDO_SQLite enabled.
return [
 'db'=>['driver'=>'sqlite','path'=>sys_get_temp_dir().DIRECTORY_SEPARATOR.'ridewire-staff-local.sqlite'],
 'security_key'=>'LOCAL_ONLY_REPLACE_WITH_A_RANDOM_64_CHARACTER_HEX_KEY',
 'session_name'=>'ridewire_staff_local',
];
