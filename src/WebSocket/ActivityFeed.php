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
	 * @return \stdClass[]
	 */
	public function get(): array {
		$key          = 'activity-feed:' . $this->id;
		$activityFeed = \json_decode( Connections::getCache()->get( $key ) );
		if ( is_array( $activityFeed ) ) {

			foreach ( $activityFeed as &$item ) {
				if ( ! is_object( $item ) ) {
					$item = (object) [ 'id' => $item, 'parent_id' => null ];
				}
			}

			return $activityFeed;
		}

		return [];
	}
}