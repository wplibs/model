<?php

namespace WPLibs\Model;

use WPLibs\Database\Database;
use WPLibs\Model\Utils\Utils;
use WPLibs\Model\Utils\Serialization;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

/**
 * Abstract model class.
 *
 * @method static self find( $id )
 * @method int         doing_insert( $attributes )
 * @method bool        doing_update( $id, $dirty )
 * @method bool        doing_delete( $id, $force = false )
 *
 * @package WPLibs\Model
 */
abstract class Model implements Arrayable, Jsonable, \ArrayAccess, \JsonSerializable {
	use Concerns\Has_Attributes,
		Concerns\Has_Events,
		Serialization;

	/**
	 * Name of object type.
	 *
	 * @var string
	 */
	protected $object_type;

	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * The primary key for the model.
	 *
	 * @var string
	 */
	protected $primary_key = 'ID';

	/**
	 * Indicates if the object exists.
	 *
	 * @var bool
	 */
	public $exists = false;

	/**
	 * Indicates if the object was inserted during the current request lifecycle.
	 *
	 * @var bool
	 */
	public $recently_created = false;

	/**
	 * The array of booted models.
	 *
	 * @var array
	 */
	protected static $booted = [];

	/**
	 * The array of trait initializers that will be called on each new instance.
	 *
	 * @var array
	 */
	protected static $trait_initializers = [];

	/**
	 * Constructor.
	 *
	 * @param array|mixed $attributes The model attributes.
	 */
	public function __construct( $attributes = [] ) {
		$this->maybe_boot();

		// Forward initialize to the sub-class.
		if ( method_exists( $this, 'initialize' ) ) {
			$this->initialize( $attributes );
		}

		$this->initialize_traits();
		$this->sync_original();

		if ( is_array( $attributes ) ) {
			$this->fill( $attributes );
		}
	}

	/**
	 * Check if the model needs to be booted and if so, do it.
	 *
	 * @return void
	 */
	protected function maybe_boot() {
		if ( ! isset( static::$booted[ static::class ] ) ) {
			static::$booted[ static::class ] = true;

			$this->trigger( 'booting' );

			static::boot();

			$this->trigger( 'booted' );
		}
	}

	/**
	 * The "booting" method of the model.
	 *
	 * @return void
	 */
	protected static function boot() {
		static::boot_traits();
	}

	/**
	 * Clear the list of booted models so they will be re-booted.
	 *
	 * @return void
	 */
	public static function clear_booted_models() {
		static::$booted = [];
	}

	/**
	 * Boot all of the bootable traits on the model.
	 *
	 * @return void
	 */
	protected static function boot_traits() {
		$class = static::class;

		$booted = [];
		static::$trait_initializers[ $class ] = [];

		foreach ( Utils::class_uses( $class ) as $trait ) {
			$trait = str_replace( '_trait', '',
				strtolower( basename( str_replace( '\\', '/', $trait ) ) )
			);

			$boot_method       = 'boot_' . $trait;
			$initialize_method = 'initialize_' . $trait;

			if ( method_exists( $class, $boot_method ) && ! in_array( $boot_method, $booted ) ) {
				forward_static_call( [ $class, $boot_method ] );
				$booted[] = $boot_method;
			}

			if ( method_exists( $class, $initialize_method ) ) {
				static::$trait_initializers[ $class ][] = $initialize_method;
				static::$trait_initializers[ $class ]   = array_unique( static::$trait_initializers[ $class ] );
			}
		}
	}

	/**
	 * Initialize any initializable traits on the model.
	 *
	 * @return void
	 */
	protected function initialize_traits() {
		if ( array_key_exists( static::class, static::$trait_initializers ) ) {
			foreach ( static::$trait_initializers[ static::class ] as $method ) {
				$this->{$method}();
			}
		}
	}

	/**
	 * Fill the object with an array of attributes.
	 *
	 * @param  array $attributes An array of attributes to fill.
	 * @return $this
	 */
	public function fill( array $attributes ) {
		foreach ( $attributes as $key => $value ) {
			$this->set_attribute( $key, $value );
		}

		return $this;
	}

	/**
	 * Create a new instance of the given model.
	 *
	 * @param  array|mixed $attributes
	 * @param  bool        $exists
	 *
	 * @return static
	 */
	public function new_instance( $attributes = [], $exists = false ) {
		// This method just provides a convenient way for us to generate fresh model
		// instances of this current model. It is particularly useful during the
		// hydration of new objects via the query builder instances.
		$model = new static( $attributes );

		$model->exists = $exists;

		return $model;
	}

	/**
	 * Create a new model instance that is existing.
	 *
	 * @param  array $attributes
	 * @return static
	 */
	public function new_from_builder( $attributes = [] ) {
		$model = $this->new_instance( [], true );

		$model->set_raw_attributes( (array) $attributes, true );

		$model->trigger( 'retrieved', false );

		return $model;
	}

	/**
	 * Update the model in the database.
	 *
	 * @param  array $attributes The attributes to update.
	 * @return bool
	 */
	public function update( array $attributes = [] ) {
		if ( ! $this->exists() ) {
			return false;
		}

		return $this->fill( $attributes )->save();
	}

