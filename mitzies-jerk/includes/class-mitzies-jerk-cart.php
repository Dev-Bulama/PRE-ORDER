<?php
/**
 * Cart handler class.
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
 * Cart handler class.
 */
class Mitzies_Jerk_Cart {

    /**
     * Session instance.
     *
     * @var Mitzies_Jerk_Session
     */
    private $session;

    /**
     * Cart contents.
     *
     * @var array
     */
    private $cart_contents = array();

    /**
     * Applied coupons.
     *
     * @var array
     */
    private $applied_coupons = array();

    /**
     * Delivery date/time.
     *
     * @var string
     */
    private $delivery_datetime;

    /**
     * Cart totals.
     *
     * @var array
     */
    private $totals = array();

    /**
     * Constructor.
     *
     * @since    1.0.0
     * @param    Mitzies_Jerk_Session $session Session instance.
     */
    public function __construct( $session ) {
        $this->session = $session;
    }

    /**
     * Initialize the cart.
     *
     * @since    1.0.0
     */
    public function init() {
        $this->cart_contents = $this->session->get( 'cart_contents', array() );
        $this->applied_coupons = $this->session->get( 'applied_coupons', array() );
        $this->delivery_datetime = $this->session->get( 'delivery_datetime', '' );
        $this->calculate_totals();
    }

    /**
     * Add item to cart.
     *
     * @since    1.0.0
     * @param    int   $food_item_id Food item ID.
     * @param    int   $quantity     Quantity.
     * @param    array $addons       Selected addons.
     * @return   string|WP_Error Cart item key or error.
     */
    public function add_to_cart( $food_item_id, $quantity = 1, $addons = array() ) {
        // Validate food item.
        $food_item = get_post( $food_item_id );

        if ( ! $food_item || 'mj_food_item' !== $food_item->post_type ) {
            return new WP_Error( 'invalid_item', __( 'Invalid food item.', 'mitzies-jerk' ) );
        }

        if ( 'publish' !== $food_item->post_status ) {
            return new WP_Error( 'unavailable', __( 'This item is not available.', 'mitzies-jerk' ) );
        }

        // Check stock.
        $stock_status = get_post_meta( $food_item_id, '_mj_stock_status', true );
        $stock_quantity = (int) get_post_meta( $food_item_id, '_mj_stock_quantity', true );

        if ( 'outofstock' === $stock_status || ( 'instock' === $stock_status && $stock_quantity < $quantity ) ) {
            return new WP_Error( 'out_of_stock', __( 'Sorry, this item is out of stock.', 'mitzies-jerk' ) );
        }

        // Check availability schedule.
        if ( ! $this->is_item_available( $food_item_id ) ) {
            return new WP_Error( 'not_available', __( 'This item is not available at this time.', 'mitzies-jerk' ) );
        }

        // Generate cart item key.
        $cart_item_key = $this->generate_cart_item_key( $food_item_id, $addons );

        // Get price.
        $price = (float) get_post_meta( $food_item_id, '_mj_price', true );

        // Calculate addon prices.
        $addon_total = 0;
        $processed_addons = array();

        if ( ! empty( $addons ) ) {
            $available_addons = Mitzies_Jerk_Database::get_food_addons( $food_item_id );
            $addon_map = array();

            foreach ( $available_addons as $addon ) {
                $addon_map[ $addon->id ] = $addon;
            }

            foreach ( $addons as $addon_id => $addon_qty ) {
                if ( isset( $addon_map[ $addon_id ] ) ) {
                    $addon = $addon_map[ $addon_id ];
                    $addon_price = (float) $addon->addon_price * (int) $addon_qty;
                    $addon_total += $addon_price;
                    $processed_addons[ $addon_id ] = array(
                        'name'     => $addon->addon_name,
                        'price'    => (float) $addon->addon_price,
                        'quantity' => (int) $addon_qty,
                    );
                }
            }
        }

        // Check if item already in cart.
        if ( isset( $this->cart_contents[ $cart_item_key ] ) ) {
            $new_quantity = $this->cart_contents[ $cart_item_key ]['quantity'] + $quantity;

            // Check stock for new quantity.
            if ( 'instock' === $stock_status && $stock_quantity < $new_quantity ) {
                return new WP_Error(
                    'insufficient_stock',
                    sprintf(
                        /* translators: %d: Available quantity */
                        __( 'Only %d items available in stock.', 'mitzies-jerk' ),
                        $stock_quantity
                    )
                );
            }

            $this->cart_contents[ $cart_item_key ]['quantity'] = $new_quantity;
        } else {
            $this->cart_contents[ $cart_item_key ] = array(
                'food_item_id' => $food_item_id,
                'quantity'     => $quantity,
                'price'        => $price,
                'addons'       => $processed_addons,
                'addon_total'  => $addon_total,
            );
        }

        $this->save_cart();
        $this->calculate_totals();

        /**
         * Fires after an item is added to the cart.
         *
         * @param string $cart_item_key Cart item key.
         * @param int    $food_item_id  Food item ID.
         * @param int    $quantity      Quantity added.
         * @param array  $addons        Selected addons.
         */
        do_action( 'mj_added_to_cart', $cart_item_key, $food_item_id, $quantity, $addons );

        return $cart_item_key;
    }

