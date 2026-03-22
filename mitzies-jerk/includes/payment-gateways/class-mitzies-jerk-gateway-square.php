<?php
/**
 * Square payment gateway.
 *
 * @package    Mitzies_Jerk
 * @subpackage Mitzies_Jerk/includes/payment-gateways
 * @author     SkillScore IT Solutions and Training, Tijani Bulama
 * @since      1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Square payment gateway class.
 *
 * Setup Instructions:
 * ==================
 * 1. Create a Square account at https://squareup.com
 * 2. Go to the Square Developer Dashboard: https://developer.squareup.com/apps
 * 3. Create a new application (or select an existing one)
 * 4. From the application dashboard, get:
 *    - Application ID (found in the Credentials tab)
 *    - Access Token (Personal Access Token from the Credentials tab)
 *    - Location ID (from the Locations tab or Square Dashboard > Account & Settings > Locations)
 * 5. In your WordPress admin panel:
 *    - Go to Mitzies Jerk > Settings > Payment
 *    - Enable Square payment gateway
 *    - Paste your Application ID, Access Token, and Location ID
 *    - Choose Sandbox (for testing) or Production (for live payments)
 *    - Save settings
 * 6. For Sandbox testing:
 *    - Use the Sandbox Access Token and Sandbox Application ID from Square Developer Dashboard
 *    - Test card: 4532 7597 3454 5858 (Visa), any future expiry, any CVV, any ZIP
 * 7. For Production (live payments):
 *    - Use the Production Access Token and Production Application ID
 *    - Ensure your Square account is fully set up and verified
 *    - Enable Apple Pay / Google Pay in your Square Dashboard if desired
 */
class Mitzies_Jerk_Gateway_Square extends Mitzies_Jerk_Payment_Gateway {

