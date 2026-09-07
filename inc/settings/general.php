<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_settings_general_panel() {
    $message = '';
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_general_settings_submit'] ) ) {
        check_admin_referer( 'ifs_general_settings_action', 'ifs_general_settings_nonce' );

        $agency_name    = isset( $_POST['agency_name'] ) ? sanitize_text_field( wp_unslash( $_POST['agency_name'] ) ) : '';
        $agency_phone   = isset( $_POST['agency_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['agency_phone'] ) ) : '';
        $agency_email   = isset( $_POST['agency_email'] ) ? sanitize_email( wp_unslash( $_POST['agency_email'] ) ) : '';
        $agency_address = isset( $_POST['agency_address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['agency_address'] ) ) : '';
        $currency_sym   = isset( $_POST['currency_symbol'] ) ? sanitize_text_field( wp_unslash( $_POST['currency_symbol'] ) ) : '৳';

        update_option( 'ifs_erp_agency_name', $agency_name );
        update_option( 'ifs_erp_agency_phone', $agency_phone );
        update_option( 'ifs_erp_agency_email', $agency_email );
        update_option( 'ifs_erp_agency_address', $agency_address );
        update_option( 'ifs_erp_currency_symbol', $currency_sym );

        $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Agency profile settings updated successfully.', 'ifs-travel-erp' ) . '</div>';
    }

    $agency_name    = get_option( 'ifs_erp_agency_name', 'IFS Travel & Tours' );
    $agency_phone   = get_option( 'ifs_erp_agency_phone', '+880 1700-000000' );
    $agency_email   = get_option( 'ifs_erp_agency_email', 'support@infinityflamesoft.com' );
    $agency_address = get_option( 'ifs_erp_agency_address', 'Motijheel C/A, Dhaka-1000, Bangladesh' );
    $currency_sym   = get_option( 'ifs_erp_currency_symbol', '৳' );
    ?>
    <div class="wrap" style="max-width: 900px; margin: 0 auto;">
        <?php echo wp_kses_post( $message ); ?>
        <form method="post" action="">
            <?php wp_nonce_field( 'ifs_general_settings_action', 'ifs_general_settings_nonce' ); ?>
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 26px;">
                <h3 style="margin-top: 0; font-size: 15px; font-weight: 800; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;"><?php esc_html_e( 'Corporate Identity &amp; Branding', 'ifs-travel-erp' ); ?></h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-top: 18px;">
                    <div>
                        <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Agency / Company Name *', 'ifs-travel-erp' ); ?></label>
                        <input type="text" name="agency_name" required value="<?php echo esc_attr( $agency_name ); ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Official Support Phone *', 'ifs-travel-erp' ); ?></label>
                        <input type="text" name="agency_phone" required value="<?php echo esc_attr( $agency_phone ); ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-family: monospace;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Official Support Email *', 'ifs-travel-erp' ); ?></label>
                        <input type="email" name="agency_email" required value="<?php echo esc_attr( $agency_email ); ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'System Currency Symbol', 'ifs-travel-erp' ); ?></label>
                        <input type="text" name="currency_symbol" value="<?php echo esc_attr( $currency_sym ); ?>" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-family: monospace;">
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;"><?php esc_html_e( 'Corporate Office Address', 'ifs-travel-erp' ); ?></label>
                        <textarea name="agency_address" rows="3" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px;"><?php echo esc_textarea( $agency_address ); ?></textarea>
                    </div>
                </div>

                <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid #f1f5f9; text-align: right;">
                    <button type="submit" name="ifs_general_settings_submit" style="background: #003376; color: #ffffff; border: none; height: 42px; padding: 0 24px; border-radius: 8px; font-weight: 700; cursor: pointer;"><?php esc_html_e( 'Save Settings', 'ifs-travel-erp' ); ?></button>
                </div>
            </div>
        </form>
    </div>
    <?php
}