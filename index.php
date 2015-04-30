<?php
$naviname = trim($argv[1]);
$currentDir = scandir(getcwd());
$htmlFiles = array();
foreach ($currentDir as $fileEntry) {
    if ((preg_match('/.+\.html$/', $fileEntry)) === 1) {
//        processFile($naviname, $fileEntry);
        extractTemplates($fileEntry);
    }
}

function extractTemplates($fileName) {
    var_dump($fileName);
    $html = file($fileName, FILE_IGNORE_NEW_LINES);
    $begin = array_search('<!-- Template Begin Menu -->', $html);
    if ($begin === false) {
        return;
    }
    $begin++;
    $end = array_search('<!-- Template End Menu -->', $html);
    $length = $end - $begin - 1;
    $templateArray = array_slice($html, $begin, $length);

    var_dump($templateArray);
}

function processFile($naviName, $fileName) {
    echo $naviName . PHP_EOL;
    echo $fileName . PHP_EOL;
    copy($fileName, $fileName . '.bak');
    $html = file($fileName, FILE_IGNORE_NEW_LINES);
    $begin = array_search('<!-- Placeholder Begin Menu -->', $html);
    if ($begin === false) {
        return;
    }
    $begin++;
    $end = array_search('<!-- Placeholder End Menu -->', $html);
    $length = $end - $begin - 1;
    $navi = file($naviName, FILE_IGNORE_NEW_LINES);
    $tempArray = array();
    foreach ($navi as $naviLine) {
        $naviEntry = explode(';', $naviLine);
        if ($naviEntry[0] === $fileName) {
            $outLine = '<li><a id="sichtbar" href="' . $naviEntry[0] . '">&gt;&gt;' . $naviEntry[1] . '</a></li>';
        } else {
            $outLine = '<li><a href="' . $naviEntry[0] . '">&gt;&gt;' . $naviEntry[1] . '</a></li>';
        }
        $tempArray[] = $outLine;
    }
    array_splice($html, $begin, $length, $tempArray);
    $htmlString = implode(PHP_EOL, $html);
    file_put_contents($fileName, $htmlString);
    return;
}
