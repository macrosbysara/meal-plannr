<?php
/**
 * Class: Recipes Rest Router
 *
 * @package MealPlannr
 */

namespace MealPlannr\API;

use MealPlannr\DB\Ingredients_Table;
use MealPlannr\DB\Recipes_Table;
use MealPlannr\Services\Recipe_Access_Service;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class: Rest Router
 */
class Recipes_Rest_Router extends Base_Rest_Router {
	/**
	 * MealPlannr DB Handler
	 *
	 * @var Ingredients_Table $ingredients_db
	 */
	private Ingredients_Table $ingredients_db;

	/**
	 * Recipes DB Handler
	 *
	 * @var Recipes_Table $recipes_db
	 */
	private Recipes_Table $recipes_db;

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
		$this->recipes_db            = new Recipes_Table();
		$this->ingredients_db        = new Ingredients_Table();
		$this->recipe_access_service = new Recipe_Access_Service();
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register routes
	 */
	public function register_routes() {
		register_rest_route(
			$this->route_namespace,
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
			$this->route_namespace,
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

		// Recipe sharing routes
		// Set recipe sharing
		register_rest_route(
			$this->route_namespace,
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
			$this->route_namespace,
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
			$this->route_namespace,
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
		$this->ingredients_db->delete_ingredients( $recipe_id );
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
			$ingredient_rows[] = $this->ingredients_db->insert_ingredient( $data );
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

		$success = $this->recipes_db->update_macros( $recipe_id, $data );

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
