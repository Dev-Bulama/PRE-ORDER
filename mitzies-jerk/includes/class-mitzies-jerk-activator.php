<?php
/**
 * Fired during plugin activation.
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
 * Fired during plugin activation.
 */
class Mitzies_Jerk_Activator {

    /**
     * Run activation tasks.
     *
     * @since    1.0.0
     */
    public static function activate() {
        self::create_tables();
        self::create_pages();
        self::set_default_options();
        self::setup_roles_and_capabilities();
        self::schedule_cron_events();

        // Flush rewrite rules.
        flush_rewrite_rules();

        // Set activation flag.
        update_option( 'mitzies_jerk_activated', true );
        update_option( 'mitzies_jerk_version', MITZIES_JERK_VERSION );
    }

    /**
     * Create custom database tables.
     *
     * @since    1.0.0
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix . MITZIES_JERK_TABLE_PREFIX;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Order items table.
        $sql_order_items = "CREATE TABLE IF NOT EXISTS {$prefix}order_items (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id bigint(20) UNSIGNED NOT NULL,
            food_item_id bigint(20) UNSIGNED NOT NULL,
            quantity int(11) NOT NULL DEFAULT 1,
            price decimal(10,2) NOT NULL,
            subtotal decimal(10,2) NOT NULL,
            addons longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY food_item_id (food_item_id)
        ) $charset_collate;";

        dbDelta( $sql_order_items );

        // Sessions table.
        $sql_sessions = "CREATE TABLE IF NOT EXISTS {$prefix}sessions (
            session_id varchar(255) NOT NULL,
            session_key varchar(255) NOT NULL,
            session_value longtext NOT NULL,
            session_expiry bigint(20) UNSIGNED NOT NULL,
            PRIMARY KEY (session_id),
            KEY session_key (session_key),
            KEY session_expiry (session_expiry)
        ) $charset_collate;";

        dbDelta( $sql_sessions );

        // Coupons table.
        $sql_coupons = "CREATE TABLE IF NOT EXISTS {$prefix}coupons (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            code varchar(100) NOT NULL,
            type enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
            amount decimal(10,2) NOT NULL,
            minimum_order decimal(10,2) DEFAULT 0,
            maximum_discount decimal(10,2) DEFAULT NULL,
            usage_limit int(11) DEFAULT NULL,
            usage_count int(11) DEFAULT 0,
            start_date datetime DEFAULT NULL,
            end_date datetime DEFAULT NULL,
            status enum('active','inactive','expired') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY code (code),
            KEY status (status)
        ) $charset_collate;";

        dbDelta( $sql_coupons );

        // Reviews table.
        $sql_reviews = "CREATE TABLE IF NOT EXISTS {$prefix}reviews (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            food_item_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            order_id bigint(20) UNSIGNED DEFAULT NULL,
            rating tinyint(1) NOT NULL,
            review_text text,
            status enum('pending','approved','rejected') DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY food_item_id (food_item_id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";

        dbDelta( $sql_reviews );

        // Delivery zones table.
        $sql_delivery_zones = "CREATE TABLE IF NOT EXISTS {$prefix}delivery_zones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            zone_name varchar(255) NOT NULL,
            zone_type enum('postcode','city','region') NOT NULL,
            zone_values longtext,
            delivery_fee decimal(10,2) NOT NULL DEFAULT 0,
            min_order decimal(10,2) DEFAULT 0,
            estimated_time varchar(100) DEFAULT NULL,
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status)
        ) $charset_collate;";

        dbDelta( $sql_delivery_zones );

        // Payment logs table.
        $sql_payment_logs = "CREATE TABLE IF NOT EXISTS {$prefix}payment_logs (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id bigint(20) UNSIGNED NOT NULL,
            gateway varchar(50) NOT NULL,
            transaction_id varchar(255) DEFAULT NULL,
            amount decimal(10,2) NOT NULL,
            currency varchar(10) DEFAULT 'USD',
            status varchar(50) NOT NULL,
            response_data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY transaction_id (transaction_id),
            KEY gateway (gateway)
        ) $charset_collate;";

        dbDelta( $sql_payment_logs );

        // Food addons table.
        $sql_addons = "CREATE TABLE IF NOT EXISTS {$prefix}addons (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            food_item_id bigint(20) UNSIGNED NOT NULL,
            addon_name varchar(255) NOT NULL,
            addon_price decimal(10,2) NOT NULL DEFAULT 0,
            addon_type enum('checkbox','radio','select') DEFAULT 'checkbox',
            addon_group varchar(100) DEFAULT NULL,
            max_quantity int(11) DEFAULT 1,
            status enum('active','inactive') DEFAULT 'active',
            sort_order int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY food_item_id (food_item_id),
            KEY status (status)
        ) $charset_collate;";

        dbDelta( $sql_addons );

        // Distance-based delivery rates table.
        $sql_distance_rates = "CREATE TABLE IF NOT EXISTS {$prefix}distance_rates (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            min_distance decimal(10,2) NOT NULL DEFAULT 0,
            max_distance decimal(10,2) NOT NULL DEFAULT 0,
            delivery_fee decimal(10,2) NOT NULL DEFAULT 0,
            estimated_time varchar(100) DEFAULT NULL,
            status enum('active','inactive') DEFAULT 'active',
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status)
        ) $charset_collate;";

        dbDelta( $sql_distance_rates );

        // Delivery methods table.
        $sql_delivery_methods = "CREATE TABLE IF NOT EXISTS {$prefix}delivery_methods (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            method_name varchar(255) NOT NULL,
            method_type enum('delivery','pickup') NOT NULL DEFAULT 'delivery',
            description text,
            base_fee decimal(10,2) NOT NULL DEFAULT 0,
            extra_fee decimal(10,2) NOT NULL DEFAULT 0,
            estimated_time varchar(100) DEFAULT NULL,
            is_distance_based tinyint(1) DEFAULT 0,
            status enum('active','inactive') DEFAULT 'active',
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY method_type (method_type),
            KEY status (status)
        ) $charset_collate;";

        dbDelta( $sql_delivery_methods );

        // Pickup locations table.
        $sql_pickup_locations = "CREATE TABLE IF NOT EXISTS {$prefix}pickup_locations (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            location_name varchar(255) NOT NULL,
            address text NOT NULL,
            city varchar(100) DEFAULT NULL,
            state varchar(100) DEFAULT NULL,
            postcode varchar(20) DEFAULT NULL,
            latitude decimal(10,8) DEFAULT NULL,
            longitude decimal(11,8) DEFAULT NULL,
            availability_hours text,
            phone varchar(50) DEFAULT NULL,
            status enum('active','inactive') DEFAULT 'active',
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status)
        ) $charset_collate;";

        dbDelta( $sql_pickup_locations );

        // Order tracking history table.
        $sql_order_tracking = "CREATE TABLE IF NOT EXISTS {$prefix}order_tracking (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id bigint(20) UNSIGNED NOT NULL,
            status varchar(50) NOT NULL,
            note text,
            location varchar(255) DEFAULT NULL,
            created_by bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY status (status)
        ) $charset_collate;";

        dbDelta( $sql_order_tracking );

        // Update database version.
        update_option( 'mitzies_jerk_db_version', '1.1.0' );
    }

    /**
     * Create required pages.
     *
     * @since    1.0.0
     */
    private static function create_pages() {
        $pages = array(
            'menu'           => array(
                'title'   => __( 'Menu', 'mitzies-jerk' ),
                'content' => '[food_menu]',
                'option'  => 'mitzies_jerk_menu_page_id',
            ),
            'cart'           => array(
                'title'   => __( 'Cart', 'mitzies-jerk' ),
                'content' => '[food_cart]',
                'option'  => 'mitzies_jerk_cart_page_id',
            ),
            'checkout'       => array(
                'title'   => __( 'Checkout', 'mitzies-jerk' ),
                'content' => '[food_checkout]',
                'option'  => 'mitzies_jerk_checkout_page_id',
            ),
            'order-received' => array(
                'title'   => __( 'Order Received', 'mitzies-jerk' ),
                'content' => '[food_order_received]',
                'option'  => 'mitzies_jerk_order_received_page_id',
            ),
            'order-tracking' => array(
                'title'   => __( 'Track Your Order', 'mitzies-jerk' ),
                'content' => '[food_order_tracking]',
                'option'  => 'mitzies_jerk_order_tracking_page_id',
            ),
            'my-account'     => array(
                'title'   => __( 'My Account', 'mitzies-jerk' ),
                'content' => '[food_my_account]',
                'option'  => 'mitzies_jerk_my_account_page_id',
            ),
        );

        foreach ( $pages as $slug => $page_data ) {
            // Check if page already exists.
            $existing_page_id = get_option( $page_data['option'] );

            if ( $existing_page_id && get_post( $existing_page_id ) ) {
                continue;
            }

            // Check if page with this slug exists.
            $existing_page = get_page_by_path( $slug );

            if ( $existing_page ) {
                update_option( $page_data['option'], $existing_page->ID );
                continue;
            }

            // Create the page.
            $page_id = wp_insert_post(
                array(
                    'post_title'     => $page_data['title'],
                    'post_content'   => $page_data['content'],
                    'post_status'    => 'publish',
                    'post_type'      => 'page',
                    'post_name'      => $slug,
                    'comment_status' => 'closed',
                )
            );

            if ( $page_id && ! is_wp_error( $page_id ) ) {
                update_option( $page_data['option'], $page_id );
            }
        }
    }

