<?php
$phar = new Phar('mostsimplecms.phar');
$phar->addFile('index.php');
$phar->addFile('src/MostSimpleCMS.php');
$phar->addFile('vendor/autoload.php');
$phar->addFile('vendor/composer/autoload_real.php');
$phar->addFile('vendor/composer/autoload_psr4.php');
$phar->addFile('vendor/composer/autoload_classmap.php');
$phar->addFile('vendor/composer/autoload_namespaces.php');
$phar->addFile('vendor/composer/ClassLoader.php');
