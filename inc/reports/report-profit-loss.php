<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Modern Profit & Loss Statement Page for IFS Travel ERP
 */
function ifs_terp_report_profit_loss_page() {
    global $wpdb;
    $table_tickets = $wpdb->prefix . 'iterp_tickets';
    $table_visas     = $wpdb->prefix . 'iterp_visa_applications';
    $table_hajj      = $wpdb->prefix . 'iterp_hajj_bookings';
    $table_hotels    = $wpdb->prefix . 'iterp_hotel_bookings';
    $table_tours     = $wpdb->prefix . 'iterp_tours';
    $table_ledger    = $wpdb->prefix . 'iterp_ledger';

    $start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : date('Y-m-01');
    $end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : date('Y-m-d');

    // Revenue / Gross Margins across all 5 operational modules
    $ticket_profit = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(profit), 0) FROM $table_tickets WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Void'", $start_date, $end_date ) );
    $visa_profit   = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(profit), 0) FROM $table_visas WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Rejected'", $start_date, $end_date ) );
    $hajj_profit   = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(profit), 0) FROM $table_hajj WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Cancelled'", $start_date, $end_date ) );
    $hotel_profit  = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(profit), 0) FROM $table_hotels WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Cancelled'", $start_date, $end_date ) );
    $tour_profit   = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(profit), 0) FROM $table_tours WHERE DATE(created_at) BETWEEN %s AND %s AND status != 'Cancelled'", $start_date, $end_date ) );
    
    $total_gross_income = $ticket_profit + $visa_profit + $hajj_profit + $hotel_profit + $tour_profit;

    // Office & Operational Expenses from General Ledger
    $expenses = $wpdb->get_results( $wpdb->prepare( "SELECT category, SUM(amount) as total_amount FROM $table_ledger WHERE transaction_type = 'Expense' AND DATE(transaction_date) BETWEEN %s AND %s GROUP BY category", $start_date, $end_date ) );
    
    $total_expenses = 0;
    if ( $expenses ) {
        foreach ( $expenses as $exp ) {
            $total_expenses += (float) $exp->total_amount;
        }
    }

    $net_profit = $total_gross_income - $total_expenses;
    $margin_pct = ($total_gross_income > 0) ? ($net_profit / $total_gross_income) * 100 : 0;
    ?>

    <div class="wrap ifs-report-wrapper" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
        
        <!-- Header & Page Title -->
        <div class="ifs-report-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
            <div>
                <h2 style="margin: 0; font-size: 22px; font-weight: 800; color: #0f172a;">
                    <span class="dashicons dashicons-media-spreadsheet" style="color: #003376; font-size: 24px; vertical-align: middle;"></span> Comprehensive Profit & Loss Statement
                </h2>
                <p style="margin: 4px 0 0 0; font-size: 13.5px; color: #64748b;">Consolidated financial performance report combining gross operating margins and office overhead expenses.</p>
            </div>
            <div>
                <button onclick="window.print();" class="button button-secondary" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600;">
                    <span class="dashicons dashicons-printer" style="font-size: 16px; margin-top: 3px;"></span> Print P&L Statement
                </button>
            </div>
        </div>

        <!-- Filter Form Card -->
        <div style="background: #fff; padding: 22px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
            <form method="get" action="">
                <input type="hidden" name="page" value="ifs_travel_erp">
                <input type="hidden" name="tab" value="reports">
                <input type="hidden" name="sub" value="profit_loss">

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
                            <span class="dashicons dashicons-calculator" style="font-size: 15px; vertical-align: middle; margin-right: 4px;"></span> Calculate Statement
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- 2-Column Statement Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 25px; margin-bottom: 25px;">
            
            <!-- Income / Revenue Column -->
            <div style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); overflow: hidden;">
                <div style="padding: 20px 24px; background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                    <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #166534; display: flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-arrow-up-alt" style="font-size: 18px; width: 18px; height: 18px;"></span> Operating Income (Gross Margins)
                    </h3>
                </div>
                <div style="padding: 20px 24px;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13.5px;">
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 11px 0; color: #475569; font-weight: 600;">Air Ticketing Profit:</td>
                            <td style="padding: 11px 0; text-align: right; font-family: ui-monospace, monospace; font-weight: 700; color: #0f172a;">৳<?php echo number_format( $ticket_profit, 2 ); ?></td>
                        </tr>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 11px 0; color: #475569; font-weight: 600;">Visa Processing Profit:</td>
                            <td style="padding: 11px 0; text-align: right; font-family: ui-monospace, monospace; font-weight: 700; color: #0f172a;">৳<?php echo number_format( $visa_profit, 2 ); ?></td>
                        </tr>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 11px 0; color: #475569; font-weight: 600;">Hajj & Umrah Profit:</td>
                            <td style="padding: 11px 0; text-align: right; font-family: ui-monospace, monospace; font-weight: 700; color: #0f172a;">৳<?php echo number_format( $hajj_profit, 2 ); ?></td>
                        </tr>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 11px 0; color: #475569; font-weight: 600;">Hotel Bookings Profit:</td>
                            <td style="padding: 11px 0; text-align: right; font-family: ui-monospace, monospace; font-weight: 700; color: #0f172a;">৳<?php echo number_format( $hotel_profit, 2 ); ?></td>
                        </tr>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 11px 0; color: #475569; font-weight: 600;">Holiday & Tour Packages Profit:</td>
                            <td style="padding: 11px 0; text-align: right; font-family: ui-monospace, monospace; font-weight: 700; color: #0f172a;">৳<?php echo number_format( $tour_profit, 2 ); ?></td>
                        </tr>
                        <tr style="border-top: 2px solid #cbd5e1; background: #f8fafc;">
                            <td style="padding: 14px 10px; font-weight: 800; color: #0f172a; font-size: 14px;">Total Gross Income:</td>
                            <td style="padding: 14px 10px; text-align: right; font-family: ui-monospace, monospace; font-weight: 900; color: #16a34a; font-size: 16px;">৳<?php echo number_format( $total_gross_income, 2 ); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Expenses Column -->
            <div style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); overflow: hidden;">
                <div style="padding: 20px 24px; background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                    <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #dc2626; display: flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-arrow-down-alt" style="font-size: 18px; width: 18px; height: 18px;"></span> Operating Expenses (Office & Bills)
                    </h3>
                </div>
                <div style="padding: 20px 24px;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13.5px;">
                        <?php if ( $expenses ) : foreach ( $expenses as $exp ) : ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 11px 0; color: #475569; font-weight: 600;"><?php echo esc_html( $exp->category ); ?>:</td>
                                <td style="padding: 11px 0; text-align: right; font-family: ui-monospace, monospace; font-weight: 700; color: #0f172a;">৳<?php echo number_format( (float) $exp->total_amount, 2 ); ?></td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr><td colspan="2" style="padding: 30px 0; text-align: center; color: #94a3b8; font-style: italic;">No office overhead expenses recorded in this period.</td></tr>
                        <?php endif; ?>
                        <tr style="border-top: 2px solid #cbd5e1; background: #f8fafc;">
                            <td style="padding: 14px 10px; font-weight: 800; color: #0f172a; font-size: 14px;">Total Overhead Expenses:</td>
                            <td style="padding: 14px 10px; text-align: right; font-family: ui-monospace, monospace; font-weight: 900; color: #dc2626; font-size: 16px;">৳<?php echo number_format( $total_expenses, 2 ); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

        </div>

        <!-- Final Net Profit / Loss Highlight Card -->
        <div style="background: #fff; padding: 30px; border-radius: 12px; border: 2px solid <?php echo ($net_profit >= 0) ? '#16a34a' : '#dc2626'; ?>; box-shadow: 0 4px 16px rgba(0,0,0,0.03); text-align: center;">
            <span style="font-size: 11.5px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px;">Net Operating Bottom-Line (Net Profit / Loss)</span>
            <div style="font-size: 32px; font-weight: 900; color: <?php echo ($net_profit >= 0) ? '#16a34a' : '#dc2626'; ?>; margin-top: 8px; font-family: ui-monospace, monospace;">
                <?php echo ($net_profit >= 0) ? '+৳' : '-৳'; ?><?php echo number_format( abs( $net_profit ), 2 ); ?>
            </div>
            <div style="margin-top: 6px; font-size: 13.5px; font-weight: 600; color: #475569;">
                Operating Margin: <span style="font-family: ui-monospace, monospace; color: <?php echo ($net_profit >= 0) ? '#16a34a' : '#dc2626'; ?>;"><?php echo number_format( $margin_pct, 1 ); ?>%</span>
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