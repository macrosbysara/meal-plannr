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
if (! defined('ABSPATH')) {
    exit;
}

$plugin_dir = plugin_dir_path(__FILE__);
require_once $plugin_dir . 'includes/class-theme-init.php';

$theme_init = new Theme_Init();

// Register activation and deactivation hooks
register_activation_hook(__FILE__, array( $theme_init, 'init' ));
register_deactivation_hook(__FILE__, array( $theme_init, 'cleanup' ));
