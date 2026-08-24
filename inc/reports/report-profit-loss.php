<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Enterprise Ultra-Modern Profit & Loss Statement Console
 * Features: Multi-Module Gross Revenue Streams, Overhead Expense Ledger, CSV Export & Executive Financial Statement Matrix
 */
function ifs_terp_report_profit_loss_page() {
    global $wpdb;

    $table_tickets = $wpdb->prefix . 'iterp_tickets';
    $table_visas   = $wpdb->prefix . 'iterp_visa_applications';
    $table_hajj    = $wpdb->prefix . 'iterp_hajj_bookings';
    $table_hotels  = $wpdb->prefix . 'iterp_hotel_bookings';
    $table_tours   = $wpdb->prefix . 'iterp_tours';
    $table_ledger  = $wpdb->prefix . 'iterp_ledger';

    $start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : date( 'Y-m-01' );
    $end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : date( 'Y-m-d' );

    // 1. Gross Profit / Margins across all 5 operational modules
    $ticket_profit = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(profit), 0) FROM $table_tickets WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Void'", $start_date, $end_date ) );
    $visa_profit   = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(profit), 0) FROM $table_visas WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Rejected'", $start_date, $end_date ) );
    $hajj_profit   = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(profit), 0) FROM $table_hajj WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Cancelled'", $start_date, $end_date ) );
    $hotel_profit  = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(profit), 0) FROM $table_hotels WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Cancelled'", $start_date, $end_date ) );
    $tour_profit   = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(profit), 0) FROM $table_tours WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Cancelled'", $start_date, $end_date ) );

    $total_gross_income = $ticket_profit + $visa_profit + $hajj_profit + $hotel_profit + $tour_profit;

    // 2. Office & Overhead Expenses from General Ledger
    $expenses = $wpdb->get_results( $wpdb->prepare( "SELECT category, SUM(amount) as total_amount FROM $table_ledger WHERE transaction_type = 'Expense' AND DATE(transaction_date) BETWEEN %s AND %s GROUP BY category ORDER BY total_amount DESC", $start_date, $end_date ) );

    $total_expenses = 0;
    if ( $expenses ) {
        foreach ( $expenses as $exp ) {
            $total_expenses += (float) $exp->total_amount;
        }
    }

    // 3. Bottom-Line Calculations
    $net_profit = $total_gross_income - $total_expenses;
    $margin_pct = ( $total_gross_income > 0 ) ? ( $net_profit / $total_gross_income ) * 100 : 0;
    $is_profit  = ( $net_profit >= 0 );

    // Handle CSV Export
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'export_csv' ) {
        if ( ! current_user_can( 'manage_options' ) && ! ifs_terp_has_access( array( 'accountant', 'admin_manager' ) ) ) {
            wp_die( 'Unauthorized export request.' );
        }

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=ifs-profit-loss-statement-' . $start_date . '-to-' . $end_date . '.csv' );

        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, array( 'Statement Category', 'Head / Module / Description', 'Amount (BDT)' ) );

        fputcsv( $output, array( 'Operating Income', 'Air Ticketing Profit', $ticket_profit ) );
        fputcsv( $output, array( 'Operating Income', 'Visa Processing Profit', $visa_profit ) );
        fputcsv( $output, array( 'Operating Income', 'Hajj & Umrah Profit', $hajj_profit ) );
        fputcsv( $output, array( 'Operating Income', 'Hotel Bookings Profit', $hotel_profit ) );
        fputcsv( $output, array( 'Operating Income', 'Holiday & Tour Packages Profit', $tour_profit ) );
        fputcsv( $output, array( 'Operating Income', 'TOTAL GROSS OPERATING INCOME', $total_gross_income ) );

        if ( $expenses ) {
            foreach ( $expenses as $exp ) {
                fputcsv( $output, array( 'Overhead Expense', $exp->category, (float) $exp->total_amount ) );
            }
        }
        fputcsv( $output, array( 'Overhead Expense', 'TOTAL OPERATING EXPENSES', $total_expenses ) );
        fputcsv( $output, array( 'Bottom Line', 'NET OPERATING PROFIT / (LOSS)', $net_profit ) );
        fputcsv( $output, array( 'Bottom Line', 'OPERATING MARGIN (%)', number_format( $margin_pct, 1 ) . '%' ) );

        fclose( $output );
        exit;
    }

    $base_url   = admin_url( 'admin.php?page=ifs_travel_erp&tab=reports&sub=profit_loss' );
    $export_url = add_query_arg( array( 'start_date' => $start_date, 'end_date' => $end_date, 'action' => 'export_csv' ), $base_url );
    ?>

    <div class="wrap ifs-pnl-wrapper">
        
        <!-- Header & Action Controls -->
        <div class="ifs-pnl-header">
            <div class="pnl-header-title-group">
                <span class="ifs-pnl-badge"><span class="dashicons dashicons-media-spreadsheet"></span> Audited Financial Statement</span>
                <h2>Comprehensive Profit &amp; Loss Statement</h2>
                <p>Consolidated executive statement reconciling multi-module gross revenue margins against overhead operational expenses.</p>
            </div>
            <div class="ifs-pnl-header-actions">
                <a href="<?php echo esc_url( $export_url ); ?>" class="ifs-btn-action-sec">
                    <span class="dashicons dashicons-download"></span> Export CSV
                </a>
                <button type="button" onclick="window.print();" class="ifs-btn-action-pri">
                    <span class="dashicons dashicons-printer"></span> Print Statement
                </button>
            </div>
        </div>

        <!-- Date Range Filter Toolbar -->
        <div class="ifs-filter-card">
            <form method="get" action="">
                <input type="hidden" name="page" value="ifs_travel_erp">
                <input type="hidden" name="tab" value="reports">
                <input type="hidden" name="sub" value="profit_loss">

                <div class="filter-grid-layout">
                    <div class="filter-field">
                        <label for="pnl_start_date">Period Start Date</label>
                        <div class="filter-input-wrap">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <input type="date" name="start_date" id="pnl_start_date" value="<?php echo esc_attr( $start_date ); ?>" class="ifs-input-field">
                        </div>
                    </div>

                    <div class="filter-field">
                        <label for="pnl_end_date">Period End Date</label>
                        <div class="filter-input-wrap">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <input type="date" name="end_date" id="pnl_end_date" value="<?php echo esc_attr( $end_date ); ?>" class="ifs-input-field">
                        </div>
                    </div>

                    <div class="filter-field filter-btn-wrap">
                        <button type="submit" class="ifs-btn-filter-submit">
                            <span class="dashicons dashicons-calculator"></span> Recalculate Statement
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Top Financial Metric Chips -->
        <div class="ifs-pnl-metrics-grid">
            <div class="ifs-kpi-chip border-emerald">
                <div class="kpi-chip-icon bg-emerald"><span class="dashicons dashicons-arrow-up-alt"></span></div>
                <div>
                    <span class="kpi-chip-lbl">Gross Operating Income</span>
                    <strong class="kpi-chip-val color-emerald font-mono">৳<?php echo number_format( $total_gross_income, 2 ); ?></strong>
                    <span class="kpi-chip-sub">Across 5 Operational Desks</span>
                </div>
            </div>

            <div class="ifs-kpi-chip border-rose">
                <div class="kpi-chip-icon bg-rose"><span class="dashicons dashicons-arrow-down-alt"></span></div>
                <div>
                    <span class="kpi-chip-lbl">Overhead Expenses</span>
                    <strong class="kpi-chip-val color-rose font-mono">৳<?php echo number_format( $total_expenses, 2 ); ?></strong>
                    <span class="kpi-chip-sub">Office Rent, Bills &amp; Operations</span>
                </div>
            </div>

            <div class="ifs-kpi-chip <?php echo $is_profit ? 'border-emerald' : 'border-rose'; ?>">
                <div class="kpi-chip-icon <?php echo $is_profit ? 'bg-emerald' : 'bg-rose'; ?>">
                    <span class="dashicons dashicons-chart-pie"></span>
                </div>
                <div>
                    <span class="kpi-chip-lbl">Net Operating Balance</span>
                    <strong class="kpi-chip-val font-mono <?php echo $is_profit ? 'color-emerald' : 'color-rose'; ?>">
                        <?php echo $is_profit ? '+৳' : '-৳'; ?><?php echo number_format( abs( $net_profit ), 2 ); ?>
                    </strong>
                    <span class="kpi-chip-sub"><?php echo $is_profit ? 'Profitable Performance' : 'Operating Loss'; ?></span>
                </div>
            </div>

            <div class="ifs-kpi-chip border-blue">
                <div class="kpi-chip-icon bg-blue"><span class="dashicons dashicons-performance"></span></div>
                <div>
                    <span class="kpi-chip-lbl">Net Yield Margin</span>
                    <strong class="kpi-chip-val color-blue font-mono"><?php echo number_format( $margin_pct, 1 ); ?>%</strong>
                    <span class="kpi-chip-sub">Profit to Revenue Ratio</span>
                </div>
            </div>
        </div>

        <!-- 2-Column Balanced Statement Grid -->
        <div class="ifs-pnl-split-grid">
            
            <!-- Left Column: Operating Income Streams -->
            <div class="ifs-statement-card">
                <div class="statement-card-head bg-income">
                    <div class="head-title-wrap">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <div>
                            <h3 class="statement-head-title">Operating Revenue (Gross Margins)</h3>
                            <span class="statement-head-sub">Net commissions retained from service sales</span>
                        </div>
                    </div>
                    <span class="statement-pill pill-income">Revenue Inflow</span>
                </div>

                <div class="statement-card-body">
                    <table class="ifs-statement-table">
                        <tbody>
                            <tr>
                                <td>
                                    <div class="stream-title-cell">
                                        <span class="dashicons dashicons-airplane color-blue"></span>
                                        <div>
                                            <strong>Air Ticketing &amp; PNR Commissions</strong>
                                            <span class="stream-meta">IATA / GDS &amp; Sub-Agent Bookings</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="font-mono amount-val">৳<?php echo number_format( $ticket_profit, 2 ); ?></td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="stream-title-cell">
                                        <span class="dashicons dashicons-id-alt color-amber"></span>
                                        <div>
                                            <strong>Visa Processing Margin Yield</strong>
                                            <span class="stream-meta">Embassy service charges &amp; fees</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="font-mono amount-val">৳<?php echo number_format( $visa_profit, 2 ); ?></td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="stream-title-cell">
                                        <span class="dashicons dashicons-awards color-emerald"></span>
                                        <div>
                                            <strong>Hajj &amp; Umrah Pilgrim Packages</strong>
                                            <span class="stream-meta">Pilgrim package margin surplus</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="font-mono amount-val">৳<?php echo number_format( $hajj_profit, 2 ); ?></td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="stream-title-cell">
                                        <span class="dashicons dashicons-building color-indigo"></span>
                                        <div>
                                            <strong>Hotel &amp; Resort Reservations</strong>
                                            <span class="stream-meta">Direct contracts &amp; supplier vouchers</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="font-mono amount-val">৳<?php echo number_format( $hotel_profit, 2 ); ?></td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="stream-title-cell">
                                        <span class="dashicons dashicons-palmtree color-rose"></span>
                                        <div>
                                            <strong>Holiday &amp; Tour Packages</strong>
                                            <span class="stream-meta">Custom itineraries &amp; group packages</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="font-mono amount-val">৳<?php echo number_format( $tour_profit, 2 ); ?></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="statement-subtotal-row income-total">
                                <td><strong>TOTAL GROSS REVENUE INFLOW</strong></td>
                                <td class="font-mono total-amount color-emerald">৳<?php echo number_format( $total_gross_income, 2 ); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Right Column: Operating & Administrative Expenses -->
            <div class="ifs-statement-card">
                <div class="statement-card-head bg-expense">
                    <div class="head-title-wrap">
                        <span class="dashicons dashicons-minus"></span>
                        <div>
                            <h3 class="statement-head-title">Administrative &amp; Overhead Expenses</h3>
                            <span class="statement-head-sub">Rent, salaries, utilities, and general ledger expenses</span>
                        </div>
                    </div>
                    <span class="statement-pill pill-expense">Expense Outflow</span>
                </div>

                <div class="statement-card-body">
                    <table class="ifs-statement-table">
                        <tbody>
                            <?php if ( $expenses ) : foreach ( $expenses as $exp ) : ?>
                                <tr>
                                    <td>
                                        <div class="stream-title-cell">
                                            <span class="dashicons dashicons-money-alt" style="color: #94a3b8;"></span>
                                            <div>
                                                <strong><?php echo esc_html( $exp->category ); ?></strong>
                                                <span class="stream-meta">General Ledger Disbursement</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="font-mono amount-val color-rose">৳<?php echo number_format( (float) $exp->total_amount, 2 ); ?></td>
                                </tr>
                            <?php endforeach; else : ?>
                                <tr>
                                    <td colspan="2" class="empty-expense-cell">
                                        <span class="dashicons dashicons-yes-alt"></span>
                                        <p>No operational overhead expenses recorded in this period.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="statement-subtotal-row expense-total">
                                <td><strong>TOTAL OPERATING DISBURSEMENTS</strong></td>
                                <td class="font-mono total-amount color-rose">৳<?php echo number_format( $total_expenses, 2 ); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>

        <!-- Net Operating Bottom-Line Hero Banner -->
        <div class="ifs-pnl-summary-hero <?php echo $is_profit ? 'hero-profit' : 'hero-loss'; ?>">
            <div class="hero-left-meta">
                <span class="hero-tag"><span class="dashicons dashicons-shield"></span> Fiscal Reconciliation Summary</span>
                <h3 class="hero-title"><?php echo $is_profit ? 'Net Operating Profit (Surplus)' : 'Net Operating Loss (Deficit)'; ?></h3>
                <p class="hero-desc">
                    Statement period covers <strong><?php echo date( 'd M Y', strtotime( $start_date ) ); ?></strong> to <strong><?php echo date( 'd M Y', strtotime( $end_date ) ); ?></strong> after deducting all overhead expense disbursements.
                </p>
            </div>
            <div class="hero-right-figure">
                <span class="hero-figure-lbl">NET BOTTOM-LINE BALANCE</span>
                <div class="hero-figure-amount font-mono">
                    <?php echo $is_profit ? '+৳' : '-৳'; ?><?php echo number_format( abs( $net_profit ), 2 ); ?>
                </div>
                <div class="hero-yield-badge font-mono">
                    Operating Yield Margin: <strong><?php echo number_format( $margin_pct, 1 ); ?>%</strong>
                </div>
            </div>
        </div>

    </div>

    <!-- Ultra-Modern Stylesheet -->
    <style>
        .ifs-pnl-wrapper {
            max-width: 1420px;
            margin: 0 auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #0f172a;
        }

        /* Header */
        .ifs-pnl-header {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04);
            margin-bottom: 22px;
        }
        .ifs-pnl-badge {
            background: #eff6ff;
            color: #1d4ed8;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-bottom: 6px;
            border: 1px solid #dbeafe;
        }
        .ifs-pnl-badge .dashicons { font-size: 13px; width: 13px; height: 13px; }
        .pnl-header-title-group h2 { margin: 0 0 4px 0; font-size: 21px; font-weight: 900; color: #0f172a; letter-spacing: -0.3px; }
        .pnl-header-title-group p { margin: 0; font-size: 13px; color: #64748b; }

        .ifs-pnl-header-actions { display: flex; gap: 10px; align-items: center; }
        .ifs-btn-action-sec {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #334155;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .ifs-btn-action-sec:hover { background: #e2e8f0; color: #0f172a; }
        .ifs-btn-action-pri {
            background: linear-gradient(135deg, #003376 0%, #0284c7 100%);
            color: #ffffff !important;
            border: none;
            padding: 10px 22px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 12px rgba(0, 51, 118, 0.25);
            transition: all 0.2s ease;
        }
        .ifs-btn-action-pri:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(0, 51, 118, 0.35); }
        .ifs-btn-action-pri .dashicons, .ifs-btn-action-sec .dashicons { font-size: 16px; width: 16px; height: 16px; }

        /* Filter Card */
        .ifs-filter-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 22px 26px;
            margin-bottom: 24px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
        }
        .filter-grid-layout {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)) auto;
            gap: 16px;
            align-items: flex-end;
        }
        @media (max-width: 768px) {
            .filter-grid-layout { grid-template-columns: 1fr; }
        }
        .filter-field { display: flex; flex-direction: column; gap: 5px; }
        .filter-field label { font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.4px; }
        .filter-input-wrap { position: relative; display: flex; align-items: center; width: 100%; }
        .filter-input-wrap .dashicons {
            position: absolute;
            left: 12px;
            color: #94a3b8;
            font-size: 16px;
            width: 16px;
            height: 16px;
            pointer-events: none;
        }
        .ifs-input-field {
            width: 100%;
            padding: 9px 12px 9px 38px !important;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13.5px;
            color: #0f172a;
            background: #ffffff;
            outline: none;
            transition: all 0.2s ease;
        }
        .ifs-input-field:focus { border-color: #003376; box-shadow: 0 0 0 3px rgba(0, 51, 118, 0.12); }
        .ifs-btn-filter-submit {
            background: #0f172a;
            color: #ffffff;
            border: none;
            padding: 10px 22px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 38px;
            transition: all 0.2s ease;
        }
        .ifs-btn-filter-submit:hover { background: #1e293b; }
        .ifs-btn-filter-submit .dashicons { font-size: 15px; width: 15px; height: 15px; }

        /* KPI Ribbon */
        .ifs-pnl-metrics-grid {
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
            box-shadow: 0 2px 8px -2px rgba(15, 23, 42, 0.03);
            border-left: 5px solid #cbd5e1;
        }
        .ifs-kpi-chip.border-blue { border-left-color: #0284c7; }
        .ifs-kpi-chip.border-emerald { border-left-color: #059669; }
        .ifs-kpi-chip.border-rose { border-left-color: #e11d48; }

        .kpi-chip-icon { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #ffffff; flex-shrink: 0; }
        .kpi-chip-icon.bg-blue { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); }
        .kpi-chip-icon.bg-emerald { background: linear-gradient(135deg, #059669 0%, #047857 100%); }
        .kpi-chip-icon.bg-rose { background: linear-gradient(135deg, #e11d48 0%, #be123c 100%); }
        .kpi-chip-icon .dashicons { font-size: 22px; width: 22px; height: 22px; }

        .kpi-chip-lbl { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; display: block; margin-bottom: 2px; }
        .kpi-chip-val { font-size: 19px; font-weight: 900; color: #0f172a; display: block; letter-spacing: -0.5px; }
        .kpi-chip-sub { font-size: 11px; color: #64748b; font-weight: 600; margin-top: 2px; display: block; }
        .color-blue { color: #003376 !important; }
        .color-emerald { color: #059669 !important; }
        .color-rose { color: #e11d48 !important; }
        .color-indigo { color: #4f46e5 !important; }
        .color-amber { color: #d97706 !important; }

        /* 2-Column Split Cards */
        .ifs-pnl-split-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
            align-items: flex-start;
        }
        @media (max-width: 960px) {
            .ifs-pnl-split-grid { grid-template-columns: 1fr; }
        }

        .ifs-statement-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
            overflow: hidden;
        }
        .statement-card-head {
            padding: 18px 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
        }
        .statement-card-head.bg-income { background: #f0fdf4; }
        .statement-card-head.bg-expense { background: #fef2f2; }

        .head-title-wrap { display: flex; align-items: center; gap: 10px; }
        .head-title-wrap .dashicons {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 16px;
        }
        .bg-income .dashicons { background: #059669; }
        .bg-expense .dashicons { background: #e11d48; }

        .statement-head-title { margin: 0; font-size: 15px; font-weight: 800; color: #0f172a; }
        .statement-head-sub { font-size: 11.5px; color: #64748b; margin-top: 1px; display: block; }
        .statement-pill { font-size: 10.5px; font-weight: 800; text-transform: uppercase; padding: 2px 8px; border-radius: 6px; }
        .pill-income { background: #dcfce7; color: #15803d; }
        .pill-expense { background: #fee2e2; color: #b91c1c; }

        .statement-card-body { padding: 8px 22px 18px 22px; }
        .ifs-statement-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        .ifs-statement-table tbody td { padding: 12px 0; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .stream-title-cell { display: flex; align-items: center; gap: 10px; }
        .stream-title-cell .dashicons { font-size: 18px; width: 18px; height: 18px; flex-shrink: 0; }
        .stream-meta { font-size: 11px; color: #64748b; display: block; margin-top: 1px; }
        .amount-val { text-align: right; font-weight: 700; color: #0f172a; font-size: 13.5px; }
        .empty-expense-cell { text-align: center; padding: 36px 0 !important; color: #94a3b8; font-size: 13px; }
        .empty-expense-cell .dashicons { font-size: 26px; width: 26px; height: 26px; color: #059669; margin-bottom: 4px; }

        .statement-subtotal-row td { padding: 14px 0 !important; font-size: 14px; border-top: 2px solid #cbd5e1; border-bottom: none; }
        .total-amount { text-align: right; font-size: 15.5px; font-weight: 900; }

        /* Net Profit/Loss Bottom Line Hero Banner */
        .ifs-pnl-summary-hero {
            border-radius: 16px;
            padding: 26px 30px;
            color: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            box-shadow: 0 12px 30px -4px rgba(15, 23, 42, 0.1);
        }
        .hero-profit {
            background: linear-gradient(135deg, #065f46 0%, #047857 50%, #059669 100%);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        .hero-loss {
            background: linear-gradient(135deg, #881337 0%, #9f1239 50%, #e11d48 100%);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .hero-tag {
            background: rgba(255, 255, 255, 0.2);
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-bottom: 6px;
        }
        .hero-tag .dashicons { font-size: 13px; width: 13px; height: 13px; }
        .hero-title { margin: 0 0 4px 0; font-size: 22px; font-weight: 900; letter-spacing: -0.3px; }
        .hero-desc { margin: 0; font-size: 13px; opacity: 0.9; max-width: 520px; line-height: 1.5; }

        .hero-right-figure { text-align: right; }
        .hero-figure-lbl { font-size: 10px; font-weight: 800; letter-spacing: 0.8px; opacity: 0.85; display: block; margin-bottom: 2px; }
        .hero-figure-amount { font-size: 30px; font-weight: 900; letter-spacing: -0.5px; }
        .hero-yield-badge {
            margin-top: 4px;
            font-size: 12px;
            background: rgba(0, 0, 0, 0.2);
            padding: 4px 10px;
            border-radius: 6px;
            display: inline-block;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .font-bold { font-weight: 700; }

        /* Print Optimization */
        @media print {
            #adminmenuback, #adminmenuwrap, #wpadminbar, #wpfooter, .ifs-pnl-header-actions, .ifs-filter-card {
                display: none !important;
            }
            #wpcontent, #wpbody-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            body.wp-admin { background: #ffffff !important; }
            .ifs-pnl-wrapper { max-width: 100% !important; padding: 0 !important; }
            .ifs-statement-card, .ifs-pnl-summary-hero {
                box-shadow: none !important;
                border: 1px solid #000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
    <?php
}