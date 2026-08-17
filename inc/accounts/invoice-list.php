<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_invoice_list_page() {
    global $wpdb;
    $table_invoices  = $wpdb->prefix . 'iterp_invoices';
    $table_customers = $wpdb->prefix . 'iterp_customers';
    $table_agents    = $wpdb->prefix . 'iterp_agents';

    $invoices = $wpdb->get_results( "SELECT * FROM $table_invoices ORDER BY id DESC" );
    ?>
    <div class="arms-card-panel" style="margin-top: 20px;">
        <div class="arms-p-header" style="padding: 15px 20px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 18px; margin: 0;">Sales Invoices & Billing Register</h2>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=accounts&sub=create_invoice' ) ); ?>" style="background:#003376; color:#fff; padding:8px 14px; border-radius:4px; text-decoration:none; font-weight:600;">
                <span class="dashicons dashicons-plus" style="vertical-align:middle;"></span> Create New Invoice
            </a>
        </div>

        <div class="arms-table-responsive">
            <table class="arms-pricing-table" id="ifsInvoiceTable" style="width:100%;">
                <thead>
                    <tr>
                        <th>Invoice No</th>
                        <th>Client Type</th>
                        <th>Client Details</th>
                        <th style="text-align: right;">Net Total (৳)</th>
                        <th style="text-align: right;">Paid (৳)</th>
                        <th style="text-align: right;">Due (৳)</th>
                        <th style="text-align: center;">Status</th>
                        <th>Date</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $invoices ) : foreach ( $invoices as $inv ) : 
                        $client_name = 'Unknown Client';
                        if ( $inv->client_type === 'Customer' ) {
                            $client_name = $wpdb->get_var( $wpdb->prepare( "SELECT full_name FROM $table_customers WHERE id = %d", $inv->client_id ) );
                        } elseif ( $inv->client_type === 'Agent' ) {
                            $client_name = $wpdb->get_var( $wpdb->prepare( "SELECT company_name FROM $table_agents WHERE id = %d", $inv->client_id ) );
                        }

                        $status_bg = '#fee2e2'; $status_color = '#991b1b'; // Unpaid
                        if ( strtolower($inv->payment_status) === 'paid' ) { $status_bg = '#dcfce7'; $status_color = '#166534'; }
                        elseif ( strtolower($inv->payment_status) === 'partial' ) { $status_bg = '#fef3c7'; $status_color = '#b45309'; }
                    ?>
                        <tr>
                            <td><strong style="font-family: monospace; font-size: 13px; color: #003376;"><?php echo esc_html( $inv->invoice_no ); ?></strong></td>
                            <td><span style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 11px;"><?php echo esc_html( $inv->client_type ); ?></span></td>
                            <td style="font-weight: 600; color: #0f172a;"><?php echo esc_html( $client_name ?: 'N/A' ); ?></td>
                            <td style="text-align: right; font-weight: 700; color: #0f172a;">৳<?php echo number_format( $inv->net_total, 2 ); ?></td>
                            <td style="text-align: right; color: #166534; font-weight: 600;">৳<?php echo number_format( $inv->paid_amount, 2 ); ?></td>
                            <td style="text-align: right; color: <?php echo ($inv->due_amount > 0) ? '#dc2626' : '#64748b'; ?>; font-weight: 700;">
                                ৳<?php echo number_format( $inv->due_amount, 2 ); ?>
                            </td>
                            <td style="text-align: center;">
                                <span style="background: <?php echo $status_bg; ?>; color: <?php echo $status_color; ?>; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 700;">
                                    <?php echo esc_html( $inv->payment_status ); ?>
                                </span>
                            </td>
                            <td><?php echo date( 'd M Y', strtotime( $inv->created_at ) ); ?></td>
                            <td style="text-align: right;">
                                <a href="<?php echo admin_url( 'admin.php?page=ifs_travel_erp&tab=accounts&sub=view_invoice&id=' . $inv->id ); ?>" style="background:#003376; color:#fff; padding:4px 10px; border-radius:4px; text-decoration:none; font-size:12px; font-weight:600;">
                                    View / Print
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="9" style="text-align: center; padding: 20px;">No invoices generated yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        jQuery(document).ready(function($) {
            $('#ifsInvoiceTable').DataTable({
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