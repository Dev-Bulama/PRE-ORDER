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
define( 'MITZIES_JERK_VERSION', '1.1.0' );

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

    // Auto-migrate database tables if needed.
    mitzies_jerk_maybe_update_db();

    require_once MITZIES_JERK_PATH . 'includes/class-mitzies-jerk.php';

    $plugin = new Mitzies_Jerk();
    $plugin->run();
}

/**
 * Check and run database migrations if the DB version is outdated.
 *
 * @since 1.1.0
 */
function mitzies_jerk_maybe_update_db() {
    $current_db_version = get_option( 'mitzies_jerk_db_version', '1.0.0' );

    if ( version_compare( $current_db_version, '1.1.0', '<' ) ) {
        require_once MITZIES_JERK_PATH . 'includes/class-mitzies-jerk-activator.php';
        Mitzies_Jerk_Activator::activate();
    }
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
