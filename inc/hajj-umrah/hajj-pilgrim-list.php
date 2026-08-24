<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enterprise Next-Gen Hajj & Umrah Pilgrim Manifest Ledger
 * Features: Streamlined Manifest, Live Summary Analytics, Room Sharing Badges, Mahram Mapping, DataTables Integration & Action Controls
 */
function ifs_terp_hajj_pilgrim_list_page() {
    global $wpdb;
    $table_bookings  = $wpdb->prefix . 'iterp_hajj_bookings';
    $table_customers = $wpdb->prefix . 'iterp_customers';
    $table_packages  = $wpdb->prefix . 'iterp_hajj_packages';
    $table_agents    = $wpdb->prefix . 'iterp_agents';
    $base_url        = admin_url( 'admin.php?page=ifs_travel_erp&tab=hajj_umrah' );

    // Handle Delete Action with Nonce Protection & Safe Redirect
    if ( isset( $_GET['sub'] ) && 'delete' === $_GET['sub'] && isset( $_GET['id'] ) ) {
        $del_id = absint( $_GET['id'] );
        check_admin_referer( 'delete_hajj_' . $del_id );

        $wpdb->delete( $table_bookings, array( 'id' => $del_id ), array( '%d' ) );

        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            ifs_terp_log_activity( "Deleted Pilgrim Booking Record #HB-{$del_id}" );
        }

        $redirect_url = add_query_arg( array( 'page' => 'ifs_travel_erp', 'tab' => 'hajj_umrah', 'msg' => 'deleted' ), admin_url( 'admin.php' ) );
        if ( ! headers_sent() ) {
            wp_safe_redirect( $redirect_url );
            exit;
        } else {
            echo '<script type="text/javascript">window.location.replace("' . esc_url_raw( $redirect_url ) . '");</script>';
            exit;
        }
    }

    $message = '';
    if ( isset( $_GET['msg'] ) && 'deleted' === $_GET['msg'] ) {
        $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Pilgrim booking record deleted successfully.', 'ifs-travel-erp' ) . '</div>';
    }

    // Dynamic High-Level Metric Aggregations
    $total_pilgrims = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table_bookings}" );
    $confirmed_hajj = (int) $wpdb->get_var( "
        SELECT COUNT(b.id) FROM {$table_bookings} b 
        JOIN {$table_packages} p ON b.package_id = p.id 
        WHERE p.package_type = 'Hajj' AND b.status = 'Confirmed'
    " );
    $umrah_count    = (int) $wpdb->get_var( "
        SELECT COUNT(b.id) FROM {$table_bookings} b 
        JOIN {$table_packages} p ON b.package_id = p.id 
        WHERE p.package_type LIKE '%Umrah%'
    " );
    $total_turnover = (float) $wpdb->get_var( "SELECT SUM(sell_price) FROM {$table_bookings} WHERE status != 'Cancelled'" );

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
        <?php echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

        <!-- Metric Counter Ribbon -->
        <div class="ifs-list-metric-ribbon">
            <div class="ifs-metric-chip">
                <div class="chip-icon bg-emerald"><span class="dashicons dashicons-groups"></span></div>
                <div>
                    <span class="chip-label"><?php esc_html_e( 'Total Pilgrims Registered', 'ifs-travel-erp' ); ?></span>
                    <strong class="chip-val"><?php echo esc_html( number_format_i18n( $total_pilgrims ) ); ?></strong>
                </div>
            </div>
            <div class="ifs-metric-chip">
                <div class="chip-icon bg-amber"><span class="dashicons dashicons-star-filled"></span></div>
                <div>
                    <span class="chip-label"><?php esc_html_e( 'Confirmed Hajj Pilgrims', 'ifs-travel-erp' ); ?></span>
                    <strong class="chip-val color-amber"><?php echo esc_html( number_format_i18n( $confirmed_hajj ) ); ?></strong>
                </div>
            </div>
            <div class="ifs-metric-chip">
                <div class="chip-icon bg-blue"><span class="dashicons dashicons-palmtree"></span></div>
                <div>
                    <span class="chip-label"><?php esc_html_e( 'Umrah Mutamir Count', 'ifs-travel-erp' ); ?></span>
                    <strong class="chip-val color-blue"><?php echo esc_html( number_format_i18n( $umrah_count ) ); ?></strong>
                </div>
            </div>
            <div class="ifs-metric-chip">
                <div class="chip-icon bg-indigo"><span class="dashicons dashicons-chart-line"></span></div>
                <div>
                    <span class="chip-label"><?php esc_html_e( 'Total Pilgrim Turnover', 'ifs-travel-erp' ); ?></span>
                    <strong class="chip-val color-emerald">৳<?php echo esc_html( number_format( $total_turnover, 0 ) ); ?></strong>
                </div>
            </div>
        </div>

        <!-- Master Data Table Card -->
        <div class="ifs-table-card">
            <div class="ifs-table-top-bar">
                <div class="ifs-table-title-group">
                    <h3 class="ifs-table-heading"><span class="dashicons dashicons-palmtree"></span> <?php esc_html_e( 'Registered Pilgrims (Haji & Mutamir Manifest)', 'ifs-travel-erp' ); ?></h3>
                    <p class="ifs-table-caption"><?php esc_html_e( 'Pilgrim portfolios, Mahram associations, room allocations, and visa statuses', 'ifs-travel-erp' ); ?></p>
                </div>
                <div class="ifs-table-btn-group">
                    <a href="<?php echo esc_url( $base_url . '&sub=add' ); ?>" class="ifs-btn-primary">
                        <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Register New Pilgrim', 'ifs-travel-erp' ); ?>
                    </a>
                </div>
            </div>

            <div class="ifs-table-responsive-wrapper">
                <table class="ifs-pro-datatable" id="ifsHajjTable">
                    <thead>
                        <tr>
                            <th style="width: 90px;"><?php esc_html_e( 'Booking ID', 'ifs-travel-erp' ); ?></th>
                            <th><?php esc_html_e( 'Pilgrim & Bio Meta', 'ifs-travel-erp' ); ?></th>
                            <th><?php esc_html_e( 'Package & Group Plan', 'ifs-travel-erp' ); ?></th>
                            <th><?php esc_html_e( 'Room Sharing', 'ifs-travel-erp' ); ?></th>
                            <th style="text-align: center;"><?php esc_html_e( 'Visa Status', 'ifs-travel-erp' ); ?></th>
                            <th style="text-align: center;"><?php esc_html_e( 'Booking Status', 'ifs-travel-erp' ); ?></th>
                            <th style="text-align: right; width: 140px;"><?php esc_html_e( 'Actions', 'ifs-travel-erp' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $bookings ) : foreach ( $bookings as $row ) : 
                            $status_class = 'status-booked';
                            $status_lower = strtolower( (string) $row->status );
                            if ( 'confirmed' === $status_lower )   $status_class = 'status-confirmed';
                            elseif ( 'completed' === $status_lower ) $status_class = 'status-completed';
                            elseif ( 'cancelled' === $status_lower ) $status_class = 'status-cancelled';

                            $visa_badge = 'visa-pending';
                            $v_lower    = strtolower( (string) $row->visa_status );
                            if ( 'issued' === $v_lower )      $visa_badge = 'visa-issued';
                            elseif ( 'submitted' === $v_lower ) $visa_badge = 'visa-submitted';
                            elseif ( 'rejected' === $v_lower )  $visa_badge = 'visa-rejected';

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
                                <td>
                                    <span class="ifs-sharing-badge sharing-<?php echo esc_attr( strtolower( (string) $row->room_sharing ) ); ?>">
                                        <?php echo esc_html( $row->room_sharing ); ?> <?php esc_html_e( 'Room', 'ifs-travel-erp' ); ?>
                                    </span>
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
                                    <div class="ifs-action-buttons">
                                        <a href="<?php echo esc_url( $base_url . '&sub=view&id=' . $row->id ); ?>" class="ifs-btn-action view" title="<?php esc_attr_e( 'View Pilgrim Dossier', 'ifs-travel-erp' ); ?>">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </a>
                                        <a href="<?php echo esc_url( $base_url . '&sub=edit&id=' . $row->id ); ?>" class="ifs-btn-action edit" title="<?php esc_attr_e( 'Edit Pilgrim File', 'ifs-travel-erp' ); ?>">
                                            <span class="dashicons dashicons-edit"></span>
                                        </a>
                                        <a href="<?php echo esc_url( wp_nonce_url( $base_url . '&sub=delete&id=' . $row->id, 'delete_hajj_' . $row->id ) ); ?>" 
                                           class="ifs-btn-action delete" 
                                           onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to permanently delete this pilgrim booking record?', 'ifs-travel-erp' ); ?>');" 
                                           title="<?php esc_attr_e( 'Delete Record', 'ifs-travel-erp' ); ?>">
                                            <span class="dashicons dashicons-trash"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr>
                                <td colspan="7" class="ifs-empty-table">
                                    <div class="ifs-empty-state">
                                        <span class="dashicons dashicons-palmtree"></span>
                                        <h4><?php esc_html_e( 'No Pilgrim Bookings Found', 'ifs-travel-erp' ); ?></h4>
                                        <p><?php esc_html_e( 'Start registering pilgrims and assigning Hajj & Umrah package slots.', 'ifs-travel-erp' ); ?></p>
                                        <a href="<?php echo esc_url( $base_url . '&sub=add' ); ?>" class="ifs-btn-primary" style="margin-top: 10px;">
                                            <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Register Pilgrim', 'ifs-travel-erp' ); ?>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modern High-End UI Stylesheet -->
    <style>
        .ifs-hajj-list-workspace { max-width: 1440px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; }
        .ifs-toast { padding: 14px 20px; border-radius: 10px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 10px; margin-bottom: 22px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .ifs-toast.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .ifs-toast.danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .ifs-toast .dashicons { font-size: 18px; width: 18px; height: 18px; }

        .ifs-list-metric-ribbon { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px; margin-bottom: 24px; }
        .ifs-metric-chip { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 8px -2px rgba(15, 23, 42, 0.03); }
        .chip-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #ffffff; flex-shrink: 0; }
        .chip-icon.bg-emerald{ background: linear-gradient(135deg, #047857 0%, #059669 100%); }
        .chip-icon.bg-amber  { background: linear-gradient(135deg, #d97706 0%, #b45309 100%); }
        .chip-icon.bg-blue   { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); }
        .chip-icon.bg-indigo { background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); }
        .chip-icon .dashicons { font-size: 22px; width: 22px; height: 22px; }

        .chip-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; display: block; margin-bottom: 2px; }
        .chip-val { font-size: 19px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
        .color-blue { color: #003376 !important; }
        .color-emerald { color: #059669 !important; }
        .color-amber { color: #d97706 !important; }

        .ifs-table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); overflow: hidden; }
        .ifs-table-top-bar { padding: 22px 26px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; gap: 15px; }
        .ifs-table-heading { margin: 0; font-size: 17px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        .ifs-table-heading .dashicons { color: #047857; font-size: 20px; width: 20px; height: 20px; }
        .ifs-table-caption { margin: 3px 0 0 0; font-size: 13px; color: #64748b; }

        .ifs-btn-primary { background: linear-gradient(135deg, #047857 0%, #059669 100%); color: #ffffff !important; border: none; padding: 9px 18px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25); transition: all 0.2s ease; }
        .ifs-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(5, 150, 105, 0.35); }

        .ifs-table-responsive-wrapper { padding: 15px 24px 24px 24px; overflow-x: auto; }
        .ifs-pro-datatable { width: 100%; border-collapse: collapse; font-size: 13px; }
        .ifs-pro-datatable thead th { background: #f8fafc; color: #475569; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px 14px; border-bottom: 2px solid #e2e8f0; text-align: left; white-space: nowrap; }
        .ifs-pro-datatable tbody td { padding: 13px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #334155; }
        .ifs-pro-datatable tbody tr:hover td { background: #f8fafc; }

        .ifs-id-badge { background: #f1f5f9; color: #475569; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-weight: 700; font-size: 11px; padding: 3px 6px; border-radius: 6px; border: 1px solid #e2e8f0; display: inline-block; }

        .ifs-passenger-name { font-weight: 700; color: #0f172a; text-decoration: none; font-size: 13px; transition: color 0.15s ease; }
        .ifs-passenger-name:hover { color: #047857; }
        .ifs-passenger-submeta { font-size: 11px; color: #64748b; display: flex; align-items: center; gap: 6px; margin-top: 2px; }
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

        .ifs-sharing-badge { font-size: 10.5px; font-weight: 700; padding: 2px 7px; border-radius: 4px; display: inline-block; }
        .sharing-quad   { background: #f1f5f9; color: #475569; }
        .sharing-triple { background: #e0f2fe; color: #0369a1; }
        .sharing-double { background: #fae8ff; color: #86198f; }
        .sharing-single { background: #fef2f2; color: #991b1b; }

        .ifs-visa-status-pill { font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 4px; display: inline-block; text-transform: uppercase; }
        .visa-issued    { background: #dcfce7; color: #15803d; }
        .visa-submitted { background: #e0f2fe; color: #0369a1; }
        .visa-pending   { background: #fef3c7; color: #b45309; }
        .visa-rejected  { background: #fee2e2; color: #b91c1c; }

        .ifs-status-badge { display: inline-flex; align-items: center; padding: 3px 8px; border-radius: 6px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; }
        .status-booked    { background: #fef3c7; color: #b45309; }
        .status-confirmed { background: #dcfce7; color: #15803d; }
        .status-completed { background: #e0f2fe; color: #0369a1; }
        .status-cancelled { background: #fee2e2; color: #b91c1c; }

        .ifs-action-buttons { display: flex; gap: 5px; justify-content: flex-end; }
        .ifs-btn-action { width: 30px; height: 30px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.15s ease; }
        .ifs-btn-action.view   { background: #f1f5f9; color: #475569; }
        .ifs-btn-action.view:hover { background: #e2e8f0; color: #0f172a; }
        .ifs-btn-action.edit   { background: #eff6ff; color: #2563eb; }
        .ifs-btn-action.edit:hover { background: #dbeafe; color: #1d4ed8; }
        .ifs-btn-action.delete { background: #fef2f2; color: #dc2626; }
        .ifs-btn-action.delete:hover { background: #fee2e2; color: #b91c1c; }
        .ifs-btn-action .dashicons { font-size: 14px; width: 14px; height: 14px; }

        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }

        .ifs-empty-table { text-align: center; padding: 50px 20px !important; }
        .ifs-empty-state .dashicons { font-size: 44px; width: 44px; height: 44px; color: #cbd5e1; margin-bottom: 10px; }
        .ifs-empty-state h4 { margin: 0 0 4px 0; font-size: 16px; font-weight: 700; color: #334155; }
        .ifs-empty-state p { margin: 0; color: #94a3b8; font-size: 13px; }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter { margin-bottom: 16px; font-size: 13px; color: #475569; }
        .dataTables_wrapper .dataTables_filter input { border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 12px; margin-left: 8px; outline: none; font-size: 13px; }
        .dataTables_wrapper .dataTables_filter input:focus { border-color: #047857; box-shadow: 0 0 0 3px rgba(4, 120, 87, 0.12); }
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate { margin-top: 18px; font-size: 13px; color: #64748b; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: #047857 !important; color: #ffffff !important; border: 1px solid #047857 !important; border-radius: 6px; font-weight: 700; }
    </style>

    <script>
    jQuery(document).ready(function($) {
        if ($.fn.DataTable) {
            $('#ifsHajjTable').DataTable({
                "pageLength": 15,
                "ordering": true,
                "info": true,
                "searching": true,
                "order": [[ 0, "desc" ]],
                "language": {
                    "search": "_INPUT_",
                    "searchPlaceholder": "Search Pilgrim, Package...",
                    "lengthMenu": "Show _MENU_ entries",
                    "paginate": {
                        "previous": "&larr; Prev",
                        "next": "Next &rarr;"
                    }
                }
            });
        }
    });
    </script>
    <?php
}