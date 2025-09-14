<?php
/**
 * Network Service
 *
 * @package MealPlannr
 */

namespace MealPlannr\Services;

use MealPlannr\DB\Households_Table;
use MealPlannr\DB\Network_Households_Table;
use MealPlannr\DB\Networks_Table;

/**
 * Network Service
 *
 * Handles network management business logic including invitations, membership, and removal
 */
class Network_Service {

	/**
	 * MealPlannr DB Handler
	 *
	 * @var Network_Households_Table $network_households_db
	 */
	private Network_Households_Table $network_households_db;

	/**
	 * MealPlannr DB Handler
	 *
	 * @var Networks_Table $networks_db
	 */
	private Networks_Table $networks_db;

	/**
	 * MealPlannr DB Handler
	 *
	 * @var Households_Table $household_db
	 */
	private Households_Table $household_db;

	/**
	 * Membership Service
	 *
	 * @var Network_Membership_Services $memberships_service
	 */
	private Network_Membership_Services $memberships_service;

	/**
	 * Maximum households per network
	 */
	public const MAX_HOUSEHOLDS_PER_NETWORK = 10;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->household_db          = new Households_Table();
		$this->network_households_db = new Network_Households_Table();
		$this->networks_db           = new Networks_Table();
		$this->memberships_service   = new Network_Membership_Services( $this->household_db, $this->networks_db, $this->network_households_db );
	}

	/**
	 * Create a new network
	 *
	 * @param string $name Network name
	 * @param int    $user_id User ID creating the network
	 * @return array Result with success status and data/error
	 */
	public function create_network( string $name, int $user_id ): array {
		// Get or create household for user
		$household_id = $this->household_db->get_user_household( $user_id );
		if ( ! $household_id ) {
			return array(
				'success' => false,
				'error'   => 'User must belong to a household to create a network',
			);
		}

		$network_id = $this->networks_db->create_network( $name, $user_id );
		if ( ! $network_id ) {
			return array(
				'success' => false,
				'error'   => 'Failed to create network',
			);
		}

		// Add creator's household to network as owner with accepted status
		$invitation_id = $this->network_households_db->invite_household_to_network( $network_id, $household_id );
		if ( $invitation_id ) {
			// Auto-accept the creator's household
			$this->network_households_db->accept_network_invitation( $invitation_id );
			// Update role to owner
			global $wpdb;
			$wpdb->update(
				$this->network_households_db->table_name,
				array( 'role' => 'owner' ),
				array( 'id' => $invitation_id ),
				array( '%s' ),
				array( '%d' )
			);
		}

		return array(
			'success'    => true,
			'network_id' => $network_id,
			'message'    => 'Network created successfully',
		);
	}

	/**
	 * Invite household to network
	 *
	 * @param int $network_id Network ID
	 * @param int $household_id Household ID to invite
	 * @param int $inviter_user_id User ID sending the invitation
	 * @return array Result with success status and data/error
	 */
	public function invite_household( int $network_id, int $household_id, int $inviter_user_id ): array {
		// Validate network exists
		$network = $this->networks_db->get_network( $network_id );
		if ( ! $network ) {
			return array(
				'success' => false,
				'error'   => 'Network not found',
			);
		}

		// Check if user is network owner
		if ( (int) $network->created_by !== $inviter_user_id ) {
			return array(
				'success' => false,
				'error'   => 'Only network owner can send invitations',
			);
		}

		// Check network size limit
		$current_size = $this->network_households_db->get_network_size( $network_id );
		if ( $current_size >= self::MAX_HOUSEHOLDS_PER_NETWORK ) {
			return array(
				'success' => false,
				'error'   => 'Network has reached maximum size of ' . self::MAX_HOUSEHOLDS_PER_NETWORK . ' households',
			);
		}

		// Check if household is already in network
		if ( $this->network_households_db->is_household_in_network( $network_id, $household_id ) ) {
			return array(
				'success' => false,
				'error'   => 'Household is already in this network',
			);
		}

		// Create invitation
		$invitation_id = $this->network_households_db->invite_household_to_network( $network_id, $household_id );
		if ( ! $invitation_id ) {
			return array(
				'success' => false,
				'error'   => 'Failed to create invitation',
			);
		}

		// Send email notification
		$this->send_invitation_email( $invitation_id );

		return array(
			'success'       => true,
			'invitation_id' => $invitation_id,
			'message'       => 'Invitation sent successfully',
		);
	}

	/**
	 * Accept network invitation
	 *
	 * @param int $invitation_id Invitation ID
	 * @param int $user_id User ID accepting the invitation
	 * @return array Result with success status and data/error
	 */
	public function accept_invitation( int $invitation_id, int $user_id ): array {
		$invitation = $this->memberships_service->get_invitation( $invitation_id );
		if ( ! $invitation ) {
			return array(
				'success' => false,
				'error'   => 'Invitation not found',
			);
		}

		// Check if user is household owner
		$household_owner = $this->household_db->get_household_owner( $invitation->household_id );
		if ( ! $household_owner || (int) $household_owner !== $user_id ) {
			return array(
				'success' => false,
				'error'   => 'Only household owner can accept invitations',
			);
		}

		// Check if invitation is pending
		if ( 'pending' !== $invitation->status ) {
			return array(
				'success' => false,
				'error'   => 'Invitation has already been ' . $invitation->status,
			);
		}

		// Check network size limit again
		$current_size = $this->network_households_db->get_network_size( $invitation->network_id );
		if ( $current_size >= self::MAX_HOUSEHOLDS_PER_NETWORK ) {
			return array(
				'success' => false,
				'error'   => 'Network has reached maximum size',
			);
		}

		$success = $this->network_households_db->accept_network_invitation( $invitation_id );
		if ( ! $success ) {
			return array(
				'success' => false,
				'error'   => 'Failed to accept invitation',
			);
		}

		return array(
			'success' => true,
			'message' => 'Invitation accepted successfully',
		);
	}

	/**
	 * Reject network invitation
	 *
	 * @param int $invitation_id Invitation ID
	 * @param int $user_id User ID rejecting the invitation
	 * @return array Result with success status and data/error
	 */
	public function reject_invitation( int $invitation_id, int $user_id ): array {
		$invitation = $this->memberships_service->get_invitation( $invitation_id );
		if ( ! $invitation ) {
			return array(
				'success' => false,
				'error'   => 'Invitation not found',
			);
		}

		// Check if user is household owner
		$household_owner = $this->household_db->get_household_owner( $invitation->household_id );
		if ( ! $household_owner || (int) $household_owner !== $user_id ) {
			return array(
				'success' => false,
				'error'   => 'Only household owner can reject invitations',
			);
		}

		// Check if invitation is pending
		if ( 'pending' !== $invitation->status ) {
			return array(
				'success' => false,
				'error'   => 'Invitation has already been ' . $invitation->status,
			);
		}

		$success = $this->network_households_db->reject_network_invitation( $invitation_id );
		if ( ! $success ) {
			return array(
				'success' => false,
				'error'   => 'Failed to reject invitation',
			);
		}

		return array(
			'success' => true,
			'message' => 'Invitation rejected successfully',
		);
	}

	/**
	 * Remove household from network
	 *
	 * @param int $network_id Network ID
	 * @param int $household_id Household ID to remove
	 * @param int $remover_user_id User ID removing the household
	 * @return array Result with success status and data/error
	 */
	public function remove_household( int $network_id, int $household_id, int $remover_user_id ): array {
		$network = $this->networks_db->get_network( $network_id );
		if ( ! $network ) {
			return array(
				'success' => false,
				'error'   => 'Network not found',
			);
		}

		// Check if user is network owner
		if ( (int) $network->created_by !== $remover_user_id ) {
			return array(
				'success' => false,
				'error'   => 'Only network owner can remove households',
			);
		}

		// Don't allow removing the owner's household
		$owner_household_id = $this->household_db->get_user_household( $remover_user_id );
		if ( $household_id === $owner_household_id ) {
			return array(
				'success' => false,
				'error'   => 'Cannot remove network owner household',
			);
		}

		$success = $this->network_households_db->remove_household_from_network( $network_id, $household_id );
		if ( ! $success ) {
			return array(
				'success' => false,
				'error'   => 'Failed to remove household from network',
			);
		}

		// Send optional removal notification
		$this->send_removal_email( $network_id, $household_id );

		return array(
			'success' => true,
			'message' => 'Household removed from network successfully',
		);
	}



	/**
	 * Send invitation email
	 *
	 * @param int $invitation_id Invitation ID
	 */
	private function send_invitation_email( int $invitation_id ): void {
		$invitation = $this->memberships_service->get_invitation( $invitation_id );
		if ( ! $invitation ) {
			return;
		}

		$household_owner_id = $this->household_db->get_household_owner( $invitation->household_id );
		if ( ! $household_owner_id ) {
			return;
		}

		$user = get_user_by( 'id', $household_owner_id );
		if ( ! $user ) {
			return;
		}

		$accept_url = add_query_arg(
			array(
				'action'        => 'accept_network_invitation',
				'invitation_id' => $invitation_id,
				'nonce'         => wp_create_nonce( 'accept_invitation_' . $invitation_id ),
			),
			home_url()
		);

		$reject_url = add_query_arg(
			array(
				'action'        => 'reject_network_invitation',
				'invitation_id' => $invitation_id,
				'nonce'         => wp_create_nonce( 'reject_invitation_' . $invitation_id ),
			),
			home_url()
		);

		$subject  = 'Network Invitation: ' . $invitation->network_name;
		$message  = "You have been invited to join the network '{$invitation->network_name}'.\n\n";
		$message .= "Accept: {$accept_url}\n";
		$message .= "Reject: {$reject_url}\n";

		wp_mail( $user->user_email, $subject, $message );
	}

	/**
	 * Send removal notification email
	 *
	 * @param int $network_id Network ID
	 * @param int $household_id Household ID that was removed
	 */
	private function send_removal_email( int $network_id, int $household_id ): void {
		$network = $this->networks_db->get_network( $network_id );
		if ( ! $network ) {
			return;
		}

		$household_owner_id = $this->household_db->get_household_owner( $household_id );
		if ( ! $household_owner_id ) {
			return;
		}

		$user = get_user_by( 'id', $household_owner_id );
		if ( ! $user ) {
			return;
		}

		$subject = 'Removed from Network: ' . $network->name;
		$message = "Your household has been removed from the network '{$network->name}'.\n";

		wp_mail( $user->user_email, $subject, $message );
	}
}
