<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_visa_view_page() {
    global $wpdb;
    $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

    if ( ! $id ) {
        echo '<div class="notice notice-error"><p>Invalid Visa Record ID.</p></div>';
        return;
    }

    $table_visas     = $wpdb->prefix . 'iterp_visas';
    $table_customers = $wpdb->prefix . 'iterp_customers';

    $query = $wpdb->prepare( "
        SELECT v.*, c.full_name, c.mobile, c.passport_no, c.passport_expiry, c.email 
        FROM $table_visas v
        LEFT JOIN $table_customers c ON v.customer_id = c.id
        WHERE v.id = %d
    ", $id );
    
    $visa = $wpdb->get_row( $query );

    if ( ! $visa ) {
        echo '<div class="notice notice-error"><p>Visa file not found.</p></div>';
        return;
    }
    ?>
    <div style="display: grid; grid-template-columns: 300px 1fr; gap: 25px; margin-top: 20px;">
        
        <!-- Left Summary Card -->
        <div style="background: #fff; padding: 25px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center; height: fit-content;">
            <span class="dashicons dashicons-admin-site-alt3" style="font-size: 48px; width: 48px; height: 48px; color: #003376;"></span>
            <h3 style="margin: 10px 0 5px 0; font-size: 22px; color: #0f172a;"><?php echo esc_html( $visa->country ); ?></h3>
            <p style="margin: 0 0 15px 0; color: #64748b; font-size: 13px;">#VSA-<?php echo esc_html( $visa->id ); ?> (<?php echo esc_html( $visa->visa_type ); ?>)</p>
            
            <span style="background: #e0f2fe; color: #0369a1; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; margin-bottom: 20px;">
                <?php echo esc_html( $visa->status ); ?>
            </span>

            <div style="border-top: 1px solid #e2e8f0; padding-top: 15px; text-align: left;">
                <a href="<?php echo admin_url( 'admin.php?page=ifs_travel_erp&tab=visa&sub=edit&id=' . $id ); ?>" style="display: block; background: #f8fafc; border: 1px solid #cbd5e1; padding: 8px 12px; text-align: center; border-radius: 4px; text-decoration: none; color: #0f172a; font-weight: 600;">
                    Edit Application
                </a>
            </div>
        </div>

        <!-- Right Detailed Info -->
        <div style="background: #fff; padding: 25px; border-radius: 8px; border: 1px solid #e2e8f0;">
            <h3 style="margin-top: 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; color: #003376;">Applicant Information</h3>
            
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 25px;">
                <tr>
                    <td style="padding: 8px 0; color: #64748b; width: 35%;">Applicant Name:</td>
                    <td style="padding: 8px 0; font-weight: 600; color: #0f172a;"><?php echo esc_html( $visa->full_name ?: 'Direct Client' ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Mobile:</td>
                    <td style="padding: 8px 0; font-weight: 600;"><?php echo esc_html( $visa->mobile ?: 'N/A' ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Passport Number:</td>
                    <td style="padding: 8px 0; font-weight: 700; font-family: monospace;"><?php echo esc_html( $visa->passport_no ?: 'N/A' ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Passport Expiry:</td>
                    <td style="padding: 8px 0;"><?php echo ( $visa->passport_expiry != '1970-01-01' ) ? date('d M, Y', strtotime($visa->passport_expiry)) : 'N/A'; ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Submission Date:</td>
                    <td style="padding: 8px 0; font-weight: 600;"><?php echo ( $visa->submission_date != '1970-01-01' ) ? date('d F Y', strtotime($visa->submission_date)) : 'Not Submitted'; ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Expected Delivery:</td>
                    <td style="padding: 8px 0; font-weight: 600; color: #0369a1;"><?php echo ( $visa->expected_delivery != '1970-01-01' ) ? date('d F Y', strtotime($visa->expected_delivery)) : 'TBD'; ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b; vertical-align: top;">Documents Received:</td>
                    <td style="padding: 8px 0; font-size: 13px; color: #334155;"><?php echo nl2br( esc_html($visa->documents_collected ?: 'None recorded') ); ?></td>
                </tr>
            </table>

            <h3 style="margin-top: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; color: #003376;">Financials</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; color: #64748b; width: 35%;">Govt/Supplier Cost:</td>
                    <td style="padding: 8px 0; font-weight: 600; color: #475569;">৳<?php echo number_format( $visa->buy_price, 2 ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Client Fee:</td>
                    <td style="padding: 8px 0; font-weight: 700; color: #0f172a;">৳<?php echo number_format( $visa->sell_price, 2 ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Net Margin:</td>
                    <td style="padding: 8px 0; font-weight: 800; color: <?php echo ($visa->profit >= 0) ? '#166534' : '#dc2626'; ?>;">
                        ৳<?php echo number_format( $visa->profit, 2 ); ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <?php
}