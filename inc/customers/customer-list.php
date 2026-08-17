<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function ifs_terp_customer_list_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'iterp_customers';

    if ( isset( $_GET['msg'] ) && $_GET['msg'] == 'deleted' ) {
        echo '<div class="notice notice-success is-dismissible"><p>Customer deleted successfully.</p></div>';
    }

    $customers = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id DESC" );
    ?>
    <div class="arms-card-panel" style="margin-top: 20px;">
        <div class="arms-p-header" style="padding: 15px 20px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 18px; margin: 0;">Registered Customers</h2>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=customers&sub=add' ) ); ?>" class="arms-btn-ui btn-ui-primary" style="background:#003376; color:#fff; padding:8px 14px; border-radius:4px; text-decoration:none; font-weight:600;">
                <span class="dashicons dashicons-plus" style="vertical-align:middle;"></span> Add Customer
            </a>
        </div>

        <div class="arms-table-responsive">
            <table class="arms-pricing-table" id="ifsCustomerTable" style="width:100%;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Type</th>
                        <th>Passport No</th>
                        <th>Expiry Date</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $customers ) : foreach ( $customers as $row ) : 
                        $expiry_date = strtotime( $row->passport_expiry );
                        $today = strtotime( date('Y-m-d') );
                        $days_left = ($expiry_date - $today) / (60 * 60 * 24);
                        
                        $expiry_style = '';
                        if ( $days_left < 0 ) {
                            $expiry_style = 'color: #dc2626; font-weight: bold;';
                        } elseif ( $days_left <= 180 ) {
                            $expiry_style = 'color: #d97706; font-weight: bold;';
                        }
                    ?>
                        <tr>
                            <td><strong>#CUS-<?php echo esc_html( $row->id ); ?></strong></td>
                            <td style="font-weight: 600; color: #0f172a;"><?php echo esc_html( $row->full_name ); ?></td>
                            <td><?php echo esc_html( $row->mobile ); ?></td>
                            <td><span class="arms-badge-tag" style="background:#e0f2fe; color:#0369a1; padding:2px 8px; border-radius:4px; font-size:11px;"><?php echo esc_html( $row->client_type ); ?></span></td>
                            <td><?php echo esc_html( $row->passport_no ?: '-' ); ?></td>
                            <td style="<?php echo $expiry_style; ?>">
                                <?php echo ( $row->passport_expiry != '1970-01-01' ) ? date( 'd M, Y', strtotime( $row->passport_expiry ) ) : '-'; ?>
                            </td>
                            <td style="text-align: right;">
                                <div class="arms-actions-wrapper" style="display: flex; gap: 5px; justify-content: flex-end;">
                                    <a href="<?php echo admin_url( 'admin.php?page=ifs_travel_erp&tab=customers&sub=view&id=' . $row->id ); ?>" class="arms-action-btn" style="background:#f1f5f9; padding:4px 8px; border-radius:4px; text-decoration:none; color:#334155;" title="View">
                                        <span class="dashicons dashicons-visibility" style="font-size:14px; vertical-align:middle;"></span>
                                    </a>
                                    <a href="<?php echo admin_url( 'admin.php?page=ifs_travel_erp&tab=customers&sub=edit&id=' . $row->id ); ?>" class="arms-action-btn" style="background:#f1f5f9; padding:4px 8px; border-radius:4px; text-decoration:none; color:#2563eb;" title="Edit">
                                        <span class="dashicons dashicons-edit" style="font-size:14px; vertical-align:middle;"></span>
                                    </a>
                                    <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=customers&sub=delete&id=' . $row->id ), 'delete_customer_' . $row->id ); ?>" class="arms-action-btn" style="background:#fef2f2; padding:4px 8px; border-radius:4px; text-decoration:none; color:#dc2626;" onclick="return confirm('Are you sure you want to delete this customer?');" title="Delete">
                                        <span class="dashicons dashicons-trash" style="font-size:14px; vertical-align:middle;"></span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="7" style="text-align: center; padding: 20px;">No customers found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        jQuery(document).ready(function($) {
            $('#ifsCustomerTable').DataTable({
                "pageLength": 15,
                "ordering": true,
                "info": true,
                "searching": true
            });
        });
    </script>
    <?php
}