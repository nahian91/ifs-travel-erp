<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_invoice_create_page' ) ) {
    /**
     * Enterprise Next-Gen Invoice & Money Receipt Creation Workspace
     * Flat Minimal UI: No Shadows, Strict 42px Equal Field Heights, Normalized Controls
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

            $client_type    = isset( $_POST['client_type'] ) ? sanitize_text_field( wp_unslash( $_POST['client_type'] ) ) : 'Customer';
            $client_id      = ( 'Customer' === $client_type ) ? ( isset( $_POST['client_id_customer'] ) ? absint( wp_unslash( $_POST['client_id_customer'] ) ) : 0 ) : ( isset( $_POST['client_id_agent'] ) ? absint( wp_unslash( $_POST['client_id_agent'] ) ) : 0 );
            $invoice_date   = isset( $_POST['invoice_date'] ) ? sanitize_text_field( wp_unslash( $_POST['invoice_date'] ) ) : gmdate( 'Y-m-d' );
            $due_date       = isset( $_POST['due_date'] ) ? sanitize_text_field( wp_unslash( $_POST['due_date'] ) ) : gmdate( 'Y-m-d', strtotime( '+7 days' ) );
            
            $subtotal       = isset( $_POST['subtotal'] ) ? (float) wp_unslash( $_POST['subtotal'] ) : 0;
            $discount       = isset( $_POST['discount'] ) ? (float) wp_unslash( $_POST['discount'] ) : 0;
            $net_total      = max( 0, $subtotal - $discount );
            $paid_amount    = isset( $_POST['paid_amount'] ) ? (float) wp_unslash( $_POST['paid_amount'] ) : 0;
            $due_amount     = max( 0, $net_total - $paid_amount );

            $pay_method     = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : 'Cash';
            $transaction_id = isset( $_POST['transaction_id'] ) ? sanitize_text_field( wp_unslash( $_POST['transaction_id'] ) ) : '';
            $invoice_terms  = isset( $_POST['invoice_terms'] ) ? sanitize_textarea_field( wp_unslash( $_POST['invoice_terms'] ) ) : '';

            $payment_status = 'Unpaid';
            if ( $paid_amount >= $net_total && $net_total > 0 ) {
                $payment_status = 'Paid';
            } elseif ( $paid_amount > 0 && $paid_amount < $net_total ) {
                $payment_status = 'Partial';
            }

            // Generate Standard Invoice Number
            $invoice_count = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table_invoices}" );
            $invoice_no    = 'INV-' . gmdate( 'Y' ) . '-' . str_pad( (string) ( $invoice_count + 1 ), 4, '0', STR_PAD_LEFT );

            // Dynamic Line Items Handling
            $items = array();
            $raw_descs = isset( $_POST['item_desc'] ) && is_array( $_POST['item_desc'] ) ? wp_unslash( $_POST['item_desc'] ) : array();
            $raw_qtys  = isset( $_POST['item_qty'] ) && is_array( $_POST['item_qty'] ) ? wp_unslash( $_POST['item_qty'] ) : array();
            $raw_prices = isset( $_POST['item_price'] ) && is_array( $_POST['item_price'] ) ? wp_unslash( $_POST['item_price'] ) : array();
            $raw_totals = isset( $_POST['item_total'] ) && is_array( $_POST['item_total'] ) ? wp_unslash( $_POST['item_total'] ) : array();

            if ( ! empty( $raw_descs ) ) {
                foreach ( $raw_descs as $key => $desc ) {
                    if ( ! empty( trim( (string) $desc ) ) ) {
                        $qty        = max( 1, intval( $raw_qtys[ $key ] ?? 1 ) );
                        $unit_price = (float) ( $raw_prices[ $key ] ?? 0 );
                        $total      = (float) ( $raw_totals[ $key ] ?? ( $qty * $unit_price ) );

                        $items[] = array(
                            'description' => sanitize_text_field( $desc ),
                            'qty'         => $qty,
                            'unit_price'  => $unit_price,
                            'total'       => $total,
                        );
                    }
                }
            }

            if ( empty( $client_id ) ) {
                $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Please select a valid billing customer or sub-agent.', 'ifs-travel-erp' ) . '</div>';
            } elseif ( empty( $items ) ) {
                $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Please add at least one line item to the invoice.', 'ifs-travel-erp' ) . '</div>';
            } else {
                $inserted = $wpdb->insert(
                    $table_invoices,
                    array(
                        'invoice_no'     => $invoice_no,
                        'client_type'    => $client_type,
                        'client_id'      => $client_id,
                        'invoice_date'   => ! empty( $invoice_date ) ? $invoice_date : current_time( 'mysql' ),
                        'due_date'       => ! empty( $due_date ) ? $due_date : current_time( 'mysql' ),
                        'subtotal'       => $subtotal,
                        'discount'       => $discount,
                        'net_total'      => $net_total,
                        'paid_amount'    => $paid_amount,
                        'due_amount'     => $due_amount,
                        'payment_status' => $payment_status,
                        'payment_method' => $pay_method,
                        'transaction_id' => $transaction_id,
                        'terms_notes'    => $invoice_terms,
                        'items_json'     => wp_json_encode( $items ),
                        'created_by'     => get_current_user_id(),
                        'created_at'     => current_time( 'mysql' ),
                    ),
                    array( '%s', '%s', '%d', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
                );

                if ( $inserted ) {
                    $inv_id = $wpdb->insert_id;

                    // Auto-post received payment to General Ledger if paid > 0
                    if ( $paid_amount > 0 ) {
                        $wpdb->insert(
                            $table_ledger,
                            array(
                                'transaction_type' => 'Income',
                                'category'         => 'Invoice Payment',
                                'amount'           => $paid_amount,
                                'payment_method'   => $pay_method,
                                'reference_no'     => $invoice_no,
                                'description'      => 'Payment against Invoice ' . $invoice_no . ( ! empty( $transaction_id ) ? ' (Ref: ' . $transaction_id . ')' : '' ),
                                'transaction_date' => $invoice_date . ' ' . current_time( 'H:i:s' ),
                                'logged_by'        => get_current_user_id(),
                            ),
                            array( '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%d' )
                        );
                    }

                    if ( function_exists( 'ifs_terp_log_activity' ) ) {
                        ifs_terp_log_activity( "Created Invoice {$invoice_no} ({$client_type} ID: {$client_id}) for ৳" . number_format( $net_total, 2 ) );
                    }

                    $view_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=accounts&sub=view_invoice&id=' . $inv_id );
                    $message  = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Invoice generated successfully.', 'ifs-travel-erp' ) . ' <a href="' . esc_url( $view_url ) . '" class="toast-link">' . esc_html__( 'View Invoice &rarr;', 'ifs-travel-erp' ) . '</a></div>';
                } else {
                    $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Database error: Unable to generate invoice record.', 'ifs-travel-erp' ) . '</div>';
                }
            }
        }

        $customers = $wpdb->get_results( "SELECT id, title, full_name, mobile FROM {$table_customers} ORDER BY full_name ASC" );
        $agents    = $wpdb->get_results( "SELECT id, agency_name, mobile, current_balance FROM {$table_agents} ORDER BY agency_name ASC" );
        $back_url  = admin_url( 'admin.php?page=ifs_travel_erp&tab=accounts' );
        ?>

        <div class="wrap ifs-invoice-workspace">
            <?php echo wp_kses_post( $message ); ?>

            <!-- Form Top Bar -->
            <div class="ifs-invoice-header-card">
                <div class="header-title-group">
                    <span class="ifs-invoice-badge">
                        <span class="dashicons dashicons-money-alt"></span> <?php esc_html_e( 'Billing & Accounts', 'ifs-travel-erp' ); ?>
                    </span>
                    <h2><?php esc_html_e( 'Create Sales Invoice', 'ifs-travel-erp' ); ?></h2>
                    <p><?php esc_html_e( 'Generate itemized travel invoices and record auto-settlement vouchers in the general ledger.', 'ifs-travel-erp' ); ?></p>
                </div>
                <div class="header-actions">
                    <a href="<?php echo esc_url( $back_url ); ?>" class="ifs-btn-secondary">
                        <span class="dashicons dashicons-arrow-left-alt2"></span> <?php esc_html_e( 'Back to Invoices', 'ifs-travel-erp' ); ?>
                    </a>
                </div>
            </div>

            <!-- Master Invoice Form Card -->
            <div class="ifs-invoice-form-card">
                <form method="post" action="" id="ifsInvoiceForm">
                    <?php wp_nonce_field( 'ifs_invoice_create_action', 'ifs_invoice_nonce' ); ?>

                    <!-- Section 1: Client & Dates -->
                    <div class="ifs-invoice-section">
                        <div class="section-title-wrap">
                            <div class="section-icon"><span class="dashicons dashicons-admin-users"></span></div>
                            <div>
                                <h4><?php esc_html_e( 'Recipient & Billing Dates', 'ifs-travel-erp' ); ?></h4>
                                <p><?php esc_html_e( 'Select the billed party, invoice issuance date, and credit payment term.', 'ifs-travel-erp' ); ?></p>
                            </div>
                        </div>

                        <div class="ifs-client-grid">
                            <div class="ifs-field-group">
                                <label class="ifs-field-label" for="ifs_client_type"><?php esc_html_e( 'Client Type', 'ifs-travel-erp' ); ?> <span class="required">*</span></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-category field-icon"></span>
                                    <select name="client_type" id="ifs_client_type" class="ifs-input-field">
                                        <option value="Customer" selected><?php esc_html_e( 'Customer (B2C)', 'ifs-travel-erp' ); ?></option>
                                        <option value="Agent"><?php esc_html_e( 'Sub-Agent (B2B)', 'ifs-travel-erp' ); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-group" id="ifs_customer_select_box">
                                <label class="ifs-field-label" for="client_id_customer"><?php esc_html_e( 'Passenger', 'ifs-travel-erp' ); ?> <span class="required">*</span></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-businessman field-icon"></span>
                                    <select name="client_id_customer" id="client_id_customer" class="ifs-input-field">
                                        <?php if ( ! empty( $customers ) ) : foreach ( $customers as $cus ) : 
                                            $t_prefix = ! empty( $cus->title ) ? ucfirst( strtolower( $cus->title ) ) . '. ' : '';
                                            $f_name   = $t_prefix . mb_convert_case( $cus->full_name, MB_CASE_TITLE, 'UTF-8' );
                                        ?>
                                            <option value="<?php echo esc_attr( $cus->id ); ?>"><?php echo esc_html( $f_name . ' (' . $cus->mobile . ')' ); ?></option>
                                        <?php endforeach; else : ?>
                                            <option value="0"><?php esc_html_e( 'No customers registered', 'ifs-travel-erp' ); ?></option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-group" id="ifs_agent_select_box" style="display:none;">
                                <label class="ifs-field-label" for="client_id_agent"><?php esc_html_e( 'Sub-Agent', 'ifs-travel-erp' ); ?> <span class="required">*</span></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-building field-icon"></span>
                                    <select name="client_id_agent" id="client_id_agent" class="ifs-input-field">
                                        <?php if ( ! empty( $agents ) ) : foreach ( $agents as $agt ) : ?>
                                            <option value="<?php echo esc_attr( $agt->id ); ?>"><?php echo esc_html( $agt->agency_name . ' (Bal: ৳' . number_format( (float) $agt->current_balance, 2 ) . ')' ); ?></option>
                                        <?php endforeach; else : ?>
                                            <option value="0"><?php esc_html_e( 'No B2B agents registered', 'ifs-travel-erp' ); ?></option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-group">
                                <label class="ifs-field-label" for="inp_invoice_date"><?php esc_html_e( 'Invoice Date', 'ifs-travel-erp' ); ?> <span class="required">*</span></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-calendar-alt field-icon"></span>
                                    <input type="date" name="invoice_date" id="inp_invoice_date" required value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" class="ifs-input-field font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-group">
                                <label class="ifs-field-label" for="inp_due_date"><?php esc_html_e( 'Due Date', 'ifs-travel-erp' ); ?> <span class="required">*</span></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-clock field-icon"></span>
                                    <input type="date" name="due_date" id="inp_due_date" required value="<?php echo esc_attr( gmdate( 'Y-m-d', strtotime( '+7 days' ) ) ); ?>" class="ifs-input-field font-mono">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ifs-section-divider"></div>

                    <!-- Section 2: Line Items Dynamic Repeater -->
                    <div class="ifs-invoice-section">
                        <div class="section-title-wrap">
                            <div class="section-icon"><span class="dashicons dashicons-list-view"></span></div>
                            <div>
                                <h4><?php esc_html_e( 'Itemized Line Items', 'ifs-travel-erp' ); ?></h4>
                                <p><?php esc_html_e( 'Air tickets, visa fees, hotel stays, or tour packages included in this invoice.', 'ifs-travel-erp' ); ?></p>
                            </div>
                        </div>

                        <div class="ifs-items-table-wrapper">
                            <table class="ifs-invoice-table" id="ifsInvoiceItemsTable">
                                <thead>
                                    <tr>
                                        <th style="width: 50%;"><?php esc_html_e( 'Service Description', 'ifs-travel-erp' ); ?></th>
                                        <th style="width: 12%; text-align: center;"><?php esc_html_e( 'Qty', 'ifs-travel-erp' ); ?></th>
                                        <th style="width: 18%; text-align: right;"><?php esc_html_e( 'Rate (৳)', 'ifs-travel-erp' ); ?></th>
                                        <th style="width: 15%; text-align: right;"><?php esc_html_e( 'Total (৳)', 'ifs-travel-erp' ); ?></th>
                                        <th style="width: 5%; text-align: center;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <div class="ifs-field-wrap">
                                                <input type="text" name="item_desc[]" required placeholder="<?php esc_attr_e( 'e.g. Air Ticket DAC-DXB on Emirates (EK-585)', 'ifs-travel-erp' ); ?>" class="ifs-input-field">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="ifs-field-wrap">
                                                <input type="number" name="item_qty[]" class="ifs-input-field item_qty font-mono" value="1" min="1">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="ifs-field-wrap">
                                                <input type="number" step="0.01" name="item_price[]" class="ifs-input-field item_price font-mono" value="0.00">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="ifs-field-wrap">
                                                <input type="number" step="0.01" name="item_total[]" class="ifs-input-field item_total font-mono font-bold bg-light" value="0.00" readonly>
                                            </div>
                                        </td>
                                        <td style="text-align: center;">
                                            <button type="button" class="ifs-btn-row-del remove-row" title="<?php esc_attr_e( 'Delete Row', 'ifs-travel-erp' ); ?>">&times;</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="ifs-add-row-bar">
                            <button type="button" id="ifs_add_item_btn" class="ifs-btn-add-line">
                                <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Item', 'ifs-travel-erp' ); ?>
                            </button>
                        </div>
                    </div>

                    <div class="ifs-section-divider"></div>

                    <!-- Section 3: Calculations, Settlement & Terms -->
                    <div class="ifs-summary-split-grid">
                        
                        <!-- Left: Settlement & Terms -->
                        <div class="ifs-notes-card">
                            <div class="notes-header">
                                <span class="dashicons dashicons-money-alt"></span>
                                <h4><?php esc_html_e( 'Payment & Terms', 'ifs-travel-erp' ); ?></h4>
                            </div>
                            <p class="notes-body">
                                <?php esc_html_e( 'Entering a payment here automatically posts an Income Voucher to your accounts ledger.', 'ifs-travel-erp' ); ?>
                            </p>

                            <div class="ifs-payment-grid">
                                <div class="ifs-field-group">
                                    <label class="ifs-field-label" for="payment_method"><?php esc_html_e( 'Pay Method', 'ifs-travel-erp' ); ?></label>
                                    <div class="ifs-field-wrap">
                                        <span class="dashicons dashicons-vault field-icon"></span>
                                        <select name="payment_method" id="payment_method" class="ifs-input-field">
                                            <option value="Cash"><?php esc_html_e( 'Cash Drawer', 'ifs-travel-erp' ); ?></option>
                                            <option value="Bank Transfer"><?php esc_html_e( 'Bank Transfer (BEFTN/NPSB)', 'ifs-travel-erp' ); ?></option>
                                            <option value="bKash / MFS"><?php esc_html_e( 'bKash / Nagad / Rocket', 'ifs-travel-erp' ); ?></option>
                                            <option value="Cheque"><?php esc_html_e( 'Bank Cheque', 'ifs-travel-erp' ); ?></option>
                                            <option value="Credit Card"><?php esc_html_e( 'POS / Credit Card', 'ifs-travel-erp' ); ?></option>
                                            <option value="Agent Balance"><?php esc_html_e( 'Agent Credit Ledger', 'ifs-travel-erp' ); ?></option>
                                        </select>
                                    </div>
                                </div>

                                <div class="ifs-field-group">
                                    <label class="ifs-field-label" for="inp_txn_id"><?php esc_html_e( 'Txn ID', 'ifs-travel-erp' ); ?></label>
                                    <div class="ifs-field-wrap">
                                        <span class="dashicons dashicons-tag field-icon"></span>
                                        <input type="text" name="transaction_id" id="inp_txn_id" placeholder="<?php esc_attr_e( 'e.g. TRX-987412 / Chq#120', 'ifs-travel-erp' ); ?>" class="ifs-input-field font-mono">
                                    </div>
                                </div>
                            </div>

                            <div class="ifs-field-group" style="margin-top: 14px;">
                                <label class="ifs-field-label" for="inp_invoice_terms"><?php esc_html_e( 'Terms & Conditions', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-textarea-wrap">
                                    <textarea name="invoice_terms" id="inp_invoice_terms" rows="2" placeholder="<?php esc_attr_e( 'e.g. Non-refundable ticket after departure. Cheque payments are subject to realization.', 'ifs-travel-erp' ); ?>" class="ifs-input-field"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Math Calculation Matrix -->
                        <div class="ifs-calculation-matrix-card">
                            <div class="calc-row">
                                <span class="calc-lbl"><?php esc_html_e( 'Subtotal:', 'ifs-travel-erp' ); ?></span>
                                <div class="ifs-field-wrap calc-input-wrap">
                                    <span class="currency-prefix">৳</span>
                                    <input type="number" step="0.01" name="subtotal" id="ifs_inv_subtotal" readonly value="0.00" class="ifs-input-field font-mono">
                                </div>
                            </div>

                            <div class="calc-row">
                                <span class="calc-lbl"><?php esc_html_e( 'Discount (-):', 'ifs-travel-erp' ); ?></span>
                                <div class="ifs-field-wrap calc-input-wrap">
                                    <span class="currency-prefix">৳</span>
                                    <input type="number" step="0.01" name="discount" id="ifs_inv_discount" value="0.00" class="ifs-input-field font-mono">
                                </div>
                            </div>

                            <div class="calc-row net-total-row">
                                <span class="calc-lbl"><?php esc_html_e( 'Net Total:', 'ifs-travel-erp' ); ?></span>
                                <div class="ifs-field-wrap calc-input-wrap">
                                    <span class="currency-prefix color-emerald">৳</span>
                                    <input type="number" step="0.01" id="ifs_inv_net_total" readonly value="0.00" class="ifs-input-field font-mono font-bold color-emerald net-field">
                                </div>
                            </div>

                            <div class="calc-row received-row">
                                <span class="calc-lbl"><?php esc_html_e( 'Paid Amount:', 'ifs-travel-erp' ); ?></span>
                                <div class="ifs-field-wrap calc-input-wrap">
                                    <span class="currency-prefix color-blue">৳</span>
                                    <input type="number" step="0.01" name="paid_amount" id="ifs_inv_paid" value="0.00" class="ifs-input-field font-mono font-bold color-blue">
                                </div>
                            </div>

                            <div class="calc-row due-row">
                                <span class="calc-lbl"><?php esc_html_e( 'Due Amount:', 'ifs-travel-erp' ); ?></span>
                                <div class="ifs-field-wrap calc-input-wrap">
                                    <span class="currency-prefix color-rose">৳</span>
                                    <input type="number" step="0.01" id="ifs_inv_due_total" readonly value="0.00" class="ifs-input-field font-mono font-bold color-rose">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Action Buttons -->
                    <div class="ifs-action-strip">
                        <a href="<?php echo esc_url( $back_url ); ?>" class="ifs-btn-secondary"><?php esc_html_e( 'Cancel', 'ifs-travel-erp' ); ?></a>
                        <button type="submit" name="ifs_invoice_submit" class="ifs-btn-primary">
                            <span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Generate Invoice', 'ifs-travel-erp' ); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Stylesheet: Flat Minimal UI, Zero Shadows & Strict 42px Uniform Field Heights -->
        <style>
            .ifs-invoice-workspace {
                max-width: 1100px;
                margin: 20px auto;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                color: #0f172a;
                box-sizing: border-box;
            }
            .ifs-invoice-workspace *,
            .ifs-invoice-workspace *::before,
            .ifs-invoice-workspace *::after {
                box-sizing: border-box;
                box-shadow: none !important;
                text-shadow: none !important;
            }

            .ifs-toast {
                padding: 13px 18px;
                border-radius: 10px;
                font-size: 13.5px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 22px;
            }
            .ifs-toast.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
            .ifs-toast.danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
            .ifs-toast .dashicons { font-size: 18px; width: 18px; height: 18px; }
            .toast-link { color: #003376; text-decoration: underline; font-weight: 700; margin-left: 6px; }

            .ifs-invoice-header-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                padding: 24px 28px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 18px;
                margin-bottom: 24px;
            }
            .ifs-invoice-badge {
                background: #eff6ff;
                color: #003376;
                padding: 3px 10px;
                border-radius: 6px;
                font-size: 11px;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                display: inline-flex;
                align-items: center;
                gap: 5px;
                margin-bottom: 6px;
                border: 1px solid #bfdbfe;
            }
            .ifs-invoice-badge .dashicons { font-size: 14px; width: 14px; height: 14px; }
            .header-title-group h2 { margin: 0 0 4px 0; font-size: 20px; font-weight: 800; color: #0f172a; }
            .header-title-group p { margin: 0; font-size: 13px; color: #64748b; }

            .ifs-invoice-form-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                padding: 28px;
            }

            .ifs-invoice-section { margin-bottom: 22px; }
            .section-title-wrap {
                display: flex;
                align-items: center;
                gap: 14px;
                margin-bottom: 20px;
            }
            .section-icon {
                width: 36px;
                height: 36px;
                border-radius: 9px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #003376;
                color: #ffffff;
                flex-shrink: 0;
            }
            .section-icon .dashicons { font-size: 18px; width: 18px; height: 18px; }

            .section-title-wrap h4 { margin: 0; font-size: 15.5px; font-weight: 800; color: #0f172a; }
            .section-title-wrap p { margin: 2px 0 0 0; font-size: 12.5px; color: #64748b; }

            .ifs-section-divider {
                height: 1px;
                background: #f1f5f9;
                margin: 26px 0;
            }

            .ifs-client-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 16px 18px;
            }
            .ifs-field-group { 
                display: flex; 
                flex-direction: column; 
                justify-content: flex-start;
                gap: 6px; 
                width: 100%;
            }
            .ifs-field-label { 
                font-size: 11px; 
                font-weight: 700; 
                color: #475569; 
                text-transform: capitalize; 
                letter-spacing: 0.3px; 
                line-height: 1.2;
            }
            .ifs-field-label .required { color: #e11d48; margin-left: 2px; }

            .ifs-field-wrap { 
                position: relative; 
                display: flex; 
                align-items: center; 
                width: 100%; 
                height: 42px; 
            }
            .ifs-field-wrap .field-icon {
                position: absolute; 
                left: 12px; 
                top: 50%;
                transform: translateY(-50%);
                color: #94a3b8; 
                font-size: 18px; 
                width: 18px; 
                height: 18px; 
                line-height: 18px;
                pointer-events: none; 
                z-index: 3; 
                transition: color 0.2s ease;
            }

            .ifs-input-field { 
                width: 100% !important; 
                height: 42px !important; 
                max-height: 42px !important; 
                min-height: 42px !important; 
                line-height: 40px !important; 
                padding: 0 14px 0 40px !important; 
                border: 1px solid #cbd5e1 !important; 
                border-radius: 8px !important; 
                font-size: 13.5px !important; 
                color: #0f172a !important; 
                background-color: #ffffff !important; 
                outline: none !important; 
                transition: border-color 0.2s ease, background-color 0.2s ease; 
                margin: 0 !important; 
                display: block; 
            }
            select.ifs-input-field { 
                appearance: none; 
                -webkit-appearance: none; 
                background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e") !important; 
                background-repeat: no-repeat !important; 
                background-position: right 12px center !important; 
                background-size: 14px !important; 
                padding-right: 36px !important; 
                cursor: pointer; 
            }
            input[type="date"].ifs-input-field {
                cursor: pointer;
            }
            input[type="date"].ifs-input-field::-webkit-calendar-picker-indicator {
                opacity: 0.6;
                cursor: pointer;
                margin-right: -4px;
            }
            .ifs-input-field:focus { 
                border-color: #003376 !important; 
                background-color: #f8fafc !important; 
            }
            .ifs-field-wrap:focus-within .field-icon { 
                color: #003376; 
            }

            .ifs-textarea-wrap { width: 100%; }
            .ifs-textarea-wrap textarea.ifs-input-field {
                width: 100% !important;
                height: auto !important;
                min-height: 64px !important;
                padding: 10px 14px !important;
                line-height: 1.5 !important;
                border-radius: 8px !important;
                resize: vertical;
            }

            .ifs-payment-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }
            @media (max-width: 600px) {
                .ifs-payment-grid { grid-template-columns: 1fr; }
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
                text-transform: capitalize;
                letter-spacing: 0.3px;
                padding: 12px 14px;
                border-bottom: 1px solid #e2e8f0;
            }
            .ifs-invoice-table tbody td {
                padding: 8px 10px;
                border-bottom: 1px solid #f1f5f9;
                vertical-align: middle;
            }
            .ifs-invoice-table tbody tr:last-child td { border-bottom: none; }

            .ifs-invoice-table .ifs-input-field {
                padding: 0 14px !important;
            }
            .ifs-invoice-table .item_qty { text-align: center; }
            .ifs-invoice-table .item_price, 
            .ifs-invoice-table .item_total { text-align: right; }

            .ifs-btn-row-del {
                background: #fee2e2;
                color: #dc2626;
                border: 1px solid #fecaca;
                border-radius: 8px;
                width: 42px;
                height: 42px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                font-size: 20px;
                line-height: 1;
                transition: background-color 0.2s ease, color 0.2s ease;
            }
            .ifs-btn-row-del:hover { background: #dc2626; color: #ffffff; border-color: #dc2626; }

            .ifs-add-row-bar { display: flex; justify-content: flex-start; margin-top: 10px; }
            .ifs-btn-add-line {
                background: #f8fafc;
                border: 1px solid #cbd5e1;
                color: #003376;
                height: 38px;
                padding: 0 16px;
                border-radius: 8px;
                font-size: 12.5px;
                font-weight: 700;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
            }
            .ifs-btn-add-line:hover { background: #003376; color: #ffffff; border-color: #003376; }
            .ifs-btn-add-line .dashicons { font-size: 14px; width: 14px; height: 14px; }

            /* Summary Split Grid */
            .ifs-summary-split-grid {
                display: grid;
                grid-template-columns: 1fr 390px;
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
            .notes-header h4 { margin: 0; font-size: 14px; font-weight: 800; color: #0f172a; text-transform: capitalize; }
            .notes-body { margin: 0 0 16px 0; font-size: 12.5px; color: #64748b; line-height: 1.5; }

            .ifs-calculation-matrix-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 16px 20px;
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            .calc-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding-bottom: 8px;
                border-bottom: 1px solid #f1f5f9;
            }
            .calc-lbl { font-size: 12.5px; font-weight: 600; color: #475569; text-transform: capitalize; }
            .calc-input-wrap {
                position: relative;
                display: flex;
                align-items: center;
                width: 170px;
                height: 42px;
            }
            .calc-input-wrap .ifs-input-field {
                text-align: right;
                padding-right: 14px !important;
                padding-left: 28px !important;
            }
            .currency-prefix {
                position: absolute;
                left: 10px;
                color: #64748b;
                font-weight: 700;
                font-size: 14px;
                pointer-events: none;
                z-index: 3;
            }

            .net-total-row {
                border-top: 2px solid #e2e8f0;
                padding-top: 10px;
                border-bottom: 2px solid #e2e8f0;
            }
            .net-total-row .calc-lbl { font-weight: 800; color: #0f172a; }
            .net-field { background: #ecfdf5 !important; border-color: #a7f3d0 !important; }

            .received-row .calc-lbl { color: #003376; font-weight: 700; }
            .due-row { border-bottom: none; }
            .due-row .calc-lbl { color: #dc2626; font-weight: 700; }

            .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important; }
            .font-bold { font-weight: 700; }
            .bg-light { background: #f8fafc !important; }
            .color-emerald { color: #059669 !important; }
            .color-rose { color: #dc2626 !important; }
            .color-blue { color: #003376 !important; }

            .ifs-action-strip {
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #f1f5f9;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 14px;
            }
            .ifs-btn-secondary {
                background: #f8fafc;
                color: #475569 !important;
                border: 1px solid #cbd5e1;
                height: 42px;
                padding: 0 20px;
                border-radius: 8px;
                font-size: 13px;
                font-weight: 600;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                transition: background-color 0.2s ease, color 0.2s ease;
            }
            .ifs-btn-secondary:hover { background: #f1f5f9; color: #0f172a; }

            .ifs-btn-primary {
                background: #003376;
                color: #ffffff !important;
                border: none;
                height: 42px;
                padding: 0 24px;
                border-radius: 8px;
                font-size: 13.5px;
                font-weight: 700;
                text-decoration: none;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: background-color 0.2s ease;
            }
            .ifs-btn-primary:hover { background: #0284c7; }
            .ifs-btn-primary .dashicons { font-size: 16px; width: 16px; height: 16px; }
        </style>

        <!-- Dynamic Calculator Script -->
        <script>
        jQuery(document).ready(function($) {
            // Toggle Client Selection
            $('#ifs_client_type').on('change', function() {
                if ($(this).val() === 'Customer') {
                    $('#ifs_customer_select_box').show();
                    $('#ifs_agent_select_box').hide();
                } else {
                    $('#ifs_customer_select_box').hide();
                    $('#ifs_agent_select_box').show();
                }
            });

            // Dynamic Calculations
            function calculateInvoice() {
                var subtotal = 0;
                $('#ifsInvoiceItemsTable tbody tr').each(function() {
                    var qty   = parseFloat($(this).find('.item_qty').val()) || 0;
                    var price = parseFloat($(this).find('.item_price').val()) || 0;
                    var total = qty * price;
                    $(this).find('.item_total').val(total.toFixed(2));
                    subtotal += total;
                });

                $('#ifs_inv_subtotal').val(subtotal.toFixed(2));
                var discount = parseFloat($('#ifs_inv_discount').val()) || 0;
                var net = Math.max(0, subtotal - discount);
                $('#ifs_inv_net_total').val(net.toFixed(2));

                var paid = parseFloat($('#ifs_inv_paid').val()) || 0;
                var due  = Math.max(0, net - paid);
                $('#ifs_inv_due_total').val(due.toFixed(2));
            }

            $(document).on('input', '.item_qty, .item_price, #ifs_inv_discount, #ifs_inv_paid', calculateInvoice);

            // Add Item Row
            $('#ifs_add_item_btn').on('click', function() {
                var row = `<tr>
                    <td>
                        <div class="ifs-field-wrap">
                            <input type="text" name="item_desc[]" required placeholder="Service description..." class="ifs-input-field">
                        </div>
                    </td>
                    <td>
                        <div class="ifs-field-wrap">
                            <input type="number" name="item_qty[]" class="ifs-input-field item_qty font-mono" value="1" min="1">
                        </div>
                    </td>
                    <td>
                        <div class="ifs-field-wrap">
                            <input type="number" step="0.01" name="item_price[]" class="ifs-input-field item_price font-mono" value="0.00">
                        </div>
                    </td>
                    <td>
                        <div class="ifs-field-wrap">
                            <input type="number" step="0.01" name="item_total[]" class="ifs-input-field item_total font-mono font-bold bg-light" value="0.00" readonly>
                        </div>
                    </td>
                    <td style="text-align: center;">
                        <button type="button" class="ifs-btn-row-del remove-row" title="Delete Row">&times;</button>
                    </td>
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
}