<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Staff & Employee Directory Console
 * Flat Minimal UI: No Shadows, Strict Equal Sizing, View Modal Dossier & Secure Delete
 */
function ifs_terp_staff_list_page() {
    $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=staff' );

    // Handle Delete Action with Nonce Verification
    if ( isset( $_GET['sub'] ) && 'delete' === sanitize_key( wp_unslash( $_GET['sub'] ) ) && isset( $_GET['id'] ) ) {
        // Check permission / capabilities
        if ( function_exists( 'ifs_terp_has_access' ) ) {
            if ( ! ifs_terp_has_access( array( 'admin_manager' ) ) && ! current_user_can( 'delete_users' ) ) {
                wp_die( esc_html__( 'Access Denied: You do not possess the required privilege level to delete staff accounts.', 'ifs-travel-erp' ) );
            }
        } elseif ( ! current_user_can( 'delete_users' ) ) {
            wp_die( esc_html__( 'Access Denied: You do not have permission to delete staff accounts.', 'ifs-travel-erp' ) );
        }

        $del_id = absint( wp_unslash( $_GET['id'] ) );
        $nonce  = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

        if ( $del_id <= 0 || ! wp_verify_nonce( $nonce, 'delete_staff_' . $del_id ) ) {
            wp_die( esc_html__( 'Security check failed or invalid request.', 'ifs-travel-erp' ) );
        }

        if ( $del_id === get_current_user_id() ) {
            wp_die( esc_html__( 'Security restriction: You cannot delete your own active account.', 'ifs-travel-erp' ) );
        }

        require_once ABSPATH . 'wp-admin/includes/user.php';
        $user_obj   = get_userdata( $del_id );
        $user_login = $user_obj ? $user_obj->user_login : 'User #' . $del_id;

        wp_delete_user( $del_id );

        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            ifs_terp_log_activity( "Deleted Staff Member Account: " . $user_login . " (ID: #UID-" . $del_id . ")" );
        }

        $redirect_url = add_query_arg(
            array(
                'page' => 'ifs_travel_erp',
                'tab'  => 'staff',
                'msg'  => 'deleted',
            ),
            admin_url( 'admin.php' )
        );

        if ( ! headers_sent() ) {
            wp_safe_redirect( $redirect_url );
            exit;
        } else {
            echo '<script type="text/javascript">window.location.replace("' . esc_url_raw( $redirect_url ) . '");</script>';
            exit;
        }
    }

    $message = '';
    if ( isset( $_GET['msg'] ) && 'deleted' === sanitize_key( wp_unslash( $_GET['msg'] ) ) ) {
        $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Staff member record permanently removed.', 'ifs-travel-erp' ) . '</div>';
    }

    $users = get_users( array( 'orderby' => 'ID', 'order' => 'DESC' ) );
    ?>

    <div class="wrap ifs-staff-list-workspace">
        <?php echo wp_kses_post( $message ); ?>

        <!-- Master Data Table Card -->
        <div class="ifs-table-card">
            <!-- Custom DataTables Controls Toolbar -->
            <div class="ifs-custom-table-controls">
                <div class="ifs-per-page-wrap">
                    <label for="ifsStaffPerPageSelect"><?php esc_html_e( 'Show', 'ifs-travel-erp' ); ?></label>
                    <select id="ifsStaffPerPageSelect" class="ifs-select-control">
                        <option value="10">10</option>
                        <option value="15" selected>15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span><?php esc_html_e( 'entries', 'ifs-travel-erp' ); ?></span>
                </div>
                <div class="ifs-live-search-wrap">
                    <label for="ifsStaffSearchInput"><span class="dashicons dashicons-search"></span></label>
                    <input type="text" id="ifsStaffSearchInput" class="ifs-search-input" placeholder="<?php esc_attr_e( 'Search by name, username, phone, email, designation...', 'ifs-travel-erp' ); ?>">
                </div>
            </div>

            <div class="ifs-table-responsive-wrapper">
                <table class="ifs-pro-datatable" id="ifsStaffTable">
                    <thead>
                        <tr>
                            <th style="width: 90px;"><?php esc_html_e( 'EMP ID', 'ifs-travel-erp' ); ?></th>
                            <th><?php esc_html_e( 'Staff Member Profile', 'ifs-travel-erp' ); ?></th>
                            <th><?php esc_html_e( 'Designation & Dept', 'ifs-travel-erp' ); ?></th>
                            <th><?php esc_html_e( 'Contact Info', 'ifs-travel-erp' ); ?></th>
                            <th><?php esc_html_e( 'Assigned Role', 'ifs-travel-erp' ); ?></th>
                            <th style="text-align: right;"><?php esc_html_e( 'Gross Salary', 'ifs-travel-erp' ); ?></th>
                            <th style="text-align: right; width: 220px;" data-sortable="false"><?php esc_html_e( 'Actions', 'ifs-travel-erp' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $users ) ) : foreach ( $users as $u ) : 
                            $phone       = get_user_meta( $u->ID, 'phone_number', true ) ?: '-';
                            $alt_phone   = get_user_meta( $u->ID, 'alt_phone', true ) ?: '-';
                            $nid         = get_user_meta( $u->ID, 'nid_number', true ) ?: '-';
                            $address     = get_user_meta( $u->ID, 'address', true ) ?: '-';
                            $emp_id      = get_user_meta( $u->ID, 'employee_id', true ) ?: 'EMP-' . $u->ID;
                            $designation = get_user_meta( $u->ID, 'designation', true ) ?: 'Staff Member';
                            $department  = get_user_meta( $u->ID, 'department', true ) ?: 'General';
                            $joining     = get_user_meta( $u->ID, 'joining_date', true ) ?: '-';
                            $emp_type    = get_user_meta( $u->ID, 'employment_type', true ) ?: 'Permanent';
                            $basic_sal   = (float) get_user_meta( $u->ID, 'basic_salary', true );
                            $house_rent  = (float) get_user_meta( $u->ID, 'house_rent', true );
                            $med_allow   = (float) get_user_meta( $u->ID, 'medical_allow', true );
                            $trans_allow = (float) get_user_meta( $u->ID, 'transport_allow', true );
                            $gross_sal   = (float) get_user_meta( $u->ID, 'gross_salary', true );
                            if ( $gross_sal <= 0 && ( $basic_sal + $house_rent + $med_allow + $trans_allow ) > 0 ) {
                                $gross_sal = $basic_sal + $house_rent + $med_allow + $trans_allow;
                            }
                            $bank_name   = get_user_meta( $u->ID, 'bank_name', true ) ?: '-';
                            $bank_acc    = get_user_meta( $u->ID, 'bank_acc_no', true ) ?: '-';
                            $mfs_no      = get_user_meta( $u->ID, 'mfs_number', true ) ?: '-';
                            $em_name     = get_user_meta( $u->ID, 'emergency_name', true ) ?: '-';
                            $em_phone    = get_user_meta( $u->ID, 'emergency_phone', true ) ?: '-';
                            $photo_url   = get_user_meta( $u->ID, 'profile_photo', true );

                            $first_role  = ! empty( $u->roles ) ? reset( $u->roles ) : 'none';
                            $role_title  = ucwords( str_replace( '_', ' ', $first_role ) );
                            $is_self     = ( $u->ID === get_current_user_id() );

                            $badge_class = 'role-badge-default';
                            if ( $first_role === 'administrator' ) {
                                $badge_class = 'role-badge-admin';
                            } elseif ( $first_role === 'admin_manager' ) {
                                $badge_class = 'role-badge-manager';
                            } elseif ( $first_role === 'ticketing_staff' ) {
                                $badge_class = 'role-badge-ticketing';
                            } elseif ( $first_role === 'visa_officer' ) {
                                $badge_class = 'role-badge-visa';
                            } elseif ( $first_role === 'accountant' ) {
                                $badge_class = 'role-badge-accountant';
                            }

                            // Compile user dataset for the view modal
                            $user_view_payload = array(
                                'id'          => $u->ID,
                                'emp_id'      => $emp_id,
                                'name'        => $u->display_name,
                                'username'    => $u->user_login,
                                'email'       => $u->user_email,
                                'phone'       => $phone,
                                'alt_phone'   => $alt_phone,
                                'nid'         => $nid,
                                'address'     => $address,
                                'role'        => $role_title,
                                'designation' => $designation,
                                'department'  => $department,
                                'joining'     => ( $joining !== '-' ) ? date_i18n( 'd F, Y', strtotime( $joining ) ) : '-',
                                'emp_type'    => $emp_type,
                                'basic'       => number_format( $basic_sal, 2 ),
                                'house'       => number_format( $house_rent, 2 ),
                                'medical'     => number_format( $med_allow, 2 ),
                                'transport'   => number_format( $trans_allow, 2 ),
                                'gross'       => number_format( $gross_sal, 2 ),
                                'bank'        => $bank_name,
                                'bank_acc'    => $bank_acc,
                                'mfs'         => $mfs_no,
                                'em_name'     => $em_name,
                                'em_phone'    => $em_phone,
                                'photo'       => $photo_url ? esc_url( $photo_url ) : '',
                            );
                        ?>
                            <tr>
                                <td>
                                    <span class="ifs-id-badge font-mono"><?php echo esc_html( $emp_id ); ?></span>
                                </td>
                                <td>
                                    <div class="ifs-staff-cell">
                                        <div class="ifs-staff-avatar">
                                            <?php if ( ! empty( $photo_url ) ) : ?>
                                                <img src="<?php echo esc_url( $photo_url ); ?>" alt="Avatar" class="avatar-img-fit" />
                                            <?php else : ?>
                                                <?php echo get_avatar( $u->ID, 38, '', '', array( 'class' => 'avatar-img-fit' ) ); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <strong class="ifs-staff-name"><?php echo esc_html( $u->display_name ); ?></strong>
                                            <div class="ifs-staff-submeta">
                                                <span>@<?php echo esc_html( $u->user_login ); ?></span>
                                                <?php if ( $is_self ) : ?>
                                                    <span class="meta-dot"></span>
                                                    <span class="self-tag"><?php esc_html_e( 'You', 'ifs-travel-erp' ); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="font-bold color-navy"><?php echo esc_html( $designation ); ?></div>
                                    <div class="sub-dept-label"><?php echo esc_html( $department ); ?></div>
                                </td>
                                <td>
                                    <div class="contact-line">
                                        <span class="dashicons dashicons-phone"></span> <?php echo esc_html( $phone ); ?>
                                    </div>
                                    <div class="contact-line email-color">
                                        <span class="dashicons dashicons-email"></span> <?php echo esc_html( $u->user_email ); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="ifs-role-badge <?php echo esc_attr( $badge_class ); ?>">
                                        <?php echo esc_html( $role_title ); ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <strong class="font-mono color-emerald">৳<?php echo esc_html( number_format( $gross_sal, 2 ) ); ?></strong>
                                </td>
                                <td style="text-align: right;">
                                    <div class="ifs-action-pills">
                                        <!-- View Button with Icon & Text -->
                                        <button type="button" class="btn-view-staff" 
                                            data-payload="<?php echo esc_attr( wp_json_encode( $user_view_payload ) ); ?>" 
                                            style="display: inline-flex; align-items: center; gap: 4px; background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; text-decoration: none; cursor: pointer; transition: all 0.18s ease;"
                                            title="<?php esc_attr_e( 'View Full Profile', 'ifs-travel-erp' ); ?>">
                                            <span class="dashicons dashicons-visibility" style="font-size: 14px; width: 14px; height: 14px;"></span>
                                            <span><?php esc_html_e( 'View', 'ifs-travel-erp' ); ?></span>
                                        </button>

                                        <!-- Edit Button with Icon & Text -->
                                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=staff&sub=edit&id=' . $u->ID ) ); ?>" 
                                            style="display: inline-flex; align-items: center; gap: 4px; background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; text-decoration: none; transition: all 0.18s ease;"
                                            title="<?php esc_attr_e( 'Edit Profile', 'ifs-travel-erp' ); ?>">
                                            <span class="dashicons dashicons-edit" style="font-size: 14px; width: 14px; height: 14px;"></span>
                                            <span><?php esc_html_e( 'Edit', 'ifs-travel-erp' ); ?></span>
                                        </a>

                                        <!-- Delete Button with Icon & Text -->
                                        <?php if ( ! $is_self ) : ?>
                                            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=staff&sub=delete&id=' . $u->ID ), 'delete_staff_' . $u->ID ) ); ?>" 
                                                style="display: inline-flex; align-items: center; gap: 4px; background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; text-decoration: none; transition: all 0.18s ease;"
                                                onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to permanently delete this staff member?', 'ifs-travel-erp' ) ); ?>');" 
                                                title="<?php esc_attr_e( 'Delete User', 'ifs-travel-erp' ); ?>">
                                                <span class="dashicons dashicons-trash" style="font-size: 14px; width: 14px; height: 14px;"></span>
                                                <span><?php esc_html_e( 'Delete', 'ifs-travel-erp' ); ?></span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr>
                                <td colspan="7" class="ifs-empty-table">
                                    <div class="ifs-empty-state">
                                        <span class="dashicons dashicons-businesswoman"></span>
                                        <h4><?php esc_html_e( 'No Staff Members Found', 'ifs-travel-erp' ); ?></h4>
                                        <p><?php esc_html_e( 'No staff records are currently registered.', 'ifs-travel-erp' ); ?></p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Staff Dossier Detail Modal (View Only) -->
        <div id="ifsStaffModal" class="ifs-modal-backdrop" style="display: none;">
            <div class="ifs-modal-content">
                <div class="ifs-modal-header">
                    <div class="modal-title-left">
                        <span class="ifs-modal-badge"><?php esc_html_e( 'Employee Dossier', 'ifs-travel-erp' ); ?></span>
                        <h3 id="modal_staff_name">Employee Profile</h3>
                    </div>
                    <button type="button" class="ifs-modal-close" id="btnCloseStaffModal">&times;</button>
                </div>
                <div class="ifs-modal-body">
                    <!-- Hero Header inside Modal -->
                    <div class="modal-hero-strip">
                        <div class="modal-avatar-box" id="modal_avatar_box">
                            <span class="dashicons dashicons-admin-users"></span>
                        </div>
                        <div class="modal-hero-details">
                            <div class="modal-id-row">
                                <span class="badge-emp-id font-mono" id="modal_emp_id">EMP-0000</span>
                                <span class="ifs-role-badge" id="modal_role_badge">Role</span>
                                <span class="badge-type" id="modal_emp_type">Permanent</span>
                            </div>
                            <h4 id="modal_display_name">Name</h4>
                            <div class="modal-sub-details" id="modal_desig_dept">Designation &bull; Department</div>
                        </div>
                    </div>

                    <!-- Personal & Employment Details Grid -->
                    <div class="modal-grid-2">
                        <div class="modal-card-block">
                            <h5 class="block-title"><span class="dashicons dashicons-id"></span> <?php esc_html_e( 'Personal & Contact Information', 'ifs-travel-erp' ); ?></h5>
                            <div class="modal-info-row">
                                <span class="lbl"><?php esc_html_e( 'Official Email:', 'ifs-travel-erp' ); ?></span>
                                <strong class="val" id="modal_email">-</strong>
                            </div>
                            <div class="modal-info-row">
                                <span class="lbl"><?php esc_html_e( 'Primary Phone:', 'ifs-travel-erp' ); ?></span>
                                <strong class="val font-mono" id="modal_phone">-</strong>
                            </div>
                            <div class="modal-info-row">
                                <span class="lbl"><?php esc_html_e( 'Alternative Phone:', 'ifs-travel-erp' ); ?></span>
                                <strong class="val font-mono" id="modal_alt_phone">-</strong>
                            </div>
                            <div class="modal-info-row">
                                <span class="lbl"><?php esc_html_e( 'NID / Passport No:', 'ifs-travel-erp' ); ?></span>
                                <strong class="val font-mono" id="modal_nid">-</strong>
                            </div>
                            <div class="modal-info-row full-w">
                                <span class="lbl"><?php esc_html_e( 'Address:', 'ifs-travel-erp' ); ?></span>
                                <span class="val" id="modal_address">-</span>
                            </div>
                        </div>

                        <div class="modal-card-block">
                            <h5 class="block-title"><span class="dashicons dashicons-portfolio"></span> <?php esc_html_e( 'Organizational Placement', 'ifs-travel-erp' ); ?></h5>
                            <div class="modal-info-row">
                                <span class="lbl"><?php esc_html_e( 'Designation:', 'ifs-travel-erp' ); ?></span>
                                <strong class="val color-navy" id="modal_designation">-</strong>
                            </div>
                            <div class="modal-info-row">
                                <span class="lbl"><?php esc_html_e( 'Department:', 'ifs-travel-erp' ); ?></span>
                                <strong class="val" id="modal_department">-</strong>
                            </div>
                            <div class="modal-info-row">
                                <span class="lbl"><?php esc_html_e( 'Official Joining:', 'ifs-travel-erp' ); ?></span>
                                <strong class="val" id="modal_joining">-</strong>
                            </div>
                            <div class="modal-info-row">
                                <span class="lbl"><?php esc_html_e( 'System Username:', 'ifs-travel-erp' ); ?></span>
                                <strong class="val font-mono" id="modal_username">-</strong>
                            </div>
                            <div class="modal-info-row">
                                <span class="lbl"><?php esc_html_e( 'Emergency Contact:', 'ifs-travel-erp' ); ?></span>
                                <strong class="val" id="modal_emergency">-</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Salary Breakdown Card -->
                    <div class="modal-card-block salary-section">
                        <h5 class="block-title"><span class="dashicons dashicons-money-alt"></span> <?php esc_html_e( 'Remuneration & Banking Matrix', 'ifs-travel-erp' ); ?></h5>
                        <div class="salary-grid font-mono">
                            <div class="sal-item">
                                <span class="sal-lbl"><?php esc_html_e( 'Basic Salary', 'ifs-travel-erp' ); ?></span>
                                <strong class="sal-val" id="modal_basic">৳0.00</strong>
                            </div>
                            <div class="sal-item">
                                <span class="sal-lbl"><?php esc_html_e( 'House Rent', 'ifs-travel-erp' ); ?></span>
                                <strong class="sal-val" id="modal_house">৳0.00</strong>
                            </div>
                            <div class="sal-item">
                                <span class="sal-lbl"><?php esc_html_e( 'Medical Allow.', 'ifs-travel-erp' ); ?></span>
                                <strong class="sal-val" id="modal_medical">৳0.00</strong>
                            </div>
                            <div class="sal-item">
                                <span class="sal-lbl"><?php esc_html_e( 'Conveyance', 'ifs-travel-erp' ); ?></span>
                                <strong class="sal-val" id="modal_transport">৳0.00</strong>
                            </div>
                        </div>

                        <div class="salary-gross-row">
                            <span class="gross-lbl"><?php esc_html_e( 'Total Monthly Gross Salary:', 'ifs-travel-erp' ); ?></span>
                            <strong class="gross-val font-mono color-emerald" id="modal_gross">৳0.00</strong>
                        </div>

                        <div class="banking-row font-mono">
                            <div><span class="lbl"><?php esc_html_e( 'Bank Account:', 'ifs-travel-erp' ); ?></span> <strong id="modal_bank">-</strong></div>
                            <div><span class="lbl"><?php esc_html_e( 'MFS Wallet:', 'ifs-travel-erp' ); ?></span> <strong id="modal_mfs">-</strong></div>
                        </div>
                    </div>
                </div>
                <div class="ifs-modal-footer">
                    <button type="button" class="ifs-btn-secondary" id="btnFooterCloseStaffModal"><?php esc_html_e( 'Close Dossier', 'ifs-travel-erp' ); ?></button>
                    <a href="#" id="modal_edit_link" class="ifs-btn-primary">
                        <span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Edit Employee', 'ifs-travel-erp' ); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stylesheet: Flat Minimal UI, Zero Shadows & Strict Controls -->
    <style>
        .ifs-staff-list-workspace {
            max-width: 1400px;
            margin: 20px auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #0f172a;
            box-sizing: border-box;
        }
        .ifs-staff-list-workspace *,
        .ifs-staff-list-workspace *::before,
        .ifs-staff-list-workspace *::after {
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

        /* Master Table Card */
        .ifs-table-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 24px;
        }

        /* Custom Table Controls */
        .ifs-custom-table-controls {
            padding: 16px 26px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
        }
        .ifs-per-page-wrap { display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #475569; font-weight: 600; }
        .ifs-select-control {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 0 26px 0 10px;
            height: 38px;
            font-size: 12.5px;
            color: #0f172a;
            background: #ffffff;
            outline: none;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 12px;
        }
        .ifs-select-control:focus { border-color: #003376; }

        .ifs-live-search-wrap { position: relative; display: flex; align-items: center; min-width: 280px; }
        .ifs-live-search-wrap label { position: absolute; left: 10px; color: #94a3b8; display: flex; align-items: center; pointer-events: none; }
        .ifs-live-search-wrap label .dashicons { font-size: 16px; width: 16px; height: 16px; }
        .ifs-search-input {
            width: 100%;
            height: 38px;
            padding: 0 12px 0 32px !important;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 12.5px;
            color: #0f172a;
            background: #ffffff;
            outline: none;
        }
        .ifs-search-input:focus { border-color: #003376; }

        /* Table Responsive */
        .ifs-table-responsive-wrapper { padding: 12px 22px 20px; overflow-x: auto; }
        .ifs-pro-datatable { width: 100%; border-collapse: collapse; font-size: 12.5px; }
        .ifs-pro-datatable thead th {
            background: #f8fafc;
            color: #475569;
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 14px;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
            white-space: nowrap;
        }
        .ifs-pro-datatable tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: #334155;
        }
        .ifs-pro-datatable tbody tr:hover td { background: #f8fafc; }

        .ifs-id-badge {
            background: #f1f5f9;
            color: #003376;
            font-weight: 700;
            font-size: 11px;
            padding: 3px 6px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
            display: inline-block;
        }

        .ifs-staff-cell { display: flex; align-items: center; gap: 12px; }
        .ifs-staff-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
            border: 1px solid #cbd5e1;
            background: #f1f5f9;
        }
        .avatar-img-fit { width: 100%; height: 100%; object-fit: cover; }
        .ifs-staff-name { font-weight: 700; color: #0f172a; font-size: 13px; display: block; }
        .ifs-staff-submeta { font-size: 11px; color: #64748b; display: flex; align-items: center; gap: 6px; margin-top: 1px; }
        .meta-dot { width: 3px; height: 3px; border-radius: 50%; background: #cbd5e1; }
        .self-tag { background: #dcfce7; color: #15803d; font-size: 9.5px; font-weight: 700; padding: 1px 5px; border-radius: 3px; }

        .sub-dept-label { font-size: 11px; color: #64748b; margin-top: 2px; }
        .contact-line { font-size: 11.5px; color: #334155; display: flex; align-items: center; gap: 4px; }
        .contact-line.email-color { color: #64748b; margin-top: 2px; }
        .contact-line .dashicons { font-size: 13px; width: 13px; height: 13px; color: #94a3b8; }

        /* Role Badges */
        .ifs-role-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .role-badge-admin      { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .role-badge-manager    { background: #eef2ff; color: #4338ca; border: 1px solid #e0e7ff; }
        .role-badge-ticketing  { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .role-badge-visa       { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .role-badge-accountant { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .role-badge-default    { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }

        .ifs-empty-table { text-align: center; padding: 50px 20px !important; }
        .ifs-empty-state .dashicons { font-size: 44px; width: 44px; height: 44px; color: #cbd5e1; margin-bottom: 10px; }
        .ifs-empty-state h4 { margin: 0 0 4px 0; font-size: 16px; font-weight: 700; color: #334155; }
        .ifs-empty-state p { margin: 0; color: #94a3b8; font-size: 13px; }

        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important; }
        .font-bold { font-weight: 700; }
        .color-emerald { color: #059669 !important; }
        .color-navy    { color: #003376 !important; }

        /* Buttons */
        .ifs-btn-secondary {
            background: #f8fafc;
            color: #475569 !important;
            border: 1px solid #cbd5e1;
            height: 38px;
            padding: 0 16px;
            border-radius: 6px;
            font-size: 12.5px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        .ifs-btn-secondary:hover { background: #f1f5f9; color: #0f172a; }

        .ifs-btn-primary {
            background: #003376;
            color: #ffffff !important;
            border: none;
            height: 38px;
            padding: 0 20px;
            border-radius: 6px;
            font-size: 12.5px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background-color 0.2s ease;
        }
        .ifs-btn-primary:hover { background: #0284c7; }

        /* Modal Architecture */
        .ifs-modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.6);
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .ifs-modal-content {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            width: 100%;
            max-width: 820px;
            max-height: 90vh;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        .ifs-modal-header {
            padding: 18px 24px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .ifs-modal-badge {
            background: #eff6ff;
            color: #003376;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 2px 7px;
            border-radius: 4px;
            border: 1px solid #bfdbfe;
            display: inline-block;
            margin-bottom: 4px;
        }
        .modal-title-left h3 { margin: 0; font-size: 16px; font-weight: 800; color: #0f172a; }
        .ifs-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            line-height: 1;
            color: #64748b;
            cursor: pointer;
            padding: 0;
        }
        .ifs-modal-close:hover { color: #0f172a; }

        .ifs-modal-body { padding: 22px 24px; display: flex; flex-direction: column; gap: 16px; }
        .modal-hero-strip {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }
        .modal-avatar-box {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
            color: #94a3b8;
        }
        .modal-avatar-box img { width: 100%; height: 100%; object-fit: cover; }
        .modal-avatar-box .dashicons { font-size: 28px; width: 28px; height: 28px; }

        .modal-id-row { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
        .badge-emp-id { background: #003376; color: #ffffff; font-size: 10.5px; font-weight: 700; padding: 2px 6px; border-radius: 4px; }
        .badge-type { background: #f1f5f9; color: #475569; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 4px; }
        .modal-hero-details h4 { margin: 0; font-size: 16px; font-weight: 800; color: #0f172a; }
        .modal-sub-details { font-size: 12px; color: #64748b; margin-top: 2px; }

        .modal-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 680px) { .modal-grid-2 { grid-template-columns: 1fr; } }
        .modal-card-block {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px;
        }
        .block-title {
            margin: 0 0 12px 0;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #003376;
            display: flex;
            align-items: center;
            gap: 6px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 8px;
        }
        .block-title .dashicons { font-size: 14px; width: 14px; height: 14px; color: #0284c7; }

        .modal-info-row { display: flex; justify-content: space-between; font-size: 12px; padding: 4px 0; border-bottom: 1px dashed #f1f5f9; }
        .modal-info-row.full-w { flex-direction: column; gap: 2px; border-bottom: none; padding-top: 6px; }
        .modal-info-row .lbl { color: #64748b; }
        .modal-info-row .val { color: #0f172a; text-align: right; }
        .modal-info-row.full-w .val { text-align: left; }

        .salary-section { background: #f8fafc; }
        .salary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 12px; }
        @media (max-width: 600px) { .salary-grid { grid-template-columns: repeat(2, 1fr); } }
        .sal-item { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 10px; }
        .sal-lbl { font-size: 10px; color: #64748b; display: block; margin-bottom: 2px; }
        .sal-val { font-size: 13px; font-weight: 700; color: #0f172a; }

        .salary-gross-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 10px;
        }
        .gross-lbl { font-size: 12.5px; font-weight: 700; color: #166534; }
        .gross-val { font-size: 16px; font-weight: 800; }

        .banking-row { display: flex; justify-content: space-between; font-size: 11.5px; color: #475569; padding-top: 4px; }
        .banking-row .lbl { color: #64748b; }

        .ifs-modal-footer {
            padding: 14px 24px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* DataTables Controls Layout */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter { display: none !important; }
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate { margin-top: 14px; font-size: 12px; color: #64748b; }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 5px !important;
            color: #334155 !important;
            padding: 4px 10px !important;
            margin-left: 3px;
            font-weight: 600;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #003376 !important;
            color: #ffffff !important;
            border: 1px solid #003376 !important;
        }
    </style>

    <!-- Interactive Script: DataTables & Modal Controller -->
    <script>
    jQuery(document).ready(function($) {
        // DataTables Engine
        if ($.fn.DataTable && $('#ifsStaffTable tbody tr td').length > 1) {
            var table = $('#ifsStaffTable').DataTable({
                "pageLength": 15,
                "ordering": true,
                "info": true,
                "searching": true,
                "lengthChange": false,
                "order": [[ 0, "desc" ]],
                "language": {
                    "info": "Showing _START_ to _END_ of _TOTAL_ personnel records",
                    "infoEmpty": "Showing 0 to 0 of 0 records",
                    "infoFiltered": "(filtered from _MAX_ total records)",
                    "paginate": { "previous": "&larr;", "next": "&rarr;" }
                }
            });

            $('#ifsStaffPerPageSelect').on('change', function() {
                table.page.len(parseInt($(this).val())).draw();
            });

            $('#ifsStaffSearchInput').on('keyup', function() {
                table.search(this.value).draw();
            });
        }

        // View Dossier Modal Engine
        const modal = $('#ifsStaffModal');
        $('.btn-view-staff').on('click', function(e) {
            e.preventDefault();
            const data = $(this).data('payload');
            if (!data) return;

            // Header & Identification
            $('#modal_staff_name').text(data.name);
            $('#modal_display_name').text(data.name);
            $('#modal_emp_id').text(data.emp_id);
            $('#modal_role_badge').text(data.role);
            $('#modal_emp_type').text(data.emp_type);
            $('#modal_desig_dept').text(data.designation + ' • ' + data.department);
            $('#modal_username').text('@' + data.username);

            // Avatar Preview
            if (data.photo) {
                $('#modal_avatar_box').html('<img src="' + data.photo + '" alt="Avatar" />');
            } else {
                $('#modal_avatar_box').html('<span class="dashicons dashicons-admin-users"></span>');
            }

            // Contact & Details
            $('#modal_email').text(data.email);
            $('#modal_phone').text(data.phone);
            $('#modal_alt_phone').text(data.alt_phone);
            $('#modal_nid').text(data.nid);
            $('#modal_address').text(data.address);
            $('#modal_designation').text(data.designation);
            $('#modal_department').text(data.department);
            $('#modal_joining').text(data.joining);
            $('#modal_emergency').text(data.em_name + ' (' + data.em_phone + ')');

            // Salary & Banking
            $('#modal_basic').text('৳' + data.basic);
            $('#modal_house').text('৳' + data.house);
            $('#modal_medical').text('৳' + data.medical);
            $('#modal_transport').text('৳' + data.transport);
            $('#modal_gross').text('৳' + data.gross);
            $('#modal_bank').text(data.bank + ' (' + data.bank_acc + ')');
            $('#modal_mfs').text(data.mfs);

            // Set Direct Edit Link
            $('#modal_edit_link').attr('href', '<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=staff&sub=edit&id=' ) ); ?>' + data.id);

            modal.show();
        });

        $('#btnCloseStaffModal, #btnFooterCloseStaffModal').on('click', function() {
            modal.hide();
        });

        $(window).on('click', function(e) {
            if ($(e.target).is(modal)) {
                modal.hide();
            }
        });
    });
    </script>
    <?php
}