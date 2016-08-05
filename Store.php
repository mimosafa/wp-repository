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
class Store {

	/**
	 * @var string
	 */
	private $prefix = '';

	/**
	 * @var array
	 */
	private $defaults = [];

	/**
	 * @static
	 * @var array
	 */
	private static $post_types = [];

	/**
	 * @static
	 * @var array
	 */
	private static $taxonomies = [];

	/**
	 * @static
	 * @var array
	 */
	private static $realNames = [];

	/**
	 * Constructor
	 *
	 * @access public
	 *
	 * @param  string|null  $prefix
	 * @param  array|atring $defaults
	 */
	public function __construct( $prefix = '', $defaults = [] ) {
		if ( $prefix && ! self::regexp( $prefix, '/\A[a-z][a-z0-9_]{0,14}\z/' ) ) {
			throw new \Exception;
		}
		$this->prefix = $prefix;
		if ( $defaults ) {
			$this->defaults = wp_parse_args( $defaults );
		}
	}

	/**
	 * Add default arguments
	 *
	 * @access public
	 *
	 * @param  array|string $defaults
	 * @return void
	 */
	public function set_defaults( $defaults ) {
		$this->defaults = wp_parse_args( $defaults, $this->defaults );
	}

	/**
	 * Reset default arguments
	 *
	 * @access public
	 *
	 * @param  array|string $defaults
	 * @return void
	 */
	public function reset_defaults( $defaults = [] ) {
		$this->defaults = wp_parse_args( $defaults );
	}

	/**
	 * Post Type Generator
	 *
	 * @access public
	 *
	 * @param  string $name
	 * @param  array|string $args
	 * @return mimosafa\WP\Repository\PostType
	 */
	public function create_post_type( $name, $args = [] ) {
		self::checkUsableAsName( $name );
		$args = wp_parse_args( $args, $this->defaults );
		$alias = $this->make_alias_string( $name, $args );
		if ( ! $alias = self::regexp( $alias, '/\A[a-z0-9][a-z0-9_\-]{0,19}\z/' ) ) {
			throw new \Exception;
		}
		self::$realNames[$name] = $alias;
		return self::$post_types[$name] = new PostType( $name, $alias, $args, $this );
	}

	/**
	 * Taxonomy Generator
	 *
	 * @access public
	 *
	 * @param  string $name
	 * @param  array|string $args
	 * @return mimosafa\WP\Repository\Taxonomy
	 */
	public function create_taxonomy( $name, $args = [] ) {
		self::checkUsableAsName( $name );
		$args = wp_parse_args( $args, $this->defaults );
		$alias = $this->make_alias_string( $name, $args );
		if ( ! $alias = self::regexp( $alias, '/\A[a-z][a-z_]{0,31}\z/' ) ) {
			throw new \Exception;
		}
		self::$realNames[$name] = $alias;
		return self::$taxonomies[$name] = new Taxonomy( $name, $alias, $args, $this );
	}

	/**
	 * Get PostType instance
	 *
	 * @static
	 * @access public
	 *
	 * @param  string $var
	 * @return mimosafa\WP\Repository\PostType|null
	 */
	public static function postTypeInstance( $name ) {
		if ( filter_var( $name ) ) {
			foreach ( [ $name, self::getNameFromRealNames( $name ) ] as $var ) {
				if ( is_string( $var ) && isset( self::$post_types[$var] ) ) {
					return self::$post_types[$var];
				}
			}
		}
		return null;
	}

	/**
	 * Get Taxonomy instance
	 *
	 * @static
	 * @access public
	 *
	 * @param  string $var
	 * @return mimosafa\WP\Repository\Taxonomy|null
	 */
	public static function taxonomyInstance( $name ) {
		if ( filter_var( $name ) ) {
			foreach ( [ $name, self::getNameFromRealNames( $name ) ] as $var ) {
				if ( is_string( $var ) && isset( self::$taxonomies[$var] ) ) {
					return self::$taxonomies[$var];
				}
			}
		}
		return null;
	}

	/**
	 * Check string, usable as post_type OR taxonomy
	 *
	 * @static
	 * @access private
	 *
	 * @param  string $string
	 * @throws Exception
	 * @return void
	 */
	private static function checkUsableAsName( $string ) {
		if ( ! filter_var( $string ) ) {
			throw new \Exception;
		}
		if ( $name = self::getNameFromRealNames( $string ) ) {
			if ( $name === true ) {
				throw new \Exception( "\"{$string}\" is already used as existing repository's name." );
			}
			else {
				throw new \Exception( "\"{$string}\" is already used as existing repository's alias." );
			}
		}
	}

	/**
	 * @static
	 * @access private
	 *
	 * @param  mixed  $var
	 * @param  string $regexp
	 * @return string|boolean
	 */
	private static function regexp( $var, $regexp ) {
		$options = [ 'options' => [ 'regexp' => $regexp ] ];
		return filter_var( $var, \FILTER_VALIDATE_REGEXP, $options );
	}

	/**
	 * @access private
	 *
	 * @param  string $name
	 * @param  array  &$args
	 * @return string
	 */
	private function make_alias_string( $name, Array &$args ) {
		$alias = $this->prefix;
		if ( isset( $args['alias'] ) ) {
			if ( ! filter_var( $args['alias'] ) ) {
				throw new \Exception;
			}
			$alias .= $args['alias'];
			unset( $args['alias'] );
		} else {
			$alias .= $name;
		}
		self::checkUsableAsName( $alias );
		return $alias;
	}

	/**
	 * Get $name as 'key' of self::$realNames
	 *
	 * @static
	 * @access private
	 *
	 * @param  mixed $name
	 * @return bool|string
	 */
	private static function getNameFromRealNames( $name ) {
		if ( filter_var( $name ) ) {
			if ( isset( self::$realNames[$name] ) ) {
				return true;
			}
			if ( in_array( $name, self::$realNames, true ) ) {
				return array_search( $name, self::$realNames, true );
			}
		}
		return false;
	}

}
