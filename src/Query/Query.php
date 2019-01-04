<?php

namespace WPLibs\Model\Query;

/**
 * Abstract Query.
 *
 * @method int|null insert( $attributes )
 * @method int|bool update( $id, $dirty )
 * @method bool     delete( $id, $force )
 *
 * @package WPLibs\Model\Query
 */
abstract class Query {
	/**
	 * The table name.
	 *
	 * @var string
	 */
	public $table;

	/**
	 * The primary key name.
	 *
	 * @var string
	 */
	public $primary_key;

	/**
	 * The object type name.
	 *
	 * @var string
	 */
	public $object_type;

	/**
	 * The query vars.
	 *
	 * @var mixed
	 */
	protected $query_vars;

	/**
	 * An array of query vars to translation.
	 *
	 * @var array
	 */
	protected $trans_query_vars = [];

	/**
	 * Constructor.
	 *
	 * @param array|\WPLibs\Model\Query\Query_Vars $main_query The main query vars.
	 */
	public function __construct( $main_query = [] ) {
		$this->query_vars = ! $main_query instanceof Query_Vars
			? new Query_Vars( $main_query )
			: $main_query;
	}

	/**
	 * Set the table name.
	 *
	 * @param  string $table The table name.
	 * @return $this
	 */
	public function set_table( $table ) {
		$this->table = $table;

		return $this;
	}

	/**
	 * Set the primary key name.
	 *
	 * @param  string $primary_key The primary key name.
	 * @return $this
	 */
	public function set_primary_key( $primary_key ) {
		$this->primary_key = $primary_key;

		return $this;
	}

	/**
	 * Set the object type name.
	 *
	 * @param  string $object_type The object type name.
	 * @return $this
	 */
	public function set_object_type( $object_type ) {
		$this->object_type = $object_type;

		return $this;
	}

	/**
	 * Find a model by its primary key.
	 *
	 * @param  int|mixed $id
	 * @return mixed
	 */
	abstract public function get_by_id( $id );

	/**
	 * Execute the query to retrieves items.
	 *
	 * @param  mixed $query_vars The query vars.
	 * @return mixed
	 */
	abstract public function do_query( $query_vars );

	/**
	 * Extract items from the query.
	 *
	 * @param  mixed $items The raw items.
	 * @return array
	 */
	public function extract_items( $items ) {
		return $items;
	}

	/**
	 * Get the query vars (or query builder).
	 *
	 * @return \WPLibs\Database\Builder|\WPLibs\Model\Query\Query_Vars|mixed
	 */
	public function get_query_vars() {
		return $this->query_vars;
	}

	/**
	 * Apply a query_var into the query builder.
	 *
	 * @param  string $name
	 * @param  mixed  ...$parameters
	 */
	public function apply_query_var( $name, ...$parameters ) {
		$name = $this->translate_query_var( $name );

		if ( is_array( $this->query_vars ) || $this->query_vars instanceof \ArrayAccess ) {
			$this->query_vars[ $name ] = count( $parameters ) > 0 ? $parameters[0] : true;
			return;
		}

		if ( method_exists( $this->query_vars, $name ) ) {
			call_user_func_array( [ $this->query_vars, $name ], $parameters );
			return;
		}

		throw new \InvalidArgumentException( 'Unsupported query [' . $name . ']' );
	}

	/**
	 * Perform translate a query vars.
	 *
	 * @param  string $key The query var key name.
	 * @return string
	 */
	protected function translate_query_var( $key ) {
		if ( array_key_exists( $key, $this->trans_query_vars ) ) {
			return $this->trans_query_vars[ $key ];
		}

		return $key;
	}

	/**
	 * Returns the query_vars as array.
	 *
	 * @return array
	 */
	public function to_array() {
		return $this->query_vars->to_array();
	}
}
