<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function ifs_terp_customer_view_page() {
    global $wpdb;
    $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

    if ( ! $id ) {
        echo '<div class="notice notice-error"><p>Invalid Customer ID.</p></div>';
        return;
    }

    $table_name = $wpdb->prefix . 'iterp_customers';
    $customer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ) );

    if ( ! $customer ) {
        echo '<div class="notice notice-error"><p>Customer not found.</p></div>';
        return;
    }

    $expiry_date = strtotime( $customer->passport_expiry );
    $today = strtotime( date('Y-m-d') );
    $days_left = ($expiry_date - $today) / (60 * 60 * 24);
    ?>

    <div style="display: grid; grid-template-columns: 300px 1fr; gap: 25px; margin-top: 20px;">
        <!-- Left Box: Profile Card -->
        <div style="background: #fff; padding: 25px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center; height: fit-content;">
            <div style="width: 80px; height: 80px; background: #003376; color: #fff; font-size: 32px; font-weight: bold; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto;">
                <?php echo esc_html( strtoupper( substr( $customer->full_name, 0, 1 ) ) ); ?>
            </div>
            <h3 style="margin: 0 0 5px 0; font-size: 20px; color: #0f172a;"><?php echo esc_html( $customer->full_name ); ?></h3>
            <p style="margin: 0 0 15px 0; color: #64748b; font-size: 13px;">#CUS-<?php echo esc_html( $customer->id ); ?></p>
            <span style="background: #e0f2fe; color: #0369a1; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; margin-bottom: 20px;">
                <?php echo esc_html( $customer->client_type ); ?>
            </span>

            <div style="border-top: 1px solid #e2e8f0; padding-top: 15px; text-align: left;">
                <a href="<?php echo admin_url( 'admin.php?page=ifs_travel_erp&tab=customers&sub=edit&id=' . $id ); ?>" style="display: block; background: #f8fafc; border: 1px solid #cbd5e1; padding: 8px 12px; text-align: center; border-radius: 4px; text-decoration: none; color: #0f172a; font-weight: 600; margin-bottom: 8px;">
                    Edit Profile
                </a>
            </div>
        </div>

        <!-- Right Box: Details & History -->
        <div style="background: #fff; padding: 25px; border-radius: 8px; border: 1px solid #e2e8f0;">
            <h3 style="margin-top: 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; color: #003376;">Contact & Passport Information</h3>
            
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 25px;">
                <tr>
                    <td style="padding: 10px 0; color: #64748b; width: 35%;">Mobile Number:</td>
                    <td style="padding: 10px 0; font-weight: 600; color: #0f172a;"><?php echo esc_html( $customer->mobile ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; color: #64748b;">Email Address:</td>
                    <td style="padding: 10px 0; font-weight: 600; color: #0f172a;"><?php echo esc_html( $customer->email ?: 'N/A' ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; color: #64748b;">Passport No:</td>
                    <td style="padding: 10px 0; font-weight: 600; color: #0f172a;"><?php echo esc_html( $customer->passport_no ?: 'N/A' ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; color: #64748b;">Passport Expiry:</td>
                    <td style="padding: 10px 0; font-weight: 600; <?php echo ($days_left < 0) ? 'color: #dc2626;' : (($days_left <= 180) ? 'color: #d97706;' : 'color: #16a34a;'); ?>">
                        <?php echo ( $customer->passport_expiry != '1970-01-01' ) ? date('d M, Y', strtotime($customer->passport_expiry)) : 'N/A'; ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; color: #64748b;">Address:</td>
                    <td style="padding: 10px 0; color: #0f172a;"><?php echo esc_html( $customer->address ?: 'N/A' ); ?></td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; color: #64748b;">Member Since:</td>
                    <td style="padding: 10px 0; color: #0f172a;"><?php echo date('d M Y, h:i A', strtotime($customer->created_at)); ?></td>
                </tr>
            </table>
        </div>
    </div>
    <?php
}