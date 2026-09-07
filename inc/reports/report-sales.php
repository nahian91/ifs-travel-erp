<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Comprehensive Sales & Revenue Report Page
 * Flat Minimal UI: No Shadows, Strict 42px Equal Field & Button Heights, Clean Border Architecture
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
            wp_die( esc_html__( 'Unauthorized export request.', 'ifs-travel-erp' ) );
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
    $ticket_sales = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(id) as total_count, COALESCE(SUM(sell_price), 0) as total_sell, COALESCE(SUM(profit), 0) as total_profit FROM {$table_tickets} WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Void'", $start_date, $end_date ) );
    $visa_sales   = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(id) as total_count, COALESCE(SUM(sell_price), 0) as total_sell, COALESCE(SUM(profit), 0) as total_profit FROM {$table_visas} WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Rejected'", $start_date, $end_date ) );
    $hajj_sales   = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(id) as total_count, COALESCE(SUM(sell_price), 0) as total_sell, COALESCE(SUM(profit), 0) as total_profit FROM {$table_hajj} WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Cancelled'", $start_date, $end_date ) );
    $hotel_sales  = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(id) as total_count, COALESCE(SUM(sell_price), 0) as total_sell, COALESCE(SUM(profit), 0) as total_profit FROM {$table_hotels} WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Cancelled'", $start_date, $end_date ) );
    $tour_sales   = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(id) as total_count, COALESCE(SUM(sell_price), 0) as total_sell, COALESCE(SUM(profit), 0) as total_profit FROM {$table_tours} WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Cancelled'", $start_date, $end_date ) );

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
                <span class="ifs-report-badge">
                    <span class="dashicons dashicons-analytics"></span> <?php esc_html_e( 'Financial Performance Ledger', 'ifs-travel-erp' ); ?>
                </span>
                <h2><?php esc_html_e( 'Sales & Revenue Overview', 'ifs-travel-erp' ); ?></h2>
                <p><?php esc_html_e( 'Audited turnover, gross operating profits, and yield ratios categorized by operations desk.', 'ifs-travel-erp' ); ?></p>
            </div>
            <div class="ifs-report-action-buttons">
                <a href="<?php echo esc_url( $export_url ); ?>" class="ifs-btn-secondary">
                    <span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export CSV', 'ifs-travel-erp' ); ?>
                </a>
                <button type="button" onclick="window.print();" class="ifs-btn-primary">
                    <span class="dashicons dashicons-printer"></span> <?php esc_html_e( 'Print Report', 'ifs-travel-erp' ); ?>
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
                        <label class="ifs-field-label" for="start_date"><?php esc_html_e( 'From Start Date', 'ifs-travel-erp' ); ?></label>
                        <div class="ifs-field-wrap">
                            <span class="dashicons dashicons-calendar-alt field-icon"></span>
                            <input type="date" name="start_date" id="start_date" value="<?php echo esc_attr( $start_date ); ?>" class="ifs-input-field">
                        </div>
                    </div>

                    <div class="filter-field">
                        <label class="ifs-field-label" for="end_date"><?php esc_html_e( 'To End Date', 'ifs-travel-erp' ); ?></label>
                        <div class="ifs-field-wrap">
                            <span class="dashicons dashicons-calendar-alt field-icon"></span>
                            <input type="date" name="end_date" id="end_date" value="<?php echo esc_attr( $end_date ); ?>" class="ifs-input-field">
                        </div>
                    </div>

                    <div class="filter-field filter-btn-wrap">
                        <button type="submit" class="ifs-btn-primary full-width-btn">
                            <span class="dashicons dashicons-filter"></span> <?php esc_html_e( 'Filter Sales Ledger', 'ifs-travel-erp' ); ?>
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
                    <span class="kpi-chip-lbl"><?php esc_html_e( 'Gross Sales Turnover', 'ifs-travel-erp' ); ?></span>
                    <strong class="kpi-chip-val color-blue font-mono">৳<?php echo number_format( $grand_turnover, 2 ); ?></strong>
                    <span class="kpi-chip-sub"><?php esc_html_e( 'Total Billed Volume', 'ifs-travel-erp' ); ?></span>
                </div>
            </div>

            <div class="ifs-kpi-chip border-emerald">
                <div class="kpi-chip-icon bg-emerald"><span class="dashicons dashicons-money-alt"></span></div>
                <div>
                    <span class="kpi-chip-lbl"><?php esc_html_e( 'Total Gross Margin', 'ifs-travel-erp' ); ?></span>
                    <strong class="kpi-chip-val color-emerald font-mono">৳<?php echo number_format( $grand_profit, 2 ); ?></strong>
                    <span class="kpi-chip-sub"><?php printf( esc_html__( 'Net Profit Margin (%s%%)', 'ifs-travel-erp' ), $overall_margin ); ?></span>
                </div>
            </div>

            <div class="ifs-kpi-chip border-indigo">
                <div class="kpi-chip-icon bg-indigo"><span class="dashicons dashicons-tickets-alt"></span></div>
                <div>
                    <span class="kpi-chip-lbl"><?php esc_html_e( 'Air Tickets Volume', 'ifs-travel-erp' ); ?></span>
                    <strong class="kpi-chip-val font-mono">৳<?php echo number_format( $t_sell, 2 ); ?></strong>
                    <span class="kpi-chip-sub"><?php printf( esc_html__( '%s Issued PNRs', 'ifs-travel-erp' ), number_format( $t_count ) ); ?></span>
                </div>
            </div>

            <div class="ifs-kpi-chip border-amber">
                <div class="kpi-chip-icon bg-amber"><span class="dashicons dashicons-portfolio"></span></div>
                <div>
                    <span class="kpi-chip-lbl"><?php esc_html_e( 'Total Operational Files', 'ifs-travel-erp' ); ?></span>
                    <strong class="kpi-chip-val"><?php printf( esc_html__( '%s Files', 'ifs-travel-erp' ), number_format( $total_files ) ); ?></strong>
                    <span class="kpi-chip-sub"><?php esc_html_e( 'Combined 5 Core Desks', 'ifs-travel-erp' ); ?></span>
                </div>
            </div>
        </div>

        <!-- Master Detailed Breakdown Table Card -->
        <div class="ifs-report-table-card">
            <div class="ifs-table-card-head">
                <h3 class="table-card-heading">
                    <span class="dashicons dashicons-analytics"></span> <?php esc_html_e( 'Module-Wise Revenue, Cost & Profit Yield Matrix', 'ifs-travel-erp' ); ?>
                </h3>
                <span class="table-card-date-badge">
                    <?php printf( esc_html__( 'Period: %s — %s', 'ifs-travel-erp' ), date( 'd M Y', strtotime( $start_date ) ), date( 'd M Y', strtotime( $end_date ) ) ); ?>
                </span>
            </div>

            <div class="ifs-table-responsive">
                <table class="ifs-report-datatable">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Operations Module Desk', 'ifs-travel-erp' ); ?></th>
                            <th style="text-align: center;"><?php esc_html_e( 'Total Volume / Files', 'ifs-travel-erp' ); ?></th>
                            <th style="text-align: right;"><?php esc_html_e( 'Gross Turnover (৳)', 'ifs-travel-erp' ); ?></th>
                            <th style="text-align: right;"><?php esc_html_e( 'Gross Profit (৳)', 'ifs-travel-erp' ); ?></th>
                            <th style="text-align: right;"><?php esc_html_e( 'Yield Margin %', 'ifs-travel-erp' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Row 1: Air Ticketing -->
                        <tr>
                            <td>
                                <div class="mod-cell-title">
                                    <span class="dashicons dashicons-airplane" style="color: #0284c7;"></span>
                                    <strong><?php esc_html_e( 'Air Ticketing & PNR Issuance', 'ifs-travel-erp' ); ?></strong>
                                </div>
                            </td>
                            <td style="text-align: center;"><span class="vol-badge"><?php printf( esc_html__( '%s Tickets', 'ifs-travel-erp' ), number_format( $t_count ) ); ?></span></td>
                            <td style="text-align: right;" class="font-mono">৳<?php echo number_format( $t_sell, 2 ); ?></td>
                            <td style="text-align: right;" class="font-mono color-emerald font-bold">৳<?php echo number_format( $t_profit, 2 ); ?></td>
                            <td style="text-align: right;" class="font-mono font-bold"><?php echo ( $t_sell > 0 ) ? number_format( ( $t_profit / $t_sell ) * 100, 1 ) : '0.0'; ?>%</td>
                        </tr>

                        <!-- Row 2: Visa Processing -->
                        <tr>
                            <td>
                                <div class="mod-cell-title">
                                    <span class="dashicons dashicons-id-alt" style="color: #d97706;"></span>
                                    <strong><?php esc_html_e( 'Visa Processing Applications', 'ifs-travel-erp' ); ?></strong>
                                </div>
                            </td>
                            <td style="text-align: center;"><span class="vol-badge"><?php printf( esc_html__( '%s Files', 'ifs-travel-erp' ), number_format( $v_count ) ); ?></span></td>
                            <td style="text-align: right;" class="font-mono">৳<?php echo number_format( $v_sell, 2 ); ?></td>
                            <td style="text-align: right;" class="font-mono color-emerald font-bold">৳<?php echo number_format( $v_profit, 2 ); ?></td>
                            <td style="text-align: right;" class="font-mono font-bold"><?php echo ( $v_sell > 0 ) ? number_format( ( $v_profit / $v_sell ) * 100, 1 ) : '0.0'; ?>%</td>
                        </tr>

                        <!-- Row 3: Hajj & Umrah -->
                        <tr>
                            <td>
                                <div class="mod-cell-title">
                                    <span class="dashicons dashicons-awards" style="color: #059669;"></span>
                                    <strong><?php esc_html_e( 'Hajj & Umrah Pilgrims', 'ifs-travel-erp' ); ?></strong>
                                </div>
                            </td>
                            <td style="text-align: center;"><span class="vol-badge"><?php printf( esc_html__( '%s Pilgrims', 'ifs-travel-erp' ), number_format( $h_count ) ); ?></span></td>
                            <td style="text-align: right;" class="font-mono">৳<?php echo number_format( $h_sell, 2 ); ?></td>
                            <td style="text-align: right;" class="font-mono color-emerald font-bold">৳<?php echo number_format( $h_profit, 2 ); ?></td>
                            <td style="text-align: right;" class="font-mono font-bold"><?php echo ( $h_sell > 0 ) ? number_format( ( $h_profit / $h_sell ) * 100, 1 ) : '0.0'; ?>%</td>
                        </tr>

                        <!-- Row 4: Hotel Reservations -->
                        <tr>
                            <td>
                                <div class="mod-cell-title">
                                    <span class="dashicons dashicons-building" style="color: #4f46e5;"></span>
                                    <strong><?php esc_html_e( 'Hotel & Resort Reservations', 'ifs-travel-erp' ); ?></strong>
                                </div>
                            </td>
                            <td style="text-align: center;"><span class="vol-badge"><?php printf( esc_html__( '%s Bookings', 'ifs-travel-erp' ), number_format( $ht_count ) ); ?></span></td>
                            <td style="text-align: right;" class="font-mono">৳<?php echo number_format( $ht_sell, 2 ); ?></td>
                            <td style="text-align: right;" class="font-mono color-emerald font-bold">৳<?php echo number_format( $ht_profit, 2 ); ?></td>
                            <td style="text-align: right;" class="font-mono font-bold"><?php echo ( $ht_sell > 0 ) ? number_format( ( $ht_profit / $ht_sell ) * 100, 1 ) : '0.0'; ?>%</td>
                        </tr>

                        <!-- Row 5: Tour Packages -->
                        <tr>
                            <td>
                                <div class="mod-cell-title">
                                    <span class="dashicons dashicons-palmtree" style="color: #dc2626;"></span>
                                    <strong><?php esc_html_e( 'Holiday & Tour Packages', 'ifs-travel-erp' ); ?></strong>
                                </div>
                            </td>
                            <td style="text-align: center;"><span class="vol-badge"><?php printf( esc_html__( '%s Packages', 'ifs-travel-erp' ), number_format( $tour_count ) ); ?></span></td>
                            <td style="text-align: right;" class="font-mono">৳<?php echo number_format( $tour_sell, 2 ); ?></td>
                            <td style="text-align: right;" class="font-mono color-emerald font-bold">৳<?php echo number_format( $tour_profit, 2 ); ?></td>
                            <td style="text-align: right;" class="font-mono font-bold"><?php echo ( $tour_sell > 0 ) ? number_format( ( $tour_profit / $tour_sell ) * 100, 1 ) : '0.0'; ?>%</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="grand-total-row">
                            <td><strong><?php esc_html_e( 'GRAND TOTAL CONSOLIDATED', 'ifs-travel-erp' ); ?></strong></td>
                            <td style="text-align: center;"><strong><?php printf( esc_html__( '%s Files', 'ifs-travel-erp' ), number_format( $total_files ) ); ?></strong></td>
                            <td style="text-align: right;" class="font-mono"><strong>৳<?php echo number_format( $grand_turnover, 2 ); ?></strong></td>
                            <td style="text-align: right;" class="font-mono color-emerald font-bold"><strong>৳<?php echo number_format( $grand_profit, 2 ); ?></strong></td>
                            <td style="text-align: right;" class="font-mono font-bold"><strong><?php echo $overall_margin; ?>%</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>

    <!-- Stylesheet: Flat Minimal UI, Zero Shadows & Strict 42px Uniform Field Heights -->
    <style>
        .ifs-report-wrapper {
            max-width: 1420px;
            margin: 20px auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #0f172a;
            box-sizing: border-box;
        }
        .ifs-report-wrapper *,
        .ifs-report-wrapper *::before,
        .ifs-report-wrapper *::after {
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
        .ifs-kpi-chip.border-indigo  { border-left-color: #4f46e5; }
        .ifs-kpi-chip.border-amber   { border-left-color: #d97706; }

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
        .kpi-chip-icon.bg-indigo  { background: #4f46e5; }
        .kpi-chip-icon.bg-amber   { background: #d97706; }
        .kpi-chip-icon .dashicons { font-size: 22px; width: 22px; height: 22px; }

        .kpi-chip-lbl { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; display: block; margin-bottom: 2px; }
        .kpi-chip-val { font-size: 19px; font-weight: 900; color: #0f172a; display: block; letter-spacing: -0.5px; }
        .kpi-chip-sub { font-size: 11px; color: #64748b; font-weight: 600; margin-top: 2px; display: block; }
        .color-blue    { color: #003376 !important; }
        .color-emerald { color: #059669 !important; }

        /* Master Detailed Table Card */
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
        .table-card-heading .dashicons { color: #003376; font-size: 20px; width: 20px; height: 20px; }
        .table-card-date-badge { font-size: 12px; font-weight: 700; color: #475569; background: #f8fafc; padding: 4px 10px; border-radius: 6px; border: 1px solid #e2e8f0; }

        .ifs-table-responsive { padding: 12px 22px 20px; overflow-x: auto; }
        .ifs-report-datatable { width: 100%; border-collapse: collapse; font-size: 13px; }
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
        .mod-cell-title { display: flex; align-items: center; gap: 8px; color: #0f172a; }
        .mod-cell-title .dashicons { font-size: 17px; width: 17px; height: 17px; }
        .vol-badge { background: #f1f5f9; color: #334155; font-weight: 700; font-size: 11px; padding: 2px 7px; border-radius: 4px; border: 1px solid #e2e8f0; }

        .grand-total-row { background: #f8fafc; border-top: 2px solid #cbd5e1; }
        .grand-total-row td { padding: 14px; font-size: 14px; color: #0f172a; }

        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important; }
        .font-bold { font-weight: 700; }

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
            .ifs-report-table-card { border: 1px solid #000 !important; }
        }
    </style>
    <?php
}