<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enterprise Next-Gen Staff & Employee Directory Console
 * Features: Live Search, Custom Pagination, Role Badges, Metric Ribbons & Secure Nonce Deletion
 */
function ifs_terp_staff_list_page() {
    $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=staff' );

    // Handle Delete Action with Nonce Verification
    if ( isset( $_GET['sub'] ) && $_GET['sub'] === 'delete' && isset( $_GET['id'] ) ) {
        $del_id = intval( $_GET['id'] );
        check_admin_referer( 'delete_staff_' . $del_id );

        if ( $del_id === get_current_user_id() ) {
            wp_die( 'Security restriction: You cannot delete your own active account.' );
        }

        require_once ABSPATH . 'wp-admin/includes/user.php';
        $user_obj = get_userdata( $del_id );
        $user_login = $user_obj ? $user_obj->user_login : 'User #' . $del_id;

        wp_delete_user( $del_id );

        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            ifs_terp_log_activity( "Deleted Staff Member Account: " . $user_login . " (ID: #UID-" . $del_id . ")" );
        }

        wp_safe_redirect( add_query_arg( array( 'page' => 'ifs_travel_erp', 'tab' => 'staff', 'msg' => 'deleted' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    $message = '';
    if ( isset( $_GET['msg'] ) && $_GET['msg'] === 'deleted' ) {
        $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Staff member record permanently removed.</div>';
    }

    $users = get_users( array( 'orderby' => 'ID', 'order' => 'DESC' ) );

    // Metric Summary Aggregations
    $total_users       = count( $users );
    $total_admins      = 0;
    $total_ticketing   = 0;
    $total_visa        = 0;
    $total_accountants = 0;

    foreach ( $users as $u ) {
        $roles = (array) $u->roles;
        if ( in_array( 'administrator', $roles, true ) || in_array( 'admin_manager', $roles, true ) ) {
            $total_admins++;
        }
        if ( in_array( 'ticketing_staff', $roles, true ) ) {
            $total_ticketing++;
        }
        if ( in_array( 'visa_officer', $roles, true ) ) {
            $total_visa++;
        }
        if ( in_array( 'accountant', $roles, true ) ) {
            $total_accountants++;
        }
    }
    ?>

    <div class="wrap ifs-staff-list-workspace">
        <?php echo $message; ?>

        <!-- Metric Counter Ribbon -->
        <div class="ifs-list-metric-ribbon">
            <div class="ifs-metric-chip">
                <div class="chip-icon bg-blue"><span class="dashicons dashicons-businesswoman"></span></div>
                <div>
                    <span class="chip-label">Total Staff</span>
                    <strong class="chip-val"><?php echo number_format( $total_users ); ?></strong>
                </div>
            </div>
            <div class="ifs-metric-chip">
                <div class="chip-icon bg-indigo"><span class="dashicons dashicons-shield"></span></div>
                <div>
                    <span class="chip-label">Managers &amp; Admins</span>
                    <strong class="chip-val color-indigo"><?php echo number_format( $total_admins ); ?></strong>
                </div>
            </div>
            <div class="ifs-metric-chip">
                <div class="chip-icon bg-sky"><span class="dashicons dashicons-airplane"></span></div>
                <div>
                    <span class="chip-label">Ticketing Officers</span>
                    <strong class="chip-val color-sky"><?php echo number_format( $total_ticketing ); ?></strong>
                </div>
            </div>
            <div class="ifs-metric-chip">
                <div class="chip-icon bg-amber"><span class="dashicons dashicons-id-alt"></span></div>
                <div>
                    <span class="chip-label">Visa &amp; Accounts</span>
                    <strong class="chip-val color-amber"><?php echo number_format( $total_visa + $total_accountants ); ?></strong>
                </div>
            </div>
        </div>

        <!-- Master Data Table Card -->
        <div class="ifs-table-card">
            <div class="ifs-table-top-bar">
                <div class="ifs-table-title-group">
                    <h3 class="ifs-table-heading"><span class="dashicons dashicons-groups"></span> Employee Directory &amp; Roles</h3>
                    <p class="ifs-table-caption">Manage agency personnel, access credentials, contact records, and ERP operational privileges.</p>
                </div>
                <div class="ifs-table-btn-group">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=staff&sub=add' ) ); ?>" class="ifs-btn-primary">
                        <span class="dashicons dashicons-plus-alt2"></span> Onboard New Employee
                    </a>
                </div>
            </div>

            <!-- Custom DataTables Controls Toolbar -->
            <div class="ifs-custom-table-controls">
                <div class="ifs-per-page-wrap">
                    <label for="ifsStaffPerPageSelect">Show</label>
                    <select id="ifsStaffPerPageSelect" class="ifs-select-control">
                        <option value="10">10</option>
                        <option value="15" selected>15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span>entries</span>
                </div>
                <div class="ifs-live-search-wrap">
                    <label for="ifsStaffSearchInput"><span class="dashicons dashicons-search"></span></label>
                    <input type="text" id="ifsStaffSearchInput" class="ifs-search-input" placeholder="Search by name, username, phone, email...">
                </div>
            </div>

            <div class="ifs-table-responsive-wrapper">
                <table class="ifs-pro-datatable" id="ifsStaffTable">
                    <thead>
                        <tr>
                            <th style="width: 80px;">UID</th>
                            <th>Staff Member Profile</th>
                            <th>Email Address</th>
                            <th>Contact Phone</th>
                            <th>Assigned ERP Role</th>
                            <th style="text-align: right; width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $users ) : foreach ( $users as $u ) : 
                            $phone       = get_user_meta( $u->ID, 'phone_number', true ) ?: '-';
                            $first_role  = ! empty( $u->roles ) ? reset( $u->roles ) : 'none';
                            $role_title  = ucwords( str_replace( '_', ' ', $first_role ) );
                            $is_self     = ( $u->ID === get_current_user_id() );

                            // Role Badge Styling
                            $badge_class = 'role-badge-default';
                            if ( $first_role === 'administrator' ) $badge_class = 'role-badge-admin';
                            elseif ( $first_role === 'admin_manager' ) $badge_class = 'role-badge-manager';
                            elseif ( $first_role === 'ticketing_staff' ) $badge_class = 'role-badge-ticketing';
                            elseif ( $first_role === 'visa_officer' ) $badge_class = 'role-badge-visa';
                            elseif ( $first_role === 'accountant' ) $badge_class = 'role-badge-accountant';
                        ?>
                            <tr>
                                <td>
                                    <span class="ifs-id-badge">#UID-<?php echo esc_html( $u->ID ); ?></span>
                                </td>
                                <td>
                                    <div class="ifs-staff-cell">
                                        <div class="ifs-staff-avatar">
                                            <?php echo get_avatar( $u->ID, 38, '', '', array( 'class' => 'avatar-img-fit' ) ); ?>
                                        </div>
                                        <div>
                                            <strong class="ifs-staff-name"><?php echo esc_html( $u->display_name ); ?></strong>
                                            <div class="ifs-staff-submeta">
                                                <span>@<?php echo esc_html( $u->user_login ); ?></span>
                                                <?php if ( $is_self ) : ?>
                                                    <span class="meta-dot"></span>
                                                    <span class="self-tag">You</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="mailto:<?php echo esc_attr( $u->user_email ); ?>" class="ifs-email-link">
                                        <span class="dashicons dashicons-email"></span> <?php echo esc_html( $u->user_email ); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if ( $phone !== '-' ) : ?>
                                        <a href="tel:<?php echo esc_attr( $phone ); ?>" class="ifs-phone-link">
                                            <span class="dashicons dashicons-phone"></span> <?php echo esc_html( $phone ); ?>
                                        </a>
                                    <?php else : ?>
                                        <span class="color-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="ifs-role-badge <?php echo esc_attr( $badge_class ); ?>">
                                        <?php echo esc_html( $role_title ); ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <div class="ifs-action-pills">
                                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=staff&sub=edit&id=' . $u->ID ) ); ?>" class="ifs-action-pill edit" title="Edit Profile">
                                            <span class="dashicons dashicons-edit"></span> Edit
                                        </a>
                                        <?php if ( ! $is_self ) : ?>
                                            <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=staff&sub=delete&id=' . $u->ID ), 'delete_staff_' . $u->ID ); ?>" 
                                               class="ifs-action-pill delete" 
                                               onclick="return confirm('Are you sure you want to permanently delete this staff user account?');" 
                                               title="Delete User">
                                                <span class="dashicons dashicons-trash"></span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr>
                                <td colspan="6" class="ifs-empty-table">
                                    <div class="ifs-empty-state">
                                        <span class="dashicons dashicons-businesswoman"></span>
                                        <h4>No Staff Members Found</h4>
                                        <p>Get started by onboarding your first employee into the system.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modern Stylesheet -->
    <style>
        .ifs-staff-list-workspace {
            max-width: 1400px;
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

        /* Metric Counter Ribbon */
        .ifs-list-metric-ribbon {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }
        .ifs-metric-chip {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 8px -2px rgba(15, 23, 42, 0.03);
        }
        .chip-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            flex-shrink: 0;
        }
        .chip-icon.bg-blue   { background: linear-gradient(135deg, #003376 0%, #0284c7 100%); }
        .chip-icon.bg-indigo { background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); }
        .chip-icon.bg-sky    { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); }
        .chip-icon.bg-amber  { background: linear-gradient(135deg, #d97706 0%, #b45309 100%); }
        .chip-icon .dashicons { font-size: 22px; width: 22px; height: 22px; }

        .chip-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; display: block; margin-bottom: 2px; }
        .chip-val { font-size: 20px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
        .color-indigo { color: #4f46e5 !important; }
        .color-sky    { color: #0284c7 !important; }
        .color-amber  { color: #d97706 !important; }
        .color-muted  { color: #94a3b8; }

        /* Master Table Card */
        .ifs-table-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
            overflow: hidden;
        }
        .ifs-table-top-bar {
            padding: 22px 26px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f1f5f9;
            flex-wrap: wrap;
            gap: 15px;
        }
        .ifs-table-heading { margin: 0; font-size: 17px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        .ifs-table-heading .dashicons { color: #003376; font-size: 20px; width: 20px; height: 20px; }
        .ifs-table-caption { margin: 3px 0 0 0; font-size: 13px; color: #64748b; }

        .ifs-btn-primary {
            background: linear-gradient(135deg, #003376 0%, #0284c7 100%);
            color: #ffffff !important;
            border: none;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 12px rgba(0, 51, 118, 0.2);
            transition: all 0.2s ease;
        }
        .ifs-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(0, 51, 118, 0.3);
        }

        /* Custom Table Controls */
        .ifs-custom-table-controls {
            padding: 16px 26px;
            background: #f8fafc;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .ifs-per-page-wrap { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #475569; font-weight: 600; }
        .ifs-select-control {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 5px 28px 5px 10px;
            font-size: 13px;
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
        .ifs-select-control:focus { border-color: #003376; box-shadow: 0 0 0 3px rgba(0, 51, 118, 0.12); }

        .ifs-live-search-wrap { position: relative; display: flex; align-items: center; min-width: 280px; }
        .ifs-live-search-wrap label { position: absolute; left: 12px; color: #94a3b8; display: flex; align-items: center; pointer-events: none; }
        .ifs-live-search-wrap label .dashicons { font-size: 16px; width: 16px; height: 16px; }
        .ifs-search-input {
            width: 100%;
            padding: 7px 12px 7px 36px !important;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13px;
            color: #0f172a;
            background: #ffffff;
            outline: none;
            transition: all 0.2s ease;
        }
        .ifs-search-input:focus { border-color: #003376; box-shadow: 0 0 0 3px rgba(0, 51, 118, 0.12); }

        /* Table Architecture */
        .ifs-table-responsive-wrapper { padding: 15px 24px 24px 24px; overflow-x: auto; }
        .ifs-pro-datatable { width: 100%; border-collapse: collapse; font-size: 13px; }
        .ifs-pro-datatable thead th {
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 16px;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
            white-space: nowrap;
        }
        .ifs-pro-datatable tbody td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: #334155;
        }
        .ifs-pro-datatable tbody tr:hover td { background: #f8fafc; }

        .ifs-id-badge {
            background: #f1f5f9;
            color: #475569;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-weight: 700;
            font-size: 11.5px;
            padding: 4px 8px;
            border-radius: 6px;
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
            border: 2px solid #e2e8f0;
        }
        .avatar-img-fit { width: 100%; height: 100%; object-fit: cover; }
        .ifs-staff-name { font-weight: 700; color: #0f172a; font-size: 13.5px; display: block; }
        .ifs-staff-submeta { font-size: 11.5px; color: #64748b; display: flex; align-items: center; gap: 6px; margin-top: 1px; }
        .meta-dot { width: 3px; height: 3px; border-radius: 50%; background: #cbd5e1; }
        .self-tag { background: #dcfce7; color: #15803d; font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 4px; }

        .ifs-email-link, .ifs-phone-link {
            color: #334155;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-weight: 500;
            transition: color 0.15s ease;
        }
        .ifs-email-link:hover, .ifs-phone-link:hover { color: #003376; }
        .ifs-email-link .dashicons, .ifs-phone-link .dashicons { font-size: 14px; width: 14px; height: 14px; color: #94a3b8; }

        /* Role Badges */
        .ifs-role-badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        .role-badge-admin       { background: #fee2e2; color: #991b1b; }
        .role-badge-manager     { background: #eef2ff; color: #4338ca; }
        .role-badge-ticketing   { background: #e0f2fe; color: #0369a1; }
        .role-badge-visa        { background: #fef3c7; color: #b45309; }
        .role-badge-accountant  { background: #dcfce7; color: #15803d; }
        .role-badge-default     { background: #f1f5f9; color: #64748b; }

        /* Action Buttons */
        .ifs-action-pills { display: flex; gap: 5px; justify-content: flex-end; align-items: center; }
        .ifs-action-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease;
            white-space: nowrap;
        }
        .ifs-action-pill.edit { background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; }
        .ifs-action-pill.edit:hover { background: #dbeafe; color: #1d4ed8; }
        .ifs-action-pill.delete { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; padding: 5px 8px; }
        .ifs-action-pill.delete:hover { background: #fee2e2; color: #b91c1c; }
        .ifs-action-pill .dashicons { font-size: 13px; width: 13px; height: 13px; margin-top: 1px; }

        .ifs-empty-table { text-align: center; padding: 50px 20px !important; }
        .ifs-empty-state .dashicons { font-size: 44px; width: 44px; height: 44px; color: #cbd5e1; margin-bottom: 10px; }
        .ifs-empty-state h4 { margin: 0 0 4px 0; font-size: 16px; font-weight: 700; color: #334155; }
        .ifs-empty-state p { margin: 0; color: #94a3b8; font-size: 13px; }

        /* DataTables Custom Controls Integration */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter { display: none !important; }
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate { margin-top: 18px; font-size: 13px; color: #64748b; }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 6px !important;
            color: #334155 !important;
            padding: 6px 12px !important;
            margin-left: 4px;
            font-weight: 600;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #003376 !important;
            color: #ffffff !important;
            border: 1px solid #003376 !important;
            font-weight: 700;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #e2e8f0 !important;
            color: #0f172a !important;
        }
    </style>

    <script>
    jQuery(document).ready(function($) {
        if ($.fn.DataTable) {
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
                    "paginate": {
                        "previous": "&larr; Prev",
                        "next": "Next &rarr;"
                    }
                }
            });

            // Bind Custom Per Page Selector
            $('#ifsStaffPerPageSelect').on('change', function() {
                var lengthVal = parseInt($(this).val());
                table.page.len(lengthVal).draw();
            });

            // Bind Custom Live Search Box
            $('#ifsStaffSearchInput').on('keyup', function() {
                table.search(this.value).draw();
            });
        }
    });
    </script>
    <?php
}