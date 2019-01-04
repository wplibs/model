<?php

namespace WPLibs\Model;

class Collection extends \Illuminate\Support\Collection {
	/**
	 * Map the values into a new class.
	 *
	 * @param  string $class The class name.
	 *
	 * @return static
	 */
	public function map_into( $class ) {
		return $this->map( function ( $value, $key ) use ( $class ) {
			return new $class( $value, $key );
		} );
	}
}
