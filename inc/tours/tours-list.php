<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_tours_list_page' ) ) {
    /**
     * All Tour Bookings List View
     */
    function ifs_terp_tours_list_page() {
        global $wpdb;
        $table_tours     = $wpdb->prefix . 'iterp_tours';
        $table_customers = $wpdb->prefix . 'iterp_customers';
        $table_agents    = $wpdb->prefix . 'iterp_agents';
        $base_url        = admin_url( 'admin.php?page=ifs_travel_erp&tab=tours' );

        $msg = isset( $_GET['msg'] ) ? sanitize_key( wp_unslash( $_GET['msg'] ) ) : '';
        $message = '';
        if ( 'deleted' === $msg ) {
            $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Tour booking removed successfully.', 'ifs-travel-erp' ) . '</div>';
        }

        $bookings = $wpdb->get_results( "
            SELECT t.*, c.full_name, c.mobile, a.agency_name 
            FROM {$table_tours} t 
            LEFT JOIN {$table_customers} c ON t.customer_id = c.id 
            LEFT JOIN {$table_agents} a ON t.agent_id = a.id
            ORDER BY t.id DESC
        " );
        ?>
        <div class="wrap ifs-tour-list-workspace">
            <?php echo wp_kses_post( $message ); ?>

            <div class="ifs-table-card">
                <div class="ifs-custom-table-controls">
                    <div class="ifs-per-page-wrap">
                        <label for="ifsToursPerPageSelect"><?php esc_html_e( 'Show', 'ifs-travel-erp' ); ?></label>
                        <select id="ifsToursPerPageSelect" class="ifs-select-control">
                            <option value="10">10</option>
                            <option value="15" selected>15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span><?php esc_html_e( 'entries', 'ifs-travel-erp' ); ?></span>
                    </div>
                    <div class="ifs-live-search-wrap">
                        <label for="ifsToursSearchInput"><span class="dashicons dashicons-search"></span></label>
                        <input type="text" id="ifsToursSearchInput" class="ifs-search-input" placeholder="<?php esc_attr_e( 'Search client, package, destination...', 'ifs-travel-erp' ); ?>">
                    </div>
                </div>

                <div class="table-scroll">
                    <table class="ifs-clean-table" id="ifsToursTable">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Tour ID', 'ifs-travel-erp' ); ?></th>
                                <th><?php esc_html_e( 'Customer', 'ifs-travel-erp' ); ?></th>
                                <th><?php esc_html_e( 'Package & Destination', 'ifs-travel-erp' ); ?></th>
                                <th><?php esc_html_e( 'Travel Date', 'ifs-travel-erp' ); ?></th>
                                <th style="text-align: center;"><?php esc_html_e( 'Status', 'ifs-travel-erp' ); ?></th>
                                <th style="text-align: right; width: 220px;" data-sortable="false"><?php esc_html_e( 'Actions', 'ifs-travel-erp' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( ! empty( $bookings ) ) : foreach ( $bookings as $b ) : 
                                $status_class = 'badge-pending';
                                $st = strtolower( (string) $b->status );
                                if ( 'confirmed' === $st ) {
                                    $status_class = 'badge-confirmed';
                                } elseif ( 'reserved' === $st ) {
                                    $status_class = 'badge-reserved';
                                } elseif ( 'cancelled' === $st ) {
                                    $status_class = 'badge-cancelled';
                                }
                                ?>
                                <tr>
                                    <td><span class="ifs-id-badge">#TR-<?php echo esc_html( str_pad( (string) $b->id, 5, '0', STR_PAD_LEFT ) ); ?></span></td>
                                    <td>
                                        <div class="tbl-main"><?php echo esc_html( $b->full_name ?: __( 'Direct Client', 'ifs-travel-erp' ) ); ?></div>
                                        <div class="tbl-sub"><?php echo esc_html( $b->agency_name ?: $b->mobile ?: '—' ); ?></div>
                                    </td>
                                    <td>
                                        <div class="tbl-main"><?php echo esc_html( $b->package_title ); ?></div>
                                        <div class="tbl-sub font-bold" style="color: #0284c7;"><?php echo esc_html( $b->destination . ' (' . $b->duration . ')' ); ?></div>
                                    </td>
                                    <td><?php echo esc_html( date_i18n( 'd M Y', strtotime( $b->travel_date ) ) ); ?></td>
                                    <td style="text-align: center;">
                                        <span class="badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $b->status ); ?></span>
                                    </td>
                                    <td style="text-align: right;">
                                        <div class="ifs-action-pills">
                                            <a href="<?php echo esc_url( add_query_arg( array( 'sub' => 'view', 'id' => $b->id ), $base_url ) ); ?>" class="ifs-action-pill view" title="<?php esc_attr_e( 'View Booking Voucher', 'ifs-travel-erp' ); ?>">
                                                <span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'View', 'ifs-travel-erp' ); ?>
                                            </a>
                                            <a href="<?php echo esc_url( add_query_arg( array( 'sub' => 'edit', 'id' => $b->id ), $base_url ) ); ?>" class="ifs-action-pill edit" title="<?php esc_attr_e( 'Edit Booking', 'ifs-travel-erp' ); ?>">
                                                <span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Edit', 'ifs-travel-erp' ); ?>
                                            </a>
                                            <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'sub' => 'delete', 'id' => $b->id ), $base_url ), 'delete_tour_' . $b->id ) ); ?>" class="ifs-action-pill delete" onclick="return confirm('<?php echo esc_js( __( 'Delete this tour booking?', 'ifs-travel-erp' ) ); ?>');" title="<?php esc_attr_e( 'Delete Booking', 'ifs-travel-erp' ); ?>">
                                                <span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Delete', 'ifs-travel-erp' ); ?>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; else : ?>
                                <tr><td colspan="6" class="no-records"><?php esc_html_e( 'No tour bookings recorded yet.', 'ifs-travel-erp' ); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <style>
            .ifs-tour-list-workspace { max-width: 1380px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; }
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
            .tbl-main { font-weight: 700; color: #0f172a; }
            .tbl-sub { font-size: 11px; color: #64748b; margin-top: 2px; }
            .ifs-id-badge { background: #f1f5f9; color: #475569; font-family: monospace; font-weight: 700; font-size: 11px; padding: 2px 6px; border-radius: 4px; }
            .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; }
            .badge-reserved   { background: #e0f2fe; color: #0369a1; }
            .badge-confirmed  { background: #dcfce7; color: #15803d; }
            .badge-pending    { background: #fef3c7; color: #b45309; }
            .badge-cancelled  { background: #fee2e2; color: #b91c1c; }
            .no-records { text-align: center; padding: 30px !important; color: #94a3b8; font-style: italic; }
            .ifs-action-pills { display: flex; gap: 4px; justify-content: flex-end; align-items: center; }
            .ifs-action-pill { 
                display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 5px; font-size: 11.5px; font-weight: 600; text-decoration: none; white-space: nowrap; border: 1px solid transparent; 
            }
            .ifs-action-pill .dashicons { font-size: 13px; width: 13px; height: 13px; }
            .ifs-action-pill.view { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }
            .ifs-action-pill.view:hover { background: #e2e8f0; color: #0f172a; }
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
            .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
            .font-bold { font-weight: 700; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            if ($.fn.DataTable && $('#ifsToursTable tbody tr td').length > 1) {
                var tourTable = $('#ifsToursTable').DataTable({
                    "pageLength": 15, "ordering": true, "info": true, "searching": true, "lengthChange": false, "order": [[ 0, "desc" ]],
                    "language": {
                        "info": "Showing _START_ to _END_ of _TOTAL_ bookings",
                        "infoEmpty": "Showing 0 to 0 of 0 bookings",
                        "infoFiltered": "(filtered from _MAX_ total)",
                        "paginate": { "previous": "&larr;", "next": "&rarr;" }
                    }
                });
                $('#ifsToursPerPageSelect').on('change', function() { tourTable.page.len(parseInt($(this).val())).draw(); });
                $('#ifsToursSearchInput').on('keyup', function() { tourTable.search(this.value).draw(); });
            }
        });
        </script>
        <?php
    }
}