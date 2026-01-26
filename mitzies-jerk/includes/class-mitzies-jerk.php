<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
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
 * The core plugin class.
 */
class Mitzies_Jerk {

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Mitzies_Jerk_Loader $loader Maintains and registers all hooks.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_name The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $version;

    /**
     * The cart instance.
     *
     * @since    1.0.0
     * @access   public
     * @var      Mitzies_Jerk_Cart $cart
     */
    public $cart;

    /**
     * The session instance.
     *
     * @since    1.0.0
     * @access   public
     * @var      Mitzies_Jerk_Session $session
     */
    public $session;

    /**
     * Define the core functionality of the plugin.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->version = defined( 'MITZIES_JERK_VERSION' ) ? MITZIES_JERK_VERSION : '1.0.0';
        $this->plugin_name = 'mitzies-jerk';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_post_types();
        $this->define_shortcodes();
        $this->define_ajax_handlers();
        $this->define_rest_api();
        $this->define_elementor_hooks();
        $this->define_cron_jobs();

        // Set global instance.
        global $mitzies_jerk;
        $mitzies_jerk = $this;
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        // Core classes.
        require_once MITZIES_JERK_PATH . 'includes/class-mitzies-jerk-loader.php';
        require_once MITZIES_JERK_PATH . 'includes/class-mitzies-jerk-i18n.php';

        // Database and utilities.
        require_once MITZIES_JERK_PATH . 'includes/class-mitzies-jerk-database.php';
        require_once MITZIES_JERK_PATH . 'includes/class-mitzies-jerk-session.php';

        // Post types.
        require_once MITZIES_JERK_PATH . 'includes/class-mitzies-jerk-post-types.php';

        // Cart and checkout.
        require_once MITZIES_JERK_PATH . 'includes/class-mitzies-jerk-cart.php';
        require_once MITZIES_JERK_PATH . 'includes/class-mitzies-jerk-checkout.php';
        require_once MITZIES_JERK_PATH . 'includes/class-mitzies-jerk-order.php';

        // Payment gateways.
        require_once MITZIES_JERK_PATH . 'includes/class-mitzies-jerk-payment.php';
        require_once MITZIES_JERK_PATH . 'includes/payment-gateways/class-mitzies-jerk-gateway-paystack.php';
        require_once MITZIES_JERK_PATH . 'includes/payment-gateways/class-mitzies-jerk-gateway-flutterwave.php';
        require_once MITZIES_JERK_PATH . 'includes/payment-gateways/class-mitzies-jerk-gateway-stripe.php';
        require_once MITZIES_JERK_PATH . 'includes/payment-gateways/class-mitzies-jerk-gateway-paypal.php';

        // Email system.
        require_once MITZIES_JERK_PATH . 'includes/class-mitzies-jerk-emails.php';

        // Shortcodes.
        require_once MITZIES_JERK_PATH . 'includes/shortcodes/class-mitzies-jerk-shortcodes.php';

        // AJAX handlers.
        require_once MITZIES_JERK_PATH . 'includes/class-mitzies-jerk-ajax.php';

        // REST API.
        require_once MITZIES_JERK_PATH . 'includes/class-mitzies-jerk-rest-api.php';

        // Admin and public.
        require_once MITZIES_JERK_PATH . 'admin/class-mitzies-jerk-admin.php';
        require_once MITZIES_JERK_PATH . 'public/class-mitzies-jerk-public.php';

        // Cron jobs.
        require_once MITZIES_JERK_PATH . 'includes/class-mitzies-jerk-cron.php';

        $this->loader = new Mitzies_Jerk_Loader();

        // Initialize session.
        $this->session = new Mitzies_Jerk_Session();
        $this->loader->add_action( 'init', $this->session, 'init', 1 );

        // Initialize cart.
        $this->cart = new Mitzies_Jerk_Cart( $this->session );
        $this->loader->add_action( 'wp_loaded', $this->cart, 'init', 10 );
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new Mitzies_Jerk_i18n();
        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Mitzies_Jerk_Admin( $this->get_plugin_name(), $this->get_version() );

        // Admin styles and scripts.
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

        // Admin menus.
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );

        // Settings.
        $this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );

        // Meta boxes.
        $this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'add_meta_boxes' );
        $this->loader->add_action( 'save_post', $plugin_admin, 'save_meta_boxes', 10, 2 );

        // Admin columns.
        $this->loader->add_filter( 'manage_mj_food_item_posts_columns', $plugin_admin, 'food_item_columns' );
        $this->loader->add_action( 'manage_mj_food_item_posts_custom_column', $plugin_admin, 'food_item_column_data', 10, 2 );
        $this->loader->add_filter( 'manage_mj_order_posts_columns', $plugin_admin, 'order_columns' );
        $this->loader->add_action( 'manage_mj_order_posts_custom_column', $plugin_admin, 'order_column_data', 10, 2 );

        // Admin notices.
        $this->loader->add_action( 'admin_notices', $plugin_admin, 'admin_notices' );

        // Plugin action links.
        $this->loader->add_filter( 'plugin_action_links_' . MITZIES_JERK_BASENAME, $plugin_admin, 'add_action_links' );

        // Dashboard widget.
        $this->loader->add_action( 'wp_dashboard_setup', $plugin_admin, 'add_dashboard_widget' );
    }

    /**
     * Register all of the hooks related to the public-facing functionality.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new Mitzies_Jerk_Public( $this->get_plugin_name(), $this->get_version() );

        // Public styles and scripts.
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

        // Template includes.
        $this->loader->add_filter( 'template_include', $plugin_public, 'template_include' );

        // Body classes.
        $this->loader->add_filter( 'body_class', $plugin_public, 'body_class' );

        // Query vars.
        $this->loader->add_filter( 'query_vars', $plugin_public, 'add_query_vars' );

        // Rewrite rules.
        $this->loader->add_action( 'init', $plugin_public, 'add_rewrite_rules' );
    }

    /**
     * Register custom post types.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_post_types() {
        $post_types = new Mitzies_Jerk_Post_Types();
        $this->loader->add_action( 'init', $post_types, 'register_post_types' );
        $this->loader->add_action( 'init', $post_types, 'register_taxonomies' );
    }

    /**
     * Register shortcodes.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_shortcodes() {
        $shortcodes = new Mitzies_Jerk_Shortcodes();
        $this->loader->add_action( 'init', $shortcodes, 'register_shortcodes' );
    }

    /**
     * Register AJAX handlers.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_ajax_handlers() {
        $ajax = new Mitzies_Jerk_Ajax();

        // Cart AJAX.
        $this->loader->add_action( 'wp_ajax_mj_add_to_cart', $ajax, 'add_to_cart' );
        $this->loader->add_action( 'wp_ajax_nopriv_mj_add_to_cart', $ajax, 'add_to_cart' );
        $this->loader->add_action( 'wp_ajax_mj_update_cart', $ajax, 'update_cart' );
        $this->loader->add_action( 'wp_ajax_nopriv_mj_update_cart', $ajax, 'update_cart' );
        $this->loader->add_action( 'wp_ajax_mj_remove_from_cart', $ajax, 'remove_from_cart' );
        $this->loader->add_action( 'wp_ajax_nopriv_mj_remove_from_cart', $ajax, 'remove_from_cart' );
        $this->loader->add_action( 'wp_ajax_mj_get_cart', $ajax, 'get_cart' );
        $this->loader->add_action( 'wp_ajax_nopriv_mj_get_cart', $ajax, 'get_cart' );
        $this->loader->add_action( 'wp_ajax_mj_apply_coupon', $ajax, 'apply_coupon' );
        $this->loader->add_action( 'wp_ajax_nopriv_mj_apply_coupon', $ajax, 'apply_coupon' );

        // Checkout AJAX.
        $this->loader->add_action( 'wp_ajax_mj_process_checkout', $ajax, 'process_checkout' );
        $this->loader->add_action( 'wp_ajax_nopriv_mj_process_checkout', $ajax, 'process_checkout' );
        $this->loader->add_action( 'wp_ajax_mj_validate_delivery', $ajax, 'validate_delivery' );
        $this->loader->add_action( 'wp_ajax_nopriv_mj_validate_delivery', $ajax, 'validate_delivery' );

        // Payment callbacks.
        $this->loader->add_action( 'wp_ajax_mj_payment_callback', $ajax, 'payment_callback' );
        $this->loader->add_action( 'wp_ajax_nopriv_mj_payment_callback', $ajax, 'payment_callback' );

        // Order tracking.
        $this->loader->add_action( 'wp_ajax_mj_track_order', $ajax, 'track_order' );
        $this->loader->add_action( 'wp_ajax_nopriv_mj_track_order', $ajax, 'track_order' );

        // Food items.
        $this->loader->add_action( 'wp_ajax_mj_load_more_items', $ajax, 'load_more_items' );
        $this->loader->add_action( 'wp_ajax_nopriv_mj_load_more_items', $ajax, 'load_more_items' );
        $this->loader->add_action( 'wp_ajax_mj_filter_items', $ajax, 'filter_items' );
        $this->loader->add_action( 'wp_ajax_nopriv_mj_filter_items', $ajax, 'filter_items' );

        // Reviews.
        $this->loader->add_action( 'wp_ajax_mj_submit_review', $ajax, 'submit_review' );

        // Admin AJAX.
        $this->loader->add_action( 'wp_ajax_mj_update_order_status', $ajax, 'update_order_status' );
        $this->loader->add_action( 'wp_ajax_mj_get_dashboard_stats', $ajax, 'get_dashboard_stats' );
        $this->loader->add_action( 'wp_ajax_mj_send_test_email', $ajax, 'send_test_email' );
        $this->loader->add_action( 'wp_ajax_mj_export_orders', $ajax, 'export_orders' );
    }

    /**
     * Register REST API endpoints.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_rest_api() {
        $rest_api = new Mitzies_Jerk_Rest_Api();
        $this->loader->add_action( 'rest_api_init', $rest_api, 'register_routes' );
    }

    /**
     * Register Elementor hooks and widgets.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_elementor_hooks() {
        // Only load if Elementor is active.
        $this->loader->add_action( 'elementor/widgets/register', $this, 'register_elementor_widgets' );
        $this->loader->add_action( 'elementor/elements/categories_registered', $this, 'add_elementor_category' );
    }

    /**
     * Register Elementor widgets.
     *
     * @since    1.0.0
     * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
     */
    public function register_elementor_widgets( $widgets_manager ) {
        if ( ! class_exists( '\Elementor\Plugin' ) ) {
            return;
        }

        require_once MITZIES_JERK_PATH . 'elementor/widgets/class-mj-food-menu-widget.php';
        require_once MITZIES_JERK_PATH . 'elementor/widgets/class-mj-food-categories-widget.php';
        require_once MITZIES_JERK_PATH . 'elementor/widgets/class-mj-cart-widget.php';
        require_once MITZIES_JERK_PATH . 'elementor/widgets/class-mj-checkout-widget.php';
        require_once MITZIES_JERK_PATH . 'elementor/widgets/class-mj-order-tracking-widget.php';

        $widgets_manager->register( new MJ_Food_Menu_Widget() );
        $widgets_manager->register( new MJ_Food_Categories_Widget() );
        $widgets_manager->register( new MJ_Cart_Widget() );
        $widgets_manager->register( new MJ_Checkout_Widget() );
        $widgets_manager->register( new MJ_Order_Tracking_Widget() );
    }

    /**
     * Add Elementor widget category.
     *
     * @since    1.0.0
     * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager.
     */
    public function add_elementor_category( $elements_manager ) {
        $elements_manager->add_category(
            'mitzies-jerk',
            array(
                'title' => __( 'Mitzies Jerk', 'mitzies-jerk' ),
                'icon'  => 'fa fa-utensils',
            )
        );
    }

    /**
     * Define cron jobs.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_cron_jobs() {
        $cron = new Mitzies_Jerk_Cron();
        $this->loader->add_action( 'mj_check_expired_orders', $cron, 'check_expired_orders' );
        $this->loader->add_action( 'mj_cleanup_sessions', $cron, 'cleanup_sessions' );
        $this->loader->add_action( 'mj_send_reminder_emails', $cron, 'send_reminder_emails' );
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it.
     *
     * @since    1.0.0
     * @return   string The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since    1.0.0
     * @return   Mitzies_Jerk_Loader Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since    1.0.0
     * @return   string The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}
