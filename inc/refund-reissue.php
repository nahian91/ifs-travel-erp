<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Ultra-Modern Segmented Sub-Navigation for Refund, Reissue & Void Desk
 * Consistent with IFS Travel ERP Executive Design System
 */
function ifs_terp_refund_render_tabs( $active_tab = 'list' ) {
    global $wpdb;
    $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=refund_reissue' );

    $table_refunds      = $wpdb->prefix . 'iterp_refund_reissue';
    $total_records      = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_refunds" );
    $total_refunded     = (float) $wpdb->get_var( "SELECT SUM(refund_amount) FROM $table_refunds WHERE type IN ('Refund', 'Void')" );
    $total_penalty      = (float) $wpdb->get_var( "SELECT SUM(airline_penalty) FROM $table_refunds" );
    $total_reissue_fees = (float) $wpdb->get_var( "SELECT SUM(agency_service_charge) FROM $table_refunds" );
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
                        <span class="ifs-meta-tag-rose">Cancellations &amp; Adjustments</span>
                    </div>
                    <h2 class="ifs-pro-heading">Ticket Refund, Reissue &amp; Void Desk</h2>
                    <p class="ifs-pro-caption">Manage passenger cancellations, airline penalties, service fees, and double-entry ledger adjustments</p>
                </div>
            </div>
            
            <div class="ifs-pro-stats-strip">
                <div class="ifs-stat-pill">
                    <span class="ifs-stat-lbl">Processed Files</span>
                    <span class="ifs-stat-num color-dark"><?php echo number_format( $total_records ); ?></span>
                </div>
                <div class="ifs-stat-pill">
                    <span class="ifs-stat-lbl">Refunds Paid</span>
                    <span class="ifs-stat-num color-rose">৳<?php echo number_format( $total_refunded, 2 ); ?></span>
                </div>
                <div class="ifs-stat-pill">
                    <span class="ifs-stat-lbl">Airline Penalties</span>
                    <span class="ifs-stat-num color-amber">৳<?php echo number_format( $total_penalty, 2 ); ?></span>
                </div>
                <div class="ifs-stat-pill">
                    <span class="ifs-stat-lbl">Agency Fees Earned</span>
                    <span class="ifs-stat-num color-emerald">৳<?php echo number_format( $total_reissue_fees, 2 ); ?></span>
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
    <?php
}

/**
 * Main Controller for Refund, Reissue & Void Module (Full CRUD: List, Process, Edit, View Voucher, Delete)
 */
