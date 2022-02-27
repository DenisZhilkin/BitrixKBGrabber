<?php

// require '../../config.php';
require 'classes/Grabber.php';

use local_sandbox\Grabber;

$root = 'https://b24-rgfsgw.bitrix24.ru';


$labels = [
	'opisaniepolnogokursabitriks24',
	'vvedeniechtotakoecrmchtotakoevnedreniecrm'
];

$item = 0;

$location = '/knowledge/bitrix24_edu/' . $labels[$item] . '/?IFRAME=Y';

$grabber = new Grabber($root);
$grabber->getPage($location, $labels[$item]);
