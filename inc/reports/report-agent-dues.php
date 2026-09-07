<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Enterprise Sub-Agent Due & Outstanding Ledger Report Page
 * Features: Flat Minimal UI, Zero Shadows, Strict 42px Control Heights,
 * Live Search, Dynamic Pagination, Credit Utilization Matrix, CSV Export & Executive Print View
 */
function ifs_terp_report_agent_dues_page() {
    global $wpdb;
    $table_agents = $wpdb->prefix . 'iterp_agents';

    // Handle CSV Export
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'export_csv' ) {
        if ( ! current_user_can( 'manage_options' ) && ! ifs_terp_has_access( array( 'accountant', 'admin_manager' ) ) ) {
            wp_die( esc_html__( 'Unauthorized export request.', 'ifs-travel-erp' ) );
        }

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=ifs-sub-agent-dues-report-' . date( 'Y-m-d' ) . '.csv' );

        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, array( 'Agent ID', 'Agency Name', 'Contact Person', 'Mobile Number', 'Email', 'Credit Limit (BDT)', 'Current Balance (BDT)', 'Outstanding Due (BDT)', 'Status' ) );

        $agents_for_export = $wpdb->get_results( "SELECT * FROM $table_agents WHERE current_balance < 0 ORDER BY current_balance ASC" );
        if ( $agents_for_export ) {
            foreach ( $agents_for_export as $agt ) {
                $due_val = abs( (float) $agt->current_balance );
                fputcsv( $output, array(
                    '#AGT-' . str_pad( (string) $agt->id, 5, '0', STR_PAD_LEFT ),
                    $agt->agency_name,
                    $agt->contact_person,
                    $agt->mobile,
                    $agt->email,
                    (float) $agt->credit_limit,
                    (float) $agt->current_balance,
                    $due_val,
                    $agt->status
                ) );
            }
        }

        fclose( $output );
        exit;
    }

    // Direct Database Aggregations
    $dues_list  = $wpdb->get_results( "SELECT * FROM $table_agents WHERE current_balance < 0 ORDER BY current_balance ASC" );
    $total_due  = (float) $wpdb->get_var( "SELECT COALESCE(SUM(current_balance), 0) FROM $table_agents WHERE current_balance < 0" );
    $total_agt  = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_agents WHERE current_balance < 0" );
    $total_cred = (float) $wpdb->get_var( "SELECT COALESCE(SUM(credit_limit), 0) FROM $table_agents WHERE current_balance < 0" );

    $base_url   = admin_url( 'admin.php?page=ifs_travel_erp&tab=reports&sub=agent_dues' );
    $export_url = add_query_arg( array( 'action' => 'export_csv' ), $base_url );
    ?>

    <div class="wrap ifs-agent-dues-wrapper">
        
        <!-- Header & Action Toolbar -->
        <div class="ifs-report-header">
            <div class="ifs-report-title-group">
                <span class="ifs-report-badge">
                    <span class="dashicons dashicons-id"></span> <?php esc_html_e( 'Credit Exposure & Dues Monitoring', 'ifs-travel-erp' ); ?>
                </span>
                <h2><?php esc_html_e( 'Sub-Agent Due & Outstanding Balance Ledger', 'ifs-travel-erp' ); ?></h2>
                <p><?php esc_html_e( 'Track B2B agency negative ledger accounts, credit limit exposure, and receivable collections in real time.', 'ifs-travel-erp' ); ?></p>
            </div>
            <div class="ifs-report-action-buttons">
                <a href="<?php echo esc_url( $export_url ); ?>" class="ifs-btn-secondary">
                    <span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export CSV', 'ifs-travel-erp' ); ?>
                </a>
                <button type="button" onclick="window.print();" class="ifs-btn-primary">
                    <span class="dashicons dashicons-printer"></span> <?php esc_html_e( 'Print Due Report', 'ifs-travel-erp' ); ?>
                </button>
            </div>
        </div>

        <!-- Metric KPI Cards Ribbon -->
        <div class="ifs-report-kpi-grid">
            <div class="ifs-kpi-chip border-rose">
                <div class="kpi-chip-icon bg-rose"><span class="dashicons dashicons-arrow-down-alt"></span></div>
                <div>
                    <span class="kpi-chip-lbl"><?php esc_html_e( 'Total Outstanding Dues', 'ifs-travel-erp' ); ?></span>
                    <strong class="kpi-chip-val color-rose font-mono">৳<?php echo number_format( abs( $total_due ), 2 ); ?></strong>
                    <span class="kpi-chip-sub"><?php esc_html_e( 'Receivable Credit Exposure', 'ifs-travel-erp' ); ?></span>
                </div>
            </div>

            <div class="ifs-kpi-chip border-blue">
                <div class="kpi-chip-icon bg-blue"><span class="dashicons dashicons-groups"></span></div>
                <div>
                    <span class="kpi-chip-lbl"><?php esc_html_e( 'Overdue Agencies', 'ifs-travel-erp' ); ?></span>
                    <strong class="kpi-chip-val color-blue font-mono"><?php printf( esc_html__( '%s Agencies', 'ifs-travel-erp' ), number_format( $total_agt ) ); ?></strong>
                    <span class="kpi-chip-sub"><?php esc_html_e( 'Accounts with Negative Balance', 'ifs-travel-erp' ); ?></span>
                </div>
            </div>

            <div class="ifs-kpi-chip border-emerald">
                <div class="kpi-chip-icon bg-emerald"><span class="dashicons dashicons-shield"></span></div>
                <div>
                    <span class="kpi-chip-lbl"><?php esc_html_e( 'Authorized Credit Limit', 'ifs-travel-erp' ); ?></span>
                    <strong class="kpi-chip-val color-emerald font-mono">৳<?php echo number_format( $total_cred, 2 ); ?></strong>
                    <span class="kpi-chip-sub"><?php esc_html_e( 'Total Overdraft Ceiling', 'ifs-travel-erp' ); ?></span>
                </div>
            </div>
        </div>

        <!-- Master Data Table Card -->
        <div class="ifs-report-table-card">
            <div class="ifs-table-card-head">
                <h3 class="table-card-heading">
                    <span class="dashicons dashicons-warning" style="color: #dc2626;"></span> <?php esc_html_e( 'B2B Sub-Agent Negative Ledger Balances', 'ifs-travel-erp' ); ?>
                </h3>
                <span class="table-card-date-badge">
                    <?php printf( esc_html__( '%d Overdue Accounts', 'ifs-travel-erp' ), $total_agt ); ?>
                </span>
            </div>

            <!-- Custom Controls Toolbar -->
            <div class="ifs-custom-table-controls">
                <div class="ifs-per-page-wrap">
                    <label for="ifsDuesPerPageSelect"><?php esc_html_e( 'Show', 'ifs-travel-erp' ); ?></label>
                    <select id="ifsDuesPerPageSelect" class="ifs-select-control">
                        <option value="10">10</option>
                        <option value="15" selected>15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span><?php esc_html_e( 'entries', 'ifs-travel-erp' ); ?></span>
                </div>
                <div class="ifs-live-search-wrap">
                    <label for="ifsDuesSearchInput"><span class="dashicons dashicons-search"></span></label>
                    <input type="text" id="ifsDuesSearchInput" class="ifs-search-input" placeholder="<?php esc_attr_e( 'Search by agency name, mobile, contact...', 'ifs-travel-erp' ); ?>">
                </div>
            </div>

            <div class="ifs-table-responsive">
                <table class="ifs-report-datatable" id="ifsAgentDuesTable">
                    <thead>
                        <tr>
                            <th style="width: 100px;"><?php esc_html_e( 'Agent ID', 'ifs-travel-erp' ); ?></th>
                            <th><?php esc_html_e( 'Agency Information', 'ifs-travel-erp' ); ?></th>
                            <th><?php esc_html_e( 'Contact Person', 'ifs-travel-erp' ); ?></th>
                            <th><?php esc_html_e( 'Mobile Number', 'ifs-travel-erp' ); ?></th>
                            <th style="text-align: right;"><?php esc_html_e( 'Credit Limit (৳)', 'ifs-travel-erp' ); ?></th>
                            <th style="text-align: right;"><?php esc_html_e( 'Total Due Amount (৳)', 'ifs-travel-erp' ); ?></th>
                            <th style="text-align: center; width: 130px;"><?php esc_html_e( 'Credit Status', 'ifs-travel-erp' ); ?></th>
                            <th style="text-align: right; width: 140px;"><?php esc_html_e( 'Quick Actions', 'ifs-travel-erp' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $dues_list ) : foreach ( $dues_list as $agt ) : 
                            $due_amt       = abs( (float) $agt->current_balance );
                            $cred_limit    = (float) $agt->credit_limit;
                            $is_over_limit = ( $cred_limit > 0 && $due_amt > $cred_limit );
                        ?>
                            <tr>
                                <td>
                                    <span class="ifs-id-badge font-mono">#AGT-<?php echo str_pad( (string) $agt->id, 5, '0', STR_PAD_LEFT ); ?></span>
                                </td>
                                <td>
                                    <div class="agency-title-cell">
                                        <strong><?php echo esc_html( $agt->agency_name ); ?></strong>
                                        <span class="agency-email"><?php echo esc_html( $agt->email ?: __( 'No email registered', 'ifs-travel-erp' ) ); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="contact-name"><?php echo esc_html( $agt->contact_person ); ?></span>
                                </td>
                                <td>
                                    <span class="font-mono"><?php echo esc_html( $agt->mobile ); ?></span>
                                </td>
                                <td style="text-align: right;" class="font-mono color-slate font-bold">
                                    ৳<?php echo number_format( $cred_limit, 2 ); ?>
                                </td>
                                <td style="text-align: right;" class="font-mono color-rose font-bold due-amount-cell">
                                    -৳<?php echo number_format( $due_amt, 2 ); ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ( $is_over_limit ) : ?>
                                        <span class="ifs-status-badge badge-limit-exceeded"><?php esc_html_e( 'Over Limit', 'ifs-travel-erp' ); ?></span>
                                    <?php else : ?>
                                        <span class="ifs-status-badge badge-within-limit"><?php esc_html_e( 'Within Limit', 'ifs-travel-erp' ); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <div class="ifs-action-pills">
                                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=b2b_agents&sub=ledger&id=' . $agt->id ) ); ?>" class="ifs-action-pill ledger" title="<?php esc_attr_e( 'View Statement & Settle', 'ifs-travel-erp' ); ?>">
                                            <span class="dashicons dashicons-money-alt"></span> <?php esc_html_e( 'Ledger', 'ifs-travel-erp' ); ?>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr>
                                <td colspan="8" class="ifs-empty-table">
                                    <div class="ifs-empty-state">
                                        <span class="dashicons dashicons-yes-alt"></span>
                                        <h4><?php esc_html_e( 'Zero Outstanding Dues', 'ifs-travel-erp' ); ?></h4>
                                        <p><?php esc_html_e( 'All B2B sub-agent accounts are fully settled or maintaining positive credit balances.', 'ifs-travel-erp' ); ?></p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Stylesheet: Flat Minimal UI, Zero Shadows & Strict 42px Control Heights -->
    <style>
        .ifs-agent-dues-wrapper {
            max-width: 1420px;
            margin: 20px auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #0f172a;
            box-sizing: border-box;
        }
        .ifs-agent-dues-wrapper *,
        .ifs-agent-dues-wrapper *::before,
        .ifs-agent-dues-wrapper *::after {
            box-sizing: border-box;
            box-shadow: none !important;
            text-shadow: none !important;
        }

        .ifs-report-header {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 24px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 18px;
            margin-bottom: 24px;
        }
        .ifs-report-badge {
            background: #fef2f2;
            color: #dc2626;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 6px;
            border: 1px solid #fecaca;
        }
        .ifs-report-badge .dashicons { font-size: 14px; width: 14px; height: 14px; }
        .ifs-report-title-group h2 { margin: 0 0 4px 0; font-size: 20px; font-weight: 800; color: #0f172a; }
        .ifs-report-title-group p { margin: 0; font-size: 13px; color: #64748b; }

        .ifs-report-action-buttons { display: flex; gap: 10px; align-items: center; }
        .ifs-btn-secondary {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #475569 !important;
            height: 42px;
            padding: 0 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        .ifs-btn-secondary:hover { background: #f1f5f9; color: #0f172a; }
        .ifs-btn-primary {
            background: #003376;
            color: #ffffff !important;
            border: none;
            height: 42px;
            padding: 0 24px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s ease;
        }
        .ifs-btn-primary:hover { background: #0284c7; }
        .ifs-btn-primary .dashicons, .ifs-btn-secondary .dashicons { font-size: 16px; width: 16px; height: 16px; }

        /* KPI Grid */
        .ifs-report-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }
        .ifs-kpi-chip {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 18px 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            border-left: 5px solid #cbd5e1;
        }
        .ifs-kpi-chip.border-blue    { border-left-color: #003376; }
        .ifs-kpi-chip.border-emerald { border-left-color: #059669; }
        .ifs-kpi-chip.border-rose    { border-left-color: #dc2626; }

        .kpi-chip-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            flex-shrink: 0;
        }
        .kpi-chip-icon.bg-blue    { background: #003376; }
        .kpi-chip-icon.bg-emerald { background: #059669; }
        .kpi-chip-icon.bg-rose    { background: #dc2626; }
        .kpi-chip-icon .dashicons { font-size: 22px; width: 22px; height: 22px; }

        .kpi-chip-lbl { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; display: block; margin-bottom: 2px; }
        .kpi-chip-val { font-size: 20px; font-weight: 900; color: #0f172a; display: block; letter-spacing: -0.5px; }
        .kpi-chip-sub { font-size: 11px; color: #64748b; font-weight: 600; margin-top: 2px; display: block; }
        .color-blue    { color: #003376 !important; }
        .color-emerald { color: #059669 !important; }
        .color-rose    { color: #dc2626 !important; }
        .color-slate   { color: #475569 !important; }

        /* Master Table Card */
        .ifs-report-table-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 24px;
        }
        .ifs-table-card-head {
            padding: 22px 26px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .table-card-heading { margin: 0; font-size: 16px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        .table-card-heading .dashicons { font-size: 20px; width: 20px; height: 20px; }
        .table-card-date-badge { 
            font-size: 11.5px; 
            font-weight: 700; 
            color: #dc2626; 
            background: #fef2f2; 
            padding: 4px 10px; 
            border-radius: 6px; 
            border: 1px solid #fecaca; 
        }

        /* Custom Table Controls Toolbar */
        .ifs-custom-table-controls {
            padding: 16px 26px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .ifs-per-page-wrap { display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #475569; font-weight: 600; }
        .ifs-select-control {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 0 26px 0 10px;
            height: 38px;
            font-size: 12.5px;
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
        .ifs-select-control:focus { border-color: #003376; }

        .ifs-live-search-wrap { position: relative; display: flex; align-items: center; min-width: 280px; }
        .ifs-live-search-wrap label { position: absolute; left: 10px; color: #94a3b8; display: flex; align-items: center; pointer-events: none; }
        .ifs-live-search-wrap label .dashicons { font-size: 16px; width: 16px; height: 16px; }
        .ifs-search-input {
            width: 100%;
            height: 38px;
            padding: 0 12px 0 32px !important;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 12.5px;
            color: #0f172a;
            background: #ffffff;
            outline: none;
        }
        .ifs-search-input:focus { border-color: #003376; }

        .ifs-table-responsive { padding: 12px 22px 20px; overflow-x: auto; }
        .ifs-report-datatable { width: 100%; border-collapse: collapse; font-size: 12.5px; }
        .ifs-report-datatable thead th {
            background: #f8fafc;
            color: #475569;
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 14px;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
            white-space: nowrap;
        }
        .ifs-report-datatable tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: #334155;
        }
        .ifs-report-datatable tbody tr:hover td { background: #f8fafc; }

        .ifs-id-badge {
            background: #f1f5f9;
            color: #003376;
            font-weight: 700;
            font-size: 11px;
            padding: 3px 6px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
            display: inline-block;
        }
        .agency-title-cell strong { color: #0f172a; font-size: 13px; display: block; }
        .agency-email { font-size: 11px; color: #64748b; margin-top: 1px; display: block; }
        .contact-name { font-weight: 600; color: #334155; }
        .due-amount-cell { font-size: 13.5px; }

        .ifs-status-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-limit-exceeded { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .badge-within-limit   { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }

        .ifs-action-pills { display: flex; gap: 6px; justify-content: flex-end; align-items: center; }
        .ifs-action-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            height: 30px;
            padding: 0 10px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease;
            white-space: nowrap;
            border: 1px solid transparent;
        }
        .ifs-action-pill.ledger { background: #eff6ff; color: #003376; border-color: #bfdbfe; }
        .ifs-action-pill.ledger:hover { background: #003376; color: #ffffff; border-color: #003376; }
        .ifs-action-pill.ledger .dashicons { font-size: 13px; width: 13px; height: 13px; }

        .ifs-empty-table { text-align: center; padding: 50px 20px !important; }
        .ifs-empty-state .dashicons { font-size: 44px; width: 44px; height: 44px; color: #059669; margin-bottom: 10px; }
        .ifs-empty-state h4 { margin: 0 0 4px 0; font-size: 16px; font-weight: 700; color: #0f172a; }
        .ifs-empty-state p { margin: 0; color: #64748b; font-size: 13px; }

        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important; }
        .font-bold { font-weight: 700; }

        /* DataTables Custom Pagination */
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
            background: #dc2626 !important;
            color: #ffffff !important;
            border: 1px solid #dc2626 !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #e2e8f0 !important;
            color: #0f172a !important;
        }

        @media print {
            #adminmenuback, #adminmenuwrap, #wpadminbar, #wpfooter, .ifs-report-header, .ifs-custom-table-controls, .dataTables_wrapper .dataTables_paginate, .dataTables_wrapper .dataTables_info {
                display: none !important;
            }
            #wpcontent, #wpbody-content { margin-left: 0 !important; padding: 0 !important; }
            body.wp-admin { background: #ffffff !important; }
            .ifs-agent-dues-wrapper { max-width: 100% !important; padding: 0 !important; }
            .ifs-report-table-card { border: 1px solid #000 !important; }
        }
    </style>

    <!-- DataTables Engine Script -->
    <script>
    jQuery(document).ready(function($) {
        if ($.fn.DataTable) {
            var table = $('#ifsAgentDuesTable').DataTable({
                "pageLength": 15,
                "ordering": true,
                "info": true,
                "searching": true,
                "lengthChange": false,
                "order": [[ 5, "desc" ]], // Sort by due amount descending
                "language": {
                    "info": "Showing _START_ to _END_ of _TOTAL_ overdue accounts",
                    "infoEmpty": "Showing 0 to 0 of 0 accounts",
                    "infoFiltered": "(filtered from _MAX_ total accounts)",
                    "paginate": {
                        "previous": "&larr; Prev",
                        "next": "Next &rarr;"
                    }
                }
            });

            // Bind Custom Controls
            $('#ifsDuesPerPageSelect').on('change', function() {
                var lengthVal = parseInt($(this).val());
                table.page.len(lengthVal).draw();
            });

            $('#ifsDuesSearchInput').on('keyup', function() {
                table.search(this.value).draw();
            });
        }
    });
    </script>
    <?php
}