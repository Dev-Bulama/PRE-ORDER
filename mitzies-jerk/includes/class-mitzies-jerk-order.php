<?php
/**
 * Order handler class.
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
 * Order handler class.
 */
class Mitzies_Jerk_Order {

    /**
     * Order ID.
     *
     * @var int
     */
    private $id = 0;

    /**
     * Order data.
     *
     * @var array
     */
    private $data = array();

    /**
     * Constructor.
     *
     * @since    1.0.0
     * @param    int $order_id Optional order ID to load.
     */
    public function __construct( $order_id = 0 ) {
        if ( $order_id ) {
            $this->id = $order_id;
            $this->load();
        }
    }

    /**
     * Load order data.
     *
     * @since    1.0.0
     */
    private function load() {
        $post = get_post( $this->id );

        if ( ! $post || 'mj_order' !== $post->post_type ) {
            return;
        }

        $this->data = array(
            'id'                => $this->id,
            'order_number'      => get_post_meta( $this->id, '_mj_order_number', true ),
            'status'            => get_post_meta( $this->id, '_mj_order_status', true ),
            'user_id'           => get_post_meta( $this->id, '_mj_user_id', true ),
            'payment_method'    => get_post_meta( $this->id, '_mj_payment_method', true ),
            'transaction_id'    => get_post_meta( $this->id, '_mj_transaction_id', true ),
            'subtotal'          => get_post_meta( $this->id, '_mj_subtotal', true ),
            'addon_total'       => get_post_meta( $this->id, '_mj_addon_total', true ),
            'discount'          => get_post_meta( $this->id, '_mj_discount', true ),
            'delivery_fee'      => get_post_meta( $this->id, '_mj_delivery_fee', true ),
            'tax'               => get_post_meta( $this->id, '_mj_tax', true ),
            'total'             => get_post_meta( $this->id, '_mj_total', true ),
            'currency'          => get_post_meta( $this->id, '_mj_currency', true ),
            'applied_coupons'   => get_post_meta( $this->id, '_mj_applied_coupons', true ),
            'delivery_datetime' => get_post_meta( $this->id, '_mj_delivery_datetime', true ),
            'billing'           => get_post_meta( $this->id, '_mj_billing', true ),
            'delivery'          => get_post_meta( $this->id, '_mj_delivery', true ),
            'payment_expiry'    => get_post_meta( $this->id, '_mj_payment_expiry', true ),
            'paid_at'           => get_post_meta( $this->id, '_mj_paid_at', true ),
            'completed_at'      => get_post_meta( $this->id, '_mj_completed_at', true ),
            'ip_address'        => get_post_meta( $this->id, '_mj_ip_address', true ),
            'user_agent'        => get_post_meta( $this->id, '_mj_user_agent', true ),
            'created_at'        => $post->post_date,
            'modified_at'       => $post->post_modified,
        );
    }

