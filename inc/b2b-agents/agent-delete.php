<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_agent_delete_handler' ) ) {
    /**
     * B2B Sub-Agent Delete Request Handler
     */
    function ifs_terp_agent_delete_handler() {
        // Enforce strict capability checks
        if ( function_exists( 'ifs_terp_has_access' ) ) {
            if ( ! ifs_terp_has_access( array( 'admin_manager' ) ) && ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Access Denied: You do not have permission to delete agent records.', 'ifs-travel-erp' ) );
            }
        } elseif ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access Denied: You do not have permission to delete agent records.', 'ifs-travel-erp' ) );
        }

        global $wpdb;
        
        $id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
        
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'delete_agent_' . $id ) ) {
            wp_die( esc_html__( 'Security check failed.', 'ifs-travel-erp' ) );
        }

        if ( $id > 0 ) {
            $table_agents  = $wpdb->prefix . 'iterp_agents';
            $table_ledgers = $wpdb->prefix . 'iterp_agent_ledgers';

            // Delete associated ledger entries and agent profile
            $wpdb->delete( $table_ledgers, array( 'agent_id' => $id ), array( '%d' ) );
            $wpdb->delete( $table_agents, array( 'id' => $id ), array( '%d' ) );
            
            if ( function_exists( 'ifs_terp_log_activity' ) ) {
                ifs_terp_log_activity( "Deleted Sub-Agent ID: #AGT-{$id}" );
            }
        }

        $redirect_url = add_query_arg(
            array(
                'page' => 'ifs_travel_erp',
                'tab'  => 'b2b_agents',
                'sub'  => 'list',
                'msg'  => 'deleted',
            ),
            admin_url( 'admin.php' )
        );

        wp_safe_redirect( $redirect_url );
        exit;
    }
}