<?php
/**
 * Recipe Shares Table
 *
 * @package MealPlannr
 * @subpackage DB
 */

namespace MealPlannr\DB;

use MealPlannr\DB\Base_Table;

/**
 * Class Recipe_Shares_Table
 */
class Recipe_Shares_Table extends Base_Table {

	/**
	 * Recipe_Shares_Table constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->table_name = $this->table_prefix . 'recipe_shares';
	}

	/**
	 * Create the recipe shares table SQL
	 *
	 * @return string
	 */
	public function create_table() {
		global $wpdb;
		$charset_collate   = $wpdb->get_charset_collate();
		$recipe_shares_sql = "CREATE TABLE $this->table_name (
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
}
