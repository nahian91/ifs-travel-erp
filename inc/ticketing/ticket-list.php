<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enterprise Next-Gen Air Ticketing Ledger & Inventory Desk
 * Features: Live Summary Analytics, Dual PNR Badges, Financial Tokens, Dynamic Search, Batch Filters & Action Controls
 */
function ifs_terp_ticket_list_page() {
    global $wpdb;
    $table_tickets   = $wpdb->prefix . 'iterp_tickets';
    $table_customers = $wpdb->prefix . 'iterp_customers';
    $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
    $table_agents    = $wpdb->prefix . 'iterp_agents';
    $base_url        = admin_url( 'admin.php?page=ifs_travel_erp&tab=ticketing' );

    // Handle Delete Action with Nonce Protection
    if ( isset( $_GET['sub'] ) && $_GET['sub'] === 'delete' && isset( $_GET['id'] ) ) {
        $del_id = intval( $_GET['id'] );
        check_admin_referer( 'delete_ticket_' . $del_id );

        $tkt_info = $wpdb->get_row( $wpdb->prepare( "SELECT pnr, ticket_no FROM $table_tickets WHERE id = %d", $del_id ) );
        $wpdb->delete( $table_tickets, array( 'id' => $del_id ), array( '%d' ) );

        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            ifs_terp_log_activity( "Deleted Ticket ID #TKT-$del_id (PNR: " . ($tkt_info->pnr ?? '') . ")" );
        }

        wp_safe_redirect( add_query_arg( array( 'page' => 'ifs_travel_erp', 'tab' => 'ticketing', 'msg' => 'deleted' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    $message = '';
    if ( isset( $_GET['msg'] ) && $_GET['msg'] === 'deleted' ) {
        $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Air ticket record removed successfully.</div>';
    }

    // Dynamic High-Level Metric Aggregations
    $total_tickets  = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_tickets" );
    $total_sales    = (float) $wpdb->get_var( "SELECT SUM(sell_price) FROM $table_tickets WHERE status = 'Issued'" );
    $total_profit   = (float) $wpdb->get_var( "SELECT SUM(profit) FROM $table_tickets WHERE status = 'Issued'" );
    $refund_count   = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_tickets WHERE status IN ('Refunded', 'Void')" );

    $query = "
        SELECT t.*, 
               c.title AS customer_title, c.full_name AS customer_name, c.mobile AS customer_mobile, c.passport_no AS customer_passport,
               s.supplier_name,
               a.agency_name
        FROM $table_tickets t
        LEFT JOIN $table_customers c ON t.customer_id = c.id
        LEFT JOIN $table_suppliers s ON t.supplier_id = s.id
        LEFT JOIN $table_agents a ON t.agent_id = a.id
        ORDER BY t.id DESC
    ";
    $tickets = $wpdb->get_results( $query );
    ?>

    <div class="ifs-ticket-list-workspace">
        <?php echo $message; ?>

        <!-- Metric Counter Ribbon -->
        <div class="ifs-list-metric-ribbon">
            <div class="ifs-metric-chip">
                <div class="chip-icon bg-blue"><span class="dashicons dashicons-tickets-alt"></span></div>
                <div>
                    <span class="chip-label">Total Issued Tickets</span>
                    <strong class="chip-val"><?php echo number_format( $total_tickets ); ?></strong>
                </div>
            </div>
            <div class="ifs-metric-chip">
                <div class="chip-icon bg-indigo"><span class="dashicons dashicons-money-alt"></span></div>
                <div>
                    <span class="chip-label">Gross Air Turnover</span>
                    <strong class="chip-val color-blue">৳<?php echo number_format( $total_sales, 2 ); ?></strong>
                </div>
            </div>
            <div class="ifs-metric-chip">
                <div class="chip-icon bg-emerald"><span class="dashicons dashicons-chart-line"></span></div>
                <div>
                    <span class="chip-label">Net Ticket Profit</span>
                    <strong class="chip-val color-emerald">৳<?php echo number_format( $total_profit, 2 ); ?></strong>
                </div>
            </div>
            <div class="ifs-metric-chip">
                <div class="chip-icon bg-amber"><span class="dashicons dashicons-image-rotate"></span></div>
                <div>
                    <span class="chip-label">Refund / Void Files</span>
                    <strong class="chip-val color-amber"><?php echo number_format( $refund_count ); ?></strong>
                </div>
            </div>
        </div>

        <!-- Master Data Table Card -->
        <div class="ifs-table-card">
            <div class="ifs-table-top-bar">
                <div class="ifs-table-title-group">
                    <h3 class="ifs-table-heading"><span class="dashicons dashicons-airplane"></span> Air Tickets Ledger & Inventory</h3>
                    <p class="ifs-table-caption">GDS issued e-tickets, passenger manifests, carrier routings, and commercial margin statements</p>
                </div>
                <div class="ifs-table-btn-group">
                    <a href="<?php echo esc_url( $base_url . '&sub=add' ); ?>" class="ifs-btn-primary">
                        <span class="dashicons dashicons-plus-alt2"></span> Issue New Air Ticket
                    </a>
                </div>
            </div>

            <div class="ifs-table-responsive-wrapper">
                <table class="ifs-pro-datatable" id="ifsTicketTable">
                    <thead>
                        <tr>
                            <th style="width: 80px;">TKT ID</th>
                            <th>Passenger & Client Info</th>
                            <th>GDS / Airline PNR</th>
                            <th>E-Ticket Number</th>
                            <th>Carrier & Sector</th>
                            <th>Travel Schedule</th>
                            <th style="text-align: right;">Cost (৳)</th>
                            <th style="text-align: right;">Sell (৳)</th>
                            <th style="text-align: right;">Net Profit (৳)</th>
                            <th style="text-align: center;">Payment</th>
                            <th style="text-align: center;">Status</th>
                            <th style="text-align: right; width: 150px;">Quick Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $tickets ) : foreach ( $tickets as $row ) : 
                            $status_class = 'status-issued';
                            $status_lower = strtolower( $row->status );
                            if ( $status_lower === 'refunded' )  $status_class = 'status-refunded';
                            elseif ( $status_lower === 'void' )  $status_class = 'status-void';
                            elseif ( $status_lower === 'reissued' ) $status_class = 'status-reissued';

                            // Fallback snapshot resolution
                            $title_prefix = ! empty( $row->customer_title ) ? esc_html( $row->customer_title ) . '. ' : '';
                            $pax_name     = ! empty( $row->passenger_name ) ? esc_html( $row->passenger_name ) : ( ! empty( $row->customer_name ) ? $title_prefix . esc_html( $row->customer_name ) : 'Guest Passenger' );
                            $pax_mobile   = esc_html( $row->customer_mobile ?? '' );
                            $gds_label    = ! empty( $row->gds_pcc ) ? esc_html( $row->gds_pcc ) : 'GDS';

                            // Payment Status Pill
                            $pay_status = $row->payment_status ?? 'Paid';
                            $pay_class  = ( $pay_status === 'Paid' ) ? 'pay-paid' : ( ( $pay_status === 'Partial' ) ? 'pay-partial' : 'pay-due' );

                            // Channel tag: Direct vs B2B Agent
                            $channel_tag = ! empty( $row->agency_name ) ? '<span class="ifs-agent-tag"><span class="dashicons dashicons-groups"></span> ' . esc_html( $row->agency_name ) . '</span>' : '<span class="ifs-direct-tag">Direct Retail</span>';
                        ?>
                            <tr>
                                <td>
                                    <span class="ifs-id-badge">#TKT-<?php echo str_pad( (string) $row->id, 5, '0', STR_PAD_LEFT ); ?></span>
                                </td>
                                <td>
                                    <div class="ifs-passenger-cell">
                                        <div>
                                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=customers&sub=view&id=' . $row->customer_id ) ); ?>" class="ifs-passenger-name">
                                                <?php echo $pax_name; ?>
                                            </a>
                                            <div class="ifs-passenger-submeta">
                                                <span><?php echo $pax_mobile; ?></span>
                                                <span class="meta-dot"></span>
                                                <?php echo $channel_tag; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="ifs-pnr-cell">
                                        <div style="display: flex; align-items: center; gap: 4px;">
                                            <span class="ifs-pnr-pill font-mono"><?php echo esc_html( $row->pnr ); ?></span>
                                            <?php if ( ! empty( $row->airline_pnr ) ) : ?>
                                                <span class="ifs-airpnr-pill font-mono" title="Airline PNR"><?php echo esc_html( $row->airline_pnr ); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="ifs-gds-source"><?php echo $gds_label; ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="ifs-tkt-no font-mono"><?php echo esc_html( $row->ticket_no ); ?></span>
                                </td>
                                <td>
                                    <div class="ifs-routing-cell">
                                        <strong class="airline-title"><?php echo esc_html( $row->airline ); ?></strong>
                                        <div class="routing-meta font-mono">
                                            <span class="sector-code"><?php echo esc_html( $row->sector ); ?></span>
                                            <span class="cabin-tag"><?php echo esc_html( $row->cabin_class ); ?></span>
                                            <?php if ( ! empty( $row->via_transit ) && $row->via_transit !== 'Direct' ) : ?>
                                                <span class="transit-tag"><?php echo esc_html( $row->via_transit ); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="ifs-date-cell">
                                        <span class="flight-date"><?php echo date( 'd M Y', strtotime( $row->travel_date ) ); ?></span>
                                        <?php if ( ! empty( $row->flight_time ) ) : ?>
                                            <span class="flight-time font-mono"><span class="dashicons dashicons-clock"></span> <?php echo esc_html( $row->flight_time ); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="text-align: right; color: #64748b; font-family: ui-monospace, monospace;">
                                    ৳<?php echo number_format( (float) $row->buy_price, 2 ); ?>
                                </td>
                                <td style="text-align: right; font-weight: 700; color: #0f172a; font-family: ui-monospace, monospace;">
                                    ৳<?php echo number_format( (float) $row->sell_price, 2 ); ?>
                                </td>
                                <td style="text-align: right; font-weight: 800; font-family: ui-monospace, monospace;" class="<?php echo ( $row->profit >= 0 ) ? 'color-emerald' : 'color-rose'; ?>">
                                    ৳<?php echo number_format( (float) $row->profit, 2 ); ?>
                                </td>
                                <td style="text-align: center;">
                                    <span class="ifs-pay-badge <?php echo esc_attr( $pay_class ); ?>">
                                        <?php echo esc_html( $pay_status ); ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="ifs-status-badge <?php echo esc_attr( $status_class ); ?>">
                                        <?php echo esc_html( $row->status ); ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <div class="ifs-action-pills">
                                        <a href="<?php echo esc_url( $base_url . '&sub=view&id=' . $row->id ); ?>" class="ifs-action-pill view" title="View E-Ticket Voucher">
                                            <span class="dashicons dashicons-visibility"></span> View
                                        </a>
                                        <a href="<?php echo esc_url( $base_url . '&sub=edit&id=' . $row->id ); ?>" class="ifs-action-pill edit" title="Edit Ticket Record">
                                            <span class="dashicons dashicons-edit"></span> Edit
                                        </a>
                                        <a href="<?php echo wp_nonce_url( $base_url . '&sub=delete&id=' . $row->id, 'delete_ticket_' . $row->id ); ?>" 
                                           class="ifs-action-pill delete" 
                                           onclick="return confirm('Are you sure you want to permanently delete this ticket record?');" 
                                           title="Delete Record">
                                            <span class="dashicons dashicons-trash"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr>
                                <td colspan="12" class="ifs-empty-table">
                                    <div class="ifs-empty-state">
                                        <span class="dashicons dashicons-tickets-alt"></span>
                                        <h4>No Air Ticket Records Found</h4>
                                        <p>Issue your first GDS flight ticket to initialize passenger manifest tracking.</p>
                                        <a href="<?php echo esc_url( $base_url . '&sub=add' ); ?>" class="ifs-btn-primary" style="margin-top: 10px;">
                                            <span class="dashicons dashicons-plus-alt2"></span> Issue Air Ticket
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
        .ifs-ticket-list-workspace {
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
        .chip-icon.bg-blue   { background: linear-gradient(135deg, #003376 0%, #0284c7 100%); }
        .chip-icon.bg-indigo { background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); }
        .chip-icon.bg-emerald{ background: linear-gradient(135deg, #059669 0%, #047857 100%); }
        .chip-icon.bg-amber  { background: linear-gradient(135deg, #d97706 0%, #b45309 100%); }
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
        .ifs-table-heading .dashicons { color: #003376; font-size: 20px; width: 20px; height: 20px; }
        .ifs-table-caption { margin: 3px 0 0 0; font-size: 13px; color: #64748b; }

        .ifs-btn-primary {
            background: linear-gradient(135deg, #003376 0%, #0284c7 100%);
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
            box-shadow: 0 4px 12px rgba(0, 51, 118, 0.2);
            transition: all 0.2s ease;
        }
        .ifs-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(0, 51, 118, 0.3);
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

        /* Cells & Visual Tokens */
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
        .ifs-passenger-name:hover { color: #003376; }
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

        .ifs-pnr-cell { display: flex; flex-direction: column; gap: 2px; }
        .ifs-pnr-pill { background: #e0f2fe; color: #003376; font-weight: 800; font-size: 11.5px; padding: 2px 6px; border-radius: 4px; width: fit-content; }
        .ifs-airpnr-pill { background: #f1f5f9; color: #475569; font-weight: 700; font-size: 10px; padding: 2px 5px; border-radius: 4px; border: 1px solid #e2e8f0; }
        .ifs-gds-source { font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 600; }

        .ifs-tkt-no { font-size: 12.5px; font-weight: 700; color: #0f172a; }

        .ifs-routing-cell { display: flex; flex-direction: column; gap: 2px; }
        .airline-title { font-size: 12.5px; color: #0f172a; }
        .routing-meta { font-size: 11px; color: #64748b; display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }
        .sector-code { font-weight: 700; color: #0284c7; }
        .cabin-tag { background: #f1f5f9; padding: 1px 4px; border-radius: 3px; font-size: 9.5px; }
        .transit-tag { background: #fef3c7; color: #92400e; padding: 1px 4px; border-radius: 3px; font-size: 9.5px; font-weight: 700; }

        .ifs-date-cell { display: flex; flex-direction: column; gap: 2px; }
        .flight-date { font-weight: 600; color: #0f172a; font-size: 12px; }
        .flight-time { font-size: 10.5px; color: #64748b; display: inline-flex; align-items: center; gap: 2px; }
        .flight-time .dashicons { font-size: 12px; width: 12px; height: 12px; }

        /* Payment Badges */
        .ifs-pay-badge {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            padding: 2px 6px;
            border-radius: 4px;
            display: inline-block;
        }
        .pay-paid    { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .pay-partial { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .pay-due     { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

        /* Status Pills */
        .ifs-status-badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-issued   { background: #dcfce7; color: #15803d; }
        .status-reissued { background: #e0f2fe; color: #0369a1; }
        .status-refunded { background: #ffedd5; color: #9a3412; }
        .status-void     { background: #fee2e2; color: #b91c1c; }

        /* Action Buttons */
        .ifs-action-pills {
            display: flex;
            gap: 4px;
            justify-content: flex-end;
            align-items: center;
        }
        .ifs-action-pill {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease;
            border: 1px solid transparent;
            white-space: nowrap;
        }
        .ifs-action-pill.view { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }
        .ifs-action-pill.view:hover { background: #e2e8f0; color: #0f172a; }
        .ifs-action-pill.edit { background: #eff6ff; color: #2563eb; border-color: #dbeafe; }
        .ifs-action-pill.edit:hover { background: #dbeafe; color: #1d4ed8; }
        .ifs-action-pill.delete { background: #fef2f2; color: #dc2626; border-color: #fee2e2; padding: 4px 6px; }
        .ifs-action-pill.delete:hover { background: #fee2e2; color: #b91c1c; }
        .ifs-action-pill .dashicons { font-size: 12px; width: 12px; height: 12px; }

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
        .dataTables_wrapper .dataTables_filter input:focus { border-color: #003376; box-shadow: 0 0 0 3px rgba(0, 51, 118, 0.12); }
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate { margin-top: 18px; font-size: 13px; color: #64748b; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: #003376 !important; color: #ffffff !important; border: 1px solid #003376 !important; border-radius: 6px; font-weight: 700; }
    </style>

    <script>
    jQuery(document).ready(function($) {
        if ($.fn.DataTable) {
            $('#ifsTicketTable').DataTable({
                "pageLength": 15,
                "ordering": true,
                "info": true,
                "searching": true,
                "order": [[ 0, "desc" ]],
                "language": {
                    "search": "_INPUT_",
                    "searchPlaceholder": "Search PNR, Ticket No, Passenger, Airline...",
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