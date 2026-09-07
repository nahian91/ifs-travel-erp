<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_settings_hotel_delete_handler' ) ) {
    /**
     * Hotel Property Configuration Delete Handler
     */
    function ifs_terp_settings_hotel_delete_handler() {
        global $wpdb;

        // Check permission / capabilities
        if ( function_exists( 'ifs_terp_has_access' ) ) {
            if ( ! ifs_terp_has_access( array( 'admin_manager' ) ) && ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Access Denied: You do not possess the required privilege level to delete this hotel property record.', 'ifs-travel-erp' ) );
            }
        } elseif ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access Denied: You do not have permission to delete this hotel property record.', 'ifs-travel-erp' ) );
        }

        $table_hotels = $wpdb->prefix . 'iterp_hotel_properties';
        $del_id       = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
        $nonce        = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

        if ( $del_id > 0 && wp_verify_nonce( $nonce, 'delete_hotel_prop_' . $del_id ) ) {
            $property_name = $wpdb->get_var( $wpdb->prepare( "SELECT property_name FROM {$table_hotels} WHERE id = %d", $del_id ) );
            $wpdb->delete( $table_hotels, array( 'id' => $del_id ), array( '%d' ) );

            if ( function_exists( 'ifs_terp_log_activity' ) ) {
                ifs_terp_log_activity( "Deleted Hotel Property Record #{$del_id} ({$property_name})" );
            }

            $redirect_url = add_query_arg(
                array(
                    'page'      => 'ifs_travel_erp',
                    'tab'       => 'settings',
                    'sub'       => 'hotels_prop',
                    'hotel_sub' => 'all',
                    'msg'       => 'deleted',
                ),
                admin_url( 'admin.php' )
            );

            if ( ! headers_sent() ) {
                wp_safe_redirect( $redirect_url );
            } else {
                echo '<script type="text/javascript">window.location.replace("' . esc_url_raw( $redirect_url ) . '");</script>';
            }
            exit;
        } else {
            wp_die( esc_html__( 'Security token verification failed or invalid request.', 'ifs-travel-erp' ) );
        }
    }
}