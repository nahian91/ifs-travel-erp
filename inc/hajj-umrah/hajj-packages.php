<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_hajj_packages_page() {
    global $wpdb;
    $table_packages = $wpdb->prefix . 'iterp_hajj_packages';

    $action_sub = isset( $_GET['action_sub'] ) ? sanitize_text_field( $_GET['action_sub'] ) : '';
    $pkg_id     = isset( $_GET['pkg_id'] ) ? intval( $_GET['pkg_id'] ) : 0;
    $message    = '';

    // Delete Package
    if ( $action_sub === 'delete' && $pkg_id > 0 ) {
        if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'delete_pkg_' . $pkg_id ) ) {
            $wpdb->delete( $table_packages, array( 'id' => $pkg_id ), array( '%d' ) );
            $message = '<div class="notice notice-success is-dismissible"><p>Package removed successfully.</p></div>';
        }
    }

    // Save Package
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_hajj_pkg_submit'] ) ) {
        check_admin_referer( 'ifs_hajj_pkg_nonce_action', 'ifs_hajj_pkg_nonce' );

        $is_edit_mode = ( isset( $_POST['edit_pkg_id'] ) && intval( $_POST['edit_pkg_id'] ) > 0 );
        $edit_id      = $is_edit_mode ? intval( $_POST['edit_pkg_id'] ) : 0;

        $data_array = array(
            'package_name'    => sanitize_text_field( $_POST['package_name'] ),
            'package_type'    => sanitize_text_field( $_POST['package_type'] ),
            'cost_bdt'        => floatval( $_POST['cost_bdt'] ),
            'cost_sar'        => floatval( $_POST['cost_sar'] ),
            'capacity'        => intval( $_POST['capacity'] ),
            'hotel_makkah'    => sanitize_text_field( $_POST['hotel_makkah'] ),
            'hotel_madinah'   => sanitize_text_field( $_POST['hotel_madinah'] ),
            'inclusions_json' => sanitize_textarea_field( $_POST['inclusions'] ),
        );

        if ( $is_edit_mode ) {
            $wpdb->update( $table_packages, $data_array, array( 'id' => $edit_id ) );
            $message = '<div class="notice notice-success is-dismissible"><p>Package updated successfully.</p></div>';
        } else {
            $data_array['created_at'] = current_time( 'mysql' );
            $wpdb->insert( $table_packages, $data_array );
            $message = '<div class="notice notice-success is-dismissible"><p>New package created successfully.</p></div>';
        }
    }

    $edit_data = false;
    if ( $action_sub === 'edit' && $pkg_id > 0 ) {
        $edit_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_packages WHERE id = %d", $pkg_id ) );
    }

    $all_packages = $wpdb->get_results( "SELECT * FROM $table_packages ORDER BY id DESC" );
    ?>

    <?php echo $message; ?>

    <div class="arms-card-panel" style="margin-top: 20px;">
        <div class="arms-p-header" style="padding: 15px 20px; margin-bottom: 15px;">
            <h2 style="font-size: 18px; margin: 0;">Hajj & Umrah Fixed Packages</h2>
        </div>

        <!-- Add/Edit Form -->
        <div style="background: #fff; padding: 25px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 30px;">
            <h3 style="margin-top: 0; color: #003376; font-size: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                <?php echo $edit_data ? 'Edit Package: ' . esc_html( $edit_data->package_name ) : 'Configure New Package'; ?>
            </h3>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=hajj_umrah&sub=packages' ) ); ?>">
                <?php wp_nonce_field( 'ifs_hajj_pkg_nonce_action', 'ifs_hajj_pkg_nonce' ); ?>
                <?php if ( $edit_data ) : ?>
                    <input type="hidden" name="edit_pkg_id" value="<?php echo esc_attr( $edit_data->id ); ?>">
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">
                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Package Name *</label>
                        <input type="text" name="package_name" required value="<?php echo $edit_data ? esc_attr( $edit_data->package_name ) : ''; ?>" placeholder="e.g. 15 Days Economy Umrah Package" style="width:100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
                    </div>

                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Package Type</label>
                        <select name="package_type" style="width:100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
                            <option value="Umrah" <?php selected($edit_data && $edit_data->package_type == 'Umrah'); ?>>Umrah</option>
                            <option value="Hajj" <?php selected($edit_data && $edit_data->package_type == 'Hajj'); ?>>Hajj</option>
                            <option value="Ramadan Umrah" <?php selected($edit_data && $edit_data->package_type == 'Ramadan Umrah'); ?>>Ramadan Umrah</option>
                        </select>
                    </div>

                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Price in BDT (৳) *</label>
                        <input type="number" step="0.01" name="cost_bdt" required value="<?php echo $edit_data ? esc_attr( $edit_data->cost_bdt ) : ''; ?>" placeholder="0.00" style="width:100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
                    </div>

                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Saudi SAR Value (﷼)</label>
                        <input type="number" step="0.01" name="cost_sar" value="<?php echo $edit_data ? esc_attr( $edit_data->cost_sar ) : ''; ?>" placeholder="0.00" style="width:100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
                    </div>

                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Hotel in Makkah</label>
                        <input type="text" name="hotel_makkah" value="<?php echo $edit_data ? esc_attr( $edit_data->hotel_makkah ) : ''; ?>" placeholder="e.g. Swissotel Makkah (500m)" style="width:100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
                    </div>

                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Hotel in Madinah</label>
                        <input type="text" name="hotel_madinah" value="<?php echo $edit_data ? esc_attr( $edit_data->hotel_madinah ) : ''; ?>" placeholder="e.g. Anwar Al Madinah (300m)" style="width:100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
                    </div>

                    <div style="grid-column: 1 / -1;">
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Inclusions / Features</label>
                        <textarea name="inclusions" rows="3" placeholder="Visa, Air Ticket, Hotel accommodation, Transport, Ziyarah..." style="width:100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;"><?php echo $edit_data ? esc_textarea( $edit_data->inclusions_json ) : ''; ?></textarea>
                    </div>
                </div>

                <div style="margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 15px; display: flex; justify-content: space-between; align-items: center;">
                    <?php if ( $edit_data ) : ?>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=hajj_umrah&sub=packages' ) ); ?>" style="color: #64748b; text-decoration: none;">Cancel Edit</a>
                    <?php else : ?>
                        <span></span>
                    <?php endif; ?>
                    <button type="submit" name="ifs_hajj_pkg_submit" style="background: #003376; color: #fff; border: none; padding: 10px 22px; border-radius: 4px; cursor: pointer; font-weight: 600;">
                        <?php echo $edit_data ? 'Update Package' : 'Save Package'; ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Packages Table -->
        <div class="arms-table-responsive">
            <table class="arms-pricing-table" id="ifsHajjPackagesTable" style="width:100%;">
                <thead>
                    <tr>
                        <th>Package Name</th>
                        <th>Type</th>
                        <th>Price (BDT)</th>
                        <th>Makkah Hotel</th>
                        <th>Madinah Hotel</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $all_packages ) : foreach ( $all_packages as $pkg ) : ?>
                        <tr>
                            <td style="font-weight: 700; color: #0f172a;"><?php echo esc_html( $pkg->package_name ); ?></td>
                            <td><span style="background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;"><?php echo esc_html( $pkg->package_type ); ?></span></td>
                            <td style="font-weight: 700; color: #166534;">৳<?php echo number_format( $pkg->cost_bdt, 2 ); ?></td>
                            <td><?php echo esc_html( $pkg->hotel_makkah ?: '-' ); ?></td>
                            <td><?php echo esc_html( $pkg->hotel_madinah ?: '-' ); ?></td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 5px; justify-content: flex-end;">
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=hajj_umrah&sub=packages&action_sub=edit&pkg_id=' . $pkg->id ) ); ?>" style="background:#f1f5f9; padding:4px 8px; border-radius:4px; text-decoration:none; color:#2563eb;" title="Edit">
                                        <span class="dashicons dashicons-edit" style="font-size:14px; vertical-align:middle;"></span>
                                    </a>
                                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=hajj_umrah&sub=packages&action_sub=delete&pkg_id=' . $pkg->id ), 'delete_pkg_' . $pkg->id ) ); ?>" style="background:#fef2f2; padding:4px 8px; border-radius:4px; text-decoration:none; color:#dc2626;" onclick="return confirm('Are you sure you want to delete this package?');" title="Delete">
                                        <span class="dashicons dashicons-trash" style="font-size:14px; vertical-align:middle;"></span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="6" style="text-align: center; padding: 20px;">No packages configured yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        jQuery(document).ready(function($) {
            $('#ifsHajjPackagesTable').DataTable({
                "pageLength": 10,
                "ordering": true,
                "info": true,
                "searching": true
            });
        });
    </script>
    <?php
}