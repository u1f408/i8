<?php
require_once(dirname(dirname(($p = realpath(__FILE__)) === false ? __FILE__ : $p)) . '/vendor/autoload.php');

$app = new \i8();
$app->app->run();
