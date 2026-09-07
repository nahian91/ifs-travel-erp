<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Sub-files include with file_exists guards
$staff_sub_files = array(
    'staff/staff-tabs.php',
    'staff/staff-list.php',
    'staff/staff-add-edit.php',
    'staff/staff-delete.php',
);

foreach ( $staff_sub_files as $staff_file ) {
    $file_path = plugin_dir_path( __FILE__ ) . $staff_file;
    if ( file_exists( $file_path ) ) {
        require_once $file_path;
    }
}

/**
 * Staff & HR Module Router
 */
if ( ! function_exists( 'ifs_terp_staff_tab' ) ) {
    function ifs_terp_staff_tab() {
        $sub_action = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'list';

        echo '<div class="ifs-terp-module-wrapper" style="padding: 20px; max-width: 1200px;">';
        
        if ( function_exists( 'ifs_terp_staff_render_tabs' ) ) {
            ifs_terp_staff_render_tabs( $sub_action );
        }

        switch ( $sub_action ) {
            case 'add':
            case 'edit':
                if ( function_exists( 'ifs_terp_staff_add_edit_page' ) ) {
                    ifs_terp_staff_add_edit_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Staff add/edit module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;

            case 'delete':
                if ( function_exists( 'ifs_terp_staff_delete_handler' ) ) {
                    ifs_terp_staff_delete_handler();
                } else {
                    wp_die( esc_html__( 'Staff delete handler callback not available.', 'ifs-travel-erp' ) );
                }
                break;

            case 'list':
            default:
                if ( function_exists( 'ifs_terp_staff_list_page' ) ) {
                    ifs_terp_staff_list_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Staff list directory module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;
        }

        echo '</div>';
    }
}