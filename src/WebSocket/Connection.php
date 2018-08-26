<?php

namespace DevPledge\WebSocket;

use Swoole\Http\Request;
use Swoole\WebSocket\Frame;

/**
 * Class Connection
 * @package DevPledge\WebSocket\Topic
 */
class Connection {
	/**
	 * @var string
	 */
	protected $userId;
	/**
	 * @var string
	 */
	protected $connectionId;
	/**
	 * @var string
	 */
	protected $origin;


	/**
	 * Connection constructor.
	 *
	 * @param \stdClass $request
	 * @param Connections $connections
	 */
	public function __construct( Request $request, Connections $connections ) {
		if ( isset( $request->fd ) ) {
			$this->connectionId = $request->fd;
			$connections->addConnection( $this );
		}
		if ( isset( $request->data ) ) {
			$this->processRawData( $request->data );
		}
	}

	/**
	 * @param Frame $request
	 *
	 * @return Connection
	 */
	public function processFrame( Frame $request ): Connection {
		if ( isset( $request->data ) ) {
			$this->processRawData( $request->data );
		}

		return $this;
	}

	/**
	 * @param string $rawData
	 *
	 * @return Connection
	 */
	public function processRawData( string $rawData ): Connection {
		$data = \json_decode( $rawData );

		if ( isset( $data->user_id ) ) {
			$this->userId = $data->user_id;
		}
		if ( isset( $data->origin ) ) {
			$this->origin = $data->origin;
		}

		return $this;
	}

	/**
	 * @return string
	 */
	public function getUserId(): string {
		return $this->userId;
	}

	/**
	 * @return bool
	 */
	public function isUser(): bool {
		return (bool) isset( $this->userId );
	}

	/**
	 * @return string
	 */
	public function getConnectionId(): string {
		return $this->connectionId;
	}

	/**
	 * @param \stdClass $data
	 *
	 * @return Connection
	 */
	public function push( \stdClass $data ): Connection {
		Connections::getWebSocketServer()->push( $this->getConnectionId(), json_encode( $data ) );

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isFromUI(): bool {
		return ( $this->origin == 'ui' );
	}

	public function isFromAPI(): bool {
		return ( $this->origin == 'api' );
	}



}