<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_settings_audit_logs_page() {
    global $wpdb;
    $table_audit = $wpdb->prefix . 'iterp_audit_logs';

    $logs = $wpdb->get_results( "SELECT * FROM $table_audit ORDER BY id DESC LIMIT 200" );
    ?>
    <div class="arms-card-panel" style="margin-top: 20px;">
        <div class="arms-p-header" style="padding: 15px 20px;">
            <h2 style="font-size: 18px; margin: 0;">Security Audit & Action Trail (Recent 200 Actions)</h2>
        </div>

        <div class="arms-table-responsive">
            <table class="arms-pricing-table" id="ifsAuditLogsTable" style="width:100%;">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User ID</th>
                        <th>Role</th>
                        <th>Action Performed</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $logs ) : foreach ( $logs as $log ) : ?>
                        <tr>
                            <td><?php echo date( 'd M Y, h:i:s A', strtotime( $log->timestamp ) ); ?></td>
                            <td><strong>#UID-<?php echo esc_html( $log->user_id ); ?></strong></td>
                            <td><span style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 11px;"><?php echo esc_html( $log->user_role ); ?></span></td>
                            <td style="font-weight: 600; color: #0f172a;"><?php echo esc_html( $log->action_performed ); ?></td>
                            <td style="font-family: monospace; color: #64748b;"><?php echo esc_html( $log->ip_address ); ?></td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="5" style="text-align: center; padding: 20px;">No system activities logged yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        jQuery(document).ready(function($) {
            $('#ifsAuditLogsTable').DataTable({
                "pageLength": 25,
                "ordering": true,
                "order": [[ 0, "desc" ]],
                "info": true,
                "searching": true
            });
        });
    </script>
    <?php
}