<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_visa_all_panel() {
    global $wpdb;
    $table_reqs = $wpdb->prefix . 'iterp_visa_requirements';
    $base_url   = admin_url( 'admin.php?page=ifs_travel_erp&tab=settings&sub=visa_req' );
    $message    = '';

    // Handle Delete Action with Nonce Protection & Capability Verification
    if ( isset( $_GET['action_sub'] ) && 'delete' === sanitize_key( wp_unslash( $_GET['action_sub'] ) ) && isset( $_GET['req_id'] ) ) {
        if ( function_exists( 'ifs_terp_has_access' ) ) {
            if ( ! ifs_terp_has_access( array( 'admin_manager', 'visa_officer' ) ) && ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Access Denied: You do not possess the required privilege level to delete this policy.', 'ifs-travel-erp' ) );
            }
        } elseif ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access Denied: You do not have permission to delete this policy.', 'ifs-travel-erp' ) );
        }

        $del_id = absint( wp_unslash( $_GET['req_id'] ) );
        check_admin_referer( 'delete_visa_req_' . $del_id );

        $country = $wpdb->get_var( $wpdb->prepare( "SELECT country_name FROM {$table_reqs} WHERE id = %d", $del_id ) );
        $wpdb->delete( $table_reqs, array( 'id' => $del_id ), array( '%d' ) );

        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            ifs_terp_log_activity( "Deleted Visa Requirement Policy #{$del_id} ({$country})" );
        }

        $redirect_url = add_query_arg( array( 'page' => 'ifs_travel_erp', 'tab' => 'settings', 'sub' => 'visa_req', 'msg' => 'deleted' ), admin_url( 'admin.php' ) );
        if ( ! headers_sent() ) {
            wp_safe_redirect( $redirect_url );
            exit;
        } else {
            echo '<script type="text/javascript">window.location.replace("' . esc_url_raw( $redirect_url ) . '");</script>';
            exit;
        }
    }

    if ( isset( $_GET['msg'] ) && 'deleted' === sanitize_key( wp_unslash( $_GET['msg'] ) ) ) {
        $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Visa requirement configuration removed successfully.', 'ifs-travel-erp' ) . '</div>';
    }

    $all_reqs = $wpdb->get_results( "SELECT * FROM {$table_reqs} ORDER BY country_name ASC" );
    ?>
    <div class="wrap" style="max-width: 1400px; margin: 0 auto;">
        <?php echo wp_kses_post( $message ); ?>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 20px;">
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 14px;">
                <span class="dashicons dashicons-admin-site-alt3" style="font-size: 28px; width: 28px; height: 28px; color: #0284c7;"></span>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;"><?php esc_html_e( 'Active Destinations', 'ifs-travel-erp' ); ?></span>
                    <h3 style="margin: 2px 0 0 0; font-size: 20px; font-weight: 900; color: #0f172a;"><?php echo esc_html( count( (array) $all_reqs ) ); ?></h3>
                </div>
            </div>
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 14px;">
                <span class="dashicons dashicons-money-alt" style="font-size: 28px; width: 28px; height: 28px; color: #059669;"></span>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;"><?php esc_html_e( 'Fee Currency', 'ifs-travel-erp' ); ?></span>
                    <h3 style="margin: 2px 0 0 0; font-size: 20px; font-weight: 900; color: #0f172a;"><?php esc_html_e( 'BDT (৳)', 'ifs-travel-erp' ); ?></h3>
                </div>
            </div>
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 14px;">
                <span class="dashicons dashicons-shield-alt" style="font-size: 28px; width: 28px; height: 28px; color: #d97706;"></span>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;"><?php esc_html_e( 'Compliance Status', 'ifs-travel-erp' ); ?></span>
                    <h3 style="margin: 2px 0 0 0; font-size: 20px; font-weight: 900; color: #059669;"><?php esc_html_e( 'VFS & Embassy Sync', 'ifs-travel-erp' ); ?></h3>
                </div>
            </div>
        </div>

        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; padding: 22px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px;">
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-list-view" style="color: #003376;"></span>
                        <?php esc_html_e( 'Configured Country Advisory Directory', 'ifs-travel-erp' ); ?>
                    </h3>
                    <p style="margin: 2px 0 0 0; font-size: 12.5px; color: #64748b;"><?php esc_html_e( 'Global embassy fees, standard checklist requirements, and application turnaround times', 'ifs-travel-erp' ); ?></p>
                </div>
                <a href="<?php echo esc_url( add_query_arg( 'action_sub', 'add', $base_url ) ); ?>" style="background: #003376; color: #ffffff; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                    <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add New Requirement Policy', 'ifs-travel-erp' ); ?>
                </a>
            </div>

            <div class="ifs-table-responsive-wrapper" style="padding: 0; overflow-x: auto;">
                <table class="ifs-pro-datatable" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8fafc; text-align: left;">
                            <th style="width: 170px; padding: 10px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #475569; border-bottom: 1px solid #e2e8f0;"><?php esc_html_e( 'Country & Category', 'ifs-travel-erp' ); ?></th>
                            <th style="padding: 10px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #475569; border-bottom: 1px solid #e2e8f0;"><?php esc_html_e( 'Submission Mode', 'ifs-travel-erp' ); ?></th>
                            <th style="padding: 10px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #475569; border-bottom: 1px solid #e2e8f0;"><?php esc_html_e( 'Processing Time', 'ifs-travel-erp' ); ?></th>
                            <th style="text-align: right; padding: 10px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #475569; border-bottom: 1px solid #e2e8f0;"><?php esc_html_e( 'Govt Fee', 'ifs-travel-erp' ); ?></th>
                            <th style="text-align: right; padding: 10px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #475569; border-bottom: 1px solid #e2e8f0;"><?php esc_html_e( 'Agency Service', 'ifs-travel-erp' ); ?></th>
                            <th style="text-align: right; padding: 10px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #475569; border-bottom: 1px solid #e2e8f0;"><?php esc_html_e( 'Total Price (৳)', 'ifs-travel-erp' ); ?></th>
                            <th style="padding: 10px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #475569; border-bottom: 1px solid #e2e8f0;"><?php esc_html_e( 'Document Checklist', 'ifs-travel-erp' ); ?></th>
                            <th style="text-align: right; width: 130px; padding: 10px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #475569; border-bottom: 1px solid #e2e8f0;"><?php esc_html_e( 'Actions', 'ifs-travel-erp' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $all_reqs ) ) : foreach ( $all_reqs as $req ) : 
                            $emb_f = (float) ( $req->embassy_fee ?? ( (float) $req->standard_fee * 0.8 ) );
                            $svc_f = (float) ( $req->service_fee ?? ( (float) $req->standard_fee * 0.2 ) );
                            $tot_f = (float) $req->standard_fee;

                            $chk_display = array();
                            if ( ! empty( $req->requirements_list ) ) {
                                $dec = json_decode( $req->requirements_list, true );
                                if ( json_last_error() === JSON_ERROR_NONE && is_array( $dec ) ) {
                                    $chk_display = $dec;
                                } else {
                                    $chk_display = array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", '', $req->requirements_list ) ) ) );
                                }
                            }
                        ?>
                            <tr>
                                <td style="padding: 12px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;">
                                    <div class="ifs-country-cell" style="display: flex; align-items: center; gap: 8px;">
                                        <span class="dashicons dashicons-admin-site-alt3 country-icon" style="color: #0284c7;"></span>
                                        <div>
                                            <strong><?php echo esc_html( $req->country_name ); ?></strong>
                                            <div style="font-size: 11px; color: #64748b;"><?php echo esc_html( $req->visa_type ); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 12px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;">
                                    <span class="ifs-tier-pill tier-corporate" style="background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;"><?php echo esc_html( $req->submission_mode ?? 'Standard Submission' ); ?></span>
                                </td>
                                <td style="padding: 12px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;">
                                    <span class="ifs-time-pill" style="display: inline-flex; align-items: center; gap: 4px; font-size: 12px; color: #475569;"><span class="dashicons dashicons-clock" style="font-size: 14px; width: 14px; height: 14px;"></span> <?php echo esc_html( $req->processing_time ?: 'Standard' ); ?></span>
                                </td>
                                <td style="text-align: right; color: #64748b; font-family: ui-monospace, monospace; font-size: 13px; padding: 12px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;">
                                    ৳<?php echo esc_html( number_format( $emb_f, 2 ) ); ?>
                                </td>
                                <td style="text-align: right; color: #059669; font-family: ui-monospace, monospace; font-size: 13px; font-weight: 700; padding: 12px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;">
                                    ৳<?php echo esc_html( number_format( $svc_f, 2 ) ); ?>
                                </td>
                                <td style="text-align: right; font-weight: 800; color: #003376; font-family: ui-monospace, monospace; font-size: 14px; padding: 12px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;">
                                    ৳<?php echo esc_html( number_format( $tot_f, 2 ) ); ?>
                                </td>
                                <td style="padding: 12px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;">
                                    <div class="ifs-checklist-cell">
                                        <?php if ( ! empty( $chk_display ) ) : ?>
                                            <ul style="margin: 0; padding-left: 14px; font-size: 12px; color: #334155;">
                                                <?php foreach ( array_slice( $chk_display, 0, 2 ) as $c_item ) : ?>
                                                    <li><?php echo esc_html( $c_item ); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                            <?php if ( count( $chk_display ) > 2 ) : ?>
                                                <small style="color: #0284c7; font-weight: 600;"><?php echo esc_html( sprintf( '+ %d more items', count( $chk_display ) - 2 ) ); ?></small>
                                            <?php endif; ?>
                                        <?php else : ?>
                                            <span style="color: #94a3b8; font-size: 12px;"><?php esc_html_e( 'No checklist items', 'ifs-travel-erp' ); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="text-align: right; padding: 12px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;">
                                    <div class="ifs-action-pills" style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                        <a href="<?php echo esc_url( add_query_arg( array( 'action_sub' => 'view', 'req_id' => $req->id ), $base_url ) ); ?>" 
                                           class="ifs-action-btn view" 
                                           style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; border-radius: 6px;" 
                                           title="<?php esc_attr_e( 'View Details', 'ifs-travel-erp' ); ?>">
                                            <span class="dashicons dashicons-visibility" style="font-size: 14px; width: 14px; height: 14px;"></span>
                                        </a>
                                        <a href="<?php echo esc_url( add_query_arg( array( 'action_sub' => 'edit', 'req_id' => $req->id ), $base_url ) ); ?>" 
                                           class="ifs-action-btn edit" 
                                           style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; border-radius: 6px;" 
                                           title="<?php esc_attr_e( 'Edit Requirement', 'ifs-travel-erp' ); ?>">
                                            <span class="dashicons dashicons-edit" style="font-size: 14px; width: 14px; height: 14px;"></span>
                                        </a>
                                        <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action_sub' => 'delete', 'req_id' => $req->id ), $base_url ), 'delete_visa_req_' . $req->id ) ); ?>" 
                                           class="ifs-action-btn delete" 
                                           style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; border-radius: 6px;" 
                                           onclick="return confirm('<?php echo esc_js( __( 'Delete this country requirement?', 'ifs-travel-erp' ) ); ?>');" 
                                           title="<?php esc_attr_e( 'Delete Policy', 'ifs-travel-erp' ); ?>">
                                            <span class="dashicons dashicons-trash" style="font-size: 14px; width: 14px; height: 14px;"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr>
                                <td colspan="8" class="ifs-empty-table" style="text-align: center; padding: 40px; color: #94a3b8;">
                                    <div class="ifs-empty-state">
                                        <span class="dashicons dashicons-admin-site-alt3" style="font-size: 36px; width: 36px; height: 36px; color: #cbd5e1;"></span>
                                        <h4 style="margin: 6px 0 2px 0; font-size: 15px; font-weight: 700; color: #334155;"><?php esc_html_e( 'No Visa Requirements Configured Yet', 'ifs-travel-erp' ); ?></h4>
                                        <p style="margin: 0; color: #94a3b8; font-size: 12.5px;"><?php esc_html_e( 'Use the button above to configure your first destination visa policy.', 'ifs-travel-erp' ); ?></p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}