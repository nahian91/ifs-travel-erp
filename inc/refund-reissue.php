<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_refund_reissue_tab() {
    global $wpdb;
    $table_refunds = $wpdb->prefix . 'iterp_refund_reissue';
    $table_tickets = $wpdb->prefix . 'iterp_tickets';

    $message = '';

    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_refund_submit'] ) ) {
        check_admin_referer( 'ifs_refund_action', 'ifs_refund_nonce' );

        $pnr             = strtoupper( sanitize_text_field( $_POST['pnr'] ) );
        $ticket_no       = sanitize_text_field( $_POST['ticket_no'] );
        $type            = sanitize_text_field( $_POST['process_type'] );
        $penalty         = floatval( $_POST['airline_penalty'] );
        $service_charge  = floatval( $_POST['service_charge'] );
        $refund_total    = floatval( $_POST['refund_amount'] );
        $remarks         = sanitize_textarea_field( $_POST['remarks'] );

        $ticket = $wpdb->get_row( $wpdb->prepare( "SELECT id, ticket_no FROM $table_tickets WHERE pnr = %s", $pnr ) );

        $wpdb->insert(
            $table_refunds,
            array(
                'type'                  => $type,
                'ticket_id'             => $ticket ? $ticket->id : 0,
                'pnr'                   => $pnr,
                'ticket_no'             => $ticket ? $ticket->ticket_no : $ticket_no,
                'airline_penalty'       => $penalty,
                'agency_service_charge' => $service_charge,
                'refund_amount'         => $refund_total,
                'status'                => 'Processed',
                'remarks'               => $remarks,
                'processed_by'          => get_current_user_id(),
                'created_at'            => current_time( 'mysql' )
            ),
            array( '%s', '%d', '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%d', '%s' )
        );

        if ( $ticket ) {
            $wpdb->update( $table_tickets, array( 'status' => $type ), array( 'id' => $ticket->id ) );
        }

        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            ifs_terp_log_activity( "Processed $type for PNR: $pnr" );
        }

        $message = '<div class="notice notice-success is-dismissible"><p>Ticket ' . esc_html( $type ) . ' processed successfully.</p></div>';
    }

    $records = $wpdb->get_results( "SELECT * FROM $table_refunds ORDER BY id DESC" );
    ?>
    <div class="ifs-terp-module-wrapper" style="padding: 20px; max-width: 1200px;">
        <h2 style="margin: 0 0 20px 0; font-size: 22px; font-weight: 700; color: #0f172a;">Ticket Refund, Reissue & Void Desk</h2>

        <?php echo $message; ?>

        <div style="background: #fff; padding: 25px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 25px;">
            <h3 style="margin-top: 0; color: #003376; font-size: 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">Process Cancellation / Reissue Calculation</h3>
            <form method="post" action="">
                <?php wp_nonce_field( 'ifs_refund_action', 'ifs_refund_nonce' ); ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px;">
                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Operation Type *</label>
                        <select name="process_type" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                            <option value="Refund">Refund Ticket</option>
                            <option value="Reissue">Date Change / Reissue</option>
                            <option value="Void">Void (Same Day)</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">PNR Number *</label>
                        <input type="text" name="pnr" required placeholder="e.g. 7X9K21" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px; text-transform:uppercase;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Ticket Number</label>
                        <input type="text" name="ticket_no" placeholder="077-1234567890" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Airline Penalty Charge (৳)</label>
                        <input type="number" step="0.01" name="airline_penalty" placeholder="0.00" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Agency Service Fee (৳)</label>
                        <input type="number" step="0.01" name="service_charge" placeholder="0.00" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Net Refund to Client (৳) *</label>
                        <input type="number" step="0.01" name="refund_amount" required placeholder="0.00" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px; font-weight:bold;">
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Reason & Remarks</label>
                        <textarea name="remarks" rows="2" placeholder="Passenger cancellation reason, fee adjustments..." style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;"></textarea>
                    </div>
                </div>
                <div style="margin-top: 15px; text-align: right;">
                    <button type="submit" name="ifs_refund_submit" style="background: #003376; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; font-weight: 600; cursor: pointer;">Execute Action</button>
                </div>
            </form>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
            <table class="arms-pricing-table" id="ifsRefundTable" style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr style="background:#f8fafc; border-bottom: 2px solid #e2e8f0; text-align: left;">
                        <th style="padding: 10px;">Date</th>
                        <th style="padding: 10px;">Type</th>
                        <th style="padding: 10px;">PNR</th>
                        <th style="padding: 10px;">Ticket No</th>
                        <th style="padding: 10px; text-align: right;">Penalty</th>
                        <th style="padding: 10px; text-align: right;">Refund Paid (৳)</th>
                        <th style="padding: 10px;">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $records ) : foreach ( $records as $rec ) : ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 10px; font-size: 13px;"><?php echo date( 'd M Y', strtotime( $rec->created_at ) ); ?></td>
                            <td style="padding: 10px;"><span style="background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;"><?php echo esc_html( $rec->type ); ?></span></td>
                            <td style="padding: 10px; font-family: monospace; font-weight: 700; color: #003376;"><?php echo esc_html( $rec->pnr ); ?></td>
                            <td style="padding: 10px; font-family: monospace;"><?php echo esc_html( $rec->ticket_no ?: '-' ); ?></td>
                            <td style="padding: 10px; text-align: right; color: #dc2626;">৳<?php echo number_format( $rec->airline_penalty, 2 ); ?></td>
                            <td style="padding: 10px; text-align: right; font-weight: 800; color: #166534;">৳<?php echo number_format( $rec->refund_amount, 2 ); ?></td>
                            <td style="padding: 10px; font-size: 12px; color: #64748b;"><?php echo esc_html( $rec->remarks ?: '-' ); ?></td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="7" style="text-align: center; padding: 20px;">No refund or reissue records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        jQuery(document).ready(function($) {
            if ($.fn.DataTable) {
                $('#ifsRefundTable').DataTable({ "pageLength": 15, "order": [[ 0, "desc" ]] });
            }
        });
    </script>
    <?php
}