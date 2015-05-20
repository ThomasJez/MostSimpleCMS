<?php
require 'vendor/autoload.php';
$simplecms = new MostSimpleCMS\MostSimpleCMS();
$simplecms->processWholePage();
if ($argc >= 2) {
    if ($argv[1] === '-m') {
        $simplecms->processMenuSpecials();
    } else {
        echo 'Sorry, the only accepted argument is -m' . PHP_EOL;
    }
}
