<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_suppliers_ledger_page' ) ) {
    /**
     * Supplier Transaction Ledger Table View
     */
    function ifs_terp_suppliers_ledger_page() {
        global $wpdb;
        $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
        $table_ledger    = $wpdb->prefix . 'iterp_supplier_ledger';

        $ledger = $wpdb->get_results( "
            SELECT l.*, s.supplier_name, s.supplier_type 
            FROM {$table_ledger} l 
            LEFT JOIN {$table_suppliers} s ON l.supplier_id = s.id 
            ORDER BY l.id DESC LIMIT 200
        " );
        ?>
        <div class="wrap ifs-suppliers-ledger-workspace">
            <div class="ifs-table-card">
                <div class="ifs-custom-table-controls">
                    <div class="ifs-per-page-wrap">
                        <label for="ifsSuppLedgerPerPageSelect"><?php esc_html_e( 'Show', 'ifs-travel-erp' ); ?></label>
                        <select id="ifsSuppLedgerPerPageSelect" class="ifs-select-control">
                            <option value="10">10</option>
                            <option value="15" selected>15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span><?php esc_html_e( 'entries', 'ifs-travel-erp' ); ?></span>
                    </div>
                    <div class="ifs-live-search-wrap">
                        <label for="ifsSuppLedgerSearchInput"><span class="dashicons dashicons-search"></span></label>
                        <input type="text" id="ifsSuppLedgerSearchInput" class="ifs-search-input" placeholder="<?php esc_attr_e( 'Search supplier, reference, note...', 'ifs-travel-erp' ); ?>">
                    </div>
                </div>

                <div class="table-scroll">
                    <table class="ifs-clean-table" id="ifsSuppLedgerTable">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Trx ID', 'ifs-travel-erp' ); ?></th>
                                <th><?php esc_html_e( 'Date', 'ifs-travel-erp' ); ?></th>
                                <th><?php esc_html_e( 'Supplier', 'ifs-travel-erp' ); ?></th>
                                <th><?php esc_html_e( 'Type', 'ifs-travel-erp' ); ?></th>
                                <th style="text-align: right;"><?php esc_html_e( 'Debit (-)', 'ifs-travel-erp' ); ?></th>
                                <th style="text-align: right;"><?php esc_html_e( 'Credit (+)', 'ifs-travel-erp' ); ?></th>
                                <th style="text-align: right;"><?php esc_html_e( 'Balance After', 'ifs-travel-erp' ); ?></th>
                                <th><?php esc_html_e( 'Note', 'ifs-travel-erp' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( ! empty( $ledger ) ) : foreach ( $ledger as $row ) : ?>
                                <tr>
                                    <td><span class="ifs-id-badge">#TXN-<?php echo esc_html( str_pad( (string) $row->id, 5, '0', STR_PAD_LEFT ) ); ?></span></td>
                                    <td><?php echo esc_html( date_i18n( 'd M Y, h:i A', strtotime( $row->created_at ) ) ); ?></td>
                                    <td>
                                        <strong style="color:#003376;"><?php echo esc_html( $row->supplier_name ?: __( 'Direct Supplier', 'ifs-travel-erp' ) ); ?></strong>
                                        <div style="font-size:11px; color:#64748b;"><?php echo esc_html( $row->supplier_type ?: __( 'B2B Vendor', 'ifs-travel-erp' ) ); ?></div>
                                    </td>
                                    <td><span class="badge" style="background:#f1f5f9; color:#475569;"><?php echo esc_html( $row->reference_type ); ?></span></td>
                                    <td style="text-align: right; font-family: monospace; font-weight: 700; color: #dc2626;">
                                        <?php echo ( (float) $row->debit > 0 ) ? '-৳' . esc_html( number_format( (float) $row->debit, 2 ) ) : '—'; ?>
                                    </td>
                                    <td style="text-align: right; font-family: monospace; font-weight: 700; color: #059669;">
                                        <?php echo ( (float) $row->credit > 0 ) ? '+৳' . esc_html( number_format( (float) $row->credit, 2 ) ) : '—'; ?>
                                    </td>
                                    <td style="text-align: right; font-family: monospace; font-weight: 700; color: #0f172a;">
                                        ৳<?php echo esc_html( number_format( (float) $row->balance_after, 2 ) ); ?>
                                    </td>
                                    <td style="font-size: 12px; color: #475569; max-width: 250px;"><?php echo esc_html( $row->note ?: '—' ); ?></td>
                                </tr>
                            <?php endforeach; else : ?>
                                <tr><td colspan="8" class="no-records"><?php esc_html_e( 'No transactions recorded yet.', 'ifs-travel-erp' ); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <style>
            .ifs-suppliers-ledger-workspace { max-width: 1380px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; }
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
            .no-records { text-align: center; padding: 30px !important; color: #94a3b8; font-style: italic; }
            .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter { display: none !important; }
            .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { margin-top: 14px; font-size: 12px; color: #64748b; }
            .dataTables_wrapper .dataTables_paginate .paginate_button {
                background: #ffffff !important; border: 1px solid #cbd5e1 !important; border-radius: 5px !important; color: #334155 !important; padding: 4px 10px !important; margin-left: 3px; font-weight: 600;
            }
            .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: #003376 !important; color: #ffffff !important; border-color: #003376 !important; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            if ($.fn.DataTable && $('#ifsSuppLedgerTable tbody tr td').length > 1) {
                var suppLedgerTable = $('#ifsSuppLedgerTable').DataTable({
                    "pageLength": 15, "ordering": true, "info": true, "searching": true, "lengthChange": false, "order": [[ 0, "desc" ]],
                    "language": {
                        "info": "Showing _START_ to _END_ of _TOTAL_ transactions",
                        "infoEmpty": "Showing 0 to 0 of 0 transactions",
                        "infoFiltered": "(filtered from _MAX_ total)",
                        "paginate": { "previous": "&larr;", "next": "&rarr;" }
                    }
                });
                $('#ifsSuppLedgerPerPageSelect').on('change', function() { suppLedgerTable.page.len(parseInt($(this).val())).draw(); });
                $('#ifsSuppLedgerSearchInput').on('keyup', function() { suppLedgerTable.search(this.value).draw(); });
            }
        });
        </script>
        <?php
    }
}