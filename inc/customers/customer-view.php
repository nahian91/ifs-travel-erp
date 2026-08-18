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
    $table_tickets  = $wpdb->prefix . 'iterp_tickets';
    $table_visa     = $wpdb->prefix . 'iterp_visa_applications';
    $table_hajj     = $wpdb->prefix . 'iterp_hajj_bookings';
    $table_tours    = $wpdb->prefix . 'iterp_tours';
    $table_hotels   = $wpdb->prefix . 'iterp_hotel_bookings';
    $table_invoices = $wpdb->prefix . 'iterp_invoices';

    $air_tickets   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_tickets WHERE customer_id = %d ORDER BY id DESC", $id ) );
    $visa_apps     = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_visa WHERE customer_id = %d ORDER BY id DESC", $id ) );
    $hajj_records  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_hajj WHERE customer_id = %d ORDER BY id DESC", $id ) );
    $tour_records  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_tours WHERE customer_id = %d ORDER BY id DESC", $id ) );
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

    // Avatar Initials & Full Name
    $parts        = explode( ' ', trim( $customer->full_name ) );
    $initial      = ( count( $parts ) > 1 ) ? ( mb_substr( $parts[0], 0, 1 ) . mb_substr( $parts[count($parts)-1], 0, 1 ) ) : mb_substr( $customer->full_name, 0, 2 );
    $initial      = strtoupper( $initial );
    $title_prefix = ! empty( $customer->title ) ? esc_html( $customer->title ) . '. ' : '';

    // Passenger Type Code
    $ptype_code = 'ADT';
    if ( ! empty( $customer->passenger_type ) ) {
        if ( $customer->passenger_type === 'Child' ) {
            $ptype_code = 'CHD';
        } elseif ( $customer->passenger_type === 'Infant' ) {
            $ptype_code = 'INF';
        }
    }
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
                        <span class="ifs-tier-pill tier-<?php echo strtolower( $customer->client_type ?: 'retail' ); ?>"><?php echo esc_html( $customer->client_type ?: 'Retail' ); ?></span>
                        <span class="ifs-type-pill"><?php echo esc_html( $customer->passenger_type ?: 'Adult' ); ?></span>
                        <span class="ifs-nation-pill"><span class="dashicons dashicons-admin-site-alt3"></span> <?php echo esc_html( $customer->nationality ?: 'Bangladeshi' ); ?></span>
                    </div>
                    <h2 class="ifs-view-name"><?php echo $title_prefix . esc_html( $customer->full_name ); ?></h2>
                </div>
            </div>

            <div class="ifs-header-actions">
                <a href="<?php echo esc_url( $base_url . '&sub=edit&id=' . $id ); ?>" class="ifs-btn-edit">
                    <span class="dashicons dashicons-edit"></span> Edit Portfolio
                </a>
                <?php if ( ! empty( $customer->whatsapp_no ) ) : ?>
                    <a href="https://wa.me/<?php echo preg_replace( '/[^0-9]/', '', $customer->whatsapp_no ); ?>" target="_blank" class="ifs-btn-whatsapp">
                        <span class="dashicons dashicons-format-chat"></span> WhatsApp
                    </a>
                <?php endif; ?>
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
                
                <!-- Ultra-Polished Holographic GDS Passenger Profile Card -->
                <div class="ifs-digital-travel-card">
                    <div class="pass-head-strip">
                        <div class="airline-brand-tag">
                            <span class="dashicons dashicons-airplane"></span>
                            <span><?php echo strtoupper( esc_html( $customer->nationality ?: 'BANGLADESH' ) ); ?></span>
                        </div>
                        <span class="pass-type"><?php echo strtoupper( esc_html( $customer->client_type ?: 'RETAIL' ) ); ?></span>
                    </div>

                    <div class="pass-bio-hero">
                        <div class="card-avatar-wrapper">
                            <div class="pass-avatar">
                                <?php if ( ! empty( $customer->photo_url ) ) : ?>
                                    <img src="<?php echo esc_url( $customer->photo_url ); ?>" alt="Passenger Portrait" />
                                <?php else : ?>
                                    <span><?php echo esc_html( $initial ); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="card-type-chip"><?php echo esc_html( $ptype_code ); ?></div>
                        </div>
                        <div class="hero-name-details">
                            <h3 class="pass-name"><?php echo $title_prefix . esc_html( $customer->full_name ); ?></h3>
                            <span class="pass-sub"><?php echo esc_html( $customer->gender ?: 'Male' ); ?> &bull; <?php echo esc_html( $customer->marital_status ?: 'Married' ); ?> &bull; DOB: <?php echo ( ! empty( $customer->date_of_birth ) && $customer->date_of_birth !== '1970-01-01' ) ? date( 'd M Y', strtotime( $customer->date_of_birth ) ) : 'N/A'; ?></span>
                        </div>
                    </div>

                    <div class="pass-grid-specs">
                        <div class="grid-cell">
                            <span class="spec-lbl">PASSPORT NUMBER</span>
                            <span class="spec-val font-mono"><?php echo esc_html( $customer->passport_no ?: 'NOT SET' ); ?></span>
                        </div>
                        <div class="grid-cell">
                            <span class="spec-lbl">DOCUMENT STATUS</span>
                            <span class="spec-val status-indicator <?php echo esc_attr( $badge_class ); ?>"><?php echo esc_html( $badge_text ); ?></span>
                        </div>
                        <div class="grid-cell">
                            <span class="spec-lbl">PASSPORT TYPE</span>
                            <span class="spec-val"><?php echo esc_html( $customer->passport_type ?: 'Regular' ); ?></span>
                        </div>
                        <div class="grid-cell">
                            <span class="spec-lbl">ISSUE PLACE</span>
                            <span class="spec-val"><?php echo esc_html( $customer->passport_issue_place ?: 'Dhaka' ); ?></span>
                        </div>
                        <div class="grid-cell">
                            <span class="spec-lbl">NATIONAL ID (NID)</span>
                            <span class="spec-val font-mono"><?php echo esc_html( $customer->nid_no ?: 'N/A' ); ?></span>
                        </div>
                        <div class="grid-cell">
                            <span class="spec-lbl">BLOOD GROUP</span>
                            <span class="spec-val color-red"><?php echo esc_html( $customer->blood_group ?: 'Unknown' ); ?></span>
                        </div>
                    </div>

                    <!-- ICAO MRZ Machine Readable Zone -->
                    <?php
                    $surname_clean = strtoupper( preg_replace( '/[^A-Za-z]/', '', $customer->passport_surname ?? '' ) );
                    $given_clean   = strtoupper( preg_replace( '/[^A-Za-z]/', '<', $customer->passport_given_name ?? '' ) );
                    if ( empty( $surname_clean ) ) {
                        $parts_mrz     = explode( ' ', trim( $customer->full_name ) );
                        $surname_clean = ( count( $parts_mrz ) > 1 ) ? strtoupper( array_pop( $parts_mrz ) ) : 'PASSENGER';
                        $given_clean   = strtoupper( implode( '<', $parts_mrz ) ) ?: 'NAME';
                    }
                    $mrz_line1 = 'P<BGD' . $surname_clean . '<<' . $given_clean;
                    $mrz_line1 = str_pad( substr( $mrz_line1, 0, 44 ), 44, '<' );
                    $pass_clean = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', $customer->passport_no ?: 'A00000000' ) );
                    $mrz_line2  = str_pad( substr( $pass_clean, 0, 9 ), 9, '<' ) . '0BGD0000000M0000000<<<<<<<<<<<<<00';
                    $mrz_line2  = str_pad( substr( $mrz_line2, 0, 44 ), 44, '<' );
                    ?>
                    <div class="card-mrz-zone">
                        <div class="mrz-line font-mono"><?php echo esc_html( $mrz_line1 ); ?></div>
                        <div class="mrz-line font-mono"><?php echo esc_html( $mrz_line2 ); ?></div>
                    </div>

                    <div class="pass-barcode-decor">
                        <div class="barcode-svg"></div>
                        <span class="barcode-txt font-mono">IATCI &bull; ELECTRONIC DOSSIER RECORD VALIDATED</span>
                    </div>
                </div>

                <!-- Verified Contact & Logistics Card -->
                <div class="ifs-info-panel-card">
                    <h4 class="panel-card-title"><span class="dashicons dashicons-location-alt"></span> Logistics, Parentage &amp; SSR Preferences</h4>
                    <div class="ifs-panel-table">
                        <div class="panel-row">
                            <span class="panel-key"><span class="dashicons dashicons-phone"></span> Primary Mobile:</span>
                            <span class="panel-val"><a href="tel:<?php echo esc_attr( $customer->mobile ); ?>" class="link-bold"><?php echo esc_html( $customer->mobile ); ?></a></span>
                        </div>
                        <?php if ( ! empty( $customer->whatsapp_no ) ) : ?>
                            <div class="panel-row">
                                <span class="panel-key"><span class="dashicons dashicons-format-chat"></span> WhatsApp:</span>
                                <span class="panel-val"><a href="https://wa.me/<?php echo preg_replace( '/[^0-9]/', '', $customer->whatsapp_no ); ?>" target="_blank" class="link-emerald font-mono"><?php echo esc_html( $customer->whatsapp_no ); ?></a></span>
                            </div>
                        <?php endif; ?>
                        <div class="panel-row">
                            <span class="panel-key"><span class="dashicons dashicons-email"></span> Official Email:</span>
                            <span class="panel-val"><?php echo esc_html( $customer->email ?: 'N/A' ); ?></span>
                        </div>
                        <?php if ( ! empty( $customer->father_spouse_name ) ) : ?>
                            <div class="panel-row">
                                <span class="panel-key"><span class="dashicons dashicons-businessman"></span> Father / Spouse:</span>
                                <span class="panel-val"><?php echo esc_html( $customer->father_spouse_name ); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ( ! empty( $customer->mother_name ) ) : ?>
                            <div class="panel-row">
                                <span class="panel-key"><span class="dashicons dashicons-heart"></span> Mother's Name:</span>
                                <span class="panel-val"><?php echo esc_html( $customer->mother_name ); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ( ! empty( $customer->profession ) ) : ?>
                            <div class="panel-row">
                                <span class="panel-key"><span class="dashicons dashicons-portfolio"></span> Profession:</span>
                                <span class="panel-val"><?php echo esc_html( $customer->profession ); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ( ! empty( $customer->birth_place ) ) : ?>
                            <div class="panel-row">
                                <span class="panel-key"><span class="dashicons dashicons-location"></span> Birth Place:</span>
                                <span class="panel-val"><?php echo esc_html( $customer->birth_place ); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="panel-row">
                            <span class="panel-key"><span class="dashicons dashicons-sos"></span> Emergency Contact:</span>
                            <span class="panel-val color-rose"><?php echo esc_html( $customer->emergency_contact ?: 'Not Provided' ); ?></span>
                        </div>
                        <div class="panel-row">
                            <span class="panel-key"><span class="dashicons dashicons-coffee"></span> Meal Choice (SSR):</span>
                            <span class="panel-val font-mono"><?php echo esc_html( $customer->meal_preference ?? 'MOML (Muslim Meal)' ); ?></span>
                        </div>
                        <?php if ( ! empty( $customer->wheelchair_ssr ) && $customer->wheelchair_ssr !== 'NONE' ) : ?>
                            <div class="panel-row">
                                <span class="panel-key"><span class="dashicons dashicons-wheelchair"></span> Wheelchair SSR:</span>
                                <span class="panel-val color-amber font-mono"><?php echo esc_html( $customer->wheelchair_ssr ); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ( ! empty( $customer->frequent_flyer_no ) ) : ?>
                            <div class="panel-row">
                                <span class="panel-key"><span class="dashicons dashicons-star-filled"></span> Frequent Flyer (FFN):</span>
                                <span class="panel-val font-mono"><?php echo esc_html( $customer->frequent_flyer_no ); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ( ! empty( $customer->prev_passport_no ) ) : ?>
                            <div class="panel-row">
                                <span class="panel-key"><span class="dashicons dashicons-backup"></span> Previous Passport:</span>
                                <span class="panel-val font-mono"><?php echo esc_html( $customer->prev_passport_no ); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="panel-row full-width">
                            <span class="panel-key"><span class="dashicons dashicons-admin-home"></span> Permanent Address:</span>
                            <span class="panel-val text-wrap">
                                <?php 
                                echo esc_html( $customer->city ? $customer->city . ', ' : '' ); 
                                echo nl2br( esc_html( $customer->address ?: 'No address specified' ) ); 
                                ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Digital Vault & Document Previews Card -->
                <div class="ifs-info-panel-card">
                    <h4 class="panel-card-title"><span class="dashicons dashicons-media-document"></span> Digital Vault &amp; Document Records</h4>
                    
                    <div class="ifs-vault-view-list">
                        <!-- Passport Copy Preview -->
                        <div class="ifs-vault-view-item">
                            <div class="vault-item-left">
                                <div class="attach-thumb-mini">
                                    <?php if ( ! empty( $customer->passport_copy_url ) ) : ?>
                                        <?php if ( preg_match( '/\.(jpg|jpeg|png|webp|gif)$/i', $customer->passport_copy_url ) ) : ?>
                                            <img src="<?php echo esc_url( $customer->passport_copy_url ); ?>" alt="Passport Document" />
                                        <?php else : ?>
                                            <span class="dashicons dashicons-pdf" style="color:#dc2626;"></span>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <span class="dashicons dashicons-media-document" style="color:#94a3b8;"></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <strong class="vault-doc-title">Passport Copy / Bio-Page</strong>
                                    <span class="vault-doc-status <?php echo ! empty( $customer->passport_copy_url ) ? 'status-attached' : 'status-missing'; ?>">
                                        <?php echo ! empty( $customer->passport_copy_url ) ? 'Verified &amp; Attached' : 'Not Attached'; ?>
                                    </span>
                                </div>
                            </div>
                            <?php if ( ! empty( $customer->passport_copy_url ) ) : ?>
                                <a href="<?php echo esc_url( $customer->passport_copy_url ); ?>" target="_blank" class="ifs-btn-view-doc">
                                    <span class="dashicons dashicons-external"></span> Open
                                </a>
                            <?php else : ?>
                                <a href="<?php echo esc_url( $base_url . '&sub=edit&id=' . $id ); ?>" class="ifs-link-upload-mini">Upload</a>
                            <?php endif; ?>
                        </div>

                        <!-- NID / Visa Document Preview -->
                        <div class="ifs-vault-view-item">
                            <div class="vault-item-left">
                                <div class="attach-thumb-mini">
                                    <?php if ( ! empty( $customer->nid_copy_url ) ) : ?>
                                        <?php if ( preg_match( '/\.(jpg|jpeg|png|webp|gif)$/i', $customer->nid_copy_url ) ) : ?>
                                            <img src="<?php echo esc_url( $customer->nid_copy_url ); ?>" alt="NID Document" />
                                        <?php else : ?>
                                            <span class="dashicons dashicons-pdf" style="color:#dc2626;"></span>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <span class="dashicons dashicons-id-alt" style="color:#94a3b8;"></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <strong class="vault-doc-title">National ID / Visa Document</strong>
                                    <span class="vault-doc-status <?php echo ! empty( $customer->nid_copy_url ) ? 'status-attached' : 'status-missing'; ?>">
                                        <?php echo ! empty( $customer->nid_copy_url ) ? 'Verified &amp; Attached' : 'Not Attached'; ?>
                                    </span>
                                </div>
                            </div>
                            <?php if ( ! empty( $customer->nid_copy_url ) ) : ?>
                                <a href="<?php echo esc_url( $customer->nid_copy_url ); ?>" target="_blank" class="ifs-btn-view-doc">
                                    <span class="dashicons dashicons-external"></span> Open
                                </a>
                            <?php else : ?>
                                <a href="<?php echo esc_url( $base_url . '&sub=edit&id=' . $id ); ?>" class="ifs-link-upload-mini">Upload</a>
                            <?php endif; ?>
                        </div>

                        <!-- Portrait Photo Preview -->
                        <div class="ifs-vault-view-item">
                            <div class="vault-item-left">
                                <div class="attach-thumb-mini">
                                    <?php if ( ! empty( $customer->photo_url ) ) : ?>
                                        <img src="<?php echo esc_url( $customer->photo_url ); ?>" alt="Portrait" />
                                    <?php else : ?>
                                        <span class="dashicons dashicons-camera" style="color:#94a3b8;"></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <strong class="vault-doc-title">Passenger Portrait Photograph</strong>
                                    <span class="vault-doc-status <?php echo ! empty( $customer->photo_url ) ? 'status-attached' : 'status-missing'; ?>">
                                        <?php echo ! empty( $customer->photo_url ) ? 'Verified &amp; Attached' : 'Not Attached'; ?>
                                    </span>
                                </div>
                            </div>
                            <?php if ( ! empty( $customer->photo_url ) ) : ?>
                                <a href="<?php echo esc_url( $customer->photo_url ); ?>" target="_blank" class="ifs-btn-view-doc">
                                    <span class="dashicons dashicons-external"></span> Open
                                </a>
                            <?php else : ?>
                                <a href="<?php echo esc_url( $base_url . '&sub=edit&id=' . $id ); ?>" class="ifs-link-upload-mini">Upload</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Interactive Booking History Tabs & Dossier -->
            <div class="ifs-dossier-main-content">
                <div class="ifs-history-container-card">
                    
                    <div class="ifs-history-header-nav">
                        <h3 class="history-title"><span class="dashicons dashicons-backup"></span> Travel &amp; Booking Lifecycle Dossier</h3>
                    </div>

                    <!-- 1. Air Ticket Issuance Records -->
                    <div class="ifs-dossier-sub-section">
                        <div class="sub-section-title">
                            <span class="dashicons dashicons-airplane"></span> Flight Bookings &amp; Issued E-Tickets (<?php echo count( $air_tickets ); ?>)
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
                            <span class="dashicons dashicons-id-alt"></span> Visa Applications &amp; Stamping Status (<?php echo count( $visa_apps ); ?>)
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
                            <span class="dashicons dashicons-awards"></span> Hajj &amp; Umrah Pilgrimage Records (<?php echo count( $hajj_records ); ?>)
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
                            <span class="dashicons dashicons-palmtree"></span> Holiday Tours &amp; Hospitality Reservations (<?php echo count( $tour_records ) + count( $hotel_records ); ?>)
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
        .ifs-tier-pill { font-size: 10px; font-weight: 800; text-transform: uppercase; padding: 2px 8px; border-radius: 6px; }
        .ifs-type-pill { font-size: 10px; font-weight: 800; text-transform: uppercase; padding: 2px 8px; border-radius: 6px; background: #f3e8ff; color: #7e22ce; }
        .tier-retail { background: #e0f2fe; color: #0369a1; }
        .tier-corporate { background: #eef2ff; color: #4338ca; }
        .tier-vip { background: #fdf4ff; color: #a21caf; }
        .ifs-nation-pill { font-size: 11px; color: #64748b; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
        .ifs-nation-pill .dashicons { font-size: 14px; width: 14px; height: 14px; }
        .ifs-view-name { margin: 0; font-size: 22px; font-weight: 800; color: #0f172a; letter-spacing: -0.4px; }
        
        .ifs-header-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
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
        .ifs-btn-whatsapp {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #15803d !important;
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
        .ifs-btn-whatsapp:hover { background: #bbf7d0; color: #166534 !important; }
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
            border-radius: 14px;
            padding: 18px 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 8px -2px rgba(15, 23, 42, 0.03);
        }
        .metric-icon { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #ffffff; flex-shrink: 0; }
        .metric-icon.bg-blue    { background: linear-gradient(135deg, #003376 0%, #0284c7 100%); }
        .metric-icon.bg-emerald { background: linear-gradient(135deg, #059669 0%, #047857 100%); }
        .metric-icon.bg-rose    { background: linear-gradient(135deg, #e11d48 0%, #be123c 100%); }
        .metric-icon.bg-indigo  { background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); }
        .metric-icon .dashicons { font-size: 22px; width: 22px; height: 22px; }
        .metric-lbl { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; display: block; margin-bottom: 2px; }
        .metric-val { font-size: 19px; font-weight: 800; color: #0f172a; }
        .font-small { font-size: 15px; }
        .color-emerald { color: #059669 !important; }
        .color-rose { color: #e11d48 !important; }
        .color-slate { color: #64748b !important; }

        /* Split Screen Grid */
        .ifs-dossier-split-layout {
            display: grid;
            grid-template-columns: 410px 1fr;
            gap: 24px;
            align-items: flex-start;
        }
        @media (max-width: 1180px) { .ifs-dossier-split-layout { grid-template-columns: 1fr; } }

        /* Digital Passport / Boarding Pass */
        .ifs-digital-travel-card {
            background: radial-gradient(circle at 100% 0%, #0369a1 0%, #002b66 50%, #001738 100%);
            border-radius: 18px;
            padding: 24px;
            color: #ffffff;
            box-shadow: 0 20px 40px -8px rgba(0, 51, 118, 0.45);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.12);
            margin-bottom: 22px;
        }
        .pass-head-strip { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid rgba(255, 255, 255, 0.15); padding-bottom: 12px; }
        .airline-brand-tag { display: flex; align-items: center; gap: 6px; font-size: 10px; font-weight: 800; letter-spacing: 1px; color: #7dd3fc; }
        .airline-brand-tag .dashicons { font-size: 14px; width: 14px; height: 14px; }
        .pass-type { background: rgba(255, 255, 255, 0.18); backdrop-filter: blur(4px); padding: 2px 8px; border-radius: 4px; font-size: 9.5px; font-weight: 800; letter-spacing: 0.5px; }
        
        .pass-bio-hero { display: flex; align-items: center; gap: 14px; margin-bottom: 18px; }
        .card-avatar-wrapper { position: relative; flex-shrink: 0; }
        .pass-avatar {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 17px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.25);
        }
        .pass-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .card-type-chip {
            position: absolute;
            bottom: -4px;
            right: -4px;
            background: #0284c7;
            color: #ffffff;
            font-size: 8.5px;
            font-weight: 900;
            padding: 1px 4px;
            border-radius: 4px;
            border: 1px solid #ffffff;
        }
        .hero-name-details { flex: 1; min-width: 0; }
        .pass-name { margin: 0; font-size: 15px; font-weight: 800; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .pass-sub { font-size: 11px; color: #bae6fd; margin-top: 3px; display: block; }
        
        .pass-grid-specs { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 14px; padding: 14px 0; border-top: 1px dashed rgba(255, 255, 255, 0.2); border-bottom: 1px dashed rgba(255, 255, 255, 0.2); margin-bottom: 14px; }
        .spec-lbl { display: block; font-size: 8.5px; font-weight: 800; color: #7dd3fc; letter-spacing: 0.6px; margin-bottom: 2px; }
        .spec-val { font-size: 11.5px; font-weight: 700; color: #ffffff; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .status-indicator.badge-valid { color: #86efac; }
        .status-indicator.badge-warning { color: #fde047; }
        .status-indicator.badge-expired { color: #f87171; }
        .color-red { color: #fca5a5 !important; }
        
        .card-mrz-zone { background: rgba(0, 0, 0, 0.25); padding: 8px 10px; border-radius: 8px; margin-bottom: 12px; border: 1px solid rgba(255, 255, 255, 0.08); }
        .mrz-line { font-size: 9px; color: #e0f2fe; letter-spacing: 1.2px; line-height: 1.35; white-space: nowrap; overflow: hidden; text-overflow: clip; }
        
        .pass-barcode-decor { text-align: center; }
        .barcode-svg { height: 18px; background: repeating-linear-gradient(90deg, #ffffff, #ffffff 2px, transparent 2px, transparent 4px, #ffffff 4px, #ffffff 5px, transparent 5px, transparent 8px); opacity: 0.75; margin-bottom: 4px; }
        .barcode-txt { font-size: 8px; color: #7dd3fc; letter-spacing: 1px; }

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
        .ifs-panel-table { display: flex; flex-direction: column; gap: 11px; }
        .panel-row { display: flex; justify-content: space-between; align-items: flex-start; font-size: 13px; gap: 10px; }
        .panel-row.full-width { flex-direction: column; gap: 4px; }
        .panel-key { color: #64748b; display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0; font-size: 12.5px; }
        .panel-key .dashicons { font-size: 14px; width: 14px; height: 14px; color: #94a3b8; }
        .panel-val { font-weight: 700; color: #0f172a; text-align: right; }
        .link-bold { color: #0284c7; text-decoration: none; }
        .link-emerald { color: #059669; text-decoration: none; }
        .color-amber { color: #d97706 !important; }
        .text-wrap { text-align: left; font-weight: normal; color: #334155; line-height: 1.4; }

        /* Vault Item Lists in View Tab */
        .ifs-vault-view-list { display: flex; flex-direction: column; gap: 12px; }
        .ifs-vault-view-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        .vault-item-left { display: flex; align-items: center; gap: 12px; }
        .attach-thumb-mini {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }
        .attach-thumb-mini img { width: 100%; height: 100%; object-fit: cover; }
        .attach-thumb-mini .dashicons { font-size: 20px; width: 20px; height: 20px; }
        .vault-doc-title { font-size: 13px; color: #0f172a; display: block; }
        .vault-doc-status { font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .vault-doc-status.status-attached { color: #16a34a; }
        .vault-doc-status.status-missing { color: #94a3b8; }
        .ifs-btn-view-doc {
            background: #003376;
            color: #ffffff !important;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s ease;
        }
        .ifs-btn-view-doc:hover { background: #0284c7; }
        .ifs-btn-view-doc .dashicons { font-size: 13px; width: 13px; height: 13px; }
        .ifs-link-upload-mini { font-size: 12px; font-weight: 700; color: #0284c7; text-decoration: none; }

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