<?php
/**
 * Checkout handler class.
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
 * Checkout handler class.
 */
class Mitzies_Jerk_Checkout {

    /**
     * Checkout fields.
     *
     * @var array
     */
    private $fields = array();

    /**
     * Validation errors.
     *
     * @var WP_Error
     */
    private $errors;

    /**
     * Constructor.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->errors = new WP_Error();
        $this->init_fields();
    }

    /**
     * Initialize checkout fields.
     *
     * @since    1.0.0
     */
    private function init_fields() {
        $this->fields = array(
            'billing' => array(
                'first_name' => array(
                    'label'       => __( 'First Name', 'mitzies-jerk' ),
                    'type'        => 'text',
                    'required'    => true,
                    'placeholder' => __( 'Enter your first name', 'mitzies-jerk' ),
                    'priority'    => 10,
                ),
                'last_name'  => array(
                    'label'       => __( 'Last Name', 'mitzies-jerk' ),
                    'type'        => 'text',
                    'required'    => true,
                    'placeholder' => __( 'Enter your last name', 'mitzies-jerk' ),
                    'priority'    => 20,
                ),
                'email'      => array(
                    'label'       => __( 'Email Address', 'mitzies-jerk' ),
                    'type'        => 'email',
                    'required'    => true,
                    'placeholder' => __( 'Enter your email', 'mitzies-jerk' ),
                    'priority'    => 30,
                ),
                'phone'      => array(
                    'label'       => __( 'Phone Number', 'mitzies-jerk' ),
                    'type'        => 'tel',
                    'required'    => true,
                    'placeholder' => __( 'Enter your phone number', 'mitzies-jerk' ),
                    'priority'    => 40,
                ),
            ),
            'delivery' => array(
                'address_1'    => array(
                    'label'       => __( 'Street Address', 'mitzies-jerk' ),
                    'type'        => 'text',
                    'required'    => true,
                    'placeholder' => __( 'House number and street name', 'mitzies-jerk' ),
                    'priority'    => 10,
                ),
                'address_2'    => array(
                    'label'       => __( 'Apartment, Suite, etc.', 'mitzies-jerk' ),
                    'type'        => 'text',
                    'required'    => false,
                    'placeholder' => __( 'Apartment, suite, unit, etc. (optional)', 'mitzies-jerk' ),
                    'priority'    => 20,
                ),
                'city'         => array(
                    'label'       => __( 'City', 'mitzies-jerk' ),
                    'type'        => 'text',
                    'required'    => true,
                    'placeholder' => __( 'Enter your city', 'mitzies-jerk' ),
                    'priority'    => 30,
                ),
                'state'        => array(
                    'label'       => __( 'State/Region', 'mitzies-jerk' ),
                    'type'        => 'text',
                    'required'    => false,
                    'placeholder' => __( 'Enter your state or region', 'mitzies-jerk' ),
                    'priority'    => 40,
                ),
                'postcode'     => array(
                    'label'       => __( 'Postcode/ZIP', 'mitzies-jerk' ),
                    'type'        => 'text',
                    'required'    => false,
                    'placeholder' => __( 'Enter your postcode', 'mitzies-jerk' ),
                    'priority'    => 50,
                ),
                'delivery_date' => array(
                    'label'       => __( 'Delivery Date', 'mitzies-jerk' ),
                    'type'        => 'date',
                    'required'    => true,
                    'priority'    => 60,
                ),
                'delivery_time' => array(
                    'label'       => __( 'Delivery Time Slot', 'mitzies-jerk' ),
                    'type'        => 'select',
                    'required'    => true,
                    'options'     => $this->get_delivery_time_slots(),
                    'priority'    => 70,
                ),
                'instructions' => array(
                    'label'       => __( 'Delivery Instructions', 'mitzies-jerk' ),
                    'type'        => 'textarea',
                    'required'    => false,
                    'placeholder' => __( 'Any special instructions for delivery (optional)', 'mitzies-jerk' ),
                    'priority'    => 80,
                ),
            ),
        );

        /**
         * Filters the checkout fields.
         *
         * @param array $fields Checkout fields.
         */
        $this->fields = apply_filters( 'mj_checkout_fields', $this->fields );
    }

    /**
     * Get checkout fields.
     *
     * @since    1.0.0
     * @param    string $group Field group (billing, delivery, or empty for all).
     * @return   array
     */
    public function get_fields( $group = '' ) {
        if ( $group && isset( $this->fields[ $group ] ) ) {
            return $this->fields[ $group ];
        }

        return $this->fields;
    }

