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
	 * Constructor
	 */
	public function __construct() {
		$this->namespace = 'mealplannr';
		$this->version   = 1;
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
						'required' => true,
						'type'     => 'integer',
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
		global $wpdb;
		$table       = "{$wpdb->prefix}mp_ingredients";
		$recipe_id   = intval( $request['recipe_id'] );
		$ingredients = $request['ingredients'];

		// Wipe old ingredients for the recipe
		$wpdb->delete( $table, array( 'recipe_id' => $recipe_id ) );

		// Insert fresh ones
		foreach ( $ingredients as $ingredient ) {
			$wpdb->insert(
				$table,
				array(
					'recipe_id'       => $recipe_id,
					'name'            => sanitize_text_field( $ingredient['name'] ),
					'quantity_volume' => floatval( $ingredient['quantityVolume'] ?? 0 ),
					'unit_volume'     => sanitize_text_field( $ingredient['unitVolume'] ?? '' ),
					'quantity_weight' => floatval( $ingredient['quantityWeight'] ?? 0 ),
					'unit_weight'     => sanitize_text_field( $ingredient['unitWeight'] ?? '' ),
				)
			);
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'count'   => count( $ingredients ),
			)
		);
	}
}
