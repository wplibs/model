<?php

namespace WPLibs\Model\Query;

use WPLibs\Model\Utils\Utils;

class Term_Query extends Query {
	/**
	 * An array of query vars to translation.
	 *
	 * @see \WP_Term_Query::__construct()
	 *
	 * @var array
	 */
	protected $trans_query_vars = [
		'select' => 'fields',
		'limit'  => 'number',
	];

	/**
	 * {@inheritdoc}
	 */
	public function get_by_id( $id ) {
		$wp_term = get_term( Utils::parse_object_id( $id ), $this->object_type, ARRAY_A );

		if ( is_null( $wp_term ) || is_wp_error( $wp_term ) ) {
			return null;
		}

		return $wp_term;
	}

	/**
	 * {@inheritdoc}
	 */
	public function do_query( $query_vars ) {
		if ( $query_vars instanceof Query_Vars ) {
			$query_vars = $query_vars->to_array();
		}

		return new \WP_Term_Query( $query_vars );
	}

	/**
	 * {@inheritdoc}
	 */
	public function extract_items( $term_query ) {
		return $term_query->terms;
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
		if ( ! isset( $attributes['name'] ) ) {
			return null;
		}

		$response = wp_insert_term( $attributes['name'], $this->object_type, $attributes );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		return $response['term_id'];
	}

	/**
	 * Perform update the model in the database.
	 *
	 * @param int   $id    The ID to update.
	 * @param array $dirty The attributes for the update.
	 * @return int|bool
	 */
	public function update( $id, $dirty ) {
		$updated = wp_update_term( $id, $this->object_type, $dirty );

		if ( is_wp_error( $updated ) ) {
			return false;
		}

		return $updated['term_id'];
	}

	/**
	 * Perform delete a model from the database.
	 *
	 * @param int  $id    The ID to delete.
	 * @param bool $force Force delete or not.
	 * @return bool
	 */
	public function delete( $id, $force ) {
		$delete = wp_delete_term( $id, $this->object_type );

		return ( ! is_wp_error( $delete ) && true === $delete );
	}
}
