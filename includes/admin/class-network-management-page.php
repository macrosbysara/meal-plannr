<?php
/**
 * Class: Network Management Page
 *
 * @package MealPlannr
 */

namespace MealPlannr\Admin;

use MealPlannr\DB\Household_Members_Table;
use MealPlannr\DB\Households_Table;
use MealPlannr\DB\Networks_Table;
use MealPlannr\Services\Network_Service;

/**
 * Class Network_Management_Page
 *
 * Manages the network management admin page where users can manage their households and networks.
 */
class Network_Management_Page {
	/**
	 * Households table instance.
	 *
	 * @var Households_Table $household_db
	 */
	private Households_Table $household_db;

	/**
	 * Household members table instance.
	 *
	 * @var Household_Members_Table $household_members_db
	 */
	private Household_Members_Table $household_members_db;

	/**
	 * Network Service instance.
	 *
	 * @var Network_Service $networks
	 */
	private Network_Service $networks;

	/**
	 * Networks table instance.
	 *
	 * @var Networks_Table $networks_db
	 */
	private Networks_Table $networks_db;

	/**
	 * Role handler instance.
	 *
	 * @var Role_Handler $roles_handler
	 */
	private Role_Handler $roles_handler;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->household_db         = new Households_Table();
		$this->household_members_db = new Household_Members_Table();
		$this->networks             = new Network_Service();
		$this->roles_handler        = new Role_Handler();
		$this->networks_db          = new Networks_Table();
	}
	/**
	 * Add network management page under Profile.
	 */
	public function add_network_management_page() {
		$current_user = wp_get_current_user();

		// Only show for household owners and members
		$allowed_roles = array( 'household_owner', 'household_member' );
		$user_roles    = array_intersect( $allowed_roles, $current_user->roles );

		if ( empty( $user_roles ) && ! in_array( 'administrator', $current_user->roles, true ) ) {
			return;
		}

		add_users_page(
			'My Networks',
			'My Networks',
			'read',
			'my-networks',
			array( $this, 'render_network_management_page' )
		);
	}

	/**
	 * Render network management page.
	 */
	public function render_network_management_page() {
		$current_user = wp_get_current_user();

		// Handle form submissions
		$this->handle_network_form_submissions();

		// Get user's current household
		$household_id = $this->household_db->get_user_household( $current_user->ID );
		if ( ! $household_id ) {
			$household_id = null;
		}
		$household         = $household_id ? $this->household_db->get_household( $household_id ) : null;
		$household_members = $household ? $this->household_members_db->get_household_members( $household_id ) : array();

		// Get user's networks
		$networks = $this->networks_db->get_user_networks( $current_user->ID );
		?>
		<div class="wrap">
			<h1>My Networks</h1>
			
			<?php if ( isset( $_GET['message'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html( $this->get_message_text( $_GET['message'] ) ); ?></p>
				</div>
			<?php endif; ?>
			
			<?php if ( isset( $_GET['error'] ) ) : ?>
				<div class="notice notice-error is-dismissible">
					<p><?php echo esc_html( $this->get_error_text( $_GET['error'] ) ); ?></p>
				</div>
			<?php endif; ?>
			
			<div class="card">
				<h2>Current Household</h2>
				<?php if ( $household ) : ?>
					<p><strong>Household:</strong> <?php echo esc_html( $household->name ); ?></p>
					<p><strong>Members:</strong></p>
					<ul>
						<?php foreach ( $household_members as $member ) : ?>
							<li>
								<?php echo esc_html( $member->display_name ); ?> 
								<em>(<?php echo esc_html( ucfirst( $member->role ) ); ?>)</em>
								<?php if ( $member->user_id === $current_user->ID ) : ?>
									<strong>(You)</strong>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
					
					<?php if ( $this->household_db->user_can_manage_household( $current_user->ID, $household_id ) ) : ?>
						<h3>Invite Member</h3>
						<form method="post" action="">
							<?php wp_nonce_field( 'invite_household_member', 'invite_member_nonce' ); ?>
							<input type="hidden" name="action" value="invite_household_member" />
							<p>
								<label for="member_email">Email:</label>
								<input type="email" name="member_email" id="member_email" required />
								<input type="submit" value="Send Invitation" class="button" />
							</p>
						</form>
					<?php endif; ?>
				<?php else : ?>
					<p>You are not currently part of a household.</p>
					<h3>Create Household</h3>
					<form method="post" action="">
						<?php wp_nonce_field( 'create_household', 'create_household_nonce' ); ?>
						<input type="hidden" name="action" value="create_household" />
						<p>
							<label for="household_name">Household Name:</label>
							<input type="text" name="household_name" id="household_name" required />
							<input type="submit" value="Create Household" class="button button-primary" />
						</p>
					</form>
				<?php endif; ?>
			</div>
			
			<div class="card">
				<h2>Network Management</h2>
				<?php if ( ! empty( $networks ) ) : ?>
					<p><strong>Your Networks:</strong></p>
					<ul>
						<?php foreach ( $networks as $network ) : ?>
							<li>
								<?php echo esc_html( $network->name ); ?>
								<em>(<?php echo esc_html( $network->household_count ); ?> households)</em>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p>You are not currently part of any networks.</p>
				<?php endif; ?>
				
				<?php if ( $household && $this->household_db->user_can_manage_household( $current_user->ID, $household_id ) ) : ?>
					<h3>Create Network</h3>
					<form method="post" action="">
						<?php wp_nonce_field( 'create_network', 'create_network_nonce' ); ?>
						<input type="hidden" name="action" value="create_network" />
						<p>
							<label for="network_name">Network Name:</label>
							<input type="text" name="network_name" id="network_name" required />
							<input type="submit" value="Create Network" class="button" />
						</p>
					</form>
					
					<h3>Invite Household to Network</h3>
					<form method="post" action="">
						<?php wp_nonce_field( 'invite_household_to_network', 'invite_network_nonce' ); ?>
						<input type="hidden" name="action" value="invite_household_to_network" />
						<p>
							<label for="household_email">Household Owner Email:</label>
							<input type="email" name="household_email" id="household_email" required />
							<input type="submit" value="Send Network Invitation" class="button" />
						</p>
					</form>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle form submissions on network management page.
	 */
	private function handle_network_form_submissions() {
		if ( ! isset( $_POST['action'] ) ) {
			return;
		}

		$action       = $_POST['action']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$current_user = wp_get_current_user();

		switch ( $action ) {
			case 'create_household':
				if ( ! wp_verify_nonce( $_POST['create_household_nonce'], 'create_household' ) ) {
					return;
				}

				$household_name = sanitize_text_field( $_POST['household_name'] );
				if ( $this->create_household( $current_user->ID, $household_name ) ) {
					wp_safe_redirect( add_query_arg( 'message', 'household_created', admin_url( 'users.php?page=my-networks' ) ) );
					exit;
				} else {
					wp_safe_redirect(
						add_query_arg( 'error', 'household_creation_failed', admin_url( 'users.php?page=my-networks' ) )
					);
					exit;
				}
				break;

			case 'create_network':
				if ( ! wp_verify_nonce( $_POST['create_network_nonce'], 'create_network' ) ) {
					return;
				}

				$network_name = sanitize_text_field( $_POST['network_name'] );
				if ( $this->networks->create_network( $current_user->ID, $network_name ) ) {
					wp_safe_redirect( add_query_arg( 'message', 'network_created', admin_url( 'users.php?page=my-networks' ) ) );
					exit;
				} else {
					wp_safe_redirect(
						add_query_arg( 'error', 'network_creation_failed', admin_url( 'users.php?page=my-networks' ) )
					);
					exit;
				}
				break;
		}
	}

	/**
	 * Get message text for success messages.
	 *
	 * @param string $message Message code.
	 * @return string Message text.
	 */
	private function get_message_text( $message ) {
		$messages = array(
			'household_created' => 'Household created successfully!',
			'network_created'   => 'Network created successfully!',
		);

		return isset( $messages[ $message ] ) ? $messages[ $message ] : 'Operation completed successfully.';
	}

	/**
	 * Get error text for error messages.
	 *
	 * @param string $error Error code.
	 * @return string Error text.
	 */
	private function get_error_text( $error ) {
		$errors = array(
			'household_creation_failed' => 'Failed to create household. You may already be in a household.',
			'network_creation_failed'   => 'Failed to create network. Please try again.',
		);

		return isset( $errors[ $error ] ) ? $errors[ $error ] : 'An error occurred.';
	}

	/**
	 * Create a new household and add current user as owner.
	 *
	 * @param int    $user_id User ID.
	 * @param string $household_name Household name.
	 * @return bool True on success, false on failure.
	 */
	private function create_household( $user_id, $household_name ) {
		if ( $this->household_members_db->is_user_in_household( $user_id ) ) {
			return false;
		}
		$household_id = $this->household_db->create_household( $user_id, $household_name );

		if ( ! $household_id ) {
			return false;
		}
		$result = $this->household_members_db->add_user_as_owner( $user_id, $household_id );
		if ( ! $result ) {
			return false;
		}
		return (bool) $this->roles_handler->set_user_to_household_owner( $user_id );
	}
}
