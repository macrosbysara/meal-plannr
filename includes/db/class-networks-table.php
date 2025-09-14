<?php
/**
 * Networks Table
 *
 * @package MealPlannr
 * @subpackage DB
 */

namespace MealPlannr\DB;

use MealPlannr\DB\Base_Table;

/**
 * Class Networks_Table
 */
class Networks_Table extends Base_Table {

	/**
	 * Networks_Table constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->table_name = $this->table_prefix . 'networks';
	}

	/**
	 * Create the networks table SQL
	 *
	 * @return string
	 */
	public function create_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$networks_sql    = "CREATE TABLE $this->table_name (
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
	 * Create a new network
	 *
	 * @param string $name Network name
	 * @param int    $created_by User ID creating the network
	 * @return int|false Network ID on success, false on failure
	 */
	public function create_network( string $name, int $created_by ): int|false {
		global $wpdb;
		$result = $wpdb->insert(
			$this->table_name,
			array(
				'name'       => $name,
				'created_by' => $created_by,
			),
			array( '%s', '%d' )
		);
		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get network by ID
	 *
	 * @param int $network_id Network ID
	 * @return object|null Network data or null if not found
	 */
	public function get_network( int $network_id ): ?object {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM {$this->table_name} WHERE id = %d",
				$network_id
			)
		);
	}

	/**
	 * Get networks by user ID (networks they created)
	 *
	 * @param int $user_id User ID
	 * @return array List of networks
	 */
	public function get_user_networks( int $user_id ): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM {$this->table_name} WHERE created_by = %d ORDER BY created_at DESC",
				$user_id
			)
		);
	}
}
