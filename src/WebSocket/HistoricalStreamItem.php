<?php

namespace DevPledge\WebSocket;


class HistoricalStreamItem extends AbstractStreamItem {

	/**
	 * @param \stdClass $data
	 *
	 * @return AbstractStreamItem
	 */
	public function processData( \stdClass $data ): AbstractStreamItem {
		if ( isset( $data->id ) ) {
			$this->id = $data->id;
		}
		if ( isset( $data->parent_id ) ) {
			$this->parentId = $data->parent_id;
		}

		return $this;
	}

	/**
	 * @return \stdClass
	 */
	public function toPushData(): \stdClass {
		return (object) [
			'id'        => $this->getId(),
			'parent_id' => $this->getParentId(),
			'function'  => 'historical-stream'
		];
	}
}