<?php

namespace DevPledge\WebSocket;


class FeedItems {
	/**
	 * @var FeedItem[]
	 */
	protected $feedItems = [];

	/**
	 * FeedItems constructor.
	 *
	 * @param array|null $feedItems
	 */
	public function __construct( array $feedItems = null ) {
		if ( isset( $feedItems ) ) {
			$this->setFeedItems( $feedItems );
		}
	}

	/**
	 * @param array $feedItems
	 *
	 * @return FeedItems
	 */
	public function setFeedItems( array $feedItems ): FeedItems {
		$items = [];
		$i     = 0;
		foreach ( $feedItems as &$item ) {
			$i ++;
			if ( ! $item instanceof FeedItem ) {
				$item = new FeedItem( $item );
			}
			$items[ $i ] = $item;
		}
		$this->feedItems = $items;

		return $this;
	}

	/**
	 * @return \stdClass
	 */
	public function toPushData() {
		$data = new \stdClass();

		$data->ids = [];

		foreach ( $this->feedItems as $item ) {
			$data->feed_ids[] = $item->getFeedItemId();
		}

		return $data;
	}


}