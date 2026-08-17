<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Sub-files include
require_once plugin_dir_path( __FILE__ ) . 'staff/staff-tabs.php';
require_once plugin_dir_path( __FILE__ ) . 'staff/staff-list.php';
require_once plugin_dir_path( __FILE__ ) . 'staff/staff-add-edit.php';
require_once plugin_dir_path( __FILE__ ) . 'staff/staff-delete.php';

/**
 * Staff & HR Module Router
 */
function ifs_terp_staff_tab() {
    $sub_action = isset( $_GET['sub'] ) ? sanitize_text_field( $_GET['sub'] ) : 'list';

    echo '<div class="ifs-terp-module-wrapper" style="padding: 20px; max-width: 1200px;">';
    
    if ( function_exists( 'ifs_terp_staff_render_tabs' ) ) {
        ifs_terp_staff_render_tabs( $sub_action );
    }

    switch ( $sub_action ) {
        case 'add':
        case 'edit':
            if ( function_exists( 'ifs_terp_staff_add_edit_page' ) ) {
                ifs_terp_staff_add_edit_page();
            }
            break;

        case 'delete':
            if ( function_exists( 'ifs_terp_staff_delete_handler' ) ) {
                ifs_terp_staff_delete_handler();
            }
            break;

        case 'list':
        default:
            if ( function_exists( 'ifs_terp_staff_list_page' ) ) {
                ifs_terp_staff_list_page();
            }
            break;
    }

    echo '</div>';
}