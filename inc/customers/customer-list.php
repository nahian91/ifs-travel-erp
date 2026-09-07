<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Customer Directory Console
 */
function ifs_terp_customer_list_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'iterp_customers';
    $base_url   = admin_url( 'admin.php?page=ifs_travel_erp&tab=customers' );

    // Handle Delete Action with Nonce & Capability Verification
    if ( isset( $_GET['sub'] ) && sanitize_key( wp_unslash( $_GET['sub'] ) ) === 'delete' && isset( $_GET['id'] ) ) {
        if ( function_exists( 'ifs_terp_has_access' ) ) {
            if ( ! ifs_terp_has_access( array( 'admin_manager' ) ) && ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Access Denied: You do not possess the required privilege level to delete this record.', 'ifs-travel-erp' ) );
            }
        } elseif ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access Denied: You do not have permission to delete this record.', 'ifs-travel-erp' ) );
        }

        $del_id = absint( wp_unslash( $_GET['id'] ) );
        check_admin_referer( 'delete_customer_' . $del_id );
        
        $customer_name = $wpdb->get_var( $wpdb->prepare( "SELECT full_name FROM {$table_name} WHERE id = %d", $del_id ) );
        $wpdb->delete( $table_name, array( 'id' => $del_id ), array( '%d' ) );
        
        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            ifs_terp_log_activity( "Deleted Customer Record #CUS-" . $del_id . " (" . $customer_name . ")" );
        }
        
        wp_safe_redirect( add_query_arg( array( 'page' => 'ifs_travel_erp', 'tab' => 'customers', 'msg' => 'deleted' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    $message = '';
    if ( isset( $_GET['msg'] ) && sanitize_key( wp_unslash( $_GET['msg'] ) ) === 'deleted' ) {
        $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Customer profile removed successfully.', 'ifs-travel-erp' ) . '</div>';
    }

    $customers = $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY id DESC" );
    ?>

    <div class="ifs-customer-list-workspace">
        <?php echo wp_kses_post( $message ); ?>

        <!-- Master Data Table Card -->
        <div class="ifs-table-card">
            <!-- Custom DataTables Controls Toolbar -->
            <div class="ifs-custom-table-controls">
                <div class="ifs-per-page-wrap">
                    <label for="ifsPerPageSelect"><?php esc_html_e( 'Show', 'ifs-travel-erp' ); ?></label>
                    <select id="ifsPerPageSelect" class="ifs-select-control">
                        <option value="10">10</option>
                        <option value="15" selected>15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span><?php esc_html_e( 'entries', 'ifs-travel-erp' ); ?></span>
                </div>
                <div class="ifs-live-search-wrap">
                    <label for="ifsLiveSearchInput"><span class="dashicons dashicons-search"></span></label>
                    <input type="text" id="ifsLiveSearchInput" class="ifs-search-input" placeholder="<?php esc_attr_e( 'Search by name, mobile, passport, email...', 'ifs-travel-erp' ); ?>">
                </div>
            </div>

            <div class="ifs-table-responsive-wrapper">
                <table class="ifs-pro-datatable" id="ifsCustomerTable">
                    <thead>
                        <tr>
                            <th class="col-client-id"><?php esc_html_e( 'Client ID', 'ifs-travel-erp' ); ?></th>
                            <th><?php esc_html_e( 'Customer & Demographics', 'ifs-travel-erp' ); ?></th>
                            <th><?php esc_html_e( 'Contact Info', 'ifs-travel-erp' ); ?></th>
                            <th><?php esc_html_e( 'Passport Expiry', 'ifs-travel-erp' ); ?></th>
                            <th class="col-actions"><?php esc_html_e( 'Actions', 'ifs-travel-erp' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $customers ) ) : foreach ( $customers as $row ) : 
                            $title_prefix = ! empty( $row->title ) ? esc_html( $row->title ) . '. ' : '';
                            $full_name    = esc_html( $row->full_name );
                            $gender       = ! empty( $row->gender ) ? esc_html( $row->gender ) : 'Male';
                            $ptype        = ! empty( $row->passenger_type ) ? esc_html( $row->passenger_type ) : 'Adult';
                            $mobile       = esc_html( $row->mobile );
                            $whatsapp     = ! empty( $row->whatsapp_no ) ? esc_html( $row->whatsapp_no ) : '';
                            $email        = ! empty( $row->email ) ? esc_html( $row->email ) : '';
                            $photo_url    = ! empty( $row->photo_url ) ? esc_url( $row->photo_url ) : '';

                            // Name Initials for Avatar
                            $parts   = explode( ' ', trim( $full_name ) );
                            $initial = ( count( $parts ) > 1 ) ? ( mb_substr( $parts[0], 0, 1 ) . mb_substr( $parts[ count( $parts ) - 1 ], 0, 1 ) ) : mb_substr( $full_name, 0, 2 );
                            $initial = strtoupper( $initial );

                            // Passport Expiry Logic
                            $has_expiry  = ( ! empty( $row->passport_expiry ) && $row->passport_expiry !== '1970-01-01' && $row->passport_expiry !== '0000-00-00' );
                            $badge_class = 'badge-valid';
                            $badge_text  = 'Valid';

                            if ( $has_expiry ) {
                                $expiry_time = strtotime( $row->passport_expiry );
                                $today_time  = strtotime( date( 'Y-m-d' ) );
                                $days_left   = ceil( ( $expiry_time - $today_time ) / ( 60 * 60 * 24 ) );

                                if ( $days_left < 0 ) {
                                    $badge_class = 'badge-expired';
                                    $badge_text  = __( 'Expired', 'ifs-travel-erp' );
                                } elseif ( $days_left <= 180 ) {
                                    $badge_class = 'badge-warning';
                                    $badge_text  = '&lt; 6 Mos (' . $days_left . 'd)';
                                } else {
                                    $badge_class = 'badge-valid';
                                    $badge_text  = sprintf( __( 'Valid (%s)', 'ifs-travel-erp' ), date( 'M Y', $expiry_time ) );
                                }
                            } else {
                                $badge_class = 'badge-none';
                                $badge_text  = __( 'No Passport', 'ifs-travel-erp' );
                            }

                            $view_url   = add_query_arg( array( 'sub' => 'view', 'id' => $row->id ), $base_url );
                            $edit_url   = add_query_arg( array( 'sub' => 'edit', 'id' => $row->id ), $base_url );
                            $delete_url = wp_nonce_url( add_query_arg( array( 'sub' => 'delete', 'id' => $row->id ), $base_url ), 'delete_customer_' . $row->id );
                        ?>
                            <tr>
                                <td>
                                    <span class="ifs-id-badge">#CUS-<?php echo esc_html( str_pad( (string) $row->id, 5, '0', STR_PAD_LEFT ) ); ?></span>
                                </td>
                                <td>
                                    <div class="ifs-passenger-cell">
                                        <div class="ifs-cell-avatar">
                                            <?php if ( ! empty( $photo_url ) ) : ?>
                                                <img src="<?php echo esc_url( $photo_url ); ?>" alt="<?php echo esc_attr( $full_name ); ?>" class="avatar-img-fit" />
                                            <?php else : ?>
                                                <?php echo esc_html( $initial ); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <a href="<?php echo esc_url( $view_url ); ?>" class="ifs-passenger-name">
                                                <?php echo esc_html( $title_prefix . $full_name ); ?>
                                            </a>
                                            <div class="ifs-passenger-submeta">
                                                <span><?php echo esc_html( $gender ); ?> (<?php echo esc_html( $ptype ); ?>)</span>
                                                <span class="meta-dot"></span>
                                                <span><?php echo ( ! empty( $row->date_of_birth ) && $row->date_of_birth !== '1970-01-01' && $row->date_of_birth !== '0000-00-00' ) ? esc_html( date_i18n( 'd M Y', strtotime( $row->date_of_birth ) ) ) : 'DOB: N/A'; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="ifs-contact-cell">
                                        <a href="tel:<?php echo esc_attr( $mobile ); ?>" class="ifs-phone-link">
                                            <span class="dashicons dashicons-phone"></span> <?php echo esc_html( $mobile ); ?>
                                        </a>
                                        <?php if ( ! empty( $whatsapp ) ) : ?>
                                            <a href="https://wa.me/<?php echo esc_attr( preg_replace( '/[^0-9]/', '', $whatsapp ) ); ?>" target="_blank" rel="noopener noreferrer" class="ifs-whatsapp-text">
                                                <span class="dashicons dashicons-format-chat"></span> <?php echo esc_html( $whatsapp ); ?>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ( ! empty( $email ) ) : ?>
                                            <div class="ifs-email-text"><span class="dashicons dashicons-email"></span> <?php echo esc_html( $email ); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="ifs-expiry-cell">
                                        <span class="ifs-status-badge <?php echo esc_attr( $badge_class ); ?>">
                                            <?php echo wp_kses_post( $badge_text ); ?>
                                        </span>
                                        <?php if ( $has_expiry ) : ?>
                                            <span class="expiry-date-label"><?php echo esc_html( date_i18n( 'd M, Y', strtotime( $row->passport_expiry ) ) ); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="col-actions">
                                    <div class="ifs-action-pills">
                                        <a href="<?php echo esc_url( $view_url ); ?>" class="ifs-action-pill view" title="<?php esc_attr_e( 'View Profile', 'ifs-travel-erp' ); ?>">
                                            <span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'View', 'ifs-travel-erp' ); ?>
                                        </a>
                                        <a href="<?php echo esc_url( $edit_url ); ?>" class="ifs-action-pill edit" title="<?php esc_attr_e( 'Edit Profile', 'ifs-travel-erp' ); ?>">
                                            <span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Edit', 'ifs-travel-erp' ); ?>
                                        </a>
                                        <a href="<?php echo esc_url( $delete_url ); ?>" 
                                           class="ifs-action-pill delete" 
                                           onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to permanently delete this customer record?', 'ifs-travel-erp' ) ); ?>');" 
                                           title="<?php esc_attr_e( 'Delete Record', 'ifs-travel-erp' ); ?>">
                                            <span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Delete', 'ifs-travel-erp' ); ?>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr>
                                <td colspan="5" class="ifs-empty-table">
                                    <div class="ifs-empty-state">
                                        <span class="dashicons dashicons-admin-users"></span>
                                        <h4><?php esc_html_e( 'No Customer Profiles Found', 'ifs-travel-erp' ); ?></h4>
                                        <p><?php esc_html_e( 'No customer records are currently registered.', 'ifs-travel-erp' ); ?></p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        if ($.fn.DataTable && $('#ifsCustomerTable tbody tr td').length > 1) {
            var table = $('#ifsCustomerTable').DataTable({
                "dom": "rt<'ifs-dt-footer'ip>",
                "pageLength": 15,
                "ordering": true,
                "info": true,
                "searching": true,
                "lengthChange": false,
                "order": [[ 0, "desc" ]],
                "columnDefs": [
                    { "orderable": false, "targets": 4 }
                ],
                "language": {
                    "info": "Showing _START_ to _END_ of _TOTAL_ customers",
                    "infoEmpty": "Showing 0 to 0 of 0 customers",
                    "infoFiltered": "(filtered from _MAX_ total)",
                    "paginate": {
                        "previous": "&larr;",
                        "next": "&rarr;"
                    }
                }
            });

            $('#ifsPerPageSelect').on('change', function() {
                table.page.len(parseInt($(this).val(), 10)).draw();
            });

            $('#ifsLiveSearchInput').on('keyup', function() {
                table.search(this.value).draw();
            });
        }
    });
    </script>
    <?php
}