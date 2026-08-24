<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enterprise Next-Gen Invoice & Money Receipt Creation Workspace
 * Features: Dynamic Line-Item Repeater, Real-time Calculation Matrix, Auto General Ledger Integration & High-End UI
 */
function ifs_terp_invoice_create_page() {
    global $wpdb;
    $table_invoices  = $wpdb->prefix . 'iterp_invoices';
    $table_customers = $wpdb->prefix . 'iterp_customers';
    $table_agents    = $wpdb->prefix . 'iterp_agents';
    $table_ledger    = $wpdb->prefix . 'iterp_ledger';

    $message = '';

    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_invoice_submit'] ) ) {
        check_admin_referer( 'ifs_invoice_create_action', 'ifs_invoice_nonce' );

        $client_type = sanitize_text_field( $_POST['client_type'] ?? 'Customer' );
        $client_id   = intval( $_POST['client_id'] ?? 0 );
        $subtotal    = floatval( $_POST['subtotal'] ?? 0 );
        $discount    = floatval( $_POST['discount'] ?? 0 );
        $net_total   = $subtotal - $discount;
        $paid_amount = floatval( $_POST['paid_amount'] ?? 0 );
        $due_amount  = $net_total - $paid_amount;

        $payment_status = 'Unpaid';
        if ( $paid_amount >= $net_total && $net_total > 0 ) {
            $payment_status = 'Paid';
        } elseif ( $paid_amount > 0 && $paid_amount < $net_total ) {
            $payment_status = 'Partial';
        }

        // Generate Standard Invoice Number
        $invoice_count = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_invoices" );
        $invoice_no    = 'INV-' . date('Y') . '-' . str_pad( (string) ($invoice_count + 1), 4, '0', STR_PAD_LEFT );

        // Dynamic Line Items Handling
        $items = array();
        if ( ! empty( $_POST['item_desc'] ) && is_array( $_POST['item_desc'] ) ) {
            foreach ( $_POST['item_desc'] as $key => $desc ) {
                if ( ! empty( $desc ) ) {
                    $qty        = max( 1, intval( $_POST['item_qty'][$key] ?? 1 ) );
                    $unit_price = floatval( $_POST['item_price'][$key] ?? 0 );
                    $total      = floatval( $_POST['item_total'][$key] ?? ($qty * $unit_price) );

                    $items[] = array(
                        'description' => sanitize_text_field( $desc ),
                        'qty'         => $qty,
                        'unit_price'  => $unit_price,
                        'total'       => $total
                    );
                }
            }
        }

        $inserted = $wpdb->insert(
            $table_invoices,
            array(
                'invoice_no'     => $invoice_no,
                'client_type'    => $client_type,
                'client_id'      => $client_id,
                'subtotal'       => $subtotal,
                'discount'       => $discount,
                'net_total'      => $net_total,
                'paid_amount'    => $paid_amount,
                'due_amount'     => $due_amount,
                'payment_status' => $payment_status,
                'items_json'     => wp_json_encode( $items ),
                'created_by'     => get_current_user_id(),
                'created_at'     => current_time( 'mysql' )
            ),
            array( '%s', '%s', '%d', '%f', '%f', '%f', '%f', '%f', '%s', '%s', '%d', '%s' )
        );

        if ( $inserted ) {
            $inv_id = $wpdb->insert_id;

            // Auto-post payment to General Ledger if paid > 0
            if ( $paid_amount > 0 ) {
                $pay_method = sanitize_text_field( $_POST['payment_method'] ?? 'Cash' );
                $wpdb->insert(
                    $table_ledger,
                    array(
                        'transaction_type' => 'Income',
                        'category'         => 'Invoice Payment',
                        'amount'           => $paid_amount,
                        'payment_method'   => $pay_method,
                        'reference_no'     => $invoice_no,
                        'description'      => 'Payment against Invoice ' . $invoice_no,
                        'transaction_date' => current_time( 'mysql' ),
                        'logged_by'        => get_current_user_id()
                    ),
                    array( '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%d' )
                );
            }

            if ( function_exists('ifs_terp_log_activity') ) {
                ifs_terp_log_activity( "Created Invoice " . $invoice_no . " (" . $client_type . " ID: " . $client_id . ") for ৳" . number_format($net_total, 2) );
            }

            $view_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=accounts&sub=view_invoice&id=' . $inv_id );
            $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Invoice generated successfully! <a href="' . esc_url( $view_url ) . '" class="toast-link">View Invoice Dossier &rarr;</a></div>';
        } else {
            $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> Database error: Unable to create invoice.</div>';
        }
    }

    $customers = $wpdb->get_results( "SELECT id, full_name, mobile FROM $table_customers ORDER BY full_name ASC" );
    $agents    = $wpdb->get_results( "SELECT id, agency_name FROM $table_agents ORDER BY agency_name ASC" );
    $back_url  = admin_url( 'admin.php?page=ifs_travel_erp&tab=accounts' );
    ?>

    <div class="wrap ifs-invoice-workspace">
        <?php echo $message; ?>

        <!-- Form Hero Header -->
        <div class="ifs-invoice-header-card">
            <div class="header-title-group">
                <span class="ifs-invoice-badge">
                    <span class="dashicons dashicons-money-alt"></span> Billing &amp; Settlement Terminal
                </span>
                <h2>Generate Sales Invoice &amp; Money Receipt</h2>
                <p>Create itemized invoices for retail travelers or B2B sub-agents with automated payment ledger posting.</p>
            </div>
            <div class="header-actions">
                <a href="<?php echo esc_url( $back_url ); ?>" class="ifs-btn-back">
                    <span class="dashicons dashicons-arrow-left-alt"></span> Return to Invoices
                </a>
            </div>
        </div>

        <!-- Master Invoice Form Container -->
        <div class="ifs-invoice-form-card">
            <form method="post" action="" id="ifsInvoiceForm">
                <?php wp_nonce_field( 'ifs_invoice_create_action', 'ifs_invoice_nonce' ); ?>

                <!-- Section 1: Client Selection -->
                <div class="ifs-invoice-section">
                    <div class="section-title-wrap">
                        <div class="section-icon bg-blue"><span class="dashicons dashicons-admin-users"></span></div>
                        <div>
                            <h4>Billing Recipient Details</h4>
                            <p>Select whether this invoice is billed to a retail customer or a B2B agency account.</p>
                        </div>
                    </div>

                    <div class="ifs-client-grid">
                        <div class="ifs-field-group">
                            <label for="ifs_client_type">Recipient Classification <span class="required">*</span></label>
                            <div class="ifs-input-icon-wrap">
                                <span class="dashicons dashicons-category"></span>
                                <select name="client_type" id="ifs_client_type" class="ifs-input-field ifs-select-styled">
                                    <option value="Customer" selected>Retail Traveler / Customer (B2C)</option>
                                    <option value="Agent">Sub-Agent Account (B2B)</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-group" id="ifs_customer_select_box">
                            <label for="client_id_customer">Select Passenger / Customer <span class="required">*</span></label>
                            <div class="ifs-input-icon-wrap">
                                <span class="dashicons dashicons-businessman"></span>
                                <select name="client_id_customer" id="client_id_customer" class="ifs-input-field ifs-select-styled">
                                    <?php if ( $customers ) : foreach ( $customers as $cus ) : ?>
                                        <option value="<?php echo $cus->id; ?>"><?php echo esc_html( $cus->full_name . ' (' . $cus->mobile . ')' ); ?></option>
                                    <?php endforeach; else : ?>
                                        <option value="0">No customers registered yet</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-group" id="ifs_agent_select_box" style="display:none;">
                            <label for="client_id_agent">Select B2B Partner Agency <span class="required">*</span></label>
                            <div class="ifs-input-icon-wrap">
                                <span class="dashicons dashicons-building"></span>
                                <select name="client_id_agent" id="client_id_agent" class="ifs-input-field ifs-select-styled">
                                    <?php if ( $agents ) : foreach ( $agents as $agt ) : ?>
                                        <option value="<?php echo $agt->id; ?>"><?php echo esc_html( $agt->agency_name ); ?></option>
                                    <?php endforeach; else : ?>
                                        <option value="0">No B2B agents registered</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <input type="hidden" name="client_id" id="ifs_final_client_id" value="<?php echo !empty($customers) ? $customers[0]->id : 0; ?>">
                    </div>
                </div>

                <div class="ifs-section-divider"></div>

                <!-- Section 2: Line Items Dynamic Repeater -->
                <div class="ifs-invoice-section">
                    <div class="section-title-wrap">
                        <div class="section-icon bg-indigo"><span class="dashicons dashicons-list-view"></span></div>
                        <div>
                            <h4>Itemized Service Breakdown</h4>
                            <p>Add individual service components such as flight sectors, visa processing, hotels, or packages.</p>
                        </div>
                    </div>

                    <div class="ifs-items-table-wrapper">
                        <table class="ifs-invoice-table" id="ifsInvoiceItemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 48%;">Service / Product Description</th>
                                    <th style="width: 14%; text-align: center;">Qty</th>
                                    <th style="width: 18%; text-align: right;">Unit Price (৳)</th>
                                    <th style="width: 15%; text-align: right;">Total Amount (৳)</th>
                                    <th style="width: 5%; text-align: center;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <input type="text" name="item_desc[]" required placeholder="e.g. Air Ticket DAC-DXB on Emirates (EK-585)" class="ifs-input-cell">
                                    </td>
                                    <td>
                                        <input type="number" name="item_qty[]" class="ifs-input-cell item_qty font-mono" value="1" min="1">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="item_price[]" class="ifs-input-cell item_price font-mono" value="0.00">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="item_total[]" class="ifs-input-cell item_total font-mono font-bold" value="0.00" readonly>
                                    </td>
                                    <td style="text-align: center;">
                                        <button type="button" class="ifs-btn-row-del remove-row" title="Delete Row">&times;</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="ifs-add-row-bar">
                        <button type="button" id="ifs_add_item_btn" class="ifs-btn-add-line">
                            <span class="dashicons dashicons-plus-alt2"></span> Add Service Line Item
                        </button>
                    </div>
                </div>

                <div class="ifs-section-divider"></div>

                <!-- Section 3: Totals, Settlement & Notes -->
                <div class="ifs-summary-split-grid">
                    <div class="ifs-notes-card">
                        <div class="notes-header">
                            <span class="dashicons dashicons-info"></span>
                            <h4>Automated Settlement Rules</h4>
                        </div>
                        <p class="notes-body">
                            If Received Amount is greater than <strong>৳0.00</strong>, the system will automatically create an <strong>Income Voucher</strong> in the General Cash Ledger under reference number <em>#INV-AUTO</em>.
                        </p>
                        <div class="ifs-payment-method-wrap">
                            <label for="payment_method">Receiving Payment Method</label>
                            <div class="ifs-input-icon-wrap">
                                <span class="dashicons dashicons-chart-pie"></span>
                                <select name="payment_method" id="payment_method" class="ifs-input-field ifs-select-styled">
                                    <option value="Cash">Cash Drawer</option>
                                    <option value="Bank Transfer">Bank Wire Transfer</option>
                                    <option value="bKash / Nagad">Mobile Banking (bKash/Nagad)</option>
                                    <option value="Cheque">Bank Cheque</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="ifs-calculation-matrix-card">
                        <div class="calc-row">
                            <span class="calc-lbl">Gross Subtotal:</span>
                            <div class="calc-input-wrap">
                                <span class="currency-prefix">৳</span>
                                <input type="number" step="0.01" name="subtotal" id="ifs_inv_subtotal" readonly value="0.00" class="calc-field font-mono">
                            </div>
                        </div>

                        <div class="calc-row">
                            <span class="calc-lbl">Special Discount (-):</span>
                            <div class="calc-input-wrap">
                                <span class="currency-prefix">৳</span>
                                <input type="number" step="0.01" name="discount" id="ifs_inv_discount" value="0.00" class="calc-field font-mono">
                            </div>
                        </div>

                        <div class="calc-row net-total-row">
                            <span class="calc-lbl">Net Payable Total:</span>
                            <div class="calc-input-wrap">
                                <span class="currency-prefix color-emerald">৳</span>
                                <input type="number" step="0.01" id="ifs_inv_net_total" readonly value="0.00" class="calc-field font-mono font-bold color-emerald net-field">
                            </div>
                        </div>

                        <div class="calc-row received-row">
                            <span class="calc-lbl">Received Amount:</span>
                            <div class="calc-input-wrap">
                                <span class="currency-prefix">৳</span>
                                <input type="number" step="0.01" name="paid_amount" id="ifs_inv_paid" value="0.00" class="calc-field font-mono font-bold color-blue">
                            </div>
                        </div>

                        <div class="calc-row due-row">
                            <span class="calc-lbl">Balance Due:</span>
                            <div class="calc-input-wrap">
                                <span class="currency-prefix color-rose">৳</span>
                                <input type="number" step="0.01" id="ifs_inv_due_total" readonly value="0.00" class="calc-field font-mono font-bold color-rose">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Action Buttons -->
                <div class="ifs-invoice-footer">
                    <a href="<?php echo esc_url( $back_url ); ?>" class="ifs-btn-cancel">Discard &amp; Return</a>
                    <button type="submit" name="ifs_invoice_submit" class="ifs-btn-submit">
                        <span class="dashicons dashicons-saved"></span> Generate &amp; Lock Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modern Responsive Stylesheet -->
    <style>
        .ifs-invoice-workspace {
            max-width: 1100px;
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
        .toast-link { color: #003376; text-decoration: underline; font-weight: 700; margin-left: 6px; }

        /* Form Hero Header */
        .ifs-invoice-header-card {
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
        .ifs-invoice-badge {
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
            margin-bottom: 6px;
            border: 1px solid #dbeafe;
        }
        .ifs-invoice-badge .dashicons { font-size: 13px; width: 13px; height: 13px; }
        .header-title-group h2 { margin: 0 0 4px 0; font-size: 21px; font-weight: 900; color: #0f172a; letter-spacing: -0.3px; }
        .header-title-group p { margin: 0; font-size: 13px; color: #64748b; }

        .ifs-btn-back {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #334155;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .ifs-btn-back:hover { background: #e2e8f0; color: #0f172a; }
        .ifs-btn-back .dashicons { font-size: 16px; width: 16px; height: 16px; }

        /* Master Form Card */
        .ifs-invoice-form-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
        }

        .ifs-invoice-section { margin-bottom: 26px; }
        .section-title-wrap {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 20px;
        }
        .section-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            flex-shrink: 0;
        }
        .section-icon.bg-blue    { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); }
        .section-icon.bg-indigo  { background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); }
        .section-icon .dashicons { font-size: 20px; width: 20px; height: 20px; }

        .section-title-wrap h4 { margin: 0; font-size: 15.5px; font-weight: 800; color: #0f172a; }
        .section-title-wrap p { margin: 2px 0 0 0; font-size: 12.5px; color: #64748b; }

        .ifs-section-divider {
            height: 1px;
            background: #f1f5f9;
            margin: 26px 0;
        }

        /* Client Grid */
        .ifs-client-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        .ifs-field-group { display: flex; flex-direction: column; gap: 6px; }
        .ifs-field-group label {
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .ifs-field-group label .required { color: #dc2626; }

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
            padding: 10px 14px 10px 38px !important;
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
            background-position: right 12px center;
            background-size: 14px;
            padding-right: 36px !important;
            cursor: pointer;
        }

        /* Items Table */
        .ifs-items-table-wrapper {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 12px;
        }
        .ifs-invoice-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .ifs-invoice-table thead th {
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 14px;
            border-bottom: 1px solid #e2e8f0;
        }
        .ifs-invoice-table tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .ifs-invoice-table tbody tr:last-child td { border-bottom: none; }

        .ifs-input-cell {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 13px;
            color: #0f172a;
            background: #ffffff;
            outline: none;
            box-sizing: border-box;
        }
        .ifs-input-cell:focus { border-color: #003376; }
        .ifs-input-cell.item_qty { text-align: center; }
        .ifs-input-cell.item_price, .ifs-input-cell.item_total { text-align: right; }
        .ifs-input-cell.item_total { background: #f8fafc; color: #003376; }

        .ifs-btn-row-del {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
            border-radius: 6px;
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            font-weight: 700;
            transition: all 0.15s ease;
        }
        .ifs-btn-row-del:hover { background: #fecaca; color: #b91c1c; }

        .ifs-add-row-bar { display: flex; justify-content: flex-start; margin-top: 10px; }
        .ifs-btn-add-line {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            color: #334155;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
        }
        .ifs-btn-add-line:hover { background: #eff6ff; color: #003376; border-color: #93c5fd; }
        .ifs-btn-add-line .dashicons { font-size: 14px; width: 14px; height: 14px; }

        /* Summary Split Grid */
        .ifs-summary-split-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 24px;
            align-items: flex-start;
        }
        @media (max-width: 860px) {
            .ifs-summary-split-grid { grid-template-columns: 1fr; }
        }

        .ifs-notes-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
        }
        .notes-header { display: flex; align-items: center; gap: 6px; color: #003376; margin-bottom: 6px; }
        .notes-header .dashicons { font-size: 18px; width: 18px; height: 18px; }
        .notes-header h4 { margin: 0; font-size: 14px; font-weight: 800; color: #0f172a; }
        .notes-body { margin: 0 0 16px 0; font-size: 12.5px; color: #64748b; line-height: 1.5; }

        .ifs-payment-method-wrap label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 4px;
        }

        .ifs-calculation-matrix-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .calc-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 8px;
            border-bottom: 1px solid #f1f5f9;
        }
        .calc-lbl { font-size: 12.5px; font-weight: 600; color: #475569; }
        .calc-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
            width: 160px;
        }
        .currency-prefix {
            position: absolute;
            left: 10px;
            color: #64748b;
            font-weight: 700;
            font-size: 13px;
            pointer-events: none;
        }
        .calc-field {
            width: 100%;
            padding: 6px 10px 6px 26px !important;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 13.5px;
            text-align: right;
            box-sizing: border-box;
            color: #0f172a;
        }
        .calc-field:focus { border-color: #003376; outline: none; }

        .net-total-row {
            border-top: 2px solid #e2e8f0;
            padding-top: 10px;
            border-bottom: 2px solid #e2e8f0;
        }
        .net-total-row .calc-lbl { font-weight: 800; color: #0f172a; }
        .net-field { background: #ecfdf5; border-color: #a7f3d0; }

        .received-row .calc-lbl { color: #003376; font-weight: 700; }
        .due-row { border-bottom: none; }
        .due-row .calc-lbl { color: #dc2626; font-weight: 700; }

        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .font-bold { font-weight: 700; }
        .color-emerald { color: #059669 !important; }
        .color-rose { color: #dc2626 !important; }
        .color-blue { color: #003376 !important; }

        /* Footer */
        .ifs-invoice-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .ifs-btn-cancel {
            color: #64748b;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            transition: color 0.15s ease;
        }
        .ifs-btn-cancel:hover { color: #0f172a; }

        .ifs-btn-submit {
            background: linear-gradient(135deg, #003376 0%, #0284c7 100%);
            color: #ffffff !important;
            border: none;
            padding: 12px 26px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 12px rgba(0, 51, 118, 0.25);
            transition: all 0.2s ease;
        }
        .ifs-btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(0, 51, 118, 0.35);
        }
        .ifs-btn-submit .dashicons { font-size: 16px; width: 16px; height: 16px; }
    </style>

    <!-- Dynamic Calculator Script -->
    <script>
    jQuery(document).ready(function($) {
        // Toggle Client Selection
        $('#ifs_client_type').on('change', function() {
            if ($(this).val() === 'Customer') {
                $('#ifs_customer_select_box').show();
                $('#ifs_agent_select_box').hide();
                $('#ifs_final_client_id').val($('#client_id_customer').val());
            } else {
                $('#ifs_customer_select_box').hide();
                $('#ifs_agent_select_box').show();
                $('#ifs_final_client_id').val($('#client_id_agent').val());
            }
        });

        $('#client_id_customer').on('change', function() {
            if ($('#ifs_client_type').val() === 'Customer') {
                $('#ifs_final_client_id').val($(this).val());
            }
        });

        $('#client_id_agent').on('change', function() {
            if ($('#ifs_client_type').val() === 'Agent') {
                $('#ifs_final_client_id').val($(this).val());
            }
        });

        // Dynamic Calculations
        function calculateInvoice() {
            var subtotal = 0;
            $('#ifsInvoiceItemsTable tbody tr').each(function() {
                var qty = parseFloat($(this).find('.item_qty').val()) || 0;
                var price = parseFloat($(this).find('.item_price').val()) || 0;
                var total = qty * price;
                $(this).find('.item_total').val(total.toFixed(2));
                subtotal += total;
            });

            $('#ifs_inv_subtotal').val(subtotal.toFixed(2));
            var discount = parseFloat($('#ifs_inv_discount').val()) || 0;
            var net = subtotal - discount;
            if (net < 0) net = 0;
            $('#ifs_inv_net_total').val(net.toFixed(2));

            var paid = parseFloat($('#ifs_inv_paid').val()) || 0;
            var due = net - paid;
            $('#ifs_inv_due_total').val(due.toFixed(2));
        }

        $(document).on('input', '.item_qty, .item_price, #ifs_inv_discount, #ifs_inv_paid', calculateInvoice);

        // Add Item Row
        $('#ifs_add_item_btn').on('click', function() {
            var row = `<tr>
                <td><input type="text" name="item_desc[]" required placeholder="Service description..." class="ifs-input-cell"></td>
                <td><input type="number" name="item_qty[]" class="ifs-input-cell item_qty font-mono" value="1" min="1"></td>
                <td><input type="number" step="0.01" name="item_price[]" class="ifs-input-cell item_price font-mono" value="0.00"></td>
                <td><input type="number" step="0.01" name="item_total[]" class="ifs-input-cell item_total font-mono font-bold" value="0.00" readonly></td>
                <td style="text-align: center;"><button type="button" class="ifs-btn-row-del remove-row" title="Delete Row">&times;</button></td>
            </tr>`;
            $('#ifsInvoiceItemsTable tbody').append(row);
        });

        // Remove Row
        $(document).on('click', '.remove-row', function() {
            if ($('#ifsInvoiceItemsTable tbody tr').length > 1) {
                $(this).closest('tr').remove();
                calculateInvoice();
            }
        });
    });
    </script>
    <?php
}