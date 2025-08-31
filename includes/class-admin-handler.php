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
		$user_roles = array_intersect( $allowed_roles, $current_user->roles );
		
		if ( empty( $user_roles ) ) {
			return; // Let WordPress handle unauthorized users
		}

		// Get current page
		global $pagenow;
		$current_screen = get_current_screen();
		
		// Always allow profile and network management pages
		if ( $pagenow === 'profile.php' || 
			 ( isset( $_GET['page'] ) && $_GET['page'] === 'my-networks' ) ) {
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
		$user_roles = array_intersect( $allowed_roles, $current_user->roles );
		
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
					if ( in_array( 'household_owner', $current_user->roles ) || 
						 in_array( 'household_member', $current_user->roles ) ) {
						$allcaps['edit_posts'] = true;
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
		$user_roles = array_intersect( $allowed_roles, $current_user->roles );
		
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
		?>
		<div class="wrap">
			<h1>My Networks</h1>
			<div class="card">
				<h2>Current Household</h2>
				<p>View and manage your household information.</p>
				<!-- Household info will be implemented next -->
				<p><em>Household management features coming soon...</em></p>
			</div>
			
			<div class="card">
				<h2>Network Management</h2>
				<p>Manage your extended family networks.</p>
				<!-- Network features will be implemented next -->
				<p><em>Network invitation and management features coming soon...</em></p>
			</div>
		</div>
		<?php
	}
}