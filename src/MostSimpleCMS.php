<?php
namespace MostSimpleCMS;
use DOMDocument;
use DOMXPath;

/**
 * Class MostSimpleCMS
 * @package MostSimpleCMS
 * @author Thomas Jez
 */
class MostSimpleCMS
{
    /**
     * array of all templates
     * @var array
     */
    public $templates = array();

    /**
     * array of all placeholders
     * @var array
     */
    public $placeHolders = array();

    /**
     * array of the files containing the templates
     * @var array
     */
    public $templatePlaces = array();

    /**
     * array of all html files of the webpage
     * @var array
     */
    public $htmlFiles = array();

    /**
     * wrapper method for the part of the "dumb copying"
     * @return $this
     */
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

    /**
     * collects the templates from one file and put them into an array
     * @param string $fileName
     * @return $this
     */
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

    /**
     * applies all templates to all corresponding templates of one file
     * @param string $fileName
     * @return $this
     */
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

    /**
     * @param string $needle
     * @param $string haystack
     * @return bool
     */
    public function array_search_regex($needle, $haystack) {
        $beginArray = preg_grep($needle, $haystack);
        return(empty($beginArray) ? false : array_keys($beginArray)[0]);
    }

    /**
     * handles menus which depend on the active url
     * @return $this
     */
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
