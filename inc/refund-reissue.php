<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Ultra-Modern Segmented Sub-Navigation for Refund, Reissue & Void Desk
 */
function ifs_terp_refund_render_tabs( $active_tab = 'list' ) {
    global $wpdb;
    $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=refund_reissue' );

    $table_refunds  = $wpdb->prefix . 'iterp_refund_reissue';
    $total_records  = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_refunds" );
    $total_refunded = (float) $wpdb->get_var( "SELECT SUM(refund_amount) FROM $table_refunds WHERE type = 'Refund'" );
    $total_penalty  = (float) $wpdb->get_var( "SELECT SUM(airline_penalty) FROM $table_refunds" );
    ?>
    <div class="ifs-pro-tab-wrapper">
        <div class="ifs-pro-header-card">
            <div class="ifs-pro-identity">
                <div class="ifs-pro-icon-glow">
                    <span class="dashicons dashicons-image-rotate"></span>
                </div>
                <div class="ifs-pro-title-meta">
                    <div class="ifs-pro-badge-group">
                        <span class="ifs-status-dot"></span>
                        <span class="ifs-meta-tag">Post-Ticketing Desk</span>
                        <span class="ifs-meta-tag-rose">Cancellation & Changes</span>
                    </div>
                    <h2 class="ifs-pro-heading">Ticket Refund, Reissue & Void Desk</h2>
                    <p class="ifs-pro-caption">Manage passenger cancellations, airline penalties, service charges, and date change adjustments</p>
                </div>
            </div>
            
            <div class="ifs-pro-stats-strip">
                <div class="ifs-stat-pill">
                    <span class="ifs-stat-lbl">Processed Files</span>
                    <span class="ifs-stat-num color-dark"><?php echo number_format( $total_records ); ?></span>
                </div>
                <div class="ifs-stat-pill">
                    <span class="ifs-stat-lbl">Refunds Paid</span>
                    <span class="ifs-stat-num color-emerald">৳<?php echo number_format( $total_refunded, 2 ); ?></span>
                </div>
                <div class="ifs-stat-pill">
                    <span class="ifs-stat-lbl">Airline Penalties</span>
                    <span class="ifs-stat-num color-rose">৳<?php echo number_format( $total_penalty, 2 ); ?></span>
                </div>
            </div>
        </div>

        <div class="ifs-pro-nav-container">
            <nav class="ifs-pro-nav-bar">
                <a href="<?php echo esc_url( $base_url . '&sub=list' ); ?>" 
                   class="ifs-pro-nav-btn <?php echo ( $active_tab === 'list' ) ? 'active-tab' : ''; ?>">
                    <span class="dashicons dashicons-list-view"></span>
                    <span class="ifs-btn-label">All Processed Records</span>
                    <span class="ifs-pro-counter"><?php echo $total_records; ?></span>
                </a>

                <a href="<?php echo esc_url( $base_url . '&sub=process' ); ?>" 
                   class="ifs-pro-nav-btn <?php echo ( $active_tab === 'process' ) ? 'active-tab' : ''; ?>">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <span class="ifs-btn-label">Process Cancellation / Reissue</span>
                </a>
            </nav>
        </div>
    </div>

    <style>
        .ifs-pro-tab-wrapper { margin-bottom: 30px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .ifs-pro-header-card { background: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; padding: 24px 28px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04); margin-bottom: 18px; }
        .ifs-pro-identity { display: flex; align-items: center; gap: 18px; }
        .ifs-pro-icon-glow { width: 52px; height: 52px; border-radius: 14px; background: linear-gradient(135deg, #e11d48 0%, #be123c 100%); display: flex; align-items: center; justify-content: center; color: #ffffff; box-shadow: 0 8px 18px -4px rgba(225, 29, 72, 0.35); flex-shrink: 0; }
        .ifs-pro-icon-glow .dashicons { font-size: 26px; width: 26px; height: 26px; }
        .ifs-pro-badge-group { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
        .ifs-status-dot { width: 6px; height: 6px; border-radius: 50%; background: #e11d48; display: inline-block; }
        .ifs-meta-tag { font-size: 10.5px; font-weight: 700; text-transform: uppercase; color: #64748b; }
        .ifs-meta-tag-rose { font-size: 10px; font-weight: 700; text-transform: uppercase; background: #ffe4e6; color: #be123c; padding: 2px 7px; border-radius: 4px; }
        .ifs-pro-heading { margin: 0; font-size: 22px; font-weight: 800; color: #0f172a; }
        .ifs-pro-caption { margin: 3px 0 0 0; font-size: 13.5px; color: #64748b; }
        .ifs-pro-stats-strip { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .ifs-stat-pill { background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 16px; border-radius: 10px; display: flex; flex-direction: column; gap: 2px; min-width: 100px; }
        .ifs-stat-lbl { font-size: 10.5px; font-weight: 600; color: #64748b; text-transform: uppercase; }
        .ifs-stat-num { font-size: 16px; font-weight: 800; }
        .color-dark { color: #0f172a; }
        .color-emerald { color: #059669; }
        .color-rose { color: #e11d48; }
        .ifs-pro-nav-container { display: flex; align-items: center; }
        .ifs-pro-nav-bar { background: #f1f5f9; border: 1px solid #e2e8f0; padding: 5px; border-radius: 12px; display: inline-flex; align-items: center; gap: 6px; max-width: 100%; overflow-x: auto; }
        .ifs-pro-nav-btn { display: inline-flex; align-items: center; gap: 9px; padding: 10px 20px; font-size: 13.5px; font-weight: 600; color: #64748b; text-decoration: none; border-radius: 9px; transition: all 0.2s ease; cursor: pointer; white-space: nowrap; border: 1px solid transparent; }
        .ifs-pro-nav-btn:hover { color: #0f172a; background: rgba(255, 255, 255, 0.65); }
        .ifs-pro-nav-btn.active-tab { background: #ffffff; color: #be123c; font-weight: 700; border: 1px solid rgba(190, 18, 60, 0.1); box-shadow: 0 4px 12px rgba(190, 18, 60, 0.08); }
        .ifs-pro-counter { background: #e2e8f0; color: #475569; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 20px; }
        .ifs-pro-nav-btn.active-tab .ifs-pro-counter { background: #be123c; color: #ffffff; }
    </style>
    <?php
}

/**
 * Main Controller for Refund & Reissue Module
 */
function ifs_terp_refund_reissue_tab() {
    global $wpdb;
    $table_refunds   = $wpdb->prefix . 'iterp_refund_reissue';
    $table_tickets   = $wpdb->prefix . 'iterp_tickets';
    $table_customers = $wpdb->prefix . 'iterp_customers';

    $sub_action = isset( $_GET['sub'] ) ? sanitize_text_field( $_GET['sub'] ) : 'list';
    $message    = '';

    // Handle Execution Action
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_refund_submit'] ) ) {
        check_admin_referer( 'ifs_refund_action', 'ifs_refund_nonce' );

        $ticket_id       = intval( $_POST['ticket_id'] ?? 0 );
        $pnr             = strtoupper( sanitize_text_field( $_POST['pnr'] ?? '' ) );
        $ticket_no       = sanitize_text_field( $_POST['ticket_no'] ?? '' );
        $type            = sanitize_text_field( $_POST['process_type'] ?? 'Refund' );
        $original_fare   = floatval( $_POST['original_fare'] ?? 0 );
        $penalty         = floatval( $_POST['airline_penalty'] ?? 0 );
        $service_charge  = floatval( $_POST['service_charge'] ?? 0 );
        $refund_total    = floatval( $_POST['refund_amount'] ?? 0 );
        $fare_difference = floatval( $_POST['fare_difference'] ?? 0 );
        $remarks         = sanitize_textarea_field( $_POST['remarks'] ?? '' );

        if ( $ticket_id > 0 ) {
            $tkt_obj = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_tickets WHERE id = %d", $ticket_id ) );
            if ( $tkt_obj ) {
                $pnr           = $tkt_obj->pnr;
                $ticket_no     = $tkt_obj->ticket_no;
                $original_fare = $tkt_obj->sell_price;
            }
        }

        $wpdb->insert(
            $table_refunds,
            array(
                'type'                  => $type,
                'ticket_id'             => $ticket_id,
                'pnr'                   => $pnr,
                'ticket_no'             => $ticket_no,
                'airline_penalty'       => $penalty,
                'agency_service_charge' => $service_charge,
                'refund_amount'         => $refund_total,
                'status'                => 'Processed',
                'remarks'               => $remarks,
                'processed_by'          => get_current_user_id(),
                'created_at'            => current_time( 'mysql' )
            ),
            array( '%s', '%d', '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%d', '%s' )
        );

        if ( $ticket_id > 0 ) {
            $wpdb->update( $table_tickets, array( 'status' => $type ), array( 'id' => $ticket_id ) );
        }

        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            ifs_terp_log_activity( "Processed $type for PNR: $pnr | Refund/Adjustment: ৳$refund_total" );
        }

        $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Ticket ' . esc_html( $type ) . ' operation executed and ledger adjusted successfully.</div>';
    }

    echo '<div class="ifs-refund-workspace">';
    
    // Render Modern Tab Header
    ifs_terp_refund_render_tabs( $sub_action );
    echo $message;

    // View: Action Form
    if ( $sub_action === 'process' ) {
        $active_tickets = $wpdb->get_results( "
            SELECT t.id, t.pnr, t.ticket_no, t.airline, t.sector, t.sell_price, c.full_name 
            FROM $table_tickets t
            LEFT JOIN $table_customers c ON t.customer_id = c.id
            WHERE t.status = 'Issued'
            ORDER BY t.id DESC LIMIT 100
        " );
        ?>
        <form method="post" action="" class="ifs-split-refund-editor">
            <?php wp_nonce_field( 'ifs_refund_action', 'ifs_refund_nonce' ); ?>

            <div class="ifs-refund-form-body">
                
                <!-- Section 1: Ticket Selection & Operation Type -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">01</div>
                        <div>
                            <h3 class="ifs-card-title">Select E-Ticket & Operation Mode</h3>
                            <p class="ifs-card-desc">Choose from issued tickets or input manual GDS reference numbers</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <div class="ifs-field-block col-span-3">
                            <label class="ifs-field-label" for="inp_ticket_id">Select From Issued Tickets (Optional Auto-Fill)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-tickets-alt field-icon"></span>
                                <select name="ticket_id" id="inp_ticket_id" class="ifs-input-field">
                                    <option value="0">-- Enter Manually or Select Issued Ticket --</option>
                                    <?php foreach ( $active_tickets as $t ) : ?>
                                        <option value="<?php echo $t->id; ?>" 
                                                data-pnr="<?php echo esc_attr( $t->pnr ); ?>"
                                                data-tkt="<?php echo esc_attr( $t->ticket_no ); ?>"
                                                data-fare="<?php echo esc_attr( $t->sell_price ); ?>"
                                                data-airline="<?php echo esc_attr( $t->airline ); ?>"
                                                data-sector="<?php echo esc_attr( $t->sector ); ?>"
                                                data-pax="<?php echo esc_attr( $t->full_name ); ?>">
                                            PNR: <?php echo esc_html( $t->pnr ); ?> &mdash; <?php echo esc_html( $t->full_name ); ?> (<?php echo esc_html( $t->airline ); ?>: <?php echo esc_html( $t->sector ); ?> | Fare: ৳<?php echo number_format( $t->sell_price, 2 ); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_process_type">Operation Type <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-image-rotate field-icon"></span>
                                <select name="process_type" id="inp_process_type" class="ifs-input-field">
                                    <option value="Refund">Refund Ticket</option>
                                    <option value="Reissue">Date Change / Reissue</option>
                                    <option value="Void">Void (Same Day Cancellation)</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_pnr">PNR / Booking Ref <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-randomize field-icon"></span>
                                <input type="text" name="pnr" id="inp_pnr" required 
                                       placeholder="e.g. 7X9K21" class="ifs-input-field uppercase font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_ticket_no">E-Ticket Number</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-nametag field-icon"></span>
                                <input type="text" name="ticket_no" id="inp_ticket_no" 
                                       placeholder="077-1234567890" class="ifs-input-field font-mono">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Penalty & Financial Settlement Calculation -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">02</div>
                        <div>
                            <h3 class="ifs-card-title">Commercial Penalty & Settlement Breakdown</h3>
                            <p class="ifs-card-desc">Calculate airline deductions, agency fee adjustments, and net payable/receivable balance</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_original_fare">Original Ticket Price (৳) <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-money-alt field-icon"></span>
                                <input type="number" step="0.01" name="original_fare" id="inp_original_fare" required 
                                       placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_penalty">Airline Penalty Charge (৳)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-dismiss field-icon"></span>
                                <input type="number" step="0.01" name="airline_penalty" id="inp_penalty" 
                                       placeholder="0.00" class="ifs-input-field font-mono color-rose font-bold">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_service_fee">Agency Service Fee (৳)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-admin-generic field-icon"></span>
                                <input type="number" step="0.01" name="service_charge" id="inp_service_fee" 
                                       placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block" id="wrap_fare_diff" style="display:none;">
                            <label class="ifs-field-label" for="inp_fare_diff">New Fare Difference (৳)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-chart-line field-icon"></span>
                                <input type="number" step="0.01" name="fare_difference" id="inp_fare_diff" 
                                       placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-2" id="wrap_settlement_box">
                            <label class="ifs-field-label" id="lbl_refund_amount">Net Refund to Client (৳) <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-calculator field-icon"></span>
                                <input type="number" step="0.01" name="refund_amount" id="inp_refund_amount" required 
                                       placeholder="0.00" class="ifs-input-field font-mono font-bold color-emerald">
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-3">
                            <label class="ifs-field-label" for="inp_remarks">Cancellation / Reissue Reason & Notes</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-edit field-icon"></span>
                                <textarea name="remarks" id="inp_remarks" rows="2" class="ifs-input-field" 
                                          placeholder="Passenger medical emergency, airline schedule change, date change details..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ifs-action-strip">
                    <a href="<?php echo esc_url( $base_url . '&sub=list' ); ?>" class="ifs-btn-back">
                        <span class="dashicons dashicons-arrow-left-alt"></span> Cancel
                    </a>
                    <button type="submit" name="ifs_refund_submit" class="ifs-btn-primary">
                        <span class="dashicons dashicons-saved"></span> Execute & Record Operation
                    </button>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="ifs-preview-sidebar">
                <div class="ifs-preview-sticky">
                    <div class="ifs-card-preview-header">
                        <span class="dashicons dashicons-calculator"></span> Live Settlement Voucher Preview
                    </div>

                    <div class="ifs-settlement-card">
                        <div class="settle-top-strip">
                            <span class="settle-brand">IFS POST-TICKETING</span>
                            <span class="settle-type" id="prev_type">REFUND</span>
                        </div>

                        <div class="settle-hero-box">
                            <span class="settle-lbl" id="prev_amount_label">NET REFUNDABLE BALANCE</span>
                            <h3 class="settle-amount" id="prev_amount_display">৳0.00</h3>
                            <span class="settle-pnr font-mono" id="prev_pnr_display">PNR: ------</span>
                        </div>

                        <div class="settle-breakdown-list">
                            <div class="breakdown-row">
                                <span>Original Ticket Fare:</span>
                                <strong id="prev_fare" class="font-mono">৳0.00</strong>
                            </div>
                            <div class="breakdown-row">
                                <span>Airline Penalty:</span>
                                <strong id="prev_penalty" class="font-mono color-rose">-৳0.00</strong>
                            </div>
                            <div class="breakdown-row">
                                <span>Agency Service Fee:</span>
                                <strong id="prev_fee" class="font-mono color-rose">-৳0.00</strong>
                            </div>
                            <div class="breakdown-row" id="prev_row_diff" style="display:none;">
                                <span>Fare Difference:</span>
                                <strong id="prev_diff" class="font-mono color-blue">+৳0.00</strong>
                            </div>
                        </div>

                        <div class="settle-footer-strip">
                            <span class="dashicons dashicons-shield"></span> Automatic General Ledger & Customer Portfolio Sync
                        </div>
                    </div>

                    <div class="ifs-tip-box">
                        <div class="tip-title"><span class="dashicons dashicons-info"></span> Cancellation Protocol Guide</div>
                        <ul class="tip-list">
                            <li><strong>Refund:</strong> Standard cancellation with penalty deducted from initial fare.</li>
                            <li><strong>Reissue:</strong> Adjust date change penalty + airline fare difference.</li>
                            <li><strong>Void:</strong> Same-day cancellation before 23:59 BSP cycle (Zero airline penalty).</li>
                        </ul>
                    </div>
                </div>
            </div>
        </form>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selTicket   = document.getElementById('inp_ticket_id');
            const selType     = document.getElementById('inp_process_type');
            const inpPnr      = document.getElementById('inp_pnr');
            const inpTicketNo = document.getElementById('inp_ticket_no');
            const inpFare     = document.getElementById('inp_original_fare');
            const inpPenalty  = document.getElementById('inp_penalty');
            const inpFee      = document.getElementById('inp_service_fee');
            const inpFareDiff = document.getElementById('inp_fare_diff');
            const inpRefund   = document.getElementById('inp_refund_amount');

            const wrapFareDiff = document.getElementById('wrap_fare_diff');
            const lblRefund    = document.getElementById('lbl_refund_amount');

            const prevType       = document.getElementById('prev_type');
            const prevAmountLbl  = document.getElementById('prev_amount_label');
            const prevAmountDisp = document.getElementById('prev_amount_display');
            const prevPnrDisp    = document.getElementById('prev_pnr_display');
            const prevFare       = document.getElementById('prev_fare');
            const prevPenalty    = document.getElementById('prev_penalty');
            const prevFee        = document.getElementById('prev_fee');
            const prevDiff       = document.getElementById('prev_diff');
            const prevRowDiff    = document.getElementById('prev_row_diff');

            if (selTicket) {
                selTicket.addEventListener('change', function() {
                    if (this.selectedIndex > 0) {
                        const opt = this.options[this.selectedIndex];
                        inpPnr.value      = opt.getAttribute('data-pnr') || '';
                        inpTicketNo.value = opt.getAttribute('data-tkt') || '';
                        inpFare.value     = opt.getAttribute('data-fare') || '';
                        calculateSettlement();
                    }
                });
            }

            function calculateSettlement() {
                const type    = selType ? selType.value : 'Refund';
                const fare    = parseFloat(inpFare ? inpFare.value : 0) || 0;
                const penalty = parseFloat(inpPenalty ? inpPenalty.value : 0) || 0;
                const fee     = parseFloat(inpFee ? inpFee.value : 0) || 0;
                const diff    = parseFloat(inpFareDiff ? inpFareDiff.value : 0) || 0;

                if (prevType) prevType.textContent = type.toUpperCase();
                if (prevPnrDisp) prevPnrDisp.textContent = 'PNR: ' + (inpPnr.value.trim().toUpperCase() || '------');

                if (prevFare) prevFare.textContent = '৳' + fare.toLocaleString('en-US', { minimumFractionDigits: 2 });
                if (prevPenalty) prevPenalty.textContent = '-৳' + penalty.toLocaleString('en-US', { minimumFractionDigits: 2 });
                if (prevFee) prevFee.textContent = '-৳' + fee.toLocaleString('en-US', { minimumFractionDigits: 2 });

                if (type === 'Refund') {
                    if (wrapFareDiff) wrapFareDiff.style.display = 'none';
                    if (prevRowDiff) prevRowDiff.style.display = 'none';
                    if (lblRefund) lblRefund.textContent = 'Net Refund to Client (৳) *';
                    if (prevAmountLbl) prevAmountLbl.textContent = 'NET REFUNDABLE TO CLIENT';

                    const netRefund = Math.max(0, fare - penalty - fee);
                    if (inpRefund) inpRefund.value = netRefund.toFixed(2);
                    if (prevAmountDisp) {
                        prevAmountDisp.textContent = '৳' + netRefund.toLocaleString('en-US', { minimumFractionDigits: 2 });
                        prevAmountDisp.style.color = '#86efac';
                    }
                } else if (type === 'Reissue') {
                    if (wrapFareDiff) wrapFareDiff.style.display = 'flex';
                    if (prevRowDiff) prevRowDiff.style.display = 'flex';
                    if (prevDiff) prevDiff.textContent = '+৳' + diff.toLocaleString('en-US', { minimumFractionDigits: 2 });
                    if (lblRefund) lblRefund.textContent = 'Total Reissue Charge to Collect (৳) *';
                    if (prevAmountLbl) prevAmountLbl.textContent = 'TOTAL CHARGE TO COLLECT';

                    const totalCharge = penalty + fee + diff;
                    if (inpRefund) inpRefund.value = totalCharge.toFixed(2);
                    if (prevAmountDisp) {
                        prevAmountDisp.textContent = '৳' + totalCharge.toLocaleString('en-US', { minimumFractionDigits: 2 });
                        prevAmountDisp.style.color = '#fde047';
                    }
                } else if (type === 'Void') {
                    if (wrapFareDiff) wrapFareDiff.style.display = 'none';
                    if (prevRowDiff) prevRowDiff.style.display = 'none';
                    if (lblRefund) lblRefund.textContent = 'Net Void Refund (৳) *';
                    if (prevAmountLbl) prevAmountLbl.textContent = 'NET VOID REFUND (SAME DAY)';

                    const netVoid = Math.max(0, fare - fee);
                    if (inpRefund) inpRefund.value = netVoid.toFixed(2);
                    if (prevAmountDisp) {
                        prevAmountDisp.textContent = '৳' + netVoid.toLocaleString('en-US', { minimumFractionDigits: 2 });
                        prevAmountDisp.style.color = '#86efac';
                    }
                }
            }

            [selType, inpPnr, inpTicketNo, inpFare, inpPenalty, inpFee, inpFareDiff].forEach(el => {
                if (el) {
                    el.addEventListener('input', calculateSettlement);
                    el.addEventListener('change', calculateSettlement);
                }
            });

            calculateSettlement();
        });
        </script>
        <?php
    }
    // View: Table List (Default)
    else {
        $records = $wpdb->get_results( "SELECT * FROM $table_refunds ORDER BY id DESC" );
        ?>
        <div class="ifs-table-card">
            <div class="ifs-table-top-bar">
                <div class="ifs-table-title-group">
                    <h3 class="ifs-table-heading"><span class="dashicons dashicons-backup"></span> Post-Ticketing Action Records</h3>
                    <p class="ifs-table-caption">Audit trail of ticket cancellations, date change penalties, and customer refunds</p>
                </div>
                <div class="ifs-table-btn-group">
                    <a href="<?php echo esc_url( $base_url . '&sub=process' ); ?>" class="ifs-btn-primary">
                        <span class="dashicons dashicons-plus-alt2"></span> Process Cancellation / Reissue
                    </a>
                </div>
            </div>

            <div class="ifs-table-responsive-wrapper">
                <table class="ifs-pro-datatable" id="ifsRefundTable">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Action ID</th>
                            <th>Date & Time</th>
                            <th>Operation</th>
                            <th>PNR Ref</th>
                            <th>Ticket Number</th>
                            <th style="text-align: right;">Penalty (৳)</th>
                            <th style="text-align: right;">Service Fee (৳)</th>
                            <th style="text-align: right;">Net Adjustment (৳)</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $records ) : foreach ( $records as $rec ) : 
                            $badge_class = 'badge-refund';
                            if ( $rec->type === 'Reissue' ) $badge_class = 'badge-reissue';
                            elseif ( $rec->type === 'Void' ) $badge_class = 'badge-void';
                        ?>
                            <tr>
                                <td><span class="ifs-id-badge">#REF-<?php echo str_pad( (string) $rec->id, 5, '0', STR_PAD_LEFT ); ?></span></td>
                                <td><?php echo date( 'd M Y, h:i A', strtotime( $rec->created_at ) ); ?></td>
                                <td><span class="ifs-operation-badge <?php echo esc_attr( $badge_class ); ?>"><?php echo esc_html( $rec->type ); ?></span></td>
                                <td><span class="ifs-pnr-pill font-mono"><?php echo esc_html( $rec->pnr ); ?></span></td>
                                <td class="font-mono"><?php echo esc_html( $rec->ticket_no ?: '-' ); ?></td>
                                <td style="text-align: right; color: #dc2626; font-family: ui-monospace, monospace; font-weight: 700;">৳<?php echo number_format( $rec->airline_penalty, 2 ); ?></td>
                                <td style="text-align: right; color: #64748b; font-family: ui-monospace, monospace;">৳<?php echo number_format( $rec->agency_service_charge, 2 ); ?></td>
                                <td style="text-align: right; font-weight: 800; color: #059669; font-family: ui-monospace, monospace; font-size: 13.5px;">৳<?php echo number_format( $rec->refund_amount, 2 ); ?></td>
                                <td style="font-size: 12px; color: #64748b; max-width: 200px;"><?php echo esc_html( $rec->remarks ?: '-' ); ?></td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr>
                                <td colspan="9" class="ifs-empty-table">
                                    <div class="ifs-empty-state">
                                        <span class="dashicons dashicons-image-rotate"></span>
                                        <h4>No Post-Ticketing Files Processed Yet</h4>
                                        <p>Process your first ticket refund, date reissue, or void request.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                if ($.fn.DataTable) {
                    $('#ifsRefundTable').DataTable({
                        "pageLength": 15,
                        "order": [[ 0, "desc" ]],
                        "language": {
                            "search": "_INPUT_",
                            "searchPlaceholder": "Search PNR, Ticket No, Type...",
                            "lengthMenu": "Show _MENU_ entries",
                            "paginate": { "previous": "&larr; Prev", "next": "Next &rarr;" }
                        }
                    });
                }
            });
        </script>
        <?php
    }

    echo '</div>';
    ?>
    <style>
        .ifs-refund-workspace { max-width: 1420px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; }
        
        .ifs-toast { padding: 14px 20px; border-radius: 10px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 10px; margin-bottom: 22px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .ifs-toast.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .ifs-toast.danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .ifs-toast .dashicons { font-size: 18px; width: 18px; height: 18px; }

        .ifs-split-refund-editor { display: grid; grid-template-columns: 1fr 390px; gap: 28px; align-items: flex-start; }
        @media (max-width: 1140px) { .ifs-split-refund-editor { grid-template-columns: 1fr; } }

        .ifs-panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 26px; margin-bottom: 22px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); }
        .ifs-card-header { display: flex; align-items: center; gap: 14px; margin-bottom: 22px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9; }
        .ifs-step-num { width: 34px; height: 34px; border-radius: 9px; background: linear-gradient(135deg, #e11d48 0%, #be123c 100%); color: #ffffff; font-weight: 800; font-size: 13px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(225, 29, 72, 0.2); flex-shrink: 0; }
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
        .ifs-field-wrap .field-icon { position: absolute; left: 12px; color: #94a3b8; font-size: 17px; width: 17px; height: 17px; pointer-events: none; z-index: 2; }
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
        textarea.ifs-input-field { padding: 10px 12px 10px 38px !important; font-family: inherit; }
        .ifs-field-wrap select.ifs-input-field {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 14px;
            padding-right: 32px !important;
        }
        .ifs-field-wrap .ifs-input-field:focus { border-color: #be123c; box-shadow: 0 0 0 3px rgba(190, 18, 60, 0.12); }

        .uppercase { text-transform: uppercase; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .font-bold { font-weight: 700; }
        .color-rose { color: #e11d48 !important; }
        .color-emerald { color: #059669 !important; }
        .color-blue { color: #0284c7 !important; }

        .ifs-action-strip { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; }
        .ifs-btn-back { color: #64748b; text-decoration: none; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .ifs-btn-primary { background: linear-gradient(135deg, #e11d48 0%, #be123c 100%); color: #ffffff !important; border: none; padding: 11px 26px; border-radius: 8px; font-size: 13.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(225, 29, 72, 0.25); }

        .ifs-preview-sticky { position: sticky; top: 30px; }
        .ifs-card-preview-header { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }

        .ifs-settlement-card {
            background: linear-gradient(145deg, #4c0519 0%, #881337 60%, #be123c 100%);
            border-radius: 16px;
            padding: 24px;
            color: #ffffff;
            box-shadow: 0 16px 36px -6px rgba(190, 18, 60, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        .settle-top-strip { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
        .settle-brand { font-size: 9.5px; font-weight: 800; letter-spacing: 1px; color: #fecdd3; }
        .settle-type { background: rgba(255, 255, 255, 0.2); padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 800; }

        .settle-hero-box { text-align: center; background: rgba(0, 0, 0, 0.2); padding: 16px; border-radius: 12px; margin-bottom: 18px; }
        .settle-lbl { font-size: 9.5px; font-weight: 700; color: #fecdd3; letter-spacing: 0.5px; display: block; margin-bottom: 4px; }
        .settle-amount { margin: 0; font-size: 26px; font-weight: 900; color: #86efac; }
        .settle-pnr { font-size: 12px; color: #bae6fd; margin-top: 4px; display: block; }

        .settle-breakdown-list { display: flex; flex-direction: column; gap: 8px; padding-bottom: 14px; border-bottom: 1px dashed rgba(255, 255, 255, 0.2); margin-bottom: 14px; font-size: 12.5px; }
        .breakdown-row { display: flex; justify-content: space-between; color: #fecdd3; }
        .breakdown-row strong { color: #ffffff; }

        .settle-footer-strip { font-size: 10px; color: #fecdd3; display: flex; align-items: center; gap: 6px; }

        .ifs-tip-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-top: 18px; }
        .tip-title { font-size: 12px; font-weight: 800; color: #be123c; display: flex; align-items: center; gap: 6px; margin-bottom: 8px; }
        .tip-list { margin: 0; padding-left: 18px; font-size: 11.5px; color: #64748b; line-height: 1.6; }

        .ifs-table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); overflow: hidden; }
        .ifs-table-top-bar { padding: 22px 26px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; gap: 15px; }
        .ifs-table-heading { margin: 0; font-size: 17px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        .ifs-table-heading .dashicons { color: #be123c; font-size: 20px; width: 20px; height: 20px; }
        .ifs-table-caption { margin: 3px 0 0 0; font-size: 13px; color: #64748b; }

        .ifs-table-responsive-wrapper { padding: 15px 24px 24px 24px; overflow-x: auto; }
        .ifs-pro-datatable { width: 100%; border-collapse: collapse; font-size: 13px; }
        .ifs-pro-datatable thead th { background: #f8fafc; color: #475569; font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 12px 14px; border-bottom: 2px solid #e2e8f0; text-align: left; }
        .ifs-pro-datatable tbody td { padding: 13px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #334155; }

        .ifs-id-badge { background: #f1f5f9; color: #475569; font-family: ui-monospace, monospace; font-weight: 700; font-size: 11px; padding: 3px 6px; border-radius: 6px; }
        .ifs-pnr-pill { background: #fee2e2; color: #991b1b; font-weight: 800; font-size: 12px; padding: 2px 6px; border-radius: 4px; }
        .ifs-operation-badge { font-size: 10.5px; font-weight: 800; padding: 2px 8px; border-radius: 4px; text-transform: uppercase; }
        .badge-refund { background: #fee2e2; color: #991b1b; }
        .badge-reissue { background: #e0f2fe; color: #0369a1; }
        .badge-void { background: #fef3c7; color: #92400e; }

        .ifs-empty-table { text-align: center; padding: 50px 20px !important; }
        .ifs-empty-state .dashicons { font-size: 44px; width: 44px; height: 44px; color: #cbd5e1; margin-bottom: 10px; }
    </style>
    <?php
}