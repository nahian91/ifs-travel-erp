<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_staff_list_page() {
    if ( isset( $_GET['msg'] ) && $_GET['msg'] === 'deleted' ) {
        echo '<div class="notice notice-success is-dismissible"><p>Staff user removed successfully.</p></div>';
    }

    $users = get_users( array( 'orderby' => 'ID', 'order' => 'DESC' ) );
    ?>
    <div class="arms-card-panel" style="margin-top: 20px;">
        <div class="arms-p-header" style="padding: 15px 20px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 18px; margin: 0;">Employee Directory & Roles</h2>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=staff&sub=add' ) ); ?>" style="background:#003376; color:#fff; padding:8px 14px; border-radius:4px; text-decoration:none; font-weight:600;">
                <span class="dashicons dashicons-plus" style="vertical-align:middle;"></span> Add Employee
            </a>
        </div>

        <div class="arms-table-responsive">
            <table class="arms-pricing-table" id="ifsStaffTable" style="width:100%;">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone Number</th>
                        <th>Assigned ERP Role</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $users ) : foreach ( $users as $u ) : 
                        $phone = get_user_meta( $u->ID, 'phone_number', true ) ?: '-';
                        $roles = ! empty( $u->roles ) ? implode( ', ', array_map( function( $r ){ return ucwords( str_replace( '_', ' ', $r ) ); }, $u->roles ) ) : 'No Role';
                    ?>
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <?php echo get_avatar( $u->ID, 36, '', '', array( 'style' => 'border-radius:50%;' ) ); ?>
                                    <strong><?php echo esc_html( $u->user_login ); ?></strong>
                                </div>
                            </td>
                            <td style="font-weight: 600; color: #0f172a;"><?php echo esc_html( $u->display_name ); ?></td>
                            <td><?php echo esc_html( $u->user_email ); ?></td>
                            <td><?php echo esc_html( $phone ); ?></td>
                            <td>
                                <span style="background: #e0f2fe; color: #0369a1; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;">
                                    <?php echo esc_html( $roles ); ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 5px; justify-content: flex-end;">
                                    <a href="<?php echo admin_url( 'admin.php?page=ifs_travel_erp&tab=staff&sub=edit&id=' . $u->ID ); ?>" style="background:#f1f5f9; padding:4px 8px; border-radius:4px; text-decoration:none; color:#2563eb;" title="Edit Staff">
                                        <span class="dashicons dashicons-edit" style="font-size:14px; vertical-align:middle;"></span>
                                    </a>
                                    <?php if ( $u->ID !== get_current_user_id() ) : ?>
                                    <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=staff&sub=delete&id=' . $u->ID ), 'delete_staff_' . $u->ID ); ?>" style="background:#fef2f2; padding:4px 8px; border-radius:4px; text-decoration:none; color:#dc2626;" onclick="return confirm('Are you sure you want to delete this staff member?');" title="Delete">
                                        <span class="dashicons dashicons-trash" style="font-size:14px; vertical-align:middle;"></span>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        jQuery(document).ready(function($) {
            $('#ifsStaffTable').DataTable({
                "pageLength": 15,
                "ordering": true,
                "info": true,
                "searching": true
            });
        });
    </script>
    <?php
}