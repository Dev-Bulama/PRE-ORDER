<?php
/**
 * Diagnostic helper class.
 *
 * @package    Mitzies_Jerk
 * @subpackage Mitzies_Jerk/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Diagnostic class for troubleshooting and sample data.
 */
class Mitzies_Jerk_Diagnostic {

    /**
     * Initialize diagnostic handlers.
     */
    public function __construct() {
        add_action( 'admin_post_mj_recreate_tables', array( $this, 'recreate_tables' ) );
        add_action( 'admin_post_mj_create_sample_data', array( $this, 'create_sample_data' ) );
        add_action( 'admin_post_mj_flush_cache', array( $this, 'flush_cache' ) );
    }

    /**
     * Recreate database tables.
     */
    public function recreate_tables() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized', 'mitzies-jerk' ) );
        }

        check_admin_referer( 'mj_create_tables', 'mj_create_tables_nonce' );

        // Run activator to create tables
        require_once MITZIES_JERK_PATH . 'includes/class-mitzies-jerk-activator.php';
        Mitzies_Jerk_Activator::activate();

        wp_safe_redirect( add_query_arg( 'mj_message', 'tables_created', admin_url( 'admin.php?page=mj-diagnostics' ) ) );
        exit;
    }

    /**
     * Create sample food items with categories and addons.
     */
    public function create_sample_data() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized', 'mitzies-jerk' ) );
        }

        check_admin_referer( 'mj_create_sample_data', 'mj_create_sample_nonce' );

        // Create categories
        $categories = array(
            'main-dishes'  => __( 'Main Dishes', 'mitzies-jerk' ),
            'sides'        => __( 'Sides', 'mitzies-jerk' ),
            'drinks'       => __( 'Drinks', 'mitzies-jerk' ),
            'desserts'     => __( 'Desserts', 'mitzies-jerk' ),
            'appetizers'   => __( 'Appetizers', 'mitzies-jerk' ),
        );

        $category_ids = array();
        foreach ( $categories as $slug => $name ) {
            $existing = term_exists( $slug, 'mj_food_category' );
            if ( $existing ) {
                $category_ids[ $slug ] = $existing['term_id'];
            } else {
                $term = wp_insert_term( $name, 'mj_food_category', array( 'slug' => $slug ) );
                if ( ! is_wp_error( $term ) ) {
                    $category_ids[ $slug ] = $term['term_id'];
                }
            }
        }

        // Sample food items with addons
        $food_items = array(
            array(
                'title'       => 'Jerk Chicken Deluxe',
                'description' => 'Our signature jerk chicken marinated for 24 hours in our secret blend of Caribbean spices. Served with rice and peas, coleslaw, and fried plantains. A customer favorite that brings the authentic taste of Jamaica to your plate.',
                'price'       => 18.99,
                'sale_price'  => '',
                'category'    => 'main-dishes',
                'featured'    => true,
                'addons'      => array(
                    array( 'name' => 'Extra Jerk Sauce', 'price' => 1.50 ),
                    array( 'name' => 'Add Festival Dumplings (3pc)', 'price' => 3.00 ),
                    array( 'name' => 'Upgrade to Large Portion', 'price' => 5.00 ),
                    array( 'name' => 'Add Grilled Vegetables', 'price' => 2.50 ),
                ),
            ),
            array(
                'title'       => 'Oxtail Stew',
                'description' => 'Slow-cooked oxtail braised in a rich, savory gravy with butter beans and Caribbean herbs. This traditional dish is simmered for hours until the meat falls off the bone. Served with your choice of rice or hard dough bread.',
                'price'       => 24.99,
                'sale_price'  => 21.99,
                'category'    => 'main-dishes',
                'featured'    => true,
                'addons'      => array(
                    array( 'name' => 'Extra Gravy', 'price' => 2.00 ),
                    array( 'name' => 'Add Steamed Vegetables', 'price' => 3.50 ),
                    array( 'name' => 'Hard Dough Bread', 'price' => 2.00 ),
                ),
            ),
            array(
                'title'       => 'Curry Goat',
                'description' => 'Tender pieces of goat meat slow-cooked in aromatic Caribbean curry with potatoes. Our curry blend includes turmeric, cumin, and scotch bonnet peppers for an authentic island flavor. Served with rice and peas.',
                'price'       => 19.99,
                'sale_price'  => '',
                'category'    => 'main-dishes',
                'featured'    => false,
                'addons'      => array(
                    array( 'name' => 'Extra Curry Sauce', 'price' => 1.50 ),
                    array( 'name' => 'Add Roti Bread', 'price' => 2.50 ),
                    array( 'name' => 'Make it Spicy', 'price' => 0.00 ),
                ),
            ),
            array(
                'title'       => 'Ackee and Saltfish',
                'description' => 'Jamaica\'s national dish featuring sautéed ackee fruit with salted codfish, onions, tomatoes, and scotch bonnet peppers. Served with fried dumplings, boiled banana, and callaloo.',
                'price'       => 16.99,
                'sale_price'  => '',
                'category'    => 'main-dishes',
                'featured'    => true,
                'addons'      => array(
                    array( 'name' => 'Extra Dumplings (2pc)', 'price' => 2.00 ),
                    array( 'name' => 'Add Fried Plantains', 'price' => 2.50 ),
                    array( 'name' => 'Add Avocado', 'price' => 3.00 ),
                ),
            ),
            array(
                'title'       => 'Jerk Pork',
                'description' => 'Succulent pork shoulder rubbed with our homemade jerk seasoning and slow-roasted over pimento wood. Served with festival dumplings, rice and peas, and a side of our signature pepper sauce.',
                'price'       => 17.99,
                'sale_price'  => '',
                'category'    => 'main-dishes',
                'featured'    => false,
                'addons'      => array(
                    array( 'name' => 'Extra Jerk Sauce', 'price' => 1.50 ),
                    array( 'name' => 'Add Coleslaw', 'price' => 2.00 ),
                ),
            ),
            array(
                'title'       => 'Rice and Peas',
                'description' => 'Traditional Jamaican rice cooked in coconut milk with kidney beans, thyme, and scotch bonnet pepper. The perfect accompaniment to any main dish.',
                'price'       => 4.99,
                'sale_price'  => '',
                'category'    => 'sides',
                'featured'    => false,
                'addons'      => array(
                    array( 'name' => 'Large Size', 'price' => 2.00 ),
                ),
            ),
            array(
                'title'       => 'Fried Plantains',
                'description' => 'Sweet ripe plantains sliced and fried to golden perfection. Sweet, caramelized, and absolutely delicious.',
                'price'       => 5.99,
                'sale_price'  => '',
                'category'    => 'sides',
                'featured'    => false,
                'addons'      => array(
                    array( 'name' => 'Extra Portion', 'price' => 3.00 ),
                ),
            ),
            array(
                'title'       => 'Festival Dumplings',
                'description' => 'Sweet, fried cornmeal dumplings - a perfect side for jerk dishes. Crispy on the outside, soft and fluffy inside. (4 pieces)',
                'price'       => 4.99,
                'sale_price'  => '',
                'category'    => 'sides',
                'featured'    => false,
                'addons'      => array(),
            ),
            array(
                'title'       => 'Jamaican Patty',
                'description' => 'Flaky golden pastry filled with seasoned beef in a rich, spicy gravy. A beloved Caribbean street food.',
                'price'       => 3.99,
                'sale_price'  => '',
                'category'    => 'appetizers',
                'featured'    => false,
                'addons'      => array(
                    array( 'name' => 'Chicken Filling', 'price' => 0.00 ),
                    array( 'name' => 'Vegetable Filling', 'price' => 0.00 ),
                    array( 'name' => 'Extra Spicy', 'price' => 0.00 ),
                ),
            ),
            array(
                'title'       => 'Sorrel Drink',
                'description' => 'Traditional Caribbean hibiscus drink made with sorrel flowers, ginger, and Caribbean spices. Refreshing and full of flavor. Served chilled.',
                'price'       => 4.99,
                'sale_price'  => '',
                'category'    => 'drinks',
                'featured'    => false,
                'addons'      => array(
                    array( 'name' => 'Large Size', 'price' => 1.50 ),
                    array( 'name' => 'Add Rum Shot', 'price' => 4.00 ),
                ),
            ),
            array(
                'title'       => 'Ginger Beer',
                'description' => 'Spicy homemade ginger beer with real ginger root. Non-alcoholic and refreshingly bold.',
                'price'       => 3.99,
                'sale_price'  => '',
                'category'    => 'drinks',
                'featured'    => false,
                'addons'      => array(
                    array( 'name' => 'Large Size', 'price' => 1.00 ),
                ),
            ),
            array(
                'title'       => 'Rum Cake',
                'description' => 'Dense, moist Caribbean rum cake soaked in premium dark rum and topped with glazed nuts. A sweet end to your Caribbean feast.',
                'price'       => 6.99,
                'sale_price'  => '',
                'category'    => 'desserts',
                'featured'    => true,
                'addons'      => array(
                    array( 'name' => 'Add Vanilla Ice Cream', 'price' => 2.50 ),
                    array( 'name' => 'Extra Rum Glaze', 'price' => 1.00 ),
                ),
            ),
        );

        global $wpdb;
        $addons_table = $wpdb->prefix . MITZIES_JERK_TABLE_PREFIX . 'addons';
        $created_count = 0;

        foreach ( $food_items as $item ) {
            // Check if item already exists
            $existing = get_page_by_title( $item['title'], OBJECT, 'mj_food_item' );
            if ( $existing ) {
                continue;
            }

            // Create food item
            $post_id = wp_insert_post( array(
                'post_title'   => $item['title'],
                'post_content' => $item['description'],
                'post_excerpt' => wp_trim_words( $item['description'], 20 ),
                'post_status'  => 'publish',
                'post_type'    => 'mj_food_item',
            ) );

            if ( is_wp_error( $post_id ) ) {
                continue;
            }

            // Set meta data
            update_post_meta( $post_id, '_mj_price', $item['price'] );
            if ( ! empty( $item['sale_price'] ) ) {
                update_post_meta( $post_id, '_mj_sale_price', $item['sale_price'] );
            }
            update_post_meta( $post_id, '_mj_stock_status', 'instock' );
            update_post_meta( $post_id, '_mj_stock_quantity', 100 );
            update_post_meta( $post_id, '_mj_is_featured', $item['featured'] ? 1 : 0 );
            update_post_meta( $post_id, '_mj_preparation_time', '15-25 mins' );

            // Set category
            if ( isset( $category_ids[ $item['category'] ] ) ) {
                wp_set_object_terms( $post_id, array( intval( $category_ids[ $item['category'] ] ) ), 'mj_food_category' );
            }

            // Create addons
            foreach ( $item['addons'] as $addon ) {
                $wpdb->insert(
                    $addons_table,
                    array(
                        'food_item_id' => $post_id,
                        'addon_name'   => $addon['name'],
                        'addon_price'  => $addon['price'],
                        'status'       => 'active',
                        'sort_order'   => 0,
                        'created_at'   => current_time( 'mysql' ),
                    ),
                    array( '%d', '%s', '%f', '%s', '%d', '%s' )
                );
            }

            $created_count++;
        }

        wp_safe_redirect( add_query_arg( array(
            'mj_message' => 'sample_created',
            'count'      => $created_count,
        ), admin_url( 'admin.php?page=mj-diagnostics' ) ) );
        exit;
    }

    /**
     * Flush plugin cache.
     */
    public function flush_cache() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized', 'mitzies-jerk' ) );
        }

        check_admin_referer( 'mj_flush_cache', 'mj_flush_cache_nonce' );

        // Clear transients
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_mj_%'" );
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_mj_%'" );

        // Clear session data for this user
        $session = new Mitzies_Jerk_Session();
        $session->destroy_session();

        // Flush rewrite rules
        flush_rewrite_rules();

        wp_safe_redirect( add_query_arg( 'mj_message', 'cache_cleared', admin_url( 'admin.php?page=mj-diagnostics' ) ) );
        exit;
    }
}
