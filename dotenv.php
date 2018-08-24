<?php

$dotEnvFile = '.app-dev-env';
if ( isset( $_ENV['production'] ) && $_ENV['production'] == "true" ) {
	$dotEnvFile = '.app-prod-env';
}

//https://github.com/vlucas/phpdotenv
$dotEnv = new \Dotenv\Dotenv( __DIR__ . '/', $dotEnvFile );
$dotEnv->load();
