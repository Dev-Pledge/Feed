<?php

namespace DevPledge\WebSocket;


class FeedItem {
	/**
	 * @var string | null
	 */
	protected $userId;
	/**
	 * @var string | null
	 */
	protected $solutionGroupId;
	/**
	 * @var string | null
	 */
	protected $organisationId;
	/**
	 * @var string
	 */
	protected $feedItemId;
	/**
	 * @var string
	 */
	protected $type;

	const TYPES = [ 'create', 'update' ];

	public function __construct( \stdClass $data ) {
		$this->processData( $data );
	}

	/**
	 * @param \stdClass $data
	 *
	 * @return FeedItem
	 */
	public function processData( \stdClass $data ): FeedItem {
		$keys = [ 'user_id', 'organisation_id', 'solution_group_id', 'id' ];
		foreach ( $keys as $key ) {
			if ( isset( $data->{$key} ) ) {
				switch ( $key ) {
					case 'user_id':
						$this->userId = $data->{$key};
						break;
					case 'organisation_id':
						$this->organisationId = $data->{$key};
						break;
					case 'solution_group_id':
						$this->organisationId = $data->{$key};
						break;
					case 'id':
						$this->feedItemId = $data->{$key};
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
	 * @return null|string
	 */
	public function getSolutionGroupId(): ?string {
		return $this->solutionGroupId;
	}

	/**
	 * @return null|string
	 */
	public function getOrganisationId(): ?string {
		return $this->organisationId;
	}

	/**
	 * @return string
	 */
	public function getFeedItemId(): string {
		return $this->feedItemId;
	}


}