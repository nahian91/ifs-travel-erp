<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_hotel_properties_list_panel() {
    global $wpdb;
    $table_hotels = $wpdb->prefix . 'iterp_hotel_properties';
    $base_url     = admin_url( 'admin.php?page=ifs_travel_erp&tab=settings&sub=hotels_prop' );
    $message      = '';

    // Handle Delete Action with Nonce Protection & Role Capability Verification
    if ( isset( $_GET['hotel_sub'] ) && 'delete' === sanitize_key( wp_unslash( $_GET['hotel_sub'] ) ) && isset( $_GET['id'] ) ) {
        if ( function_exists( 'ifs_terp_has_access' ) ) {
            if ( ! ifs_terp_has_access( array( 'admin_manager' ) ) && ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Access Denied: You do not possess the required privilege level to delete this property record.', 'ifs-travel-erp' ) );
            }
        } elseif ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access Denied: You do not have permission to delete this property record.', 'ifs-travel-erp' ) );
        }

        $del_id = absint( wp_unslash( $_GET['id'] ) );
        check_admin_referer( 'delete_hotel_prop_' . $del_id );

        $property_name = $wpdb->get_var( $wpdb->prepare( "SELECT property_name FROM {$table_hotels} WHERE id = %d", $del_id ) );
        $wpdb->delete( $table_hotels, array( 'id' => $del_id ), array( '%d' ) );

        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            ifs_terp_log_activity( "Deleted Hotel Property Record #{$del_id} ({$property_name})" );
        }

        $redirect_url = add_query_arg(
            array(
                'page' => 'ifs_travel_erp',
                'tab'  => 'settings',
                'sub'  => 'hotels_prop',
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

    if ( isset( $_GET['msg'] ) && 'deleted' === sanitize_key( wp_unslash( $_GET['msg'] ) ) ) {
        $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Hotel property record deleted.', 'ifs-travel-erp' ) . '</div>';
    }

    $all_hotels = $wpdb->get_results( "SELECT * FROM {$table_hotels} ORDER BY id DESC" );
    $total_beds = 0;
    if ( ! empty( $all_hotels ) ) {
        foreach ( $all_hotels as $h ) {
            $total_beds += intval( $h->total_beds );
        }
    }
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
        
        .ifs-filter-input {
            height: 36px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 12px; background: #fff; font-size: 12.5px; font-weight: 600; color: #334155; outline: none; box-sizing: border-box; width: 220px;
        }
        .ifs-filter-input::placeholder { color: #94a3b8; font-weight: 500; }
    </style>

    <div class="wrap" style="max-width: 1400px; margin: 0 auto;">
        <?php echo wp_kses_post( $message ); ?>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 20px;">
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 14px;">
                <span class="dashicons dashicons-building" style="font-size: 28px; width: 28px; height: 28px; color: #0284c7;"></span>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;"><?php esc_html_e( 'Total Properties', 'ifs-travel-erp' ); ?></span>
                    <h3 style="margin: 2px 0 0 0; font-size: 20px; font-weight: 900; color: #0f172a;"><?php echo esc_html( count( (array) $all_hotels ) ); ?></h3>
                </div>
            </div>
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 14px;">
                <span class="dashicons dashicons-bed" style="font-size: 28px; width: 28px; height: 28px; color: #059669;"></span>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;"><?php esc_html_e( 'Allocated Beds', 'ifs-travel-erp' ); ?></span>
                    <h3 style="margin: 2px 0 0 0; font-size: 20px; font-weight: 900; color: #0f172a;"><?php echo esc_html( number_format( $total_beds ) ); ?></h3>
                </div>
            </div>
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 14px;">
                <span class="dashicons dashicons-shield-alt" style="font-size: 28px; width: 28px; height: 28px; color: #d97706;"></span>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;"><?php esc_html_e( 'Live Status', 'ifs-travel-erp' ); ?></span>
                    <h3 style="margin: 2px 0 0 0; font-size: 20px; font-weight: 900; color: #059669;"><?php esc_html_e( 'Active Contracts', 'ifs-travel-erp' ); ?></h3>
                </div>
            </div>
        </div>

        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; padding: 22px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; flex-wrap: wrap; gap: 12px;">
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-list-view" style="color: #003376;"></span>
                        <?php esc_html_e( 'Contracted Hotel Properties Directory', 'ifs-travel-erp' ); ?>
                    </h3>
                    <p style="margin: 2px 0 0 0; font-size: 12.5px; color: #64748b;"><?php esc_html_e( 'Review negotiated seasonal rates, allocations, and partner capacities', 'ifs-travel-erp' ); ?></p>
                </div>
                
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <!-- Search by Name Input -->
                    <input type="text" id="filterName" class="ifs-filter-input" placeholder="<?php esc_attr_e( 'Search by Property Name...', 'ifs-travel-erp' ); ?>">

                    <a href="<?php echo esc_url( add_query_arg( 'hotel_sub', 'add', $base_url ) ); ?>" style="background: #003376; color: #ffffff; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; height: 36px; box-sizing: border-box;">
                        <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add New Property', 'ifs-travel-erp' ); ?>
                    </a>
                </div>
            </div>

            <table id="ifsHotelTable" class="wp-list-table widefat fixed" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc; text-align: left;">
                        <th style="padding: 12px 14px; font-size: 11px; text-transform: uppercase; color: #475569;"><?php esc_html_e( 'Property Name', 'ifs-travel-erp' ); ?></th>
                        <th style="padding: 12px 14px; font-size: 11px; text-transform: uppercase; color: #475569;"><?php esc_html_e( 'Type / Rating', 'ifs-travel-erp' ); ?></th>
                        <th style="padding: 12px 14px; font-size: 11px; text-transform: uppercase; color: #475569;"><?php esc_html_e( 'City / Country', 'ifs-travel-erp' ); ?></th>
                        <th style="padding: 12px 14px; font-size: 11px; text-transform: uppercase; color: #475569; text-align: right; width: 220px;" data-sortable="false"><?php esc_html_e( 'Actions', 'ifs-travel-erp' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $all_hotels ) ) : foreach ( $all_hotels as $htl ) : ?>
                        <tr>
                            <td style="padding: 12px 14px;">
                                <strong style="color: #0f172a; font-size: 13.5px;"><?php echo esc_html( $htl->property_name ); ?></strong>
                                <?php if ( ! empty( $htl->total_beds ) ) : ?>
                                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                                        <span class="dashicons dashicons-bed" style="font-size: 13px; width: 13px; height: 13px; vertical-align: middle;"></span>
                                        <?php echo intval( $htl->total_beds ); ?> <?php esc_html_e( 'Total Beds', 'ifs-travel-erp' ); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 14px;">
                                <span style="background: #f1f5f9; padding: 2px 7px; border-radius: 4px; font-size: 11px; font-weight: 700; color: #475569;"><?php echo esc_html( $htl->property_type ?? 'Hotel' ); ?></span>
                                <span style="color: #d97706; font-size: 11px; font-weight: 800; margin-left: 4px;">★ <?php echo esc_html( str_replace( ' Star', '', (string) $htl->star_rating ) ); ?></span>
                            </td>
                            <td style="padding: 12px 14px; color: #475569; font-weight: 600;"><?php echo esc_html( $htl->city . ', ' . $htl->country ); ?></td>
                            <td style="padding: 12px 14px; text-align: right;">
                                <div style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                    <a href="<?php echo esc_url( add_query_arg( array( 'hotel_sub' => 'view', 'id' => $htl->id ), $base_url ) ); ?>" 
                                       style="display: inline-flex; align-items: center; gap: 4px; background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; text-decoration: none;"
                                       title="<?php esc_attr_e( 'View Details', 'ifs-travel-erp' ); ?>">
                                        <span class="dashicons dashicons-visibility" style="font-size: 14px; width: 14px; height: 14px;"></span>
                                        <span><?php esc_html_e( 'View', 'ifs-travel-erp' ); ?></span>
                                    </a>

                                    <a href="<?php echo esc_url( add_query_arg( array( 'hotel_sub' => 'edit', 'id' => $htl->id ), $base_url ) ); ?>" 
                                       style="display: inline-flex; align-items: center; gap: 4px; background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; text-decoration: none;"
                                       title="<?php esc_attr_e( 'Edit Property', 'ifs-travel-erp' ); ?>">
                                        <span class="dashicons dashicons-edit" style="font-size: 14px; width: 14px; height: 14px;"></span>
                                        <span><?php esc_html_e( 'Edit', 'ifs-travel-erp' ); ?></span>
                                    </a>

                                    <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'hotel_sub' => 'delete', 'id' => $htl->id ), $base_url ), 'delete_hotel_prop_' . $htl->id ) ); ?>" 
                                       style="display: inline-flex; align-items: center; gap: 4px; background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; text-decoration: none;"
                                       onclick="return confirm('<?php echo esc_js( __( 'Delete this hotel property permanently?', 'ifs-travel-erp' ) ); ?>');" 
                                       title="<?php esc_attr_e( 'Delete Property', 'ifs-travel-erp' ); ?>">
                                        <span class="dashicons dashicons-trash" style="font-size: 14px; width: 14px; height: 14px;"></span>
                                        <span><?php esc_html_e( 'Delete', 'ifs-travel-erp' ); ?></span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="4" style="text-align: center; padding: 40px; color: #94a3b8;"><?php esc_html_e( 'No hotel properties recorded yet.', 'ifs-travel-erp' ); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        if ($.fn.DataTable && $('#ifsHotelTable tbody tr td').length > 1) {
            var table = $('#ifsHotelTable').DataTable({
                "pageLength": 10,
                "lengthMenu": [ [10, 25, 50, -1], [10, 25, 50, "All"] ],
                "columnDefs": [
                    { "orderable": false, "targets": 3 }
                ],
                "language": {
                    "search": "Global Search:",
                    "lengthMenu": "Show _MENU_ entries per page",
                    "zeroRecords": "No matching hotel properties found",
                    "info": "Showing page _PAGE_ of _PAGES_",
                    "infoEmpty": "No properties available",
                    "infoFiltered": "(filtered from _MAX_ total entries)"
                }
            });

            // Live Search by Property Name (Column index 0)
            $('#filterName').on('keyup change', function() {
                table.column(0).search(this.value).draw();
            });
        }
    });
    </script>
    <?php
}