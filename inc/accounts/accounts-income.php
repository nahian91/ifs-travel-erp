<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enterprise Income & Revenue Management Workspace (Ultimate Pro Edition)
 */
function ifs_terp_accounts_income_page() {
    global $wpdb;
    $table_ledger    = $wpdb->prefix . 'iterp_ledger';
    $table_customers = $wpdb->prefix . 'iterp_customers';
    $table_agents    = $wpdb->prefix . 'iterp_agents';
    $base_url        = admin_url( 'admin.php?page=ifs_travel_erp&tab=accounts&sub=income' );

    $message = '';

    // 1. Handle Delete Record (Placed at the very top before any HTML output)
    if ( isset( $_GET['del'] ) && $_GET['del'] === '1' && isset( $_GET['id'] ) ) {
        $del_id = intval( $_GET['id'] );
        
        if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'delete_income_' . $del_id ) ) {
            $wpdb->delete( $table_ledger, array( 'id' => $del_id, 'transaction_type' => 'Income' ), array( '%d', '%s' ) );
            
            if ( function_exists( 'ifs_terp_log_activity' ) ) {
                ifs_terp_log_activity( "Deleted Income Record ID #{$del_id}" );
            }

            if ( ! headers_sent() ) {
                wp_safe_redirect( admin_url( 'admin.php?page=ifs_travel_erp&tab=accounts&sub=income&view=all&msg=deleted' ) );
                exit;
            }
        }
    }

    // 2. Handle Form Submission for Creating New Income Voucher
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_income_submit'] ) ) {
        check_admin_referer( 'ifs_income_action', 'ifs_income_nonce' );

        $category         = sanitize_text_field( $_POST['category'] ?? 'Ticket Commission' );
        $income_source    = sanitize_text_field( $_POST['income_source'] ?? 'Direct' );
        $client_id        = intval( $_POST['client_id'] ?? 0 );
        $amount           = floatval( $_POST['amount'] ?? 0 );
        $payment_method   = sanitize_text_field( $_POST['payment_method'] ?? 'Cash' );
        $bank_name        = sanitize_text_field( $_POST['bank_name'] ?? '' );
        $reference_no     = sanitize_text_field( $_POST['reference_no'] ?? '' );
        $transaction_date = sanitize_text_field( $_POST['transaction_date'] ?? current_time( 'Y-m-d H:i:s' ) );
        $description      = sanitize_textarea_field( $_POST['description'] ?? '' );

        if ( $amount <= 0 ) {
            $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Please enter a valid positive income amount.', 'ifs-travel-erp' ) . '</div>';
        } else {
            $full_description = sprintf(
                'Source: %s (Client ID: %d) | Channel: %s | %s',
                $income_source,
                $client_id,
                $bank_name ?: 'Cash Drawer',
                $description
            );

            $inserted = $wpdb->insert(
                $table_ledger,
                array(
                    'transaction_type' => 'Income',
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
                    ifs_terp_log_activity( "Created Professional Income Voucher [{$category}] for ৳" . number_format( $amount, 2 ) );
                }
                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Income voucher successfully registered and logged in accounts ledger.', 'ifs-travel-erp' ) . '</div>';
            } else {
                $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Database error: Unable to record income voucher.', 'ifs-travel-erp' ) . '</div>';
            }
        }
    }

    // 3. Handle Form Submission for Updating Existing Income Voucher
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_income_update'] ) ) {
        check_admin_referer( 'ifs_income_update_action', 'ifs_income_update_nonce' );

        $edit_id          = intval( $_POST['edit_id'] ?? 0 );
        $category         = sanitize_text_field( $_POST['category'] ?? 'Ticket Commission' );
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
                array( 'id' => $edit_id, 'transaction_type' => 'Income' ),
                array( '%s', '%f', '%s', '%s', '%s', '%s' ),
                array( '%d', '%s' )
            );

            if ( $updated !== false ) {
                if ( function_exists( 'ifs_terp_log_activity' ) ) {
                    ifs_terp_log_activity( "Updated Income Voucher ID #{$edit_id}" );
                }
                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Income voucher updated successfully.', 'ifs-travel-erp' ) . '</div>';
            } else {
                $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Database error: Unable to update voucher.', 'ifs-travel-erp' ) . '</div>';
            }
        }
    }

    if ( isset( $_GET['msg'] ) && $_GET['msg'] === 'deleted' ) {
        $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Income voucher removed successfully.', 'ifs-travel-erp' ) . '</div>';
    }

    $inner_tab = isset( $_GET['view'] ) ? sanitize_text_field( $_GET['view'] ) : 'all';
    $view_id   = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

    // Optional Filter Parameters for All Income View
    $filter_category = isset( $_GET['filter_cat'] ) ? sanitize_text_field( $_GET['filter_cat'] ) : '';
    $filter_method   = isset( $_GET['filter_method'] ) ? sanitize_text_field( $_GET['filter_method'] ) : '';

    $query_where = " WHERE transaction_type = 'Income' ";
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
        $incomes = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_ledger} {$query_where} ORDER BY transaction_date DESC, id DESC", $query_vals ) );
    } else {
        $incomes = $wpdb->get_results( "SELECT * FROM {$table_ledger} WHERE transaction_type = 'Income' ORDER BY transaction_date DESC, id DESC" );
    }

    // Analytics Metrics Calculations
    $total_inc         = (float) $wpdb->get_var( "SELECT SUM(amount) FROM {$table_ledger} WHERE transaction_type = 'Income'" );
    $current_month_inc = (float) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(amount) FROM {$table_ledger} WHERE transaction_type = 'Income' AND transaction_date >= %s", date( 'Y-m-01 00:00:00' ) ) );
    $total_vouchers    = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table_ledger} WHERE transaction_type = 'Income'" );

    $customers  = $wpdb->get_results( "SELECT id, full_name, mobile FROM {$table_customers} ORDER BY full_name ASC" );
    $agents     = $wpdb->get_results( "SELECT id, agency_name FROM {$table_agents} ORDER BY agency_name ASC" );
    $categories = $wpdb->get_col( "SELECT DISTINCT category FROM {$table_ledger} WHERE transaction_type = 'Income' ORDER BY category ASC" );
    ?>
    <div class="wrap ifs-invoice-list-workspace">
        <?php echo $message; ?>

        <!-- Executive KPI Metric Cards Bar -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin-bottom: 22px;">
            <div class="ifs-table-card" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 22px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
                <div style="width: 48px; height: 48px; border-radius: 10px; background: #dcfce7; color: #15803d; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e( 'Total Realized Revenue', 'ifs-travel-erp' ); ?></span>
                    <h3 style="margin: 3px 0 0 0; font-size: 20px; font-weight: 800; color: #15803d; font-family: monospace;">৳<?php echo number_format( $total_inc, 2 ); ?></h3>
                </div>
            </div>

            <div class="ifs-table-card" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 22px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
                <div style="width: 48px; height: 48px; border-radius: 10px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </div>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e( 'Current Month Revenue', 'ifs-travel-erp' ); ?></span>
                    <h3 style="margin: 3px 0 0 0; font-size: 20px; font-weight: 800; color: #0369a1; font-family: monospace;">৳<?php echo number_format( $current_month_inc, 2 ); ?></h3>
                </div>
            </div>

            <div class="ifs-table-card" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 22px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
                <div style="width: 48px; height: 48px; border-radius: 10px; background: #fef3c7; color: #b45309; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
                    <span class="dashicons dashicons-media-document"></span>
                </div>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e( 'Total Income Vouchers', 'ifs-travel-erp' ); ?></span>
                    <h3 style="margin: 3px 0 0 0; font-size: 20px; font-weight: 800; color: #b45309; font-family: monospace;"><?php echo number_format( $total_vouchers ); ?> <?php esc_html_e( 'Entries', 'ifs-travel-erp' ); ?></h3>
                </div>
            </div>
        </div>

        <!-- Pro Navigation Toggle Pills -->
        <div style="display: flex; gap: 8px; margin-bottom: 22px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; justify-content: space-between; align-items: center; flex-wrap: wrap;">
            <div style="display: flex; gap: 8px;">
                <a href="<?php echo esc_url( $base_url . '&view=all' ); ?>" style="padding: 8px 18px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; background: <?php echo ($inner_tab === 'all') ? '#003376' : '#fff'; ?>; color: <?php echo ($inner_tab === 'all') ? '#fff' : '#475569'; ?>; border: 1px solid <?php echo ($inner_tab === 'all') ? '#003376' : '#cbd5e1'; ?>; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                    <span class="dashicons dashicons-list-view" style="font-size: 16px; width: 16px; height: 16px;"></span> <?php esc_html_e( 'All Income Records', 'ifs-travel-erp' ); ?>
                </a>
                <a href="<?php echo esc_url( $base_url . '&view=add' ); ?>" style="padding: 8px 18px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; background: <?php echo ($inner_tab === 'add') ? '#003376' : '#fff'; ?>; color: <?php echo ($inner_tab === 'add') ? '#fff' : '#475569'; ?>; border: 1px solid <?php echo ($inner_tab === 'add') ? '#003376' : '#cbd5e1'; ?>; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                    <span class="dashicons dashicons-plus-alt2" style="font-size: 16px; width: 16px; height: 16px;"></span> <?php esc_html_e( 'Add Income Voucher', 'ifs-travel-erp' ); ?>
                </a>
            </div>
        </div>

        <?php if ( $inner_tab === 'add' ) : ?>
            <!-- Pro Add Income Form Card -->
            <div class="ifs-table-card" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 32px; max-width: 900px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04);">
                <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 16px; margin-bottom: 24px; display: flex; align-items: center; gap: 14px;">
                    <div style="width: 42px; height: 42px; border-radius: 10px; background: #dcfce7; color: #15803d; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                        <span class="dashicons dashicons-chart-line"></span>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 16.5px; font-weight: 800; color: #0f172a;"><?php esc_html_e( 'Create Professional Income Voucher', 'ifs-travel-erp' ); ?></h3>
                        <p style="margin: 3px 0 0 0; font-size: 12.5px; color: #64748b;"><?php esc_html_e( 'Record commercial revenues, ticket commissions, markups, or service fees with absolute precision.', 'ifs-travel-erp' ); ?></p>
                    </div>
                </div>

                <form method="post" action="">
                    <?php wp_nonce_field( 'ifs_income_action', 'ifs_income_nonce' ); ?>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Income Stream Category', 'ifs-travel-erp' ); ?> <span style="color: #e11d48;">*</span></label>
                            <select name="category" class="ifs-select-control" style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; background: #fff; font-size: 13px;">
                                <option value="Ticket Commission"><?php esc_html_e( 'Air Ticket Commission / Markup', 'ifs-travel-erp' ); ?></option>
                                <option value="Visa Processing Profit"><?php esc_html_e( 'Visa Processing Profit / Service Charge', 'ifs-travel-erp' ); ?></option>
                                <option value="Hajj & Umrah Margin"><?php esc_html_e( 'Hajj & Umrah Package Profit', 'ifs-travel-erp' ); ?></option>
                                <option value="Tour Package Margin"><?php esc_html_e( 'Holiday Tour Package Profit', 'ifs-travel-erp' ); ?></option>
                                <option value="Hotel Booking Margin"><?php esc_html_e( 'Hotel Booking Commission', 'ifs-travel-erp' ); ?></option>
                                <option value="Other Commercial Income"><?php esc_html_e( 'Other Commercial Income / Refund Surplus', 'ifs-travel-erp' ); ?></option>
                            </select>
                        </div>

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Income Source Classification', 'ifs-travel-erp' ); ?></label>
                            <select name="income_source" id="ifs_income_source" class="ifs-select-control" style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; background: #fff; font-size: 13px;">
                                <option value="Direct"><?php esc_html_e( 'Direct Collection / Walk-in Client', 'ifs-travel-erp' ); ?></option>
                                <option value="Customer"><?php esc_html_e( 'Registered Customer (B2C)', 'ifs-travel-erp' ); ?></option>
                                <option value="Agent"><?php esc_html_e( 'Sub-Agent (B2B Partner)', 'ifs-travel-erp' ); ?></option>
                            </select>
                        </div>
                    </div>

                    <!-- Conditional Client Dropdowns -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px;">
                        <div id="ifs_customer_box" style="display: none;">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Select Customer Directory', 'ifs-travel-erp' ); ?></label>
                            <select name="client_id_customer" class="ifs-select-control client-target-select" style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; background: #fff; font-size: 13px;">
                                <option value=""><?php esc_html_e( '-- Choose Customer --', 'ifs-travel-erp' ); ?></option>
                                <?php if ( $customers ) : foreach ( $customers as $c ) : ?>
                                    <option value="<?php echo esc_attr( $c->id ); ?>"><?php echo esc_html( $c->full_name . ' (' . $c->mobile . ')' ); ?></option>
                                <?php endforeach; endif; ?>
                            </select>
                        </div>

                        <div id="ifs_agent_box" style="display: none;">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Select Sub-Agent Partner', 'ifs-travel-erp' ); ?></label>
                            <select name="client_id_agent" class="ifs-select-control client-target-select" style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; background: #fff; font-size: 13px;">
                                <option value=""><?php esc_html_e( '-- Choose Sub-Agent --', 'ifs-travel-erp' ); ?></option>
                                <?php if ( $agents ) : foreach ( $agents as $a ) : ?>
                                    <option value="<?php echo esc_attr( $a->id ); ?>"><?php echo esc_html( $a->agency_name ); ?></option>
                                <?php endforeach; endif; ?>
                            </select>
                        </div>
                        <input type="hidden" name="client_id" id="ifs_final_client_id" value="0">

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Realized Amount (৳)', 'ifs-travel-erp' ); ?> <span style="color: #e11d48;">*</span></label>
                            <input type="number" step="0.01" name="amount" required value="0.00" class="font-mono" style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-weight: 800; color: #15803d; font-size: 15px;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 18px; margin-bottom: 18px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Settlement Method', 'ifs-travel-erp' ); ?></label>
                            <select name="payment_method" class="ifs-select-control" style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; background: #fff; font-size: 13px;">
                                <option value="Cash"><?php esc_html_e( 'Cash Drawer', 'ifs-travel-erp' ); ?></option>
                                <option value="Bank Transfer"><?php esc_html_e( 'Bank Transfer (BEFTN/NPSB)', 'ifs-travel-erp' ); ?></option>
                                <option value="bKash / MFS"><?php esc_html_e( 'bKash / Nagad / Rocket', 'ifs-travel-erp' ); ?></option>
                                <option value="Cheque"><?php esc_html_e( 'Bank Cheque', 'ifs-travel-erp' ); ?></option>
                                <option value="Credit Card"><?php esc_html_e( 'POS / Credit Card', 'ifs-travel-erp' ); ?></option>
                            </select>
                        </div>

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Bank / Gateway Account', 'ifs-travel-erp' ); ?></label>
                            <input type="text" name="bank_name" placeholder="e.g. EBL / BRAC / bKash Merchant" style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 13px;">
                        </div>

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Reference / Invoice No', 'ifs-travel-erp' ); ?></label>
                            <input type="text" name="reference_no" placeholder="e.g. INV-2026-001" class="font-mono" style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 13px;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr; gap: 18px; margin-bottom: 18px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Transaction Date & Time', 'ifs-travel-erp' ); ?> <span style="color: #e11d48;">*</span></label>
                            <input type="datetime-local" name="transaction_date" required value="<?php echo esc_attr( date( 'Y-m-d\TH:i' ) ); ?>" class="font-mono" style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 13px;">
                        </div>
                    </div>

                    <div style="margin-bottom: 26px;">
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Particulars / Accounting Narration', 'ifs-travel-erp' ); ?></label>
                        <textarea name="description" rows="3" placeholder="Provide extra accounting particulars, passenger details, or receipt notes..." style="width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 12px; font-size: 13px;"></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 12px;">
                        <a href="<?php echo esc_url( $base_url . '&view=all' ); ?>" class="button" style="height: 42px; line-height: 40px; padding: 0 20px; border-radius: 8px; font-weight: 600;"><?php esc_html_e( 'Cancel', 'ifs-travel-erp' ); ?></a>
                        <button type="submit" name="ifs_income_submit" style="background: #15803d; color: #fff; border: none; height: 42px; padding: 0 28px; border-radius: 8px; font-weight: 700; font-size: 13.5px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(21, 128, 61, 0.2);">
                            <span class="dashicons dashicons-saved" style="margin-top: 1px;"></span> <?php esc_html_e( 'Save Income Voucher', 'ifs-travel-erp' ); ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Dynamic Source Switching Script -->
            <script>
            jQuery(document).ready(function($) {
                $('#ifs_income_source').on('change', function() {
                    var val = $(this).val();
                    $('#ifs_customer_box').hide();
                    $('#ifs_agent_box').hide();
                    $('#ifs_final_client_id').val(0);

                    if (val === 'Customer') {
                        $('#ifs_customer_box').show();
                    } else if (val === 'Agent') {
                        $('#ifs_agent_box').show();
                    }
                });

                $(document).on('change', '.client-target-select', function() {
                    $('#ifs_final_client_id').val($(this).val());
                });
            });
            </script>

        <?php elseif ( $inner_tab === 'view' && $view_id > 0 ) : ?>
            <?php 
            $single_inc = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_ledger} WHERE id = %d AND transaction_type = 'Income'", $view_id ) );
            if ( ! $single_inc ) {
                echo '<div class="notice notice-error"><p>Income voucher record not found.</p></div>';
                return;
            }
            ?>
            <!-- View Income Dossier Card -->
            <div class="ifs-table-card ifs-print-receipt-box" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 36px; max-width: 800px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 24px;">
                    <div>
                        <span style="background: #dcfce7; color: #15803d; padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Income Receipt Dossier</span>
                        <h2 style="margin: 10px 0 4px 0; font-size: 24px; font-weight: 800; color: #0f172a; font-family: monospace;">#TX-<?php echo esc_html( $single_inc->id ); ?></h2>
                        <span style="font-size: 13px; color: #64748b; font-family: monospace;"><?php echo esc_html( date( 'd M Y, h:i A', strtotime( $single_inc->transaction_date ) ) ); ?></span>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 12px; color: #64748b; display: block; font-weight: 700; text-transform: uppercase;">Realized Amount</span>
                        <span style="font-size: 28px; font-weight: 800; color: #15803d; font-family: monospace;">+৳<?php echo number_format( (float) $single_inc->amount, 2 ); ?></span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; background: #f8fafc; padding: 20px; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <div>
                        <strong style="display: block; font-size: 11.5px; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">Income Stream Category</strong>
                        <span style="font-size: 14px; font-weight: 700; color: #0f172a;"><?php echo esc_html( $single_inc->category ); ?></span>
                    </div>
                    <div>
                        <strong style="display: block; font-size: 11.5px; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">Settlement Channel</strong>
                        <span style="font-size: 14px; font-weight: 700; color: #0f172a;"><?php echo esc_html( $single_inc->payment_method ); ?></span>
                    </div>
                    <div>
                        <strong style="display: block; font-size: 11.5px; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">Reference / Invoice No</strong>
                        <span style="font-size: 14px; font-weight: 700; color: #0f172a; font-family: monospace;"><?php echo esc_html( $single_inc->reference_no ?: 'N/A' ); ?></span>
                    </div>
                    <div>
                        <strong style="display: block; font-size: 11.5px; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">Logged By User ID</strong>
                        <span style="font-size: 14px; font-weight: 700; color: #0f172a;">#<?php echo esc_html( $single_inc->logged_by ); ?></span>
                    </div>
                </div>

                <div style="margin-bottom: 28px;">
                    <strong style="display: block; font-size: 11.5px; color: #64748b; text-transform: uppercase; margin-bottom: 6px;">Particulars / Description Details</strong>
                    <div style="background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 14px; font-size: 13.5px; color: #334155; line-height: 1.5; min-height: 60px;">
                        <?php echo nl2br( esc_html( $single_inc->description ?: 'No additional particulars recorded.' ) ); ?>
                    </div>
                </div>

                <div class="ifs-no-print" style="display: flex; justify-content: space-between; align-items: center;">
                    <a href="<?php echo esc_url( $base_url . '&view=all' ); ?>" class="button" style="height: 38px; line-height: 36px; padding: 0 16px; border-radius: 6px; font-weight: 600;">&larr; Back to Register</a>
                    <div style="display: flex; gap: 8px;">
                        <a href="<?php echo esc_url( $base_url . '&view=edit&id=' . $single_inc->id ); ?>" class="button button-primary" style="background: #003376; border-color: #003376; height: 38px; line-height: 36px; padding: 0 16px; border-radius: 6px; font-weight: 700;">Edit Voucher</a>
                        <button type="button" onclick="window.print();" class="button" style="height: 38px; line-height: 36px; padding: 0 16px; border-radius: 6px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                            <span class="dashicons dashicons-printer" style="font-size: 14px; width: 14px; height: 14px; margin-top: 2px;"></span> Print Receipt
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
            $edit_inc = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_ledger} WHERE id = %d AND transaction_type = 'Income'", $view_id ) );
            if ( ! $edit_inc ) {
                echo '<div class="notice notice-error"><p>Income voucher record not found for editing.</p></div>';
                return;
            }
            ?>
            <!-- Pro Edit Income Form Card -->
            <div class="ifs-table-card" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 32px; max-width: 850px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04);">
                <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 16px; margin-bottom: 24px; display: flex; align-items: center; gap: 14px;">
                    <div style="width: 42px; height: 42px; border-radius: 10px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                        <span class="dashicons dashicons-edit"></span>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 16.5px; font-weight: 800; color: #0f172a;"><?php esc_html_e( 'Edit Income Voucher #TX-', 'ifs-travel-erp' ); ?><?php echo esc_html( $edit_inc->id ); ?></h3>
                        <p style="margin: 3px 0 0 0; font-size: 12.5px; color: #64748b;"><?php esc_html_e( 'Modify financial parameters or particulars for this entry.', 'ifs-travel-erp' ); ?></p>
                    </div>
                </div>

                <form method="post" action="">
                    <?php wp_nonce_field( 'ifs_income_update_action', 'ifs_income_update_nonce' ); ?>
                    <input type="hidden" name="edit_id" value="<?php echo esc_attr( $edit_inc->id ); ?>">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Income Stream Category', 'ifs-travel-erp' ); ?> <span style="color: #e11d48;">*</span></label>
                            <input type="text" name="category" required value="<?php echo esc_attr( $edit_inc->category ); ?>" style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 13px;">
                        </div>

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Realized Amount (৳)', 'ifs-travel-erp' ); ?> <span style="color: #e11d48;">*</span></label>
                            <input type="number" step="0.01" name="amount" required value="<?php echo esc_attr( $edit_inc->amount ); ?>" class="font-mono" style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-weight: 800; color: #15803d; font-size: 15px;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Settlement Method', 'ifs-travel-erp' ); ?></label>
                            <select name="payment_method" class="ifs-select-control" style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; background: #fff; font-size: 13px;">
                                <option value="Cash" <?php selected( $edit_inc->payment_method, 'Cash' ); ?>><?php esc_html_e( 'Cash Drawer', 'ifs-travel-erp' ); ?></option>
                                <option value="Bank Transfer" <?php selected( $edit_inc->payment_method, 'Bank Transfer' ); ?>><?php esc_html_e( 'Bank Transfer', 'ifs-travel-erp' ); ?></option>
                                <option value="bKash / MFS" <?php selected( $edit_inc->payment_method, 'bKash / MFS' ); ?>><?php esc_html_e( 'bKash / MFS', 'ifs-travel-erp' ); ?></option>
                                <option value="Cheque" <?php selected( $edit_inc->payment_method, 'Cheque' ); ?>><?php esc_html_e( 'Bank Cheque', 'ifs-travel-erp' ); ?></option>
                            </select>
                        </div>

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Reference / Invoice No', 'ifs-travel-erp' ); ?></label>
                            <input type="text" name="reference_no" value="<?php echo esc_attr( $edit_inc->reference_no ); ?>" class="font-mono" style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 13px;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr; gap: 18px; margin-bottom: 18px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Transaction Date & Time', 'ifs-travel-erp' ); ?> <span style="color: #e11d48;">*</span></label>
                            <input type="datetime-local" name="transaction_date" required value="<?php echo esc_attr( date( 'Y-m-d\TH:i', strtotime( $edit_inc->transaction_date ) ) ); ?>" class="font-mono" style="width: 100%; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 13px;">
                        </div>
                    </div>

                    <div style="margin-bottom: 26px;">
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;"><?php esc_html_e( 'Particulars / Accounting Narration', 'ifs-travel-erp' ); ?></label>
                        <textarea name="description" rows="3" style="width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 12px; font-size: 13px;"><?php echo esc_textarea( $edit_inc->description ); ?></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 12px;">
                        <a href="<?php echo esc_url( $base_url . '&view=view&id=' . $edit_inc->id ); ?>" class="button" style="height: 42px; line-height: 40px; padding: 0 20px; border-radius: 8px; font-weight: 600;"><?php esc_html_e( 'Cancel', 'ifs-travel-erp' ); ?></a>
                        <button type="submit" name="ifs_income_update" style="background: #003376; color: #fff; border: none; height: 42px; padding: 0 28px; border-radius: 8px; font-weight: 700; font-size: 13.5px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                            <span class="dashicons dashicons-saved" style="margin-top: 1px;"></span> <?php esc_html_e( 'Update Income Voucher', 'ifs-travel-erp' ); ?>
                        </button>
                    </div>
                </form>
            </div>

        <?php else : ?>
            <!-- Pro All Income Table Card with Filter Bar -->
            <div class="ifs-table-card" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);">
                <div class="ifs-custom-table-controls" style="padding: 18px 26px; background: #f8fafc; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
                    <form method="get" action="" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <input type="hidden" name="page" value="ifs_travel_erp">
                        <input type="hidden" name="tab" value="accounts">
                        <input type="hidden" name="sub" value="income">
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

                    <div style="font-size: 14px; font-weight: 800; color: #15803d; font-family: monospace; background: #dcfce7; padding: 7px 16px; border-radius: 8px; border: 1px solid #a7f3d0;">
                        <?php esc_html_e( 'Total Revenue:', 'ifs-travel-erp' ); ?> ৳<?php echo number_format( $total_inc, 2 ); ?>
                    </div>
                </div>

                <div class="table-scroll" style="padding: 16px 24px 24px 24px; overflow-x: auto;">
                    <table class="ifs-pro-datatable" id="ifsIncomeTable" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <thead>
                            <tr style="background: #f8fafc; text-align: left;">
                                <th style="padding: 12px 16px; border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 11px; text-transform: uppercase;"><?php esc_html_e( 'Txn ID', 'ifs-travel-erp' ); ?></th>
                                <th style="padding: 12px 16px; border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 11px; text-transform: uppercase;"><?php esc_html_e( 'Category & Details', 'ifs-travel-erp' ); ?></th>
                                <th style="padding: 12px 16px; border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 11px; text-transform: uppercase;"><?php esc_html_e( 'Payment Method', 'ifs-travel-erp' ); ?></th>
                                <th style="padding: 12px 16px; border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 11px; text-transform: uppercase; text-align: right;"><?php esc_html_e( 'Realized Amount', 'ifs-travel-erp' ); ?></th>
                                <th style="padding: 12px 16px; border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 11px; text-transform: uppercase;"><?php esc_html_e( 'Date & Time', 'ifs-travel-erp' ); ?></th>
                                <th style="padding: 12px 16px; border-bottom: 2px solid #e2e8f0; text-align: right; width: 140px;"><?php esc_html_e( 'Actions', 'ifs-travel-erp' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( $incomes ) : foreach ( $incomes as $inc ) : ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 14px 16px;"><span class="ifs-id-badge font-mono" style="background: #f1f5f9; padding: 3px 8px; border-radius: 4px; font-weight: 700; color: #334155;">#TX-<?php echo esc_html( $inc->id ); ?></span></td>
                                    <td style="padding: 14px 16px;">
                                        <div style="font-weight: 700; color: #0f172a;"><?php echo esc_html( $inc->category ); ?></div>
                                        <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;"><?php echo esc_html( $inc->reference_no ? 'Ref: ' . $inc->reference_no . ' — ' : '' ); ?><?php echo esc_html( $inc->description ); ?></div>
                                    </td>
                                    <td style="padding: 14px 16px; font-weight: 600; color: #334155;">
                                        <span style="background: #f8fafc; border: 1px solid #cbd5e1; padding: 2px 8px; border-radius: 4px; font-size: 11.5px;"><?php echo esc_html( $inc->payment_method ); ?></span>
                                    </td>
                                    <td style="padding: 14px 16px; text-align: right; font-weight: 800; font-family: monospace; color: #15803d; font-size: 14px;">
                                        +৳<?php echo number_format( (float) $inc->amount, 2 ); ?>
                                    </td>
                                    <td style="padding: 14px 16px; font-family: monospace; font-size: 12px; color: #64748b;"><?php echo esc_html( date( 'd M Y, h:i A', strtotime( $inc->transaction_date ) ) ); ?></td>
                                    <td style="padding: 14px 16px; text-align: right;">
                                        <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                            <a href="<?php echo esc_url( $base_url . '&view=view&id=' . $inc->id ); ?>" class="ifs-action-pill view" style="background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; padding: 5px 8px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 3px;" title="View Dossier">
                                                <span class="dashicons dashicons-visibility" style="font-size: 13px; width: 13px; height: 13px;"></span>
                                            </a>
                                            <a href="<?php echo esc_url( $base_url . '&view=edit&id=' . $inc->id ); ?>" class="ifs-action-pill edit" style="background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; padding: 5px 8px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 3px;" title="Edit Voucher">
                                                <span class="dashicons dashicons-edit" style="font-size: 13px; width: 13px; height: 13px;"></span>
                                            </a>
                                            <a href="<?php echo wp_nonce_url( $base_url . '&view=all&del=1&id=' . $inc->id, 'delete_income_' . $inc->id ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to permanently delete this income voucher?', 'ifs-travel-erp' ); ?>');" class="ifs-action-pill delete" style="background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; padding: 5px 8px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 3px;" title="Delete">
                                                <span class="dashicons dashicons-trash" style="font-size: 13px; width: 13px; height: 13px;"></span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; else : ?>
                                <tr><td colspan="6" style="text-align: center; padding: 45px; color: #94a3b8; font-style: italic;"><?php esc_html_e( 'No income vouchers registered in the system yet.', 'ifs-travel-erp' ); ?></td></tr>
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
        if ($.fn.DataTable && $('#ifsIncomeTable').length) {
            $('#ifsIncomeTable').DataTable({
                "pageLength": 15,
                "ordering": true,
                "info": true,
                "searching": true,
                "lengthChange": false,
                "order": [[ 0, "desc" ]],
                "language": {
                    "info": "Showing _START_ to _END_ of _TOTAL_ income vouchers",
                    "infoEmpty": "Showing 0 to 0 of 0 vouchers",
                    "paginate": { "previous": "&larr; Prev", "next": "Next &rarr;" }
                }
            });
        }
    });
    </script>
    <?php
}