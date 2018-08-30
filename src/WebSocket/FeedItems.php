<?php

namespace DevPledge\WebSocket;


class FeedItems extends AbstractStreamItem {
	/**
	 * @var AbstractStreamItem[]
	 */
	protected $feedItems = [];

	/**
	 * FeedItems constructor.
	 *
	 * @param array|null $feedItems
	 */
	public function __construct( ?array $feedItems = null ) {
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

		foreach ( $feedItems as &$item ) {

			if ( ! $item instanceof AbstractStreamItem ) {
				$item = new FeedItem( $item );
			}
			$items[] = $item;
		}
		$this->feedItems = $items;

		return $this;
	}

	/**
	 * @return AbstractStreamItem[]
	 */
	public function getFeedItems(): array {
		return $this->feedItems;
	}

	/**
	 * @return \stdClass
	 */
	public function toPushData(): \stdClass {
		$data = new \stdClass();

		$data->entitys = [];

		foreach ( $this->feedItems as $item ) {

			$data->entitys[] = $item->toPushData();

		}

		return $data;
	}


	/**
	 * @param \stdClass $data
	 *
	 * @return AbstractStreamItem
	 */
	public function processData( \stdClass $data ): AbstractStreamItem {
		return $this;
	}
}