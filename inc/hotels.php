<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Enterprise Ultra-Modern Segmented Sub-Navigation for Hotel Bookings Module
 */
function ifs_terp_hotels_render_tabs( $active_tab = 'list' ) {
    global $wpdb;
    $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=hotels' );

    $table_hotels   = $wpdb->prefix . 'iterp_hotel_bookings';
    $table_props    = $wpdb->prefix . 'iterp_hotel_properties';

    $total_hotels   = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_hotels" );
    $total_props    = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_props" );
    $active_hotels  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $table_hotels WHERE status IN (%s, %s)", 'Confirmed', 'Reserved' ) );
    $total_profit   = (float) $wpdb->get_var( "SELECT SUM(profit) FROM $table_hotels WHERE status != 'Cancelled'" );
    ?>
    <div class="ifs-pro-tab-wrapper">
        <div class="ifs-pro-header-card">
            <div class="ifs-pro-identity">
                <div class="ifs-pro-icon-glow">
                    <span class="dashicons dashicons-building"></span>
                </div>
                <div class="ifs-pro-title-meta">
                    <div class="ifs-pro-badge-group">
                        <span class="ifs-status-dot"></span>
                        <span class="ifs-meta-tag">Hospitality Desk</span>
                        <span class="ifs-meta-tag-indigo">Hotel Reservations</span>
                    </div>
                    <h2 class="ifs-pro-heading">Hotel & Resort Reservations</h2>
                    <p class="ifs-pro-caption">Manage contracted hotel properties, room vouchers, guest manifests, and hospitality margins</p>
                </div>
            </div>
            
            <div class="ifs-pro-stats-strip">
                <div class="ifs-stat-pill">
                    <span class="ifs-stat-lbl">Total Stays</span>
                    <span class="ifs-stat-num color-dark"><?php echo number_format( $total_hotels ); ?></span>
                </div>
                <div class="ifs-stat-pill">
                    <span class="ifs-stat-lbl">Contracted Properties</span>
                    <span class="ifs-stat-num color-blue"><?php echo number_format( $total_props ); ?></span>
                </div>
                <div class="ifs-stat-pill">
                    <span class="ifs-stat-lbl">Active Bookings</span>
                    <span class="ifs-stat-num color-indigo"><?php echo number_format( $active_hotels ); ?></span>
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
                    <span class="ifs-btn-label">All Hotel Bookings</span>
                    <span class="ifs-pro-counter"><?php echo $total_hotels; ?></span>
                </a>

                <a href="<?php echo esc_url( $base_url . '&sub=properties' ); ?>" 
                   class="ifs-pro-nav-btn <?php echo ( $active_tab === 'properties' ) ? 'active-tab' : ''; ?>">
                    <span class="dashicons dashicons-admin-multisite"></span>
                    <span class="ifs-btn-label">Contracted Hotels</span>
                    <span class="ifs-pro-counter"><?php echo $total_props; ?></span>
                </a>

                <a href="<?php echo esc_url( $base_url . '&sub=add' ); ?>" 
                   class="ifs-pro-nav-btn <?php echo ( $active_tab === 'add' ) ? 'active-tab' : ''; ?>">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <span class="ifs-btn-label">Issue Hotel Voucher</span>
                </a>
            </nav>
        </div>
    </div>

    <style>
        .ifs-pro-tab-wrapper { margin-bottom: 30px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .ifs-pro-header-card { background: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; padding: 24px 28px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04); margin-bottom: 18px; }
        .ifs-pro-identity { display: flex; align-items: center; gap: 18px; }
        .ifs-pro-icon-glow { width: 52px; height: 52px; border-radius: 14px; background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%); display: flex; align-items: center; justify-content: center; color: #ffffff; box-shadow: 0 8px 18px -4px rgba(79, 70, 229, 0.35); flex-shrink: 0; }
        .ifs-pro-icon-glow .dashicons { font-size: 26px; width: 26px; height: 26px; }
        .ifs-pro-badge-group { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
        .ifs-status-dot { width: 6px; height: 6px; border-radius: 50%; background: #6366f1; display: inline-block; }
        .ifs-meta-tag { font-size: 10.5px; font-weight: 700; text-transform: uppercase; color: #64748b; }
        .ifs-meta-tag-indigo { font-size: 10px; font-weight: 700; text-transform: uppercase; background: #eef2ff; color: #4338ca; padding: 2px 7px; border-radius: 4px; }
        .ifs-pro-heading { margin: 0; font-size: 22px; font-weight: 800; color: #0f172a; }
        .ifs-pro-caption { margin: 3px 0 0 0; font-size: 13.5px; color: #64748b; }
        .ifs-pro-stats-strip { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .ifs-stat-pill { background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 16px; border-radius: 10px; display: flex; flex-direction: column; gap: 2px; min-width: 100px; }
        .ifs-stat-lbl { font-size: 10.5px; font-weight: 600; color: #64748b; text-transform: uppercase; }
        .ifs-stat-num { font-size: 16px; font-weight: 800; }
        .color-dark { color: #0f172a; }
        .color-blue { color: #0284c7; }
        .color-indigo { color: #4f46e5; }
        .color-emerald { color: #059669; }
        .ifs-pro-nav-container { display: flex; align-items: center; }
        .ifs-pro-nav-bar { background: #f1f5f9; border: 1px solid #e2e8f0; padding: 5px; border-radius: 12px; display: inline-flex; align-items: center; gap: 6px; max-width: 100%; overflow-x: auto; }
        .ifs-pro-nav-btn { display: inline-flex; align-items: center; gap: 9px; padding: 10px 20px; font-size: 13.5px; font-weight: 600; color: #64748b; text-decoration: none; border-radius: 9px; transition: all 0.2s ease; cursor: pointer; white-space: nowrap; border: 1px solid transparent; }
        .ifs-pro-nav-btn:hover { color: #0f172a; background: rgba(255, 255, 255, 0.65); }
        .ifs-pro-nav-btn.active-tab { background: #ffffff; color: #4f46e5; font-weight: 700; border: 1px solid rgba(79, 70, 229, 0.1); box-shadow: 0 4px 12px rgba(79, 70, 229, 0.08); }
        .ifs-pro-counter { background: #e2e8f0; color: #475569; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 20px; }
        .ifs-pro-nav-btn.active-tab .ifs-pro-counter { background: #4f46e5; color: #ffffff; }
    </style>
    <?php
}

/**
 * Main Controller for Hotel Bookings Module
 */
function ifs_terp_hotels_tab() {
    global $wpdb;
    $table_hotels    = $wpdb->prefix . 'iterp_hotel_bookings';
    $table_props     = $wpdb->prefix . 'iterp_hotel_properties';
    $table_customers = $wpdb->prefix . 'iterp_customers';
    $table_agents    = $wpdb->prefix . 'iterp_agents';
    $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
    $base_url        = admin_url( 'admin.php?page=ifs_travel_erp&tab=hotels' );

    $sub_action = isset( $_GET['sub'] ) ? sanitize_text_field( $_GET['sub'] ) : 'list';
    $message    = '';

    /* =========================================================================
       1. HANDLE DELETE OPERATIONS
       ========================================================================= */
    if ( $sub_action === 'delete' && isset( $_GET['id'] ) ) {
        $del_id = intval( $_GET['id'] );
        check_admin_referer( 'delete_hotel_' . $del_id );
        
        $wpdb->delete( $table_hotels, array( 'id' => $del_id ), array( '%d' ) );
        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            ifs_terp_log_activity( "Deleted Hotel Booking Record #HT-$del_id" );
        }
        wp_safe_redirect( add_query_arg( array( 'page' => 'ifs_travel_erp', 'tab' => 'hotels', 'msg' => 'deleted' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    if ( $sub_action === 'delete_prop' && isset( $_GET['prop_id'] ) ) {
        $prop_id = intval( $_GET['prop_id'] );
        check_admin_referer( 'delete_hotel_prop_' . $prop_id );
        
        $wpdb->delete( $table_props, array( 'id' => $prop_id ), array( '%d' ) );
        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            ifs_terp_log_activity( "Deleted Hotel Property #PRP-$prop_id" );
        }
        wp_safe_redirect( add_query_arg( array( 'page' => 'ifs_travel_erp', 'tab' => 'hotels', 'sub' => 'properties', 'msg' => 'prop_deleted' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    if ( isset( $_GET['msg'] ) ) {
        if ( $_GET['msg'] === 'deleted' ) {
            $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Hotel reservation deleted successfully.</div>';
        } elseif ( $_GET['msg'] === 'prop_deleted' ) {
            $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Hotel property removed from directory.</div>';
        }
    }

    /* =========================================================================
       2. HANDLE CONTRACTED HOTEL PROPERTY FORM SUBMIT
       ========================================================================= */
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_hotel_prop_submit'] ) ) {
        check_admin_referer( 'ifs_hotel_prop_action', 'ifs_hotel_prop_nonce' );

        $edit_prop_id = isset( $_POST['edit_prop_id'] ) ? intval( $_POST['edit_prop_id'] ) : 0;
        $is_prop_edit = ( $edit_prop_id > 0 );

        $prop_data = array(
            'property_name'  => sanitize_text_field( $_POST['property_name'] ?? '' ),
            'city'           => sanitize_text_field( $_POST['city'] ?? '' ),
            'country'        => sanitize_text_field( $_POST['country'] ?? '' ),
            'star_rating'    => sanitize_text_field( $_POST['star_rating'] ?? '4 Star' ),
            'contact_person' => sanitize_text_field( $_POST['contact_person'] ?? '' ),
            'contact_phone'  => sanitize_text_field( $_POST['contact_phone'] ?? '' ),
            'contract_rate'  => floatval( $_POST['contract_rate'] ?? 0 ),
            'standard_sell'  => floatval( $_POST['standard_sell'] ?? 0 ),
            'address'        => sanitize_text_field( $_POST['address'] ?? '' ),
            'amenities'      => sanitize_textarea_field( $_POST['amenities'] ?? '' ),
        );

        if ( $is_prop_edit ) {
            $wpdb->update( $table_props, $prop_data, array( 'id' => $edit_prop_id ) );
            $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Hotel property details updated successfully.</div>';
        } else {
            $prop_data['created_at'] = current_time( 'mysql' );
            $wpdb->insert( $table_props, $prop_data );
            $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> New Hotel property added to contracted directory.</div>';
        }
    }

    /* =========================================================================
       3. HANDLE HOTEL RESERVATION / VOUCHER SUBMIT
       ========================================================================= */
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_hotel_submit'] ) ) {
        check_admin_referer( 'ifs_hotel_action', 'ifs_hotel_nonce' );

        $edit_id      = isset( $_POST['edit_id'] ) ? intval( $_POST['edit_id'] ) : 0;
        $is_edit_mode = ( $edit_id > 0 );

        $buy_price  = floatval( $_POST['buy_price'] ?? 0 );
        $sell_price = floatval( $_POST['sell_price'] ?? 0 );
        $profit     = $sell_price - $buy_price;

        $data_array = array(
            'customer_id'    => intval( $_POST['customer_id'] ?? 0 ),
            'agent_id'       => intval( $_POST['agent_id'] ?? 0 ),
            'supplier_id'    => intval( $_POST['supplier_id'] ?? 0 ),
            'hotel_name'     => sanitize_text_field( $_POST['hotel_name'] ?? '' ),
            'city'           => sanitize_text_field( $_POST['city'] ?? '' ),
            'check_in'       => sanitize_text_field( $_POST['check_in'] ?? '' ),
            'check_out'      => sanitize_text_field( $_POST['check_out'] ?? '' ),
            'room_type'      => sanitize_text_field( $_POST['room_type'] ?? 'Deluxe Room' ),
            'meal_plan'      => sanitize_text_field( $_POST['meal_plan'] ?? 'Bed & Breakfast (BB)' ),
            'voucher_no'     => strtoupper( sanitize_text_field( $_POST['voucher_no'] ?? '' ) ),
            'confirmation_no'=> strtoupper( sanitize_text_field( $_POST['confirmation_no'] ?? '' ) ),
            'buy_price'      => $buy_price,
            'sell_price'     => $sell_price,
            'profit'         => $profit,
            'status'         => sanitize_text_field( $_POST['status'] ?? 'Confirmed' ),
            'special_req'    => sanitize_textarea_field( $_POST['special_req'] ?? '' ),
            'created_by'     => get_current_user_id()
        );

        if ( $is_edit_mode ) {
            $wpdb->update( $table_hotels, $data_array, array( 'id' => $edit_id ) );
            $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Hotel reservation updated successfully.</div>';
        } else {
            $data_array['created_at'] = current_time( 'mysql' );
            $wpdb->insert( $table_hotels, $data_array );
            $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Hotel voucher issued & reservation recorded successfully.</div>';
        }

        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            $action_log = $is_edit_mode ? "Updated" : "Reserved";
            ifs_terp_log_activity( "$action_log Hotel: " . sanitize_text_field( $_POST['hotel_name'] ) );
        }
    }

    echo '<div class="ifs-hotels-workspace">';
    
    // Top Tabs
    ifs_terp_hotels_render_tabs( $sub_action );
    echo $message;

    /* =========================================================================
       SUB-TAB: VIEW HOTEL VOUCHER
       ========================================================================= */
    if ( $sub_action === 'view' && isset( $_GET['id'] ) ) {
        $view_id = intval( $_GET['id'] );
        $booking = $wpdb->get_row( $wpdb->prepare( "
            SELECT h.*, 
                   c.full_name AS guest_name, c.mobile, c.passport_no, c.email,
                   a.agency_name,
                   s.supplier_name
            FROM $table_hotels h
            LEFT JOIN $table_customers c ON h.customer_id = c.id
            LEFT JOIN $table_agents a ON h.agent_id = a.id
            LEFT JOIN $table_suppliers s ON h.supplier_id = s.id
            WHERE h.id = %d
        ", $view_id ) );

        if ( ! $booking ) {
            echo '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> Hotel booking voucher not found.</div>';
            echo '</div>';
            return;
        }

        $nights = (int) max( 1, ( strtotime( $booking->check_out ) - strtotime( $booking->check_in ) ) / 86400 );
        ?>
        <div class="ifs-voucher-view-layout">
            <div class="ifs-view-header-strip">
                <div class="ifs-header-identity">
                    <a href="<?php echo esc_url( $base_url . '&sub=list' ); ?>" class="ifs-back-round-btn" title="Back to Bookings">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                    </a>
                    <div>
                        <div class="ifs-badge-row">
                            <span class="ifs-id-pill">#HT-<?php echo str_pad( (string) $booking->id, 5, '0', STR_PAD_LEFT ); ?></span>
                            <span class="ifs-voucher-pill"><?php echo esc_html( $booking->voucher_no ); ?></span>
                            <span class="ifs-status-badge status-confirmed"><?php echo esc_html( $booking->status ); ?></span>
                        </div>
                        <h2 class="ifs-view-name"><?php echo esc_html( $booking->hotel_name ); ?> &mdash; <?php echo esc_html( $booking->city ); ?></h2>
                    </div>
                </div>

                <div class="ifs-header-actions">
                    <button type="button" onclick="window.print();" class="ifs-btn-print">
                        <span class="dashicons dashicons-printer"></span> Print Hotel Voucher
                    </button>
                    <a href="<?php echo esc_url( $base_url . '&sub=edit&id=' . $booking->id ); ?>" class="ifs-btn-edit">
                        <span class="dashicons dashicons-edit"></span> Edit Reservation
                    </a>
                </div>
            </div>

            <!-- Printable Official Voucher Card -->
            <div class="ifs-official-voucher">
                <div class="vcr-header-band">
                    <div>
                        <span class="vcr-sup-title">CONFIRMED ACCOMMODATION VOUCHER</span>
                        <h1 class="vcr-hotel-title"><?php echo esc_html( $booking->hotel_name ); ?></h1>
                        <span class="vcr-city-line"><span class="dashicons dashicons-location-alt"></span> <?php echo esc_html( $booking->city ); ?></span>
                    </div>
                    <div class="vcr-meta-box">
                        <div class="vcr-meta-item">
                            <span>VOUCHER NO:</span>
                            <strong class="font-mono"><?php echo esc_html( $booking->voucher_no ); ?></strong>
                        </div>
                        <div class="vcr-meta-item">
                            <span>HOTEL CONFIRMATION:</span>
                            <strong class="font-mono color-indigo"><?php echo esc_html( $booking->confirmation_no ?: 'GUARANTEED' ); ?></strong>
                        </div>
                    </div>
                </div>

                <div class="vcr-guest-strip">
                    <div class="vcr-col">
                        <span class="lbl">PRIMARY GUEST NAME</span>
                        <strong class="val uppercase"><?php echo esc_html( $booking->guest_name ); ?></strong>
                    </div>
                    <div class="vcr-col">
                        <span class="lbl">PASSPORT NUMBER</span>
                        <strong class="val font-mono"><?php echo esc_html( $booking->passport_no ?: 'NOT SPECIFIED' ); ?></strong>
                    </div>
                    <div class="vcr-col">
                        <span class="lbl">CONTACT PHONE</span>
                        <strong class="val"><?php echo esc_html( $booking->mobile ?: 'N/A' ); ?></strong>
                    </div>
                </div>

                <div class="vcr-stay-grid">
                    <div class="stay-box">
                        <span class="s-lbl"><span class="dashicons dashicons-calendar-alt"></span> CHECK-IN DATE</span>
                        <strong class="s-val"><?php echo date( 'l, d F Y', strtotime( $booking->check_in ) ); ?></strong>
                        <span class="s-sub">Standard Check-in: 14:00 PM</span>
                    </div>
                    <div class="stay-box">
                        <span class="s-lbl"><span class="dashicons dashicons-calendar-alt"></span> CHECK-OUT DATE</span>
                        <strong class="s-val"><?php echo date( 'l, d F Y', strtotime( $booking->check_out ) ); ?></strong>
                        <span class="s-sub">Standard Check-out: 12:00 PM</span>
                    </div>
                    <div class="stay-box">
                        <span class="s-lbl"><span class="dashicons dashicons-clock"></span> TOTAL DURATION</span>
                        <strong class="s-val font-mono"><?php echo $nights; ?> Nights Stay</strong>
                        <span class="s-sub">Continuous Occupancy</span>
                    </div>
                </div>

                <div class="vcr-details-table-wrap">
                    <table class="vcr-table">
                        <thead>
                            <tr>
                                <th>Room Description</th>
                                <th>Board / Meal Basis</th>
                                <th>Booking Status</th>
                                <th style="text-align: right;">Invoiced Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html( $booking->room_type ); ?></strong>
                                    <div style="font-size:11px; color:#64748b;">Includes all local taxes and service charges</div>
                                </td>
                                <td><span class="vcr-meal-badge"><?php echo esc_html( $booking->meal_plan ); ?></span></td>
                                <td><span class="ifs-status-badge status-confirmed">Confirmed</span></td>
                                <td style="text-align: right; font-weight: 800; font-family: monospace; font-size: 15px;">৳<?php echo number_format( $booking->sell_price, 2 ); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <?php if ( ! empty( $booking->special_req ) ) : ?>
                    <div class="vcr-remarks-box">
                        <span class="vcr-rem-title"><span class="dashicons dashicons-info"></span> Special Guest Requests:</span>
                        <p><?php echo nl2br( esc_html( $booking->special_req ) ); ?></p>
                    </div>
                <?php endif; ?>

                <div class="vcr-footer-note">
                    <p><strong>Important Note:</strong> Please present this official confirmation voucher along with photo identification / passports upon arrival at the hotel reception.</p>
                </div>
            </div>
        </div>
        <?php
    }

    /* =========================================================================
       SUB-TAB: CONTRACTED HOTEL PROPERTIES DIRECTORY & FORM
       ========================================================================= */
    elseif ( $sub_action === 'properties' ) {
        $edit_prop_id   = isset( $_GET['prop_id'] ) ? intval( $_GET['prop_id'] ) : 0;
        $edit_prop_data = false;
        if ( $edit_prop_id > 0 ) {
            $edit_prop_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_props WHERE id = %d", $edit_prop_id ) );
        }

        $all_props = $wpdb->get_results( "SELECT * FROM $table_props ORDER BY id DESC" );

        $val_pr_name     = $edit_prop_data ? esc_attr( $edit_prop_data->property_name ) : 'Swissotel Makkah (Clock Tower)';
        $val_pr_city     = $edit_prop_data ? esc_attr( $edit_prop_data->city ) : 'Makkah';
        $val_pr_country  = $edit_prop_data ? esc_attr( $edit_prop_data->country ) : 'Saudi Arabia';
        $val_pr_star     = $edit_prop_data ? esc_attr( $edit_prop_data->star_rating ) : '5 Star';
        $val_pr_person   = $edit_prop_data ? esc_attr( $edit_prop_data->contact_person ) : 'Mr. Tariq Al-Mansoor';
        $val_pr_phone    = $edit_prop_data ? esc_attr( $edit_prop_data->contact_phone ) : '+966 12 571 8000';
        $val_pr_rate     = $edit_prop_data ? floatval( $edit_prop_data->contract_rate ) : 18500;
        $val_pr_sell     = $edit_prop_data ? floatval( $edit_prop_data->standard_sell ) : 22000;
        $val_pr_address  = $edit_prop_data ? esc_attr( $edit_prop_data->address ) : 'King Abdul Aziz Endowment, Abraj Al Bait Complex, Makkah';
        $val_pr_amenities= $edit_prop_data ? esc_textarea( $edit_prop_data->amenities ) : "Free High-Speed Wi-Fi\nDirect Haram Entrance via Mall\n24/7 Room Service & Dining\nConcierge & Baggage Assistance\nCentral Air Conditioning";
        ?>
        
        <form method="post" action="<?php echo esc_url( $base_url . '&sub=properties' ); ?>" class="ifs-split-hotel-editor">
            <?php wp_nonce_field( 'ifs_hotel_prop_action', 'ifs_hotel_prop_nonce' ); ?>
            <?php if ( $edit_prop_data ) : ?>
                <input type="hidden" name="edit_prop_id" value="<?php echo esc_attr( $edit_prop_data->id ); ?>">
            <?php endif; ?>

            <div class="ifs-hotel-form-body">
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">01</div>
                        <div>
                            <h3 class="ifs-card-title"><?php echo $edit_prop_data ? 'Update Contracted Hotel: ' . esc_html( $edit_prop_data->property_name ) : 'Configure Contracted Hotel Property'; ?></h3>
                            <p class="ifs-card-desc">Add partner hotels, city locations, star ratings, and negotiated contract rates</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <div class="ifs-field-block col-span-2">
                            <label class="ifs-field-label" for="inp_pr_name">Hotel / Property Name <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-building field-icon"></span>
                                <input type="text" name="property_name" id="inp_pr_name" required 
                                       value="<?php echo $val_pr_name; ?>" 
                                       placeholder="e.g. Swissotel Makkah" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_pr_star">Star Classification</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-star-filled field-icon"></span>
                                <select name="star_rating" id="inp_pr_star" class="ifs-input-field">
                                    <option value="5 Star" <?php selected( $val_pr_star, '5 Star' ); ?>>5 Star Luxury</option>
                                    <option value="4 Star" <?php selected( $val_pr_star, '4 Star' ); ?>>4 Star Premium</option>
                                    <option value="3 Star" <?php selected( $val_pr_star, '3 Star' ); ?>>3 Star Standard</option>
                                    <option value="2 Star / Boutique" <?php selected( $val_pr_star, '2 Star / Boutique' ); ?>>2 Star / Economy</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_pr_city">City <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-location field-icon"></span>
                                <input type="text" name="city" id="inp_pr_city" required 
                                       value="<?php echo $val_pr_city; ?>" placeholder="e.g. Makkah" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_pr_country">Country <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-admin-site-alt3 field-icon"></span>
                                <input type="text" name="country" id="inp_pr_country" required 
                                       value="<?php echo $val_pr_country; ?>" placeholder="e.g. Saudi Arabia" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_pr_rate">Contract Net Cost / Night (৳)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-cart field-icon"></span>
                                <input type="number" step="0.01" name="contract_rate" id="inp_pr_rate" 
                                       value="<?php echo $val_pr_rate; ?>" placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_pr_sell">Standard Selling Price / Night (৳) <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-money-alt field-icon"></span>
                                <input type="number" step="0.01" name="standard_sell" id="inp_pr_sell" required 
                                       value="<?php echo $val_pr_sell; ?>" placeholder="0.00" class="ifs-input-field font-mono font-bold color-indigo">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_pr_person">Hotel Reservation Contact</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-businessman field-icon"></span>
                                <input type="text" name="contact_person" id="inp_pr_person" 
                                       value="<?php echo $val_pr_person; ?>" placeholder="Reservation Manager" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_pr_phone">Contact Phone / Hotline</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-phone field-icon"></span>
                                <input type="text" name="contact_phone" id="inp_pr_phone" 
                                       value="<?php echo $val_pr_phone; ?>" placeholder="+966 ..." class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-3">
                            <label class="ifs-field-label" for="inp_pr_address">Physical Address / Proximity</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-location-alt field-icon"></span>
                                <input type="text" name="address" id="inp_pr_address" 
                                       value="<?php echo $val_pr_address; ?>" placeholder="Full street address or distance to landmarks" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-3">
                            <label class="ifs-field-label" for="inp_pr_amenities">Key Amenities & Services (One per line)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-clipboard field-icon textarea-icon"></span>
                                <textarea name="amenities" id="inp_pr_amenities" rows="4" 
                                          class="ifs-input-field has-textarea-icon" 
                                          placeholder="Free Wi-Fi, Breakfast Included, Swimming Pool..."><?php echo $val_pr_amenities; ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ifs-action-strip">
                    <?php if ( $edit_prop_data ) : ?>
                        <a href="<?php echo esc_url( $base_url . '&sub=properties' ); ?>" class="ifs-btn-back">
                            <span class="dashicons dashicons-arrow-left-alt"></span> Cancel Edit
                        </a>
                    <?php else : ?>
                        <span class="ifs-submeta-hint"><span class="dashicons dashicons-info"></span> Saved hotels populate auto-fill dropdowns when issuing vouchers</span>
                    <?php endif; ?>
                    <button type="submit" name="ifs_hotel_prop_submit" class="ifs-btn-primary">
                        <span class="dashicons dashicons-saved"></span> <?php echo $edit_prop_data ? 'Update Hotel Property' : 'Save Hotel Property'; ?>
                    </button>
                </div>
            </div>

            <!-- Right Sidebar: Live Preview -->
            <div class="ifs-preview-sidebar">
                <div class="ifs-preview-sticky">
                    <div class="ifs-card-preview-header">
                        <span class="dashicons dashicons-visibility"></span> Live Property Card Preview
                    </div>

                    <div class="ifs-hotel-card">
                        <div class="hotel-head-strip">
                            <span class="hotel-brand-tag" id="prev_prop_star">5 STAR LUXURY</span>
                            <span class="hotel-status-badge" id="prev_prop_loc">MAKKAH</span>
                        </div>

                        <div class="hotel-hero">
                            <h3 class="hotel-title" id="prev_prop_title">SWISSOTEL MAKKAH</h3>
                            <span class="hotel-city-sub" id="prev_prop_addr">King Abdul Aziz Endowment</span>
                        </div>

                        <div class="hotel-amenities-preview" id="prev_prop_amenities">
                            ✔ Free High-Speed Wi-Fi<br>
                            ✔ Direct Haram Entrance<br>
                            ✔ 24/7 Room Service
                        </div>

                        <div class="hotel-fee-footer">
                            <div class="fee-row">
                                <span>STANDARD RATE:</span>
                                <strong class="color-green font-mono" id="prev_prop_rate">৳22,000 / Night</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Properties Table -->
        <div class="ifs-table-card" style="margin-top: 30px;">
            <div class="ifs-table-top-bar">
                <div class="ifs-table-title-group">
                    <h3 class="ifs-table-heading"><span class="dashicons dashicons-admin-multisite"></span> Contracted Hotel Directory</h3>
                    <p class="ifs-table-caption">Partner hotels, negotiated base rates, and contact details</p>
                </div>
            </div>

            <div class="ifs-table-responsive-wrapper">
                <table class="ifs-pro-datatable" id="ifsHotelPropsTable">
                    <thead>
                        <tr>
                            <th style="width: 220px;">Hotel Name</th>
                            <th>Location</th>
                            <th>Rating</th>
                            <th>Contact Person</th>
                            <th style="text-align: right;">Contract Cost (৳)</th>
                            <th style="text-align: right;">Standard Sell (৳)</th>
                            <th style="text-align: right; width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $all_props ) : foreach ( $all_props as $prp ) : ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html( $prp->property_name ); ?></strong>
                                    <div style="font-size:11px; color:#64748b;"><?php echo esc_html( $prp->address ?: '-' ); ?></div>
                                </td>
                                <td><span class="ifs-dest-tag"><span class="dashicons dashicons-location-alt"></span> <?php echo esc_html( $prp->city . ', ' . $prp->country ); ?></span></td>
                                <td><span class="ifs-star-badge"><?php echo esc_html( $prp->star_rating ); ?></span></td>
                                <td>
                                    <div><?php echo esc_html( $prp->contact_person ?: '-' ); ?></div>
                                    <div style="font-size:11px; font-family:monospace; color:#64748b;"><?php echo esc_html( $prp->contact_phone ); ?></div>
                                </td>
                                <td style="text-align: right; font-family: monospace; color: #64748b;">৳<?php echo number_format( $prp->contract_rate, 2 ); ?></td>
                                <td style="text-align: right; font-family: monospace; font-weight: 800; color: #4f46e5;">৳<?php echo number_format( $prp->standard_sell, 2 ); ?></td>
                                <td style="text-align: right;">
                                    <div class="ifs-action-buttons">
                                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=hotels&sub=properties&prop_id=' . $prp->id ) ); ?>" class="ifs-btn-action edit" title="Edit Property">
                                            <span class="dashicons dashicons-edit"></span>
                                        </a>
                                        <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=hotels&sub=delete_prop&prop_id=' . $prp->id ), 'delete_hotel_prop_' . $prp->id ); ?>" class="ifs-btn-action delete" onclick="return confirm('Delete this hotel property?');" title="Delete Property">
                                            <span class="dashicons dashicons-trash"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr><td colspan="7" class="ifs-empty-table">No contracted properties configured yet. Add your first partner hotel above.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const prName      = document.getElementById('inp_pr_name');
            const prCity      = document.getElementById('inp_pr_city');
            const prStar      = document.getElementById('inp_pr_star');
            const prSell      = document.getElementById('inp_pr_sell');
            const prAddr      = document.getElementById('inp_pr_address');
            const prAmenities = document.getElementById('inp_pr_amenities');

            const prevTitle   = document.getElementById('prev_prop_title');
            const prevStar    = document.getElementById('prev_prop_star');
            const prevLoc     = document.getElementById('prev_prop_loc');
            const prevAddr    = document.getElementById('prev_prop_addr');
            const prevRate    = document.getElementById('prev_prop_rate');
            const prevAmen    = document.getElementById('prev_prop_amenities');

            function updatePropPreview() {
                if (prevTitle) prevTitle.textContent = (prName && prName.value.trim()) ? prName.value.trim().toUpperCase() : 'HOTEL PROPERTY NAME';
                if (prevStar)  prevStar.textContent  = (prStar) ? prStar.value.toUpperCase() : '5 STAR';
                if (prevLoc)   prevLoc.textContent   = (prCity && prCity.value.trim()) ? prCity.value.trim().toUpperCase() : 'CITY';
                if (prevAddr)  prevAddr.textContent  = (prAddr && prAddr.value.trim()) ? prAddr.value.trim() : 'Location Address';
                
                const sVal = parseFloat(prSell ? prSell.value : 0) || 0;
                if (prevRate) prevRate.textContent = '৳' + sVal.toLocaleString('en-US', { minimumFractionDigits: 0 }) + ' / Night';

                if (prevAmen && prAmenities) {
                    const lines = prAmenities.value.split('\n').filter(l => l.trim() !== '');
                    if (lines.length > 0) {
                        prevAmen.innerHTML = lines.slice(0, 3).map(l => '✔ ' + l).join('<br>');
                    }
                }
            }

            [prName, prCity, prStar, prSell, prAddr, prAmenities].forEach(el => {
                if (el) {
                    el.addEventListener('input', updatePropPreview);
                    el.addEventListener('change', updatePropPreview);
                }
            });
            updatePropPreview();

            if (window.jQuery && jQuery.fn.DataTable) {
                jQuery('#ifsHotelPropsTable').DataTable({ "pageLength": 10, "order": [[ 0, "desc" ]] });
            }
        });
        </script>
        <?php
    }

    /* =========================================================================
       SUB-TAB: ISSUE HOTEL VOUCHER FORM (ADD / EDIT)
       ========================================================================= */
    elseif ( $sub_action === 'add' || $sub_action === 'edit' ) {
        $edit_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
        $edit_data = false;
        if ( $edit_id > 0 ) {
            $edit_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_hotels WHERE id = %d", $edit_id ) );
        }

        $customers = $wpdb->get_results( "SELECT id, full_name, mobile, passport_no FROM $table_customers ORDER BY full_name ASC" );
        $agents    = $wpdb->get_results( "SELECT id, agency_name, current_balance FROM $table_agents WHERE status = 'Active' ORDER BY agency_name ASC" );
        $suppliers = $wpdb->get_results( "SELECT id, supplier_name, current_balance FROM $table_suppliers WHERE status = 'Active' ORDER BY supplier_name ASC" );
        $props     = $wpdb->get_results( "SELECT * FROM $table_props ORDER BY property_name ASC" );
        
        $val_customer    = $edit_data ? intval( $edit_data->customer_id ) : 0;
        $val_agent       = $edit_data ? intval( $edit_data->agent_id ?? 0 ) : 0;
        $val_supplier    = $edit_data ? intval( $edit_data->supplier_id ?? 0 ) : 0;
        $val_hotel       = $edit_data ? esc_attr( $edit_data->hotel_name ) : 'Swissotel Makkah (Clock Tower)';
        $val_city        = $edit_data ? esc_attr( $edit_data->city ) : 'Makkah, Saudi Arabia';
        $val_room        = $edit_data ? esc_attr( $edit_data->room_type ) : 'Deluxe Double Room';
        $val_meal        = $edit_data ? esc_attr( $edit_data->meal_plan ?? 'Bed & Breakfast (BB)' ) : 'Bed & Breakfast (BB)';
        $val_in          = $edit_data ? esc_attr( $edit_data->check_in ) : date( 'Y-m-d', strtotime( '+2 days' ) );
        $val_out         = $edit_data ? esc_attr( $edit_data->check_out ) : date( 'Y-m-d', strtotime( '+7 days' ) );
        $val_voucher     = $edit_data ? esc_attr( $edit_data->voucher_no ) : 'VCR-' . rand(100000, 999999);
        $val_confirm     = $edit_data ? esc_attr( $edit_data->confirmation_no ?? '' ) : '';
        $val_buy         = $edit_data ? floatval( $edit_data->buy_price ) : '';
        $val_sell        = $edit_data ? floatval( $edit_data->sell_price ) : '';
        $val_profit      = $edit_data ? floatval( $edit_data->profit ) : 0;
        $val_status      = $edit_data ? esc_attr( $edit_data->status ) : 'Confirmed';
        $val_req         = $edit_data ? esc_textarea( $edit_data->special_req ?? '' ) : '';
        ?>
        
        <form method="post" action="" class="ifs-split-hotel-editor">
            <?php wp_nonce_field( 'ifs_hotel_action', 'ifs_hotel_nonce' ); ?>
            <?php if ( $edit_data ) : ?>
                <input type="hidden" name="edit_id" value="<?php echo esc_attr( $edit_data->id ); ?>">
            <?php endif; ?>

            <div class="ifs-hotel-form-body">
                
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">01</div>
                        <div>
                            <h3 class="ifs-card-title"><?php echo $edit_data ? 'Update Hotel Reservation' : 'Issue Hotel Voucher & Room Reservation'; ?></h3>
                            <p class="ifs-card-desc">Select guest portfolio, hotel property, room category, and check-in dates</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <!-- Contracted Hotel Template Auto-Fill -->
                        <div class="ifs-field-block col-span-3">
                            <label class="ifs-field-label" for="inp_prop_template">Choose From Contracted Hotel Properties (Auto-Fill)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-admin-multisite field-icon"></span>
                                <select id="inp_prop_template" class="ifs-input-field">
                                    <option value="">-- Select Partner Hotel or Type Manually Below --</option>
                                    <?php foreach ( $props as $pr ) : ?>
                                        <option value="<?php echo $pr->id; ?>"
                                                data-name="<?php echo esc_attr( $pr->property_name ); ?>"
                                                data-city="<?php echo esc_attr( $pr->city . ', ' . $pr->country ); ?>"
                                                data-rate="<?php echo esc_attr( $pr->contract_rate ); ?>"
                                                data-sell="<?php echo esc_attr( $pr->standard_sell ); ?>">
                                            <?php echo esc_html( $pr->property_name . ' (' . $pr->city . ') | ' . $pr->star_rating . ' | Rate: ৳' . number_format( $pr->standard_sell, 0 ) . '/N' ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-2">
                            <label class="ifs-field-label" for="inp_customer">Select Guest / Primary Traveler <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-admin-users field-icon"></span>
                                <select name="customer_id" id="inp_customer" required class="ifs-input-field">
                                    <option value="">-- Choose Guest --</option>
                                    <?php foreach ( $customers as $cus ) : ?>
                                        <option value="<?php echo $cus->id; ?>" 
                                                data-name="<?php echo esc_attr( $cus->full_name ); ?>" 
                                                data-passport="<?php echo esc_attr( $cus->passport_no ?: 'N/A' ); ?>"
                                                <?php selected( $val_customer, $cus->id ); ?>>
                                            <?php echo esc_html( $cus->full_name . ' (' . $cus->mobile . ')' . ( $cus->passport_no ? ' [PPT: ' . $cus->passport_no . ']' : '' ) ); ?>
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
                                    <option value="0">Direct Retail Guest</option>
                                    <?php foreach ( $agents as $ag ) : ?>
                                        <option value="<?php echo $ag->id; ?>" <?php selected( $val_agent, $ag->id ); ?>>
                                            <?php echo esc_html( $ag->agency_name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-2">
                            <label class="ifs-field-label" for="inp_hotel_name">Hotel / Resort Property Name <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-building field-icon"></span>
                                <input type="text" name="hotel_name" id="inp_hotel_name" required 
                                       value="<?php echo $val_hotel; ?>" 
                                       placeholder="e.g. Swissotel Makkah / Atlantis The Palm Dubai" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_city">City & Destination <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-location-alt field-icon"></span>
                                <input type="text" name="city" id="inp_city" required 
                                       value="<?php echo $val_city; ?>" 
                                       placeholder="e.g. Makkah / Dubai / Bangkok" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_room_type">Room Category / Bedding <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-tag field-icon"></span>
                                <input type="text" name="room_type" id="inp_room_type" required 
                                       value="<?php echo $val_room; ?>" 
                                       placeholder="e.g. Deluxe Sea View Double" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_meal_plan">Meal Board Basis</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-coffee field-icon"></span>
                                <select name="meal_plan" id="inp_meal_plan" class="ifs-input-field">
                                    <option value="Room Only (RO)" <?php selected( $val_meal, 'Room Only (RO)' ); ?>>Room Only (RO)</option>
                                    <option value="Bed & Breakfast (BB)" <?php selected( $val_meal, 'Bed & Breakfast (BB)' ); ?>>Bed & Breakfast (BB)</option>
                                    <option value="Half Board (HB)" <?php selected( $val_meal, 'Half Board (HB)' ); ?>>Half Board (HB - Breakfast & Dinner)</option>
                                    <option value="Full Board (FB)" <?php selected( $val_meal, 'Full Board (FB)' ); ?>>Full Board (FB - All Meals)</option>
                                    <option value="All Inclusive (AI)" <?php selected( $val_meal, 'All Inclusive (AI)' ); ?>>All Inclusive (AI)</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_supplier">Wholesaler / Bedbank Supplier</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-networking field-icon"></span>
                                <select name="supplier_id" id="inp_supplier" class="ifs-input-field">
                                    <option value="0">Direct Hotel Contracting</option>
                                    <?php foreach ( $suppliers as $sup ) : ?>
                                        <option value="<?php echo $sup->id; ?>" <?php selected( $val_supplier, $sup->id ); ?>>
                                            <?php echo esc_html( $sup->supplier_name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_check_in">Check-In Date <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-calendar-alt field-icon"></span>
                                <input type="date" name="check_in" id="inp_check_in" required 
                                       value="<?php echo $val_in; ?>" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_check_out">Check-Out Date <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-calendar-alt field-icon"></span>
                                <input type="date" name="check_out" id="inp_check_out" required 
                                       value="<?php echo $val_out; ?>" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_voucher_no">Internal Voucher Number</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-media-document field-icon"></span>
                                <input type="text" name="voucher_no" id="inp_voucher_no" 
                                       value="<?php echo $val_voucher; ?>" class="ifs-input-field font-mono uppercase">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_confirmation_no">Hotel CRS / Confirmation Ref</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-nametag field-icon"></span>
                                <input type="text" name="confirmation_no" id="inp_confirmation_no" 
                                       value="<?php echo $val_confirm; ?>" placeholder="e.g. CRS-992109" class="ifs-input-field font-mono uppercase">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_buy_price">Supplier Cost Rate (৳) <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-cart field-icon"></span>
                                <input type="number" step="0.01" name="buy_price" id="inp_buy_price" required 
                                       value="<?php echo $val_buy; ?>" placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_sell_price">Client Selling Rate (৳) <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-money-alt field-icon"></span>
                                <input type="number" step="0.01" name="sell_price" id="inp_sell_price" required 
                                       value="<?php echo $val_sell; ?>" placeholder="0.00" class="ifs-input-field font-mono font-bold color-indigo">
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

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_status">Reservation Status</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-clipboard field-icon"></span>
                                <select name="status" id="inp_status" class="ifs-input-field">
                                    <option value="Confirmed" <?php selected( $val_status, 'Confirmed' ); ?>>Confirmed & Guaranteed</option>
                                    <option value="Reserved" <?php selected( $val_status, 'Reserved' ); ?>>Reserved (Tentative Hold)</option>
                                    <option value="Cancelled" <?php selected( $val_status, 'Cancelled' ); ?>>Cancelled / Released</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-3">
                            <label class="ifs-field-label" for="inp_special_req">Special Requests & Bedding Notes</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-edit field-icon"></span>
                                <input type="text" name="special_req" id="inp_special_req" 
                                       value="<?php echo $val_req; ?>" 
                                       placeholder="e.g. Non-smoking room, High floor, Early check-in requested" class="ifs-input-field">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ifs-action-strip">
                    <a href="<?php echo esc_url( $base_url . '&sub=list' ); ?>" class="ifs-btn-back">
                        <span class="dashicons dashicons-arrow-left-alt"></span> Cancel
                    </a>
                    <button type="submit" name="ifs_hotel_submit" class="ifs-btn-primary">
                        <span class="dashicons dashicons-saved"></span> <?php echo $edit_data ? 'Update Reservation' : 'Issue Hotel Voucher'; ?>
                    </button>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="ifs-preview-sidebar">
                <div class="ifs-preview-sticky">
                    <div class="ifs-card-preview-header">
                        <span class="dashicons dashicons-visibility"></span> Live Hotel Voucher Preview
                    </div>

                    <div class="ifs-hotel-card">
                        <div class="hotel-head-strip">
                            <span class="hotel-brand-tag">HOTEL VOUCHER</span>
                            <span class="hotel-status-badge" id="prev_status">CONFIRMED</span>
                        </div>

                        <div class="hotel-hero">
                            <h3 class="hotel-title" id="prev_hotel">SWISSOTEL MAKKAH</h3>
                            <span class="hotel-city-sub" id="prev_city"><span class="dashicons dashicons-location-alt"></span> Makkah, Saudi Arabia</span>
                        </div>

                        <div class="hotel-pax-box">
                            <span class="pax-lbl">PRIMARY GUEST NAME</span>
                            <strong class="pax-name uppercase" id="prev_guest">SELECT GUEST</strong>
                        </div>

                        <div class="hotel-grid-specs font-mono">
                            <div>
                                <span class="spec-lbl">CHECK-IN</span>
                                <strong class="spec-val color-cyan" id="prev_in">TBD</strong>
                            </div>
                            <div>
                                <span class="spec-lbl">CHECK-OUT</span>
                                <strong class="spec-val color-cyan" id="prev_out">TBD</strong>
                            </div>
                            <div>
                                <span class="spec-lbl">ROOM TYPE</span>
                                <strong class="spec-val" id="prev_room">DELUXE DOUBLE</strong>
                            </div>
                            <div>
                                <span class="spec-lbl">BOARD BASIS</span>
                                <strong class="spec-val color-green" id="prev_meal">BB</strong>
                            </div>
                        </div>

                        <div class="hotel-fee-footer">
                            <div class="fee-row">
                                <span>TOTAL INVOICED STAY:</span>
                                <strong class="color-green font-mono" id="prev_sell">৳0.00</strong>
                            </div>
                            <span class="hotel-barcode font-mono" id="prev_vcr">VCR: ------</span>
                        </div>
                    </div>

                    <div class="ifs-intel-box">
                        <div class="intel-head"><span class="dashicons dashicons-analytics"></span> Real-Time Hospitality Yield</div>
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
            const selProp     = document.getElementById('inp_prop_template');
            const inpCustomer = document.getElementById('inp_customer');
            const inpHotel    = document.getElementById('inp_hotel_name');
            const inpCity     = document.getElementById('inp_city');
            const inpRoom     = document.getElementById('inp_room_type');
            const inpMeal     = document.getElementById('inp_meal_plan');
            const inpIn       = document.getElementById('inp_check_in');
            const inpOut      = document.getElementById('inp_check_out');
            const inpVoucher  = document.getElementById('inp_voucher_no');
            const inpBuy      = document.getElementById('inp_buy_price');
            const inpSell     = document.getElementById('inp_sell_price');
            const inpStatus   = document.getElementById('inp_status');

            const prevGuest   = document.getElementById('prev_guest');
            const prevHotel   = document.getElementById('prev_hotel');
            const prevCity    = document.getElementById('prev_city');
            const prevRoom    = document.getElementById('prev_room');
            const prevMeal    = document.getElementById('prev_meal');
            const prevIn      = document.getElementById('prev_in');
            const prevOut     = document.getElementById('prev_out');
            const prevVcr     = document.getElementById('prev_vcr');
            const prevStatus  = document.getElementById('prev_status');
            const prevSell    = document.getElementById('prev_sell');
            
            const profitDisplay = document.getElementById('inp_profit');
            const intelProfit   = document.getElementById('intel_profit');
            const intelRatio    = document.getElementById('intel_ratio');

            // Property selector auto-fill
            if (selProp) {
                selProp.addEventListener('change', function() {
                    if (this.selectedIndex > 0) {
                        const opt = this.options[this.selectedIndex];
                        if (inpHotel) inpHotel.value = opt.getAttribute('data-name') || '';
                        if (inpCity)  inpCity.value  = opt.getAttribute('data-city') || '';
                        
                        // Calculate total based on stay nights
                        const d1 = new Date(inpIn.value);
                        const d2 = new Date(inpOut.value);
                        const nights = Math.max(1, Math.round((d2 - d1) / (1000 * 60 * 60 * 24)) || 1);

                        const rRate = parseFloat(opt.getAttribute('data-rate')) || 0;
                        const sRate = parseFloat(opt.getAttribute('data-sell')) || 0;

                        if (inpBuy) inpBuy.value = (rRate * nights).toFixed(2);
                        if (inpSell) inpSell.value = (sRate * nights).toFixed(2);
                        updateHotelVoucher();
                    }
                });
            }

            function updateHotelVoucher() {
                if (inpCustomer && inpCustomer.selectedIndex > 0) {
                    const opt = inpCustomer.options[inpCustomer.selectedIndex];
                    if (prevGuest) prevGuest.textContent = (opt.getAttribute('data-name') || '').toUpperCase();
                } else {
                    if (prevGuest) prevGuest.textContent = 'SELECT GUEST';
                }

                if (prevHotel) prevHotel.textContent = (inpHotel && inpHotel.value.trim()) ? inpHotel.value.trim().toUpperCase() : 'HOTEL PROPERTY NAME';
                if (prevCity)  prevCity.innerHTML   = '<span class="dashicons dashicons-location-alt"></span> ' + ((inpCity && inpCity.value.trim()) ? inpCity.value.trim() : 'City Destination');
                if (prevRoom)  prevRoom.textContent  = (inpRoom && inpRoom.value.trim()) ? inpRoom.value.trim().toUpperCase() : 'ROOM CATEGORY';
                
                if (prevMeal && inpMeal) {
                    const mVal = inpMeal.value;
                    prevMeal.textContent = mVal.includes('(') ? mVal.split('(')[1].replace(')', '') : 'BB';
                }

                if (prevStatus) prevStatus.textContent = (inpStatus) ? inpStatus.value.toUpperCase() : 'CONFIRMED';
                if (prevVcr)    prevVcr.textContent    = 'VCR: ' + ((inpVoucher && inpVoucher.value.trim()) ? inpVoucher.value.trim().toUpperCase() : '------');

                if (inpIn && inpIn.value) {
                    const d1 = new Date(inpIn.value);
                    if (prevIn) prevIn.textContent = d1.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' }).toUpperCase();
                } else {
                    if (prevIn) prevIn.textContent = 'TBD';
                }

                if (inpOut && inpOut.value) {
                    const d2 = new Date(inpOut.value);
                    if (prevOut) prevOut.textContent = d2.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' }).toUpperCase();
                } else {
                    if (prevOut) prevOut.textContent = 'TBD';
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

            const watchFields = [inpCustomer, inpHotel, inpCity, inpRoom, inpMeal, inpIn, inpOut, inpVoucher, inpBuy, inpSell, inpStatus];
            watchFields.forEach(el => {
                if (el) {
                    el.addEventListener('input', updateHotelVoucher);
                    el.addEventListener('change', updateHotelVoucher);
                }
            });

            updateHotelVoucher();
        });
        </script>
        <?php
    }

    /* =========================================================================
       SUB-TAB: ALL HOTEL BOOKINGS LIST (DEFAULT)
       ========================================================================= */
    else {
        $bookings = $wpdb->get_results( "
            SELECT h.*, c.full_name, c.mobile, a.agency_name 
            FROM $table_hotels h 
            LEFT JOIN $table_customers c ON h.customer_id = c.id 
            LEFT JOIN $table_agents a ON h.agent_id = a.id
            ORDER BY h.id DESC
        " );
        ?>
        <div class="ifs-table-card">
            <div class="ifs-table-top-bar">
                <div class="ifs-table-title-group">
                    <h3 class="ifs-table-heading"><span class="dashicons dashicons-list-view"></span> All Hotel & Resort Reservations</h3>
                    <p class="ifs-table-caption">Guest manifests, check-in schedules, voucher tracking, and commercial margins</p>
                </div>
                <div class="ifs-table-btn-group">
                    <a href="<?php echo esc_url( $base_url . '&sub=add' ); ?>" class="ifs-btn-primary">
                        <span class="dashicons dashicons-plus-alt2"></span> Issue Hotel Voucher
                    </a>
                </div>
            </div>

            <div class="ifs-table-responsive-wrapper">
                <table class="ifs-pro-datatable" id="ifsHotelsTable">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Stay ID</th>
                            <th>Guest / Traveler</th>
                            <th>Hotel Property & City</th>
                            <th>Stay Schedule</th>
                            <th>Voucher / CRS Ref</th>
                            <th style="text-align: right;">Cost (৳)</th>
                            <th style="text-align: right;">Sell Price (৳)</th>
                            <th style="text-align: right;">Profit (৳)</th>
                            <th style="text-align: center;">Status</th>
                            <th style="text-align: right; width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $bookings ) : foreach ( $bookings as $h ) : 
                            $status_class = 'status-confirmed';
                            $status_lower = strtolower( $h->status );
                            if ( $status_lower === 'reserved' )    $status_class = 'status-reserved';
                            elseif ( $status_lower === 'cancelled' ) $status_class = 'status-cancelled';

                            $channel_tag = ! empty( $h->agency_name ) ? '<span class="ifs-agent-tag"><span class="dashicons dashicons-groups"></span> ' . esc_html( $h->agency_name ) . '</span>' : '<span class="ifs-direct-tag">Direct Retail</span>';
                        ?>
                            <tr>
                                <td><span class="ifs-id-badge">#HT-<?php echo str_pad( (string) $h->id, 5, '0', STR_PAD_LEFT ); ?></span></td>
                                <td>
                                    <div class="ifs-passenger-name"><?php echo esc_html( $h->full_name ?: 'Direct Guest' ); ?></div>
                                    <div class="ifs-passenger-submeta"><?php echo esc_html( $h->mobile ?: '-' ); ?> <span class="meta-dot"></span> <?php echo $channel_tag; ?></div>
                                </td>
                                <td>
                                    <div class="package-name"><strong><?php echo esc_html( $h->hotel_name ); ?></strong></div>
                                    <div class="ifs-destination-sub"><span class="dashicons dashicons-location-alt"></span> <?php echo esc_html( $h->city ); ?> <span class="ifs-room-tag"><?php echo esc_html( $h->room_type ); ?></span></div>
                                </td>
                                <td>
                                    <div class="ifs-date-cell">
                                        <span class="date-main"><?php echo date( 'd M', strtotime( $h->check_in ) ); ?> &rarr; <?php echo date( 'd M Y', strtotime( $h->check_out ) ); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="ifs-voucher-pill font-mono"><?php echo esc_html( $h->voucher_no ?: '-' ); ?></span>
                                </td>
                                <td style="text-align: right; color: #64748b; font-family: ui-monospace, monospace;">৳<?php echo number_format( $h->buy_price, 2 ); ?></td>
                                <td style="text-align: right; font-weight: 700; color: #0f172a; font-family: ui-monospace, monospace;">৳<?php echo number_format( $h->sell_price, 2 ); ?></td>
                                <td style="text-align: right; font-weight: 800; font-family: ui-monospace, monospace;" class="<?php echo ( $h->profit >= 0 ) ? 'color-emerald' : 'color-rose'; ?>">
                                    ৳<?php echo number_format( $h->profit, 2 ); ?>
                                </td>
                                <td style="text-align: center;">
                                    <span class="ifs-status-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $h->status ); ?></span>
                                </td>
                                <td style="text-align: right;">
                                    <div class="ifs-action-buttons">
                                        <a href="<?php echo esc_url( $base_url . '&sub=view&id=' . $h->id ); ?>" class="ifs-btn-action view" title="View Voucher">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </a>
                                        <a href="<?php echo esc_url( $base_url . '&sub=edit&id=' . $h->id ); ?>" class="ifs-btn-action edit" title="Edit Reservation">
                                            <span class="dashicons dashicons-edit"></span>
                                        </a>
                                        <a href="<?php echo wp_nonce_url( $base_url . '&sub=delete&id=' . $h->id, 'delete_hotel_' . $h->id ); ?>" 
                                           class="ifs-btn-action delete" 
                                           onclick="return confirm('Are you sure you want to permanently delete this hotel reservation?');" 
                                           title="Delete Record">
                                            <span class="dashicons dashicons-trash"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr>
                                <td colspan="10" class="ifs-empty-table">
                                    <div class="ifs-empty-state">
                                        <span class="dashicons dashicons-building"></span>
                                        <h4>No Hotel Reservations Found</h4>
                                        <p>Start issuing room vouchers and managing hotel booking inventories.</p>
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
                    $('#ifsHotelsTable').DataTable({
                        "pageLength": 15,
                        "order": [[ 0, "desc" ]],
                        "language": {
                            "search": "_INPUT_",
                            "searchPlaceholder": "Search Guest, Hotel, City, Voucher...",
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

    <!-- Ultra High-End Stylesheet for Hotels Module -->
    <style>
        .ifs-hotels-workspace { max-width: 1440px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; }
        
        .ifs-toast { padding: 14px 20px; border-radius: 10px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 10px; margin-bottom: 22px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .ifs-toast.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .ifs-toast.danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .ifs-toast .dashicons { font-size: 18px; width: 18px; height: 18px; }

        /* Form Split Grid */
        .ifs-split-hotel-editor { display: grid; grid-template-columns: 1fr 390px; gap: 28px; align-items: flex-start; }
        @media (max-width: 1140px) { .ifs-split-hotel-editor { grid-template-columns: 1fr; } }

        .ifs-panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 26px; margin-bottom: 22px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); }
        .ifs-card-header { display: flex; align-items: center; gap: 14px; margin-bottom: 22px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9; }
        .ifs-step-num { width: 34px; height: 34px; border-radius: 9px; background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%); color: #ffffff; font-weight: 800; font-size: 13px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.25); flex-shrink: 0; }
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
        .ifs-field-wrap .ifs-input-field:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12); }
        .uppercase { text-transform: uppercase; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .font-bold { font-weight: 700; }
        .color-indigo { color: #4f46e5 !important; }
        .profit-positive { background: #f0fdf4 !important; color: #166534 !important; border-color: #bbf7d0 !important; }
        .profit-negative { background: #fef2f2 !important; color: #dc2626 !important; border-color: #fecaca !important; }

        .ifs-action-strip { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; }
        .ifs-btn-back { color: #64748b; text-decoration: none; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .ifs-submeta-hint { font-size: 12px; color: #64748b; display: inline-flex; align-items: center; gap: 4px; }
        .ifs-submeta-hint .dashicons { font-size: 14px; width: 14px; height: 14px; color: #4f46e5; }
        .ifs-btn-primary { background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%); color: #ffffff !important; border: none; padding: 11px 26px; border-radius: 8px; font-size: 13.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(79, 70, 229, 0.25); }

        /* Preview Sidebar */
        .ifs-preview-sticky { position: sticky; top: 30px; }
        .ifs-card-preview-header { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }

        .ifs-hotel-card { background: linear-gradient(145deg, #1e1b4b 0%, #312e81 60%, #4338ca 100%); border-radius: 16px; padding: 22px; color: #ffffff; box-shadow: 0 16px 36px -6px rgba(79, 70, 229, 0.35); border: 1px solid rgba(255, 255, 255, 0.15); margin-bottom: 18px; }
        .hotel-head-strip { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px dashed rgba(255, 255, 255, 0.2); }
        .hotel-brand-tag { font-size: 10px; font-weight: 800; letter-spacing: 0.8px; color: #c7d2fe; text-transform: uppercase; }
        .hotel-status-badge { background: rgba(255, 255, 255, 0.18); backdrop-filter: blur(4px); padding: 2px 8px; border-radius: 4px; font-size: 9.5px; font-weight: 800; text-transform: uppercase; }
        .hotel-hero { margin-bottom: 14px; }
        .hotel-title { margin: 0 0 4px 0; font-size: 15px; font-weight: 900; color: #ffffff; text-transform: uppercase; }
        .hotel-city-sub { font-size: 11px; color: #c7d2fe; display: inline-flex; align-items: center; gap: 4px; }
        .hotel-city-sub .dashicons { font-size: 13px; width: 13px; height: 13px; }

        .hotel-pax-box { background: rgba(0,0,0,0.2); padding: 8px 12px; border-radius: 8px; margin-bottom: 12px; }
        .pax-lbl { font-size: 8px; font-weight: 700; color: #a5b4fc; display: block; margin-bottom: 2px; }
        .pax-name { font-size: 12.5px; font-weight: 800; color: #ffffff; }

        .hotel-grid-specs { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding-bottom: 14px; margin-bottom: 12px; border-bottom: 1px dashed rgba(255, 255, 255, 0.2); }
        .spec-lbl { font-size: 8.5px; font-weight: 700; color: #a5b4fc; letter-spacing: 0.5px; display: block; margin-bottom: 2px; }
        .spec-val { font-size: 12px; font-weight: 700; color: #ffffff; display: block; }
        .color-cyan { color: #38bdf8 !important; }
        .color-green { color: #86efac !important; }
        
        .hotel-amenities-preview { background: rgba(0,0,0,0.18); border-radius: 8px; padding: 10px 12px; margin-bottom: 12px; font-size: 11px; color: #e0e7ff; line-height: 1.5; }
        .hotel-fee-footer .fee-row { display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #e0e7ff; margin-bottom: 4px; }
        .hotel-fee-footer strong { font-size: 16px; color: #86efac; }
        .hotel-barcode { font-size: 8.5px; color: #a5b4fc; letter-spacing: 1px; text-align: center; display: block; }

        .ifs-intel-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02); }
        .intel-head { font-size: 12px; font-weight: 800; color: #4f46e5; text-transform: uppercase; display: flex; align-items: center; gap: 6px; margin-bottom: 10px; }
        .intel-row { display: flex; justify-content: space-between; font-size: 12.5px; color: #475569; margin-bottom: 6px; }
        .intel-row strong { font-weight: 800; font-size: 13.5px; }

        /* Table Card */
        .ifs-table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); overflow: hidden; }
        .ifs-table-top-bar { padding: 22px 26px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; gap: 15px; }
        .ifs-table-heading { margin: 0; font-size: 17px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        .ifs-table-heading .dashicons { color: #4f46e5; font-size: 20px; width: 20px; height: 20px; }
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
        
        .package-name { font-size: 13.5px; color: #0f172a; }
        .ifs-destination-sub { font-size: 11px; color: #64748b; display: inline-flex; align-items: center; gap: 4px; margin-top: 2px; font-weight: 600; }
        .ifs-destination-sub .dashicons { font-size: 12px; width: 12px; height: 12px; color: #4f46e5; }
        .ifs-room-tag { background: #f1f5f9; padding: 1px 5px; border-radius: 3px; font-size: 10px; color: #475569; }
        .ifs-dest-tag { font-size: 11.5px; font-weight: 700; color: #0369a1; display: inline-flex; align-items: center; gap: 3px; }
        .ifs-dest-tag .dashicons { font-size: 13px; width: 13px; height: 13px; }
        .ifs-star-badge { background: #fef3c7; color: #b45309; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 4px; }

        .ifs-date-cell .date-main { font-weight: 600; color: #0f172a; font-size: 12px; }
        .ifs-voucher-pill { background: #f8fafc; border: 1px solid #e2e8f0; color: #4f46e5; font-weight: 700; font-size: 11px; padding: 2px 6px; border-radius: 4px; }

        .ifs-status-badge { display: inline-flex; align-items: center; padding: 3px 8px; border-radius: 6px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; }
        .status-confirmed { background: #dcfce7; color: #15803d; }
        .status-reserved  { background: #e0f2fe; color: #0369a1; }
        .status-cancelled { background: #fee2e2; color: #b91c1c; }

        .ifs-action-buttons { display: flex; gap: 5px; justify-content: flex-end; }
        .ifs-btn-action { width: 30px; height: 30px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.15s ease; }
        .ifs-btn-action.view   { background: #f1f5f9; color: #475569; }
        .ifs-btn-action.view:hover { background: #e2e8f0; color: #0f172a; }
        .ifs-btn-action.edit   { background: #eff6ff; color: #2563eb; }
        .ifs-btn-action.edit:hover { background: #dbeafe; color: #1d4ed8; }
        .ifs-btn-action.delete { background: #fef2f2; color: #dc2626; }
        .ifs-btn-action.delete:hover { background: #fee2e2; color: #b91c1c; }
        .ifs-btn-action .dashicons { font-size: 14px; width: 14px; height: 14px; }

        .ifs-empty-table { text-align: center; padding: 50px 20px !important; }
        .ifs-empty-state .dashicons { font-size: 44px; width: 44px; height: 44px; color: #cbd5e1; margin-bottom: 10px; }

        /* View Voucher Layout & Official Print Voucher */
        .ifs-view-header-strip {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 22px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04);
            margin-bottom: 22px;
        }
        .ifs-header-identity { display: flex; align-items: center; gap: 18px; }
        .ifs-back-round-btn { width: 44px; height: 44px; border-radius: 12px; background: #f1f5f9; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; color: #334155; text-decoration: none; transition: all 0.2s ease; }
        .ifs-back-round-btn:hover { background: #4f46e5; color: #ffffff; }
        .ifs-badge-row { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
        .ifs-view-name { margin: 0; font-size: 20px; font-weight: 800; color: #0f172a; letter-spacing: -0.4px; }
        .ifs-header-actions { display: flex; align-items: center; gap: 10px; }
        .ifs-btn-print { background: #f8fafc; border: 1px solid #cbd5e1; color: #334155 !important; padding: 10px 18px; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
        .ifs-btn-edit { background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%); color: #ffffff !important; padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }

        .ifs-official-voucher { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 36px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); max-width: 900px; margin: 0 auto; }
        .vcr-header-band { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 22px; border-bottom: 2px solid #0f172a; margin-bottom: 24px; }
        .vcr-sup-title { font-size: 11px; font-weight: 800; color: #4f46e5; letter-spacing: 1px; display: block; margin-bottom: 4px; }
        .vcr-hotel-title { margin: 0 0 6px 0; font-size: 24px; font-weight: 900; color: #0f172a; }
        .vcr-city-line { font-size: 13px; color: #64748b; display: inline-flex; align-items: center; gap: 4px; }
        .vcr-meta-box { text-align: right; display: flex; flex-direction: column; gap: 6px; }
        .vcr-meta-item span { font-size: 10px; font-weight: 700; color: #64748b; display: block; }
        .vcr-meta-item strong { font-size: 14px; }

        .vcr-guest-strip { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; }
        .vcr-col .lbl { font-size: 10px; font-weight: 700; color: #64748b; display: block; margin-bottom: 3px; }
        .vcr-col .val { font-size: 13.5px; font-weight: 800; color: #0f172a; }

        .vcr-stay-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
        .stay-box { border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; display: flex; flex-direction: column; gap: 2px; }
        .stay-box .s-lbl { font-size: 10.5px; font-weight: 700; color: #4f46e5; display: flex; align-items: center; gap: 4px; margin-bottom: 4px; }
        .stay-box .s-val { font-size: 14px; font-weight: 800; color: #0f172a; }
        .stay-box .s-sub { font-size: 11px; color: #94a3b8; margin-top: 2px; }

        .vcr-details-table-wrap { margin-bottom: 24px; }
        .vcr-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .vcr-table thead th { background: #f1f5f9; padding: 10px 14px; text-align: left; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; border-bottom: 2px solid #cbd5e1; }
        .vcr-table tbody td { padding: 14px; border-bottom: 1px solid #e2e8f0; color: #334155; }
        .vcr-meal-badge { background: #e0e7ff; color: #4338ca; padding: 3px 8px; border-radius: 4px; font-weight: 700; font-size: 11px; }

        .vcr-remarks-box { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; }
        .vcr-rem-title { font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; display: flex; align-items: center; gap: 4px; margin-bottom: 4px; }
        .vcr-remarks-box p { margin: 0; font-size: 13px; color: #334155; line-height: 1.5; }

        .vcr-footer-note { font-size: 11.5px; color: #64748b; line-height: 1.5; border-top: 1px solid #f1f5f9; padding-top: 14px; }

        @media print {
            body * { visibility: hidden; }
            .ifs-official-voucher, .ifs-official-voucher * { visibility: visible; }
            .ifs-official-voucher { position: absolute; left: 0; top: 0; width: 100%; border: 1px solid #000; box-shadow: none; }
        }
    </style>
    <?php
}