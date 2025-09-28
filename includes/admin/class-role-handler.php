<?php
/**
 * Role Handler
 *
 * @package MealPlannr
 */

namespace MealPlannr\Admin;

use WP_User;

/**
 * Class Role_Handler
 * Handles custom user roles, capabilities, and admin access restrictions.
 */
class Role_Handler {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'restrict_admin_access' ) );
		add_action( 'admin_menu', array( $this, 'customize_admin_menu' ), 999 );
		add_filter( 'user_has_cap', array( $this, 'filter_user_capabilities' ), 10, 3 );
	}

	/**
	 * Register custom roles and capabilities.
	 * Called on plugin activation.
	 */
	public function register_roles(): void {
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
		if ( in_array( 'administrator', $current_user->roles, true ) ) {
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
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if (
			'profile.php' === $pagenow ||
			( isset( $_GET['page'] ) && 'my-networks' === $_GET['page'] )
		) {
			return;
		}

		// Allow recipe post type pages
		if ( isset( $_GET['post_type'] ) && 'recipe' === $_GET['post_type'] ) {
			return;
		}

		// Allow editing individual recipes
		if ( 'post.php' === $pagenow || 'post-new.php' === $pagenow ) {
			$post_type = 'recipe';
			if ( isset( $_GET['post'] ) ) {
				$post = get_post( $_GET['post'] );
				if ( $post ) {
					$post_type = $post->post_type;
				}
			} elseif ( isset( $_GET['post_type'] ) ) {
				$post_type = $_GET['post_type'];
			}

			if ( 'recipe' === $post_type ) {
				return;
			}
		}

		// Allow admin-ajax.php
		if ( 'admin-ajax.php' === $pagenow ) {
			return;
		}

		// Redirect to recipes page for unauthorized access
		if ( 'edit.php' !== $pagenow || ! isset( $_GET['post_type'] ) || 'recipe' !== $_GET['post_type'] ) {
			wp_safe_redirect( admin_url( 'edit.php?post_type=recipe' ) );
			exit;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Customize admin menu for non-admin users.
	 */
	public function customize_admin_menu() {
		$current_user = wp_get_current_user();

		// Don't modify menu for administrators
		if ( in_array( 'administrator', $current_user->roles, true ) ) {
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
		if ( in_array( 'administrator', $current_user->roles, true ) ) {
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
		if ( in_array( 'administrator', $current_user->roles, true ) ) {
			return $allcaps;
		}

		// Handle recipe-related capabilities
		if ( in_array( 'edit_posts', $caps, true ) || in_array( 'delete_posts', $caps, true ) ) {
			// Check if user is dealing with recipe post type
			if ( isset( $args[2] ) ) {
				$post = get_post( $args[2] );
				if ( $post && 'recipe' === $post->post_type ) {
					// Allow recipe operations for household roles
					if (
						in_array( 'household_owner', $current_user->roles, true ) ||
						in_array( 'household_member', $current_user->roles, true )
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
	 * Set user to household owner role if not already assigned.
	 *
	 * @param int $user_id User ID.
	 * @return bool|mixed True if role was added, false if already has role or on failure.
	 */
	public function set_user_to_household_owner( int $user_id ): mixed {
		$user = new WP_User( $user_id );
		if ( ! in_array( 'household_owner', $user->roles, true ) ) {
			return $user->add_role( 'household_owner' );
		}
		return false;
	}
}
