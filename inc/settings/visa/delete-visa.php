<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_settings_visa_req_delete_handler' ) ) {
    /**
     * Visa Requirement Configuration Delete Handler
     */
    function ifs_terp_settings_visa_req_delete_handler() {
        global $wpdb;

        // Check permission / capabilities
        if ( function_exists( 'ifs_terp_has_access' ) ) {
            if ( ! ifs_terp_has_access( array( 'admin_manager', 'visa_officer' ) ) && ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Access Denied: You do not possess the required privilege level to delete this visa requirement policy.', 'ifs-travel-erp' ) );
            }
        } elseif ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access Denied: You do not have permission to delete this visa requirement policy.', 'ifs-travel-erp' ) );
        }

        $table_reqs = $wpdb->prefix . 'iterp_visa_requirements';
        $req_id     = isset( $_GET['req_id'] ) ? absint( wp_unslash( $_GET['req_id'] ) ) : 0;
        $nonce      = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

        if ( $req_id > 0 && wp_verify_nonce( $nonce, 'delete_visa_req_' . $req_id ) ) {
            $country_name = $wpdb->get_var( $wpdb->prepare( "SELECT country_name FROM {$table_reqs} WHERE id = %d", $req_id ) );
            $wpdb->delete( $table_reqs, array( 'id' => $req_id ), array( '%d' ) );

            if ( function_exists( 'ifs_terp_log_activity' ) ) {
                ifs_terp_log_activity( "Deleted Visa Requirement Policy #{$req_id} ({$country_name})" );
            }

            $redirect_url = add_query_arg(
                array(
                    'page'       => 'ifs_travel_erp',
                    'tab'        => 'settings',
                    'sub'        => 'visa_req',
                    'action_sub' => 'all',
                    'msg'        => 'deleted',
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