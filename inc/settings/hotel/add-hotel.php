<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_hotel_property_form_panel' ) ) {
    /**
     * Hotel Property & Contract Configuration Panel (Fully Enhanced)
     */
    function ifs_terp_hotel_property_form_panel() {
        global $wpdb;
        $table_hotels    = $wpdb->prefix . 'iterp_hotel_properties';
        $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
        $base_url        = admin_url( 'admin.php?page=ifs_travel_erp&tab=settings&sub=hotels_prop' );
        $message         = '';

        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_hotel_prop_submit'] ) ) {
            check_admin_referer( 'ifs_hotel_prop_action', 'ifs_hotel_prop_nonce' );

            $edit_id              = isset( $_POST['edit_hotel_id'] ) ? absint( wp_unslash( $_POST['edit_hotel_id'] ) ) : 0;
            $property_name        = isset( $_POST['property_name'] ) ? sanitize_text_field( wp_unslash( $_POST['property_name'] ) ) : '';
            $property_type        = isset( $_POST['property_type'] ) ? sanitize_text_field( wp_unslash( $_POST['property_type'] ) ) : 'Hotel';
            $supplier_id          = isset( $_POST['supplier_id'] ) ? absint( wp_unslash( $_POST['supplier_id'] ) ) : 0;
            $city                 = isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '';
            $country              = isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : '';
            $star_rating          = isset( $_POST['star_rating'] ) ? sanitize_text_field( wp_unslash( $_POST['star_rating'] ) ) : '4 Star';
            $default_meal_plan    = isset( $_POST['default_meal_plan'] ) ? sanitize_text_field( wp_unslash( $_POST['default_meal_plan'] ) ) : 'Bed & Breakfast (BB)';
            $child_policy         = isset( $_POST['child_policy'] ) ? sanitize_text_field( wp_unslash( $_POST['child_policy'] ) ) : 'Free up to 6 years';
            $allocation_type      = isset( $_POST['allocation_type'] ) ? sanitize_text_field( wp_unslash( $_POST['allocation_type'] ) ) : 'Guaranteed Block';
            $release_days         = isset( $_POST['release_days'] ) ? intval( wp_unslash( $_POST['release_days'] ) ) : 7;
            $room_view_type       = isset( $_POST['room_view_type'] ) ? sanitize_text_field( wp_unslash( $_POST['room_view_type'] ) ) : 'City View';
            $payment_terms        = isset( $_POST['payment_terms'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_terms'] ) ) : '50% Advance, Balance on Arrival';
            $rate_currency        = isset( $_POST['rate_currency'] ) ? sanitize_text_field( wp_unslash( $_POST['rate_currency'] ) ) : 'BDT';
            $season_type          = isset( $_POST['season_type'] ) ? sanitize_text_field( wp_unslash( $_POST['season_type'] ) ) : 'Regular / Off-Peak';
            $haram_distance       = isset( $_POST['haram_distance'] ) ? sanitize_text_field( wp_unslash( $_POST['haram_distance'] ) ) : '';
            $total_beds           = isset( $_POST['total_beds'] ) ? intval( wp_unslash( $_POST['total_beds'] ) ) : 0;
            $contract_rate        = isset( $_POST['contract_rate'] ) ? (float) wp_unslash( $_POST['contract_rate'] ) : 0;
            $standard_sell        = isset( $_POST['standard_sell'] ) ? (float) wp_unslash( $_POST['standard_sell'] ) : 0;
            $tax_policy           = isset( $_POST['tax_policy'] ) ? sanitize_text_field( wp_unslash( $_POST['tax_policy'] ) ) : 'Inclusive of VAT & Taxes';
            $contract_start_date  = isset( $_POST['contract_start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['contract_start_date'] ) ) : '1970-01-01';
            $contract_end_date    = isset( $_POST['contract_end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['contract_end_date'] ) ) : '1970-01-01';
            $checkin_time         = isset( $_POST['checkin_time'] ) ? sanitize_text_field( wp_unslash( $_POST['checkin_time'] ) ) : '14:00';
            $checkout_time        = isset( $_POST['checkout_time'] ) ? sanitize_text_field( wp_unslash( $_POST['checkout_time'] ) ) : '12:00';
            $room_types_supported = isset( $_POST['room_types_supported'] ) ? sanitize_text_field( wp_unslash( $_POST['room_types_supported'] ) ) : 'Single, Double, Triple, Quad';
            $contact_person       = isset( $_POST['contact_person'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_person'] ) ) : '';
            $contact_phone        = isset( $_POST['contact_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_phone'] ) ) : '';
            $emergency_phone      = isset( $_POST['emergency_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['emergency_phone'] ) ) : '';
            $contact_email        = isset( $_POST['contact_email'] ) ? sanitize_email( wp_unslash( $_POST['contact_email'] ) ) : '';
            $contract_doc_url     = isset( $_POST['contract_doc_url'] ) ? esc_url_raw( wp_unslash( $_POST['contract_doc_url'] ) ) : '';
            $cancellation_policy  = isset( $_POST['cancellation_policy'] ) ? sanitize_textarea_field( wp_unslash( $_POST['cancellation_policy'] ) ) : '';
            $blackout_dates_notes = isset( $_POST['blackout_dates_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['blackout_dates_notes'] ) ) : '';
            $bank_details         = isset( $_POST['bank_details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['bank_details'] ) ) : '';
            $address              = isset( $_POST['address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['address'] ) ) : '';
            $status               = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'Active';

            // Amenities Repeater Sanitization
            $raw_amenities   = isset( $_POST['amenities_repeater'] ) && is_array( $_POST['amenities_repeater'] ) ? (array) wp_unslash( $_POST['amenities_repeater'] ) : array();
            $clean_amenities = array_values( array_filter( array_map( 'sanitize_text_field', $raw_amenities ) ) );
            $amenities_str   = implode( ', ', $clean_amenities );

            if ( empty( $property_name ) || empty( $city ) ) {
                $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> ' . esc_html__( 'Property Name and City are required.', 'ifs-travel-erp' ) . '</div>';
            } else {
                $data = array(
                    'property_name'       => $property_name,
                    'property_type'       => $property_type,
                    'city'                => $city,
                    'country'             => $country,
                    'star_rating'         => $star_rating,
                    'haram_distance'      => $haram_distance,
                    'total_beds'          => $total_beds,
                    'contract_start_date' => ! empty( $contract_start_date ) ? $contract_start_date : '1970-01-01',
                    'contract_end_date'   => ! empty( $contract_end_date ) ? $contract_end_date : '1970-01-01',
                    'contact_person'      => $contact_person,
                    'contact_phone'       => $contact_phone,
                    'contract_rate'       => $contract_rate,
                    'standard_sell'       => $standard_sell,
                    'address'             => $address,
                    'amenities'           => $amenities_str,
                );

                $existing_columns = $wpdb->get_col( "DESC {$table_hotels}", 0 );
                $mappings = array(
                    'supplier_id'          => $supplier_id,
                    'contact_email'        => $contact_email,
                    'emergency_phone'      => $emergency_phone,
                    'default_meal_plan'    => $default_meal_plan,
                    'child_policy'         => $child_policy,
                    'allocation_type'      => $allocation_type,
                    'release_days'         => $release_days,
                    'room_view_type'       => $room_view_type,
                    'payment_terms'        => $payment_terms,
                    'rate_currency'        => $rate_currency,
                    'season_type'          => $season_type,
                    'tax_policy'           => $tax_policy,
                    'extra_bed_rate'       => isset( $_POST['extra_bed_rate'] ) ? (float) wp_unslash( $_POST['extra_bed_rate'] ) : 0,
                    'checkin_time'         => $checkin_time,
                    'checkout_time'        => $checkout_time,
                    'room_types_supported' => $room_types_supported,
                    'cancellation_policy'  => $cancellation_policy,
                    'blackout_dates_notes' => $blackout_dates_notes,
                    'bank_details'         => $bank_details,
                    'contract_doc_url'     => $contract_doc_url,
                    'status'               => $status,
                );

                foreach ( $mappings as $col => $val ) {
                    if ( in_array( $col, $existing_columns, true ) ) {
                        $data[ $col ] = $val;
                    }
                }

                if ( $edit_id > 0 ) {
                    $wpdb->update( $table_hotels, $data, array( 'id' => $edit_id ) );
                    $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Hotel Property Profile Updated Successfully.', 'ifs-travel-erp' ) . '</div>';
                } else {
                    $data['created_at'] = current_time( 'mysql' );
                    $wpdb->insert( $table_hotels, $data );
                    $edit_id = $wpdb->insert_id;
                    $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'New Hotel Property Contract Added Successfully.', 'ifs-travel-erp' ) . '</div>';
                }
            }
        }

        $edit_id  = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : ( ! empty( $edit_id ) ? $edit_id : 0 );
        $edit_row = $edit_id > 0 ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_hotels} WHERE id = %d", $edit_id ) ) : false;
        
        $suppliers = $wpdb->get_results( "SELECT id, supplier_name FROM {$table_suppliers} WHERE status = 'Active' ORDER BY supplier_name ASC" );

        $amenities_list = array();
        if ( $edit_row && ! empty( $edit_row->amenities ) ) {
            $decoded = json_decode( $edit_row->amenities, true );
            if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
                $amenities_list = $decoded;
            } else {
                $amenities_list = array_filter( array_map( 'trim', explode( ',', $edit_row->amenities ) ) );
            }
        }
        if ( empty( $amenities_list ) ) {
            $amenities_list = array( 'High-Speed Wi-Fi', 'Daily Buffet Breakfast', 'Airport Shuttle Service', '24/7 Room Service' );
        }
        ?>
        <style>
            .ifs-split-reqs-editor { display: grid; grid-template-columns: 1fr 390px; gap: 28px; align-items: flex-start; }
            @media (max-width: 1140px) { .ifs-split-reqs-editor { grid-template-columns: 1fr; } }
            .ifs-preview-sticky { position: sticky; top: 30px; }
            .ifs-panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 26px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04); }
            .ifs-card-header { display: flex; align-items: center; gap: 14px; margin-bottom: 22px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9; }
            .ifs-step-num { width: 34px; height: 34px; border-radius: 9px; background: #003376; color: #ffffff; font-weight: 800; font-size: 13px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
            .ifs-card-title { margin: 0; font-size: 16px; font-weight: 800; color: #0f172a; }
            .ifs-card-desc { margin: 2px 0 0 0; font-size: 12.5px; color: #64748b; }
            .ifs-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; }
            @media (max-width: 768px) { .ifs-grid-2 { grid-template-columns: 1fr; } }
            .ifs-field-block { display: flex; flex-direction: column; gap: 6px; }
            .ifs-field-label { font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.3px; }
            .ifs-field-label .req { color: #dc2626; margin-left: 2px; }
            .ifs-field-wrap { position: relative; display: flex; align-items: center; height: 42px; width: 100%; }
            .ifs-field-wrap .field-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 18px; width: 18px; height: 18px; color: #94a3b8; pointer-events: none; z-index: 2; }
            .ifs-input-field { width: 100% !important; height: 42px !important; line-height: 40px !important; border: 1px solid #cbd5e1 !important; border-radius: 8px !important; padding: 0 14px 0 40px !important; font-size: 13px !important; color: #0f172a !important; background: #ffffff !important; outline: none !important; margin: 0 !important; }
            .ifs-input-field:focus { border-color: #003376 !important; background: #f8fafc !important; }
            select.ifs-input-field { appearance: none; -webkit-appearance: none; background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e") !important; background-repeat: no-repeat !important; background-position: right 12px center !important; background-size: 14px !important; padding-right: 36px !important; cursor: pointer; }
            .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important; }
            .font-bold { font-weight: 700; }
            .color-emerald { color: #059669 !important; }
            .ifs-action-strip { margin-top: 10px; padding: 18px 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; display: flex; justify-content: space-between; align-items: center; }
            .ifs-btn-back { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; font-weight: 700; font-size: 13px; text-decoration: none; }
            .ifs-btn-back:hover { background: #f1f5f9; color: #0f172a; }
            .ifs-btn-primary { display: inline-flex; align-items: center; gap: 6px; padding: 0 24px; height: 42px; border-radius: 8px; border: none; background: #003376; color: #ffffff !important; font-weight: 700; font-size: 13.5px; cursor: pointer; text-decoration: none; }
            .ifs-btn-primary:hover { background: #0284c7; }
            .repeater-items-container { display: flex; flex-direction: column; gap: 8px; margin-top: 14px; }
            .repeater-row { display: flex; align-items: center; gap: 8px; }
            .repeater-row .row-indicator { width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: #ecfdf5; color: #047857; font-size: 13px; font-weight: 800; flex-shrink: 0; }
            .repeater-row .ifs-input-field { padding: 0 14px !important; }
            .btn-remove-row { background: #fee2e2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 8px; width: 42px; height: 42px; font-size: 20px; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
            .ifs-btn-add-row { background: #eff6ff; color: #0284c7; border: 1px solid #bae6fd; padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; }
            .ifs-card-preview-header { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }
            .ifs-advisory-card { background: linear-gradient(135deg, #0a1f44 0%, #003376 100%); border-radius: 18px; padding: 24px; color: #ffffff; border: 1px solid rgba(255, 255, 255, 0.12); position: relative; overflow: hidden; margin-bottom: 18px; box-shadow: 0 4px 16px rgba(10, 31, 68, 0.15); }
            .advisory-watermark { position: absolute; right: -15px; bottom: -15px; opacity: 0.05; pointer-events: none; }
            .advisory-watermark .dashicons { font-size: 140px; width: 140px; height: 140px; color: #ffffff; }
            .advisory-head-strip { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
            .advisory-badge-tag { font-size: 9.5px; font-weight: 800; letter-spacing: 0.8px; color: #93c5fd; display: inline-flex; align-items: center; gap: 4px; }
            .advisory-time-tag { background: rgba(2, 132, 199, 0.3); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.4); padding: 3px 8px; border-radius: 6px; font-size: 9.5px; font-weight: 800; text-transform: uppercase; display: inline-flex; align-items: center; gap: 4px; }
            .advisory-hero { margin-bottom: 16px; }
            .advisory-country { margin: 0; font-size: 18px; font-weight: 900; color: #ffffff; text-transform: uppercase; letter-spacing: -0.2px; }
            .advisory-category { font-size: 11.5px; color: #93c5fd; margin-top: 3px; display: block; text-transform: uppercase; font-weight: 700; }
            .advisory-meta-pills { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
            .advisory-mode-badge, .advisory-validity-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 10px; color: #e2e8f0; background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.08); padding: 3px 8px; border-radius: 6px; font-weight: 600; }
            .advisory-fee-box { background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 12px; padding: 14px; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center; }
            .advisory-fee-lbl { font-size: 9px; font-weight: 700; color: #93c5fd; letter-spacing: 0.5px; }
            .advisory-fee-sub { font-size: 10px; color: #cbd5e1; margin-top: 2px; font-family: monospace; }
            .advisory-fee-val { margin: 0; font-size: 18px; font-weight: 900; color: #38bdf8; }
            .advisory-checklist-box { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.06); border-radius: 12px; padding: 12px 14px; margin-bottom: 12px; }
            .checklist-head { font-size: 9.5px; font-weight: 800; color: #38bdf8; display: flex; align-items: center; gap: 4px; margin-bottom: 6px; }
            .checklist-content { font-size: 11px; color: #e2e8f0; line-height: 1.6; }
            .advisory-footer-strip { font-size: 9.5px; color: #93c5fd; display: flex; justify-content: space-between; align-items: center; padding-top: 8px; border-top: 1px solid rgba(255, 255, 255, 0.1); }
            .live-pulse-dot { width: 7px; height: 7px; background: #38bdf8; border-radius: 50%; display: inline-block; }
            .ifs-toast { padding: 13px 18px; border-radius: 10px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 10px; margin-bottom: 22px; }
            .ifs-toast.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
            .ifs-toast.danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        </style>

        <div class="wrap ifs-reqs-workspace" style="max-width: 1440px; margin: 0 auto;">
            <?php echo wp_kses_post( $message ); ?>

            <form method="post" action="" class="ifs-split-reqs-editor" id="ifsHotelForm">
                <?php wp_nonce_field( 'ifs_hotel_prop_action', 'ifs_hotel_prop_nonce' ); ?>
                <?php if ( $edit_row ) : ?>
                    <input type="hidden" name="edit_hotel_id" value="<?php echo esc_attr( $edit_row->id ); ?>">
                <?php endif; ?>

                <div class="ifs-reqs-form-body">
                    <div class="ifs-panel-card">
                        <div class="ifs-card-header">
                            <div class="ifs-step-num">01</div>
                            <div>
                                <h3 class="ifs-card-title"><?php echo $edit_row ? esc_html__( 'Edit Hotel Property Profile', 'ifs-travel-erp' ) : esc_html__( 'Configure New Hotel Property & Contract', 'ifs-travel-erp' ); ?></h3>
                                <p class="ifs-card-desc"><?php esc_html_e( 'Manage hotel specifications, room allocations, pricing tiers, and seasonal policies', 'ifs-travel-erp' ); ?></p>
                            </div>
                        </div>

                        <div class="ifs-grid-2">
                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_property_name"><?php esc_html_e( 'Property Name', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-building field-icon"></span>
                                    <input type="text" name="property_name" id="inp_property_name" required value="<?php echo $edit_row ? esc_attr( $edit_row->property_name ) : ''; ?>" placeholder="<?php esc_attr_e( 'e.g. Swissotel Makkah', 'ifs-travel-erp' ); ?>" class="ifs-input-field font-bold">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_property_type"><?php esc_html_e( 'Property Type', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-tag field-icon"></span>
                                    <select name="property_type" id="inp_property_type" class="ifs-input-field">
                                        <option value="Hotel" <?php selected( $edit_row ? $edit_row->property_type : '', 'Hotel' ); ?>><?php esc_html_e( 'Hotel', 'ifs-travel-erp' ); ?></option>
                                        <option value="Resort" <?php selected( $edit_row ? $edit_row->property_type : '', 'Resort' ); ?>><?php esc_html_e( 'Resort', 'ifs-travel-erp' ); ?></option>
                                        <option value="Apartment / Suite" <?php selected( $edit_row ? $edit_row->property_type : '', 'Apartment / Suite' ); ?>><?php esc_html_e( 'Apartment / Suite', 'ifs-travel-erp' ); ?></option>
                                        <option value="Camp / Tent" <?php selected( $edit_row ? $edit_row->property_type : '', 'Camp / Tent' ); ?>><?php esc_html_e( 'Camp / Tent (Mina/Arafat)', 'ifs-travel-erp' ); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_supplier_id"><?php esc_html_e( 'Supplier / DMC Partner', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-groups field-icon"></span>
                                    <select name="supplier_id" id="inp_supplier_id" class="ifs-input-field">
                                        <option value="0"><?php esc_html_e( '— Direct Contract / No Supplier —', 'ifs-travel-erp' ); ?></option>
                                        <?php if ( ! empty( $suppliers ) ) : foreach ( $suppliers as $sup ) : ?>
                                            <option value="<?php echo esc_attr( $sup->id ); ?>" <?php selected( $edit_row ? ( $edit_row->supplier_id ?? 0 ) : 0, $sup->id ); ?>>
                                                <?php echo esc_html( $sup->supplier_name ); ?>
                                            </option>
                                        <?php endforeach; endif; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_star_rating"><?php esc_html_e( 'Star Rating', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-star-filled field-icon" style="color: #d97706;"></span>
                                    <select name="star_rating" id="inp_star_rating" class="ifs-input-field">
                                        <option value="5 Star" <?php selected( $edit_row ? $edit_row->star_rating : '', '5 Star' ); ?>>5 Star</option>
                                        <option value="4 Star" <?php selected( $edit_row ? $edit_row->star_rating : '', '4 Star' ); ?>>4 Star</option>
                                        <option value="3 Star" <?php selected( $edit_row ? $edit_row->star_rating : '', '3 Star' ); ?>>3 Star</option>
                                        <option value="Economy / Standard" <?php selected( $edit_row ? $edit_row->star_rating : '', 'Economy / Standard' ); ?>>Economy / Standard</option>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_city"><?php esc_html_e( 'City', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-location field-icon"></span>
                                    <input type="text" name="city" id="inp_city" required value="<?php echo $edit_row ? esc_attr( $edit_row->city ) : ''; ?>" placeholder="<?php esc_attr_e( 'e.g. Makkah', 'ifs-travel-erp' ); ?>" class="ifs-input-field">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_country"><?php esc_html_e( 'Country', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-admin-site-alt3 field-icon"></span>
                                    <input type="text" name="country" id="inp_country" value="<?php echo $edit_row ? esc_attr( $edit_row->country ) : 'Saudi Arabia'; ?>" class="ifs-input-field">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_haram_distance"><?php esc_html_e( 'Distance From Center / Haram', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-performance field-icon"></span>
                                    <input type="text" name="haram_distance" id="inp_haram_distance" value="<?php echo $edit_row ? esc_attr( $edit_row->haram_distance ) : ''; ?>" placeholder="<?php esc_attr_e( 'e.g. 150 Meters', 'ifs-travel-erp' ); ?>" class="ifs-input-field">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_room_view_type"><?php esc_html_e( 'Primary Room View Category', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-visibility field-icon"></span>
                                    <select name="room_view_type" id="inp_room_view_type" class="ifs-input-field">
                                        <option value="Haram View" <?php selected( $edit_row ? ( $edit_row->room_view_type ?? '' ) : '', 'Haram View' ); ?>><?php esc_html_e( 'Haram View', 'ifs-travel-erp' ); ?></option>
                                        <option value="Kaaba View" <?php selected( $edit_row ? ( $edit_row->room_view_type ?? '' ) : '', 'Kaaba View' ); ?>><?php esc_html_e( 'Direct Kaaba View', 'ifs-travel-erp' ); ?></option>
                                        <option value="Sea View" <?php selected( $edit_row ? ( $edit_row->room_view_type ?? '' ) : '', 'Sea View' ); ?>><?php esc_html_e( 'Sea View / Beachfront', 'ifs-travel-erp' ); ?></option>
                                        <option value="City View" <?php selected( $edit_row ? ( $edit_row->room_view_type ?? 'City View' ) : 'City View', 'City View' ); ?>><?php esc_html_e( 'City View / Standard', 'ifs-travel-erp' ); ?></option>
                                        <option value="Back View / Courtyard" <?php selected( $edit_row ? ( $edit_row->room_view_type ?? '' ) : '', 'Back View / Courtyard' ); ?>><?php esc_html_e( 'Back View / Courtyard', 'ifs-travel-erp' ); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_default_meal_plan"><?php esc_html_e( 'Default Meal Plan', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-food field-icon"></span>
                                    <select name="default_meal_plan" id="inp_default_meal_plan" class="ifs-input-field">
                                        <option value="Room Only (RO)" <?php selected( $edit_row ? ( $edit_row->default_meal_plan ?? '' ) : '', 'Room Only (RO)' ); ?>><?php esc_html_e( 'Room Only (RO)', 'ifs-travel-erp' ); ?></option>
                                        <option value="Bed & Breakfast (BB)" <?php selected( $edit_row ? ( $edit_row->default_meal_plan ?? '' ) : 'Bed & Breakfast (BB)', 'Bed & Breakfast (BB)' ); ?>><?php esc_html_e( 'Bed & Breakfast (BB)', 'ifs-travel-erp' ); ?></option>
                                        <option value="Half Board (HB)" <?php selected( $edit_row ? ( $edit_row->default_meal_plan ?? '' ) : '', 'Half Board (HB)' ); ?>><?php esc_html_e( 'Half Board (HB)', 'ifs-travel-erp' ); ?></option>
                                        <option value="Full Board (FB)" <?php selected( $edit_row ? ( $edit_row->default_meal_plan ?? '' ) : '', 'Full Board (FB)' ); ?>><?php esc_html_e( 'Full Board (FB)', 'ifs-travel-erp' ); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_child_policy"><?php esc_html_e( 'Child Policy', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-admin-users field-icon"></span>
                                    <input type="text" name="child_policy" id="inp_child_policy" value="<?php echo $edit_row ? esc_attr( $edit_row->child_policy ?? 'Free up to 6 years' ) : 'Free up to 6 years'; ?>" placeholder="<?php esc_attr_e( 'e.g. Free up to 6 years', 'ifs-travel-erp' ); ?>" class="ifs-input-field">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_season_type"><?php esc_html_e( 'Season Type', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-calendar field-icon"></span>
                                    <select name="season_type" id="inp_season_type" class="ifs-input-field">
                                        <option value="Regular / Off-Peak" <?php selected( $edit_row ? ( $edit_row->season_type ?? '' ) : 'Regular / Off-Peak', 'Regular / Off-Peak' ); ?>><?php esc_html_e( 'Regular / Off-Peak Season', 'ifs-travel-erp' ); ?></option>
                                        <option value="Ramadan Peak" <?php selected( $edit_row ? ( $edit_row->season_type ?? '' ) : '', 'Ramadan Peak' ); ?>><?php esc_html_e( 'Ramadan Peak Season', 'ifs-travel-erp' ); ?></option>
                                        <option value="Hajj Season" <?php selected( $edit_row ? ( $edit_row->season_type ?? '' ) : '', 'Hajj Season' ); ?>><?php esc_html_e( 'Hajj Season', 'ifs-travel-erp' ); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_allocation_type"><?php esc_html_e( 'Allocation Model', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-grid-view field-icon"></span>
                                    <select name="allocation_type" id="inp_allocation_type" class="ifs-input-field">
                                        <option value="Guaranteed Block" <?php selected( $edit_row ? ( $edit_row->allocation_type ?? '' ) : 'Guaranteed Block', 'Guaranteed Block' ); ?>><?php esc_html_e( 'Guaranteed Block', 'ifs-travel-erp' ); ?></option>
                                        <option value="Free Sale / Request" <?php selected( $edit_row ? ( $edit_row->allocation_type ?? '' ) : '', 'Free Sale / Request' ); ?>><?php esc_html_e( 'Free Sale / Request Basis', 'ifs-travel-erp' ); ?></option>
                                        <option value="Release Period" <?php selected( $edit_row ? ( $edit_row->allocation_type ?? '' ) : '', 'Release Period' ); ?>><?php esc_html_e( 'Release Period Allocation', 'ifs-travel-erp' ); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_release_days"><?php esc_html_e( 'Release Period / Cut-Off (Days)', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-backup field-icon"></span>
                                    <input type="number" min="0" name="release_days" id="inp_release_days" value="<?php echo $edit_row ? esc_attr( $edit_row->release_days ?? 7 ) : '7'; ?>" class="ifs-input-field font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_total_beds"><?php esc_html_e( 'Total Beds Capacity', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-bed field-icon"></span>
                                    <input type="number" name="total_beds" id="inp_total_beds" value="<?php echo $edit_row ? esc_attr( $edit_row->total_beds ) : '0'; ?>" class="ifs-input-field font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_rate_currency"><?php esc_html_e( 'Rate Currency', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-money field-icon"></span>
                                    <select name="rate_currency" id="inp_rate_currency" class="ifs-input-field">
                                        <option value="BDT" <?php selected( $edit_row ? ( $edit_row->rate_currency ?? '' ) : 'BDT', 'BDT' ); ?>>BDT (৳)</option>
                                        <option value="SAR" <?php selected( $edit_row ? ( $edit_row->rate_currency ?? '' ) : '', 'SAR' ); ?>>SAR (﷼)</option>
                                        <option value="USD" <?php selected( $edit_row ? ( $edit_row->rate_currency ?? '' ) : '', 'USD' ); ?>>USD ($)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_contract_rate"><?php esc_html_e( 'Contract Rate (Cost)', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-tag field-icon"></span>
                                    <input type="number" step="0.01" name="contract_rate" id="inp_contract_rate" value="<?php echo $edit_row ? esc_attr( $edit_row->contract_rate ) : '0.00'; ?>" class="ifs-input-field font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_standard_sell"><?php esc_html_e( 'Standard Sell Price', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-cart field-icon" style="color: #059669;"></span>
                                    <input type="number" step="0.01" name="standard_sell" id="inp_standard_sell" value="<?php echo $edit_row ? esc_attr( $edit_row->standard_sell ) : '0.00'; ?>" class="ifs-input-field font-mono font-bold color-emerald">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_extra_bed_rate"><?php esc_html_e( 'Extra Bed Rate', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-plus field-icon"></span>
                                    <input type="number" step="0.01" name="extra_bed_rate" id="inp_extra_bed_rate" value="<?php echo $edit_row ? esc_attr( $edit_row->extra_bed_rate ?? 0 ) : '0.00'; ?>" class="ifs-input-field font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_tax_policy"><?php esc_html_e( 'Tax & Municipal VAT Policy', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-calculator field-icon"></span>
                                    <select name="tax_policy" id="inp_tax_policy" class="ifs-input-field">
                                        <option value="Inclusive of VAT & Taxes" <?php selected( $edit_row ? ( $edit_row->tax_policy ?? '' ) : 'Inclusive of VAT & Taxes', 'Inclusive of VAT & Taxes' ); ?>><?php esc_html_e( 'Inclusive of VAT & Taxes', 'ifs-travel-erp' ); ?></option>
                                        <option value="Exclusive of 15% Saudi VAT" <?php selected( $edit_row ? ( $edit_row->tax_policy ?? '' ) : '', 'Exclusive of 15% Saudi VAT' ); ?>><?php esc_html_e( 'Exclusive of 15% Saudi VAT', 'ifs-travel-erp' ); ?></option>
                                        <option value="Plus City Tax / Municipality Fee" <?php selected( $edit_row ? ( $edit_row->tax_policy ?? '' ) : '', 'Plus City Tax / Municipality Fee' ); ?>><?php esc_html_e( 'Plus City Tax / Municipality Fee', 'ifs-travel-erp' ); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_contract_start_date"><?php esc_html_e( 'Contract Start Date', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-calendar-alt field-icon"></span>
                                    <input type="date" name="contract_start_date" id="inp_contract_start_date" value="<?php echo $edit_row && $edit_row->contract_start_date !== '1970-01-01' ? esc_attr( $edit_row->contract_start_date ) : ''; ?>" class="ifs-input-field font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_contract_end_date"><?php esc_html_e( 'Contract End Date', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-calendar-alt field-icon"></span>
                                    <input type="date" name="contract_end_date" id="inp_contract_end_date" value="<?php echo $edit_row && $edit_row->contract_end_date !== '1970-01-01' ? esc_attr( $edit_row->contract_end_date ) : ''; ?>" class="ifs-input-field font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_payment_terms"><?php esc_html_e( 'Payment Terms', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-pressthis field-icon"></span>
                                    <input type="text" name="payment_terms" id="inp_payment_terms" value="<?php echo $edit_row ? esc_attr( $edit_row->payment_terms ?? '50% Advance, Balance on Arrival' ) : '50% Advance, Balance on Arrival'; ?>" class="ifs-input-field">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_checkin_time"><?php esc_html_e( 'Standard Check-in Time', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-clock field-icon"></span>
                                    <input type="text" name="checkin_time" id="inp_checkin_time" value="<?php echo $edit_row ? esc_attr( $edit_row->checkin_time ?? '14:00' ) : '14:00'; ?>" placeholder="14:00" class="ifs-input-field font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_checkout_time"><?php esc_html_e( 'Standard Check-out Time', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-clock field-icon"></span>
                                    <input type="text" name="checkout_time" id="inp_checkout_time" value="<?php echo $edit_row ? esc_attr( $edit_row->checkout_time ?? '12:00' ) : '12:00'; ?>" placeholder="12:00" class="ifs-input-field font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-block" style="grid-column: span 2;">
                                <label class="ifs-field-label" for="inp_room_types_supported"><?php esc_html_e( 'Room Categories Supported', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-layout field-icon"></span>
                                    <input type="text" name="room_types_supported" id="inp_room_types_supported" value="<?php echo $edit_row ? esc_attr( $edit_row->room_types_supported ?? 'Single, Double, Triple, Quad' ) : 'Single, Double, Triple, Quad'; ?>" class="ifs-input-field">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_contact_person"><?php esc_html_e( 'Contact Person', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-id field-icon"></span>
                                    <input type="text" name="contact_person" id="inp_contact_person" value="<?php echo $edit_row ? esc_attr( $edit_row->contact_person ) : ''; ?>" class="ifs-input-field">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_contact_phone"><?php esc_html_e( 'Contact Phone', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-phone field-icon"></span>
                                    <input type="text" name="contact_phone" id="inp_contact_phone" value="<?php echo $edit_row ? esc_attr( $edit_row->contact_phone ) : ''; ?>" class="ifs-input-field font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_emergency_phone"><?php esc_html_e( '24/7 Emergency / Desk Phone', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-sos field-icon" style="color: #dc2626;"></span>
                                    <input type="text" name="emergency_phone" id="inp_emergency_phone" value="<?php echo $edit_row ? esc_attr( $edit_row->emergency_phone ?? '' ) : ''; ?>" placeholder="+966 ..." class="ifs-input-field font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_contact_email"><?php esc_html_e( 'Official Contact Email', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-email field-icon"></span>
                                    <input type="email" name="contact_email" id="inp_contact_email" value="<?php echo $edit_row ? esc_attr( $edit_row->contact_email ?? '' ) : ''; ?>" placeholder="reservations@hotel.com" class="ifs-input-field">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_status"><?php esc_html_e( 'Contract Status', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-yes-alt field-icon" style="color: #059669;"></span>
                                    <select name="status" id="inp_status" class="ifs-input-field">
                                        <option value="Active" <?php selected( $edit_row ? ( $edit_row->status ?? '' ) : 'Active', 'Active' ); ?>><?php esc_html_e( 'Active Contract', 'ifs-travel-erp' ); ?></option>
                                        <option value="Inactive" <?php selected( $edit_row ? ( $edit_row->status ?? '' ) : '', 'Inactive' ); ?>><?php esc_html_e( 'Inactive / Expired', 'ifs-travel-erp' ); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div style="margin-top: 18px;">
                            <label class="ifs-field-label" for="inp_contract_doc_url"><?php esc_html_e( 'Agreement Document File / PDF Link', 'ifs-travel-erp' ); ?></label>
                            <div style="display: flex; gap: 10px; margin-top: 5px; align-items: center;">
                                <div class="ifs-field-wrap" style="flex: 1;">
                                    <span class="dashicons dashicons-media-document field-icon"></span>
                                    <input type="url" name="contract_doc_url" id="inp_contract_doc_url" value="<?php echo $edit_row ? esc_url( $edit_row->contract_doc_url ?? '' ) : ''; ?>" placeholder="https://..." class="ifs-input-field">
                                </div>
                                <a href="#" id="previewContractBtn" target="_blank" rel="noopener noreferrer" class="button button-secondary" style="height: 42px; display: inline-flex; align-items: center; gap: 4px; text-decoration: none; padding: 0 14px; border-radius: 8px; font-weight: 600; pointer-events: none; opacity: 0.5;">
                                    <span class="dashicons dashicons-external"></span> <?php esc_html_e( 'Preview File', 'ifs-travel-erp' ); ?>
                                </a>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr; gap: 16px; margin-top: 18px;">
                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_cancellation_policy"><?php esc_html_e( 'Cancellation Policy & Deadlines', 'ifs-travel-erp' ); ?></label>
                                <textarea name="cancellation_policy" id="inp_cancellation_policy" rows="2" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; outline: none;" placeholder="<?php esc_attr_e( 'e.g. Free cancellation up to 7 days before check-in...', 'ifs-travel-erp' ); ?>"><?php echo $edit_row ? esc_textarea( $edit_row->cancellation_policy ?? '' ) : ''; ?></textarea>
                            </div>
                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_blackout_notes"><?php esc_html_e( 'Blackout Dates & Peak Season Surcharges', 'ifs-travel-erp' ); ?></label>
                                <textarea name="blackout_dates_notes" id="inp_blackout_notes" rows="2" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; outline: none;" placeholder="<?php esc_attr_e( 'e.g. Last 10 days of Ramadan: +30% surcharge; 10-15 Dhul Hijjah: strictly non-refundable.', 'ifs-travel-erp' ); ?>"><?php echo $edit_row ? esc_textarea( $edit_row->blackout_dates_notes ?? '' ) : ''; ?></textarea>
                            </div>
                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_bank_details"><?php esc_html_e( 'Hotel Bank & Remittance Wire Details', 'ifs-travel-erp' ); ?></label>
                                <textarea name="bank_details" id="inp_bank_details" rows="2" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; outline: none; font-family: monospace;" placeholder="<?php esc_attr_e( 'Beneficiary Name, Bank Name, IBAN / Account Number, SWIFT Code...', 'ifs-travel-erp' ); ?>"><?php echo $edit_row ? esc_textarea( $edit_row->bank_details ?? '' ) : ''; ?></textarea>
                            </div>
                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_address"><?php esc_html_e( 'Full Physical Address', 'ifs-travel-erp' ); ?></label>
                                <textarea name="address" id="inp_address" rows="2" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; outline: none;" placeholder="<?php esc_attr_e( 'Street address, district, postal code...', 'ifs-travel-erp' ); ?>"><?php echo $edit_row ? esc_textarea( $edit_row->address ) : ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Section 02: Amenities Dynamic Repeater -->
                    <div class="ifs-panel-card">
                        <div class="ifs-card-header" style="justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                            <div style="display: flex; align-items: center; gap: 14px;">
                                <div class="ifs-step-num">02</div>
                                <div>
                                    <h3 class="ifs-card-title"><?php esc_html_e( 'Amenities & Services (Dynamic Repeater)', 'ifs-travel-erp' ); ?></h3>
                                    <p class="ifs-card-desc"><?php esc_html_e( 'Add, remove, or modify property facilities and hospitality perks', 'ifs-travel-erp' ); ?></p>
                                </div>
                            </div>
                            <button type="button" class="ifs-btn-add-row" id="btnAddAmenity">
                                <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Amenity Item', 'ifs-travel-erp' ); ?>
                            </button>
                        </div>

                        <div id="amenitiesRepeaterContainer" class="repeater-items-container">
                            <?php foreach ( $amenities_list as $amenity_item ) : ?>
                                <div class="repeater-row">
                                    <span class="row-indicator inc">✔</span>
                                    <input type="text" name="amenities_repeater[]" value="<?php echo esc_attr( $amenity_item ); ?>" placeholder="<?php esc_attr_e( 'e.g. Free High-Speed Wi-Fi', 'ifs-travel-erp' ); ?>" class="ifs-input-field">
                                    <button type="button" class="btn-remove-row" title="<?php esc_attr_e( 'Remove', 'ifs-travel-erp' ); ?>">&times;</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="ifs-action-strip">
                        <a href="<?php echo esc_url( add_query_arg( 'hotel_sub', 'all', $base_url ) ); ?>" class="ifs-btn-back">
                            <span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'Cancel Form', 'ifs-travel-erp' ); ?>
                        </a>
                        <button type="submit" name="ifs_hotel_prop_submit" class="ifs-btn-primary">
                            <span class="dashicons dashicons-saved"></span> 
                            <?php echo $edit_row ? esc_html__( 'Update Property Contract', 'ifs-travel-erp' ) : esc_html__( 'Save Property Contract', 'ifs-travel-erp' ); ?>
                        </button>
                    </div>
                </div>

                <!-- Right Sidebar: Live Enterprise Hotel Contract Card Preview -->
                <div class="ifs-preview-sidebar">
                    <div class="ifs-preview-sticky">
                        <div class="ifs-card-preview-header">
                            <span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Live Property Contract Card Preview', 'ifs-travel-erp' ); ?>
                        </div>

                        <div class="ifs-advisory-card">
                            <div class="advisory-watermark"><span class="dashicons dashicons-building"></span></div>
                            
                            <div class="advisory-head-strip">
                                <span class="advisory-badge-tag"><span class="dashicons dashicons-shield-alt"></span> <?php esc_html_e( 'CONTRACTED ACCOMMODATION', 'ifs-travel-erp' ); ?></span>
                                <span class="advisory-time-tag" id="prev_star"><span class="dashicons dashicons-star-filled"></span> <?php echo esc_html( strtoupper( $edit_row ? $edit_row->star_rating : '4 STAR' ) ); ?></span>
                            </div>

                            <div class="advisory-hero">
                                <h3 class="advisory-country" id="prev_prop_name"><?php echo esc_html( strtoupper( $edit_row ? $edit_row->property_name : 'SWISSOTEL MAKKAH' ) ); ?></h3>
                                <span class="advisory-category" id="prev_location"><?php echo esc_html( strtoupper( ( $edit_row ? $edit_row->city : 'MAKKAH' ) . ', ' . ( $edit_row ? $edit_row->country : 'SAUDI ARABIA' ) ) ); ?></span>
                                <div class="advisory-meta-pills">
                                    <span class="advisory-mode-badge" id="prev_season"><span class="dashicons dashicons-calendar"></span> <?php echo esc_html( $edit_row ? ( $edit_row->season_type ?? 'Regular Season' ) : 'Regular Season' ); ?></span>
                                    <span class="advisory-validity-badge" id="prev_allocation"><span class="dashicons dashicons-grid-view"></span> <?php echo esc_html( $edit_row ? ( $edit_row->allocation_type ?? 'Guaranteed Block' ) : 'Guaranteed Block' ); ?></span>
                                    <span class="advisory-mode-badge" id="prev_view"><span class="dashicons dashicons-visibility"></span> <?php echo esc_html( $edit_row ? ( $edit_row->room_view_type ?? 'City View' ) : 'City View' ); ?></span>
                                </div>
                            </div>

                            <div class="advisory-fee-box">
                                <div style="display: flex; flex-direction: column;">
                                    <span class="advisory-fee-lbl"><?php esc_html_e( 'CONTRACT COST VS SELL', 'ifs-travel-erp' ); ?></span>
                                    <span class="advisory-fee-sub" id="prev_rate_breakdown">Cost: <?php echo esc_html( ( $edit_row ? ( $edit_row->rate_currency ?? 'BDT' ) : 'BDT' ) . ' ' . number_format( (float) ( $edit_row ? $edit_row->contract_rate : 12000 ) ) ); ?> | Sell: <?php echo esc_html( ( $edit_row ? ( $edit_row->rate_currency ?? 'BDT' ) : 'BDT' ) . ' ' . number_format( (float) ( $edit_row ? $edit_row->standard_sell : 15000 ) ) ); ?></span>
                                </div>
                                <h4 class="advisory-fee-val font-mono" id="prev_sell_price"><?php echo esc_html( ( $edit_row ? ( $edit_row->rate_currency ?? 'BDT' ) : 'BDT' ) . ' ' . number_format( (float) ( $edit_row ? $edit_row->standard_sell : 15000 ), 2 ) ); ?></h4>
                            </div>

                            <div class="advisory-checklist-box">
                                <span class="checklist-head"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'CONTRACTED AMENITIES:', 'ifs-travel-erp' ); ?></span>
                                <div class="checklist-content" id="prev_amenities_list">
                                    1. High-Speed Wi-Fi<br>
                                    2. Daily Buffet Breakfast<br>
                                    3. Airport Shuttle Service
                                </div>
                            </div>

                            <div class="advisory-footer-strip">
                                <span><span class="dashicons dashicons-saved" style="color: #38bdf8; font-size: 13px; vertical-align: middle;"></span> <?php esc_html_e( 'ERP Verified Partner', 'ifs-travel-erp' ); ?></span>
                                <span class="live-pulse-dot" title="<?php esc_attr_e( 'Live Sync Active', 'ifs-travel-erp' ); ?>"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#btnAddAmenity').on('click', function(e) {
                e.preventDefault();
                const rowHtml = `
                    <div class="repeater-row">
                        <span class="row-indicator inc">✔</span>
                        <input type="text" name="amenities_repeater[]" placeholder="<?php echo esc_js( __( 'e.g. 24/7 Concierge', 'ifs-travel-erp' ) ); ?>" class="ifs-input-field">
                        <button type="button" class="btn-remove-row" title="<?php echo esc_js( __( 'Remove', 'ifs-travel-erp' ) ); ?>">&times;</button>
                    </div>
                `;
                $('#amenitiesRepeaterContainer').append(rowHtml);
                updateHotelPreviewCard();
            });

            $(document).on('click', '.btn-remove-row', function(e) {
                e.preventDefault();
                const container = $(this).closest('.repeater-items-container');
                if (container.find('.repeater-row').length > 1) {
                    $(this).closest('.repeater-row').remove();
                } else {
                    $(this).closest('.repeater-row').find('input').val('');
                }
                updateHotelPreviewCard();
            });

            const docUrlInput = $('#inp_contract_doc_url');
            const previewBtn  = $('#previewContractBtn');

            function updateDocPreview() {
                const val = docUrlInput.val().trim();
                if (val && val.startsWith('http')) {
                    previewBtn.attr('href', val).css({ 'pointer-events': 'auto', 'opacity': '1' });
                } else {
                    previewBtn.attr('href', '#').css({ 'pointer-events': 'none', 'opacity': '0.5' });
                }
            }

            docUrlInput.on('input change keyup', updateDocPreview);
            updateDocPreview();

            function updateHotelPreviewCard() {
                const propName = $('#inp_property_name').val().trim();
                const city     = $('#inp_city').val().trim();
                const country  = $('#inp_country').val().trim();
                const star     = $('#inp_star_rating').val();
                const season   = $('#inp_season_type').val();
                const alloc    = $('#inp_allocation_type').val();
                const view     = $('#inp_room_view_type').val();
                const curr     = $('#inp_rate_currency').val();
                const rate     = parseFloat($('#inp_contract_rate').val()) || 0;
                const sell     = parseFloat($('#inp_standard_sell').val()) || 0;

                $('#prev_prop_name').text(propName ? propName.toUpperCase() : 'SWISSOTEL MAKKAH');
                $('#prev_location').text(((city || 'MAKKAH') + ', ' + (country || 'SAUDI ARABIA')).toUpperCase());
                $('#prev_star').html('<span class="dashicons dashicons-star-filled"></span> ' + (star || '4 STAR').toUpperCase());
                $('#prev_season').html('<span class="dashicons dashicons-calendar"></span> ' + (season || 'Regular Season'));
                $('#prev_allocation').html('<span class="dashicons dashicons-grid-view"></span> ' + (alloc || 'Guaranteed Block'));
                $('#prev_view').html('<span class="dashicons dashicons-visibility"></span> ' + (view || 'City View'));

                $('#prev_sell_price').text(curr + ' ' + sell.toLocaleString('en-US', { minimumFractionDigits: 2 }));
                $('#prev_rate_breakdown').text('Cost: ' + curr + ' ' + rate.toLocaleString('en-US') + ' | Sell: ' + curr + ' ' + sell.toLocaleString('en-US'));

                const items = [];
                $('input[name="amenities_repeater[]"]').each(function() {
                    const val = $(this).val().trim();
                    if (val) items.push(val);
                });

                if (items.length > 0) {
                    $('#prev_amenities_list').html(items.map((item, idx) => (idx + 1) + '. ' + item).join('<br>'));
                } else {
                    $('#prev_amenities_list').text('<?php echo esc_js( __( 'No amenities specified.', 'ifs-travel-erp' ) ); ?>');
                }
            }

            $(document).on('input change keyup', '#inp_property_name, #inp_city, #inp_country, #inp_star_rating, #inp_season_type, #inp_allocation_type, #inp_room_view_type, #inp_rate_currency, #inp_contract_rate, #inp_standard_sell, input[name="amenities_repeater[]"]', updateHotelPreviewCard);

            updateHotelPreviewCard();
        });
        </script>
        <?php
    }
}