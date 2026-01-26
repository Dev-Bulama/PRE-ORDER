<?php
/**
 * Shortcodes handler class.
 *
 * @package    Mitzies_Jerk
 * @subpackage Mitzies_Jerk/includes/shortcodes
 * @author     SkillScore IT Solutions and Training, Tijani Bulama
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcodes handler class.
 */
class Mitzies_Jerk_Shortcodes {

    /**
     * Register all shortcodes.
     */
    public function register_shortcodes() {
        add_shortcode( 'food_menu', array( $this, 'food_menu' ) );
        add_shortcode( 'food_cart', array( $this, 'food_cart' ) );
        add_shortcode( 'food_checkout', array( $this, 'food_checkout' ) );
        add_shortcode( 'food_order_received', array( $this, 'food_order_received' ) );
        add_shortcode( 'food_order_tracking', array( $this, 'food_order_tracking' ) );
        add_shortcode( 'food_my_account', array( $this, 'food_my_account' ) );
        add_shortcode( 'food_categories', array( $this, 'food_categories' ) );
        add_shortcode( 'food_featured', array( $this, 'food_featured' ) );
        add_shortcode( 'food_mini_cart', array( $this, 'food_mini_cart' ) );
    }

    /**
     * Food menu shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function food_menu( $atts ) {
        $atts = shortcode_atts( array(
            'category'   => '',
            'per_page'   => mitzies_jerk_get_option( 'items_per_page', 12 ),
            'columns'    => 3,
            'orderby'    => 'date',
            'order'      => 'DESC',
            'show_filter' => 'yes',
            'featured_only' => 'no',
        ), $atts, 'food_menu' );

        $args = array(
            'post_type'      => 'mj_food_item',
            'post_status'    => 'publish',
            'posts_per_page' => intval( $atts['per_page'] ),
            'orderby'        => $atts['orderby'],
            'order'          => $atts['order'],
            'paged'          => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1,
        );

        if ( ! empty( $atts['category'] ) ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'mj_food_category',
                    'field'    => 'slug',
                    'terms'    => explode( ',', $atts['category'] ),
                ),
            );
        }

        if ( 'yes' === $atts['featured_only'] ) {
            $args['meta_query'] = array(
                array(
                    'key'   => '_mj_is_featured',
                    'value' => 1,
                ),
            );
        }

        $query = new WP_Query( $args );
        $categories = get_terms( array(
            'taxonomy'   => 'mj_food_category',
            'hide_empty' => true,
        ) );

        ob_start();
        ?>
        <div class="mj-food-menu" data-columns="<?php echo esc_attr( $atts['columns'] ); ?>">
            <?php if ( 'yes' === $atts['show_filter'] && ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
                <div class="mj-menu-filter">
                    <div class="mj-filter-row">
                        <div class="mj-filter-categories">
                            <button class="mj-filter-btn active" data-category=""><?php esc_html_e( 'All', 'mitzies-jerk' ); ?></button>
                            <?php foreach ( $categories as $category ) : ?>
                                <button class="mj-filter-btn" data-category="<?php echo esc_attr( $category->term_id ); ?>">
                                    <?php echo esc_html( $category->name ); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <div class="mj-filter-search">
                            <input type="text" class="mj-search-input" placeholder="<?php esc_attr_e( 'Search...', 'mitzies-jerk' ); ?>">
                        </div>
                        <div class="mj-filter-sort">
                            <select class="mj-sort-select">
                                <option value="date"><?php esc_html_e( 'Latest', 'mitzies-jerk' ); ?></option>
                                <option value="price_low"><?php esc_html_e( 'Price: Low to High', 'mitzies-jerk' ); ?></option>
                                <option value="price_high"><?php esc_html_e( 'Price: High to Low', 'mitzies-jerk' ); ?></option>
                                <option value="name"><?php esc_html_e( 'Name', 'mitzies-jerk' ); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="mj-food-grid">
                <?php if ( $query->have_posts() ) : ?>
                    <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                        <?php $this->render_food_item( get_the_ID() ); ?>
                    <?php endwhile; ?>
                <?php else : ?>
                    <p class="mj-no-items"><?php esc_html_e( 'No food items found.', 'mitzies-jerk' ); ?></p>
                <?php endif; ?>
            </div>

            <?php if ( $query->max_num_pages > 1 ) : ?>
                <div class="mj-pagination">
                    <?php
                    echo paginate_links( array(
                        'total'   => $query->max_num_pages,
                        'current' => max( 1, get_query_var( 'paged' ) ),
                    ) );
                    ?>
                </div>
            <?php endif; ?>

            <div class="mj-loading" style="display: none;">
                <span class="mj-spinner"></span>
            </div>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Render a single food item.
     *
     * @param int $post_id Post ID.
     */
    private function render_food_item( $post_id ) {
        $price = get_post_meta( $post_id, '_mj_price', true );
        $sale_price = get_post_meta( $post_id, '_mj_sale_price', true );
        $stock_status = get_post_meta( $post_id, '_mj_stock_status', true );
        $is_featured = get_post_meta( $post_id, '_mj_is_featured', true );
        $rating = Mitzies_Jerk_Database::get_average_rating( $post_id );
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
                <?php if ( $rating > 0 ) : ?>
                    <div class="mj-food-rating">
                        <?php $this->render_stars( $rating ); ?>
                        <span class="mj-rating-value">(<?php echo esc_html( $rating ); ?>)</span>
                    </div>
                <?php endif; ?>
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
                        <button class="mj-add-to-cart-btn" data-product-id="<?php echo esc_attr( $post_id ); ?>">
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
     * Render star rating.
     *
     * @param float $rating Rating value.
     */
    private function render_stars( $rating ) {
        $full_stars = floor( $rating );
        $half_star = ( $rating - $full_stars ) >= 0.5;
        $empty_stars = 5 - $full_stars - ( $half_star ? 1 : 0 );

        echo '<div class="mj-stars">';
        for ( $i = 0; $i < $full_stars; $i++ ) {
            echo '<span class="dashicons dashicons-star-filled"></span>';
        }
        if ( $half_star ) {
            echo '<span class="dashicons dashicons-star-half"></span>';
        }
        for ( $i = 0; $i < $empty_stars; $i++ ) {
            echo '<span class="dashicons dashicons-star-empty"></span>';
        }
        echo '</div>';
    }

    /**
     * Food cart shortcode.
     *
     * @return string
     */
    public function food_cart() {
        global $mitzies_jerk;

        $cart = $mitzies_jerk->cart->get_cart_for_display();

        ob_start();
        ?>
        <div class="mj-cart-page">
            <?php if ( empty( $cart['items'] ) ) : ?>
                <div class="mj-cart-empty">
                    <span class="dashicons dashicons-cart"></span>
                    <h2><?php esc_html_e( 'Your cart is empty', 'mitzies-jerk' ); ?></h2>
                    <p><?php esc_html_e( 'Browse our menu and add some delicious items!', 'mitzies-jerk' ); ?></p>
                    <a href="<?php echo esc_url( get_permalink( get_option( 'mitzies_jerk_menu_page_id' ) ) ); ?>" class="mj-btn mj-btn-primary">
                        <?php esc_html_e( 'Browse Menu', 'mitzies-jerk' ); ?>
                    </a>
                </div>
            <?php else : ?>
                <div class="mj-cart-content">
                    <table class="mj-cart-table">
                        <thead>
                            <tr>
                                <th class="mj-col-product"><?php esc_html_e( 'Product', 'mitzies-jerk' ); ?></th>
                                <th class="mj-col-price"><?php esc_html_e( 'Price', 'mitzies-jerk' ); ?></th>
                                <th class="mj-col-quantity"><?php esc_html_e( 'Quantity', 'mitzies-jerk' ); ?></th>
                                <th class="mj-col-total"><?php esc_html_e( 'Total', 'mitzies-jerk' ); ?></th>
                                <th class="mj-col-remove"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $cart['items'] as $key => $item ) : ?>
                                <tr class="mj-cart-item" data-key="<?php echo esc_attr( $key ); ?>">
                                    <td class="mj-col-product">
                                        <div class="mj-product-info">
                                            <?php if ( $item['image'] ) : ?>
                                                <img src="<?php echo esc_url( $item['image'] ); ?>" alt="<?php echo esc_attr( $item['name'] ); ?>">
                                            <?php endif; ?>
                                            <div class="mj-product-details">
                                                <a href="<?php echo esc_url( $item['permalink'] ); ?>"><?php echo esc_html( $item['name'] ); ?></a>
                                                <?php if ( ! empty( $item['addons'] ) ) : ?>
                                                    <div class="mj-item-addons">
                                                        <?php foreach ( $item['addons'] as $addon ) : ?>
                                                            <small><?php echo esc_html( $addon['name'] ); ?> (+<?php echo esc_html( mitzies_jerk_format_price( $addon['price'] ) ); ?>)</small>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="mj-col-price"><?php echo esc_html( mitzies_jerk_format_price( $item['price'] + $item['addon_total'] ) ); ?></td>
                                    <td class="mj-col-quantity">
                                        <div class="mj-quantity-control">
                                            <button type="button" class="mj-qty-minus">-</button>
                                            <input type="number" class="mj-qty-input" value="<?php echo esc_attr( $item['quantity'] ); ?>" min="1" max="99">
                                            <button type="button" class="mj-qty-plus">+</button>
                                        </div>
                                    </td>
                                    <td class="mj-col-total"><?php echo esc_html( mitzies_jerk_format_price( $item['line_total'] ) ); ?></td>
                                    <td class="mj-col-remove">
                                        <button type="button" class="mj-remove-item" title="<?php esc_attr_e( 'Remove', 'mitzies-jerk' ); ?>">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="mj-cart-actions">
                        <div class="mj-coupon-form">
                            <input type="text" class="mj-coupon-input" placeholder="<?php esc_attr_e( 'Coupon code', 'mitzies-jerk' ); ?>">
                            <button type="button" class="mj-btn mj-apply-coupon"><?php esc_html_e( 'Apply', 'mitzies-jerk' ); ?></button>
                        </div>
                    </div>

                    <?php if ( ! empty( $cart['applied_coupons'] ) ) : ?>
                        <div class="mj-applied-coupons">
                            <?php foreach ( $cart['applied_coupons'] as $coupon ) : ?>
                                <span class="mj-coupon-tag">
                                    <?php echo esc_html( $coupon ); ?>
                                    <button type="button" class="mj-remove-coupon" data-coupon="<?php echo esc_attr( $coupon ); ?>">&times;</button>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="mj-cart-totals">
                        <h3><?php esc_html_e( 'Cart Totals', 'mitzies-jerk' ); ?></h3>
                        <table>
                            <tr>
                                <th><?php esc_html_e( 'Subtotal', 'mitzies-jerk' ); ?></th>
                                <td><?php echo esc_html( mitzies_jerk_format_price( $cart['subtotal'] + $cart['addon_total'] ) ); ?></td>
                            </tr>
                            <?php if ( $cart['discount'] > 0 ) : ?>
                                <tr>
                                    <th><?php esc_html_e( 'Discount', 'mitzies-jerk' ); ?></th>
                                    <td>-<?php echo esc_html( mitzies_jerk_format_price( $cart['discount'] ) ); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <th><?php esc_html_e( 'Delivery Fee', 'mitzies-jerk' ); ?></th>
                                <td>
                                    <?php if ( $cart['delivery_fee'] > 0 ) : ?>
                                        <?php echo esc_html( mitzies_jerk_format_price( $cart['delivery_fee'] ) ); ?>
                                    <?php else : ?>
                                        <?php esc_html_e( 'Free', 'mitzies-jerk' ); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if ( $cart['tax'] > 0 ) : ?>
                                <tr>
                                    <th><?php esc_html_e( 'Tax', 'mitzies-jerk' ); ?></th>
                                    <td><?php echo esc_html( mitzies_jerk_format_price( $cart['tax'] ) ); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr class="total">
                                <th><?php esc_html_e( 'Total', 'mitzies-jerk' ); ?></th>
                                <td><?php echo esc_html( mitzies_jerk_format_price( $cart['total'] ) ); ?></td>
                            </tr>
                        </table>
                        <a href="<?php echo esc_url( get_permalink( get_option( 'mitzies_jerk_checkout_page_id' ) ) ); ?>" class="mj-btn mj-btn-primary mj-btn-block">
                            <?php esc_html_e( 'Proceed to Checkout', 'mitzies-jerk' ); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Food checkout shortcode.
     *
     * @return string
     */
    public function food_checkout() {
        global $mitzies_jerk;

        $cart = $mitzies_jerk->cart->get_cart_for_display();

        if ( empty( $cart['items'] ) ) {
            return '<div class="mj-notice mj-notice-info">' . esc_html__( 'Your cart is empty.', 'mitzies-jerk' ) . '</div>';
        }

        $checkout = new Mitzies_Jerk_Checkout();
        $fields = $checkout->get_fields();
        $gateways = $checkout->get_available_payment_gateways();
        $min_date = $checkout->get_min_delivery_date();
        $max_date = $checkout->get_max_delivery_date();
        $time_slots = mitzies_jerk_get_option( 'delivery_time_slots', array() );
        $min_hours = mitzies_jerk_get_min_preorder_hours();

        ob_start();
        ?>
        <div class="mj-checkout-page">
            <div class="mj-checkout-notice">
                <span class="dashicons dashicons-info"></span>
                <?php
                printf(
                    /* translators: %d: Minimum pre-order hours */
                    esc_html__( 'Orders must be placed at least %d hours in advance.', 'mitzies-jerk' ),
                    $min_hours
                );
                ?>
            </div>

            <form id="mj-checkout-form" class="mj-checkout-form">
                <?php wp_nonce_field( 'mj_ajax_nonce', 'mj_checkout_nonce' ); ?>

                <div class="mj-checkout-grid">
                    <div class="mj-checkout-main">
                        <!-- Billing Details -->
                        <div class="mj-checkout-section">
                            <h3><?php esc_html_e( 'Contact Information', 'mitzies-jerk' ); ?></h3>
                            <div class="mj-form-row mj-row-2">
                                <?php foreach ( $fields['billing'] as $key => $field ) : ?>
                                    <div class="mj-form-group">
                                        <label for="<?php echo esc_attr( $key ); ?>">
                                            <?php echo esc_html( $field['label'] ); ?>
                                            <?php if ( $field['required'] ) : ?><span class="required">*</span><?php endif; ?>
                                        </label>
                                        <input type="<?php echo esc_attr( $field['type'] ); ?>"
                                               id="<?php echo esc_attr( $key ); ?>"
                                               name="<?php echo esc_attr( $key ); ?>"
                                               placeholder="<?php echo esc_attr( $field['placeholder'] ?? '' ); ?>"
                                               <?php echo $field['required'] ? 'required' : ''; ?>>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Delivery Details -->
                        <div class="mj-checkout-section">
                            <h3><?php esc_html_e( 'Delivery Details', 'mitzies-jerk' ); ?></h3>
                            <div class="mj-form-row">
                                <div class="mj-form-group">
                                    <label for="address_1"><?php esc_html_e( 'Street Address', 'mitzies-jerk' ); ?> <span class="required">*</span></label>
                                    <input type="text" id="address_1" name="address_1" placeholder="<?php esc_attr_e( 'House number and street name', 'mitzies-jerk' ); ?>" required>
                                </div>
                            </div>
                            <div class="mj-form-row">
                                <div class="mj-form-group">
                                    <label for="address_2"><?php esc_html_e( 'Apartment, Suite, etc.', 'mitzies-jerk' ); ?></label>
                                    <input type="text" id="address_2" name="address_2" placeholder="<?php esc_attr_e( 'Apartment, suite, unit, etc. (optional)', 'mitzies-jerk' ); ?>">
                                </div>
                            </div>
                            <div class="mj-form-row mj-row-3">
                                <div class="mj-form-group">
                                    <label for="city"><?php esc_html_e( 'City', 'mitzies-jerk' ); ?> <span class="required">*</span></label>
                                    <input type="text" id="city" name="city" required>
                                </div>
                                <div class="mj-form-group">
                                    <label for="state"><?php esc_html_e( 'State/Region', 'mitzies-jerk' ); ?></label>
                                    <input type="text" id="state" name="state">
                                </div>
                                <div class="mj-form-group">
                                    <label for="postcode"><?php esc_html_e( 'Postcode/ZIP', 'mitzies-jerk' ); ?></label>
                                    <input type="text" id="postcode" name="postcode">
                                </div>
                            </div>
                            <div class="mj-form-row mj-row-2">
                                <div class="mj-form-group">
                                    <label for="delivery_date"><?php esc_html_e( 'Delivery Date', 'mitzies-jerk' ); ?> <span class="required">*</span></label>
                                    <input type="date" id="delivery_date" name="delivery_date"
                                           min="<?php echo esc_attr( $min_date ); ?>"
                                           max="<?php echo esc_attr( $max_date ); ?>" required>
                                </div>
                                <div class="mj-form-group">
                                    <label for="delivery_time"><?php esc_html_e( 'Delivery Time', 'mitzies-jerk' ); ?> <span class="required">*</span></label>
                                    <select id="delivery_time" name="delivery_time" required>
                                        <option value=""><?php esc_html_e( 'Select a time slot', 'mitzies-jerk' ); ?></option>
                                        <?php foreach ( $time_slots as $slot ) : ?>
                                            <option value="<?php echo esc_attr( $slot['start'] . '-' . $slot['end'] ); ?>">
                                                <?php echo esc_html( $slot['start'] . ' - ' . $slot['end'] ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mj-form-row">
                                <div class="mj-form-group">
                                    <label for="instructions"><?php esc_html_e( 'Delivery Instructions', 'mitzies-jerk' ); ?></label>
                                    <textarea id="instructions" name="instructions" rows="3" placeholder="<?php esc_attr_e( 'Any special instructions for delivery (optional)', 'mitzies-jerk' ); ?>"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="mj-checkout-section">
                            <h3><?php esc_html_e( 'Payment Method', 'mitzies-jerk' ); ?></h3>
                            <?php if ( ! empty( $gateways ) ) : ?>
                                <div class="mj-payment-methods">
                                    <?php $first = true; foreach ( $gateways as $gateway_id => $gateway ) : ?>
                                        <label class="mj-payment-method<?php echo $first ? ' selected' : ''; ?>">
                                            <input type="radio" name="payment_method" value="<?php echo esc_attr( $gateway_id ); ?>" <?php checked( $first ); ?>>
                                            <span class="mj-pm-info">
                                                <?php if ( ! empty( $gateway['icon'] ) ) : ?>
                                                    <img src="<?php echo esc_url( $gateway['icon'] ); ?>" alt="<?php echo esc_attr( $gateway['title'] ); ?>">
                                                <?php endif; ?>
                                                <span class="mj-pm-title"><?php echo esc_html( $gateway['title'] ); ?></span>
                                                <span class="mj-pm-desc"><?php echo esc_html( $gateway['description'] ); ?></span>
                                            </span>
                                        </label>
                                    <?php $first = false; endforeach; ?>
                                </div>
                            <?php else : ?>
                                <p class="mj-notice mj-notice-error"><?php esc_html_e( 'No payment methods available.', 'mitzies-jerk' ); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mj-checkout-sidebar">
                        <div class="mj-order-summary">
                            <h3><?php esc_html_e( 'Order Summary', 'mitzies-jerk' ); ?></h3>
                            <div class="mj-order-items">
                                <?php foreach ( $cart['items'] as $item ) : ?>
                                    <div class="mj-summary-item">
                                        <span class="mj-item-name">
                                            <?php echo esc_html( $item['name'] ); ?>
                                            <span class="mj-item-qty">&times;<?php echo esc_html( $item['quantity'] ); ?></span>
                                        </span>
                                        <span class="mj-item-price"><?php echo esc_html( mitzies_jerk_format_price( $item['line_total'] ) ); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mj-order-totals">
                                <div class="mj-total-row">
                                    <span><?php esc_html_e( 'Subtotal', 'mitzies-jerk' ); ?></span>
                                    <span><?php echo esc_html( mitzies_jerk_format_price( $cart['subtotal'] + $cart['addon_total'] ) ); ?></span>
                                </div>
                                <?php if ( $cart['discount'] > 0 ) : ?>
                                    <div class="mj-total-row discount">
                                        <span><?php esc_html_e( 'Discount', 'mitzies-jerk' ); ?></span>
                                        <span>-<?php echo esc_html( mitzies_jerk_format_price( $cart['discount'] ) ); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="mj-total-row">
                                    <span><?php esc_html_e( 'Delivery', 'mitzies-jerk' ); ?></span>
                                    <span>
                                        <?php echo $cart['delivery_fee'] > 0 ? esc_html( mitzies_jerk_format_price( $cart['delivery_fee'] ) ) : esc_html__( 'Free', 'mitzies-jerk' ); ?>
                                    </span>
                                </div>
                                <?php if ( $cart['tax'] > 0 ) : ?>
                                    <div class="mj-total-row">
                                        <span><?php esc_html_e( 'Tax', 'mitzies-jerk' ); ?></span>
                                        <span><?php echo esc_html( mitzies_jerk_format_price( $cart['tax'] ) ); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="mj-total-row total">
                                    <span><?php esc_html_e( 'Total', 'mitzies-jerk' ); ?></span>
                                    <span><?php echo esc_html( mitzies_jerk_format_price( $cart['total'] ) ); ?></span>
                                </div>
                            </div>
                            <button type="submit" class="mj-btn mj-btn-primary mj-btn-block mj-place-order">
                                <?php esc_html_e( 'Place Order', 'mitzies-jerk' ); ?>
                            </button>
                            <p class="mj-terms-notice">
                                <?php esc_html_e( 'By placing an order, you agree to our terms and conditions.', 'mitzies-jerk' ); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Order received shortcode.
     *
     * @return string
     */
    public function food_order_received() {
        $order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;

        if ( ! $order_id ) {
            return '<div class="mj-notice mj-notice-error">' . esc_html__( 'Order not found.', 'mitzies-jerk' ) . '</div>';
        }

        $order = new Mitzies_Jerk_Order( $order_id );

        if ( ! $order->get_id() ) {
            return '<div class="mj-notice mj-notice-error">' . esc_html__( 'Order not found.', 'mitzies-jerk' ) . '</div>';
        }

        $billing = $order->get( 'billing' );
        $delivery = $order->get( 'delivery' );
        $items = $order->get_items();

        ob_start();
        ?>
        <div class="mj-order-received">
            <div class="mj-order-success">
                <span class="dashicons dashicons-yes-alt"></span>
                <h2><?php esc_html_e( 'Thank you for your order!', 'mitzies-jerk' ); ?></h2>
                <p><?php esc_html_e( 'Your order has been received and is being processed.', 'mitzies-jerk' ); ?></p>
            </div>

            <div class="mj-order-details">
                <div class="mj-order-info-grid">
                    <div class="mj-info-item">
                        <span class="mj-info-label"><?php esc_html_e( 'Order Number', 'mitzies-jerk' ); ?></span>
                        <span class="mj-info-value"><?php echo esc_html( $order->get( 'order_number' ) ); ?></span>
                    </div>
                    <div class="mj-info-item">
                        <span class="mj-info-label"><?php esc_html_e( 'Date', 'mitzies-jerk' ); ?></span>
                        <span class="mj-info-value"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $order->get( 'created_at' ) ) ) ); ?></span>
                    </div>
                    <div class="mj-info-item">
                        <span class="mj-info-label"><?php esc_html_e( 'Total', 'mitzies-jerk' ); ?></span>
                        <span class="mj-info-value"><?php echo esc_html( mitzies_jerk_format_price( $order->get( 'total' ) ) ); ?></span>
                    </div>
                    <div class="mj-info-item">
                        <span class="mj-info-label"><?php esc_html_e( 'Delivery', 'mitzies-jerk' ); ?></span>
                        <span class="mj-info-value"><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $order->get( 'delivery_datetime' ) ) ) ); ?></span>
                    </div>
                </div>

                <h3><?php esc_html_e( 'Order Items', 'mitzies-jerk' ); ?></h3>
                <table class="mj-order-items-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Product', 'mitzies-jerk' ); ?></th>
                            <th><?php esc_html_e( 'Total', 'mitzies-jerk' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $items as $item ) :
                            $food_item = get_post( $item->food_item_id );
                        ?>
                            <tr>
                                <td>
                                    <?php echo $food_item ? esc_html( $food_item->post_title ) : esc_html__( 'Item', 'mitzies-jerk' ); ?>
                                    <strong>&times;<?php echo esc_html( $item->quantity ); ?></strong>
                                </td>
                                <td><?php echo esc_html( mitzies_jerk_format_price( $item->subtotal ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="mj-order-addresses">
                    <div class="mj-address-box">
                        <h4><?php esc_html_e( 'Delivery Address', 'mitzies-jerk' ); ?></h4>
                        <address>
                            <?php echo esc_html( $billing['first_name'] . ' ' . $billing['last_name'] ); ?><br>
                            <?php echo esc_html( $delivery['address_1'] ); ?><br>
                            <?php if ( ! empty( $delivery['address_2'] ) ) : ?>
                                <?php echo esc_html( $delivery['address_2'] ); ?><br>
                            <?php endif; ?>
                            <?php echo esc_html( $delivery['city'] ); ?>
                            <?php if ( ! empty( $delivery['state'] ) ) : ?>, <?php echo esc_html( $delivery['state'] ); ?><?php endif; ?>
                            <?php echo esc_html( $delivery['postcode'] ); ?><br>
                            <?php echo esc_html( $billing['phone'] ); ?>
                        </address>
                    </div>
                </div>

                <p class="mj-order-note">
                    <?php esc_html_e( 'A confirmation email has been sent to your email address.', 'mitzies-jerk' ); ?>
                </p>

                <a href="<?php echo esc_url( add_query_arg( 'order', $order->get( 'order_number' ), get_permalink( get_option( 'mitzies_jerk_order_tracking_page_id' ) ) ) ); ?>" class="mj-btn mj-btn-primary">
                    <?php esc_html_e( 'Track Your Order', 'mitzies-jerk' ); ?>
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Order tracking shortcode.
     *
     * @return string
     */
    public function food_order_tracking() {
        $order_number = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : '';

        ob_start();
        ?>
        <div class="mj-order-tracking">
            <div class="mj-tracking-form-wrapper">
                <h2><?php esc_html_e( 'Track Your Order', 'mitzies-jerk' ); ?></h2>
                <form id="mj-tracking-form" class="mj-tracking-form">
                    <div class="mj-form-group">
                        <label for="tracking_order_number"><?php esc_html_e( 'Order Number', 'mitzies-jerk' ); ?></label>
                        <input type="text" id="tracking_order_number" name="order_number" value="<?php echo esc_attr( $order_number ); ?>" placeholder="<?php esc_attr_e( 'e.g., MJ000001', 'mitzies-jerk' ); ?>" required>
                    </div>
                    <div class="mj-form-group">
                        <label for="tracking_email"><?php esc_html_e( 'Email Address', 'mitzies-jerk' ); ?></label>
                        <input type="email" id="tracking_email" name="email" placeholder="<?php esc_attr_e( 'Email used for the order', 'mitzies-jerk' ); ?>" required>
                    </div>
                    <button type="submit" class="mj-btn mj-btn-primary"><?php esc_html_e( 'Track Order', 'mitzies-jerk' ); ?></button>
                </form>
            </div>

            <div id="mj-tracking-result" class="mj-tracking-result" style="display: none;">
                <!-- Results will be loaded here via AJAX -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * My account shortcode.
     *
     * @return string
     */
    public function food_my_account() {
        if ( ! is_user_logged_in() ) {
            return wp_login_form( array( 'echo' => false ) );
        }

        $user_id = get_current_user_id();
        $orders = Mitzies_Jerk_Order::get_user_orders( $user_id, array( 'posts_per_page' => 10 ) );

        ob_start();
        ?>
        <div class="mj-my-account">
            <h2><?php esc_html_e( 'My Orders', 'mitzies-jerk' ); ?></h2>

            <?php if ( empty( $orders['orders'] ) ) : ?>
                <p><?php esc_html_e( 'You have not placed any orders yet.', 'mitzies-jerk' ); ?></p>
            <?php else : ?>
                <table class="mj-orders-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Order', 'mitzies-jerk' ); ?></th>
                            <th><?php esc_html_e( 'Date', 'mitzies-jerk' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'mitzies-jerk' ); ?></th>
                            <th><?php esc_html_e( 'Total', 'mitzies-jerk' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'mitzies-jerk' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $orders['orders'] as $order ) :
                            $statuses = mitzies_jerk_get_order_statuses();
                            $status = $order->get( 'status' );
                        ?>
                            <tr>
                                <td>#<?php echo esc_html( $order->get( 'order_number' ) ); ?></td>
                                <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $order->get( 'created_at' ) ) ) ); ?></td>
                                <td>
                                    <span class="mj-status-badge status-<?php echo esc_attr( $status ); ?>">
                                        <?php echo esc_html( isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status ); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html( mitzies_jerk_format_price( $order->get( 'total' ) ) ); ?></td>
                                <td>
                                    <a href="<?php echo esc_url( add_query_arg( 'order', $order->get( 'order_number' ), get_permalink( get_option( 'mitzies_jerk_order_tracking_page_id' ) ) ) ); ?>">
                                        <?php esc_html_e( 'Track', 'mitzies-jerk' ); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Food categories shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function food_categories( $atts ) {
        $atts = shortcode_atts( array(
            'columns'     => 4,
            'show_count'  => 'yes',
            'hide_empty'  => 'yes',
            'parent'      => '',
        ), $atts, 'food_categories' );

        $args = array(
            'taxonomy'   => 'mj_food_category',
            'hide_empty' => 'yes' === $atts['hide_empty'],
        );

        if ( '' !== $atts['parent'] ) {
            $args['parent'] = intval( $atts['parent'] );
        }

        $categories = get_terms( $args );

        if ( empty( $categories ) || is_wp_error( $categories ) ) {
            return '';
        }

        ob_start();
        ?>
        <div class="mj-categories-grid" style="--columns: <?php echo esc_attr( $atts['columns'] ); ?>">
            <?php foreach ( $categories as $category ) : ?>
                <a href="<?php echo esc_url( get_term_link( $category ) ); ?>" class="mj-category-card">
                    <?php
                    $thumbnail_id = get_term_meta( $category->term_id, 'thumbnail_id', true );
                    if ( $thumbnail_id ) {
                        echo wp_get_attachment_image( $thumbnail_id, 'medium' );
                    } else {
                        echo '<div class="mj-category-placeholder"><span class="dashicons dashicons-food"></span></div>';
                    }
                    ?>
                    <div class="mj-category-info">
                        <h3><?php echo esc_html( $category->name ); ?></h3>
                        <?php if ( 'yes' === $atts['show_count'] ) : ?>
                            <span class="mj-category-count"><?php echo esc_html( $category->count ); ?> <?php esc_html_e( 'items', 'mitzies-jerk' ); ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Food featured shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function food_featured( $atts ) {
        $atts = shortcode_atts( array(
            'per_page' => 4,
            'columns'  => 4,
        ), $atts, 'food_featured' );

        $atts['featured_only'] = 'yes';
        $atts['show_filter'] = 'no';

        return $this->food_menu( $atts );
    }

    /**
     * Mini cart shortcode.
     *
     * @return string
     */
    public function food_mini_cart() {
        global $mitzies_jerk;

        $cart_count = $mitzies_jerk->cart->get_cart_count();
        $cart_total = $mitzies_jerk->cart->get_total();

        ob_start();
        ?>
        <a href="<?php echo esc_url( get_permalink( get_option( 'mitzies_jerk_cart_page_id' ) ) ); ?>" class="mj-mini-cart">
            <span class="mj-cart-icon">
                <span class="dashicons dashicons-cart"></span>
                <span class="mj-cart-count"><?php echo esc_html( $cart_count ); ?></span>
            </span>
            <span class="mj-cart-total"><?php echo esc_html( mitzies_jerk_format_price( $cart_total ) ); ?></span>
        </a>
        <?php
        return ob_get_clean();
    }
}
