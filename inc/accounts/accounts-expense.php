<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enterprise Expense & Operational Outflow Management Workspace (Ultimate Pro Edition)
 */
function ifs_terp_accounts_expense_page() {
    global $wpdb;
    $table_ledger    = $wpdb->prefix . 'iterp_ledger';
    $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
    $base_url        = admin_url( 'admin.php?page=ifs_travel_erp&tab=accounts&sub=expense' );

    $message = '';

    // 0. Handle CSV Export Action (Must be at the top before HTML headers)
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'export_expense_csv' ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized access.' );
        }

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=expense-ledger-report-' . date( 'Y-m-d' ) . '.csv' );

        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, array( 'Txn ID', 'Category', 'Payee / Details', 'Payment Method', 'Amount (BDT)', 'Reference / Memo', 'Transaction Date' ) );

        $export_rows = $wpdb->get_results( "SELECT * FROM {$table_ledger} WHERE transaction_type = 'Expense' ORDER BY transaction_date DESC, id DESC", ARRAY_A );
        if ( $export_rows ) {
            foreach ( $export_rows as $row ) {
                fputcsv( $output, array(
                    '#TX-' . $row['id'],
                    $row['category'],
                    $row['description'],
                    $row['payment_method'],
                    $row['amount'],
                    $row['reference_no'],
                    $row['transaction_date'],
                ) );
            }
        }
        fclose( $output );
        exit;
    }

    // 1. Handle Delete Record (Placed at the very top before any HTML output)
    if ( isset( $_GET['del'] ) && $_GET['del'] === '1' && isset( $_GET['id'] ) ) {
        $del_id = intval( $_GET['id'] );
        
        if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'delete_expense_' . $del_id ) ) {
            $wpdb->delete( $table_ledger, array( 'id' => $del_id, 'transaction_type' => 'Expense' ), array( '%d', '%s' ) );
            
            if ( function_exists( 'ifs_terp_log_activity' ) ) {
                ifs_terp_log_activity( "Deleted Expense Record ID #{$del_id}" );
            }

            if ( ! headers_sent() ) {
                wp_safe_redirect( admin_url( 'admin.php?page=ifs_travel_erp&tab=accounts&sub=expense&view=all&msg=deleted' ) );
                exit;
            }
        }
    }

    // 2. Handle Form Submission for Creating New Expense Voucher
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_expense_submit'] ) ) {
        check_admin_referer( 'ifs_expense_action', 'ifs_expense_nonce' );

        $category         = sanitize_text_field( $_POST['category'] ?? 'Office Rent' );
        $expense_payee    = sanitize_text_field( $_POST['expense_payee'] ?? 'General' );
        $supplier_id      = intval( $_POST['supplier_id'] ?? 0 );
        $amount           = floatval( $_POST['amount'] ?? 0 );
        $payment_method   = sanitize_text_field( $_POST['payment_method'] ?? 'Cash' );
        $bank_name        = sanitize_text_field( $_POST['bank_name'] ?? '' );
        $reference_no     = sanitize_text_field( $_POST['reference_no'] ?? '' );
        $transaction_date = sanitize_text_field( $_POST['transaction_date'] ?? current_time( 'Y-m-d H:i:s' ) );
        $description      = sanitize_textarea_field( $_POST['description'] ?? '' );

        if ( $amount <= 0 ) {
            $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Please enter a valid positive expense amount.', 'ifs-travel-erp' ) . '</div>';
        } else {
            $full_description = sprintf(
                'Payee Type: %s (Supplier ID: %d) | Bank/Channel: %s | %s',
                $expense_payee,
                $supplier_id,
                $bank_name ?: 'N/A',
                $description
            );

            $inserted = $wpdb->insert(
                $table_ledger,
                array(
                    'transaction_type' => 'Expense',
                    'category'         => $category,
                    'amount'           => $amount,
                    'payment_method'   => $payment_method,
                    'reference_no'     => $reference_no,
                    'description'      => trim( $full_description ),
                    'transaction_date' => $transaction_date,
                    'logged_by'        => get_current_user_id(),
                ),
                array( '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%d' )
            );

            if ( $inserted ) {
                if ( function_exists( 'ifs_terp_log_activity' ) ) {
                    ifs_terp_log_activity( "Created Professional Expense Voucher [{$category}] for ৳" . number_format( $amount, 2 ) );
                }
                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Expense voucher successfully registered and logged in accounts ledger.', 'ifs-travel-erp' ) . '</div>';
            } else {
                $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Database error: Unable to record expense voucher.', 'ifs-travel-erp' ) . '</div>';
            }
        }
    }

    // 3. Handle Form Submission for Updating Existing Expense Voucher
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_expense_update'] ) ) {
        check_admin_referer( 'ifs_expense_update_action', 'ifs_expense_update_nonce' );

        $edit_id          = intval( $_POST['edit_id'] ?? 0 );
        $category         = sanitize_text_field( $_POST['category'] ?? 'Office Rent' );
        $amount           = floatval( $_POST['amount'] ?? 0 );
        $payment_method   = sanitize_text_field( $_POST['payment_method'] ?? 'Cash' );
        $reference_no     = sanitize_text_field( $_POST['reference_no'] ?? '' );
        $transaction_date = sanitize_text_field( $_POST['transaction_date'] ?? current_time( 'Y-m-d H:i:s' ) );
        $description      = sanitize_textarea_field( $_POST['description'] ?? '' );

        if ( $edit_id > 0 && $amount > 0 ) {
            $updated = $wpdb->update(
                $table_ledger,
                array(
                    'category'         => $category,
                    'amount'           => $amount,
                    'payment_method'   => $payment_method,
                    'reference_no'     => $reference_no,
                    'description'      => trim( $description ),
                    'transaction_date' => $transaction_date,
                ),
                array( 'id' => $edit_id, 'transaction_type' => 'Expense' ),
                array( '%s', '%f', '%s', '%s', '%s', '%s' ),
                array( '%d', '%s' )
            );

            if ( $updated !== false ) {
                if ( function_exists( 'ifs_terp_log_activity' ) ) {
                    ifs_terp_log_activity( "Updated Expense Voucher ID #{$edit_id}" );
                }
                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Expense voucher updated successfully.', 'ifs-travel-erp' ) . '</div>';
            } else {
                $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Database error: Unable to update voucher.', 'ifs-travel-erp' ) . '</div>';
            }
        }
    }

    if ( isset( $_GET['msg'] ) && $_GET['msg'] === 'deleted' ) {
        $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Expense voucher removed successfully.', 'ifs-travel-erp' ) . '</div>';
    }

    $inner_tab = isset( $_GET['view'] ) ? sanitize_text_field( $_GET['view'] ) : 'all';
    $view_id   = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

    // Optional Filter Parameters
    $filter_category = isset( $_GET['filter_cat'] ) ? sanitize_text_field( $_GET['filter_cat'] ) : '';
    $filter_method   = isset( $_GET['filter_method'] ) ? sanitize_text_field( $_GET['filter_method'] ) : '';

    $query_where = " WHERE transaction_type = 'Expense' ";
    $query_vals  = array();

    if ( ! empty( $filter_category ) ) {
        $query_where .= " AND category = %s ";
        $query_vals[] = $filter_category;
    }
    if ( ! empty( $filter_method ) ) {
        $query_where .= " AND payment_method = %s ";
        $query_vals[] = $filter_method;
    }

    if ( ! empty( $query_vals ) ) {
        $expenses = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_ledger} {$query_where} ORDER BY transaction_date DESC, id DESC", $query_vals ) );
    } else {
        $expenses = $wpdb->get_results( "SELECT * FROM {$table_ledger} WHERE transaction_type = 'Expense' ORDER BY transaction_date DESC, id DESC" );
    }

    // Analytics Metrics Calculations
    $total_exp         = (float) $wpdb->get_var( "SELECT SUM(amount) FROM {$table_ledger} WHERE transaction_type = 'Expense'" );
    $current_month_exp = (float) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(amount) FROM {$table_ledger} WHERE transaction_type = 'Expense' AND transaction_date >= %s", date( 'Y-m-01 00:00:00' ) ) );
    $total_vouchers    = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table_ledger} WHERE transaction_type = 'Expense'" );

    $suppliers  = $wpdb->get_results( "SELECT id, supplier_name, phone FROM {$table_suppliers} ORDER BY supplier_name ASC" );
    $categories = $wpdb->get_col( "SELECT DISTINCT category FROM {$table_ledger} WHERE transaction_type = 'Expense' ORDER BY category ASC" );
    ?>
    <div class="wrap ifs-invoice-list-workspace">
        <?php echo $message; ?>

        <!-- Executive KPI Metric Cards Bar -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin-bottom: 22px;">
            <div class="ifs-table-card" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 22px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
                <div style="width: 48px; height: 48px; border-radius: 10px; background: #fee2e2; color: #b91c1c; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
                    <span class="dashicons dashicons-cart"></span>
                </div>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e( 'Total Operating Expenses', 'ifs-travel-erp' ); ?></span>
                    <h3 style="margin: 3px 0 0 0; font-size: 20px; font-weight: 800; color: #b91c1c; font-family: monospace;">৳<?php echo number_format( $total_exp, 2 ); ?></h3>
                </div>
            </div>

            <div class="ifs-table-card" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 22px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
                <div style="width: 48px; height: 48px; border-radius: 10px; background: #fef3c7; color: #b45309; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </div>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e( 'Current Month Outflow', 'ifs-travel-erp' ); ?></span>
                    <h3 style="margin: 3px 0 0 0; font-size: 20px; font-weight: 800; color: #b45309; font-family: monospace;">৳<?php echo number_format( $current_month_exp, 2 ); ?></h3>
                </div>
            </div>

            <div class="ifs-table-card" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 22px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
                <div style="width: 48px; height: 48px; border-radius: 10px; background: #f1f5f9; color: #475569; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
                    <span class="dashicons dashicons-media-document"></span>
                </div>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e( 'Total Expense Vouchers', 'ifs-travel-erp' ); ?></span>
                    <h3 style="margin: 3px 0 0 0; font-size: 20px; font-weight: 800; color: #334155; font-family: monospace;"><?php echo number_format( $total_vouchers ); ?> <?php esc_html_e( 'Entries', 'ifs-travel-erp' ); ?></h3>
                </div>
            </div>
        </div>

        <!-- Pro Navigation Toggle Pills & Export Button -->
        <div style="display: flex; gap: 8px; margin-bottom: 22px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; justify-content: space-between; align-items: center; flex-wrap: wrap;">
            <div style="display: flex; gap: 8px;">
                <a href="<?php echo esc_url( $base_url . '&view=all' ); ?>" style="padding: 8px 18px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; background: <?php echo ($inner_tab === 'all') ? '#003376' : '#fff'; ?>; color: <?php echo ($inner_tab === 'all') ? '#fff' : '#475569'; ?>; border: 1px solid <?php echo ($inner_tab === 'all') ? '#003376' : '#cbd5e1'; ?>; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                    <span class="dashicons dashicons-list-view" style="font-size: 16px; width: 16px; height: 16px;"></span> <?php esc_html_e( 'All Expense Records', 'ifs-travel-erp' ); ?>
                </a>
                <a href="<?php echo esc_url( $base_url . '&view=add' ); ?>" style="padding: 8px 18px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; background: <?php echo ($inner_tab === 'add') ? '#003376' : '#fff'; ?>; color: <?php echo ($inner_tab === 'add') ? '#fff' : '#475569'; ?>; border: 1px solid <?php echo ($inner_tab === 'add') ? '#003376' : '#cbd5e1'; ?>; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                    <span class="dashicons dashicons-plus-alt2" style="font-size: 16px; width: 16px; height: 16px;"></span> <?php esc_html_e( 'Add Expense Voucher', 'ifs-travel-erp' ); ?>
                </a>
            </div>
            <div>
                <a href="<?php echo esc_url( $base_url . '&action=export_expense_csv' ); ?>" class="button" style="height: 36px; line-height: 34px; padding: 0 14px; border-radius: 6px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                    <span class="dashicons dashicons-download" style="font-size: 14px; width: 14px; height: 14px; margin-top: 3px;"></span> <?php esc_html_e( 'Export CSV', 'ifs-travel-erp' ); ?>
                </a>
            </div>
        </div>

        <?php if ( $inner_tab === 'add' ) : ?>
            <!-- Add Expense Form Card -->
            <div class="ifs-table-card" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 32px; max-width: 900px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04);">
                <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 16px; margin-bottom: 24px; display: flex; align-items: center; gap: 14px;">
                    <div style="width: 42px; height: 42px; border-radius: 10px; background: #fee2e2; color: #b91c1c; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                        <span class="dashicons dashicons-cart"></span>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 16.5px; font-weight: 800; color: #0f172a;"><?php esc_html_e( 'Create Professional Expense Voucher', 'ifs-travel-erp' ); ?></h3>
                        <p style="margin: 3px 0 0 0; font-size: 12.5px; color: #64748b;"><?php esc_html_e( 'Record operational expenditures such as office rent, staff salaries, utility bills, GDS/supplier payables, or marketing costs.', 'ifs-travel-erp' ); ?></p>
                    </div>
                </div>

                <form method="post" action="">
                    <?php wp_nonce_field( 'ifs_expense_action', 'ifs_expense_nonce' ); ?>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Expense Category', 'ifs-travel-erp' ); ?> <span style="color: #e11d48;">*</span></label>
                            <select name="category" class="ifs-select-control" style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; background: #fff; font-size: 13px;">
                                <option value="Office Rent"><?php esc_html_e( 'Office Rent & Property Maintenance', 'ifs-travel-erp' ); ?></option>
                                <option value="Staff Salaries & Allowance"><?php esc_html_e( 'Staff Salaries & Allowances', 'ifs-travel-erp' ); ?></option>
                                <option value="Utilities (Electricity/Internet)"><?php esc_html_e( 'Utilities (Electricity, Gas, Internet)', 'ifs-travel-erp' ); ?></option>
                                <option value="Supplier / GDS Payable"><?php esc_html_e( 'Supplier / GDS Ticketing Settlement', 'ifs-travel-erp' ); ?></option>
                                <option value="Marketing & Advertising"><?php esc_html_e( 'Marketing, Ads & Promotional Cost', 'ifs-travel-erp' ); ?></option>
                                <option value="Office Supplies & Stationery"><?php esc_html_e( 'Office Supplies & Stationery', 'ifs-travel-erp' ); ?></option>
                                <option value="Miscellaneous Expense"><?php esc_html_e( 'Miscellaneous / General Expense', 'ifs-travel-erp' ); ?></option>
                            </select>
                        </div>

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Payee Entity Type', 'ifs-travel-erp' ); ?></label>
                            <select name="expense_payee" id="ifs_expense_payee" class="ifs-select-control" style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; background: #fff; font-size: 13px;">
                                <option value="General"><?php esc_html_e( 'General / Cash Vendor', 'ifs-travel-erp' ); ?></option>
                                <option value="Supplier"><?php esc_html_e( 'Registered GDS / Supplier Portal', 'ifs-travel-erp' ); ?></option>
                            </select>
                        </div>
                    </div>

                    <!-- Conditional Supplier Dropdown -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px;">
                        <div id="ifs_supplier_box" style="display: none;">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Select Supplier Directory', 'ifs-travel-erp' ); ?></label>
                            <select name="supplier_id" class="ifs-select-control" style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; background: #fff; font-size: 13px;">
                                <option value="0"><?php esc_html_e( '-- Choose Supplier --', 'ifs-travel-erp' ); ?></option>
                                <?php if ( $suppliers ) : foreach ( $suppliers as $s ) : ?>
                                    <option value="<?php echo esc_attr( $s->id ); ?>"><?php echo esc_html( $s->supplier_name . ' (' . $s->phone . ')' ); ?></option>
                                <?php endforeach; endif; ?>
                            </select>
                        </div>

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Expense Amount (৳)', 'ifs-travel-erp' ); ?> <span style="color: #e11d48;">*</span></label>
                            <input type="number" step="0.01" name="amount" required value="0.00" class="font-mono" style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-weight: 800; color: #b91c1c; font-size: 15px;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 18px; margin-bottom: 18px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Payment Method', 'ifs-travel-erp' ); ?></label>
                            <select name="payment_method" class="ifs-select-control" style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; background: #fff; font-size: 13px;">
                                <option value="Cash"><?php esc_html_e( 'Cash Drawer', 'ifs-travel-erp' ); ?></option>
                                <option value="Bank Transfer"><?php esc_html_e( 'Bank Transfer (BEFTN/NPSB)', 'ifs-travel-erp' ); ?></option>
                                <option value="bKash / MFS"><?php esc_html_e( 'bKash / Nagad / Rocket', 'ifs-travel-erp' ); ?></option>
                                <option value="Cheque"><?php esc_html_e( 'Bank Cheque', 'ifs-travel-erp' ); ?></option>
                            </select>
                        </div>

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Bank / Gateway Name', 'ifs-travel-erp' ); ?></label>
                            <input type="text" name="bank_name" placeholder="e.g. EBL / BRAC / Cash Drawer" style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 13px;">
                        </div>

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Memo / Voucher No', 'ifs-travel-erp' ); ?></label>
                            <input type="text" name="reference_no" placeholder="e.g. EXP-2026-001" class="font-mono" style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 13px;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr; gap: 18px; margin-bottom: 18px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Transaction Date & Time', 'ifs-travel-erp' ); ?> <span style="color: #e11d48;">*</span></label>
                            <input type="datetime-local" name="transaction_date" required value="<?php echo esc_attr( date( 'Y-m-d\TH:i' ) ); ?>" class="font-mono" style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 13px;">
                        </div>
                    </div>

                    <div style="margin-bottom: 26px;">
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Narration / Particulars', 'ifs-travel-erp' ); ?></label>
                        <textarea name="description" rows="3" placeholder="Provide extra accounting particulars, bill numbers, or vendor notes..." style="width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 12px; font-size: 13px;"></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 12px;">
                        <a href="<?php echo esc_url( $base_url . '&view=all' ); ?>" class="button" style="height: 42px; line-height: 40px; padding: 0 20px; border-radius: 8px; font-weight: 600;"><?php esc_html_e( 'Cancel', 'ifs-travel-erp' ); ?></a>
                        <button type="submit" name="ifs_expense_submit" style="background: #b91c1c; color: #fff; border: none; height: 42px; padding: 0 28px; border-radius: 8px; font-weight: 700; font-size: 13.5px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(185, 28, 28, 0.2);">
                            <span class="dashicons dashicons-saved" style="margin-top: 1px;"></span> <?php esc_html_e( 'Save Expense Voucher', 'ifs-travel-erp' ); ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Dynamic Payee Switching Script -->
            <script>
            jQuery(document).ready(function($) {
                $('#ifs_expense_payee').on('change', function() {
                    var val = $(this).val();
                    $('#ifs_supplier_box').hide();
                    if (val === 'Supplier') {
                        $('#ifs_supplier_box').show();
                    }
                });
            });
            </script>

        <?php elseif ( $inner_tab === 'view' && $view_id > 0 ) : ?>
            <?php 
            $single_exp = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_ledger} WHERE id = %d AND transaction_type = 'Expense'", $view_id ) );
            if ( ! $single_exp ) {
                echo '<div class="notice notice-error"><p>Expense voucher record not found.</p></div>';
                return;
            }
            ?>
            <!-- View Expense Dossier Card -->
            <div class="ifs-table-card ifs-print-receipt-box" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 36px; max-width: 800px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 24px;">
                    <div>
                        <span style="background: #fee2e2; color: #b91c1c; padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Expense Voucher Dossier</span>
                        <h2 style="margin: 10px 0 4px 0; font-size: 24px; font-weight: 800; color: #0f172a; font-family: monospace;">#TX-<?php echo esc_html( $single_exp->id ); ?></h2>
                        <span style="font-size: 13px; color: #64748b; font-family: monospace;"><?php echo esc_html( date( 'd M Y, h:i A', strtotime( $single_exp->transaction_date ) ) ); ?></span>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 12px; color: #64748b; display: block; font-weight: 700; text-transform: uppercase;">Disbursed Amount</span>
                        <span style="font-size: 28px; font-weight: 800; color: #b91c1c; font-family: monospace;">-৳<?php echo number_format( (float) $single_exp->amount, 2 ); ?></span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; background: #f8fafc; padding: 20px; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <div>
                        <strong style="display: block; font-size: 11.5px; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">Expense Category</strong>
                        <span style="font-size: 14px; font-weight: 700; color: #0f172a;"><?php echo esc_html( $single_exp->category ); ?></span>
                    </div>
                    <div>
                        <strong style="display: block; font-size: 11.5px; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">Payment Method</strong>
                        <span style="font-size: 14px; font-weight: 700; color: #0f172a;"><?php echo esc_html( $single_exp->payment_method ); ?></span>
                    </div>
                    <div>
                        <strong style="display: block; font-size: 11.5px; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">Memo / Reference No</strong>
                        <span style="font-size: 14px; font-weight: 700; color: #0f172a; font-family: monospace;"><?php echo esc_html( $single_exp->reference_no ?: 'N/A' ); ?></span>
                    </div>
                    <div>
                        <strong style="display: block; font-size: 11.5px; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">Logged By User ID</strong>
                        <span style="font-size: 14px; font-weight: 700; color: #0f172a;">#<?php echo esc_html( $single_exp->logged_by ); ?></span>
                    </div>
                </div>

                <div style="margin-bottom: 28px;">
                    <strong style="display: block; font-size: 11.5px; color: #64748b; text-transform: uppercase; margin-bottom: 6px;">Particulars / Description Details</strong>
                    <div style="background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 14px; font-size: 13.5px; color: #334155; line-height: 1.5; min-height: 60px;">
                        <?php echo nl2br( esc_html( $single_exp->description ?: 'No additional particulars recorded.' ) ); ?>
                    </div>
                </div>

                <div class="ifs-no-print" style="display: flex; justify-content: space-between; align-items: center;">
                    <a href="<?php echo esc_url( $base_url . '&view=all' ); ?>" class="button" style="height: 38px; line-height: 36px; padding: 0 16px; border-radius: 6px; font-weight: 600;">&larr; Back to Register</a>
                    <div style="display: flex; gap: 8px;">
                        <a href="<?php echo esc_url( $base_url . '&view=edit&id=' . $single_exp->id ); ?>" class="button button-primary" style="background: #003376; border-color: #003376; height: 38px; line-height: 36px; padding: 0 16px; border-radius: 6px; font-weight: 700;">Edit Voucher</a>
                        <button type="button" onclick="window.print();" class="button" style="height: 38px; line-height: 36px; padding: 0 16px; border-radius: 6px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                            <span class="dashicons dashicons-printer" style="font-size: 14px; width: 14px; height: 14px; margin-top: 2px;"></span> Print Voucher
                        </button>
                    </div>
                </div>
            </div>

            <!-- Print Rules for Dossier -->
            <style>
            @media print {
                body * { visibility: hidden; }
                .ifs-print-receipt-box, .ifs-print-receipt-box * { visibility: visible; }
                .ifs-print-receipt-box { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none !important; border: none !important; padding: 10px; }
                .ifs-no-print { display: none !important; }
            }
            </style>

        <?php elseif ( $inner_tab === 'edit' && $view_id > 0 ) : ?>
            <?php 
            $edit_exp = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_ledger} WHERE id = %d AND transaction_type = 'Expense'", $view_id ) );
            if ( ! $edit_exp ) {
                echo '<div class="notice notice-error"><p>Expense voucher record not found for editing.</p></div>';
                return;
            }
            ?>
            <!-- Pro Edit Expense Form Card -->
            <div class="ifs-table-card" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 32px; max-width: 850px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04);">
                <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 16px; margin-bottom: 24px; display: flex; align-items: center; gap: 14px;">
                    <div style="width: 42px; height: 42px; border-radius: 10px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                        <span class="dashicons dashicons-edit"></span>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 16.5px; font-weight: 800; color: #0f172a;"><?php esc_html_e( 'Edit Expense Voucher #TX-', 'ifs-travel-erp' ); ?><?php echo esc_html( $edit_exp->id ); ?></h3>
                        <p style="margin: 3px 0 0 0; font-size: 12.5px; color: #64748b;"><?php esc_html_e( 'Modify financial parameters or particulars for this entry.', 'ifs-travel-erp' ); ?></p>
                    </div>
                </div>

                <form method="post" action="">
                    <?php wp_nonce_field( 'ifs_expense_update_action', 'ifs_expense_update_nonce' ); ?>
                    <input type="hidden" name="edit_id" value="<?php echo esc_attr( $edit_exp->id ); ?>">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Expense Category', 'ifs-travel-erp' ); ?> <span style="color: #e11d48;">*</span></label>
                            <input type="text" name="category" required value="<?php echo esc_attr( $edit_exp->category ); ?>" style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 13px;">
                        </div>

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Expense Amount (৳)', 'ifs-travel-erp' ); ?> <span style="color: #e11d48;">*</span></label>
                            <input type="number" step="0.01" name="amount" required value="<?php echo esc_attr( $edit_exp->amount ); ?>" class="font-mono" style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-weight: 800; color: #b91c1c; font-size: 15px;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Payment Method', 'ifs-travel-erp' ); ?></label>
                            <select name="payment_method" class="ifs-select-control" style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; background: #fff; font-size: 13px;">
                                <option value="Cash" <?php selected( $edit_exp->payment_method, 'Cash' ); ?>><?php esc_html_e( 'Cash Drawer', 'ifs-travel-erp' ); ?></option>
                                <option value="Bank Transfer" <?php selected( $edit_exp->payment_method, 'Bank Transfer' ); ?>><?php esc_html_e( 'Bank Transfer', 'ifs-travel-erp' ); ?></option>
                                <option value="bKash / MFS" <?php selected( $edit_exp->payment_method, 'bKash / MFS' ); ?>><?php esc_html_e( 'bKash / MFS', 'ifs-travel-erp' ); ?></option>
                                <option value="Cheque" <?php selected( $edit_exp->payment_method, 'Cheque' ); ?>><?php esc_html_e( 'Bank Cheque', 'ifs-travel-erp' ); ?></option>
                            </select>
                        </div>

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Reference / Memo No', 'ifs-travel-erp' ); ?></label>
                            <input type="text" name="reference_no" value="<?php echo esc_attr( $edit_exp->reference_no ); ?>" class="font-mono" style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 13px;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr; gap: 18px; margin-bottom: 18px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Transaction Date & Time', 'ifs-travel-erp' ); ?> <span style="color: #e11d48;">*</span></label>
                            <input type="datetime-local" name="transaction_date" required value="<?php echo esc_attr( date( 'Y-m-d\TH:i', strtotime( $edit_exp->transaction_date ) ) ); ?>" class="font-mono" style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 13px;">
                        </div>
                    </div>

                    <div style="margin-bottom: 26px;">
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Particulars / Accounting Narration', 'ifs-travel-erp' ); ?></label>
                        <textarea name="description" rows="3" style="width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 12px; font-size: 13px;"><?php echo esc_textarea( $edit_exp->description ); ?></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 12px;">
                        <a href="<?php echo esc_url( $base_url . '&view=view&id=' . $edit_exp->id ); ?>" class="button" style="height: 42px; line-height: 40px; padding: 0 20px; border-radius: 8px; font-weight: 600;"><?php esc_html_e( 'Cancel', 'ifs-travel-erp' ); ?></a>
                        <button type="submit" name="ifs_expense_update" style="background: #003376; color: #fff; border: none; height: 42px; padding: 0 28px; border-radius: 8px; font-weight: 700; font-size: 13.5px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                            <span class="dashicons dashicons-saved" style="margin-top: 1px;"></span> <?php esc_html_e( 'Update Expense Voucher', 'ifs-travel-erp' ); ?>
                        </button>
                    </div>
                </form>
            </div>

        <?php else : ?>
            <!-- Pro All Expense Table Card with Filter Bar -->
            <div class="ifs-table-card" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);">
                <div class="ifs-custom-table-controls" style="padding: 18px 26px; background: #f8fafc; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
                    <form method="get" action="" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <input type="hidden" name="page" value="ifs_travel_erp">
                        <input type="hidden" name="tab" value="accounts">
                        <input type="hidden" name="sub" value="expense">
                        <input type="hidden" name="view" value="all">

                        <select name="filter_cat" class="ifs-select-control" style="height: 36px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 10px; background: #fff; font-size: 12.5px;">
                            <option value=""><?php esc_html_e( 'All Categories', 'ifs-travel-erp' ); ?></option>
                            <?php if ( $categories ) : foreach ( $categories as $cat ) : ?>
                                <option value="<?php echo esc_attr( $cat ); ?>" <?php selected( $filter_category, $cat ); ?>><?php echo esc_html( $cat ); ?></option>
                            <?php endforeach; endif; ?>
                        </select>

                        <select name="filter_method" class="ifs-select-control" style="height: 36px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 10px; background: #fff; font-size: 12.5px;">
                            <option value=""><?php esc_html_e( 'All Payment Methods', 'ifs-travel-erp' ); ?></option>
                            <option value="Cash" <?php selected( $filter_method, 'Cash' ); ?>><?php esc_html_e( 'Cash Drawer', 'ifs-travel-erp' ); ?></option>
                            <option value="Bank Transfer" <?php selected( $filter_method, 'Bank Transfer' ); ?>><?php esc_html_e( 'Bank Transfer', 'ifs-travel-erp' ); ?></option>
                            <option value="bKash / MFS" <?php selected( $filter_method, 'bKash / MFS' ); ?>><?php esc_html_e( 'bKash / MFS', 'ifs-travel-erp' ); ?></option>
                            <option value="Cheque" <?php selected( $filter_method, 'Cheque' ); ?>><?php esc_html_e( 'Cheque', 'ifs-travel-erp' ); ?></option>
                        </select>

                        <button type="submit" class="button button-secondary" style="height: 36px; padding: 0 14px; border-radius: 6px; font-weight: 600;"><?php esc_html_e( 'Filter', 'ifs-travel-erp' ); ?></button>
                        <?php if ( ! empty( $filter_category ) || ! empty( $filter_method ) ) : ?>
                            <a href="<?php echo esc_url( $base_url . '&view=all' ); ?>" class="button" style="height: 36px; line-height: 34px; padding: 0 10px; border-radius: 6px;"><?php esc_html_e( 'Reset', 'ifs-travel-erp' ); ?></a>
                        <?php endif; ?>
                    </form>

                    <div style="font-size: 14px; font-weight: 800; color: #b91c1c; font-family: monospace; background: #fee2e2; padding: 7px 16px; border-radius: 8px; border: 1px solid #fecaca;">
                        <?php esc_html_e( 'Total Outflow:', 'ifs-travel-erp' ); ?> ৳<?php echo number_format( $total_exp, 2 ); ?>
                    </div>
                </div>

                <div class="table-scroll" style="padding: 16px 24px 24px 24px; overflow-x: auto;">
                    <table class="ifs-pro-datatable" id="ifsExpenseTable" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <thead>
                            <tr style="background: #f8fafc; text-align: left;">
                                <th style="padding: 12px 16px; border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 11px; text-transform: uppercase;"><?php esc_html_e( 'Txn ID', 'ifs-travel-erp' ); ?></th>
                                <th style="padding: 12px 16px; border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 11px; text-transform: uppercase;"><?php esc_html_e( 'Category & Details', 'ifs-travel-erp' ); ?></th>
                                <th style="padding: 12px 16px; border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 11px; text-transform: uppercase;"><?php esc_html_e( 'Payment Method', 'ifs-travel-erp' ); ?></th>
                                <th style="padding: 12px 16px; border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 11px; text-transform: uppercase; text-align: right;"><?php esc_html_e( 'Amount', 'ifs-travel-erp' ); ?></th>
                                <th style="padding: 12px 16px; border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 11px; text-transform: uppercase;"><?php esc_html_e( 'Date & Time', 'ifs-travel-erp' ); ?></th>
                                <th style="padding: 12px 16px; border-bottom: 2px solid #e2e8f0; text-align: right; width: 140px;"><?php esc_html_e( 'Actions', 'ifs-travel-erp' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( $expenses ) : foreach ( $expenses as $exp ) : ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 14px 16px;"><span class="ifs-id-badge font-mono" style="background: #f1f5f9; padding: 3px 8px; border-radius: 4px; font-weight: 700; color: #334155;">#TX-<?php echo esc_html( $exp->id ); ?></span></td>
                                    <td style="padding: 14px 16px;">
                                        <div style="font-weight: 700; color: #0f172a;"><?php echo esc_html( $exp->category ); ?></div>
                                        <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;"><?php echo esc_html( $exp->reference_no ? 'Memo: ' . $exp->reference_no . ' — ' : '' ); ?><?php echo esc_html( $exp->description ); ?></div>
                                    </td>
                                    <td style="padding: 14px 16px; font-weight: 600; color: #334155;">
                                        <span style="background: #f8fafc; border: 1px solid #cbd5e1; padding: 2px 8px; border-radius: 4px; font-size: 11.5px;"><?php echo esc_html( $exp->payment_method ); ?></span>
                                    </td>
                                    <td style="padding: 14px 16px; text-align: right; font-weight: 800; font-family: monospace; color: #b91c1c; font-size: 14px;">
                                        -৳<?php echo number_format( (float) $exp->amount, 2 ); ?>
                                    </td>
                                    <td style="padding: 14px 16px; font-family: monospace; font-size: 12px; color: #64748b;"><?php echo esc_html( date( 'd M Y, h:i A', strtotime( $exp->transaction_date ) ) ); ?></td>
                                    <td style="padding: 14px 16px; text-align: right;">
                                        <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                            <a href="<?php echo esc_url( $base_url . '&view=view&id=' . $exp->id ); ?>" class="ifs-action-pill view" style="background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; padding: 5px 8px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 3px;" title="View Dossier">
                                                <span class="dashicons dashicons-visibility" style="font-size: 13px; width: 13px; height: 13px;"></span>
                                            </a>
                                            <a href="<?php echo esc_url( $base_url . '&view=edit&id=' . $exp->id ); ?>" class="ifs-action-pill edit" style="background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; padding: 5px 8px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 3px;" title="Edit Voucher">
                                                <span class="dashicons dashicons-edit" style="font-size: 13px; width: 13px; height: 13px;"></span>
                                            </a>
                                            <a href="<?php echo wp_nonce_url( $base_url . '&view=all&del=1&id=' . $exp->id, 'delete_expense_' . $exp->id ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to permanently delete this expense record?', 'ifs-travel-erp' ); ?>');" class="ifs-action-pill delete" style="background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; padding: 5px 8px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 3px;" title="Delete">
                                                <span class="dashicons dashicons-trash" style="font-size: 13px; width: 13px; height: 13px;"></span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; else : ?>
                                <tr><td colspan="6" style="text-align: center; padding: 45px; color: #94a3b8; font-style: italic;"><?php esc_html_e( 'No expense records registered in the system yet.', 'ifs-travel-erp' ); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- DataTables Integration Script -->
    <script>
    jQuery(document).ready(function($) {
        if ($.fn.DataTable && $('#ifsExpenseTable').length) {
            $('#ifsExpenseTable').DataTable({
                "pageLength": 15,
                "ordering": true,
                "info": true,
                "searching": true,
                "lengthChange": false,
                "order": [[ 0, "desc" ]],
                "language": {
                    "info": "Showing _START_ to _END_ of _TOTAL_ expense vouchers",
                    "infoEmpty": "Showing 0 to 0 of 0 vouchers",
                    "paginate": { "previous": "&larr; Prev", "next": "Next &rarr;" }
                }
            });
        }
    });
    </script>
    <?php
}