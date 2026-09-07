<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_hotels_view_page' ) ) {
    /**
     * Printable Hotel Accommodation Voucher Detail View
     * Features: Official Voucher Layout, Guest Strip, Occupancy Breakdown, Stay Duration & Print Optimization
     */
    function ifs_terp_hotels_view_page() {
        global $wpdb;
        $table_hotels    = $wpdb->prefix . 'iterp_hotel_bookings';
        $table_customers = $wpdb->prefix . 'iterp_customers';
        $table_agents    = $wpdb->prefix . 'iterp_agents';
        $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
        $base_url        = admin_url( 'admin.php?page=ifs_travel_erp&tab=hotels' );

        $view_id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
        $booking = $wpdb->get_row( $wpdb->prepare( "
            SELECT h.*, 
                   c.full_name AS guest_name, c.mobile, c.passport_no, c.email,
                   a.agency_name,
                   s.supplier_name
            FROM {$table_hotels} h
            LEFT JOIN {$table_customers} c ON h.customer_id = c.id
            LEFT JOIN {$table_agents} a ON h.agent_id = a.id
            LEFT JOIN {$table_suppliers} s ON h.supplier_id = s.id
            WHERE h.id = %d
        ", $view_id ) );

        if ( ! $booking ) {
            echo '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> ' . esc_html__( 'Hotel booking voucher not found.', 'ifs-travel-erp' ) . '</div>';
            return;
        }

        $nights = (int) max( 1, ( strtotime( $booking->check_out ) - strtotime( $booking->check_in ) ) / 86400 );
        $rooms  = isset( $booking->no_of_rooms ) ? intval( $booking->no_of_rooms ) : 1;
        $adults = isset( $booking->adult_count ) ? intval( $booking->adult_count ) : 2;
        $child  = isset( $booking->child_count ) ? intval( $booking->child_count ) : 0;
        ?>
        <div class="ifs-panel-card">
            <div class="ifs-card-header" style="justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="ifs-id-badge">#HT-<?php echo esc_html( str_pad( (string) $booking->id, 5, '0', STR_PAD_LEFT ) ); ?></span>
                    <span class="badge badge-confirmed"><?php echo esc_html( $booking->status ); ?></span>
                    <h3 class="ifs-card-title"><?php echo esc_html( $booking->hotel_name . ' — ' . $booking->city ); ?></h3>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button type="button" onclick="window.print();" class="ifs-btn-secondary"><span class="dashicons dashicons-printer"></span> <?php esc_html_e( 'Print Voucher', 'ifs-travel-erp' ); ?></button>
                    <a href="<?php echo esc_url( add_query_arg( array( 'sub' => 'edit', 'id' => $booking->id ), $base_url ) ); ?>" class="ifs-btn-secondary"><span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Edit', 'ifs-travel-erp' ); ?></a>
                    <a href="<?php echo esc_url( add_query_arg( 'sub', 'list', $base_url ) ); ?>" class="ifs-btn-secondary"><span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'Back', 'ifs-travel-erp' ); ?></a>
                </div>
            </div>

            <!-- Printable Voucher Body -->
            <div class="ifs-official-voucher">
                <div class="vcr-header-band">
                    <div>
                        <span class="vcr-sup-title"><?php esc_html_e( 'CONFIRMED ACCOMMODATION VOUCHER', 'ifs-travel-erp' ); ?></span>
                        <h1 class="vcr-hotel-title"><?php echo esc_html( $booking->hotel_name ); ?></h1>
                        <span class="vcr-city-line"><span class="dashicons dashicons-location-alt"></span> <?php echo esc_html( $booking->city ); ?></span>
                    </div>
                    <div class="vcr-meta-box">
                        <div class="vcr-meta-item">
                            <span><?php esc_html_e( 'VOUCHER NO:', 'ifs-travel-erp' ); ?></span>
                            <strong class="font-mono"><?php echo esc_html( $booking->voucher_no ); ?></strong>
                        </div>
                        <div class="vcr-meta-item">
                            <span><?php esc_html_e( 'HOTEL CONFIRMATION:', 'ifs-travel-erp' ); ?></span>
                            <strong class="font-mono" style="color: #003376;"><?php echo esc_html( $booking->confirmation_no ?: __( 'GUARANTEED', 'ifs-travel-erp' ) ); ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Guest & Occupancy Strip -->
                <div class="vcr-guest-strip">
                    <div class="vcr-col">
                        <span class="lbl"><?php esc_html_e( 'PRIMARY GUEST NAME', 'ifs-travel-erp' ); ?></span>
                        <strong class="val uppercase"><?php echo esc_html( $booking->guest_name ); ?></strong>
                    </div>
                    <div class="vcr-col">
                        <span class="lbl"><?php esc_html_e( 'PASSPORT NUMBER', 'ifs-travel-erp' ); ?></span>
                        <strong class="val font-mono"><?php echo esc_html( $booking->passport_no ?: '—' ); ?></strong>
                    </div>
                    <div class="vcr-col">
                        <span class="lbl"><?php esc_html_e( 'ROOMS & OCCUPANCY', 'ifs-travel-erp' ); ?></span>
                        <strong class="val font-mono"><?php 
                            /* translators: 1: number of rooms, 2: number of adults, 3: child string */
                            printf( esc_html__( '%1$d Room(s) | %2$d Adult(s)%3$s', 'ifs-travel-erp' ), $rooms, $adults, ( $child > 0 ? ", {$child} Child" : '' ) ); 
                        ?></strong>
                    </div>
                </div>

                <div class="vcr-stay-grid">
                    <div class="stay-box">
                        <span class="s-lbl"><span class="dashicons dashicons-calendar-alt"></span> <?php esc_html_e( 'CHECK-IN DATE', 'ifs-travel-erp' ); ?></span>
                        <strong class="s-val"><?php echo esc_html( date_i18n( 'l, d F Y', strtotime( $booking->check_in ) ) ); ?></strong>
                        <span class="s-sub"><?php esc_html_e( 'Standard Check-in: 14:00 PM', 'ifs-travel-erp' ); ?></span>
                    </div>
                    <div class="stay-box">
                        <span class="s-lbl"><span class="dashicons dashicons-calendar-alt"></span> <?php esc_html_e( 'CHECK-OUT DATE', 'ifs-travel-erp' ); ?></span>
                        <strong class="s-val"><?php echo esc_html( date_i18n( 'l, d F Y', strtotime( $booking->check_out ) ) ); ?></strong>
                        <span class="s-sub"><?php esc_html_e( 'Standard Check-out: 12:00 PM', 'ifs-travel-erp' ); ?></span>
                    </div>
                    <div class="stay-box">
                        <span class="s-lbl"><span class="dashicons dashicons-clock"></span> <?php esc_html_e( 'TOTAL DURATION', 'ifs-travel-erp' ); ?></span>
                        <strong class="s-val font-mono"><?php printf( esc_html__( '%d Nights Stay', 'ifs-travel-erp' ), $nights ); ?></strong>
                        <span class="s-sub"><?php esc_html_e( 'Continuous Occupancy', 'ifs-travel-erp' ); ?></span>
                    </div>
                </div>

                <div class="vcr-details-table-wrap">
                    <table class="vcr-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Room Description', 'ifs-travel-erp' ); ?></th>
                                <th><?php esc_html_e( 'Meal Plan', 'ifs-travel-erp' ); ?></th>
                                <th><?php esc_html_e( 'Status', 'ifs-travel-erp' ); ?></th>
                                <th style="text-align: right;"><?php esc_html_e( 'Total Amount', 'ifs-travel-erp' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html( $booking->room_type ); ?></strong>
                                    <div style="font-size:11px; color:#64748b;"><?php esc_html_e( 'Includes taxes and standard hotel charges', 'ifs-travel-erp' ); ?></div>
                                </td>
                                <td><span class="vcr-meal-badge"><?php echo esc_html( $booking->meal_plan ); ?></span></td>
                                <td><span class="badge badge-confirmed"><?php echo esc_html( $booking->status ); ?></span></td>
                                <td style="text-align: right; font-weight: 800; font-family: monospace; font-size: 15px;">৳<?php echo esc_html( number_format( (float) $booking->sell_price, 2 ) ); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <?php if ( ! empty( $booking->special_req ) ) : ?>
                    <div class="vcr-remarks-box">
                        <span class="vcr-rem-title"><span class="dashicons dashicons-info"></span> <?php esc_html_e( 'Special Guest Requests:', 'ifs-travel-erp' ); ?></span>
                        <p><?php echo wp_kses_post( nl2br( esc_html( $booking->special_req ) ) ); ?></p>
                    </div>
                <?php endif; ?>

                <div class="vcr-footer-note">
                    <p><strong><?php esc_html_e( 'Notice:', 'ifs-travel-erp' ); ?></strong> <?php esc_html_e( 'Please present this official confirmation voucher along with photo identification or passports upon arrival at the hotel front desk.', 'ifs-travel-erp' ); ?></p>
                </div>
            </div>
        </div>

        <!-- Stylesheet -->
        <style>
            .ifs-panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px 24px; margin-bottom: 20px; }
            .ifs-card-header { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
            .ifs-card-title { margin: 0; font-size: 16px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 6px; }

            .ifs-id-badge { background: #f1f5f9; color: #475569; font-family: monospace; font-weight: 700; font-size: 11px; padding: 2px 6px; border-radius: 4px; }
            .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; }
            .badge-confirmed { background: #dcfce7; color: #15803d; }
            
            .ifs-btn-secondary { background: #f8fafc; color: #475569 !important; border: 1px solid #cbd5e1; padding: 7px 14px; border-radius: 6px; font-size: 12.5px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; cursor: pointer; }
            .ifs-btn-secondary:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; }

            /* Print Voucher Styles */
            .ifs-official-voucher { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 28px; max-width: 860px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; }
            .vcr-header-band { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 18px; border-bottom: 2px solid #0f172a; margin-bottom: 20px; }
            .vcr-sup-title { font-size: 10.5px; font-weight: 800; color: #003376; letter-spacing: 0.8px; display: block; margin-bottom: 4px; }
            .vcr-hotel-title { margin: 0 0 4px; font-size: 22px; font-weight: 900; color: #0f172a; }
            .vcr-city-line { font-size: 12.5px; color: #64748b; display: inline-flex; align-items: center; gap: 4px; }
            .vcr-meta-box { text-align: right; display: flex; flex-direction: column; gap: 4px; }
            .vcr-meta-item span { font-size: 9.5px; font-weight: 700; color: #64748b; display: block; }
            .vcr-meta-item strong { font-size: 13px; }

            .vcr-guest-strip { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; }
            .vcr-col .lbl { font-size: 9.5px; font-weight: 700; color: #64748b; display: block; margin-bottom: 2px; }
            .vcr-col .val { font-size: 13px; font-weight: 800; color: #0f172a; }

            .vcr-stay-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 20px; }
            .stay-box { border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; display: flex; flex-direction: column; gap: 2px; }
            .stay-box .s-lbl { font-size: 10px; font-weight: 700; color: #003376; display: flex; align-items: center; gap: 4px; margin-bottom: 2px; }
            .stay-box .s-val { font-size: 13.5px; font-weight: 800; color: #0f172a; }
            .stay-box .s-sub { font-size: 10.5px; color: #94a3b8; }

            .vcr-details-table-wrap { margin-bottom: 20px; }
            .vcr-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
            .vcr-table thead th { background: #f1f5f9; padding: 8px 12px; text-align: left; font-size: 10.5px; font-weight: 700; color: #475569; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; }
            .vcr-table tbody td { padding: 12px; border-bottom: 1px solid #e2e8f0; color: #334155; }
            .vcr-meal-badge { background: #e0f2fe; color: #0369a1; padding: 2px 7px; border-radius: 4px; font-weight: 700; font-size: 10.5px; }

            .vcr-remarks-box { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 12px 16px; margin-bottom: 18px; }
            .vcr-rem-title { font-size: 10.5px; font-weight: 700; color: #475569; text-transform: uppercase; display: flex; align-items: center; gap: 4px; margin-bottom: 4px; }
            .vcr-remarks-box p { margin: 0; font-size: 12.5px; color: #334155; }
            .vcr-footer-note { font-size: 11px; color: #64748b; line-height: 1.5; border-top: 1px solid #f1f5f9; padding-top: 12px; }

            .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
            .uppercase { text-transform: uppercase; }

            @media print {
                body * { visibility: hidden; }
                .ifs-official-voucher, .ifs-official-voucher * { visibility: visible; }
                .ifs-official-voucher { position: absolute; left: 0; top: 0; width: 100%; border: 1px solid #000; box-shadow: none; }
            }
        </style>
        <?php
    }
}