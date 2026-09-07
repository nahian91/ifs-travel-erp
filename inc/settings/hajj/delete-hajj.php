<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_settings_hajj_delete_handler' ) ) {
    /**
     * Hajj & Umrah Package Template Delete Handler
     */
    function ifs_terp_settings_hajj_delete_handler() {
        global $wpdb;

        // Check permission / capabilities
        if ( function_exists( 'ifs_terp_has_access' ) ) {
            if ( ! ifs_terp_has_access( array( 'admin_manager' ) ) && ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Access Denied: You do not possess the required privilege level to delete this package template.', 'ifs-travel-erp' ) );
            }
        } elseif ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access Denied: You do not have permission to delete this package template.', 'ifs-travel-erp' ) );
        }

        $table_pkgs = $wpdb->prefix . 'iterp_hajj_packages';
        $del_id     = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
        $nonce      = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

        if ( $del_id > 0 && wp_verify_nonce( $nonce, 'delete_hajj_pkg_' . $del_id ) ) {
            $pkg_name = $wpdb->get_var( $wpdb->prepare( "SELECT package_name FROM {$table_pkgs} WHERE id = %d", $del_id ) );
            $wpdb->delete( $table_pkgs, array( 'id' => $del_id ), array( '%d' ) );

            if ( function_exists( 'ifs_terp_log_activity' ) ) {
                ifs_terp_log_activity( "Deleted Hajj/Umrah Package Template #{$del_id} ({$pkg_name})" );
            }

            $redirect_url = add_query_arg(
                array(
                    'page'       => 'ifs_travel_erp',
                    'tab'        => 'settings',
                    'sub'        => 'hajj_pkg',
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