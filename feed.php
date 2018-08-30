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


$websocket = new swoole_websocket_server( getenv( 'API_DOMAIN' ), getenv( 'SWOOLE_PORT' ) );

$connections = new Connections( $websocket, $cache );

$websocket->on( 'open', function ( swoole_websocket_server $server, $request ) use ( & $connections ) {
	$connections->setWebSocketServer( $server );
	echo 'open REQUEST:' . PHP_EOL;
	var_dump( $request );
	try {
		$connection = new Connection( $request, $connections );
		$connection->push( (object) [ 'connection_id' => $connection->getConnectionId() ] );
	} catch ( TypeError | Exception $exception ) {
		echo 'error - ' . $exception->getMessage() . PHP_EOL . $exception->getTraceAsString() . PHP_EOL;
	}

} );


$websocket->on( 'message', function ( swoole_websocket_server $server, $frame ) use ( & $connections ) {
	if ( $frame->data == '.' ) {
		echo '.';
	} else {
		$connections->setWebSocketServer( $server );
		echo 'MESSAGE' . PHP_EOL;
		try {
			var_dump( $frame );
			echo PHP_EOL;
			$connections->processFrameIntoConnection( $frame );
		} catch ( TypeError | Exception $exception ) {
			echo 'error - ' . $exception->getMessage() . PHP_EOL . $exception->getTraceAsString() . PHP_EOL;
		}
	}
} );

$websocket->on( 'close', function ( swoole_websocket_server $server, $fd ) use ( & $connections ) {
	$connections->setWebSocketServer( $server );
	echo 'remove ' . $fd . PHP_EOL;
	$connections->removeConnection( $fd );
	echo PHP_EOL;
} );

$websocket->start();



