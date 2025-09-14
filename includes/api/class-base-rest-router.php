<?php
/**
 * Class: Base Rest Router
 *
 * @package MealPlannr
 * @subpackage API
 */

namespace MealPlannr\API;

/**
 * Class Base_Rest_Router
 */
abstract class Base_Rest_Router {

	/**
	 * API Namespace
	 *
	 * @var string $namespace
	 */
	protected string $namespace;

	/**
	 * API Version
	 *
	 * @var int $version
	 */
	protected int $version;

	/**
	 * Route Namespace
	 *
	 * @var string $route_namespace
	 */
	protected string $route_namespace;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->namespace       = 'mealplannr';
		$this->version         = 1;
		$this->route_namespace = $this->namespace . '/v' . $this->version;
	}

	/**
	 * Register routes
	 */
	abstract public function register_routes();
}
