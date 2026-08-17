<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_report_profit_loss_page() {
    global $wpdb;
    $table_tickets = $wpdb->prefix . 'iterp_tickets';
    $table_visas   = $wpdb->prefix . 'iterp_visas';
    $table_hajj    = $wpdb->prefix . 'iterp_hajj_bookings';
    $table_ledger  = $wpdb->prefix . 'iterp_ledger';

    $start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : date('Y-m-01');
    $end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : date('Y-m-d');

    // Revenue / Gross Margins
    $ticket_profit = $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(profit), 0) FROM $table_tickets WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Void'", $start_date, $end_date ) );
    $visa_profit   = $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(profit), 0) FROM $table_visas WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Rejected'", $start_date, $end_date ) );
    $hajj_profit   = $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(profit), 0) FROM $table_hajj WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Cancelled'", $start_date, $end_date ) );
    
    $total_gross_income = floatval($ticket_profit) + floatval($visa_profit) + floatval($hajj_profit);

    // Office & Operational Expenses from General Ledger
    $expenses = $wpdb->get_results( $wpdb->prepare( "SELECT category, SUM(amount) as total_amount FROM $table_ledger WHERE transaction_type = 'Expense' AND DATE(transaction_date) BETWEEN %s AND %s GROUP BY category", $start_date, $end_date ) );
    
    $total_expenses = 0;
    if ( $expenses ) {
        foreach ( $expenses as $exp ) {
            $total_expenses += $exp->total_amount;
        }
    }

    $net_profit = $total_gross_income - $total_expenses;
    ?>

    <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; margin-top: 20px;">
        <form method="get" action="">
            <input type="hidden" name="page" value="ifs_travel_erp">
            <input type="hidden" name="tab" value="reports">
            <input type="hidden" name="sub" value="profit_loss">

            <div style="display: flex; gap: 15px; align-items: flex-end;">
                <div>
                    <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">From Date</label>
                    <input type="date" name="start_date" value="<?php echo esc_attr( $start_date ); ?>" style="padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>
                <div>
                    <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">To Date</label>
                    <input type="date" name="end_date" value="<?php echo esc_attr( $end_date ); ?>" style="padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>
                <div>
                    <button type="submit" style="background: #003376; color: #fff; border: none; padding: 9px 20px; border-radius: 4px; font-weight: 600; cursor: pointer;">
                        Calculate Statement
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 25px; margin-top: 25px;">
        <!-- Income / Revenue Column -->
        <div style="background: #fff; padding: 25px; border-radius: 8px; border: 1px solid #e2e8f0;">
            <h3 style="margin-top: 0; color: #166534; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                <span class="dashicons dashicons-arrow-up-alt"></span> Operating Income (Gross Margins)
            </h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 10px 0; color: #64748b;">Air Ticketing Profit:</td>
                    <td style="padding: 10px 0; text-align: right; font-weight: 600;">৳<?php echo number_format( $ticket_profit, 2 ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; color: #64748b;">Visa Processing Profit:</td>
                    <td style="padding: 10px 0; text-align: right; font-weight: 600;">৳<?php echo number_format( $visa_profit, 2 ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; color: #64748b;">Hajj & Umrah Profit:</td>
                    <td style="padding: 10px 0; text-align: right; font-weight: 600;">৳<?php echo number_format( $hajj_profit, 2 ); ?></td>
                </tr>
                <tr style="border-top: 2px solid #e2e8f0;">
                    <td style="padding: 12px 0; font-weight: 700; color: #0f172a;">Total Gross Income:</td>
                    <td style="padding: 12px 0; text-align: right; font-weight: 800; color: #16a34a; font-size: 16px;">৳<?php echo number_format( $total_gross_income, 2 ); ?></td>
                </tr>
            </table>
        </div>

        <!-- Expenses Column -->
        <div style="background: #fff; padding: 25px; border-radius: 8px; border: 1px solid #e2e8f0;">
            <h3 style="margin-top: 0; color: #dc2626; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                <span class="dashicons dashicons-arrow-down-alt"></span> Operating Expenses (Office & Bills)
            </h3>
            <table style="width: 100%; border-collapse: collapse;">
                <?php if ( $expenses ) : foreach ( $expenses as $exp ) : ?>
                    <tr>
                        <td style="padding: 8px 0; color: #64748b;"><?php echo esc_html( $exp->category ); ?>:</td>
                        <td style="padding: 8px 0; text-align: right; font-weight: 600;">৳<?php echo number_format( $exp->total_amount, 2 ); ?></td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="2" style="padding: 10px 0; color: #94a3b8;">No office expenses recorded in this period.</td></tr>
                <?php endif; ?>
                <tr style="border-top: 2px solid #e2e8f0;">
                    <td style="padding: 12px 0; font-weight: 700; color: #0f172a;">Total Overhead Expenses:</td>
                    <td style="padding: 12px 0; text-align: right; font-weight: 800; color: #dc2626; font-size: 16px;">৳<?php echo number_format( $total_expenses, 2 ); ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Final Net Profit / Loss Highlight Card -->
    <div style="background: #fff; padding: 25px; border-radius: 8px; border: 2px solid <?php echo ($net_profit >= 0) ? '#16a34a' : '#dc2626'; ?>; margin-top: 25px; text-align: center;">
        <span style="font-size: 13px; color: #64748b; font-weight: 700; text-transform: uppercase;">Net Operating Bottom-line</span>
        <div style="font-size: 32px; font-weight: 900; color: <?php echo ($net_profit >= 0) ? '#16a34a' : '#dc2626'; ?>; margin-top: 5px;">
            <?php echo ($net_profit >= 0) ? 'NET PROFIT: +৳' : 'NET LOSS: -৳'; ?><?php echo number_format( abs($net_profit), 2 ); ?>
        </div>
    </div>
    <?php
}