function ifs_terp_refund_reissue_tab() {
    global $wpdb;
    $table_refunds       = $wpdb->prefix . 'iterp_refund_reissue';
    $table_tickets       = $wpdb->prefix . 'iterp_tickets';
    $table_customers     = $wpdb->prefix . 'iterp_customers';
    $table_ledger        = $wpdb->prefix . 'iterp_ledger';
    $table_agent_ledgers = $wpdb->prefix . 'iterp_agent_ledgers';
    $table_agents        = $wpdb->prefix . 'iterp_agents';
    $base_url            = admin_url( 'admin.php?page=ifs_travel_erp&tab=refund_reissue' );

    $sub_action = isset( $_GET['sub'] ) ? sanitize_text_field( $_GET['sub'] ) : 'list';
    $message    = '';

    /* =========================================================================
       1. DELETE ACTION HANDLER
       ========================================================================= */
    if ( $sub_action === 'delete' && isset( $_GET['id'] ) ) {
        $del_id = intval( $_GET['id'] );
        check_admin_referer( 'delete_refund_' . $del_id );

        $ref_info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_refunds WHERE id = %d", $del_id ) );
        if ( $ref_info ) {
            // Restore linked ticket status back to Issued
            if ( ! empty( $ref_info->ticket_id ) ) {
                $wpdb->update( $table_tickets, array( 'status' => 'Issued' ), array( 'id' => $ref_info->ticket_id ) );
            }
            $wpdb->delete( $table_refunds, array( 'id' => $del_id ), array( '%d' ) );

            if ( function_exists( 'ifs_terp_log_activity' ) ) {
                ifs_terp_log_activity( "Deleted Post-Ticketing Record #REF-$del_id (PNR: {$ref_info->pnr} | Type: {$ref_info->type})" );
            }
        }

        wp_safe_redirect( add_query_arg( array( 'page' => 'ifs_travel_erp', 'tab' => 'refund_reissue', 'msg' => 'deleted' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    /* =========================================================================
       2. SAVE / UPDATE ACTION HANDLER (PROCESS & EDIT)
       ========================================================================= */
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_refund_submit'] ) ) {
        check_admin_referer( 'ifs_refund_action', 'ifs_refund_nonce' );

        $edit_id            = intval( $_POST['edit_id'] ?? 0 );
        $is_update          = ( $edit_id > 0 );
        $ticket_id          = intval( $_POST['ticket_id'] ?? 0 );
        $customer_id        = intval( $_POST['customer_id'] ?? 0 );
        $agent_id           = intval( $_POST['agent_id'] ?? 0 );
        $supplier_id        = intval( $_POST['supplier_id'] ?? 0 );
        $pnr                = strtoupper( sanitize_text_field( $_POST['pnr'] ?? '' ) );
        $ticket_no          = sanitize_text_field( $_POST['ticket_no'] ?? '' );
        $new_pnr            = strtoupper( sanitize_text_field( $_POST['new_pnr'] ?? '' ) );
        $new_ticket_no      = sanitize_text_field( $_POST['new_ticket_no'] ?? '' );
        $type               = sanitize_text_field( $_POST['process_type'] ?? 'Refund' );
        $original_fare      = floatval( $_POST['original_fare'] ?? 0 );
        $penalty            = floatval( $_POST['airline_penalty'] ?? 0 );
        $service_charge     = floatval( $_POST['service_charge'] ?? 0 );
        $fare_difference    = floatval( $_POST['fare_difference'] ?? 0 );
        $refund_total       = floatval( $_POST['refund_amount'] ?? 0 );
        $settlement_method  = sanitize_text_field( $_POST['settlement_method'] ?? 'Bank Transfer' );
        $remarks            = sanitize_textarea_field( $_POST['remarks'] ?? '' );

        if ( $ticket_id > 0 && ! $is_update ) {
            $tkt_obj = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_tickets WHERE id = %d", $ticket_id ) );
            if ( $tkt_obj ) {
                $customer_id   = $tkt_obj->customer_id;
                $agent_id      = $tkt_obj->agent_id;
                $supplier_id   = $tkt_obj->supplier_id;
                $pnr           = $tkt_obj->pnr;
                $ticket_no     = $tkt_obj->ticket_no;
                $original_fare = (float) $tkt_obj->sell_price;
            }
        }

        $data_array = array(
            'type'                  => $type,
            'ticket_id'             => $ticket_id,
            'customer_id'           => $customer_id,
            'agent_id'              => $agent_id,
            'supplier_id'           => $supplier_id,
            'pnr'                   => $pnr,
            'new_pnr'               => $new_pnr,
            'ticket_no'             => $ticket_no,
            'new_ticket_no'         => $new_ticket_no,
            'original_fare'         => $original_fare,
            'airline_penalty'       => $penalty,
            'agency_service_charge' => $service_charge,
            'fare_difference'       => $fare_difference,
            'refund_amount'         => $refund_total,
            'settlement_method'     => $settlement_method,
            'status'                => 'Processed',
            'remarks'               => $remarks,
            'processed_by'          => get_current_user_id()
        );

        if ( $is_update ) {
            $wpdb->update( $table_refunds, $data_array, array( 'id' => $edit_id ) );
            $refund_id = $edit_id;
            $message   = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Record #REF-' . str_pad( (string) $edit_id, 5, '0', STR_PAD_LEFT ) . ' updated successfully.</div>';
        } else {
            $data_array['created_at'] = current_time( 'mysql' );
            $wpdb->insert( $table_refunds, $data_array );
            $refund_id = $wpdb->insert_id;

            // Sync ticket status
            if ( $ticket_id > 0 ) {
                $wpdb->update( $table_tickets, array( 'status' => $type ), array( 'id' => $ticket_id ) );
            }

            // General Ledger Auto-Posting
            if ( $type === 'Refund' || $type === 'Void' ) {
                if ( $refund_total > 0 ) {
                    $wpdb->insert(
                        $table_ledger,
                        array(
                            'transaction_type' => 'Expense',
                            'category'         => 'Ticket Refund Payout',
                            'amount'           => $refund_total,
                            'payment_method'   => $settlement_method,
                            'reference_no'     => "PNR: $pnr (#REF-$refund_id)",
                            'description'      => "Ticket $type for PNR $pnr (Penalty: ৳$penalty | Srv Fee: ৳$service_charge)",
                            'transaction_date' => current_time( 'mysql' ),
                            'logged_by'        => get_current_user_id()
                        )
                    );
                }
                if ( $service_charge > 0 ) {
                    $wpdb->insert(
                        $table_ledger,
                        array(
                            'transaction_type' => 'Income',
                            'category'         => 'Refund Service Fee',
                            'amount'           => $service_charge,
                            'payment_method'   => $settlement_method,
                            'reference_no'     => "PNR: $pnr (#REF-$refund_id)",
                            'description'      => "Agency fee retained for processing $type on PNR $pnr",
                            'transaction_date' => current_time( 'mysql' ),
                            'logged_by'        => get_current_user_id()
                        )
                    );
                }
            } elseif ( $type === 'Reissue' ) {
                if ( $refund_total > 0 ) {
                    $wpdb->insert(
                        $table_ledger,
                        array(
                            'transaction_type' => 'Income',
                            'category'         => 'Ticket Reissue Collection',
                            'amount'           => $refund_total,
                            'payment_method'   => $settlement_method,
                            'reference_no'     => "PNR: $pnr (#REF-$refund_id)",
                            'description'      => "Reissue fee & penalty collected on PNR $pnr (New TKT: $new_ticket_no)",
                            'transaction_date' => current_time( 'mysql' ),
                            'logged_by'        => get_current_user_id()
                        )
                    );
                }
            }

            // Sub-Agent Balance Adjustment
            if ( $agent_id > 0 ) {
                $cur_bal = (float) $wpdb->get_var( $wpdb->prepare( "SELECT current_balance FROM $table_agents WHERE id = %d", $agent_id ) );
                if ( $type === 'Refund' || $type === 'Void' ) {
                    $new_bal = $cur_bal + $refund_total;
                    $wpdb->update( $table_agents, array( 'current_balance' => $new_bal ), array( 'id' => $agent_id ) );
                    $wpdb->insert(
                        $table_agent_ledgers,
                        array(
                            'agent_id'       => $agent_id,
                            'reference_type' => "Ticket $type",
                            'reference_id'   => $refund_id,
                            'debit'          => 0,
                            'credit'         => $refund_total,
                            'balance_after'  => $new_bal,
                            'note'           => "Credit for $type on PNR: $pnr (#REF-$refund_id)",
                            'created_at'     => current_time( 'mysql' )
                        )
                    );
                } elseif ( $type === 'Reissue' ) {
                    $new_bal = $cur_bal - $refund_total;
                    $wpdb->update( $table_agents, array( 'current_balance' => $new_bal ), array( 'id' => $agent_id ) );
                    $wpdb->insert(
                        $table_agent_ledgers,
                        array(
                            'agent_id'       => $agent_id,
                            'reference_type' => "Ticket Reissue",
                            'reference_id'   => $refund_id,
                            'debit'          => $refund_total,
                            'credit'         => 0,
                            'balance_after'  => $new_bal,
                            'note'           => "Debit for Reissue charges on PNR: $pnr (#REF-$refund_id)",
                            'created_at'     => current_time( 'mysql' )
                        )
                    );
                }
            }

            $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Ticket ' . esc_html( $type ) . ' operation executed and ledger adjusted successfully.</div>';
        }

        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            ifs_terp_log_activity( "Executed/Updated $type for PNR: $pnr | Net Adjustment: ৳$refund_total" );
        }
    }

    if ( isset( $_GET['msg'] ) && $_GET['msg'] === 'deleted' ) {
        $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Record removed successfully and ticket status restored.</div>';
    }

    echo '<div class="ifs-refund-workspace">';
    ifs_terp_refund_render_tabs( in_array( $sub_action, array( 'process', 'edit' ), true ) ? 'process' : 'list' );
    echo $message;

    /* =========================================================================
       3. VIEW SINGLE VOUCHER DETAILS (sub=view)
       ========================================================================= */
    if ( $sub_action === 'view' && isset( $_GET['id'] ) ) {
        $view_id = intval( $_GET['id'] );
        $record  = $wpdb->get_row( $wpdb->prepare( "
            SELECT r.*, c.full_name AS customer_name, c.mobile AS customer_mobile, a.agency_name, t.airline, t.sector, t.travel_date
            FROM $table_refunds r
            LEFT JOIN $table_customers c ON r.customer_id = c.id
            LEFT JOIN $table_agents a ON r.agent_id = a.id
            LEFT JOIN $table_tickets t ON r.ticket_id = t.id
            WHERE r.id = %d
        ", $view_id ) );

        if ( ! $record ) {
            echo '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> Record not found.</div>';
            echo '</div>';
            return;
        }

        $badge_class = ( $record->type === 'Reissue' ) ? 'badge-reissue' : ( ( $record->type === 'Void' ) ? 'badge-void' : 'badge-refund' );
        ?>
        <div class="ifs-single-view-container">
            <div class="ifs-view-top-bar">
                <div class="view-title-group">
                    <span class="ifs-id-badge">#REF-<?php echo str_pad( (string) $record->id, 5, '0', STR_PAD_LEFT ); ?></span>
                    <span class="ifs-operation-badge <?php echo esc_attr( $badge_class ); ?>"><?php echo esc_html( $record->type ); ?></span>
                    <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;">Post-Ticketing Settlement Voucher</h3>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button type="button" onclick="window.print();" class="ifs-btn-back"><span class="dashicons dashicons-printer"></span> Print Voucher</button>
                    <a href="<?php echo esc_url( $base_url . '&sub=edit&id=' . $record->id ); ?>" class="ifs-action-pill edit" style="padding: 7px 14px; font-size: 13px;"><span class="dashicons dashicons-edit"></span> Edit Record</a>
                    <a href="<?php echo esc_url( $base_url . '&sub=list' ); ?>" class="ifs-btn-back"><span class="dashicons dashicons-arrow-left-alt"></span> Back to List</a>
                </div>
            </div>

            <div class="ifs-voucher-split-grid">
                <!-- Settlement Card Box -->
                <div class="ifs-settlement-card">
                    <div class="settle-top-strip">
                        <span class="settle-brand">IFS POST-TICKETING VOUCHER</span>
                        <span class="settle-type"><?php echo strtoupper( esc_html( $record->type ) ); ?></span>
                    </div>

                    <div class="settle-hero-box">
                        <span class="settle-lbl"><?php echo ( $record->type === 'Reissue' ) ? 'TOTAL REISSUE CHARGE COLLECTED' : 'NET REFUND PAID TO CLIENT'; ?></span>
                        <h3 class="settle-amount">৳<?php echo number_format( (float) $record->refund_amount, 2 ); ?></h3>
                        <span class="settle-pnr font-mono">PNR: <?php echo esc_html( $record->pnr ); ?></span>
                    </div>

                    <div class="settle-breakdown-list">
                        <div class="breakdown-row">
                            <span>Original Ticket Fare:</span>
                            <strong class="font-mono">৳<?php echo number_format( (float) $record->original_fare, 2 ); ?></strong>
                        </div>
                        <div class="breakdown-row">
                            <span>Airline Penalty:</span>
                            <strong class="font-mono color-rose">-৳<?php echo number_format( (float) $record->airline_penalty, 2 ); ?></strong>
                        </div>
                        <div class="breakdown-row">
                            <span>Agency Service Fee:</span>
                            <strong class="font-mono color-emerald">-৳<?php echo number_format( (float) $record->agency_service_charge, 2 ); ?></strong>
                        </div>
                        <?php if ( $record->type === 'Reissue' && $record->fare_difference > 0 ) : ?>
                            <div class="breakdown-row">
                                <span>Fare Difference:</span>
                                <strong class="font-mono color-blue">+৳<?php echo number_format( (float) $record->fare_difference, 2 ); ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="settle-footer-strip">
                        <span class="dashicons dashicons-shield"></span> Settled via: <?php echo esc_html( $record->settlement_method ); ?>
                    </div>
                </div>

                <!-- Metadata Specification Details -->
                <div class="ifs-panel-card" style="margin-bottom: 0;">
                    <div class="ifs-card-header">
                        <div>
                            <h3 class="ifs-card-title">Flight Manifest &amp; Transaction Details</h3>
                            <p class="ifs-card-desc">Recorded timestamp: <?php echo date( 'l, d F Y, h:i A', strtotime( $record->created_at ) ); ?></p>
                        </div>
                    </div>
                    <div class="ifs-grid-3">
                        <div class="spec-item">
                            <span class="spec-title">Passenger Name</span>
                            <strong class="spec-data"><?php echo esc_html( $record->customer_name ?: 'Direct Passenger' ); ?></strong>
                        </div>
                        <div class="spec-item">
                            <span class="spec-title">Channel / Agent</span>
                            <strong class="spec-data"><?php echo esc_html( $record->agency_name ?: 'Direct Retail Client' ); ?></strong>
                        </div>
                        <div class="spec-item">
                            <span class="spec-title">Original E-Ticket</span>
                            <strong class="spec-data font-mono"><?php echo esc_html( $record->ticket_no ?: 'N/A' ); ?></strong>
                        </div>
                        <?php if ( ! empty( $record->new_ticket_no ) ) : ?>
                            <div class="spec-item">
                                <span class="spec-title">New Reissued Ticket</span>
                                <strong class="spec-data font-mono color-blue"><?php echo esc_html( $record->new_ticket_no ); ?></strong>
                            </div>
                        <?php endif; ?>
                        <?php if ( ! empty( $record->new_pnr ) ) : ?>
                            <div class="spec-item">
                                <span class="spec-title">New Reissued PNR</span>
                                <strong class="spec-data font-mono color-blue"><?php echo esc_html( $record->new_pnr ); ?></strong>
                            </div>
                        <?php endif; ?>
                        <div class="spec-item col-span-3">
                            <span class="spec-title">Operational Remarks</span>
                            <p style="margin: 4px 0 0 0; font-size: 13px; color: #334155;"><?php echo nl2br( esc_html( $record->remarks ?: 'No notes provided.' ) ); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /* =========================================================================
       4. PROCESS / CREATE / EDIT FORM (sub=process OR sub=edit)
       ========================================================================= */
    elseif ( $sub_action === 'process' || $sub_action === 'edit' ) {
        $edit_id  = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
        $is_edit  = ( $edit_id > 0 );
        $edit_row = false;

        if ( $is_edit ) {
            $edit_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_refunds WHERE id = %d", $edit_id ) );
        }

        $active_tickets = $wpdb->get_results( "
            SELECT t.id, t.customer_id, t.agent_id, t.supplier_id, t.pnr, t.ticket_no, t.airline, t.sector, t.sell_price, c.full_name 
            FROM $table_tickets t
            LEFT JOIN $table_customers c ON t.customer_id = c.id
            WHERE t.status = 'Issued' OR t.id = " . ( $edit_row ? intval( $edit_row->ticket_id ) : 0 ) . "
            ORDER BY t.id DESC LIMIT 100
        " );

        $val_type       = $is_edit ? esc_attr( $edit_row->type ) : 'Refund';
        $val_tkt_id     = $is_edit ? intval( $edit_row->ticket_id ) : 0;
        $val_pnr        = $is_edit ? esc_attr( $edit_row->pnr ) : '';
        $val_tkt_no     = $is_edit ? esc_attr( $edit_row->ticket_no ) : '';
        $val_new_pnr    = $is_edit ? esc_attr( $edit_row->new_pnr ) : '';
        $val_new_tkt    = $is_edit ? esc_attr( $edit_row->new_ticket_no ) : '';
        $val_fare       = $is_edit ? floatval( $edit_row->original_fare ) : '';
        $val_penalty    = $is_edit ? floatval( $edit_row->airline_penalty ) : '';
        $val_fee        = $is_edit ? floatval( $edit_row->agency_service_charge ) : '';
        $val_diff       = $is_edit ? floatval( $edit_row->fare_difference ) : '';
        $val_refund     = $is_edit ? floatval( $edit_row->refund_amount ) : '';
        $val_method     = $is_edit ? esc_attr( $edit_row->settlement_method ) : 'Bank Transfer';
        $val_remarks    = $is_edit ? esc_textarea( $edit_row->remarks ) : '';
        $val_customer   = $is_edit ? intval( $edit_row->customer_id ) : 0;
        $val_agent      = $is_edit ? intval( $edit_row->agent_id ) : 0;
        $val_supplier   = $is_edit ? intval( $edit_row->supplier_id ) : 0;
        ?>
        <form method="post" action="" class="ifs-split-refund-editor">
            <?php wp_nonce_field( 'ifs_refund_action', 'ifs_refund_nonce' ); ?>
            <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">

            <div class="ifs-refund-form-body">
                
                <!-- Section 1: Ticket Selection & Operation Type -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">01</div>
                        <div>
                            <h3 class="ifs-card-title"><?php echo $is_edit ? 'Edit Record Details' : 'Select E-Ticket & Operation Mode'; ?></h3>
                            <p class="ifs-card-desc">Choose from issued tickets or input manual GDS reference numbers</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <div class="ifs-field-block col-span-3">
                            <label class="ifs-field-label" for="inp_ticket_id">Select From Issued Tickets (Auto-Fill Manifest)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-tickets-alt field-icon"></span>
                                <select name="ticket_id" id="inp_ticket_id" class="ifs-input-field">
                                    <option value="0">-- Enter Manually or Select Issued Ticket --</option>
                                    <?php foreach ( $active_tickets as $t ) : ?>
                                        <option value="<?php echo $t->id; ?>" 
                                                data-pnr="<?php echo esc_attr( $t->pnr ); ?>"
                                                data-tkt="<?php echo esc_attr( $t->ticket_no ); ?>"
                                                data-fare="<?php echo esc_attr( $t->sell_price ); ?>"
                                                data-customer="<?php echo esc_attr( $t->customer_id ); ?>"
                                                data-agent="<?php echo esc_attr( $t->agent_id ); ?>"
                                                data-supplier="<?php echo esc_attr( $t->supplier_id ); ?>"
                                                <?php selected( $val_tkt_id, $t->id ); ?>>
                                            PNR: <?php echo esc_html( $t->pnr ); ?> &mdash; <?php echo esc_html( $t->full_name ); ?> (<?php echo esc_html( $t->airline ); ?>: <?php echo esc_html( $t->sector ); ?> | Fare: ৳<?php echo number_format( (float) $t->sell_price, 2 ); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <input type="hidden" name="customer_id" id="inp_customer_id" value="<?php echo $val_customer; ?>">
                            <input type="hidden" name="agent_id" id="inp_agent_id" value="<?php echo $val_agent; ?>">
                            <input type="hidden" name="supplier_id" id="inp_supplier_id" value="<?php echo $val_supplier; ?>">
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_process_type">Operation Type <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-image-rotate field-icon"></span>
                                <select name="process_type" id="inp_process_type" class="ifs-input-field">
                                    <option value="Refund" <?php selected( $val_type, 'Refund' ); ?>>Refund Ticket</option>
                                    <option value="Reissue" <?php selected( $val_type, 'Reissue' ); ?>>Date Change / Reissue</option>
                                    <option value="Void" <?php selected( $val_type, 'Void' ); ?>>Void (Same Day Cancellation)</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_pnr">GDS PNR Ref <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-randomize field-icon"></span>
                                <input type="text" name="pnr" id="inp_pnr" required 
                                       value="<?php echo $val_pnr; ?>"
                                       placeholder="e.g. 7X9K21" class="ifs-input-field uppercase font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_ticket_no">Original Ticket Number</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-nametag field-icon"></span>
                                <input type="text" name="ticket_no" id="inp_ticket_no" 
                                       value="<?php echo $val_tkt_no; ?>"
                                       placeholder="077-1234567890" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <!-- Reissue Specific Fields -->
                        <div class="ifs-field-block" id="wrap_new_pnr" style="<?php echo ( $val_type !== 'Reissue' ) ? 'display:none;' : ''; ?>">
                            <label class="ifs-field-label" for="inp_new_pnr">New Reissue PNR</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-randomize field-icon"></span>
                                <input type="text" name="new_pnr" id="inp_new_pnr" value="<?php echo $val_new_pnr; ?>" placeholder="Leave blank if unchanged" class="ifs-input-field uppercase font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-2" id="wrap_new_ticket" style="<?php echo ( $val_type !== 'Reissue' ) ? 'display:none;' : ''; ?>">
                            <label class="ifs-field-label" for="inp_new_tkt">New Reissue E-Ticket No</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-tickets-alt field-icon"></span>
                                <input type="text" name="new_ticket_no" id="inp_new_tkt" value="<?php echo $val_new_tkt; ?>" placeholder="New 13-digit ticket number" class="ifs-input-field font-mono">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Penalty & Financial Settlement Calculation -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">02</div>
                        <div>
                            <h3 class="ifs-card-title">Commercial Penalty &amp; Settlement Breakdown</h3>
                            <p class="ifs-card-desc">Calculate airline deductions, agency fee adjustments, and net balance</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_original_fare">Original Ticket Price (৳) <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-money-alt field-icon"></span>
                                <input type="number" step="0.01" name="original_fare" id="inp_original_fare" required 
                                       value="<?php echo $val_fare; ?>" placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_penalty">Airline Penalty Charge (৳)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-dismiss field-icon"></span>
                                <input type="number" step="0.01" name="airline_penalty" id="inp_penalty" 
                                       value="<?php echo $val_penalty; ?>" placeholder="0.00" class="ifs-input-field font-mono color-rose font-bold">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_service_fee">Agency Service Fee (৳)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-admin-generic field-icon"></span>
                                <input type="number" step="0.01" name="service_charge" id="inp_service_fee" 
                                       value="<?php echo $val_fee; ?>" placeholder="0.00" class="ifs-input-field font-mono color-emerald font-bold">
                            </div>
                        </div>

                        <div class="ifs-field-block" id="wrap_fare_diff" style="<?php echo ( $val_type !== 'Reissue' ) ? 'display:none;' : ''; ?>">
                            <label class="ifs-field-label" for="inp_fare_diff">New Fare Difference (৳)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-chart-line field-icon"></span>
                                <input type="number" step="0.01" name="fare_difference" id="inp_fare_diff" 
                                       value="<?php echo $val_diff; ?>" placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_settle_method">Settlement Method</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-vault field-icon"></span>
                                <select name="settlement_method" id="inp_settle_method" class="ifs-input-field">
                                    <option value="Bank Transfer" <?php selected( $val_method, 'Bank Transfer' ); ?>>Bank Transfer (BEFTN/RTGS)</option>
                                    <option value="Cash" <?php selected( $val_method, 'Cash' ); ?>>Cash at Counter</option>
                                    <option value="bKash / MFS" <?php selected( $val_method, 'bKash / MFS' ); ?>>bKash / Nagad / Rocket</option>
                                    <option value="Agent Credit Balance" <?php selected( $val_method, 'Agent Credit Balance' ); ?>>Agent Account Ledger Adjustment</option>
                                    <option value="Cheque" <?php selected( $val_method, 'Cheque' ); ?>>Cheque</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-2" id="wrap_settlement_box">
                            <label class="ifs-field-label" id="lbl_refund_amount"><?php echo ( $val_type === 'Reissue' ) ? 'Total Reissue Charge to Collect (৳) *' : 'Net Refund to Client (৳) *'; ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-calculator field-icon"></span>
                                <input type="number" step="0.01" name="refund_amount" id="inp_refund_amount" required 
                                       value="<?php echo $val_refund; ?>" placeholder="0.00" class="ifs-input-field font-mono font-bold color-emerald">
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-3">
                            <label class="ifs-field-label" for="inp_remarks">Cancellation / Reissue Reason &amp; Notes</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-edit field-icon"></span>
                                <textarea name="remarks" id="inp_remarks" rows="2" class="ifs-input-field" 
                                          placeholder="Passenger medical emergency, airline schedule change, date change details..."><?php echo $val_remarks; ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ifs-action-strip">
                    <a href="<?php echo esc_url( $base_url . '&sub=list' ); ?>" class="ifs-btn-back">
                        <span class="dashicons dashicons-arrow-left-alt"></span> Cancel
                    </a>
                    <button type="submit" name="ifs_refund_submit" class="ifs-btn-primary">
                        <span class="dashicons dashicons-saved"></span> <?php echo $is_edit ? 'Update Record' : 'Execute & Record Operation'; ?>
                    </button>
                </div>
            </div>

            <!-- Right Sidebar: Interactive Live Settlement Voucher -->
            <div class="ifs-preview-sidebar">
                <div class="ifs-preview-sticky">
                    <div class="ifs-card-preview-header">
                        <span class="dashicons dashicons-calculator"></span> Live Settlement Voucher Preview
                    </div>

                    <div class="ifs-settlement-card">
                        <div class="settle-top-strip">
                            <span class="settle-brand">IFS POST-TICKETING</span>
                            <span class="settle-type" id="prev_type"><?php echo strtoupper( $val_type ); ?></span>
                        </div>

                        <div class="settle-hero-box">
                            <span class="settle-lbl" id="prev_amount_label"><?php echo ( $val_type === 'Reissue' ) ? 'TOTAL CHARGE TO COLLECT' : 'NET REFUNDABLE TO CLIENT'; ?></span>
                            <h3 class="settle-amount" id="prev_amount_display">৳<?php echo number_format( (float) $val_refund, 2 ); ?></h3>
                            <span class="settle-pnr font-mono" id="prev_pnr_display">PNR: <?php echo esc_html( $val_pnr ?: '------' ); ?></span>
                        </div>

                        <div class="settle-breakdown-list">
                            <div class="breakdown-row">
                                <span>Original Ticket Fare:</span>
                                <strong id="prev_fare" class="font-mono">৳<?php echo number_format( (float) $val_fare, 2 ); ?></strong>
                            </div>
                            <div class="breakdown-row">
                                <span>Airline Penalty:</span>
                                <strong id="prev_penalty" class="font-mono color-rose">-৳<?php echo number_format( (float) $val_penalty, 2 ); ?></strong>
                            </div>
                            <div class="breakdown-row">
                                <span>Agency Service Fee:</span>
                                <strong id="prev_fee" class="font-mono color-rose">-৳<?php echo number_format( (float) $val_fee, 2 ); ?></strong>
                            </div>
                            <div class="breakdown-row" id="prev_row_diff" style="<?php echo ( $val_type !== 'Reissue' ) ? 'display:none;' : ''; ?>">
                                <span>Fare Difference:</span>
                                <strong id="prev_diff" class="font-mono color-blue">+৳<?php echo number_format( (float) $val_diff, 2 ); ?></strong>
                            </div>
                        </div>

                        <div class="settle-footer-strip">
                            <span class="dashicons dashicons-shield"></span> Automatic General Ledger &amp; Agent Ledger Sync
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
            const selTicket    = document.getElementById('inp_ticket_id');
            const selType      = document.getElementById('inp_process_type');
            const inpPnr       = document.getElementById('inp_pnr');
            const inpTicketNo  = document.getElementById('inp_ticket_no');
            const inpHiddenCus = document.getElementById('inp_customer_id');
            const inpHiddenAg  = document.getElementById('inp_agent_id');
            const inpHiddenSup = document.getElementById('inp_supplier_id');
            
            const inpFare      = document.getElementById('inp_original_fare');
            const inpPenalty   = document.getElementById('inp_penalty');
            const inpFee       = document.getElementById('inp_service_fee');
            const inpFareDiff  = document.getElementById('inp_fare_diff');
            const inpRefund    = document.getElementById('inp_refund_amount');

            const wrapNewPnr   = document.getElementById('wrap_new_pnr');
            const wrapNewTkt   = document.getElementById('wrap_new_ticket');
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
                        if (inpHiddenCus) inpHiddenCus.value = opt.getAttribute('data-customer') || '0';
                        if (inpHiddenAg)  inpHiddenAg.value  = opt.getAttribute('data-agent') || '0';
                        if (inpHiddenSup) inpHiddenSup.value = opt.getAttribute('data-supplier') || '0';
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
                    if (wrapNewPnr)   wrapNewPnr.style.display   = 'none';
                    if (wrapNewTkt)   wrapNewTkt.style.display   = 'none';
                    if (prevRowDiff)  prevRowDiff.style.display  = 'none';
                    if (lblRefund)    lblRefund.textContent      = 'Net Refund to Client (৳) *';
                    if (prevAmountLbl) prevAmountLbl.textContent = 'NET REFUNDABLE TO CLIENT';

                    const netRefund = Math.max(0, fare - penalty - fee);
                    if (inpRefund) inpRefund.value = netRefund.toFixed(2);
                    if (prevAmountDisp) {
                        prevAmountDisp.textContent = '৳' + netRefund.toLocaleString('en-US', { minimumFractionDigits: 2 });
                        prevAmountDisp.style.color = '#86efac';
                    }
                } else if (type === 'Reissue') {
                    if (wrapFareDiff) wrapFareDiff.style.display = 'flex';
                    if (wrapNewPnr)   wrapNewPnr.style.display   = 'flex';
                    if (wrapNewTkt)   wrapNewTkt.style.display   = 'flex';
                    if (prevRowDiff)  prevRowDiff.style.display  = 'flex';
                    if (prevDiff)     prevDiff.textContent       = '+৳' + diff.toLocaleString('en-US', { minimumFractionDigits: 2 });
                    if (lblRefund)    lblRefund.textContent      = 'Total Reissue Charge to Collect (৳) *';
                    if (prevAmountLbl) prevAmountLbl.textContent = 'TOTAL CHARGE TO COLLECT';

                    const totalCharge = penalty + fee + diff;
                    if (inpRefund) inpRefund.value = totalCharge.toFixed(2);
                    if (prevAmountDisp) {
                        prevAmountDisp.textContent = '৳' + totalCharge.toLocaleString('en-US', { minimumFractionDigits: 2 });
                        prevAmountDisp.style.color = '#fde047';
                    }
                } else if (type === 'Void') {
                    if (wrapFareDiff) wrapFareDiff.style.display = 'none';
                    if (wrapNewPnr)   wrapNewPnr.style.display   = 'none';
                    if (wrapNewTkt)   wrapNewTkt.style.display   = 'none';
                    if (prevRowDiff)  prevRowDiff.style.display  = 'none';
                    if (lblRefund)    lblRefund.textContent      = 'Net Void Refund (৳) *';
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

    /* =========================================================================
       5. DEFAULT DATA TABLE LIST (sub=list)
       ========================================================================= */
    else {
        $records = $wpdb->get_results( "
            SELECT r.*, c.full_name as customer_name, a.agency_name
            FROM $table_refunds r
            LEFT JOIN $table_customers c ON r.customer_id = c.id
            LEFT JOIN $table_agents a ON r.agent_id = a.id
            ORDER BY r.id DESC
        " );
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
                            <th>Date &amp; Time</th>
                            <th>Operation</th>
                            <th>Passenger &amp; Channel</th>
                            <th>PNR Ref</th>
                            <th>Ticket No</th>
                            <th style="text-align: right;">Penalty (৳)</th>
                            <th style="text-align: right;">Service Fee (৳)</th>
                            <th style="text-align: right;">Net Adjustment (৳)</th>
                            <th>Settlement Method</th>
                            <th style="text-align: right; width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $records ) : foreach ( $records as $rec ) : 
                            $badge_class = 'badge-refund';
                            if ( $rec->type === 'Reissue' ) $badge_class = 'badge-reissue';
                            elseif ( $rec->type === 'Void' ) $badge_class = 'badge-void';
                            $channel_lbl = ! empty( $rec->agency_name ) ? '<span class="agent-submeta"><span class="dashicons dashicons-groups"></span> ' . esc_html( $rec->agency_name ) . '</span>' : '<span class="direct-submeta">Direct Client</span>';
                        ?>
                            <tr>
                                <td><span class="ifs-id-badge">#REF-<?php echo str_pad( (string) $rec->id, 5, '0', STR_PAD_LEFT ); ?></span></td>
                                <td><?php echo date( 'd M Y, h:i A', strtotime( $rec->created_at ) ); ?></td>
                                <td><span class="ifs-operation-badge <?php echo esc_attr( $badge_class ); ?>"><?php echo esc_html( $rec->type ); ?></span></td>
                                <td>
                                    <div style="font-weight: 700; color: #0f172a;"><?php echo esc_html( $rec->customer_name ?: 'Direct Passenger' ); ?></div>
                                    <div><?php echo $channel_lbl; ?></div>
                                </td>
                                <td><span class="ifs-pnr-pill font-mono"><?php echo esc_html( $rec->pnr ); ?></span></td>
                                <td class="font-mono"><?php echo esc_html( $rec->ticket_no ?: '-' ); ?></td>
                                <td style="text-align: right; color: #dc2626; font-family: ui-monospace, monospace; font-weight: 700;">৳<?php echo number_format( (float) $rec->airline_penalty, 2 ); ?></td>
                                <td style="text-align: right; color: #059669; font-family: ui-monospace, monospace; font-weight: 700;">৳<?php echo number_format( (float) $rec->agency_service_charge, 2 ); ?></td>
                                <td style="text-align: right; font-weight: 800; font-family: ui-monospace, monospace; font-size: 13.5px;" class="<?php echo ( $rec->type === 'Reissue' ) ? 'color-amber' : 'color-emerald'; ?>">
                                    ৳<?php echo number_format( (float) $rec->refund_amount, 2 ); ?>
                                </td>
                                <td><span class="ifs-method-tag"><?php echo esc_html( $rec->settlement_method ?: 'Bank Transfer' ); ?></span></td>
                                <td style="text-align: right;">
                                    <div class="ifs-action-pills">
                                        <a href="<?php echo esc_url( $base_url . '&sub=view&id=' . $rec->id ); ?>" class="ifs-action-pill view" title="View Settlement Voucher">
                                            <span class="dashicons dashicons-visibility"></span> View
                                        </a>
                                        <a href="<?php echo esc_url( $base_url . '&sub=edit&id=' . $rec->id ); ?>" class="ifs-action-pill edit" title="Edit Record">
                                            <span class="dashicons dashicons-edit"></span> Edit
                                        </a>
                                        <a href="<?php echo wp_nonce_url( $base_url . '&sub=delete&id=' . $rec->id, 'delete_refund_' . $rec->id ); ?>" 
                                           class="ifs-action-pill delete" 
                                           onclick="return confirm('Are you sure you want to permanently delete this audit record?');" 
                                           title="Delete Record">
                                            <span class="dashicons dashicons-trash"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr>
                                <td colspan="11" class="ifs-empty-table">
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
                            "searchPlaceholder": "Search PNR, Ticket No, Type, Passenger...",
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
        .ifs-refund-workspace {
            max-width: 1420px;
            margin: 0 auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #0f172a;
        }

        /* ----------------------------------------------------
           TOAST NOTIFICATIONS
        ---------------------------------------------------- */
        .ifs-toast {
            padding: 14px 20px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 22px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        .ifs-toast.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .ifs-toast.danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .ifs-toast .dashicons { font-size: 18px; width: 18px; height: 18px; }

        /* ----------------------------------------------------
           HEADER CARDS & STATS
        ---------------------------------------------------- */
        .ifs-pro-tab-wrapper { margin-bottom: 24px; }
        .ifs-pro-header-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 24px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04);
            margin-bottom: 18px;
        }
        .ifs-pro-identity { display: flex; align-items: center; gap: 18px; }
        .ifs-pro-icon-glow {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            box-shadow: 0 8px 18px -4px rgba(225, 29, 72, 0.35);
            flex-shrink: 0;
        }
        .ifs-pro-icon-glow .dashicons { font-size: 28px; width: 28px; height: 28px; }
        .ifs-pro-badge-group { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
        .ifs-status-dot { width: 6px; height: 6px; border-radius: 50%; background: #e11d48; display: inline-block; }
        .ifs-meta-tag { font-size: 10.5px; font-weight: 700; text-transform: uppercase; color: #64748b; }
        .ifs-meta-tag-rose { font-size: 10px; font-weight: 700; text-transform: uppercase; background: #ffe4e6; color: #be123c; padding: 2px 7px; border-radius: 4px; }
        .ifs-pro-heading { margin: 0; font-size: 22px; font-weight: 800; color: #0f172a; letter-spacing: -0.3px; }
        .ifs-pro-caption { margin: 3px 0 0 0; font-size: 13.5px; color: #64748b; }
        .ifs-pro-stats-strip { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .ifs-stat-pill { background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 16px; border-radius: 10px; display: flex; flex-direction: column; gap: 2px; min-width: 110px; }
        .ifs-stat-lbl { font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .ifs-stat-num { font-size: 16px; font-weight: 800; font-family: ui-monospace, monospace; }
        .color-dark { color: #0f172a; }
        .color-emerald { color: #059669; }
        .color-rose { color: #e11d48; }
        .color-amber { color: #d97706; }

        .ifs-pro-nav-container { display: flex; align-items: center; }
        .ifs-pro-nav-bar { background: #f1f5f9; border: 1px solid #e2e8f0; padding: 5px; border-radius: 12px; display: inline-flex; align-items: center; gap: 6px; max-width: 100%; overflow-x: auto; }
        .ifs-pro-nav-btn { display: inline-flex; align-items: center; gap: 9px; padding: 10px 20px; font-size: 13.5px; font-weight: 600; color: #64748b; text-decoration: none; border-radius: 9px; transition: all 0.2s ease; cursor: pointer; white-space: nowrap; border: 1px solid transparent; }
        .ifs-pro-nav-btn:hover { color: #0f172a; background: rgba(255, 255, 255, 0.65); }
        .ifs-pro-nav-btn.active-tab { background: #ffffff; color: #be123c; font-weight: 700; border: 1px solid rgba(190, 18, 60, 0.1); box-shadow: 0 4px 12px rgba(190, 18, 60, 0.08); }
        .ifs-pro-counter { background: #e2e8f0; color: #475569; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 20px; }
        .ifs-pro-nav-btn.active-tab .ifs-pro-counter { background: #be123c; color: #ffffff; }

        /* ----------------------------------------------------
           SPLIT EDITOR & PANELS
        ---------------------------------------------------- */
        .ifs-split-refund-editor {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 28px;
            align-items: flex-start;
        }
        @media (max-width: 1140px) {
            .ifs-split-refund-editor { grid-template-columns: 1fr; }
        }

        .ifs-panel-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 26px;
            margin-bottom: 22px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
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
            background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
            color: #ffffff;
            font-weight: 800;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(225, 29, 72, 0.2);
            flex-shrink: 0;
        }
        .ifs-card-title { margin: 0; font-size: 15.5px; font-weight: 800; color: #0f172a; }
        .ifs-card-desc { margin: 2px 0 0 0; font-size: 12.5px; color: #64748b; }

        .ifs-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px 18px;
        }
        .col-span-2 { grid-column: span 2; }
        .col-span-3 { grid-column: span 3; }
        @media (max-width: 768px) {
            .ifs-grid-3 { grid-template-columns: 1fr; }
            .col-span-2, .col-span-3 { grid-column: span 1; }
        }

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
        .color-blue { color: #0284c7 !important; }

        .agent-submeta { font-size: 10.5px; color: #4338ca; font-weight: 600; display: inline-flex; align-items: center; gap: 3px; }
        .agent-submeta .dashicons { font-size: 11px; width: 11px; height: 11px; }
        .direct-submeta { font-size: 10.5px; color: #059669; font-weight: 600; }
        .ifs-method-tag { font-size: 10.5px; background: #f1f5f9; padding: 2px 7px; border-radius: 4px; color: #475569; font-weight: 600; }

        .ifs-action-strip {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 24px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
        }
        .ifs-btn-back {
            color: #64748b;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 8px 14px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .ifs-btn-back:hover { color: #0f172a; background: #e2e8f0; }
        .ifs-btn-primary {
            background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
            color: #ffffff !important;
            border: none;
            padding: 11px 26px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(225, 29, 72, 0.25);
            transition: all 0.2s ease;
        }
        .ifs-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(225, 29, 72, 0.35);
        }

        /* ----------------------------------------------------
           INTERACTIVE SETTLEMENT VOUCHER CARD (PREVIEW & VIEW)
        ---------------------------------------------------- */
        .ifs-preview-sticky { position: sticky; top: 30px; }
        .ifs-card-preview-header { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }

        .ifs-settlement-card {
            background: linear-gradient(145deg, #4c0519 0%, #881337 60%, #be123c 100%);
            border-radius: 16px;
            padding: 24px;
            color: #ffffff;
            box-shadow: 0 16px 36px -6px rgba(190, 18, 60, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.15);
            position: relative;
            overflow: hidden;
        }
        .settle-top-strip { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
        .settle-brand { font-size: 9.5px; font-weight: 800; letter-spacing: 1px; color: #fecdd3; }
        .settle-type { background: rgba(255, 255, 255, 0.2); padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 800; letter-spacing: 0.5px; }

        .settle-hero-box { text-align: center; background: rgba(0, 0, 0, 0.2); padding: 16px; border-radius: 12px; margin-bottom: 18px; }
        .settle-lbl { font-size: 9.5px; font-weight: 700; color: #fecdd3; letter-spacing: 0.5px; display: block; margin-bottom: 4px; }
        .settle-amount { margin: 0; font-size: 26px; font-weight: 900; color: #86efac; font-family: ui-monospace, monospace; }
        .settle-pnr { font-size: 12px; color: #bae6fd; margin-top: 4px; display: block; }

        .settle-breakdown-list { display: flex; flex-direction: column; gap: 8px; padding-bottom: 14px; border-bottom: 1px dashed rgba(255, 255, 255, 0.2); margin-bottom: 14px; font-size: 12.5px; }
        .breakdown-row { display: flex; justify-content: space-between; color: #fecdd3; }
        .breakdown-row strong { color: #ffffff; }

        .settle-footer-strip { font-size: 10px; color: #fecdd3; display: flex; align-items: center; gap: 6px; }

        .ifs-tip-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-top: 18px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02); }
        .tip-title { font-size: 12px; font-weight: 800; color: #be123c; display: flex; align-items: center; gap: 6px; margin-bottom: 8px; }
        .tip-list { margin: 0; padding-left: 18px; font-size: 11.5px; color: #64748b; line-height: 1.6; }

        /* ----------------------------------------------------
           DATA TABLE & CARDS
        ---------------------------------------------------- */
        .ifs-table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); overflow: hidden; }
        .ifs-table-top-bar { padding: 22px 26px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; gap: 15px; }
        .ifs-table-heading { margin: 0; font-size: 17px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        .ifs-table-heading .dashicons { color: #be123c; font-size: 20px; width: 20px; height: 20px; }
        .ifs-table-caption { margin: 3px 0 0 0; font-size: 13px; color: #64748b; }

        .ifs-table-responsive-wrapper { padding: 15px 24px 24px 24px; overflow-x: auto; }
        .ifs-pro-datatable { width: 100%; border-collapse: collapse; font-size: 13px; }
        .ifs-pro-datatable thead th { background: #f8fafc; color: #475569; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px 14px; border-bottom: 2px solid #e2e8f0; text-align: left; }
        .ifs-pro-datatable tbody td { padding: 13px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #334155; }

        .ifs-id-badge { background: #f1f5f9; color: #475569; font-family: ui-monospace, monospace; font-weight: 700; font-size: 11px; padding: 3px 6px; border-radius: 6px; }
        .ifs-pnr-pill { background: #fee2e2; color: #991b1b; font-weight: 800; font-size: 12px; padding: 2px 6px; border-radius: 4px; }
        .ifs-operation-badge { font-size: 10.5px; font-weight: 800; padding: 2px 8px; border-radius: 4px; text-transform: uppercase; }
        .badge-refund { background: #fee2e2; color: #991b1b; }
        .badge-reissue { background: #e0f2fe; color: #0369a1; }
        .badge-void { background: #fef3c7; color: #92400e; }

        .ifs-action-pills { display: flex; gap: 4px; justify-content: flex-end; align-items: center; }
        .ifs-action-pill { display: inline-flex; align-items: center; gap: 3px; padding: 4px 8px; border-radius: 6px; font-size: 11.5px; font-weight: 600; text-decoration: none; transition: all 0.15s ease; border: 1px solid transparent; white-space: nowrap; }
        .ifs-action-pill.view { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }
        .ifs-action-pill.view:hover { background: #e2e8f0; color: #0f172a; }
        .ifs-action-pill.edit { background: #eff6ff; color: #2563eb; border-color: #dbeafe; }
        .ifs-action-pill.edit:hover { background: #dbeafe; color: #1d4ed8; }
        .ifs-action-pill.delete { background: #fef2f2; color: #dc2626; border-color: #fee2e2; padding: 4px 6px; }
        .ifs-action-pill.delete:hover { background: #fee2e2; color: #b91c1c; }

        /* ----------------------------------------------------
           SINGLE VOUCHER VIEW CONTAINER
        ---------------------------------------------------- */
        .ifs-single-view-container { display: flex; flex-direction: column; gap: 20px; }
        .ifs-view-top-bar { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); }
        .view-title-group { display: flex; align-items: center; gap: 12px; }
        .ifs-voucher-split-grid { display: grid; grid-template-columns: 400px 1fr; gap: 24px; align-items: flex-start; }
        @media (max-width: 1140px) { .ifs-voucher-split-grid { grid-template-columns: 1fr; } }
        .spec-item { display: flex; flex-direction: column; gap: 3px; background: #f8fafc; padding: 12px 16px; border-radius: 10px; border: 1px solid #e2e8f0; }
        .spec-title { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .spec-data { font-size: 14px; font-weight: 800; color: #0f172a; }

        .ifs-empty-table { text-align: center; padding: 50px 20px !important; }
        .ifs-empty-state .dashicons { font-size: 44px; width: 44px; height: 44px; color: #cbd5e1; margin-bottom: 10px; }
        .ifs-empty-state h4 { margin: 0 0 4px 0; font-size: 16px; font-weight: 700; color: #334155; }
        .ifs-empty-state p { margin: 0; color: #94a3b8; font-size: 13px; }
    </style>
    <?php
}