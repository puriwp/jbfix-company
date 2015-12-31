<?php

/**
 * @link              http://minimalthemes.net/
 * @since             1.0.0
 * @package           Jbfix_Company
 */

class Jbfix_Company_Activator {

    function __construct() {
        add_action( 'admin_init', array( $this, 'check_version' ) );

        // Don't run anything else in the plugin, if we're on an incompatible WordPress version
        if ( ! self::compatible_version() ) {
            return;
        }
    }

    // The primary sanity check, automatically disable the plugin on activation if it doesn't
    // meet minimum requirements.
    static function activation_check() {
        if ( ! self::compatible_version() ) {
            wp_die( '<strong>ERROR!</strong> Job Board version 2.5.2 is not installed or activated!' );
        }
    }

    // The backup sanity check, in case the plugin is activated in a weird way,
    // or the versions change after activation.
    function check_version() {
        if ( ! self::compatible_version() ) {
			add_action( 'admin_notices', array( $this, 'disabled_notice' ) );
			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}
        }
    }

    function disabled_notice() {
       echo '<strong>ERROR!</strong> Job Board version 2.5.2 is not installed or activated!';
    }

    static function compatible_version() {

			$return = false;

			if ( defined( 'JBOARD_VERSION' ) && version_compare( JBOARD_VERSION, '2.5.2', '>=' ) ) {
				$return = true;
			}

			if ( version_compare( $GLOBALS['wp_version'], '4.0', '<' ) ) {
				$return = false;
			}
			return $return;
    }
}
