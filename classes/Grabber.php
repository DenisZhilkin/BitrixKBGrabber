<?php

declare(strict_types=1);

namespace BitrixKB;

use DOMDocument;
use Exception;

class Grabber
{
    # without ending /
    protected const DESTINATION_DIR = 'Bitrix';
    protected string $root;
    protected string $destinationFolder;

    function __construct(string $root)
    {
        global $CFG;
        $dataRoot = $CFG->dataroot;
        // $dataRoot = '/home/denis/BitrixKBGrabber';

        $this->root = $root;
        $this->destinationFolder = $dataRoot . DIRECTORY_SEPARATOR . static::DESTINATION_DIR;
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
                throw new Exception(__FUNCTION__ . ': Can`t get source file content');
            }
            $content = $this->preprocessContent($content);
            file_put_contents($file, $content);
        }
    }

    # save 
    protected function getSource(string $href): string
    {
        $path = pathinfo($href);
        $dir = $path['dirname'];
        $dir = str_replace(['/bitrix/', 'https://'], '', $dir);
        if (key_exists('extension', $path)) {
            $clean = strstr($path['extension'], '?', true);
            $ext = $clean ? $clean : $path['extension'];
            $file = $path['filename'] . '.' . $ext;
        } else {
            $clean = strstr($path['filename'], '?', true);
            $file = $clean ? $clean : $path['filename'];
        }

        print($href) . PHP_EOL;
        print($dir)  . PHP_EOL;
        print($file) . PHP_EOL;

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
        if (!preg_match_all("#'[^\s()<>,]+'#", $content, $links)) {
            throw new Exception('Can`t extract url from content');
        }
        $links = $links[0];
        # get and save file, rewrite links
        foreach ($links as $link) {
            $localLink = $this->getSource($link);
            $content = str_replace($link, $localLink, $content);
        }

        return $content;
    }

    public function getPage(string $location, string $pageName): void
    {
        $html = $this->getHTML($location);
        $dom = new DOMDocument('1.0', 'utf-8');
        @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        # Убираем шапку и подвал от Битрикса
        $body = $dom->getElementsByTagName('body')->item(0);
        foreach ($body->getElementsByTagName('div') as $div) {
            if (in_array($div->getAttribute('class'), ['landing-pub-top-panel-wrapper', 'bitrix-footer'])) {
                if ($body->removeChild($div) === false) {
                    throw new Exception('Removing failed');
                }
            }
        }

        foreach ($dom->getElementsByTagName('link') as $link) {     
            $href = $link->getAttribute('href');
            $localRef = $this->getSource($href);
            $link->setAttribute('href', $localRef);
        }

        foreach ($dom->getElementsByTagName('script') as $script) {
            if ($src = $script->getAttribute('src')) {
                $localSrc = $this->getSource($src);
                $script->setAttribute('src', $localSrc);
            } else {
                $script->nodeValue = $this->preprocessContent($script->nodeValue);
            }
        }

        $file = $this->destinationFolder . DIRECTORY_SEPARATOR . $pageName . '.html';
        if (!$dom->saveHTMLFile($file)) {
            throw new Exception(__FUNCTION__ . ': Can`t save html to file');
        }
    }
}