<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enterprise Next-Gen Air Ticket Issuance Console
 * Clean UI, Balanced Form Controls, Select2 Search, Dual PNR, Full Round-Trip, Settlement Auditing & Live Image/PDF Preview
 */
function ifs_terp_ticket_add_edit_page() {
    global $wpdb;
    $table_tickets   = $wpdb->prefix . 'iterp_tickets';
    $table_customers = $wpdb->prefix . 'iterp_customers';
    $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
    $table_agents    = $wpdb->prefix . 'iterp_agents';
    
    $id       = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
    $is_edit  = ( $id > 0 );
    $message  = '';
    $errors   = array();
    $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=ticketing' );

    // Auto-migration check for newly added columns
    $existing_cols = $wpdb->get_col( "DESC {$table_tickets}", 0 );
    if ( ! empty( $existing_cols ) ) {
        if ( ! in_array( 'yq_tax', $existing_cols, true ) ) {
            $wpdb->query( "ALTER TABLE {$table_tickets} ADD yq_tax decimal(12,2) DEFAULT '0.00' NOT NULL AFTER tax_amount" );
        }
        if ( ! in_array( 'booking_class', $existing_cols, true ) ) {
            $wpdb->query( "ALTER TABLE {$table_tickets} ADD booking_class varchar(10) DEFAULT 'Y' NOT NULL AFTER cabin_class" );
        }
    }

    // Enqueue Select2 Assets
    if ( ! wp_script_is( 'select2', 'registered' ) ) {
        wp_register_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), '4.1.0-rc.0', true );
        wp_register_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0-rc.0' );
    }
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'select2' );
    wp_enqueue_style( 'select2' );

    if ( function_exists( 'wp_enqueue_media' ) ) {
        wp_enqueue_media();
    }

    // Process Ticket Submission
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_ticket_submit'] ) ) {
        check_admin_referer( 'ifs_ticket_save_action', 'ifs_ticket_nonce' );

        $customer_id         = isset( $_POST['customer_id'] ) ? absint( wp_unslash( $_POST['customer_id'] ) ) : 0;
        $passenger_name      = isset( $_POST['passenger_name'] ) ? sanitize_text_field( wp_unslash( $_POST['passenger_name'] ) ) : '';
        $passenger_phone     = isset( $_POST['passenger_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['passenger_phone'] ) ) : '';
        $passenger_email     = isset( $_POST['passenger_email'] ) ? sanitize_email( wp_unslash( $_POST['passenger_email'] ) ) : '';
        $passport_no         = isset( $_POST['passport_no'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['passport_no'] ) ) ) : '';
        $agent_id            = isset( $_POST['agent_id'] ) ? absint( wp_unslash( $_POST['agent_id'] ) ) : 0;
        $supplier_id         = isset( $_POST['supplier_id'] ) ? absint( wp_unslash( $_POST['supplier_id'] ) ) : 0;
        $supplier_ref        = isset( $_POST['supplier_ref'] ) ? sanitize_text_field( wp_unslash( $_POST['supplier_ref'] ) ) : '';
        
        $pnr                 = isset( $_POST['pnr'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['pnr'] ) ) ) : '';
        $airline_pnr         = isset( $_POST['airline_pnr'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['airline_pnr'] ) ) ) : '';
        $ticket_no           = isset( $_POST['ticket_no'] ) ? sanitize_text_field( wp_unslash( $_POST['ticket_no'] ) ) : '';
        $airline             = isset( $_POST['airline'] ) ? sanitize_text_field( wp_unslash( $_POST['airline'] ) ) : '';
        $flight_no           = isset( $_POST['flight_no'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['flight_no'] ) ) ) : '';
        $return_flight_no    = isset( $_POST['return_flight_no'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['return_flight_no'] ) ) ) : '';
        
        $origin_airport      = isset( $_POST['origin_airport'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['origin_airport'] ) ) ) : 'DAC';
        $destination_airport = isset( $_POST['destination_airport'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['destination_airport'] ) ) ) : 'DXB';
        $sector              = $origin_airport . '-' . $destination_airport;

        $via_transit         = isset( $_POST['via_transit'] ) ? sanitize_text_field( wp_unslash( $_POST['via_transit'] ) ) : 'Direct';
        $cabin_class         = isset( $_POST['cabin_class'] ) ? sanitize_text_field( wp_unslash( $_POST['cabin_class'] ) ) : 'Economy';
        $booking_class       = isset( $_POST['booking_class'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['booking_class'] ) ) ) : 'Y';
        $fare_basis          = isset( $_POST['fare_basis'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['fare_basis'] ) ) ) : '';
        $flight_type         = isset( $_POST['flight_type'] ) ? sanitize_text_field( wp_unslash( $_POST['flight_type'] ) ) : 'One Way';
        
        $issue_date          = isset( $_POST['issue_date'] ) ? sanitize_text_field( wp_unslash( $_POST['issue_date'] ) ) : current_time( 'Y-m-d' );
        $travel_date         = isset( $_POST['travel_date'] ) ? sanitize_text_field( wp_unslash( $_POST['travel_date'] ) ) : '';
        $flight_time         = isset( $_POST['flight_time'] ) ? sanitize_text_field( wp_unslash( $_POST['flight_time'] ) ) : '';
        $arrival_date        = isset( $_POST['arrival_date'] ) ? sanitize_text_field( wp_unslash( $_POST['arrival_date'] ) ) : '';
        $arrival_time        = isset( $_POST['arrival_time'] ) ? sanitize_text_field( wp_unslash( $_POST['arrival_time'] ) ) : '';

        $return_date         = isset( $_POST['return_date'] ) ? sanitize_text_field( wp_unslash( $_POST['return_date'] ) ) : '';
        $return_flight_time  = isset( $_POST['return_flight_time'] ) ? sanitize_text_field( wp_unslash( $_POST['return_flight_time'] ) ) : '';
        $return_arrival_time = isset( $_POST['return_arrival_time'] ) ? sanitize_text_field( wp_unslash( $_POST['return_arrival_time'] ) ) : '';

        $baggage             = isset( $_POST['baggage'] ) ? sanitize_text_field( wp_unslash( $_POST['baggage'] ) ) : '20 KG';
        $gds_pcc             = isset( $_POST['gds_pcc'] ) ? sanitize_text_field( wp_unslash( $_POST['gds_pcc'] ) ) : 'Sabre';
        
        // Financials
        $base_fare           = isset( $_POST['base_fare'] ) ? (float) wp_unslash( $_POST['base_fare'] ) : 0;
        $tax_amount          = isset( $_POST['tax_amount'] ) ? (float) wp_unslash( $_POST['tax_amount'] ) : 0;
        $yq_tax              = isset( $_POST['yq_tax'] ) ? (float) wp_unslash( $_POST['yq_tax'] ) : 0;
        $commission_amount   = isset( $_POST['commission_amount'] ) ? (float) wp_unslash( $_POST['commission_amount'] ) : 0;
        $ait_amount          = isset( $_POST['ait_amount'] ) ? (float) wp_unslash( $_POST['ait_amount'] ) : 0;
        $discount_amount     = isset( $_POST['discount_amount'] ) ? (float) wp_unslash( $_POST['discount_amount'] ) : 0;
        $buy_price           = isset( $_POST['buy_price'] ) ? (float) wp_unslash( $_POST['buy_price'] ) : 0;
        $sell_price          = isset( $_POST['sell_price'] ) ? (float) wp_unslash( $_POST['sell_price'] ) : 0;
        $paid_amount         = isset( $_POST['paid_amount'] ) ? (float) wp_unslash( $_POST['paid_amount'] ) : 0;
        $due_amount          = max( 0, $sell_price - $paid_amount );
        
        $profit              = ( $sell_price - $buy_price ) + $commission_amount - $discount_amount - $ait_amount;
        
        $status              = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'Issued';
        $payment_status      = isset( $_POST['payment_status'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_status'] ) ) : 'Paid';
        $payment_method      = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : 'Bank Transfer';
        $transaction_id      = isset( $_POST['transaction_id'] ) ? sanitize_text_field( wp_unslash( $_POST['transaction_id'] ) ) : '';
        $remarks             = isset( $_POST['remarks'] ) ? sanitize_textarea_field( wp_unslash( $_POST['remarks'] ) ) : '';
        $ticket_copy_url     = isset( $_POST['ticket_copy_url'] ) ? esc_url_raw( wp_unslash( $_POST['ticket_copy_url'] ) ) : '';

        if ( empty( $customer_id ) ) {
            $errors[] = esc_html__( 'Please select a passenger for this ticket.', 'ifs-travel-erp' );
        }
        if ( empty( $pnr ) ) {
            $errors[] = esc_html__( 'GDS PNR / Booking Reference is mandatory.', 'ifs-travel-erp' );
        }
        if ( empty( $ticket_no ) ) {
            $errors[] = esc_html__( 'E-Ticket Number is required.', 'ifs-travel-erp' );
        }
        if ( empty( $travel_date ) ) {
            $errors[] = esc_html__( 'Flight departure date is required.', 'ifs-travel-erp' );
        }

        if ( empty( $errors ) ) {
            $data = array(
                'customer_id'         => $customer_id,
                'passenger_name'      => $passenger_name,
                'passenger_phone'     => $passenger_phone,
                'passenger_email'     => $passenger_email,
                'passport_no'         => $passport_no,
                'agent_id'            => $agent_id,
                'supplier_id'         => $supplier_id,
                'supplier_ref'        => $supplier_ref,
                'pnr'                 => $pnr,
                'airline_pnr'         => $airline_pnr,
                'ticket_no'           => $ticket_no,
                'airline'             => $airline,
                'flight_no'           => $flight_no,
                'return_flight_no'    => ( $flight_type === 'Round Trip' ) ? $return_flight_no : '',
                'sector'              => $sector,
                'via_transit'         => $via_transit,
                'cabin_class'         => $cabin_class,
                'booking_class'       => $booking_class,
                'fare_basis'          => $fare_basis,
                'flight_type'         => $flight_type,
                'issue_date'          => ! empty( $issue_date ) ? $issue_date : current_time( 'Y-m-d' ),
                'travel_date'         => ! empty( $travel_date ) ? $travel_date : '1970-01-01',
                'flight_time'         => $flight_time,
                'arrival_date'        => ! empty( $arrival_date ) ? $arrival_date : $travel_date,
                'arrival_time'        => $arrival_time,
                'return_date'         => ( $flight_type === 'Round Trip' && ! empty( $return_date ) ) ? $return_date : '1970-01-01',
                'return_flight_time'  => ( $flight_type === 'Round Trip' ) ? $return_flight_time : '',
                'return_arrival_time' => ( $flight_type === 'Round Trip' ) ? $return_arrival_time : '',
                'baggage'             => $baggage,
                'gds_pcc'             => $gds_pcc,
                'base_fare'           => $base_fare,
                'tax_amount'          => $tax_amount,
                'yq_tax'              => $yq_tax,
                'commission_amount'   => $commission_amount,
                'ait_amount'          => $ait_amount,
                'discount_amount'     => $discount_amount,
                'buy_price'           => $buy_price,
                'sell_price'          => $sell_price,
                'paid_amount'         => $paid_amount,
                'due_amount'          => $due_amount,
                'profit'              => $profit,
                'status'              => $status,
                'payment_status'      => $payment_status,
                'payment_method'      => $payment_method,
                'transaction_id'      => $transaction_id,
                'remarks'             => $remarks,
                'ticket_copy_url'     => $ticket_copy_url,
                'issued_by'           => get_current_user_id(),
            );

            if ( $is_edit ) {
                $wpdb->update( $table_tickets, $data, array( 'id' => $id ) );
                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Air Ticket record updated successfully.', 'ifs-travel-erp' ) . '</div>';
            } else {
                $data['created_at'] = current_time( 'mysql' );
                $wpdb->insert( $table_tickets, $data );
                $id      = $wpdb->insert_id;
                $is_edit = true;
                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . sprintf( esc_html__( 'New Air Ticket registered (#TKT-%s).', 'ifs-travel-erp' ), str_pad( (string) $id, 5, '0', STR_PAD_LEFT ) ) . '</div>';
            }

            if ( function_exists( 'ifs_terp_log_activity' ) ) {
                ifs_terp_log_activity( "Processed E-Ticket #TKT-{$id} | PNR: {$pnr} | Fare: ৳{$sell_price}" );
            }
        } else {
            $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> ' . implode( '<br>', $errors ) . '</div>';
        }
    }

    $row = false;
    if ( $is_edit ) {
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_tickets} WHERE id = %d", $id ) );
    }

    $customers = $wpdb->get_results( "SELECT id, title, full_name, mobile, email, passport_no FROM {$table_customers} ORDER BY full_name ASC" );
    $suppliers = $wpdb->get_results( "SELECT id, supplier_name, current_balance FROM {$table_suppliers} WHERE status = 'Active' ORDER BY supplier_name ASC" );
    $agents    = $wpdb->get_results( "SELECT id, agency_name, current_balance FROM {$table_agents} WHERE status = 'Active' ORDER BY agency_name ASC" );

    // Field Defaults
    $val_customer        = $is_edit ? absint( $row->customer_id ?? 0 ) : 0;
    $val_pax_name        = $is_edit ? esc_attr( $row->passenger_name ?? '' ) : '';
    $val_pax_phone       = $is_edit ? esc_attr( $row->passenger_phone ?? '' ) : '';
    $val_pax_email       = $is_edit ? esc_attr( $row->passenger_email ?? '' ) : '';
    $val_passport        = $is_edit ? esc_attr( $row->passport_no ?? '' ) : '';
    $val_agent           = $is_edit ? absint( $row->agent_id ?? 0 ) : 0;
    $val_supplier        = $is_edit ? absint( $row->supplier_id ?? 0 ) : 0;
    $val_supplier_ref    = $is_edit ? esc_attr( $row->supplier_ref ?? '' ) : '';
    $val_pnr             = $is_edit ? esc_attr( $row->pnr ?? '' ) : '';
    $val_airline_pnr     = $is_edit ? esc_attr( $row->airline_pnr ?? '' ) : '';
    $val_ticket_no       = $is_edit ? esc_attr( $row->ticket_no ?? '' ) : '';
    $val_airline         = $is_edit ? esc_attr( $row->airline ?? '' ) : '';
    $val_flight_no       = $is_edit ? esc_attr( $row->flight_no ?? '' ) : '';
    $val_ret_flight_no   = $is_edit ? esc_attr( $row->return_flight_no ?? '' ) : '';
    
    $saved_sector        = $is_edit ? esc_attr( $row->sector ?? 'DAC-DXB' ) : 'DAC-DXB';
    $sector_parts        = explode( '-', $saved_sector );
    $val_origin          = $sector_parts[0] ?? 'DAC';
    $val_dest            = $sector_parts[1] ?? 'DXB';

    $val_transit         = $is_edit ? esc_attr( $row->via_transit ?? 'Direct' ) : 'Direct';
    $val_cabin           = $is_edit ? esc_attr( $row->cabin_class ?? 'Economy' ) : 'Economy';
    $val_booking_class   = $is_edit ? esc_attr( $row->booking_class ?? 'Y' ) : 'Y';
    $val_fare_basis      = $is_edit ? esc_attr( $row->fare_basis ?? '' ) : '';
    $val_flight_type     = $is_edit ? esc_attr( $row->flight_type ?? 'One Way' ) : 'One Way';
    
    $val_issue_date      = ( $is_edit && ! empty( $row->issue_date ) && $row->issue_date !== '1970-01-01' ) ? esc_attr( $row->issue_date ) : current_time( 'Y-m-d' );
    $val_travel_date     = $is_edit ? esc_attr( $row->travel_date ?? '' ) : date( 'Y-m-d', strtotime( '+3 days' ) );
    $val_flight_time     = $is_edit ? esc_attr( $row->flight_time ?? '' ) : '21:30';
    $val_arr_date        = $is_edit ? esc_attr( $row->arrival_date ?? '' ) : '';
    $val_arr_time        = $is_edit ? esc_attr( $row->arrival_time ?? '' ) : '';
    
    $val_return_date     = ( $is_edit && ! empty( $row->return_date ) && $row->return_date !== '1970-01-01' ) ? esc_attr( $row->return_date ) : '';
    $val_ret_time        = $is_edit ? esc_attr( $row->return_flight_time ?? '' ) : '';
    $val_ret_arr_time    = $is_edit ? esc_attr( $row->return_arrival_time ?? '' ) : '';
    
    $val_baggage         = $is_edit ? esc_attr( $row->baggage ?? '20 KG' ) : '20 KG';
    $val_gds             = $is_edit ? esc_attr( $row->gds_pcc ?? 'Sabre' ) : 'Sabre';
    
    $val_base_fare       = $is_edit ? (float) ( $row->base_fare ?? 0 ) : '';
    $val_tax_amount      = $is_edit ? (float) ( $row->tax_amount ?? 0 ) : '';
    $val_yq_tax          = $is_edit ? (float) ( $row->yq_tax ?? 0 ) : '';
    $val_comm            = $is_edit ? (float) ( $row->commission_amount ?? 0 ) : '';
    $val_ait             = $is_edit ? (float) ( $row->ait_amount ?? 0 ) : '';
    $val_discount        = $is_edit ? (float) ( $row->discount_amount ?? 0 ) : '';
    $val_buy             = $is_edit ? (float) ( $row->buy_price ?? 0 ) : '';
    $val_sell            = $is_edit ? (float) ( $row->sell_price ?? 0 ) : '';
    $val_paid            = $is_edit ? (float) ( $row->paid_amount ?? 0 ) : '';
    $val_due             = $is_edit ? (float) ( $row->due_amount ?? 0 ) : 0;
    $val_profit          = $is_edit ? (float) ( $row->profit ?? 0 ) : 0;
    
    $val_status          = $is_edit ? esc_attr( $row->status ?? 'Issued' ) : 'Issued';
    $val_pay_status      = $is_edit ? esc_attr( $row->payment_status ?? 'Paid' ) : 'Paid';
    $val_pay_method      = $is_edit ? esc_attr( $row->payment_method ?? 'Bank Transfer' ) : 'Bank Transfer';
    $val_txn_id          = $is_edit ? esc_attr( $row->transaction_id ?? '' ) : '';
    $val_remarks         = $is_edit ? esc_attr( $row->remarks ?? '' ) : '';
    $val_tkt_copy        = $is_edit ? esc_url( $row->ticket_copy_url ?? '' ) : '';
    ?>

    <div class="wrap ifs-ticket-workspace">
        <?php echo wp_kses_post( $message ); ?>

        <form method="post" action="" id="ifsTicketForm" class="ifs-single-column-editor">
            <?php wp_nonce_field( 'ifs_ticket_save_action', 'ifs_ticket_nonce' ); ?>

            <div class="ifs-ticket-form-body">
                
                <div class="ifs-panel-card ifs-preview-card-wrap">
                    <div class="ifs-card-preview-header">
                        <div class="preview-header-left">
                            <span class="pulse-beacon"></span>
                            <span><?php esc_html_e( 'Live Boarding Pass', 'ifs-travel-erp' ); ?></span>
                        </div>
                        <span class="preview-secure-tag"><span class="dashicons dashicons-shield"></span> <?php esc_html_e( 'IATA ETKT', 'ifs-travel-erp' ); ?></span>
                    </div>

                    <div class="ifs-lux-boarding-pass">
                        <div class="pass-top-band">
                            <div class="pass-carrier-wrap">
                                <div class="carrier-tail-icon"><span class="dashicons dashicons-airplane"></span></div>
                                <div>
                                    <span class="carrier-name" id="prev_airline"><?php echo esc_html( ! empty( $val_airline ) ? strtoupper( $val_airline ) : 'AIRLINE CARRIER' ); ?></span>
                                    <span class="flight-no-badge font-mono" id="prev_flight_no"><?php echo esc_html( ! empty( $val_flight_no ) ? strtoupper( $val_flight_no ) : 'FLIGHT' ); ?></span>
                                </div>
                            </div>
                            <div class="cabin-class-pill" id="prev_cabin"><?php echo esc_html( strtoupper( $val_cabin ) ); ?></div>
                        </div>

                        <div class="pass-route-arc-box">
                            <div class="route-station origin">
                                <span class="station-code font-mono" id="prev_origin"><?php echo esc_html( strtoupper( $val_origin ) ); ?></span>
                                <span class="station-city"><?php esc_html_e( 'Origin', 'ifs-travel-erp' ); ?></span>
                            </div>
                            <div class="route-arc-visual">
                                <div class="arc-line-dotted"></div>
                                <div class="plane-flight-symbol"><span class="dashicons dashicons-airplane"></span></div>
                                <span class="trip-tag-pill" id="prev_type"><?php echo esc_html( strtoupper( $val_flight_type ) ); ?></span>
                            </div>
                            <div class="route-station dest">
                                <span class="station-code font-mono" id="prev_dest"><?php echo esc_html( strtoupper( $val_dest ) ); ?></span>
                                <span class="station-city"><?php esc_html_e( 'Destination', 'ifs-travel-erp' ); ?></span>
                            </div>
                        </div>

                        <div class="pass-perforation-divider">
                            <div class="perf-hole left"></div>
                            <div class="perf-line"></div>
                            <div class="perf-hole right"></div>
                        </div>

                        <div class="pass-pax-hero">
                            <div class="pax-meta-cell">
                                <span class="pax-label"><?php esc_html_e( 'Passenger Name', 'ifs-travel-erp' ); ?></span>
                                <strong class="pax-name-val uppercase" id="prev_pax_name"><?php echo esc_html( ! empty( $val_pax_name ) ? $val_pax_name : 'SELECT TRAVELER' ); ?></strong>
                            </div>
                            <div class="pax-meta-cell text-right">
                                <span class="pax-label"><?php esc_html_e( 'Departure Date & Time', 'ifs-travel-erp' ); ?></span>
                                <strong class="pax-date-val font-mono" id="prev_date"><?php echo esc_html( date_i18n( 'd M Y', strtotime( $val_travel_date ) ) . ( ! empty( $val_flight_time ) ? ' (' . $val_flight_time . ')' : '' ) ); ?></strong>
                            </div>
                        </div>

                        <div class="pass-specs-grid font-mono">
                            <div class="spec-cell">
                                <span class="spec-label"><?php esc_html_e( 'GDS / Airline PNR', 'ifs-travel-erp' ); ?></span>
                                <strong class="spec-value color-cyan" id="prev_pnr"><?php echo esc_html( ( $val_pnr ?: '------' ) . ( ! empty( $val_airline_pnr ) ? ' / ' . $val_airline_pnr : '' ) ); ?></strong>
                            </div>
                            <div class="spec-cell">
                                <span class="spec-label"><?php esc_html_e( 'Baggage Allowance', 'ifs-travel-erp' ); ?></span>
                                <strong class="spec-value" id="prev_baggage"><?php echo esc_html( $val_baggage ); ?></strong>
                            </div>
                            <div class="spec-cell">
                                <span class="spec-label"><?php esc_html_e( 'E-Ticket Number', 'ifs-travel-erp' ); ?></span>
                                <strong class="spec-value" id="prev_tkt"><?php echo esc_html( $val_ticket_no ?: '077-XXXXXXXXXX' ); ?></strong>
                            </div>
                            <div class="spec-cell">
                                <span class="spec-label"><?php esc_html_e( 'Total Fare', 'ifs-travel-erp' ); ?></span>
                                <strong class="spec-value color-green" id="prev_sell">৳<?php echo esc_html( number_format( (float) ( $val_sell ?: 0 ), 2 ) ); ?></strong>
                            </div>
                        </div>

                        <div class="pass-barcode-area">
                            <div class="barcode-matrix-lines"></div>
                            <span class="barcode-code-text font-mono" id="prev_barcode">M1RAHIM/MD  E7X9K2L DACDXBEK 0585 233Y</span>
                        </div>
                    </div>
                </div>

                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">01</div>
                        <div>
                            <h3 class="ifs-card-title"><?php esc_html_e( 'Passenger & PNR Info', 'ifs-travel-erp' ); ?></h3>
                            <p class="ifs-card-desc"><?php esc_html_e( 'Assign customer profile, supplier channel, and booking references', 'ifs-travel-erp' ); ?></p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <div class="ifs-field-block col-span-2">
                            <label class="ifs-field-label" for="inp_customer"><?php esc_html_e( 'Passenger', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <select name="customer_id" id="inp_customer" required class="ifs-input-field ifs-select2">
                                    <option value=""><?php esc_html_e( '-- Search & Choose Registered Traveler --', 'ifs-travel-erp' ); ?></option>
                                    <?php foreach ( $customers as $cus ) : 
                                        $t_prefix = ! empty( $cus->title ) ? $cus->title . '. ' : '';
                                        $p_meta   = ! empty( $cus->passport_no ) ? ' [PPT: ' . $cus->passport_no . ']' : '';
                                    ?>
                                        <option value="<?php echo esc_attr( $cus->id ); ?>" 
                                                data-name="<?php echo esc_attr( $t_prefix . $cus->full_name ); ?>"
                                                data-phone="<?php echo esc_attr( $cus->mobile ); ?>"
                                                data-email="<?php echo esc_attr( $cus->email ); ?>"
                                                data-passport="<?php echo esc_attr( $cus->passport_no ?: 'NOT SET' ); ?>"
                                                <?php selected( $val_customer, $cus->id ); ?>>
                                            <?php echo esc_html( $t_prefix . $cus->full_name . ' (' . $cus->mobile . ')' . $p_meta ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <input type="hidden" name="passenger_name" id="inp_passenger_name_hidden" value="<?php echo esc_attr( $val_pax_name ); ?>">
                            <input type="hidden" name="passenger_phone" id="inp_passenger_phone_hidden" value="<?php echo esc_attr( $val_pax_phone ); ?>">
                            <input type="hidden" name="passenger_email" id="inp_passenger_email_hidden" value="<?php echo esc_attr( $val_pax_email ); ?>">
                            <input type="hidden" name="passport_no" id="inp_passport_no_hidden" value="<?php echo esc_attr( $val_passport ); ?>">
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_agent"><?php esc_html_e( 'Sub-Agent', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <select name="agent_id" id="inp_agent" class="ifs-input-field ifs-select2">
                                    <option value="0"><?php esc_html_e( 'Direct Retail Passenger', 'ifs-travel-erp' ); ?></option>
                                    <?php foreach ( $agents as $ag ) : ?>
                                        <option value="<?php echo esc_attr( $ag->id ); ?>" <?php selected( $val_agent, $ag->id ); ?>>
                                            <?php echo esc_html( $ag->agency_name . ' (Bal: ৳' . number_format( (float) $ag->current_balance, 0 ) . ')' ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_supplier"><?php esc_html_e( 'Supplier', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <select name="supplier_id" id="inp_supplier" class="ifs-input-field ifs-select2">
                                    <option value="0"><?php esc_html_e( 'Direct IATA / BSP', 'ifs-travel-erp' ); ?></option>
                                    <?php foreach ( $suppliers as $sup ) : ?>
                                        <option value="<?php echo esc_attr( $sup->id ); ?>" <?php selected( $val_supplier, $sup->id ); ?>>
                                            <?php echo esc_html( $sup->supplier_name . ' (Bal: ৳' . number_format( (float) $sup->current_balance, 0 ) . ')' ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_supplier_ref"><?php esc_html_e( 'Supplier Ref', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-admin-links field-icon"></span>
                                <input type="text" name="supplier_ref" id="inp_supplier_ref" 
                                       value="<?php echo esc_attr( $val_supplier_ref ); ?>" 
                                       placeholder="e.g. FL-987421" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_gds"><?php esc_html_e( 'GDS System', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-admin-site-alt3 field-icon"></span>
                                <select name="gds_pcc" id="inp_gds" class="ifs-input-field">
                                    <option value="Sabre" <?php selected( $val_gds, 'Sabre' ); ?>><?php esc_html_e( 'Sabre GDS', 'ifs-travel-erp' ); ?></option>
                                    <option value="Amadeus" <?php selected( $val_gds, 'Amadeus' ); ?>><?php esc_html_e( 'Amadeus GDS', 'ifs-travel-erp' ); ?></option>
                                    <option value="Galileo" <?php selected( $val_gds, 'Galileo' ); ?>><?php esc_html_e( 'Travelport / Galileo', 'ifs-travel-erp' ); ?></option>
                                    <option value="FlyHub" <?php selected( $val_gds, 'FlyHub' ); ?>><?php esc_html_e( 'FlyHub B2B', 'ifs-travel-erp' ); ?></option>
                                    <option value="ShareTrip" <?php selected( $val_gds, 'ShareTrip' ); ?>><?php esc_html_e( 'ShareTrip B2B', 'ifs-travel-erp' ); ?></option>
                                    <option value="Airline Portal" <?php selected( $val_gds, 'Airline Portal' ); ?>><?php esc_html_e( 'Direct Airline Portal', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_pnr"><?php esc_html_e( 'GDS PNR', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-randomize field-icon"></span>
                                <input type="text" name="pnr" id="inp_pnr" required 
                                       value="<?php echo esc_attr( $val_pnr ); ?>" 
                                       placeholder="e.g. 7X9K2L" class="ifs-input-field uppercase font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_airline_pnr"><?php esc_html_e( 'Airline PNR', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-tag field-icon"></span>
                                <input type="text" name="airline_pnr" id="inp_airline_pnr" 
                                       value="<?php echo esc_attr( $val_airline_pnr ); ?>" 
                                       placeholder="e.g. EK-89QPZ" class="ifs-input-field uppercase font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_ticket"><?php esc_html_e( 'Ticket Number', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-tickets-alt field-icon"></span>
                                <input type="text" name="ticket_no" id="inp_ticket" required 
                                       value="<?php echo esc_attr( $val_ticket_no ); ?>" 
                                       placeholder="077-1234567890" class="ifs-input-field font-mono">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">02</div>
                        <div>
                            <h3 class="ifs-card-title"><?php esc_html_e( 'Flight & Route Details', 'ifs-travel-erp' ); ?></h3>
                            <p class="ifs-card-desc"><?php esc_html_e( 'Operating carriers, routes, stops, departure, and arrival timing', 'ifs-travel-erp' ); ?></p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_issue_date"><?php esc_html_e( 'Issue Date', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-calendar-alt field-icon"></span>
                                <input type="date" name="issue_date" id="inp_issue_date" required 
                                       value="<?php echo esc_attr( $val_issue_date ); ?>" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_airline"><?php esc_html_e( 'Airline', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-airplane field-icon"></span>
                                <input type="text" name="airline" id="inp_airline" required 
                                       value="<?php echo esc_attr( $val_airline ); ?>" 
                                       placeholder="e.g. Emirates" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_flight_no"><?php esc_html_e( 'Flight Number', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-location-alt field-icon"></span>
                                <input type="text" name="flight_no" id="inp_flight_no" 
                                       value="<?php echo esc_attr( $val_flight_no ); ?>" 
                                       placeholder="e.g. EK-585" class="ifs-input-field uppercase font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_flight_type"><?php esc_html_e( 'Trip Type', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-image-rotate field-icon"></span>
                                <select name="flight_type" id="inp_flight_type" class="ifs-input-field">
                                    <option value="One Way" <?php selected( $val_flight_type, 'One Way' ); ?>><?php esc_html_e( 'One Way Flight', 'ifs-travel-erp' ); ?></option>
                                    <option value="Round Trip" <?php selected( $val_flight_type, 'Round Trip' ); ?>><?php esc_html_e( 'Round Trip (Return)', 'ifs-travel-erp' ); ?></option>
                                    <option value="Multi City" <?php selected( $val_flight_type, 'Multi City' ); ?>><?php esc_html_e( 'Multi City Routing', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_origin"><?php esc_html_e( 'Origin', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-arrow-right-alt field-icon"></span>
                                <input type="text" name="origin_airport" id="inp_origin" required maxlength="3"
                                       value="<?php echo esc_attr( $val_origin ); ?>" 
                                       placeholder="DAC" class="ifs-input-field uppercase font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_dest"><?php esc_html_e( 'Destination', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-arrow-left-alt field-icon"></span>
                                <input type="text" name="destination_airport" id="inp_dest" required maxlength="3"
                                       value="<?php echo esc_attr( $val_dest ); ?>" 
                                       placeholder="DXB" class="ifs-input-field uppercase font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_transit"><?php esc_html_e( 'Transit', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-admin-site field-icon"></span>
                                <select name="via_transit" id="inp_transit" class="ifs-input-field">
                                    <option value="Direct" <?php selected( $val_transit, 'Direct' ); ?>><?php esc_html_e( 'Direct Flight', 'ifs-travel-erp' ); ?></option>
                                    <option value="1 Stop" <?php selected( $val_transit, '1 Stop' ); ?>><?php esc_html_e( '1 Stop (Transit)', 'ifs-travel-erp' ); ?></option>
                                    <option value="2+ Stops" <?php selected( $val_transit, '2+ Stops' ); ?>><?php esc_html_e( '2+ Stops / Multi-Stop', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_travel_date"><?php esc_html_e( 'Departure Date', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-calendar-alt field-icon"></span>
                                <input type="date" name="travel_date" id="inp_travel_date" required 
                                       value="<?php echo esc_attr( $val_travel_date ); ?>" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_flight_time"><?php esc_html_e( 'Departure Time', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-clock field-icon"></span>
                                <input type="text" name="flight_time" id="inp_flight_time" 
                                       value="<?php echo esc_attr( $val_flight_time ); ?>" placeholder="e.g. 21:30" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_arrival_date"><?php esc_html_e( 'Arrival Date', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-calendar field-icon"></span>
                                <input type="date" name="arrival_date" id="inp_arrival_date" 
                                       value="<?php echo esc_attr( $val_arr_date ); ?>" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_arrival_time"><?php esc_html_e( 'Arrival Time', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-clock field-icon"></span>
                                <input type="text" name="arrival_time" id="inp_arrival_time" 
                                       value="<?php echo esc_attr( $val_arr_time ); ?>" placeholder="e.g. 02:45" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_cabin"><?php esc_html_e( 'Cabin Class', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-tag field-icon"></span>
                                <select name="cabin_class" id="inp_cabin" class="ifs-input-field">
                                    <option value="Economy" <?php selected( $val_cabin, 'Economy' ); ?>><?php esc_html_e( 'Economy Class', 'ifs-travel-erp' ); ?></option>
                                    <option value="Premium Economy" <?php selected( $val_cabin, 'Premium Economy' ); ?>><?php esc_html_e( 'Premium Economy', 'ifs-travel-erp' ); ?></option>
                                    <option value="Business" <?php selected( $val_cabin, 'Business' ); ?>><?php esc_html_e( 'Business Class', 'ifs-travel-erp' ); ?></option>
                                    <option value="First Class" <?php selected( $val_cabin, 'First Class' ); ?>><?php esc_html_e( 'First Class', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_booking_class"><?php esc_html_e( 'Booking Class', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-editor-textcolor field-icon"></span>
                                <input type="text" name="booking_class" id="inp_booking_class" maxlength="3"
                                       value="<?php echo esc_attr( $val_booking_class ); ?>" placeholder="e.g. Y / K / J" class="ifs-input-field uppercase font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_fare_basis"><?php esc_html_e( 'Fare Basis', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-media-code field-icon"></span>
                                <input type="text" name="fare_basis" id="inp_fare_basis" 
                                       value="<?php echo esc_attr( $val_fare_basis ); ?>" placeholder="e.g. YOWBD / K21RT" class="ifs-input-field uppercase font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_baggage"><?php esc_html_e( 'Baggage', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-portfolio field-icon"></span>
                                <select name="baggage" id="inp_baggage" class="ifs-input-field">
                                    <option value="20 KG" <?php selected( $val_baggage, '20 KG' ); ?>><?php esc_html_e( '20 KG (Standard Economy)', 'ifs-travel-erp' ); ?></option>
                                    <option value="25 KG" <?php selected( $val_baggage, '25 KG' ); ?>><?php esc_html_e( '25 KG', 'ifs-travel-erp' ); ?></option>
                                    <option value="30 KG" <?php selected( $val_baggage, '30 KG' ); ?>><?php esc_html_e( '30 KG (Middle East Standard)', 'ifs-travel-erp' ); ?></option>
                                    <option value="35 KG" <?php selected( $val_baggage, '35 KG' ); ?>><?php esc_html_e( '35 KG', 'ifs-travel-erp' ); ?></option>
                                    <option value="40 KG" <?php selected( $val_baggage, '40 KG' ); ?>><?php esc_html_e( '40 KG (Business / Student)', 'ifs-travel-erp' ); ?></option>
                                    <option value="2 Pieces (PC)" <?php selected( $val_baggage, '2 Pieces (PC)' ); ?>><?php esc_html_e( '2 Pieces (US/Europe Route)', 'ifs-travel-erp' ); ?></option>
                                    <option value="7 KG (Cabin Only)" <?php selected( $val_baggage, '7 KG (Cabin Only)' ); ?>><?php esc_html_e( '7 KG (Cabin Only / LCC)', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div id="wrap_return_segment" style="<?php echo ( $val_flight_type !== 'Round Trip' ) ? 'display:none;' : ''; ?>; margin-top: 18px; padding-top: 18px; border-top: 1px dashed #cbd5e1;">
                        <span class="ifs-field-label" style="display: block; margin-bottom: 12px; color: #0284c7;"><?php esc_html_e( 'Return Flight Details', 'ifs-travel-erp' ); ?></span>
                        <div class="ifs-grid-3">
                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_return_flight_no"><?php esc_html_e( 'Return Flight No', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-location-alt field-icon"></span>
                                    <input type="text" name="return_flight_no" id="inp_return_flight_no" 
                                           value="<?php echo esc_attr( $val_ret_flight_no ); ?>" 
                                           placeholder="e.g. EK-584" class="ifs-input-field uppercase font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_return_date"><?php esc_html_e( 'Return Date', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-calendar field-icon"></span>
                                    <input type="date" name="return_date" id="inp_return_date" 
                                           value="<?php echo esc_attr( $val_return_date ); ?>" class="ifs-input-field">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_return_flight_time"><?php esc_html_e( 'Return Time', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-clock field-icon"></span>
                                    <input type="text" name="return_flight_time" id="inp_return_flight_time" 
                                           value="<?php echo esc_attr( $val_ret_time ); ?>" placeholder="e.g. 14:00" class="ifs-input-field font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_return_arrival_time"><?php esc_html_e( 'Return Arrival Time', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-clock field-icon"></span>
                                    <input type="text" name="return_arrival_time" id="inp_return_arrival_time" 
                                           value="<?php echo esc_attr( $val_ret_arr_time ); ?>" placeholder="e.g. 19:30" class="ifs-input-field font-mono">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">03</div>
                        <div>
                            <h3 class="ifs-card-title"><?php esc_html_e( 'Fare & Pricing', 'ifs-travel-erp' ); ?></h3>
                            <p class="ifs-card-desc"><?php esc_html_e( 'Fare breakdown, commission percentage, AIT withholding tax, client rate, and net yield', 'ifs-travel-erp' ); ?></p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_base_fare"><?php esc_html_e( 'Base Fare (৳)', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-money-alt field-icon"></span>
                                <input type="number" step="0.01" name="base_fare" id="inp_base_fare" 
                                       value="<?php echo esc_attr( $val_base_fare ); ?>" placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_tax_amount"><?php esc_html_e( 'Tax (৳)', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-media-document field-icon"></span>
                                <input type="number" step="0.01" name="tax_amount" id="inp_tax_amount" 
                                       value="<?php echo esc_attr( $val_tax_amount ); ?>" placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_yq_tax"><?php esc_html_e( 'Fuel / YQ Tax (৳)', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-dashboard field-icon"></span>
                                <input type="number" step="0.01" name="yq_tax" id="inp_yq_tax" 
                                       value="<?php echo esc_attr( $val_yq_tax ); ?>" placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_comm"><?php esc_html_e( 'Commission (৳)', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-awards field-icon"></span>
                                <input type="number" step="0.01" name="commission_amount" id="inp_comm" 
                                       value="<?php echo esc_attr( $val_comm ); ?>" placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_ait"><?php esc_html_e( 'AIT (৳)', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-clipboard field-icon"></span>
                                <input type="number" step="0.01" name="ait_amount" id="inp_ait" 
                                       value="<?php echo esc_attr( $val_ait ); ?>" placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_discount"><?php esc_html_e( 'Discount (৳)', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-tag field-icon"></span>
                                <input type="number" step="0.01" name="discount_amount" id="inp_discount" 
                                       value="<?php echo esc_attr( $val_discount ); ?>" placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="ifs_buy_price"><?php esc_html_e( 'Cost Price (৳)', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-cart field-icon"></span>
                                <input type="number" step="0.01" name="buy_price" id="ifs_buy_price" required 
                                       value="<?php echo esc_attr( $val_buy ); ?>" placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="ifs_sell_price"><?php esc_html_e( 'Sale Price (৳)', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-money-alt field-icon"></span>
                                <input type="number" step="0.01" name="sell_price" id="ifs_sell_price" required 
                                       value="<?php echo esc_attr( $val_sell ); ?>" placeholder="0.00" class="ifs-input-field font-mono font-bold color-blue">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label"><?php esc_html_e( 'Profit (৳)', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-chart-line field-icon"></span>
                                <input type="text" id="ifs_profit_display" readonly 
                                       value="<?php echo esc_attr( number_format( (float) $val_profit, 2 ) ); ?>" 
                                       class="ifs-input-field font-mono font-bold <?php echo ( $val_profit >= 0 ) ? 'profit-positive' : 'profit-negative'; ?>">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_status"><?php esc_html_e( 'Status', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-yes-alt field-icon"></span>
                                <select name="status" id="inp_status" class="ifs-input-field">
                                    <option value="Issued" <?php selected( $val_status, 'Issued' ); ?>><?php esc_html_e( 'Issued / Confirmed', 'ifs-travel-erp' ); ?></option>
                                    <option value="Reissued" <?php selected( $val_status, 'Reissued' ); ?>><?php esc_html_e( 'Date Reissued', 'ifs-travel-erp' ); ?></option>
                                    <option value="Refunded" <?php selected( $val_status, 'Refunded' ); ?>><?php esc_html_e( 'Refunded', 'ifs-travel-erp' ); ?></option>
                                    <option value="Void" <?php selected( $val_status, 'Void' ); ?>><?php esc_html_e( 'Voided (Same Day)', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">04</div>
                        <div>
                            <h3 class="ifs-card-title"><?php esc_html_e( 'Payment & Files', 'ifs-travel-erp' ); ?></h3>
                            <p class="ifs-card-desc"><?php esc_html_e( 'Payment reconciliation, transaction reference IDs, operational remarks, and e-ticket document upload', 'ifs-travel-erp' ); ?></p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_pay_status"><?php esc_html_e( 'Pay Status', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-money-alt field-icon"></span>
                                <select name="payment_status" id="inp_pay_status" class="ifs-input-field">
                                    <option value="Paid" <?php selected( $val_pay_status, 'Paid' ); ?>><?php esc_html_e( 'Fully Paid', 'ifs-travel-erp' ); ?></option>
                                    <option value="Partial" <?php selected( $val_pay_status, 'Partial' ); ?>><?php esc_html_e( 'Partially Paid', 'ifs-travel-erp' ); ?></option>
                                    <option value="Due" <?php selected( $val_pay_status, 'Due' ); ?>><?php esc_html_e( 'Due / Credit Allowed', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_pay_method"><?php esc_html_e( 'Pay Method', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-vault field-icon"></span>
                                <select name="payment_method" id="inp_pay_method" class="ifs-input-field">
                                    <option value="Bank Transfer" <?php selected( $val_pay_method, 'Bank Transfer' ); ?>><?php esc_html_e( 'Bank Transfer (BEFTN/RTGS)', 'ifs-travel-erp' ); ?></option>
                                    <option value="Cash" <?php selected( $val_pay_method, 'Cash' ); ?>><?php esc_html_e( 'Cash at Counter', 'ifs-travel-erp' ); ?></option>
                                    <option value="bKash / MFS" <?php selected( $val_pay_method, 'bKash / MFS' ); ?>><?php esc_html_e( 'bKash / Nagad / Rocket', 'ifs-travel-erp' ); ?></option>
                                    <option value="Cheque" <?php selected( $val_pay_method, 'Cheque' ); ?>><?php esc_html_e( 'Cheque', 'ifs-travel-erp' ); ?></option>
                                    <option value="Credit Card" <?php selected( $val_pay_method, 'Credit Card' ); ?>><?php esc_html_e( 'Credit / Debit Card (POS)', 'ifs-travel-erp' ); ?></option>
                                    <option value="Agent Deposit" <?php selected( $val_pay_method, 'Agent Deposit' ); ?>><?php esc_html_e( 'Agent Credit Ledger', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_txn_id"><?php esc_html_e( 'Txn ID', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-id-alt field-icon"></span>
                                <input type="text" name="transaction_id" id="inp_txn_id" 
                                       value="<?php echo esc_attr( $val_txn_id ); ?>" 
                                       placeholder="e.g. TXN-9847291 / Chq#129" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_paid_amount"><?php esc_html_e( 'Paid Amount (৳)', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-money-alt field-icon"></span>
                                <input type="number" step="0.01" name="paid_amount" id="inp_paid_amount" 
                                       value="<?php echo esc_attr( $val_paid ); ?>" placeholder="0.00" class="ifs-input-field font-mono font-bold text-emerald">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label"><?php esc_html_e( 'Due Amount (৳)', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-warning field-icon"></span>
                                <input type="text" id="inp_due_amount_display" readonly 
                                       value="<?php echo esc_attr( number_format( (float) $val_due, 2 ) ); ?>" 
                                       class="ifs-input-field font-mono font-bold <?php echo ( $val_due > 0 ) ? 'profit-negative' : ''; ?>">
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-3">
                            <label class="ifs-field-label" for="inp_ticket_copy"><?php esc_html_e( 'Ticket Document & Live Preview', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-media-uploader-box">
                                <div class="ifs-field-wrap" style="flex: 1;">
                                    <span class="dashicons dashicons-pdf field-icon"></span>
                                    <input type="text" name="ticket_copy_url" id="inp_ticket_copy" 
                                           value="<?php echo esc_url( $val_tkt_copy ); ?>" 
                                           placeholder="<?php esc_attr_e( 'Attach airline e-ticket PDF / Image copy URL', 'ifs-travel-erp' ); ?>" class="ifs-input-field">
                                </div>
                                <button type="button" class="ifs-btn-upload" id="ifsUploadTktBtn">
                                    <span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Media Library', 'ifs-travel-erp' ); ?>
                                </button>
                            </div>

                            <div id="ifs_ticket_preview_thumb_wrap" style="margin-top: 12px; <?php echo empty( $val_tkt_copy ) ? 'display:none;' : 'display:flex;'; ?> align-items: center; gap: 14px; background: #f8fafc; border: 1px solid #cbd5e1; padding: 10px 14px; border-radius: 10px;">
                                <div id="ifs_ticket_thumb_box" style="width: 50px; height: 50px; border-radius: 6px; background: #ffffff; border: 1px dashed #94a3b8; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                    <?php if ( ! empty( $val_tkt_copy ) ) : ?>
                                        <?php if ( preg_match( '/\.(jpg|jpeg|png|webp)$/i', $val_tkt_copy ) ) : ?>
                                            <img src="<?php echo esc_url( $val_tkt_copy ); ?>" alt="<?php esc_attr_e( 'Ticket Preview', 'ifs-travel-erp' ); ?>" style="width: 100%; height: 100%; object-fit: cover;" />
                                        <?php else : ?>
                                            <span class="dashicons dashicons-pdf" style="color: #dc2626; font-size: 24px;"></span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 2px;">
                                    <span style="font-size: 12px; font-weight: 700; color: #0f172a;"><?php esc_html_e( 'Attached E-Ticket Document', 'ifs-travel-erp' ); ?></span>
                                    <a href="<?php echo esc_url( $val_tkt_copy ); ?>" target="_blank" rel="noopener noreferrer" id="ifs_ticket_preview_link" style="font-size: 11px; color: #0284c7; text-decoration: none; font-weight: 600;"><?php esc_html_e( 'Open Full Document', 'ifs-travel-erp' ); ?></a>
                                </div>
                                <button type="button" id="ifs_remove_ticket_btn" style="margin-left: auto; background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; border-radius: 6px; width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer;" title="<?php esc_attr_e( 'Remove Document', 'ifs-travel-erp' ); ?>">
                                    <span class="dashicons dashicons-trash" style="font-size: 14px; width: 14px; height: 14px;"></span>
                                </button>
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-3">
                            <label class="ifs-field-label" for="inp_remarks"><?php esc_html_e( 'Remarks', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-edit field-icon"></span>
                                <input type="text" name="remarks" id="inp_remarks" 
                                       value="<?php echo esc_attr( $val_remarks ); ?>" 
                                       placeholder="e.g. Seat 14A, Wheelchair Requested, Non-refundable Fare" class="ifs-input-field">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ifs-panel-card">
                    <div class="intel-head">
                        <span class="dashicons dashicons-chart-pie"></span> <?php esc_html_e( 'Margin Matrix', 'ifs-travel-erp' ); ?>
                    </div>
                    <div class="intel-body" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                        <div class="intel-row" style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0; display: flex; flex-direction: column;">
                            <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;"><?php esc_html_e( 'Net Margin:', 'ifs-travel-erp' ); ?></span>
                            <strong id="intel_profit" class="color-green" style="font-size: 16px; margin-top: 4px;">৳0.00</strong>
                        </div>
                        <div class="intel-row" style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0; display: flex; flex-direction: column;">
                            <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;"><?php esc_html_e( 'Yield Ratio:', 'ifs-travel-erp' ); ?></span>
                            <strong id="intel_ratio" style="font-size: 16px; margin-top: 4px; color: #003376;">0.0%</strong>
                        </div>
                        <div class="intel-row" style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0; display: flex; flex-direction: column;">
                            <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;"><?php esc_html_e( 'Net Comm:', 'ifs-travel-erp' ); ?></span>
                            <span id="intel_comm_net" class="font-mono" style="font-size: 16px; margin-top: 4px; font-weight: 800; color: #0f172a;">৳0.00</span>
                        </div>
                    </div>
                </div>

                <div class="ifs-action-strip">
                    <a href="<?php echo esc_url( $base_url . '&sub=list' ); ?>" class="ifs-btn-back">
                        <span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'Cancel', 'ifs-travel-erp' ); ?>
                    </a>
                    <button type="submit" name="ifs_ticket_submit" class="ifs-btn-primary">
                        <span class="dashicons dashicons-saved"></span> 
                        <?php echo $is_edit ? esc_html__( 'Update Ticket Record', 'ifs-travel-erp' ) : esc_html__( 'Save & Issue Ticket', 'ifs-travel-erp' ); ?>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <style>
        .ifs-ticket-workspace { 
            max-width: 1000px; 
            margin: 20px auto; 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; 
            color: #0f172a; 
            box-sizing: border-box;
        }
        .ifs-ticket-workspace *, 
        .ifs-ticket-workspace *::before, 
        .ifs-ticket-workspace *::after { 
            box-sizing: border-box; 
            box-shadow: none !important; 
            text-shadow: none !important; 
        }

        .ifs-toast { 
            padding: 13px 18px; 
            border-radius: 10px; 
            font-size: 13.5px; 
            font-weight: 600; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            margin-bottom: 22px; 
        }
        .ifs-toast.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .ifs-toast.danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .ifs-toast .dashicons { font-size: 18px; width: 18px; height: 18px; }

        .ifs-single-column-editor { display: flex; flex-direction: column; gap: 24px; }

        .ifs-panel-card { 
            background: #ffffff; 
            border: 1px solid #e2e8f0; 
            border-radius: 14px; 
            padding: 26px; 
        }
        .ifs-card-header { 
            display: flex; 
            align-items: center; 
            gap: 14px; 
            margin-bottom: 22px; 
            padding-bottom: 14px; 
            border-bottom: 1px solid #f1f5f9; 
        }
        .ifs-step-num { 
            width: 34px; 
            height: 34px; 
            border-radius: 9px; 
            background: #003376; 
            color: #ffffff; 
            font-weight: 800; 
            font-size: 13px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            flex-shrink: 0; 
        }
        .ifs-card-title { margin: 0; font-size: 15.5px; font-weight: 800; color: #0f172a; }
        .ifs-card-desc { margin: 2px 0 0 0; font-size: 12.5px; color: #64748b; }

        .ifs-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
        .col-span-2 { grid-column: span 2; }
        .col-span-3 { grid-column: span 3; }
        @media (max-width: 768px) { 
            .ifs-grid-3 { grid-template-columns: 1fr; } 
            .col-span-2, .col-span-3 { grid-column: span 1; } 
        }

        .ifs-field-block { 
            display: flex; 
            flex-direction: column; 
            justify-content: flex-start; 
            gap: 6px; 
            width: 100%; 
        }
        .ifs-field-label { 
            font-size: 11px; 
            font-weight: 700; 
            color: #475569; 
            text-transform: capitalize; 
            letter-spacing: 0.3px; 
            line-height: 1.2; 
        }
        .ifs-field-label .req { color: #e11d48; margin-left: 2px; }

        .ifs-field-wrap { 
            position: relative; 
            display: flex; 
            align-items: center; 
            width: 100%; 
            height: 42px; 
        }
        .ifs-field-wrap .field-icon { 
            position: absolute; 
            left: 12px; 
            top: 50%; 
            transform: translateY(-50%); 
            color: #94a3b8; 
            font-size: 18px; 
            width: 18px; 
            height: 18px; 
            line-height: 18px; 
            pointer-events: none; 
            z-index: 3; 
            transition: color 0.2s ease; 
        }

        .ifs-field-wrap .ifs-input-field { 
            width: 100% !important; 
            height: 42px !important; 
            max-height: 42px !important; 
            min-height: 42px !important; 
            line-height: 40px !important; 
            padding: 0 14px 0 40px !important; 
            border: 1px solid #cbd5e1 !important; 
            border-radius: 8px !important; 
            font-size: 13.5px !important; 
            color: #0f172a !important; 
            background-color: #ffffff !important; 
            outline: none !important; 
            transition: border-color 0.2s ease, background-color 0.2s ease; 
            margin: 0 !important; 
            display: block; 
        }

        .ifs-field-wrap select.ifs-input-field { 
            appearance: none; 
            -webkit-appearance: none; 
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e") !important; 
            background-repeat: no-repeat !important; 
            background-position: right 12px center !important; 
            background-size: 14px !important; 
            padding-right: 36px !important; 
            cursor: pointer; 
        }

        .ifs-field-wrap input[type="date"].ifs-input-field { 
            cursor: pointer; 
        }
        .ifs-field-wrap input[type="date"].ifs-input-field::-webkit-calendar-picker-indicator { 
            opacity: 0.6; 
            cursor: pointer; 
            margin-right: -4px; 
            transition: opacity 0.2s ease; 
        }
        .ifs-field-wrap input[type="date"].ifs-input-field::-webkit-calendar-picker-indicator:hover { 
            opacity: 1; 
        }

        .ifs-field-wrap .ifs-input-field:focus { 
            border-color: #003376 !important; 
            background-color: #f8fafc !important; 
        }
        .ifs-field-wrap:focus-within .field-icon { 
            color: #003376; 
        }

        .select2-container { width: 100% !important; }
        .select2-container .select2-selection--single { 
            height: 42px !important; 
            border: 1px solid #cbd5e1 !important; 
            border-radius: 8px !important; 
            padding: 0 12px !important; 
            display: flex !important; 
            align-items: center !important; 
            background: #ffffff !important; 
            outline: none !important; 
            transition: border-color 0.2s ease, background-color 0.2s ease; 
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered { 
            color: #0f172a !important; 
            font-size: 13.5px !important; 
            line-height: 40px !important; 
            padding-left: 0 !important; 
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow { 
            height: 40px !important; 
            right: 10px !important; 
        }
        .select2-container--default.select2-container--focus .select2-selection--single { 
            border-color: #003376 !important; 
            background-color: #f8fafc !important; 
        }
        .select2-dropdown { 
            border: 1px solid #cbd5e1 !important; 
            border-radius: 8px !important; 
            font-size: 13.5px !important; 
            z-index: 99999 !important; 
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] { 
            background-color: #003376 !important; 
            color: #ffffff !important; 
        }

        .uppercase { text-transform: uppercase; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important; }
        .font-bold { font-weight: 700; }
        .color-blue { color: #003376 !important; }
        .text-emerald { color: #059669 !important; }

        .profit-positive { background: #f0fdf4 !important; color: #166534 !important; border-color: #bbf7d0 !important; }
        .profit-negative { background: #fef2f2 !important; color: #dc2626 !important; border-color: #fecaca !important; }

        .ifs-media-uploader-box { display: flex; gap: 8px; align-items: center; width: 100%; }
        .ifs-btn-upload { 
            background: #f1f5f9; 
            border: 1px solid #cbd5e1; 
            height: 42px; 
            padding: 0 16px; 
            border-radius: 8px; 
            font-size: 12.5px; 
            font-weight: 600; 
            color: #334155; 
            cursor: pointer; 
            display: inline-flex; 
            align-items: center; 
            gap: 6px; 
            white-space: nowrap; 
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease; 
        }
        .ifs-btn-upload:hover { background: #003376; color: #ffffff; border-color: #003376; }

        .ifs-action-strip { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; }
        .ifs-btn-back { color: #64748b; text-decoration: none; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; transition: color 0.2s; }
        .ifs-btn-back:hover { color: #0f172a; }
        .ifs-btn-primary { background: #003376; color: #ffffff !important; border: none; height: 42px; padding: 0 26px; border-radius: 8px; font-size: 13.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: background-color 0.2s ease; }
        .ifs-btn-primary:hover { background: #0284c7; }

        .ifs-card-preview-header { font-size: 12px; font-weight: 800; text-transform: capitalize; letter-spacing: 0.3px; color: #475569; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; }
        .preview-header-left { display: flex; align-items: center; gap: 8px; }
        .pulse-beacon { width: 8px; height: 8px; border-radius: 50%; background: #10b981; }
        .preview-secure-tag { font-size: 10px; font-weight: 800; background: #e2e8f0; color: #475569; padding: 2px 7px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px; }
        .preview-secure-tag .dashicons { font-size: 12px; width: 12px; height: 12px; }

        .ifs-lux-boarding-pass { background: #00224f; border-radius: 18px; padding: 24px; color: #ffffff; position: relative; overflow: hidden; border: 1px solid #1e3a8a; }
        .pass-top-band { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .pass-carrier-wrap { display: flex; align-items: center; gap: 10px; }
        .carrier-tail-icon { width: 34px; height: 34px; border-radius: 8px; background: rgba(255, 255, 255, 0.15); display: flex; align-items: center; justify-content: center; color: #7dd3fc; border: 1px solid rgba(255, 255, 255, 0.2); }
        .carrier-tail-icon .dashicons { font-size: 18px; width: 18px; height: 18px; }
        .carrier-name { display: block; font-size: 12px; font-weight: 800; letter-spacing: 0.8px; color: #ffffff; line-height: 1.2; }
        .flight-no-badge { font-size: 10px; color: #7dd3fc; font-weight: 700; }
        .cabin-class-pill { background: rgba(255, 255, 255, 0.18); padding: 3px 10px; border-radius: 6px; font-size: 9.5px; font-weight: 800; letter-spacing: 0.6px; border: 1px solid rgba(255, 255, 255, 0.2); }

        .pass-route-arc-box { display: flex; justify-content: space-between; align-items: center; padding: 10px 0 16px 0; }
        .route-station { display: flex; flex-direction: column; }
        .route-station.dest { text-align: right; }
        .station-code { font-size: 32px; font-weight: 900; letter-spacing: -0.5px; color: #ffffff; line-height: 1; }
        .station-city { font-size: 10px; color: #93c5fd; text-transform: uppercase; font-weight: 600; margin-top: 3px; }
        .route-arc-visual { display: flex; flex-direction: column; align-items: center; position: relative; width: 220px; }
        @media (max-width: 640px) { .route-arc-visual { width: 100px; } }
        .arc-line-dotted { width: 100%; height: 2px; border-top: 2px dashed rgba(56, 189, 248, 0.5); position: absolute; top: 10px; z-index: 1; }
        .plane-flight-symbol { width: 22px; height: 22px; border-radius: 50%; background: #0284c7; display: flex; align-items: center; justify-content: center; position: relative; z-index: 2; color: #ffffff; }
        .plane-flight-symbol .dashicons { font-size: 12px; width: 12px; height: 12px; }
        .trip-tag-pill { font-size: 9px; font-weight: 800; background: rgba(0, 0, 0, 0.3); padding: 2px 9px; border-radius: 10px; margin-top: 8px; letter-spacing: 0.5px; border: 1px solid rgba(255, 255, 255, 0.1); }

        .pass-perforation-divider { position: relative; margin: 6px -24px 16px -24px; display: flex; align-items: center; }
        .perf-hole { width: 18px; height: 18px; background: #ffffff; border-radius: 50%; position: absolute; z-index: 3; }
        .perf-hole.left { left: -9px; }
        .perf-hole.right { right: -9px; }
        .perf-line { width: 100%; height: 1px; border-top: 1px dashed rgba(255, 255, 255, 0.25); }

        .pass-pax-hero { display: flex; justify-content: space-between; align-items: center; background: rgba(0, 0, 0, 0.2); padding: 10px 14px; border-radius: 10px; margin-bottom: 14px; border: 1px solid rgba(255, 255, 255, 0.08); }
        .pax-meta-cell { display: flex; flex-direction: column; gap: 2px; }
        .pax-meta-cell.text-right { text-align: right; }
        .pax-label { font-size: 8px; font-weight: 800; color: #7dd3fc; letter-spacing: 0.6px; text-transform: capitalize; }
        .pax-name-val { font-size: 13px; font-weight: 800; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 320px; }
        .pax-date-val { font-size: 11px; font-weight: 700; color: #ffffff; }

        .pass-specs-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px 14px; padding-bottom: 14px; margin-bottom: 14px; border-bottom: 1px dashed rgba(255, 255, 255, 0.2); }
        @media (max-width: 768px) { .pass-specs-grid { grid-template-columns: repeat(2, 1fr); } }
        .spec-cell { display: flex; flex-direction: column; gap: 2px; }
        .spec-label { font-size: 8px; font-weight: 800; color: #7dd3fc; letter-spacing: 0.6px; text-transform: capitalize; }
        .spec-value { font-size: 11.5px; font-weight: 700; color: #ffffff; }
        .color-cyan { color: #38bdf8 !important; }
        .color-green { color: #86efac !important; }
        .color-rose { color: #f87171 !important; }

        .pass-barcode-area { text-align: center; }
        .barcode-matrix-lines { height: 22px; background: repeating-linear-gradient(90deg, #ffffff, #ffffff 2px, transparent 2px, transparent 4px, #ffffff 4px, #ffffff 5px, transparent 5px, transparent 8px, #ffffff 8px, #ffffff 10px, transparent 10px, transparent 12px); opacity: 0.85; margin-bottom: 5px; border-radius: 2px; }
        .barcode-code-text { font-size: 8.5px; color: #93c5fd; letter-spacing: 1.5px; }

        .intel-head { font-size: 12px; font-weight: 800; color: #003376; text-transform: capitalize; letter-spacing: 0.3px; display: flex; align-items: center; gap: 6px; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9; }
        .intel-head .dashicons { color: #0284c7; font-size: 16px; width: 16px; height: 16px; }
    </style>

    <script>
    jQuery(document).ready(function($) {
        if ($.fn.select2) {
            $('.ifs-select2').select2({ width: '100%', allowClear: false });
        }

        const hiddenPaxName   = document.getElementById('inp_passenger_name_hidden');
        const hiddenPaxPhone  = document.getElementById('inp_passenger_phone_hidden');
        const hiddenPaxEmail  = document.getElementById('inp_passenger_email_hidden');
        const hiddenPassNo    = document.getElementById('inp_passport_no_hidden');

        const inpPnr          = document.getElementById('inp_pnr');
        const inpAirPnr       = document.getElementById('inp_airline_pnr');
        const inpTicket       = document.getElementById('inp_ticket');
        const inpAirline      = document.getElementById('inp_airline');
        const inpFlightNo     = document.getElementById('inp_flight_no');
        const inpOrigin       = document.getElementById('inp_origin');
        const inpDest         = document.getElementById('inp_dest');
        const inpCabin        = document.getElementById('inp_cabin');
        const inpBaggage      = document.getElementById('inp_baggage');
        const inpFlightType   = document.getElementById('inp_flight_type');
        const inpTravelDate   = document.getElementById('inp_travel_date');
        const inpFlightTime   = document.getElementById('inp_flight_time');
        
        const inpBaseFare     = document.getElementById('inp_base_fare');
        const inpTaxAmount    = document.getElementById('inp_tax_amount');
        const inpYqTax        = document.getElementById('inp_yq_tax');
        const inpComm         = document.getElementById('inp_comm');
        const inpAit          = document.getElementById('inp_ait');
        const inpDiscount     = document.getElementById('inp_discount');
        const inpBuy          = document.getElementById('ifs_buy_price');
        const inpSell         = document.getElementById('ifs_sell_price');
        const inpPaid         = document.getElementById('inp_paid_amount');
        const inpDueDisplay   = document.getElementById('inp_due_amount_display');
        const inpPayStatus    = document.getElementById('inp_pay_status');
        
        const wrapReturnSegment = document.getElementById('wrap_return_segment');

        const prevAirline     = document.getElementById('prev_airline');
        const prevCabin       = document.getElementById('prev_cabin');
        const prevOrigin      = document.getElementById('prev_origin');
        const prevDest        = document.getElementById('prev_dest');
        const prevFlightNo    = document.getElementById('prev_flight_no');
        const prevType        = document.getElementById('prev_type');
        const prevPaxName     = document.getElementById('prev_pax_name');
        const prevDate        = document.getElementById('prev_date');
        const prevPnr         = document.getElementById('prev_pnr');
        const prevBaggage     = document.getElementById('prev_baggage');
        const prevTkt         = document.getElementById('prev_tkt');
        const prevSell        = document.getElementById('prev_sell');
        const prevBarcode     = document.getElementById('prev_barcode');
        
        const profitDisplay   = document.getElementById('ifs_profit_display');
        const intelProfit     = document.getElementById('intel_profit');
        const intelRatio      = document.getElementById('intel_ratio');
        const intelCommNet    = document.getElementById('intel_comm_net');

        function updateTicketPass() {
            if (prevAirline) prevAirline.textContent = (inpAirline && inpAirline.value.trim()) ? inpAirline.value.trim().toUpperCase() : 'AIRLINE CARRIER';
            if (prevCabin) prevCabin.textContent = (inpCabin) ? inpCabin.value.toUpperCase() : 'ECONOMY';
            if (prevFlightNo) prevFlightNo.textContent = (inpFlightNo && inpFlightNo.value.trim()) ? inpFlightNo.value.trim().toUpperCase() : 'FLIGHT';
            if (prevType) prevType.textContent = (inpFlightType) ? inpFlightType.value.toUpperCase() : 'ONE WAY';
            if (prevBaggage) prevBaggage.textContent = (inpBaggage && inpBaggage.value.trim()) ? inpBaggage.value.trim().toUpperCase() : '20 KG';

            let orig = (inpOrigin && inpOrigin.value.trim()) ? inpOrigin.value.trim().toUpperCase() : 'DAC';
            let dest = (inpDest && inpDest.value.trim()) ? inpDest.value.trim().toUpperCase() : 'DXB';
            
            if (prevOrigin) prevOrigin.textContent = orig;
            if (prevDest) prevDest.textContent = dest;

            let paxStr = 'SELECT TRAVELER';
            let passStr = 'NOT SET';
            const selectedOpt = $('#inp_customer').find(':selected');
            if (selectedOpt && selectedOpt.val()) {
                const paxName  = selectedOpt.attr('data-name');
                const paxPhone = selectedOpt.attr('data-phone');
                const paxEmail = selectedOpt.attr('data-email');
                const passNo   = selectedOpt.attr('data-passport');
                
                if (paxName) {
                    paxStr = paxName;
                } else if (selectedOpt.text()) {
                    paxStr = selectedOpt.text().split('(')[0].trim();
                }
                passStr = passNo || 'NOT SET';
                if (hiddenPaxName) hiddenPaxName.value   = paxStr;
                if (hiddenPaxPhone) hiddenPaxPhone.value = paxPhone || '';
                if (hiddenPaxEmail) hiddenPaxEmail.value = paxEmail || '';
                if (hiddenPassNo) hiddenPassNo.value     = passStr;
            }
            if (prevPaxName) {
                prevPaxName.textContent = paxStr + (passStr && passStr !== 'NOT SET' ? ' [PPT: ' + passStr + ']' : '');
            }

            if (inpTravelDate && inpTravelDate.value) {
                const d = new Date(inpTravelDate.value);
                if (!isNaN(d)) {
                    const options = { day: '2-digit', month: 'short', year: 'numeric' };
                    const timeStr = (inpFlightTime && inpFlightTime.value.trim()) ? ' (' + inpFlightTime.value.trim() + ')' : '';
                    if (prevDate) prevDate.textContent = d.toLocaleDateString('en-GB', options).toUpperCase() + timeStr;
                }
            }

            const pnrVal    = (inpPnr && inpPnr.value.trim()) ? inpPnr.value.trim().toUpperCase() : '------';
            const airPnrVal = (inpAirPnr && inpAirPnr.value.trim()) ? ' / ' + inpAirPnr.value.trim().toUpperCase() : '';
            const tktVal    = (inpTicket && inpTicket.value.trim()) ? inpTicket.value.trim() : '077-XXXXXXXXXX';

            if (prevPnr) prevPnr.textContent = pnrVal + airPnrVal;
            if (prevTkt) prevTkt.textContent = tktVal;

            const baseVal = parseFloat(inpBaseFare ? inpBaseFare.value : 0) || 0;
            const taxVal  = parseFloat(inpTaxAmount ? inpTaxAmount.value : 0) || 0;
            const yqVal   = parseFloat(inpYqTax ? inpYqTax.value : 0) || 0;

            if ((baseVal > 0 || taxVal > 0) && (!inpBuy.value || inpBuy.value == '0' || inpBuy.value == '0.00')) {
                inpBuy.value = (baseVal + taxVal + yqVal).toFixed(2);
            }

            if (baseVal > 0 && (!inpAit.value || inpAit.value == '0' || inpAit.value == '0.00')) {
                inpAit.value = (baseVal * 0.003).toFixed(2);
            }

            const buyVal   = parseFloat(inpBuy ? inpBuy.value : 0) || 0;
            const sellVal  = parseFloat(inpSell ? inpSell.value : 0) || 0;
            const commVal  = parseFloat(inpComm ? inpComm.value : 0) || 0;
            const aitVal   = parseFloat(inpAit ? inpAit.value : 0) || 0;
            const discVal  = parseFloat(inpDiscount ? inpDiscount.value : 0) || 0;

            const profit   = (sellVal - buyVal) + commVal - discVal - aitVal;
            const ratio    = sellVal > 0 ? ((profit / sellVal) * 100).toFixed(1) : '0.0';

            if (inpPayStatus && inpPayStatus.value === 'Paid' && (!inpPaid.value || parseFloat(inpPaid.value) === 0)) {
                inpPaid.value = sellVal.toFixed(2);
            }
            const paidVal = parseFloat(inpPaid ? inpPaid.value : 0) || 0;
            const dueVal  = Math.max(0, sellVal - paidVal);

            if (inpDueDisplay) {
                inpDueDisplay.value = dueVal.toLocaleString('en-US', { minimumFractionDigits: 2 });
                inpDueDisplay.className = 'ifs-input-field font-mono font-bold ' + (dueVal > 0 ? 'profit-negative' : '');
            }

            if (prevSell) prevSell.textContent = '৳' + sellVal.toLocaleString('en-US', { minimumFractionDigits: 2 });
            if (profitDisplay) {
                profitDisplay.value = profit.toLocaleString('en-US', { minimumFractionDigits: 2 });
                profitDisplay.className = 'ifs-input-field font-mono font-bold ' + (profit >= 0 ? 'profit-positive' : 'profit-negative');
            }

            if (intelProfit) {
                intelProfit.textContent = '৳' + profit.toLocaleString('en-US', { minimumFractionDigits: 2 });
                intelProfit.className = (profit >= 0) ? 'color-green' : 'color-rose';
            }
            if (intelRatio) intelRatio.textContent = ratio + '%';
            if (intelCommNet) intelCommNet.textContent = '৳' + (commVal - aitVal).toLocaleString('en-US', { minimumFractionDigits: 2 });

            const cleanPax = paxStr.replace(/[^A-Za-z]/g, '').slice(0, 10).toUpperCase();
            if (prevBarcode) {
                prevBarcode.textContent = 'M1' + (cleanPax || 'PASSENGER') + ' E' + pnrVal + ' ' + orig + dest + ' ' + (inpFlightNo ? inpFlightNo.value.trim().toUpperCase() : 'FLT') + ' 233Y';
            }

            if (inpFlightType) {
                const isRT = (inpFlightType.value === 'Round Trip');
                if (wrapReturnSegment) wrapReturnSegment.style.display = isRT ? 'block' : 'none';
            }
        }

        $('#ifsTicketForm input, #ifsTicketForm select, #ifsTicketForm textarea').on('input change keyup paste select2:select', function() {
            updateTicketPass();
        });

        updateTicketPass();

        // WP Media Uploader Integration with Live Thumbnail Preview
        const uploadBtn = document.getElementById('ifsUploadTktBtn');
        const copyInput = document.getElementById('inp_ticket_copy');
        const thumbBox  = document.getElementById('ifs_ticket_thumb_box');
        const previewWrap = document.getElementById('ifs_ticket_preview_thumb_wrap');
        const previewLink = document.getElementById('ifs_ticket_preview_link');
        const removeBtn = document.getElementById('ifs_remove_ticket_btn');

        function renderTicketThumb(url) {
            if (!url) {
                if (previewWrap) previewWrap.style.display = 'none';
                return;
            }
            if (previewWrap) previewWrap.style.display = 'flex';
            if (previewLink) previewLink.href = url;

            if (url.match(/\.(jpg|jpeg|png|webp|gif)$/i)) {
                thumbBox.innerHTML = '<img src="' + url + '" alt="Ticket" style="width: 100%; height: 100%; object-fit: cover;" />';
            } else {
                thumbBox.innerHTML = '<span class="dashicons dashicons-pdf" style="color: #dc2626; font-size: 24px;"></span>';
            }
        }

        if (uploadBtn && window.wp && wp.media) {
            uploadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const customUploader = wp.media({
                    title: 'Select E-Ticket PDF or Scan',
                    button: { text: 'Attach File' },
                    multiple: false
                }).on('select', function() {
                    const attachment = customUploader.state().get('selection').first().toJSON();
                    if (copyInput && attachment.url) {
                        copyInput.value = attachment.url;
                        renderTicketThumb(attachment.url);
                    }
                }).open();
            });
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (copyInput) copyInput.value = '';
                renderTicketThumb('');
            });
        }
    });
    </script>
    <?php
}