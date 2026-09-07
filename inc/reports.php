<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Sub-files include with file_exists guards
$report_sub_files = array(
    'reports/report-tabs.php',
    'reports/report-sales.php',
    'reports/report-profit-loss.php',
    'reports/report-agent-dues.php',
);

foreach ( $report_sub_files as $report_file ) {
    $file_path = plugin_dir_path( __FILE__ ) . $report_file;
    if ( file_exists( $file_path ) ) {
        require_once $file_path;
    }
}

/**
 * Reports & Analytics Module Router
 */
if ( ! function_exists( 'ifs_terp_reports_tab' ) ) {
    function ifs_terp_reports_tab() {
        $sub_action = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'sales';

        echo '<div class="ifs-terp-module-wrapper" style="padding: 20px; max-width: 1200px;">';
        
        // Sub-Navigation Tabs
        if ( function_exists( 'ifs_terp_report_render_tabs' ) ) {
            ifs_terp_report_render_tabs( $sub_action );
        }

        // Sub-Routing
        switch ( $sub_action ) {
            case 'profit_loss':
                if ( function_exists( 'ifs_terp_report_profit_loss_page' ) ) {
                    ifs_terp_report_profit_loss_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Profit & loss report module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;

            case 'agent_dues':
                if ( function_exists( 'ifs_terp_report_agent_dues_page' ) ) {
                    ifs_terp_report_agent_dues_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Agent dues report module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;

            case 'sales':
            default:
                if ( function_exists( 'ifs_terp_report_sales_page' ) ) {
                    ifs_terp_report_sales_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Sales report module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;
        }

        echo '</div>';
    }
}