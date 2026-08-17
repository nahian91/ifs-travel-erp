<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_ticket_view_page() {
    global $wpdb;
    $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

    if ( ! $id ) {
        echo '<div class="notice notice-error"><p>Invalid Ticket ID.</p></div>';
        return;
    }

    $table_tickets   = $wpdb->prefix . 'iterp_tickets';
    $table_customers = $wpdb->prefix . 'iterp_customers';

    $query = $wpdb->prepare( "
        SELECT t.*, c.full_name, c.mobile, c.passport_no, c.email 
        FROM $table_tickets t
        LEFT JOIN $table_customers c ON t.customer_id = c.id
        WHERE t.id = %d
    ", $id );
    
    $ticket = $wpdb->get_row( $query );

    if ( ! $ticket ) {
        echo '<div class="notice notice-error"><p>Ticket record not found.</p></div>';
        return;
    }
    ?>
    <div style="display: grid; grid-template-columns: 300px 1fr; gap: 25px; margin-top: 20px;">
        
        <!-- Left Summary Card -->
        <div style="background: #fff; padding: 25px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center; height: fit-content;">
            <span class="dashicons dashicons-tickets-alt" style="font-size: 48px; width: 48px; height: 48px; color: #003376;"></span>
            <h3 style="margin: 10px 0 5px 0; font-size: 22px; font-family: monospace; color: #0f172a;"><?php echo esc_html( $ticket->pnr ); ?></h3>
            <p style="margin: 0 0 15px 0; color: #64748b; font-size: 13px;">PNR / Booking Reference</p>
            
            <span style="background: #e0f2fe; color: #0369a1; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; margin-bottom: 20px;">
                <?php echo esc_html( $ticket->status ); ?>
            </span>

            <div style="border-top: 1px solid #e2e8f0; padding-top: 15px; text-align: left;">
                <a href="<?php echo admin_url( 'admin.php?page=ifs_travel_erp&tab=ticketing&sub=edit&id=' . $id ); ?>" style="display: block; background: #f8fafc; border: 1px solid #cbd5e1; padding: 8px 12px; text-align: center; border-radius: 4px; text-decoration: none; color: #0f172a; font-weight: 600;">
                    Edit Ticket
                </a>
            </div>
        </div>

        <!-- Right Detailed Breakdown -->
        <div style="background: #fff; padding: 25px; border-radius: 8px; border: 1px solid #e2e8f0;">
            <h3 style="margin-top: 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; color: #003376;">Flight & Passenger Details</h3>
            
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 25px;">
                <tr>
                    <td style="padding: 8px 0; color: #64748b; width: 35%;">Passenger Name:</td>
                    <td style="padding: 8px 0; font-weight: 600; color: #0f172a;"><?php echo esc_html( $ticket->full_name ?: 'Direct Customer' ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Mobile:</td>
                    <td style="padding: 8px 0; font-weight: 600;"><?php echo esc_html( $ticket->mobile ?: 'N/A' ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Passport No:</td>
                    <td style="padding: 8px 0; font-weight: 600;"><?php echo esc_html( $ticket->passport_no ?: 'N/A' ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Ticket Number:</td>
                    <td style="padding: 8px 0; font-weight: 700; font-family: monospace;"><?php echo esc_html( $ticket->ticket_no ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Airline:</td>
                    <td style="padding: 8px 0; font-weight: 600;"><?php echo esc_html( $ticket->airline ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Sector / Route:</td>
                    <td style="padding: 8px 0; font-weight: 700; color: #0369a1;"><?php echo esc_html( $ticket->sector ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Cabin Class:</td>
                    <td style="padding: 8px 0;"><?php echo esc_html( $ticket->cabin_class ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Travel Date:</td>
                    <td style="padding: 8px 0; font-weight: 600;"><?php echo ( $ticket->travel_date != '1970-01-01' ) ? date('d F, Y', strtotime($ticket->travel_date)) : 'N/A'; ?></td>
                </tr>
            </table>

            <h3 style="margin-top: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; color: #003376;">Financial Overview</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; color: #64748b; width: 35%;">Buy Price (Supplier):</td>
                    <td style="padding: 8px 0; font-weight: 600; color: #475569;">৳<?php echo number_format( $ticket->buy_price, 2 ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Sell Price (Customer):</td>
                    <td style="padding: 8px 0; font-weight: 700; color: #0f172a;">৳<?php echo number_format( $ticket->sell_price, 2 ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Net Profit:</td>
                    <td style="padding: 8px 0; font-weight: 800; color: <?php echo ($ticket->profit >= 0) ? '#166534' : '#dc2626'; ?>;">
                        ৳<?php echo number_format( $ticket->profit, 2 ); ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <?php
}