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
		$this->init();
	}

	/**
	 * Initialize the theme.
	 */
	public function init() {
		register_activation_hook( __FILE__, array( $this, 'create_tables' ) );
		add_action( 'after_setup_theme', array( $this, 'block_theme_support' ), 50 );

		add_action( 'init', array( $this, 'register_block_assets' ) );
		add_action( 'block_categories_all', array( $this, 'register_block_pattern_categories' ) );
	}

	/**
	 * Create the necessary database tables on plugin activation.
	 *
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 */
	public function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Recipes table
		$recipes_table = $wpdb->prefix . 'meal_plannr_recipes';
		$recipes_sql   = "CREATE TABLE $recipes_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        post_id bigint(20) unsigned NOT NULL,
        macros_protein decimal(8,2) DEFAULT 0,
        macros_carbs decimal(8,2) DEFAULT 0,
        macros_fat decimal(8,2) DEFAULT 0,
        last_used datetime DEFAULT NULL,
        times_used int(11) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY post_id (post_id),
        KEY last_used (last_used)
    ) $charset_collate;";

		// Recipe ingredients table
		$ingredients_table = $wpdb->prefix . 'meal_plannr_recipe_ingredients';
		$ingredients_sql   = "CREATE TABLE $ingredients_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        recipe_id bigint(20) unsigned NOT NULL,
        name varchar(255) NOT NULL,
        quantity_volume decimal(8,2) DEFAULT NULL,
        unit_volume varchar(50) DEFAULT NULL,
        quantity_weight decimal(8,2) DEFAULT NULL,
        unit_weight varchar(50) DEFAULT NULL,
        notes text DEFAULT NULL,
        sort_order int(11) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY recipe_id (recipe_id)
    ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $recipes_sql );
		dbDelta( $ingredients_sql );
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
