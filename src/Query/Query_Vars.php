<?php

namespace WPLibs\Model\Query;

class Query_Vars implements \ArrayAccess {
	/**
	 * The query vars array.
	 *
	 * @var array
	 */
	public $query_vars = [];

	/**
	 * Constructor.
	 *
	 * @param array $main_query The main query vars.
	 */
	public function __construct( array $main_query = [] ) {
		$this->query_vars = $main_query;
	}

	/**
	 * Merge the current query_vars with given value.
	 *
	 * @param  array $query_vars The query vars.
	 * @return $this
	 */
	public function with( array $query_vars ) {
		$this->query_vars = array_merge( $this->query_vars, $query_vars );

		return $this;
	}

	/**
	 * Returns all query vars.
	 *
	 * @return array
	 */
	public function to_array() {
		return $this->query_vars;
	}

	/**
	 * Handle dynamic calls to set query vars.
	 *
	 * @param  string $name       The query name.
	 * @param  array  $parameters The query value.
	 * @return $this
	 */
	public function __call( $name, $parameters ) {
		$value = count( $parameters ) > 0 ? $parameters[0] : true;

		$this->query_vars[ $name ] = $value;

		return $this;
	}

	/**
	 * Determine if the given offset exists.
	 *
	 * @param  string $offset The offset key.
	 * @return bool
	 */
	public function offsetExists( $offset ) {
		return isset( $this->query_vars[ $offset ] );
	}

	/**
	 * Get the value for a given offset.
	 *
	 * @param  string $offset The offset key.
	 * @return mixed
	 */
	public function offsetGet( $offset ) {
		return isset( $this->query_vars[ $offset ] ) ? $this->query_vars[ $offset ] : null;
	}

	/**
	 * Set the value at the given offset.
	 *
	 * @param  string $offset The offset key.
	 * @param  mixed  $value  The offset value.
	 * @return void
	 */
	public function offsetSet( $offset, $value ) {
		$this->query_vars[ $offset ] = $value;
	}

	/**
	 * Unset the value at the given offset.
	 *
	 * @param  string $offset The offset key.
	 * @return void
	 */
	public function offsetUnset( $offset ) {
		unset( $this->query_vars[ $offset ] );
	}

	/**
	 * Dynamically retrieve the value of a query.
	 *
	 * @param  string $key The key name.
	 * @return mixed
	 */
	public function __get( $key ) {
		return $this->offsetGet( $key );
	}

	/**
	 * Dynamically set the value of a query.
	 *
	 * @param  string $key   The key name.
	 * @param  mixed  $value The key value.
	 * @return void
	 */
	public function __set( $key, $value ) {
		$this->offsetSet( $key, $value );
	}

	/**
	 * Dynamically check if a query is set.
	 *
	 * @param  string $key The key name.
	 * @return bool
	 */
	public function __isset( $key ) {
		return $this->offsetExists( $key );
	}

	/**
	 * Dynamically unset a query.
	 *
	 * @param  string $key The key name.
	 * @return void
	 */
	public function __unset( $key ) {
		$this->offsetUnset( $key );
	}
}
