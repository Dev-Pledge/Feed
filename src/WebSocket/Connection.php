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
		if ( isset( $request->header['origin'] ) ) {
			$this->origin = $request->header['origin'];
		}
		if ( isset( $request->data ) ) {
			$this->processRawData( $request->data );
		} else {
			Connections::getConnectionsMaster();
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

		echo 'PROCESSING DATA:' . PHP_EOL;
		var_dump( $data );
		echo PHP_EOL;

		if ( isset( $data->user_id ) ) {
			$this->userId = $data->user_id;
		}

		$this->doApiFunction( $data );
		$this->doUiFunctions( $data );
		Connections::getConnectionsMaster();

		return $this;
	}

	public function doApiFunction( \stdClass $data ) {

		if ( ! $this->isFromAPI() ) {
			return null;
		}
		echo 'DoAPIFUNC:' . PHP_EOL;
		var_dump( $data );
		$function = isset( $data->function ) ? $data->function : null;
		switch ( $function ) {
			case 'created-entity':
			case 'updated-entity':
			case 'deleted-entity':
				$feedItem = new FeedItem( $data );
				echo __LINE__ . PHP_EOL;
				var_dump( $feedItem );
				Connections::getConnectionsMaster()->eachUiUser( function ( Connection $con ) use ( $feedItem ) {
					echo '-------';
					echo $con->origin;
					echo '-------';
					$con->pushFeedItem( $feedItem );
				} );
				break;
		}
		$this->push( (object) [ 'done' => true ] );
	}

	public function doUiFunctions( \stdClass $data ) {
		if ( ! $this->isFromUI() ) {
			return null;
		}
		echo 'DoUIFUNC:' . PHP_EOL;
		var_dump( $data );
		$function = isset( $data->function ) ? $data->function : null;
		switch ( $function ) {
			case 'get-feed':
				$this->pushHistoricalStream();
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
		echo 'PUSHING TO ' . $this->getConnectionId() . PHP_EOL;
		var_dump( $data );
		Connections::getWebSocketServer()->push( $this->getConnectionId(), json_encode( $data ) );

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isFromUI(): bool {
		return ( strpos( $this->origin, 'http' ) !== false );
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
		var_dump( $this->origin );
		$followIds = $this->getFollowIds();
		echo 'FOLLOW IDS' . PHP_EOL;
		var_dump( $followIds );
		$relatedIds = $feedItem->getRelatedIds();
		foreach ( $relatedIds as $id ) {
			if ( in_array( $id, $followIds ) ) {
				echo $id . PHP_EOL;
				echo 'SENDING' . PHP_EOL . 'CONID:' . $this->getConnectionId() . ' from ' . $this->origin . PHP_EOL;
				var_dump( $feedItem->toPushData() );
				$this->push( $feedItem->toPushData() );
				Connections::getCache()->set( 'historical-stream:' . $this->userId, serialize( null ) );
				break;
			}
		}

		return $this;

	}

	/**
	 * @return Connection
	 */
	public function pushHistoricalStream(): Connection {
		if ( $this->isFromUI() ) {
			return $this->push( $this->getHistoricalFeedItems()->toPushData() );
		} else {
			echo 'Not from UI ';
			var_dump( $this->origin );
			echo PHP_EOL;
		}

		return $this;
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
		Connections::getCache()->setex( 'historical-stream:' . $this->userId, 60, \json_encode( $historicalFeed ) );

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
		echo 'Historical' . PHP_EOL;
		var_dump( $historicalFeed );
		if ( ! ( is_array( $historicalFeed ) && count( $historicalFeed ) ) ) {

			$historicalFeed = $this->createHistoricalFeedItems( $followIds );
		}
		foreach ( $historicalFeed as &$feedItem ) {
			if ( ! is_object( $feedItem ) ) {
				$feedItem = (object) [ 'id' => $feedItem, 'parent_id' => null ];
			}
			$feedItem = new HistoricalStreamItem( $feedItem );
		}

		return new FeedItems( $historicalFeed );
	}

}