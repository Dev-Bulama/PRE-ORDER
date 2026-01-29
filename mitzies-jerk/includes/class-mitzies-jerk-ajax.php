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

        $posted_data = array();
        $fields = array(
            'first_name', 'last_name', 'email', 'phone',
            'address_1', 'address_2', 'city', 'state', 'postcode',
            'delivery_date', 'delivery_time', 'instructions',
            'payment_method'
        );

        foreach ( $fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                $posted_data[ $field ] = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
            }
        }

        $checkout = new Mitzies_Jerk_Checkout();
        $result = $checkout->process_checkout( $posted_data );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        wp_send_json_success( $result );
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
            wp_send_json_error( __( 'Please enter your order number.', 'mitzies-jerk' ) );
        }

        $order = Mitzies_Jerk_Order::get_by_order_number( $order_number );

        if ( ! $order ) {
            wp_send_json_error( __( 'Order not found.', 'mitzies-jerk' ) );
        }

        // Verify email if provided.
        if ( $email ) {
            $billing = $order->get( 'billing' );
            if ( strtolower( $billing['email'] ) !== strtolower( $email ) ) {
                wp_send_json_error( __( 'Email does not match order.', 'mitzies-jerk' ) );
            }
        }

        $statuses = mitzies_jerk_get_order_statuses();
        $current_status = $order->get( 'status' );

        wp_send_json_success( array(
            'order_number'      => $order->get( 'order_number' ),
            'status'            => $current_status,
            'status_label'      => isset( $statuses[ $current_status ] ) ? $statuses[ $current_status ] : $current_status,
            'delivery_datetime' => $order->get( 'delivery_datetime' ),
            'total'             => mitzies_jerk_format_price( $order->get( 'total' ) ),
            'created_at'        => $order->get( 'created_at' ),
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
                <div class="mj-food-actions">
                    <?php if ( 'outofstock' !== $stock_status ) : ?>
                        <button class="mj-add-to-cart-btn" data-item-id="<?php echo esc_attr( $post_id ); ?>">
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
