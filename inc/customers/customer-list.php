<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Enterprise Next-Gen Customer List & Directory Console
 * Features: Live Search Box, Custom Pagination, Per Page Selector, Avatar Photo Sync, Expiry Badge Warnings & Action Pills
 */
function ifs_terp_customer_list_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'iterp_customers';
    $base_url   = admin_url( 'admin.php?page=ifs_travel_erp&tab=customers' );

    // Handle Delete Action with Nonce Verification
    if ( isset( $_GET['sub'] ) && $_GET['sub'] === 'delete' && isset( $_GET['id'] ) ) {
        $del_id = intval( $_GET['id'] );
        check_admin_referer( 'delete_customer_' . $del_id );
        
        $customer_name = $wpdb->get_var( $wpdb->prepare( "SELECT full_name FROM $table_name WHERE id = %d", $del_id ) );
        $wpdb->delete( $table_name, array( 'id' => $del_id ), array( '%d' ) );
        
        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            ifs_terp_log_activity( "Deleted Customer Record #CUS-" . $del_id . " (" . $customer_name . ")" );
        }
        
        wp_safe_redirect( add_query_arg( array( 'page' => 'ifs_travel_erp', 'tab' => 'customers', 'msg' => 'deleted' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    $message = '';
    if ( isset( $_GET['msg'] ) && $_GET['msg'] === 'deleted' ) {
        $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Customer profile removed successfully.</div>';
    }

    // Dynamic Summary Aggregations
    $total_count       = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_name" );
    $retail_count      = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $table_name WHERE client_type = %s", 'Retail' ) );
    $corporate_count   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $table_name WHERE client_type = %s", 'Corporate' ) );
    $expired_passports = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_name WHERE passport_expiry != '1970-01-01' AND passport_expiry < CURDATE()" );
    $warning_passports = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_name WHERE passport_expiry != '1970-01-01' AND passport_expiry >= CURDATE() AND passport_expiry <= DATE_ADD(CURDATE(), INTERVAL 180 DAY)" );

    $customers = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id DESC" );
    ?>

    <div class="ifs-customer-list-workspace">
        <?php echo $message; ?>

        <!-- Metric Counter Ribbon -->
        <div class="ifs-list-metric-ribbon">
            <div class="ifs-metric-chip">
                <div class="chip-icon bg-blue"><span class="dashicons dashicons-admin-users"></span></div>
                <div>
                    <span class="chip-label">Total Profiles</span>
                    <strong class="chip-val"><?php echo number_format( $total_count ); ?></strong>
                </div>
            </div>
            <div class="ifs-metric-chip">
                <div class="chip-icon bg-indigo"><span class="dashicons dashicons-businessman"></span></div>
                <div>
                    <span class="chip-label">Corporate / VIP</span>
                    <strong class="chip-val"><?php echo number_format( $corporate_count ); ?></strong>
                </div>
            </div>
            <div class="ifs-metric-chip">
                <div class="chip-icon bg-amber"><span class="dashicons dashicons-warning"></span></div>
                <div>
                    <span class="chip-label">&lt; 6 Mos Expiry</span>
                    <strong class="chip-val color-amber"><?php echo number_format( $warning_passports ); ?></strong>
                </div>
            </div>
            <div class="ifs-metric-chip">
                <div class="chip-icon bg-rose"><span class="dashicons dashicons-dismiss"></span></div>
                <div>
                    <span class="chip-label">Expired Passports</span>
                    <strong class="chip-val color-rose"><?php echo number_format( $expired_passports ); ?></strong>
                </div>
            </div>
        </div>

        <!-- Master Data Table Card -->
        <div class="ifs-table-card">
            <div class="ifs-table-top-bar">
                <div class="ifs-table-title-group">
                    <h3 class="ifs-table-heading"><span class="dashicons dashicons-groups"></span> Passenger Directory</h3>
                    <p class="ifs-table-caption">Comprehensive passenger dossier, passport validities, and quick service links</p>
                </div>
                <div class="ifs-table-btn-group">
                    <a href="<?php echo esc_url( $base_url . '&sub=add' ); ?>" class="ifs-btn-primary">
                        <span class="dashicons dashicons-plus-alt2"></span> Register New Passenger
                    </a>
                </div>
            </div>

            <!-- Custom DataTables Controls Toolbar (Search Box & Per Page Selector) -->
            <div class="ifs-custom-table-controls">
                <div class="ifs-per-page-wrap">
                    <label for="ifsPerPageSelect">Show</label>
                    <select id="ifsPerPageSelect" class="ifs-select-control">
                        <option value="10">10</option>
                        <option value="15" selected>15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span>entries</span>
                </div>
                <div class="ifs-live-search-wrap">
                    <label for="ifsLiveSearchInput"><span class="dashicons dashicons-search"></span></label>
                    <input type="text" id="ifsLiveSearchInput" class="ifs-search-input" placeholder="Search by name, mobile, email...">
                </div>
            </div>

            <div class="ifs-table-responsive-wrapper">
                <table class="ifs-pro-datatable" id="ifsCustomerTable">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Client ID</th>
                            <th>Passenger Bio &amp; Demographics</th>
                            <th>Primary Contact</th>
                            <th>Validity Status</th>
                            <th style="text-align: right; width: 170px;">Quick Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $customers ) : foreach ( $customers as $row ) : 
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
                            $initial = ( count( $parts ) > 1 ) ? ( mb_substr( $parts[0], 0, 1 ) . mb_substr( $parts[count($parts)-1], 0, 1 ) ) : mb_substr( $full_name, 0, 2 );
                            $initial = strtoupper( $initial );

                            // Passport Expiry Logic
                            $has_expiry   = ( ! empty( $row->passport_expiry ) && $row->passport_expiry !== '1970-01-01' );
                            $badge_class  = 'badge-valid';
                            $badge_text   = 'Valid';
                            $days_left    = 9999;

                            if ( $has_expiry ) {
                                $expiry_time = strtotime( $row->passport_expiry );
                                $today_time  = strtotime( date( 'Y-m-d' ) );
                                $days_left   = ceil( ( $expiry_time - $today_time ) / ( 60 * 60 * 24 ) );

                                if ( $days_left < 0 ) {
                                    $badge_class = 'badge-expired';
                                    $badge_text  = 'Expired';
                                } elseif ( $days_left <= 180 ) {
                                    $badge_class = 'badge-warning';
                                    $badge_text  = '&lt; 6 Mos (' . $days_left . 'd)';
                                } else {
                                    $badge_class = 'badge-valid';
                                    $badge_text  = 'Valid (' . date( 'M Y', $expiry_time ) . ')';
                                }
                            } else {
                                $badge_class = 'badge-none';
                                $badge_text  = 'No Passport';
                            }
                        ?>
                            <tr>
                                <td>
                                    <span class="ifs-id-badge">#CUS-<?php echo str_pad( (string) $row->id, 5, '0', STR_PAD_LEFT ); ?></span>
                                </td>
                                <td>
                                    <div class="ifs-passenger-cell">
                                        <div class="ifs-cell-avatar">
                                            <?php if ( ! empty( $photo_url ) ) : ?>
                                                <img src="<?php echo $photo_url; ?>" alt="Passenger Photo" class="avatar-img-fit" />
                                            <?php else : ?>
                                                <?php echo esc_html( $initial ); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <a href="<?php echo esc_url( $base_url . '&sub=view&id=' . $row->id ); ?>" class="ifs-passenger-name">
                                                <?php echo $title_prefix . $full_name; ?>
                                            </a>
                                            <div class="ifs-passenger-submeta">
                                                <span><?php echo $gender; ?> (<?php echo $ptype; ?>)</span>
                                                <span class="meta-dot"></span>
                                                <span><?php echo ( ! empty( $row->date_of_birth ) && $row->date_of_birth !== '1970-01-01' ) ? date( 'd M Y', strtotime( $row->date_of_birth ) ) : 'DOB: N/A'; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="ifs-contact-cell">
                                        <a href="tel:<?php echo esc_attr( $mobile ); ?>" class="ifs-phone-link">
                                            <span class="dashicons dashicons-phone"></span> <?php echo $mobile; ?>
                                        </a>
                                        <?php if ( $whatsapp ) : ?>
                                            <a href="https://wa.me/<?php echo preg_replace( '/[^0-9]/', '', $whatsapp ); ?>" target="_blank" class="ifs-whatsapp-text">
                                                <span class="dashicons dashicons-format-chat"></span> <?php echo $whatsapp; ?>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ( $email ) : ?>
                                            <div class="ifs-email-text"><span class="dashicons dashicons-email"></span> <?php echo $email; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="ifs-expiry-cell">
                                        <span class="ifs-status-badge <?php echo esc_attr( $badge_class ); ?>">
                                            <?php echo $badge_text; ?>
                                        </span>
                                        <?php if ( $has_expiry ) : ?>
                                            <span class="expiry-date-label"><?php echo date( 'd M, Y', strtotime( $row->passport_expiry ) ); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="text-align: right;">
                                    <div class="ifs-action-pills">
                                        <a href="<?php echo esc_url( $base_url . '&sub=view&id=' . $row->id ); ?>" class="ifs-action-pill view" title="View Digital Dossier">
                                            <span class="dashicons dashicons-visibility"></span> View
                                        </a>
                                        <a href="<?php echo esc_url( $base_url . '&sub=edit&id=' . $row->id ); ?>" class="ifs-action-pill edit" title="Edit Profile">
                                            <span class="dashicons dashicons-edit"></span> Edit
                                        </a>
                                        <a href="<?php echo wp_nonce_url( $base_url . '&sub=delete&id=' . $row->id, 'delete_customer_' . $row->id ); ?>" 
                                           class="ifs-action-pill delete" 
                                           onclick="return confirm('Are you sure you want to permanently delete this passenger record? This action cannot be undone.');" 
                                           title="Delete Record">
                                            <span class="dashicons dashicons-trash"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr>
                                <td colspan="5" class="ifs-empty-table">
                                    <div class="ifs-empty-state">
                                        <span class="dashicons dashicons-admin-users"></span>
                                        <h4>No Passenger Profiles Found</h4>
                                        <p>Get started by adding your first traveler record into the system.</p>
                                        <a href="<?php echo esc_url( $base_url . '&sub=add' ); ?>" class="ifs-btn-primary" style="margin-top: 10px;">
                                            <span class="dashicons dashicons-plus-alt2"></span> Register Passenger
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modern High-End UI Stylesheet -->
    <style>
        .ifs-customer-list-workspace {
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
        .chip-icon.bg-amber  { background: linear-gradient(135deg, #d97706 0%, #b45309 100%); }
        .chip-icon.bg-rose   { background: linear-gradient(135deg, #e11d48 0%, #be123c 100%); }
        .chip-icon .dashicons { font-size: 22px; width: 22px; height: 22px; }

        .chip-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; display: block; margin-bottom: 2px; }
        .chip-val { font-size: 20px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
        .color-amber { color: #d97706 !important; }
        .color-rose { color: #e11d48 !important; }

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

        /* Custom Table Controls Toolbar (Per Page & Search Box) */
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
        .ifs-per-page-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #475569;
            font-weight: 600;
        }
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

        .ifs-live-search-wrap {
            position: relative;
            display: flex;
            align-items: center;
            min-width: 280px;
        }
        .ifs-live-search-wrap label {
            position: absolute;
            left: 12px;
            color: #94a3b8;
            display: flex;
            align-items: center;
            pointer-events: none;
        }
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

        /* Modern Table Styles */
        .ifs-table-responsive-wrapper {
            padding: 15px 24px 24px 24px;
            overflow-x: auto;
        }
        .ifs-pro-datatable {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
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
        .ifs-pro-datatable tbody tr:hover td {
            background: #f8fafc;
        }

        /* Cell Visuals */
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

        .ifs-passenger-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .ifs-cell-avatar {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: linear-gradient(135deg, #003376 0%, #0284c7 100%);
            color: #ffffff;
            font-weight: 800;
            font-size: 12.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .avatar-img-fit {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .ifs-passenger-name {
            font-weight: 700;
            color: #0f172a;
            text-decoration: none;
            font-size: 13.5px;
            transition: color 0.15s ease;
        }
        .ifs-passenger-name:hover {
            color: #003376;
        }
        .ifs-passenger-submeta {
            font-size: 11px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 2px;
        }
        .meta-dot {
            width: 3px;
            height: 3px;
            border-radius: 50%;
            background: #cbd5e1;
        }

        .ifs-contact-cell {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .ifs-phone-link {
            color: #0f172a;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .ifs-phone-link .dashicons { font-size: 14px; width: 14px; height: 14px; color: #0284c7; }
        .ifs-whatsapp-text {
            font-size: 11.5px;
            color: #15803d;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .ifs-whatsapp-text .dashicons { font-size: 13px; width: 13px; height: 13px; color: #16a34a; }
        .ifs-email-text {
            font-size: 11.5px;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .ifs-email-text .dashicons { font-size: 13px; width: 13px; height: 13px; color: #94a3b8; }

        /* Expiry Statuses */
        .ifs-status-badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 10.5px;
            font-weight: 700;
            width: fit-content;
        }
        .badge-valid   { background: #dcfce7; color: #15803d; }
        .badge-warning { background: #fef3c7; color: #b45309; }
        .badge-expired { background: #fee2e2; color: #b91c1c; }
        .badge-none    { background: #f1f5f9; color: #64748b; }
        .expiry-date-label { font-size: 11px; color: #64748b; }

        /* Modern Gorgeous Pill Action Buttons */
        .ifs-action-pills {
            display: flex;
            gap: 5px;
            justify-content: flex-end;
            align-items: center;
        }
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
            border: 1px solid transparent;
            white-space: nowrap;
        }
        .ifs-action-pill.view {
            background: #f1f5f9;
            color: #475569;
            border-color: #e2e8f0;
        }
        .ifs-action-pill.view:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .ifs-action-pill.edit {
            background: #eff6ff;
            color: #2563eb;
            border-color: #dbeafe;
        }
        .ifs-action-pill.edit:hover {
            background: #dbeafe;
            color: #1d4ed8;
        }
        .ifs-action-pill.delete {
            background: #fef2f2;
            color: #dc2626;
            border-color: #fee2e2;
            padding: 5px 8px;
        }
        .ifs-action-pill.delete:hover {
            background: #fee2e2;
            color: #b91c1c;
        }
        .ifs-action-pill .dashicons {
            font-size: 13px;
            width: 13px;
            height: 13px;
            margin-top: 1px;
        }

        /* Empty State */
        .ifs-empty-table {
            text-align: center;
            padding: 50px 20px !important;
        }
        .ifs-empty-state .dashicons {
            font-size: 48px;
            width: 48px;
            height: 48px;
            color: #cbd5e1;
            margin-bottom: 12px;
        }
        .ifs-empty-state h4 { margin: 0 0 4px 0; font-size: 16px; font-weight: 700; color: #334155; }
        .ifs-empty-state p { margin: 0; color: #94a3b8; font-size: 13px; }

        /* DataTables Custom Polish */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            display: none !important; /* Replaced by custom toolbar controls */
        }
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            margin-top: 18px;
            font-size: 13px;
            color: #64748b;
        }
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
            var table = $('#ifsCustomerTable').DataTable({
                "pageLength": 15,
                "ordering": true,
                "info": true,
                "searching": true,
                "lengthChange": false,
                "order": [[ 0, "desc" ]],
                "language": {
                    "info": "Showing _START_ to _END_ of _TOTAL_ passenger profiles",
                    "infoEmpty": "Showing 0 to 0 of 0 profiles",
                    "infoFiltered": "(filtered from _MAX_ total profiles)",
                    "paginate": {
                        "previous": "&larr; Prev",
                        "next": "Next &rarr;"
                    }
                }
            });

            // Bind Custom Per Page Selector
            $('#ifsPerPageSelect').on('change', function() {
                var lengthVal = parseInt($(this).val());
                table.page.len(lengthVal).draw();
            });

            // Bind Custom Live Search Box
            $('#ifsLiveSearchInput').on('keyup', function() {
                table.search(this.value).draw();
            });
        }
    });
    </script>
    <?php
}