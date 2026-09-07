<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_invoice_list_page' ) ) {
    /**
     * Sales Invoices & Billing Register
     */
    function ifs_terp_invoice_list_page() {
        global $wpdb;
        $table_invoices  = $wpdb->prefix . 'iterp_invoices';
        $table_customers = $wpdb->prefix . 'iterp_customers';
        $table_agents    = $wpdb->prefix . 'iterp_agents';
        $base_url        = admin_url( 'admin.php?page=ifs_travel_erp&tab=accounts' );

        $sub_action = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : '';

        // Handle Delete Action with Nonce Verification
        if ( 'delete' === $sub_action && isset( $_GET['id'] ) ) {
            $del_id = absint( wp_unslash( $_GET['id'] ) );
            check_admin_referer( 'delete_invoice_' . $del_id );

            $inv_no = $wpdb->get_var( $wpdb->prepare( "SELECT invoice_no FROM {$table_invoices} WHERE id = %d", $del_id ) );
            $wpdb->delete( $table_invoices, array( 'id' => $del_id ), array( '%d' ) );

            if ( function_exists( 'ifs_terp_log_activity' ) ) {
                ifs_terp_log_activity( "Deleted Sales Invoice #{$inv_no} (Record ID #{$del_id})" );
            }

            $redirect_url = add_query_arg(
                array(
                    'page' => 'ifs_travel_erp',
                    'tab'  => 'accounts',
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
            $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Invoice record removed successfully.', 'ifs-travel-erp' ) . '</div>';
        }

        // Optimized Single-Query with Conditional Joins
        $sql_invoices = "
            SELECT 
                i.*,
                CASE 
                    WHEN i.client_type = 'Customer' THEN c.full_name 
                    WHEN i.client_type = 'Agent' THEN a.agency_name 
                    ELSE 'Direct Client' 
                END AS client_name,
                CASE 
                    WHEN i.client_type = 'Customer' THEN c.mobile 
                    WHEN i.client_type = 'Agent' THEN a.mobile 
                    ELSE '' 
                END AS client_phone
            FROM {$table_invoices} i
            LEFT JOIN {$table_customers} c ON (i.client_type = 'Customer' AND i.client_id = c.id)
            LEFT JOIN {$table_agents} a ON (i.client_type = 'Agent' AND i.client_id = a.id)
            ORDER BY i.id DESC
        ";
        $invoices = $wpdb->get_results( $sql_invoices );
        ?>

        <div class="wrap ifs-invoice-list-workspace">
            <?php echo wp_kses_post( $message ); ?>

            <!-- Master Data Table Card -->
            <div class="ifs-table-card">
                <!-- Custom DataTables Controls Toolbar -->
                <div class="ifs-custom-table-controls">
                    <div class="ifs-per-page-wrap">
                        <label for="ifsInvoicePerPageSelect"><?php esc_html_e( 'Show', 'ifs-travel-erp' ); ?></label>
                        <select id="ifsInvoicePerPageSelect" class="ifs-select-control">
                            <option value="10">10</option>
                            <option value="15" selected>15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span><?php esc_html_e( 'entries', 'ifs-travel-erp' ); ?></span>
                    </div>
                    <div class="ifs-live-search-wrap">
                        <label for="ifsInvoiceSearchInput"><span class="dashicons dashicons-search"></span></label>
                        <input type="text" id="ifsInvoiceSearchInput" class="ifs-search-input" placeholder="<?php esc_attr_e( 'Search by invoice no, client name, phone, status...', 'ifs-travel-erp' ); ?>">
                    </div>
                </div>

                <div class="ifs-table-responsive-wrapper">
                    <table class="ifs-pro-datatable" id="ifsInvoiceTable">
                        <thead>
                            <tr>
                                <th style="width: 130px;"><?php esc_html_e( 'Invoice No', 'ifs-travel-erp' ); ?></th>
                                <th style="width: 100px;"><?php esc_html_e( 'Client Type', 'ifs-travel-erp' ); ?></th>
                                <th><?php esc_html_e( 'Billed Recipient Details', 'ifs-travel-erp' ); ?></th>
                                <th style="text-align: center; width: 100px;"><?php esc_html_e( 'Status', 'ifs-travel-erp' ); ?></th>
                                <th><?php esc_html_e( 'Date Issued', 'ifs-travel-erp' ); ?></th>
                                <th style="text-align: right; width: 220px;" data-sortable="false"><?php esc_html_e( 'Actions', 'ifs-travel-erp' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( ! empty( $invoices ) ) : foreach ( $invoices as $inv ) : 
                                $status_lower = strtolower( (string) $inv->payment_status );
                                $status_class = 'badge-unpaid';
                                if ( 'paid' === $status_lower ) {
                                    $status_class = 'badge-paid';
                                } elseif ( 'partial' === $status_lower ) {
                                    $status_class = 'badge-partial';
                                }

                                $type_badge = ( 'Agent' === $inv->client_type ) ? 'type-agent' : 'type-customer';
                            ?>
                                <tr>
                                    <td>
                                        <span class="ifs-invoice-num font-mono"><?php echo esc_html( $inv->invoice_no ); ?></span>
                                    </td>
                                    <td>
                                        <span class="ifs-type-pill <?php echo esc_attr( $type_badge ); ?>">
                                            <?php echo esc_html( $inv->client_type ); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="ifs-client-cell">
                                            <strong class="client-name"><?php echo esc_html( $inv->client_name ?: __( 'Direct Client', 'ifs-travel-erp' ) ); ?></strong>
                                            <?php if ( ! empty( $inv->client_phone ) ) : ?>
                                                <span class="client-phone font-mono"><span class="dashicons dashicons-phone"></span> <?php echo esc_html( $inv->client_phone ); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="ifs-status-badge <?php echo esc_attr( $status_class ); ?>">
                                            <?php echo esc_html( strtoupper( $inv->payment_status ) ); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="font-mono text-date"><?php echo esc_html( gmdate( 'd M Y', strtotime( $inv->created_at ) ) ); ?></span>
                                    </td>
                                    <td style="text-align: right;">
                                        <div class="ifs-action-pills">
                                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=accounts&sub=view_invoice&id=' . $inv->id ) ); ?>" class="ifs-action-pill view" title="<?php esc_attr_e( 'View & Print Dossier', 'ifs-travel-erp' ); ?>">
                                                <span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'View', 'ifs-travel-erp' ); ?>
                                            </a>
                                            <a href="<?php echo esc_url( wp_nonce_url( $base_url . '&sub=delete&id=' . $inv->id, 'delete_invoice_' . $inv->id ) ); ?>" 
                                               class="ifs-action-pill delete" 
                                               onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to permanently delete this invoice? This action cannot be undone.', 'ifs-travel-erp' ) ); ?>');" 
                                               title="<?php esc_attr_e( 'Delete Invoice', 'ifs-travel-erp' ); ?>">
                                                <span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Delete', 'ifs-travel-erp' ); ?>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; else : ?>
                                <tr>
                                    <td colspan="6" class="ifs-empty-table">
                                        <div class="ifs-empty-state">
                                            <span class="dashicons dashicons-media-spreadsheet"></span>
                                            <h4><?php esc_html_e( 'No Sales Invoices Generated', 'ifs-travel-erp' ); ?></h4>
                                            <p><?php esc_html_e( 'No client invoices are currently registered in the system.', 'ifs-travel-erp' ); ?></p>
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
            .ifs-invoice-list-workspace {
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

            .ifs-invoice-num {
                color: #003376;
                font-weight: 800;
                font-size: 13px;
            }

            .ifs-type-pill {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 4px;
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 0.3px;
                text-transform: uppercase;
            }
            .type-customer { background: #eff6ff; color: #1d4ed8; }
            .type-agent    { background: #fdf4ff; color: #a21caf; }

            .ifs-client-cell { display: flex; flex-direction: column; gap: 2px; }
            .client-name { font-size: 13.5px; color: #0f172a; }
            .client-phone { font-size: 11px; color: #64748b; display: flex; align-items: center; gap: 3px; }
            .client-phone .dashicons { font-size: 12px; width: 12px; height: 12px; color: #0284c7; }

            .ifs-status-badge {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 6px;
                font-size: 10.5px;
                font-weight: 800;
                letter-spacing: 0.3px;
            }
            .badge-paid    { background: #dcfce7; color: #15803d; }
            .badge-partial { background: #fef3c7; color: #b45309; }
            .badge-unpaid  { background: #fee2e2; color: #b91c1c; }

            .text-date { font-size: 12px; color: #64748b; }

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
            .ifs-action-pill.view { background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; }
            .ifs-action-pill.view:hover { background: #dbeafe; color: #1d4ed8; }
            .ifs-action-pill.delete { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; padding: 5px 8px; }
            .ifs-action-pill.delete:hover { background: #fee2e2; color: #b91c1c; }
            .ifs-action-pill .dashicons { font-size: 13px; width: 13px; height: 13px; margin-top: 1px; }

            .ifs-empty-table { text-align: center; padding: 50px 20px !important; }
            .ifs-empty-state .dashicons { font-size: 44px; width: 44px; height: 44px; color: #cbd5e1; margin-bottom: 10px; }
            .ifs-empty-state h4 { margin: 0 0 4px 0; font-size: 16px; font-weight: 700; color: #334155; }
            .ifs-empty-state p { margin: 0; color: #94a3b8; font-size: 13px; }

            .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
            .font-bold { font-weight: 700; }

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
            if ($.fn.DataTable && $('#ifsInvoiceTable tbody tr td').length > 1) {
                var table = $('#ifsInvoiceTable').DataTable({
                    "pageLength": 15,
                    "ordering": true,
                    "info": true,
                    "searching": true,
                    "lengthChange": false,
                    "order": [[ 0, "desc" ]],
                    "language": {
                        "info": "Showing _START_ to _END_ of _TOTAL_ billing records",
                        "infoEmpty": "Showing 0 to 0 of 0 records",
                        "infoFiltered": "(filtered from _MAX_ total records)",
                        "paginate": {
                            "previous": "&larr; Prev",
                            "next": "Next &rarr;"
                        }
                    }
                });

                // Bind Custom Per Page Selector
                $('#ifsInvoicePerPageSelect').on('change', function() {
                    var lengthVal = parseInt($(this).val());
                    table.page.len(lengthVal).draw();
                });

                // Bind Custom Live Search Box
                $('#ifsInvoiceSearchInput').on('keyup', function() {
                    table.search(this.value).draw();
                });
            }
        });
        </script>
        <?php
    }
}