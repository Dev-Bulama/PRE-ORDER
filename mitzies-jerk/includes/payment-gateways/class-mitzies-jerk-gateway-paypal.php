<?php
/**
 * PayPal payment gateway.
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
 * PayPal payment gateway class.
 */
class Mitzies_Jerk_Gateway_PayPal extends Mitzies_Jerk_Payment_Gateway {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id = 'paypal';
        $this->title = __( 'PayPal', 'mitzies-jerk' );
        $this->description = __( 'Pay securely with PayPal', 'mitzies-jerk' );
        $this->icon = MITZIES_JERK_URL . 'assets/images/paypal.png';
        $this->test_mode = (bool) $this->get_option( 'test_mode', true );
    }

    /**
     * Get API URL.
     *
     * @return string
     */
    private function get_api_url() {
        if ( $this->test_mode ) {
            return 'https://api-m.sandbox.paypal.com';
        }
        return 'https://api-m.paypal.com';
    }

    /**
     * Check if gateway is configured.
     *
     * @return bool
     */
    public function is_configured() {
        $client_id = $this->get_client_id();
        $client_secret = $this->get_client_secret();
        return ! empty( $client_id ) && ! empty( $client_secret );
    }

    /**
     * Get client ID.
     *
     * @return string
     */
    private function get_client_id() {
        if ( $this->test_mode ) {
            return $this->get_option( 'sandbox_client_id' );
        }
        return $this->get_option( 'live_client_id' );
    }

    /**
     * Get client secret.
     *
     * @return string
     */
    private function get_client_secret() {
        if ( $this->test_mode ) {
            return $this->get_option( 'sandbox_client_secret' );
        }
        return $this->get_option( 'live_client_secret' );
    }

    /**
     * Get access token.
     *
     * @return string|WP_Error
     */
    private function get_access_token() {
        $transient_key = 'mj_paypal_access_token_' . ( $this->test_mode ? 'sandbox' : 'live' );
        $access_token = get_transient( $transient_key );

        if ( $access_token ) {
            return $access_token;
        }

        $response = wp_remote_post( $this->get_api_url() . '/v1/oauth2/token', array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $this->get_client_id() . ':' . $this->get_client_secret() ),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ),
            'body'    => 'grant_type=client_credentials',
            'timeout' => 60,
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['error'] ) ) {
            return new WP_Error( 'paypal_auth_error', $body['error_description'] ?? $body['error'] );
        }

        if ( isset( $body['access_token'] ) ) {
            set_transient( $transient_key, $body['access_token'], $body['expires_in'] - 60 );
            return $body['access_token'];
        }

        return new WP_Error( 'paypal_auth_error', __( 'Failed to get access token.', 'mitzies-jerk' ) );
    }

    /**
     * Process payment.
     *
     * @param int $order_id Order ID.
     * @return array|WP_Error
     */
    public function process_payment( $order_id ) {
        $access_token = $this->get_access_token();

        if ( is_wp_error( $access_token ) ) {
            return $access_token;
        }

        $order = new Mitzies_Jerk_Order( $order_id );
        $billing = $order->get( 'billing' );

        // Build items array.
        $items = array();
        $items_total = 0;
        $order_items = $order->get_items();

        foreach ( $order_items as $item ) {
            $food_item = get_post( $item->food_item_id );
            $unit_amount = round( $item->subtotal / $item->quantity, 2 );
            $items_total += $unit_amount * $item->quantity;

            $items[] = array(
                'name'        => $food_item->post_title,
                'quantity'    => strval( $item->quantity ),
                'unit_amount' => array(
                    'currency_code' => $order->get( 'currency' ),
                    'value'         => number_format( $unit_amount, 2, '.', '' ),
                ),
            );
        }

        // Calculate breakdown.
        $breakdown = array(
            'item_total' => array(
                'currency_code' => $order->get( 'currency' ),
                'value'         => number_format( $items_total, 2, '.', '' ),
            ),
        );

        $delivery_fee = floatval( $order->get( 'delivery_fee' ) );
        if ( $delivery_fee > 0 ) {
            $breakdown['shipping'] = array(
                'currency_code' => $order->get( 'currency' ),
                'value'         => number_format( $delivery_fee, 2, '.', '' ),
            );
        }

        $tax = floatval( $order->get( 'tax' ) );
        if ( $tax > 0 ) {
            $breakdown['tax_total'] = array(
                'currency_code' => $order->get( 'currency' ),
                'value'         => number_format( $tax, 2, '.', '' ),
            );
        }

        $discount = floatval( $order->get( 'discount' ) );
        if ( $discount > 0 ) {
            $breakdown['discount'] = array(
                'currency_code' => $order->get( 'currency' ),
                'value'         => number_format( $discount, 2, '.', '' ),
            );
        }

        $payload = array(
            'intent'         => 'CAPTURE',
            'purchase_units' => array(
                array(
                    'reference_id' => strval( $order_id ),
                    'description'  => sprintf( __( 'Order %s', 'mitzies-jerk' ), $order->get( 'order_number' ) ),
                    'custom_id'    => strval( $order_id ),
                    'amount'       => array(
                        'currency_code' => $order->get( 'currency' ),
                        'value'         => number_format( $order->get( 'total' ), 2, '.', '' ),
                        'breakdown'     => $breakdown,
                    ),
                    'items'        => $items,
                ),
            ),
            'application_context' => array(
                'return_url'          => add_query_arg( array(
                    'mj-api'  => 'payment-callback',
                    'gateway' => 'paypal',
                ), home_url( '/' ) ),
                'cancel_url'          => add_query_arg( 'order_id', $order_id, get_permalink( get_option( 'mitzies_jerk_checkout_page_id' ) ) ),
                'brand_name'          => get_bloginfo( 'name' ),
                'shipping_preference' => 'NO_SHIPPING',
                'user_action'         => 'PAY_NOW',
            ),
        );

        $response = wp_remote_post( $this->get_api_url() . '/v2/checkout/orders', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json',
            ),
            'body'    => wp_json_encode( $payload ),
            'timeout' => 60,
        ) );

        if ( is_wp_error( $response ) ) {
            $this->log( 'Order creation failed: ' . $response->get_error_message(), 'error', array( 'order_id' => $order_id ) );
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['error'] ) || ( isset( $body['name'] ) && 'INVALID_REQUEST' === $body['name'] ) ) {
            $error_message = $body['message'] ?? $body['error_description'] ?? __( 'PayPal order creation failed.', 'mitzies-jerk' );
            $this->log( 'Order creation failed: ' . $error_message, 'error', array( 'order_id' => $order_id, 'response' => $body ) );
            return new WP_Error( 'paypal_error', $error_message );
        }

        update_post_meta( $order_id, '_mj_paypal_order_id', $body['id'] );

        // Find approve link.
        $approve_url = '';
        foreach ( $body['links'] as $link ) {
            if ( 'approve' === $link['rel'] ) {
                $approve_url = $link['href'];
                break;
            }
        }

        if ( empty( $approve_url ) ) {
            return new WP_Error( 'paypal_error', __( 'No approval URL found.', 'mitzies-jerk' ) );
        }

        return array(
            'result'        => 'success',
            'redirect'      => $approve_url,
            'paypal_order'  => $body['id'],
        );
    }

    /**
     * Handle payment callback.
     *
     * @return array|WP_Error
     */
    public function handle_callback() {
        $paypal_order_id = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

        if ( empty( $paypal_order_id ) ) {
            return new WP_Error( 'no_token', __( 'No PayPal order ID provided.', 'mitzies-jerk' ) );
        }

        // Capture payment.
        return $this->capture_payment( $paypal_order_id );
    }

    /**
     * Capture payment.
     *
     * @param string $paypal_order_id PayPal order ID.
     * @return array|WP_Error
     */
    private function capture_payment( $paypal_order_id ) {
        $access_token = $this->get_access_token();

        if ( is_wp_error( $access_token ) ) {
            return $access_token;
        }

        $response = wp_remote_post( $this->get_api_url() . '/v2/checkout/orders/' . $paypal_order_id . '/capture', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json',
            ),
            'body'    => '{}',
            'timeout' => 60,
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 'COMPLETED' === ( $body['status'] ?? '' ) ) {
            $capture = $body['purchase_units'][0]['payments']['captures'][0] ?? null;
            $order_id = intval( $body['purchase_units'][0]['custom_id'] ?? 0 );

            if ( $order_id && $capture ) {
                $this->complete_payment( $order_id, $capture['id'], $body );

                return array(
                    'result'   => 'success',
                    'order_id' => $order_id,
                    'redirect' => add_query_arg( 'order_id', $order_id, get_permalink( get_option( 'mitzies_jerk_order_received_page_id' ) ) ),
                );
            }
        }

        // Get order ID from database.
        global $wpdb;
        $order_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_mj_paypal_order_id' AND meta_value = %s",
                $paypal_order_id
            )
        );

        if ( $order_id ) {
            $this->fail_payment( $order_id, __( 'Payment capture failed.', 'mitzies-jerk' ), $body );
        }

        return new WP_Error( 'capture_failed', __( 'Payment capture failed.', 'mitzies-jerk' ) );
    }

    /**
     * Verify payment.
     *
     * @param string $paypal_order_id PayPal order ID.
     * @return array|WP_Error
     */
    public function verify_payment( $paypal_order_id ) {
        $access_token = $this->get_access_token();

        if ( is_wp_error( $access_token ) ) {
            return $access_token;
        }

        $response = wp_remote_get( $this->get_api_url() . '/v2/checkout/orders/' . $paypal_order_id, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
            ),
            'timeout' => 60,
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 'COMPLETED' === ( $body['status'] ?? '' ) ) {
            return array(
                'result' => 'success',
                'data'   => $body,
            );
        }

        return new WP_Error( 'not_completed', __( 'Payment is not completed.', 'mitzies-jerk' ) );
    }

    /**
     * Handle webhook.
     */
    public function handle_webhook() {
        $payload = file_get_contents( 'php://input' );
        $event = json_decode( $payload, true );

        // Verify webhook (simplified - production should verify signature).
        if ( 'PAYMENT.CAPTURE.COMPLETED' === ( $event['event_type'] ?? '' ) ) {
            $capture = $event['resource'] ?? null;
            if ( $capture ) {
                // Find order by PayPal order ID from supplementary data.
                $paypal_order_id = $capture['supplementary_data']['related_ids']['order_id'] ?? '';

                if ( $paypal_order_id ) {
                    global $wpdb;
                    $order_id = $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_mj_paypal_order_id' AND meta_value = %s",
                            $paypal_order_id
                        )
                    );

                    if ( $order_id ) {
                        $this->complete_payment( $order_id, $capture['id'], $capture );
                    }
                }
            }
        }

        status_header( 200 );
        exit;
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
        $capture_id = get_post_meta( $order_id, '_mj_transaction_id', true );

        if ( empty( $capture_id ) ) {
            return new WP_Error( 'no_capture', __( 'No capture ID found for this order.', 'mitzies-jerk' ) );
        }

        $access_token = $this->get_access_token();

        if ( is_wp_error( $access_token ) ) {
            return $access_token;
        }

        $order = new Mitzies_Jerk_Order( $order_id );

        $payload = array(
            'amount' => array(
                'value'         => number_format( $amount, 2, '.', '' ),
                'currency_code' => $order->get( 'currency' ),
            ),
        );

        if ( $reason ) {
            $payload['note_to_payer'] = $reason;
        }

        $response = wp_remote_post( $this->get_api_url() . '/v2/payments/captures/' . $capture_id . '/refund', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json',
            ),
            'body'    => wp_json_encode( $payload ),
            'timeout' => 60,
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 'COMPLETED' === ( $body['status'] ?? '' ) ) {
            return true;
        }

        return new WP_Error( 'refund_failed', $body['message'] ?? __( 'Refund failed.', 'mitzies-jerk' ) );
    }

    /**
     * Get settings fields.
     *
     * @return array
     */
    public function get_settings_fields() {
        return array(
            'test_mode' => array(
                'title'   => __( 'Sandbox Mode', 'mitzies-jerk' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable PayPal sandbox mode', 'mitzies-jerk' ),
                'default' => true,
            ),
            'sandbox_client_id' => array(
                'title'       => __( 'Sandbox Client ID', 'mitzies-jerk' ),
                'type'        => 'text',
                'description' => __( 'Your PayPal sandbox client ID.', 'mitzies-jerk' ),
            ),
            'sandbox_client_secret' => array(
                'title'       => __( 'Sandbox Client Secret', 'mitzies-jerk' ),
                'type'        => 'password',
                'description' => __( 'Your PayPal sandbox client secret.', 'mitzies-jerk' ),
            ),
            'live_client_id' => array(
                'title'       => __( 'Live Client ID', 'mitzies-jerk' ),
                'type'        => 'text',
                'description' => __( 'Your PayPal live client ID.', 'mitzies-jerk' ),
            ),
            'live_client_secret' => array(
                'title'       => __( 'Live Client Secret', 'mitzies-jerk' ),
                'type'        => 'password',
                'description' => __( 'Your PayPal live client secret.', 'mitzies-jerk' ),
            ),
        );
    }
}
