<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Sub-files include
require_once plugin_dir_path( __FILE__ ) . 'ticketing/ticket-tabs.php';
require_once plugin_dir_path( __FILE__ ) . 'ticketing/ticket-delete.php';
require_once plugin_dir_path( __FILE__ ) . 'ticketing/ticket-list.php';
require_once plugin_dir_path( __FILE__ ) . 'ticketing/ticket-add-edit.php';
require_once plugin_dir_path( __FILE__ ) . 'ticketing/ticket-view.php';

/**
 * Air Ticketing Module Router
 */
function ifs_terp_ticketing_tab() {
    $sub_action = isset( $_GET['sub'] ) ? sanitize_text_field( $_GET['sub'] ) : 'list';

    echo '<div class="ifs-terp-module-wrapper" style="padding: 20px; max-width: 1200px;">';
    
    // Sub-Navigation Tabs Render
    if ( function_exists( 'ifs_terp_ticketing_render_tabs' ) ) {
        ifs_terp_ticketing_render_tabs( $sub_action );
    }

    // Specific Action Routing
    switch ( $sub_action ) {
        case 'add':
        case 'edit':
            if ( function_exists( 'ifs_terp_ticket_add_edit_page' ) ) {
                ifs_terp_ticket_add_edit_page();
            }
            break;

        case 'view':
            if ( function_exists( 'ifs_terp_ticket_view_page' ) ) {
                ifs_terp_ticket_view_page();
            }
            break;

        case 'delete':
            if ( function_exists( 'ifs_terp_ticket_delete_handler' ) ) {
                ifs_terp_ticket_delete_handler();
            }
            break;

        case 'list':
        default:
            if ( function_exists( 'ifs_terp_ticket_list_page' ) ) {
                ifs_terp_ticket_list_page();
            }
            break;
    }

    echo '</div>';
}