<?php
ini_set('phar.readonly',false);

$p = new Phar('backup.phar', FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME, 'backup.phar');
$p->startBuffering();
$p->setStub('#!/usr/bin/env php'.PHP_EOL.'<?php Phar::mapPhar(); include "phar://backup.phar/main.php"; __HALT_COMPILER(); ?>');
$p->addFile('main.php');
$p->addFile('app/autoloader.php');
$p->buildFromDirectory('src', '$(.*)\.php$');
$p->stopBuffering();
chmod('backup.phar',0700);
echo 'backup.phar has been created.'.PHP_EOL;
