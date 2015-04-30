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
                $htmlFiles[] = $fileEntry;
            }
        }
        foreach ($htmlFiles as $fileEntry) {
            $this->extractTemplates($fileEntry);
        }
        foreach ($htmlFiles as $fileEntry) {
            $this->processFile($fileEntry);
        }
    }

    public function extractTemplates($fileName)
    {
        $html = file($fileName, FILE_IGNORE_NEW_LINES);
        $allTemplates = preg_grep('/<!-- Template Begin [A-Za-z0-9]+ -->/', $html);
        $templateNames = array();
        foreach ($allTemplates as $template) {
            $templateName = explode(' ', $template);
            $templateNames[] = $templateName[3];
        }
        var_dump($templateNames);
        foreach ($templateNames as $templateName) {
            $begin = array_search('<!-- Template Begin ' . $templateName . ' -->', $html);
            if ($begin === false) {
                return;
            }
//            $templateName = explode(' ', $html[$begin]);
            $begin++;
            $end = array_search('<!-- Template End ' . $templateName . ' -->', $html);
            $length = $end - $begin - 1;
            $templateArray = array_slice($html, $begin, $length);
            $this->templates[$templateName] = $templateArray;
        }
    }

    public function processFile($fileName)
    {
        copy($fileName, $fileName . '.bak');
        foreach ($this->templates as $templateName => $template) {
            $html = file($fileName, FILE_IGNORE_NEW_LINES);
            $begin = array_search('<!-- Placeholder Begin ' . $templateName . ' -->', $html);
            if ($begin === false) {
                continue;
            }
            $begin++;
            $end = array_search('<!-- Placeholder End ' . $templateName . ' -->', $html);
            $length = $end - $begin - 1;
            array_splice($html, $begin, $length, $this->templates[$templateName]);
            $htmlString = implode(PHP_EOL, $html);
            file_put_contents($fileName, $htmlString);
        }
        return;
    }
}