<?php

namespace DevPledge\WebSocket;


use Predis\Client;
use Swoole\WebSocket\Frame;

/**
 * Class Connections
 * @package DevPledge\WebSocket
 */
class Connections {
	/**
	 * @var Connection[]
	 */
	protected $connections = [];

	/**
	 * @var \swoole_websocket_server
	 */
	protected static $websocketServer;
	/**
	 * @var Client
	 */
	protected static $cache;
	/**
	 * @var Connections
	 */
	protected static $connectionsMaster;

	/**
	 * Connections constructor.
	 *
	 * @param \swoole_websocket_server $websocketServer
	 * @param Client $cache
	 */
	public function __construct( \swoole_websocket_server $websocketServer, Client $cache ) {
		static::$websocketServer   = $websocketServer;
		static::$cache             = $cache;
		static::$connectionsMaster = $this;
	}

	/**
	 * @param Connection $connection
	 *
	 * @return Connections
	 */
	public function addConnection( Connection $connection ): Connections {
		$this->connections[ $connection->getConnectionId() ] =& $connection;

		return $this;
	}

	/**
	 * @param Frame $request
	 *
	 * @return Connection|null
	 */
	public function getConnectionByFrame( Frame $frame ): ?Connection {
		if ( isset( $frame->fd ) ) {
			return $this->getConnectionByConnectionId( $frame->fd );

		}

		return null;
	}

	/**
	 * @param int $connectionId
	 *
	 * @return Connection|null
	 */
	public function getConnectionByConnectionId( int $connectionId ): ?Connection {
		if ( isset( $this->connections[ $connectionId ] ) ) {
			return $this->connections[ $connectionId ];
		}

		return null;
	}

	/**
	 * @param Frame $request
	 *
	 * @return Connection|null
	 */
	public function processFrameIntoConnection( Frame $frame ): ?Connection {

		$connection = $this->getConnectionByFrame( $frame );
		if ( ! is_null( $connection ) ) {
			return $connection->processFrame( $frame );
		}

		return null;
	}

	/**
	 * @param int $connectionId
	 *
	 * @return Connections
	 */
	public function removeConnection( int $connectionId ): Connections {
		if ( isset( $this->connections[ $connectionId ] ) ) {
			unset( $this->connections[ $connectionId ] );
		}

		return $this;
	}

	/**
	 * @param \Closure $function
	 *
	 * @return Connections
	 */
	public function each( \Closure $function ): Connections {
		if ( count( $this->connections ) ) {
			foreach ( $this->connections as $connection ) {
				try {
					$function( $connection );
				} catch ( \Exception | \TypeError $exception ) {
					echo 'each error ' . PHP_EOL . $exception->getMessage() . PHP_EOL . $exception->getTraceAsString();

					return $this;
				}
			}
		}

		return $this;
	}

	/**
	 * @param \Closure $function
	 *
	 * @return Connections
	 */
	public function eachUiUser( \Closure $function ): Connections {
		$this->each( function ( Connection $con ) use ( $function ) {
			if ( $con->isUser() && $con->isFromUI() ) {
				$function( $con );
			}
		} );

		return $this;
	}

	/**
	 * @return \swoole_websocket_server
	 */
	public static function getWebSocketServer(): \swoole_websocket_server {
		return static::$websocketServer;
	}

	/**
	 * @return Client
	 */
	public static function getCache(): Client {
		return static::$cache;
	}

	public function pushFeedItems( FeedItems $feedItems ) {


		$this->eachUiUser( function ( Connection $con ) use ( $feedItems ) {
			$con->push( $feedItems->toPushData() );
		} );
	}

	/**
	 * @return Connections
	 */
	public static function getConnectionsMaster() {
		return static::$connectionsMaster;
	}

}