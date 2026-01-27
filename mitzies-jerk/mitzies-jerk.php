<?php
/**
 * Plugin Name: Mitzies Jerk - Food Pre-Order System
 * Plugin URI: https://skillscoreit.com/mitzies-jerk
 * Description: A comprehensive food pre-order system allowing customers to browse food items, pre-order meals at least 24 hours in advance, make immediate online payments, schedule delivery, and track countdown to order/payment expiry.
 * Version: 1.0.0
 * Author: SkillScore IT Solutions and Training, Tijani Bulama
 * Author URI: https://skillscoreit.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: mitzies-jerk
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 *
 * @package Mitzies_Jerk
 * @author SkillScore IT Solutions and Training, Tijani Bulama
 * @copyright 2024 SkillScore IT Solutions and Training
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Current plugin version.
 */
define( 'MITZIES_JERK_VERSION', '1.0.0' );

/**
 * Plugin base file.
 */
define( 'MITZIES_JERK_FILE', __FILE__ );

/**
 * Plugin base path.
 */
define( 'MITZIES_JERK_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin base URL.
 */
define( 'MITZIES_JERK_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename.
 */
define( 'MITZIES_JERK_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Database table prefix for the plugin.
 */
define( 'MITZIES_JERK_TABLE_PREFIX', 'mitzies_jerk_' );

/**
 * Minimum required WordPress version.
 */
define( 'MITZIES_JERK_MIN_WP_VERSION', '5.8' );

/**
 * Minimum required PHP version.
 */
define( 'MITZIES_JERK_MIN_PHP_VERSION', '7.4' );

/**
 * The code that runs during plugin activation.
 */
function mitzies_jerk_activate() {
    require_once MITZIES_JERK_PATH . 'includes/class-mitzies-jerk-activator.php';
    Mitzies_Jerk_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function mitzies_jerk_deactivate() {
    require_once MITZIES_JERK_PATH . 'includes/class-mitzies-jerk-deactivator.php';
    Mitzies_Jerk_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'mitzies_jerk_activate' );
register_deactivation_hook( __FILE__, 'mitzies_jerk_deactivate' );

/**
 * Check system requirements before loading the plugin.
 *
 * @return bool True if requirements are met, false otherwise.
 */
function mitzies_jerk_check_requirements() {
    $errors = array();

    // Check PHP version.
    if ( version_compare( PHP_VERSION, MITZIES_JERK_MIN_PHP_VERSION, '<' ) ) {
        $errors[] = sprintf(
            /* translators: 1: Current PHP version, 2: Required PHP version */
            __( 'Mitzies Jerk requires PHP version %2$s or higher. You are running version %1$s.', 'mitzies-jerk' ),
            PHP_VERSION,
            MITZIES_JERK_MIN_PHP_VERSION
        );
    }

    // Check WordPress version.
    if ( version_compare( get_bloginfo( 'version' ), MITZIES_JERK_MIN_WP_VERSION, '<' ) ) {
        $errors[] = sprintf(
            /* translators: 1: Current WordPress version, 2: Required WordPress version */
            __( 'Mitzies Jerk requires WordPress version %2$s or higher. You are running version %1$s.', 'mitzies-jerk' ),
            get_bloginfo( 'version' ),
            MITZIES_JERK_MIN_WP_VERSION
        );
    }

    if ( ! empty( $errors ) ) {
        add_action( 'admin_notices', function() use ( $errors ) {
            echo '<div class="notice notice-error"><p>';
            echo implode( '</p><p>', array_map( 'esc_html', $errors ) );
            echo '</p></div>';
        } );
        return false;
    }

    return true;
}

/**
 * Begins execution of the plugin.
 *
 * @since 1.0.0
 */
function mitzies_jerk_run() {
    if ( ! mitzies_jerk_check_requirements() ) {
        return;
    }

    // Load helper functions.
    require_once MITZIES_JERK_PATH . 'includes/functions.php';

    require_once MITZIES_JERK_PATH . 'includes/class-mitzies-jerk.php';

    $plugin = new Mitzies_Jerk();
    $plugin->run();
}

// Initialize the plugin.
add_action( 'plugins_loaded', 'mitzies_jerk_run' );

/**
 * Get the main plugin instance.
 *
 * @since 1.0.0
 * @return Mitzies_Jerk|null
 */
function mitzies_jerk() {
    global $mitzies_jerk;

    if ( ! isset( $mitzies_jerk ) ) {
        return null;
    }

    return $mitzies_jerk;
}

/**
 * Helper function to get plugin options.
 *
 * @since 1.0.0
 * @param string $option Option name.
 * @param mixed  $default Default value.
 * @return mixed
 */
function mitzies_jerk_get_option( $option, $default = '' ) {
    $options = get_option( 'mitzies_jerk_settings', array() );
    return isset( $options[ $option ] ) ? $options[ $option ] : $default;
}

/**
 * Helper function to update plugin options.
 *
 * @since 1.0.0
 * @param string $option Option name.
 * @param mixed  $value Option value.
 * @return bool
 */
function mitzies_jerk_update_option( $option, $value ) {
    $options = get_option( 'mitzies_jerk_settings', array() );
    $options[ $option ] = $value;
    return update_option( 'mitzies_jerk_settings', $options );
}

/**
 * Helper function to format price.
 *
 * @since 1.0.0
 * @param float $price Price to format.
 * @return string
 */
function mitzies_jerk_format_price( $price ) {
    $currency_symbol = mitzies_jerk_get_option( 'currency_symbol', '$' );
    $currency_position = mitzies_jerk_get_option( 'currency_position', 'left' );
    $decimal_places = mitzies_jerk_get_option( 'decimal_places', 2 );
    $thousands_sep = mitzies_jerk_get_option( 'thousands_separator', ',' );
    $decimal_sep = mitzies_jerk_get_option( 'decimal_separator', '.' );

    $formatted = number_format( (float) $price, $decimal_places, $decimal_sep, $thousands_sep );

    if ( 'left' === $currency_position ) {
        return $currency_symbol . $formatted;
    } elseif ( 'left_space' === $currency_position ) {
        return $currency_symbol . ' ' . $formatted;
    } elseif ( 'right' === $currency_position ) {
        return $formatted . $currency_symbol;
    } elseif ( 'right_space' === $currency_position ) {
        return $formatted . ' ' . $currency_symbol;
    }

    return $currency_symbol . $formatted;
}

/**
 * Helper function to get minimum pre-order hours.
 *
 * @since 1.0.0
 * @return int
 */
function mitzies_jerk_get_min_preorder_hours() {
    return (int) mitzies_jerk_get_option( 'min_preorder_hours', 24 );
}

/**
 * Helper function to validate delivery date/time.
 *
 * @since 1.0.0
 * @param string $datetime Delivery datetime string.
 * @return bool|WP_Error
 */
function mitzies_jerk_validate_delivery_datetime( $datetime ) {
    $min_hours = mitzies_jerk_get_min_preorder_hours();
    $delivery_time = strtotime( $datetime );
    $min_time = strtotime( '+' . $min_hours . ' hours' );

    if ( ! $delivery_time ) {
        return new WP_Error( 'invalid_datetime', __( 'Invalid delivery date/time format.', 'mitzies-jerk' ) );
    }

    if ( $delivery_time < $min_time ) {
        return new WP_Error(
            'too_soon',
            sprintf(
                /* translators: %d: Minimum pre-order hours */
                __( 'Delivery must be scheduled at least %d hours in advance.', 'mitzies-jerk' ),
                $min_hours
            )
        );
    }

    // Check if delivery day is allowed.
    $allowed_days = mitzies_jerk_get_option( 'delivery_days', array( 0, 1, 2, 3, 4, 5, 6 ) );
    $delivery_day = (int) date( 'w', $delivery_time );

    if ( ! in_array( $delivery_day, $allowed_days, true ) ) {
        return new WP_Error( 'day_not_allowed', __( 'Delivery is not available on the selected day.', 'mitzies-jerk' ) );
    }

    // Check delivery time slots.
    $time_slots = mitzies_jerk_get_option( 'delivery_time_slots', array() );
    if ( ! empty( $time_slots ) ) {
        $delivery_hour = (int) date( 'H', $delivery_time );
        $delivery_minute = (int) date( 'i', $delivery_time );
        $valid_slot = false;

        foreach ( $time_slots as $slot ) {
            $start = explode( ':', $slot['start'] );
            $end = explode( ':', $slot['end'] );

            $slot_start = (int) $start[0] * 60 + (int) $start[1];
            $slot_end = (int) $end[0] * 60 + (int) $end[1];
            $delivery_minutes = $delivery_hour * 60 + $delivery_minute;

            if ( $delivery_minutes >= $slot_start && $delivery_minutes <= $slot_end ) {
                $valid_slot = true;
                break;
            }
        }

        if ( ! $valid_slot ) {
            return new WP_Error( 'invalid_time_slot', __( 'Please select a valid delivery time slot.', 'mitzies-jerk' ) );
        }
    }

    return true;
}

/**
 * Helper function to get order statuses.
 *
 * @since 1.0.0
 * @return array
 */
function mitzies_jerk_get_order_statuses() {
    return array(
        'pending'    => __( 'Pending Payment', 'mitzies-jerk' ),
        'paid'       => __( 'Paid', 'mitzies-jerk' ),
        'processing' => __( 'Processing', 'mitzies-jerk' ),
        'preparing'  => __( 'Preparing', 'mitzies-jerk' ),
        'ready'      => __( 'Ready for Delivery', 'mitzies-jerk' ),
        'delivering' => __( 'Out for Delivery', 'mitzies-jerk' ),
        'completed'  => __( 'Completed', 'mitzies-jerk' ),
        'cancelled'  => __( 'Cancelled', 'mitzies-jerk' ),
        'refunded'   => __( 'Refunded', 'mitzies-jerk' ),
        'expired'    => __( 'Payment Expired', 'mitzies-jerk' ),
        'failed'     => __( 'Payment Failed', 'mitzies-jerk' ),
    );
}

/**
 * Helper function to log plugin events.
 *
 * @since 1.0.0
 * @param string $message Log message.
 * @param string $level Log level (info, warning, error).
 * @param array  $context Additional context.
 */
function mitzies_jerk_log( $message, $level = 'info', $context = array() ) {
    if ( ! mitzies_jerk_get_option( 'enable_logging', false ) ) {
        return;
    }

    $log_entry = array(
        'timestamp' => current_time( 'mysql' ),
        'level'     => $level,
        'message'   => $message,
        'context'   => $context,
    );

    $logs = get_option( 'mitzies_jerk_logs', array() );
    $logs[] = $log_entry;

    // Keep only last 1000 log entries.
    if ( count( $logs ) > 1000 ) {
        $logs = array_slice( $logs, -1000 );
    }

    update_option( 'mitzies_jerk_logs', $logs );
}
