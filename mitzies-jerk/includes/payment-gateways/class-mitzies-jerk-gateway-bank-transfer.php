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

    /**
     * Get settings fields for admin configuration.
     *
     * @return array
     */
    public function get_settings_fields() {
        return array(
            'account_name' => array(
                'title'       => __( 'Account Name', 'mitzies-jerk' ),
                'type'        => 'text',
                'description' => __( 'The name on the bank account.', 'mitzies-jerk' ),
                'default'     => '',
            ),
            'account_number' => array(
                'title'       => __( 'Account Number', 'mitzies-jerk' ),
                'type'        => 'text',
                'description' => __( 'Your bank account number.', 'mitzies-jerk' ),
                'default'     => '',
            ),
            'bank_name' => array(
                'title'       => __( 'Bank Name', 'mitzies-jerk' ),
                'type'        => 'text',
                'description' => __( 'The name of your bank.', 'mitzies-jerk' ),
                'default'     => '',
            ),
            'sort_code' => array(
                'title'       => __( 'Sort Code / Routing Number', 'mitzies-jerk' ),
                'type'        => 'text',
                'description' => __( 'Bank sort code or routing number (if applicable).', 'mitzies-jerk' ),
                'default'     => '',
            ),
            'iban' => array(
                'title'       => __( 'IBAN', 'mitzies-jerk' ),
                'type'        => 'text',
                'description' => __( 'International Bank Account Number (if applicable).', 'mitzies-jerk' ),
                'default'     => '',
            ),
            'swift_code' => array(
                'title'       => __( 'SWIFT/BIC Code', 'mitzies-jerk' ),
                'type'        => 'text',
                'description' => __( 'SWIFT or BIC code for international transfers.', 'mitzies-jerk' ),
                'default'     => '',
            ),
            'instructions' => array(
                'title'       => __( 'Payment Instructions', 'mitzies-jerk' ),
                'type'        => 'textarea',
                'description' => __( 'Instructions that will be shown to the customer after checkout.', 'mitzies-jerk' ),
                'default'     => __( 'Please make your payment directly into our bank account. Use your Order Number as the payment reference. Your order will be processed once the funds have cleared.', 'mitzies-jerk' ),
            ),
        );
    }

    /**
     * Get the bank details for display.
     *
     * @return array
     */
    public function get_bank_details() {
        return array(
            'account_name'   => $this->get_option( 'account_name', '' ),
            'account_number' => $this->get_option( 'account_number', '' ),
            'bank_name'      => $this->get_option( 'bank_name', '' ),
            'sort_code'      => $this->get_option( 'sort_code', '' ),
            'iban'           => $this->get_option( 'iban', '' ),
            'swift_code'     => $this->get_option( 'swift_code', '' ),
        );
    }

    /**
     * Get the customer-facing instructions.
     *
     * @return string
     */
    public function get_instructions() {
        return $this->get_option( 'instructions', __( 'Please make your payment directly into our bank account.', 'mitzies-jerk' ) );
    }
}
