<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_hajj_pilgrim_list_page() {
    global $wpdb;
    $table_bookings  = $wpdb->prefix . 'iterp_hajj_bookings';
    $table_customers = $wpdb->prefix . 'iterp_customers';
    $table_packages  = $wpdb->prefix . 'iterp_hajj_packages';

    if ( isset( $_GET['msg'] ) && $_GET['msg'] == 'deleted' ) {
        echo '<div class="notice notice-success is-dismissible"><p>Pilgrim booking record deleted successfully.</p></div>';
    }

    $query = "
        SELECT b.*, c.full_name AS pilgrim_name, c.mobile, c.passport_no, p.package_name, p.package_type
        FROM $table_bookings b
        LEFT JOIN $table_customers c ON b.customer_id = c.id
        LEFT JOIN $table_packages p ON b.package_id = p.id
        ORDER BY b.id DESC
    ";
    $bookings = $wpdb->get_results( $query );
    ?>
    <div class="arms-card-panel" style="margin-top: 20px;">
        <div class="arms-p-header" style="padding: 15px 20px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 18px; margin: 0;">Registered Pilgrims (Haji & Mutamir List)</h2>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=hajj_umrah&sub=add' ) ); ?>" style="background:#003376; color:#fff; padding:8px 14px; border-radius:4px; text-decoration:none; font-weight:600;">
                <span class="dashicons dashicons-plus" style="vertical-align:middle;"></span> New Booking
            </a>
        </div>

        <div class="arms-table-responsive">
            <table class="arms-pricing-table" id="ifsHajjTable" style="width:100%;">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Pilgrim Name</th>
                        <th>Package</th>
                        <th>Room Sharing</th>
                        <th>Saudi BRN / Mofaza</th>
                        <th>Visa Status</th>
                        <th style="text-align: right;">Package Price (৳)</th>
                        <th style="text-align: center;">Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $bookings ) : foreach ( $bookings as $row ) : 
                        $status_bg = '#dcfce7'; $status_color = '#166534';
                        if ( strtolower($row->status) === 'pending' ) { $status_bg = '#fef3c7'; $status_color = '#b45309'; }
                        elseif ( strtolower($row->status) === 'cancelled' ) { $status_bg = '#fee2e2'; $status_color = '#991b1b'; }
                    ?>
                        <tr>
                            <td><strong>#HB-<?php echo esc_html( $row->id ); ?></strong></td>
                            <td style="font-weight: 600; color: #0f172a;">
                                <?php echo esc_html( $row->pilgrim_name ?: 'Unknown' ); ?>
                                <div style="font-size: 11px; color: #64748b; font-weight: normal;">Pass: <?php echo esc_html( $row->passport_no ?: '-' ); ?> | Mob: <?php echo esc_html( $row->mobile ?: '-' ); ?></div>
                            </td>
                            <td>
                                <strong><?php echo esc_html( $row->package_name ?: 'Custom Package' ); ?></strong>
                                <div style="font-size: 11px; color: #0369a1;"><?php echo esc_html( $row->package_type ?: 'Umrah' ); ?></div>
                            </td>
                            <td><span style="background: #f1f5f9; padding: 2px 8px; border-radius: 4px; font-size: 12px;"><?php echo esc_html( $row->room_sharing ); ?></span></td>
                            <td style="font-family: monospace; font-size: 12px;">
                                <div>BRN: <?php echo esc_html( $row->brn_no ?: '-' ); ?></div>
                                <div style="color: #64748b;">Mofaza: <?php echo esc_html( $row->mofaza_no ?: '-' ); ?></div>
                            </td>
                            <td>
                                <span style="background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">
                                    <?php echo esc_html( $row->visa_status ); ?>
                                </span>
                            </td>
                            <td style="text-align: right; font-weight: 700; color: #0f172a;">৳<?php echo number_format( $row->sell_price, 2 ); ?></td>
                            <td style="text-align: center;">
                                <span style="background: <?php echo $status_bg; ?>; color: <?php echo $status_color; ?>; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                    <?php echo esc_html( $row->status ); ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 5px; justify-content: flex-end;">
                                    <a href="<?php echo admin_url( 'admin.php?page=ifs_travel_erp&tab=hajj_umrah&sub=view&id=' . $row->id ); ?>" style="background:#f1f5f9; padding:4px 8px; border-radius:4px; text-decoration:none; color:#334155;" title="View">
                                        <span class="dashicons dashicons-visibility" style="font-size:14px; vertical-align:middle;"></span>
                                    </a>
                                    <a href="<?php echo admin_url( 'admin.php?page=ifs_travel_erp&tab=hajj_umrah&sub=edit&id=' . $row->id ); ?>" style="background:#f1f5f9; padding:4px 8px; border-radius:4px; text-decoration:none; color:#2563eb;" title="Edit">
                                        <span class="dashicons dashicons-edit" style="font-size:14px; vertical-align:middle;"></span>
                                    </a>
                                    <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=hajj_umrah&sub=delete&id=' . $row->id ), 'delete_hajj_' . $row->id ); ?>" style="background:#fef2f2; padding:4px 8px; border-radius:4px; text-decoration:none; color:#dc2626;" onclick="return confirm('Are you sure you want to delete this booking record?');" title="Delete">
                                        <span class="dashicons dashicons-trash" style="font-size:14px; vertical-align:middle;"></span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="9" style="text-align: center; padding: 20px;">No pilgrim bookings found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        jQuery(document).ready(function($) {
            $('#ifsHajjTable').DataTable({
                "pageLength": 15,
                "ordering": true,
                "order": [[ 0, "desc" ]],
                "info": true,
                "searching": true
            });
        });
    </script>
    <?php
}