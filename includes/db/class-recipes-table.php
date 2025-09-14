<?php
/**
 * Recipe Table
 *
 * @package MealPlannr
 * @subpackage DB
 */

namespace MealPlannr\DB;

use MealPlannr\DB\Base_Table;

/**
 * Class Recipe_Table
 *
 * @package MealPlannr\DB
 */
class Recipes_Table extends Base_Table {
	/**
	 * Recipes_Table constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->table_name = $this->table_prefix . 'recipes';
	}

	/**
	 * Create the recipes table SQL
	 *
	 * @return string
	 */
	public function create_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$recipes_sql     = "CREATE TABLE $this->table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			post_id bigint(20) unsigned NOT NULL,
			protein decimal(8,2) DEFAULT 0,
			carbs decimal(8,2) DEFAULT 0,
			fat decimal(8,2) DEFAULT 0,
			calories decimal(8,2) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY post_id (post_id)
		) $charset_collate;";
		return $recipes_sql;
	}

	/**
	 * Update the macro nutrient values for a recipe.
	 *
	 * @param int   $recipe_id The recipe ID.
	 * @param array $data The macro nutrient data.
	 * @return bool True on success, false on failure.
	 */
	public function update_macros( int $recipe_id, array $data ): bool {
		global $wpdb;

		// Check if row exists for this post_id
		$exists = $wpdb->get_var(
			$wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT id FROM `{$wpdb->prefix}meal_plannr_recipes` WHERE post_id = %d",
				$recipe_id
			)
		);

		if ( $exists ) {
			$updated = $wpdb->update(
				$this->table_name,
				$data,
				array( 'post_id' => $recipe_id ),
				array( '%f', '%f', '%f', '%f' ),
				array( '%d' )
			);
			return (bool) $updated;
		} else {
			// Add post_id to data for insert
			$data['post_id'] = $recipe_id;
			$inserted        = $wpdb->insert(
				$this->table_name,
				$data,
				array( '%d', '%f', '%f', '%f', '%f' )
			);
			return (bool) $inserted;
		}
	}

	/**
	 * Delete Macros
	 *
	 * @param int $recipe_id the recipe to delete macros from
	 */
	public function clear_macros( int $recipe_id ): bool {
		global $wpdb;

		$updated = $wpdb->update(
			$this->table_name,
			array(
				'protein'  => null,
				'carbs'    => null,
				'fat'      => null,
				'calories' => null,
			),
			array( 'post_id' => $recipe_id ),
			array( '%f', '%f', '%f', '%f' ),
			array( '%d' )
		);
		return false !== $updated;
	}
}
