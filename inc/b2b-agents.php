<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Sub-files include
require_once plugin_dir_path( __FILE__ ) . 'b2b-agents/agent-tabs.php';
require_once plugin_dir_path( __FILE__ ) . 'b2b-agents/agent-delete.php';
require_once plugin_dir_path( __FILE__ ) . 'b2b-agents/agent-list.php';
require_once plugin_dir_path( __FILE__ ) . 'b2b-agents/agent-add-edit.php';
require_once plugin_dir_path( __FILE__ ) . 'b2b-agents/agent-ledger.php';

/**
 * B2B Sub-Agents Module Router
 */
function ifs_terp_b2b_agents_tab() {
    $sub_action = isset( $_GET['sub'] ) ? sanitize_text_field( $_GET['sub'] ) : 'list';

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
            }
            break;

        case 'ledger':
            if ( function_exists( 'ifs_terp_agent_ledger_page' ) ) {
                ifs_terp_agent_ledger_page();
            }
            break;

        case 'delete':
            if ( function_exists( 'ifs_terp_agent_delete_handler' ) ) {
                ifs_terp_agent_delete_handler();
            }
            break;

        case 'list':
        default:
            if ( function_exists( 'ifs_terp_agent_list_page' ) ) {
                ifs_terp_agent_list_page();
            }
            break;
    }

    echo '</div>';
}