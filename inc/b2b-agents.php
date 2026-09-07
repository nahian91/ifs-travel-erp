<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Sub-files include with file_exists guards
$agent_sub_files = array(
    'agent-tabs.php',
    'agent-delete.php',
    'agent-list.php',
    'agent-add-edit.php',
    'agent-ledger.php',
);

foreach ( $agent_sub_files as $agent_file ) {
    $file_path = plugin_dir_path( __FILE__ ) . 'b2b-agents/' . $agent_file;
    if ( file_exists( $file_path ) ) {
        require_once $file_path;
    }
}

/**
 * B2B Sub-Agents Module Router
 */
if ( ! function_exists( 'ifs_terp_b2b_agents_tab' ) ) {
    function ifs_terp_b2b_agents_tab() {
        $sub_action = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'list';

        echo '<div class="ifs-terp-module-wrapper" style="padding: 20px; max-width: 1200px;">';
        
        // Sub-Navigation Tabs
        if ( function_exists( 'ifs_terp_agent_render_tabs' ) ) {
            ifs_terp_agent_render_tabs( $sub_action );
        }

        // Sub-Routing
        switch ( $sub_action ) {
            case 'add':
            case 'edit':
                if ( function_exists( 'ifs_terp_agent_add_edit_page' ) ) {
                    ifs_terp_agent_add_edit_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Agent add/edit module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;

            case 'ledger':
                if ( function_exists( 'ifs_terp_agent_ledger_page' ) ) {
                    ifs_terp_agent_ledger_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Agent ledger module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;

            case 'delete':
                if ( function_exists( 'ifs_terp_agent_delete_handler' ) ) {
                    ifs_terp_agent_delete_handler();
                } else {
                    wp_die( esc_html__( 'Agent delete handler callback not available.', 'ifs-travel-erp' ) );
                }
                break;

            case 'list':
            default:
                if ( function_exists( 'ifs_terp_agent_list_page' ) ) {
                    ifs_terp_agent_list_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Agent list directory module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;
        }

        echo '</div>';
    }
}