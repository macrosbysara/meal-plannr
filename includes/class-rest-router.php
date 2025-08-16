<?php
/**
 * Class: Rest Router
 *
 * @package MealPlannr
 */

namespace MealPlannr;

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
	 * Constructor
	 */
	public function __construct() {
		$this->namespace = 'mealplannr';
		$this->version   = 1;
		$this->mp_db     = new Table_Handler();
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register routes
	 */
	public function register_routes() {
		register_rest_route(
			"{$this->namespace}/v{$this->version}",
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
				'notes'     => sanitize_textarea_field( $ingredient['notes'] ?? null ),
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
}