    /**
     * Remove item from cart.
     *
     * @since    1.0.0
     * @param    string $cart_item_key Cart item key.
     * @return   bool
     */
    public function remove_from_cart( $cart_item_key ) {
        if ( isset( $this->cart_contents[ $cart_item_key ] ) ) {
            $food_item_id = $this->cart_contents[ $cart_item_key ]['food_item_id'];

            unset( $this->cart_contents[ $cart_item_key ] );
            $this->save_cart();
            $this->calculate_totals();

            /**
             * Fires after an item is removed from the cart.
             *
             * @param string $cart_item_key Cart item key.
             * @param int    $food_item_id  Food item ID.
             */
            do_action( 'mj_removed_from_cart', $cart_item_key, $food_item_id );

            return true;
        }

        return false;
    }

    /**
     * Update cart item quantity.
     *
     * @since    1.0.0
     * @param    string $cart_item_key Cart item key.
     * @param    int    $quantity      New quantity.
     * @return   bool|WP_Error
     */
    public function update_quantity( $cart_item_key, $quantity ) {
        if ( ! isset( $this->cart_contents[ $cart_item_key ] ) ) {
            return new WP_Error( 'invalid_key', __( 'Invalid cart item.', 'mitzies-jerk' ) );
        }

        if ( $quantity <= 0 ) {
            return $this->remove_from_cart( $cart_item_key );
        }

        $food_item_id = $this->cart_contents[ $cart_item_key ]['food_item_id'];

        // Check stock.
        $stock_status = get_post_meta( $food_item_id, '_mj_stock_status', true );
        $stock_quantity = (int) get_post_meta( $food_item_id, '_mj_stock_quantity', true );

        if ( 'instock' === $stock_status && $stock_quantity < $quantity ) {
            return new WP_Error(
                'insufficient_stock',
                sprintf(
                    /* translators: %d: Available quantity */
                    __( 'Only %d items available in stock.', 'mitzies-jerk' ),
                    $stock_quantity
                )
            );
        }

        $this->cart_contents[ $cart_item_key ]['quantity'] = $quantity;
        $this->save_cart();
        $this->calculate_totals();

        return true;
    }

    /**
     * Empty the cart.
     *
     * @since    1.0.0
     */
    public function empty_cart() {
        $this->cart_contents = array();
        $this->applied_coupons = array();
        $this->delivery_datetime = '';
        $this->totals = array();
        $this->save_cart();

        /**
         * Fires after the cart is emptied.
         */
        do_action( 'mj_cart_emptied' );
    }

    /**
     * Get cart contents.
     *
     * @since    1.0.0
     * @return   array
     */
    public function get_cart_contents() {
        return $this->cart_contents;
    }

    /**
     * Get cart item.
     *
     * @since    1.0.0
     * @param    string $cart_item_key Cart item key.
     * @return   array|null
     */
    public function get_cart_item( $cart_item_key ) {
        return isset( $this->cart_contents[ $cart_item_key ] ) ? $this->cart_contents[ $cart_item_key ] : null;
    }

    /**
     * Get cart item count.
     *
     * @since    1.0.0
     * @return   int
     */
    public function get_cart_count() {
        $count = 0;

        foreach ( $this->cart_contents as $item ) {
            $count += $item['quantity'];
        }

        return $count;
    }

