<?php
/**
 * Flutterwave payment gateway.
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
 * Flutterwave payment gateway class.
 */
class Mitzies_Jerk_Gateway_Flutterwave extends Mitzies_Jerk_Payment_Gateway {

    /**
     * API base URL.
     *
     * @var string
     */
    private $api_url = 'https://api.flutterwave.com/v3';

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id = 'flutterwave';
        $this->title = __( 'Flutterwave', 'mitzies-jerk' );
        $this->description = __( 'Pay with Flutterwave - Cards, Bank Transfer, Mobile Money, USSD', 'mitzies-jerk' );
        $this->icon = MITZIES_JERK_URL . 'assets/images/flutterwave.png';
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
        $reference = 'MJ_' . $order_id . '_' . uniqid();
        update_post_meta( $order_id, '_mj_payment_reference', $reference );

        // Initialize payment.
        $response = $this->api_request( '/payments', array(
            'tx_ref'         => $reference,
            'amount'         => floatval( $order->get( 'total' ) ),
            'currency'       => $order->get( 'currency' ),
            'redirect_url'   => $this->get_callback_url(),
            'customer'       => array(
                'email'        => $billing['email'],
                'phonenumber'  => $billing['phone'],
                'name'         => $billing['first_name'] . ' ' . $billing['last_name'],
            ),
            'customizations' => array(
                'title'       => get_bloginfo( 'name' ),
                'description' => sprintf( __( 'Order %s', 'mitzies-jerk' ), $order->get( 'order_number' ) ),
                'logo'        => get_site_icon_url(),
            ),
            'meta'           => array(
                'order_id'     => $order_id,
                'order_number' => $order->get( 'order_number' ),
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            $this->log( 'Payment initialization failed: ' . $response->get_error_message(), 'error', array( 'order_id' => $order_id ) );
            return $response;
        }

        if ( 'success' !== $response['status'] ) {
            $this->log( 'Payment initialization failed: ' . ( $response['message'] ?? 'Unknown error' ), 'error', array( 'order_id' => $order_id ) );
            return new WP_Error( 'payment_error', $response['message'] ?? __( 'Payment initialization failed.', 'mitzies-jerk' ) );
        }

        return array(
            'result'    => 'success',
            'redirect'  => $response['data']['link'],
            'reference' => $reference,
        );
    }

    /**
     * Handle payment callback.
     *
     * @return array|WP_Error
     */
    public function handle_callback() {
        $status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
        $tx_ref = isset( $_GET['tx_ref'] ) ? sanitize_text_field( wp_unslash( $_GET['tx_ref'] ) ) : '';
        $transaction_id = isset( $_GET['transaction_id'] ) ? sanitize_text_field( wp_unslash( $_GET['transaction_id'] ) ) : '';

        if ( empty( $tx_ref ) ) {
            return new WP_Error( 'no_reference', __( 'No payment reference provided.', 'mitzies-jerk' ) );
        }

        if ( 'successful' !== $status ) {
            // Get order ID from reference.
            $parts = explode( '_', $tx_ref );
            $order_id = isset( $parts[1] ) ? intval( $parts[1] ) : 0;

            if ( $order_id ) {
                $this->fail_payment( $order_id, __( 'Payment was not successful.', 'mitzies-jerk' ), array( 'status' => $status ) );
            }

            return new WP_Error( 'payment_failed', __( 'Payment was not successful.', 'mitzies-jerk' ) );
        }

        return $this->verify_payment( $transaction_id );
    }

    /**
     * Handle webhook.
     */
    public function handle_webhook() {
        // Verify webhook hash.
        $secret_hash = $this->get_option( 'webhook_secret' );
        $signature = isset( $_SERVER['HTTP_VERIF_HASH'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_VERIF_HASH'] ) ) : '';

        if ( $secret_hash && $signature !== $secret_hash ) {
            $this->log( 'Invalid webhook signature', 'error' );
            status_header( 401 );
            exit;
        }

        $input = file_get_contents( 'php://input' );
        $event = json_decode( $input, true );

        if ( 'charge.completed' === $event['event'] && 'successful' === $event['data']['status'] ) {
            $this->verify_payment( $event['data']['id'] );
        }

        status_header( 200 );
        exit;
    }

    /**
     * Verify payment.
     *
     * @param string $transaction_id Transaction ID.
     * @return array|WP_Error
     */
    public function verify_payment( $transaction_id ) {
        $response = $this->api_request( '/transactions/' . urlencode( $transaction_id ) . '/verify', array(), 'GET' );

        if ( is_wp_error( $response ) ) {
            $this->log( 'Payment verification failed: ' . $response->get_error_message(), 'error', array( 'transaction_id' => $transaction_id ) );
            return $response;
        }

        if ( 'success' !== $response['status'] ) {
            return new WP_Error( 'verification_failed', $response['message'] ?? __( 'Verification failed.', 'mitzies-jerk' ) );
        }

        $data = $response['data'];

        // Get order ID from tx_ref.
        $parts = explode( '_', $data['tx_ref'] );
        $order_id = isset( $parts[1] ) ? intval( $parts[1] ) : 0;

        if ( ! $order_id ) {
            return new WP_Error( 'invalid_reference', __( 'Invalid payment reference.', 'mitzies-jerk' ) );
        }

        // Verify amount.
        $order = new Mitzies_Jerk_Order( $order_id );
        $expected_amount = floatval( $order->get( 'total' ) );

        if ( abs( $data['amount'] - $expected_amount ) > 0.01 ) {
            $this->log( 'Amount mismatch', 'error', array(
                'order_id' => $order_id,
                'expected' => $expected_amount,
                'received' => $data['amount'],
            ) );
            return new WP_Error( 'amount_mismatch', __( 'Payment amount mismatch.', 'mitzies-jerk' ) );
        }

        if ( 'successful' === $data['status'] ) {
            $this->complete_payment( $order_id, $data['flw_ref'], $data );

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
                'description' => __( 'Your Flutterwave test public key.', 'mitzies-jerk' ),
            ),
            'test_secret_key' => array(
                'title'       => __( 'Test Secret Key', 'mitzies-jerk' ),
                'type'        => 'password',
                'description' => __( 'Your Flutterwave test secret key.', 'mitzies-jerk' ),
            ),
            'live_public_key' => array(
                'title'       => __( 'Live Public Key', 'mitzies-jerk' ),
                'type'        => 'text',
                'description' => __( 'Your Flutterwave live public key.', 'mitzies-jerk' ),
            ),
            'live_secret_key' => array(
                'title'       => __( 'Live Secret Key', 'mitzies-jerk' ),
                'type'        => 'password',
                'description' => __( 'Your Flutterwave live secret key.', 'mitzies-jerk' ),
            ),
            'webhook_secret' => array(
                'title'       => __( 'Webhook Secret Hash', 'mitzies-jerk' ),
                'type'        => 'text',
                'description' => __( 'Your Flutterwave webhook secret hash for verification.', 'mitzies-jerk' ),
            ),
        );
    }
}
