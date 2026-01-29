<?php
/**
 * Elementor Integration
 *
 * @package    Mitzies_Jerk
 * @subpackage Mitzies_Jerk/includes/elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Elementor Integration Class
 */
class Mitzies_Jerk_Elementor {

    /**
     * Instance
     *
     * @var Mitzies_Jerk_Elementor
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return Mitzies_Jerk_Elementor
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        // Register category early with high priority.
        add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ), 5 );
        // Register widgets after category is set up.
        add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ), 10 );
        add_action( 'elementor/frontend/after_enqueue_styles', array( $this, 'enqueue_styles' ) );
        // Also try on init in case Elementor already loaded.
        add_action( 'elementor/init', array( $this, 'on_elementor_init' ) );
    }

    /**
     * On Elementor init - ensure category is registered.
     */
    public function on_elementor_init() {
        // Register category if Elementor is already initialized.
        if ( did_action( 'elementor/elements/categories_registered' ) ) {
            $elements_manager = \Elementor\Plugin::instance()->elements_manager;
            if ( $elements_manager ) {
                $this->register_category( $elements_manager );
            }
        }
    }

    /**
     * Register widget category
     *
     * @param \Elementor\Elements_Manager $elements_manager Elements manager.
     */
    public function register_category( $elements_manager ) {
        $elements_manager->add_category(
            'mitzies-jerk',
            array(
                'title' => __( 'Mitzies Jerk', 'mitzies-jerk' ),
                'icon'  => 'fa fa-utensils',
            )
        );
    }

    /**
     * Register widgets
     *
     * @param \Elementor\Widgets_Manager $widgets_manager Widgets manager.
     */
    public function register_widgets( $widgets_manager ) {
        require_once plugin_dir_path( __FILE__ ) . 'widgets/class-widget-food-menu.php';
        require_once plugin_dir_path( __FILE__ ) . 'widgets/class-widget-food-item.php';
        require_once plugin_dir_path( __FILE__ ) . 'widgets/class-widget-cart.php';
        require_once plugin_dir_path( __FILE__ ) . 'widgets/class-widget-checkout.php';
        require_once plugin_dir_path( __FILE__ ) . 'widgets/class-widget-categories.php';
        require_once plugin_dir_path( __FILE__ ) . 'widgets/class-widget-order-history.php';

        $widgets_manager->register( new Mitzies_Jerk_Widget_Food_Menu() );
        $widgets_manager->register( new Mitzies_Jerk_Widget_Food_Item() );
        $widgets_manager->register( new Mitzies_Jerk_Widget_Cart() );
        $widgets_manager->register( new Mitzies_Jerk_Widget_Checkout() );
        $widgets_manager->register( new Mitzies_Jerk_Widget_Categories() );
        $widgets_manager->register( new Mitzies_Jerk_Widget_Order_History() );
    }

    /**
     * Enqueue styles for Elementor
     */
    public function enqueue_styles() {
        wp_enqueue_style( 'mitzies-jerk-public' );
    }
}
