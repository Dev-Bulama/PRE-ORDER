<?php
/**
 * Admin settings page.
 *
 * @package    Mitzies_Jerk
 * @subpackage Mitzies_Jerk/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';
$options = get_option( 'mitzies_jerk_settings', array() );

// Default values.
$defaults = array(
    'currency' => 'USD',
    'currency_symbol' => '$',
    'currency_position' => 'left',
    'decimal_places' => 2,
    'thousands_separator' => ',',
    'decimal_separator' => '.',
    'min_preorder_hours' => 24,
    'max_preorder_days' => 30,
    'payment_expiry_minutes' => 30,
    'max_orders_per_day' => 100,
    'delivery_days' => array( 0, 1, 2, 3, 4, 5, 6 ),
    'delivery_time_slots' => array(
        array( 'start' => '09:00', 'end' => '12:00' ),
        array( 'start' => '12:00', 'end' => '15:00' ),
        array( 'start' => '15:00', 'end' => '18:00' ),
        array( 'start' => '18:00', 'end' => '21:00' ),
    ),
    'delivery_fee' => 5.00,
    'free_delivery_threshold' => 50.00,
    'enable_tax' => false,
    'tax_rate' => 0,
    'tax_inclusive' => false,
    'enabled_gateways' => array(),
    'admin_email' => get_option( 'admin_email' ),
    'email_from_name' => get_bloginfo( 'name' ),
    'email_from_address' => get_option( 'admin_email' ),
    'items_per_page' => 12,
    'enable_guest_checkout' => true,
    'enable_reviews' => true,
    'review_approval' => true,
    'order_auto_approve' => false,
    'show_addons_on_thumbnail' => true,
    'enable_distance_rates' => false,
    'google_maps_api_key' => '',
    'store_latitude' => '',
    'store_longitude' => '',
    'store_address' => '',
    'distance_unit' => 'km',
    'enable_logging' => false,
    'delete_data_on_uninstall' => false,
);

$options = wp_parse_args( $options, $defaults );

$tabs = array(
    'general'  => __( 'General', 'mitzies-jerk' ),
    'preorder' => __( 'Pre-Order', 'mitzies-jerk' ),
    'delivery' => __( 'Delivery & Pickup', 'mitzies-jerk' ),
    'payment'  => __( 'Payment', 'mitzies-jerk' ),
    'email'    => __( 'Email', 'mitzies-jerk' ),
    'advanced' => __( 'Advanced', 'mitzies-jerk' ),
);

$days_of_week = array(
    0 => __( 'Sunday', 'mitzies-jerk' ),
    1 => __( 'Monday', 'mitzies-jerk' ),
    2 => __( 'Tuesday', 'mitzies-jerk' ),
    3 => __( 'Wednesday', 'mitzies-jerk' ),
    4 => __( 'Thursday', 'mitzies-jerk' ),
    5 => __( 'Friday', 'mitzies-jerk' ),
    6 => __( 'Saturday', 'mitzies-jerk' ),
);

$currencies = array(
    'USD' => 'US Dollar ($)',
    'EUR' => 'Euro (€)',
    'GBP' => 'British Pound (£)',
    'NGN' => 'Nigerian Naira (₦)',
    'GHS' => 'Ghanaian Cedi (GH₵)',
    'KES' => 'Kenyan Shilling (KSh)',
    'ZAR' => 'South African Rand (R)',
    'INR' => 'Indian Rupee (₹)',
    'AUD' => 'Australian Dollar (A$)',
    'CAD' => 'Canadian Dollar (C$)',
);
?>

<div class="wrap mj-settings">
    <h1><?php esc_html_e( 'Mitzies Jerk Settings', 'mitzies-jerk' ); ?></h1>

    <nav class="nav-tab-wrapper">
        <?php foreach ( $tabs as $tab_id => $tab_name ) : ?>
            <a href="<?php echo esc_url( add_query_arg( 'tab', $tab_id, admin_url( 'admin.php?page=mj-settings' ) ) ); ?>"
               class="nav-tab <?php echo $active_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html( $tab_name ); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <form method="post" action="options.php" class="mj-settings-form">
        <?php settings_fields( 'mitzies_jerk_settings' ); ?>

        <?php if ( 'general' === $active_tab ) : ?>
            <!-- General Settings -->
            <div class="mj-settings-section">
                <h2><?php esc_html_e( 'Currency Settings', 'mitzies-jerk' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="currency"><?php esc_html_e( 'Currency', 'mitzies-jerk' ); ?></label></th>
                        <td>
                            <select name="mitzies_jerk_settings[currency]" id="currency">
                                <?php foreach ( $currencies as $code => $name ) : ?>
                                    <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $options['currency'], $code ); ?>>
                                        <?php echo esc_html( $name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="currency_symbol"><?php esc_html_e( 'Currency Symbol', 'mitzies-jerk' ); ?></label></th>
                        <td>
                            <input type="text" name="mitzies_jerk_settings[currency_symbol]" id="currency_symbol"
                                   value="<?php echo esc_attr( $options['currency_symbol'] ); ?>" class="small-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="currency_position"><?php esc_html_e( 'Currency Position', 'mitzies-jerk' ); ?></label></th>
                        <td>
                            <select name="mitzies_jerk_settings[currency_position]" id="currency_position">
                                <option value="left" <?php selected( $options['currency_position'], 'left' ); ?>><?php esc_html_e( 'Left ($99)', 'mitzies-jerk' ); ?></option>
                                <option value="right" <?php selected( $options['currency_position'], 'right' ); ?>><?php esc_html_e( 'Right (99$)', 'mitzies-jerk' ); ?></option>
                                <option value="left_space" <?php selected( $options['currency_position'], 'left_space' ); ?>><?php esc_html_e( 'Left with space ($ 99)', 'mitzies-jerk' ); ?></option>
                                <option value="right_space" <?php selected( $options['currency_position'], 'right_space' ); ?>><?php esc_html_e( 'Right with space (99 $)', 'mitzies-jerk' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="decimal_places"><?php esc_html_e( 'Decimal Places', 'mitzies-jerk' ); ?></label></th>
                        <td>
                            <input type="number" name="mitzies_jerk_settings[decimal_places]" id="decimal_places"
                                   value="<?php echo esc_attr( $options['decimal_places'] ); ?>" min="0" max="4" class="small-text">
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Tax Settings', 'mitzies-jerk' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Enable Tax', 'mitzies-jerk' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="mitzies_jerk_settings[enable_tax]" value="1" <?php checked( $options['enable_tax'], true ); ?>>
                                <?php esc_html_e( 'Enable tax calculation', 'mitzies-jerk' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tax_rate"><?php esc_html_e( 'Tax Rate (%)', 'mitzies-jerk' ); ?></label></th>
                        <td>
                            <input type="number" name="mitzies_jerk_settings[tax_rate]" id="tax_rate"
                                   value="<?php echo esc_attr( $options['tax_rate'] ); ?>" min="0" max="100" step="0.01" class="small-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Prices Include Tax', 'mitzies-jerk' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="mitzies_jerk_settings[tax_inclusive]" value="1" <?php checked( $options['tax_inclusive'], true ); ?>>
                                <?php esc_html_e( 'Prices entered include tax', 'mitzies-jerk' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Order Settings', 'mitzies-jerk' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Order Auto-Approval', 'mitzies-jerk' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="mitzies_jerk_settings[order_auto_approve]" value="1" <?php checked( $options['order_auto_approve'], true ); ?>>
                                <?php esc_html_e( 'Auto-approve orders (set to Processing instead of Pending)', 'mitzies-jerk' ); ?>
                            </label>
                            <p class="description"><?php esc_html_e( 'When enabled, new orders will be automatically set to "Processing" status. When disabled, orders will stay as "Pending" until manually approved.', 'mitzies-jerk' ); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Display Settings', 'mitzies-jerk' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="items_per_page"><?php esc_html_e( 'Items Per Page', 'mitzies-jerk' ); ?></label></th>
                        <td>
                            <input type="number" name="mitzies_jerk_settings[items_per_page]" id="items_per_page"
                                   value="<?php echo esc_attr( $options['items_per_page'] ); ?>" min="1" max="100" class="small-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Guest Checkout', 'mitzies-jerk' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="mitzies_jerk_settings[enable_guest_checkout]" value="1" <?php checked( $options['enable_guest_checkout'], true ); ?>>
                                <?php esc_html_e( 'Allow customers to checkout without an account', 'mitzies-jerk' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Add-ons on Thumbnails', 'mitzies-jerk' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="mitzies_jerk_settings[show_addons_on_thumbnail]" value="1" <?php checked( $options['show_addons_on_thumbnail'], true ); ?>>
                                <?php esc_html_e( 'Show add-ons checkbox on product thumbnail/grid', 'mitzies-jerk' ); ?>
                            </label>
                            <p class="description"><?php esc_html_e( 'When OFF, add-ons will only be visible inside the product detail page.', 'mitzies-jerk' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Reviews', 'mitzies-jerk' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="mitzies_jerk_settings[enable_reviews]" value="1" <?php checked( $options['enable_reviews'], true ); ?>>
                                <?php esc_html_e( 'Enable customer reviews', 'mitzies-jerk' ); ?>
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" name="mitzies_jerk_settings[review_approval]" value="1" <?php checked( $options['review_approval'], true ); ?>>
                                <?php esc_html_e( 'Reviews require approval', 'mitzies-jerk' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>

        <?php elseif ( 'preorder' === $active_tab ) : ?>
            <!-- Pre-Order Settings -->
            <div class="mj-settings-section">
                <h2><?php esc_html_e( 'Pre-Order Configuration', 'mitzies-jerk' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="min_preorder_hours"><?php esc_html_e( 'Minimum Pre-Order Time (hours)', 'mitzies-jerk' ); ?></label></th>
                        <td>
                            <input type="number" name="mitzies_jerk_settings[min_preorder_hours]" id="min_preorder_hours"
                                   value="<?php echo esc_attr( $options['min_preorder_hours'] ); ?>" min="1" max="168" class="small-text">
                            <p class="description"><?php esc_html_e( 'Minimum hours before delivery that orders must be placed.', 'mitzies-jerk' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="max_preorder_days"><?php esc_html_e( 'Maximum Pre-Order Days', 'mitzies-jerk' ); ?></label></th>
                        <td>
                            <input type="number" name="mitzies_jerk_settings[max_preorder_days]" id="max_preorder_days"
                                   value="<?php echo esc_attr( $options['max_preorder_days'] ); ?>" min="1" max="365" class="small-text">
                            <p class="description"><?php esc_html_e( 'Maximum days in advance that orders can be placed.', 'mitzies-jerk' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="payment_expiry_minutes"><?php esc_html_e( 'Payment Expiry (minutes)', 'mitzies-jerk' ); ?></label></th>
                        <td>
                            <input type="number" name="mitzies_jerk_settings[payment_expiry_minutes]" id="payment_expiry_minutes"
                                   value="<?php echo esc_attr( $options['payment_expiry_minutes'] ); ?>" min="5" max="1440" class="small-text">
                            <p class="description"><?php esc_html_e( 'Time allowed to complete payment before order expires.', 'mitzies-jerk' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="max_orders_per_day"><?php esc_html_e( 'Maximum Orders Per Day', 'mitzies-jerk' ); ?></label></th>
                        <td>
                            <input type="number" name="mitzies_jerk_settings[max_orders_per_day]" id="max_orders_per_day"
                                   value="<?php echo esc_attr( $options['max_orders_per_day'] ); ?>" min="1" class="small-text">
                            <p class="description"><?php esc_html_e( 'Maximum orders accepted per day (0 for unlimited).', 'mitzies-jerk' ); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Delivery Settings', 'mitzies-jerk' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Delivery Days', 'mitzies-jerk' ); ?></th>
                        <td>
                            <?php foreach ( $days_of_week as $day_num => $day_name ) : ?>
                                <label style="margin-right: 15px;">
                                    <input type="checkbox" name="mitzies_jerk_settings[delivery_days][]"
                                           value="<?php echo esc_attr( $day_num ); ?>"
                                           <?php checked( in_array( $day_num, $options['delivery_days'], true ), true ); ?>>
                                    <?php echo esc_html( $day_name ); ?>
                                </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Delivery Time Slots', 'mitzies-jerk' ); ?></th>
                        <td>
                            <div id="mj-time-slots">
                                <?php foreach ( $options['delivery_time_slots'] as $index => $slot ) : ?>
                                    <div class="mj-time-slot">
                                        <input type="time" name="mitzies_jerk_settings[delivery_time_slots][<?php echo esc_attr( $index ); ?>][start]"
                                               value="<?php echo esc_attr( $slot['start'] ); ?>">
                                        <span>to</span>
                                        <input type="time" name="mitzies_jerk_settings[delivery_time_slots][<?php echo esc_attr( $index ); ?>][end]"
                                               value="<?php echo esc_attr( $slot['end'] ); ?>">
                                        <button type="button" class="button mj-remove-slot">&times;</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="button" id="mj-add-time-slot"><?php esc_html_e( 'Add Time Slot', 'mitzies-jerk' ); ?></button>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="delivery_fee"><?php esc_html_e( 'Delivery Fee', 'mitzies-jerk' ); ?></label></th>
                        <td>
                            <input type="number" name="mitzies_jerk_settings[delivery_fee]" id="delivery_fee"
                                   value="<?php echo esc_attr( $options['delivery_fee'] ); ?>" min="0" step="0.01" class="small-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="free_delivery_threshold"><?php esc_html_e( 'Free Delivery Threshold', 'mitzies-jerk' ); ?></label></th>
                        <td>
                            <input type="number" name="mitzies_jerk_settings[free_delivery_threshold]" id="free_delivery_threshold"
                                   value="<?php echo esc_attr( $options['free_delivery_threshold'] ); ?>" min="0" step="0.01" class="small-text">
                            <p class="description"><?php esc_html_e( 'Orders above this amount get free delivery (0 to disable).', 'mitzies-jerk' ); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

        <?php elseif ( 'delivery' === $active_tab ) : ?>
            <!-- Delivery & Pickup Settings -->
            <?php
            global $wpdb;
            $prefix = $wpdb->prefix . MITZIES_JERK_TABLE_PREFIX;

            // Fetch existing delivery methods.
            $delivery_methods = $wpdb->get_results( "SELECT * FROM {$prefix}delivery_methods ORDER BY sort_order ASC" );
            if ( ! $delivery_methods ) {
                $delivery_methods = array();
            }

            // Fetch existing distance rates.
            $distance_rates = $wpdb->get_results( "SELECT * FROM {$prefix}distance_rates ORDER BY min_distance ASC" );
            if ( ! $distance_rates ) {
                $distance_rates = array();
            }

            // Fetch existing pickup locations.
            $pickup_locations = $wpdb->get_results( "SELECT * FROM {$prefix}pickup_locations ORDER BY sort_order ASC" );
            if ( ! $pickup_locations ) {
                $pickup_locations = array();
            }
            ?>

            <div class="mj-settings-section">
                <h2><?php esc_html_e( 'Delivery Methods', 'mitzies-jerk' ); ?></h2>
                <p class="description"><?php esc_html_e( 'Configure delivery and pickup methods available to customers.', 'mitzies-jerk' ); ?></p>

                <table class="widefat mj-delivery-methods-table" id="mj-delivery-methods-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Method Name', 'mitzies-jerk' ); ?></th>
                            <th><?php esc_html_e( 'Type', 'mitzies-jerk' ); ?></th>
                            <th><?php esc_html_e( 'Base Fee', 'mitzies-jerk' ); ?></th>
                            <th><?php esc_html_e( 'Extra Fee', 'mitzies-jerk' ); ?></th>
                            <th><?php esc_html_e( 'Est. Time', 'mitzies-jerk' ); ?></th>
                            <th><?php esc_html_e( 'Distance-Based', 'mitzies-jerk' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'mitzies-jerk' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'mitzies-jerk' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $delivery_methods as $index => $method ) : ?>
                            <tr data-id="<?php echo esc_attr( $method->id ); ?>">
                                <td><input type="text" name="mj_delivery_methods[<?php echo esc_attr( $index ); ?>][method_name]" value="<?php echo esc_attr( $method->method_name ); ?>" class="regular-text"></td>
                                <td>
                                    <select name="mj_delivery_methods[<?php echo esc_attr( $index ); ?>][method_type]">
                                        <option value="delivery" <?php selected( $method->method_type, 'delivery' ); ?>><?php esc_html_e( 'Delivery', 'mitzies-jerk' ); ?></option>
                                        <option value="pickup" <?php selected( $method->method_type, 'pickup' ); ?>><?php esc_html_e( 'Pickup', 'mitzies-jerk' ); ?></option>
                                    </select>
                                </td>
                                <td><input type="number" name="mj_delivery_methods[<?php echo esc_attr( $index ); ?>][base_fee]" value="<?php echo esc_attr( $method->base_fee ); ?>" step="0.01" min="0" class="small-text"></td>
                                <td><input type="number" name="mj_delivery_methods[<?php echo esc_attr( $index ); ?>][extra_fee]" value="<?php echo esc_attr( $method->extra_fee ); ?>" step="0.01" min="0" class="small-text"></td>
                                <td><input type="text" name="mj_delivery_methods[<?php echo esc_attr( $index ); ?>][estimated_time]" value="<?php echo esc_attr( $method->estimated_time ); ?>" placeholder="30-45 mins" class="small-text"></td>
                                <td><input type="checkbox" name="mj_delivery_methods[<?php echo esc_attr( $index ); ?>][is_distance_based]" value="1" <?php checked( $method->is_distance_based, 1 ); ?>></td>
                                <td>
                                    <select name="mj_delivery_methods[<?php echo esc_attr( $index ); ?>][status]">
                                        <option value="active" <?php selected( $method->status, 'active' ); ?>><?php esc_html_e( 'Active', 'mitzies-jerk' ); ?></option>
                                        <option value="inactive" <?php selected( $method->status, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'mitzies-jerk' ); ?></option>
                                    </select>
                                </td>
                                <td>
                                    <input type="hidden" name="mj_delivery_methods[<?php echo esc_attr( $index ); ?>][id]" value="<?php echo esc_attr( $method->id ); ?>">
                                    <input type="hidden" name="mj_delivery_methods[<?php echo esc_attr( $index ); ?>][sort_order]" value="<?php echo esc_attr( $method->sort_order ); ?>">
                                    <button type="button" class="button mj-remove-delivery-method">&times;</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><button type="button" class="button" id="mj-add-delivery-method"><?php esc_html_e( 'Add Delivery Method', 'mitzies-jerk' ); ?></button></p>
            </div>

            <div class="mj-settings-section">
                <h2><?php esc_html_e( 'Distance-Based Delivery Rates', 'mitzies-jerk' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Enable Distance Rates', 'mitzies-jerk' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="mitzies_jerk_settings[enable_distance_rates]" value="1" <?php checked( $options['enable_distance_rates'], true ); ?>>
                                <?php esc_html_e( 'Calculate delivery fees based on distance from store', 'mitzies-jerk' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="google_maps_api_key"><?php esc_html_e( 'Google Maps API Key', 'mitzies-jerk' ); ?></label></th>
                        <td>
                            <input type="text" name="mitzies_jerk_settings[google_maps_api_key]" id="google_maps_api_key"
                                   value="<?php echo esc_attr( $options['google_maps_api_key'] ); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e( 'Required for automatic distance calculation. Get your key from Google Cloud Console. If not set, manual delivery zones will be used.', 'mitzies-jerk' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="store_address"><?php esc_html_e( 'Store Address', 'mitzies-jerk' ); ?></label></th>
                        <td>
                            <input type="text" name="mitzies_jerk_settings[store_address]" id="store_address"
                                   value="<?php echo esc_attr( $options['store_address'] ); ?>" class="large-text"
                                   placeholder="<?php esc_attr_e( '123 Main Street, City, Country', 'mitzies-jerk' ); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Store Coordinates', 'mitzies-jerk' ); ?></th>
                        <td>
                            <label><?php esc_html_e( 'Latitude:', 'mitzies-jerk' ); ?>
                                <input type="text" name="mitzies_jerk_settings[store_latitude]" value="<?php echo esc_attr( $options['store_latitude'] ); ?>" class="small-text" placeholder="0.000000">
                            </label>
                            <label style="margin-left: 10px;"><?php esc_html_e( 'Longitude:', 'mitzies-jerk' ); ?>
                                <input type="text" name="mitzies_jerk_settings[store_longitude]" value="<?php echo esc_attr( $options['store_longitude'] ); ?>" class="small-text" placeholder="0.000000">
                            </label>
                            <p class="description"><?php esc_html_e( 'Used for fallback distance calculation without Google Maps API.', 'mitzies-jerk' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="distance_unit"><?php esc_html_e( 'Distance Unit', 'mitzies-jerk' ); ?></label></th>
                        <td>
                            <select name="mitzies_jerk_settings[distance_unit]" id="distance_unit">
                                <option value="km" <?php selected( $options['distance_unit'], 'km' ); ?>><?php esc_html_e( 'Kilometers (km)', 'mitzies-jerk' ); ?></option>
                                <option value="miles" <?php selected( $options['distance_unit'], 'miles' ); ?>><?php esc_html_e( 'Miles', 'mitzies-jerk' ); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>

                <h3><?php esc_html_e( 'Distance Rate Tiers', 'mitzies-jerk' ); ?></h3>
                <p class="description"><?php esc_html_e( 'Define delivery fee tiers based on distance ranges.', 'mitzies-jerk' ); ?></p>

                <table class="widefat mj-distance-rates-table" id="mj-distance-rates-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Min Distance', 'mitzies-jerk' ); ?></th>
                            <th><?php esc_html_e( 'Max Distance', 'mitzies-jerk' ); ?></th>
                            <th><?php esc_html_e( 'Delivery Fee', 'mitzies-jerk' ); ?></th>
                            <th><?php esc_html_e( 'Est. Time', 'mitzies-jerk' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'mitzies-jerk' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'mitzies-jerk' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $distance_rates as $index => $rate ) : ?>
                            <tr data-id="<?php echo esc_attr( $rate->id ); ?>">
                                <td><input type="number" name="mj_distance_rates[<?php echo esc_attr( $index ); ?>][min_distance]" value="<?php echo esc_attr( $rate->min_distance ); ?>" step="0.1" min="0" class="small-text"></td>
                                <td><input type="number" name="mj_distance_rates[<?php echo esc_attr( $index ); ?>][max_distance]" value="<?php echo esc_attr( $rate->max_distance ); ?>" step="0.1" min="0" class="small-text"></td>
                                <td><input type="number" name="mj_distance_rates[<?php echo esc_attr( $index ); ?>][delivery_fee]" value="<?php echo esc_attr( $rate->delivery_fee ); ?>" step="0.01" min="0" class="small-text"></td>
                                <td><input type="text" name="mj_distance_rates[<?php echo esc_attr( $index ); ?>][estimated_time]" value="<?php echo esc_attr( $rate->estimated_time ); ?>" placeholder="30-45 mins" class="small-text"></td>
                                <td>
                                    <select name="mj_distance_rates[<?php echo esc_attr( $index ); ?>][status]">
                                        <option value="active" <?php selected( $rate->status, 'active' ); ?>><?php esc_html_e( 'Active', 'mitzies-jerk' ); ?></option>
                                        <option value="inactive" <?php selected( $rate->status, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'mitzies-jerk' ); ?></option>
                                    </select>
                                </td>
                                <td>
                                    <input type="hidden" name="mj_distance_rates[<?php echo esc_attr( $index ); ?>][id]" value="<?php echo esc_attr( $rate->id ); ?>">
                                    <button type="button" class="button mj-remove-distance-rate">&times;</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><button type="button" class="button" id="mj-add-distance-rate"><?php esc_html_e( 'Add Distance Rate', 'mitzies-jerk' ); ?></button></p>
            </div>

            <div class="mj-settings-section">
                <h2><?php esc_html_e( 'Pickup Locations', 'mitzies-jerk' ); ?></h2>
                <p class="description"><?php esc_html_e( 'Add pickup locations where customers can collect their orders.', 'mitzies-jerk' ); ?></p>

                <table class="widefat mj-pickup-locations-table" id="mj-pickup-locations-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Location Name', 'mitzies-jerk' ); ?></th>
                            <th><?php esc_html_e( 'Address', 'mitzies-jerk' ); ?></th>
                            <th><?php esc_html_e( 'City', 'mitzies-jerk' ); ?></th>
                            <th><?php esc_html_e( 'Availability Hours', 'mitzies-jerk' ); ?></th>
                            <th><?php esc_html_e( 'Phone', 'mitzies-jerk' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'mitzies-jerk' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'mitzies-jerk' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $pickup_locations as $index => $location ) : ?>
                            <tr data-id="<?php echo esc_attr( $location->id ); ?>">
                                <td><input type="text" name="mj_pickup_locations[<?php echo esc_attr( $index ); ?>][location_name]" value="<?php echo esc_attr( $location->location_name ); ?>" class="regular-text"></td>
                                <td><input type="text" name="mj_pickup_locations[<?php echo esc_attr( $index ); ?>][address]" value="<?php echo esc_attr( $location->address ); ?>" class="regular-text"></td>
                                <td><input type="text" name="mj_pickup_locations[<?php echo esc_attr( $index ); ?>][city]" value="<?php echo esc_attr( $location->city ); ?>" class="small-text"></td>
                                <td><input type="text" name="mj_pickup_locations[<?php echo esc_attr( $index ); ?>][availability_hours]" value="<?php echo esc_attr( $location->availability_hours ); ?>" placeholder="Mon-Fri 9AM-6PM" class="regular-text"></td>
                                <td><input type="text" name="mj_pickup_locations[<?php echo esc_attr( $index ); ?>][phone]" value="<?php echo esc_attr( $location->phone ); ?>" class="small-text"></td>
                                <td>
                                    <select name="mj_pickup_locations[<?php echo esc_attr( $index ); ?>][status]">
                                        <option value="active" <?php selected( $location->status, 'active' ); ?>><?php esc_html_e( 'Active', 'mitzies-jerk' ); ?></option>
                                        <option value="inactive" <?php selected( $location->status, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'mitzies-jerk' ); ?></option>
                                    </select>
                                </td>
                                <td>
                                    <input type="hidden" name="mj_pickup_locations[<?php echo esc_attr( $index ); ?>][id]" value="<?php echo esc_attr( $location->id ); ?>">
                                    <button type="button" class="button mj-remove-pickup-location">&times;</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><button type="button" class="button" id="mj-add-pickup-location"><?php esc_html_e( 'Add Pickup Location', 'mitzies-jerk' ); ?></button></p>
            </div>

        <?php elseif ( 'payment' === $active_tab ) : ?>
            <!-- Payment Settings -->
            <div class="mj-settings-section">
                <h2><?php esc_html_e( 'Payment Gateways', 'mitzies-jerk' ); ?></h2>
                <p><?php esc_html_e( 'Enable and configure payment gateways for your store.', 'mitzies-jerk' ); ?></p>

                <?php
                $payment = new Mitzies_Jerk_Payment();
                $gateways = $payment->get_gateways();

                foreach ( $gateways as $gateway_id => $gateway ) :
                    $is_enabled = in_array( $gateway_id, $options['enabled_gateways'], true );
                ?>
                    <div class="mj-gateway-settings">
                        <h3>
                            <label>
                                <input type="checkbox" name="mitzies_jerk_settings[enabled_gateways][]"
                                       value="<?php echo esc_attr( $gateway_id ); ?>" <?php checked( $is_enabled ); ?>>
                                <?php echo esc_html( $gateway->get_title() ); ?>
                            </label>
                        </h3>
                        <div class="mj-gateway-fields" style="<?php echo ! $is_enabled ? 'display:none;' : ''; ?>">
                            <table class="form-table">
                                <?php
                                $fields = method_exists( $gateway, 'get_settings_fields' ) ? $gateway->get_settings_fields() : array();
                                foreach ( $fields as $field_id => $field ) :
                                    $field_name = $gateway_id . '_' . $field_id;
                                    $field_value = isset( $options[ $field_name ] ) ? $options[ $field_name ] : ( $field['default'] ?? '' );
                                ?>
                                    <tr>
                                        <th scope="row"><label for="<?php echo esc_attr( $field_name ); ?>"><?php echo esc_html( $field['title'] ); ?></label></th>
                                        <td>
                                            <?php if ( 'checkbox' === $field['type'] ) : ?>
                                                <label>
                                                    <input type="checkbox" name="mitzies_jerk_settings[<?php echo esc_attr( $field_name ); ?>]"
                                                           id="<?php echo esc_attr( $field_name ); ?>" value="1" <?php checked( $field_value, 1 ); ?>>
                                                    <?php echo esc_html( $field['label'] ?? '' ); ?>
                                                </label>
                                            <?php elseif ( 'password' === $field['type'] ) : ?>
                                                <input type="password" name="mitzies_jerk_settings[<?php echo esc_attr( $field_name ); ?>]"
                                                       id="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( $field_value ); ?>" class="regular-text">
                                            <?php elseif ( 'textarea' === $field['type'] ) : ?>
                                                <textarea name="mitzies_jerk_settings[<?php echo esc_attr( $field_name ); ?>]"
                                                          id="<?php echo esc_attr( $field_name ); ?>" rows="4" class="large-text"><?php echo esc_textarea( $field_value ); ?></textarea>
                                            <?php else : ?>
                                                <input type="text" name="mitzies_jerk_settings[<?php echo esc_attr( $field_name ); ?>]"
                                                       id="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( $field_value ); ?>" class="regular-text">
                                            <?php endif; ?>
                                            <?php if ( ! empty( $field['description'] ) ) : ?>
                                                <p class="description"><?php echo esc_html( $field['description'] ); ?></p>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ( 'email' === $active_tab ) :
            // Email template defaults.
            $email_template_defaults = array(
                'email_header_bg_color' => '#e74c3c',
                'email_header_text_color' => '#ffffff',
                'email_body_bg_color' => '#f5f5f5',
                'email_body_text_color' => '#333333',
                'email_footer_text' => sprintf( __( 'Thank you for ordering from %s!', 'mitzies-jerk' ), get_bloginfo( 'name' ) ),
                'email_new_order_subject' => __( 'New Order #{order_number} - {site_name}', 'mitzies-jerk' ),
                'email_new_order_heading' => __( 'New Order Received!', 'mitzies-jerk' ),
                'email_new_order_body' => __( "A new order has been placed on your store.\n\nOrder Number: {order_number}\nCustomer: {customer_name}\nEmail: {customer_email}\nTotal: {order_total}\nDelivery Date: {delivery_date}", 'mitzies-jerk' ),
                'email_order_confirmation_subject' => __( 'Order Confirmation #{order_number} - {site_name}', 'mitzies-jerk' ),
                'email_order_confirmation_heading' => __( 'Thank You for Your Order!', 'mitzies-jerk' ),
                'email_order_confirmation_body' => __( "Hi {customer_name},\n\nThank you for your order! We've received your order and will begin preparing it for delivery.\n\nOrder Number: {order_number}\nDelivery Date: {delivery_date}\nTotal: {order_total}\n\nWe'll send you another email when your order is ready for delivery.", 'mitzies-jerk' ),
                'email_order_status_subject' => __( 'Order #{order_number} Status Update - {site_name}', 'mitzies-jerk' ),
                'email_order_status_heading' => __( 'Order Status Update', 'mitzies-jerk' ),
                'email_order_status_body' => __( "Hi {customer_name},\n\nYour order #{order_number} status has been updated to: {order_status}\n\nYou can track your order anytime using your order number.\n\nThank you for choosing us!", 'mitzies-jerk' ),
                'email_order_ready_subject' => __( 'Your Order #{order_number} is Ready! - {site_name}', 'mitzies-jerk' ),
                'email_order_ready_heading' => __( 'Your Order is Ready for Delivery!', 'mitzies-jerk' ),
                'email_order_ready_body' => __( "Hi {customer_name},\n\nGreat news! Your order #{order_number} has been prepared and is ready for delivery.\n\nDelivery scheduled for: {delivery_date}\n\nPlease ensure someone is available to receive your order.\n\nThank you!", 'mitzies-jerk' ),
            );
            $options = wp_parse_args( $options, $email_template_defaults );
        ?>
            <!-- Email Settings -->
            <div class="mj-settings-section">
                <h2><?php esc_html_e( 'Email Configuration', 'mitzies-jerk' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="admin_email"><?php esc_html_e( 'Admin Email', 'mitzies-jerk' ); ?></label></th>
                        <td>
                            <input type="email" name="mitzies_jerk_settings[admin_email]" id="admin_email"
                                   value="<?php echo esc_attr( $options['admin_email'] ); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e( 'Email address for new order notifications.', 'mitzies-jerk' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="email_from_name"><?php esc_html_e( 'From Name', 'mitzies-jerk' ); ?></label></th>
                        <td>
                            <input type="text" name="mitzies_jerk_settings[email_from_name]" id="email_from_name"
                                   value="<?php echo esc_attr( $options['email_from_name'] ); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="email_from_address"><?php esc_html_e( 'From Email Address', 'mitzies-jerk' ); ?></label></th>
                        <td>
                            <input type="email" name="mitzies_jerk_settings[email_from_address]" id="email_from_address"
                                   value="<?php echo esc_attr( $options['email_from_address'] ); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>
            </div>

            <div class="mj-settings-section">
                <h2><?php esc_html_e( 'Email Appearance', 'mitzies-jerk' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="email_header_bg_color"><?php esc_html_e( 'Header Background Color', 'mitzies-jerk' ); ?></label></th>
                        <td>
                            <input type="text" name="mitzies_jerk_settings[email_header_bg_color]" id="email_header_bg_color"
                                   value="<?php echo esc_attr( $options['email_header_bg_color'] ); ?>" class="mj-color-picker">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="email_header_text_color"><?php esc_html_e( 'Header Text Color', 'mitzies-jerk' ); ?></label></th>
                        <td>
                            <input type="text" name="mitzies_jerk_settings[email_header_text_color]" id="email_header_text_color"
                                   value="<?php echo esc_attr( $options['email_header_text_color'] ); ?>" class="mj-color-picker">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="email_body_bg_color"><?php esc_html_e( 'Body Background Color', 'mitzies-jerk' ); ?></label></th>
                        <td>
                            <input type="text" name="mitzies_jerk_settings[email_body_bg_color]" id="email_body_bg_color"
                                   value="<?php echo esc_attr( $options['email_body_bg_color'] ); ?>" class="mj-color-picker">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="email_footer_text"><?php esc_html_e( 'Footer Text', 'mitzies-jerk' ); ?></label></th>
                        <td>
                            <input type="text" name="mitzies_jerk_settings[email_footer_text]" id="email_footer_text"
                                   value="<?php echo esc_attr( $options['email_footer_text'] ); ?>" class="large-text">
                        </td>
                    </tr>
                </table>
            </div>

            <div class="mj-settings-section">
                <h2><?php esc_html_e( 'Email Templates', 'mitzies-jerk' ); ?></h2>
                <p class="description"><?php esc_html_e( 'Customize email content. Available placeholders: {order_number}, {customer_name}, {customer_email}, {order_total}, {delivery_date}, {order_status}, {site_name}, {site_url}', 'mitzies-jerk' ); ?></p>

                <div class="mj-email-templates">
                    <!-- New Order (Admin) -->
                    <div class="mj-email-template">
                        <h3><?php esc_html_e( 'New Order Notification (Admin)', 'mitzies-jerk' ); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="email_new_order_subject"><?php esc_html_e( 'Subject', 'mitzies-jerk' ); ?></label></th>
                                <td>
                                    <input type="text" name="mitzies_jerk_settings[email_new_order_subject]" id="email_new_order_subject"
                                           value="<?php echo esc_attr( $options['email_new_order_subject'] ); ?>" class="large-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="email_new_order_heading"><?php esc_html_e( 'Heading', 'mitzies-jerk' ); ?></label></th>
                                <td>
                                    <input type="text" name="mitzies_jerk_settings[email_new_order_heading]" id="email_new_order_heading"
                                           value="<?php echo esc_attr( $options['email_new_order_heading'] ); ?>" class="large-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="email_new_order_body"><?php esc_html_e( 'Body', 'mitzies-jerk' ); ?></label></th>
                                <td>
                                    <textarea name="mitzies_jerk_settings[email_new_order_body]" id="email_new_order_body"
                                              rows="6" class="large-text"><?php echo esc_textarea( $options['email_new_order_body'] ); ?></textarea>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Order Confirmation (Customer) -->
                    <div class="mj-email-template">
                        <h3><?php esc_html_e( 'Order Confirmation (Customer)', 'mitzies-jerk' ); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="email_order_confirmation_subject"><?php esc_html_e( 'Subject', 'mitzies-jerk' ); ?></label></th>
                                <td>
                                    <input type="text" name="mitzies_jerk_settings[email_order_confirmation_subject]" id="email_order_confirmation_subject"
                                           value="<?php echo esc_attr( $options['email_order_confirmation_subject'] ); ?>" class="large-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="email_order_confirmation_heading"><?php esc_html_e( 'Heading', 'mitzies-jerk' ); ?></label></th>
                                <td>
                                    <input type="text" name="mitzies_jerk_settings[email_order_confirmation_heading]" id="email_order_confirmation_heading"
                                           value="<?php echo esc_attr( $options['email_order_confirmation_heading'] ); ?>" class="large-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="email_order_confirmation_body"><?php esc_html_e( 'Body', 'mitzies-jerk' ); ?></label></th>
                                <td>
                                    <textarea name="mitzies_jerk_settings[email_order_confirmation_body]" id="email_order_confirmation_body"
                                              rows="6" class="large-text"><?php echo esc_textarea( $options['email_order_confirmation_body'] ); ?></textarea>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Order Status Update (Customer) -->
                    <div class="mj-email-template">
                        <h3><?php esc_html_e( 'Order Status Update (Customer)', 'mitzies-jerk' ); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="email_order_status_subject"><?php esc_html_e( 'Subject', 'mitzies-jerk' ); ?></label></th>
                                <td>
                                    <input type="text" name="mitzies_jerk_settings[email_order_status_subject]" id="email_order_status_subject"
                                           value="<?php echo esc_attr( $options['email_order_status_subject'] ); ?>" class="large-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="email_order_status_heading"><?php esc_html_e( 'Heading', 'mitzies-jerk' ); ?></label></th>
                                <td>
                                    <input type="text" name="mitzies_jerk_settings[email_order_status_heading]" id="email_order_status_heading"
                                           value="<?php echo esc_attr( $options['email_order_status_heading'] ); ?>" class="large-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="email_order_status_body"><?php esc_html_e( 'Body', 'mitzies-jerk' ); ?></label></th>
                                <td>
                                    <textarea name="mitzies_jerk_settings[email_order_status_body]" id="email_order_status_body"
                                              rows="6" class="large-text"><?php echo esc_textarea( $options['email_order_status_body'] ); ?></textarea>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Order Ready (Customer) -->
                    <div class="mj-email-template">
                        <h3><?php esc_html_e( 'Order Ready for Delivery (Customer)', 'mitzies-jerk' ); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="email_order_ready_subject"><?php esc_html_e( 'Subject', 'mitzies-jerk' ); ?></label></th>
                                <td>
                                    <input type="text" name="mitzies_jerk_settings[email_order_ready_subject]" id="email_order_ready_subject"
                                           value="<?php echo esc_attr( $options['email_order_ready_subject'] ); ?>" class="large-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="email_order_ready_heading"><?php esc_html_e( 'Heading', 'mitzies-jerk' ); ?></label></th>
                                <td>
                                    <input type="text" name="mitzies_jerk_settings[email_order_ready_heading]" id="email_order_ready_heading"
                                           value="<?php echo esc_attr( $options['email_order_ready_heading'] ); ?>" class="large-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="email_order_ready_body"><?php esc_html_e( 'Body', 'mitzies-jerk' ); ?></label></th>
                                <td>
                                    <textarea name="mitzies_jerk_settings[email_order_ready_body]" id="email_order_ready_body"
                                              rows="6" class="large-text"><?php echo esc_textarea( $options['email_order_ready_body'] ); ?></textarea>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mj-settings-section">
                <h3><?php esc_html_e( 'Test Email', 'mitzies-jerk' ); ?></h3>
                <p>
                    <input type="email" id="mj-test-email" placeholder="<?php esc_attr_e( 'Enter email address', 'mitzies-jerk' ); ?>" class="regular-text">
                    <button type="button" class="button" id="mj-send-test-email"><?php esc_html_e( 'Send Test Email', 'mitzies-jerk' ); ?></button>
                    <span id="mj-test-email-result"></span>
                </p>
            </div>

        <?php elseif ( 'advanced' === $active_tab ) : ?>
            <!-- Advanced Settings -->
            <div class="mj-settings-section">
                <h2><?php esc_html_e( 'Advanced Settings', 'mitzies-jerk' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Debug Logging', 'mitzies-jerk' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="mitzies_jerk_settings[enable_logging]" value="1" <?php checked( $options['enable_logging'], true ); ?>>
                                <?php esc_html_e( 'Enable debug logging', 'mitzies-jerk' ); ?>
                            </label>
                            <p class="description"><?php esc_html_e( 'Log plugin events for debugging purposes.', 'mitzies-jerk' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Data Removal', 'mitzies-jerk' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="mitzies_jerk_settings[delete_data_on_uninstall]" value="1" <?php checked( $options['delete_data_on_uninstall'], true ); ?>>
                                <?php esc_html_e( 'Delete all plugin data on uninstall', 'mitzies-jerk' ); ?>
                            </label>
                            <p class="description" style="color: #d63638;"><?php esc_html_e( 'Warning: This will permanently delete all orders, food items, and settings when the plugin is uninstalled.', 'mitzies-jerk' ); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Plugin Pages', 'mitzies-jerk' ); ?></h2>
                <p class="description"><?php esc_html_e( 'Select the pages for each plugin function. Each page should contain the appropriate shortcode.', 'mitzies-jerk' ); ?></p>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="menu_page_id"><?php esc_html_e( 'Menu Page', 'mitzies-jerk' ); ?></label></th>
                        <td>
                            <?php
                            $menu_page_id = get_option( 'mitzies_jerk_menu_page_id' );
                            wp_dropdown_pages( array(
                                'name'              => 'mitzies_jerk_menu_page_id',
                                'id'                => 'menu_page_id',
                                'selected'          => $menu_page_id,
                                'show_option_none'  => __( '— Select —', 'mitzies-jerk' ),
                                'option_none_value' => '',
                            ) );
                            ?>
                            <p class="description"><?php esc_html_e( 'Page should contain [food_menu] shortcode', 'mitzies-jerk' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cart_page_id"><?php esc_html_e( 'Cart Page', 'mitzies-jerk' ); ?></label></th>
                        <td>
                            <?php
                            $cart_page_id = get_option( 'mitzies_jerk_cart_page_id' );
                            wp_dropdown_pages( array(
                                'name'              => 'mitzies_jerk_cart_page_id',
                                'id'                => 'cart_page_id',
                                'selected'          => $cart_page_id,
                                'show_option_none'  => __( '— Select —', 'mitzies-jerk' ),
                                'option_none_value' => '',
                            ) );
                            ?>
                            <p class="description"><?php esc_html_e( 'Page should contain [food_cart] shortcode', 'mitzies-jerk' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="checkout_page_id"><?php esc_html_e( 'Checkout Page', 'mitzies-jerk' ); ?></label></th>
                        <td>
                            <?php
                            $checkout_page_id = get_option( 'mitzies_jerk_checkout_page_id' );
                            wp_dropdown_pages( array(
                                'name'              => 'mitzies_jerk_checkout_page_id',
                                'id'                => 'checkout_page_id',
                                'selected'          => $checkout_page_id,
                                'show_option_none'  => __( '— Select —', 'mitzies-jerk' ),
                                'option_none_value' => '',
                            ) );
                            ?>
                            <p class="description"><?php esc_html_e( 'Page should contain [food_checkout] shortcode', 'mitzies-jerk' ); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        <?php endif; ?>

        <?php submit_button(); ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle gateway settings visibility.
    $('.mj-gateway-settings input[type="checkbox"]').on('change', function() {
        $(this).closest('.mj-gateway-settings').find('.mj-gateway-fields').toggle(this.checked);
    });

    // Add time slot.
    var slotIndex = <?php echo count( $options['delivery_time_slots'] ); ?>;
    $('#mj-add-time-slot').on('click', function() {
        var html = '<div class="mj-time-slot">' +
            '<input type="time" name="mitzies_jerk_settings[delivery_time_slots][' + slotIndex + '][start]">' +
            '<span>to</span>' +
            '<input type="time" name="mitzies_jerk_settings[delivery_time_slots][' + slotIndex + '][end]">' +
            '<button type="button" class="button mj-remove-slot">&times;</button>' +
            '</div>';
        $('#mj-time-slots').append(html);
        slotIndex++;
    });

    // Remove time slot.
    $(document).on('click', '.mj-remove-slot', function() {
        $(this).closest('.mj-time-slot').remove();
    });

    // Add delivery method.
    var deliveryMethodIndex = <?php echo count( isset( $delivery_methods ) ? $delivery_methods : array() ); ?>;
    $('#mj-add-delivery-method').on('click', function() {
        var html = '<tr>' +
            '<td><input type="text" name="mj_delivery_methods[' + deliveryMethodIndex + '][method_name]" value="" class="regular-text" placeholder="<?php esc_attr_e( 'Method name', 'mitzies-jerk' ); ?>"></td>' +
            '<td><select name="mj_delivery_methods[' + deliveryMethodIndex + '][method_type]"><option value="delivery"><?php esc_html_e( 'Delivery', 'mitzies-jerk' ); ?></option><option value="pickup"><?php esc_html_e( 'Pickup', 'mitzies-jerk' ); ?></option></select></td>' +
            '<td><input type="number" name="mj_delivery_methods[' + deliveryMethodIndex + '][base_fee]" value="0" step="0.01" min="0" class="small-text"></td>' +
            '<td><input type="number" name="mj_delivery_methods[' + deliveryMethodIndex + '][extra_fee]" value="0" step="0.01" min="0" class="small-text"></td>' +
            '<td><input type="text" name="mj_delivery_methods[' + deliveryMethodIndex + '][estimated_time]" value="" placeholder="30-45 mins" class="small-text"></td>' +
            '<td><input type="checkbox" name="mj_delivery_methods[' + deliveryMethodIndex + '][is_distance_based]" value="1"></td>' +
            '<td><select name="mj_delivery_methods[' + deliveryMethodIndex + '][status]"><option value="active"><?php esc_html_e( 'Active', 'mitzies-jerk' ); ?></option><option value="inactive"><?php esc_html_e( 'Inactive', 'mitzies-jerk' ); ?></option></select></td>' +
            '<td><input type="hidden" name="mj_delivery_methods[' + deliveryMethodIndex + '][id]" value="0"><input type="hidden" name="mj_delivery_methods[' + deliveryMethodIndex + '][sort_order]" value="' + deliveryMethodIndex + '"><button type="button" class="button mj-remove-delivery-method">&times;</button></td>' +
            '</tr>';
        $('#mj-delivery-methods-table tbody').append(html);
        deliveryMethodIndex++;
    });
    $(document).on('click', '.mj-remove-delivery-method', function() {
        $(this).closest('tr').remove();
    });

    // Add distance rate.
    var distanceRateIndex = <?php echo count( isset( $distance_rates ) ? $distance_rates : array() ); ?>;
    $('#mj-add-distance-rate').on('click', function() {
        var html = '<tr>' +
            '<td><input type="number" name="mj_distance_rates[' + distanceRateIndex + '][min_distance]" value="0" step="0.1" min="0" class="small-text"></td>' +
            '<td><input type="number" name="mj_distance_rates[' + distanceRateIndex + '][max_distance]" value="0" step="0.1" min="0" class="small-text"></td>' +
            '<td><input type="number" name="mj_distance_rates[' + distanceRateIndex + '][delivery_fee]" value="0" step="0.01" min="0" class="small-text"></td>' +
            '<td><input type="text" name="mj_distance_rates[' + distanceRateIndex + '][estimated_time]" value="" placeholder="30-45 mins" class="small-text"></td>' +
            '<td><select name="mj_distance_rates[' + distanceRateIndex + '][status]"><option value="active"><?php esc_html_e( 'Active', 'mitzies-jerk' ); ?></option><option value="inactive"><?php esc_html_e( 'Inactive', 'mitzies-jerk' ); ?></option></select></td>' +
            '<td><input type="hidden" name="mj_distance_rates[' + distanceRateIndex + '][id]" value="0"><button type="button" class="button mj-remove-distance-rate">&times;</button></td>' +
            '</tr>';
        $('#mj-distance-rates-table tbody').append(html);
        distanceRateIndex++;
    });
    $(document).on('click', '.mj-remove-distance-rate', function() {
        $(this).closest('tr').remove();
    });

    // Add pickup location.
    var pickupLocationIndex = <?php echo count( isset( $pickup_locations ) ? $pickup_locations : array() ); ?>;
    $('#mj-add-pickup-location').on('click', function() {
        var html = '<tr>' +
            '<td><input type="text" name="mj_pickup_locations[' + pickupLocationIndex + '][location_name]" value="" class="regular-text" placeholder="<?php esc_attr_e( 'Location name', 'mitzies-jerk' ); ?>"></td>' +
            '<td><input type="text" name="mj_pickup_locations[' + pickupLocationIndex + '][address]" value="" class="regular-text" placeholder="<?php esc_attr_e( 'Full address', 'mitzies-jerk' ); ?>"></td>' +
            '<td><input type="text" name="mj_pickup_locations[' + pickupLocationIndex + '][city]" value="" class="small-text"></td>' +
            '<td><input type="text" name="mj_pickup_locations[' + pickupLocationIndex + '][availability_hours]" value="" placeholder="Mon-Fri 9AM-6PM" class="regular-text"></td>' +
            '<td><input type="text" name="mj_pickup_locations[' + pickupLocationIndex + '][phone]" value="" class="small-text"></td>' +
            '<td><select name="mj_pickup_locations[' + pickupLocationIndex + '][status]"><option value="active"><?php esc_html_e( 'Active', 'mitzies-jerk' ); ?></option><option value="inactive"><?php esc_html_e( 'Inactive', 'mitzies-jerk' ); ?></option></select></td>' +
            '<td><input type="hidden" name="mj_pickup_locations[' + pickupLocationIndex + '][id]" value="0"><button type="button" class="button mj-remove-pickup-location">&times;</button></td>' +
            '</tr>';
        $('#mj-pickup-locations-table tbody').append(html);
        pickupLocationIndex++;
    });
    $(document).on('click', '.mj-remove-pickup-location', function() {
        $(this).closest('tr').remove();
    });

    // Send test email.
    $('#mj-send-test-email').on('click', function() {
        var email = $('#mj-test-email').val();
        var $result = $('#mj-test-email-result');

        if (!email) {
            $result.text('<?php esc_html_e( 'Please enter an email address.', 'mitzies-jerk' ); ?>').css('color', 'red');
            return;
        }

        $result.text('<?php esc_html_e( 'Sending...', 'mitzies-jerk' ); ?>').css('color', 'blue');

        $.post(ajaxurl, {
            action: 'mj_send_test_email',
            email: email,
            nonce: mitziesJerkAdmin.nonce
        }, function(response) {
            if (response.success) {
                $result.text('<?php esc_html_e( 'Test email sent!', 'mitzies-jerk' ); ?>').css('color', 'green');
            } else {
                $result.text(response.data || '<?php esc_html_e( 'Failed to send email.', 'mitzies-jerk' ); ?>').css('color', 'red');
            }
        });
    });
});
</script>
