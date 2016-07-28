<?php

/*
 * This file is part of the mimosafa\wp-repository package.
 *
 * (c) Toshimichi Mimoto <mimosafa@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace mimosafa\WP\Repository;

/**
 * @author Toshimichi Mimoto <mimosafa@gmail.com>
 */
abstract class Entities {

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $alias;

	/**
	 * @var array|string
	 */
	protected $args;

	/**
	 * Post Types & Taxonomies arguments.
	 *
	 * @var array
	 */
	protected static $post_types = [];
	protected static $taxonomies = [];

	/**
	 * Constructor.
	 *
	 * @param  string $name
	 * @param  string $alias
	 * @param  array|string $args
	 * @param  mimosafa\WP\Repository\Store $store
	 */
	public function __construct( $name, $alias, Array $args, Store $store ) {
		$this->name = $name;
		$this->alias = $alias;

		/**
		 * Default arguments.
		 *
		 * @var array
		 */
		$defaults = apply_filters( get_called_class() . '\\default_arguments', static::$defaults, $name, $alias );

		$this->args = wp_parse_args( $args, static::$defaults );
		add_action( 'init', [ $this, 'init_arguments' ], 11 );
		static $done = false;
		if ( ! $done ) {
			add_action( 'init', [ $this, 'register_taxonomies' ], 12 );
			add_action( 'init', [ $this, 'register_post_types' ], 12 );
			$done = true;
		}
	}

	/**
	 * Paramator getter.
	 */
	public function __get( $name ) {
		return in_array( $name, [ 'name', 'alias' ], true ) ? $this->$name : null;
	}

	/**
	 * Paramator setter.
	 */
	public function __set( $name, $var ) {
		if ( substr( $name, 0, 8 ) === 'rewrite_' ) {
			/**
			 * Set rewrite arguments.
			 */
			$name = substr( $name, 8 );
			if ( array_key_exists( $name, static::$rewrite_defaults ) ) {
				if ( ! is_array( $this->args['rewrite'] ) ) {
					$this->args['rewrite'] = [];
				}
				$this->args['rewrite'][$name] = $var;
			}
		}
		else if ( substr( $name, 0, 6 ) === 'label_' ) {
			/**
			 * Set label arguments.
			 */
			$name = substr( $name, 6 );
			if ( array_key_exists( $name, static::$label_formats ) ) {
				if ( ! is_array( $this->args['labels'] ) ) {
					$this->args['labels'] = [];
				}
				$this->args['labels'][$name] = $var;
			}
		}
		else {
			$this->args[$name] = $var;
		}
	}

	/**
	 * @abstract
	 * @access public
	 *
	 * @return void
	 */
	abstract public function init_arguments();

	/**
	 * @access public
	 *
	 * @return void
	 */
	public function register_taxonomies() {
		if ( ! empty( self::$taxonomies ) ) {
			foreach ( self::$taxonomies as $tx ) {
				/**
				 * @var string $taxonomy
				 * @var array  $object_type
				 * @var array  $args
				 */
				extract( $tx, \EXTR_OVERWRITE );
				register_taxonomy( $taxonomy, $object_type, $args );
			}
		}
	}

	/**
	 * @access public
	 *
	 * @return void
	 */
	public function register_post_types() {
		if ( ! empty( self::$post_types ) ) {
			foreach ( self::$post_types as $pt ) {
				/**
				 * @var string $post_type
				 * @var array  $args
				 */
				extract( $pt, \EXTR_OVERWRITE );
				if ( ! empty( self::$taxonomies ) ) {
					$taxonomies = [];
					foreach ( self::$taxonomies as $tx ) {
						if ( in_array( $post_type, $tx['object_type'], true ) ) {
							$taxonomies[] = $tx['taxonomy'];
						}
					}
					if ( ! empty( $taxonomies ) ) {
						if ( ! isset( $args['taxonomies'] ) ) {
							$args['taxonomies'] = [];
						}
						$args['taxonomies'] = array_unique(
							array_merge( (array) $args['taxonomies'], $taxonomies )
						);
					}
				}
				register_post_type( $post_type, $args );
			}
		}
	}

	/**
	 * @static
	 * @access protected
	 *
	 * @param  string $string
	 * @return string
	 */
	protected static function labelize( $string ) {
		return trim( ucwords( str_replace( [ '-', '_' ], ' ', $string ) ) );
	}

}
