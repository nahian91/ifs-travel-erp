<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_hajj_booking_view_page() {
    global $wpdb;
    $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

    if ( ! $id ) {
        echo '<div class="notice notice-error"><p>Invalid Booking ID.</p></div>';
        return;
    }

    $table_bookings  = $wpdb->prefix . 'iterp_hajj_bookings';
    $table_customers = $wpdb->prefix . 'iterp_customers';
    $table_packages  = $wpdb->prefix . 'iterp_hajj_packages';

    $query = $wpdb->prepare( "
        SELECT b.*, c.full_name AS pilgrim_name, c.mobile, c.passport_no, c.passport_expiry,
               p.package_name, p.package_type, p.hotel_makkah, p.hotel_madinah,
               m.full_name AS mahram_name
        FROM $table_bookings b
        LEFT JOIN $table_customers c ON b.customer_id = c.id
        LEFT JOIN $table_packages p ON b.package_id = p.id
        LEFT JOIN $table_customers m ON b.mahram_customer_id = m.id
        WHERE b.id = %d
    ", $id );
    
    $booking = $wpdb->get_row( $query );

    if ( ! $booking ) {
        echo '<div class="notice notice-error"><p>Pilgrim booking not found.</p></div>';
        return;
    }
    ?>
    <div style="display: grid; grid-template-columns: 300px 1fr; gap: 25px; margin-top: 20px;">
        
        <!-- Left Summary Card -->
        <div style="background: #fff; padding: 25px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center; height: fit-content;">
            <span class="dashicons dashicons-id" style="font-size: 48px; width: 48px; height: 48px; color: #003376;"></span>
            <h3 style="margin: 10px 0 5px 0; font-size: 20px; color: #0f172a;"><?php echo esc_html( $booking->pilgrim_name ); ?></h3>
            <p style="margin: 0 0 15px 0; color: #64748b; font-size: 13px;">Booking Ref: #HB-<?php echo esc_html( $booking->id ); ?></p>
            
            <span style="background: #dcfce7; color: #166534; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; margin-bottom: 20px;">
                <?php echo esc_html( $booking->status ); ?>
            </span>

            <div style="border-top: 1px solid #e2e8f0; padding-top: 15px; text-align: left;">
                <a href="<?php echo admin_url( 'admin.php?page=ifs_travel_erp&tab=hajj_umrah&sub=edit&id=' . $id ); ?>" style="display: block; background: #f8fafc; border: 1px solid #cbd5e1; padding: 8px 12px; text-align: center; border-radius: 4px; text-decoration: none; color: #0f172a; font-weight: 600;">
                    Edit Booking
                </a>
            </div>
        </div>

        <!-- Right Detailed Info -->
        <div style="background: #fff; padding: 25px; border-radius: 8px; border: 1px solid #e2e8f0;">
            <h3 style="margin-top: 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; color: #003376;">Pilgrim & Group Information</h3>
            
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 25px;">
                <tr>
                    <td style="padding: 8px 0; color: #64748b; width: 35%;">Pilgrim Name:</td>
                    <td style="padding: 8px 0; font-weight: 600; color: #0f172a;"><?php echo esc_html( $booking->pilgrim_name ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Contact Mobile:</td>
                    <td style="padding: 8px 0; font-weight: 600;"><?php echo esc_html( $booking->mobile ?: 'N/A' ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Passport Number:</td>
                    <td style="padding: 8px 0; font-weight: 700; font-family: monospace;"><?php echo esc_html( $booking->passport_no ?: 'N/A' ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Mahram / Guardian:</td>
                    <td style="padding: 8px 0; font-weight: 600; color: #0369a1;"><?php echo esc_html( $booking->mahram_name ?: 'None / Self' ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Package Name:</td>
                    <td style="padding: 8px 0; font-weight: 700; color: #003376;"><?php echo esc_html( $booking->package_name ?: 'Custom Package' ); ?> (<?php echo esc_html( $booking->package_type ); ?>)</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Room Sharing:</td>
                    <td style="padding: 8px 0; font-weight: 600;"><?php echo esc_html( $booking->room_sharing ); ?> Room</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Makkah Hotel:</td>
                    <td style="padding: 8px 0;"><?php echo esc_html( $booking->hotel_makkah ?: 'Not Specified' ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Madinah Hotel:</td>
                    <td style="padding: 8px 0;"><?php echo esc_html( $booking->hotel_madinah ?: 'Not Specified' ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Saudi BRN Number:</td>
                    <td style="padding: 8px 0; font-family: monospace;"><?php echo esc_html( $booking->brn_no ?: 'N/A' ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Mofaza Number:</td>
                    <td style="padding: 8px 0; font-family: monospace;"><?php echo esc_html( $booking->mofaza_no ?: 'N/A' ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Visa Status:</td>
                    <td style="padding: 8px 0; font-weight: 700; color: #0369a1;"><?php echo esc_html( $booking->visa_status ); ?></td>
                </tr>
            </table>

            <h3 style="margin-top: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; color: #003376;">Financial Summary</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; color: #64748b; width: 35%;">Net Cost Rate:</td>
                    <td style="padding: 8px 0; font-weight: 600; color: #475569;">৳<?php echo number_format( $booking->buy_price, 2 ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Total Selling Price:</td>
                    <td style="padding: 8px 0; font-weight: 700; color: #0f172a;">৳<?php echo number_format( $booking->sell_price, 2 ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Profit Margin:</td>
                    <td style="padding: 8px 0; font-weight: 800; color: <?php echo ($booking->profit >= 0) ? '#166534' : '#dc2626'; ?>;">
                        ৳<?php echo number_format( $booking->profit, 2 ); ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <?php
}