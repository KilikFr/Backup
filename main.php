<?php

include_once __DIR__.'/app/autoloader.php';
$autoloader=new \Psr4AutoloaderClass();

// @todo remove this temp fix, check how to add Dir in phar without removing is path
if(substr(__DIR__,0,7)=='phar://') {
    $autoloader->addNamespace('\\Kilik\\Backup', __DIR__.'/Kilik/Backup');
}
else {
    $autoloader->addNamespace('\\Kilik\\Backup', __DIR__.'/src/Kilik/Backup');
}
$autoloader->register();

$cmd=new \Kilik\Backup\Command();
$cmd->exec();
