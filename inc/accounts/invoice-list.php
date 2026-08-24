<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enterprise Next-Gen Sales Invoices & Billing Register
 * Features: Metric Stat Ribbons, Live Custom Search, Per-Page Selector, Optimized Single-Query Joins & DataTables Controls
 */
function ifs_terp_invoice_list_page() {
    global $wpdb;
    $table_invoices  = $wpdb->prefix . 'iterp_invoices';
    $table_customers = $wpdb->prefix . 'iterp_customers';
    $table_agents    = $wpdb->prefix . 'iterp_agents';

    // Handle Delete Action with Nonce Verification
    if ( isset( $_GET['sub'] ) && $_GET['sub'] === 'delete' && isset( $_GET['id'] ) ) {
        $del_id = intval( $_GET['id'] );
        check_admin_referer( 'delete_invoice_' . $del_id );

        $inv_no = $wpdb->get_var( $wpdb->prepare( "SELECT invoice_no FROM $table_invoices WHERE id = %d", $del_id ) );
        $wpdb->delete( $table_invoices, array( 'id' => $del_id ), array( '%d' ) );

        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            ifs_terp_log_activity( "Deleted Sales Invoice #{$inv_no} (Record ID #{$del_id})" );
        }

        wp_safe_redirect( add_query_arg( array( 'page' => 'ifs_travel_erp', 'tab' => 'accounts', 'msg' => 'deleted' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    $message = '';
    if ( isset( $_GET['msg'] ) && $_GET['msg'] === 'deleted' ) {
        $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Invoice record removed successfully.</div>';
    }

    // Dynamic Summary Aggregations
    $total_invoices   = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_invoices" );
    $total_billed     = (float) $wpdb->get_var( "SELECT COALESCE(SUM(net_total), 0) FROM $table_invoices" );
    $total_collected  = (float) $wpdb->get_var( "SELECT COALESCE(SUM(paid_amount), 0) FROM $table_invoices" );
    $total_due        = (float) $wpdb->get_var( "SELECT COALESCE(SUM(due_amount), 0) FROM $table_invoices" );

    // Optimized Single-Query with Conditional Joins
    $sql_invoices = "
        SELECT 
            i.*,
            CASE 
                WHEN i.client_type = 'Customer' THEN c.full_name 
                WHEN i.client_type = 'Agent' THEN a.agency_name 
                ELSE 'Direct Client' 
            END AS client_name,
            CASE 
                WHEN i.client_type = 'Customer' THEN c.mobile 
                WHEN i.client_type = 'Agent' THEN a.mobile 
                ELSE '' 
            END AS client_phone
        FROM $table_invoices i
        LEFT JOIN $table_customers c ON (i.client_type = 'Customer' AND i.client_id = c.id)
        LEFT JOIN $table_agents a ON (i.client_type = 'Agent' AND i.client_id = a.id)
        ORDER BY i.id DESC
    ";
    $invoices = $wpdb->get_results( $sql_invoices );
    $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=accounts' );
    ?>

    <div class="wrap ifs-invoice-list-workspace">
        <?php echo $message; ?>

        <!-- Metric Counter Ribbon -->
        <div class="ifs-list-metric-ribbon">
            <div class="ifs-metric-chip border-blue">
                <div class="chip-icon bg-blue"><span class="dashicons dashicons-media-spreadsheet"></span></div>
                <div>
                    <span class="chip-label">Total Invoiced Sales</span>
                    <strong class="chip-val color-blue font-mono">৳<?php echo number_format( $total_billed, 2 ); ?></strong>
                    <span class="chip-sub"><?php echo number_format( $total_invoices ); ?> Invoices Generated</span>
                </div>
            </div>

            <div class="ifs-metric-chip border-emerald">
                <div class="chip-icon bg-emerald"><span class="dashicons dashicons-money-alt"></span></div>
                <div>
                    <span class="chip-label">Collected Payments</span>
                    <strong class="chip-val color-emerald font-mono">৳<?php echo number_format( $total_collected, 2 ); ?></strong>
                    <span class="chip-sub">Verified Cash Inflow</span>
                </div>
            </div>

            <div class="ifs-metric-chip border-rose">
                <div class="chip-icon bg-rose"><span class="dashicons dashicons-warning"></span></div>
                <div>
                    <span class="chip-label">Total Outstanding Due</span>
                    <strong class="chip-val color-rose font-mono">৳<?php echo number_format( $total_due, 2 ); ?></strong>
                    <span class="chip-sub">Unsettled Receivables</span>
                </div>
            </div>
        </div>

        <!-- Master Data Table Card -->
        <div class="ifs-table-card">
            <div class="ifs-table-top-bar">
                <div class="ifs-table-title-group">
                    <h3 class="ifs-table-heading"><span class="dashicons dashicons-media-text"></span> Sales Invoices &amp; Billing Register</h3>
                    <p class="ifs-table-caption">Manage client bills, dynamic money receipts, collection status, and invoice printing dossiers.</p>
                </div>
                <div class="ifs-table-btn-group">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=accounts&sub=create_invoice' ) ); ?>" class="ifs-btn-primary">
                        <span class="dashicons dashicons-plus-alt2"></span> Create New Invoice
                    </a>
                </div>
            </div>

            <!-- Custom DataTables Controls Toolbar -->
            <div class="ifs-custom-table-controls">
                <div class="ifs-per-page-wrap">
                    <label for="ifsInvoicePerPageSelect">Show</label>
                    <select id="ifsInvoicePerPageSelect" class="ifs-select-control">
                        <option value="10">10</option>
                        <option value="15" selected>15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span>entries</span>
                </div>
                <div class="ifs-live-search-wrap">
                    <label for="ifsInvoiceSearchInput"><span class="dashicons dashicons-search"></span></label>
                    <input type="text" id="ifsInvoiceSearchInput" class="ifs-search-input" placeholder="Search by invoice no, client name, phone, status...">
                </div>
            </div>

            <div class="ifs-table-responsive-wrapper">
                <table class="ifs-pro-datatable" id="ifsInvoiceTable">
                    <thead>
                        <tr>
                            <th style="width: 130px;">Invoice No</th>
                            <th style="width: 100px;">Client Type</th>
                            <th>Billed Recipient Details</th>
                            <th style="text-align: right;">Net Total (৳)</th>
                            <th style="text-align: right;">Paid (৳)</th>
                            <th style="text-align: right;">Balance Due (৳)</th>
                            <th style="text-align: center; width: 100px;">Status</th>
                            <th>Date Issued</th>
                            <th style="text-align: right; width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $invoices ) : foreach ( $invoices as $inv ) : 
                            $status_lower = strtolower( $inv->payment_status );
                            $status_class = 'badge-unpaid';
                            if ( $status_lower === 'paid' ) $status_class = 'badge-paid';
                            elseif ( $status_lower === 'partial' ) $status_class = 'badge-partial';

                            $type_badge = ( $inv->client_type === 'Agent' ) ? 'type-agent' : 'type-customer';
                        ?>
                            <tr>
                                <td>
                                    <span class="ifs-invoice-num font-mono"><?php echo esc_html( $inv->invoice_no ); ?></span>
                                </td>
                                <td>
                                    <span class="ifs-type-pill <?php echo esc_attr( $type_badge ); ?>">
                                        <?php echo esc_html( $inv->client_type ); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="ifs-client-cell">
                                        <strong class="client-name"><?php echo esc_html( $inv->client_name ?: 'Direct Client' ); ?></strong>
                                        <?php if ( ! empty( $inv->client_phone ) ) : ?>
                                            <span class="client-phone font-mono"><span class="dashicons dashicons-phone"></span> <?php echo esc_html( $inv->client_phone ); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="text-align: right;" class="font-mono font-bold color-navy">
                                    ৳<?php echo number_format( (float) $inv->net_total, 2 ); ?>
                                </td>
                                <td style="text-align: right;" class="font-mono font-bold color-emerald">
                                    ৳<?php echo number_format( (float) $inv->paid_amount, 2 ); ?>
                                </td>
                                <td style="text-align: right;" class="font-mono font-bold <?php echo ( (float) $inv->due_amount > 0 ) ? 'color-rose' : 'color-muted'; ?>">
                                    ৳<?php echo number_format( (float) $inv->due_amount, 2 ); ?>
                                </td>
                                <td style="text-align: center;">
                                    <span class="ifs-status-badge <?php echo esc_attr( $status_class ); ?>">
                                        <?php echo esc_html( strtoupper( $inv->payment_status ) ); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="font-mono text-date"><?php echo date( 'd M Y', strtotime( $inv->created_at ) ); ?></span>
                                </td>
                                <td style="text-align: right;">
                                    <div class="ifs-action-pills">
                                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=accounts&sub=view_invoice&id=' . $inv->id ) ); ?>" class="ifs-action-pill view" title="View &amp; Print Dossier">
                                            <span class="dashicons dashicons-visibility"></span> View
                                        </a>
                                        <a href="<?php echo wp_nonce_url( $base_url . '&sub=delete&id=' . $inv->id, 'delete_invoice_' . $inv->id ); ?>" 
                                           class="ifs-action-pill delete" 
                                           onclick="return confirm('Are you sure you want to permanently delete this invoice? This action cannot be undone.');" 
                                           title="Delete Invoice">
                                            <span class="dashicons dashicons-trash"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr>
                                <td colspan="9" class="ifs-empty-table">
                                    <div class="ifs-empty-state">
                                        <span class="dashicons dashicons-media-spreadsheet"></span>
                                        <h4>No Sales Invoices Generated</h4>
                                        <p>Create your first client invoice and money receipt using the billing generator.</p>
                                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=accounts&sub=create_invoice' ) ); ?>" class="ifs-btn-primary" style="margin-top: 10px;">
                                            <span class="dashicons dashicons-plus-alt2"></span> Create New Invoice
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

    <!-- Ultra-Modern Stylesheet -->
    <style>
        .ifs-invoice-list-workspace {
            max-width: 1420px;
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
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }
        .ifs-metric-chip {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 18px 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 8px -2px rgba(15, 23, 42, 0.03);
            border-left: 5px solid #cbd5e1;
        }
        .ifs-metric-chip.border-blue    { border-left-color: #0284c7; }
        .ifs-metric-chip.border-emerald { border-left-color: #059669; }
        .ifs-metric-chip.border-rose    { border-left-color: #e11d48; }

        .chip-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            flex-shrink: 0;
        }
        .chip-icon.bg-blue    { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); }
        .chip-icon.bg-emerald { background: linear-gradient(135deg, #059669 0%, #047857 100%); }
        .chip-icon.bg-rose    { background: linear-gradient(135deg, #e11d48 0%, #be123c 100%); }
        .chip-icon .dashicons { font-size: 22px; width: 22px; height: 22px; }

        .chip-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; display: block; margin-bottom: 2px; }
        .chip-val { font-size: 20px; font-weight: 900; color: #0f172a; letter-spacing: -0.5px; }
        .chip-sub { font-size: 11px; color: #64748b; font-weight: 600; margin-top: 2px; display: block; }
        .color-blue    { color: #003376 !important; }
        .color-emerald { color: #059669 !important; }
        .color-rose    { color: #e11d48 !important; }
        .color-navy    { color: #0f172a !important; }
        .color-muted   { color: #94a3b8 !important; }

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

        /* Custom Table Controls */
        .ifs-custom-table-controls {
            padding: 16px 26px;
            background: #f8fafc;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .ifs-per-page-wrap { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #475569; font-weight: 600; }
        .ifs-select-control {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 5px 28px 5px 10px;
            font-size: 13px;
            color: #0f172a;
            background: #ffffff;
            outline: none;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 12px;
        }
        .ifs-select-control:focus { border-color: #003376; box-shadow: 0 0 0 3px rgba(0, 51, 118, 0.12); }

        .ifs-live-search-wrap { position: relative; display: flex; align-items: center; min-width: 280px; }
        .ifs-live-search-wrap label { position: absolute; left: 12px; color: #94a3b8; display: flex; align-items: center; pointer-events: none; }
        .ifs-live-search-wrap label .dashicons { font-size: 16px; width: 16px; height: 16px; }
        .ifs-search-input {
            width: 100%;
            padding: 7px 12px 7px 36px !important;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13px;
            color: #0f172a;
            background: #ffffff;
            outline: none;
            transition: all 0.2s ease;
        }
        .ifs-search-input:focus { border-color: #003376; box-shadow: 0 0 0 3px rgba(0, 51, 118, 0.12); }

        /* Table Architecture */
        .ifs-table-responsive-wrapper { padding: 15px 24px 24px 24px; overflow-x: auto; }
        .ifs-pro-datatable { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        .ifs-pro-datatable thead th {
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 16px;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
            white-space: nowrap;
        }
        .ifs-pro-datatable tbody td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: #334155;
        }
        .ifs-pro-datatable tbody tr:hover td { background: #f8fafc; }

        .ifs-invoice-num {
            color: #003376;
            font-weight: 800;
            font-size: 13px;
        }

        .ifs-type-pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        .type-customer { background: #eff6ff; color: #1d4ed8; }
        .type-agent    { background: #fdf4ff; color: #a21caf; }

        .ifs-client-cell { display: flex; flex-direction: column; gap: 2px; }
        .client-name { font-size: 13.5px; color: #0f172a; }
        .client-phone { font-size: 11px; color: #64748b; display: flex; align-items: center; gap: 3px; }
        .client-phone .dashicons { font-size: 12px; width: 12px; height: 12px; color: #0284c7; }

        .ifs-status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 10.5px;
            font-weight: 800;
            letter-spacing: 0.3px;
        }
        .badge-paid    { background: #dcfce7; color: #15803d; }
        .badge-partial { background: #fef3c7; color: #b45309; }
        .badge-unpaid  { background: #fee2e2; color: #b91c1c; }

        .text-date { font-size: 12px; color: #64748b; }

        .ifs-action-pills { display: flex; gap: 5px; justify-content: flex-end; align-items: center; }
        .ifs-action-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease;
            white-space: nowrap;
        }
        .ifs-action-pill.view { background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; }
        .ifs-action-pill.view:hover { background: #dbeafe; color: #1d4ed8; }
        .ifs-action-pill.delete { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; padding: 5px 8px; }
        .ifs-action-pill.delete:hover { background: #fee2e2; color: #b91c1c; }
        .ifs-action-pill .dashicons { font-size: 13px; width: 13px; height: 13px; margin-top: 1px; }

        .ifs-empty-table { text-align: center; padding: 50px 20px !important; }
        .ifs-empty-state .dashicons { font-size: 44px; width: 44px; height: 44px; color: #cbd5e1; margin-bottom: 10px; }
        .ifs-empty-state h4 { margin: 0 0 4px 0; font-size: 16px; font-weight: 700; color: #334155; }
        .ifs-empty-state p { margin: 0; color: #94a3b8; font-size: 13px; }

        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .font-bold { font-weight: 700; }

        /* DataTables Controls Integration */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter { display: none !important; }
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate { margin-top: 18px; font-size: 13px; color: #64748b; }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 6px !important;
            color: #334155 !important;
            padding: 6px 12px !important;
            margin-left: 4px;
            font-weight: 600;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #003376 !important;
            color: #ffffff !important;
            border: 1px solid #003376 !important;
            font-weight: 700;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #e2e8f0 !important;
            color: #0f172a !important;
        }
    </style>

    <!-- DataTables Engine Script -->
    <script>
    jQuery(document).ready(function($) {
        if ($.fn.DataTable) {
            var table = $('#ifsInvoiceTable').DataTable({
                "pageLength": 15,
                "ordering": true,
                "info": true,
                "searching": true,
                "lengthChange": false,
                "order": [[ 0, "desc" ]],
                "language": {
                    "info": "Showing _START_ to _END_ of _TOTAL_ billing records",
                    "infoEmpty": "Showing 0 to 0 of 0 records",
                    "infoFiltered": "(filtered from _MAX_ total records)",
                    "paginate": {
                        "previous": "&larr; Prev",
                        "next": "Next &rarr;"
                    }
                }
            });

            // Bind Custom Per Page Selector
            $('#ifsInvoicePerPageSelect').on('change', function() {
                var lengthVal = parseInt($(this).val());
                table.page.len(lengthVal).draw();
            });

            // Bind Custom Live Search Box
            $('#ifsInvoiceSearchInput').on('keyup', function() {
                table.search(this.value).draw();
            });
        }
    });
    </script>
    <?php
}