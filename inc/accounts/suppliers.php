<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_suppliers_tab() {
    global $wpdb;
    $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
    $table_ledger    = $wpdb->prefix . 'iterp_supplier_ledger';

    $message = '';

    // 1. Add New Supplier / Vendor
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_add_supplier_submit'] ) ) {
        check_admin_referer( 'ifs_add_supplier_action', 'ifs_add_supplier_nonce' );

        $wpdb->insert(
            $table_suppliers,
            array(
                'supplier_name'   => sanitize_text_field( $_POST['supplier_name'] ),
                'supplier_type'   => sanitize_text_field( $_POST['supplier_type'] ),
                'contact_person'  => sanitize_text_field( $_POST['contact_person'] ),
                'phone'           => sanitize_text_field( $_POST['phone'] ),
                'email'           => sanitize_email( $_POST['email'] ),
                'current_balance' => floatval( $_POST['initial_balance'] ),
                'status'          => sanitize_text_field( $_POST['status'] ),
                'created_at'      => current_time( 'mysql' )
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s' )
        );

        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            ifs_terp_log_activity( "Added Supplier: " . sanitize_text_field( $_POST['supplier_name'] ) );
        }

        $message = '<div class="notice notice-success is-dismissible"><p>New supplier registered successfully.</p></div>';
    }

    // 2. Post Balance Deposit / Top-up
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_supplier_payment_submit'] ) ) {
        check_admin_referer( 'ifs_supp_pay_action', 'ifs_supp_pay_nonce' );

        $supp_id = intval( $_POST['supplier_id'] );
        $amount  = floatval( $_POST['amount'] );
        $note    = sanitize_textarea_field( $_POST['note'] );

        $current_bal = (float) $wpdb->get_var( $wpdb->prepare( "SELECT current_balance FROM $table_suppliers WHERE id = %d", $supp_id ) );
        $new_bal = $current_bal + $amount;

        $wpdb->update( $table_suppliers, array( 'current_balance' => $new_bal ), array( 'id' => $supp_id ) );

        $wpdb->insert(
            $table_ledger,
            array(
                'supplier_id'    => $supp_id,
                'reference_type' => 'Bank Deposit / Top-up',
                'credit'         => $amount,
                'balance_after'  => $new_bal,
                'note'           => $note,
                'created_at'     => current_time( 'mysql' )
            ),
            array( '%d', '%s', '%f', '%f', '%s', '%s' )
        );

        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            ifs_terp_log_activity( "Deposited ৳$amount to Supplier ID: $supp_id" );
        }

        $message = '<div class="notice notice-success is-dismissible"><p>Supplier deposit recorded and portal balance updated.</p></div>';
    }

    $suppliers = $wpdb->get_results( "SELECT * FROM $table_suppliers ORDER BY id DESC" );
    ?>
    <div class="ifs-terp-module-wrapper" style="padding: 20px; max-width: 1200px;">
        <h2 style="margin: 0 0 20px 0; font-size: 22px; font-weight: 700; color: #0f172a;">Suppliers & GDS Consortia Ledger</h2>

        <?php echo $message; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-bottom: 25px;">
            <!-- Register Form -->
            <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <h3 style="margin-top:0; font-size: 15px; color: #003376;">Add New Supplier / Vendor</h3>
                <form method="post" action="">
                    <?php wp_nonce_field( 'ifs_add_supplier_action', 'ifs_add_supplier_nonce' ); ?>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Vendor Name *</label>
                            <input type="text" name="supplier_name" required placeholder="e.g. FlyHub / ShareTrip" style="width:100%; padding:6px; border:1px solid #cbd5e1; border-radius:4px;">
                        </div>
                        <div>
                            <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Type</label>
                            <select name="supplier_type" style="width:100%; padding:6px; border:1px solid #cbd5e1; border-radius:4px;">
                                <option value="GDS / IATA">GDS / IATA Consortia</option>
                                <option value="B2B Portal">B2B Air Ticket Portal</option>
                                <option value="Visa Vendor">Visa Processing Vendor</option>
                                <option value="Hotel Supplier">Hotel Wholesaler</option>
                            </select>
                        </div>
                        <div>
                            <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Contact Phone</label>
                            <input type="text" name="phone" placeholder="017XXXXXXXX" style="width:100%; padding:6px; border:1px solid #cbd5e1; border-radius:4px;">
                        </div>
                        <div>
                            <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Opening Balance (৳)</label>
                            <input type="number" step="0.01" name="initial_balance" value="0.00" style="width:100%; padding:6px; border:1px solid #cbd5e1; border-radius:4px;">
                        </div>
                        <input type="hidden" name="contact_person" value="">
                        <input type="hidden" name="email" value="">
                        <input type="hidden" name="status" value="Active">
                    </div>
                    <div style="margin-top: 15px; text-align: right;">
                        <button type="submit" name="ifs_add_supplier_submit" style="background: #003376; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; font-weight: 600; cursor: pointer;">Register Supplier</button>
                    </div>
                </form>
            </div>

            <!-- Top-Up Form -->
            <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <h3 style="margin-top:0; font-size: 15px; color: #003376;">Post Portal Deposit / Balance Top-Up</h3>
                <form method="post" action="">
                    <?php wp_nonce_field( 'ifs_supp_pay_action', 'ifs_supp_pay_nonce' ); ?>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div style="grid-column: 1 / -1;">
                            <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Select Supplier *</label>
                            <select name="supplier_id" required style="width:100%; padding:6px; border:1px solid #cbd5e1; border-radius:4px;">
                                <?php foreach ( $suppliers as $s ) : ?>
                                    <option value="<?php echo $s->id; ?>"><?php echo esc_html( $s->supplier_name ); ?> (Bal: ৳<?php echo number_format($s->current_balance, 2); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Deposit Amount (৳) *</label>
                            <input type="number" step="0.01" name="amount" required placeholder="0.00" style="width:100%; padding:6px; border:1px solid #cbd5e1; border-radius:4px; font-weight:bold;">
                        </div>
                        <div>
                            <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Bank Ref / Note</label>
                            <input type="text" name="note" placeholder="e.g. City Bank Transfer #8821" style="width:100%; padding:6px; border:1px solid #cbd5e1; border-radius:4px;">
                        </div>
                    </div>
                    <div style="margin-top: 15px; text-align: right;">
                        <button type="submit" name="ifs_supplier_payment_submit" style="background: #166534; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; font-weight: 600; cursor: pointer;">Post Balance</button>
                    </div>
                </form>
            </div>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
            <table class="arms-pricing-table" id="ifsSuppliersTable" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc; text-align: left; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 10px;">ID</th>
                        <th style="padding: 10px;">Supplier / Portal Name</th>
                        <th style="padding: 10px;">Category</th>
                        <th style="padding: 10px;">Contact Phone</th>
                        <th style="padding: 10px; text-align: right;">Available Portal Balance (৳)</th>
                        <th style="padding: 10px; text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $suppliers ) : foreach ( $suppliers as $row ) : ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 10px;"><strong>#SUP-<?php echo esc_html( $row->id ); ?></strong></td>
                            <td style="padding: 10px; font-weight: 700; color: #0f172a;"><?php echo esc_html( $row->supplier_name ); ?></td>
                            <td style="padding: 10px;"><span style="background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;"><?php echo esc_html( $row->supplier_type ); ?></span></td>
                            <td style="padding: 10px;"><?php echo esc_html( $row->phone ?: '-' ); ?></td>
                            <td style="padding: 10px; text-align: right; font-weight: 800; color: #166534; font-size: 15px;">৳<?php echo number_format( $row->current_balance, 2 ); ?></td>
                            <td style="padding: 10px; text-align: center;"><span style="background: #dcfce7; color: #166534; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 700;"><?php echo esc_html( $row->status ); ?></span></td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="6" style="text-align: center; padding: 20px;">No suppliers configured yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        jQuery(document).ready(function($) {
            if ($.fn.DataTable) {
                $('#ifsSuppliersTable').DataTable({ "pageLength": 15, "order": [[ 0, "desc" ]] });
            }
        });
    </script>
    <?php
}