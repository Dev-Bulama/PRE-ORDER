<?php
/**
 * REST API handler class.
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
 * REST API handler class.
 */
class Mitzies_Jerk_Rest_Api {

    /**
     * Namespace.
     *
     * @var string
     */
    private $namespace = 'mitzies-jerk/v1';

    /**
     * Register REST API routes.
     */
    public function register_routes() {
        // Food items.
        register_rest_route( $this->namespace, '/food-items', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_food_items' ),
                'permission_callback' => '__return_true',
                'args'                => $this->get_collection_params(),
            ),
        ) );

        register_rest_route( $this->namespace, '/food-items/(?P<id>[\d]+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_food_item' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'id' => array(
                        'required'    => true,
                        'type'        => 'integer',
                        'description' => __( 'Food item ID.', 'mitzies-jerk' ),
                    ),
                ),
            ),
        ) );

        // Categories.
        register_rest_route( $this->namespace, '/categories', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_categories' ),
                'permission_callback' => '__return_true',
            ),
        ) );

        // Cart.
        register_rest_route( $this->namespace, '/cart', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_cart' ),
                'permission_callback' => '__return_true',
            ),
        ) );

        register_rest_route( $this->namespace, '/cart/add', array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'add_to_cart' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'food_item_id' => array(
                        'required'    => true,
                        'type'        => 'integer',
                        'description' => __( 'Food item ID.', 'mitzies-jerk' ),
                    ),
                    'quantity'     => array(
                        'required'    => false,
                        'type'        => 'integer',
                        'default'     => 1,
                        'description' => __( 'Quantity.', 'mitzies-jerk' ),
                    ),
                    'addons'       => array(
                        'required'    => false,
                        'type'        => 'array',
                        'default'     => array(),
                        'description' => __( 'Selected addons.', 'mitzies-jerk' ),
                    ),
                ),
            ),
        ) );

        // Orders (authenticated).
        register_rest_route( $this->namespace, '/orders', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_orders' ),
                'permission_callback' => array( $this, 'check_permissions' ),
            ),
        ) );

        register_rest_route( $this->namespace, '/orders/(?P<id>[\d]+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_order' ),
                'permission_callback' => array( $this, 'check_permissions' ),
                'args'                => array(
                    'id' => array(
                        'required'    => true,
                        'type'        => 'integer',
                        'description' => __( 'Order ID.', 'mitzies-jerk' ),
                    ),
                ),
            ),
        ) );

        // Order tracking (public with order number + email).
        register_rest_route( $this->namespace, '/track', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'track_order' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'order_number' => array(
                        'required'    => true,
                        'type'        => 'string',
                        'description' => __( 'Order number.', 'mitzies-jerk' ),
                    ),
                    'email'        => array(
                        'required'    => true,
                        'type'        => 'string',
                        'format'      => 'email',
                        'description' => __( 'Customer email.', 'mitzies-jerk' ),
                    ),
                ),
            ),
        ) );

        // Settings (for mobile app configuration).
        register_rest_route( $this->namespace, '/settings', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_settings' ),
                'permission_callback' => '__return_true',
            ),
        ) );
    }

    /**
     * Get collection parameters.
     *
     * @return array
     */
    private function get_collection_params() {
        return array(
            'page'     => array(
                'type'        => 'integer',
                'default'     => 1,
                'minimum'     => 1,
                'description' => __( 'Page number.', 'mitzies-jerk' ),
            ),
            'per_page' => array(
                'type'        => 'integer',
                'default'     => 12,
                'minimum'     => 1,
                'maximum'     => 100,
                'description' => __( 'Items per page.', 'mitzies-jerk' ),
            ),
            'category' => array(
                'type'        => 'integer',
                'description' => __( 'Category ID.', 'mitzies-jerk' ),
            ),
            'search'   => array(
                'type'        => 'string',
                'description' => __( 'Search query.', 'mitzies-jerk' ),
            ),
            'orderby'  => array(
                'type'        => 'string',
                'default'     => 'date',
                'enum'        => array( 'date', 'title', 'price', 'rating' ),
                'description' => __( 'Order by field.', 'mitzies-jerk' ),
            ),
            'order'    => array(
                'type'        => 'string',
                'default'     => 'DESC',
                'enum'        => array( 'ASC', 'DESC' ),
                'description' => __( 'Sort order.', 'mitzies-jerk' ),
            ),
        );
    }

    /**
     * Check permissions.
     *
     * @param WP_REST_Request $request Request object.
     * @return bool|WP_Error
     */
    public function check_permissions( $request ) {
        if ( ! is_user_logged_in() ) {
            return new WP_Error(
                'rest_unauthorized',
                __( 'You must be logged in.', 'mitzies-jerk' ),
                array( 'status' => 401 )
            );
        }
        return true;
    }

    /**
     * Get food items.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_food_items( $request ) {
        $args = array(
            'post_type'      => 'mj_food_item',
            'post_status'    => 'publish',
            'posts_per_page' => $request->get_param( 'per_page' ),
            'paged'          => $request->get_param( 'page' ),
            'orderby'        => $request->get_param( 'orderby' ),
            'order'          => $request->get_param( 'order' ),
        );

        if ( $request->get_param( 'category' ) ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'mj_food_category',
                    'terms'    => $request->get_param( 'category' ),
                ),
            );
        }

        if ( $request->get_param( 'search' ) ) {
            $args['s'] = $request->get_param( 'search' );
        }

        if ( 'price' === $request->get_param( 'orderby' ) ) {
            $args['meta_key'] = '_mj_price';
            $args['orderby'] = 'meta_value_num';
        }

        $query = new WP_Query( $args );
        $items = array();

        while ( $query->have_posts() ) {
            $query->the_post();
            $items[] = $this->prepare_food_item( get_the_ID() );
        }

        wp_reset_postdata();

        return new WP_REST_Response( array(
            'items'       => $items,
            'total'       => $query->found_posts,
            'total_pages' => $query->max_num_pages,
            'page'        => $request->get_param( 'page' ),
        ), 200 );
    }

    /**
     * Get single food item.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_food_item( $request ) {
        $id = $request->get_param( 'id' );
        $post = get_post( $id );

        if ( ! $post || 'mj_food_item' !== $post->post_type ) {
            return new WP_Error( 'not_found', __( 'Food item not found.', 'mitzies-jerk' ), array( 'status' => 404 ) );
        }

        return new WP_REST_Response( $this->prepare_food_item( $id, true ), 200 );
    }

    /**
     * Prepare food item for response.
     *
     * @param int  $id   Post ID.
     * @param bool $full Include full details.
     * @return array
     */
    private function prepare_food_item( $id, $full = false ) {
        $price = get_post_meta( $id, '_mj_price', true );
        $sale_price = get_post_meta( $id, '_mj_sale_price', true );
        $stock_status = get_post_meta( $id, '_mj_stock_status', true );

        $item = array(
            'id'           => $id,
            'title'        => get_the_title( $id ),
            'slug'         => get_post_field( 'post_name', $id ),
            'permalink'    => get_permalink( $id ),
            'excerpt'      => get_the_excerpt( $id ),
            'image'        => get_the_post_thumbnail_url( $id, 'medium' ),
            'price'        => floatval( $price ),
            'sale_price'   => $sale_price ? floatval( $sale_price ) : null,
            'in_stock'     => 'outofstock' !== $stock_status,
            'rating'       => Mitzies_Jerk_Database::get_average_rating( $id ),
            'categories'   => wp_get_post_terms( $id, 'mj_food_category', array( 'fields' => 'names' ) ),
        );

        if ( $full ) {
            $item['content'] = apply_filters( 'the_content', get_post_field( 'post_content', $id ) );
            $item['ingredients'] = get_post_meta( $id, '_mj_ingredients', true );
            $item['preparation_time'] = get_post_meta( $id, '_mj_preparation_time', true );
            $item['calories'] = get_post_meta( $id, '_mj_calories', true );
            $item['gallery'] = array();

            $gallery = get_post_meta( $id, '_mj_gallery', true );
            if ( is_array( $gallery ) ) {
                foreach ( $gallery as $image_id ) {
                    $item['gallery'][] = wp_get_attachment_image_url( $image_id, 'large' );
                }
            }

            $item['addons'] = array();
            $addons = Mitzies_Jerk_Database::get_food_addons( $id );
            foreach ( $addons as $addon ) {
                $item['addons'][] = array(
                    'id'    => $addon->id,
                    'name'  => $addon->addon_name,
                    'price' => floatval( $addon->addon_price ),
                );
            }

            $item['reviews'] = array();
            $reviews = Mitzies_Jerk_Database::get_reviews( $id );
            foreach ( $reviews as $review ) {
                $user = get_user_by( 'id', $review->user_id );
                $item['reviews'][] = array(
                    'rating'     => intval( $review->rating ),
                    'review'     => $review->review_text,
                    'author'     => $user ? $user->display_name : __( 'Guest', 'mitzies-jerk' ),
                    'created_at' => $review->created_at,
                );
            }
        }

        return $item;
    }

    /**
     * Get categories.
     *
     * @return WP_REST_Response
     */
    public function get_categories() {
        $terms = get_terms( array(
            'taxonomy'   => 'mj_food_category',
            'hide_empty' => false,
        ) );

        $categories = array();

        foreach ( $terms as $term ) {
            $thumbnail_id = get_term_meta( $term->term_id, 'thumbnail_id', true );

            $categories[] = array(
                'id'          => $term->term_id,
                'name'        => $term->name,
                'slug'        => $term->slug,
                'description' => $term->description,
                'count'       => $term->count,
                'image'       => $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'medium' ) : null,
            );
        }

        return new WP_REST_Response( $categories, 200 );
    }

    /**
     * Get cart.
     *
     * @return WP_REST_Response
     */
    public function get_cart() {
        global $mitzies_jerk;

        return new WP_REST_Response( $mitzies_jerk->cart->get_cart_for_display(), 200 );
    }

    /**
     * Add to cart.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function add_to_cart( $request ) {
        global $mitzies_jerk;

        $result = $mitzies_jerk->cart->add_to_cart(
            $request->get_param( 'food_item_id' ),
            $request->get_param( 'quantity' ),
            $request->get_param( 'addons' )
        );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return new WP_REST_Response( array(
            'success'    => true,
            'message'    => __( 'Item added to cart.', 'mitzies-jerk' ),
            'cart_count' => $mitzies_jerk->cart->get_cart_count(),
            'cart'       => $mitzies_jerk->cart->get_cart_for_display(),
        ), 200 );
    }

    /**
     * Get orders.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_orders( $request ) {
        $user_id = get_current_user_id();
        $orders = Mitzies_Jerk_Order::get_user_orders( $user_id );

        $data = array();
        foreach ( $orders['orders'] as $order ) {
            $data[] = $this->prepare_order( $order );
        }

        return new WP_REST_Response( array(
            'orders' => $data,
            'total'  => $orders['total'],
        ), 200 );
    }

    /**
     * Get single order.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_order( $request ) {
        $order = new Mitzies_Jerk_Order( $request->get_param( 'id' ) );

        if ( ! $order->get_id() ) {
            return new WP_Error( 'not_found', __( 'Order not found.', 'mitzies-jerk' ), array( 'status' => 404 ) );
        }

        // Check ownership.
        if ( $order->get( 'user_id' ) != get_current_user_id() && ! current_user_can( 'edit_mj_orders' ) ) {
            return new WP_Error( 'forbidden', __( 'You do not have permission.', 'mitzies-jerk' ), array( 'status' => 403 ) );
        }

        return new WP_REST_Response( $this->prepare_order( $order, true ), 200 );
    }

    /**
     * Prepare order for response.
     *
     * @param Mitzies_Jerk_Order $order Order object.
     * @param bool               $full  Include full details.
     * @return array
     */
    private function prepare_order( $order, $full = false ) {
        $statuses = mitzies_jerk_get_order_statuses();
        $status = $order->get( 'status' );

        $data = array(
            'id'                => $order->get_id(),
            'order_number'      => $order->get( 'order_number' ),
            'status'            => $status,
            'status_label'      => isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status,
            'total'             => floatval( $order->get( 'total' ) ),
            'delivery_datetime' => $order->get( 'delivery_datetime' ),
            'created_at'        => $order->get( 'created_at' ),
        );

        if ( $full ) {
            $data['billing'] = $order->get( 'billing' );
            $data['delivery'] = $order->get( 'delivery' );
            $data['subtotal'] = floatval( $order->get( 'subtotal' ) );
            $data['discount'] = floatval( $order->get( 'discount' ) );
            $data['delivery_fee'] = floatval( $order->get( 'delivery_fee' ) );
            $data['tax'] = floatval( $order->get( 'tax' ) );
            $data['payment_method'] = $order->get( 'payment_method' );

            $data['items'] = array();
            foreach ( $order->get_items() as $item ) {
                $food_item = get_post( $item->food_item_id );
                $data['items'][] = array(
                    'name'     => $food_item ? $food_item->post_title : __( 'Item', 'mitzies-jerk' ),
                    'quantity' => intval( $item->quantity ),
                    'price'    => floatval( $item->price ),
                    'subtotal' => floatval( $item->subtotal ),
                );
            }
        }

        return $data;
    }

    /**
     * Track order.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function track_order( $request ) {
        $order = Mitzies_Jerk_Order::get_by_order_number( $request->get_param( 'order_number' ) );

        if ( ! $order ) {
            return new WP_Error( 'not_found', __( 'Order not found.', 'mitzies-jerk' ), array( 'status' => 404 ) );
        }

        $billing = $order->get( 'billing' );
        if ( strtolower( $billing['email'] ) !== strtolower( $request->get_param( 'email' ) ) ) {
            return new WP_Error( 'forbidden', __( 'Email does not match.', 'mitzies-jerk' ), array( 'status' => 403 ) );
        }

        return new WP_REST_Response( $this->prepare_order( $order, true ), 200 );
    }

    /**
     * Get settings.
     *
     * @return WP_REST_Response
     */
    public function get_settings() {
        return new WP_REST_Response( array(
            'currency'          => mitzies_jerk_get_option( 'currency', 'USD' ),
            'currency_symbol'   => mitzies_jerk_get_option( 'currency_symbol', '$' ),
            'min_preorder_hours' => mitzies_jerk_get_min_preorder_hours(),
            'max_preorder_days' => mitzies_jerk_get_option( 'max_preorder_days', 30 ),
            'delivery_fee'      => floatval( mitzies_jerk_get_option( 'delivery_fee', 0 ) ),
            'free_delivery_threshold' => floatval( mitzies_jerk_get_option( 'free_delivery_threshold', 0 ) ),
            'delivery_days'     => mitzies_jerk_get_option( 'delivery_days', array( 0, 1, 2, 3, 4, 5, 6 ) ),
            'delivery_time_slots' => mitzies_jerk_get_option( 'delivery_time_slots', array() ),
        ), 200 );
    }
}
