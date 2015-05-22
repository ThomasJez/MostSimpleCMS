<?php
namespace MostSimpleCMS;
use DOMDocument;
use DOMXPath;
class MostSimpleCMS
{
    public $templates = array();
    public $placeHolders = array();
    public $templatePlaces = array();
    public $htmlFiles = array();

    public function processWholePage() {
        $currentDir = scandir(getcwd());
        $this->htmlFiles = array();
        foreach ($currentDir as $fileEntry) {
            if ((preg_match('/.+\.html$/', $fileEntry)) === 1) {
                $this->htmlFiles[] = $fileEntry;
            }
        }
        foreach ($this->htmlFiles as $fileEntry) {
            $this->extractTemplates($fileEntry);
        }
        foreach ($this->htmlFiles as $fileEntry) {
            copy($fileEntry, $fileEntry . '.bak');
            $this->processFile($fileEntry);
        }
        return $this;
    }

    public function extractTemplates($fileName)
    {
        $html = file($fileName, FILE_IGNORE_NEW_LINES);

        $allTemplates = preg_grep('/ *<!-- Template Begin [A-Za-z0-9]+ --> */', $html);
        $templateNames = array();
        foreach ($allTemplates as $template) {
            $templateName = explode(' ', trim($template));
            $templateNames[] = $templateName[3];
        }
        foreach ($templateNames as $templateName) {
            $begin = $this->array_search_regex('/ *<!-- Template Begin ' . $templateName . ' --> */', $html);
            if ($begin === false) {
                return $this;
            }
            $begin++;
            $end = $this->array_search_regex('/ *<!-- Template End ' . $templateName . ' --> */', $html);
            $length = $end - $begin;
            $templateArray = array_slice($html, $begin, $length);
            $this->templates[$templateName] = $templateArray;
            $this->templatePlaces[$templateName] = $fileName;
        }
        return $this;
    }

    public function processFile($fileName)
    {
        $placeHolders = array();
        foreach ($this->templates as $templateName => $template) {
            $html = file($fileName, FILE_IGNORE_NEW_LINES);
            $begin = $this->array_search_regex('/ *<!-- Placeholder Begin ' . $templateName . ' --> */', $html);
            if ($begin === false) {
                continue;
            }
            $begin++;
            $end = $this->array_search_regex('/ *<!-- Placeholder End ' . $templateName . ' --> */', $html);
            $length = $end - $begin;
            array_splice($html, $begin, $length, $this->templates[$templateName]);
            $placeHolders[$templateName] = &$this->templates[$templateName];
            $htmlString = implode(PHP_EOL, $html);
            file_put_contents($fileName, $htmlString);
        }
        $this->placeHolders[$fileName] = $placeHolders;
        return $this;
    }

    public function array_search_regex($needle, $haystack) {
        $beginArray = preg_grep($needle, $haystack);
        return(empty($beginArray) ? false : array_keys($beginArray)[0]);
    }

    public function processMenuSpecials()
    {
        foreach ($this->htmlFiles as $fileEntry) {
            foreach ($this->placeHolders[$fileEntry] as $templateName => $template) {
                $templatePlace = $this->templatePlaces[$templateName];
                $selfRefIndex = $this->array_search_regex('/.*href *= *"' . $templatePlace . '".*/', $template);
                if ($selfRefIndex === false) {
                    continue;
                }
                $templateString = implode($this->templates[$templateName], PHP_EOL);
                $menuDom = new DOMDocument();
                $menuDom->loadHTML('<?xml encoding="utf-8" ?>' . $templateString);

                $menuPath = new DOMXPath($menuDom);

                $referencedA = $menuPath->query("//a[@href='" . $templatePlace . "']")->item(0);
                $referencedText = $referencedA->textContent;
                $referencedHref = $referencedA->attributes->getNamedItem('href')->value;

                $ownA = $menuPath->query("//a[@href='". $fileEntry . "']")->item(0);
                $ownText = $ownA->textContent;
                $ownHref = $ownA->attributes->getNamedItem('href')->value;

                $newOwnAXml = simplexml_import_dom($referencedA);
                $newOwnAXml[0] = $ownText;
                $newOwnAXml->attributes()['href'] = $ownHref;

                $newReferencedAXml = simplexml_import_dom($ownA);
                $newReferencedAXml[0] = $referencedText;
                $newReferencedAXml->attributes()['href'] = $referencedHref;

                $referencedFragment = $menuDom->createDocumentFragment();
                $referencedFragment->appendXML($newReferencedAXml->asXML());

                $ownFragment = $menuDom->createDocumentFragment();
                $ownFragment->appendXML($newOwnAXml->asXML());

                $referencedA->parentNode->replaceChild($referencedFragment, $referencedA);
                $ownA->parentNode->replaceChild($ownFragment, $ownA);

                $templateString = $menuDom->saveXML($menuDom->documentElement->firstChild->firstChild);

                $modifiedTemplate = explode(PHP_EOL, $templateString);
                $this->templates[$templateName] = $modifiedTemplate;
                $this->processFile($fileEntry);
                $this->templates[$templateName] = $template;

            }
        }
        return $this;
    }
}
