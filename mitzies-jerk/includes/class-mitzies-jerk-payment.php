<?php
/**
 * Payment handler class.
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
 * Payment handler class.
 */
class Mitzies_Jerk_Payment {

    /**
     * Available payment gateways.
     *
     * @var array
     */
    private $gateways = array();

    /**
     * Constructor.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->init_gateways();
    }

    /**
     * Initialize payment gateways.
     *
     * @since    1.0.0
     */
    private function init_gateways() {
        $this->gateways = array(
            'cod'           => new Mitzies_Jerk_Gateway_COD(),
            'bank_transfer' => new Mitzies_Jerk_Gateway_Bank_Transfer(),
            'paystack'      => new Mitzies_Jerk_Gateway_Paystack(),
            'flutterwave'   => new Mitzies_Jerk_Gateway_Flutterwave(),
            'stripe'        => new Mitzies_Jerk_Gateway_Stripe(),
            'paypal'        => new Mitzies_Jerk_Gateway_PayPal(),
        );

        /**
         * Filters the available payment gateways.
         *
         * @param array $gateways Payment gateways.
         */
        $this->gateways = apply_filters( 'mj_payment_gateways', $this->gateways );
    }

    /**
     * Get all gateways.
     *
     * @since    1.0.0
     * @return   array
     */
    public function get_gateways() {
        return $this->gateways;
    }

    /**
     * Get a specific gateway.
     *
     * @since    1.0.0
     * @param    string $gateway_id Gateway ID.
     * @return   object|null
     */
    public function get_gateway( $gateway_id ) {
        return isset( $this->gateways[ $gateway_id ] ) ? $this->gateways[ $gateway_id ] : null;
    }

    /**
     * Get enabled gateways.
     *
     * @since    1.0.0
     * @return   array
     */
    public function get_enabled_gateways() {
        $enabled = mitzies_jerk_get_option( 'enabled_gateways', array() );
        $gateways = array();

        foreach ( $enabled as $gateway_id ) {
            if ( isset( $this->gateways[ $gateway_id ] ) ) {
                $gateway = $this->gateways[ $gateway_id ];
                if ( $gateway->is_available() ) {
                    $gateways[ $gateway_id ] = $gateway;
                }
            }
        }

        return $gateways;
    }

    /**
     * Process payment.
     *
     * @since    1.0.0
     * @param    int    $order_id   Order ID.
     * @param    string $gateway_id Gateway ID.
     * @return   array|WP_Error Payment result or error.
     */
    public function process( $order_id, $gateway_id ) {
        $gateway = $this->get_gateway( $gateway_id );

        if ( ! $gateway ) {
            return new WP_Error( 'invalid_gateway', __( 'Invalid payment gateway.', 'mitzies-jerk' ) );
        }

        if ( ! $gateway->is_available() ) {
            return new WP_Error( 'gateway_unavailable', __( 'This payment method is currently unavailable.', 'mitzies-jerk' ) );
        }

        // Log payment attempt.
        $order = new Mitzies_Jerk_Order( $order_id );
        Mitzies_Jerk_Database::log_payment( array(
            'order_id'      => $order_id,
            'gateway'       => $gateway_id,
            'amount'        => $order->get( 'total' ),
            'currency'      => $order->get( 'currency' ),
            'status'        => 'initiated',
            'response_data' => '',
        ) );

        /**
         * Fires before payment processing.
         *
         * @param int    $order_id   Order ID.
         * @param string $gateway_id Gateway ID.
         */
        do_action( 'mj_before_payment_process', $order_id, $gateway_id );

        // Process payment through gateway.
        $result = $gateway->process_payment( $order_id );

        /**
         * Fires after payment processing.
         *
         * @param int    $order_id   Order ID.
         * @param string $gateway_id Gateway ID.
         * @param mixed  $result     Payment result.
         */
        do_action( 'mj_after_payment_process', $order_id, $gateway_id, $result );

        return $result;
    }

    /**
     * Handle payment callback/webhook.
     *
     * @since    1.0.0
     * @param    string $gateway_id Gateway ID.
     * @return   array|WP_Error
     */
    public function handle_callback( $gateway_id ) {
        $gateway = $this->get_gateway( $gateway_id );

        if ( ! $gateway ) {
            return new WP_Error( 'invalid_gateway', __( 'Invalid payment gateway.', 'mitzies-jerk' ) );
        }

        return $gateway->handle_callback();
    }

    /**
     * Handle payment webhook.
     *
     * @since    1.0.0
     * @param    string $gateway_id Gateway ID.
     */
    public function handle_webhook( $gateway_id ) {
        $gateway = $this->get_gateway( $gateway_id );

        if ( $gateway ) {
            $gateway->handle_webhook();
        }
    }

    /**
     * Verify payment.
     *
     * @since    1.0.0
     * @param    string $gateway_id Gateway ID.
     * @param    string $reference  Payment reference.
     * @return   array|WP_Error
     */
    public function verify_payment( $gateway_id, $reference ) {
        $gateway = $this->get_gateway( $gateway_id );

        if ( ! $gateway ) {
            return new WP_Error( 'invalid_gateway', __( 'Invalid payment gateway.', 'mitzies-jerk' ) );
        }

        return $gateway->verify_payment( $reference );
    }

