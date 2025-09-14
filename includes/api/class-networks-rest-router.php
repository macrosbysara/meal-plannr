<?php
/**
 * Networks Rest Router
 *
 * @package MealPlannr
 * @subpackage API
 */

namespace MealPlannr\API;

use MealPlannr\DB\Households_Table;
use MealPlannr\DB\Network_Households_Table;
use MealPlannr\DB\Networks_Table;
use MealPlannr\Services\Network_Membership_Services;
use MealPlannr\Services\Network_Service;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class: Rest Router
 */
class Networks_Rest_Router extends Base_Rest_Router {
	/**
	 * MealPlannr DB Handler
	 *
	 * @var Networks_Table $networks_db
	 */
	private Networks_Table $networks_db;

	/**
	 * Network Service
	 *
	 * @var Network_Service $network_service
	 */
	private Network_Service $network_service;

	/**
	 * Network Membership Service
	 *
	 * @var Network_Membership_Services $membership_service
	 */
	private Network_Membership_Services $membership_service;

	/**
	 * Household DB Handler
	 *
	 * @var Households_Table $household_db
	 */
	private Households_Table $household_db;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->networks_db        = new Networks_Table();
		$this->network_service    = new Network_Service();
		$this->household_db       = new Households_Table();
		$this->membership_service = new Network_Membership_Services( $this->household_db, $this->networks_db, new Network_Households_Table() );
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register routes
	 */
	public function register_routes() {
		// Create network
		register_rest_route(
			$this->route_namespace,
			'/networks',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_network' ),
				'permission_callback' => fn() => is_user_logged_in(),
				'args'                => array(
					'name' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Get user networks
		register_rest_route(
			$this->route_namespace,
			'/networks/my',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_user_networks' ),
				'permission_callback' => fn() => is_user_logged_in(),
			)
		);

		// Send network invitation
		register_rest_route(
			$this->route_namespace,
			'/networks/(?P<network_id>\d+)/invite',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'invite_household' ),
				'permission_callback' => fn() => is_user_logged_in(),
				'args'                => array(
					'network_id'   => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'household_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Accept/reject invitation
		register_rest_route(
			$this->route_namespace,
			'/invitations/(?P<invitation_id>\d+)/(?P<action>accept|reject)',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_invitation' ),
				'permission_callback' => fn() => is_user_logged_in(),
				'args'                => array(
					'invitation_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'action'        => array(
						'required' => true,
						'type'     => 'string',
						'enum'     => array( 'accept', 'reject' ),
					),
				),
			)
		);

		// Remove household from network
		register_rest_route(
			$this->route_namespace,
			'/networks/(?P<network_id>\d+)/households/(?P<household_id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'remove_household' ),
				'permission_callback' => fn() => is_user_logged_in(),
				'args'                => array(
					'network_id'   => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'household_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Get network households
		register_rest_route(
			$this->route_namespace,
			'/networks/(?P<network_id>\d+)/households',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_network_households' ),
				'permission_callback' => fn() => is_user_logged_in(),
				'args'                => array(
					'network_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'status'     => array(
						'required' => false,
						'type'     => 'string',
						'enum'     => array( 'pending', 'accepted', 'rejected' ),
					),
				),
			)
		);

		// Get household invitations
		register_rest_route(
			$this->route_namespace,
			'/households/invitations',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_household_invitations' ),
				'permission_callback' => fn() => is_user_logged_in(),
				'args'                => array(
					'status' => array(
						'required' => false,
						'type'     => 'string',
						'enum'     => array( 'pending', 'accepted', 'rejected' ),
						'default'  => 'pending',
					),
				),
			)
		);
	}

	/**
	 * Create network
	 *
	 * @param WP_REST_Request $request The request
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_network( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$name    = $request['name'];
		$user_id = get_current_user_id();

		$result = $this->network_service->create_network( $name, $user_id );

		if ( $result['success'] ) {
			return new WP_REST_Response( $result, 201 );
		} else {
			return new WP_Error( 'network_error', $result['error'], array( 'status' => 400 ) );
		}
	}

	/**
	 * Get user networks
	 *
	 * @return WP_REST_Response
	 */
	public function get_user_networks(): WP_REST_Response {
		$user_id  = get_current_user_id();
		$networks = $this->networks_db->get_user_networks( $user_id );

		return new WP_REST_Response(
			array(
				'success'  => true,
				'networks' => $networks,
			),
			200
		);
	}

	/**
	 * Invite household to network
	 *
	 * @param WP_REST_Request $request The request
	 * @return WP_REST_Response|WP_Error
	 */
	public function invite_household( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$network_id   = $request['network_id'];
		$household_id = $request['household_id'];
		$user_id      = get_current_user_id();

		$result = $this->network_service->invite_household( $network_id, $household_id, $user_id );

		if ( $result['success'] ) {
			return new WP_REST_Response( $result, 201 );
		} else {
			return new WP_Error( 'invitation_error', $result['error'], array( 'status' => 400 ) );
		}
	}

	/**
	 * Handle invitation (accept/reject)
	 *
	 * @param WP_REST_Request $request The request
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_invitation( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$invitation_id = $request['invitation_id'];
		$action        = $request['action'];
		$user_id       = get_current_user_id();

		if ( 'accept' === $action ) {
			$result = $this->network_service->accept_invitation( $invitation_id, $user_id );
		} else {
			$result = $this->network_service->reject_invitation( $invitation_id, $user_id );
		}

		if ( $result['success'] ) {
			return new WP_REST_Response( $result, 200 );
		} else {
			return new WP_Error( 'invitation_error', $result['error'], array( 'status' => 400 ) );
		}
	}

	/**
	 * Remove household from network
	 *
	 * @param WP_REST_Request $request The request
	 * @return WP_REST_Response|WP_Error
	 */
	public function remove_household( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$network_id   = $request['network_id'];
		$household_id = $request['household_id'];
		$user_id      = get_current_user_id();

		$result = $this->network_service->remove_household( $network_id, $household_id, $user_id );

		if ( $result['success'] ) {
			return new WP_REST_Response( $result, 200 );
		} else {
			return new WP_Error( 'removal_error', $result['error'], array( 'status' => 400 ) );
		}
	}

	/**
	 * Get network households
	 *
	 * @param WP_REST_Request $request The request
	 * @return WP_REST_Response
	 */
	public function get_network_households( WP_REST_Request $request ): WP_REST_Response {
		$network_id = $request['network_id'];
		$status     = $request->get_param( 'status' );

		$households = $this->membership_service->get_network_households( $network_id, $status );

		return new WP_REST_Response(
			array(
				'success'    => true,
				'households' => $households,
			),
			200
		);
	}

	/**
	 * Get household invitations
	 *
	 * @param WP_REST_Request $request The request
	 * @return WP_REST_Response
	 */
	public function get_household_invitations( WP_REST_Request $request ): WP_REST_Response {
		$user_id = get_current_user_id();
		$status  = $request->get_param( 'status' );

		// Get user's household ID
		$household_id = $this->household_db->get_user_household( $user_id );
		if ( ! $household_id ) {
			return new WP_REST_Response(
				array(
					'success'     => true,
					'invitations' => array(),
					'message'     => 'User is not a household owner',
				),
				200
			);
		}

		$invitations = $this->membership_service->get_household_invitations( $household_id, $status );

		return new WP_REST_Response(
			array(
				'success'     => true,
				'invitations' => $invitations,
			),
			200
		);
	}
}
