<?php

namespace WPLibs\Model\Deprecated;

use WPLibs\Model\Post;
use WPLibs\Model\Term;

trait Metadata {
	/**
	 * An array of attributes mapped with metadata.
	 *
	 * @var array
	 */
	protected $maps = [];

	/**
	 * Store normalized of mapping metadata.
	 *
	 * @var array
	 */
	protected $mapping = [];

	/**
	 * Return type of object metadata is for (e.g., comment, post, or user)
	 *
	 * @return null|string
	 */
	public function get_meta_type() {
		if ( $this instanceof Term ) {
			return 'term';
		}

		if ( $this instanceof Post ) {
			return 'post';
		}

		if ( isset( $this->meta_type ) ) {
			return $this->meta_type;
		}

		return null;
	}

	/**
	 * Mapped metadata with the attributes.
	 *
	 * @return void
	 */
	protected function setup_metadata() {
		if ( $this->get_meta_type() ) {
			$this->fill( $this->get_metadata() );
		}
	}

	/**
	 * Get a metadata by meta key.
	 *
	 * @param  string $meta_key The metadata key.
	 * @return mixed|null
	 */
	public function get_meta( $meta_key ) {
		$meta_value = get_metadata( $this->get_meta_type(), $this->get_id(), $meta_key, true );

		if ( false === $meta_value ) {
			return null;
		}

		return $meta_value;
	}

	/**
	 * Add metadata.
	 *
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
	 * @return int|false
	 */
	public function add_meta( $meta_key, $meta_value ) {
		$added = add_metadata( $this->get_meta_type(), $this->get_id(), $meta_key, $meta_value, true );

		if ( false === $added ) {
			return false;
		}

		$this->update_attribute_meta( $meta_key, $meta_value );

		return $added;
	}

	/**
	 * Update metadata.
	 *
	 * @param  string $meta_key   Metadata key.
	 * @param  mixed  $meta_value Metadata value. Must be serializable if non-scalar.
	 * @return bool
	 */
	public function update_meta( $meta_key, $meta_value ) {
		$updated = update_metadata( $this->get_meta_type(), $this->get_id(), $meta_key, $meta_value );

		if ( false === $updated ) {
			return false;
		}

		$this->update_attribute_meta( $meta_key, $meta_value );

		return true;
	}

	/**
	 * Delete metadata.
	 *
	 * @param  string $meta_key Metadata key.
	 * @return bool
	 */
	public function delete_meta( $meta_key ) {
		$deleted = delete_metadata( $this->get_meta_type(), $this->get_id(), $meta_key, '', false );

		if ( false === $deleted ) {
			return false;
		}

		$this->update_attribute_meta( $meta_key, null );

		return true;
	}

	/**
	 * Update a attribute by meta_key.
	 *
	 * @param string $meta_key
	 * @param mixed  $meta_value
	 */
	protected function update_attribute_meta( $meta_key, $meta_value ) {
		if ( $attribute = $this->get_mapping_attribute( $meta_key ) ) {
			$this->set_attribute( $attribute, $meta_value );
		}
	}

	/**
	 * Get all metadata of this object.
	 *
	 * @return array
	 */
	public function get_metadata() {
		$metadata = [];

		foreach ( $this->get_mapping() as $attribute => $meta_key ) {
			$metadata[ $attribute ] = $this->get_meta( $meta_key );
		}

		return $metadata;
	}

	/**
	 * Determine if the object or given attribute(s) were mapped.
	 *
	 * @param  string $attributes Optional, an array or string attribute(s).
	 * @return bool
	 */
	public function has_mapping( $attributes = null ) {
		$mapping = $this->get_mapping();

		if ( is_null( $attributes ) ) {
			return count( $mapping ) > 0;
		}

		foreach ( is_array( $attributes ) ? $attributes : func_get_args() as $attribute ) {
			if ( array_key_exists( $attribute, $mapping ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the mapping metakey by special attribute.
	 *
	 * @param  string $attribute The attribute key to get metakey.
	 * @return string|null
	 */
	public function get_mapping_metakey( $attribute ) {
		$mapping = $this->get_mapping();

		return isset( $mapping[ $attribute ] ) ? $mapping[ $attribute ] : null;
	}

	/**
	 * Get the mapping attribute by special metakey.
	 *
	 * @param  string $metakey The metakey key to get attribute.
	 * @return string|null
	 */
	public function get_mapping_attribute( $metakey ) {
		$mapping = array_flip( $this->get_mapping() );

		return isset( $mapping[ $metakey ] ) ? $mapping[ $metakey ] : null;
	}

	/**
	 * Get a list normalized of mapping.
	 *
	 * @return array
	 */
	public function get_mapping() {
		if ( empty( $this->maps ) ) {
			return [];
		}

		if ( ! $this->mapping ) {
			$this->mapping = $this->normalize_mapping();
		}

		return $this->mapping;
	}

	/**
	 * Normalize mapping metadata.
	 *
	 * @return array
	 */
	protected function normalize_mapping() {
		$mapping = [];

		foreach ( $this->maps as $attribute => $metadata ) {
			// Allowed using same name of attribute and metadata.
			// Eg: [gallery] same as [gallery => gallery].
			$attribute = is_int( $attribute ) ? $metadata : $attribute;

			if ( array_key_exists( $attribute, $this->attributes ) ) {
				$mapping[ $attribute ] = $metadata;
			}
		}

		return $mapping;
	}

	/**
	 * Run perform update object metadata.
	 *
	 * @param  array $changes The attributes changed.
	 * @return array|null
	 */
	protected function perform_update_metadata( array $changes ) {
		if ( ! $this->get_meta_type() ) {
			return null;
		}

		$mapping = $this->get_mapping();

		$changes = $this->recently_created
			? array_keys( $mapping )
			: $this->get_changes_only( $changes, array_keys( $mapping ) );

		// Don't do anything if nothing changes.
		if ( empty( $changes ) ) {
			return null;
		}

		$updated = [];

		foreach ( $changes as $attribute ) {
			$meta_key = $this->get_mapping_metakey( $attribute );

			if ( is_null( $meta_key ) ) {
				continue;
			}

			if ( $this->update_meta( $meta_key, $this->get_attribute( $attribute ) ) ) {
				$updated[] = $attribute;
			}
		}

		return $updated;
	}
}
