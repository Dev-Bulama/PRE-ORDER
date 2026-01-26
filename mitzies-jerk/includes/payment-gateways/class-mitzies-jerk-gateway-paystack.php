<?php
/**
 * Paystack payment gateway.
 *
 * @package    Mitzies_Jerk
 * @subpackage Mitzies_Jerk/includes/payment-gateways
 * @author     SkillScore IT Solutions and Training, Tijani Bulama
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Paystack payment gateway class.
 */
class Mitzies_Jerk_Gateway_Paystack extends Mitzies_Jerk_Payment_Gateway {

    /**
     * API base URL.
     *
     * @var string
     */
    private $api_url = 'https://api.paystack.co';

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id = 'paystack';
        $this->title = __( 'Paystack', 'mitzies-jerk' );
        $this->description = __( 'Pay with Paystack - Cards, Bank Transfer, USSD, Mobile Money', 'mitzies-jerk' );
        $this->icon = MITZIES_JERK_URL . 'assets/images/paystack.png';
        $this->test_mode = (bool) $this->get_option( 'test_mode', true );
    }

    /**
     * Check if gateway is configured.
     *
     * @return bool
     */
    public function is_configured() {
        $secret_key = $this->get_secret_key();
        $public_key = $this->get_public_key();
        return ! empty( $secret_key ) && ! empty( $public_key );
    }

    /**
     * Get secret key.
     *
     * @return string
     */
    private function get_secret_key() {
        if ( $this->test_mode ) {
            return $this->get_option( 'test_secret_key' );
        }
        return $this->get_option( 'live_secret_key' );
    }

    /**
     * Get public key.
     *
     * @return string
     */
    private function get_public_key() {
        if ( $this->test_mode ) {
            return $this->get_option( 'test_public_key' );
        }
        return $this->get_option( 'live_public_key' );
    }

    /**
     * Process payment.
     *
     * @param int $order_id Order ID.
     * @return array|WP_Error
     */
    public function process_payment( $order_id ) {
        $order = new Mitzies_Jerk_Order( $order_id );
        $billing = $order->get( 'billing' );

        // Generate reference.
        $reference = 'MJ_' . $order_id . '_' . time();
        update_post_meta( $order_id, '_mj_payment_reference', $reference );

        // Initialize transaction.
        $response = $this->api_request( '/transaction/initialize', array(
            'email'       => $billing['email'],
            'amount'      => intval( $order->get( 'total' ) * 100 ), // Amount in kobo.
            'currency'    => $order->get( 'currency' ),
            'reference'   => $reference,
            'callback_url' => $this->get_callback_url(),
            'metadata'    => array(
                'order_id'   => $order_id,
                'custom_fields' => array(
                    array(
                        'display_name'  => 'Order Number',
                        'variable_name' => 'order_number',
                        'value'         => $order->get( 'order_number' ),
                    ),
                ),
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            $this->log( 'Payment initialization failed: ' . $response->get_error_message(), 'error', array( 'order_id' => $order_id ) );
            return $response;
        }

        if ( ! $response['status'] ) {
            $this->log( 'Payment initialization failed: ' . $response['message'], 'error', array( 'order_id' => $order_id ) );
            return new WP_Error( 'payment_error', $response['message'] );
        }

        return array(
            'result'       => 'success',
            'redirect'     => $response['data']['authorization_url'],
            'reference'    => $reference,
            'access_code'  => $response['data']['access_code'],
        );
    }

    /**
     * Handle payment callback.
     *
     * @return array|WP_Error
     */
    public function handle_callback() {
        $reference = isset( $_GET['reference'] ) ? sanitize_text_field( wp_unslash( $_GET['reference'] ) ) : '';

        if ( empty( $reference ) ) {
            return new WP_Error( 'no_reference', __( 'No payment reference provided.', 'mitzies-jerk' ) );
        }

        return $this->verify_payment( $reference );
    }

    /**
     * Handle webhook.
     */
    public function handle_webhook() {
        // Verify webhook signature.
        $input = file_get_contents( 'php://input' );
        $signature = isset( $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ) ) : '';

        if ( ! $this->verify_webhook_signature( $input, $signature ) ) {
            $this->log( 'Invalid webhook signature', 'error' );
            status_header( 401 );
            exit;
        }

        $event = json_decode( $input, true );

        if ( 'charge.success' === $event['event'] ) {
            $reference = $event['data']['reference'];
            $this->verify_payment( $reference );
        }

        status_header( 200 );
        exit;
    }

    /**
     * Verify payment.
     *
     * @param string $reference Payment reference.
     * @return array|WP_Error
     */
    public function verify_payment( $reference ) {
        $response = $this->api_request( '/transaction/verify/' . urlencode( $reference ), array(), 'GET' );

        if ( is_wp_error( $response ) ) {
            $this->log( 'Payment verification failed: ' . $response->get_error_message(), 'error', array( 'reference' => $reference ) );
            return $response;
        }

        if ( ! $response['status'] ) {
            return new WP_Error( 'verification_failed', $response['message'] );
        }

        $data = $response['data'];

        // Get order ID from reference.
        $parts = explode( '_', $reference );
        $order_id = isset( $parts[1] ) ? intval( $parts[1] ) : 0;

        if ( ! $order_id ) {
            return new WP_Error( 'invalid_reference', __( 'Invalid payment reference.', 'mitzies-jerk' ) );
        }

        // Verify amount.
        $order = new Mitzies_Jerk_Order( $order_id );
        $expected_amount = intval( $order->get( 'total' ) * 100 );

        if ( $data['amount'] !== $expected_amount ) {
            $this->log( 'Amount mismatch', 'error', array(
                'order_id' => $order_id,
                'expected' => $expected_amount,
                'received' => $data['amount'],
            ) );
            return new WP_Error( 'amount_mismatch', __( 'Payment amount mismatch.', 'mitzies-jerk' ) );
        }

        if ( 'success' === $data['status'] ) {
            $this->complete_payment( $order_id, $data['reference'], $data );

            return array(
                'result'   => 'success',
                'order_id' => $order_id,
                'redirect' => add_query_arg( 'order_id', $order_id, get_permalink( get_option( 'mitzies_jerk_order_received_page_id' ) ) ),
            );
        }

        $this->fail_payment( $order_id, __( 'Payment failed.', 'mitzies-jerk' ), $data );

        return new WP_Error( 'payment_failed', __( 'Payment was not successful.', 'mitzies-jerk' ) );
    }

    /**
     * Process refund.
     *
     * @param int    $order_id Order ID.
     * @param float  $amount   Refund amount.
     * @param string $reason   Refund reason.
     * @return bool|WP_Error
     */
    public function process_refund( $order_id, $amount, $reason = '' ) {
        $transaction_id = get_post_meta( $order_id, '_mj_transaction_id', true );

        if ( empty( $transaction_id ) ) {
            return new WP_Error( 'no_transaction', __( 'No transaction found for this order.', 'mitzies-jerk' ) );
        }

        $response = $this->api_request( '/refund', array(
            'transaction' => $transaction_id,
            'amount'      => intval( $amount * 100 ),
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        if ( ! $response['status'] ) {
            return new WP_Error( 'refund_failed', $response['message'] );
        }

        return true;
    }

    /**
     * Make API request.
     *
     * @param string $endpoint API endpoint.
     * @param array  $data     Request data.
     * @param string $method   HTTP method.
     * @return array|WP_Error
     */
    private function api_request( $endpoint, $data = array(), $method = 'POST' ) {
        $url = $this->api_url . $endpoint;
        $secret_key = $this->get_secret_key();

        $args = array(
            'method'  => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $secret_key,
                'Content-Type'  => 'application/json',
            ),
            'timeout' => 60,
        );

        if ( 'POST' === $method && ! empty( $data ) ) {
            $args['body'] = wp_json_encode( $data );
        }

        $response = wp_remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = wp_remote_retrieve_body( $response );
        $decoded = json_decode( $body, true );

        if ( null === $decoded ) {
            return new WP_Error( 'invalid_response', __( 'Invalid response from payment gateway.', 'mitzies-jerk' ) );
        }

        return $decoded;
    }

    /**
     * Verify webhook signature.
     *
     * @param string $input     Request body.
     * @param string $signature Signature header.
     * @return bool
     */
    private function verify_webhook_signature( $input, $signature ) {
        $secret_key = $this->get_secret_key();
        $computed = hash_hmac( 'sha512', $input, $secret_key );
        return hash_equals( $computed, $signature );
    }

    /**
     * Get settings fields.
     *
     * @return array
     */
    public function get_settings_fields() {
        return array(
            'test_mode' => array(
                'title'   => __( 'Test Mode', 'mitzies-jerk' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable test mode', 'mitzies-jerk' ),
                'default' => true,
            ),
            'test_public_key' => array(
                'title'       => __( 'Test Public Key', 'mitzies-jerk' ),
                'type'        => 'text',
                'description' => __( 'Your Paystack test public key.', 'mitzies-jerk' ),
            ),
            'test_secret_key' => array(
                'title'       => __( 'Test Secret Key', 'mitzies-jerk' ),
                'type'        => 'password',
                'description' => __( 'Your Paystack test secret key.', 'mitzies-jerk' ),
            ),
            'live_public_key' => array(
                'title'       => __( 'Live Public Key', 'mitzies-jerk' ),
                'type'        => 'text',
                'description' => __( 'Your Paystack live public key.', 'mitzies-jerk' ),
            ),
            'live_secret_key' => array(
                'title'       => __( 'Live Secret Key', 'mitzies-jerk' ),
                'type'        => 'password',
                'description' => __( 'Your Paystack live secret key.', 'mitzies-jerk' ),
            ),
        );
    }
}
