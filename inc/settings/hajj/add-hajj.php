<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_settings_hajj_pkg_form_panel' ) ) {
    /**
     * Hajj & Umrah Package Template Form Panel (Fully Enhanced)
     */
    function ifs_terp_settings_hajj_pkg_form_panel() {
        global $wpdb;
        $table_pkgs = $wpdb->prefix . 'iterp_hajj_packages';
        $base_url   = admin_url( 'admin.php?page=ifs_travel_erp&tab=settings&sub=hajj_pkg' );
        $message    = '';

        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_hajj_pkg_submit'] ) ) {
            check_admin_referer( 'ifs_hajj_pkg_action', 'ifs_hajj_pkg_nonce' );

            $edit_id        = isset( $_POST['edit_pkg_id'] ) ? absint( wp_unslash( $_POST['edit_pkg_id'] ) ) : 0;
            $package_name   = isset( $_POST['package_name'] ) ? sanitize_text_field( wp_unslash( $_POST['package_name'] ) ) : '';
            $package_type   = isset( $_POST['package_type'] ) ? sanitize_text_field( wp_unslash( $_POST['package_type'] ) ) : 'Umrah';
            $season_year    = isset( $_POST['season_year'] ) ? sanitize_text_field( wp_unslash( $_POST['season_year'] ) ) : '1447 AH / 2026';
            $visa_type      = isset( $_POST['visa_type'] ) ? sanitize_text_field( wp_unslash( $_POST['visa_type'] ) ) : 'Umrah Visa (B2B/B2C)';
            $transport_type = isset( $_POST['transport_type'] ) ? sanitize_text_field( wp_unslash( $_POST['transport_type'] ) ) : 'AC Bus / Coaster Sharing';
            $meal_plan      = isset( $_POST['meal_plan'] ) ? sanitize_text_field( wp_unslash( $_POST['meal_plan'] ) ) : 'Full Board (Buffet)';
            
            $total_days     = isset( $_POST['total_days'] ) ? intval( wp_unslash( $_POST['total_days'] ) ) : 15;
            $nights_makkah  = isset( $_POST['nights_makkah'] ) ? intval( wp_unslash( $_POST['nights_makkah'] ) ) : 7;
            $nights_madinah = isset( $_POST['nights_madinah'] ) ? intval( wp_unslash( $_POST['nights_madinah'] ) ) : 7;
            $capacity       = isset( $_POST['capacity'] ) ? intval( wp_unslash( $_POST['capacity'] ) ) : 0;
            
            $cost_bdt       = isset( $_POST['cost_bdt'] ) ? (float) wp_unslash( $_POST['cost_bdt'] ) : 0;
            $cost_sar       = isset( $_POST['cost_sar'] ) ? (float) wp_unslash( $_POST['cost_sar'] ) : 0;
            
            $price_quad     = isset( $_POST['price_quad'] ) ? (float) wp_unslash( $_POST['price_quad'] ) : 0;
            $price_triple   = isset( $_POST['price_triple'] ) ? (float) wp_unslash( $_POST['price_triple'] ) : 0;
            $price_double   = isset( $_POST['price_double'] ) ? (float) wp_unslash( $_POST['price_double'] ) : 0;
            $price_single   = isset( $_POST['price_single'] ) ? (float) wp_unslash( $_POST['price_single'] ) : 0;
            $price_child    = isset( $_POST['price_child'] ) ? (float) wp_unslash( $_POST['price_child'] ) : 0;
            
            $hotel_makkah   = isset( $_POST['hotel_makkah'] ) ? sanitize_text_field( wp_unslash( $_POST['hotel_makkah'] ) ) : '';
            $makkah_dist    = isset( $_POST['makkah_distance'] ) ? sanitize_text_field( wp_unslash( $_POST['makkah_distance'] ) ) : '';
            $hotel_madinah  = isset( $_POST['hotel_madinah'] ) ? sanitize_text_field( wp_unslash( $_POST['hotel_madinah'] ) ) : '';
            $madinah_dist   = isset( $_POST['madinah_distance'] ) ? sanitize_text_field( wp_unslash( $_POST['madinah_distance'] ) ) : '';
            
            $airline_name   = isset( $_POST['airline_name'] ) ? sanitize_text_field( wp_unslash( $_POST['airline_name'] ) ) : '';
            $flight_routing = isset( $_POST['flight_routing'] ) ? sanitize_text_field( wp_unslash( $_POST['flight_routing'] ) ) : '';
            $mina_category  = isset( $_POST['mina_category'] ) ? sanitize_text_field( wp_unslash( $_POST['mina_category'] ) ) : '';

            $raw_inclusions   = isset( $_POST['inclusions_repeater'] ) && is_array( $_POST['inclusions_repeater'] ) ? (array) wp_unslash( $_POST['inclusions_repeater'] ) : array();
            $clean_inclusions = array_values( array_filter( array_map( 'sanitize_text_field', $raw_inclusions ) ) );
            $inclusions_json  = wp_json_encode( $clean_inclusions );

            $raw_exclusions   = isset( $_POST['exclusions_repeater'] ) && is_array( $_POST['exclusions_repeater'] ) ? (array) wp_unslash( $_POST['exclusions_repeater'] ) : array();
            $clean_exclusions = array_values( array_filter( array_map( 'sanitize_text_field', $raw_exclusions ) ) );
            $exclusions_json  = wp_json_encode( $clean_exclusions );

            if ( empty( $package_name ) ) {
                $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> ' . esc_html__( 'Package Title is required.', 'ifs-travel-erp' ) . '</div>';
            } else {
                $data = array(
                    'package_name'        => $package_name,
                    'package_type'        => $package_type,
                    'season_year'         => $season_year, // <-- ADDED
                    'visa_type'           => $visa_type,
                    'transport_type'      => $transport_type,
                    'meal_plan'           => $meal_plan,
                    'total_days'          => $total_days,
                    'nights_makkah'       => $nights_makkah,
                    'nights_madinah'      => $nights_madinah,
                    'capacity'            => $capacity,
                    'cost_bdt'            => $cost_bdt,
                    'cost_sar'            => $cost_sar,
                    'selling_price'       => $price_quad,
                    'price_quad'          => $price_quad,
                    'price_triple'        => $price_triple,
                    'price_double'        => $price_double,
                    'price_single'        => $price_single,
                    'price_child'         => $price_child, // <-- ADDED
                    'hotel_makkah'        => $hotel_makkah,
                    'makkah_distance'     => $makkah_dist,
                    'hotel_madinah'       => $hotel_madinah,
                    'madinah_distance'    => $madinah_dist,
                    'airline_name'        => $airline_name,
                    'flight_routing'      => $flight_routing, // <-- ADDED
                    'mina_category'       => $mina_category,  // <-- ADDED
                    'inclusions_standard' => $inclusions_json,
                    'inclusions_json'     => $inclusions_json,
                    'exclusions_json'     => $exclusions_json,
                );

                if ( $edit_id > 0 ) {
                    $wpdb->update( $table_pkgs, $data, array( 'id' => $edit_id ) );
                    $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Package template updated successfully.', 'ifs-travel-erp' ) . '</div>';
                } else {
                    $data['created_at'] = current_time( 'mysql' );
                    $wpdb->insert( $table_pkgs, $data );
                    $edit_id = $wpdb->insert_id;
                    $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'New Hajj/Umrah package template created successfully.', 'ifs-travel-erp' ) . '</div>';
                }
            }
        }

        $edit_id  = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : ( ! empty( $edit_id ) ? $edit_id : 0 );
        $edit_row = $edit_id > 0 ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_pkgs} WHERE id = %d", $edit_id ) ) : false;

        $inclusions_list = array();
        if ( $edit_row && ! empty( $edit_row->inclusions_json ) ) {
            $dec = json_decode( $edit_row->inclusions_json, true );
            if ( is_array( $dec ) ) {
                $inclusions_list = $dec;
            }
        }
        if ( empty( $inclusions_list ) ) {
            $inclusions_list = array( 'Return Air Ticket on Saudia / Biman', 'Umrah Visa Processing with Insurance', 'Makkah & Madinah Hotel Accommodation', 'Ziyarah in Makkah and Madinah with Bus Guide' );
        }

        $exclusions_list = array();
        if ( $edit_row && ! empty( $edit_row->exclusions_json ) ) {
            $dec = json_decode( $edit_row->exclusions_json, true );
            if ( is_array( $dec ) ) {
                $exclusions_list = $dec;
            }
        }
        if ( empty( $exclusions_list ) ) {
            $exclusions_list = array( 'Personal Expenses, Laundry & Food unless specified', 'Any PCR / Emergency Medical Hospitalization Costs' );
        }
        ?>
        <style>
            .ifs-split-editor { display: grid; grid-template-columns: 1fr 390px; gap: 28px; align-items: flex-start; }
            @media (max-width: 1140px) { .ifs-split-editor { grid-template-columns: 1fr; } }
            .ifs-preview-sticky { position: sticky; top: 30px; }
            .ifs-advisory-card { background: linear-gradient(135deg, #047857 0%, #064e3b 100%); border-radius: 18px; padding: 24px; color: #ffffff; border: 1px solid rgba(255, 255, 255, 0.12); position: relative; overflow: hidden; margin-bottom: 18px; }
            .advisory-watermark { position: absolute; right: -15px; bottom: -15px; opacity: 0.05; pointer-events: none; }
            .advisory-watermark .dashicons { font-size: 140px; width: 140px; height: 140px; color: #ffffff; }
        </style>

        <div class="wrap" style="max-width: 1440px; margin: 0 auto;">
            <?php echo wp_kses_post( $message ); ?>

            <form method="post" action="" class="ifs-split-editor">
                <?php wp_nonce_field( 'ifs_hajj_pkg_action', 'ifs_hajj_pkg_nonce' ); ?>
                <?php if ( $edit_row ) : ?>
                    <input type="hidden" name="edit_pkg_id" value="<?php echo esc_attr( $edit_row->id ); ?>">
                <?php endif; ?>

                <div class="ifs-form-body">
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 26px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);">
                        <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 22px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9;">
                            <div style="width: 34px; height: 34px; border-radius: 9px; background: linear-gradient(135deg, #047857 0%, #059669 100%); color: #ffffff; font-weight: 800; font-size: 14px; display: flex; align-items: center; justify-content: center;">
                                <span class="dashicons dashicons-palmtree"></span>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 15.5px; font-weight: 800; color: #0f172a;">
                                    <?php echo $edit_row ? esc_html__( 'Edit Hajj/Umrah Package Template', 'ifs-travel-erp' ) : esc_html__( 'Create New Hajj/Umrah Package Template', 'ifs-travel-erp' ); ?>
                                </h3>
                                <p style="margin: 2px 0 0 0; font-size: 12.5px; color: #64748b;"><?php esc_html_e( 'Configure hotel allocations, room sharing prices in BDT/SAR, flight routing, and pilgrim logistics', 'ifs-travel-erp' ); ?></p>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px;">
                            <div style="grid-column: span 2;">
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Package Title', 'ifs-travel-erp' ); ?> <span style="color: #e11d48;">*</span></label>
                                <input type="text" name="package_name" id="inp_p_name" required value="<?php echo $edit_row ? esc_attr( $edit_row->package_name ) : ''; ?>" placeholder="<?php esc_attr_e( 'e.g. 15 Days Economy Umrah Package', 'ifs-travel-erp' ); ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-weight: 700;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Package Type', 'ifs-travel-erp' ); ?></label>
                                <select name="package_type" id="inp_p_type" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                                    <option value="Umrah" <?php selected( $edit_row ? $edit_row->package_type : '', 'Umrah' ); ?>><?php esc_html_e( 'Umrah Package', 'ifs-travel-erp' ); ?></option>
                                    <option value="Hajj" <?php selected( $edit_row ? $edit_row->package_type : '', 'Hajj' ); ?>><?php esc_html_e( 'Hajj Package', 'ifs-travel-erp' ); ?></option>
                                    <option value="Ramadan Special" <?php selected( $edit_row ? $edit_row->package_type : '', 'Ramadan Special' ); ?>><?php esc_html_e( 'Ramadan Special', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Season Year / Cycle', 'ifs-travel-erp' ); ?></label>
                                <input type="text" name="season_year" id="inp_p_season" value="<?php echo $edit_row ? esc_attr( $edit_row->season_year ) : '1447 AH / 2026'; ?>" placeholder="1447 AH / 2026" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-family: monospace;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Visa Type / Category', 'ifs-travel-erp' ); ?></label>
                                <select name="visa_type" id="inp_p_visa" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                                    <option value="Umrah Visa (B2B/B2C)" <?php selected( $edit_row ? $edit_row->visa_type : '', 'Umrah Visa (B2B/B2C)' ); ?>><?php esc_html_e( 'Umrah Visa (Standard)', 'ifs-travel-erp' ); ?></option>
                                    <option value="Tourist Visa (1 Year)" <?php selected( $edit_row ? $edit_row->visa_type : '', 'Tourist Visa (1 Year)' ); ?>><?php esc_html_e( 'Tourist Visa (1 Year Multi-Entry)', 'ifs-travel-erp' ); ?></option>
                                    <option value="Hajj Quota Permit" <?php selected( $edit_row ? $edit_row->visa_type : '', 'Hajj Quota Permit' ); ?>><?php esc_html_e( 'Hajj Quota Permit', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>
                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Ground Transport Type', 'ifs-travel-erp' ); ?></label>
                                <select name="transport_type" id="inp_p_trans" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                                    <option value="AC Bus / Coaster Sharing" <?php selected( $edit_row ? $edit_row->transport_type : '', 'AC Bus / Coaster Sharing' ); ?>><?php esc_html_e( 'AC Bus / Coaster (Sharing)', 'ifs-travel-erp' ); ?></option>
                                    <option value="Private GMC / Sedan" <?php selected( $edit_row ? $edit_row->transport_type : '', 'Private GMC / Sedan' ); ?>><?php esc_html_e( 'Private GMC / Sedan', 'ifs-travel-erp' ); ?></option>
                                    <option value="Haramain Train Included" <?php selected( $edit_row ? $edit_row->transport_type : '', 'Haramain Train Included' ); ?>><?php esc_html_e( 'Haramain High-Speed Train', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Meal Plan Allocation', 'ifs-travel-erp' ); ?></label>
                                <select name="meal_plan" id="inp_p_meal" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                                    <option value="Full Board (Buffet)" <?php selected( $edit_row ? $edit_row->meal_plan : '', 'Full Board (Buffet)' ); ?>><?php esc_html_e( 'Full Board (Breakfast, Lunch, Dinner)', 'ifs-travel-erp' ); ?></option>
                                    <option value="Half Board (Breakfast & Dinner)" <?php selected( $edit_row ? $edit_row->meal_plan : '', 'Half Board (Breakfast & Dinner)' ); ?>><?php esc_html_e( 'Half Board (Breakfast & Dinner)', 'ifs-travel-erp' ); ?></option>
                                    <option value="Breakfast Only" <?php selected( $edit_row ? $edit_row->meal_plan : '', 'Breakfast Only' ); ?>><?php esc_html_e( 'Breakfast Only', 'ifs-travel-erp' ); ?></option>
                                    <option value="Room Only / No Meals" <?php selected( $edit_row ? $edit_row->meal_plan : '', 'Room Only / No Meals' ); ?>><?php esc_html_e( 'Room Only / No Meals', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>
                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Total Duration (Days)', 'ifs-travel-erp' ); ?></label>
                                <input type="number" min="1" name="total_days" id="inp_p_days" value="<?php echo $edit_row ? esc_attr( $edit_row->total_days ) : '15'; ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-family: monospace;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Makkah Nights', 'ifs-travel-erp' ); ?></label>
                                <input type="number" min="0" name="nights_makkah" id="inp_p_makkah" value="<?php echo $edit_row ? esc_attr( $edit_row->nights_makkah ) : '7'; ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-family: monospace;">
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Madinah Nights', 'ifs-travel-erp' ); ?></label>
                                <input type="number" min="0" name="nights_madinah" id="inp_p_madinah" value="<?php echo $edit_row ? esc_attr( $edit_row->nights_madinah ) : '7'; ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-family: monospace;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Total Group Capacity (Seats)', 'ifs-travel-erp' ); ?></label>
                                <input type="number" min="0" name="capacity" id="inp_p_cap" value="<?php echo $edit_row ? esc_attr( $edit_row->capacity ) : '45'; ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-family: monospace;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Estimated Cost (BDT ৳)', 'ifs-travel-erp' ); ?></label>
                                <input type="number" step="0.01" name="cost_bdt" id="inp_p_cost" value="<?php echo $edit_row ? esc_attr( $edit_row->cost_bdt ) : '0.00'; ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-family: monospace;">
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Estimated Cost (SAR ﷼)', 'ifs-travel-erp' ); ?></label>
                                <input type="number" step="0.01" name="cost_sar" id="inp_p_sar" value="<?php echo $edit_row ? esc_attr( $edit_row->cost_sar ) : '0.00'; ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-family: monospace;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Quad Share Price (৳)', 'ifs-travel-erp' ); ?></label>
                                <input type="number" step="0.01" name="price_quad" id="inp_p_quad" value="<?php echo $edit_row ? esc_attr( $edit_row->price_quad ) : '0.00'; ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-family: monospace; font-weight: 700; color: #047857;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Triple Share Price (৳)', 'ifs-travel-erp' ); ?></label>
                                <input type="number" step="0.01" name="price_triple" id="inp_p_triple" value="<?php echo $edit_row ? esc_attr( $edit_row->price_triple ) : '0.00'; ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-family: monospace;">
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Double Share Price (৳)', 'ifs-travel-erp' ); ?></label>
                                <input type="number" step="0.01" name="price_double" id="inp_p_double" value="<?php echo $edit_row ? esc_attr( $edit_row->price_double ) : '0.00'; ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-family: monospace;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Single Room Price (৳)', 'ifs-travel-erp' ); ?></label>
                                <input type="number" step="0.01" name="price_single" id="inp_p_single" value="<?php echo $edit_row ? esc_attr( $edit_row->price_single ) : '0.00'; ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-family: monospace;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Child Rate (৳)', 'ifs-travel-erp' ); ?></label>
                                <input type="number" step="0.01" name="price_child" id="inp_p_child" value="<?php echo $edit_row ? esc_attr( $edit_row->price_child ) : '0.00'; ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-family: monospace;">
                            </div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Preferred Airline', 'ifs-travel-erp' ); ?></label>
                                <input type="text" name="airline_name" id="inp_p_airline" value="<?php echo $edit_row ? esc_attr( $edit_row->airline_name ) : ''; ?>" placeholder="e.g. Saudia / Biman" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Flight Routing / Sectors', 'ifs-travel-erp' ); ?></label>
                                <input type="text" name="flight_routing" id="inp_p_routing" value="<?php echo $edit_row ? esc_attr( $edit_row->flight_routing ) : ''; ?>" placeholder="e.g. DAC - JED / MED - DAC" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-family: monospace;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Mina / Arafat Tent Category', 'ifs-travel-erp' ); ?></label>
                                <input type="text" name="mina_category" value="<?php echo $edit_row ? esc_attr( $edit_row->mina_category ) : ''; ?>" placeholder="e.g. Maktab Category A (VIP)" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                            </div>

                            <!-- Accommodation Logistics -->
                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Makkah Hotel', 'ifs-travel-erp' ); ?></label>
                                <input type="text" name="hotel_makkah" id="inp_p_hmakkah" value="<?php echo $edit_row ? esc_attr( $edit_row->hotel_makkah ) : ''; ?>" placeholder="e.g. Swissotel Makkah" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Makkah Haram Distance', 'ifs-travel-erp' ); ?></label>
                                <input type="text" name="makkah_distance" id="inp_p_dmakkah" value="<?php echo $edit_row ? esc_attr( $edit_row->makkah_distance ) : ''; ?>" placeholder="e.g. 150 Meters / Shuttle" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                            </div>
                            <div></div>

                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Madinah Hotel', 'ifs-travel-erp' ); ?></label>
                                <input type="text" name="hotel_madinah" id="inp_p_hmadinah" value="<?php echo $edit_row ? esc_attr( $edit_row->hotel_madinah ) : ''; ?>" placeholder="e.g. Oberoi Madinah" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Madinah Haram Distance', 'ifs-travel-erp' ); ?></label>
                                <input type="text" name="madinah_distance" id="inp_p_dmadinah" value="<?php echo $edit_row ? esc_attr( $edit_row->madinah_distance ) : ''; ?>" placeholder="e.g. 100 Meters / 2 Min Walk" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                            </div>
                            <div></div>

                            <!-- Inclusions Dynamic Repeater -->
                            <div style="grid-column: 1 / -1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                    <label style="margin: 0; font-size: 11px; font-weight: 700; color: #047857; text-transform: uppercase;">
                                        <span class="dashicons dashicons-yes-alt" style="font-size: 15px; width: 15px; height: 15px; vertical-align: middle;"></span>
                                        <?php esc_html_e( 'Package Inclusions (Repeater)', 'ifs-travel-erp' ); ?>
                                    </label>
                                    <button type="button" class="ifs-btn-add-row" id="btnAddHajjInclusion" style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                        <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Inclusion Item', 'ifs-travel-erp' ); ?>
                                    </button>
                                </div>
                                <div id="hajjInclusionsRepeaterContainer" style="display: flex; flex-direction: column; gap: 8px;">
                                    <?php foreach ( $inclusions_list as $inc_item ) : ?>
                                        <div class="repeater-row" style="display: flex; align-items: center; gap: 8px;">
                                            <span style="font-size: 13px; font-weight: 800; width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: #ecfdf5; color: #047857; flex-shrink: 0;">✔</span>
                                            <input type="text" name="inclusions_repeater[]" value="<?php echo esc_attr( $inc_item ); ?>" placeholder="e.g. Umrah visa processing and health insurance" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
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
                                        <?php esc_html_e( 'Package Exclusions', 'ifs-travel-erp' ); ?>
                                    </label>
                                    <button type="button" class="ifs-btn-add-row" id="btnAddHajjExclusion" style="background: #eff6ff; color: #0284c7; border: 1px solid #bae6fd; padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                        <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Exclusion Item', 'ifs-travel-erp' ); ?>
                                    </button>
                                </div>
                                <div id="hajjExclusionsRepeaterContainer" style="display: flex; flex-direction: column; gap: 8px;">
                                    <?php foreach ( $exclusions_list as $excl_item ) : ?>
                                        <div class="repeater-row" style="display: flex; align-items: center; gap: 8px;">
                                            <span style="font-size: 13px; font-weight: 800; width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: #fee2e2; color: #dc2626; flex-shrink: 0;">✖</span>
                                            <input type="text" name="exclusions_repeater[]" value="<?php echo esc_attr( $excl_item ); ?>" placeholder="e.g. Any personal laundry and meals outside hotel" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                                            <button type="button" class="btn-remove-row" style="background: #fee2e2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 8px; width: 42px; height: 42px; font-size: 20px; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">&times;</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                            <a href="<?php echo esc_url( add_query_arg( 'action_sub', 'all', $base_url ) ); ?>" style="color: #64748b; text-decoration: none; font-weight: 600;"><?php esc_html_e( 'Cancel', 'ifs-travel-erp' ); ?></a>
                            <button type="submit" name="ifs_hajj_pkg_submit" style="background: #047857; color: #ffffff; border: none; height: 42px; padding: 0 24px; border-radius: 8px; font-weight: 700; cursor: pointer;">
                                <?php echo $edit_row ? esc_html__( 'Update Package Template', 'ifs-travel-erp' ) : esc_html__( 'Save Package Template', 'ifs-travel-erp' ); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right Sidebar: Live Hajj/Umrah Package Summary Card Preview -->
                <div class="ifs-preview-sidebar">
                    <div class="ifs-preview-sticky">
                        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                            <span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Live Package Card Preview', 'ifs-travel-erp' ); ?>
                        </div>

                        <div class="ifs-advisory-card">
                            <div class="advisory-watermark"><span class="dashicons dashicons-palmtree"></span></div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                                <span style="font-size: 9.5px; font-weight: 800; letter-spacing: 0.8px; color: #a7f3d0; display: inline-flex; align-items: center; gap: 4px;">
                                    <span class="dashicons dashicons-shield-alt"></span> <?php esc_html_e( 'HAJJ & UMRAH PACKAGE', 'ifs-travel-erp' ); ?>
                                </span>
                                <span style="background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.4); padding: 3px 8px; border-radius: 6px; font-size: 9.5px; font-weight: 800; text-transform: uppercase;" id="prev_type">
                                    <?php echo esc_html( $edit_row ? $edit_row->package_type : 'Umrah' ); ?>
                                </span>
                            </div>

                            <div style="margin-bottom: 16px;">
                                <h3 style="margin: 0; font-size: 18px; font-weight: 900; color: #ffffff; text-transform: uppercase;" id="prev_pkg_name">
                                    <?php echo esc_html( $edit_row ? strtoupper( $edit_row->package_name ) : '15 DAYS ECONOMY UMRAH PACKAGE' ); ?>
                                </h3>
                                <span style="font-size: 11.5px; color: #34d399; margin-top: 3px; display: block; text-transform: uppercase; font-weight: 700;" id="prev_duration">
                                    <?php echo esc_html( sprintf( 'Duration: %d Days (%d Makkah / %d Madinah)', $edit_row ? $edit_row->total_days : 15, $edit_row ? $edit_row->nights_makkah : 7, $edit_row ? $edit_row->nights_madinah : 7 ) ); ?>
                                </span>
                                <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px;">
                                    <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 10px; color: #e2e8f0; background: rgba(255, 255, 255, 0.07); border: 1px solid rgba(255, 255, 255, 0.06); padding: 3px 8px; border-radius: 6px; font-weight: 600;" id="prev_airline">
                                        <span class="dashicons dashicons-airplane" style="color: #34d399;"></span> <?php echo esc_html( $edit_row && $edit_row->airline_name ? $edit_row->airline_name : 'Saudia / Biman' ); ?>
                                    </span>
                                </div>
                            </div>

                            <div style="background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.06); border-radius: 12px; padding: 14px; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center;">
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-size: 9px; font-weight: 700; color: #a7f3d0; letter-spacing: 0.5px;"><?php esc_html_e( 'QUAD SHARE PRICE', 'ifs-travel-erp' ); ?></span>
                                    <span style="font-size: 10px; color: #cbd5e1; margin-top: 2px; font-family: monospace;" id="prev_cost_breakdown">Cost: ৳<?php echo esc_html( number_format( (float) ( $edit_row ? $edit_row->cost_bdt : 0 ), 2 ) ); ?></span>
                                </div>
                                <h4 style="margin: 0; font-size: 18px; font-weight: 900; color: #34d399; font-family: monospace;" id="prev_quad_price">৳<?php echo esc_html( number_format( (float) ( $edit_row ? $edit_row->price_quad : 0 ), 2 ) ); ?></h4>
                            </div>

                            <div style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 12px; padding: 12px 14px; margin-bottom: 12px;">
                                <span style="font-size: 9.5px; font-weight: 800; color: #34d399; display: flex; align-items: center; gap: 4px; margin-bottom: 6px;">
                                    <span class="dashicons dashicons-building"></span> <?php esc_html_e( 'HOTEL ALLOCATIONS:', 'ifs-travel-erp' ); ?>
                                </span>
                                <div style="font-size: 11px; color: #cbd5e1; line-height: 1.6;" id="prev_hotels_list">
                                    Makkah: <?php echo esc_html( $edit_row && $edit_row->hotel_makkah ? $edit_row->hotel_makkah : 'Swissotel Makkah' ); ?><br>Madinah: <?php echo esc_html( $edit_row && $edit_row->hotel_madinah ? $edit_row->hotel_madinah : 'Oberoi Madinah' ); ?>
                                </div>
                            </div>

                            <div style="font-size: 9.5px; color: #a7f3d0; display: flex; justify-content: space-between; align-items: center; padding-top: 8px; border-top: 1px solid rgba(255, 255, 255, 0.08);">
                                <span><span class="dashicons dashicons-saved" style="color: #34d399; font-size: 13px; vertical-align: middle;"></span> <?php esc_html_e( 'Pilgrim Package Template', 'ifs-travel-erp' ); ?></span>
                                <span style="width: 7px; height: 7px; background: #34d399; border-radius: 50%; display: inline-block;" title="<?php esc_attr_e( 'Live Sync Active', 'ifs-travel-erp' ); ?>"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            function updateHajjPreviewCard() {
                const name     = $('#inp_p_name').val().trim();
                const type     = $('#inp_p_type').val();
                const days     = $('#inp_p_days').val() || 15;
                const makkah   = $('#inp_p_makkah').val() || 7;
                const madinah  = $('#inp_p_madinah').val() || 7;
                const quad     = parseFloat($('#inp_p_quad').val()) || 0;
                const cost     = parseFloat($('#inp_p_cost').val()) || 0;
                const hmakkah  = $('#inp_p_hmakkah').val().trim();
                const hmadinah = $('#inp_p_hmadinah').val().trim();
                const airline  = $('#inp_p_airline').val().trim();

                $('#prev_pkg_name').text(name ? name.toUpperCase() : "15 DAYS ECONOMY UMRAH PACKAGE");
                $('#prev_type').text(type);
                $('#prev_duration').text('Duration: ' + days + ' Days (' + makkah + ' Makkah / ' + madinah + ' Madinah)');
                $('#prev_airline').html('<span class="dashicons dashicons-airplane" style="color: #34d399;"></span> ' + (airline || 'Preferred Airline'));
                $('#prev_quad_price').text('৳' + quad.toLocaleString('en-US', { minimumFractionDigits: 2 }));
                $('#prev_cost_breakdown').text('Cost: ৳' + cost.toLocaleString('en-US'));

                let hotelText = '';
                if (hmakkah) hotelText += 'Makkah: ' + hmakkah + '<br>';
                if (hmadinah) hotelText += 'Madinah: ' + hmadinah;
                if (!hmakkah && !hmadinah) hotelText = 'Makkah & Madinah Hotels Unassigned';
                $('#prev_hotels_list').html(hotelText);
            }

            $(document).on('input change keyup', '#inp_p_name, #inp_p_type, #inp_p_days, #inp_p_makkah, #inp_p_madinah, #inp_p_quad, #inp_p_cost, #inp_p_hmakkah, #inp_p_hmadinah, #inp_p_airline', updateHajjPreviewCard);

            $('#btnAddHajjInclusion').on('click', function(e) {
                e.preventDefault();
                const rowHtml = `
                    <div class="repeater-row" style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 13px; font-weight: 800; width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: #ecfdf5; color: #047857; flex-shrink: 0;">✔</span>
                        <input type="text" name="inclusions_repeater[]" placeholder="e.g. Return air tickets & airport transfers" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                        <button type="button" class="btn-remove-row" style="background: #fee2e2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 8px; width: 42px; height: 42px; font-size: 20px; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">&times;</button>
                    </div>
                `;
                $('#hajjInclusionsRepeaterContainer').append(rowHtml);
            });

            $('#btnAddHajjExclusion').on('click', function(e) {
                e.preventDefault();
                const rowHtml = `
                    <div class="repeater-row" style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 13px; font-weight: 800; width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: #fee2e2; color: #dc2626; flex-shrink: 0;">✖</span>
                        <input type="text" name="exclusions_repeater[]" placeholder="e.g. Tips, excess baggage fees" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                        <button type="button" class="btn-remove-row" style="background: #fee2e2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 8px; width: 42px; height: 42px; font-size: 20px; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">&times;</button>
                    </div>
                `;
                $('#hajjExclusionsRepeaterContainer').append(rowHtml);
            });

            $(document).on('click', '.btn-remove-row', function(e) {
                e.preventDefault();
                const parentContainer = $(this).closest('#hajjInclusionsRepeaterContainer, #hajjExclusionsRepeaterContainer');
                if (parentContainer.find('.repeater-row').length > 1) {
                    $(this).closest('.repeater-row').remove();
                } else {
                    $(this).closest('.repeater-row').find('input').val('');
                }
            });

            updateHajjPreviewCard();
        });
        </script>
        <?php
    }
}