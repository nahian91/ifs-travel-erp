<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enterprise B2B Sub-Agent Onboarding & Profile Management
 * Features: Complete Compliance Schema, Commercial Terms, Commission Rate, Credit Limits & Live Ledger Sync
 */
function ifs_terp_agent_add_edit_page() {
    global $wpdb;
    $table_agents = $wpdb->prefix . 'iterp_agents';
    
    $id      = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
    $is_edit = ( $id > 0 );
    $message = '';

    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_agent_submit'] ) ) {
        check_admin_referer( 'ifs_agent_save_action', 'ifs_agent_nonce' );

        $agency_name      = sanitize_text_field( $_POST['agency_name'] ?? '' );
        $contact_person   = sanitize_text_field( $_POST['contact_person'] ?? '' );
        $mobile           = sanitize_text_field( $_POST['mobile'] ?? '' );
        $email            = sanitize_email( $_POST['email'] ?? '' );
        $city             = sanitize_text_field( $_POST['city'] ?? '' );
        $address          = sanitize_textarea_field( $_POST['address'] ?? '' );
        $trade_license_no = sanitize_text_field( $_POST['trade_license_no'] ?? '' );
        $commission_rate  = floatval( $_POST['commission_rate'] ?? 0 );
        $credit_limit     = floatval( $_POST['credit_limit'] ?? 0 );
        $status           = sanitize_text_field( $_POST['status'] ?? 'Active' );

        $data = array(
            'agency_name'      => $agency_name,
            'contact_person'   => $contact_person,
            'mobile'           => $mobile,
            'email'            => $email,
            'city'             => $city,
            'address'          => $address,
            'trade_license_no' => $trade_license_no,
            'commission_rate'  => $commission_rate,
            'credit_limit'     => $credit_limit,
            'status'           => $status,
        );

        if ( ! $is_edit ) {
            $opening_bal             = floatval( $_POST['opening_balance'] ?? 0 );
            $data['current_balance'] = $opening_bal;
            $data['created_at']      = current_time( 'mysql' );
            
            $inserted = $wpdb->insert(
                $table_agents,
                $data,
                array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%f', '%s' )
            );

            if ( $inserted ) {
                $id      = $wpdb->insert_id;
                $is_edit = true;

                if ( function_exists( 'ifs_terp_log_activity' ) ) {
                    ifs_terp_log_activity( "Registered New B2B Sub-Agent: {$agency_name} (ID: #AGT-{$id})" );
                }

                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> B2B sub-agent registered successfully.</div>';
            } else {
                $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> Database error: Unable to register sub-agent.</div>';
            }
        } else {
            $updated = $wpdb->update(
                $table_agents,
                $data,
                array( 'id' => $id ),
                array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s' ),
                array( '%d' )
            );

            if ( false !== $updated ) {
                if ( function_exists( 'ifs_terp_log_activity' ) ) {
                    ifs_terp_log_activity( "Updated B2B Sub-Agent Profile: {$agency_name} (ID: #AGT-{$id})" );
                }
                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Sub-agent profile updated successfully.</div>';
            } else {
                $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> Database error: Unable to update profile.</div>';
            }
        }
    }

    $row = false;
    if ( $is_edit ) {
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_agents WHERE id = %d", $id ) );
    }

    $back_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=b2b_agents' );
    ?>

    <div class="wrap ifs-agent-form-workspace">
        <?php echo $message; ?>

        <!-- Form Hero Header -->
        <div class="ifs-form-header-card">
            <div class="form-header-title-group">
                <span class="ifs-form-badge">
                    <span class="dashicons dashicons-networking"></span> B2B Partner Network
                </span>
                <h2><?php echo $is_edit ? 'Update Sub-Agent Profile' : 'Onboard New B2B Sub-Agent'; ?></h2>
                <p>Configure commercial credit ceilings, agency licensing, default commission cuts, and billing addresses.</p>
            </div>
            <div class="form-header-actions">
                <a href="<?php echo esc_url( $back_url ); ?>" class="ifs-btn-back">
                    <span class="dashicons dashicons-arrow-left-alt"></span> Return to Directory
                </a>
            </div>
        </div>

        <!-- Master Form Card -->
        <div class="ifs-master-form-card">
            <form method="post" action="">
                <?php wp_nonce_field( 'ifs_agent_save_action', 'ifs_agent_nonce' ); ?>

                <!-- Section 1: Agency & Trade Licensing -->
                <div class="ifs-form-section">
                    <div class="section-title-wrap">
                        <div class="section-icon bg-blue"><span class="dashicons dashicons-building"></span></div>
                        <div>
                            <h4>Corporate Identity &amp; Licensing</h4>
                            <p>Official trade registration, business location, and key management focal point.</p>
                        </div>
                    </div>

                    <div class="ifs-grid-layout">
                        <div class="ifs-field-group">
                            <label for="agency_name">Agency / Company Name <span class="required">*</span></label>
                            <div class="ifs-input-icon-wrap">
                                <span class="dashicons dashicons-store"></span>
                                <input type="text" name="agency_name" id="agency_name" required class="ifs-input-field" 
                                    placeholder="e.g. Al-Madina Travels &amp; Tours" 
                                    value="<?php echo $is_edit && $row ? esc_attr( $row->agency_name ) : ''; ?>">
                            </div>
                        </div>

                        <div class="ifs-field-group">
                            <label for="trade_license_no">Trade License / Civil Aviation No</label>
                            <div class="ifs-input-icon-wrap">
                                <span class="dashicons dashicons-media-text"></span>
                                <input type="text" name="trade_license_no" id="trade_license_no" class="ifs-input-field font-mono" 
                                    placeholder="e.g. TR-DH-84920" 
                                    value="<?php echo $is_edit && $row && isset($row->trade_license_no) ? esc_attr( $row->trade_license_no ) : ''; ?>">
                            </div>
                        </div>

                        <div class="ifs-field-group">
                            <label for="contact_person">Primary Contact Person <span class="required">*</span></label>
                            <div class="ifs-input-icon-wrap">
                                <span class="dashicons dashicons-businessman"></span>
                                <input type="text" name="contact_person" id="contact_person" required class="ifs-input-field" 
                                    placeholder="e.g. Hasan Mahmud" 
                                    value="<?php echo $is_edit && $row ? esc_attr( $row->contact_person ) : ''; ?>">
                            </div>
                        </div>

                        <div class="ifs-field-group">
                            <label for="agent_mobile">Official Mobile Number <span class="required">*</span></label>
                            <div class="ifs-input-icon-wrap">
                                <span class="dashicons dashicons-phone"></span>
                                <input type="text" name="mobile" id="agent_mobile" required class="ifs-input-field font-mono" 
                                    placeholder="e.g. 018XXXXXXXX" 
                                    value="<?php echo $is_edit && $row ? esc_attr( $row->mobile ) : ''; ?>">
                            </div>
                        </div>

                        <div class="ifs-field-group">
                            <label for="agent_email">Official Email Address</label>
                            <div class="ifs-input-icon-wrap">
                                <span class="dashicons dashicons-email"></span>
                                <input type="email" name="email" id="agent_email" class="ifs-input-field" 
                                    placeholder="e.g. booking@almadinatravels.com" 
                                    value="<?php echo $is_edit && $row ? esc_attr( $row->email ) : ''; ?>">
                            </div>
                        </div>

                        <div class="ifs-field-group">
                            <label for="agent_city">City / Operating District</label>
                            <div class="ifs-input-icon-wrap">
                                <span class="dashicons dashicons-location"></span>
                                <input type="text" name="city" id="agent_city" class="ifs-input-field" 
                                    placeholder="e.g. Dhaka / Sylhet" 
                                    value="<?php echo $is_edit && $row && isset($row->city) ? esc_attr( $row->city ) : ''; ?>">
                            </div>
                        </div>

                        <div class="ifs-field-group full-width">
                            <label for="agent_address">Full Office Address</label>
                            <textarea name="address" id="agent_address" rows="2" class="ifs-textarea-field" 
                                placeholder="Suite / Road, Area, Postal Code..."><?php echo $is_edit && $row && isset($row->address) ? esc_textarea( $row->address ) : ''; ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="ifs-section-divider"></div>

                <!-- Section 2: Commercial Credit & Ledger Control -->
                <div class="ifs-form-section">
                    <div class="section-title-wrap">
                        <div class="section-icon bg-emerald"><span class="dashicons dashicons-money-alt"></span></div>
                        <div>
                            <h4>Financial Terms &amp; Commission Cut</h4>
                            <p>Set authorized credit limits, default commission share ratio, and account status.</p>
                        </div>
                    </div>

                    <div class="ifs-grid-layout">
                        <div class="ifs-field-group">
                            <label for="credit_limit">Authorized Credit Limit (৳) <span class="required">*</span></label>
                            <div class="ifs-input-icon-wrap">
                                <span class="dashicons dashicons-shield"></span>
                                <input type="number" step="0.01" name="credit_limit" id="credit_limit" required class="ifs-input-field font-mono" 
                                    placeholder="0.00" 
                                    value="<?php echo $is_edit && $row ? esc_attr( $row->credit_limit ) : '0.00'; ?>">
                            </div>
                            <span class="field-hint">Maximum negative balance permitted before automated booking lockout.</span>
                        </div>

                        <div class="ifs-field-group">
                            <label for="commission_rate">Default Sub-Agent Commission Cut (%)</label>
                            <div class="ifs-input-icon-wrap">
                                <span class="dashicons dashicons-chart-pie"></span>
                                <input type="number" step="0.1" name="commission_rate" id="commission_rate" class="ifs-input-field font-mono" 
                                    placeholder="e.g. 7.0" 
                                    value="<?php echo $is_edit && $row && isset($row->commission_rate) ? esc_attr( $row->commission_rate ) : '0.0'; ?>">
                            </div>
                            <span class="field-hint">Default profit sharing cut applied on air ticketing / packages.</span>
                        </div>

                        <?php if ( ! $is_edit ) : ?>
                            <div class="ifs-field-group">
                                <label for="opening_balance">Opening Deposit / Balance (৳)</label>
                                <div class="ifs-input-icon-wrap">
                                    <span class="dashicons dashicons-money"></span>
                                    <input type="number" step="0.01" name="opening_balance" id="opening_balance" class="ifs-input-field font-mono" 
                                        placeholder="0.00" value="0.00">
                                </div>
                                <span class="field-hint">Initial deposit credit balance upon onboarding.</span>
                            </div>
                        <?php else : ?>
                            <div class="ifs-field-group">
                                <label>Current Ledger Balance</label>
                                <div class="ifs-readonly-stat-box">
                                    <span class="stat-prefix">৳</span>
                                    <span class="stat-val font-mono font-bold <?php echo ( (float) $row->current_balance < 0 ) ? 'color-rose' : 'color-emerald'; ?>">
                                        <?php echo number_format( (float) $row->current_balance, 2 ); ?>
                                    </span>
                                </div>
                                <span class="field-hint">Managed via automated service bookings and cash receipts.</span>
                            </div>
                        <?php endif; ?>

                        <div class="ifs-field-group">
                            <label for="agent_status">Account Operating Status <span class="required">*</span></label>
                            <div class="ifs-input-icon-wrap">
                                <span class="dashicons dashicons-admin-settings"></span>
                                <select name="status" id="agent_status" class="ifs-input-field ifs-select-styled">
                                    <option value="Active" <?php selected( $is_edit && $row && $row->status === 'Active' ); ?>>Active (Full Ticketing &amp; Visa Privileges)</option>
                                    <option value="Suspended" <?php selected( $is_edit && $row && $row->status === 'Suspended' ); ?>>Suspended (Temporarily Lock Issuances)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Action Buttons -->
                <div class="ifs-form-footer">
                    <a href="<?php echo esc_url( $back_url ); ?>" class="ifs-btn-cancel">Discard &amp; Return</a>
                    <button type="submit" name="ifs_agent_submit" class="ifs-btn-submit">
                        <span class="dashicons dashicons-saved"></span>
                        <?php echo $is_edit ? 'Update Agent Profile' : 'Save B2B Sub-Agent'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Stylesheet -->
    <style>
        .ifs-agent-form-workspace {
            max-width: 900px;
            margin: 0 auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #0f172a;
        }

        .ifs-toast {
            padding: 14px 20px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 22px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        .ifs-toast.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .ifs-toast.danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .ifs-toast .dashicons { font-size: 18px; width: 18px; height: 18px; }

        .ifs-form-header-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04);
            margin-bottom: 24px;
        }
        .ifs-form-badge {
            background: #eff6ff;
            color: #1d4ed8;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-bottom: 6px;
            border: 1px solid #dbeafe;
        }
        .ifs-form-badge .dashicons { font-size: 13px; width: 13px; height: 13px; }
        .form-header-title-group h2 { margin: 0 0 4px 0; font-size: 21px; font-weight: 900; color: #0f172a; letter-spacing: -0.3px; }
        .form-header-title-group p { margin: 0; font-size: 13px; color: #64748b; }

        .ifs-btn-back {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #334155;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .ifs-btn-back:hover { background: #e2e8f0; color: #0f172a; }
        .ifs-btn-back .dashicons { font-size: 16px; width: 16px; height: 16px; }

        .ifs-master-form-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
        }

        .ifs-form-section { margin-bottom: 26px; }
        .section-title-wrap {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 20px;
        }
        .section-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            flex-shrink: 0;
        }
        .section-icon.bg-blue    { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); }
        .section-icon.bg-emerald { background: linear-gradient(135deg, #059669 0%, #047857 100%); }
        .section-icon .dashicons { font-size: 20px; width: 20px; height: 20px; }

        .section-title-wrap h4 { margin: 0; font-size: 15.5px; font-weight: 800; color: #0f172a; }
        .section-title-wrap p { margin: 2px 0 0 0; font-size: 12.5px; color: #64748b; }

        .ifs-section-divider {
            height: 1px;
            background: #f1f5f9;
            margin: 26px 0;
        }

        .ifs-grid-layout {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px 20px;
        }
        @media (max-width: 680px) {
            .ifs-grid-layout { grid-template-columns: 1fr; }
        }
        .ifs-field-group { display: flex; flex-direction: column; gap: 6px; }
        .ifs-field-group.full-width { grid-column: 1 / -1; }

        .ifs-field-group label {
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .ifs-field-group label .required { color: #dc2626; }

        .ifs-input-icon-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }
        .ifs-input-icon-wrap .dashicons {
            position: absolute;
            left: 12px;
            color: #94a3b8;
            font-size: 17px;
            width: 17px;
            height: 17px;
            pointer-events: none;
        }

        .ifs-input-field {
            width: 100%;
            padding: 10px 14px 10px 38px !important;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13.5px;
            color: #0f172a;
            background: #ffffff;
            outline: none;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }
        .ifs-input-field:focus, .ifs-textarea-field:focus {
            border-color: #003376;
            box-shadow: 0 0 0 3px rgba(0, 51, 118, 0.12);
        }

        .ifs-textarea-field {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13.5px;
            color: #0f172a;
            background: #ffffff;
            outline: none;
            transition: all 0.2s ease;
            box-sizing: border-box;
            resize: vertical;
        }

        .ifs-select-styled {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 14px;
            padding-right: 36px !important;
            cursor: pointer;
        }

        .ifs-readonly-stat-box {
            display: flex;
            align-items: center;
            gap: 4px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 9px 14px;
            box-sizing: border-box;
        }
        .stat-prefix { font-weight: 700; color: #64748b; font-size: 14px; }
        .stat-val { font-size: 15px; }

        .field-hint { font-size: 11px; color: #94a3b8; margin-top: 2px; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .font-bold { font-weight: 700; }
        .color-emerald { color: #059669 !important; }
        .color-rose    { color: #e11d48 !important; }

        .ifs-form-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .ifs-btn-cancel {
            color: #64748b;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            transition: color 0.15s ease;
        }
        .ifs-btn-cancel:hover { color: #0f172a; }

        .ifs-btn-submit {
            background: linear-gradient(135deg, #003376 0%, #0284c7 100%);
            color: #ffffff !important;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 12px rgba(0, 51, 118, 0.25);
            transition: all 0.2s ease;
        }
        .ifs-btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(0, 51, 118, 0.35);
        }
        .ifs-btn-submit .dashicons { font-size: 16px; width: 16px; height: 16px; }
    </style>
    <?php
}