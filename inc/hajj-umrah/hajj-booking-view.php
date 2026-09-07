<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_hajj_booking_view_page' ) ) {
    /**
     * Enterprise Ultra-Modern Hajj & Umrah Pilgrim Dossier & Printable Itinerary Pass
     * Features: Gov PID Vault, High-End Pilgrim Pass Card, Saudi MoFA/BRN Verification, 
     * Night Split, Health Checks, Add-on Badges, Document Vault & Ledger Accounting
     */
    function ifs_terp_hajj_booking_view_page() {
        global $wpdb;
        $id       = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
        $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=hajj_umrah' );

        if ( ! $id ) {
            echo '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Invalid Pilgrim Booking ID.', 'ifs-travel-erp' ) . '</div>';
            return;
        }

        $table_bookings  = $wpdb->prefix . 'iterp_hajj_bookings';
        $table_customers = $wpdb->prefix . 'iterp_customers';
        $table_packages  = $wpdb->prefix . 'iterp_hajj_packages';
        $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
        $table_agents    = $wpdb->prefix . 'iterp_agents';

        $query = $wpdb->prepare( "
            SELECT b.*, 
                   c.title AS pilgrim_title, c.full_name AS pilgrim_name, c.mobile, c.passport_no AS cus_passport_no, c.passport_expiry AS cus_passport_expiry, c.email AS pilgrim_email, c.gender, c.blood_group, c.nationality, c.emergency_contact AS cus_emergency_contact,
                   p.package_name, p.package_type, p.total_days, p.hotel_makkah AS def_hotel_makkah, p.makkah_distance, p.hotel_madinah AS def_hotel_madinah, p.madinah_distance, p.airline_name, p.inclusions_json,
                   m.full_name AS mahram_name, m.mobile AS mahram_mobile,
                   s.supplier_name,
                   a.agency_name, a.contact_person AS agent_contact
            FROM {$table_bookings} b
            LEFT JOIN {$table_customers} c ON b.customer_id = c.id
            LEFT JOIN {$table_packages} p ON b.package_id = p.id
            LEFT JOIN {$table_customers} m ON b.mahram_customer_id = m.id
            LEFT JOIN {$table_suppliers} s ON b.supplier_id = s.id
            LEFT JOIN {$table_agents} a ON b.agent_id = a.id
            WHERE b.id = %d
        ", $id );
        
        $booking = $wpdb->get_row( $query );

        if ( ! $booking ) {
            echo '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> ' . esc_html__( 'Pilgrim booking record not found.', 'ifs-travel-erp' ) . '</div>';
            return;
        }

        // Status Badges
        $status_class = 'status-booked';
        $status_lower = strtolower( (string) $booking->status );
        if ( 'confirmed' === $status_lower ) {
            $status_class = 'status-confirmed';
        } elseif ( 'completed' === $status_lower ) {
            $status_class = 'status-completed';
        } elseif ( 'cancelled' === $status_lower ) {
            $status_class = 'status-cancelled';
        }

        // Payment Badges
        $pay_badge = 'pay-due';
        $p_lower   = strtolower( (string) ( $booking->payment_status ?? 'paid' ) );
        if ( 'paid' === $p_lower ) {
            $pay_badge = 'pay-paid';
        } elseif ( 'partial' === $p_lower ) {
            $pay_badge = 'pay-partial';
        }

        // Visa Badges
        $visa_badge = 'visa-pending';
        $v_lower    = strtolower( (string) $booking->visa_status );
        if ( 'issued' === $v_lower ) {
            $visa_badge = 'visa-issued';
        } elseif ( 'submitted' === $v_lower ) {
            $visa_badge = 'visa-submitted';
        } elseif ( 'rejected' === $v_lower ) {
            $visa_badge = 'visa-rejected';
        }

        // Pilgrim Name Formatting (Title Case)
        $title_prefix = ! empty( $booking->pilgrim_title ) ? ucfirst( strtolower( $booking->pilgrim_title ) ) . '. ' : '';
        $raw_pax_name = ! empty( $booking->pilgrim_name ) ? $booking->pilgrim_name : 'Guest Pilgrim';
        $pax_name     = $title_prefix . mb_convert_case( trim( (string) $raw_pax_name ), MB_CASE_TITLE, 'UTF-8' );

        // Mahram Formatting
        $mahram_name = ! empty( $booking->mahram_name ) ? mb_convert_case( trim( (string) $booking->mahram_name ), MB_CASE_TITLE, 'UTF-8' ) : 'None / Self / Male';
        if ( ! empty( $booking->mahram_relation ) && $mahram_name !== 'None / Self / Male' ) {
            $mahram_display = $mahram_name . ' (' . esc_html( $booking->mahram_relation ) . ')';
        } else {
            $mahram_display = $mahram_name;
        }

        // Flight & Passport Snapshots
        $passport_num = ! empty( $booking->passport_no ) ? $booking->passport_no : ( ! empty( $booking->cus_passport_no ) ? $booking->cus_passport_no : 'NOT SET' );
        $pass_expiry  = ( ! empty( $booking->passport_expiry ) && $booking->passport_expiry !== '1970-01-01' ) ? $booking->passport_expiry : ( ( ! empty( $booking->cus_passport_expiry ) && $booking->cus_passport_expiry !== '1970-01-01' ) ? $booking->cus_passport_expiry : '' );
        $airline_name = ! empty( $booking->flight_airline ) ? $booking->flight_airline : ( ! empty( $booking->airline_name ) ? $booking->airline_name : 'Biman / Saudia' );

        // Avatar Initials
        $parts   = preg_split( '/\s+/', trim( (string) $raw_pax_name ) );
        $initial = ( count( $parts ) > 1 ) ? ( mb_substr( $parts[0], 0, 1 ) . mb_substr( $parts[count( $parts ) - 1], 0, 1 ) ) : mb_substr( $raw_pax_name, 0, 2 );
        $initial = strtoupper( $initial );

        // Financial Variables
        $sell_price  = (float) ( $booking->sell_price ?? 0 );
        $buy_price   = (float) ( $booking->buy_price ?? 0 );
        $paid_amount = (float) ( $booking->paid_amount ?? 0 );
        $due_amount  = isset( $booking->due_amount ) ? (float) $booking->due_amount : max( 0, $sell_price - $paid_amount );
        $profit      = $sell_price - $buy_price;

        $hotel_mak = ! empty( $booking->hotel_makkah ) ? $booking->hotel_makkah : ( $booking->def_hotel_makkah ?: 'Standard Makkah Hotel' );
        $hotel_mad = ! empty( $booking->hotel_madinah ) ? $booking->hotel_madinah : ( $booking->def_hotel_madinah ?: 'Standard Madinah Hotel' );
        ?>

        <div class="ifs-hajj-view-workspace">
            
            <!-- Top Executive Identity & Actions Strip -->
            <div class="ifs-view-header-strip">
                <div class="ifs-header-identity">
                    <a href="<?php echo esc_url( add_query_arg( 'sub', 'list', $base_url ) ); ?>" class="ifs-back-round-btn" title="<?php esc_attr_e( 'Return to Pilgrim Ledger', 'ifs-travel-erp' ); ?>">
                        <span class="dashicons dashicons-arrow-left-alt2"></span>
                    </a>
                    <div>
                        <div class="ifs-badge-row">
                            <span class="ifs-id-pill font-mono">#HB-<?php echo esc_html( str_pad( (string) $booking->id, 5, '0', STR_PAD_LEFT ) ); ?></span>
                            <?php if ( ! empty( $booking->pilgrim_id ) ) : ?>
                                <span class="ifs-badge-pill font-mono"><span class="dashicons dashicons-tag"></span> Badge: <?php echo esc_html( $booking->pilgrim_id ); ?></span>
                            <?php endif; ?>
                            <?php if ( ! empty( $booking->tracking_id ) ) : ?>
                                <span class="ifs-pid-pill font-mono"><span class="dashicons dashicons-id"></span> PID: <?php echo esc_html( $booking->tracking_id ); ?></span>
                            <?php endif; ?>
                            <span class="ifs-pkg-pill"><?php echo esc_html( $booking->package_type ?: 'Umrah' ); ?></span>
                            <span class="ifs-status-badge <?php echo esc_attr( $visa_badge ); ?>">Visa: <?php echo esc_html( $booking->visa_status ?: 'Pending' ); ?></span>
                            <span class="ifs-status-badge <?php echo esc_attr( $pay_badge ); ?>"><?php echo esc_html( $booking->payment_status ?? 'Paid' ); ?></span>
                            <span class="ifs-status-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $booking->status ); ?></span>
                        </div>
                        <h2 class="ifs-view-name"><?php echo esc_html( $pax_name ); ?> &mdash; <?php echo esc_html( $booking->package_name ?: 'Custom Package' ); ?></h2>
                    </div>
                </div>

                <div class="ifs-header-actions">
                    <button type="button" onclick="window.print();" class="ifs-btn-print">
                        <span class="dashicons dashicons-printer"></span> <?php esc_html_e( 'Print Pilgrim Pass', 'ifs-travel-erp' ); ?>
                    </button>
                    <a href="<?php echo esc_url( add_query_arg( array( 'sub' => 'edit', 'id' => $id ), $base_url ) ); ?>" class="ifs-btn-edit">
                        <span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Edit Registration', 'ifs-travel-erp' ); ?>
                    </a>
                </div>
            </div>

            <!-- Metric KPI Ribbon -->
            <div class="ifs-dossier-metrics-grid">
                <div class="ifs-metric-box">
                    <div class="metric-icon bg-emerald"><span class="dashicons dashicons-building"></span></div>
                    <div>
                        <span class="metric-lbl"><?php esc_html_e( 'Room Sharing Plan', 'ifs-travel-erp' ); ?></span>
                        <strong class="metric-val"><?php echo esc_html( $booking->room_sharing ); ?> Room</strong>
                    </div>
                </div>

                <div class="ifs-metric-box">
                    <div class="metric-icon bg-indigo"><span class="dashicons dashicons-money-alt"></span></div>
                    <div>
                        <span class="metric-lbl"><?php esc_html_e( 'Total Package Fare', 'ifs-travel-erp' ); ?></span>
                        <strong class="metric-val color-blue">৳<?php echo esc_html( number_format( $sell_price, 2 ) ); ?></strong>
                    </div>
                </div>

                <div class="ifs-metric-box">
                    <div class="metric-icon bg-slate"><span class="dashicons dashicons-yes-alt"></span></div>
                    <div>
                        <span class="metric-lbl"><?php esc_html_e( 'Paid Amount', 'ifs-travel-erp' ); ?></span>
                        <strong class="metric-val color-emerald">৳<?php echo esc_html( number_format( $paid_amount, 2 ) ); ?></strong>
                    </div>
                </div>

                <div class="ifs-metric-box">
                    <div class="metric-icon bg-amber"><span class="dashicons dashicons-clock"></span></div>
                    <div>
                        <span class="metric-lbl"><?php esc_html_e( 'Remaining Due', 'ifs-travel-erp' ); ?></span>
                        <strong class="metric-val <?php echo ( $due_amount > 0 ) ? 'color-rose' : 'color-emerald'; ?>">৳<?php echo esc_html( number_format( $due_amount, 2 ) ); ?></strong>
                    </div>
                </div>
            </div>

            <!-- Split Grid Layout -->
            <div class="ifs-dossier-split-layout">
                
                <!-- Left Column: Printable Digital Pilgrim Pass Card & Credentials -->
                <div class="ifs-dossier-left-sidebar">
                    
                    <!-- Modern Pilgrim Pass Card Widget -->
                    <div class="ifs-pilgrim-card">
                        <div class="pilgrim-head-strip">
                            <span class="pilgrim-brand-tag"><?php echo esc_html( strtoupper( $booking->package_type ?: 'UMRAH' ) ); ?> PASS</span>
                            <span class="pilgrim-sharing-badge"><?php echo esc_html( strtoupper( $booking->room_sharing ) ); ?> ROOM</span>
                        </div>

                        <div class="pilgrim-bio-hero">
                            <div class="pilgrim-avatar"><?php echo esc_html( $initial ); ?></div>
                            <div>
                                <h4 class="pilgrim-name"><?php echo esc_html( $pax_name ); ?></h4>
                                <div class="pilgrim-submeta">PPT: <?php echo esc_html( $passport_num ); ?> &bull; BLOOD: <?php echo esc_html( $booking->blood_group ?: 'N/A' ); ?></div>
                            </div>
                        </div>

                        <div class="pilgrim-package-strip">
                            <span class="pkg-label"><?php esc_html_e( 'PACKAGE PLAN', 'ifs-travel-erp' ); ?></span>
                            <strong class="pkg-val"><?php echo esc_html( $booking->package_name ?: 'Custom Package' ); ?> (<?php echo esc_html( $booking->total_days ?? 15 ); ?> Days)</strong>
                        </div>

                        <div class="pilgrim-grid-specs font-mono">
                            <div>
                                <span class="spec-lbl"><?php esc_html_e( 'GOV PID NO', 'ifs-travel-erp' ); ?></span>
                                <strong class="spec-val color-amber"><?php echo esc_html( $booking->tracking_id ?: 'PENDING' ); ?></strong>
                            </div>
                            <div>
                                <span class="spec-lbl"><?php esc_html_e( 'SAUDI MOFA NO', 'ifs-travel-erp' ); ?></span>
                                <strong class="spec-val color-cyan"><?php echo esc_html( $booking->mofaza_no ?: 'PENDING' ); ?></strong>
                            </div>
                            <div>
                                <span class="spec-lbl"><?php esc_html_e( 'HOTEL BRN', 'ifs-travel-erp' ); ?></span>
                                <strong class="spec-val"><?php echo esc_html( $booking->brn_no ?: '------' ); ?></strong>
                            </div>
                            <div>
                                <span class="spec-lbl"><?php esc_html_e( 'FLIGHT DEPARTURE', 'ifs-travel-erp' ); ?></span>
                                <strong class="spec-val color-green"><?php echo ( $booking->flight_date !== '1970-01-01' && ! empty( $booking->flight_date ) ) ? esc_html( date_i18n( 'd M Y', strtotime( $booking->flight_date ) ) ) : esc_html__( 'TBD', 'ifs-travel-erp' ); ?></strong>
                            </div>
                        </div>

                        <div class="pilgrim-fee-footer">
                            <div class="fee-row">
                                <span><?php esc_html_e( 'TOTAL FARE:', 'ifs-travel-erp' ); ?></span>
                                <strong class="color-green font-mono">৳<?php echo esc_html( number_format( $sell_price, 2 ) ); ?></strong>
                            </div>
                            <div class="fee-row" style="font-size: 11px; opacity: 0.9;">
                                <span><?php esc_html_e( 'BALANCE DUE:', 'ifs-travel-erp' ); ?></span>
                                <strong class="color-amber font-mono">৳<?php echo esc_html( number_format( $due_amount, 2 ) ); ?></strong>
                            </div>
                            <span class="pilgrim-barcode font-mono">H&lt;BGD&lt;&lt;<?php echo esc_html( str_replace( ' ', '<', strtoupper( $raw_pax_name ) ) ); ?>&lt;&lt;MAKKAH&lt;MADINAH&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;</span>
                        </div>
                    </div>

                    <!-- Agency / Channel Issuance Meta -->
                    <div class="ifs-info-panel-card">
                        <h4 class="panel-card-title"><span class="dashicons dashicons-networking"></span> <?php esc_html_e( 'Issuing Channel & Credentials', 'ifs-travel-erp' ); ?></h4>
                        <div class="ifs-panel-table">
                            <div class="panel-row">
                                <span class="panel-key"><span class="dashicons dashicons-store"></span> <?php esc_html_e( 'Supplier:', 'ifs-travel-erp' ); ?></span>
                                <span class="panel-val"><?php echo esc_html( $booking->supplier_name ?: 'Direct Ministry Account' ); ?></span>
                            </div>
                            <div class="panel-row">
                                <span class="panel-key"><span class="dashicons dashicons-groups"></span> <?php esc_html_e( 'Sub-Agent:', 'ifs-travel-erp' ); ?></span>
                                <span class="panel-val <?php echo ! empty( $booking->agency_name ) ? 'color-indigo font-bold' : ''; ?>">
                                    <?php echo esc_html( $booking->agency_name ?: 'Direct Retail Pilgrim' ); ?>
                                </span>
                            </div>
                            <div class="panel-row">
                                <span class="panel-key"><span class="dashicons dashicons-shield-alt"></span> <?php esc_html_e( 'Nusuk Masar ID:', 'ifs-travel-erp' ); ?></span>
                                <span class="panel-val font-mono"><?php echo esc_html( $booking->nusuk_id ?: 'NOT SET' ); ?></span>
                            </div>
                            <div class="panel-row">
                                <span class="panel-key"><span class="dashicons dashicons-phone"></span> <?php esc_html_e( 'Saudi SIM:', 'ifs-travel-erp' ); ?></span>
                                <span class="panel-val font-mono"><?php echo esc_html( $booking->saudi_mobile ?: 'Not Issued' ); ?></span>
                            </div>
                            <div class="panel-row">
                                <span class="panel-key"><span class="dashicons dashicons-sos"></span> <?php esc_html_e( 'KSA Emergency:', 'ifs-travel-erp' ); ?></span>
                                <span class="panel-val font-mono text-rose"><?php echo esc_html( $booking->emergency_contact ?: 'Not Provided' ); ?></span>
                            </div>
                            <div class="panel-row">
                                <span class="panel-key"><span class="dashicons dashicons-heart"></span> <?php esc_html_e( 'ACWY Vaccine:', 'ifs-travel-erp' ); ?></span>
                                <span class="panel-val font-bold <?php echo ( ( $booking->vaccine_status ?? 'Verified' ) === 'Verified' ) ? 'color-emerald' : 'color-rose'; ?>">
                                    <?php echo esc_html( $booking->vaccine_status ?? 'Verified' ); ?>
                                </span>
                            </div>
                            <div class="panel-row">
                                <span class="panel-key"><span class="dashicons dashicons-portfolio"></span> <?php esc_html_e( 'Kit & ID Handover:', 'ifs-travel-erp' ); ?></span>
                                <span class="panel-val font-bold <?php echo ( ! empty( $booking->kit_delivered ) ) ? 'color-emerald' : 'color-rose'; ?>">
                                    <?php echo ( ! empty( $booking->kit_delivered ) ) ? esc_html__( 'Delivered', 'ifs-travel-erp' ) : esc_html__( 'Pending', 'ifs-travel-erp' ); ?>
                                </span>
                            </div>
                        </div>

                        <?php if ( ! empty( $booking->visa_doc_url ) ) : ?>
                            <div style="margin-top: 16px; padding-top: 12px; border-top: 1px dashed #e2e8f0;">
                                <a href="<?php echo esc_url( $booking->visa_doc_url ); ?>" target="_blank" rel="noopener noreferrer" class="ifs-btn-secondary" style="width: 100%; justify-content: center;">
                                    <span class="dashicons dashicons-media-document"></span> <?php esc_html_e( 'View Attached E-Visa / Card', 'ifs-travel-erp' ); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Column: Hotels, Pilgrim Manifest, Inclusions & Financials -->
                <div class="ifs-dossier-main-content">
                    
                    <!-- 1. Holy Cities Hotel Accommodations & Schedule -->
                    <div class="ifs-history-container-card">
                        <div class="ifs-history-header-nav">
                            <h3 class="history-title"><span class="dashicons dashicons-building"></span> <?php esc_html_e( 'Holy Cities Accommodations & Schedule', 'ifs-travel-erp' ); ?></h3>
                        </div>

                        <div class="ifs-specs-two-col">
                            <div class="spec-item">
                                <span class="spec-title"><span class="dashicons dashicons-location"></span> <?php esc_html_e( 'Hotel Makkah', 'ifs-travel-erp' ); ?></span>
                                <strong class="spec-data"><?php echo esc_html( $hotel_mak ); ?></strong>
                                <span class="spec-sub font-mono">
                                    <span class="dashicons dashicons-calendar-alt"></span> 
                                    <?php echo intval( $booking->nights_makkah ?? 0 ) > 0 ? esc_html( $booking->nights_makkah ) . ' Nights' : esc_html( $booking->makkah_distance ?: 'Standard Distance' ); ?>
                                </span>
                            </div>

                            <div class="spec-item">
                                <span class="spec-title"><span class="dashicons dashicons-location"></span> <?php esc_html_e( 'Hotel Madinah', 'ifs-travel-erp' ); ?></span>
                                <strong class="spec-data"><?php echo esc_html( $hotel_mad ); ?></strong>
                                <span class="spec-sub font-mono">
                                    <span class="dashicons dashicons-calendar-alt"></span> 
                                    <?php echo intval( $booking->nights_madinah ?? 0 ) > 0 ? esc_html( $booking->nights_madinah ) . ' Nights' : esc_html( $booking->madinah_distance ?: 'Standard Distance' ); ?>
                                </span>
                            </div>

                            <div class="spec-item">
                                <span class="spec-title"><span class="dashicons dashicons-airplane"></span> <?php esc_html_e( 'Flight Schedule', 'ifs-travel-erp' ); ?></span>
                                <strong class="spec-data font-mono">
                                    <?php echo ( $booking->flight_date !== '1970-01-01' && ! empty( $booking->flight_date ) ) ? esc_html( date_i18n( 'd M Y', strtotime( $booking->flight_date ) ) ) : esc_html__( 'TBD', 'ifs-travel-erp' ); ?>
                                    <?php echo ( ! empty( $booking->return_flight_date ) && $booking->return_flight_date !== '1970-01-01' ) ? ' &rarr; ' . esc_html( date_i18n( 'd M Y', strtotime( $booking->return_flight_date ) ) ) : ''; ?>
                                </strong>
                                <span class="spec-sub font-mono">
                                    Airline: <?php echo esc_html( $airline_name ); ?>
                                    <?php echo ! empty( $booking->flight_pnr ) ? ' | PNR: ' . esc_html( $booking->flight_pnr ) : ''; ?>
                                    <?php echo ! empty( $booking->return_flight_no ) ? ' | Ret No: ' . esc_html( $booking->return_flight_no ) : ''; ?>
                                </span>
                            </div>

                            <div class="spec-item">
                                <span class="spec-title"><span class="dashicons dashicons-admin-site-alt3"></span> <?php esc_html_e( 'Saudi Ministry Credentials', 'ifs-travel-erp' ); ?></span>
                                <strong class="spec-data font-mono color-blue">BRN: <?php echo esc_html( $booking->brn_no ?: 'N/A' ); ?></strong>
                                <span class="spec-sub font-mono">MoFA: <?php echo esc_html( $booking->mofaza_no ?: 'PENDING' ); ?></span>
                            </div>

                            <?php if ( ! empty( $booking->tent_zone ) ) : ?>
                                <div class="spec-item col-span-2">
                                    <span class="spec-title"><span class="dashicons dashicons-admin-home"></span> <?php esc_html_e( 'Mina Tent / Maktab & Transport', 'ifs-travel-erp' ); ?></span>
                                    <strong class="spec-data font-mono"><?php echo esc_html( $booking->tent_zone ); ?></strong>
                                    <?php if ( ! empty( $booking->transport_provider ) ) : ?>
                                        <span class="spec-sub font-mono">Transport: <?php echo esc_html( $booking->transport_provider ); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( ! empty( $booking->group_leader ) ) : ?>
                                <div class="spec-item col-span-2">
                                    <span class="spec-title"><span class="dashicons dashicons-businessman"></span> <?php esc_html_e( 'Assigned Group Leader / Guide', 'ifs-travel-erp' ); ?></span>
                                    <strong class="spec-data"><?php echo esc_html( $booking->group_leader ); ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Add-On Feature Badges -->
                        <div class="ifs-services-badges-wrap">
                            <span class="service-chip <?php echo ( ! empty( $booking->haramain_train ) ) ? 'active' : 'inactive'; ?>">
                                <span class="dashicons dashicons-car"></span> Haramain Train: <?php echo ( ! empty( $booking->haramain_train ) ) ? esc_html__( 'Included', 'ifs-travel-erp' ) : esc_html__( 'Not Included', 'ifs-travel-erp' ); ?>
                            </span>
                            <span class="service-chip <?php echo ( ! empty( $booking->ziyarah_included ) ) ? 'active' : 'inactive'; ?>">
                                <span class="dashicons dashicons-location-alt"></span> Guided Ziyarah: <?php echo ( ! empty( $booking->ziyarah_included ) ) ? esc_html__( 'Included', 'ifs-travel-erp' ) : esc_html__( 'Not Included', 'ifs-travel-erp' ); ?>
                            </span>
                            <span class="service-chip <?php echo ( ! empty( $booking->kit_delivered ) ) ? 'active' : 'inactive'; ?>">
                                <span class="dashicons dashicons-portfolio"></span> Pilgrim Kit: <?php echo ( ! empty( $booking->kit_delivered ) ) ? esc_html__( 'Delivered', 'ifs-travel-erp' ) : esc_html__( 'Pending', 'ifs-travel-erp' ); ?>
                            </span>
                        </div>
                    </div>

                    <!-- 2. Pilgrim Manifest & Mahram Mapping -->
                    <div class="ifs-history-container-card" style="margin-top: 22px;">
                        <div class="ifs-history-header-nav">
                            <h3 class="history-title"><span class="dashicons dashicons-admin-users"></span> <?php esc_html_e( 'Pilgrim Details & Mahram Profile', 'ifs-travel-erp' ); ?></h3>
                        </div>

                        <div class="ifs-passenger-dossier-card">
                            <div class="dossier-avatar"><?php echo esc_html( $initial ); ?></div>
                            <div class="dossier-info">
                                <h4 class="dossier-name">
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=customers&sub=view&id=' . $booking->customer_id ) ); ?>">
                                        <?php echo esc_html( $pax_name ); ?>
                                    </a>
                                </h4>
                                <div class="dossier-meta-grid">
                                    <div><span><?php esc_html_e( 'Mobile:', 'ifs-travel-erp' ); ?></span> <strong><a href="tel:<?php echo esc_attr( $booking->mobile ); ?>"><?php echo esc_html( $booking->mobile ?: 'N/A' ); ?></a></strong></div>
                                    <div><span><?php esc_html_e( 'Emergency:', 'ifs-travel-erp' ); ?></span> <strong class="color-rose"><?php echo esc_html( $booking->cus_emergency_contact ?: 'Not Provided' ); ?></strong></div>
                                    <div><span><?php esc_html_e( 'Passport No:', 'ifs-travel-erp' ); ?></span> <strong class="font-mono"><?php echo esc_html( $passport_num ); ?></strong></div>
                                    <div><span><?php esc_html_e( 'Passport Expiry:', 'ifs-travel-erp' ); ?></span> <strong><?php echo ( ! empty( $pass_expiry ) && $pass_expiry !== '1970-01-01' ) ? esc_html( date_i18n( 'd M, Y', strtotime( $pass_expiry ) ) ) : esc_html__( 'N/A', 'ifs-travel-erp' ); ?></strong></div>
                                    <div><span><?php esc_html_e( 'Gender / Blood:', 'ifs-travel-erp' ); ?></span> <strong><?php echo esc_html( $booking->gender ?: 'Male' ); ?>, <?php echo esc_html( $booking->blood_group ?: 'Unknown' ); ?></strong></div>
                                    <div><span><?php esc_html_e( 'Mahram Relation:', 'ifs-travel-erp' ); ?></span> <strong class="color-indigo"><?php echo esc_html( $mahram_display ); ?></strong></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Package Inclusions -->
                    <?php if ( ! empty( $booking->inclusions_json ) ) : ?>
                        <div class="ifs-history-container-card" style="margin-top: 22px;">
                            <div class="ifs-history-header-nav">
                                <h3 class="history-title"><span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Guaranteed Package Inclusions', 'ifs-travel-erp' ); ?></h3>
                            </div>
                            <div class="ifs-checklist-box-view">
                                <p class="checklist-text"><?php echo nl2br( esc_html( $booking->inclusions_json ) ); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- 4. Commercial Accounting & Settlement -->
                    <div class="ifs-history-container-card" style="margin-top: 22px;">
                        <div class="ifs-history-header-nav">
                            <h3 class="history-title"><span class="dashicons dashicons-chart-area"></span> <?php esc_html_e( 'Fare & Settlement Breakdown', 'ifs-travel-erp' ); ?></h3>
                        </div>

                        <table class="ifs-finance-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Description', 'ifs-travel-erp' ); ?></th>
                                    <th style="text-align: right;"><?php esc_html_e( 'Amount (৳)', 'ifs-travel-erp' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="highlight-row">
                                    <td><strong><?php esc_html_e( 'Cost Price (Payable to Supplier / Moallem)', 'ifs-travel-erp' ); ?></strong></td>
                                    <td style="text-align: right;" class="font-mono font-bold color-slate">৳<?php echo esc_html( number_format( $buy_price, 2 ) ); ?></td>
                                </tr>
                                <tr class="highlight-row">
                                    <td><strong><?php esc_html_e( 'Sale Price (Invoiced to Pilgrim)', 'ifs-travel-erp' ); ?></strong></td>
                                    <td style="text-align: right;" class="font-mono font-bold color-blue">৳<?php echo esc_html( number_format( $sell_price, 2 ) ); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Paid Amount', 'ifs-travel-erp' ); ?></td>
                                    <td style="text-align: right;" class="font-mono font-bold color-emerald">৳<?php echo esc_html( number_format( $paid_amount, 2 ) ); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="dashicons dashicons-warning"></span> <?php esc_html_e( 'Due Amount', 'ifs-travel-erp' ); ?></td>
                                    <td style="text-align: right;" class="font-mono font-bold <?php echo ( $due_amount > 0 ) ? 'color-rose' : 'color-emerald'; ?>">৳<?php echo esc_html( number_format( $due_amount, 2 ) ); ?></td>
                                </tr>
                                <tr class="total-row">
                                    <td><strong><?php esc_html_e( 'Net Agency Profit', 'ifs-travel-erp' ); ?></strong></td>
                                    <td style="text-align: right;" class="font-mono font-bold <?php echo ( $profit >= 0 ) ? 'color-emerald' : 'color-rose'; ?>">
                                        ৳<?php echo esc_html( number_format( $profit, 2 ) ); ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="vcr-settlement-meta">
                            <div>
                                <span class="meta-label"><?php esc_html_e( 'Pay Method:', 'ifs-travel-erp' ); ?></span>
                                <strong><?php echo esc_html( $booking->payment_method ?? 'Bank Transfer' ); ?></strong>
                            </div>
                            <?php if ( ! empty( $booking->transaction_id ) ) : ?>
                                <div>
                                    <span class="meta-label"><?php esc_html_e( 'Txn ID:', 'ifs-travel-erp' ); ?></span>
                                    <strong class="font-mono"><?php echo esc_html( $booking->transaction_id ); ?></strong>
                                </div>
                            <?php endif; ?>
                            <div>
                                <span class="meta-label"><?php esc_html_e( 'Pay Status:', 'ifs-travel-erp' ); ?></span>
                                <strong class="uppercase"><?php echo esc_html( $booking->payment_status ?? 'Paid' ); ?></strong>
                            </div>
                        </div>

                        <?php if ( ! empty( $booking->remarks ) ) : ?>
                            <div class="ifs-ticket-remarks-box">
                                <span class="remarks-title"><span class="dashicons dashicons-info"></span> <?php esc_html_e( 'Remarks & Notes:', 'ifs-travel-erp' ); ?></span>
                                <p class="remarks-body"><?php echo nl2br( esc_html( $booking->remarks ) ); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

            </div>
        </div>

        <!-- Stylesheet -->
        <style>
            .ifs-hajj-view-workspace { max-width: 1420px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; }
            
            .ifs-toast { padding: 14px 20px; border-radius: 10px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 10px; margin-bottom: 22px; }
            .ifs-toast.danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
            .ifs-toast .dashicons { font-size: 18px; width: 18px; height: 18px; }

            /* Top Navigation Header */
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
            .ifs-back-round-btn {
                width: 44px;
                height: 44px;
                border-radius: 12px;
                background: #f1f5f9;
                border: 1px solid #e2e8f0;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #334155;
                text-decoration: none;
                transition: all 0.2s ease;
            }
            .ifs-back-round-btn:hover { background: #047857; color: #ffffff; }
            .ifs-badge-row { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; flex-wrap: wrap; }
            .ifs-id-pill { font-family: ui-monospace, monospace; font-size: 11px; font-weight: 800; background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 6px; }
            .ifs-badge-pill { font-family: ui-monospace, monospace; font-size: 11px; font-weight: 800; background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px; }
            .ifs-badge-pill .dashicons { font-size: 13px; width: 13px; height: 13px; }
            .ifs-pid-pill { font-family: ui-monospace, monospace; font-size: 11px; font-weight: 800; background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px; }
            .ifs-pid-pill .dashicons { font-size: 13px; width: 13px; height: 13px; }
            .ifs-pkg-pill { font-size: 10.5px; font-weight: 800; background: #dcfce7; color: #15803d; padding: 2px 8px; border-radius: 6px; text-transform: uppercase; }
            
            .ifs-status-badge { font-size: 10px; font-weight: 800; text-transform: uppercase; padding: 2px 8px; border-radius: 6px; }
            .status-booked    { background: #fef3c7; color: #b45309; }
            .status-confirmed { background: #dcfce7; color: #15803d; }
            .status-completed { background: #e0f2fe; color: #0369a1; }
            .status-cancelled { background: #fee2e2; color: #b91c1c; }

            .pay-paid    { background: #dcfce7; color: #15803d; }
            .pay-partial { background: #fef3c7; color: #b45309; }
            .pay-due     { background: #fee2e2; color: #b91c1c; }

            .visa-pending   { background: #fef3c7; color: #b45309; }
            .visa-submitted { background: #e0f2fe; color: #0369a1; }
            .visa-issued    { background: #dcfce7; color: #15803d; }
            .visa-rejected  { background: #fee2e2; color: #b91c1c; }

            .ifs-view-name { margin: 0; font-size: 20px; font-weight: 800; color: #0f172a; letter-spacing: -0.4px; }

            .ifs-header-actions { display: flex; align-items: center; gap: 10px; }
            .ifs-btn-print {
                background: #f8fafc;
                border: 1px solid #cbd5e1;
                color: #334155 !important;
                padding: 10px 18px;
                border-radius: 8px;
                font-size: 13px;
                font-weight: 700;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                transition: all 0.2s ease;
            }
            .ifs-btn-print:hover { background: #e2e8f0; color: #0f172a; }
            .ifs-btn-secondary {
                background: #f8fafc;
                border: 1px solid #cbd5e1;
                color: #475569 !important;
                padding: 8px 14px;
                border-radius: 6px;
                font-size: 12.5px;
                font-weight: 600;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 4px;
            }
            .ifs-btn-secondary:hover { background: #f1f5f9; color: #0f172a; }
            .ifs-btn-edit {
                background: linear-gradient(135deg, #047857 0%, #059669 100%);
                color: #ffffff !important;
                padding: 10px 20px;
                border-radius: 8px;
                font-size: 13px;
                font-weight: 700;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
                transition: all 0.2s ease;
            }
            .ifs-btn-edit:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(5, 150, 105, 0.35); }

            /* KPI Metric Cards */
            .ifs-dossier-metrics-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                gap: 18px;
                margin-bottom: 24px;
            }
            .ifs-metric-box {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 16px 20px;
                display: flex;
                align-items: center;
                gap: 16px;
                box-shadow: 0 2px 8px -2px rgba(15, 23, 42, 0.03);
            }
            .metric-icon { width: 46px; height: 46px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #ffffff; flex-shrink: 0; }
            .metric-icon.bg-emerald { background: linear-gradient(135deg, #047857 0%, #059669 100%); }
            .metric-icon.bg-indigo  { background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); }
            .metric-icon.bg-slate   { background: linear-gradient(135deg, #475569 0%, #334155 100%); }
            .metric-icon.bg-amber   { background: linear-gradient(135deg, #d97706 0%, #b45309 100%); }
            .metric-icon .dashicons { font-size: 22px; width: 22px; height: 22px; }

            .metric-lbl { font-size: 11px; font-weight: 700; color: #64748b; text-transform: capitalize; letter-spacing: 0.3px; display: block; margin-bottom: 2px; }
            .metric-val { font-size: 18px; font-weight: 800; color: #0f172a; }
            .color-blue { color: #003376 !important; }
            .color-slate { color: #475569 !important; }
            .color-emerald { color: #059669 !important; }
            .color-rose { color: #e11d48 !important; }

            /* Split Screen Grid */
            .ifs-dossier-split-layout {
                display: grid;
                grid-template-columns: 390px 1fr;
                gap: 24px;
                align-items: flex-start;
            }
            @media (max-width: 1140px) { .ifs-dossier-split-layout { grid-template-columns: 1fr; } }

            /* Digital Pilgrim Pass Card */
            .ifs-pilgrim-card {
                background: linear-gradient(145deg, #064e3b 0%, #047857 60%, #059669 100%);
                border-radius: 16px;
                padding: 22px;
                color: #ffffff;
                box-shadow: 0 16px 36px -6px rgba(4, 120, 87, 0.35);
                border: 1px solid rgba(255, 255, 255, 0.15);
                position: relative;
                overflow: hidden;
                margin-bottom: 22px;
            }
            .pilgrim-head-strip { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px dashed rgba(255, 255, 255, 0.2); }
            .pilgrim-brand-tag { font-size: 11px; font-weight: 800; letter-spacing: 0.8px; color: #a7f3d0; text-transform: uppercase; }
            .pilgrim-sharing-badge { background: rgba(255, 255, 255, 0.18); backdrop-filter: blur(4px); padding: 2px 8px; border-radius: 4px; font-size: 9.5px; font-weight: 800; text-transform: uppercase; }

            .pilgrim-bio-hero { display: flex; align-items: center; gap: 14px; margin-bottom: 16px; }
            .pilgrim-avatar { width: 46px; height: 46px; border-radius: 12px; background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 15px; flex-shrink: 0; }
            .pilgrim-name { margin: 0; font-size: 14px; font-weight: 800; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 210px; }
            .pilgrim-submeta { font-size: 11px; color: #a7f3d0; margin-top: 2px; }

            .pilgrim-package-strip { background: rgba(0, 0, 0, 0.18); padding: 8px 12px; border-radius: 8px; margin-bottom: 14px; }
            .pkg-label { font-size: 8.5px; font-weight: 700; color: #6ee7b7; letter-spacing: 0.5px; display: block; margin-bottom: 2px; }
            .pkg-val { font-size: 12px; font-weight: 700; color: #ffffff; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

            .pilgrim-grid-specs { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding-bottom: 14px; margin-bottom: 12px; border-bottom: 1px dashed rgba(255, 255, 255, 0.2); }
            .spec-lbl { font-size: 8.5px; font-weight: 700; color: #a7f3d0; letter-spacing: 0.5px; display: block; margin-bottom: 2px; }
            .spec-val { font-size: 12px; font-weight: 700; color: #ffffff; display: block; }
            .color-cyan  { color: #38bdf8 !important; }
            .color-amber { color: #fde047 !important; }
            .color-green { color: #86efac !important; }

            .pilgrim-fee-footer { display: flex; flex-direction: column; gap: 5px; }
            .fee-row { display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #d1fae5; }
            .fee-row strong { font-size: 14px; }
            .pilgrim-barcode { font-size: 8px; color: #a7f3d0; letter-spacing: 1px; text-align: center; margin-top: 4px; }

            /* Left Info Cards */
            .ifs-info-panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 22px; margin-bottom: 22px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); }
            .panel-card-title { margin: 0 0 16px 0; font-size: 14px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; text-transform: capitalize; }
            .panel-card-title .dashicons { color: #047857; font-size: 18px; width: 18px; height: 18px; }
            .ifs-panel-table { display: flex; flex-direction: column; gap: 12px; }
            .panel-row { display: flex; justify-content: space-between; align-items: center; font-size: 13px; gap: 10px; }
            .panel-key { color: #64748b; display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0; }
            .panel-key .dashicons { font-size: 14px; width: 14px; height: 14px; color: #94a3b8; }
            .panel-val { font-weight: 700; color: #0f172a; text-align: right; }
            .color-indigo { color: #4f46e5 !important; }

            /* Right History Container & Tables */
            .ifs-history-container-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 26px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); }
            .ifs-history-header-nav { padding-bottom: 16px; border-bottom: 1px solid #f1f5f9; margin-bottom: 20px; }
            .history-title { margin: 0; font-size: 16px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; text-transform: capitalize; }
            .history-title .dashicons { color: #047857; font-size: 20px; width: 20px; height: 20px; }

            .ifs-specs-two-col { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; }
            .col-span-2 { grid-column: span 2; }
            @media (max-width: 640px) { .ifs-specs-two-col { grid-template-columns: 1fr; } .col-span-2 { grid-column: span 1; } }
            .spec-item { display: flex; flex-direction: column; gap: 3px; background: #f8fafc; padding: 12px 16px; border-radius: 10px; border: 1px solid #e2e8f0; }
            .spec-title { font-size: 11px; font-weight: 700; color: #64748b; text-transform: capitalize; display: flex; align-items: center; gap: 4px; }
            .spec-title .dashicons { font-size: 13px; width: 13px; height: 13px; color: #94a3b8; }
            .spec-data { font-size: 13.5px; font-weight: 800; color: #0f172a; }
            .spec-sub { font-size: 11px; color: #64748b; display: inline-flex; align-items: center; gap: 4px; margin-top: 1px; }
            .spec-sub .dashicons { font-size: 12px; width: 12px; height: 12px; color: #047857; }

            .ifs-services-badges-wrap { display: flex; gap: 10px; margin-top: 16px; flex-wrap: wrap; }
            .service-chip { font-size: 12px; font-weight: 700; padding: 5px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 5px; }
            .service-chip.active   { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
            .service-chip.inactive { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }

            /* Pilgrim Dossier Card */
            .ifs-passenger-dossier-card { display: flex; align-items: center; gap: 18px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; }
            .dossier-avatar { width: 52px; height: 52px; border-radius: 14px; background: linear-gradient(135deg, #047857 0%, #059669 100%); color: #ffffff; font-weight: 800; font-size: 17px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
            .dossier-info { flex: 1; }
            .dossier-name { margin: 0 0 8px 0; font-size: 16px; font-weight: 800; }
            .dossier-name a { color: #047857; text-decoration: none; }
            .dossier-meta-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px 16px; font-size: 12.5px; }
            .dossier-meta-grid span { color: #64748b; margin-right: 4px; }
            .dossier-meta-grid a { color: #047857; text-decoration: none; }

            /* Checklist Box */
            .ifs-checklist-box-view { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; }
            .checklist-text { margin: 0; font-size: 13px; color: #334155; line-height: 1.6; }

            /* Commercial Accounting Table */
            .ifs-finance-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 16px; }
            .ifs-finance-table thead th { background: #f8fafc; padding: 10px 14px; text-align: left; font-size: 11px; font-weight: 700; color: #64748b; text-transform: capitalize; border-bottom: 2px solid #e2e8f0; }
            .ifs-finance-table tbody td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; color: #334155; }
            .highlight-row { background: #f8fafc; }
            .total-row { background: #eff6ff; font-size: 14px; }
            .total-row td { padding: 14px; border-top: 2px solid #bfdbfe; border-bottom: none; }

            .vcr-settlement-meta { display: flex; justify-content: space-between; background: #f8fafc; padding: 12px 16px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; }
            .meta-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: capitalize; margin-right: 6px; }

            /* Remarks Box */
            .ifs-ticket-remarks-box { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px; padding: 14px 18px; }
            .remarks-title { font-size: 11.5px; font-weight: 700; text-transform: capitalize; color: #475569; display: flex; align-items: center; gap: 4px; margin-bottom: 4px; }
            .remarks-title .dashicons { font-size: 14px; width: 14px; height: 14px; color: #047857; }
            .remarks-body { margin: 0; font-size: 13px; color: #334155; line-height: 1.5; }

            .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
            .font-bold { font-weight: 700; }
            .uppercase { text-transform: uppercase; }
            .text-rose { color: #e11d48 !important; }

            /* Print Optimization */
            @media print {
                body * { visibility: hidden; }
                .ifs-pilgrim-card, .ifs-pilgrim-card * { visibility: visible; }
                .ifs-pilgrim-card {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 100%;
                    box-shadow: none;
                    border: 1px solid #000;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
            }
        </style>
        <?php
    }
}