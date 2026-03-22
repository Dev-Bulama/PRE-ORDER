<?php
/**
 * Public-facing functionality.
 *
 * @package    Mitzies_Jerk
 * @subpackage Mitzies_Jerk/public
 * @author     SkillScore IT Solutions and Training, Tijani Bulama
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Public class.
 */
class Mitzies_Jerk_Public {

    /**
     * Plugin name.
     *
     * @var string
     */
    private $plugin_name;

    /**
     * Plugin version.
     *
     * @var string
     */
    private $version;

    /**
     * Constructor.
     *
     * @param string $plugin_name Plugin name.
     * @param string $version     Plugin version.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Enqueue public styles.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            MITZIES_JERK_URL . 'public/css/mitzies-jerk-public.css',
            array(),
            $this->version,
            'all'
        );

        // Dashicons for icons.
        wp_enqueue_style( 'dashicons' );
    }

    /**
     * Enqueue public scripts.
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            MITZIES_JERK_URL . 'public/js/mitzies-jerk-public.js',
            array( 'jquery' ),
            $this->version,
            true
        );

        global $mitzies_jerk;

        $cart_count = 0;
        $cart_total = 0;

        if ( $mitzies_jerk && $mitzies_jerk->cart ) {
            $cart_count = $mitzies_jerk->cart->get_cart_count();
            $cart_total = $mitzies_jerk->cart->get_total();
        }

        // Get business hours from settings.
        $business_hours = mitzies_jerk_get_option( 'business_hours', array() );

        // Localize script with correct variable name expected by JS.
        wp_localize_script( $this->plugin_name, 'mitzies_jerk_params', array(
            'ajax_url'            => admin_url( 'admin-ajax.php' ),
            'nonce'               => wp_create_nonce( 'mj_ajax_nonce' ),
            'cart_url'            => get_permalink( get_option( 'mitzies_jerk_cart_page_id' ) ),
            'checkout_url'        => get_permalink( get_option( 'mitzies_jerk_checkout_page_id' ) ),
            'menu_url'            => get_permalink( get_option( 'mitzies_jerk_menu_page_id' ) ),
            'cart_count'          => $cart_count,
            'cart_total'          => $cart_total,
            'currency_symbol'     => mitzies_jerk_get_option( 'currency_symbol', '$' ),
            'currency_position'   => mitzies_jerk_get_option( 'currency_position', 'left' ),
            'decimals'            => mitzies_jerk_get_option( 'price_decimals', 2 ),
            'decimal_separator'   => mitzies_jerk_get_option( 'decimal_separator', '.' ),
            'thousand_separator'  => mitzies_jerk_get_option( 'thousand_separator', ',' ),
            'min_preorder_hours'  => mitzies_jerk_get_option( 'min_preorder_hours', 2 ),
            'max_preorder_days'   => mitzies_jerk_get_option( 'max_preorder_days', 7 ),
            'business_hours'      => $business_hours,
            'i18n'                => array(
                'add_to_cart'       => __( 'Add to Cart', 'mitzies-jerk' ),
                'added'             => __( 'Added!', 'mitzies-jerk' ),
                'adding'            => __( 'Adding...', 'mitzies-jerk' ),
                'view_cart'         => __( 'View Cart', 'mitzies-jerk' ),
                'checkout'          => __( 'Checkout', 'mitzies-jerk' ),
                'confirm_remove'    => __( 'Remove this item from cart?', 'mitzies-jerk' ),
                'confirm_reorder'   => __( 'Add all items from this order to your cart?', 'mitzies-jerk' ),
                'updating'          => __( 'Updating...', 'mitzies-jerk' ),
                'processing'        => __( 'Processing...', 'mitzies-jerk' ),
                'place_order'       => __( 'Place Order', 'mitzies-jerk' ),
                'please_wait'       => __( 'Please wait...', 'mitzies-jerk' ),
                'error'             => __( 'An error occurred. Please try again.', 'mitzies-jerk' ),
                'cart_empty'        => __( 'Your cart is empty.', 'mitzies-jerk' ),
                'continue_shopping' => __( 'Continue Shopping', 'mitzies-jerk' ),
                'enter_coupon'      => __( 'Please enter a coupon code.', 'mitzies-jerk' ),
                'enter_order_number' => __( 'Please enter your order number.', 'mitzies-jerk' ),
                'select_payment'    => __( 'Please select a payment method.', 'mitzies-jerk' ),
                'fill_required'     => __( 'Please fill in all required fields.', 'mitzies-jerk' ),
                'closed_day'        => __( 'Sorry, we are closed on this day.', 'mitzies-jerk' ),
                'order_success'     => __( 'Order placed successfully!', 'mitzies-jerk' ),
                'timeout'           => __( 'Request timed out. Please check your order status before trying again.', 'mitzies-jerk' ),
                'loading'           => __( 'Loading...', 'mitzies-jerk' ),
                'select_delivery'   => __( 'Select Delivery Method', 'mitzies-jerk' ),
                'select_pickup'     => __( 'Select Pickup Location', 'mitzies-jerk' ),
                'choose_location'   => __( 'Choose a location...', 'mitzies-jerk' ),
                'free'              => __( 'Free', 'mitzies-jerk' ),
                'distance'          => __( 'Distance', 'mitzies-jerk' ),
            ),
        ) );
    }

    /**
     * Template include filter.
     *
     * @param string $template Template path.
     * @return string
     */
    public function template_include( $template ) {
        if ( is_singular( 'mj_food_item' ) ) {
            $custom_template = MITZIES_JERK_PATH . 'public/templates/single-food-item.php';
            if ( file_exists( $custom_template ) ) {
                return $custom_template;
            }
        }

        if ( is_post_type_archive( 'mj_food_item' ) || is_tax( 'mj_food_category' ) || is_tax( 'mj_food_tag' ) ) {
            $custom_template = MITZIES_JERK_PATH . 'public/templates/archive-food-item.php';
            if ( file_exists( $custom_template ) ) {
                return $custom_template;
            }
        }

        return $template;
    }

