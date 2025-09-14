<?php
/**
 * Network Membership Service
 * Handles operations related to network memberships
 *
 * @package MealPlannr
 * @subpackage Services
 */

namespace MealPlannr\Services;

use Dom\Node;
use MealPlannr\DB\Households_Table;
use MealPlannr\DB\Network_Households_Table;
use MealPlannr\DB\Networks_Table;

/**
 * Class Network_Membership_Services
 */
class Network_Membership_Services {
	/**
	 * The households table instance.
	 *
	 * @var Households_Table $households_table
	 */
	protected $households_table;

	/**
	 * The network households table instance.
	 *
	 * @var Network_Households_Table $network_households_table
	 */
	protected $network_households_table;

	/**
	 * The networks table instance
	 *
	 * @var Networks_Table $networks_table
	 */
	protected Networks_Table $networks_table;

	/**
	 * Constructor
	 *
	 * @param Households_Table         $households_table The households table instance.
	 * @param Networks_Table           $networks_table The networks table instance.
	 * @param Network_Households_Table $network_households_table The network households table instance.
	 */
	public function __construct( Households_Table $households_table, Networks_Table $networks_table, Network_Households_Table $network_households_table, ) {
		$this->households_table         = $households_table;
		$this->networks_table           = $networks_table;
		$this->network_households_table = $network_households_table;
	}

	/**
	 * Get network households with status
	 *
	 * @param int    $network_id Network ID
	 * @param string $status Optional status filter
	 * @return array List of households in network
	 */
	public function get_network_households( int $network_id, string $status = '' ): array {
		global $wpdb;
		$sql    = "SELECT nh.*, h.name as household_name, h.created_by as household_owner 
				FROM {$this->network_households_table} nh 
				JOIN {$this->households_table} h ON nh.household_id = h.id 
				WHERE nh.network_id = %d";
		$params = array( $network_id );

		if ( ! empty( $status ) ) {
			$sql     .= ' AND nh.status = %s';
			$params[] = $status;
		}

		$sql .= ' ORDER BY nh.invited_at DESC';

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Get household invitations
	 *
	 * @param int    $household_id Household ID
	 * @param string $status Optional status filter
	 * @return array List of invitations for household
	 */
	public function get_household_invitations( int $household_id, string $status = 'pending' ): array {
		global $wpdb;
		$sql    = 'SELECT nh.*, n.name as network_name, n.created_by as network_owner 
				FROM `' . esc_sql( $this->network_households_table ) . '` nh 
				JOIN `' . esc_sql( $this->networks_table ) . '` n ON nh.network_id = n.id 
				WHERE nh.household_id = %d';
		$params = array( $household_id );

		if ( ! empty( $status ) ) {
			$sql     .= ' AND nh.status = %s';
			$params[] = $status;
		}

		$sql .= ' ORDER BY nh.invited_at DESC';

		return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Get invitation by ID
	 *
	 * @param int $invitation_id Invitation ID
	 * @return object|null Invitation data or null if not found
	 */
	public function get_invitation( int $invitation_id ): ?object {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				'SELECT nh.*, n.name as network_name, h.name as household_name 
				 FROM `' . esc_sql( $this->network_households_table ) . '` nh 
				 JOIN `' . esc_sql( $this->networks_table ) . '` n ON nh.network_id = n.id 
				 JOIN `' . esc_sql( $this->households_table ) . '` h ON nh.household_id = h.id 
				 WHERE nh.id = %d',
				$invitation_id
			)
		);
	}
}
