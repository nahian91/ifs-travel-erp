<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enterprise Next-Gen B2B Partner Sub-Agent Statement & Ledger Console
 * Features: Financial Summary Badges, Balance Top-Up / Deduction Voucher Engine, CSV Statement Export & Modern DataTables
 */
function ifs_terp_agent_ledger_page() {
    global $wpdb;
    $agent_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

    if ( ! $agent_id ) {
        echo '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> Invalid Sub-Agent Identifier.</div>';
        return;
    }

    $table_agents  = $wpdb->prefix . 'iterp_agents';
    $table_ledgers = $wpdb->prefix . 'iterp_agent_ledgers';

    $agent = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_agents WHERE id = %d", $agent_id ) );

    if ( ! $agent ) {
        echo '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> Sub-agent partner record not found in system database.</div>';
        return;
    }

    // Handle CSV Export
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'export_csv' ) {
        if ( ! current_user_can( 'manage_options' ) && ! ifs_terp_has_access( array( 'accountant', 'admin_manager' ) ) ) {
            wp_die( 'Unauthorized export request.' );
        }

        $agent_filename_slug = sanitize_title( $agent->agency_name ?: 'agent-' . $agent_id );
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=statement-' . $agent_filename_slug . '-' . date( 'Y-m-d' ) . '.csv' );

        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, array( 'Statement Date & Time', 'Reference Type', 'Reference ID', 'Debit (-) BDT', 'Credit (+) BDT', 'Running Balance (BDT)', 'Narration / Remarks' ) );

        $export_ledgers = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_ledgers WHERE agent_id = %d ORDER BY id DESC", $agent_id ) );
        if ( $export_ledgers ) {
            foreach ( $export_ledgers as $rec ) {
                fputcsv( $output, array(
                    date( 'd M Y, h:i A', strtotime( $rec->created_at ) ),
                    $rec->reference_type,
                    $rec->reference_id ? '#' . $rec->reference_id : 'N/A',
                    (float) $rec->debit,
                    (float) $rec->credit,
                    (float) $rec->balance_after,
                    $rec->note
                ) );
            }
        }

        fclose( $output );
        exit;
    }

    $message = '';

    // Handle Manual Deposit / Top-up / Deduction Voucher
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_agent_topup_submit'] ) ) {
        check_admin_referer( 'ifs_agent_topup_action', 'ifs_agent_topup_nonce' );

        $trans_type = sanitize_text_field( $_POST['trans_type'] ?? 'credit' ); // 'credit' (Deposit) or 'debit' (Deduction)
        $amount     = floatval( $_POST['amount'] ?? 0 );
        $note       = sanitize_textarea_field( $_POST['note'] ?? '' );
        $ref_type   = sanitize_text_field( $_POST['reference_type_custom'] ?? 'Manual Adjustment' );

        if ( $amount > 0 ) {
            $debit_val  = ( $trans_type === 'debit' ) ? $amount : 0.00;
            $credit_val = ( $trans_type === 'credit' ) ? $amount : 0.00;

            $current_bal = (float) $agent->current_balance;
            $new_balance = ( $trans_type === 'credit' ) ? ( $current_bal + $amount ) : ( $current_bal - $amount );

            // Atomic Balance Update
            $wpdb->update( $table_agents, array( 'current_balance' => $new_balance ), array( 'id' => $agent_id ), array( '%f' ), array( '%d' ) );

            // Log Transaction in Ledger
            $wpdb->insert(
                $table_ledgers,
                array(
                    'agent_id'       => $agent_id,
                    'reference_type' => $ref_type,
                    'reference_id'   => 0,
                    'debit'          => $debit_val,
                    'credit'         => $credit_val,
                    'balance_after'  => $new_balance,
                    'note'           => $note,
                    'created_at'     => current_time( 'mysql' )
                ),
                array( '%d', '%s', '%d', '%f', '%f', '%f', '%s', '%s' )
            );

            if ( function_exists( 'ifs_terp_log_activity' ) ) {
                $action_label = ( $trans_type === 'credit' ) ? 'Deposit (+)' : 'Deduction (-)';
                ifs_terp_log_activity( "Posted {$action_label} of ৳" . number_format( $amount, 2 ) . " to Agent #AGT-{$agent_id} ({$agent->agency_name})" );
            }

            $agent->current_balance = $new_balance;
            $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Transaction voucher posted and agent balance updated successfully.</div>';
        } else {
            $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> Transaction amount must be greater than zero.</div>';
        }
    }

    $ledger_records = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_ledgers WHERE agent_id = %d ORDER BY id DESC", $agent_id ) );
    $total_debits   = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(debit), 0) FROM $table_ledgers WHERE agent_id = %d", $agent_id ) );
    $total_credits  = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(credit), 0) FROM $table_ledgers WHERE agent_id = %d", $agent_id ) );

    $current_balance = (float) $agent->current_balance;
    $credit_limit    = (float) $agent->credit_limit;
    $is_negative     = ( $current_balance < 0 );

    $base_url   = admin_url( 'admin.php?page=ifs_travel_erp&tab=b2b_agents&sub=ledger&id=' . $agent_id );
    $export_url = add_query_arg( array( 'action' => 'export_csv' ), $base_url );
    $back_url   = admin_url( 'admin.php?page=ifs_travel_erp&tab=b2b_agents' );
    ?>

    <div class="wrap ifs-agent-ledger-workspace">
        <?php echo $message; ?>

        <!-- Ledger Header Card -->
        <div class="ifs-ledger-header-card">
            <div class="header-title-group">
                <div class="header-badge-row">
                    <span class="ifs-ledger-badge"><span class="dashicons dashicons-media-spreadsheet"></span> Statement Ledger</span>
                    <span class="ifs-id-badge font-mono">#AGT-<?php echo str_pad( (string) $agent->id, 5, '0', STR_PAD_LEFT ); ?></span>
                </div>
                <h2><?php echo esc_html( $agent->agency_name ); ?></h2>
                <div class="header-contact-meta">
                    <span><span class="dashicons dashicons-businessman"></span> Contact: <strong><?php echo esc_html( $agent->contact_person ); ?></strong></span>
                    <span class="meta-dot"></span>
                    <span><span class="dashicons dashicons-phone"></span> <?php echo esc_html( $agent->mobile ); ?></span>
                    <?php if ( ! empty( $agent->email ) ) : ?>
                        <span class="meta-dot"></span>
                        <span><span class="dashicons dashicons-email"></span> <?php echo esc_html( $agent->email ); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="header-actions">
                <a href="<?php echo esc_url( $back_url ); ?>" class="ifs-btn-back">
                    <span class="dashicons dashicons-arrow-left-alt"></span> Back to Directory
                </a>
                <a href="<?php echo esc_url( $export_url ); ?>" class="ifs-btn-action-sec">
                    <span class="dashicons dashicons-download"></span> Export CSV
                </a>
                <button type="button" onclick="window.print();" class="ifs-btn-action-pri">
                    <span class="dashicons dashicons-printer"></span> Print Statement
                </button>
            </div>
        </div>

        <!-- Metric Counter Ribbon -->
        <div class="ifs-list-metric-ribbon">
            <div class="ifs-metric-chip border-blue">
                <div class="chip-icon bg-blue"><span class="dashicons dashicons-shield"></span></div>
                <div>
                    <span class="chip-label">Authorized Credit Limit</span>
                    <strong class="chip-val color-blue font-mono">৳<?php echo number_format( $credit_limit, 2 ); ?></strong>
                    <span class="chip-sub">Maximum Overdraft Cap</span>
                </div>
            </div>

            <div class="ifs-metric-chip <?php echo $is_negative ? 'border-rose' : 'border-emerald'; ?>">
                <div class="chip-icon <?php echo $is_negative ? 'bg-rose' : 'bg-emerald'; ?>">
                    <span class="dashicons dashicons-money-alt"></span>
                </div>
                <div>
                    <span class="chip-label">Current Available Balance</span>
                    <strong class="chip-val font-mono <?php echo $is_negative ? 'color-rose' : 'color-emerald'; ?>">
                        <?php echo $is_negative ? '-৳' : '+৳'; ?><?php echo number_format( abs( $current_balance ), 2 ); ?>
                    </strong>
                    <span class="chip-sub"><?php echo $is_negative ? 'Receivable Overdue' : 'Active Deposit Credit'; ?></span>
                </div>
            </div>

            <div class="ifs-metric-chip border-rose">
                <div class="chip-icon bg-rose"><span class="dashicons dashicons-arrow-down-alt"></span></div>
                <div>
                    <span class="chip-label">Total Debits (Bookings)</span>
                    <strong class="chip-val color-rose font-mono">৳<?php echo number_format( $total_debits, 2 ); ?></strong>
                    <span class="chip-sub">Service Invoices &amp; Issues</span>
                </div>
            </div>

            <div class="ifs-metric-chip border-emerald">
                <div class="chip-icon bg-emerald"><span class="dashicons dashicons-arrow-up-alt"></span></div>
                <div>
                    <span class="chip-label">Total Credits (Deposits)</span>
                    <strong class="chip-val color-emerald font-mono">৳<?php echo number_format( $total_credits, 2 ); ?></strong>
                    <span class="chip-sub">Cash &amp; Bank Receipts</span>
                </div>
            </div>
        </div>

        <!-- Quick Top-Up / Balance Adjustment Form Card -->
        <div class="ifs-topup-form-card">
            <div class="topup-form-header">
                <div class="topup-head-title">
                    <span class="dashicons dashicons-money-alt"></span>
                    <div>
                        <h3>Post Balance Deposit or Debit Voucher</h3>
                        <p>Manually adjust balance for bank wire deposits, cash payments, commissions, or booking invoices.</p>
                    </div>
                </div>
            </div>

            <form method="post" action="" class="ifs-topup-form-body">
                <?php wp_nonce_field( 'ifs_agent_topup_action', 'ifs_agent_topup_nonce' ); ?>
                
                <div class="topup-grid-layout">
                    <div class="ifs-field-group">
                        <label for="trans_type">Voucher Classification <span class="required">*</span></label>
                        <div class="ifs-input-icon-wrap">
                            <span class="dashicons dashicons-tag"></span>
                            <select name="trans_type" id="trans_type" class="ifs-input-field ifs-select-styled" required>
                                <option value="credit" selected>Deposit (+) Top-Up Account Balance</option>
                                <option value="debit">Deduction (-) Manual Invoice / Debit</option>
                            </select>
                        </div>
                    </div>

                    <div class="ifs-field-group">
                        <label for="topup_amount">Transaction Amount (৳) <span class="required">*</span></label>
                        <div class="ifs-input-icon-wrap">
                            <span class="dashicons dashicons-money-alt"></span>
                            <input type="number" step="0.01" name="amount" id="topup_amount" required class="ifs-input-field font-mono" placeholder="0.00">
                        </div>
                    </div>

                    <div class="ifs-field-group">
                        <label for="reference_type_custom">Reference Classification</label>
                        <div class="ifs-input-icon-wrap">
                            <span class="dashicons dashicons-category"></span>
                            <select name="reference_type_custom" id="reference_type_custom" class="ifs-input-field ifs-select-styled">
                                <option value="Bank Deposit" selected>Bank Wire / Deposit</option>
                                <option value="Cash Receipt">Cash Inflow Receipt</option>
                                <option value="bKash / Nagad">Mobile Banking Payment</option>
                                <option value="Manual Adjustment">Manual Audit Adjustment</option>
                                <option value="Commission Rebate">Commission Incentive / Rebate</option>
                            </select>
                        </div>
                    </div>

                    <div class="ifs-field-group">
                        <label for="topup_note">Bank Memo / Transaction Note</label>
                        <div class="ifs-input-icon-wrap">
                            <span class="dashicons dashicons-media-text"></span>
                            <input type="text" name="note" id="topup_note" class="ifs-input-field" placeholder="e.g. City Bank Slip #CB-84920">
                        </div>
                    </div>

                    <div class="ifs-field-group btn-col">
                        <button type="submit" name="ifs_agent_topup_submit" class="ifs-btn-topup-submit">
                            <span class="dashicons dashicons-saved"></span> Post Voucher Entry
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Master Statement History Table Card -->
        <div class="ifs-table-card">
            <div class="ifs-table-top-bar">
                <div class="ifs-table-title-group">
                    <h3 class="ifs-table-heading"><span class="dashicons dashicons-list-view"></span> Chronological Statement &amp; Transaction Ledger</h3>
                    <p class="ifs-table-caption">Complete audit trail of all ticketing deductions, visa charges, refunds, and bank payment settlements.</p>
                </div>
            </div>

            <!-- Custom DataTables Controls -->
            <div class="ifs-custom-table-controls">
                <div class="ifs-per-page-wrap">
                    <label for="ifsAgentLedgerPerPage">Show</label>
                    <select id="ifsAgentLedgerPerPage" class="ifs-select-control">
                        <option value="10">10</option>
                        <option value="15" selected>15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span>entries</span>
                </div>
                <div class="ifs-live-search-wrap">
                    <label for="ifsAgentLedgerSearch"><span class="dashicons dashicons-search"></span></label>
                    <input type="text" id="ifsAgentLedgerSearch" class="ifs-search-input" placeholder="Search by reference, remarks, date...">
                </div>
            </div>

            <div class="ifs-table-responsive-wrapper">
                <table class="ifs-pro-datatable" id="ifsAgentLedgerTable">
                    <thead>
                        <tr>
                            <th style="width: 170px;">Date &amp; Timestamp</th>
                            <th style="width: 150px;">Reference Scope</th>
                            <th style="text-align: right;">Debit Outflow (-)</th>
                            <th style="text-align: right;">Credit Inflow (+)</th>
                            <th style="text-align: right;">Running Balance (৳)</th>
                            <th>Narration / Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $ledger_records ) : foreach ( $ledger_records as $rec ) : 
                            $bal_val       = (float) $rec->balance_after;
                            $is_bal_neg    = ( $bal_val < 0 );
                            $has_debit     = ( (float) $rec->debit > 0 );
                            $has_credit    = ( (float) $rec->credit > 0 );
                        ?>
                            <tr>
                                <td>
                                    <span class="font-mono text-date"><?php echo date( 'd M Y, h:i A', strtotime( $rec->created_at ) ); ?></span>
                                </td>
                                <td>
                                    <span class="ifs-ref-type-badge">
                                        <?php echo esc_html( $rec->reference_type ); ?>
                                        <?php if ( ! empty( $rec->reference_id ) ) : ?>
                                            <span class="ref-num">#<?php echo esc_html( $rec->reference_id ); ?></span>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td style="text-align: right;" class="font-mono font-bold <?php echo $has_debit ? 'color-rose' : 'color-muted'; ?>">
                                    <?php echo $has_debit ? '-৳' . number_format( (float) $rec->debit, 2 ) : '&mdash;'; ?>
                                </td>
                                <td style="text-align: right;" class="font-mono font-bold <?php echo $has_credit ? 'color-emerald' : 'color-muted'; ?>">
                                    <?php echo $has_credit ? '+৳' . number_format( (float) $rec->credit, 2 ) : '&mdash;'; ?>
                                </td>
                                <td style="text-align: right;" class="font-mono font-bold <?php echo $is_bal_neg ? 'color-rose' : 'color-navy'; ?>">
                                    <?php echo $is_bal_neg ? '-৳' : '+৳'; ?><?php echo number_format( abs( $bal_val ), 2 ); ?>
                                </td>
                                <td>
                                    <span class="narration-text"><?php echo esc_html( $rec->note ?: 'System ledger transaction' ); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr>
                                <td colspan="6" class="ifs-empty-table">
                                    <div class="ifs-empty-state">
                                        <span class="dashicons dashicons-media-spreadsheet"></span>
                                        <h4>No Transaction History Found</h4>
                                        <p>Post a top-up deposit or issue tickets to generate ledger entries for this sub-agent.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Stylesheet -->
    <style>
        .ifs-agent-ledger-workspace {
            max-width: 1420px;
            margin: 0 auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #0f172a;
        }

        /* Toast Notifications */
        .ifs-toast {
            padding: 14px 20px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 22px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        .ifs-toast.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .ifs-toast.danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .ifs-toast .dashicons { font-size: 18px; width: 18px; height: 18px; }

        /* Header Card */
        .ifs-ledger-header-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04);
            margin-bottom: 24px;
        }
        .header-badge-row { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
        .ifs-ledger-badge {
            background: #eff6ff;
            color: #1d4ed8;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border: 1px solid #dbeafe;
        }
        .ifs-ledger-badge .dashicons { font-size: 13px; width: 13px; height: 13px; }
        .ifs-id-badge {
            background: #f1f5f9;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }

        .header-title-group h2 { margin: 0 0 6px 0; font-size: 22px; font-weight: 900; color: #0f172a; letter-spacing: -0.4px; }
        .header-contact-meta { font-size: 12.5px; color: #64748b; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .header-contact-meta .dashicons { font-size: 14px; width: 14px; height: 14px; color: #94a3b8; }
        .meta-dot { width: 3px; height: 3px; border-radius: 50%; background: #cbd5e1; }

        .header-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .ifs-btn-back, .ifs-btn-action-sec {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #334155;
            padding: 9px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .ifs-btn-back:hover, .ifs-btn-action-sec:hover { background: #e2e8f0; color: #0f172a; }
        .ifs-btn-action-pri {
            background: linear-gradient(135deg, #003376 0%, #0284c7 100%);
            color: #ffffff !important;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 12px rgba(0, 51, 118, 0.25);
            transition: all 0.2s ease;
        }
        .ifs-btn-action-pri:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(0, 51, 118, 0.35); }
        .ifs-btn-action-pri .dashicons, .ifs-btn-action-sec .dashicons, .ifs-btn-back .dashicons { font-size: 15px; width: 15px; height: 15px; }

        /* Metric Counter Ribbon */
        .ifs-list-metric-ribbon {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }
        .ifs-metric-chip {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 18px 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 8px -2px rgba(15, 23, 42, 0.03);
            border-left: 5px solid #cbd5e1;
        }
        .ifs-metric-chip.border-blue    { border-left-color: #0284c7; }
        .ifs-metric-chip.border-emerald { border-left-color: #059669; }
        .ifs-metric-chip.border-rose    { border-left-color: #e11d48; }

        .chip-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            flex-shrink: 0;
        }
        .chip-icon.bg-blue    { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); }
        .chip-icon.bg-emerald { background: linear-gradient(135deg, #059669 0%, #047857 100%); }
        .chip-icon.bg-rose    { background: linear-gradient(135deg, #e11d48 0%, #be123c 100%); }
        .chip-icon .dashicons { font-size: 22px; width: 22px; height: 22px; }

        .chip-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; display: block; margin-bottom: 2px; }
        .chip-val { font-size: 20px; font-weight: 900; color: #0f172a; letter-spacing: -0.5px; }
        .chip-sub { font-size: 11px; color: #64748b; font-weight: 600; margin-top: 2px; display: block; }
        .color-blue    { color: #003376 !important; }
        .color-emerald { color: #059669 !important; }
        .color-rose    { color: #e11d48 !important; }
        .color-navy    { color: #0f172a !important; }
        .color-muted   { color: #94a3b8 !important; }

        /* Top-Up / Voucher Form Card */
        .ifs-topup-form-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
            overflow: hidden;
            margin-bottom: 26px;
        }
        .topup-form-header {
            padding: 18px 24px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .topup-head-title { display: flex; align-items: center; gap: 12px; }
        .topup-head-title .dashicons {
            width: 32px;
            height: 32px;
            background: #003376;
            color: #ffffff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .topup-head-title h3 { margin: 0; font-size: 15.5px; font-weight: 800; color: #0f172a; }
        .topup-head-title p { margin: 2px 0 0 0; font-size: 12px; color: #64748b; }

        .ifs-topup-form-body { padding: 22px 24px; }
        .topup-grid-layout {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)) auto;
            gap: 16px 18px;
            align-items: flex-end;
        }
        @media (max-width: 900px) {
            .topup-grid-layout { grid-template-columns: 1fr; }
        }

        .ifs-field-group { display: flex; flex-direction: column; gap: 5px; }
        .ifs-field-group label {
            font-size: 11.5px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .ifs-field-group label .required { color: #e11d48; }

        .ifs-input-icon-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }
        .ifs-input-icon-wrap .dashicons {
            position: absolute;
            left: 12px;
            color: #94a3b8;
            font-size: 16px;
            width: 16px;
            height: 16px;
            pointer-events: none;
        }

        .ifs-input-field {
            width: 100%;
            padding: 9px 12px 9px 38px !important;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13.5px;
            color: #0f172a;
            background: #ffffff;
            outline: none;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }
        .ifs-input-field:focus {
            border-color: #003376;
            box-shadow: 0 0 0 3px rgba(0, 51, 118, 0.12);
        }
        .ifs-select-styled {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 14px;
            padding-right: 32px !important;
            cursor: pointer;
        }

        .ifs-btn-topup-submit {
            background: linear-gradient(135deg, #003376 0%, #0284c7 100%);
            color: #ffffff !important;
            border: none;
            padding: 10px 22px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 38px;
            box-shadow: 0 4px 12px rgba(0, 51, 118, 0.25);
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        .ifs-btn-topup-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(0, 51, 118, 0.35); }
        .ifs-btn-topup-submit .dashicons { font-size: 16px; width: 16px; height: 16px; }

        /* Master Table Card */
        .ifs-table-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
            overflow: hidden;
        }
        .ifs-table-top-bar {
            padding: 22px 26px;
            border-bottom: 1px solid #f1f5f9;
        }
        .ifs-table-heading { margin: 0; font-size: 17px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        .ifs-table-heading .dashicons { color: #003376; font-size: 20px; width: 20px; height: 20px; }
        .ifs-table-caption { margin: 3px 0 0 0; font-size: 13px; color: #64748b; }

        /* Custom Table Controls */
        .ifs-custom-table-controls {
            padding: 16px 26px;
            background: #f8fafc;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .ifs-per-page-wrap { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #475569; font-weight: 600; }
        .ifs-select-control {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 5px 28px 5px 10px;
            font-size: 13px;
            color: #0f172a;
            background: #ffffff;
            outline: none;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 12px;
        }
        .ifs-select-control:focus { border-color: #003376; box-shadow: 0 0 0 3px rgba(0, 51, 118, 0.12); }

        .ifs-live-search-wrap { position: relative; display: flex; align-items: center; min-width: 280px; }
        .ifs-live-search-wrap label { position: absolute; left: 12px; color: #94a3b8; display: flex; align-items: center; pointer-events: none; }
        .ifs-live-search-wrap label .dashicons { font-size: 16px; width: 16px; height: 16px; }
        .ifs-search-input {
            width: 100%;
            padding: 7px 12px 7px 36px !important;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13px;
            color: #0f172a;
            background: #ffffff;
            outline: none;
            transition: all 0.2s ease;
        }
        .ifs-search-input:focus { border-color: #003376; box-shadow: 0 0 0 3px rgba(0, 51, 118, 0.12); }

        /* Table Architecture */
        .ifs-table-responsive-wrapper { padding: 15px 24px 24px 24px; overflow-x: auto; }
        .ifs-pro-datatable { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        .ifs-pro-datatable thead th {
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 16px;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
            white-space: nowrap;
        }
        .ifs-pro-datatable tbody td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: #334155;
        }
        .ifs-pro-datatable tbody tr:hover td { background: #f8fafc; }

        .text-date { font-size: 12px; color: #64748b; }
        .ifs-ref-type-badge {
            background: #f1f5f9;
            color: #0f172a;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 700;
            border: 1px solid #e2e8f0;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .ref-num { color: #0284c7; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .narration-text { font-size: 12.5px; color: #64748b; line-height: 1.4; }

        .ifs-empty-table { text-align: center; padding: 50px 20px !important; }
        .ifs-empty-state .dashicons { font-size: 44px; width: 44px; height: 44px; color: #cbd5e1; margin-bottom: 10px; }
        .ifs-empty-state h4 { margin: 0 0 4px 0; font-size: 16px; font-weight: 700; color: #334155; }
        .ifs-empty-state p { margin: 0; color: #94a3b8; font-size: 13px; }

        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .font-bold { font-weight: 700; }

        /* DataTables Controls Integration */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter { display: none !important; }
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate { margin-top: 18px; font-size: 13px; color: #64748b; }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 6px !important;
            color: #334155 !important;
            padding: 6px 12px !important;
            margin-left: 4px;
            font-weight: 600;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #003376 !important;
            color: #ffffff !important;
            border: 1px solid #003376 !important;
            font-weight: 700;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #e2e8f0 !important;
            color: #0f172a !important;
        }

        /* Print Media Isolation */
        @media print {
            #adminmenuback, #adminmenuwrap, #wpadminbar, #wpfooter, .ifs-topup-form-card, .header-actions, .ifs-custom-table-controls, .dataTables_wrapper .dataTables_paginate, .dataTables_wrapper .dataTables_info {
                display: none !important;
            }
            #wpcontent, #wpbody-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            body.wp-admin { background: #ffffff !important; }
            .ifs-agent-ledger-workspace { max-width: 100% !important; margin: 0 !important; }
            .ifs-table-card, .ifs-ledger-header-card {
                box-shadow: none !important;
                border: 1px solid #000 !important;
            }
        }
    </style>

    <!-- DataTables Engine Script -->
    <script>
    jQuery(document).ready(function($) {
        if ($.fn.DataTable) {
            var table = $('#ifsAgentLedgerTable').DataTable({
                "pageLength": 15,
                "ordering": true,
                "info": true,
                "searching": true,
                "lengthChange": false,
                "order": [[ 0, "desc" ]],
                "language": {
                    "info": "Showing _START_ to _END_ of _TOTAL_ statement ledger entries",
                    "infoEmpty": "Showing 0 to 0 of 0 entries",
                    "infoFiltered": "(filtered from _MAX_ total records)",
                    "paginate": {
                        "previous": "&larr; Prev",
                        "next": "Next &rarr;"
                    }
                }
            });

            // Bind Custom Per Page Selector
            $('#ifsAgentLedgerPerPage').on('change', function() {
                var lengthVal = parseInt($(this).val());
                table.page.len(lengthVal).draw();
            });

            // Bind Custom Live Search Box
            $('#ifsAgentLedgerSearch').on('keyup', function() {
                table.search(this.value).draw();
            });
        }
    });
    </script>
    <?php
}