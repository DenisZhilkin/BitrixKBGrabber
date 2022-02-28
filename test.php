<?php

$s = [
    "BX.setJSList(['/bitrix/js/main/core/core_ajax.js','/bitrix/js/main/core/core_promise.js','/bitrix/js/main/polyfill/promise/js/promise.js','/bitrix/js/main/loadext/loadext.js','/bitrix/js/main/loadext/extension.js','/bitrix/js/main/polyfill/promise/js/promise.js','/bitrix/js/main/polyfill/find/js/find.js','/bitrix/js/main/polyfill/includes/js/includes.js','/bitrix/js/main/polyfill/matches/js/matches.js','/bitrix/js/ui/polyfill/closest/js/closest.js','/bitrix/js/main/polyfill/fill/main.polyfill.fill.js','/bitrix/js/main/polyfill/find/js/find.js','/bitrix/js/main/polyfill/matches/js/matches.js','/bitrix/js/main/polyfill/core/dist/polyfill.bundle.js','/bitrix/js/main/core/core.js','/bitrix/js/main/polyfill/intersectionobserver/js/intersectionobserver.js','/bitrix/js/main/lazyload/dist/lazyload.bundle.js','/bitrix/js/main/polyfill/core/dist/polyfill.bundle.js','/bitrix/js/main/parambag/dist/parambag.bundle.js']);
BX.setCSSList(['/bitrix/js/main/lazyload/dist/lazyload.bundle.css','/bitrix/js/main/parambag/dist/parambag.bundle.css']);",

    ''
];

$content = $s[0];

// $content = str_replace(["'/bitrix/", '"/bitrix/'], ["'", '"'], $content);

$hrefs = [];
if (!preg_match_all("#'[^\s()<>,]+'#", $content, $hrefs)) {
    print('No') . PHP_EOL;
}
// $hrefs = $hrefs[0];

print_r($hrefs);