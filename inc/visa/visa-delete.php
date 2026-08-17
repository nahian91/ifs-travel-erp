<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_visa_delete_handler() {
    global $wpdb;
    
    $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
    
    if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'delete_visa_' . $id ) ) {
        wp_die( 'Security check failed.' );
    }

    if ( $id > 0 ) {
        $table_visas = $wpdb->prefix . 'iterp_visas';
        $wpdb->delete( $table_visas, array( 'id' => $id ), array( '%d' ) );
        
        if ( function_exists('ifs_terp_log_activity') ) {
            ifs_terp_log_activity( "Deleted Visa Application Record ID: #VSA-" . $id );
        }
    }

    $redirect_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=visa&sub=list&msg=deleted' );
    echo '<script type="text/javascript">window.location.href = "' . esc_url_raw( $redirect_url ) . '";</script>';
    exit;
}