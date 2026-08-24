<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enterprise Next-Gen Comprehensive Sales & Revenue Report Page
 * Features: Native Aggregations, Performance Margin Matrix, CSV Export & Executive Print Layout
 */
function ifs_terp_report_sales_page() {
    global $wpdb;

    $table_tickets = $wpdb->prefix . 'iterp_tickets';
    $table_visas   = $wpdb->prefix . 'iterp_visa_applications';
    $table_hajj    = $wpdb->prefix . 'iterp_hajj_bookings';
    $table_hotels  = $wpdb->prefix . 'iterp_hotel_bookings';
    $table_tours   = $wpdb->prefix . 'iterp_tours';

    $start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : date( 'Y-m-01' );
    $end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : date( 'Y-m-d' );

    // Handle CSV Export
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'export_csv' ) {
        if ( ! current_user_can( 'manage_options' ) && ! ifs_terp_has_access( array( 'accountant', 'admin_manager' ) ) ) {
            wp_die( 'Unauthorized export request.' );
        }

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=ifs-sales-report-' . $start_date . '-to-' . $end_date . '.csv' );

        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, array( 'Operations Module', 'Total Volume / Files', 'Gross Turnover (BDT)', 'Gross Profit (BDT)', 'Margin Ratio (%)' ) );

        $modules_export = array(
            'tickets' => array( 'Air Ticketing & PNR Issuance', $table_tickets, "status != 'Void'" ),
            'visas'   => array( 'Visa Processing Applications', $table_visas, "status != 'Rejected'" ),
            'hajj'    => array( 'Hajj & Umrah Pilgrims', $table_hajj, "status != 'Cancelled'" ),
            'hotels'  => array( 'Hotel & Resort Reservations', $table_hotels, "status != 'Cancelled'" ),
            'tours'   => array( 'Holiday & Tour Packages', $table_tours, "status != 'Cancelled'" ),
        );

        $tot_vol = 0;
        $tot_rev = 0;
        $tot_prf = 0;

        foreach ( $modules_export as $m_info ) {
            $row_data = $wpdb->get_row( $wpdb->prepare(
                "SELECT COUNT(id) as cnt, COALESCE(SUM(sell_price), 0) as sell, COALESCE(SUM(profit), 0) as prf 
                 FROM {$m_info[1]} 
                 WHERE DATE(created_at) BETWEEN %s AND %s AND {$m_info[2]}",
                $start_date,
                $end_date
            ) );

            $cnt = (int) ( $row_data->cnt ?? 0 );
            $rev = (float) ( $row_data->sell ?? 0 );
            $prf = (float) ( $row_data->prf ?? 0 );
            $rat = ( $rev > 0 ) ? number_format( ( $prf / $rev ) * 100, 1 ) . '%' : '0.0%';

            $tot_vol += $cnt;
            $tot_rev += $rev;
            $tot_prf += $prf;

            fputcsv( $output, array( $m_info[0], $cnt, $rev, $prf, $rat ) );
        }

        $grand_margin = ( $tot_rev > 0 ) ? number_format( ( $tot_prf / $tot_rev ) * 100, 1 ) . '%' : '0.0%';
        fputcsv( $output, array( 'GRAND TOTAL CONSOLIDATED', $tot_vol, $tot_rev, $tot_prf, $grand_margin ) );

        fclose( $output );
        exit;
    }

    // Direct Database Aggregations
    $ticket_sales = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(id) as total_count, COALESCE(SUM(sell_price), 0) as total_sell, COALESCE(SUM(profit), 0) as total_profit FROM $table_tickets WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Void'", $start_date, $end_date ) );
    $visa_sales   = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(id) as total_count, COALESCE(SUM(sell_price), 0) as total_sell, COALESCE(SUM(profit), 0) as total_profit FROM $table_visas WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Rejected'", $start_date, $end_date ) );
    $hajj_sales   = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(id) as total_count, COALESCE(SUM(sell_price), 0) as total_sell, COALESCE(SUM(profit), 0) as total_profit FROM $table_hajj WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Cancelled'", $start_date, $end_date ) );
    $hotel_sales  = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(id) as total_count, COALESCE(SUM(sell_price), 0) as total_sell, COALESCE(SUM(profit), 0) as total_profit FROM $table_hotels WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Cancelled'", $start_date, $end_date ) );
    $tour_sales   = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(id) as total_count, COALESCE(SUM(sell_price), 0) as total_sell, COALESCE(SUM(profit), 0) as total_profit FROM $table_tours WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Cancelled'", $start_date, $end_date ) );

    $t_sell   = (float) ( $ticket_sales->total_sell ?? 0 );
    $t_profit = (float) ( $ticket_sales->total_profit ?? 0 );
    $t_count  = (int) ( $ticket_sales->total_count ?? 0 );

    $v_sell   = (float) ( $visa_sales->total_sell ?? 0 );
    $v_profit = (float) ( $visa_sales->total_profit ?? 0 );
    $v_count  = (int) ( $visa_sales->total_count ?? 0 );

    $h_sell   = (float) ( $hajj_sales->total_sell ?? 0 );
    $h_profit = (float) ( $hajj_sales->total_profit ?? 0 );
    $h_count  = (int) ( $hajj_sales->total_count ?? 0 );

    $ht_sell   = (float) ( $hotel_sales->total_sell ?? 0 );
    $ht_profit = (float) ( $hotel_sales->total_profit ?? 0 );
    $ht_count  = (int) ( $hotel_sales->total_count ?? 0 );

    $tour_sell   = (float) ( $tour_sales->total_sell ?? 0 );
    $tour_profit = (float) ( $tour_sales->total_profit ?? 0 );
    $tour_count  = (int) ( $tour_sales->total_count ?? 0 );

    $grand_turnover = $t_sell + $v_sell + $h_sell + $ht_sell + $tour_sell;
    $grand_profit   = $t_profit + $v_profit + $h_profit + $ht_profit + $tour_profit;
    $total_files    = $t_count + $v_count + $h_count + $ht_count + $tour_count;
    $overall_margin = ( $grand_turnover > 0 ) ? number_format( ( $grand_profit / $grand_turnover ) * 100, 1 ) : '0.0';

    $base_url   = admin_url( 'admin.php?page=ifs_travel_erp&tab=reports&sub=sales' );
    $export_url = add_query_arg( array( 'start_date' => $start_date, 'end_date' => $end_date, 'action' => 'export_csv' ), $base_url );
    ?>

    <div class="wrap ifs-report-wrapper">
        
        <!-- Header & Action Toolbar -->
        <div class="ifs-report-header">
            <div class="ifs-report-title-group">
                <span class="ifs-report-badge"><span class="dashicons dashicons-analytics"></span> Financial Performance Ledger</span>
                <h2>Comprehensive Sales &amp; Revenue Overview</h2>
                <p>Audited turnover, gross operating profits, and yield ratios categorized by operations desk.</p>
            </div>
            <div class="ifs-report-action-buttons">
                <a href="<?php echo esc_url( $export_url ); ?>" class="ifs-btn-action-sec">
                    <span class="dashicons dashicons-download"></span> Export CSV
                </a>
                <button type="button" onclick="window.print();" class="ifs-btn-action-pri">
                    <span class="dashicons dashicons-printer"></span> Print Executive Report
                </button>
            </div>
        </div>

        <!-- Date Range Filter Toolbar -->
        <div class="ifs-filter-card">
            <form method="get" action="">
                <input type="hidden" name="page" value="ifs_travel_erp">
                <input type="hidden" name="tab" value="reports">
                <input type="hidden" name="sub" value="sales">

                <div class="filter-grid-layout">
                    <div class="filter-field">
                        <label for="start_date">From Start Date</label>
                        <div class="filter-input-wrap">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <input type="date" name="start_date" id="start_date" value="<?php echo esc_attr( $start_date ); ?>" class="ifs-input-field">
                        </div>
                    </div>

                    <div class="filter-field">
                        <label for="end_date">To End Date</label>
                        <div class="filter-input-wrap">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <input type="date" name="end_date" id="end_date" value="<?php echo esc_attr( $end_date ); ?>" class="ifs-input-field">
                        </div>
                    </div>

                    <div class="filter-field filter-btn-wrap">
                        <button type="submit" class="ifs-btn-filter-submit">
                            <span class="dashicons dashicons-filter"></span> Filter Sales Ledger
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Summary KPI Cards Ribbon -->
        <div class="ifs-report-kpi-grid">
            <div class="ifs-kpi-chip border-blue">
                <div class="kpi-chip-icon bg-blue"><span class="dashicons dashicons-chart-line"></span></div>
                <div>
                    <span class="kpi-chip-lbl">Gross Sales Turnover</span>
                    <strong class="kpi-chip-val color-blue font-mono">৳<?php echo number_format( $grand_turnover, 2 ); ?></strong>
                    <span class="kpi-chip-sub">Total Billed Volume</span>
                </div>
            </div>

            <div class="ifs-kpi-chip border-emerald">
                <div class="kpi-chip-icon bg-emerald"><span class="dashicons dashicons-money-alt"></span></div>
                <div>
                    <span class="kpi-chip-lbl">Total Gross Margin</span>
                    <strong class="kpi-chip-val color-emerald font-mono">৳<?php echo number_format( $grand_profit, 2 ); ?></strong>
                    <span class="kpi-chip-sub">Net Profit Margin (<?php echo $overall_margin; ?>%)</span>
                </div>
            </div>

            <div class="ifs-kpi-chip border-indigo">
                <div class="kpi-chip-icon bg-indigo"><span class="dashicons dashicons-tickets-alt"></span></div>
                <div>
                    <span class="kpi-chip-lbl">Air Tickets Volume</span>
                    <strong class="kpi-chip-val font-mono">৳<?php echo number_format( $t_sell, 2 ); ?></strong>
                    <span class="kpi-chip-sub"><?php echo number_format( $t_count ); ?> Issued PNRs</span>
                </div>
            </div>

            <div class="ifs-kpi-chip border-amber">
                <div class="kpi-chip-icon bg-amber"><span class="dashicons dashicons-portfolio"></span></div>
                <div>
                    <span class="kpi-chip-lbl">Total Operational Files</span>
                    <strong class="kpi-chip-val"><?php echo number_format( $total_files ); ?> Files</strong>
                    <span class="kpi-chip-sub">Combined 5 Core Desks</span>
                </div>
            </div>
        </div>

        <!-- Master Detailed Breakdown Table Card -->
        <div class="ifs-report-table-card">
            <div class="ifs-table-card-head">
                <h3 class="table-card-heading">
                    <span class="dashicons dashicons-analytics"></span> Module-Wise Revenue, Cost &amp; Profit Yield Matrix
                </h3>
                <span class="table-card-date-badge">
                    Period: <?php echo date( 'd M Y', strtotime( $start_date ) ); ?> &mdash; <?php echo date( 'd M Y', strtotime( $end_date ) ); ?>
                </span>
            </div>

            <div class="ifs-table-responsive">
                <table class="ifs-report-datatable">
                    <thead>
                        <tr>
                            <th>Operations Module Desk</th>
                            <th style="text-align: center;">Total Volume / Files</th>
                            <th style="text-align: right;">Gross Turnover (৳)</th>
                            <th style="text-align: right;">Gross Profit (৳)</th>
                            <th style="text-align: right;">Yield Margin %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Row 1: Air Ticketing -->
                        <tr>
                            <td>
                                <div class="mod-cell-title">
                                    <span class="dashicons dashicons-airplane" style="color: #0284c7;"></span>
                                    <strong>Air Ticketing &amp; PNR Issuance</strong>
                                </div>
                            </td>
                            <td style="text-align: center;"><span class="vol-badge"><?php echo number_format( $t_count ); ?> Tickets</span></td>
                            <td style="text-align: right;" class="font-mono">৳<?php echo number_format( $t_sell, 2 ); ?></td>
                            <td style="text-align: right;" class="font-mono color-emerald font-bold">৳<?php echo number_format( $t_profit, 2 ); ?></td>
                            <td style="text-align: right;" class="font-mono font-bold"><?php echo ( $t_sell > 0 ) ? number_format( ( $t_profit / $t_sell ) * 100, 1 ) : '0.0'; ?>%</td>
                        </tr>

                        <!-- Row 2: Visa Processing -->
                        <tr>
                            <td>
                                <div class="mod-cell-title">
                                    <span class="dashicons dashicons-id-alt" style="color: #d97706;"></span>
                                    <strong>Visa Processing Applications</strong>
                                </div>
                            </td>
                            <td style="text-align: center;"><span class="vol-badge"><?php echo number_format( $v_count ); ?> Files</span></td>
                            <td style="text-align: right;" class="font-mono">৳<?php echo number_format( $v_sell, 2 ); ?></td>
                            <td style="text-align: right;" class="font-mono color-emerald font-bold">৳<?php echo number_format( $v_profit, 2 ); ?></td>
                            <td style="text-align: right;" class="font-mono font-bold"><?php echo ( $v_sell > 0 ) ? number_format( ( $v_profit / $v_sell ) * 100, 1 ) : '0.0'; ?>%</td>
                        </tr>

                        <!-- Row 3: Hajj & Umrah -->
                        <tr>
                            <td>
                                <div class="mod-cell-title">
                                    <span class="dashicons dashicons-awards" style="color: #059669;"></span>
                                    <strong>Hajj &amp; Umrah Pilgrims</strong>
                                </div>
                            </td>
                            <td style="text-align: center;"><span class="vol-badge"><?php echo number_format( $h_count ); ?> Pilgrims</span></td>
                            <td style="text-align: right;" class="font-mono">৳<?php echo number_format( $h_sell, 2 ); ?></td>
                            <td style="text-align: right;" class="font-mono color-emerald font-bold">৳<?php echo number_format( $h_profit, 2 ); ?></td>
                            <td style="text-align: right;" class="font-mono font-bold"><?php echo ( $h_sell > 0 ) ? number_format( ( $h_profit / $h_sell ) * 100, 1 ) : '0.0'; ?>%</td>
                        </tr>

                        <!-- Row 4: Hotel Reservations -->
                        <tr>
                            <td>
                                <div class="mod-cell-title">
                                    <span class="dashicons dashicons-building" style="color: #4f46e5;"></span>
                                    <strong>Hotel &amp; Resort Reservations</strong>
                                </div>
                            </td>
                            <td style="text-align: center;"><span class="vol-badge"><?php echo number_format( $ht_count ); ?> Bookings</span></td>
                            <td style="text-align: right;" class="font-mono">৳<?php echo number_format( $ht_sell, 2 ); ?></td>
                            <td style="text-align: right;" class="font-mono color-emerald font-bold">৳<?php echo number_format( $ht_profit, 2 ); ?></td>
                            <td style="text-align: right;" class="font-mono font-bold"><?php echo ( $ht_sell > 0 ) ? number_format( ( $ht_profit / $ht_sell ) * 100, 1 ) : '0.0'; ?>%</td>
                        </tr>

                        <!-- Row 5: Tour Packages -->
                        <tr>
                            <td>
                                <div class="mod-cell-title">
                                    <span class="dashicons dashicons-palmtree" style="color: #e11d48;"></span>
                                    <strong>Holiday &amp; Tour Packages</strong>
                                </div>
                            </td>
                            <td style="text-align: center;"><span class="vol-badge"><?php echo number_format( $tour_count ); ?> Packages</span></td>
                            <td style="text-align: right;" class="font-mono">৳<?php echo number_format( $tour_sell, 2 ); ?></td>
                            <td style="text-align: right;" class="font-mono color-emerald font-bold">৳<?php echo number_format( $tour_profit, 2 ); ?></td>
                            <td style="text-align: right;" class="font-mono font-bold"><?php echo ( $tour_sell > 0 ) ? number_format( ( $tour_profit / $tour_sell ) * 100, 1 ) : '0.0'; ?>%</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="grand-total-row">
                            <td><strong>GRAND TOTAL CONSOLIDATED</strong></td>
                            <td style="text-align: center;"><strong><?php echo number_format( $total_files ); ?> Files</strong></td>
                            <td style="text-align: right;" class="font-mono"><strong>৳<?php echo number_format( $grand_turnover, 2 ); ?></strong></td>
                            <td style="text-align: right;" class="font-mono color-emerald font-bold"><strong>৳<?php echo number_format( $grand_profit, 2 ); ?></strong></td>
                            <td style="text-align: right;" class="font-mono font-bold"><strong><?php echo $overall_margin; ?>%</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>

    <!-- Layout Stylesheet -->
    <style>
        .ifs-report-wrapper {
            max-width: 1420px;
            margin: 0 auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #0f172a;
        }

        .ifs-report-header {
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
        .ifs-report-badge {
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
        .ifs-report-badge .dashicons { font-size: 13px; width: 13px; height: 13px; }
        .ifs-report-title-group h2 { margin: 0 0 4px 0; font-size: 21px; font-weight: 900; color: #0f172a; letter-spacing: -0.3px; }
        .ifs-report-title-group p { margin: 0; font-size: 13px; color: #64748b; }

        .ifs-report-action-buttons { display: flex; gap: 10px; align-items: center; }
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
            box-shadow: 0 2px 8px -2px rgba(15, 23, 42, 0.03);
            border-left: 5px solid #cbd5e1;
        }
        .ifs-kpi-chip.border-blue { border-left-color: #0284c7; }
        .ifs-kpi-chip.border-emerald { border-left-color: #059669; }
        .ifs-kpi-chip.border-indigo { border-left-color: #4f46e5; }
        .ifs-kpi-chip.border-amber { border-left-color: #d97706; }

        .kpi-chip-icon { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #ffffff; flex-shrink: 0; }
        .kpi-chip-icon.bg-blue { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); }
        .kpi-chip-icon.bg-emerald { background: linear-gradient(135deg, #059669 0%, #047857 100%); }
        .kpi-chip-icon.bg-indigo { background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); }
        .kpi-chip-icon.bg-amber { background: linear-gradient(135deg, #d97706 0%, #b45309 100%); }
        .kpi-chip-icon .dashicons { font-size: 22px; width: 22px; height: 22px; }

        .kpi-chip-lbl { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; display: block; margin-bottom: 2px; }
        .kpi-chip-val { font-size: 19px; font-weight: 900; color: #0f172a; display: block; letter-spacing: -0.5px; }
        .kpi-chip-sub { font-size: 11px; color: #64748b; font-weight: 600; margin-top: 2px; display: block; }
        .color-blue { color: #003376 !important; }
        .color-emerald { color: #059669 !important; }

        .ifs-report-table-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
            overflow: hidden;
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
        .table-card-heading { margin: 0; font-size: 17px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        .table-card-heading .dashicons { color: #003376; font-size: 20px; width: 20px; height: 20px; }
        .table-card-date-badge { font-size: 12px; font-weight: 700; color: #475569; background: #f8fafc; padding: 5px 12px; border-radius: 6px; border: 1px solid #e2e8f0; }

        .ifs-table-responsive { padding: 15px 24px 24px 24px; overflow-x: auto; }
        .ifs-report-datatable { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        .ifs-report-datatable thead th {
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 16px;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
        }
        .ifs-report-datatable tbody td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: #334155;
        }
        .ifs-report-datatable tbody tr:hover td { background: #f8fafc; }
        .mod-cell-title { display: flex; align-items: center; gap: 8px; color: #0f172a; }
        .mod-cell-title .dashicons { font-size: 18px; width: 18px; height: 18px; }
        .vol-badge { background: #f1f5f9; color: #334155; font-weight: 700; font-size: 11px; padding: 3px 8px; border-radius: 6px; border: 1px solid #e2e8f0; }

        .grand-total-row { background: #f8fafc; border-top: 2px solid #cbd5e1; }
        .grand-total-row td { padding: 16px; font-size: 14.5px; color: #0f172a; }

        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }

        @media print {
            #adminmenuback, #adminmenuwrap, #wpadminbar, #wpfooter, .ifs-report-header, .ifs-filter-card {
                display: none !important;
            }
            #wpcontent, #wpbody-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            body.wp-admin { background: #ffffff !important; }
            .ifs-report-wrapper { max-width: 100% !important; padding: 0 !important; }
            .ifs-report-table-card { box-shadow: none !important; border: 1px solid #000 !important; }
        }
    </style>
    <?php
}