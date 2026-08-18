<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enterprise Next-Gen Visa Application Management Console
 * Features: Dynamic Document Checklist Repeater, Live Document Vault Previews, Holographic Embassy Dossier & Financial Yield Engine
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

    if ( function_exists( 'wp_enqueue_media' ) ) {
        wp_enqueue_media();
    }

    // Handle Form Submission
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_visa_submit'] ) ) {
        check_admin_referer( 'ifs_visa_save_action', 'ifs_visa_nonce' );

        $customer_id         = intval( $_POST['customer_id'] ?? 0 );
        $passenger_name      = sanitize_text_field( $_POST['passenger_name'] ?? '' );
        $passport_no         = strtoupper( sanitize_text_field( $_POST['passport_no'] ?? '' ) );
        $agent_id            = intval( $_POST['agent_id'] ?? 0 );
        $supplier_id         = intval( $_POST['supplier_id'] ?? 0 );
        $country             = sanitize_text_field( $_POST['country'] ?? '' );
        $visa_type           = sanitize_text_field( $_POST['visa_type'] ?? 'Tourist Visa' );
        $entry_type          = sanitize_text_field( $_POST['entry_type'] ?? 'Single Entry' );
        $tracking_no         = strtoupper( sanitize_text_field( $_POST['tracking_no'] ?? '' ) );
        $embassy_app_no      = strtoupper( sanitize_text_field( $_POST['embassy_app_no'] ?? '' ) );
        $submission_date     = sanitize_text_field( $_POST['submission_date'] ?? '' );
        $appointment_date    = sanitize_text_field( $_POST['appointment_date'] ?? '' );
        $expected_delivery   = sanitize_text_field( $_POST['expected_delivery'] ?? '' );
        $issue_date          = sanitize_text_field( $_POST['issue_date'] ?? '' );
        $expiry_date         = sanitize_text_field( $_POST['expiry_date'] ?? '' );
        $validity_days       = intval( $_POST['validity_days'] ?? 30 );
        $stay_duration       = sanitize_text_field( $_POST['stay_duration'] ?? '30 Days' );
        $processing_center   = sanitize_text_field( $_POST['processing_center'] ?? 'VFS Global Dhaka' );
        
        // Commercials
        $embassy_fee         = floatval( $_POST['embassy_fee'] ?? 0 );
        $service_fee         = floatval( $_POST['service_fee'] ?? 0 );
        $buy_price           = floatval( $_POST['buy_price'] ?? 0 );
        $sell_price          = floatval( $_POST['sell_price'] ?? 0 );
        $profit              = $sell_price - $buy_price;
        
        $status              = sanitize_text_field( $_POST['status'] ?? 'Processing' );
        $payment_status      = sanitize_text_field( $_POST['payment_status'] ?? 'Unpaid' );
        $payment_method      = sanitize_text_field( $_POST['payment_method'] ?? 'Bank Transfer' );
        $remarks             = sanitize_textarea_field( $_POST['remarks'] ?? '' );
        
        // Media Vault Attachments
        $passport_scan_url   = esc_url_raw( $_POST['passport_scan_url'] ?? '' );
        $photo_url           = esc_url_raw( $_POST['photo_url'] ?? '' );
        $visa_doc_url        = esc_url_raw( $_POST['visa_doc_url'] ?? '' );
        $supporting_doc_url  = esc_url_raw( $_POST['supporting_doc_url'] ?? '' );

        // Process Dynamic Document Checklist Repeater into JSON
        $doc_names    = $_POST['doc_name'] ?? array();
        $doc_statuses = $_POST['doc_status'] ?? array();
        $doc_notes    = $_POST['doc_note'] ?? array();
        $checklist    = array();

        if ( is_array( $doc_names ) ) {
            foreach ( $doc_names as $k => $doc_name ) {
                $doc_name_clean = sanitize_text_field( $doc_name );
                if ( ! empty( $doc_name_clean ) ) {
                    $checklist[] = array(
                        'name'   => $doc_name_clean,
                        'status' => sanitize_text_field( $doc_statuses[ $k ] ?? 'Received' ),
                        'note'   => sanitize_text_field( $doc_notes[ $k ] ?? '' ),
                    );
                }
            }
        }
        $documents_collected = wp_json_encode( $checklist );

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
                'passenger_name'      => $passenger_name,
                'passport_no'         => $passport_no,
                'agent_id'            => $agent_id,
                'supplier_id'         => $supplier_id,
                'country'             => $country,
                'visa_type'           => $visa_type,
                'entry_type'          => $entry_type,
                'tracking_no'         => $tracking_no,
                'embassy_app_no'      => $embassy_app_no,
                'processing_center'   => $processing_center,
                'submission_date'     => $submission_date,
                'appointment_date'    => ! empty( $appointment_date ) ? $appointment_date : '1970-01-01',
                'expected_delivery'   => ! empty( $expected_delivery ) ? $expected_delivery : '1970-01-01',
                'issue_date'          => ! empty( $issue_date ) ? $issue_date : '1970-01-01',
                'expiry_date'         => ! empty( $expiry_date ) ? $expiry_date : '1970-01-01',
                'validity_days'       => $validity_days,
                'stay_duration'       => $stay_duration,
                'embassy_fee'         => $embassy_fee,
                'service_fee'         => $service_fee,
                'buy_price'           => $buy_price,
                'sell_price'          => $sell_price,
                'profit'              => $profit,
                'status'              => $status,
                'payment_status'      => $payment_status,
                'payment_method'      => $payment_method,
                'documents_collected' => $documents_collected,
                'remarks'             => $remarks,
                'passport_scan_url'   => $passport_scan_url,
                'photo_url'           => $photo_url,
                'visa_doc_url'        => $visa_doc_url,
                'supporting_doc_url'  => $supporting_doc_url,
                'created_by'          => get_current_user_id()
            );

            if ( $is_edit ) {
                $wpdb->update( $table_visas, $data, array( 'id' => $id ) );
                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Visa portfolio record updated successfully.</div>';
            } else {
                $data['created_at'] = current_time( 'mysql' );
                $wpdb->insert( $table_visas, $data );
                $id      = $wpdb->insert_id;
                $is_edit = true;
                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> New Visa application opened (#VSA-' . str_pad( (string) $id, 5, '0', STR_PAD_LEFT ) . ').</div>';
            }

            if ( function_exists( 'ifs_terp_log_activity' ) ) {
                ifs_terp_log_activity( "Processed Visa Dossier #VSA-$id for $country | Applicant: $passenger_name" );
            }
        } else {
            $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> ' . implode( '<br>', $errors ) . '</div>';
        }
    }

    $row = false;
    if ( $is_edit ) {
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_visas WHERE id = %d", $id ) );
    }

    $customers = $wpdb->get_results( "SELECT id, title, full_name, mobile, passport_no, nationality, photo_url, passport_copy_url FROM $table_customers ORDER BY full_name ASC" );
    $agents    = $wpdb->get_results( "SELECT id, agency_name, current_balance FROM $table_agents WHERE status = 'Active' ORDER BY agency_name ASC" );
    $suppliers = $wpdb->get_results( "SELECT id, supplier_name, current_balance FROM $table_suppliers WHERE status = 'Active' ORDER BY supplier_name ASC" );

    // Field Defaults
    $val_customer     = $is_edit ? intval( $row->customer_id ) : 0;
    $val_pax_name     = $is_edit ? esc_attr( $row->passenger_name ?? '' ) : '';
    $val_passport     = $is_edit ? esc_attr( $row->passport_no ?? '' ) : '';
    $val_agent        = $is_edit ? intval( $row->agent_id ?? 0 ) : 0;
    $val_supplier     = $is_edit ? intval( $row->supplier_id ?? 0 ) : 0;
    $val_country      = $is_edit ? esc_attr( $row->country ) : 'Saudi Arabia';
    $val_type         = $is_edit ? esc_attr( $row->visa_type ) : 'Tourist Visa';
    $val_entry        = $is_edit ? esc_attr( $row->entry_type ?? 'Single Entry' ) : 'Single Entry';
    $val_tracking     = $is_edit ? esc_attr( $row->tracking_no ?? '' ) : '';
    $val_embassy_app  = $is_edit ? esc_attr( $row->embassy_app_no ?? '' ) : '';
    $val_center       = $is_edit ? esc_attr( $row->processing_center ?? 'VFS Global Dhaka' ) : 'VFS Global Dhaka';
    $val_submission   = $is_edit ? esc_attr( $row->submission_date ) : date( 'Y-m-d' );
    $val_appointment  = ( $is_edit && ! empty( $row->appointment_date ) && $row->appointment_date !== '1970-01-01' ) ? esc_attr( $row->appointment_date ) : '';
    $val_expected     = ( $is_edit && ! empty( $row->expected_delivery ) && $row->expected_delivery !== '1970-01-01' ) ? esc_attr( $row->expected_delivery ) : date( 'Y-m-d', strtotime( '+7 days' ) );
    $val_issue_date   = ( $is_edit && ! empty( $row->issue_date ) && $row->issue_date !== '1970-01-01' ) ? esc_attr( $row->issue_date ) : '';
    $val_expiry_date  = ( $is_edit && ! empty( $row->expiry_date ) && $row->expiry_date !== '1970-01-01' ) ? esc_attr( $row->expiry_date ) : '';
    $val_validity     = $is_edit ? intval( $row->validity_days ?? 30 ) : 30;
    $val_stay         = $is_edit ? esc_attr( $row->stay_duration ?? '30 Days' ) : '30 Days';
    
    $val_embassy_fee  = $is_edit ? floatval( $row->embassy_fee ?? 0 ) : '';
    $val_service_fee  = $is_edit ? floatval( $row->service_fee ?? 0 ) : '';
    $val_buy          = $is_edit ? floatval( $row->buy_price ) : '';
    $val_sell         = $is_edit ? floatval( $row->sell_price ) : '';
    $val_profit       = $is_edit ? floatval( $row->profit ) : 0;
    
    $val_status       = $is_edit ? esc_attr( $row->status ) : 'Processing';
    $val_pay_status   = $is_edit ? esc_attr( $row->payment_status ?? 'Unpaid' ) : 'Unpaid';
    $val_pay_method   = $is_edit ? esc_attr( $row->payment_method ?? 'Bank Transfer' ) : 'Bank Transfer';
    $val_remarks      = $is_edit ? esc_textarea( $row->remarks ?? '' ) : '';

    $val_passport_scan = $is_edit ? esc_url( $row->passport_scan_url ?? '' ) : '';
    $val_photo_scan    = $is_edit ? esc_url( $row->photo_url ?? '' ) : '';
    $val_visa_doc      = $is_edit ? esc_url( $row->visa_doc_url ?? '' ) : '';
    $val_support_doc   = $is_edit ? esc_url( $row->supporting_doc_url ?? '' ) : '';

    // Document Checklist Parser
    $checklist_items = array();
    if ( $is_edit && ! empty( $row->documents_collected ) ) {
        $decoded = json_decode( $row->documents_collected, true );
        if ( is_array( $decoded ) ) {
            $checklist_items = $decoded;
        }
    }
    if ( empty( $checklist_items ) ) {
        $checklist_items = array(
            array( 'name' => 'Original Passport (Min 6 Months Validity)', 'status' => 'Received', 'note' => 'Original Physical Book' ),
            array( 'name' => 'Passport Size Photos (2x2 White BG)', 'status' => 'Received', 'note' => '2 Copies Lab Print' ),
            array( 'name' => 'Bank Statement & Solvency Certificate', 'status' => 'Received', 'note' => 'Last 6 Months Verified' ),
            array( 'name' => 'Trade License / Office NOC / Visiting Card', 'status' => 'Pending', 'note' => 'English Translated & Notarized' ),
            array( 'name' => 'Air Ticket Itinerary & Hotel Booking', 'status' => 'Received', 'note' => 'Confirmed Voucher Attached' ),
        );
    }
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
                            <h3 class="ifs-card-title">Applicant, Destination &amp; Category</h3>
                            <p class="ifs-card-desc">Assign traveler portfolio, destination country, and entry permission type</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
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
                                                data-photo="<?php echo esc_url( $cus->photo_url ?: '' ); ?>"
                                                data-passportscan="<?php echo esc_url( $cus->passport_copy_url ?: '' ); ?>"
                                                <?php selected( $val_customer, $cus->id ); ?>>
                                            <?php echo esc_html( $t_prefix . $cus->full_name . ' (' . $cus->mobile . ')' . $p_num ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <input type="hidden" name="passenger_name" id="inp_passenger_name_hidden" value="<?php echo $val_pax_name; ?>">
                            <input type="hidden" name="passport_no" id="inp_passport_no_hidden" value="<?php echo $val_passport; ?>">
                        </div>

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

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_country">Destination Country <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-admin-site-alt3 field-icon"></span>
                                <input type="text" name="country" id="inp_country" required 
                                       value="<?php echo $val_country; ?>" 
                                       placeholder="e.g. Saudi Arabia / UAE / UK / Canada" class="ifs-input-field">
                            </div>
                        </div>

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

                <!-- Section 2: Embassy Schedule, Tracking & Center -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">02</div>
                        <div>
                            <h3 class="ifs-card-title">Timeline, Processing Center &amp; Tracking</h3>
                            <p class="ifs-card-desc">Embassy submission schedule, application IDs, and processing logistics</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_center">Processing Center / Embassy</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-building field-icon"></span>
                                <input type="text" name="processing_center" id="inp_center" 
                                       value="<?php echo $val_center; ?>" placeholder="e.g. VFS Global / Embassy Counter" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_tracking_no">Embassy Application / Tracking No</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-search field-icon"></span>
                                <input type="text" name="tracking_no" id="inp_tracking_no" 
                                       value="<?php echo $val_tracking; ?>" 
                                       placeholder="e.g. VFS-998821 / GWF065432" class="ifs-input-field uppercase font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_embassy_app">MOFA / Reference No</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-media-document field-icon"></span>
                                <input type="text" name="embassy_app_no" id="inp_embassy_app" 
                                       value="<?php echo $val_embassy_app; ?>" placeholder="e.g. MOFA-123456" class="ifs-input-field font-mono uppercase">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_submission_date">Submission Date <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-calendar-alt field-icon"></span>
                                <input type="date" name="submission_date" id="inp_submission_date" required 
                                       value="<?php echo $val_submission; ?>" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_appointment_date">Biometric / Interview Date</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-clock field-icon"></span>
                                <input type="date" name="appointment_date" id="inp_appointment_date" 
                                       value="<?php echo $val_appointment; ?>" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_expected_delivery">Expected Delivery Date</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-calendar field-icon"></span>
                                <input type="date" name="expected_delivery" id="inp_expected_delivery" 
                                       value="<?php echo $val_expected; ?>" class="ifs-input-field">
                            </div>
                        </div>

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

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_validity">Validity (Days)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-backup field-icon"></span>
                                <input type="number" name="validity_days" id="inp_validity" 
                                       value="<?php echo $val_validity; ?>" placeholder="30" class="ifs-input-field font-mono">
                            </div>
                        </div>

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
                    </div>
                </div>

                <!-- Section 3: Dynamic Document Checklist Repeater -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header" style="justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <div class="ifs-step-num">03</div>
                            <div>
                                <h3 class="ifs-card-title">Physical Document Checklist (Dynamic Repeater)</h3>
                                <p class="ifs-card-desc">Track individual documents received, pending items, or embassy submissions</p>
                            </div>
                        </div>
                        <button type="button" class="ifs-btn-repeater-add" id="ifsAddDocBtn">
                            <span class="dashicons dashicons-plus-alt2"></span> Add Item
                        </button>
                    </div>

                    <div class="ifs-repeater-container" id="ifsDocRepeaterWrap">
                        <?php foreach ( $checklist_items as $index => $item ) : ?>
                            <div class="ifs-repeater-row">
                                <div class="repeater-field flex-2">
                                    <input type="text" name="doc_name[]" value="<?php echo esc_attr( $item['name'] ); ?>" placeholder="Document Title (e.g. Original Passport)" class="ifs-input-field" required>
                                </div>
                                <div class="repeater-field flex-1">
                                    <select name="doc_status[]" class="ifs-input-field">
                                        <option value="Received" <?php selected( $item['status'], 'Received' ); ?>>Received (In Office)</option>
                                        <option value="Submitted" <?php selected( $item['status'], 'Submitted' ); ?>>Submitted to Embassy</option>
                                        <option value="Pending" <?php selected( $item['status'], 'Pending' ); ?>>Pending from Client</option>
                                        <option value="Not Required" <?php selected( $item['status'], 'Not Required' ); ?>>Not Required</option>
                                    </select>
                                </div>
                                <div class="repeater-field flex-2">
                                    <input type="text" name="doc_note[]" value="<?php echo esc_attr( $item['note'] ?? '' ); ?>" placeholder="Notes (e.g. 2 copies, notarized)" class="ifs-input-field">
                                </div>
                                <button type="button" class="ifs-btn-repeater-del" title="Remove Document"><span class="dashicons dashicons-trash"></span></button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Section 4: Live Media Vault Attachment Cards -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">04</div>
                        <div>
                            <h3 class="ifs-card-title">Digital Document Vault &amp; Attachments</h3>
                            <p class="ifs-card-desc">Store passenger portrait, passport bio-page scan, stamped visa copy, and embassy letters</p>
                        </div>
                    </div>

                    <div class="ifs-vault-grid">
                        <!-- 1. Applicant Portrait Photo -->
                        <div class="ifs-vault-card">
                            <div class="vault-card-thumb" id="prev_vault_photo">
                                <?php if ( ! empty( $val_photo_scan ) ) : ?>
                                    <img src="<?php echo $val_photo_scan; ?>" alt="Applicant Photo" />
                                <?php else : ?>
                                    <div class="vault-empty-icon"><span class="dashicons dashicons-camera"></span></div>
                                <?php endif; ?>
                            </div>
                            <div class="vault-card-body">
                                <div class="vault-item-title">Applicant Photograph</div>
                                <div class="vault-item-meta">Passport size 2x2 white background</div>
                                <input type="hidden" name="photo_url" id="inp_photo_url" value="<?php echo $val_photo_scan; ?>">
                                <div class="vault-action-row">
                                    <button type="button" class="vault-btn-action" id="ifsUploadPhotoBtn"><span class="dashicons dashicons-upload"></span> <?php echo ! empty( $val_photo_scan ) ? 'Replace Photo' : 'Upload Photo'; ?></button>
                                    <button type="button" class="vault-btn-remove <?php echo empty( $val_photo_scan ) ? 'hide' : ''; ?>" id="ifsRemovePhotoBtn"><span class="dashicons dashicons-trash"></span></button>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Passport Scan -->
                        <div class="ifs-vault-card">
                            <div class="vault-card-thumb" id="prev_vault_passport">
                                <?php if ( ! empty( $val_passport_scan ) ) : ?>
                                    <?php if ( preg_match( '/\.(jpg|jpeg|png|webp)$/i', $val_passport_scan ) ) : ?>
                                        <img src="<?php echo $val_passport_scan; ?>" alt="Passport Scan" />
                                    <?php else : ?>
                                        <div class="vault-pdf-icon"><span class="dashicons dashicons-pdf"></span><small>PDF</small></div>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <div class="vault-empty-icon"><span class="dashicons dashicons-media-document"></span></div>
                                <?php endif; ?>
                            </div>
                            <div class="vault-card-body">
                                <div class="vault-item-title">Passport Bio-Page Scan</div>
                                <div class="vault-item-meta">Color scanned PDF or image</div>
                                <input type="hidden" name="passport_scan_url" id="inp_passport_scan" value="<?php echo $val_passport_scan; ?>">
                                <div class="vault-action-row">
                                    <button type="button" class="vault-btn-action" id="ifsUploadPassportBtn"><span class="dashicons dashicons-upload"></span> <?php echo ! empty( $val_passport_scan ) ? 'Replace Scan' : 'Upload Passport'; ?></button>
                                    <button type="button" class="vault-btn-remove <?php echo empty( $val_passport_scan ) ? 'hide' : ''; ?>" id="ifsRemovePassportBtn"><span class="dashicons dashicons-trash"></span></button>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Approved Visa Document -->
                        <div class="ifs-vault-card">
                            <div class="vault-card-thumb" id="prev_vault_visa">
                                <?php if ( ! empty( $val_visa_doc ) ) : ?>
                                    <?php if ( preg_match( '/\.(jpg|jpeg|png|webp)$/i', $val_visa_doc ) ) : ?>
                                        <img src="<?php echo $val_visa_doc; ?>" alt="Visa Document" />
                                    <?php else : ?>
                                        <div class="vault-pdf-icon"><span class="dashicons dashicons-pdf"></span><small>PDF</small></div>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <div class="vault-empty-icon"><span class="dashicons dashicons-id-alt"></span></div>
                                <?php endif; ?>
                            </div>
                            <div class="vault-card-body">
                                <div class="vault-item-title">Approved E-Visa / Stamp</div>
                                <div class="vault-item-meta">Stamped sticker or issued E-Visa PDF</div>
                                <input type="hidden" name="visa_doc_url" id="inp_visa_doc" value="<?php echo $val_visa_doc; ?>">
                                <div class="vault-action-row">
                                    <button type="button" class="vault-btn-action" id="ifsUploadVisaBtn"><span class="dashicons dashicons-upload"></span> <?php echo ! empty( $val_visa_doc ) ? 'Replace File' : 'Attach Visa'; ?></button>
                                    <button type="button" class="vault-btn-remove <?php echo empty( $val_visa_doc ) ? 'hide' : ''; ?>" id="ifsRemoveVisaBtn"><span class="dashicons dashicons-trash"></span></button>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Supporting Document -->
                        <div class="ifs-vault-card">
                            <div class="vault-card-thumb" id="prev_vault_support">
                                <?php if ( ! empty( $val_support_doc ) ) : ?>
                                    <?php if ( preg_match( '/\.(jpg|jpeg|png|webp)$/i', $val_support_doc ) ) : ?>
                                        <img src="<?php echo $val_support_doc; ?>" alt="Supporting Document" />
                                    <?php else : ?>
                                        <div class="vault-pdf-icon"><span class="dashicons dashicons-pdf"></span><small>PDF</small></div>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <div class="vault-empty-icon"><span class="dashicons dashicons-portfolio"></span></div>
                                <?php endif; ?>
                            </div>
                            <div class="vault-card-body">
                                <div class="vault-item-title">Embassy Support Packet</div>
                                <div class="vault-item-meta">NOC, Solvency &amp; Insurance files</div>
                                <input type="hidden" name="supporting_doc_url" id="inp_supporting_doc" value="<?php echo $val_support_doc; ?>">
                                <div class="vault-action-row">
                                    <button type="button" class="vault-btn-action" id="ifsUploadSupportBtn"><span class="dashicons dashicons-upload"></span> <?php echo ! empty( $val_support_doc ) ? 'Replace Packet' : 'Attach Packet'; ?></button>
                                    <button type="button" class="vault-btn-remove <?php echo empty( $val_support_doc ) ? 'hide' : ''; ?>" id="ifsRemoveSupportBtn"><span class="dashicons dashicons-trash"></span></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 5: Commercial Fees & Agency Profit Settlement -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">05</div>
                        <div>
                            <h3 class="ifs-card-title">Embassy Fees, Service Charges &amp; Net Margin</h3>
                            <p class="ifs-card-desc">Supplier cost, customer billing charge, payment channel, and real-time agency yield</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="vsa_buy">Embassy / Supplier Cost (৳) <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-cart field-icon"></span>
                                <input type="number" step="0.01" name="buy_price" id="vsa_buy" required 
                                       value="<?php echo $val_buy; ?>" placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="vsa_sell">Client Total Invoiced Fee (৳) <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-money-alt field-icon"></span>
                                <input type="number" step="0.01" name="sell_price" id="vsa_sell" required 
                                       value="<?php echo $val_sell; ?>" placeholder="0.00" class="ifs-input-field font-mono font-bold color-blue">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label">Gross Margin / Profit (৳)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-chart-line field-icon"></span>
                                <input type="text" id="vsa_profit" readonly 
                                       value="<?php echo number_format( $val_profit, 2 ); ?>" 
                                       class="ifs-input-field font-mono font-bold <?php echo ( $val_profit >= 0 ) ? 'profit-positive' : 'profit-negative'; ?>">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_pay_status">Payment Status</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-money-alt field-icon"></span>
                                <select name="payment_status" id="inp_pay_status" class="ifs-input-field">
                                    <option value="Paid" <?php selected( $val_pay_status, 'Paid' ); ?>>Fully Paid</option>
                                    <option value="Partial" <?php selected( $val_pay_status, 'Partial' ); ?>>Partially Paid</option>
                                    <option value="Unpaid" <?php selected( $val_pay_status, 'Unpaid' ); ?>>Unpaid / Due</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_pay_method">Payment Method</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-vault field-icon"></span>
                                <select name="payment_method" id="inp_pay_method" class="ifs-input-field">
                                    <option value="Bank Transfer" <?php selected( $val_pay_method, 'Bank Transfer' ); ?>>Bank Transfer</option>
                                    <option value="Cash" <?php selected( $val_pay_method, 'Cash' ); ?>>Cash at Counter</option>
                                    <option value="bKash / MFS" <?php selected( $val_pay_method, 'bKash / MFS' ); ?>>bKash / Nagad</option>
                                    <option value="Agent Deposit" <?php selected( $val_pay_method, 'Agent Deposit' ); ?>>Agent Credit Balance</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-3">
                            <label class="ifs-field-label" for="inp_remarks">Special Remarks / Appointment Schedule / Notes</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-edit field-icon"></span>
                                <input type="text" name="remarks" id="inp_remarks" 
                                       value="<?php echo $val_remarks; ?>" 
                                       placeholder="e.g. Biometrics at VFS Gulshan, Embassy appointment token details..." class="ifs-input-field">
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

            <!-- Right Sidebar: Ultra-Polished Holographic Visa Dossier Pass -->
            <div class="ifs-preview-sidebar">
                <div class="ifs-preview-sticky">
                    
                    <div class="ifs-card-preview-header">
                        <div class="preview-header-left">
                            <span class="pulse-beacon"></span>
                            <span>Live Visa Dossier Preview</span>
                        </div>
                        <span class="preview-secure-tag"><span class="dashicons dashicons-shield"></span> IMMIGRATION</span>
                    </div>

                    <!-- Holographic Visa Passport Card -->
                    <div class="ifs-visa-card">
                        
                        <!-- Top Head Strip -->
                        <div class="visa-head-strip">
                            <div class="visa-country-tag">
                                <span class="dashicons dashicons-admin-site-alt3"></span>
                                <span id="prev_country">SAUDI ARABIA</span>
                            </div>
                            <span class="visa-type-badge" id="prev_type">TOURIST VISA</span>
                        </div>

                        <!-- Applicant Hero Profile Row -->
                        <div class="visa-applicant-hero">
                            <div class="visa-avatar-wrap">
                                <div class="visa-avatar" id="prev_avatar">
                                    <?php if ( ! empty( $val_photo_scan ) ) : ?>
                                        <img src="<?php echo $val_photo_scan; ?>" alt="Applicant" />
                                    <?php else : ?>
                                        <span>VA</span>
                                    <?php endif; ?>
                                </div>
                                <div class="visa-entry-pill" id="prev_entry_badge">SGL</div>
                            </div>
                            <div class="visa-applicant-details">
                                <h4 class="visa-name" id="prev_name">MOHAMMED RAHIM</h4>
                                <div class="visa-submeta" id="prev_meta">PPT: NOT SET &bull; BANGLADESHI</div>
                            </div>
                        </div>

                        <!-- Grid Specifications Matrix -->
                        <div class="visa-grid-specs font-mono">
                            <div class="spec-cell">
                                <span class="visa-lbl">TRACKING / VFS REF</span>
                                <strong class="visa-val color-cyan" id="prev_tracking">PENDING</strong>
                            </div>
                            <div class="spec-cell">
                                <span class="visa-lbl">ENTRY PERMISSION</span>
                                <strong class="visa-val" id="prev_entry">SINGLE</strong>
                            </div>
                            <div class="spec-cell">
                                <span class="visa-lbl">SUBMISSION DATE</span>
                                <strong class="visa-val" id="prev_sub_date">20 AUG 2026</strong>
                            </div>
                            <div class="spec-cell">
                                <span class="visa-lbl">EST. DELIVERY DATE</span>
                                <strong class="visa-val color-green" id="prev_exp_date">27 AUG 2026</strong>
                            </div>
                            <div class="spec-cell">
                                <span class="visa-lbl">STAY VALIDITY</span>
                                <strong class="visa-val" id="prev_stay_val">30 DAYS</strong>
                            </div>
                            <div class="spec-cell">
                                <span class="visa-lbl">TOTAL INVOICED</span>
                                <strong class="visa-val color-green" id="prev_sell">৳0.00</strong>
                            </div>
                        </div>

                        <!-- ICAO MRV Code Zone -->
                        <div class="visa-mrv-zone font-mono">
                            <div class="mrv-line" id="prev_mrv_1">V&lt;BGD&lt;&lt;MOHAMMED&lt;&lt;RAHIM&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;</div>
                            <div class="mrv-line" id="prev_mrv_2">A000000000BGD0000000M0000000&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;00</div>
                        </div>

                        <div class="visa-fee-footer">
                            <div class="visa-barcode-lines"></div>
                            <span class="visa-barcode-txt font-mono">IATCI &bull; EMBASSY VISA DOSSIER VALIDATED</span>
                        </div>
                    </div>

                    <!-- Commercial Intelligence Box -->
                    <div class="ifs-intel-box">
                        <div class="intel-head"><span class="dashicons dashicons-analytics"></span> Real-Time Profit Yield</div>
                        <div class="intel-body">
                            <div class="intel-row">
                                <span>Gross Profit Margin:</span>
                                <strong id="intel_profit" class="color-green">৳0.00</strong>
                            </div>
                            <div class="intel-row">
                                <span>Agency Yield Ratio:</span>
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

        .ifs-split-visa-editor { display: grid; grid-template-columns: 1fr 410px; gap: 28px; align-items: flex-start; }
        @media (max-width: 1180px) { .ifs-split-visa-editor { grid-template-columns: 1fr; } }

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
        .ifs-field-wrap .ifs-input-field:focus { border-color: #0284c7; box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12); }
        .ifs-field-wrap:focus-within .field-icon { color: #0284c7; }

        .uppercase { text-transform: uppercase; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .font-bold { font-weight: 700; }
        .color-blue { color: #003376 !important; }

        .profit-positive { background: #f0fdf4 !important; color: #166534 !important; border-color: #bbf7d0 !important; }
        .profit-negative { background: #fef2f2 !important; color: #dc2626 !important; border-color: #fecaca !important; }

        /* ----------------------------------------------------
           DYNAMIC CHECKLIST REPEATER
        ---------------------------------------------------- */
        .ifs-btn-repeater-add {
            background: #eff6ff;
            color: #0284c7;
            border: 1px solid #bae6fd;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s ease;
        }
        .ifs-btn-repeater-add:hover { background: #0284c7; color: #ffffff; }
        .ifs-repeater-container { display: flex; flex-direction: column; gap: 10px; }
        .ifs-repeater-row {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 10px 12px;
            border-radius: 10px;
            transition: border-color 0.2s ease;
        }
        .ifs-repeater-row:hover { border-color: #cbd5e1; }
        .repeater-field.flex-1 { flex: 1; }
        .repeater-field.flex-2 { flex: 2; }
        .ifs-btn-repeater-del {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
            border-radius: 8px;
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
            flex-shrink: 0;
        }
        .ifs-btn-repeater-del:hover { background: #dc2626; color: #ffffff; }

        /* ----------------------------------------------------
           DIGITAL VAULT INTERACTIVE MEDIA PREVIEWS
        ---------------------------------------------------- */
        .ifs-vault-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 18px;
        }
        .ifs-vault-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            transition: all 0.2s ease;
        }
        .ifs-vault-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 6px 18px -4px rgba(15, 23, 42, 0.06);
            background: #ffffff;
        }
        .vault-card-thumb {
            width: 100%;
            height: 120px;
            border-radius: 10px;
            background: #ffffff;
            border: 1px dashed #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .vault-card-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .vault-empty-icon { color: #94a3b8; display: flex; flex-direction: column; align-items: center; }
        .vault-empty-icon .dashicons { font-size: 34px; width: 34px; height: 34px; }
        .vault-pdf-icon { display: flex; flex-direction: column; align-items: center; color: #dc2626; font-weight: 800; }
        .vault-pdf-icon .dashicons { font-size: 36px; width: 36px; height: 36px; }
        .vault-pdf-icon small { font-size: 10.5px; margin-top: 2px; background: #fee2e2; padding: 1px 6px; border-radius: 4px; }
        .vault-card-body { display: flex; flex-direction: column; gap: 4px; }
        .vault-item-title { font-size: 12.5px; font-weight: 700; color: #0f172a; }
        .vault-item-meta { font-size: 11px; color: #64748b; margin-bottom: 6px; }
        .vault-action-row { display: flex; gap: 6px; }
        .vault-btn-action {
            flex: 1;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 700;
            color: #334155;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            transition: all 0.2s ease;
        }
        .vault-btn-action:hover { background: #0284c7; color: #ffffff; border-color: #0284c7; }
        .vault-btn-remove {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            border-radius: 6px;
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .vault-btn-remove:hover { background: #dc2626; color: #ffffff; }
        .vault-btn-remove.hide { display: none !important; }

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

        /* ----------------------------------------------------
           HOLOGRAPHIC VISA DOSSIER PREVIEW CARD
        ---------------------------------------------------- */
        .ifs-preview-sticky { position: sticky; top: 30px; }
        .ifs-card-preview-header {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #475569;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .preview-header-left { display: flex; align-items: center; gap: 8px; }
        .pulse-beacon {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25);
            animation: pulseGlow 1.8s infinite;
        }
        @keyframes pulseGlow {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.6; }
        }
        .preview-secure-tag {
            font-size: 10px;
            font-weight: 800;
            background: #e2e8f0;
            color: #475569;
            padding: 2px 7px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .preview-secure-tag .dashicons { font-size: 12px; width: 12px; height: 12px; }

        .ifs-visa-card {
            background: radial-gradient(circle at 100% 0%, #0284c7 0%, #0369a1 50%, #0c4a6e 100%);
            border-radius: 18px;
            padding: 22px;
            color: #ffffff;
            box-shadow: 0 20px 40px -8px rgba(2, 132, 199, 0.45);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        .ifs-visa-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            pointer-events: none;
        }

        .visa-head-strip {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        }
        .visa-country-tag {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.8px;
            color: #7dd3fc;
        }
        .visa-country-tag .dashicons { font-size: 15px; width: 15px; height: 15px; }
        .visa-type-badge {
            background: rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(6px);
            padding: 2px 9px;
            border-radius: 6px;
            font-size: 9.5px;
            font-weight: 800;
            letter-spacing: 0.5px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .visa-applicant-hero {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
        }
        .visa-avatar-wrap { position: relative; flex-shrink: 0; }
        .visa-avatar {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 16px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .visa-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .visa-entry-pill {
            position: absolute;
            bottom: -3px;
            right: -3px;
            background: #0284c7;
            color: #ffffff;
            font-size: 8px;
            font-weight: 900;
            padding: 1px 4px;
            border-radius: 4px;
            border: 1px solid #ffffff;
        }
        .visa-applicant-details { flex: 1; min-width: 0; }
        .visa-name { margin: 0; font-size: 14.5px; font-weight: 800; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .visa-submeta { font-size: 11px; color: #bae6fd; margin-top: 2px; }

        .visa-grid-specs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 12px;
            padding: 12px 0;
            border-top: 1px dashed rgba(255, 255, 255, 0.2);
            border-bottom: 1px dashed rgba(255, 255, 255, 0.2);
            margin-bottom: 12px;
        }
        .spec-cell { display: flex; flex-direction: column; gap: 2px; }
        .visa-lbl { font-size: 8px; font-weight: 800; color: #7dd3fc; letter-spacing: 0.5px; }
        .visa-val { font-size: 11px; font-weight: 700; color: #ffffff; }
        .color-cyan { color: #38bdf8 !important; }
        .color-green { color: #86efac !important; }

        /* ICAO MRV Line Zone */
        .visa-mrv-zone {
            background: rgba(0, 0, 0, 0.25);
            padding: 8px 10px;
            border-radius: 8px;
            margin-bottom: 12px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .mrv-line { font-size: 8.5px; color: #e0f2fe; letter-spacing: 1.2px; line-height: 1.35; white-space: nowrap; overflow: hidden; text-overflow: clip; }

        .visa-fee-footer { text-align: center; }
        .visa-barcode-lines {
            height: 18px;
            background: repeating-linear-gradient(90deg, #ffffff, #ffffff 2px, transparent 2px, transparent 4px, #ffffff 4px, #ffffff 5px, transparent 5px, transparent 8px);
            opacity: 0.8;
            margin-bottom: 4px;
            border-radius: 2px;
        }
        .visa-barcode-txt { font-size: 8px; color: #7dd3fc; letter-spacing: 1px; }

        /* Intelligence Card */
        .ifs-intel-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-top: 18px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02); }
        .intel-head { font-size: 12px; font-weight: 800; color: #003376; text-transform: uppercase; letter-spacing: 0.4px; display: flex; align-items: center; gap: 6px; margin-bottom: 10px; }
        .intel-head .dashicons { color: #0284c7; font-size: 16px; width: 16px; height: 16px; }
        .intel-body { display: flex; flex-direction: column; gap: 6px; }
        .intel-row { display: flex; justify-content: space-between; font-size: 12.5px; color: #475569; }
        .intel-row strong { font-weight: 800; font-size: 13.5px; }
    </style>

    <!-- Real-Time Interactive Engine & Live Media Vault Handlers -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const inpCustomer   = document.getElementById('inp_customer');
        const hiddenPaxName = document.getElementById('inp_passenger_name_hidden');
        const hiddenPassNo  = document.getElementById('inp_passport_no_hidden');

        const inpCountry    = document.getElementById('inp_country');
        const inpVisaType   = document.getElementById('inp_visa_type');
        const inpEntryType  = document.getElementById('inp_entry_type');
        const inpTracking   = document.getElementById('inp_tracking_no');
        const inpSubDate    = document.getElementById('inp_submission_date');
        const inpExpDate    = document.getElementById('inp_expected_delivery');
        const inpValidity   = document.getElementById('inp_validity');
        const inpBuy        = document.getElementById('vsa_buy');
        const inpSell       = document.getElementById('vsa_sell');

        const prevCountry   = document.getElementById('prev_country');
        const prevType      = document.getElementById('prev_type');
        const prevAvatar    = document.getElementById('prev_avatar');
        const prevName      = document.getElementById('prev_name');
        const prevMeta      = document.getElementById('prev_meta');
        const prevTracking  = document.getElementById('prev_tracking');
        const prevEntry     = document.getElementById('prev_entry');
        const prevEntryBdg  = document.getElementById('prev_entry_badge');
        const prevSubDate   = document.getElementById('prev_sub_date');
        const prevExpDate   = document.getElementById('prev_exp_date');
        const prevStayVal   = document.getElementById('prev_stay_val');
        const prevSell      = document.getElementById('prev_sell');
        const prevMrv1      = document.getElementById('prev_mrv_1');
        const prevMrv2      = document.getElementById('prev_mrv_2');

        const profitDisplay = document.getElementById('vsa_profit');
        const intelProfit   = document.getElementById('intel_profit');
        const intelRatio    = document.getElementById('intel_ratio');

        function updateVisaCard() {
            if (prevCountry) prevCountry.textContent = (inpCountry && inpCountry.value.trim()) ? inpCountry.value.trim().toUpperCase() : 'DESTINATION';
            if (prevType) prevType.textContent = (inpVisaType) ? inpVisaType.value.toUpperCase() : 'TOURIST VISA';
            
            const entryVal = inpEntryType ? inpEntryType.value : 'Single Entry';
            if (prevEntry) prevEntry.textContent = entryVal.toUpperCase();
            if (prevEntryBdg) prevEntryBdg.textContent = (entryVal === 'Single Entry') ? 'SGL' : ((entryVal === 'Multiple Entry') ? 'MULT' : 'DBL');
            
            const trkVal = (inpTracking && inpTracking.value.trim()) ? inpTracking.value.trim().toUpperCase() : 'PENDING';
            if (prevTracking) prevTracking.textContent = trkVal;

            let paxStr = 'MOHAMMED RAHIM';
            let passStr = 'NOT SET';
            let natStr = 'BANGLADESHI';

            // Customer Name & Meta Resolution
            if (inpCustomer && inpCustomer.selectedIndex > 0) {
                const selectedOpt = inpCustomer.options[inpCustomer.selectedIndex];
                paxName     = selectedOpt.getAttribute('data-name') || 'MOHAMMED RAHIM';
                passportNo  = selectedOpt.getAttribute('data-passport') || 'NOT SET';
                nationality = selectedOpt.getAttribute('data-nation') || 'BANGLADESHI';

                paxStr  = paxName;
                passStr = passportNo;
                natStr  = nationality;

                if (hiddenPaxName) hiddenPaxName.value = paxName;
                if (hiddenPassNo) hiddenPassNo.value   = passportNo;

                // Sync portrait if available
                const photoUrl = selectedOpt.getAttribute('data-photo');
                if (photoUrl && prevAvatar) {
                    prevAvatar.innerHTML = '<img src="' + photoUrl + '" alt="Applicant" />';
                } else if (prevAvatar) {
                    const parts = paxName.trim().split(' ');
                    const inits = parts.length > 1 ? (parts[0][0] + parts[parts.length-1][0]) : parts[0].slice(0, 2);
                    prevAvatar.innerHTML = '<span>' + inits.toUpperCase() + '</span>';
                }
            } else if (prevAvatar) {
                prevAvatar.innerHTML = '<span>VA</span>';
            }

            if (prevName) prevName.textContent = paxStr.toUpperCase();
            if (prevMeta) prevMeta.textContent = 'PPT: ' + passStr.toUpperCase() + ' • ' + natStr.toUpperCase();

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

            if (prevStayVal) {
                prevStayVal.textContent = (inpValidity ? inpValidity.value : 30) + ' DAYS';
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

            // MRV Line Generator
            const partsPax = paxStr.replace(/[^A-Za-z ]/g, '').trim().split(' ');
            const mrvSur   = partsPax.length > 1 ? partsPax[partsPax.length - 1].toUpperCase() : 'APPLICANT';
            const mrvGiv   = partsPax.length > 1 ? partsPax[0].toUpperCase() : 'NAME';
            
            let l1 = 'V<BGD' + mrvSur + '<<' + mrvGiv;
            while (l1.length < 44) { l1 += '<'; }
            if (l1.length > 44) l1 = l1.substring(0, 44);

            let l2 = passStr.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            while (l2.length < 9) { l2 += '<'; }
            l2 += '0BGD0000000M0000000<<<<<<<<<<<<<00';
            if (l2.length > 44) l2 = l2.substring(0, 44);

            if (prevMrv1) prevMrv1.textContent = l1;
            if (prevMrv2) prevMrv2.textContent = l2;
        }

        const watchFields = [inpCustomer, inpCountry, inpVisaType, inpEntryType, inpTracking, inpSubDate, inpExpDate, inpValidity, inpBuy, inpSell];
        watchFields.forEach(el => {
            if (el) {
                el.addEventListener('input', updateVisaCard);
                el.addEventListener('change', updateVisaCard);
            }
        });

        updateVisaCard();

        // Dynamic Document Checklist Repeater Engine
        const addDocBtn = document.getElementById('ifsAddDocBtn');
        const repeaterWrap = document.getElementById('ifsDocRepeaterWrap');

        if (addDocBtn && repeaterWrap) {
            addDocBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const row = document.createElement('div');
                row.className = 'ifs-repeater-row';
                row.innerHTML = `
                    <div class="repeater-field flex-2">
                        <input type="text" name="doc_name[]" placeholder="Document Title (e.g. NOC Letter)" class="ifs-input-field" required>
                    </div>
                    <div class="repeater-field flex-1">
                        <select name="doc_status[]" class="ifs-input-field">
                            <option value="Received">Received (In Office)</option>
                            <option value="Submitted">Submitted to Embassy</option>
                            <option value="Pending" selected>Pending from Client</option>
                            <option value="Not Required">Not Required</option>
                        </select>
                    </div>
                    <div class="repeater-field flex-2">
                        <input type="text" name="doc_note[]" placeholder="Notes (e.g. Attested copy)" class="ifs-input-field">
                    </div>
                    <button type="button" class="ifs-btn-repeater-del" title="Remove Document"><span class="dashicons dashicons-trash"></span></button>
                `;
                repeaterWrap.appendChild(row);
                bindDeleteButtons();
            });
        }

        function bindDeleteButtons() {
            const delButtons = document.querySelectorAll('.ifs-btn-repeater-del');
            delButtons.forEach(btn => {
                btn.onclick = function() {
                    if (document.querySelectorAll('.ifs-repeater-row').length > 1) {
                        this.closest('.ifs-repeater-row').remove();
                    } else {
                        alert('At least one document checklist item must remain.');
                    }
                };
            });
        }
        bindDeleteButtons();

        // Live Media Vault Helper
        function setupVaultUploader(btnId, removeBtnId, inputId, previewId, isPhoto) {
            const btn       = document.getElementById(btnId);
            const removeBtn = document.getElementById(removeBtnId);
            const input     = document.getElementById(inputId);
            const preview   = document.getElementById(previewId);

            if (btn && window.wp && wp.media) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const customUploader = wp.media({
                        title: 'Select or Upload Document File',
                        button: { text: 'Attach File' },
                        multiple: false
                    }).on('select', function() {
                        const attachment = customUploader.state().get('selection').first().toJSON();
                        if (attachment && attachment.url) {
                            input.value = attachment.url;
                            if (attachment.url.match(/\.(jpg|jpeg|png|webp|gif)$/i)) {
                                preview.innerHTML = '<img src="' + attachment.url + '" alt="Document Preview" />';
                            } else {
                                preview.innerHTML = '<div class="vault-pdf-icon"><span class="dashicons dashicons-pdf"></span><small>PDF</small></div>';
                            }
                            if (removeBtn) removeBtn.classList.remove('hide');
                            
                            if (isPhoto) {
                                const cardAvatar = document.getElementById('prev_avatar');
                                if (cardAvatar) cardAvatar.innerHTML = '<img src="' + attachment.url + '" alt="Applicant" />';
                            }
                        }
                    }).open();
                });
            }

            if (removeBtn) {
                removeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    input.value = '';
                    removeBtn.classList.add('hide');
                    if (isPhoto) {
                        preview.innerHTML = '<div class="vault-empty-icon"><span class="dashicons dashicons-camera"></span></div>';
                        updateVisaCard();
                    } else {
                        preview.innerHTML = '<div class="vault-empty-icon"><span class="dashicons dashicons-media-document"></span></div>';
                    }
                });
            }
        }

        setupVaultUploader('ifsUploadPhotoBtn', 'ifsRemovePhotoBtn', 'inp_photo_url', 'prev_vault_photo', true);
        setupVaultUploader('ifsUploadPassportBtn', 'ifsRemovePassportBtn', 'inp_passport_scan', 'prev_vault_passport', false);
        setupVaultUploader('ifsUploadVisaBtn', 'ifsRemoveVisaBtn', 'inp_visa_doc', 'prev_vault_visa', false);
        setupVaultUploader('ifsUploadSupportBtn', 'ifsRemoveSupportBtn', 'inp_supporting_doc', 'prev_vault_support', false);
    });
    </script>
    <?php
}