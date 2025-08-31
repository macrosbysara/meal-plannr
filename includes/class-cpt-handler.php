<?php

/**
 * CPT Handler
 *
 * @package Meal Plannr
 */

namespace MealPlannr;

/**
 * Class CPT_Handler
 *
 * Handles the registration of custom post types and taxonomies.
 */
class CPT_Handler
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('init', array( $this, 'register_custom_post_types' ));
        add_action('init', array( $this, 'register_custom_taxonomies' ));
    }

    /**
     * Register custom taxonomies.
     */
    public function register_custom_taxonomies()
    {
        register_taxonomy(
            'cuisine',
            array(
                0 => 'recipe',
            ),
            array(
                'labels'       => array(
                    'name'                       => 'Cuisines',
                    'singular_name'              => 'Cuisine',
                    'menu_name'                  => 'Cuisines',
                    'all_items'                  => 'All Cuisines',
                    'edit_item'                  => 'Edit Cuisine',
                    'view_item'                  => 'View Cuisine',
                    'update_item'                => 'Update Cuisine',
                    'add_new_item'               => 'Add New Cuisine',
                    'new_item_name'              => 'New Cuisine Name',
                    'search_items'               => 'Search Cuisines',
                    'popular_items'              => 'Popular Cuisines',
                    'separate_items_with_commas' => 'Separate cuisines with commas',
                    'add_or_remove_items'        => 'Add or remove cuisines',
                    'choose_from_most_used'      => 'Choose from the most used cuisines',
                    'not_found'                  => 'No cuisines found',
                    'no_terms'                   => 'No cuisines',
                    'items_list_navigation'      => 'Cuisines list navigation',
                    'items_list'                 => 'Cuisines list',
                    'back_to_items'              => '← Go to cuisines',
                    'item_link'                  => 'Cuisine Link',
                    'item_link_description'      => 'A link to a cuisine',
                ),
                'public'       => true,
                'show_in_menu' => true,
                'show_in_rest' => true,
            )
        );

        register_taxonomy(
            'diet',
            array(
                0 => 'recipe',
            ),
            array(
                'labels'       => array(
                    'name'                       => 'Diets',
                    'singular_name'              => 'Diet',
                    'menu_name'                  => 'Diets',
                    'all_items'                  => 'All Diets',
                    'edit_item'                  => 'Edit Diet',
                    'view_item'                  => 'View Diet',
                    'update_item'                => 'Update Diet',
                    'add_new_item'               => 'Add New Diet',
                    'new_item_name'              => 'New Diet Name',
                    'search_items'               => 'Search Diets',
                    'popular_items'              => 'Popular Diets',
                    'separate_items_with_commas' => 'Separate diets with commas',
                    'add_or_remove_items'        => 'Add or remove diets',
                    'choose_from_most_used'      => 'Choose from the most used diets',
                    'not_found'                  => 'No diets found',
                    'no_terms'                   => 'No diets',
                    'items_list_navigation'      => 'Diets list navigation',
                    'items_list'                 => 'Diets list',
                    'back_to_items'              => '← Go to diets',
                    'item_link'                  => 'Diet Link',
                    'item_link_description'      => 'A link to a diet',
                ),
                'public'       => true,
                'show_in_menu' => true,
                'show_in_rest' => true,
            )
        );
    }

    /**
     * Register custom post types.
     */
    public function register_custom_post_types()
    {
        register_post_type(
            'recipe',
            array(
                'labels'           => array(
                    'name'                     => 'Recipes',
                    'singular_name'            => 'Recipe',
                    'menu_name'                => 'Recipes',
                    'all_items'                => 'All Recipes',
                    'edit_item'                => 'Edit Recipe',
                    'view_item'                => 'View Recipe',
                    'view_items'               => 'View Recipes',
                    'add_new_item'             => 'Add Recipe',
                    'add_new'                  => 'Add Recipe',
                    'new_item'                 => 'New Recipe',
                    'parent_item_colon'        => 'Parent Recipe:',
                    'search_items'             => 'Search Recipes',
                    'not_found'                => 'No recipes found',
                    'not_found_in_trash'       => 'No recipes found in Trash',
                    'archives'                 => 'Recipe Archives',
                    'attributes'               => 'Recipe Attributes',
                    'insert_into_item'         => 'Insert into recipe',
                    'uploaded_to_this_item'    => 'Uploaded to this recipe',
                    'filter_items_list'        => 'Filter recipes list',
                    'filter_by_date'           => 'Filter recipes by date',
                    'items_list_navigation'    => 'Recipes list navigation',
                    'items_list'               => 'Recipes list',
                    'item_published'           => 'Recipe published.',
                    'item_published_privately' => 'Recipe published privately.',
                    'item_reverted_to_draft'   => 'Recipe reverted to draft.',
                    'item_scheduled'           => 'Recipe scheduled.',
                    'item_updated'             => 'Recipe updated.',
                    'item_link'                => 'Recipe Link',
                    'item_link_description'    => 'A link to a recipe.',
                ),
                'public'           => true,
                'show_in_rest'     => true,
                'menu_position'    => 5,
                'menu_icon'        => 'dashicons-food',
                'supports'         => array(
                    0 => 'title',
                    1 => 'editor',
                    2 => 'thumbnail',
                    3 => 'custom-fields',
                ),
                'has_archive'      => 'recipes',
                'rewrite'          => array(
                    'feeds' => false,
                ),
                'delete_with_user' => false,
                'capability_type'  => 'recipe',
                'capabilities'     => array(
                    'edit_post'              => 'edit_recipes',
                    'edit_posts'             => 'edit_recipes',
                    'edit_others_posts'      => 'edit_recipes',
                    'publish_posts'          => 'publish_recipes',
                    'read_post'              => 'read',
                    'read_private_posts'     => 'read',
                    'delete_post'            => 'delete_recipes',
                    'delete_posts'           => 'delete_recipes',
                    'delete_private_posts'   => 'delete_recipes',
                    'delete_published_posts' => 'delete_recipes',
                    'delete_others_posts'    => 'delete_recipes',
                ),
                'template'         => array(
                    array(
                        'core/post-featured-image',
                        array(
                            'lock'  => array(
                                'move'   => true,
                                'remove' => true,
                            ),
                            'align' => 'wide',
                        ),
                    ),
                    array(
                        'meal-plannr/recipe-block',
                        array(
                            'lock' => array(
                                'move'   => true,
                                'remove' => true,
                            ),
                        ),
                    ),
                ),
            )
        );
    }
}
