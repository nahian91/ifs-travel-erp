<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Modern Sub-Agent Due & Outstanding Ledger Report Page
 */
function ifs_terp_report_agent_dues_page() {
    global $wpdb;
    $table_agents = $wpdb->prefix . 'iterp_agents';

    // Negative balance indicates agent owes agency money (Due)
    $dues_list  = $wpdb->get_results( "SELECT * FROM $table_agents WHERE current_balance < 0 ORDER BY current_balance ASC" );
    $total_due  = (float) $wpdb->get_var( "SELECT COALESCE(SUM(current_balance), 0) FROM $table_agents WHERE current_balance < 0" );
    $total_agt  = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_agents WHERE current_balance < 0" );
    $total_cred = (float) $wpdb->get_var( "SELECT COALESCE(SUM(credit_limit), 0) FROM $table_agents WHERE current_balance < 0" );
    ?>
    <div class="wrap ifs-report-wrapper" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
        
        <!-- Header & Page Title -->
        <div class="ifs-report-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
            <div>
                <h2 style="margin: 0; font-size: 22px; font-weight: 800; color: #0f172a;">
                    <span class="dashicons dashicons-id" style="color: #dc2626; font-size: 24px; vertical-align: middle;"></span> Sub-Agent Due & Outstanding Ledger
                </h2>
                <p style="margin: 4px 0 0 0; font-size: 13.5px; color: #64748b;">Monitor B2B sub-agent credit exposure, outstanding dues, and active collection statuses.</p>
            </div>
            <div>
                <button onclick="window.print();" class="button button-secondary" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600;">
                    <span class="dashicons dashicons-printer" style="font-size: 16px; margin-top: 3px;"></span> Print Due Report
                </button>
            </div>
        </div>

        <!-- Summary KPI Cards Strip -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 25px;">
            <div style="background: #fff; padding: 22px; border-radius: 12px; border-left: 5px solid #dc2626; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
                <span style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Total Outstanding Dues</span>
                <div style="font-size: 24px; font-weight: 900; color: #dc2626; margin-top: 8px; font-family: ui-monospace, monospace;">৳<?php echo number_format( abs( $total_due ), 2 ); ?></div>
                <span style="font-size: 11.5px; color: #991b1b; font-weight: 600; margin-top: 4px; display: block;">Requires Immediate Collection</span>
            </div>

            <div style="background: #fff; padding: 22px; border-radius: 12px; border-left: 5px solid #003376; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
                <span style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Defaulter Sub-Agents</span>
                <div style="font-size: 24px; font-weight: 900; color: #0f172a; margin-top: 8px;"><?php echo number_format( $total_agt ); ?> Agencies</div>
                <span style="font-size: 11.5px; color: #0284c7; font-weight: 600; margin-top: 4px; display: block;">With Negative Ledgers</span>
            </div>

            <div style="background: #fff; padding: 22px; border-radius: 12px; border-left: 5px solid #059669; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
                <span style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Allocated Credit Limit</span>
                <div style="font-size: 24px; font-weight: 900; color: #0f172a; margin-top: 8px; font-family: ui-monospace, monospace;">৳<?php echo number_format( $total_cred, 2 ); ?></div>
                <span style="font-size: 11.5px; color: #059669; font-weight: 600; margin-top: 4px; display: block;">Max Exposure Limit</span>
            </div>
        </div>

        <!-- Main Data Card & Table -->
        <div style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); overflow: hidden;">
            <div style="padding: 22px 26px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 17px; font-weight: 800; color: #0f172a;">
                    <span class="dashicons dashicons-warning" style="color: #dc2626; vertical-align: middle;"></span> Outstanding Balances Directory
                </h3>
                <span style="font-size: 12px; font-weight: 700; color: #dc2626; background: #fef2f2; padding: 4px 10px; border-radius: 6px; border: 1px solid #fecaca;">
                    <?php echo esc_html( $total_agt ); ?> Agencies Overdue
                </span>
            </div>

            <div style="padding: 15px 24px 24px 24px; overflow-x: auto;">
                <table class="ifs-pro-datatable" id="ifsAgentDuesTable" style="width: 100%; border-collapse: collapse; font-size: 13.5px;">
                    <thead>
                        <tr style="background: #f8fafc; text-align: left; border-bottom: 2px solid #e2e8f0;">
                            <th style="padding: 12px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #475569; width: 90px;">Agent ID</th>
                            <th style="padding: 12px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #475569;">Agency Name</th>
                            <th style="padding: 12px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #475569;">Contact Person</th>
                            <th style="padding: 12px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #475569;">Mobile Number</th>
                            <th style="padding: 12px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #475569; text-align: right;">Credit Limit (৳)</th>
                            <th style="padding: 12px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #475569; text-align: right;">Total Due Amount (৳)</th>
                            <th style="padding: 12px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #475569; text-align: right; width: 130px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $dues_list ) : foreach ( $dues_list as $agt ) : 
                            $due_amt = abs( (float) $agt->current_balance );
                        ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 14px;">
                                    <span style="background: #f1f5f9; color: #475569; font-family: ui-monospace, monospace; font-weight: 700; font-size: 11px; padding: 3px 6px; border-radius: 6px;">
                                        #AGT-<?php echo esc_html( $agt->id ); ?>
                                    </span>
                                </td>
                                <td style="padding: 14px; font-weight: 700; color: #0f172a;">
                                    <?php echo esc_html( $agt->company_name ); ?>
                                    <div style="font-size: 11px; color: #64748b; font-weight: normal;"><?php echo esc_html( $agt->email ); ?></div>
                                </td>
                                <td style="padding: 14px;"><?php echo esc_html( $agt->contact_person ); ?></td>
                                <td style="padding: 14px; font-family: ui-monospace, monospace;"><?php echo esc_html( $agt->mobile ); ?></td>
                                <td style="padding: 14px; text-align: right; font-family: ui-monospace, monospace; color: #64748b; font-weight: 600;">
                                    ৳<?php echo number_format( (float) $agt->credit_limit, 2 ); ?>
                                </td>
                                <td style="padding: 14px; text-align: right; font-family: ui-monospace, monospace; font-weight: 800; color: #dc2626; font-size: 14.5px;">
                                    ৳<?php echo number_format( $due_amt, 2 ); ?>
                                </td>
                                <td style="padding: 14px; text-align: right;">
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=b2b_agents&sub=ledger&id=' . $agt->id ) ); ?>" 
                                       style="background: #eff6ff; color: #2563eb; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; gap: 4px; border: 1px solid #dbeafe;">
                                        <span class="dashicons dashicons-money-alt" style="font-size: 14px; width: 14px; height: 14px;"></span> Collect / Ledger
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 50px 20px;">
                                    <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                        <span class="dashicons dashicons-yes-alt" style="font-size: 42px; width: 42px; height: 42px; color: #16a34a;"></span>
                                        <h4 style="margin: 0; font-size: 16px; color: #0f172a;">Zero Outstanding Dues</h4>
                                        <p style="margin: 0; font-size: 13px; color: #64748b;">All B2B sub-agent accounts are fully balanced or maintaining positive credit deposits.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Initialization & Print Styles -->
    <script>
        jQuery(document).ready(function($) {
            if ($.fn.DataTable) {
                $('#ifsAgentDuesTable').DataTable({
                    "pageLength": 15,
                    "ordering": true,
                    "order": [[ 5, "desc" ]], // Sort by highest due amount descending
                    "info": true,
                    "searching": true,
                    "language": {
                        "search": "_INPUT_",
                        "searchPlaceholder": "Search Agency, Mobile, Contact...",
                        "lengthMenu": "Show _MENU_ entries"
                    }
                });
            }
        });
    </script>

    <style>
        @media print {
            #adminmenuback, #adminmenuwrap, #wpadminbar, #wpfooter, .ifs-report-header button {
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