    /**
     * Get delivery time slots.
     *
     * @since    1.0.0
     * @return   array
     */
    private function get_delivery_time_slots() {
        $slots = mitzies_jerk_get_option( 'delivery_time_slots', array() );
        $options = array( '' => __( 'Select a time slot', 'mitzies-jerk' ) );

        foreach ( $slots as $slot ) {
            $key = $slot['start'] . '-' . $slot['end'];
            $options[ $key ] = sprintf( '%s - %s', $slot['start'], $slot['end'] );
        }

        return $options;
    }

    /**
     * Process checkout.
     *
     * @since    1.0.0
     * @param    array $posted_data Posted checkout data.
     * @return   int|WP_Error Order ID or error.
     */
    public function process_checkout( $posted_data ) {
        global $mitzies_jerk;

        $cart = $mitzies_jerk->cart;

        // Check if cart is empty.
        if ( $cart->is_empty() ) {
            return new WP_Error( 'empty_cart', __( 'Your cart is empty.', 'mitzies-jerk' ) );
        }

        // Validate fields.
        $this->validate_posted_data( $posted_data );

        if ( $this->errors->has_errors() ) {
            return $this->errors;
        }

        // Validate delivery date/time.
        $delivery_datetime = $posted_data['delivery_date'] . ' ' . explode( '-', $posted_data['delivery_time'] )[0];
        $datetime_validation = mitzies_jerk_validate_delivery_datetime( $delivery_datetime );

        if ( is_wp_error( $datetime_validation ) ) {
            return $datetime_validation;
        }

        // Check payment gateway.
        $payment_gateway = isset( $posted_data['payment_method'] ) ? sanitize_text_field( $posted_data['payment_method'] ) : '';

        if ( empty( $payment_gateway ) ) {
            return new WP_Error( 'no_payment_method', __( 'Please select a payment method.', 'mitzies-jerk' ) );
        }

        // Create order.
        $order_data = $this->prepare_order_data( $posted_data, $cart );
        $order = new Mitzies_Jerk_Order();
        $order_id = $order->create( $order_data );

        if ( is_wp_error( $order_id ) ) {
            return $order_id;
        }

        // Process payment.
        $payment = new Mitzies_Jerk_Payment();
        $payment_result = $payment->process( $order_id, $payment_gateway );

        if ( is_wp_error( $payment_result ) ) {
            // Update order status to failed.
            $order->update_status( $order_id, 'failed', $payment_result->get_error_message() );
            return $payment_result;
        }

        // Empty cart after successful order creation.
        $cart->empty_cart();

        /**
         * Fires after a successful checkout.
         *
         * @param int   $order_id       Order ID.
         * @param array $posted_data    Posted data.
         * @param array $payment_result Payment result.
         */
        do_action( 'mj_checkout_complete', $order_id, $posted_data, $payment_result );

        return array(
            'order_id' => $order_id,
            'payment'  => $payment_result,
        );
    }

    /**
     * Validate posted data.
     *
     * @since    1.0.0
     * @param    array $posted_data Posted data.
     */
    private function validate_posted_data( $posted_data ) {
        foreach ( $this->fields as $group => $fields ) {
            foreach ( $fields as $key => $field ) {
                $field_key = $group . '_' . $key;
                $value = isset( $posted_data[ $key ] ) ? $posted_data[ $key ] : '';

                // Check required.
                if ( ! empty( $field['required'] ) && empty( $value ) ) {
                    $this->errors->add(
                        $field_key . '_required',
                        sprintf(
                            /* translators: %s: Field label */
                            __( '%s is required.', 'mitzies-jerk' ),
                            $field['label']
                        )
                    );
                    continue;
                }

                // Validate by type.
                if ( ! empty( $value ) ) {
                    switch ( $field['type'] ) {
                        case 'email':
                            if ( ! is_email( $value ) ) {
                                $this->errors->add(
                                    $field_key . '_invalid',
                                    __( 'Please enter a valid email address.', 'mitzies-jerk' )
                                );
                            }
                            break;

                        case 'tel':
                            if ( ! preg_match( '/^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/', $value ) ) {
                                $this->errors->add(
                                    $field_key . '_invalid',
                                    __( 'Please enter a valid phone number.', 'mitzies-jerk' )
                                );
                            }
                            break;

                        case 'date':
                            if ( ! strtotime( $value ) ) {
                                $this->errors->add(
                                    $field_key . '_invalid',
                                    __( 'Please enter a valid date.', 'mitzies-jerk' )
                                );
                            }
                            break;
                    }
                }
            }
        }

        /**
         * Fires during checkout validation.
         *
         * @param array    $posted_data Posted data.
         * @param WP_Error $errors      Error object.
         */
        do_action( 'mj_checkout_validation', $posted_data, $this->errors );
    }

