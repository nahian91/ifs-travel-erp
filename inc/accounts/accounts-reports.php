<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enterprise Financial Statements & Accounts Reporting Workspace with Advanced Filters
 */
function ifs_terp_accounts_reports_page() {
    global $wpdb;
    $table_ledger = $wpdb->prefix . 'iterp_ledger';

    // Handle Optional Filter Parameters
    $start_date       = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : '';
    $end_date         = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : '';
    $txn_type_filter  = isset( $_GET['txn_type'] ) ? sanitize_text_field( $_GET['txn_type'] ) : '';

    $where_clauses = array();
    $query_args    = array();

    // Date Range Condition
    if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
        $where_clauses[] = "transaction_date BETWEEN %s AND %s";
        $query_args[]    = $start_date . ' 00:00:00';
        $query_args[]    = $end_date . ' 23:59:59';
    }

    // Transaction Type Condition (Income / Expense)
    if ( ! empty( $txn_type_filter ) && in_array( $txn_type_filter, array( 'Income', 'Expense' ), true ) ) {
        $where_clauses[] = "transaction_type = %s";
        $query_args[]    = $txn_type_filter;
    }

    $sql_where = '';
    if ( ! empty( $where_clauses ) ) {
        $sql_where = " WHERE " . implode( " AND ", $where_clauses );
    }

    // Fetch Summary Metrics (Base totals for cards remain consistent or follow filter)
    if ( ! empty( $query_args ) ) {
        $total_income  = (float) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(amount) FROM {$table_ledger} {$sql_where} " . (empty($txn_type_filter) ? " AND transaction_type = 'Income'" : ""), $query_args ) );
        $total_expense = (float) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(amount) FROM {$table_ledger} {$sql_where} " . (empty($txn_type_filter) ? " AND transaction_type = 'Expense'" : ""), $query_args ) );
    } else {
        $total_income  = (float) $wpdb->get_var( "SELECT SUM(amount) FROM {$table_ledger} WHERE transaction_type = 'Income'" );
        $total_expense = (float) $wpdb->get_var( "SELECT SUM(amount) FROM {$table_ledger} WHERE transaction_type = 'Expense'" );
    }

    // Adjust summary calculation if a specific type filter is active
    if ( $txn_type_filter === 'Income' ) {
        $total_expense = 0.00;
    } elseif ( $txn_type_filter === 'Expense' ) {
        $total_income = 0.00;
    }

    $net_profit = $total_income - $total_expense;
    $base_url   = admin_url( 'admin.php?page=ifs_travel_erp&tab=accounts&sub=reports' );
    ?>
    <div class="wrap ifs-invoice-list-workspace">
        
        <!-- Report Controls & Print Bar -->
        <div class="ifs-table-card" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 22px 28px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h3 style="margin: 0 0 4px 0; font-size: 18px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-media-spreadsheet" style="color: #003376;"></span> <?php esc_html_e( 'Executive Financial Statement & Cash Flow', 'ifs-travel-erp' ); ?>
                </h3>
                <p style="margin: 0; font-size: 13px; color: #64748b;"><?php esc_html_e( 'Analyze operational earnings, expenditures, and net liquidity performance.', 'ifs-travel-erp' ); ?></p>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <button type="button" onclick="window.print();" class="button button-primary" style="background: #003376; border-color: #003376; height: 38px; padding: 0 16px; border-radius: 6px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;">
                    <span class="dashicons dashicons-printer" style="font-size: 15px; width: 15px; height: 15px; margin-top: 2px;"></span> <?php esc_html_e( 'Print Statement', 'ifs-travel-erp' ); ?>
                </button>
            </div>
        </div>

        <!-- Advanced Filter Toolbar (Type & Date Range) -->
        <div class="ifs-table-card" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px 24px; margin-bottom: 24px;">
            <form method="get" action="" style="display: flex; gap: 14px; align-items: flex-end; flex-wrap: wrap;">
                <input type="hidden" name="page" value="ifs_travel_erp">
                <input type="hidden" name="tab" value="accounts">
                <input type="hidden" name="sub" value="reports">

                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;"><?php esc_html_e( 'Transaction Type', 'ifs-travel-erp' ); ?></label>
                    <select name="txn_type" class="ifs-select-control" style="height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; background: #fff; font-size: 13px;">
                        <option value=""><?php esc_html_e( 'All (Income & Expense)', 'ifs-travel-erp' ); ?></option>
                        <option value="Income" <?php selected( $txn_type_filter, 'Income' ); ?>><?php esc_html_e( 'Income Only', 'ifs-travel-erp' ); ?></option>
                        <option value="Expense" <?php selected( $txn_type_filter, 'Expense' ); ?>><?php esc_html_e( 'Expense Only', 'ifs-travel-erp' ); ?></option>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;"><?php esc_html_e( 'From Date', 'ifs-travel-erp' ); ?></label>
                    <input type="date" name="start_date" value="<?php echo esc_attr( $start_date ); ?>" class="font-mono" style="height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 10px; background: #fff;">
                </div>

                <div>
                    <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;"><?php esc_html_e( 'To Date', 'ifs-travel-erp' ); ?></label>
                    <input type="date" name="end_date" value="<?php echo esc_attr( $end_date ); ?>" class="font-mono" style="height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 10px; background: #fff;">
                </div>

                <div style="display: flex; gap: 6px;">
                    <button type="submit" class="button button-secondary" style="height: 38px; padding: 0 16px; border-radius: 6px; font-weight: 600;"><?php esc_html_e( 'Apply Filters', 'ifs-travel-erp' ); ?></button>
                    <?php if ( ! empty( $start_date ) || ! empty( $txn_type_filter ) ) : ?>
                        <a href="<?php echo esc_url( $base_url ); ?>" class="button" style="height: 38px; line-height: 36px; padding: 0 12px; border-radius: 6px;"><?php esc_html_e( 'Reset', 'ifs-travel-erp' ); ?></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- High-Level Metric Summary Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 28px;">
            <div class="ifs-table-card" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 22px; display: flex; align-items: center; gap: 18px;">
                <div style="width: 52px; height: 52px; border-radius: 12px; background: #dcfce7; color: #15803d; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0;">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
                <div>
                    <span style="font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e( 'Realized Revenue', 'ifs-travel-erp' ); ?></span>
                    <h3 style="margin: 4px 0 0 0; font-size: 22px; font-weight: 800; color: #15803d; font-family: monospace;">৳<?php echo number_format( $total_income, 2 ); ?></h3>
                </div>
            </div>

            <div class="ifs-table-card" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 22px; display: flex; align-items: center; gap: 18px;">
                <div style="width: 52px; height: 52px; border-radius: 12px; background: #fee2e2; color: #b91c1c; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0;">
                    <span class="dashicons dashicons-cart"></span>
                </div>
                <div>
                    <span style="font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e( 'Operating Expense', 'ifs-travel-erp' ); ?></span>
                    <h3 style="margin: 4px 0 0 0; font-size: 22px; font-weight: 800; color: #b91c1c; font-family: monospace;">৳<?php echo number_format( $total_expense, 2 ); ?></h3>
                </div>
            </div>

            <div class="ifs-table-card" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 22px; display: flex; align-items: center; gap: 18px;">
                <div style="width: 52px; height: 52px; border-radius: 12px; background: #e0f2fe; color: #0369a1; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0;">
                    <span class="dashicons dashicons-vault"></span>
                </div>
                <div>
                    <span style="font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e( 'Net Balance Position', 'ifs-travel-erp' ); ?></span>
                    <h3 style="margin: 4px 0 0 0; font-size: 22px; font-weight: 800; color: <?php echo ($net_profit >= 0) ? '#0369a1' : '#b91c1c'; ?>; font-family: monospace;">৳<?php echo number_format( $net_profit, 2 ); ?></h3>
                </div>
            </div>
        </div>

        <!-- Detailed Category Breakdown Section -->
        <div class="ifs-table-card" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);">
            <div style="padding: 18px 26px; background: #f8fafc; border-bottom: 1px solid #f1f5f9;">
                <h3 style="margin: 0; font-size: 15px; font-weight: 800; color: #0f172a;"><?php esc_html_e( 'Ledger Category Breakdown Analysis', 'ifs-travel-erp' ); ?></h3>
            </div>

            <div style="padding: 15px 24px 24px 24px; overflow-x: auto;">
                <table class="ifs-pro-datatable" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="background: #f8fafc; text-align: left;">
                            <th style="padding: 12px 16px; border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 11px; text-transform: uppercase;"><?php esc_html_e( 'Classification Type', 'ifs-travel-erp' ); ?></th>
                            <th style="padding: 12px 16px; border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 11px; text-transform: uppercase;"><?php esc_html_e( 'Particular Category', 'ifs-travel-erp' ); ?></th>
                            <th style="padding: 12px 16px; border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 11px; text-transform: uppercase; text-align: right;"><?php esc_html_e( 'Aggregated Total (৳)', 'ifs-travel-erp' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ( ! empty( $sql_where ) ) {
                            $breakdowns = $wpdb->get_results( $wpdb->prepare( "SELECT transaction_type, category, SUM(amount) as total FROM {$table_ledger} {$sql_where} GROUP BY transaction_type, category ORDER BY transaction_type DESC, total DESC", $query_args ) );
                        } else {
                            $breakdowns = $wpdb->get_results( "SELECT transaction_type, category, SUM(amount) as total FROM {$table_ledger} GROUP BY transaction_type, category ORDER BY transaction_type DESC, total DESC" );
                        }

                        if ( $breakdowns ) : foreach ( $breakdowns as $b ) : 
                            $is_inc = ( $b->transaction_type === 'Income' );
                        ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 14px 16px;">
                                    <span style="background: <?php echo $is_inc ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo $is_inc ? '#15803d' : '#b91c1c'; ?>; padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 10.5px; text-transform: uppercase;">
                                        <?php echo esc_html( $b->transaction_type ); ?>
                                    </span>
                                </td>
                                <td style="padding: 14px 16px; font-weight: 700; color: #0f172a;"><?php echo esc_html( $b->category ); ?></td>
                                <td style="padding: 14px 16px; text-align: right; font-weight: 700; font-family: monospace; color: <?php echo $is_inc ? '#15803d' : '#b91c1c'; ?>; font-size: 13.5px;">
                                    <?php echo $is_inc ? '+' : '-'; ?>৳<?php echo number_format( (float) $b->total, 2 ); ?>
                                </td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr><td colspan="3" style="text-align: center; padding: 40px; color: #94a3b8; font-style: italic;"><?php esc_html_e( 'No transaction statistics found for the selected filter parameters.', 'ifs-travel-erp' ); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Print Media Styling Rules -->
    <style>
    @media print {
        body * { visibility: hidden; }
        .ifs-invoice-list-workspace, .ifs-invoice-list-workspace * { visibility: visible; }
        .ifs-invoice-list-workspace { position: absolute; left: 0; top: 0; width: 100%; margin: 0; padding: 10px; }
        form, .button { display: none !important; }
    }
    </style>
    <?php
}