    /**
     * Add body classes.
     *
     * @param array $classes Body classes.
     * @return array
     */
    public function body_class( $classes ) {
        if ( is_singular( 'mj_food_item' ) ) {
            $classes[] = 'mj-food-single';
        }

        if ( is_post_type_archive( 'mj_food_item' ) ) {
            $classes[] = 'mj-food-archive';
        }

        // Check if on plugin pages.
        $page_ids = array(
            get_option( 'mitzies_jerk_menu_page_id' ),
            get_option( 'mitzies_jerk_cart_page_id' ),
            get_option( 'mitzies_jerk_checkout_page_id' ),
            get_option( 'mitzies_jerk_order_received_page_id' ),
            get_option( 'mitzies_jerk_order_tracking_page_id' ),
            get_option( 'mitzies_jerk_my_account_page_id' ),
        );

        if ( is_page( $page_ids ) ) {
            $classes[] = 'mitzies-jerk-page';
        }

        return $classes;
    }

    /**
     * Add query vars.
     *
     * @param array $vars Query vars.
     * @return array
     */
    public function add_query_vars( $vars ) {
        $vars[] = 'mj-api';
        $vars[] = 'gateway';
        return $vars;
    }

    /**
     * Add rewrite rules.
     */
    public function add_rewrite_rules() {
        // Payment callback/webhook endpoints.
        add_rewrite_rule(
            '^mj-api/([^/]+)/?$',
            'index.php?mj-api=$matches[1]',
            'top'
        );

        // Handle API requests.
        add_action( 'template_redirect', array( $this, 'handle_api_request' ) );
    }

    /**
     * Handle API requests.
     */
    public function handle_api_request() {
        $api_action = get_query_var( 'mj-api' );

        if ( ! $api_action ) {
            // Check for GET parameter fallback.
            $api_action = isset( $_GET['mj-api'] ) ? sanitize_text_field( wp_unslash( $_GET['mj-api'] ) ) : '';
        }

        if ( ! $api_action ) {
            return;
        }

        switch ( $api_action ) {
            case 'payment-callback':
                $gateway = isset( $_GET['gateway'] ) ? sanitize_text_field( wp_unslash( $_GET['gateway'] ) ) : '';
                if ( $gateway ) {
                    $payment = new Mitzies_Jerk_Payment();
                    $result = $payment->handle_callback( $gateway );

                    if ( isset( $result['redirect'] ) ) {
                        wp_redirect( $result['redirect'] );
                        exit;
                    }

                    if ( is_wp_error( $result ) ) {
                        wp_redirect( add_query_arg( 'payment_error', urlencode( $result->get_error_message() ), get_permalink( get_option( 'mitzies_jerk_checkout_page_id' ) ) ) );
                        exit;
                    }
                }
                break;

            case 'payment-webhook':
                $gateway = isset( $_GET['gateway'] ) ? sanitize_text_field( wp_unslash( $_GET['gateway'] ) ) : '';
                if ( $gateway ) {
                    $payment = new Mitzies_Jerk_Payment();
                    $payment->handle_webhook( $gateway );
                }
                break;
        }
    }
}
