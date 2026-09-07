<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Sub-files include with file_exists guards
$ticketing_sub_files = array(
    'ticketing/ticket-tabs.php',
    'ticketing/ticket-delete.php',
    'ticketing/ticket-list.php',
    'ticketing/ticket-add-edit.php',
    'ticketing/ticket-view.php',
);

foreach ( $ticketing_sub_files as $ticket_file ) {
    $file_path = plugin_dir_path( __FILE__ ) . $ticket_file;
    if ( file_exists( $file_path ) ) {
        require_once $file_path;
    }
}

/**
 * Air Ticketing Module Router
 */
if ( ! function_exists( 'ifs_terp_ticketing_tab' ) ) {
    function ifs_terp_ticketing_tab() {
        $sub_action = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'list';

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
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Ticketing add/edit module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;

            case 'view':
                if ( function_exists( 'ifs_terp_ticket_view_page' ) ) {
                    ifs_terp_ticket_view_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Ticketing view module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;

            case 'delete':
                if ( function_exists( 'ifs_terp_ticket_delete_handler' ) ) {
                    ifs_terp_ticket_delete_handler();
                } else {
                    wp_die( esc_html__( 'Ticketing delete handler callback not available.', 'ifs-travel-erp' ) );
                }
                break;

            case 'list':
            default:
                if ( function_exists( 'ifs_terp_ticket_list_page' ) ) {
                    ifs_terp_ticket_list_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Ticketing list directory module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;
        }

        echo '</div>';
    }
}