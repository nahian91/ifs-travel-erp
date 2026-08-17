<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enterprise Ultra-Modern Hajj & Umrah Pilgrim Dossier & Printable Itinerary Pass
 * Features: High-End Pilgrim Pass Card, Saudi MoFA/BRN Verification, Hotel Proximities, Flight Schedules & Financial Margins
 */
function ifs_terp_hajj_booking_view_page() {
    global $wpdb;
    $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
    $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=hajj_umrah' );

    if ( ! $id ) {
        echo '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> Invalid Pilgrim Booking ID.</div>';
        return;
    }

    $table_bookings  = $wpdb->prefix . 'iterp_hajj_bookings';
    $table_customers = $wpdb->prefix . 'iterp_customers';
    $table_packages  = $wpdb->prefix . 'iterp_hajj_packages';
    $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
    $table_agents    = $wpdb->prefix . 'iterp_agents';

    $query = $wpdb->prepare( "
        SELECT b.*, 
               c.title AS pilgrim_title, c.full_name AS pilgrim_name, c.mobile, c.passport_no, c.passport_expiry, c.email AS pilgrim_email, c.gender, c.blood_group, c.nationality, c.emergency_contact,
               p.package_name, p.package_type, p.total_days, p.hotel_makkah, p.makkah_distance, p.hotel_madinah, p.madinah_distance, p.airline_name, p.inclusions_json,
               m.full_name AS mahram_name, m.mobile AS mahram_mobile,
               s.supplier_name,
               a.agency_name, a.contact_person AS agent_contact
        FROM $table_bookings b
        LEFT JOIN $table_customers c ON b.customer_id = c.id
        LEFT JOIN $table_packages p ON b.package_id = p.id
        LEFT JOIN $table_customers m ON b.mahram_customer_id = m.id
        LEFT JOIN $table_suppliers s ON b.supplier_id = s.id
        LEFT JOIN $table_agents a ON b.agent_id = a.id
        WHERE b.id = %d
    ", $id );
    
    $booking = $wpdb->get_row( $query );

    if ( ! $booking ) {
        echo '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> Pilgrim booking record not found.</div>';
        return;
    }

    // Lifecycle Status Badges
    $status_class = 'status-booked';
    $status_lower = strtolower( $booking->status );
    if ( $status_lower === 'confirmed' )   $status_class = 'status-confirmed';
    elseif ( $status_lower === 'completed' ) $status_class = 'status-completed';
    elseif ( $status_lower === 'cancelled' ) $status_class = 'status-cancelled';

    // Visa Badges
    $visa_badge = 'visa-pending';
    $v_lower    = strtolower( $booking->visa_status );
    if ( $v_lower === 'issued' )     $visa_badge = 'visa-issued';
    elseif ( $v_lower === 'submitted' ) $visa_badge = 'visa-submitted';
    elseif ( $v_lower === 'rejected' )  $visa_badge = 'visa-rejected';

    $title_prefix = ! empty( $booking->pilgrim_title ) ? esc_html( $booking->pilgrim_title ) . '. ' : '';
    $pax_name     = ! empty( $booking->pilgrim_name ) ? $title_prefix . esc_html( $booking->pilgrim_name ) : 'Guest Pilgrim';

    // Avatar Initials
    $parts   = explode( ' ', trim( $booking->pilgrim_name ?? '' ) );
    $initial = ( count( $parts ) > 1 ) ? ( mb_substr( $parts[0], 0, 1 ) . mb_substr( $parts[count($parts)-1], 0, 1 ) ) : mb_substr( $booking->pilgrim_name ?? 'HP', 0, 2 );
    $initial = strtoupper( $initial );
    ?>

    <div class="ifs-hajj-view-workspace">
        
        <!-- Top Executive Identity & Actions Strip -->
        <div class="ifs-view-header-strip">
            <div class="ifs-header-identity">
                <a href="<?php echo esc_url( $base_url . '&sub=list' ); ?>" class="ifs-back-round-btn" title="Return to Pilgrim Ledger">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                </a>
                <div>
                    <div class="ifs-badge-row">
                        <span class="ifs-id-pill">#HB-<?php echo str_pad( (string) $booking->id, 5, '0', STR_PAD_LEFT ); ?></span>
                        <span class="ifs-pkg-pill"><?php echo esc_html( $booking->package_type ?: 'Umrah' ); ?></span>
                        <span class="ifs-status-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $booking->status ); ?></span>
                    </div>
                    <h2 class="ifs-view-name"><?php echo $pax_name; ?> &mdash; <?php echo esc_html( $booking->package_name ?: 'Custom Package' ); ?></h2>
                </div>
            </div>

            <div class="ifs-header-actions">
                <button type="button" onclick="window.print();" class="ifs-btn-print">
                    <span class="dashicons dashicons-printer"></span> Print Pilgrim Pass
                </button>
                <a href="<?php echo esc_url( $base_url . '&sub=edit&id=' . $id ); ?>" class="ifs-btn-edit">
                    <span class="dashicons dashicons-edit"></span> Edit Booking
                </a>
            </div>
        </div>

        <!-- Metric KPI Ribbon -->
        <div class="ifs-dossier-metrics-grid">
            <div class="ifs-metric-box">
                <div class="metric-icon bg-emerald"><span class="dashicons dashicons-building"></span></div>
                <div>
                    <span class="metric-lbl">Room Sharing Plan</span>
                    <strong class="metric-val"><?php echo esc_html( $booking->room_sharing ); ?> Room</strong>
                </div>
            </div>

            <div class="ifs-metric-box">
                <div class="metric-icon bg-indigo"><span class="dashicons dashicons-money-alt"></span></div>
                <div>
                    <span class="metric-lbl">Total Package Fare</span>
                    <strong class="metric-val color-blue">৳<?php echo number_format( $booking->sell_price, 2 ); ?></strong>
                </div>
            </div>

            <div class="ifs-metric-box">
                <div class="metric-icon bg-slate"><span class="dashicons dashicons-cart"></span></div>
                <div>
                    <span class="metric-lbl">Ground Cost (Net)</span>
                    <strong class="metric-val color-slate">৳<?php echo number_format( $booking->buy_price, 2 ); ?></strong>
                </div>
            </div>

            <div class="ifs-metric-box">
                <div class="metric-icon bg-amber"><span class="dashicons dashicons-chart-line"></span></div>
                <div>
                    <span class="metric-lbl">Net Package Yield</span>
                    <strong class="metric-val <?php echo ( $booking->profit >= 0 ) ? 'color-emerald' : 'color-rose'; ?>">৳<?php echo number_format( $booking->profit, 2 ); ?></strong>
                </div>
            </div>
        </div>

        <!-- Split Grid Layout -->
        <div class="ifs-dossier-split-layout">
            
            <!-- Left Column: Printable Digital Pilgrim Pass Card -->
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
                            <h4 class="pilgrim-name"><?php echo $pax_name; ?></h4>
                            <div class="pilgrim-submeta">PPT: <?php echo esc_html( $booking->passport_no ?: 'NOT SET' ); ?> &bull; BLOOD: <?php echo esc_html( $booking->blood_group ?: 'N/A' ); ?></div>
                        </div>
                    </div>

                    <div class="pilgrim-package-strip">
                        <span class="pkg-label">PACKAGE PLAN</span>
                        <strong class="pkg-val"><?php echo esc_html( $booking->package_name ?: 'Custom Package' ); ?> (<?php echo esc_html( $booking->total_days ?? 15 ); ?> Days)</strong>
                    </div>

                    <div class="pilgrim-grid-specs font-mono">
                        <div>
                            <span class="spec-lbl">SAUDI MOFA NO</span>
                            <strong class="spec-val color-cyan"><?php echo esc_html( $booking->mofaza_no ?: 'PENDING' ); ?></strong>
                        </div>
                        <div>
                            <span class="spec-lbl">HOTEL BRN</span>
                            <strong class="spec-val"><?php echo esc_html( $booking->brn_no ?: '------' ); ?></strong>
                        </div>
                        <div>
                            <span class="spec-lbl">VISA STATUS</span>
                            <strong class="spec-val color-amber"><?php echo esc_html( strtoupper( $booking->visa_status ) ); ?></strong>
                        </div>
                        <div>
                            <span class="spec-lbl">FLIGHT DATE</span>
                            <strong class="spec-val color-green"><?php echo ( $booking->flight_date !== '1970-01-01' && ! empty( $booking->flight_date ) ) ? date( 'd M Y', strtotime( $booking->flight_date ) ) : 'TBD'; ?></strong>
                        </div>
                    </div>

                    <div class="pilgrim-fee-footer">
                        <div class="fee-row">
                            <span>TOTAL PACKAGE FARE:</span>
                            <strong class="color-green font-mono">৳<?php echo number_format( $booking->sell_price, 2 ); ?></strong>
                        </div>
                        <span class="pilgrim-barcode font-mono">H&lt;BGD&lt;&lt;<?php echo esc_html( str_replace( ' ', '<', $booking->pilgrim_name ?? 'PILGRIM' ) ); ?>&lt;&lt;MAKKAH&lt;MADINAH&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;</span>
                    </div>
                </div>

                <!-- Agency / Channel Issuance Meta -->
                <div class="ifs-info-panel-card">
                    <h4 class="panel-card-title"><span class="dashicons dashicons-networking"></span> Issuing Channel & Saudi Wholesaler</h4>
                    <div class="ifs-panel-table">
                        <div class="panel-row">
                            <span class="panel-key"><span class="dashicons dashicons-store"></span> Saudi Moallem:</span>
                            <span class="panel-val"><?php echo esc_html( $booking->supplier_name ?: 'Direct Ministry Account' ); ?></span>
                        </div>
                        <div class="panel-row">
                            <span class="panel-key"><span class="dashicons dashicons-groups"></span> B2B Sub-Agent:</span>
                            <span class="panel-val <?php echo ! empty( $booking->agency_name ) ? 'color-indigo font-bold' : ''; ?>">
                                <?php echo esc_html( $booking->agency_name ?: 'Direct Retail Pilgrim' ); ?>
                            </span>
                        </div>
                        <div class="panel-row">
                            <span class="panel-key"><span class="dashicons dashicons-shield-alt"></span> Nusuk Masar ID:</span>
                            <span class="panel-val font-mono"><?php echo esc_html( $booking->nusuk_id ?: 'NOT SET' ); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Hotels, Pilgrim Manifest, Mahram & Commercials -->
            <div class="ifs-dossier-main-content">
                
                <!-- 1. Holy Cities Hotel Accommodations -->
                <div class="ifs-history-container-card">
                    <div class="ifs-history-header-nav">
                        <h3 class="history-title"><span class="dashicons dashicons-building"></span> Makkah & Madinah Hotel Allocations</h3>
                    </div>

                    <div class="ifs-specs-two-col">
                        <div class="spec-item">
                            <span class="spec-title"><span class="dashicons dashicons-location"></span> Makkah Al-Mukarramah Hotel</span>
                            <strong class="spec-data"><?php echo esc_html( $booking->hotel_makkah ?: 'Not Specified' ); ?></strong>
                            <span class="spec-sub font-mono"><span class="dashicons dashicons-dashboard"></span> <?php echo esc_html( $booking->makkah_distance ?: 'Walking distance to Haram' ); ?></span>
                        </div>
                        <div class="spec-item">
                            <span class="spec-title"><span class="dashicons dashicons-location"></span> Madinah Al-Munawwarah Hotel</span>
                            <strong class="spec-data"><?php echo esc_html( $booking->hotel_madinah ?: 'Not Specified' ); ?></strong>
                            <span class="spec-sub font-mono"><span class="dashicons dashicons-dashboard"></span> <?php echo esc_html( $booking->madinah_distance ?: 'Central Markazia' ); ?></span>
                        </div>
                        <div class="spec-item">
                            <span class="spec-title"><span class="dashicons dashicons-airplane"></span> Flight Transit Schedule</span>
                            <strong class="spec-data font-mono"><?php echo ( $booking->flight_date !== '1970-01-01' && ! empty( $booking->flight_date ) ) ? date( 'd M Y', strtotime( $booking->flight_date ) ) : 'TBD'; ?></strong>
                            <span class="spec-sub">Carrier: <?php echo esc_html( $booking->airline_name ?: 'Biman / Saudia' ); ?></span>
                        </div>
                        <div class="spec-item">
                            <span class="spec-title"><span class="dashicons dashicons-admin-site-alt3"></span> Saudi Ministry Credentials</span>
                            <strong class="spec-data font-mono color-blue">BRN: <?php echo esc_html( $booking->brn_no ?: 'N/A' ); ?></strong>
                            <span class="spec-sub font-mono">MoFA: <?php echo esc_html( $booking->mofaza_no ?: 'PENDING' ); ?></span>
                        </div>
                    </div>
                </div>

                <!-- 2. Pilgrim Manifest & Mahram Mapping -->
                <div class="ifs-history-container-card" style="margin-top: 22px;">
                    <div class="ifs-history-header-nav">
                        <h3 class="history-title"><span class="dashicons dashicons-admin-users"></span> Pilgrim Manifest & Mahram Relations</h3>
                    </div>

                    <div class="ifs-passenger-dossier-card">
                        <div class="dossier-avatar"><?php echo esc_html( $initial ); ?></div>
                        <div class="dossier-info">
                            <h4 class="dossier-name">
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=customers&sub=view&id=' . $booking->customer_id ) ); ?>">
                                    <?php echo $pax_name; ?>
                                </a>
                            </h4>
                            <div class="dossier-meta-grid">
                                <div><span>Mobile:</span> <strong><a href="tel:<?php echo esc_attr( $booking->mobile ); ?>"><?php echo esc_html( $booking->mobile ?: 'N/A' ); ?></a></strong></div>
                                <div><span>Emergency Care:</span> <strong class="color-rose"><?php echo esc_html( $booking->emergency_contact ?: 'Not Provided' ); ?></strong></div>
                                <div><span>Passport No:</span> <strong class="font-mono"><?php echo esc_html( $booking->passport_no ?: 'NOT PROVIDED' ); ?></strong></div>
                                <div><span>Passport Expiry:</span> <strong><?php echo ( ! empty( $booking->passport_expiry ) && $booking->passport_expiry !== '1970-01-01' ) ? date( 'd M, Y', strtotime( $booking->passport_expiry ) ) : 'N/A'; ?></strong></div>
                                <div><span>Gender / Blood:</span> <strong><?php echo esc_html( $booking->gender ?: 'Male' ); ?>, <?php echo esc_html( $booking->blood_group ?: 'Unknown' ); ?></strong></div>
                                <div><span>Mahram Relation:</span> <strong class="color-indigo"><?php echo esc_html( $booking->mahram_name ?: 'None / Self / Male' ); ?></strong></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Package Inclusions -->
                <?php if ( ! empty( $booking->inclusions_json ) ) : ?>
                    <div class="ifs-history-container-card" style="margin-top: 22px;">
                        <div class="ifs-history-header-nav">
                            <h3 class="history-title"><span class="dashicons dashicons-clipboard"></span> Guaranteed Package Inclusions</h3>
                        </div>
                        <div class="ifs-checklist-box-view">
                            <p class="checklist-text"><?php echo nl2br( esc_html( $booking->inclusions_json ) ); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- 4. Commercial Accounting & Settlement -->
                <div class="ifs-history-container-card" style="margin-top: 22px;">
                    <div class="ifs-history-header-nav">
                        <h3 class="history-title"><span class="dashicons dashicons-chart-area"></span> Commercial Breakdown & Agency Settlement</h3>
                    </div>

                    <table class="ifs-finance-table">
                        <thead>
                            <tr>
                                <th>Accounting Head / Description</th>
                                <th style="text-align: right;">Amount (BDT ৳)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="highlight-row">
                                <td><strong>Supplier / Moallem Net Cost (Hotels, Transport & Visa)</strong></td>
                                <td style="text-align: right;" class="font-mono font-bold color-slate">৳<?php echo number_format( $booking->buy_price, 2 ); ?></td>
                            </tr>
                            <tr class="highlight-row">
                                <td><strong>Pilgrim Invoiced Package Selling Rate (Gross Revenue)</strong></td>
                                <td style="text-align: right;" class="font-mono font-bold color-blue">৳<?php echo number_format( $booking->sell_price, 2 ); ?></td>
                            </tr>
                            <tr class="total-row">
                                <td><strong>Net Agency Profit Yield</strong></td>
                                <td style="text-align: right;" class="font-mono font-bold <?php echo ( $booking->profit >= 0 ) ? 'color-emerald' : 'color-rose'; ?>">
                                    ৳<?php echo number_format( $booking->profit, 2 ); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <?php if ( ! empty( $booking->remarks ) ) : ?>
                        <div class="ifs-ticket-remarks-box">
                            <span class="remarks-title"><span class="dashicons dashicons-info"></span> Pilgrim Special Request & Health Notes:</span>
                            <p class="remarks-body"><?php echo nl2br( esc_html( $booking->remarks ) ); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </div>
    </div>

    <!-- Ultra High-End Stylesheet -->
    <style>
        .ifs-hajj-view-workspace { max-width: 1420px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; }
        
        .ifs-toast { padding: 14px 20px; border-radius: 10px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 10px; margin-bottom: 22px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .ifs-toast.danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
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
        .ifs-badge-row { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
        .ifs-id-pill { font-family: ui-monospace, monospace; font-size: 11px; font-weight: 800; background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 6px; }
        .ifs-pkg-pill { font-size: 10.5px; font-weight: 800; background: #dcfce7; color: #15803d; padding: 2px 8px; border-radius: 6px; text-transform: uppercase; }
        .ifs-status-badge { font-size: 10px; font-weight: 800; text-transform: uppercase; padding: 2px 8px; border-radius: 6px; }
        .status-booked    { background: #fef3c7; color: #b45309; }
        .status-confirmed { background: #dcfce7; color: #15803d; }
        .status-completed { background: #e0f2fe; color: #0369a1; }
        .status-cancelled { background: #fee2e2; color: #b91c1c; }

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

        .metric-lbl { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; display: block; margin-bottom: 2px; }
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
        .color-cyan { color: #38bdf8 !important; }
        .color-amber { color: #fde047 !important; }
        .color-green { color: #86efac !important; }

        .pilgrim-fee-footer { display: flex; flex-direction: column; gap: 6px; }
        .fee-row { display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #d1fae5; }
        .fee-row strong { font-size: 15px; }
        .pilgrim-barcode { font-size: 8px; color: #a7f3d0; letter-spacing: 1px; text-align: center; margin-top: 4px; }

        /* Left Info Cards */
        .ifs-info-panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 22px; margin-bottom: 22px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); }
        .panel-card-title { margin: 0 0 16px 0; font-size: 14px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
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
        .history-title { margin: 0; font-size: 16px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        .history-title .dashicons { color: #047857; font-size: 20px; width: 20px; height: 20px; }

        .ifs-specs-two-col { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; }
        @media (max-width: 640px) { .ifs-specs-two-col { grid-template-columns: 1fr; } }
        .spec-item { display: flex; flex-direction: column; gap: 3px; background: #f8fafc; padding: 12px 16px; border-radius: 10px; border: 1px solid #e2e8f0; }
        .spec-title { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: flex; align-items: center; gap: 4px; }
        .spec-title .dashicons { font-size: 13px; width: 13px; height: 13px; }
        .spec-data { font-size: 14px; font-weight: 800; color: #0f172a; }
        .spec-sub { font-size: 11px; color: #64748b; display: inline-flex; align-items: center; gap: 4px; margin-top: 1px; }
        .spec-sub .dashicons { font-size: 12px; width: 12px; height: 12px; color: #047857; }

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
        .ifs-finance-table thead th { background: #f8fafc; padding: 10px 14px; text-align: left; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        .ifs-finance-table tbody td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .highlight-row { background: #f8fafc; }
        .total-row { background: #eff6ff; font-size: 14px; }
        .total-row td { padding: 14px; border-top: 2px solid #bfdbfe; border-bottom: none; }

        /* Remarks Box */
        .ifs-ticket-remarks-box { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px; padding: 14px 18px; }
        .remarks-title { font-size: 11.5px; font-weight: 700; text-transform: uppercase; color: #475569; display: flex; align-items: center; gap: 4px; margin-bottom: 4px; }
        .remarks-title .dashicons { font-size: 14px; width: 14px; height: 14px; color: #047857; }
        .remarks-body { margin: 0; font-size: 13px; color: #334155; line-height: 1.5; }

        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .font-bold { font-weight: 700; }
        .uppercase { text-transform: uppercase; }

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