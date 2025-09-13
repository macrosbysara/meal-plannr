<?php

/**
 * Admin Handler
 *
 * @package MealPlannr
 */

namespace MealPlannr;

/**
 * Class Admin_Handler
 *
 * Handles WordPress admin functionality including roles, capabilities, and admin interface.
 */
class Admin_Handler {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Hook into admin initialization
		add_action( 'admin_init', array( $this, 'restrict_admin_access' ) );
		add_action( 'admin_menu', array( $this, 'customize_admin_menu' ), 999 );
		add_filter( 'user_has_cap', array( $this, 'filter_user_capabilities' ), 10, 3 );

		// Add network management page
		add_action( 'admin_menu', array( $this, 'add_network_management_page' ) );
	}

	/**
	 * Register custom roles and capabilities.
	 * Called on plugin activation.
	 */
	public function register_roles() {
		// Remove existing roles first to ensure clean state
		remove_role( 'household_owner' );
		remove_role( 'household_member' );

		// Add household_owner role
		add_role(
			'household_owner',
			'Household Owner',
			array(
				'read'             => true,
				'edit_recipes'     => true,
				'publish_recipes'  => true,
				'delete_recipes'   => true,
				'manage_household' => true,
			)
		);

		// Add household_member role
		add_role(
			'household_member',
			'Household Member',
			array(
				'read'            => true,
				'edit_recipes'    => true,
				'publish_recipes' => true,
			)
		);

		// Also add these capabilities to administrators
		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			$admin_role->add_cap( 'edit_recipes' );
			$admin_role->add_cap( 'publish_recipes' );
			$admin_role->add_cap( 'delete_recipes' );
			$admin_role->add_cap( 'manage_household' );
		}
	}

	/**
	 * Clean up custom roles.
	 * Called on plugin deactivation.
	 */
	public function remove_roles() {
		remove_role( 'household_owner' );
		remove_role( 'household_member' );
	}

	/**
	 * Restrict admin access for non-admin users.
	 */
	public function restrict_admin_access() {
		// Skip AJAX requests
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		$current_user = wp_get_current_user();

		// Allow full access for administrators
		if ( in_array( 'administrator', $current_user->roles ) ) {
			return;
		}

		// Check if user has household roles
		$allowed_roles = array( 'household_owner', 'household_member' );
		$user_roles    = array_intersect( $allowed_roles, $current_user->roles );

		if ( empty( $user_roles ) ) {
			return; // Let WordPress handle unauthorized users
		}

		// Get current page
		global $pagenow;
		$current_screen = get_current_screen();

		// Always allow profile and network management pages
		if (
			$pagenow === 'profile.php' ||
			( isset( $_GET['page'] ) && $_GET['page'] === 'my-networks' )
		) {
			return;
		}

		// Allow recipe post type pages
		if ( isset( $_GET['post_type'] ) && $_GET['post_type'] === 'recipe' ) {
			return;
		}

		// Allow editing individual recipes
		if ( $pagenow === 'post.php' || $pagenow === 'post-new.php' ) {
			$post_type = 'recipe'; // Default for new posts
			if ( isset( $_GET['post'] ) ) {
				$post = get_post( $_GET['post'] );
				if ( $post ) {
					$post_type = $post->post_type;
				}
			} elseif ( isset( $_GET['post_type'] ) ) {
				$post_type = $_GET['post_type'];
			}

			if ( $post_type === 'recipe' ) {
				return;
			}
		}

		// Allow admin-ajax.php
		if ( $pagenow === 'admin-ajax.php' ) {
			return;
		}

		// Redirect to recipes page for unauthorized access
		if ( $pagenow !== 'edit.php' || ! isset( $_GET['post_type'] ) || $_GET['post_type'] !== 'recipe' ) {
			wp_redirect( admin_url( 'edit.php?post_type=recipe' ) );
			exit;
		}
	}

	/**
	 * Customize admin menu for non-admin users.
	 */
	public function customize_admin_menu() {
		$current_user = wp_get_current_user();

		// Don't modify menu for administrators
		if ( in_array( 'administrator', $current_user->roles ) ) {
			return;
		}

		// Check if user has household roles
		$allowed_roles = array( 'household_owner', 'household_member' );
		$user_roles    = array_intersect( $allowed_roles, $current_user->roles );

		if ( empty( $user_roles ) ) {
			return;
		}

		// Remove unwanted menu items
		$menus_to_remove = array(
			'index.php',                 // Dashboard
			'edit.php',                  // Posts
			'upload.php',                // Media
			'edit.php?post_type=page',   // Pages
			'edit-comments.php',         // Comments
			'themes.php',                // Appearance
			'plugins.php',               // Plugins
			'users.php',                 // Users
			'tools.php',                 // Tools
			'options-general.php',       // Settings
		);

		foreach ( $menus_to_remove as $menu ) {
			remove_menu_page( $menu );
		}

		// Remove admin bar items
		add_action( 'wp_before_admin_bar_render', array( $this, 'customize_admin_bar' ) );
	}

	/**
	 * Customize admin bar for non-admin users.
	 */
	public function customize_admin_bar() {
		global $wp_admin_bar;

		$current_user = wp_get_current_user();

		// Don't modify admin bar for administrators
		if ( in_array( 'administrator', $current_user->roles ) ) {
			return;
		}

		// Remove unwanted admin bar items
		$items_to_remove = array(
			'new-content',
			'comments',
			'updates',
			'themes',
			'customize',
		);

		foreach ( $items_to_remove as $item ) {
			$wp_admin_bar->remove_node( $item );
		}
	}

	/**
	 * Filter user capabilities based on role.
	 *
	 * @param array $allcaps All capabilities for the user.
	 * @param array $caps    Requested capabilities.
	 * @param array $args    Arguments.
	 * @return array Modified capabilities.
	 */
	public function filter_user_capabilities( $allcaps, $caps, $args ) {
		$current_user = wp_get_current_user();

		// Don't modify capabilities for administrators
		if ( in_array( 'administrator', $current_user->roles ) ) {
			return $allcaps;
		}

		// Handle recipe-related capabilities
		if ( in_array( 'edit_posts', $caps ) || in_array( 'delete_posts', $caps ) ) {
			// Check if user is dealing with recipe post type
			if ( isset( $args[2] ) ) {
				$post = get_post( $args[2] );
				if ( $post && $post->post_type === 'recipe' ) {
					// Allow recipe operations for household roles
					if (
						in_array( 'household_owner', $current_user->roles ) ||
						in_array( 'household_member', $current_user->roles )
					) {
						$allcaps['edit_posts']   = true;
						$allcaps['delete_posts'] = true;
					}
				}
			}
		}

		return $allcaps;
	}

	/**
	 * Add network management page under Profile.
	 */
	public function add_network_management_page() {
		$current_user = wp_get_current_user();

		// Only show for household owners and members
		$allowed_roles = array( 'household_owner', 'household_member' );
		$user_roles    = array_intersect( $allowed_roles, $current_user->roles );

		if ( empty( $user_roles ) && ! in_array( 'administrator', $current_user->roles ) ) {
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
		$household         = $this->get_user_household( $current_user->ID );
		$household_members = $household ? $this->get_household_members( $household->id ) : array();

		// Get user's networks
		$networks = $this->get_user_networks( $current_user->ID );
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
					
					<?php if ( $this->user_can_manage_household( $current_user->ID, $household->id ) ) : ?>
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
				
				<?php if ( $household && $this->user_can_manage_household( $current_user->ID, $household->id ) ) : ?>
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

		$action       = $_POST['action'];
		$current_user = wp_get_current_user();

		switch ( $action ) {
			case 'create_household':
				if ( ! wp_verify_nonce( $_POST['create_household_nonce'], 'create_household' ) ) {
					return;
				}

				$household_name = sanitize_text_field( $_POST['household_name'] );
				if ( $this->create_household( $current_user->ID, $household_name ) ) {
					wp_redirect( add_query_arg( 'message', 'household_created', admin_url( 'users.php?page=my-networks' ) ) );
					exit;
				} else {
					wp_redirect(
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
				if ( $this->create_network( $current_user->ID, $network_name ) ) {
					wp_redirect( add_query_arg( 'message', 'network_created', admin_url( 'users.php?page=my-networks' ) ) );
					exit;
				} else {
					wp_redirect(
						add_query_arg( 'error', 'network_creation_failed', admin_url( 'users.php?page=my-networks' ) )
					);
					exit;
				}
				break;
		}
	}

	/**
	 * Get user's household.
	 *
	 * @param int $user_id User ID.
	 * @return object|null Household object or null.
	 */
	private function get_user_household( $user_id ) {
		global $wpdb;

		$table_handler = new Table_Handler();

		$household = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT h.* FROM {$table_handler->households_table} h
				 INNER JOIN {$table_handler->household_members_table} hm ON h.id = hm.household_id
				 WHERE hm.user_id = %d",
				$user_id
			)
		);

		return $household;
	}

	/**
	 * Get household members.
	 *
	 * @param int $household_id Household ID.
	 * @return array Array of member objects.
	 */
	private function get_household_members( $household_id ) {
		global $wpdb;

		$table_handler = new Table_Handler();

		$members = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT u.display_name, hm.role, hm.user_id
				 FROM {$table_handler->household_members_table} hm
				 INNER JOIN {$wpdb->users} u ON hm.user_id = u.ID
				 WHERE hm.household_id = %d
				 ORDER BY hm.role = 'owner' DESC, u.display_name ASC",
				$household_id
			)
		);

		return $members;
	}

	/**
	 * Get user's networks.
	 *
	 * @param int $user_id User ID.
	 * @return array Array of network objects.
	 */
	private function get_user_networks( $user_id ) {
		global $wpdb;

		$table_handler = new Table_Handler();

		$networks = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT n.*, COUNT(nh.household_id) as household_count
				 FROM {$table_handler->networks_table} n
				 INNER JOIN {$table_handler->network_households_table} nh ON n.id = nh.network_id
				 INNER JOIN {$table_handler->household_members_table} hm ON nh.household_id = hm.household_id
				 WHERE hm.user_id = %d
				 GROUP BY n.id",
				$user_id
			)
		);

		return $networks;
	}

	/**
	 * Check if user can manage household.
	 *
	 * @param int $user_id User ID.
	 * @param int $household_id Household ID.
	 * @return bool True if user can manage household.
	 */
	private function user_can_manage_household( $user_id, $household_id ) {
		global $wpdb;

		$table_handler = new Table_Handler();

		$role = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT role FROM {$table_handler->household_members_table}
				 WHERE household_id = %d AND user_id = %d",
				$household_id,
				$user_id
			)
		);

		return $role === 'owner';
	}

	/**
	 * Create a new household.
	 *
	 * @param int    $user_id User ID.
	 * @param string $household_name Household name.
	 * @return bool True on success, false on failure.
	 */
	private function create_household( $user_id, $household_name ) {
		global $wpdb;

		$table_handler = new Table_Handler();

		// Check if user is already in a household
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_handler->household_members_table} WHERE user_id = %d",
				$user_id
			)
		);

		if ( $existing > 0 ) {
			return false;
		}

		// Create household
		$result = $wpdb->insert(
			$table_handler->households_table,
			array(
				'name'       => $household_name,
				'created_by' => $user_id,
			),
			array( '%s', '%d' )
		);

		if ( ! $result ) {
			return false;
		}

		$household_id = $wpdb->insert_id;

		// Add user as owner
		$result = $wpdb->insert(
			$table_handler->household_members_table,
			array(
				'household_id' => $household_id,
				'user_id'      => $user_id,
				'role'         => 'owner',
			),
			array( '%d', '%d', '%s' )
		);

		// Update user role
		$user       = new \WP_User( $user_id );
		$user_roles = $user->roles;
		if ( ! in_array( 'household_owner', $user_roles ) ) {
			$user_roles[] = 'household_owner';
			$result = update_user_meta( $user_id, 'additional_roles', $user_roles );
		}

		return (bool) $result;
	}

	/**
	 * Create a new network.
	 *
	 * @param int    $user_id User ID.
	 * @param string $network_name Network name.
	 * @return bool True on success, false on failure.
	 */
	private function create_network( $user_id, $network_name ) {
		global $wpdb;

		$table_handler = new Table_Handler();

		// Get user's household
		$household = $this->get_user_household( $user_id );
		if ( ! $household || ! $this->user_can_manage_household( $user_id, $household->id ) ) {
			return false;
		}

		// Create network
		$result = $wpdb->insert(
			$table_handler->networks_table,
			array(
				'name'       => $network_name,
				'created_by' => $user_id,
			),
			array( '%s', '%d' )
		);

		if ( ! $result ) {
			return false;
		}

		$network_id = $wpdb->insert_id;

		// Add household to network as owner
		$result = $wpdb->insert(
			$table_handler->network_households_table,
			array(
				'network_id'   => $network_id,
				'household_id' => $household->id,
				'role'         => 'owner',
			),
			array( '%d', '%d', '%s' )
		);

		return (bool) $result;
	}

	/**
	 * Validate network size limit.
	 *
	 * @param int $network_id Network ID.
	 * @return bool True if network can accept more households.
	 */
	private function validate_network_size_limit( $network_id ) {
		global $wpdb;

		$table_handler = new Table_Handler();

		$household_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_handler->network_households_table} WHERE network_id = %d",
				$network_id
			)
		);

		return $household_count < 10; // Max 10 households per network
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
}
