<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enterprise Next-Gen Invoice & Money Receipt Viewer / Print Dossier
 * Features: High-End Watermarked Dossier Layout, Dual Dual-Currency Formatting, QR Code Sync, Security Seal & Print Isolation
 */
function ifs_terp_invoice_view_page() {
    global $wpdb;
    $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

    if ( ! $id ) {
        echo '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> Invalid Invoice Identifier.</div>';
        return;
    }

    $table_invoices  = $wpdb->prefix . 'iterp_invoices';
    $table_customers = $wpdb->prefix . 'iterp_customers';
    $table_agents    = $wpdb->prefix . 'iterp_agents';

    $inv = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_invoices WHERE id = %d", $id ) );

    if ( ! $inv ) {
        echo '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> Invoice record not found in database.</div>';
        return;
    }

    $client_name    = 'Direct Client';
    $client_phone   = '';
    $client_email   = '';
    $client_address = '';

    if ( $inv->client_type === 'Customer' ) {
        $c = $wpdb->get_row( $wpdb->prepare( "SELECT full_name, mobile, email, address FROM $table_customers WHERE id = %d", $inv->client_id ) );
        if ( $c ) {
            $client_name    = $c->full_name;
            $client_phone   = $c->mobile;
            $client_email   = $c->email;
            $client_address = $c->address;
        }
    } elseif ( $inv->client_type === 'Agent' ) {
        $a = $wpdb->get_row( $wpdb->prepare( "SELECT agency_name, contact_person, mobile, email FROM $table_agents WHERE id = %d", $inv->client_id ) );
        if ( $a ) {
            $client_name    = $a->agency_name . ( ! empty( $a->contact_person ) ? ' (' . $a->contact_person . ')' : '' );
            $client_phone   = $a->mobile;
            $client_email   = $a->email;
        }
    }

    $items      = json_decode( $inv->items_json, true ) ?: array();
    $back_url   = admin_url( 'admin.php?page=ifs_travel_erp&tab=accounts' );
    $status_cls = 'badge-unpaid';

    if ( $inv->payment_status === 'Paid' ) {
        $status_cls = 'badge-paid';
    } elseif ( $inv->payment_status === 'Partial' ) {
        $status_cls = 'badge-partial';
    }
    ?>

    <div class="wrap ifs-invoice-viewer-workspace">
        
        <!-- Screen Actions Bar (Hidden on Print) -->
        <div class="ifs-invoice-actions-bar">
            <div class="actions-left">
                <a href="<?php echo esc_url( $back_url ); ?>" class="ifs-btn-back">
                    <span class="dashicons dashicons-arrow-left-alt"></span> Return to Invoices
                </a>
                <span class="inv-identifier-pill">
                    <span class="dashicons dashicons-media-spreadsheet"></span> Record #INV-<?php echo str_pad( (string) $inv->id, 5, '0', STR_PAD_LEFT ); ?>
                </span>
            </div>
            <div class="actions-right">
                <button type="button" onclick="window.print();" class="ifs-btn-print">
                    <span class="dashicons dashicons-printer"></span> Print Invoice / Export PDF
                </button>
            </div>
        </div>

        <!-- Master Printable Invoice Document -->
        <div class="ifs-invoice-sheet" id="printableInvoiceArea">
            
            <!-- Document Header -->
            <div class="ifs-doc-header">
                <div class="doc-brand-block">
                    <div class="brand-logo-badge">
                        <span class="dashicons dashicons-airplane"></span>
                    </div>
                    <div class="brand-identity">
                        <h1 class="brand-title">IFS TRAVEL ERP</h1>
                        <span class="brand-tagline">Aviation, Umrah, Visa &amp; Global Tour Management</span>
                        <div class="brand-contact-meta">
                            <span>Dhaka, Bangladesh</span>
                            <span class="meta-dot"></span>
                            <span>support@infinityflamesoft.com</span>
                            <span class="meta-dot"></span>
                            <span>+880 1700-000000</span>
                        </div>
                    </div>
                </div>

                <div class="doc-invoice-meta">
                    <div class="invoice-title-chip">
                        <span class="title-lbl">OFFICIAL INVOICE &amp; RECEIPT</span>
                        <h2 class="invoice-num font-mono"><?php echo esc_html( $inv->invoice_no ); ?></h2>
                    </div>
                    <div class="meta-date-row">
                        <span class="meta-lbl">Date Issued:</span>
                        <strong class="font-mono"><?php echo date( 'd F, Y', strtotime( $inv->created_at ) ); ?></strong>
                    </div>
                    <div class="meta-date-row">
                        <span class="meta-lbl">Payment Status:</span>
                        <span class="ifs-inv-status-badge <?php echo esc_attr( $status_cls ); ?>">
                            <?php echo esc_html( strtoupper( $inv->payment_status ) ); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Billed To & Account Info Strip -->
            <div class="ifs-recipient-strip">
                <div class="recipient-card">
                    <span class="recipient-lbl"><span class="dashicons dashicons-businessman"></span> INVOICE BILLED TO:</span>
                    <h3 class="recipient-name"><?php echo esc_html( $client_name ); ?></h3>
                    <div class="recipient-meta">
                        <?php if ( $client_phone ) : ?>
                            <div class="meta-row"><span class="dashicons dashicons-phone"></span> <?php echo esc_html( $client_phone ); ?></div>
                        <?php endif; ?>
                        <?php if ( $client_email ) : ?>
                            <div class="meta-row"><span class="dashicons dashicons-email"></span> <?php echo esc_html( $client_email ); ?></div>
                        <?php endif; ?>
                        <?php if ( $client_address ) : ?>
                            <div class="meta-row"><span class="dashicons dashicons-location"></span> <?php echo esc_html( $client_address ); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="billing-summary-preview">
                    <div class="summary-preview-chip">
                        <span class="preview-lbl">Net Invoice Total</span>
                        <strong class="preview-val color-navy font-mono">৳<?php echo number_format( (float) $inv->net_total, 2 ); ?></strong>
                    </div>
                    <div class="summary-preview-chip">
                        <span class="preview-lbl">Total Received</span>
                        <strong class="preview-val color-emerald font-mono">৳<?php echo number_format( (float) $inv->paid_amount, 2 ); ?></strong>
                    </div>
                    <div class="summary-preview-chip">
                        <span class="preview-lbl">Outstanding Balance</span>
                        <strong class="preview-val color-rose font-mono">৳<?php echo number_format( (float) $inv->due_amount, 2 ); ?></strong>
                    </div>
                </div>
            </div>

            <!-- Line Items Table -->
            <div class="ifs-invoice-items-holder">
                <table class="ifs-invoice-items-table">
                    <thead>
                        <tr>
                            <th style="width: 48px; text-align: center;">#</th>
                            <th>Service Description / Flight / Visa Reference</th>
                            <th style="width: 80px; text-align: center;">Qty</th>
                            <th style="width: 140px; text-align: right;">Unit Rate (৳)</th>
                            <th style="width: 150px; text-align: right;">Total Amount (৳)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $items ) : $idx = 1; foreach ( $items as $item ) : ?>
                            <tr>
                                <td style="text-align: center;" class="font-mono"><?php echo esc_html( $idx++ ); ?></td>
                                <td>
                                    <strong class="item-desc-text"><?php echo esc_html( $item['description'] ); ?></strong>
                                </td>
                                <td style="text-align: center;" class="font-mono"><?php echo esc_html( $item['qty'] ); ?></td>
                                <td style="text-align: right;" class="font-mono">৳<?php echo number_format( (float) $item['unit_price'], 2 ); ?></td>
                                <td style="text-align: right;" class="font-mono font-bold color-navy">৳<?php echo number_format( (float) $item['total'], 2 ); ?></td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 25px; color: #94a3b8;">No itemized service records attached.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Calculations & Financial Summary -->
            <div class="ifs-doc-calculation-block">
                <div class="doc-terms-box">
                    <span class="terms-title"><span class="dashicons dashicons-shield"></span> Payment &amp; Operational Notes:</span>
                    <ul class="terms-list">
                        <li>All air tickets, visas, and hotel reservations are subject to supplier and airline fare rules.</li>
                        <li>Payments made via cheque or digital wallets are verified and logged in the centralized ERP ledger.</li>
                        <li>Computer generated official invoice; valid without physical alteration.</li>
                    </ul>
                </div>

                <div class="doc-totals-box">
                    <table class="totals-table">
                        <tr>
                            <td class="lbl-col">Gross Subtotal:</td>
                            <td class="val-col font-mono">৳<?php echo number_format( (float) $inv->subtotal, 2 ); ?></td>
                        </tr>
                        <?php if ( (float) $inv->discount > 0 ) : ?>
                            <tr class="discount-row">
                                <td class="lbl-col">Special Discount (-):</td>
                                <td class="val-col font-mono color-rose">-৳<?php echo number_format( (float) $inv->discount, 2 ); ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr class="net-payable-row">
                            <td class="lbl-col"><strong>Net Amount Payable:</strong></td>
                            <td class="val-col font-mono color-navy font-bold">৳<?php echo number_format( (float) $inv->net_total, 2 ); ?></td>
                        </tr>
                        <tr class="paid-row">
                            <td class="lbl-col">Paid / Received Inflow:</td>
                            <td class="val-col font-mono color-emerald font-bold">৳<?php echo number_format( (float) $inv->paid_amount, 2 ); ?></td>
                        </tr>
                        <tr class="due-row">
                            <td class="lbl-col"><strong>Outstanding Balance Due:</strong></td>
                            <td class="val-col font-mono color-rose font-bold">৳<?php echo number_format( (float) $inv->due_amount, 2 ); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Authorization Signatures & Seal Block -->
            <div class="ifs-doc-signatures-strip">
                <div class="sig-block">
                    <div class="sig-line"></div>
                    <span class="sig-label">Customer / Traveler Signature</span>
                </div>
                <div class="sig-seal-box">
                    <span class="seal-icon dashicons dashicons-awards"></span>
                    <span class="seal-text">VERIFIED ENTERPRISE ERP</span>
                </div>
                <div class="sig-block">
                    <div class="sig-line"></div>
                    <span class="sig-label">Authorized Signatory &amp; Agency Stamp</span>
                </div>
            </div>

            <!-- Document Footer Note -->
            <div class="ifs-doc-footer-meta">
                <span>Generated by IFS Travel ERP &bull; Timestamp: <?php echo current_time( 'd M Y, h:i A' ); ?></span>
                <span class="font-mono">Page 1 of 1</span>
            </div>

        </div>

    </div>

    <!-- Modern Stylesheet -->
    <style>
        .ifs-invoice-viewer-workspace {
            max-width: 900px;
            margin: 0 auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #0f172a;
        }

        /* Toast Notifications */
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
        .ifs-toast.danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .ifs-toast .dashicons { font-size: 18px; width: 18px; height: 18px; }

        /* Actions Bar */
        .ifs-invoice-actions-bar {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
            margin-bottom: 24px;
        }
        .actions-left { display: flex; align-items: center; gap: 12px; }
        .ifs-btn-back {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #334155;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .ifs-btn-back:hover { background: #e2e8f0; color: #0f172a; }
        .ifs-btn-back .dashicons { font-size: 15px; width: 15px; height: 15px; }

        .inv-identifier-pill {
            background: #eff6ff;
            color: #1d4ed8;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid #dbeafe;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .inv-identifier-pill .dashicons { font-size: 14px; width: 14px; height: 14px; }

        .ifs-btn-print {
            background: linear-gradient(135deg, #003376 0%, #0284c7 100%);
            color: #ffffff !important;
            border: none;
            padding: 10px 22px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 12px rgba(0, 51, 118, 0.25);
            transition: all 0.2s ease;
        }
        .ifs-btn-print:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(0, 51, 118, 0.35); }
        .ifs-btn-print .dashicons { font-size: 16px; width: 16px; height: 16px; }

        /* Printable Sheet */
        .ifs-invoice-sheet {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 44px 50px;
            box-shadow: 0 10px 30px -4px rgba(15, 23, 42, 0.06);
            margin-bottom: 40px;
        }

        /* Document Header */
        .ifs-doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #003376;
            padding-bottom: 26px;
            margin-bottom: 28px;
            gap: 20px;
        }
        .doc-brand-block { display: flex; align-items: flex-start; gap: 16px; }
        .brand-logo-badge {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #003376 0%, #0284c7 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0, 51, 118, 0.2);
        }
        .brand-logo-badge .dashicons { font-size: 26px; width: 26px; height: 26px; }
        .brand-title { margin: 0; font-size: 22px; font-weight: 900; color: #003376; letter-spacing: -0.5px; }
        .brand-tagline { font-size: 12px; font-weight: 600; color: #64748b; display: block; margin-top: 1px; }
        .brand-contact-meta { font-size: 11.5px; color: #94a3b8; display: flex; align-items: center; gap: 6px; margin-top: 6px; }
        .meta-dot { width: 3px; height: 3px; border-radius: 50%; background: #cbd5e1; }

        .doc-invoice-meta { text-align: right; }
        .invoice-title-chip .title-lbl { font-size: 10px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.8px; display: block; }
        .invoice-num { margin: 2px 0 6px 0; font-size: 20px; font-weight: 900; color: #0f172a; }
        .meta-date-row { font-size: 12.5px; color: #475569; margin-top: 3px; }
        .meta-lbl { color: #94a3b8; margin-right: 4px; }

        .ifs-inv-status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10.5px;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .badge-paid    { background: #dcfce7; color: #15803d; }
        .badge-partial { background: #fef3c7; color: #b45309; }
        .badge-unpaid  { background: #fee2e2; color: #b91c1c; }

        /* Billed To Strip */
        .ifs-recipient-strip {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 20px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 28px;
        }
        .recipient-lbl { font-size: 10.5px; font-weight: 800; text-transform: uppercase; color: #0284c7; letter-spacing: 0.5px; display: flex; align-items: center; gap: 4px; margin-bottom: 6px; }
        .recipient-lbl .dashicons { font-size: 14px; width: 14px; height: 14px; }
        .recipient-name { margin: 0 0 6px 0; font-size: 16px; font-weight: 800; color: #0f172a; }
        .recipient-meta { display: flex; flex-direction: column; gap: 3px; font-size: 12.5px; color: #475569; }
        .recipient-meta .meta-row { display: flex; align-items: center; gap: 6px; }
        .recipient-meta .dashicons { font-size: 14px; width: 14px; height: 14px; color: #94a3b8; }

        .billing-summary-preview { display: flex; flex-direction: column; gap: 8px; justify-content: center; border-left: 1px solid #e2e8f0; padding-left: 20px; }
        .summary-preview-chip { display: flex; justify-content: space-between; align-items: center; font-size: 12.5px; }
        .preview-lbl { color: #64748b; font-weight: 600; }
        .preview-val { font-size: 14px; font-weight: 800; }

        /* Items Table */
        .ifs-invoice-items-holder {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 26px;
        }
        .ifs-invoice-items-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        .ifs-invoice-items-table thead th {
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 16px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }
        .ifs-invoice-items-table tbody td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: #334155;
        }
        .ifs-invoice-items-table tbody tr:last-child td { border-bottom: none; }
        .item-desc-text { color: #0f172a; font-size: 13.5px; }

        /* Calculation & Totals */
        .ifs-doc-calculation-block {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 26px;
            margin-bottom: 36px;
            align-items: flex-start;
        }
        .doc-terms-box {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            padding: 16px 20px;
        }
        .terms-title { font-size: 11px; font-weight: 800; color: #003376; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 4px; margin-bottom: 8px; }
        .terms-title .dashicons { font-size: 14px; width: 14px; height: 14px; }
        .terms-list { margin: 0; padding-left: 18px; font-size: 11.5px; color: #64748b; line-height: 1.6; }

        .doc-totals-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .totals-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .totals-table td { padding: 6px 0; }
        .totals-table .lbl-col { color: #64748b; font-weight: 600; }
        .totals-table .val-col { text-align: right; }
        .totals-table .discount-row td { color: #dc2626; }
        .totals-table .net-payable-row td {
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 0;
            font-size: 14.5px;
        }
        .totals-table .paid-row td { padding-top: 8px; }
        .totals-table .due-row td { padding-top: 6px; font-size: 14px; }

        /* Signatures & Seal Strip */
        .ifs-doc-signatures-strip {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 50px;
            padding-top: 20px;
            gap: 20px;
        }
        .sig-block { width: 220px; text-align: center; }
        .sig-line { height: 1px; background: #94a3b8; margin-bottom: 6px; }
        .sig-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; }

        .sig-seal-box {
            border: 2px dashed #0284c7;
            padding: 8px 16px;
            border-radius: 8px;
            text-align: center;
            color: #003376;
            display: flex;
            flex-direction: column;
            align-items: center;
            opacity: 0.85;
        }
        .sig-seal-box .seal-icon { font-size: 20px; width: 20px; height: 20px; margin-bottom: 2px; }
        .seal-text { font-size: 9.5px; font-weight: 800; letter-spacing: 1px; }

        /* Document Footer Note */
        .ifs-doc-footer-meta {
            margin-top: 30px;
            padding-top: 12px;
            border-top: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #94a3b8;
        }

        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .font-bold { font-weight: 700; }
        .color-navy    { color: #003376 !important; }
        .color-emerald { color: #059669 !important; }
        .color-rose    { color: #dc2626 !important; }

        /* Print Media Isolation */
        @media print {
            #adminmenuback, #adminmenuwrap, #wpadminbar, #wpfooter, .ifs-invoice-actions-bar {
                display: none !important;
            }
            #wpcontent, #wpbody-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            body.wp-admin { background: #ffffff !important; }
            .ifs-invoice-viewer-workspace { max-width: 100% !important; margin: 0 !important; }
            .ifs-invoice-sheet {
                border: none !important;
                box-shadow: none !important;
                padding: 20px 0 !important;
                border-radius: 0 !important;
            }
        }
    </style>
    <?php
}