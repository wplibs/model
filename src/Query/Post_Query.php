<?php

namespace WPLibs\Model\Query;

use WPLibs\Model\Utils\Utils;

class Post_Query extends Query {
	/**
	 * An array of query vars to translation.
	 *
	 * @see \WP_Query::parse_query()
	 *
	 * @var array
	 */
	protected $trans_query_vars = [
		'select'  => 'fields',
		'limit'   => 'posts_per_page',
		'parent'  => 'post_parent',
		'status'  => 'post_status',
		'include' => 'post__in',
		'exclude' => 'post__not_in',
	];

	/**
	 * {@inheritdoc}
	 */
	public function get_by_id( $id ) {
		$post = get_post( Utils::parse_object_id( $id ), ARRAY_A );

		if ( ! $post || get_post_type( $id ) !== $this->object_type ) {
			return null;
		}

		return $post;
	}

	/**
	 * {@inheritdoc}
	 */
	public function do_query( $query_vars ) {
		if ( $query_vars instanceof Query_Vars ) {
			$query_vars = $query_vars->to_array();
		}

		return new \WP_Query( $query_vars );
	}

	/**
	 * {@inheritdoc}
	 */
	public function extract_items( $the_query ) {
		return $the_query->posts;
	}

	/**
	 * {@inheritdoc}
	 */
	public function apply_query_var( $name, ...$parameters ) {
		if ( 'orderby' === $name ) {
			list( $this->query_vars['orderby'], $this->query_vars['order'] ) = $parameters;
			return;
		}

		parent::apply_query_var( $name, ...$parameters );
	}

	/**
	 * Perform insert the model into the database.
	 *
	 * @param  array $attributes The attributes to insert.
	 * @return int|null
	 */
	public function insert( $attributes ) {
		return wp_insert_post( $attributes, false );
	}

	/**
	 * Perform update the model in the database.
	 *
	 * @param int   $id    The ID to update.
	 * @param array $dirty The attributes for the update.
	 * @return int|bool
	 */
	public function update( $id, $dirty ) {
		return (bool) Utils::update_the_post( $id, $dirty );
	}

	/**
	 * Perform delete a model from the database.
	 *
	 * @param int  $id    The ID to delete.
	 * @param bool $force Force delete or not.
	 * @return bool
	 */
	public function delete( $id, $force ) {
		if ( ! $force && EMPTY_TRASH_DAYS && 'trash' !== get_post_status( $id ) ) {
			$delete = wp_trash_post( $id );
		} else {
			$delete = wp_delete_post( $id, true );
		}

		return ( ! is_null( $delete ) && ! is_wp_error( $delete ) && false !== $delete );
	}
}
