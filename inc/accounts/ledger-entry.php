<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Enterprise Office Expenses & Accounts Ledger Console
 * Features: Live Cash Register, Dynamic Aggregation KPIs, Quick Voucher Posting, Live Search & DataTables Controls
 */
function ifs_terp_ledger_entry_page() {
    global $wpdb;
    $table_ledger = $wpdb->prefix . 'iterp_ledger';
    $message      = '';

    // Handle Voucher Entry Submission
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_ledger_submit'] ) ) {
        check_admin_referer( 'ifs_ledger_entry_action', 'ifs_ledger_nonce' );

        $t_type     = sanitize_text_field( $_POST['transaction_type'] ?? 'Expense' );
        $category   = sanitize_text_field( $_POST['category'] ?? 'General Expense' );
        $amount     = floatval( $_POST['amount'] ?? 0 );
        $pay_method = sanitize_text_field( $_POST['payment_method'] ?? 'Cash' );
        $ref_no     = sanitize_text_field( $_POST['reference_no'] ?? '' );
        $desc       = sanitize_textarea_field( $_POST['description'] ?? '' );
        $raw_date   = sanitize_text_field( $_POST['transaction_date'] ?? date( 'Y-m-d' ) );
        $trans_date = $raw_date . ' ' . current_time( 'H:i:s' );
        $user_id    = get_current_user_id();

        if ( $amount > 0 ) {
            $inserted = $wpdb->insert(
                $table_ledger,
                array(
                    'transaction_type' => $t_type,
                    'category'         => $category,
                    'amount'           => $amount,
                    'payment_method'   => $pay_method,
                    'reference_no'     => $ref_no,
                    'description'      => $desc,
                    'transaction_date' => $trans_date,
                    'logged_by'        => $user_id,
                ),
                array( '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%d' )
            );

            if ( $inserted ) {
                if ( function_exists( 'ifs_terp_log_activity' ) ) {
                    ifs_terp_log_activity( "Posted Ledger Voucher #{$ref_no}: {$t_type} ৳" . number_format( $amount, 2 ) . " under {$category}" );
                }
                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Ledger voucher entry recorded successfully.</div>';
            } else {
                $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> Database error: Unable to record voucher.</div>';
            }
        } else {
            $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> Invalid entry: Voucher amount must be greater than zero.</div>';
        }
    }

    // Dynamic Ledger Metric Aggregations
    $total_income  = (float) $wpdb->get_var( "SELECT COALESCE(SUM(amount), 0) FROM $table_ledger WHERE transaction_type = 'Income'" );
    $total_expense = (float) $wpdb->get_var( "SELECT COALESCE(SUM(amount), 0) FROM $table_ledger WHERE transaction_type = 'Expense'" );
    $net_balance   = $total_income - $total_expense;
    $total_entries = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_ledger" );

    $records = $wpdb->get_results( "SELECT * FROM $table_ledger ORDER BY id DESC" );
    ?>

    <div class="wrap ifs-ledger-workspace">
        <?php echo $message; ?>

        <!-- Metric Summary Ribbon -->
        <div class="ifs-list-metric-ribbon">
            <div class="ifs-metric-chip border-emerald">
                <div class="chip-icon bg-emerald"><span class="dashicons dashicons-arrow-up-alt"></span></div>
                <div>
                    <span class="chip-label">Total Cash Inflow</span>
                    <strong class="chip-val color-emerald font-mono">৳<?php echo number_format( $total_income, 2 ); ?></strong>
                </div>
            </div>
            <div class="ifs-metric-chip border-rose">
                <div class="chip-icon bg-rose"><span class="dashicons dashicons-arrow-down-alt"></span></div>
                <div>
                    <span class="chip-label">Total Cash Outflow</span>
                    <strong class="chip-val color-rose font-mono">৳<?php echo number_format( $total_expense, 2 ); ?></strong>
                </div>
            </div>
            <div class="ifs-metric-chip <?php echo ( $net_balance >= 0 ) ? 'border-blue' : 'border-rose'; ?>">
                <div class="chip-icon <?php echo ( $net_balance >= 0 ) ? 'bg-blue' : 'bg-rose'; ?>">
                    <span class="dashicons dashicons-money-alt"></span>
                </div>
                <div>
                    <span class="chip-label">Net Register Balance</span>
                    <strong class="chip-val font-mono <?php echo ( $net_balance >= 0 ) ? 'color-blue' : 'color-rose'; ?>">
                        <?php echo ( $net_balance >= 0 ) ? '+৳' : '-৳'; ?><?php echo number_format( abs( $net_balance ), 2 ); ?>
                    </strong>
                </div>
            </div>
            <div class="ifs-metric-chip border-indigo">
                <div class="chip-icon bg-indigo"><span class="dashicons dashicons-media-spreadsheet"></span></div>
                <div>
                    <span class="chip-label">Voucher Entries</span>
                    <strong class="chip-val font-mono"><?php echo number_format( $total_entries ); ?> Records</strong>
                </div>
            </div>
        </div>

        <!-- Voucher Posting Form Card -->
        <div class="ifs-voucher-form-card">
            <div class="voucher-form-header">
                <div class="voucher-head-title">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <div>
                        <h3>Post New Ledger Voucher</h3>
                        <p>Record daily miscellaneous revenue inflow, utility disbursements, and operational expenses.</p>
                    </div>
                </div>
            </div>

            <form method="post" action="" class="ifs-voucher-form-body">
                <?php wp_nonce_field( 'ifs_ledger_entry_action', 'ifs_ledger_nonce' ); ?>
                
                <div class="voucher-grid-layout">
                    <div class="ifs-field-group">
                        <label for="transaction_type">Voucher Classification <span class="required">*</span></label>
                        <div class="ifs-input-icon-wrap">
                            <span class="dashicons dashicons-tag"></span>
                            <select name="transaction_type" id="transaction_type" class="ifs-input-field ifs-select-styled" required>
                                <option value="Expense" selected>Expense (- Cash Outflow)</option>
                                <option value="Income">Income (+ Cash Inflow)</option>
                            </select>
                        </div>
                    </div>

                    <div class="ifs-field-group">
                        <label for="ledger_category">Ledger Category / Head <span class="required">*</span></label>
                        <div class="ifs-input-icon-wrap">
                            <span class="dashicons dashicons-category"></span>
                            <input type="text" name="category" id="ledger_category" required class="ifs-input-field" 
                                placeholder="e.g. Office Rent, Entertainment, Utilities, Tea">
                        </div>
                    </div>

                    <div class="ifs-field-group">
                        <label for="ledger_amount">Transaction Amount (৳) <span class="required">*</span></label>
                        <div class="ifs-input-icon-wrap">
                            <span class="dashicons dashicons-money-alt"></span>
                            <input type="number" step="0.01" name="amount" id="ledger_amount" required class="ifs-input-field font-mono" 
                                placeholder="0.00">
                        </div>
                    </div>

                    <div class="ifs-field-group">
                        <label for="ledger_payment_method">Settlement Account</label>
                        <div class="ifs-input-icon-wrap">
                            <span class="dashicons dashicons-chart-pie"></span>
                            <select name="payment_method" id="ledger_payment_method" class="ifs-input-field ifs-select-styled">
                                <option value="Cash">Cash Drawer</option>
                                <option value="Bank">Bank Account</option>
                                <option value="bKash / Nagad">Mobile Financial (bKash/Nagad)</option>
                                <option value="Cheque">Bank Cheque</option>
                            </select>
                        </div>
                    </div>

                    <div class="ifs-field-group">
                        <label for="ledger_date">Voucher Date <span class="required">*</span></label>
                        <div class="ifs-input-icon-wrap">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <input type="date" name="transaction_date" id="ledger_date" required value="<?php echo date( 'Y-m-d' ); ?>" class="ifs-input-field">
                        </div>
                    </div>

                    <div class="ifs-field-group">
                        <label for="ledger_ref">Voucher / Receipt Ref No</label>
                        <div class="ifs-input-icon-wrap">
                            <span class="dashicons dashicons-media-text"></span>
                            <input type="text" name="reference_no" id="ledger_ref" class="ifs-input-field font-mono" placeholder="e.g. VCH-<?php echo date( 'ymd' ) . '-' . rand( 10, 99 ); ?>">
                        </div>
                    </div>

                    <div class="ifs-field-group full-width">
                        <label for="ledger_desc">Narration &amp; Transaction Details</label>
                        <textarea name="description" id="ledger_desc" rows="2" class="ifs-textarea-field" placeholder="Provide memo details, bill numbers, or vendor remarks..."></textarea>
                    </div>
                </div>

                <div class="voucher-form-footer">
                    <button type="reset" class="ifs-btn-reset">Clear Fields</button>
                    <button type="submit" name="ifs_ledger_submit" class="ifs-btn-submit">
                        <span class="dashicons dashicons-saved"></span> Post Voucher to Ledger
                    </button>
                </div>
            </form>
        </div>

        <!-- Master Ledger Data Table Card -->
        <div class="ifs-table-card">
            <div class="ifs-table-top-bar">
                <div class="ifs-table-title-group">
                    <h3 class="ifs-table-heading"><span class="dashicons dashicons-list-view"></span> Daily Office Accounts Register</h3>
                    <p class="ifs-table-caption">Complete chronological audit history of all office income, operational costs, and petty cash disbursements.</p>
                </div>
            </div>

            <!-- Custom DataTables Controls -->
            <div class="ifs-custom-table-controls">
                <div class="ifs-per-page-wrap">
                    <label for="ifsLedgerPerPageSelect">Show</label>
                    <select id="ifsLedgerPerPageSelect" class="ifs-select-control">
                        <option value="10">10</option>
                        <option value="15" selected>15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span>entries</span>
                </div>
                <div class="ifs-live-search-wrap">
                    <label for="ifsLedgerSearchInput"><span class="dashicons dashicons-search"></span></label>
                    <input type="text" id="ifsLedgerSearchInput" class="ifs-search-input" placeholder="Search by category, voucher, narration, method...">
                </div>
            </div>

            <div class="ifs-table-responsive-wrapper">
                <table class="ifs-pro-datatable" id="ifsLedgerTable">
                    <thead>
                        <tr>
                            <th style="width: 100px;">Date</th>
                            <th style="width: 110px; text-align: center;">Entry Type</th>
                            <th>Ledger Category</th>
                            <th>Method</th>
                            <th>Voucher Ref</th>
                            <th style="text-align: right;">Amount (৳)</th>
                            <th>Narration / Memo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $records ) : foreach ( $records as $row ) : 
                            $is_inc = ( $row->transaction_type === 'Income' );
                        ?>
                            <tr>
                                <td>
                                    <span class="font-mono"><?php echo date( 'd M Y', strtotime( $row->transaction_date ) ); ?></span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="ifs-type-badge <?php echo $is_inc ? 'badge-inflow' : 'badge-outflow'; ?>">
                                        <?php echo $is_inc ? '+ Inflow' : '- Outflow'; ?>
                                    </span>
                                </td>
                                <td>
                                    <strong class="color-navy"><?php echo esc_html( $row->category ); ?></strong>
                                </td>
                                <td>
                                    <span class="ifs-method-pill"><?php echo esc_html( $row->payment_method ); ?></span>
                                </td>
                                <td>
                                    <span class="ifs-ref-badge"><?php echo esc_html( $row->reference_no ?: '-' ); ?></span>
                                </td>
                                <td style="text-align: right;" class="font-mono font-bold <?php echo $is_inc ? 'color-emerald' : 'color-rose'; ?>">
                                    <?php echo $is_inc ? '+' : '-'; ?>৳<?php echo number_format( (float) $row->amount, 2 ); ?>
                                </td>
                                <td>
                                    <span class="narration-text"><?php echo esc_html( $row->description ?: 'No memo added' ); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr>
                                <td colspan="7" class="ifs-empty-table">
                                    <div class="ifs-empty-state">
                                        <span class="dashicons dashicons-media-spreadsheet"></span>
                                        <h4>No Ledger Vouchers Recorded</h4>
                                        <p>Use the form above to record your first office cash entry.</p>
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
        .ifs-ledger-workspace {
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

        /* Metric Counter Ribbon */
        .ifs-list-metric-ribbon {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
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
        .ifs-metric-chip.border-indigo  { border-left-color: #4f46e5; }

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
        .chip-icon.bg-indigo  { background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); }
        .chip-icon .dashicons { font-size: 22px; width: 22px; height: 22px; }

        .chip-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; display: block; margin-bottom: 2px; }
        .chip-val { font-size: 20px; font-weight: 900; color: #0f172a; letter-spacing: -0.5px; }
        .color-emerald { color: #059669 !important; }
        .color-rose    { color: #e11d48 !important; }
        .color-blue    { color: #003376 !important; }
        .color-navy    { color: #0f172a !important; }

        /* Voucher Form Card */
        .ifs-voucher-form-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
            overflow: hidden;
            margin-bottom: 26px;
        }
        .voucher-form-header {
            padding: 20px 26px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .voucher-head-title { display: flex; align-items: center; gap: 12px; }
        .voucher-head-title .dashicons {
            width: 34px;
            height: 34px;
            background: #003376;
            color: #ffffff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .voucher-head-title h3 { margin: 0; font-size: 16px; font-weight: 800; color: #0f172a; }
        .voucher-head-title p { margin: 2px 0 0 0; font-size: 12.5px; color: #64748b; }

        .ifs-voucher-form-body { padding: 24px 26px; }
        .voucher-grid-layout {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px 20px;
        }
        .ifs-field-group { display: flex; flex-direction: column; gap: 5px; }
        .ifs-field-group.full-width { grid-column: 1 / -1; }
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
        .ifs-input-field:focus, .ifs-textarea-field:focus {
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
        .ifs-textarea-field {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13px;
            color: #0f172a;
            background: #ffffff;
            outline: none;
            transition: all 0.2s ease;
            box-sizing: border-box;
            resize: vertical;
        }

        .voucher-form-footer {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #f1f5f9;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 12px;
        }
        .ifs-btn-reset {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #475569;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .ifs-btn-reset:hover { background: #e2e8f0; color: #0f172a; }
        .ifs-btn-submit {
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
            box-shadow: 0 4px 12px rgba(0, 51, 118, 0.25);
            transition: all 0.2s ease;
        }
        .ifs-btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(0, 51, 118, 0.35); }
        .ifs-btn-submit .dashicons { font-size: 16px; width: 16px; height: 16px; }

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
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        /* Table Responsive Styling */
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

        .ifs-type-badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 10.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .badge-inflow  { background: #dcfce7; color: #15803d; }
        .badge-outflow { background: #fee2e2; color: #b91c1c; }

        .ifs-method-pill {
            background: #f1f5f9;
            color: #475569;
            font-size: 11.5px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 6px;
        }
        .ifs-ref-badge {
            background: #f8fafc;
            color: #003376;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-weight: 700;
            font-size: 11px;
            padding: 3px 6px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
        }
        .narration-text { font-size: 12px; color: #64748b; }

        .ifs-empty-table { text-align: center; padding: 50px 20px !important; }
        .ifs-empty-state .dashicons { font-size: 44px; width: 44px; height: 44px; color: #cbd5e1; margin-bottom: 10px; }
        .ifs-empty-state h4 { margin: 0 0 4px 0; font-size: 16px; font-weight: 700; color: #334155; }
        .ifs-empty-state p { margin: 0; color: #94a3b8; font-size: 13px; }

        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .font-bold { font-weight: 700; }

        /* DataTables Integration */
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
    </style>

    <!-- DataTables Engine Script -->
    <script>
    jQuery(document).ready(function($) {
        if ($.fn.DataTable) {
            var table = $('#ifsLedgerTable').DataTable({
                "pageLength": 15,
                "ordering": true,
                "info": true,
                "searching": true,
                "lengthChange": false,
                "order": [[ 0, "desc" ]],
                "language": {
                    "info": "Showing _START_ to _END_ of _TOTAL_ ledger entries",
                    "infoEmpty": "Showing 0 to 0 of 0 entries",
                    "infoFiltered": "(filtered from _MAX_ total entries)",
                    "paginate": {
                        "previous": "&larr; Prev",
                        "next": "Next &rarr;"
                    }
                }
            });

            // Bind Custom Per Page Selector
            $('#ifsLedgerPerPageSelect').on('change', function() {
                var lengthVal = parseInt($(this).val());
                table.page.len(lengthVal).draw();
            });

            // Bind Custom Live Search Box
            $('#ifsLedgerSearchInput').on('keyup', function() {
                table.search(this.value).draw();
            });
        }
    });
    </script>
    <?php
}