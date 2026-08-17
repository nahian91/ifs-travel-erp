<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Enterprise Ultra-Modern Segmented Sub-Navigation for Reports & Analytics Module
 * @param string $active_tab The currently active sub-action
 */
function ifs_terp_report_render_tabs( $active_tab = 'sales' ) {
    global $wpdb;
    $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=reports' ); 
    
    // Dynamic Metric Summaries for Reporting Cards
    $table_invoices = $wpdb->prefix . 'iterp_invoices';
    $table_ledger   = $wpdb->prefix . 'iterp_ledger';
    $table_agents   = $wpdb->prefix . 'iterp_agents';

    $month_start = current_time( 'Y-m-01 00:00:00' );
    $month_end   = current_time( 'Y-m-t 23:59:59' );

    // Turnover & Income
    $monthly_turnover = (float) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(net_total) FROM $table_invoices WHERE created_at BETWEEN %s AND %s", $month_start, $month_end ) );
    
    // Net Operating Profit/Loss
    $month_income  = (float) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(amount) FROM $table_ledger WHERE transaction_type = 'Income' AND transaction_date BETWEEN %s AND %s", $month_start, $month_end ) );
    $month_expense = (float) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(amount) FROM $table_ledger WHERE transaction_type = 'Expense' AND transaction_date BETWEEN %s AND %s", $month_start, $month_end ) );
    $net_margin    = $month_income - $month_expense;

    // Overdue / Negative Agent Balances
    $agent_dues = (float) $wpdb->get_var( "SELECT SUM(ABS(current_balance)) FROM $table_agents WHERE current_balance < 0" );
    ?>
    
    <div class="ifs-pro-tab-wrapper">
        <!-- Top Executive Identity & Mini Analytics Header -->
        <div class="ifs-pro-header-card">
            <div class="ifs-pro-identity">
                <div class="ifs-pro-icon-glow">
                    <span class="dashicons dashicons-chart-area"></span>
                </div>
                <div class="ifs-pro-title-meta">
                    <div class="ifs-pro-badge-group">
                        <span class="ifs-status-dot"></span>
                        <span class="ifs-meta-tag">Business Intelligence</span>
                        <span class="ifs-meta-tag-blue">Analytics & Audits</span>
                    </div>
                    <h2 class="ifs-pro-heading">Operational Reports & Business Analytics</h2>
                    <p class="ifs-pro-caption">Financial audits, turnover metrics, net margin statements, and B2B exposure tracking</p>
                </div>
            </div>
            
            <!-- Quick Stat Badges Strip -->
            <div class="ifs-pro-stats-strip">
                <div class="ifs-stat-pill">
                    <span class="ifs-stat-lbl">Turnover (Month)</span>
                    <span class="ifs-stat-num color-blue">৳<?php echo number_format( $monthly_turnover, 2 ); ?></span>
                </div>
                <div class="ifs-stat-pill">
                    <span class="ifs-stat-lbl">Net Margin</span>
                    <span class="ifs-stat-num <?php echo ( $net_margin >= 0 ) ? 'color-emerald' : 'color-rose'; ?>">
                        ৳<?php echo number_format( $net_margin, 2 ); ?>
                    </span>
                </div>
                <div class="ifs-stat-pill">
                    <span class="ifs-stat-lbl">Agent Negative Due</span>
                    <span class="ifs-stat-num color-rose">৳<?php echo number_format( $agent_dues, 2 ); ?></span>
                </div>
            </div>
        </div>

        <!-- Glassmorphism Floating Navigation Strip -->
        <div class="ifs-pro-nav-container">
            <nav class="ifs-pro-nav-bar">
                <a href="<?php echo esc_url( $base_url . '&sub=sales' ); ?>" 
                   class="ifs-pro-nav-btn <?php echo ( $active_tab === 'sales' ) ? 'active-tab' : ''; ?>">
                    <span class="dashicons dashicons-chart-area"></span>
                    <span class="ifs-btn-label">Sales & Turnover Report</span>
                </a>

                <a href="<?php echo esc_url( $base_url . '&sub=profit_loss' ); ?>" 
                   class="ifs-pro-nav-btn <?php echo ( $active_tab === 'profit_loss' ) ? 'active-tab' : ''; ?>">
                    <span class="dashicons dashicons-chart-pie"></span>
                    <span class="ifs-btn-label">Profit & Loss Statement</span>
                </a>

                <a href="<?php echo esc_url( $base_url . '&sub=agent_dues' ); ?>" 
                   class="ifs-pro-nav-btn <?php echo ( $active_tab === 'agent_dues' ) ? 'active-tab' : ''; ?>">
                    <span class="dashicons dashicons-warning"></span>
                    <span class="ifs-btn-label">Agent Receivables & Dues</span>
                    <?php if ( $agent_dues > 0 ) : ?>
                        <span class="ifs-pro-counter-alert">Alert</span>
                    <?php endif; ?>
                </a>
            </nav>
        </div>
    </div>

    <!-- Modern High-End UI Stylesheet -->
    <style>
        .ifs-pro-tab-wrapper {
            margin-bottom: 30px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        /* Identity Banner */
        .ifs-pro-header-card {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            padding: 24px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04), 0 2px 6px -1px rgba(15, 23, 42, 0.02);
            margin-bottom: 18px;
        }

        .ifs-pro-identity {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .ifs-pro-icon-glow {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            box-shadow: 0 8px 18px -4px rgba(2, 132, 199, 0.35);
            flex-shrink: 0;
        }

        .ifs-pro-icon-glow .dashicons {
            font-size: 26px;
            width: 26px;
            height: 26px;
        }

        .ifs-pro-badge-group {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 4px;
        }

        .ifs-status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #0284c7;
            box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.2);
            display: inline-block;
        }

        .ifs-meta-tag {
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
        }

        .ifs-meta-tag-blue {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            background: #e0f2fe;
            color: #0369a1;
            padding: 2px 7px;
            border-radius: 4px;
        }

        .ifs-pro-heading {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.4px;
        }

        .ifs-pro-caption {
            margin: 3px 0 0 0;
            font-size: 13.5px;
            color: #64748b;
            font-weight: 400;
        }

        /* Stats Strip */
        .ifs-pro-stats-strip {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .ifs-stat-pill {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 8px 16px;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 105px;
        }

        .ifs-stat-lbl {
            font-size: 10.5px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .ifs-stat-num {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: -0.2px;
        }
        .color-blue    { color: #0284c7; }
        .color-emerald { color: #059669; }
        .color-rose    { color: #e11d48; }

        /* Floating Navigation Strip */
        .ifs-pro-nav-container {
            display: flex;
            align-items: center;
        }

        .ifs-pro-nav-bar {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            padding: 5px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.02);
            max-width: 100%;
            overflow-x: auto;
        }

        .ifs-pro-nav-btn {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 10px 20px;
            font-size: 13.5px;
            font-weight: 600;
            color: #64748b;
            text-decoration: none;
            border-radius: 9px;
            transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            cursor: pointer;
            white-space: nowrap;
            border: 1px solid transparent;
        }

        .ifs-pro-nav-btn .dashicons {
            font-size: 17px;
            width: 17px;
            height: 17px;
            color: #64748b;
            transition: transform 0.2s ease, color 0.2s ease;
        }

        .ifs-pro-nav-btn:hover {
            color: #0f172a;
            background: rgba(255, 255, 255, 0.65);
        }

        .ifs-pro-nav-btn:hover .dashicons {
            color: #0284c7;
            transform: scale(1.08);
        }

        /* Active Navigation Button */
        .ifs-pro-nav-btn.active-tab {
            background: #ffffff;
            color: #003376;
            font-weight: 700;
            border: 1px solid rgba(0, 51, 118, 0.08);
            box-shadow: 0 4px 12px rgba(0, 51, 118, 0.06), 0 1px 3px rgba(0, 0, 0, 0.03);
        }

        .ifs-pro-nav-btn.active-tab .dashicons {
            color: #003376;
        }

        .ifs-pro-counter-alert {
            background: #fee2e2;
            color: #dc2626;
            font-size: 10.5px;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 12px;
            text-transform: uppercase;
        }
    </style>
    <?php
}