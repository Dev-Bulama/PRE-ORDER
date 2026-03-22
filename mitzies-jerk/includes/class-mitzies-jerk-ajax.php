<?php
/**
 * AJAX handler class.
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
 * AJAX handler class.
 */
class Mitzies_Jerk_Ajax {

    /**
     * Add to cart.
     */
    public function add_to_cart() {
        check_ajax_referer( 'mj_ajax_nonce', 'nonce' );

        // Accept both 'food_item_id' and 'item_id' for compatibility.
        $food_item_id = 0;
        if ( isset( $_POST['food_item_id'] ) ) {
            $food_item_id = absint( $_POST['food_item_id'] );
        } elseif ( isset( $_POST['item_id'] ) ) {
            $food_item_id = absint( $_POST['item_id'] );
        }

        $quantity = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 1;

        // Accept 'addons' or 'options' for compatibility.
        $addons = array();
        if ( isset( $_POST['addons'] ) && is_array( $_POST['addons'] ) ) {
            // Handle new format where addons[id] = 1 (checked).
            foreach ( $_POST['addons'] as $addon_id => $checked ) {
                if ( $checked ) {
                    $addons[] = absint( $addon_id );
                }
            }
        } elseif ( isset( $_POST['options'] ) && is_array( $_POST['options'] ) ) {
            $addons = array_map( 'absint', (array) $_POST['options'] );
        }

        // Handle extras.
        $extras = array();
        if ( isset( $_POST['extras'] ) && is_array( $_POST['extras'] ) ) {
            foreach ( $_POST['extras'] as $group_id => $selected ) {
                $extras[ sanitize_text_field( $group_id ) ] = is_array( $selected )
                    ? array_map( 'absint', $selected )
                    : absint( $selected );
            }
        }

        // Handle special instructions.
        $special_instructions = '';
        if ( isset( $_POST['special_instructions'] ) ) {
            $special_instructions = sanitize_textarea_field( wp_unslash( $_POST['special_instructions'] ) );
        }

        if ( ! $food_item_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid food item.', 'mitzies-jerk' ) ) );
        }

        global $mitzies_jerk;

        if ( ! $mitzies_jerk || ! $mitzies_jerk->cart ) {
            wp_send_json_error( array( 'message' => __( 'Cart not initialized.', 'mitzies-jerk' ) ) );
        }

        // Build cart item data.
        $cart_item_data = array(
            'extras'               => $extras,
            'special_instructions' => $special_instructions,
        );

        $result = $mitzies_jerk->cart->add_to_cart( $food_item_id, $quantity, $addons, $cart_item_data );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        $cart_data = $mitzies_jerk->cart->get_cart_for_display();

        wp_send_json_success( array(
            'message'    => __( 'Item added to cart!', 'mitzies-jerk' ),
            'cart_count' => $mitzies_jerk->cart->get_cart_count(),
            'cart'       => $cart_data,
        ) );
    }

