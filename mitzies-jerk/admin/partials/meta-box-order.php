<?php
/**
 * Order meta box template.
 *
 * @package    Mitzies_Jerk
 * @subpackage Mitzies_Jerk/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$billing = $order->get( 'billing' );
$delivery = $order->get( 'delivery' );
$statuses = mitzies_jerk_get_order_statuses();
$current_status = $order->get( 'status' );
?>

<div class="mj-order-data">
    <div class="mj-order-grid">
        <div class="mj-order-section">
            <h4><?php esc_html_e( 'Order Information', 'mitzies-jerk' ); ?></h4>
            <table class="mj-order-table">
                <tr>
                    <th><?php esc_html_e( 'Order Number', 'mitzies-jerk' ); ?></th>
                    <td>#<?php echo esc_html( $order->get( 'order_number' ) ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Status', 'mitzies-jerk' ); ?></th>
                    <td>
                        <span class="mj-status-badge status-<?php echo esc_attr( $current_status ); ?>">
                            <?php echo esc_html( isset( $statuses[ $current_status ] ) ? $statuses[ $current_status ] : $current_status ); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Date Created', 'mitzies-jerk' ); ?></th>
                    <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $order->get( 'created_at' ) ) ) ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Payment Method', 'mitzies-jerk' ); ?></th>
                    <td><?php echo esc_html( ucfirst( $order->get( 'payment_method' ) ) ); ?></td>
                </tr>
                <?php if ( $order->get( 'transaction_id' ) ) : ?>
                <tr>
                    <th><?php esc_html_e( 'Transaction ID', 'mitzies-jerk' ); ?></th>
                    <td><code><?php echo esc_html( $order->get( 'transaction_id' ) ); ?></code></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>

        <div class="mj-order-section">
            <h4><?php esc_html_e( 'Customer Details', 'mitzies-jerk' ); ?></h4>
            <table class="mj-order-table">
                <tr>
                    <th><?php esc_html_e( 'Name', 'mitzies-jerk' ); ?></th>
                    <td><?php echo esc_html( $billing['first_name'] . ' ' . $billing['last_name'] ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Email', 'mitzies-jerk' ); ?></th>
                    <td><a href="mailto:<?php echo esc_attr( $billing['email'] ); ?>"><?php echo esc_html( $billing['email'] ); ?></a></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Phone', 'mitzies-jerk' ); ?></th>
                    <td><a href="tel:<?php echo esc_attr( $billing['phone'] ); ?>"><?php echo esc_html( $billing['phone'] ); ?></a></td>
                </tr>
            </table>
        </div>

        <div class="mj-order-section">
            <h4><?php esc_html_e( 'Delivery Details', 'mitzies-jerk' ); ?></h4>
            <table class="mj-order-table">
                <tr>
                    <th><?php esc_html_e( 'Address', 'mitzies-jerk' ); ?></th>
                    <td>
                        <?php echo esc_html( $delivery['address_1'] ); ?>
                        <?php if ( ! empty( $delivery['address_2'] ) ) : ?>
                            <br><?php echo esc_html( $delivery['address_2'] ); ?>
                        <?php endif; ?>
                        <br><?php echo esc_html( $delivery['city'] ); ?>
                        <?php if ( ! empty( $delivery['state'] ) ) : ?>
                            , <?php echo esc_html( $delivery['state'] ); ?>
                        <?php endif; ?>
                        <?php if ( ! empty( $delivery['postcode'] ) ) : ?>
                            <?php echo esc_html( $delivery['postcode'] ); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Delivery Date/Time', 'mitzies-jerk' ); ?></th>
                    <td>
                        <strong><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $order->get( 'delivery_datetime' ) ) ) ); ?></strong>
                    </td>
                </tr>
                <?php if ( ! empty( $delivery['instructions'] ) ) : ?>
                <tr>
                    <th><?php esc_html_e( 'Instructions', 'mitzies-jerk' ); ?></th>
                    <td><?php echo esc_html( $delivery['instructions'] ); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>

        <div class="mj-order-section">
            <h4><?php esc_html_e( 'Order Totals', 'mitzies-jerk' ); ?></h4>
            <table class="mj-order-table mj-order-totals">
                <tr>
                    <th><?php esc_html_e( 'Subtotal', 'mitzies-jerk' ); ?></th>
                    <td><?php echo esc_html( mitzies_jerk_format_price( $order->get( 'subtotal' ) ) ); ?></td>
                </tr>
                <?php if ( $order->get( 'addon_total' ) > 0 ) : ?>
                <tr>
                    <th><?php esc_html_e( 'Add-ons', 'mitzies-jerk' ); ?></th>
                    <td><?php echo esc_html( mitzies_jerk_format_price( $order->get( 'addon_total' ) ) ); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ( $order->get( 'discount' ) > 0 ) : ?>
                <tr>
                    <th><?php esc_html_e( 'Discount', 'mitzies-jerk' ); ?></th>
                    <td>-<?php echo esc_html( mitzies_jerk_format_price( $order->get( 'discount' ) ) ); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th><?php esc_html_e( 'Delivery Fee', 'mitzies-jerk' ); ?></th>
                    <td><?php echo esc_html( mitzies_jerk_format_price( $order->get( 'delivery_fee' ) ) ); ?></td>
                </tr>
                <?php if ( $order->get( 'tax' ) > 0 ) : ?>
                <tr>
                    <th><?php esc_html_e( 'Tax', 'mitzies-jerk' ); ?></th>
                    <td><?php echo esc_html( mitzies_jerk_format_price( $order->get( 'tax' ) ) ); ?></td>
                </tr>
                <?php endif; ?>
                <tr class="total">
                    <th><?php esc_html_e( 'Total', 'mitzies-jerk' ); ?></th>
                    <td><strong><?php echo esc_html( mitzies_jerk_format_price( $order->get( 'total' ) ) ); ?></strong></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<style>
.mj-order-data { margin: -6px -12px -12px; }
.mj-order-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; padding: 15px; }
.mj-order-section { background: #f9f9f9; padding: 15px; border-radius: 4px; }
.mj-order-section h4 { margin: 0 0 10px; padding-bottom: 10px; border-bottom: 1px solid #ddd; }
.mj-order-table { width: 100%; border-collapse: collapse; }
.mj-order-table th, .mj-order-table td { padding: 5px 0; text-align: left; vertical-align: top; }
.mj-order-table th { width: 40%; color: #666; font-weight: 400; }
.mj-order-totals tr.total th, .mj-order-totals tr.total td { border-top: 1px solid #ddd; padding-top: 10px; margin-top: 5px; }
.mj-status-badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 12px; font-weight: 500; }
.mj-status-badge.status-pending { background: #fff3cd; color: #856404; }
.mj-status-badge.status-paid, .mj-status-badge.status-completed { background: #d4edda; color: #155724; }
.mj-status-badge.status-processing, .mj-status-badge.status-preparing { background: #cce5ff; color: #004085; }
.mj-status-badge.status-cancelled, .mj-status-badge.status-failed { background: #f8d7da; color: #721c24; }

@media (max-width: 782px) {
    .mj-order-grid { grid-template-columns: 1fr; }
}
</style>
