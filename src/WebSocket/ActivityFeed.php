<?php

namespace DevPledge\WebSocket;

/**
 * Class ActivityFeed
 * @package DevPledge\WebSocket
 */
class ActivityFeed {
	protected $id;

	public function __construct( $id ) {
		$this->id = $id;
	}

	/**
	 * @return array
	 */
	public function get(): array {
		$key          = 'activity-feed:' . $this->id;
		$activityFeed = \json_decode( Connections::getCache()->get( $key ) );
		if ( is_array( $activityFeed ) ) {
			return $activityFeed;
		}

		return [];
	}
}