    /**
     * Update cart.
     */
    public function update_cart() {
        check_ajax_referer( 'mj_ajax_nonce', 'nonce' );

        // Accept both cart_item_key and item_key for compatibility.
        $cart_item_key = '';
        if ( isset( $_POST['cart_item_key'] ) ) {
            $cart_item_key = sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) );
        } elseif ( isset( $_POST['item_key'] ) ) {
            $cart_item_key = sanitize_text_field( wp_unslash( $_POST['item_key'] ) );
        }
        $quantity = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 0;

        if ( ! $cart_item_key ) {
            wp_send_json_error( array( 'message' => __( 'Invalid cart item.', 'mitzies-jerk' ) ) );
        }

        global $mitzies_jerk;
        $result = $mitzies_jerk->cart->update_quantity( $cart_item_key, $quantity );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        wp_send_json_success( array(
            'message'    => __( 'Cart updated!', 'mitzies-jerk' ),
            'cart_count' => $mitzies_jerk->cart->get_cart_count(),
            'cart'       => $mitzies_jerk->cart->get_cart_for_display(),
        ) );
    }

    /**
     * Remove from cart.
     */
    public function remove_from_cart() {
        check_ajax_referer( 'mj_ajax_nonce', 'nonce' );

        // Accept both cart_item_key and item_key for compatibility.
        $cart_item_key = '';
        if ( isset( $_POST['cart_item_key'] ) ) {
            $cart_item_key = sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) );
        } elseif ( isset( $_POST['item_key'] ) ) {
            $cart_item_key = sanitize_text_field( wp_unslash( $_POST['item_key'] ) );
        }

        if ( ! $cart_item_key ) {
            wp_send_json_error( array( 'message' => __( 'Invalid cart item.', 'mitzies-jerk' ) ) );
        }

        global $mitzies_jerk;
        $mitzies_jerk->cart->remove_from_cart( $cart_item_key );

        wp_send_json_success( array(
            'message'    => __( 'Item removed!', 'mitzies-jerk' ),
            'cart_count' => $mitzies_jerk->cart->get_cart_count(),
            'cart'       => $mitzies_jerk->cart->get_cart_for_display(),
        ) );
    }

    /**
     * Get cart.
     */
    public function get_cart() {
        global $mitzies_jerk;

        wp_send_json_success( array(
            'cart_count' => $mitzies_jerk->cart->get_cart_count(),
            'cart'       => $mitzies_jerk->cart->get_cart_for_display(),
        ) );
    }

    /**
     * Apply coupon.
     */
    public function apply_coupon() {
        check_ajax_referer( 'mj_ajax_nonce', 'nonce' );

        $coupon_code = isset( $_POST['coupon_code'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) ) : '';

        if ( ! $coupon_code ) {
            wp_send_json_error( __( 'Please enter a coupon code.', 'mitzies-jerk' ) );
        }

        global $mitzies_jerk;
        $result = $mitzies_jerk->cart->apply_coupon( $coupon_code );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        wp_send_json_success( array(
            'message' => __( 'Coupon applied!', 'mitzies-jerk' ),
            'cart'    => $mitzies_jerk->cart->get_cart_for_display(),
        ) );
    }

    /**
     * Process checkout.
     */
    public function process_checkout() {
        check_ajax_referer( 'mj_ajax_nonce', 'nonce' );

        try {
            $posted_data = array();
            $fields = array(
                'first_name', 'last_name', 'email', 'phone',
                'address_1', 'address_2', 'city', 'state', 'postcode',
                'delivery_date', 'delivery_time', 'instructions',
                'payment_method', 'delivery_method', 'pickup_method',
                'pickup_location'
            );

            foreach ( $fields as $field ) {
                if ( isset( $_POST[ $field ] ) ) {
                    $posted_data[ $field ] = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
                }
            }

            $checkout = new Mitzies_Jerk_Checkout();
            $result = $checkout->process_checkout( $posted_data );

            if ( is_wp_error( $result ) ) {
                wp_send_json_error( array( 'message' => $result->get_error_message() ) );
                return;
            }

            // Flatten the response for JS compatibility.
            $response = array(
                'success'  => true,
                'order_id' => $result['order_id'],
                'message'  => __( 'Order placed successfully!', 'mitzies-jerk' ),
            );

            // Extract redirect URL from payment result.
            if ( isset( $result['payment']['redirect'] ) ) {
                $response['redirect_url'] = $result['payment']['redirect'];
            } elseif ( isset( $result['payment']['payment_url'] ) ) {
                $response['redirect_url'] = $result['payment']['payment_url'];
            }

            // Always provide a fallback redirect URL to the order received page.
            if ( empty( $response['redirect_url'] ) ) {
                $order_received_page = get_option( 'mitzies_jerk_order_received_page_id' );
                if ( $order_received_page ) {
                    $response['redirect_url'] = add_query_arg(
                        array(
                            'order_id' => $result['order_id'],
                        ),
                        get_permalink( $order_received_page )
                    );
                } else {
                    $response['redirect_url'] = home_url( '/' );
                }
            }

            // Include payment result status.
            if ( isset( $result['payment']['result'] ) ) {
                $response['result'] = $result['payment']['result'];
            }

            wp_send_json_success( $response );
        } catch ( \Exception $e ) {
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        } catch ( \Error $e ) {
            wp_send_json_error( array( 'message' => __( 'A server error occurred. Please try again.', 'mitzies-jerk' ) ) );
        }
    }

    /**
     * Check if a database table exists.
     *
     * @param string $table_name Full table name.
     * @return bool
     */
    private function table_exists( $table_name ) {
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;
    }

    /**
     * Calculate delivery fee based on address/distance.
     */
    public function calculate_delivery_fee() {
        check_ajax_referer( 'mj_ajax_nonce', 'nonce' );

        $address = isset( $_POST['address'] ) ? sanitize_text_field( wp_unslash( $_POST['address'] ) ) : '';
        $city = isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '';
        $delivery_method_id = isset( $_POST['delivery_method_id'] ) ? absint( $_POST['delivery_method_id'] ) : 0;

        global $wpdb;
        $prefix = $wpdb->prefix . MITZIES_JERK_TABLE_PREFIX;

        // Check if tables exist.
        $methods = array();
        if ( $this->table_exists( $prefix . 'delivery_methods' ) ) {
            $methods = $wpdb->get_results(
                "SELECT * FROM {$prefix}delivery_methods WHERE status = 'active' ORDER BY sort_order ASC"
            );
        }

        // Legacy: get available delivery methods if table doesn't exist or is empty.
        if ( empty( $methods ) ) {
            $default_fee = mitzies_jerk_get_option( 'delivery_fee', 5.00 );
            wp_send_json_success( array(
                'methods'       => array(
                    array(
                        'id'             => 0,
                        'name'           => __( 'Standard Delivery', 'mitzies-jerk' ),
                        'type'           => 'delivery',
                        'fee'            => $default_fee,
                        'formatted_fee'  => mitzies_jerk_format_price( $default_fee ),
                        'estimated_time' => '30-45 mins',
                        'locations'      => array(),
                    ),
                ),
                'distance'      => null,
                'distance_unit' => mitzies_jerk_get_option( 'distance_unit', 'km' ),
            ) );
            return;
        }

        $enable_distance_rates = mitzies_jerk_get_option( 'enable_distance_rates', false );
        $distance = 0;
        $distance_calculated = false;

        // Try to calculate distance if enabled.
        if ( $enable_distance_rates && ! empty( $address ) ) {
            $distance = $this->calculate_distance( $address . ', ' . $city );
            if ( $distance > 0 ) {
                $distance_calculated = true;
            }
        }

        // Build response with delivery options and prices.
        $options = array();
        $currency_symbol = mitzies_jerk_get_option( 'currency_symbol', '$' );

        foreach ( $methods as $method ) {
            $fee = floatval( $method->base_fee );

            if ( $method->is_distance_based && $distance_calculated ) {
                // Get distance-based rate.
                $rate = $wpdb->get_row( $wpdb->prepare(
                    "SELECT * FROM {$prefix}distance_rates WHERE min_distance <= %f AND max_distance >= %f AND status = 'active' ORDER BY min_distance ASC LIMIT 1",
                    $distance,
                    $distance
                ) );

                if ( $rate ) {
                    $fee = floatval( $rate->delivery_fee );
                }

                // Add extra fee for the method (e.g., express surcharge).
                $fee += floatval( $method->extra_fee );
            }

            // Get estimated time.
            $estimated_time = $method->estimated_time;
            if ( $method->is_distance_based && $distance_calculated ) {
                $rate = $wpdb->get_row( $wpdb->prepare(
                    "SELECT estimated_time FROM {$prefix}distance_rates WHERE min_distance <= %f AND max_distance >= %f AND status = 'active' LIMIT 1",
                    $distance,
                    $distance
                ) );
                if ( $rate && ! empty( $rate->estimated_time ) ) {
                    $estimated_time = $rate->estimated_time;
                }
            }

            // For pickup methods, get available locations.
            $locations = array();
            if ( 'pickup' === $method->method_type && $this->table_exists( $prefix . 'pickup_locations' ) ) {
                $locations = $wpdb->get_results(
                    "SELECT * FROM {$prefix}pickup_locations WHERE status = 'active' ORDER BY sort_order ASC"
                );
            }

            $options[] = array(
                'id'             => $method->id,
                'name'           => $method->method_name,
                'type'           => $method->method_type,
                'fee'            => $fee,
                'formatted_fee'  => mitzies_jerk_format_price( $fee ),
                'estimated_time' => $estimated_time,
                'locations'      => $locations,
            );
        }

        // If no methods configured, fall back to default.
        if ( empty( $options ) ) {
            $default_fee = mitzies_jerk_get_option( 'delivery_fee', 5.00 );
            $options[] = array(
                'id'             => 0,
                'name'           => __( 'Standard Delivery', 'mitzies-jerk' ),
                'type'           => 'delivery',
                'fee'            => $default_fee,
                'formatted_fee'  => mitzies_jerk_format_price( $default_fee ),
                'estimated_time' => '30-45 mins',
                'locations'      => array(),
            );
        }

        wp_send_json_success( array(
            'methods'     => $options,
            'distance'    => $distance_calculated ? round( $distance, 2 ) : null,
            'distance_unit' => mitzies_jerk_get_option( 'distance_unit', 'km' ),
        ) );
    }

    /**
     * Calculate distance between store and customer address.
     *
     * @param string $customer_address Customer address string.
     * @return float Distance in configured units.
     */
    private function calculate_distance( $customer_address ) {
        $api_key = mitzies_jerk_get_option( 'google_maps_api_key', '' );
        $store_lat = mitzies_jerk_get_option( 'store_latitude', '' );
        $store_lng = mitzies_jerk_get_option( 'store_longitude', '' );
        $unit = mitzies_jerk_get_option( 'distance_unit', 'km' );

        // Try Google Maps Distance Matrix API first.
        if ( ! empty( $api_key ) ) {
            $store_address = mitzies_jerk_get_option( 'store_address', '' );
            $origins = ! empty( $store_address ) ? urlencode( $store_address ) : $store_lat . ',' . $store_lng;
            $destinations = urlencode( $customer_address );

            $url = 'https://maps.googleapis.com/maps/api/distancematrix/json?origins=' . $origins . '&destinations=' . $destinations . '&units=' . ( 'miles' === $unit ? 'imperial' : 'metric' ) . '&key=' . $api_key;

            $response = wp_remote_get( $url, array( 'timeout' => 10 ) );

            if ( ! is_wp_error( $response ) ) {
                $body = json_decode( wp_remote_retrieve_body( $response ), true );

                if ( isset( $body['rows'][0]['elements'][0]['distance']['value'] ) ) {
                    $distance_meters = $body['rows'][0]['elements'][0]['distance']['value'];
                    return 'miles' === $unit ? $distance_meters / 1609.34 : $distance_meters / 1000;
                }
            }
        }

        // Fallback: Haversine formula if coordinates are available.
        if ( ! empty( $store_lat ) && ! empty( $store_lng ) ) {
            // Try to geocode the customer address using a simple approach.
            // In production, this would use the Google Geocoding API.
            return 0;
        }

        return 0;
    }

    /**
     * Get delivery methods for checkout.
     */
    public function get_delivery_methods() {
        check_ajax_referer( 'mj_ajax_nonce', 'nonce' );

        global $wpdb;
        $prefix = $wpdb->prefix . MITZIES_JERK_TABLE_PREFIX;

        $methods = array();
        $pickup_locations = array();

        if ( $this->table_exists( $prefix . 'delivery_methods' ) ) {
            $methods = $wpdb->get_results(
                "SELECT * FROM {$prefix}delivery_methods WHERE status = 'active' ORDER BY sort_order ASC"
            );
        }

        if ( $this->table_exists( $prefix . 'pickup_locations' ) ) {
            $pickup_locations = $wpdb->get_results(
                "SELECT * FROM {$prefix}pickup_locations WHERE status = 'active' ORDER BY sort_order ASC"
            );
        }

        // If no methods configured, return defaults.
        if ( empty( $methods ) ) {
            $default_fee = mitzies_jerk_get_option( 'delivery_fee', 5.00 );
            $methods = array(
                (object) array(
                    'id'             => 0,
                    'method_name'    => __( 'Standard Delivery', 'mitzies-jerk' ),
                    'method_type'    => 'delivery',
                    'base_fee'       => $default_fee,
                    'extra_fee'      => 0,
                    'estimated_time' => '30-45 mins',
                    'is_distance_based' => 0,
                    'status'         => 'active',
                ),
            );
        }

        $result = array();
        foreach ( $methods as $method ) {
            $item = array(
                'id'             => $method->id,
                'name'           => $method->method_name,
                'type'           => $method->method_type,
                'fee'            => floatval( $method->base_fee ),
                'formatted_fee'  => mitzies_jerk_format_price( $method->base_fee ),
                'estimated_time' => $method->estimated_time,
                'is_distance_based' => (bool) $method->is_distance_based,
            );

            if ( 'pickup' === $method->method_type ) {
                $item['locations'] = $pickup_locations;
            }

            $result[] = $item;
        }

        wp_send_json_success( array(
            'methods'   => $result,
            'locations' => $pickup_locations,
        ) );
    }

    /**
     * Validate delivery date/time.
     */
    public function validate_delivery() {
        check_ajax_referer( 'mj_ajax_nonce', 'nonce' );

        $date = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
        $time = isset( $_POST['time'] ) ? sanitize_text_field( wp_unslash( $_POST['time'] ) ) : '';

        if ( ! $date || ! $time ) {
            wp_send_json_error( __( 'Please select a delivery date and time.', 'mitzies-jerk' ) );
        }

        $datetime = $date . ' ' . explode( '-', $time )[0];
        $result = mitzies_jerk_validate_delivery_datetime( $datetime );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        wp_send_json_success( __( 'Valid delivery date and time.', 'mitzies-jerk' ) );
    }

    /**
     * Payment callback.
     */
    public function payment_callback() {
        $gateway = isset( $_GET['gateway'] ) ? sanitize_text_field( wp_unslash( $_GET['gateway'] ) ) : '';

        if ( ! $gateway ) {
            wp_die( __( 'Invalid payment gateway.', 'mitzies-jerk' ) );
        }

        $payment = new Mitzies_Jerk_Payment();
        $result = $payment->handle_callback( $gateway );

        if ( is_wp_error( $result ) ) {
            wp_redirect( add_query_arg( 'payment_error', urlencode( $result->get_error_message() ), get_permalink( get_option( 'mitzies_jerk_checkout_page_id' ) ) ) );
            exit;
        }

        if ( isset( $result['redirect'] ) ) {
            wp_redirect( $result['redirect'] );
            exit;
        }
    }

    /**
     * Track order.
     */
    public function track_order() {
        $order_number = isset( $_POST['order_number'] ) ? sanitize_text_field( wp_unslash( $_POST['order_number'] ) ) : '';
        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

        if ( ! $order_number ) {
            wp_send_json_error( array( 'message' => __( 'Please enter your order number.', 'mitzies-jerk' ) ) );
        }

        $order = Mitzies_Jerk_Order::get_by_order_number( $order_number );

        if ( ! $order ) {
            wp_send_json_error( array( 'message' => __( 'Order not found. Please check your order number and try again.', 'mitzies-jerk' ) ) );
        }

        // Verify email if provided.
        $billing = $order->get( 'billing' );
        if ( $email ) {
            if ( strtolower( $billing['email'] ) !== strtolower( $email ) ) {
                wp_send_json_error( array( 'message' => __( 'Email does not match the order. Please verify your email address.', 'mitzies-jerk' ) ) );
            }
        }

        $statuses = mitzies_jerk_get_order_statuses();
        $current_status = $order->get( 'status' );
        $status_label = isset( $statuses[ $current_status ] ) ? $statuses[ $current_status ] : $current_status;
        $delivery_datetime = $order->get( 'delivery_datetime' );
        $created_at = $order->get( 'created_at' );
        $total = $order->get( 'total' );
        $items = $order->get_items();
        $delivery = $order->get( 'delivery' );
        $payment_method = $order->get( 'payment_method' );

        // Define status order for progress tracking.
        $status_order = array( 'mj-pending', 'mj-paid', 'mj-processing', 'mj-preparing', 'mj-ready', 'mj-delivering', 'mj-completed' );
        $current_index = array_search( $current_status, $status_order );
        if ( false === $current_index ) {
            $current_index = -1; // For cancelled/refunded/failed.
        }

        // Build HTML response.
        ob_start();
        ?>
        <div class="mj-tracking-result-content">
            <div class="mj-tracking-header">
                <div class="mj-tracking-order-info">
                    <h3><?php esc_html_e( 'Order', 'mitzies-jerk' ); ?> #<?php echo esc_html( $order->get( 'order_number' ) ); ?></h3>
                    <span class="mj-tracking-status mj-status-<?php echo esc_attr( $current_status ); ?>">
                        <?php echo esc_html( $status_label ); ?>
                    </span>
                </div>
                <div class="mj-tracking-date">
                    <?php esc_html_e( 'Placed on', 'mitzies-jerk' ); ?>
                    <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $created_at ) ) ); ?>
                </div>
            </div>

            <?php if ( ! in_array( $current_status, array( 'mj-cancelled', 'mj-refunded', 'mj-failed', 'mj-expired' ), true ) ) : ?>
            <div class="mj-tracking-progress">
                <div class="mj-progress-bar">
                    <?php
                    $display_statuses = array(
                        'mj-pending'    => __( 'Pending', 'mitzies-jerk' ),
                        'mj-paid'       => __( 'Paid', 'mitzies-jerk' ),
                        'mj-preparing'  => __( 'Preparing', 'mitzies-jerk' ),
                        'mj-ready'      => __( 'Ready', 'mitzies-jerk' ),
                        'mj-delivering' => __( 'Delivering', 'mitzies-jerk' ),
                        'mj-completed'  => __( 'Completed', 'mitzies-jerk' ),
                    );
                    $step = 0;
                    foreach ( $display_statuses as $status_key => $status_name ) :
                        $step++;
                        $status_index = array_search( $status_key, $status_order );
                        $is_completed = $status_index !== false && $current_index >= $status_index;
                        $is_current = $status_key === $current_status;
                    ?>
                        <div class="mj-progress-step <?php echo $is_completed ? 'completed' : ''; ?> <?php echo $is_current ? 'current' : ''; ?>">
                            <div class="mj-step-icon">
                                <?php if ( $is_completed && ! $is_current ) : ?>
                                    <span class="dashicons dashicons-yes"></span>
                                <?php else : ?>
                                    <span class="step-number"><?php echo esc_html( $step ); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="mj-step-label"><?php echo esc_html( $status_name ); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="mj-tracking-details">
                <div class="mj-tracking-detail-grid">
                    <div class="mj-tracking-detail-card">
                        <div class="mj-detail-icon"><span class="dashicons dashicons-calendar-alt"></span></div>
                        <div class="mj-detail-content">
                            <span class="mj-detail-label"><?php esc_html_e( 'Delivery Date', 'mitzies-jerk' ); ?></span>
                            <span class="mj-detail-value">
                                <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $delivery_datetime ) ) ); ?>
                            </span>
                        </div>
                    </div>

                    <div class="mj-tracking-detail-card">
                        <div class="mj-detail-icon"><span class="dashicons dashicons-money-alt"></span></div>
                        <div class="mj-detail-content">
                            <span class="mj-detail-label"><?php esc_html_e( 'Total Amount', 'mitzies-jerk' ); ?></span>
                            <span class="mj-detail-value"><?php echo esc_html( mitzies_jerk_format_price( $total ) ); ?></span>
                        </div>
                    </div>

                    <div class="mj-tracking-detail-card">
                        <div class="mj-detail-icon"><span class="dashicons dashicons-credit-card"></span></div>
                        <div class="mj-detail-content">
                            <span class="mj-detail-label"><?php esc_html_e( 'Payment', 'mitzies-jerk' ); ?></span>
                            <span class="mj-detail-value"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $payment_method ) ) ); ?></span>
                        </div>
                    </div>

                    <div class="mj-tracking-detail-card">
                        <div class="mj-detail-icon"><span class="dashicons dashicons-location"></span></div>
                        <div class="mj-detail-content">
                            <span class="mj-detail-label"><?php esc_html_e( 'Delivery To', 'mitzies-jerk' ); ?></span>
                            <span class="mj-detail-value">
                                <?php
                                if ( ! empty( $delivery['address_1'] ) ) {
                                    echo esc_html( $delivery['address_1'] );
                                    if ( ! empty( $delivery['city'] ) ) {
                                        echo ', ' . esc_html( $delivery['city'] );
                                    }
                                } else {
                                    esc_html_e( 'Not specified', 'mitzies-jerk' );
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                </div>

                <?php if ( ! empty( $items ) ) : ?>
                <div class="mj-tracking-items">
                    <h4><?php esc_html_e( 'Order Items', 'mitzies-jerk' ); ?></h4>
                    <div class="mj-tracking-items-list">
                        <?php foreach ( $items as $item ) :
                            $food_item = get_post( $item->food_item_id );
                            $thumbnail = get_the_post_thumbnail_url( $item->food_item_id, 'thumbnail' );
                        ?>
                        <div class="mj-tracking-item">
                            <?php if ( $thumbnail ) : ?>
                                <img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php echo esc_attr( $food_item ? $food_item->post_title : '' ); ?>" class="mj-tracking-item-img">
                            <?php else : ?>
                                <div class="mj-tracking-item-img mj-no-img"><span class="dashicons dashicons-food"></span></div>
                            <?php endif; ?>
                            <div class="mj-tracking-item-info">
                                <span class="mj-tracking-item-name">
                                    <?php echo $food_item ? esc_html( $food_item->post_title ) : esc_html__( 'Item', 'mitzies-jerk' ); ?>
                                </span>
                                <span class="mj-tracking-item-qty">x<?php echo esc_html( $item->quantity ); ?></span>
                            </div>
                            <div class="mj-tracking-item-price">
                                <?php echo esc_html( mitzies_jerk_format_price( $item->subtotal ) ); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="mj-tracking-contact">
                    <p>
                        <span class="dashicons dashicons-phone"></span>
                        <?php esc_html_e( 'Need help? Contact us for assistance with your order.', 'mitzies-jerk' ); ?>
                    </p>
                </div>
            </div>
        </div>
        <?php
        $html = ob_get_clean();

        wp_send_json_success( array(
            'html'              => $html,
            'order_number'      => $order->get( 'order_number' ),
            'status'            => $current_status,
            'status_label'      => $status_label,
            'delivery_datetime' => $delivery_datetime,
            'total'             => mitzies_jerk_format_price( $total ),
            'created_at'        => $created_at,
        ) );
    }

    /**
     * Load more items.
     */
    public function load_more_items() {
        $page = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
        $per_page = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 12;
        $category = isset( $_POST['category'] ) ? absint( $_POST['category'] ) : 0;

        $args = array(
            'post_type'      => 'mj_food_item',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
        );

        if ( $category ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'mj_food_category',
                    'terms'    => $category,
                ),
            );
        }

        $query = new WP_Query( $args );
        $items = array();

        while ( $query->have_posts() ) {
            $query->the_post();
            $items[] = $this->get_food_item_data( get_the_ID() );
        }

        wp_reset_postdata();

        wp_send_json_success( array(
            'items'       => $items,
            'has_more'    => $query->max_num_pages > $page,
            'total_pages' => $query->max_num_pages,
        ) );
    }

    /**
     * Filter items.
     */
    public function filter_items() {
        $category = isset( $_POST['category'] ) ? absint( $_POST['category'] ) : 0;
        $search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
        $sort = isset( $_POST['sort'] ) ? sanitize_text_field( wp_unslash( $_POST['sort'] ) ) : 'date';
        $per_page = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 12;

        $args = array(
            'post_type'      => 'mj_food_item',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
        );

        if ( $category ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'mj_food_category',
                    'terms'    => $category,
                ),
            );
        }

        if ( $search ) {
            $args['s'] = $search;
        }

        switch ( $sort ) {
            case 'price_low':
                $args['meta_key'] = '_mj_price';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'ASC';
                break;
            case 'price_high':
                $args['meta_key'] = '_mj_price';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            case 'name':
                $args['orderby'] = 'title';
                $args['order'] = 'ASC';
                break;
            case 'popular':
                $args['meta_key'] = '_mj_order_count';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            default:
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
        }

        $query = new WP_Query( $args );

        // Generate HTML output.
        ob_start();
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $this->render_food_item_html( get_the_ID() );
            }
        } else {
            echo '<p class="mj-no-items">' . esc_html__( 'No food items found.', 'mitzies-jerk' ) . '</p>';
        }
        $html = ob_get_clean();

        wp_reset_postdata();

        wp_send_json_success( array(
            'html'        => $html,
            'total'       => $query->found_posts,
            'total_pages' => $query->max_num_pages,
        ) );
    }

    /**
     * Render food item HTML for AJAX responses.
     *
     * @param int $post_id Post ID.
     */
    private function render_food_item_html( $post_id ) {
        $price = get_post_meta( $post_id, '_mj_price', true );
        $sale_price = get_post_meta( $post_id, '_mj_sale_price', true );
        $stock_status = get_post_meta( $post_id, '_mj_stock_status', true );
        $is_featured = get_post_meta( $post_id, '_mj_is_featured', true );
        $addons = Mitzies_Jerk_Database::get_food_addons( $post_id );
        $display_price = $sale_price ? $sale_price : $price;
        ?>
        <div class="mj-food-item<?php echo $is_featured ? ' featured' : ''; ?><?php echo 'outofstock' === $stock_status ? ' out-of-stock' : ''; ?>">
            <div class="mj-food-image">
                <a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
                    <?php if ( has_post_thumbnail( $post_id ) ) : ?>
                        <?php echo get_the_post_thumbnail( $post_id, 'medium' ); ?>
                    <?php else : ?>
                        <div class="mj-no-image"><span class="dashicons dashicons-food"></span></div>
                    <?php endif; ?>
                </a>
                <?php if ( $sale_price ) : ?>
                    <span class="mj-sale-badge"><?php esc_html_e( 'Sale!', 'mitzies-jerk' ); ?></span>
                <?php endif; ?>
                <?php if ( $is_featured ) : ?>
                    <span class="mj-featured-badge"><?php esc_html_e( 'Featured', 'mitzies-jerk' ); ?></span>
                <?php endif; ?>
                <?php if ( 'outofstock' === $stock_status ) : ?>
                    <span class="mj-stock-badge"><?php esc_html_e( 'Out of Stock', 'mitzies-jerk' ); ?></span>
                <?php endif; ?>
            </div>
            <div class="mj-food-content">
                <h3 class="mj-food-title">
                    <a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a>
                </h3>
                <div class="mj-food-excerpt">
                    <?php echo wp_trim_words( get_the_excerpt( $post_id ), 15 ); ?>
                </div>
                <div class="mj-food-price">
                    <?php if ( $sale_price ) : ?>
                        <del><?php echo esc_html( mitzies_jerk_format_price( $price ) ); ?></del>
                        <ins><?php echo esc_html( mitzies_jerk_format_price( $sale_price ) ); ?></ins>
                    <?php else : ?>
                        <?php echo esc_html( mitzies_jerk_format_price( $price ) ); ?>
                    <?php endif; ?>
                </div>
                <?php
                $show_addons_on_thumbnail = mitzies_jerk_get_option( 'show_addons_on_thumbnail', true );
                if ( ! empty( $addons ) && $show_addons_on_thumbnail ) : ?>
                    <div class="mj-food-addons">
                        <span class="mj-addons-label"><?php esc_html_e( 'Available Add-ons:', 'mitzies-jerk' ); ?></span>
                        <div class="mj-addons-list">
                            <?php foreach ( $addons as $addon ) : ?>
                                <label class="mj-addon-option">
                                    <input type="checkbox" class="mj-addon-input"
                                           data-addon-id="<?php echo esc_attr( $addon->id ); ?>"
                                           data-price="<?php echo esc_attr( $addon->addon_price ); ?>">
                                    <span class="mj-addon-name"><?php echo esc_html( $addon->addon_name ); ?></span>
                                    <span class="mj-addon-price">(+<?php echo esc_html( mitzies_jerk_format_price( $addon->addon_price ) ); ?>)</span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="mj-food-actions">
                    <?php if ( 'outofstock' !== $stock_status ) : ?>
                        <button class="mj-add-to-cart-btn" data-item-id="<?php echo esc_attr( $post_id ); ?>" data-base-price="<?php echo esc_attr( $display_price ); ?>">
                            <span class="dashicons dashicons-cart"></span>
                            <?php esc_html_e( 'Add to Cart', 'mitzies-jerk' ); ?>
                        </button>
                    <?php else : ?>
                        <button class="mj-add-to-cart-btn disabled" disabled>
                            <?php esc_html_e( 'Out of Stock', 'mitzies-jerk' ); ?>
                        </button>
                    <?php endif; ?>
                    <a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" class="mj-view-btn">
                        <?php esc_html_e( 'View', 'mitzies-jerk' ); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get food item data.
     *
     * @param int $post_id Post ID.
     * @return array
     */
    private function get_food_item_data( $post_id ) {
        $price = get_post_meta( $post_id, '_mj_price', true );
        $sale_price = get_post_meta( $post_id, '_mj_sale_price', true );
        $stock_status = get_post_meta( $post_id, '_mj_stock_status', true );

        return array(
            'id'           => $post_id,
            'title'        => get_the_title( $post_id ),
            'excerpt'      => get_the_excerpt( $post_id ),
            'permalink'    => get_permalink( $post_id ),
            'image'        => get_the_post_thumbnail_url( $post_id, 'medium' ),
            'price'        => $price,
            'sale_price'   => $sale_price,
            'formatted_price' => $sale_price ? mitzies_jerk_format_price( $sale_price ) : mitzies_jerk_format_price( $price ),
            'original_price' => $sale_price ? mitzies_jerk_format_price( $price ) : '',
            'in_stock'     => 'outofstock' !== $stock_status,
            'rating'       => Mitzies_Jerk_Database::get_average_rating( $post_id ),
        );
    }

    /**
     * Submit review.
     */
    public function submit_review() {
        check_ajax_referer( 'mj_ajax_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( __( 'You must be logged in to leave a review.', 'mitzies-jerk' ) );
        }

        $food_item_id = isset( $_POST['food_item_id'] ) ? absint( $_POST['food_item_id'] ) : 0;
        $rating = isset( $_POST['rating'] ) ? absint( $_POST['rating'] ) : 0;
        $review_text = isset( $_POST['review'] ) ? sanitize_textarea_field( wp_unslash( $_POST['review'] ) ) : '';

        if ( ! $food_item_id || $rating < 1 || $rating > 5 ) {
            wp_send_json_error( __( 'Invalid rating.', 'mitzies-jerk' ) );
        }

        $review_approval = mitzies_jerk_get_option( 'review_approval', true );

        $review_data = array(
            'food_item_id' => $food_item_id,
            'user_id'      => get_current_user_id(),
            'rating'       => $rating,
            'review_text'  => $review_text,
            'status'       => $review_approval ? 'pending' : 'approved',
        );

        $result = Mitzies_Jerk_Database::save_review( $review_data );

        if ( ! $result ) {
            wp_send_json_error( __( 'Failed to submit review.', 'mitzies-jerk' ) );
        }

        $message = $review_approval
            ? __( 'Thank you for your review! It will be published after approval.', 'mitzies-jerk' )
            : __( 'Thank you for your review!', 'mitzies-jerk' );

        wp_send_json_success( array( 'message' => $message ) );
    }

    /**
     * Update order status (admin).
     */
    public function update_order_status() {
        check_ajax_referer( 'mj_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_mj_orders' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'mitzies-jerk' ) );
        }

        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';

        if ( ! $order_id || ! $status ) {
            wp_send_json_error( __( 'Invalid request.', 'mitzies-jerk' ) );
        }

        $order = new Mitzies_Jerk_Order();
        $result = $order->update_status( $order_id, $status );

        if ( ! $result ) {
            wp_send_json_error( __( 'Failed to update status.', 'mitzies-jerk' ) );
        }

        wp_send_json_success( __( 'Order status updated.', 'mitzies-jerk' ) );
    }

    /**
     * Get dashboard stats (admin).
     */
    public function get_dashboard_stats() {
        check_ajax_referer( 'mj_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'view_mj_reports' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'mitzies-jerk' ) );
        }

        global $wpdb;

        $today_start = date( 'Y-m-d 00:00:00' );
        $today_end = date( 'Y-m-d 23:59:59' );

        $today_orders = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'mj_order' AND post_date >= %s AND post_date <= %s",
                $today_start,
                $today_end
            )
        );

        $today_revenue = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(pm.meta_value) FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE p.post_type = 'mj_order'
                AND p.post_date >= %s AND p.post_date <= %s
                AND pm.meta_key = '_mj_total'",
                $today_start,
                $today_end
            )
        );

        wp_send_json_success( array(
            'today_orders'  => (int) $today_orders,
            'today_revenue' => (float) $today_revenue,
        ) );
    }

    /**
     * Send test email (admin).
     */
    public function send_test_email() {
        check_ajax_referer( 'mj_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_mj_settings' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'mitzies-jerk' ) );
        }

        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

        if ( ! is_email( $email ) ) {
            wp_send_json_error( __( 'Invalid email address.', 'mitzies-jerk' ) );
        }

        $emails = new Mitzies_Jerk_Emails();
        $result = $emails->send_test_email( $email );

        if ( $result ) {
            wp_send_json_success();
        } else {
            wp_send_json_error( __( 'Failed to send email.', 'mitzies-jerk' ) );
        }
    }

    /**
     * Export orders (admin).
     */
    public function export_orders() {
        check_ajax_referer( 'mj_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'view_mj_reports' ) ) {
            wp_die( __( 'Permission denied.', 'mitzies-jerk' ) );
        }

        $start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : '';
        $end_date = isset( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) : '';

        $args = array(
            'posts_per_page' => -1,
        );

        if ( $start_date ) {
            $args['date_query']['after'] = $start_date;
        }
        if ( $end_date ) {
            $args['date_query']['before'] = $end_date;
        }

        $orders = Mitzies_Jerk_Order::get_orders( $args );

        header( 'Content-Type: text/csv' );
        header( 'Content-Disposition: attachment; filename="orders-' . date( 'Y-m-d' ) . '.csv"' );

        $output = fopen( 'php://output', 'w' );

        // Headers.
        fputcsv( $output, array(
            'Order Number',
            'Date',
            'Customer',
            'Email',
            'Phone',
            'Status',
            'Total',
            'Payment Method',
            'Delivery Date',
        ) );

        foreach ( $orders['orders'] as $order ) {
            $billing = $order->get( 'billing' );
            fputcsv( $output, array(
                $order->get( 'order_number' ),
                $order->get( 'created_at' ),
                $billing['first_name'] . ' ' . $billing['last_name'],
                $billing['email'],
                $billing['phone'],
                $order->get( 'status' ),
                $order->get( 'total' ),
                $order->get( 'payment_method' ),
                $order->get( 'delivery_datetime' ),
            ) );
        }

        fclose( $output );
        exit;
    }
}
