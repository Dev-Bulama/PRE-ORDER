<?php
/**
 * Register custom post types and taxonomies.
 *
 * @package    Mitzies_Jerk
 * @subpackage Mitzies_Jerk/includes
 * @author     SkillScore IT Solutions and Training, Tijani Bulama
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register custom post types and taxonomies.
 */
class Mitzies_Jerk_Post_Types {

    /**
     * Register all custom post types.
     *
     * @since    1.0.0
     */
    public function register_post_types() {
        $this->register_food_item_post_type();
        $this->register_order_post_type();
        $this->register_post_meta();
    }

    /**
     * Register post meta for REST API support.
     *
     * @since    1.0.0
     */
    private function register_post_meta() {
        // Register meta fields for food items to work with block editor.
        $meta_fields = array(
            '_mj_price'            => array( 'type' => 'number', 'default' => 0 ),
            '_mj_sale_price'       => array( 'type' => 'number', 'default' => '' ),
            '_mj_stock_status'     => array( 'type' => 'string', 'default' => 'instock' ),
            '_mj_stock_quantity'   => array( 'type' => 'integer', 'default' => 0 ),
            '_mj_is_featured'      => array( 'type' => 'boolean', 'default' => false ),
            '_mj_ingredients'      => array( 'type' => 'string', 'default' => '' ),
            '_mj_preparation_time' => array( 'type' => 'string', 'default' => '' ),
            '_mj_calories'         => array( 'type' => 'string', 'default' => '' ),
        );

        foreach ( $meta_fields as $meta_key => $args ) {
            register_post_meta( 'mj_food_item', $meta_key, array(
                'show_in_rest'      => true,
                'single'            => true,
                'type'              => $args['type'],
                'default'           => $args['default'],
                'sanitize_callback' => $args['type'] === 'number' ? 'floatval' : ( $args['type'] === 'integer' ? 'absint' : 'sanitize_text_field' ),
                'auth_callback'     => function() {
                    return current_user_can( 'edit_posts' );
                },
            ) );
        }
    }

    /**
     * Register the Food Item post type.
     *
     * @since    1.0.0
     */
    private function register_food_item_post_type() {
        $labels = array(
            'name'                  => _x( 'Food Items', 'Post type general name', 'mitzies-jerk' ),
            'singular_name'         => _x( 'Food Item', 'Post type singular name', 'mitzies-jerk' ),
            'menu_name'             => _x( 'Food Items', 'Admin Menu text', 'mitzies-jerk' ),
            'name_admin_bar'        => _x( 'Food Item', 'Add New on Toolbar', 'mitzies-jerk' ),
            'add_new'               => __( 'Add New', 'mitzies-jerk' ),
            'add_new_item'          => __( 'Add New Food Item', 'mitzies-jerk' ),
            'new_item'              => __( 'New Food Item', 'mitzies-jerk' ),
            'edit_item'             => __( 'Edit Food Item', 'mitzies-jerk' ),
            'view_item'             => __( 'View Food Item', 'mitzies-jerk' ),
            'all_items'             => __( 'All Food Items', 'mitzies-jerk' ),
            'search_items'          => __( 'Search Food Items', 'mitzies-jerk' ),
            'parent_item_colon'     => __( 'Parent Food Items:', 'mitzies-jerk' ),
            'not_found'             => __( 'No food items found.', 'mitzies-jerk' ),
            'not_found_in_trash'    => __( 'No food items found in Trash.', 'mitzies-jerk' ),
            'featured_image'        => _x( 'Food Image', 'Overrides the "Featured Image" phrase', 'mitzies-jerk' ),
            'set_featured_image'    => _x( 'Set food image', 'Overrides the "Set featured image" phrase', 'mitzies-jerk' ),
            'remove_featured_image' => _x( 'Remove food image', 'Overrides the "Remove featured image" phrase', 'mitzies-jerk' ),
            'use_featured_image'    => _x( 'Use as food image', 'Overrides the "Use as featured image" phrase', 'mitzies-jerk' ),
            'archives'              => _x( 'Food Item archives', 'The post type archive label', 'mitzies-jerk' ),
            'insert_into_item'      => _x( 'Insert into food item', 'Overrides the "Insert into post" phrase', 'mitzies-jerk' ),
            'uploaded_to_this_item' => _x( 'Uploaded to this food item', 'Overrides the "Uploaded to this post" phrase', 'mitzies-jerk' ),
            'filter_items_list'     => _x( 'Filter food items list', 'Screen reader text for the filter links', 'mitzies-jerk' ),
            'items_list_navigation' => _x( 'Food items list navigation', 'Screen reader text for the pagination', 'mitzies-jerk' ),
            'items_list'            => _x( 'Food items list', 'Screen reader text for the items list', 'mitzies-jerk' ),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => false, // Will be added to custom menu.
            'query_var'           => true,
            'rewrite'             => array( 'slug' => 'food', 'with_front' => false ),
            'capability_type'     => array( 'mj_food_item', 'mj_food_items' ),
            'map_meta_cap'        => true,
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => null,
            'menu_icon'           => 'dashicons-carrot',
            'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments' ),
            'show_in_rest'        => true,
            'rest_base'           => 'food-items',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        );

