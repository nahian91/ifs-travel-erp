<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Sub-files include with file_exists guards
$accounts_sub_files = array(
    'accounts-tabs.php',
    'accounts-income.php',
    'accounts-expense.php',
    'accounts-reports.php',
);

foreach ( $accounts_sub_files as $accounts_file ) {
    $file_path = plugin_dir_path( __FILE__ ) . 'accounts/' . $accounts_file;
    if ( file_exists( $file_path ) ) {
        require_once $file_path;
    }
}

/**
 * Accounts & Financial Control Router
 */
if ( ! function_exists( 'ifs_terp_accounts_tab' ) ) {
    function ifs_terp_accounts_tab() {
        $sub_action = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'income';

        echo '<div class="ifs-terp-module-wrapper" style="padding: 20px; max-width: 1400px; margin: 0 auto;">';
        
        // Render Sub-Navigation Tabs
        if ( function_exists( 'ifs_terp_accounts_render_tabs' ) ) {
            ifs_terp_accounts_render_tabs( $sub_action );
        }

        // Sub-Routing Switch
        switch ( $sub_action ) {
            case 'expense':
                if ( function_exists( 'ifs_terp_accounts_expense_page' ) ) {
                    ifs_terp_accounts_expense_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Accounts expense module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;

            case 'reports':
                if ( function_exists( 'ifs_terp_accounts_reports_page' ) ) {
                    ifs_terp_accounts_reports_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Accounts reports module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;

            case 'income':
            default:
                if ( function_exists( 'ifs_terp_accounts_income_page' ) ) {
                    ifs_terp_accounts_income_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Accounts income module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;
        }

        echo '</div>';
    }
}