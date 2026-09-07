<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_agent_add_edit_page' ) ) {
    /**
     * Enterprise B2B Sub-Agent Onboarding & Profile Management
     * Flat Minimal UI: No Shadows, Strict 42px Equal Field Heights, Normalized Controls
     */
    function ifs_terp_agent_add_edit_page() {
        global $wpdb;
        $table_agents = $wpdb->prefix . 'iterp_agents';
        
        if ( function_exists( 'wp_enqueue_media' ) ) {
            wp_enqueue_media();
        }
        
        $id      = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
        $is_edit = ( $id > 0 );
        $message = '';

        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_agent_submit'] ) ) {
            check_admin_referer( 'ifs_agent_save_action', 'ifs_agent_nonce' );

            $agency_name       = isset( $_POST['agency_name'] ) ? sanitize_text_field( wp_unslash( $_POST['agency_name'] ) ) : '';
            $contact_person    = isset( $_POST['contact_person'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_person'] ) ) : '';
            $designation       = isset( $_POST['designation'] ) ? sanitize_text_field( wp_unslash( $_POST['designation'] ) ) : 'Proprietor';
            $mobile            = isset( $_POST['mobile'] ) ? sanitize_text_field( wp_unslash( $_POST['mobile'] ) ) : '';
            $whatsapp_no       = isset( $_POST['whatsapp_no'] ) ? sanitize_text_field( wp_unslash( $_POST['whatsapp_no'] ) ) : '';
            $accounts_mobile   = isset( $_POST['accounts_mobile'] ) ? sanitize_text_field( wp_unslash( $_POST['accounts_mobile'] ) ) : '';
            $email             = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
            $city              = isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '';
            $address           = isset( $_POST['address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['address'] ) ) : '';
            
            $trade_license_no  = isset( $_POST['trade_license_no'] ) ? sanitize_text_field( wp_unslash( $_POST['trade_license_no'] ) ) : '';
            $owner_nid         = isset( $_POST['owner_nid'] ) ? sanitize_text_field( wp_unslash( $_POST['owner_nid'] ) ) : '';
            $security_cheque   = isset( $_POST['security_cheque'] ) ? sanitize_text_field( wp_unslash( $_POST['security_cheque'] ) ) : '';
            $bank_details      = isset( $_POST['bank_details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['bank_details'] ) ) : '';
            
            $agency_tier       = isset( $_POST['agency_tier'] ) ? sanitize_text_field( wp_unslash( $_POST['agency_tier'] ) ) : 'Standard Partner';
            $commission_mode   = isset( $_POST['commission_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['commission_mode'] ) ) : 'Percentage';
            $commission_rate   = isset( $_POST['commission_rate'] ) ? (float) wp_unslash( $_POST['commission_rate'] ) : 0;
            $credit_limit      = isset( $_POST['credit_limit'] ) ? (float) wp_unslash( $_POST['credit_limit'] ) : 0;
            $credit_threshold  = isset( $_POST['credit_threshold'] ) ? intval( wp_unslash( $_POST['credit_threshold'] ) ) : 80;
            $status            = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'Active';
            
            $logo_url          = isset( $_POST['logo_url'] ) ? esc_url_raw( wp_unslash( $_POST['logo_url'] ) ) : '';
            $agreement_doc_url = isset( $_POST['agreement_doc_url'] ) ? esc_url_raw( wp_unslash( $_POST['agreement_doc_url'] ) ) : '';

            $data = array(
                'agency_name'       => $agency_name,
                'contact_person'    => $contact_person,
                'designation'       => $designation,
                'mobile'            => $mobile,
                'whatsapp_no'       => $whatsapp_no,
                'accounts_mobile'   => $accounts_mobile,
                'email'             => $email,
                'city'              => $city,
                'address'           => $address,
                'trade_license_no'  => $trade_license_no,
                'owner_nid'         => $owner_nid,
                'security_cheque'   => $security_cheque,
                'bank_details'      => $bank_details,
                'agency_tier'       => $agency_tier,
                'commission_mode'   => $commission_mode,
                'commission_rate'   => $commission_rate,
                'credit_limit'      => $credit_limit,
                'credit_threshold'  => $credit_threshold,
                'logo_url'          => $logo_url,
                'agreement_doc_url' => $agreement_doc_url,
                'status'            => $status,
            );

            if ( empty( $agency_name ) ) {
                $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> ' . esc_html__( 'Agency name is required.', 'ifs-travel-erp' ) . '</div>';
            } elseif ( empty( $mobile ) ) {
                $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> ' . esc_html__( 'Official mobile number is required.', 'ifs-travel-erp' ) . '</div>';
            } else {
                if ( ! $is_edit ) {
                    $opening_bal             = isset( $_POST['opening_balance'] ) ? (float) wp_unslash( $_POST['opening_balance'] ) : 0;
                    $data['current_balance'] = $opening_bal;
                    $data['created_at']      = current_time( 'mysql' );
                    
                    $inserted = $wpdb->insert( $table_agents, $data );

                    if ( $inserted ) {
                        $id      = $wpdb->insert_id;
                        $is_edit = true;

                        if ( function_exists( 'ifs_terp_log_activity' ) ) {
                            ifs_terp_log_activity( "Registered B2B Agent: {$agency_name} (#AGT-{$id})" );
                        }

                        $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'B2B sub-agent registered successfully.', 'ifs-travel-erp' ) . '</div>';
                    } else {
                        $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Database error: Unable to register sub-agent.', 'ifs-travel-erp' ) . '</div>';
                    }
                } else {
                    $updated = $wpdb->update( $table_agents, $data, array( 'id' => $id ) );

                    if ( false !== $updated ) {
                        if ( function_exists( 'ifs_terp_log_activity' ) ) {
                            ifs_terp_log_activity( "Updated B2B Agent: {$agency_name} (#AGT-{$id})" );
                        }
                        $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Sub-agent profile updated successfully.', 'ifs-travel-erp' ) . '</div>';
                    } else {
                        $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Database error: Unable to update profile.', 'ifs-travel-erp' ) . '</div>';
                    }
                }
            }
        }

        $row = false;
        if ( $is_edit ) {
            $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_agents} WHERE id = %d", $id ) );
        }

        // Form Field Defaults
        $val_name     = $is_edit && $row ? esc_attr( $row->agency_name ) : '';
        $val_contact  = $is_edit && $row ? esc_attr( $row->contact_person ) : '';
        $val_desig    = $is_edit && $row && isset( $row->designation ) ? esc_attr( $row->designation ) : 'Proprietor';
        $val_mobile   = $is_edit && $row ? esc_attr( $row->mobile ) : '';
        $val_whatsapp = $is_edit && $row && isset( $row->whatsapp_no ) ? esc_attr( $row->whatsapp_no ) : '';
        $val_accounts = $is_edit && $row && isset( $row->accounts_mobile ) ? esc_attr( $row->accounts_mobile ) : '';
        $val_email    = $is_edit && $row ? esc_attr( $row->email ) : '';
        $val_city     = $is_edit && $row && isset( $row->city ) ? esc_attr( $row->city ) : '';
        $val_address  = $is_edit && $row && isset( $row->address ) ? esc_textarea( $row->address ) : '';
        
        $val_license  = $is_edit && $row && isset( $row->trade_license_no ) ? esc_attr( $row->trade_license_no ) : '';
        $val_nid      = $is_edit && $row && isset( $row->owner_nid ) ? esc_attr( $row->owner_nid ) : '';
        $val_cheque   = $is_edit && $row && isset( $row->security_cheque ) ? esc_attr( $row->security_cheque ) : '';
        $val_bank     = $is_edit && $row && isset( $row->bank_details ) ? esc_textarea( $row->bank_details ) : '';
        
        $val_tier     = $is_edit && $row && isset( $row->agency_tier ) ? esc_attr( $row->agency_tier ) : 'Standard Partner';
        $val_comm_mode = $is_edit && $row && isset( $row->commission_mode ) ? esc_attr( $row->commission_mode ) : 'Percentage';
        $val_comm     = $is_edit && $row && isset( $row->commission_rate ) ? esc_attr( $row->commission_rate ) : '0.0';
        $val_limit    = $is_edit && $row ? esc_attr( $row->credit_limit ) : '0.00';
        $val_thresh   = $is_edit && $row && isset( $row->credit_threshold ) ? intval( $row->credit_threshold ) : 80;
        $val_logo     = $is_edit && $row && isset( $row->logo_url ) ? esc_url( $row->logo_url ) : '';
        $val_doc      = $is_edit && $row && isset( $row->agreement_doc_url ) ? esc_url( $row->agreement_doc_url ) : '';
        $val_status   = $is_edit && $row ? esc_attr( $row->status ) : 'Active';

        $back_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=b2b_agents' );
        ?>

        <div class="wrap ifs-agent-form-workspace">
            <?php echo wp_kses_post( $message ); ?>

            <!-- Form Top Bar -->
            <div class="ifs-form-header-card">
                <div class="form-header-title-group">
                    <span class="ifs-form-badge">
                        <span class="dashicons dashicons-networking"></span> <?php esc_html_e( 'B2B Partner Network', 'ifs-travel-erp' ); ?>
                    </span>
                    <h2><?php echo $is_edit ? esc_html__( 'Edit Sub-Agent', 'ifs-travel-erp' ) : esc_html__( 'Add Sub-Agent', 'ifs-travel-erp' ); ?></h2>
                    <p><?php esc_html_e( 'Configure B2B agency credentials, credit ceilings, commission structures, and licensing verification.', 'ifs-travel-erp' ); ?></p>
                </div>
                <div class="form-header-actions">
                    <a href="<?php echo esc_url( $back_url ); ?>" class="ifs-btn-secondary">
                        <span class="dashicons dashicons-arrow-left-alt2"></span> <?php esc_html_e( 'Back to Agents', 'ifs-travel-erp' ); ?>
                    </a>
                </div>
            </div>

            <!-- Master Form Card -->
            <div class="ifs-master-form-card">
                <form method="post" action="">
                    <?php wp_nonce_field( 'ifs_agent_save_action', 'ifs_agent_nonce' ); ?>

                    <!-- Section 1: Agency & Contact -->
                    <div class="ifs-form-section">
                        <div class="section-title-wrap">
                            <div class="section-icon"><span class="dashicons dashicons-building"></span></div>
                            <div>
                                <h4><?php esc_html_e( 'Agency & Contact Profile', 'ifs-travel-erp' ); ?></h4>
                                <p><?php esc_html_e( 'Registered commercial title, contact personnel, and official communications.', 'ifs-travel-erp' ); ?></p>
                            </div>
                        </div>

                        <div class="ifs-grid-layout">
                            <div class="ifs-field-group">
                                <label class="ifs-field-label" for="agency_name"><?php esc_html_e( 'Agency Name', 'ifs-travel-erp' ); ?> <span class="required">*</span></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-store field-icon"></span>
                                    <input type="text" name="agency_name" id="agency_name" required class="ifs-input-field font-bold" 
                                           placeholder="e.g. Al-Madina Travels" value="<?php echo esc_attr( $val_name ); ?>">
                                </div>
                            </div>

                            <div class="ifs-field-group">
                                <label class="ifs-field-label" for="agency_tier"><?php esc_html_e( 'Tier', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-awards field-icon"></span>
                                    <select name="agency_tier" id="agency_tier" class="ifs-input-field">
                                        <option value="Standard Partner" <?php selected( $val_tier, 'Standard Partner' ); ?>><?php esc_html_e( 'Standard Partner', 'ifs-travel-erp' ); ?></option>
                                        <option value="Gold Partner" <?php selected( $val_tier, 'Gold Partner' ); ?>><?php esc_html_e( 'Gold Partner', 'ifs-travel-erp' ); ?></option>
                                        <option value="Platinum Corporate" <?php selected( $val_tier, 'Platinum Corporate' ); ?>><?php esc_html_e( 'Platinum Corporate', 'ifs-travel-erp' ); ?></option>
                                        <option value="Preferred Wholesaler" <?php selected( $val_tier, 'Preferred Wholesaler' ); ?>><?php esc_html_e( 'Preferred Wholesaler', 'ifs-travel-erp' ); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-group">
                                <label class="ifs-field-label" for="contact_person"><?php esc_html_e( 'Contact Person', 'ifs-travel-erp' ); ?> <span class="required">*</span></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-businessman field-icon"></span>
                                    <input type="text" name="contact_person" id="contact_person" required class="ifs-input-field" 
                                           placeholder="e.g. Hasan Mahmud" value="<?php echo esc_attr( $val_contact ); ?>">
                                </div>
                            </div>

                            <div class="ifs-field-group">
                                <label class="ifs-field-label" for="inp_designation"><?php esc_html_e( 'Designation', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-id field-icon"></span>
                                    <input type="text" name="designation" id="inp_designation" class="ifs-input-field" 
                                           placeholder="e.g. Managing Director" value="<?php echo esc_attr( $val_desig ); ?>">
                                </div>
                            </div>

                            <div class="ifs-field-group">
                                <label class="ifs-field-label" for="agent_mobile"><?php esc_html_e( 'Mobile', 'ifs-travel-erp' ); ?> <span class="required">*</span></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-phone field-icon"></span>
                                    <input type="text" name="mobile" id="agent_mobile" required class="ifs-input-field font-mono" 
                                           placeholder="e.g. 01711-000000" value="<?php echo esc_attr( $val_mobile ); ?>">
                                </div>
                            </div>

                            <div class="ifs-field-group">
                                <label class="ifs-field-label" for="agent_whatsapp"><?php esc_html_e( 'WhatsApp', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-format-chat field-icon"></span>
                                    <input type="text" name="whatsapp_no" id="agent_whatsapp" class="ifs-input-field font-mono text-emerald" 
                                           placeholder="e.g. 01711-000000" value="<?php echo esc_attr( $val_whatsapp ); ?>">
                                </div>
                            </div>

                            <div class="ifs-field-group">
                                <label class="ifs-field-label" for="accounts_mobile"><?php esc_html_e( 'Accounts Contact', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-money-alt field-icon"></span>
                                    <input type="text" name="accounts_mobile" id="accounts_mobile" class="ifs-input-field font-mono" 
                                           placeholder="e.g. Accounts Desk Number" value="<?php echo esc_attr( $val_accounts ); ?>">
                                </div>
                            </div>

                            <div class="ifs-field-group">
                                <label class="ifs-field-label" for="agent_email"><?php esc_html_e( 'Email', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-email field-icon"></span>
                                    <input type="email" name="email" id="agent_email" class="ifs-input-field" 
                                           placeholder="e.g. b2b@agency.com" value="<?php echo esc_attr( $val_email ); ?>">
                                </div>
                            </div>

                            <div class="ifs-field-group">
                                <label class="ifs-field-label" for="agent_city"><?php esc_html_e( 'City', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-location field-icon"></span>
                                    <input type="text" name="city" id="agent_city" class="ifs-input-field" 
                                           placeholder="e.g. Dhaka" value="<?php echo esc_attr( $val_city ); ?>">
                                </div>
                            </div>

                            <div class="ifs-field-group full-width">
                                <label class="ifs-field-label" for="agent_address"><?php esc_html_e( 'Address', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-textarea-wrap">
                                    <textarea name="address" id="agent_address" rows="2" class="ifs-input-field" 
                                        placeholder="<?php esc_attr_e( 'Suite / Road, Commercial District, Postal Area...', 'ifs-travel-erp' ); ?>"><?php echo esc_textarea( $val_address ); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ifs-section-divider"></div>

                    <!-- Section 2: Credit, Finance & Commission -->
                    <div class="ifs-form-section">
                        <div class="section-title-wrap">
                            <div class="section-icon"><span class="dashicons dashicons-money-alt"></span></div>
                            <div>
                                <h4><?php esc_html_e( 'Credit Limits & Commission', 'ifs-travel-erp' ); ?></h4>
                                <p><?php esc_html_e( 'Configure authorized credit ceiling, automated lockout threshold, and default agency profit cuts.', 'ifs-travel-erp' ); ?></p>
                            </div>
                        </div>

                        <div class="ifs-grid-layout">
                            <div class="ifs-field-group">
                                <label class="ifs-field-label" for="credit_limit"><?php esc_html_e( 'Credit Limit (৳)', 'ifs-travel-erp' ); ?> <span class="required">*</span></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-shield field-icon"></span>
                                    <input type="number" step="0.01" name="credit_limit" id="credit_limit" required class="ifs-input-field font-mono" 
                                           placeholder="0.00" value="<?php echo esc_attr( $val_limit ); ?>">
                                </div>
                                <span class="field-hint"><?php esc_html_e( 'Maximum allowable negative balance before booking lockout.', 'ifs-travel-erp' ); ?></span>
                            </div>

                            <div class="ifs-field-group">
                                <label class="ifs-field-label" for="credit_threshold"><?php esc_html_e( 'Alert Threshold (%)', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-warning field-icon"></span>
                                    <input type="number" min="50" max="100" name="credit_threshold" id="credit_threshold" class="ifs-input-field font-mono" 
                                           placeholder="80" value="<?php echo esc_attr( $val_thresh ); ?>">
                                </div>
                                <span class="field-hint"><?php esc_html_e( 'Alerts accounting when credit usage hits this percentage.', 'ifs-travel-erp' ); ?></span>
                            </div>

                            <div class="ifs-field-group">
                                <label class="ifs-field-label" for="commission_mode"><?php esc_html_e( 'Comm Mode', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-calculator field-icon"></span>
                                    <select name="commission_mode" id="commission_mode" class="ifs-input-field">
                                        <option value="Percentage" <?php selected( $val_comm_mode, 'Percentage' ); ?>><?php esc_html_e( 'Percentage (%)', 'ifs-travel-erp' ); ?></option>
                                        <option value="Fixed Amount" <?php selected( $val_comm_mode, 'Fixed Amount' ); ?>><?php esc_html_e( 'Fixed Fee (৳)', 'ifs-travel-erp' ); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-group">
                                <label class="ifs-field-label" for="commission_rate" id="lbl_commission_rate">
                                    <?php 
                                    /* translators: %s: currency symbol or percentage sign */
                                    printf( esc_html__( 'Comm Value (%s)', 'ifs-travel-erp' ), ( 'Fixed Amount' === $val_comm_mode ) ? '৳' : '%' ); 
                                    ?>
                                </label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-chart-pie field-icon"></span>
                                    <input type="number" step="0.01" name="commission_rate" id="commission_rate" class="ifs-input-field font-mono" 
                                           placeholder="e.g. 7.0 or 500" value="<?php echo esc_attr( $val_comm ); ?>">
                                </div>
                            </div>

                            <?php if ( ! $is_edit ) : ?>
                                <div class="ifs-field-group">
                                    <label class="ifs-field-label" for="opening_balance"><?php esc_html_e( 'Opening Deposit (৳)', 'ifs-travel-erp' ); ?></label>
                                    <div class="ifs-field-wrap">
                                        <span class="dashicons dashicons-money field-icon"></span>
                                        <input type="number" step="0.01" name="opening_balance" id="opening_balance" class="ifs-input-field font-mono text-emerald font-bold" 
                                               placeholder="0.00" value="0.00">
                                    </div>
                                    <span class="field-hint"><?php esc_html_e( 'Initial advance deposit credit balance upon onboarding.', 'ifs-travel-erp' ); ?></span>
                                </div>
                            <?php else : ?>
                                <div class="ifs-field-group">
                                    <label class="ifs-field-label"><?php esc_html_e( 'Current Balance', 'ifs-travel-erp' ); ?></label>
                                    <div class="ifs-field-wrap ifs-readonly-stat-box">
                                        <span class="stat-prefix">৳</span>
                                        <span class="stat-val font-mono font-bold <?php echo ( (float) $row->current_balance < 0 ) ? 'color-rose' : 'text-emerald'; ?>">
                                            <?php echo esc_html( number_format( (float) $row->current_balance, 2 ) ); ?>
                                        </span>
                                    </div>
                                    <span class="field-hint"><?php esc_html_e( 'Managed via automated service bookings and cash deposits.', 'ifs-travel-erp' ); ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="ifs-field-group">
                                <label class="ifs-field-label" for="agent_status"><?php esc_html_e( 'Status', 'ifs-travel-erp' ); ?> <span class="required">*</span></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-admin-settings field-icon"></span>
                                    <select name="status" id="agent_status" class="ifs-input-field">
                                        <option value="Active" <?php selected( $val_status, 'Active' ); ?>><?php esc_html_e( 'Active (Full Privileges)', 'ifs-travel-erp' ); ?></option>
                                        <option value="Suspended" <?php selected( $val_status, 'Suspended' ); ?>><?php esc_html_e( 'Suspended (Lock Issuances)', 'ifs-travel-erp' ); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ifs-section-divider"></div>

                    <!-- Section 3: Legal, Verification & Banking -->
                    <div class="ifs-form-section">
                        <div class="section-title-wrap">
                            <div class="section-icon"><span class="dashicons dashicons-shield"></span></div>
                            <div>
                                <h4><?php esc_html_e( 'Legal & Settlement Details', 'ifs-travel-erp' ); ?></h4>
                                <p><?php esc_html_e( 'Trade license verification, security guarantees, bank settlement credentials, and documents.', 'ifs-travel-erp' ); ?></p>
                            </div>
                        </div>

                        <div class="ifs-grid-layout">
                            <div class="ifs-field-group">
                                <label class="ifs-field-label" for="trade_license_no"><?php esc_html_e( 'Trade License', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-media-text field-icon"></span>
                                    <input type="text" name="trade_license_no" id="trade_license_no" class="ifs-input-field font-mono" 
                                           placeholder="e.g. TR-DH-84920" value="<?php echo esc_attr( $val_license ); ?>">
                                </div>
                            </div>

                            <div class="ifs-field-group">
                                <label class="ifs-field-label" for="inp_owner_nid"><?php esc_html_e( 'Owner NID', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-id-alt field-icon"></span>
                                    <input type="text" name="owner_nid" id="inp_owner_nid" class="ifs-input-field font-mono" 
                                           placeholder="e.g. 1990123456789" value="<?php echo esc_attr( $val_nid ); ?>">
                                </div>
                            </div>

                            <div class="ifs-field-group">
                                <label class="ifs-field-label" for="inp_security_cheque"><?php esc_html_e( 'Security Cheque', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <span class="dashicons dashicons-text-page field-icon"></span>
                                    <input type="text" name="security_cheque" id="inp_security_cheque" class="ifs-input-field font-mono" 
                                           placeholder="e.g. Chq#984210 / Bank Guarantee" value="<?php echo esc_attr( $val_cheque ); ?>">
                                </div>
                            </div>

                            <div class="ifs-field-group">
                                <label class="ifs-field-label" for="logo_url"><?php esc_html_e( 'Agency Logo', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-upload-field-row">
                                    <div class="ifs-field-wrap" style="flex: 1;">
                                        <span class="dashicons dashicons-camera field-icon"></span>
                                        <input type="url" name="logo_url" id="logo_url" value="<?php echo esc_url( $val_logo ); ?>" placeholder="https://..." class="ifs-input-field">
                                    </div>
                                    <button type="button" class="ifs-btn-upload-file" id="btn_upload_logo">
                                        <span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Logo', 'ifs-travel-erp' ); ?>
                                    </button>
                                </div>
                            </div>

                            <div class="ifs-field-group full-width">
                                <label class="ifs-field-label" for="inp_bank_details"><?php esc_html_e( 'Bank Details', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-textarea-wrap">
                                    <textarea name="bank_details" id="inp_bank_details" rows="2" class="ifs-input-field font-mono" 
                                        placeholder="<?php esc_attr_e( 'Bank Name, Account Title, Account No, Routing Number, Branch, MFS bKash/Nagad...', 'ifs-travel-erp' ); ?>"><?php echo esc_textarea( $val_bank ); ?></textarea>
                                </div>
                            </div>

                            <div class="ifs-field-group full-width">
                                <label class="ifs-field-label" for="agreement_doc_url"><?php esc_html_e( 'Agreement Copy', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-upload-field-row">
                                    <div class="ifs-field-wrap" style="flex: 1;">
                                        <span class="dashicons dashicons-pdf field-icon"></span>
                                        <input type="url" name="agreement_doc_url" id="agreement_doc_url" value="<?php echo esc_url( $val_doc ); ?>" placeholder="https://..." class="ifs-input-field">
                                    </div>
                                    <button type="button" class="ifs-btn-upload-file" id="btn_upload_agreement">
                                        <span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Upload', 'ifs-travel-erp' ); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Action Buttons -->
                    <div class="ifs-action-strip">
                        <a href="<?php echo esc_url( $back_url ); ?>" class="ifs-btn-secondary"><?php esc_html_e( 'Cancel', 'ifs-travel-erp' ); ?></a>
                        <button type="submit" name="ifs_agent_submit" class="ifs-btn-primary">
                            <span class="dashicons dashicons-saved"></span>
                            <?php echo $is_edit ? esc_html__( 'Update Agent', 'ifs-travel-erp' ) : esc_html__( 'Save Agent', 'ifs-travel-erp' ); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Stylesheet -->
        <style>
            .ifs-agent-form-workspace {
                max-width: 980px;
                margin: 20px auto;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                color: #0f172a;
                box-sizing: border-box;
            }
            .ifs-agent-form-workspace *,
            .ifs-agent-form-workspace *::before,
            .ifs-agent-form-workspace *::after {
                box-sizing: border-box;
                box-shadow: none !important;
                text-shadow: none !important;
            }

            .ifs-toast {
                padding: 13px 18px;
                border-radius: 10px;
                font-size: 13.5px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 22px;
            }
            .ifs-toast.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
            .ifs-toast.danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
            .ifs-toast .dashicons { font-size: 18px; width: 18px; height: 18px; }

            .ifs-form-header-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                padding: 24px 28px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 18px;
                margin-bottom: 24px;
            }
            .ifs-form-badge {
                background: #eff6ff;
                color: #003376;
                padding: 3px 10px;
                border-radius: 6px;
                font-size: 11px;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                display: inline-flex;
                align-items: center;
                gap: 5px;
                margin-bottom: 6px;
                border: 1px solid #bfdbfe;
            }
            .ifs-form-badge .dashicons { font-size: 14px; width: 14px; height: 14px; }
            .form-header-title-group h2 { margin: 0 0 4px 0; font-size: 20px; font-weight: 800; color: #0f172a; }
            .form-header-title-group p { margin: 0; font-size: 13px; color: #64748b; }

            .ifs-master-form-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                padding: 28px;
            }

            .ifs-form-section { margin-bottom: 22px; }
            .section-title-wrap {
                display: flex;
                align-items: center;
                gap: 14px;
                margin-bottom: 20px;
            }
            .section-icon {
                width: 36px;
                height: 36px;
                border-radius: 9px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #003376;
                color: #ffffff;
                flex-shrink: 0;
            }
            .section-icon .dashicons { font-size: 18px; width: 18px; height: 18px; }

            .section-title-wrap h4 { margin: 0; font-size: 15.5px; font-weight: 800; color: #0f172a; text-transform: capitalize; }
            .section-title-wrap p { margin: 2px 0 0 0; font-size: 12.5px; color: #64748b; }

            .ifs-section-divider {
                height: 1px;
                background: #f1f5f9;
                margin: 26px 0;
            }

            .ifs-grid-layout {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 16px 20px;
            }
            @media (max-width: 768px) {
                .ifs-grid-layout { grid-template-columns: 1fr; }
            }
            .ifs-field-group { 
                display: flex; 
                flex-direction: column; 
                justify-content: flex-start;
                gap: 6px; 
                width: 100%;
            }
            .ifs-field-group.full-width { grid-column: 1 / -1; }

            .ifs-field-label { 
                font-size: 11px; 
                font-weight: 700; 
                color: #475569; 
                text-transform: capitalize; 
                letter-spacing: 0.3px; 
                line-height: 1.2;
            }
            .ifs-field-label .required { color: #e11d48; margin-left: 2px; }

            .ifs-field-wrap { 
                position: relative; 
                display: flex; 
                align-items: center; 
                width: 100%; 
                height: 42px; 
            }
            .ifs-field-wrap .field-icon {
                position: absolute; 
                left: 12px; 
                top: 50%;
                transform: translateY(-50%);
                color: #94a3b8; 
                font-size: 18px; 
                width: 18px; 
                height: 18px; 
                line-height: 18px;
                pointer-events: none; 
                z-index: 3; 
                transition: color 0.2s ease;
            }

            .ifs-input-field { 
                width: 100% !important; 
                height: 42px !important; 
                max-height: 42px !important; 
                min-height: 42px !important; 
                line-height: 40px !important; 
                padding: 0 14px 0 40px !important; 
                border: 1px solid #cbd5e1 !important; 
                border-radius: 8px !important; 
                font-size: 13.5px !important; 
                color: #0f172a !important; 
                background-color: #ffffff !important; 
                outline: none !important; 
                transition: border-color 0.2s ease, background-color 0.2s ease; 
                margin: 0 !important; 
                display: block; 
            }
            select.ifs-input-field { 
                appearance: none; 
                -webkit-appearance: none; 
                background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e") !important; 
                background-repeat: no-repeat !important; 
                background-position: right 12px center !important; 
                background-size: 14px !important; 
                padding-right: 36px !important; 
                cursor: pointer; 
            }
            .ifs-input-field:focus { 
                border-color: #003376 !important; 
                background-color: #f8fafc !important; 
            }
            .ifs-field-wrap:focus-within .field-icon { 
                color: #003376; 
            }

            .ifs-textarea-wrap { width: 100%; }
            .ifs-textarea-wrap textarea.ifs-input-field {
                width: 100% !important;
                height: auto !important;
                min-height: 64px !important;
                padding: 10px 14px !important;
                line-height: 1.5 !important;
                border-radius: 8px !important;
                resize: vertical;
            }

            .ifs-readonly-stat-box {
                display: flex;
                align-items: center;
                gap: 6px;
                background: #f8fafc !important;
                border: 1px solid #cbd5e1 !important;
                border-radius: 8px !important;
                padding: 0 14px !important;
                height: 42px !important;
            }
            .stat-prefix { font-weight: 700; color: #64748b; font-size: 14px; }
            .stat-val { font-size: 15px; }

            .ifs-upload-field-row { display: flex; gap: 8px; align-items: center; width: 100%; }
            .ifs-btn-upload-file {
                background: #f1f5f9;
                border: 1px solid #cbd5e1;
                height: 42px;
                padding: 0 16px;
                border-radius: 8px;
                font-size: 12.5px;
                font-weight: 600;
                color: #334155;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                white-space: nowrap;
                transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
            }
            .ifs-btn-upload-file:hover { background: #003376; color: #ffffff; border-color: #003376; }

            .field-hint { font-size: 11px; color: #94a3b8; margin-top: 2px; }
            .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important; }
            .font-bold { font-weight: 700; }
            .text-emerald { color: #059669 !important; }
            .color-rose    { color: #dc2626 !important; }

            .ifs-action-strip {
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #f1f5f9;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 14px;
            }
            .ifs-btn-secondary {
                background: #f8fafc;
                color: #475569 !important;
                border: 1px solid #cbd5e1;
                height: 42px;
                padding: 0 20px;
                border-radius: 8px;
                font-size: 13px;
                font-weight: 600;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                transition: background-color 0.2s ease, color 0.2s ease;
            }
            .ifs-btn-secondary:hover { background: #f1f5f9; color: #0f172a; }

            .ifs-btn-primary {
                background: #003376;
                color: #ffffff !important;
                border: none;
                height: 42px;
                padding: 0 24px;
                border-radius: 8px;
                font-size: 13.5px;
                font-weight: 700;
                text-decoration: none;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: background-color 0.2s ease;
            }
            .ifs-btn-primary:hover { background: #0284c7; }
            .ifs-btn-primary .dashicons { font-size: 16px; width: 16px; height: 16px; }
        </style>

        <!-- Interactive Script for Dynamic Comm Label & Dual Media Uploaders -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modeSelect = document.getElementById('commission_mode');
            const commLabel  = document.getElementById('lbl_commission_rate');

            function updateLabel() {
                if (modeSelect && commLabel) {
                    if (modeSelect.value === 'Fixed Amount') {
                        commLabel.textContent = 'Comm Value (৳)';
                    } else {
                        commLabel.textContent = 'Comm Value (%)';
                    }
                }
            }

            if (modeSelect) {
                modeSelect.addEventListener('change', updateLabel);
                updateLabel();
            }

            // Generic Media Uploader Initializer
            function setupMediaUpload(buttonId, targetInputId, titleText) {
                const btn   = document.getElementById(buttonId);
                const input = document.getElementById(targetInputId);
                if (btn && window.wp && wp.media) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const uploader = wp.media({
                            title: titleText,
                            button: { text: 'Attach File' },
                            multiple: false
                        }).on('select', function() {
                            const attachment = uploader.state().get('selection').first().toJSON();
                            if (attachment && attachment.url && input) {
                                input.value = attachment.url;
                            }
                        }).open();
                    });
                }
            }

            setupMediaUpload('btn_upload_logo', 'logo_url', 'Select Agency Logo');
            setupMediaUpload('btn_upload_agreement', 'agreement_doc_url', 'Select Agreement or Trade License');
        });
        </script>
        <?php
    }
}