	/**
	 * Save the model to the database.
	 *
	 * @return bool
	 */
	public function save() {
		// If the "saving" event returns false we'll bail out of the save and return
		// false, indicating that the save failed. This provides a chance for any
		// listeners to cancel save operations if validations fail or whatever.
		if ( false === $this->trigger( 'saving' ) ) {
			return false;
		}

		if ( $this->recently_created ) {
			$this->recently_created = false;
		}

		// If the model already exists in the database we can just update our record
		// that is already in this database using the current IDs in this "where"
		// clause to only update this model. Otherwise, we'll just insert them.
		if ( $this->exists() ) {
			$saved = $this->is_dirty() ? $this->perform_update() : true;
		} else {
			$saved = $this->perform_insert();
		}

		if ( $saved ) {
			$this->finish_save();
		}

		return $saved;
	}

	/**
	 * Perform any actions that are necessary after the model is saved.
	 *
	 * @return void
	 */
	protected function finish_save() {
		$this->flush_cache();

		$this->sync_original();

		$this->trigger( 'saved' );
	}

	/**
	 * Flush the cache or whatever if necessary.
	 *
	 * @return void
	 */
	protected function flush_cache() {}

	/**
	 * Perform a model update operation.
	 *
	 * @return bool
	 */
	protected function perform_update() {
		// If the updating event returns false, we will cancel the update operation so
		// developers can hook Validation systems into their models and cancel this
		// operation if the model does not pass validation. Otherwise, we update.
		if ( false === $this->trigger( 'updating', true ) ) {
			return false;
		}

		$dirty = $this->get_dirty();

		if ( count( $dirty ) > 0 ) {
			// Pass the update action into subclass to process.
			if ( false === $this->doing( 'update', $this->get_key_for_save(), $dirty ) ) {
				return false;
			}

			$this->sync_changes();

			$this->trigger( 'updating' );
		}

		return true;
	}

	/**
	 * Perform a model insert operation.
	 *
	 * @return bool
	 */
	protected function perform_insert() {
		if ( false === $this->trigger( 'creating', true ) ) {
			return false;
		}

		// Pass the action to subclass to process.
		$insert_id = $this->doing( 'insert', $this->get_attributes() );

		if ( ! is_int( $insert_id ) || $insert_id <= 0 ) {
			return false;
		}

		// We will go ahead and set the exists property to true, so that it is set when
		// the created event is fired, just in case the developer tries to update it
		// during the event. This will allow them to do so and run an update here.
		$this->exists = true;

		$this->recently_created = true;

		// Set the ID on the model.
		$this->set_attribute( $this->get_key_name(), $insert_id );

		$this->trigger( 'created' );

		return true;
	}

