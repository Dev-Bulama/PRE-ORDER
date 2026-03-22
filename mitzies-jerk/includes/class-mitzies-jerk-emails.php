<?php
/**
 * Email handler class.
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
 * Email handler class.
 */
class Mitzies_Jerk_Emails {

    /**
     * From name.
     *
     * @var string
     */
    private $from_name;

    /**
     * From email.
     *
     * @var string
     */
    private $from_email;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->from_name = mitzies_jerk_get_option( 'email_from_name', get_bloginfo( 'name' ) );
        $this->from_email = mitzies_jerk_get_option( 'email_from_address', get_option( 'admin_email' ) );

        add_filter( 'wp_mail_from', array( $this, 'get_from_email' ) );
        add_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
        add_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );
    }

    /**
     * Get from email.
     *
     * @return string
     */
    public function get_from_email() {
        return $this->from_email;
    }

    /**
     * Get from name.
     *
     * @return string
     */
    public function get_from_name() {
        return $this->from_name;
    }

    /**
     * Get content type.
     *
     * @return string
     */
    public function get_content_type() {
        return 'text/html';
    }

    /**
     * Send email.
     *
     * @param string $to      Recipient email.
     * @param string $subject Email subject.
     * @param string $content Email content.
     * @param array  $headers Additional headers.
     * @return bool
     */
    public function send( $to, $subject, $content, $headers = array() ) {
        $html = $this->get_email_template( $content );

        return wp_mail( $to, $subject, $html, $headers );
    }

    /**
     * Get email template wrapper.
     *
     * @param string $content Email content.
     * @return string
     */
    private function get_email_template( $content ) {
        $logo = get_site_icon_url( 100 );
        $site_name = get_bloginfo( 'name' );
        $site_url = home_url();
        $year = date( 'Y' );

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html( $site_name ); ?></title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f7f7f7; }
                .email-wrapper { max-width: 600px; margin: 0 auto; background: #ffffff; }
                .email-header { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); padding: 30px; text-align: center; }
                .email-header img { max-width: 120px; height: auto; }
                .email-header h1 { color: #fff; margin: 15px 0 0; font-size: 24px; }
                .email-body { padding: 30px; }
                .email-footer { background: #333; color: #fff; padding: 20px 30px; text-align: center; font-size: 13px; }
                .email-footer a { color: #e74c3c; text-decoration: none; }
                .order-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .order-table th, .order-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
                .order-table th { background: #f9f9f9; font-weight: 600; }
                .order-total { font-size: 18px; font-weight: bold; color: #e74c3c; }
                .button { display: inline-block; padding: 12px 30px; background: #e74c3c; color: #fff; text-decoration: none; border-radius: 5px; font-weight: 600; margin: 10px 0; }
                .info-box { background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .info-box h4 { margin: 0 0 10px; color: #333; }
                .status-badge { display: inline-block; padding: 5px 15px; border-radius: 20px; font-size: 14px; font-weight: 600; }
                .status-paid { background: #d4edda; color: #155724; }
                .status-processing { background: #cce5ff; color: #004085; }
                .status-completed { background: #d4edda; color: #155724; }
                .status-cancelled { background: #f8d7da; color: #721c24; }
            </style>
        </head>
        <body>
            <div class="email-wrapper">
                <div class="email-header">
                    <?php if ( $logo ) : ?>
                        <img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( $site_name ); ?>">
                    <?php endif; ?>
                    <h1><?php echo esc_html( $site_name ); ?></h1>
                </div>
                <div class="email-body">
                    <?php echo $content; ?>
                </div>
                <div class="email-footer">
                    <p>&copy; <?php echo esc_html( $year ); ?> <a href="<?php echo esc_url( $site_url ); ?>"><?php echo esc_html( $site_name ); ?></a>. <?php esc_html_e( 'All rights reserved.', 'mitzies-jerk' ); ?></p>
                    <p><?php esc_html_e( 'Powered by Mitzies Jerk - Food Pre-Order System', 'mitzies-jerk' ); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Send order confirmation email to customer.
     *
     * @param int $order_id Order ID.
     * @return bool
     */
    public function send_order_confirmation( $order_id ) {
        $order = new Mitzies_Jerk_Order( $order_id );
        $billing = $order->get( 'billing' );
        $delivery = $order->get( 'delivery' );

        if ( empty( $billing['email'] ) ) {
            return false;
        }

        $subject = sprintf(
            /* translators: 1: Site name, 2: Order number */
            __( '[%1$s] Order Confirmation - %2$s', 'mitzies-jerk' ),
            get_bloginfo( 'name' ),
            $order->get( 'order_number' )
        );

        $content = $this->get_order_email_content( $order, 'confirmation' );

        return $this->send( $billing['email'], $subject, $content );
    }

    /**
     * Send new order notification to admin.
     *
     * @param int $order_id Order ID.
     * @return bool
     */
    public function send_new_order_admin_email( $order_id ) {
        $admin_email = mitzies_jerk_get_option( 'admin_email', get_option( 'admin_email' ) );
        $order = new Mitzies_Jerk_Order( $order_id );

        $subject = sprintf(
            /* translators: 1: Site name, 2: Order number */
            __( '[%1$s] New Order - %2$s', 'mitzies-jerk' ),
            get_bloginfo( 'name' ),
            $order->get( 'order_number' )
        );

        $content = $this->get_order_email_content( $order, 'new_order_admin' );

        return $this->send( $admin_email, $subject, $content );
    }

    /**
     * Send order status change email.
     *
     * @param int    $order_id Order ID.
     * @param string $status   New status.
     * @return bool
     */
    public function send_order_status_email( $order_id, $status ) {
        $order = new Mitzies_Jerk_Order( $order_id );
        $billing = $order->get( 'billing' );

        if ( empty( $billing['email'] ) ) {
            return false;
        }

        $statuses = mitzies_jerk_get_order_statuses();
        $status_label = isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status;

        $subject = sprintf(
            /* translators: 1: Site name, 2: Order number, 3: Status */
            __( '[%1$s] Order %2$s - %3$s', 'mitzies-jerk' ),
            get_bloginfo( 'name' ),
            $order->get( 'order_number' ),
            $status_label
        );

        $content = $this->get_order_email_content( $order, 'status_update', $status );

        return $this->send( $billing['email'], $subject, $content );
    }

    /**
     * Get order email content.
     *
     * @param Mitzies_Jerk_Order $order Order object.
     * @param string             $type  Email type.
     * @param string             $status Order status (for status emails).
     * @return string
     */
    private function get_order_email_content( $order, $type, $status = '' ) {
        $billing = $order->get( 'billing' );
        $delivery = $order->get( 'delivery' );
        $items = $order->get_items();
        $statuses = mitzies_jerk_get_order_statuses();

        ob_start();

        // Greeting.
        if ( 'new_order_admin' === $type ) {
            echo '<h2>' . esc_html__( 'New Order Received!', 'mitzies-jerk' ) . '</h2>';
            echo '<p>' . sprintf(
                /* translators: 1: Order number */
                esc_html__( 'You have received a new order #%s. Details below:', 'mitzies-jerk' ),
                esc_html( $order->get( 'order_number' ) )
            ) . '</p>';
        } elseif ( 'status_update' === $type ) {
            $status_label = isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status;
            echo '<h2>' . esc_html__( 'Order Status Update', 'mitzies-jerk' ) . '</h2>';
            echo '<p>' . esc_html__( 'Hello', 'mitzies-jerk' ) . ' ' . esc_html( $billing['first_name'] ) . ',</p>';
            echo '<p>' . sprintf(
                /* translators: 1: Order number, 2: Status */
                esc_html__( 'Your order #%1$s has been updated to: %2$s', 'mitzies-jerk' ),
                esc_html( $order->get( 'order_number' ) ),
                '<span class="status-badge status-' . esc_attr( $status ) . '">' . esc_html( $status_label ) . '</span>'
            ) . '</p>';
        } else {
            echo '<h2>' . esc_html__( 'Thank You for Your Order!', 'mitzies-jerk' ) . '</h2>';
            echo '<p>' . esc_html__( 'Hello', 'mitzies-jerk' ) . ' ' . esc_html( $billing['first_name'] ) . ',</p>';
            echo '<p>' . esc_html__( 'Thank you for your order. We are preparing your delicious food!', 'mitzies-jerk' ) . '</p>';
        }

        // Order details.
        echo '<div class="info-box">';
        echo '<h4>' . esc_html__( 'Order Details', 'mitzies-jerk' ) . '</h4>';
        echo '<p><strong>' . esc_html__( 'Order Number:', 'mitzies-jerk' ) . '</strong> ' . esc_html( $order->get( 'order_number' ) ) . '</p>';
        echo '<p><strong>' . esc_html__( 'Order Date:', 'mitzies-jerk' ) . '</strong> ' . esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $order->get( 'created_at' ) ) ) ) . '</p>';
        echo '<p><strong>' . esc_html__( 'Delivery Date/Time:', 'mitzies-jerk' ) . '</strong> ' . esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $order->get( 'delivery_datetime' ) ) ) ) . '</p>';

        // Show delivery timeframe/estimated time.
        $delivery_info = $order->get( 'delivery' );
        $delivery_method_id = isset( $delivery_info['delivery_method'] ) ? absint( $delivery_info['delivery_method'] ) : 0;
        $estimated_time = '';

        if ( $delivery_method_id > 0 ) {
            global $wpdb;
            $prefix = $wpdb->prefix . MITZIES_JERK_TABLE_PREFIX;
            $dm_table = $prefix . 'delivery_methods';

            // Only query if tables exist.
            if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $dm_table ) ) === $dm_table ) {
                $method = $wpdb->get_row( $wpdb->prepare(
                    "SELECT method_name, estimated_time, method_type FROM {$dm_table} WHERE id = %d",
                    $delivery_method_id
                ) );
                if ( $method ) {
                    echo '<p><strong>' . esc_html__( 'Delivery Method:', 'mitzies-jerk' ) . '</strong> ' . esc_html( $method->method_name ) . '</p>';
                    if ( ! empty( $method->estimated_time ) ) {
                        $estimated_time = $method->estimated_time;
                    }
                    // Show pickup location if applicable.
                    $pl_table = $prefix . 'pickup_locations';
                    if ( 'pickup' === $method->method_type && ! empty( $delivery_info['pickup_location'] ) && $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $pl_table ) ) === $pl_table ) {
                        $location = $wpdb->get_row( $wpdb->prepare(
                            "SELECT location_name, address FROM {$pl_table} WHERE id = %d",
                            absint( $delivery_info['pickup_location'] )
                        ) );
                        if ( $location ) {
                            echo '<p><strong>' . esc_html__( 'Pickup Location:', 'mitzies-jerk' ) . '</strong> ' . esc_html( $location->location_name ) . ' - ' . esc_html( $location->address ) . '</p>';
                        }
                    }
                }
            }
        }

        if ( ! empty( $estimated_time ) ) {
            echo '<p><strong>' . esc_html__( 'Estimated Delivery Timeframe:', 'mitzies-jerk' ) . '</strong> ' . esc_html( $estimated_time ) . '</p>';
        }

        echo '</div>';

        // Order items.
        echo '<table class="order-table">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'Item', 'mitzies-jerk' ) . '</th>';
        echo '<th>' . esc_html__( 'Qty', 'mitzies-jerk' ) . '</th>';
        echo '<th>' . esc_html__( 'Total', 'mitzies-jerk' ) . '</th>';
        echo '</tr></thead><tbody>';

        foreach ( $items as $item ) {
            $food_item = get_post( $item->food_item_id );
            $item_name = $food_item ? $food_item->post_title : __( 'Item', 'mitzies-jerk' );
            $addons = maybe_unserialize( $item->addons );

            echo '<tr>';
            echo '<td>' . esc_html( $item_name );
            if ( ! empty( $addons ) ) {
                echo '<br><small>';
                $addon_names = array();
                foreach ( $addons as $addon ) {
                    $addon_names[] = $addon['name'];
                }
                echo esc_html( implode( ', ', $addon_names ) );
                echo '</small>';
            }
            echo '</td>';
            echo '<td>' . esc_html( $item->quantity ) . '</td>';
            echo '<td>' . esc_html( mitzies_jerk_format_price( $item->subtotal ) ) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        // Totals.
        echo '<table class="order-table" style="width: 300px; margin-left: auto;">';
        echo '<tr><td>' . esc_html__( 'Subtotal', 'mitzies-jerk' ) . '</td><td style="text-align:right;">' . esc_html( mitzies_jerk_format_price( $order->get( 'subtotal' ) + $order->get( 'addon_total' ) ) ) . '</td></tr>';
        if ( $order->get( 'discount' ) > 0 ) {
            echo '<tr><td>' . esc_html__( 'Discount', 'mitzies-jerk' ) . '</td><td style="text-align:right;">-' . esc_html( mitzies_jerk_format_price( $order->get( 'discount' ) ) ) . '</td></tr>';
        }
        echo '<tr><td>' . esc_html__( 'Delivery', 'mitzies-jerk' ) . '</td><td style="text-align:right;">' . esc_html( mitzies_jerk_format_price( $order->get( 'delivery_fee' ) ) ) . '</td></tr>';
        if ( $order->get( 'tax' ) > 0 ) {
            echo '<tr><td>' . esc_html__( 'Tax', 'mitzies-jerk' ) . '</td><td style="text-align:right;">' . esc_html( mitzies_jerk_format_price( $order->get( 'tax' ) ) ) . '</td></tr>';
        }
        echo '<tr><td><strong>' . esc_html__( 'Total', 'mitzies-jerk' ) . '</strong></td><td style="text-align:right;" class="order-total">' . esc_html( mitzies_jerk_format_price( $order->get( 'total' ) ) ) . '</td></tr>';
        echo '</table>';

        // Delivery address.
        echo '<div class="info-box">';
        echo '<h4>' . esc_html__( 'Delivery Address', 'mitzies-jerk' ) . '</h4>';
        echo '<p>' . esc_html( $billing['first_name'] . ' ' . $billing['last_name'] ) . '<br>';
        echo esc_html( $delivery['address_1'] );
        if ( ! empty( $delivery['address_2'] ) ) {
            echo '<br>' . esc_html( $delivery['address_2'] );
        }
        echo '<br>' . esc_html( $delivery['city'] );
        if ( ! empty( $delivery['state'] ) ) {
            echo ', ' . esc_html( $delivery['state'] );
        }
        if ( ! empty( $delivery['postcode'] ) ) {
            echo ' ' . esc_html( $delivery['postcode'] );
        }
        echo '</p>';
        echo '<p><strong>' . esc_html__( 'Phone:', 'mitzies-jerk' ) . '</strong> ' . esc_html( $billing['phone'] ) . '</p>';
        echo '</div>';

        // Bank transfer payment details (for customer confirmation emails).
        if ( 'confirmation' === $type && 'bank_transfer' === $order->get( 'payment_method' ) ) {
            $bt_gateway = new Mitzies_Jerk_Gateway_Bank_Transfer();
            $bank_details = $bt_gateway->get_bank_details();
            $instructions = $bt_gateway->get_instructions();
            $proof_message = $bt_gateway->get_option( 'payment_proof_message', __( 'After making your bank transfer, please send a screenshot or photo of your payment receipt/proof to our email or WhatsApp. Include your Order Number as reference. Your order will be confirmed once we verify your payment.', 'mitzies-jerk' ) );

            echo '<div class="info-box" style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin: 20px 0;">';
            echo '<h4 style="margin-top: 0; color: #856404;">' . esc_html__( 'Bank Transfer Payment Required', 'mitzies-jerk' ) . '</h4>';

            if ( $instructions ) {
                echo '<p style="color: #856404;">' . esc_html( $instructions ) . '</p>';
            }

            $has_bank_info = ! empty( $bank_details['bank_name'] ) || ! empty( $bank_details['account_name'] ) || ! empty( $bank_details['account_number'] );
            if ( $has_bank_info ) {
                echo '<table style="width: 100%; border-collapse: collapse; background: #fff; padding: 10px; border-radius: 5px;">';
                if ( ! empty( $bank_details['bank_name'] ) ) {
                    echo '<tr><td style="padding: 5px 10px 5px 15px; font-weight: 600;">' . esc_html__( 'Bank Name', 'mitzies-jerk' ) . '</td><td style="padding: 5px 0;">' . esc_html( $bank_details['bank_name'] ) . '</td></tr>';
                }
                if ( ! empty( $bank_details['account_name'] ) ) {
                    echo '<tr><td style="padding: 5px 10px 5px 15px; font-weight: 600;">' . esc_html__( 'Account Name', 'mitzies-jerk' ) . '</td><td style="padding: 5px 0;">' . esc_html( $bank_details['account_name'] ) . '</td></tr>';
                }
                if ( ! empty( $bank_details['account_number'] ) ) {
                    echo '<tr><td style="padding: 5px 10px 5px 15px; font-weight: 600;">' . esc_html__( 'Account Number', 'mitzies-jerk' ) . '</td><td style="padding: 5px 0;">' . esc_html( $bank_details['account_number'] ) . '</td></tr>';
                }
                if ( ! empty( $bank_details['sort_code'] ) ) {
                    echo '<tr><td style="padding: 5px 10px 5px 15px; font-weight: 600;">' . esc_html__( 'Sort Code', 'mitzies-jerk' ) . '</td><td style="padding: 5px 0;">' . esc_html( $bank_details['sort_code'] ) . '</td></tr>';
                }
                if ( ! empty( $bank_details['iban'] ) ) {
                    echo '<tr><td style="padding: 5px 10px 5px 15px; font-weight: 600;">' . esc_html__( 'IBAN', 'mitzies-jerk' ) . '</td><td style="padding: 5px 0;">' . esc_html( $bank_details['iban'] ) . '</td></tr>';
                }
                if ( ! empty( $bank_details['swift_code'] ) ) {
                    echo '<tr><td style="padding: 5px 10px 5px 15px; font-weight: 600;">' . esc_html__( 'SWIFT/BIC', 'mitzies-jerk' ) . '</td><td style="padding: 5px 0;">' . esc_html( $bank_details['swift_code'] ) . '</td></tr>';
                }
                echo '</table>';
                echo '<p style="margin: 10px 0 0; font-size: 13px;"><strong>' . esc_html__( 'Payment Reference:', 'mitzies-jerk' ) . '</strong> ' . esc_html( $order->get( 'order_number' ) ) . '</p>';
            }

            if ( $proof_message ) {
                echo '<p style="margin: 15px 0 0; padding: 12px; background: #ffeeba; border-radius: 5px; color: #856404; font-weight: 600;">' . esc_html( $proof_message ) . '</p>';
            }

            echo '</div>';
        }

        // Track order button.
        $tracking_url = add_query_arg( 'order', $order->get( 'order_number' ), get_permalink( get_option( 'mitzies_jerk_order_tracking_page_id' ) ) );
        echo '<p style="text-align: center;">';
        echo '<a href="' . esc_url( $tracking_url ) . '" class="button">' . esc_html__( 'Track Your Order', 'mitzies-jerk' ) . '</a>';
        echo '</p>';

        return ob_get_clean();
    }

    /**
     * Send test email.
     *
     * @param string $to Recipient email.
     * @return bool
     */
    public function send_test_email( $to ) {
        $subject = sprintf(
            /* translators: %s: Site name */
            __( '[%s] Test Email', 'mitzies-jerk' ),
            get_bloginfo( 'name' )
        );

        $content = '<h2>' . esc_html__( 'Test Email', 'mitzies-jerk' ) . '</h2>';
        $content .= '<p>' . esc_html__( 'This is a test email from Mitzies Jerk. If you received this, your email settings are working correctly!', 'mitzies-jerk' ) . '</p>';
        $content .= '<p>' . esc_html__( 'Sent at:', 'mitzies-jerk' ) . ' ' . esc_html( current_time( 'mysql' ) ) . '</p>';

        return $this->send( $to, $subject, $content );
    }
}
