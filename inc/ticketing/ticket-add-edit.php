<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enterprise Next-Gen Air Ticket Issuance Console (100% GDS, IATA & BSP Ready)
 * Features: Dual PNRs (GDS & Airline), Fare Basis, Transit Stops, Luxury Boarding Pass Preview & Yield Matrix
 */
function ifs_terp_ticket_add_edit_page() {
    global $wpdb;
    $table_tickets   = $wpdb->prefix . 'iterp_tickets';
    $table_customers = $wpdb->prefix . 'iterp_customers';
    $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
    $table_agents    = $wpdb->prefix . 'iterp_agents';
    
    $id       = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
    $is_edit  = ( $id > 0 );
    $message  = '';
    $errors   = array();
    $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=ticketing' );

    if ( function_exists( 'wp_enqueue_media' ) ) {
        wp_enqueue_media();
    }

    // Process Ticket Submission
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_ticket_submit'] ) ) {
        check_admin_referer( 'ifs_ticket_save_action', 'ifs_ticket_nonce' );

        $customer_id        = intval( $_POST['customer_id'] ?? 0 );
        $passenger_name     = sanitize_text_field( $_POST['passenger_name'] ?? '' );
        $passport_no        = strtoupper( sanitize_text_field( $_POST['passport_no'] ?? '' ) );
        $agent_id           = intval( $_POST['agent_id'] ?? 0 );
        $supplier_id        = intval( $_POST['supplier_id'] ?? 0 );
        
        $pnr                = strtoupper( sanitize_text_field( $_POST['pnr'] ?? '' ) );
        $airline_pnr        = strtoupper( sanitize_text_field( $_POST['airline_pnr'] ?? '' ) );
        $ticket_no          = sanitize_text_field( $_POST['ticket_no'] ?? '' );
        $airline            = sanitize_text_field( $_POST['airline'] ?? '' );
        $flight_no          = strtoupper( sanitize_text_field( $_POST['flight_no'] ?? '' ) );
        $sector             = strtoupper( sanitize_text_field( $_POST['sector'] ?? '' ) );
        $via_transit        = sanitize_text_field( $_POST['via_transit'] ?? 'Direct' );
        $cabin_class        = sanitize_text_field( $_POST['cabin_class'] ?? 'Economy' );
        $fare_basis         = strtoupper( sanitize_text_field( $_POST['fare_basis'] ?? '' ) );
        $flight_type        = sanitize_text_field( $_POST['flight_type'] ?? 'One Way' );
        
        $travel_date        = sanitize_text_field( $_POST['travel_date'] ?? '' );
        $flight_time        = sanitize_text_field( $_POST['flight_time'] ?? '' );
        $return_date        = sanitize_text_field( $_POST['return_date'] ?? '' );
        $return_flight_time = sanitize_text_field( $_POST['return_flight_time'] ?? '' );
        $baggage            = sanitize_text_field( $_POST['baggage'] ?? '20 KG' );
        $gds_pcc            = sanitize_text_field( $_POST['gds_pcc'] ?? 'Sabre' );
        
        // Financials
        $base_fare          = floatval( $_POST['base_fare'] ?? 0 );
        $tax_amount         = floatval( $_POST['tax_amount'] ?? 0 );
        $commission_amount  = floatval( $_POST['commission_amount'] ?? 0 );
        $ait_amount         = floatval( $_POST['ait_amount'] ?? 0 );
        $discount_amount    = floatval( $_POST['discount_amount'] ?? 0 );
        $buy_price          = floatval( $_POST['buy_price'] ?? 0 );
        $sell_price         = floatval( $_POST['sell_price'] ?? 0 );
        
        // Calculated Net Agency Profit
        $profit             = ( $sell_price - $buy_price ) + $commission_amount - $discount_amount - $ait_amount;
        
        $status             = sanitize_text_field( $_POST['status'] ?? 'Issued' );
        $payment_status     = sanitize_text_field( $_POST['payment_status'] ?? 'Paid' );
        $payment_method     = sanitize_text_field( $_POST['payment_method'] ?? 'Bank Transfer' );
        $remarks            = sanitize_textarea_field( $_POST['remarks'] ?? '' );
        $ticket_copy_url    = esc_url_raw( $_POST['ticket_copy_url'] ?? '' );

        if ( empty( $customer_id ) ) {
            $errors[] = 'Please select a passenger for this ticket.';
        }
        if ( empty( $pnr ) ) {
            $errors[] = 'GDS PNR / Booking Reference is mandatory.';
        }
        if ( empty( $ticket_no ) ) {
            $errors[] = 'E-Ticket Number is required.';
        }
        if ( empty( $travel_date ) ) {
            $errors[] = 'Flight departure date is required.';
        }

        if ( empty( $errors ) ) {
            $data = array(
                'customer_id'        => $customer_id,
                'passenger_name'     => $passenger_name,
                'passport_no'        => $passport_no,
                'agent_id'           => $agent_id,
                'supplier_id'        => $supplier_id,
                'pnr'                => $pnr,
                'airline_pnr'        => $airline_pnr,
                'ticket_no'          => $ticket_no,
                'airline'            => $airline,
                'flight_no'          => $flight_no,
                'sector'             => $sector,
                'via_transit'        => $via_transit,
                'cabin_class'        => $cabin_class,
                'fare_basis'         => $fare_basis,
                'flight_type'        => $flight_type,
                'travel_date'        => $travel_date,
                'flight_time'        => $flight_time,
                'return_date'        => ( $flight_type === 'Round Trip' && ! empty( $return_date ) ) ? $return_date : '1970-01-01',
                'return_flight_time' => ( $flight_type === 'Round Trip' ) ? $return_flight_time : '',
                'baggage'            => $baggage,
                'gds_pcc'            => $gds_pcc,
                'base_fare'          => $base_fare,
                'tax_amount'         => $tax_amount,
                'commission_amount'  => $commission_amount,
                'ait_amount'         => $ait_amount,
                'discount_amount'    => $discount_amount,
                'buy_price'          => $buy_price,
                'sell_price'         => $sell_price,
                'profit'             => $profit,
                'status'             => $status,
                'payment_status'     => $payment_status,
                'payment_method'     => $payment_method,
                'remarks'            => $remarks,
                'ticket_copy_url'    => $ticket_copy_url,
                'issued_by'          => get_current_user_id()
            );

            if ( $is_edit ) {
                $wpdb->update( $table_tickets, $data, array( 'id' => $id ) );
                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Air Ticket record updated successfully.</div>';
            } else {
                $data['created_at'] = current_time( 'mysql' );
                $wpdb->insert( $table_tickets, $data );
                $id      = $wpdb->insert_id;
                $is_edit = true;
                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> New Air Ticket registered (#TKT-' . str_pad( (string) $id, 5, '0', STR_PAD_LEFT ) . ').</div>';
            }

            if ( function_exists( 'ifs_terp_log_activity' ) ) {
                ifs_terp_log_activity( "Processed E-Ticket #TKT-$id | PNR: $pnr | Fare: ৳$sell_price" );
            }
        } else {
            $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> ' . implode( '<br>', $errors ) . '</div>';
        }
    }

    $row = false;
    if ( $is_edit ) {
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_tickets WHERE id = %d", $id ) );
    }

    $customers = $wpdb->get_results( "SELECT id, title, full_name, mobile, passport_no FROM $table_customers ORDER BY full_name ASC" );
    $suppliers = $wpdb->get_results( "SELECT id, supplier_name, current_balance FROM $table_suppliers WHERE status = 'Active' ORDER BY supplier_name ASC" );
    $agents    = $wpdb->get_results( "SELECT id, agency_name, current_balance FROM $table_agents WHERE status = 'Active' ORDER BY agency_name ASC" );

    // Field Defaults
    $val_customer     = $is_edit ? intval( $row->customer_id ) : 0;
    $val_pax_name     = $is_edit ? esc_attr( $row->passenger_name ?? '' ) : '';
    $val_passport     = $is_edit ? esc_attr( $row->passport_no ?? '' ) : '';
    $val_agent        = $is_edit ? intval( $row->agent_id ?? 0 ) : 0;
    $val_supplier     = $is_edit ? intval( $row->supplier_id ?? 0 ) : 0;
    $val_pnr          = $is_edit ? esc_attr( $row->pnr ) : '';
    $val_airline_pnr  = $is_edit ? esc_attr( $row->airline_pnr ?? '' ) : '';
    $val_ticket_no    = $is_edit ? esc_attr( $row->ticket_no ) : '';
    $val_airline      = $is_edit ? esc_attr( $row->airline ) : '';
    $val_flight_no    = $is_edit ? esc_attr( $row->flight_no ?? '' ) : '';
    $val_sector       = $is_edit ? esc_attr( $row->sector ) : 'DAC-DXB';
    $val_transit      = $is_edit ? esc_attr( $row->via_transit ?? 'Direct' ) : 'Direct';
    $val_cabin        = $is_edit ? esc_attr( $row->cabin_class ) : 'Economy';
    $val_fare_basis   = $is_edit ? esc_attr( $row->fare_basis ?? '' ) : '';
    $val_flight_type  = $is_edit ? esc_attr( $row->flight_type ?? 'One Way' ) : 'One Way';
    
    $val_travel_date  = $is_edit ? esc_attr( $row->travel_date ) : date( 'Y-m-d', strtotime( '+3 days' ) );
    $val_flight_time  = $is_edit ? esc_attr( $row->flight_time ?? '' ) : '21:30';
    $val_return_date  = ( $is_edit && ! empty( $row->return_date ) && $row->return_date !== '1970-01-01' ) ? esc_attr( $row->return_date ) : '';
    $val_ret_time     = $is_edit ? esc_attr( $row->return_flight_time ?? '' ) : '';
    $val_baggage      = $is_edit ? esc_attr( $row->baggage ?? '20 KG' ) : '20 KG';
    $val_gds          = $is_edit ? esc_attr( $row->gds_pcc ?? 'Sabre' ) : 'Sabre';
    
    $val_base_fare    = $is_edit ? floatval( $row->base_fare ?? 0 ) : '';
    $val_tax_amount   = $is_edit ? floatval( $row->tax_amount ?? 0 ) : '';
    $val_comm         = $is_edit ? floatval( $row->commission_amount ?? 0 ) : '';
    $val_ait          = $is_edit ? floatval( $row->ait_amount ?? 0 ) : '';
    $val_discount     = $is_edit ? floatval( $row->discount_amount ?? 0 ) : '';
    $val_buy          = $is_edit ? floatval( $row->buy_price ) : '';
    $val_sell         = $is_edit ? floatval( $row->sell_price ) : '';
    $val_profit       = $is_edit ? floatval( $row->profit ) : 0;
    
    $val_status       = $is_edit ? esc_attr( $row->status ) : 'Issued';
    $val_pay_status   = $is_edit ? esc_attr( $row->payment_status ?? 'Paid' ) : 'Paid';
    $val_pay_method   = $is_edit ? esc_attr( $row->payment_method ?? 'Bank Transfer' ) : 'Bank Transfer';
    $val_remarks      = $is_edit ? esc_textarea( $row->remarks ?? '' ) : '';
    $val_tkt_copy     = $is_edit ? esc_url( $row->ticket_copy_url ?? '' ) : '';
    ?>

    <div class="ifs-ticket-workspace">
        <?php echo $message; ?>

        <form method="post" action="" id="ifsTicketForm" class="ifs-split-ticket-editor">
            <?php wp_nonce_field( 'ifs_ticket_save_action', 'ifs_ticket_nonce' ); ?>

            <div class="ifs-ticket-form-body">
                
                <!-- Section 1: Passenger, Sub-Agent & Dual PNR Setup -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">01</div>
                        <div>
                            <h3 class="ifs-card-title">Passenger, B2B Agent & Dual PNR Identification</h3>
                            <p class="ifs-card-desc">Assign customer dossier, B2B sub-agent ledger, GDS PNR, and Airline confirmation code</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <div class="ifs-field-block col-span-2">
                            <label class="ifs-field-label" for="inp_customer">Passenger Portfolio <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-admin-users field-icon"></span>
                                <select name="customer_id" id="inp_customer" required class="ifs-input-field">
                                    <option value="">-- Choose Registered Traveler --</option>
                                    <?php foreach ( $customers as $cus ) : 
                                        $t_prefix = ! empty( $cus->title ) ? $cus->title . '. ' : '';
                                        $p_meta   = ! empty( $cus->passport_no ) ? ' [PPT: ' . $cus->passport_no . ']' : '';
                                    ?>
                                        <option value="<?php echo $cus->id; ?>" 
                                                data-name="<?php echo esc_attr( $t_prefix . $cus->full_name ); ?>"
                                                data-passport="<?php echo esc_attr( $cus->passport_no ?: 'NOT SET' ); ?>"
                                                <?php selected( $val_customer, $cus->id ); ?>>
                                            <?php echo esc_html( $t_prefix . $cus->full_name . ' (' . $cus->mobile . ')' . $p_meta ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <input type="hidden" name="passenger_name" id="inp_passenger_name_hidden" value="<?php echo $val_pax_name; ?>">
                            <input type="hidden" name="passport_no" id="inp_passport_no_hidden" value="<?php echo $val_passport; ?>">
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_agent">B2B Sub-Agent (If Any)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-groups field-icon"></span>
                                <select name="agent_id" id="inp_agent" class="ifs-input-field">
                                    <option value="0">Direct Retail Passenger</option>
                                    <?php foreach ( $agents as $ag ) : ?>
                                        <option value="<?php echo $ag->id; ?>" <?php selected( $val_agent, $ag->id ); ?>>
                                            <?php echo esc_html( $ag->agency_name . ' (Bal: ৳' . number_format( $ag->current_balance, 0 ) . ')' ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_supplier">Supplier / Consortia Portal</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-networking field-icon"></span>
                                <select name="supplier_id" id="inp_supplier" class="ifs-input-field">
                                    <option value="0">Direct IATA / BSP</option>
                                    <?php foreach ( $suppliers as $sup ) : ?>
                                        <option value="<?php echo $sup->id; ?>" <?php selected( $val_supplier, $sup->id ); ?>>
                                            <?php echo esc_html( $sup->supplier_name . ' (Bal: ৳' . number_format( $sup->current_balance, 0 ) . ')' ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_gds">GDS Platform / Aggregator</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-admin-site-alt3 field-icon"></span>
                                <select name="gds_pcc" id="inp_gds" class="ifs-input-field">
                                    <option value="Sabre" <?php selected( $val_gds, 'Sabre' ); ?>>Sabre GDS</option>
                                    <option value="Amadeus" <?php selected( $val_gds, 'Amadeus' ); ?>>Amadeus GDS</option>
                                    <option value="Galileo" <?php selected( $val_gds, 'Galileo' ); ?>>Travelport / Galileo</option>
                                    <option value="FlyHub" <?php selected( $val_gds, 'FlyHub' ); ?>>FlyHub B2B</option>
                                    <option value="ShareTrip" <?php selected( $val_gds, 'ShareTrip' ); ?>>ShareTrip B2B</option>
                                    <option value="Airline Portal" <?php selected( $val_gds, 'Airline Portal' ); ?>>Direct Airline Web</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_pnr">GDS PNR (Booking Ref) <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-randomize field-icon"></span>
                                <input type="text" name="pnr" id="inp_pnr" required 
                                       value="<?php echo $val_pnr; ?>" 
                                       placeholder="e.g. 7X9K2L" class="ifs-input-field uppercase font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_airline_pnr">Airline Confirmation PNR</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-tag field-icon"></span>
                                <input type="text" name="airline_pnr" id="inp_airline_pnr" 
                                       value="<?php echo $val_airline_pnr; ?>" 
                                       placeholder="e.g. EK-89QPZ" class="ifs-input-field uppercase font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-2">
                            <label class="ifs-field-label" for="inp_ticket">E-Ticket Number (13/14 Digits) <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-tickets-alt field-icon"></span>
                                <input type="text" name="ticket_no" id="inp_ticket" required 
                                       value="<?php echo $val_ticket_no; ?>" 
                                       placeholder="077-1234567890" class="ifs-input-field font-mono">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Flight Segment & Sector Routing -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">02</div>
                        <div>
                            <h3 class="ifs-card-title">Flight Details & Routing Sector</h3>
                            <p class="ifs-card-desc">Carrier, flight schedule, baggage allowance, transit stops, and fare basis</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_airline">Operating Carrier <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-airplane field-icon"></span>
                                <input type="text" name="airline" id="inp_airline" required 
                                       value="<?php echo $val_airline; ?>" 
                                       placeholder="e.g. Emirates / Biman" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_flight_no">Flight Number</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-location-alt field-icon"></span>
                                <input type="text" name="flight_no" id="inp_flight_no" 
                                       value="<?php echo $val_flight_no; ?>" 
                                       placeholder="e.g. EK-585 / BG-047" class="ifs-input-field uppercase font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_sector">Sector / Routing Code <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-location field-icon"></span>
                                <input type="text" name="sector" id="inp_sector" required 
                                       value="<?php echo $val_sector; ?>" 
                                       placeholder="e.g. DAC-DXB-LHR" class="ifs-input-field uppercase font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_transit">Transit / Via Stops</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-admin-site field-icon"></span>
                                <input type="text" name="via_transit" id="inp_transit" 
                                       value="<?php echo $val_transit; ?>" placeholder="Direct / DOH / DXB" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_flight_type">Trip Type</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-image-rotate field-icon"></span>
                                <select name="flight_type" id="inp_flight_type" class="ifs-input-field">
                                    <option value="One Way" <?php selected( $val_flight_type, 'One Way' ); ?>>One Way Flight</option>
                                    <option value="Round Trip" <?php selected( $val_flight_type, 'Round Trip' ); ?>>Round Trip (Return)</option>
                                    <option value="Multi City" <?php selected( $val_flight_type, 'Multi City' ); ?>>Multi City Routing</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_cabin">Cabin Class</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-tag field-icon"></span>
                                <select name="cabin_class" id="inp_cabin" class="ifs-input-field">
                                    <option value="Economy" <?php selected( $val_cabin, 'Economy' ); ?>>Economy Class</option>
                                    <option value="Premium Economy" <?php selected( $val_cabin, 'Premium Economy' ); ?>>Premium Economy</option>
                                    <option value="Business" <?php selected( $val_cabin, 'Business' ); ?>>Business Class</option>
                                    <option value="First Class" <?php selected( $val_cabin, 'First Class' ); ?>>First Class</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_fare_basis">Fare Basis Code</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-media-code field-icon"></span>
                                <input type="text" name="fare_basis" id="inp_fare_basis" 
                                       value="<?php echo $val_fare_basis; ?>" placeholder="e.g. YOWBD / K21RT" class="ifs-input-field uppercase font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_baggage">Baggage Allowance</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-portfolio field-icon"></span>
                                <input type="text" name="baggage" id="inp_baggage" 
                                       value="<?php echo $val_baggage; ?>" 
                                       placeholder="e.g. 20 KG / 2 PC" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_travel_date">Departure Date <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-calendar-alt field-icon"></span>
                                <input type="date" name="travel_date" id="inp_travel_date" required 
                                       value="<?php echo $val_travel_date; ?>" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_flight_time">Departure Time</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-clock field-icon"></span>
                                <input type="text" name="flight_time" id="inp_flight_time" 
                                       value="<?php echo $val_flight_time; ?>" placeholder="e.g. 21:30" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block" id="wrap_return_date" style="<?php echo ( $val_flight_type !== 'Round Trip' ) ? 'display:none;' : ''; ?>">
                            <label class="ifs-field-label" for="inp_return_date">Return Flight Date</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-calendar field-icon"></span>
                                <input type="date" name="return_date" id="inp_return_date" 
                                       value="<?php echo $val_return_date; ?>" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block" id="wrap_return_time" style="<?php echo ( $val_flight_type !== 'Round Trip' ) ? 'display:none;' : ''; ?>">
                            <label class="ifs-field-label" for="inp_ret_time">Return Departure Time</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-clock field-icon"></span>
                                <input type="text" name="return_flight_time" id="inp_ret_time" 
                                       value="<?php echo $val_ret_time; ?>" placeholder="e.g. 14:45" class="ifs-input-field font-mono">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Commercial Fare, Tax & Profit Calculation -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">03</div>
                        <div>
                            <h3 class="ifs-card-title">Commercial Fare, Tax & Profit Calculation</h3>
                            <p class="ifs-card-desc">Base fare, taxes, commission, AIT, client selling invoice, and live calculated agency profit</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_base_fare">Base Fare (৳)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-money-alt field-icon"></span>
                                <input type="number" step="0.01" name="base_fare" id="inp_base_fare" 
                                       value="<?php echo $val_base_fare; ?>" placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_tax_amount">Taxes & Surcharges (৳)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-media-document field-icon"></span>
                                <input type="number" step="0.01" name="tax_amount" id="inp_tax_amount" 
                                       value="<?php echo $val_tax_amount; ?>" placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_comm">Agency Commission (৳)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-awards field-icon"></span>
                                <input type="number" step="0.01" name="commission_amount" id="inp_comm" 
                                       value="<?php echo $val_comm; ?>" placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_ait">AIT 0.3% Source Tax (৳)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-clipboard field-icon"></span>
                                <input type="number" step="0.01" name="ait_amount" id="inp_ait" 
                                       value="<?php echo $val_ait; ?>" placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_discount">Discount / Rebate Allowed (৳)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-tag field-icon"></span>
                                <input type="number" step="0.01" name="discount_amount" id="inp_discount" 
                                       value="<?php echo $val_discount; ?>" placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="ifs_buy_price">Supplier / Net Cost Rate (৳) <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-cart field-icon"></span>
                                <input type="number" step="0.01" name="buy_price" id="ifs_buy_price" required 
                                       value="<?php echo $val_buy; ?>" placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="ifs_sell_price">Client / Selling Rate (৳) <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-money-alt field-icon"></span>
                                <input type="number" step="0.01" name="sell_price" id="ifs_sell_price" required 
                                       value="<?php echo $val_sell; ?>" placeholder="0.00" class="ifs-input-field font-mono font-bold color-blue">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label">Gross Margin / Profit (৳)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-chart-line field-icon"></span>
                                <input type="text" id="ifs_profit_display" readonly 
                                       value="<?php echo number_format( $val_profit, 2 ); ?>" 
                                       class="ifs-input-field font-mono font-bold <?php echo ( $val_profit >= 0 ) ? 'profit-positive' : 'profit-negative'; ?>">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_status">Ticket Status</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-yes-alt field-icon"></span>
                                <select name="status" id="inp_status" class="ifs-input-field">
                                    <option value="Issued" <?php selected( $val_status, 'Issued' ); ?>>Issued / Confirmed</option>
                                    <option value="Reissued" <?php selected( $val_status, 'Reissued' ); ?>>Date Reissued</option>
                                    <option value="Refunded" <?php selected( $val_status, 'Refunded' ); ?>>Refunded</option>
                                    <option value="Void" <?php selected( $val_status, 'Void' ); ?>>Voided (Same Day)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Settlement & Document Attachment -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">04</div>
                        <div>
                            <h3 class="ifs-card-title">Settlement &amp; E-Ticket PDF Attachment</h3>
                            <p class="ifs-card-desc">Payment status, collection channel, remarks, and digital ticket copy</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_pay_status">Payment Status</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-money-alt field-icon"></span>
                                <select name="payment_status" id="inp_pay_status" class="ifs-input-field">
                                    <option value="Paid" <?php selected( $val_pay_status, 'Paid' ); ?>>Fully Paid</option>
                                    <option value="Partial" <?php selected( $val_pay_status, 'Partial' ); ?>>Partially Paid</option>
                                    <option value="Due" <?php selected( $val_pay_status, 'Due' ); ?>>Due / Credit</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_pay_method">Payment Method</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-vault field-icon"></span>
                                <select name="payment_method" id="inp_pay_method" class="ifs-input-field">
                                    <option value="Bank Transfer" <?php selected( $val_pay_method, 'Bank Transfer' ); ?>>Bank Transfer (BEFTN/RTGS)</option>
                                    <option value="Cash" <?php selected( $val_pay_method, 'Cash' ); ?>>Cash at Counter</option>
                                    <option value="bKash / MFS" <?php selected( $val_pay_method, 'bKash / MFS' ); ?>>bKash / Nagad / Rocket</option>
                                    <option value="Cheque" <?php selected( $val_pay_method, 'Cheque' ); ?>>Cheque</option>
                                    <option value="Credit Card" <?php selected( $val_pay_method, 'Credit Card' ); ?>>Credit / Debit Card (POS)</option>
                                    <option value="Agent Deposit" <?php selected( $val_pay_method, 'Agent Deposit' ); ?>>Agent Credit Balance</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-3">
                            <label class="ifs-field-label" for="inp_ticket_copy">Airline E-Ticket PDF / Document URL</label>
                            <div class="ifs-media-uploader-box">
                                <div class="ifs-field-wrap" style="flex: 1;">
                                    <span class="dashicons dashicons-pdf field-icon"></span>
                                    <input type="text" name="ticket_copy_url" id="inp_ticket_copy" 
                                           value="<?php echo $val_tkt_copy; ?>" 
                                           placeholder="Attach airline e-ticket PDF / Image copy" class="ifs-input-field">
                                </div>
                                <button type="button" class="ifs-btn-upload" id="ifsUploadTktBtn">
                                    <span class="dashicons dashicons-upload"></span> Media Library
                                </button>
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-3">
                            <label class="ifs-field-label" for="inp_remarks">Special Service Remarks / Seat / SSR Notes</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-edit field-icon"></span>
                                <input type="text" name="remarks" id="inp_remarks" 
                                       value="<?php echo $val_remarks; ?>" 
                                       placeholder="e.g. Seat 14A, Wheelchair Requested, Non-refundable Fare" class="ifs-input-field">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Toolbar -->
                <div class="ifs-action-strip">
                    <a href="<?php echo esc_url( $base_url . '&sub=list' ); ?>" class="ifs-btn-back">
                        <span class="dashicons dashicons-arrow-left-alt"></span> Cancel
                    </a>
                    <button type="submit" name="ifs_ticket_submit" class="ifs-btn-primary">
                        <span class="dashicons dashicons-saved"></span> 
                        <?php echo $is_edit ? 'Update Ticket Record' : 'Save & Issue Ticket'; ?>
                    </button>
                </div>
            </div>

            <!-- Right Sidebar: Ultra-Premium Luxury Boarding Pass & E-Ticket Live Preview -->
            <div class="ifs-preview-sidebar">
                <div class="ifs-preview-sticky">
                    
                    <div class="ifs-card-preview-header">
                        <div class="preview-header-left">
                            <span class="pulse-beacon"></span>
                            <span>Live Flight Boarding Pass</span>
                        </div>
                        <span class="preview-secure-tag"><span class="dashicons dashicons-shield"></span> IATA ETKT</span>
                    </div>

                    <!-- Luxury Aviation Boarding Pass Card -->
                    <div class="ifs-lux-boarding-pass">
                        
                        <!-- Top Flight & Tailfin Strip -->
                        <div class="pass-top-band">
                            <div class="pass-carrier-wrap">
                                <div class="carrier-tail-icon"><span class="dashicons dashicons-airplane"></span></div>
                                <div>
                                    <span class="carrier-name" id="prev_airline">EMIRATES AIRLINE</span>
                                    <span class="flight-no-badge font-mono" id="prev_flight_no">EK-585</span>
                                </div>
                            </div>
                            <div class="cabin-class-pill" id="prev_cabin">ECONOMY</div>
                        </div>

                        <!-- Route Arc Section -->
                        <div class="pass-route-arc-box">
                            <div class="route-station origin">
                                <span class="station-code font-mono" id="prev_origin">DAC</span>
                                <span class="station-city">Origin</span>
                            </div>
                            <div class="route-arc-visual">
                                <div class="arc-line-dotted"></div>
                                <div class="plane-flight-symbol"><span class="dashicons dashicons-airplane"></span></div>
                                <span class="trip-tag-pill" id="prev_type">ONE WAY</span>
                            </div>
                            <div class="route-station dest">
                                <span class="station-code font-mono" id="prev_dest">DXB</span>
                                <span class="station-city">Destination</span>
                            </div>
                        </div>

                        <!-- Perforation Divider -->
                        <div class="pass-perforation-divider">
                            <div class="perf-hole left"></div>
                            <div class="perf-line"></div>
                            <div class="perf-hole right"></div>
                        </div>

                        <!-- Passenger Hero Row -->
                        <div class="pass-pax-hero">
                            <div class="pax-meta-cell">
                                <span class="pax-label">PASSENGER NAME</span>
                                <strong class="pax-name-val uppercase" id="prev_pax_name">SELECT TRAVELER</strong>
                            </div>
                            <div class="pax-meta-cell text-right">
                                <span class="pax-label">DEPARTURE DATE</span>
                                <strong class="pax-date-val font-mono" id="prev_date">20 AUG 2026</strong>
                            </div>
                        </div>

                        <!-- Technical Specs Matrix -->
                        <div class="pass-specs-grid font-mono">
                            <div class="spec-cell">
                                <span class="spec-label">GDS / AIRLINE PNR</span>
                                <strong class="spec-value color-cyan" id="prev_pnr">------</strong>
                            </div>
                            <div class="spec-cell">
                                <span class="spec-label">BAGGAGE</span>
                                <strong class="spec-value" id="prev_baggage">20 KG</strong>
                            </div>
                            <div class="spec-cell">
                                <span class="spec-label">E-TICKET NO</span>
                                <strong class="spec-value" id="prev_tkt">077-XXXXXXXXXX</strong>
                            </div>
                            <div class="spec-cell">
                                <span class="spec-label">TOTAL FARE</span>
                                <strong class="spec-value color-green" id="prev_sell">৳0.00</strong>
                            </div>
                        </div>

                        <!-- Barcode Section -->
                        <div class="pass-barcode-area">
                            <div class="barcode-matrix-lines"></div>
                            <span class="barcode-code-text font-mono" id="prev_barcode">M1RAHIM/MD  E7X9K2L DACDXBEK 0585 233Y</span>
                        </div>

                    </div>

                    <!-- Commercial Intelligence Box -->
                    <div class="ifs-intel-box">
                        <div class="intel-head">
                            <span class="dashicons dashicons-chart-pie"></span> Real-Time Yield & Margin Matrix
                        </div>
                        <div class="intel-body">
                            <div class="intel-row">
                                <span>Calculated Net Margin:</span>
                                <strong id="intel_profit" class="color-green">৳0.00</strong>
                            </div>
                            <div class="intel-row">
                                <span>Agency Yield Ratio:</span>
                                <strong id="intel_ratio">0.0%</strong>
                            </div>
                            <div class="intel-row sub-row">
                                <span>Net Commission Balance:</span>
                                <span id="intel_comm_net" class="font-mono">৳0.00</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </form>
    </div>

    <!-- Ultra High-End Custom Stylesheet -->
    <style>
        .ifs-ticket-workspace { max-width: 1420px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; }
        .ifs-toast { padding: 14px 20px; border-radius: 10px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 10px; margin-bottom: 22px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .ifs-toast.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .ifs-toast.danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .ifs-toast .dashicons { font-size: 18px; width: 18px; height: 18px; }

        .ifs-split-ticket-editor { display: grid; grid-template-columns: 1fr 410px; gap: 28px; align-items: flex-start; }
        @media (max-width: 1180px) { .ifs-split-ticket-editor { grid-template-columns: 1fr; } }

        .ifs-panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 26px; margin-bottom: 22px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); }
        .ifs-card-header { display: flex; align-items: center; gap: 14px; margin-bottom: 22px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9; }
        .ifs-step-num { width: 34px; height: 34px; border-radius: 9px; background: linear-gradient(135deg, #003376 0%, #0284c7 100%); color: #ffffff; font-weight: 800; font-size: 13px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0, 51, 118, 0.2); flex-shrink: 0; }
        .ifs-card-title { margin: 0; font-size: 15.5px; font-weight: 800; color: #0f172a; }
        .ifs-card-desc { margin: 2px 0 0 0; font-size: 12.5px; color: #64748b; }

        .ifs-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px 18px; }
        .col-span-2 { grid-column: span 2; }
        .col-span-3 { grid-column: span 3; }
        @media (max-width: 768px) { .ifs-grid-3 { grid-template-columns: 1fr; } .col-span-2, .col-span-3 { grid-column: span 1; } }

        .ifs-field-block { display: flex; flex-direction: column; gap: 5px; }
        .ifs-field-label { font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.4px; }
        .ifs-field-label .req { color: #e11d48; }

        .ifs-field-wrap { position: relative; display: flex; align-items: center; width: 100%; }
        .ifs-field-wrap .field-icon {
            position: absolute;
            left: 12px;
            color: #94a3b8;
            font-size: 17px;
            width: 17px;
            height: 17px;
            pointer-events: none;
            z-index: 2;
            transition: color 0.2s ease;
        }

        .ifs-field-wrap .ifs-input-field {
            width: 100%;
            padding: 9px 12px 9px 38px !important;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13.5px;
            color: #0f172a;
            background: #ffffff;
            outline: none;
            transition: all 0.2s ease;
            position: relative;
            z-index: 1;
        }
        .ifs-field-wrap select.ifs-input-field {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 14px;
            padding-right: 32px !important;
        }
        .ifs-field-wrap .ifs-input-field:focus { border-color: #003376; box-shadow: 0 0 0 3px rgba(0, 51, 118, 0.12); }
        .ifs-field-wrap:focus-within .field-icon { color: #003376; }

        .uppercase { text-transform: uppercase; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .font-bold { font-weight: 700; }
        .color-blue { color: #003376 !important; }

        .profit-positive { background: #f0fdf4 !important; color: #166534 !important; border-color: #bbf7d0 !important; }
        .profit-negative { background: #fef2f2 !important; color: #dc2626 !important; border-color: #fecaca !important; }

        .ifs-media-uploader-box { display: flex; gap: 8px; align-items: center; width: 100%; }
        .ifs-btn-upload { background: #f1f5f9; border: 1px solid #cbd5e1; height: 38px; padding: 0 16px; border-radius: 8px; font-size: 12.5px; font-weight: 600; color: #334155; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; transition: all 0.2s ease; }
        .ifs-btn-upload:hover { background: #e2e8f0; color: #0f172a; }

        .ifs-action-strip { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; }
        .ifs-btn-back { color: #64748b; text-decoration: none; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .ifs-btn-back:hover { color: #0f172a; }
        .ifs-btn-primary { background: linear-gradient(135deg, #003376 0%, #0284c7 100%); color: #ffffff !important; border: none; padding: 11px 26px; border-radius: 8px; font-size: 13.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(0, 51, 118, 0.25); transition: all 0.2s ease; }
        .ifs-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(0, 51, 118, 0.35); }

        /* ----------------------------------------------------
           ULTRA-PREMIUM BOARDING PASS PREVIEW WIDGET
        ---------------------------------------------------- */
        .ifs-preview-sticky { position: sticky; top: 30px; }
        
        .ifs-card-preview-header {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #475569;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .preview-header-left {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .pulse-beacon {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25);
            animation: pulseGlow 1.8s infinite;
        }
        @keyframes pulseGlow {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.6; }
        }
        .preview-secure-tag {
            font-size: 10px;
            font-weight: 800;
            background: #e2e8f0;
            color: #475569;
            padding: 2px 7px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .preview-secure-tag .dashicons { font-size: 12px; width: 12px; height: 12px; }

        /* Luxury Pass Card */
        .ifs-lux-boarding-pass {
            background: radial-gradient(circle at 100% 0%, #0369a1 0%, #002b66 50%, #001738 100%);
            border-radius: 18px;
            padding: 22px;
            color: #ffffff;
            box-shadow: 0 20px 40px -8px rgba(0, 51, 118, 0.45);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.12);
        }
        .ifs-lux-boarding-pass::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            pointer-events: none;
        }

        /* Top Carrier Band */
        .pass-top-band {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .pass-carrier-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
        }
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
        .carrier-name {
            display: block;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.8px;
            color: #ffffff;
            line-height: 1.2;
        }
        .flight-no-badge {
            font-size: 10px;
            color: #7dd3fc;
            font-weight: 700;
        }
        .cabin-class-pill {
            background: rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(6px);
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 9.5px;
            font-weight: 800;
            letter-spacing: 0.6px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Route Arc Section */
        .pass-route-arc-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0 16px 0;
        }
        .route-station {
            display: flex;
            flex-direction: column;
        }
        .route-station.dest { text-align: right; }
        .station-code {
            font-size: 28px;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: #ffffff;
            line-height: 1;
        }
        .station-city {
            font-size: 10px;
            color: #93c5fd;
            text-transform: uppercase;
            font-weight: 600;
            margin-top: 3px;
        }
        
        .route-arc-visual {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            width: 140px;
        }
        .arc-line-dotted {
            width: 100%;
            height: 2px;
            border-top: 2px dashed rgba(56, 189, 248, 0.5);
            position: absolute;
            top: 10px;
            z-index: 1;
        }
        .plane-flight-symbol {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #0284c7;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 2;
            box-shadow: 0 0 10px #38bdf8;
            color: #ffffff;
        }
        .plane-flight-symbol .dashicons { font-size: 12px; width: 12px; height: 12px; }
        .trip-tag-pill {
            font-size: 8.5px;
            font-weight: 800;
            background: rgba(0, 0, 0, 0.3);
            padding: 2px 7px;
            border-radius: 10px;
            margin-top: 8px;
            letter-spacing: 0.5px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Perforation Line & Holes */
        .pass-perforation-divider {
            position: relative;
            margin: 6px -22px 16px -22px;
            display: flex;
            align-items: center;
        }
        .perf-hole {
            width: 18px;
            height: 18px;
            background: #f8fafc;
            border-radius: 50%;
            position: absolute;
            z-index: 3;
        }
        .perf-hole.left { left: -9px; }
        .perf-hole.right { right: -9px; }
        .perf-line {
            width: 100%;
            height: 1px;
            border-top: 1px dashed rgba(255, 255, 255, 0.25);
        }

        /* Passenger Details */
        .pass-pax-hero {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(0, 0, 0, 0.2);
            padding: 10px 14px;
            border-radius: 10px;
            margin-bottom: 14px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .pax-meta-cell {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .pax-meta-cell.text-right { text-align: right; }
        .pax-label { font-size: 8px; font-weight: 800; color: #7dd3fc; letter-spacing: 0.6px; }
        .pax-name-val { font-size: 12.5px; font-weight: 800; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 190px; }
        .pax-date-val { font-size: 11px; font-weight: 700; color: #ffffff; }

        /* Specs Grid */
        .pass-specs-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 14px;
            padding-bottom: 14px;
            margin-bottom: 14px;
            border-bottom: 1px dashed rgba(255, 255, 255, 0.2);
        }
        .spec-cell {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .spec-label { font-size: 8px; font-weight: 800; color: #7dd3fc; letter-spacing: 0.6px; }
        .spec-value { font-size: 11px; font-weight: 700; color: #ffffff; }
        .color-cyan { color: #38bdf8 !important; }
        .color-green { color: #86efac !important; }

        /* Barcode Area */
        .pass-barcode-area { text-align: center; }
        .barcode-matrix-lines {
            height: 22px;
            background: repeating-linear-gradient(90deg, #ffffff, #ffffff 2px, transparent 2px, transparent 4px, #ffffff 4px, #ffffff 5px, transparent 5px, transparent 8px, #ffffff 8px, #ffffff 10px, transparent 10px, transparent 12px);
            opacity: 0.85;
            margin-bottom: 5px;
            border-radius: 2px;
        }
        .barcode-code-text { font-size: 8px; color: #93c5fd; letter-spacing: 1.5px; }

        /* Commercial Intelligence Box */
        .ifs-intel-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 18px;
            margin-top: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
        }
        .intel-head {
            font-size: 12px;
            font-weight: 800;
            color: #003376;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f1f5f9;
        }
        .intel-head .dashicons { color: #0284c7; font-size: 16px; width: 16px; height: 16px; }
        .intel-body { display: flex; flex-direction: column; gap: 8px; }
        .intel-row { display: flex; justify-content: space-between; font-size: 12.5px; color: #475569; }
        .intel-row strong { font-weight: 800; font-size: 13.5px; }
        .intel-row.sub-row { font-size: 11.5px; color: #64748b; border-top: 1px dashed #f1f5f9; padding-top: 6px; }
    </style>

    <!-- Real-Time Interactive Engine -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const inpCustomer   = document.getElementById('inp_customer');
        const hiddenPaxName = document.getElementById('inp_passenger_name_hidden');
        const hiddenPassNo  = document.getElementById('inp_passport_no_hidden');

        const inpPnr        = document.getElementById('inp_pnr');
        const inpAirPnr     = document.getElementById('inp_airline_pnr');
        const inpTicket     = document.getElementById('inp_ticket');
        const inpAirline    = document.getElementById('inp_airline');
        const inpFlightNo   = document.getElementById('inp_flight_no');
        const inpSector     = document.getElementById('inp_sector');
        const inpCabin      = document.getElementById('inp_cabin');
        const inpBaggage    = document.getElementById('inp_baggage');
        const inpFlightType = document.getElementById('inp_flight_type');
        const inpTravelDate = document.getElementById('inp_travel_date');
        
        const inpBaseFare   = document.getElementById('inp_base_fare');
        const inpTaxAmount  = document.getElementById('inp_tax_amount');
        const inpComm       = document.getElementById('inp_comm');
        const inpAit        = document.getElementById('inp_ait');
        const inpDiscount   = document.getElementById('inp_discount');
        const inpBuy        = document.getElementById('ifs_buy_price');
        const inpSell       = document.getElementById('ifs_sell_price');
        
        const wrapReturnDate = document.getElementById('wrap_return_date');
        const wrapReturnTime = document.getElementById('wrap_return_time');

        const prevAirline   = document.getElementById('prev_airline');
        const prevCabin     = document.getElementById('prev_cabin');
        const prevOrigin    = document.getElementById('prev_origin');
        const prevDest      = document.getElementById('prev_dest');
        const prevFlightNo  = document.getElementById('prev_flight_no');
        const prevType      = document.getElementById('prev_type');
        const prevPaxName   = document.getElementById('prev_pax_name');
        const prevDate      = document.getElementById('prev_date');
        const prevPnr       = document.getElementById('prev_pnr');
        const prevBaggage   = document.getElementById('prev_baggage');
        const prevTkt       = document.getElementById('prev_tkt');
        const prevSell      = document.getElementById('prev_sell');
        const prevBarcode   = document.getElementById('prev_barcode');
        
        const profitDisplay = document.getElementById('ifs_profit_display');
        const intelProfit   = document.getElementById('intel_profit');
        const intelRatio    = document.getElementById('intel_ratio');
        const intelCommNet  = document.getElementById('intel_comm_net');

        function updateTicketPass() {
            if (prevAirline) prevAirline.textContent = (inpAirline && inpAirline.value.trim()) ? inpAirline.value.trim().toUpperCase() : 'AIRLINE CARRIER';
            if (prevCabin) prevCabin.textContent = (inpCabin) ? inpCabin.value.toUpperCase() : 'ECONOMY';
            if (prevFlightNo) prevFlightNo.textContent = (inpFlightNo && inpFlightNo.value.trim()) ? inpFlightNo.value.trim().toUpperCase() : 'FLIGHT';
            if (prevType) prevType.textContent = (inpFlightType) ? inpFlightType.value.toUpperCase() : 'ONE WAY';
            if (prevBaggage) prevBaggage.textContent = (inpBaggage && inpBaggage.value.trim()) ? inpBaggage.value.trim().toUpperCase() : '20 KG';

            // Sector Parser
            let orig = 'DAC';
            let dest = 'DXB';
            if (inpSector && inpSector.value.trim()) {
                const secParts = inpSector.value.trim().toUpperCase().split('-');
                if (secParts.length >= 2) {
                    orig = secParts[0];
                    dest = secParts[secParts.length - 1];
                } else {
                    orig = inpSector.value.trim().slice(0, 3).toUpperCase();
                    dest = '---';
                }
            }
            if (prevOrigin) prevOrigin.textContent = orig;
            if (prevDest) prevDest.textContent = dest;

            // Passenger Name & Snapshot Auto-fill
            let paxStr = 'SELECT TRAVELER';
            if (inpCustomer && inpCustomer.selectedIndex > 0) {
                const selectedOpt = inpCustomer.options[inpCustomer.selectedIndex];
                const paxName     = selectedOpt.getAttribute('data-name');
                const passNo      = selectedOpt.getAttribute('data-passport');
                paxStr = paxName || 'PASSENGER NAME';
                if (hiddenPaxName) hiddenPaxName.value   = paxName || '';
                if (hiddenPassNo) hiddenPassNo.value     = passNo || '';
            }
            if (prevPaxName) prevPaxName.textContent = paxStr;

            // Travel Date
            if (inpTravelDate && inpTravelDate.value) {
                const d = new Date(inpTravelDate.value);
                const options = { day: '2-digit', month: 'short', year: 'numeric' };
                if (prevDate) prevDate.textContent = d.toLocaleDateString('en-GB', options).toUpperCase();
            }

            const pnrVal    = (inpPnr && inpPnr.value.trim()) ? inpPnr.value.trim().toUpperCase() : '------';
            const airPnrVal = (inpAirPnr && inpAirPnr.value.trim()) ? ' / ' + inpAirPnr.value.trim().toUpperCase() : '';
            const tktVal    = (inpTicket && inpTicket.value.trim()) ? inpTicket.value.trim() : '077-XXXXXXXXXX';

            if (prevPnr) prevPnr.textContent = pnrVal + airPnrVal;
            if (prevTkt) prevTkt.textContent = tktVal;

            // Auto-Calculate Buy Price from Base + Tax if empty
            const baseVal = parseFloat(inpBaseFare ? inpBaseFare.value : 0) || 0;
            const taxVal  = parseFloat(inpTaxAmount ? inpTaxAmount.value : 0) || 0;
            if (baseVal > 0 && taxVal > 0 && (!inpBuy.value || inpBuy.value == '0' || inpBuy.value == '0.00')) {
                inpBuy.value = (baseVal + taxVal).toFixed(2);
            }

            // Auto-calculate 0.3% AIT on base fare if AIT is empty
            if (baseVal > 0 && (!inpAit.value || inpAit.value == '0' || inpAit.value == '0.00')) {
                inpAit.value = (baseVal * 0.003).toFixed(2);
            }

            // Financial Calculations
            const buyVal   = parseFloat(inpBuy ? inpBuy.value : 0) || 0;
            const sellVal  = parseFloat(inpSell ? inpSell.value : 0) || 0;
            const commVal  = parseFloat(inpComm ? inpComm.value : 0) || 0;
            const aitVal   = parseFloat(inpAit ? inpAit.value : 0) || 0;
            const discVal  = parseFloat(inpDiscount ? inpDiscount.value : 0) || 0;

            const profit   = (sellVal - buyVal) + commVal - discVal - aitVal;
            const ratio    = sellVal > 0 ? ((profit / sellVal) * 100).toFixed(1) : '0.0';

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

            // Barcode String Generator
            const cleanPax = paxStr.replace(/[^A-Za-z]/g, '').slice(0, 10).toUpperCase();
            if (prevBarcode) {
                prevBarcode.textContent = 'M1' + cleanPax + ' E' + pnrVal + ' ' + orig + dest + ' ' + (inpFlightNo ? inpFlightNo.value.trim().toUpperCase() : 'FLT') + ' 233Y';
            }

            if (inpFlightType) {
                const isRT = (inpFlightType.value === 'Round Trip');
                if (wrapReturnDate) wrapReturnDate.style.display = isRT ? 'flex' : 'none';
                if (wrapReturnTime) wrapReturnTime.style.display = isRT ? 'flex' : 'none';
            }
        }

        const watchFields = [inpCustomer, inpPnr, inpAirPnr, inpTicket, inpAirline, inpFlightNo, inpSector, inpTransit, inpCabin, inpFareBasis, inpBaggage, inpFlightType, inpTravelDate, inpFlightTime, inpBaseFare, inpTaxAmount, inpComm, inpAit, inpDiscount, inpBuy, inpSell];
        watchFields.forEach(el => {
            if (el) {
                el.addEventListener('input', updateTicketPass);
                el.addEventListener('change', updateTicketPass);
            }
        });

        updateTicketPass();

        // WP Media Uploader for Ticket Copy
        const uploadBtn = document.getElementById('ifsUploadTktBtn');
        const copyInput = document.getElementById('inp_ticket_copy');
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
                    }
                }).open();
            });
        }
    });
    </script>
    <?php
}