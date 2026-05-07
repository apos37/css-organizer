<?php
/**
 * Uninstall handler for CSS Organizer
 *
 * Deletes all plugin options
 */

// Exit if not called by WP uninstall routine
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Runs all uninstall cleanup logic in an isolated scope.
 */
function css_organizer_run_uninstall() {

    /**
     * Check the cleanup setting before proceeding.
     */
    $css_organizer_cleanup_key = 'css-organizer-uninstall-cleanup';
    if ( ! (bool) get_option( $css_organizer_cleanup_key, false ) ) {
        return;
    }

    global $wpdb;

    $css_organizer_opt_prefix = 'css-organizer-';

    /**
     * Clean up Options
     */
    $css_organizer_options = $wpdb->get_col( $wpdb->prepare( // phpcs:ignore
        "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
        $wpdb->esc_like( $css_organizer_opt_prefix ) . '%'
    ) );

    if ( ! empty( $css_organizer_options ) ) {
        foreach ( $css_organizer_options as $css_organizer_option_name ) {
            delete_option( $css_organizer_option_name );
        }
    }
}

css_organizer_run_uninstall();

// Finished.