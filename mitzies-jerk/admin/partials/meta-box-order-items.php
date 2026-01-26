<?php
/**
 * Order items meta box template.
 *
 * @package    Mitzies_Jerk
 * @subpackage Mitzies_Jerk/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<table class="widefat striped mj-order-items-table">
    <thead>
        <tr>
            <th><?php esc_html_e( 'Item', 'mitzies-jerk' ); ?></th>
            <th class="mj-col-qty"><?php esc_html_e( 'Qty', 'mitzies-jerk' ); ?></th>
            <th class="mj-col-price"><?php esc_html_e( 'Price', 'mitzies-jerk' ); ?></th>
            <th class="mj-col-total"><?php esc_html_e( 'Total', 'mitzies-jerk' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ( $items as $item ) :
            $food_item = get_post( $item->food_item_id );
            $addons = maybe_unserialize( $item->addons );
        ?>
        <tr>
            <td>
                <div class="mj-item-details">
                    <?php if ( $food_item ) : ?>
                        <?php echo get_the_post_thumbnail( $food_item->ID, array( 50, 50 ), array( 'class' => 'mj-item-thumb' ) ); ?>
                        <div class="mj-item-info">
                            <a href="<?php echo esc_url( get_edit_post_link( $food_item->ID ) ); ?>">
                                <strong><?php echo esc_html( $food_item->post_title ); ?></strong>
                            </a>
                            <?php if ( ! empty( $addons ) ) : ?>
                                <div class="mj-item-addons">
                                    <?php foreach ( $addons as $addon ) : ?>
                                        <span class="mj-addon-item">
                                            <?php echo esc_html( $addon['name'] ); ?>
                                            <?php if ( $addon['quantity'] > 1 ) : ?>
                                                &times;<?php echo esc_html( $addon['quantity'] ); ?>
                                            <?php endif; ?>
                                            (<?php echo esc_html( mitzies_jerk_format_price( $addon['price'] * $addon['quantity'] ) ); ?>)
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else : ?>
                        <em><?php esc_html_e( 'Item deleted', 'mitzies-jerk' ); ?></em>
                    <?php endif; ?>
                </div>
            </td>
            <td class="mj-col-qty"><?php echo esc_html( $item->quantity ); ?></td>
            <td class="mj-col-price"><?php echo esc_html( mitzies_jerk_format_price( $item->price ) ); ?></td>
            <td class="mj-col-total"><?php echo esc_html( mitzies_jerk_format_price( $item->subtotal ) ); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<style>
.mj-order-items-table .mj-col-qty,
.mj-order-items-table .mj-col-price,
.mj-order-items-table .mj-col-total { width: 80px; text-align: right; }
.mj-item-details { display: flex; align-items: flex-start; gap: 10px; }
.mj-item-thumb { border-radius: 4px; object-fit: cover; }
.mj-item-info { flex: 1; }
.mj-item-addons { margin-top: 5px; }
.mj-addon-item { display: inline-block; background: #f0f0f1; padding: 2px 6px; border-radius: 3px; font-size: 11px; margin-right: 5px; margin-bottom: 3px; }
</style>
