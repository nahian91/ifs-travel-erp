<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enterprise Next-Gen Staff & Employee Onboarding Workspace
 * Features: Role-Based Access Assignment, User Meta Synchronization, Activity Audit Logging & Responsive Form Architecture
 */
function ifs_terp_staff_add_edit_page() {
    $user_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
    $is_edit = ( $user_id > 0 );
    $message = '';

    // Handle Form Submission
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_staff_submit'] ) ) {
        check_admin_referer( 'ifs_staff_save_action', 'ifs_staff_nonce' );

        $username   = sanitize_user( $_POST['username'] ?? '' );
        $email      = sanitize_email( $_POST['email'] ?? '' );
        $first_name = sanitize_text_field( $_POST['first_name'] ?? '' );
        $last_name  = sanitize_text_field( $_POST['last_name'] ?? '' );
        $phone      = sanitize_text_field( $_POST['phone'] ?? '' );
        $role       = sanitize_text_field( $_POST['role'] ?? 'ticketing_staff' );
        $password   = $_POST['password'] ?? '';

        if ( $is_edit ) {
            $userdata = array(
                'ID'           => $user_id,
                'user_email'   => $email,
                'first_name'   => $first_name,
                'last_name'    => $last_name,
                'display_name' => trim( $first_name . ' ' . $last_name ),
                'role'         => $role,
            );
            if ( ! empty( $password ) ) {
                $userdata['user_pass'] = $password;
            }
            $updated = wp_update_user( $userdata );
            if ( ! is_wp_error( $updated ) ) {
                update_user_meta( $user_id, 'phone_number', $phone );

                if ( function_exists( 'ifs_terp_log_activity' ) ) {
                    ifs_terp_log_activity( "Updated Staff Account: {$username} (ID: #UID-{$user_id})" );
                }

                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Staff profile updated successfully.</div>';
            } else {
                $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> ' . esc_html( $updated->get_error_message() ) . '</div>';
            }
        } else {
            $userdata = array(
                'user_login'   => $username,
                'user_email'   => $email,
                'user_pass'    => $password,
                'first_name'   => $first_name,
                'last_name'    => $last_name,
                'display_name' => trim( $first_name . ' ' . $last_name ),
                'role'         => $role,
            );
            $new_user_id = wp_insert_user( $userdata );
            if ( ! is_wp_error( $new_user_id ) ) {
                update_user_meta( $new_user_id, 'phone_number', $phone );
                $user_id = $new_user_id;
                $is_edit = true;

                if ( function_exists( 'ifs_terp_log_activity' ) ) {
                    ifs_terp_log_activity( "Created New Staff Account: {$username} (ID: #UID-{$new_user_id})" );
                }

                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> New staff member onboarded successfully.</div>';
            } else {
                $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> ' . esc_html( $new_user_id->get_error_message() ) . '</div>';
            }
        }
    }

    $edit_user  = $is_edit ? get_userdata( $user_id ) : false;
    $edit_phone = $is_edit ? get_user_meta( $user_id, 'phone_number', true ) : '';
    $user_role  = ( $edit_user && ! empty( $edit_user->roles ) ) ? reset( $edit_user->roles ) : 'ticketing_staff';
    $back_url   = admin_url( 'admin.php?page=ifs_travel_erp&tab=staff' );
    ?>

    <div class="wrap ifs-staff-form-workspace">
        <?php echo $message; ?>

        <!-- Form Hero Header -->
        <div class="ifs-form-header-card">
            <div class="form-header-title-group">
                <span class="ifs-form-badge">
                    <span class="dashicons dashicons-businesswoman"></span> Human Resources &amp; Access Control
                </span>
                <h2><?php echo $is_edit ? 'Update Employee Profile' : 'Onboard New Staff Member'; ?></h2>
                <p>Configure operational permissions, login credentials, and department assignments for team members.</p>
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
                <?php wp_nonce_field( 'ifs_staff_save_action', 'ifs_staff_nonce' ); ?>

                <!-- Section 1: Account & Credentials -->
                <div class="ifs-form-section">
                    <div class="section-title-wrap">
                        <div class="section-icon bg-blue"><span class="dashicons dashicons-admin-network"></span></div>
                        <div>
                            <h4>System Identity &amp; Credentials</h4>
                            <p>Unique system login credentials and communication email address.</p>
                        </div>
                    </div>

                    <div class="ifs-grid-layout">
                        <div class="ifs-field-group">
                            <label for="staff_username">Account Username <span class="required">*</span></label>
                            <div class="ifs-input-icon-wrap">
                                <span class="dashicons dashicons-admin-users"></span>
                                <input type="text" name="username" id="staff_username" required 
                                    <?php echo $is_edit ? 'readonly class="ifs-input-field readonly-state"' : 'class="ifs-input-field" placeholder="e.g. jdoe"'; ?> 
                                    value="<?php echo $is_edit ? esc_attr( $edit_user->user_login ) : ''; ?>">
                            </div>
                            <?php if ( $is_edit ) : ?>
                                <span class="field-hint">Usernames are immutable system identifiers.</span>
                            <?php endif; ?>
                        </div>

                        <div class="ifs-field-group">
                            <label for="staff_email">Official Email Address <span class="required">*</span></label>
                            <div class="ifs-input-icon-wrap">
                                <span class="dashicons dashicons-email"></span>
                                <input type="email" name="email" id="staff_email" required class="ifs-input-field" 
                                    placeholder="e.g. staff@agency.com" 
                                    value="<?php echo $is_edit ? esc_attr( $edit_user->user_email ) : ''; ?>">
                            </div>
                        </div>

                        <div class="ifs-field-group full-width">
                            <label for="staff_password">
                                <?php echo $is_edit ? 'Change Password (Leave blank to keep existing)' : 'Set Account Password <span class="required">*</span>'; ?>
                            </label>
                            <div class="ifs-input-icon-wrap">
                                <span class="dashicons dashicons-lock"></span>
                                <input type="password" name="password" id="staff_password" <?php echo $is_edit ? '' : 'required'; ?> 
                                    class="ifs-input-field" placeholder="••••••••••••••••">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ifs-section-divider"></div>

                <!-- Section 2: Personal Profile & Contact -->
                <div class="ifs-form-section">
                    <div class="section-title-wrap">
                        <div class="section-icon bg-indigo"><span class="dashicons dashicons-id-alt"></span></div>
                        <div>
                            <h4>Demographics &amp; Contact Information</h4>
                            <p>Employee full name and verified telephone contact information.</p>
                        </div>
                    </div>

                    <div class="ifs-grid-layout">
                        <div class="ifs-field-group">
                            <label for="staff_fname">First Name <span class="required">*</span></label>
                            <div class="ifs-input-icon-wrap">
                                <span class="dashicons dashicons-editor-textcolor"></span>
                                <input type="text" name="first_name" id="staff_fname" required class="ifs-input-field" 
                                    placeholder="e.g. John" 
                                    value="<?php echo $is_edit ? esc_attr( $edit_user->first_name ) : ''; ?>">
                            </div>
                        </div>

                        <div class="ifs-field-group">
                            <label for="staff_lname">Last Name</label>
                            <div class="ifs-input-icon-wrap">
                                <span class="dashicons dashicons-editor-textcolor"></span>
                                <input type="text" name="last_name" id="staff_lname" class="ifs-input-field" 
                                    placeholder="e.g. Doe" 
                                    value="<?php echo $is_edit ? esc_attr( $edit_user->last_name ) : ''; ?>">
                            </div>
                        </div>

                        <div class="ifs-field-group full-width">
                            <label for="staff_phone">Phone / Mobile Number</label>
                            <div class="ifs-input-icon-wrap">
                                <span class="dashicons dashicons-phone"></span>
                                <input type="text" name="phone" id="staff_phone" class="ifs-input-field" 
                                    placeholder="e.g. +880 17XXXXXXXX" 
                                    value="<?php echo esc_attr( $edit_phone ); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ifs-section-divider"></div>

                <!-- Section 3: ERP Role & Access Control -->
                <div class="ifs-form-section">
                    <div class="section-title-wrap">
                        <div class="section-icon bg-emerald"><span class="dashicons dashicons-shield-alt"></span></div>
                        <div>
                            <h4>Role Assignment &amp; Access Privileges</h4>
                            <p>Defines departmental access, operational desk controls, and reporting scope.</p>
                        </div>
                    </div>

                    <div class="ifs-grid-layout">
                        <div class="ifs-field-group full-width">
                            <label for="staff_role">Assigned Operations Role <span class="required">*</span></label>
                            <div class="ifs-input-icon-wrap">
                                <span class="dashicons dashicons-groups"></span>
                                <select name="role" id="staff_role" class="ifs-input-field ifs-select-styled">
                                    <option value="administrator" <?php selected( $user_role, 'administrator' ); ?>>Super Administrator (Full Master ERP Access)</option>
                                    <option value="admin_manager" <?php selected( $user_role, 'admin_manager' ); ?>>Branch / Operations Manager (Operations &amp; Staff Desk)</option>
                                    <option value="ticketing_staff" <?php selected( $user_role, 'ticketing_staff' ); ?>>Air Ticketing Executive (Ticketing &amp; Reissues)</option>
                                    <option value="visa_officer" <?php selected( $user_role, 'visa_officer' ); ?>>Visa Processing Officer (Dossiers &amp; Consulates)</option>
                                    <option value="accountant" <?php selected( $user_role, 'accountant' ); ?>>Finance &amp; Accounts Executive (Ledger &amp; Settlements)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Action Buttons -->
                <div class="ifs-form-footer">
                    <a href="<?php echo esc_url( $back_url ); ?>" class="ifs-btn-cancel">Cancel &amp; Return</a>
                    <button type="submit" name="ifs_staff_submit" class="ifs-btn-submit">
                        <span class="dashicons dashicons-saved"></span>
                        <?php echo $is_edit ? 'Save Profile Changes' : 'Complete Staff Onboarding'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modern Stylesheet -->
    <style>
        .ifs-staff-form-workspace {
            max-width: 900px;
            margin: 0 auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #0f172a;
        }

        /* Toast Notifications */
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

        /* Form Hero Header */
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

        /* Master Form Card */
        .ifs-master-form-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
        }

        /* Form Sections */
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
        .section-icon.bg-indigo  { background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); }
        .section-icon.bg-emerald { background: linear-gradient(135deg, #059669 0%, #047857 100%); }
        .section-icon .dashicons { font-size: 20px; width: 20px; height: 20px; }

        .section-title-wrap h4 { margin: 0; font-size: 15.5px; font-weight: 800; color: #0f172a; }
        .section-title-wrap p { margin: 2px 0 0 0; font-size: 12.5px; color: #64748b; }

        .ifs-section-divider {
            height: 1px;
            background: #f1f5f9;
            margin: 26px 0;
        }

        /* Grid & Fields */
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
        .ifs-input-field:focus {
            border-color: #003376;
            box-shadow: 0 0 0 3px rgba(0, 51, 118, 0.12);
        }
        .ifs-input-field.readonly-state {
            background: #f8fafc;
            color: #64748b;
            cursor: not-allowed;
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

        .field-hint { font-size: 11px; color: #94a3b8; margin-top: 2px; }

        /* Footer */
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