    /**
     * Process refund.
     *
     * @since    1.0.0
     * @param    int    $order_id Order ID.
     * @param    float  $amount   Refund amount.
     * @param    string $reason   Refund reason.
     * @return   bool|WP_Error
     */
    public function process_refund( $order_id, $amount = null, $reason = '' ) {
        $order = new Mitzies_Jerk_Order( $order_id );
        $gateway_id = $order->get( 'payment_method' );
        $gateway = $this->get_gateway( $gateway_id );

        if ( ! $gateway ) {
            return new WP_Error( 'invalid_gateway', __( 'Invalid payment gateway.', 'mitzies-jerk' ) );
        }

        if ( ! method_exists( $gateway, 'process_refund' ) ) {
            return new WP_Error( 'refund_not_supported', __( 'This payment gateway does not support refunds.', 'mitzies-jerk' ) );
        }

        if ( null === $amount ) {
            $amount = $order->get( 'total' );
        }

        $result = $gateway->process_refund( $order_id, $amount, $reason );

        if ( ! is_wp_error( $result ) && $result ) {
            $order->update_status( $order_id, 'refunded', sprintf(
                /* translators: 1: Refund amount, 2: Refund reason */
                __( 'Refunded %1$s. Reason: %2$s', 'mitzies-jerk' ),
                mitzies_jerk_format_price( $amount ),
                $reason
            ) );
        }

        return $result;
    }
}

/**
 * Abstract payment gateway class.
 */
abstract class Mitzies_Jerk_Payment_Gateway {

    /**
     * Gateway ID.
     *
     * @var string
     */
    protected $id;

    /**
     * Gateway title.
     *
     * @var string
     */
    protected $title;

    /**
     * Gateway description.
     *
     * @var string
     */
    protected $description;

    /**
     * Gateway icon URL.
     *
     * @var string
     */
    protected $icon;

    /**
     * Is gateway enabled.
     *
     * @var bool
     */
    protected $enabled = false;

    /**
     * Test mode.
     *
     * @var bool
     */
    protected $test_mode = false;

    /**
     * Get gateway ID.
     *
     * @return string
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Get gateway title.
     *
     * @return string
     */
    public function get_title() {
        return $this->title;
    }

    /**
     * Get gateway description.
     *
     * @return string
     */
    public function get_description() {
        return $this->description;
    }

    /**
     * Get gateway icon.
     *
     * @return string
     */
    public function get_icon() {
        return $this->icon;
    }

    /**
     * Check if gateway is enabled.
     *
     * @return bool
     */
    public function is_enabled() {
        $enabled = mitzies_jerk_get_option( 'enabled_gateways', array() );
        return in_array( $this->id, $enabled, true );
    }

    /**
     * Check if gateway is available.
     *
     * @return bool
     */
    public function is_available() {
        return $this->is_enabled() && $this->is_configured();
    }

    /**
     * Check if gateway is configured.
     *
     * @return bool
     */
    abstract public function is_configured();

    /**
     * Process payment.
     *
     * @param int $order_id Order ID.
     * @return array|WP_Error
     */
    abstract public function process_payment( $order_id );

    /**
     * Handle payment callback.
     *
     * @return array|WP_Error
     */
    abstract public function handle_callback();

    /**
     * Handle webhook.
     */
    public function handle_webhook() {
        // Override in child class if needed.
    }

    /**
     * Verify payment.
     *
     * @param string $reference Payment reference.
     * @return array|WP_Error
     */
    abstract public function verify_payment( $reference );

    /**
     * Get callback URL.
     *
     * @return string
     */
    protected function get_callback_url() {
        return add_query_arg(
            array(
                'mj-api' => 'payment-callback',
                'gateway' => $this->id,
            ),
            home_url( '/' )
        );
    }

    /**
     * Get webhook URL.
     *
     * @return string
     */
    protected function get_webhook_url() {
        return add_query_arg(
            array(
                'mj-api' => 'payment-webhook',
                'gateway' => $this->id,
            ),
            home_url( '/' )
        );
    }

    /**
     * Get option.
     *
     * @param string $key     Option key.
     * @param mixed  $default Default value.
     * @return mixed
     */
    protected function get_option( $key, $default = '' ) {
        return mitzies_jerk_get_option( $this->id . '_' . $key, $default );
    }

    /**
     * Log gateway message.
     *
     * @param string $message Message.
     * @param string $level   Log level.
     * @param array  $context Context data.
     */
    protected function log( $message, $level = 'info', $context = array() ) {
        $context['gateway'] = $this->id;
        mitzies_jerk_log( $message, $level, $context );
    }

    /**
     * Complete payment.
     *
     * @param int    $order_id       Order ID.
     * @param string $transaction_id Transaction ID.
     * @param array  $response_data  Response data.
     */
    protected function complete_payment( $order_id, $transaction_id, $response_data = array() ) {
        $order = new Mitzies_Jerk_Order();
        $order->mark_paid( $order_id, $transaction_id );

        // Log successful payment.
        Mitzies_Jerk_Database::log_payment( array(
            'order_id'       => $order_id,
            'gateway'        => $this->id,
            'transaction_id' => $transaction_id,
            'amount'         => get_post_meta( $order_id, '_mj_total', true ),
            'currency'       => get_post_meta( $order_id, '_mj_currency', true ),
            'status'         => 'completed',
            'response_data'  => maybe_serialize( $response_data ),
        ) );
    }

    /**
     * Fail payment.
     *
     * @param int    $order_id      Order ID.
     * @param string $error_message Error message.
     * @param array  $response_data Response data.
     */
    protected function fail_payment( $order_id, $error_message, $response_data = array() ) {
        $order = new Mitzies_Jerk_Order();
        $order->update_status( $order_id, 'failed', $error_message );

        // Log failed payment.
        Mitzies_Jerk_Database::log_payment( array(
            'order_id'       => $order_id,
            'gateway'        => $this->id,
            'amount'         => get_post_meta( $order_id, '_mj_total', true ),
            'currency'       => get_post_meta( $order_id, '_mj_currency', true ),
            'status'         => 'failed',
            'response_data'  => maybe_serialize( array_merge( $response_data, array( 'error' => $error_message ) ) ),
        ) );
    }
}