    /**
     * Check if cart is empty.
     *
     * @since    1.0.0
     * @return   bool
     */
    public function is_empty() {
        return empty( $this->cart_contents );
    }

    /**
     * Apply coupon to cart.
     *
     * @since    1.0.0
     * @param    string $coupon_code Coupon code.
     * @return   bool|WP_Error
     */
    public function apply_coupon( $coupon_code ) {
        $coupon_code = strtoupper( sanitize_text_field( $coupon_code ) );

        // Check if already applied.
        if ( in_array( $coupon_code, $this->applied_coupons, true ) ) {
            return new WP_Error( 'already_applied', __( 'This coupon is already applied.', 'mitzies-jerk' ) );
        }

        // Get coupon.
        $coupon = Mitzies_Jerk_Database::get_coupon_by_code( $coupon_code );

        if ( ! $coupon ) {
            return new WP_Error( 'invalid_coupon', __( 'Invalid coupon code.', 'mitzies-jerk' ) );
        }

        // Check status.
        if ( 'active' !== $coupon->status ) {
            return new WP_Error( 'inactive_coupon', __( 'This coupon is no longer active.', 'mitzies-jerk' ) );
        }

        // Check dates.
        $now = current_time( 'timestamp' );

        if ( $coupon->start_date && strtotime( $coupon->start_date ) > $now ) {
            return new WP_Error( 'not_started', __( 'This coupon is not yet valid.', 'mitzies-jerk' ) );
        }

        if ( $coupon->end_date && strtotime( $coupon->end_date ) < $now ) {
            return new WP_Error( 'expired', __( 'This coupon has expired.', 'mitzies-jerk' ) );
        }

        // Check usage limit.
        if ( $coupon->usage_limit && $coupon->usage_count >= $coupon->usage_limit ) {
            return new WP_Error( 'usage_limit', __( 'This coupon has reached its usage limit.', 'mitzies-jerk' ) );
        }

        // Check minimum order.
        if ( $coupon->minimum_order > 0 && $this->get_subtotal() < $coupon->minimum_order ) {
            return new WP_Error(
                'minimum_order',
                sprintf(
                    /* translators: %s: Minimum order amount */
                    __( 'This coupon requires a minimum order of %s.', 'mitzies-jerk' ),
                    mitzies_jerk_format_price( $coupon->minimum_order )
                )
            );
        }

        $this->applied_coupons[] = $coupon_code;
        $this->save_cart();
        $this->calculate_totals();

        /**
         * Fires after a coupon is applied.
         *
         * @param string $coupon_code Coupon code.
         */
        do_action( 'mj_coupon_applied', $coupon_code );

        return true;
    }

    /**
     * Remove coupon from cart.
     *
     * @since    1.0.0
     * @param    string $coupon_code Coupon code.
     * @return   bool
     */
    public function remove_coupon( $coupon_code ) {
        $coupon_code = strtoupper( sanitize_text_field( $coupon_code ) );
        $key = array_search( $coupon_code, $this->applied_coupons, true );

        if ( false !== $key ) {
            unset( $this->applied_coupons[ $key ] );
            $this->applied_coupons = array_values( $this->applied_coupons );
            $this->save_cart();
            $this->calculate_totals();

            /**
             * Fires after a coupon is removed.
             *
             * @param string $coupon_code Coupon code.
             */
            do_action( 'mj_coupon_removed', $coupon_code );

            return true;
        }

        return false;
    }

    /**
     * Get applied coupons.
     *
     * @since    1.0.0
     * @return   array
     */
    public function get_applied_coupons() {
        return $this->applied_coupons;
    }

    /**
     * Set delivery date/time.
     *
     * @since    1.0.0
     * @param    string $datetime Delivery datetime.
     * @return   bool|WP_Error
     */
    public function set_delivery_datetime( $datetime ) {
        $validation = mitzies_jerk_validate_delivery_datetime( $datetime );

        if ( is_wp_error( $validation ) ) {
            return $validation;
        }

        $this->delivery_datetime = sanitize_text_field( $datetime );
        $this->session->set( 'delivery_datetime', $this->delivery_datetime );

        return true;
    }

    /**
     * Get delivery date/time.
     *
     * @since    1.0.0
     * @return   string
     */
    public function get_delivery_datetime() {
        return $this->delivery_datetime;
    }

