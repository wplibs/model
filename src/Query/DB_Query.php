<?php

namespace WPLibs\Model\Query;

use WPLibs\Database\Database;
use WPLibs\Database\Builder as QueryBuilder;

class DB_Query extends Query {
	/**
	 * An array of query vars to translation.
	 *
	 * @var array
	 */
	protected $trans_query_vars = [
		'orderby' => 'orderBy',
	];

	/**
	 * Constructor.
	 *
	 * @param QueryBuilder $query The database query builder.
	 */
	public function __construct( QueryBuilder $query ) {
		$this->query_vars = $query;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_by_id( $id ) {
		return $this->query_vars->where( $this->primary_key, $id )->first();
	}

	/**
	 * {@inheritdoc}
	 */
	public function do_query( $query ) {
		if ( ! $query instanceof QueryBuilder ) {
			throw new \InvalidArgumentException( 'The query must be instance of the [' . QueryBuilder::class . ']' );
		}

		return $query->get();
	}

	/**
	 * Perform insert the model into the database.
	 *
	 * @param  array $attributes The attributes to insert.
	 * @return int|null
	 */
	public function insert( $attributes ) {
		return Database::table( $this->table )->insertGetId( $attributes, $this->primary_key );
	}

	/**
	 * Perform update the model in the database.
	 *
	 * @param int   $id    The ID to update.
	 * @param array $dirty The attributes for the update.
	 * @return int|bool
	 */
	public function update( $id, $dirty ) {
		$updated = $this->get_query_for_save( $id )->update( $dirty );

		return is_int( $updated ) ? $updated : false;
	}

	/**
	 * Perform delete a model from the database.
	 *
	 * @param int  $id    The ID to delete.
	 * @param bool $force Force delete or not.
	 * @return bool
	 */
	public function delete( $id, $force ) {
		// TODO: Support force delete.
		return (bool) $this->get_query_for_save( $id )->delete();
	}

	/**
	 * Gets the query for save action.
	 *
	 * @param  int $id The ID.
	 * @return QueryBuilder
	 */
	protected function get_query_for_save( $id ) {
		return Database::table( $this->table )->where( $this->primary_key, '=', $id );
	}

	/**
	 * {@inheritdoc}
	 */
	public function to_array() {
		return [];
	}
}
