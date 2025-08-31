<?php
/**
 * Network Management Tests
 *
 * Basic test file to validate network management functionality
 * Run this by accessing it via browser or CLI after setting up WordPress
 *
 * @package MealPlannr
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	// For CLI testing - set up basic WordPress environment
	require_once dirname( __DIR__ ) . '/meal-plannr.php';
}

use MealPlannr\Network_Service;
use MealPlannr\Recipe_Access_Service;
use MealPlannr\Table_Handler;

/**
 * Simple test runner for network management features
 */
class Network_Management_Tests {

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
	 * Table Handler
	 *
	 * @var Table_Handler $table_handler
	 */
	private Table_Handler $table_handler;

	/**
	 * Test results
	 *
	 * @var array $results
	 */
	private array $results = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->network_service       = new Network_Service();
		$this->recipe_access_service = new Recipe_Access_Service();
		$this->table_handler         = new Table_Handler();
	}

	/**
	 * Run all tests
	 *
	 * @return array Test results
	 */
	public function run_tests(): array {
		echo "<h1>Network Management Tests</h1>\n";

		$this->test_table_creation();
		$this->test_network_service_methods();
		$this->test_recipe_access_service_methods();
		$this->test_rest_api_endpoints();

		return $this->results;
	}

	/**
	 * Test table creation
	 */
	private function test_table_creation(): void {
		echo "<h2>Testing Table Creation</h2>\n";

		global $wpdb;

		// Test if networks table exists
		$networks_table = $this->table_handler->networks_table;
		$table_exists   = $wpdb->get_var( "SHOW TABLES LIKE '{$networks_table}'" ) === $networks_table;
		$this->assert_true( $table_exists, 'Networks table should exist' );

		// Test if network_households table exists with correct schema
		$network_households_table = $this->table_handler->network_households_table;
		$table_exists             = $wpdb->get_var( "SHOW TABLES LIKE '{$network_households_table}'" ) === $network_households_table;
		$this->assert_true( $table_exists, 'Network households table should exist' );

		// Check if status column exists
		$columns = $wpdb->get_results( "SHOW COLUMNS FROM {$network_households_table}" );
		$has_status = false;
		foreach ( $columns as $column ) {
			if ( $column->Field === 'status' ) {
				$has_status = true;
				break;
			}
		}
		$this->assert_true( $has_status, 'Network households table should have status column' );

		echo "<p>✓ Table creation tests completed</p>\n";
	}

	/**
	 * Test network service methods
	 */
	private function test_network_service_methods(): void {
		echo "<h2>Testing Network Service Methods</h2>\n";

		// Test network size limit constant
		$max_households = Network_Service::MAX_HOUSEHOLDS_PER_NETWORK;
		$this->assert_equals( 10, $max_households, 'Maximum households per network should be 10' );

		echo "<p>✓ Network service tests completed</p>\n";
	}

	/**
	 * Test recipe access service methods
	 */
	private function test_recipe_access_service_methods(): void {
		echo "<h2>Testing Recipe Access Service Methods</h2>\n";

		// Test recipe sharing status for non-existent recipe
		$sharing_status = $this->recipe_access_service->get_recipe_sharing_status( 99999 );
		$expected       = array(
			'visibility'   => 'private',
			'household_id' => null,
			'network_id'   => null,
		);
		$this->assert_equals( $expected, $sharing_status, 'Non-existent recipe should default to private' );

		echo "<p>✓ Recipe access service tests completed</p>\n";
	}

	/**
	 * Test REST API endpoint structure
	 */
	private function test_rest_api_endpoints(): void {
		echo "<h2>Testing REST API Endpoint Structure</h2>\n";

		// Test if REST routes are available
		$routes = rest_get_server()->get_routes();

		$expected_routes = array(
			'/mealplannr/v1/networks',
			'/mealplannr/v1/networks/my',
			'/mealplannr/v1/recipes/accessible',
		);

		foreach ( $expected_routes as $route ) {
			$route_exists = isset( $routes[ $route ] );
			$this->assert_true( $route_exists, "REST route {$route} should be registered" );
		}

		echo "<p>✓ REST API endpoint tests completed</p>\n";
	}

	/**
	 * Assert true
	 *
	 * @param bool   $condition Condition to test
	 * @param string $message Test message
	 */
	private function assert_true( bool $condition, string $message ): void {
		if ( $condition ) {
			echo "<p style='color: green;'>✓ PASS: {$message}</p>\n";
			$this->results[] = array( 'status' => 'PASS', 'message' => $message );
		} else {
			echo "<p style='color: red;'>✗ FAIL: {$message}</p>\n";
			$this->results[] = array( 'status' => 'FAIL', 'message' => $message );
		}
	}

	/**
	 * Assert equals
	 *
	 * @param mixed  $expected Expected value
	 * @param mixed  $actual Actual value
	 * @param string $message Test message
	 */
	private function assert_equals( $expected, $actual, string $message ): void {
		if ( $expected === $actual ) {
			echo "<p style='color: green;'>✓ PASS: {$message}</p>\n";
			$this->results[] = array( 'status' => 'PASS', 'message' => $message );
		} else {
			echo "<p style='color: red;'>✗ FAIL: {$message} - Expected: " . print_r( $expected, true ) . ', Got: ' . print_r( $actual, true ) . "</p>\n";
			$this->results[] = array( 'status' => 'FAIL', 'message' => $message );
		}
	}
}

// Run tests if accessed directly
if ( defined( 'ABSPATH' ) || php_sapi_name() === 'cli' ) {
	$tests   = new Network_Management_Tests();
	$results = $tests->run_tests();

	// Summary
	$pass_count = count( array_filter( $results, fn( $r ) => $r['status'] === 'PASS' ) );
	$fail_count = count( array_filter( $results, fn( $r ) => $r['status'] === 'FAIL' ) );

	echo "<h2>Test Summary</h2>\n";
	echo "<p>Passed: {$pass_count}</p>\n";
	echo "<p>Failed: {$fail_count}</p>\n";

	if ( $fail_count === 0 ) {
		echo "<p style='color: green; font-weight: bold;'>All tests passed! ✓</p>\n";
	} else {
		echo "<p style='color: red; font-weight: bold;'>Some tests failed. ✗</p>\n";
	}
}