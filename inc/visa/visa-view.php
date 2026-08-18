<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enterprise Ultra-Modern Visa Application Dossier & Printable Status Itinerary
 * Features: High-End Visa Card Header, Document Checklist Vault, Live Document Previews, Applicant Passport Meta & Print Utility
 */
function ifs_terp_visa_view_page() {
    global $wpdb;
    $id       = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
    $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=visa' );

    if ( ! $id ) {
        echo '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> Invalid Visa Record ID.</div>';
        return;
    }

    $table_visas     = $wpdb->prefix . 'iterp_visa_applications';
    $table_customers = $wpdb->prefix . 'iterp_customers';
    $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
    $table_agents    = $wpdb->prefix . 'iterp_agents';

    $query = $wpdb->prepare( "
        SELECT v.*, 
               c.title AS customer_title, c.full_name AS customer_name, c.mobile AS customer_mobile, c.passport_no AS customer_passport, c.passport_expiry, c.email AS customer_email, c.nationality, c.photo_url AS customer_photo,
               s.supplier_name,
               a.agency_name, a.contact_person AS agent_contact
        FROM $table_visas v
        LEFT JOIN $table_customers c ON v.customer_id = c.id
        LEFT JOIN $table_suppliers s ON v.supplier_id = s.id
        LEFT JOIN $table_agents a ON v.agent_id = a.id
        WHERE v.id = %d
    ", $id );
    
    $visa = $wpdb->get_row( $query );

    if ( ! $visa ) {
        echo '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> Visa application file not found.</div>';
        return;
    }

    // Status Badges
    $status_class = 'status-processing';
    $status_lower = strtolower( $visa->status );
    if ( $status_lower === 'approved' )     $status_class = 'status-approved';
    elseif ( $status_lower === 'delivered' ) $status_class = 'status-delivered';
    elseif ( $status_lower === 'rejected' )  $status_class = 'status-rejected';

    $title_prefix = ! empty( $visa->customer_title ) ? esc_html( $visa->customer_title ) . '. ' : '';
    $pax_name     = ! empty( $visa->passenger_name ) ? esc_html( $visa->passenger_name ) : ( ! empty( $visa->customer_name ) ? $title_prefix . esc_html( $visa->customer_name ) : 'Guest Applicant' );
    $passport_num = ! empty( $visa->passport_no ) ? esc_html( $visa->passport_no ) : ( ! empty( $visa->customer_passport ) ? esc_html( $visa->customer_passport ) : 'NOT PROVIDED' );
    $photo_url    = ! empty( $visa->photo_url ) ? esc_url( $visa->photo_url ) : ( ! empty( $visa->customer_photo ) ? esc_url( $visa->customer_photo ) : '' );

    // Initials for avatar
    $parts   = explode( ' ', trim( $pax_name ) );
    $initial = ( count( $parts ) > 1 ) ? ( mb_substr( $parts[0], 0, 1 ) . mb_substr( $parts[count($parts)-1], 0, 1 ) ) : mb_substr( $pax_name, 0, 2 );
    $initial = strtoupper( $initial );

    // Payment badge styling
    $pay_status = $visa->payment_status ?? 'Unpaid';
    $pay_class  = ( $pay_status === 'Paid' ) ? 'pay-paid' : ( ( $pay_status === 'Partial' ) ? 'pay-partial' : 'pay-due' );

    // Document Checklist Parser
    $checklist_items = array();
    if ( ! empty( $visa->documents_collected ) ) {
        $decoded = json_decode( $visa->documents_collected, true );
        if ( is_array( $decoded ) ) {
            $checklist_items = $decoded;
        }
    }

    // MRV Machine Readable Lines
    $clean_country = strtoupper( substr( preg_replace( '/[^A-Za-z]/', '', $visa->country ), 0, 3 ) ) ?: 'BGD';
    $parts_mrv     = explode( ' ', trim( $pax_name ) );
    $mrv_surname   = ( count( $parts_mrv ) > 1 ) ? strtoupper( array_pop( $parts_mrv ) ) : 'APPLICANT';
    $mrv_given     = strtoupper( implode( '<', $parts_mrv ) ) ?: 'NAME';
    $mrv_line1     = 'V<' . $clean_country . $mrv_surname . '<<' . $mrv_given;
    $mrv_line1     = str_pad( substr( $mrv_line1, 0, 44 ), 44, '<' );
    $pass_clean    = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', $passport_num ) );
    $mrv_line2     = str_pad( substr( $pass_clean, 0, 9 ), 9, '<' ) . '0' . $clean_country . '0000000M0000000<<<<<<<<<<<<<00';
    $mrv_line2     = str_pad( substr( $mrv_line2, 0, 44 ), 44, '<' );
    ?>

    <div class="ifs-visa-view-workspace">
        
        <!-- Top Executive Header Strip -->
        <div class="ifs-view-header-strip">
            <div class="ifs-header-identity">
                <a href="<?php echo esc_url( $base_url . '&sub=list' ); ?>" class="ifs-back-round-btn" title="Return to Visa Pipeline">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                </a>
                <div>
                    <div class="ifs-badge-row">
                        <span class="ifs-id-pill">#VSA-<?php echo str_pad( (string) $visa->id, 5, '0', STR_PAD_LEFT ); ?></span>
                        <span class="ifs-country-pill"><span class="dashicons dashicons-admin-site-alt3"></span> <?php echo esc_html( $visa->country ); ?></span>
                        <span class="ifs-pay-pill <?php echo esc_attr( $pay_class ); ?>"><?php echo esc_html( $pay_status ); ?></span>
                        <span class="ifs-status-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $visa->status ); ?></span>
                    </div>
                    <h2 class="ifs-view-name"><?php echo esc_html( $visa->country ); ?> &mdash; <?php echo esc_html( $visa->visa_type ); ?></h2>
                </div>
            </div>

            <div class="ifs-header-actions">
                <button type="button" onclick="window.print();" class="ifs-btn-print">
                    <span class="dashicons dashicons-printer"></span> Print Dossier
                </button>
                <a href="<?php echo esc_url( $base_url . '&sub=edit&id=' . $id ); ?>" class="ifs-btn-edit">
                    <span class="dashicons dashicons-edit"></span> Edit Application
                </a>
            </div>
        </div>

        <!-- Metric KPI Ribbon -->
        <div class="ifs-dossier-metrics-grid">
            <div class="ifs-metric-box">
                <div class="metric-icon bg-cyan"><span class="dashicons dashicons-admin-site-alt3"></span></div>
                <div>
                    <span class="metric-lbl">Destination Country</span>
                    <strong class="metric-val font-mono"><?php echo esc_html( $visa->country ); ?></strong>
                </div>
            </div>

            <div class="ifs-metric-box">
                <div class="metric-icon bg-indigo"><span class="dashicons dashicons-money-alt"></span></div>
                <div>
                    <span class="metric-lbl">Client Invoiced Fee</span>
                    <strong class="metric-val color-blue">৳<?php echo number_format( (float) $visa->sell_price, 2 ); ?></strong>
                </div>
            </div>

            <div class="ifs-metric-box">
                <div class="metric-icon bg-slate"><span class="dashicons dashicons-cart"></span></div>
                <div>
                    <span class="metric-lbl">Embassy / Supplier Cost</span>
                    <strong class="metric-val color-slate">৳<?php echo number_format( (float) $visa->buy_price, 2 ); ?></strong>
                </div>
            </div>

            <div class="ifs-metric-box">
                <div class="metric-icon bg-emerald"><span class="dashicons dashicons-chart-line"></span></div>
                <div>
                    <span class="metric-lbl">Net Agency Margin</span>
                    <strong class="metric-val <?php echo ( $visa->profit >= 0 ) ? 'color-emerald' : 'color-rose'; ?>">৳<?php echo number_format( (float) $visa->profit, 2 ); ?></strong>
                </div>
            </div>
        </div>

        <!-- Split Grid: Left Visa Card & Right Dossier Specifications -->
        <div class="ifs-dossier-split-layout">
            
            <!-- Left Column: Printable Digital Visa Pass Card & Vault Previews -->
            <div class="ifs-dossier-left-sidebar">
                
                <!-- Holographic Visa Passport Card -->
                <div class="ifs-visa-card">
                    <div class="visa-head-strip">
                        <div class="visa-country-tag">
                            <span class="dashicons dashicons-admin-site-alt3"></span>
                            <span><?php echo esc_html( strtoupper( $visa->country ) ); ?></span>
                        </div>
                        <span class="visa-type-badge"><?php echo esc_html( strtoupper( $visa->visa_type ) ); ?></span>
                    </div>

                    <div class="visa-applicant-hero">
                        <div class="visa-avatar-wrap">
                            <div class="visa-avatar">
                                <?php if ( ! empty( $photo_url ) ) : ?>
                                    <img src="<?php echo $photo_url; ?>" alt="Applicant Photo" />
                                <?php else : ?>
                                    <span><?php echo esc_html( $initial ); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="visa-entry-pill"><?php echo ( $visa->entry_type === 'Single Entry' ) ? 'SGL' : ( ( $visa->entry_type === 'Multiple Entry' ) ? 'MULT' : 'DBL' ); ?></div>
                        </div>
                        <div class="visa-applicant-details">
                            <h4 class="visa-name"><?php echo $pax_name; ?></h4>
                            <div class="visa-submeta">PPT: <?php echo $passport_num; ?> &bull; <?php echo esc_html( strtoupper( $visa->nationality ?: 'BANGLADESHI' ) ); ?></div>
                        </div>
                    </div>

                    <div class="visa-grid-specs font-mono">
                        <div class="spec-cell">
                            <span class="visa-lbl">TRACKING / REF</span>
                            <strong class="visa-val color-cyan"><?php echo esc_html( $visa->tracking_no ?: 'PENDING' ); ?></strong>
                        </div>
                        <div class="spec-cell">
                            <span class="visa-lbl">ENTRY PERMISSION</span>
                            <strong class="visa-val"><?php echo esc_html( strtoupper( $visa->entry_type ?? 'SINGLE ENTRY' ) ); ?></strong>
                        </div>
                        <div class="spec-cell">
                            <span class="visa-lbl">SUBMISSION DATE</span>
                            <strong class="visa-val"><?php echo ( $visa->submission_date !== '1970-01-01' && ! empty( $visa->submission_date ) ) ? date( 'd M Y', strtotime( $visa->submission_date ) ) : 'NOT SUBMITTED'; ?></strong>
                        </div>
                        <div class="spec-cell">
                            <span class="visa-lbl">DELIVERY (EST)</span>
                            <strong class="visa-val color-green"><?php echo ( $visa->expected_delivery !== '1970-01-01' && ! empty( $visa->expected_delivery ) ) ? date( 'd M Y', strtotime( $visa->expected_delivery ) ) : 'TBD'; ?></strong>
                        </div>
                        <div class="spec-cell">
                            <span class="visa-lbl">STAY VALIDITY</span>
                            <strong class="visa-val"><?php echo esc_html( $visa->validity_days ?? 30 ); ?> DAYS</strong>
                        </div>
                        <div class="spec-cell">
                            <span class="visa-lbl">TOTAL INVOICED</span>
                            <strong class="visa-val color-green">৳<?php echo number_format( (float) $visa->sell_price, 2 ); ?></strong>
                        </div>
                    </div>

                    <!-- ICAO MRV Code Zone -->
                    <div class="visa-mrv-zone font-mono">
                        <div class="mrv-line"><?php echo esc_html( $mrv_line1 ); ?></div>
                        <div class="mrv-line"><?php echo esc_html( $mrv_line2 ); ?></div>
                    </div>

                    <div class="visa-fee-footer">
                        <div class="visa-barcode-lines"></div>
                        <span class="visa-barcode-txt font-mono">IATCI &bull; EMBASSY VISA DOSSIER VALIDATED</span>
                    </div>
                </div>

                <!-- Processing Vendor & Channel Card -->
                <div class="ifs-info-panel-card">
                    <h4 class="panel-card-title"><span class="dashicons dashicons-networking"></span> Issuing Channel &amp; Vendor</h4>
                    <div class="ifs-panel-table">
                        <div class="panel-row">
                            <span class="panel-key"><span class="dashicons dashicons-store"></span> Processing Vendor:</span>
                            <span class="panel-val"><?php echo esc_html( $visa->supplier_name ?: 'Direct Embassy Submission' ); ?></span>
                        </div>
                        <div class="panel-row">
                            <span class="panel-key"><span class="dashicons dashicons-groups"></span> B2B Sub-Agent:</span>
                            <span class="panel-val <?php echo ! empty( $visa->agency_name ) ? 'color-indigo font-bold' : ''; ?>">
                                <?php echo esc_html( $visa->agency_name ?: 'Direct Retail Customer' ); ?>
                            </span>
                        </div>
                        <div class="panel-row">
                            <span class="panel-key"><span class="dashicons dashicons-money-alt"></span> Payment Status:</span>
                            <span class="panel-val"><span class="ifs-pay-pill <?php echo esc_attr( $pay_class ); ?>"><?php echo esc_html( $pay_status ); ?></span></span>
                        </div>
                        <div class="panel-row">
                            <span class="panel-key"><span class="dashicons dashicons-vault"></span> Payment Method:</span>
                            <span class="panel-val"><?php echo esc_html( $visa->payment_method ?? 'Bank Transfer' ); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Digital Vault Live Attachments Preview Card -->
                <div class="ifs-info-panel-card">
                    <h4 class="panel-card-title"><span class="dashicons dashicons-media-document"></span> Digital Vault Documents</h4>
                    <div class="ifs-vault-view-list">
                        <!-- Passport Scan Document -->
                        <div class="ifs-vault-view-item">
                            <div class="vault-item-left">
                                <div class="attach-thumb-mini">
                                    <?php if ( ! empty( $visa->passport_scan_url ) ) : ?>
                                        <?php if ( preg_match( '/\.(jpg|jpeg|png|webp)$/i', $visa->passport_scan_url ) ) : ?>
                                            <img src="<?php echo esc_url( $visa->passport_scan_url ); ?>" alt="Passport Scan" />
                                        <?php else : ?>
                                            <span class="dashicons dashicons-pdf" style="color:#dc2626;"></span>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <span class="dashicons dashicons-media-document" style="color:#94a3b8;"></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <strong class="vault-doc-title">Passport Bio-Page Scan</strong>
                                    <span class="vault-doc-status <?php echo ! empty( $visa->passport_scan_url ) ? 'status-attached' : 'status-missing'; ?>">
                                        <?php echo ! empty( $visa->passport_scan_url ) ? 'Attached &amp; Verified' : 'Not Attached'; ?>
                                    </span>
                                </div>
                            </div>
                            <?php if ( ! empty( $visa->passport_scan_url ) ) : ?>
                                <a href="<?php echo esc_url( $visa->passport_scan_url ); ?>" target="_blank" class="ifs-btn-view-doc"><span class="dashicons dashicons-external"></span> Open</a>
                            <?php else : ?>
                                <a href="<?php echo esc_url( $base_url . '&sub=edit&id=' . $id ); ?>" class="ifs-link-upload-mini">Upload</a>
                            <?php endif; ?>
                        </div>

                        <!-- Approved Visa Copy Document -->
                        <div class="ifs-vault-view-item">
                            <div class="vault-item-left">
                                <div class="attach-thumb-mini">
                                    <?php if ( ! empty( $visa->visa_doc_url ) ) : ?>
                                        <?php if ( preg_match( '/\.(jpg|jpeg|png|webp)$/i', $visa->visa_doc_url ) ) : ?>
                                            <img src="<?php echo esc_url( $visa->visa_doc_url ); ?>" alt="Visa Document" />
                                        <?php else : ?>
                                            <span class="dashicons dashicons-pdf" style="color:#dc2626;"></span>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <span class="dashicons dashicons-id-alt" style="color:#94a3b8;"></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <strong class="vault-doc-title">Approved E-Visa / Stamped Copy</strong>
                                    <span class="vault-doc-status <?php echo ! empty( $visa->visa_doc_url ) ? 'status-attached' : 'status-missing'; ?>">
                                        <?php echo ! empty( $visa->visa_doc_url ) ? 'Attached &amp; Verified' : 'Not Attached'; ?>
                                    </span>
                                </div>
                            </div>
                            <?php if ( ! empty( $visa->visa_doc_url ) ) : ?>
                                <a href="<?php echo esc_url( $visa->visa_doc_url ); ?>" target="_blank" class="ifs-btn-view-doc"><span class="dashicons dashicons-external"></span> Open</a>
                            <?php else : ?>
                                <a href="<?php echo esc_url( $base_url . '&sub=edit&id=' . $id ); ?>" class="ifs-link-upload-mini">Upload</a>
                            <?php endif; ?>
                        </div>

                        <!-- Supporting Documents Packet -->
                        <div class="ifs-vault-view-item">
                            <div class="vault-item-left">
                                <div class="attach-thumb-mini">
                                    <?php if ( ! empty( $visa->supporting_doc_url ) ) : ?>
                                        <?php if ( preg_match( '/\.(jpg|jpeg|png|webp)$/i', $visa->supporting_doc_url ) ) : ?>
                                            <img src="<?php echo esc_url( $visa->supporting_doc_url ); ?>" alt="Supporting Packet" />
                                        <?php else : ?>
                                            <span class="dashicons dashicons-pdf" style="color:#dc2626;"></span>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <span class="dashicons dashicons-portfolio" style="color:#94a3b8;"></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <strong class="vault-doc-title">Embassy Support Packet</strong>
                                    <span class="vault-doc-status <?php echo ! empty( $visa->supporting_doc_url ) ? 'status-attached' : 'status-missing'; ?>">
                                        <?php echo ! empty( $visa->supporting_doc_url ) ? 'Attached &amp; Verified' : 'Not Attached'; ?>
                                    </span>
                                </div>
                            </div>
                            <?php if ( ! empty( $visa->supporting_doc_url ) ) : ?>
                                <a href="<?php echo esc_url( $visa->supporting_doc_url ); ?>" target="_blank" class="ifs-btn-view-doc"><span class="dashicons dashicons-external"></span> Open</a>
                            <?php else : ?>
                                <a href="<?php echo esc_url( $base_url . '&sub=edit&id=' . $id ); ?>" class="ifs-link-upload-mini">Upload</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column: Visa Timeline, Applicant Manifest & Accounting Ledger -->
            <div class="ifs-dossier-main-content">
                
                <!-- 1. Application & Embassy Timeline -->
                <div class="ifs-history-container-card">
                    <div class="ifs-history-header-nav">
                        <h3 class="history-title"><span class="dashicons dashicons-clock"></span> Application Specifications &amp; Schedule</h3>
                    </div>

                    <div class="ifs-specs-two-col">
                        <div class="spec-item">
                            <span class="spec-title"><span class="dashicons dashicons-admin-site-alt3"></span> Destination Country</span>
                            <strong class="spec-data color-blue"><?php echo esc_html( $visa->country ); ?></strong>
                        </div>
                        <div class="spec-item">
                            <span class="spec-title"><span class="dashicons dashicons-tag"></span> Visa Category</span>
                            <strong class="spec-data"><?php echo esc_html( $visa->visa_type ); ?></strong>
                        </div>
                        <div class="spec-item">
                            <span class="spec-title"><span class="dashicons dashicons-location-alt"></span> Entry Permission</span>
                            <strong class="spec-data"><?php echo esc_html( $visa->entry_type ?? 'Single Entry' ); ?></strong>
                        </div>
                        <div class="spec-item">
                            <span class="spec-title"><span class="dashicons dashicons-building"></span> Processing Center</span>
                            <strong class="spec-data"><?php echo esc_html( $visa->processing_center ?: 'VFS Global / Embassy' ); ?></strong>
                        </div>
                        <div class="spec-item">
                            <span class="spec-title"><span class="dashicons dashicons-search"></span> Tracking / File Ref</span>
                            <strong class="spec-data font-mono"><?php echo esc_html( $visa->tracking_no ?: 'PENDING' ); ?></strong>
                        </div>
                        <div class="spec-item">
                            <span class="spec-title"><span class="dashicons dashicons-media-document"></span> MOFA / Reference No</span>
                            <strong class="spec-data font-mono"><?php echo esc_html( $visa->embassy_app_no ?: 'N/A' ); ?></strong>
                        </div>
                        <div class="spec-item">
                            <span class="spec-title"><span class="dashicons dashicons-calendar-alt"></span> Embassy Submission Date</span>
                            <strong class="spec-data"><?php echo ( $visa->submission_date !== '1970-01-01' && ! empty( $visa->submission_date ) ) ? date( 'l, d F Y', strtotime( $visa->submission_date ) ) : 'Not Submitted'; ?></strong>
                        </div>
                        <div class="spec-item">
                            <span class="spec-title"><span class="dashicons dashicons-clock"></span> Biometric / Appointment Date</span>
                            <strong class="spec-data"><?php echo ( ! empty( $visa->appointment_date ) && $visa->appointment_date !== '1970-01-01' ) ? date( 'l, d F Y', strtotime( $visa->appointment_date ) ) : 'No Appointment Required'; ?></strong>
                        </div>
                        <div class="spec-item">
                            <span class="spec-title"><span class="dashicons dashicons-yes-alt"></span> Expected Delivery Date</span>
                            <strong class="spec-data color-emerald"><?php echo ( $visa->expected_delivery !== '1970-01-01' && ! empty( $visa->expected_delivery ) ) ? date( 'l, d F Y', strtotime( $visa->expected_delivery ) ) : 'TBD'; ?></strong>
                        </div>
                        <div class="spec-item">
                            <span class="spec-title"><span class="dashicons dashicons-backup"></span> Validity Duration</span>
                            <strong class="spec-data font-mono"><?php echo esc_html( $visa->validity_days ?? 30 ); ?> Days Stay</strong>
                        </div>
                    </div>
                </div>

                <!-- 2. Applicant Manifest & Passport Details -->
                <div class="ifs-history-container-card" style="margin-top: 22px;">
                    <div class="ifs-history-header-nav">
                        <h3 class="history-title"><span class="dashicons dashicons-admin-users"></span> Applicant Manifest &amp; Contact Profile</h3>
                    </div>

                    <div class="ifs-passenger-dossier-card">
                        <div class="dossier-avatar">
                            <?php if ( ! empty( $photo_url ) ) : ?>
                                <img src="<?php echo $photo_url; ?>" alt="Applicant Photo" style="width:100%; height:100%; object-fit:cover; border-radius:12px;" />
                            <?php else : ?>
                                <?php echo esc_html( $initial ); ?>
                            <?php endif; ?>
                        </div>
                        <div class="dossier-info">
                            <h4 class="dossier-name">
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=customers&sub=view&id=' . $visa->customer_id ) ); ?>">
                                    <?php echo $pax_name; ?>
                                </a>
                            </h4>
                            <div class="dossier-meta-grid">
                                <div><span>Mobile:</span> <strong><a href="tel:<?php echo esc_attr( $visa->customer_mobile ); ?>"><?php echo esc_html( $visa->customer_mobile ?: 'N/A' ); ?></a></strong></div>
                                <div><span>Email:</span> <strong><?php echo esc_html( $visa->customer_email ?: 'N/A' ); ?></strong></div>
                                <div><span>Passport No:</span> <strong class="font-mono"><?php echo $passport_num; ?></strong></div>
                                <div><span>Passport Expiry:</span> <strong><?php echo ( ! empty( $visa->passport_expiry ) && $visa->passport_expiry !== '1970-01-01' ) ? date( 'd M, Y', strtotime( $visa->passport_expiry ) ) : 'N/A'; ?></strong></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Dynamic Documents Checklist Table -->
                <div class="ifs-history-container-card" style="margin-top: 22px;">
                    <div class="ifs-history-header-nav">
                        <h3 class="history-title"><span class="dashicons dashicons-clipboard"></span> Physical Document Checklist Verification</h3>
                    </div>

                    <?php if ( ! empty( $checklist_items ) ) : ?>
                        <table class="ifs-inner-table">
                            <thead>
                                <tr>
                                    <th>Document Title</th>
                                    <th>Verification Status</th>
                                    <th>Operational Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $checklist_items as $item ) : 
                                    $st = $item['status'] ?? 'Received';
                                    $st_class = ( $st === 'Received' ) ? 'doc-received' : ( ( $st === 'Submitted' ) ? 'doc-submitted' : 'doc-pending' );
                                ?>
                                    <tr>
                                        <td><strong><?php echo esc_html( $item['name'] ); ?></strong></td>
                                        <td><span class="doc-status-pill <?php echo esc_attr( $st_class ); ?>"><?php echo esc_html( $st ); ?></span></td>
                                        <td style="color: #64748b;"><?php echo esc_html( $item['note'] ?: '-' ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <div class="ifs-no-data-strip">No physical documents recorded for this application file.</div>
                    <?php endif; ?>
                </div>

                <!-- 4. Commercial Accounting & Settlement -->
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
                            <?php if ( ! empty( $visa->embassy_fee ) && $visa->embassy_fee > 0 ) : ?>
                                <tr>
                                    <td><span class="dashicons dashicons-arrow-right-alt2"></span> Embassy Visa Fee Component</td>
                                    <td style="text-align: right;" class="font-mono">৳<?php echo number_format( (float) $visa->embassy_fee, 2 ); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ( ! empty( $visa->service_fee ) && $visa->service_fee > 0 ) : ?>
                                <tr>
                                    <td><span class="dashicons dashicons-arrow-right-alt2"></span> Agency Processing &amp; Service Charge Component</td>
                                    <td style="text-align: right;" class="font-mono">৳<?php echo number_format( (float) $visa->service_fee, 2 ); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr class="highlight-row">
                                <td><strong>Supplier / Embassy Processing Cost Rate (Payable)</strong></td>
                                <td style="text-align: right;" class="font-mono font-bold color-slate">৳<?php echo number_format( (float) $visa->buy_price, 2 ); ?></td>
                            </tr>
                            <tr class="highlight-row">
                                <td><strong>Client Invoiced Visa Fee (Gross Revenue)</strong></td>
                                <td style="text-align: right;" class="font-mono font-bold color-blue">৳<?php echo number_format( (float) $visa->sell_price, 2 ); ?></td>
                            </tr>
                            <tr class="total-row">
                                <td><strong>Net Agency Commission &amp; Profit Margin</strong></td>
                                <td style="text-align: right;" class="font-mono font-bold <?php echo ( $visa->profit >= 0 ) ? 'color-emerald' : 'color-rose'; ?>">
                                    ৳<?php echo number_format( (float) $visa->profit, 2 ); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <?php if ( ! empty( $visa->remarks ) ) : ?>
                        <div class="ifs-ticket-remarks-box">
                            <span class="remarks-title"><span class="dashicons dashicons-info"></span> Operational Remarks &amp; Embassy Notes:</span>
                            <p class="remarks-body"><?php echo nl2br( esc_html( $visa->remarks ) ); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </div>
    </div>

    <!-- Ultra High-End Dossier Stylesheet -->
    <style>
        .ifs-visa-view-workspace { max-width: 1420px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; }
        
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
        .ifs-back-round-btn:hover { background: #0284c7; color: #ffffff; }
        .ifs-badge-row { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; flex-wrap: wrap; }
        .ifs-id-pill { font-family: ui-monospace, monospace; font-size: 11px; font-weight: 800; background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 6px; border: 1px solid #e2e8f0; }
        .ifs-country-pill { font-size: 10.5px; font-weight: 800; background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px; text-transform: uppercase; }
        .ifs-country-pill .dashicons { font-size: 13px; width: 13px; height: 13px; }
        
        .ifs-pay-pill { font-size: 10px; font-weight: 800; text-transform: uppercase; padding: 2px 8px; border-radius: 6px; }
        .pay-paid    { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .pay-partial { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .pay-due     { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

        .ifs-status-badge { font-size: 10px; font-weight: 800; text-transform: uppercase; padding: 2px 8px; border-radius: 6px; }
        .status-processing { background: #fef3c7; color: #b45309; }
        .status-approved   { background: #dcfce7; color: #15803d; }
        .status-delivered  { background: #e0f2fe; color: #0369a1; }
        .status-rejected   { background: #fee2e2; color: #b91c1c; }

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
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #ffffff !important;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
            transition: all 0.2s ease;
        }
        .ifs-btn-edit:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(2, 132, 199, 0.35); }

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
        .metric-icon.bg-cyan    { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); }
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

        /* Holographic Visa Pass Card */
        .ifs-visa-card {
            background: radial-gradient(circle at 100% 0%, #0284c7 0%, #0369a1 50%, #0c4a6e 100%);
            border-radius: 18px;
            padding: 22px;
            color: #ffffff;
            box-shadow: 0 20px 40px -8px rgba(2, 132, 199, 0.45);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.15);
            margin-bottom: 22px;
        }
        .visa-head-strip { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid rgba(255, 255, 255, 0.15); }
        .visa-country-tag { display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 800; letter-spacing: 0.8px; color: #7dd3fc; }
        .visa-country-tag .dashicons { font-size: 15px; width: 15px; height: 15px; }
        .visa-type-badge { background: rgba(255, 255, 255, 0.18); backdrop-filter: blur(6px); padding: 2px 9px; border-radius: 6px; font-size: 9.5px; font-weight: 800; letter-spacing: 0.5px; border: 1px solid rgba(255, 255, 255, 0.2); }

        .visa-applicant-hero { display: flex; align-items: center; gap: 14px; margin-bottom: 18px; }
        .visa-avatar-wrap { position: relative; flex-shrink: 0; }
        .visa-avatar {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 16px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .visa-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .visa-entry-pill { position: absolute; bottom: -3px; right: -3px; background: #0284c7; color: #ffffff; font-size: 8px; font-weight: 900; padding: 1px 4px; border-radius: 4px; border: 1px solid #ffffff; }
        .visa-applicant-details { flex: 1; min-width: 0; }
        .visa-name { margin: 0; font-size: 14.5px; font-weight: 800; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .visa-submeta { font-size: 11px; color: #bae6fd; margin-top: 2px; }

        .visa-grid-specs { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 12px; padding: 12px 0; border-top: 1px dashed rgba(255, 255, 255, 0.2); border-bottom: 1px dashed rgba(255, 255, 255, 0.2); margin-bottom: 12px; }
        .spec-cell { display: flex; flex-direction: column; gap: 2px; }
        .visa-lbl { font-size: 8px; font-weight: 800; color: #7dd3fc; letter-spacing: 0.5px; }
        .visa-val { font-size: 11px; font-weight: 700; color: #ffffff; }
        .color-cyan { color: #38bdf8 !important; }
        .color-green { color: #86efac !important; }

        .visa-mrv-zone { background: rgba(0, 0, 0, 0.25); padding: 8px 10px; border-radius: 8px; margin-bottom: 12px; border: 1px solid rgba(255, 255, 255, 0.08); }
        .mrv-line { font-size: 8.5px; color: #e0f2fe; letter-spacing: 1.2px; line-height: 1.35; white-space: nowrap; overflow: hidden; text-overflow: clip; }

        .visa-fee-footer { text-align: center; }
        .visa-barcode-lines { height: 18px; background: repeating-linear-gradient(90deg, #ffffff, #ffffff 2px, transparent 2px, transparent 4px, #ffffff 4px, #ffffff 5px, transparent 5px, transparent 8px); opacity: 0.8; margin-bottom: 4px; border-radius: 2px; }
        .visa-barcode-txt { font-size: 8px; color: #7dd3fc; letter-spacing: 1px; }

        /* Left Info Cards */
        .ifs-info-panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 22px; margin-bottom: 22px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); }
        .panel-card-title { margin: 0 0 16px 0; font-size: 14px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
        .panel-card-title .dashicons { color: #0284c7; font-size: 18px; width: 18px; height: 18px; }
        .ifs-panel-table { display: flex; flex-direction: column; gap: 12px; }
        .panel-row { display: flex; justify-content: space-between; align-items: center; font-size: 13px; gap: 10px; }
        .panel-key { color: #64748b; display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0; }
        .panel-key .dashicons { font-size: 14px; width: 14px; height: 14px; color: #94a3b8; }
        .panel-val { font-weight: 700; color: #0f172a; text-align: right; }
        .color-indigo { color: #4f46e5 !important; }

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
            background: #0284c7;
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
        .ifs-btn-view-doc:hover { background: #0369a1; }
        .ifs-btn-view-doc .dashicons { font-size: 13px; width: 13px; height: 13px; }
        .ifs-link-upload-mini { font-size: 12px; font-weight: 700; color: #0284c7; text-decoration: none; }

        /* Right History Container & Tables */
        .ifs-history-container-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 26px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); }
        .ifs-history-header-nav { padding-bottom: 16px; border-bottom: 1px solid #f1f5f9; margin-bottom: 20px; }
        .history-title { margin: 0; font-size: 16px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        .history-title .dashicons { color: #0284c7; font-size: 20px; width: 20px; height: 20px; }

        .ifs-specs-two-col { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; }
        @media (max-width: 640px) { .ifs-specs-two-col { grid-template-columns: 1fr; } }
        .spec-item { display: flex; flex-direction: column; gap: 3px; background: #f8fafc; padding: 12px 16px; border-radius: 10px; border: 1px solid #e2e8f0; }
        .spec-title { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: flex; align-items: center; gap: 4px; }
        .spec-title .dashicons { font-size: 13px; width: 13px; height: 13px; }
        .spec-data { font-size: 14px; font-weight: 800; color: #0f172a; }

        /* Applicant Dossier Card */
        .ifs-passenger-dossier-card { display: flex; align-items: center; gap: 18px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; }
        .dossier-avatar { width: 52px; height: 52px; border-radius: 14px; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; font-weight: 800; font-size: 17px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .dossier-info { flex: 1; }
        .dossier-name { margin: 0 0 8px 0; font-size: 16px; font-weight: 800; }
        .dossier-name a { color: #003376; text-decoration: none; }
        .dossier-meta-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px 16px; font-size: 12.5px; }
        .dossier-meta-grid span { color: #64748b; margin-right: 4px; }
        .dossier-meta-grid a { color: #0284c7; text-decoration: none; }

        /* Checklist Inner Table */
        .ifs-inner-table { width: 100%; border-collapse: collapse; font-size: 13px; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
        .ifs-inner-table thead th { background: #f8fafc; padding: 10px 14px; text-align: left; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        .ifs-inner-table tbody td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .doc-status-pill { font-size: 10.5px; font-weight: 800; padding: 2px 8px; border-radius: 4px; text-transform: uppercase; }
        .doc-received  { background: #dcfce7; color: #15803d; }
        .doc-submitted { background: #e0f2fe; color: #0369a1; }
        .doc-pending   { background: #fef3c7; color: #b45309; }
        .ifs-no-data-strip { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 16px; text-align: center; color: #94a3b8; font-size: 12.5px; }

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
            .ifs-visa-card, .ifs-visa-card * { visibility: visible; }
            .ifs-visa-card {
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