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
	 * Households table name
	 *
	 * @var string $households_table
	 */
	public string $households_table;

	/**
	 * Household members table name
	 *
	 * @var string $household_members_table
	 */
	public string $household_members_table;

	/**
	 * Networks table name
	 *
	 * @var string $networks_table
	 */
	public string $networks_table;

	/**
	 * Network households table name
	 *
	 * @var string $network_households_table
	 */
	public string $network_households_table;

	/**
	 * Recipe shares table name
	 *
	 * @var string $recipe_shares_table
	 */
	public string $recipe_shares_table;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->table_prefix             = $wpdb->prefix . 'meal_plannr_';
		$this->ingredients_table        = $this->table_prefix . 'recipe_ingredients';
		$this->recipes_table            = $this->table_prefix . 'recipes';
		$this->households_table         = $this->table_prefix . 'households';
		$this->household_members_table  = $this->table_prefix . 'household_members';
		$this->networks_table           = $this->table_prefix . 'networks';
		$this->network_households_table = $this->table_prefix . 'network_households';
		$this->recipe_shares_table      = $this->table_prefix . 'recipe_shares';
		$this->create_tables();
	}

	/**
	 * Create the necessary database tables on plugin activation.
	 *
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 */
	public function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Existing tables
		$recipes_sql     = $this->create_recipe_table( $charset_collate );
		$ingredients_sql = $this->create_ingredients_table( $charset_collate );

		// New custom tables for households, networks and sharing
		$households_sql         = $this->create_households_table( $charset_collate );
		$household_members_sql  = $this->create_household_members_table( $charset_collate );
		$networks_sql           = $this->create_networks_table( $charset_collate );
		$network_households_sql = $this->create_network_households_table( $charset_collate );
		$recipe_shares_sql      = $this->create_recipe_shares_table( $charset_collate );

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Create all tables
		dbDelta( $recipes_sql );
		dbDelta( $ingredients_sql );
		dbDelta( $households_sql );
		dbDelta( $household_members_sql );
		dbDelta( $networks_sql );
		dbDelta( $network_households_sql );
		dbDelta( $recipe_shares_sql );
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
			protein decimal(8,2) DEFAULT 0,
			carbs decimal(8,2) DEFAULT 0,
			fat decimal(8,2) DEFAULT 0,
			calories decimal(8,2) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY post_id (post_id)
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
	 * Create households table
	 *
	 * @param string $charset_collate The character set and collation for the table.
	 * @return string SQL statement to create the households table.
	 */
	private function create_households_table( string $charset_collate ): string {
		$households_sql = "CREATE TABLE $this->households_table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			created_by bigint(20) unsigned NOT NULL,
			max_members int(11) DEFAULT 4,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY created_by (created_by)
		) $charset_collate;";
		return $households_sql;
	}

	/**
	 * Create household members table
	 *
	 * @param string $charset_collate The character set and collation for the table.
	 * @return string SQL statement to create the household members table.
	 */
	private function create_household_members_table( string $charset_collate ): string {
		$household_members_sql = "CREATE TABLE $this->household_members_table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			household_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			role enum('owner','member','child','manager') NOT NULL DEFAULT 'member',
			invited_at datetime DEFAULT NULL,
			joined_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY unique_household_user (household_id, user_id),
			KEY household_id (household_id),
			KEY user_id (user_id)
		) $charset_collate;";
		return $household_members_sql;
	}

	/**
	 * Create networks table
	 *
	 * @param string $charset_collate The character set and collation for the table.
	 * @return string SQL statement to create the networks table.
	 */
	private function create_networks_table( string $charset_collate ): string {
		$networks_sql = "CREATE TABLE $this->networks_table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			created_by bigint(20) unsigned NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY created_by (created_by)
		) $charset_collate;";
		return $networks_sql;
	}

	/**
	 * Create network households table
	 *
	 * @param string $charset_collate The character set and collation for the table.
	 * @return string SQL statement to create the network households table.
	 */
	private function create_network_households_table( string $charset_collate ): string {
		$network_households_sql = "CREATE TABLE $this->network_households_table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			network_id bigint(20) unsigned NOT NULL,
			household_id bigint(20) unsigned NOT NULL,
			role enum('owner','member') NOT NULL DEFAULT 'member',
			joined_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY unique_network_household (network_id, household_id),
			KEY network_id (network_id),
			KEY household_id (household_id)
		) $charset_collate;";
		return $network_households_sql;
	}

	/**
	 * Create recipe shares table
	 *
	 * @param string $charset_collate The character set and collation for the table.
	 * @return string SQL statement to create the recipe shares table.
	 */
	private function create_recipe_shares_table( string $charset_collate ): string {
		$recipe_shares_sql = "CREATE TABLE $this->recipe_shares_table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			recipe_id bigint(20) unsigned NOT NULL,
			visibility enum('private','household','network','public') NOT NULL DEFAULT 'private',
			household_id bigint(20) unsigned DEFAULT NULL,
			network_id bigint(20) unsigned DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY recipe_id (recipe_id),
			KEY household_id (household_id),
			KEY network_id (network_id)
		) $charset_collate;";
		return $recipe_shares_sql;
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

	/**
	 * Update the macro nutrient values for a recipe.
	 *
	 * @param int   $recipe_id The recipe ID.
	 * @param array $data The macro nutrient data.
	 * @return bool True on success, false on failure.
	 */
	public function update_macros( int $recipe_id, array $data ): bool {
		global $wpdb;

		// Check if row exists for this post_id
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM `{$wpdb->prefix}meal_plannr_recipes` WHERE post_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$recipe_id
			)
		);

		if ( $exists ) {
			$updated = $wpdb->update(
				$this->recipes_table,
				$data,
				array( 'post_id' => $recipe_id ),
				array( '%f', '%f', '%f', '%f' ),
				array( '%d' )
			);
			return (bool) $updated;
		} else {
			// Add post_id to data for insert
			$data['post_id'] = $recipe_id;
			$inserted        = $wpdb->insert(
				$this->recipes_table,
				$data,
				array( '%d', '%f', '%f', '%f', '%f' )
			);
			return (bool) $inserted;
		}
	}

	/**
	 * Delete Macros
	 *
	 * @param int $recipe_id the recipe to delete macros from
	 */
	public function clear_macros( int $recipe_id ): bool {
		global $wpdb;

		$updated = $wpdb->update(
			$this->recipes_table,
			array(
				'protein'  => null,
				'carbs'    => null,
				'fat'      => null,
				'calories' => null,
			),
			array( 'post_id' => $recipe_id ),
			array( '%f', '%f', '%f', '%f' ),
			array( '%d' )
		);
		return false !== $updated;
	}
}
