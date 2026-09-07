<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Sub-files include with file_exists guards
$refund_sub_files = array(
    'refund-tabs.php',
    'refund-list.php',
    'refund-process-edit.php',
    'refund-view.php',
    'refund-delete.php',
);

foreach ( $refund_sub_files as $refund_file ) {
    $file_path = plugin_dir_path( __FILE__ ) . 'refund/' . $refund_file;
    if ( file_exists( $file_path ) ) {
        require_once $file_path;
    }
}

/**
 * Changes & Refunds Module Router
 */
if ( ! function_exists( 'ifs_terp_refund_reissue_tab' ) ) {
    function ifs_terp_refund_reissue_tab() {
        $sub_action = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'list';

        echo '<div class="ifs-terp-module-wrapper" style="padding: 20px; max-width: 1400px; margin: 0 auto;">';
        
        if ( function_exists( 'ifs_terp_refund_render_tabs' ) ) {
            ifs_terp_refund_render_tabs( in_array( $sub_action, array( 'process', 'edit', 'view' ), true ) ? $sub_action : 'list' );
        }

        switch ( $sub_action ) {
            case 'process':
            case 'edit':
                if ( function_exists( 'ifs_terp_refund_process_edit_page' ) ) {
                    ifs_terp_refund_process_edit_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Refund process/edit module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;

            case 'view':
                if ( function_exists( 'ifs_terp_refund_view_page' ) ) {
                    ifs_terp_refund_view_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Refund view module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;

            case 'delete':
                if ( function_exists( 'ifs_terp_refund_delete_handler' ) ) {
                    ifs_terp_refund_delete_handler();
                } else {
                    wp_die( esc_html__( 'Refund delete handler callback not available.', 'ifs-travel-erp' ) );
                }
                break;

            case 'list':
            default:
                if ( function_exists( 'ifs_terp_refund_list_page' ) ) {
                    ifs_terp_refund_list_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Refund directory list module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;
        }

        echo '</div>';
    }
}