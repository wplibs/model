<?php

namespace WPLibs\Model\Utils;

class Object_Data implements \ArrayAccess {
	/**
	 * Store the object data.
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * Constructor.
	 *
	 * @param  array|object $data The data.
	 * @return void
	 */
	public function __construct( $data ) {
		$this->data = $data;
	}

	/**
	 * Get an item from an array or object.
	 *
	 * @param  string $key     The key name.
	 * @param  mixed  $default The default value.
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		return data_get( $this->data, $key, $default );
	}

	/**
	 * Get the data.
	 *
	 * @return mixed
	 */
	public function data() {
		return $this->data;
	}

	/**
	 * Get the value for a given offset.
	 *
	 * @param  string $offset The offset key.
	 * @return mixed
	 */
	public function offsetGet( $offset ) {
		return $this->get( $offset );
	}

	/**
	 * Determine if the given offset exists.
	 *
	 * @param  string $offset The offset key.
	 * @return bool
	 */
	public function offsetExists( $offset ) {
		$default = new \stdClass;

		return $default !== $this->get( $offset, $default );
	}

	/**
	 * Set the value at the given offset.
	 *
	 * @param  string $offset The offset key.
	 * @param  mixed  $value  The offset value.
	 * @return void
	 */
	public function offsetSet( $offset, $value ) {
		// ...
	}

	/**
	 * Unset the value at the given offset.
	 *
	 * @param  string $offset The offset key.
	 * @return void
	 */
	public function offsetUnset( $offset ) {
		// ...
	}

	/**
	 * Dynamically retrieve the value of an attribute.
	 *
	 * @param  string $key The key name.
	 * @return mixed
	 */
	public function __get( $key ) {
		return $this->get( $key );
	}
}
