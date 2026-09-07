<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_hotel_property_view_panel' ) ) {
    /**
     * Hotel Property & Contract Dossier View Panel
     */
    function ifs_terp_hotel_property_view_panel() {
        global $wpdb;
        $table_hotels    = $wpdb->prefix . 'iterp_hotel_properties';
        $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
        $base_url        = admin_url( 'admin.php?page=ifs_travel_erp&tab=settings&sub=hotels_prop' );
        $hotel_id        = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;

        $hotel = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_hotels} WHERE id = %d", $hotel_id ) );

        if ( ! $hotel ) {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'Hotel property record not found.', 'ifs-travel-erp' ) . '</p></div>';
            return;
        }

        $contract_rate = floatval( $hotel->contract_rate ?? 0 );
        $standard_sell = floatval( $hotel->standard_sell ?? 0 );
        $profit_margin = $standard_sell - $contract_rate;
        $margin_pct    = ( $contract_rate > 0 ) ? ( $profit_margin / $contract_rate ) * 100 : 0;
        $currency      = esc_html( $hotel->rate_currency ?? 'BDT' );
        $symbol        = ( 'SAR' === $currency ) ? '﷼' : ( ( 'USD' === $currency ) ? '$' : '৳' );
        $status        = esc_html( $hotel->status ?? 'Active' );

        // Fetch Supplier Name if linked
        $supplier_name = esc_html__( 'Direct Contract / Independent Property', 'ifs-travel-erp' );
        if ( ! empty( $hotel->supplier_id ) && $hotel->supplier_id > 0 ) {
            $sup_row = $wpdb->get_row( $wpdb->prepare( "SELECT supplier_name FROM {$table_suppliers} WHERE id = %d", $hotel->supplier_id ) );
            if ( $sup_row && ! empty( $sup_row->supplier_name ) ) {
                $supplier_name = $sup_row->supplier_name;
            }
        }

        // Parse Amenities (Supports JSON Repeater Array or legacy comma-separated string)
        $amenities_list = array();
        if ( ! empty( $hotel->amenities ) ) {
            $decoded = json_decode( $hotel->amenities, true );
            if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
                $amenities_list = $decoded;
            } else {
                $amenities_list = array_filter( array_map( 'trim', explode( ',', $hotel->amenities ) ) );
            }
        }
        ?>
        <div class="wrap" style="max-width: 1200px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #0f172a;">
            
            <!-- Top Navigation & Action Strip -->
            <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <a href="<?php echo esc_url( add_query_arg( 'hotel_sub', 'all', $base_url ) ); ?>" 
                   style="display: inline-flex; align-items: center; gap: 6px; background: #ffffff; color: #475569; border: 1px solid #cbd5e1; height: 42px; padding: 0 16px; border-radius: 8px; font-weight: 700; font-size: 13px; text-decoration: none; box-sizing: border-box;">
                    <span class="dashicons dashicons-arrow-left-alt2" style="font-size: 16px; width: 16px; height: 16px;"></span>
                    <span><?php esc_html_e( 'Back to Property Directory', 'ifs-travel-erp' ); ?></span>
                </a>

                <div style="display: flex; gap: 8px; align-items: center;">
                    <a href="<?php echo esc_url( add_query_arg( array( 'hotel_sub' => 'edit', 'id' => $hotel->id ), $base_url ) ); ?>" 
                       style="display: inline-flex; align-items: center; gap: 6px; background: #003376; color: #ffffff; border: 1px solid #003376; height: 42px; padding: 0 18px; border-radius: 8px; font-weight: 700; font-size: 13px; text-decoration: none; box-sizing: border-box;">
                        <span class="dashicons dashicons-edit" style="font-size: 16px; width: 16px; height: 16px;"></span>
                        <span><?php esc_html_e( 'Edit Contract Profile', 'ifs-travel-erp' ); ?></span>
                    </a>

                    <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'hotel_sub' => 'delete', 'id' => $hotel->id ), $base_url ), 'delete_hotel_prop_' . $hotel->id ) ); ?>" 
                       onclick="return confirm('<?php echo esc_js( __( 'Delete this hotel property permanently?', 'ifs-travel-erp' ) ); ?>');" 
                       style="display: inline-flex; align-items: center; gap: 6px; background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; height: 42px; padding: 0 16px; border-radius: 8px; font-weight: 700; font-size: 13px; text-decoration: none; box-sizing: border-box;">
                        <span class="dashicons dashicons-trash" style="font-size: 16px; width: 16px; height: 16px;"></span>
                        <span><?php esc_html_e( 'Delete Property', 'ifs-travel-erp' ); ?></span>
                    </a>
                </div>
            </div>

            <!-- Main Property Profile Card Container -->
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden;">
                
                <!-- Hero Header Strip -->
                <div style="background: #0a1f44; padding: 30px; color: #ffffff; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 8px; flex-wrap: wrap;">
                            <span style="background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase;">
                                <?php echo esc_html( $hotel->property_type ?? 'Hotel' ); ?>
                            </span>
                            <span style="background: rgba(251, 191, 36, 0.15); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3); padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 800;">
                                ★ <?php echo esc_html( $hotel->star_rating ); ?>
                            </span>
                            <?php if ( ! empty( $hotel->room_view_type ) ) : ?>
                                <span style="background: rgba(147, 197, 253, 0.15); color: #93c5fd; border: 1px solid rgba(147, 197, 253, 0.3); padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 800;">
                                    <span class="dashicons dashicons-visibility" style="font-size: 13px; width: 13px; height: 13px; vertical-align: middle;"></span> <?php echo esc_html( $hotel->room_view_type ); ?>
                                </span>
                            <?php endif; ?>
                            <span style="background: <?php echo ( 'Active' === $status ) ? 'rgba(74, 222, 128, 0.15)' : 'rgba(248, 113, 113, 0.15)'; ?>; color: <?php echo ( 'Active' === $status ) ? '#4ade80' : '#f87171'; ?>; border: 1px solid <?php echo ( 'Active' === $status ) ? 'rgba(74, 222, 128, 0.3)' : 'rgba(248, 113, 113, 0.3)'; ?>; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 800;">
                                ● <?php echo esc_html( $status ); ?> <?php esc_html_e( 'Contract', 'ifs-travel-erp' ); ?>
                            </span>
                        </div>
                        <h1 style="margin: 0; font-size: 26px; font-weight: 900; color: #ffffff; letter-spacing: -0.3px;"><?php echo esc_html( $hotel->property_name ); ?></h1>
                        <p style="margin: 6px 0 0 0; font-size: 13.5px; color: #94a3b8; display: flex; align-items: center; gap: 6px;">
                            <span class="dashicons dashicons-location" style="font-size: 15px; width: 15px; height: 15px; color: #38bdf8;"></span>
                            <?php echo esc_html( $hotel->city . ', ' . $hotel->country ); ?>
                            <?php if ( ! empty( $hotel->haram_distance ) ) : ?>
                                &bull; <strong style="color: #e2e8f0;"><?php echo esc_html( $hotel->haram_distance ); ?></strong> <?php esc_html_e( 'from Haram/Center', 'ifs-travel-erp' ); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <div style="text-align: right; background: rgba(255, 255, 255, 0.06); border: 1px solid rgba(255, 255, 255, 0.1); padding: 12px 18px; border-radius: 10px;">
                        <span style="font-size: 10.5px; font-weight: 700; color: #94a3b8; text-transform: uppercase; display: block; letter-spacing: 0.5px;"><?php esc_html_e( 'Season & Allocation', 'ifs-travel-erp' ); ?></span>
                        <span style="font-size: 13.5px; font-weight: 800; color: #ffffff; display: block; margin-top: 2px;">
                            <?php echo esc_html( $hotel->season_type ?? 'Regular' ); ?> &bull; <span style="color: #38bdf8;"><?php echo esc_html( $hotel->allocation_type ?? 'Guaranteed Block' ); ?></span>
                            <?php if ( ! empty( $hotel->release_days ) ) : ?>
                                <small style="display: block; color: #cbd5e1; font-size: 11px; font-weight: 600; margin-top: 2px;">
                                    <?php printf( esc_html__( 'Cut-Off: %d Days', 'ifs-travel-erp' ), intval( $hotel->release_days ) ); ?>
                                </small>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <!-- Content Body Area -->
                <div style="padding: 30px;">

                    <!-- Financial Metric Cards -->
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 28px;">
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; display: flex; align-items: center; gap: 14px;">
                            <div style="width: 42px; height: 42px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                                <span class="dashicons dashicons-tag"></span>
                            </div>
                            <div>
                                <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px;"><?php esc_html_e( 'Contract Rate (Cost)', 'ifs-travel-erp' ); ?></span>
                                <h3 style="margin: 2px 0 0 0; font-family: ui-monospace, monospace; font-size: 20px; font-weight: 900; color: #0f172a;"><?php echo esc_html( $symbol . number_format( $contract_rate, 2 ) ); ?></h3>
                            </div>
                        </div>

                        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 18px; display: flex; align-items: center; gap: 14px;">
                            <div style="width: 42px; height: 42px; border-radius: 10px; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                                <span class="dashicons dashicons-cart"></span>
                            </div>
                            <div>
                                <span style="font-size: 11px; font-weight: 700; color: #166534; text-transform: uppercase; letter-spacing: 0.3px;"><?php esc_html_e( 'Standard Selling Price', 'ifs-travel-erp' ); ?></span>
                                <h3 style="margin: 2px 0 0 0; font-family: ui-monospace, monospace; font-size: 20px; font-weight: 900; color: #059669;"><?php echo esc_html( $symbol . number_format( $standard_sell, 2 ) ); ?></h3>
                            </div>
                        </div>

                        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 18px; display: flex; align-items: center; gap: 14px;">
                            <div style="width: 42px; height: 42px; border-radius: 10px; background: #dbeafe; color: #003376; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                                <span class="dashicons dashicons-chart-line"></span>
                            </div>
                            <div>
                                <span style="font-size: 11px; font-weight: 700; color: #1e40af; text-transform: uppercase; letter-spacing: 0.3px;"><?php esc_html_e( 'Estimated Margin', 'ifs-travel-erp' ); ?></span>
                                <h3 style="margin: 2px 0 0 0; font-family: ui-monospace, monospace; font-size: 20px; font-weight: 900; color: #003376;">
                                    <?php echo esc_html( $symbol . number_format( $profit_margin, 2 ) ); ?> 
                                    <small style="font-size: 11px; font-weight: 700; color: <?php echo ( $profit_margin >= 0 ) ? '#059669' : '#dc2626'; ?>;">
                                        (<?php echo esc_html( number_format( $margin_pct, 1 ) ); ?>%)
                                    </small>
                                </h3>
                            </div>
                        </div>
                    </div>

                    <!-- Two-Column Information Grid -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                        
                        <!-- Contract & Operations Breakdown -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px;">
                            <h4 style="margin: 0 0 16px 0; font-size: 13.5px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.4px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                                <span class="dashicons dashicons-media-spreadsheet" style="color: #003376;"></span> <?php esc_html_e( 'Contract & Operations Parameters', 'ifs-travel-erp' ); ?>
                            </h4>
                            
                            <ul style="margin: 0; padding: 0; list-style: none; font-size: 13px; color: #334155; line-height: 2.1;">
                                <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                    <span style="color: #64748b;"><?php esc_html_e( 'Supplier / DMC Partner:', 'ifs-travel-erp' ); ?></span>
                                    <strong style="color: #0f172a;"><?php echo esc_html( $supplier_name ); ?></strong>
                                </li>
                                <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                    <span style="color: #64748b;"><?php esc_html_e( 'Room View Type:', 'ifs-travel-erp' ); ?></span>
                                    <strong style="color: #003376;"><?php echo esc_html( $hotel->room_view_type ?? __( 'City View', 'ifs-travel-erp' ) ); ?></strong>
                                </li>
                                <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                    <span style="color: #64748b;"><?php esc_html_e( 'Total Beds Capacity:', 'ifs-travel-erp' ); ?></span>
                                    <strong style="font-family: ui-monospace, monospace; color: #0f172a;"><?php echo intval( $hotel->total_beds ); ?> <?php esc_html_e( 'Beds', 'ifs-travel-erp' ); ?></strong>
                                </li>
                                <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                    <span style="color: #64748b;"><?php esc_html_e( 'Extra Bed Rate:', 'ifs-travel-erp' ); ?></span>
                                    <strong style="font-family: ui-monospace, monospace; color: #0f172a;"><?php echo esc_html( $symbol . number_format( (float) ( $hotel->extra_bed_rate ?? 0 ), 2 ) ); ?></strong>
                                </li>
                                <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                    <span style="color: #64748b;"><?php esc_html_e( 'Default Meal Plan:', 'ifs-travel-erp' ); ?></span>
                                    <strong style="color: #0f172a;"><?php echo esc_html( $hotel->default_meal_plan ?? 'Bed & Breakfast (BB)' ); ?></strong>
                                </li>
                                <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                    <span style="color: #64748b;"><?php esc_html_e( 'Child Policy:', 'ifs-travel-erp' ); ?></span>
                                    <strong style="color: #0f172a;"><?php echo esc_html( $hotel->child_policy ?? 'Free up to 6 years' ); ?></strong>
                                </li>
                                <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                    <span style="color: #64748b;"><?php esc_html_e( 'Tax & Municipal VAT:', 'ifs-travel-erp' ); ?></span>
                                    <strong style="color: #0f172a;"><?php echo esc_html( $hotel->tax_policy ?? 'Inclusive' ); ?></strong>
                                </li>
                                <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                    <span style="color: #64748b;"><?php esc_html_e( 'Payment Terms:', 'ifs-travel-erp' ); ?></span>
                                    <strong style="color: #0f172a;"><?php echo esc_html( $hotel->payment_terms ?? '50% Advance' ); ?></strong>
                                </li>
                                <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                    <span style="color: #64748b;"><?php esc_html_e( 'Check-in / Check-out:', 'ifs-travel-erp' ); ?></span>
                                    <strong style="font-family: ui-monospace, monospace; color: #0f172a;"><?php echo esc_html( $hotel->checkin_time ?? '14:00' ); ?> / <?php echo esc_html( $hotel->checkout_time ?? '12:00' ); ?></strong>
                                </li>
                                <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                    <span style="color: #64748b;"><?php esc_html_e( 'Room Categories:', 'ifs-travel-erp' ); ?></span>
                                    <strong style="color: #0f172a;"><?php echo esc_html( $hotel->room_types_supported ?? 'Single, Double, Triple, Quad' ); ?></strong>
                                </li>
                                <li style="display: flex; justify-content: space-between; padding-top: 3px;">
                                    <span style="color: #64748b;"><?php esc_html_e( 'Contract Validity:', 'ifs-travel-erp' ); ?></span>
                                    <strong style="font-family: ui-monospace, monospace; color: #0f172a;">
                                        <?php 
                                        $start = ( ! empty( $hotel->contract_start_date ) && $hotel->contract_start_date !== '1970-01-01' ) ? date_i18n( 'd M Y', strtotime( $hotel->contract_start_date ) ) : 'Open';
                                        $end   = ( ! empty( $hotel->contract_end_date ) && $hotel->contract_end_date !== '1970-01-01' ) ? date_i18n( 'd M Y', strtotime( $hotel->contract_end_date ) ) : 'Open';
                                        echo esc_html( $start . ' — ' . $end );
                                        ?>
                                    </strong>
                                </li>
                            </ul>
                        </div>

                        <!-- Management Contacts & Banking Panel -->
                        <div style="display: flex; flex-direction: column; gap: 20px;">
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px;">
                                <h4 style="margin: 0 0 16px 0; font-size: 13.5px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.4px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                                    <span class="dashicons dashicons-id" style="color: #003376;"></span> <?php esc_html_e( 'Management & Support Contacts', 'ifs-travel-erp' ); ?>
                                </h4>
                                
                                <ul style="margin: 0; padding: 0; list-style: none; font-size: 13px; color: #334155; line-height: 2.1;">
                                    <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                        <span style="color: #64748b;"><?php esc_html_e( 'Contact Person:', 'ifs-travel-erp' ); ?></span>
                                        <strong style="color: #0f172a;"><?php echo esc_html( $hotel->contact_person ?: __( 'Not specified', 'ifs-travel-erp' ) ); ?></strong>
                                    </li>
                                    <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                        <span style="color: #64748b;"><?php esc_html_e( 'Phone Number:', 'ifs-travel-erp' ); ?></span>
                                        <strong style="font-family: ui-monospace, monospace; color: #0f172a;"><?php echo esc_html( $hotel->contact_phone ?: __( 'Not specified', 'ifs-travel-erp' ) ); ?></strong>
                                    </li>
                                    <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                        <span style="color: #64748b;"><?php esc_html_e( '24/7 Emergency Desk:', 'ifs-travel-erp' ); ?></span>
                                        <strong style="font-family: ui-monospace, monospace; color: #dc2626;"><?php echo esc_html( $hotel->emergency_phone ?: __( 'Not specified', 'ifs-travel-erp' ) ); ?></strong>
                                    </li>
                                    <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                        <span style="color: #64748b;"><?php esc_html_e( 'Official Email:', 'ifs-travel-erp' ); ?></span>
                                        <strong style="color: #0f172a;"><?php echo esc_html( $hotel->contact_email ?? __( 'Not specified', 'ifs-travel-erp' ) ); ?></strong>
                                    </li>
                                    <?php if ( ! empty( $hotel->contract_doc_url ) ) : ?>
                                        <li style="display: flex; justify-content: space-between; padding-top: 4px; align-items: center;">
                                            <span style="color: #64748b;"><?php esc_html_e( 'Agreement PDF:', 'ifs-travel-erp' ); ?></span>
                                            <a href="<?php echo esc_url( $hotel->contract_doc_url ); ?>" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 4px; background: #ffffff; color: #003376; border: 1px solid #cbd5e1; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; text-decoration: none;">
                                                <span class="dashicons dashicons-external" style="font-size: 13px; width: 13px; height: 13px;"></span> <?php esc_html_e( 'View Document', 'ifs-travel-erp' ); ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>

                            <!-- Bank & Remittance Wire Details -->
                            <?php if ( ! empty( $hotel->bank_details ) ) : ?>
                                <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 22px;">
                                    <h4 style="margin: 0 0 10px 0; font-size: 13.5px; font-weight: 800; color: #1e40af; text-transform: uppercase; letter-spacing: 0.4px; display: flex; align-items: center; gap: 6px;">
                                        <span class="dashicons dashicons-money" style="color: #003376;"></span> <?php esc_html_e( 'Direct Bank Remittance Details', 'ifs-travel-erp' ); ?>
                                    </h4>
                                    <p style="margin: 0; font-size: 12.5px; color: #1e3a8a; font-family: ui-monospace, monospace; line-height: 1.6;">
                                        <?php echo nl2br( esc_html( $hotel->bank_details ) ); ?>
                                    </p>
                                </div>
                            <?php endif; ?>

                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px;">
                                <h4 style="margin: 0 0 10px 0; font-size: 13.5px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.4px;"><?php esc_html_e( 'Physical Address & Policies', 'ifs-travel-erp' ); ?></h4>
                                <p style="margin: 0 0 10px 0; font-size: 13px; color: #334155; line-height: 1.5;">
                                    <strong style="color: #64748b; font-size: 11px; text-transform: uppercase; display: block; margin-bottom: 2px;"><?php esc_html_e( 'Address:', 'ifs-travel-erp' ); ?></strong>
                                    <?php echo ! empty( $hotel->address ) ? nl2br( esc_html( $hotel->address ) ) : '<span style="color: #94a3b8; font-style: italic;">' . esc_html__( 'No street address specified.', 'ifs-travel-erp' ) . '</span>'; ?>
                                </p>
                                <p style="margin: 0 0 10px 0; font-size: 13px; color: #334155; line-height: 1.5;">
                                    <strong style="color: #64748b; font-size: 11px; text-transform: uppercase; display: block; margin-bottom: 2px;"><?php esc_html_e( 'Cancellation Policy:', 'ifs-travel-erp' ); ?></strong>
                                    <?php echo ! empty( $hotel->cancellation_policy ) ? nl2br( esc_html( $hotel->cancellation_policy ) ) : '<span style="color: #94a3b8; font-style: italic;">' . esc_html__( 'Standard hotel cancellation rules apply.', 'ifs-travel-erp' ) . '</span>'; ?>
                                </p>
                                <?php if ( ! empty( $hotel->blackout_dates_notes ) ) : ?>
                                    <p style="margin: 0; font-size: 13px; color: #991b1b; line-height: 1.5; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 10px;">
                                        <strong style="color: #b91c1c; font-size: 11px; text-transform: uppercase; display: block; margin-bottom: 2px;"><?php esc_html_e( 'Blackout Dates & Peak Surcharges:', 'ifs-travel-erp' ); ?></strong>
                                        <?php echo nl2br( esc_html( $hotel->blackout_dates_notes ) ); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Contracted Amenities & Hospitality Services List -->
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px;">
                        <h4 style="margin: 0 0 16px 0; font-size: 13.5px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.4px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                            <span class="dashicons dashicons-star-filled" style="color: #d97706;"></span> <?php esc_html_e( 'Contracted Amenities & Hospitality Services', 'ifs-travel-erp' ); ?>
                        </h4>
                        
                        <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 8px;">
                            <?php 
                            if ( ! empty( $amenities_list ) ) {
                                foreach ( $amenities_list as $it ) {
                                    if ( ! empty( $it ) ) {
                                        $amenity_lower = strtolower( (string) $it );
                                        $icon = 'dashicons-yes-alt';
                                        if ( strpos( $amenity_lower, 'wifi' ) !== false || strpos( $amenity_lower, 'internet' ) !== false ) {
                                            $icon = 'dashicons-networking';
                                        } elseif ( strpos( $amenity_lower, 'breakfast' ) !== false || strpos( $amenity_lower, 'meal' ) !== false || strpos( $amenity_lower, 'dining' ) !== false ) {
                                            $icon = 'dashicons-food';
                                        } elseif ( strpos( $amenity_lower, 'shuttle' ) !== false || strpos( $amenity_lower, 'transport' ) !== false || strpos( $amenity_lower, 'airport' ) !== false ) {
                                            $icon = 'dashicons-car';
                                        } elseif ( strpos( $amenity_lower, 'room service' ) !== false || strpos( $amenity_lower, 'concierge' ) !== false ) {
                                            $icon = 'dashicons-bell';
                                        } elseif ( strpos( $amenity_lower, 'ac' ) !== false || strpos( $amenity_lower, 'air conditioning' ) !== false ) {
                                            $icon = 'dashicons-marker';
                                        } elseif ( strpos( $amenity_lower, 'laundry' ) !== false ) {
                                            $icon = 'dashicons-tag';
                                        }

                                        echo '<li style="display: flex; align-items: center; gap: 12px; background: #ffffff; border: 1px solid #e2e8f0; padding: 12px 16px; border-radius: 8px; font-size: 13.5px; font-weight: 700; color: #1e293b;">';
                                        echo '<span class="dashicons ' . esc_attr( $icon ) . '" style="color: #003376; font-size: 18px; width: 18px; height: 18px; flex-shrink: 0;"></span>';
                                        echo '<span>' . esc_html( $it ) . '</span>';
                                        echo '</li>';
                                    }
                                }
                            } else {
                                echo '<li style="color: #94a3b8; font-style: italic; padding: 8px 0;">' . esc_html__( 'No specific amenities listed for this property.', 'ifs-travel-erp' ) . '</li>';
                            }
                            ?>
                        </ul>
                    </div>

                </div>
            </div>
        </div>
        <?php
    }
}