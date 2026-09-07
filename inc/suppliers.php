<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Sub-files include with file_exists guards
$suppliers_sub_files = array(
    'suppliers/suppliers-tabs.php',
    'suppliers/suppliers-list.php',
    'suppliers/suppliers-add-edit.php',
    'suppliers/suppliers-topup.php',
    'suppliers/suppliers-ledger.php',
    'suppliers/suppliers-delete.php',
);

foreach ( $suppliers_sub_files as $supplier_file ) {
    $file_path = plugin_dir_path( __FILE__ ) . $supplier_file;
    if ( file_exists( $file_path ) ) {
        require_once $file_path;
    }
}

/**
 * Main Controller for Suppliers & GDS Module
 */
if ( ! function_exists( 'ifs_terp_suppliers_tab' ) ) {
    function ifs_terp_suppliers_tab() {
        $sub_action = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'list';

        echo '<div class="ifs-terp-module-wrapper" style="padding: 20px; max-width: 1400px; margin: 0 auto;">';
        
        if ( function_exists( 'ifs_terp_suppliers_render_tabs' ) ) {
            ifs_terp_suppliers_render_tabs( in_array( $sub_action, array( 'add', 'edit', 'topup', 'ledger', 'view' ), true ) ? $sub_action : 'list' );
        }

        switch ( $sub_action ) {
            case 'add':
            case 'edit':
                if ( function_exists( 'ifs_terp_suppliers_add_edit_page' ) ) {
                    ifs_terp_suppliers_add_edit_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Supplier add/edit module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;

            case 'topup':
                if ( function_exists( 'ifs_terp_suppliers_topup_page' ) ) {
                    ifs_terp_suppliers_topup_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Supplier top-up module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;

            case 'ledger':
                if ( function_exists( 'ifs_terp_suppliers_ledger_page' ) ) {
                    ifs_terp_suppliers_ledger_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Supplier ledger module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;

            case 'delete':
                if ( function_exists( 'ifs_terp_suppliers_delete_handler' ) ) {
                    ifs_terp_suppliers_delete_handler();
                } else {
                    wp_die( esc_html__( 'Supplier delete handler callback not available.', 'ifs-travel-erp' ) );
                }
                break;

            case 'list':
            default:
                if ( function_exists( 'ifs_terp_suppliers_list_page' ) ) {
                    ifs_terp_suppliers_list_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Supplier list directory module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;
        }

        echo '</div>';
    }
}