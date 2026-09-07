<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_staff_delete_handler' ) ) {
    /**
     * Staff Account Delete Handler
     */
    function ifs_terp_staff_delete_handler() {
        // Check permission / capabilities
        if ( function_exists( 'ifs_terp_has_access' ) ) {
            if ( ! ifs_terp_has_access( array( 'admin_manager' ) ) && ! current_user_can( 'delete_users' ) ) {
                wp_die( esc_html__( 'Access Denied: You do not possess the required privilege level to delete staff accounts.', 'ifs-travel-erp' ) );
            }
        } elseif ( ! current_user_can( 'delete_users' ) ) {
            wp_die( esc_html__( 'Access Denied: You do not have permission to delete staff accounts.', 'ifs-travel-erp' ) );
        }

        $id    = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
        $nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
        
        if ( $id <= 0 || ! wp_verify_nonce( $nonce, 'delete_staff_' . $id ) ) {
            wp_die( esc_html__( 'Security check failed or invalid request.', 'ifs-travel-erp' ) );
        }

        if ( $id > 0 && $id !== get_current_user_id() ) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
            
            $user_info = get_userdata( $id );
            $username  = $user_info ? $user_info->user_login : "UID-{$id}";

            wp_delete_user( $id );
            
            if ( function_exists( 'ifs_terp_log_activity' ) ) {
                ifs_terp_log_activity( "Deleted Staff User: {$username} (ID: #UID-{$id})" );
            }
        }

        $redirect_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=staff&sub=list&msg=deleted' );
        
        if ( ! headers_sent() ) {
            wp_safe_redirect( $redirect_url );
            exit;
        } else {
            echo '<script type="text/javascript">window.location.replace("' . esc_url_raw( $redirect_url ) . '");</script>';
            exit;
        }
    }
}