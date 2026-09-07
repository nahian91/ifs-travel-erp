<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_tour_plan_form_panel' ) ) {
    /**
     * Holiday & Tour Package Plan Creation & Editing Panel (Enhanced)
     */
    function ifs_terp_tour_plan_form_panel() {
        global $wpdb;
        $table_plans     = $wpdb->prefix . 'iterp_tour_packages';
        $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
        $base_url        = admin_url( 'admin.php?page=ifs_travel_erp&tab=settings&sub=tour_pkg' );
        $message         = '';

        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_tour_plan_submit'] ) ) {
            check_admin_referer( 'ifs_tour_plan_action', 'ifs_tour_plan_nonce' );

            $edit_plan_id = isset( $_POST['edit_plan_id'] ) ? absint( wp_unslash( $_POST['edit_plan_id'] ) ) : 0;
            $is_plan_edit = ( $edit_plan_id > 0 );

            $amenities_input = isset( $_POST['standard_amenities'] ) && is_array( $_POST['standard_amenities'] ) ? (array) wp_unslash( $_POST['standard_amenities'] ) : array();
            $amenity_fees    = isset( $_POST['amenity_fees'] ) && is_array( $_POST['amenity_fees'] ) ? (array) wp_unslash( $_POST['amenity_fees'] ) : array();
            
            $formatted_amenities = array();
            foreach ( $amenities_input as $amenity_name ) {
                $sanitized_name = sanitize_text_field( $amenity_name );
                $fee_val = isset( $amenity_fees[ $sanitized_name ] ) ? (float) $amenity_fees[ $sanitized_name ] : 0.00;
                $formatted_amenities[ $sanitized_name ] = $fee_val;
            }
            $inclusions_std = wp_json_encode( $formatted_amenities );

            $raw_inclusions   = isset( $_POST['inclusions_repeater'] ) && is_array( $_POST['inclusions_repeater'] ) ? (array) wp_unslash( $_POST['inclusions_repeater'] ) : array();
            $clean_inclusions = array_values( array_filter( array_map( 'sanitize_text_field', $raw_inclusions ) ) );
            $inclusions_json  = wp_json_encode( $clean_inclusions );

            $raw_exclusions   = isset( $_POST['exclusions_repeater'] ) && is_array( $_POST['exclusions_repeater'] ) ? (array) wp_unslash( $_POST['exclusions_repeater'] ) : array();
            $clean_exclusions = array_values( array_filter( array_map( 'sanitize_text_field', $raw_exclusions ) ) );
            $exclusions_json  = wp_json_encode( $clean_exclusions );

            // Daily Itinerary Matrix Sanitization
            $itinerary_titles = isset( $_POST['itinerary_title'] ) && is_array( $_POST['itinerary_title'] ) ? (array) wp_unslash( $_POST['itinerary_title'] ) : array();
            $itinerary_descs  = isset( $_POST['itinerary_desc'] ) && is_array( $_POST['itinerary_desc'] ) ? (array) wp_unslash( $_POST['itinerary_desc'] ) : array();
            $itinerary_list   = array();

            foreach ( $itinerary_titles as $day_idx => $day_title ) {
                $day_title_clean = sanitize_text_field( $day_title );
                if ( ! empty( $day_title_clean ) ) {
                    $itinerary_list[] = array(
                        'day'   => $day_idx + 1,
                        'title' => $day_title_clean,
                        'desc'  => isset( $itinerary_descs[ $day_idx ] ) ? sanitize_textarea_field( $itinerary_descs[ $day_idx ] ) : '',
                    );
                }
            }
            $itinerary_json = wp_json_encode( $itinerary_list );

            $plan_data = array(
                'package_code'        => isset( $_POST['package_code'] ) ? sanitize_text_field( wp_unslash( $_POST['package_code'] ) ) : '',
                'package_name'        => isset( $_POST['package_name'] ) ? sanitize_text_field( wp_unslash( $_POST['package_name'] ) ) : '',
                'package_type'        => isset( $_POST['package_type'] ) ? sanitize_text_field( wp_unslash( $_POST['package_type'] ) ) : 'Holiday Tour',
                'season_type'         => isset( $_POST['season_type'] ) ? sanitize_text_field( wp_unslash( $_POST['season_type'] ) ) : 'Regular / Off-Peak',
                'destination'         => isset( $_POST['destination'] ) ? sanitize_text_field( wp_unslash( $_POST['destination'] ) ) : '',
                'departure_point'     => isset( $_POST['departure_point'] ) ? sanitize_text_field( wp_unslash( $_POST['departure_point'] ) ) : '',
                'transport_mode'      => isset( $_POST['transport_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['transport_mode'] ) ) : 'Tourist Coach / Bus',
                'supplier_id'         => isset( $_POST['supplier_id'] ) ? absint( wp_unslash( $_POST['supplier_id'] ) ) : 0,
                'difficulty_level'    => isset( $_POST['difficulty_level'] ) ? sanitize_text_field( wp_unslash( $_POST['difficulty_level'] ) ) : 'Easy / Leisure',
                'total_days'          => isset( $_POST['total_days'] ) ? intval( wp_unslash( $_POST['total_days'] ) ) : 3,
                'total_nights'        => isset( $_POST['total_nights'] ) ? intval( wp_unslash( $_POST['total_nights'] ) ) : 2,
                'min_pax'             => isset( $_POST['min_pax'] ) ? intval( wp_unslash( $_POST['min_pax'] ) ) : 2,
                'max_pax'             => isset( $_POST['max_pax'] ) ? intval( wp_unslash( $_POST['max_pax'] ) ) : 40,
                'valid_till'          => ! empty( $_POST['valid_till'] ) ? sanitize_text_field( wp_unslash( $_POST['valid_till'] ) ) : '1970-01-01',
                'rate_currency'       => isset( $_POST['rate_currency'] ) ? sanitize_text_field( wp_unslash( $_POST['rate_currency'] ) ) : 'BDT',
                'cost_bdt'            => isset( $_POST['cost_bdt'] ) ? (float) wp_unslash( $_POST['cost_bdt'] ) : 0,
                'selling_price'       => isset( $_POST['selling_price'] ) ? (float) wp_unslash( $_POST['selling_price'] ) : 0,
                'price_child'         => isset( $_POST['price_child'] ) ? (float) wp_unslash( $_POST['price_child'] ) : 0,
                'price_infant'        => isset( $_POST['price_infant'] ) ? (float) wp_unslash( $_POST['price_infant'] ) : 0,
                'hotel_name'          => isset( $_POST['hotel_name'] ) ? sanitize_text_field( wp_unslash( $_POST['hotel_name'] ) ) : '',
                'child_policy'        => isset( $_POST['child_policy'] ) ? sanitize_text_field( wp_unslash( $_POST['child_policy'] ) ) : '',
                'package_image_url'   => isset( $_POST['package_image_url'] ) ? esc_url_raw( wp_unslash( $_POST['package_image_url'] ) ) : '',
                'payment_policy'      => isset( $_POST['payment_policy'] ) ? sanitize_textarea_field( wp_unslash( $_POST['payment_policy'] ) ) : '',
                'admin_notes'         => isset( $_POST['admin_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['admin_notes'] ) ) : '',
                'itinerary_json'      => $itinerary_json,
                'inclusions_standard' => $inclusions_std,
                'inclusions_text'     => $inclusions_json,
                'exclusions_text'     => $exclusions_json,
            );

            if ( empty( $plan_data['package_name'] ) || empty( $plan_data['destination'] ) ) {
                $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> ' . esc_html__( 'Package Title and Destination are required.', 'ifs-travel-erp' ) . '</div>';
            } else {
                if ( $is_plan_edit ) {
                    $wpdb->update( $table_plans, $plan_data, array( 'id' => $edit_plan_id ) );
                    $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Tour Package Plan Updated Successfully.', 'ifs-travel-erp' ) . '</div>';
                } else {
                    $plan_data['created_at'] = current_time( 'mysql' );
                    $wpdb->insert( $table_plans, $plan_data );
                    $edit_plan_id = $wpdb->insert_id;
                    $is_plan_edit = true;
                    $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'New Tour Package Template Created Successfully.', 'ifs-travel-erp' ) . '</div>';
                }
            }
        }

        $plan_id        = isset( $_GET['plan_id'] ) ? absint( wp_unslash( $_GET['plan_id'] ) ) : ( ! empty( $edit_plan_id ) ? $edit_plan_id : 0 );
        $edit_plan_data = $plan_id > 0 ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_plans} WHERE id = %d", $plan_id ) ) : false;

        $val_p_code    = $edit_plan_data ? esc_attr( $edit_plan_data->package_code ?? '' ) : 'PKG-' . strtoupper( wp_generate_password( 5, false ) );
        $val_p_name    = $edit_plan_data ? esc_attr( $edit_plan_data->package_name ?? '' ) : '';
        $val_p_type    = $edit_plan_data ? esc_attr( $edit_plan_data->package_type ?? 'Holiday Tour' ) : 'Holiday Tour';
        $val_p_season  = $edit_plan_data ? esc_attr( $edit_plan_data->season_type ?? 'Regular / Off-Peak' ) : 'Regular / Off-Peak';
        $val_p_dest    = $edit_plan_data ? esc_attr( $edit_plan_data->destination ?? '' ) : '';
        $val_p_dept    = $edit_plan_data ? esc_attr( $edit_plan_data->departure_point ?? '' ) : '';
        $val_p_trans   = $edit_plan_data ? esc_attr( $edit_plan_data->transport_mode ?? 'Tourist Coach / Bus' ) : 'Tourist Coach / Bus';
        $val_p_sup     = $edit_plan_data ? absint( $edit_plan_data->supplier_id ?? 0 ) : 0;
        $val_p_diff    = $edit_plan_data ? esc_attr( $edit_plan_data->difficulty_level ?? 'Easy / Leisure' ) : 'Easy / Leisure';
        $val_p_days    = $edit_plan_data ? intval( $edit_plan_data->total_days ?? 3 ) : 3;
        $val_p_nights  = $edit_plan_data ? intval( $edit_plan_data->total_nights ?? 2 ) : 2;
        $val_p_pax     = $edit_plan_data ? intval( $edit_plan_data->min_pax ?? 2 ) : 2;
        $val_p_max_pax = $edit_plan_data ? intval( $edit_plan_data->max_pax ?? 40 ) : 40;
        $val_p_valid   = $edit_plan_data && $edit_plan_data->valid_till !== '1970-01-01' ? esc_attr( $edit_plan_data->valid_till ) : '';
        $val_p_curr    = $edit_plan_data ? esc_attr( $edit_plan_data->rate_currency ?? 'BDT' ) : 'BDT';
        $val_p_cost    = $edit_plan_data ? (float) ( $edit_plan_data->cost_bdt ?? 0 ) : '';
        $val_p_sell    = $edit_plan_data ? (float) ( $edit_plan_data->selling_price ?? 0 ) : '';
        $val_p_child   = $edit_plan_data ? (float) ( $edit_plan_data->price_child ?? 0 ) : '0.00';
        $val_p_infant  = $edit_plan_data ? (float) ( $edit_plan_data->price_infant ?? 0 ) : '0.00';
        $val_p_hotel   = $edit_plan_data ? esc_attr( $edit_plan_data->hotel_name ?? '' ) : '';
        $val_p_cpolicy = $edit_plan_data ? esc_attr( $edit_plan_data->child_policy ?? '' ) : '';
        $val_p_img     = $edit_plan_data ? esc_url( $edit_plan_data->package_image_url ?? '' ) : '';
        $val_p_pay     = $edit_plan_data ? esc_textarea( $edit_plan_data->payment_policy ?? '' ) : '';
        $val_p_notes   = $edit_plan_data ? esc_textarea( $edit_plan_data->admin_notes ?? '' ) : '';

        $active_amenities = array();
        if ( $edit_plan_data && ! empty( $edit_plan_data->inclusions_standard ) ) {
            $decoded_amenities = json_decode( $edit_plan_data->inclusions_standard, true );
            if ( is_array( $decoded_amenities ) ) {
                $active_amenities = $decoded_amenities;
            }
        }

        $inclusions_list = array();
        if ( $edit_plan_data && ! empty( $edit_plan_data->inclusions_text ) ) {
            $decoded = json_decode( $edit_plan_data->inclusions_text, true );
            if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
                $inclusions_list = $decoded;
            } else {
                $inclusions_list = array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", '', $edit_plan_data->inclusions_text ) ) ) );
            }
        }
        if ( empty( $inclusions_list ) ) {
            $inclusions_list = array( 'AC / Non-AC Return Transport', 'Hotel Accommodation on Sharing Basis', 'Daily Breakfast & Set Dinner' );
        }

        $exclusions_list = array();
        if ( $edit_plan_data && ! empty( $edit_plan_data->exclusions_text ) ) {
            $decoded = json_decode( $edit_plan_data->exclusions_text, true );
            if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
                $exclusions_list = $decoded;
            } else {
                $exclusions_list = array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", '', $edit_plan_data->exclusions_text ) ) ) );
            }
        }
        if ( empty( $exclusions_list ) ) {
            $exclusions_list = array( 'Personal Expenses & Tips', 'Entry Fees to Monuments unless specified' );
        }

        $itinerary_saved = array();
        if ( $edit_plan_data && ! empty( $edit_plan_data->itinerary_json ) ) {
            $dec_itn = json_decode( $edit_plan_data->itinerary_json, true );
            if ( is_array( $dec_itn ) ) {
                $itinerary_saved = $dec_itn;
            }
        }
        if ( empty( $itinerary_saved ) ) {
            $itinerary_saved = array(
                array( 'day' => 1, 'title' => 'Arrival & Hotel Check-in', 'desc' => 'Meet and greet upon arrival, airport/station transfer to hotel, evening at leisure.' ),
                array( 'day' => 2, 'title' => 'Full Day Sightseeing Excursion', 'desc' => 'Guided tour covering prominent local landmarks, attractions, and cultural points of interest.' ),
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

            <form method="post" action="" class="ifs-split-editor">
                <?php wp_nonce_field( 'ifs_tour_plan_action', 'ifs_tour_plan_nonce' ); ?>
                <?php if ( $edit_plan_data ) : ?>
                    <input type="hidden" name="edit_plan_id" value="<?php echo esc_attr( $edit_plan_data->id ); ?>">
                <?php endif; ?>

                <div class="ifs-form-body">
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 26px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);">
                        <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 22px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9;">
                            <div style="width: 34px; height: 34px; border-radius: 9px; background: linear-gradient(135deg, #003376 0%, #0284c7 100%); color: #ffffff; font-weight: 800; font-size: 14px; display: flex; align-items: center; justify-content: center;">
                                <span class="dashicons dashicons-location"></span>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 15.5px; font-weight: 800; color: #0f172a;">
                                    <?php echo $edit_plan_data ? esc_html__( 'Edit Tour Package Template', 'ifs-travel-erp' ) : esc_html__( 'Create New Tour Package Template', 'ifs-travel-erp' ); ?>
                                </h3>
                                <p style="margin: 2px 0 0 0; font-size: 12.5px; color: #64748b;"><?php esc_html_e( 'Configure itineraries, durations, costings, transport logistics, and daily schedules', 'ifs-travel-erp' ); ?></p>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px;">
                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Package Code / SKU', 'ifs-travel-erp' ); ?></label>
                                <input type="text" name="package_code" id="inp_p_code" value="<?php echo esc_attr( $val_p_code ); ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-family: monospace; font-weight: 700;">
                            </div>

                            <div style="grid-column: span 2;">
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Package Title', 'ifs-travel-erp' ); ?> <span style="color: #e11d48;">*</span></label>
                                <input type="text" name="package_name" id="inp_p_name" required value="<?php echo esc_attr( $val_p_name ); ?>" placeholder="<?php esc_attr_e( 'e.g. 3D/2N Cox\'s Bazar Sea View Escape', 'ifs-travel-erp' ); ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-weight: 700;">
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Package Category', 'ifs-travel-erp' ); ?></label>
                                <select name="package_type" id="inp_p_type" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                                    <option value="Domestic Holiday" <?php selected( $val_p_type, 'Domestic Holiday' ); ?>><?php esc_html_e( 'Domestic Holiday', 'ifs-travel-erp' ); ?></option>
                                    <option value="International Tour" <?php selected( $val_p_type, 'International Tour' ); ?>><?php esc_html_e( 'International Tour', 'ifs-travel-erp' ); ?></option>
                                    <option value="Honeymoon Special" <?php selected( $val_p_type, 'Honeymoon Special' ); ?>><?php esc_html_e( 'Honeymoon Special', 'ifs-travel-erp' ); ?></option>
                                    <option value="Family Holiday" <?php selected( $val_p_type, 'Family Holiday' ); ?>><?php esc_html_e( 'Family Holiday Package', 'ifs-travel-erp' ); ?></option>
                                    <option value="Group Tour" <?php selected( $val_p_type, 'Group Tour' ); ?>><?php esc_html_e( 'Group Tour', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Season Type', 'ifs-travel-erp' ); ?></label>
                                <select name="season_type" id="inp_p_season" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                                    <option value="Regular / Off-Peak" <?php selected( $val_p_season, 'Regular / Off-Peak' ); ?>><?php esc_html_e( 'Regular / Off-Peak Season', 'ifs-travel-erp' ); ?></option>
                                    <option value="Peak Season" <?php selected( $val_p_season, 'Peak Season' ); ?>><?php esc_html_e( 'Peak Season', 'ifs-travel-erp' ); ?></option>
                                    <option value="Holiday / Eid Special" <?php selected( $val_p_season, 'Holiday / Eid Special' ); ?>><?php esc_html_e( 'Holiday / Eid Special', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Destination', 'ifs-travel-erp' ); ?> <span style="color: #e11d48;">*</span></label>
                                <input type="text" name="destination" id="inp_p_dest" required value="<?php echo esc_attr( $val_p_dest ); ?>" placeholder="<?php esc_attr_e( 'e.g. Cox\'s Bazar', 'ifs-travel-erp' ); ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; text-transform: uppercase;">
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Departure Point / Pickup', 'ifs-travel-erp' ); ?></label>
                                <input type="text" name="departure_point" id="inp_p_dept" value="<?php echo esc_attr( $val_p_dept ); ?>" placeholder="<?php esc_attr_e( 'e.g. Dhaka (Fakirapool)', 'ifs-travel-erp' ); ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Primary Transport Mode', 'ifs-travel-erp' ); ?></label>
                                <select name="transport_mode" id="inp_p_trans" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                                    <option value="Tourist Coach / Bus" <?php selected( $val_p_trans, 'Tourist Coach / Bus' ); ?>><?php esc_html_e( 'Tourist Coach / Bus', 'ifs-travel-erp' ); ?></option>
                                    <option value="Air / Flight Tickets Included" <?php selected( $val_p_trans, 'Air / Flight Tickets Included' ); ?>><?php esc_html_e( 'Air / Flight Tickets Included', 'ifs-travel-erp' ); ?></option>
                                    <option value="Private Sedan / SUV" <?php selected( $val_p_trans, 'Private Sedan / SUV' ); ?>><?php esc_html_e( 'Private Sedan / SUV', 'ifs-travel-erp' ); ?></option>
                                    <option value="Train / Rail" <?php selected( $val_p_trans, 'Train / Rail' ); ?>><?php esc_html_e( 'Train / Rail Transit', 'ifs-travel-erp' ); ?></option>
                                    <option value="Cruise / Ship" <?php selected( $val_p_trans, 'Cruise / Ship' ); ?>><?php esc_html_e( 'Cruise / Ship', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Assigned Supplier / DMC', 'ifs-travel-erp' ); ?></label>
                                <select name="supplier_id" id="inp_p_supplier" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                                    <option value="0"><?php esc_html_e( 'Direct Operation / None', 'ifs-travel-erp' ); ?></option>
                                    <?php 
                                    $suppliers = $wpdb->get_results( "SELECT id, supplier_name FROM {$table_suppliers} ORDER BY supplier_name ASC" );
                                    if ( ! empty( $suppliers ) ) {
                                        foreach ( $suppliers as $sup ) {
                                            echo '<option value="' . esc_attr( $sup->id ) . '" ' . selected( $val_p_sup, $sup->id, false ) . '>' . esc_html( $sup->supplier_name ) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Activity / Difficulty Level', 'ifs-travel-erp' ); ?></label>
                                <select name="difficulty_level" id="inp_p_diff" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                                    <option value="Easy / Leisure" <?php selected( $val_p_diff, 'Easy / Leisure' ); ?>><?php esc_html_e( 'Easy / Leisure', 'ifs-travel-erp' ); ?></option>
                                    <option value="Moderate" <?php selected( $val_p_diff, 'Moderate' ); ?>><?php esc_html_e( 'Moderate Activity', 'ifs-travel-erp' ); ?></option>
                                    <option value="Adventurous / Trekking" <?php selected( $val_p_diff, 'Adventurous / Trekking' ); ?>><?php esc_html_e( 'Adventurous / Trekking', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Duration (Days / Nights)', 'ifs-travel-erp' ); ?></label>
                                <div style="display: flex; gap: 8px;">
                                    <input type="number" min="1" name="total_days" id="inp_p_days" value="<?php echo esc_attr( $val_p_days ); ?>" placeholder="<?php esc_attr_e( 'Days', 'ifs-travel-erp' ); ?>" style="width: 50%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-family: monospace;">
                                    <input type="number" min="0" name="total_nights" id="inp_p_nights" value="<?php echo esc_attr( $val_p_nights ); ?>" placeholder="<?php esc_attr_e( 'Nights', 'ifs-travel-erp' ); ?>" style="width: 50%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-family: monospace;">
                                </div>
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Group Capacity (Min / Max Pax)', 'ifs-travel-erp' ); ?></label>
                                <div style="display: flex; gap: 8px;">
                                    <input type="number" min="1" name="min_pax" id="inp_p_pax" value="<?php echo esc_attr( $val_p_pax ); ?>" placeholder="<?php esc_attr_e( 'Min Pax', 'ifs-travel-erp' ); ?>" style="width: 50%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-family: monospace;">
                                    <input type="number" min="1" name="max_pax" id="inp_p_max_pax" value="<?php echo esc_attr( $val_p_max_pax ); ?>" placeholder="<?php esc_attr_e( 'Max Pax', 'ifs-travel-erp' ); ?>" style="width: 50%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-family: monospace;">
                                </div>
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Partner Hotel / Resort', 'ifs-travel-erp' ); ?></label>
                                <input type="text" name="hotel_name" id="inp_p_hotel" value="<?php echo esc_attr( $val_p_hotel ); ?>" placeholder="<?php esc_attr_e( 'e.g. Hotel Sea Crown', 'ifs-travel-erp' ); ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Child Policy', 'ifs-travel-erp' ); ?></label>
                                <input type="text" name="child_policy" id="inp_p_child" value="<?php echo esc_attr( $val_p_cpolicy ); ?>" placeholder="<?php esc_attr_e( 'e.g. Free up to 5 years', 'ifs-travel-erp' ); ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Package Validity Expiry', 'ifs-travel-erp' ); ?></label>
                                <input type="date" name="valid_till" id="inp_p_valid" value="<?php echo esc_attr( $val_p_valid ); ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Pricing Quote Currency', 'ifs-travel-erp' ); ?></label>
                                <select name="rate_currency" id="inp_p_curr" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                                    <option value="BDT" <?php selected( $val_p_curr, 'BDT' ); ?>>BDT (৳)</option>
                                    <option value="USD" <?php selected( $val_p_curr, 'USD' ); ?>>USD ($)</option>
                                    <option value="EUR" <?php selected( $val_p_curr, 'EUR' ); ?>>EUR (€)</option>
                                    <option value="SAR" <?php selected( $val_p_curr, 'SAR' ); ?>>SAR (﷼)</option>
                                    <option value="AED" <?php selected( $val_p_curr, 'AED' ); ?>>AED (د.إ)</option>
                                </select>
                            </div>

                            <!-- Pricing Tiers: Cost, Selling, Child, Infant -->
                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Cost Rate (Adult)', 'ifs-travel-erp' ); ?></label>
                                <input type="number" step="0.01" name="cost_bdt" id="inp_p_cost" value="<?php echo esc_attr( $val_p_cost ); ?>" placeholder="0.00" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-family: monospace;">
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Base Selling Price (Adult)', 'ifs-travel-erp' ); ?> <span style="color: #e11d48;">*</span></label>
                                <input type="number" step="0.01" name="selling_price" id="inp_p_sell" required value="<?php echo esc_attr( $val_p_sell ); ?>" placeholder="0.00" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-family: monospace; font-weight: 700; color: #059669;">
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Child Sharing Price', 'ifs-travel-erp' ); ?></label>
                                <input type="number" step="0.01" name="price_child" id="inp_p_price_child" value="<?php echo esc_attr( $val_p_child ); ?>" placeholder="0.00" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-family: monospace;">
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Infant Rate', 'ifs-travel-erp' ); ?></label>
                                <input type="number" step="0.01" name="price_infant" id="inp_p_price_infant" value="<?php echo esc_attr( $val_p_infant ); ?>" placeholder="0.00" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-family: monospace;">
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Profit Margin', 'ifs-travel-erp' ); ?></label>
                                <input type="text" id="inp_p_margin" readonly value="0.00" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-family: monospace; font-weight: 700; background: #f8fafc;">
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Featured Image / Brochure URL', 'ifs-travel-erp' ); ?></label>
                                <input type="url" name="package_image_url" id="inp_p_img" value="<?php echo esc_url( $val_p_img ); ?>" placeholder="https://..." style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                            </div>

                            <!-- Day-by-Day Itinerary Builder -->
                            <div style="grid-column: 1 / -1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                                    <label style="margin: 0; font-size: 11px; font-weight: 700; color: #003376; text-transform: uppercase;">
                                        <span class="dashicons dashicons-calendar-alt" style="font-size: 15px; width: 15px; height: 15px; vertical-align: middle;"></span>
                                        <?php esc_html_e( 'Day-by-Day Tour Itinerary Schedule', 'ifs-travel-erp' ); ?>
                                    </label>
                                    <button type="button" id="btnAddItineraryDay" style="background: #eff6ff; color: #003376; border: 1px solid #bfdbfe; padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                        <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Itinerary Day', 'ifs-travel-erp' ); ?>
                                    </button>
                                </div>
                                <div id="itineraryRepeaterContainer" style="display: flex; flex-direction: column; gap: 10px;">
                                    <?php foreach ( $itinerary_saved as $idx => $itn ) : ?>
                                        <div class="itinerary-row" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; display: flex; flex-direction: column; gap: 8px;">
                                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                                                <span class="itn-day-badge" style="background: #003376; color: #ffffff; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 800; font-family: monospace;">Day <?php echo esc_html( $idx + 1 ); ?></span>
                                                <input type="text" name="itinerary_title[]" value="<?php echo esc_attr( $itn['title'] ?? '' ); ?>" placeholder="Day activity title (e.g. Arrival in Bangkok & Cruise Dinner)" style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; font-weight: 600;">
                                                <button type="button" class="btn-remove-itn" style="background: #fee2e2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 6px; width: 38px; height: 38px; font-size: 18px; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">&times;</button>
                                            </div>
                                            <textarea name="itinerary_desc[]" rows="2" placeholder="Full day description, included meals, pickup timings, and tourist highlights..." style="width: 100%; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 12px; font-size: 12.5px;"><?php echo esc_textarea( $itn['desc'] ?? '' ); ?></textarea>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div style="grid-column: 1 / -1;">
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Payment Terms &amp; Cancellation Policy', 'ifs-travel-erp' ); ?></label>
                                <textarea name="payment_policy" id="inp_p_pay" rows="2" placeholder="<?php esc_attr_e( 'e.g. 50% advance upon booking, balance 7 days prior to departure...', 'ifs-travel-erp' ); ?>" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; outline: none;"><?php echo esc_textarea( $val_p_pay ); ?></textarea>
                            </div>

                            <div style="grid-column: 1 / -1;">
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Internal Back-Office Notes &amp; Remarks', 'ifs-travel-erp' ); ?></label>
                                <textarea name="admin_notes" id="inp_p_notes" rows="2" placeholder="<?php esc_attr_e( 'Private operational guidelines for booking agents...', 'ifs-travel-erp' ); ?>" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; outline: none;"><?php echo esc_textarea( $val_p_notes ); ?></textarea>
                            </div>

                            <!-- Amenities Fee Matrix -->
                            <div style="grid-column: 1 / -1; margin-top: 6px;">
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Standard Included Amenities &amp; Item Costs', 'ifs-travel-erp' ); ?></label>
                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                                    <?php 
                                    $amenity_options = array( 'Air Tickets / Return Airfare', 'Bus / Train Tickets', 'Hotel Stay', 'Daily Breakfast', 'All Meals (Lunch & Dinner)', 'Airport / Station Transfers', 'Sightseeing & Guide', 'Visa Assistance' );
                                    foreach ( $amenity_options as $amenity ) : 
                                        $is_checked = array_key_exists( $amenity, $active_amenities );
                                        $fee_amount = $is_checked ? (float) $active_amenities[ $amenity ] : 0.00;
                                    ?>
                                        <div style="display: flex; flex-direction: column; gap: 6px; background: <?php echo $is_checked ? '#eff6ff' : '#f8fafc'; ?>; border: 1px solid <?php echo $is_checked ? '#003376' : '#cbd5e1'; ?>; padding: 12px 14px; border-radius: 8px;" class="amenity-card-wrap">
                                            <label style="display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #334155; cursor: pointer;">
                                                <input type="checkbox" name="standard_amenities[]" value="<?php echo esc_attr( $amenity ); ?>" <?php checked( $is_checked ); ?> class="amenity-checkbox" style="margin: 0;">
                                                <span><?php echo esc_html( $amenity ); ?></span>
                                            </label>
                                            <div class="amenity-fee-wrap" style="<?php echo $is_checked ? '' : 'display:none;'; ?> position: relative;">
                                                <input type="number" step="0.01" name="amenity_fees[<?php echo esc_attr( $amenity ); ?>]" value="<?php echo esc_attr( $fee_amount ); ?>" placeholder="0.00" style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; font-family: monospace;">
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Inclusions Dynamic Repeater -->
                            <div style="grid-column: 1 / -1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                    <label style="margin: 0; font-size: 11px; font-weight: 700; color: #0284c7; text-transform: uppercase;">
                                        <span class="dashicons dashicons-yes-alt" style="font-size: 15px; width: 15px; height: 15px; vertical-align: middle;"></span>
                                        <?php esc_html_e( 'Detailed Itinerary Inclusions', 'ifs-travel-erp' ); ?>
                                    </label>
                                    <button type="button" id="btnAddInclusion" style="background: #eff6ff; color: #0284c7; border: 1px solid #bae6fd; padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                        <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Inclusion Item', 'ifs-travel-erp' ); ?>
                                    </button>
                                </div>
                                <div id="inclusionsRepeaterContainer" style="display: flex; flex-direction: column; gap: 8px;">
                                    <?php foreach ( $inclusions_list as $inc_item ) : ?>
                                        <div class="repeater-row" style="display: flex; align-items: center; gap: 8px;">
                                            <span style="font-size: 13px; font-weight: 800; width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: #e0f2fe; color: #0284c7; flex-shrink: 0;">✔</span>
                                            <input type="text" name="inclusions_repeater[]" value="<?php echo esc_attr( $inc_item ); ?>" placeholder="<?php esc_attr_e( 'e.g. AC bus tickets from Dhaka to Cox\'s Bazar', 'ifs-travel-erp' ); ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                                            <button type="button" class="btn-remove-row" style="background: #fee2e2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 8px; width: 42px; height: 42px; font-size: 20px; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">&times;</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Exclusions Dynamic Repeater -->
                            <div style="grid-column: 1 / -1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                    <label style="margin: 0; font-size: 11px; font-weight: 700; color: #dc2626; text-transform: uppercase;">
                                        <span class="dashicons dashicons-dismiss" style="font-size: 15px; width: 15px; height: 15px; vertical-align: middle;"></span>
                                        <?php esc_html_e( 'Exclusions &amp; Extra Charges', 'ifs-travel-erp' ); ?>
                                    </label>
                                    <button type="button" id="btnAddExclusion" style="background: #eff6ff; color: #0284c7; border: 1px solid #bae6fd; padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                        <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Exclusion Item', 'ifs-travel-erp' ); ?>
                                    </button>
                                </div>
                                <div id="exclusionsRepeaterContainer" style="display: flex; flex-direction: column; gap: 8px;">
                                    <?php foreach ( $exclusions_list as $excl_item ) : ?>
                                        <div class="repeater-row" style="display: flex; align-items: center; gap: 8px;">
                                            <span style="font-size: 13px; font-weight: 800; width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: #fee2e2; color: #dc2626; flex-shrink: 0;">✖</span>
                                            <input type="text" name="exclusions_repeater[]" value="<?php echo esc_attr( $excl_item ); ?>" placeholder="<?php esc_attr_e( 'e.g. Personal shopping or entry tickets', 'ifs-travel-erp' ); ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                                            <button type="button" class="btn-remove-row" style="background: #fee2e2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 8px; width: 42px; height: 42px; font-size: 20px; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">&times;</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                            <a href="<?php echo esc_url( add_query_arg( 'action_sub', 'all', $base_url ) ); ?>" style="color: #64748b; text-decoration: none; font-weight: 600;"><?php esc_html_e( 'Cancel Form', 'ifs-travel-erp' ); ?></a>
                            <button type="submit" name="ifs_tour_plan_submit" style="background: linear-gradient(135deg, #003376 0%, #0284c7 100%); color: #ffffff; border: none; height: 42px; padding: 0 26px; border-radius: 8px; font-weight: 700; cursor: pointer;">
                                <?php echo $edit_plan_data ? esc_html__( 'Update Package Plan', 'ifs-travel-erp' ) : esc_html__( 'Save Plan Template', 'ifs-travel-erp' ); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right Sidebar: Live Tour Package Summary Card Preview -->
                <div class="ifs-preview-sidebar">
                    <div class="ifs-preview-sticky">
                        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                            <span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Live Package Card Preview', 'ifs-travel-erp' ); ?>
                        </div>

                        <div class="ifs-advisory-card">
                            <div class="advisory-watermark"><span class="dashicons dashicons-location"></span></div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                                <span style="font-size: 9.5px; font-weight: 800; letter-spacing: 0.8px; color: #94a3b8; display: inline-flex; align-items: center; gap: 4px;">
                                    <span class="dashicons dashicons-shield-alt" style="color: #38bdf8;"></span> <?php esc_html_e( 'TOUR PACKAGE PLAN', 'ifs-travel-erp' ); ?>
                                </span>
                                <span style="background: rgba(56, 189, 248, 0.12); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.25); padding: 3px 8px; border-radius: 6px; font-size: 9.5px; font-weight: 800; text-transform: uppercase;" id="prev_duration">
                                    <span class="dashicons dashicons-calendar"></span> <?php echo esc_html( sprintf( '%d Days / %d Nights', $val_p_days, $val_p_nights ) ); ?>
                                </span>
                            </div>

                            <div style="margin-bottom: 16px;">
                                <h3 style="margin: 0; font-size: 18px; font-weight: 900; color: #ffffff; text-transform: uppercase;" id="prev_pkg_name"><?php echo esc_html( ! empty( $val_p_name ) ? strtoupper( $val_p_name ) : '3D/2N COX\'S BAZAR ESCAPE' ); ?></h3>
                                <span style="font-size: 11.5px; color: #38bdf8; margin-top: 3px; display: block; text-transform: uppercase; font-weight: 700;" id="prev_destination"><?php echo esc_html( 'Destination: ' . ( ! empty( $val_p_dest ) ? strtoupper( $val_p_dest ) : 'COX\'S BAZAR' ) ); ?></span>
                                <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px;">
                                    <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 10px; color: #e2e8f0; background: rgba(255, 255, 255, 0.07); border: 1px solid rgba(255, 255, 255, 0.06); padding: 3px 8px; border-radius: 6px; font-weight: 600;" id="prev_category">
                                        <span class="dashicons dashicons-tag" style="color: #38bdf8;"></span> <?php echo esc_html( $val_p_type ); ?>
                                    </span>
                                    <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 10px; color: #e2e8f0; background: rgba(255, 255, 255, 0.07); border: 1px solid rgba(255, 255, 255, 0.06); padding: 3px 8px; border-radius: 6px; font-weight: 600;" id="prev_hotel">
                                        <span class="dashicons dashicons-building" style="color: #38bdf8;"></span> <?php echo esc_html( $val_p_hotel ?: 'Partner Hotel' ); ?>
                                    </span>
                                </div>
                            </div>

                            <div style="background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.06); border-radius: 12px; padding: 14px; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center;">
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-size: 9px; font-weight: 700; color: #94a3b8; letter-spacing: 0.5px;"><?php esc_html_e( 'ESTIMATED COST VS SELL', 'ifs-travel-erp' ); ?></span>
                                    <span style="font-size: 10px; color: #cbd5e1; margin-top: 2px; font-family: monospace;" id="prev_rate_breakdown"><?php echo esc_html( $val_p_curr ); ?> Cost: <?php echo esc_html( number_format( (float) $val_p_cost, 2 ) ); ?> | Sell: <?php echo esc_html( number_format( (float) $val_p_sell, 2 ) ); ?></span>
                                </div>
                                <h4 style="margin: 0; font-size: 18px; font-weight: 900; color: #4ade80; font-family: monospace;" id="prev_sell_price"><?php echo esc_html( $val_p_curr . ' ' . number_format( (float) $val_p_sell, 2 ) ); ?></h4>
                            </div>

                            <div style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 12px; padding: 12px 14px; margin-bottom: 12px;">
                                <span style="font-size: 9.5px; font-weight: 800; color: #38bdf8; display: flex; align-items: center; gap: 4px; margin-bottom: 6px;">
                                    <span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'FEATURED AMENITIES:', 'ifs-travel-erp' ); ?>
                                </span>
                                <div style="font-size: 11px; color: #cbd5e1; line-height: 1.5; max-height: 120px; overflow-y: auto;" id="prev_amenities_list">
                                    <?php esc_html_e( 'No amenities selected.', 'ifs-travel-erp' ); ?>
                                </div>
                            </div>

                            <div style="font-size: 9.5px; color: #94a3b8; display: flex; justify-content: space-between; align-items: center; padding-top: 8px; border-top: 1px solid rgba(255, 255, 255, 0.08);">
                                <span><span class="dashicons dashicons-saved" style="color: #38bdf8; font-size: 13px; vertical-align: middle;"></span> <?php esc_html_e( 'ERP Package Template', 'ifs-travel-erp' ); ?></span>
                                <span style="width: 7px; height: 7px; background: #4ade80; border-radius: 50%; display: inline-block;" title="<?php esc_attr_e( 'Live Sync Active', 'ifs-travel-erp' ); ?>"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $(document).on('change', '.amenity-checkbox', function() {
                const cardWrap = $(this).closest('.amenity-card-wrap');
                const feeWrap  = cardWrap.find('.amenity-fee-wrap');
                if ($(this).is(':checked')) {
                    feeWrap.stop(true, true).slideDown(150);
                    cardWrap.css({ background: '#eff6ff', borderColor: '#003376' });
                } else {
                    feeWrap.stop(true, true).slideUp(150);
                    feeWrap.find('input').val('0.00');
                    cardWrap.css({ background: '#f8fafc', borderColor: '#cbd5e1' });
                }
                updatePackagePreviewCard();
            });

            const costInput   = $('#inp_p_cost');
            const sellInput   = $('#inp_p_sell');
            const marginInput = $('#inp_p_margin');

            function updateMargin() {
                const c = parseFloat(costInput.val()) || 0;
                const s = parseFloat(sellInput.val()) || 0;
                const m = s - c;
                marginInput.val(m.toFixed(2));
                marginInput.css('color', m >= 0 ? '#059669' : '#dc2626');
                updatePackagePreviewCard();
            }

            costInput.on('input change', updateMargin);
            sellInput.on('input change', updateMargin);
            updateMargin();

            function updatePackagePreviewCard() {
                const name   = $('#inp_p_name').val().trim();
                const dest   = $('#inp_p_dest').val().trim();
                const type   = $('#inp_p_type').val();
                const days   = $('#inp_p_days').val() || 3;
                const nights = $('#inp_p_nights').val() || 2;
                const hotel  = $('#inp_p_hotel').val().trim();
                const curr   = $('#inp_p_curr').val() || 'BDT';
                const cost   = parseFloat(costInput.val()) || 0;
                const sell   = parseFloat(sellInput.val()) || 0;

                $('#prev_pkg_name').text(name ? name.toUpperCase() : "3D/2N COX'S BAZAR ESCAPE");
                $('#prev_destination').text('Destination: ' + (dest ? dest.toUpperCase() : "COX'S BAZAR"));
                $('#prev_duration').html('<span class="dashicons dashicons-calendar"></span> ' + days + ' Days / ' + nights + ' Nights');
                $('#prev_category').html('<span class="dashicons dashicons-tag" style="color: #38bdf8;"></span> ' + type);
                $('#prev_hotel').html('<span class="dashicons dashicons-building" style="color: #38bdf8;"></span> ' + (hotel || 'Partner Hotel'));

                $('#prev_sell_price').text(curr + ' ' + sell.toLocaleString('en-US', { minimumFractionDigits: 2 }));
                $('#prev_rate_breakdown').text(curr + ' Cost: ' + cost.toLocaleString('en-US') + ' | Sell: ' + sell.toLocaleString('en-US'));

                const checkedAmenities = [];
                $('.amenity-checkbox:checked').each(function() {
                    checkedAmenities.push($(this).val());
                });

                if (checkedAmenities.length > 0) {
                    $('#prev_amenities_list').html(checkedAmenities.map((item, idx) => (idx + 1) + '. ' + item).join('<br>'));
                } else {
                    $('#prev_amenities_list').text('<?php echo esc_js( __( 'No amenities selected.', 'ifs-travel-erp' ) ); ?>');
                }
            }

            $(document).on('input change keyup', '#inp_p_name, #inp_p_dest, #inp_p_type, #inp_p_days, #inp_p_nights, #inp_p_hotel, #inp_p_curr, input[name^="amenity_fees"]', updatePackagePreviewCard);

            // Itinerary Day Repeater Action
            $('#btnAddItineraryDay').on('click', function(e) {
                e.preventDefault();
                const dayCount = $('#itineraryRepeaterContainer .itinerary-row').length + 1;
                const rowHtml = `
                    <div class="itinerary-row" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; display: flex; flex-direction: column; gap: 8px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                            <span class="itn-day-badge" style="background: #003376; color: #ffffff; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 800; font-family: monospace;">Day ${dayCount}</span>
                            <input type="text" name="itinerary_title[]" placeholder="Day activity title..." style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; font-weight: 600;">
                            <button type="button" class="btn-remove-itn" style="background: #fee2e2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 6px; width: 38px; height: 38px; font-size: 18px; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">&times;</button>
                        </div>
                        <textarea name="itinerary_desc[]" rows="2" placeholder="Full day description, included meals, pickup timings, and tourist highlights..." style="width: 100%; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 12px; font-size: 12.5px;"></textarea>
                    </div>
                `;
                $('#itineraryRepeaterContainer').append(rowHtml);
            });

            $(document).on('click', '.btn-remove-itn', function(e) {
                e.preventDefault();
                if ($('#itineraryRepeaterContainer .itinerary-row').length > 1) {
                    $(this).closest('.itinerary-row').remove();
                    $('#itineraryRepeaterContainer .itinerary-row').each(function(index) {
                        $(this).find('.itn-day-badge').text('Day ' + (index + 1));
                    });
                } else {
                    $(this).closest('.itinerary-row').find('input, textarea').val('');
                }
            });

            $('#btnAddInclusion').on('click', function(e) {
                e.preventDefault();
                const rowHtml = `
                    <div class="repeater-row" style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 13px; font-weight: 800; width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: #e0f2fe; color: #0284c7; flex-shrink: 0;">✔</span>
                        <input type="text" name="inclusions_repeater[]" placeholder="<?php echo esc_js( __( 'e.g. Complimentary breakfast', 'ifs-travel-erp' ) ); ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                        <button type="button" class="btn-remove-row" style="background: #fee2e2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 8px; width: 42px; height: 42px; font-size: 20px; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">&times;</button>
                    </div>
                `;
                $('#inclusionsRepeaterContainer').append(rowHtml);
            });

            $('#btnAddExclusion').on('click', function(e) {
                e.preventDefault();
                const rowHtml = `
                    <div class="repeater-row" style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 13px; font-weight: 800; width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: #fee2e2; color: #dc2626; flex-shrink: 0;">✖</span>
                        <input type="text" name="exclusions_repeater[]" placeholder="<?php echo esc_js( __( 'e.g. Personal shopping', 'ifs-travel-erp' ) ); ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                        <button type="button" class="btn-remove-row" style="background: #fee2e2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 8px; width: 42px; height: 42px; font-size: 20px; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">&times;</button>
                    </div>
                `;
                $('#exclusionsRepeaterContainer').append(rowHtml);
            });

            $(document).on('click', '.btn-remove-row', function(e) {
                e.preventDefault();
                const parentContainer = $(this).closest('#inclusionsRepeaterContainer, #exclusionsRepeaterContainer');
                if (parentContainer.find('.repeater-row').length > 1) {
                    $(this).closest('.repeater-row').remove();
                } else {
                    $(this).closest('.repeater-row').find('input').val('');
                }
            });

            updatePackagePreviewCard();
        });
        </script>
        <?php
    }
}