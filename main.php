<?php

include_once('app/autoloader.php');
$autoloader=new \app\Psr4AutoloaderClass();
$autoloader->addNamespace('\\Kilik\\Backup','src/Kilik/Backup');
$autoloader->register();

$cmd=new \Kilik\Backup\Command();
$cmd->exec();
