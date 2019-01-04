<?php

namespace WPLibs\Model\Concerns;

trait Has_Events {
	/**
	 * User exposed observable events.
	 *
	 * These are extra user-defined events observers may subscribe to.
	 *
	 * @var array
	 */
	protected $observables = [];

	/**
	 * Store the hooks prefix.
	 *
	 * @var array
	 */
	protected static $prefix = 'wp.';

	/**
	 * Register a model event with the dispatcher.
	 *
	 * @param  string          $event
	 * @param  \Closure|string $callback
	 *
	 * @return void
	 */
	protected static function on( $event, $callback ) {
	}

	/**
	 * Fire the given event for the model.
	 *
	 * @param  string $event   The event name.
	 * @param  bool   $filter  Trigger a filter instead a action.
	 * @param  array  ...$args Additional arguments which are passed on to the
	 *                         functions hooked to the action.
	 * @return mixed
	 */
	protected function trigger( $event, $filter = false, ...$args ) {
		$name = $this->prefix( $event );

		if ( $filter ) {
			return apply_filters( $name, $this, ...$args );
		}

		do_action( $name, $this, ...$args );
	}

	/**
	 * Helper: Prefix for action and filter hooks for this object.
	 *
	 * @param  string $hook_name Hook name without prefix.
	 * @return string
	 */
	protected function prefix( $hook_name ) {
		return sprintf( '%s/%s/%s', static::$prefix, $this->object_type, $hook_name );
	}

	/**
	 * Register a retrieved model event with the dispatcher.
	 *
	 * @param  \Closure|string $callback
	 *
	 * @return void
	 */
	public static function retrieved( $callback ) {
		static::on( 'retrieved', $callback );
	}

	/**
	 * Register a saving model event with the dispatcher.
	 *
	 * @param  \Closure|string $callback
	 *
	 * @return void
	 */
	public static function saving( $callback ) {
		static::on( 'saving', $callback );
	}

	/**
	 * Register a saved model event with the dispatcher.
	 *
	 * @param  \Closure|string $callback
	 *
	 * @return void
	 */
	public static function saved( $callback ) {
		static::on( 'saved', $callback );
	}

	/**
	 * Register an updating model event with the dispatcher.
	 *
	 * @param  \Closure|string $callback
	 *
	 * @return void
	 */
	public static function updating( $callback ) {
		static::on( 'updating', $callback );
	}

	/**
	 * Register an updated model event with the dispatcher.
	 *
	 * @param  \Closure|string $callback
	 *
	 * @return void
	 */
	public static function updated( $callback ) {
		static::on( 'updated', $callback );
	}

	/**
	 * Register a creating model event with the dispatcher.
	 *
	 * @param  \Closure|string $callback
	 *
	 * @return void
	 */
	public static function creating( $callback ) {
		static::on( 'creating', $callback );
	}

	/**
	 * Register a created model event with the dispatcher.
	 *
	 * @param  \Closure|string $callback
	 *
	 * @return void
	 */
	public static function created( $callback ) {
		static::on( 'created', $callback );
	}

	/**
	 * Register a deleting model event with the dispatcher.
	 *
	 * @param  \Closure|string $callback
	 *
	 * @return void
	 */
	public static function deleting( $callback ) {
		static::on( 'deleting', $callback );
	}

	/**
	 * Register a deleted model event with the dispatcher.
	 *
	 * @param  \Closure|string $callback
	 *
	 * @return void
	 */
	public static function deleted( $callback ) {
		static::on( 'deleted', $callback );
	}
}
