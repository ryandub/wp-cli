<?php

namespace WP_CLI\Dispatcher;

/**
 * A non-leaf node in the command tree.
 */
class CompositeCommand {

	protected $name, $shortdesc, $synopsis;

	protected $parent, $subcommands = array();

	public function __construct( $parent, $name, $docparser ) {
		$this->parent = $parent;

		$this->name = $name;

		$this->shortdesc = $docparser->get_shortdesc();

		$when_to_invoke = $docparser->get_tag( 'when' );
		if ( $when_to_invoke ) {
			\WP_CLI::$runner->register_early_invoke( $when_to_invoke, $this );
		}
	}

	function get_parent() {
		return $this->parent;
	}

	function add_subcommand( $name, $command ) {
		$this->subcommands[ $name ] = $command;
	}

	function has_subcommands() {
		return !empty( $this->subcommands );
	}

	function get_subcommands() {
		ksort( $this->subcommands );

		return $this->subcommands;
	}

	function get_name() {
		return $this->name;
	}

	function get_shortdesc() {
		return $this->shortdesc;
	}

	function get_synopsis() {
		return '<subcommand>';
	}

	function invoke( $args, $assoc_args ) {
		$this->show_usage();
	}

	function show_usage() {
		$methods = $this->get_subcommands();

		$i = 0;

		foreach ( $methods as $name => $subcommand ) {
			$prefix = ( 0 == $i++ ) ? 'usage: ' : '   or: ';

			$subcommand->show_usage( $prefix );
		}

		\WP_CLI::line();
		\WP_CLI::line( "See 'wp help $this->name <subcommand>' for more information on a specific subcommand." );
	}

	function get_extra_markdown() {
		$md_file = self::find_extra_markdown_file( $this );
		if ( !$md_file )
			return '';

		return file_get_contents( $md_file );
	}

	private static function find_extra_markdown_file( $command ) {
		$cmd_path = get_path( $command );
		array_shift( $cmd_path ); // discard 'wp'
		$cmd_path = implode( '-', $cmd_path );

		foreach ( \WP_CLI::get_man_dirs() as $src_dir ) {
			$src_path = "$src_dir/$cmd_path.txt";
			if ( is_readable( $src_path ) )
				return $src_path;
		}

		return false;
	}

	function find_subcommand( &$args ) {
		$name = array_shift( $args );

		$subcommands = $this->get_subcommands();

		if ( !isset( $subcommands[ $name ] ) ) {
			$aliases = self::get_aliases( $subcommands );

			if ( isset( $aliases[ $name ] ) ) {
				$name = $aliases[ $name ];
			}
		}

		if ( !isset( $subcommands[ $name ] ) )
			return false;

		return $subcommands[ $name ];
	}

	private static function get_aliases( $subcommands ) {
		$aliases = array();

		foreach ( $subcommands as $name => $subcommand ) {
			$alias = $subcommand->get_alias();
			if ( $alias )
				$aliases[ $alias ] = $name;
		}

		return $aliases;
	}
}

