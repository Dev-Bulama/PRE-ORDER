<?php
/**
 * Fired during plugin deactivation.
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
 * Fired during plugin deactivation.
 */
class Mitzies_Jerk_Deactivator {

    /**
     * Run deactivation tasks.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Clear scheduled cron events.
        self::clear_cron_events();

        // Flush rewrite rules.
        flush_rewrite_rules();

        // Clear any cached data.
        self::clear_cache();
    }

    /**
     * Clear scheduled cron events.
     *
     * @since    1.0.0
     */
    private static function clear_cron_events() {
        $cron_hooks = array(
            'mj_check_expired_orders',
            'mj_cleanup_sessions',
            'mj_send_reminder_emails',
        );

        foreach ( $cron_hooks as $hook ) {
            $timestamp = wp_next_scheduled( $hook );
            if ( $timestamp ) {
                wp_unschedule_event( $timestamp, $hook );
            }
        }
    }

    /**
     * Clear plugin cache.
     *
     * @since    1.0.0
     */
    private static function clear_cache() {
        // Clear transients.
        global $wpdb;

        $wpdb->query(
            "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '%_transient_mj_%'
            OR option_name LIKE '%_transient_timeout_mj_%'"
        );

        // Clear object cache if available.
        if ( function_exists( 'wp_cache_flush' ) ) {
            wp_cache_flush();
        }
    }
}
