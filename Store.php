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
	private static $rewriteSlugs = [];

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
	 * Instance getter.
	 */
	public function __get( $name ) {
		if ( isset( self::$post_types[$name] ) ) {
			return self::$post_types[$name];
		}
		if ( isset( self::$taxonomies[$name] ) ) {
			return self::$taxonomies[$name];
		}
		if ( $alias = array_search( $name, self::$rewriteSlugs, true ) ) {
			return $this->__get( $alias );
		}
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
		self::checkNameOrAliasAvailable( $name );
		$args = wp_parse_args( $args, $this->defaults );
		$alias = $this->make_alias_string( $name, $args );
		if ( ! $alias = self::regexp( $alias, '/\A[a-z0-9][a-z0-9_\-]{0,19}\z/' ) ) {
			throw new \Exception;
		}
		self::checkNameOrAliasAvailable( $alias );
		self::$rewriteSlugs[$name] = $alias;
		return self::$post_types[$name] = new PostType( $name, $alias, $args, $this );
	}

	/**
	 * Taxonomy Generator
	 *
	 * @access public
	 *
	 * @param  string $name
	 * @param  array|string $args
	 * @return mimosafa\WP\Repository\PostType
	 */
	public function create_taxonomy( $name, $args = [] ) {
		self::checkNameOrAliasAvailable( $name );
		$args = wp_parse_args( $args, $this->defaults );
		$alias = $this->make_alias_string( $name, $args );
		if ( ! $alias = self::regexp( $alias, '/\A[a-z][a-z_]{0,31}\z/' ) ) {
			throw new \Exception;
		}
		self::checkNameOrAliasAvailable( $alias );
		self::$rewriteSlugs[$name] = $alias;
		return self::$taxonomies[$name] = new Taxonomy( $name, $alias, $args, $this );
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
	 * @static
	 * @access private
	 *
	 * @param  string $string
	 * @return void
	 * @throws Exception
	 */
	private static function checkNameOrAliasAvailable( $string ) {
		if ( in_array( $string, self::$rewriteSlugs, true ) ) {
			throw new \Exception( "\"{$string}\" is already used as existing repository's name." );
		}
		if ( isset( self::$rewriteSlugs[$string] ) ) {
			throw new \Exception( "\"{$string}\" is already used as existing repository's alias." );
		}
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
		return $alias;
	}

}
