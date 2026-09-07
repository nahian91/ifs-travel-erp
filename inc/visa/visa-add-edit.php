<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_visa_add_edit_page' ) ) {
    /**
     * Enterprise Visa Application Management Console
     * Flat Minimal UI: No Shadows, Strict 42px Control Heights
     */
    function ifs_terp_visa_add_edit_page() {
        global $wpdb;
        $table_visas     = $wpdb->prefix . 'iterp_visa_applications';
        $table_customers = $wpdb->prefix . 'iterp_customers';
        $table_agents    = $wpdb->prefix . 'iterp_agents';
        $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
        $table_reqs      = $wpdb->prefix . 'iterp_visa_requirements';

        $id       = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
        $is_edit  = ( $id > 0 );
        $message  = '';
        $errors   = array();
        $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=visa' );

        // Auto-migration check for newly added columns
        $existing_cols = $wpdb->get_col( "DESC {$table_visas}", 0 );
        if ( ! empty( $existing_cols ) ) {
            if ( ! in_array( 'sponsor_name', $existing_cols, true ) ) {
                $wpdb->query( "ALTER TABLE {$table_visas} ADD sponsor_name varchar(255) DEFAULT '' NOT NULL AFTER country" );
            }
            if ( ! in_array( 'biometric_loc', $existing_cols, true ) ) {
                $wpdb->query( "ALTER TABLE {$table_visas} ADD biometric_loc varchar(150) DEFAULT 'VFS Gulshan' NOT NULL AFTER processing_center" );
            }
        }

        // Enqueue Media Uploader & Select2
        if ( function_exists( 'wp_enqueue_media' ) ) {
            wp_enqueue_media();
        }
        if ( ! wp_script_is( 'select2', 'registered' ) ) {
            wp_register_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), '4.1.0-rc.0', true );
            wp_register_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0-rc.0' );
        }
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'select2' );
        wp_enqueue_style( 'select2' );

        // Handle Form Submission
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_visa_submit'] ) ) {
            check_admin_referer( 'ifs_visa_save_action', 'ifs_visa_nonce' );

            $customer_id         = isset( $_POST['customer_id'] ) ? absint( wp_unslash( $_POST['customer_id'] ) ) : 0;
            $passenger_name      = isset( $_POST['passenger_name'] ) ? sanitize_text_field( wp_unslash( $_POST['passenger_name'] ) ) : '';
            $passport_no         = isset( $_POST['passport_no'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['passport_no'] ) ) ) : '';
            $passport_expiry     = isset( $_POST['passport_expiry'] ) ? sanitize_text_field( wp_unslash( $_POST['passport_expiry'] ) ) : '';
            $passport_status     = isset( $_POST['passport_status'] ) ? sanitize_text_field( wp_unslash( $_POST['passport_status'] ) ) : 'In Office';
            
            $agent_id            = isset( $_POST['agent_id'] ) ? absint( wp_unslash( $_POST['agent_id'] ) ) : 0;
            $supplier_id         = isset( $_POST['supplier_id'] ) ? absint( wp_unslash( $_POST['supplier_id'] ) ) : 0;
            $country             = isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : '';
            $sponsor_name        = isset( $_POST['sponsor_name'] ) ? sanitize_text_field( wp_unslash( $_POST['sponsor_name'] ) ) : '';
            $visa_type           = isset( $_POST['visa_type'] ) ? sanitize_text_field( wp_unslash( $_POST['visa_type'] ) ) : 'Tourist Visa';
            $entry_type          = isset( $_POST['entry_type'] ) ? sanitize_text_field( wp_unslash( $_POST['entry_type'] ) ) : 'Single Entry';
            $tracking_no         = isset( $_POST['tracking_no'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['tracking_no'] ) ) ) : '';
            $embassy_app_no      = isset( $_POST['embassy_app_no'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['embassy_app_no'] ) ) ) : '';
            $issued_visa_no      = isset( $_POST['issued_visa_no'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['issued_visa_no'] ) ) ) : '';

            $submission_date     = isset( $_POST['submission_date'] ) ? sanitize_text_field( wp_unslash( $_POST['submission_date'] ) ) : '';
            $appointment_date    = isset( $_POST['appointment_date'] ) ? sanitize_text_field( wp_unslash( $_POST['appointment_date'] ) ) : '';
            $expected_delivery   = isset( $_POST['expected_delivery'] ) ? sanitize_text_field( wp_unslash( $_POST['expected_delivery'] ) ) : '';
            $issue_date          = isset( $_POST['issue_date'] ) ? sanitize_text_field( wp_unslash( $_POST['issue_date'] ) ) : '';
            $expiry_date         = isset( $_POST['expiry_date'] ) ? sanitize_text_field( wp_unslash( $_POST['expiry_date'] ) ) : '';
            $validity_days       = isset( $_POST['validity_days'] ) ? intval( wp_unslash( $_POST['validity_days'] ) ) : 30;
            $stay_duration       = isset( $_POST['stay_duration'] ) ? sanitize_text_field( wp_unslash( $_POST['stay_duration'] ) ) : '30 Days';
            $processing_center   = isset( $_POST['processing_center'] ) ? sanitize_text_field( wp_unslash( $_POST['processing_center'] ) ) : 'VFS Global Dhaka';
            $biometric_loc       = isset( $_POST['biometric_loc'] ) ? sanitize_text_field( wp_unslash( $_POST['biometric_loc'] ) ) : 'VFS Gulshan';
            
            // Financial Ledger
            $embassy_fee         = isset( $_POST['embassy_fee'] ) ? (float) wp_unslash( $_POST['embassy_fee'] ) : 0;
            $service_fee         = isset( $_POST['service_fee'] ) ? (float) wp_unslash( $_POST['service_fee'] ) : 0;
            $buy_price           = isset( $_POST['buy_price'] ) ? (float) wp_unslash( $_POST['buy_price'] ) : 0;
            $sell_price          = isset( $_POST['sell_price'] ) ? (float) wp_unslash( $_POST['sell_price'] ) : 0;
            $paid_amount         = isset( $_POST['paid_amount'] ) ? (float) wp_unslash( $_POST['paid_amount'] ) : 0;
            $due_amount          = max( 0, $sell_price - $paid_amount );
            $profit              = $sell_price - $buy_price;
            
            $status              = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'Processing';
            $payment_status      = isset( $_POST['payment_status'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_status'] ) ) : 'Unpaid';
            $payment_method      = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : 'Bank Transfer';
            $remarks             = isset( $_POST['remarks'] ) ? sanitize_textarea_field( wp_unslash( $_POST['remarks'] ) ) : '';
            
            // Media Vault Attachments
            $passport_scan_url   = isset( $_POST['passport_scan_url'] ) ? esc_url_raw( wp_unslash( $_POST['passport_scan_url'] ) ) : '';
            $photo_url           = isset( $_POST['photo_url'] ) ? esc_url_raw( wp_unslash( $_POST['photo_url'] ) ) : '';
            $visa_doc_url        = isset( $_POST['visa_doc_url'] ) ? esc_url_raw( wp_unslash( $_POST['visa_doc_url'] ) ) : '';
            $supporting_doc_url  = isset( $_POST['supporting_doc_url'] ) ? esc_url_raw( wp_unslash( $_POST['supporting_doc_url'] ) ) : '';

            // Process Document Checklist Repeater
            $doc_names    = isset( $_POST['doc_name'] ) ? (array) wp_unslash( $_POST['doc_name'] ) : array();
            $doc_statuses = isset( $_POST['doc_status'] ) ? (array) wp_unslash( $_POST['doc_status'] ) : array();
            $doc_notes    = isset( $_POST['doc_note'] ) ? (array) wp_unslash( $_POST['doc_note'] ) : array();
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
                $errors[] = esc_html__( 'Please select a registered applicant/traveler.', 'ifs-travel-erp' );
            }
            if ( empty( $country ) ) {
                $errors[] = esc_html__( 'Destination country is required.', 'ifs-travel-erp' );
            }
            if ( empty( $submission_date ) ) {
                $errors[] = esc_html__( 'Embassy/Consulate submission date is required.', 'ifs-travel-erp' );
            }

            if ( empty( $errors ) ) {
                $data = array(
                    'customer_id'         => $customer_id,
                    'passenger_name'      => $passenger_name,
                    'passport_no'         => $passport_no,
                    'passport_expiry'     => ! empty( $passport_expiry ) ? $passport_expiry : '1970-01-01',
                    'passport_status'     => $passport_status,
                    'agent_id'            => $agent_id,
                    'supplier_id'         => $supplier_id,
                    'country'             => $country,
                    'sponsor_name'        => $sponsor_name,
                    'visa_type'           => $visa_type,
                    'entry_type'          => $entry_type,
                    'tracking_no'         => $tracking_no,
                    'embassy_app_no'      => $embassy_app_no,
                    'issued_visa_no'      => $issued_visa_no,
                    'processing_center'   => $processing_center,
                    'biometric_loc'       => $biometric_loc,
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
                    'paid_amount'         => $paid_amount,
                    'due_amount'          => $due_amount,
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
                );

                if ( $is_edit ) {
                    $wpdb->update( $table_visas, $data, array( 'id' => $id ) );
                    $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Visa portfolio record updated successfully.', 'ifs-travel-erp' ) . '</div>';
                } else {
                    $data['created_at'] = current_time( 'mysql' );
                    $data['created_by'] = get_current_user_id();
                    $wpdb->insert( $table_visas, $data );
                    $id      = $wpdb->insert_id;
                    $is_edit = true;
                    $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . sprintf( esc_html__( 'New Visa application opened (#VSA-%s).', 'ifs-travel-erp' ), str_pad( (string) $id, 5, '0', STR_PAD_LEFT ) ) . '</div>';
                }

                if ( function_exists( 'ifs_terp_log_activity' ) ) {
                    ifs_terp_log_activity( "Processed Visa Dossier #VSA-{$id} for {$country} | Applicant: {$passenger_name}" );
                }
            } else {
                $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> ' . implode( '<br>', $errors ) . '</div>';
            }
        }

        $row = false;
        if ( $is_edit ) {
            $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_visas} WHERE id = %d", $id ) );
        }

        $customers = $wpdb->get_results( "SELECT id, title, full_name, mobile, passport_no, passport_expiry, nationality, photo_url, passport_copy_url FROM {$table_customers} ORDER BY full_name ASC" );
        $agents    = $wpdb->get_results( "SELECT id, agency_name, current_balance FROM {$table_agents} WHERE status = 'Active' ORDER BY agency_name ASC" );
        $suppliers = $wpdb->get_results( "SELECT id, supplier_name, current_balance FROM {$table_suppliers} WHERE status = 'Active' ORDER BY supplier_name ASC" );
        
        $all_requirements = array();
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_reqs}'" ) === $table_reqs ) {
            $all_requirements = $wpdb->get_results( "SELECT * FROM {$table_reqs} ORDER BY country_name ASC" );
        }

        // Field Defaults
        $val_customer      = $is_edit ? absint( $row->customer_id ?? 0 ) : 0;
        $val_pax_name      = $is_edit ? esc_attr( $row->passenger_name ?? '' ) : '';
        $val_passport      = $is_edit ? esc_attr( $row->passport_no ?? '' ) : '';
        $val_pass_exp      = ( $is_edit && ! empty( $row->passport_expiry ) && $row->passport_expiry !== '1970-01-01' ) ? esc_attr( $row->passport_expiry ) : '';
        $val_pass_stat     = $is_edit ? esc_attr( $row->passport_status ?? 'In Office' ) : 'In Office';

        $val_agent         = $is_edit ? absint( $row->agent_id ?? 0 ) : 0;
        $val_supplier      = $is_edit ? absint( $row->supplier_id ?? 0 ) : 0;
        $val_country       = $is_edit ? esc_attr( $row->country ?? 'Saudi Arabia' ) : 'Saudi Arabia';
        $val_sponsor       = $is_edit ? esc_attr( $row->sponsor_name ?? '' ) : '';
        $val_type          = $is_edit ? esc_attr( $row->visa_type ?? 'Tourist Visa' ) : 'Tourist Visa';
        $val_entry         = $is_edit ? esc_attr( $row->entry_type ?? 'Single Entry' ) : 'Single Entry';
        $val_tracking      = $is_edit ? esc_attr( $row->tracking_no ?? '' ) : '';
        $val_embassy_app   = $is_edit ? esc_attr( $row->embassy_app_no ?? '' ) : '';
        $val_issued_no     = $is_edit ? esc_attr( $row->issued_visa_no ?? '' ) : '';
        
        $val_center        = $is_edit ? esc_attr( $row->processing_center ?? 'VFS Global Dhaka' ) : 'VFS Global Dhaka';
        $val_biometric     = $is_edit ? esc_attr( $row->biometric_loc ?? 'VFS Gulshan' ) : 'VFS Gulshan';
        $val_submission    = $is_edit ? esc_attr( $row->submission_date ?? current_time( 'Y-m-d' ) ) : current_time( 'Y-m-d' );
        $val_appointment   = ( $is_edit && ! empty( $row->appointment_date ) && $row->appointment_date !== '1970-01-01' ) ? esc_attr( $row->appointment_date ) : '';
        $val_expected      = ( $is_edit && ! empty( $row->expected_delivery ) && $row->expected_delivery !== '1970-01-01' ) ? esc_attr( $row->expected_delivery ) : gmdate( 'Y-m-d', strtotime( '+7 days' ) );
        $val_issue_date    = ( $is_edit && ! empty( $row->issue_date ) && $row->issue_date !== '1970-01-01' ) ? esc_attr( $row->issue_date ) : '';
        $val_expiry_date   = ( $is_edit && ! empty( $row->expiry_date ) && $row->expiry_date !== '1970-01-01' ) ? esc_attr( $row->expiry_date ) : '';
        $val_validity      = $is_edit ? intval( $row->validity_days ?? 30 ) : 30;
        $val_stay          = $is_edit ? esc_attr( $row->stay_duration ?? '30 Days' ) : '30 Days';
        
        $val_embassy_fee   = $is_edit ? (float) ( $row->embassy_fee ?? 0 ) : '';
        $val_service_fee   = $is_edit ? (float) ( $row->service_fee ?? 0 ) : '';
        $val_buy           = $is_edit ? (float) ( $row->buy_price ?? 0 ) : '';
        $val_sell          = $is_edit ? (float) ( $row->sell_price ?? 0 ) : '';
        $val_paid          = $is_edit ? (float) ( $row->paid_amount ?? 0 ) : '';
        $val_due           = $is_edit ? (float) ( $row->due_amount ?? 0 ) : 0;
        $val_profit        = $is_edit ? (float) ( $row->profit ?? 0 ) : 0;
        
        $val_status        = $is_edit ? esc_attr( $row->status ?? 'Processing' ) : 'Processing';
        $val_pay_status    = $is_edit ? esc_attr( $row->payment_status ?? 'Unpaid' ) : 'Unpaid';
        $val_pay_method    = $is_edit ? esc_attr( $row->payment_method ?? 'Bank Transfer' ) : 'Bank Transfer';
        $val_remarks       = $is_edit ? esc_textarea( $row->remarks ?? '' ) : '';

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
                array( 'name' => 'Original Passport (Min 6 Months Validity)', 'status' => 'Received', 'note' => 'Physical Booklet' ),
                array( 'name' => 'Passport Size Photos (2x2 White BG)', 'status' => 'Received', 'note' => '2 Copies Lab Print' ),
                array( 'name' => 'Bank Statement & Solvency Certificate', 'status' => 'Received', 'note' => 'Last 6 Months Verified' ),
                array( 'name' => 'Trade License / Office NOC / Visiting Card', 'status' => 'Pending', 'note' => 'English Translated & Notarized' ),
                array( 'name' => 'Air Ticket Itinerary & Hotel Booking', 'status' => 'Received', 'note' => 'Confirmed Voucher Attached' ),
            );
        }
        ?>

        <div class="wrap ifs-visa-workspace">
            <?php echo wp_kses_post( $message ); ?>

            <form method="post" action="" id="ifsVisaForm" class="ifs-split-visa-editor">
                <?php wp_nonce_field( 'ifs_visa_save_action', 'ifs_visa_nonce' ); ?>

                <div class="ifs-visa-form-body">
                    
                    <div class="ifs-panel-card">
                        <div class="ifs-card-header">
                            <div class="ifs-step-num"><span class="dashicons dashicons-id-alt"></span></div>
                            <div>
                                <h3 class="ifs-card-title"><?php esc_html_e( 'Applicant, Destination & Classification', 'ifs-travel-erp' ); ?></h3>
                                <p class="ifs-card-desc"><?php esc_html_e( 'Assign traveler portfolio, passport validity checks, and destination entry permission', 'ifs-travel-erp' ); ?></p>
                            </div>
                        </div>

                        <div class="ifs-grid-3">
                            <div class="ifs-field-block col-span-2">
                                <label class="ifs-field-label" for="inp_customer"><?php esc_html_e( 'Applicant / Traveler Portfolio', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                                <div class="ifs-field-wrap">
                                    <select name="customer_id" id="inp_customer" required class="ifs-input-field ifs-select2">
                                        <option value=""><?php esc_html_e( '-- Search Registered Traveler by Name, Mobile, Passport --', 'ifs-travel-erp' ); ?></option>
                                        <?php foreach ( $customers as $cus ) : 
                                            $t_prefix = ! empty( $cus->title ) ? $cus->title . '. ' : '';
                                            $p_num    = ! empty( $cus->passport_no ) ? ' [PPT: ' . $cus->passport_no . ']' : '';
                                        ?>
                                            <option value="<?php echo esc_attr( $cus->id ); ?>" 
                                                    data-name="<?php echo esc_attr( $t_prefix . $cus->full_name ); ?>"
                                                    data-passport="<?php echo esc_attr( $cus->passport_no ?: 'NOT PROVIDED' ); ?>"
                                                    data-expiry="<?php echo esc_attr( $cus->passport_expiry && $cus->passport_expiry !== '1970-01-01' ? $cus->passport_expiry : '' ); ?>"
                                                    data-nation="<?php echo esc_attr( $cus->nationality ?: 'Bangladeshi' ); ?>"
                                                    data-photo="<?php echo esc_url( $cus->photo_url ?: '' ); ?>"
                                                    data-passportscan="<?php echo esc_url( $cus->passport_copy_url ?: '' ); ?>"
                                                    <?php selected( $val_customer, $cus->id ); ?>>
                                                <?php echo esc_html( $t_prefix . $cus->full_name . ' (' . $cus->mobile . ')' . $p_num ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <input type="hidden" name="passenger_name" id="inp_passenger_name_hidden" value="<?php echo esc_attr( $val_pax_name ); ?>">
                                <input type="hidden" name="passport_no" id="inp_passport_no_hidden" value="<?php echo esc_attr( $val_passport ); ?>">
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_agent"><?php esc_html_e( 'B2B Sub-Agent', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <select name="agent_id" id="inp_agent" class="ifs-input-field ifs-select2">
                                        <option value="0"><?php esc_html_e( 'Direct Retail Customer', 'ifs-travel-erp' ); ?></option>
                                        <?php foreach ( $agents as $ag ) : ?>
                                            <option value="<?php echo esc_attr( $ag->id ); ?>" <?php selected( $val_agent, $ag->id ); ?>>
                                                <?php echo esc_html( $ag->agency_name . ' (Bal: ৳' . number_format( (float) $ag->current_balance, 0 ) . ')' ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_passport_expiry"><?php esc_html_e( 'Passport Expiry Date', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="date" name="passport_expiry" id="inp_passport_expiry" value="<?php echo esc_attr( $val_pass_exp ); ?>" class="ifs-input-field font-mono">
                                </div>
                                <span id="passport_validity_warning" style="display:none; color:#dc2626; font-size:10.5px; font-weight:700; margin-top:3px;">
                                    <span class="dashicons dashicons-warning" style="font-size:13px; width:13px; height:13px; vertical-align:middle;"></span> <?php esc_html_e( 'Caution: Less than 6 months validity remaining!', 'ifs-travel-erp' ); ?>
                                </span>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_passport_status"><?php esc_html_e( 'Physical Booklet Custody', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <select name="passport_status" id="inp_passport_status" class="ifs-input-field">
                                        <option value="In Office" <?php selected( $val_pass_stat, 'In Office' ); ?>><?php esc_html_e( 'In Office Vault (Safe)', 'ifs-travel-erp' ); ?></option>
                                        <option value="Submitted to VFS/Embassy" <?php selected( $val_pass_stat, 'Submitted to VFS/Embassy' ); ?>><?php esc_html_e( 'Submitted to Embassy / VFS', 'ifs-travel-erp' ); ?></option>
                                        <option value="Returned to Client" <?php selected( $val_pass_stat, 'Returned to Client' ); ?>><?php esc_html_e( 'Returned to Traveler', 'ifs-travel-erp' ); ?></option>
                                        <option value="E-Visa Only (No Booklet)" <?php selected( $val_pass_stat, 'E-Visa Only (No Booklet)' ); ?>><?php esc_html_e( 'E-Visa (No Physical Book)', 'ifs-travel-erp' ); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_country"><?php esc_html_e( 'Destination Country', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                                <div class="ifs-field-wrap">
                                    <input type="text" name="country" id="inp_country" required value="<?php echo esc_attr( $val_country ); ?>" placeholder="<?php esc_attr_e( 'e.g. Saudi Arabia, UK, Canada', 'ifs-travel-erp' ); ?>" class="ifs-input-field">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_sponsor_name"><?php esc_html_e( 'Sponsor / Inviting Company', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="text" name="sponsor_name" id="inp_sponsor_name" value="<?php echo esc_attr( $val_sponsor ); ?>" placeholder="<?php esc_attr_e( 'e.g. Company Name in Riyadh / Family', 'ifs-travel-erp' ); ?>" class="ifs-input-field">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_visa_type"><?php esc_html_e( 'Visa Category', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                                <div class="ifs-field-wrap">
                                    <select name="visa_type" id="inp_visa_type" class="ifs-input-field">
                                        <option value="Tourist Visa" <?php selected( $val_type, 'Tourist Visa' ); ?>><?php esc_html_e( 'Tourist / Visit Visa', 'ifs-travel-erp' ); ?></option>
                                        <option value="Business Visa" <?php selected( $val_type, 'Business Visa' ); ?>><?php esc_html_e( 'Business Visa', 'ifs-travel-erp' ); ?></option>
                                        <option value="Work / Employment" <?php selected( $val_type, 'Work / Employment' ); ?>><?php esc_html_e( 'Work / Employment Visa', 'ifs-travel-erp' ); ?></option>
                                        <option value="Umrah / E-Visa" <?php selected( $val_type, 'Umrah / E-Visa' ); ?>><?php esc_html_e( 'Umrah / Saudi E-Visa', 'ifs-travel-erp' ); ?></option>
                                        <option value="Student Visa" <?php selected( $val_type, 'Student Visa' ); ?>><?php esc_html_e( 'Student Visa', 'ifs-travel-erp' ); ?></option>
                                        <option value="Medical Visa" <?php selected( $val_type, 'Medical Visa' ); ?>><?php esc_html_e( 'Medical Treatment Visa', 'ifs-travel-erp' ); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_entry_type"><?php esc_html_e( 'Entry Permission', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <select name="entry_type" id="inp_entry_type" class="ifs-input-field">
                                        <option value="Single Entry" <?php selected( $val_entry, 'Single Entry' ); ?>><?php esc_html_e( 'Single Entry', 'ifs-travel-erp' ); ?></option>
                                        <option value="Multiple Entry" <?php selected( $val_entry, 'Multiple Entry' ); ?>><?php esc_html_e( 'Multiple Entry', 'ifs-travel-erp' ); ?></option>
                                        <option value="Double Entry" <?php selected( $val_entry, 'Double Entry' ); ?>><?php esc_html_e( 'Double Entry', 'ifs-travel-erp' ); ?></option>
                                        <option value="Transit Visa" <?php selected( $val_entry, 'Transit Visa' ); ?>><?php esc_html_e( 'Transit Permission', 'ifs-travel-erp' ); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ifs-panel-card">
                        <div class="ifs-card-header">
                            <div class="ifs-step-num"><span class="dashicons dashicons-admin-site-alt3"></span></div>
                            <div>
                                <h3 class="ifs-card-title"><?php esc_html_e( 'Destination Visa Requirements & Advisory', 'ifs-travel-erp' ); ?></h3>
                                <p class="ifs-card-desc"><?php esc_html_e( 'Auto-sync embassy fees, submission channels, and checklist policies from the global directory', 'ifs-travel-erp' ); ?></p>
                            </div>
                        </div>

                        <div class="ifs-grid-3">
                            <div class="ifs-field-block col-span-2">
                                <label class="ifs-field-label" for="ifs_quick_req_preset"><?php esc_html_e( 'Select Configured Country Requirement Policy', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <select id="ifs_quick_req_preset" class="ifs-input-field">
                                        <option value=""><?php esc_html_e( '-- Choose Destination Policy to Auto-Populate --', 'ifs-travel-erp' ); ?></option>
                                        <?php foreach ( $all_requirements as $req ) : 
                                            $decoded_list = ! empty( $req->requirements_list ) ? json_decode( $req->requirements_list, true ) : array();
                                            $json_checklist_attr = esc_attr( wp_json_encode( $decoded_list ) );
                                        ?>
                                            <option value="<?php echo esc_attr( $req->country_name ); ?>"
                                                    data-type="<?php echo esc_attr( $req->visa_type ); ?>"
                                                    data-mode="<?php echo esc_attr( $req->submission_mode ); ?>"
                                                    data-time="<?php echo esc_attr( $req->processing_time ); ?>"
                                                    data-validity="<?php echo esc_attr( $req->stay_validity ); ?>"
                                                    data-embfee="<?php echo esc_attr( $req->embassy_fee ); ?>"
                                                    data-svcfee="<?php echo esc_attr( $req->service_fee ); ?>"
                                                    data-checklist='<?php echo $json_checklist_attr; ?>'>
                                                <?php echo esc_html( $req->country_name . ' (' . $req->visa_type . ') - ৳' . number_format( (float) $req->standard_fee, 0 ) ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="ifs-field-block">
                                <label class="ifs-field-label"><?php esc_html_e( 'Directory Sync Status', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap" style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:8px; padding:0 14px; font-size:12px; font-weight:700; color:#059669; display:flex; align-items:center; gap:6px;">
                                    <span class="dashicons dashicons-yes-alt" style="font-size:16px; width:16px; height:16px;"></span> <?php esc_html_e( 'Active Live Policy Sync', 'ifs-travel-erp' ); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ifs-panel-card">
                        <div class="ifs-card-header">
                            <div class="ifs-step-num"><span class="dashicons dashicons-location-alt"></span></div>
                            <div>
                                <h3 class="ifs-card-title"><?php esc_html_e( 'Timeline, Application Tracking & Stamped Numbers', 'ifs-travel-erp' ); ?></h3>
                                <p class="ifs-card-desc"><?php esc_html_e( 'Embassy appointment dates, VFS application codes, and final stamped visa credentials', 'ifs-travel-erp' ); ?></p>
                            </div>
                        </div>

                        <div class="ifs-grid-3">
                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_center"><?php esc_html_e( 'Processing Center / Embassy', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="text" name="processing_center" id="inp_center" value="<?php echo esc_attr( $val_center ); ?>" placeholder="<?php esc_attr_e( 'e.g. VFS Global / Embassy Counter', 'ifs-travel-erp' ); ?>" class="ifs-input-field">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_biometric_loc"><?php esc_html_e( 'Biometric Center Location', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <select name="biometric_loc" id="inp_biometric_loc" class="ifs-input-field">
                                        <option value="VFS Gulshan" <?php selected( $val_biometric, 'VFS Gulshan' ); ?>><?php esc_html_e( 'VFS Global Gulshan, Dhaka', 'ifs-travel-erp' ); ?></option>
                                        <option value="VFS Jamuna" <?php selected( $val_biometric, 'VFS Jamuna' ); ?>><?php esc_html_e( 'VFS Jamuna Future Park, Dhaka', 'ifs-travel-erp' ); ?></option>
                                        <option value="TLScontact Dhaka" <?php selected( $val_biometric, 'TLScontact Dhaka' ); ?>><?php esc_html_e( 'TLScontact Center, Dhaka', 'ifs-travel-erp' ); ?></option>
                                        <option value="Direct Embassy" <?php selected( $val_biometric, 'Direct Embassy' ); ?>><?php esc_html_e( 'Direct Embassy Interview', 'ifs-travel-erp' ); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_tracking_no"><?php esc_html_e( 'VFS / File Tracking No', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="text" name="tracking_no" id="inp_tracking_no" value="<?php echo esc_attr( $val_tracking ); ?>" placeholder="<?php esc_attr_e( 'e.g. GWF065432 / VFS-9988', 'ifs-travel-erp' ); ?>" class="ifs-input-field uppercase font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_embassy_app"><?php esc_html_e( 'MoFA / Embassy App No', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="text" name="embassy_app_no" id="inp_embassy_app" value="<?php echo esc_attr( $val_embassy_app ); ?>" placeholder="<?php esc_attr_e( 'e.g. MOFA-123456', 'ifs-travel-erp' ); ?>" class="ifs-input-field font-mono uppercase">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_issued_visa_no"><?php esc_html_e( 'Approved Visa Sticker / File No', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="text" name="issued_visa_no" id="inp_issued_visa_no" value="<?php echo esc_attr( $val_issued_no ); ?>" placeholder="<?php esc_attr_e( 'e.g. E-8899201 / VSA-441', 'ifs-travel-erp' ); ?>" class="ifs-input-field font-mono uppercase font-bold color-blue">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_submission_date"><?php esc_html_e( 'Submission Date', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                                <div class="ifs-field-wrap">
                                    <input type="date" name="submission_date" id="inp_submission_date" required value="<?php echo esc_attr( $val_submission ); ?>" class="ifs-input-field">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_appointment_date"><?php esc_html_e( 'Biometric / Interview Date', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="date" name="appointment_date" id="inp_appointment_date" value="<?php echo esc_attr( $val_appointment ); ?>" class="ifs-input-field">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_expected_delivery"><?php esc_html_e( 'Expected Delivery Date', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="date" name="expected_delivery" id="inp_expected_delivery" value="<?php echo esc_attr( $val_expected ); ?>" class="ifs-input-field">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_supplier"><?php esc_html_e( 'Wholesaler / Vendor', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <select name="supplier_id" id="inp_supplier" class="ifs-input-field ifs-select2">
                                        <option value="0"><?php esc_html_e( 'Direct Embassy Submission', 'ifs-travel-erp' ); ?></option>
                                        <?php foreach ( $suppliers as $sup ) : ?>
                                            <option value="<?php echo esc_attr( $sup->id ); ?>" <?php selected( $val_supplier, $sup->id ); ?>>
                                                <?php echo esc_html( $sup->supplier_name . ' (Bal: ৳' . number_format( (float) $sup->current_balance, 0 ) . ')' ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_validity"><?php esc_html_e( 'Validity (Days)', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="number" name="validity_days" id="inp_validity" value="<?php echo esc_attr( $val_validity ); ?>" placeholder="30" class="ifs-input-field font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_status"><?php esc_html_e( 'Current File Lifecycle Status', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <select name="status" id="inp_status" class="ifs-input-field">
                                        <option value="Processing" <?php selected( $val_status, 'Processing' ); ?>><?php esc_html_e( 'Processing (Under Review)', 'ifs-travel-erp' ); ?></option>
                                        <option value="Approved" <?php selected( $val_status, 'Approved' ); ?>><?php esc_html_e( 'Approved / Stamped', 'ifs-travel-erp' ); ?></option>
                                        <option value="Delivered" <?php selected( $val_status, 'Delivered' ); ?>><?php esc_html_e( 'Delivered to Client', 'ifs-travel-erp' ); ?></option>
                                        <option value="Rejected" <?php selected( $val_status, 'Rejected' ); ?>><?php esc_html_e( 'Rejected by Embassy', 'ifs-travel-erp' ); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ifs-panel-card">
                        <div class="ifs-card-header" style="justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 14px;">
                                <div class="ifs-step-num"><span class="dashicons dashicons-clipboard"></span></div>
                                <div>
                                    <h3 class="ifs-card-title"><?php esc_html_e( 'Physical Document Checklist (Dynamic Repeater)', 'ifs-travel-erp' ); ?></h3>
                                    <p class="ifs-card-desc"><?php esc_html_e( 'Track individual documents received, pending items, or embassy submissions', 'ifs-travel-erp' ); ?></p>
                                </div>
                            </div>
                            <button type="button" class="ifs-btn-repeater-add" id="ifsAddDocBtn">
                                <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Item', 'ifs-travel-erp' ); ?>
                            </button>
                        </div>

                        <div class="ifs-repeater-container" id="ifsDocRepeaterWrap">
                            <?php foreach ( $checklist_items as $item ) : ?>
                                <div class="ifs-repeater-row">
                                    <div class="repeater-field flex-2">
                                        <input type="text" name="doc_name[]" value="<?php echo esc_attr( $item['name'] ); ?>" placeholder="<?php esc_attr_e( 'Document Title (e.g. Original Passport)', 'ifs-travel-erp' ); ?>" class="ifs-input-field" required>
                                    </div>
                                    <div class="repeater-field flex-1">
                                        <select name="doc_status[]" class="ifs-input-field">
                                            <option value="Received" <?php selected( $item['status'], 'Received' ); ?>><?php esc_html_e( 'Received (In Office)', 'ifs-travel-erp' ); ?></option>
                                            <option value="Submitted" <?php selected( $item['status'], 'Submitted' ); ?>><?php esc_html_e( 'Submitted to Embassy', 'ifs-travel-erp' ); ?></option>
                                            <option value="Pending" <?php selected( $item['status'], 'Pending' ); ?>><?php esc_html_e( 'Pending from Client', 'ifs-travel-erp' ); ?></option>
                                            <option value="Not Required" <?php selected( $item['status'], 'Not Required' ); ?>><?php esc_html_e( 'Not Required', 'ifs-travel-erp' ); ?></option>
                                        </select>
                                    </div>
                                    <div class="repeater-field flex-2">
                                        <input type="text" name="doc_note[]" value="<?php echo esc_attr( $item['note'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Notes (e.g. 2 copies, notarized)', 'ifs-travel-erp' ); ?>" class="ifs-input-field">
                                    </div>
                                    <button type="button" class="ifs-btn-repeater-del" title="<?php esc_attr_e( 'Remove Document', 'ifs-travel-erp' ); ?>"><span class="dashicons dashicons-trash"></span></button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="ifs-panel-card">
                        <div class="ifs-card-header">
                            <div class="ifs-step-num"><span class="dashicons dashicons-vault"></span></div>
                            <div>
                                <h3 class="ifs-card-title"><?php esc_html_e( 'Digital Document Vault & Media Attachments', 'ifs-travel-erp' ); ?></h3>
                                <p class="ifs-card-desc"><?php esc_html_e( 'Store passenger portrait, passport bio-page scan, stamped visa copy, and embassy letters', 'ifs-travel-erp' ); ?></p>
                            </div>
                        </div>

                        <div class="ifs-vault-grid">
                            <div class="ifs-vault-card">
                                <div class="vault-card-thumb" id="prev_vault_photo">
                                    <?php if ( ! empty( $val_photo_scan ) ) : ?>
                                        <img src="<?php echo esc_url( $val_photo_scan ); ?>" alt="<?php esc_attr_e( 'Applicant Photo', 'ifs-travel-erp' ); ?>" />
                                    <?php else : ?>
                                        <div class="vault-empty-icon"><span class="dashicons dashicons-camera"></span></div>
                                    <?php endif; ?>
                                </div>
                                <div class="vault-card-body">
                                    <div class="vault-item-title"><?php esc_html_e( 'Applicant Photograph', 'ifs-travel-erp' ); ?></div>
                                    <div class="vault-item-meta"><?php esc_html_e( 'Passport size 2x2 white background', 'ifs-travel-erp' ); ?></div>
                                    <input type="hidden" name="photo_url" id="inp_photo_url" value="<?php echo esc_url( $val_photo_scan ); ?>">
                                    <div class="vault-action-row">
                                        <button type="button" class="vault-btn-action" id="ifsUploadPhotoBtn"><span class="dashicons dashicons-upload"></span> <?php echo ! empty( $val_photo_scan ) ? esc_html__( 'Replace', 'ifs-travel-erp' ) : esc_html__( 'Upload', 'ifs-travel-erp' ); ?></button>
                                        <button type="button" class="vault-btn-remove <?php echo empty( $val_photo_scan ) ? 'hide' : ''; ?>" id="ifsRemovePhotoBtn"><span class="dashicons dashicons-trash"></span></button>
                                    </div>
                                </div>
                            </div>

                            <div class="ifs-vault-card">
                                <div class="vault-card-thumb" id="prev_vault_passport">
                                    <?php if ( ! empty( $val_passport_scan ) ) : ?>
                                        <?php if ( preg_match( '/\.(jpg|jpeg|png|webp)$/i', $val_passport_scan ) ) : ?>
                                            <img src="<?php echo esc_url( $val_passport_scan ); ?>" alt="<?php esc_attr_e( 'Passport Scan', 'ifs-travel-erp' ); ?>" />
                                        <?php else : ?>
                                            <div class="vault-pdf-icon"><span class="dashicons dashicons-pdf"></span><small><?php esc_html_e( 'PDF', 'ifs-travel-erp' ); ?></small></div>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <div class="vault-empty-icon"><span class="dashicons dashicons-media-document"></span></div>
                                    <?php endif; ?>
                                </div>
                                <div class="vault-card-body">
                                    <div class="vault-item-title"><?php esc_html_e( 'Passport Bio-Page Scan', 'ifs-travel-erp' ); ?></div>
                                    <div class="vault-item-meta"><?php esc_html_e( 'Color scanned PDF or image', 'ifs-travel-erp' ); ?></div>
                                    <input type="hidden" name="passport_scan_url" id="inp_passport_scan" value="<?php echo esc_url( $val_passport_scan ); ?>">
                                    <div class="vault-action-row">
                                        <button type="button" class="vault-btn-action" id="ifsUploadPassportBtn"><span class="dashicons dashicons-upload"></span> <?php echo ! empty( $val_passport_scan ) ? esc_html__( 'Replace', 'ifs-travel-erp' ) : esc_html__( 'Upload', 'ifs-travel-erp' ); ?></button>
                                        <button type="button" class="vault-btn-remove <?php echo empty( $val_passport_scan ) ? 'hide' : ''; ?>" id="ifsRemovePassportBtn"><span class="dashicons dashicons-trash"></span></button>
                                    </div>
                                </div>
                            </div>

                            <div class="ifs-vault-card">
                                <div class="vault-card-thumb" id="prev_vault_visa">
                                    <?php if ( ! empty( $val_visa_doc ) ) : ?>
                                        <?php if ( preg_match( '/\.(jpg|jpeg|png|webp)$/i', $val_visa_doc ) ) : ?>
                                            <img src="<?php echo esc_url( $val_visa_doc ); ?>" alt="<?php esc_attr_e( 'Visa Document', 'ifs-travel-erp' ); ?>" />
                                        <?php else : ?>
                                            <div class="vault-pdf-icon"><span class="dashicons dashicons-pdf"></span><small><?php esc_html_e( 'PDF', 'ifs-travel-erp' ); ?></small></div>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <div class="vault-empty-icon"><span class="dashicons dashicons-id-alt"></span></div>
                                    <?php endif; ?>
                                </div>
                                <div class="vault-card-body">
                                    <div class="vault-item-title"><?php esc_html_e( 'Approved Visa Copy', 'ifs-travel-erp' ); ?></div>
                                    <div class="vault-item-meta"><?php esc_html_e( 'Stamped sticker or issued E-Visa PDF', 'ifs-travel-erp' ); ?></div>
                                    <input type="hidden" name="visa_doc_url" id="inp_visa_doc" value="<?php echo esc_url( $val_visa_doc ); ?>">
                                    <div class="vault-action-row">
                                        <button type="button" class="vault-btn-action" id="ifsUploadVisaBtn"><span class="dashicons dashicons-upload"></span> <?php echo ! empty( $val_visa_doc ) ? esc_html__( 'Replace', 'ifs-travel-erp' ) : esc_html__( 'Upload', 'ifs-travel-erp' ); ?></button>
                                        <button type="button" class="vault-btn-remove <?php echo empty( $val_visa_doc ) ? 'hide' : ''; ?>" id="ifsRemoveVisaBtn"><span class="dashicons dashicons-trash"></span></button>
                                    </div>
                                </div>
                            </div>

                            <div class="ifs-vault-card">
                                <div class="vault-card-thumb" id="prev_vault_support">
                                    <?php if ( ! empty( $val_support_doc ) ) : ?>
                                        <?php if ( preg_match( '/\.(jpg|jpeg|png|webp)$/i', $val_support_doc ) ) : ?>
                                            <img src="<?php echo esc_url( $val_support_doc ); ?>" alt="<?php esc_attr_e( 'Supporting Document', 'ifs-travel-erp' ); ?>" />
                                        <?php else : ?>
                                            <div class="vault-pdf-icon"><span class="dashicons dashicons-pdf"></span><small><?php esc_html_e( 'PDF', 'ifs-travel-erp' ); ?></small></div>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <div class="vault-empty-icon"><span class="dashicons dashicons-portfolio"></span></div>
                                    <?php endif; ?>
                                </div>
                                <div class="vault-card-body">
                                    <div class="vault-item-title"><?php esc_html_e( 'Embassy Support Packet', 'ifs-travel-erp' ); ?></div>
                                    <div class="vault-item-meta"><?php esc_html_e( 'NOC, Solvency & Insurance files', 'ifs-travel-erp' ); ?></div>
                                    <input type="hidden" name="supporting_doc_url" id="inp_supporting_doc" value="<?php echo esc_url( $val_support_doc ); ?>">
                                    <div class="vault-action-row">
                                        <button type="button" class="vault-btn-action" id="ifsUploadSupportBtn"><span class="dashicons dashicons-upload"></span> <?php echo ! empty( $val_support_doc ) ? esc_html__( 'Replace', 'ifs-travel-erp' ) : esc_html__( 'Upload', 'ifs-travel-erp' ); ?></button>
                                        <button type="button" class="vault-btn-remove <?php echo empty( $val_support_doc ) ? 'hide' : ''; ?>" id="ifsRemoveSupportBtn"><span class="dashicons dashicons-trash"></span></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ifs-panel-card">
                        <div class="ifs-card-header">
                            <div class="ifs-step-num"><span class="dashicons dashicons-money-alt"></span></div>
                            <div>
                                <h3 class="ifs-card-title"><?php esc_html_e( 'Embassy Fees, Invoicing & Due Collection', 'ifs-travel-erp' ); ?></h3>
                                <p class="ifs-card-desc"><?php esc_html_e( 'Supplier cost, client selling fare, paid collection, live calculated balance due & net profit', 'ifs-travel-erp' ); ?></p>
                            </div>
                        </div>

                        <div class="ifs-grid-3">
                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="vsa_buy"><?php esc_html_e( 'Embassy / Supplier Cost (৳)', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                                <div class="ifs-field-wrap">
                                    <input type="number" step="0.01" name="buy_price" id="vsa_buy" required value="<?php echo esc_attr( $val_buy ); ?>" placeholder="0.00" class="ifs-input-field font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="vsa_sell"><?php esc_html_e( 'Client Total Invoiced Fee (৳)', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                                <div class="ifs-field-wrap">
                                    <input type="number" step="0.01" name="sell_price" id="vsa_sell" required value="<?php echo esc_attr( $val_sell ); ?>" placeholder="0.00" class="ifs-input-field font-mono font-bold color-blue">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label"><?php esc_html_e( 'Net Profit Margin (৳)', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="text" id="vsa_profit" readonly value="<?php echo esc_attr( number_format( (float) $val_profit, 2 ) ); ?>" class="ifs-input-field font-mono font-bold <?php echo ( $val_profit >= 0 ) ? 'profit-positive' : 'profit-negative'; ?>">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="vsa_paid"><?php esc_html_e( 'Paid Amount (৳)', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="number" step="0.01" name="paid_amount" id="vsa_paid" value="<?php echo esc_attr( $val_paid ); ?>" placeholder="0.00" class="ifs-input-field font-mono font-bold">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label"><?php esc_html_e( 'Remaining Balance Due (৳)', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="text" id="vsa_due" readonly value="<?php echo esc_attr( number_format( (float) $val_due, 2 ) ); ?>" class="ifs-input-field font-mono font-bold <?php echo $val_due > 0 ? 'color-rose' : 'color-emerald'; ?>">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_pay_status"><?php esc_html_e( 'Payment Status', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <select name="payment_status" id="inp_pay_status" class="ifs-input-field">
                                        <option value="Paid" <?php selected( $val_pay_status, 'Paid' ); ?>><?php esc_html_e( 'Fully Paid', 'ifs-travel-erp' ); ?></option>
                                        <option value="Partial" <?php selected( $val_pay_status, 'Partial' ); ?>><?php esc_html_e( 'Partially Paid', 'ifs-travel-erp' ); ?></option>
                                        <option value="Unpaid" <?php selected( $val_pay_status, 'Unpaid' ); ?>><?php esc_html_e( 'Unpaid / Due', 'ifs-travel-erp' ); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_pay_method"><?php esc_html_e( 'Payment Method', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <select name="payment_method" id="inp_pay_method" class="ifs-input-field">
                                        <option value="Bank Transfer" <?php selected( $val_pay_method, 'Bank Transfer' ); ?>><?php esc_html_e( 'Bank Transfer', 'ifs-travel-erp' ); ?></option>
                                        <option value="Cash" <?php selected( $val_pay_method, 'Cash' ); ?>><?php esc_html_e( 'Cash at Counter', 'ifs-travel-erp' ); ?></option>
                                        <option value="bKash / MFS" <?php selected( $val_pay_method, 'bKash / MFS' ); ?>><?php esc_html_e( 'bKash / Nagad', 'ifs-travel-erp' ); ?></option>
                                        <option value="Agent Deposit" <?php selected( $val_pay_method, 'Agent Deposit' ); ?>><?php esc_html_e( 'Agent Credit Balance', 'ifs-travel-erp' ); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block col-span-2">
                                <label class="ifs-field-label" for="inp_remarks"><?php esc_html_e( 'Special Remarks & Case Notes', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="text" name="remarks" id="inp_remarks" value="<?php echo esc_attr( $val_remarks ); ?>" placeholder="<?php esc_attr_e( 'e.g. Biometrics scheduled at VFS Gulshan, urgent file...', 'ifs-travel-erp' ); ?>" class="ifs-input-field">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ifs-action-strip">
                        <a href="<?php echo esc_url( $base_url . '&sub=list' ); ?>" class="ifs-btn-back">
                            <span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'Back to Applications', 'ifs-travel-erp' ); ?>
                        </a>
                        <button type="submit" name="ifs_visa_submit" class="ifs-btn-primary">
                            <span class="dashicons dashicons-saved"></span> 
                            <?php echo $is_edit ? esc_html__( 'Update Visa Application', 'ifs-travel-erp' ) : esc_html__( 'Open Visa Processing File', 'ifs-travel-erp' ); ?>
                        </button>
                    </div>
                </div>

                <div class="ifs-preview-sidebar">
                    <div class="ifs-preview-sticky">
                        
                        <div class="ifs-card-preview-header">
                            <div class="preview-header-left">
                                <span class="pulse-beacon"></span>
                                <span><?php esc_html_e( 'Live Visa Dossier Preview', 'ifs-travel-erp' ); ?></span>
                            </div>
                            <span class="preview-secure-tag"><span class="dashicons dashicons-shield"></span> <?php esc_html_e( 'IMMIGRATION', 'ifs-travel-erp' ); ?></span>
                        </div>

                        <div class="ifs-visa-card">
                            <div class="visa-head-strip">
                                <div class="visa-country-tag">
                                    <span class="dashicons dashicons-admin-site-alt3"></span>
                                    <span id="prev_country"><?php echo esc_html( strtoupper( $val_country ) ); ?></span>
                                </div>
                                <span class="visa-type-badge" id="prev_type"><?php echo esc_html( strtoupper( $val_type ) ); ?></span>
                            </div>

                            <div class="visa-applicant-hero">
                                <div class="visa-avatar-wrap">
                                    <div class="visa-avatar" id="prev_avatar">
                                        <?php if ( ! empty( $val_photo_scan ) ) : ?>
                                            <img src="<?php echo esc_url( $val_photo_scan ); ?>" alt="<?php esc_attr_e( 'Applicant', 'ifs-travel-erp' ); ?>" />
                                        <?php else : ?>
                                            <span>VA</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="visa-entry-pill" id="prev_entry_badge"><?php echo ( 'Single Entry' === $val_entry ) ? 'SGL' : ( ( 'Multiple Entry' === $val_entry ) ? 'MULT' : 'DBL' ); ?></div>
                                </div>
                                <div class="visa-applicant-details">
                                    <h4 class="visa-name" id="prev_name"><?php echo esc_html( ! empty( $val_pax_name ) ? strtoupper( $val_pax_name ) : 'MOHAMMED RAHIM' ); ?></h4>
                                    <div class="visa-submeta" id="prev_meta"><?php echo esc_html( 'PPT: ' . ( ! empty( $val_passport ) ? strtoupper( $val_passport ) : 'NOT SET' ) . ' • BANGLADESHI' ); ?></div>
                                </div>
                            </div>

                            <div class="visa-grid-specs font-mono">
                                <div class="spec-cell">
                                    <span class="visa-lbl"><?php esc_html_e( 'TRACKING / VFS REF', 'ifs-travel-erp' ); ?></span>
                                    <strong class="visa-val color-cyan" id="prev_tracking"><?php echo esc_html( $val_tracking ?: 'PENDING' ); ?></strong>
                                </div>
                                <div class="spec-cell">
                                    <span class="visa-lbl"><?php esc_html_e( 'ENTRY PERMISSION', 'ifs-travel-erp' ); ?></span>
                                    <strong class="visa-val" id="prev_entry"><?php echo esc_html( strtoupper( $val_entry ) ); ?></strong>
                                </div>
                                <div class="spec-cell">
                                    <span class="visa-lbl"><?php esc_html_e( 'SUBMISSION DATE', 'ifs-travel-erp' ); ?></span>
                                    <strong class="visa-val" id="prev_sub_date"><?php echo esc_html( date_i18n( 'd M Y', strtotime( $val_submission ) ) ); ?></strong>
                                </div>
                                <div class="spec-cell">
                                    <span class="visa-lbl"><?php esc_html_e( 'EST. DELIVERY DATE', 'ifs-travel-erp' ); ?></span>
                                    <strong class="visa-val color-green" id="prev_exp_date"><?php echo esc_html( date_i18n( 'd M Y', strtotime( $val_expected ) ) ); ?></strong>
                                </div>
                                <div class="spec-cell">
                                    <span class="visa-lbl"><?php esc_html_e( 'STAY VALIDITY', 'ifs-travel-erp' ); ?></span>
                                    <strong class="visa-val" id="prev_stay_val"><?php echo esc_html( $val_validity . ' DAYS' ); ?></strong>
                                </div>
                                <div class="spec-cell">
                                    <span class="visa-lbl"><?php esc_html_e( 'TOTAL INVOICED', 'ifs-travel-erp' ); ?></span>
                                    <strong class="visa-val color-green" id="prev_sell">৳<?php echo esc_html( number_format( (float) ( $val_sell ?: 0 ), 2 ) ); ?></strong>
                                </div>
                            </div>

                            <div class="visa-mrv-zone font-mono">
                                <div class="mrv-line" id="prev_mrv_1">V&lt;BGD&lt;&lt;MOHAMMED&lt;&lt;RAHIM&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;</div>
                                <div class="mrv-line" id="prev_mrv_2">A000000000BGD0000000M0000000&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;00</div>
                            </div>

                            <div class="visa-fee-footer">
                                <div class="visa-barcode-lines"></div>
                                <span class="visa-barcode-txt font-mono"><?php esc_html_e( 'IATCI • EMBASSY VISA DOSSIER VALIDATED', 'ifs-travel-erp' ); ?></span>
                            </div>
                        </div>

                        <div class="ifs-intel-box">
                            <div class="intel-head"><span class="dashicons dashicons-chart-line"></span> <?php esc_html_e( 'Real-Time Profit Yield', 'ifs-travel-erp' ); ?></div>
                            <div class="intel-body">
                                <div class="intel-row">
                                    <span><?php esc_html_e( 'Gross Profit Margin:', 'ifs-travel-erp' ); ?></span>
                                    <strong id="intel_profit" class="<?php echo ( $val_profit >= 0 ) ? 'color-green' : 'color-rose'; ?>">৳<?php echo esc_html( number_format( (float) $val_profit, 2 ) ); ?></strong>
                                </div>
                                <div class="intel-row">
                                    <span><?php esc_html_e( 'Agency Yield Ratio:', 'ifs-travel-erp' ); ?></span>
                                    <strong id="intel_ratio"><?php echo esc_html( ( $val_sell > 0 ? number_format( ( ( $val_profit / $val_sell ) * 100 ), 1 ) : '0.0' ) . '%' ); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <style>
            .ifs-visa-workspace { 
                max-width: 1420px; 
                margin: 20px auto; 
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; 
                color: #0f172a; 
                box-sizing: border-box; 
            }
            .ifs-visa-workspace *, 
            .ifs-visa-workspace *::before, 
            .ifs-visa-workspace *::after { 
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

            .ifs-split-visa-editor { display: grid; grid-template-columns: 1fr 410px; gap: 28px; align-items: flex-start; }
            @media (max-width: 1180px) { .ifs-split-visa-editor { grid-template-columns: 1fr; } }

            .ifs-panel-card { 
                background: #ffffff; 
                border: 1px solid #e2e8f0; 
                border-radius: 14px; 
                padding: 26px; 
                margin-bottom: 22px; 
            }
            .ifs-card-header { 
                display: flex; 
                align-items: center; 
                gap: 14px; 
                margin-bottom: 22px; 
                padding-bottom: 14px; 
                border-bottom: 1px solid #f1f5f9; 
            }
            .ifs-step-num { 
                width: 36px; 
                height: 36px; 
                border-radius: 9px; 
                background: #003376; 
                color: #ffffff; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                flex-shrink: 0; 
            }
            .ifs-step-num .dashicons { font-size: 18px; width: 18px; height: 18px; }
            .ifs-card-title { margin: 0; font-size: 15.5px; font-weight: 800; color: #0f172a; }
            .ifs-card-desc { margin: 2px 0 0 0; font-size: 12.5px; color: #64748b; }

            .ifs-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
            .col-span-2 { grid-column: span 2; }
            .col-span-3 { grid-column: span 3; }
            @media (max-width: 768px) { .ifs-grid-3 { grid-template-columns: 1fr; } .col-span-2, .col-span-3 { grid-column: span 1; } }

            .ifs-field-block { 
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
                letter-spacing: 0.5px; 
                line-height: 1.2; 
            }
            .ifs-field-label .req { color: #e11d48; margin-left: 2px; }

            .ifs-field-wrap { 
                position: relative; 
                display: flex; 
                align-items: center; 
                width: 100%; 
                height: 42px; 
            }

            .ifs-input-field { 
                width: 100% !important; 
                height: 42px !important; 
                max-height: 42px !important; 
                min-height: 42px !important; 
                line-height: 40px !important; 
                padding: 0 14px !important; 
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
                transition: opacity 0.2s ease; 
            }
            input[type="date"].ifs-input-field::-webkit-calendar-picker-indicator:hover { 
                opacity: 1; 
            }

            .ifs-input-field:focus { 
                border-color: #003376 !important; 
                background-color: #f8fafc !important; 
            }

            /* Select2 Flat Layout (Height: 42px) */
            .select2-container { width: 100% !important; }
            .select2-container .select2-selection--single { 
                height: 42px !important; 
                border: 1px solid #cbd5e1 !important; 
                border-radius: 8px !important; 
                padding: 0 12px !important; 
                display: flex !important; 
                align-items: center !important; 
                background: #ffffff !important; 
                outline: none !important; 
                transition: border-color 0.2s ease, background-color 0.2s ease; 
            }
            .select2-container--default .select2-selection--single .select2-selection__rendered { 
                color: #0f172a !important; 
                font-size: 13.5px !important; 
                line-height: 40px !important; 
                padding-left: 0 !important; 
            }
            .select2-container--default .select2-selection--single .select2-selection__arrow { 
                height: 40px !important; 
                right: 10px !important; 
            }
            .select2-container--default.select2-container--focus .select2-selection--single { 
                border-color: #003376 !important; 
                background-color: #f8fafc !important; 
            }
            .select2-dropdown { 
                border: 1px solid #cbd5e1 !important; 
                border-radius: 8px !important; 
                font-size: 13.5px !important; 
                z-index: 99999 !important; 
            }
            .select2-container--default .select2-results__option--highlighted[aria-selected] { 
                background-color: #003376 !important; 
                color: #ffffff !important; 
            }

            .uppercase { text-transform: uppercase; }
            .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important; }
            .font-bold { font-weight: 700; }
            .color-blue { color: #003376 !important; }
            .color-rose { color: #dc2626 !important; }
            .color-emerald { color: #059669 !important; }

            .profit-positive { background: #f0fdf4 !important; color: #166534 !important; border-color: #bbf7d0 !important; }
            .profit-negative { background: #fef2f2 !important; color: #dc2626 !important; border-color: #fecaca !important; }

            /* Dynamic Checklist Repeater */
            .ifs-btn-repeater-add { 
                background: #eff6ff; 
                color: #0284c7; 
                border: 1px solid #bae6fd; 
                height: 38px; 
                padding: 0 16px; 
                border-radius: 8px; 
                font-size: 12.5px; 
                font-weight: 700; 
                cursor: pointer; 
                display: inline-flex; 
                align-items: center; 
                gap: 6px; 
                transition: background-color 0.2s ease, color 0.2s ease; 
            }
            .ifs-btn-repeater-add:hover { background: #0284c7; color: #ffffff; }
            .ifs-repeater-container { display: flex; flex-direction: column; gap: 10px; }
            .ifs-repeater-row { 
                display: flex; 
                align-items: center; 
                gap: 12px; 
                background: #f8fafc; 
                border: 1px solid #e2e8f0; 
                padding: 12px; 
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
                width: 42px; 
                height: 42px; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                cursor: pointer; 
                transition: background-color 0.2s ease, color 0.2s ease; 
                flex-shrink: 0; 
            }
            .ifs-btn-repeater-del:hover { background: #dc2626; color: #ffffff; }

            /* Digital Vault Previews */
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
                transition: border-color 0.2s ease; 
            }
            .ifs-vault-card:hover { 
                border-color: #cbd5e1; 
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
                height: 32px; 
                flex: 1; 
                background: #ffffff; 
                border: 1px solid #cbd5e1; 
                padding: 0 10px; 
                border-radius: 6px; 
                font-size: 11.5px; 
                font-weight: 700; 
                color: #334155; 
                cursor: pointer; 
                display: inline-flex; 
                align-items: center; 
                justify-content: center; 
                gap: 4px; 
                transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease; 
            }
            .vault-btn-action:hover { background: #003376; color: #ffffff; border-color: #003376; }
            .vault-btn-remove { 
                background: #fee2e2; 
                color: #b91c1c; 
                border: 1px solid #fecaca; 
                border-radius: 6px; 
                width: 32px; 
                height: 32px; 
                display: inline-flex; 
                align-items: center; 
                justify-content: center; 
                cursor: pointer; 
                transition: background-color 0.2s ease, color 0.2s ease; 
            }
            .vault-btn-remove:hover { background: #dc2626; color: #ffffff; }
            .vault-btn-remove.hide { display: none !important; }

            /* Action Strip */
            .ifs-action-strip { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; }
            .ifs-btn-back { color: #64748b; text-decoration: none; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; transition: color 0.2s ease; }
            .ifs-btn-back:hover { color: #0f172a; }
            .ifs-btn-primary { 
                background: #003376; 
                color: #ffffff !important; 
                border: none; 
                height: 42px; 
                padding: 0 24px; 
                border-radius: 8px; 
                font-size: 13.5px; 
                font-weight: 700; 
                cursor: pointer; 
                display: inline-flex; 
                align-items: center; 
                gap: 8px; 
                transition: background-color 0.2s ease; 
            }
            .ifs-btn-primary:hover { background: #0284c7; }

            /* Preview Dossier Sidebar */
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
                background: #00224f; 
                border-radius: 18px; 
                padding: 22px; 
                color: #ffffff; 
                position: relative; 
                overflow: hidden; 
                border: 1px solid #1e3a8a; 
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

            .visa-mrv-zone { 
                background: rgba(0, 0, 0, 0.35); 
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

            .ifs-intel-box { 
                background: #ffffff; 
                border: 1px solid #e2e8f0; 
                border-radius: 12px; 
                padding: 16px; 
                margin-top: 18px; 
            }
            .intel-head { font-size: 12px; font-weight: 800; color: #003376; text-transform: uppercase; letter-spacing: 0.4px; display: flex; align-items: center; gap: 6px; margin-bottom: 10px; }
            .intel-head .dashicons { color: #0284c7; font-size: 16px; width: 16px; height: 16px; }
            .intel-body { display: flex; flex-direction: column; gap: 6px; }
            .intel-row { display: flex; justify-content: space-between; font-size: 12.5px; color: #475569; }
            .intel-row strong { font-weight: 800; font-size: 13.5px; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Select2 Initializer
            if ($.fn.select2) {
                $('.ifs-select2').select2({ width: '100%', allowClear: false });
            }

            const inpCustomer   = $('#inp_customer');
            const hiddenPaxName = $('#inp_passenger_name_hidden');
            const hiddenPassNo  = $('#inp_passport_no_hidden');
            const inpPassExp    = $('#inp_passport_expiry');
            const passWarning   = $('#passport_validity_warning');

            const inpCountry    = $('#inp_country');
            const inpVisaType   = $('#inp_visa_type');
            const inpEntryType  = $('#inp_entry_type');
            const inpTracking   = $('#inp_tracking_no');
            const inpSubDate    = $('#inp_submission_date');
            const inpExpDate    = $('#inp_expected_delivery');
            const inpValidity   = $('#inp_validity');
            const inpBuy        = $('#vsa_buy');
            const inpSell       = $('#vsa_sell');
            const inpPaid       = $('#vsa_paid');
            const dueDisplay    = $('#vsa_due');
            const payStatus     = $('#inp_pay_status');
            const quickPreset   = $('#ifs_quick_req_preset');

            const prevCountry   = $('#prev_country');
            const prevType      = $('#prev_type');
            const prevAvatar    = $('#prev_avatar');
            const prevName      = $('#prev_name');
            const prevMeta      = $('#prev_meta');
            const prevTracking  = $('#prev_tracking');
            const prevEntry     = $('#prev_entry');
            const prevEntryBdg  = $('#prev_entry_badge');
            const prevSubDate   = $('#prev_sub_date');
            const prevExpDate   = $('#prev_exp_date');
            const prevStayVal   = $('#prev_stay_val');
            const prevSell      = $('#prev_sell');
            const prevMrv1      = $('#prev_mrv_1');
            const prevMrv2      = $('#prev_mrv_2');

            const profitDisplay = $('#vsa_profit');
            const intelProfit   = $('#intel_profit');
            const intelRatio    = $('#intel_ratio');

            function checkPassportValidity(expiryVal) {
                if (!expiryVal) {
                    passWarning.hide();
                    return;
                }
                const exp = new Date(expiryVal);
                const now = new Date();
                const sixMonthsAhead = new Date();
                sixMonthsAhead.setMonth(now.getMonth() + 6);

                if (exp < sixMonthsAhead) {
                    passWarning.show();
                } else {
                    passWarning.hide();
                }
            }

            // Requirements Preset Auto-Sync Engine
            quickPreset.on('change', function() {
                const opt = $(this).find(':selected');
                if (opt.val()) {
                    const countryName = opt.val();
                    const vType = opt.data('type');
                    const vEmbFee = parseFloat(opt.data('embfee')) || 0;
                    const vSvcFee = parseFloat(opt.data('svcfee')) || 0;
                    const checklistArr = opt.data('checklist');

                    inpCountry.val(countryName);
                    if (vType) inpVisaType.val(vType);
                    inpBuy.val(vEmbFee);
                    inpSell.val(vEmbFee + vSvcFee);

                    if (Array.isArray(checklistArr) && checklistArr.length > 0) {
                        $('#ifsDocRepeaterWrap').empty();
                        checklistArr.forEach(function(docName) {
                            const rowHtml = `
                                <div class="ifs-repeater-row">
                                    <div class="repeater-field flex-2">
                                        <input type="text" name="doc_name[]" value="${docName}" placeholder="<?php echo esc_js( __( 'Document Title', 'ifs-travel-erp' ) ); ?>" class="ifs-input-field" required>
                                    </div>
                                    <div class="repeater-field flex-1">
                                        <select name="doc_status[]" class="ifs-input-field">
                                            <option value="Received"><?php echo esc_js( __( 'Received (In Office)', 'ifs-travel-erp' ) ); ?></option>
                                            <option value="Submitted"><?php echo esc_js( __( 'Submitted to Embassy', 'ifs-travel-erp' ) ); ?></option>
                                            <option value="Pending" selected><?php echo esc_js( __( 'Pending from Client', 'ifs-travel-erp' ) ); ?></option>
                                            <option value="Not Required"><?php echo esc_js( __( 'Not Required', 'ifs-travel-erp' ) ); ?></option>
                                        </select>
                                    </div>
                                    <div class="repeater-field flex-2">
                                        <input type="text" name="doc_note[]" value="<?php echo esc_js( __( 'Configured per country policy', 'ifs-travel-erp' ) ); ?>" placeholder="<?php echo esc_js( __( 'Notes', 'ifs-travel-erp' ) ); ?>" class="ifs-input-field">
                                    </div>
                                    <button type="button" class="ifs-btn-repeater-del" title="<?php echo esc_js( __( 'Remove Document', 'ifs-travel-erp' ) ); ?>"><span class="dashicons dashicons-trash"></span></button>
                                </div>
                            `;
                            $('#ifsDocRepeaterWrap').append(rowHtml);
                        });
                    }
                    updateVisaCard();
                }
            });

            function updateVisaCard() {
                if (prevCountry.length) prevCountry.text(inpCountry.val().trim() ? inpCountry.val().trim().toUpperCase() : 'DESTINATION');
                if (prevType.length)    prevType.text(inpVisaType.val().toUpperCase());
                
                const entryVal = inpEntryType.val() || 'Single Entry';
                if (prevEntry.length)    prevEntry.text(entryVal.toUpperCase());
                if (prevEntryBdg.length) prevEntryBdg.text(entryVal === 'Single Entry' ? 'SGL' : (entryVal === 'Multiple Entry' ? 'MULT' : 'DBL'));
                
                const trkVal = inpTracking.val().trim() ? inpTracking.val().trim().toUpperCase() : 'PENDING';
                if (prevTracking.length) prevTracking.text(trkVal);

                let paxStr  = 'MOHAMMED RAHIM';
                let passStr = 'NOT SET';
                let natStr  = 'BANGLADESHI';

                // Customer Resolution
                const selectedOpt = inpCustomer.find(':selected');
                if (selectedOpt.val() && selectedOpt.val() !== '') {
                    paxStr  = selectedOpt.attr('data-name') || 'MOHAMMED RAHIM';
                    passStr = selectedOpt.attr('data-passport') || 'NOT SET';
                    natStr  = selectedOpt.attr('data-nation') || 'BANGLADESHI';
                    const expVal = selectedOpt.attr('data-expiry') || '';

                    hiddenPaxName.val(paxStr);
                    hiddenPassNo.val(passStr);

                    if (!inpPassExp.val() && expVal) {
                        inpPassExp.val(expVal);
                        checkPassportValidity(expVal);
                    }

                    const photoUrl = selectedOpt.attr('data-photo');
                    if (photoUrl && prevAvatar.length) {
                        prevAvatar.html('<img src="' + photoUrl + '" alt="<?php echo esc_js( __( 'Applicant', 'ifs-travel-erp' ) ); ?>" />');
                    } else if (prevAvatar.length) {
                        const parts = paxStr.trim().split(' ');
                        const inits = parts.length > 1 ? (parts[0][0] + parts[parts.length-1][0]) : parts[0].slice(0, 2);
                        prevAvatar.html('<span>' + inits.toUpperCase() + '</span>');
                    }
                } else if (prevAvatar.length) {
                    prevAvatar.html('<span>VA</span>');
                }

                if (prevName.length) prevName.text(paxStr.toUpperCase());
                if (prevMeta.length) prevMeta.text('PPT: ' + passStr.toUpperCase() + ' • ' + natStr.toUpperCase());

                // Dates Format
                if (inpSubDate.val()) {
                    const d = new Date(inpSubDate.val());
                    if (prevSubDate.length && !isNaN(d)) prevSubDate.text(d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }).toUpperCase());
                }
                if (inpExpDate.val()) {
                    const d = new Date(inpExpDate.val());
                    if (prevExpDate.length && !isNaN(d)) prevExpDate.text(d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }).toUpperCase());
                }

                if (prevStayVal.length) {
                    prevStayVal.text((inpValidity.val() || 30) + ' DAYS');
                }

                // Financial Calculations
                const buyVal  = parseFloat(inpBuy.val()) || 0;
                const sellVal = parseFloat(inpSell.val()) || 0;
                const paidVal = parseFloat(inpPaid.val()) || 0;
                
                const profit  = sellVal - buyVal;
                const due     = Math.max(0, sellVal - paidVal);
                const ratio   = sellVal > 0 ? ((profit / sellVal) * 100).toFixed(1) : '0.0';

                if (prevSell.length) prevSell.text('৳' + sellVal.toLocaleString('en-US', { minimumFractionDigits: 2 }));
                
                dueDisplay.val(due.toFixed(2));
                dueDisplay.css('color', due > 0 ? '#dc2626' : '#059669');

                if (profitDisplay.length) {
                    profitDisplay.val(profit.toFixed(2));
                    profitDisplay.attr('class', 'ifs-input-field font-mono font-bold ' + (profit >= 0 ? 'profit-positive' : 'profit-negative'));
                }

                if (intelProfit.length) {
                    intelProfit.text('৳' + profit.toLocaleString('en-US', { minimumFractionDigits: 2 }));
                    intelProfit.attr('class', profit >= 0 ? 'color-green' : 'color-rose');
                }
                if (intelRatio.length) intelRatio.text(ratio + '%');

                // Auto-align payment status
                if (sellVal > 0 && payStatus.length) {
                    if (paidVal >= sellVal) {
                        payStatus.val('Paid');
                    } else if (paidVal > 0 && paidVal < sellVal) {
                        payStatus.val('Partial');
                    } else {
                        payStatus.val('Unpaid');
                    }
                }

                // MRV Line Generator
                const partsPax = paxStr.replace(/[^A-Za-z ]/g, '').trim().split(' ');
                const mrvSur   = partsPax.length > 1 ? partsPax[partsPax.length - 1].toUpperCase() : 'APPLICANT';
                const mrvGiv   = partsPax.length > 1 ? partsPax[0].toUpperCase() : 'NAME';
                
                let l1 = 'V<BGD' + mrvSur + '<<' + mrvGiv;
                while (l1.length < 44) { l1 += '<'; }
                if (l1.length > 44) l1 = l1.substring(0, 44);

                let l2 = passStr.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
                while (l2.length < 9) { l2 += '<'; }
                l2 += '0BGD0000000M0000000<<<<<<<<<<<<<<00';
                if (l2.length > 44) l2 = l2.substring(0, 44);

                if (prevMrv1.length) prevMrv1.text(l1);
                if (prevMrv2.length) prevMrv2.text(l2);
            }

            inpPassExp.on('change input', function() {
                checkPassportValidity($(this).val());
            });

            $(document).on('input change', '#inp_customer, #inp_country, #inp_visa_type, #inp_entry_type, #inp_tracking_no, #inp_submission_date, #inp_expected_delivery, #inp_validity, #vsa_buy, #vsa_sell, #vsa_paid', updateVisaCard);

            updateVisaCard();

            // Dynamic Document Checklist Repeater Engine
            $('#ifsAddDocBtn').on('click', function(e) {
                e.preventDefault();
                const rowHtml = `
                    <div class="ifs-repeater-row">
                        <div class="repeater-field flex-2">
                            <input type="text" name="doc_name[]" placeholder="<?php echo esc_js( __( 'Document Title (e.g. NOC Letter)', 'ifs-travel-erp' ) ); ?>" class="ifs-input-field" required>
                        </div>
                        <div class="repeater-field flex-1">
                            <select name="doc_status[]" class="ifs-input-field">
                                <option value="Received"><?php echo esc_js( __( 'Received (In Office)', 'ifs-travel-erp' ) ); ?></option>
                                <option value="Submitted"><?php echo esc_js( __( 'Submitted to Embassy', 'ifs-travel-erp' ) ); ?></option>
                                <option value="Pending" selected><?php echo esc_js( __( 'Pending from Client', 'ifs-travel-erp' ) ); ?></option>
                                <option value="Not Required"><?php echo esc_js( __( 'Not Required', 'ifs-travel-erp' ) ); ?></option>
                            </select>
                        </div>
                        <div class="repeater-field flex-2">
                            <input type="text" name="doc_note[]" placeholder="<?php echo esc_js( __( 'Notes (e.g. Attested copy)', 'ifs-travel-erp' ) ); ?>" class="ifs-input-field">
                        </div>
                        <button type="button" class="ifs-btn-repeater-del" title="<?php echo esc_js( __( 'Remove Document', 'ifs-travel-erp' ) ); ?>"><span class="dashicons dashicons-trash"></span></button>
                    </div>
                `;
                $('#ifsDocRepeaterWrap').append(rowHtml);
            });

            $(document).on('click', '.ifs-btn-repeater-del', function(e) {
                e.preventDefault();
                if ($('.ifs-repeater-row').length > 1) {
                    $(this).closest('.ifs-repeater-row').remove();
                } else {
                    alert('<?php echo esc_js( __( 'At least one document checklist item must remain.', 'ifs-travel-erp' ) ); ?>');
                }
            });

            // Media Vault Helper
            function setupVaultUploader(btnId, removeBtnId, inputId, previewId, isPhoto) {
                $('#' + btnId).on('click', function(e) {
                    e.preventDefault();
                    const customUploader = wp.media({
                        title: '<?php echo esc_js( __( 'Select or Upload Document File', 'ifs-travel-erp' ) ); ?>',
                        button: { text: '<?php echo esc_js( __( 'Attach File', 'ifs-travel-erp' ) ); ?>' },
                        multiple: false
                    }).on('select', function() {
                        const attachment = customUploader.state().get('selection').first().toJSON();
                        if (attachment && attachment.url) {
                            $('#' + inputId).val(attachment.url);
                            if (attachment.url.match(/\.(jpg|jpeg|png|webp|gif)$/i)) {
                                $('#' + previewId).html('<img src="' + attachment.url + '" alt="<?php echo esc_js( __( 'Document Preview', 'ifs-travel-erp' ) ); ?>" />');
                            } else {
                                $('#' + previewId).html('<div class="vault-pdf-icon"><span class="dashicons dashicons-pdf"></span><small><?php echo esc_js( __( 'PDF', 'ifs-travel-erp' ) ); ?></small></div>');
                            }
                            $('#' + removeBtnId).removeClass('hide');
                            if (isPhoto) {
                                $('#prev_avatar').html('<img src="' + attachment.url + '" alt="<?php echo esc_js( __( 'Applicant', 'ifs-travel-erp' ) ); ?>" />');
                            }
                        }
                    }).open();
                });

                $('#' + removeBtnId).on('click', function(e) {
                    e.preventDefault();
                    $('#' + inputId).val('');
                    $(this).addClass('hide');
                    if (isPhoto) {
                        $('#' + previewId).html('<div class="vault-empty-icon"><span class="dashicons dashicons-camera"></span></div>');
                        updateVisaCard();
                    } else {
                        $('#' + previewId).html('<div class="vault-empty-icon"><span class="dashicons dashicons-media-document"></span></div>');
                    }
                });
            }

            setupVaultUploader('ifsUploadPhotoBtn', 'ifsRemovePhotoBtn', 'inp_photo_url', 'prev_vault_photo', true);
            setupVaultUploader('ifsUploadPassportBtn', 'ifsRemovePassportBtn', 'inp_passport_scan', 'prev_vault_passport', false);
            setupVaultUploader('ifsUploadVisaBtn', 'ifsRemoveVisaBtn', 'inp_visa_doc', 'prev_vault_visa', false);
            setupVaultUploader('ifsUploadSupportBtn', 'ifsRemoveSupportBtn', 'inp_supporting_doc', 'prev_vault_support', false);
        });
        </script>
        <?php
    }
}