<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Sub-files include
require_once plugin_dir_path( __FILE__ ) . 'accounts/accounts-tabs.php';
require_once plugin_dir_path( __FILE__ ) . 'accounts/invoice-list.php';
require_once plugin_dir_path( __FILE__ ) . 'accounts/invoice-create.php';
require_once plugin_dir_path( __FILE__ ) . 'accounts/invoice-view.php';
require_once plugin_dir_path( __FILE__ ) . 'accounts/ledger-entry.php';

/**
 * Accounts & Financial Control Router
 */
function ifs_terp_accounts_tab() {
    $sub_action = isset( $_GET['sub'] ) ? sanitize_text_field( $_GET['sub'] ) : 'invoices';

    echo '<div class="ifs-terp-module-wrapper" style="padding: 20px; max-width: 1200px;">';
    
    // Sub-Navigation Tabs
    if ( function_exists( 'ifs_terp_accounts_render_tabs' ) ) {
        ifs_terp_accounts_render_tabs( $sub_action );
    }

    // Sub-Routing
    switch ( $sub_action ) {
        case 'create_invoice':
            if ( function_exists( 'ifs_terp_invoice_create_page' ) ) {
                ifs_terp_invoice_create_page();
            }
            break;

        case 'view_invoice':
            if ( function_exists( 'ifs_terp_invoice_view_page' ) ) {
                ifs_terp_invoice_view_page();
            }
            break;

        case 'ledger':
            if ( function_exists( 'ifs_terp_ledger_entry_page' ) ) {
                ifs_terp_ledger_entry_page();
            }
            break;

        case 'invoices':
        default:
            if ( function_exists( 'ifs_terp_invoice_list_page' ) ) {
                ifs_terp_invoice_list_page();
            }
            break;
    }

    echo '</div>';
}