<?php
/**
 * Class: Theme Init
 *
 * @package MealPlannr
 */

namespace MealPlannr;

/**
 * Class Theme_Init
 *
 * Initializes the theme, registers block assets, and sets up theme support.
 */
class Theme_Init {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->load_required_files();
		add_action( 'after_setup_theme', array( $this, 'block_theme_support' ), 50 );
		add_action( 'init', array( $this, 'register_block_assets' ) );
		add_action( 'block_categories_all', array( $this, 'register_block_pattern_categories' ) );
	}

	/**
	 * Initialize the theme.
	 */
	public function init() {
		$table_handler = new Table_Handler();
		$admin_handler = new Admin_Handler();
		$admin_handler->register_roles();
	}

	/**
	 * Cleanup on plugin deactivation.
	 */
	public function cleanup() {
		$admin_handler = new Admin_Handler();
		$admin_handler->remove_roles();
	}

	/**
	 * Load required files.
	 */
	private function load_required_files() {
		$base_path = plugin_dir_path( __DIR__ ) . 'includes/';
		$files     = array(
			'cpt-handler'   => 'CPT_Handler',
			'table-handler' => null,
			'rest-router'   => 'REST_Router',
			'admin-handler' => 'Admin_Handler',
		);
		foreach ( $files as $file => $class ) {
			require_once $base_path . "class-{$file}.php";
			if ( ! is_null( $class ) ) {
				new ( __NAMESPACE__ . '\\' . $class )();
			}
		}
	}

	/**
	 * Register the block assets.
	 */
	public function register_block_assets() {
		$blocks_path = plugin_dir_path( __DIR__ ) . 'build';
		$manifest    = $blocks_path . '/blocks-manifest.php';
		if ( ! file_exists( $manifest ) ) {
			return;
		}
		if ( function_exists( 'wp_register_block_types_from_metadata_collection' ) ) {
			wp_register_block_types_from_metadata_collection( $blocks_path . '/blocks', $blocks_path . '/blocks-manifest.php' );
			return;
		}

		/**
		 * Registers the block(s) metadata from the `blocks-manifest.php` file.
		 * Added to WordPress 6.7 to improve the performance of block type registration.
		 *
		 * @see https://make.wordpress.org/core/2024/10/17/new-block-type-registration-apis-to-improve-performance-in-wordpress-6-7/
		 */
		if ( function_exists( 'wp_register_block_metadata_collection' ) ) {
			wp_register_block_metadata_collection( $blocks_path . '/blocks', $blocks_path . '/blocks-manifest.php' );
		}
		/**
		 * Registers the block type(s) in the `blocks-manifest.php` file.
		 *
		 * @see https://developer.wordpress.org/reference/functions/register_block_type/
		 */
		$manifest_data = require $manifest;
		foreach ( array_keys( $manifest_data ) as $block_type ) {
			register_block_type( "{$blocks_path}/blocks/{$block_type}" );
		}
	}

	/**
	 * Adds a custom category for the CNO Email Blocks.
	 *
	 * @param array $categories The existing block categories.
	 * @return array The modified block categories.
	 */
	public function register_block_pattern_categories( array $categories ): array {
		$new_categories = array(
			array(
				'slug'  => 'meal-plannr-blocks',
				'title' => 'Meal Plannr Blocks',
				'icon'  => null, // optional
			),
		);
		return array( ...$new_categories, ...$categories );
	}

	/**
	 * Init theme supports specific to the block editor.
	 */
	public function block_theme_support() {
		$opt_in_features = array(
			'responsive-embeds',
		);
		foreach ( $opt_in_features as $feature ) {
			add_theme_support( $feature );
		}
		$opt_out_features = array(
			'core-block-patterns',
		);
		foreach ( $opt_out_features as $feature ) {
			remove_theme_support( $feature );
		}
	}
}
