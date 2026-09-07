<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_tour_plan_view_panel' ) ) {
    /**
     * Complete Tour Package Dossier View Panel
     */
    function ifs_terp_tour_plan_view_panel() {
        global $wpdb;
        $table_plans     = $wpdb->prefix . 'iterp_tour_packages';
        $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
        $base_url        = admin_url( 'admin.php?page=ifs_travel_erp&tab=settings&sub=tour_pkg' );
        $plan_id         = isset( $_GET['plan_id'] ) ? absint( wp_unslash( $_GET['plan_id'] ) ) : 0;

        $plan = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_plans} WHERE id = %d", $plan_id ) );

        if ( ! $plan ) {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'Tour package plan not found.', 'ifs-travel-erp' ) . '</p></div>';
            return;
        }

        // Financial & Currency Setup
        $currency      = esc_html( $plan->rate_currency ?? 'BDT' );
        $currency_syms = array( 'BDT' => '৳', 'USD' => '$', 'EUR' => '€', 'SAR' => '﷼', 'AED' => 'د.إ' );
        $symbol        = $currency_syms[ $currency ] ?? $currency . ' ';

        $cost         = (float) ( $plan->cost_bdt ?? 0 );
        $sell         = (float) ( $plan->selling_price ?? 0 );
        $price_child  = (float) ( $plan->price_child ?? 0 );
        $price_infant = (float) ( $plan->price_infant ?? 0 );
        $margin       = $sell - $cost;
        $margin_pct   = ( $cost > 0 ) ? ( $margin / $cost ) * 100 : 0;

        // Fetch Supplier Name if linked
        $supplier_name = esc_html__( 'Direct Operation / None', 'ifs-travel-erp' );
        if ( ! empty( $plan->supplier_id ) && $plan->supplier_id > 0 ) {
            $sup_row = $wpdb->get_row( $wpdb->prepare( "SELECT supplier_name FROM {$table_suppliers} WHERE id = %d", $plan->supplier_id ) );
            if ( $sup_row && ! empty( $sup_row->supplier_name ) ) {
                $supplier_name = $sup_row->supplier_name;
            }
        }

        // Standard Amenities Map
        $amenities_map = array();
        if ( ! empty( $plan->inclusions_standard ) ) {
            $dec = json_decode( $plan->inclusions_standard, true );
            if ( is_array( $dec ) ) {
                $amenities_map = $dec;
            }
        }

        // Itinerary Decoder
        $itinerary_list = array();
        if ( ! empty( $plan->itinerary_json ) ) {
            $dec_itn = json_decode( $plan->itinerary_json, true );
            if ( is_array( $dec_itn ) ) {
                $itinerary_list = $dec_itn;
            }
        }

        // Inclusions List
        $inclusions_list = array();
        if ( ! empty( $plan->inclusions_text ) ) {
            $dec_inc = json_decode( $plan->inclusions_text, true );
            if ( is_array( $dec_inc ) ) {
                $inclusions_list = $dec_inc;
            } else {
                $inclusions_list = array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", '', $plan->inclusions_text ) ) ) );
            }
        }

        // Exclusions List
        $exclusions_list = array();
        if ( ! empty( $plan->exclusions_text ) ) {
            $dec_excl = json_decode( $plan->exclusions_text, true );
            if ( is_array( $dec_excl ) ) {
                $exclusions_list = $dec_excl;
            } else {
                $exclusions_list = array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", '', $plan->exclusions_text ) ) ) );
            }
        }
        ?>
        <div class="wrap" style="max-width: 1240px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #0f172a;">
            
            <!-- Top Navigation & Action Strip -->
            <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <a href="<?php echo esc_url( add_query_arg( 'action_sub', 'all', $base_url ) ); ?>" 
                   style="display: inline-flex; align-items: center; gap: 6px; background: #ffffff; color: #475569; border: 1px solid #cbd5e1; height: 42px; padding: 0 16px; border-radius: 8px; font-weight: 700; font-size: 13px; text-decoration: none; box-sizing: border-box;">
                    <span class="dashicons dashicons-arrow-left-alt2" style="font-size: 16px; width: 16px; height: 16px;"></span>
                    <span><?php esc_html_e( 'Back to Tour Directory', 'ifs-travel-erp' ); ?></span>
                </a>

                <div style="display: flex; gap: 8px; align-items: center;">
                    <a href="<?php echo esc_url( add_query_arg( array( 'action_sub' => 'edit', 'plan_id' => $plan->id ), $base_url ) ); ?>" 
                       style="display: inline-flex; align-items: center; gap: 6px; background: #003376; color: #ffffff; border: 1px solid #003376; height: 42px; padding: 0 18px; border-radius: 8px; font-weight: 700; font-size: 13px; text-decoration: none; box-sizing: border-box;">
                        <span class="dashicons dashicons-edit" style="font-size: 16px; width: 16px; height: 16px;"></span>
                        <span><?php esc_html_e( 'Edit Tour Package', 'ifs-travel-erp' ); ?></span>
                    </a>

                    <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action_sub' => 'delete', 'plan_id' => $plan->id ), $base_url ), 'delete_tour_plan_' . $plan->id ) ); ?>" 
                       onclick="return confirm('<?php echo esc_js( __( 'Delete this tour plan permanently?', 'ifs-travel-erp' ) ); ?>');" 
                       style="display: inline-flex; align-items: center; gap: 6px; background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; height: 42px; padding: 0 16px; border-radius: 8px; font-weight: 700; font-size: 13px; text-decoration: none; box-sizing: border-box;">
                        <span class="dashicons dashicons-trash" style="font-size: 16px; width: 16px; height: 16px;"></span>
                        <span><?php esc_html_e( 'Delete Package', 'ifs-travel-erp' ); ?></span>
                    </a>
                </div>
            </div>

            <!-- Master Dossier Card -->
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden;">
                
                <!-- Hero Header Strip -->
                <div style="background: linear-gradient(135deg, #0b1329 0%, #1e293b 100%); padding: 30px; color: #ffffff; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 8px; flex-wrap: wrap;">
                            <span style="background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase;">
                                <?php echo esc_html( $plan->package_type ?? 'Holiday Tour' ); ?>
                            </span>
                            <span style="background: rgba(251, 191, 36, 0.15); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3); padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 800;">
                                <?php echo esc_html( $plan->season_type ?? 'Regular' ); ?>
                            </span>
                            <?php if ( ! empty( $plan->package_code ) ) : ?>
                                <span style="background: rgba(255, 255, 255, 0.1); color: #e2e8f0; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; font-family: ui-monospace, monospace;">
                                    <?php echo esc_html( sprintf( 'SKU: %s', $plan->package_code ) ); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <h1 style="margin: 0; font-size: 26px; font-weight: 900; color: #ffffff; letter-spacing: -0.3px;"><?php echo esc_html( $plan->package_name ); ?></h1>
                        <p style="margin: 6px 0 0 0; font-size: 13.5px; color: #94a3b8; display: flex; align-items: center; gap: 6px;">
                            <span class="dashicons dashicons-location" style="font-size: 15px; width: 15px; height: 15px; color: #38bdf8;"></span>
                            <?php esc_html_e( 'Destination:', 'ifs-travel-erp' ); ?> <strong style="color: #ffffff;"><?php echo esc_html( $plan->destination ); ?></strong>
                            &bull; <span class="dashicons dashicons-calendar" style="font-size: 14px; width: 14px; height: 14px; color: #38bdf8;"></span>
                            <strong style="color: #e2e8f0;"><?php echo intval( $plan->total_days ); ?> <?php esc_html_e( 'Days', 'ifs-travel-erp' ); ?> / <?php echo intval( $plan->total_nights ); ?> <?php esc_html_e( 'Nights', 'ifs-travel-erp' ); ?></strong>
                        </p>
                    </div>
                    
                    <div style="text-align: right; background: rgba(255, 255, 255, 0.06); border: 1px solid rgba(255, 255, 255, 0.1); padding: 12px 18px; border-radius: 10px;">
                        <span style="font-size: 10.5px; font-weight: 700; color: #94a3b8; text-transform: uppercase; display: block; letter-spacing: 0.5px;"><?php esc_html_e( 'Group Capacity & Validity', 'ifs-travel-erp' ); ?></span>
                        <span style="font-size: 13px; font-weight: 800; color: #ffffff; display: block; margin-top: 2px;">
                            <?php esc_html_e( 'Pax:', 'ifs-travel-erp' ); ?> <span style="color: #38bdf8; font-family: ui-monospace, monospace;"><?php echo intval( $plan->min_pax ?? 2 ); ?> &ndash; <?php echo intval( $plan->max_pax ?? 40 ); ?></span> &bull; 
                            <?php esc_html_e( 'Valid:', 'ifs-travel-erp' ); ?> <span style="color: #4ade80;"><?php echo ( ! empty( $plan->valid_till ) && $plan->valid_till !== '1970-01-01' ) ? esc_html( date_i18n( 'd M Y', strtotime( $plan->valid_till ) ) ) : esc_html__( 'Open-Ended', 'ifs-travel-erp' ); ?></span>
                        </span>
                    </div>
                </div>

                <!-- Content Body Area -->
                <div style="padding: 30px;">

                    <!-- Financial Metric Cards -->
                    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 14px; margin-bottom: 28px;">
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px;">
                            <span style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase;"><?php esc_html_e( 'Cost Rate (Adult)', 'ifs-travel-erp' ); ?></span>
                            <h3 style="margin: 4px 0 0 0; font-family: ui-monospace, monospace; font-size: 18px; font-weight: 900; color: #0f172a;"><?php echo esc_html( $symbol . number_format( $cost, 2 ) ); ?></h3>
                        </div>
                        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 16px;">
                            <span style="font-size: 10.5px; font-weight: 700; color: #166534; text-transform: uppercase;"><?php esc_html_e( 'Selling Price (Adult)', 'ifs-travel-erp' ); ?></span>
                            <h3 style="margin: 4px 0 0 0; font-family: ui-monospace, monospace; font-size: 18px; font-weight: 900; color: #059669;"><?php echo esc_html( $symbol . number_format( $sell, 2 ) ); ?></h3>
                        </div>
                        <div style="background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 12px; padding: 16px;">
                            <span style="font-size: 10.5px; font-weight: 700; color: #6b21a8; text-transform: uppercase;"><?php esc_html_e( 'Child Fare', 'ifs-travel-erp' ); ?></span>
                            <h3 style="margin: 4px 0 0 0; font-family: ui-monospace, monospace; font-size: 18px; font-weight: 900; color: #6b21a8;"><?php echo esc_html( $symbol . number_format( $price_child, 2 ) ); ?></h3>
                        </div>
                        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 16px;">
                            <span style="font-size: 10.5px; font-weight: 700; color: #92400e; text-transform: uppercase;"><?php esc_html_e( 'Infant Rate', 'ifs-travel-erp' ); ?></span>
                            <h3 style="margin: 4px 0 0 0; font-family: ui-monospace, monospace; font-size: 18px; font-weight: 900; color: #b45309;"><?php echo esc_html( $symbol . number_format( $price_infant, 2 ) ); ?></h3>
                        </div>
                        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 16px;">
                            <span style="font-size: 10.5px; font-weight: 700; color: #1e40af; text-transform: uppercase;"><?php esc_html_e( 'Adult Margin', 'ifs-travel-erp' ); ?></span>
                            <h3 style="margin: 4px 0 0 0; font-family: ui-monospace, monospace; font-size: 18px; font-weight: 900; color: #003376;">
                                <?php echo esc_html( $symbol . number_format( $margin, 2 ) ); ?>
                                <small style="font-size: 11px; font-weight: 700; color: <?php echo ( $margin >= 0 ) ? '#059669' : '#dc2626'; ?>;">(<?php echo esc_html( number_format( $margin_pct, 1 ) ); ?>%)</small>
                            </h3>
                        </div>
                    </div>

                    <!-- Two-Column Parameters Grid -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                        
                        <!-- Operations & Logistics Details -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px;">
                            <h4 style="margin: 0 0 16px 0; font-size: 13.5px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.4px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                                <span class="dashicons dashicons-media-spreadsheet" style="color: #003376;"></span> <?php esc_html_e( 'Logistics & Accommodation Setup', 'ifs-travel-erp' ); ?>
                            </h4>
                            
                            <ul style="margin: 0; padding: 0; list-style: none; font-size: 13px; color: #334155; line-height: 2.1;">
                                <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                    <span style="color: #64748b;"><?php esc_html_e( 'Departure Point / Pickup:', 'ifs-travel-erp' ); ?></span>
                                    <strong style="color: #0f172a;"><?php echo esc_html( $plan->departure_point ?: __( 'Not specified', 'ifs-travel-erp' ) ); ?></strong>
                                </li>
                                <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                    <span style="color: #64748b;"><?php esc_html_e( 'Primary Transport Mode:', 'ifs-travel-erp' ); ?></span>
                                    <strong style="color: #0f172a;"><?php echo esc_html( $plan->transport_mode ?? __( 'Tourist Coach / Bus', 'ifs-travel-erp' ) ); ?></strong>
                                </li>
                                <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                    <span style="color: #64748b;"><?php esc_html_e( 'Assigned Supplier / DMC:', 'ifs-travel-erp' ); ?></span>
                                    <strong style="color: #0f172a;"><?php echo esc_html( $supplier_name ); ?></strong>
                                </li>
                                <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                    <span style="color: #64748b;"><?php esc_html_e( 'Activity / Difficulty Level:', 'ifs-travel-erp' ); ?></span>
                                    <strong style="color: #0f172a;"><?php echo esc_html( $plan->difficulty_level ?? __( 'Easy / Leisure', 'ifs-travel-erp' ) ); ?></strong>
                                </li>
                                <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                    <span style="color: #64748b;"><?php esc_html_e( 'Partner Hotel / Resort:', 'ifs-travel-erp' ); ?></span>
                                    <strong style="color: #0f172a;"><?php echo esc_html( $plan->hotel_name ?: __( 'Standard Hotel / Resort', 'ifs-travel-erp' ) ); ?></strong>
                                </li>
                                <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                    <span style="color: #64748b;"><?php esc_html_e( 'Group Capacity Limits:', 'ifs-travel-erp' ); ?></span>
                                    <strong style="font-family: ui-monospace, monospace; color: #0f172a;"><?php echo intval( $plan->min_pax ?? 2 ); ?> &ndash; <?php echo intval( $plan->max_pax ?? 40 ); ?> <?php esc_html_e( 'Persons', 'ifs-travel-erp' ); ?></strong>
                                </li>
                                <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                    <span style="color: #64748b;"><?php esc_html_e( 'Child Policy:', 'ifs-travel-erp' ); ?></span>
                                    <strong style="color: #0f172a;"><?php echo esc_html( $plan->child_policy ?: __( 'Standard policies apply', 'ifs-travel-erp' ) ); ?></strong>
                                </li>
                                <?php if ( ! empty( $plan->package_image_url ) ) : ?>
                                    <li style="display: flex; justify-content: space-between; padding-top: 4px; align-items: center;">
                                        <span style="color: #64748b;"><?php esc_html_e( 'Brochure / Media File:', 'ifs-travel-erp' ); ?></span>
                                        <a href="<?php echo esc_url( $plan->package_image_url ); ?>" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 4px; background: #ffffff; color: #003376; border: 1px solid #cbd5e1; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; text-decoration: none;">
                                            <span class="dashicons dashicons-external" style="font-size: 13px; width: 13px; height: 13px;"></span> <?php esc_html_e( 'View Media', 'ifs-travel-erp' ); ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>

                        <!-- Payment Terms & Back-office Notes Panel -->
                        <div style="display: flex; flex-direction: column; gap: 20px;">
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px;">
                                <h4 style="margin: 0 0 12px 0; font-size: 13.5px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.4px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                                    <span class="dashicons dashicons-money-alt" style="color: #003376;"></span> <?php esc_html_e( 'Financial Terms & Booking Policies', 'ifs-travel-erp' ); ?>
                                </h4>
                                <p style="margin: 0; font-size: 13px; color: #334155; line-height: 1.6;">
                                    <strong style="color: #64748b; font-size: 11px; text-transform: uppercase; display: block; margin-bottom: 4px;"><?php esc_html_e( 'Payment Terms & Cancellation:', 'ifs-travel-erp' ); ?></strong>
                                    <?php echo ! empty( $plan->payment_policy ) ? nl2br( esc_html( $plan->payment_policy ) ) : '<span style="color: #94a3b8; font-style: italic;">' . esc_html__( '50% advance upon booking, balance prior to trip commencement.', 'ifs-travel-erp' ) . '</span>'; ?>
                                </p>
                            </div>

                            <?php if ( ! empty( $plan->admin_notes ) ) : ?>
                                <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 22px;">
                                    <h4 style="margin: 0 0 8px 0; font-size: 13.5px; font-weight: 800; color: #b45309; text-transform: uppercase; letter-spacing: 0.4px; display: flex; align-items: center; gap: 8px;">
                                        <span class="dashicons dashicons-lock" style="color: #d97706;"></span> <?php esc_html_e( 'Internal Back-Office Notes', 'ifs-travel-erp' ); ?>
                                    </h4>
                                    <p style="margin: 0; font-size: 12.5px; color: #78350f; line-height: 1.5;">
                                        <?php echo nl2br( esc_html( $plan->admin_notes ) ); ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Day-by-Day Tour Itinerary Timeline -->
                    <?php if ( ! empty( $itinerary_list ) ) : ?>
                        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px; margin-bottom: 24px;">
                            <h4 style="margin: 0 0 18px 0; font-size: 13.5px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.4px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                                <span class="dashicons dashicons-calendar-alt" style="color: #003376;"></span> <?php esc_html_e( 'Day-by-Day Daily Tour Itinerary', 'ifs-travel-erp' ); ?>
                            </h4>
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <?php foreach ( $itinerary_list as $day_step ) : ?>
                                    <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 14px 18px; display: flex; flex-direction: column; gap: 6px;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <span style="background: #003376; color: #ffffff; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 800; font-family: ui-monospace, monospace;">
                                                Day <?php echo intval( $day_step['day'] ?? 1 ); ?>
                                            </span>
                                            <strong style="font-size: 14px; color: #0f172a;"><?php echo esc_html( $day_step['title'] ?? '' ); ?></strong>
                                        </div>
                                        <?php if ( ! empty( $day_step['desc'] ) ) : ?>
                                            <p style="margin: 4px 0 0 0; font-size: 12.5px; color: #475569; line-height: 1.6;">
                                                <?php echo nl2br( esc_html( $day_step['desc'] ) ); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Included Amenities & Cost Structure Matrix -->
                    <?php if ( ! empty( $amenities_map ) ) : ?>
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px; margin-bottom: 24px;">
                            <h4 style="margin: 0 0 16px 0; font-size: 13.5px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.4px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                                <span class="dashicons dashicons-star-filled" style="color: #d97706;"></span> <?php esc_html_e( 'Standard Included Amenities & Cost Breakdown', 'ifs-travel-erp' ); ?>
                            </h4>
                            
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                                <?php foreach ( $amenities_map as $amenity_name => $amenity_cost ) : ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center; background: #ffffff; border: 1px solid #cbd5e1; padding: 10px 14px; border-radius: 8px; font-size: 13px; font-weight: 700; color: #1e293b;">
                                        <span style="display: inline-flex; align-items: center; gap: 8px;">
                                            <span class="dashicons dashicons-yes-alt" style="color: #059669; font-size: 16px; width: 16px; height: 16px;"></span>
                                            <?php echo esc_html( $amenity_name ); ?>
                                        </span>
                                        <strong style="font-family: ui-monospace, monospace; color: #003376;"><?php echo esc_html( $symbol . number_format( (float) $amenity_cost, 2 ) ); ?></strong>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Inclusions & Exclusions Side-by-Side Lists -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                        
                        <!-- What is Included -->
                        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 22px;">
                            <h4 style="margin: 0 0 14px 0; font-size: 13.5px; font-weight: 800; color: #166534; text-transform: uppercase; letter-spacing: 0.4px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #bbf7d0; padding-bottom: 10px;">
                                <span class="dashicons dashicons-yes-alt" style="color: #16a34a;"></span> <?php esc_html_e( 'Detailed Itinerary Inclusions', 'ifs-travel-erp' ); ?>
                            </h4>
                            <?php if ( ! empty( $inclusions_list ) ) : ?>
                                <ul style="margin: 0; padding-left: 18px; font-size: 13px; color: #1e293b; line-height: 2.0;">
                                    <?php foreach ( $inclusions_list as $inc ) : ?>
                                        <li><?php echo esc_html( $inc ); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else : ?>
                                <p style="margin: 0; font-size: 12.5px; color: #64748b; font-style: italic;"><?php esc_html_e( 'No detailed inclusions configured.', 'ifs-travel-erp' ); ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- What is Excluded -->
                        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 22px;">
                            <h4 style="margin: 0 0 14px 0; font-size: 13.5px; font-weight: 800; color: #991b1b; text-transform: uppercase; letter-spacing: 0.4px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #fecaca; padding-bottom: 10px;">
                                <span class="dashicons dashicons-dismiss" style="color: #dc2626;"></span> <?php esc_html_e( 'Exclusions & Extra Charges', 'ifs-travel-erp' ); ?>
                            </h4>
                            <?php if ( ! empty( $exclusions_list ) ) : ?>
                                <ul style="margin: 0; padding-left: 18px; font-size: 13px; color: #1e293b; line-height: 2.0;">
                                    <?php foreach ( $exclusions_list as $excl ) : ?>
                                        <li><?php echo esc_html( $excl ); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else : ?>
                                <p style="margin: 0; font-size: 12.5px; color: #64748b; font-style: italic;"><?php esc_html_e( 'No exclusions specified.', 'ifs-travel-erp' ); ?></p>
                            <?php endif; ?>
                        </div>

                    </div>

                </div>
            </div>
        </div>
        <?php
    }
}