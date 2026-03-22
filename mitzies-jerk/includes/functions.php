<?php
/**
 * Helper functions.
 *
 * @package    Mitzies_Jerk
 * @subpackage Mitzies_Jerk/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Helper function to get plugin options.
 *
 * @since 1.0.0
 * @param string $option Option name.
 * @param mixed  $default Default value.
 * @return mixed
 */
function mitzies_jerk_get_option( $option, $default = '' ) {
    $options = get_option( 'mitzies_jerk_settings', array() );
    return isset( $options[ $option ] ) ? $options[ $option ] : $default;
}

/**
 * Helper function to update plugin options.
 *
 * @since 1.0.0
 * @param string $option Option name.
 * @param mixed  $value Option value.
 * @return bool
 */
function mitzies_jerk_update_option( $option, $value ) {
    $options = get_option( 'mitzies_jerk_settings', array() );
    $options[ $option ] = $value;
    return update_option( 'mitzies_jerk_settings', $options );
}

/**
 * Helper function to format price.
 *
 * @since 1.0.0
 * @param float $price Price to format.
 * @return string
 */
function mitzies_jerk_format_price( $price ) {
    // Try to inherit currency from WooCommerce if available.
    $currency_symbol = mitzies_jerk_get_option( 'currency_symbol', '' );
    if ( empty( $currency_symbol ) && function_exists( 'get_woocommerce_currency_symbol' ) ) {
        $currency_symbol = get_woocommerce_currency_symbol();
    }
    if ( empty( $currency_symbol ) ) {
        $currency_symbol = mitzies_jerk_get_active_currency_symbol();
    }

    $currency_position = mitzies_jerk_get_option( 'currency_position', 'left' );
    $decimal_places = mitzies_jerk_get_option( 'decimal_places', 2 );
    $thousands_sep = mitzies_jerk_get_option( 'thousands_separator', ',' );
    $decimal_sep = mitzies_jerk_get_option( 'decimal_separator', '.' );

    $formatted = number_format( (float) $price, $decimal_places, $decimal_sep, $thousands_sep );

    if ( 'left' === $currency_position ) {
        return $currency_symbol . $formatted;
    } elseif ( 'left_space' === $currency_position ) {
        return $currency_symbol . ' ' . $formatted;
    } elseif ( 'right' === $currency_position ) {
        return $formatted . $currency_symbol;
    } elseif ( 'right_space' === $currency_position ) {
        return $formatted . ' ' . $currency_symbol;
    }

    return $currency_symbol . $formatted;
}

/**
 * Helper function to get minimum pre-order hours.
 *
 * @since 1.0.0
 * @return int
 */
function mitzies_jerk_get_min_preorder_hours() {
    return (int) mitzies_jerk_get_option( 'min_preorder_hours', 24 );
}

/**
 * Helper function to validate delivery date/time.
 *
 * @since 1.0.0
 * @param string $datetime Delivery datetime string.
 * @return bool|WP_Error
 */
function mitzies_jerk_validate_delivery_datetime( $datetime ) {
    $min_hours = mitzies_jerk_get_min_preorder_hours();
    $delivery_time = strtotime( $datetime );
    $min_time = strtotime( '+' . $min_hours . ' hours' );

    if ( ! $delivery_time ) {
        return new WP_Error( 'invalid_datetime', __( 'Invalid delivery date/time format.', 'mitzies-jerk' ) );
    }

    if ( $delivery_time < $min_time ) {
        return new WP_Error(
            'too_soon',
            sprintf(
                /* translators: %d: Minimum pre-order hours */
                __( 'Delivery must be scheduled at least %d hours in advance.', 'mitzies-jerk' ),
                $min_hours
            )
        );
    }

    // Check if delivery day is allowed.
    $allowed_days = mitzies_jerk_get_option( 'delivery_days', array( 0, 1, 2, 3, 4, 5, 6 ) );
    $delivery_day = (int) date( 'w', $delivery_time );

    if ( ! in_array( $delivery_day, $allowed_days, true ) ) {
        return new WP_Error( 'day_not_allowed', __( 'Delivery is not available on the selected day.', 'mitzies-jerk' ) );
    }

    // Check delivery time slots.
    $time_slots = mitzies_jerk_get_option( 'delivery_time_slots', array() );
    if ( ! empty( $time_slots ) ) {
        $delivery_hour = (int) date( 'H', $delivery_time );
        $delivery_minute = (int) date( 'i', $delivery_time );
        $valid_slot = false;

        foreach ( $time_slots as $slot ) {
            $start = explode( ':', $slot['start'] );
            $end = explode( ':', $slot['end'] );

            $slot_start = (int) $start[0] * 60 + (int) $start[1];
            $slot_end = (int) $end[0] * 60 + (int) $end[1];
            $delivery_minutes = $delivery_hour * 60 + $delivery_minute;

            if ( $delivery_minutes >= $slot_start && $delivery_minutes <= $slot_end ) {
                $valid_slot = true;
                break;
            }
        }

        if ( ! $valid_slot ) {
            return new WP_Error( 'invalid_time_slot', __( 'Please select a valid delivery time slot.', 'mitzies-jerk' ) );
        }
    }

    return true;
}

