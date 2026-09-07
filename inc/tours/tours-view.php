<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_tours_view_page' ) ) {
    /**
     * Universal Tour & Package Plan View Engine
     * Handles:
     * 1. Package Plan Template Profiles (sub=view_plan&plan_id=ID)
     * 2. Passenger Tour Booking Vouchers (sub=view&id=ID)
     */
    function ifs_terp_tours_view_page() {
        global $wpdb;
        $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=tours' );

        $plan_id    = isset( $_GET['plan_id'] ) ? absint( wp_unslash( $_GET['plan_id'] ) ) : 0;
        $booking_id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
        $sub_action = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : '';

        // ==========================================
        // 1. PACKAGE PLAN TEMPLATE VIEW PROFILE
        // ==========================================
        if ( $plan_id > 0 || 'view_plan' === $sub_action ) {
            $table_plans = $wpdb->prefix . 'iterp_tour_packages';
            $plan = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_plans} WHERE id = %d", $plan_id ) );

            if ( ! $plan ) {
                echo '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> ' . esc_html__( 'Tour package template profile not found.', 'ifs-travel-erp' ) . '</div>';
                return;
            }

            $cost   = (float) $plan->cost_bdt;
            $sell   = (float) $plan->selling_price;
            $margin = $sell - $cost;
            $ratio  = $sell > 0 ? ( ( $margin / $sell ) * 100 ) : 0;

            // Inclusions parsing
            $std_inclusions = ! empty( $plan->inclusions_standard ) ? array_map( 'trim', explode( ',', $plan->inclusions_standard ) ) : array();

            $inc_list = array();
            if ( ! empty( $plan->inclusions_text ) ) {
                $dec = json_decode( $plan->inclusions_text, true );
                $inc_list = is_array( $dec ) ? $dec : array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", '', $plan->inclusions_text ) ) ) );
            }

            $excl_list = array();
            if ( ! empty( $plan->exclusions_text ) ) {
                $dec = json_decode( $plan->exclusions_text, true );
                $excl_list = is_array( $dec ) ? $dec : array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", '', $plan->exclusions_text ) ) ) );
            }
            ?>
            <div class="ifs-panel-card">
                <div class="ifs-card-header" style="justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <span class="ifs-id-badge">#PLAN-<?php echo esc_html( str_pad( (string) $plan->id, 4, '0', STR_PAD_LEFT ) ); ?></span>
                        <span class="ifs-tag-pill"><?php echo esc_html( $plan->package_type ?? 'Holiday Tour' ); ?></span>
                        <h3 class="ifs-card-title"><?php echo esc_html( $plan->package_name ); ?></h3>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button type="button" onclick="window.print();" class="ifs-btn-primary"><span class="dashicons dashicons-printer"></span> <?php esc_html_e( 'Print Profile', 'ifs-travel-erp' ); ?></button>
                        <a href="<?php echo esc_url( add_query_arg( array( 'sub' => 'plans', 'plan_id' => $plan->id ), $base_url ) ); ?>" class="ifs-btn-secondary"><span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Edit Template', 'ifs-travel-erp' ); ?></a>
                        <a href="<?php echo esc_url( add_query_arg( 'sub', 'plans', $base_url ) ); ?>" class="ifs-btn-secondary"><span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'Back to Plans', 'ifs-travel-erp' ); ?></a>
                    </div>
                </div>

                <div class="ifs-official-voucher">
                    <div class="vcr-header-band">
                        <div>
                            <span class="vcr-sup-title"><?php esc_html_e( 'TOUR PACKAGE MASTER TEMPLATE', 'ifs-travel-erp' ); ?></span>
                            <h1 class="vcr-hotel-title"><?php echo esc_html( $plan->package_name ); ?></h1>
                            <span class="vcr-city-line"><span class="dashicons dashicons-location-alt"></span> <?php echo esc_html( $plan->destination ); ?> &bull; <?php echo esc_html( $plan->total_days . ' Days / ' . $plan->total_nights . ' Nights' ); ?></span>
                        </div>
                        <div class="vcr-meta-box">
                            <div class="vcr-meta-item">
                                <span><?php esc_html_e( 'TEMPLATE REFERENCE', 'ifs-travel-erp' ); ?></span>
                                <strong class="font-mono">#PLAN-<?php echo esc_html( str_pad( (string) $plan->id, 4, '0', STR_PAD_LEFT ) ); ?></strong>
                            </div>
                            <div class="vcr-meta-item">
                                <span><?php esc_html_e( 'PACKAGE CATEGORY', 'ifs-travel-erp' ); ?></span>
                                <strong style="color: #003376;"><?php echo esc_html( $plan->package_type ?? 'Holiday Tour' ); ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="vcr-stay-grid">
                        <div class="stay-box">
                            <span class="s-lbl"><span class="dashicons dashicons-location"></span> <?php esc_html_e( 'DESTINATION', 'ifs-travel-erp' ); ?></span>
                            <strong class="s-val uppercase"><?php echo esc_html( $plan->destination ); ?></strong>
                            <span class="s-sub"><?php esc_html_e( 'Target Region', 'ifs-travel-erp' ); ?></span>
                        </div>
                        <div class="stay-box">
                            <span class="s-lbl"><span class="dashicons dashicons-clock"></span> <?php esc_html_e( 'DURATION', 'ifs-travel-erp' ); ?></span>
                            <strong class="s-val"><?php echo esc_html( $plan->total_days . 'D / ' . $plan->total_nights . 'N' ); ?></strong>
                            <span class="s-sub"><?php echo esc_html( $plan->total_days ); ?> Days Tour</span>
                        </div>
                        <div class="stay-box">
                            <span class="s-lbl"><span class="dashicons dashicons-building"></span> <?php esc_html_e( 'PREFERRED HOTEL', 'ifs-travel-erp' ); ?></span>
                            <strong class="s-val"><?php echo esc_html( $plan->hotel_name ?: 'Standard Selected Hotel' ); ?></strong>
                            <span class="s-sub"><?php esc_html_e( 'Standard Accommodation', 'ifs-travel-erp' ); ?></span>
                        </div>
                        <div class="stay-box">
                            <span class="s-lbl"><span class="dashicons dashicons-chart-pie"></span> <?php esc_html_e( 'ESTIMATED MARGIN', 'ifs-travel-erp' ); ?></span>
                            <strong class="s-val font-mono color-emerald">৳<?php echo esc_html( number_format( $margin, 2 ) ); ?></strong>
                            <span class="s-sub"><?php echo esc_html( number_format( $ratio, 1 ) ); ?>% <?php esc_html_e( 'Agency Yield', 'ifs-travel-erp' ); ?></span>
                        </div>
                    </div>

                    <!-- Financial Rates Breakdown -->
                    <div class="vcr-details-table-wrap">
                        <table class="vcr-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Pricing Tier', 'ifs-travel-erp' ); ?></th>
                                    <th><?php esc_html_e( 'Duration Specification', 'ifs-travel-erp' ); ?></th>
                                    <th style="text-align: right;"><?php esc_html_e( 'Supplier Cost', 'ifs-travel-erp' ); ?></th>
                                    <th style="text-align: right;"><?php esc_html_e( 'Selling Fare (BDT)', 'ifs-travel-erp' ); ?></th>
                                    <th style="text-align: right;"><?php esc_html_e( 'Net Margin', 'ifs-travel-erp' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong><?php echo esc_html( $plan->package_name ); ?></strong></td>
                                    <td><?php echo esc_html( $plan->total_days . ' Days / ' . $plan->total_nights . ' Nights' ); ?></td>
                                    <td style="text-align: right; font-family: monospace; color: #64748b;">৳<?php echo esc_html( number_format( $cost, 2 ) ); ?></td>
                                    <td style="text-align: right; font-family: monospace; font-weight: 700; color: #003376;">৳<?php echo esc_html( number_format( $sell, 2 ) ); ?></td>
                                    <td style="text-align: right; font-family: monospace; font-weight: 800; color: <?php echo $margin >= 0 ? '#059669' : '#dc2626'; ?>;">
                                        ৳<?php echo esc_html( number_format( $margin, 2 ) ); ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <?php if ( ! empty( $std_inclusions ) ) : ?>
                        <div class="vcr-inclusions-card" style="margin-bottom: 20px;">
                            <span class="vcr-card-heading"><?php esc_html_e( 'STANDARD INCLUDED SERVICES', 'ifs-travel-erp' ); ?></span>
                            <div class="vcr-pills-wrap">
                                <?php foreach ( $std_inclusions as $si ) : ?>
                                    <span class="vcr-inc-pill"><span class="dashicons dashicons-yes"></span> <?php echo esc_html( $si ); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Detailed Inclusions and Exclusions Repeaters -->
                    <div class="vcr-repeaters-grid">
                        <div class="vcr-repeat-box inc">
                            <h4 class="vcr-repeat-head inc"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Highlights & Inclusions', 'ifs-travel-erp' ); ?></h4>
                            <ul class="vcr-check-list inc">
                                <?php if ( ! empty( $inc_list ) ) : foreach ( $inc_list as $item ) : ?>
                                    <li><?php echo esc_html( $item ); ?></li>
                                <?php endforeach; else : ?>
                                    <li style="color: #94a3b8;"><?php esc_html_e( 'No specific inclusion highlights recorded.', 'ifs-travel-erp' ); ?></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div class="vcr-repeat-box excl">
                            <h4 class="vcr-repeat-head excl"><span class="dashicons dashicons-dismiss"></span> <?php esc_html_e( 'Exclusions & Extra Charges', 'ifs-travel-erp' ); ?></h4>
                            <ul class="vcr-check-list excl">
                                <?php if ( ! empty( $excl_list ) ) : foreach ( $excl_list as $item ) : ?>
                                    <li><?php echo esc_html( $item ); ?></li>
                                <?php endforeach; else : ?>
                                    <li style="color: #94a3b8;"><?php esc_html_e( 'No specific exclusion items recorded.', 'ifs-travel-erp' ); ?></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="vcr-footer-note" style="margin-top: 24px;">
                        <p><strong><?php esc_html_e( 'Operational Note:', 'ifs-travel-erp' ); ?></strong> <?php esc_html_e( 'This package plan is configured as an ERP inventory template. Rates and itineraries auto-fill when making a booking under "Add Tour".', 'ifs-travel-erp' ); ?></p>
                    </div>
                </div>
            </div>
            <?php
        }

        // ==========================================
        // 2. PASSENGER TOUR BOOKING VOUCHER VIEW
        // ==========================================
        else {
            $table_tours     = $wpdb->prefix . 'iterp_tours';
            $table_customers = $wpdb->prefix . 'iterp_customers';
            $table_agents    = $wpdb->prefix . 'iterp_agents';
            $table_suppliers = $wpdb->prefix . 'iterp_suppliers';

            $booking = $wpdb->get_row( $wpdb->prepare( "
                SELECT t.*, 
                       c.full_name AS customer_name, c.mobile, c.passport_no, c.email,
                       a.agency_name,
                       s.supplier_name
                FROM {$table_tours} t
                LEFT JOIN {$table_customers} c ON t.customer_id = c.id
                LEFT JOIN {$table_agents} a ON t.agent_id = a.id
                LEFT JOIN {$table_suppliers} s ON t.supplier_id = s.id
                WHERE t.id = %d
            ", $booking_id ) );

            if ( ! $booking ) {
                echo '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> ' . esc_html__( 'Tour booking voucher not found.', 'ifs-travel-erp' ) . '</div>';
                return;
            }

            $status_class = 'badge-reserved';
            $st = strtolower( (string) $booking->status );
            if ( 'confirmed' === $st ) {
                $status_class = 'badge-confirmed';
            } elseif ( 'pending' === $st ) {
                $status_class = 'badge-pending';
            } elseif ( 'cancelled' === $st ) {
                $status_class = 'badge-cancelled';
            }

            $pay_class = 'pay-due';
            $pst = strtolower( (string) ( $booking->payment_status ?? 'paid' ) );
            if ( 'paid' === $pst ) {
                $pay_class = 'pay-paid';
            } elseif ( 'partial' === $pst ) {
                $pay_class = 'pay-partial';
            }

            $inclusions_list = ! empty( $booking->inclusions ) ? array_map( 'trim', explode( ',', $booking->inclusions ) ) : array();
            $sell_price      = floatval( $booking->sell_price ?? 0 );
            $paid_amount     = floatval( $booking->paid_amount ?? 0 );
            $due_amount      = isset( $booking->due_amount ) ? floatval( $booking->due_amount ) : max( 0, $sell_price - $paid_amount );
            $adults          = isset( $booking->adults_count ) ? intval( $booking->adults_count ) : 1;
            $children        = isset( $booking->child_count ) ? intval( $booking->child_count ) : 0;
            $sharing         = ! empty( $booking->room_sharing ) ? $booking->room_sharing : 'Twin Sharing';
            ?>
            <div class="ifs-panel-card">
                <div class="ifs-card-header" style="justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <span class="ifs-id-badge">#TR-<?php echo esc_html( str_pad( (string) $booking->id, 5, '0', STR_PAD_LEFT ) ); ?></span>
                        <span class="badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $booking->status ); ?></span>
                        <span class="badge <?php echo esc_attr( $pay_class ); ?>"><?php echo esc_html( $booking->payment_status ?? 'Paid' ); ?></span>
                        <h3 class="ifs-card-title"><?php echo esc_html( $booking->package_title . ' — ' . $booking->destination ); ?></h3>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button type="button" onclick="window.print();" class="ifs-btn-primary"><span class="dashicons dashicons-printer"></span> <?php esc_html_e( 'Print Official Voucher', 'ifs-travel-erp' ); ?></button>
                        <a href="<?php echo esc_url( add_query_arg( array( 'sub' => 'edit', 'id' => $booking->id ), $base_url ) ); ?>" class="ifs-btn-secondary"><span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Edit', 'ifs-travel-erp' ); ?></a>
                        <a href="<?php echo esc_url( add_query_arg( 'sub', 'list', $base_url ) ); ?>" class="ifs-btn-secondary"><span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'Back', 'ifs-travel-erp' ); ?></a>
                    </div>
                </div>

                <div class="ifs-official-voucher">
                    <div class="vcr-header-band">
                        <div class="vcr-brand-info">
                            <span class="vcr-sup-title"><?php esc_html_e( 'CONFIRMED HOLIDAY TOUR VOUCHER', 'ifs-travel-erp' ); ?></span>
                            <h1 class="vcr-hotel-title"><?php echo esc_html( $booking->package_title ); ?></h1>
                            <span class="vcr-city-line"><span class="dashicons dashicons-location-alt"></span> <?php echo esc_html( $booking->destination ); ?> &bull; <?php echo esc_html( $booking->duration ); ?></span>
                        </div>
                        <div class="vcr-meta-box">
                            <div class="vcr-meta-item">
                                <span><?php esc_html_e( 'BOOKING VOUCHER REF', 'ifs-travel-erp' ); ?></span>
                                <strong class="font-mono">#TR-<?php echo esc_html( str_pad( (string) $booking->id, 5, '0', STR_PAD_LEFT ) ); ?></strong>
                            </div>
                            <div class="vcr-meta-item">
                                <span><?php esc_html_e( 'ISSUED ON', 'ifs-travel-erp' ); ?></span>
                                <strong class="font-mono"><?php echo esc_html( date_i18n( 'd M Y', strtotime( $booking->created_at ?? current_time( 'mysql' ) ) ) ); ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="vcr-guest-strip">
                        <div class="vcr-col">
                            <span class="lbl"><?php esc_html_e( 'PRIMARY TRAVELER', 'ifs-travel-erp' ); ?></span>
                            <strong class="val uppercase"><?php echo esc_html( $booking->customer_name ?: __( 'Direct Retail Client', 'ifs-travel-erp' ) ); ?></strong>
                        </div>
                        <div class="vcr-col">
                            <span class="lbl"><?php esc_html_e( 'PASSPORT NUMBER', 'ifs-travel-erp' ); ?></span>
                            <strong class="val font-mono"><?php echo esc_html( $booking->passport_no ?: '—' ); ?></strong>
                        </div>
                        <div class="vcr-col">
                            <span class="lbl"><?php esc_html_e( 'CONTACT MOBILE', 'ifs-travel-erp' ); ?></span>
                            <strong class="val font-mono"><?php echo esc_html( $booking->mobile ?: '—' ); ?></strong>
                        </div>
                        <div class="vcr-col">
                            <span class="lbl"><?php esc_html_e( 'EMAIL ADDRESS', 'ifs-travel-erp' ); ?></span>
                            <strong class="val"><?php echo esc_html( $booking->email ?: '—' ); ?></strong>
                        </div>
                    </div>

                    <div class="vcr-stay-grid">
                        <div class="stay-box">
                            <span class="s-lbl"><span class="dashicons dashicons-calendar-alt"></span> <?php esc_html_e( 'DEPARTURE DATE', 'ifs-travel-erp' ); ?></span>
                            <strong class="s-val"><?php echo esc_html( date_i18n( 'd M Y', strtotime( $booking->travel_date ) ) ); ?></strong>
                            <span class="s-sub"><?php echo esc_html( date_i18n( 'l', strtotime( $booking->travel_date ) ) ); ?></span>
                        </div>
                        <div class="stay-box">
                            <span class="s-lbl"><span class="dashicons dashicons-calendar-alt"></span> <?php esc_html_e( 'RETURN / END DATE', 'ifs-travel-erp' ); ?></span>
                            <strong class="s-val"><?php echo ! empty( $booking->return_date ) ? esc_html( date_i18n( 'd M Y', strtotime( $booking->return_date ) ) ) : '—'; ?></strong>
                            <span class="s-sub"><?php echo ! empty( $booking->return_date ) ? esc_html( date_i18n( 'l', strtotime( $booking->return_date ) ) ) : esc_html__( 'Flexible', 'ifs-travel-erp' ); ?></span>
                        </div>
                        <div class="stay-box">
                            <span class="s-lbl"><span class="dashicons dashicons-admin-users"></span> <?php esc_html_e( 'PASSENGERS', 'ifs-travel-erp' ); ?></span>
                            <strong class="s-val"><?php echo esc_html( $adults . ' Adult' . ( $adults > 1 ? 's' : '' ) . ( $children > 0 ? ', ' . $children . ' Child' : '' ) ); ?></strong>
                            <span class="s-sub"><?php echo esc_html( $sharing ); ?></span>
                        </div>
                        <div class="stay-box">
                            <span class="s-lbl"><span class="dashicons dashicons-networking"></span> <?php esc_html_e( 'BOOKING CHANNEL', 'ifs-travel-erp' ); ?></span>
                            <strong class="s-val"><?php echo esc_html( $booking->agency_name ?: __( 'Direct Retail', 'ifs-travel-erp' ) ); ?></strong>
                            <span class="s-sub"><?php echo esc_html( $booking->supplier_name ?: __( 'In-house Tour', 'ifs-travel-erp' ) ); ?></span>
                        </div>
                    </div>

                    <?php if ( ! empty( $inclusions_list ) ) : ?>
                        <div class="vcr-inclusions-card">
                            <span class="vcr-card-heading"><?php esc_html_e( 'CONFIRMED PACKAGE INCLUSIONS & SERVICES', 'ifs-travel-erp' ); ?></span>
                            <div class="vcr-pills-wrap">
                                <?php foreach ( $inclusions_list as $inc_item ) : ?>
                                    <span class="vcr-inc-pill"><span class="dashicons dashicons-yes"></span> <?php echo esc_html( $inc_item ); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="vcr-details-table-wrap">
                        <table class="vcr-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Package Description', 'ifs-travel-erp' ); ?></th>
                                    <th><?php esc_html_e( 'Room Allocation', 'ifs-travel-erp' ); ?></th>
                                    <th style="text-align: right;"><?php esc_html_e( 'Total Fare', 'ifs-travel-erp' ); ?></th>
                                    <th style="text-align: right;"><?php esc_html_e( 'Amount Paid', 'ifs-travel-erp' ); ?></th>
                                    <th style="text-align: right;"><?php esc_html_e( 'Balance Due', 'ifs-travel-erp' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html( $booking->package_title ); ?></strong>
                                        <div style="font-size: 11px; color: #64748b;"><?php echo esc_html( $booking->destination . ' (' . $booking->duration . ')' ); ?></div>
                                    </td>
                                    <td><?php echo esc_html( $sharing ); ?></td>
                                    <td style="text-align: right; font-family: monospace; font-weight: 700;">৳<?php echo esc_html( number_format( $sell_price, 2 ) ); ?></td>
                                    <td style="text-align: right; font-family: monospace; font-weight: 700; color: #059669;">৳<?php echo esc_html( number_format( $paid_amount, 2 ) ); ?></td>
                                    <td style="text-align: right; font-family: monospace; font-weight: 800; color: <?php echo $due_amount > 0 ? '#dc2626' : '#059669'; ?>;">
                                        ৳<?php echo esc_html( number_format( $due_amount, 2 ) ); ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="vcr-settlement-strip">
                        <div>
                            <span class="s-lbl"><?php esc_html_e( 'PAYMENT METHOD', 'ifs-travel-erp' ); ?></span>
                            <strong class="val"><?php echo esc_html( $booking->payment_method ?? 'Bank Transfer' ); ?></strong>
                        </div>
                        <div>
                            <span class="s-lbl"><?php esc_html_e( 'PAYMENT STATUS', 'ifs-travel-erp' ); ?></span>
                            <strong class="val"><?php echo esc_html( $booking->payment_status ?? 'Paid' ); ?></strong>
                        </div>
                        <div style="text-align: right;">
                            <span class="s-lbl"><?php esc_html_e( 'TOTAL AMOUNT INVOICED', 'ifs-travel-erp' ); ?></span>
                            <strong class="val color-emerald" style="font-size: 17px; font-family: monospace;">৳<?php echo esc_html( number_format( $sell_price, 2 ) ); ?></strong>
                        </div>
                    </div>

                    <?php if ( ! empty( $booking->remarks ) ) : ?>
                        <div class="vcr-remarks-box">
                            <span class="vcr-card-heading"><?php esc_html_e( 'SPECIAL REQUIREMENTS / REMARKS', 'ifs-travel-erp' ); ?></span>
                            <p><?php echo wp_kses_post( nl2br( esc_html( $booking->remarks ) ) ); ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="vcr-footer-note">
                        <p><strong><?php esc_html_e( 'Terms & Guest Instructions:', 'ifs-travel-erp' ); ?></strong> <?php esc_html_e( 'Please present this official booking voucher along with passport identification upon airport reception and hotel check-in.', 'ifs-travel-erp' ); ?></p>
                        <div class="vcr-signature-strip">
                            <div class="sig-block">
                                <div class="sig-line"></div>
                                <span><?php esc_html_e( 'Passenger Acceptance Signature', 'ifs-travel-erp' ); ?></span>
                            </div>
                            <div class="sig-block">
                                <div class="sig-line"></div>
                                <span><?php esc_html_e( 'Authorized Agency Stamp & Signature', 'ifs-travel-erp' ); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
        ?>

        <!-- Stylesheet -->
        <style>
            .ifs-panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px 24px; margin-bottom: 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
            .ifs-card-header { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
            .ifs-card-title { margin: 0; font-size: 16px; font-weight: 800; color: #0f172a; }
            .ifs-id-badge { background: #f1f5f9; color: #475569; font-family: monospace; font-weight: 700; font-size: 11px; padding: 3px 8px; border-radius: 4px; }
            
            .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; }
            .badge-confirmed { background: #dcfce7; color: #15803d; }
            .badge-reserved  { background: #e0f2fe; color: #0369a1; }
            .badge-pending   { background: #fef3c7; color: #b45309; }
            .badge-cancelled { background: #fee2e2; color: #b91c1c; }

            .pay-paid    { background: #dcfce7; color: #15803d; }
            .pay-partial { background: #fef3c7; color: #b45309; }
            .pay-due     { background: #fee2e2; color: #b91c1c; }

            .ifs-tag-pill { background: #f1f5f9; color: #475569; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 4px; }
            .ifs-btn-primary { background: #003376; color: #ffffff !important; border: none; padding: 7px 14px; border-radius: 6px; font-size: 12.5px; font-weight: 700; text-decoration: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
            .ifs-btn-primary:hover { background: #002255; }
            .ifs-btn-secondary { background: #f8fafc; color: #475569 !important; border: 1px solid #cbd5e1; padding: 7px 14px; border-radius: 6px; font-size: 12.5px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; cursor: pointer; }
            .ifs-btn-secondary:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; }
            
            /* Printable Voucher Document */
            .ifs-official-voucher { background: #ffffff; border: 1px solid #cbd5e1; border-radius: 12px; padding: 34px; max-width: 900px; margin: 0 auto; color: #0f172a; box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04); }
            .vcr-header-band { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 20px; border-bottom: 2px solid #003376; margin-bottom: 20px; }
            .vcr-sup-title { font-size: 10.5px; font-weight: 800; color: #003376; letter-spacing: 1px; display: block; margin-bottom: 4px; }
            .vcr-hotel-title { margin: 0 0 6px; font-size: 24px; font-weight: 900; color: #0f172a; line-height: 1.2; }
            .vcr-city-line { font-size: 13px; color: #64748b; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
            .vcr-meta-box { text-align: right; display: flex; flex-direction: column; gap: 6px; }
            .vcr-meta-item span { font-size: 9.5px; font-weight: 700; color: #64748b; display: block; }
            .vcr-meta-item strong { font-size: 13.5px; color: #003376; }

            .vcr-guest-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 16px; margin-bottom: 20px; }
            .vcr-col .lbl { font-size: 9px; font-weight: 700; color: #64748b; display: block; margin-bottom: 3px; }
            .vcr-col .val { font-size: 13px; font-weight: 800; color: #0f172a; }
            
            .vcr-stay-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px; }
            .stay-box { border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; display: flex; flex-direction: column; gap: 2px; background: #ffffff; }
            .stay-box .s-lbl { font-size: 9.5px; font-weight: 700; color: #003376; display: flex; align-items: center; gap: 4px; margin-bottom: 3px; }
            .stay-box .s-lbl .dashicons { font-size: 14px; width: 14px; height: 14px; }
            .stay-box .s-val { font-size: 13.5px; font-weight: 800; color: #0f172a; }
            .stay-box .s-sub { font-size: 11px; color: #94a3b8; }

            .vcr-inclusions-card { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 14px 16px; margin-bottom: 20px; }
            .vcr-card-heading { font-size: 10px; font-weight: 800; color: #166534; letter-spacing: 0.6px; display: block; margin-bottom: 8px; }
            .vcr-pills-wrap { display: flex; flex-wrap: wrap; gap: 8px; }
            .vcr-inc-pill { background: #ffffff; border: 1px solid #86efac; color: #166534; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 20px; display: inline-flex; align-items: center; gap: 4px; }
            .vcr-inc-pill .dashicons { font-size: 14px; width: 14px; height: 14px; color: #16a34a; }

            .vcr-details-table-wrap { margin-bottom: 16px; }
            .vcr-table { width: 100%; border-collapse: collapse; font-size: 13px; }
            .vcr-table thead th { background: #f1f5f9; padding: 10px 14px; text-align: left; font-size: 10.5px; font-weight: 700; color: #475569; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; }
            .vcr-table tbody td { padding: 12px 14px; border-bottom: 1px solid #e2e8f0; color: #334155; }

            .vcr-settlement-strip { display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 18px; margin-bottom: 20px; }
            .vcr-settlement-strip .s-lbl { font-size: 9.5px; font-weight: 700; color: #64748b; display: block; margin-bottom: 2px; }
            .vcr-settlement-strip .val { font-size: 13.5px; font-weight: 800; color: #0f172a; }

            .vcr-remarks-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; }
            .vcr-remarks-box p { margin: 0; font-size: 12.5px; color: #475569; line-height: 1.5; }

            /* Repeaters Comparison Layout */
            .vcr-repeaters-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
            .vcr-repeat-box { border-radius: 8px; padding: 14px 16px; border: 1px solid #e2e8f0; background: #f8fafc; }
            .vcr-repeat-box.inc { background: #f0f9ff; border-color: #bae6fd; }
            .vcr-repeat-box.excl { background: #fef2f2; border-color: #fecaca; }
            .vcr-repeat-head { margin: 0 0 10px 0; font-size: 13px; font-weight: 800; display: flex; align-items: center; gap: 6px; }
            .vcr-repeat-head.inc { color: #0284c7; }
            .vcr-repeat-head.excl { color: #dc2626; }
            .vcr-check-list { margin: 0; padding-left: 0; list-style: none; display: flex; flex-direction: column; gap: 6px; }
            .vcr-check-list li { font-size: 12.5px; line-height: 1.4; display: flex; align-items: flex-start; gap: 6px; }
            .vcr-check-list.inc li { color: #0369a1; }
            .vcr-check-list.inc li::before { content: '✔'; font-weight: 800; }
            .vcr-check-list.excl li { color: #991b1b; }
            .vcr-check-list.excl li::before { content: '✖'; font-weight: 800; }

            .vcr-footer-note { font-size: 11px; color: #64748b; line-height: 1.6; border-top: 1px solid #f1f5f9; padding-top: 16px; }
            .vcr-signature-strip { display: flex; justify-content: space-between; margin-top: 45px; }
            .sig-block { text-align: center; width: 220px; }
            .sig-line { border-top: 1px dashed #94a3b8; margin-bottom: 6px; }
            .sig-block span { font-size: 10.5px; color: #475569; font-weight: 600; }

            .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
            .uppercase { text-transform: uppercase; }
            .color-emerald { color: #059669 !important; }

            @media print {
                body * { visibility: hidden; }
                .ifs-official-voucher, .ifs-official-voucher * { visibility: visible; }
                .ifs-official-voucher { position: absolute; left: 0; top: 0; width: 100%; border: 1px solid #000; box-shadow: none; padding: 20px; }
            }
        </style>
        <?php
    }
}