<?php
/**
 * Class: Rest Router
 *
 * @package MealPlannr
 */

namespace MealPlannr\API;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class: Rest Router
 */
class REST_Router {

	/**
	 * API Namespace
	 *
	 * @var string $namespace
	 */
	private string $namespace;

	/**
	 * API Version
	 *
	 * @var int $version
	 */
	private int $version;

	/**
	 * MealPlannr DB Handler
	 *
	 * @var Table_Handler $mp_db
	 */
	private Table_Handler $mp_db;

	/**
	 * Network Service
	 *
	 * @var Network_Service $network_service
	 */
	private Network_Service $network_service;

	/**
	 * Recipe Access Service
	 *
	 * @var Recipe_Access_Service $recipe_access_service
	 */
	private Recipe_Access_Service $recipe_access_service;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->namespace             = 'mealplannr';
		$this->version               = 1;
		$this->mp_db                 = new Table_Handler();
		$this->network_service       = new Network_Service();
		$this->recipe_access_service = new Recipe_Access_Service();
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register routes
	 */
	public function register_routes() {
		$namespace = "{$this->namespace}/v{$this->version}";
		register_rest_route(
			$namespace,
			'/ingredients/batch',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'batch_update_ingredients' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => array(
					'recipe_id'   => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'ingredients' => array(
						'required' => true,
						'type'     => 'array',
					),
				),
			)
		);

		// Macros routes
		register_rest_route(
			$namespace,
			'/recipes/(?P<id>\d+)/macros',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_macros' ),
					'permission_callback' => fn() => current_user_can( 'edit_posts' ),
					'args'                => array(
						'id'   => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
						'data' => array(
							'required'   => true,
							'type'       => 'object',
							'properties' => array(
								'protein'  => array(
									'type'              => 'number',
									'required'          => true,
									'sanitize_callback' => 'floatval',
								),
								'carbs'    => array(
									'type'              => 'number',
									'required'          => true,
									'sanitize_callback' => 'floatval',
								),
								'fat'      => array(
									'type'              => 'number',
									'required'          => true,
									'sanitize_callback' => 'floatval',
								),
								'calories' => array(
									'type'              => 'number',
									'required'          => true,
									'sanitize_callback' => 'floatval',
								),
							),
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_macros' ),
					'permission_callback' => fn() => current_user_can( 'edit_posts' ),
				),
			)
		);

