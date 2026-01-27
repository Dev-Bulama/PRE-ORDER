<?php
/**
 * Stripe payment gateway.
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
 * Stripe payment gateway class.
 */
class Mitzies_Jerk_Gateway_Stripe extends Mitzies_Jerk_Payment_Gateway {

    /**
     * API base URL.
     *
     * @var string
     */
    private $api_url = 'https://api.stripe.com/v1';

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id = 'stripe';
        $this->title = __( 'Stripe', 'mitzies-jerk' );
        $this->description = __( 'Pay with Credit/Debit Card via Stripe', 'mitzies-jerk' );
        $this->icon = MITZIES_JERK_URL . 'assets/images/stripe.png';
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
    public function get_public_key() {
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

        // Create Stripe Checkout Session.
        $line_items = array();
        $items = $order->get_items();

        foreach ( $items as $item ) {
            $food_item = get_post( $item->food_item_id );
            $item_name = $food_item ? $food_item->post_title : __( 'Food Item', 'mitzies-jerk' );
            $addons = maybe_unserialize( $item->addons );
            $addon_names = array();

            if ( ! empty( $addons ) ) {
                foreach ( $addons as $addon ) {
                    $addon_names[] = $addon['name'];
                }
            }

            $description = ! empty( $addon_names ) ? implode( ', ', $addon_names ) : '';

            $line_items[] = array(
                'price_data' => array(
                    'currency'     => strtolower( $order->get( 'currency' ) ),
                    'product_data' => array(
                        'name'        => $item_name,
                        'description' => $description,
                    ),
                    'unit_amount'  => intval( ( $item->price + ( $item->subtotal / $item->quantity - $item->price ) ) * 100 ),
                ),
                'quantity'   => $item->quantity,
            );
        }

        // Add delivery fee.
        $delivery_fee = floatval( $order->get( 'delivery_fee' ) );
        if ( $delivery_fee > 0 ) {
            $line_items[] = array(
                'price_data' => array(
                    'currency'     => strtolower( $order->get( 'currency' ) ),
                    'product_data' => array(
                        'name' => __( 'Delivery Fee', 'mitzies-jerk' ),
                    ),
                    'unit_amount'  => intval( $delivery_fee * 100 ),
                ),
                'quantity'   => 1,
            );
        }

        // Add tax.
        $tax = floatval( $order->get( 'tax' ) );
        if ( $tax > 0 ) {
            $line_items[] = array(
                'price_data' => array(
                    'currency'     => strtolower( $order->get( 'currency' ) ),
                    'product_data' => array(
                        'name' => __( 'Tax', 'mitzies-jerk' ),
                    ),
                    'unit_amount'  => intval( $tax * 100 ),
                ),
                'quantity'   => 1,
            );
        }

        $response = $this->api_request( '/checkout/sessions', array(
            'payment_method_types' => array( 'card' ),
            'mode'                 => 'payment',
            'line_items'           => $line_items,
            'customer_email'       => $billing['email'],
            'success_url'          => add_query_arg( array(
                'mj-api'    => 'payment-callback',
                'gateway'   => 'stripe',
                'session_id' => '{CHECKOUT_SESSION_ID}',
            ), home_url( '/' ) ),
            'cancel_url'           => add_query_arg( 'order_id', $order_id, get_permalink( get_option( 'mitzies_jerk_checkout_page_id' ) ) ),
            'metadata'             => array(
                'order_id'     => $order_id,
                'order_number' => $order->get( 'order_number' ),
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            $this->log( 'Checkout session creation failed: ' . $response->get_error_message(), 'error', array( 'order_id' => $order_id ) );
            return $response;
        }

        if ( isset( $response['error'] ) ) {
            $this->log( 'Checkout session creation failed: ' . $response['error']['message'], 'error', array( 'order_id' => $order_id ) );
            return new WP_Error( 'stripe_error', $response['error']['message'] );
        }

        update_post_meta( $order_id, '_mj_stripe_session_id', $response['id'] );

        return array(
            'result'     => 'success',
            'redirect'   => $response['url'],
            'session_id' => $response['id'],
        );
    }

    /**
     * Handle payment callback.
     *
     * @return array|WP_Error
     */
    public function handle_callback() {
        $session_id = isset( $_GET['session_id'] ) ? sanitize_text_field( wp_unslash( $_GET['session_id'] ) ) : '';

        if ( empty( $session_id ) ) {
            return new WP_Error( 'no_session', __( 'No session ID provided.', 'mitzies-jerk' ) );
        }

        return $this->verify_payment( $session_id );
    }

    /**
     * Handle webhook.
     */
    public function handle_webhook() {
        $payload = file_get_contents( 'php://input' );
        $sig_header = isset( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ) : '';
        $webhook_secret = $this->get_option( 'webhook_secret' );

        if ( $webhook_secret ) {
            // Verify signature.
            $timestamp = null;
            $signatures = array();

            $items = explode( ',', $sig_header );
            foreach ( $items as $item ) {
                $parts = explode( '=', $item, 2 );
                if ( count( $parts ) === 2 ) {
                    if ( 't' === $parts[0] ) {
                        $timestamp = $parts[1];
                    } elseif ( 'v1' === $parts[0] ) {
                        $signatures[] = $parts[1];
                    }
                }
            }

            if ( ! $timestamp || empty( $signatures ) ) {
                $this->log( 'Invalid webhook signature format', 'error' );
                status_header( 400 );
                exit;
            }

            $signed_payload = $timestamp . '.' . $payload;
            $expected_sig = hash_hmac( 'sha256', $signed_payload, $webhook_secret );

            $valid = false;
            foreach ( $signatures as $sig ) {
                if ( hash_equals( $expected_sig, $sig ) ) {
                    $valid = true;
                    break;
                }
            }

            if ( ! $valid ) {
                $this->log( 'Invalid webhook signature', 'error' );
                status_header( 401 );
                exit;
            }
        }

        $event = json_decode( $payload, true );

        if ( 'checkout.session.completed' === $event['type'] ) {
            $session = $event['data']['object'];
            $this->verify_payment( $session['id'] );
        }

        status_header( 200 );
        exit;
    }

    /**
     * Verify payment.
     *
     * @param string $session_id Checkout session ID.
     * @return array|WP_Error
     */
    public function verify_payment( $session_id ) {
        $response = $this->api_request( '/checkout/sessions/' . urlencode( $session_id ), array(), 'GET' );

        if ( is_wp_error( $response ) ) {
            $this->log( 'Session retrieval failed: ' . $response->get_error_message(), 'error', array( 'session_id' => $session_id ) );
            return $response;
        }

        if ( isset( $response['error'] ) ) {
            return new WP_Error( 'stripe_error', $response['error']['message'] );
        }

        $order_id = isset( $response['metadata']['order_id'] ) ? intval( $response['metadata']['order_id'] ) : 0;

        if ( ! $order_id ) {
            return new WP_Error( 'no_order', __( 'Order not found.', 'mitzies-jerk' ) );
        }

        if ( 'complete' === $response['status'] && 'paid' === $response['payment_status'] ) {
            $this->complete_payment( $order_id, $response['payment_intent'], $response );

            return array(
                'result'   => 'success',
                'order_id' => $order_id,
                'redirect' => add_query_arg( 'order_id', $order_id, get_permalink( get_option( 'mitzies_jerk_order_received_page_id' ) ) ),
            );
        }

        $this->fail_payment( $order_id, __( 'Payment failed.', 'mitzies-jerk' ), $response );

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
        $payment_intent = get_post_meta( $order_id, '_mj_transaction_id', true );

        if ( empty( $payment_intent ) ) {
            return new WP_Error( 'no_payment', __( 'No payment found for this order.', 'mitzies-jerk' ) );
        }

        $order = new Mitzies_Jerk_Order( $order_id );

        $data = array(
            'payment_intent' => $payment_intent,
            'amount'         => intval( $amount * 100 ),
        );

        if ( $reason ) {
            $data['reason'] = 'requested_by_customer';
            $data['metadata'] = array( 'reason' => $reason );
        }

        $response = $this->api_request( '/refunds', $data );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        if ( isset( $response['error'] ) ) {
            return new WP_Error( 'refund_failed', $response['error']['message'] );
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
                'Authorization' => 'Basic ' . base64_encode( $secret_key . ':' ),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ),
            'timeout' => 60,
        );

        if ( 'POST' === $method && ! empty( $data ) ) {
            $args['body'] = http_build_query( $data );
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
                'title'       => __( 'Test Publishable Key', 'mitzies-jerk' ),
                'type'        => 'text',
                'description' => __( 'Your Stripe test publishable key (pk_test_...).', 'mitzies-jerk' ),
            ),
            'test_secret_key' => array(
                'title'       => __( 'Test Secret Key', 'mitzies-jerk' ),
                'type'        => 'password',
                'description' => __( 'Your Stripe test secret key (sk_test_...).', 'mitzies-jerk' ),
            ),
            'live_public_key' => array(
                'title'       => __( 'Live Publishable Key', 'mitzies-jerk' ),
                'type'        => 'text',
                'description' => __( 'Your Stripe live publishable key (pk_live_...).', 'mitzies-jerk' ),
            ),
            'live_secret_key' => array(
                'title'       => __( 'Live Secret Key', 'mitzies-jerk' ),
                'type'        => 'password',
                'description' => __( 'Your Stripe live secret key (sk_live_...).', 'mitzies-jerk' ),
            ),
            'webhook_secret' => array(
                'title'       => __( 'Webhook Signing Secret', 'mitzies-jerk' ),
                'type'        => 'password',
                'description' => __( 'Your Stripe webhook signing secret (whsec_...).', 'mitzies-jerk' ),
            ),
        );
    }
}