    /**
     * Calculate cart totals.
     *
     * @since    1.0.0
     */
    public function calculate_totals() {
        $subtotal = 0;
        $addon_total = 0;

        foreach ( $this->cart_contents as $item ) {
            $item_subtotal = ( $item['price'] + $item['addon_total'] ) * $item['quantity'];
            $subtotal += $item['price'] * $item['quantity'];
            $addon_total += $item['addon_total'] * $item['quantity'];
        }

        $this->totals['subtotal'] = $subtotal;
        $this->totals['addon_total'] = $addon_total;

        // Calculate discount.
        $discount = $this->calculate_discount();
        $this->totals['discount'] = $discount;

        // Calculate delivery fee.
        $delivery_fee = $this->calculate_delivery_fee();
        $this->totals['delivery_fee'] = $delivery_fee;

        // Calculate tax.
        $taxable_amount = $subtotal + $addon_total - $discount;
        $tax = $this->calculate_tax( $taxable_amount );
        $this->totals['tax'] = $tax;

        // Calculate total.
        $total = $subtotal + $addon_total - $discount + $delivery_fee + $tax;
        $this->totals['total'] = max( 0, $total );

        /**
         * Filters the calculated cart totals.
         *
         * @param array                $totals Cart totals.
         * @param Mitzies_Jerk_Cart $cart   Cart instance.
         */
        $this->totals = apply_filters( 'mj_cart_totals', $this->totals, $this );
    }

    /**
     * Calculate discount from coupons.
     *
     * @since    1.0.0
     * @return   float
     */
    private function calculate_discount() {
        $discount = 0;
        $subtotal = $this->totals['subtotal'] + $this->totals['addon_total'];

        foreach ( $this->applied_coupons as $coupon_code ) {
            $coupon = Mitzies_Jerk_Database::get_coupon_by_code( $coupon_code );

            if ( ! $coupon ) {
                continue;
            }

            if ( 'percentage' === $coupon->type ) {
                $coupon_discount = $subtotal * ( (float) $coupon->amount / 100 );
            } else {
                $coupon_discount = (float) $coupon->amount;
            }

            // Apply maximum discount limit.
            if ( $coupon->maximum_discount && $coupon_discount > $coupon->maximum_discount ) {
                $coupon_discount = (float) $coupon->maximum_discount;
            }

            $discount += $coupon_discount;
        }

        return $discount;
    }

    /**
     * Calculate delivery fee.
     *
     * @since    1.0.0
     * @return   float
     */
    private function calculate_delivery_fee() {
        $delivery_fee = (float) mitzies_jerk_get_option( 'delivery_fee', 0 );
        $free_threshold = (float) mitzies_jerk_get_option( 'free_delivery_threshold', 0 );

        $subtotal = $this->totals['subtotal'] + $this->totals['addon_total'];

        // Free delivery threshold.
        if ( $free_threshold > 0 && $subtotal >= $free_threshold ) {
            return 0;
        }

        /**
         * Filters the delivery fee.
         *
         * @param float              $delivery_fee Calculated delivery fee.
         * @param Mitzies_Jerk_Cart $cart         Cart instance.
         */
        return apply_filters( 'mj_delivery_fee', $delivery_fee, $this );
    }

    /**
     * Calculate tax.
     *
     * @since    1.0.0
     * @param    float $amount Taxable amount.
     * @return   float
     */
    private function calculate_tax( $amount ) {
        if ( ! mitzies_jerk_get_option( 'enable_tax', false ) ) {
            return 0;
        }

        $tax_rate = (float) mitzies_jerk_get_option( 'tax_rate', 0 );
        $tax_inclusive = mitzies_jerk_get_option( 'tax_inclusive', false );

        if ( $tax_inclusive ) {
            // Tax is already included in prices.
            return $amount - ( $amount / ( 1 + ( $tax_rate / 100 ) ) );
        }

        return $amount * ( $tax_rate / 100 );
    }

    /**
     * Get cart subtotal.
     *
     * @since    1.0.0
     * @return   float
     */
    public function get_subtotal() {
        return isset( $this->totals['subtotal'] ) ? $this->totals['subtotal'] : 0;
    }

    /**
     * Get cart total.
     *
     * @since    1.0.0
     * @return   float
     */
    public function get_total() {
        return isset( $this->totals['total'] ) ? $this->totals['total'] : 0;
    }

