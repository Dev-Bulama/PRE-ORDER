<?php
/**
 * Cron jobs handler class.
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
 * Cron jobs handler class.
 */
class Mitzies_Jerk_Cron {

    /**
     * Check for expired orders.
     */
    public function check_expired_orders() {
        global $wpdb;

        // Get pending orders with expired payment windows.
        $expired_orders = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.ID FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = 'mj_order'
                AND p.post_status = 'mj-pending'
                AND pm.meta_key = '_mj_payment_expiry'
                AND pm.meta_value < %s",
                current_time( 'mysql' )
            )
        );

        if ( empty( $expired_orders ) ) {
            return;
        }

        $order_handler = new Mitzies_Jerk_Order();

        foreach ( $expired_orders as $expired ) {
            $order_handler->update_status(
                $expired->ID,
                'expired',
                __( 'Payment window expired. Order automatically cancelled.', 'mitzies-jerk' )
            );

            // Restore stock.
            $items = Mitzies_Jerk_Database::get_order_items( $expired->ID );

            foreach ( $items as $item ) {
                $current_stock = (int) get_post_meta( $item->food_item_id, '_mj_stock_quantity', true );
                update_post_meta( $item->food_item_id, '_mj_stock_quantity', $current_stock + $item->quantity );

                $stock_status = get_post_meta( $item->food_item_id, '_mj_stock_status', true );
                if ( 'outofstock' === $stock_status ) {
                    update_post_meta( $item->food_item_id, '_mj_stock_status', 'instock' );
                }
            }

            mitzies_jerk_log(
                sprintf( 'Order #%d expired due to payment timeout.', $expired->ID ),
                'info',
                array( 'order_id' => $expired->ID )
            );
        }
    }

    /**
     * Cleanup old sessions.
     */
    public function cleanup_sessions() {
        global $wpdb;

        $table_name = Mitzies_Jerk_Database::get_table( 'sessions' );

        // Delete sessions older than 48 hours.
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM `$table_name` WHERE session_expiry < %d",
                time() - ( 48 * 60 * 60 )
            )
        );

        // Cleanup old logs if logging is disabled or too many.
        $logs = get_option( 'mitzies_jerk_logs', array() );
        $max_logs = 500;

        if ( count( $logs ) > $max_logs ) {
            $logs = array_slice( $logs, -$max_logs );
            update_option( 'mitzies_jerk_logs', $logs );
        }

        // Cleanup old transients.
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '%_transient_mj_%'
            AND option_name LIKE '%_transient_timeout_mj_%'"
        );
    }

    /**
     * Send reminder emails.
     */
    public function send_reminder_emails() {
        // Find orders that are due for delivery in the next 2 hours.
        global $wpdb;

        $now = current_time( 'mysql' );
        $two_hours_later = date( 'Y-m-d H:i:s', strtotime( '+2 hours' ) );

        $upcoming_orders = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.ID FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = 'mj_order'
                AND p.post_status IN ('mj-paid', 'mj-processing', 'mj-preparing')
                AND pm.meta_key = '_mj_delivery_datetime'
                AND pm.meta_value BETWEEN %s AND %s",
                $now,
                $two_hours_later
            )
        );

        if ( empty( $upcoming_orders ) ) {
            return;
        }

        $emails = new Mitzies_Jerk_Emails();

        foreach ( $upcoming_orders as $upcoming ) {
            // Check if reminder already sent.
            $reminder_sent = get_post_meta( $upcoming->ID, '_mj_delivery_reminder_sent', true );

            if ( $reminder_sent ) {
                continue;
            }

            $order = new Mitzies_Jerk_Order( $upcoming->ID );
            $billing = $order->get( 'billing' );

            if ( empty( $billing['email'] ) ) {
                continue;
            }

            // Send reminder email.
            $subject = sprintf(
                /* translators: 1: Site name, 2: Order number */
                __( '[%1$s] Your order %2$s is coming soon!', 'mitzies-jerk' ),
                get_bloginfo( 'name' ),
                $order->get( 'order_number' )
            );

            $content = '<h2>' . esc_html__( 'Your order is on its way!', 'mitzies-jerk' ) . '</h2>';
            $content .= '<p>' . esc_html__( 'Hello', 'mitzies-jerk' ) . ' ' . esc_html( $billing['first_name'] ) . ',</p>';
            $content .= '<p>' . sprintf(
                /* translators: 1: Order number, 2: Delivery time */
                esc_html__( 'Your order #%1$s is scheduled for delivery at %2$s. Please make sure someone is available to receive it.', 'mitzies-jerk' ),
                esc_html( $order->get( 'order_number' ) ),
                esc_html( date_i18n( get_option( 'time_format' ), strtotime( $order->get( 'delivery_datetime' ) ) ) )
            ) . '</p>';

            $tracking_url = add_query_arg( 'order', $order->get( 'order_number' ), get_permalink( get_option( 'mitzies_jerk_order_tracking_page_id' ) ) );
            $content .= '<p><a href="' . esc_url( $tracking_url ) . '" class="button">' . esc_html__( 'Track Your Order', 'mitzies-jerk' ) . '</a></p>';

            $emails->send( $billing['email'], $subject, $content );

            // Mark as sent.
            update_post_meta( $upcoming->ID, '_mj_delivery_reminder_sent', 1 );

            mitzies_jerk_log(
                sprintf( 'Delivery reminder sent for order #%d.', $upcoming->ID ),
                'info',
                array( 'order_id' => $upcoming->ID )
            );
        }
    }
}