    /**
     * Set default plugin options.
     *
     * @since    1.0.0
     */
    private static function set_default_options() {
        $default_options = array(
            // General settings.
            'currency'               => 'USD',
            'currency_symbol'        => '$',
            'currency_position'      => 'left',
            'decimal_places'         => 2,
            'thousands_separator'    => ',',
            'decimal_separator'      => '.',

            // Pre-order settings.
            'min_preorder_hours'     => 24,
            'max_preorder_days'      => 30,
            'payment_expiry_minutes' => 30,
            'max_orders_per_day'     => 100,

            // Delivery settings.
            'delivery_days'          => array( 0, 1, 2, 3, 4, 5, 6 ),
            'delivery_time_slots'    => array(
                array( 'start' => '09:00', 'end' => '12:00' ),
                array( 'start' => '12:00', 'end' => '15:00' ),
                array( 'start' => '15:00', 'end' => '18:00' ),
                array( 'start' => '18:00', 'end' => '21:00' ),
            ),
            'delivery_fee'           => 5.00,
            'free_delivery_threshold'=> 50.00,

            // Tax settings.
            'enable_tax'             => false,
            'tax_rate'               => 0,
            'tax_inclusive'          => false,

            // Payment gateways.
            'enabled_gateways'       => array(),

            // Email settings.
            'admin_email'            => get_option( 'admin_email' ),
            'email_from_name'        => get_bloginfo( 'name' ),
            'email_from_address'     => get_option( 'admin_email' ),

            // Display settings.
            'items_per_page'         => 12,
            'enable_guest_checkout'  => true,
            'enable_reviews'         => true,
            'review_approval'        => true,

            // Order settings.
            'order_auto_approve'     => false,
            'show_addons_on_thumbnail' => true,

            // Delivery methods.
            'enable_distance_rates'  => false,
            'google_maps_api_key'    => '',
            'store_latitude'         => '',
            'store_longitude'        => '',
            'store_address'          => '',
            'distance_unit'          => 'km',

            // Advanced settings.
            'enable_logging'         => false,
            'delete_data_on_uninstall' => false,
        );

        $existing_options = get_option( 'mitzies_jerk_settings', array() );
        $merged_options = wp_parse_args( $existing_options, $default_options );

        update_option( 'mitzies_jerk_settings', $merged_options );
    }

