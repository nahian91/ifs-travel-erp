<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_suppliers_delete_handler' ) ) {
    /**
     * Supplier Delete Handler
     */
    function ifs_terp_suppliers_delete_handler() {
        // Enforce strict capability checks
        if ( function_exists( 'ifs_terp_has_access' ) ) {
            if ( ! ifs_terp_has_access( array( 'admin_manager' ) ) && ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Access Denied: You do not have permission to delete supplier records.', 'ifs-travel-erp' ) );
            }
        } elseif ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access Denied: You do not have permission to delete supplier records.', 'ifs-travel-erp' ) );
        }

        global $wpdb;
        $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
        $sub_action      = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : '';

        if ( 'delete' === $sub_action && isset( $_GET['id'] ) ) {
            $del_id = absint( wp_unslash( $_GET['id'] ) );
            check_admin_referer( 'delete_supplier_' . $del_id );
            
            if ( $del_id > 0 ) {
                $supp_name = $wpdb->get_var( $wpdb->prepare( "SELECT supplier_name FROM {$table_suppliers} WHERE id = %d", $del_id ) );
                $wpdb->delete( $table_suppliers, array( 'id' => $del_id ), array( '%d' ) );
                
                if ( function_exists( 'ifs_terp_log_activity' ) ) {
                    ifs_terp_log_activity( "Deleted Supplier: #SUP-{$del_id} ({$supp_name})" );
                }
            }
            
            $redirect_url = add_query_arg(
                array(
                    'page' => 'ifs_travel_erp',
                    'tab'  => 'suppliers',
                    'msg'  => 'deleted',
                ),
                admin_url( 'admin.php' )
            );
            wp_safe_redirect( $redirect_url );
            exit;
        }
    }
}