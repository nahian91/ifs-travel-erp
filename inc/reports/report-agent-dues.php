<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_report_agent_dues_page() {
    global $wpdb;
    $table_agents = $wpdb->prefix . 'iterp_agents';

    // Negative balance indicates agent owes agency money (Due)
    $dues_list = $wpdb->get_results( "SELECT * FROM $table_agents WHERE current_balance < 0 ORDER BY current_balance ASC" );
    $total_due = $wpdb->get_var( "SELECT COALESCE(SUM(current_balance), 0) FROM $table_agents WHERE current_balance < 0" );
    ?>
    <div class="arms-card-panel" style="margin-top: 20px;">
        <div class="arms-p-header" style="padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 18px; margin: 0;">Sub-Agent Due & Outstanding Ledger</h2>
            <div style="font-weight: 700; color: #dc2626; font-size: 16px;">
                Total Outstanding: ৳<?php echo number_format( abs(floatval($total_due)), 2 ); ?>
            </div>
        </div>

        <div class="arms-table-responsive">
            <table class="arms-pricing-table" id="ifsAgentDuesTable" style="width:100%;">
                <thead>
                    <tr>
                        <th>Agent ID</th>
                        <th>Agency Name</th>
                        <th>Contact Person</th>
                        <th>Mobile</th>
                        <th style="text-align: right;">Credit Limit (৳)</th>
                        <th style="text-align: right;">Total Due Amount (৳)</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $dues_list ) : foreach ( $dues_list as $agt ) : ?>
                        <tr>
                            <td><strong>#AGT-<?php echo esc_html( $agt->id ); ?></strong></td>
                            <td style="font-weight: 700; color: #0f172a;"><?php echo esc_html( $agt->company_name ); ?></td>
                            <td><?php echo esc_html( $agt->contact_person ); ?></td>
                            <td><?php echo esc_html( $agt->mobile ); ?></td>
                            <td style="text-align: right; color: #64748b;">৳<?php echo number_format( $agt->credit_limit, 2 ); ?></td>
                            <td style="text-align: right; font-weight: 800; color: #dc2626;">
                                ৳<?php echo number_format( abs($agt->current_balance), 2 ); ?>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?php echo admin_url( 'admin.php?page=ifs_travel_erp&tab=b2b_agents&sub=ledger&id=' . $agt->id ); ?>" style="background: #eff6ff; color: #2563eb; padding: 4px 10px; border-radius: 4px; text-decoration: none; font-weight: 600; font-size: 12px;">
                                    Collect / Ledger
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="7" style="text-align: center; padding: 20px; color: #166534; font-weight: 600;">No outstanding dues from any B2B sub-agent.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        jQuery(document).ready(function($) {
            $('#ifsAgentDuesTable').DataTable({
                "pageLength": 15,
                "ordering": true,
                "info": true,
                "searching": true
            });
        });
    </script>
    <?php
}