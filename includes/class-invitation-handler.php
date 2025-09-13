<?php

/**
 * Invitation Handler
 *
 * @package MealPlannr
 */

namespace MealPlannr;

/**
 * Invitation Handler
 *
 * Handles public invitation links from emails
 */
class Invitation_Handler {

	/**
	 * Network Service
	 *
	 * @var Network_Service $network_service
	 */
	private Network_Service $network_service;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->network_service = new Network_Service();
		add_action( 'init', array( $this, 'handle_invitation_actions' ) );
	}

	/**
	 * Handle invitation actions from email links
	 */
	public function handle_invitation_actions(): void {
		if ( ! isset( $_GET['action'] ) || ! isset( $_GET['invitation_id'] ) || ! isset( $_GET['nonce'] ) ) {
			return;
		}

		$action        = sanitize_text_field( $_GET['action'] );
		$invitation_id = absint( $_GET['invitation_id'] );
		$nonce         = sanitize_text_field( $_GET['nonce'] );

		if ( ! in_array( $action, array( 'accept_network_invitation', 'reject_network_invitation' ), true ) ) {
			return;
		}

		// Verify nonce
		$nonce_action = str_replace( '_network_invitation', '_invitation_', $action ) . $invitation_id;
		if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
			wp_die( 'Invalid invitation link.' );
		}

		// Check if user is logged in
		if ( ! is_user_logged_in() ) {
			// Redirect to login with return URL
			$return_url = add_query_arg(
				array(
					'action'        => $action,
					'invitation_id' => $invitation_id,
					'nonce'         => $nonce,
				),
				home_url()
			);
			wp_redirect( wp_login_url( $return_url ) );
			exit;
		}

		$user_id = get_current_user_id();

		if ( $action === 'accept_network_invitation' ) {
			$result = $this->network_service->accept_invitation( $invitation_id, $user_id );
		} else {
			$result = $this->network_service->reject_invitation( $invitation_id, $user_id );
		}

		// Display result message
		if ( $result['success'] ) {
			$this->show_success_message( $result['message'] );
		} else {
			$this->show_error_message( $result['error'] );
		}
	}

	/**
	 * Show success message
	 *
	 * @param string $message Success message
	 */
	private function show_success_message( string $message ): void {
		add_action(
			'wp_head',
			function () use ( $message ) {
				echo '<style>
					.invitation-message { 
						background: #d4edda; 
						border: 1px solid #c3e6cb; 
						color: #155724; 
						padding: 15px; 
						margin: 20px; 
						border-radius: 4px; 
						text-align: center; 
						font-size: 18px;
					}
				</style>';
			}
		);

		add_action(
			'wp_footer',
			function () use ( $message ) {
				echo '<script>
					document.addEventListener("DOMContentLoaded", function() {
						var body = document.querySelector("body");
						var messageDiv = document.createElement("div");
						messageDiv.className = "invitation-message";
						messageDiv.innerHTML = "' . esc_js( $message ) . '";
						body.insertBefore(messageDiv, body.firstChild);
					});
				</script>';
			}
		);
	}

	/**
	 * Show error message
	 *
	 * @param string $message Error message
	 */
	private function show_error_message( string $message ): void {
		add_action(
			'wp_head',
			function () use ( $message ) {
				echo '<style>
					.invitation-message { 
						background: #f8d7da; 
						border: 1px solid #f5c6cb; 
						color: #721c24; 
						padding: 15px; 
						margin: 20px; 
						border-radius: 4px; 
						text-align: center; 
						font-size: 18px;
					}
				</style>';
			}
		);

		add_action(
			'wp_footer',
			function () use ( $message ) {
				echo '<script>
					document.addEventListener("DOMContentLoaded", function() {
						var body = document.querySelector("body");
						var messageDiv = document.createElement("div");
						messageDiv.className = "invitation-message";
						messageDiv.innerHTML = "' . esc_js( $message ) . '";
						body.insertBefore(messageDiv, body.firstChild);
					});
				</script>';
			}
		);
	}
}
