<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_refund_delete_handler' ) ) {
    /**
     * Changes & Refunds Record Delete Handler
     */
    function ifs_terp_refund_delete_handler() {
        // Check permission / capabilities
        if ( function_exists( 'ifs_terp_has_access' ) ) {
            if ( ! ifs_terp_has_access( array( 'admin_manager' ) ) && ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Access Denied: You do not possess the required privilege level to delete refund or reissue records.', 'ifs-travel-erp' ) );
            }
        } elseif ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access Denied: You do not have permission to delete refund or reissue records.', 'ifs-travel-erp' ) );
        }

        global $wpdb;
        $table_refunds = $wpdb->prefix . 'iterp_refund_reissue';
        $table_tickets = $wpdb->prefix . 'iterp_tickets';

        $sub_action = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : '';
        $del_id     = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;

        if ( 'delete' === $sub_action && $del_id > 0 ) {
            check_admin_referer( 'delete_refund_' . $del_id );

            $ref_info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_refunds} WHERE id = %d", $del_id ) );
            if ( $ref_info ) {
                if ( ! empty( $ref_info->ticket_id ) ) {
                    $wpdb->update( $table_tickets, array( 'status' => 'Issued' ), array( 'id' => $ref_info->ticket_id ), array( '%s' ), array( '%d' ) );
                }
                $wpdb->delete( $table_refunds, array( 'id' => $del_id ), array( '%d' ) );

                if ( function_exists( 'ifs_terp_log_activity' ) ) {
                    ifs_terp_log_activity( "Deleted Change/Refund Record #REF-{$del_id} (PNR: {$ref_info->pnr} | Type: {$ref_info->type})" );
                }
            }

            $redirect_url = add_query_arg(
                array(
                    'page' => 'ifs_travel_erp',
                    'tab'  => 'refund_reissue',
                    'msg'  => 'deleted',
                ),
                admin_url( 'admin.php' )
            );

            if ( ! headers_sent() ) {
                wp_safe_redirect( $redirect_url );
                exit;
            } else {
                echo '<script type="text/javascript">window.location.replace("' . esc_url_raw( $redirect_url ) . '");</script>';
                exit;
            }
        }
    }
}