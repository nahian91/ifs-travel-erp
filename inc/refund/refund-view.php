<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_refund_view_page' ) ) {
    /**
     * Enterprise Ultra-Modern Settlement Voucher & Printable After-Sales Dossier
     * Features: Official Voucher Header, Two-Way Ledger Breakdown, Ticket Metadata Sync,
     * Dual Authorization Signatures, BSP Compliance Fields & Optimized 1-Page Print Stylesheet.
     */
    function ifs_terp_refund_view_page() {
        global $wpdb;
        $table_refunds   = $wpdb->prefix . 'iterp_refund_reissue';
        $table_customers = $wpdb->prefix . 'iterp_customers';
        $table_agents    = $wpdb->prefix . 'iterp_agents';
        $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
        $table_tickets   = $wpdb->prefix . 'iterp_tickets';
        $base_url        = admin_url( 'admin.php?page=ifs_travel_erp&tab=refund_reissue' );

        $view_id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
        $record  = $wpdb->get_row( $wpdb->prepare( "
            SELECT r.*, 
                   c.full_name AS customer_name, c.mobile AS customer_mobile, c.passport_no AS customer_passport,
                   a.agency_name, a.contact_person AS agent_contact,
                   s.supplier_name,
                   t.airline, t.sector, t.travel_date, t.pnr AS ticket_pnr, t.flight_no
            FROM {$table_refunds} r
            LEFT JOIN {$table_customers} c ON r.customer_id = c.id
            LEFT JOIN {$table_agents} a ON r.agent_id = a.id
            LEFT JOIN {$table_suppliers} s ON r.supplier_id = s.id
            LEFT JOIN {$table_tickets} t ON r.ticket_id = t.id
            WHERE r.id = %d
        ", $view_id ) );

        if ( ! $record ) {
            echo '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> ' . esc_html__( 'After-sales operation record not found.', 'ifs-travel-erp' ) . '</div>';
            return;
        }

        // Operation type badges
        $badge_class = 'badge-refund';
        if ( 'Reissue' === $record->type ) {
            $badge_class = 'badge-reissue';
        } elseif ( 'Void' === $record->type ) {
            $badge_class = 'badge-void';
        }

        // Settlement Status badges
        $status_class = 'status-completed';
        $st = strtolower( (string) ( $record->status ?? 'completed' ) );
        if ( 'pending' === $st ) {
            $status_class = 'status-pending';
        } elseif ( 'rejected' === $st ) {
            $status_class = 'status-rejected';
        }

        // Calculations
        $orig_fare   = (float) ( $record->original_fare ?? 0 );
        $penalty     = (float) ( $record->airline_penalty ?? 0 );
        $service_fee = (float) ( $record->agency_service_charge ?? 0 );
        $fare_diff   = (float) ( $record->fare_difference ?? 0 );
        $settled_val = (float) ( $record->refund_amount ?? 0 );

        $pax_name = ! empty( $record->customer_name ) ? $record->customer_name : __( 'Direct Retail Passenger', 'ifs-travel-erp' );
        $channel  = ! empty( $record->agency_name ) ? $record->agency_name : __( 'Direct Retail Customer', 'ifs-travel-erp' );
        $vendor   = ! empty( $record->supplier_name ) ? $record->supplier_name : ( ! empty( $record->airline ) ? $record->airline : __( 'Direct IATA Stock', 'ifs-travel-erp' ) );
        ?>

        <div class="ifs-refund-view-workspace">
            <!-- Top Navigation & Action Strip -->
            <div class="ifs-view-header-strip">
                <div class="ifs-header-identity">
                    <a href="<?php echo esc_url( add_query_arg( 'sub', 'list', $base_url ) ); ?>" class="ifs-back-round-btn" title="<?php esc_attr_e( 'Back to Log', 'ifs-travel-erp' ); ?>">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                    </a>
                    <div>
                        <div class="ifs-badge-row">
                            <span class="ifs-id-pill">#OP-<?php echo esc_html( str_pad( (string) $record->id, 5, '0', STR_PAD_LEFT ) ); ?></span>
                            <span class="badge <?php echo esc_attr( $badge_class ); ?>"><?php echo esc_html( $record->type ); ?></span>
                            <span class="ifs-status-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $record->status ?? 'Completed' ); ?></span>
                        </div>
                        <h2 class="ifs-view-name">
                            <?php echo esc_html( $record->type ); ?> &mdash; PNR: <?php echo esc_html( $record->pnr ); ?> 
                            <?php if ( ! empty( $record->sector ) ) : ?>
                                <span style="font-size: 15px; color: #64748b; font-weight: 600;">(<?php echo esc_html( $record->sector ); ?>)</span>
                            <?php endif; ?>
                        </h2>
                    </div>
                </div>

                <div class="ifs-header-actions">
                    <button type="button" onclick="window.print();" class="ifs-btn-print">
                        <span class="dashicons dashicons-printer"></span> <?php esc_html_e( 'Print Official Voucher', 'ifs-travel-erp' ); ?>
                    </button>
                    <a href="<?php echo esc_url( add_query_arg( array( 'sub' => 'edit', 'id' => $record->id ), $base_url ) ); ?>" class="ifs-btn-edit">
                        <span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Edit Record', 'ifs-travel-erp' ); ?>
                    </a>
                </div>
            </div>

            <!-- Printable Official Settlement Document -->
            <div class="ifs-official-voucher">
                
                <!-- Document Header Band -->
                <div class="vcr-header-band">
                    <div class="vcr-brand-info">
                        <span class="vcr-sup-title">
                            <?php 
                            if ( 'Reissue' === $record->type ) {
                                esc_html_e( 'TICKET DATE CHANGE / REISSUE VOUCHER', 'ifs-travel-erp' );
                            } elseif ( 'Void' === $record->type ) {
                                esc_html_e( 'SAME-DAY TICKET VOID CANCELLATION VOUCHER', 'ifs-travel-erp' );
                            } else {
                                esc_html_e( 'AIR TICKET REFUND SETTLEMENT VOUCHER', 'ifs-travel-erp' );
                            }
                            ?>
                        </span>
                        <h1 class="vcr-doc-title"><?php echo esc_html( $record->type ); ?> Settlement Certificate</h1>
                        <span class="vcr-route-line">
                            <span class="dashicons dashicons-airplane"></span> 
                            <?php echo esc_html( $record->airline ?: 'GDS Flight' ); ?> &bull; 
                            <?php echo esc_html( $record->sector ?: 'Confirmed Route' ); ?>
                            <?php if ( ! empty( $record->travel_date ) && $record->travel_date !== '1970-01-01' ) : ?>
                                &bull; Flight Date: <?php echo esc_html( date_i18n( 'd M Y', strtotime( $record->travel_date ) ) ); ?>
                            <?php endif; ?>
                        </span>
                    </div>

                    <div class="vcr-meta-box">
                        <div class="vcr-meta-item">
                            <span><?php esc_html_e( 'SETTLEMENT REF NO', 'ifs-travel-erp' ); ?></span>
                            <strong class="font-mono">#REF-<?php echo esc_html( str_pad( (string) $record->id, 5, '0', STR_PAD_LEFT ) ); ?></strong>
                        </div>
                        <div class="vcr-meta-item">
                            <span><?php esc_html_e( 'EXECUTION DATE', 'ifs-travel-erp' ); ?></span>
                            <strong class="font-mono"><?php echo esc_html( date_i18n( 'd M Y, h:i A', strtotime( $record->created_at ?? current_time( 'mysql' ) ) ) ); ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Passenger & Channel Matrix -->
                <div class="vcr-guest-strip">
                    <div class="vcr-col">
                        <span class="lbl"><?php esc_html_e( 'PRIMARY TRAVELER', 'ifs-travel-erp' ); ?></span>
                        <strong class="val uppercase"><?php echo esc_html( $pax_name ); ?></strong>
                    </div>
                    <div class="vcr-col">
                        <span class="lbl"><?php esc_html_e( 'PASSPORT NO', 'ifs-travel-erp' ); ?></span>
                        <strong class="val font-mono"><?php echo esc_html( $record->customer_passport ?: '—' ); ?></strong>
                    </div>
                    <div class="vcr-col">
                        <span class="lbl"><?php esc_html_e( 'BOOKING CHANNEL', 'ifs-travel-erp' ); ?></span>
                        <strong class="val"><?php echo esc_html( $channel ); ?></strong>
                    </div>
                    <div class="vcr-col">
                        <span class="lbl"><?php esc_html_e( 'SUPPLIER / ISSUER', 'ifs-travel-erp' ); ?></span>
                        <strong class="val"><?php echo esc_html( $vendor ); ?></strong>
                    </div>
                </div>

                <!-- Ticket & Flight Logistics Grid -->
                <div class="vcr-stay-grid">
                    <div class="stay-box">
                        <span class="s-lbl"><span class="dashicons dashicons-tag"></span> <?php esc_html_e( 'ORIGINAL GDS PNR', 'ifs-travel-erp' ); ?></span>
                        <strong class="s-val font-mono uppercase color-blue"><?php echo esc_html( $record->pnr ); ?></strong>
                        <span class="s-sub"><?php esc_html_e( 'Original Booking Ref', 'ifs-travel-erp' ); ?></span>
                    </div>
                    <div class="stay-box">
                        <span class="s-lbl"><span class="dashicons dashicons-media-document"></span> <?php esc_html_e( 'ORIGINAL TICKET NO', 'ifs-travel-erp' ); ?></span>
                        <strong class="s-val font-mono"><?php echo esc_html( $record->ticket_no ?: '—' ); ?></strong>
                        <span class="s-sub"><?php esc_html_e( 'Surrendered E-Ticket', 'ifs-travel-erp' ); ?></span>
                    </div>

                    <?php if ( 'Reissue' === $record->type ) : ?>
                        <div class="stay-box highlight">
                            <span class="s-lbl"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'NEW REISSUED PNR', 'ifs-travel-erp' ); ?></span>
                            <strong class="s-val font-mono uppercase color-emerald"><?php echo esc_html( $record->new_pnr ?: $record->pnr ); ?></strong>
                            <span class="s-sub"><?php esc_html_e( 'Modified Itinerary PNR', 'ifs-travel-erp' ); ?></span>
                        </div>
                        <div class="stay-box highlight">
                            <span class="s-lbl"><span class="dashicons dashicons-tickets-alt"></span> <?php esc_html_e( 'NEW TICKET NUMBER', 'ifs-travel-erp' ); ?></span>
                            <strong class="s-val font-mono color-emerald"><?php echo esc_html( $record->new_ticket_no ?: '—' ); ?></strong>
                            <span class="s-sub"><?php esc_html_e( 'New Stamped E-Ticket', 'ifs-travel-erp' ); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $record->bsp_ra_no ) ) : ?>
                        <div class="stay-box">
                            <span class="s-lbl"><span class="dashicons dashicons-media-text"></span> <?php esc_html_e( 'BSP RA NO', 'ifs-travel-erp' ); ?></span>
                            <strong class="s-val font-mono color-blue"><?php echo esc_html( $record->bsp_ra_no ); ?></strong>
                            <span class="s-sub"><?php esc_html_e( 'Refund Application Ref', 'ifs-travel-erp' ); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $record->airline_waiver_no ) ) : ?>
                        <div class="stay-box">
                            <span class="s-lbl"><span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'AIRLINE WAIVER', 'ifs-travel-erp' ); ?></span>
                            <strong class="s-val font-mono color-emerald"><?php echo esc_html( $record->airline_waiver_no ); ?></strong>
                            <span class="s-sub"><?php esc_html_e( 'Authority Code', 'ifs-travel-erp' ); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Financial Calculation & Breakdown Ledger -->
                <div class="vcr-details-table-wrap">
                    <table class="vcr-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Accounting Head / Commercial Description', 'ifs-travel-erp' ); ?></th>
                                <th><?php esc_html_e( 'Ledger Allocation', 'ifs-travel-erp' ); ?></th>
                                <th style="text-align: right;"><?php esc_html_e( 'Debit (৳)', 'ifs-travel-erp' ); ?></th>
                                <th style="text-align: right;"><?php esc_html_e( 'Credit (৳)', 'ifs-travel-erp' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <strong><?php esc_html_e( 'Original Invoiced Ticket Fare', 'ifs-travel-erp' ); ?></strong>
                                    <div style="font-size: 11px; color: #64748b;"><?php echo esc_html( "Gross amount charged for PNR: {$record->pnr} | TKT: " . ( $record->ticket_no ?: 'N/A' ) ); ?></div>
                                </td>
                                <td><?php esc_html_e( 'Base Airfare', 'ifs-travel-erp' ); ?></td>
                                <td style="text-align: right; font-family: monospace;">—</td>
                                <td style="text-align: right; font-family: monospace; font-weight: 700;">৳<?php echo esc_html( number_format( $orig_fare, 2 ) ); ?></td>
                            </tr>

                            <?php if ( $penalty > 0 ) : ?>
                                <tr>
                                    <td>
                                        <strong><?php esc_html_e( 'Airline Cancellation / Penalty Charge', 'ifs-travel-erp' ); ?></strong>
                                        <div style="font-size: 11px; color: #64748b;"><?php esc_html_e( 'Official airline/GDS fine deducted as per fare rules', 'ifs-travel-erp' ); ?></div>
                                    </td>
                                    <td><span style="color: #dc2626; font-weight: 600;"><?php esc_html_e( 'Airline Penalty', 'ifs-travel-erp' ); ?></span></td>
                                    <td style="text-align: right; font-family: monospace; color: #dc2626; font-weight: 700;">৳<?php echo esc_html( number_format( $penalty, 2 ) ); ?></td>
                                    <td style="text-align: right; font-family: monospace;">—</td>
                                </tr>
                            <?php endif; ?>

                            <?php if ( $service_fee > 0 ) : ?>
                                <tr>
                                    <td>
                                        <strong><?php esc_html_e( 'Agency After-Sales Processing Fee', 'ifs-travel-erp' ); ?></strong>
                                        <div style="font-size: 11px; color: #64748b;"><?php esc_html_e( 'Agency operational service charge for after-sales handling', 'ifs-travel-erp' ); ?></div>
                                    </td>
                                    <td><span style="color: #059669; font-weight: 600;"><?php esc_html_e( 'Agency Commission', 'ifs-travel-erp' ); ?></span></td>
                                    <td style="text-align: right; font-family: monospace; color: #059669; font-weight: 700;">৳<?php echo esc_html( number_format( $service_fee, 2 ) ); ?></td>
                                    <td style="text-align: right; font-family: monospace;">—</td>
                                </tr>
                            <?php endif; ?>

                            <?php if ( 'Reissue' === $record->type && $fare_diff > 0 ) : ?>
                                <tr>
                                    <td>
                                        <strong><?php esc_html_e( 'Fare & Tax Difference', 'ifs-travel-erp' ); ?></strong>
                                        <div style="font-size: 11px; color: #64748b;"><?php esc_html_e( 'Additional class upgrade or seasonal tax differential', 'ifs-travel-erp' ); ?></div>
                                    </td>
                                    <td><span style="color: #0284c7; font-weight: 600;"><?php esc_html_e( 'Fare Difference', 'ifs-travel-erp' ); ?></span></td>
                                    <td style="text-align: right; font-family: monospace; color: #0284c7; font-weight: 700;">৳<?php echo esc_html( number_format( $fare_diff, 2 ) ); ?></td>
                                    <td style="text-align: right; font-family: monospace;">—</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Settlement Final Figure Strip -->
                <div class="vcr-settlement-strip">
                    <div>
                        <span class="s-lbl"><?php esc_html_e( 'SETTLEMENT GATEWAY', 'ifs-travel-erp' ); ?></span>
                        <strong class="val"><?php echo esc_html( $record->settlement_method ?? 'Bank Transfer' ); ?></strong>
                    </div>
                    <?php if ( ! empty( $record->payout_account ) ) : ?>
                        <div>
                            <span class="s-lbl"><?php esc_html_e( 'PAYOUT ACCOUNT', 'ifs-travel-erp' ); ?></span>
                            <strong class="val uppercase"><?php echo esc_html( $record->payout_account ); ?></strong>
                        </div>
                    <?php endif; ?>
                    <div>
                        <span class="s-lbl"><?php esc_html_e( 'FILE STATUS', 'ifs-travel-erp' ); ?></span>
                        <strong class="val uppercase <?php echo ( 'Rejected' === $record->status ) ? 'color-rose' : 'color-emerald'; ?>"><?php echo esc_html( $record->status ?? 'Completed' ); ?></strong>
                    </div>
                    <div style="text-align: right;">
                        <span class="s-lbl">
                            <?php echo ( 'Reissue' === $record->type ) ? esc_html__( 'TOTAL AMOUNT PAYABLE BY CLIENT', 'ifs-travel-erp' ) : esc_html__( 'NET REFUND PAYABLE TO CLIENT', 'ifs-travel-erp' ); ?>
                        </span>
                        <strong class="val color-emerald" style="font-size: 19px; font-family: monospace;">
                            ৳<?php echo esc_html( number_format( $settled_val, 2 ) ); ?>
                        </strong>
                    </div>
                </div>

                <!-- Notes / Remarks -->
                <?php if ( ! empty( $record->remarks ) ) : ?>
                    <div class="vcr-remarks-box">
                        <span class="vcr-card-heading"><?php esc_html_e( 'OPERATIONAL REMARKS & AIRLINE REASON:', 'ifs-travel-erp' ); ?></span>
                        <p><?php echo nl2br( esc_html( $record->remarks ) ); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Footer & Authorization Signatures -->
                <div class="vcr-footer-note">
                    <p><strong><?php esc_html_e( 'Terms & Reversal Policy:', 'ifs-travel-erp' ); ?></strong> <?php esc_html_e( 'This settlement voucher is generated electronically and reflects adjustments processed according to GDS and airline fare rules. For electronic transfers, please allow 3–5 banking business days for reflection in your account statement.', 'ifs-travel-erp' ); ?></p>
                    
                    <div class="vcr-signature-strip">
                        <div class="sig-block">
                            <div class="sig-line"></div>
                            <span><?php esc_html_e( 'Passenger / Agent Acceptance Signature', 'ifs-travel-erp' ); ?></span>
                        </div>
                        <div class="sig-block">
                            <div class="sig-line"></div>
                            <span><?php esc_html_e( 'Authorized Travel Desk Stamp & Signature', 'ifs-travel-erp' ); ?></span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Stylesheet -->
        <style>
            .ifs-refund-view-workspace { max-width: 1100px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; }
            
            .ifs-toast { padding: 12px 18px; border-radius: 8px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 8px; margin-bottom: 20px; }
            .ifs-toast.danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
            .ifs-toast .dashicons { font-size: 18px; width: 18px; height: 18px; }

            /* Header Action Strip */
            .ifs-view-header-strip {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                padding: 20px 24px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 16px;
                box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
                margin-bottom: 22px;
            }
            .ifs-header-identity { display: flex; align-items: center; gap: 16px; }
            .ifs-back-round-btn {
                width: 42px;
                height: 42px;
                border-radius: 10px;
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
            .ifs-badge-row { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; flex-wrap: wrap; }
            .ifs-id-pill { font-family: ui-monospace, monospace; font-size: 11px; font-weight: 800; background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 6px; border: 1px solid #e2e8f0; }
            
            .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10.5px; font-weight: 800; text-transform: uppercase; }
            .badge-refund  { background: #fee2e2; color: #991b1b; }
            .badge-reissue { background: #e0f2fe; color: #0369a1; }
            .badge-void    { background: #fef3c7; color: #92400e; }

            .ifs-status-badge { font-size: 10px; font-weight: 800; text-transform: uppercase; padding: 2px 8px; border-radius: 6px; }
            .status-completed { background: #dcfce7; color: #15803d; }
            .status-pending   { background: #fef3c7; color: #b45309; }
            .status-rejected  { background: #fee2e2; color: #b91c1c; }

            .ifs-view-name { margin: 0; font-size: 19px; font-weight: 800; color: #0f172a; }

            .ifs-header-actions { display: flex; align-items: center; gap: 8px; }
            .ifs-btn-print {
                background: #003376;
                color: #ffffff !important;
                border: none;
                padding: 9px 18px;
                border-radius: 6px;
                font-size: 13px;
                font-weight: 700;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                transition: opacity 0.2s ease;
            }
            .ifs-btn-print:hover { opacity: 0.92; }
            .ifs-btn-edit {
                background: #f8fafc;
                border: 1px solid #cbd5e1;
                color: #334155 !important;
                padding: 8px 16px;
                border-radius: 6px;
                font-size: 13px;
                font-weight: 600;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }
            .ifs-btn-edit:hover { background: #f1f5f9; color: #0f172a; }

            /* Printable Voucher Card */
            .ifs-official-voucher {
                background: #ffffff;
                border: 1px solid #cbd5e1;
                border-radius: 12px;
                padding: 34px;
                color: #0f172a;
                box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
            }
            .vcr-header-band {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                padding-bottom: 20px;
                border-bottom: 2px solid #003376;
                margin-bottom: 20px;
            }
            .vcr-sup-title { font-size: 10.5px; font-weight: 800; color: #003376; letter-spacing: 1px; display: block; margin-bottom: 4px; }
            .vcr-doc-title { margin: 0 0 6px; font-size: 24px; font-weight: 900; color: #0f172a; line-height: 1.2; }
            .vcr-route-line { font-size: 13px; color: #64748b; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
            .vcr-route-line .dashicons { font-size: 14px; width: 14px; height: 14px; color: #003376; }
            .vcr-meta-box { text-align: right; display: flex; flex-direction: column; gap: 6px; }
            .vcr-meta-item span { font-size: 9.5px; font-weight: 700; color: #64748b; display: block; }
            .vcr-meta-item strong { font-size: 13.5px; color: #003376; }

            /* Passenger Strip */
            .vcr-guest-strip {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 14px;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                padding: 14px 16px;
                margin-bottom: 20px;
            }
            .vcr-col .lbl { font-size: 9px; font-weight: 700; color: #64748b; display: block; margin-bottom: 3px; }
            .vcr-col .val { font-size: 13px; font-weight: 800; color: #0f172a; }

            /* Stay / Flight Grid */
            .vcr-stay-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 12px;
                margin-bottom: 20px;
            }
            .stay-box {
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                padding: 12px 14px;
                display: flex;
                flex-direction: column;
                gap: 2px;
                background: #ffffff;
            }
            .stay-box.highlight { background: #f0fdf4; border-color: #bbf7d0; }
            .stay-box .s-lbl { font-size: 9.5px; font-weight: 700; color: #003376; display: flex; align-items: center; gap: 4px; margin-bottom: 3px; }
            .stay-box .s-lbl .dashicons { font-size: 13px; width: 13px; height: 13px; }
            .stay-box .s-val { font-size: 13.5px; font-weight: 800; color: #0f172a; }
            .stay-box .s-sub { font-size: 11px; color: #94a3b8; }

            /* Table Wrap */
            .vcr-details-table-wrap { margin-bottom: 16px; }
            .vcr-table { width: 100%; border-collapse: collapse; font-size: 13px; }
            .vcr-table thead th { background: #f1f5f9; padding: 10px 14px; text-align: left; font-size: 10.5px; font-weight: 700; color: #475569; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; }
            .vcr-table tbody td { padding: 12px 14px; border-bottom: 1px solid #e2e8f0; color: #334155; vertical-align: middle; }

            /* Settlement Final Figure */
            .vcr-settlement-strip {
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                padding: 14px 20px;
                margin-bottom: 20px;
            }
            .vcr-settlement-strip .s-lbl { font-size: 9.5px; font-weight: 700; color: #64748b; display: block; margin-bottom: 2px; }
            .vcr-settlement-strip .val { font-size: 13.5px; font-weight: 800; color: #0f172a; }

            .vcr-remarks-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; }
            .vcr-remarks-box p { margin: 0; font-size: 12.5px; color: #475569; line-height: 1.5; }
            .vcr-card-heading { font-size: 10px; font-weight: 800; color: #003376; letter-spacing: 0.5px; display: block; margin-bottom: 4px; }

            /* Footer & Signatures */
            .vcr-footer-note { font-size: 11px; color: #64748b; line-height: 1.6; border-top: 1px solid #f1f5f9; padding-top: 16px; }
            .vcr-signature-strip { display: flex; justify-content: space-between; margin-top: 45px; }
            .sig-block { text-align: center; width: 220px; }
            .sig-line { border-top: 1px dashed #94a3b8; margin-bottom: 6px; }
            .sig-block span { font-size: 10.5px; color: #475569; font-weight: 600; }

            .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
            .font-bold { font-weight: 700; }
            .uppercase { text-transform: uppercase; }
            .color-blue { color: #003376 !important; }
            .color-rose { color: #dc2626 !important; }
            .color-emerald { color: #059669 !important; }

            /* 1-Page Clean Print Optimization */
            @media print {
                body * { visibility: hidden; }
                .ifs-official-voucher, .ifs-official-voucher * { visibility: visible; }
                .ifs-official-voucher {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 100%;
                    border: 1px solid #000;
                    box-shadow: none;
                    padding: 20px;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
            }
        </style>
        <?php
    }
}