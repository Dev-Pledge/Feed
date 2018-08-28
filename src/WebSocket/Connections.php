<?php

namespace DevPledge\WebSocket;


use Swoole\WebSocket\Frame;

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
	 * Connections constructor.
	 *
	 * @param swoole_websocket_server $websocketServer
	 */
	public function __construct( \swoole_websocket_server $websocketServer ) {
		static::$websocketServer = $websocketServer;
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
					return null;
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

	public function pushFeedItems( FeedItems $feedItems ) {


		$this->eachUiUser( function ( Connection $con ) use ( $feedItems ) {
			$con->push( $feedItems->toPushData() );
		} );
	}
}