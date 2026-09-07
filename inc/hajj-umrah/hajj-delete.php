<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_hajj_delete_handler' ) ) {
    /**
     * Hajj & Umrah Booking Delete Handler
     */
    function ifs_terp_hajj_delete_handler() {
        // Check permission / capabilities
        if ( function_exists( 'ifs_terp_has_access' ) ) {
            if ( ! ifs_terp_has_access( array( 'admin_manager' ) ) && ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Access Denied: You do not possess the required privilege level to delete Hajj & Umrah bookings.', 'ifs-travel-erp' ) );
            }
        } elseif ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access Denied: You do not have permission to delete Hajj & Umrah bookings.', 'ifs-travel-erp' ) );
        }

        global $wpdb;
        $table_bookings = $wpdb->prefix . 'iterp_hajj_bookings';
        
        $id    = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
        $nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
        
        if ( $id <= 0 || ! wp_verify_nonce( $nonce, 'delete_hajj_' . $id ) ) {
            wp_die( esc_html__( 'Security check failed or invalid request.', 'ifs-travel-erp' ) );
        }

        if ( $id > 0 ) {
            $wpdb->delete( $table_bookings, array( 'id' => $id ), array( '%d' ) );
            
            if ( function_exists( 'ifs_terp_log_activity' ) ) {
                ifs_terp_log_activity( "Deleted Hajj/Umrah Booking ID: #HB-" . $id );
            }
        }

        $redirect_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=hajj_umrah&sub=list&msg=deleted' );
        
        if ( ! headers_sent() ) {
            wp_safe_redirect( $redirect_url );
            exit;
        } else {
            echo '<script type="text/javascript">window.location.replace("' . esc_url_raw( $redirect_url ) . '");</script>';
            exit;
        }
    }
}