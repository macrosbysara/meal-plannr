<?php
/**
 * Household Members Table
 *
 * @package MealPlannr
 * @subpackage DB
 */

namespace MealPlannr\DB;

use MealPlannr\DB\Base_Table;

/**
 * Class Household_Members_Table
 */
class Household_Members_Table extends Base_Table {
	/**
	 * Household_Members_Table constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->table_name = $this->table_prefix . 'household_members';
	}

	/**
	 * Create the household members table SQL
	 *
	 * @return string
	 */
	public function create_table() {
		global $wpdb;
		$charset_collate       = $wpdb->get_charset_collate();
		$household_members_sql = "CREATE TABLE $this->table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			household_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			role enum('owner','member','manager') NOT NULL DEFAULT 'member',
			invited_at datetime DEFAULT NULL,
			joined_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY unique_household_user (household_id, user_id),
			KEY household_id (household_id),
			KEY user_id (user_id)
		) $charset_collate;";
		return $household_members_sql;
	}
}
