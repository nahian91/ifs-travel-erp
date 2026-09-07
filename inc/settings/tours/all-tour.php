<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_tour_plans_list_panel() {
    global $wpdb;
    $table_plans = $wpdb->prefix . 'iterp_tour_packages';
    $base_url    = admin_url( 'admin.php?page=ifs_travel_erp&tab=settings&sub=tour_pkg' );
    $message     = '';

    // Handle Delete Action with Nonce Protection & Capability Verification
    if ( isset( $_GET['action_sub'] ) && 'delete' === sanitize_key( wp_unslash( $_GET['action_sub'] ) ) && isset( $_GET['plan_id'] ) ) {
        if ( function_exists( 'ifs_terp_has_access' ) ) {
            if ( ! ifs_terp_has_access( array( 'admin_manager' ) ) && ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Access Denied: You do not possess the required privilege level to delete this tour plan.', 'ifs-travel-erp' ) );
            }
        } elseif ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access Denied: You do not have permission to delete this tour plan.', 'ifs-travel-erp' ) );
        }

        $del_id = absint( wp_unslash( $_GET['plan_id'] ) );
        check_admin_referer( 'delete_tour_plan_' . $del_id );

        $pkg_name = $wpdb->get_var( $wpdb->prepare( "SELECT package_name FROM {$table_plans} WHERE id = %d", $del_id ) );
        $wpdb->delete( $table_plans, array( 'id' => $del_id ), array( '%d' ) );

        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            ifs_terp_log_activity( "Deleted Tour Package Plan #{$del_id} ({$pkg_name})" );
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
            exit;
        } else {
            echo '<script type="text/javascript">window.location.replace("' . esc_url_raw( $redirect_url ) . '");</script>';
            exit;
        }
    }

    if ( isset( $_GET['msg'] ) && 'plan_deleted' === sanitize_key( wp_unslash( $_GET['msg'] ) ) ) {
        $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Tour package plan removed successfully.', 'ifs-travel-erp' ) . '</div>';
    }

    $all_plans = $wpdb->get_results( "SELECT * FROM {$table_plans} ORDER BY id DESC" );
    ?>
    <style>
        /* Custom DataTables ERP Theme Integration */
        .dataTables_wrapper { font-size: 13px; color: #334155; padding-top: 5px; }
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 12px; outline: none; margin-left: 8px; background: #fff;
        }
        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 8px; background: #fff; margin: 0 6px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border: 1px solid #cbd5e1 !important; border-radius: 6px !important; background: #fff !important; color: #334155 !important; margin-left: 4px !important; font-weight: 600;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #003376 !important; border-color: #003376 !important; color: #ffffff !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #f1f5f9 !important; color: #0f172a !important; border-color: #94a3b8 !important;
        }
        table.dataTable.no-footer { border-bottom: 1px solid #e2e8f0 !important; }
        table.dataTable thead th { border-bottom: 2px solid #e2e8f0 !important; font-weight: 800 !important; color: #475569 !important; }
        table.dataTable tbody td { padding: 14px 14px !important; border-bottom: 1px solid #f1f5f9 !important; vertical-align: middle !important; }
    </style>

    <div class="wrap" style="max-width: 1400px; margin: 0 auto;">
        <?php echo wp_kses_post( $message ); ?>

        <!-- Quick Summary Stats Grid -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 20px;">
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 14px;">
                <span class="dashicons dashicons-location" style="font-size: 28px; width: 28px; height: 28px; color: #0284c7;"></span>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;"><?php esc_html_e( 'Total Tour Plans', 'ifs-travel-erp' ); ?></span>
                    <h3 style="margin: 2px 0 0 0; font-size: 20px; font-weight: 900; color: #0f172a;"><?php echo esc_html( count( (array) $all_plans ) ); ?></h3>
                </div>
            </div>
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 14px;">
                <span class="dashicons dashicons-money-alt" style="font-size: 28px; width: 28px; height: 28px; color: #059669;"></span>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;"><?php esc_html_e( 'Base Currency', 'ifs-travel-erp' ); ?></span>
                    <h3 style="margin: 2px 0 0 0; font-size: 20px; font-weight: 900; color: #0f172a;"><?php esc_html_e( 'BDT (৳)', 'ifs-travel-erp' ); ?></h3>
                </div>
            </div>
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 14px;">
                <span class="dashicons dashicons-airplane" style="font-size: 28px; width: 28px; height: 28px; color: #d97706;"></span>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;"><?php esc_html_e( 'Tour Sectors', 'ifs-travel-erp' ); ?></span>
                    <h3 style="margin: 2px 0 0 0; font-size: 20px; font-weight: 900; color: #059669;"><?php esc_html_e( 'Domestic &amp; Intl', 'ifs-travel-erp' ); ?></h3>
                </div>
            </div>
        </div>

        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; padding: 22px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px;">
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-list-view" style="color: #003376;"></span>
                        <?php esc_html_e( 'Holiday Tour Packages & Itineraries', 'ifs-travel-erp' ); ?>
                    </h3>
                    <p style="margin: 2px 0 0 0; font-size: 12.5px; color: #64748b;"><?php esc_html_e( 'Active template plans, pricing matrices, and inclusions breakdown', 'ifs-travel-erp' ); ?></p>
                </div>
                <a href="<?php echo esc_url( add_query_arg( 'action_sub', 'add', $base_url ) ); ?>" style="background: #003376; color: #ffffff; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                    <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Tour Plan', 'ifs-travel-erp' ); ?>
                </a>
            </div>

            <table id="ifsTourTable" class="wp-list-table widefat fixed" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc; text-align: left;">
                        <th style="padding: 12px 14px; font-size: 11px; text-transform: uppercase; color: #475569;"><?php esc_html_e( 'Package Title', 'ifs-travel-erp' ); ?></th>
                        <th style="padding: 12px 14px; font-size: 11px; text-transform: uppercase; color: #475569;"><?php esc_html_e( 'Destination', 'ifs-travel-erp' ); ?></th>
                        <th style="padding: 12px 14px; font-size: 11px; text-transform: uppercase; color: #475569; text-align: right;"><?php esc_html_e( 'Cost Rate', 'ifs-travel-erp' ); ?></th>
                        <th style="padding: 12px 14px; font-size: 11px; text-transform: uppercase; color: #475569; text-align: right;"><?php esc_html_e( 'Base Price', 'ifs-travel-erp' ); ?></th>
                        <th style="padding: 12px 14px; font-size: 11px; text-transform: uppercase; color: #475569; text-align: right;"><?php esc_html_e( 'Margin', 'ifs-travel-erp' ); ?></th>
                        <th style="padding: 12px 14px; font-size: 11px; text-transform: uppercase; color: #475569; text-align: right; width: 220px;" data-sortable="false"><?php esc_html_e( 'Actions', 'ifs-travel-erp' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $all_plans ) ) : foreach ( $all_plans as $pln ) : 
                        $cost   = (float) $pln->cost_bdt;
                        $sell   = (float) $pln->selling_price;
                        $margin = $sell - $cost;
                    ?>
                        <tr>
                            <td style="padding: 12px 14px;">
                                <strong style="color: #0f172a; font-size: 13.5px;"><?php echo esc_html( $pln->package_name ); ?></strong>
                                <?php if ( ! empty( $pln->hotel_name ) ) : ?>
                                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                                        <span class="dashicons dashicons-building" style="font-size: 13px; width: 13px; height: 13px; vertical-align: middle;"></span>
                                        <?php echo esc_html( $pln->hotel_name ); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 14px; font-weight: 700; color: #0f172a;"><?php echo esc_html( $pln->destination ); ?></td>
                            <td style="padding: 12px 14px; text-align: right; font-family: monospace; font-weight: 700; color: #64748b;">৳<?php echo esc_html( number_format( $cost, 2 ) ); ?></td>
                            <td style="padding: 12px 14px; text-align: right; font-family: monospace; font-weight: 800; color: #059669;">৳<?php echo esc_html( number_format( $sell, 2 ) ); ?></td>
                            <td style="padding: 12px 14px; text-align: right; font-family: monospace; font-weight: 800; color: <?php echo ( $margin >= 0 ) ? '#059669' : '#dc2626'; ?>;">
                                ৳<?php echo esc_html( number_format( $margin, 2 ) ); ?>
                            </td>
                            <td style="padding: 12px 14px; text-align: right;">
                                <div style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                    <!-- View Button with Icon & Text -->
                                    <a href="<?php echo esc_url( add_query_arg( array( 'action_sub' => 'view', 'plan_id' => $pln->id ), $base_url ) ); ?>" 
                                       style="display: inline-flex; align-items: center; gap: 4px; background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; text-decoration: none; transition: all 0.18s ease;"
                                       title="<?php esc_attr_e( 'View Details', 'ifs-travel-erp' ); ?>">
                                        <span class="dashicons dashicons-visibility" style="font-size: 14px; width: 14px; height: 14px;"></span>
                                        <span><?php esc_html_e( 'View', 'ifs-travel-erp' ); ?></span>
                                    </a>

                                    <!-- Edit Button with Icon & Text -->
                                    <a href="<?php echo esc_url( add_query_arg( array( 'action_sub' => 'edit', 'plan_id' => $pln->id ), $base_url ) ); ?>" 
                                       style="display: inline-flex; align-items: center; gap: 4px; background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; text-decoration: none; transition: all 0.18s ease;"
                                       title="<?php esc_attr_e( 'Edit Template', 'ifs-travel-erp' ); ?>">
                                        <span class="dashicons dashicons-edit" style="font-size: 14px; width: 14px; height: 14px;"></span>
                                        <span><?php esc_html_e( 'Edit', 'ifs-travel-erp' ); ?></span>
                                    </a>

                                    <!-- Delete Button with Icon & Text -->
                                    <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action_sub' => 'delete', 'plan_id' => $pln->id ), $base_url ), 'delete_tour_plan_' . $pln->id ) ); ?>" 
                                       style="display: inline-flex; align-items: center; gap: 4px; background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; text-decoration: none; transition: all 0.18s ease;"
                                       onclick="return confirm('<?php echo esc_js( __( 'Delete this tour plan permanently?', 'ifs-travel-erp' ) ); ?>');" 
                                       title="<?php esc_attr_e( 'Delete Template', 'ifs-travel-erp' ); ?>">
                                        <span class="dashicons dashicons-trash" style="font-size: 14px; width: 14px; height: 14px;"></span>
                                        <span><?php esc_html_e( 'Delete', 'ifs-travel-erp' ); ?></span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;"><?php esc_html_e( 'No package templates created yet. Use the button above to build one.', 'ifs-travel-erp' ); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        if ($.fn.DataTable && $('#ifsTourTable tbody tr td').length > 1) {
            $('#ifsTourTable').DataTable({
                "pageLength": 10,
                "lengthMenu": [ [10, 25, 50, -1], [10, 25, 50, "All"] ],
                "columnDefs": [
                    { "orderable": false, "targets": 5 }
                ],
                "language": {
                    "search": "Search Tours:",
                    "lengthMenu": "Show _MENU_ entries per page",
                    "zeroRecords": "No matching tour plans found",
                    "info": "Showing page _PAGE_ of _PAGES_",
                    "infoEmpty": "No tour plans available",
                    "infoFiltered": "(filtered from _MAX_ total entries)"
                }
            });
        }
    });
    </script>
    <?php
}