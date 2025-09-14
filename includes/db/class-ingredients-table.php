<?php
/**
 * Ingredients Table
 *
 * @package MealPlannr
 * @subpackage DB
 */

namespace MealPlannr\DB;

use MealPlannr\DB\Base_Table;

/**
 * Class Ingredients_Table
 */
class Ingredients_Table extends Base_Table {

	/**
	 * Ingredients_Table constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->table_name = $this->table_prefix . 'recipe_ingredients';
	}

	/**
	 * Create the ingredients table SQL
	 *
	 * @return string
	 */
	public function create_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$ingredients_sql = "CREATE TABLE $this->table_name (
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
		$wpdb->delete( $this->table_name, array( 'recipe_id' => $recipe_id ) );
	}

	/**
	 * Insert a new ingredient
	 *
	 * @param array $data The ingredient data.
	 * @return int|false The number of rows inserted or false on failure.
	 */
	public function insert_ingredient( array $data ): int|false {
		global $wpdb;
		return $wpdb->insert( $this->table_name, $data );
	}
}
