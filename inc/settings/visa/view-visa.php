<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_visa_view_panel' ) ) {
    /**
     * Complete Visa Advisory & Requirement Dossier View Panel
     */
    function ifs_terp_visa_view_panel() {
        global $wpdb;
        $table_reqs = $wpdb->prefix . 'iterp_visa_requirements';
        $base_url   = admin_url( 'admin.php?page=ifs_travel_erp&tab=settings&sub=visa_req' );
        $req_id     = isset( $_GET['req_id'] ) ? absint( wp_unslash( $_GET['req_id'] ) ) : 0;

        $req = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_reqs} WHERE id = %d", $req_id ) );

        if ( ! $req ) {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'Visa requirement configuration not found.', 'ifs-travel-erp' ) . '</p></div>';
            return;
        }

        // Currency and Symbols
        $currency      = esc_html( $req->fee_currency ?? 'BDT' );
        $currency_syms = array( 'BDT' => '৳', 'USD' => '$', 'EUR' => '€', 'SAR' => '﷼', 'AED' => 'د.إ' );
        $symbol        = $currency_syms[ $currency ] ?? $currency . ' ';

        $emb_f = (float) ( $req->embassy_fee ?? ( (float) ( $req->standard_fee ?? 0 ) * 0.8 ) );
        $svc_f = (float) ( $req->service_fee ?? ( (float) ( $req->standard_fee ?? 0 ) * 0.2 ) );
        $tot_f = (float) ( $req->standard_fee ?? ( $emb_f + $svc_f ) );

        $checklist_items = array();
        if ( ! empty( $req->requirements_list ) ) {
            $decoded = json_decode( $req->requirements_list, true );
            if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
                $checklist_items = $decoded;
            } else {
                $checklist_items = array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", '', $req->requirements_list ) ) ) );
            }
        }
        ?>
        <div class="wrap" style="max-width: 1200px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #0f172a;">
            
            <!-- Top Navigation & Action Strip -->
            <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <a href="<?php echo esc_url( add_query_arg( 'action_sub', 'all', $base_url ) ); ?>" 
                   style="display: inline-flex; align-items: center; gap: 6px; background: #ffffff; color: #475569; border: 1px solid #cbd5e1; height: 42px; padding: 0 16px; border-radius: 8px; font-weight: 700; font-size: 13px; text-decoration: none; box-sizing: border-box;">
                    <span class="dashicons dashicons-arrow-left-alt2" style="font-size: 16px; width: 16px; height: 16px;"></span>
                    <span><?php esc_html_e( 'Back to All Policies', 'ifs-travel-erp' ); ?></span>
                </a>

                <div style="display: flex; gap: 8px; align-items: center;">
                    <a href="<?php echo esc_url( add_query_arg( array( 'action_sub' => 'edit', 'req_id' => $req->id ), $base_url ) ); ?>" 
                       style="display: inline-flex; align-items: center; gap: 6px; background: #003376; color: #ffffff; border: 1px solid #003376; height: 42px; padding: 0 18px; border-radius: 8px; font-weight: 700; font-size: 13px; text-decoration: none; box-sizing: border-box;">
                        <span class="dashicons dashicons-edit" style="font-size: 16px; width: 16px; height: 16px;"></span>
                        <span><?php esc_html_e( 'Edit Requirement', 'ifs-travel-erp' ); ?></span>
                    </a>

                    <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action_sub' => 'delete', 'req_id' => $req->id ), $base_url ), 'delete_visa_req_' . $req->id ) ); ?>" 
                       onclick="return confirm('<?php echo esc_js( __( 'Delete this visa policy permanently?', 'ifs-travel-erp' ) ); ?>');" 
                       style="display: inline-flex; align-items: center; gap: 6px; background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; height: 42px; padding: 0 16px; border-radius: 8px; font-weight: 700; font-size: 13px; text-decoration: none; box-sizing: border-box;">
                        <span class="dashicons dashicons-trash" style="font-size: 16px; width: 16px; height: 16px;"></span>
                        <span><?php esc_html_e( 'Delete', 'ifs-travel-erp' ); ?></span>
                    </a>
                </div>
            </div>

            <!-- Main Container Card -->
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden;">
                
                <!-- Hero Header Banner -->
                <div style="background: linear-gradient(135deg, #0b1329 0%, #1e293b 100%); padding: 30px; color: #ffffff; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 8px; flex-wrap: wrap;">
                            <span style="background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase;">
                                <?php echo esc_html( $req->visa_type ); ?>
                            </span>
                            <span style="background: rgba(255, 255, 255, 0.1); color: #e2e8f0; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700;">
                                <?php echo esc_html( $req->entry_type ?? 'Single Entry' ); ?>
                            </span>
                            <span style="background: rgba(74, 222, 128, 0.15); color: #4ade80; border: 1px solid rgba(74, 222, 128, 0.3); padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; font-family: ui-monospace, monospace;">
                                <?php echo esc_html( $currency ); ?>
                            </span>
                        </div>
                        <h1 style="margin: 0; font-size: 26px; font-weight: 900; color: #ffffff; letter-spacing: -0.3px; text-transform: uppercase;"><?php echo esc_html( $req->country_name ); ?></h1>
                        <p style="margin: 6px 0 0 0; font-size: 13.5px; color: #94a3b8; display: flex; align-items: center; gap: 6px;">
                            <span class="dashicons dashicons-networking" style="font-size: 15px; width: 15px; height: 15px; color: #38bdf8;"></span>
                            <?php esc_html_e( 'Submission Mode:', 'ifs-travel-erp' ); ?> <strong style="color: #ffffff;"><?php echo esc_html( $req->submission_mode ?? 'VFS / Biometric In-Person' ); ?></strong>
                        </p>
                    </div>
                    
                    <div style="text-align: right; background: rgba(255, 255, 255, 0.06); border: 1px solid rgba(255, 255, 255, 0.1); padding: 12px 18px; border-radius: 10px;">
                        <span style="font-size: 10.5px; font-weight: 700; color: #94a3b8; text-transform: uppercase; display: block; letter-spacing: 0.5px;"><?php esc_html_e( 'Turnaround & Validity', 'ifs-travel-erp' ); ?></span>
                        <span style="font-size: 13px; font-weight: 800; color: #ffffff; display: block; margin-top: 2px;">
                            <span style="color: #38bdf8;"><?php echo esc_html( $req->processing_time ?: 'Standard' ); ?></span> &bull; <span style="color: #4ade80;"><?php echo esc_html( $req->stay_validity ?: '30 Days' ); ?></span>
                        </span>
                    </div>
                </div>

                <!-- Content Body Area -->
                <div style="padding: 30px;">

                    <!-- Financial Metric Cards -->
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 28px;">
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; display: flex; align-items: center; gap: 14px;">
                            <div style="width: 42px; height: 42px; border-radius: 10px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                                <span class="dashicons dashicons-bank"></span>
                            </div>
                            <div>
                                <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px;"><?php esc_html_e( 'Embassy / Govt Fee', 'ifs-travel-erp' ); ?></span>
                                <h3 style="margin: 2px 0 0 0; font-family: ui-monospace, monospace; font-size: 20px; font-weight: 900; color: #0f172a;"><?php echo esc_html( $symbol . number_format( $emb_f, 2 ) ); ?></h3>
                            </div>
                        </div>

                        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 18px; display: flex; align-items: center; gap: 14px;">
                            <div style="width: 42px; height: 42px; border-radius: 10px; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                                <span class="dashicons dashicons-money-alt"></span>
                            </div>
                            <div>
                                <span style="font-size: 11px; font-weight: 700; color: #166534; text-transform: uppercase; letter-spacing: 0.3px;"><?php esc_html_e( 'Agency Service Charge', 'ifs-travel-erp' ); ?></span>
                                <h3 style="margin: 2px 0 0 0; font-family: ui-monospace, monospace; font-size: 20px; font-weight: 900; color: #059669;"><?php echo esc_html( $symbol . number_format( $svc_f, 2 ) ); ?></h3>
                            </div>
                        </div>

                        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 18px; display: flex; align-items: center; gap: 14px;">
                            <div style="width: 42px; height: 42px; border-radius: 10px; background: #dbeafe; color: #003376; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                                <span class="dashicons dashicons-calculator"></span>
                            </div>
                            <div>
                                <span style="font-size: 11px; font-weight: 700; color: #1e40af; text-transform: uppercase; letter-spacing: 0.3px;"><?php esc_html_e( 'Total Quoted Fare', 'ifs-travel-erp' ); ?></span>
                                <h3 style="margin: 2px 0 0 0; font-family: ui-monospace, monospace; font-size: 20px; font-weight: 900; color: #003376;"><?php echo esc_html( $symbol . number_format( $tot_f, 2 ) ); ?></h3>
                            </div>
                        </div>
                    </div>

                    <!-- Consular, Biometric & Application Rules Grid -->
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px; margin-bottom: 24px;">
                        <h4 style="margin: 0 0 16px 0; font-size: 13.5px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.4px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                            <span class="dashicons dashicons-media-spreadsheet" style="color: #003376;"></span> <?php esc_html_e( 'Consular, Passport & Biometric Setup', 'ifs-travel-erp' ); ?>
                        </h4>
                        
                        <ul style="margin: 0; padding: 0; list-style: none; font-size: 13px; color: #334155; line-height: 2.1;">
                            <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                <span style="color: #64748b;"><?php esc_html_e( 'Consular / Submission Center:', 'ifs-travel-erp' ); ?></span>
                                <strong style="color: #0f172a;"><?php echo esc_html( $req->consular_jurisdiction ?: __( 'Not specified', 'ifs-travel-erp' ) ); ?></strong>
                            </li>
                            <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                <span style="color: #64748b;"><?php esc_html_e( 'Passport Validity Required:', 'ifs-travel-erp' ); ?></span>
                                <strong style="color: #0f172a;"><?php echo esc_html( $req->passport_validity_req ?? __( 'Minimum 6 Months', 'ifs-travel-erp' ) ); ?></strong>
                            </li>
                            <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                <span style="color: #64748b;"><?php esc_html_e( 'Photo Specifications:', 'ifs-travel-erp' ); ?></span>
                                <strong style="color: #0f172a;"><?php echo esc_html( $req->photo_spec ?? __( '35x45mm, White Background, Matte', 'ifs-travel-erp' ) ); ?></strong>
                            </li>
                            <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                <span style="color: #64748b;"><?php esc_html_e( 'Biometric Rules & Exemptions:', 'ifs-travel-erp' ); ?></span>
                                <strong style="color: #0f172a;"><?php echo esc_html( $req->biometric_rules ?? __( 'Mandatory in-person', 'ifs-travel-erp' ) ); ?></strong>
                            </li>
                            <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                <span style="color: #64748b;"><?php esc_html_e( 'Submission Channel:', 'ifs-travel-erp' ); ?></span>
                                <strong style="color: #0f172a;"><?php echo esc_html( $req->submission_mode ?? 'VFS / Biometric In-Person' ); ?></strong>
                            </li>
                            <li style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                <span style="color: #64748b;"><?php esc_html_e( 'Entry Specification:', 'ifs-travel-erp' ); ?></span>
                                <strong style="color: #003376;"><?php echo esc_html( $req->entry_type ?? 'Single Entry' ); ?></strong>
                            </li>
                            <?php if ( ! empty( $req->official_portal_url ) ) : ?>
                                <li style="display: flex; justify-content: space-between; padding-top: 4px; align-items: center;">
                                    <span style="color: #64748b;"><?php esc_html_e( 'Official Visa / Appointment Portal:', 'ifs-travel-erp' ); ?></span>
                                    <a href="<?php echo esc_url( $req->official_portal_url ); ?>" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 4px; background: #ffffff; color: #003376; border: 1px solid #cbd5e1; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; text-decoration: none;">
                                        <span class="dashicons dashicons-external" style="font-size: 13px; width: 13px; height: 13px;"></span> <?php esc_html_e( 'Open Portal', 'ifs-travel-erp' ); ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- Mandatory Document Checklist Box -->
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 22px; margin-bottom: 24px;">
                        <h4 style="margin: 0 0 14px 0; font-size: 13.5px; font-weight: 800; color: #166534; text-transform: uppercase; letter-spacing: 0.4px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #bbf7d0; padding-bottom: 10px;">
                            <span class="dashicons dashicons-yes-alt" style="color: #16a34a;"></span> <?php esc_html_e( 'Mandatory Paperwork Checklist', 'ifs-travel-erp' ); ?>
                        </h4>
                        <?php if ( ! empty( $checklist_items ) ) : ?>
                            <ul style="margin: 0; padding-left: 18px; font-size: 13px; color: #1e293b; line-height: 2.0;">
                                <?php foreach ( $checklist_items as $item ) : ?>
                                    <li><?php echo esc_html( $item ); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else : ?>
                            <p style="margin: 0; font-size: 12.5px; color: #64748b; font-style: italic;"><?php esc_html_e( 'No checklist items configured for this destination.', 'ifs-travel-erp' ); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Internal Processing Notes Box -->
                    <?php if ( ! empty( $req->admin_notes ) ) : ?>
                        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 22px;">
                            <h4 style="margin: 0 0 8px 0; font-size: 13.5px; font-weight: 800; color: #b45309; text-transform: uppercase; letter-spacing: 0.4px; display: flex; align-items: center; gap: 8px;">
                                <span class="dashicons dashicons-lock" style="color: #d97706;"></span> <?php esc_html_e( 'Internal Processing Notes', 'ifs-travel-erp' ); ?>
                            </h4>
                            <p style="margin: 0; font-size: 12.5px; color: #78350f; line-height: 1.5;">
                                <?php echo nl2br( esc_html( $req->admin_notes ) ); ?>
                            </p>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
        <?php
    }
}