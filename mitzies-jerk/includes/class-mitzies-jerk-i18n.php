<?php
/**
 * Define the internationalization functionality.
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
 * Define the internationalization functionality.
 */
class Mitzies_Jerk_i18n {

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'mitzies-jerk',
            false,
            dirname( MITZIES_JERK_BASENAME ) . '/languages/'
        );
    }
}