    /**
     * API base URL.
     *
     * @var string
     */
    private $api_url;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id          = 'square';
        $this->title       = __( 'Square', 'mitzies-jerk' );
        $this->description = __( 'Pay with Credit/Debit Card, Apple Pay, or Google Pay via Square', 'mitzies-jerk' );
        $this->icon        = '';
        $this->test_mode   = (bool) $this->get_option( 'test_mode', true );
        $this->api_url     = $this->test_mode
            ? 'https://connect.squareupsandbox.com/v2'
            : 'https://connect.squareup.com/v2';
    }

    /**
     * Check if gateway is configured.
     *
     * @return bool
     */
    public function is_configured() {
        $app_id       = $this->get_application_id();
        $access_token = $this->get_access_token();
        $location_id  = $this->get_location_id();
        return ! empty( $app_id ) && ! empty( $access_token ) && ! empty( $location_id );
    }

    /**
     * Get application ID.
     *
     * @return string
     */
    private function get_application_id() {
        if ( $this->test_mode ) {
            return $this->get_option( 'sandbox_application_id', '' );
        }
        return $this->get_option( 'application_id', '' );
    }

    /**
     * Get access token.
     *
     * @return string
     */
    private function get_access_token() {
        if ( $this->test_mode ) {
            return $this->get_option( 'sandbox_access_token', '' );
        }
        return $this->get_option( 'access_token', '' );
    }

    /**
     * Get location ID.
     *
     * @return string
     */
    private function get_location_id() {
        if ( $this->test_mode ) {
            return $this->get_option( 'sandbox_location_id', '' );
        }
        return $this->get_option( 'location_id', '' );
    }

    /**
     * Process payment.
     *
     * @param int $order_id Order ID.
     * @return array|WP_Error
     */
    public function process_payment( $order_id ) {
        $order        = new Mitzies_Jerk_Order( $order_id );
        $total        = $order->get( 'total' );
        $currency     = strtoupper( $order->get( 'currency' ) );
        $billing      = $order->get( 'billing' );
        $order_number = $order->get( 'order_number' );

        // Convert total to smallest currency unit (cents).
        $amount = (int) round( $total * 100 );

        // Generate idempotency key for this order.
        $idempotency_key = 'mj_order_' . $order_id . '_' . wp_generate_uuid4();

        $body = array(
            'idempotency_key' => $idempotency_key,
            'amount_money'    => array(
                'amount'   => $amount,
                'currency' => $currency,
            ),
            'source_id'       => 'EXTERNAL',
            'location_id'     => $this->get_location_id(),
            'reference_id'    => $order_number,
            'note'            => sprintf(
                /* translators: 1: Site name, 2: Order number */
                __( '%1$s - Order #%2$s', 'mitzies-jerk' ),
                get_bloginfo( 'name' ),
                $order_number
            ),
        );

        // Add buyer email if available.
        if ( ! empty( $billing['email'] ) ) {
            $body['buyer_email_address'] = sanitize_email( $billing['email'] );
        }

        $response = wp_remote_post(
            $this->api_url . '/payments',
            array(
                'headers' => array(
                    'Authorization'  => 'Bearer ' . $this->get_access_token(),
                    'Content-Type'   => 'application/json',
                    'Square-Version' => '2024-01-18',
                ),
                'body'    => wp_json_encode( $body ),
                'timeout' => 45,
            )
        );

        if ( is_wp_error( $response ) ) {
            $this->log( 'Square API error: ' . $response->get_error_message(), 'error' );
            return new WP_Error( 'square_error', __( 'Payment processing failed. Please try again.', 'mitzies-jerk' ) );
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 === $response_code && isset( $response_body['payment'] ) ) {
            $payment = $response_body['payment'];

            if ( 'COMPLETED' === $payment['status'] || 'APPROVED' === $payment['status'] ) {
                $this->complete_payment( $order_id, $payment['id'], $response_body );

                $order_received_page = get_option( 'mitzies_jerk_order_received_page_id' );
                $redirect_url = $order_received_page
                    ? add_query_arg( 'order_id', $order_id, get_permalink( $order_received_page ) )
                    : home_url( '/' );

                return array(
                    'result'   => 'success',
                    'redirect' => $redirect_url,
                );
            }
        }

        // Handle error.
        $error_message = __( 'Payment failed.', 'mitzies-jerk' );
        if ( isset( $response_body['errors'] ) && ! empty( $response_body['errors'] ) ) {
            $error_message = $response_body['errors'][0]['detail'] ?? $error_message;
        }

        $this->log( 'Square payment failed: ' . $error_message, 'error', $response_body ?? array() );
        $this->fail_payment( $order_id, $error_message, $response_body ?? array() );

        return new WP_Error( 'square_payment_failed', $error_message );
    }

    /**
     * Handle payment callback.
     *
     * @return array|WP_Error
     */
    public function handle_callback() {
        return array( 'result' => 'success' );
    }

    /**
     * Handle webhook.
     */
    public function handle_webhook() {
        $payload = file_get_contents( 'php://input' );
        $data    = json_decode( $payload, true );

        if ( empty( $data ) || ! isset( $data['type'] ) ) {
            wp_die( 'Invalid webhook', 'Square Webhook', array( 'response' => 400 ) );
        }

        $this->log( 'Square webhook received: ' . $data['type'], 'info', $data );

        if ( 'payment.completed' === $data['type'] && isset( $data['data']['object']['payment'] ) ) {
            $payment      = $data['data']['object']['payment'];
            $reference_id = $payment['reference_id'] ?? '';

            if ( $reference_id ) {
                $order = Mitzies_Jerk_Order::get_by_order_number( $reference_id );
                if ( $order ) {
                    $this->complete_payment( $order->get( 'id' ), $payment['id'], $data );
                }
            }
        }

        wp_die( 'OK', 'Square Webhook', array( 'response' => 200 ) );
    }

    /**
     * Verify payment.
     *
     * @param string $reference Payment reference (Square payment ID).
     * @return array|WP_Error
     */
    public function verify_payment( $reference ) {
        $response = wp_remote_get(
            $this->api_url . '/payments/' . $reference,
            array(
                'headers' => array(
                    'Authorization'  => 'Bearer ' . $this->get_access_token(),
                    'Content-Type'   => 'application/json',
                    'Square-Version' => '2024-01-18',
                ),
                'timeout' => 30,
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['payment'] ) ) {
            return array(
                'status'         => $body['payment']['status'],
                'transaction_id' => $body['payment']['id'],
                'amount'         => $body['payment']['amount_money']['amount'] / 100,
            );
        }

        return new WP_Error( 'verification_failed', __( 'Could not verify payment.', 'mitzies-jerk' ) );
    }

    /**
     * Get settings fields for admin.
     *
     * @return array
     */
    public function get_settings_fields() {
        return array(
            'test_mode' => array(
                'title'       => __( 'Test Mode', 'mitzies-jerk' ),
                'type'        => 'checkbox',
                'label'       => __( 'Enable sandbox/test mode', 'mitzies-jerk' ),
                'default'     => 1,
                'description' => __( 'Use Square sandbox for testing. Uncheck for live payments.', 'mitzies-jerk' ),
            ),
            'application_id' => array(
                'title'       => __( 'Production Application ID', 'mitzies-jerk' ),
                'type'        => 'text',
                'default'     => '',
                'description' => __( 'Your Square production Application ID.', 'mitzies-jerk' ),
            ),
            'access_token' => array(
                'title'       => __( 'Production Access Token', 'mitzies-jerk' ),
                'type'        => 'password',
                'default'     => '',
                'description' => __( 'Your Square production Access Token.', 'mitzies-jerk' ),
            ),
            'location_id' => array(
                'title'       => __( 'Production Location ID', 'mitzies-jerk' ),
                'type'        => 'text',
                'default'     => '',
                'description' => __( 'Your Square production Location ID.', 'mitzies-jerk' ),
            ),
            'sandbox_application_id' => array(
                'title'       => __( 'Sandbox Application ID', 'mitzies-jerk' ),
                'type'        => 'text',
                'default'     => '',
                'description' => __( 'Your Square sandbox Application ID for testing.', 'mitzies-jerk' ),
            ),
            'sandbox_access_token' => array(
                'title'       => __( 'Sandbox Access Token', 'mitzies-jerk' ),
                'type'        => 'password',
                'default'     => '',
                'description' => __( 'Your Square sandbox Access Token for testing.', 'mitzies-jerk' ),
            ),
            'sandbox_location_id' => array(
                'title'       => __( 'Sandbox Location ID', 'mitzies-jerk' ),
                'type'        => 'text',
                'default'     => '',
                'description' => __( 'Your Square sandbox Location ID for testing.', 'mitzies-jerk' ),
            ),
        );
    }
}
