<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Enterprise Ultra-Modern Profit & Loss Statement Console
 * Flat Minimal UI: No Shadows, Strict 42px Uniform Interactive Heights, Normalized Clean Controls
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
            wp_die( esc_html__( 'Unauthorized export request.', 'ifs-travel-erp' ) );
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
                <span class="ifs-pnl-badge">
                    <span class="dashicons dashicons-media-spreadsheet"></span> <?php esc_html_e( 'Audited Financial Statement', 'ifs-travel-erp' ); ?>
                </span>
                <h2><?php esc_html_e( 'Comprehensive Profit & Loss Statement', 'ifs-travel-erp' ); ?></h2>
                <p><?php esc_html_e( 'Consolidated executive statement reconciling multi-module gross revenue margins against overhead operational expenses.', 'ifs-travel-erp' ); ?></p>
            </div>
            <div class="ifs-pnl-header-actions">
                <a href="<?php echo esc_url( $export_url ); ?>" class="ifs-btn-secondary">
                    <span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export CSV', 'ifs-travel-erp' ); ?>
                </a>
                <button type="button" onclick="window.print();" class="ifs-btn-primary">
                    <span class="dashicons dashicons-printer"></span> <?php esc_html_e( 'Print Statement', 'ifs-travel-erp' ); ?>
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
                        <label class="ifs-field-label" for="pnl_start_date"><?php esc_html_e( 'Period Start Date', 'ifs-travel-erp' ); ?></label>
                        <div class="ifs-field-wrap">
                            <span class="dashicons dashicons-calendar-alt field-icon"></span>
                            <input type="date" name="start_date" id="pnl_start_date" value="<?php echo esc_attr( $start_date ); ?>" class="ifs-input-field">
                        </div>
                    </div>

                    <div class="filter-field">
                        <label class="ifs-field-label" for="pnl_end_date"><?php esc_html_e( 'Period End Date', 'ifs-travel-erp' ); ?></label>
                        <div class="ifs-field-wrap">
                            <span class="dashicons dashicons-calendar-alt field-icon"></span>
                            <input type="date" name="end_date" id="pnl_end_date" value="<?php echo esc_attr( $end_date ); ?>" class="ifs-input-field">
                        </div>
                    </div>

                    <div class="filter-field filter-btn-wrap">
                        <button type="submit" class="ifs-btn-primary full-width-btn">
                            <span class="dashicons dashicons-calculator"></span> <?php esc_html_e( 'Recalculate Statement', 'ifs-travel-erp' ); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Top Financial Metric Chips -->
        <div class="ifs-pnl-metrics-grid">
            <div class="ifs-kpi-chip border-emerald">
                <div class="chip-icon bg-emerald"><span class="dashicons dashicons-arrow-up-alt"></span></div>
                <div>
                    <span class="kpi-chip-lbl"><?php esc_html_e( 'Gross Operating Income', 'ifs-travel-erp' ); ?></span>
                    <strong class="kpi-chip-val color-emerald font-mono">৳<?php echo number_format( $total_gross_income, 2 ); ?></strong>
                    <span class="kpi-chip-sub"><?php esc_html_e( 'Across 5 Operational Desks', 'ifs-travel-erp' ); ?></span>
                </div>
            </div>

            <div class="ifs-kpi-chip border-rose">
                <div class="chip-icon bg-rose"><span class="dashicons dashicons-arrow-down-alt"></span></div>
                <div>
                    <span class="kpi-chip-lbl"><?php esc_html_e( 'Overhead Expenses', 'ifs-travel-erp' ); ?></span>
                    <strong class="kpi-chip-val color-rose font-mono">৳<?php echo number_format( $total_expenses, 2 ); ?></strong>
                    <span class="kpi-chip-sub"><?php esc_html_e( 'Office Rent, Bills & Operations', 'ifs-travel-erp' ); ?></span>
                </div>
            </div>

            <div class="ifs-kpi-chip <?php echo $is_profit ? 'border-emerald' : 'border-rose'; ?>">
                <div class="chip-icon <?php echo $is_profit ? 'bg-emerald' : 'bg-rose'; ?>">
                    <span class="dashicons dashicons-chart-pie"></span>
                </div>
                <div>
                    <span class="kpi-chip-lbl"><?php esc_html_e( 'Net Operating Balance', 'ifs-travel-erp' ); ?></span>
                    <strong class="kpi-chip-val font-mono <?php echo $is_profit ? 'color-emerald' : 'color-rose'; ?>">
                        <?php echo $is_profit ? '+৳' : '-৳'; ?><?php echo number_format( abs( $net_profit ), 2 ); ?>
                    </strong>
                    <span class="kpi-chip-sub"><?php echo $is_profit ? esc_html__( 'Profitable Performance', 'ifs-travel-erp' ) : esc_html__( 'Operating Loss', 'ifs-travel-erp' ); ?></span>
                </div>
            </div>

            <div class="ifs-kpi-chip border-blue">
                <div class="chip-icon bg-blue"><span class="dashicons dashicons-performance"></span></div>
                <div>
                    <span class="kpi-chip-lbl"><?php esc_html_e( 'Net Yield Margin', 'ifs-travel-erp' ); ?></span>
                    <strong class="kpi-chip-val color-blue font-mono"><?php echo number_format( $margin_pct, 1 ); ?>%</strong>
                    <span class="kpi-chip-sub"><?php esc_html_e( 'Profit to Revenue Ratio', 'ifs-travel-erp' ); ?></span>
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
                            <h3 class="statement-head-title"><?php esc_html_e( 'Operating Revenue (Gross Margins)', 'ifs-travel-erp' ); ?></h3>
                            <span class="statement-head-sub"><?php esc_html_e( 'Net commissions retained from service sales', 'ifs-travel-erp' ); ?></span>
                        </div>
                    </div>
                    <span class="statement-pill pill-income"><?php esc_html_e( 'Revenue Inflow', 'ifs-travel-erp' ); ?></span>
                </div>

                <div class="statement-card-body">
                    <table class="ifs-statement-table">
                        <tbody>
                            <tr>
                                <td>
                                    <div class="stream-title-cell">
                                        <span class="dashicons dashicons-airplane color-blue"></span>
                                        <div>
                                            <strong><?php esc_html_e( 'Air Ticketing & PNR Commissions', 'ifs-travel-erp' ); ?></strong>
                                            <span class="stream-meta"><?php esc_html_e( 'IATA / GDS & Sub-Agent Bookings', 'ifs-travel-erp' ); ?></span>
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
                                            <strong><?php esc_html_e( 'Visa Processing Margin Yield', 'ifs-travel-erp' ); ?></strong>
                                            <span class="stream-meta"><?php esc_html_e( 'Embassy service charges & fees', 'ifs-travel-erp' ); ?></span>
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
                                            <strong><?php esc_html_e( 'Hajj & Umrah Pilgrim Packages', 'ifs-travel-erp' ); ?></strong>
                                            <span class="stream-meta"><?php esc_html_e( 'Pilgrim package margin surplus', 'ifs-travel-erp' ); ?></span>
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
                                            <strong><?php esc_html_e( 'Hotel & Resort Reservations', 'ifs-travel-erp' ); ?></strong>
                                            <span class="stream-meta"><?php esc_html_e( 'Direct contracts & supplier vouchers', 'ifs-travel-erp' ); ?></span>
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
                                            <strong><?php esc_html_e( 'Holiday & Tour Packages', 'ifs-travel-erp' ); ?></strong>
                                            <span class="stream-meta"><?php esc_html_e( 'Custom itineraries & group packages', 'ifs-travel-erp' ); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="font-mono amount-val">৳<?php echo number_format( $tour_profit, 2 ); ?></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="statement-subtotal-row income-total">
                                <td><strong><?php esc_html_e( 'TOTAL GROSS REVENUE INFLOW', 'ifs-travel-erp' ); ?></strong></td>
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
                            <h3 class="statement-head-title"><?php esc_html_e( 'Administrative & Overhead Expenses', 'ifs-travel-erp' ); ?></h3>
                            <span class="statement-head-sub"><?php esc_html_e( 'Rent, salaries, utilities, and general ledger expenses', 'ifs-travel-erp' ); ?></span>
                        </div>
                    </div>
                    <span class="statement-pill pill-expense"><?php esc_html_e( 'Expense Outflow', 'ifs-travel-erp' ); ?></span>
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
                                                <span class="stream-meta"><?php esc_html_e( 'General Ledger Disbursement', 'ifs-travel-erp' ); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="font-mono amount-val color-rose">৳<?php echo number_format( (float) $exp->total_amount, 2 ); ?></td>
                                </tr>
                            <?php endforeach; else : ?>
                                <tr>
                                    <td colspan="2" class="empty-expense-cell">
                                        <span class="dashicons dashicons-yes-alt"></span>
                                        <p><?php esc_html_e( 'No operational overhead expenses recorded in this period.', 'ifs-travel-erp' ); ?></p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="statement-subtotal-row expense-total">
                                <td><strong><?php esc_html_e( 'TOTAL OPERATING DISBURSEMENTS', 'ifs-travel-erp' ); ?></strong></td>
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
                <span class="hero-tag"><span class="dashicons dashicons-shield"></span> <?php esc_html_e( 'Fiscal Reconciliation Summary', 'ifs-travel-erp' ); ?></span>
                <h3 class="hero-title"><?php echo $is_profit ? esc_html__( 'Net Operating Profit (Surplus)', 'ifs-travel-erp' ) : esc_html__( 'Net Operating Loss (Deficit)', 'ifs-travel-erp' ); ?></h3>
                <p class="hero-desc">
                    <?php 
                    printf( 
                        esc_html__( 'Statement period covers %1$s to %2$s after deducting all overhead expense disbursements.', 'ifs-travel-erp' ),
                        '<strong>' . date( 'd M Y', strtotime( $start_date ) ) . '</strong>',
                        '<strong>' . date( 'd M Y', strtotime( $end_date ) ) . '</strong>'
                    ); 
                    ?>
                </p>
            </div>
            <div class="hero-right-figure">
                <span class="hero-figure-lbl"><?php esc_html_e( 'NET BOTTOM-LINE BALANCE', 'ifs-travel-erp' ); ?></span>
                <div class="hero-figure-amount font-mono">
                    <?php echo $is_profit ? '+৳' : '-৳'; ?><?php echo number_format( abs( $net_profit ), 2 ); ?>
                </div>
                <div class="hero-yield-badge font-mono">
                    <?php printf( esc_html__( 'Operating Yield Margin: %s', 'ifs-travel-erp' ), '<strong>' . number_format( $margin_pct, 1 ) . '%</strong>' ); ?>
                </div>
            </div>
        </div>

    </div>

    <!-- Stylesheet: Flat Minimal UI, Zero Shadows & Strict 42px Uniform Field Heights -->
    <style>
        .ifs-pnl-wrapper {
            max-width: 1420px;
            margin: 20px auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #0f172a;
            box-sizing: border-box;
        }
        .ifs-pnl-wrapper *,
        .ifs-pnl-wrapper *::before,
        .ifs-pnl-wrapper *::after {
            box-sizing: border-box;
            box-shadow: none !important;
            text-shadow: none !important;
        }

        /* Header */
        .ifs-pnl-header {
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
        .ifs-pnl-badge {
            background: #eff6ff;
            color: #003376;
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
            border: 1px solid #bfdbfe;
        }
        .ifs-pnl-badge .dashicons { font-size: 14px; width: 14px; height: 14px; }
        .pnl-header-title-group h2 { margin: 0 0 4px 0; font-size: 20px; font-weight: 800; color: #0f172a; }
        .pnl-header-title-group p { margin: 0; font-size: 13px; color: #64748b; }

        .ifs-pnl-header-actions { display: flex; gap: 10px; align-items: center; }
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
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background-color 0.2s ease;
        }
        .ifs-btn-primary:hover { background: #0284c7; }
        .ifs-btn-primary .dashicons, .ifs-btn-secondary .dashicons { font-size: 16px; width: 16px; height: 16px; }

        /* Filter Card */
        .ifs-filter-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 22px 26px;
            margin-bottom: 24px;
        }
        .filter-grid-layout {
            display: grid;
            grid-template-columns: repeat(2, 1fr) auto;
            gap: 18px;
            align-items: flex-end;
        }
        @media (max-width: 860px) {
            .filter-grid-layout { grid-template-columns: 1fr; }
            .full-width-btn { width: 100%; }
        }
        .filter-field { 
            display: flex; 
            flex-direction: column; 
            justify-content: flex-start;
            gap: 6px; 
            width: 100%;
        }
        .ifs-field-label { 
            font-size: 11px; 
            font-weight: 700; 
            color: #475569; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            line-height: 1.2;
        }
        .ifs-field-wrap { 
            position: relative; 
            display: flex; 
            align-items: center; 
            width: 100%; 
            height: 42px; 
        }
        .ifs-field-wrap .field-icon {
            position: absolute; 
            left: 12px; 
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8; 
            font-size: 18px; 
            width: 18px; 
            height: 18px; 
            line-height: 18px;
            pointer-events: none; 
            z-index: 3; 
            transition: color 0.2s ease;
        }
        .ifs-input-field { 
            width: 100% !important; 
            height: 42px !important; 
            max-height: 42px !important; 
            min-height: 42px !important; 
            line-height: 40px !important; 
            padding: 0 14px 0 40px !important; 
            border: 1px solid #cbd5e1 !important; 
            border-radius: 8px !important; 
            font-size: 13.5px !important; 
            color: #0f172a !important; 
            background-color: #ffffff !important; 
            outline: none !important; 
            transition: border-color 0.2s ease, background-color 0.2s ease; 
            margin: 0 !important; 
            display: block; 
        }
        input[type="date"].ifs-input-field { cursor: pointer; }
        input[type="date"].ifs-input-field::-webkit-calendar-picker-indicator { 
            opacity: 0.6; 
            cursor: pointer; 
            margin-right: -4px; 
            transition: opacity 0.2s ease; 
        }
        input[type="date"].ifs-input-field::-webkit-calendar-picker-indicator:hover { opacity: 1; }
        .ifs-input-field:focus { 
            border-color: #003376 !important; 
            background-color: #f8fafc !important; 
        }
        .ifs-field-wrap:focus-within .field-icon { 
            color: #003376; 
        }

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
        .kpi-chip-val { font-size: 19px; font-weight: 900; color: #0f172a; display: block; letter-spacing: -0.5px; }
        .kpi-chip-sub { font-size: 11px; color: #64748b; font-weight: 600; margin-top: 2px; display: block; }
        .color-blue    { color: #003376 !important; }
        .color-emerald { color: #059669 !important; }
        .color-rose    { color: #dc2626 !important; }
        .color-indigo  { color: #4f46e5 !important; }
        .color-amber   { color: #d97706 !important; }

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
            overflow: hidden;
        }
        .statement-card-head {
            padding: 18px 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
        }
        .statement-card-head.bg-income  { background: #f0fdf4; }
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
            flex-shrink: 0;
        }
        .bg-income .dashicons  { background: #059669; }
        .bg-expense .dashicons { background: #dc2626; }

        .statement-head-title { margin: 0; font-size: 15px; font-weight: 800; color: #0f172a; }
        .statement-head-sub { font-size: 11.5px; color: #64748b; margin-top: 1px; display: block; }
        .statement-pill { font-size: 10.5px; font-weight: 800; text-transform: uppercase; padding: 2px 8px; border-radius: 4px; }
        .pill-income  { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .pill-expense { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

        .statement-card-body { padding: 8px 22px 18px 22px; }
        .ifs-statement-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .ifs-statement-table tbody td { padding: 12px 0; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .stream-title-cell { display: flex; align-items: center; gap: 10px; }
        .stream-title-cell .dashicons { font-size: 18px; width: 18px; height: 18px; flex-shrink: 0; }
        .stream-meta { font-size: 11px; color: #64748b; display: block; margin-top: 1px; }
        .amount-val { text-align: right; font-weight: 700; color: #0f172a; font-size: 13.5px; }
        .empty-expense-cell { text-align: center; padding: 36px 0 !important; color: #94a3b8; font-size: 13px; }
        .empty-expense-cell .dashicons { font-size: 26px; width: 26px; height: 26px; color: #059669; margin-bottom: 4px; }

        .statement-subtotal-row td { padding: 14px 0 !important; font-size: 13.5px; border-top: 2px solid #cbd5e1; border-bottom: none; }
        .total-amount { text-align: right; font-size: 15.5px; font-weight: 900; }

        /* Net Profit/Loss Bottom Line Hero Banner (Flat Bordered Design) */
        .ifs-pnl-summary-hero {
            border-radius: 14px;
            padding: 26px 30px;
            color: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        .hero-profit {
            background: #064e3b;
            border: 1px solid #047857;
        }
        .hero-loss {
            background: #881337;
            border: 1px solid #be123c;
        }

        .hero-tag {
            background: rgba(255, 255, 255, 0.18);
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
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .hero-tag .dashicons { font-size: 13px; width: 13px; height: 13px; }
        .hero-title { margin: 0 0 4px 0; font-size: 22px; font-weight: 900; letter-spacing: -0.3px; color: #ffffff; }
        .hero-desc { margin: 0; font-size: 13px; opacity: 0.9; max-width: 520px; line-height: 1.5; color: #f1f5f9; }

        .hero-right-figure { text-align: right; }
        .hero-figure-lbl { font-size: 10px; font-weight: 800; letter-spacing: 0.8px; opacity: 0.85; display: block; margin-bottom: 2px; }
        .hero-figure-amount { font-size: 30px; font-weight: 900; letter-spacing: -0.5px; color: #ffffff; }
        .hero-yield-badge {
            margin-top: 4px;
            font-size: 12px;
            background: rgba(0, 0, 0, 0.25);
            padding: 4px 10px;
            border-radius: 6px;
            display: inline-block;
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
        }

        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important; }
        .font-bold { font-weight: 700; }

        /* Print Optimization */
        @media print {
            #adminmenuback, #adminmenuwrap, #wpadminbar, #wpfooter, .ifs-pnl-header-actions, .ifs-filter-card {
                display: none !important;
            }
            #wpcontent, #wpbody-content { margin-left: 0 !important; padding: 0 !important; }
            body.wp-admin { background: #ffffff !important; }
            .ifs-pnl-wrapper { max-width: 100% !important; padding: 0 !important; }
            .ifs-statement-card, .ifs-pnl-summary-hero {
                border: 1px solid #000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
    <?php
}