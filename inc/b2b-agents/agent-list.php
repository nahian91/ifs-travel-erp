<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_agent_list_page() {
    global $wpdb;
    $table_agents = $wpdb->prefix . 'iterp_agents';

    if ( isset( $_GET['msg'] ) && $_GET['msg'] == 'deleted' ) {
        echo '<div class="notice notice-success is-dismissible"><p>Agent profile removed successfully.</p></div>';
    }

    $agents = $wpdb->get_results( "SELECT * FROM $table_agents ORDER BY id DESC" );
    ?>
    <div class="arms-card-panel" style="margin-top: 20px;">
        <div class="arms-p-header" style="padding: 15px 20px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 18px; margin: 0;">Active B2B Partner Agencies</h2>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=b2b_agents&sub=add' ) ); ?>" style="background:#003376; color:#fff; padding:8px 14px; border-radius:4px; text-decoration:none; font-weight:600;">
                <span class="dashicons dashicons-plus" style="vertical-align:middle;"></span> Add Sub-Agent
            </a>
        </div>

        <div class="arms-table-responsive">
            <table class="arms-pricing-table" id="ifsAgentTable" style="width:100%;">
                <thead>
                    <tr>
                        <th>Agent ID</th>
                        <th>Agency & Contact</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th style="text-align: right;">Credit Limit (৳)</th>
                        <th style="text-align: right;">Current Balance (৳)</th>
                        <th style="text-align: center;">Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $agents ) : foreach ( $agents as $row ) : 
                        $balance_color = ($row->current_balance < 0) ? '#dc2626' : '#166534';
                        $status_bg = ($row->status === 'Active') ? '#dcfce7' : '#fee2e2';
                        $status_color = ($row->status === 'Active') ? '#166534' : '#991b1b';
                    ?>
                        <tr>
                            <td><strong>#AGT-<?php echo esc_html( $row->id ); ?></strong></td>
                            <td style="font-weight: 600; color: #0f172a;">
                                <?php echo esc_html( $row->company_name ); ?>
                                <div style="font-size: 11px; color: #64748b; font-weight: normal;">Contact: <?php echo esc_html( $row->contact_person ); ?></div>
                            </td>
                            <td><?php echo esc_html( $row->mobile ); ?></td>
                            <td><?php echo esc_html( $row->email ?: '-' ); ?></td>
                            <td style="text-align: right; color: #475569;">৳<?php echo number_format( $row->credit_limit, 2 ); ?></td>
                            <td style="text-align: right; font-weight: 800; color: <?php echo $balance_color; ?>;">
                                ৳<?php echo number_format( $row->current_balance, 2 ); ?>
                            </td>
                            <td style="text-align: center;">
                                <span style="background: <?php echo $status_bg; ?>; color: <?php echo $status_color; ?>; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                    <?php echo esc_html( $row->status ); ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 5px; justify-content: flex-end;">
                                    <a href="<?php echo admin_url( 'admin.php?page=ifs_travel_erp&tab=b2b_agents&sub=ledger&id=' . $row->id ); ?>" style="background:#eff6ff; padding:4px 8px; border-radius:4px; text-decoration:none; color:#2563eb; font-weight:600; font-size:12px;" title="Statement & Ledger">
                                        <span class="dashicons dashicons-media-spreadsheet" style="font-size:14px; vertical-align:middle;"></span> Ledger
                                    </a>
                                    <a href="<?php echo admin_url( 'admin.php?page=ifs_travel_erp&tab=b2b_agents&sub=edit&id=' . $row->id ); ?>" style="background:#f1f5f9; padding:4px 8px; border-radius:4px; text-decoration:none; color:#334155;" title="Edit">
                                        <span class="dashicons dashicons-edit" style="font-size:14px; vertical-align:middle;"></span>
                                    </a>
                                    <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=b2b_agents&sub=delete&id=' . $row->id ), 'delete_agent_' . $row->id ); ?>" style="background:#fef2f2; padding:4px 8px; border-radius:4px; text-decoration:none; color:#dc2626;" onclick="return confirm('Are you sure you want to delete this agent?');" title="Delete">
                                        <span class="dashicons dashicons-trash" style="font-size:14px; vertical-align:middle;"></span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="8" style="text-align: center; padding: 20px;">No B2B Sub-Agents registered yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        jQuery(document).ready(function($) {
            $('#ifsAgentTable').DataTable({
                "pageLength": 15,
                "ordering": true,
                "order": [[ 0, "desc" ]],
                "info": true,
                "searching": true
            });
        });
    </script>
    <?php
}