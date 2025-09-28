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

	/**
	 * Get household members.
	 *
	 * @param int $household_id Household ID.
	 * @return array Array of member objects.
	 */
	public function get_household_members( int $household_id ): array {
		global $wpdb;

		$members = $wpdb->get_results(
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->prepare(
				"SELECT u.display_name, hm.role, hm.user_id
				 FROM {$this->table_name} hm
				 INNER JOIN {$wpdb->users} u ON hm.user_id = u.ID
				 WHERE hm.household_id = %d
				 ORDER BY hm.role = 'owner' DESC, u.display_name ASC",
				$household_id
			)
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		return $members;
	}

	/**
	 * Check if a user is in a household.
	 *
	 * @param int $user_id User ID.
	 * @return bool True if user is in household, false otherwise.
	 */
	public function is_user_in_household( $user_id ) {
		global $wpdb;

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = %d",
				$user_id,
			)
		);

		return $count > 0;
	}

	/**
	 * Add user as owner to household.
	 *
	 * @param int $user_id User ID.
	 * @param int $household_id Household ID.
	 * @return bool True on success, false on failure.
	 */
	public function add_user_as_owner( int $user_id, int $household_id ): bool {
		global $wpdb;

		// Add user as owner
		$result = $wpdb->insert(
			$this->table_name,
			array(
				'household_id' => $household_id,
				'user_id'      => $user_id,
				'role'         => 'owner',
			),
			array( '%d', '%d', '%s' )
		);
		return (bool) $result;
	}
}
