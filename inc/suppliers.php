<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Modern Segmented Sub-Navigation for Suppliers Module
 */
function ifs_terp_suppliers_render_tabs( $active_tab = 'list' ) {
    global $wpdb;
    $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=suppliers' );

    $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
    $total_suppliers = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_suppliers" );
    $total_balance   = (float) $wpdb->get_var( "SELECT SUM(current_balance) FROM $table_suppliers WHERE status = 'Active'" );
    $total_gds       = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_suppliers WHERE supplier_type LIKE '%GDS%' OR supplier_type LIKE '%IATA%'" );
    ?>
    <div class="ifs-pro-tab-wrapper">
        <div class="ifs-pro-header-card">
            <div class="ifs-pro-identity">
                <div class="ifs-pro-icon-glow">
                    <span class="dashicons dashicons-chart-area"></span>
                </div>
                <div class="ifs-pro-title-meta">
                    <div class="ifs-pro-badge-group">
                        <span class="ifs-status-dot"></span>
                        <span class="ifs-meta-tag">Consortia & Vendors</span>
                        <span class="ifs-meta-tag-blue">B2B Supplies</span>
                    </div>
                    <h2 class="ifs-pro-heading">Suppliers & GDS Portal Ledgers</h2>
                    <p class="ifs-pro-caption">Track IATA/GDS deposits, B2B air portal balances, and vendor top-up histories</p>
                </div>
            </div>
            
            <div class="ifs-pro-stats-strip">
                <div class="ifs-stat-pill">
                    <span class="ifs-stat-lbl">Active Portals</span>
                    <span class="ifs-stat-num color-dark"><?php echo number_format( $total_suppliers ); ?></span>
                </div>
                <div class="ifs-stat-pill">
                    <span class="ifs-stat-lbl">GDS / IATA Consortia</span>
                    <span class="ifs-stat-num color-blue"><?php echo number_format( $total_gds ); ?></span>
                </div>
                <div class="ifs-stat-pill">
                    <span class="ifs-stat-lbl">Total Portal Balance</span>
                    <span class="ifs-stat-num color-emerald">৳<?php echo number_format( $total_balance, 2 ); ?></span>
                </div>
            </div>
        </div>

        <div class="ifs-pro-nav-container">
            <nav class="ifs-pro-nav-bar">
                <a href="<?php echo esc_url( $base_url . '&sub=list' ); ?>" 
                   class="ifs-pro-nav-btn <?php echo ( $active_tab === 'list' ) ? 'active-tab' : ''; ?>">
                    <span class="dashicons dashicons-list-view"></span>
                    <span class="ifs-btn-label">Supplier Directory</span>
                    <span class="ifs-pro-counter"><?php echo $total_suppliers; ?></span>
                </a>

                <a href="<?php echo esc_url( $base_url . '&sub=add' ); ?>" 
                   class="ifs-pro-nav-btn <?php echo ( $active_tab === 'add' ) ? 'active-tab' : ''; ?>">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <span class="ifs-btn-label">Register Supplier</span>
                </a>

                <a href="<?php echo esc_url( $base_url . '&sub=topup' ); ?>" 
                   class="ifs-pro-nav-btn <?php echo ( $active_tab === 'topup' ) ? 'active-tab' : ''; ?>">
                    <span class="dashicons dashicons-money-alt"></span>
                    <span class="ifs-btn-label">Post Balance Top-Up</span>
                </a>

                <a href="<?php echo esc_url( $base_url . '&sub=ledger' ); ?>" 
                   class="ifs-pro-nav-btn <?php echo ( $active_tab === 'ledger' ) ? 'active-tab' : ''; ?>">
                    <span class="dashicons dashicons-book"></span>
                    <span class="ifs-btn-label">Transaction Ledger</span>
                </a>
            </nav>
        </div>
    </div>

    <style>
        .ifs-pro-tab-wrapper { margin-bottom: 30px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .ifs-pro-header-card { background: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; padding: 24px 28px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04); margin-bottom: 18px; }
        .ifs-pro-identity { display: flex; align-items: center; gap: 18px; }
        .ifs-pro-icon-glow { width: 52px; height: 52px; border-radius: 14px; background: linear-gradient(135deg, #003376 0%, #0284c7 100%); display: flex; align-items: center; justify-content: center; color: #ffffff; box-shadow: 0 8px 18px -4px rgba(0, 51, 118, 0.35); flex-shrink: 0; }
        .ifs-pro-icon-glow .dashicons { font-size: 26px; width: 26px; height: 26px; }
        .ifs-pro-badge-group { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
        .ifs-status-dot { width: 6px; height: 6px; border-radius: 50%; background: #0284c7; display: inline-block; }
        .ifs-meta-tag { font-size: 10.5px; font-weight: 700; text-transform: uppercase; color: #64748b; }
        .ifs-meta-tag-blue { font-size: 10px; font-weight: 700; text-transform: uppercase; background: #e0f2fe; color: #0369a1; padding: 2px 7px; border-radius: 4px; }
        .ifs-pro-heading { margin: 0; font-size: 22px; font-weight: 800; color: #0f172a; }
        .ifs-pro-caption { margin: 3px 0 0 0; font-size: 13.5px; color: #64748b; }
        .ifs-pro-stats-strip { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .ifs-stat-pill { background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 16px; border-radius: 10px; display: flex; flex-direction: column; gap: 2px; min-width: 100px; }
        .ifs-stat-lbl { font-size: 10.5px; font-weight: 600; color: #64748b; text-transform: uppercase; }
        .ifs-stat-num { font-size: 16px; font-weight: 800; }
        .color-dark { color: #0f172a; }
        .color-blue { color: #0284c7; }
        .color-emerald { color: #059669; }
        .ifs-pro-nav-container { display: flex; align-items: center; }
        .ifs-pro-nav-bar { background: #f1f5f9; border: 1px solid #e2e8f0; padding: 5px; border-radius: 12px; display: inline-flex; align-items: center; gap: 6px; max-width: 100%; overflow-x: auto; }
        .ifs-pro-nav-btn { display: inline-flex; align-items: center; gap: 9px; padding: 10px 20px; font-size: 13.5px; font-weight: 600; color: #64748b; text-decoration: none; border-radius: 9px; transition: all 0.2s ease; cursor: pointer; white-space: nowrap; border: 1px solid transparent; }
        .ifs-pro-nav-btn:hover { color: #0f172a; background: rgba(255, 255, 255, 0.65); }
        .ifs-pro-nav-btn.active-tab { background: #ffffff; color: #003376; font-weight: 700; border: 1px solid rgba(0, 51, 118, 0.08); box-shadow: 0 4px 12px rgba(0, 51, 118, 0.06); }
        .ifs-pro-counter { background: #e2e8f0; color: #475569; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 20px; }
        .ifs-pro-nav-btn.active-tab .ifs-pro-counter { background: #003376; color: #ffffff; }
    </style>
    <?php
}

/**
 * Main Controller for Suppliers & GDS Module
 */
function ifs_terp_suppliers_tab() {
    global $wpdb;
    $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
    $table_ledger    = $wpdb->prefix . 'iterp_supplier_ledger';
    $base_url        = admin_url( 'admin.php?page=ifs_travel_erp&tab=suppliers' );

    $sub_action = isset( $_GET['sub'] ) ? sanitize_text_field( $_GET['sub'] ) : 'list';
    $message    = '';

    /* =========================================================================
       1. HANDLE DELETE SUPPLIER
       ========================================================================= */
    if ( $sub_action === 'delete' && isset( $_GET['id'] ) ) {
        $del_id = intval( $_GET['id'] );
        check_admin_referer( 'delete_supplier_' . $del_id );
        
        $supp_name = $wpdb->get_var( $wpdb->prepare( "SELECT supplier_name FROM $table_suppliers WHERE id = %d", $del_id ) );
        $wpdb->delete( $table_suppliers, array( 'id' => $del_id ), array( '%d' ) );
        
        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            ifs_terp_log_activity( "Deleted Supplier: #SUP-$del_id ($supp_name)" );
        }
        
        wp_safe_redirect( add_query_arg( array( 'page' => 'ifs_travel_erp', 'tab' => 'suppliers', 'msg' => 'deleted' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    if ( isset( $_GET['msg'] ) && $_GET['msg'] === 'deleted' ) {
        $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Supplier record deleted successfully.</div>';
    }

    /* =========================================================================
       2. HANDLE ADD / EDIT SUPPLIER
       ========================================================================= */
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_add_supplier_submit'] ) ) {
        check_admin_referer( 'ifs_add_supplier_action', 'ifs_add_supplier_nonce' );

        $edit_id      = isset( $_POST['edit_id'] ) ? intval( $_POST['edit_id'] ) : 0;
        $is_edit_mode = ( $edit_id > 0 );

        $data_array = array(
            'supplier_name'  => sanitize_text_field( $_POST['supplier_name'] ?? '' ),
            'supplier_type'  => sanitize_text_field( $_POST['supplier_type'] ?? 'GDS / IATA' ),
            'contact_person' => sanitize_text_field( $_POST['contact_person'] ?? '' ),
            'phone'          => sanitize_text_field( $_POST['phone'] ?? '' ),
            'email'          => sanitize_email( $_POST['email'] ?? '' ),
            'status'         => sanitize_text_field( $_POST['status'] ?? 'Active' ),
        );

        if ( $is_edit_mode ) {
            $wpdb->update( $table_suppliers, $data_array, array( 'id' => $edit_id ) );
            $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Supplier details updated successfully.</div>';
            if ( function_exists( 'ifs_terp_log_activity' ) ) {
                ifs_terp_log_activity( "Updated Supplier: " . $data_array['supplier_name'] . " (ID: #SUP-$edit_id)" );
            }
        } else {
            $initial_bal = floatval( $_POST['initial_balance'] ?? 0 );
            $data_array['current_balance'] = $initial_bal;
            $data_array['created_at']      = current_time( 'mysql' );
            $wpdb->insert( $table_suppliers, $data_array );
            $new_id = $wpdb->insert_id;

            if ( $initial_bal > 0 ) {
                $wpdb->insert(
                    $table_ledger,
                    array(
                        'supplier_id'    => $new_id,
                        'reference_type' => 'Opening Balance',
                        'debit'          => 0,
                        'credit'         => $initial_bal,
                        'balance_after'  => $initial_bal,
                        'note'           => 'Initial deposit during supplier registration',
                        'created_at'     => current_time( 'mysql' )
                    ),
                    array( '%d', '%s', '%f', '%f', '%f', '%s', '%s' )
                );
            }

            if ( function_exists( 'ifs_terp_log_activity' ) ) {
                ifs_terp_log_activity( "Registered New Supplier: " . $data_array['supplier_name'] . " (ID: #SUP-$new_id)" );
            }
            $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Supplier / Consortia added successfully.</div>';
        }
    }

    /* =========================================================================
       3. HANDLE BALANCE TOP-UP / DEPOSIT
       ========================================================================= */
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_supplier_payment_submit'] ) ) {
        check_admin_referer( 'ifs_supp_pay_action', 'ifs_supp_pay_nonce' );

        $supp_id     = intval( $_POST['supplier_id'] ?? 0 );
        $amount      = floatval( $_POST['amount'] ?? 0 );
        $pay_method  = sanitize_text_field( $_POST['payment_method'] ?? 'Bank Transfer' );
        $note        = sanitize_textarea_field( $_POST['note'] ?? '' );

        if ( $supp_id > 0 && $amount > 0 ) {
            $current_bal = (float) $wpdb->get_var( $wpdb->prepare( "SELECT current_balance FROM $table_suppliers WHERE id = %d", $supp_id ) );
            $new_bal     = $current_bal + $amount;

            $wpdb->update( $table_suppliers, array( 'current_balance' => $new_bal ), array( 'id' => $supp_id ) );

            $wpdb->insert(
                $table_ledger,
                array(
                    'supplier_id'    => $supp_id,
                    'reference_type' => $pay_method . ' Deposit',
                    'debit'          => 0,
                    'credit'         => $amount,
                    'balance_after'  => $new_bal,
                    'note'           => $note,
                    'created_at'     => current_time( 'mysql' )
                ),
                array( '%d', '%s', '%f', '%f', '%f', '%s', '%s' )
            );

            if ( function_exists( 'ifs_terp_log_activity' ) ) {
                ifs_terp_log_activity( "Posted Deposit ৳$amount to Supplier ID #SUP-$supp_id ($pay_method)" );
            }

            $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Deposit posted and supplier balance updated successfully.</div>';
        } else {
            $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> Please select a supplier and enter a valid deposit amount.</div>';
        }
    }

    echo '<div class="ifs-suppliers-workspace">';
    
    ifs_terp_suppliers_render_tabs( $sub_action );
    echo $message;

    /* =========================================================================
       SUB-TAB 1: REGISTER / EDIT SUPPLIER FORM
       ========================================================================= */
    if ( $sub_action === 'add' || $sub_action === 'edit' ) {
        $edit_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
        $edit_data = false;
        if ( $edit_id > 0 ) {
            $edit_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_suppliers WHERE id = %d", $edit_id ) );
        }

        $val_name    = $edit_data ? esc_attr( $edit_data->supplier_name ) : 'FlyHub B2B Portal';
        $val_type    = $edit_data ? esc_attr( $edit_data->supplier_type ) : 'B2B Air Portal';
        $val_person  = $edit_data ? esc_attr( $edit_data->contact_person ) : 'Key Account Manager';
        $val_phone   = $edit_data ? esc_attr( $edit_data->phone ) : '+880 1700 000000';
        $val_email   = $edit_data ? esc_attr( $edit_data->email ) : 'support@flyhub.com';
        $val_bal     = $edit_data ? floatval( $edit_data->current_balance ) : 50000;
        $val_status  = $edit_data ? esc_attr( $edit_data->status ) : 'Active';
        ?>
        
        <form method="post" action="<?php echo esc_url( $base_url . '&sub=' . $sub_action . ( $edit_id ? '&id=' . $edit_id : '' ) ); ?>" class="ifs-split-supp-editor">
            <?php wp_nonce_field( 'ifs_add_supplier_action', 'ifs_add_supplier_nonce' ); ?>
            <?php if ( $edit_data ) : ?>
                <input type="hidden" name="edit_id" value="<?php echo esc_attr( $edit_data->id ); ?>">
            <?php endif; ?>

            <div class="ifs-supp-form-body">
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">01</div>
                        <div>
                            <h3 class="ifs-card-title"><?php echo $edit_data ? 'Update Supplier Profile: ' . esc_html( $edit_data->supplier_name ) : 'Register New Supplier / GDS Consortia'; ?></h3>
                            <p class="ifs-card-desc">Configure B2B ticketing portals, IATA suppliers, hotel wholesalers, and API aggregators</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <div class="ifs-field-block col-span-2">
                            <label class="ifs-field-label" for="inp_supplier_name">Supplier / Company Name <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-networking field-icon"></span>
                                <input type="text" name="supplier_name" id="inp_supplier_name" required 
                                       value="<?php echo $val_name; ?>" 
                                       placeholder="e.g. FlyHub / ShareTrip / Sabre / Amadeus" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_supplier_type">Supplier Category <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-tag field-icon"></span>
                                <select name="supplier_type" id="inp_supplier_type" class="ifs-input-field">
                                    <option value="GDS / IATA" <?php selected( $val_type, 'GDS / IATA' ); ?>>GDS / IATA Consortia (Sabre/Amadeus)</option>
                                    <option value="B2B Air Portal" <?php selected( $val_type, 'B2B Air Portal' ); ?>>B2B Air Ticketing Portal</option>
                                    <option value="Visa Vendor" <?php selected( $val_type, 'Visa Vendor' ); ?>>Visa Processing Vendor</option>
                                    <option value="Hotel Wholesaler" <?php selected( $val_type, 'Hotel Wholesaler' ); ?>>Hotel / Bedbank Wholesaler</option>
                                    <option value="Hajj / Moallem" <?php selected( $val_type, 'Hajj / Moallem' ); ?>>Hajj & Umrah Moallem / Operator</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_contact_person">Contact Person / Account Manager</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-admin-users field-icon"></span>
                                <input type="text" name="contact_person" id="inp_contact_person" 
                                       value="<?php echo $val_person; ?>" placeholder="e.g. Mr. Rafiqul Islam" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_phone">Contact Phone / Hotline <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-phone field-icon"></span>
                                <input type="text" name="phone" id="inp_phone" required 
                                       value="<?php echo $val_phone; ?>" placeholder="017XXXXXXXX" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_email">Official Email Address</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-email field-icon"></span>
                                <input type="email" name="email" id="inp_email" 
                                       value="<?php echo $val_email; ?>" placeholder="support@portal.com" class="ifs-input-field">
                            </div>
                        </div>

                        <?php if ( ! $edit_data ) : ?>
                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_initial_balance">Opening Deposit Balance (৳)</label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-money-alt field-icon"></span>
                                    <input type="number" step="0.01" name="initial_balance" id="inp_initial_balance" 
                                           value="<?php echo $val_bal; ?>" placeholder="0.00" class="ifs-input-field font-mono font-bold color-emerald">
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="ifs-field-block <?php echo $edit_data ? 'col-span-3' : 'col-span-2'; ?>">
                            <label class="ifs-field-label" for="inp_status">Account Status</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-yes-alt field-icon"></span>
                                <select name="status" id="inp_status" class="ifs-input-field">
                                    <option value="Active" <?php selected( $val_status, 'Active' ); ?>>Active (Enabled for Ticketing)</option>
                                    <option value="Suspended" <?php selected( $val_status, 'Suspended' ); ?>>Suspended (Low Balance / Hold)</option>
                                    <option value="Inactive" <?php selected( $val_status, 'Inactive' ); ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ifs-action-strip">
                    <a href="<?php echo esc_url( $base_url . '&sub=list' ); ?>" class="ifs-btn-back">
                        <span class="dashicons dashicons-arrow-left-alt"></span> Cancel
                    </a>
                    <button type="submit" name="ifs_add_supplier_submit" class="ifs-btn-primary">
                        <span class="dashicons dashicons-saved"></span> <?php echo $edit_data ? 'Update Supplier Profile' : 'Save Supplier Record'; ?>
                    </button>
                </div>
            </div>

            <!-- Right Sidebar: Live Supplier Card Preview -->
            <div class="ifs-preview-sidebar">
                <div class="ifs-preview-sticky">
                    <div class="ifs-card-preview-header">
                        <span class="dashicons dashicons-visibility"></span> Live Portal Card Preview
                    </div>

                    <div class="ifs-portal-card">
                        <div class="portal-head-strip">
                            <span class="portal-category-tag" id="prev_supp_type">B2B AIR PORTAL</span>
                            <span class="portal-status-badge" id="prev_supp_status">ACTIVE</span>
                        </div>

                        <div class="portal-hero">
                            <h3 class="portal-name" id="prev_supp_name">FLYHUB B2B PORTAL</h3>
                            <span class="portal-person-sub" id="prev_supp_person"><span class="dashicons dashicons-admin-users"></span> Key Account Manager</span>
                        </div>

                        <div class="portal-balance-hero font-mono">
                            <span class="balance-lbl">LIVE DEPOSIT BALANCE</span>
                            <h2 class="balance-val" id="prev_supp_bal">৳50,000.00</h2>
                            <span class="balance-sub">Available for Issuance</span>
                        </div>

                        <div class="portal-contact-strip">
                            <div>
                                <span class="c-lbl">PHONE / HOTLINE</span>
                                <strong class="c-val font-mono" id="prev_supp_phone">+880 1700 000000</strong>
                            </div>
                            <div style="text-align: right;">
                                <span class="c-lbl">EMAIL SUPPORT</span>
                                <strong class="c-val" id="prev_supp_email">support@flyhub.com</strong>
                            </div>
                        </div>

                        <div class="portal-footer-strip">
                            <span class="dashicons dashicons-shield"></span> Automated Multi-Module Cost Settlement Link
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sName   = document.getElementById('inp_supplier_name');
            const sType   = document.getElementById('inp_supplier_type');
            const sPerson = document.getElementById('inp_contact_person');
            const sPhone  = document.getElementById('inp_phone');
            const sEmail  = document.getElementById('inp_email');
            const sBal    = document.getElementById('inp_initial_balance');
            const sStatus = document.getElementById('inp_status');

            const prevName   = document.getElementById('prev_supp_name');
            const prevType   = document.getElementById('prev_supp_type');
            const prevPerson = document.getElementById('prev_supp_person');
            const prevPhone  = document.getElementById('prev_supp_phone');
            const prevEmail  = document.getElementById('prev_supp_email');
            const prevBal    = document.getElementById('prev_supp_bal');
            const prevStatus = document.getElementById('prev_supp_status');

            function updateSupplierPreview() {
                if (prevName)   prevName.textContent   = (sName && sName.value.trim()) ? sName.value.trim().toUpperCase() : 'SUPPLIER / PORTAL NAME';
                if (prevType)   prevType.textContent   = (sType) ? sType.value.toUpperCase() : 'B2B AIR PORTAL';
                if (prevPerson) prevPerson.innerHTML   = '<span class="dashicons dashicons-admin-users"></span> ' + ((sPerson && sPerson.value.trim()) ? sPerson.value.trim() : 'Account Manager');
                if (prevPhone)  prevPhone.textContent  = (sPhone && sPhone.value.trim()) ? sPhone.value.trim() : '+880 1700 000000';
                if (prevEmail)  prevEmail.textContent  = (sEmail && sEmail.value.trim()) ? sEmail.value.trim() : 'support@portal.com';
                if (prevStatus) prevStatus.textContent = (sStatus) ? sStatus.value.toUpperCase() : 'ACTIVE';

                if (sBal) {
                    const bVal = parseFloat(sBal.value) || 0;
                    if (prevBal) prevBal.textContent = '৳' + bVal.toLocaleString('en-US', { minimumFractionDigits: 2 });
                }
            }

            [sName, sType, sPerson, sPhone, sEmail, sBal, sStatus].forEach(el => {
                if (el) {
                    el.addEventListener('input', updateSupplierPreview);
                    el.addEventListener('change', updateSupplierPreview);
                }
            });
            updateSupplierPreview();
        });
        </script>
        <?php
    }

    /* =========================================================================
       SUB-TAB 2: POST BALANCE TOP-UP / DEPOSIT FORM
       ========================================================================= */
    elseif ( $sub_action === 'topup' ) {
        $suppliers = $wpdb->get_results( "SELECT id, supplier_name, current_balance, supplier_type FROM $table_suppliers WHERE status = 'Active' ORDER BY supplier_name ASC" );
        ?>
        <div class="ifs-split-supp-editor">
            <div class="ifs-supp-form-body">
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">01</div>
                        <div>
                            <h3 class="ifs-card-title">Post Deposit / Balance Top-Up</h3>
                            <p class="ifs-card-desc">Credit supplier deposit balances, record bank payments, and update live purchasing power</p>
                        </div>
                    </div>

                    <form method="post" action="<?php echo esc_url( $base_url . '&sub=topup' ); ?>">
                        <?php wp_nonce_field( 'ifs_supp_pay_action', 'ifs_supp_pay_nonce' ); ?>
                        
                        <div class="ifs-grid-2">
                            <div class="ifs-field-block col-span-2">
                                <label class="ifs-field-label" for="sel_topup_supplier">Select Portal / Supplier <span class="req">*</span></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-networking field-icon"></span>
                                    <select name="supplier_id" id="sel_topup_supplier" required class="ifs-input-field">
                                        <option value="">-- Choose Supplier / GDS Portal --</option>
                                        <?php foreach ( $suppliers as $s ) : ?>
                                            <option value="<?php echo $s->id; ?>" 
                                                    data-name="<?php echo esc_attr( $s->supplier_name ); ?>"
                                                    data-type="<?php echo esc_attr( $s->supplier_type ); ?>"
                                                    data-bal="<?php echo esc_attr( $s->current_balance ); ?>">
                                                <?php echo esc_html( $s->supplier_name ); ?> (Current Balance: ৳<?php echo number_format( $s->current_balance, 2 ); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_topup_amount">Deposit Amount (৳) <span class="req">*</span></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-money-alt field-icon"></span>
                                    <input type="number" step="0.01" name="amount" id="inp_topup_amount" required 
                                           placeholder="0.00" class="ifs-input-field font-mono font-bold color-emerald">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_payment_method">Payment Mode / Source <span class="req">*</span></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-bank field-icon"></span>
                                    <select name="payment_method" id="inp_payment_method" class="ifs-input-field">
                                        <option value="Bank Transfer (BEFTN/RTGS/NPSB)">Bank Transfer (BEFTN/RTGS/NPSB)</option>
                                        <option value="Cheque Deposit">Cheque Deposit</option>
                                        <option value="Corporate Credit Card">Corporate Credit Card</option>
                                        <option value="Cash Deposit">Direct Cash Deposit</option>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block col-span-2">
                                <label class="ifs-field-label" for="inp_topup_note">Bank Transaction Reference / Deposit Slip Note</label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-edit field-icon"></span>
                                    <input type="text" name="note" id="inp_topup_note" 
                                           placeholder="e.g. City Bank CD A/C #1102983 | Trx ID: TXN-881928" class="ifs-input-field">
                                </div>
                            </div>
                        </div>

                        <div class="ifs-action-strip" style="margin-top:22px;">
                            <span class="ifs-submeta-hint"><span class="dashicons dashicons-info"></span> Deposit reflects immediately on Air, Visa & Hotel issuing desks</span>
                            <button type="submit" name="ifs_supplier_payment_submit" class="ifs-btn-primary">
                                <span class="dashicons dashicons-saved"></span> Post Balance Deposit
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Sidebar: Interactive Deposit Calculator Card -->
            <div class="ifs-preview-sidebar">
                <div class="ifs-preview-sticky">
                    <div class="ifs-card-preview-header">
                        <span class="dashicons dashicons-calculator"></span> Real-time Balance Projection
                    </div>

                    <div class="ifs-portal-card">
                        <div class="portal-head-strip">
                            <span class="portal-category-tag" id="topup_prev_type">SUPPLIER PORTAL</span>
                            <span class="portal-status-badge">TOP-UP</span>
                        </div>

                        <div class="portal-hero">
                            <h3 class="portal-name" id="topup_prev_name">SELECT PORTAL</h3>
                        </div>

                        <div class="portal-balance-breakdown">
                            <div class="p-row">
                                <span>Current Available Balance:</span>
                                <strong id="topup_prev_curr" class="font-mono">৳0.00</strong>
                            </div>
                            <div class="p-row">
                                <span>Deposit Top-Up Inflow:</span>
                                <strong id="topup_prev_add" class="font-mono color-green">+৳0.00</strong>
                            </div>
                            <div class="p-row total-bal">
                                <span>Projected Balance After Deposit:</span>
                                <strong id="topup_prev_after" class="font-mono color-green font-bold">৳0.00</strong>
                            </div>
                        </div>

                        <div class="portal-footer-strip">
                            <span class="dashicons dashicons-shield"></span> Automatic Vendor Ledger Statement Synchronization
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selSupp  = document.getElementById('sel_topup_supplier');
            const inpAmt   = document.getElementById('inp_topup_amount');
            
            const prevName = document.getElementById('topup_prev_name');
            const prevType = document.getElementById('topup_prev_type');
            const prevCurr = document.getElementById('topup_prev_curr');
            const prevAdd  = document.getElementById('topup_prev_add');
            const prevAft  = document.getElementById('topup_prev_after');

            function updateTopupCalc() {
                let currBal = 0;
                if (selSupp && selSupp.selectedIndex > 0) {
                    const opt = selSupp.options[selSupp.selectedIndex];
                    if (prevName) prevName.textContent = opt.getAttribute('data-name').toUpperCase();
                    if (prevType) prevType.textContent = opt.getAttribute('data-type').toUpperCase();
                    currBal = parseFloat(opt.getAttribute('data-bal')) || 0;
                } else {
                    if (prevName) prevName.textContent = 'SELECT PORTAL';
                    if (prevType) prevType.textContent = 'SUPPLIER PORTAL';
                }

                const addAmt = parseFloat(inpAmt ? inpAmt.value : 0) || 0;
                const newBal = currBal + addAmt;

                if (prevCurr) prevCurr.textContent = '৳' + currBal.toLocaleString('en-US', { minimumFractionDigits: 2 });
                if (prevAdd)  prevAdd.textContent  = '+৳' + addAmt.toLocaleString('en-US', { minimumFractionDigits: 2 });
                if (prevAft)  prevAft.textContent  = '৳' + newBal.toLocaleString('en-US', { minimumFractionDigits: 2 });
            }

            if (selSupp) selSupp.addEventListener('change', updateTopupCalc);
            if (inpAmt)  inpAmt.addEventListener('input', updateTopupCalc);
            updateTopupCalc();
        });
        </script>
        <?php
    }

    /* =========================================================================
       SUB-TAB 3: TRANSACTION LEDGER
       ========================================================================= */
    elseif ( $sub_action === 'ledger' ) {
        $ledger = $wpdb->get_results( "
            SELECT l.*, s.supplier_name, s.supplier_type 
            FROM $table_ledger l 
            LEFT JOIN $table_suppliers s ON l.supplier_id = s.id 
            ORDER BY l.id DESC LIMIT 200
        " );
        ?>
        <div class="ifs-table-card">
            <div class="ifs-table-top-bar">
                <div class="ifs-table-title-group">
                    <h3 class="ifs-table-heading"><span class="dashicons dashicons-book"></span> Supplier & Portal Transaction Ledger</h3>
                    <p class="ifs-table-caption">Audit trail of bank deposits, ticket issuances, cancellations, and real-time portal balance history</p>
                </div>
            </div>

            <div class="ifs-table-responsive-wrapper">
                <table class="ifs-pro-datatable" id="ifsSuppLedgerTable">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Trx ID</th>
                            <th>Date & Time</th>
                            <th>Supplier / Portal</th>
                            <th>Transaction Type</th>
                            <th style="text-align: right;">Debit / Outflow (৳)</th>
                            <th style="text-align: right;">Credit / Inflow (৳)</th>
                            <th style="text-align: right;">Balance After (৳)</th>
                            <th>Note / Payment Ref</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $ledger ) : foreach ( $ledger as $row ) : ?>
                            <tr>
                                <td><span class="ifs-id-badge">#TXN-<?php echo str_pad( (string) $row->id, 5, '0', STR_PAD_LEFT ); ?></span></td>
                                <td><?php echo date( 'd M Y, h:i A', strtotime( $row->created_at ) ); ?></td>
                                <td>
                                    <strong style="color:#003376;"><?php echo esc_html( $row->supplier_name ?: 'Direct Supplier' ); ?></strong>
                                    <div style="font-size:11px; color:#64748b;"><?php echo esc_html( $row->supplier_type ?: 'B2B Vendor' ); ?></div>
                                </td>
                                <td><span class="ifs-trx-badge"><?php echo esc_html( $row->reference_type ); ?></span></td>
                                <td style="text-align: right; font-family: ui-monospace, monospace; font-weight: 700; color: #dc2626;">
                                    <?php echo ( $row->debit > 0 ) ? '-৳' . number_format( $row->debit, 2 ) : '-'; ?>
                                </td>
                                <td style="text-align: right; font-family: ui-monospace, monospace; font-weight: 700; color: #059669;">
                                    <?php echo ( $row->credit > 0 ) ? '+৳' . number_format( $row->credit, 2 ) : '-'; ?>
                                </td>
                                <td style="text-align: right; font-family: ui-monospace, monospace; font-weight: 800; color: #0f172a; font-size: 13.5px;">
                                    ৳<?php echo number_format( $row->balance_after, 2 ); ?>
                                </td>
                                <td style="font-size: 12px; color: #475569; max-width: 250px;"><?php echo esc_html( $row->note ?: '-' ); ?></td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr>
                                <td colspan="8" class="ifs-empty-table">
                                    <div class="ifs-empty-state">
                                        <span class="dashicons dashicons-book"></span>
                                        <h4>No Transaction Records Found</h4>
                                        <p>Post deposits or issue bookings to generate transaction ledger entries.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                if ($.fn.DataTable) {
                    $('#ifsSuppLedgerTable').DataTable({
                        "pageLength": 15,
                        "order": [[ 0, "desc" ]],
                        "language": {
                            "search": "_INPUT_",
                            "searchPlaceholder": "Search Supplier, Reference, Note...",
                            "lengthMenu": "Show _MENU_ entries"
                        }
                    });
                }
            });
        </script>
        <?php
    }

    /* =========================================================================
       SUB-TAB 4: SUPPLIER DIRECTORY LIST (DEFAULT)
       ========================================================================= */
    else {
        $suppliers = $wpdb->get_results( "SELECT * FROM $table_suppliers ORDER BY id DESC" );
        ?>
        <div class="ifs-table-card">
            <div class="ifs-table-top-bar">
                <div class="ifs-table-title-group">
                    <h3 class="ifs-table-heading"><span class="dashicons dashicons-list-view"></span> Supplier & Consortia Directory</h3>
                    <p class="ifs-table-caption">Active GDS providers, B2B air portals, hotel wholesalers, and live deposit balances</p>
                </div>
                <div class="ifs-table-btn-group">
                    <a href="<?php echo esc_url( $base_url . '&sub=topup' ); ?>" class="ifs-btn-secondary" style="margin-right:8px;">
                        <span class="dashicons dashicons-money-alt"></span> Post Top-Up
                    </a>
                    <a href="<?php echo esc_url( $base_url . '&sub=add' ); ?>" class="ifs-btn-primary">
                        <span class="dashicons dashicons-plus-alt2"></span> Register Supplier
                    </a>
                </div>
            </div>

            <div class="ifs-table-responsive-wrapper">
                <table class="ifs-pro-datatable" id="ifsSuppliersTable">
                    <thead>
                        <tr>
                            <th style="width: 80px;">SUP ID</th>
                            <th>Supplier / Portal Name</th>
                            <th>Category</th>
                            <th>Contact Person</th>
                            <th>Contact Phone & Email</th>
                            <th style="text-align: right;">Portal Balance (৳)</th>
                            <th style="text-align: center;">Status</th>
                            <th style="text-align: right; width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $suppliers ) : foreach ( $suppliers as $row ) : 
                            $status_class = ( strtolower( $row->status ) === 'active' ) ? 'status-confirmed' : 'status-cancelled';
                        ?>
                            <tr>
                                <td><span class="ifs-id-badge">#SUP-<?php echo str_pad( (string) $row->id, 5, '0', STR_PAD_LEFT ); ?></span></td>
                                <td>
                                    <div class="package-name"><strong><?php echo esc_html( $row->supplier_name ); ?></strong></div>
                                </td>
                                <td><span class="ifs-tier-pill tier-gds"><?php echo esc_html( $row->supplier_type ); ?></span></td>
                                <td><?php echo esc_html( $row->contact_person ?: '-' ); ?></td>
                                <td>
                                    <div><span class="dashicons dashicons-phone" style="font-size:12px; vertical-align:middle; color:#64748b;"></span> <?php echo esc_html( $row->phone ?: '-' ); ?></div>
                                    <?php if ( ! empty( $row->email ) ) : ?>
                                        <div style="font-size:11px; color:#64748b;"><span class="dashicons dashicons-email" style="font-size:12px; vertical-align:middle;"></span> <?php echo esc_html( $row->email ); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right; font-weight: 800; color: #059669; font-family: ui-monospace, monospace; font-size: 14.5px;">
                                    ৳<?php echo number_format( $row->current_balance, 2 ); ?>
                                </td>
                                <td style="text-align: center;">
                                    <span class="ifs-status-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $row->status ); ?></span>
                                </td>
                                <td style="text-align: right;">
                                    <div class="ifs-action-buttons">
                                        <a href="<?php echo esc_url( $base_url . '&sub=edit&id=' . $row->id ); ?>" class="ifs-btn-action edit" title="Edit Supplier">
                                            <span class="dashicons dashicons-edit"></span>
                                        </a>
                                        <a href="<?php echo wp_nonce_url( $base_url . '&sub=delete&id=' . $row->id, 'delete_supplier_' . $row->id ); ?>" 
                                           class="ifs-btn-action delete" 
                                           onclick="return confirm('Are you sure you want to permanently delete this supplier?');" 
                                           title="Delete Supplier">
                                            <span class="dashicons dashicons-trash"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr>
                                <td colspan="8" class="ifs-empty-table">
                                    <div class="ifs-empty-state">
                                        <span class="dashicons dashicons-networking"></span>
                                        <h4>No Suppliers Registered Yet</h4>
                                        <p>Start registering B2B ticketing portals and GDS consortia.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                if ($.fn.DataTable) {
                    $('#ifsSuppliersTable').DataTable({
                        "pageLength": 15,
                        "order": [[ 0, "desc" ]],
                        "language": {
                            "search": "_INPUT_",
                            "searchPlaceholder": "Search Supplier, Category, Phone...",
                            "lengthMenu": "Show _MENU_ entries"
                        }
                    });
                }
            });
        </script>
        <?php
    }

    echo '</div>';
    ?>

    <!-- Ultra High-End Stylesheet for Suppliers Module -->
    <style>
        .ifs-suppliers-workspace { max-width: 1440px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; }
        
        .ifs-toast { padding: 14px 20px; border-radius: 10px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 10px; margin-bottom: 22px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .ifs-toast.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .ifs-toast.danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .ifs-toast .dashicons { font-size: 18px; width: 18px; height: 18px; }

        /* Form Split Grid */
        .ifs-split-supp-editor { display: grid; grid-template-columns: 1fr 390px; gap: 28px; align-items: flex-start; }
        @media (max-width: 1140px) { .ifs-split-supp-editor { grid-template-columns: 1fr; } }

        .ifs-panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 26px; margin-bottom: 22px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); }
        .ifs-card-header { display: flex; align-items: center; gap: 14px; margin-bottom: 22px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9; }
        .ifs-step-num { width: 34px; height: 34px; border-radius: 9px; background: linear-gradient(135deg, #003376 0%, #0284c7 100%); color: #ffffff; font-weight: 800; font-size: 13px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0, 51, 118, 0.25); flex-shrink: 0; }
        .ifs-card-title { margin: 0; font-size: 15.5px; font-weight: 800; color: #0f172a; }
        .ifs-card-desc { margin: 2px 0 0 0; font-size: 12.5px; color: #64748b; }

        .ifs-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px 18px; }
        .ifs-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px 18px; }
        .col-span-2 { grid-column: span 2; }
        .col-span-3 { grid-column: span 3; }
        @media (max-width: 768px) { .ifs-grid-3, .ifs-grid-2 { grid-template-columns: 1fr; } .col-span-2, .col-span-3 { grid-column: span 1; } }

        .ifs-field-block { display: flex; flex-direction: column; gap: 5px; }
        .ifs-field-label { font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.4px; }
        .ifs-field-label .req { color: #e11d48; }

        .ifs-field-wrap { position: relative; display: flex; align-items: center; width: 100%; }
        .ifs-field-wrap .field-icon { position: absolute; left: 12px; color: #94a3b8; font-size: 17px; width: 17px; height: 17px; pointer-events: none; z-index: 2; }
        .ifs-field-wrap .ifs-input-field { width: 100%; padding: 9px 12px 9px 38px !important; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; color: #0f172a; background: #ffffff; outline: none; transition: all 0.2s ease; position: relative; z-index: 1; }
        .ifs-field-wrap select.ifs-input-field {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat; background-position: right 10px center; background-size: 14px; padding-right: 32px !important;
        }
        .ifs-field-wrap .ifs-input-field:focus { border-color: #003376; box-shadow: 0 0 0 3px rgba(0, 51, 118, 0.12); }
        .uppercase { text-transform: uppercase; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .font-bold { font-weight: 700; }
        .color-emerald { color: #059669 !important; }

        .ifs-action-strip { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; }
        .ifs-btn-back { color: #64748b; text-decoration: none; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .ifs-submeta-hint { font-size: 12px; color: #64748b; display: inline-flex; align-items: center; gap: 4px; }
        .ifs-submeta-hint .dashicons { font-size: 14px; width: 14px; height: 14px; color: #0284c7; }
        .ifs-btn-primary { background: linear-gradient(135deg, #003376 0%, #0284c7 100%); color: #ffffff !important; border: none; padding: 11px 26px; border-radius: 8px; font-size: 13.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(0, 51, 118, 0.25); }
        .ifs-btn-secondary { background: #f8fafc; border: 1px solid #cbd5e1; color: #0f172a !important; padding: 9px 18px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }

        /* Preview Sidebar */
        .ifs-preview-sticky { position: sticky; top: 30px; }
        .ifs-card-preview-header { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }

        .ifs-portal-card { background: linear-gradient(145deg, #001e47 0%, #003376 60%, #0284c7 100%); border-radius: 16px; padding: 22px; color: #ffffff; box-shadow: 0 16px 36px -6px rgba(0, 51, 118, 0.35); border: 1px solid rgba(255, 255, 255, 0.15); margin-bottom: 18px; }
        .portal-head-strip { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px dashed rgba(255, 255, 255, 0.2); }
        .portal-category-tag { font-size: 10px; font-weight: 800; letter-spacing: 0.8px; color: #bae6fd; text-transform: uppercase; }
        .portal-status-badge { background: rgba(255, 255, 255, 0.18); backdrop-filter: blur(4px); padding: 2px 8px; border-radius: 4px; font-size: 9.5px; font-weight: 800; text-transform: uppercase; }
        .portal-hero { margin-bottom: 14px; }
        .portal-name { margin: 0 0 4px 0; font-size: 16px; font-weight: 900; color: #ffffff; text-transform: uppercase; }
        .portal-person-sub { font-size: 11px; color: #bae6fd; display: inline-flex; align-items: center; gap: 4px; }
        .portal-person-sub .dashicons { font-size: 13px; width: 13px; height: 13px; color: #38bdf8; }

        .portal-balance-hero { background: rgba(0,0,0,0.22); border-radius: 10px; padding: 14px; margin-bottom: 14px; text-align: center; }
        .balance-lbl { font-size: 8.5px; font-weight: 700; color: #bae6fd; letter-spacing: 0.5px; display: block; margin-bottom: 2px; }
        .balance-val { margin: 0; font-size: 24px; font-weight: 900; color: #86efac; }
        .balance-sub { font-size: 10px; color: #cbd5e1; margin-top: 2px; display: block; }

        .portal-contact-strip { display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.18); padding: 10px 14px; border-radius: 8px; margin-bottom: 14px; font-size: 11px; }
        .portal-contact-strip .c-lbl { font-size: 8px; font-weight: 700; color: #93c5fd; display: block; margin-bottom: 2px; }
        .portal-contact-strip .c-val { color: #ffffff; }

        .portal-balance-breakdown { display: flex; flex-direction: column; gap: 8px; background: rgba(0,0,0,0.2); border-radius: 10px; padding: 12px 14px; margin-bottom: 12px; font-size: 12px; }
        .portal-balance-breakdown .p-row { display: flex; justify-content: space-between; color: #bae6fd; }
        .portal-balance-breakdown .total-bal { border-top: 1px dashed rgba(255,255,255,0.2); padding-top: 6px; font-size: 13px; }
        .portal-footer-strip { font-size: 8.5px; color: #bae6fd; display: flex; align-items: center; gap: 4px; padding-top: 6px; border-top: 1px solid rgba(255, 255, 255, 0.1); }

        /* Table Card */
        .ifs-table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); overflow: hidden; }
        .ifs-table-top-bar { padding: 22px 26px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; gap: 15px; }
        .ifs-table-heading { margin: 0; font-size: 17px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        .ifs-table-heading .dashicons { color: #003376; font-size: 20px; width: 20px; height: 20px; }
        .ifs-table-caption { margin: 3px 0 0 0; font-size: 13px; color: #64748b; }

        .ifs-table-responsive-wrapper { padding: 15px 24px 24px 24px; overflow-x: auto; }
        .ifs-pro-datatable { width: 100%; border-collapse: collapse; font-size: 13px; }
        .ifs-pro-datatable thead th { background: #f8fafc; color: #475569; font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 12px 14px; border-bottom: 2px solid #e2e8f0; text-align: left; }
        .ifs-pro-datatable tbody td { padding: 13px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #334155; }
        
        .ifs-id-badge { background: #f1f5f9; color: #475569; font-family: ui-monospace, monospace; font-weight: 700; font-size: 11px; padding: 3px 6px; border-radius: 6px; }
        .package-name { font-size: 13.5px; color: #0f172a; }
        .ifs-tier-pill { font-size: 10.5px; font-weight: 700; padding: 2px 7px; border-radius: 4px; display: inline-block; }
        .tier-gds { background: #e0f2fe; color: #0369a1; }
        .ifs-trx-badge { background: #f1f5f9; border: 1px solid #e2e8f0; padding: 2px 7px; border-radius: 4px; font-size: 11px; font-weight: 600; color: #475569; }

        .ifs-status-badge { display: inline-flex; align-items: center; padding: 3px 8px; border-radius: 6px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; }
        .status-confirmed { background: #dcfce7; color: #15803d; }
        .status-cancelled { background: #fee2e2; color: #b91c1c; }

        .ifs-action-buttons { display: flex; gap: 5px; justify-content: flex-end; }
        .ifs-btn-action { width: 30px; height: 30px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.15s ease; }
        .ifs-btn-action.edit   { background: #eff6ff; color: #2563eb; }
        .ifs-btn-action.edit:hover { background: #dbeafe; color: #1d4ed8; }
        .ifs-btn-action.delete { background: #fef2f2; color: #dc2626; }
        .ifs-btn-action.delete:hover { background: #fee2e2; color: #b91c1c; }
        .ifs-btn-action .dashicons { font-size: 14px; width: 14px; height: 14px; }

        .ifs-empty-table { text-align: center; padding: 50px 20px !important; }
        .ifs-empty-state .dashicons { font-size: 44px; width: 44px; height: 44px; color: #cbd5e1; margin-bottom: 10px; }
    </style>
    <?php
}