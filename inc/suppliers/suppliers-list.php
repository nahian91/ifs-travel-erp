<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_suppliers_list_page' ) ) {
    /**
     * All Suppliers Directory List View
     */
    function ifs_terp_suppliers_list_page() {
        global $wpdb;
        $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
        $base_url        = admin_url( 'admin.php?page=ifs_travel_erp&tab=suppliers' );

        $msg = isset( $_GET['msg'] ) ? sanitize_key( wp_unslash( $_GET['msg'] ) ) : '';
        $message = '';
        if ( 'deleted' === $msg ) {
            $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Supplier record deleted successfully.', 'ifs-travel-erp' ) . '</div>';
        }

        $suppliers = $wpdb->get_results( "SELECT * FROM {$table_suppliers} ORDER BY id DESC" );
        ?>
        <div class="wrap ifs-suppliers-list-workspace">
            <?php echo wp_kses_post( $message ); ?>

            <div class="ifs-table-card">
                <div class="ifs-custom-table-controls">
                    <div class="ifs-per-page-wrap">
                        <label for="ifsSuppliersPerPageSelect"><?php esc_html_e( 'Show', 'ifs-travel-erp' ); ?></label>
                        <select id="ifsSuppliersPerPageSelect" class="ifs-select-control">
                            <option value="10">10</option>
                            <option value="15" selected>15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span><?php esc_html_e( 'entries', 'ifs-travel-erp' ); ?></span>
                    </div>
                    <div class="ifs-live-search-wrap">
                        <label for="ifsSuppliersSearchInput"><span class="dashicons dashicons-search"></span></label>
                        <input type="text" id="ifsSuppliersSearchInput" class="ifs-search-input" placeholder="<?php esc_attr_e( 'Search suppliers, category, phone...', 'ifs-travel-erp' ); ?>">
                    </div>
                </div>

                <div class="table-scroll">
                    <table class="ifs-clean-table" id="ifsSuppliersTable">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'ID', 'ifs-travel-erp' ); ?></th>
                                <th><?php esc_html_e( 'Supplier Name', 'ifs-travel-erp' ); ?></th>
                                <th><?php esc_html_e( 'Category', 'ifs-travel-erp' ); ?></th>
                                <th><?php esc_html_e( 'Contact Person', 'ifs-travel-erp' ); ?></th>
                                <th><?php esc_html_e( 'Phone & Email', 'ifs-travel-erp' ); ?></th>
                                <th style="text-align: right;"><?php esc_html_e( 'Deposit Balance', 'ifs-travel-erp' ); ?></th>
                                <th style="text-align: center;"><?php esc_html_e( 'Status', 'ifs-travel-erp' ); ?></th>
                                <th style="text-align: right; width: 170px;" data-sortable="false"><?php esc_html_e( 'Actions', 'ifs-travel-erp' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( ! empty( $suppliers ) ) : foreach ( $suppliers as $row ) : 
                                $status_class = ( strtolower( (string) $row->status ) === 'active' ) ? 'badge-confirmed' : 'badge-cancelled';
                                ?>
                                <tr>
                                    <td><span class="ifs-id-badge">#SUP-<?php echo esc_html( str_pad( (string) $row->id, 5, '0', STR_PAD_LEFT ) ); ?></span></td>
                                    <td><strong><?php echo esc_html( $row->supplier_name ); ?></strong></td>
                                    <td><span class="badge" style="background: #e0f2fe; color: #0369a1;"><?php echo esc_html( $row->supplier_type ); ?></span></td>
                                    <td><?php echo esc_html( $row->contact_person ?: '—' ); ?></td>
                                    <td>
                                        <div><span class="dashicons dashicons-phone" style="font-size:12px; vertical-align:middle; color:#64748b;"></span> <?php echo esc_html( $row->phone ?: '—' ); ?></div>
                                        <?php if ( ! empty( $row->email ) ) : ?>
                                            <div style="font-size:11px; color:#64748b;"><span class="dashicons dashicons-email" style="font-size:12px; vertical-align:middle;"></span> <?php echo esc_html( $row->email ); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right; font-weight: 700; color: #059669; font-family: monospace; font-size: 13.5px;">
                                        ৳<?php echo esc_html( number_format( (float) $row->current_balance, 2 ) ); ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $row->status ); ?></span>
                                    </td>
                                    <td style="text-align: right;">
                                        <div class="ifs-action-pills">
                                            <a href="<?php echo esc_url( add_query_arg( array( 'sub' => 'edit', 'id' => $row->id ), $base_url ) ); ?>" class="ifs-action-pill edit" title="<?php esc_attr_e( 'Edit Supplier', 'ifs-travel-erp' ); ?>">
                                                <span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Edit', 'ifs-travel-erp' ); ?>
                                            </a>
                                            <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'sub' => 'delete', 'id' => $row->id ), $base_url ), 'delete_supplier_' . $row->id ) ); ?>" 
                                               class="ifs-action-pill delete" 
                                               onclick="return confirm('<?php echo esc_js( __( 'Delete this supplier record?', 'ifs-travel-erp' ) ); ?>');" 
                                               title="<?php esc_attr_e( 'Delete Supplier', 'ifs-travel-erp' ); ?>">
                                                <span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Delete', 'ifs-travel-erp' ); ?>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; else : ?>
                                <tr><td colspan="8" class="no-records"><?php esc_html_e( 'No suppliers registered yet.', 'ifs-travel-erp' ); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <style>
            .ifs-suppliers-list-workspace { max-width: 1380px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; }
            .ifs-table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 20px; overflow: hidden; }
            .ifs-custom-table-controls { padding: 16px 22px; background: #f8fafc; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; }
            .ifs-per-page-wrap { display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #475569; font-weight: 600; }
            .ifs-select-control {
                border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 24px 4px 8px; font-size: 12.5px; color: #0f172a; background: #ffffff; outline: none; cursor: pointer; appearance: none;
                background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
                background-repeat: no-repeat; background-position: right 6px center; background-size: 11px;
            }
            .ifs-select-control:focus { border-color: #003376; box-shadow: 0 0 0 2px rgba(0, 51, 118, 0.12); }
            .ifs-live-search-wrap { position: relative; display: flex; align-items: center; min-width: 260px; }
            .ifs-live-search-wrap label { position: absolute; left: 10px; color: #94a3b8; display: flex; align-items: center; pointer-events: none; }
            .ifs-live-search-wrap label .dashicons { font-size: 15px; width: 15px; height: 15px; }
            .ifs-search-input { width: 100%; padding: 6px 10px 6px 32px !important; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12.5px; color: #0f172a; background: #ffffff; outline: none; }
            .ifs-search-input:focus { border-color: #003376; box-shadow: 0 0 0 2px rgba(0, 51, 118, 0.12); }
            .table-scroll { padding: 12px 22px 20px; overflow-x: auto; }
            .ifs-clean-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
            .ifs-clean-table th { background: #f8fafc; color: #475569; font-weight: 700; text-transform: uppercase; font-size: 10.5px; padding: 10px 14px; border-bottom: 1px solid #e2e8f0; text-align: left; white-space: nowrap; }
            .ifs-clean-table td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #334155; }
            .ifs-clean-table tr:hover td { background: #f8fafc; }
            .ifs-id-badge { background: #f1f5f9; color: #475569; font-family: monospace; font-weight: 700; font-size: 11px; padding: 2px 6px; border-radius: 4px; }
            .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; }
            .badge-confirmed { background: #dcfce7; color: #15803d; }
            .badge-cancelled { background: #fee2e2; color: #b91c1c; }
            .no-records { text-align: center; padding: 30px !important; color: #94a3b8; font-style: italic; }
            .ifs-action-pills { display: flex; gap: 4px; justify-content: flex-end; align-items: center; }
            .ifs-action-pill { 
                display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 5px; font-size: 11.5px; font-weight: 600; text-decoration: none; white-space: nowrap; border: 1px solid transparent; 
            }
            .ifs-action-pill .dashicons { font-size: 13px; width: 13px; height: 13px; }
            .ifs-action-pill.edit { background: #eff6ff; color: #2563eb; border-color: #dbeafe; }
            .ifs-action-pill.edit:hover { background: #dbeafe; color: #1d4ed8; }
            .ifs-action-pill.delete { background: #fef2f2; color: #dc2626; border-color: #fee2e2; }
            .ifs-action-pill.delete:hover { background: #fee2e2; color: #b91c1c; }
            .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter { display: none !important; }
            .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { margin-top: 14px; font-size: 12px; color: #64748b; }
            .dataTables_wrapper .dataTables_paginate .paginate_button {
                background: #ffffff !important; border: 1px solid #cbd5e1 !important; border-radius: 5px !important; color: #334155 !important; padding: 4px 10px !important; margin-left: 3px; font-weight: 600;
            }
            .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: #003376 !important; color: #ffffff !important; border-color: #003376 !important; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            if ($.fn.DataTable && $('#ifsSuppliersTable tbody tr td').length > 1) {
                var suppTable = $('#ifsSuppliersTable').DataTable({
                    "pageLength": 15, "ordering": true, "info": true, "searching": true, "lengthChange": false, "order": [[ 0, "desc" ]],
                    "language": {
                        "info": "Showing _START_ to _END_ of _TOTAL_ suppliers",
                        "infoEmpty": "Showing 0 to 0 of 0 suppliers",
                        "infoFiltered": "(filtered from _MAX_ total)",
                        "paginate": { "previous": "&larr;", "next": "&rarr;" }
                    }
                });
                $('#ifsSuppliersPerPageSelect').on('change', function() { suppTable.page.len(parseInt($(this).val())).draw(); });
                $('#ifsSuppliersSearchInput').on('keyup', function() { suppTable.search(this.value).draw(); });
            }
        });
        </script>
        <?php
    }
}