<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_staff_delete_handler() {
    $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
    
    if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'delete_staff_' . $id ) ) {
        wp_die( 'Security check failed.' );
    }

    if ( $id > 0 && $id !== get_current_user_id() ) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user( $id );
        
        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            ifs_terp_log_activity( "Deleted Staff User ID: #UID-" . $id );
        }
    }

    $redirect_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=staff&sub=list&msg=deleted' );
    echo '<script type="text/javascript">window.location.href = "' . esc_url_raw( $redirect_url ) . '";</script>';
    exit;
}