    /**
     * Get all totals.
     *
     * @since    1.0.0
     * @return   array
     */
    public function get_totals() {
        return $this->totals;
    }

    /**
     * Save cart to session.
     *
     * @since    1.0.0
     */
    private function save_cart() {
        $this->session->set( 'cart_contents', $this->cart_contents );
        $this->session->set( 'applied_coupons', $this->applied_coupons );
    }

    /**
     * Generate cart item key.
     *
     * @since    1.0.0
     * @param    int   $food_item_id Food item ID.
     * @param    array $addons       Selected addons.
     * @return   string
     */
    private function generate_cart_item_key( $food_item_id, $addons ) {
        ksort( $addons );
        return md5( $food_item_id . wp_json_encode( $addons ) );
    }

    /**
     * Check if food item is available at current time.
     *
     * @since    1.0.0
     * @param    int $food_item_id Food item ID.
     * @return   bool
     */
    private function is_item_available( $food_item_id ) {
        $schedule = get_post_meta( $food_item_id, '_mj_availability_schedule', true );

        if ( empty( $schedule ) ) {
            return true;
        }

        $current_day = strtolower( date( 'l' ) );
        $current_time = date( 'H:i' );

        if ( isset( $schedule[ $current_day ] ) ) {
            $day_schedule = $schedule[ $current_day ];

            if ( ! $day_schedule['enabled'] ) {
                return false;
            }

            if ( ! empty( $day_schedule['start'] ) && ! empty( $day_schedule['end'] ) ) {
                if ( $current_time < $day_schedule['start'] || $current_time > $day_schedule['end'] ) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get cart for display.
     *
     * @since    1.0.0
     * @return   array
     */
    public function get_cart_for_display() {
        $items = array();

        foreach ( $this->cart_contents as $key => $item ) {
            $food_item = get_post( $item['food_item_id'] );

            if ( ! $food_item ) {
                continue;
            }

            $line_total = ( $item['price'] + $item['addon_total'] ) * $item['quantity'];

            $items[] = array(
                'key'               => $key,
                'food_item_id'      => $item['food_item_id'],
                'name'              => $food_item->post_title,
                'quantity'          => $item['quantity'],
                'price'             => $item['price'],
                'price_formatted'   => mitzies_jerk_format_price( $item['price'] ),
                'addons'            => $item['addons'],
                'addon_total'       => $item['addon_total'],
                'line_total'        => $line_total,
                'subtotal'          => $line_total,
                'subtotal_formatted' => mitzies_jerk_format_price( $line_total ),
                'thumbnail'         => get_the_post_thumbnail_url( $item['food_item_id'], 'thumbnail' ),
                'image'             => get_the_post_thumbnail_url( $item['food_item_id'], 'thumbnail' ),
                'permalink'         => get_permalink( $item['food_item_id'] ),
            );
        }

        $subtotal = isset( $this->totals['subtotal'] ) ? $this->totals['subtotal'] : 0;
        $addon_total = isset( $this->totals['addon_total'] ) ? $this->totals['addon_total'] : 0;
        $discount = isset( $this->totals['discount'] ) ? $this->totals['discount'] : 0;
        $delivery_fee = isset( $this->totals['delivery_fee'] ) ? $this->totals['delivery_fee'] : 0;
        $tax = isset( $this->totals['tax'] ) ? $this->totals['tax'] : 0;
        $total = isset( $this->totals['total'] ) ? $this->totals['total'] : 0;

        return array(
            'items'              => $items,
            'item_count'         => $this->get_cart_count(),
            'subtotal'           => $subtotal,
            'subtotal_formatted' => mitzies_jerk_format_price( $subtotal + $addon_total ),
            'addon_total'        => $addon_total,
            'discount'           => $discount,
            'discount_formatted' => mitzies_jerk_format_price( $discount ),
            'delivery_fee'       => $delivery_fee,
            'delivery_fee_formatted' => mitzies_jerk_format_price( $delivery_fee ),
            'tax'                => $tax,
            'tax_formatted'      => mitzies_jerk_format_price( $tax ),
            'total'              => $total,
            'total_formatted'    => mitzies_jerk_format_price( $total ),
            'applied_coupons'    => $this->applied_coupons,
        );
    }
}
