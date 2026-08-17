<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enterprise Ultra-Modern Hajj & Umrah Fixed Packages Configuration Console
 * Features: Multi-Tier Pricing (Quad/Triple/Double/Single), SAR to BDT Real-time Calculator, Hotel Distance & Inclusions Vault, Live Brochure Card Preview
 */
function ifs_terp_hajj_packages_page() {
    global $wpdb;
    $table_packages = $wpdb->prefix . 'iterp_hajj_packages';

    $action_sub = isset( $_GET['action_sub'] ) ? sanitize_text_field( $_GET['action_sub'] ) : '';
    $pkg_id     = isset( $_GET['pkg_id'] ) ? intval( $_GET['pkg_id'] ) : 0;
    $message    = '';
    $base_url   = admin_url( 'admin.php?page=ifs_travel_erp&tab=hajj_umrah&sub=packages' );

    /* =========================================================================
       1. DELETE PACKAGE RECORD
       ========================================================================= */
    if ( $action_sub === 'delete' && $pkg_id > 0 ) {
        if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'delete_pkg_' . $pkg_id ) ) {
            $pkg_name = $wpdb->get_var( $wpdb->prepare( "SELECT package_name FROM $table_packages WHERE id = %d", $pkg_id ) );
            $wpdb->delete( $table_packages, array( 'id' => $pkg_id ), array( '%d' ) );
            if ( function_exists( 'ifs_terp_log_activity' ) ) {
                ifs_terp_log_activity( "Deleted Package Plan: #" . $pkg_id . " (" . $pkg_name . ")" );
            }
            wp_safe_redirect( add_query_arg( array( 'page' => 'ifs_travel_erp', 'tab' => 'hajj_umrah', 'sub' => 'packages', 'msg' => 'deleted' ), admin_url( 'admin.php' ) ) );
            exit;
        } else {
            $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> Security token verification failed.</div>';
        }
    }

    if ( isset( $_GET['msg'] ) && $_GET['msg'] === 'deleted' ) {
        $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Package plan removed successfully.</div>';
    }

    /* =========================================================================
       2. FORM SUBMIT HANDLER (ADD / UPDATE)
       ========================================================================= */
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_hajj_pkg_submit'] ) ) {
        check_admin_referer( 'ifs_hajj_pkg_nonce_action', 'ifs_hajj_pkg_nonce' );

        $is_edit_mode = ( isset( $_POST['edit_pkg_id'] ) && intval( $_POST['edit_pkg_id'] ) > 0 );
        $edit_id      = $is_edit_mode ? intval( $_POST['edit_pkg_id'] ) : 0;

        $package_name    = sanitize_text_field( $_POST['package_name'] ?? '' );
        $package_type    = sanitize_text_field( $_POST['package_type'] ?? 'Umrah' );
        $total_days      = intval( $_POST['total_days'] ?? 15 );
        $cost_bdt        = floatval( $_POST['cost_bdt'] ?? 0 );
        $selling_price   = floatval( $_POST['selling_price'] ?? $cost_bdt );
        $cost_sar        = floatval( $_POST['cost_sar'] ?? 0 );
        $capacity        = intval( $_POST['capacity'] ?? 40 );
        $hotel_makkah    = sanitize_text_field( $_POST['hotel_makkah'] ?? '' );
        $makkah_distance = sanitize_text_field( $_POST['makkah_distance'] ?? '' );
        $hotel_madinah   = sanitize_text_field( $_POST['hotel_madinah'] ?? '' );
        $madinah_distance= sanitize_text_field( $_POST['madinah_distance'] ?? '' );
        $airline_name    = sanitize_text_field( $_POST['airline_name'] ?? 'Biman / Saudia' );
        $inclusions_json = sanitize_textarea_field( $_POST['inclusions'] ?? '' );

        if ( empty( $package_name ) ) {
            $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> Package name is required.</div>';
        } else {
            $data_array = array(
                'package_name'     => $package_name,
                'package_type'     => $package_type,
                'total_days'       => $total_days,
                'cost_bdt'         => $cost_bdt,
                'selling_price'    => $selling_price,
                'cost_sar'         => $cost_sar,
                'capacity'         => $capacity,
                'hotel_makkah'     => $hotel_makkah,
                'makkah_distance'  => $makkah_distance,
                'hotel_madinah'    => $hotel_madinah,
                'madinah_distance' => $madinah_distance,
                'airline_name'     => $airline_name,
                'inclusions_json'  => $inclusions_json,
            );

            if ( $is_edit_mode ) {
                $wpdb->update( $table_packages, $data_array, array( 'id' => $edit_id ) );
                if ( function_exists( 'ifs_terp_log_activity' ) ) {
                    ifs_terp_log_activity( "Updated Package Plan: " . $package_name . " (ID: #" . $edit_id . ")" );
                }
                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Package plan updated successfully.</div>';
            } else {
                $data_array['created_at'] = current_time( 'mysql' );
                $wpdb->insert( $table_packages, $data_array );
                $new_id = $wpdb->insert_id;
                if ( function_exists( 'ifs_terp_log_activity' ) ) {
                    ifs_terp_log_activity( "Created Package Plan: " . $package_name . " (ID: #" . $new_id . ")" );
                }
                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> New Package plan registered successfully.</div>';
            }
        }
    }

    /* =========================================================================
       3. FETCH EDIT RECORD IF REQUESTED
       ========================================================================= */
    $edit_data = false;
    if ( $action_sub === 'edit' && $pkg_id > 0 ) {
        $edit_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_packages WHERE id = %d", $pkg_id ) );
    }

    // Default Fallbacks
    $val_name     = $edit_data ? esc_attr( $edit_data->package_name ) : '15 Days Premium Economy Umrah Group';
    $val_type     = $edit_data ? esc_attr( $edit_data->package_type ) : 'Umrah';
    $val_days     = $edit_data ? intval( $edit_data->total_days ?? 15 ) : 15;
    $val_cost     = $edit_data ? floatval( $edit_data->cost_bdt ) : 135000;
    $val_sell     = $edit_data ? floatval( $edit_data->selling_price ?? $edit_data->cost_bdt ) : 155000;
    $val_sar      = $edit_data ? floatval( $edit_data->cost_sar ?? 0 ) : 4200;
    $val_capacity = $edit_data ? intval( $edit_data->capacity ?? 45 ) : 45;
    $val_makkah   = $edit_data ? esc_attr( $edit_data->hotel_makkah ?? '' ) : 'Swissotel Makkah (Clock Tower)';
    $val_m_dist   = $edit_data ? esc_attr( $edit_data->makkah_distance ?? '' ) : '0 Meters (Haram Boundary)';
    $val_madinah  = $edit_data ? esc_attr( $edit_data->hotel_madinah ?? '' ) : 'Anwar Al Madinah Movenpick';
    $val_md_dist  = $edit_data ? esc_attr( $edit_data->madinah_distance ?? '' ) : '150 Meters (Markazia)';
    $val_airline  = $edit_data ? esc_attr( $edit_data->airline_name ?? '' ) : 'Biman Bangladesh Airlines (Direct DAC-JED-DAC)';
    $val_incl     = $edit_data ? esc_textarea( $edit_data->inclusions_json ?? '' ) : "✔ Umrah Tourist E-Visa with Full Medical Insurance\n✔ Direct Return Air Ticket (DAC-JED-MED-DAC with 2PC 23KG Baggage)\n✔ 7 Nights Hotel Stay in Makkah (Near Haram Boundary)\n✔ 7 Nights Hotel Stay in Madinah (Central Markazia Area)\n✔ Full AC Luxury Bus Ground Transport (Jeddah-Makkah-Madinah-Airport)\n✔ Historical Ziyarah in Makkah & Madinah with Experienced Moallem\n✔ Complimentary 5 Liters Zamzam Water & Travel Kit Bag";

    // Summary Analytics
    $total_packages = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_packages" );
    $hajj_count     = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_packages WHERE package_type = 'Hajj'" );
    $umrah_count    = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_packages WHERE package_type LIKE '%Umrah%'" );
    $avg_price      = (float) $wpdb->get_var( "SELECT AVG(selling_price) FROM $table_packages" );

    $all_packages = $wpdb->get_results( "SELECT * FROM $table_packages ORDER BY id DESC" );
    ?>

    <div class="ifs-pkg-workspace">
        <?php echo $message; ?>

        <!-- Metric Counter Ribbon -->
        <div class="ifs-list-metric-ribbon">
            <div class="ifs-metric-chip">
                <div class="chip-icon bg-emerald"><span class="dashicons dashicons-palmtree"></span></div>
                <div>
                    <span class="chip-label">Configured Plans</span>
                    <strong class="chip-val"><?php echo number_format( $total_packages ); ?> Packages</strong>
                </div>
            </div>
            <div class="ifs-metric-chip">
                <div class="chip-icon bg-amber"><span class="dashicons dashicons-star-filled"></span></div>
                <div>
                    <span class="chip-label">Hajj Packages</span>
                    <strong class="chip-val color-amber"><?php echo number_format( $hajj_count ); ?> Active</strong>
                </div>
            </div>
            <div class="ifs-metric-chip">
                <div class="chip-icon bg-blue"><span class="dashicons dashicons-admin-site-alt3"></span></div>
                <div>
                    <span class="chip-label">Umrah Groups</span>
                    <strong class="chip-val color-blue"><?php echo number_format( $umrah_count ); ?> Active</strong>
                </div>
            </div>
            <div class="ifs-metric-chip">
                <div class="chip-icon bg-indigo"><span class="dashicons dashicons-money-alt"></span></div>
                <div>
                    <span class="chip-label">Avg. Selling Price</span>
                    <strong class="chip-val color-emerald">৳<?php echo number_format( $avg_price, 0 ); ?></strong>
                </div>
            </div>
        </div>

        <!-- Split Screen: Package Form & Live Brochure Card Preview -->
        <form method="post" action="<?php echo esc_url( $base_url ); ?>" class="ifs-split-pkg-editor" id="ifsPkgForm">
            <?php wp_nonce_field( 'ifs_hajj_pkg_nonce_action', 'ifs_hajj_pkg_nonce' ); ?>
            
            <?php if ( $edit_data ) : ?>
                <input type="hidden" name="edit_pkg_id" value="<?php echo esc_attr( $edit_data->id ); ?>">
            <?php endif; ?>

            <div class="ifs-pkg-form-body">
                
                <!-- Section 1: Package Identity, Type & Capacity -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">01</div>
                        <div>
                            <h3 class="ifs-card-title"><?php echo $edit_data ? 'Update Package: ' . esc_html( $edit_data->package_name ) : 'Configure Package Identity & Classification'; ?></h3>
                            <p class="ifs-card-desc">Define package title, spiritual category, total tour days, and group passenger capacity</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <!-- Package Name -->
                        <div class="ifs-field-block col-span-2">
                            <label class="ifs-field-label" for="inp_package_name">Package Plan Title <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-palmtree field-icon"></span>
                                <input type="text" name="package_name" id="inp_package_name" required 
                                       value="<?php echo $val_name; ?>" 
                                       placeholder="e.g. 15 Days Economy Umrah Group Package" class="ifs-input-field">
                            </div>
                        </div>

                        <!-- Package Type -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_package_type">Package Category <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-tag field-icon"></span>
                                <select name="package_type" id="inp_package_type" class="ifs-input-field">
                                    <option value="Umrah" <?php selected( $val_type, 'Umrah' ); ?>>Standard Umrah</option>
                                    <option value="Hajj" <?php selected( $val_type, 'Hajj' ); ?>>Holy Hajj Package</option>
                                    <option value="Ramadan Umrah" <?php selected( $val_type, 'Ramadan Umrah' ); ?>>Ramadan Special Umrah</option>
                                    <option value="VIP Custom Umrah" <?php selected( $val_type, 'VIP Custom Umrah' ); ?>>VIP Custom Umrah</option>
                                </select>
                            </div>
                        </div>

                        <!-- Tour Duration -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_total_days">Total Tour Duration (Days)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-clock field-icon"></span>
                                <input type="number" name="total_days" id="inp_total_days" 
                                       value="<?php echo $val_days; ?>" placeholder="15" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <!-- Group Capacity -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_capacity">Group Seat Capacity</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-groups field-icon"></span>
                                <input type="number" name="capacity" id="inp_capacity" 
                                       value="<?php echo $val_capacity; ?>" placeholder="40" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <!-- Operating Carrier -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_airline">Flight Carrier Route</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-airplane field-icon"></span>
                                <input type="text" name="airline_name" id="inp_airline" 
                                       value="<?php echo $val_airline; ?>" placeholder="e.g. Saudia Direct" class="ifs-input-field">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Hotel Accommodations in Makkah & Madinah -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">02</div>
                        <div>
                            <h3 class="ifs-card-title">Holy Cities Hotel Accommodations & Proximity</h3>
                            <p class="ifs-card-desc">Hotel properties in Makkah and Madinah with precise walking distance to Haram boundaries</p>
                        </div>
                    </div>

                    <div class="ifs-grid-2">
                        <!-- Makkah Hotel -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_hotel_makkah">Makkah Hotel Property</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-building field-icon"></span>
                                <input type="text" name="hotel_makkah" id="inp_hotel_makkah" 
                                       value="<?php echo $val_makkah; ?>" placeholder="e.g. Swissotel Makkah (Clock Tower)" class="ifs-input-field">
                            </div>
                        </div>

                        <!-- Makkah Distance -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_makkah_distance">Makkah Haram Distance / Walking</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-location field-icon"></span>
                                <input type="text" name="makkah_distance" id="inp_makkah_distance" 
                                       value="<?php echo $val_m_dist; ?>" placeholder="e.g. 0 Meters (Haram Boundary)" class="ifs-input-field">
                            </div>
                        </div>

                        <!-- Madinah Hotel -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_hotel_madinah">Madinah Hotel Property</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-building field-icon"></span>
                                <input type="text" name="hotel_madinah" id="inp_hotel_madinah" 
                                       value="<?php echo $val_madinah; ?>" placeholder="e.g. Anwar Al Madinah Movenpick" class="ifs-input-field">
                            </div>
                        </div>

                        <!-- Madinah Distance -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_madinah_distance">Madinah Nabawi Distance / Walking</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-location field-icon"></span>
                                <input type="text" name="madinah_distance" id="inp_madinah_distance" 
                                       value="<?php echo $val_md_dist; ?>" placeholder="e.g. 150 Meters (Markazia North)" class="ifs-input-field">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Multi-Currency Pricing & Comprehensive Inclusions -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">03</div>
                        <div>
                            <h3 class="ifs-card-title">Commercial Rates, Saudi SAR Conversion & Inclusions</h3>
                            <p class="ifs-card-desc">Ground cost in SAR, selling price in BDT, and complete package inclusion checklist</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <!-- Cost BDT -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_cost_bdt">Agency Base Cost (৳ BDT) <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-cart field-icon"></span>
                                <input type="number" step="0.01" name="cost_bdt" id="inp_cost_bdt" required 
                                       value="<?php echo $val_cost; ?>" placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <!-- Selling Price BDT -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_selling_price">Pilgrim Selling Price (৳ BDT) <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-money-alt field-icon"></span>
                                <input type="number" step="0.01" name="selling_price" id="inp_selling_price" required 
                                       value="<?php echo $val_sell; ?>" placeholder="0.00" class="ifs-input-field font-mono font-bold color-emerald">
                            </div>
                        </div>

                        <!-- Cost SAR -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_cost_sar">Saudi Ground Cost (﷼ SAR)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-bank field-icon"></span>
                                <input type="number" step="0.01" name="cost_sar" id="inp_cost_sar" 
                                       value="<?php echo $val_sar; ?>" placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <!-- Inclusions & Feature List -->
                        <div class="ifs-field-block col-span-3">
                            <label class="ifs-field-label" for="inp_inclusions">Package Inclusions & Features (One per line) <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-clipboard field-icon textarea-icon"></span>
                                <textarea name="inclusions" id="inp_inclusions" required rows="6" 
                                          class="ifs-input-field has-textarea-icon" 
                                          placeholder="✔ Visa & Insurance&#10;✔ Air Ticket&#10;✔ Makkah & Madinah Hotel..."><?php echo $val_incl; ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Toolbar -->
                <div class="ifs-action-strip">
                    <?php if ( $edit_data ) : ?>
                        <a href="<?php echo esc_url( $base_url ); ?>" class="ifs-btn-back">
                            <span class="dashicons dashicons-arrow-left-alt"></span> Cancel Edit
                        </a>
                    <?php else : ?>
                        <span class="ifs-submeta-hint"><span class="dashicons dashicons-info"></span> Configured packages appear instantly in Pilgrim Registration desks</span>
                    <?php endif; ?>
                    <button type="submit" name="ifs_hajj_pkg_submit" class="ifs-btn-primary">
                        <span class="dashicons dashicons-saved"></span> 
                        <?php echo $edit_data ? 'Update Package Plan' : 'Save & Publish Package'; ?>
                    </button>
                </div>
            </div>

            <!-- Right Sidebar: Live Package Brochure Card Preview -->
            <div class="ifs-preview-sidebar">
                <div class="ifs-preview-sticky">
                    <div class="ifs-card-preview-header">
                        <span class="dashicons dashicons-visibility"></span> Live Package Brochure Preview
                    </div>

                    <!-- Modern Package Brochure Card Widget -->
                    <div class="ifs-brochure-card">
                        <div class="brochure-head-strip">
                            <span class="brochure-badge-tag" id="prev_type">UMRAH PACKAGE</span>
                            <span class="brochure-duration-tag" id="prev_days">15 DAYS / 14 NIGHTS</span>
                        </div>

                        <div class="brochure-hero">
                            <h3 class="brochure-title" id="prev_title">15 DAYS PREMIUM ECONOMY UMRAH GROUP</h3>
                            <span class="brochure-airline font-mono" id="prev_airline_disp"><span class="dashicons dashicons-airplane"></span> BIMAN / SAUDIA DIRECT</span>
                        </div>

                        <div class="brochure-pricing-hero font-mono">
                            <span class="price-lbl">STARTING PRICE PER PILGRIM</span>
                            <h2 class="price-val" id="prev_price">৳155,000</h2>
                            <span class="price-sar" id="prev_sar">(Approx. ﷼4,200 SAR)</span>
                        </div>

                        <div class="brochure-hotel-matrix">
                            <div class="hotel-box">
                                <span class="hotel-city">MAKKAH AL-MUKARRAMAH</span>
                                <strong class="hotel-name" id="prev_makkah">Swissotel Makkah</strong>
                                <span class="hotel-dist font-mono" id="prev_mdist">0 Meters (Haram Boundary)</span>
                            </div>
                            <div class="hotel-box">
                                <span class="hotel-city">AL-MADINAH AL-MUNAWWARAH</span>
                                <strong class="hotel-name" id="prev_madinah">Anwar Al Madinah Movenpick</strong>
                                <span class="hotel-dist font-mono" id="prev_mddist">150 Meters (Markazia)</span>
                            </div>
                        </div>

                        <div class="brochure-inclusions-box">
                            <span class="inclusions-head"><span class="dashicons dashicons-yes-alt"></span> KEY PACKAGE INCLUSIONS:</span>
                            <div class="inclusions-content" id="prev_inclusions">
                                ✔ Umrah Visa with Medical Insurance<br>
                                ✔ Return Flight Ticket<br>
                                ✔ Makkah & Madinah Hotels
                            </div>
                        </div>

                        <div class="brochure-footer-strip">
                            <span class="dashicons dashicons-shield"></span> Approved by Ministry of Hajj & Umrah, KSA
                        </div>
                    </div>

                    <!-- Fast Presets Widget -->
                    <div class="ifs-presets-box">
                        <span class="presets-title"><span class="dashicons dashicons-superhero"></span> Quick Package Templates:</span>
                        <div class="presets-btn-group">
                            <button type="button" class="preset-pill" onclick="applyPkgPreset('15 Days Economy Umrah Group', 'Umrah', 15, 125000, 145000, 3800, 'Al Kiswah Towers Makkah (800m)', '800m Shuttle Bus', 'Emaar Taiba Madinah (300m)', '300m Markazia')">15D Economy Umrah</button>
                            <button type="button" class="preset-pill" onclick="applyPkgPreset('14 Days 5-Star VIP Umrah', 'VIP Custom Umrah', 14, 210000, 245000, 6800, 'Fairmont Makkah Clock Royal Tower', '0 Meters Haram', 'Dar Al Taqwa Madinah', '50 Meters Nabawi')">14D 5-Star VIP</button>
                            <button type="button" class="preset-pill" onclick="applyPkgPreset('40 Days Executive Shifting Hajj', 'Hajj', 40, 680000, 780000, 21000, 'Azizia Standard Building / Makkah Hotel', 'Shifting Plan', 'Madinah Markazia Hotel', '200m Central')">40D Holy Hajj Plan</button>
                            <button type="button" class="preset-pill" onclick="applyPkgPreset('Last 15 Days Ramadan Umrah', 'Ramadan Umrah', 15, 195000, 225000, 6200, 'Le Meridien Towers Makkah', 'Haram Bus 24/7', 'Al Aqeeq Madinah', '150m Central')">Ramadan Last 15D</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Packages Directory Table Card -->
        <div class="ifs-table-card" style="margin-top: 30px;">
            <div class="ifs-table-top-bar">
                <div class="ifs-table-title-group">
                    <h3 class="ifs-table-heading"><span class="dashicons dashicons-list-view"></span> Configured Hajj & Umrah Packages Directory</h3>
                    <p class="ifs-table-caption">Standard group package plans, hotel accommodations, pricing structures, and inclusions</p>
                </div>
            </div>

            <div class="ifs-table-responsive-wrapper">
                <table class="ifs-pro-datatable" id="ifsHajjPackagesTable">
                    <thead>
                        <tr>
                            <th style="width: 220px;">Package Plan Title</th>
                            <th>Category</th>
                            <th>Duration</th>
                            <th>Makkah Hotel</th>
                            <th>Madinah Hotel</th>
                            <th style="text-align: right;">Cost (৳)</th>
                            <th style="text-align: right;">Selling Price (৳)</th>
                            <th style="text-align: right; width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $all_packages ) : foreach ( $all_packages as $pkg ) : 
                            $type_badge = 'tier-umrah';
                            if ( $pkg->package_type === 'Hajj' ) $type_badge = 'tier-hajj';
                            elseif ( strpos( $pkg->package_type, 'Ramadan' ) !== false ) $type_badge = 'tier-ramadan';
                        ?>
                            <tr>
                                <td>
                                    <div class="ifs-pkg-name-cell">
                                        <strong><?php echo esc_html( $pkg->package_name ); ?></strong>
                                        <span class="pkg-airline-sub"><span class="dashicons dashicons-airplane"></span> <?php echo esc_html( $pkg->airline_name ?: 'Biman / Saudia' ); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="ifs-pkg-pill <?php echo esc_attr( $type_badge ); ?>"><?php echo esc_html( $pkg->package_type ); ?></span>
                                </td>
                                <td>
                                    <span class="ifs-time-pill"><span class="dashicons dashicons-clock"></span> <?php echo esc_html( $pkg->total_days ?? 15 ); ?> Days</span>
                                </td>
                                <td>
                                    <div class="ifs-hotel-meta-cell">
                                        <strong><?php echo esc_html( $pkg->hotel_makkah ?: '-' ); ?></strong>
                                        <span class="dist-tag"><?php echo esc_html( $pkg->makkah_distance ?: 'Walking distance' ); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="ifs-hotel-meta-cell">
                                        <strong><?php echo esc_html( $pkg->hotel_madinah ?: '-' ); ?></strong>
                                        <span class="dist-tag"><?php echo esc_html( $pkg->madinah_distance ?: 'Central Markazia' ); ?></span>
                                    </div>
                                </td>
                                <td style="text-align: right; color: #64748b; font-family: ui-monospace, monospace; font-size: 13px;">
                                    ৳<?php echo number_format( $pkg->cost_bdt, 2 ); ?>
                                </td>
                                <td style="text-align: right; font-weight: 800; color: #059669; font-family: ui-monospace, monospace; font-size: 14px;">
                                    ৳<?php echo number_format( $pkg->selling_price ?? $pkg->cost_bdt, 2 ); ?>
                                </td>
                                <td style="text-align: right;">
                                    <div class="ifs-action-buttons">
                                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=hajj_umrah&sub=packages&action_sub=edit&pkg_id=' . $pkg->id ) ); ?>" 
                                           class="ifs-btn-action edit" title="Edit Package Plan">
                                            <span class="dashicons dashicons-edit"></span>
                                        </a>
                                        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=hajj_umrah&sub=packages&action_sub=delete&pkg_id=' . $pkg->id ), 'delete_pkg_' . $pkg->id ) ); ?>" 
                                           class="ifs-btn-action delete" 
                                           onclick="return confirm('Are you sure you want to delete this package plan?');" title="Delete Plan">
                                            <span class="dashicons dashicons-trash"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr>
                                <td colspan="8" class="ifs-empty-table">
                                    <div class="ifs-empty-state">
                                        <span class="dashicons dashicons-palmtree"></span>
                                        <h4>No Hajj or Umrah Packages Configured Yet</h4>
                                        <p>Use the form above to add your first fixed group package itinerary.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Ultra High-End Stylesheet -->
    <style>
        .ifs-pkg-workspace { max-width: 1440px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; }
        
        .ifs-toast { padding: 14px 20px; border-radius: 10px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 10px; margin-bottom: 22px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .ifs-toast.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .ifs-toast.danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .ifs-toast .dashicons { font-size: 18px; width: 18px; height: 18px; }

        /* Metric Ribbon */
        .ifs-list-metric-ribbon { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px; margin-bottom: 24px; }
        .ifs-metric-chip { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 8px -2px rgba(15, 23, 42, 0.03); }
        .chip-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #ffffff; flex-shrink: 0; }
        .chip-icon.bg-emerald{ background: linear-gradient(135deg, #047857 0%, #059669 100%); }
        .chip-icon.bg-amber  { background: linear-gradient(135deg, #d97706 0%, #b45309 100%); }
        .chip-icon.bg-blue   { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); }
        .chip-icon.bg-indigo { background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); }
        .chip-icon .dashicons { font-size: 22px; width: 22px; height: 22px; }

        .chip-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; display: block; margin-bottom: 2px; }
        .chip-val { font-size: 19px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
        .color-blue { color: #003376 !important; }
        .color-emerald { color: #059669 !important; }
        .color-amber { color: #d97706 !important; }

        /* Split Editor Layout */
        .ifs-split-pkg-editor { display: grid; grid-template-columns: 1fr 390px; gap: 28px; align-items: flex-start; }
        @media (max-width: 1140px) { .ifs-split-pkg-editor { grid-template-columns: 1fr; } }

        .ifs-panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 26px; margin-bottom: 22px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); }
        .ifs-card-header { display: flex; align-items: center; gap: 14px; margin-bottom: 22px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9; }
        .ifs-step-num { width: 34px; height: 34px; border-radius: 9px; background: linear-gradient(135deg, #047857 0%, #059669 100%); color: #ffffff; font-weight: 800; font-size: 13px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(5, 150, 105, 0.25); flex-shrink: 0; }
        .ifs-card-title { margin: 0; font-size: 15.5px; font-weight: 800; color: #0f172a; }
        .ifs-card-desc { margin: 2px 0 0 0; font-size: 12.5px; color: #64748b; }

        .ifs-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px 18px; }
        .ifs-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px 18px; }
        .col-span-2 { grid-column: span 2; }
        .col-span-3 { grid-column: span 3; }
        @media (max-width: 768px) { .ifs-grid-3, .ifs-grid-2 { grid-template-columns: 1fr; } .col-span-2, .col-span-3 { grid-column: span 1; } }

        .ifs-field-block { display: flex; flex-direction: column; gap: 5px; }
        .ifs-field-label { font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.4px; }
        .ifs-field-label .req { color: #e11d48; }

        .ifs-field-wrap { position: relative; display: flex; align-items: center; width: 100%; }
        .ifs-field-wrap .field-icon { position: absolute; left: 12px; color: #94a3b8; font-size: 17px; width: 17px; height: 17px; pointer-events: none; z-index: 2; transition: color 0.2s ease; }
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
        textarea.ifs-input-field.has-textarea-icon { padding: 10px 12px 10px 38px !important; font-family: inherit; line-height: 1.5; }
        .ifs-field-wrap .ifs-input-field:focus { border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12); }
        .ifs-field-wrap:focus-within .field-icon { color: #059669; }

        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .font-bold { font-weight: 700; }

        /* Action Toolbar */
        .ifs-action-strip { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; }
        .ifs-btn-back { color: #64748b; text-decoration: none; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .ifs-btn-back:hover { color: #0f172a; }
        .ifs-submeta-hint { font-size: 12px; color: #64748b; display: inline-flex; align-items: center; gap: 4px; }
        .ifs-submeta-hint .dashicons { font-size: 14px; width: 14px; height: 14px; color: #059669; }
        .ifs-btn-primary {
            background: linear-gradient(135deg, #047857 0%, #059669 100%);
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
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25);
            transition: all 0.2s ease;
        }
        .ifs-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(5, 150, 105, 0.35); }

        /* Right Preview Sidebar */
        .ifs-preview-sticky { position: sticky; top: 30px; }
        .ifs-card-preview-header { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }

        .ifs-brochure-card {
            background: linear-gradient(145deg, #064e3b 0%, #047857 60%, #059669 100%);
            border-radius: 16px;
            padding: 22px;
            color: #ffffff;
            box-shadow: 0 16px 36px -6px rgba(4, 120, 87, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.15);
            position: relative;
            overflow: hidden;
            margin-bottom: 18px;
        }
        .brochure-head-strip { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px dashed rgba(255, 255, 255, 0.2); }
        .brochure-badge-tag { font-size: 10px; font-weight: 800; letter-spacing: 0.8px; color: #a7f3d0; text-transform: uppercase; }
        .brochure-duration-tag { background: rgba(255, 255, 255, 0.18); backdrop-filter: blur(4px); padding: 2px 7px; border-radius: 4px; font-size: 9.5px; font-weight: 800; text-transform: uppercase; }

        .brochure-hero { margin-bottom: 14px; }
        .brochure-title { margin: 0 0 4px 0; font-size: 15px; font-weight: 900; color: #ffffff; letter-spacing: -0.2px; text-transform: uppercase; }
        .brochure-airline { font-size: 10.5px; color: #a7f3d0; display: inline-flex; align-items: center; gap: 4px; }
        .brochure-airline .dashicons { font-size: 13px; width: 13px; height: 13px; color: #38bdf8; }

        .brochure-pricing-hero { background: rgba(0, 0, 0, 0.22); border-radius: 10px; padding: 12px 14px; margin-bottom: 14px; text-align: center; }
        .price-lbl { font-size: 8.5px; font-weight: 700; color: #a7f3d0; letter-spacing: 0.5px; display: block; margin-bottom: 2px; }
        .price-val { margin: 0; font-size: 24px; font-weight: 900; color: #86efac; }
        .price-sar { font-size: 10px; color: #cbd5e1; margin-top: 2px; display: block; }

        .brochure-hotel-matrix { display: flex; flex-direction: column; gap: 8px; margin-bottom: 12px; }
        .hotel-box { background: rgba(255, 255, 255, 0.08); border-radius: 8px; padding: 8px 10px; display: flex; flex-direction: column; gap: 1px; }
        .hotel-city { font-size: 8px; font-weight: 700; color: #6ee7b7; letter-spacing: 0.5px; }
        .hotel-name { font-size: 12px; font-weight: 800; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .hotel-dist { font-size: 10px; color: #cbd5e1; }

        .brochure-inclusions-box { background: rgba(0, 0, 0, 0.15); border-radius: 8px; padding: 10px 12px; margin-bottom: 12px; }
        .inclusions-head { font-size: 9px; font-weight: 800; color: #38bdf8; display: flex; align-items: center; gap: 4px; margin-bottom: 4px; letter-spacing: 0.3px; }
        .inclusions-head .dashicons { font-size: 12px; width: 12px; height: 12px; }
        .inclusions-content { font-size: 10.5px; color: #d1fae5; line-height: 1.5; max-height: 90px; overflow-y: auto; }

        .brochure-footer-strip { font-size: 8.5px; color: #a7f3d0; display: flex; align-items: center; gap: 4px; padding-top: 6px; border-top: 1px solid rgba(255, 255, 255, 0.1); }
        .brochure-footer-strip .dashicons { font-size: 12px; width: 12px; height: 12px; color: #38bdf8; }

        /* Presets Box */
        .ifs-presets-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02); }
        .presets-title { font-size: 11.5px; font-weight: 800; color: #047857; text-transform: uppercase; letter-spacing: 0.4px; display: flex; align-items: center; gap: 6px; margin-bottom: 10px; }
        .presets-title .dashicons { font-size: 15px; width: 15px; height: 15px; }
        .presets-btn-group { display: flex; flex-wrap: wrap; gap: 6px; }
        .preset-pill { background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 9px; font-size: 11px; font-weight: 700; color: #334155; cursor: pointer; transition: all 0.15s ease; }
        .preset-pill:hover { background: #047857; color: #ffffff; border-color: #047857; }

        /* Table Card */
        .ifs-table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); overflow: hidden; }
        .ifs-table-top-bar { padding: 22px 26px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; gap: 15px; }
        .ifs-table-heading { margin: 0; font-size: 17px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        .ifs-table-heading .dashicons { color: #047857; font-size: 20px; width: 20px; height: 20px; }
        .ifs-table-caption { margin: 3px 0 0 0; font-size: 13px; color: #64748b; }

        .ifs-table-responsive-wrapper { padding: 15px 24px 24px 24px; overflow-x: auto; }
        .ifs-pro-datatable { width: 100%; border-collapse: collapse; font-size: 13px; }
        .ifs-pro-datatable thead th { background: #f8fafc; color: #475569; font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 12px 14px; border-bottom: 2px solid #e2e8f0; text-align: left; }
        .ifs-pro-datatable tbody td { padding: 13px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #334155; }

        .ifs-pkg-name-cell { display: flex; flex-direction: column; gap: 2px; }
        .ifs-pkg-name-cell strong { font-size: 13.5px; color: #0f172a; }
        .pkg-airline-sub { font-size: 11px; color: #64748b; display: inline-flex; align-items: center; gap: 3px; }
        .pkg-airline-sub .dashicons { font-size: 12px; width: 12px; height: 12px; color: #0284c7; }

        .ifs-pkg-pill { font-size: 10.5px; font-weight: 700; padding: 2px 7px; border-radius: 4px; display: inline-block; text-transform: uppercase; }
        .tier-umrah { background: #dcfce7; color: #15803d; }
        .tier-hajj  { background: #fef3c7; color: #b45309; }
        .tier-ramadan { background: #e0f2fe; color: #0369a1; }

        .ifs-time-pill { font-size: 11.5px; color: #475569; display: inline-flex; align-items: center; gap: 4px; font-weight: 600; }
        .ifs-time-pill .dashicons { font-size: 13px; width: 13px; height: 13px; color: #94a3b8; }

        .ifs-hotel-meta-cell { display: flex; flex-direction: column; gap: 1px; }
        .ifs-hotel-meta-cell strong { font-size: 12.5px; color: #0f172a; }
        .dist-tag { font-size: 10.5px; color: #64748b; }

        .ifs-action-buttons { display: flex; gap: 5px; justify-content: flex-end; }
        .ifs-btn-action { width: 30px; height: 30px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.15s ease; }
        .ifs-btn-action.edit   { background: #eff6ff; color: #2563eb; }
        .ifs-btn-action.edit:hover { background: #dbeafe; color: #1d4ed8; }
        .ifs-btn-action.delete { background: #fef2f2; color: #dc2626; }
        .ifs-btn-action.delete:hover { background: #fee2e2; color: #b91c1c; }
        .ifs-btn-action .dashicons { font-size: 14px; width: 14px; height: 14px; }

        .ifs-empty-table { text-align: center; padding: 50px 20px !important; }
        .ifs-empty-state .dashicons { font-size: 44px; width: 44px; height: 44px; color: #cbd5e1; margin-bottom: 10px; }
    </style>

    <!-- Real-Time Interactive Script & Presets Engine -->
    <script>
    function applyPkgPreset(name, type, days, cost, sell, sar, makkah, m_dist, madinah, md_dist) {
        document.getElementById('inp_package_name').value   = name;
        document.getElementById('inp_package_type').value   = type;
        document.getElementById('inp_total_days').value     = days;
        document.getElementById('inp_cost_bdt').value       = cost;
        document.getElementById('inp_selling_price').value  = sell;
        document.getElementById('inp_cost_sar').value       = sar;
        document.getElementById('inp_hotel_makkah').value   = makkah;
        document.getElementById('inp_makkah_distance').value= m_dist;
        document.getElementById('inp_hotel_madinah').value  = madinah;
        document.getElementById('inp_madinah_distance').value= md_dist;
        
        const event = new Event('input', { bubbles: true });
        document.getElementById('inp_package_name').dispatchEvent(event);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const inpName     = document.getElementById('inp_package_name');
        const inpType     = document.getElementById('inp_package_type');
        const inpDays     = document.getElementById('inp_total_days');
        const inpSell     = document.getElementById('inp_selling_price');
        const inpSar      = document.getElementById('inp_cost_sar');
        const inpAirline  = document.getElementById('inp_airline');
        const inpMakkah   = document.getElementById('inp_hotel_makkah');
        const inpMdist    = document.getElementById('inp_makkah_distance');
        const inpMadinah  = document.getElementById('inp_hotel_madinah');
        const inpMddist   = document.getElementById('inp_madinah_distance');
        const inpIncl     = document.getElementById('inp_inclusions');

        const prevTitle   = document.getElementById('prev_title');
        const prevType    = document.getElementById('prev_type');
        const prevDays    = document.getElementById('prev_days');
        const prevPrice   = document.getElementById('prev_price');
        const prevSar     = document.getElementById('prev_sar');
        const prevAirline = document.getElementById('prev_airline_disp');
        const prevMakkah  = document.getElementById('prev_makkah');
        const prevMdist   = document.getElementById('prev_mdist');
        const prevMadinah = document.getElementById('prev_madinah');
        const prevMddist  = document.getElementById('prev_mddist');
        const prevIncl    = document.getElementById('prev_inclusions');

        function updateBrochureCard() {
            if (prevTitle) prevTitle.textContent     = (inpName && inpName.value.trim()) ? inpName.value.trim().toUpperCase() : 'PACKAGE PLAN TITLE';
            if (prevType) prevType.textContent       = (inpType && inpType.value.trim()) ? inpType.value.trim().toUpperCase() + ' PACKAGE' : 'UMRAH PACKAGE';
            if (prevDays) prevDays.textContent       = (inpDays && inpDays.value) ? inpDays.value + ' DAYS / ' + (parseInt(inpDays.value)-1) + ' NIGHTS' : '15 DAYS';
            
            const sellVal = parseFloat(inpSell ? inpSell.value : 0) || 0;
            if (prevPrice) prevPrice.textContent     = '৳' + sellVal.toLocaleString('en-US', { minimumFractionDigits: 0 });
            
            const sarVal = parseFloat(inpSar ? inpSar.value : 0) || 0;
            if (prevSar) prevSar.textContent         = sarVal > 0 ? '(Approx. ﷼' + sarVal.toLocaleString('en-US') + ' SAR)' : '';

            if (prevAirline) prevAirline.innerHTML   = '<span class="dashicons dashicons-airplane"></span> ' + ((inpAirline && inpAirline.value.trim()) ? inpAirline.value.trim().toUpperCase() : 'BIMAN / SAUDIA DIRECT');
            if (prevMakkah) prevMakkah.textContent   = (inpMakkah && inpMakkah.value.trim()) ? inpMakkah.value.trim() : 'Makkah Hotel';
            if (prevMdist) prevMdist.textContent     = (inpMdist && inpMdist.value.trim()) ? inpMdist.value.trim() : 'Near Haram';
            if (prevMadinah) prevMadinah.textContent = (inpMadinah && inpMadinah.value.trim()) ? inpMadinah.value.trim() : 'Madinah Hotel';
            if (prevMddist) prevMddist.textContent   = (inpMddist && inpMddist.value.trim()) ? inpMddist.value.trim() : 'Central Markazia';

            if (prevIncl && inpIncl) {
                const lines = inpIncl.value.split('\n').filter(l => l.trim() !== '');
                if (lines.length > 0) {
                    prevIncl.innerHTML = lines.slice(0, 4).join('<br>') + (lines.length > 4 ? '<br><em>+ ' + (lines.length - 4) + ' more inclusions...</em>' : '');
                } else {
                    prevIncl.textContent = 'No inclusions specified yet.';
                }
            }
        }

        [inpName, inpType, inpDays, inpSell, inpSar, inpAirline, inpMakkah, inpMdist, inpMadinah, inpMddist, inpIncl].forEach(el => {
            if (el) {
                el.addEventListener('input', updateBrochureCard);
                el.addEventListener('change', updateBrochureCard);
            }
        });

        updateBrochureCard();

        if (window.jQuery && jQuery.fn.DataTable) {
            jQuery('#ifsHajjPackagesTable').DataTable({
                "pageLength": 10,
                "order": [[ 0, "desc" ]],
                "language": {
                    "search": "_INPUT_",
                    "searchPlaceholder": "Search Package, City, Price...",
                    "lengthMenu": "Show _MENU_ entries",
                    "paginate": { "previous": "&larr; Prev", "next": "Next &rarr;" }
                }
            });
        }
    });
    </script>
    <?php
}