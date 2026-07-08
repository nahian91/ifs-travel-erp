<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Dashboard Tab - IFS Travel ERP Operations & Financial Control Panel
 * Database Mapping: iterp_tickets, iterp_visas, iterp_invoices, iterp_ledger, iterp_agents
 */
function ifs_terp_dashboard_tab() {
    global $wpdb;

    // Core Database Table Registries for Travel ERP
    $table_tickets  = $wpdb->prefix . 'iterp_tickets';
    $table_visas    = $wpdb->prefix . 'iterp_visas';
    $table_invoices = $wpdb->prefix . 'iterp_invoices';
    $table_ledger   = $wpdb->prefix . 'iterp_ledger';
    $table_agents   = $wpdb->prefix . 'iterp_agents';

    // Time Frames & Ranges
    $today_start = current_time( 'Y-m-d 00:00:00' );
    $today_end   = current_time( 'Y-m-d 23:59:59' );
    $month_start = current_time( 'Y-m-01 00:00:00' );
    $month_end   = current_time( 'Y-m-t 23:59:59' );

    /* =========================================================================
       1. TICKETING ENGINE (Today's Ticket Issue Count)
       ========================================================================= */
    $total_tickets_today = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(id) FROM $table_tickets WHERE created_at BETWEEN %s AND %s",
        $today_start,
        $today_end
    ) );

    /* =========================================================================
       2. VISA PROCESSING TRACKER
       ========================================================================= */
    $processing_visas_count = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(id) FROM $table_visas WHERE status = %s",
        'Processing'
    ) );

    /* =========================================================================
       3. INVOICES / SALES TODAY
       ========================================================================= */
    $today_invoices_count = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(id) FROM $table_invoices WHERE created_at BETWEEN %s AND %s",
        $today_start,
        $today_end
    ) );

    /* =========================================================================
       4. FINANCIAL METRICS (INCOME VS EXPENSE, PROFIT/LOSS, RECEIVABLES)
       ========================================================================= */
    // Today's Ledger Records
    $today_income = (float) $wpdb->get_var( $wpdb->prepare(
        "SELECT SUM(amount) FROM $table_ledger WHERE transaction_type = 'Income' AND transaction_date BETWEEN %s AND %s",
        $today_start,
        $today_end
    ) );
    $today_expense = (float) $wpdb->get_var( $wpdb->prepare(
        "SELECT SUM(amount) FROM $table_ledger WHERE transaction_type = 'Expense' AND transaction_date BETWEEN %s AND %s",
        $today_start,
        $today_end
    ) );

    // Month's Ledger Records
    $month_income = (float) $wpdb->get_var( $wpdb->prepare(
        "SELECT SUM(amount) FROM $table_ledger WHERE transaction_type = 'Income' AND transaction_date BETWEEN %s AND %s",
        $month_start,
        $month_end
    ) );
    $month_expense = (float) $wpdb->get_var( $wpdb->prepare(
        "SELECT SUM(amount) FROM $table_ledger WHERE transaction_type = 'Expense' AND transaction_date BETWEEN %s AND %s",
        $month_start,
        $month_end
    ) );

    // Net Profit/Loss calculations
    $net_profit_loss = $month_income - $month_expense;

    // Unpaid/Pending client accounts receivables (Total Due)
    $pending_dues_amount = (float) $wpdb->get_var(
        "SELECT SUM(due_amount) FROM $table_invoices WHERE due_amount > 0"
    );

    /* =========================================================================
       5. B2B AGENTS MONITORING
       ========================================================================= */
    $active_agents_count = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(id) FROM $table_agents WHERE status = %s",
        'Active'
    ) );

    // Admin redirection routing anchors
    $ticketing_tab_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=ticketing' );
    $accounts_tab_url  = admin_url( 'admin.php?page=ifs_travel_erp&tab=accounts' );
    $visa_tab_url      = admin_url( 'admin.php?page=ifs_travel_erp&tab=visa' );
    $agents_tab_url    = admin_url( 'admin.php?page=ifs_travel_erp&tab=b2b_agents' );

    /* =========================================================================
       CUSTOM TIMELINE GREETING CALCULATOR ENGINE
       ========================================================================= */
    $current_hour = (int) current_time( 'H' ); // 24-hour scale format (00 - 23)
    
    if ( $current_hour >= 6 && $current_hour < 12 ) {
        $greeting_prefix = 'Good Morning';
    } elseif ( $current_hour >= 12 && $current_hour < 18 ) {
        $greeting_prefix = 'Good Evening';
    } else {
        $greeting_prefix = 'Good Night';
    }

    $current_wp_user = wp_get_current_user();
    $user_display_name = ! empty( $current_wp_user->display_name ) ? $current_wp_user->display_name : 'Agent';
    $rendered_greeting = $greeting_prefix . ', ' . $user_display_name;
    ?>
    <div class="ifs-terp-dashboard-wrapper">
        
        <div class="ifs-terp-header-block" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="display: flex; align-items: center; gap: 18px;">
                <div class="ifs-terp-header-logo">
                    <img src="<?php echo esc_url( ITERP_URL . 'assets/img/logo.png' ); ?>" 
                         alt="System Logo" style="height: 64px; width: auto; display: block; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.05));">
                </div>
                <div>
                    <h1 style="margin: 0 0 5px 0; font-size: 22px; color: #1e293b;"><?php echo esc_html( $rendered_greeting ); ?></h1>
                    <p style="margin: 0; color: #64748b; font-size: 14px;">Travel ERP Intelligence & Integrated Financial Control Panel</p>
                </div>
            </div>

            <div class="ifs-terp-live-timer-container" style="text-align: right;">
                <div style="color: #64748b; font-size: 14px; margin-bottom: 5px;">
                    <span class="dashicons dashicons-calendar-alt" style="font-size:16px; vertical-align:middle; margin-right:4px;"></span> 
                    <?php echo date( 'l, jS F Y' ); ?>
                </div>
                <div class="ifs-terp-ticker-digits" style="font-size: 18px; font-weight: 700; color: #003376;">
                    <span class="dashicons dashicons-clock" style="font-size:18px; vertical-align:middle; margin-right:4px;"></span>
                    <span id="ifsTerpLiveTickerClock">00:00:00</span>
                </div>
            </div>
        </div>

        <div class="ifs-terp-summary-grid-matrix" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">
            
            <div class="ifs-terp-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #3b82f6; box-shadow: 0 1px 3px rgba(0,0,0,0.05); position: relative;">
                <div>
                    <div style="color: #64748b; font-size: 13px; font-weight: 600; text-transform: uppercase;">Tickets Issued (Today)</div>
                    <div style="font-size: 28px; font-weight: 700; color: #1e293b; margin: 10px 0;"><?php echo $total_tickets_today; ?></div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px; margin-top: 15px; padding-top: 15px; border-top: 1px solid #f1f5f9;">
                    <span style="background: #eff6ff; color: #2563eb; padding: 4px 8px; border-radius: 4px; font-weight: 500;">Air Ticketing</span>
                    <a href="<?php echo esc_url( $ticketing_tab_url ); ?>" style="text-decoration: none; color: #2563eb; font-weight: 500;">Issue New &rarr;</a>
                </div>
            </div>

            <div class="ifs-terp-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #f59e0b; box-shadow: 0 1px 3px rgba(0,0,0,0.05); position: relative;">
                <div>
                    <div style="color: #64748b; font-size: 13px; font-weight: 600; text-transform: uppercase;">Visas in Processing</div>
                    <div style="font-size: 28px; font-weight: 700; color: #1e293b; margin: 10px 0;"><?php echo $processing_visas_count; ?></div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px; margin-top: 15px; padding-top: 15px; border-top: 1px solid #f1f5f9;">
                    <span style="background: #fffbeb; color: #d97706; padding: 4px 8px; border-radius: 4px; font-weight: 500;">Embassy / VFS</span>
                    <a href="<?php echo esc_url( $visa_tab_url ); ?>" style="text-decoration: none; color: #d97706; font-weight: 500;">Track Status &rarr;</a>
                </div>
            </div>

            <div class="ifs-terp-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #8b5cf6; box-shadow: 0 1px 3px rgba(0,0,0,0.05); position: relative;">
                <div>
                    <div style="color: #64748b; font-size: 13px; font-weight: 600; text-transform: uppercase;">Sales Invoices (Today)</div>
                    <div style="font-size: 28px; font-weight: 700; color: #1e293b; margin: 10px 0;"><?php echo $today_invoices_count; ?></div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px; margin-top: 15px; padding-top: 15px; border-top: 1px solid #f1f5f9;">
                    <span style="background: #f5f3ff; color: #7c3aed; padding: 4px 8px; border-radius: 4px; font-weight: 500;">Billed Items</span>
                    <a href="<?php echo esc_url( $accounts_tab_url ); ?>" style="text-decoration: none; color: #7c3aed; font-weight: 500;">View Bills &rarr;</a>
                </div>
            </div>

            <div class="ifs-terp-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #ef4444; box-shadow: 0 1px 3px rgba(0,0,0,0.05); position: relative;">
                <div>
                    <div style="color: #64748b; font-size: 13px; font-weight: 600; text-transform: uppercase;">Total Market Due</div>
                    <div style="font-size: 28px; font-weight: 700; color: #ef4444; margin: 10px 0;">৳<?php echo number_format( $pending_dues_amount, 2 ); ?></div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px; margin-top: 15px; padding-top: 15px; border-top: 1px solid #f1f5f9;">
                    <span style="background: #fef2f2; color: #dc2626; padding: 4px 8px; border-radius: 4px; font-weight: 500;">Receivables</span>
                    <a href="<?php echo esc_url( $accounts_tab_url ); ?>" style="text-decoration: none; color: #dc2626; font-weight: 500;">Collect &rarr;</a>
                </div>
            </div>

            <div class="ifs-terp-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #10b981; box-shadow: 0 1px 3px rgba(0,0,0,0.05); position: relative;">
                <div>
                    <div style="color: #64748b; font-size: 13px; font-weight: 600; text-transform: uppercase;">Today's Income</div>
                    <div style="font-size: 28px; font-weight: 700; color: #10b981; margin: 10px 0;">৳<?php echo number_format( $today_income, 2 ); ?></div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px; margin-top: 15px; padding-top: 15px; border-top: 1px solid #f1f5f9;">
                    <span style="background: #ecfdf5; color: #059669; padding: 4px 8px; border-radius: 4px; font-weight: 500;">Gross Inflow</span>
                    <span style="color: #64748b; font-weight: 500;">Real-time</span>
                </div>
            </div>

            <div class="ifs-terp-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #64748b; box-shadow: 0 1px 3px rgba(0,0,0,0.05); position: relative;">
                <div>
                    <div style="color: #64748b; font-size: 13px; font-weight: 600; text-transform: uppercase;">Today's Expense</div>
                    <div style="font-size: 28px; font-weight: 700; color: #475569; margin: 10px 0;">৳<?php echo number_format( $today_expense, 2 ); ?></div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px; margin-top: 15px; padding-top: 15px; border-top: 1px solid #f1f5f9;">
                    <span style="background: #f1f5f9; color: #475569; padding: 4px 8px; border-radius: 4px; font-weight: 500;">Outflow Stack</span>
                    <span style="color: #64748b; font-weight: 500;">Ledger Entry</span>
                </div>
            </div>

            <div class="ifs-terp-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid <?php echo ( $net_profit_loss >= 0 ) ? '#10b981' : '#ef4444'; ?>; box-shadow: 0 1px 3px rgba(0,0,0,0.05); position: relative;">
                <div>
                    <div style="color: #64748b; font-size: 13px; font-weight: 600; text-transform: uppercase;">Net Margin (This Month)</div>
                    <div style="font-size: 28px; font-weight: 700; color: <?php echo ( $net_profit_loss >= 0 ) ? '#10b981' : '#ef4444'; ?>; margin: 10px 0;">
                        ৳<?php echo number_format( $net_profit_loss, 2 ); ?>
                    </div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px; margin-top: 15px; padding-top: 15px; border-top: 1px solid #f1f5f9;">
                    <span style="background: <?php echo ( $net_profit_loss >= 0 ) ? '#ecfdf5' : '#fef2f2'; ?>; color: <?php echo ( $net_profit_loss >= 0 ) ? '#059669' : '#dc2626'; ?>; padding: 4px 8px; border-radius: 4px; font-weight: 500;">
                        <?php echo ( $net_profit_loss >= 0 ) ? 'Net Profit' : 'Net Loss'; ?>
                    </span>
                    <a href="<?php echo esc_url( $accounts_tab_url ); ?>" style="text-decoration: none; color: <?php echo ( $net_profit_loss >= 0 ) ? '#059669' : '#dc2626'; ?>; font-weight: 500;">Ledger &rarr;</a>
                </div>
            </div>

            <div class="ifs-terp-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #0ea5e9; box-shadow: 0 1px 3px rgba(0,0,0,0.05); position: relative;">
                <div>
                    <div style="color: #64748b; font-size: 13px; font-weight: 600; text-transform: uppercase;">Active B2B Agents</div>
                    <div style="font-size: 28px; font-weight: 700; color: #1e293b; margin: 10px 0;">
                        <?php echo $active_agents_count; ?>
                    </div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px; margin-top: 15px; padding-top: 15px; border-top: 1px solid #f1f5f9;">
                    <span style="background: #e0f2fe; color: #0284c7; padding: 4px 8px; border-radius: 4px; font-weight: 500;">Agent Network</span>
                    <a href="<?php echo esc_url( $agents_tab_url ); ?>" style="text-decoration: none; color: #0284c7; font-weight: 500;">Manage &rarr;</a>
                </div>
            </div>

        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        function ifsTerpDashboardClockEngine() {
            var timeObject = new Date();
            var processString = timeObject.getHours().toString().padStart(2, '0') + ':' + 
                                timeObject.getMinutes().toString().padStart(2, '0') + ':' + 
                                timeObject.getSeconds().toString().padStart(2, '0');
            var tickerContainer = document.getElementById('ifsTerpLiveTickerClock');
            if (tickerContainer) {
                tickerContainer.textContent = processString;
            }
        }
        setInterval(ifsTerpDashboardClockEngine, 1000);
        ifsTerpDashboardClockEngine();
    });
    </script>
    <?php
}