/**
 * Helper function to get order statuses.
 *
 * @since 1.0.0
 * @return array
 */
function mitzies_jerk_get_order_statuses() {
    return array(
        'pending'    => __( 'Pending Payment', 'mitzies-jerk' ),
        'paid'       => __( 'Paid', 'mitzies-jerk' ),
        'processing' => __( 'Processing', 'mitzies-jerk' ),
        'preparing'  => __( 'Preparing', 'mitzies-jerk' ),
        'ready'      => __( 'Ready for Delivery', 'mitzies-jerk' ),
        'delivering' => __( 'Out for Delivery', 'mitzies-jerk' ),
        'completed'  => __( 'Completed', 'mitzies-jerk' ),
        'cancelled'  => __( 'Cancelled', 'mitzies-jerk' ),
        'refunded'   => __( 'Refunded', 'mitzies-jerk' ),
        'expired'    => __( 'Payment Expired', 'mitzies-jerk' ),
        'failed'     => __( 'Payment Failed', 'mitzies-jerk' ),
    );
}

/**
 * Helper function to log plugin events.
 *
 * @since 1.0.0
 * @param string $message Log message.
 * @param string $level Log level (info, warning, error).
 * @param array  $context Additional context.
 */
function mitzies_jerk_log( $message, $level = 'info', $context = array() ) {
    if ( ! mitzies_jerk_get_option( 'enable_logging', false ) ) {
        return;
    }

    $log_entry = array(
        'timestamp' => current_time( 'mysql' ),
        'level'     => $level,
        'message'   => $message,
        'context'   => $context,
    );

    $logs = get_option( 'mitzies_jerk_logs', array() );
    $logs[] = $log_entry;

    // Keep only last 1000 log entries.
    if ( count( $logs ) > 1000 ) {
        $logs = array_slice( $logs, -1000 );
    }

    update_option( 'mitzies_jerk_logs', $logs );
}

/**
 * Get payment gateways.
 *
 * @return array Payment gateways.
 */
/**
 * Get active currency symbol based on currency code.
 * Tries to inherit from WooCommerce or other plugins, falls back to plugin settings.
 *
 * @since 1.1.0
 * @return string
 */
function mitzies_jerk_get_active_currency_symbol() {
    $currency = mitzies_jerk_get_option( 'currency', 'USD' );

    // Try WooCommerce first.
    if ( function_exists( 'get_woocommerce_currency' ) ) {
        $currency = get_woocommerce_currency();
    }

    $symbols = array(
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'NGN' => '₦',
        'GHS' => 'GH₵',
        'KES' => 'KSh',
        'ZAR' => 'R',
        'INR' => '₹',
        'AUD' => 'A$',
        'CAD' => 'C$',
        'JPY' => '¥',
        'CNY' => '¥',
        'BRL' => 'R$',
        'MXN' => 'Mex$',
        'KRW' => '₩',
        'TRY' => '₺',
        'RUB' => '₽',
        'SEK' => 'kr',
        'NOK' => 'kr',
        'DKK' => 'kr',
        'PLN' => 'zł',
        'THB' => '฿',
        'AED' => 'د.إ',
        'SAR' => '﷼',
        'EGP' => 'E£',
        'XOF' => 'CFA',
        'XAF' => 'FCFA',
        'TZS' => 'TSh',
        'UGX' => 'USh',
        'RWF' => 'FRw',
    );

    return isset( $symbols[ $currency ] ) ? $symbols[ $currency ] : '$';
}

