<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_hotels_delete_handler' ) ) {
    /**
     * Hotel Booking & Property Delete Request Handler
     */
    function ifs_terp_hotels_delete_handler() {
        // Enforce strict capability checks
        if ( function_exists( 'ifs_terp_has_access' ) ) {
            if ( ! ifs_terp_has_access( array( 'admin_manager' ) ) && ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Access Denied: You do not have permission to delete hotel records.', 'ifs-travel-erp' ) );
            }
        } elseif ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access Denied: You do not have permission to delete hotel records.', 'ifs-travel-erp' ) );
        }

        global $wpdb;
        $table_hotels = $wpdb->prefix . 'iterp_hotel_bookings';
        $table_props  = $wpdb->prefix . 'iterp_hotel_properties';
        $sub_action   = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : '';

        if ( 'delete' === $sub_action && isset( $_GET['id'] ) ) {
            $del_id = absint( wp_unslash( $_GET['id'] ) );
            check_admin_referer( 'delete_hotel_' . $del_id );
            
            if ( $del_id > 0 ) {
                $wpdb->delete( $table_hotels, array( 'id' => $del_id ), array( '%d' ) );
                if ( function_exists( 'ifs_terp_log_activity' ) ) {
                    ifs_terp_log_activity( "Deleted Hotel Booking Record #HT-{$del_id}" );
                }
            }

            $redirect_url = add_query_arg(
                array(
                    'page' => 'ifs_travel_erp',
                    'tab'  => 'hotels',
                    'msg'  => 'deleted',
                ),
                admin_url( 'admin.php' )
            );
            wp_safe_redirect( $redirect_url );
            exit;
        }

        if ( 'delete_prop' === $sub_action && isset( $_GET['prop_id'] ) ) {
            $prop_id = absint( wp_unslash( $_GET['prop_id'] ) );
            check_admin_referer( 'delete_hotel_prop_' . $prop_id );
            
            if ( $prop_id > 0 ) {
                $wpdb->delete( $table_props, array( 'id' => $prop_id ), array( '%d' ) );
                if ( function_exists( 'ifs_terp_log_activity' ) ) {
                    ifs_terp_log_activity( "Deleted Hotel Property #PRP-{$prop_id}" );
                }
            }

            $redirect_url = add_query_arg(
                array(
                    'page'    => 'ifs_travel_erp',
                    'tab'     => 'hotels',
                    'sub'     => 'properties',
                    'msg'     => 'prop_deleted',
                ),
                admin_url( 'admin.php' )
            );
            wp_safe_redirect( $redirect_url );
            exit;
        }
    }
}