<?php
/**
 * Network Households Table
 *
 * @package MealPlannr
 * @subpackage DB
 */

namespace MealPlannr\DB;

use MealPlannr\DB\Base_Table;

/**
 * Class Network_Households_Table
 */
class Network_Households_Table extends Base_Table {

	/**
	 * Network_Households_Table constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->table_name = $this->table_prefix . 'network_households';
	}

	/**
	 * Create the network households table SQL
	 *
	 * @return string
	 */
	public function create_table() {
		global $wpdb;
		$charset_collate        = $wpdb->get_charset_collate();
		$network_households_sql = "CREATE TABLE $this->table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			network_id bigint(20) unsigned NOT NULL,
			household_id bigint(20) unsigned NOT NULL,
			role enum('owner','member') NOT NULL DEFAULT 'member',
			status enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
			invited_at datetime DEFAULT CURRENT_TIMESTAMP,
			joined_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY unique_network_household (network_id, household_id),
			KEY network_id (network_id),
			KEY household_id (household_id)
		) $charset_collate;";
		return $network_households_sql;
	}

	/**
	 * Send household invitation to network
	 *
	 * @param int $network_id Network ID
	 * @param int $household_id Household ID to invite
	 * @return int|false Invitation ID on success, false on failure
	 */
	public function invite_household_to_network( int $network_id, int $household_id ): int|false {
		global $wpdb;
		$result = $wpdb->insert(
			$this->table_name,
			array(
				'network_id'   => $network_id,
				'household_id' => $household_id,
				'status'       => 'pending',
				'role'         => 'member',
			),
			array( '%d', '%d', '%s', '%s' )
		);
		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Accept network invitation
	 *
	 * @param int $invitation_id Invitation ID
	 * @return bool Success
	 */
	public function accept_network_invitation( int $invitation_id ): bool {
		global $wpdb;
		$result = $wpdb->update(
			$this->table_name,
			array(
				'status'    => 'accepted',
				'joined_at' => current_time( 'mysql' ),
			),
			array( 'id' => $invitation_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
		return false !== $result;
	}

	/**
	 * Reject network invitation
	 *
	 * @param int $invitation_id Invitation ID
	 * @return bool Success
	 */
	public function reject_network_invitation( int $invitation_id ): bool {
		global $wpdb;
		$result = $wpdb->update(
			$this->table_name,
			array( 'status' => 'rejected' ),
			array( 'id' => $invitation_id ),
			array( '%s' ),
			array( '%d' )
		);
		return false !== $result;
	}

	/**
	 * Remove household from network
	 *
	 * @param int $network_id Network ID
	 * @param int $household_id Household ID to remove
	 * @return bool Success
	 */
	public function remove_household_from_network( int $network_id, int $household_id ): bool {
		global $wpdb;
		$result = $wpdb->delete(
			$this->table_name,
			array(
				'network_id'   => $network_id,
				'household_id' => $household_id,
			),
			array( '%d', '%d' )
		);
		return false !== $result;
	}

	/**
	 * Get network size (accepted households only)
	 *
	 * @param int $network_id Network ID
	 * @return int Number of accepted households in network
	 */
	public function get_network_size( int $network_id ): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM `' . esc_sql( $this->table_name ) . '` WHERE network_id = %d AND status = %s',
				$network_id,
				'accepted'
			)
		);
	}

	/**
	 * Check if household is already in network
	 *
	 * @param int $network_id Network ID
	 * @param int $household_id Household ID
	 * @return bool True if household is already in network (any status)
	 */
	public function is_household_in_network( int $network_id, int $household_id ): bool {
		global $wpdb;

		$count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM `' . esc_sql( $this->table_name ) . '` WHERE network_id = %d AND household_id = %d',
				$network_id,
				$household_id
			)
		);
		return (int) $count > 0;
	}
}
