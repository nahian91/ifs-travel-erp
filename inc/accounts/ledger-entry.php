<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_ledger_entry_page() {
    global $wpdb;
    $table_ledger = $wpdb->prefix . 'iterp_ledger';
    $message = '';

    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_ledger_submit'] ) ) {
        check_admin_referer( 'ifs_ledger_entry_action', 'ifs_ledger_nonce' );

        $wpdb->insert(
            $table_ledger,
            array(
                'transaction_type' => sanitize_text_field( $_POST['transaction_type'] ),
                'category'         => sanitize_text_field( $_POST['category'] ),
                'amount'           => floatval( $_POST['amount'] ),
                'payment_method'   => sanitize_text_field( $_POST['payment_method'] ),
                'reference_no'     => sanitize_text_field( $_POST['reference_no'] ),
                'description'      => sanitize_textarea_field( $_POST['description'] ),
                'transaction_date' => sanitize_text_field( $_POST['transaction_date'] ) . ' ' . date('H:i:s'),
                'logged_by'        => get_current_user_id()
            )
        );
        $message = '<div class="notice notice-success is-dismissible"><p>Ledger entry recorded successfully.</p></div>';
    }

    $records = $wpdb->get_results( "SELECT * FROM $table_ledger ORDER BY id DESC" );
    ?>

    <?php echo $message; ?>

    <div class="arms-card-panel" style="margin-top: 20px;">
        <div class="arms-p-header" style="padding: 15px 20px; margin-bottom: 15px;">
            <h2 style="font-size: 18px; margin: 0;">Daily Office Expenses & Cash Register</h2>
        </div>

        <!-- Post Entry Form -->
        <div style="background: #fff; padding: 25px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 30px;">
            <h3 style="margin-top:0; color:#003376; font-size:15px; border-bottom:1px solid #e2e8f0; padding-bottom:8px;">
                <span class="dashicons dashicons-money-alt"></span> Post New Income / Expense Voucher
            </h3>

            <form method="post" action="">
                <?php wp_nonce_field( 'ifs_ledger_entry_action', 'ifs_ledger_nonce' ); ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Entry Type *</label>
                        <select name="transaction_type" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                            <option value="Expense">Expense (- Outflow)</option>
                            <option value="Income">Income (+ Inflow)</option>
                        </select>
                    </div>

                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Category *</label>
                        <input type="text" name="category" required placeholder="e.g. Office Rent / Entertainment / Utility" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>

                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Amount (৳) *</label>
                        <input type="number" step="0.01" name="amount" required placeholder="0.00" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>

                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Payment Method</label>
                        <select name="payment_method" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                            <option value="Cash">Cash Account</option>
                            <option value="Bank">Bank Account</option>
                            <option value="bKash / Nagad">Mobile Banking</option>
                        </select>
                    </div>

                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Date *</label>
                        <input type="date" name="transaction_date" required value="<?php echo date('Y-m-d'); ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>

                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Voucher / Receipt No</label>
                        <input type="text" name="reference_no" placeholder="e.g. VOUCH-019" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>

                    <div style="grid-column: 1 / -1;">
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Narration / Description</label>
                        <textarea name="description" rows="2" placeholder="Details about this expense..." style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;"></textarea>
                    </div>
                </div>

                <div style="margin-top: 15px; text-align: right;">
                    <button type="submit" name="ifs_ledger_submit" style="background: #003376; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; font-weight: 600; cursor: pointer;">
                        Record Ledger Entry
                    </button>
                </div>
            </form>
        </div>

        <!-- Ledger Table -->
        <div class="arms-table-responsive">
            <table class="arms-pricing-table" id="ifsLedgerTable" style="width:100%;">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Method</th>
                        <th>Voucher Ref</th>
                        <th style="text-align: right;">Amount (৳)</th>
                        <th>Narration</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $records ) : foreach ( $records as $row ) : ?>
                        <tr>
                            <td><?php echo date( 'd M Y', strtotime( $row->transaction_date ) ); ?></td>
                            <td>
                                <span style="background: <?php echo ($row->transaction_type === 'Income') ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo ($row->transaction_type === 'Income') ? '#166534' : '#991b1b'; ?>; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;">
                                    <?php echo esc_html( $row->transaction_type ); ?>
                                </span>
                            </td>
                            <td style="font-weight: 600; color: #0f172a;"><?php echo esc_html( $row->category ); ?></td>
                            <td><?php echo esc_html( $row->payment_method ); ?></td>
                            <td style="font-family: monospace;"><?php echo esc_html( $row->reference_no ?: '-' ); ?></td>
                            <td style="text-align: right; font-weight: 700; color: <?php echo ($row->transaction_type === 'Income') ? '#166534' : '#dc2626'; ?>;">
                                <?php echo ($row->transaction_type === 'Income') ? '+' : '-'; ?>৳<?php echo number_format( $row->amount, 2 ); ?>
                            </td>
                            <td style="font-size: 12px; color: #64748b;"><?php echo esc_html( $row->description ?: '-' ); ?></td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="7" style="text-align: center; padding: 20px;">No ledger entries found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        jQuery(document).ready(function($) {
            $('#ifsLedgerTable').DataTable({
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