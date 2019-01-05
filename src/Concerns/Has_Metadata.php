<?php

namespace WPLibs\Model\Deprecated;

use WPLibs\Model\Core\Term;

trait Has_Metadata {
	/**
	 * Return type of object metadata is for (e.g., comment, post, or user)
	 *
	 * @return string
	 */
	public function get_meta_type() {
		if ( $this instanceof Term ) {
			return 'term';
		}

		if ( isset( $this->meta_type ) ) {
			return $this->meta_type;
		}

		return 'post';
	}

	/**
	 * Get a meta-data by meta key.
	 *
	 * @param  string $meta_key The metadata key.
	 * @param  bool   $single
	 * @return mixed|null
	 */
	public function get_meta( $meta_key, $single = true ) {
		$meta_value = get_metadata( $this->get_meta_type(), $this->get_id(), $meta_key, $single );

		if ( false === $meta_value ) {
			return null;
		}

		return $meta_value;
	}

	/**
	 *  Add meta-data for the current object.
	 *
	 * @param  string $meta_key   Metadata key.
	 * @param  mixed  $meta_value Metadata value. Must be serializable if non-scalar.
	 * @param  bool   $unique
	 * @return int|false
	 */
	public function add_meta( $meta_key, $meta_value, $unique = true ) {
		return add_metadata( $this->get_meta_type(), $this->get_id(), $meta_key, $meta_value, $unique );
	}

	/**
	 * Update metadata.
	 *
	 * @param  string $meta_key   Metadata key.
	 * @param  mixed  $meta_value Metadata value. Must be serializable if non-scalar.
	 * @param  string $prev_value
	 * @return bool
	 */
	public function update_meta( $meta_key, $meta_value, $prev_value = '' ) {
		return update_metadata( $this->get_meta_type(), $this->get_id(), $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Delete metadata.
	 *
	 * @param  string $meta_key Metadata key.
	 * @param  string $meta_value
	 * @param  bool   $delete_all
	 * @return bool
	 */
	public function delete_meta( $meta_key, $meta_value = '', $delete_all = false ) {
		return delete_metadata( $this->get_meta_type(), $this->get_id(), $meta_key, $meta_value, $delete_all );
	}
}