    /**
     * Create a new order.
     *
     * @since    1.0.0
     * @param    array $order_data Order data.
     * @return   int|WP_Error Order ID or error.
     */
    public function create( $order_data ) {
        // Generate order number.
        $order_number = $this->generate_order_number();

        // Set payment expiry.
        $expiry_minutes = (int) mitzies_jerk_get_option( 'payment_expiry_minutes', 30 );
        $payment_expiry = date( 'Y-m-d H:i:s', strtotime( '+' . $expiry_minutes . ' minutes' ) );

        // Create the post.
        $post_data = array(
            'post_type'   => 'mj_order',
            'post_status' => 'mj-pending',
            'post_title'  => sprintf(
                /* translators: %s: Order number */
                __( 'Order #%s', 'mitzies-jerk' ),
                $order_number
            ),
            'post_author' => $order_data['user_id'] ?: 1,
        );

        $order_id = wp_insert_post( $post_data, true );

        if ( is_wp_error( $order_id ) ) {
            return $order_id;
        }

        $this->id = $order_id;

        // Save order meta.
        update_post_meta( $order_id, '_mj_order_number', $order_number );
        update_post_meta( $order_id, '_mj_order_status', 'pending' );
        update_post_meta( $order_id, '_mj_user_id', $order_data['user_id'] );
        update_post_meta( $order_id, '_mj_payment_method', $order_data['payment_method'] );
        update_post_meta( $order_id, '_mj_subtotal', $order_data['subtotal'] );
        update_post_meta( $order_id, '_mj_addon_total', $order_data['addon_total'] );
        update_post_meta( $order_id, '_mj_discount', $order_data['discount'] );
        update_post_meta( $order_id, '_mj_delivery_fee', $order_data['delivery_fee'] );
        update_post_meta( $order_id, '_mj_tax', $order_data['tax'] );
        update_post_meta( $order_id, '_mj_total', $order_data['total'] );
        update_post_meta( $order_id, '_mj_currency', $order_data['currency'] );
        update_post_meta( $order_id, '_mj_applied_coupons', $order_data['applied_coupons'] );
        update_post_meta( $order_id, '_mj_delivery_datetime', $order_data['delivery_datetime'] );
        update_post_meta( $order_id, '_mj_billing', $order_data['billing'] );
        update_post_meta( $order_id, '_mj_delivery', $order_data['delivery'] );
        update_post_meta( $order_id, '_mj_payment_expiry', $payment_expiry );
        update_post_meta( $order_id, '_mj_ip_address', $order_data['ip_address'] );
        update_post_meta( $order_id, '_mj_user_agent', $order_data['user_agent'] );

        // Save order items.
        foreach ( $order_data['items'] as $item ) {
            $item_data = array(
                'order_id'     => $order_id,
                'food_item_id' => $item['food_item_id'],
                'quantity'     => $item['quantity'],
                'price'        => $item['price'],
                'subtotal'     => ( $item['price'] + $item['addon_total'] ) * $item['quantity'],
                'addons'       => maybe_serialize( $item['addons'] ),
            );

            Mitzies_Jerk_Database::save_order_item( $item_data );

            // Reduce stock.
            $this->reduce_stock( $item['food_item_id'], $item['quantity'] );
        }

        // Increment coupon usage.
        foreach ( $order_data['applied_coupons'] as $coupon_code ) {
            $coupon = Mitzies_Jerk_Database::get_coupon_by_code( $coupon_code );
            if ( $coupon ) {
                Mitzies_Jerk_Database::increment_coupon_usage( $coupon->id );
            }
        }

        // Load order data.
        $this->load();

        /**
         * Fires after an order is created.
         *
         * @param int   $order_id   Order ID.
         * @param array $order_data Order data.
         */
        do_action( 'mj_order_created', $order_id, $order_data );

        // Log.
        mitzies_jerk_log(
            sprintf( 'Order #%s created', $order_number ),
            'info',
            array( 'order_id' => $order_id, 'total' => $order_data['total'] )
        );

        return $order_id;
    }

    /**
     * Generate unique order number.
     *
     * @since    1.0.0
     * @return   string
     */
    private function generate_order_number() {
        $prefix = mitzies_jerk_get_option( 'order_prefix', 'MJ' );
        $last_number = (int) get_option( 'mitzies_jerk_last_order_number', 0 );
        $new_number = $last_number + 1;

        update_option( 'mitzies_jerk_last_order_number', $new_number );

        return $prefix . str_pad( $new_number, 6, '0', STR_PAD_LEFT );
    }

    /**
     * Reduce item stock.
     *
     * @since    1.0.0
     * @param    int $food_item_id Food item ID.
     * @param    int $quantity     Quantity to reduce.
     */
    private function reduce_stock( $food_item_id, $quantity ) {
        $stock_status = get_post_meta( $food_item_id, '_mj_stock_status', true );

        if ( 'instock' !== $stock_status ) {
            return;
        }

        $current_stock = (int) get_post_meta( $food_item_id, '_mj_stock_quantity', true );
        $new_stock = max( 0, $current_stock - $quantity );

        update_post_meta( $food_item_id, '_mj_stock_quantity', $new_stock );

        if ( 0 === $new_stock ) {
            update_post_meta( $food_item_id, '_mj_stock_status', 'outofstock' );
        }
    }

