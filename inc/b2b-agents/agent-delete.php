<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_agent_delete_handler() {
    global $wpdb;
    
    $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
    
    if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'delete_agent_' . $id ) ) {
        wp_die( 'Security check failed.' );
    }

    if ( $id > 0 ) {
        $table_agents  = $wpdb->prefix . 'iterp_agents';
        $table_ledgers = $wpdb->prefix . 'iterp_agent_ledgers';

        // Delete associated ledger entries and agent profile
        $wpdb->delete( $table_ledgers, array( 'agent_id' => $id ), array( '%d' ) );
        $wpdb->delete( $table_agents, array( 'id' => $id ), array( '%d' ) );
        
        if ( function_exists('ifs_terp_log_activity') ) {
            ifs_terp_log_activity( "Deleted Sub-Agent ID: #AGT-" . $id );
        }
    }

    $redirect_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=b2b_agents&sub=list&msg=deleted' );
    echo '<script type="text/javascript">window.location.href = "' . esc_url_raw( $redirect_url ) . '";</script>';
    exit;
}