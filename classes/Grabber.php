<?php

declare(strict_types=1);

namespace BitrixKB;

use DOMDocument;
use DOMComment;
use DOMEntityReference;
use DOMText;
use DOMDocumentType;
use Exception;

class Grabber
{
    # without ending /
    protected string $root;
    protected string $destinationFolder;

    function __construct(string $root)
    {
        global $destinationFolder;

        $this->root = $root;
        $this->destinationFolder = $destinationFolder;
        if (!is_dir($this->destinationFolder)) {
            if (!mkdir($this->destinationFolder, 0777)) {
                throw new Exception('Can`t create destination folder');
            }
        }
    }

    # /{location} : starting /
    protected function getHTML(string $location): string
    {
        $url = $this->root . $location;
        $headers = [
            'Accept: */*',
            'Connection: keep-alive',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'Sec-Fetch-Site: same-origin',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:97.0) Gecko/20100101 Firefox/97.0',
            'Authorization: Basic ' . base64_encode('postdvz@yandex.ru:shikaka')
        ]; 

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_SSL_VERIFYHOST  => false,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_HTTPHEADER      => $headers
        ]);
        $html = curl_exec($ch);
        curl_close($ch);

        if ($html === false) {
            throw new Exception(__FUNCTION__ . ': CURL failed');
        }
        return $html;
    }

    protected function saveToFile(string $dir, string $file, string $href): void
    {
        $full = $this->destinationFolder . DIRECTORY_SEPARATOR . $dir;
        if (!is_dir($full)) {
            if (!mkdir($full, 0777, true)) {
                throw new Exception(__FUNCTION__ . ': Can`t create directory');
            }
        }

        $file = $full . DIRECTORY_SEPARATOR . $file;
        if (!file_exists($file)) {
            $content = file_get_contents($href);
            if ($content === false) {
                // throw new Exception(__FUNCTION__ . ': Can`t get source file content: ' . $href);
                // pr(__FUNCTION__ . ': Can`t get source file content: ' . $href);
                print(__FUNCTION__ . ': file: ' . $file . PHP_EOL);
                $content = '';
            }
            $content = $this->preprocessContent($content);
            file_put_contents($file, $content);
        }
    }

    # save 
    protected function getSource(string $href): string
    {
        if ($clean = strstr($href, '?', true)) {
            $path = pathinfo($clean);
        } else {
            $path = pathinfo($href);
        }
        $dir = $path['dirname'];
        $dir = str_replace('https://', '', $dir);
        if (strpos($dir, '/bitrix/') === 0) {
            $off = strlen('/bitrix/');
            $dir = substr($dir, $off);
        }
        if (key_exists('extension', $path)) {
            if  ($path['extension'] == 'php') {
                return $href;
            }
            $file = $path['filename'] . '.' . $path['extension'];
        } else {
            $file = $path['filename'];
        }
        /*
        print(PHP_EOL);
        print($href . PHP_EOL);
        print($dir  . PHP_EOL);
        print($file . PHP_EOL);
        print(PHP_EOL);
        /**/
        if (ord($href) == 47) { # 47 is /
            $href = $this->root . $href;
        }
        
        $this->saveToFile($dir, $file, $href);
        
        $localRef = $dir . DIRECTORY_SEPARATOR . $file;
        return $localRef;
    }

    # relocate content from links
    # rewrite urls
    protected function preprocessContent(string $content): string
    {
        # extract links
        $links = [];
        // (https:\/\/|\/bitrix\/)[^\s()<>,'\"]+ too much
        // (https:\/\/bitrix|\/bitrix\/)[^\s()<>,'\"]+
        if (preg_match_all("/(https:\/\/bitrix|\/bitrix\/)[^\s()<>,'\"]+/", $content, $links) === false) {
            throw new Exception('Can`t extract url from content');
        }
        $links = $links[0];
        // print_r($links);
        # get and save file, rewrite links
        foreach ($links as $link) {
            $localLink = $this->getSource($link);
            $content = str_replace($link, $localLink, $content);
        }

        return $content;
    }

    protected function rewriteUrls($nodeList): void
    {
        foreach ($nodeList as $node) {
            if ($node instanceof DOMDocumentType || $node instanceof DOMText || $node instanceof DOMComment || $node instanceof DOMEntityReference || $node->nodeName == 'a') {
                continue;
            }
            if ($href = $node->getAttribute('href')) {
                $localUrl = $this->getSource($href);
                $node->setAttribute('href', $localUrl);
            } elseif ($src = $node->getAttribute('src')) {
                $localUrl = $this->getSource($src);
                $node->setAttribute('src', $localUrl);
            } elseif (in_array($node->nodeName, ['style', 'script'])) {
                $node->nodeValue = $this->preprocessContent($node->nodeValue);
            }
            if ($style = $node->getAttribute('style')) {
                $style = $this->preprocessContent($style);
                $node->setAttribute('style', $style);
            }
            if ($node->hasChildNodes()) {
                $this->rewriteUrls($node->childNodes);
            }
        }
    }

    public function getPage(string $location, string $pageName): void
    {
        $html = $this->getHTML($location);
        $dom = new DOMDocument('1.0', 'utf-8');
        @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        # Убираем шапку и подвал от Битрикса
        $body = $dom->getElementsByTagName('body')->item(0);
        foreach ($body->getElementsByTagName('div') as $div) {
            $classes = $div->getAttribute('class');
            $classes = explode(' ', $classes);
            if ($a = array_intersect($classes, ['landing-pub-top-panel-wrapper', 'bitrix-footer', 'landing-sidebar'])) {
                if ($div->parentNode->removeChild($div) === false) {
                    throw new Exception('Removing failed');
                }
            }
        }

        # Сохраняем локально контент и заменяем ссылки на него в html
        $this->rewriteUrls($dom->childNodes);

        $file = $this->destinationFolder . DIRECTORY_SEPARATOR . $pageName . '.html';
        if (!$dom->saveHTMLFile($file)) {
            throw new Exception(__FUNCTION__ . ': Can`t save html to file');
        }
    }
}