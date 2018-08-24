<?php

use DevPledge\WebSocket\Connection;
use DevPledge\WebSocket\Connections;
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


$websocket   = new swoole_websocket_server( getenv( 'API_DOMAIN' ), getenv( 'SWOOLE_PORT' ) );
$connections = new Connections( $websocket );

$websocket->on( 'open', function ( swoole_websocket_server $server, $request ) use ( $connections ) {
	try {
		new Connection( $request, $connections );
	} catch ( TypeError | Exception $exception ) {
		echo 'error';
	}
} );

//$websocket->on( 'handshake', function () {
//
//} );

$websocket->on( 'message', function ( swoole_websocket_server $server, $frame ) use ( $connections ) {
	$connection = $connections->processRequestIntoConnection( $frame );


} );

$websocket->on( 'close', function ( $ser, $fd ) use ( $connections ) {
	$connections->removeConnection( $fd );
} );



