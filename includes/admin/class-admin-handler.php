<?php
/**
 * Admin Handler
 *
 * @package MealPlannr
 */

// phpcs:disable WordPress.Security.NonceVerification.Recommended

namespace MealPlannr\Admin;

/**
 * Class Admin_Handler
 *
 * Handles WordPress admin functionality including roles, capabilities, and admin interface.
 */
class Admin_Handler {
	/**
	 * Role handler instance.
	 *
	 * @var Role_Handler $role_handler
	 */
	private Role_Handler $role_handler;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->role_handler = new Role_Handler();
		$networks_page      = new Network_Management_Page();
		// Add network management page
		add_action( 'admin_menu', array( $networks_page, 'add_network_management_page' ) );
	}

	/**
	 * Register custom roles and capabilities.
	 * Called on plugin activation.
	 */
	public function register_roles(): void {
		$this->role_handler->register_roles();
	}

	/**
	 * Clean up custom roles.
	 * Called on plugin deactivation.
	 */
	public function remove_roles(): void {
		$this->role_handler->remove_roles();
	}
}

// phpcs:enable WordPress.Security.NonceVerification.Recommended
