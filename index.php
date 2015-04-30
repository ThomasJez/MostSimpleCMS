<?php
$simplecms = new MostSimpleCMS();
$simplecms->processWholePage();

class MostSimpleCMS
{
    public $templates = array();

    public function processWholePage() {
        $currentDir = scandir(getcwd());
        $htmlFiles = array();
        foreach ($currentDir as $fileEntry) {
            if ((preg_match('/.+\.html$/', $fileEntry)) === 1) {
                $this->extractTemplates($fileEntry);
            }
        }
        foreach ($currentDir as $fileEntry) {
            if ((preg_match('/.+\.html$/', $fileEntry)) === 1) {
                $this->processFile('navi.txt', $fileEntry);
            }
        }
    }

    public function extractTemplates($fileName)
    {
        $html = file($fileName, FILE_IGNORE_NEW_LINES);
        $begin = array_search('<!-- Template Begin Menu -->', $html);
        if ($begin === false) {
            return;
        }
        $templateName = explode(' ', $html[$begin]);
        $begin++;
        $end = array_search('<!-- Template End Menu -->', $html);
        $length = $end - $begin - 1;
        $templateArray = array_slice($html, $begin, $length);
        $this->templates[$templateName[3]] = $templateArray;
    }

    public function processFile($naviName, $fileName)
    {
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
        array_splice($html, $begin, $length, $this->templates['Menu']);
        $htmlString = implode(PHP_EOL, $html);
        file_put_contents($fileName, $htmlString);
        return;
    }
}