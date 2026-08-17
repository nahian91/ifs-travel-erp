<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enterprise Next-Gen Visa Application Management Console
 * Features: Multi-Country Presets, Document Checklist Vault, Live Visa Card Preview, B2B Agent Ledger Link, Embassy Tracking & Yield Calculator
 */
function ifs_terp_visa_add_edit_page() {
    global $wpdb;
    $table_visas     = $wpdb->prefix . 'iterp_visa_applications';
    $table_customers = $wpdb->prefix . 'iterp_customers';
    $table_agents    = $wpdb->prefix . 'iterp_agents';
    $table_suppliers = $wpdb->prefix . 'iterp_suppliers';

    $id       = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
    $is_edit  = ( $id > 0 );
    $message  = '';
    $errors   = array();
    $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=visa' );

    // Handle Form Processing
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_visa_submit'] ) ) {
        check_admin_referer( 'ifs_visa_save_action', 'ifs_visa_nonce' );

        $customer_id         = intval( $_POST['customer_id'] ?? 0 );
        $agent_id            = intval( $_POST['agent_id'] ?? 0 );
        $supplier_id         = intval( $_POST['supplier_id'] ?? 0 );
        $country             = sanitize_text_field( $_POST['country'] ?? '' );
        $visa_type           = sanitize_text_field( $_POST['visa_type'] ?? 'Tourist Visa' );
        $entry_type          = sanitize_text_field( $_POST['entry_type'] ?? 'Single Entry' );
        $tracking_no         = strtoupper( sanitize_text_field( $_POST['tracking_no'] ?? '' ) );
        $submission_date     = sanitize_text_field( $_POST['submission_date'] ?? '' );
        $expected_delivery   = sanitize_text_field( $_POST['expected_delivery'] ?? '' );
        $validity_days       = intval( $_POST['validity_days'] ?? 30 );
        $buy_price           = floatval( $_POST['buy_price'] ?? 0 );
        $sell_price          = floatval( $_POST['sell_price'] ?? 0 );
        $profit              = $sell_price - $buy_price;
        $status              = sanitize_text_field( $_POST['status'] ?? 'Processing' );
        $documents_collected = sanitize_textarea_field( $_POST['documents_collected'] ?? '' );
        $remarks             = sanitize_textarea_field( $_POST['remarks'] ?? '' );

        if ( empty( $customer_id ) ) {
            $errors[] = 'Please select a registered applicant/traveler.';
        }
        if ( empty( $country ) ) {
            $errors[] = 'Destination country is required.';
        }
        if ( empty( $submission_date ) ) {
            $errors[] = 'Embassy/Consulate submission date is required.';
        }

        if ( empty( $errors ) ) {
            $data = array(
                'customer_id'         => $customer_id,
                'agent_id'            => $agent_id,
                'supplier_id'         => $supplier_id,
                'country'             => $country,
                'visa_type'           => $visa_type,
                'entry_type'          => $entry_type,
                'tracking_no'         => $tracking_no,
                'submission_date'     => $submission_date,
                'expected_delivery'   => ! empty( $expected_delivery ) ? $expected_delivery : '1970-01-01',
                'validity_days'       => $validity_days,
                'buy_price'           => $buy_price,
                'sell_price'          => $sell_price,
                'profit'              => $profit,
                'status'              => $status,
                'documents_collected' => $documents_collected,
                'remarks'             => $remarks,
                'created_by'          => get_current_user_id()
            );

            if ( $is_edit ) {
                $wpdb->update( $table_visas, $data, array( 'id' => $id ) );
                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Visa application updated successfully.</div>';
            } else {
                $data['created_at'] = current_time( 'mysql' );
                $wpdb->insert( $table_visas, $data );
                $id      = $wpdb->insert_id;
                $is_edit = true;
                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> New Visa application opened (#VSA-' . str_pad( (string) $id, 5, '0', STR_PAD_LEFT ) . ').</div>';
            }

            if ( function_exists( 'ifs_terp_log_activity' ) ) {
                ifs_terp_log_activity( "Processed Visa File #VSA-$id for $country (Client ID: #CUS-$customer_id)" );
            }
        } else {
            $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> ' . implode( '<br>', $errors ) . '</div>';
        }
    }

    $row = false;
    if ( $is_edit ) {
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_visas WHERE id = %d", $id ) );
    }

    $customers = $wpdb->get_results( "SELECT id, title, full_name, mobile, passport_no, nationality FROM $table_customers ORDER BY full_name ASC" );
    $agents    = $wpdb->get_results( "SELECT id, agency_name, current_balance FROM $table_agents WHERE status = 'Active' ORDER BY agency_name ASC" );
    $suppliers = $wpdb->get_results( "SELECT id, supplier_name, current_balance FROM $table_suppliers WHERE status = 'Active' ORDER BY supplier_name ASC" );

    // Field Defaults
    $val_customer    = $is_edit ? intval( $row->customer_id ) : 0;
    $val_agent       = $is_edit ? intval( $row->agent_id ?? 0 ) : 0;
    $val_supplier    = $is_edit ? intval( $row->supplier_id ?? 0 ) : 0;
    $val_country     = $is_edit ? esc_attr( $row->country ) : 'Saudi Arabia';
    $val_type        = $is_edit ? esc_attr( $row->visa_type ) : 'Tourist Visa';
    $val_entry       = $is_edit ? esc_attr( $row->entry_type ?? 'Single Entry' ) : 'Single Entry';
    $val_tracking    = $is_edit ? esc_attr( $row->tracking_no ?? '' ) : '';
    $val_submission  = $is_edit ? esc_attr( $row->submission_date ) : date( 'Y-m-d' );
    $val_expected    = ( $is_edit && ! empty( $row->expected_delivery ) && $row->expected_delivery !== '1970-01-01' ) ? esc_attr( $row->expected_delivery ) : date( 'Y-m-d', strtotime( '+7 days' ) );
    $val_validity    = $is_edit ? intval( $row->validity_days ?? 30 ) : 30;
    $val_buy         = $is_edit ? floatval( $row->buy_price ) : '';
    $val_sell        = $is_edit ? floatval( $row->sell_price ) : '';
    $val_profit      = $is_edit ? floatval( $row->profit ) : 0;
    $val_status      = $is_edit ? esc_attr( $row->status ) : 'Processing';
    $val_docs        = $is_edit ? esc_textarea( $row->documents_collected ) : "Original Passport\n2x Passport Size Photos (White BG)\nBank Statement & Solvency\nTrade License / Visiting Card\nNID Photocopy";
    $val_remarks     = $is_edit ? esc_textarea( $row->remarks ?? '' ) : '';
    ?>

    <div class="ifs-visa-workspace">
        <?php echo $message; ?>

        <form method="post" action="" id="ifsVisaForm" class="ifs-split-visa-editor">
            <?php wp_nonce_field( 'ifs_visa_save_action', 'ifs_visa_nonce' ); ?>

            <div class="ifs-visa-form-body">
                
                <!-- Section 1: Applicant, Country & Visa Classification -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">01</div>
                        <div>
                            <h3 class="ifs-card-title">Applicant, Destination & Category</h3>
                            <p class="ifs-card-desc">Assign traveler portfolio, destination country, and entry permission type</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <!-- Applicant Selection -->
                        <div class="ifs-field-block col-span-2">
                            <label class="ifs-field-label" for="inp_customer">Applicant / Traveler Portfolio <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-admin-users field-icon"></span>
                                <select name="customer_id" id="inp_customer" required class="ifs-input-field">
                                    <option value="">-- Choose Registered Applicant --</option>
                                    <?php foreach ( $customers as $cus ) : 
                                        $t_prefix = ! empty( $cus->title ) ? $cus->title . '. ' : '';
                                        $p_num    = ! empty( $cus->passport_no ) ? ' [PPT: ' . $cus->passport_no . ']' : '';
                                    ?>
                                        <option value="<?php echo $cus->id; ?>" 
                                                data-name="<?php echo esc_attr( $t_prefix . $cus->full_name ); ?>"
                                                data-passport="<?php echo esc_attr( $cus->passport_no ?: 'NOT PROVIDED' ); ?>"
                                                data-nation="<?php echo esc_attr( $cus->nationality ?: 'Bangladeshi' ); ?>"
                                                <?php selected( $val_customer, $cus->id ); ?>>
                                            <?php echo esc_html( $t_prefix . $cus->full_name . ' (' . $cus->mobile . ')' . $p_num ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- B2B Sub-Agent -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_agent">B2B Sub-Agent (If Any)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-groups field-icon"></span>
                                <select name="agent_id" id="inp_agent" class="ifs-input-field">
                                    <option value="0">Direct Retail Customer</option>
                                    <?php foreach ( $agents as $ag ) : ?>
                                        <option value="<?php echo $ag->id; ?>" <?php selected( $val_agent, $ag->id ); ?>>
                                            <?php echo esc_html( $ag->agency_name . ' (Bal: ৳' . number_format( $ag->current_balance, 0 ) . ')' ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Destination Country -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_country">Destination Country <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-admin-site-alt3 field-icon"></span>
                                <input type="text" name="country" id="inp_country" required 
                                       value="<?php echo $val_country; ?>" 
                                       placeholder="e.g. Saudi Arabia / UAE / UK / Canada" class="ifs-input-field">
                            </div>
                        </div>

                        <!-- Visa Category -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_visa_type">Visa Category <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-tag field-icon"></span>
                                <select name="visa_type" id="inp_visa_type" class="ifs-input-field">
                                    <option value="Tourist Visa" <?php selected( $val_type, 'Tourist Visa' ); ?>>Tourist / Visit Visa</option>
                                    <option value="Business Visa" <?php selected( $val_type, 'Business Visa' ); ?>>Business Visa</option>
                                    <option value="Work / Employment" <?php selected( $val_type, 'Work / Employment' ); ?>>Work / Employment Visa</option>
                                    <option value="Umrah / E-Visa" <?php selected( $val_type, 'Umrah / E-Visa' ); ?>>Umrah / Saudi E-Visa</option>
                                    <option value="Student Visa" <?php selected( $val_type, 'Student Visa' ); ?>>Student Visa</option>
                                    <option value="Medical Visa" <?php selected( $val_type, 'Medical Visa' ); ?>>Medical Treatment Visa</option>
                                </select>
                            </div>
                        </div>

                        <!-- Entry Permission Type -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_entry_type">Entry Permission</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-location-alt field-icon"></span>
                                <select name="entry_type" id="inp_entry_type" class="ifs-input-field">
                                    <option value="Single Entry" <?php selected( $val_entry, 'Single Entry' ); ?>>Single Entry</option>
                                    <option value="Multiple Entry" <?php selected( $val_entry, 'Multiple Entry' ); ?>>Multiple Entry</option>
                                    <option value="Double Entry" <?php selected( $val_entry, 'Double Entry' ); ?>>Double Entry</option>
                                    <option value="Transit Visa" <?php selected( $val_entry, 'Transit Visa' ); ?>>Transit Permission</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Embassy Schedule, Tracking & Document Vault -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">02</div>
                        <div>
                            <h3 class="ifs-card-title">Timeline, Embassy Tracking & Checklist</h3>
                            <p class="ifs-card-desc">Embassy submission schedules, online tracking reference, and collected original documents</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <!-- Submission Date -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_submission_date">Submission Date <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-calendar-alt field-icon"></span>
                                <input type="date" name="submission_date" id="inp_submission_date" required 
                                       value="<?php echo $val_submission; ?>" class="ifs-input-field">
                            </div>
                        </div>

                        <!-- Expected Delivery Date -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_expected_delivery">Expected Delivery Date</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-clock field-icon"></span>
                                <input type="date" name="expected_delivery" id="inp_expected_delivery" 
                                       value="<?php echo $val_expected; ?>" class="ifs-input-field">
                            </div>
                        </div>

                        <!-- Embassy Tracking Reference -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_tracking_no">Embassy Application / Tracking No</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-search field-icon"></span>
                                <input type="text" name="tracking_no" id="inp_tracking_no" 
                                       value="<?php echo $val_tracking; ?>" 
                                       placeholder="e.g. VFS-998821 / GWF065432" class="ifs-input-field uppercase font-mono">
                            </div>
                        </div>

                        <!-- Processing Vendor / Embassy Supplier -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_supplier">Processing Vendor / Wholesaler</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-networking field-icon"></span>
                                <select name="supplier_id" id="inp_supplier" class="ifs-input-field">
                                    <option value="0">Direct Embassy Submission</option>
                                    <?php foreach ( $suppliers as $sup ) : ?>
                                        <option value="<?php echo $sup->id; ?>" <?php selected( $val_supplier, $sup->id ); ?>>
                                            <?php echo esc_html( $sup->supplier_name . ' (Bal: ৳' . number_format( $sup->current_balance, 0 ) . ')' ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Visa Validity Duration -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_validity">Stay / Validity (Days)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-backup field-icon"></span>
                                <input type="number" name="validity_days" id="inp_validity" 
                                       value="<?php echo $val_validity; ?>" placeholder="30" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <!-- Current Status -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_status">Current File Status</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-yes-alt field-icon"></span>
                                <select name="status" id="inp_status" class="ifs-input-field">
                                    <option value="Processing" <?php selected( $val_status, 'Processing' ); ?>>Processing (Under Review)</option>
                                    <option value="Approved" <?php selected( $val_status, 'Approved' ); ?>>Approved / Stamped</option>
                                    <option value="Delivered" <?php selected( $val_status, 'Delivered' ); ?>>Delivered to Client</option>
                                    <option value="Rejected" <?php selected( $val_status, 'Rejected' ); ?>>Rejected by Embassy</option>
                                </select>
                            </div>
                        </div>

                        <!-- Collected Documents Checklist -->
                        <div class="ifs-field-block col-span-3">
                            <label class="ifs-field-label" for="inp_docs">Received Physical Documents Checklist</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-clipboard field-icon textarea-icon"></span>
                                <textarea name="documents_collected" id="inp_docs" rows="3" class="ifs-input-field has-textarea-icon" 
                                          placeholder="Original Passport, Photos, Trade License, Bank Statement..."><?php echo $val_docs; ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Commercial Fees & Agency Profit Settlement -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">03</div>
                        <div>
                            <h3 class="ifs-card-title">Embassy Fees, Service Charges & Net Margin</h3>
                            <p class="ifs-card-desc">Supplier / Embassy processing cost, customer billing charge, and real-time agency yield</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <!-- Embassy Buy Rate -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="vsa_buy">Embassy / Supplier Cost (৳) <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-cart field-icon"></span>
                                <input type="number" step="0.01" name="buy_price" id="vsa_buy" required 
                                       value="<?php echo $val_buy; ?>" placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <!-- Client Billing Price -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="vsa_sell">Client Total Invoiced Fee (৳) <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-money-alt field-icon"></span>
                                <input type="number" step="0.01" name="sell_price" id="vsa_sell" required 
                                       value="<?php echo $val_sell; ?>" placeholder="0.00" class="ifs-input-field font-mono font-bold color-blue">
                            </div>
                        </div>

                        <!-- Net Profit -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label">Gross Margin / Profit (৳)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-chart-line field-icon"></span>
                                <input type="text" id="vsa_profit" readonly 
                                       value="<?php echo number_format( $val_profit, 2 ); ?>" 
                                       class="ifs-input-field font-mono font-bold <?php echo ( $val_profit >= 0 ) ? 'profit-positive' : 'profit-negative'; ?>">
                            </div>
                        </div>

                        <!-- Remarks & Case Notes -->
                        <div class="ifs-field-block col-span-3">
                            <label class="ifs-field-label" for="inp_remarks">Special Remarks / Appointment Schedule / Notes</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-edit field-icon"></span>
                                <input type="text" name="remarks" id="inp_remarks" 
                                       value="<?php echo $val_remarks; ?>" 
                                       placeholder="e.g. Biometric Appointment on 25th Aug at 10:30 AM (VFS Gulshan)" class="ifs-input-field">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Toolbar -->
                <div class="ifs-action-strip">
                    <a href="<?php echo esc_url( $base_url . '&sub=list' ); ?>" class="ifs-btn-back">
                        <span class="dashicons dashicons-arrow-left-alt"></span> Cancel
                    </a>
                    <button type="submit" name="ifs_visa_submit" class="ifs-btn-primary">
                        <span class="dashicons dashicons-saved"></span> 
                        <?php echo $is_edit ? 'Update Visa Application' : 'Open Visa Processing File'; ?>
                    </button>
                </div>
            </div>

            <!-- Right Sidebar: Live Visa Dossier Pass Card Preview -->
            <div class="ifs-preview-sidebar">
                <div class="ifs-preview-sticky">
                    <div class="ifs-card-preview-header">
                        <span class="dashicons dashicons-id"></span> Live Visa File Dossier Preview
                    </div>

                    <!-- Modern Visa Dossier Card -->
                    <div class="ifs-visa-card">
                        <div class="visa-head-strip">
                            <span class="visa-country-tag" id="prev_country">SAUDI ARABIA</span>
                            <span class="visa-type-badge" id="prev_type">TOURIST VISA</span>
                        </div>

                        <div class="visa-applicant-hero">
                            <div class="visa-avatar" id="prev_avatar">MR</div>
                            <div>
                                <h4 class="visa-name" id="prev_name">MOHAMMED RAHIM</h4>
                                <div class="visa-submeta" id="prev_meta">PPT: NOT SET &bull; BANGLADESHI</div>
                            </div>
                        </div>

                        <div class="visa-grid-specs font-mono">
                            <div>
                                <span class="visa-lbl">TRACKING / REF</span>
                                <strong class="visa-val color-cyan" id="prev_tracking">PENDING</strong>
                            </div>
                            <div>
                                <span class="visa-lbl">ENTRY PERMIT</span>
                                <strong class="visa-val" id="prev_entry">SINGLE</strong>
                            </div>
                            <div>
                                <span class="visa-lbl">SUBMITTED DATE</span>
                                <strong class="visa-val" id="prev_sub_date">20 AUG 2026</strong>
                            </div>
                            <div>
                                <span class="visa-lbl">DELIVERY (EST)</span>
                                <strong class="visa-val color-green" id="prev_exp_date">27 AUG 2026</strong>
                            </div>
                        </div>

                        <div class="visa-fee-footer">
                            <div class="fee-row">
                                <span>TOTAL VISA INVOICE:</span>
                                <strong class="color-green font-mono" id="prev_sell">৳0.00</strong>
                            </div>
                            <span class="visa-barcode-txt font-mono" id="prev_barcode">V&lt;BGD&lt;&lt;APPLICATION&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;</span>
                        </div>
                    </div>

                    <!-- Commercial Intelligence Box -->
                    <div class="ifs-intel-box">
                        <div class="intel-head"><span class="dashicons dashicons-analytics"></span> Real-Time Profit Yield</div>
                        <div class="intel-body">
                            <div class="intel-row">
                                <span>Gross Margin:</span>
                                <strong id="intel_profit" class="color-green">৳0.00</strong>
                            </div>
                            <div class="intel-row">
                                <span>Yield Ratio:</span>
                                <strong id="intel_ratio">0.0%</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Ultra High-End Stylesheet -->
    <style>
        .ifs-visa-workspace { max-width: 1420px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; }
        
        .ifs-toast { padding: 14px 20px; border-radius: 10px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 10px; margin-bottom: 22px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .ifs-toast.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .ifs-toast.danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .ifs-toast .dashicons { font-size: 18px; width: 18px; height: 18px; }

        .ifs-split-visa-editor { display: grid; grid-template-columns: 1fr 390px; gap: 28px; align-items: flex-start; }
        @media (max-width: 1140px) { .ifs-split-visa-editor { grid-template-columns: 1fr; } }

        .ifs-panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 26px; margin-bottom: 22px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); }
        .ifs-card-header { display: flex; align-items: center; gap: 14px; margin-bottom: 22px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9; }
        .ifs-step-num { width: 34px; height: 34px; border-radius: 9px; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; font-weight: 800; font-size: 13px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(2, 132, 199, 0.25); flex-shrink: 0; }
        .ifs-card-title { margin: 0; font-size: 15.5px; font-weight: 800; color: #0f172a; }
        .ifs-card-desc { margin: 2px 0 0 0; font-size: 12.5px; color: #64748b; }

        .ifs-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px 18px; }
        .col-span-2 { grid-column: span 2; }
        .col-span-3 { grid-column: span 3; }
        @media (max-width: 768px) { .ifs-grid-3 { grid-template-columns: 1fr; } .col-span-2, .col-span-3 { grid-column: span 1; } }

        .ifs-field-block { display: flex; flex-direction: column; gap: 5px; }
        .ifs-field-label { font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.4px; }
        .ifs-field-label .req { color: #e11d48; }

        .ifs-field-wrap { position: relative; display: flex; align-items: center; width: 100%; }
        .ifs-field-wrap .field-icon {
            position: absolute;
            left: 12px;
            color: #94a3b8;
            font-size: 17px;
            width: 17px;
            height: 17px;
            pointer-events: none;
            z-index: 2;
            transition: color 0.2s ease;
        }
        .ifs-field-wrap .textarea-icon { top: 12px; }

        .ifs-field-wrap .ifs-input-field {
            width: 100%;
            padding: 9px 12px 9px 38px !important;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13.5px;
            color: #0f172a;
            background: #ffffff;
            outline: none;
            transition: all 0.2s ease;
            position: relative;
            z-index: 1;
        }
        .ifs-field-wrap select.ifs-input-field {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 14px;
            padding-right: 32px !important;
        }
        textarea.ifs-input-field.has-textarea-icon { padding: 10px 12px 10px 38px !important; font-family: inherit; }
        .ifs-field-wrap .ifs-input-field:focus { border-color: #0284c7; box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12); }
        .ifs-field-wrap:focus-within .field-icon { color: #0284c7; }

        .uppercase { text-transform: uppercase; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .font-bold { font-weight: 700; }
        .color-blue { color: #003376 !important; }

        .profit-positive { background: #f0fdf4 !important; color: #166534 !important; border-color: #bbf7d0 !important; }
        .profit-negative { background: #fef2f2 !important; color: #dc2626 !important; border-color: #fecaca !important; }

        /* Action Toolbar */
        .ifs-action-strip { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; }
        .ifs-btn-back { color: #64748b; text-decoration: none; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .ifs-btn-back:hover { color: #0f172a; }
        .ifs-btn-primary {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #ffffff !important;
            border: none;
            padding: 11px 26px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.25);
            transition: all 0.2s ease;
        }
        .ifs-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(2, 132, 199, 0.35); }

        /* Right Preview Sidebar */
        .ifs-preview-sticky { position: sticky; top: 30px; }
        .ifs-card-preview-header { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }

        .ifs-visa-card {
            background: linear-gradient(145deg, #0c4a6e 0%, #0369a1 60%, #0284c7 100%);
            border-radius: 16px;
            padding: 22px;
            color: #ffffff;
            box-shadow: 0 16px 36px -6px rgba(2, 132, 199, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.15);
            position: relative;
            overflow: hidden;
        }

        .visa-head-strip { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; padding-bottom: 12px; border-bottom: 1px dashed rgba(255, 255, 255, 0.2); }
        .visa-country-tag { font-size: 12px; font-weight: 800; letter-spacing: 0.8px; color: #bae6fd; text-transform: uppercase; }
        .visa-type-badge { background: rgba(255, 255, 255, 0.18); backdrop-filter: blur(4px); padding: 2px 8px; border-radius: 4px; font-size: 9.5px; font-weight: 800; }

        .visa-applicant-hero { display: flex; align-items: center; gap: 14px; margin-bottom: 18px; }
        .visa-avatar { width: 46px; height: 46px; border-radius: 12px; background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 15px; flex-shrink: 0; }
        .visa-name { margin: 0; font-size: 14px; font-weight: 800; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 210px; }
        .visa-submeta { font-size: 11px; color: #bae6fd; margin-top: 2px; }

        .visa-grid-specs { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding-bottom: 14px; margin-bottom: 14px; border-bottom: 1px dashed rgba(255, 255, 255, 0.2); }
        .visa-lbl { font-size: 8.5px; font-weight: 700; color: #93c5fd; letter-spacing: 0.5px; display: block; margin-bottom: 2px; }
        .visa-val { font-size: 12px; font-weight: 700; color: #ffffff; display: block; }
        .color-cyan { color: #38bdf8 !important; }
        .color-green { color: #86efac !important; }

        .visa-fee-footer { display: flex; flex-direction: column; gap: 6px; }
        .fee-row { display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #e0f2fe; }
        .fee-row strong { font-size: 15px; }
        .visa-barcode-txt { font-size: 8px; color: #93c5fd; letter-spacing: 1px; text-align: center; margin-top: 4px; }

        /* Intelligence Card */
        .ifs-intel-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-top: 18px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02); }
        .intel-head { font-size: 12px; font-weight: 800; color: #003376; text-transform: uppercase; letter-spacing: 0.4px; display: flex; align-items: center; gap: 6px; margin-bottom: 10px; }
        .intel-body { display: flex; flex-direction: column; gap: 6px; }
        .intel-row { display: flex; justify-content: space-between; font-size: 12.5px; color: #475569; }
        .intel-row strong { font-weight: 800; font-size: 13.5px; }
    </style>

    <!-- Real-Time Interactive Engine -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const inpCustomer   = document.getElementById('inp_customer');
        const inpCountry    = document.getElementById('inp_country');
        const inpVisaType   = document.getElementById('inp_visa_type');
        const inpEntryType  = document.getElementById('inp_entry_type');
        const inpTracking   = document.getElementById('inp_tracking_no');
        const inpSubDate    = document.getElementById('inp_submission_date');
        const inpExpDate    = document.getElementById('inp_expected_delivery');
        const inpBuy        = document.getElementById('vsa_buy');
        const inpSell       = document.getElementById('vsa_sell');

        const prevCountry   = document.getElementById('prev_country');
        const prevType      = document.getElementById('prev_type');
        const prevAvatar    = document.getElementById('prev_avatar');
        const prevName      = document.getElementById('prev_name');
        const prevMeta      = document.getElementById('prev_meta');
        const prevTracking  = document.getElementById('prev_tracking');
        const prevEntry     = document.getElementById('prev_entry');
        const prevSubDate   = document.getElementById('prev_sub_date');
        const prevExpDate   = document.getElementById('prev_exp_date');
        const prevSell      = document.getElementById('prev_sell');
        const profitDisplay = document.getElementById('vsa_profit');
        const intelProfit   = document.getElementById('intel_profit');
        const intelRatio    = document.getElementById('intel_ratio');

        function updateVisaCard() {
            if (prevCountry) prevCountry.textContent = (inpCountry && inpCountry.value.trim()) ? inpCountry.value.trim().toUpperCase() : 'DESTINATION';
            if (prevType) prevType.textContent = (inpVisaType) ? inpVisaType.value.toUpperCase() : 'TOURIST VISA';
            if (prevEntry) prevEntry.textContent = (inpEntryType) ? inpEntryType.value.toUpperCase() : 'SINGLE';
            if (prevTracking) prevTracking.textContent = (inpTracking && inpTracking.value.trim()) ? inpTracking.value.trim().toUpperCase() : 'PENDING';

            // Customer Name & Meta
            if (inpCustomer && inpCustomer.selectedIndex > 0) {
                const selectedOpt = inpCustomer.options[inpCustomer.selectedIndex];
                const paxName     = selectedOpt.getAttribute('data-name') || 'MOHAMMED RAHIM';
                const passportNo  = selectedOpt.getAttribute('data-passport') || 'NOT PROVIDED';
                const nationality = selectedOpt.getAttribute('data-nation') || 'BANGLADESHI';

                if (prevName) prevName.textContent = paxName.toUpperCase();
                if (prevMeta) prevMeta.textContent = 'PPT: ' + passportNo + ' • ' + nationality.toUpperCase();

                const parts = paxName.trim().split(' ');
                if (prevAvatar) prevAvatar.textContent = parts.length > 1 ? (parts[0][0] + parts[parts.length-1][0]).toUpperCase() : parts[0].slice(0, 2).toUpperCase();
            } else {
                if (prevName) prevName.textContent = 'SELECT APPLICANT';
                if (prevMeta) prevMeta.textContent = 'PPT: NOT SET • BANGLADESHI';
                if (prevAvatar) prevAvatar.textContent = 'VA';
            }

            // Dates Format
            if (inpSubDate && inpSubDate.value) {
                const d = new Date(inpSubDate.value);
                const options = { day: '2-digit', month: 'short', year: 'numeric' };
                if (prevSubDate) prevSubDate.textContent = d.toLocaleDateString('en-GB', options).toUpperCase();
            }
            if (inpExpDate && inpExpDate.value) {
                const d = new Date(inpExpDate.value);
                const options = { day: '2-digit', month: 'short', year: 'numeric' };
                if (prevExpDate) prevExpDate.textContent = d.toLocaleDateString('en-GB', options).toUpperCase();
            }

            // Financial Calculations
            const buyVal  = parseFloat(inpBuy ? inpBuy.value : 0) || 0;
            const sellVal = parseFloat(inpSell ? inpSell.value : 0) || 0;
            const profit  = sellVal - buyVal;
            const ratio   = sellVal > 0 ? ((profit / sellVal) * 100).toFixed(1) : '0.0';

            if (prevSell) prevSell.textContent = '৳' + sellVal.toLocaleString('en-US', { minimumFractionDigits: 2 });
            if (profitDisplay) {
                profitDisplay.value = profit.toLocaleString('en-US', { minimumFractionDigits: 2 });
                profitDisplay.className = 'ifs-input-field font-mono font-bold ' + (profit >= 0 ? 'profit-positive' : 'profit-negative');
            }

            if (intelProfit) {
                intelProfit.textContent = '৳' + profit.toLocaleString('en-US', { minimumFractionDigits: 2 });
                intelProfit.className = (profit >= 0) ? 'color-green' : 'color-rose';
            }
            if (intelRatio) intelRatio.textContent = ratio + '%';
        }

        const watchFields = [inpCustomer, inpCountry, inpVisaType, inpEntryType, inpTracking, inpSubDate, inpExpDate, inpBuy, inpSell];
        watchFields.forEach(el => {
            if (el) {
                el.addEventListener('input', updateVisaCard);
                el.addEventListener('change', updateVisaCard);
            }
        });

        updateVisaCard();
    });
    </script>
    <?php
}