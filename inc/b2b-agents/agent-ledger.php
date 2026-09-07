<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_agent_ledger_page' ) ) {
    /**
     * Enterprise Ultra-Modern B2B Sub-Agent Dossier & Financial Statement Console
     * Features: High-End Corporate Bio-Card, Legal & Document Vault, Financial KPI Ribbon,
     * Interactive Ledger History Table, CSV Export, and Voucher Inspection Modal.
     */
    function ifs_terp_agent_ledger_page() {
        global $wpdb;
        $agent_id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;

        if ( ! $agent_id ) {
            echo '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Invalid Sub-Agent Identifier.', 'ifs-travel-erp' ) . '</div>';
            return;
        }

        $table_agents  = $wpdb->prefix . 'iterp_agents';
        $table_ledgers = $wpdb->prefix . 'iterp_agent_ledgers';

        $agent = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_agents} WHERE id = %d", $agent_id ) );

        if ( ! $agent ) {
            echo '<div class="ifs-toast danger"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Sub-agent partner record not found in system database.', 'ifs-travel-erp' ) . '</div>';
            return;
        }

        // Handle CSV Export
        $sub_action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
        if ( 'export_csv' === $sub_action ) {
            if ( ! current_user_can( 'manage_options' ) && ! ( function_exists( 'ifs_terp_has_access' ) && ifs_terp_has_access( array( 'accountant', 'admin_manager' ) ) ) ) {
                wp_die( esc_html__( 'Unauthorized export request.', 'ifs-travel-erp' ) );
            }

            $agent_filename_slug = sanitize_title( $agent->agency_name ?: 'agent-' . $agent_id );
            header( 'Content-Type: text/csv; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename=statement-' . $agent_filename_slug . '-' . gmdate( 'Y-m-d' ) . '.csv' );

            $output = fopen( 'php://output', 'w' );
            fputcsv( $output, array( 'Statement Date & Time', 'Reference Type', 'Reference ID', 'Debit (-) BDT', 'Credit (+) BDT', 'Running Balance (BDT)', 'Narration / Remarks' ) );

            $export_ledgers = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_ledgers} WHERE agent_id = %d ORDER BY id DESC", $agent_id ) );
            if ( ! empty( $export_ledgers ) ) {
                foreach ( $export_ledgers as $rec ) {
                    fputcsv( $output, array(
                        gmdate( 'd M Y, h:i A', strtotime( $rec->created_at ) ),
                        $rec->reference_type,
                        $rec->reference_id ? '#' . $rec->reference_id : 'N/A',
                        (float) $rec->debit,
                        (float) $rec->credit,
                        (float) $rec->balance_after,
                        $rec->note
                    ) );
                }
            }

            fclose( $output );
            exit;
        }

        // Optional Date Filters
        $date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
        $date_to   = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';

        $where_sql  = "WHERE agent_id = %d";
        $query_args = array( $agent_id );

        if ( ! empty( $date_from ) ) {
            $where_sql .= " AND DATE(created_at) >= %s";
            $query_args[] = $date_from;
        }
        if ( ! empty( $date_to ) ) {
            $where_sql .= " AND DATE(created_at) <= %s";
            $query_args[] = $date_to;
        }

        $ledger_records = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_ledgers} {$where_sql} ORDER BY id DESC", ...$query_args ) );
        
        // Aggregations
        $total_debits  = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(debit), 0) FROM {$table_ledgers} WHERE agent_id = %d", $agent_id ) );
        $total_credits = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(credit), 0) FROM {$table_ledgers} WHERE agent_id = %d", $agent_id ) );

        $current_balance = (float) $agent->current_balance;
        $credit_limit    = (float) $agent->credit_limit;
        $is_negative     = ( $current_balance < 0 );

        $base_url   = admin_url( 'admin.php?page=ifs_travel_erp&tab=b2b_agents&sub=ledger&id=' . $agent_id );
        $export_url = add_query_arg( array( 'action' => 'export_csv', 'date_from' => $date_from, 'date_to' => $date_to ), $base_url );
        $edit_url   = admin_url( 'admin.php?page=ifs_travel_erp&tab=b2b_agents&sub=edit&id=' . $agent_id );
        $back_url   = admin_url( 'admin.php?page=ifs_travel_erp&tab=b2b_agents' );

        // Monogram Initials
        $agency_clean = trim( (string) $agent->agency_name );
        $parts        = preg_split( '/\s+/', $agency_clean );
        $initial      = ( count( $parts ) > 1 ) ? ( mb_substr( $parts[0], 0, 1 ) . mb_substr( $parts[count($parts)-1], 0, 1 ) ) : mb_substr( $agency_clean, 0, 2 );
        $initial      = strtoupper( $initial );
        ?>

        <div class="wrap ifs-agent-dossier-workspace">
            
            <div class="ifs-view-header-strip">
                <div class="ifs-header-identity">
                    <a href="<?php echo esc_url( $back_url ); ?>" class="ifs-back-round-btn" title="<?php esc_attr_e( 'Back to Directory', 'ifs-travel-erp' ); ?>">
                        <span class="dashicons dashicons-arrow-left-alt2"></span>
                    </a>
                    <div>
                        <div class="ifs-badge-row">
                            <span class="ifs-id-pill font-mono">#AGT-<?php echo esc_html( str_pad( (string) $agent->id, 5, '0', STR_PAD_LEFT ) ); ?></span>
                            <span class="ifs-tier-pill tier-<?php echo esc_attr( sanitize_title( $agent->agency_tier ?? 'Standard Partner' ) ); ?>"><?php echo esc_html( $agent->agency_tier ?? 'Standard Partner' ); ?></span>
                            <span class="ifs-status-pill status-<?php echo esc_attr( strtolower( $agent->status ?? 'Active' ) ); ?>"><?php echo esc_html( $agent->status ?? 'Active' ); ?></span>
                        </div>
                        <h1 class="ifs-header-title"><?php echo esc_html( mb_convert_case( $agent->agency_name, MB_CASE_TITLE, 'UTF-8' ) ); ?></h1>
                    </div>
                </div>

                <div class="ifs-header-actions">
                    <button type="button" onclick="window.print();" class="ifs-btn ifs-btn-default">
                        <span class="dashicons dashicons-printer"></span> <?php esc_html_e( 'Print', 'ifs-travel-erp' ); ?>
                    </button>
                    <a href="<?php echo esc_url( $export_url ); ?>" class="ifs-btn ifs-btn-default">
                        <span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export CSV', 'ifs-travel-erp' ); ?>
                    </a>
                    <a href="<?php echo esc_url( $edit_url ); ?>" class="ifs-btn ifs-btn-primary">
                        <span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Edit Profile', 'ifs-travel-erp' ); ?>
                    </a>
                </div>
            </div>

            <section class="ifs-kpi-grid">
                <div class="ifs-kpi-card">
                    <div class="ifs-kpi-icon kpi-blue"><span class="dashicons dashicons-shield"></span></div>
                    <div class="ifs-kpi-content">
                        <span class="ifs-kpi-label"><?php esc_html_e( 'Credit Limit Cap', 'ifs-travel-erp' ); ?></span>
                        <strong class="ifs-kpi-value font-mono">৳<?php echo esc_html( number_format( $credit_limit, 2 ) ); ?></strong>
                    </div>
                </div>

                <div class="ifs-kpi-card">
                    <div class="ifs-kpi-icon <?php echo $is_negative ? 'kpi-rose' : 'kpi-emerald'; ?>">
                        <span class="dashicons dashicons-money-alt"></span>
                    </div>
                    <div class="ifs-kpi-content">
                        <span class="ifs-kpi-label"><?php esc_html_e( 'Current Balance', 'ifs-travel-erp' ); ?></span>
                        <strong class="ifs-kpi-value font-mono <?php echo $is_negative ? 'text-rose' : 'text-emerald'; ?>">
                            <?php echo $is_negative ? '-৳' : '+৳'; ?><?php echo esc_html( number_format( abs( $current_balance ), 2 ) ); ?>
                        </strong>
                    </div>
                </div>

                <div class="ifs-kpi-card">
                    <div class="ifs-kpi-icon kpi-rose"><span class="dashicons dashicons-arrow-down-alt"></span></div>
                    <div class="ifs-kpi-content">
                        <span class="ifs-kpi-label"><?php esc_html_e( 'Total Debits', 'ifs-travel-erp' ); ?></span>
                        <strong class="ifs-kpi-value font-mono text-rose">৳<?php echo esc_html( number_format( $total_debits, 2 ) ); ?></strong>
                    </div>
                </div>

                <div class="ifs-kpi-card">
                    <div class="ifs-kpi-icon kpi-emerald"><span class="dashicons dashicons-arrow-up-alt"></span></div>
                    <div class="ifs-kpi-content">
                        <span class="ifs-kpi-label"><?php esc_html_e( 'Total Credits', 'ifs-travel-erp' ); ?></span>
                        <strong class="ifs-kpi-value font-mono text-emerald">৳<?php echo esc_html( number_format( $total_credits, 2 ) ); ?></strong>
                    </div>
                </div>
            </section>

            <main class="ifs-dossier-grid">
                
                <aside class="ifs-sidebar-pane">
                    <div class="ifs-card">
                        <div class="ifs-card-header">
                            <span class="dashicons dashicons-building"></span>
                            <h3><?php esc_html_e( 'Corporate Profile', 'ifs-travel-erp' ); ?></h3>
                        </div>

                        <div class="ifs-spec-group">
                            <span class="spec-group-title"><?php esc_html_e( 'Management & Contact', 'ifs-travel-erp' ); ?></span>
                            <div class="ifs-spec-row">
                                <span class="spec-label"><span class="dashicons dashicons-businessman"></span> <?php esc_html_e( 'Focal Person', 'ifs-travel-erp' ); ?></span>
                                <span class="spec-val"><?php echo esc_html( mb_convert_case( $agent->contact_person, MB_CASE_TITLE, 'UTF-8' ) ); ?> (<?php echo esc_html( $agent->designation ?: 'Proprietor' ); ?>)</span>
                            </div>
                            <div class="ifs-spec-row">
                                <span class="spec-label"><span class="dashicons dashicons-phone"></span> <?php esc_html_e( 'Mobile', 'ifs-travel-erp' ); ?></span>
                                <span class="spec-val"><a href="tel:<?php echo esc_attr( $agent->mobile ); ?>" class="font-mono"><?php echo esc_html( $agent->mobile ); ?></a></span>
                            </div>
                            <?php if ( ! empty( $agent->whatsapp_no ) ) : ?>
                                <div class="ifs-spec-row">
                                    <span class="spec-label"><span class="dashicons dashicons-format-chat"></span> <?php esc_html_e( 'WhatsApp', 'ifs-travel-erp' ); ?></span>
                                    <span class="spec-val"><a href="https://wa.me/<?php echo esc_attr( preg_replace( '/[^0-9]/', '', $agent->whatsapp_no ) ); ?>" target="_blank" rel="noopener" class="font-mono text-emerald"><?php echo esc_html( $agent->whatsapp_no ); ?></a></span>
                                </div>
                            <?php endif; ?>
                            <?php if ( ! empty( $agent->accounts_mobile ) ) : ?>
                                <div class="ifs-spec-row">
                                    <span class="spec-label"><span class="dashicons dashicons-money-alt"></span> <?php esc_html_e( 'Accounts Desk', 'ifs-travel-erp' ); ?></span>
                                    <span class="spec-val"><a href="tel:<?php echo esc_attr( $agent->accounts_mobile ); ?>" class="font-mono"><?php echo esc_html( $agent->accounts_mobile ); ?></a></span>
                                </div>
                            <?php endif; ?>
                            <div class="ifs-spec-row">
                                <span class="spec-label"><span class="dashicons dashicons-email"></span> <?php esc_html_e( 'Email', 'ifs-travel-erp' ); ?></span>
                                <span class="spec-val"><?php echo esc_html( $agent->email ?: '—' ); ?></span>
                            </div>
                        </div>

                        <div class="ifs-spec-group">
                            <span class="spec-group-title"><?php esc_html_e( 'Legal & Verification', 'ifs-travel-erp' ); ?></span>
                            <div class="ifs-spec-row">
                                <span class="spec-label"><span class="dashicons dashicons-media-text"></span> <?php esc_html_e( 'Trade License', 'ifs-travel-erp' ); ?></span>
                                <span class="spec-val font-mono"><?php echo esc_html( $agent->trade_license_no ?: 'N/A' ); ?></span>
                            </div>
                            <div class="ifs-spec-row">
                                <span class="spec-label"><span class="dashicons dashicons-id-alt"></span> <?php esc_html_e( 'Owner NID', 'ifs-travel-erp' ); ?></span>
                                <span class="spec-val font-mono"><?php echo esc_html( $agent->owner_nid ?: 'N/A' ); ?></span>
                            </div>
                            <div class="ifs-spec-row">
                                <span class="spec-label"><span class="dashicons dashicons-text-page"></span> <?php esc_html_e( 'Security Cheque', 'ifs-travel-erp' ); ?></span>
                                <span class="spec-val font-mono"><?php echo esc_html( $agent->security_cheque ?: 'None Provided' ); ?></span>
                            </div>
                            <div class="ifs-spec-row">
                                <span class="spec-label"><span class="dashicons dashicons-calculator"></span> <?php esc_html_e( 'Commission Mode', 'ifs-travel-erp' ); ?></span>
                                <span class="spec-val"><?php echo esc_html( $agent->commission_mode ); ?> (<strong><?php echo ( 'Fixed Amount' === $agent->commission_mode ) ? '৳' . esc_html( number_format( (float) $agent->commission_rate, 2 ) ) : esc_html( (float) $agent->commission_rate ) . '%'; ?></strong>)</span>
                            </div>
                        </div>

                        <div class="ifs-spec-group">
                            <span class="spec-group-title"><?php esc_html_e( 'Office Location', 'ifs-travel-erp' ); ?></span>
                            <div class="ifs-address-box">
                                <?php 
                                echo esc_html( $agent->city ? mb_convert_case( $agent->city, MB_CASE_TITLE, 'UTF-8' ) . ', ' : '' ); 
                                echo nl2br( esc_html( $agent->address ?: __( 'No address specified.', 'ifs-travel-erp' ) ) ); 
                                ?>
                            </div>
                        </div>

                        <?php if ( ! empty( $agent->bank_details ) ) : ?>
                            <div class="ifs-spec-group" style="margin-bottom: 0;">
                                <span class="spec-group-title"><?php esc_html_e( 'Settlement Bank & MFS', 'ifs-travel-erp' ); ?></span>
                                <div class="ifs-address-box font-mono" style="background: #f0fdf4; border-color: #bbf7d0; color: #166534;">
                                    <?php echo nl2br( esc_html( $agent->bank_details ) ); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="ifs-card">
                        <div class="ifs-card-header">
                            <span class="dashicons dashicons-media-document"></span>
                            <h3><?php esc_html_e( 'Partner Document Vault', 'ifs-travel-erp' ); ?></h3>
                        </div>

                        <div class="ifs-vault-grid">
                            <div class="ifs-vault-card">
                                <div class="vault-thumb">
                                    <?php if ( ! empty( $agent->agreement_doc_url ) ) : ?>
                                        <?php if ( preg_match( '/\.(jpg|jpeg|png|webp|gif)$/i', $agent->agreement_doc_url ) ) : ?>
                                            <img src="<?php echo esc_url( $agent->agreement_doc_url ); ?>" alt="Agreement" />
                                        <?php else : ?>
                                            <span class="dashicons dashicons-pdf text-rose"></span>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <span class="dashicons dashicons-media-document text-muted"></span>
                                    <?php endif; ?>
                                </div>
                                <div class="vault-details">
                                    <span class="vault-title"><?php esc_html_e( 'Partnership Contract', 'ifs-travel-erp' ); ?></span>
                                    <span class="vault-status <?php echo ! empty( $agent->agreement_doc_url ) ? 'status-ok' : 'status-empty'; ?>">
                                        <?php echo ! empty( $agent->agreement_doc_url ) ? esc_html__( 'Verified & On File', 'ifs-travel-erp' ) : esc_html__( 'Missing Document', 'ifs-travel-erp' ); ?>
                                    </span>
                                </div>
                                <div class="vault-action">
                                    <?php if ( ! empty( $agent->agreement_doc_url ) ) : ?>
                                        <button type="button" class="ifs-btn-sm open-image-modal" data-img="<?php echo esc_url( $agent->agreement_doc_url ); ?>" data-title="<?php esc_attr_e( 'Partnership Agreement', 'ifs-travel-erp' ); ?>">
                                            <span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'View', 'ifs-travel-erp' ); ?>
                                        </button>
                                    <?php else : ?>
                                        <a href="<?php echo esc_url( $edit_url ); ?>" class="ifs-link-upload"><?php esc_html_e( 'Upload', 'ifs-travel-erp' ); ?></a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="ifs-vault-card">
                                <div class="vault-thumb">
                                    <?php if ( ! empty( $agent->logo_url ) ) : ?>
                                        <img src="<?php echo esc_url( $agent->logo_url ); ?>" alt="Logo" />
                                    <?php else : ?>
                                        <span class="dashicons dashicons-camera text-muted"></span>
                                    <?php endif; ?>
                                </div>
                                <div class="vault-details">
                                    <span class="vault-title"><?php esc_html_e( 'Agency Branding Logo', 'ifs-travel-erp' ); ?></span>
                                    <span class="vault-status <?php echo ! empty( $agent->logo_url ) ? 'status-ok' : 'status-empty'; ?>">
                                        <?php echo ! empty( $agent->logo_url ) ? esc_html__( 'Attached', 'ifs-travel-erp' ) : esc_html__( 'Missing Logo', 'ifs-travel-erp' ); ?>
                                    </span>
                                </div>
                                <div class="vault-action">
                                    <?php if ( ! empty( $agent->logo_url ) ) : ?>
                                        <button type="button" class="ifs-btn-sm open-image-modal" data-img="<?php echo esc_url( $agent->logo_url ); ?>" data-title="<?php esc_attr_e( 'Agency Logo', 'ifs-travel-erp' ); ?>">
                                            <span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'View', 'ifs-travel-erp' ); ?>
                                        </button>
                                    <?php else : ?>
                                        <a href="<?php echo esc_url( $edit_url ); ?>" class="ifs-link-upload"><?php esc_html_e( 'Upload', 'ifs-travel-erp' ); ?></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>

                <section class="ifs-main-pane">
                    
                    <div class="ifs-card" style="margin-bottom: 0;">
                        <div class="ifs-card-header" style="justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span class="dashicons dashicons-list-view"></span>
                                <h3><?php esc_html_e( 'Chronological Statement Ledger', 'ifs-travel-erp' ); ?></h3>
                            </div>

                            <form method="get" action="" class="ifs-date-filter-form">
                                <input type="hidden" name="page" value="ifs_travel_erp">
                                <input type="hidden" name="tab" value="b2b_agents">
                                <input type="hidden" name="sub" value="ledger">
                                <input type="hidden" name="id" value="<?php echo esc_attr( $agent_id ); ?>">
                                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                    <input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" class="ifs-input-field font-mono" style="width: auto; height: 36px !important; font-size: 12px;">
                                    <span style="color: var(--ifs-slate-400); font-size: 12px; font-weight: 700;"><?php esc_html_e( 'to', 'ifs-travel-erp' ); ?></span>
                                    <input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" class="ifs-input-field font-mono" style="width: auto; height: 36px !important; font-size: 12px;">
                                    <button type="submit" class="ifs-btn ifs-btn-primary" style="height: 36px; padding: 0 12px; font-size: 12px;"><?php esc_html_e( 'Filter', 'ifs-travel-erp' ); ?></button>
                                    <?php if ( ! empty( $date_from ) || ! empty( $date_to ) ) : ?>
                                        <a href="<?php echo esc_url( $base_url ); ?>" class="ifs-btn-clear-filter"><?php esc_html_e( 'Reset', 'ifs-travel-erp' ); ?></a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>

                        <div class="ifs-custom-table-controls">
                            <div class="ifs-per-page-wrap">
                                <label for="ifsAgentLedgerPerPage"><?php esc_html_e( 'Show', 'ifs-travel-erp' ); ?></label>
                                <select id="ifsAgentLedgerPerPage" class="ifs-select-control">
                                    <option value="10">10</option>
                                    <option value="15" selected>15</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                </select>
                                <span><?php esc_html_e( 'entries', 'ifs-travel-erp' ); ?></span>
                            </div>
                            <div class="ifs-live-search-wrap">
                                <label for="ifsAgentLedgerSearch"><span class="dashicons dashicons-search"></span></label>
                                <input type="text" id="ifsAgentLedgerSearch" class="ifs-search-input" placeholder="<?php esc_attr_e( 'Search statement records...', 'ifs-travel-erp' ); ?>">
                            </div>
                        </div>

                        <div class="ifs-table-container">
                            <table class="ifs-table" id="ifsAgentLedgerTable">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e( 'Date & Time', 'ifs-travel-erp' ); ?></th>
                                        <th><?php esc_html_e( 'Reference Scope', 'ifs-travel-erp' ); ?></th>
                                        <th class="text-right"><?php esc_html_e( 'Debit (-)', 'ifs-travel-erp' ); ?></th>
                                        <th class="text-right"><?php esc_html_e( 'Credit (+)', 'ifs-travel-erp' ); ?></th>
                                        <th class="text-right"><?php esc_html_e( 'Balance (৳)', 'ifs-travel-erp' ); ?></th>
                                        <th><?php esc_html_e( 'Narration / Note', 'ifs-travel-erp' ); ?></th>
                                        <th class="text-center" style="width: 70px;" data-sortable="false"><?php esc_html_e( 'Action', 'ifs-travel-erp' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ( ! empty( $ledger_records ) ) : foreach ( $ledger_records as $rec ) : 
                                        $bal_val    = (float) $rec->balance_after;
                                        $is_bal_neg = ( $bal_val < 0 );
                                        $has_debit  = ( (float) $rec->debit > 0 );
                                        $has_credit = ( (float) $rec->credit > 0 );
                                    ?>
                                        <tr>
                                            <td><span class="font-mono text-date"><?php echo esc_html( gmdate( 'd M Y, h:i A', strtotime( $rec->created_at ) ) ); ?></span></td>
                                            <td>
                                                <span class="ifs-pill font-mono">
                                                    <?php echo esc_html( $rec->reference_type ); ?>
                                                    <?php if ( ! empty( $rec->reference_id ) ) : ?>
                                                        <span class="text-muted">#<?php echo esc_html( $rec->reference_id ); ?></span>
                                                    <?php endif; ?>
                                                </span>
                                            </td>
                                            <td class="text-right font-mono text-bold <?php echo $has_debit ? 'text-rose' : 'text-muted'; ?>">
                                                <?php echo $has_debit ? '-৳' . esc_html( number_format( (float) $rec->debit, 2 ) ) : '&mdash;'; ?>
                                            </td>
                                            <td class="text-right font-mono text-bold <?php echo $has_credit ? 'text-emerald' : 'text-muted'; ?>">
                                                <?php echo $has_credit ? '+৳' . esc_html( number_format( (float) $rec->credit, 2 ) ) : '&mdash;'; ?>
                                            </td>
                                            <td class="text-right font-mono text-bold <?php echo $is_bal_neg ? 'text-rose' : 'text-slate'; ?>">
                                                <?php echo $is_bal_neg ? '-৳' : '+৳'; ?><?php echo esc_html( number_format( abs( $bal_val ), 2 ) ); ?>
                                            </td>
                                            <td><span class="narration-text"><?php echo esc_html( $rec->note ?: __( 'System ledger transaction', 'ifs-travel-erp' ) ); ?></span></td>
                                            <td class="text-center">
                                                <button type="button" class="ifs-btn-sm open-ledger-modal" 
                                                        data-id="<?php echo esc_attr( $rec->id ); ?>"
                                                        data-date="<?php echo esc_attr( gmdate( 'd M Y, h:i A', strtotime( $rec->created_at ) ) ); ?>"
                                                        data-type="<?php echo esc_attr( $rec->reference_type ); ?>"
                                                        data-refid="<?php echo esc_attr( $rec->reference_id ?: 'N/A' ); ?>"
                                                        data-debit="<?php echo esc_attr( number_format( (float) $rec->debit, 2 ) ); ?>"
                                                        data-credit="<?php echo esc_attr( number_format( (float) $rec->credit, 2 ) ); ?>"
                                                        data-bal="<?php echo esc_attr( number_format( (float) $rec->balance_after, 2 ) ); ?>"
                                                        data-note="<?php echo esc_attr( $rec->note ?: 'N/A' ); ?>"
                                                        title="<?php esc_attr_e( 'View Voucher Audit', 'ifs-travel-erp' ); ?>">
                                                    <span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'View', 'ifs-travel-erp' ); ?>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; else : ?>
                                        <tr>
                                            <td colspan="7">
                                                <div class="ifs-empty-record"><?php esc_html_e( 'No transaction records found for this sub-agent partner.', 'ifs-travel-erp' ); ?></div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </section>
            </main>
        </div>

        <div id="ifsImageModal" class="ifs-modal-root" style="display: none;">
            <div class="ifs-modal-backdrop"></div>
            <div class="ifs-modal-container">
                <div class="ifs-modal-content">
                    <header class="ifs-modal-header">
                        <h3 id="modalImageTitle"><span class="dashicons dashicons-format-image"></span> <?php esc_html_e( 'Document Preview', 'ifs-travel-erp' ); ?></h3>
                        <button type="button" class="ifs-modal-close" id="btnCloseImageModal">&times;</button>
                    </header>
                    <div class="ifs-modal-body">
                        <img id="modalImagePreviewSrc" src="" alt="Document Preview" />
                    </div>
                    <footer class="ifs-modal-footer">
                        <a id="modalImageDownloadBtn" href="" target="_blank" rel="noopener" class="ifs-btn ifs-btn-default"><span class="dashicons dashicons-external"></span> <?php esc_html_e( 'Open Original', 'ifs-travel-erp' ); ?></a>
                        <button type="button" class="ifs-btn ifs-btn-primary" id="btnFooterCloseImageModal"><?php esc_html_e( 'Dismiss', 'ifs-travel-erp' ); ?></button>
                    </footer>
                </div>
            </div>
        </div>

        <div id="ifsLedgerModal" class="ifs-modal-root" style="display: none;">
            <div class="ifs-modal-backdrop" id="backdropLedgerModal"></div>
            <div class="ifs-modal-container">
                <div class="ifs-modal-content">
                    <header class="ifs-modal-header">
                        <h3><span class="dashicons dashicons-media-text"></span> <?php esc_html_e( 'Transaction Voucher Audit', 'ifs-travel-erp' ); ?></h3>
                        <button type="button" class="ifs-modal-close" id="btnCloseLedgerModal">&times;</button>
                    </header>
                    <div class="ifs-modal-body" style="background: var(--ifs-slate-50); text-align: left;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                            <div class="ifs-card" style="margin-bottom: 0; padding: 12px;">
                                <span class="spec-group-title"><?php esc_html_e( 'Timestamp', 'ifs-travel-erp' ); ?></span>
                                <strong class="font-mono" id="mDate">---</strong>
                            </div>
                            <div class="ifs-card" style="margin-bottom: 0; padding: 12px;">
                                <span class="spec-group-title"><?php esc_html_e( 'Reference ID', 'ifs-travel-erp' ); ?></span>
                                <strong class="font-mono" id="mRefId">---</strong>
                            </div>
                            <div class="ifs-card" style="margin-bottom: 0; padding: 12px;">
                                <span class="spec-group-title"><?php esc_html_e( 'Debit Outflow', 'ifs-travel-erp' ); ?></span>
                                <strong class="font-mono text-rose" id="mDebit">৳0.00</strong>
                            </div>
                            <div class="ifs-card" style="margin-bottom: 0; padding: 12px;">
                                <span class="spec-group-title"><?php esc_html_e( 'Credit Inflow', 'ifs-travel-erp' ); ?></span>
                                <strong class="font-mono text-emerald" id="mCredit">৳0.00</strong>
                            </div>
                        </div>
                        <div class="ifs-card" style="margin-bottom: 0; padding: 12px;">
                            <span class="spec-group-title"><?php esc_html_e( 'Running Balance After Transaction', 'ifs-travel-erp' ); ?></span>
                            <strong class="font-mono text-bold" id="mBal">৳0.00</strong>
                        </div>
                        <div class="ifs-card" style="margin-bottom: 0; padding: 12px; margin-top: 12px;">
                            <span class="spec-group-title"><?php esc_html_e( 'Narration / Remarks', 'ifs-travel-erp' ); ?></span>
                            <p id="mNote" style="margin: 4px 0 0 0; font-size: 13px; font-weight: 600; color: var(--ifs-slate-800);">---</p>
                        </div>
                    </div>
                    <footer class="ifs-modal-footer">
                        <button type="button" class="ifs-btn ifs-btn-primary" id="btnFooterCloseLedgerModal"><?php esc_html_e( 'Close Window', 'ifs-travel-erp' ); ?></button>
                    </footer>
                </div>
            </div>
        </div>

        <style>
            :root {
                --ifs-navy-950: #02122c;
                --ifs-navy-900: #0a1f44;
                --ifs-navy-800: #0b2d6b;
                --ifs-primary: #0284c7;
                --ifs-slate-50: #f8fafc;
                --ifs-slate-100: #f1f5f9;
                --ifs-slate-200: #e2e8f0;
                --ifs-slate-300: #cbd5e1;
                --ifs-slate-400: #94a3b8;
                --ifs-slate-600: #475569;
                --ifs-slate-700: #334155;
                --ifs-slate-800: #1e293b;
                --ifs-slate-900: #0f172a;
                --ifs-emerald: #059669;
                --ifs-rose: #e11d48;
                --ifs-radius: 12px;
            }

            .ifs-agent-dossier-workspace {
                max-width: 1440px;
                margin: 18px auto;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                color: var(--ifs-slate-800);
                -webkit-font-smoothing: antialiased;
            }

            .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
            .text-bold { font-weight: 700; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .text-emerald { color: var(--ifs-emerald) !important; }
            .text-rose { color: var(--ifs-rose) !important; }
            .text-muted { color: var(--ifs-slate-400) !important; }

            /* Command Strip Header */
            .ifs-view-header-strip {
                background: #ffffff;
                border: 1px solid var(--ifs-slate-200);
                border-radius: var(--ifs-radius);
                padding: 16px 24px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 16px;
                flex-wrap: wrap;
                margin-bottom: 20px;
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            }
            .ifs-header-identity { display: flex; align-items: center; gap: 16px; }
            .ifs-back-round-btn {
                width: 40px;
                height: 40px;
                border-radius: 8px;
                background: var(--ifs-slate-50);
                border: 1px solid var(--ifs-slate-200);
                display: flex;
                align-items: center;
                justify-content: center;
                color: var(--ifs-slate-700);
                text-decoration: none;
                transition: all 0.15s ease-in-out;
            }
            .ifs-back-round-btn:hover { background: var(--ifs-slate-200); color: var(--ifs-slate-900); }
            .ifs-badge-row { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; flex-wrap: wrap; }
            .ifs-id-pill {
                font-size: 11px;
                font-weight: 600;
                background: var(--ifs-slate-100);
                color: var(--ifs-slate-600);
                padding: 2px 8px;
                border-radius: 4px;
                border: 1px solid var(--ifs-slate-200);
                display: inline-flex;
                align-items: center;
                gap: 4px;
            }
            .tier-pill { font-size: 10px; font-weight: 800; text-transform: uppercase; padding: 2px 8px; border-radius: 4px; }
            .tier-standard-partner { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
            .tier-gold-partner { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
            .tier-platinum-corporate { background: #faf5ff; color: #6b21a8; border: 1px solid #e9d5ff; }
            .status-pill { font-size: 10px; font-weight: 800; text-transform: uppercase; padding: 2px 8px; border-radius: 4px; }
            .status-active { background: #dcfce7; color: #166534; }
            .status-suspended { background: #fee2e2; color: #991b1b; }

            .ifs-header-title { margin: 0; font-size: 20px; font-weight: 800; color: var(--ifs-slate-900); letter-spacing: -0.02em; }
            .ifs-header-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

            /* Global Buttons */
            .ifs-btn {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 8px 14px;
                font-size: 13px;
                font-weight: 600;
                border-radius: 6px;
                text-decoration: none;
                cursor: pointer;
                transition: all 0.15s ease;
                line-height: 1.2;
                border: 1px solid transparent;
            }
            .ifs-btn .dashicons { font-size: 15px; width: 15px; height: 15px; }
            .ifs-btn-default { background: #ffffff; border-color: var(--ifs-slate-300); color: var(--ifs-slate-700); }
            .ifs-btn-default:hover { background: var(--ifs-slate-50); border-color: var(--ifs-slate-400); color: var(--ifs-slate-900); }
            .ifs-btn-primary { background: var(--ifs-navy-900); color: #ffffff !important; }
            .ifs-btn-primary:hover { background: var(--ifs-navy-800); color: #ffffff !important; }
            .ifs-btn-sm { padding: 4px 10px; font-size: 12px; font-weight: 600; border-radius: 4px; background: var(--ifs-slate-100); border: 1px solid var(--ifs-slate-300); color: var(--ifs-slate-700); cursor: pointer; display: inline-flex; align-items: center; gap: 4px; }
            .ifs-btn-sm:hover { background: var(--ifs-navy-900); color: #ffffff; border-color: var(--ifs-navy-900); }
            .ifs-btn-sm .dashicons { font-size: 13px; width: 13px; height: 13px; }

            /* KPI Strip */
            .ifs-kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 20px; }
            .ifs-kpi-card {
                background: #ffffff;
                border: 1px solid var(--ifs-slate-200);
                border-radius: var(--ifs-radius);
                padding: 16px 20px;
                display: flex;
                align-items: center;
                gap: 16px;
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            }
            .ifs-kpi-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
            .ifs-kpi-icon.kpi-blue { background: #eff6ff; color: #2563eb; }
            .ifs-kpi-icon.kpi-emerald { background: #ecfdf5; color: var(--ifs-emerald); }
            .ifs-kpi-icon.kpi-rose { background: #fff1f2; color: var(--ifs-rose); }
            .ifs-kpi-icon .dashicons { font-size: 22px; width: 22px; height: 22px; }
            .ifs-kpi-label { font-size: 11px; font-weight: 700; color: var(--ifs-slate-400); text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 2px; }
            .ifs-kpi-value { font-size: 18px; font-weight: 800; color: var(--ifs-slate-900); display: block; }

            /* Dossier Main Grid */
            .ifs-dossier-grid { display: grid; grid-template-columns: 380px 1fr; gap: 20px; align-items: flex-start; }
            @media (max-width: 1120px) { .ifs-dossier-grid { grid-template-columns: 1fr; } }

            /* Reusable Card */
            .ifs-card {
                background: #ffffff;
                border: 1px solid var(--ifs-slate-200);
                border-radius: var(--ifs-radius);
                padding: 20px;
                margin-bottom: 20px;
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            }
            .ifs-card-header {
                display: flex;
                align-items: center;
                gap: 8px;
                padding-bottom: 14px;
                border-bottom: 1px solid var(--ifs-slate-100);
                margin-bottom: 16px;
            }
            .ifs-card-header h3 { margin: 0; font-size: 14px; font-weight: 800; color: var(--ifs-slate-900); letter-spacing: -0.01em; text-transform: uppercase; }
            .ifs-card-header .dashicons { font-size: 16px; width: 16px; height: 16px; color: var(--ifs-primary); }

            /* Demographic Specs */
            .ifs-spec-group { margin-bottom: 16px; }
            .spec-group-title { font-size: 10px; font-weight: 800; text-transform: uppercase; color: var(--ifs-slate-400); letter-spacing: 0.05em; display: block; margin-bottom: 8px; }
            .ifs-spec-row { display: flex; justify-content: space-between; align-items: center; padding: 5px 0; font-size: 12.5px; }
            .spec-label { color: var(--ifs-slate-600); display: inline-flex; align-items: center; gap: 6px; }
            .spec-label .dashicons { font-size: 14px; width: 14px; height: 14px; color: var(--ifs-slate-400); }
            .spec-val { font-weight: 600; color: var(--ifs-slate-900); text-align: right; }
            .spec-val a { color: var(--ifs-primary); text-decoration: none; }
            .spec-val a:hover { text-decoration: underline; }
            .ifs-address-box { font-size: 12.5px; color: var(--ifs-slate-700); line-height: 1.4; background: var(--ifs-slate-50); border: 1px solid var(--ifs-slate-200); border-radius: 6px; padding: 10px; margin-top: 4px; }

            /* Vault */
            .ifs-vault-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
            .ifs-vault-card {
                background: var(--ifs-slate-50);
                border: 1px solid var(--ifs-slate-200);
                border-radius: 8px;
                padding: 12px;
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .vault-thumb {
                width: 42px;
                height: 42px;
                border-radius: 6px;
                background: #ffffff;
                border: 1px solid var(--ifs-slate-200);
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                flex-shrink: 0;
            }
            .vault-thumb img { width: 100%; height: 100%; object-fit: cover; }
            .vault-thumb .dashicons { font-size: 20px; width: 20px; height: 20px; }
            .vault-details { flex: 1; min-width: 0; }
            .vault-title { font-size: 12px; font-weight: 700; color: var(--ifs-slate-900); display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .vault-status { font-size: 10.5px; font-weight: 600; }
            .vault-status.status-ok { color: var(--ifs-emerald); }
            .vault-status.status-empty { color: var(--ifs-slate-400); }
            .ifs-link-upload { font-size: 11.5px; font-weight: 600; color: var(--ifs-primary); text-decoration: none; }
            .ifs-link-upload:hover { text-decoration: underline; }

            /* Tables & Filters */
            .ifs-date-filter-form .ifs-input-field {
                border: 1px solid var(--ifs-slate-300);
                border-radius: 6px;
                padding: 4px 8px;
                font-size: 12px;
                outline: none;
            }
            .ifs-btn-clear-filter { font-size: 12px; font-weight: 600; color: var(--ifs-rose); text-decoration: none; }
            .ifs-custom-table-controls {
                padding: 12px 16px;
                background: var(--ifs-slate-50);
                border-bottom: 1px solid var(--ifs-slate-200);
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 12px;
                font-size: 12.5px;
                color: var(--ifs-slate-600);
                font-weight: 600;
            }
            .ifs-per-page-wrap { display: flex; align-items: center; gap: 6px; }
            .ifs-select-control { border: 1px solid var(--ifs-slate-300); border-radius: 6px; padding: 3px 24px 3px 8px; font-size: 12.5px; background: #ffffff; outline: none; }
            .ifs-live-search-wrap { position: relative; display: flex; align-items: center; min-width: 240px; }
            .ifs-live-search-wrap label { position: absolute; left: 10px; color: var(--ifs-slate-400); display: flex; align-items: center; pointer-events: none; }
            .ifs-live-search-wrap label .dashicons { font-size: 15px; width: 15px; height: 15px; }
            .ifs-search-input { width: 100%; height: 34px !important; padding: 0 10px 0 32px !important; border: 1px solid var(--ifs-slate-300); border-radius: 6px; font-size: 12.5px; background: #ffffff; outline: none; }

            .ifs-table-container { overflow-x: auto; border: 1px solid var(--ifs-slate-200); border-radius: 8px; }
            .ifs-table { width: 100%; border-collapse: collapse; font-size: 12.5px; text-align: left; }
            .ifs-table th { background: var(--ifs-slate-50); padding: 8px 12px; font-weight: 700; color: var(--ifs-slate-600); font-size: 11px; text-transform: uppercase; border-bottom: 1px solid var(--ifs-slate-200); white-space: nowrap; }
            .ifs-table td { padding: 10px 12px; border-bottom: 1px solid var(--ifs-slate-100); color: var(--ifs-slate-700); }
            .ifs-table tr:last-child td { border-bottom: none; }
            .ifs-table tr:hover td { background: var(--ifs-slate-50); }
            .ifs-pill { font-size: 10.5px; font-weight: 600; background: var(--ifs-slate-100); color: var(--ifs-slate-700); padding: 2px 6px; border-radius: 4px; border: 1px solid var(--ifs-slate-200); display: inline-flex; align-items: center; gap: 4px; }
            .narration-text { font-size: 12px; color: var(--ifs-slate-600); }
            .ifs-empty-record { background: var(--ifs-slate-50); border: 1px dashed var(--ifs-slate-300); border-radius: 6px; padding: 16px; font-size: 12px; color: var(--ifs-slate-400); text-align: center; }

            /* Modal Lightbox */
            .ifs-modal-root { position: fixed; inset: 0; z-index: 99999; display: flex; align-items: center; justify-content: center; }
            .ifs-modal-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(2px); }
            .ifs-modal-container { position: relative; z-index: 100000; width: 620px; max-width: 90vw; }
            .ifs-modal-content { background: #ffffff; border-radius: var(--ifs-radius); overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); }
            .ifs-modal-header { padding: 14px 18px; border-bottom: 1px solid var(--ifs-slate-200); display: flex; justify-content: space-between; align-items: center; background: var(--ifs-slate-50); }
            .ifs-modal-header h3 { margin: 0; font-size: 14px; font-weight: 700; color: var(--ifs-slate-900); display: flex; align-items: center; gap: 6px; }
            .ifs-modal-header .dashicons { font-size: 16px; width: 16px; height: 16px; color: var(--ifs-primary); }
            .ifs-modal-close { background: transparent; border: none; font-size: 20px; line-height: 1; cursor: pointer; color: var(--ifs-slate-400); }
            .ifs-modal-close:hover { color: var(--ifs-slate-900); }
            .ifs-modal-body { padding: 18px; text-align: center; background: var(--ifs-slate-950, #090d16); max-height: 70vh; overflow-y: auto; }
            .ifs-modal-body img { max-width: 100%; max-height: 65vh; border-radius: 4px; }
            .ifs-modal-footer { padding: 12px 18px; border-top: 1px solid var(--ifs-slate-200); display: flex; justify-content: flex-end; gap: 8px; background: var(--ifs-slate-50); }

            /* DataTables Controls Integration */
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter { display: none !important; }
            .dataTables_wrapper .dataTables_info,
            .dataTables_wrapper .dataTables_paginate { margin-top: 14px; font-size: 12px; color: var(--ifs-slate-600); }
            .dataTables_wrapper .dataTables_paginate .paginate_button {
                background: #ffffff !important;
                border: 1px solid var(--ifs-slate-300) !important;
                border-radius: 4px !important;
                color: var(--ifs-slate-700) !important;
                padding: 4px 10px !important;
                margin-left: 4px;
                font-weight: 600;
            }
            .dataTables_wrapper .dataTables_paginate .paginate_button.current {
                background: var(--ifs-navy-900) !important;
                color: #ffffff !important;
                border-color: var(--ifs-navy-900) !important;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            if ($.fn.DataTable) {
                var table = $('#ifsAgentLedgerTable').DataTable({
                    "pageLength": 15,
                    "ordering": true,
                    "info": true,
                    "searching": true,
                    "lengthChange": false,
                    "order": [[ 0, "desc" ]],
                    "language": {
                        "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                        "infoEmpty": "Showing 0 to 0 of 0 entries",
                        "paginate": { "previous": "&larr; Prev", "next": "Next &rarr;" }
                    }
                });

                $('#ifsAgentLedgerPerPage').on('change', function() {
                    table.page.len(parseInt($(this).val())).draw();
                });

                $('#ifsAgentLedgerSearch').on('keyup', function() {
                    table.search(this.value).draw();
                });
            }

            // Image Lightbox Modal
            $(document).on('click', '.open-image-modal', function() {
                var imgSrc   = $(this).data('img');
                var imgTitle = $(this).data('title');
                if (imgSrc) {
                    $('#modalImagePreviewSrc').attr('src', imgSrc);
                    $('#modalImageDownloadBtn').attr('href', imgSrc);
                    if (imgTitle) {
                        $('#modalImageTitle').html('<span class="dashicons dashicons-format-image"></span> ' + imgTitle);
                    }
                    $('#ifsImageModal').fadeIn(150);
                }
            });

            // Ledger Voucher Modal
            $(document).on('click', '.open-ledger-modal', function() {
                const btn = $(this);
                $('#mDate').text(btn.data('date'));
                $('#mRefId').text('#' + btn.data('refid'));
                $('#mDebit').text('-৳' + btn.data('debit'));
                $('#mCredit').text('+৳' + btn.data('credit'));
                $('#mBal').text('৳' + btn.data('bal'));
                $('#mNote').text(btn.data('note'));
                $('#ifsLedgerModal').fadeIn(150);
            });

            $('#btnCloseImageModal, #btnFooterCloseImageModal, #btnCloseLedgerModal, #btnFooterCloseLedgerModal, .ifs-modal-backdrop').on('click', function() {
                $('#ifsImageModal, #ifsLedgerModal').fadeOut(150);
            });

            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $('#ifsImageModal, #ifsLedgerModal').fadeOut(150);
                }
            });
        });
        </script>
        <?php
    }
}