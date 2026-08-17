<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Enterprise Ultra-Modern Segmented Sub-Navigation for Tour Packages Module
 */
function ifs_terp_tours_render_tabs( $active_tab = 'list' ) {
    global $wpdb;
    $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=tours' );

    $table_tours     = $wpdb->prefix . 'iterp_tours';
    $table_plans     = $wpdb->prefix . 'iterp_tour_packages';
    
    $total_bookings  = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_tours" );
    $total_plans     = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_plans" );
    $total_profit    = (float) $wpdb->get_var( "SELECT SUM(profit) FROM $table_tours WHERE status != 'Cancelled'" );
    ?>
    <div class="ifs-pro-tab-wrapper">
        <div class="ifs-pro-header-card">
            <div class="ifs-pro-identity">
                <div class="ifs-pro-icon-glow">
                    <span class="dashicons dashicons-palmtree"></span>
                </div>
                <div class="ifs-pro-title-meta">
                    <div class="ifs-pro-badge-group">
                        <span class="ifs-status-dot"></span>
                        <span class="ifs-meta-tag">Holiday Desk</span>
                        <span class="ifs-meta-tag-emerald">Tour Operations</span>
                    </div>
                    <h2 class="ifs-pro-heading">Holiday & Tour Packages Desk</h2>
                    <p class="ifs-pro-caption">Manage fixed holiday package plans, customized passenger bookings, and tour margins</p>
                </div>
            </div>
            
            <div class="ifs-pro-stats-strip">
                <div class="ifs-stat-pill">
                    <span class="ifs-stat-lbl">Tour Bookings</span>
                    <span class="ifs-stat-num color-dark"><?php echo number_format( $total_bookings ); ?></span>
                </div>
                <div class="ifs-stat-pill">
                    <span class="ifs-stat-lbl">Package Plans</span>
                    <span class="ifs-stat-num color-blue"><?php echo number_format( $total_plans ); ?></span>
                </div>
                <div class="ifs-stat-pill">
                    <span class="ifs-stat-lbl">Gross Margin</span>
                    <span class="ifs-stat-num color-emerald">৳<?php echo number_format( $total_profit, 2 ); ?></span>
                </div>
            </div>
        </div>

        <div class="ifs-pro-nav-container">
            <nav class="ifs-pro-nav-bar">
                <a href="<?php echo esc_url( $base_url . '&sub=list' ); ?>" 
                   class="ifs-pro-nav-btn <?php echo ( $active_tab === 'list' ) ? 'active-tab' : ''; ?>">
                    <span class="dashicons dashicons-list-view"></span>
                    <span class="ifs-btn-label">All Tour Bookings</span>
                    <span class="ifs-pro-counter"><?php echo $total_bookings; ?></span>
                </a>

                <a href="<?php echo esc_url( $base_url . '&sub=plans' ); ?>" 
                   class="ifs-pro-nav-btn <?php echo ( $active_tab === 'plans' ) ? 'active-tab' : ''; ?>">
                    <span class="dashicons dashicons-portfolio"></span>
                    <span class="ifs-btn-label">Holiday Package Plans</span>
                    <span class="ifs-pro-counter"><?php echo $total_plans; ?></span>
                </a>

                <a href="<?php echo esc_url( $base_url . '&sub=add' ); ?>" 
                   class="ifs-pro-nav-btn <?php echo ( $active_tab === 'add' ) ? 'active-tab' : ''; ?>">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <span class="ifs-btn-label">Book Holiday Package</span>
                </a>
            </nav>
        </div>
    </div>

    <style>
        .ifs-pro-tab-wrapper { margin-bottom: 30px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .ifs-pro-header-card { background: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; padding: 24px 28px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04); margin-bottom: 18px; }
        .ifs-pro-identity { display: flex; align-items: center; gap: 18px; }
        .ifs-pro-icon-glow { width: 52px; height: 52px; border-radius: 14px; background: linear-gradient(135deg, #059669 0%, #047857 100%); display: flex; align-items: center; justify-content: center; color: #ffffff; box-shadow: 0 8px 18px -4px rgba(5, 150, 105, 0.35); flex-shrink: 0; }
        .ifs-pro-icon-glow .dashicons { font-size: 26px; width: 26px; height: 26px; }
        .ifs-pro-badge-group { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
        .ifs-status-dot { width: 6px; height: 6px; border-radius: 50%; background: #10b981; display: inline-block; }
        .ifs-meta-tag { font-size: 10.5px; font-weight: 700; text-transform: uppercase; color: #64748b; }
        .ifs-meta-tag-emerald { font-size: 10px; font-weight: 700; text-transform: uppercase; background: #ecfdf5; color: #047857; padding: 2px 7px; border-radius: 4px; }
        .ifs-pro-heading { margin: 0; font-size: 22px; font-weight: 800; color: #0f172a; }
        .ifs-pro-caption { margin: 3px 0 0 0; font-size: 13.5px; color: #64748b; }
        .ifs-pro-stats-strip { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .ifs-stat-pill { background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 16px; border-radius: 10px; display: flex; flex-direction: column; gap: 2px; min-width: 100px; }
        .ifs-stat-lbl { font-size: 10.5px; font-weight: 600; color: #64748b; text-transform: uppercase; }
        .ifs-stat-num { font-size: 16px; font-weight: 800; }
        .color-dark { color: #0f172a; }
        .color-blue { color: #0284c7; }
        .color-emerald { color: #059669; }
        .ifs-pro-nav-container { display: flex; align-items: center; }
        .ifs-pro-nav-bar { background: #f1f5f9; border: 1px solid #e2e8f0; padding: 5px; border-radius: 12px; display: inline-flex; align-items: center; gap: 6px; max-width: 100%; overflow-x: auto; }
        .ifs-pro-nav-btn { display: inline-flex; align-items: center; gap: 9px; padding: 10px 20px; font-size: 13.5px; font-weight: 600; color: #64748b; text-decoration: none; border-radius: 9px; transition: all 0.2s ease; cursor: pointer; white-space: nowrap; border: 1px solid transparent; }
        .ifs-pro-nav-btn:hover { color: #0f172a; background: rgba(255, 255, 255, 0.65); }
        .ifs-pro-nav-btn.active-tab { background: #ffffff; color: #047857; font-weight: 700; border: 1px solid rgba(4, 120, 87, 0.1); box-shadow: 0 4px 12px rgba(4, 120, 87, 0.08); }
        .ifs-pro-counter { background: #e2e8f0; color: #475569; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 20px; }
        .ifs-pro-nav-btn.active-tab .ifs-pro-counter { background: #047857; color: #ffffff; }
    </style>
    <?php
}

/**
 * Main Controller for Tour Packages Module
 */
function ifs_terp_tours_tab() {
    global $wpdb;
    $table_tours     = $wpdb->prefix . 'iterp_tours';
    $table_plans     = $wpdb->prefix . 'iterp_tour_packages';
    $table_customers = $wpdb->prefix . 'iterp_customers';
    $table_agents    = $wpdb->prefix . 'iterp_agents';
    $table_suppliers = $wpdb->prefix . 'iterp_suppliers';

    $sub_action = isset( $_GET['sub'] ) ? sanitize_text_field( $_GET['sub'] ) : 'list';
    $message    = '';

    /* =========================================================================
       1. HANDLE DELETE ACTIONS (BOOKING OR PACKAGE PLAN)
       ========================================================================= */
    if ( $sub_action === 'delete' && isset( $_GET['id'] ) ) {
        $del_id = intval( $_GET['id'] );
        check_admin_referer( 'delete_tour_' . $del_id );
        
        $wpdb->delete( $table_tours, array( 'id' => $del_id ), array( '%d' ) );
        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            ifs_terp_log_activity( "Deleted Tour Booking #TR-$del_id" );
        }
        wp_safe_redirect( add_query_arg( array( 'page' => 'ifs_travel_erp', 'tab' => 'tours', 'msg' => 'deleted' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    if ( $sub_action === 'delete_plan' && isset( $_GET['plan_id'] ) ) {
        $plan_id = intval( $_GET['plan_id'] );
        check_admin_referer( 'delete_tour_plan_' . $plan_id );

        $wpdb->delete( $table_plans, array( 'id' => $plan_id ), array( '%d' ) );
        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            ifs_terp_log_activity( "Deleted Tour Package Plan #PLN-$plan_id" );
        }
        wp_safe_redirect( add_query_arg( array( 'page' => 'ifs_travel_erp', 'tab' => 'tours', 'sub' => 'plans', 'msg' => 'plan_deleted' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    if ( isset( $_GET['msg'] ) ) {
        if ( $_GET['msg'] === 'deleted' ) {
            $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Tour booking deleted successfully.</div>';
        } elseif ( $_GET['msg'] === 'plan_deleted' ) {
            $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Package plan configuration removed.</div>';
        }
    }

    /* =========================================================================
       2. HANDLE PACKAGE PLAN FORM SUBMIT (ADD / EDIT)
       ========================================================================= */
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_tour_plan_submit'] ) ) {
        check_admin_referer( 'ifs_tour_plan_action', 'ifs_tour_plan_nonce' );

        $edit_plan_id = isset( $_POST['edit_plan_id'] ) ? intval( $_POST['edit_plan_id'] ) : 0;
        $is_plan_edit = ( $edit_plan_id > 0 );

        $plan_data = array(
            'package_name'     => sanitize_text_field( $_POST['package_name'] ?? '' ),
            'destination'      => sanitize_text_field( $_POST['destination'] ?? '' ),
            'total_days'       => intval( $_POST['total_days'] ?? 4 ),
            'total_nights'     => intval( $_POST['total_nights'] ?? 3 ),
            'cost_bdt'         => floatval( $_POST['cost_bdt'] ?? 0 ),
            'selling_price'    => floatval( $_POST['selling_price'] ?? 0 ),
            'hotel_name'       => sanitize_text_field( $_POST['hotel_name'] ?? '' ),
            'inclusions_text'  => sanitize_textarea_field( $_POST['inclusions_text'] ?? '' ),
        );

        if ( $is_plan_edit ) {
            $wpdb->update( $table_plans, $plan_data, array( 'id' => $edit_plan_id ) );
            $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Package Plan updated successfully.</div>';
        } else {
            $plan_data['created_at'] = current_time( 'mysql' );
            $wpdb->insert( $table_plans, $plan_data );
            $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> New Holiday Package Plan configured.</div>';
        }
    }

    /* =========================================================================
       3. HANDLE TOUR BOOKING FORM SUBMIT
       ========================================================================= */
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_tour_submit'] ) ) {
        check_admin_referer( 'ifs_tour_action', 'ifs_tour_nonce' );

        $edit_id       = isset( $_POST['edit_id'] ) ? intval( $_POST['edit_id'] ) : 0;
        $is_edit_mode  = ( $edit_id > 0 );

        $buy_price  = floatval( $_POST['buy_price'] ?? 0 );
        $sell_price = floatval( $_POST['sell_price'] ?? 0 );
        $profit     = $sell_price - $buy_price;

        $data_array = array(
            'customer_id'   => intval( $_POST['customer_id'] ?? 0 ),
            'agent_id'      => intval( $_POST['agent_id'] ?? 0 ),
            'supplier_id'   => intval( $_POST['supplier_id'] ?? 0 ),
            'package_title' => sanitize_text_field( $_POST['package_title'] ?? '' ),
            'destination'   => sanitize_text_field( $_POST['destination'] ?? '' ),
            'duration'      => sanitize_text_field( $_POST['duration'] ?? '' ),
            'travel_date'   => sanitize_text_field( $_POST['travel_date'] ?? '' ),
            'buy_price'     => $buy_price,
            'sell_price'    => $sell_price,
            'profit'        => $profit,
            'status'        => sanitize_text_field( $_POST['status'] ?? 'Reserved' ),
            'created_by'    => get_current_user_id()
        );

        if ( $is_edit_mode ) {
            $wpdb->update( $table_tours, $data_array, array( 'id' => $edit_id ) );
            $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Tour booking updated successfully.</div>';
        } else {
            $data_array['created_at'] = current_time( 'mysql' );
            $wpdb->insert( $table_tours, $data_array );
            $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Holiday tour package reserved successfully.</div>';
        }
    }

    echo '<div class="ifs-tours-workspace">';
    
    ifs_terp_tours_render_tabs( $sub_action );
    echo $message;

    /* =========================================================================
       SUB-TAB 1: PACKAGE PLANS DIRECTORY & BUILDER
       ========================================================================= */
    if ( $sub_action === 'plans' ) {
        $edit_plan_id = isset( $_GET['plan_id'] ) ? intval( $_GET['plan_id'] ) : 0;
        $edit_plan_data = false;
        if ( $edit_plan_id > 0 ) {
            $edit_plan_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_plans WHERE id = %d", $edit_plan_id ) );
        }

        $all_plans = $wpdb->get_results( "SELECT * FROM $table_plans ORDER BY id DESC" );

        $val_p_name     = $edit_plan_data ? esc_attr( $edit_plan_data->package_name ) : '4D/3N Bangkok & Pattaya Wonder';
        $val_p_dest     = $edit_plan_data ? esc_attr( $edit_plan_data->destination ) : 'Thailand';
        $val_p_days     = $edit_plan_data ? intval( $edit_plan_data->total_days ) : 4;
        $val_p_nights   = $edit_plan_data ? intval( $edit_plan_data->total_nights ) : 3;
        $val_p_cost     = $edit_plan_data ? floatval( $edit_plan_data->cost_bdt ) : 28000;
        $val_p_sell     = $edit_plan_data ? floatval( $edit_plan_data->selling_price ) : 35000;
        $val_p_hotel    = $edit_plan_data ? esc_attr( $edit_plan_data->hotel_name ) : 'Amari Watergate Bangkok & Pattaya Bay Resort';
        $val_p_incl     = $edit_plan_data ? esc_textarea( $edit_plan_data->inclusions_text ) : "✔ 3 Star / 4 Star Hotel Stay with Daily Breakfast\n✔ Coral Island Tour with Speedboat & Indian/Thai Buffet Lunch\n✔ Bangkok City & Temple Tour (Golden Buddha + Marble Temple)\n✔ All Airport to Hotel & Inter-city Transfers in AC Private Van\n✔ Experienced English Speaking Guide Service";
        ?>
        
        <form method="post" action="<?php echo esc_url( $base_url . '&sub=plans' ); ?>" class="ifs-split-tour-editor">
            <?php wp_nonce_field( 'ifs_tour_plan_action', 'ifs_tour_plan_nonce' ); ?>
            <?php if ( $edit_plan_data ) : ?>
                <input type="hidden" name="edit_plan_id" value="<?php echo esc_attr( $edit_plan_data->id ); ?>">
            <?php endif; ?>

            <div class="ifs-tour-form-body">
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">01</div>
                        <div>
                            <h3 class="ifs-card-title"><?php echo $edit_plan_data ? 'Update Package Plan: ' . esc_html( $edit_plan_data->package_name ) : 'Configure New Holiday Package Plan'; ?></h3>
                            <p class="ifs-card-desc">Define standard fixed holiday itineraries, hotel properties, and selling prices</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <div class="ifs-field-block col-span-2">
                            <label class="ifs-field-label" for="inp_p_name">Package Plan Name <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-portfolio field-icon"></span>
                                <input type="text" name="package_name" id="inp_p_name" required 
                                       value="<?php echo $val_p_name; ?>" 
                                       placeholder="e.g. 5D/4N Romantic Maldives Holiday" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_p_dest">Destination Country / City <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-location-alt field-icon"></span>
                                <input type="text" name="destination" id="inp_p_dest" required 
                                       value="<?php echo $val_p_dest; ?>" 
                                       placeholder="e.g. Maldives" class="ifs-input-field uppercase">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_p_days">Duration (Days)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-clock field-icon"></span>
                                <input type="number" name="total_days" id="inp_p_days" 
                                       value="<?php echo $val_p_days; ?>" placeholder="4" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_p_nights">Nights</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-calendar-alt field-icon"></span>
                                <input type="number" name="total_nights" id="inp_p_nights" 
                                       value="<?php echo $val_p_nights; ?>" placeholder="3" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_p_cost">Base Vendor Cost (৳)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-cart field-icon"></span>
                                <input type="number" step="0.01" name="cost_bdt" id="inp_p_cost" 
                                       value="<?php echo $val_p_cost; ?>" placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_p_sell">Standard Selling Price (৳) <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-money-alt field-icon"></span>
                                <input type="number" step="0.01" name="selling_price" id="inp_p_sell" required 
                                       value="<?php echo $val_p_sell; ?>" placeholder="0.00" class="ifs-input-field font-mono font-bold color-emerald">
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-2">
                            <label class="ifs-field-label" for="inp_p_hotel">Featured Hotel Accommodations</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-building field-icon"></span>
                                <input type="text" name="hotel_name" id="inp_p_hotel" 
                                       value="<?php echo $val_p_hotel; ?>" placeholder="e.g. 4-Star Central Resort" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-3">
                            <label class="ifs-field-label" for="inp_p_incl">Guaranteed Inclusions (One per line) <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-clipboard field-icon textarea-icon"></span>
                                <textarea name="inclusions_text" id="inp_p_incl" required rows="5" 
                                          class="ifs-input-field has-textarea-icon" 
                                          placeholder="✔ Daily Breakfast&#10;✔ Sightseeing & Entry Tickets&#10;✔ Airport Transfers..."><?php echo $val_p_incl; ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ifs-action-strip">
                    <?php if ( $edit_plan_data ) : ?>
                        <a href="<?php echo esc_url( $base_url . '&sub=plans' ); ?>" class="ifs-btn-back">
                            <span class="dashicons dashicons-arrow-left-alt"></span> Cancel Edit
                        </a>
                    <?php else : ?>
                        <span class="ifs-submeta-hint"><span class="dashicons dashicons-info"></span> Published plans auto-fill during customer booking</span>
                    <?php endif; ?>
                    <button type="submit" name="ifs_tour_plan_submit" class="ifs-btn-primary">
                        <span class="dashicons dashicons-saved"></span> <?php echo $edit_plan_data ? 'Update Package Plan' : 'Save & Publish Package Plan'; ?>
                    </button>
                </div>
            </div>

            <!-- Right Sidebar: Live Preview Card -->
            <div class="ifs-preview-sidebar">
                <div class="ifs-preview-sticky">
                    <div class="ifs-card-preview-header">
                        <span class="dashicons dashicons-visibility"></span> Live Plan Brochure Preview
                    </div>

                    <div class="ifs-tour-card">
                        <div class="tour-head-strip">
                            <span class="tour-brand-tag" id="prev_plan_dest">THAILAND</span>
                            <span class="tour-duration-badge" id="prev_plan_dur">4D / 3N</span>
                        </div>

                        <div class="tour-hero">
                            <h3 class="tour-title" id="prev_plan_title">4D/3N BANGKOK & PATTAYA WONDER</h3>
                            <span class="tour-pax" id="prev_plan_hotel"><span class="dashicons dashicons-building"></span> Amari Watergate</span>
                        </div>

                        <div class="tour-inclusions-preview" id="prev_plan_incl">
                            ✔ Hotel with Breakfast<br>
                            ✔ Speedboat Coral Island<br>
                            ✔ Private Van Transfers
                        </div>

                        <div class="tour-fee-footer">
                            <div class="fee-row">
                                <span>STARTING PER PERSON:</span>
                                <strong class="color-green font-mono" id="prev_plan_price">৳35,000</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Plans Directory Table -->
        <div class="ifs-table-card" style="margin-top: 30px;">
            <div class="ifs-table-top-bar">
                <div class="ifs-table-title-group">
                    <h3 class="ifs-table-heading"><span class="dashicons dashicons-portfolio"></span> Configured Package Plans Directory</h3>
                    <p class="ifs-table-caption">Ready-made holiday itineraries and pricing templates</p>
                </div>
            </div>

            <div class="ifs-table-responsive-wrapper">
                <table class="ifs-pro-datatable" id="ifsTourPlansTable">
                    <thead>
                        <tr>
                            <th style="width: 220px;">Package Plan Name</th>
                            <th>Destination</th>
                            <th>Duration</th>
                            <th>Hotel Accommodations</th>
                            <th style="text-align: right;">Cost (৳)</th>
                            <th style="text-align: right;">Selling Rate (৳)</th>
                            <th style="text-align: right; width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $all_plans ) : foreach ( $all_plans as $pln ) : ?>
                            <tr>
                                <td><strong><?php echo esc_html( $pln->package_name ); ?></strong></td>
                                <td><span class="ifs-dest-tag"><span class="dashicons dashicons-location-alt"></span> <?php echo esc_html( $pln->destination ); ?></span></td>
                                <td><?php echo esc_html( $pln->total_days ); ?> Days / <?php echo esc_html( $pln->total_nights ); ?> Nights</td>
                                <td><?php echo esc_html( $pln->hotel_name ?: 'Standard Hotel' ); ?></td>
                                <td style="text-align: right; font-family: monospace; color: #64748b;">৳<?php echo number_format( $pln->cost_bdt, 2 ); ?></td>
                                <td style="text-align: right; font-family: monospace; font-weight: 800; color: #059669;">৳<?php echo number_format( $pln->selling_price, 2 ); ?></td>
                                <td style="text-align: right;">
                                    <div class="ifs-action-buttons">
                                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=tours&sub=plans&plan_id=' . $pln->id ) ); ?>" class="ifs-btn-action edit" title="Edit Plan">
                                            <span class="dashicons dashicons-edit"></span>
                                        </a>
                                        <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=tours&sub=delete_plan&plan_id=' . $pln->id ), 'delete_tour_plan_' . $pln->id ); ?>" class="ifs-btn-action delete" onclick="return confirm('Delete this package plan?');" title="Delete Plan">
                                            <span class="dashicons dashicons-trash"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr><td colspan="7" class="ifs-empty-table">No package plans created yet. Use the form above to add your first package template.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const pName  = document.getElementById('inp_p_name');
            const pDest  = document.getElementById('inp_p_dest');
            const pDays  = document.getElementById('inp_p_days');
            const pNights= document.getElementById('inp_p_nights');
            const pHotel = document.getElementById('inp_p_hotel');
            const pSell  = document.getElementById('inp_p_sell');
            const pIncl  = document.getElementById('inp_p_incl');

            const prevTitle = document.getElementById('prev_plan_title');
            const prevDest  = document.getElementById('prev_plan_dest');
            const prevDur   = document.getElementById('prev_plan_dur');
            const prevHotel = document.getElementById('prev_plan_hotel');
            const prevPrice = document.getElementById('prev_plan_price');
            const prevIncl  = document.getElementById('prev_plan_incl');

            function updatePlanPreview() {
                if (prevTitle) prevTitle.textContent = (pName && pName.value.trim()) ? pName.value.trim().toUpperCase() : 'PACKAGE PLAN NAME';
                if (prevDest) prevDest.textContent   = (pDest && pDest.value.trim()) ? pDest.value.trim().toUpperCase() : 'DESTINATION';
                if (prevDur) prevDur.textContent     = (pDays ? pDays.value : '4') + 'D / ' + (pNights ? pNights.value : '3') + 'N';
                if (prevHotel) prevHotel.innerHTML   = '<span class="dashicons dashicons-building"></span> ' + ((pHotel && pHotel.value.trim()) ? pHotel.value.trim() : 'Featured Hotel');
                
                const sVal = parseFloat(pSell ? pSell.value : 0) || 0;
                if (prevPrice) prevPrice.textContent = '৳' + sVal.toLocaleString('en-US', { minimumFractionDigits: 0 });

                if (prevIncl && pIncl) {
                    const lines = pIncl.value.split('\n').filter(l => l.trim() !== '');
                    if (lines.length > 0) {
                        prevIncl.innerHTML = lines.slice(0, 3).join('<br>') + (lines.length > 3 ? '<br><em>+ more inclusions...</em>' : '');
                    }
                }
            }

            [pName, pDest, pDays, pNights, pHotel, pSell, pIncl].forEach(el => {
                if (el) {
                    el.addEventListener('input', updatePlanPreview);
                    el.addEventListener('change', updatePlanPreview);
                }
            });
            updatePlanPreview();

            if (window.jQuery && jQuery.fn.DataTable) {
                jQuery('#ifsTourPlansTable').DataTable({ "pageLength": 10, "order": [[ 0, "desc" ]] });
            }
        });
        </script>
        <?php
    }

    /* =========================================================================
       SUB-TAB 2: BOOKING FORM (ADD / EDIT)
       ========================================================================= */
    elseif ( $sub_action === 'add' || $sub_action === 'edit' ) {
        $edit_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
        $edit_data = false;
        if ( $edit_id > 0 ) {
            $edit_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_tours WHERE id = %d", $edit_id ) );
        }

        $customers = $wpdb->get_results( "SELECT id, full_name, mobile FROM $table_customers ORDER BY full_name ASC" );
        $agents    = $wpdb->get_results( "SELECT id, agency_name, current_balance FROM $table_agents WHERE status = 'Active' ORDER BY agency_name ASC" );
        $suppliers = $wpdb->get_results( "SELECT id, supplier_name, current_balance FROM $table_suppliers WHERE status = 'Active' ORDER BY supplier_name ASC" );
        $plans     = $wpdb->get_results( "SELECT * FROM $table_plans ORDER BY package_name ASC" );
        
        $val_customer    = $edit_data ? intval( $edit_data->customer_id ) : 0;
        $val_agent       = $edit_data ? intval( $edit_data->agent_id ?? 0 ) : 0;
        $val_supplier    = $edit_data ? intval( $edit_data->supplier_id ?? 0 ) : 0;
        $val_title       = $edit_data ? esc_attr( $edit_data->package_title ) : '';
        $val_destination = $edit_data ? esc_attr( $edit_data->destination ) : '';
        $val_duration    = $edit_data ? esc_attr( $edit_data->duration ) : '';
        $val_travel_date = $edit_data ? esc_attr( $edit_data->travel_date ) : date( 'Y-m-d' );
        $val_buy         = $edit_data ? floatval( $edit_data->buy_price ) : '';
        $val_sell        = $edit_data ? floatval( $edit_data->sell_price ) : '';
        $val_profit      = $edit_data ? floatval( $edit_data->profit ) : 0;
        $val_status      = $edit_data ? esc_attr( $edit_data->status ) : 'Reserved';
        ?>
        
        <form method="post" action="" class="ifs-split-tour-editor">
            <?php wp_nonce_field( 'ifs_tour_action', 'ifs_tour_nonce' ); ?>
            <?php if ( $edit_data ) : ?>
                <input type="hidden" name="edit_id" value="<?php echo esc_attr( $edit_data->id ); ?>">
            <?php endif; ?>

            <div class="ifs-tour-form-body">
                
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">01</div>
                        <div>
                            <h3 class="ifs-card-title"><?php echo $edit_data ? 'Update Tour Booking' : 'Book Holiday Tour Package'; ?></h3>
                            <p class="ifs-card-desc">Assign traveler portfolio, tour destination, itinerary dates, and vendor settlement</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <!-- Template Auto-Fill Dropdown -->
                        <div class="ifs-field-block col-span-3">
                            <label class="ifs-field-label" for="inp_plan_template">Choose From Published Package Plans (Auto-Fill)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-portfolio field-icon"></span>
                                <select id="inp_plan_template" class="ifs-input-field">
                                    <option value="">-- Select Template or Fill Manually Below --</option>
                                    <?php foreach ( $plans as $pl ) : ?>
                                        <option value="<?php echo $pl->id; ?>"
                                                data-title="<?php echo esc_attr( $pl->package_name ); ?>"
                                                data-dest="<?php echo esc_attr( $pl->destination ); ?>"
                                                data-dur="<?php echo esc_attr( $pl->total_days . ' Days, ' . $pl->total_nights . ' Nights' ); ?>"
                                                data-cost="<?php echo esc_attr( $pl->cost_bdt ); ?>"
                                                data-sell="<?php echo esc_attr( $pl->selling_price ); ?>">
                                            <?php echo esc_html( $pl->package_name . ' (' . $pl->destination . ') | ' . $pl->total_days . 'D/' . $pl->total_nights . 'N | Rate: ৳' . number_format( $pl->selling_price, 0 ) ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-2">
                            <label class="ifs-field-label" for="inp_customer">Select Client / Traveler <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-admin-users field-icon"></span>
                                <select name="customer_id" id="inp_customer" required class="ifs-input-field">
                                    <option value="">-- Choose Client --</option>
                                    <?php foreach ( $customers as $cus ) : ?>
                                        <option value="<?php echo $cus->id; ?>" data-name="<?php echo esc_attr( $cus->full_name ); ?>" <?php selected( $val_customer, $cus->id ); ?>>
                                            <?php echo esc_html( $cus->full_name . ' (' . $cus->mobile . ')' ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_agent">B2B Sub-Agent</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-groups field-icon"></span>
                                <select name="agent_id" id="inp_agent" class="ifs-input-field">
                                    <option value="0">Direct Retail</option>
                                    <?php foreach ( $agents as $ag ) : ?>
                                        <option value="<?php echo $ag->id; ?>" <?php selected( $val_agent, $ag->id ); ?>>
                                            <?php echo esc_html( $ag->agency_name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-2">
                            <label class="ifs-field-label" for="inp_package_title">Package Title <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-palmtree field-icon"></span>
                                <input type="text" name="package_title" id="inp_package_title" required 
                                       value="<?php echo $val_title; ?>" 
                                       placeholder="e.g. 4D/3N Bangkok & Pattaya Holiday" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_supplier">DMC / Ground Supplier</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-networking field-icon"></span>
                                <select name="supplier_id" id="inp_supplier" class="ifs-input-field">
                                    <option value="0">In-house Operated</option>
                                    <?php foreach ( $suppliers as $sup ) : ?>
                                        <option value="<?php echo $sup->id; ?>" <?php selected( $val_supplier, $sup->id ); ?>>
                                            <?php echo esc_html( $sup->supplier_name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_destination">Destination City / Country <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-location-alt field-icon"></span>
                                <input type="text" name="destination" id="inp_destination" required 
                                       value="<?php echo $val_destination; ?>" 
                                       placeholder="e.g. Maldives" class="ifs-input-field uppercase">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_duration">Duration / Nights</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-clock field-icon"></span>
                                <input type="text" name="duration" id="inp_duration" 
                                       value="<?php echo $val_duration; ?>" 
                                       placeholder="e.g. 4 Days, 3 Nights" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_travel_date">Travel / Departure Date <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-calendar-alt field-icon"></span>
                                <input type="date" name="travel_date" id="inp_travel_date" required 
                                       value="<?php echo $val_travel_date; ?>" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_buy_price">Vendor Cost Rate (৳) <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-cart field-icon"></span>
                                <input type="number" step="0.01" name="buy_price" id="inp_buy_price" required 
                                       value="<?php echo $val_buy; ?>" placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_sell_price">Client Selling Price (৳) <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-money-alt field-icon"></span>
                                <input type="number" step="0.01" name="sell_price" id="inp_sell_price" required 
                                       value="<?php echo $val_sell; ?>" placeholder="0.00" class="ifs-input-field font-mono font-bold color-emerald">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label">Gross Margin / Profit (৳)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-chart-line field-icon"></span>
                                <input type="text" id="inp_profit" readonly 
                                       value="<?php echo number_format( $val_profit, 2 ); ?>" 
                                       class="ifs-input-field font-mono font-bold <?php echo ( $val_profit >= 0 ) ? 'profit-positive' : 'profit-negative'; ?>">
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-3">
                            <label class="ifs-field-label" for="inp_status">Booking Status</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-clipboard field-icon"></span>
                                <select name="status" id="inp_status" class="ifs-input-field">
                                    <option value="Reserved" <?php selected( $val_status, 'Reserved' ); ?>>Reserved (Deposit Paid)</option>
                                    <option value="Confirmed" <?php selected( $val_status, 'Confirmed' ); ?>>Confirmed (Fully Paid)</option>
                                    <option value="Pending" <?php selected( $val_status, 'Pending' ); ?>>Pending (Awaiting Token)</option>
                                    <option value="Cancelled" <?php selected( $val_status, 'Cancelled' ); ?>>Cancelled / Void</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ifs-action-strip">
                    <a href="<?php echo esc_url( $base_url . '&sub=list' ); ?>" class="ifs-btn-back">
                        <span class="dashicons dashicons-arrow-left-alt"></span> Cancel
                    </a>
                    <button type="submit" name="ifs_tour_submit" class="ifs-btn-primary">
                        <span class="dashicons dashicons-saved"></span> <?php echo $edit_data ? 'Update Booking' : 'Save Tour Booking'; ?>
                    </button>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="ifs-preview-sidebar">
                <div class="ifs-preview-sticky">
                    <div class="ifs-card-preview-header">
                        <span class="dashicons dashicons-visibility"></span> Live Travel Voucher Preview
                    </div>

                    <div class="ifs-tour-card">
                        <div class="tour-head-strip">
                            <span class="tour-brand-tag" id="prev_dest">DESTINATION</span>
                            <span class="tour-duration-badge" id="prev_duration">4 DAYS, 3 NIGHTS</span>
                        </div>

                        <div class="tour-hero">
                            <h3 class="tour-title" id="prev_title">HOLIDAY PACKAGE TITLE</h3>
                            <span class="tour-pax" id="prev_pax"><span class="dashicons dashicons-admin-users"></span> SELECT TRAVELER</span>
                        </div>

                        <div class="tour-grid-specs font-mono">
                            <div>
                                <span class="spec-lbl">TRAVEL DATE</span>
                                <strong class="spec-val color-cyan" id="prev_date">TBD</strong>
                            </div>
                            <div>
                                <span class="spec-lbl">STATUS</span>
                                <strong class="spec-val color-amber" id="prev_status">RESERVED</strong>
                            </div>
                        </div>

                        <div class="tour-fee-footer">
                            <div class="fee-row">
                                <span>INVOICE AMOUNT:</span>
                                <strong class="color-green font-mono" id="prev_sell">৳0.00</strong>
                            </div>
                        </div>
                    </div>

                    <div class="ifs-intel-box">
                        <div class="intel-head"><span class="dashicons dashicons-analytics"></span> Real-Time Profit Yield</div>
                        <div class="intel-body">
                            <div class="intel-row">
                                <span>Gross Margin:</span>
                                <strong id="intel_profit" class="color-green">৳0.00</strong>
                            </div>
                            <div class="intel-row">
                                <span>Yield Ratio:</span>
                                <strong id="intel_ratio">0.0%</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selTemplate = document.getElementById('inp_plan_template');
            const inpCustomer = document.getElementById('inp_customer');
            const inpTitle    = document.getElementById('inp_package_title');
            const inpDest     = document.getElementById('inp_destination');
            const inpDur      = document.getElementById('inp_duration');
            const inpDate     = document.getElementById('inp_travel_date');
            const inpBuy      = document.getElementById('inp_buy_price');
            const inpSell     = document.getElementById('inp_sell_price');
            const inpStatus   = document.getElementById('inp_status');

            const prevPax     = document.getElementById('prev_pax');
            const prevTitle   = document.getElementById('prev_title');
            const prevDest    = document.getElementById('prev_dest');
            const prevDur     = document.getElementById('prev_duration');
            const prevDate    = document.getElementById('prev_date');
            const prevStatus  = document.getElementById('prev_status');
            const prevSell    = document.getElementById('prev_sell');
            
            const profitDisplay = document.getElementById('inp_profit');
            const intelProfit   = document.getElementById('intel_profit');
            const intelRatio    = document.getElementById('intel_ratio');

            // Template Selector auto fill
            if (selTemplate) {
                selTemplate.addEventListener('change', function() {
                    if (this.selectedIndex > 0) {
                        const opt = this.options[this.selectedIndex];
                        if (inpTitle) inpTitle.value = opt.getAttribute('data-title') || '';
                        if (inpDest) inpDest.value   = opt.getAttribute('data-dest') || '';
                        if (inpDur) inpDur.value     = opt.getAttribute('data-dur') || '';
                        if (inpBuy) inpBuy.value     = opt.getAttribute('data-cost') || '';
                        if (inpSell) inpSell.value   = opt.getAttribute('data-sell') || '';
                        updateTourCard();
                    }
                });
            }

            function updateTourCard() {
                if (inpCustomer && inpCustomer.selectedIndex > 0) {
                    const opt = inpCustomer.options[inpCustomer.selectedIndex];
                    if (prevPax) prevPax.innerHTML = '<span class="dashicons dashicons-admin-users"></span> ' + (opt.getAttribute('data-name') || '').toUpperCase();
                } else {
                    if (prevPax) prevPax.innerHTML = '<span class="dashicons dashicons-admin-users"></span> SELECT TRAVELER';
                }

                if (prevTitle) prevTitle.textContent = (inpTitle && inpTitle.value.trim()) ? inpTitle.value.trim().toUpperCase() : 'HOLIDAY PACKAGE TITLE';
                if (prevDest) prevDest.textContent   = (inpDest && inpDest.value.trim()) ? inpDest.value.trim().toUpperCase() : 'DESTINATION';
                if (prevDur) prevDur.textContent     = (inpDur && inpDur.value.trim()) ? inpDur.value.trim().toUpperCase() : 'DURATION';
                if (prevStatus) prevStatus.textContent = (inpStatus) ? inpStatus.value.toUpperCase() : 'RESERVED';

                if (inpDate && inpDate.value) {
                    const d = new Date(inpDate.value);
                    if (prevDate) prevDate.textContent = d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }).toUpperCase();
                } else {
                    if (prevDate) prevDate.textContent = 'TBD';
                }

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
            }

            const watchFields = [inpCustomer, inpTitle, inpDest, inpDur, inpDate, inpBuy, inpSell, inpStatus];
            watchFields.forEach(el => {
                if (el) {
                    el.addEventListener('input', updateTourCard);
                    el.addEventListener('change', updateTourCard);
                }
            });

            updateTourCard();
        });
        </script>
        <?php
    }

    /* =========================================================================
       SUB-TAB 3: BOOKINGS LIST (DEFAULT)
       ========================================================================= */
    else {
        $bookings = $wpdb->get_results( "
            SELECT t.*, c.full_name, c.mobile, a.agency_name 
            FROM $table_tours t 
            LEFT JOIN $table_customers c ON t.customer_id = c.id 
            LEFT JOIN $table_agents a ON t.agent_id = a.id
            ORDER BY t.id DESC
        " );
        ?>
        <div class="ifs-table-card">
            <div class="ifs-table-top-bar">
                <div class="ifs-table-title-group">
                    <h3 class="ifs-table-heading"><span class="dashicons dashicons-list-view"></span> All Tour Bookings & Itineraries</h3>
                    <p class="ifs-table-caption">Holiday packages, traveler manifests, travel dates, and commercial margins</p>
                </div>
                <div class="ifs-table-btn-group">
                    <a href="<?php echo esc_url( $base_url . '&sub=add' ); ?>" class="ifs-btn-primary">
                        <span class="dashicons dashicons-plus-alt2"></span> Book Holiday Package
                    </a>
                </div>
            </div>

            <div class="ifs-table-responsive-wrapper">
                <table class="ifs-pro-datatable" id="ifsToursTable">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Tour ID</th>
                            <th>Client / Traveler</th>
                            <th>Package Title & Destination</th>
                            <th>Travel Date</th>
                            <th style="text-align: right;">Cost (৳)</th>
                            <th style="text-align: right;">Sell Price (৳)</th>
                            <th style="text-align: right;">Profit (৳)</th>
                            <th style="text-align: center;">Status</th>
                            <th style="text-align: right; width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $bookings ) : foreach ( $bookings as $b ) : 
                            $status_class = 'status-pending';
                            $status_lower = strtolower( $b->status );
                            if ( $status_lower === 'confirmed' )   $status_class = 'status-confirmed';
                            elseif ( $status_lower === 'reserved' ) $status_class = 'status-reserved';
                            elseif ( $status_lower === 'cancelled' ) $status_class = 'status-cancelled';

                            $channel_tag = ! empty( $b->agency_name ) ? '<span class="ifs-agent-tag"><span class="dashicons dashicons-groups"></span> ' . esc_html( $b->agency_name ) . '</span>' : '<span class="ifs-direct-tag">Direct Retail</span>';
                        ?>
                            <tr>
                                <td><span class="ifs-id-badge">#TR-<?php echo str_pad( (string) $b->id, 5, '0', STR_PAD_LEFT ); ?></span></td>
                                <td>
                                    <div class="ifs-passenger-name"><?php echo esc_html( $b->full_name ?: 'Direct Client' ); ?></div>
                                    <div class="ifs-passenger-submeta"><?php echo esc_html( $b->mobile ?: '-' ); ?> <span class="meta-dot"></span> <?php echo $channel_tag; ?></div>
                                </td>
                                <td>
                                    <div class="package-name"><strong><?php echo esc_html( $b->package_title ); ?></strong></div>
                                    <div class="ifs-destination-sub"><span class="dashicons dashicons-location-alt"></span> <?php echo esc_html( $b->destination ); ?> (<?php echo esc_html( $b->duration ); ?>)</div>
                                </td>
                                <td>
                                    <span class="flight-date"><?php echo date( 'd M Y', strtotime( $b->travel_date ) ); ?></span>
                                </td>
                                <td style="text-align: right; color: #64748b; font-family: ui-monospace, monospace;">৳<?php echo number_format( $b->buy_price, 2 ); ?></td>
                                <td style="text-align: right; font-weight: 700; color: #0f172a; font-family: ui-monospace, monospace;">৳<?php echo number_format( $b->sell_price, 2 ); ?></td>
                                <td style="text-align: right; font-weight: 800; font-family: ui-monospace, monospace;" class="<?php echo ( $b->profit >= 0 ) ? 'color-emerald' : 'color-rose'; ?>">
                                    ৳<?php echo number_format( $b->profit, 2 ); ?>
                                </td>
                                <td style="text-align: center;">
                                    <span class="ifs-status-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $b->status ); ?></span>
                                </td>
                                <td style="text-align: right;">
                                    <div class="ifs-action-buttons">
                                        <a href="<?php echo esc_url( $base_url . '&sub=edit&id=' . $b->id ); ?>" class="ifs-btn-action edit" title="Edit Booking">
                                            <span class="dashicons dashicons-edit"></span>
                                        </a>
                                        <a href="<?php echo wp_nonce_url( $base_url . '&sub=delete&id=' . $b->id, 'delete_tour_' . $b->id ); ?>" 
                                           class="ifs-btn-action delete" 
                                           onclick="return confirm('Are you sure you want to permanently delete this tour booking?');" 
                                           title="Delete Record">
                                            <span class="dashicons dashicons-trash"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr>
                                <td colspan="9" class="ifs-empty-table">
                                    <div class="ifs-empty-state">
                                        <span class="dashicons dashicons-palmtree"></span>
                                        <h4>No Tour Bookings Recorded</h4>
                                        <p>Start booking custom holiday itineraries and group packages.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                if ($.fn.DataTable) {
                    $('#ifsToursTable').DataTable({
                        "pageLength": 15,
                        "order": [[ 0, "desc" ]],
                        "language": {
                            "search": "_INPUT_",
                            "searchPlaceholder": "Search Client, Package, Destination...",
                            "lengthMenu": "Show _MENU_ entries"
                        }
                    });
                }
            });
        </script>
        <?php
    }

    echo '</div>';
    ?>

    <!-- Ultra High-End Stylesheet for Tours Module -->
    <style>
        .ifs-tours-workspace { max-width: 1440px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; }
        
        .ifs-toast { padding: 14px 20px; border-radius: 10px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 10px; margin-bottom: 22px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .ifs-toast.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .ifs-toast.danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .ifs-toast .dashicons { font-size: 18px; width: 18px; height: 18px; }

        /* Form Split Grid */
        .ifs-split-tour-editor { display: grid; grid-template-columns: 1fr 390px; gap: 28px; align-items: flex-start; }
        @media (max-width: 1140px) { .ifs-split-tour-editor { grid-template-columns: 1fr; } }

        .ifs-panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 26px; margin-bottom: 22px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); }
        .ifs-card-header { display: flex; align-items: center; gap: 14px; margin-bottom: 22px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9; }
        .ifs-step-num { width: 34px; height: 34px; border-radius: 9px; background: linear-gradient(135deg, #059669 0%, #047857 100%); color: #ffffff; font-weight: 800; font-size: 13px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(5, 150, 105, 0.25); flex-shrink: 0; }
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
        .ifs-field-wrap .field-icon { position: absolute; left: 12px; color: #94a3b8; font-size: 17px; width: 17px; height: 17px; pointer-events: none; z-index: 2; }
        .ifs-field-wrap .textarea-icon { top: 12px; }
        .ifs-field-wrap .ifs-input-field { width: 100%; padding: 9px 12px 9px 38px !important; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; color: #0f172a; background: #ffffff; outline: none; transition: all 0.2s ease; position: relative; z-index: 1; }
        textarea.ifs-input-field.has-textarea-icon { padding: 10px 12px 10px 38px !important; font-family: inherit; line-height: 1.5; }
        .ifs-field-wrap select.ifs-input-field {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat; background-position: right 10px center; background-size: 14px; padding-right: 32px !important;
        }
        .ifs-field-wrap .ifs-input-field:focus { border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12); }
        .uppercase { text-transform: uppercase; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .font-bold { font-weight: 700; }
        .color-emerald { color: #059669 !important; }
        .profit-positive { background: #f0fdf4 !important; color: #166534 !important; border-color: #bbf7d0 !important; }
        .profit-negative { background: #fef2f2 !important; color: #dc2626 !important; border-color: #fecaca !important; }

        .ifs-action-strip { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; }
        .ifs-btn-back { color: #64748b; text-decoration: none; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .ifs-submeta-hint { font-size: 12px; color: #64748b; display: inline-flex; align-items: center; gap: 4px; }
        .ifs-submeta-hint .dashicons { font-size: 14px; width: 14px; height: 14px; color: #059669; }
        .ifs-btn-primary { background: linear-gradient(135deg, #059669 0%, #047857 100%); color: #ffffff !important; border: none; padding: 11px 26px; border-radius: 8px; font-size: 13.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25); }

        /* Preview Sidebar */
        .ifs-preview-sticky { position: sticky; top: 30px; }
        .ifs-card-preview-header { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }

        .ifs-tour-card { background: linear-gradient(145deg, #064e3b 0%, #047857 60%, #059669 100%); border-radius: 16px; padding: 22px; color: #ffffff; box-shadow: 0 16px 36px -6px rgba(4, 120, 87, 0.35); border: 1px solid rgba(255, 255, 255, 0.15); margin-bottom: 18px; }
        .tour-head-strip { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px dashed rgba(255, 255, 255, 0.2); }
        .tour-brand-tag { font-size: 10px; font-weight: 800; letter-spacing: 0.8px; color: #a7f3d0; text-transform: uppercase; }
        .tour-duration-badge { background: rgba(255, 255, 255, 0.18); backdrop-filter: blur(4px); padding: 2px 8px; border-radius: 4px; font-size: 9.5px; font-weight: 800; text-transform: uppercase; }
        .tour-hero { margin-bottom: 16px; }
        .tour-title { margin: 0 0 6px 0; font-size: 15px; font-weight: 900; color: #ffffff; text-transform: uppercase; }
        .tour-pax { font-size: 11px; color: #a7f3d0; display: inline-flex; align-items: center; gap: 4px; }
        .tour-pax .dashicons { font-size: 13px; width: 13px; height: 13px; }
        .tour-grid-specs { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding-bottom: 14px; margin-bottom: 12px; border-bottom: 1px dashed rgba(255, 255, 255, 0.2); }
        .spec-lbl { font-size: 8.5px; font-weight: 700; color: #6ee7b7; letter-spacing: 0.5px; display: block; margin-bottom: 2px; }
        .spec-val { font-size: 12px; font-weight: 700; color: #ffffff; display: block; }
        .color-cyan { color: #38bdf8 !important; }
        .color-amber { color: #fde047 !important; }
        .tour-inclusions-preview { background: rgba(0,0,0,0.18); border-radius: 8px; padding: 10px 12px; margin-bottom: 12px; font-size: 11px; color: #d1fae5; line-height: 1.5; }
        .tour-fee-footer .fee-row { display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #d1fae5; }
        .tour-fee-footer strong { font-size: 16px; color: #86efac; }

        .ifs-intel-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02); }
        .intel-head { font-size: 12px; font-weight: 800; color: #047857; text-transform: uppercase; display: flex; align-items: center; gap: 6px; margin-bottom: 10px; }
        .intel-row { display: flex; justify-content: space-between; font-size: 12.5px; color: #475569; margin-bottom: 6px; }
        .intel-row strong { font-weight: 800; font-size: 13.5px; }

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
        
        .ifs-id-badge { background: #f1f5f9; color: #475569; font-family: ui-monospace, monospace; font-weight: 700; font-size: 11px; padding: 3px 6px; border-radius: 6px; }
        .ifs-passenger-name { font-weight: 700; color: #0f172a; font-size: 13px; }
        .ifs-passenger-submeta { font-size: 11px; color: #64748b; display: flex; align-items: center; gap: 6px; margin-top: 2px; }
        .meta-dot { width: 3px; height: 3px; border-radius: 50%; background: #cbd5e1; }
        .ifs-agent-tag { background: #eef2ff; color: #4338ca; font-size: 10px; font-weight: 700; padding: 1px 5px; border-radius: 4px; display: inline-flex; align-items: center; gap: 3px; }
        .ifs-agent-tag .dashicons { font-size: 11px; width: 11px; height: 11px; }
        .ifs-direct-tag { font-size: 10.5px; color: #059669; font-weight: 600; }
        .ifs-dest-tag { font-size: 11.5px; font-weight: 700; color: #0369a1; display: inline-flex; align-items: center; gap: 3px; }
        .ifs-dest-tag .dashicons { font-size: 13px; width: 13px; height: 13px; }
        
        .package-name { font-size: 13.5px; color: #0f172a; }
        .ifs-destination-sub { font-size: 11px; color: #64748b; display: inline-flex; align-items: center; gap: 4px; margin-top: 2px; text-transform: uppercase; font-weight: 600; }
        .ifs-destination-sub .dashicons { font-size: 12px; width: 12px; height: 12px; color: #0284c7; }
        .flight-date { font-weight: 600; color: #0f172a; font-size: 12px; }

        .ifs-status-badge { display: inline-flex; align-items: center; padding: 3px 8px; border-radius: 6px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; }
        .status-reserved  { background: #e0f2fe; color: #0369a1; }
        .status-confirmed { background: #dcfce7; color: #15803d; }
        .status-pending   { background: #fef3c7; color: #b45309; }
        .status-cancelled { background: #fee2e2; color: #b91c1c; }

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
    <?php
}