        register_post_type( 'mj_food_item', $args );
    }

    /**
     * Register the Order post type.
     *
     * @since    1.0.0
     */
    private function register_order_post_type() {
        $labels = array(
            'name'                  => _x( 'Orders', 'Post type general name', 'mitzies-jerk' ),
            'singular_name'         => _x( 'Order', 'Post type singular name', 'mitzies-jerk' ),
            'menu_name'             => _x( 'Orders', 'Admin Menu text', 'mitzies-jerk' ),
            'name_admin_bar'        => _x( 'Order', 'Add New on Toolbar', 'mitzies-jerk' ),
            'add_new'               => __( 'Add New', 'mitzies-jerk' ),
            'add_new_item'          => __( 'Add New Order', 'mitzies-jerk' ),
            'new_item'              => __( 'New Order', 'mitzies-jerk' ),
            'edit_item'             => __( 'Edit Order', 'mitzies-jerk' ),
            'view_item'             => __( 'View Order', 'mitzies-jerk' ),
            'all_items'             => __( 'All Orders', 'mitzies-jerk' ),
            'search_items'          => __( 'Search Orders', 'mitzies-jerk' ),
            'parent_item_colon'     => __( 'Parent Orders:', 'mitzies-jerk' ),
            'not_found'             => __( 'No orders found.', 'mitzies-jerk' ),
            'not_found_in_trash'    => __( 'No orders found in Trash.', 'mitzies-jerk' ),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => false, // Will be added to custom menu.
            'query_var'           => false,
            'rewrite'             => false,
            'capability_type'     => array( 'mj_order', 'mj_orders' ),
            'map_meta_cap'        => true,
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_position'       => null,
            'menu_icon'           => 'dashicons-list-view',
            'supports'            => array( 'title' ),
            'show_in_rest'        => true,
            'rest_base'           => 'orders',
        );

        register_post_type( 'mj_order', $args );

        // Register custom order statuses.
        $this->register_order_statuses();
    }

    /**
     * Register custom order statuses.
     *
     * @since    1.0.0
     */
    private function register_order_statuses() {
        $statuses = array(
            'mj-pending'    => array(
                'label'                     => _x( 'Pending Payment', 'Order status', 'mitzies-jerk' ),
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                /* translators: %s: Number of orders */
                'label_count'               => _n_noop( 'Pending Payment <span class="count">(%s)</span>', 'Pending Payment <span class="count">(%s)</span>', 'mitzies-jerk' ),
            ),
            'mj-paid'       => array(
                'label'                     => _x( 'Paid', 'Order status', 'mitzies-jerk' ),
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop( 'Paid <span class="count">(%s)</span>', 'Paid <span class="count">(%s)</span>', 'mitzies-jerk' ),
            ),
            'mj-processing' => array(
                'label'                     => _x( 'Processing', 'Order status', 'mitzies-jerk' ),
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop( 'Processing <span class="count">(%s)</span>', 'Processing <span class="count">(%s)</span>', 'mitzies-jerk' ),
            ),
            'mj-preparing'  => array(
                'label'                     => _x( 'Preparing', 'Order status', 'mitzies-jerk' ),
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop( 'Preparing <span class="count">(%s)</span>', 'Preparing <span class="count">(%s)</span>', 'mitzies-jerk' ),
            ),
            'mj-ready'      => array(
                'label'                     => _x( 'Ready for Delivery', 'Order status', 'mitzies-jerk' ),
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop( 'Ready for Delivery <span class="count">(%s)</span>', 'Ready for Delivery <span class="count">(%s)</span>', 'mitzies-jerk' ),
            ),
            'mj-delivering' => array(
                'label'                     => _x( 'Out for Delivery', 'Order status', 'mitzies-jerk' ),
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop( 'Out for Delivery <span class="count">(%s)</span>', 'Out for Delivery <span class="count">(%s)</span>', 'mitzies-jerk' ),
            ),
            'mj-completed'  => array(
                'label'                     => _x( 'Completed', 'Order status', 'mitzies-jerk' ),
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop( 'Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>', 'mitzies-jerk' ),
            ),
            'mj-cancelled'  => array(
                'label'                     => _x( 'Cancelled', 'Order status', 'mitzies-jerk' ),
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>', 'mitzies-jerk' ),
            ),
            'mj-refunded'   => array(
                'label'                     => _x( 'Refunded', 'Order status', 'mitzies-jerk' ),
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop( 'Refunded <span class="count">(%s)</span>', 'Refunded <span class="count">(%s)</span>', 'mitzies-jerk' ),
            ),
            'mj-expired'    => array(
                'label'                     => _x( 'Payment Expired', 'Order status', 'mitzies-jerk' ),
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop( 'Payment Expired <span class="count">(%s)</span>', 'Payment Expired <span class="count">(%s)</span>', 'mitzies-jerk' ),
            ),
            'mj-failed'     => array(
                'label'                     => _x( 'Payment Failed', 'Order status', 'mitzies-jerk' ),
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop( 'Payment Failed <span class="count">(%s)</span>', 'Payment Failed <span class="count">(%s)</span>', 'mitzies-jerk' ),
            ),
        );

        foreach ( $statuses as $status => $args ) {
            register_post_status( $status, $args );
        }
    }

    /**
     * Register all custom taxonomies.
     *
     * @since    1.0.0
     */
    public function register_taxonomies() {
        $this->register_food_category_taxonomy();
        $this->register_food_tag_taxonomy();
    }

    /**
     * Register the Food Category taxonomy.
     *
     * @since    1.0.0
     */
    private function register_food_category_taxonomy() {
        $labels = array(
            'name'                       => _x( 'Food Categories', 'Taxonomy general name', 'mitzies-jerk' ),
            'singular_name'              => _x( 'Food Category', 'Taxonomy singular name', 'mitzies-jerk' ),
            'search_items'               => __( 'Search Food Categories', 'mitzies-jerk' ),
            'popular_items'              => __( 'Popular Food Categories', 'mitzies-jerk' ),
            'all_items'                  => __( 'All Food Categories', 'mitzies-jerk' ),
            'parent_item'                => __( 'Parent Food Category', 'mitzies-jerk' ),
            'parent_item_colon'          => __( 'Parent Food Category:', 'mitzies-jerk' ),
            'edit_item'                  => __( 'Edit Food Category', 'mitzies-jerk' ),
            'view_item'                  => __( 'View Food Category', 'mitzies-jerk' ),
            'update_item'                => __( 'Update Food Category', 'mitzies-jerk' ),
            'add_new_item'               => __( 'Add New Food Category', 'mitzies-jerk' ),
            'new_item_name'              => __( 'New Food Category Name', 'mitzies-jerk' ),
            'separate_items_with_commas' => __( 'Separate categories with commas', 'mitzies-jerk' ),
            'add_or_remove_items'        => __( 'Add or remove categories', 'mitzies-jerk' ),
            'choose_from_most_used'      => __( 'Choose from the most used categories', 'mitzies-jerk' ),
            'not_found'                  => __( 'No food categories found.', 'mitzies-jerk' ),
            'no_terms'                   => __( 'No food categories', 'mitzies-jerk' ),
            'menu_name'                  => __( 'Categories', 'mitzies-jerk' ),
            'items_list_navigation'      => __( 'Food categories list navigation', 'mitzies-jerk' ),
            'items_list'                 => __( 'Food categories list', 'mitzies-jerk' ),
            'back_to_items'              => __( '&larr; Back to Food Categories', 'mitzies-jerk' ),
        );

        $args = array(
            'labels'             => $labels,
            'hierarchical'       => true,
            'public'             => true,
            'show_ui'            => true,
            'show_admin_column'  => true,
            'show_in_nav_menus'  => true,
            'show_tagcloud'      => true,
            'rewrite'            => array( 'slug' => 'food-category', 'with_front' => false, 'hierarchical' => true ),
            'show_in_rest'       => true,
            'rest_base'          => 'food-categories',
        );

        register_taxonomy( 'mj_food_category', array( 'mj_food_item' ), $args );
    }

    /**
     * Register the Food Tag taxonomy.
     *
     * @since    1.0.0
     */
    private function register_food_tag_taxonomy() {
        $labels = array(
            'name'                       => _x( 'Food Tags', 'Taxonomy general name', 'mitzies-jerk' ),
            'singular_name'              => _x( 'Food Tag', 'Taxonomy singular name', 'mitzies-jerk' ),
            'search_items'               => __( 'Search Food Tags', 'mitzies-jerk' ),
            'popular_items'              => __( 'Popular Food Tags', 'mitzies-jerk' ),
            'all_items'                  => __( 'All Food Tags', 'mitzies-jerk' ),
            'edit_item'                  => __( 'Edit Food Tag', 'mitzies-jerk' ),
            'view_item'                  => __( 'View Food Tag', 'mitzies-jerk' ),
            'update_item'                => __( 'Update Food Tag', 'mitzies-jerk' ),
            'add_new_item'               => __( 'Add New Food Tag', 'mitzies-jerk' ),
            'new_item_name'              => __( 'New Food Tag Name', 'mitzies-jerk' ),
            'separate_items_with_commas' => __( 'Separate tags with commas', 'mitzies-jerk' ),
            'add_or_remove_items'        => __( 'Add or remove tags', 'mitzies-jerk' ),
            'choose_from_most_used'      => __( 'Choose from the most used tags', 'mitzies-jerk' ),
            'not_found'                  => __( 'No food tags found.', 'mitzies-jerk' ),
            'no_terms'                   => __( 'No food tags', 'mitzies-jerk' ),
            'menu_name'                  => __( 'Tags', 'mitzies-jerk' ),
            'items_list_navigation'      => __( 'Food tags list navigation', 'mitzies-jerk' ),
            'items_list'                 => __( 'Food tags list', 'mitzies-jerk' ),
            'back_to_items'              => __( '&larr; Back to Food Tags', 'mitzies-jerk' ),
        );

        $args = array(
            'labels'             => $labels,
            'hierarchical'       => false,
            'public'             => true,
            'show_ui'            => true,
            'show_admin_column'  => true,
            'show_in_nav_menus'  => true,
            'show_tagcloud'      => true,
            'rewrite'            => array( 'slug' => 'food-tag', 'with_front' => false ),
            'show_in_rest'       => true,
            'rest_base'          => 'food-tags',
        );

        register_taxonomy( 'mj_food_tag', array( 'mj_food_item' ), $args );
    }
}
