<?php
/**
 * Single Food Item Template
 *
 * @package    Mitzies_Jerk
 * @subpackage Mitzies_Jerk/public/templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

while ( have_posts() ) :
    the_post();

    $post_id      = get_the_ID();
    $price        = get_post_meta( $post_id, '_mj_price', true );
    $sale_price   = get_post_meta( $post_id, '_mj_sale_price', true );
    $stock_status = get_post_meta( $post_id, '_mj_stock_status', true );
    $stock_qty    = get_post_meta( $post_id, '_mj_stock_quantity', true );
    $ingredients  = get_post_meta( $post_id, '_mj_ingredients', true );
    $prep_time    = get_post_meta( $post_id, '_mj_preparation_time', true );
    $calories     = get_post_meta( $post_id, '_mj_calories', true );
    $is_featured  = get_post_meta( $post_id, '_mj_is_featured', true );
    $is_spicy     = get_post_meta( $post_id, '_mj_is_spicy', true );
    $is_vegetarian = get_post_meta( $post_id, '_mj_is_vegetarian', true );
    $allergens    = get_post_meta( $post_id, '_mj_allergens', true );
    $serving_size = get_post_meta( $post_id, '_mj_serving_size', true );

    // Get addons from database (primary) or post meta (fallback)
    $db_addons = array();

    // Try the Database class method first.
    if ( class_exists( 'Mitzies_Jerk_Database' ) ) {
        $db_addons = Mitzies_Jerk_Database::get_food_addons( $post_id );
    }

    // Fallback: direct database query if class method returns empty.
    if ( empty( $db_addons ) ) {
        global $wpdb;
        $table_prefix = defined( 'MITZIES_JERK_TABLE_PREFIX' ) ? MITZIES_JERK_TABLE_PREFIX : 'mitzies_jerk_';
        $addons_table = $wpdb->prefix . $table_prefix . 'addons';

        // Check if table exists before querying.
        $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $addons_table ) );

        if ( $table_exists ) {
            $db_addons = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM `$addons_table` WHERE food_item_id = %d AND status = 'active' ORDER BY sort_order ASC",
                    $post_id
                )
            );
        }
    }

    $meta_addons = get_post_meta( $post_id, '_mj_addons', true );

    // Use database addons if available, otherwise fall back to meta
    $addons = ! empty( $db_addons ) ? $db_addons : ( is_array( $meta_addons ) ? $meta_addons : array() );

    // Get extras/add-ons categories
    $extras = get_post_meta( $post_id, '_mj_extras', true );
    if ( ! is_array( $extras ) ) {
        $extras = array();
    }

    $categories   = get_the_terms( $post_id, 'mj_food_category' );
    $tags         = get_the_terms( $post_id, 'mj_food_tag' );

    $current_price = $sale_price ? $sale_price : $price;
    $in_stock      = 'outofstock' !== $stock_status;

    $cart_page_id = get_option( 'mitzies_jerk_cart_page_id' );
    $menu_page_id = get_option( 'mitzies_jerk_menu_page_id' );

    // Get rating
    $rating = Mitzies_Jerk_Database::get_average_rating( $post_id );
    ?>

    <div class="mj-wrapper">
        <div class="mj-single-food-container">
            <!-- Breadcrumb Navigation -->
            <nav class="mj-breadcrumb" aria-label="Breadcrumb">
                <ol class="mj-breadcrumb-list">
                    <li><a href="<?php echo esc_url( home_url() ); ?>"><?php esc_html_e( 'Home', 'mitzies-jerk' ); ?></a></li>
                    <?php if ( $menu_page_id ) : ?>
                        <li><a href="<?php echo esc_url( get_permalink( $menu_page_id ) ); ?>"><?php esc_html_e( 'Menu', 'mitzies-jerk' ); ?></a></li>
                    <?php endif; ?>
                    <?php if ( $categories && ! is_wp_error( $categories ) ) : ?>
                        <li><a href="<?php echo esc_url( get_term_link( $categories[0] ) ); ?>"><?php echo esc_html( $categories[0]->name ); ?></a></li>
                    <?php endif; ?>
                    <li class="current"><?php the_title(); ?></li>
                </ol>
            </nav>

            <div class="mj-single-food-item">
                <!-- Product Image Section -->
                <div class="mj-single-food-gallery">
                    <div class="mj-single-food-image">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <?php the_post_thumbnail( 'large', array( 'class' => 'mj-main-image' ) ); ?>
                        <?php else : ?>
                            <div class="mj-no-image-placeholder">
                                <span class="dashicons dashicons-food"></span>
                                <span><?php esc_html_e( 'No Image', 'mitzies-jerk' ); ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Badges -->
                        <div class="mj-badge-container">
                            <?php if ( $sale_price ) : ?>
                                <span class="mj-badge mj-badge-sale">
                                    <?php
                                    $discount = round( ( ( $price - $sale_price ) / $price ) * 100 );
                                    printf( esc_html__( '%d%% OFF', 'mitzies-jerk' ), $discount );
                                    ?>
                                </span>
                            <?php endif; ?>
                            <?php if ( $is_featured ) : ?>
                                <span class="mj-badge mj-badge-featured"><?php esc_html_e( 'Featured', 'mitzies-jerk' ); ?></span>
                            <?php endif; ?>
                            <?php if ( $is_spicy ) : ?>
                                <span class="mj-badge mj-badge-spicy"><?php esc_html_e( 'Spicy', 'mitzies-jerk' ); ?></span>
                            <?php endif; ?>
                            <?php if ( $is_vegetarian ) : ?>
                                <span class="mj-badge mj-badge-veg"><?php esc_html_e( 'Vegetarian', 'mitzies-jerk' ); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Product Details Section -->
                <div class="mj-single-food-details">
                    <!-- Title and Category -->
                    <div class="mj-product-header">
                        <h1 class="mj-single-food-title"><?php the_title(); ?></h1>

                        <?php if ( $categories && ! is_wp_error( $categories ) ) : ?>
                            <div class="mj-single-food-categories">
                                <?php foreach ( $categories as $category ) : ?>
                                    <a href="<?php echo esc_url( get_term_link( $category ) ); ?>" class="mj-category-tag">
                                        <?php echo esc_html( $category->name ); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ( $rating > 0 ) : ?>
                            <div class="mj-product-rating">
                                <div class="mj-stars">
                                    <?php
                                    $full_stars = floor( $rating );
                                    $half_star = ( $rating - $full_stars ) >= 0.5;
                                    for ( $i = 0; $i < $full_stars; $i++ ) {
                                        echo '<span class="dashicons dashicons-star-filled"></span>';
                                    }
                                    if ( $half_star ) {
                                        echo '<span class="dashicons dashicons-star-half"></span>';
                                    }
                                    for ( $i = 0; $i < ( 5 - $full_stars - ( $half_star ? 1 : 0 ) ); $i++ ) {
                                        echo '<span class="dashicons dashicons-star-empty"></span>';
                                    }
                                    ?>
                                </div>
                                <span class="mj-rating-value"><?php echo esc_html( $rating ); ?> / 5</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Price -->
                    <div class="mj-single-food-price">
                        <?php if ( $sale_price ) : ?>
                            <span class="mj-original-price"><?php echo esc_html( mitzies_jerk_format_price( $price ) ); ?></span>
                            <span class="mj-current-price"><?php echo esc_html( mitzies_jerk_format_price( $sale_price ) ); ?></span>
                        <?php else : ?>
                            <span class="mj-current-price"><?php echo esc_html( mitzies_jerk_format_price( $price ) ); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Description -->
                    <div class="mj-single-food-description">
                        <?php the_content(); ?>
                    </div>

                    <!-- Product Info Cards -->
                    <div class="mj-product-info-cards">
                        <?php if ( $prep_time ) : ?>
                            <div class="mj-info-card">
                                <span class="mj-info-icon"><span class="dashicons dashicons-clock"></span></span>
                                <div class="mj-info-content">
                                    <span class="mj-info-label"><?php esc_html_e( 'Prep Time', 'mitzies-jerk' ); ?></span>
                                    <span class="mj-info-value"><?php echo esc_html( $prep_time ); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ( $calories ) : ?>
                            <div class="mj-info-card">
                                <span class="mj-info-icon"><span class="dashicons dashicons-heart"></span></span>
                                <div class="mj-info-content">
                                    <span class="mj-info-label"><?php esc_html_e( 'Calories', 'mitzies-jerk' ); ?></span>
                                    <span class="mj-info-value"><?php echo esc_html( $calories ); ?> kcal</span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ( $serving_size ) : ?>
                            <div class="mj-info-card">
                                <span class="mj-info-icon"><span class="dashicons dashicons-groups"></span></span>
                                <div class="mj-info-content">
                                    <span class="mj-info-label"><?php esc_html_e( 'Serves', 'mitzies-jerk' ); ?></span>
                                    <span class="mj-info-value"><?php echo esc_html( $serving_size ); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="mj-info-card mj-stock-card <?php echo $in_stock ? 'in-stock' : 'out-of-stock'; ?>">
                            <span class="mj-info-icon"><span class="dashicons dashicons-<?php echo $in_stock ? 'yes-alt' : 'dismiss'; ?>"></span></span>
                            <div class="mj-info-content">
                                <span class="mj-info-label"><?php esc_html_e( 'Availability', 'mitzies-jerk' ); ?></span>
                                <span class="mj-info-value"><?php echo $in_stock ? esc_html__( 'In Stock', 'mitzies-jerk' ) : esc_html__( 'Out of Stock', 'mitzies-jerk' ); ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if ( $ingredients ) : ?>
                        <div class="mj-product-section mj-ingredients-section">
                            <h4 class="mj-section-title">
                                <span class="dashicons dashicons-list-view"></span>
                                <?php esc_html_e( 'Ingredients', 'mitzies-jerk' ); ?>
                            </h4>
                            <p class="mj-ingredients-text"><?php echo esc_html( $ingredients ); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ( $allergens ) : ?>
                        <div class="mj-product-section mj-allergens-section">
                            <h4 class="mj-section-title">
                                <span class="dashicons dashicons-warning"></span>
                                <?php esc_html_e( 'Allergen Information', 'mitzies-jerk' ); ?>
                            </h4>
                            <p class="mj-allergens-text"><?php echo esc_html( $allergens ); ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Extras/Add-ons Section -->
                    <?php if ( ! empty( $addons ) || ! empty( $extras ) ) : ?>
                        <div class="mj-product-section mj-extras-section">
                            <h4 class="mj-section-title">
                                <span class="dashicons dashicons-plus-alt"></span>
                                <?php esc_html_e( 'Customize Your Order', 'mitzies-jerk' ); ?>
                            </h4>

                            <?php if ( ! empty( $addons ) ) : ?>
                                <div class="mj-addons-group">
                                    <h5 class="mj-group-title"><?php esc_html_e( 'Add-ons', 'mitzies-jerk' ); ?></h5>
                                    <div class="mj-addons-list">
                                        <?php foreach ( $addons as $index => $addon ) :
                                            // Handle both database objects and array format
                                            $addon_id = is_object( $addon ) ? $addon->id : $index;
                                            $addon_name = is_object( $addon ) ? $addon->addon_name : ( isset( $addon['name'] ) ? $addon['name'] : '' );
                                            $addon_price = is_object( $addon ) ? $addon->addon_price : ( isset( $addon['price'] ) ? $addon['price'] : 0 );
                                            $addon_desc = is_object( $addon ) && isset( $addon->description ) ? $addon->description : '';

                                            if ( empty( $addon_name ) ) continue;
                                        ?>
                                            <label class="mj-addon-item">
                                                <div class="mj-addon-checkbox">
                                                    <input type="checkbox"
                                                           name="addons[<?php echo esc_attr( $addon_id ); ?>]"
                                                           value="1"
                                                           class="mj-item-option mj-addon-input"
                                                           data-addon-id="<?php echo esc_attr( $addon_id ); ?>"
                                                           data-price="<?php echo esc_attr( $addon_price ); ?>">
                                                    <span class="mj-checkbox-custom"></span>
                                                </div>
                                                <div class="mj-addon-info">
                                                    <span class="mj-addon-name"><?php echo esc_html( $addon_name ); ?></span>
                                                    <?php if ( $addon_desc ) : ?>
                                                        <span class="mj-addon-desc"><?php echo esc_html( $addon_desc ); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="mj-addon-price">+<?php echo esc_html( mitzies_jerk_format_price( $addon_price ) ); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ( ! empty( $extras ) ) : ?>
                                <?php foreach ( $extras as $group_index => $extra_group ) :
                                    if ( empty( $extra_group['items'] ) ) continue;
                                    $group_name = isset( $extra_group['name'] ) ? $extra_group['name'] : __( 'Extras', 'mitzies-jerk' );
                                    $is_required = isset( $extra_group['required'] ) && $extra_group['required'];
                                    $max_select = isset( $extra_group['max'] ) ? intval( $extra_group['max'] ) : 0;
                                ?>
                                    <div class="mj-extras-group" data-group="<?php echo esc_attr( $group_index ); ?>" data-max="<?php echo esc_attr( $max_select ); ?>">
                                        <h5 class="mj-group-title">
                                            <?php echo esc_html( $group_name ); ?>
                                            <?php if ( $is_required ) : ?>
                                                <span class="mj-required-badge"><?php esc_html_e( 'Required', 'mitzies-jerk' ); ?></span>
                                            <?php endif; ?>
                                            <?php if ( $max_select > 0 ) : ?>
                                                <span class="mj-max-select"><?php printf( esc_html__( 'Select up to %d', 'mitzies-jerk' ), $max_select ); ?></span>
                                            <?php endif; ?>
                                        </h5>
                                        <div class="mj-extras-list">
                                            <?php foreach ( $extra_group['items'] as $item_index => $item ) :
                                                $item_name = isset( $item['name'] ) ? $item['name'] : '';
                                                $item_price = isset( $item['price'] ) ? floatval( $item['price'] ) : 0;
                                                if ( empty( $item_name ) ) continue;
                                            ?>
                                                <label class="mj-extra-item">
                                                    <div class="mj-extra-checkbox">
                                                        <input type="<?php echo $max_select === 1 ? 'radio' : 'checkbox'; ?>"
                                                               name="extras[<?php echo esc_attr( $group_index ); ?>]<?php echo $max_select !== 1 ? '[]' : ''; ?>"
                                                               value="<?php echo esc_attr( $item_index ); ?>"
                                                               class="mj-item-option mj-extra-input"
                                                               data-price="<?php echo esc_attr( $item_price ); ?>"
                                                               <?php echo $is_required && $max_select === 1 ? 'required' : ''; ?>>
                                                        <span class="mj-checkbox-custom"></span>
                                                    </div>
                                                    <span class="mj-extra-name"><?php echo esc_html( $item_name ); ?></span>
                                                    <?php if ( $item_price > 0 ) : ?>
                                                        <span class="mj-extra-price">+<?php echo esc_html( mitzies_jerk_format_price( $item_price ) ); ?></span>
                                                    <?php else : ?>
                                                        <span class="mj-extra-price mj-free"><?php esc_html_e( 'Free', 'mitzies-jerk' ); ?></span>
                                                    <?php endif; ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Special Instructions -->
                    <div class="mj-product-section mj-instructions-section">
                        <h4 class="mj-section-title">
                            <span class="dashicons dashicons-edit"></span>
                            <?php esc_html_e( 'Special Instructions', 'mitzies-jerk' ); ?>
                        </h4>
                        <textarea name="special_instructions"
                                  class="mj-special-instructions mj-item-option"
                                  placeholder="<?php esc_attr_e( 'Add any special requests (allergies, preferences, etc.)', 'mitzies-jerk' ); ?>"
                                  rows="3"></textarea>
                    </div>

                    <!-- Add to Cart Section -->
                    <?php if ( $in_stock ) : ?>
                        <div class="mj-add-to-cart-section">
                            <div class="mj-quantity-wrapper">
                                <span class="mj-quantity-label"><?php esc_html_e( 'Quantity', 'mitzies-jerk' ); ?></span>
                                <div class="mj-quantity-controls">
                                    <button type="button" class="mj-qty-btn mj-quantity-minus" aria-label="<?php esc_attr_e( 'Decrease quantity', 'mitzies-jerk' ); ?>">
                                        <span class="dashicons dashicons-minus"></span>
                                    </button>
                                    <input type="number"
                                           class="mj-quantity-input"
                                           value="1"
                                           min="1"
                                           max="<?php echo $stock_qty ? esc_attr( $stock_qty ) : '99'; ?>"
                                           aria-label="<?php esc_attr_e( 'Quantity', 'mitzies-jerk' ); ?>">
                                    <button type="button" class="mj-qty-btn mj-quantity-plus" aria-label="<?php esc_attr_e( 'Increase quantity', 'mitzies-jerk' ); ?>">
                                        <span class="dashicons dashicons-plus"></span>
                                    </button>
                                </div>
                            </div>

                            <div class="mj-total-price">
                                <span class="mj-total-label"><?php esc_html_e( 'Total:', 'mitzies-jerk' ); ?></span>
                                <span class="mj-total-value" data-base-price="<?php echo esc_attr( $current_price ); ?>">
                                    <?php echo esc_html( mitzies_jerk_format_price( $current_price ) ); ?>
                                </span>
                            </div>

                            <button class="mj-add-to-cart-btn mj-add-to-cart-single" data-item-id="<?php echo esc_attr( $post_id ); ?>">
                                <span class="mj-btn-icon"><span class="dashicons dashicons-cart"></span></span>
                                <span class="mj-btn-text"><?php esc_html_e( 'Add to Cart', 'mitzies-jerk' ); ?></span>
                            </button>
                        </div>

                        <?php if ( $cart_page_id ) : ?>
                            <div class="mj-cart-link-wrapper">
                                <a href="<?php echo esc_url( get_permalink( $cart_page_id ) ); ?>" class="mj-view-cart-link">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <?php esc_html_e( 'View Cart', 'mitzies-jerk' ); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php else : ?>
                        <div class="mj-out-of-stock-notice">
                            <span class="dashicons dashicons-warning"></span>
                            <p><?php esc_html_e( 'This item is currently out of stock. Please check back later or browse our other delicious options.', 'mitzies-jerk' ); ?></p>
                            <?php if ( $menu_page_id ) : ?>
                                <a href="<?php echo esc_url( get_permalink( $menu_page_id ) ); ?>" class="mj-btn mj-btn-primary">
                                    <?php esc_html_e( 'Browse Menu', 'mitzies-jerk' ); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( $tags && ! is_wp_error( $tags ) ) : ?>
                        <div class="mj-product-tags">
                            <span class="mj-tags-label"><?php esc_html_e( 'Tags:', 'mitzies-jerk' ); ?></span>
                            <div class="mj-tags-list">
                                <?php foreach ( $tags as $tag ) : ?>
                                    <a href="<?php echo esc_url( get_term_link( $tag ) ); ?>" class="mj-tag">
                                        #<?php echo esc_html( $tag->name ); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php
            // Related items.
            $related_args = array(
                'post_type'      => 'mj_food_item',
                'post_status'    => 'publish',
                'posts_per_page' => 4,
                'post__not_in'   => array( $post_id ),
                'orderby'        => 'rand',
            );

            if ( $categories && ! is_wp_error( $categories ) ) {
                $category_ids = wp_list_pluck( $categories, 'term_id' );
                $related_args['tax_query'] = array(
                    array(
                        'taxonomy' => 'mj_food_category',
                        'terms'    => $category_ids,
                    ),
                );
            }

            $related_query = new WP_Query( $related_args );

            if ( $related_query->have_posts() ) :
                ?>
                <div class="mj-related-items">
                    <h3 class="mj-section-heading"><?php esc_html_e( 'You May Also Like', 'mitzies-jerk' ); ?></h3>
                    <div class="mj-food-grid mj-related-grid">
                        <?php
                        while ( $related_query->have_posts() ) :
                            $related_query->the_post();
                            $related_id         = get_the_ID();
                            $related_price      = get_post_meta( $related_id, '_mj_price', true );
                            $related_sale_price = get_post_meta( $related_id, '_mj_sale_price', true );
                            $related_stock      = get_post_meta( $related_id, '_mj_stock_status', true );
                            $related_rating     = Mitzies_Jerk_Database::get_average_rating( $related_id );
                            ?>
                            <div class="mj-food-item<?php echo 'outofstock' === $related_stock ? ' out-of-stock' : ''; ?>">
                                <div class="mj-food-image">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php if ( has_post_thumbnail() ) : ?>
                                            <?php the_post_thumbnail( 'medium' ); ?>
                                        <?php else : ?>
                                            <div class="mj-no-image"><span class="dashicons dashicons-food"></span></div>
                                        <?php endif; ?>
                                    </a>
                                    <?php if ( $related_sale_price ) : ?>
                                        <span class="mj-sale-badge"><?php esc_html_e( 'Sale', 'mitzies-jerk' ); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="mj-food-content">
                                    <h3 class="mj-food-title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h3>
                                    <?php if ( $related_rating > 0 ) : ?>
                                        <div class="mj-food-rating">
                                            <span class="dashicons dashicons-star-filled"></span>
                                            <span><?php echo esc_html( $related_rating ); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="mj-food-price">
                                        <?php if ( $related_sale_price ) : ?>
                                            <del><?php echo esc_html( mitzies_jerk_format_price( $related_price ) ); ?></del>
                                            <ins><?php echo esc_html( mitzies_jerk_format_price( $related_sale_price ) ); ?></ins>
                                        <?php else : ?>
                                            <?php echo esc_html( mitzies_jerk_format_price( $related_price ) ); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mj-food-actions">
                                        <?php if ( 'outofstock' !== $related_stock ) : ?>
                                            <button class="mj-add-to-cart-btn" data-item-id="<?php echo esc_attr( $related_id ); ?>">
                                                <span class="dashicons dashicons-cart"></span>
                                                <?php esc_html_e( 'Add', 'mitzies-jerk' ); ?>
                                            </button>
                                        <?php endif; ?>
                                        <a href="<?php the_permalink(); ?>" class="mj-view-btn">
                                            <?php esc_html_e( 'View', 'mitzies-jerk' ); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php
                        endwhile;
                        wp_reset_postdata();
                        ?>
                    </div>
                </div>
                <?php
            endif;
            ?>
        </div>
    </div>

    <?php
endwhile;

get_footer();
