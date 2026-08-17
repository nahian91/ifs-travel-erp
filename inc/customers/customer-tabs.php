<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Enterprise Ultra-Modern Segmented Sub-Navigation for Customers Module
 * @param string $active_tab The currently active sub-action
 */
function ifs_terp_customer_render_tabs( $active_tab = 'list' ) {
    global $wpdb;
    $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=customers' );
    
    // Dynamic Query Metrics
    $table_customers = $wpdb->prefix . 'iterp_customers';
    $total_customers = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_customers" );
    $retail_count    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $table_customers WHERE client_type = %s", 'Retail' ) );
    $corporate_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $table_customers WHERE client_type = %s", 'Corporate' ) );
    ?>
    
    <div class="ifs-pro-tab-wrapper">
        <!-- Top Executive Identity & Mini Analytics Header -->
        <div class="ifs-pro-header-card">
            <div class="ifs-pro-identity">
                <div class="ifs-pro-icon-glow">
                    <span class="dashicons dashicons-admin-users"></span>
                </div>
                <div class="ifs-pro-title-meta">
                    <div class="ifs-pro-badge-group">
                        <span class="ifs-status-dot"></span>
                        <span class="ifs-meta-tag">CRM Directory</span>
                        <span class="ifs-meta-tag-blue">B2C Operations</span>
                    </div>
                    <h2 class="ifs-pro-heading">Customer Directory & Profiles</h2>
                    <p class="ifs-pro-caption">Manage passenger portfolios, passport expirations, and travel bookings</p>
                </div>
            </div>
            
            <!-- Quick Stat Badges Strip -->
            <div class="ifs-pro-stats-strip">
                <div class="ifs-stat-pill">
                    <span class="ifs-stat-lbl">Total Profiles</span>
                    <span class="ifs-stat-num color-dark"><?php echo number_format( $total_customers ); ?></span>
                </div>
                <div class="ifs-stat-pill">
                    <span class="ifs-stat-lbl">Retail Travelers</span>
                    <span class="ifs-stat-num color-blue"><?php echo number_format( $retail_count ); ?></span>
                </div>
                <div class="ifs-stat-pill">
                    <span class="ifs-stat-lbl">Corporate</span>
                    <span class="ifs-stat-num color-purple"><?php echo number_format( $corporate_count ); ?></span>
                </div>
            </div>
        </div>

        <!-- Glassmorphism Floating Navigation Strip -->
        <div class="ifs-pro-nav-container">
            <nav class="ifs-pro-nav-bar">
                <a href="<?php echo esc_url( $base_url . '&sub=list' ); ?>" 
                   class="ifs-pro-nav-btn <?php echo ( $active_tab === 'list' ) ? 'active-tab' : ''; ?>">
                    <span class="dashicons dashicons-groups"></span>
                    <span class="ifs-btn-label">All Travelers</span>
                    <span class="ifs-pro-counter"><?php echo $total_customers; ?></span>
                </a>

                <a href="<?php echo esc_url( $base_url . '&sub=add' ); ?>" 
                   class="ifs-pro-nav-btn <?php echo ( $active_tab === 'add' ) ? 'active-tab' : ''; ?>">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <span class="ifs-btn-label">New Customer Entry</span>
                </a>

                <?php if ( $active_tab === 'edit' ) : ?>
                    <div class="ifs-pro-nav-btn state-active">
                        <span class="dashicons dashicons-edit"></span>
                        <span class="ifs-btn-label">Editing Portfolio</span>
                        <span class="ifs-active-bubble"></span>
                    </div>
                <?php endif; ?>

                <?php if ( $active_tab === 'view' ) : ?>
                    <div class="ifs-pro-nav-btn state-active">
                        <span class="dashicons dashicons-id"></span>
                        <span class="ifs-btn-label">Profile Overview</span>
                        <span class="ifs-active-bubble"></span>
                    </div>
                <?php endif; ?>
            </nav>
        </div>
    </div>

    <!-- Ultra High-End UI Stylesheet -->
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
            position: relative;
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
            background: linear-gradient(135deg, #003376 0%, #0284c7 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            box-shadow: 0 8px 18px -4px rgba(0, 51, 118, 0.35);
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
            background: #10b981;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
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
            background: #f0fdf4;
            color: #166534;
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
            min-width: 95px;
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
        .color-dark { color: #0f172a; }
        .color-blue { color: #0284c7; }
        .color-purple { color: #7c3aed; }

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
            color: #003376;
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

        .ifs-pro-counter {
            background: #e2e8f0;
            color: #475569;
            font-size: 11px;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 20px;
            transition: all 0.2s ease;
        }

        .ifs-pro-nav-btn.active-tab .ifs-pro-counter {
            background: #003376;
            color: #ffffff;
        }

        /* Active State Badge (For Edit & View) */
        .ifs-pro-nav-btn.state-active {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }

        .ifs-pro-nav-btn.state-active .dashicons {
            color: #0369a1;
        }

        .ifs-active-bubble {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #0284c7;
            display: inline-block;
        }
    </style>
    <?php
}