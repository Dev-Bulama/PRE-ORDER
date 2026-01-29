<?php
/**
 * Bank Transfer Payment Gateway
 *
 * @package    Mitzies_Jerk
 * @subpackage Mitzies_Jerk/includes/payment-gateways
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Bank Transfer Gateway Class
 */
class Mitzies_Jerk_Gateway_Bank_Transfer extends Mitzies_Jerk_Payment_Gateway {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id          = 'bank_transfer';
        $this->title       = __( 'Bank Transfer', 'mitzies-jerk' );
        $this->description = __( 'Pay via direct bank transfer', 'mitzies-jerk' );
        $this->icon        = '';
    }

    /**
     * Check if gateway is available.
     *
     * @return bool
     */
    public function is_available() {
        // Bank Transfer is always available by default.
        return true;
    }

    /**
     * Check if gateway is configured.
     *
     * @return bool
     */
    public function is_configured() {
        // Bank Transfer doesn't require configuration.
        return true;
    }

    /**
     * Process payment.
     *
     * @param int $order_id Order ID.
     * @return array
     */
    public function process_payment( $order_id ) {
        $order = new Mitzies_Jerk_Order( $order_id );

        // Update order status to pending payment.
        $order->update_status( $order_id, 'pending', __( 'Awaiting bank transfer payment.', 'mitzies-jerk' ) );

        // Send order confirmation email with bank details.
        $emails = new Mitzies_Jerk_Emails();
        $emails->send_order_confirmation( $order_id );
        $emails->send_admin_new_order( $order_id );

        // Get order received URL.
        $received_url = add_query_arg(
            'order_id',
            $order_id,
            get_permalink( get_option( 'mitzies_jerk_order_received_page_id' ) )
        );

        return array(
            'result'   => 'success',
            'redirect' => $received_url,
        );
    }

    /**
     * Handle callback (not used for Bank Transfer).
     *
     * @return array
     */
    public function handle_callback() {
        return array( 'result' => 'success' );
    }

    /**
     * Verify payment (not used for Bank Transfer).
     *
     * @param string $reference Payment reference.
     * @return array
     */
    public function verify_payment( $reference ) {
        return array( 'result' => 'success' );
    }
}
