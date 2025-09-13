<?php

/**
 * Recipe Access Service
 *
 * @package MealPlannr
 */

namespace MealPlannr;

/**
 * Recipe Access Service
 *
 * Handles access control for recipe sharing based on network membership
 */
class Recipe_Access_Service {

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
		$this->mp_db = new Table_Handler();
	}

	/**
	 * Check if user can access recipe
	 *
	 * @param int $recipe_id Recipe ID (post ID)
	 * @param int $user_id User ID
	 * @return bool True if user can access recipe
	 */
	public function can_user_access_recipe( int $recipe_id, int $user_id ): bool {
		// Get recipe sharing settings
		$recipe_share = $this->get_recipe_share_settings( $recipe_id );
		if ( ! $recipe_share ) {
			// No sharing settings = private access for recipe author only
			$post = get_post( $recipe_id );
			return $post && (int) $post->post_author === $user_id;
		}

		switch ( $recipe_share->visibility ) {
			case 'public':
				return true;

			case 'private':
				$post = get_post( $recipe_id );
				return $post && (int) $post->post_author === $user_id;

			case 'household':
				return $this->can_user_access_household_recipe( $recipe_share->household_id, $user_id );

			case 'network':
				return $this->can_user_access_network_recipe( $recipe_share->network_id, $user_id );

			default:
				return false;
		}
	}

	/**
	 * Check if user can access household recipe
	 *
	 * @param int $household_id Household ID
	 * @param int $user_id User ID
	 * @return bool True if user is in household
	 */
	private function can_user_access_household_recipe( int $household_id, int $user_id ): bool {
		global $wpdb;
		$count = $wpdb->get_var(
			$wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COUNT(*) FROM {$this->mp_db->household_members_table} WHERE household_id = %d AND user_id = %d",
				$household_id,
				$user_id
			)
		);
		return (int) $count > 0;
	}

	/**
	 * Check if user can access network recipe
	 *
	 * @param int $network_id Network ID
	 * @param int $user_id User ID
	 * @return bool True if user's household is in network with accepted status
	 */
	private function can_user_access_network_recipe( int $network_id, int $user_id ): bool {
		// Get user's household
		$user_household_id = $this->get_user_household( $user_id );
		if ( ! $user_household_id ) {
			return false;
		}

		// Check if household is in network with accepted status
		global $wpdb;
		$count = $wpdb->get_var(
			$wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COUNT(*) FROM {$this->mp_db->network_households_table} WHERE network_id = %d AND household_id = %d AND status = 'accepted'",
				$network_id,
				$user_household_id
			)
		);
		return (int) $count > 0;
	}

	/**
	 * Get user's primary household
	 *
	 * @param int $user_id User ID
	 * @return int|null Household ID or null if not found
	 */
	private function get_user_household( int $user_id ): ?int {
		global $wpdb;
		$household_id = $wpdb->get_var(
			$wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT household_id FROM {$this->mp_db->household_members_table} WHERE user_id = %d LIMIT 1",
				$user_id
			)
		);
		return $household_id ? (int) $household_id : null;
	}

	/**
	 * Get recipe share settings
	 *
	 * @param int $recipe_id Recipe ID (post ID)
	 * @return object|null Recipe share settings or null if not found
	 */
	private function get_recipe_share_settings( int $recipe_id ): ?object {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM {$this->mp_db->recipe_shares_table} WHERE recipe_id = %d LIMIT 1",
				$recipe_id
			)
		);
	}

	/**
	 * Set recipe sharing settings
	 *
	 * @param int      $recipe_id Recipe ID (post ID)
	 * @param string   $visibility Visibility level (private, household, network, public)
	 * @param int      $user_id User ID setting the sharing
	 * @param int|null $household_id Household ID (for household visibility)
	 * @param int|null $network_id Network ID (for network visibility)
	 * @return bool Success
	 */
	public function set_recipe_sharing( int $recipe_id, string $visibility, int $user_id, ?int $household_id = null, ?int $network_id = null ): bool {
		// Check if user can edit this recipe
		$post = get_post( $recipe_id );
		if ( ! $post || (int) $post->post_author !== $user_id ) {
			return false;
		}

		// Validate visibility options
		$valid_visibility = array( 'private', 'household', 'network', 'public' );
		if ( ! in_array( $visibility, $valid_visibility, true ) ) {
			return false;
		}

		// Validate required parameters
		if ( $visibility === 'household' && ! $household_id ) {
			return false;
		}
		if ( $visibility === 'network' && ! $network_id ) {
			return false;
		}

		// For household visibility, ensure user is in the household
		if ( $visibility === 'household' && ! $this->can_user_access_household_recipe( $household_id, $user_id ) ) {
			return false;
		}

		// For network visibility, ensure user's household is in the network
		if ( $visibility === 'network' && ! $this->can_user_access_network_recipe( $network_id, $user_id ) ) {
			return false;
		}

		global $wpdb;

		// Check if sharing settings already exist
		$existing = $this->get_recipe_share_settings( $recipe_id );

		$data = array(
			'recipe_id'    => $recipe_id,
			'visibility'   => $visibility,
			'household_id' => $household_id,
			'network_id'   => $network_id,
		);

		if ( $existing ) {
			// Update existing
			$result = $wpdb->update(
				$this->mp_db->recipe_shares_table,
				$data,
				array( 'recipe_id' => $recipe_id ),
				array( '%d', '%s', '%d', '%d' ),
				array( '%d' )
			);
		} else {
			// Insert new
			$result = $wpdb->insert(
				$this->mp_db->recipe_shares_table,
				$data,
				array( '%d', '%s', '%d', '%d' )
			);
		}

		return false !== $result;
	}

	/**
	 * Get accessible recipes for user
	 *
	 * @param int   $user_id User ID
	 * @param array $args Optional query arguments
	 * @return array List of accessible recipe IDs
	 */
	public function get_accessible_recipes( int $user_id, array $args = array() ): array {
		global $wpdb;

		$user_household_id = $this->get_user_household( $user_id );

		$sql = "SELECT DISTINCT p.ID, p.post_title 
				FROM {$wpdb->posts} p 
				LEFT JOIN {$this->mp_db->recipe_shares_table} rs ON p.ID = rs.recipe_id
				LEFT JOIN {$this->mp_db->network_households_table} nh ON rs.network_id = nh.network_id AND nh.household_id = %d AND nh.status = 'accepted'
				WHERE p.post_type = 'recipe' 
				AND p.post_status = 'publish'
				AND (
					p.post_author = %d OR
					rs.visibility = 'public' OR
					(rs.visibility = 'household' AND rs.household_id = %d) OR
					(rs.visibility = 'network' AND nh.id IS NOT NULL) OR
					rs.id IS NULL
				)
				ORDER BY p.post_date DESC";

		$params = array(
			$user_household_id ?: 0,
			$user_id,
			$user_household_id ?: 0,
		);

		if ( isset( $args['limit'] ) ) {
			$sql     .= ' LIMIT %d';
			$params[] = (int) $args['limit'];
		}

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Filter recipe query based on user access
	 *
	 * Can be used as a filter on WP_Query or get_posts
	 *
	 * @param string $where WHERE clause
	 * @param object $query WP_Query object
	 * @return string Modified WHERE clause
	 */
	public function filter_recipe_query( string $where, object $query ): string {
		// Only apply to recipe queries for logged-in users
		if ( ! is_user_logged_in()
			|| ! isset( $query->query_vars['post_type'] )
			|| $query->query_vars['post_type'] !== 'recipe'
		) {
			return $where;
		}

		$user_id           = get_current_user_id();
		$user_household_id = $this->get_user_household( $user_id );

		global $wpdb;

		$access_clause = "
			AND (
				{$wpdb->posts}.post_author = {$user_id}
				OR EXISTS (
					SELECT 1 FROM {$this->mp_db->recipe_shares_table} rs 
					WHERE rs.recipe_id = {$wpdb->posts}.ID 
					AND (
						rs.visibility = 'public'
						OR (rs.visibility = 'household' AND rs.household_id = " . ( $user_household_id ?: 0 ) . ")
						OR (rs.visibility = 'network' AND EXISTS (
							SELECT 1 FROM {$this->mp_db->network_households_table} nh 
							WHERE nh.network_id = rs.network_id 
							AND nh.household_id = " . ( $user_household_id ?: 0 ) . " 
							AND nh.status = 'accepted'
						))
					)
				)
				OR NOT EXISTS (
					SELECT 1 FROM {$this->mp_db->recipe_shares_table} rs2 
					WHERE rs2.recipe_id = {$wpdb->posts}.ID
				)
			)";

		return $where . $access_clause;
	}

	/**
	 * Get recipe sharing status
	 *
	 * @param int $recipe_id Recipe ID (post ID)
	 * @return array Sharing information
	 */
	public function get_recipe_sharing_status( int $recipe_id ): array {
		$recipe_share = $this->get_recipe_share_settings( $recipe_id );

		if ( ! $recipe_share ) {
			return array(
				'visibility'   => 'private',
				'household_id' => null,
				'network_id'   => null,
			);
		}

		return array(
			'visibility'   => $recipe_share->visibility,
			'household_id' => $recipe_share->household_id,
			'network_id'   => $recipe_share->network_id,
		);
	}
}
