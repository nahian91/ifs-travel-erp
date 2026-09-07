<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Sub-Navigation Bar for Reports & Analytics Module
 * Flat Minimal UI: No Shadows, Strict 42px Navigation Control Heights, Clean Border System
 * 
 * @param string $active_tab The currently active sub-action (sales, profit_loss, agent_dues)
 */
function ifs_terp_report_render_tabs( $active_tab = 'sales' ) {
    global $wpdb;
    $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=reports' ); 

    // Check for negative agent balances to conditionally display alert badge
    $table_agents = $wpdb->prefix . 'iterp_agents';
    $agent_dues   = (float) $wpdb->get_var( "SELECT SUM(ABS(current_balance)) FROM {$table_agents} WHERE current_balance < 0" );

    // Tab Definitions
    $nav_items = array(
        'sales'       => array(
            'label' => __( 'Sales Report', 'ifs-travel-erp' ),
            'icon'  => 'dashicons-chart-area',
            'url'   => $base_url . '&sub=sales',
        ),
        'profit_loss' => array(
            'label' => __( 'Profit & Loss', 'ifs-travel-erp' ),
            'icon'  => 'dashicons-chart-pie',
            'url'   => $base_url . '&sub=profit_loss',
        ),
        'agent_dues'  => array(
            'label' => __( 'Agent Dues', 'ifs-travel-erp' ),
            'icon'  => 'dashicons-warning',
            'url'   => $base_url . '&sub=agent_dues',
            'alert' => ( $agent_dues > 0 ),
        ),
    );
    ?>

    <div class="ifs-module-header">
        <!-- Module Heading -->
        <div class="ifs-module-title-wrap">
            <div class="ifs-module-icon">
                <span class="dashicons dashicons-chart-area"></span>
            </div>
            <div>
                <h2 class="ifs-module-title"><?php esc_html_e( 'Reports', 'ifs-travel-erp' ); ?></h2>
                <p class="ifs-module-subtitle"><?php esc_html_e( 'Sales turnover statements, profit/loss reconciliation, and agent dues audits', 'ifs-travel-erp' ); ?></p>
            </div>
        </div>

        <!-- Sub-Navigation Bar -->
        <nav class="ifs-sub-nav">
            <?php foreach ( $nav_items as $key => $item ) : 
                $is_active = ( $active_tab === $key );
                ?>
                <a href="<?php echo esc_url( $item['url'] ); ?>" 
                   class="ifs-sub-nav-item <?php echo $is_active ? 'active' : ''; ?>">
                    <span class="dashicons <?php echo esc_attr( $item['icon'] ); ?>"></span>
                    <span><?php echo esc_html( $item['label'] ); ?></span>
                    <?php if ( ! empty( $item['alert'] ) ) : ?>
                        <span class="ifs-nav-alert"><?php esc_html_e( 'Due', 'ifs-travel-erp' ); ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <!-- Stylesheet: Flat Minimal UI, Zero Shadows & Strict Control Heights -->
    <style>
        .ifs-module-header {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px 24px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            box-sizing: border-box;
        }
        .ifs-module-header *,
        .ifs-module-header *::before,
        .ifs-module-header *::after {
            box-sizing: border-box;
            box-shadow: none !important;
            text-shadow: none !important;
        }

        .ifs-module-title-wrap {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .ifs-module-icon {
            width: 42px;
            height: 42px;
            border-radius: 9px;
            background: #003376;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            flex-shrink: 0;
        }
        .ifs-module-icon .dashicons {
            font-size: 20px;
            width: 20px;
            height: 20px;
        }

        .ifs-module-title {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.2;
        }

        .ifs-module-subtitle {
            margin: 2px 0 0 0;
            font-size: 12.5px;
            color: #64748b;
        }

        /* Nav Pills Container */
        .ifs-sub-nav {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 4px;
            display: inline-flex;
            gap: 4px;
            flex-wrap: wrap;
            align-items: center;
        }

        .ifs-sub-nav-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 34px;
            padding: 0 14px;
            font-size: 12.5px;
            font-weight: 700;
            color: #475569;
            text-decoration: none;
            border-radius: 6px;
            transition: background-color 0.2s ease, color 0.2s ease;
            white-space: nowrap;
        }

        .ifs-sub-nav-item .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
            color: #64748b;
        }

        .ifs-sub-nav-item:hover {
            color: #003376;
            background: #f1f5f9;
        }
        .ifs-sub-nav-item:hover .dashicons {
            color: #003376;
        }

        .ifs-sub-nav-item.active {
            background: #003376;
            color: #ffffff !important;
        }

        .ifs-sub-nav-item.active .dashicons {
            color: #ffffff;
        }

        .ifs-nav-alert {
            background: #fee2e2;
            color: #dc2626;
            font-size: 9.5px;
            font-weight: 800;
            padding: 1px 6px;
            border-radius: 4px;
            text-transform: uppercase;
            margin-left: 2px;
            border: 1px solid #fecaca;
        }

        .ifs-sub-nav-item.active .ifs-nav-alert {
            background: #dc2626;
            color: #ffffff;
            border-color: #dc2626;
        }

        @media print {
            .ifs-module-header {
                display: none !important;
            }
        }
    </style>
    <?php
}