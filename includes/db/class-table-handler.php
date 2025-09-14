<?php
/**
 * Table Handler
 *
 * @package MealPlannr
 */

namespace MealPlannr\DB;

/**
 * Table Handler
 */
class Table_Handler {
	/**
	 * Create the necessary database tables on plugin activation.
	 */
	public function create_tables() {
		$tables = array(
			new Recipes_Table(),
			new Ingredients_Table(),
			new Households_Table(),
			new Household_Members_Table(),
			new Networks_Table(),
			new Network_Households_Table(),
			new Recipe_Shares_Table(),
		);
		// Create all tables
		foreach ( $tables as $table ) {
			dbDelta( $table->create_table() );
		}
	}
}
