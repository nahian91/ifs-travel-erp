<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Sub-files include with file_exists guards
$invoices_sub_files = array(
    'invoices/invoices-tabs.php',
    'invoices/invoice-list.php',
    'invoices/invoice-create.php',
    'invoices/invoice-view.php',
);

foreach ( $invoices_sub_files as $invoice_file ) {
    $file_path = plugin_dir_path( __FILE__ ) . $invoice_file;
    if ( file_exists( $file_path ) ) {
        require_once $file_path;
    }
}

/**
 * Invoices & Financial Control Router
 */
if ( ! function_exists( 'ifs_terp_invoices_tab' ) ) {
    function ifs_terp_invoices_tab() {
        $sub_action = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'invoices';

        echo '<div class="ifs-terp-module-wrapper" style="padding: 20px; max-width: 1200px;">';
        
        // Sub-Navigation Tabs
        if ( function_exists( 'ifs_terp_invoices_render_tabs' ) ) {
            ifs_terp_invoices_render_tabs( $sub_action );
        }

        // Sub-Routing
        switch ( $sub_action ) {
            case 'create_invoice':
                if ( function_exists( 'ifs_terp_invoice_create_page' ) ) {
                    ifs_terp_invoice_create_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Invoice create module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;

            case 'view_invoice':
                if ( function_exists( 'ifs_terp_invoice_view_page' ) ) {
                    ifs_terp_invoice_view_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Invoice view module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;

            case 'invoices':
            default:
                if ( function_exists( 'ifs_terp_invoice_list_page' ) ) {
                    ifs_terp_invoice_list_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Invoice list directory module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;
        }

        echo '</div>';
    }
}