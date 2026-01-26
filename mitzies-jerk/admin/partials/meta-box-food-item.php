<?php
/**
 * Food item meta box template.
 *
 * @package    Mitzies_Jerk
 * @subpackage Mitzies_Jerk/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="mj-meta-box-tabs">
    <ul class="mj-tabs-nav">
        <li class="active"><a href="#mj-tab-pricing"><?php esc_html_e( 'Pricing', 'mitzies-jerk' ); ?></a></li>
        <li><a href="#mj-tab-inventory"><?php esc_html_e( 'Inventory', 'mitzies-jerk' ); ?></a></li>
        <li><a href="#mj-tab-details"><?php esc_html_e( 'Details', 'mitzies-jerk' ); ?></a></li>
        <li><a href="#mj-tab-addons"><?php esc_html_e( 'Add-ons', 'mitzies-jerk' ); ?></a></li>
    </ul>

    <div id="mj-tab-pricing" class="mj-tab-content active">
        <p>
            <label for="mj_price"><strong><?php esc_html_e( 'Regular Price', 'mitzies-jerk' ); ?></strong></label>
            <input type="number" name="mj_price" id="mj_price" value="<?php echo esc_attr( $price ); ?>" step="0.01" min="0" class="widefat">
        </p>
        <p>
            <label for="mj_sale_price"><strong><?php esc_html_e( 'Sale Price', 'mitzies-jerk' ); ?></strong></label>
            <input type="number" name="mj_sale_price" id="mj_sale_price" value="<?php echo esc_attr( $sale_price ); ?>" step="0.01" min="0" class="widefat">
            <span class="description"><?php esc_html_e( 'Leave empty for no sale.', 'mitzies-jerk' ); ?></span>
        </p>
        <p>
            <label>
                <input type="checkbox" name="mj_is_featured" value="1" <?php checked( $is_featured, 1 ); ?>>
                <strong><?php esc_html_e( 'Featured Item', 'mitzies-jerk' ); ?></strong>
            </label>
            <span class="description"><?php esc_html_e( 'Featured items are highlighted in the menu.', 'mitzies-jerk' ); ?></span>
        </p>
    </div>

    <div id="mj-tab-inventory" class="mj-tab-content">
        <p>
            <label for="mj_stock_status"><strong><?php esc_html_e( 'Stock Status', 'mitzies-jerk' ); ?></strong></label>
            <select name="mj_stock_status" id="mj_stock_status" class="widefat">
                <option value="instock" <?php selected( $stock_status, 'instock' ); ?>><?php esc_html_e( 'In Stock', 'mitzies-jerk' ); ?></option>
                <option value="outofstock" <?php selected( $stock_status, 'outofstock' ); ?>><?php esc_html_e( 'Out of Stock', 'mitzies-jerk' ); ?></option>
            </select>
        </p>
        <p>
            <label for="mj_stock_quantity"><strong><?php esc_html_e( 'Stock Quantity', 'mitzies-jerk' ); ?></strong></label>
            <input type="number" name="mj_stock_quantity" id="mj_stock_quantity" value="<?php echo esc_attr( $stock_quantity ); ?>" min="0" class="widefat">
            <span class="description"><?php esc_html_e( 'Leave empty for unlimited stock.', 'mitzies-jerk' ); ?></span>
        </p>
    </div>

    <div id="mj-tab-details" class="mj-tab-content">
        <p>
            <label for="mj_ingredients"><strong><?php esc_html_e( 'Ingredients', 'mitzies-jerk' ); ?></strong></label>
            <textarea name="mj_ingredients" id="mj_ingredients" rows="4" class="widefat"><?php echo esc_textarea( $ingredients ); ?></textarea>
            <span class="description"><?php esc_html_e( 'List of ingredients (one per line or comma-separated).', 'mitzies-jerk' ); ?></span>
        </p>
        <p>
            <label for="mj_preparation_time"><strong><?php esc_html_e( 'Preparation Time', 'mitzies-jerk' ); ?></strong></label>
            <input type="text" name="mj_preparation_time" id="mj_preparation_time" value="<?php echo esc_attr( $preparation_time ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'e.g., 30 minutes', 'mitzies-jerk' ); ?>">
        </p>
        <p>
            <label for="mj_calories"><strong><?php esc_html_e( 'Calories', 'mitzies-jerk' ); ?></strong></label>
            <input type="text" name="mj_calories" id="mj_calories" value="<?php echo esc_attr( $calories ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'e.g., 450 kcal', 'mitzies-jerk' ); ?>">
        </p>
    </div>

    <div id="mj-tab-addons" class="mj-tab-content">
        <p><?php esc_html_e( 'Add optional extras that customers can add to this item.', 'mitzies-jerk' ); ?></p>
        <div id="mj-addons-list">
            <?php
            $addons = Mitzies_Jerk_Database::get_food_addons( $post->ID );
            if ( ! empty( $addons ) ) :
                foreach ( $addons as $addon ) :
            ?>
                <div class="mj-addon-row">
                    <input type="text" name="mj_addons[<?php echo esc_attr( $addon->id ); ?>][name]" value="<?php echo esc_attr( $addon->addon_name ); ?>" placeholder="<?php esc_attr_e( 'Add-on name', 'mitzies-jerk' ); ?>">
                    <input type="number" name="mj_addons[<?php echo esc_attr( $addon->id ); ?>][price]" value="<?php echo esc_attr( $addon->addon_price ); ?>" step="0.01" min="0" placeholder="<?php esc_attr_e( 'Price', 'mitzies-jerk' ); ?>">
                    <button type="button" class="button mj-remove-addon">&times;</button>
                </div>
            <?php
                endforeach;
            endif;
            ?>
        </div>
        <p>
            <button type="button" class="button" id="mj-add-addon"><?php esc_html_e( 'Add Add-on', 'mitzies-jerk' ); ?></button>
        </p>
    </div>
</div>

<style>
.mj-meta-box-tabs { margin-top: 10px; }
.mj-tabs-nav { display: flex; gap: 0; margin: 0; padding: 0; list-style: none; border-bottom: 1px solid #ccd0d4; }
.mj-tabs-nav li { margin: 0; }
.mj-tabs-nav a { display: block; padding: 8px 16px; text-decoration: none; color: #50575e; background: #f6f7f7; border: 1px solid #ccd0d4; border-bottom: none; margin-right: -1px; }
.mj-tabs-nav li.active a { background: #fff; color: #1d2327; border-bottom-color: #fff; margin-bottom: -1px; }
.mj-tab-content { display: none; padding: 15px; background: #fff; border: 1px solid #ccd0d4; border-top: none; }
.mj-tab-content.active { display: block; }
.mj-addon-row { display: flex; gap: 10px; margin-bottom: 10px; align-items: center; }
.mj-addon-row input[type="text"] { flex: 2; }
.mj-addon-row input[type="number"] { flex: 1; }
</style>

<script>
jQuery(document).ready(function($) {
    // Tab navigation.
    $('.mj-tabs-nav a').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        $('.mj-tabs-nav li').removeClass('active');
        $(this).parent().addClass('active');
        $('.mj-tab-content').removeClass('active');
        $(target).addClass('active');
    });

    // Add addon.
    var addonIndex = <?php echo count( $addons ?? array() ); ?>;
    $('#mj-add-addon').on('click', function() {
        var html = '<div class="mj-addon-row">' +
            '<input type="text" name="mj_addons[new_' + addonIndex + '][name]" placeholder="<?php esc_attr_e( 'Add-on name', 'mitzies-jerk' ); ?>">' +
            '<input type="number" name="mj_addons[new_' + addonIndex + '][price]" step="0.01" min="0" placeholder="<?php esc_attr_e( 'Price', 'mitzies-jerk' ); ?>">' +
            '<button type="button" class="button mj-remove-addon">&times;</button>' +
            '</div>';
        $('#mj-addons-list').append(html);
        addonIndex++;
    });

    // Remove addon.
    $(document).on('click', '.mj-remove-addon', function() {
        $(this).closest('.mj-addon-row').remove();
    });
});
</script>