    /**
     * Prepare order data.
     *
     * @since    1.0.0
     * @param    array              $posted_data Posted data.
     * @param    Mitzies_Jerk_Cart $cart        Cart instance.
     * @return   array
     */
    private function prepare_order_data( $posted_data, $cart ) {
        $user_id = is_user_logged_in() ? get_current_user_id() : 0;

        // Calculate delivery datetime.
        $delivery_time_parts = explode( '-', $posted_data['delivery_time'] );
        $delivery_datetime = $posted_data['delivery_date'] . ' ' . $delivery_time_parts[0] . ':00';

        // Get cart totals.
        $totals = $cart->get_totals();

        $order_data = array(
            'user_id'           => $user_id,
            'status'            => 'pending',
            'payment_method'    => sanitize_text_field( $posted_data['payment_method'] ),
            'subtotal'          => $totals['subtotal'],
            'addon_total'       => $totals['addon_total'],
            'discount'          => $totals['discount'],
            'delivery_fee'      => $totals['delivery_fee'],
            'tax'               => $totals['tax'],
            'total'             => $totals['total'],
            'currency'          => mitzies_jerk_get_option( 'currency', 'USD' ),
            'applied_coupons'   => $cart->get_applied_coupons(),
            'delivery_datetime' => $delivery_datetime,
            'billing'           => array(
                'first_name' => sanitize_text_field( $posted_data['first_name'] ?? '' ),
                'last_name'  => sanitize_text_field( $posted_data['last_name'] ?? '' ),
                'email'      => sanitize_email( $posted_data['email'] ?? '' ),
                'phone'      => sanitize_text_field( $posted_data['phone'] ?? '' ),
            ),
            'delivery'          => array(
                'address_1'    => sanitize_text_field( $posted_data['address_1'] ?? '' ),
                'address_2'    => sanitize_text_field( $posted_data['address_2'] ?? '' ),
                'city'         => sanitize_text_field( $posted_data['city'] ?? '' ),
                'state'        => sanitize_text_field( $posted_data['state'] ?? '' ),
                'postcode'     => sanitize_text_field( $posted_data['postcode'] ?? '' ),
                'instructions' => sanitize_textarea_field( $posted_data['instructions'] ?? '' ),
            ),
            'items'             => $cart->get_cart_contents(),
            'ip_address'        => $this->get_client_ip(),
            'user_agent'        => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
        );

        /**
         * Filters the order data before creation.
         *
         * @param array $order_data  Order data.
         * @param array $posted_data Posted checkout data.
         */
        return apply_filters( 'mj_checkout_order_data', $order_data, $posted_data );
    }

