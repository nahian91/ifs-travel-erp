<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_settings_hajj_pkg_view_panel' ) ) {
    /**
     * Hajj & Umrah Package Template Viewer Panel
     */
    function ifs_terp_settings_hajj_pkg_view_panel() {
        global $wpdb;
        $table_pkgs = $wpdb->prefix . 'iterp_hajj_packages';
        $base_url   = admin_url( 'admin.php?page=ifs_travel_erp&tab=settings&sub=hajj_pkg' );
        $pkg_id     = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;

        $pkg = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_pkgs} WHERE id = %d", $pkg_id ) );

        if ( ! $pkg ) {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'Package template not found.', 'ifs-travel-erp' ) . '</p></div>';
            return;
        }

        $cost_bdt     = (float) ( $pkg->cost_bdt ?? 0 );
        $cost_sar     = (float) ( $pkg->cost_sar ?? 0 );
        $price_quad   = (float) ( $pkg->price_quad ?? 0 );
        $price_triple = (float) ( $pkg->price_triple ?? 0 );
        $price_double = (float) ( $pkg->price_double ?? 0 );
        $price_single = (float) ( $pkg->price_single ?? 0 );
        $price_child  = (float) ( $pkg->price_child ?? 0 );
        $price_infant = (float) ( $pkg->price_infant ?? 0 );

        $margin     = $price_quad - $cost_bdt;
        $margin_pct = ( $cost_bdt > 0 ) ? ( $margin / $cost_bdt ) * 100 : 0;

        $inclusions = array();
        if ( ! empty( $pkg->inclusions_json ) ) {
            $dec_inc = json_decode( $pkg->inclusions_json, true );
            if ( is_array( $dec_inc ) ) {
                $inclusions = $dec_inc;
            }
        }

        $exclusions = array();
        if ( ! empty( $pkg->exclusions_json ) ) {
            $dec_excl = json_decode( $pkg->exclusions_json, true );
            if ( is_array( $dec_excl ) ) {
                $exclusions = $dec_excl;
            }
        }
        ?>
        <div class="wrap" style="max-width: 1240px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
            
            <!-- Top Navigation & Action Bar -->
            <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <a href="<?php echo esc_url( add_query_arg( 'action_sub', 'all', $base_url ) ); ?>" 
                   class="ifs-nav-btn back" 
                   style="display: inline-flex; align-items: center; gap: 6px; background: #ffffff; color: #475569; border: 1px solid #cbd5e1; padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 13px; text-decoration: none; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04); transition: all 0.2s ease;">
                    <span class="dashicons dashicons-arrow-left-alt2" style="font-size: 16px; width: 16px; height: 16px;"></span>
                    <span><?php esc_html_e( 'Back to Package Directory', 'ifs-travel-erp' ); ?></span>
                </a>

                <div style="display: flex; gap: 8px; align-items: center;">
                    <a href="<?php echo esc_url( add_query_arg( array( 'action_sub' => 'edit', 'id' => $pkg->id ), $base_url ) ); ?>" 
                       class="ifs-nav-btn edit" 
                       style="display: inline-flex; align-items: center; gap: 6px; background: #047857; color: #ffffff; border: 1px solid #047857; padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 13px; text-decoration: none; box-shadow: 0 2px 4px rgba(4, 120, 87, 0.2); transition: all 0.2s ease;">
                        <span class="dashicons dashicons-edit" style="font-size: 16px; width: 16px; height: 16px;"></span>
                        <span><?php esc_html_e( 'Edit Package Template', 'ifs-travel-erp' ); ?></span>
                    </a>

                    <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action_sub' => 'delete', 'id' => $pkg->id ), $base_url ), 'delete_hajj_pkg_' . $pkg->id ) ); ?>" 
                       class="ifs-nav-btn delete" 
                       onclick="return confirm('<?php echo esc_js( __( 'Delete this package template permanently?', 'ifs-travel-erp' ) ); ?>');" 
                       style="display: inline-flex; align-items: center; gap: 6px; background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 13px; text-decoration: none; transition: all 0.2s ease;">
                        <span class="dashicons dashicons-trash" style="font-size: 16px; width: 16px; height: 16px;"></span>
                        <span><?php esc_html_e( 'Delete Package', 'ifs-travel-erp' ); ?></span>
                    </a>
                </div>
            </div>

            <!-- Main Container Card -->
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);">
                
                <!-- Hero Header Banner -->
                <div style="background: linear-gradient(135deg, #064e3b 0%, #047857 100%); padding: 30px; color: #ffffff; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 8px; flex-wrap: wrap;">
                            <span style="background: rgba(255, 255, 255, 0.15); color: #a7f3d0; border: 1px solid rgba(255, 255, 255, 0.3); padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase;">
                                <?php echo esc_html( $pkg->package_type ?? 'Umrah' ); ?>
                            </span>
                            <span style="background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.4); padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 800;">
                                <?php echo esc_html( $pkg->visa_type ?? 'Umrah Visa (B2B/B2C)' ); ?>
                            </span>
                            <?php if ( ! empty( $pkg->season_year ) ) : ?>
                                <span style="background: rgba(0, 0, 0, 0.25); color: #ffffff; border: 1px solid rgba(255, 255, 255, 0.2); padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; font-family: monospace;">
                                    <?php echo esc_html( $pkg->season_year ); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <h1 style="margin: 0; font-size: 26px; font-weight: 900; color: #ffffff; letter-spacing: -0.3px;"><?php echo esc_html( $pkg->package_name ); ?></h1>
                        <p style="margin: 6px 0 0 0; font-size: 13.5px; color: #a7f3d0; display: flex; align-items: center; gap: 6px;">
                            <span class="dashicons dashicons-clock" style="font-size: 15px; width: 15px; height: 15px; color: #34d399;"></span>
                            <?php esc_html_e( 'Duration:', 'ifs-travel-erp' ); ?> <strong style="color: #ffffff;"><?php echo intval( $pkg->total_days ); ?> <?php esc_html_e( 'Days', 'ifs-travel-erp' ); ?></strong> 
                            (&bull; <?php echo intval( $pkg->nights_makkah ?? 0 ); ?> <?php esc_html_e( 'Nights Makkah', 'ifs-travel-erp' ); ?> &bull; <?php echo intval( $pkg->nights_madinah ?? 0 ); ?> <?php esc_html_e( 'Nights Madinah', 'ifs-travel-erp' ); ?>)
                        </p>
                    </div>
                    
                    <div style="text-align: right; background: rgba(0, 0, 0, 0.2); border: 1px solid rgba(255, 255, 255, 0.1); padding: 12px 18px; border-radius: 10px;">
                        <span style="font-size: 10.5px; font-weight: 700; color: #a7f3d0; text-transform: uppercase; display: block; letter-spacing: 0.5px;"><?php esc_html_e( 'Group Capacity', 'ifs-travel-erp' ); ?></span>
                        <span style="font-size: 14px; font-weight: 800; color: #ffffff; display: block; margin-top: 2px;">
                            <span style="color: #34d399; font-family: monospace; font-size: 16px;"><?php echo intval( $pkg->capacity ?? 0 ); ?></span> <?php esc_html_e( 'Pilgrim Seats', 'ifs-travel-erp' ); ?>
                        </span>
                    </div>
                </div>

                <!-- Content Body Area -->
                <div style="padding: 30px;">

                    <!-- Pricing & Margin Matrix Header -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <span style="font-size: 11px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">
                            <?php esc_html_e( 'Sharing Configuration & Financial Margin', 'ifs-travel-erp' ); ?>
                        </span>
                        <span style="font-size: 12px; font-weight: 700; color: <?php echo ( $margin >= 0 ) ? '#059669' : '#dc2626'; ?>;">
                            <?php 
                            printf(
                                /* translators: 1: margin amount, 2: margin percentage */
                                esc_html__( 'Quad Margin: ৳%1$s (%2$s%%)', 'ifs-travel-erp' ),
                                esc_html( number_format( $margin, 2 ) ),
                                esc_html( number_format( $margin_pct, 1 ) )
                            ); 
                            ?>
                        </span>
                    </div>

                    <!-- Financial Pricing Tiers Grid -->
                    <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; margin-bottom: 28px;">
                        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; padding: 16px;">
                            <span style="font-size: 10.5px; font-weight: 700; color: #047857; text-transform: uppercase;"><?php esc_html_e( 'Quad Share', 'ifs-travel-erp' ); ?></span>
                            <h3 style="margin: 4px 0 0 0; font-family: monospace; font-size: 17px; font-weight: 900; color: #064e3b;">৳<?php echo esc_html( number_format( $price_quad, 2 ) ); ?></h3>
                        </div>
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px;">
                            <span style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase;"><?php esc_html_e( 'Triple Share', 'ifs-travel-erp' ); ?></span>
                            <h3 style="margin: 4px 0 0 0; font-family: monospace; font-size: 17px; font-weight: 900; color: #0f172a;">৳<?php echo esc_html( number_format( $price_triple, 2 ) ); ?></h3>
                        </div>
                        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 16px;">
                            <span style="font-size: 10.5px; font-weight: 700; color: #1e40af; text-transform: uppercase;"><?php esc_html_e( 'Double Share', 'ifs-travel-erp' ); ?></span>
                            <h3 style="margin: 4px 0 0 0; font-family: monospace; font-size: 17px; font-weight: 900; color: #0284c7;">৳<?php echo esc_html( number_format( $price_double, 2 ) ); ?></h3>
                        </div>
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px;">
                            <span style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase;"><?php esc_html_e( 'Single Room', 'ifs-travel-erp' ); ?></span>
                            <h3 style="margin: 4px 0 0 0; font-family: monospace; font-size: 17px; font-weight: 900; color: #475569;">৳<?php echo esc_html( number_format( $price_single, 2 ) ); ?></h3>
                        </div>
                        <div style="background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 12px; padding: 16px;">
                            <span style="font-size: 10.5px; font-weight: 700; color: #6b21a8; text-transform: uppercase;"><?php esc_html_e( 'Child Rate', 'ifs-travel-erp' ); ?></span>
                            <h3 style="margin: 4px 0 0 0; font-family: monospace; font-size: 17px; font-weight: 900; color: #6b21a8;">৳<?php echo esc_html( number_format( $price_child, 2 ) ); ?></h3>
                        </div>
                        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 16px;">
                            <span style="font-size: 10.5px; font-weight: 700; color: #92400e; text-transform: uppercase;"><?php esc_html_e( 'Infant Rate', 'ifs-travel-erp' ); ?></span>
                            <h3 style="margin: 4px 0 0 0; font-family: monospace; font-size: 17px; font-weight: 900; color: #b45309;">৳<?php echo esc_html( number_format( $price_infant, 2 ) ); ?></h3>
                        </div>
                    </div>

                    <!-- Logistics & Operations Parameters Grid -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                        
                        <!-- Makkah & Madinah Hotels -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px;">
                            <h4 style="margin: 0 0 16px 0; font-size: 13.5px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.4px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                                <span class="dashicons dashicons-building" style="color: #047857;"></span> <?php esc_html_e( 'Accommodation & Haram Distances', 'ifs-travel-erp' ); ?>
                            </h4>
                            
                            <ul style="margin: 0; padding: 0; list-style: none; font-size: 13px; color: #334155; line-height: 2.1;">
                                <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                    <span style="color: #64748b;"><?php esc_html_e( 'Makkah Hotel:', 'ifs-travel-erp' ); ?></span>
                                    <strong style="color: #0f172a;"><?php echo esc_html( $pkg->hotel_makkah ?: __( 'Standard Hotel', 'ifs-travel-erp' ) ); ?></strong>
                                </li>
                                <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                    <span style="color: #64748b;"><?php esc_html_e( 'Makkah Haram Distance:', 'ifs-travel-erp' ); ?></span>
                                    <strong style="color: #047857;"><?php echo esc_html( $pkg->makkah_distance ?: __( 'Not specified', 'ifs-travel-erp' ) ); ?></strong>
                                </li>
                                <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                    <span style="color: #64748b;"><?php esc_html_e( 'Madinah Hotel:', 'ifs-travel-erp' ); ?></span>
                                    <strong style="color: #0f172a;"><?php echo esc_html( $pkg->hotel_madinah ?: __( 'Standard Hotel', 'ifs-travel-erp' ) ); ?></strong>
                                </li>
                                <li style="display: flex; justify-content: space-between; padding-top: 3px;">
                                    <span style="color: #64748b;"><?php esc_html_e( 'Madinah Haram Distance:', 'ifs-travel-erp' ); ?></span>
                                    <strong style="color: #047857;"><?php echo esc_html( $pkg->madinah_distance ?: __( 'Not specified', 'ifs-travel-erp' ) ); ?></strong>
                                </li>
                            </ul>
                        </div>

                        <!-- Transport, Meals & Costs Breakdown -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px;">
                            <h4 style="margin: 0 0 16px 0; font-size: 13.5px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.4px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                                <span class="dashicons dashicons-car" style="color: #003376;"></span> <?php esc_html_e( 'Transport, Flight & Operations', 'ifs-travel-erp' ); ?>
                            </h4>
                            
                            <ul style="margin: 0; padding: 0; list-style: none; font-size: 13px; color: #334155; line-height: 2.1;">
                                <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                    <span style="color: #64748b;"><?php esc_html_e( 'Ground Transport:', 'ifs-travel-erp' ); ?></span>
                                    <strong style="color: #0f172a;"><?php echo esc_html( $pkg->transport_type ?? 'AC Bus / Coaster Sharing' ); ?></strong>
                                </li>
                                <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                    <span style="color: #64748b;"><?php esc_html_e( 'Meal Plan Allocation:', 'ifs-travel-erp' ); ?></span>
                                    <strong style="color: #0f172a;"><?php echo esc_html( $pkg->meal_plan ?? 'Full Board (Buffet)' ); ?></strong>
                                </li>
                                <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                    <span style="color: #64748b;"><?php esc_html_e( 'Carrier & Sectors:', 'ifs-travel-erp' ); ?></span>
                                    <strong style="color: #0f172a;">
                                        <?php echo esc_html( $pkg->airline_name ?: 'Saudia / Biman' ); ?>
                                        <?php if ( ! empty( $pkg->flight_routing ) ) : ?>
                                            <span style="font-family: monospace; font-size: 11.5px; color: #0284c7;">(<?php echo esc_html( $pkg->flight_routing ); ?>)</span>
                                        <?php endif; ?>
                                    </strong>
                                </li>
                                <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                    <span style="color: #64748b;"><?php esc_html_e( 'Mina / Arafat Tent:', 'ifs-travel-erp' ); ?></span>
                                    <strong style="color: #0f172a;"><?php echo esc_html( $pkg->mina_category ?: __( 'Standard Tent Services', 'ifs-travel-erp' ) ); ?></strong>
                                </li>
                                <li style="display: flex; justify-content: space-between; padding-top: 3px;">
                                    <span style="color: #64748b;"><?php esc_html_e( 'Estimated Unit Cost:', 'ifs-travel-erp' ); ?></span>
                                    <strong style="font-family: monospace; color: #0284c7;">৳<?php echo esc_html( number_format( $cost_bdt, 2 ) ); ?> (<?php echo esc_html( number_format( $cost_sar, 2 ) ); ?> SAR)</strong>
                                </li>
                            </ul>
                        </div>

                    </div>

                    <!-- Inclusions & Exclusions Side-by-Side Lists -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                        
                        <!-- What is Included -->
                        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 22px;">
                            <h4 style="margin: 0 0 14px 0; font-size: 13.5px; font-weight: 800; color: #166534; text-transform: uppercase; letter-spacing: 0.4px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #bbf7d0; padding-bottom: 10px;">
                                <span class="dashicons dashicons-yes-alt" style="color: #16a34a;"></span> <?php esc_html_e( 'Package Inclusions', 'ifs-travel-erp' ); ?>
                            </h4>
                            <?php if ( ! empty( $inclusions ) ) : ?>
                                <ul style="margin: 0; padding-left: 18px; font-size: 13px; color: #1e293b; line-height: 2.0;">
                                    <?php foreach ( $inclusions as $inc ) : ?>
                                        <li><?php echo esc_html( $inc ); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else : ?>
                                <p style="margin: 0; font-size: 12.5px; color: #64748b; font-style: italic;"><?php esc_html_e( 'No specific inclusions configured.', 'ifs-travel-erp' ); ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- What is Excluded -->
                        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 22px;">
                            <h4 style="margin: 0 0 14px 0; font-size: 13.5px; font-weight: 800; color: #991b1b; text-transform: uppercase; letter-spacing: 0.4px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #fecaca; padding-bottom: 10px;">
                                <span class="dashicons dashicons-dismiss" style="color: #dc2626;"></span> <?php esc_html_e( 'Package Exclusions', 'ifs-travel-erp' ); ?>
                            </h4>
                            <?php if ( ! empty( $exclusions ) ) : ?>
                                <ul style="margin: 0; padding-left: 18px; font-size: 13px; color: #1e293b; line-height: 2.0;">
                                    <?php foreach ( $exclusions as $excl ) : ?>
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