function mitzies_jerk_get_payment_gateways() {
    return array(
        'paystack'    => __( 'Paystack', 'mitzies-jerk' ),
        'flutterwave' => __( 'Flutterwave', 'mitzies-jerk' ),
        'stripe'      => __( 'Stripe', 'mitzies-jerk' ),
        'paypal'      => __( 'PayPal', 'mitzies-jerk' ),
        'square'      => __( 'Square', 'mitzies-jerk' ),
        'cod'         => __( 'Cash on Delivery', 'mitzies-jerk' ),
    );
}

/**
 * Create demo data.
 */
function mitzies_jerk_create_demo_data() {
    // Create categories first
    $categories = array(
        array(
            'name' => 'Main Dishes',
            'slug' => 'main-dishes',
            'description' => 'Delicious Caribbean main courses',
        ),
        array(
            'name' => 'Sides',
            'slug' => 'sides',
            'description' => 'Perfect accompaniments to your meal',
        ),
        array(
            'name' => 'Drinks',
            'slug' => 'drinks',
            'description' => 'Refreshing beverages',
        ),
    );

    $category_ids = array();
    foreach ( $categories as $cat ) {
        $existing = term_exists( $cat['slug'], 'mj_food_category' );
        if ( ! $existing ) {
            $result = wp_insert_term( $cat['name'], 'mj_food_category', array(
                'slug' => $cat['slug'],
                'description' => $cat['description'],
            ) );
            if ( ! is_wp_error( $result ) ) {
                $category_ids[ $cat['slug'] ] = $result['term_id'];
            }
        } else {
            $category_ids[ $cat['slug'] ] = $existing['term_id'];
        }
    }

    // Demo food items
    $demo_items = array(
        array(
            'title'       => 'Jerk Chicken',
            'description' => 'Our signature jerk chicken, marinated for 24 hours in our secret blend of Jamaican spices and slow-cooked to perfection. Served with authentic Caribbean flavors that will transport you to the islands.',
            'price'       => 15.99,
            'sale_price'  => '',
            'category'    => 'main-dishes',
            'ingredients' => 'Chicken, scotch bonnet peppers, allspice, thyme, garlic, ginger, soy sauce, brown sugar',
            'prep_time'   => '25-30 mins',
            'calories'    => '450 cal',
            'featured'    => 1,
        ),
        array(
            'title'       => 'Oxtail Stew',
            'description' => 'Tender oxtail braised for hours in a rich, savory gravy with butter beans. A Caribbean comfort food classic that melts in your mouth.',
            'price'       => 18.99,
            'sale_price'  => '',
            'category'    => 'main-dishes',
            'ingredients' => 'Oxtail, butter beans, carrots, thyme, scotch bonnet, allspice, tomatoes',
            'prep_time'   => '30-35 mins',
            'calories'    => '580 cal',
            'featured'    => 1,
        ),
        array(
            'title'       => 'Curry Goat',
            'description' => 'Succulent goat meat slow-cooked in a fragrant Caribbean curry sauce with potatoes. Bold, aromatic, and absolutely delicious.',
            'price'       => 17.99,
            'sale_price'  => 15.99,
            'category'    => 'main-dishes',
            'ingredients' => 'Goat meat, curry powder, potatoes, thyme, scotch bonnet, onions, garlic',
            'prep_time'   => '30-35 mins',
            'calories'    => '520 cal',
            'featured'    => 0,
        ),
        array(
            'title'       => 'Rice and Peas',
            'description' => 'Traditional Jamaican rice and peas cooked in coconut milk with kidney beans, thyme, and scotch bonnet pepper. The perfect side dish.',
            'price'       => 5.99,
            'sale_price'  => '',
            'category'    => 'sides',
            'ingredients' => 'Rice, red kidney beans, coconut milk, thyme, scotch bonnet, garlic',
            'prep_time'   => '10-15 mins',
            'calories'    => '280 cal',
            'featured'    => 0,
        ),
        array(
            'title'       => 'Sorrel Drink',
            'description' => 'Refreshing traditional Caribbean sorrel drink made from hibiscus flowers with ginger and spices. Sweet, tangy, and perfect for any occasion.',
            'price'       => 3.99,
            'sale_price'  => '',
            'category'    => 'drinks',
            'ingredients' => 'Hibiscus flowers, ginger, cinnamon, cloves, sugar, water',
            'prep_time'   => '2-3 mins',
            'calories'    => '120 cal',
            'featured'    => 0,
        ),
    );

    foreach ( $demo_items as $item ) {
        // Check if item already exists
        $existing = get_page_by_title( $item['title'], OBJECT, 'mj_food_item' );
        if ( $existing ) {
            continue;
        }

        // Create the food item
        $post_id = wp_insert_post( array(
            'post_title'   => $item['title'],
            'post_content' => $item['description'],
            'post_status'  => 'publish',
            'post_type'    => 'mj_food_item',
        ) );

        if ( ! is_wp_error( $post_id ) && $post_id ) {
            // Set meta data
            update_post_meta( $post_id, '_mj_price', $item['price'] );
            if ( ! empty( $item['sale_price'] ) ) {
                update_post_meta( $post_id, '_mj_sale_price', $item['sale_price'] );
            }
            update_post_meta( $post_id, '_mj_stock_status', 'instock' );
            update_post_meta( $post_id, '_mj_stock_quantity', 100 );
            update_post_meta( $post_id, '_mj_ingredients', $item['ingredients'] );
            update_post_meta( $post_id, '_mj_preparation_time', $item['prep_time'] );
            update_post_meta( $post_id, '_mj_calories', $item['calories'] );
            update_post_meta( $post_id, '_mj_is_featured', $item['featured'] );

            // Set category
            if ( isset( $category_ids[ $item['category'] ] ) ) {
                wp_set_object_terms( $post_id, array( (int) $category_ids[ $item['category'] ] ), 'mj_food_category' );
            }
        }
    }
}

