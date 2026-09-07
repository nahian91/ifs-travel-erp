<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enterprise Next-Gen Staff & Employee Onboarding Workspace
 * Features: Full Profile with Salary & Compensation, Employment Meta,
 * National ID, Emergency Contacts, Role Privileges, Flat UI, Zero Shadows & Strict 42px Field Heights
 */
function ifs_terp_staff_add_edit_page() {
    $user_id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
    $is_edit = ( $user_id > 0 );
    $message = '';

    // Ensure Media Uploader is available for Avatar / Documents
    if ( function_exists( 'wp_enqueue_media' ) ) {
        wp_enqueue_media();
    }

    // Handle Form Submission
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_staff_submit'] ) ) {
        check_admin_referer( 'ifs_staff_save_action', 'ifs_staff_nonce' );

        $username   = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : '';
        $email      = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
        $last_name  = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
        $phone      = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
        $alt_phone  = isset( $_POST['alt_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['alt_phone'] ) ) : '';
        $nid_number = isset( $_POST['nid_number'] ) ? sanitize_text_field( wp_unslash( $_POST['nid_number'] ) ) : '';
        $address    = isset( $_POST['address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['address'] ) ) : '';
        $role       = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : 'ticketing_staff';
        $password   = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '';

        // Employment & Salary Fields
        $employee_id     = isset( $_POST['employee_id'] ) ? sanitize_text_field( wp_unslash( $_POST['employee_id'] ) ) : '';
        $designation     = isset( $_POST['designation'] ) ? sanitize_text_field( wp_unslash( $_POST['designation'] ) ) : '';
        $department      = isset( $_POST['department'] ) ? sanitize_text_field( wp_unslash( $_POST['department'] ) ) : 'Ticketing';
        $joining_date    = isset( $_POST['joining_date'] ) ? sanitize_text_field( wp_unslash( $_POST['joining_date'] ) ) : '';
        $employment_type = isset( $_POST['employment_type'] ) ? sanitize_text_field( wp_unslash( $_POST['employment_type'] ) ) : 'Permanent';
        $basic_salary    = isset( $_POST['basic_salary'] ) ? (float) wp_unslash( $_POST['basic_salary'] ) : 0;
        $house_rent      = isset( $_POST['house_rent'] ) ? (float) wp_unslash( $_POST['house_rent'] ) : 0;
        $medical_allow   = isset( $_POST['medical_allow'] ) ? (float) wp_unslash( $_POST['medical_allow'] ) : 0;
        $transport_allow = isset( $_POST['transport_allow'] ) ? (float) wp_unslash( $_POST['transport_allow'] ) : 0;
        $gross_salary    = $basic_salary + $house_rent + $medical_allow + $transport_allow;
        $bank_name       = isset( $_POST['bank_name'] ) ? sanitize_text_field( wp_unslash( $_POST['bank_name'] ) ) : '';
        $bank_acc_no     = isset( $_POST['bank_acc_no'] ) ? sanitize_text_field( wp_unslash( $_POST['bank_acc_no'] ) ) : '';
        $mfs_number      = isset( $_POST['mfs_number'] ) ? sanitize_text_field( wp_unslash( $_POST['mfs_number'] ) ) : '';
        $emergency_name  = isset( $_POST['emergency_name'] ) ? sanitize_text_field( wp_unslash( $_POST['emergency_name'] ) ) : '';
        $emergency_phone = isset( $_POST['emergency_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['emergency_phone'] ) ) : '';
        $profile_photo   = isset( $_POST['profile_photo'] ) ? esc_url_raw( wp_unslash( $_POST['profile_photo'] ) ) : '';

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
                update_user_meta( $user_id, 'alt_phone', $alt_phone );
                update_user_meta( $user_id, 'nid_number', $nid_number );
                update_user_meta( $user_id, 'address', $address );
                update_user_meta( $user_id, 'employee_id', $employee_id );
                update_user_meta( $user_id, 'designation', $designation );
                update_user_meta( $user_id, 'department', $department );
                update_user_meta( $user_id, 'joining_date', $joining_date );
                update_user_meta( $user_id, 'employment_type', $employment_type );
                update_user_meta( $user_id, 'basic_salary', $basic_salary );
                update_user_meta( $user_id, 'house_rent', $house_rent );
                update_user_meta( $user_id, 'medical_allow', $medical_allow );
                update_user_meta( $user_id, 'transport_allow', $transport_allow );
                update_user_meta( $user_id, 'gross_salary', $gross_salary );
                update_user_meta( $user_id, 'bank_name', $bank_name );
                update_user_meta( $user_id, 'bank_acc_no', $bank_acc_no );
                update_user_meta( $user_id, 'mfs_number', $mfs_number );
                update_user_meta( $user_id, 'emergency_name', $emergency_name );
                update_user_meta( $user_id, 'emergency_phone', $emergency_phone );
                update_user_meta( $user_id, 'profile_photo', $profile_photo );

                if ( function_exists( 'ifs_terp_log_activity' ) ) {
                    ifs_terp_log_activity( "Updated Staff Account: {$username} (ID: #UID-{$user_id})" );
                }

                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Staff profile updated successfully.', 'ifs-travel-erp' ) . '</div>';
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
                update_user_meta( $new_user_id, 'alt_phone', $alt_phone );
                update_user_meta( $new_user_id, 'nid_number', $nid_number );
                update_user_meta( $new_user_id, 'address', $address );
                update_user_meta( $new_user_id, 'employee_id', $employee_id );
                update_user_meta( $new_user_id, 'designation', $designation );
                update_user_meta( $new_user_id, 'department', $department );
                update_user_meta( $new_user_id, 'joining_date', $joining_date );
                update_user_meta( $new_user_id, 'employment_type', $employment_type );
                update_user_meta( $new_user_id, 'basic_salary', $basic_salary );
                update_user_meta( $new_user_id, 'house_rent', $house_rent );
                update_user_meta( $new_user_id, 'medical_allow', $medical_allow );
                update_user_meta( $new_user_id, 'transport_allow', $transport_allow );
                update_user_meta( $new_user_id, 'gross_salary', $gross_salary );
                update_user_meta( $new_user_id, 'bank_name', $bank_name );
                update_user_meta( $new_user_id, 'bank_acc_no', $bank_acc_no );
                update_user_meta( $new_user_id, 'mfs_number', $mfs_number );
                update_user_meta( $new_user_id, 'emergency_name', $emergency_name );
                update_user_meta( $new_user_id, 'emergency_phone', $emergency_phone );
                update_user_meta( $new_user_id, 'profile_photo', $profile_photo );
                
                $user_id = $new_user_id;
                $is_edit = true;

                if ( function_exists( 'ifs_terp_log_activity' ) ) {
                    ifs_terp_log_activity( "Created New Staff Account: {$username} (ID: #UID-{$new_user_id})" );
                }

                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'New staff member onboarded successfully.', 'ifs-travel-erp' ) . '</div>';
            } else {
                $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> ' . esc_html( $new_user_id->get_error_message() ) . '</div>';
            }
        }
    }

    $edit_user = $is_edit ? get_userdata( $user_id ) : false;
    
    // Meta variables with defaults
    $val_phone       = $is_edit ? get_user_meta( $user_id, 'phone_number', true ) : '';
    $val_alt_phone   = $is_edit ? get_user_meta( $user_id, 'alt_phone', true ) : '';
    $val_nid         = $is_edit ? get_user_meta( $user_id, 'nid_number', true ) : '';
    $val_address     = $is_edit ? get_user_meta( $user_id, 'address', true ) : '';
    $val_emp_id      = $is_edit ? get_user_meta( $user_id, 'employee_id', true ) : 'EMP-' . str_pad( (string) wp_rand( 100, 999 ), 4, '0', STR_PAD_LEFT );
    $val_designation = $is_edit ? get_user_meta( $user_id, 'designation', true ) : '';
    $val_dept        = $is_edit ? get_user_meta( $user_id, 'department', true ) : 'Ticketing';
    $val_joining     = $is_edit ? get_user_meta( $user_id, 'joining_date', true ) : current_time( 'Y-m-d' );
    $val_emp_type    = $is_edit ? get_user_meta( $user_id, 'employment_type', true ) : 'Permanent';
    
    $val_basic       = $is_edit ? (float) get_user_meta( $user_id, 'basic_salary', true ) : '';
    $val_house       = $is_edit ? (float) get_user_meta( $user_id, 'house_rent', true ) : '';
    $val_medical     = $is_edit ? (float) get_user_meta( $user_id, 'medical_allow', true ) : '';
    $val_transport   = $is_edit ? (float) get_user_meta( $user_id, 'transport_allow', true ) : '';
    $val_gross       = $is_edit ? (float) get_user_meta( $user_id, 'gross_salary', true ) : '';
    
    $val_bank        = $is_edit ? get_user_meta( $user_id, 'bank_name', true ) : '';
    $val_bank_acc    = $is_edit ? get_user_meta( $user_id, 'bank_acc_no', true ) : '';
    $val_mfs         = $is_edit ? get_user_meta( $user_id, 'mfs_number', true ) : '';
    $val_em_name     = $is_edit ? get_user_meta( $user_id, 'emergency_name', true ) : '';
    $val_em_phone    = $is_edit ? get_user_meta( $user_id, 'emergency_phone', true ) : '';
    $val_photo       = $is_edit ? get_user_meta( $user_id, 'profile_photo', true ) : '';
    
    $user_role = ( $edit_user && ! empty( $edit_user->roles ) ) ? reset( $edit_user->roles ) : 'ticketing_staff';
    $back_url  = admin_url( 'admin.php?page=ifs_travel_erp&tab=staff' );
    ?>

    <div class="wrap ifs-staff-form-workspace">
        <?php echo wp_kses_post( $message ); ?>

        <!-- Form Hero Header -->
        <div class="ifs-form-header-card">
            <div class="form-header-title-group">
                <span class="ifs-form-badge">
                    <span class="dashicons dashicons-businesswoman"></span> <?php esc_html_e( 'Human Resources & Access Control', 'ifs-travel-erp' ); ?>
                </span>
                <h2><?php echo $is_edit ? esc_html__( 'Update Employee Profile', 'ifs-travel-erp' ) : esc_html__( 'Onboard New Staff Member', 'ifs-travel-erp' ); ?></h2>
                <p><?php esc_html_e( 'Configure employee details, compensation packages, system access roles, and emergency records.', 'ifs-travel-erp' ); ?></p>
            </div>
            <div class="form-header-actions">
                <a href="<?php echo esc_url( $back_url ); ?>" class="ifs-btn-secondary">
                    <span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'Return to Directory', 'ifs-travel-erp' ); ?>
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
                        <div class="section-icon"><span class="dashicons dashicons-admin-network"></span></div>
                        <div>
                            <h4><?php esc_html_e( 'System Identity & Credentials', 'ifs-travel-erp' ); ?></h4>
                            <p><?php esc_html_e( 'Unique login identifier, password authentication, and administrative access privileges.', 'ifs-travel-erp' ); ?></p>
                        </div>
                    </div>

                    <div class="ifs-grid-layout">
                        <div class="ifs-field-group">
                            <label class="ifs-field-label" for="staff_username"><?php esc_html_e( 'Account Username', 'ifs-travel-erp' ); ?> <span class="required">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-admin-users field-icon"></span>
                                <input type="text" name="username" id="staff_username" required 
                                    <?php echo $is_edit ? 'readonly class="ifs-input-field readonly-state"' : 'class="ifs-input-field" placeholder="e.g. jdoe"'; ?> 
                                    value="<?php echo $is_edit ? esc_attr( $edit_user->user_login ) : ''; ?>">
                            </div>
                            <?php if ( $is_edit ) : ?>
                                <span class="field-hint"><?php esc_html_e( 'Usernames are permanent and cannot be modified.', 'ifs-travel-erp' ); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="ifs-field-group">
                            <label class="ifs-field-label" for="staff_email"><?php esc_html_e( 'Official Email Address', 'ifs-travel-erp' ); ?> <span class="required">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-email field-icon"></span>
                                <input type="email" name="email" id="staff_email" required class="ifs-input-field" 
                                    placeholder="e.g. staff@agency.com" 
                                    value="<?php echo $is_edit ? esc_attr( $edit_user->user_email ) : ''; ?>">
                            </div>
                        </div>

                        <div class="ifs-field-group">
                            <label class="ifs-field-label" for="staff_password">
                                <?php echo $is_edit ? esc_html__( 'Change Password (Leave blank to keep existing)', 'ifs-travel-erp' ) : esc_html__( 'Set Password', 'ifs-travel-erp' ) . ' <span class="required">*</span>'; ?>
                            </label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-lock field-icon"></span>
                                <input type="password" name="password" id="staff_password" <?php echo $is_edit ? '' : 'required'; ?> 
                                    class="ifs-input-field" placeholder="••••••••••••••••">
                            </div>
                        </div>

                        <div class="ifs-field-group">
                            <label class="ifs-field-label" for="staff_role"><?php esc_html_e( 'Assigned System Role', 'ifs-travel-erp' ); ?> <span class="required">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-groups field-icon"></span>
                                <select name="role" id="staff_role" class="ifs-input-field">
                                    <option value="administrator" <?php selected( $user_role, 'administrator' ); ?>><?php esc_html_e( 'Super Administrator (Full Master ERP Access)', 'ifs-travel-erp' ); ?></option>
                                    <option value="admin_manager" <?php selected( $user_role, 'admin_manager' ); ?>><?php esc_html_e( 'Branch / Operations Manager', 'ifs-travel-erp' ); ?></option>
                                    <option value="ticketing_staff" <?php selected( $user_role, 'ticketing_staff' ); ?>><?php esc_html_e( 'Air Ticketing Executive', 'ifs-travel-erp' ); ?></option>
                                    <option value="visa_officer" <?php selected( $user_role, 'visa_officer' ); ?>><?php esc_html_e( 'Visa Processing Officer', 'ifs-travel-erp' ); ?></option>
                                    <option value="accountant" <?php selected( $user_role, 'accountant' ); ?>><?php esc_html_e( 'Finance & Accounts Executive', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ifs-section-divider"></div>

                <!-- Section 2: Personal Profile & Demographics -->
                <div class="ifs-form-section">
                    <div class="section-title-wrap">
                        <div class="section-icon"><span class="dashicons dashicons-id-alt"></span></div>
                        <div>
                            <h4><?php esc_html_e( 'Personal Profile & Contact', 'ifs-travel-erp' ); ?></h4>
                            <p><?php esc_html_e( 'Personal verification details, government IDs, and contact telephone numbers.', 'ifs-travel-erp' ); ?></p>
                        </div>
                    </div>

                    <div class="ifs-grid-layout">
                        <div class="ifs-field-group">
                            <label class="ifs-field-label" for="staff_fname"><?php esc_html_e( 'First Name', 'ifs-travel-erp' ); ?> <span class="required">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-editor-textcolor field-icon"></span>
                                <input type="text" name="first_name" id="staff_fname" required class="ifs-input-field font-bold" 
                                    placeholder="e.g. John" 
                                    value="<?php echo $is_edit ? esc_attr( $edit_user->first_name ) : ''; ?>">
                            </div>
                        </div>

                        <div class="ifs-field-group">
                            <label class="ifs-field-label" for="staff_lname"><?php esc_html_e( 'Last Name', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-editor-textcolor field-icon"></span>
                                <input type="text" name="last_name" id="staff_lname" class="ifs-input-field font-bold" 
                                    placeholder="e.g. Doe" 
                                    value="<?php echo $is_edit ? esc_attr( $edit_user->last_name ) : ''; ?>">
                            </div>
                        </div>

                        <div class="ifs-field-group">
                            <label class="ifs-field-label" for="staff_phone"><?php esc_html_e( 'Primary Mobile', 'ifs-travel-erp' ); ?> <span class="required">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-phone field-icon"></span>
                                <input type="text" name="phone" id="staff_phone" required class="ifs-input-field font-mono" 
                                    placeholder="e.g. +880 17XXXXXXXX" 
                                    value="<?php echo esc_attr( $val_phone ); ?>">
                            </div>
                        </div>

                        <div class="ifs-field-group">
                            <label class="ifs-field-label" for="staff_alt_phone"><?php esc_html_e( 'Alternative / WhatsApp Phone', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-format-chat field-icon"></span>
                                <input type="text" name="alt_phone" id="staff_alt_phone" class="ifs-input-field font-mono" 
                                    placeholder="e.g. +880 18XXXXXXXX" 
                                    value="<?php echo esc_attr( $val_alt_phone ); ?>">
                            </div>
                        </div>

                        <div class="ifs-field-group">
                            <label class="ifs-field-label" for="staff_nid"><?php esc_html_e( 'National ID (NID) / Passport No', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-text field-icon"></span>
                                <input type="text" name="nid_number" id="staff_nid" class="ifs-input-field font-mono uppercase" 
                                    placeholder="e.g. 1990123456789" 
                                    value="<?php echo esc_attr( $val_nid ); ?>">
                            </div>
                        </div>

                        <div class="ifs-field-group">
                            <label class="ifs-field-label" for="staff_photo"><?php esc_html_e( 'Staff Photo Document URL', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-upload-field-row">
                                <div class="ifs-field-wrap" style="flex: 1;">
                                    <span class="dashicons dashicons-camera field-icon"></span>
                                    <input type="url" name="profile_photo" id="staff_photo" class="ifs-input-field" 
                                        placeholder="https://..." value="<?php echo esc_url( $val_photo ); ?>">
                                </div>
                                <button type="button" class="ifs-btn-upload-file" id="btn_upload_staff_photo">
                                    <span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Upload', 'ifs-travel-erp' ); ?>
                                </button>
                            </div>
                        </div>

                        <div class="ifs-field-group full-width">
                            <label class="ifs-field-label" for="staff_address"><?php esc_html_e( 'Residential Address', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-textarea-wrap">
                                <textarea name="address" id="staff_address" rows="2" class="ifs-input-field" 
                                    placeholder="Current residential street address, flat/house, area..."><?php echo esc_textarea( $val_address ); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ifs-section-divider"></div>

                <!-- Section 3: Employment Designation & Position -->
                <div class="ifs-form-section">
                    <div class="section-title-wrap">
                        <div class="section-icon"><span class="dashicons dashicons-portfolio"></span></div>
                        <div>
                            <h4><?php esc_html_e( 'Employment & Organization', 'ifs-travel-erp' ); ?></h4>
                            <p><?php esc_html_e( 'Staff code, job designation, department assignment, and official joining date.', 'ifs-travel-erp' ); ?></p>
                        </div>
                    </div>

                    <div class="ifs-grid-layout">
                        <div class="ifs-field-group">
                            <label class="ifs-field-label" for="emp_id"><?php esc_html_e( 'Employee Code / ID', 'ifs-travel-erp' ); ?> <span class="required">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-tag field-icon"></span>
                                <input type="text" name="employee_id" id="emp_id" required class="ifs-input-field font-mono font-bold uppercase" 
                                    placeholder="e.g. EMP-0012" value="<?php echo esc_attr( $val_emp_id ); ?>">
                            </div>
                        </div>

                        <div class="ifs-field-group">
                            <label class="ifs-field-label" for="emp_designation"><?php esc_html_e( 'Official Designation', 'ifs-travel-erp' ); ?> <span class="required">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-businessman field-icon"></span>
                                <input type="text" name="designation" id="emp_designation" required class="ifs-input-field" 
                                    placeholder="e.g. Senior Ticketing Executive" value="<?php echo esc_attr( $val_designation ); ?>">
                            </div>
                        </div>

                        <div class="ifs-field-group">
                            <label class="ifs-field-label" for="emp_department"><?php esc_html_e( 'Department / Division', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-category field-icon"></span>
                                <select name="department" id="emp_department" class="ifs-input-field">
                                    <option value="Ticketing" <?php selected( $val_dept, 'Ticketing' ); ?>><?php esc_html_e( 'Air Ticketing & GDS', 'ifs-travel-erp' ); ?></option>
                                    <option value="Visa Processing" <?php selected( $val_dept, 'Visa Processing' ); ?>><?php esc_html_e( 'Visa Processing & Embassy', 'ifs-travel-erp' ); ?></option>
                                    <option value="Hajj & Umrah" <?php selected( $val_dept, 'Hajj & Umrah' ); ?>><?php esc_html_e( 'Hajj & Umrah Operations', 'ifs-travel-erp' ); ?></option>
                                    <option value="Holiday & Tours" <?php selected( $val_dept, 'Holiday & Tours' ); ?>><?php esc_html_e( 'Tour Packages & DMC', 'ifs-travel-erp' ); ?></option>
                                    <option value="Hotel Booking" <?php selected( $val_dept, 'Hotel Booking' ); ?>><?php esc_html_e( 'Hotel Reservations', 'ifs-travel-erp' ); ?></option>
                                    <option value="Accounts & Finance" <?php selected( $val_dept, 'Accounts & Finance' ); ?>><?php esc_html_e( 'Accounts & Finance', 'ifs-travel-erp' ); ?></option>
                                    <option value="Administration" <?php selected( $val_dept, 'Administration' ); ?>><?php esc_html_e( 'Administration / HR', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-group">
                            <label class="ifs-field-label" for="emp_type"><?php esc_html_e( 'Employment Type', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-clock field-icon"></span>
                                <select name="employment_type" id="emp_type" class="ifs-input-field">
                                    <option value="Permanent" <?php selected( $val_emp_type, 'Permanent' ); ?>><?php esc_html_e( 'Permanent / Full-Time', 'ifs-travel-erp' ); ?></option>
                                    <option value="Contractual" <?php selected( $val_emp_type, 'Contractual' ); ?>><?php esc_html_e( 'Contractual', 'ifs-travel-erp' ); ?></option>
                                    <option value="Probationary" <?php selected( $val_emp_type, 'Probationary' ); ?>><?php esc_html_e( 'Probationary', 'ifs-travel-erp' ); ?></option>
                                    <option value="Part-Time" <?php selected( $val_emp_type, 'Part-Time' ); ?>><?php esc_html_e( 'Part-Time', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-group full-width">
                            <label class="ifs-field-label" for="emp_joining"><?php esc_html_e( 'Joining Date', 'ifs-travel-erp' ); ?> <span class="required">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-calendar-alt field-icon"></span>
                                <input type="date" name="joining_date" id="emp_joining" required class="ifs-input-field" 
                                    value="<?php echo esc_attr( $val_joining ); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ifs-section-divider"></div>

                <!-- Section 4: Salary Structure & Compensation -->
                <div class="ifs-form-section">
                    <div class="section-title-wrap">
                        <div class="section-icon"><span class="dashicons dashicons-money-alt"></span></div>
                        <div>
                            <h4><?php esc_html_e( 'Compensation & Salary Structure', 'ifs-travel-erp' ); ?></h4>
                            <p><?php esc_html_e( 'Base salary break-down, medical, house rent, and live calculated gross remuneration.', 'ifs-travel-erp' ); ?></p>
                        </div>
                    </div>

                    <div class="ifs-grid-layout">
                        <div class="ifs-field-group">
                            <label class="ifs-field-label" for="inp_basic_salary"><?php esc_html_e( 'Basic Salary (৳)', 'ifs-travel-erp' ); ?> <span class="required">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-money-alt field-icon"></span>
                                <input type="number" step="0.01" name="basic_salary" id="inp_basic_salary" required 
                                    class="ifs-input-field font-mono font-bold" placeholder="0.00" 
                                    value="<?php echo esc_attr( $val_basic ); ?>">
                            </div>
                        </div>

                        <div class="ifs-field-group">
                            <label class="ifs-field-label" for="inp_house_rent"><?php esc_html_e( 'House Rent Allowance (৳)', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-admin-home field-icon"></span>
                                <input type="number" step="0.01" name="house_rent" id="inp_house_rent" 
                                    class="ifs-input-field font-mono" placeholder="0.00" 
                                    value="<?php echo esc_attr( $val_house ); ?>">
                            </div>
                        </div>

                        <div class="ifs-field-group">
                            <label class="ifs-field-label" for="inp_medical_allow"><?php esc_html_e( 'Medical Allowance (৳)', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-heart field-icon"></span>
                                <input type="number" step="0.01" name="medical_allow" id="inp_medical_allow" 
                                    class="ifs-input-field font-mono" placeholder="0.00" 
                                    value="<?php echo esc_attr( $val_medical ); ?>">
                            </div>
                        </div>

                        <div class="ifs-field-group">
                            <label class="ifs-field-label" for="inp_transport_allow"><?php esc_html_e( 'Conveyance / Transport (৳)', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-car field-icon"></span>
                                <input type="number" step="0.01" name="transport_allow" id="inp_transport_allow" 
                                    class="ifs-input-field font-mono" placeholder="0.00" 
                                    value="<?php echo esc_attr( $val_transport ); ?>">
                            </div>
                        </div>

                        <div class="ifs-field-group full-width">
                            <label class="ifs-field-label"><?php esc_html_e( 'Calculated Monthly Gross Salary (৳)', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-calculator field-icon"></span>
                                <input type="text" id="inp_gross_salary" readonly 
                                    class="ifs-input-field font-mono font-bold color-emerald bg-light" 
                                    value="<?php echo esc_attr( number_format( (float) $val_gross, 2 ) ); ?>">
                            </div>
                            <span class="field-hint"><?php esc_html_e( 'Sum of Basic + House Rent + Medical + Conveyance automatically calculated.', 'ifs-travel-erp' ); ?></span>
                        </div>
                    </div>
                </div>

                <div class="ifs-section-divider"></div>

                <!-- Section 5: Banking & Emergency Contact -->
                <div class="ifs-form-section">
                    <div class="section-title-wrap">
                        <div class="section-icon"><span class="dashicons dashicons-vault"></span></div>
                        <div>
                            <h4><?php esc_html_e( 'Disbursement Bank & Emergency Contact', 'ifs-travel-erp' ); ?></h4>
                            <p><?php esc_html_e( 'Salary disbursement accounts and next-of-kin emergency contact numbers.', 'ifs-travel-erp' ); ?></p>
                        </div>
                    </div>

                    <div class="ifs-grid-layout">
                        <div class="ifs-field-group">
                            <label class="ifs-field-label" for="bank_name"><?php esc_html_e( 'Disbursement Bank Name', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-building field-icon"></span>
                                <input type="text" name="bank_name" id="bank_name" class="ifs-input-field" 
                                    placeholder="e.g. Dutch Bangla Bank, City Bank" value="<?php echo esc_attr( $val_bank ); ?>">
                            </div>
                        </div>

                        <div class="ifs-field-group">
                            <label class="ifs-field-label" for="bank_acc_no"><?php esc_html_e( 'Bank Account Number', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-media-text field-icon"></span>
                                <input type="text" name="bank_acc_no" id="bank_acc_no" class="ifs-input-field font-mono" 
                                    placeholder="e.g. 1102983489201" value="<?php echo esc_attr( $val_bank_acc ); ?>">
                            </div>
                        </div>

                        <div class="ifs-field-group">
                            <label class="ifs-field-label" for="mfs_number"><?php esc_html_e( 'Mobile Banking Wallet (bKash/Nagad)', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-smartphone field-icon"></span>
                                <input type="text" name="mfs_number" id="mfs_number" class="ifs-input-field font-mono" 
                                    placeholder="017XXXXXXXX" value="<?php echo esc_attr( $val_mfs ); ?>">
                            </div>
                        </div>

                        <div class="ifs-field-group">
                            <label class="ifs-field-label" for="emergency_name"><?php esc_html_e( 'Emergency Contact Name', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-businessman field-icon"></span>
                                <input type="text" name="emergency_name" id="emergency_name" class="ifs-input-field" 
                                    placeholder="Next of Kin / Relative" value="<?php echo esc_attr( $val_em_name ); ?>">
                            </div>
                        </div>

                        <div class="ifs-field-group full-width">
                            <label class="ifs-field-label" for="emergency_phone"><?php esc_html_e( 'Emergency Contact Phone', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-warning field-icon"></span>
                                <input type="text" name="emergency_phone" id="emergency_phone" class="ifs-input-field font-mono" 
                                    placeholder="e.g. +880 19XXXXXXXX" value="<?php echo esc_attr( $val_em_phone ); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Action Buttons -->
                <div class="ifs-action-strip">
                    <a href="<?php echo esc_url( $back_url ); ?>" class="ifs-btn-secondary"><?php esc_html_e( 'Cancel & Return', 'ifs-travel-erp' ); ?></a>
                    <button type="submit" name="ifs_staff_submit" class="ifs-btn-primary">
                        <span class="dashicons dashicons-saved"></span>
                        <?php echo $is_edit ? esc_html__( 'Save Profile Changes', 'ifs-travel-erp' ) : esc_html__( 'Complete Staff Onboarding', 'ifs-travel-erp' ); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Stylesheet: Flat Minimal UI, Zero Shadows & Strict 42px Uniform Field Heights -->
    <style>
        .ifs-staff-form-workspace {
            max-width: 960px;
            margin: 20px auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #0f172a;
            box-sizing: border-box;
        }
        .ifs-staff-form-workspace *,
        .ifs-staff-form-workspace *::before,
        .ifs-staff-form-workspace *::after {
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
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
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
        input[type="date"].ifs-input-field { cursor: pointer; }
        input[type="date"].ifs-input-field::-webkit-calendar-picker-indicator { 
            opacity: 0.6; 
            cursor: pointer; 
            margin-right: -4px; 
            transition: opacity 0.2s ease; 
        }
        input[type="date"].ifs-input-field::-webkit-calendar-picker-indicator:hover { opacity: 1; }

        .ifs-input-field:focus { 
            border-color: #003376 !important; 
            background-color: #f8fafc !important; 
        }
        .ifs-field-wrap:focus-within .field-icon { 
            color: #003376; 
        }
        .ifs-input-field.readonly-state {
            background: #f8fafc !important;
            color: #64748b !important;
            cursor: not-allowed;
        }

        .ifs-textarea-wrap { width: 100%; }
        .ifs-textarea-wrap textarea.ifs-input-field {
            width: 100% !important;
            height: auto !important;
            min-height: 70px !important;
            padding: 10px 14px !important;
            line-height: 1.5 !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px !important;
            font-size: 13.5px !important;
            color: #0f172a !important;
            background-color: #ffffff !important;
            outline: none !important;
            transition: border-color 0.2s ease, background-color 0.2s ease;
            font-family: inherit;
            resize: vertical;
            display: block;
        }

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
        .uppercase { text-transform: uppercase; }
        .bg-light { background-color: #f8fafc !important; }
        .color-emerald { color: #059669 !important; }

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

    <!-- Calculation Engine & Media Uploader Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const inpBasic     = document.getElementById('inp_basic_salary');
        const inpHouse     = document.getElementById('inp_house_rent');
        const inpMed       = document.getElementById('inp_medical_allow');
        const inpTransport = document.getElementById('inp_transport_allow');
        const inpGross     = document.getElementById('inp_gross_salary');

        function calculateGrossSalary() {
            const b = parseFloat(inpBasic ? inpBasic.value : 0) || 0;
            const h = parseFloat(inpHouse ? inpHouse.value : 0) || 0;
            const m = parseFloat(inpMed ? inpMed.value : 0) || 0;
            const t = parseFloat(inpTransport ? inpTransport.value : 0) || 0;
            const gross = b + h + m + t;

            if (inpGross) {
                inpGross.value = gross.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        }

        [inpBasic, inpHouse, inpMed, inpTransport].forEach(el => {
            if (el) {
                el.addEventListener('input', calculateGrossSalary);
                el.addEventListener('change', calculateGrossSalary);
            }
        });

        // Initialize Calculation on Load
        calculateGrossSalary();

        // WP Media Uploader for Staff Photo
        const uploadBtn = document.getElementById('btn_upload_staff_photo');
        const photoInput = document.getElementById('staff_photo');

        if (uploadBtn && window.wp && wp.media) {
            uploadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const customUploader = wp.media({
                    title: 'Select or Upload Staff Profile Photo',
                    button: { text: 'Select Photo' },
                    multiple: false
                }).on('select', function() {
                    const attachment = customUploader.state().get('selection').first().toJSON();
                    if (attachment && attachment.url && photoInput) {
                        photoInput.value = attachment.url;
                    }
                }).open();
            });
        }
    });
    </script>
    <?php
}