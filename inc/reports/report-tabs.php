<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render Sub-Navigation Tabs for Reports Module
 */
function ifs_terp_report_render_tabs( $active_tab = 'sales' ) {
    $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=reports' ); 
    ?>
    <div class="ifs-module-header">
        <h2 class="ifs-module-title">Operational Reports & Business Analytics</h2>
        
        <div class="ifs-sub-nav-wrapper" style="display: flex; justify-content: flex-start; align-items: center; border-bottom: 2px solid #e2e8f0; width: 100%;">
            <div class="ifs-sub-nav-bar" style="display: flex; gap: 8px; margin-bottom: -2px;">
                <a href="<?php echo esc_url( $base_url . '&sub=sales' ); ?>" 
                   class="ifs-sub-nav-btn <?php echo ( $active_tab === 'sales' ) ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-chart-area"></span> Sales & Turnover Report
                </a>
                
                <a href="<?php echo esc_url( $base_url . '&sub=profit_loss' ); ?>" 
                   class="ifs-sub-nav-btn <?php echo ( $active_tab === 'profit_loss' ) ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-chart-pie"></span> Profit & Loss Statement
                </a>

                <a href="<?php echo esc_url( $base_url . '&sub=agent_dues' ); ?>" 
                   class="ifs-sub-nav-btn <?php echo ( $active_tab === 'agent_dues' ) ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-warning"></span> Agent Receivables & Dues
                </a>
            </div>
        </div>
    </div>

    <style>
        .ifs-module-header { margin-bottom: 24px; }
        .ifs-module-title { margin: 0 0 15px 0; font-size: 22px; font-weight: 700; color: #0f172a; }
        .ifs-sub-nav-btn { 
            display: inline-flex; align-items: center; gap: 6px; background: none; border: none; 
            padding: 10px 16px; font-size: 14px; font-weight: 600; color: #64748b; 
            cursor: pointer; border-bottom: 2px solid transparent; transition: all 0.2s ease; text-decoration: none; 
        }
        .ifs-sub-nav-btn .dashicons { font-size: 16px; width: 16px; height: 16px; }
        .ifs-sub-nav-btn:hover { color: #0f172a; }
        .ifs-sub-nav-btn.active { color: #003376; border-bottom-color: #003376; }
    </style>
    <?php
}