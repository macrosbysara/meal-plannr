<?php
/**
 * Plugin Name: Meal Plannr
 * Description: A plugin to help you plan your meals.
 * Version: 1.0.0
 * Requires at least: 6.7.0
 * Tested up to: 6.8.2
 * Requires PHP: 8.2
 * Author: K.J. Roelke
 *
 * @package MealPlannr
 */

use MealPlannr\Theme_Init;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

spl_autoload_register(
	function ( $class ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.classFound
		// Only autoload MealPlannr classes
		if ( strpos( $class, 'MealPlannr\\' ) !== 0 ) {
			return;
		}
		$base_dir       = __DIR__ . '/includes/';
		$relative_class = substr( $class, strlen( 'MealPlannr\\' ) );
		$relative_class = str_replace( '\\', DIRECTORY_SEPARATOR, $relative_class );
		// Find last directory separator for filename
		$last_sep = strrpos( $relative_class, DIRECTORY_SEPARATOR );
		if ( false !== $last_sep ) {
			$dir  = substr( $relative_class, 0, $last_sep + 1 );
			$file = substr( $relative_class, $last_sep + 1 );
			$path = $base_dir . strtolower( $dir ) . 'class-' . strtolower( str_replace( '_', '-', $file ) ) . '.php';
		} else {
			$path = $base_dir . 'class-' . strtolower( str_replace( '_', '-', $relative_class ) ) . '.php';
		}

		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}
);

$theme_init = new Theme_Init();

// Register activation and deactivation hooks
register_activation_hook( __FILE__, array( $theme_init, 'init' ) );
register_deactivation_hook( __FILE__, array( $theme_init, 'cleanup' ) );
