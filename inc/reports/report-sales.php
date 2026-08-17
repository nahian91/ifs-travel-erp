<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_report_sales_page() {
    global $wpdb;
    $table_tickets   = $wpdb->prefix . 'iterp_tickets';
    $table_visas     = $wpdb->prefix . 'iterp_visas';
    $table_hajj      = $wpdb->prefix . 'iterp_hajj_bookings';
    $table_customers = $wpdb->prefix . 'iterp_customers';

    $start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : date('Y-m-01');
    $end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : date('Y-m-d');
    $module     = isset( $_GET['report_module'] ) ? sanitize_text_field( $_GET['report_module'] ) : 'all';

    // Aggregations
    $ticket_sales = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(id) as total_count, COALESCE(SUM(sell_price), 0) as total_sell, COALESCE(SUM(profit), 0) as total_profit FROM $table_tickets WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Void'", $start_date, $end_date ) );
    $visa_sales   = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(id) as total_count, COALESCE(SUM(sell_price), 0) as total_sell, COALESCE(SUM(profit), 0) as total_profit FROM $table_visas WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Rejected'", $start_date, $end_date ) );
    $hajj_sales   = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(id) as total_count, COALESCE(SUM(sell_price), 0) as total_sell, COALESCE(SUM(profit), 0) as total_profit FROM $table_hajj WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Cancelled'", $start_date, $end_date ) );

    $grand_turnover = $ticket_sales->total_sell + $visa_sales->total_sell + $hajj_sales->total_sell;
    $grand_profit   = $ticket_sales->total_profit + $visa_sales->total_profit + $hajj_sales->total_profit;
    ?>

    <!-- Filter Form -->
    <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; margin-top: 20px;">
        <form method="get" action="">
            <input type="hidden" name="page" value="ifs_travel_erp">
            <input type="hidden" name="tab" value="reports">
            <input type="hidden" name="sub" value="sales">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: flex-end;">
                <div>
                    <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">From Date</label>
                    <input type="date" name="start_date" value="<?php echo esc_attr( $start_date ); ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>
                <div>
                    <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">To Date</label>
                    <input type="date" name="end_date" value="<?php echo esc_attr( $end_date ); ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>
                <div>
                    <button type="submit" style="background: #003376; color: #fff; border: none; padding: 9px 20px; border-radius: 4px; font-weight: 600; cursor: pointer; width: 100%;">
                        Filter Sales Data
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Summary KPI Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-top: 25px;">
        <div style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #003376; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
            <span style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Total Turnover (Sales)</span>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a; margin-top: 5px;">৳<?php echo number_format( $grand_turnover, 2 ); ?></div>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #16a34a; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
            <span style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Total Gross Profit</span>
            <div style="font-size: 22px; font-weight: 800; color: #16a34a; margin-top: 5px;">৳<?php echo number_format( $grand_profit, 2 ); ?></div>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #3b82f6; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
            <span style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Air Tickets Issued</span>
            <div style="font-size: 20px; font-weight: 700; color: #0f172a; margin-top: 5px;"><?php echo esc_html( $ticket_sales->total_count ); ?> <span style="font-size: 13px; color: #64748b;">(৳<?php echo number_format($ticket_sales->total_sell, 2); ?>)</span></div>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #f59e0b; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
            <span style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Visa & Pilgrims</span>
            <div style="font-size: 20px; font-weight: 700; color: #0f172a; margin-top: 5px;"><?php echo ($visa_sales->total_count + $hajj_sales->total_count); ?> Files</div>
        </div>
    </div>
    <?php
}