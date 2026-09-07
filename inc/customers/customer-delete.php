<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Customer Delete Handler
 */
function ifs_terp_customer_delete_handler() {
    global $wpdb;
    
    // Check permission / capabilities
    if ( function_exists( 'ifs_terp_has_access' ) ) {
        if ( ! ifs_terp_has_access( array( 'admin_manager' ) ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access Denied: You do not possess the required privilege level to delete this record.', 'ifs-travel-erp' ) );
        }
    } elseif ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Access Denied: You do not have permission to delete this record.', 'ifs-travel-erp' ) );
    }

    $id    = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
    $nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
    
    if ( ! wp_verify_nonce( $nonce, 'delete_customer_' . $id ) ) {
        wp_die( esc_html__( 'Security check failed.', 'ifs-travel-erp' ) );
    }

    if ( $id > 0 ) {
        $table_name = $wpdb->prefix . 'iterp_customers';
        $wpdb->delete( $table_name, array( 'id' => $id ), array( '%d' ) );
        
        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            ifs_terp_log_activity( 'Deleted Customer ID: #CUS-' . $id );
        }
    }

    $redirect_url = add_query_arg(
        array(
            'page' => 'ifs_travel_erp',
            'tab'  => 'customers',
            'sub'  => 'list',
            'msg'  => 'deleted',
        ),
        admin_url( 'admin.php' )
    );

    if ( ! headers_sent() ) {
        wp_safe_redirect( $redirect_url );
    } else {
        echo '<script type="text/javascript">window.location.href = "' . esc_url_raw( $redirect_url ) . '";</script>';
    }
    exit;
}