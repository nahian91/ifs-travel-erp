<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Enterprise Ultra-Modern Passenger Dossier & Travel History Portfolio
 * Features: High-End Bio-Card, Document Vault with Live Previews, Booking Timelines (Air, Visa, Hajj, Tours, Hotels) & Financial Ledger
 */
function ifs_terp_customer_view_page() {
    global $wpdb;
    $id       = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
    $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=customers' );

    if ( ! $id ) {
        echo '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> Invalid Traveler Profile ID.</div>';
        return;
    }

    $table_customers = $wpdb->prefix . 'iterp_customers';
    $customer        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_customers WHERE id = %d", $id ) );

    if ( ! $customer ) {
        echo '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> Passenger portfolio could not be found.</div>';
        return;
    }

    // Related Bookings & Financial Tables
    $table_tickets = $wpdb->prefix . 'iterp_tickets';
    $table_visa    = $wpdb->prefix . 'iterp_visa_applications';
    $table_hajj    = $wpdb->prefix . 'iterp_hajj_bookings';
    $table_tours   = $wpdb->prefix . 'iterp_tours';
    $table_hotels  = $wpdb->prefix . 'iterp_hotel_bookings';
    $table_invoices = $wpdb->prefix . 'iterp_invoices';

    $air_tickets  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_tickets WHERE customer_id = %d ORDER BY id DESC", $id ) );
    $visa_apps    = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_visa WHERE customer_id = %d ORDER BY id DESC", $id ) );
    $hajj_records = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_hajj WHERE customer_id = %d ORDER BY id DESC", $id ) );
    $tour_records = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_tours WHERE customer_id = %d ORDER BY id DESC", $id ) );
    $hotel_records = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_hotels WHERE customer_id = %d ORDER BY id DESC", $id ) );

    // Total Lifetime Spend & Unpaid Invoices
    $total_spend    = (float) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(net_total) FROM $table_invoices WHERE client_type = 'Customer' AND client_id = %d", $id ) );
    $total_due      = (float) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(due_amount) FROM $table_invoices WHERE client_type = 'Customer' AND client_id = %d AND due_amount > 0", $id ) );
    $total_bookings = count( $air_tickets ) + count( $visa_apps ) + count( $hajj_records ) + count( $tour_records ) + count( $hotel_records );

    // Passport Expiry Status
    $has_expiry  = ( ! empty( $customer->passport_expiry ) && $customer->passport_expiry !== '1970-01-01' );
    $badge_class = 'badge-valid';
    $badge_text  = 'Valid Document';
    $days_left   = 9999;

    if ( $has_expiry ) {
        $expiry_time = strtotime( $customer->passport_expiry );
        $today_time  = strtotime( date( 'Y-m-d' ) );
        $days_left   = ceil( ( $expiry_time - $today_time ) / ( 60 * 60 * 24 ) );

        if ( $days_left < 0 ) {
            $badge_class = 'badge-expired';
            $badge_text  = 'Expired (' . abs( $days_left ) . ' days ago)';
        } elseif ( $days_left <= 180 ) {
            $badge_class = 'badge-warning';
            $badge_text  = 'Critical: < 6 Mos (' . $days_left . ' days left)';
        } else {
            $badge_class = 'badge-valid';
            $badge_text  = 'Valid (' . date( 'd M, Y', $expiry_time ) . ')';
        }
    } else {
        $badge_class = 'badge-none';
        $badge_text  = 'No Expiry Set';
    }

    // Avatar Initials
    $parts        = explode( ' ', trim( $customer->full_name ) );
    $initial      = ( count( $parts ) > 1 ) ? ( mb_substr( $parts[0], 0, 1 ) . mb_substr( $parts[count($parts)-1], 0, 1 ) ) : mb_substr( $customer->full_name, 0, 2 );
    $initial      = strtoupper( $initial );
    $title_prefix = ! empty( $customer->title ) ? esc_html( $customer->title ) . '. ' : '';
    ?>

    <div class="ifs-view-workspace">
        
        <!-- Top Executive Identity & Navigation Strip -->
        <div class="ifs-view-header-strip">
            <div class="ifs-header-identity">
                <a href="<?php echo esc_url( $base_url . '&sub=list' ); ?>" class="ifs-back-round-btn" title="Back to Directory">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                </a>
                <div>
                    <div class="ifs-badge-row">
                        <span class="ifs-id-pill">#CUS-<?php echo str_pad( (string) $customer->id, 5, '0', STR_PAD_LEFT ); ?></span>
                        <span class="ifs-tier-pill tier-<?php echo strtolower( $customer->client_type ); ?>"><?php echo esc_html( $customer->client_type ); ?></span>
                        <span class="ifs-nation-pill"><span class="dashicons dashicons-admin-site-alt3"></span> <?php echo esc_html( $customer->nationality ?: 'Bangladeshi' ); ?></span>
                    </div>
                    <h2 class="ifs-view-name"><?php echo $title_prefix . esc_html( $customer->full_name ); ?></h2>
                </div>
            </div>

            <div class="ifs-header-actions">
                <a href="<?php echo esc_url( $base_url . '&sub=edit&id=' . $id ); ?>" class="ifs-btn-edit">
                    <span class="dashicons dashicons-edit"></span> Edit Portfolio
                </a>
                <a href="tel:<?php echo esc_attr( $customer->mobile ); ?>" class="ifs-btn-call">
                    <span class="dashicons dashicons-phone"></span> Call Client
                </a>
            </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="ifs-dossier-metrics-grid">
            <div class="ifs-metric-box">
                <div class="metric-icon bg-blue"><span class="dashicons dashicons-portfolio"></span></div>
                <div>
                    <span class="metric-lbl">Total Bookings</span>
                    <strong class="metric-val"><?php echo number_format( $total_bookings ); ?> Files</strong>
                </div>
            </div>

            <div class="ifs-metric-box">
                <div class="metric-icon bg-emerald"><span class="dashicons dashicons-money-alt"></span></div>
                <div>
                    <span class="metric-lbl">Lifetime Invoiced</span>
                    <strong class="metric-val color-emerald">৳<?php echo number_format( $total_spend, 2 ); ?></strong>
                </div>
            </div>

            <div class="ifs-metric-box">
                <div class="metric-icon bg-rose"><span class="dashicons dashicons-warning"></span></div>
                <div>
                    <span class="metric-lbl">Receivable / Due</span>
                    <strong class="metric-val <?php echo ( $total_due > 0 ) ? 'color-rose' : 'color-slate'; ?>">৳<?php echo number_format( $total_due, 2 ); ?></strong>
                </div>
            </div>

            <div class="ifs-metric-box">
                <div class="metric-icon bg-indigo"><span class="dashicons dashicons-calendar-alt"></span></div>
                <div>
                    <span class="metric-lbl">Client Since</span>
                    <strong class="metric-val font-small"><?php echo date( 'd M Y', strtotime( $customer->created_at ) ); ?></strong>
                </div>
            </div>
        </div>

        <!-- Split Grid Details Console -->
        <div class="ifs-dossier-split-layout">
            
            <!-- Left Column: Bio Card, Passport Vault & Documents -->
            <div class="ifs-dossier-left-sidebar">
                
                <!-- Digital Pass Card Widget -->
                <div class="ifs-digital-travel-card">
                    <div class="pass-head-strip">
                        <span class="pass-brand">IFS TRAVEL ERP DOSSIER</span>
                        <span class="pass-type"><?php echo strtoupper( $customer->client_type ); ?></span>
                    </div>

                    <div class="pass-bio-hero">
                        <div class="pass-avatar"><?php echo esc_html( $initial ); ?></div>
                        <div>
                            <h3 class="pass-name"><?php echo $title_prefix . esc_html( $customer->full_name ); ?></h3>
                            <span class="pass-sub"><?php echo esc_html( $customer->gender ?: 'Male' ); ?> &bull; DOB: <?php echo ( ! empty( $customer->date_of_birth ) && $customer->date_of_birth !== '1970-01-01' ) ? date( 'd M Y', strtotime( $customer->date_of_birth ) ) : 'N/A'; ?></span>
                        </div>
                    </div>

                    <div class="pass-grid-specs">
                        <div>
                            <span class="spec-lbl">PASSPORT NUMBER</span>
                            <span class="spec-val font-mono"><?php echo esc_html( $customer->passport_no ?: 'NOT SET' ); ?></span>
                        </div>
                        <div>
                            <span class="spec-lbl">DOCUMENT STATUS</span>
                            <span class="spec-val status-indicator <?php echo esc_attr( $badge_class ); ?>"><?php echo esc_html( $badge_text ); ?></span>
                        </div>
                        <div>
                            <span class="spec-lbl">NATIONAL ID (NID)</span>
                            <span class="spec-val font-mono"><?php echo esc_html( $customer->nid_no ?? 'N/A' ); ?></span>
                        </div>
                        <div>
                            <span class="spec-lbl">BLOOD GROUP</span>
                            <span class="spec-val color-red"><?php echo esc_html( $customer->blood_group ?: 'Unknown' ); ?></span>
                        </div>
                    </div>

                    <div class="pass-barcode-decor">
                        <div class="barcode-svg"></div>
                        <span class="barcode-txt font-mono">P&lt;BGD<?php echo esc_html( $customer->passport_no ?: '0000000' ); ?>&lt;&lt;<?php echo esc_html( str_replace( ' ', '<', $customer->full_name ) ); ?>&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;</span>
                    </div>
                </div>

                <!-- Verified Contact & Logistics Card -->
                <div class="ifs-info-panel-card">
                    <h4 class="panel-card-title"><span class="dashicons dashicons-location-alt"></span> Logistics & Emergency Contact</h4>
                    <div class="ifs-panel-table">
                        <div class="panel-row">
                            <span class="panel-key"><span class="dashicons dashicons-phone"></span> Primary Mobile:</span>
                            <span class="panel-val"><a href="tel:<?php echo esc_attr( $customer->mobile ); ?>" class="link-bold"><?php echo esc_html( $customer->mobile ); ?></a></span>
                        </div>
                        <div class="panel-row">
                            <span class="panel-key"><span class="dashicons dashicons-email"></span> Official Email:</span>
                            <span class="panel-val"><?php echo esc_html( $customer->email ?: 'N/A' ); ?></span>
                        </div>
                        <div class="panel-row">
                            <span class="panel-key"><span class="dashicons dashicons-sos"></span> Emergency Care:</span>
                            <span class="panel-val color-rose"><?php echo esc_html( $customer->emergency_contact ?: 'Not Provided' ); ?></span>
                        </div>
                        <div class="panel-row">
                            <span class="panel-key"><span class="dashicons dashicons-coffee"></span> Meal Choice (SSR):</span>
                            <span class="panel-val font-mono"><?php echo esc_html( $customer->meal_preference ?? 'MOML (Muslim Meal)' ); ?></span>
                        </div>
                        <div class="panel-row">
                            <span class="panel-key"><span class="dashicons dashicons-star-filled"></span> Frequent Flyer (FFN):</span>
                            <span class="panel-val font-mono"><?php echo esc_html( $customer->frequent_flyer_no ?? 'None' ); ?></span>
                        </div>
                        <div class="panel-row full-width">
                            <span class="panel-key"><span class="dashicons dashicons-admin-home"></span> Permanent Address:</span>
                            <span class="panel-val text-wrap"><?php echo nl2br( esc_html( $customer->address ?: 'No address specified' ) ); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Document Vault Card -->
                <div class="ifs-info-panel-card">
                    <h4 class="panel-card-title"><span class="dashicons dashicons-media-document"></span> Scanned Passport & Attachments</h4>
                    <?php if ( ! empty( $customer->passport_copy_url ) ) : ?>
                        <div class="ifs-attachment-preview">
                            <div class="attach-icon"><span class="dashicons dashicons-pdf"></span></div>
                            <div class="attach-meta">
                                <span class="attach-title">Scanned_Passport_Doc.pdf</span>
                                <span class="attach-link-url"><?php echo esc_html( $customer->passport_copy_url ); ?></span>
                            </div>
                            <a href="<?php echo esc_url( $customer->passport_copy_url ); ?>" target="_blank" class="ifs-btn-view-doc">
                                <span class="dashicons dashicons-external"></span> Open
                            </a>
                        </div>
                    <?php else : ?>
                        <div class="ifs-doc-empty">
                            <span class="dashicons dashicons-paperclip"></span>
                            <p>No document attachment linked yet.</p>
                            <a href="<?php echo esc_url( $base_url . '&sub=edit&id=' . $id ); ?>" class="ifs-link-upload">Upload Scanned File</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Interactive Booking History Tabs & Dossier -->
            <div class="ifs-dossier-main-content">
                <div class="ifs-history-container-card">
                    
                    <div class="ifs-history-header-nav">
                        <h3 class="history-title"><span class="dashicons dashicons-backup"></span> Travel & Booking Lifecycle Dossier</h3>
                    </div>

                    <!-- 1. Air Ticket Issuance Records -->
                    <div class="ifs-dossier-sub-section">
                        <div class="sub-section-title">
                            <span class="dashicons dashicons-airplane"></span> Flight Bookings & Issued E-Tickets (<?php echo count( $air_tickets ); ?>)
                        </div>
                        <?php if ( $air_tickets ) : ?>
                            <div class="ifs-table-responsive-box">
                                <table class="ifs-inner-table">
                                    <thead>
                                        <tr>
                                            <th>Issue Date</th>
                                            <th>PNR</th>
                                            <th>Ticket No</th>
                                            <th>Sector / Route</th>
                                            <th>Travel Date</th>
                                            <th style="text-align: right;">Fare (৳)</th>
                                            <th style="text-align: center;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ( $air_tickets as $ticket ) : ?>
                                            <tr>
                                                <td><?php echo date( 'd M Y', strtotime( $ticket->created_at ) ); ?></td>
                                                <td><span class="pnr-badge font-mono"><?php echo esc_html( $ticket->pnr ); ?></span></td>
                                                <td class="font-mono"><?php echo esc_html( $ticket->ticket_no ?: '-' ); ?></td>
                                                <td><strong><?php echo esc_html( $ticket->sector ); ?></strong></td>
                                                <td><?php echo date( 'd M Y', strtotime( $ticket->travel_date ) ); ?></td>
                                                <td style="text-align: right; font-weight: 700;">৳<?php echo number_format( (float) $ticket->sell_price, 2 ); ?></td>
                                                <td style="text-align: center;"><span class="status-pill status-<?php echo strtolower( $ticket->status ); ?>"><?php echo esc_html( $ticket->status ); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else : ?>
                            <div class="ifs-no-data-strip">No flight bookings recorded for this passenger.</div>
                        <?php endif; ?>
                    </div>

                    <!-- 2. Visa Applications -->
                    <div class="ifs-dossier-sub-section">
                        <div class="sub-section-title">
                            <span class="dashicons dashicons-id-alt"></span> Visa Applications & Stamping Status (<?php echo count( $visa_apps ); ?>)
                        </div>
                        <?php if ( $visa_apps ) : ?>
                            <div class="ifs-table-responsive-box">
                                <table class="ifs-inner-table">
                                    <thead>
                                        <tr>
                                            <th>Application Date</th>
                                            <th>Country</th>
                                            <th>Visa Category</th>
                                            <th>Embassy Ref</th>
                                            <th style="text-align: right;">Total Fee (৳)</th>
                                            <th style="text-align: center;">Visa Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ( $visa_apps as $v ) : ?>
                                            <tr>
                                                <td><?php echo date( 'd M Y', strtotime( $v->created_at ) ); ?></td>
                                                <td><strong><?php echo esc_html( $v->country ); ?></strong></td>
                                                <td><?php echo esc_html( $v->visa_type ); ?></td>
                                                <td class="font-mono"><?php echo esc_html( $v->tracking_no ?: '-' ); ?></td>
                                                <td style="text-align: right; font-weight: 700;">৳<?php echo number_format( (float) $v->sell_price, 2 ); ?></td>
                                                <td style="text-align: center;"><span class="status-pill status-<?php echo strtolower( $v->status ); ?>"><?php echo esc_html( $v->status ); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else : ?>
                            <div class="ifs-no-data-strip">No visa applications submitted.</div>
                        <?php endif; ?>
                    </div>

                    <!-- 3. Hajj & Umrah Pilgrims -->
                    <div class="ifs-dossier-sub-section">
                        <div class="sub-section-title">
                            <span class="dashicons dashicons-awards"></span> Hajj & Umrah Pilgrimage Records (<?php echo count( $hajj_records ); ?>)
                        </div>
                        <?php if ( $hajj_records ) : ?>
                            <div class="ifs-table-responsive-box">
                                <table class="ifs-inner-table">
                                    <thead>
                                        <tr>
                                            <th>Booking Date</th>
                                            <th>BRN No</th>
                                            <th>Room Sharing</th>
                                            <th>Flight Date</th>
                                            <th style="text-align: right;">Package Price (৳)</th>
                                            <th style="text-align: center;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ( $hajj_records as $h ) : ?>
                                            <tr>
                                                <td><?php echo date( 'd M Y', strtotime( $h->created_at ) ); ?></td>
                                                <td><span class="pnr-badge font-mono"><?php echo esc_html( $h->brn_no ?: 'N/A' ); ?></span></td>
                                                <td><?php echo esc_html( $h->room_sharing ); ?></td>
                                                <td><?php echo date( 'd M Y', strtotime( $h->flight_date ) ); ?></td>
                                                <td style="text-align: right; font-weight: 700;">৳<?php echo number_format( (float) $h->sell_price, 2 ); ?></td>
                                                <td style="text-align: center;"><span class="status-pill status-<?php echo strtolower( $h->status ); ?>"><?php echo esc_html( $h->status ); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else : ?>
                            <div class="ifs-no-data-strip">No Hajj or Umrah registrations found.</div>
                        <?php endif; ?>
                    </div>

                    <!-- 4. Holiday Packages & Hotels -->
                    <div class="ifs-dossier-sub-section">
                        <div class="sub-section-title">
                            <span class="dashicons dashicons-palmtree"></span> Holiday Tours & Hospitality Reservations (<?php echo count( $tour_records ) + count( $hotel_records ); ?>)
                        </div>
                        <?php if ( $tour_records || $hotel_records ) : ?>
                            <div class="ifs-table-responsive-box">
                                <table class="ifs-inner-table">
                                    <thead>
                                        <tr>
                                            <th>Service Type</th>
                                            <th>Details / Package</th>
                                            <th>Destination / Hotel</th>
                                            <th>Schedule Dates</th>
                                            <th style="text-align: right;">Price (৳)</th>
                                            <th style="text-align: center;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ( $tour_records as $tr ) : ?>
                                            <tr>
                                                <td><span class="service-pill tour">Holiday Tour</span></td>
                                                <td><strong><?php echo esc_html( $tr->package_title ); ?></strong></td>
                                                <td><?php echo esc_html( $tr->destination ); ?></td>
                                                <td><?php echo date( 'd M Y', strtotime( $tr->travel_date ) ); ?></td>
                                                <td style="text-align: right; font-weight: 700;">৳<?php echo number_format( (float) $tr->sell_price, 2 ); ?></td>
                                                <td style="text-align: center;"><span class="status-pill status-confirmed"><?php echo esc_html( $tr->status ); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php foreach ( $hotel_records as $ht ) : ?>
                                            <tr>
                                                <td><span class="service-pill hotel">Hotel Stay</span></td>
                                                <td><strong><?php echo esc_html( $ht->hotel_name ); ?></strong> (<?php echo esc_html( $ht->room_type ); ?>)</td>
                                                <td><?php echo esc_html( $ht->city ); ?></td>
                                                <td><?php echo date( 'd M', strtotime( $ht->check_in ) ) . ' - ' . date( 'd M Y', strtotime( $ht->check_out ) ); ?></td>
                                                <td style="text-align: right; font-weight: 700;">৳<?php echo number_format( (float) $ht->sell_price, 2 ); ?></td>
                                                <td style="text-align: center;"><span class="status-pill status-confirmed"><?php echo esc_html( $ht->status ); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else : ?>
                            <div class="ifs-no-data-strip">No tour packages or hotel stays booked yet.</div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <!-- Ultra High-End Stylesheet -->
    <style>
        .ifs-view-workspace { max-width: 1400px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; }
        
        /* Top Navigation Strip */
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
        .ifs-tier-pill { font-size: 10px; font-weight: 800; text-transform: uppercase; padding: 2px 8px; border-radius: 6px; }
        .tier-retail { background: #e0f2fe; color: #0369a1; }
        .tier-corporate { background: #eef2ff; color: #4338ca; }
        .tier-vip { background: #fdf4ff; color: #a21caf; }
        .ifs-nation-pill { font-size: 11px; color: #64748b; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
        .ifs-nation-pill .dashicons { font-size: 14px; width: 14px; height: 14px; }
        .ifs-view-name { margin: 0; font-size: 22px; font-weight: 800; color: #0f172a; letter-spacing: -0.4px; }
        
        .ifs-header-actions { display: flex; align-items: center; gap: 10px; }
        .ifs-btn-edit {
            background: linear-gradient(135deg, #003376 0%, #0284c7 100%);
            color: #ffffff !important;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 12px rgba(0, 51, 118, 0.2);
            transition: all 0.2s ease;
        }
        .ifs-btn-edit:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(0, 51, 118, 0.3); }
        .ifs-btn-call {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #334155 !important;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .ifs-btn-call:hover { background: #e2e8f0; color: #0f172a; }

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
        .metric-icon.bg-emerald { background: linear-gradient(135deg, #059669 0%, #047857 100%); }
        .metric-icon.bg-rose    { background: linear-gradient(135deg, #e11d48 0%, #be123c 100%); }
        .metric-icon.bg-indigo  { background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); }
        .metric-icon .dashicons { font-size: 22px; width: 22px; height: 22px; }
        .metric-lbl { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; display: block; margin-bottom: 2px; }
        .metric-val { font-size: 18px; font-weight: 800; color: #0f172a; }
        .font-small { font-size: 15px; }
        .color-emerald { color: #059669 !important; }
        .color-rose { color: #e11d48 !important; }
        .color-slate { color: #64748b !important; }

        /* Split Screen Grid */
        .ifs-dossier-split-layout {
            display: grid;
            grid-template-columns: 400px 1fr;
            gap: 24px;
            align-items: flex-start;
        }
        @media (max-width: 1120px) { .ifs-dossier-split-layout { grid-template-columns: 1fr; } }

        /* Digital Passport / Boarding Pass */
        .ifs-digital-travel-card {
            background: linear-gradient(135deg, #001e47 0%, #003376 50%, #0369a1 100%);
            border-radius: 16px;
            padding: 24px;
            color: #ffffff;
            box-shadow: 0 16px 32px -6px rgba(0, 51, 118, 0.35);
            position: relative;
            overflow: hidden;
            margin-bottom: 22px;
        }
        .pass-head-strip { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
        .pass-brand { font-size: 9.5px; font-weight: 800; letter-spacing: 1px; color: #bae6fd; }
        .pass-type { background: rgba(255, 255, 255, 0.18); backdrop-filter: blur(4px); padding: 2px 8px; border-radius: 4px; font-size: 9.5px; font-weight: 800; letter-spacing: 0.5px; }
        .pass-bio-hero { display: flex; align-items: center; gap: 14px; margin-bottom: 20px; }
        .pass-avatar { width: 50px; height: 50px; border-radius: 14px; background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 16px; flex-shrink: 0; }
        .pass-name { margin: 0; font-size: 15.5px; font-weight: 800; color: #ffffff; }
        .pass-sub { font-size: 11.5px; color: #bae6fd; margin-top: 2px; display: block; }
        .pass-grid-specs { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; padding-top: 16px; border-top: 1px dashed rgba(255, 255, 255, 0.2); margin-bottom: 18px; }
        .spec-lbl { display: block; font-size: 8.5px; font-weight: 700; color: #93c5fd; letter-spacing: 0.5px; margin-bottom: 2px; }
        .spec-val { font-size: 12px; font-weight: 700; color: #ffffff; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .status-indicator.badge-valid { color: #86efac; }
        .status-indicator.badge-warning { color: #fde047; }
        .status-indicator.badge-expired { color: #f87171; }
        .color-red { color: #fca5a5 !important; }
        .pass-barcode-decor { border-top: 1px solid rgba(255, 255, 255, 0.15); padding-top: 12px; text-align: center; }
        .barcode-svg { height: 18px; background: repeating-linear-gradient(90deg, #ffffff, #ffffff 2px, transparent 2px, transparent 4px, #ffffff 4px, #ffffff 5px, transparent 5px, transparent 8px); opacity: 0.7; margin-bottom: 4px; }
        .barcode-txt { font-size: 8.5px; color: #93c5fd; letter-spacing: 1px; }

        /* Left Info Cards */
        .ifs-info-panel-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 22px;
            margin-bottom: 22px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
        }
        .panel-card-title {
            margin: 0 0 16px 0;
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        .panel-card-title .dashicons { color: #003376; font-size: 18px; width: 18px; height: 18px; }
        .ifs-panel-table { display: flex; flex-direction: column; gap: 12px; }
        .panel-row { display: flex; justify-content: space-between; align-items: flex-start; font-size: 13px; gap: 10px; }
        .panel-row.full-width { flex-direction: column; gap: 4px; }
        .panel-key { color: #64748b; display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0; }
        .panel-key .dashicons { font-size: 14px; width: 14px; height: 14px; color: #94a3b8; }
        .panel-val { font-weight: 700; color: #0f172a; text-align: right; }
        .link-bold { color: #0284c7; text-decoration: none; }
        .text-wrap { text-align: left; font-weight: normal; color: #334155; line-height: 1.4; }

        /* Document Attachment Box */
        .ifs-attachment-preview {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .attach-icon { width: 36px; height: 36px; border-radius: 8px; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .attach-icon .dashicons { font-size: 18px; width: 18px; height: 18px; }
        .attach-meta { flex: 1; min-width: 0; }
        .attach-title { font-size: 13px; font-weight: 700; color: #0f172a; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .attach-link-url { font-size: 11px; color: #94a3b8; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ifs-btn-view-doc { background: #003376; color: #ffffff; padding: 6px 12px; border-radius: 6px; font-size: 11.5px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        
        .ifs-doc-empty { text-align: center; padding: 20px; color: #94a3b8; }
        .ifs-doc-empty .dashicons { font-size: 32px; width: 32px; height: 32px; margin-bottom: 6px; }
        .ifs-doc-empty p { margin: 0 0 8px 0; font-size: 12.5px; }
        .ifs-link-upload { font-size: 12px; font-weight: 700; color: #0284c7; text-decoration: none; }

        /* Right History Container */
        .ifs-history-container-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 26px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
        }
        .ifs-history-header-nav {
            padding-bottom: 16px;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 22px;
        }
        .history-title { margin: 0; font-size: 16px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        .history-title .dashicons { color: #003376; font-size: 20px; width: 20px; height: 20px; }

        /* Sub Sections & Inner Tables */
        .ifs-dossier-sub-section { margin-bottom: 26px; }
        .sub-section-title { font-size: 13px; font-weight: 800; text-transform: uppercase; color: #475569; letter-spacing: 0.4px; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }
        .sub-section-title .dashicons { color: #0284c7; font-size: 16px; width: 16px; height: 16px; }
        .ifs-table-responsive-box { overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 10px; }
        .ifs-inner-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
        .ifs-inner-table thead th { background: #f8fafc; padding: 10px 14px; text-align: left; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
        .ifs-inner-table tbody td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .ifs-inner-table tbody tr:last-child td { border-bottom: none; }
        .ifs-inner-table tbody tr:hover td { background: #f8fafc; }

        .pnr-badge { background: #e0f2fe; color: #003376; font-weight: 800; padding: 2px 6px; border-radius: 4px; }
        .status-pill { font-size: 10.5px; font-weight: 800; padding: 2px 7px; border-radius: 4px; text-transform: uppercase; }
        .status-issued, .status-confirmed, .status-approved { background: #dcfce7; color: #15803d; }
        .status-pending, .status-submitted { background: #fef3c7; color: #b45309; }
        .status-cancelled, .status-rejected { background: #fee2e2; color: #b91c1c; }
        .service-pill { font-size: 10.5px; font-weight: 800; padding: 2px 7px; border-radius: 4px; }
        .service-pill.tour { background: #ecfdf5; color: #047857; }
        .service-pill.hotel { background: #eef2ff; color: #4338ca; }
        .ifs-no-data-strip { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 16px; text-align: center; color: #94a3b8; font-size: 12.5px; }

        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
    </style>
    <?php
}