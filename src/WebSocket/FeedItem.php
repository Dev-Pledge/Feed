<?php

namespace DevPledge\WebSocket;


class FeedItem extends AbstractStreamItem {
	/**
	 * @var string | null
	 */
	protected $userId;
	/**
	 * @var array | null
	 */
	protected $relatedIds;

	/**
	 * @var string
	 */
	protected $function;


	/**
	 * @param \stdClass $data
	 *
	 * @return FeedItem
	 */
	public function processData( \stdClass $data ): AbstractStreamItem {
		$keys = [ 'user_id', 'id', 'parent_id', 'related_ids', 'function' ];
		foreach ( $keys as $key ) {
			if ( isset( $data->{$key} ) ) {
				switch ( $key ) {
					case 'user_id':
						$this->userId = $data->{$key};
						break;
					case 'id':
						$this->id = $data->{$key};
						break;
					case 'related_ids':
						if ( is_array( $data->{$key} ) ) {
							$this->relatedIds = $data->{$key};
						}
						break;
					case 'parent_id':
						$this->parentId = $data->{$key};
						break;
					case 'function':
						$this->function = $data->{$key};
						break;
				}
			}
		}

		return $this;
	}

	/**
	 * @return null|string
	 */
	public function getUserId(): ?string {
		return $this->userId;
	}


	/**
	 * @return array
	 */
	public function getRelatedIds(): array {
		return isset( $this->relatedIds ) ? $this->relatedIds : [];
	}


	public function toPushData(): \stdClass {
		return (object) [
			'id'        => $this->getId(),
			'parent_id' => $this->getParentId(),
			'function'  => $this->getFunction()
		];
	}

	/**
	 * @return string
	 */
	public function getFunction(): ?string {
		return isset( $this->function ) ? $this->function : 'historical-stream';
	}
}