    /**
     * Update order status.
     *
     * @since    1.0.0
     * @param    int    $order_id Order ID.
     * @param    string $status   New status.
     * @param    string $note     Optional note.
     * @return   bool
     */
    public function update_status( $order_id, $status, $note = '' ) {
        $old_status = get_post_meta( $order_id, '_mj_order_status', true );

        if ( $old_status === $status ) {
            return false;
        }

        // Update post status.
        $post_status = 'mj-' . $status;
        wp_update_post( array(
            'ID'          => $order_id,
            'post_status' => $post_status,
        ) );

        // Update meta.
        update_post_meta( $order_id, '_mj_order_status', $status );

        // Update timestamps.
        if ( 'paid' === $status ) {
            update_post_meta( $order_id, '_mj_paid_at', current_time( 'mysql' ) );
        } elseif ( 'completed' === $status ) {
            update_post_meta( $order_id, '_mj_completed_at', current_time( 'mysql' ) );
        }

        // Add order note.
        if ( $note ) {
            $this->add_note( $order_id, $note );
        }

        /**
         * Fires when order status changes.
         *
         * @param int    $order_id   Order ID.
         * @param string $status     New status.
         * @param string $old_status Old status.
         */
        do_action( 'mj_order_status_changed', $order_id, $status, $old_status );
        do_action( 'mj_order_status_' . $status, $order_id, $old_status );

        // Send notifications.
        $this->send_status_notification( $order_id, $status, $old_status );

        // Log.
        mitzies_jerk_log(
            sprintf( 'Order #%d status changed from %s to %s', $order_id, $old_status, $status ),
            'info',
            array( 'order_id' => $order_id )
        );

        return true;
    }

    /**
     * Add order note.
     *
     * @since    1.0.0
     * @param    int    $order_id Order ID.
     * @param    string $note     Note content.
     * @param    bool   $customer Whether note is for customer.
     */
    public function add_note( $order_id, $note, $customer = false ) {
        $notes = get_post_meta( $order_id, '_mj_order_notes', true );

        if ( ! is_array( $notes ) ) {
            $notes = array();
        }

        $notes[] = array(
            'content'    => $note,
            'customer'   => $customer,
            'added_by'   => is_user_logged_in() ? get_current_user_id() : 0,
            'created_at' => current_time( 'mysql' ),
        );

        update_post_meta( $order_id, '_mj_order_notes', $notes );
    }

    /**
     * Get order notes.
     *
     * @since    1.0.0
     * @param    int  $order_id      Order ID.
     * @param    bool $customer_only Whether to get only customer notes.
     * @return   array
     */
    public function get_notes( $order_id, $customer_only = false ) {
        $notes = get_post_meta( $order_id, '_mj_order_notes', true );

        if ( ! is_array( $notes ) ) {
            return array();
        }

        if ( $customer_only ) {
            return array_filter( $notes, function( $note ) {
                return ! empty( $note['customer'] );
            } );
        }

        return $notes;
    }

    /**
     * Send status notification.
     *
     * @since    1.0.0
     * @param    int    $order_id   Order ID.
     * @param    string $status     New status.
     * @param    string $old_status Old status.
     */
    private function send_status_notification( $order_id, $status, $old_status ) {
        $emails = new Mitzies_Jerk_Emails();

        // Customer notifications.
        $customer_notifications = array( 'paid', 'processing', 'preparing', 'ready', 'delivering', 'completed', 'cancelled', 'refunded' );

        if ( in_array( $status, $customer_notifications, true ) ) {
            $emails->send_order_status_email( $order_id, $status );
        }

        // Admin notifications for new paid orders.
        if ( 'paid' === $status && 'pending' === $old_status ) {
            $emails->send_new_order_admin_email( $order_id );
        }
    }

    /**
     * Mark order as paid.
     *
     * @since    1.0.0
     * @param    int    $order_id       Order ID.
     * @param    string $transaction_id Transaction ID from payment gateway.
     * @return   bool
     */
    public function mark_paid( $order_id, $transaction_id = '' ) {
        if ( $transaction_id ) {
            update_post_meta( $order_id, '_mj_transaction_id', $transaction_id );
        }

        return $this->update_status( $order_id, 'paid', __( 'Payment received.', 'mitzies-jerk' ) );
    }

    /**
     * Cancel order.
     *
     * @since    1.0.0
     * @param    int    $order_id Order ID.
     * @param    string $reason   Cancellation reason.
     * @return   bool
     */
    public function cancel( $order_id, $reason = '' ) {
        // Restore stock.
        $items = Mitzies_Jerk_Database::get_order_items( $order_id );

        foreach ( $items as $item ) {
            $current_stock = (int) get_post_meta( $item->food_item_id, '_mj_stock_quantity', true );
            update_post_meta( $item->food_item_id, '_mj_stock_quantity', $current_stock + $item->quantity );
            update_post_meta( $item->food_item_id, '_mj_stock_status', 'instock' );
        }

        $note = $reason ? sprintf( __( 'Order cancelled. Reason: %s', 'mitzies-jerk' ), $reason ) : __( 'Order cancelled.', 'mitzies-jerk' );

        return $this->update_status( $order_id, 'cancelled', $note );
    }

