<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_visa_add_panel' ) ) {
    /**
     * Visa Advisory & Country Requirements Form Panel
     */
    function ifs_terp_visa_add_panel() {
        global $wpdb;
        $table_reqs = $wpdb->prefix . 'iterp_visa_requirements';
        $base_url   = admin_url( 'admin.php?page=ifs_travel_erp&tab=settings&sub=visa_req' );
        $message    = '';

        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_visa_req_submit'] ) ) {
            check_admin_referer( 'ifs_visa_req_nonce_action', 'ifs_visa_req_nonce' );

            $is_edit_mode = ( isset( $_POST['edit_req_id'] ) && absint( wp_unslash( $_POST['edit_req_id'] ) ) > 0 );
            $edit_id      = $is_edit_mode ? absint( wp_unslash( $_POST['edit_req_id'] ) ) : 0;

            $country_name          = isset( $_POST['country_name'] ) ? sanitize_text_field( wp_unslash( $_POST['country_name'] ) ) : '';
            $visa_type             = isset( $_POST['visa_type'] ) ? sanitize_text_field( wp_unslash( $_POST['visa_type'] ) ) : 'Tourist / Visit Visa';
            $entry_type            = isset( $_POST['entry_type'] ) ? sanitize_text_field( wp_unslash( $_POST['entry_type'] ) ) : 'Single Entry';
            $submission_mode       = isset( $_POST['submission_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['submission_mode'] ) ) : 'VFS / Biometric In-Person';
            $consular_jurisdiction = isset( $_POST['consular_jurisdiction'] ) ? sanitize_text_field( wp_unslash( $_POST['consular_jurisdiction'] ) ) : '';
            $processing_time       = isset( $_POST['processing_time'] ) ? sanitize_text_field( wp_unslash( $_POST['processing_time'] ) ) : '7-10 Working Days';
            $stay_validity         = isset( $_POST['stay_validity'] ) ? sanitize_text_field( wp_unslash( $_POST['stay_validity'] ) ) : '30 Days';
            $passport_validity_req = isset( $_POST['passport_validity_req'] ) ? sanitize_text_field( wp_unslash( $_POST['passport_validity_req'] ) ) : 'Minimum 6 Months';
            $photo_spec            = isset( $_POST['photo_spec'] ) ? sanitize_text_field( wp_unslash( $_POST['photo_spec'] ) ) : '35x45mm, White Background, Matte';
            $biometric_rules       = isset( $_POST['biometric_rules'] ) ? sanitize_text_field( wp_unslash( $_POST['biometric_rules'] ) ) : 'Mandatory (Exempt under 12 yrs)';
            $official_portal_url   = isset( $_POST['official_portal_url'] ) ? esc_url_raw( wp_unslash( $_POST['official_portal_url'] ) ) : '';
            
            $fee_currency          = isset( $_POST['fee_currency'] ) ? sanitize_text_field( wp_unslash( $_POST['fee_currency'] ) ) : 'BDT';
            $embassy_fee           = isset( $_POST['embassy_fee'] ) ? (float) wp_unslash( $_POST['embassy_fee'] ) : 0;
            $service_fee           = isset( $_POST['service_fee'] ) ? (float) wp_unslash( $_POST['service_fee'] ) : 0;
            $standard_fee          = $embassy_fee + $service_fee;

            $raw_items         = isset( $_POST['checklist_repeater'] ) && is_array( $_POST['checklist_repeater'] ) ? (array) wp_unslash( $_POST['checklist_repeater'] ) : array();
            $clean_items       = array_values( array_filter( array_map( 'sanitize_text_field', $raw_items ) ) );
            $requirements_json = wp_json_encode( $clean_items );

            if ( empty( $country_name ) ) {
                $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> ' . esc_html__( 'Country Name is required.', 'ifs-travel-erp' ) . '</div>';
            } else {
                $data_array = array(
                    'country_name'          => $country_name,
                    'visa_type'             => $visa_type,
                    'entry_type'            => $entry_type,
                    'submission_mode'       => $submission_mode,
                    'consular_jurisdiction' => $consular_jurisdiction,
                    'processing_time'       => $processing_time,
                    'stay_validity'         => $stay_validity,
                    'passport_validity_req' => $passport_validity_req,
                    'photo_spec'            => $photo_spec,
                    'biometric_rules'       => $biometric_rules,
                    'official_portal_url'   => $official_portal_url,
                    'fee_currency'          => $fee_currency,
                    'embassy_fee'           => $embassy_fee,
                    'service_fee'           => $service_fee,
                    'standard_fee'          => $standard_fee,
                    'requirements_list'     => $requirements_json,
                );

                $existing_columns = $wpdb->get_col( "DESC {$table_reqs}", 0 );
                $final_data = array();
                foreach ( $data_array as $col => $val ) {
                    if ( in_array( $col, $existing_columns, true ) ) {
                        $final_data[ $col ] = $val;
                    }
                }

                if ( $is_edit_mode ) {
                    $wpdb->update( $table_reqs, $final_data, array( 'id' => $edit_id ) );
                    $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Visa Requirement Updated Successfully.', 'ifs-travel-erp' ) . '</div>';
                } else {
                    if ( in_array( 'created_at', $existing_columns, true ) ) {
                        $final_data['created_at'] = current_time( 'mysql' );
                    }
                    $wpdb->insert( $table_reqs, $final_data );
                    $edit_id      = $wpdb->insert_id;
                    $is_edit_mode = true;
                    $message      = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'New Visa Requirement Template Configured Successfully.', 'ifs-travel-erp' ) . '</div>';
                }
            }
        }

        $req_id    = isset( $_GET['req_id'] ) ? absint( wp_unslash( $_GET['req_id'] ) ) : 0;
        $edit_data = false;
        if ( $req_id > 0 ) {
            $edit_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_reqs} WHERE id = %d", $req_id ) );
        }

        $val_country   = $edit_data ? esc_attr( $edit_data->country_name ) : 'United Kingdom';
        $val_type      = $edit_data ? esc_attr( $edit_data->visa_type ) : 'Standard Tourist Visa';
        $val_entry     = $edit_data ? esc_attr( $edit_data->entry_type ?? 'Single Entry' ) : 'Single Entry';
        $val_mode      = $edit_data ? esc_attr( $edit_data->submission_mode ?? 'VFS / Biometric In-Person' ) : 'VFS / Biometric In-Person';
        $val_consular  = $edit_data ? esc_attr( $edit_data->consular_jurisdiction ?? '' ) : '';
        $val_time      = $edit_data ? esc_attr( $edit_data->processing_time ) : '7-10 Working Days';
        $val_valid     = $edit_data ? esc_attr( $edit_data->stay_validity ?? '30 Days Stay' ) : '30 Days Stay';
        $val_pass_req  = $edit_data ? esc_attr( $edit_data->passport_validity_req ?? 'Minimum 6 Months' ) : 'Minimum 6 Months';
        $val_photo     = $edit_data ? esc_attr( $edit_data->photo_spec ?? '35x45mm, White Background, Matte' ) : '35x45mm, White Background, Matte';
        $val_bio_rules = $edit_data ? esc_attr( $edit_data->biometric_rules ?? 'Mandatory (Exempt under 12 yrs)' ) : 'Mandatory (Exempt under 12 yrs)';
        $val_portal    = $edit_data ? esc_url( $edit_data->official_portal_url ?? '' ) : '';
        $val_curr      = $edit_data ? esc_attr( $edit_data->fee_currency ?? 'BDT' ) : 'BDT';
        
        $val_emb_fee   = $edit_data ? (float) ( $edit_data->embassy_fee ?? 0 ) : 13500;
        $val_svc_fee   = $edit_data ? (float) ( $edit_data->service_fee ?? 0 ) : 3000;
        $val_fee       = $val_emb_fee + $val_svc_fee;

        $checklist_items = array();
        if ( $edit_data && ! empty( $edit_data->requirements_list ) ) {
            $decoded = json_decode( $edit_data->requirements_list, true );
            if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
                $checklist_items = $decoded;
            } else {
                $checklist_items = array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", '', $edit_data->requirements_list ) ) ) );
            }
        }
        if ( empty( $checklist_items ) ) {
            $checklist_items = array(
                'Original Passport (Minimum 6 months validity with at least 2 blank pages)',
                '2 Copies Recent Photos (35x45mm, White Background, Matte Finish)',
                'Bank Statement & Solvency Certificate (Last 6 Months, Verified Balance)',
                'Trade License / Office NOC / Official Leave Approval Letter',
                'Air Ticket Itinerary & Hotel Accommodation Voucher'
            );
        }
        ?>
        <style>
            .ifs-split-editor { display: grid; grid-template-columns: 1fr 390px; gap: 28px; align-items: flex-start; }
            @media (max-width: 1140px) { .ifs-split-editor { grid-template-columns: 1fr; } }
            .ifs-preview-sticky { position: sticky; top: 30px; }
            .ifs-advisory-card { background: linear-gradient(135deg, #0b1329 0%, #1e293b 50%, #0f172a 100%); border-radius: 18px; padding: 24px; color: #ffffff; border: 1px solid rgba(255, 255, 255, 0.12); position: relative; overflow: hidden; margin-bottom: 18px; }
            .advisory-watermark { position: absolute; right: -15px; bottom: -15px; opacity: 0.04; pointer-events: none; }
            .advisory-watermark .dashicons { font-size: 140px; width: 140px; height: 140px; color: #ffffff; }
            .ifs-toast { padding: 13px 18px; border-radius: 10px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 10px; margin-bottom: 22px; }
            .ifs-toast.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
            .ifs-toast.danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        </style>

        <div class="wrap" style="max-width: 1440px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #0f172a;">
            <?php echo wp_kses_post( $message ); ?>

            <form method="post" action="" class="ifs-split-editor" id="ifsReqsForm">
                <?php wp_nonce_field( 'ifs_visa_req_nonce_action', 'ifs_visa_req_nonce' ); ?>
                
                <?php if ( $edit_data ) : ?>
                    <input type="hidden" name="edit_req_id" value="<?php echo esc_attr( $edit_data->id ); ?>">
                <?php endif; ?>

                <div class="ifs-form-body">
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 26px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); margin-bottom: 22px;">
                        <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 22px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9;">
                            <div style="width: 34px; height: 34px; border-radius: 9px; background: linear-gradient(135deg, #003376 0%, #0284c7 100%); color: #ffffff; font-weight: 800; font-size: 14px; display: flex; align-items: center; justify-content: center;">
                                <span class="dashicons dashicons-admin-site-alt3"></span>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 15.5px; font-weight: 800; color: #0f172a;">
                                    <?php echo $edit_data ? esc_html__( 'Update Country Visa Advisory', 'ifs-travel-erp' ) : esc_html__( 'Configure Country Visa Policy, Fees & Submission Channels', 'ifs-travel-erp' ); ?>
                                </h3>
                                <p style="margin: 2px 0 0 0; font-size: 12.5px; color: #64748b;"><?php esc_html_e( 'Define standard visa parameters, processing channels, photo standards, and cost allocations', 'ifs-travel-erp' ); ?></p>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px;">
                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Destination Country', 'ifs-travel-erp' ); ?> <span style="color: #e11d48;">*</span></label>
                                <input type="text" name="country_name" id="inp_country_name" required value="<?php echo esc_attr( $val_country ); ?>" placeholder="<?php esc_attr_e( 'e.g. United Kingdom, UAE, Canada', 'ifs-travel-erp' ); ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-weight: 700;">
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Visa Category', 'ifs-travel-erp' ); ?> <span style="color: #e11d48;">*</span></label>
                                <input type="text" name="visa_type" id="inp_visa_type" required value="<?php echo esc_attr( $val_type ); ?>" placeholder="<?php esc_attr_e( 'e.g. Tourist / Business / E-Visa', 'ifs-travel-erp' ); ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Entry Type', 'ifs-travel-erp' ); ?></label>
                                <select name="entry_type" id="inp_entry_type" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                                    <option value="Single Entry" <?php selected( $val_entry, 'Single Entry' ); ?>><?php esc_html_e( 'Single Entry', 'ifs-travel-erp' ); ?></option>
                                    <option value="Multiple Entry" <?php selected( $val_entry, 'Multiple Entry' ); ?>><?php esc_html_e( 'Multiple Entry', 'ifs-travel-erp' ); ?></option>
                                    <option value="Double Entry" <?php selected( $val_entry, 'Double Entry' ); ?>><?php esc_html_e( 'Double Entry', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Submission Method', 'ifs-travel-erp' ); ?></label>
                                <select name="submission_mode" id="inp_submission_mode" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                                    <option value="VFS / Biometric In-Person" <?php selected( $val_mode, 'VFS / Biometric In-Person' ); ?>><?php esc_html_e( 'VFS / Biometric In-Person', 'ifs-travel-erp' ); ?></option>
                                    <option value="Online E-Visa Portal" <?php selected( $val_mode, 'Online E-Visa Portal' ); ?>><?php esc_html_e( 'Online Portal / E-Visa Stickerless', 'ifs-travel-erp' ); ?></option>
                                    <option value="Physical Passport Drop-box" <?php selected( $val_mode, 'Physical Passport Drop-box' ); ?>><?php esc_html_e( 'Physical Passport Drop-box', 'ifs-travel-erp' ); ?></option>
                                    <option value="Embassy In-Person Interview" <?php selected( $val_mode, 'Embassy In-Person Interview' ); ?>><?php esc_html_e( 'Embassy Direct In-Person Interview', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Consular / Submission Center', 'ifs-travel-erp' ); ?></label>
                                <input type="text" name="consular_jurisdiction" id="inp_consular" value="<?php echo esc_attr( $val_consular ); ?>" placeholder="<?php esc_attr_e( 'e.g. VFS Dhaka / Embassy Direct', 'ifs-travel-erp' ); ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Standard Turnaround Time', 'ifs-travel-erp' ); ?></label>
                                <input type="text" name="processing_time" id="inp_processing_time" value="<?php echo esc_attr( $val_time ); ?>" placeholder="<?php esc_attr_e( 'e.g. 7-10 Working Days', 'ifs-travel-erp' ); ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Stay Duration & Validity', 'ifs-travel-erp' ); ?></label>
                                <input type="text" name="stay_validity" id="inp_stay_validity" value="<?php echo esc_attr( $val_valid ); ?>" placeholder="<?php esc_attr_e( 'e.g. 30 Days Stay (90 Days Valid)', 'ifs-travel-erp' ); ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Passport Validity Required', 'ifs-travel-erp' ); ?></label>
                                <select name="passport_validity_req" id="inp_passport_validity_req" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                                    <option value="Minimum 6 Months" <?php selected( $val_pass_req, 'Minimum 6 Months' ); ?>><?php esc_html_e( 'Minimum 6 Months from Travel Date', 'ifs-travel-erp' ); ?></option>
                                    <option value="Minimum 3 Months" <?php selected( $val_pass_req, 'Minimum 3 Months' ); ?>><?php esc_html_e( 'Minimum 3 Months beyond stay', 'ifs-travel-erp' ); ?></option>
                                    <option value="Valid throughout stay" <?php selected( $val_pass_req, 'Valid throughout stay' ); ?>><?php esc_html_e( 'Valid throughout intended stay', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Photo Specifications', 'ifs-travel-erp' ); ?></label>
                                <input type="text" name="photo_spec" id="inp_photo_spec" value="<?php echo esc_attr( $val_photo ); ?>" placeholder="<?php esc_attr_e( 'e.g. 35x45mm, White BG, Matte Finish', 'ifs-travel-erp' ); ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Biometrics & Age Exemption Rules', 'ifs-travel-erp' ); ?></label>
                                <input type="text" name="biometric_rules" id="inp_biometric_rules" value="<?php echo esc_attr( $val_bio_rules ); ?>" placeholder="<?php esc_attr_e( 'e.g. Mandatory (Exempt under 12 yrs)', 'ifs-travel-erp' ); ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                            </div>

                            <div style="grid-column: span 2;">
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Official Embassy / Application Portal URL', 'ifs-travel-erp' ); ?></label>
                                <input type="url" name="official_portal_url" id="inp_portal_url" value="<?php echo esc_url( $val_portal ); ?>" placeholder="https://visa.vfsglobal.com/..." style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Quote Currency', 'ifs-travel-erp' ); ?></label>
                                <select name="fee_currency" id="inp_fee_curr" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                                    <option value="BDT" <?php selected( $val_curr, 'BDT' ); ?>>BDT (৳)</option>
                                    <option value="USD" <?php selected( $val_curr, 'USD' ); ?>>USD ($)</option>
                                    <option value="EUR" <?php selected( $val_curr, 'EUR' ); ?>>EUR (€)</option>
                                    <option value="SAR" <?php selected( $val_curr, 'SAR' ); ?>>SAR (﷼)</option>
                                    <option value="AED" <?php selected( $val_curr, 'AED' ); ?>>AED (د.إ)</option>
                                </select>
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Embassy / Govt Fee', 'ifs-travel-erp' ); ?> <span style="color: #e11d48;">*</span></label>
                                <input type="number" step="0.01" name="embassy_fee" id="inp_embassy_fee" required value="<?php echo esc_attr( $val_emb_fee ); ?>" placeholder="0.00" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-family: monospace;">
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Agency Processing Charge', 'ifs-travel-erp' ); ?></label>
                                <input type="number" step="0.01" name="service_fee" id="inp_service_fee" value="<?php echo esc_attr( $val_svc_fee ); ?>" placeholder="0.00" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-family: monospace; font-weight: 700; color: #059669;">
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Total Quoted Fare', 'ifs-travel-erp' ); ?></label>
                                <input type="text" id="inp_total_fee_display" readonly value="<?php echo esc_attr( number_format( (float) $val_fee, 2 ) ); ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-family: monospace; font-weight: 700; background: #f8fafc; color: #0284c7;">
                            </div>
                        </div>
                    </div>

                    <!-- Mandatory Document Checklist Card -->
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 26px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px;">
                            <div style="display: flex; align-items: center; gap: 14px;">
                                <div style="width: 34px; height: 34px; border-radius: 9px; background: #0284c7; color: #ffffff; font-weight: 800; font-size: 14px; display: flex; align-items: center; justify-content: center;">02</div>
                                <div>
                                    <h3 style="margin: 0; font-size: 15.5px; font-weight: 800; color: #0f172a;"><?php esc_html_e( 'Mandatory Document Checklist (Dynamic Repeater)', 'ifs-travel-erp' ); ?></h3>
                                    <p style="margin: 2px 0 0 0; font-size: 12.5px; color: #64748b;"><?php esc_html_e( 'Add, reorder, or edit mandatory paperwork items required for consular clearance', 'ifs-travel-erp' ); ?></p>
                                </div>
                            </div>
                            <button type="button" id="btnAddChecklistItem" style="background: #eff6ff; color: #0284c7; border: 1px solid #bae6fd; padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Document Item', 'ifs-travel-erp' ); ?>
                            </button>
                        </div>

                        <div id="checklistRepeaterContainer" style="display: flex; flex-direction: column; gap: 8px;">
                            <?php foreach ( $checklist_items as $item_text ) : ?>
                                <div class="repeater-row" style="display: flex; align-items: center; gap: 8px;">
                                    <span style="font-size: 13px; font-weight: 800; width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: #e0f2fe; color: #0284c7; flex-shrink: 0;">✔</span>
                                    <input type="text" name="checklist_repeater[]" value="<?php echo esc_attr( $item_text ); ?>" placeholder="<?php esc_attr_e( 'e.g. Original Passport with 6 months validity', 'ifs-travel-erp' ); ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                                    <button type="button" class="btn-remove-row" style="background: #fee2e2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 8px; width: 42px; height: 42px; font-size: 20px; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0;" title="<?php esc_attr_e( 'Remove', 'ifs-travel-erp' ); ?>">&times;</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div style="margin-top: 24px; display: flex; justify-content: space-between; align-items: center;">
                        <a href="<?php echo esc_url( add_query_arg( 'action_sub', 'all', $base_url ) ); ?>" style="color: #64748b; text-decoration: none; font-weight: 600;">
                            <span class="dashicons dashicons-arrow-left-alt" style="vertical-align: middle;"></span> <?php esc_html_e( 'Cancel Advisory', 'ifs-travel-erp' ); ?>
                        </a>
                        <button type="submit" name="ifs_visa_req_submit" style="background: linear-gradient(135deg, #003376 0%, #0284c7 100%); color: #ffffff; border: none; height: 42px; padding: 0 26px; border-radius: 8px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                            <span class="dashicons dashicons-saved"></span> 
                            <?php echo $edit_data ? esc_html__( 'Update Requirement Advisory', 'ifs-travel-erp' ) : esc_html__( 'Save Requirement Entry', 'ifs-travel-erp' ); ?>
                        </button>
                    </div>
                </div>

                <!-- Right Sidebar: Live Advisory Card Preview -->
                <div class="ifs-preview-sidebar">
                    <div class="ifs-preview-sticky">
                        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                            <span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Live Country Advisory Card Preview', 'ifs-travel-erp' ); ?>
                        </div>

                        <div class="ifs-advisory-card">
                            <div class="advisory-watermark"><span class="dashicons dashicons-admin-site-alt3"></span></div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                                <span style="font-size: 9.5px; font-weight: 800; letter-spacing: 0.8px; color: #94a3b8; display: inline-flex; align-items: center; gap: 4px;">
                                    <span class="dashicons dashicons-shield-alt" style="color: #38bdf8;"></span> <?php esc_html_e( 'OFFICIAL VISA POLICY', 'ifs-travel-erp' ); ?>
                                </span>
                                <span style="background: rgba(56, 189, 248, 0.12); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.25); padding: 3px 8px; border-radius: 6px; font-size: 9.5px; font-weight: 800; text-transform: uppercase;" id="prev_time">
                                    <span class="dashicons dashicons-clock"></span> <?php echo esc_html( strtoupper( $val_time ) ); ?>
                                </span>
                            </div>

                            <div style="margin-bottom: 16px;">
                                <h3 style="margin: 0; font-size: 18px; font-weight: 900; color: #ffffff; text-transform: uppercase;" id="prev_country"><?php echo esc_html( strtoupper( $val_country ) ); ?></h3>
                                <span style="font-size: 11.5px; color: #38bdf8; margin-top: 3px; display: block; text-transform: uppercase; font-weight: 700;" id="prev_category"><?php echo esc_html( strtoupper( $val_type ) ); ?></span>
                                <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px;">
                                    <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 10px; color: #e2e8f0; background: rgba(255, 255, 255, 0.07); border: 1px solid rgba(255, 255, 255, 0.06); padding: 3px 8px; border-radius: 6px; font-weight: 600;" id="prev_entry">
                                        <span class="dashicons dashicons-randomize" style="color: #38bdf8;"></span> <?php echo esc_html( $val_entry ); ?>
                                    </span>
                                    <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 10px; color: #e2e8f0; background: rgba(255, 255, 255, 0.07); border: 1px solid rgba(255, 255, 255, 0.06); padding: 3px 8px; border-radius: 6px; font-weight: 600;" id="prev_validity">
                                        <span class="dashicons dashicons-calendar-alt" style="color: #38bdf8;"></span> <?php echo esc_html( $val_valid ); ?>
                                    </span>
                                </div>
                            </div>

                            <div style="background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.06); border-radius: 12px; padding: 14px; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center;">
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-size: 9px; font-weight: 700; color: #94a3b8; letter-spacing: 0.5px;"><?php esc_html_e( 'QUOTED TOTAL CHARGE', 'ifs-travel-erp' ); ?></span>
                                    <span style="font-size: 10px; color: #cbd5e1; margin-top: 2px; font-family: monospace;" id="prev_fee_breakdown"><?php echo esc_html( $val_curr ); ?> Govt: <?php echo esc_html( number_format( (float) $val_emb_fee, 2 ) ); ?> | Agency: <?php echo esc_html( number_format( (float) $val_svc_fee, 2 ) ); ?></span>
                                </div>
                                <h4 style="margin: 0; font-size: 18px; font-weight: 900; color: #4ade80; font-family: monospace;" id="prev_fee"><?php echo esc_html( $val_curr . ' ' . number_format( (float) $val_fee, 2 ) ); ?></h4>
                            </div>

                            <div style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 12px; padding: 12px 14px; margin-bottom: 12px;">
                                <span style="font-size: 9.5px; font-weight: 800; color: #38bdf8; display: flex; align-items: center; gap: 4px; margin-bottom: 6px;">
                                    <span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'MANDATORY DOCUMENT CHECKLIST:', 'ifs-travel-erp' ); ?>
                                </span>
                                <div style="font-size: 11px; color: #cbd5e1; line-height: 1.5; max-height: 140px; overflow-y: auto;" id="prev_checklist">
                                    1. Original Passport (Min 6 months validity)<br>
                                    2. Photos (35x45mm White BG)<br>
                                    3. Bank Statement &amp; Solvency
                                </div>
                            </div>

                            <div style="font-size: 9.5px; color: #94a3b8; display: flex; justify-content: space-between; align-items: center; padding-top: 8px; border-top: 1px solid rgba(255, 255, 255, 0.08);">
                                <span><span class="dashicons dashicons-saved" style="color: #38bdf8; font-size: 13px; vertical-align: middle;"></span> <?php esc_html_e( 'IATA & VFS Compliance', 'ifs-travel-erp' ); ?></span>
                                <span style="width: 7px; height: 7px; background: #4ade80; border-radius: 50%; display: inline-block;" title="<?php esc_attr_e( 'Live Sync Active', 'ifs-travel-erp' ); ?>"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            const inpCountry  = $('#inp_country_name');
            const inpType     = $('#inp_visa_type');
            const inpEntry    = $('#inp_entry_type');
            const inpTime     = $('#inp_processing_time');
            const inpValidity = $('#inp_stay_validity');
            const inpCurr     = $('#inp_fee_curr');
            const inpEmbFee   = $('#inp_embassy_fee');
            const inpSvcFee   = $('#inp_service_fee');
            const totFeeDisp  = $('#inp_total_fee_display');

            const prevCountry   = $('#prev_country');
            const prevCategory  = $('#prev_category');
            const prevEntry     = $('#prev_entry');
            const prevTime      = $('#prev_time');
            const prevValidity  = $('#prev_validity');
            const prevFee       = $('#prev_fee');
            const prevFeeBreak  = $('#prev_fee_breakdown');
            const prevChecklist = $('#prev_checklist');

            $('#btnAddChecklistItem').on('click', function(e) {
                e.preventDefault();
                const rowHtml = `
                    <div class="repeater-row" style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 13px; font-weight: 800; width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: #e0f2fe; color: #0284c7; flex-shrink: 0;">✔</span>
                        <input type="text" name="checklist_repeater[]" placeholder="<?php echo esc_js( __( 'e.g. Bank Solvency Certificate', 'ifs-travel-erp' ) ); ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                        <button type="button" class="btn-remove-row" style="background: #fee2e2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 8px; width: 42px; height: 42px; font-size: 20px; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0;" title="<?php echo esc_js( __( 'Remove', 'ifs-travel-erp' ) ); ?>">&times;</button>
                    </div>
                `;
                $('#checklistRepeaterContainer').append(rowHtml);
                updateAdvisoryCard();
            });

            $(document).on('click', '.btn-remove-row', function(e) {
                e.preventDefault();
                const container = $(this).closest('#checklistRepeaterContainer');
                if (container.find('.repeater-row').length > 1) {
                    $(this).closest('.repeater-row').remove();
                } else {
                    $(this).closest('.repeater-row').find('input').val('');
                }
                updateAdvisoryCard();
            });

            function updateAdvisoryCard() {
                const curr = inpCurr.val() || 'BDT';
                if (prevCountry.length)  prevCountry.text(inpCountry.val().trim() ? inpCountry.val().trim().toUpperCase() : 'DESTINATION');
                if (prevCategory.length) prevCategory.text(inpType.val().trim() ? inpType.val().trim().toUpperCase() : 'TOURIST VISA');
                if (prevEntry.length)    prevEntry.html('<span class="dashicons dashicons-randomize" style="color: #38bdf8;"></span> ' + (inpEntry.val() || 'Single Entry'));
                if (prevTime.length)     prevTime.html('<span class="dashicons dashicons-clock"></span> ' + (inpTime.val().trim() ? inpTime.val().trim().toUpperCase() : 'STANDARD TIME'));
                if (prevValidity.length) prevValidity.html('<span class="dashicons dashicons-calendar-alt" style="color: #38bdf8;"></span> ' + (inpValidity.val().trim() || '30 Days Stay'));

                const embVal = parseFloat(inpEmbFee.val()) || 0;
                const svcVal = parseFloat(inpSvcFee.val()) || 0;
                const totVal = embVal + svcVal;

                if (totFeeDisp.length)   totFeeDisp.val(totVal.toFixed(2));
                if (prevFee.length)      prevFee.text(curr + ' ' + totVal.toLocaleString('en-US', { minimumFractionDigits: 2 }));
                if (prevFeeBreak.length) prevFeeBreak.text(curr + ' Govt: ' + embVal.toLocaleString('en-US') + ' | Agency: ' + svcVal.toLocaleString('en-US'));

                const items = [];
                $('input[name="checklist_repeater[]"]').each(function() {
                    const val = $(this).val().trim();
                    if (val) items.push(val);
                });

                if (prevChecklist.length) {
                    if (items.length > 0) {
                        prevChecklist.html(items.map((item, idx) => (idx + 1) + '. ' + item).join('<br>'));
                    } else {
                        prevChecklist.text('<?php echo esc_js( __( 'No checklist documents defined yet.', 'ifs-travel-erp' ) ); ?>');
                    }
                }
            }

            $(document).on('input change keyup', '#inp_country_name, #inp_visa_type, #inp_entry_type, #inp_submission_mode, #inp_consular, #inp_processing_time, #inp_stay_validity, #inp_fee_curr, #inp_embassy_fee, #inp_service_fee, input[name="checklist_repeater[]"]', updateAdvisoryCard);

            updateAdvisoryCard();
        });
        </script>
        <?php
    }
}