	/**
	 * Destroy the models for the given IDs.
	 *
	 * @param  array|int $ids The IDs.
	 * @return int
	 */
	public static function destroy( $ids ) {
		// We'll initialize a count here so we will return the total number of deletes
		// for the operation. The developers can then check this number as a boolean
		// type value or get this total count of records deleted for logging, etc.
		$count = 0;

		$ids = is_array( $ids ) ? $ids : func_get_args();

		// We will actually pull the models from the database table and call delete on
		// each of them individually so that their events get fired properly with a
		// correct set of attributes in case the developers wants to check these.
		foreach ( $ids as $id ) {
			/* @var $model \WPLibs\Model\Model */
			$model = static::find( $id );

			if ( $model && $model->delete( true ) ) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Delete the model from the database.
	 *
	 * @param  bool $force Optional. Whether to bypass trash and force deletion.
	 * @return bool|null
	 */
	public function delete( $force = false ) {
		// If the model doesn't exist, there is nothing to delete so we'll just return
		// immediately and not do anything else. Otherwise, we will continue with a
		// deletion process on the model, firing the proper events, and so forth.
		if ( ! $this->exists() ) {
			return null;
		}

		if ( false === $this->trigger( 'deleting', true ) ) {
			return false;
		}

		// Pass the action to subclass to process.
		if ( ! $this->doing( 'delete', $this->get_key_for_save(), $force ) ) {
			return false;
		}

		$this->exists = false;

		$this->flush_cache();

		$this->trigger( 'deleted' );

		return true;
	}

	/**
	 * Call the doing a action.
	 *
	 * @param  string $action  The action name.
	 * @param  mixed  ...$vars The action parameters.
	 * @return mixed
	 */
	protected function doing( $action, ...$vars ) {
		// First, we will looking up for the actions in current model.
		if ( method_exists( $this, $method = "doing_{$action}" ) ) {
			return $this->{$method}( ...$vars );
		}

		// Then, looking actions in the query.
		$query = $this->new_query_builder()->get_query();

		if ( method_exists( $query, $action ) ) {
			return $query->{$action}( ...$vars );
		}

		throw new \RuntimeException( 'The "' . $action . '" action is not supported in the [' . get_class( $this ) . ']' );
	}

	/**
	 * Get all of the models.
	 *
	 * @return \WPLibs\Model\Collection static[]
	 */
	public static function all() {
		return ( new static )->new_query_builder()->get();
	}

	/**
	 * Begin querying the model.
	 *
	 * @param array $query The query.
	 * @return \WPLibs\Model\Query\Builder
	 */
	public static function query( $query = [] ) {
		return ( new static )->new_query_builder( $query );
	}

	/**
	 * Get a new query builder for the model's.
	 *
	 * @param array $query_vars The query.
	 * @return \WPLibs\Model\Query\Builder
	 */
	public function new_query_builder( $query_vars = [] ) {
		return ( new Query\Builder( $this->new_query() ) )->set_model( $this );
	}

	/**
	 * Get a new query instance.
	 *
	 * @return \WPLibs\Model\Query\Query
	 */
	public function new_query() {
		return new Query\DB_Query( $this->new_db_query() );
	}

	/**
	 * Get a new database query builder.
	 *
	 * @return \WPLibs\Database\Builder
	 */
	public function new_db_query() {
		return Database::table( $this->get_table() );
	}

	/**
	 * Create a new Collection instance.
	 *
	 * @param mixed $models An array of models.
	 * @param bool  $map    Should map this class into.
	 *
	 * @return \WPLibs\Model\Collection
	 */
	public function new_collection( $models, $map = false ) {
		$collect = new Collection( $models );

		return ! $map ? $collect : $collect->map_into( get_class( $this ) );
	}

	/**
	 * Returns the WP internal type, e.g: "post", "term", "user", etc.
	 *
	 * @return string
	 */
	public function resolve_internal_type() {
		return 'post';
	}

	/**
	 * Get the value of the model's primary key.
	 *
	 * @return int
	 */
	public function get_id() {
		return (int) $this->get_key();
	}

	/**
	 * Get the value of the model's primary key.
	 *
	 * @return int|null
	 */
	public function get_key() {
		if ( array_key_exists( $this->get_key_name(), $this->attributes ) ) {
			return $this->attributes[ $this->get_key_name() ];
		}

		return null;
	}

	/**
	 * Get the primary key value for a save query.
	 *
	 * @return int|null
	 */
	public function get_key_for_save() {
		return $this->get_original( $this->get_key_name(), $this->get_key() );
	}

	/**
	 * Determine if object exists.
	 *
	 * @return bool
	 */
	public function exists() {
		return $this->exists;
	}

	/**
	 * Get the table associated with the model.
	 *
	 * @return string
	 */
	public function get_table() {
		return $this->table ?: 'posts';
	}

	/**
	 * Get the primary key for the model.
	 *
	 * @return string
	 */
	public function get_key_name() {
		return $this->primary_key;
	}

	/**
	 * Return the object type name.
	 *
	 * @return string
	 */
	public function get_object_type() {
		return $this->object_type;
	}

	/**
	 * Retrieves the attributes as array.
	 *
	 * @return array
	 */
	public function to_array() {
		return $this->get_attributes();
	}

	/**
	 * Returns the value at specified offset.
	 *
	 * @param  string $offset The offset to retrieve.
	 * @return mixed
	 */
	public function offsetGet( $offset ) {
		return $this->get_attribute( $offset );
	}

	/**
	 * Assigns a value to the specified offset.
	 *
	 * @param string $offset The offset to assign the value to.
	 * @param mixed  $value  The value to set.
	 */
	public function offsetSet( $offset, $value ) {
		$this->set_attribute( $offset, $value );
	}

	/**
	 * Whether or not an offset exists.
	 *
	 * @param  string $offset The offset to check for.
	 * @return bool
	 */
	public function offsetExists( $offset ) {
		return ! is_null( $this->get_attribute( $offset ) );
	}

	/**
	 * Unsets an offset.
	 *
	 * @param string $offset The offset to unset.
	 */
	public function offsetUnset( $offset ) {
		unset( $this->attributes[ $offset ] );
	}

	/**
	 * Dynamically retrieve attributes on the model.
	 *
	 * @param  string $key The attribute key name.
	 * @return mixed
	 */
	public function __get( $key ) {
		return $this->get_attribute( $key );
	}

	/**
	 * Dynamically set attributes on the model.
	 *
	 * @param  string $key   The attribute key name.
	 * @param  mixed  $value The attribute value.
	 * @return void
	 */
	public function __set( $key, $value ) {
		$this->set_attribute( $key, $value );
	}

	/**
	 * Determine if an attribute exists on the model.
	 *
	 * @param  string $key The attribute key name.
	 * @return bool
	 */
	public function __isset( $key ) {
		return $this->offsetExists( $key );
	}

	/**
	 * Unset an attribute on the model.
	 *
	 * @param  string $key The attribute key name to remove.
	 * @return void
	 */
	public function __unset( $key ) {
		$this->offsetUnset( $key );
	}

	/**
	 * Convert the object to its string representation.
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->to_json();
	}

	/**
	 * Handle dynamic static method calls into the method.
	 *
	 * @param  string $method
	 * @param  array  $parameters
	 * @return mixed
	 */
	public static function __callStatic( $method, $parameters ) {
		$builder = ( new static )->new_query_builder();

		return $builder->$method( ...$parameters );
	}
}
