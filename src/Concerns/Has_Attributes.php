<?php

namespace WPLibs\Model\Concerns;

use Illuminate\Support\Arr;

trait Has_Attributes {
	/**
	 * The attributes for this object.
	 *
	 * Name value pairs (name + default value).
	 *
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * The object attributes original state.
	 *
	 * @var array
	 */
	protected $original = [];

	/**
	 * The changed object attributes.
	 *
	 * @var array
	 */
	protected $changes = [];

	/**
	 * Get an attribute from this object.
	 *
	 * @param  string $key Attribute key name.
	 * @return mixed|null
	 */
	public function get_attribute( $key ) {
		if ( 'id' === $key ) {
			return $this->get_key();
		}

		return array_key_exists( $key, $this->attributes )
			? $this->attributes[ $key ]
			: null;
	}

	/**
	 * Sets an attribute to new value.
	 *
	 * @param  string $key   Name of attribute to set.
	 * @param  mixed  $value Value of new attribute.
	 * @return $this
	 */
	public function set_attribute( $key, $value ) {
		$this->attributes[ $key ] = $this->sanitize_attribute( $key, $value );

		return $this;
	}

	/**
	 * Santize attribute value before set.
	 *
	 * @param  string $key   Attribute key name.
	 * @param  mixed  $value Attribute value.
	 * @return mixed
	 */
	protected function sanitize_attribute( $key, $value ) {
		return apply_filters( $this->prefix( 'sanitize_attribute' ), $value, $key, $this );
	}

	/**
	 * Get all of the current attributes on the object.
	 *
	 * @return array
	 */
	public function get_attributes() {
		return $this->attributes;
	}

	/**
	 * Set the array of object attributes.
	 *
	 * @param  array $attributes The object attributes.
	 * @param  bool  $sync       Sync original after.
	 * @return $this
	 */
	public function set_raw_attributes( array $attributes, $sync = false ) {
		$this->attributes = $attributes;

		if ( $sync ) {
			$this->sync_original();
		}

		return $this;
	}

	/**
	 * Sync the original attributes with the current.
	 *
	 * @return $this
	 */
	public function sync_original() {
		$this->original = $this->attributes;

		return $this;
	}

	/**
	 * Get the model's original attribute values.
	 *
	 * @param  string|null $key
	 * @param  mixed       $default
	 * @return mixed|array
	 */
	public function get_original( $key = null, $default = null ) {
		return Arr::get( $this->original, $key, $default );
	}

	/**
	 * Get a subset of the model's attributes.
	 *
	 * @param  array|mixed $attributes The only attributes.
	 * @return array
	 */
	public function only( $attributes ) {
		$results = [];

		foreach ( is_array( $attributes ) ? $attributes : func_get_args() as $attribute ) {
			$results[ $attribute ] = $this->get_attribute( $attribute );
		}

		return $results;
	}

	/**
	 * Sync a single original attribute with its current value.
	 *
	 * @param  string $attribute The attribute to sync.
	 * @return $this
	 */
	public function sync_original_attribute( $attribute ) {
		if ( array_key_exists( $attribute, $this->attributes ) ) {
			$this->original[ $attribute ] = $this->attributes[ $attribute ];
		}

		return $this;
	}

	/**
	 * Revert a attribute to the original value.
	 *
	 * @param  string $attribute The attribute to revert.
	 * @return $this
	 */
	public function revert_attribute( $attribute ) {
		if ( array_key_exists( $attribute, $this->attributes ) ) {
			$this->attributes[ $attribute ] = $this->original[ $attribute ];
		}

		return $this;
	}

	/**
	 * Sync the changed attributes.
	 *
	 * @return $this
	 */
	public function sync_changes() {
		$this->changes = $this->get_dirty();

		return $this;
	}

	/**
	 * Determine if the model or given attribute(s) have been modified.
	 *
	 * @param  array|string|null $attributes Optional, the attribute(s) for determine.
	 * @return bool
	 */
	public function is_dirty( $attributes = null ) {
		return $this->has_changes(
			$this->get_dirty(), is_array( $attributes ) ? $attributes : func_get_args()
		);
	}

	/**
	 * Determine if the model or given attribute(s) have remained the same.
	 *
	 * @param  array|string|null $attributes Optional, the attribute(s) for determine.
	 * @return bool
	 */
	public function is_clean( $attributes = null ) {
		return ! $this->is_dirty( ...func_get_args() );
	}

	/**
	 * Determine if the model or given attribute(s) have been modified.
	 *
	 * @param  array|string|null $attributes Optional, the attribute(s) for determine.
	 * @return bool
	 */
	public function was_changed( $attributes = null ) {
		return $this->has_changes(
			$this->get_changes(), is_array( $attributes ) ? $attributes : func_get_args()
		);
	}

	/**
	 * Determine if the given attributes were changed.
	 *
	 * @param  array             $changes    An array attributes was change.
	 * @param  array|string|null $attributes Optional, the attribute(s) for determine.
	 * @return bool
	 */
	protected function has_changes( array $changes, $attributes = null ) {
		// If no specific attributes were provided, we will just see if the dirty array
		// already contains any attributes. If it does we will just return that this
		// count is greater than zero. Else, we need to check specific attributes.
		if ( empty( $attributes ) ) {
			return count( $changes ) > 0;
		}

		// Here we will spin through every attribute and see if this is in the array of
		// dirty attributes. If it is, we will return true and if we make it through
		// all of the attributes for the entire array we will return false at end.
		foreach ( (array) $attributes as $attribute ) {
			if ( array_key_exists( $attribute, $changes ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the attributes that have been changed since last sync.
	 *
	 * @return array
	 */
	public function get_dirty() {
		$dirty = [];

		foreach ( $this->get_attributes() as $key => $value ) {
			if ( ! $this->original_is_equivalent( $key, $value ) ) {
				$dirty[ $key ] = $value;
			}
		}

		return $dirty;
	}

	/**
	 * Get the attributes that was changed.
	 *
	 * @return array
	 */
	public function get_changes() {
		return $this->changes;
	}

	/**
	 * Determine if the new and old values for a given key are equivalent.
	 *
	 * @param  string $key     The attribute key name.
	 * @param  mixed  $current Current attribute value.
	 * @return bool
	 */
	protected function original_is_equivalent( $key, $current ) {
		if ( ! array_key_exists( $key, $this->original ) ) {
			return false;
		}

		$original = $this->get_original( $key );

		if ( $current === $original ) {
			return true;
		}

		if ( is_null( $current ) ) {
			return false;
		}

		// Binary safe string comparison for numberic attribute.
		return is_numeric( $current ) && is_numeric( $original ) &&
			strcmp( (string) $current, (string) $original ) === 0;
	}
}
