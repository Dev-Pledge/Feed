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
	echo 'open';
	try {
		$connection = new Connection( $request, $connections );
		$connection->pushHistoricalStream();
	} catch ( TypeError | Exception $exception ) {
		echo 'error - ' . $exception->getMessage() . PHP_EOL . $exception->getTraceAsString() . PHP_EOL;
	}

} );


$websocket->on( 'message', function ( swoole_websocket_server $server, $frame ) use ( & $connections ) {

	echo 'MESSAGE' . PHP_EOL;
	try {
		$connections->processFrameIntoConnection( $frame );
		var_dump( $frame );
		echo PHP_EOL;
		$connections->each( function ( Connection $con ) {
			echo 'CON ID:' . $con->getConnectionId() . PHP_EOL;
			$con->push( (object) [ 'hello' => 'there' ] );
		} );
	} catch ( TypeError | Exception $exception ) {
		echo 'error - ' . $exception->getMessage() . PHP_EOL . $exception->getTraceAsString() . PHP_EOL;
	}
} );

$websocket->on( 'close', function ( $ser, $fd ) use ( & $connections ) {
	echo 'remove ' . $fd . PHP_EOL;
	var_dump( $connections );
	$connections->removeConnection( $fd );
	var_dump( $connections );
	echo PHP_EOL;
} );

$websocket->start();



