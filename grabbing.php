<?php

const CLI_SCRIPT = 1;

require 'config.php';
require 'classes/Grabber.php';

use BitrixKB\Grabber;


$labels = [
	'opisaniepolnogokursabitriks24',
	'vvedeniechtotakoecrmchtotakoevnedreniecrm'
];

$item = 0;

$location = '/knowledge/' . $kbName . '/' . $labels[$item] . '/?IFRAME=Y';

$grabber = new Grabber($root);
$grabber->getPage($location, $labels[$item]);