    /**
     * Setup roles and capabilities.
     *
     * @since    1.0.0
     */
    private static function setup_roles_and_capabilities() {
        // Add custom role for restaurant staff.
        add_role(
            'mj_restaurant_staff',
            __( 'Restaurant Staff', 'mitzies-jerk' ),
            array(
                'read'                          => true,
                'edit_mj_orders'                => true,
                'edit_others_mj_orders'         => true,
                'read_mj_orders'                => true,
                'delete_mj_orders'              => false,
                'edit_mj_food_items'            => true,
                'edit_others_mj_food_items'     => true,
                'edit_published_mj_food_items'  => true,
                'read_mj_food_items'            => true,
                'delete_mj_food_items'          => false,
            )
        );

        // Add custom role for restaurant manager.
        add_role(
            'mj_restaurant_manager',
            __( 'Restaurant Manager', 'mitzies-jerk' ),
            array(
                'read'                           => true,
                'edit_mj_orders'                 => true,
                'edit_others_mj_orders'          => true,
                'edit_published_mj_orders'       => true,
                'read_mj_orders'                 => true,
                'delete_mj_orders'               => true,
                'delete_others_mj_orders'        => true,
                'delete_published_mj_orders'     => true,
                'edit_mj_food_items'             => true,
                'edit_others_mj_food_items'      => true,
                'edit_published_mj_food_items'   => true,
                'edit_private_mj_food_items'     => true,
                'publish_mj_food_items'          => true,
                'read_mj_food_items'             => true,
                'read_private_mj_food_items'     => true,
                'delete_mj_food_items'           => true,
                'delete_others_mj_food_items'    => true,
                'delete_published_mj_food_items' => true,
                'delete_private_mj_food_items'   => true,
                'manage_mj_food_categories'      => true,
                'manage_mj_settings'             => true,
            )
        );

        // Add capabilities to administrator - all capabilities for food items and orders.
        self::add_admin_capabilities();
    }

