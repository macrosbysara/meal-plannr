<?php
/**
 * Table Handler
 *
 * @package MealPlannr
 */

namespace MealPlannr;

/**
 * Table Handler
 */
class Table_Handler {

	/**
	 * Table prefix for custom db tables
	 *
	 * @var string $table_prefix
	 */
	public string $table_prefix;

	/**
	 * Ingredients table name
	 *
	 * @var string $ingredients_table
	 */
	public string $ingredients_table;

	/**
	 * Recipes table name
	 *
	 * @var string $recipes_table
	 */
	public string $recipes_table;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->table_prefix      = $wpdb->prefix . 'meal_plannr_';
		$this->ingredients_table = $this->table_prefix . 'recipe_ingredients';
		$this->recipes_table     = $this->table_prefix . 'recipes';
	}

	/**
	 * Create the necessary database tables on plugin activation.
	 *
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 */
	public function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$recipes_sql     = $this->create_recipe_table( $charset_collate );
		$ingredients_sql = $this->create_ingredients_table( $charset_collate );
		// Recipe ingredients table
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $recipes_sql );
		dbDelta( $ingredients_sql );
	}

	/**
	 * Create recipe table
	 *
	 * @param string $charset_collate The character set and collation for the table.
	 * @return string SQL statement to create the recipe table.
	 */
	private function create_recipe_table( string $charset_collate ): string {
		$recipes_sql = "CREATE TABLE $this->recipes_table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			post_id bigint(20) unsigned NOT NULL,
			macros_protein decimal(8,2) DEFAULT 0,
			macros_carbs decimal(8,2) DEFAULT 0,
			macros_fat decimal(8,2) DEFAULT 0,
			last_used datetime DEFAULT NULL,
			times_used int(11) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY post_id (post_id),
			KEY last_used (last_used)
		) $charset_collate;";
		return $recipes_sql;
	}

	/**
	 * Create ingredients table
	 *
	 * @param string $charset_collate The character set and collation for the table.
	 * @return string SQL statement to create the ingredients table.
	 */
	private function create_ingredients_table( string $charset_collate ): string {
		$ingredients_sql = "CREATE TABLE $this->ingredients_table (
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
			PRIMARY KEY  (id),
			KEY recipe_id (recipe_id)
    	) $charset_collate;";
		return $ingredients_sql;
	}

	/**
	 * Delete ingredients by recipe ID
	 *
	 * @param int $recipe_id The recipe ID.
	 */
	public function delete_ingredients( int $recipe_id ): void {
		global $wpdb;
		$wpdb->delete( $this->ingredients_table, array( 'recipe_id' => $recipe_id ) );
	}

	/**
	 * Insert a new ingredient
	 *
	 * @param array $data The ingredient data.
	 * @return int|false The number of rows inserted or false on failure.
	 */
	public function insert_ingredient( array $data ): int|false {
		global $wpdb;
		return $wpdb->insert( $this->ingredients_table, $data );
	}
}
