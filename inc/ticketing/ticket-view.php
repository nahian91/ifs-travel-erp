<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enterprise Ultra-Modern Air Ticket Dossier & Printable E-Ticket Itinerary
 * Features: High-End Aviation Pass Header, Route Breakdown, Financial Margins, Passenger Passport Meta & Print Utility
 */
function ifs_terp_ticket_view_page() {
    global $wpdb;
    $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
    $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=ticketing' );

    if ( ! $id ) {
        echo '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> Invalid Ticket ID.</div>';
        return;
    }

    $table_tickets   = $wpdb->prefix . 'iterp_tickets';
    $table_customers = $wpdb->prefix . 'iterp_customers';
    $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
    $table_agents    = $wpdb->prefix . 'iterp_agents';

    $query = $wpdb->prepare( "
        SELECT t.*, 
               c.title AS customer_title, c.full_name AS customer_name, c.mobile AS customer_mobile, c.passport_no, c.email AS customer_email, c.nationality, c.passport_expiry,
               s.supplier_name,
               a.agency_name, a.contact_person AS agent_contact
        FROM $table_tickets t
        LEFT JOIN $table_customers c ON t.customer_id = c.id
        LEFT JOIN $table_suppliers s ON t.supplier_id = s.id
        LEFT JOIN $table_agents a ON t.agent_id = a.id
        WHERE t.id = %d
    ", $id );
    
    $ticket = $wpdb->get_row( $query );

    if ( ! $ticket ) {
        echo '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> Air ticket record could not be found.</div>';
        return;
    }

    // Status Badges
    $status_class = 'status-issued';
    $status_lower = strtolower( $ticket->status );
    if ( $status_lower === 'refunded' )  $status_class = 'status-refunded';
    elseif ( $status_lower === 'void' )  $status_class = 'status-void';
    elseif ( $status_lower === 'reissued' ) $status_class = 'status-reissued';

    $title_prefix = ! empty( $ticket->customer_title ) ? esc_html( $ticket->customer_title ) . '. ' : '';
    $pax_name     = ! empty( $ticket->customer_name ) ? $title_prefix . esc_html( $ticket->customer_name ) : 'Guest Traveler';

    // Sector parsing for Route Header
    $sectors = explode( '-', $ticket->sector );
    $origin  = $sectors[0] ?? 'DAC';
    $dest    = end( $sectors ) ?? 'DXB';

    // Name initials for avatar
    $parts   = explode( ' ', trim( $ticket->customer_name ?? '' ) );
    $initial = ( count( $parts ) > 1 ) ? ( mb_substr( $parts[0], 0, 1 ) . mb_substr( $parts[count($parts)-1], 0, 1 ) ) : mb_substr( $ticket->customer_name ?? 'PAX', 0, 2 );
    $initial = strtoupper( $initial );
    ?>

    <div class="ifs-ticket-view-workspace">
        
        <!-- Top Executive Identity & Actions Strip -->
        <div class="ifs-view-header-strip">
            <div class="ifs-header-identity">
                <a href="<?php echo esc_url( $base_url . '&sub=list' ); ?>" class="ifs-back-round-btn" title="Return to Ticket Ledger">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                </a>
                <div>
                    <div class="ifs-badge-row">
                        <span class="ifs-id-pill">#TKT-<?php echo str_pad( (string) $ticket->id, 5, '0', STR_PAD_LEFT ); ?></span>
                        <span class="ifs-gds-pill"><?php echo esc_html( $ticket->gds_pcc ?: 'Sabre GDS' ); ?></span>
                        <span class="ifs-status-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $ticket->status ); ?></span>
                    </div>
                    <h2 class="ifs-view-name">PNR: <?php echo esc_html( $ticket->pnr ); ?> &mdash; <?php echo esc_html( $ticket->airline ); ?></h2>
                </div>
            </div>

            <div class="ifs-header-actions">
                <button type="button" onclick="window.print();" class="ifs-btn-print">
                    <span class="dashicons dashicons-printer"></span> Print E-Ticket Itinerary
                </button>
                <a href="<?php echo esc_url( $base_url . '&sub=edit&id=' . $id ); ?>" class="ifs-btn-edit">
                    <span class="dashicons dashicons-edit"></span> Edit Booking
                </a>
            </div>
        </div>

        <!-- Metric KPI Ribbon -->
        <div class="ifs-dossier-metrics-grid">
            <div class="ifs-metric-box">
                <div class="metric-icon bg-blue"><span class="dashicons dashicons-airplane"></span></div>
                <div>
                    <span class="metric-lbl">Routing Sector</span>
                    <strong class="metric-val font-mono"><?php echo esc_html( $ticket->sector ); ?></strong>
                </div>
            </div>

            <div class="ifs-metric-box">
                <div class="metric-icon bg-indigo"><span class="dashicons dashicons-money-alt"></span></div>
                <div>
                    <span class="metric-lbl">Client Selling Invoice</span>
                    <strong class="metric-val color-blue">৳<?php echo number_format( $ticket->sell_price, 2 ); ?></strong>
                </div>
            </div>

            <div class="ifs-metric-box">
                <div class="metric-icon bg-slate"><span class="dashicons dashicons-cart"></span></div>
                <div>
                    <span class="metric-lbl">Supplier Net Cost</span>
                    <strong class="metric-val color-slate">৳<?php echo number_format( $ticket->buy_price, 2 ); ?></strong>
                </div>
            </div>

            <div class="ifs-metric-box">
                <div class="metric-icon bg-emerald"><span class="dashicons dashicons-chart-line"></span></div>
                <div>
                    <span class="metric-lbl">Gross Margin / Profit</span>
                    <strong class="metric-val <?php echo ( $ticket->profit >= 0 ) ? 'color-emerald' : 'color-rose'; ?>">৳<?php echo number_format( $ticket->profit, 2 ); ?></strong>
                </div>
            </div>
        </div>

        <!-- Split Grid: Left Pass Widget & Right Comprehensive Detail Panels -->
        <div class="ifs-dossier-split-layout">
            
            <!-- Left Column: Printable Digital Boarding Pass -->
            <div class="ifs-dossier-left-sidebar">
                
                <!-- Digital E-Ticket Pass Card -->
                <div class="ifs-eticket-pass">
                    <div class="pass-airline-header">
                        <span class="airline-brand-name"><?php echo esc_html( strtoupper( $ticket->airline ) ); ?></span>
                        <span class="cabin-badge"><?php echo esc_html( strtoupper( $ticket->cabin_class ) ); ?></span>
                    </div>

                    <div class="pass-route-strip">
                        <div class="airport-code-box left">
                            <span class="airport-code font-mono"><?php echo esc_html( $origin ); ?></span>
                            <span class="airport-city">Origin</span>
                        </div>
                        <div class="flight-flight-indicator">
                            <span class="flight-no-tag font-mono"><?php echo esc_html( $ticket->flight_no ?: 'FLIGHT' ); ?></span>
                            <div class="plane-line"><span class="dashicons dashicons-airplane"></span></div>
                            <span class="trip-tag"><?php echo esc_html( strtoupper( $ticket->flight_type ?? 'ONE WAY' ) ); ?></span>
                        </div>
                        <div class="airport-code-box right">
                            <span class="airport-code font-mono"><?php echo esc_html( $dest ); ?></span>
                            <span class="airport-city">Destination</span>
                        </div>
                    </div>

                    <div class="pass-passenger-strip">
                        <div>
                            <span class="pass-lbl">PASSENGER NAME</span>
                            <strong class="pass-val uppercase"><?php echo $pax_name; ?></strong>
                        </div>
                        <div style="text-align: right;">
                            <span class="pass-lbl">FLIGHT DATE</span>
                            <strong class="pass-val"><?php echo date( 'd M Y', strtotime( $ticket->travel_date ) ); ?></strong>
                        </div>
                    </div>

                    <div class="pass-ticket-grid font-mono">
                        <div>
                            <span class="pass-lbl">BOOKING REF (PNR)</span>
                            <strong class="pass-val color-cyan"><?php echo esc_html( $ticket->pnr ); ?></strong>
                        </div>
                        <div>
                            <span class="pass-lbl">BAGGAGE</span>
                            <strong class="pass-val"><?php echo esc_html( $ticket->baggage ?: '20 KG' ); ?></strong>
                        </div>
                        <div>
                            <span class="pass-lbl">E-TICKET NUMBER</span>
                            <strong class="pass-val"><?php echo esc_html( $ticket->ticket_no ); ?></strong>
                        </div>
                        <div>
                            <span class="pass-lbl">TOTAL FARE</span>
                            <strong class="pass-val color-green">৳<?php echo number_format( $ticket->sell_price, 2 ); ?></strong>
                        </div>
                    </div>

                    <div class="pass-barcode-footer">
                        <div class="pass-barcode-lines"></div>
                        <span class="pass-barcode-text font-mono">M1<?php echo esc_html( str_replace( ' ', '/', $ticket->customer_name ?? 'PASSENGER' ) ); ?>  E<?php echo esc_html( $ticket->pnr ); ?> <?php echo esc_html( $ticket->sector ); ?></span>
                    </div>
                </div>

                <!-- Agency / Channel Issuance Meta -->
                <div class="ifs-info-panel-card">
                    <h4 class="panel-card-title"><span class="dashicons dashicons-networking"></span> Issuing Channel & Settlement</h4>
                    <div class="ifs-panel-table">
                        <div class="panel-row">
                            <span class="panel-key"><span class="dashicons dashicons-admin-site"></span> Issuing Portal:</span>
                            <span class="panel-val font-mono"><?php echo esc_html( $ticket->gds_pcc ?: 'Sabre GDS' ); ?></span>
                        </div>
                        <div class="panel-row">
                            <span class="panel-key"><span class="dashicons dashicons-store"></span> Supplier / Consortia:</span>
                            <span class="panel-val"><?php echo esc_html( $ticket->supplier_name ?: 'Direct IATA / BSP' ); ?></span>
                        </div>
                        <div class="panel-row">
                            <span class="panel-key"><span class="dashicons dashicons-groups"></span> B2B Sub-Agent:</span>
                            <span class="panel-val <?php echo ! empty( $ticket->agency_name ) ? 'color-indigo font-bold' : ''; ?>">
                                <?php echo esc_html( $ticket->agency_name ?: 'Direct Retail Sale' ); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Flight Routing, Passenger Manifest & Financial Breakdown -->
            <div class="ifs-dossier-main-content">
                
                <!-- 1. Flight & Routing Segment Details -->
                <div class="ifs-history-container-card">
                    <div class="ifs-history-header-nav">
                        <h3 class="history-title"><span class="dashicons dashicons-location-alt"></span> Flight Segment & Routing Specifications</h3>
                    </div>

                    <div class="ifs-specs-two-col">
                        <div class="spec-item">
                            <span class="spec-title"><span class="dashicons dashicons-airplane"></span> Operating Airline Carrier</span>
                            <strong class="spec-data"><?php echo esc_html( $ticket->airline ); ?></strong>
                        </div>
                        <div class="spec-item">
                            <span class="spec-title"><span class="dashicons dashicons-tag"></span> Flight Number</span>
                            <strong class="spec-data font-mono"><?php echo esc_html( $ticket->flight_no ?: 'Open / Unassigned' ); ?></strong>
                        </div>
                        <div class="spec-item">
                            <span class="spec-title"><span class="dashicons dashicons-randomize"></span> Sector / Route</span>
                            <strong class="spec-data font-mono color-blue"><?php echo esc_html( $ticket->sector ); ?></strong>
                        </div>
                        <div class="spec-item">
                            <span class="spec-title"><span class="dashicons dashicons-portfolio"></span> Cabin Class</span>
                            <strong class="spec-data"><?php echo esc_html( $ticket->cabin_class ); ?></strong>
                        </div>
                        <div class="spec-item">
                            <span class="spec-title"><span class="dashicons dashicons-calendar-alt"></span> Departure Travel Date</span>
                            <strong class="spec-data"><?php echo date( 'l, d F Y', strtotime( $ticket->travel_date ) ); ?></strong>
                        </div>
                        <div class="spec-item">
                            <span class="spec-title"><span class="dashicons dashicons-clock"></span> Departure Schedule</span>
                            <strong class="spec-data font-mono"><?php echo esc_html( $ticket->flight_time ?: 'Standard Schedule' ); ?></strong>
                        </div>
                        <?php if ( $ticket->flight_type === 'Round Trip' && ! empty( $ticket->return_date ) && $ticket->return_date !== '1970-01-01' ) : ?>
                            <div class="spec-item">
                                <span class="spec-title"><span class="dashicons dashicons-image-rotate"></span> Return Flight Date</span>
                                <strong class="spec-data color-emerald"><?php echo date( 'l, d F Y', strtotime( $ticket->return_date ) ); ?></strong>
                            </div>
                        <?php endif; ?>
                        <div class="spec-item">
                            <span class="spec-title"><span class="dashicons dashicons-archive"></span> Baggage Policy</span>
                            <strong class="spec-data"><?php echo esc_html( $ticket->baggage ?: '20 KG' ); ?></strong>
                        </div>
                    </div>
                </div>

                <!-- 2. Passenger Manifest & Identity -->
                <div class="ifs-history-container-card" style="margin-top: 22px;">
                    <div class="ifs-history-header-nav">
                        <h3 class="history-title"><span class="dashicons dashicons-admin-users"></span> Passenger Manifest & Contact Profile</h3>
                    </div>

                    <div class="ifs-passenger-dossier-card">
                        <div class="dossier-avatar"><?php echo esc_html( $initial ); ?></div>
                        <div class="dossier-info">
                            <h4 class="dossier-name">
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=customers&sub=view&id=' . $ticket->customer_id ) ); ?>">
                                    <?php echo $pax_name; ?>
                                </a>
                            </h4>
                            <div class="dossier-meta-grid">
                                <div><span>Mobile:</span> <strong><a href="tel:<?php echo esc_attr( $ticket->customer_mobile ); ?>"><?php echo esc_html( $ticket->customer_mobile ?: 'N/A' ); ?></a></strong></div>
                                <div><span>Email:</span> <strong><?php echo esc_html( $ticket->customer_email ?: 'N/A' ); ?></strong></div>
                                <div><span>Passport No:</span> <strong class="font-mono"><?php echo esc_html( $ticket->passport_no ?: 'NOT PROVIDED' ); ?></strong></div>
                                <div><span>Passport Expiry:</span> <strong><?php echo ( ! empty( $ticket->passport_expiry ) && $ticket->passport_expiry !== '1970-01-01' ) ? date( 'd M, Y', strtotime( $ticket->passport_expiry ) ) : 'N/A'; ?></strong></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Comprehensive Commercial & Accounting Ledger -->
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
                            <?php if ( ! empty( $ticket->base_fare ) && $ticket->base_fare > 0 ) : ?>
                                <tr>
                                    <td><span class="dashicons dashicons-arrow-right-alt2"></span> Airline Base Airfare</td>
                                    <td style="text-align: right;" class="font-mono">৳<?php echo number_format( $ticket->base_fare, 2 ); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ( ! empty( $ticket->tax_amount ) && $ticket->tax_amount > 0 ) : ?>
                                <tr>
                                    <td><span class="dashicons dashicons-arrow-right-alt2"></span> Government Taxes, Fuel & Airport Surcharges</td>
                                    <td style="text-align: right;" class="font-mono">৳<?php echo number_format( $ticket->tax_amount, 2 ); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr class="highlight-row">
                                <td><strong>Supplier Net Cost Rate (Payable to Consortia / GDS)</strong></td>
                                <td style="text-align: right;" class="font-mono font-bold color-slate">৳<?php echo number_format( $ticket->buy_price, 2 ); ?></td>
                            </tr>
                            <tr class="highlight-row">
                                <td><strong>Client / Passenger Invoiced Amount (Gross Revenue)</strong></td>
                                <td style="text-align: right;" class="font-mono font-bold color-blue">৳<?php echo number_format( $ticket->sell_price, 2 ); ?></td>
                            </tr>
                            <tr class="total-row">
                                <td><strong>Net Agency Commission & Profit Margin</strong></td>
                                <td style="text-align: right;" class="font-mono font-bold <?php echo ( $ticket->profit >= 0 ) ? 'color-emerald' : 'color-rose'; ?>">
                                    ৳<?php echo number_format( $ticket->profit, 2 ); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <?php if ( ! empty( $ticket->remarks ) ) : ?>
                        <div class="ifs-ticket-remarks-box">
                            <span class="remarks-title"><span class="dashicons dashicons-info"></span> Operational Remarks & SSR Notes:</span>
                            <p class="remarks-body"><?php echo nl2br( esc_html( $ticket->remarks ) ); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </div>
    </div>

    <!-- Ultra High-End Dossier Stylesheet -->
    <style>
        .ifs-ticket-view-workspace { max-width: 1420px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; }
        
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
        .ifs-back-round-btn:hover { background: #003376; color: #ffffff; }
        .ifs-badge-row { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
        .ifs-id-pill { font-family: ui-monospace, monospace; font-size: 11px; font-weight: 800; background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 6px; }
        .ifs-gds-pill { font-size: 10px; font-weight: 800; background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 6px; text-transform: uppercase; }
        .ifs-status-badge { font-size: 10px; font-weight: 800; text-transform: uppercase; padding: 2px 8px; border-radius: 6px; }
        .status-issued   { background: #dcfce7; color: #15803d; }
        .status-reissued { background: #e0f2fe; color: #0369a1; }
        .status-refunded { background: #ffedd5; color: #9a3412; }
        .status-void     { background: #fee2e2; color: #b91c1c; }

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
            background: linear-gradient(135deg, #003376 0%, #0284c7 100%);
            color: #ffffff !important;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 12px rgba(0, 51, 118, 0.2);
            transition: all 0.2s ease;
        }
        .ifs-btn-edit:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(0, 51, 118, 0.3); }

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
        .metric-icon.bg-blue    { background: linear-gradient(135deg, #003376 0%, #0284c7 100%); }
        .metric-icon.bg-indigo  { background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); }
        .metric-icon.bg-slate   { background: linear-gradient(135deg, #475569 0%, #334155 100%); }
        .metric-icon.bg-emerald { background: linear-gradient(135deg, #059669 0%, #047857 100%); }
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

        /* Digital E-Ticket Pass */
        .ifs-eticket-pass {
            background: linear-gradient(145deg, #001e47 0%, #003376 60%, #0284c7 100%);
            border-radius: 16px;
            padding: 22px;
            color: #ffffff;
            box-shadow: 0 16px 36px -6px rgba(0, 51, 118, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.15);
            position: relative;
            overflow: hidden;
            margin-bottom: 22px;
        }
        .pass-airline-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; padding-bottom: 12px; border-bottom: 1px dashed rgba(255, 255, 255, 0.2); }
        .airline-brand-name { font-size: 12px; font-weight: 800; letter-spacing: 0.8px; color: #bae6fd; text-transform: uppercase; }
        .cabin-badge { background: rgba(255, 255, 255, 0.18); backdrop-filter: blur(4px); padding: 2px 8px; border-radius: 4px; font-size: 9.5px; font-weight: 800; }

        .pass-route-strip { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
        .airport-code { font-size: 26px; font-weight: 900; letter-spacing: -0.5px; color: #ffffff; display: block; }
        .airport-city { font-size: 10px; color: #93c5fd; text-transform: uppercase; font-weight: 600; }
        .flight-flight-indicator { text-align: center; }
        .flight-no-tag { font-size: 10.5px; font-weight: 700; color: #bae6fd; display: block; }
        .plane-line { position: relative; margin: 3px 0; color: #38bdf8; }
        .trip-tag { font-size: 8.5px; background: rgba(255, 255, 255, 0.12); padding: 1px 6px; border-radius: 3px; }

        .pass-passenger-strip { display: flex; justify-content: space-between; align-items: center; background: rgba(0, 0, 0, 0.18); padding: 10px 14px; border-radius: 8px; margin-bottom: 14px; }
        .pass-lbl { font-size: 8.5px; font-weight: 700; color: #93c5fd; letter-spacing: 0.5px; display: block; margin-bottom: 2px; text-transform: uppercase; }
        .pass-val { font-size: 12px; font-weight: 700; color: #ffffff; display: block; }
        .color-cyan { color: #38bdf8 !important; }
        .color-green { color: #86efac !important; }

        .pass-ticket-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding-bottom: 14px; margin-bottom: 12px; border-bottom: 1px dashed rgba(255, 255, 255, 0.2); }
        .pass-barcode-footer { text-align: center; padding-top: 4px; }
        .pass-barcode-lines { height: 18px; background: repeating-linear-gradient(90deg, #ffffff, #ffffff 2px, transparent 2px, transparent 4px, #ffffff 4px, #ffffff 5px, transparent 5px, transparent 8px); opacity: 0.75; margin-bottom: 4px; }
        .pass-barcode-text { font-size: 8px; color: #93c5fd; letter-spacing: 1px; }

        /* Left Info Cards */
        .ifs-info-panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 22px; margin-bottom: 22px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); }
        .panel-card-title { margin: 0 0 16px 0; font-size: 14px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
        .panel-card-title .dashicons { color: #003376; font-size: 18px; width: 18px; height: 18px; }
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
        .history-title .dashicons { color: #003376; font-size: 20px; width: 20px; height: 20px; }

        .ifs-specs-two-col { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; }
        @media (max-width: 640px) { .ifs-specs-two-col { grid-template-columns: 1fr; } }
        .spec-item { display: flex; flex-direction: column; gap: 3px; background: #f8fafc; padding: 12px 16px; border-radius: 10px; border: 1px solid #e2e8f0; }
        .spec-title { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: flex; align-items: center; gap: 4px; }
        .spec-title .dashicons { font-size: 13px; width: 13px; height: 13px; }
        .spec-data { font-size: 14px; font-weight: 800; color: #0f172a; }

        /* Passenger Dossier Card */
        .ifs-passenger-dossier-card { display: flex; align-items: center; gap: 18px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; }
        .dossier-avatar { width: 52px; height: 52px; border-radius: 14px; background: linear-gradient(135deg, #003376 0%, #0284c7 100%); color: #ffffff; font-weight: 800; font-size: 17px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .dossier-info { flex: 1; }
        .dossier-name { margin: 0 0 8px 0; font-size: 16px; font-weight: 800; }
        .dossier-name a { color: #003376; text-decoration: none; }
        .dossier-meta-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px 16px; font-size: 12.5px; }
        .dossier-meta-grid span { color: #64748b; margin-right: 4px; }
        .dossier-meta-grid a { color: #0284c7; text-decoration: none; }

        /* Commercial Accounting Table */
        .ifs-finance-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 16px; }
        .ifs-finance-table thead th { background: #f8fafc; padding: 10px 14px; text-align: left; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        .ifs-finance-table tbody td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .ifs-finance-table tbody td .dashicons { font-size: 13px; width: 13px; height: 13px; color: #94a3b8; }
        .highlight-row { background: #f8fafc; }
        .total-row { background: #eff6ff; font-size: 14px; }
        .total-row td { padding: 14px; border-top: 2px solid #bfdbfe; border-bottom: none; }

        /* Remarks Box */
        .ifs-ticket-remarks-box { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px; padding: 14px 18px; }
        .remarks-title { font-size: 11.5px; font-weight: 700; text-transform: uppercase; color: #475569; display: flex; align-items: center; gap: 4px; margin-bottom: 4px; }
        .remarks-title .dashicons { font-size: 14px; width: 14px; height: 14px; color: #0284c7; }
        .remarks-body { margin: 0; font-size: 13px; color: #334155; line-height: 1.5; }

        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .font-bold { font-weight: 700; }
        .uppercase { text-transform: uppercase; }

        /* Print Optimization */
        @media print {
            body * { visibility: hidden; }
            .ifs-eticket-pass, .ifs-eticket-pass * { visibility: visible; }
            .ifs-eticket-pass {
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