    /**
     * Add all required capabilities to administrator role.
     *
     * @since    1.0.0
     */
    public static function add_admin_capabilities() {
        $admin_role = get_role( 'administrator' );

        if ( ! $admin_role ) {
            return;
        }

        // Complete list of capabilities for custom post types.
        $capabilities = array(
            // Food item capabilities.
            'edit_mj_food_item',
            'read_mj_food_item',
            'delete_mj_food_item',
            'edit_mj_food_items',
            'edit_others_mj_food_items',
            'edit_published_mj_food_items',
            'edit_private_mj_food_items',
            'publish_mj_food_items',
            'read_mj_food_items',
            'read_private_mj_food_items',
            'delete_mj_food_items',
            'delete_others_mj_food_items',
            'delete_published_mj_food_items',
            'delete_private_mj_food_items',
            // Order capabilities.
            'edit_mj_order',
            'read_mj_order',
            'delete_mj_order',
            'edit_mj_orders',
            'edit_others_mj_orders',
            'edit_published_mj_orders',
            'edit_private_mj_orders',
            'publish_mj_orders',
            'read_mj_orders',
            'read_private_mj_orders',
            'delete_mj_orders',
            'delete_others_mj_orders',
            'delete_published_mj_orders',
            'delete_private_mj_orders',
            // Taxonomy capabilities.
            'manage_mj_food_categories',
            'edit_mj_food_categories',
            'delete_mj_food_categories',
            'assign_mj_food_categories',
            // Plugin-specific capabilities.
            'manage_mj_settings',
            'manage_mj_coupons',
            'view_mj_reports',
        );

        foreach ( $capabilities as $cap ) {
            if ( ! $admin_role->has_cap( $cap ) ) {
                $admin_role->add_cap( $cap );
            }
        }
    }

    /**
     * Schedule cron events.
     *
     * @since    1.0.0
     */
    private static function schedule_cron_events() {
        // Add custom cron interval first, before scheduling events.
        add_filter( 'cron_schedules', array( __CLASS__, 'add_cron_schedules' ) );

        // Check for expired orders every 5 minutes.
        if ( ! wp_next_scheduled( 'mj_check_expired_orders' ) ) {
            wp_schedule_event( time(), 'five_minutes', 'mj_check_expired_orders' );
        }

        // Cleanup old sessions daily.
        if ( ! wp_next_scheduled( 'mj_cleanup_sessions' ) ) {
            wp_schedule_event( time(), 'daily', 'mj_cleanup_sessions' );
        }

        // Send reminder emails every hour.
        if ( ! wp_next_scheduled( 'mj_send_reminder_emails' ) ) {
            wp_schedule_event( time(), 'hourly', 'mj_send_reminder_emails' );
        }
    }

    /**
     * Add custom cron schedules.
     *
     * @since    1.0.0
     * @param array $schedules Existing schedules.
     * @return array Modified schedules.
     */
    public static function add_cron_schedules( $schedules ) {
        $schedules['five_minutes'] = array(
            'interval' => 300,
            'display'  => __( 'Every 5 Minutes', 'mitzies-jerk' ),
        );
        return $schedules;
    }
}
