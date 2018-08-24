<?php

use Predis\Client;
use Predis\Collection\Iterator\HashKey;
use Predis\Collection\Iterator\Keyspace;


require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/dotenv.php';

$cache = new Client( [
	'scheme' => 'tcp',
	'host'   => 'cache',
	'port'   => 6379,
] );

$http = new swoole_http_server( getenv( 'API_DOMAIN' ), getenv( 'SWOOLE_PORT' ) );
$http->on( 'request', function ( $request, $response ) use ( $cache ) {

	$keyp   = ltrim( $request->server['request_uri'], '/' );
	$result = '<pre>';
	foreach ( new Keyspace( $cache, 'usrn:*', 3 ) as $index => $key ) {
		$result = $result . $cache->get($key)."\r\n";
	}
//	$result = $cache->get( $keyp );
	$response->end( "<h1>FEED Hello Devpledge Swoole Test. #" . rand( 1000, 9999 ) . "</h1>" . print_r( $result, true ) );

} );
$http->start();