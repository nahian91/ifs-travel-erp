<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Sub-files include with file_exists guards
$hajj_umrah_sub_files = array(
    'hajj-tabs.php',
    'hajj-delete.php',
    'hajj-pilgrim-list.php',
    'hajj-booking-add-edit.php',
    'hajj-booking-view.php',
);

foreach ( $hajj_umrah_sub_files as $hajj_file ) {
    $file_path = plugin_dir_path( __FILE__ ) . 'hajj-umrah/' . $hajj_file;
    if ( file_exists( $file_path ) ) {
        require_once $file_path;
    }
}

/**
 * Hajj & Umrah Module Router
 */
if ( ! function_exists( 'ifs_terp_hajj_umrah_tab' ) ) {
    function ifs_terp_hajj_umrah_tab() {
        $sub_action = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'list';

        echo '<div class="ifs-terp-module-wrapper" style="padding: 20px; max-width: 1200px;">';
        
        // Sub-Navigation Tabs
        if ( function_exists( 'ifs_terp_hajj_render_tabs' ) ) {
            ifs_terp_hajj_render_tabs( $sub_action );
        }

        // Sub-Routing
        switch ( $sub_action ) {
            case 'add':
            case 'edit':
                if ( function_exists( 'ifs_terp_hajj_booking_add_edit_page' ) ) {
                    ifs_terp_hajj_booking_add_edit_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Hajj booking add/edit module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;

            case 'view':
                if ( function_exists( 'ifs_terp_hajj_booking_view_page' ) ) {
                    ifs_terp_hajj_booking_view_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Hajj booking view module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;

            case 'packages':
                if ( function_exists( 'ifs_terp_hajj_packages_page' ) ) {
                    ifs_terp_packages_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Hajj packages module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;

            case 'delete':
                if ( function_exists( 'ifs_terp_hajj_delete_handler' ) ) {
                    ifs_terp_hajj_delete_handler();
                } else {
                    wp_die( esc_html__( 'Hajj delete handler callback not available.', 'ifs-travel-erp' ) );
                }
                break;

            case 'list':
            default:
                if ( function_exists( 'ifs_terp_hajj_pilgrim_list_page' ) ) {
                    ifs_terp_hajj_pilgrim_list_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Hajj pilgrim list directory module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;
        }

        echo '</div>';
    }
}