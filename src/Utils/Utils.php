<?php

namespace WPLibs\Model\Utils;

use WPLibs\Model\Model;

class Utils {
	/**
	 * Determines if a post, identified by the specified ID, exist
	 * within the WordPress database.
	 *
	 * @link https://tommcfarlin.com/wordpress-post-exists-by-id/
	 *
	 * @param  int $id The ID of the post to check.
	 * @return bool    True if the post exists; otherwise, false.
	 */
	public static function post_exists( $id ) {
		return is_string( get_post_status( $id ) );
	}

	/**
	 * Safely update a post.
	 *
	 * When updating a post, to prevent infinite loops, use $wpdb to update data,
	 * since 'wp_update_post' spawns more calls to the save_post action.
	 *
	 * @param  mixed $post      The post instance or post ID.
	 * @param  array $post_data An array post data to update.
	 *
	 * @return bool|null
	 */
	public static function update_the_post( $post, array $post_data ) {
		global $wpdb;

		if ( empty( $post_data ) || ! static::post_exists( $post ) ) {
			return null;
		}

		$post_id = get_post( $post )->ID;

		if ( doing_action( 'save_post' ) || 0 === strpos( current_action(), 'save_post' ) ) {
			$updated = $wpdb->update( $wpdb->posts, $post_data, [ 'ID' => $post_id ] );
		} else {
			$updated = wp_update_post( array_merge( [ 'ID' => $post_id ], $post_data ) );
		}

		clean_post_cache( $post_id );

		return ( 0 !== $updated && false !== $updated );
	}

	/**
	 * Get terms as IDs from a taxonomy.
	 *
	 * @param  int    $post_id  The post ID.
	 * @param  string $taxonomy Taxonomy name.
	 *
	 * @return array|null
	 */
	public static function get_term_ids( $post_id, $taxonomy ) {
		$terms = get_the_terms( $post_id, $taxonomy );

		if ( false === $terms || is_wp_error( $terms ) ) {
			return null;
		}

		return wp_list_pluck( $terms, 'term_id' );
	}

	/**
	 * Parse the object ID.
	 *
	 * @param  mixed $object The object.
	 * @return int|null
	 */
	public static function parse_object_id( $object ) {
		if ( is_numeric( $object ) && $object > 0 ) {
			return (int) $object;
		}

		if ( ! empty( $object->ID ) ) {
			return (int) $object->ID;
		}

		if ( ! empty( $object->term_id ) ) {
			return (int) $object->term_id;
		}

		if ( $object instanceof Model ) {
			return $object->get_id();
		}

		return null;
	}

	/**
	 * Returns all traits used by a class,
	 * its parent classes and trait of their traits.
	 *
	 * @param  object|string $class
	 * @return array
	 */
	public static function class_uses( $class ) {
		if ( is_object( $class ) ) {
			$class = get_class( $class );
		}

		$results = [ [] ];

		foreach ( array_reverse( class_parents( $class ) ) + [ $class => $class ] as $_class ) {
			$results[] = static::trait_uses( $_class );
		}

		return array_unique( array_merge( ...$results ) );
	}

	/**
	 * Returns all traits used by a trait and its traits.
	 *
	 * @param  string $trait
	 * @return array
	 */
	public static function trait_uses( $trait ) {
		$traits = class_uses( $trait );

		foreach ( $traits as $_trait ) {
			$traits += static::trait_uses( $_trait );
		}

		return $traits;
	}
}
