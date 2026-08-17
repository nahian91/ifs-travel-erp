<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_invoice_view_page() {
    global $wpdb;
    $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

    if ( ! $id ) {
        echo '<div class="notice notice-error"><p>Invalid Invoice ID.</p></div>';
        return;
    }

    $table_invoices  = $wpdb->prefix . 'iterp_invoices';
    $table_customers = $wpdb->prefix . 'iterp_customers';
    $table_agents    = $wpdb->prefix . 'iterp_agents';

    $inv = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_invoices WHERE id = %d", $id ) );

    if ( ! $inv ) {
        echo '<div class="notice notice-error"><p>Invoice record not found.</p></div>';
        return;
    }

    $client_name = 'Direct Client';
    $client_phone = '';
    $client_address = '';

    if ( $inv->client_type === 'Customer' ) {
        $c = $wpdb->get_row( $wpdb->prepare( "SELECT full_name, mobile, address FROM $table_customers WHERE id = %d", $inv->client_id ) );
        if ( $c ) {
            $client_name = $c->full_name;
            $client_phone = $c->mobile;
            $client_address = $c->address;
        }
    } elseif ( $inv->client_type === 'Agent' ) {
        $a = $wpdb->get_row( $wpdb->prepare( "SELECT company_name, mobile FROM $table_agents WHERE id = %d", $inv->client_id ) );
        if ( $a ) {
            $client_name = $a->company_name;
            $client_phone = $a->mobile;
        }
    }

    $items = json_decode( $inv->items_json, true );
    ?>
    <div style="margin-bottom: 20px;">
        <button onclick="window.print();" style="background:#003376; color:#fff; border:none; padding:8px 16px; border-radius:4px; font-weight:600; cursor:pointer;">
            <span class="dashicons dashicons-printer" style="vertical-align:middle;"></span> Print Invoice / Save as PDF
        </button>
    </div>

    <div style="background:#fff; padding:40px; border-radius:8px; border:1px solid #e2e8f0; max-width:800px; margin:0 auto; font-family: sans-serif; color:#1e293b;" id="printableInvoiceArea">
        <!-- Header -->
        <div style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #003376; padding-bottom:20px; margin-bottom:25px;">
            <div>
                <h1 style="margin:0; font-size:24px; color:#003376; font-weight:800;">IFS TRAVEL ERP</h1>
                <p style="margin:4px 0 0 0; font-size:12px; color:#64748b;">Hajj, Umrah & Air Ticketing Management</p>
            </div>
            <div style="text-align:right;">
                <h2 style="margin:0; font-size:20px; color:#0f172a;"><?php echo esc_html( $inv->invoice_no ); ?></h2>
                <p style="margin:4px 0 0 0; font-size:13px; color:#64748b;">Date: <?php echo date('d F, Y', strtotime($inv->created_at)); ?></p>
            </div>
        </div>

        <!-- Billed To Info -->
        <div style="margin-bottom:30px;">
            <span style="font-size:11px; text-transform:uppercase; color:#94a3b8; font-weight:700; display:block; margin-bottom:5px;">Invoice Billed To:</span>
            <div style="font-size:16px; font-weight:700; color:#0f172a;"><?php echo esc_html( $client_name ); ?></div>
            <?php if ( $client_phone ) : ?><div style="font-size:13px; color:#475569;">Mobile: <?php echo esc_html( $client_phone ); ?></div><?php endif; ?>
            <?php if ( $client_address ) : ?><div style="font-size:13px; color:#475569;"><?php echo esc_html( $client_address ); ?></div><?php endif; ?>
        </div>

        <!-- Items Table -->
        <table style="width:100%; border-collapse:collapse; margin-bottom:25px;">
            <thead>
                <tr style="background:#f8fafc; border-bottom:1px solid #e2e8f0; text-align:left;">
                    <th style="padding:10px; font-size:13px; color:#475569;">Item Description</th>
                    <th style="padding:10px; font-size:13px; color:#475569; text-align:center;">Qty</th>
                    <th style="padding:10px; font-size:13px; color:#475569; text-align:right;">Rate (৳)</th>
                    <th style="padding:10px; font-size:13px; color:#475569; text-align:right;">Amount (৳)</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $items ) : foreach ( $items as $item ) : ?>
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:10px; font-size:13px; font-weight:600; color:#0f172a;"><?php echo esc_html( $item['description'] ); ?></td>
                        <td style="padding:10px; font-size:13px; text-align:center;"><?php echo esc_html( $item['qty'] ); ?></td>
                        <td style="padding:10px; font-size:13px; text-align:right;">৳<?php echo number_format( $item['unit_price'], 2 ); ?></td>
                        <td style="padding:10px; font-size:13px; text-align:right; font-weight:700;">৳<?php echo number_format( $item['total'], 2 ); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>

        <!-- Totals Summary -->
        <div style="display:flex; justify-content:flex-end;">
            <table style="width:280px; border-collapse:collapse;">
                <tr>
                    <td style="padding:6px 0; color:#64748b; font-size:13px;">Subtotal:</td>
                    <td style="padding:6px 0; text-align:right; font-weight:600; font-size:13px;">৳<?php echo number_format( $inv->subtotal, 2 ); ?></td>
                </tr>
                <?php if ( $inv->discount > 0 ) : ?>
                <tr>
                    <td style="padding:6px 0; color:#64748b; font-size:13px;">Discount:</td>
                    <td style="padding:6px 0; text-align:right; color:#dc2626; font-size:13px;">-৳<?php echo number_format( $inv->discount, 2 ); ?></td>
                </tr>
                <?php endif; ?>
                <tr style="border-top:1px solid #e2e8f0;">
                    <td style="padding:8px 0; font-weight:700; font-size:15px; color:#0f172a;">Net Payable:</td>
                    <td style="padding:8px 0; text-align:right; font-weight:800; font-size:15px; color:#003376;">৳<?php echo number_format( $inv->net_total, 2 ); ?></td>
                </tr>
                <tr>
                    <td style="padding:6px 0; color:#166534; font-weight:600; font-size:13px;">Paid / Received:</td>
                    <td style="padding:6px 0; text-align:right; color:#166534; font-weight:700; font-size:13px;">৳<?php echo number_format( $inv->paid_amount, 2 ); ?></td>
                </tr>
                <tr>
                    <td style="padding:6px 0; color:#dc2626; font-weight:700; font-size:14px;">Total Due:</td>
                    <td style="padding:6px 0; text-align:right; color:#dc2626; font-weight:800; font-size:14px;">৳<?php echo number_format( $inv->due_amount, 2 ); ?></td>
                </tr>
            </table>
        </div>

        <div style="margin-top:60px; display:flex; justify-content:space-between; border-top:1px dashed #cbd5e1; padding-top:10px; font-size:12px; color:#64748b;">
            <span>Customer Signature</span>
            <span>Authorized Signature & Seal</span>
        </div>
    </div>
    <?php
}