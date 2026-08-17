<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Sub-files include
require_once plugin_dir_path( __FILE__ ) . 'hajj-umrah/hajj-tabs.php';
require_once plugin_dir_path( __FILE__ ) . 'hajj-umrah/hajj-delete.php';
require_once plugin_dir_path( __FILE__ ) . 'hajj-umrah/hajj-pilgrim-list.php';
require_once plugin_dir_path( __FILE__ ) . 'hajj-umrah/hajj-booking-add-edit.php';
require_once plugin_dir_path( __FILE__ ) . 'hajj-umrah/hajj-booking-view.php';
require_once plugin_dir_path( __FILE__ ) . 'hajj-umrah/hajj-packages.php';

/**
 * Hajj & Umrah Module Router
 */
function ifs_terp_hajj_umrah_tab() {
    $sub_action = isset( $_GET['sub'] ) ? sanitize_text_field( $_GET['sub'] ) : 'list';

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
            }
            break;

        case 'view':
            if ( function_exists( 'ifs_terp_hajj_booking_view_page' ) ) {
                ifs_terp_hajj_booking_view_page();
            }
            break;

        case 'packages':
            if ( function_exists( 'ifs_terp_hajj_packages_page' ) ) {
                ifs_terp_hajj_packages_page();
            }
            break;

        case 'delete':
            if ( function_exists( 'ifs_terp_hajj_delete_handler' ) ) {
                ifs_terp_hajj_delete_handler();
            }
            break;

        case 'list':
        default:
            if ( function_exists( 'ifs_terp_hajj_pilgrim_list_page' ) ) {
                ifs_terp_hajj_pilgrim_list_page();
            }
            break;
    }

    echo '</div>';
}