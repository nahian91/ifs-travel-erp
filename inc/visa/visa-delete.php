<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Visa Record Delete Handler
 */
function ifs_terp_visa_delete_handler() {
    global $wpdb;

    // Check permission / capabilities
    if ( function_exists( 'ifs_terp_has_access' ) ) {
        if ( ! ifs_terp_has_access( array( 'admin_manager', 'visa_officer' ) ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access Denied: You do not possess the required privilege level to delete this record.', 'ifs-travel-erp' ) );
        }
    } elseif ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Access Denied: You do not have permission to delete this record.', 'ifs-travel-erp' ) );
    }

    $id    = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
    $nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

    if ( ! wp_verify_nonce( $nonce, 'delete_visa_' . $id ) ) {
        wp_die( esc_html__( 'Security check failed.', 'ifs-travel-erp' ) );
    }

    if ( $id > 0 ) {
        $table_visas = $wpdb->prefix . 'iterp_visa_applications';
        $wpdb->delete( $table_visas, array( 'id' => $id ), array( '%d' ) );

        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            ifs_terp_log_activity( 'Deleted Visa Application Record ID: #VSA-' . $id );
        }
    }

    $redirect_url = add_query_arg(
        array(
            'page' => 'ifs_travel_erp',
            'tab'  => 'visa',
            'sub'  => 'list',
            'msg'  => 'deleted',
        ),
        admin_url( 'admin.php' )
    );

    if ( ! headers_sent() ) {
        wp_safe_redirect( $redirect_url );
    } else {
        echo '<script type="text/javascript">window.location.replace("' . esc_url_raw( $redirect_url ) . '");</script>';
    }
    exit;
}