    /**
     * Get order data.
     *
     * @since    1.0.0
     * @param    string $key Optional key to get specific data.
     * @return   mixed
     */
    public function get( $key = '' ) {
        if ( $key ) {
            return isset( $this->data[ $key ] ) ? $this->data[ $key ] : null;
        }

        return $this->data;
    }

    /**
     * Get order ID.
     *
     * @since    1.0.0
     * @return   int
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Get order items.
     *
     * @since    1.0.0
     * @return   array
     */
    public function get_items() {
        return Mitzies_Jerk_Database::get_order_items( $this->id );
    }

    /**
     * Get formatted billing address.
     *
     * @since    1.0.0
     * @return   string
     */
    public function get_formatted_billing_address() {
        $billing = $this->data['billing'];

        if ( empty( $billing ) ) {
            return '';
        }

        return sprintf(
            '%s %s<br>%s<br>%s',
            esc_html( $billing['first_name'] ?? '' ),
            esc_html( $billing['last_name'] ?? '' ),
            esc_html( $billing['email'] ?? '' ),
            esc_html( $billing['phone'] ?? '' )
        );
    }

    /**
     * Get formatted delivery address.
     *
     * @since    1.0.0
     * @return   string
     */
    public function get_formatted_delivery_address() {
        $delivery = $this->data['delivery'];

        if ( empty( $delivery ) ) {
            return '';
        }

        $parts = array_filter( array(
            $delivery['address_1'] ?? '',
            $delivery['address_2'] ?? '',
            $delivery['city'] ?? '',
            $delivery['state'] ?? '',
            $delivery['postcode'] ?? '',
        ) );

        return esc_html( implode( ', ', $parts ) );
    }

    /**
     * Get payment countdown remaining time.
     *
     * @since    1.0.0
     * @return   int Seconds remaining, 0 if expired.
     */
    public function get_payment_countdown() {
        if ( 'pending' !== $this->data['status'] ) {
            return 0;
        }

        $expiry = strtotime( $this->data['payment_expiry'] );
        $now = current_time( 'timestamp' );

        return max( 0, $expiry - $now );
    }

    /**
     * Check if payment is expired.
     *
     * @since    1.0.0
     * @return   bool
     */
    public function is_payment_expired() {
        return 0 === $this->get_payment_countdown();
    }

    /**
     * Get all orders.
     *
     * @since    1.0.0
     * @param    array $args Query arguments.
     * @return   array
     */
    public static function get_orders( $args = array() ) {
        $defaults = array(
            'post_type'      => 'mj_order',
            'posts_per_page' => 20,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        $args = wp_parse_args( $args, $defaults );

        // Handle status filter.
        if ( ! empty( $args['status'] ) ) {
            $args['post_status'] = 'mj-' . $args['status'];
            unset( $args['status'] );
        } else {
            $args['post_status'] = array(
                'mj-pending', 'mj-paid', 'mj-processing', 'mj-preparing',
                'mj-ready', 'mj-delivering', 'mj-completed', 'mj-cancelled',
                'mj-refunded', 'mj-expired', 'mj-failed',
            );
        }

        $query = new WP_Query( $args );
        $orders = array();

        foreach ( $query->posts as $post ) {
            $orders[] = new self( $post->ID );
        }

        return array(
            'orders'      => $orders,
            'total'       => $query->found_posts,
            'total_pages' => $query->max_num_pages,
        );
    }

    /**
     * Get order by order number.
     *
     * @since    1.0.0
     * @param    string $order_number Order number.
     * @return   Mitzies_Jerk_Order|null
     */
    public static function get_by_order_number( $order_number ) {
        global $wpdb;

        $order_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_mj_order_number' AND meta_value = %s",
                $order_number
            )
        );

        if ( $order_id ) {
            return new self( $order_id );
        }

        return null;
    }

    /**
     * Get user orders.
     *
     * @since    1.0.0
     * @param    int   $user_id User ID.
     * @param    array $args    Additional query args.
     * @return   array
     */
    public static function get_user_orders( $user_id, $args = array() ) {
        $args['meta_query'] = array(
            array(
                'key'   => '_mj_user_id',
                'value' => $user_id,
            ),
        );

        return self::get_orders( $args );
    }
}
