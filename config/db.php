<?php

$db_acc = parse_ini_file(DB_PATH);

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host='.$db_acc['DB_HOST'].';dbname='.$db_acc['DB_NAME'],
    'username' => $db_acc['DB_USER'],
    'password' => $db_acc['DB_PASSWORD'],
    'tablePrefix'=>'sb_',
    'charset' => 'utf8',
];
