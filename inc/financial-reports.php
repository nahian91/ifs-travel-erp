<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_financial_reports_page' ) ) {
    /**
     * Enterprise Ultra-Modern Income & Expense Financial Reporting Dashboard
     * Features: Cross-Module Revenue Aggregation (Ticketing, Hajj/Umrah, Visas, Hotels, Agent Top-Ups),
     * Date Range Filtering, Executive KPI Ribbon, Net Profit Yield Analysis & Print/Export Utilities
     */
    function ifs_terp_financial_reports_page() {
        global $wpdb;

        // Table References
        $table_tickets = $wpdb->prefix . 'iterp_tickets';
        $table_hajj    = $wpdb->prefix . 'iterp_hajj_bookings';
        $table_visas   = $wpdb->prefix . 'iterp_visa_applications';
        $table_hotels  = $wpdb->prefix . 'iterp_hotel_bookings';
        $table_ledgers = $wpdb->prefix . 'iterp_agent_ledgers';

        // Date Filter Inputs
        $date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : date( 'Y-m-01' );
        $date_to   = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : date( 'Y-m-t' );

        // =========================================================================
        // AGGREGATE FINANCIAL METRICS ACROSS ERP MODULES
        // =========================================================================

        // 1. Air Ticketing Revenue & Cost
        $ticket_sql = $wpdb->prepare( "
            SELECT COALESCE(SUM(sell_price), 0) AS revenue, 
                   COALESCE(SUM(buy_price), 0) AS cost, 
                   COALESCE(SUM(profit), 0) AS profit,
                   COUNT(id) AS count
            FROM {$table_tickets}
            WHERE status != 'Cancelled' AND DATE(created_at) BETWEEN %s AND %s
        ", $date_from, $date_to );
        $tkt_stats = $wpdb->get_row( $ticket_sql );

        // 2. Hajj & Umrah Bookings Revenue & Cost
        $hajj_sql = $wpdb->prepare( "
            SELECT COALESCE(SUM(sell_price), 0) AS revenue, 
                   COALESCE(SUM(buy_price), 0) AS cost, 
                   COALESCE(SUM(sell_price - buy_price), 0) AS profit,
                   COUNT(id) AS count
            FROM {$table_hajj}
            WHERE status != 'Cancelled' AND DATE(created_at) BETWEEN %s AND %s
        ", $date_from, $date_to );
        $hajj_stats = $wpdb->get_row( $hajj_sql );

        // 3. Visa Applications Revenue & Cost
        $visa_sql = $wpdb->prepare( "
            SELECT COALESCE(SUM(sell_price), 0) AS revenue, 
                   COALESCE(SUM(buy_price), 0) AS cost, 
                   COALESCE(SUM(profit), 0) AS profit,
                   COUNT(id) AS count
            FROM {$table_visas}
            WHERE status != 'Rejected' AND DATE(created_at) BETWEEN %s AND %s
        ", $date_from, $date_to );
        $visa_stats = $wpdb->get_row( $visa_sql );

        // 4. Hotel Bookings Revenue & Cost
        $hotel_sql = $wpdb->prepare( "
            SELECT COALESCE(SUM(sell_price), 0) AS revenue, 
                   COALESCE(SUM(buy_price), 0) AS cost, 
                   COALESCE(SUM(profit), 0) AS profit,
                   COUNT(id) AS count
            FROM {$table_hotels}
            WHERE status != 'Cancelled' AND DATE(created_at) BETWEEN %s AND %s
        ", $date_from, $date_to );
        $hotel_stats = $wpdb->get_row( $hotel_sql );

        // 5. Manual Cash/Bank Deposits (Agent Inflows)
        $deposit_sql = $wpdb->prepare( "
            SELECT COALESCE(SUM(credit), 0) AS total_deposits
            FROM {$table_ledgers}
            WHERE reference_type IN ('Bank Deposit', 'Cash Receipt', 'bKash / Nagad', 'Manual Adjustment') 
              AND DATE(created_at) BETWEEN %s AND %s
        ", $date_from, $date_to );
        $total_manual_deposits = (float) $wpdb->get_var( $deposit_sql );

        // Grand Totals Computation
        $grand_revenue      = floatval( $tkt_stats->revenue ) + floatval( $hajj_stats->revenue ) + floatval( $visa_stats->revenue ) + floatval( $hotel_stats->revenue );
        $grand_cost         = floatval( $tkt_stats->cost ) + floatval( $hajj_stats->cost ) + floatval( $visa_stats->cost ) + floatval( $hotel_stats->cost );
        $grand_profit       = floatval( $tkt_stats->profit ) + floatval( $hajj_stats->profit ) + floatval( $visa_stats->profit ) + floatval( $hotel_stats->profit );
        $total_transactions = intval( $tkt_stats->count ) + intval( $hajj_stats->count ) + intval( $visa_stats->count ) + intval( $hotel_stats->count );
        
        $profit_margin = $grand_revenue > 0 ? ( ( $grand_profit / $grand_revenue ) * 100 ) : 0;

        // Handle CSV Export
        if ( isset( $_GET['action'] ) && 'export_financial_csv' === sanitize_key( wp_unslash( $_GET['action'] ) ) ) {
            if ( ! current_user_can( 'manage_options' ) && ! ( function_exists( 'ifs_terp_has_access' ) && ifs_terp_has_access( array( 'accountant', 'admin_manager' ) ) ) ) {
                wp_die( esc_html__( 'Unauthorized export request.', 'ifs-travel-erp' ) );
            }

            header( 'Content-Type: text/csv; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename=financial-report-' . esc_attr( $date_from ) . '-to-' . esc_attr( $date_to ) . '.csv' );

            $output = fopen( 'php://output', 'w' );
            fputcsv( $output, array( 'Operational Module', 'Total Invoiced / Sales (BDT)', 'Total Cost / Payable (BDT)', 'Net Profit Margin (BDT)' ) );
            fputcsv( $output, array( 'Air Ticketing', floatval( $tkt_stats->revenue ), floatval( $tkt_stats->cost ), floatval( $tkt_stats->profit ) ) );
            fputcsv( $output, array( 'Hajj & Umrah Groups', floatval( $hajj_stats->revenue ), floatval( $hajj_stats->cost ), floatval( $hajj_stats->profit ) ) );
            fputcsv( $output, array( 'Visa Applications', floatval( $visa_stats->revenue ), floatval( $visa_stats->cost ), floatval( $visa_stats->profit ) ) );
            fputcsv( $output, array( 'Hotel Reservations', floatval( $hotel_stats->revenue ), floatval( $hotel_stats->cost ), floatval( $hotel_stats->profit ) ) );
            fputcsv( $output, array( 'CONSOLIDATED TOTAL', $grand_revenue, $grand_cost, $grand_profit ) );
            fclose( $output );
            exit;
        }

        $base_url   = admin_url( 'admin.php?page=ifs_travel_erp&tab=reports' );
        $export_url = add_query_arg( array( 'action' => 'export_financial_csv', 'date_from' => $date_from, 'date_to' => $date_to ), $base_url );
        ?>

        <div class="ifs-reports-workspace">
            
            <!-- Top Header & Action Strip -->
            <div class="ifs-view-header-strip">
                <div class="ifs-header-identity">
                    <div class="ifs-header-icon-box">
                        <span class="dashicons dashicons-chart-area"></span>
                    </div>
                    <div>
                        <div class="ifs-badge-row">
                            <span class="ifs-id-pill"><?php esc_html_e( 'ERP FINANCIAL CONSOLE', 'ifs-travel-erp' ); ?></span>
                            <span class="ifs-status-badge status-active"><?php esc_html_e( 'Live Aggregation', 'ifs-travel-erp' ); ?></span>
                        </div>
                        <h2 class="ifs-view-name"><?php esc_html_e( 'Income & Expense Financial Reporting Dashboard', 'ifs-travel-erp' ); ?></h2>
                    </div>
                </div>

                <div class="ifs-header-actions">
                    <form method="get" action="" class="ifs-toolbar-date-form">
                        <input type="hidden" name="page" value="ifs_travel_erp">
                        <input type="hidden" name="tab" value="reports">
                        <div class="date-inputs-group">
                            <label><?php esc_html_e( 'From:', 'ifs-travel-erp' ); ?></label>
                            <input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" class="ifs-input-sm font-mono">
                            <label><?php esc_html_e( 'To:', 'ifs-travel-erp' ); ?></label>
                            <input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" class="ifs-input-sm font-mono">
                            <button type="submit" class="ifs-btn-filter-sm"><?php esc_html_e( 'Apply Filter', 'ifs-travel-erp' ); ?></button>
                        </div>
                    </form>
                    <a href="<?php echo esc_url( $export_url ); ?>" class="ifs-btn-print" style="text-decoration: none;">
                        <span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export CSV', 'ifs-travel-erp' ); ?>
                    </a>
                    <button type="button" onclick="window.print();" class="ifs-btn-print">
                        <span class="dashicons dashicons-printer"></span> <?php esc_html_e( 'Print P&L Report', 'ifs-travel-erp' ); ?>
                    </button>
                </div>
            </div>

            <!-- Metric KPI Ribbon -->
            <div class="ifs-dossier-metrics-grid">
                <div class="ifs-metric-box">
                    <div class="metric-icon bg-blue"><span class="dashicons dashicons-money-alt"></span></div>
                    <div>
                        <span class="metric-lbl"><?php esc_html_e( 'Total Gross Revenue', 'ifs-travel-erp' ); ?></span>
                        <strong class="metric-val color-blue font-mono">৳<?php echo esc_html( number_format( $grand_revenue, 2 ) ); ?></strong>
                        <span class="metric-sub"><?php echo esc_html( number_format( $total_transactions ) ); ?> <?php esc_html_e( 'Invoiced Services', 'ifs-travel-erp' ); ?></span>
                    </div>
                </div>

                <div class="ifs-metric-box">
                    <div class="metric-icon bg-slate"><span class="dashicons dashicons-cart"></span></div>
                    <div>
                        <span class="metric-lbl"><?php esc_html_e( 'Total Cost / Payables', 'ifs-travel-erp' ); ?></span>
                        <strong class="metric-val color-slate font-mono">৳<?php echo esc_html( number_format( $grand_cost, 2 ) ); ?></strong>
                        <span class="metric-sub"><?php esc_html_e( 'Supplier & GDS Liabilities', 'ifs-travel-erp' ); ?></span>
                    </div>
                </div>

                <div class="ifs-metric-box">
                    <div class="metric-icon bg-emerald"><span class="dashicons dashicons-chart-line"></span></div>
                    <div>
                        <span class="metric-lbl"><?php esc_html_e( 'Net Operating Profit', 'ifs-travel-erp' ); ?></span>
                        <strong class="metric-val color-emerald font-mono">৳<?php echo esc_html( number_format( $grand_profit, 2 ) ); ?></strong>
                        <span class="metric-sub"><?php echo esc_html( number_format( $profit_margin, 1 ) ); ?>% <?php esc_html_e( 'Profit Margin Ratio', 'ifs-travel-erp' ); ?></span>
                    </div>
                </div>

                <div class="ifs-metric-box">
                    <div class="metric-icon bg-amber"><span class="dashicons dashicons-vault"></span></div>
                    <div>
                        <span class="metric-lbl"><?php esc_html_e( 'Manual Bank / Cash Inflows', 'ifs-travel-erp' ); ?></span>
                        <strong class="metric-val color-amber font-mono">৳<?php echo esc_html( number_format( $total_manual_deposits, 2 ) ); ?></strong>
                        <span class="metric-sub"><?php esc_html_e( 'Agent Deposits & Receipts', 'ifs-travel-erp' ); ?></span>
                    </div>
                </div>
            </div>

            <!-- Master Financial Breakdown Table Card -->
            <div class="ifs-history-container-card">
                <div class="ifs-history-header-nav">
                    <h3 class="history-title"><span class="dashicons dashicons-media-spreadsheet"></span> <?php esc_html_e( 'P&L Breakdown by ERP Operational Module', 'ifs-travel-erp' ); ?></h3>
                    <span style="font-size: 12px; color: #64748b; font-weight: 600;"><?php esc_html_e( 'Period:', 'ifs-travel-erp' ); ?> <?php echo esc_html( $date_from ); ?> <?php esc_html_e( 'to', 'ifs-travel-erp' ); ?> <?php echo esc_html( $date_to ); ?></span>
                </div>

                <table class="ifs-finance-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Operational Module', 'ifs-travel-erp' ); ?></th>
                            <th style="text-align: center;"><?php esc_html_e( 'Total Transactions', 'ifs-travel-erp' ); ?></th>
                            <th style="text-align: right;"><?php esc_html_e( 'Gross Revenue (৳)', 'ifs-travel-erp' ); ?></th>
                            <th style="text-align: right;"><?php esc_html_e( 'Direct Cost (৳)', 'ifs-travel-erp' ); ?></th>
                            <th style="text-align: right;"><?php esc_html_e( 'Net Profit Yield (৳)', 'ifs-travel-erp' ); ?></th>
                            <th style="text-align: right; width: 120px;"><?php esc_html_e( 'Yield Share (%)', 'ifs-travel-erp' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- 1. Air Ticketing -->
                        <?php 
                        $tkt_rev   = floatval( $tkt_stats->revenue );
                        $tkt_share = $grand_revenue > 0 ? ( $tkt_rev / $grand_revenue ) * 100 : 0;
                        ?>
                        <tr>
                            <td>
                                <div class="mod-cell">
                                    <span class="dashicons dashicons-airplane" style="color: #0284c7;"></span>
                                    <div>
                                        <strong><?php esc_html_e( 'Air Ticketing & GDS Issuances', 'ifs-travel-erp' ); ?></strong>
                                        <div style="font-size: 11px; color: #64748b;"><?php esc_html_e( 'Flight bookings and airline tickets', 'ifs-travel-erp' ); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="text-align: center;"><span class="ifs-count-pill"><?php echo intval( $tkt_stats->count ); ?></span></td>
                            <td style="text-align: right;" class="font-mono">৳<?php echo esc_html( number_format( $tkt_rev, 2 ) ); ?></td>
                            <td style="text-align: right;" class="font-mono color-slate">৳<?php echo esc_html( number_format( floatval( $tkt_stats->cost ), 2 ) ); ?></td>
                            <td style="text-align: right;" class="font-mono font-bold color-emerald">৳<?php echo esc_html( number_format( floatval( $tkt_stats->profit ), 2 ) ); ?></td>
                            <td style="text-align: right;" class="font-mono"><?php echo esc_html( number_format( $tkt_share, 1 ) ); ?>%</td>
                        </tr>

                        <!-- 2. Hajj & Umrah Packages -->
                        <?php 
                        $hajj_rev   = floatval( $hajj_stats->revenue );
                        $hajj_share = $grand_revenue > 0 ? ( $hajj_rev / $grand_revenue ) * 100 : 0;
                        ?>
                        <tr>
                            <td>
                                <div class="mod-cell">
                                    <span class="dashicons dashicons-palmtree" style="color: #047857;"></span>
                                    <div>
                                        <strong><?php esc_html_e( 'Hajj & Umrah Pilgrimage Groups', 'ifs-travel-erp' ); ?></strong>
                                        <div style="font-size: 11px; color: #64748b;"><?php esc_html_e( 'Fixed package itineraries & hotel room splits', 'ifs-travel-erp' ); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="text-align: center;"><span class="ifs-count-pill"><?php echo intval( $hajj_stats->count ); ?></span></td>
                            <td style="text-align: right;" class="font-mono">৳<?php echo esc_html( number_format( $hajj_rev, 2 ) ); ?></td>
                            <td style="text-align: right;" class="font-mono color-slate">৳<?php echo esc_html( number_format( floatval( $hajj_stats->cost ), 2 ) ); ?></td>
                            <td style="text-align: right;" class="font-mono font-bold color-emerald">৳<?php echo esc_html( number_format( floatval( $hajj_stats->profit ), 2 ) ); ?></td>
                            <td style="text-align: right;" class="font-mono"><?php echo esc_html( number_format( $hajj_share, 1 ) ); ?>%</td>
                        </tr>

                        <!-- 3. Visa Applications -->
                        <?php 
                        $visa_rev   = floatval( $visa_stats->revenue );
                        $visa_share = $grand_revenue > 0 ? ( $visa_rev / $grand_revenue ) * 100 : 0;
                        ?>
                        <tr>
                            <td>
                                <div class="mod-cell">
                                    <span class="dashicons dashicons-admin-site-alt3" style="color: #4f46e5;"></span>
                                    <div>
                                        <strong><?php esc_html_e( 'Visa Applications & Embassy Dossiers', 'ifs-travel-erp' ); ?></strong>
                                        <div style="font-size: 11px; color: #64748b;"><?php esc_html_e( 'Embassy processing & VFS service fees', 'ifs-travel-erp' ); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="text-align: center;"><span class="ifs-count-pill"><?php echo intval( $visa_stats->count ); ?></span></td>
                            <td style="text-align: right;" class="font-mono">৳<?php echo esc_html( number_format( $visa_rev, 2 ) ); ?></td>
                            <td style="text-align: right;" class="font-mono color-slate">৳<?php echo esc_html( number_format( floatval( $visa_stats->cost ), 2 ) ); ?></td>
                            <td style="text-align: right;" class="font-mono font-bold color-emerald">৳<?php echo esc_html( number_format( floatval( $visa_stats->profit ), 2 ) ); ?></td>
                            <td style="text-align: right;" class="font-mono"><?php echo esc_html( number_format( $visa_share, 1 ) ); ?>%</td>
                        </tr>

                        <!-- 4. Hotel Bookings -->
                        <?php 
                        $hotel_rev   = floatval( $hotel_stats->revenue );
                        $hotel_share = $grand_revenue > 0 ? ( $hotel_rev / $grand_revenue ) * 100 : 0;
                        ?>
                        <tr>
                            <td>
                                <div class="mod-cell">
                                    <span class="dashicons dashicons-building" style="color: #d97706;"></span>
                                    <div>
                                        <strong><?php esc_html_e( 'Contracted Hotel Room Reservations', 'ifs-travel-erp' ); ?></strong>
                                        <div style="font-size: 11px; color: #64748b;"><?php esc_html_e( 'Nightly room inventory allocations', 'ifs-travel-erp' ); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="text-align: center;"><span class="ifs-count-pill"><?php echo intval( $hotel_stats->count ); ?></span></td>
                            <td style="text-align: right;" class="font-mono">৳<?php echo esc_html( number_format( $hotel_rev, 2 ) ); ?></td>
                            <td style="text-align: right;" class="font-mono color-slate">৳<?php echo esc_html( number_format( floatval( $hotel_stats->cost ), 2 ) ); ?></td>
                            <td style="text-align: right;" class="font-mono font-bold color-emerald">৳<?php echo esc_html( number_format( floatval( $hotel_stats->profit ), 2 ) ); ?></td>
                            <td style="text-align: right;" class="font-mono"><?php echo esc_html( number_format( $hotel_share, 1 ) ); ?>%</td>
                        </tr>

                        <!-- Grand Total Summary Row -->
                        <tr class="total-row">
                            <td><strong><?php esc_html_e( 'GRAND TOTAL CONSOLIDATED P&L', 'ifs-travel-erp' ); ?></strong></td>
                            <td style="text-align: center;"><strong class="font-mono"><?php echo esc_html( number_format( $total_transactions ) ); ?> <?php esc_html_e( 'Files', 'ifs-travel-erp' ); ?></strong></td>
                            <td style="text-align: right;" class="font-mono font-bold color-blue">৳<?php echo esc_html( number_format( $grand_revenue, 2 ) ); ?></td>
                            <td style="text-align: right;" class="font-mono font-bold color-slate">৳<?php echo esc_html( number_format( $grand_cost, 2 ) ); ?></td>
                            <td style="text-align: right;" class="font-mono font-bold color-emerald">৳<?php echo esc_html( number_format( $grand_profit, 2 ) ); ?></td>
                            <td style="text-align: right;" class="font-mono font-bold">100.0%</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>

        <!-- Stylesheet -->
        <style>
            .ifs-reports-workspace { max-width: 1420px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; }
            
            .ifs-toast { padding: 14px 20px; border-radius: 10px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 10px; margin-bottom: 22px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
            .ifs-toast.danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
            .ifs-toast .dashicons { font-size: 18px; width: 18px; height: 18px; }

            /* Top Header Strip */
            .ifs-view-header-strip {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                padding: 22px 28px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 20px;
                box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04);
                margin-bottom: 24px;
            }
            .ifs-header-identity { display: flex; align-items: center; gap: 18px; }
            .ifs-header-icon-box {
                width: 48px;
                height: 48px;
                border-radius: 12px;
                background: linear-gradient(135deg, #003376 0%, #0284c7 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                color: #ffffff;
                flex-shrink: 0;
                box-shadow: 0 4px 12px rgba(0, 51, 118, 0.25);
            }
            .ifs-header-icon-box .dashicons { font-size: 24px; width: 24px; height: 24px; }
            .ifs-badge-row { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; flex-wrap: wrap; }
            .ifs-id-pill { font-family: ui-monospace, monospace; font-size: 11px; font-weight: 800; background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 6px; border: 1px solid #e2e8f0; }
            .ifs-status-badge { font-size: 10px; font-weight: 800; text-transform: uppercase; padding: 2px 8px; border-radius: 6px; }
            .status-active { background: #dcfce7; color: #15803d; }

            .ifs-view-name { margin: 0; font-size: 20px; font-weight: 800; color: #0f172a; letter-spacing: -0.4px; }

            .ifs-header-actions { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
            .ifs-toolbar-date-form { display: flex; align-items: center; }
            .date-inputs-group { display: flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; color: #475569; }
            .ifs-input-sm { padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12px; background: #ffffff; }
            .ifs-btn-filter-sm { background: #003376; color: #ffffff; border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer; }
            .ifs-btn-filter-sm:hover { background: #0284c7; }

            .ifs-btn-print {
                background: #f8fafc;
                border: 1px solid #cbd5e1;
                color: #334155 !important;
                padding: 10px 18px;
                border-radius: 8px;
                font-size: 13px;
                font-weight: 700;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                transition: all 0.2s ease;
            }
            .ifs-btn-print:hover { background: #e2e8f0; color: #0f172a; }

            /* KPI Metric Cards */
            .ifs-dossier-metrics-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
                gap: 18px;
                margin-bottom: 24px;
            }
            .ifs-metric-box {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                padding: 18px 22px;
                display: flex;
                align-items: center;
                gap: 16px;
                box-shadow: 0 2px 8px -2px rgba(15, 23, 42, 0.03);
            }
            .metric-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #ffffff; flex-shrink: 0; }
            .metric-icon.bg-blue    { background: linear-gradient(135deg, #003376 0%, #0284c7 100%); }
            .metric-icon.bg-slate   { background: linear-gradient(135deg, #475569 0%, #334155 100%); }
            .metric-icon.bg-emerald { background: linear-gradient(135deg, #059669 0%, #047857 100%); }
            .metric-icon.bg-amber   { background: linear-gradient(135deg, #d97706 0%, #b45309 100%); }
            .metric-icon .dashicons { font-size: 22px; width: 22px; height: 22px; }

            .metric-lbl { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; display: block; margin-bottom: 2px; }
            .metric-val { font-size: 19px; font-weight: 900; color: #0f172a; display: block; }
            .metric-sub { font-size: 11px; color: #64748b; font-weight: 600; margin-top: 2px; display: block; }
            
            .color-blue { color: #003376 !important; }
            .color-slate { color: #475569 !important; }
            .color-emerald { color: #059669 !important; }
            .color-amber { color: #d97706 !important; }

            /* History Container & Tables */
            .ifs-history-container-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 26px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); }
            .ifs-history-header-nav { padding-bottom: 16px; border-bottom: 1px solid #f1f5f9; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
            .history-title { margin: 0; font-size: 16px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; }
            .history-title .dashicons { color: #003376; font-size: 20px; width: 20px; height: 20px; }

            /* Commercial Accounting Table */
            .ifs-finance-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
            .ifs-finance-table thead th { background: #f8fafc; padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
            .ifs-finance-table tbody td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
            
            .mod-cell { display: flex; align-items: center; gap: 12px; }
            .mod-cell .dashicons { font-size: 20px; width: 20px; height: 20px; }
            .ifs-count-pill { background: #f1f5f9; color: #334155; font-family: ui-monospace, monospace; font-size: 11.5px; font-weight: 700; padding: 2px 8px; border-radius: 6px; border: 1px solid #e2e8f0; }

            .highlight-row { background: #f8fafc; }
            .total-row { background: #eff6ff; font-size: 14.5px; }
            .total-row td { padding: 16px; border-top: 2px solid #bfdbfe; border-bottom: none; }

            .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
            .font-bold { font-weight: 700; }

            /* Print Optimization */
            @media print {
                body * { visibility: hidden; }
                .ifs-history-container-card, .ifs-history-container-card * { visibility: visible; }
                .ifs-history-container-card {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 100%;
                    box-shadow: none;
                    border: none;
                    padding: 0;
                }
                .ifs-history-header-nav button, .ifs-history-header-nav a { display: none !important; }
            }
        </style>
        <?php
    }
}