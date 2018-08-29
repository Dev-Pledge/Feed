<?php

namespace DevPledge\WebSocket;

/**
 * Class AbstractStreamItem
 * @package DevPledge\WebSocket
 */
abstract class AbstractStreamItem {
	
	const FUNCTIONS = [ 'created-entity', 'updated-entity', 'deleted-entity', 'historical-stream' ];
	/**
	 * @var string
	 */
	protected $id;
	/**
	 * @var
	 */
	protected $parentId;

	public function __construct( \stdClass $data ) {
		$this->processData( $data );
	}

	/**
	 * @param \stdClass $data
	 *
	 * @return AbstractStreamItem
	 */
	abstract public function processData( \stdClass $data ): AbstractStreamItem;

	/**
	 * @return null|string
	 */
	public function getId(): ?string {
		return $this->id;
	}

	/**
	 * @return null|string
	 */
	public function getParentId(): ?string {
		return $this->parentId;
	}

	abstract public function toPushData(): \stdClass;

}