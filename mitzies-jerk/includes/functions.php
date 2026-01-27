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
 * Format price.
 *
 * @param float $price Price to format.
 * @return string Formatted price.
 */
function mitzies_jerk_format_price( $price ) {
    $settings = get_option( 'mitzies_jerk_settings', array() );

    $currency_symbol = isset( $settings['currency_symbol'] ) ? $settings['currency_symbol'] : '$';
    $currency_position = isset( $settings['currency_position'] ) ? $settings['currency_position'] : 'left';
    $decimal_places = isset( $settings['decimal_places'] ) ? absint( $settings['decimal_places'] ) : 2;
    $thousands_sep = isset( $settings['thousands_separator'] ) ? $settings['thousands_separator'] : ',';
    $decimal_sep = isset( $settings['decimal_separator'] ) ? $settings['decimal_separator'] : '.';

    $formatted = number_format( floatval( $price ), $decimal_places, $decimal_sep, $thousands_sep );

    if ( 'left' === $currency_position ) {
        return $currency_symbol . $formatted;
    } elseif ( 'left_space' === $currency_position ) {
        return $currency_symbol . ' ' . $formatted;
    } elseif ( 'right' === $currency_position ) {
        return $formatted . $currency_symbol;
    } else {
        return $formatted . ' ' . $currency_symbol;
    }
}

/**
 * Get option value.
 *
 * @param string $key     Option key.
 * @param mixed  $default Default value.
 * @return mixed Option value.
 */
function mitzies_jerk_get_option( $key, $default = '' ) {
    $settings = get_option( 'mitzies_jerk_settings', array() );
    return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
}

/**
 * Get order statuses.
 *
 * @return array Order statuses.
 */
function mitzies_jerk_get_order_statuses() {
    return array(
        'mj-pending'    => __( 'Pending', 'mitzies-jerk' ),
        'mj-paid'       => __( 'Paid', 'mitzies-jerk' ),
        'mj-processing' => __( 'Processing', 'mitzies-jerk' ),
        'mj-preparing'  => __( 'Preparing', 'mitzies-jerk' ),
        'mj-ready'      => __( 'Ready for Pickup', 'mitzies-jerk' ),
        'mj-completed'  => __( 'Completed', 'mitzies-jerk' ),
        'mj-cancelled'  => __( 'Cancelled', 'mitzies-jerk' ),
        'mj-refunded'   => __( 'Refunded', 'mitzies-jerk' ),
    );
}

/**
 * Get payment gateways.
 *
 * @return array Payment gateways.
 */
function mitzies_jerk_get_payment_gateways() {
    return array(
        'paystack'    => __( 'Paystack', 'mitzies-jerk' ),
        'flutterwave' => __( 'Flutterwave', 'mitzies-jerk' ),
        'stripe'      => __( 'Stripe', 'mitzies-jerk' ),
        'paypal'      => __( 'PayPal', 'mitzies-jerk' ),
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
