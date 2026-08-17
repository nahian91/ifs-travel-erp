<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Modern Sales & Performance Report Page for IFS Travel ERP
 */
function ifs_terp_report_sales_page() {
    global $wpdb;
    $table_tickets   = $wpdb->prefix . 'iterp_tickets';
    $table_visas     = $wpdb->prefix . 'iterp_visa_applications';
    $table_hajj      = $wpdb->prefix . 'iterp_hajj_bookings';
    $table_hotels    = $wpdb->prefix . 'iterp_hotel_bookings';
    $table_tours     = $wpdb->prefix . 'iterp_tours';
    $table_customers = $wpdb->prefix . 'iterp_customers';

    $start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : date('Y-m-01');
    $end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : date('Y-m-d');
    $module     = isset( $_GET['report_module'] ) ? sanitize_text_field( $_GET['report_module'] ) : 'all';

    // Aggregations with Robust Error-Free Handling
    $ticket_sales = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(id) as total_count, COALESCE(SUM(sell_price), 0) as total_sell, COALESCE(SUM(profit), 0) as total_profit FROM $table_tickets WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Void'", $start_date, $end_date ) );
    
    $visa_sales = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(id) as total_count, COALESCE(SUM(sell_price), 0) as total_sell, COALESCE(SUM(profit), 0) as total_profit FROM $table_visas WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Rejected'", $start_date, $end_date ) );
    
    $hajj_sales = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(id) as total_count, COALESCE(SUM(sell_price), 0) as total_sell, COALESCE(SUM(profit), 0) as total_profit FROM $table_hajj WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Cancelled'", $start_date, $end_date ) );

    $hotel_sales = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(id) as total_count, COALESCE(SUM(sell_price), 0) as total_sell, COALESCE(SUM(profit), 0) as total_profit FROM $table_hotels WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Cancelled'", $start_date, $end_date ) );

    $tour_sales = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(id) as total_count, COALESCE(SUM(sell_price), 0) as total_sell, COALESCE(SUM(profit), 0) as total_profit FROM $table_tours WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Cancelled'", $start_date, $end_date ) );

    $t_sell   = (float) ($ticket_sales->total_sell ?? 0);
    $t_profit = (float) ($ticket_sales->total_profit ?? 0);
    $t_count  = (int) ($ticket_sales->total_count ?? 0);

    $v_sell   = (float) ($visa_sales->total_sell ?? 0);
    $v_profit = (float) ($visa_sales->total_profit ?? 0);
    $v_count  = (int) ($visa_sales->total_count ?? 0);

    $h_sell   = (float) ($hajj_sales->total_sell ?? 0);
    $h_profit = (float) ($hajj_sales->total_profit ?? 0);
    $h_count  = (int) ($hajj_sales->total_count ?? 0);

    $ht_sell  = (float) ($hotel_sales->total_sell ?? 0);
    $ht_profit= (float) ($hotel_sales->total_profit ?? 0);
    $ht_count = (int) ($hotel_sales->total_count ?? 0);

    $tour_sell  = (float) ($tour_sales->total_sell ?? 0);
    $tour_profit= (float) ($tour_sales->total_profit ?? 0);
    $tour_count = (int) ($tour_sales->total_count ?? 0);

    $grand_turnover = $t_sell + $v_sell + $h_sell + $ht_sell + $tour_sell;
    $grand_profit   = $t_profit + $v_profit + $h_profit + $ht_profit + $tour_profit;
    $total_files    = $t_count + $v_count + $h_count + $ht_count + $tour_count;
    ?>

    <div class="wrap ifs-report-wrapper" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
        
        <!-- Header & Page Title -->
        <div class="ifs-report-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
            <div>
                <h2 style="margin: 0; font-size: 22px; font-weight: 800; color: #0f172a;">
                    <span class="dashicons dashicons-chart-bar" style="color: #003376; font-size: 24px; vertical-align: middle;"></span> Comprehensive Sales & Revenue Report
                </h2>
                <p style="margin: 4px 0 0 0; font-size: 13.5px; color: #64748b;">Analyze multi-module enterprise sales performance, gross revenue, and agency commissions.</p>
            </div>
            <div>
                <button onclick="window.print();" class="button button-secondary" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600;">
                    <span class="dashicons dashicons-printer" style="font-size: 16px; margin-top: 3px;"></span> Print Report
                </button>
            </div>
        </div>

        <!-- Filter Form Card -->
        <div style="background: #fff; padding: 22px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
            <form method="get" action="">
                <input type="hidden" name="page" value="ifs_travel_erp">
                <input type="hidden" name="tab" value="reports">
                <input type="hidden" name="sub" value="sales">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; align-items: flex-end;">
                    <div>
                        <label style="display:block; font-weight:700; font-size:11.5px; text-transform:uppercase; color:#475569; margin-bottom:6px;">From Date</label>
                        <input type="date" name="start_date" value="<?php echo esc_attr( $start_date ); ?>" style="width:100%; padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13.5px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:700; font-size:11.5px; text-transform:uppercase; color:#475569; margin-bottom:6px;">To Date</label>
                        <input type="date" name="end_date" value="<?php echo esc_attr( $end_date ); ?>" style="width:100%; padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13.5px;">
                    </div>
                    <div>
                        <button type="submit" style="background: linear-gradient(135deg, #003376 0%, #0284c7 100%); color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 700; font-size: 13.5px; cursor: pointer; width: 100%; box-shadow: 0 4px 12px rgba(0, 51, 118, 0.2);">
                            <span class="dashicons dashicons-filter" style="font-size: 15px; vertical-align: middle; margin-right: 4px;"></span> Filter Sales Data
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Summary KPI Cards Strip -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div style="background: #fff; padding: 22px; border-radius: 12px; border-left: 5px solid #003376; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
                <span style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Total Turnover (Sales)</span>
                <div style="font-size: 24px; font-weight: 900; color: #0f172a; margin-top: 8px; font-family: ui-monospace, monospace;">৳<?php echo number_format( $grand_turnover, 2 ); ?></div>
                <span style="font-size: 11.5px; color: #059669; font-weight: 600; margin-top: 4px; display: block;">Active Period Performance</span>
            </div>

            <div style="background: #fff; padding: 22px; border-radius: 12px; border-left: 5px solid #059669; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
                <span style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Total Gross Profit</span>
                <div style="font-size: 24px; font-weight: 900; color: #059669; margin-top: 8px; font-family: ui-monospace, monospace;">৳<?php echo number_format( $grand_profit, 2 ); ?></div>
                <span style="font-size: 11.5px; color: #64748b; font-weight: 600; margin-top: 4px; display: block;">Combined Net Margin</span>
            </div>

            <div style="background: #fff; padding: 22px; border-radius: 12px; border-left: 5px solid #0284c7; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
                <span style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Air Tickets Issued</span>
                <div style="font-size: 22px; font-weight: 900; color: #0f172a; margin-top: 8px;"><?php echo number_format( $t_count ); ?> <span style="font-size: 13px; color: #64748b; font-weight: 600;">(৳<?php echo number_format($t_sell, 2); ?>)</span></div>
                <span style="font-size: 11.5px; color: #0284c7; font-weight: 600; margin-top: 4px; display: block;">Profit: ৳<?php echo number_format($t_profit, 2); ?></span>
            </div>

            <div style="background: #fff; padding: 22px; border-radius: 12px; border-left: 5px solid #d97706; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
                <span style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Total Operations Files</span>
                <div style="font-size: 22px; font-weight: 900; color: #0f172a; margin-top: 8px;"><?php echo number_format( $total_files ); ?> Files</div>
                <span style="font-size: 11.5px; color: #d97706; font-weight: 600; margin-top: 4px; display: block;">Across All 5 Modules</span>
            </div>
        </div>

        <!-- Detailed Breakdown Table -->
        <div style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); overflow: hidden;">
            <div style="padding: 22px 26px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 17px; font-weight: 800; color: #0f172a;">
                    <span class="dashicons dashicons-analytics" style="color: #003376; vertical-align: middle;"></span> Module-wise Revenue & Profit Breakdown
                </h3>
                <span style="font-size: 12px; font-weight: 700; color: #64748b; background: #f8fafc; padding: 4px 10px; border-radius: 6px; border: 1px solid #e2e8f0;">
                    <?php echo esc_html( $start_date ); ?> to <?php echo esc_html( $end_date ); ?>
                </span>
            </div>

            <div style="padding: 15px 24px 24px 24px; overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13.5px;">
                    <thead>
                        <tr style="background: #f8fafc; text-align: left; border-bottom: 2px solid #e2e8f0;">
                            <th style="padding: 12px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #475569;">Operations Module</th>
                            <th style="padding: 12px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #475569; text-align: center;">Total Volume / Files</th>
                            <th style="padding: 12px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #475569; text-align: right;">Gross Turnover (৳)</th>
                            <th style="padding: 12px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #475569; text-align: right;">Gross Profit (৳)</th>
                            <th style="padding: 12px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #475569; text-align: right;">Margin %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Row 1: Air Ticketing -->
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 14px; font-weight: 700; color: #003376;">
                                <span class="dashicons dashicons-airplane" style="font-size: 16px; vertical-align: middle; margin-right: 6px;"></span> Air Ticketing & PNR Issuance
                            </td>
                            <td style="padding: 14px; text-align: center; font-weight: 600;"><?php echo number_format( $t_count ); ?> Tickets</td>
                            <td style="padding: 14px; text-align: right; font-family: ui-monospace, monospace; font-weight: 700;">৳<?php echo number_format( $t_sell, 2 ); ?></td>
                            <td style="padding: 14px; text-align: right; font-family: ui-monospace, monospace; font-weight: 700; color: #059669;">৳<?php echo number_format( $t_profit, 2 ); ?></td>
                            <td style="padding: 14px; text-align: right; font-family: ui-monospace, monospace; font-weight: 600; color: #64748b;">
                                <?php echo ($t_sell > 0) ? number_format(($t_profit / $t_sell) * 100, 1) . '%' : '0.0%'; ?>
                            </td>
                        </tr>

                        <!-- Row 2: Visa Processing -->
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 14px; font-weight: 700; color: #003376;">
                                <span class="dashicons dashicons-id-alt" style="font-size: 16px; vertical-align: middle; margin-right: 6px;"></span> Visa Processing Applications
                            </td>
                            <td style="padding: 14px; text-align: center; font-weight: 600;"><?php echo number_format( $v_count ); ?> Files</td>
                            <td style="padding: 14px; text-align: right; font-family: ui-monospace, monospace; font-weight: 700;">৳<?php echo number_format( $v_sell, 2 ); ?></td>
                            <td style="padding: 14px; text-align: right; font-family: ui-monospace, monospace; font-weight: 700; color: #059669;">৳<?php echo number_format( $v_profit, 2 ); ?></td>
                            <td style="padding: 14px; text-align: right; font-family: ui-monospace, monospace; font-weight: 600; color: #64748b;">
                                <?php echo ($v_sell > 0) ? number_format(($v_profit / $v_sell) * 100, 1) . '%' : '0.0%'; ?>
                            </td>
                        </tr>

                        <!-- Row 3: Hajj & Umrah -->
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 14px; font-weight: 700; color: #003376;">
                                <span class="dashicons dashicons-awards" style="font-size: 16px; vertical-align: middle; margin-right: 6px;"></span> Hajj & Umrah Pilgrims
                            </td>
                            <td style="padding: 14px; text-align: center; font-weight: 600;"><?php echo number_format( $h_count ); ?> Pilgrims</td>
                            <td style="padding: 14px; text-align: right; font-family: ui-monospace, monospace; font-weight: 700;">৳<?php echo number_format( $h_sell, 2 ); ?></td>
                            <td style="padding: 14px; text-align: right; font-family: ui-monospace, monospace; font-weight: 700; color: #059669;">৳<?php echo number_format( $h_profit, 2 ); ?></td>
                            <td style="padding: 14px; text-align: right; font-family: ui-monospace, monospace; font-weight: 600; color: #64748b;">
                                <?php echo ($h_sell > 0) ? number_format(($h_profit / $h_sell) * 100, 1) . '%' : '0.0%'; ?>
                            </td>
                        </tr>

                        <!-- Row 4: Hotel Bookings -->
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 14px; font-weight: 700; color: #003376;">
                                <span class="dashicons dashicons-building" style="font-size: 16px; vertical-align: middle; margin-right: 6px;"></span> Hotel & Resort Reservations
                            </td>
                            <td style="padding: 14px; text-align: center; font-weight: 600;"><?php echo number_format( $ht_count ); ?> Bookings</td>
                            <td style="padding: 14px; text-align: right; font-family: ui-monospace, monospace; font-weight: 700;">৳<?php echo number_format( $ht_sell, 2 ); ?></td>
                            <td style="padding: 14px; text-align: right; font-family: ui-monospace, monospace; font-weight: 700; color: #059669;">৳<?php echo number_format( $ht_profit, 2 ); ?></td>
                            <td style="padding: 14px; text-align: right; font-family: ui-monospace, monospace; font-weight: 600; color: #64748b;">
                                <?php echo ($ht_sell > 0) ? number_format(($ht_profit / $ht_sell) * 100, 1) . '%' : '0.0%'; ?>
                            </td>
                        </tr>

                        <!-- Row 5: Tour Packages -->
                        <tr style="border-bottom: 2px solid #cbd5e1;">
                            <td style="padding: 14px; font-weight: 700; color: #003376;">
                                <span class="dashicons dashicons-palmtree" style="font-size: 16px; vertical-align: middle; margin-right: 6px;"></span> Holiday & Tour Packages
                            </td>
                            <td style="padding: 14px; text-align: center; font-weight: 600;"><?php echo number_format( $tour_count ); ?> Packages</td>
                            <td style="padding: 14px; text-align: right; font-family: ui-monospace, monospace; font-weight: 700;">৳<?php echo number_format( $tour_sell, 2 ); ?></td>
                            <td style="padding: 14px; text-align: right; font-family: ui-monospace, monospace; font-weight: 700; color: #059669;">৳<?php echo number_format( $tour_profit, 2 ); ?></td>
                            <td style="padding: 14px; text-align: right; font-family: ui-monospace, monospace; font-weight: 600; color: #64748b;">
                                <?php echo ($tour_sell > 0) ? number_format(($tour_profit / $tour_sell) * 100, 1) . '%' : '0.0%'; ?>
                            </td>
                        </tr>

                        <!-- Grand Total Row -->
                        <tr style="background: #f8fafc;">
                            <td style="padding: 16px 14px; font-weight: 900; color: #0f172a; font-size: 14px;">
                                GRAND TOTAL CONSOLIDATED
                            </td>
                            <td style="padding: 16px 14px; text-align: center; font-weight: 900; font-size: 14px; color: #0f172a;"><?php echo number_format( $total_files ); ?> Files</td>
                            <td style="padding: 16px 14px; text-align: right; font-family: ui-monospace, monospace; font-weight: 900; color: #0f172a; font-size: 15px;">৳<?php echo number_format( $grand_turnover, 2 ); ?></td>
                            <td style="padding: 16px 14px; text-align: right; font-family: ui-monospace, monospace; font-weight: 900; color: #059669; font-size: 15px;">৳<?php echo number_format( $grand_profit, 2 ); ?></td>
                            <td style="padding: 16px 14px; text-align: right; font-family: ui-monospace, monospace; font-weight: 900; color: #0f172a; font-size: 14px;">
                                <?php echo ($grand_turnover > 0) ? number_format(($grand_profit / $grand_turnover) * 100, 1) . '%' : '0.0%'; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Print Optimization Styles -->
    <style>
        @media print {
            #adminmenuback, #adminmenuwrap, #wpadminbar, #wpfooter, .ifs-report-header button, .ifs-report-wrapper form {
                display: none !important;
            }
            #wpcontent, #wpbody-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            body.wp-admin {
                background: #ffffff !important;
            }
            .ifs-report-wrapper {
                max-width: 100% !important;
                padding: 0 !important;
            }
        }
    </style>
    <?php
}