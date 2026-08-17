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
    <div class="ifs-dashboard-container">
        
        <!-- Premium Header Hero Banner -->
        <div class="ifs-dash-hero-banner">
            <div class="ifs-hero-intro">
                <div class="ifs-hero-avatar-box">
                    <img src="<?php echo esc_url( ITERP_URL . 'assets/img/logo.png' ); ?>" 
                         alt="System Logo" onerror="this.style.display='none'">
                    <div class="status-online-dot"></div>
                </div>
                <div>
                    <span class="hero-badge">Enterprise Operations Hub</span>
                    <h1 class="hero-title"><?php echo esc_html( $rendered_greeting ); ?></h1>
                    <p class="hero-subtitle">Real-time financial reconciliation, multi-module ticketing, and agency audit workflows.</p>
                </div>
            </div>

            <div class="ifs-hero-datetime-box">
                <div class="hero-date">
                    <span class="dashicons dashicons-calendar-alt"></span> 
                    <?php echo date( 'l, jS F Y' ); ?>
                </div>
                <div class="hero-time">
                    <span class="dashicons dashicons-clock"></span>
                    <span id="ifsTerpLiveTickerClock">00:00:00</span>
                </div>
            </div>
        </div>

        <!-- Quick Action Shortcuts Bar -->
        <div class="ifs-quick-action-strip">
            <span class="strip-label">Quick Actions:</span>
            <div class="strip-links">
                <a href="<?php echo esc_url( $ticketing_tab_url . '&sub=add' ); ?>" class="ifs-action-chip primary">
                    <span class="dashicons dashicons-tickets-alt"></span> Issue Air Ticket
                </a>
                <a href="<?php echo esc_url( $visa_tab_url . '&sub=add' ); ?>" class="ifs-action-chip">
                    <span class="dashicons dashicons-admin-site-alt3" style="color: #d97706;"></span> New Visa File
                </a>
                <a href="<?php echo esc_url( $hajj_tab_url . '&sub=add' ); ?>" class="ifs-action-chip">
                    <span class="dashicons dashicons-groups" style="color: #0284c7;"></span> Pilgrim Booking
                </a>
                <a href="<?php echo esc_url( $accounts_tab_url . '&sub=create_invoice' ); ?>" class="ifs-action-chip">
                    <span class="dashicons dashicons-media-document" style="color: #7c3aed;"></span> Generate Invoice
                </a>
                <a href="<?php echo esc_url( $accounts_tab_url . '&sub=ledger' ); ?>" class="ifs-action-chip">
                    <span class="dashicons dashicons-money-alt" style="color: #dc2626;"></span> Record Expense
                </a>
            </div>
        </div>

        <!-- Metric KPI Cards Matrix -->
        <div class="ifs-metric-cards-grid">
            
            <div class="ifs-kpi-card border-blue">
                <div class="kpi-icon-wrap bg-blue"><span class="dashicons dashicons-tickets-alt"></span></div>
                <div class="kpi-content">
                    <span class="kpi-title">Tickets Issued (Today)</span>
                    <div class="kpi-value"><?php echo number_format( $total_tickets_today ); ?></div>
                    <a href="<?php echo esc_url( $ticketing_tab_url ); ?>" class="kpi-link">View Flight Ledger &rarr;</a>
                </div>
            </div>

            <div class="ifs-kpi-card border-amber">
                <div class="kpi-icon-wrap bg-amber"><span class="dashicons dashicons-admin-site-alt3"></span></div>
                <div class="kpi-content">
                    <span class="kpi-title">Visas in Processing</span>
                    <div class="kpi-value"><?php echo number_format( $processing_visas_count ); ?></div>
                    <a href="<?php echo esc_url( $visa_tab_url ); ?>" class="kpi-link">Track Applications &rarr;</a>
                </div>
            </div>

            <div class="ifs-kpi-card border-cyan">
                <div class="kpi-icon-wrap bg-cyan"><span class="dashicons dashicons-groups"></span></div>
                <div class="kpi-content">
                    <span class="kpi-title">Active Hajj & Umrah</span>
                    <div class="kpi-value"><?php echo number_format( $active_hajj_count ); ?> <span class="unit-sub">Pilgrims</span></div>
                    <a href="<?php echo esc_url( $hajj_tab_url ); ?>" class="kpi-link">Pilgrim Directory &rarr;</a>
                </div>
            </div>

            <div class="ifs-kpi-card border-rose">
                <div class="kpi-icon-wrap bg-rose"><span class="dashicons dashicons-warning"></span></div>
                <div class="kpi-content">
                    <span class="kpi-title">Total Market Due</span>
                    <div class="kpi-value text-rose">৳<?php echo number_format( $pending_dues_amount, 2 ); ?></div>
                    <a href="<?php echo esc_url( $accounts_tab_url ); ?>" class="kpi-link text-rose">Collect Receivables &rarr;</a>
                </div>
            </div>

            <div class="ifs-kpi-card border-emerald">
                <div class="kpi-icon-wrap bg-emerald"><span class="dashicons dashicons-arrow-down-alt"></span></div>
                <div class="kpi-content">
                    <span class="kpi-title">Today's Inflow (Income)</span>
                    <div class="kpi-value text-emerald">৳<?php echo number_format( $today_income, 2 ); ?></div>
                    <span class="kpi-sub-text">Daily Cash Register</span>
                </div>
            </div>

            <div class="ifs-kpi-card border-slate">
                <div class="kpi-icon-wrap bg-slate"><span class="dashicons dashicons-arrow-up-alt"></span></div>
                <div class="kpi-content">
                    <span class="kpi-title">Today's Outflow (Expense)</span>
                    <div class="kpi-value text-slate">৳<?php echo number_format( $today_expense, 2 ); ?></div>
                    <span class="kpi-sub-text">Office & Operating Cost</span>
                </div>
            </div>

            <div class="ifs-kpi-card border-<?php echo ( $net_profit_loss >= 0 ) ? 'emerald' : 'rose'; ?>">
                <div class="kpi-icon-wrap <?php echo ( $net_profit_loss >= 0 ) ? 'bg-emerald' : 'bg-rose'; ?>"><span class="dashicons dashicons-chart-line"></span></div>
                <div class="kpi-content">
                    <span class="kpi-title">Net Margin (This Month)</span>
                    <div class="kpi-value <?php echo ( $net_profit_loss >= 0 ) ? 'text-emerald' : 'text-rose'; ?>">
                        ৳<?php echo number_format( $net_profit_loss, 2 ); ?>
                    </div>
                    <span class="kpi-sub-text font-bold"><?php echo ( $net_profit_loss >= 0 ) ? 'Profitable Month' : 'Net Loss Status'; ?></span>
                </div>
            </div>

            <div class="ifs-kpi-card border-indigo">
                <div class="kpi-icon-wrap bg-indigo"><span class="dashicons dashicons-networking"></span></div>
                <div class="kpi-content">
                    <span class="kpi-title">Active B2B Sub-Agents</span>
                    <div class="kpi-value"><?php echo number_format( $active_agents_count ); ?></div>
                    <a href="<?php echo esc_url( $agents_tab_url ); ?>" class="kpi-link">B2B Network &rarr;</a>
                </div>
            </div>

        </div>

        <!-- Recent Operations Feeds (Two Column Layout) -->
        <div class="ifs-feed-dual-grid">
            
            <!-- Latest Air Tickets Feed -->
            <div class="ifs-feed-card">
                <div class="feed-card-header">
                    <h3 class="feed-title">
                        <span class="dashicons dashicons-tickets-alt"></span> Recent Air Tickets
                    </h3>
                    <a href="<?php echo esc_url( $ticketing_tab_url ); ?>" class="feed-view-all">View All</a>
                </div>

                <div class="table-responsive-wrap">
                    <table class="ifs-premium-table">
                        <thead>
                            <tr>
                                <th>Passenger / PNR</th>
                                <th>Sector / Airline</th>
                                <th style="text-align: right;">Sell (৳)</th>
                                <th style="text-align: right;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( $recent_tickets ) : foreach ( $recent_tickets as $t ) : ?>
                                <tr>
                                    <td>
                                        <div class="row-main-title"><?php echo esc_html( $t->customer_name ?: 'Direct Client' ); ?></div>
                                        <div class="row-sub-code"><?php echo esc_html( $t->pnr ); ?></div>
                                    </td>
                                    <td>
                                        <div class="row-main-title"><?php echo esc_html( $t->airline ); ?></div>
                                        <div class="row-sub-code"><?php echo esc_html( $t->sector ); ?></div>
                                    </td>
                                    <td style="text-align: right; font-weight: 700; font-family: ui-monospace, monospace;">৳<?php echo number_format( $t->sell_price, 2 ); ?></td>
                                    <td style="text-align: right;">
                                        <span class="ifs-badge-status status-issued"><?php echo esc_html( $t->status ); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; else : ?>
                                <tr><td colspan="4" class="empty-feed-row">No recent air tickets found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Latest Visa Applications Feed -->
            <div class="ifs-feed-card">
                <div class="feed-card-header">
                    <h3 class="feed-title">
                        <span class="dashicons dashicons-admin-site-alt3"></span> Recent Visa Applications
                    </h3>
                    <a href="<?php echo esc_url( $visa_tab_url ); ?>" class="feed-view-all">View All</a>
                </div>

                <div class="table-responsive-wrap">
                    <table class="ifs-premium-table">
                        <thead>
                            <tr>
                                <th>Applicant Name</th>
                                <th>Country</th>
                                <th>Exp. Delivery</th>
                                <th style="text-align: right;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( $recent_visas ) : foreach ( $recent_visas as $v ) : ?>
                                <tr>
                                    <td>
                                        <div class="row-main-title"><?php echo esc_html( $v->customer_name ?: 'Direct Client' ); ?></div>
                                        <div class="row-sub-code"><?php echo esc_html( $v->visa_type ); ?></div>
                                    </td>
                                    <td><strong style="color: #0f172a;"><?php echo esc_html( $v->country ); ?></strong></td>
                                    <td style="color: #64748b; font-size: 12.5px;"><?php echo ( $v->expected_delivery != '1970-01-01' ) ? date('d M Y', strtotime($v->expected_delivery)) : '-'; ?></td>
                                    <td style="text-align: right;">
                                        <span class="ifs-badge-status status-processing"><?php echo esc_html( $v->status ); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; else : ?>
                                <tr><td colspan="4" class="empty-feed-row">No visa applications found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

    <!-- Premium Dashboard Stylesheet -->
    <style>
        .ifs-dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #0f172a;
        }

        /* Hero Banner */
        .ifs-dash-hero-banner {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.04);
            margin-bottom: 24px;
        }
        .ifs-hero-intro {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .ifs-hero-avatar-box {
            position: relative;
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
        }
        .ifs-hero-avatar-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 6px;
        }
        .status-online-dot {
            position: absolute;
            bottom: 4px;
            right: 4px;
            width: 10px;
            height: 10px;
            background: #10b981;
            border: 2px solid #ffffff;
            border-radius: 50%;
        }
        .hero-badge {
            display: inline-block;
            font-size: 10.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #e0f2fe;
            color: #0369a1;
            padding: 2px 8px;
            border-radius: 6px;
            margin-bottom: 4px;
        }
        .hero-title {
            margin: 0 0 2px 0;
            font-size: 22px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.4px;
        }
        .hero-subtitle {
            margin: 0;
            font-size: 13px;
            color: #64748b;
        }
        .ifs-hero-datetime-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 18px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            text-align: right;
        }
        .hero-date {
            font-size: 12.5px;
            font-weight: 700;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 6px;
            justify-content: flex-end;
        }
        .hero-date .dashicons { font-size: 15px; width: 15px; height: 15px; color: #0284c7; }
        .hero-time {
            font-size: 15px;
            font-weight: 800;
            color: #003376;
            font-family: ui-monospace, monospace;
            display: flex;
            align-items: center;
            gap: 6px;
            justify-content: flex-end;
        }
        .hero-time .dashicons { font-size: 15px; width: 15px; height: 15px; color: #003376; }

        /* Quick Action Strip */
        .ifs-quick-action-strip {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            flex-wrap: wrap;
        }
        .strip-label {
            font-size: 12px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .strip-links {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        .ifs-action-chip {
            background: #f8fafc;
            color: #334155;
            border: 1px solid #cbd5e1;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .ifs-action-chip.primary {
            background: linear-gradient(135deg, #003376 0%, #0284c7 100%);
            color: #ffffff;
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(0, 51, 118, 0.2);
        }
        .ifs-action-chip:hover {
            transform: translateY(-1px);
            background: #f1f5f9;
            color: #0f172a;
            border-color: #94a3b8;
        }
        .ifs-action-chip.primary:hover {
            background: linear-gradient(135deg, #002255 0%, #026aa2 100%);
            color: #ffffff;
        }
        .ifs-action-chip .dashicons { font-size: 15px; width: 15px; height: 15px; }

        /* Metric KPI Cards Matrix */
        .ifs-metric-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-bottom: 26px;
        }
        .ifs-kpi-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px 22px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .ifs-kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px -4px rgba(15, 23, 42, 0.06);
        }
        .ifs-kpi-card.border-blue { border-left: 5px solid #3b82f6; }
        .ifs-kpi-card.border-amber { border-left: 5px solid #f59e0b; }
        .ifs-kpi-card.border-cyan { border-left: 5px solid #0284c7; }
        .ifs-kpi-card.border-rose { border-left: 5px solid #ef4444; }
        .ifs-kpi-card.border-emerald { border-left: 5px solid #10b981; }
        .ifs-kpi-card.border-slate { border-left: 5px solid #64748b; }
        .ifs-kpi-card.border-indigo { border-left: 5px solid #8b5cf6; }

        .kpi-icon-wrap {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            flex-shrink: 0;
        }
        .kpi-icon-wrap.bg-blue { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); }
        .kpi-icon-wrap.bg-amber { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .kpi-icon-wrap.bg-cyan { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); }
        .kpi-icon-wrap.bg-rose { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        .kpi-icon-wrap.bg-emerald { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .kpi-icon-wrap.bg-slate { background: linear-gradient(135deg, #64748b 0%, #475569 100%); }
        .kpi-icon-wrap.bg-indigo { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }
        .kpi-icon-wrap .dashicons { font-size: 20px; width: 20px; height: 20px; }

        .kpi-content { flex: 1; min-width: 0; }
        .kpi-title { font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; display: block; margin-bottom: 4px; }
        .kpi-value { font-size: 24px; font-weight: 900; color: #0f172a; letter-spacing: -0.5px; line-height: 1.1; margin-bottom: 6px; font-family: ui-monospace, monospace; }
        .unit-sub { font-size: 13px; font-weight: 600; color: #64748b; font-family: -apple-system, sans-serif; }
        .kpi-sub-text { font-size: 12px; color: #64748b; font-weight: 600; display: block; }
        .kpi-link { font-size: 12px; font-weight: 700; color: #0284c7; text-decoration: none; display: inline-block; }
        .kpi-link:hover { text-decoration: underline; }
        .text-rose { color: #dc2626 !important; }
        .text-emerald { color: #059669 !important; }
        .text-slate { color: #475569 !important; }
        .font-bold { font-weight: 800 !important; }

        /* Feeds Grid */
        .ifs-feed-dual-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(480px, 1fr));
            gap: 24px;
        }
        .ifs-feed-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
        }
        .feed-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        .feed-title {
            margin: 0;
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .feed-title .dashicons { color: #003376; font-size: 20px; width: 20px; height: 20px; }
        .feed-view-all {
            font-size: 12.5px;
            font-weight: 700;
            color: #003376;
            text-decoration: none;
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 6px;
            transition: background 0.15s ease;
        }
        .feed-view-all:hover { background: #e2e8f0; }

        /* Premium Table Design */
        .table-responsive-wrap { overflow-x: auto; }
        .ifs-premium-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .ifs-premium-table thead th {
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 14px;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
        }
        .ifs-premium-table tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: #334155;
        }
        .ifs-premium-table tbody tr:hover td { background: #f8fafc; }
        .row-main-title { font-weight: 700; color: #0f172a; font-size: 13px; }
        .row-sub-code { font-family: ui-monospace, monospace; font-size: 11px; color: #64748b; margin-top: 2px; }

        .ifs-badge-status {
            display: inline-flex;
            align-items: center;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-issued { background: #dcfce7; color: #15803d; }
        .status-processing { background: #fef3c7; color: #b45309; }
        .empty-feed-row { text-align: center; padding: 25px !important; color: #94a3b8; font-style: italic; }
    </style>

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