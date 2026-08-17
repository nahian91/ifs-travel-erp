<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_staff_add_edit_page() {
    $user_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
    $is_edit = ( $user_id > 0 );
    $message = '';

    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_staff_submit'] ) ) {
        check_admin_referer( 'ifs_staff_save_action', 'ifs_staff_nonce' );

        $username   = sanitize_user( $_POST['username'] );
        $email      = sanitize_email( $_POST['email'] );
        $first_name = sanitize_text_field( $_POST['first_name'] );
        $last_name  = sanitize_text_field( $_POST['last_name'] );
        $phone      = sanitize_text_field( $_POST['phone'] );
        $role       = sanitize_text_field( $_POST['role'] );
        $password   = $_POST['password'];

        if ( $is_edit ) {
            $userdata = array(
                'ID'           => $user_id,
                'user_email'   => $email,
                'first_name'   => $first_name,
                'last_name'    => $last_name,
                'display_name' => trim( $first_name . ' ' . $last_name ),
                'role'         => $role
            );
            if ( ! empty( $password ) ) {
                $userdata['user_pass'] = $password;
            }
            $updated = wp_update_user( $userdata );
            if ( ! is_wp_error( $updated ) ) {
                update_user_meta( $user_id, 'phone_number', $phone );
                
                if ( function_exists( 'ifs_terp_log_activity' ) ) {
                    ifs_terp_log_activity( "Updated Staff Account: " . $username . " (ID: #UID-$user_id)" );
                }

                $message = '<div class="notice notice-success is-dismissible"><p>Staff profile updated successfully.</p></div>';
            } else {
                $message = '<div class="notice notice-error is-dismissible"><p>' . esc_html( $updated->get_error_message() ) . '</p></div>';
            }
        } else {
            $userdata = array(
                'user_login'   => $username,
                'user_email'   => $email,
                'user_pass'    => $password,
                'first_name'   => $first_name,
                'last_name'    => $last_name,
                'display_name' => trim( $first_name . ' ' . $last_name ),
                'role'         => $role
            );
            $new_user_id = wp_insert_user( $userdata );
            if ( ! is_wp_error( $new_user_id ) ) {
                update_user_meta( $new_user_id, 'phone_number', $phone );
                $user_id = $new_user_id;
                $is_edit = true;

                if ( function_exists( 'ifs_terp_log_activity' ) ) {
                    ifs_terp_log_activity( "Created New Staff Account: " . $username . " (ID: #UID-$new_user_id)" );
                }

                $message = '<div class="notice notice-success is-dismissible"><p>New staff member onboarded successfully.</p></div>';
            } else {
                $message = '<div class="notice notice-error is-dismissible"><p>' . esc_html( $new_user_id->get_error_message() ) . '</p></div>';
            }
        }
    }

    $edit_user  = $is_edit ? get_userdata( $user_id ) : false;
    $edit_phone = $is_edit ? get_user_meta( $user_id, 'phone_number', true ) : '';
    $user_role  = ( $edit_user && ! empty( $edit_user->roles ) ) ? reset( $edit_user->roles ) : 'ticketing_staff';
    ?>

    <?php echo $message; ?>

    <div class="arms-form-container" style="background:#fff; padding:25px; border-radius:8px; border:1px solid #e2e8f0; max-width: 850px; margin-top: 20px;">
        <h3 style="margin-top:0; color:#003376; border-bottom:1px solid #e2e8f0; padding-bottom:12px;">
            <?php echo $is_edit ? 'Edit Staff Account: ' . esc_html( $edit_user->user_login ) : 'Onboard New Staff / Employee'; ?>
        </h3>

        <form method="post" action="">
            <?php wp_nonce_field( 'ifs_staff_save_action', 'ifs_staff_nonce' ); ?>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px;">
                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Username *</label>
                    <input type="text" name="username" required <?php echo $is_edit ? 'readonly style="background:#f1f5f9; width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;"' : 'style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;"'; ?> value="<?php echo $is_edit ? esc_attr( $edit_user->user_login ) : ''; ?>">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Email Address *</label>
                    <input type="email" name="email" required value="<?php echo $is_edit ? esc_attr( $edit_user->user_email ) : ''; ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">First Name *</label>
                    <input type="text" name="first_name" required value="<?php echo $is_edit ? esc_attr( $edit_user->first_name ) : ''; ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Last Name</label>
                    <input type="text" name="last_name" value="<?php echo $is_edit ? esc_attr( $edit_user->last_name ) : ''; ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Phone / Mobile</label>
                    <input type="text" name="phone" value="<?php echo esc_attr( $edit_phone ); ?>" placeholder="e.g. 017XXXXXXXX" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">ERP Role & Privileges *</label>
                    <select name="role" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                        <option value="administrator" <?php selected( $user_role, 'administrator' ); ?>>Super Administrator (Full Access)</option>
                        <option value="admin_manager" <?php selected( $user_role, 'admin_manager' ); ?>>Branch / Operations Manager</option>
                        <option value="ticketing_staff" <?php selected( $user_role, 'ticketing_staff' ); ?>>Air Ticketing Executive</option>
                        <option value="visa_officer" <?php selected( $user_role, 'visa_officer' ); ?>>Visa Processing Officer</option>
                        <option value="accountant" <?php selected( $user_role, 'accountant' ); ?>>Finance & Accounts Executive</option>
                    </select>
                </div>

                <div style="grid-column: 1 / -1;">
                    <label style="display:block; font-weight:600; margin-bottom:5px;">
                        <?php echo $is_edit ? 'Change Login Password (leave blank to keep current)' : 'Account Login Password *'; ?>
                    </label>
                    <input type="password" name="password" <?php echo $is_edit ? '' : 'required'; ?> placeholder="••••••••••••" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>
            </div>

            <div style="margin-top: 25px; border-top:1px solid #e2e8f0; padding-top:15px; display:flex; justify-content:space-between; align-items:center;">
                <a href="<?php echo admin_url( 'admin.php?page=ifs_travel_erp&tab=staff' ); ?>" style="color:#64748b; text-decoration:none;">Cancel</a>
                <button type="submit" name="ifs_staff_submit" style="background: #003376; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; font-weight: 600; cursor: pointer;">
                    <?php echo $is_edit ? 'Update Staff Member' : 'Save Staff Account'; ?>
                </button>
            </div>
        </form>
    </div>
    <?php
}