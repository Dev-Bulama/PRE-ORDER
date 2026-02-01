<?php
/**
 * Admin-specific functionality.
 *
 * @package    Mitzies_Jerk
 * @subpackage Mitzies_Jerk/admin
 * @author     SkillScore IT Solutions and Training, Tijani Bulama
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin class.
 */
class Mitzies_Jerk_Admin {

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
     * Enqueue admin styles.
     *
     * @param string $hook Current admin page.
     */
    public function enqueue_styles( $hook ) {
        wp_enqueue_style(
            $this->plugin_name,
            MITZIES_JERK_URL . 'admin/css/mitzies-jerk-admin.css',
            array(),
            $this->version,
            'all'
        );

        // Enqueue on plugin pages.
        if ( $this->is_plugin_page( $hook ) ) {
            wp_enqueue_style( 'wp-color-picker' );
        }
    }

    /**
     * Enqueue admin scripts.
     *
     * @param string $hook Current admin page.
     */
    public function enqueue_scripts( $hook ) {
        wp_enqueue_script(
            $this->plugin_name,
            MITZIES_JERK_URL . 'admin/js/mitzies-jerk-admin.js',
            array( 'jquery', 'wp-color-picker' ),
            $this->version,
            true
        );

        wp_localize_script( $this->plugin_name, 'mitziesJerkAdmin', array(
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'mj_admin_nonce' ),
            'strings'   => array(
                'confirmDelete'  => __( 'Are you sure you want to delete this?', 'mitzies-jerk' ),
                'confirmCancel'  => __( 'Are you sure you want to cancel this order?', 'mitzies-jerk' ),
                'saving'         => __( 'Saving...', 'mitzies-jerk' ),
                'saved'          => __( 'Saved!', 'mitzies-jerk' ),
                'error'          => __( 'An error occurred.', 'mitzies-jerk' ),
            ),
        ) );

