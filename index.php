<?php
$naviname = trim($argv[1]);
$currentDir = scandir(getcwd());
$htmlFiles = array();
foreach ($currentDir as $fileEntry) {
    if ((preg_match('/.+\.html$/', $fileEntry)) === 1) {
        processFile($naviname, $fileEntry);
//        extractTemplates($fileEntry);
    }
}

/*
public function extractTemplates($fileName) {
    var_dump($fileName);
}
*/

function processFile($naviName, $fileName) {
    echo $naviName . PHP_EOL;
    echo $fileName . PHP_EOL;
    copy($fileName, $fileName . '.bak');
    $html = file($fileName, FILE_IGNORE_NEW_LINES);
    $beginPlaceholder = array_search('<!-- Placeholder Begin Menu -->', $html);
    if ($beginPlaceholder === false) {
        return;
    }
    $beginPlaceholder++;
    $endPlaceholder = array_search('<!-- Placeholder End Menu -->', $html);
    $placeholderLength = $endPlaceholder - $beginPlaceholder - 1;
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
    array_splice($html, $beginPlaceholder, $placeholderLength, $tempArray);
    $htmlString = implode(PHP_EOL, $html);
    file_put_contents($fileName, $htmlString);
    return;
}
