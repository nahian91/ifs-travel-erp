<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Sub-files include with file_exists guards
$tours_sub_files = array(
    'tours/tours-tabs.php',
    'tours/tours-list.php',
    'tours/tours-add-edit.php',
    'tours/tours-view.php',
    'tours/tours-delete.php',
);

foreach ( $tours_sub_files as $tour_file ) {
    $file_path = plugin_dir_path( __FILE__ ) . $tour_file;
    if ( file_exists( $file_path ) ) {
        require_once $file_path;
    }
}

/**
 * Main Controller & Router for Tour Packages Module
 */
if ( ! function_exists( 'ifs_terp_tours_tab' ) ) {
    function ifs_terp_tours_tab() {
        $sub_action = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'list';

        echo '<div class="ifs-terp-module-wrapper" style="padding: 20px; max-width: 1400px; margin: 0 auto;">';
        
        // Render the sub-navigation tabs
        if ( function_exists( 'ifs_terp_tours_render_tabs' ) ) {
            ifs_terp_tours_render_tabs( $sub_action );
        }

        // Switch case router for different sub-actions
        switch ( $sub_action ) {
            // --- Tour Bookings Routes ---
            case 'add':
            case 'edit':
                if ( function_exists( 'ifs_terp_tours_add_edit_page' ) ) {
                    ifs_terp_tours_add_edit_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Tour add/edit module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;

            case 'view':
                if ( function_exists( 'ifs_terp_tours_view_page' ) ) {
                    ifs_terp_tours_view_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Tour view module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;

            // --- Delete Handler ---
            case 'delete':
                if ( function_exists( 'ifs_terp_tour_delete_handler' ) ) {
                    ifs_terp_tour_delete_handler();
                } else {
                    wp_die( esc_html__( 'Tour delete handler callback not available.', 'ifs-travel-erp' ) );
                }
                break;

            // --- Default (All Bookings) ---
            case 'list':
            default:
                if ( function_exists( 'ifs_terp_tours_list_page' ) ) {
                    ifs_terp_tours_list_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Tour list directory module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;
        }

        echo '</div>';
    }
}