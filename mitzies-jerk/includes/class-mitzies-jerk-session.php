<?php
/**
 * Session handler class.
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
 * Session handler class.
 */
class Mitzies_Jerk_Session {

    /**
     * Session ID.
     *
     * @var string
     */
    private $session_id;

    /**
     * Session data.
     *
     * @var array
     */
    private $data = array();

    /**
     * Session expiry.
     *
     * @var int
     */
    private $session_expiry;

    /**
     * Cookie name.
     *
     * @var string
     */
    private $cookie_name = 'mj_session';

    /**
     * Has session been changed.
     *
     * @var bool
     */
    private $dirty = false;

    /**
     * Initialize the session.
     *
     * @since    1.0.0
     */
    public function init() {
        $this->session_expiry = time() + ( 48 * 60 * 60 ); // 48 hours.

        $cookie = $this->get_session_cookie();

        if ( $cookie ) {
            $this->session_id = $cookie[0];
            $this->session_expiry = $cookie[1];

            // Update expiry if nearing expiration.
            if ( time() > $this->session_expiry - ( 24 * 60 * 60 ) ) {
                $this->session_expiry = time() + ( 48 * 60 * 60 );
                $this->dirty = true;
            }

            $this->data = $this->get_session_data();
        } else {
            $this->session_id = $this->generate_session_id();
            $this->set_session_cookie();
        }

        // Save session on shutdown.
        add_action( 'shutdown', array( $this, 'save_data' ), 20 );
    }

    /**
     * Generate a unique session ID.
     *
     * @since    1.0.0
     * @return   string
     */
    private function generate_session_id() {
        if ( is_user_logged_in() ) {
            return 'user_' . get_current_user_id();
        }

        return 'guest_' . wp_generate_password( 32, false );
    }

    /**
     * Get session cookie.
     *
     * @since    1.0.0
     * @return   array|false
     */
    private function get_session_cookie() {
        if ( empty( $_COOKIE[ $this->cookie_name ] ) ) {
            return false;
        }

        $cookie_value = sanitize_text_field( wp_unslash( $_COOKIE[ $this->cookie_name ] ) );
        $parts = explode( '||', $cookie_value );

        if ( count( $parts ) !== 3 ) {
            return false;
        }

        list( $session_id, $session_expiry, $cookie_hash ) = $parts;

        // Validate hash.
        $to_hash = $session_id . '|' . $session_expiry;
        $hash = hash_hmac( 'sha256', $to_hash, wp_hash( $to_hash ) );

        if ( ! hash_equals( $hash, $cookie_hash ) ) {
            return false;
        }

        return array( $session_id, (int) $session_expiry );
    }

    /**
     * Set session cookie.
     *
     * @since    1.0.0
     */
    private function set_session_cookie() {
        $to_hash = $this->session_id . '|' . $this->session_expiry;
        $cookie_hash = hash_hmac( 'sha256', $to_hash, wp_hash( $to_hash ) );
        $cookie_value = $this->session_id . '||' . $this->session_expiry . '||' . $cookie_hash;

        if ( ! headers_sent() ) {
            setcookie(
                $this->cookie_name,
                $cookie_value,
                $this->session_expiry,
                COOKIEPATH ? COOKIEPATH : '/',
                COOKIE_DOMAIN,
                is_ssl(),
                true
            );
        }
    }

    /**
     * Get session data from database.
     *
     * @since    1.0.0
     * @return   array
     */
    private function get_session_data() {
        global $wpdb;
        $table_name = Mitzies_Jerk_Database::get_table( 'sessions' );

        $value = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT session_value FROM `$table_name` WHERE session_id = %s",
                $this->session_id
            )
        );

        if ( $value ) {
            return maybe_unserialize( $value );
        }

        return array();
    }

    /**
     * Save session data to database.
     *
     * @since    1.0.0
     */
    public function save_data() {
        if ( ! $this->dirty && empty( $this->data ) ) {
            return;
        }

        global $wpdb;
        $table_name = Mitzies_Jerk_Database::get_table( 'sessions' );

        $wpdb->replace(
            $table_name,
            array(
                'session_id'     => $this->session_id,
                'session_key'    => $this->session_id,
                'session_value'  => maybe_serialize( $this->data ),
                'session_expiry' => $this->session_expiry,
            ),
            array( '%s', '%s', '%s', '%d' )
        );

        // Update cookie if needed.
        if ( $this->dirty ) {
            $this->set_session_cookie();
        }
    }

    /**
     * Get a session value.
     *
     * @since    1.0.0
     * @param    string $key     Session key.
     * @param    mixed  $default Default value.
     * @return   mixed
     */
    public function get( $key, $default = null ) {
        return isset( $this->data[ $key ] ) ? $this->data[ $key ] : $default;
    }

    /**
     * Set a session value.
     *
     * @since    1.0.0
     * @param    string $key   Session key.
     * @param    mixed  $value Session value.
     */
    public function set( $key, $value ) {
        $this->data[ $key ] = $value;
        $this->dirty = true;

        // Save immediately for AJAX requests to ensure data persists.
        if ( wp_doing_ajax() ) {
            $this->save_data();
        }
    }

    /**
     * Remove a session value.
     *
     * @since    1.0.0
     * @param    string $key Session key.
     */
    public function remove( $key ) {
        if ( isset( $this->data[ $key ] ) ) {
            unset( $this->data[ $key ] );
            $this->dirty = true;
        }
    }

    /**
     * Clear all session data.
     *
     * @since    1.0.0
     */
    public function clear() {
        $this->data = array();
        $this->dirty = true;
    }

    /**
     * Destroy the session.
     *
     * @since    1.0.0
     */
    public function destroy() {
        global $wpdb;
        $table_name = Mitzies_Jerk_Database::get_table( 'sessions' );

        // Delete from database.
        $wpdb->delete(
            $table_name,
            array( 'session_id' => $this->session_id ),
            array( '%s' )
        );

        // Clear cookie.
        if ( ! headers_sent() ) {
            setcookie(
                $this->cookie_name,
                '',
                time() - 3600,
                COOKIEPATH ? COOKIEPATH : '/',
                COOKIE_DOMAIN
            );
        }

        $this->data = array();
        $this->session_id = $this->generate_session_id();
    }

    /**
     * Get the session ID.
     *
     * @since    1.0.0
     * @return   string
     */
    public function get_session_id() {
        return $this->session_id;
    }

    /**
     * Check if session has data.
     *
     * @since    1.0.0
     * @return   bool
     */
    public function has_data() {
        return ! empty( $this->data );
    }
}
