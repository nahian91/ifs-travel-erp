<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_tour_delete_handler' ) ) {
    /**
     * Tour Booking & Package Plan Delete Handler
     */
    function ifs_terp_tour_delete_handler() {
        // Check permissions and capabilities
        if ( function_exists( 'ifs_terp_has_access' ) ) {
            if ( ! ifs_terp_has_access( array( 'admin_manager' ) ) && ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Access Denied: You do not have permission to delete tour records.', 'ifs-travel-erp' ) );
            }
        } elseif ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access Denied: You do not have permission to delete tour records.', 'ifs-travel-erp' ) );
        }

        global $wpdb;
        $table_tours = $wpdb->prefix . 'iterp_tours';
        $table_plans = $wpdb->prefix . 'iterp_tour_packages';
        $sub_action  = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : '';

        if ( 'delete' === $sub_action && isset( $_GET['id'] ) ) {
            $del_id = absint( wp_unslash( $_GET['id'] ) );
            check_admin_referer( 'delete_tour_' . $del_id );
            
            if ( $del_id > 0 ) {
                $wpdb->delete( $table_tours, array( 'id' => $del_id ), array( '%d' ) );
                if ( function_exists( 'ifs_terp_log_activity' ) ) {
                    ifs_terp_log_activity( "Deleted Tour Booking #TR-{$del_id}" );
                }
            }

            $redirect_url = add_query_arg(
                array(
                    'page' => 'ifs_travel_erp',
                    'tab'  => 'tours',
                    'msg'  => 'deleted',
                ),
                admin_url( 'admin.php' )
            );
            wp_safe_redirect( $redirect_url );
            exit;
        }

        if ( 'delete_plan' === $sub_action && isset( $_GET['plan_id'] ) ) {
            $plan_id = absint( wp_unslash( $_GET['plan_id'] ) );
            check_admin_referer( 'delete_tour_plan_' . $plan_id );

            if ( $plan_id > 0 ) {
                $wpdb->delete( $table_plans, array( 'id' => $plan_id ), array( '%d' ) );
                if ( function_exists( 'ifs_terp_log_activity' ) ) {
                    ifs_terp_log_activity( "Deleted Tour Package Plan #PLN-{$plan_id}" );
                }
            }

            $redirect_url = add_query_arg(
                array(
                    'page' => 'ifs_travel_erp',
                    'tab'  => 'tours',
                    'sub'  => 'plans',
                    'msg'  => 'plan_deleted',
                ),
                admin_url( 'admin.php' )
            );
            wp_safe_redirect( $redirect_url );
            exit;
        }
    }
}