		// Network management routes
		// Create network
		register_rest_route(
			$namespace,
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
			$namespace,
			'/networks/my',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_user_networks' ),
				'permission_callback' => fn() => is_user_logged_in(),
			)
		);

		// Send network invitation
		register_rest_route(
			$namespace,
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
			$namespace,
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
			$namespace,
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
			$namespace,
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
			$namespace,
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

		// Recipe sharing routes
		// Set recipe sharing
		register_rest_route(
			$namespace,
			'/recipes/(?P<recipe_id>\d+)/sharing',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'set_recipe_sharing' ),
				'permission_callback' => fn() => is_user_logged_in(),
				'args'                => array(
					'recipe_id'    => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'visibility'   => array(
						'required' => true,
						'type'     => 'string',
						'enum'     => array( 'private', 'household', 'network', 'public' ),
					),
					'household_id' => array(
						'required'          => false,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'network_id'   => array(
						'required'          => false,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Get recipe sharing status
		register_rest_route(
			$namespace,
			'/recipes/(?P<recipe_id>\d+)/sharing',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_recipe_sharing' ),
				'permission_callback' => fn() => is_user_logged_in(),
				'args'                => array(
					'recipe_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Get accessible recipes
		register_rest_route(
			$namespace,
			'/recipes/accessible',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_accessible_recipes' ),
				'permission_callback' => fn() => is_user_logged_in(),
				'args'                => array(
					'limit' => array(
						'required'          => false,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'default'           => 20,
					),
				),
			)
		);
	}

	/**
	 * Batch update/create ingredients
	 *
	 * @param WP_REST_Request $request the request
	 * @return array
	 */
	public function batch_update_ingredients( WP_REST_Request $request ): WP_REST_Response {
		$recipe_id   = $request['recipe_id'];
		$ingredients = $request['ingredients'];
		$this->mp_db->delete_ingredients( $recipe_id );
		if ( empty( $ingredients ) ) {
			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => 'All ingredients removed.',
				),
				201
			);
		}
		$ingredient_rows = array();
		foreach ( $ingredients as $ingredient ) {
			$data              = array(
				'recipe_id'       => $recipe_id,
				'name'            => sanitize_text_field( $ingredient['name'] ),
				'quantity_volume' => floatval( $ingredient['quantityVolume'] ?? null ),
				'unit_volume'     => sanitize_text_field( $ingredient['unitVolume'] ?? null ),
				'quantity_weight' => floatval( $ingredient['quantityWeight'] ?? null ),
				'unit_weight'     => sanitize_text_field( $ingredient['unitWeight'] ?? null ),
				'notes'           => sanitize_textarea_field( $ingredient['notes'] ?? null ),
			);
			$ingredient_rows[] = $this->mp_db->insert_ingredient( $data );
		}

		// Check for any errors during insertion
		if ( in_array( false, $ingredient_rows, true ) || count( $ingredient_rows ) !== count( $ingredients ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'One or more ingredients could not be inserted.',
				),
				500
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Added ' . count( $ingredients ) . ' ingredients to recipe ' . $recipe_id,
				'count'   => count( $ingredients ),
				'data'    => $ingredients,
			),
			201
		);
	}

	/**
	 * Update recipe macros
	 *
	 * @param WP_REST_Request $request the request
	 * @return array|WP_Error
	 */
	public function update_macros( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$recipe_id = (int) $request['id'];
		$data      = $request['data'];

		$success = $this->mp_db->update_macros( $recipe_id, $data );

		return $success
			? new WP_REST_Response(
				array(
					'success' => true,
					'data'    => $data,
					'message' => 'Macros updated successfully.',
				),
				201
			)
			: new WP_Error( 'db_error', 'Failed to update macros.', array( 'status' => 500 ) );
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
		$networks = $this->mp_db->get_user_networks( $user_id );

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

		$households = $this->mp_db->get_network_households( $network_id, $status );

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
		global $wpdb;
		$household_id = $wpdb->get_var(
			$wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT household_id FROM {$this->mp_db->household_members_table} WHERE user_id = %d AND role = 'owner' LIMIT 1",
				$user_id
			)
		);

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

		$invitations = $this->mp_db->get_household_invitations( $household_id, $status );

		return new WP_REST_Response(
			array(
				'success'     => true,
				'invitations' => $invitations,
			),
			200
		);
	}

	/**
	 * Set recipe sharing settings
	 *
	 * @param WP_REST_Request $request The request
	 * @return WP_REST_Response|WP_Error
	 */
	public function set_recipe_sharing( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$recipe_id    = $request['recipe_id'];
		$visibility   = $request['visibility'];
		$household_id = $request->get_param( 'household_id' );
		$network_id   = $request->get_param( 'network_id' );
		$user_id      = get_current_user_id();

		$success = $this->recipe_access_service->set_recipe_sharing(
			$recipe_id,
			$visibility,
			$user_id,
			$household_id,
			$network_id
		);

		if ( $success ) {
			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => 'Recipe sharing settings updated',
				),
				200
			);
		} else {
			return new WP_Error( 'sharing_error', 'Failed to update recipe sharing settings', array( 'status' => 400 ) );
		}
	}

	/**
	 * Get recipe sharing settings
	 *
	 * @param WP_REST_Request $request The request
	 * @return WP_REST_Response
	 */
	public function get_recipe_sharing( WP_REST_Request $request ): WP_REST_Response {
		$recipe_id = $request['recipe_id'];
		$sharing   = $this->recipe_access_service->get_recipe_sharing_status( $recipe_id );

		return new WP_REST_Response(
			array(
				'success' => true,
				'sharing' => $sharing,
			),
			200
		);
	}

	/**
	 * Get accessible recipes for user
	 *
	 * @param WP_REST_Request $request The request
	 * @return WP_REST_Response
	 */
	public function get_accessible_recipes( WP_REST_Request $request ): WP_REST_Response {
		$user_id = get_current_user_id();
		$limit   = $request->get_param( 'limit' );

		$args = array();
		if ( $limit ) {
			$args['limit'] = $limit;
		}

		$recipes = $this->recipe_access_service->get_accessible_recipes( $user_id, $args );

		return new WP_REST_Response(
			array(
				'success' => true,
				'recipes' => $recipes,
			),
			200
		);
	}
}
