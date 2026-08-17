<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_agent_ledger_page() {
    global $wpdb;
    $agent_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

    if ( ! $agent_id ) {
        echo '<div class="notice notice-error"><p>Invalid Agent ID.</p></div>';
        return;
    }

    $table_agents  = $wpdb->prefix . 'iterp_agents';
    $table_ledgers = $wpdb->prefix . 'iterp_agent_ledgers';

    $agent = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_agents WHERE id = %d", $agent_id ) );

    if ( ! $agent ) {
        echo '<div class="notice notice-error"><p>Agent not found.</p></div>';
        return;
    }

    $message = '';

    // Handle Manual Deposit / Top-up / Adjustment
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_agent_topup_submit'] ) ) {
        check_admin_referer( 'ifs_agent_topup_action', 'ifs_agent_topup_nonce' );

        $type   = sanitize_text_field( $_POST['trans_type'] ); // 'credit' (Deposit) or 'debit' (Deduction)
        $amount = floatval( $_POST['amount'] );
        $note   = sanitize_textarea_field( $_POST['note'] );

        $debit_val  = ($type === 'debit') ? $amount : 0.00;
        $credit_val = ($type === 'credit') ? $amount : 0.00;

        // Calculate new balance
        $current_bal = $agent->current_balance;
        $new_balance = ($type === 'credit') ? ($current_bal + $amount) : ($current_bal - $amount);

        // Update Agent Profile Balance
        $wpdb->update( $table_agents, array( 'current_balance' => $new_balance ), array( 'id' => $agent_id ) );

        // Insert Ledger Entry
        $wpdb->insert(
            $table_ledgers,
            array(
                'agent_id'        => $agent_id,
                'reference_type'  => 'Manual Adjustment',
                'reference_id'    => 0,
                'debit'           => $debit_val,
                'credit'          => $credit_val,
                'balance_after'   => $new_balance,
                'note'            => $note,
                'created_at'      => current_time( 'mysql' )
            )
        );

        $agent->current_balance = $new_balance;
        $message = '<div class="notice notice-success is-dismissible"><p>Transaction posted and balance updated successfully.</p></div>';
    }

    $ledger_records = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_ledgers WHERE agent_id = %d ORDER BY id DESC", $agent_id ) );
    ?>

    <?php echo $message; ?>

    <!-- Summary Box -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-top: 20px;">
        <div style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #003376; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
            <span style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Agency Name</span>
            <div style="font-size: 18px; font-weight: 700; color: #0f172a; margin-top: 5px;"><?php echo esc_html( $agent->company_name ); ?></div>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #3b82f6; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
            <span style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Credit Limit</span>
            <div style="font-size: 18px; font-weight: 700; color: #1e293b; margin-top: 5px;">৳<?php echo number_format( $agent->credit_limit, 2 ); ?></div>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid <?php echo ($agent->current_balance < 0) ? '#dc2626' : '#166534'; ?>; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
            <span style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Available Balance</span>
            <div style="font-size: 20px; font-weight: 800; color: <?php echo ($agent->current_balance < 0) ? '#dc2626' : '#166534'; ?>; margin-top: 5px;">
                ৳<?php echo number_format( $agent->current_balance, 2 ); ?>
            </div>
        </div>
    </div>

    <!-- Quick Top-Up / Deposit Form -->
    <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; margin-top: 25px;">
        <h3 style="margin-top: 0; color: #003376; font-size: 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">
            <span class="dashicons dashicons-money-alt"></span> Post Balance Deposit / Deduction
        </h3>

        <form method="post" action="">
            <?php wp_nonce_field( 'ifs_agent_topup_action', 'ifs_agent_topup_nonce' ); ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Transaction Type</label>
                    <select name="trans_type" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                        <option value="credit">Deposit (+) Top-Up Balance</option>
                        <option value="debit">Deduction (-) Invoice / Debit</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Amount (৳) *</label>
                    <input type="number" step="0.01" name="amount" required placeholder="0.00" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>
                <div>
                    <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Note / Bank Reference</label>
                    <input type="text" name="note" placeholder="e.g. Bank Transfer Ref: City-1029" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>
                <div style="display: flex; align-items: flex-end;">
                    <button type="submit" name="ifs_agent_topup_submit" style="background: #003376; color: #fff; border: none; padding: 10px 18px; border-radius: 4px; font-weight: 600; cursor: pointer; width: 100%;">
                        Post Entry
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Ledger Table -->
    <div class="arms-card-panel" style="margin-top: 25px;">
        <div class="arms-p-header" style="padding: 15px 20px;">
            <h3 style="font-size: 16px; margin: 0;">Statement / Transaction History</h3>
        </div>

        <div class="arms-table-responsive">
            <table class="arms-pricing-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Reference</th>
                        <th style="text-align: right;">Debit (-)</th>
                        <th style="text-align: right;">Credit (+)</th>
                        <th style="text-align: right;">Balance After</th>
                        <th>Narration / Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $ledger_records ) : foreach ( $ledger_records as $rec ) : ?>
                        <tr>
                            <td><?php echo date( 'd M Y, h:i A', strtotime( $rec->created_at ) ); ?></td>
                            <td><span style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 11px;"><?php echo esc_html( $rec->reference_type ); ?></span></td>
                            <td style="text-align: right; color: <?php echo ($rec->debit > 0) ? '#dc2626' : '#64748b'; ?>; font-weight: 600;">
                                <?php echo ($rec->debit > 0) ? '৳' . number_format( $rec->debit, 2 ) : '-'; ?>
                            </td>
                            <td style="text-align: right; color: <?php echo ($rec->credit > 0) ? '#166534' : '#64748b'; ?>; font-weight: 600;">
                                <?php echo ($rec->credit > 0) ? '৳' . number_format( $rec->credit, 2 ) : '-'; ?>
                            </td>
                            <td style="text-align: right; font-weight: 700; color: #0f172a;">৳<?php echo number_format( $rec->balance_after, 2 ); ?></td>
                            <td style="font-size: 12px; color: #475569;"><?php echo esc_html( $rec->note ?: '-' ); ?></td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="6" style="text-align: center; padding: 20px;">No transaction entries recorded for this agent.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}