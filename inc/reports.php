<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Sub-files include
require_once plugin_dir_path( __FILE__ ) . 'reports/report-tabs.php';
require_once plugin_dir_path( __FILE__ ) . 'reports/report-sales.php';
require_once plugin_dir_path( __FILE__ ) . 'reports/report-profit-loss.php';
require_once plugin_dir_path( __FILE__ ) . 'reports/report-agent-dues.php';

/**
 * Reports & Analytics Module Router
 */
function ifs_terp_reports_tab() {
    $sub_action = isset( $_GET['sub'] ) ? sanitize_text_field( $_GET['sub'] ) : 'sales';

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
            }
            break;

        case 'agent_dues':
            if ( function_exists( 'ifs_terp_report_agent_dues_page' ) ) {
                ifs_terp_report_agent_dues_page();
            }
            break;

        case 'sales':
        default:
            if ( function_exists( 'ifs_terp_report_sales_page' ) ) {
                ifs_terp_report_sales_page();
            }
            break;
    }

    echo '</div>';
}