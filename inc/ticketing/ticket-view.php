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
    $id       = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
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
               c.title AS customer_title, c.full_name AS customer_name, c.mobile AS customer_mobile, c.passport_no AS customer_passport, c.email AS customer_email, c.nationality, c.passport_expiry,
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
    if ( $status_lower === 'refunded' )     $status_class = 'status-refunded';
    elseif ( $status_lower === 'void' )     $status_class = 'status-void';
    elseif ( $status_lower === 'reissued' ) $status_class = 'status-reissued';

    $title_prefix = ! empty( $ticket->customer_title ) ? esc_html( $ticket->customer_title ) . '. ' : '';
    $pax_name     = ! empty( $ticket->passenger_name ) ? esc_html( $ticket->passenger_name ) : ( ! empty( $ticket->customer_name ) ? $title_prefix . esc_html( $ticket->customer_name ) : 'Guest Traveler' );
    $passport_num = ! empty( $ticket->passport_no ) ? esc_html( $ticket->passport_no ) : ( ! empty( $ticket->customer_passport ) ? esc_html( $ticket->customer_passport ) : 'NOT PROVIDED' );

    // Sector parsing for Route Header
    $sectors = explode( '-', $ticket->sector );
    $origin  = $sectors[0] ?? 'DAC';
    $dest    = end( $sectors ) ?? 'DXB';

    // Name initials for avatar
    $parts   = explode( ' ', trim( $pax_name ) );
    $initial = ( count( $parts ) > 1 ) ? ( mb_substr( $parts[0], 0, 1 ) . mb_substr( $parts[count($parts)-1], 0, 1 ) ) : mb_substr( $pax_name, 0, 2 );
    $initial = strtoupper( $initial );

    // Payment badge styling
    $pay_status = $ticket->payment_status ?? 'Paid';
    $pay_class  = ( $pay_status === 'Paid' ) ? 'pay-paid' : ( ( $pay_status === 'Partial' ) ? 'pay-partial' : 'pay-due' );
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
                        <span class="ifs-pay-pill <?php echo esc_attr( $pay_class ); ?>"><?php echo esc_html( $pay_status ); ?></span>
                        <span class="ifs-status-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $ticket->status ); ?></span>
                    </div>
                    <h2 class="ifs-view-name">PNR: <?php echo esc_html( $ticket->pnr ); ?><?php echo ! empty( $ticket->airline_pnr ) ? ' / ' . esc_html( $ticket->airline_pnr ) : ''; ?> &mdash; <?php echo esc_html( $ticket->airline ); ?></h2>
                </div>
            </div>

            <div class="ifs-header-actions">
                <button type="button" onclick="window.print();" class="ifs-btn-print">
                    <span class="dashicons dashicons-printer"></span> Print Itinerary
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
                    <strong class="metric-val color-blue">৳<?php echo number_format( (float) $ticket->sell_price, 2 ); ?></strong>
                </div>
            </div>

            <div class="ifs-metric-box">
                <div class="metric-icon bg-slate"><span class="dashicons dashicons-cart"></span></div>
                <div>
                    <span class="metric-lbl">Supplier Net Cost</span>
                    <strong class="metric-val color-slate">৳<?php echo number_format( (float) $ticket->buy_price, 2 ); ?></strong>
                </div>
            </div>

            <div class="ifs-metric-box">
                <div class="metric-icon bg-emerald"><span class="dashicons dashicons-chart-line"></span></div>
                <div>
                    <span class="metric-lbl">Gross Margin / Profit</span>
                    <strong class="metric-val <?php echo ( $ticket->profit >= 0 ) ? 'color-emerald' : 'color-rose'; ?>">৳<?php echo number_format( (float) $ticket->profit, 2 ); ?></strong>
                </div>
            </div>
        </div>

        <!-- Split Grid: Left Pass Widget & Right Comprehensive Detail Panels -->
        <div class="ifs-dossier-split-layout">
            
            <!-- Left Column: Printable Digital Boarding Pass -->
            <div class="ifs-dossier-left-sidebar">
                
                <!-- Ultra-Luxury Boarding Pass Card -->
                <div class="ifs-lux-boarding-pass">
                    <div class="pass-top-band">
                        <div class="pass-carrier-wrap">
                            <div class="carrier-tail-icon"><span class="dashicons dashicons-airplane"></span></div>
                            <div>
                                <span class="carrier-name"><?php echo esc_html( strtoupper( $ticket->airline ) ); ?></span>
                                <span class="flight-no-badge font-mono"><?php echo esc_html( $ticket->flight_no ?: 'FLIGHT' ); ?></span>
                            </div>
                        </div>
                        <div class="cabin-class-pill"><?php echo esc_html( strtoupper( $ticket->cabin_class ) ); ?></div>
                    </div>

                    <div class="pass-route-arc-box">
                        <div class="route-station origin">
                            <span class="station-code font-mono"><?php echo esc_html( $origin ); ?></span>
                            <span class="station-city">Origin</span>
                        </div>
                        <div class="route-arc-visual">
                            <div class="arc-line-dotted"></div>
                            <div class="plane-flight-symbol"><span class="dashicons dashicons-airplane"></span></div>
                            <span class="trip-tag-pill"><?php echo esc_html( strtoupper( $ticket->flight_type ?? 'ONE WAY' ) ); ?></span>
                        </div>
                        <div class="route-station dest">
                            <span class="station-code font-mono"><?php echo esc_html( $dest ); ?></span>
                            <span class="station-city">Destination</span>
                        </div>
                    </div>

                    <div class="pass-perforation-divider">
                        <div class="perf-hole left"></div>
                        <div class="perf-line"></div>
                        <div class="perf-hole right"></div>
                    </div>

                    <div class="pass-pax-hero">
                        <div class="pax-meta-cell">
                            <span class="pax-label">PASSENGER NAME</span>
                            <strong class="pax-name-val uppercase"><?php echo $pax_name; ?></strong>
                        </div>
                        <div class="pax-meta-cell text-right">
                            <span class="pax-label">DEPARTURE DATE</span>
                            <strong class="pax-date-val font-mono"><?php echo date( 'd M Y', strtotime( $ticket->travel_date ) ); ?></strong>
                        </div>
                    </div>

                    <div class="pass-specs-grid font-mono">
                        <div class="spec-cell">
                            <span class="spec-label">GDS / AIRLINE PNR</span>
                            <strong class="spec-value color-cyan"><?php echo esc_html( $ticket->pnr ); ?><?php echo ! empty( $ticket->airline_pnr ) ? ' / ' . esc_html( $ticket->airline_pnr ) : ''; ?></strong>
                        </div>
                        <div class="spec-cell">
                            <span class="spec-label">BAGGAGE</span>
                            <strong class="spec-value"><?php echo esc_html( $ticket->baggage ?: '20 KG' ); ?></strong>
                        </div>
                        <div class="spec-cell">
                            <span class="spec-label">E-TICKET NO</span>
                            <strong class="spec-value"><?php echo esc_html( $ticket->ticket_no ); ?></strong>
                        </div>
                        <div class="spec-cell">
                            <span class="spec-label">TOTAL FARE</span>
                            <strong class="spec-value color-green">৳<?php echo number_format( (float) $ticket->sell_price, 2 ); ?></strong>
                        </div>
                    </div>

                    <div class="pass-barcode-area">
                        <div class="barcode-matrix-lines"></div>
                        <span class="barcode-code-text font-mono">M1<?php echo esc_html( str_replace( ' ', '/', $pax_name ) ); ?>  E<?php echo esc_html( $ticket->pnr ); ?> <?php echo esc_html( $ticket->sector ); ?></span>
                    </div>
                </div>

                <!-- Issuing Channel & Settlement Card -->
                <div class="ifs-info-panel-card">
                    <h4 class="panel-card-title"><span class="dashicons dashicons-networking"></span> Issuing Channel &amp; Settlement</h4>
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
                        <div class="panel-row">
                            <span class="panel-key"><span class="dashicons dashicons-money-alt"></span> Payment Status:</span>
                            <span class="panel-val"><span class="ifs-pay-pill <?php echo esc_attr( $pay_class ); ?>"><?php echo esc_html( $pay_status ); ?></span></span>
                        </div>
                        <div class="panel-row">
                            <span class="panel-key"><span class="dashicons dashicons-vault"></span> Payment Method:</span>
                            <span class="panel-val"><?php echo esc_html( $ticket->payment_method ?? 'Bank Transfer' ); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Document Attachment Box -->
                <?php if ( ! empty( $ticket->ticket_copy_url ) ) : ?>
                    <div class="ifs-info-panel-card">
                        <h4 class="panel-card-title"><span class="dashicons dashicons-pdf"></span> Attached E-Ticket PDF</h4>
                        <div class="ifs-attachment-preview">
                            <div class="attach-icon"><span class="dashicons dashicons-pdf"></span></div>
                            <div class="attach-meta">
                                <span class="attach-title">eTicket_<?php echo esc_html( $ticket->pnr ); ?>.pdf</span>
                                <span class="attach-link-url"><?php echo esc_html( $ticket->ticket_copy_url ); ?></span>
                            </div>
                            <a href="<?php echo esc_url( $ticket->ticket_copy_url ); ?>" target="_blank" class="ifs-btn-view-doc">
                                <span class="dashicons dashicons-external"></span> Open
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

            <!-- Right Column: Flight Routing, Passenger Manifest & Financial Breakdown -->
            <div class="ifs-dossier-main-content">
                
                <!-- 1. Flight & Routing Segment Details -->
                <div class="ifs-history-container-card">
                    <div class="ifs-history-header-nav">
                        <h3 class="history-title"><span class="dashicons dashicons-location-alt"></span> Flight Segment &amp; Routing Specifications</h3>
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
                            <span class="spec-title"><span class="dashicons dashicons-admin-site"></span> Transit / Via Stops</span>
                            <strong class="spec-data"><?php echo esc_html( $ticket->via_transit ?? 'Direct' ); ?></strong>
                        </div>
                        <div class="spec-item">
                            <span class="spec-title"><span class="dashicons dashicons-portfolio"></span> Cabin Class</span>
                            <strong class="spec-data"><?php echo esc_html( $ticket->cabin_class ); ?></strong>
                        </div>
                        <div class="spec-item">
                            <span class="spec-title"><span class="dashicons dashicons-media-code"></span> Fare Basis Code</span>
                            <strong class="spec-data font-mono"><?php echo esc_html( $ticket->fare_basis ?: 'Standard Fare' ); ?></strong>
                        </div>
                        <div class="spec-item">
                            <span class="spec-title"><span class="dashicons-calendar-alt dashicons"></span> Departure Travel Date</span>
                            <strong class="spec-data"><?php echo date( 'l, d F Y', strtotime( $ticket->travel_date ) ); ?></strong>
                        </div>
                        <div class="spec-item">
                            <span class="spec-title"><span class="dashicons dashicons-clock"></span> Departure Schedule</span>
                            <strong class="spec-data font-mono"><?php echo esc_html( $ticket->flight_time ?: 'Standard Schedule' ); ?></strong>
                        </div>
                        <?php if ( $ticket->flight_type === 'Round Trip' && ! empty( $ticket->return_date ) && $ticket->return_date !== '1970-01-01' ) : ?>
                            <div class="spec-item">
                                <span class="spec-title"><span class="dashicons dashicons-image-rotate"></span> Return Flight Date</span>
                                <strong class="spec-data color-emerald">
                                    <?php echo date( 'l, d F Y', strtotime( $ticket->return_date ) ); ?>
                                    <?php echo ! empty( $ticket->return_flight_time ) ? ' (' . esc_html( $ticket->return_flight_time ) . ')' : ''; ?>
                                </strong>
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
                        <h3 class="history-title"><span class="dashicons dashicons-admin-users"></span> Passenger Manifest &amp; Contact Profile</h3>
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
                                <div><span>Passport No:</span> <strong class="font-mono"><?php echo $passport_num; ?></strong></div>
                                <div><span>Passport Expiry:</span> <strong><?php echo ( ! empty( $ticket->passport_expiry ) && $ticket->passport_expiry !== '1970-01-01' ) ? date( 'd M, Y', strtotime( $ticket->passport_expiry ) ) : 'N/A'; ?></strong></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Comprehensive Commercial & Accounting Ledger -->
                <div class="ifs-history-container-card" style="margin-top: 22px;">
                    <div class="ifs-history-header-nav">
                        <h3 class="history-title"><span class="dashicons dashicons-chart-area"></span> Commercial Breakdown &amp; Agency Settlement</h3>
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
                                    <td style="text-align: right;" class="font-mono">৳<?php echo number_format( (float) $ticket->base_fare, 2 ); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ( ! empty( $ticket->tax_amount ) && $ticket->tax_amount > 0 ) : ?>
                                <tr>
                                    <td><span class="dashicons dashicons-arrow-right-alt2"></span> Government Taxes, Fuel &amp; Airport Surcharges</td>
                                    <td style="text-align: right;" class="font-mono">৳<?php echo number_format( (float) $ticket->tax_amount, 2 ); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ( ! empty( $ticket->commission_amount ) && $ticket->commission_amount > 0 ) : ?>
                                <tr>
                                    <td><span class="dashicons dashicons-arrow-right-alt2"></span> Agency Commission / Incentive Earned (+)</td>
                                    <td style="text-align: right; color: #16a34a;" class="font-mono font-bold">+৳<?php echo number_format( (float) $ticket->commission_amount, 2 ); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ( ! empty( $ticket->ait_amount ) && $ticket->ait_amount > 0 ) : ?>
                                <tr>
                                    <td><span class="dashicons dashicons-arrow-right-alt2"></span> AIT 0.3% Source Tax Deduction (-)</td>
                                    <td style="text-align: right; color: #dc2626;" class="font-mono font-bold">-৳<?php echo number_format( (float) $ticket->ait_amount, 2 ); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ( ! empty( $ticket->discount_amount ) && $ticket->discount_amount > 0 ) : ?>
                                <tr>
                                    <td><span class="dashicons dashicons-arrow-right-alt2"></span> Client Discount / Rebate Allowed (-)</td>
                                    <td style="text-align: right; color: #dc2626;" class="font-mono font-bold">-৳<?php echo number_format( (float) $ticket->discount_amount, 2 ); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr class="highlight-row">
                                <td><strong>Supplier Net Cost Rate (Payable to Consortia / GDS)</strong></td>
                                <td style="text-align: right;" class="font-mono font-bold color-slate">৳<?php echo number_format( (float) $ticket->buy_price, 2 ); ?></td>
                            </tr>
                            <tr class="highlight-row">
                                <td><strong>Client / Passenger Invoiced Amount (Gross Revenue)</strong></td>
                                <td style="text-align: right;" class="font-mono font-bold color-blue">৳<?php echo number_format( (float) $ticket->sell_price, 2 ); ?></td>
                            </tr>
                            <tr class="total-row">
                                <td><strong>Net Agency Profit Yield (After Commission, AIT &amp; Discount)</strong></td>
                                <td style="text-align: right;" class="font-mono font-bold <?php echo ( $ticket->profit >= 0 ) ? 'color-emerald' : 'color-rose'; ?>">
                                    ৳<?php echo number_format( (float) $ticket->profit, 2 ); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <?php if ( ! empty( $ticket->remarks ) ) : ?>
                        <div class="ifs-ticket-remarks-box">
                            <span class="remarks-title"><span class="dashicons dashicons-info"></span> Operational Remarks &amp; SSR Notes:</span>
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
            border-radius: 16px;
            padding: 22px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04);
            margin-bottom: 24px;
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
        .ifs-badge-row { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; flex-wrap: wrap; }
        .ifs-id-pill { font-family: ui-monospace, monospace; font-size: 11px; font-weight: 800; background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 6px; border: 1px solid #e2e8f0; }
        .ifs-gds-pill { font-size: 10px; font-weight: 800; background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 6px; text-transform: uppercase; }
        
        .ifs-pay-pill { font-size: 10px; font-weight: 800; text-transform: uppercase; padding: 2px 8px; border-radius: 6px; }
        .pay-paid    { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .pay-partial { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .pay-due     { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

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
            border-radius: 14px;
            padding: 18px 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 8px -2px rgba(15, 23, 42, 0.03);
        }
        .metric-icon { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #ffffff; flex-shrink: 0; }
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
            grid-template-columns: 410px 1fr;
            gap: 24px;
            align-items: flex-start;
        }
        @media (max-width: 1180px) { .ifs-dossier-split-layout { grid-template-columns: 1fr; } }

        /* Luxury Boarding Pass Widget */
        .ifs-lux-boarding-pass {
            background: radial-gradient(circle at 100% 0%, #0369a1 0%, #002b66 50%, #001738 100%);
            border-radius: 18px;
            padding: 22px;
            color: #ffffff;
            box-shadow: 0 20px 40px -8px rgba(0, 51, 118, 0.45);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.12);
            margin-bottom: 22px;
        }
        .pass-top-band { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .pass-carrier-wrap { display: flex; align-items: center; gap: 10px; }
        .carrier-tail-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7dd3fc;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .carrier-tail-icon .dashicons { font-size: 18px; width: 18px; height: 18px; }
        .carrier-name { display: block; font-size: 12px; font-weight: 800; letter-spacing: 0.8px; color: #ffffff; line-height: 1.2; }
        .flight-no-badge { font-size: 10px; color: #7dd3fc; font-weight: 700; }
        .cabin-class-pill { background: rgba(255, 255, 255, 0.18); backdrop-filter: blur(6px); padding: 3px 10px; border-radius: 6px; font-size: 9.5px; font-weight: 800; letter-spacing: 0.6px; border: 1px solid rgba(255, 255, 255, 0.2); }

        .pass-route-arc-box { display: flex; justify-content: space-between; align-items: center; padding: 10px 0 16px 0; }
        .route-station { display: flex; flex-direction: column; }
        .route-station.dest { text-align: right; }
        .station-code { font-size: 28px; font-weight: 900; letter-spacing: -0.5px; color: #ffffff; line-height: 1; }
        .station-city { font-size: 10px; color: #93c5fd; text-transform: uppercase; font-weight: 600; margin-top: 3px; }
        .route-arc-visual { display: flex; flex-direction: column; align-items: center; position: relative; width: 140px; }
        .arc-line-dotted { width: 100%; height: 2px; border-top: 2px dashed rgba(56, 189, 248, 0.5); position: absolute; top: 10px; z-index: 1; }
        .plane-flight-symbol { width: 22px; height: 22px; border-radius: 50%; background: #0284c7; display: flex; align-items: center; justify-content: center; position: relative; z-index: 2; box-shadow: 0 0 10px #38bdf8; color: #ffffff; }
        .plane-flight-symbol .dashicons { font-size: 12px; width: 12px; height: 12px; }
        .trip-tag-pill { font-size: 8.5px; font-weight: 800; background: rgba(0, 0, 0, 0.3); padding: 2px 7px; border-radius: 10px; margin-top: 8px; letter-spacing: 0.5px; border: 1px solid rgba(255, 255, 255, 0.1); }

        .pass-perforation-divider { position: relative; margin: 6px -22px 16px -22px; display: flex; align-items: center; }
        .perf-hole { width: 18px; height: 18px; background: #f8fafc; border-radius: 50%; position: absolute; z-index: 3; }
        .perf-hole.left { left: -9px; }
        .perf-hole.right { right: -9px; }
        .perf-line { width: 100%; height: 1px; border-top: 1px dashed rgba(255, 255, 255, 0.25); }

        .pass-pax-hero { display: flex; justify-content: space-between; align-items: center; background: rgba(0, 0, 0, 0.2); padding: 10px 14px; border-radius: 10px; margin-bottom: 14px; border: 1px solid rgba(255, 255, 255, 0.08); }
        .pax-meta-cell { display: flex; flex-direction: column; gap: 2px; }
        .pax-meta-cell.text-right { text-align: right; }
        .pax-label { font-size: 8px; font-weight: 800; color: #7dd3fc; letter-spacing: 0.6px; }
        .pax-name-val { font-size: 12.5px; font-weight: 800; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 190px; }
        .pax-date-val { font-size: 11px; font-weight: 700; color: #ffffff; }

        .pass-specs-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 14px; padding-bottom: 14px; margin-bottom: 14px; border-bottom: 1px dashed rgba(255, 255, 255, 0.2); }
        .spec-cell { display: flex; flex-direction: column; gap: 2px; }
        .spec-label { font-size: 8px; font-weight: 800; color: #7dd3fc; letter-spacing: 0.6px; }
        .spec-value { font-size: 11px; font-weight: 700; color: #ffffff; }
        .color-cyan { color: #38bdf8 !important; }
        .color-green { color: #86efac !important; }

        .pass-barcode-area { text-align: center; }
        .barcode-matrix-lines { height: 22px; background: repeating-linear-gradient(90deg, #ffffff, #ffffff 2px, transparent 2px, transparent 4px, #ffffff 4px, #ffffff 5px, transparent 5px, transparent 8px, #ffffff 8px, #ffffff 10px, transparent 10px, transparent 12px); opacity: 0.85; margin-bottom: 5px; border-radius: 2px; }
        .barcode-code-text { font-size: 8px; color: #93c5fd; letter-spacing: 1.5px; }

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

        /* Document Attachment Box */
        .ifs-attachment-preview { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; display: flex; align-items: center; gap: 12px; }
        .attach-icon { width: 36px; height: 36px; border-radius: 8px; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .attach-icon .dashicons { font-size: 18px; width: 18px; height: 18px; }
        .attach-meta { flex: 1; min-width: 0; }
        .attach-title { font-size: 13px; font-weight: 700; color: #0f172a; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .attach-link-url { font-size: 11px; color: #94a3b8; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ifs-btn-view-doc { background: #003376; color: #ffffff !important; padding: 6px 12px; border-radius: 6px; font-size: 11.5px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }

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
            .ifs-lux-boarding-pass, .ifs-lux-boarding-pass * { visibility: visible; }
            .ifs-lux-boarding-pass {
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