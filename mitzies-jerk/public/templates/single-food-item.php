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
    $addons       = get_post_meta( $post_id, '_mj_addons', true );
    $is_featured  = get_post_meta( $post_id, '_mj_is_featured', true );

    $categories   = get_the_terms( $post_id, 'mj_food_category' );
    $tags         = get_the_terms( $post_id, 'mj_food_tag' );

    $current_price = $sale_price ? $sale_price : $price;
    $in_stock      = 'outofstock' !== $stock_status;

    $cart_page_id = get_option( 'mitzies_jerk_cart_page_id' );
    $menu_page_id = get_option( 'mitzies_jerk_menu_page_id' );
    ?>

    <div class="mj-single-food-item-wrapper">
        <div class="mj-single-food-item">
            <div class="mj-single-food-gallery">
                <?php if ( has_post_thumbnail() ) : ?>
                    <div class="mj-single-food-image">
                        <?php the_post_thumbnail( 'large' ); ?>
                    </div>
                <?php else : ?>
                    <div class="mj-single-food-image mj-no-image">
                        <span class="dashicons dashicons-food"></span>
                    </div>
                <?php endif; ?>

                <?php if ( $sale_price ) : ?>
                    <span class="mj-sale-badge"><?php esc_html_e( 'Sale!', 'mitzies-jerk' ); ?></span>
                <?php endif; ?>

                <?php if ( $is_featured ) : ?>
                    <span class="mj-featured-badge"><?php esc_html_e( 'Featured', 'mitzies-jerk' ); ?></span>
                <?php endif; ?>
            </div>

            <div class="mj-single-food-details">
                <nav class="mj-breadcrumb">
                    <a href="<?php echo esc_url( home_url() ); ?>"><?php esc_html_e( 'Home', 'mitzies-jerk' ); ?></a>
                    <span class="mj-breadcrumb-sep">/</span>
                    <?php if ( $menu_page_id ) : ?>
                        <a href="<?php echo esc_url( get_permalink( $menu_page_id ) ); ?>"><?php esc_html_e( 'Menu', 'mitzies-jerk' ); ?></a>
                        <span class="mj-breadcrumb-sep">/</span>
                    <?php endif; ?>
                    <?php if ( $categories && ! is_wp_error( $categories ) ) : ?>
                        <a href="<?php echo esc_url( get_term_link( $categories[0] ) ); ?>"><?php echo esc_html( $categories[0]->name ); ?></a>
                        <span class="mj-breadcrumb-sep">/</span>
                    <?php endif; ?>
                    <span class="mj-breadcrumb-current"><?php the_title(); ?></span>
                </nav>

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

                <div class="mj-single-food-price">
                    <?php if ( $sale_price ) : ?>
                        <del class="mj-original-price"><?php echo esc_html( mitzies_jerk_format_price( $price ) ); ?></del>
                        <ins class="mj-sale-price"><?php echo esc_html( mitzies_jerk_format_price( $sale_price ) ); ?></ins>
                    <?php else : ?>
                        <span class="mj-regular-price"><?php echo esc_html( mitzies_jerk_format_price( $price ) ); ?></span>
                    <?php endif; ?>
                </div>

                <div class="mj-single-food-description">
                    <?php the_content(); ?>
                </div>

                <?php if ( $ingredients ) : ?>
                    <div class="mj-single-food-ingredients">
                        <h4><?php esc_html_e( 'Ingredients', 'mitzies-jerk' ); ?></h4>
                        <p><?php echo esc_html( $ingredients ); ?></p>
                    </div>
                <?php endif; ?>

                <div class="mj-single-food-meta">
                    <?php if ( $prep_time ) : ?>
                        <div class="mj-meta-item">
                            <span class="dashicons dashicons-clock"></span>
                            <span class="mj-meta-label"><?php esc_html_e( 'Prep Time:', 'mitzies-jerk' ); ?></span>
                            <span class="mj-meta-value"><?php echo esc_html( $prep_time ); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ( $calories ) : ?>
                        <div class="mj-meta-item">
                            <span class="dashicons dashicons-heart"></span>
                            <span class="mj-meta-label"><?php esc_html_e( 'Calories:', 'mitzies-jerk' ); ?></span>
                            <span class="mj-meta-value"><?php echo esc_html( $calories ); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="mj-meta-item mj-stock-status <?php echo $in_stock ? 'in-stock' : 'out-of-stock'; ?>">
                        <span class="dashicons dashicons-<?php echo $in_stock ? 'yes' : 'no'; ?>"></span>
                        <span class="mj-meta-value">
                            <?php echo $in_stock ? esc_html__( 'In Stock', 'mitzies-jerk' ) : esc_html__( 'Out of Stock', 'mitzies-jerk' ); ?>
                        </span>
                    </div>
                </div>

                <?php if ( ! empty( $addons ) && is_array( $addons ) ) : ?>
                    <div class="mj-single-food-addons">
                        <h4><?php esc_html_e( 'Add-ons', 'mitzies-jerk' ); ?></h4>
                        <div class="mj-addons-list">
                            <?php foreach ( $addons as $index => $addon ) : ?>
                                <label class="mj-addon-item">
                                    <input type="checkbox" name="addons[]" value="<?php echo esc_attr( $index ); ?>" class="mj-item-option" data-price="<?php echo esc_attr( $addon['price'] ); ?>">
                                    <span class="mj-addon-name"><?php echo esc_html( $addon['name'] ); ?></span>
                                    <span class="mj-addon-price">+<?php echo esc_html( mitzies_jerk_format_price( $addon['price'] ) ); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ( $in_stock ) : ?>
                    <div class="mj-single-food-actions">
                        <div class="mj-quantity-wrapper">
                            <label for="mj-quantity"><?php esc_html_e( 'Quantity:', 'mitzies-jerk' ); ?></label>
                            <div class="mj-quantity-controls">
                                <button type="button" class="mj-quantity-btn mj-quantity-minus">-</button>
                                <input type="number" id="mj-quantity" class="mj-quantity-input" value="1" min="1" max="<?php echo $stock_qty ? esc_attr( $stock_qty ) : '99'; ?>">
                                <button type="button" class="mj-quantity-btn mj-quantity-plus">+</button>
                            </div>
                        </div>

                        <button class="mj-add-to-cart-btn mj-add-to-cart-single" data-item-id="<?php echo esc_attr( $post_id ); ?>">
                            <span class="dashicons dashicons-cart"></span>
                            <?php esc_html_e( 'Add to Cart', 'mitzies-jerk' ); ?>
                        </button>
                    </div>

                    <?php if ( $cart_page_id ) : ?>
                        <div class="mj-single-food-cart-link">
                            <a href="<?php echo esc_url( get_permalink( $cart_page_id ) ); ?>" class="mj-view-cart-link">
                                <?php esc_html_e( 'View Cart', 'mitzies-jerk' ); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <div class="mj-out-of-stock-notice">
                        <p><?php esc_html_e( 'This item is currently out of stock. Please check back later.', 'mitzies-jerk' ); ?></p>
                    </div>
                <?php endif; ?>

                <?php if ( $tags && ! is_wp_error( $tags ) ) : ?>
                    <div class="mj-single-food-tags">
                        <span class="mj-tags-label"><?php esc_html_e( 'Tags:', 'mitzies-jerk' ); ?></span>
                        <?php foreach ( $tags as $tag ) : ?>
                            <a href="<?php echo esc_url( get_term_link( $tag ) ); ?>" class="mj-food-tag">
                                <?php echo esc_html( $tag->name ); ?>
                            </a>
                        <?php endforeach; ?>
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
                <h3 class="mj-related-title"><?php esc_html_e( 'You May Also Like', 'mitzies-jerk' ); ?></h3>
                <div class="mj-food-grid columns-4">
                    <?php
                    while ( $related_query->have_posts() ) :
                        $related_query->the_post();
                        $related_id         = get_the_ID();
                        $related_price      = get_post_meta( $related_id, '_mj_price', true );
                        $related_sale_price = get_post_meta( $related_id, '_mj_sale_price', true );
                        $related_stock      = get_post_meta( $related_id, '_mj_stock_status', true );
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
                            </div>
                            <div class="mj-food-content">
                                <h3 class="mj-food-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>
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
                                            <?php esc_html_e( 'Add to Cart', 'mitzies-jerk' ); ?>
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

    <?php
endwhile;

get_footer();
