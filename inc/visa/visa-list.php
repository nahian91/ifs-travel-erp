<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_visa_list_page() {
    global $wpdb;
    $table_visas     = $wpdb->prefix . 'iterp_visas';
    $table_customers = $wpdb->prefix . 'iterp_customers';

    if ( isset( $_GET['msg'] ) && $_GET['msg'] == 'deleted' ) {
        echo '<div class="notice notice-success is-dismissible"><p>Visa file record deleted successfully.</p></div>';
    }

    $query = "
        SELECT v.*, c.full_name AS customer_name, c.mobile AS customer_mobile, c.passport_no
        FROM $table_visas v
        LEFT JOIN $table_customers c ON v.customer_id = c.id
        ORDER BY v.id DESC
    ";
    $visas = $wpdb->get_results( $query );
    ?>
    <div class="arms-card-panel" style="margin-top: 20px;">
        <div class="arms-p-header" style="padding: 15px 20px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 18px; margin: 0;">Visa Processing Pipeline</h2>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=visa&sub=add' ) ); ?>" style="background:#003376; color:#fff; padding:8px 14px; border-radius:4px; text-decoration:none; font-weight:600;">
                <span class="dashicons dashicons-plus" style="vertical-align:middle;"></span> New Application
            </a>
        </div>

        <div class="arms-table-responsive">
            <table class="arms-pricing-table" id="ifsVisaTable" style="width:100%;">
                <thead>
                    <tr>
                        <th>File ID</th>
                        <th>Applicant</th>
                        <th>Country</th>
                        <th>Visa Type</th>
                        <th>Submission Date</th>
                        <th>Delivery Date</th>
                        <th style="text-align: right;">Cost / Sell (৳)</th>
                        <th style="text-align: center;">Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $visas ) : foreach ( $visas as $row ) : 
                        $status_bg = '#fef3c7'; $status_color = '#b45309'; // Processing
                        if ( strtolower($row->status) === 'approved' ) { $status_bg = '#dcfce7'; $status_color = '#166534'; }
                        elseif ( strtolower($row->status) === 'rejected' ) { $status_bg = '#fee2e2'; $status_color = '#991b1b'; }
                        elseif ( strtolower($row->status) === 'delivered' ) { $status_bg = '#e0f2fe'; $status_color = '#0369a1'; }
                    ?>
                        <tr>
                            <td><strong>#VSA-<?php echo esc_html( $row->id ); ?></strong></td>
                            <td style="font-weight: 600; color: #0f172a;">
                                <?php echo esc_html( $row->customer_name ?: 'Unknown' ); ?>
                                <div style="font-size: 11px; color: #64748b; font-weight: normal;">Pass: <?php echo esc_html( $row->passport_no ?: '-' ); ?></div>
                            </td>
                            <td style="font-weight: 700; color: #0f172a;"><?php echo esc_html( $row->country ); ?></td>
                            <td><span style="background: #f1f5f9; padding: 2px 8px; border-radius: 4px; font-size: 12px;"><?php echo esc_html( $row->visa_type ); ?></span></td>
                            <td><?php echo ( $row->submission_date != '1970-01-01' ) ? date('d M Y', strtotime($row->submission_date)) : '-'; ?></td>
                            <td><?php echo ( $row->expected_delivery != '1970-01-01' ) ? date('d M Y', strtotime($row->expected_delivery)) : '-'; ?></td>
                            <td style="text-align: right;">
                                <div style="font-size: 11px; color: #64748b;">Cost: ৳<?php echo number_format( $row->buy_price, 2 ); ?></div>
                                <div style="font-weight: 700; color: #0f172a;">Sell: ৳<?php echo number_format( $row->sell_price, 2 ); ?></div>
                            </td>
                            <td style="text-align: center;">
                                <span style="background: <?php echo $status_bg; ?>; color: <?php echo $status_color; ?>; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 700;">
                                    <?php echo esc_html( $row->status ); ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 5px; justify-content: flex-end;">
                                    <a href="<?php echo admin_url( 'admin.php?page=ifs_travel_erp&tab=visa&sub=view&id=' . $row->id ); ?>" style="background:#f1f5f9; padding:4px 8px; border-radius:4px; text-decoration:none; color:#334155;" title="View">
                                        <span class="dashicons dashicons-visibility" style="font-size:14px; vertical-align:middle;"></span>
                                    </a>
                                    <a href="<?php echo admin_url( 'admin.php?page=ifs_travel_erp&tab=visa&sub=edit&id=' . $row->id ); ?>" style="background:#f1f5f9; padding:4px 8px; border-radius:4px; text-decoration:none; color:#2563eb;" title="Edit">
                                        <span class="dashicons dashicons-edit" style="font-size:14px; vertical-align:middle;"></span>
                                    </a>
                                    <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=visa&sub=delete&id=' . $row->id ), 'delete_visa_' . $row->id ); ?>" style="background:#fef2f2; padding:4px 8px; border-radius:4px; text-decoration:none; color:#dc2626;" onclick="return confirm('Are you sure you want to delete this visa record?');" title="Delete">
                                        <span class="dashicons dashicons-trash" style="font-size:14px; vertical-align:middle;"></span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="9" style="text-align: center; padding: 20px;">No visa processing records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        jQuery(document).ready(function($) {
            $('#ifsVisaTable').DataTable({
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