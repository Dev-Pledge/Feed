<?php

namespace DevPledge\WebSocket;

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
	 * Connection constructor.
	 *
	 * @param \stdClass $request
	 * @param Connections $connections
	 */
	public function __construct( \stdClass $request, Connections $connections ) {
		if ( isset( $request->fd ) ) {
			$this->connectionId = $request->fd;
			$connections->addConnection( $this );
		}
		if ( isset( $request->data ) ) {
			$this->processRawData( $request->data );
		}
	}

	/**
	 * @param \stdClass $request
	 *
	 * @return Connection
	 */
	public function processRequest( \stdClass $request ): Connection {
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
		Connections::getWebSocketServer()->push( $this . $this->getConnectionId(), json_encode( $data ) );

		return $this;
	}


}