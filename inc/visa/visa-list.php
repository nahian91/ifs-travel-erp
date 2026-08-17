<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enterprise Next-Gen Visa Processing Pipeline & Inventory Desk
 * Features: Real-time Metric Aggregations, Embassy Tracking Indicators, Country Badges, DataTables Integration & Action Controls
 */
function ifs_terp_visa_list_page() {
    global $wpdb;
    $table_visas     = $wpdb->prefix . 'iterp_visa_applications';
    $table_customers = $wpdb->prefix . 'iterp_customers';
    $table_agents    = $wpdb->prefix . 'iterp_agents';
    $base_url        = admin_url( 'admin.php?page=ifs_travel_erp&tab=visa' );

    // Handle Delete Action with Nonce Verification
    if ( isset( $_GET['sub'] ) && $_GET['sub'] === 'delete' && isset( $_GET['id'] ) ) {
        $del_id = intval( $_GET['id'] );
        check_admin_referer( 'delete_visa_' . $del_id );

        $country = $wpdb->get_var( $wpdb->prepare( "SELECT country FROM $table_visas WHERE id = %d", $del_id ) );
        $wpdb->delete( $table_visas, array( 'id' => $del_id ), array( '%d' ) );

        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            ifs_terp_log_activity( "Deleted Visa Application File #VSA-$del_id ($country)" );
        }

        wp_safe_redirect( add_query_arg( array( 'page' => 'ifs_travel_erp', 'tab' => 'visa', 'msg' => 'deleted' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    $message = '';
    if ( isset( $_GET['msg'] ) && $_GET['msg'] === 'deleted' ) {
        $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Visa application file deleted successfully.</div>';
    }

    // Dynamic Summary Aggregations
    $total_files      = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_visas" );
    $processing_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $table_visas WHERE status = %s", 'Processing' ) );
    $approved_count   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $table_visas WHERE status IN (%s, %s)", 'Approved', 'Delivered' ) );
    $total_profit     = (float) $wpdb->get_var( "SELECT SUM(profit) FROM $table_visas WHERE status != 'Rejected'" );

    $query = "
        SELECT v.*, 
               c.title AS customer_title, c.full_name AS customer_name, c.mobile AS customer_mobile, c.passport_no,
               a.agency_name
        FROM $table_visas v
        LEFT JOIN $table_customers c ON v.customer_id = c.id
        LEFT JOIN $table_agents a ON v.agent_id = a.id
        ORDER BY v.id DESC
    ";
    $visas = $wpdb->get_results( $query );
    ?>

    <div class="ifs-visa-list-workspace">
        <?php echo $message; ?>

        <!-- Metric Counter Ribbon -->
        <div class="ifs-list-metric-ribbon">
            <div class="ifs-metric-chip">
                <div class="chip-icon bg-blue"><span class="dashicons dashicons-id-alt"></span></div>
                <div>
                    <span class="chip-label">Total Visa Files</span>
                    <strong class="chip-val"><?php echo number_format( $total_files ); ?></strong>
                </div>
            </div>
            <div class="ifs-metric-chip">
                <div class="chip-icon bg-amber"><span class="dashicons dashicons-clock"></span></div>
                <div>
                    <span class="chip-label">Under Processing</span>
                    <strong class="chip-val color-amber"><?php echo number_format( $processing_count ); ?></strong>
                </div>
            </div>
            <div class="ifs-metric-chip">
                <div class="chip-icon bg-emerald"><span class="dashicons dashicons-yes-alt"></span></div>
                <div>
                    <span class="chip-label">Approved & Delivered</span>
                    <strong class="chip-val color-emerald"><?php echo number_format( $approved_count ); ?></strong>
                </div>
            </div>
            <div class="ifs-metric-chip">
                <div class="chip-icon bg-indigo"><span class="dashicons dashicons-chart-line"></span></div>
                <div>
                    <span class="chip-label">Net Visa Margin</span>
                    <strong class="chip-val color-blue">৳<?php echo number_format( $total_profit, 2 ); ?></strong>
                </div>
            </div>
        </div>

        <!-- Master Data Table Card -->
        <div class="ifs-table-card">
            <div class="ifs-table-top-bar">
                <div class="ifs-table-title-group">
                    <h3 class="ifs-table-heading"><span class="dashicons dashicons-portfolio"></span> Visa Processing Pipeline</h3>
                    <p class="ifs-table-caption">Embassy submission workflows, passport tracking, delivery schedules, and commercial settlements</p>
                </div>
                <div class="ifs-table-btn-group">
                    <a href="<?php echo esc_url( $base_url . '&sub=add' ); ?>" class="ifs-btn-primary">
                        <span class="dashicons dashicons-plus-alt2"></span> New Visa Application
                    </a>
                </div>
            </div>

            <div class="ifs-table-responsive-wrapper">
                <table class="ifs-pro-datatable" id="ifsVisaTable">
                    <thead>
                        <tr>
                            <th style="width: 80px;">File ID</th>
                            <th>Applicant & Passport Meta</th>
                            <th>Destination & Category</th>
                            <th>Tracking Ref</th>
                            <th>Submission Date</th>
                            <th>Expected Delivery</th>
                            <th style="text-align: right;">Cost (৳)</th>
                            <th style="text-align: right;">Sell (৳)</th>
                            <th style="text-align: right;">Profit (৳)</th>
                            <th style="text-align: center;">Status</th>
                            <th style="text-align: right; width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $visas ) : foreach ( $visas as $row ) : 
                            $status_class = 'status-processing';
                            $status_lower = strtolower( $row->status );
                            if ( $status_lower === 'approved' )   $status_class = 'status-approved';
                            elseif ( $status_lower === 'delivered' ) $status_class = 'status-delivered';
                            elseif ( $status_lower === 'rejected' )  $status_class = 'status-rejected';

                            $title_prefix = ! empty( $row->customer_title ) ? esc_html( $row->customer_title ) . '. ' : '';
                            $pax_name     = ! empty( $row->customer_name ) ? $title_prefix . esc_html( $row->customer_name ) : 'Guest Applicant';
                            $pax_mobile   = esc_html( $row->customer_mobile ?? '' );
                            $passport_no  = ! empty( $row->passport_no ) ? esc_html( $row->passport_no ) : '-';

                            // Channel tag: Direct vs B2B Sub-Agent
                            $channel_tag  = ! empty( $row->agency_name ) ? '<span class="ifs-agent-tag"><span class="dashicons dashicons-groups"></span> ' . esc_html( $row->agency_name ) . '</span>' : '<span class="ifs-direct-tag">Direct Retail</span>';
                        ?>
                            <tr>
                                <td>
                                    <span class="ifs-id-badge">#VSA-<?php echo str_pad( (string) $row->id, 5, '0', STR_PAD_LEFT ); ?></span>
                                </td>
                                <td>
                                    <div class="ifs-passenger-cell">
                                        <div>
                                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=customers&sub=view&id=' . $row->customer_id ) ); ?>" class="ifs-passenger-name">
                                                <?php echo $pax_name; ?>
                                            </a>
                                            <div class="ifs-passenger-submeta">
                                                <span class="font-mono">PPT: <?php echo $passport_no; ?></span>
                                                <span class="meta-dot"></span>
                                                <?php echo $channel_tag; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="ifs-country-cell">
                                        <strong class="country-title"><span class="dashicons dashicons-admin-site-alt3"></span> <?php echo esc_html( $row->country ); ?></strong>
                                        <div class="visa-meta">
                                            <span class="visa-type-tag"><?php echo esc_html( $row->visa_type ); ?></span>
                                            <?php if ( ! empty( $row->entry_type ) ) : ?>
                                                <span class="entry-tag"><?php echo esc_html( $row->entry_type ); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="ifs-tracking-pill font-mono"><?php echo esc_html( $row->tracking_no ?: 'PENDING' ); ?></span>
                                </td>
                                <td>
                                    <div class="ifs-date-cell">
                                        <span class="date-main"><?php echo ( $row->submission_date !== '1970-01-01' && ! empty( $row->submission_date ) ) ? date( 'd M Y', strtotime( $row->submission_date ) ) : '-'; ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="ifs-date-cell">
                                        <span class="date-main"><?php echo ( $row->expected_delivery !== '1970-01-01' && ! empty( $row->expected_delivery ) ) ? date( 'd M Y', strtotime( $row->expected_delivery ) ) : '-'; ?></span>
                                    </div>
                                </td>
                                <td style="text-align: right; color: #64748b; font-family: ui-monospace, monospace;">
                                    ৳<?php echo number_format( $row->buy_price, 2 ); ?>
                                </td>
                                <td style="text-align: right; font-weight: 700; color: #0f172a; font-family: ui-monospace, monospace;">
                                    ৳<?php echo number_format( $row->sell_price, 2 ); ?>
                                </td>
                                <td style="text-align: right; font-weight: 800; font-family: ui-monospace, monospace;" class="<?php echo ( $row->profit >= 0 ) ? 'color-emerald' : 'color-rose'; ?>">
                                    ৳<?php echo number_format( $row->profit, 2 ); ?>
                                </td>
                                <td style="text-align: center;">
                                    <span class="ifs-status-badge <?php echo esc_attr( $status_class ); ?>">
                                        <?php echo esc_html( $row->status ); ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <div class="ifs-action-buttons">
                                        <a href="<?php echo esc_url( $base_url . '&sub=view&id=' . $row->id ); ?>" class="ifs-btn-action view" title="View Visa Dossier">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </a>
                                        <a href="<?php echo esc_url( $base_url . '&sub=edit&id=' . $row->id ); ?>" class="ifs-btn-action edit" title="Edit Visa File">
                                            <span class="dashicons dashicons-edit"></span>
                                        </a>
                                        <a href="<?php echo wp_nonce_url( $base_url . '&sub=delete&id=' . $row->id, 'delete_visa_' . $row->id ); ?>" 
                                           class="ifs-btn-action delete" 
                                           onclick="return confirm('Are you sure you want to permanently delete this visa application?');" 
                                           title="Delete Record">
                                            <span class="dashicons dashicons-trash"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr>
                                <td colspan="11" class="ifs-empty-table">
                                    <div class="ifs-empty-state">
                                        <span class="dashicons dashicons-id-alt"></span>
                                        <h4>No Visa Applications Recorded</h4>
                                        <p>Start processing traveler visa files and tracking embassy approvals.</p>
                                        <a href="<?php echo esc_url( $base_url . '&sub=add' ); ?>" class="ifs-btn-primary" style="margin-top: 10px;">
                                            <span class="dashicons dashicons-plus-alt2"></span> New Visa File
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
        .ifs-visa-list-workspace {
            max-width: 1440px;
            margin: 0 auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #0f172a;
        }

        /* Toast Notifications */
        .ifs-toast {
            padding: 14px 20px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 22px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        .ifs-toast.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .ifs-toast.danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .ifs-toast .dashicons { font-size: 18px; width: 18px; height: 18px; }

        /* Metric Counter Ribbon */
        .ifs-list-metric-ribbon {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }
        .ifs-metric-chip {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 8px -2px rgba(15, 23, 42, 0.03);
        }
        .chip-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            flex-shrink: 0;
        }
        .chip-icon.bg-blue   { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); }
        .chip-icon.bg-amber  { background: linear-gradient(135deg, #d97706 0%, #b45309 100%); }
        .chip-icon.bg-emerald{ background: linear-gradient(135deg, #059669 0%, #047857 100%); }
        .chip-icon.bg-indigo { background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); }
        .chip-icon .dashicons { font-size: 22px; width: 22px; height: 22px; }

        .chip-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; display: block; margin-bottom: 2px; }
        .chip-val { font-size: 19px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
        .color-blue { color: #003376 !important; }
        .color-emerald { color: #059669 !important; }
        .color-amber { color: #d97706 !important; }
        .color-rose { color: #e11d48 !important; }

        /* Master Table Card */
        .ifs-table-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
            overflow: hidden;
        }
        .ifs-table-top-bar {
            padding: 22px 26px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f1f5f9;
            flex-wrap: wrap;
            gap: 15px;
        }
        .ifs-table-heading { margin: 0; font-size: 17px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        .ifs-table-heading .dashicons { color: #0284c7; font-size: 20px; width: 20px; height: 20px; }
        .ifs-table-caption { margin: 3px 0 0 0; font-size: 13px; color: #64748b; }

        .ifs-btn-primary {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #ffffff !important;
            border: none;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
            transition: all 0.2s ease;
        }
        .ifs-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(2, 132, 199, 0.35);
        }

        /* Table Architecture */
        .ifs-table-responsive-wrapper {
            padding: 15px 24px 24px 24px;
            overflow-x: auto;
        }
        .ifs-pro-datatable {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .ifs-pro-datatable thead th {
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 14px;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
            white-space: nowrap;
        }
        .ifs-pro-datatable tbody td {
            padding: 13px 14px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: #334155;
        }
        .ifs-pro-datatable tbody tr:hover td {
            background: #f8fafc;
        }

        /* Tokens & Cells */
        .ifs-id-badge {
            background: #f1f5f9;
            color: #475569;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-weight: 700;
            font-size: 11px;
            padding: 3px 6px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            display: inline-block;
        }

        .ifs-passenger-name {
            font-weight: 700;
            color: #0f172a;
            text-decoration: none;
            font-size: 13px;
            transition: color 0.15s ease;
        }
        .ifs-passenger-name:hover { color: #0284c7; }
        .ifs-passenger-submeta {
            font-size: 11px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 2px;
        }
        .meta-dot { width: 3px; height: 3px; border-radius: 50%; background: #cbd5e1; }

        .ifs-agent-tag { background: #eef2ff; color: #4338ca; font-size: 10px; font-weight: 700; padding: 1px 5px; border-radius: 4px; display: inline-flex; align-items: center; gap: 3px; }
        .ifs-agent-tag .dashicons { font-size: 11px; width: 11px; height: 11px; }
        .ifs-direct-tag { font-size: 10.5px; color: #059669; font-weight: 600; }

        .ifs-country-cell { display: flex; flex-direction: column; gap: 2px; }
        .country-title { font-size: 13px; color: #0f172a; display: inline-flex; align-items: center; gap: 4px; }
        .country-title .dashicons { font-size: 14px; width: 14px; height: 14px; color: #0284c7; }
        .visa-meta { font-size: 11px; color: #64748b; display: flex; align-items: center; gap: 6px; margin-top: 1px; }
        .visa-type-tag { background: #e0f2fe; color: #0369a1; padding: 1px 5px; border-radius: 3px; font-size: 10px; font-weight: 600; }
        .entry-tag { background: #f1f5f9; padding: 1px 4px; border-radius: 3px; font-size: 9.5px; }

        .ifs-tracking-pill { background: #f8fafc; border: 1px solid #e2e8f0; color: #003376; font-weight: 700; font-size: 11px; padding: 2px 6px; border-radius: 4px; width: fit-content; }

        .ifs-date-cell .date-main { font-weight: 600; color: #0f172a; font-size: 12px; }

        /* Status Badges */
        .ifs-status-badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-processing { background: #fef3c7; color: #b45309; }
        .status-approved   { background: #dcfce7; color: #15803d; }
        .status-delivered  { background: #e0f2fe; color: #0369a1; }
        .status-rejected   { background: #fee2e2; color: #b91c1c; }

        /* Actions */
        .ifs-action-buttons { display: flex; gap: 5px; justify-content: flex-end; }
        .ifs-btn-action {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .ifs-btn-action.view   { background: #f1f5f9; color: #475569; }
        .ifs-btn-action.view:hover { background: #e2e8f0; color: #0f172a; }
        .ifs-btn-action.edit   { background: #eff6ff; color: #2563eb; }
        .ifs-btn-action.edit:hover { background: #dbeafe; color: #1d4ed8; }
        .ifs-btn-action.delete { background: #fef2f2; color: #dc2626; }
        .ifs-btn-action.delete:hover { background: #fee2e2; color: #b91c1c; }
        .ifs-btn-action .dashicons { font-size: 14px; width: 14px; height: 14px; }

        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }

        /* Empty State */
        .ifs-empty-table { text-align: center; padding: 50px 20px !important; }
        .ifs-empty-state .dashicons { font-size: 44px; width: 44px; height: 44px; color: #cbd5e1; margin-bottom: 10px; }
        .ifs-empty-state h4 { margin: 0 0 4px 0; font-size: 16px; font-weight: 700; color: #334155; }
        .ifs-empty-state p { margin: 0; color: #94a3b8; font-size: 13px; }

        /* DataTables Custom Polish */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter { margin-bottom: 16px; font-size: 13px; color: #475569; }
        .dataTables_wrapper .dataTables_filter input { border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 12px; margin-left: 8px; outline: none; font-size: 13px; }
        .dataTables_wrapper .dataTables_filter input:focus { border-color: #0284c7; box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12); }
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate { margin-top: 18px; font-size: 13px; color: #64748b; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: #0284c7 !important; color: #ffffff !important; border: 1px solid #0284c7 !important; border-radius: 6px; font-weight: 700; }
    </style>

    <script>
    jQuery(document).ready(function($) {
        if ($.fn.DataTable) {
            $('#ifsVisaTable').DataTable({
                "pageLength": 15,
                "ordering": true,
                "info": true,
                "searching": true,
                "order": [[ 0, "desc" ]],
                "language": {
                    "search": "_INPUT_",
                    "searchPlaceholder": "Search Country, Tracking No, Applicant, Passport...",
                    "lengthMenu": "Show _MENU_ records",
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