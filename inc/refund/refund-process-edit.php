<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_refund_process_edit_page' ) ) {
    /**
     * Process / Edit Refund, Reissue & Void Console (Flat Minimal UI - No Shadows & Equal Field Height)
     * Features: Resilient Ticket Query, Full Select2 Search Engine, Two-Way Financial Matrix,
     * Automatic Parent Ticket Status Synchronization & Live JavaScript Calculations.
     */
    function ifs_terp_refund_process_edit_page() {
        global $wpdb;
        $table_refunds   = $wpdb->prefix . 'iterp_refund_reissue';
        $table_tickets   = $wpdb->prefix . 'iterp_tickets';
        $table_customers = $wpdb->prefix . 'iterp_customers';
        $table_agents    = $wpdb->prefix . 'iterp_agents';
        $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
        $base_url        = admin_url( 'admin.php?page=ifs_travel_erp&tab=refund_reissue' );

        // Enqueue Select2 Assets with CDN Fallbacks
        if ( ! wp_script_is( 'select2', 'registered' ) ) {
            wp_register_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), '4.1.0-rc.0', true );
            wp_register_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0-rc.0' );
        }
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'select2' );
        wp_enqueue_style( 'select2' );

        $edit_id  = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
        $is_edit  = ( $edit_id > 0 );
        $edit_row = false;
        $message  = '';
        $errors   = array();

        /* =========================================================================
           1. PROCESS FORM SUBMISSION (ADD / EDIT)
           ========================================================================= */
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_refund_submit'] ) ) {
            check_admin_referer( 'ifs_refund_action', 'ifs_refund_nonce' );

            $ticket_id         = isset( $_POST['ticket_id'] ) ? absint( wp_unslash( $_POST['ticket_id'] ) ) : 0;
            $customer_id       = isset( $_POST['customer_id'] ) ? absint( wp_unslash( $_POST['customer_id'] ) ) : 0;
            $agent_id          = isset( $_POST['agent_id'] ) ? absint( wp_unslash( $_POST['agent_id'] ) ) : 0;
            $supplier_id       = isset( $_POST['supplier_id'] ) ? absint( wp_unslash( $_POST['supplier_id'] ) ) : 0;
            $process_type      = isset( $_POST['process_type'] ) ? sanitize_text_field( wp_unslash( $_POST['process_type'] ) ) : 'Refund';
            $pnr               = isset( $_POST['pnr'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['pnr'] ) ) ) : '';
            $ticket_no         = isset( $_POST['ticket_no'] ) ? sanitize_text_field( wp_unslash( $_POST['ticket_no'] ) ) : '';
            
            $new_pnr           = isset( $_POST['new_pnr'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['new_pnr'] ) ) ) : '';
            $new_ticket_no     = isset( $_POST['new_ticket_no'] ) ? sanitize_text_field( wp_unslash( $_POST['new_ticket_no'] ) ) : '';
            
            // New Enterprise Compliance Fields
            $bsp_ra_no         = isset( $_POST['bsp_ra_no'] ) ? sanitize_text_field( wp_unslash( $_POST['bsp_ra_no'] ) ) : '';
            $airline_waiver_no = isset( $_POST['airline_waiver_no'] ) ? sanitize_text_field( wp_unslash( $_POST['airline_waiver_no'] ) ) : '';
            $payout_account    = isset( $_POST['payout_account'] ) ? sanitize_text_field( wp_unslash( $_POST['payout_account'] ) ) : '';
            
            $original_fare     = isset( $_POST['original_fare'] ) ? (float) wp_unslash( $_POST['original_fare'] ) : 0;
            $airline_penalty   = isset( $_POST['airline_penalty'] ) ? (float) wp_unslash( $_POST['airline_penalty'] ) : 0;
            $service_charge    = isset( $_POST['service_charge'] ) ? (float) wp_unslash( $_POST['service_charge'] ) : 0;
            $fare_difference   = isset( $_POST['fare_difference'] ) ? (float) wp_unslash( $_POST['fare_difference'] ) : 0;
            $refund_amount     = isset( $_POST['refund_amount'] ) ? (float) wp_unslash( $_POST['refund_amount'] ) : 0;

            $settlement_method = isset( $_POST['settlement_method'] ) ? sanitize_text_field( wp_unslash( $_POST['settlement_method'] ) ) : 'Bank Transfer';
            $status            = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'Completed';
            $remarks           = isset( $_POST['remarks'] ) ? sanitize_textarea_field( wp_unslash( $_POST['remarks'] ) ) : '';

            if ( empty( $pnr ) ) {
                $errors[] = esc_html__( 'PNR Code is required.', 'ifs-travel-erp' );
            }

            if ( empty( $errors ) ) {
                $data = array(
                    'ticket_id'             => $ticket_id,
                    'customer_id'           => $customer_id,
                    'agent_id'              => $agent_id,
                    'supplier_id'           => $supplier_id,
                    'type'                  => $process_type,
                    'pnr'                   => $pnr,
                    'ticket_no'             => $ticket_no,
                    'new_pnr'               => $new_pnr,
                    'new_ticket_no'         => $new_ticket_no,
                    'bsp_ra_no'             => $bsp_ra_no,
                    'airline_waiver_no'     => $airline_waiver_no,
                    'payout_account'        => $payout_account,
                    'original_fare'         => $original_fare,
                    'airline_penalty'       => $airline_penalty,
                    'agency_service_charge' => $service_charge,
                    'fare_difference'       => $fare_difference,
                    'refund_amount'         => $refund_amount,
                    'settlement_method'     => $settlement_method,
                    'status'                => $status,
                    'remarks'               => $remarks,
                );

                if ( $is_edit ) {
                    $wpdb->update( $table_refunds, $data, array( 'id' => $edit_id ) );
                    $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Operation record updated successfully.', 'ifs-travel-erp' ) . '</div>';
                } else {
                    $data['created_at'] = current_time( 'mysql' );
                    $data['created_by'] = get_current_user_id();
                    $wpdb->insert( $table_refunds, $data );
                    $edit_id = $wpdb->insert_id;
                    $is_edit = true;

                    // Sync Ticket Status
                    if ( $ticket_id > 0 && 'Completed' === $status ) {
                        $ticket_new_status = ( 'Refund' === $process_type ) ? 'Refunded' : ( ( 'Void' === $process_type ) ? 'Void' : 'Reissued' );
                        $wpdb->update( $table_tickets, array( 'status' => $ticket_new_status ), array( 'id' => $ticket_id ) );
                    }

                    if ( function_exists( 'ifs_terp_log_activity' ) ) {
                        ifs_terp_log_activity( "Recorded {$process_type} for PNR: {$pnr} | Ticket: {$ticket_no} (Record #{$edit_id})" );
                    }

                    $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . sprintf( esc_html__( '%s operation saved successfully (#OP-%s).', 'ifs-travel-erp' ), esc_html( $process_type ), esc_html( str_pad( (string) $edit_id, 5, '0', STR_PAD_LEFT ) ) ) . '</div>';
                }
            } else {
                $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> ' . implode( '<br>', $errors ) . '</div>';
            }
        }

        if ( $is_edit ) {
            $edit_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_refunds} WHERE id = %d", $edit_id ) );
        }

        // Entity Lookups
        $customers = $wpdb->get_results( "SELECT id, full_name, mobile, passport_no FROM {$table_customers} ORDER BY full_name ASC" );
        $agents    = $wpdb->get_results( "SELECT id, agency_name, current_balance FROM {$table_agents} WHERE status = 'Active' ORDER BY agency_name ASC" );
        $suppliers = $wpdb->get_results( "SELECT id, supplier_name, current_balance FROM {$table_suppliers} WHERE status = 'Active' ORDER BY supplier_name ASC" );

        $current_ticket_id = ( $edit_row && ! empty( $edit_row->ticket_id ) ) ? absint( $edit_row->ticket_id ) : 0;
        $active_tickets    = $wpdb->get_results( $wpdb->prepare( "
            SELECT t.id, t.customer_id, t.agent_id, t.supplier_id, t.pnr, t.ticket_no, t.airline, t.sector, t.sell_price, t.status, c.full_name 
            FROM {$table_tickets} t
            LEFT JOIN {$table_customers} c ON t.customer_id = c.id
            WHERE t.status NOT IN ('Cancelled', 'Deleted', 'Void') 
               OR t.id = %d
            ORDER BY t.id DESC LIMIT 250
        ", $current_ticket_id ) );

        $val_type       = $edit_row ? esc_attr( $edit_row->type ) : 'Refund';
        $val_tkt_id     = $edit_row ? absint( $edit_row->ticket_id ) : 0;
        $val_pnr        = $edit_row ? esc_attr( $edit_row->pnr ) : '';
        $val_tkt_no     = $edit_row ? esc_attr( $edit_row->ticket_no ) : '';
        $val_new_pnr    = $edit_row ? esc_attr( $edit_row->new_pnr ) : '';
        $val_new_tkt    = $edit_row ? esc_attr( $edit_row->new_ticket_no ) : '';
        
        $val_ra         = $edit_row && isset( $edit_row->bsp_ra_no ) ? esc_attr( $edit_row->bsp_ra_no ) : '';
        $val_waiver     = $edit_row && isset( $edit_row->airline_waiver_no ) ? esc_attr( $edit_row->airline_waiver_no ) : '';
        $val_payout_acc = $edit_row && isset( $edit_row->payout_account ) ? esc_attr( $edit_row->payout_account ) : '';

        $val_fare       = $edit_row ? (float) $edit_row->original_fare : '';
        $val_penalty    = $edit_row ? (float) $edit_row->airline_penalty : '';
        $val_fee        = $edit_row ? (float) $edit_row->agency_service_charge : '';
        $val_diff       = $edit_row ? (float) $edit_row->fare_difference : '';
        $val_refund     = $edit_row ? (float) $edit_row->refund_amount : '';
        $val_method     = $edit_row ? esc_attr( $edit_row->settlement_method ) : 'Bank Transfer';
        $val_status     = $edit_row ? esc_attr( $edit_row->status ?? 'Completed' ) : 'Completed';
        $val_remarks    = $edit_row ? esc_textarea( $edit_row->remarks ) : '';

        $val_customer   = $edit_row ? absint( $edit_row->customer_id ) : 0;
        $val_agent      = $edit_row ? absint( $edit_row->agent_id ) : 0;
        $val_supplier   = $edit_row ? absint( $edit_row->supplier_id ) : 0;
        ?>

        <div class="wrap ifs-refund-workspace">
            <?php echo wp_kses_post( $message ); ?>

            <form method="post" action="">
                <?php wp_nonce_field( 'ifs_refund_action', 'ifs_refund_nonce' ); ?>
                <?php if ( $edit_id > 0 ) : ?>
                    <input type="hidden" name="edit_id" value="<?php echo esc_attr( $edit_id ); ?>">
                <?php endif; ?>

                <!-- Section 1: Ticket & Operating Context -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">01</div>
                        <div>
                            <h3 class="ifs-card-title"><?php echo $is_edit ? esc_html__( 'Edit After-Sales Record', 'ifs-travel-erp' ) : esc_html__( 'Ticket Lookup & Operation Details', 'ifs-travel-erp' ); ?></h3>
                            <p class="ifs-card-desc"><?php esc_html_e( 'Select an issued ticket to auto-fill booking details or map entities manually', 'ifs-travel-erp' ); ?></p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <div class="ifs-field-block col-span-3">
                            <label class="ifs-field-label" for="inp_ticket_id"><?php esc_html_e( 'Select Issued Ticket (Auto-Fills Details)', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <select name="ticket_id" id="inp_ticket_id" class="ifs-input-field ifs-select2-ticket">
                                    <option value="0"><?php esc_html_e( '-- Search & Select Issued Ticket (PNR, Passenger, Ticket No) --', 'ifs-travel-erp' ); ?></option>
                                    <?php if ( ! empty( $active_tickets ) ) : ?>
                                        <?php foreach ( $active_tickets as $t ) : 
                                            $passenger = ! empty( $t->full_name ) ? $t->full_name : __( 'Direct Client', 'ifs-travel-erp' );
                                            $airline   = ! empty( $t->airline ) ? $t->airline : 'Airline';
                                            $sector    = ! empty( $t->sector ) ? $t->sector : 'Route';
                                            $price     = number_format( (float) $t->sell_price, 2 );
                                        ?>
                                            <option value="<?php echo esc_attr( $t->id ); ?>" 
                                                    data-pnr="<?php echo esc_attr( $t->pnr ); ?>"
                                                    data-tkt="<?php echo esc_attr( $t->ticket_no ); ?>"
                                                    data-fare="<?php echo esc_attr( $t->sell_price ); ?>"
                                                    data-customer="<?php echo esc_attr( $t->customer_id ); ?>"
                                                    data-agent="<?php echo esc_attr( $t->agent_id ); ?>"
                                                    data-supplier="<?php echo esc_attr( $t->supplier_id ); ?>"
                                                    <?php selected( $val_tkt_id, $t->id ); ?>>
                                                <?php echo esc_html( "PNR: {$t->pnr} | TKT: {$t->ticket_no} | {$passenger} ({$airline}: {$sector}) - ৳{$price}" ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_process_type"><?php esc_html_e( 'Operation Action', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <select name="process_type" id="inp_process_type" class="ifs-input-field font-bold">
                                    <option value="Refund" <?php selected( $val_type, 'Refund' ); ?>><?php esc_html_e( 'Full / Partial Refund', 'ifs-travel-erp' ); ?></option>
                                    <option value="Reissue" <?php selected( $val_type, 'Reissue' ); ?>><?php esc_html_e( 'Date Change / Ticket Reissue', 'ifs-travel-erp' ); ?></option>
                                    <option value="Void" <?php selected( $val_type, 'Void' ); ?>><?php esc_html_e( 'Same-Day Void Cancellation', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_pnr"><?php esc_html_e( 'Original PNR Code', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <input type="text" name="pnr" id="inp_pnr" required value="<?php echo esc_attr( $val_pnr ); ?>" placeholder="e.g. 7X9K21" class="ifs-input-field uppercase font-mono font-bold">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_ticket_no"><?php esc_html_e( 'Original E-Ticket No', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <input type="text" name="ticket_no" id="inp_ticket_no" value="<?php echo esc_attr( $val_tkt_no ); ?>" placeholder="077-1234567890" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_bsp_ra"><?php esc_html_e( 'BSP Refund Application No (RA)', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <input type="text" name="bsp_ra_no" id="inp_bsp_ra" value="<?php echo esc_attr( $val_ra ); ?>" placeholder="e.g. RA-948201" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_waiver"><?php esc_html_e( 'Airline Waiver / Authority Ref', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <input type="text" name="airline_waiver_no" id="inp_waiver" value="<?php echo esc_attr( $val_waiver ); ?>" placeholder="e.g. SKED-CHG-99" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_payout_acc"><?php esc_html_e( 'Client Payout / Bank Account', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <input type="text" name="payout_account" id="inp_payout_acc" value="<?php echo esc_attr( $val_payout_acc ); ?>" placeholder="e.g. EBL A/C 102938 or bKash" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_customer_id"><?php esc_html_e( 'Primary Passenger / Client', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <select name="customer_id" id="inp_customer_id" class="ifs-input-field ifs-select2">
                                    <option value="0"><?php esc_html_e( '-- Direct Walk-In Client --', 'ifs-travel-erp' ); ?></option>
                                    <?php if ( ! empty( $customers ) ) : foreach ( $customers as $cus ) : ?>
                                        <option value="<?php echo esc_attr( $cus->id ); ?>" <?php selected( $val_customer, $cus->id ); ?>>
                                            <?php echo esc_html( $cus->full_name . ' (' . $cus->mobile . ')' . ( ! empty( $cus->passport_no ) ? ' [PPT: ' . $cus->passport_no . ']' : '' ) ); ?>
                                        </option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_agent_id"><?php esc_html_e( 'B2B Sub-Agent', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <select name="agent_id" id="inp_agent_id" class="ifs-input-field ifs-select2">
                                    <option value="0"><?php esc_html_e( 'Direct Retail Customer', 'ifs-travel-erp' ); ?></option>
                                    <?php if ( ! empty( $agents ) ) : foreach ( $agents as $ag ) : ?>
                                        <option value="<?php echo esc_attr( $ag->id ); ?>" <?php selected( $val_agent, $ag->id ); ?>>
                                            <?php echo esc_html( $ag->agency_name . ' (Bal: ৳' . number_format( (float) $ag->current_balance, 0 ) . ')' ); ?>
                                        </option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_supplier_id"><?php esc_html_e( 'Ticketing Supplier / GDS', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <select name="supplier_id" id="inp_supplier_id" class="ifs-input-field ifs-select2">
                                    <option value="0"><?php esc_html_e( 'Direct Airline / IATA Stock', 'ifs-travel-erp' ); ?></option>
                                    <?php if ( ! empty( $suppliers ) ) : foreach ( $suppliers as $sup ) : ?>
                                        <option value="<?php echo esc_attr( $sup->id ); ?>" <?php selected( $val_supplier, $sup->id ); ?>>
                                            <?php echo esc_html( $sup->supplier_name . ' (Bal: ৳' . number_format( (float) $sup->current_balance, 0 ) . ')' ); ?>
                                        </option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block" id="wrap_new_pnr" style="<?php echo ( 'Reissue' !== $val_type ) ? 'display:none;' : ''; ?>">
                            <label class="ifs-field-label" for="inp_new_pnr"><?php esc_html_e( 'New PNR (Reissue Only)', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <input type="text" name="new_pnr" id="inp_new_pnr" value="<?php echo esc_attr( $val_new_pnr ); ?>" placeholder="Leave blank if unchanged" class="ifs-input-field uppercase font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-2" id="wrap_new_ticket" style="<?php echo ( 'Reissue' !== $val_type ) ? 'display:none;' : ''; ?>">
                            <label class="ifs-field-label" for="inp_new_tkt"><?php esc_html_e( 'New Stamped Ticket No (Reissue Only)', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <input type="text" name="new_ticket_no" id="inp_new_tkt" value="<?php echo esc_attr( $val_new_tkt ); ?>" placeholder="e.g. 077-9876543210" class="ifs-input-field font-mono">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Financial Matrix & Settlement -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">02</div>
                        <div>
                            <h3 class="ifs-card-title"><?php esc_html_e( 'Financial Breakdown & Settlement Ledger', 'ifs-travel-erp' ); ?></h3>
                            <p class="ifs-card-desc"><?php esc_html_e( 'Calculate penalties, fare differences, and live net client payout / collection', 'ifs-travel-erp' ); ?></p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_original_fare"><?php esc_html_e( 'Original Invoiced Fare (৳)', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <input type="number" step="0.01" name="original_fare" id="inp_original_fare" required value="<?php echo esc_attr( $val_fare ); ?>" placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_penalty"><?php esc_html_e( 'Airline Penalty (৳)', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <input type="number" step="0.01" name="airline_penalty" id="inp_penalty" value="<?php echo esc_attr( $val_penalty ); ?>" placeholder="0.00" class="ifs-input-field font-mono color-rose font-bold">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_service_fee"><?php esc_html_e( 'Agency Service Fee (৳)', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <input type="number" step="0.01" name="service_charge" id="inp_service_fee" value="<?php echo esc_attr( $val_fee ); ?>" placeholder="0.00" class="ifs-input-field font-mono color-emerald font-bold">
                            </div>
                        </div>

                        <div class="ifs-field-block" id="wrap_fare_diff" style="<?php echo ( 'Reissue' !== $val_type ) ? 'display:none;' : ''; ?>">
                            <label class="ifs-field-label" for="inp_fare_diff"><?php esc_html_e( 'Airfare Difference (৳)', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <input type="number" step="0.01" name="fare_difference" id="inp_fare_diff" value="<?php echo esc_attr( $val_diff ); ?>" placeholder="0.00" class="ifs-input-field font-mono font-bold color-blue">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_settle_method"><?php esc_html_e( 'Settlement Payment Channel', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <select name="settlement_method" id="inp_settle_method" class="ifs-input-field">
                                    <option value="Bank Transfer" <?php selected( $val_method, 'Bank Transfer' ); ?>><?php esc_html_e( 'Bank Transfer (BEFTN/RTGS)', 'ifs-travel-erp' ); ?></option>
                                    <option value="Cash" <?php selected( $val_method, 'Cash' ); ?>><?php esc_html_e( 'Cash at Counter', 'ifs-travel-erp' ); ?></option>
                                    <option value="bKash / MFS" <?php selected( $val_method, 'bKash / MFS' ); ?>><?php esc_html_e( 'bKash / Nagad / Rocket', 'ifs-travel-erp' ); ?></option>
                                    <option value="Agent Credit Balance" <?php selected( $val_method, 'Agent Credit Balance' ); ?>><?php esc_html_e( 'Agent Credit Balance Ledger', 'ifs-travel-erp' ); ?></option>
                                    <option value="Cheque" <?php selected( $val_method, 'Cheque' ); ?>><?php esc_html_e( 'Cheque Clearing', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_status"><?php esc_html_e( 'Settlement Status', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <select name="status" id="inp_status" class="ifs-input-field">
                                    <option value="Completed" <?php selected( $val_status, 'Completed' ); ?>><?php esc_html_e( 'Completed & Reconciled', 'ifs-travel-erp' ); ?></option>
                                    <option value="Pending" <?php selected( $val_status, 'Pending' ); ?>><?php esc_html_e( 'Pending Approval / In-Review', 'ifs-travel-erp' ); ?></option>
                                    <option value="Rejected" <?php selected( $val_status, 'Rejected' ); ?>><?php esc_html_e( 'Rejected / Declined', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-3">
                            <label class="ifs-field-label" id="lbl_refund_amount"><?php echo ( 'Reissue' === $val_type ) ? esc_html__( 'Total Reissue Charge to Collect (৳) *', 'ifs-travel-erp' ) : esc_html__( 'Net Refund Amount Payable to Client (৳) *', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <input type="number" step="0.01" name="refund_amount" id="inp_refund_amount" required value="<?php echo esc_attr( $val_refund ); ?>" placeholder="0.00" class="ifs-input-field font-mono font-bold color-emerald" style="font-size: 15px;">
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-3">
                            <label class="ifs-field-label" for="inp_remarks"><?php esc_html_e( 'Operational Reason & Airline Notes', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-textarea-wrap">
                                <textarea name="remarks" id="inp_remarks" rows="2" class="ifs-input-field" placeholder="e.g. Passenger medical cancellation, GDS flight waiver code, schedule change notice..."><?php echo esc_textarea( $val_remarks ); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Toolbar -->
                <div class="ifs-action-strip">
                    <a href="<?php echo esc_url( add_query_arg( 'sub', 'list', $base_url ) ); ?>" class="ifs-btn-secondary">
                        <span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'Back to Log', 'ifs-travel-erp' ); ?>
                    </a>
                    <button type="submit" name="ifs_refund_submit" class="ifs-btn-primary">
                        <span class="dashicons dashicons-saved"></span> <?php echo $is_edit ? esc_html__( 'Update Operation', 'ifs-travel-erp' ) : esc_html__( 'Process & Update Accounts', 'ifs-travel-erp' ); ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Flat Minimal Stylesheet (Zero Shadow & Strict 42px Equal Field Height) -->
        <style>
            .ifs-refund-workspace { 
                max-width: 1100px; 
                margin: 20px auto; 
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; 
                color: #0f172a; 
                box-sizing: border-box;
            }
            .ifs-refund-workspace *, 
            .ifs-refund-workspace *::before, 
            .ifs-refund-workspace *::after { 
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

            .ifs-panel-card { 
                background: #ffffff; 
                border: 1px solid #e2e8f0; 
                border-radius: 14px; 
                padding: 26px; 
                margin-bottom: 22px; 
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
            @media (max-width: 820px) { 
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
                text-transform: uppercase; 
                letter-spacing: 0.5px; 
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
            .ifs-textarea-wrap {
                width: 100%;
            }

            .ifs-field-wrap .ifs-input-field {
                width: 100% !important; 
                height: 42px !important; 
                max-height: 42px !important;
                min-height: 42px !important;
                line-height: 40px !important;
                padding: 0 14px !important; 
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

            .ifs-textarea-wrap textarea.ifs-input-field {
                width: 100% !important;
                height: auto !important;
                min-height: 70px !important;
                padding: 10px 14px !important;
                line-height: 1.5 !important;
                border: 1px solid #cbd5e1 !important;
                border-radius: 8px !important;
                font-size: 13.5px !important;
                color: #0f172a !important;
                background-color: #ffffff !important;
                outline: none !important;
                transition: border-color 0.2s ease, background-color 0.2s ease;
                font-family: inherit;
                resize: vertical;
                display: block;
            }

            .ifs-field-wrap .ifs-input-field:focus,
            .ifs-textarea-wrap textarea.ifs-input-field:focus { 
                border-color: #003376 !important; 
                background-color: #f8fafc !important;
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
            .color-rose { color: #dc2626 !important; }
            .color-emerald { color: #059669 !important; }
            .color-blue { color: #003376 !important; }

            .ifs-action-strip { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; }
            .ifs-btn-secondary { background: #f8fafc; color: #475569 !important; border: 1px solid #cbd5e1; height: 42px; padding: 0 20px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; transition: background-color 0.2s ease, color 0.2s ease; }
            .ifs-btn-secondary:hover { background: #f1f5f9; color: #0f172a; }
            .ifs-btn-primary { background: #003376; color: #ffffff !important; border: none; height: 42px; padding: 0 24px; border-radius: 8px; font-size: 13.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: background-color 0.2s ease; }
            .ifs-btn-primary:hover { background: #0284c7; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            if ($.fn.select2) {
                $('.ifs-select2-ticket').select2({
                    width: '100%',
                    placeholder: '<?php echo esc_js( __( '-- Search & Select Issued Ticket (PNR, Passenger, Ticket No) --', 'ifs-travel-erp' ) ); ?>',
                    allowClear: true
                });

                $('.ifs-select2').select2({
                    width: '100%',
                    allowClear: false
                });
            }

            const selTicket   = $('#inp_ticket_id');
            const selType     = $('#inp_process_type');
            const inpPnr      = $('#inp_pnr');
            const inpTicketNo = $('#inp_ticket_no');
            const selCustomer = $('#inp_customer_id');
            const selAgent    = $('#inp_agent_id');
            const selSupplier = $('#inp_supplier_id');
            
            const inpFare     = $('#inp_original_fare');
            const inpPenalty  = $('#inp_penalty');
            const inpFee      = $('#inp_service_fee');
            const inpFareDiff = $('#inp_fare_diff');
            const inpRefund   = $('#inp_refund_amount');

            const wrapNewPnr  = $('#wrap_new_pnr');
            const wrapNewTkt  = $('#wrap_new_ticket');
            const wrapFareDiff = $('#wrap_fare_diff');
            const lblRefund   = $('#lbl_refund_amount');

            selTicket.on('change', function() {
                const opt = $(this).find(':selected');
                if (opt.val() && opt.val() !== '0') {
                    inpPnr.val(opt.attr('data-pnr') || '');
                    inpTicketNo.val(opt.attr('data-tkt') || '');
                    inpFare.val(opt.attr('data-fare') || '');

                    const cusId = opt.attr('data-customer') || '0';
                    const agId  = opt.attr('data-agent') || '0';
                    const supId = opt.attr('data-supplier') || '0';

                    selCustomer.val(cusId).trigger('change.select2');
                    selAgent.val(agId).trigger('change.select2');
                    selSupplier.val(supId).trigger('change.select2');

                    calculateSettlement();
                }
            });

            function calculateSettlement() {
                const type    = selType.val() || 'Refund';
                const fare    = parseFloat(inpFare.val()) || 0;
                const penalty = parseFloat(inpPenalty.val()) || 0;
                const fee     = parseFloat(inpFee.val()) || 0;
                const diff    = parseFloat(inpFareDiff.val()) || 0;

                if (type === 'Refund') {
                    wrapFareDiff.hide();
                    wrapNewPnr.hide();
                    wrapNewTkt.hide();
                    lblRefund.text('<?php echo esc_js( __( 'Net Refund Amount Payable to Client (৳) *', 'ifs-travel-erp' ) ); ?>');
                    const netRefund = Math.max(0, fare - penalty - fee);
                    inpRefund.val(netRefund.toFixed(2));
                } else if (type === 'Reissue') {
                    wrapFareDiff.show();
                    wrapNewPnr.show();
                    wrapNewTkt.show();
                    lblRefund.text('<?php echo esc_js( __( 'Total Reissue Charge to Collect (৳) *', 'ifs-travel-erp' ) ); ?>');
                    const totalCharge = penalty + fee + diff;
                    inpRefund.val(totalCharge.toFixed(2));
                } else if (type === 'Void') {
                    wrapFareDiff.hide();
                    wrapNewPnr.hide();
                    wrapNewTkt.hide();
                    lblRefund.text('<?php echo esc_js( __( 'Net Void Refund Payable to Client (৳) *', 'ifs-travel-erp' ) ); ?>');
                    const netVoid = Math.max(0, fare - fee);
                    inpRefund.val(netVoid.toFixed(2));
                }
            }

            $(document).on('input change', '#inp_process_type, #inp_original_fare, #inp_penalty, #inp_service_fee, #inp_fare_diff', calculateSettlement);
            calculateSettlement();
        });
        </script>
        <?php
    }
}