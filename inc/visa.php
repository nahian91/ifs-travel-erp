<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Sub-files include with file_exists guards
$visa_sub_files = array(
    'visa/visa-tabs.php',
    'visa/visa-delete.php',
    'visa/visa-list.php',
    'visa/visa-add-edit.php',
    'visa/visa-view.php',
);

foreach ( $visa_sub_files as $visa_file ) {
    $file_path = plugin_dir_path( __FILE__ ) . $visa_file;
    if ( file_exists( $file_path ) ) {
        require_once $file_path;
    }
}

/**
 * Visa Processing Module Router
 */
if ( ! function_exists( 'ifs_terp_visa_tab' ) ) {
    function ifs_terp_visa_tab() {
        $sub_action = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'list';

        echo '<div class="ifs-terp-module-wrapper" style="padding: 20px; max-width: 1200px;">';
        
        // Sub-Navigation Tabs
        if ( function_exists( 'ifs_terp_visa_render_tabs' ) ) {
            ifs_terp_visa_render_tabs( $sub_action );
        }

        // Sub-Routing
        switch ( $sub_action ) {
            case 'add':
            case 'edit':
                if ( function_exists( 'ifs_terp_visa_add_edit_page' ) ) {
                    ifs_terp_visa_add_edit_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Visa add/edit module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;

            case 'view':
                if ( function_exists( 'ifs_terp_visa_view_page' ) ) {
                    ifs_terp_visa_view_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Visa view module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;

            case 'delete':
                if ( function_exists( 'ifs_terp_visa_delete_handler' ) ) {
                    ifs_terp_visa_delete_handler();
                } else {
                    wp_die( esc_html__( 'Visa delete handler callback not available.', 'ifs-travel-erp' ) );
                }
                break;

            case 'list':
            default:
                if ( function_exists( 'ifs_terp_visa_list_page' ) ) {
                    ifs_terp_visa_list_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Visa list directory module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;
        }

        echo '</div>';
    }
}