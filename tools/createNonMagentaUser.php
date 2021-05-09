<?php
require_once(dirname(__DIR__) . '/vendor/autoload.php');
$app = new \i8();

if ($argc < 2) {
	echo "⭕  Please provide an email address as a command-line parameter!\n";
	exit();
}

$email = strtolower(trim($argv[1]));
$external_id = $argc >= 3 ? $argv[2] : \i8Helpers::generate_token();

if (($user = \i8Helpers::user_get_or_create($external_id, $email)) === null) {
	echo "❌  Creating the user failed!\n\n";
	echo var_dump($user);
	exit();
}

echo "✔️  User created! API key: {$user['apikey']}\n";
