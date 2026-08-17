<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_settings_api_page() {
    $message = '';

    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_save_api_settings'] ) ) {
        check_admin_referer( 'ifs_api_settings_action', 'ifs_api_settings_nonce' );

        update_option( 'iterp_sabre_pcc', sanitize_text_field( $_POST['sabre_pcc'] ) );
        update_option( 'iterp_sabre_api_key', sanitize_text_field( $_POST['sabre_api_key'] ) );
        update_option( 'iterp_sms_gateway_url', sanitize_text_field( $_POST['sms_gateway_url'] ) );
        update_option( 'iterp_sms_api_key', sanitize_text_field( $_POST['sms_api_key'] ) );
        update_option( 'iterp_sms_sender_id', sanitize_text_field( $_POST['sms_sender_id'] ) );

        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            ifs_terp_log_activity( "Updated Third-Party API & SMS Gateway Configurations" );
        }

        $message = '<div class="notice notice-success is-dismissible"><p>API Configurations updated successfully.</p></div>';
    }

    $sabre_pcc       = get_option( 'iterp_sabre_pcc', '' );
    $sabre_api_key   = get_option( 'iterp_sabre_api_key', '' );
    $sms_gateway_url = get_option( 'iterp_sms_gateway_url', 'http://bulksmsbd.net/api/smsapi' );
    $sms_api_key     = get_option( 'iterp_sms_api_key', '' );
    $sms_sender_id   = get_option( 'iterp_sms_sender_id', '8809612XXXXXX' );
    ?>

    <?php echo $message; ?>

    <div class="arms-form-container" style="background:#fff; padding:25px; border-radius:8px; border:1px solid #e2e8f0; max-width: 900px; margin-top: 20px;">
        <form method="post" action="">
            <?php wp_nonce_field( 'ifs_api_settings_action', 'ifs_api_settings_nonce' ); ?>

            <!-- GDS Setup -->
            <h3 style="margin-top:0; color:#003376; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">
                <span class="dashicons dashicons-airplane"></span> Global Distribution System (GDS Web-Services)
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Sabre / Amadeus PCC (Pseudo City Code)</label>
                    <input type="text" name="sabre_pcc" value="<?php echo esc_attr( $sabre_pcc ); ?>" placeholder="e.g. 7X9K" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>
                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">GDS REST API Key / Token</label>
                    <input type="password" name="sabre_api_key" value="<?php echo esc_attr( $sabre_api_key ); ?>" placeholder="••••••••••••••••" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>
            </div>

            <!-- SMS Notification Gateway -->
            <h3 style="margin-top:0; color:#003376; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">
                <span class="dashicons dashicons-email-alt"></span> BD Local Bulk SMS Gateway (On-Booking SMS)
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                <div style="grid-column: 1 / -1;">
                    <label style="display:block; font-weight:600; margin-bottom:5px;">SMS Gateway API Endpoint URL</label>
                    <input type="text" name="sms_gateway_url" value="<?php echo esc_attr( $sms_gateway_url ); ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">SMS API Key</label>
                    <input type="password" name="sms_api_key" value="<?php echo esc_attr( $sms_api_key ); ?>" placeholder="••••••••••••••••" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Approved Sender ID (Masking / Non-Masking)</label>
                    <input type="text" name="sms_sender_id" value="<?php echo esc_attr( $sms_sender_id ); ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>
            </div>

            <div style="margin-top: 25px; border-top:1px solid #e2e8f0; padding-top:15px; text-align:right;">
                <button type="submit" name="ifs_save_api_settings" style="background: #003376; color: #fff; border: none; padding: 10px 24px; border-radius: 4px; font-weight: 600; cursor: pointer;">
                    Save API Configurations
                </button>
            </div>
        </form>
    </div>
    <?php
}