        // Chart.js for dashboard.
        if ( $this->is_plugin_page( $hook ) ) {
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
                array(),
                '4.4.0',
                true
            );
            wp_enqueue_media();
        }
    }

    /**
     * Check if current page is a plugin page.
     *
     * @param string $hook Current admin page.
     * @return bool
     */
    private function is_plugin_page( $hook ) {
        $plugin_pages = array(
            'toplevel_page_mitzies-jerk',
            'mitzies-jerk_page_mj-orders',
            'mitzies-jerk_page_mj-food-items',
            'mitzies-jerk_page_mj-categories',
            'mitzies-jerk_page_mj-coupons',
            'mitzies-jerk_page_mj-settings',
            'mitzies-jerk_page_mj-reports',
        );

        return in_array( $hook, $plugin_pages, true ) ||
               'post.php' === $hook ||
               'post-new.php' === $hook;
    }

    /**
     * Add admin menu.
     */
    public function add_admin_menu() {
        // Main menu.
        add_menu_page(
            __( 'Mitzies Jerk', 'mitzies-jerk' ),
            __( 'Mitzies Jerk', 'mitzies-jerk' ),
            'manage_options',
            'mitzies-jerk',
            array( $this, 'render_dashboard_page' ),
            'dashicons-carrot',
            26
        );

        // Dashboard submenu.
        add_submenu_page(
            'mitzies-jerk',
            __( 'Dashboard', 'mitzies-jerk' ),
            __( 'Dashboard', 'mitzies-jerk' ),
            'manage_options',
            'mitzies-jerk',
            array( $this, 'render_dashboard_page' )
        );

        // Orders submenu.
        add_submenu_page(
            'mitzies-jerk',
            __( 'Orders', 'mitzies-jerk' ),
            __( 'Orders', 'mitzies-jerk' ),
            'edit_mj_orders',
            'edit.php?post_type=mj_order'
        );

        // Food Items submenu.
        add_submenu_page(
            'mitzies-jerk',
            __( 'Food Items', 'mitzies-jerk' ),
            __( 'Food Items', 'mitzies-jerk' ),
            'edit_mj_food_items',
            'edit.php?post_type=mj_food_item'
        );

        // Add New Food Item.
        add_submenu_page(
            'mitzies-jerk',
            __( 'Add New Food Item', 'mitzies-jerk' ),
            __( 'Add New Food', 'mitzies-jerk' ),
            'edit_mj_food_items',
            'post-new.php?post_type=mj_food_item'
        );

        // Categories submenu.
        add_submenu_page(
            'mitzies-jerk',
            __( 'Categories', 'mitzies-jerk' ),
            __( 'Categories', 'mitzies-jerk' ),
            'manage_mj_food_categories',
            'edit-tags.php?taxonomy=mj_food_category&post_type=mj_food_item'
        );

        // Coupons submenu.
        add_submenu_page(
            'mitzies-jerk',
            __( 'Coupons', 'mitzies-jerk' ),
            __( 'Coupons', 'mitzies-jerk' ),
            'manage_mj_coupons',
            'mj-coupons',
            array( $this, 'render_coupons_page' )
        );

        // Reports submenu.
        add_submenu_page(
            'mitzies-jerk',
            __( 'Reports', 'mitzies-jerk' ),
            __( 'Reports', 'mitzies-jerk' ),
            'view_mj_reports',
            'mj-reports',
            array( $this, 'render_reports_page' )
        );

        // Settings submenu.
        add_submenu_page(
            'mitzies-jerk',
            __( 'Settings', 'mitzies-jerk' ),
            __( 'Settings', 'mitzies-jerk' ),
            'manage_mj_settings',
            'mj-settings',
            array( $this, 'render_settings_page' )
        );

        // Documentation submenu.
        add_submenu_page(
            'mitzies-jerk',
            __( 'Help & Documentation', 'mitzies-jerk' ),
            __( 'Help & Docs', 'mitzies-jerk' ),
            'manage_options',
            'mj-documentation',
            array( $this, 'render_documentation_page' )
        );

        // Diagnostic submenu.
        add_submenu_page(
            'mitzies-jerk',
            __( 'Diagnostics', 'mitzies-jerk' ),
            __( 'Diagnostics', 'mitzies-jerk' ),
            'manage_options',
            'mj-diagnostics',
            array( $this, 'render_diagnostic_page' )
        );
    }

    /**
     * Render dashboard page.
     */
    public function render_dashboard_page() {
        include MITZIES_JERK_PATH . 'admin/partials/dashboard.php';
    }

    /**
     * Render documentation page.
     */
    public function render_documentation_page() {
        include MITZIES_JERK_PATH . 'admin/partials/documentation.php';
    }

    /**
     * Render diagnostic page.
     */
    public function render_diagnostic_page() {
        include MITZIES_JERK_PATH . 'admin/partials/diagnostic.php';
    }

    /**
     * Render coupons page.
     */
    public function render_coupons_page() {
        include MITZIES_JERK_PATH . 'admin/partials/coupons.php';
    }

    /**
     * Render reports page.
     */
    public function render_reports_page() {
        include MITZIES_JERK_PATH . 'admin/partials/reports.php';
    }

    /**
     * Render settings page.
     */
    public function render_settings_page() {
        include MITZIES_JERK_PATH . 'admin/partials/settings.php';
    }

    /**
     * Register settings.
     */
    public function register_settings() {
        register_setting(
            'mitzies_jerk_settings',
            'mitzies_jerk_settings',
            array( $this, 'sanitize_settings' )
        );

        // Handle page options on form submission.
        if ( isset( $_POST['mitzies_jerk_menu_page_id'] ) ) {
            update_option( 'mitzies_jerk_menu_page_id', absint( $_POST['mitzies_jerk_menu_page_id'] ) );
        }
        if ( isset( $_POST['mitzies_jerk_cart_page_id'] ) ) {
            update_option( 'mitzies_jerk_cart_page_id', absint( $_POST['mitzies_jerk_cart_page_id'] ) );
        }
        if ( isset( $_POST['mitzies_jerk_checkout_page_id'] ) ) {
            update_option( 'mitzies_jerk_checkout_page_id', absint( $_POST['mitzies_jerk_checkout_page_id'] ) );
        }

        // General settings section.
        add_settings_section(
            'mj_general_section',
            __( 'General Settings', 'mitzies-jerk' ),
            array( $this, 'render_section_general' ),
            'mj-settings-general'
        );

        // Pre-order settings section.
        add_settings_section(
            'mj_preorder_section',
            __( 'Pre-Order Settings', 'mitzies-jerk' ),
            array( $this, 'render_section_preorder' ),
            'mj-settings-preorder'
        );

        // Payment settings section.
        add_settings_section(
            'mj_payment_section',
            __( 'Payment Settings', 'mitzies-jerk' ),
            array( $this, 'render_section_payment' ),
            'mj-settings-payment'
        );

        // Email settings section.
        add_settings_section(
            'mj_email_section',
            __( 'Email Settings', 'mitzies-jerk' ),
            array( $this, 'render_section_email' ),
            'mj-settings-email'
        );
    }

    /**
     * Sanitize settings.
     *
     * @param array $input Input values.
     * @return array Sanitized values.
     */
    public function sanitize_settings( $input ) {
        // Get existing settings to preserve values from other tabs.
        $existing = get_option( 'mitzies_jerk_settings', array() );
        $sanitized = array();

        // General settings.
        $sanitized['currency'] = sanitize_text_field( $input['currency'] ?? 'USD' );
        $sanitized['currency_symbol'] = sanitize_text_field( $input['currency_symbol'] ?? '$' );
        $sanitized['currency_position'] = sanitize_text_field( $input['currency_position'] ?? 'left' );
        $sanitized['decimal_places'] = absint( $input['decimal_places'] ?? 2 );
        $sanitized['thousands_separator'] = sanitize_text_field( $input['thousands_separator'] ?? ',' );
        $sanitized['decimal_separator'] = sanitize_text_field( $input['decimal_separator'] ?? '.' );

        // Pre-order settings.
        $sanitized['min_preorder_hours'] = absint( $input['min_preorder_hours'] ?? 24 );
        $sanitized['max_preorder_days'] = absint( $input['max_preorder_days'] ?? 30 );
        $sanitized['payment_expiry_minutes'] = absint( $input['payment_expiry_minutes'] ?? 30 );
        $sanitized['max_orders_per_day'] = absint( $input['max_orders_per_day'] ?? 100 );

        // Delivery settings.
        $sanitized['delivery_days'] = isset( $input['delivery_days'] ) ? array_map( 'absint', $input['delivery_days'] ) : array();
        $sanitized['delivery_fee'] = floatval( $input['delivery_fee'] ?? 0 );
        $sanitized['free_delivery_threshold'] = floatval( $input['free_delivery_threshold'] ?? 0 );

        // Delivery time slots.
        if ( isset( $input['delivery_time_slots'] ) && is_array( $input['delivery_time_slots'] ) ) {
            $sanitized['delivery_time_slots'] = array();
            foreach ( $input['delivery_time_slots'] as $slot ) {
                if ( ! empty( $slot['start'] ) && ! empty( $slot['end'] ) ) {
                    $sanitized['delivery_time_slots'][] = array(
                        'start' => sanitize_text_field( $slot['start'] ),
                        'end'   => sanitize_text_field( $slot['end'] ),
                    );
                }
            }
        }

        // Tax settings.
        $sanitized['enable_tax'] = ! empty( $input['enable_tax'] );
        $sanitized['tax_rate'] = floatval( $input['tax_rate'] ?? 0 );
        $sanitized['tax_inclusive'] = ! empty( $input['tax_inclusive'] );

        // Payment gateways.
        $sanitized['enabled_gateways'] = isset( $input['enabled_gateways'] ) ? array_map( 'sanitize_text_field', $input['enabled_gateways'] ) : array();

        // Payment gateway specific settings (all gateways including COD and Bank Transfer).
        $gateways = array( 'cod', 'bank_transfer', 'paystack', 'flutterwave', 'stripe', 'paypal' );
        foreach ( $gateways as $gateway ) {
            $prefix = $gateway . '_';
            foreach ( $input as $key => $value ) {
                if ( strpos( $key, $prefix ) === 0 ) {
                    // Handle textarea fields differently
                    if ( is_string( $value ) && strpos( $value, "\n" ) !== false ) {
                        $sanitized[ $key ] = sanitize_textarea_field( $value );
                    } else {
                        $sanitized[ $key ] = sanitize_text_field( $value );
                    }
                }
            }
        }

        // Email settings.
        $sanitized['admin_email'] = sanitize_email( $input['admin_email'] ?? get_option( 'admin_email' ) );
        $sanitized['email_from_name'] = sanitize_text_field( $input['email_from_name'] ?? get_bloginfo( 'name' ) );
        $sanitized['email_from_address'] = sanitize_email( $input['email_from_address'] ?? get_option( 'admin_email' ) );

        // Email appearance.
        $sanitized['email_header_bg_color'] = sanitize_hex_color( $input['email_header_bg_color'] ?? '#e74c3c' );
        $sanitized['email_header_text_color'] = sanitize_hex_color( $input['email_header_text_color'] ?? '#ffffff' );
        $sanitized['email_body_bg_color'] = sanitize_hex_color( $input['email_body_bg_color'] ?? '#f5f5f5' );
        $sanitized['email_body_text_color'] = sanitize_hex_color( $input['email_body_text_color'] ?? '#333333' );
        $sanitized['email_footer_text'] = sanitize_text_field( $input['email_footer_text'] ?? '' );

        // Email templates - New Order (Admin).
        $sanitized['email_new_order_subject'] = sanitize_text_field( $input['email_new_order_subject'] ?? '' );
        $sanitized['email_new_order_heading'] = sanitize_text_field( $input['email_new_order_heading'] ?? '' );
        $sanitized['email_new_order_body'] = sanitize_textarea_field( $input['email_new_order_body'] ?? '' );

        // Email templates - Order Confirmation (Customer).
        $sanitized['email_order_confirmation_subject'] = sanitize_text_field( $input['email_order_confirmation_subject'] ?? '' );
        $sanitized['email_order_confirmation_heading'] = sanitize_text_field( $input['email_order_confirmation_heading'] ?? '' );
        $sanitized['email_order_confirmation_body'] = sanitize_textarea_field( $input['email_order_confirmation_body'] ?? '' );

        // Email templates - Order Status Update (Customer).
        $sanitized['email_order_status_subject'] = sanitize_text_field( $input['email_order_status_subject'] ?? '' );
        $sanitized['email_order_status_heading'] = sanitize_text_field( $input['email_order_status_heading'] ?? '' );
        $sanitized['email_order_status_body'] = sanitize_textarea_field( $input['email_order_status_body'] ?? '' );

        // Email templates - Order Ready (Customer).
        $sanitized['email_order_ready_subject'] = sanitize_text_field( $input['email_order_ready_subject'] ?? '' );
        $sanitized['email_order_ready_heading'] = sanitize_text_field( $input['email_order_ready_heading'] ?? '' );
        $sanitized['email_order_ready_body'] = sanitize_textarea_field( $input['email_order_ready_body'] ?? '' );

        // Display settings.
        $sanitized['items_per_page'] = absint( $input['items_per_page'] ?? 12 );
        $sanitized['enable_guest_checkout'] = ! empty( $input['enable_guest_checkout'] );
        $sanitized['enable_reviews'] = ! empty( $input['enable_reviews'] );
        $sanitized['review_approval'] = ! empty( $input['review_approval'] );

        // Advanced settings.
        $sanitized['enable_logging'] = ! empty( $input['enable_logging'] );
        $sanitized['delete_data_on_uninstall'] = ! empty( $input['delete_data_on_uninstall'] );

        // Merge with existing settings to preserve values from other tabs.
        // Only update values that were actually submitted in the form.
        foreach ( $existing as $key => $value ) {
            if ( ! isset( $sanitized[ $key ] ) ) {
                $sanitized[ $key ] = $value;
            }
        }

        // Special handling for arrays that might not be submitted (like checkboxes and time slots).
        // Preserve delivery_time_slots if not in this submission but exists.
        if ( ! isset( $input['delivery_time_slots'] ) && isset( $existing['delivery_time_slots'] ) ) {
            $sanitized['delivery_time_slots'] = $existing['delivery_time_slots'];
        }

        // Preserve delivery_days if not in this submission but exists.
        if ( ! isset( $input['delivery_days'] ) && isset( $existing['delivery_days'] ) ) {
            $sanitized['delivery_days'] = $existing['delivery_days'];
        }

        // Preserve enabled_gateways if not in this submission but exists.
        if ( ! isset( $input['enabled_gateways'] ) && isset( $existing['enabled_gateways'] ) ) {
            $sanitized['enabled_gateways'] = $existing['enabled_gateways'];
        }

        return $sanitized;
    }

    /**
     * Render general settings section.
     */
    public function render_section_general() {
        echo '<p>' . esc_html__( 'Configure general settings for your food ordering system.', 'mitzies-jerk' ) . '</p>';
    }

    /**
     * Render pre-order settings section.
     */
    public function render_section_preorder() {
        echo '<p>' . esc_html__( 'Configure pre-order and delivery settings.', 'mitzies-jerk' ) . '</p>';
    }

    /**
     * Render payment settings section.
     */
    public function render_section_payment() {
        echo '<p>' . esc_html__( 'Configure payment gateway settings.', 'mitzies-jerk' ) . '</p>';
    }

    /**
     * Render email settings section.
     */
    public function render_section_email() {
        echo '<p>' . esc_html__( 'Configure email notification settings.', 'mitzies-jerk' ) . '</p>';
    }

    /**
     * Add meta boxes.
     */
    public function add_meta_boxes() {
        // Food item meta boxes.
        add_meta_box(
            'mj_food_item_data',
            __( 'Food Item Data', 'mitzies-jerk' ),
            array( $this, 'render_food_item_meta_box' ),
            'mj_food_item',
            'normal',
            'high'
        );

        add_meta_box(
            'mj_food_item_gallery',
            __( 'Gallery Images', 'mitzies-jerk' ),
            array( $this, 'render_gallery_meta_box' ),
            'mj_food_item',
            'side',
            'default'
        );

        // Order meta boxes.
        add_meta_box(
            'mj_order_data',
            __( 'Order Details', 'mitzies-jerk' ),
            array( $this, 'render_order_meta_box' ),
            'mj_order',
            'normal',
            'high'
        );

        add_meta_box(
            'mj_order_items',
            __( 'Order Items', 'mitzies-jerk' ),
            array( $this, 'render_order_items_meta_box' ),
            'mj_order',
            'normal',
            'default'
        );

        add_meta_box(
            'mj_order_actions',
            __( 'Order Actions', 'mitzies-jerk' ),
            array( $this, 'render_order_actions_meta_box' ),
            'mj_order',
            'side',
            'high'
        );
    }

    /**
     * Render food item meta box.
     *
     * @param WP_Post $post Post object.
     */
    public function render_food_item_meta_box( $post ) {
        wp_nonce_field( 'mj_food_item_meta', 'mj_food_item_nonce' );

        $price = get_post_meta( $post->ID, '_mj_price', true );
        $sale_price = get_post_meta( $post->ID, '_mj_sale_price', true );
        $stock_status = get_post_meta( $post->ID, '_mj_stock_status', true ) ?: 'instock';
        $stock_quantity = get_post_meta( $post->ID, '_mj_stock_quantity', true );
        $ingredients = get_post_meta( $post->ID, '_mj_ingredients', true );
        $preparation_time = get_post_meta( $post->ID, '_mj_preparation_time', true );
        $calories = get_post_meta( $post->ID, '_mj_calories', true );
        $is_featured = get_post_meta( $post->ID, '_mj_is_featured', true );

        include MITZIES_JERK_PATH . 'admin/partials/meta-box-food-item.php';
    }

    /**
     * Render gallery meta box.
     *
     * @param WP_Post $post Post object.
     */
    public function render_gallery_meta_box( $post ) {
        $gallery = get_post_meta( $post->ID, '_mj_gallery', true );
        include MITZIES_JERK_PATH . 'admin/partials/meta-box-gallery.php';
    }

    /**
     * Render order meta box.
     *
     * @param WP_Post $post Post object.
     */
    public function render_order_meta_box( $post ) {
        $order = new Mitzies_Jerk_Order( $post->ID );
        include MITZIES_JERK_PATH . 'admin/partials/meta-box-order.php';
    }

    /**
     * Render order items meta box.
     *
     * @param WP_Post $post Post object.
     */
    public function render_order_items_meta_box( $post ) {
        $order = new Mitzies_Jerk_Order( $post->ID );
        $items = $order->get_items();
        include MITZIES_JERK_PATH . 'admin/partials/meta-box-order-items.php';
    }

    /**
     * Render order actions meta box.
     *
     * @param WP_Post $post Post object.
     */
    public function render_order_actions_meta_box( $post ) {
        $order = new Mitzies_Jerk_Order( $post->ID );
        include MITZIES_JERK_PATH . 'admin/partials/meta-box-order-actions.php';
    }

    /**
     * Save meta boxes.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     */
    public function save_meta_boxes( $post_id, $post ) {
        // Check autosave.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Food item meta.
        if ( 'mj_food_item' === $post->post_type ) {
            if ( ! isset( $_POST['mj_food_item_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mj_food_item_nonce'] ) ), 'mj_food_item_meta' ) ) {
                return;
            }

            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }

            // Save price.
            if ( isset( $_POST['mj_price'] ) ) {
                update_post_meta( $post_id, '_mj_price', floatval( $_POST['mj_price'] ) );
            }

            // Save sale price.
            if ( isset( $_POST['mj_sale_price'] ) ) {
                $sale_price = sanitize_text_field( $_POST['mj_sale_price'] );
                update_post_meta( $post_id, '_mj_sale_price', $sale_price !== '' ? floatval( $sale_price ) : '' );
            }

            // Save stock status.
            if ( isset( $_POST['mj_stock_status'] ) ) {
                update_post_meta( $post_id, '_mj_stock_status', sanitize_text_field( $_POST['mj_stock_status'] ) );
            }

            // Save stock quantity.
            if ( isset( $_POST['mj_stock_quantity'] ) ) {
                update_post_meta( $post_id, '_mj_stock_quantity', absint( $_POST['mj_stock_quantity'] ) );
            }

            // Save ingredients.
            if ( isset( $_POST['mj_ingredients'] ) ) {
                update_post_meta( $post_id, '_mj_ingredients', sanitize_textarea_field( $_POST['mj_ingredients'] ) );
            }

            // Save preparation time.
            if ( isset( $_POST['mj_preparation_time'] ) ) {
                update_post_meta( $post_id, '_mj_preparation_time', sanitize_text_field( $_POST['mj_preparation_time'] ) );
            }

            // Save calories.
            if ( isset( $_POST['mj_calories'] ) ) {
                update_post_meta( $post_id, '_mj_calories', sanitize_text_field( $_POST['mj_calories'] ) );
            }

            // Save featured status.
            update_post_meta( $post_id, '_mj_is_featured', isset( $_POST['mj_is_featured'] ) ? 1 : 0 );

            // Save gallery.
            if ( isset( $_POST['mj_gallery'] ) ) {
                $gallery = array_map( 'absint', (array) $_POST['mj_gallery'] );
                update_post_meta( $post_id, '_mj_gallery', $gallery );
            } else {
                delete_post_meta( $post_id, '_mj_gallery' );
            }

            // Save addons.
            $this->save_food_addons( $post_id );
        }

        // Order meta.
        if ( 'mj_order' === $post->post_type ) {
            if ( isset( $_POST['mj_order_status'] ) ) {
                $new_status = sanitize_text_field( $_POST['mj_order_status'] );
                $order = new Mitzies_Jerk_Order();
                $order->update_status( $post_id, $new_status );
            }
        }
    }

    /**
     * Save food addons.
     *
     * @param int $post_id Post ID.
     */
    private function save_food_addons( $post_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . MITZIES_JERK_TABLE_PREFIX . 'addons';

        // Get submitted addons.
        $submitted_addons = isset( $_POST['mj_addons'] ) ? $_POST['mj_addons'] : array();

        // Get existing addon IDs.
        $existing_addons = Mitzies_Jerk_Database::get_food_addons( $post_id );
        $existing_ids = array();
        foreach ( $existing_addons as $addon ) {
            $existing_ids[] = $addon->id;
        }

        $processed_ids = array();

        // Process submitted addons.
        foreach ( $submitted_addons as $addon_key => $addon_data ) {
            $addon_name = isset( $addon_data['name'] ) ? sanitize_text_field( $addon_data['name'] ) : '';
            $addon_price = isset( $addon_data['price'] ) ? floatval( $addon_data['price'] ) : 0;

            // Skip empty addons.
            if ( empty( $addon_name ) ) {
                continue;
            }

            // Check if this is a new addon (key starts with 'new_').
            if ( strpos( $addon_key, 'new_' ) === 0 ) {
                // Insert new addon.
                $wpdb->insert(
                    $table_name,
                    array(
                        'food_item_id' => $post_id,
                        'addon_name'   => $addon_name,
                        'addon_price'  => $addon_price,
                        'status'       => 'active',
                        'sort_order'   => 0,
                        'created_at'   => current_time( 'mysql' ),
                    ),
                    array( '%d', '%s', '%f', '%s', '%d', '%s' )
                );
            } else {
                // Update existing addon.
                $addon_id = absint( $addon_key );
                $processed_ids[] = $addon_id;

                $wpdb->update(
                    $table_name,
                    array(
                        'addon_name'  => $addon_name,
                        'addon_price' => $addon_price,
                    ),
                    array( 'id' => $addon_id ),
                    array( '%s', '%f' ),
                    array( '%d' )
                );
            }
        }

        // Delete removed addons.
        foreach ( $existing_ids as $existing_id ) {
            if ( ! in_array( $existing_id, $processed_ids, true ) ) {
                $wpdb->delete(
                    $table_name,
                    array( 'id' => $existing_id ),
                    array( '%d' )
                );
            }
        }
    }

    /**
     * Food item columns.
     *
     * @param array $columns Columns.
     * @return array
     */
    public function food_item_columns( $columns ) {
        $new_columns = array();

        foreach ( $columns as $key => $value ) {
            if ( 'title' === $key ) {
                $new_columns[ $key ] = $value;
                $new_columns['image'] = __( 'Image', 'mitzies-jerk' );
            } elseif ( 'date' === $key ) {
                $new_columns['price'] = __( 'Price', 'mitzies-jerk' );
                $new_columns['stock'] = __( 'Stock', 'mitzies-jerk' );
                $new_columns['featured'] = __( 'Featured', 'mitzies-jerk' );
                $new_columns[ $key ] = $value;
            } else {
                $new_columns[ $key ] = $value;
            }
        }

        return $new_columns;
    }

    /**
     * Food item column data.
     *
     * @param string $column  Column name.
     * @param int    $post_id Post ID.
     */
    public function food_item_column_data( $column, $post_id ) {
        switch ( $column ) {
            case 'image':
                $thumbnail = get_the_post_thumbnail( $post_id, array( 50, 50 ) );
                echo $thumbnail ? $thumbnail : '<span class="dashicons dashicons-format-image"></span>';
                break;

            case 'price':
                $price = get_post_meta( $post_id, '_mj_price', true );
                $sale_price = get_post_meta( $post_id, '_mj_sale_price', true );

                if ( $sale_price ) {
                    echo '<del>' . esc_html( mitzies_jerk_format_price( $price ) ) . '</del> ';
                    echo '<ins>' . esc_html( mitzies_jerk_format_price( $sale_price ) ) . '</ins>';
                } else {
                    echo esc_html( mitzies_jerk_format_price( $price ) );
                }
                break;

            case 'stock':
                $stock_status = get_post_meta( $post_id, '_mj_stock_status', true );
                $stock_quantity = get_post_meta( $post_id, '_mj_stock_quantity', true );

                if ( 'instock' === $stock_status ) {
                    echo '<span class="mj-stock-status instock">' . esc_html__( 'In Stock', 'mitzies-jerk' );
                    if ( $stock_quantity ) {
                        echo ' (' . esc_html( $stock_quantity ) . ')';
                    }
                    echo '</span>';
                } else {
                    echo '<span class="mj-stock-status outofstock">' . esc_html__( 'Out of Stock', 'mitzies-jerk' ) . '</span>';
                }
                break;

            case 'featured':
                $is_featured = get_post_meta( $post_id, '_mj_is_featured', true );
                echo $is_featured ? '<span class="dashicons dashicons-star-filled" style="color:#ffb900;"></span>' : '<span class="dashicons dashicons-star-empty"></span>';
                break;
        }
    }

    /**
     * Order columns.
     *
     * @param array $columns Columns.
     * @return array
     */
    public function order_columns( $columns ) {
        return array(
            'cb'              => $columns['cb'],
            'order_number'    => __( 'Order', 'mitzies-jerk' ),
            'customer'        => __( 'Customer', 'mitzies-jerk' ),
            'order_status'    => __( 'Status', 'mitzies-jerk' ),
            'delivery_date'   => __( 'Delivery Date', 'mitzies-jerk' ),
            'order_total'     => __( 'Total', 'mitzies-jerk' ),
            'payment_method'  => __( 'Payment', 'mitzies-jerk' ),
            'date'            => __( 'Date', 'mitzies-jerk' ),
        );
    }

    /**
     * Order column data.
     *
     * @param string $column  Column name.
     * @param int    $post_id Post ID.
     */
    public function order_column_data( $column, $post_id ) {
        $order = new Mitzies_Jerk_Order( $post_id );

        switch ( $column ) {
            case 'order_number':
                printf(
                    '<a href="%s"><strong>#%s</strong></a>',
                    esc_url( get_edit_post_link( $post_id ) ),
                    esc_html( $order->get( 'order_number' ) )
                );
                break;

            case 'customer':
                $billing = $order->get( 'billing' );
                if ( $billing ) {
                    echo esc_html( $billing['first_name'] . ' ' . $billing['last_name'] );
                    echo '<br><small>' . esc_html( $billing['email'] ) . '</small>';
                }
                break;

            case 'order_status':
                $status = $order->get( 'status' );
                $statuses = mitzies_jerk_get_order_statuses();
                $status_label = isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status;
                echo '<span class="mj-order-status status-' . esc_attr( $status ) . '">' . esc_html( $status_label ) . '</span>';
                break;

            case 'delivery_date':
                $delivery_datetime = $order->get( 'delivery_datetime' );
                if ( $delivery_datetime ) {
                    echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $delivery_datetime ) ) );
                }
                break;

            case 'order_total':
                echo esc_html( mitzies_jerk_format_price( $order->get( 'total' ) ) );
                break;

            case 'payment_method':
                $payment_method = $order->get( 'payment_method' );
                echo esc_html( ucfirst( $payment_method ) );
                break;
        }
    }

    /**
     * Admin notices.
     */
    public function admin_notices() {
        // Check if plugin was just activated.
        if ( get_option( 'mitzies_jerk_activated' ) ) {
            delete_option( 'mitzies_jerk_activated' );
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <?php
                    printf(
                        /* translators: %s: Settings page URL */
                        esc_html__( 'Mitzies Jerk has been activated! Please visit the %s to configure your food ordering system.', 'mitzies-jerk' ),
                        '<a href="' . esc_url( admin_url( 'admin.php?page=mj-settings' ) ) . '">' . esc_html__( 'Settings page', 'mitzies-jerk' ) . '</a>'
                    );
                    ?>
                </p>
            </div>
            <?php
        }

        // Check for payment gateway configuration.
        $enabled_gateways = mitzies_jerk_get_option( 'enabled_gateways', array() );
        if ( empty( $enabled_gateways ) ) {
            $screen = get_current_screen();
            if ( $screen && strpos( $screen->id, 'mitzies-jerk' ) !== false ) {
                ?>
                <div class="notice notice-warning">
                    <p>
                        <?php
                        printf(
                            /* translators: %s: Payment settings URL */
                            esc_html__( 'No payment gateways are enabled. Please configure your %s to accept orders.', 'mitzies-jerk' ),
                            '<a href="' . esc_url( admin_url( 'admin.php?page=mj-settings&tab=payment' ) ) . '">' . esc_html__( 'payment settings', 'mitzies-jerk' ) . '</a>'
                        );
                        ?>
                    </p>
                </div>
                <?php
            }
        }
    }

    /**
     * Add action links.
     *
     * @param array $links Plugin action links.
     * @return array
     */
    public function add_action_links( $links ) {
        $plugin_links = array(
            '<a href="' . esc_url( admin_url( 'admin.php?page=mj-settings' ) ) . '">' . esc_html__( 'Settings', 'mitzies-jerk' ) . '</a>',
        );

        return array_merge( $plugin_links, $links );
    }

    /**
     * Add dashboard widget.
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'mj_dashboard_widget',
            __( 'Mitzies Jerk - Recent Orders', 'mitzies-jerk' ),
            array( $this, 'render_dashboard_widget' )
        );
    }

    /**
     * Render dashboard widget.
     */
    public function render_dashboard_widget() {
        $orders = Mitzies_Jerk_Order::get_orders( array(
            'posts_per_page' => 5,
        ) );

        if ( empty( $orders['orders'] ) ) {
            echo '<p>' . esc_html__( 'No orders yet.', 'mitzies-jerk' ) . '</p>';
            return;
        }

        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'Order', 'mitzies-jerk' ) . '</th>';
        echo '<th>' . esc_html__( 'Status', 'mitzies-jerk' ) . '</th>';
        echo '<th>' . esc_html__( 'Total', 'mitzies-jerk' ) . '</th>';
        echo '</tr></thead><tbody>';

        foreach ( $orders['orders'] as $order ) {
            echo '<tr>';
            echo '<td><a href="' . esc_url( get_edit_post_link( $order->get_id() ) ) . '">#' . esc_html( $order->get( 'order_number' ) ) . '</a></td>';
            echo '<td>' . esc_html( $order->get( 'status' ) ) . '</td>';
            echo '<td>' . esc_html( mitzies_jerk_format_price( $order->get( 'total' ) ) ) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '<p class="textright"><a href="' . esc_url( admin_url( 'edit.php?post_type=mj_order' ) ) . '">' . esc_html__( 'View all orders', 'mitzies-jerk' ) . '</a></p>';
    }
}
