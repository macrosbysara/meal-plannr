<?php
/**
 * Households Table
 *
 * @package MealPlannr
 * @subpackage DB
 */

namespace MealPlannr\DB;

use MealPlannr\DB\Base_Table;
use stdClass;

/**
 * Class Households_Table
 */
class Households_Table extends Base_Table {

	/**
	 * Households_Table constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->table_name = $this->table_prefix . 'households';
	}

	/**
	 * Create the households table SQL
	 *
	 * @return string
	 */
	public function create_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$households_sql  = "CREATE TABLE $this->table_name (
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
	 * Get user's primary household
	 *
	 * @param int $user_id User ID
	 * @return int|null Household ID or null if not found
	 */
	public function get_user_household( int $user_id ): ?int {
		global $wpdb;
		$household_id = $wpdb->get_var(
			$wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT household_id FROM {$this->table_name} WHERE user_id = %d AND role = 'owner' LIMIT 1",
				$user_id
			)
		);
		return $household_id ? (int) $household_id : null;
	}

	/**
	 * Get household by ID
	 *
	 * @param int $household_id Household ID
	 * @return object|null Household data or null if not found
	 */
	public function get_household( int $household_id ): stdClass|null {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %s WHERE id = %d LIMIT 1',
				$this->table_name,
				$household_id
			),
			OBJECT
		);
	}

	/**
	 * Check if user can manage household.
	 *
	 * @param int $user_id User ID.
	 * @param int $household_id Household ID.
	 * @return bool True if user can manage household.
	 */
	public function user_can_manage_household( int $user_id, int $household_id ): bool {
		global $wpdb;

		$role = $wpdb->get_var(
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->prepare(
				"SELECT role FROM {$this->table_name}
				 WHERE household_id = %d AND user_id = %d",
				$household_id,
				$user_id
			)
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		return 'owner' === $role || in_array( 'administrator', wp_get_current_user()->roles, true );
	}

	/**
	 * Get household owner
	 *
	 * @param int $household_id Household ID
	 * @return int|null User ID of household owner or null if not found
	 */
	public function get_household_owner( int $household_id ): ?int {
		global $wpdb;
		$user_id = $wpdb->get_var(
			$wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT user_id FROM {$this->table_name} WHERE household_id = %d AND role = 'owner' LIMIT 1",
				$household_id
			)
		);
		return $user_id ? (int) $user_id : null;
	}

	/**
	 * Create a new household.
	 *
	 * @param int    $user_id User ID.
	 * @param string $household_name Household name.
	 * @return bool True on success, false on failure.
	 */
	public function create_household( $user_id, $household_name ): int|false {
		global $wpdb;
		$result = $wpdb->insert(
			$this->table_name,
			array(
				'name'       => $household_name,
				'created_by' => $user_id,
			),
			array( '%s', '%d' )
		);

		if ( ! $result ) {
			return false;
		}

		return $wpdb->insert_id;
	}
}