    /**
     * Get client IP address.
     *
     * @since    1.0.0
     * @return   string
     */
    private function get_client_ip() {
        $ip = '';

        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
        } elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
        }

        return $ip;
    }

    /**
     * Get errors.
     *
     * @since    1.0.0
     * @return   WP_Error
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * Get available payment gateways.
     *
     * @since    1.0.0
     * @return   array
     */
    public function get_available_payment_gateways() {
        $enabled = mitzies_jerk_get_option( 'enabled_gateways', array() );
        $gateways = array();

        // Get gateway-specific settings from stored options.
        $bank_details = '';
        $bank_name = mitzies_jerk_get_option( 'bank_transfer_bank_name', '' );
        $account_name = mitzies_jerk_get_option( 'bank_transfer_account_name', '' );
        $account_number = mitzies_jerk_get_option( 'bank_transfer_account_number', '' );
        $sort_code = mitzies_jerk_get_option( 'bank_transfer_sort_code', '' );

        if ( ! empty( $bank_name ) || ! empty( $account_number ) ) {
            $details_parts = array();
            if ( ! empty( $bank_name ) ) {
                $details_parts[] = sprintf( __( 'Bank: %s', 'mitzies-jerk' ), $bank_name );
            }
            if ( ! empty( $account_name ) ) {
                $details_parts[] = sprintf( __( 'Account Name: %s', 'mitzies-jerk' ), $account_name );
            }
            if ( ! empty( $account_number ) ) {
                $details_parts[] = sprintf( __( 'Account Number: %s', 'mitzies-jerk' ), $account_number );
            }
            if ( ! empty( $sort_code ) ) {
                $details_parts[] = sprintf( __( 'Sort Code: %s', 'mitzies-jerk' ), $sort_code );
            }
            $bank_details = implode( "\n", $details_parts );
        }

        $cod_instructions = mitzies_jerk_get_option( 'cod_instructions', '' );
        if ( empty( $cod_instructions ) ) {
            $cod_instructions = __( 'Pay with cash when your order is delivered. Please have the exact amount ready.', 'mitzies-jerk' );
        }

        $all_gateways = array(
            'cod'         => array(
                'title'       => __( 'Cash on Delivery', 'mitzies-jerk' ),
                'description' => ! empty( $cod_instructions ) ? $cod_instructions : __( 'Pay when your order arrives', 'mitzies-jerk' ),
                'icon'        => '',
                'icon_class'  => 'dashicons-money-alt',
            ),
            'bank_transfer' => array(
                'title'       => __( 'Bank Transfer', 'mitzies-jerk' ),
                'description' => __( 'Pay via direct bank transfer', 'mitzies-jerk' ),
                'icon'        => '',
                'icon_class'  => 'dashicons-bank',
                'extra_info'  => ! empty( $bank_details ) ? nl2br( esc_html( $bank_details ) ) : '',
            ),
            'paystack'    => array(
                'title'       => __( 'Paystack', 'mitzies-jerk' ),
                'description' => __( 'Pay with Paystack - Cards, Bank Transfer, USSD', 'mitzies-jerk' ),
                'icon'        => '',
                'icon_class'  => 'dashicons-credit-card',
            ),
            'flutterwave' => array(
                'title'       => __( 'Flutterwave', 'mitzies-jerk' ),
                'description' => __( 'Pay with Flutterwave - Cards, Bank Transfer, Mobile Money', 'mitzies-jerk' ),
                'icon'        => '',
                'icon_class'  => 'dashicons-credit-card',
            ),
            'stripe'      => array(
                'title'       => __( 'Stripe', 'mitzies-jerk' ),
                'description' => __( 'Pay with Credit/Debit Card via Stripe', 'mitzies-jerk' ),
                'icon'        => '',
                'icon_class'  => 'dashicons-credit-card',
            ),
            'paypal'      => array(
                'title'       => __( 'PayPal', 'mitzies-jerk' ),
                'description' => __( 'Pay with PayPal', 'mitzies-jerk' ),
                'icon'        => '',
                'icon_class'  => 'dashicons-paypal',
            ),
        );

        // If no gateways are enabled, default to COD and Bank Transfer.
        if ( empty( $enabled ) ) {
            $enabled = array( 'cod', 'bank_transfer' );
        }

        foreach ( $enabled as $gateway_id ) {
            if ( isset( $all_gateways[ $gateway_id ] ) ) {
                $gateways[ $gateway_id ] = $all_gateways[ $gateway_id ];
            }
        }

        /**
         * Filters the available payment gateways.
         *
         * @param array $gateways Available payment gateways.
         */
        return apply_filters( 'mj_available_payment_gateways', $gateways );
    }

    /**
     * Get minimum delivery date.
     *
     * @since    1.0.0
     * @return   string Date in Y-m-d format.
     */
    public function get_min_delivery_date() {
        $min_hours = mitzies_jerk_get_min_preorder_hours();
        return date( 'Y-m-d', strtotime( '+' . $min_hours . ' hours' ) );
    }

    /**
     * Get maximum delivery date.
     *
     * @since    1.0.0
     * @return   string Date in Y-m-d format.
     */
    public function get_max_delivery_date() {
        $max_days = (int) mitzies_jerk_get_option( 'max_preorder_days', 30 );
        return date( 'Y-m-d', strtotime( '+' . $max_days . ' days' ) );
    }

    /**
     * Get available delivery dates.
     *
     * @since    1.0.0
     * @return   array
     */
    public function get_available_delivery_dates() {
        $min_date = $this->get_min_delivery_date();
        $max_date = $this->get_max_delivery_date();
        $allowed_days = mitzies_jerk_get_option( 'delivery_days', array( 0, 1, 2, 3, 4, 5, 6 ) );

        $dates = array();
        $current = strtotime( $min_date );
        $end = strtotime( $max_date );

        while ( $current <= $end ) {
            $day_of_week = (int) date( 'w', $current );

            if ( in_array( $day_of_week, $allowed_days, true ) ) {
                $dates[] = date( 'Y-m-d', $current );
            }

            $current = strtotime( '+1 day', $current );
        }

        return $dates;
    }
}
