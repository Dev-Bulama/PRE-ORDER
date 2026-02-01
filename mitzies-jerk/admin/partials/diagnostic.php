<?php
/**
 * Diagnostic page for troubleshooting plugin issues.
 *
 * @package    Mitzies_Jerk
 * @subpackage Mitzies_Jerk/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Ensure required constants are defined.
if ( ! defined( 'MITZIES_JERK_TABLE_PREFIX' ) ) {
    define( 'MITZIES_JERK_TABLE_PREFIX', 'mitzies_jerk_' );
}

// Run diagnostics
$diagnostics = array();

// Check 1: PHP Version
$diagnostics['php_version'] = array(
    'label'   => __( 'PHP Version', 'mitzies-jerk' ),
    'value'   => phpversion(),
    'status'  => version_compare( phpversion(), '7.4', '>=' ) ? 'pass' : 'fail',
    'message' => version_compare( phpversion(), '7.4', '>=' ) ? __( 'PHP version is compatible', 'mitzies-jerk' ) : __( 'PHP 7.4 or higher required', 'mitzies-jerk' ),
);

// Check 2: WordPress Version
$diagnostics['wp_version'] = array(
    'label'   => __( 'WordPress Version', 'mitzies-jerk' ),
    'value'   => get_bloginfo( 'version' ),
    'status'  => version_compare( get_bloginfo( 'version' ), '5.8', '>=' ) ? 'pass' : 'fail',
    'message' => version_compare( get_bloginfo( 'version' ), '5.8', '>=' ) ? __( 'WordPress version is compatible', 'mitzies-jerk' ) : __( 'WordPress 5.8 or higher recommended', 'mitzies-jerk' ),
);

// Check 3: Database Tables
global $wpdb;
$required_tables = array(
    'sessions'     => $wpdb->prefix . MITZIES_JERK_TABLE_PREFIX . 'sessions',
    'order_items'  => $wpdb->prefix . MITZIES_JERK_TABLE_PREFIX . 'order_items',
    'addons'       => $wpdb->prefix . MITZIES_JERK_TABLE_PREFIX . 'addons',
    'coupons'      => $wpdb->prefix . MITZIES_JERK_TABLE_PREFIX . 'coupons',
    'reviews'      => $wpdb->prefix . MITZIES_JERK_TABLE_PREFIX . 'reviews',
    'payment_logs' => $wpdb->prefix . MITZIES_JERK_TABLE_PREFIX . 'payment_logs',
);

$tables_exist = true;
$missing_tables = array();
foreach ( $required_tables as $table_name => $table ) {
    $exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) === $table;
    if ( ! $exists ) {
        $tables_exist = false;
        $missing_tables[] = $table_name;
    }
}

$diagnostics['database_tables'] = array(
    'label'   => __( 'Database Tables', 'mitzies-jerk' ),
    'value'   => $tables_exist ? __( 'All tables exist', 'mitzies-jerk' ) : sprintf( __( 'Missing: %s', 'mitzies-jerk' ), implode( ', ', $missing_tables ) ),
    'status'  => $tables_exist ? 'pass' : 'fail',
    'message' => $tables_exist ? __( 'All required database tables are present', 'mitzies-jerk' ) : __( 'Some tables are missing. Try deactivating and reactivating the plugin.', 'mitzies-jerk' ),
);

// Check 4: Shortcodes Registered
$registered_shortcodes = array(
    'mitzies_jerk_menu'           => shortcode_exists( 'mitzies_jerk_menu' ),
    'mitzies_jerk_cart'           => shortcode_exists( 'mitzies_jerk_cart' ),
    'mitzies_jerk_checkout'       => shortcode_exists( 'mitzies_jerk_checkout' ),
    'mitzies_jerk_categories'     => shortcode_exists( 'mitzies_jerk_categories' ),
    'mitzies_jerk_order_tracking' => shortcode_exists( 'mitzies_jerk_order_tracking' ),
    'mitzies_jerk_order_history'  => shortcode_exists( 'mitzies_jerk_order_history' ),
    'mitzies_jerk_mini_cart'      => shortcode_exists( 'mitzies_jerk_mini_cart' ),
);
$all_shortcodes_registered = ! in_array( false, $registered_shortcodes, true );
$missing_shortcodes = array_keys( array_filter( $registered_shortcodes, function( $v ) { return ! $v; } ) );

$diagnostics['shortcodes'] = array(
    'label'   => __( 'Shortcodes', 'mitzies-jerk' ),
    'value'   => $all_shortcodes_registered ? __( 'All registered', 'mitzies-jerk' ) : sprintf( __( 'Missing: %s', 'mitzies-jerk' ), implode( ', ', $missing_shortcodes ) ),
    'status'  => $all_shortcodes_registered ? 'pass' : 'fail',
    'message' => $all_shortcodes_registered ? __( 'All shortcodes are properly registered', 'mitzies-jerk' ) : __( 'Some shortcodes are not registered. Check plugin initialization.', 'mitzies-jerk' ),
);

// Check 5: AJAX Handlers
$ajax_actions = array(
    'mj_add_to_cart'    => has_action( 'wp_ajax_mj_add_to_cart' ) || has_action( 'wp_ajax_nopriv_mj_add_to_cart' ),
    'mj_update_cart'    => has_action( 'wp_ajax_mj_update_cart' ) || has_action( 'wp_ajax_nopriv_mj_update_cart' ),
    'mj_remove_from_cart' => has_action( 'wp_ajax_mj_remove_from_cart' ) || has_action( 'wp_ajax_nopriv_mj_remove_from_cart' ),
    'mj_get_cart'       => has_action( 'wp_ajax_mj_get_cart' ) || has_action( 'wp_ajax_nopriv_mj_get_cart' ),
);
$all_ajax_registered = ! in_array( false, $ajax_actions, true );
$missing_ajax = array_keys( array_filter( $ajax_actions, function( $v ) { return ! $v; } ) );

$diagnostics['ajax_handlers'] = array(
    'label'   => __( 'AJAX Handlers', 'mitzies-jerk' ),
    'value'   => $all_ajax_registered ? __( 'All registered', 'mitzies-jerk' ) : sprintf( __( 'Missing: %s', 'mitzies-jerk' ), implode( ', ', $missing_ajax ) ),
    'status'  => $all_ajax_registered ? 'pass' : 'fail',
    'message' => $all_ajax_registered ? __( 'All AJAX handlers are registered', 'mitzies-jerk' ) : __( 'Some AJAX handlers are missing. Cart functionality may not work.', 'mitzies-jerk' ),
);

// Check 6: Elementor Integration
$elementor_active = class_exists( '\Elementor\Plugin' );
$elementor_widgets_registered = false;

if ( $elementor_active && did_action( 'elementor/loaded' ) ) {
    // Check if our category exists
    $elements_manager = \Elementor\Plugin::instance()->elements_manager;
    if ( $elements_manager ) {
        $categories = $elements_manager->get_categories();
        $elementor_widgets_registered = isset( $categories['mitzies-jerk'] );
    }
}

$diagnostics['elementor'] = array(
    'label'   => __( 'Elementor Integration', 'mitzies-jerk' ),
    'value'   => ! $elementor_active ? __( 'Elementor not active', 'mitzies-jerk' ) : ( $elementor_widgets_registered ? __( 'Widgets registered', 'mitzies-jerk' ) : __( 'Widgets NOT registered', 'mitzies-jerk' ) ),
    'status'  => ! $elementor_active ? 'warning' : ( $elementor_widgets_registered ? 'pass' : 'fail' ),
    'message' => ! $elementor_active ? __( 'Install Elementor to use widgets', 'mitzies-jerk' ) : ( $elementor_widgets_registered ? __( 'Elementor widgets are available', 'mitzies-jerk' ) : __( 'Widgets not loading. Check console for errors.', 'mitzies-jerk' ) ),
);

// Check 7: Session Handling
$session_working = false;
$session_id = '';
try {
    global $mitzies_jerk;
    if ( isset( $mitzies_jerk ) && isset( $mitzies_jerk->session ) ) {
        $session_id = $mitzies_jerk->session->get_session_id();
        $session_working = ! empty( $session_id );
    } elseif ( class_exists( 'Mitzies_Jerk_Session' ) ) {
        $session = new Mitzies_Jerk_Session();
        $session_id = $session->get_session_id();
        $session_working = ! empty( $session_id );
    }
} catch ( Exception $e ) {
    $session_working = false;
}

$diagnostics['session'] = array(
    'label'   => __( 'Session Handling', 'mitzies-jerk' ),
    'value'   => $session_working ? sprintf( __( 'Session ID: %s', 'mitzies-jerk' ), substr( $session_id, 0, 16 ) . '...' ) : __( 'No session', 'mitzies-jerk' ),
    'status'  => $session_working ? 'pass' : 'warning',
    'message' => $session_working ? __( 'Session is working correctly', 'mitzies-jerk' ) : __( 'Session not initialized on admin. This is normal for admin pages.', 'mitzies-jerk' ),
);

// Check 8: Food Items Count
$food_items_count = wp_count_posts( 'mj_food_item' );
$total_items = $food_items_count->publish ?? 0;

$diagnostics['food_items'] = array(
    'label'   => __( 'Food Items', 'mitzies-jerk' ),
    'value'   => sprintf( __( '%d published items', 'mitzies-jerk' ), $total_items ),
    'status'  => $total_items > 0 ? 'pass' : 'warning',
    'message' => $total_items > 0 ? __( 'Food items exist in the database', 'mitzies-jerk' ) : __( 'No food items found. Add some food items.', 'mitzies-jerk' ),
);

// Check 9: Payment Gateways
$enabled_gateways = mitzies_jerk_get_option( 'enabled_gateways', array() );

$diagnostics['payment_gateways'] = array(
    'label'   => __( 'Payment Gateways', 'mitzies-jerk' ),
    'value'   => ! empty( $enabled_gateways ) ? implode( ', ', $enabled_gateways ) : __( 'None enabled', 'mitzies-jerk' ),
    'status'  => ! empty( $enabled_gateways ) ? 'pass' : 'warning',
    'message' => ! empty( $enabled_gateways ) ? __( 'Payment gateways are configured', 'mitzies-jerk' ) : __( 'No payment gateways enabled. Configure in Settings > Payment.', 'mitzies-jerk' ),
);

// Check 10: Plugin Pages
$menu_page_id = get_option( 'mitzies_jerk_menu_page_id' );
$cart_page_id = get_option( 'mitzies_jerk_cart_page_id' );
$checkout_page_id = get_option( 'mitzies_jerk_checkout_page_id' );
$pages_configured = $menu_page_id && $cart_page_id && $checkout_page_id;

$diagnostics['plugin_pages'] = array(
    'label'   => __( 'Plugin Pages', 'mitzies-jerk' ),
    'value'   => $pages_configured ? __( 'All configured', 'mitzies-jerk' ) : __( 'Some missing', 'mitzies-jerk' ),
    'status'  => $pages_configured ? 'pass' : 'warning',
    'message' => $pages_configured ? __( 'Menu, Cart, and Checkout pages are set', 'mitzies-jerk' ) : __( 'Some plugin pages are not configured.', 'mitzies-jerk' ),
);

// Check 11: Scripts & Styles Enqueued (on frontend)
$scripts_info = array(
    'jquery'              => wp_script_is( 'jquery', 'registered' ),
    'mitzies-jerk-public' => wp_script_is( 'mitzies-jerk-public', 'registered' ),
);

$diagnostics['frontend_assets'] = array(
    'label'   => __( 'Frontend Assets', 'mitzies-jerk' ),
    'value'   => $scripts_info['mitzies-jerk-public'] ? __( 'Registered', 'mitzies-jerk' ) : __( 'Not registered', 'mitzies-jerk' ),
    'status'  => 'info',
    'message' => __( 'Note: Scripts are only enqueued on the frontend', 'mitzies-jerk' ),
);

// Check 12: Cart Contents
global $mitzies_jerk;
$cart_contents = array();
$cart_count = 0;
if ( isset( $mitzies_jerk ) && isset( $mitzies_jerk->cart ) ) {
    $cart_contents = $mitzies_jerk->cart->get_cart();
    $cart_count = $mitzies_jerk->cart->get_cart_count();
}

$diagnostics['cart_contents'] = array(
    'label'   => __( 'Current Cart', 'mitzies-jerk' ),
    'value'   => sprintf( __( '%d items', 'mitzies-jerk' ), $cart_count ),
    'status'  => 'info',
    'message' => __( 'Current items in cart for this session', 'mitzies-jerk' ),
);

// Check 13: Addons Table
$addons_table = $wpdb->prefix . MITZIES_JERK_TABLE_PREFIX . 'addons';
$addons_count = null;
$addons_error = false;
$addons_table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $addons_table ) ) === $addons_table;
if ( $addons_table_exists ) {
    $addons_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$addons_table}" );
    if ( $wpdb->last_error ) {
        $addons_error = true;
    }
}

$diagnostics['addons'] = array(
    'label'   => __( 'Food Addons', 'mitzies-jerk' ),
    'value'   => $addons_table_exists && ! $addons_error ? sprintf( __( '%d addons', 'mitzies-jerk' ), intval( $addons_count ) ) : __( 'Table not found', 'mitzies-jerk' ),
    'status'  => $addons_table_exists && ! $addons_error ? 'pass' : 'fail',
    'message' => $addons_table_exists && ! $addons_error ? __( 'Addons table is accessible', 'mitzies-jerk' ) : __( 'Addons table not found. Click "Recreate Database Tables" to fix.', 'mitzies-jerk' ),
);

// Check 14: Post Types Registered
$post_types_registered = array(
    'mj_food_item' => post_type_exists( 'mj_food_item' ),
    'mj_order'     => post_type_exists( 'mj_order' ),
);
$all_post_types = ! in_array( false, $post_types_registered, true );

$diagnostics['post_types'] = array(
    'label'   => __( 'Custom Post Types', 'mitzies-jerk' ),
    'value'   => $all_post_types ? __( 'All registered', 'mitzies-jerk' ) : __( 'Some missing', 'mitzies-jerk' ),
    'status'  => $all_post_types ? 'pass' : 'fail',
    'message' => $all_post_types ? __( 'Food Item and Order post types are registered', 'mitzies-jerk' ) : __( 'Post types not registered. Plugin may not be initialized correctly.', 'mitzies-jerk' ),
);

// Check 15: Taxonomy Registered
$taxonomy_registered = taxonomy_exists( 'mj_food_category' );

$diagnostics['taxonomy'] = array(
    'label'   => __( 'Food Category Taxonomy', 'mitzies-jerk' ),
    'value'   => $taxonomy_registered ? __( 'Registered', 'mitzies-jerk' ) : __( 'Not registered', 'mitzies-jerk' ),
    'status'  => $taxonomy_registered ? 'pass' : 'fail',
    'message' => $taxonomy_registered ? __( 'Food category taxonomy is available', 'mitzies-jerk' ) : __( 'Taxonomy not registered', 'mitzies-jerk' ),
);

// AJAX Test endpoint
if ( isset( $_POST['mj_ajax_test'] ) && wp_verify_nonce( $_POST['mj_ajax_test_nonce'], 'mj_ajax_test' ) ) {
    $test_result = array(
        'ajax_url'      => admin_url( 'admin-ajax.php' ),
        'nonce_valid'   => true,
        'cart_object'   => isset( $mitzies_jerk->cart ),
        'session_id'    => $session_id,
    );

    // Try to add a test item to cart
    if ( $total_items > 0 ) {
        $test_item = get_posts( array(
            'post_type'      => 'mj_food_item',
            'posts_per_page' => 1,
            'post_status'    => 'publish',
        ) );
        if ( ! empty( $test_item ) ) {
            $test_result['test_item_id'] = $test_item[0]->ID;
            $add_result = $mitzies_jerk->cart->add_to_cart( $test_item[0]->ID, 1 );
            $test_result['add_to_cart_result'] = $add_result;
            $test_result['cart_after_add'] = $mitzies_jerk->cart->get_cart_count();
            // Remove the test item
            if ( $add_result ) {
                $mitzies_jerk->cart->remove_from_cart( $add_result );
            }
        }
    }

    $diagnostics['ajax_test_result'] = array(
        'label'   => __( 'AJAX Test Result', 'mitzies-jerk' ),
        'value'   => '<pre>' . esc_html( print_r( $test_result, true ) ) . '</pre>',
        'status'  => ! empty( $test_result['add_to_cart_result'] ) ? 'pass' : 'fail',
        'message' => ! empty( $test_result['add_to_cart_result'] ) ? __( 'Cart functionality working', 'mitzies-jerk' ) : __( 'Cart add failed', 'mitzies-jerk' ),
    );
}

?>

<div class="wrap mj-admin-wrap mj-diagnostic">
    <div class="mj-admin-header">
        <div class="mj-header-left">
            <h1 class="mj-admin-title">
                <span class="dashicons dashicons-admin-tools"></span>
                <?php esc_html_e( 'Plugin Diagnostics', 'mitzies-jerk' ); ?>
            </h1>
        </div>
    </div>

    <div class="mj-diagnostic-grid">
        <!-- System Status -->
        <div class="mj-card">
            <div class="mj-card-header">
                <h2><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'System Status', 'mitzies-jerk' ); ?></h2>
            </div>
            <div class="mj-card-body">
                <table class="mj-diagnostic-table widefat">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Check', 'mitzies-jerk' ); ?></th>
                            <th><?php esc_html_e( 'Value', 'mitzies-jerk' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'mitzies-jerk' ); ?></th>
                            <th><?php esc_html_e( 'Message', 'mitzies-jerk' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $diagnostics as $key => $check ) : ?>
                        <tr class="mj-status-<?php echo esc_attr( $check['status'] ); ?>">
                            <td><strong><?php echo esc_html( $check['label'] ); ?></strong></td>
                            <td><?php echo wp_kses_post( $check['value'] ); ?></td>
                            <td>
                                <?php if ( 'pass' === $check['status'] ) : ?>
                                    <span class="mj-badge mj-badge-success"><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Pass', 'mitzies-jerk' ); ?></span>
                                <?php elseif ( 'fail' === $check['status'] ) : ?>
                                    <span class="mj-badge mj-badge-error"><span class="dashicons dashicons-no"></span> <?php esc_html_e( 'Fail', 'mitzies-jerk' ); ?></span>
                                <?php elseif ( 'warning' === $check['status'] ) : ?>
                                    <span class="mj-badge mj-badge-warning"><span class="dashicons dashicons-warning"></span> <?php esc_html_e( 'Warning', 'mitzies-jerk' ); ?></span>
                                <?php else : ?>
                                    <span class="mj-badge mj-badge-info"><span class="dashicons dashicons-info"></span> <?php esc_html_e( 'Info', 'mitzies-jerk' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html( $check['message'] ); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mj-card">
            <div class="mj-card-header">
                <h2><span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e( 'Quick Actions', 'mitzies-jerk' ); ?></h2>
            </div>
            <div class="mj-card-body">
                <form method="post">
                    <?php wp_nonce_field( 'mj_ajax_test', 'mj_ajax_test_nonce' ); ?>
                    <input type="hidden" name="mj_ajax_test" value="1">
                    <p>
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Test Cart Functionality', 'mitzies-jerk' ); ?>
                        </button>
                    </p>
                </form>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'mj_create_tables', 'mj_create_tables_nonce' ); ?>
                    <input type="hidden" name="action" value="mj_recreate_tables">
                    <p>
                        <button type="submit" class="button">
                            <span class="dashicons dashicons-database"></span> <?php esc_html_e( 'Recreate Database Tables', 'mitzies-jerk' ); ?>
                        </button>
                    </p>
                </form>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'mj_create_sample_data', 'mj_create_sample_nonce' ); ?>
                    <input type="hidden" name="action" value="mj_create_sample_data">
                    <p>
                        <button type="submit" class="button">
                            <span class="dashicons dashicons-carrot"></span> <?php esc_html_e( 'Create Sample Food Items', 'mitzies-jerk' ); ?>
                        </button>
                    </p>
                </form>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'mj_flush_cache', 'mj_flush_cache_nonce' ); ?>
                    <input type="hidden" name="action" value="mj_flush_cache">
                    <p>
                        <button type="submit" class="button">
                            <span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Clear Plugin Cache', 'mitzies-jerk' ); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>

        <!-- Shortcode Reference -->
        <div class="mj-card">
            <div class="mj-card-header">
                <h2><span class="dashicons dashicons-shortcode"></span> <?php esc_html_e( 'Working Shortcodes', 'mitzies-jerk' ); ?></h2>
            </div>
            <div class="mj-card-body">
                <p><?php esc_html_e( 'Copy and paste these shortcodes to your pages:', 'mitzies-jerk' ); ?></p>
                <table class="widefat">
                    <tr>
                        <td><strong><?php esc_html_e( 'Food Menu', 'mitzies-jerk' ); ?></strong></td>
                        <td><code>[mitzies_jerk_menu]</code></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e( 'Cart', 'mitzies-jerk' ); ?></strong></td>
                        <td><code>[mitzies_jerk_cart]</code></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e( 'Checkout', 'mitzies-jerk' ); ?></strong></td>
                        <td><code>[mitzies_jerk_checkout]</code></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e( 'Categories', 'mitzies-jerk' ); ?></strong></td>
                        <td><code>[mitzies_jerk_categories]</code></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e( 'Order History', 'mitzies-jerk' ); ?></strong></td>
                        <td><code>[mitzies_jerk_order_history]</code></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e( 'Order Tracking', 'mitzies-jerk' ); ?></strong></td>
                        <td><code>[mitzies_jerk_order_tracking]</code></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e( 'Mini Cart', 'mitzies-jerk' ); ?></strong></td>
                        <td><code>[mitzies_jerk_mini_cart]</code></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Debug Log -->
        <div class="mj-card">
            <div class="mj-card-header">
                <h2><span class="dashicons dashicons-media-text"></span> <?php esc_html_e( 'JavaScript Console Test', 'mitzies-jerk' ); ?></h2>
            </div>
            <div class="mj-card-body">
                <p><?php esc_html_e( 'Open browser console (F12) and click the button below to test AJAX:', 'mitzies-jerk' ); ?></p>
                <p>
                    <button type="button" id="mj-test-ajax" class="button button-primary">
                        <?php esc_html_e( 'Test AJAX Add to Cart', 'mitzies-jerk' ); ?>
                    </button>
                    <span id="mj-ajax-result"></span>
                </p>
                <div id="mj-ajax-log" style="background: #1e1e1e; color: #0f0; padding: 15px; margin-top: 15px; font-family: monospace; font-size: 12px; max-height: 300px; overflow: auto; display: none;"></div>
            </div>
        </div>
    </div>
</div>

<style>
.mj-diagnostic-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
    margin-top: 20px;
}
.mj-diagnostic-table th,
.mj-diagnostic-table td {
    padding: 12px 15px;
    vertical-align: middle;
}
.mj-status-fail {
    background-color: #ffeaea !important;
}
.mj-status-warning {
    background-color: #fff8e5 !important;
}
.mj-status-pass {
    background-color: #edfaef !important;
}
.mj-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}
.mj-badge-success {
    background: #d4edda;
    color: #155724;
}
.mj-badge-error {
    background: #f8d7da;
    color: #721c24;
}
.mj-badge-warning {
    background: #fff3cd;
    color: #856404;
}
.mj-badge-info {
    background: #d1ecf1;
    color: #0c5460;
}
.mj-card-body form {
    margin-bottom: 10px;
}
.mj-card-body .button .dashicons {
    margin-right: 5px;
    line-height: inherit;
}
</style>

<script>
jQuery(document).ready(function($) {
    var logEl = $('#mj-ajax-log');

    function log(msg, type) {
        type = type || 'info';
        var color = type === 'error' ? '#f44' : (type === 'success' ? '#4f4' : '#0ff');
        var time = new Date().toLocaleTimeString();
        logEl.show().append('<div style="color:' + color + '">[' + time + '] ' + msg + '</div>');
        logEl.scrollTop(logEl[0].scrollHeight);
        console.log('[MJ Diagnostic]', msg);
    }

    $('#mj-test-ajax').on('click', function() {
        logEl.empty();
        log('Starting AJAX test...');

        // Check if mitziesJerk object exists
        if (typeof mitziesJerk === 'undefined') {
            log('ERROR: mitziesJerk JavaScript object not found!', 'error');
            log('This means the public JS file is not loaded on this page.', 'error');
            log('AJAX URL should be: <?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>', 'info');

            // Try manual AJAX call
            log('Attempting manual AJAX call...', 'info');

            $.ajax({
                url: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
                type: 'POST',
                data: {
                    action: 'mj_get_cart',
                    nonce: '<?php echo esc_js( wp_create_nonce( 'mj_ajax_nonce' ) ); ?>'
                },
                success: function(response) {
                    log('Manual AJAX response: ' + JSON.stringify(response), response.success ? 'success' : 'error');
                },
                error: function(xhr, status, error) {
                    log('Manual AJAX failed: ' + error, 'error');
                    log('Status: ' + status, 'error');
                    log('Response: ' + xhr.responseText, 'error');
                }
            });
            return;
        }

        log('mitziesJerk object found', 'success');
        log('AJAX URL: ' + mitziesJerk.ajaxUrl, 'info');
        log('Nonce: ' + mitziesJerk.nonce.substring(0, 10) + '...', 'info');

        // Get first food item
        <?php
        $test_items = get_posts( array(
            'post_type'      => 'mj_food_item',
            'posts_per_page' => 1,
            'post_status'    => 'publish',
        ) );
        $test_id = ! empty( $test_items ) ? $test_items[0]->ID : 0;
        ?>

        var testItemId = <?php echo intval( $test_id ); ?>;

        if (!testItemId) {
            log('No food items found to test with', 'error');
            return;
        }

        log('Testing with food item ID: ' + testItemId, 'info');

        // Test get cart
        log('Testing mj_get_cart action...', 'info');
        $.ajax({
            url: mitziesJerk.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mj_get_cart',
                nonce: mitziesJerk.nonce
            },
            success: function(response) {
                log('Get cart response: ' + JSON.stringify(response).substring(0, 200), response.success ? 'success' : 'error');
            },
            error: function(xhr, status, error) {
                log('Get cart error: ' + error, 'error');
            }
        });

        // Test add to cart
        log('Testing mj_add_to_cart action...', 'info');
        $.ajax({
            url: mitziesJerk.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mj_add_to_cart',
                nonce: mitziesJerk.nonce,
                food_item_id: testItemId,
                quantity: 1
            },
            success: function(response) {
                log('Add to cart response: ' + JSON.stringify(response).substring(0, 300), response.success ? 'success' : 'error');
                if (response.success) {
                    log('CART FUNCTIONALITY IS WORKING!', 'success');
                } else {
                    log('Add to cart failed: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                }
            },
            error: function(xhr, status, error) {
                log('Add to cart AJAX error: ' + error, 'error');
                log('Response: ' + xhr.responseText.substring(0, 500), 'error');
            }
        });
    });
});
</script>
