<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Sub-files include
require_once plugin_dir_path( __FILE__ ) . 'settings/settings-tabs.php';
require_once plugin_dir_path( __FILE__ ) . 'settings/settings-general.php';
require_once plugin_dir_path( __FILE__ ) . 'settings/settings-api.php';
require_once plugin_dir_path( __FILE__ ) . 'settings/settings-audit-logs.php';

/**
 * Settings & Configuration Module Router
 */
function ifs_terp_settings_tab() {
    $sub_action = isset( $_GET['sub'] ) ? sanitize_text_field( $_GET['sub'] ) : 'general';

    echo '<div class="ifs-terp-module-wrapper" style="padding: 20px; max-width: 1200px;">';
    
    // Sub-Navigation Tabs
    if ( function_exists( 'ifs_terp_settings_render_tabs' ) ) {
        ifs_terp_settings_render_tabs( $sub_action );
    }

    // Sub-Routing
    switch ( $sub_action ) {
        case 'api':
            if ( function_exists( 'ifs_terp_settings_api_page' ) ) {
                ifs_terp_settings_api_page();
            }
            break;

        case 'audit_logs':
            if ( function_exists( 'ifs_terp_settings_audit_logs_page' ) ) {
                ifs_terp_settings_audit_logs_page();
            }
            break;

        case 'general':
        default:
            if ( function_exists( 'ifs_terp_settings_general_page' ) ) {
                ifs_terp_settings_general_page();
            }
            break;
    }

    echo '</div>';
}