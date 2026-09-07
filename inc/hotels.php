<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Sub-files include with file_exists guards
$hotels_sub_files = array(
    'hotels-tabs.php',
    'hotels-list.php',
    'hotels-add-edit.php',
    'hotels-view.php',
    'hotels-delete.php',
);

foreach ( $hotels_sub_files as $hotel_file ) {
    $file_path = plugin_dir_path( __FILE__ ) . 'hotels/' . $hotel_file;
    if ( file_exists( $file_path ) ) {
        require_once $file_path;
    }
}

/**
 * Hotel Bookings Module Router
 */
if ( ! function_exists( 'ifs_terp_hotels_tab' ) ) {
    function ifs_terp_hotels_tab() {
        $sub_action = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'list';

        echo '<div class="ifs-terp-module-wrapper" style="padding: 20px; max-width: 1400px; margin: 0 auto;">';
        
        if ( function_exists( 'ifs_terp_hotels_render_tabs' ) ) {
            ifs_terp_hotels_render_tabs( $sub_action );
        }

        switch ( $sub_action ) {
            case 'add':
            case 'edit':
                if ( function_exists( 'ifs_terp_hotels_add_edit_page' ) ) {
                    ifs_terp_hotels_add_edit_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Hotel add/edit module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;

            case 'view':
                if ( function_exists( 'ifs_terp_hotels_view_page' ) ) {
                    ifs_terp_hotels_view_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Hotel view module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;

            case 'delete':
                if ( function_exists( 'ifs_terp_hotels_delete_handler' ) ) {
                    ifs_terp_hotels_delete_handler();
                } else {
                    wp_die( esc_html__( 'Hotel delete handler callback not available.', 'ifs-travel-erp' ) );
                }
                break;

            case 'list':
            default:
                if ( function_exists( 'ifs_terp_hotels_list_page' ) ) {
                    ifs_terp_hotels_list_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Hotel list directory module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;
        }

        echo '</div>';
    }
}