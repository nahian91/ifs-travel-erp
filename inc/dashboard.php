<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Dashboard Tab - IFS Travel ERP Operations & Financial Control Panel
 * Database Mapping: iterp_tickets, iterp_visas, iterp_invoices, iterp_ledger, iterp_agents, iterp_customers
 */
function ifs_terp_dashboard_tab() {
    global $wpdb;

    // Core Database Table Registries
    $table_tickets   = $wpdb->prefix . 'iterp_tickets';
    $table_visas     = $wpdb->prefix . 'iterp_visas';
    $table_invoices  = $wpdb->prefix . 'iterp_invoices';
    $table_ledger    = $wpdb->prefix . 'iterp_ledger';
    $table_agents    = $wpdb->prefix . 'iterp_agents';
    $table_customers = $wpdb->prefix . 'iterp_customers';
    $table_hajj      = $wpdb->prefix . 'iterp_hajj_bookings';

    // Time Frames & Ranges
    $today_start = current_time( 'Y-m-d 00:00:00' );
    $today_end   = current_time( 'Y-m-d 23:59:59' );
    $month_start = current_time( 'Y-m-01 00:00:00' );
    $month_end   = current_time( 'Y-m-t 23:59:59' );

    /* =========================================================================
       1. CORE STATS & AGGREGATIONS
       ========================================================================= */
    $total_tickets_today = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(id) FROM $table_tickets WHERE created_at BETWEEN %s AND %s",
        $today_start,
        $today_end
    ) );

    $processing_visas_count = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(id) FROM $table_visas WHERE status = %s",
        'Processing'
    ) );

    $active_hajj_count = (int) $wpdb->get_var(
        "SELECT COUNT(id) FROM $table_hajj WHERE status IN ('Booked', 'Confirmed')"
    );

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

    $net_profit_loss = $month_income - $month_expense;

    $pending_dues_amount = (float) $wpdb->get_var(
        "SELECT SUM(due_amount) FROM $table_invoices WHERE due_amount > 0"
    );

    $active_agents_count = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(id) FROM $table_agents WHERE status = %s",
        'Active'
    ) );

    /* =========================================================================
       2. RECENT RECORDS FOR LIVE FEED
       ========================================================================= */
    $recent_tickets = $wpdb->get_results( "
        SELECT t.*, c.full_name as customer_name 
        FROM $table_tickets t 
        LEFT JOIN $table_customers c ON t.customer_id = c.id 
        ORDER BY t.id DESC LIMIT 5
    " );

    $recent_visas = $wpdb->get_results( "
        SELECT v.*, c.full_name as customer_name 
        FROM $table_visas v 
        LEFT JOIN $table_customers c ON v.customer_id = c.id 
        ORDER BY v.id DESC LIMIT 5
    " );

    // Admin URL Anchors
    $ticketing_tab_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=ticketing' );
    $accounts_tab_url  = admin_url( 'admin.php?page=ifs_travel_erp&tab=accounts' );
    $visa_tab_url      = admin_url( 'admin.php?page=ifs_travel_erp&tab=visa' );
    $hajj_tab_url      = admin_url( 'admin.php?page=ifs_travel_erp&tab=hajj_umrah' );
    $agents_tab_url    = admin_url( 'admin.php?page=ifs_travel_erp&tab=b2b_agents' );

    // Greeting Engine
    $current_hour = (int) current_time( 'H' );
    if ( $current_hour >= 5 && $current_hour < 12 ) {
        $greeting_prefix = 'Good Morning';
    } elseif ( $current_hour >= 12 && $current_hour < 17 ) {
        $greeting_prefix = 'Good Afternoon';
    } elseif ( $current_hour >= 17 && $current_hour < 21 ) {
        $greeting_prefix = 'Good Evening';
    } else {
        $greeting_prefix = 'Welcome Back';
    }

    $current_wp_user = wp_get_current_user();
    $user_display_name = ! empty( $current_wp_user->display_name ) ? $current_wp_user->display_name : 'Partner';
    $rendered_greeting = $greeting_prefix . ', ' . $user_display_name;
    ?>
    <div class="ifs-terp-dashboard-wrapper">
        
        <!-- Header Banner -->
        <div class="ifs-terp-header-block" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
            <div style="display: flex; align-items: center; gap: 18px;">
                <div class="ifs-terp-header-logo">
                    <img src="<?php echo esc_url( ITERP_URL . 'assets/img/logo.png' ); ?>" 
                         alt="System Logo" style="height: 52px; width: auto; display: block;" onerror="this.style.display='none'">
                </div>
                <div>
                    <h1 style="margin: 0 0 4px 0; font-size: 20px; color: #0f172a; font-weight: 700;"><?php echo esc_html( $rendered_greeting ); ?></h1>
                    <p style="margin: 0; color: #64748b; font-size: 13px;">IFS Travel ERP Operations Desk & Live Financial Summary</p>
                </div>
            </div>

            <div class="ifs-terp-live-timer-container" style="text-align: right;">
                <div style="color: #64748b; font-size: 13px; margin-bottom: 4px;">
                    <span class="dashicons dashicons-calendar-alt" style="font-size:15px; vertical-align:middle; margin-right:4px;"></span> 
                    <?php echo date( 'l, jS F Y' ); ?>
                </div>
                <div class="ifs-terp-ticker-digits" style="font-size: 16px; font-weight: 700; color: #003376; font-family: monospace;">
                    <span class="dashicons dashicons-clock" style="font-size:16px; vertical-align:middle; margin-right:4px;"></span>
                    <span id="ifsTerpLiveTickerClock">00:00:00</span>
                </div>
            </div>
        </div>

        <!-- Quick Action Shortcuts -->
        <div style="display: flex; gap: 12px; margin-bottom: 25px; flex-wrap: wrap;">
            <a href="<?php echo esc_url( $ticketing_tab_url . '&sub=add' ); ?>" style="background: #003376; color: #fff; text-decoration: none; padding: 10px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-tickets-alt"></span> Issue Air Ticket
            </a>
            <a href="<?php echo esc_url( $visa_tab_url . '&sub=add' ); ?>" style="background: #fff; color: #0f172a; border: 1px solid #cbd5e1; text-decoration: none; padding: 10px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-admin-site-alt3" style="color: #d97706;"></span> New Visa File
            </a>
            <a href="<?php echo esc_url( $hajj_tab_url . '&sub=add' ); ?>" style="background: #fff; color: #0f172a; border: 1px solid #cbd5e1; text-decoration: none; padding: 10px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-groups" style="color: #0284c7;"></span> Pilgrim Booking
            </a>
            <a href="<?php echo esc_url( $accounts_tab_url . '&sub=create_invoice' ); ?>" style="background: #fff; color: #0f172a; border: 1px solid #cbd5e1; text-decoration: none; padding: 10px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-media-document" style="color: #7c3aed;"></span> Generate Invoice
            </a>
            <a href="<?php echo esc_url( $accounts_tab_url . '&sub=ledger' ); ?>" style="background: #fff; color: #0f172a; border: 1px solid #cbd5e1; text-decoration: none; padding: 10px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-money-alt" style="color: #dc2626;"></span> Record Expense
            </a>
        </div>

        <!-- Metric KPI Cards -->
        <div class="ifs-terp-summary-grid-matrix" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px;">
            
            <div style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #3b82f6; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
                <div style="color: #64748b; font-size: 12px; font-weight: 600; text-transform: uppercase;">Tickets Issued (Today)</div>
                <div style="font-size: 26px; font-weight: 700; color: #0f172a; margin: 8px 0;"><?php echo $total_tickets_today; ?></div>
                <div style="font-size: 12px; color: #2563eb;">
                    <a href="<?php echo esc_url( $ticketing_tab_url ); ?>" style="text-decoration:none; color:inherit; font-weight:600;">View Flight Ledger &rarr;</a>
                </div>
            </div>

            <div style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #f59e0b; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
                <div style="color: #64748b; font-size: 12px; font-weight: 600; text-transform: uppercase;">Visas in Processing</div>
                <div style="font-size: 26px; font-weight: 700; color: #0f172a; margin: 8px 0;"><?php echo $processing_visas_count; ?></div>
                <div style="font-size: 12px; color: #d97706;">
                    <a href="<?php echo esc_url( $visa_tab_url ); ?>" style="text-decoration:none; color:inherit; font-weight:600;">Track Applications &rarr;</a>
                </div>
            </div>

            <div style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #0284c7; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
                <div style="color: #64748b; font-size: 12px; font-weight: 600; text-transform: uppercase;">Active Hajj & Umrah</div>
                <div style="font-size: 26px; font-weight: 700; color: #0f172a; margin: 8px 0;"><?php echo $active_hajj_count; ?> <span style="font-size: 14px; font-weight: normal; color: #64748b;">Pilgrims</span></div>
                <div style="font-size: 12px; color: #0284c7;">
                    <a href="<?php echo esc_url( $hajj_tab_url ); ?>" style="text-decoration:none; color:inherit; font-weight:600;">Pilgrim Directory &rarr;</a>
                </div>
            </div>

            <div style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #ef4444; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
                <div style="color: #64748b; font-size: 12px; font-weight: 600; text-transform: uppercase;">Total Market Due</div>
                <div style="font-size: 24px; font-weight: 800; color: #dc2626; margin: 8px 0;">৳<?php echo number_format( $pending_dues_amount, 2 ); ?></div>
                <div style="font-size: 12px; color: #dc2626;">
                    <a href="<?php echo esc_url( $accounts_tab_url ); ?>" style="text-decoration:none; color:inherit; font-weight:600;">Collect Receivables &rarr;</a>
                </div>
            </div>

            <div style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #10b981; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
                <div style="color: #64748b; font-size: 12px; font-weight: 600; text-transform: uppercase;">Today's Inflow (Income)</div>
                <div style="font-size: 24px; font-weight: 700; color: #166534; margin: 8px 0;">৳<?php echo number_format( $today_income, 2 ); ?></div>
                <div style="font-size: 12px; color: #64748b;">Daily Cash Register</div>
            </div>

            <div style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #64748b; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
                <div style="color: #64748b; font-size: 12px; font-weight: 600; text-transform: uppercase;">Today's Outflow (Expense)</div>
                <div style="font-size: 24px; font-weight: 700; color: #475569; margin: 8px 0;">৳<?php echo number_format( $today_expense, 2 ); ?></div>
                <div style="font-size: 12px; color: #64748b;">Office & Operating Cost</div>
            </div>

            <div style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid <?php echo ( $net_profit_loss >= 0 ) ? '#10b981' : '#ef4444'; ?>; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
                <div style="color: #64748b; font-size: 12px; font-weight: 600; text-transform: uppercase;">Net Margin (This Month)</div>
                <div style="font-size: 24px; font-weight: 800; color: <?php echo ( $net_profit_loss >= 0 ) ? '#166534' : '#dc2626'; ?>; margin: 8px 0;">
                    ৳<?php echo number_format( $net_profit_loss, 2 ); ?>
                </div>
                <div style="font-size: 12px; color: <?php echo ( $net_profit_loss >= 0 ) ? '#166534' : '#dc2626'; ?>; font-weight:600;">
                    <?php echo ( $net_profit_loss >= 0 ) ? 'Profitable Month' : 'Net Loss Status'; ?>
                </div>
            </div>

            <div style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #8b5cf6; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
                <div style="color: #64748b; font-size: 12px; font-weight: 600; text-transform: uppercase;">Active B2B Sub-Agents</div>
                <div style="font-size: 26px; font-weight: 700; color: #0f172a; margin: 8px 0;"><?php echo $active_agents_count; ?></div>
                <div style="font-size: 12px; color: #7c3aed;">
                    <a href="<?php echo esc_url( $agents_tab_url ); ?>" style="text-decoration:none; color:inherit; font-weight:600;">B2B Network &rarr;</a>
                </div>
            </div>

        </div>

        <!-- Recent Operations Feeds (Two Column Layout) -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 25px;">
            
            <!-- Latest Air Tickets Feed -->
            <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                    <h3 style="margin: 0; font-size: 15px; color: #0f172a; font-weight: 700;">
                        <span class="dashicons dashicons-tickets-alt" style="color: #003376; vertical-align: middle;"></span> Recent Air Tickets
                    </h3>
                    <a href="<?php echo esc_url( $ticketing_tab_url ); ?>" style="font-size: 12px; color: #003376; text-decoration: none; font-weight: 600;">View All</a>
                </div>

                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="text-align: left; color: #64748b; border-bottom: 1px solid #f1f5f9;">
                            <th style="padding: 8px 0;">Passenger / PNR</th>
                            <th style="padding: 8px 0;">Sector</th>
                            <th style="padding: 8px 0; text-align: right;">Sell (৳)</th>
                            <th style="padding: 8px 0; text-align: right;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $recent_tickets ) : foreach ( $recent_tickets as $t ) : ?>
                            <tr style="border-bottom: 1px solid #f8fafc;">
                                <td style="padding: 10px 0;">
                                    <div style="font-weight: 600; color: #0f172a;"><?php echo esc_html( $t->customer_name ?: 'Direct Client' ); ?></div>
                                    <div style="font-family: monospace; font-size: 11px; color: #0284c7; font-weight: bold;"><?php echo esc_html( $t->pnr ); ?></div>
                                </td>
                                <td style="padding: 10px 0;">
                                    <div><?php echo esc_html( $t->airline ); ?></div>
                                    <div style="font-size: 11px; color: #64748b;"><?php echo esc_html( $t->sector ); ?></div>
                                </td>
                                <td style="padding: 10px 0; text-align: right; font-weight: 600; color: #0f172a;">৳<?php echo number_format( $t->sell_price, 2 ); ?></td>
                                <td style="padding: 10px 0; text-align: right;">
                                    <span style="background: #dcfce7; color: #166534; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 600;"><?php echo esc_html( $t->status ); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr><td colspan="4" style="text-align: center; padding: 15px; color: #94a3b8;">No recent tickets found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Latest Visa Applications Feed -->
            <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                    <h3 style="margin: 0; font-size: 15px; color: #0f172a; font-weight: 700;">
                        <span class="dashicons dashicons-admin-site-alt3" style="color: #d97706; vertical-align: middle;"></span> Recent Visa Applications
                    </h3>
                    <a href="<?php echo esc_url( $visa_tab_url ); ?>" style="font-size: 12px; color: #003376; text-decoration: none; font-weight: 600;">View All</a>
                </div>

                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="text-align: left; color: #64748b; border-bottom: 1px solid #f1f5f9;">
                            <th style="padding: 8px 0;">Applicant Name</th>
                            <th style="padding: 8px 0;">Country</th>
                            <th style="padding: 8px 0;">Exp. Delivery</th>
                            <th style="padding: 8px 0; text-align: right;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $recent_visas ) : foreach ( $recent_visas as $v ) : ?>
                            <tr style="border-bottom: 1px solid #f8fafc;">
                                <td style="padding: 10px 0;">
                                    <div style="font-weight: 600; color: #0f172a;"><?php echo esc_html( $v->customer_name ?: 'Direct Client' ); ?></div>
                                    <div style="font-size: 11px; color: #64748b;"><?php echo esc_html( $v->visa_type ); ?></div>
                                </td>
                                <td style="padding: 10px 0; font-weight: 600; color: #0f172a;"><?php echo esc_html( $v->country ); ?></td>
                                <td style="padding: 10px 0; color: #64748b; font-size: 12px;"><?php echo ( $v->expected_delivery != '1970-01-01' ) ? date('d M Y', strtotime($v->expected_delivery)) : '-'; ?></td>
                                <td style="padding: 10px 0; text-align: right;">
                                    <span style="background: #fef3c7; color: #b45309; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 600;"><?php echo esc_html( $v->status ); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr><td colspan="4" style="text-align: center; padding: 15px; color: #94a3b8;">No visa applications found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
