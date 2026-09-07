<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enterprise Ultra-Modern Passenger Dossier & Comprehensive Portfolio View
 * Synchronized with Add/Edit Form schema and fields.
 */
function ifs_terp_customer_view_page() {
    global $wpdb;
    $id       = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
    $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=customers' );

    if ( ! $id ) {
        echo '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Invalid Traveler Profile ID.', 'ifs-travel-erp' ) . '</div>';
        return;
    }

    $table_customers = $wpdb->prefix . 'iterp_customers';
    $customer        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_customers} WHERE id = %d", $id ) );

    if ( ! $customer ) {
        echo '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> ' . esc_html__( 'Passenger portfolio could not be found.', 'ifs-travel-erp' ) . '</div>';
        return;
    }

    // Name formatting & Capitalization
    $raw_name            = ! empty( $customer->full_name ) ? $customer->full_name : trim( ( $customer->passport_given_name ?? '' ) . ' ' . ( $customer->passport_surname ?? '' ) );
    $formatted_full_name = mb_convert_case( trim( (string) $raw_name ), MB_CASE_TITLE, 'UTF-8' );
    $title_prefix        = ! empty( $customer->title ) ? esc_html( ucfirst( strtolower( $customer->title ) ) ) . '. ' : '';
    $display_name        = $title_prefix . esc_html( $formatted_full_name );

    // Related Bookings & Financial Tables
    $table_tickets  = $wpdb->prefix . 'iterp_tickets';
    $table_visa     = $wpdb->prefix . 'iterp_visa_applications';
    $table_hajj     = $wpdb->prefix . 'iterp_hajj_bookings';
    $table_tours    = $wpdb->prefix . 'iterp_tours';
    $table_hotels   = $wpdb->prefix . 'iterp_hotel_bookings';
    $table_invoices = $wpdb->prefix . 'iterp_invoices';

    $air_tickets   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_tickets} WHERE customer_id = %d ORDER BY id DESC", $id ) );
    $visa_apps     = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_visa} WHERE customer_id = %d ORDER BY id DESC", $id ) );
    $hajj_records  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_hajj} WHERE customer_id = %d ORDER BY id DESC", $id ) );
    $tour_records  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_tours} WHERE customer_id = %d ORDER BY id DESC", $id ) );
    $hotel_records = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_hotels} WHERE customer_id = %d ORDER BY id DESC", $id ) );

    // Financial Metrics
    $total_spend    = (float) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(net_total) FROM {$table_invoices} WHERE client_type = 'Customer' AND client_id = %d", $id ) );
    $total_due      = (float) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(due_amount) FROM {$table_invoices} WHERE client_type = 'Customer' AND client_id = %d AND due_amount > 0", $id ) );
    $total_bookings = count( $air_tickets ) + count( $visa_apps ) + count( $hajj_records ) + count( $tour_records ) + count( $hotel_records );

    // Passport Expiry Status
    $has_expiry  = ( ! empty( $customer->passport_expiry ) && $customer->passport_expiry !== '1970-01-01' && $customer->passport_expiry !== '0000-00-00' );
    $badge_class = 'badge-valid';
    $badge_text  = 'Valid Document';
    $days_left   = 0;

    if ( $has_expiry ) {
        $expiry_time = strtotime( $customer->passport_expiry );
        $today_time  = strtotime( current_time( 'Y-m-d' ) );
        $days_left   = (int) ceil( ( $expiry_time - $today_time ) / 86400 );

        if ( $days_left < 0 ) {
            $badge_class = 'badge-expired';
            $badge_text  = 'Expired (' . abs( $days_left ) . 'd ago)';
        } elseif ( $days_left <= 180 ) {
            $badge_class = 'badge-warning';
            $badge_text  = 'Critical: < 6 Mos (' . $days_left . 'd left)';
        } else {
            $badge_class = 'badge-valid';
            $badge_text  = 'Valid (' . date_i18n( 'd M, Y', $expiry_time ) . ')';
        }
    } else {
        $badge_class = 'badge-none';
        $badge_text  = 'No Expiry Set';
    }

    // Monogram Initials
    $parts   = preg_split( '/\s+/', trim( (string) $raw_name ) );
    $initial = ( count( $parts ) > 1 ) ? ( mb_substr( $parts[0], 0, 1 ) . mb_substr( $parts[ count( $parts ) - 1 ], 0, 1 ) ) : mb_substr( (string) $raw_name, 0, 2 );
    $initial = strtoupper( $initial );

    // Passenger Type Code
    $ptype_code = 'ADT';
    if ( ! empty( $customer->passenger_type ) ) {
        if ( $customer->passenger_type === 'Child' ) {
            $ptype_code = 'CHD';
        } elseif ( $customer->passenger_type === 'Infant' ) {
            $ptype_code = 'INF';
        }
    }

    // Meal Label Lookup
    $meals = array(
        'MOML' => 'Halal Muslim Meal (MOML)',
        'AVML' => 'Vegetarian Meal (AVML)',
        'VGML' => 'Strict Vegan (VGML)',
        'CHML' => 'Child Meal (CHML)',
    );
    $meal_label = $meals[ $customer->meal_preference ?? '' ] ?? ( $customer->meal_preference ?: 'MOML (Muslim Meal)' );

    // Wheelchair SSR Lookup
    $wheelchairs = array(
        'NONE' => 'No Wheelchair',
        'WCHR' => 'Basic - Ramp Only (WCHR)',
        'WCHS' => 'Cannot Climb Stairs (WCHS)',
        'WCHC' => 'Cabin Seat - Immobile (WCHC)',
    );
    $wheelchair_label = $wheelchairs[ $customer->wheelchair_ssr ?? '' ] ?? ( $customer->wheelchair_ssr ?: 'None' );
    ?>

    <div class="ifs-view-workspace">
        <header class="ifs-view-header">
            <div class="ifs-header-left">
                <a href="<?php echo esc_url( $base_url . '&sub=list' ); ?>" class="ifs-icon-btn" title="<?php esc_attr_e( 'Back to Customers', 'ifs-travel-erp' ); ?>">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                </a>
                <div class="ifs-header-meta">
                    <div class="ifs-pills-cluster">
                        <span class="ifs-pill font-mono">#CUS-<?php echo esc_html( str_pad( (string) $customer->id, 5, '0', STR_PAD_LEFT ) ); ?></span>
                        <span class="ifs-pill tier-badge tier-<?php echo esc_attr( strtolower( $customer->client_type ?: 'retail' ) ); ?>"><?php echo esc_html( ucfirst( $customer->client_type ?: 'Retail' ) ); ?></span>
                        <span class="ifs-pill"><?php echo esc_html( $customer->passenger_type ?: 'Adult' ); ?></span>
                        <span class="ifs-pill"><span class="dashicons dashicons-flag"></span> <?php echo esc_html( $customer->nationality ?: 'Bangladeshi' ); ?></span>
                    </div>
                    <h1 class="ifs-header-title"><?php echo wp_kses_post( $display_name ); ?></h1>
                </div>
            </div>

            <div class="ifs-header-right">
                <button type="button" onclick="window.print();" class="ifs-btn ifs-btn-default">
                    <span class="dashicons dashicons-printer"></span> <?php esc_html_e( 'Print Dossier', 'ifs-travel-erp' ); ?>
                </button>
                <a href="<?php echo esc_url( $base_url . '&sub=edit&id=' . $id ); ?>" class="ifs-btn ifs-btn-primary">
                    <span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Edit Portfolio', 'ifs-travel-erp' ); ?>
                </a>
                <?php if ( ! empty( $customer->whatsapp_no ) ) : ?>
                    <a href="https://wa.me/<?php echo esc_attr( preg_replace( '/[^0-9]/', '', $customer->whatsapp_no ) ); ?>" target="_blank" rel="noopener noreferrer" class="ifs-btn ifs-btn-whatsapp">
                        <span class="dashicons dashicons-format-chat"></span> <?php esc_html_e( 'WhatsApp', 'ifs-travel-erp' ); ?>
                    </a>
                <?php endif; ?>
                <a href="tel:<?php echo esc_attr( $customer->mobile ); ?>" class="ifs-btn ifs-btn-default">
                    <span class="dashicons dashicons-phone"></span> <?php esc_html_e( 'Call', 'ifs-travel-erp' ); ?>
                </a>
            </div>
        </header>

        <section class="ifs-kpi-grid">
            <div class="ifs-kpi-card">
                <div class="ifs-kpi-icon kpi-blue"><span class="dashicons dashicons-portfolio"></span></div>
                <div class="ifs-kpi-content">
                    <span class="ifs-kpi-label"><?php esc_html_e( 'Total Bookings', 'ifs-travel-erp' ); ?></span>
                    <strong class="ifs-kpi-value"><?php echo esc_html( number_format( $total_bookings ) ); ?> <span class="ifs-kpi-unit">Files</span></strong>
                </div>
            </div>

            <div class="ifs-kpi-card">
                <div class="ifs-kpi-icon kpi-emerald"><span class="dashicons dashicons-money-alt"></span></div>
                <div class="ifs-kpi-content">
                    <span class="ifs-kpi-label"><?php esc_html_e( 'Total Invoiced', 'ifs-travel-erp' ); ?></span>
                    <strong class="ifs-kpi-value text-emerald">৳<?php echo esc_html( number_format( $total_spend, 2 ) ); ?></strong>
                </div>
            </div>

            <div class="ifs-kpi-card">
                <div class="ifs-kpi-icon kpi-rose"><span class="dashicons dashicons-warning"></span></div>
                <div class="ifs-kpi-content">
                    <span class="ifs-kpi-label"><?php esc_html_e( 'Outstanding Due', 'ifs-travel-erp' ); ?></span>
                    <strong class="ifs-kpi-value <?php echo ( $total_due > 0 ) ? 'text-rose' : 'text-muted'; ?>">৳<?php echo esc_html( number_format( $total_due, 2 ) ); ?></strong>
                </div>
            </div>

            <div class="ifs-kpi-card">
                <div class="ifs-kpi-icon kpi-slate"><span class="dashicons dashicons-calendar-alt"></span></div>
                <div class="ifs-kpi-content">
                    <span class="ifs-kpi-label"><?php esc_html_e( 'Member Since', 'ifs-travel-erp' ); ?></span>
                    <strong class="ifs-kpi-value"><?php echo ! empty( $customer->created_at ) ? esc_html( date_i18n( 'd M, Y', strtotime( $customer->created_at ) ) ) : 'N/A'; ?></strong>
                </div>
            </div>
        </section>

        <main class="ifs-dossier-grid">
            
            <aside class="ifs-sidebar-pane">
                <div class="ifs-travel-pass">
                    <div class="pass-notch left"></div>
                    <div class="pass-notch right"></div>

                    <div class="pass-header">
                        <span class="pass-origin"><span class="dashicons dashicons-airplane"></span> <?php echo esc_html( strtoupper( $customer->nationality ?: 'BANGLADESH' ) ); ?></span>
                        <span class="pass-category"><?php echo esc_html( strtoupper( $customer->client_type ?: 'RETAIL' ) ); ?></span>
                    </div>

                    <div class="pass-profile">
                        <div class="pass-avatar">
                            <?php if ( ! empty( $customer->photo_url ) ) : ?>
                                <img src="<?php echo esc_url( $customer->photo_url ); ?>" alt="Avatar" />
                            <?php else : ?>
                                <span><?php echo esc_html( $initial ); ?></span>
                            <?php endif; ?>
                            <span class="pass-type-badge"><?php echo esc_html( $ptype_code ); ?></span>
                        </div>
                        <div class="pass-info">
                            <div class="pass-name"><?php echo wp_kses_post( $display_name ); ?></div>
                            <span class="pass-meta"><?php echo esc_html( ucfirst( $customer->gender ?: 'Male' ) ); ?> &bull; <?php echo esc_html( ucfirst( $customer->marital_status ?: 'Married' ) ); ?> &bull; DOB: <?php echo ( ! empty( $customer->date_of_birth ) && $customer->date_of_birth !== '1970-01-01' ) ? esc_html( date_i18n( 'd M Y', strtotime( $customer->date_of_birth ) ) ) : 'N/A'; ?></span>
                        </div>
                    </div>

                    <div class="pass-details">
                        <div class="detail-cell">
                            <span class="detail-title"><?php esc_html_e( 'PASSPORT NUMBER', 'ifs-travel-erp' ); ?></span>
                            <span class="detail-content font-mono"><?php echo esc_html( $customer->passport_no ?: 'NOT SET' ); ?></span>
                        </div>
                        <div class="detail-cell">
                            <span class="detail-title"><?php esc_html_e( 'VALIDITY', 'ifs-travel-erp' ); ?></span>
                            <span class="detail-content status-indicator <?php echo esc_attr( $badge_class ); ?>"><?php echo esc_html( $badge_text ); ?></span>
                        </div>
                        <div class="detail-cell">
                            <span class="detail-title"><?php esc_html_e( 'PASSPORT TYPE', 'ifs-travel-erp' ); ?></span>
                            <span class="detail-content"><?php echo esc_html( ucfirst( $customer->passport_type ?: 'Regular' ) ); ?></span>
                        </div>
                        <div class="detail-cell">
                            <span class="detail-title"><?php esc_html_e( 'ISSUE PLACE', 'ifs-travel-erp' ); ?></span>
                            <span class="detail-content"><?php echo esc_html( mb_convert_case( $customer->passport_issue_place ?: 'Dhaka', MB_CASE_TITLE, 'UTF-8' ) ); ?></span>
                        </div>
                        <div class="detail-cell">
                            <span class="detail-title"><?php esc_html_e( 'NATIONAL ID (NID)', 'ifs-travel-erp' ); ?></span>
                            <span class="detail-content font-mono"><?php echo esc_html( $customer->nid_no ?: 'N/A' ); ?></span>
                        </div>
                        <div class="detail-cell">
                            <span class="detail-title"><?php esc_html_e( 'BLOOD GROUP', 'ifs-travel-erp' ); ?></span>
                            <span class="detail-content text-rose"><?php echo esc_html( $customer->blood_group ?: 'Unknown' ); ?></span>
                        </div>
                    </div>

                    <?php
                    $surname_clean = strtoupper( (string) preg_replace( '/[^A-Za-z]/', '', (string) ( $customer->passport_surname ?? '' ) ) );
                    $given_clean   = strtoupper( (string) preg_replace( '/[^A-Za-z]/', '<', (string) ( $customer->passport_given_name ?? '' ) ) );
                    if ( empty( $surname_clean ) ) {
                        $parts_mrz     = explode( ' ', trim( (string) $raw_name ) );
                        $surname_clean = ( count( $parts_mrz ) > 1 ) ? strtoupper( (string) array_pop( $parts_mrz ) ) : 'PASSENGER';
                        $given_clean   = strtoupper( implode( '<', $parts_mrz ) ) ?: 'NAME';
                    }
                    $mrz_line1  = 'P<BGD' . $surname_clean . '<<' . $given_clean;
                    $mrz_line1  = str_pad( substr( $mrz_line1, 0, 44 ), 44, '<' );
                    $pass_clean = strtoupper( (string) preg_replace( '/[^A-Za-z0-9]/', '', $customer->passport_no ?: 'A00000000' ) );
                    $mrz_line2  = str_pad( substr( $pass_clean, 0, 9 ), 9, '<' ) . '0BGD0000000M0000000<<<<<<<<<<<<<00';
                    $mrz_line2  = str_pad( substr( $mrz_line2, 0, 44 ), 44, '<' );
                    ?>
                    <div class="pass-mrz-zone font-mono">
                        <div><?php echo esc_html( $mrz_line1 ); ?></div>
                        <div><?php echo esc_html( $mrz_line2 ); ?></div>
                    </div>

                    <div class="pass-barcode-zone">
                        <div class="barcode-graphic"></div>
                        <span class="barcode-legend font-mono"><?php esc_html_e( 'IATCI • ELECTRONIC RECORD VALIDATED', 'ifs-travel-erp' ); ?></span>
                    </div>
                </div>

                <div class="ifs-card">
                    <div class="ifs-card-header">
                        <span class="dashicons dashicons-admin-users"></span>
                        <h3><?php esc_html_e( 'Traveler Demographics', 'ifs-travel-erp' ); ?></h3>
                    </div>

                    <div class="ifs-demographics-container">
                        <div class="demo-group">
                            <span class="demo-group-title"><?php esc_html_e( '01. Direct Communication Channels', 'ifs-travel-erp' ); ?></span>
                            <div class="demo-grid-2">
                                <div class="demo-cell">
                                    <span class="demo-label"><span class="dashicons dashicons-phone"></span> <?php esc_html_e( 'Primary Mobile', 'ifs-travel-erp' ); ?></span>
                                    <span class="demo-val"><a href="tel:<?php echo esc_attr( $customer->mobile ); ?>" class="font-mono"><?php echo esc_html( $customer->mobile ?: '—' ); ?></a></span>
                                </div>
                                <div class="demo-cell">
                                    <span class="demo-label"><span class="dashicons dashicons-format-chat"></span> <?php esc_html_e( 'WhatsApp', 'ifs-travel-erp' ); ?></span>
                                    <span class="demo-val">
                                        <?php if ( ! empty( $customer->whatsapp_no ) ) : ?>
                                            <a href="https://wa.me/<?php echo esc_attr( preg_replace( '/[^0-9]/', '', $customer->whatsapp_no ) ); ?>" target="_blank" rel="noopener noreferrer" class="font-mono text-emerald"><?php echo esc_html( $customer->whatsapp_no ); ?></a>
                                        <?php else : ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="demo-cell">
                                    <span class="demo-label"><span class="dashicons dashicons-email"></span> <?php esc_html_e( 'Email Address', 'ifs-travel-erp' ); ?></span>
                                    <span class="demo-val"><?php echo esc_html( $customer->email ?: '—' ); ?></span>
                                </div>
                                <div class="demo-cell">
                                    <span class="demo-label"><span class="dashicons dashicons-warning"></span> <?php esc_html_e( 'Emergency Contact', 'ifs-travel-erp' ); ?></span>
                                    <span class="demo-val font-mono text-rose"><?php echo esc_html( $customer->emergency_contact ?: 'Not Provided' ); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="demo-group">
                            <span class="demo-group-title"><?php esc_html_e( '02. Personal & Lineage Profile', 'ifs-travel-erp' ); ?></span>
                            <div class="demo-grid-2">
                                <div class="demo-cell">
                                    <span class="demo-label"><span class="dashicons dashicons-id"></span> <?php esc_html_e( 'Given / First Name', 'ifs-travel-erp' ); ?></span>
                                    <span class="demo-val"><?php echo esc_html( mb_convert_case( $customer->passport_given_name ?: ( $parts[0] ?? '—' ), MB_CASE_TITLE, 'UTF-8' ) ); ?></span>
                                </div>
                                <div class="demo-cell">
                                    <span class="demo-label"><span class="dashicons dashicons-id-alt"></span> <?php esc_html_e( 'Surname / Last Name', 'ifs-travel-erp' ); ?></span>
                                    <span class="demo-val"><?php echo esc_html( mb_convert_case( $customer->passport_surname ?: ( count( $parts ) > 1 ? $parts[ count( $parts ) - 1 ] : '—' ), MB_CASE_TITLE, 'UTF-8' ) ); ?></span>
                                </div>
                                <div class="demo-cell">
                                    <span class="demo-label"><span class="dashicons dashicons-businessman"></span> <?php esc_html_e( 'Father / Spouse', 'ifs-travel-erp' ); ?></span>
                                    <span class="demo-val"><?php echo esc_html( mb_convert_case( $customer->father_spouse_name ?: 'Not Specified', MB_CASE_TITLE, 'UTF-8' ) ); ?></span>
                                </div>
                                <div class="demo-cell">
                                    <span class="demo-label"><span class="dashicons dashicons-heart"></span> <?php esc_html_e( "Mother's Name", 'ifs-travel-erp' ); ?></span>
                                    <span class="demo-val"><?php echo esc_html( mb_convert_case( $customer->mother_name ?: 'Not Specified', MB_CASE_TITLE, 'UTF-8' ) ); ?></span>
                                </div>
                                <div class="demo-cell">
                                    <span class="demo-label"><span class="dashicons dashicons-calendar-alt"></span> <?php esc_html_e( 'Date of Birth', 'ifs-travel-erp' ); ?></span>
                                    <span class="demo-val"><?php echo ( ! empty( $customer->date_of_birth ) && $customer->date_of_birth !== '1970-01-01' ) ? esc_html( date_i18n( 'd M, Y', strtotime( $customer->date_of_birth ) ) ) : '—'; ?></span>
                                </div>
                                <div class="demo-cell">
                                    <span class="demo-label"><span class="dashicons dashicons-location"></span> <?php esc_html_e( 'Place of Birth', 'ifs-travel-erp' ); ?></span>
                                    <span class="demo-val"><?php echo esc_html( mb_convert_case( $customer->birth_place ?: 'Bangladesh', MB_CASE_TITLE, 'UTF-8' ) ); ?></span>
                                </div>
                                <div class="demo-cell">
                                    <span class="demo-label"><span class="dashicons dashicons-portfolio"></span> <?php esc_html_e( 'Profession', 'ifs-travel-erp' ); ?></span>
                                    <span class="demo-val"><?php echo esc_html( mb_convert_case( $customer->profession ?: 'General', MB_CASE_TITLE, 'UTF-8' ) ); ?></span>
                                </div>
                                <div class="demo-cell">
                                    <span class="demo-label"><span class="dashicons dashicons-sos"></span> <?php esc_html_e( 'Blood Group', 'ifs-travel-erp' ); ?></span>
                                    <span class="demo-val"><span class="demo-pill blood-pill"><?php echo esc_html( $customer->blood_group ?: 'Unknown' ); ?></span></span>
                                </div>
                                <div class="demo-cell col-span-full">
                                    <span class="demo-label"><span class="dashicons dashicons-text"></span> <?php esc_html_e( 'National ID (NID)', 'ifs-travel-erp' ); ?></span>
                                    <span class="demo-val font-mono"><?php echo esc_html( $customer->nid_no ?: '—' ); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="demo-group">
                            <span class="demo-group-title"><?php esc_html_e( '03. Aviation & SSR Preferences', 'ifs-travel-erp' ); ?></span>
                            <div class="demo-grid-2">
                                <div class="demo-cell">
                                    <span class="demo-label"><span class="dashicons dashicons-coffee"></span> <?php esc_html_e( 'Meal Choice', 'ifs-travel-erp' ); ?></span>
                                    <span class="demo-val"><span class="demo-pill"><?php echo esc_html( $meal_label ); ?></span></span>
                                </div>
                                <div class="demo-cell">
                                    <span class="demo-label"><span class="dashicons dashicons-wheelchair"></span> <?php esc_html_e( 'Wheelchair SSR', 'ifs-travel-erp' ); ?></span>
                                    <span class="demo-val">
                                        <span class="demo-pill <?php echo ( ! empty( $customer->wheelchair_ssr ) && $customer->wheelchair_ssr !== 'NONE' ) ? 'pill-warning' : ''; ?>">
                                            <?php echo esc_html( $wheelchair_label ); ?>
                                        </span>
                                    </span>
                                </div>
                                <div class="demo-cell col-span-full">
                                    <span class="demo-label"><span class="dashicons dashicons-star-filled"></span> <?php esc_html_e( 'Frequent Flyer Number', 'ifs-travel-erp' ); ?></span>
                                    <span class="demo-val font-mono"><?php echo esc_html( $customer->frequent_flyer_no ?: 'None Registered' ); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="demo-group" style="margin-bottom: 0;">
                            <span class="demo-group-title"><?php esc_html_e( '04. Permanent Address', 'ifs-travel-erp' ); ?></span>
                            <div class="demo-address-box">
                                <span class="dashicons dashicons-admin-home address-icon"></span>
                                <div>
                                    <?php 
                                    echo esc_html( $customer->city ? mb_convert_case( $customer->city, MB_CASE_TITLE, 'UTF-8' ) . ', ' : '' ); 
                                    echo nl2br( esc_html( $customer->address ?: 'No address specified on record.' ) ); 
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <section class="ifs-main-pane">

                <div class="ifs-card">
                    <div class="ifs-card-header">
                        <span class="dashicons dashicons-book-alt"></span>
                        <h3><?php esc_html_e( 'Passport Lifecycle & Travel Documentation', 'ifs-travel-erp' ); ?></h3>
                    </div>

                    <div class="ifs-passport-matrix-grid">
                        <div class="matrix-card">
                            <span class="matrix-lbl"><?php esc_html_e( 'Current Passport No', 'ifs-travel-erp' ); ?></span>
                            <strong class="matrix-val font-mono"><?php echo esc_html( $customer->passport_no ?: 'NOT REGISTERED' ); ?></strong>
                        </div>
                        <div class="matrix-card">
                            <span class="matrix-lbl"><?php esc_html_e( 'Previous Passport No', 'ifs-travel-erp' ); ?></span>
                            <strong class="matrix-val font-mono"><?php echo esc_html( $customer->prev_passport_no ?: 'None On File' ); ?></strong>
                        </div>
                        <div class="matrix-card">
                            <span class="matrix-lbl"><?php esc_html_e( 'Passport Document Type', 'ifs-travel-erp' ); ?></span>
                            <strong class="matrix-val"><?php echo esc_html( ucfirst( $customer->passport_type ?: 'Regular' ) ); ?></strong>
                        </div>
                        <div class="matrix-card">
                            <span class="matrix-lbl"><?php esc_html_e( 'Issuing Authority / Place', 'ifs-travel-erp' ); ?></span>
                            <strong class="matrix-val"><?php echo esc_html( mb_convert_case( $customer->passport_issue_place ?: 'Dhaka', MB_CASE_TITLE, 'UTF-8' ) ); ?></strong>
                        </div>
                        <div class="matrix-card">
                            <span class="matrix-lbl"><?php esc_html_e( 'Passport Issue Date', 'ifs-travel-erp' ); ?></span>
                            <strong class="matrix-val"><?php echo ( ! empty( $customer->passport_issue_date ) && $customer->passport_issue_date !== '1970-01-01' ) ? esc_html( date_i18n( 'd M, Y', strtotime( $customer->passport_issue_date ) ) ) : 'Not Recorded'; ?></strong>
                        </div>
                        <div class="matrix-card">
                            <span class="matrix-lbl"><?php esc_html_e( 'Passport Expiry Date', 'ifs-travel-erp' ); ?></span>
                            <strong class="matrix-val <?php echo ( $has_expiry && $days_left <= 180 ) ? 'text-rose' : ''; ?>">
                                <?php echo ( ! empty( $customer->passport_expiry ) && $customer->passport_expiry !== '1970-01-01' ) ? esc_html( date_i18n( 'd M, Y', strtotime( $customer->passport_expiry ) ) ) : 'Not Set'; ?>
                            </strong>
                        </div>
                    </div>
                </div>

                <div class="ifs-card">
                    <div class="ifs-card-header">
                        <span class="dashicons dashicons-media-document"></span>
                        <h3><?php esc_html_e( 'Attached Digital Vault Records', 'ifs-travel-erp' ); ?></h3>
                    </div>

                    <div class="ifs-vault-grid">
                        <div class="ifs-vault-card">
                            <div class="vault-thumb">
                                <?php if ( ! empty( $customer->passport_copy_url ) ) : ?>
                                    <?php if ( preg_match( '/\.(jpg|jpeg|png|webp|gif)$/i', $customer->passport_copy_url ) ) : ?>
                                        <img src="<?php echo esc_url( $customer->passport_copy_url ); ?>" alt="Passport" />
                                    <?php else : ?>
                                        <div class="vault-pdf-icon"><span class="dashicons dashicons-pdf"></span><small>PDF</small></div>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <span class="dashicons dashicons-media-document text-muted"></span>
                                <?php endif; ?>
                            </div>
                            <div class="vault-details">
                                <span class="vault-title"><?php esc_html_e( 'Passport Bio-Page', 'ifs-travel-erp' ); ?></span>
                                <span class="vault-status <?php echo ! empty( $customer->passport_copy_url ) ? 'status-ok' : 'status-empty'; ?>">
                                    <?php echo ! empty( $customer->passport_copy_url ) ? esc_html__( 'Attached & Validated', 'ifs-travel-erp' ) : esc_html__( 'Missing Document', 'ifs-travel-erp' ); ?>
                                </span>
                            </div>
                            <div class="vault-action">
                                <?php if ( ! empty( $customer->passport_copy_url ) ) : ?>
                                    <button type="button" class="ifs-btn-sm open-image-modal" data-img="<?php echo esc_url( $customer->passport_copy_url ); ?>" data-title="<?php esc_attr_e( 'Passport Scan Preview', 'ifs-travel-erp' ); ?>">
                                        <span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Preview', 'ifs-travel-erp' ); ?>
                                    </button>
                                <?php else : ?>
                                    <a href="<?php echo esc_url( $base_url . '&sub=edit&id=' . $id ); ?>" class="ifs-link-upload"><?php esc_html_e( 'Upload', 'ifs-travel-erp' ); ?></a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="ifs-vault-card">
                            <div class="vault-thumb">
                                <?php if ( ! empty( $customer->nid_copy_url ) ) : ?>
                                    <?php if ( preg_match( '/\.(jpg|jpeg|png|webp|gif)$/i', $customer->nid_copy_url ) ) : ?>
                                        <img src="<?php echo esc_url( $customer->nid_copy_url ); ?>" alt="NID" />
                                    <?php else : ?>
                                        <div class="vault-pdf-icon"><span class="dashicons dashicons-pdf"></span><small>PDF</small></div>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <span class="dashicons dashicons-id-alt text-muted"></span>
                                <?php endif; ?>
                            </div>
                            <div class="vault-details">
                                <span class="vault-title"><?php esc_html_e( 'National ID / Visa Document', 'ifs-travel-erp' ); ?></span>
                                <span class="vault-status <?php echo ! empty( $customer->nid_copy_url ) ? 'status-ok' : 'status-empty'; ?>">
                                    <?php echo ! empty( $customer->nid_copy_url ) ? esc_html__( 'Attached & Validated', 'ifs-travel-erp' ) : esc_html__( 'Missing Document', 'ifs-travel-erp' ); ?>
                                </span>
                            </div>
                            <div class="vault-action">
                                <?php if ( ! empty( $customer->nid_copy_url ) ) : ?>
                                    <button type="button" class="ifs-btn-sm open-image-modal" data-img="<?php echo esc_url( $customer->nid_copy_url ); ?>" data-title="<?php esc_attr_e( 'National ID / Document Preview', 'ifs-travel-erp' ); ?>">
                                        <span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Preview', 'ifs-travel-erp' ); ?>
                                    </button>
                                <?php else : ?>
                                    <a href="<?php echo esc_url( $base_url . '&sub=edit&id=' . $id ); ?>" class="ifs-link-upload"><?php esc_html_e( 'Upload', 'ifs-travel-erp' ); ?></a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="ifs-vault-card">
                            <div class="vault-thumb">
                                <?php if ( ! empty( $customer->photo_url ) ) : ?>
                                    <img src="<?php echo esc_url( $customer->photo_url ); ?>" alt="Portrait" />
                                <?php else : ?>
                                    <span class="dashicons dashicons-camera text-muted"></span>
                                <?php endif; ?>
                            </div>
                            <div class="vault-details">
                                <span class="vault-title"><?php esc_html_e( 'Passenger Portrait', 'ifs-travel-erp' ); ?></span>
                                <span class="vault-status <?php echo ! empty( $customer->photo_url ) ? 'status-ok' : 'status-empty'; ?>">
                                    <?php echo ! empty( $customer->photo_url ) ? esc_html__( 'Attached', 'ifs-travel-erp' ) : esc_html__( 'Missing Photo', 'ifs-travel-erp' ); ?>
                                </span>
                            </div>
                            <div class="vault-action">
                                <?php if ( ! empty( $customer->photo_url ) ) : ?>
                                    <button type="button" class="ifs-btn-sm open-image-modal" data-img="<?php echo esc_url( $customer->photo_url ); ?>" data-title="<?php esc_attr_e( 'Passenger Portrait Preview', 'ifs-travel-erp' ); ?>">
                                        <span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Preview', 'ifs-travel-erp' ); ?>
                                    </button>
                                <?php else : ?>
                                    <a href="<?php echo esc_url( $base_url . '&sub=edit&id=' . $id ); ?>" class="ifs-link-upload"><?php esc_html_e( 'Upload', 'ifs-travel-erp' ); ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ifs-card">
                    <div class="ifs-card-header">
                        <span class="dashicons dashicons-backup"></span>
                        <h3><?php esc_html_e( 'Travel History & Service Operations', 'ifs-travel-erp' ); ?></h3>
                    </div>

                    <div class="ifs-sub-section">
                        <div class="ifs-sub-header">
                            <span class="dashicons dashicons-airplane"></span>
                            <h4><?php echo sprintf( esc_html__( 'Flight Bookings & Issued E-Tickets (%d)', 'ifs-travel-erp' ), count( $air_tickets ) ); ?></h4>
                        </div>
                        <?php if ( ! empty( $air_tickets ) ) : ?>
                            <div class="ifs-table-container">
                                <table class="ifs-table">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e( 'Issue Date', 'ifs-travel-erp' ); ?></th>
                                            <th><?php esc_html_e( 'PNR', 'ifs-travel-erp' ); ?></th>
                                            <th><?php esc_html_e( 'Ticket No', 'ifs-travel-erp' ); ?></th>
                                            <th><?php esc_html_e( 'Sector', 'ifs-travel-erp' ); ?></th>
                                            <th><?php esc_html_e( 'Travel Date', 'ifs-travel-erp' ); ?></th>
                                            <th class="text-right"><?php esc_html_e( 'Fare (৳)', 'ifs-travel-erp' ); ?></th>
                                            <th class="text-center"><?php esc_html_e( 'Status', 'ifs-travel-erp' ); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ( $air_tickets as $ticket ) : ?>
                                            <tr>
                                                <td><?php echo esc_html( date_i18n( 'd M, Y', strtotime( $ticket->created_at ) ) ); ?></td>
                                                <td><span class="font-mono text-bold"><?php echo esc_html( $ticket->pnr ); ?></span></td>
                                                <td class="font-mono text-muted"><?php echo esc_html( $ticket->ticket_no ?: '—' ); ?></td>
                                                <td><strong><?php echo esc_html( $ticket->sector ); ?></strong></td>
                                                <td><?php echo esc_html( date_i18n( 'd M, Y', strtotime( $ticket->travel_date ) ) ); ?></td>
                                                <td class="text-right font-mono text-bold">৳<?php echo esc_html( number_format( (float) $ticket->sell_price, 2 ) ); ?></td>
                                                <td class="text-center"><span class="ifs-status-pill status-<?php echo esc_attr( strtolower( $ticket->status ) ); ?>"><?php echo esc_html( $ticket->status ); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else : ?>
                            <div class="ifs-empty-record"><?php esc_html_e( 'No flight bookings registered for this passenger.', 'ifs-travel-erp' ); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="ifs-sub-section">
                        <div class="ifs-sub-header">
                            <span class="dashicons dashicons-id-alt"></span>
                            <h4><?php echo sprintf( esc_html__( 'Visa Applications & Processing Status (%d)', 'ifs-travel-erp' ), count( $visa_apps ) ); ?></h4>
                        </div>
                        <?php if ( ! empty( $visa_apps ) ) : ?>
                            <div class="ifs-table-container">
                                <table class="ifs-table">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e( 'Applied Date', 'ifs-travel-erp' ); ?></th>
                                            <th><?php esc_html_e( 'Country', 'ifs-travel-erp' ); ?></th>
                                            <th><?php esc_html_e( 'Visa Type', 'ifs-travel-erp' ); ?></th>
                                            <th><?php esc_html_e( 'Embassy Tracking / Ref', 'ifs-travel-erp' ); ?></th>
                                            <th class="text-right"><?php esc_html_e( 'Fee (৳)', 'ifs-travel-erp' ); ?></th>
                                            <th class="text-center"><?php esc_html_e( 'Status', 'ifs-travel-erp' ); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ( $visa_apps as $v ) : ?>
                                            <tr>
                                                <td><?php echo esc_html( date_i18n( 'd M, Y', strtotime( $v->created_at ) ) ); ?></td>
                                                <td><strong><?php echo esc_html( $v->country ); ?></strong></td>
                                                <td><?php echo esc_html( $v->visa_type ); ?></td>
                                                <td class="font-mono text-muted"><?php echo esc_html( $v->tracking_no ?: '—' ); ?></td>
                                                <td class="text-right font-mono text-bold">৳<?php echo esc_html( number_format( (float) $v->sell_price, 2 ) ); ?></td>
                                                <td class="text-center"><span class="ifs-status-pill status-<?php echo esc_attr( strtolower( $v->status ) ); ?>"><?php echo esc_html( $v->status ); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else : ?>
                            <div class="ifs-empty-record"><?php esc_html_e( 'No visa applications on record.', 'ifs-travel-erp' ); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="ifs-sub-section">
                        <div class="ifs-sub-header">
                            <span class="dashicons dashicons-awards"></span>
                            <h4><?php echo sprintf( esc_html__( 'Hajj & Umrah Pilgrimages (%d)', 'ifs-travel-erp' ), count( $hajj_records ) ); ?></h4>
                        </div>
                        <?php if ( ! empty( $hajj_records ) ) : ?>
                            <div class="ifs-table-container">
                                <table class="ifs-table">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e( 'Booking Date', 'ifs-travel-erp' ); ?></th>
                                            <th><?php esc_html_e( 'BRN No', 'ifs-travel-erp' ); ?></th>
                                            <th><?php esc_html_e( 'Sharing Scheme', 'ifs-travel-erp' ); ?></th>
                                            <th><?php esc_html_e( 'Flight Date', 'ifs-travel-erp' ); ?></th>
                                            <th class="text-right"><?php esc_html_e( 'Package (৳)', 'ifs-travel-erp' ); ?></th>
                                            <th class="text-center"><?php esc_html_e( 'Status', 'ifs-travel-erp' ); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ( $hajj_records as $h ) : ?>
                                            <tr>
                                                <td><?php echo esc_html( date_i18n( 'd M, Y', strtotime( $h->created_at ) ) ); ?></td>
                                                <td><span class="font-mono text-bold"><?php echo esc_html( $h->brn_no ?: '—' ); ?></span></td>
                                                <td><?php echo esc_html( $h->room_sharing ); ?></td>
                                                <td><?php echo esc_html( date_i18n( 'd M, Y', strtotime( $h->flight_date ) ) ); ?></td>
                                                <td class="text-right font-mono text-bold">৳<?php echo esc_html( number_format( (float) $h->sell_price, 2 ) ); ?></td>
                                                <td class="text-center"><span class="ifs-status-pill status-<?php echo esc_attr( strtolower( $h->status ) ); ?>"><?php echo esc_html( $h->status ); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else : ?>
                            <div class="ifs-empty-record"><?php esc_html_e( 'No religious pilgrimage accounts registered.', 'ifs-travel-erp' ); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="ifs-sub-section" style="margin-bottom: 0;">
                        <div class="ifs-sub-header">
                            <span class="dashicons dashicons-palmtree"></span>
                            <h4><?php echo sprintf( esc_html__( 'Holiday Tours & Hospitality (%d)', 'ifs-travel-erp' ), count( $tour_records ) + count( $hotel_records ) ); ?></h4>
                        </div>
                        <?php if ( ! empty( $tour_records ) || ! empty( $hotel_records ) ) : ?>
                            <div class="ifs-table-container">
                                <table class="ifs-table">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e( 'Service Type', 'ifs-travel-erp' ); ?></th>
                                            <th><?php esc_html_e( 'Package / Property Name', 'ifs-travel-erp' ); ?></th>
                                            <th><?php esc_html_e( 'Destination / City', 'ifs-travel-erp' ); ?></th>
                                            <th><?php esc_html_e( 'Schedule / Dates', 'ifs-travel-erp' ); ?></th>
                                            <th class="text-right"><?php esc_html_e( 'Amount (৳)', 'ifs-travel-erp' ); ?></th>
                                            <th class="text-center"><?php esc_html_e( 'Status', 'ifs-travel-erp' ); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ( $tour_records as $tr ) : ?>
                                            <tr>
                                                <td><span class="ifs-badge-indicator tour"><?php esc_html_e( 'Tour', 'ifs-travel-erp' ); ?></span></td>
                                                <td><strong><?php echo esc_html( $tr->package_title ); ?></strong></td>
                                                <td><?php echo esc_html( $tr->destination ); ?></td>
                                                <td><?php echo esc_html( date_i18n( 'd M, Y', strtotime( $tr->travel_date ) ) ); ?></td>
                                                <td class="text-right font-mono text-bold">৳<?php echo esc_html( number_format( (float) $tr->sell_price, 2 ) ); ?></td>
                                                <td class="text-center"><span class="ifs-status-pill status-confirmed"><?php echo esc_html( $tr->status ); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php foreach ( $hotel_records as $ht ) : ?>
                                            <tr>
                                                <td><span class="ifs-badge-indicator hotel"><?php esc_html_e( 'Hotel', 'ifs-travel-erp' ); ?></span></td>
                                                <td><strong><?php echo esc_html( $ht->hotel_name ); ?></strong> <span class="text-muted">(<?php echo esc_html( $ht->room_type ); ?>)</span></td>
                                                <td><?php echo esc_html( $ht->city ); ?></td>
                                                <td><?php echo esc_html( date_i18n( 'd M', strtotime( $ht->check_in ) ) . ' – ' . date_i18n( 'd M, Y', strtotime( $ht->check_out ) ) ); ?></td>
                                                <td class="text-right font-mono text-bold">৳<?php echo esc_html( number_format( (float) $ht->sell_price, 2 ) ); ?></td>
                                                <td class="text-center"><span class="ifs-status-pill status-confirmed"><?php echo esc_html( $ht->status ); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else : ?>
                            <div class="ifs-empty-record"><?php esc_html_e( 'No tour packages or hotel stays booked yet.', 'ifs-travel-erp' ); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

            </section>
        </main>
    </div>

    <div id="ifsImageModal" class="ifs-modal-root" style="display: none;">
        <div class="ifs-modal-backdrop"></div>
        <div class="ifs-modal-container">
            <div class="ifs-modal-content">
                <header class="ifs-modal-header">
                    <h3 id="modalImageTitle"><span class="dashicons dashicons-format-image"></span> <?php esc_html_e( 'Document Preview', 'ifs-travel-erp' ); ?></h3>
                    <button type="button" class="ifs-modal-close" id="btnCloseImageModal">&times;</button>
                </header>
                <div class="ifs-modal-body">
                    <img id="modalImagePreviewSrc" src="" alt="<?php esc_attr_e( 'Document Preview', 'ifs-travel-erp' ); ?>" />
                </div>
                <footer class="ifs-modal-footer">
                    <a id="modalImageDownloadBtn" href="" target="_blank" rel="noopener noreferrer" class="ifs-btn ifs-btn-default"><span class="dashicons dashicons-external"></span> <?php esc_html_e( 'Open Original', 'ifs-travel-erp' ); ?></a>
                    <button type="button" class="ifs-btn ifs-btn-primary" id="btnFooterCloseImageModal"><?php esc_html_e( 'Dismiss', 'ifs-travel-erp' ); ?></button>
                </footer>
            </div>
        </div>
    </div>

    <style>
        :root {
            --ifs-navy-950: #02122c;
            --ifs-navy-900: #0a1f44;
            --ifs-navy-800: #0b2d6b;
            --ifs-primary: #0284c7;
            --ifs-slate-50: #f8fafc;
            --ifs-slate-100: #f1f5f9;
            --ifs-slate-200: #e2e8f0;
            --ifs-slate-300: #cbd5e1;
            --ifs-slate-400: #94a3b8;
            --ifs-slate-600: #475569;
            --ifs-slate-700: #334155;
            --ifs-slate-800: #1e293b;
            --ifs-slate-900: #0f172a;
            --ifs-emerald: #059669;
            --ifs-rose: #e11d48;
            --ifs-radius: 12px;
        }

        .ifs-view-workspace {
            max-width: 1440px;
            margin: 18px auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            color: var(--ifs-slate-800);
            -webkit-font-smoothing: antialiased;
        }

        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
        .text-bold { font-weight: 700; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-emerald { color: var(--ifs-emerald) !important; }
        .text-rose { color: var(--ifs-rose) !important; }
        .text-muted { color: var(--ifs-slate-400) !important; }

        .ifs-view-header {
            background: #ffffff;
            border: 1px solid var(--ifs-slate-200);
            border-radius: var(--ifs-radius);
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
        .ifs-header-left { display: flex; align-items: center; gap: 16px; }
        .ifs-icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: var(--ifs-slate-50);
            border: 1px solid var(--ifs-slate-200);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--ifs-slate-700);
            text-decoration: none;
            transition: all 0.15s ease-in-out;
        }
        .ifs-icon-btn:hover { background: var(--ifs-slate-200); color: var(--ifs-slate-900); }
        .ifs-pills-cluster { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; flex-wrap: wrap; }
        .ifs-pill {
            font-size: 11px;
            font-weight: 600;
            background: var(--ifs-slate-100);
            color: var(--ifs-slate-600);
            padding: 2px 8px;
            border-radius: 4px;
            border: 1px solid var(--ifs-slate-200);
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .ifs-pill .dashicons { font-size: 13px; width: 13px; height: 13px; }
        .tier-badge { text-transform: uppercase; font-weight: 700; }
        .tier-retail { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
        .tier-corporate { background: #eff6ff; color: #1e40af; border-color: #bfdbfe; }
        .tier-vip { background: #faf5ff; color: #6b21a8; border-color: #e9d5ff; }
        .ifs-header-title { margin: 0; font-size: 20px; font-weight: 800; color: var(--ifs-slate-900); letter-spacing: -0.02em; }
        .ifs-header-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

        .ifs-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease;
            line-height: 1.2;
            border: 1px solid transparent;
        }
        .ifs-btn .dashicons { font-size: 15px; width: 15px; height: 15px; }
        .ifs-btn-default { background: #ffffff; border-color: var(--ifs-slate-300); color: var(--ifs-slate-700); }
        .ifs-btn-default:hover { background: var(--ifs-slate-50); border-color: var(--ifs-slate-400); color: var(--ifs-slate-900); }
        .ifs-btn-primary { background: var(--ifs-navy-900); color: #ffffff; }
        .ifs-btn-primary:hover { background: var(--ifs-navy-800); color: #ffffff; }
        .ifs-btn-whatsapp { background: #ecfdf5; border-color: #a7f3d0; color: #065f46; }
        .ifs-btn-whatsapp:hover { background: #d1fae5; border-color: #6ee7b7; color: #064e3b; }
        .ifs-btn-sm { padding: 4px 10px; font-size: 12px; font-weight: 600; border-radius: 4px; background: var(--ifs-slate-100); border: 1px solid var(--ifs-slate-300); color: var(--ifs-slate-700); cursor: pointer; display: inline-flex; align-items: center; gap: 4px; }
        .ifs-btn-sm:hover { background: var(--ifs-navy-900); color: #ffffff; border-color: var(--ifs-navy-900); }
        .ifs-btn-sm .dashicons { font-size: 13px; width: 13px; height: 13px; }

        .ifs-kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 20px; }
        .ifs-kpi-card {
            background: #ffffff;
            border: 1px solid var(--ifs-slate-200);
            border-radius: var(--ifs-radius);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
        .ifs-kpi-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .ifs-kpi-icon.kpi-blue { background: #eff6ff; color: #2563eb; }
        .ifs-kpi-icon.kpi-emerald { background: #ecfdf5; color: var(--ifs-emerald); }
        .ifs-kpi-icon.kpi-rose { background: #fff1f2; color: var(--ifs-rose); }
        .ifs-kpi-icon.kpi-slate { background: var(--ifs-slate-100); color: var(--ifs-slate-600); }
        .ifs-kpi-icon .dashicons { font-size: 22px; width: 22px; height: 22px; }
        .ifs-kpi-label { font-size: 11px; font-weight: 700; color: var(--ifs-slate-400); text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 2px; }
        .ifs-kpi-value { font-size: 18px; font-weight: 800; color: var(--ifs-slate-900); display: block; }
        .ifs-kpi-unit { font-size: 13px; font-weight: 500; color: var(--ifs-slate-600); }

        .ifs-dossier-grid { display: grid; grid-template-columns: 380px 1fr; gap: 20px; align-items: flex-start; }
        @media (max-width: 1120px) { .ifs-dossier-grid { grid-template-columns: 1fr; } }

        .ifs-card {
            background: #ffffff;
            border: 1px solid var(--ifs-slate-200);
            border-radius: var(--ifs-radius);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
        .ifs-card-header {
            display: flex;
            align-items: center;
            gap: 8px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--ifs-slate-100);
            margin-bottom: 16px;
        }
        .ifs-card-header h3 { margin: 0; font-size: 14px; font-weight: 800; color: var(--ifs-slate-900); letter-spacing: -0.01em; text-transform: uppercase; }
        .ifs-card-header .dashicons { font-size: 16px; width: 16px; height: 16px; color: var(--ifs-primary); }

        .ifs-travel-pass {
            background: linear-gradient(135deg, var(--ifs-navy-950) 0%, var(--ifs-navy-900) 60%, #063462 100%);
            border-radius: var(--ifs-radius);
            padding: 22px;
            color: #ffffff;
            position: relative;
            box-shadow: 0 10px 25px -5px rgba(2, 18, 44, 0.35);
            margin-bottom: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .pass-notch {
            position: absolute;
            width: 14px;
            height: 24px;
            background: #f0f0f1;
            top: 65%;
            z-index: 2;
        }
        .pass-notch.left { left: 0; border-radius: 0 14px 14px 0; }
        .pass-notch.right { right: 0; border-radius: 14px 0 0 14px; }
        .pass-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 14px; border-bottom: 1px solid rgba(255, 255, 255, 0.12); margin-bottom: 16px; }
        .pass-origin { font-size: 11px; font-weight: 800; letter-spacing: 0.08em; color: #7dd3fc; display: flex; align-items: center; gap: 6px; }
        .pass-origin .dashicons { font-size: 14px; width: 14px; height: 14px; }
        .pass-category { font-size: 9px; font-weight: 800; background: rgba(255, 255, 255, 0.15); padding: 2px 6px; border-radius: 3px; letter-spacing: 0.05em; }
        .pass-profile { display: flex; align-items: center; gap: 14px; margin-bottom: 18px; }
        .pass-avatar {
            width: 52px;
            height: 52px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 800;
            position: relative;
            flex-shrink: 0;
            overflow: hidden;
        }
        .pass-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .pass-type-badge { position: absolute; bottom: 0; right: 0; background: var(--ifs-primary); font-size: 8px; font-weight: 900; padding: 1px 3px; border-radius: 2px; }
        .pass-name { font-size: 15px; font-weight: 800; color: #ffffff; line-height: 1.2; }
        .pass-meta { font-size: 11px; color: #93c5fd; margin-top: 4px; display: block; }
        .pass-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 10px;
            padding: 14px 0;
            border-top: 1px dashed rgba(255, 255, 255, 0.18);
            border-bottom: 1px dashed rgba(255, 255, 255, 0.18);
            margin-bottom: 14px;
        }
        .detail-title { font-size: 9px; font-weight: 700; color: #7dd3fc; letter-spacing: 0.05em; display: block; margin-bottom: 2px; }
        .detail-content { font-size: 12px; font-weight: 700; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }
        .status-indicator.badge-valid { color: #86efac; }
        .status-indicator.badge-warning { color: #fde047; }
        .status-indicator.badge-expired { color: #fca5a5; }
        .pass-mrz-zone {
            background: rgba(0, 0, 0, 0.35);
            padding: 8px;
            border-radius: 6px;
            font-size: 9.5px;
            letter-spacing: 1px;
            color: #bae6fd;
            line-height: 1.4;
            margin-bottom: 12px;
            overflow: hidden;
        }
        .pass-barcode-zone { text-align: center; }
        .barcode-graphic {
            height: 22px;
            background: repeating-linear-gradient(90deg, #ffffff, #ffffff 1px, transparent 1px, transparent 3px, #ffffff 3px, #ffffff 6px, transparent 6px, transparent 7px);
            opacity: 0.85;
            margin-bottom: 4px;
        }
        .barcode-legend { font-size: 8px; color: #7dd3fc; letter-spacing: 1.5px; }

        /* Modernized Demographics Stylesheet Enhancement */
        .ifs-demographics-container { display: flex; flex-direction: column; gap: 16px; }
        .demo-group { background: var(--ifs-slate-50); border: 1px solid var(--ifs-slate-200); border-radius: 10px; padding: 14px 16px; }
        .demo-group-title { font-size: 10px; font-weight: 800; text-transform: uppercase; color: var(--ifs-slate-400); letter-spacing: 0.06em; display: block; margin-bottom: 10px; border-bottom: 1px dashed var(--ifs-slate-200); padding-bottom: 6px; }
        .demo-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px 16px; }
        .col-span-full { grid-column: span 2; }
        @media (max-width: 600px) { .demo-grid-2 { grid-template-columns: 1fr; } .col-span-full { grid-column: span 1; } }
        
        .demo-cell { display: flex; flex-direction: column; gap: 2px; }
        .demo-label { font-size: 10.5px; font-weight: 700; color: var(--ifs-slate-500); display: inline-flex; align-items: center; gap: 4px; text-transform: uppercase; }
        .demo-label .dashicons { font-size: 13px; width: 13px; height: 13px; color: var(--ifs-slate-400); }
        .demo-val { font-size: 13px; font-weight: 700; color: var(--ifs-slate-900); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .demo-val a { color: var(--ifs-primary); text-decoration: none; }
        .demo-val a:hover { text-decoration: underline; }
        
        .demo-pill { background: #ffffff; border: 1px solid var(--ifs-slate-200); padding: 2px 8px; border-radius: 5px; font-size: 11.5px; font-weight: 600; color: var(--ifs-slate-700); display: inline-block; }
        .demo-pill.blood-pill { background: #fff1f2; color: #be123c; border-color: #fecdd3; }
        .demo-pill.pill-warning { background: #fef3c7; color: #b45309; border-color: #fde68a; }
        
        .demo-address-box { display: flex; align-items: flex-start; gap: 10px; font-size: 12.5px; color: var(--ifs-slate-700); line-height: 1.4; background: #ffffff; border: 1px solid var(--ifs-slate-200); border-radius: 6px; padding: 10px 12px; }
        .demo-address-box .address-icon { font-size: 16px; width: 16px; height: 16px; color: var(--ifs-primary); flex-shrink: 0; margin-top: 2px; }

        .ifs-passport-matrix-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; }
        .matrix-card { background: var(--ifs-slate-50); border: 1px solid var(--ifs-slate-200); border-radius: 8px; padding: 12px 14px; }
        .matrix-lbl { display: block; font-size: 10.5px; font-weight: 700; text-transform: uppercase; color: var(--ifs-slate-400); margin-bottom: 4px; letter-spacing: 0.03em; }
        .matrix-val { font-size: 13.5px; font-weight: 800; color: var(--ifs-slate-900); }

        .ifs-vault-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
        .ifs-vault-card {
            background: var(--ifs-slate-50);
            border: 1px solid var(--ifs-slate-200);
            border-radius: 8px;
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .vault-thumb {
            width: 42px;
            height: 42px;
            border-radius: 6px;
            background: #ffffff;
            border: 1px solid var(--ifs-slate-200);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }
        .vault-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .vault-thumb .dashicons { font-size: 20px; width: 20px; height: 20px; }
        .vault-pdf-icon { display: flex; flex-direction: column; align-items: center; color: #dc2626; font-weight: 800; }
        .vault-pdf-icon .dashicons { font-size: 20px; width: 20px; height: 20px; }
        .vault-pdf-icon small { font-size: 8px; line-height: 1; margin-top: 1px; }
        .vault-details { flex: 1; min-width: 0; }
        .vault-title { font-size: 12px; font-weight: 700; color: var(--ifs-slate-900); display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .vault-status { font-size: 10.5px; font-weight: 600; }
        .vault-status.status-ok { color: var(--ifs-emerald); }
        .vault-status.status-empty { color: var(--ifs-slate-400); }
        .ifs-link-upload { font-size: 11.5px; font-weight: 600; color: var(--ifs-primary); text-decoration: none; }
        .ifs-link-upload:hover { text-decoration: underline; }

        .ifs-sub-section { margin-bottom: 24px; }
        .ifs-sub-header { display: flex; align-items: center; gap: 6px; margin-bottom: 10px; }
        .ifs-sub-header h4 { margin: 0; font-size: 12.5px; font-weight: 700; color: var(--ifs-slate-700); text-transform: uppercase; letter-spacing: 0.03em; }
        .ifs-sub-header .dashicons { font-size: 15px; width: 15px; height: 15px; color: var(--ifs-primary); }
        .ifs-table-container { overflow-x: auto; border: 1px solid var(--ifs-slate-200); border-radius: 8px; }
        .ifs-table { width: 100%; border-collapse: collapse; font-size: 12.5px; text-align: left; }
        .ifs-table th { background: var(--ifs-slate-50); padding: 8px 12px; font-weight: 700; color: var(--ifs-slate-600); font-size: 11px; text-transform: uppercase; border-bottom: 1px solid var(--ifs-slate-200); }
        .ifs-table td { padding: 10px 12px; border-bottom: 1px solid var(--ifs-slate-100); color: var(--ifs-slate-700); }
        .ifs-table tr:last-child td { border-bottom: none; }
        .ifs-table tr:hover td { background: var(--ifs-slate-50); }
        .ifs-status-pill { font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 4px; text-transform: uppercase; }
        .status-issued, .status-confirmed, .status-approved { background: #dcfce7; color: #166534; }
        .status-pending, .status-submitted { background: #fef3c7; color: #92400e; }
        .status-cancelled, .status-rejected { background: #fee2e2; color: #991b1b; }
        .ifs-badge-indicator { font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 4px; text-transform: uppercase; }
        .ifs-badge-indicator.tour { background: #f0fdf4; color: #166534; }
        .ifs-badge-indicator.hotel { background: #e0e7ff; color: #3730a3; }
        .ifs-empty-record { background: var(--ifs-slate-50); border: 1px dashed var(--ifs-slate-300); border-radius: 6px; padding: 12px; font-size: 12px; color: var(--ifs-slate-400); text-align: center; }

        .ifs-modal-root { position: fixed; inset: 0; z-index: 99999; display: flex; align-items: center; justify-content: center; }
        .ifs-modal-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(2px); }
        .ifs-modal-container { position: relative; z-index: 100000; width: 680px; max-width: 90vw; }
        .ifs-modal-content { background: #ffffff; border-radius: var(--ifs-radius); overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); }
        .ifs-modal-header { padding: 14px 18px; border-bottom: 1px solid var(--ifs-slate-200); display: flex; justify-content: space-between; align-items: center; background: var(--ifs-slate-50); }
        .ifs-modal-header h3 { margin: 0; font-size: 14px; font-weight: 700; color: var(--ifs-slate-900); display: flex; align-items: center; gap: 6px; }
        .ifs-modal-header .dashicons { font-size: 16px; width: 16px; height: 16px; color: var(--ifs-primary); }
        .ifs-modal-close { background: transparent; border: none; font-size: 20px; line-height: 1; cursor: pointer; color: var(--ifs-slate-400); }
        .ifs-modal-close:hover { color: var(--ifs-slate-900); }
        .ifs-modal-body { padding: 18px; text-align: center; background: var(--ifs-slate-950, #090d16); max-height: 70vh; overflow-y: auto; }
        .ifs-modal-body img { max-width: 100%; max-height: 65vh; border-radius: 4px; }
        .ifs-modal-footer { padding: 12px 18px; border-top: 1px solid var(--ifs-slate-200); display: flex; justify-content: flex-end; gap: 8px; background: var(--ifs-slate-50); }
    </style>

    <script>
    jQuery(document).ready(function($) {
        $(document).on('click', 'button.open-image-modal', function() {
            var imgSrc   = $(this).data('img');
            var imgTitle = $(this).data('title');
            if (imgSrc) {
                $('#modalImagePreviewSrc').attr('src', imgSrc);
                $('#modalImageDownloadBtn').attr('href', imgSrc);
                if (imgTitle) {
                    $('#modalImageTitle').html('<span class="dashicons dashicons-format-image"></span> ' + imgTitle);
                }
                $('#ifsImageModal').fadeIn(150);
            }
        });

        $('#btnCloseImageModal, #btnFooterCloseImageModal, .ifs-modal-backdrop').on('click', function() {
            $('#ifsImageModal').fadeOut(150);
        });

        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('#ifsImageModal').fadeOut(150);
            }
        });
    });
    </script>
    <?php
}