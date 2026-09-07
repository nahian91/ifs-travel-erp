<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_settings_tour_delete_handler' ) ) {
    /**
     * Tour Package Plan Delete Handler
     */
    function ifs_terp_settings_tour_delete_handler() {
        global $wpdb;

        // Check permission / capabilities
        if ( function_exists( 'ifs_terp_has_access' ) ) {
            if ( ! ifs_terp_has_access( array( 'admin_manager' ) ) && ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Access Denied: You do not possess the required privilege level to delete this tour package plan.', 'ifs-travel-erp' ) );
            }
        } elseif ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access Denied: You do not have permission to delete this tour package plan.', 'ifs-travel-erp' ) );
        }

        $table_plans = $wpdb->prefix . 'iterp_tour_packages';
        $plan_id     = isset( $_GET['plan_id'] ) ? absint( wp_unslash( $_GET['plan_id'] ) ) : 0;
        $nonce       = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

        if ( $plan_id > 0 && wp_verify_nonce( $nonce, 'delete_tour_plan_' . $plan_id ) ) {
            $pkg_name = $wpdb->get_var( $wpdb->prepare( "SELECT package_name FROM {$table_plans} WHERE id = %d", $plan_id ) );
            $wpdb->delete( $table_plans, array( 'id' => $plan_id ), array( '%d' ) );

            if ( function_exists( 'ifs_terp_log_activity' ) ) {
                ifs_terp_log_activity( "Deleted Tour Package Plan #{$plan_id} ({$pkg_name})" );
            }

            $redirect_url = add_query_arg(
                array(
                    'page'       => 'ifs_travel_erp',
                    'tab'        => 'settings',
                    'sub'        => 'tour_pkg',
                    'action_sub' => 'all',
                    'msg'        => 'plan_deleted',
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