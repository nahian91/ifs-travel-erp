<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_ticket_list_page() {
    global $wpdb;
    $table_tickets   = $wpdb->prefix . 'iterp_tickets';
    $table_customers = $wpdb->prefix . 'iterp_customers';

    if ( isset( $_GET['msg'] ) && $_GET['msg'] == 'deleted' ) {
        echo '<div class="notice notice-success is-dismissible"><p>Ticket record deleted successfully.</p></div>';
    }

    $query = "
        SELECT t.*, c.full_name AS customer_name, c.mobile AS customer_mobile
        FROM $table_tickets t
        LEFT JOIN $table_customers c ON t.customer_id = c.id
        ORDER BY t.id DESC
    ";
    $tickets = $wpdb->get_results( $query );
    ?>
    <div class="arms-card-panel" style="margin-top: 20px;">
        <div class="arms-p-header" style="padding: 15px 20px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 18px; margin: 0;">Issued Tickets Ledger</h2>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=ticketing&sub=add' ) ); ?>" style="background:#003376; color:#fff; padding:8px 14px; border-radius:4px; text-decoration:none; font-weight:600;">
                <span class="dashicons dashicons-plus" style="vertical-align:middle;"></span> Issue New Ticket
            </a>
        </div>

        <div class="arms-table-responsive">
            <table class="arms-pricing-table" id="ifsTicketTable" style="width:100%;">
                <thead>
                    <tr>
                        <th>TKT ID</th>
                        <th>Passenger</th>
                        <th>PNR</th>
                        <th>Ticket No</th>
                        <th>Airline / Sector</th>
                        <th style="text-align: right;">Buy (৳)</th>
                        <th style="text-align: right;">Sell (৳)</th>
                        <th style="text-align: right;">Profit (৳)</th>
                        <th style="text-align: center;">Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $tickets ) : foreach ( $tickets as $row ) : 
                        $status_bg = '#f1f5f9'; $status_color = '#475569';
                        if ( strtolower($row->status) === 'issued' ) { $status_bg = '#dcfce7'; $status_color = '#166534'; }
                        elseif ( strtolower($row->status) === 'refunded' ) { $status_bg = '#ffedd5'; $status_color = '#9a3412'; }
                        elseif ( strtolower($row->status) === 'void' ) { $status_bg = '#fee2e2'; $status_color = '#991b1b'; }
                    ?>
                        <tr>
                            <td><strong>#TKT-<?php echo esc_html( $row->id ); ?></strong></td>
                            <td style="font-weight: 600; color: #0f172a;">
                                <?php echo esc_html( $row->customer_name ?: 'Guest / Direct' ); ?>
                                <div style="font-size: 11px; color: #64748b; font-weight: normal;"><?php echo esc_html( $row->customer_mobile ); ?></div>
                            </td>
                            <td><span style="font-family: monospace; font-weight: 700; color: #0369a1; background: #e0f2fe; padding: 2px 6px; border-radius: 4px;"><?php echo esc_html( $row->pnr ); ?></span></td>
                            <td style="font-family: monospace;"><?php echo esc_html( $row->ticket_no ); ?></td>
                            <td>
                                <strong><?php echo esc_html( $row->airline ); ?></strong>
                                <div style="font-size: 11px; color: #64748b;"><?php echo esc_html( $row->sector ); ?> (<?php echo esc_html( $row->cabin_class ); ?>)</div>
                            </td>
                            <td style="text-align: right; color: #64748b;">৳<?php echo number_format( $row->buy_price, 2 ); ?></td>
                            <td style="text-align: right; font-weight: 600; color: #0f172a;">৳<?php echo number_format( $row->sell_price, 2 ); ?></td>
                            <td style="text-align: right; font-weight: 700; color: <?php echo ($row->profit >= 0) ? '#166534' : '#dc2626'; ?>;">
                                ৳<?php echo number_format( $row->profit, 2 ); ?>
                            </td>
                            <td style="text-align: center;">
                                <span style="background: <?php echo $status_bg; ?>; color: <?php echo $status_color; ?>; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                    <?php echo esc_html( $row->status ); ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 5px; justify-content: flex-end;">
                                    <a href="<?php echo admin_url( 'admin.php?page=ifs_travel_erp&tab=ticketing&sub=view&id=' . $row->id ); ?>" style="background:#f1f5f9; padding:4px 8px; border-radius:4px; text-decoration:none; color:#334155;" title="View">
                                        <span class="dashicons dashicons-visibility" style="font-size:14px; vertical-align:middle;"></span>
                                    </a>
                                    <a href="<?php echo admin_url( 'admin.php?page=ifs_travel_erp&tab=ticketing&sub=edit&id=' . $row->id ); ?>" style="background:#f1f5f9; padding:4px 8px; border-radius:4px; text-decoration:none; color:#2563eb;" title="Edit">
                                        <span class="dashicons dashicons-edit" style="font-size:14px; vertical-align:middle;"></span>
                                    </a>
                                    <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=ticketing&sub=delete&id=' . $row->id ), 'delete_ticket_' . $row->id ); ?>" style="background:#fef2f2; padding:4px 8px; border-radius:4px; text-decoration:none; color:#dc2626;" onclick="return confirm('Are you sure you want to delete this ticket record?');" title="Delete">
                                        <span class="dashicons dashicons-trash" style="font-size:14px; vertical-align:middle;"></span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="10" style="text-align: center; padding: 20px;">No ticket records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        jQuery(document).ready(function($) {
            $('#ifsTicketTable').DataTable({
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