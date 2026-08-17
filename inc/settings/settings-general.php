<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_settings_general_page() {
    $message = '';

    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_save_general_settings'] ) ) {
        check_admin_referer( 'ifs_general_settings_action', 'ifs_general_settings_nonce' );

        update_option( 'iterp_agency_name', sanitize_text_field( $_POST['agency_name'] ) );
        update_option( 'iterp_agency_email', sanitize_email( $_POST['agency_email'] ) );
        update_option( 'iterp_agency_phone', sanitize_text_field( $_POST['agency_phone'] ) );
        update_option( 'iterp_agency_address', sanitize_textarea_field( $_POST['agency_address'] ) );
        update_option( 'iterp_currency_symbol', sanitize_text_field( $_POST['currency_symbol'] ) );
        update_option( 'iterp_invoice_footer_note', sanitize_textarea_field( $_POST['invoice_footer_note'] ) );
        update_option( 'iterp_agency_logo_url', esc_url_raw( $_POST['agency_logo_url'] ) );

        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            ifs_terp_log_activity( "Updated Agency Profile & Branding Settings" );
        }

        $message = '<div class="notice notice-success is-dismissible"><p>Settings updated successfully.</p></div>';
    }

    $agency_name     = get_option( 'iterp_agency_name', get_bloginfo('name') );
    $agency_email    = get_option( 'iterp_agency_email', get_bloginfo('admin_email') );
    $agency_phone    = get_option( 'iterp_agency_phone', '+880 1700-000000' );
    $agency_address  = get_option( 'iterp_agency_address', 'Dhaka, Bangladesh' );
    $currency_symbol = get_option( 'iterp_currency_symbol', '৳' );
    $invoice_footer  = get_option( 'iterp_invoice_footer_note', 'Thank you for booking with us. Have a safe flight!' );
    $agency_logo_url = get_option( 'iterp_agency_logo_url', '' );
    ?>

    <?php echo $message; ?>

    <div class="arms-form-container" style="background:#fff; padding:25px; border-radius:8px; border:1px solid #e2e8f0; max-width: 900px; margin-top: 20px;">
        <h3 style="margin-top:0; color:#003376; border-bottom:1px solid #e2e8f0; padding-bottom:12px;">
            <span class="dashicons dashicons-building"></span> Agency Profile & Print Invoice Branding
        </h3>

        <form method="post" action="">
            <?php wp_nonce_field( 'ifs_general_settings_action', 'ifs_general_settings_nonce' ); ?>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Agency / Business Name *</label>
                    <input type="text" name="agency_name" required value="<?php echo esc_attr( $agency_name ); ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Official Contact Email *</label>
                    <input type="email" name="agency_email" required value="<?php echo esc_attr( $agency_email ); ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Hotline / Phone Number *</label>
                    <input type="text" name="agency_phone" required value="<?php echo esc_attr( $agency_phone ); ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Default Currency Symbol</label>
                    <input type="text" name="currency_symbol" value="<?php echo esc_attr( $currency_symbol ); ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>

                <div style="grid-column: 1 / -1;">
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Logo Image URL (for Printable Invoices)</label>
                    <input type="text" name="agency_logo_url" value="<?php echo esc_attr( $agency_logo_url ); ?>" placeholder="https://example.com/logo.png" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>

                <div style="grid-column: 1 / -1;">
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Official Office Address (Shows on Invoices & Money Receipts)</label>
                    <textarea name="agency_address" rows="2" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;"><?php echo esc_textarea( $agency_address ); ?></textarea>
                </div>

                <div style="grid-column: 1 / -1;">
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Invoice Footer Note / Terms</label>
                    <textarea name="invoice_footer_note" rows="2" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;"><?php echo esc_textarea( $invoice_footer ); ?></textarea>
                </div>
            </div>

            <div style="margin-top: 25px; border-top:1px solid #e2e8f0; padding-top:15px; text-align:right;">
                <button type="submit" name="ifs_save_general_settings" style="background: #003376; color: #fff; border: none; padding: 10px 24px; border-radius: 4px; font-weight: 600; cursor: pointer;">
                    Save Configurations
                </button>
            </div>
        </form>
    </div>
    <?php
}