<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_invoice_create_page() {
    global $wpdb;
    $table_invoices  = $wpdb->prefix . 'iterp_invoices';
    $table_customers = $wpdb->prefix . 'iterp_customers';
    $table_agents    = $wpdb->prefix . 'iterp_agents';
    $table_ledger    = $wpdb->prefix . 'iterp_ledger';

    $message = '';

    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_invoice_submit'] ) ) {
        check_admin_referer( 'ifs_invoice_create_action', 'ifs_invoice_nonce' );

        $client_type = sanitize_text_field( $_POST['client_type'] );
        $client_id   = intval( $_POST['client_id'] );
        $subtotal    = floatval( $_POST['subtotal'] );
        $discount    = floatval( $_POST['discount'] );
        $net_total   = $subtotal - $discount;
        $paid_amount = floatval( $_POST['paid_amount'] );
        $due_amount  = $net_total - $paid_amount;

        $payment_status = 'Unpaid';
        if ( $paid_amount >= $net_total && $net_total > 0 ) {
            $payment_status = 'Paid';
        } elseif ( $paid_amount > 0 && $paid_amount < $net_total ) {
            $payment_status = 'Partial';
        }

        // Generate Standard Invoice Number (e.g. INV-2026-0001)
        $invoice_count = $wpdb->get_var( "SELECT COUNT(id) FROM $table_invoices" );
        $invoice_no    = 'INV-' . date('Y') . '-' . str_pad( ($invoice_count + 1), 4, '0', STR_PAD_LEFT );

        // Dynamic Line Items Handling
        $items = array();
        if ( ! empty( $_POST['item_desc'] ) && is_array( $_POST['item_desc'] ) ) {
            foreach ( $_POST['item_desc'] as $key => $desc ) {
                if ( ! empty( $desc ) ) {
                    $items[] = array(
                        'description' => sanitize_text_field( $desc ),
                        'qty'         => intval( $_POST['item_qty'][$key] ),
                        'unit_price'  => floatval( $_POST['item_price'][$key] ),
                        'total'       => floatval( $_POST['item_total'][$key] )
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
            )
        );

        if ( $inserted ) {
            $inv_id = $wpdb->insert_id;

            // Auto-post payment to General Ledger if paid > 0
            if ( $paid_amount > 0 ) {
                $wpdb->insert(
                    $table_ledger,
                    array(
                        'transaction_type' => 'Income',
                        'category'         => 'Invoice Payment',
                        'amount'           => $paid_amount,
                        'payment_method'   => sanitize_text_field( $_POST['payment_method'] ),
                        'reference_no'     => $invoice_no,
                        'description'      => 'Payment against Invoice ' . $invoice_no,
                        'transaction_date' => current_time( 'mysql' ),
                        'logged_by'        => get_current_user_id()
                    )
                );
            }

            if ( function_exists('ifs_terp_log_activity') ) {
                ifs_terp_log_activity( "Created Invoice " . $invoice_no . " for " . $client_type . " ID: " . $client_id );
            }

            $message = '<div class="notice notice-success is-dismissible"><p>Invoice generated successfully! <a href="' . admin_url( 'admin.php?page=ifs_travel_erp&tab=accounts&sub=view_invoice&id=' . $inv_id ) . '">View Invoice</a></p></div>';
        }
    }

    $customers = $wpdb->get_results( "SELECT id, full_name, mobile FROM $table_customers ORDER BY full_name ASC" );
    $agents    = $wpdb->get_results( "SELECT id, company_name FROM $table_agents ORDER BY company_name ASC" );
    ?>

    <?php echo $message; ?>

    <div class="arms-form-container" style="background:#fff; padding:25px; border-radius:8px; border:1px solid #e2e8f0; max-width: 950px; margin-top: 20px;">
        <h3 style="margin-top:0; color:#003376; border-bottom:1px solid #e2e8f0; padding-bottom:12px;">Create Sales Invoice & Money Receipt</h3>

        <form method="post" action="">
            <?php wp_nonce_field( 'ifs_invoice_create_action', 'ifs_invoice_nonce' ); ?>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 25px;">
                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Billing To (Client Type)</label>
                    <select name="client_type" id="ifs_client_type" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                        <option value="Customer">Retail Customer (B2C)</option>
                        <option value="Agent">Sub-Agent (B2B)</option>
                    </select>
                </div>

                <div id="ifs_customer_select_box">
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Select Customer *</label>
                    <select name="client_id_customer" id="client_id_customer" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                        <?php foreach ( $customers as $cus ) : ?>
                            <option value="<?php echo $cus->id; ?>"><?php echo esc_html( $cus->full_name . ' (' . $cus->mobile . ')' ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="ifs_agent_select_box" style="display:none;">
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Select B2B Agent *</label>
                    <select name="client_id_agent" id="client_id_agent" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                        <?php foreach ( $agents as $agt ) : ?>
                            <option value="<?php echo $agt->id; ?>"><?php echo esc_html( $agt->company_name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="hidden" name="client_id" id="ifs_final_client_id" value="<?php echo !empty($customers) ? $customers[0]->id : 0; ?>">
            </div>

            <!-- Invoice Line Items Dynamic Table -->
            <h4 style="color:#0f172a; margin-bottom: 10px;">Itemized Service Breakdown</h4>
            <table style="width:100%; border-collapse:collapse; margin-bottom: 20px;" id="ifsInvoiceItemsTable">
                <thead>
                    <tr style="background:#f8fafc; text-align:left;">
                        <th style="padding:10px; border:1px solid #e2e8f0; width:50%;">Description / Service Item</th>
                        <th style="padding:10px; border:1px solid #e2e8f0; width:15%;">Qty</th>
                        <th style="padding:10px; border:1px solid #e2e8f0; width:15%;">Price (৳)</th>
                        <th style="padding:10px; border:1px solid #e2e8f0; width:15%;">Total (৳)</th>
                        <th style="padding:10px; border:1px solid #e2e8f0; width:5%;"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding:8px; border:1px solid #e2e8f0;">
                            <input type="text" name="item_desc[]" required placeholder="e.g. Air Ticket DAC-DXB / Dubai 30 Days Visa" style="width:100%; padding:6px; border:1px solid #cbd5e1; border-radius:4px;">
                        </td>
                        <td style="padding:8px; border:1px solid #e2e8f0;">
                            <input type="number" name="item_qty[]" class="item_qty" value="1" min="1" style="width:100%; padding:6px; border:1px solid #cbd5e1; border-radius:4px;">
                        </td>
                        <td style="padding:8px; border:1px solid #e2e8f0;">
                            <input type="number" step="0.01" name="item_price[]" class="item_price" value="0.00" style="width:100%; padding:6px; border:1px solid #cbd5e1; border-radius:4px;">
                        </td>
                        <td style="padding:8px; border:1px solid #e2e8f0;">
                            <input type="number" step="0.01" name="item_total[]" class="item_total" value="0.00" readonly style="width:100%; padding:6px; border:1px solid #cbd5e1; border-radius:4px; background:#f8fafc; font-weight:bold;">
                        </td>
                        <td style="padding:8px; border:1px solid #e2e8f0; text-align:center;">
                            <button type="button" class="remove-row" style="background:#fee2e2; color:#dc2626; border:none; border-radius:4px; padding:6px 8px; cursor:pointer;">&times;</button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <button type="button" id="ifs_add_item_btn" style="background:#f1f5f9; color:#0f172a; border:1px solid #cbd5e1; padding:6px 12px; border-radius:4px; cursor:pointer; font-weight:600; margin-bottom:25px;">
                + Add Another Item
            </button>

            <!-- Calculations Box -->
            <div style="display: flex; justify-content: flex-end;">
                <div style="width: 350px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                        <span style="font-weight:600; color:#64748b;">Subtotal:</span>
                        <input type="number" step="0.01" name="subtotal" id="ifs_inv_subtotal" readonly value="0.00" style="width:140px; padding:6px; font-weight:bold; text-align:right;">
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                        <span style="font-weight:600; color:#64748b;">Discount (-):</span>
                        <input type="number" step="0.01" name="discount" id="ifs_inv_discount" value="0.00" style="width:140px; padding:6px; text-align:right;">
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px; border-top:1px solid #e2e8f0; padding-top:10px;">
                        <span style="font-weight:700; color:#0f172a;">Net Total:</span>
                        <input type="number" step="0.01" id="ifs_inv_net_total" readonly value="0.00" style="width:140px; padding:6px; font-weight:800; text-align:right; background:#f0fdf4; color:#166534;">
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                        <span style="font-weight:600; color:#166534;">Received Amount:</span>
                        <input type="number" step="0.01" name="paid_amount" id="ifs_inv_paid" value="0.00" style="width:140px; padding:6px; font-weight:bold; text-align:right;">
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                        <span style="font-weight:600; color:#64748b;">Payment Method:</span>
                        <select name="payment_method" style="width:140px; padding:6px;">
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="bKash / Nagad">bKash / Nagad</option>
                        </select>
                    </div>
                </div>
            </div>

            <div style="margin-top: 25px; border-top:1px solid #e2e8f0; padding-top:15px; display:flex; justify-content:space-between; align-items:center;">
                <a href="<?php echo admin_url( 'admin.php?page=ifs_travel_erp&tab=accounts' ); ?>" style="color:#64748b; text-decoration:none;">Cancel</a>
                <button type="submit" name="ifs_invoice_submit" style="background: #003376; color: #fff; border: none; padding: 10px 24px; border-radius: 4px; font-weight: 700; cursor: pointer;">
                    Generate & Save Invoice
                </button>
            </div>
        </form>
    </div>

    <script>
        jQuery(document).ready(function($) {
            // Toggle Client Selection
            $('#ifs_client_type').on('change', function() {
                if($(this).val() === 'Customer') {
                    $('#ifs_customer_select_box').show();
                    $('#ifs_agent_select_box').hide();
                    $('#ifs_final_client_id').val($('#client_id_customer').val());
                } else {
                    $('#ifs_customer_select_box').hide();
                    $('#ifs_agent_select_box').show();
                    $('#ifs_final_client_id').val($('#client_id_agent').val());
                }
            });
            $('#client_id_customer').on('change', function() { $('#ifs_final_client_id').val($(this).val()); });
            $('#client_id_agent').on('change', function() { $('#ifs_final_client_id').val($(this).val()); });

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
                $('#ifs_inv_net_total').val(net.toFixed(2));
            }

            $(document).on('input', '.item_qty, .item_price, #ifs_inv_discount', calculateInvoice);

            // Add Item Row
            $('#ifs_add_item_btn').on('click', function() {
                var row = `<tr>
                    <td style="padding:8px; border:1px solid #e2e8f0;"><input type="text" name="item_desc[]" required placeholder="Service description..." style="width:100%; padding:6px; border:1px solid #cbd5e1; border-radius:4px;"></td>
                    <td style="padding:8px; border:1px solid #e2e8f0;"><input type="number" name="item_qty[]" class="item_qty" value="1" min="1" style="width:100%; padding:6px; border:1px solid #cbd5e1; border-radius:4px;"></td>
                    <td style="padding:8px; border:1px solid #e2e8f0;"><input type="number" step="0.01" name="item_price[]" class="item_price" value="0.00" style="width:100%; padding:6px; border:1px solid #cbd5e1; border-radius:4px;"></td>
                    <td style="padding:8px; border:1px solid #e2e8f0;"><input type="number" step="0.01" name="item_total[]" class="item_total" value="0.00" readonly style="width:100%; padding:6px; border:1px solid #cbd5e1; border-radius:4px; background:#f8fafc; font-weight:bold;"></td>
                    <td style="padding:8px; border:1px solid #e2e8f0; text-align:center;"><button type="button" class="remove-row" style="background:#fee2e2; color:#dc2626; border:none; border-radius:4px; padding:6px 8px; cursor:pointer;">&times;</button></td>
                </tr>`;
                $('#ifsInvoiceItemsTable tbody').append(row);
            });

            $(document).on('click', '.remove-row', function() {
                if($('#ifsInvoiceItemsTable tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                    calculateInvoice();
                }
            });
        });
    </script>
    <?php
}