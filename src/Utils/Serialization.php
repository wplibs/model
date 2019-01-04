<?php

namespace WPLibs\Model\Utils;

trait Serialization {
	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	abstract public function to_array();

	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	public function toArray() {
		return $this->to_array();
	}

	/**
	 * Convert the object to its JSON representation.
	 *
	 * @param  int $options JSON encode options.
	 * @return string
	 */
	public function toJson( $options = 0 ) {
		return $this->to_json( $options );
	}

	/**
	 * Convert the object to its JSON representation.
	 *
	 * @param  int $options JSON encode options.
	 * @return string
	 */
	public function to_json( $options = 0 ) {
		return json_encode( $this->jsonSerialize(), $options );
	}

	/**
	 * Retrieves the data for JSON serialization.
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		return $this->to_array();
	}
}
