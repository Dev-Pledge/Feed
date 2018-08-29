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
	 * @var string | null
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

		$this->doApiFunction( $data );


		return $this;
	}

	public function doApiFunction( \stdClass $data ) {
		if ( ! $this->isFromAPI() ) {
			return null;
		}
		$function = isset( $data->function ) ? $data->function : null;
		switch ( $function ) {
			case 'created-entity':
			case 'updated-entity':
			case 'deleted-entity':
				$feedItem = new FeedItems( $data );
				Connections::getConnectionsMaster()->each( function ( Connection $con ) use ( $feedItem ) {
					$con->pushFeedItem( $feedItem );
				} );
				break;
		}
	}

	/**
	 * @return string
	 */
	public function getUserId(): ?string {
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

	/**
	 * @return array
	 */
	public function getFollowIds(): array {
		$array = \json_decode( Connections::getCache()->get( 'follows:' . $this->getUserId() ) );

		if ( is_array( $array ) ) {
			return $array;
		}

		return [];
	}

	/**
	 * @param FeedItem $feedItem
	 *
	 * @return Connection
	 */
	public function pushFeedItem( FeedItem $feedItem ): Connection {
		$followIds  = $this->getFollowIds();
		$relatedIds = $feedItem->getRelatedIds();
		foreach ( $relatedIds as $id ) {
			if ( in_array( $id, $followIds ) ) {
				$this->push( $feedItem->toPushData() );
				break;
			}
		}

		return $this;

	}

	/**
	 * @return Connection
	 */
	public function pushHistoricalStream(): Connection {
		return $this->push( $this->getHistoricalFeedItems()->toPushData() );
	}

	/**
	 * @param array|null $followIds
	 *
	 * @return array
	 */
	protected function createHistoricalFeedItems( ?array $followIds = null ): array {
		$followIds      = $followIds ?? $this->getFollowIds();
		$historicalFeed = [];
		foreach ( $followIds as $followId ) {
			$activity       = array_slice( ( new ActivityFeed( $followId ) )->get(), 0, 5 );
			$historicalFeed = array_merge( $historicalFeed, $activity );
		}
		shuffle( $historicalFeed );
		Connections::getCache()->set( 'historical-stream:' . $this->userId, \json_encode( $historicalFeed ) );

		return $historicalFeed;
	}

	/**
	 * @param array|null $followIds
	 *
	 * @return FeedItems
	 */
	public function getHistoricalFeedItems( ?array $followIds = null ): FeedItems {
		$followIds      = $followIds ?? $this->getFollowIds();
		$historicalFeed = \json_decode( Connections::getCache()->get( 'historical-stream:' . $this->userId ) );
		if ( ! ( is_array( $historicalFeed ) && count( $historicalFeed ) ) ) {

			$historicalFeed = $this->createHistoricalFeedItems( $followIds );
		}
		foreach ( $historicalFeed as &$feedItem ) {
			$feedItem = new HistoricalStreamItem( $feedItem );
		}

		return new FeedItems( $historicalFeed );
	}

}