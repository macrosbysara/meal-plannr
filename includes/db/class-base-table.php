<?php
/**
 * Base Table Class
 *
 * @package MealPlannr
 * @subpackage DB
 */

namespace MealPlannr\DB;

/**
 * Class Base_Table
 *
 * @package MealPlannr\DB
 */
abstract class Base_Table {
	/**
	 * Table prefix for custom db tables
	 *
	 * @var string $table_prefix
	 */
	public string $table_prefix;

	/**
	 * Full table name including prefix
	 *
	 * @var string $table_name
	 */
	public string $table_name;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->table_prefix = $wpdb->prefix . 'meal_plannr_';
	}

	/**
	 * SQL to create the table
	 *
	 * @return string
	 */
	abstract public function create_table();

	/**
	 * Install the table in the database
	 */
	public function install() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $this->create_table() );
	}
}
