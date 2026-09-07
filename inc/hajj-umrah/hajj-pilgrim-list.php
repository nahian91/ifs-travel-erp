<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Hajj & Umrah Pilgrim Manifest Ledger
 */
function ifs_terp_hajj_pilgrim_list_page() {
    global $wpdb;
    $table_bookings  = $wpdb->prefix . 'iterp_hajj_bookings';
    $table_customers = $wpdb->prefix . 'iterp_customers';
    $table_packages  = $wpdb->prefix . 'iterp_hajj_packages';
    $table_agents    = $wpdb->prefix . 'iterp_agents';
    $base_url        = admin_url( 'admin.php?page=ifs_travel_erp&tab=hajj_umrah' );

    // Handle Delete Action with Nonce Protection & Safe Redirect
    if ( isset( $_GET['sub'] ) && 'delete' === sanitize_key( wp_unslash( $_GET['sub'] ) ) && isset( $_GET['id'] ) ) {
        // Check permission / capabilities
        if ( function_exists( 'ifs_terp_has_access' ) ) {
            if ( ! ifs_terp_has_access( array( 'admin_manager' ) ) && ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Access Denied: You do not possess the required privilege level to delete Hajj & Umrah bookings.', 'ifs-travel-erp' ) );
            }
        } elseif ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access Denied: You do not have permission to delete Hajj & Umrah bookings.', 'ifs-travel-erp' ) );
        }

        $del_id = absint( wp_unslash( $_GET['id'] ) );
        $nonce  = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

        if ( $del_id <= 0 || ! wp_verify_nonce( $nonce, 'delete_hajj_' . $del_id ) ) {
            wp_die( esc_html__( 'Security check failed or invalid request.', 'ifs-travel-erp' ) );
        }

        $wpdb->delete( $table_bookings, array( 'id' => $del_id ), array( '%d' ) );

        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            ifs_terp_log_activity( "Deleted Pilgrim Booking Record #HB-{$del_id}" );
        }

        $redirect_url = add_query_arg(
            array(
                'page' => 'ifs_travel_erp',
                'tab'  => 'hajj_umrah',
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

    $message = '';
    if ( isset( $_GET['msg'] ) && 'deleted' === sanitize_key( wp_unslash( $_GET['msg'] ) ) ) {
        $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Pilgrim booking record deleted successfully.', 'ifs-travel-erp' ) . '</div>';
    }

    $query = "
        SELECT b.*, 
               c.title AS pilgrim_title, c.full_name AS pilgrim_name, c.mobile, c.passport_no, c.gender, c.blood_group,
               m.full_name AS mahram_name,
               p.package_name, p.package_type, p.hotel_makkah, p.hotel_madinah,
               a.agency_name
        FROM {$table_bookings} b
        LEFT JOIN {$table_customers} c ON b.customer_id = c.id
        LEFT JOIN {$table_customers} m ON b.mahram_customer_id = m.id
        LEFT JOIN {$table_packages} p ON b.package_id = p.id
        LEFT JOIN {$table_agents} a ON b.agent_id = a.id
        ORDER BY b.id DESC
    ";
    $bookings = $wpdb->get_results( $query );
    ?>

    <div class="ifs-hajj-list-workspace">
        <?php echo wp_kses_post( $message ); ?>

        <!-- Master Data Table Card -->
        <div class="ifs-table-card">
            <!-- Custom DataTables Controls Toolbar -->
            <div class="ifs-custom-table-controls">
                <div class="ifs-per-page-wrap">
                    <label for="ifsHajjPerPageSelect"><?php esc_html_e( 'Show', 'ifs-travel-erp' ); ?></label>
                    <select id="ifsHajjPerPageSelect" class="ifs-select-control">
                        <option value="10">10</option>
                        <option value="15" selected>15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span><?php esc_html_e( 'entries', 'ifs-travel-erp' ); ?></span>
                </div>
                <div class="ifs-live-search-wrap">
                    <label for="ifsHajjSearchInput"><span class="dashicons dashicons-search"></span></label>
                    <input type="text" id="ifsHajjSearchInput" class="ifs-search-input" placeholder="<?php esc_attr_e( 'Search by pilgrim, passport, package...', 'ifs-travel-erp' ); ?>">
                </div>
            </div>

            <div class="ifs-table-responsive-wrapper">
                <table class="ifs-pro-datatable" id="ifsHajjTable">
                    <thead>
                        <tr>
                            <th style="width: 90px;"><?php esc_html_e( 'Booking ID', 'ifs-travel-erp' ); ?></th>
                            <th><?php esc_html_e( 'Pilgrim & Bio Meta', 'ifs-travel-erp' ); ?></th>
                            <th><?php esc_html_e( 'Package & Group Plan', 'ifs-travel-erp' ); ?></th>
                            <th style="text-align: center;"><?php esc_html_e( 'Visa Status', 'ifs-travel-erp' ); ?></th>
                            <th style="text-align: center;"><?php esc_html_e( 'Booking Status', 'ifs-travel-erp' ); ?></th>
                            <th style="text-align: right; width: 220px;" data-sortable="false"><?php esc_html_e( 'Actions', 'ifs-travel-erp' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $bookings ) ) : foreach ( $bookings as $row ) : 
                            $status_class = 'status-booked';
                            $status_lower = strtolower( (string) $row->status );
                            if ( 'confirmed' === $status_lower ) {
                                $status_class = 'status-confirmed';
                            } elseif ( 'completed' === $status_lower ) {
                                $status_class = 'status-completed';
                            } elseif ( 'cancelled' === $status_lower ) {
                                $status_class = 'status-cancelled';
                            }

                            $visa_badge = 'visa-pending';
                            $v_lower    = strtolower( (string) $row->visa_status );
                            if ( 'issued' === $v_lower ) {
                                $visa_badge = 'visa-issued';
                            } elseif ( 'submitted' === $v_lower ) {
                                $visa_badge = 'visa-submitted';
                            } elseif ( 'rejected' === $v_lower ) {
                                $visa_badge = 'visa-rejected';
                            }

                            $title_prefix = ! empty( $row->pilgrim_title ) ? esc_html( $row->pilgrim_title ) . '. ' : '';
                            $pax_name     = ! empty( $row->pilgrim_name ) ? $title_prefix . esc_html( $row->pilgrim_name ) : __( 'Guest Pilgrim', 'ifs-travel-erp' );
                            $passport_no  = ! empty( $row->passport_no ) ? esc_html( $row->passport_no ) : '-';

                            $pkg_type_class = ( 'Hajj' === $row->package_type ) ? 'type-hajj' : 'type-umrah';
                            $channel_tag    = ! empty( $row->agency_name ) ? '<span class="ifs-agent-tag"><span class="dashicons dashicons-groups"></span> ' . esc_html( $row->agency_name ) . '</span>' : '<span class="ifs-direct-tag">' . esc_html__( 'Direct Retail', 'ifs-travel-erp' ) . '</span>';
                        ?>
                            <tr>
                                <td>
                                    <span class="ifs-id-badge">#HB-<?php echo esc_html( str_pad( (string) $row->id, 5, '0', STR_PAD_LEFT ) ); ?></span>
                                </td>
                                <td>
                                    <div class="ifs-passenger-cell">
                                        <div>
                                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=customers&sub=view&id=' . $row->customer_id ) ); ?>" class="ifs-passenger-name">
                                                <?php echo esc_html( $pax_name ); ?>
                                            </a>
                                            <div class="ifs-passenger-submeta">
                                                <span class="font-mono">PPT: <?php echo esc_html( $passport_no ); ?></span>
                                                <span class="meta-dot"></span>
                                                <?php echo $channel_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                            </div>
                                            <?php if ( ! empty( $row->mahram_name ) ) : ?>
                                                <div class="ifs-mahram-tag"><span class="dashicons dashicons-admin-users"></span> <?php printf( esc_html__( 'Mahram: %s', 'ifs-travel-erp' ), esc_html( $row->mahram_name ) ); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="ifs-package-cell">
                                        <strong class="package-name"><?php echo esc_html( $row->package_name ?: __( 'Custom Package', 'ifs-travel-erp' ) ); ?></strong>
                                        <span class="ifs-pkg-pill <?php echo esc_attr( $pkg_type_class ); ?>"><?php echo esc_html( $row->package_type ?: 'Umrah' ); ?></span>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="ifs-visa-status-pill <?php echo esc_attr( $visa_badge ); ?>">
                                        <?php echo esc_html( $row->visa_status ); ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="ifs-status-badge <?php echo esc_attr( $status_class ); ?>">
                                        <?php echo esc_html( $row->status ); ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <div class="ifs-action-pills">
                                        <a href="<?php echo esc_url( add_query_arg( array( 'sub' => 'view', 'id' => $row->id ), $base_url ) ); ?>" class="ifs-action-pill view" title="<?php esc_attr_e( 'View Pilgrim Dossier', 'ifs-travel-erp' ); ?>">
                                            <span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'View', 'ifs-travel-erp' ); ?>
                                        </a>
                                        <a href="<?php echo esc_url( add_query_arg( array( 'sub' => 'edit', 'id' => $row->id ), $base_url ) ); ?>" class="ifs-action-pill edit" title="<?php esc_attr_e( 'Edit Pilgrim File', 'ifs-travel-erp' ); ?>">
                                            <span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Edit', 'ifs-travel-erp' ); ?>
                                        </a>
                                        <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'sub' => 'delete', 'id' => $row->id ), $base_url ), 'delete_hajj_' . $row->id ) ); ?>" 
                                           class="ifs-action-pill delete" 
                                           onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to permanently delete this pilgrim booking record?', 'ifs-travel-erp' ) ); ?>');" 
                                           title="<?php esc_attr_e( 'Delete Record', 'ifs-travel-erp' ); ?>">
                                            <span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Delete', 'ifs-travel-erp' ); ?>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr>
                                <td colspan="6" class="ifs-empty-table">
                                    <div class="ifs-empty-state">
                                        <span class="dashicons dashicons-palmtree"></span>
                                        <h4><?php esc_html_e( 'No Pilgrim Bookings Found', 'ifs-travel-erp' ); ?></h4>
                                        <p><?php esc_html_e( 'No pilgrim booking records are currently registered.', 'ifs-travel-erp' ); ?></p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        .ifs-hajj-list-workspace { 
            max-width: 1380px; 
            margin: 0 auto; 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            color: #0f172a; 
        }

        .ifs-toast { 
            padding: 12px 18px; 
            border-radius: 8px; 
            font-size: 13px; 
            font-weight: 600; 
            display: flex; 
            align-items: center; 
            gap: 8px; 
            margin-bottom: 20px; 
        }
        .ifs-toast.success { background: #dcfce7; border: 1px solid #bbf7d0; color: #166534; }
        .ifs-toast.danger  { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }
        .ifs-toast .dashicons { font-size: 18px; width: 18px; height: 18px; }

        /* Master Table Card */
        .ifs-table-card { 
            background: #ffffff; 
            border: 1px solid #e2e8f0; 
            border-radius: 12px; 
            overflow: hidden; 
        }

        /* Custom Toolbar */
        .ifs-custom-table-controls {
            padding: 16px 22px;
            background: #f8fafc;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
        }
        .ifs-per-page-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12.5px;
            color: #475569;
            font-weight: 600;
        }
        .ifs-select-control {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 4px 24px 4px 8px;
            font-size: 12.5px;
            color: #0f172a;
            background: #ffffff;
            outline: none;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 6px center;
            background-size: 11px;
        }
        .ifs-select-control:focus { border-color: #003376; box-shadow: 0 0 0 2px rgba(0, 51, 118, 0.12); }

        .ifs-live-search-wrap {
            position: relative;
            display: flex;
            align-items: center;
            min-width: 260px;
        }
        .ifs-live-search-wrap label {
            position: absolute;
            left: 10px;
            color: #94a3b8;
            display: flex;
            align-items: center;
            pointer-events: none;
        }
        .ifs-live-search-wrap label .dashicons { font-size: 15px; width: 15px; height: 15px; }
        .ifs-search-input {
            width: 100%;
            padding: 6px 10px 6px 32px !important;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 12.5px;
            color: #0f172a;
            background: #ffffff;
            outline: none;
        }
        .ifs-search-input:focus { border-color: #003376; box-shadow: 0 0 0 2px rgba(0, 51, 118, 0.12); }

        /* Table */
        .ifs-table-responsive-wrapper { padding: 12px 22px 20px; overflow-x: auto; }
        .ifs-pro-datatable { width: 100%; border-collapse: collapse; font-size: 12.5px; }
        .ifs-pro-datatable thead th { 
            background: #f8fafc; 
            color: #475569; 
            font-size: 11px; 
            font-weight: 700; 
            text-transform: uppercase; 
            letter-spacing: 0.4px; 
            padding: 10px 14px; 
            border-bottom: 1px solid #e2e8f0; 
            text-align: left; 
            white-space: nowrap; 
        }
        .ifs-pro-datatable tbody td { 
            padding: 12px 14px; 
            border-bottom: 1px solid #f1f5f9; 
            vertical-align: middle; 
            color: #334155; 
        }
        .ifs-pro-datatable tbody tr:hover td { background: #f8fafc; }

        .ifs-id-badge { 
            background: #f1f5f9; 
            color: #475569; 
            font-family: monospace; 
            font-weight: 700; 
            font-size: 11px; 
            padding: 2px 6px; 
            border-radius: 4px; 
            border: 1px solid #e2e8f0; 
            display: inline-block; 
        }

        .ifs-passenger-cell { display: flex; align-items: center; gap: 8px; }
        .ifs-passenger-name { font-weight: 700; color: #0f172a; text-decoration: none; font-size: 13px; }
        .ifs-passenger-name:hover { color: #047857; text-decoration: underline; }
        .ifs-passenger-submeta { font-size: 11px; color: #64748b; display: flex; align-items: center; gap: 5px; margin-top: 1px; }
        .meta-dot { width: 3px; height: 3px; border-radius: 50%; background: #cbd5e1; }

        .ifs-agent-tag { background: #eef2ff; color: #4338ca; font-size: 10px; font-weight: 700; padding: 1px 5px; border-radius: 4px; display: inline-flex; align-items: center; gap: 3px; }
        .ifs-agent-tag .dashicons { font-size: 11px; width: 11px; height: 11px; }
        .ifs-direct-tag { font-size: 10.5px; color: #059669; font-weight: 600; }

        .ifs-mahram-tag { font-size: 10px; color: #854d0e; background: #fef9c3; padding: 1px 6px; border-radius: 4px; display: inline-flex; align-items: center; gap: 3px; margin-top: 3px; font-weight: 600; }
        .ifs-mahram-tag .dashicons { font-size: 11px; width: 11px; height: 11px; }

        .ifs-package-cell { display: flex; flex-direction: column; gap: 2px; }
        .package-name { font-size: 12.5px; color: #0f172a; }
        .ifs-pkg-pill { font-size: 9.5px; font-weight: 800; padding: 1px 5px; border-radius: 3px; width: fit-content; text-transform: uppercase; }
        .type-umrah { background: #dcfce7; color: #15803d; }
        .type-hajj  { background: #fef3c7; color: #b45309; }

        .ifs-visa-status-pill { font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 4px; display: inline-block; text-transform: uppercase; }
        .visa-issued    { background: #dcfce7; color: #15803d; }
        .visa-submitted { background: #e0f2fe; color: #0369a1; }
        .visa-pending   { background: #fef3c7; color: #b45309; }
        .visa-rejected  { background: #fee2e2; color: #b91c1c; }

        .ifs-status-badge { display: inline-flex; align-items: center; padding: 2px 7px; border-radius: 4px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; }
        .status-booked    { background: #fef3c7; color: #b45309; }
        .status-confirmed { background: #dcfce7; color: #15803d; }
        .status-completed { background: #e0f2fe; color: #0369a1; }
        .status-cancelled { background: #fee2e2; color: #b91c1c; }

        /* Action Buttons */
        .ifs-action-pills { display: flex; gap: 4px; justify-content: flex-end; align-items: center; }
        .ifs-action-pill { 
            display: inline-flex; 
            align-items: center; 
            gap: 4px; 
            padding: 4px 8px; 
            border-radius: 5px; 
            font-size: 11.5px; 
            font-weight: 600; 
            text-decoration: none; 
            white-space: nowrap; 
            border: 1px solid transparent; 
        }
        .ifs-action-pill .dashicons { font-size: 13px; width: 13px; height: 13px; }
        .ifs-action-pill.view { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }
        .ifs-action-pill.view:hover { background: #e2e8f0; color: #0f172a; }
        .ifs-action-pill.edit { background: #eff6ff; color: #2563eb; border-color: #dbeafe; }
        .ifs-action-pill.edit:hover { background: #dbeafe; color: #1d4ed8; }
        .ifs-action-pill.delete { background: #fef2f2; color: #dc2626; border-color: #fee2e2; }
        .ifs-action-pill.delete:hover { background: #fee2e2; color: #b91c1c; }

        .font-mono { font-family: ui-monospace, SFMono-Regular, monospace; }

        .ifs-empty-table { text-align: center; padding: 40px 20px !important; }
        .ifs-empty-state .dashicons { font-size: 40px; width: 40px; height: 40px; color: #cbd5e1; margin-bottom: 8px; }
        .ifs-empty-state h4 { margin: 0 0 3px 0; font-size: 15px; font-weight: 700; color: #334155; }
        .ifs-empty-state p { margin: 0; color: #94a3b8; font-size: 12.5px; }

        /* DataTables Integration */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter { display: none !important; }
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate { margin-top: 14px; font-size: 12px; color: #64748b; }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 5px !important;
            color: #334155 !important;
            padding: 4px 10px !important;
            margin-left: 3px;
            font-weight: 600;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #003376 !important;
            color: #ffffff !important;
            border-color: #003376 !important;
        }
    </style>

    <script>
    jQuery(document).ready(function($) {
        if ($.fn.DataTable && $('#ifsHajjTable tbody tr td').length > 1) {
            var table = $('#ifsHajjTable').DataTable({
                "pageLength": 15,
                "ordering": true,
                "info": true,
                "searching": true,
                "lengthChange": false,
                "order": [[ 0, "desc" ]],
                "language": {
                    "info": "Showing _START_ to _END_ of _TOTAL_ pilgrim records",
                    "infoEmpty": "Showing 0 to 0 of 0 records",
                    "infoFiltered": "(filtered from _MAX_ total)",
                    "paginate": {
                        "previous": "&larr;",
                        "next": "&rarr;"
                    }
                }
            });

            $('#ifsHajjPerPageSelect').on('change', function() {
                table.page.len(parseInt($(this).val())).draw();
            });

            $('#ifsHajjSearchInput').on('keyup', function() {
                table.search(this.value).draw();
            });
        }
    });
    </script>
    <?php
}