/**
 * Get default email templates.
 *
 * @return array Default email templates.
 */
function mitzies_jerk_get_default_email_templates() {
    return array(
        'order_confirmation' => array(
            'subject' => __( 'Order Confirmation - #{order_number}', 'mitzies-jerk' ),
            'heading' => __( 'Thank you for your order!', 'mitzies-jerk' ),
            'content' => __( "Hi {customer_name},\n\nThank you for your order! We've received your order #{order_number} and will start preparing it soon.\n\n{order_details}\n\nPickup Time: {pickup_time}\n\nTotal: {order_total}\n\nThank you for choosing {site_name}!", 'mitzies-jerk' ),
        ),
        'order_status_update' => array(
            'subject' => __( 'Order #{order_number} Status Update', 'mitzies-jerk' ),
            'heading' => __( 'Your order status has been updated', 'mitzies-jerk' ),
            'content' => __( "Hi {customer_name},\n\nYour order #{order_number} status has been updated to: {order_status}\n\n{status_message}\n\nThank you for choosing {site_name}!", 'mitzies-jerk' ),
        ),
        'order_ready' => array(
            'subject' => __( 'Your Order is Ready for Pickup! - #{order_number}', 'mitzies-jerk' ),
            'heading' => __( 'Your order is ready!', 'mitzies-jerk' ),
            'content' => __( "Hi {customer_name},\n\nGreat news! Your order #{order_number} is ready for pickup.\n\n{order_details}\n\nPlease come pick up your order at your scheduled time.\n\nThank you for choosing {site_name}!", 'mitzies-jerk' ),
        ),
        'admin_new_order' => array(
            'subject' => __( 'New Order Received - #{order_number}', 'mitzies-jerk' ),
            'heading' => __( 'New Order Received', 'mitzies-jerk' ),
            'content' => __( "A new order has been received!\n\nOrder Number: #{order_number}\nCustomer: {customer_name}\nEmail: {customer_email}\nPhone: {customer_phone}\n\n{order_details}\n\nPickup Time: {pickup_time}\nTotal: {order_total}\nPayment Method: {payment_method}", 'mitzies-jerk' ),
        ),
    );
}
