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

        wp_localize_script( $this->plugin_name, 'mitziesJerk', array(
            'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
            'nonce'        => wp_create_nonce( 'mj_ajax_nonce' ),
            'cartUrl'      => get_permalink( get_option( 'mitzies_jerk_cart_page_id' ) ),
            'checkoutUrl'  => get_permalink( get_option( 'mitzies_jerk_checkout_page_id' ) ),
            'cartCount'    => $cart_count,
            'cartTotal'    => $cart_total,
            'currencySymbol' => mitzies_jerk_get_option( 'currency_symbol', '$' ),
            'strings'      => array(
                'addedToCart'    => __( 'Added to cart!', 'mitzies-jerk' ),
                'addingToCart'   => __( 'Adding...', 'mitzies-jerk' ),
                'viewCart'       => __( 'View Cart', 'mitzies-jerk' ),
                'checkout'       => __( 'Checkout', 'mitzies-jerk' ),
                'removeItem'     => __( 'Remove item?', 'mitzies-jerk' ),
                'updating'       => __( 'Updating...', 'mitzies-jerk' ),
                'processing'     => __( 'Processing...', 'mitzies-jerk' ),
                'pleaseWait'     => __( 'Please wait...', 'mitzies-jerk' ),
                'error'          => __( 'An error occurred. Please try again.', 'mitzies-jerk' ),
                'invalidDate'    => __( 'Please select a valid delivery date.', 'mitzies-jerk' ),
                'invalidTime'    => __( 'Please select a delivery time slot.', 'mitzies-jerk' ),
                'fillRequired'   => __( 'Please fill in all required fields.', 'mitzies-jerk' ),
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
