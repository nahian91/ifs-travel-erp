<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_agent_list_page' ) ) {
    /**
     * B2B Partner Agencies Directory Console
     */
    function ifs_terp_agent_list_page() {
        global $wpdb;
        $table_agents = $wpdb->prefix . 'iterp_agents';
        $base_url     = admin_url( 'admin.php?page=ifs_travel_erp&tab=b2b_agents' );

        $sub_action = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : '';

        // Handle Delete Action with Nonce Verification
        if ( 'delete' === $sub_action && isset( $_GET['id'] ) ) {
            $del_id = absint( wp_unslash( $_GET['id'] ) );
            check_admin_referer( 'delete_agent_' . $del_id );

            $agent_name = $wpdb->get_var( $wpdb->prepare( "SELECT agency_name FROM {$table_agents} WHERE id = %d", $del_id ) );
            $wpdb->delete( $table_agents, array( 'id' => $del_id ), array( '%d' ) );

            if ( function_exists( 'ifs_terp_log_activity' ) ) {
                ifs_terp_log_activity( "Deleted B2B Sub-Agent Account: {$agent_name} (ID: #AGT-{$del_id})" );
            }

            $redirect_url = add_query_arg(
                array(
                    'page' => 'ifs_travel_erp',
                    'tab'  => 'b2b_agents',
                    'msg'  => 'deleted',
                ),
                admin_url( 'admin.php' )
            );
            wp_safe_redirect( $redirect_url );
            exit;
        }

        $msg = isset( $_GET['msg'] ) ? sanitize_key( wp_unslash( $_GET['msg'] ) ) : '';
        $message = '';
        if ( 'deleted' === $msg ) {
            $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Sub-agent partner account permanently removed.', 'ifs-travel-erp' ) . '</div>';
        }

        $agents = $wpdb->get_results( "SELECT * FROM {$table_agents} ORDER BY id DESC" );
        ?>

        <div class="wrap ifs-agent-list-workspace">
            <?php echo wp_kses_post( $message ); ?>

            <!-- Master Data Table Card -->
            <div class="ifs-table-card">
                <!-- Custom DataTables Controls Toolbar -->
                <div class="ifs-custom-table-controls">
                    <div class="ifs-per-page-wrap">
                        <label for="ifsAgentPerPageSelect"><?php esc_html_e( 'Show', 'ifs-travel-erp' ); ?></label>
                        <select id="ifsAgentPerPageSelect" class="ifs-select-control">
                            <option value="10">10</option>
                            <option value="15" selected>15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span><?php esc_html_e( 'entries', 'ifs-travel-erp' ); ?></span>
                    </div>
                    <div class="ifs-live-search-wrap">
                        <label for="ifsAgentSearchInput"><span class="dashicons dashicons-search"></span></label>
                        <input type="text" id="ifsAgentSearchInput" class="ifs-search-input" placeholder="<?php esc_attr_e( 'Search by agency name, contact person, mobile, city...', 'ifs-travel-erp' ); ?>">
                    </div>
                </div>

                <div class="ifs-table-responsive-wrapper">
                    <table class="ifs-pro-datatable" id="ifsAgentTable">
                        <thead>
                            <tr>
                                <th style="width: 100px;"><?php esc_html_e( 'Agent ID', 'ifs-travel-erp' ); ?></th>
                                <th><?php esc_html_e( 'Agency Profile & Location', 'ifs-travel-erp' ); ?></th>
                                <th><?php esc_html_e( 'Primary Contact', 'ifs-travel-erp' ); ?></th>
                                <th><?php esc_html_e( 'Direct Phone / Mobile', 'ifs-travel-erp' ); ?></th>
                                <th style="text-align: right;"><?php esc_html_e( 'Credit Limit (৳)', 'ifs-travel-erp' ); ?></th>
                                <th style="text-align: right;"><?php esc_html_e( 'Current Balance (৳)', 'ifs-travel-erp' ); ?></th>
                                <th style="text-align: center; width: 100px;"><?php esc_html_e( 'Status', 'ifs-travel-erp' ); ?></th>
                                <th style="text-align: right; width: 240px;" data-sortable="false"><?php esc_html_e( 'Quick Actions', 'ifs-travel-erp' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( ! empty( $agents ) ) : foreach ( $agents as $row ) : 
                                $balance      = (float) $row->current_balance;
                                $credit_limit = (float) $row->credit_limit;
                                $is_negative  = ( $balance < 0 );
                                $status_class = ( 'Active' === $row->status ) ? 'badge-active' : 'badge-suspended';
                                $city_display = ! empty( $row->city ) ? esc_html( $row->city ) : esc_html__( 'Head Office', 'ifs-travel-erp' );
                            ?>
                                <tr>
                                    <td>
                                        <span class="ifs-id-badge">#AGT-<?php echo esc_html( str_pad( (string) $row->id, 5, '0', STR_PAD_LEFT ) ); ?></span>
                                    </td>
                                    <td>
                                        <div class="agency-title-cell">
                                            <a href="<?php echo esc_url( add_query_arg( array( 'sub' => 'ledger', 'id' => $row->id ), $base_url ) ); ?>" class="agency-name-link">
                                                <?php echo esc_html( $row->agency_name ); ?>
                                            </a>
                                            <div class="agency-submeta">
                                                <span class="location-tag"><span class="dashicons dashicons-location"></span> <?php echo $city_display; ?></span>
                                                <?php if ( ! empty( $row->email ) ) : ?>
                                                    <span class="meta-dot"></span>
                                                    <span><?php echo esc_html( $row->email ); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="contact-name"><?php echo esc_html( $row->contact_person ); ?></span>
                                    </td>
                                    <td>
                                        <a href="tel:<?php echo esc_attr( $row->mobile ); ?>" class="ifs-phone-link font-mono">
                                            <span class="dashicons dashicons-phone"></span> <?php echo esc_html( $row->mobile ); ?>
                                        </a>
                                    </td>
                                    <td style="text-align: right;" class="font-mono font-bold color-slate">
                                        ৳<?php echo esc_html( number_format( $credit_limit, 2 ) ); ?>
                                    </td>
                                    <td style="text-align: right;" class="font-mono font-bold <?php echo $is_negative ? 'color-rose' : 'color-emerald'; ?>">
                                        <?php echo $is_negative ? '-৳' : '+৳'; ?><?php echo esc_html( number_format( abs( $balance ), 2 ) ); ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="ifs-status-badge <?php echo esc_attr( $status_class ); ?>">
                                            <?php echo esc_html( strtoupper( $row->status ) ); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <div class="ifs-action-pills">
                                            <a href="<?php echo esc_url( add_query_arg( array( 'sub' => 'ledger', 'id' => $row->id ), $base_url ) ); ?>" class="ifs-action-pill ledger" title="<?php esc_attr_e( 'Account Ledger & Statement', 'ifs-travel-erp' ); ?>">
                                                <span class="dashicons dashicons-media-spreadsheet"></span> <?php esc_html_e( 'Ledger', 'ifs-travel-erp' ); ?>
                                            </a>
                                            <a href="<?php echo esc_url( add_query_arg( array( 'sub' => 'edit', 'id' => $row->id ), $base_url ) ); ?>" class="ifs-action-pill edit" title="<?php esc_attr_e( 'Edit Profile', 'ifs-travel-erp' ); ?>">
                                                <span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Edit', 'ifs-travel-erp' ); ?>
                                            </a>
                                            <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'sub' => 'delete', 'id' => $row->id ), $base_url ), 'delete_agent_' . $row->id ) ); ?>" 
                                               class="ifs-action-pill delete" 
                                               onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this B2B sub-agent? This action cannot be undone.', 'ifs-travel-erp' ) ); ?>');" 
                                               title="<?php esc_attr_e( 'Delete Agent', 'ifs-travel-erp' ); ?>">
                                                <span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Delete', 'ifs-travel-erp' ); ?>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; else : ?>
                                <tr>
                                    <td colspan="8" class="ifs-empty-table">
                                        <div class="ifs-empty-state">
                                            <span class="dashicons dashicons-networking"></span>
                                            <h4><?php esc_html_e( 'No B2B Sub-Agents Registered', 'ifs-travel-erp' ); ?></h4>
                                            <p><?php esc_html_e( 'No partner agencies are currently registered in the system.', 'ifs-travel-erp' ); ?></p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Ultra-Modern Stylesheet -->
        <style>
            .ifs-agent-list-workspace {
                max-width: 1420px;
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

            /* Master Table Card */
            .ifs-table-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
                overflow: hidden;
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
            .ifs-pro-datatable { width: 100%; border-collapse: collapse; font-size: 13.5px; }
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

            .agency-title-cell { display: flex; flex-direction: column; gap: 2px; }
            .agency-name-link {
                color: #0f172a;
                font-weight: 700;
                font-size: 13.5px;
                text-decoration: none;
                transition: color 0.15s ease;
            }
            .agency-name-link:hover { color: #003376; }
            .agency-submeta {
                font-size: 11.5px;
                color: #64748b;
                display: flex;
                align-items: center;
                gap: 6px;
                margin-top: 1px;
            }
            .location-tag { display: inline-flex; align-items: center; gap: 2px; }
            .location-tag .dashicons { font-size: 12px; width: 12px; height: 12px; color: #0284c7; }
            .meta-dot { width: 3px; height: 3px; border-radius: 50%; background: #cbd5e1; }

            .contact-name { font-weight: 600; color: #334155; }
            .ifs-phone-link {
                color: #334155;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 4px;
                font-size: 12.5px;
                font-weight: 500;
                transition: color 0.15s ease;
            }
            .ifs-phone-link:hover { color: #003376; }
            .ifs-phone-link .dashicons { font-size: 13px; width: 13px; height: 13px; color: #94a3b8; }

            .ifs-status-badge {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 6px;
                font-size: 10.5px;
                font-weight: 800;
                letter-spacing: 0.3px;
            }
            .badge-active    { background: #dcfce7; color: #15803d; }
            .badge-suspended { background: #fee2e2; color: #b91c1c; }

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
            .ifs-action-pill.ledger { background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; font-weight: 700; }
            .ifs-action-pill.ledger:hover { background: #dbeafe; color: #1d4ed8; }
            .ifs-action-pill.edit { background: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; }
            .ifs-action-pill.edit:hover { background: #e2e8f0; color: #0f172a; }
            .ifs-action-pill.delete { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; padding: 5px 8px; }
            .ifs-action-pill.delete:hover { background: #fee2e2; color: #b91c1c; }
            .ifs-action-pill .dashicons { font-size: 13px; width: 13px; height: 13px; margin-top: 1px; }

            .ifs-empty-table { text-align: center; padding: 50px 20px !important; }
            .ifs-empty-state .dashicons { font-size: 44px; width: 44px; height: 44px; color: #cbd5e1; margin-bottom: 10px; }
            .ifs-empty-state h4 { margin: 0 0 4px 0; font-size: 16px; font-weight: 700; color: #334155; }
            .ifs-empty-state p { margin: 0; color: #94a3b8; font-size: 13px; }

            .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
            .font-bold { font-weight: 700; }
            .color-emerald { color: #059669 !important; }
            .color-rose { color: #e11d48 !important; }
            .color-slate { color: #475569 !important; }

            /* DataTables Controls Integration */
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

        <!-- DataTables Engine Script -->
        <script>
        jQuery(document).ready(function($) {
            if ($.fn.DataTable && $('#ifsAgentTable tbody tr td').length > 1) {
                var table = $('#ifsAgentTable').DataTable({
                    "pageLength": 15,
                    "ordering": true,
                    "info": true,
                    "searching": true,
                    "lengthChange": false,
                    "order": [[ 0, "desc" ]],
                    "language": {
                        "info": "Showing _START_ to _END_ of _TOTAL_ partner agencies",
                        "infoEmpty": "Showing 0 to 0 of 0 records",
                        "infoFiltered": "(filtered from _MAX_ total records)",
                        "paginate": {
                            "previous": "&larr; Prev",
                            "next": "Next &rarr;"
                        }
                    }
                });

                // Bind Custom Per Page Selector
                $('#ifsAgentPerPageSelect').on('change', function() {
                    var lengthVal = parseInt($(this).val());
                    table.page.len(lengthVal).draw();
                });

                // Bind Custom Live Search Box
                $('#ifsAgentSearchInput').on('keyup', function() {
                    table.search(this.value).draw();
                });
            }
        });
        </script>
        <?php
    }
}