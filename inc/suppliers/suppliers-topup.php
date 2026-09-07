<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_suppliers_topup_page' ) ) {
    /**
     * Add Supplier Deposit Form Page
     * Flat Minimal UI: Zero Shadows, Strict 42px Uniform Field Heights, Normalized Controls
     */
    function ifs_terp_suppliers_topup_page() {
        global $wpdb;
        $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
        $table_ledger    = $wpdb->prefix . 'iterp_supplier_ledger';
        $base_url        = admin_url( 'admin.php?page=ifs_travel_erp&tab=suppliers' );

        // Enqueue Select2 assets
        if ( ! wp_script_is( 'select2', 'registered' ) ) {
            wp_register_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), '4.1.0-rc.0', true );
            wp_register_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0-rc.0' );
        }
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'select2' );
        wp_enqueue_style( 'select2' );

        $message = '';
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_supplier_payment_submit'] ) ) {
            check_admin_referer( 'ifs_supp_pay_action', 'ifs_supp_pay_nonce' );

            $supp_id    = isset( $_POST['supplier_id'] ) ? absint( wp_unslash( $_POST['supplier_id'] ) ) : 0;
            $amount     = isset( $_POST['amount'] ) ? (float) wp_unslash( $_POST['amount'] ) : 0;
            $pay_method = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : 'Bank Transfer';
            $note       = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';

            if ( $supp_id > 0 && $amount > 0 ) {
                $current_bal = (float) $wpdb->get_var( $wpdb->prepare( "SELECT current_balance FROM {$table_suppliers} WHERE id = %d", $supp_id ) );
                $new_bal     = $current_bal + $amount;

                $wpdb->update( $table_suppliers, array( 'current_balance' => $new_bal ), array( 'id' => $supp_id ) );

                $wpdb->insert(
                    $table_ledger,
                    array(
                        'supplier_id'    => $supp_id,
                        'reference_type' => $pay_method . ' Deposit',
                        'debit'          => 0,
                        'credit'         => $amount,
                        'balance_after'  => $new_bal,
                        'note'           => $note,
                        'created_at'     => current_time( 'mysql' )
                    ),
                    array( '%d', '%s', '%f', '%f', '%f', '%s', '%s' )
                );

                if ( function_exists( 'ifs_terp_log_activity' ) ) {
                    ifs_terp_log_activity( "Posted Deposit ৳{$amount} to Supplier ID #SUP-{$supp_id} ({$pay_method})" );
                }

                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Deposit posted and supplier balance updated successfully.', 'ifs-travel-erp' ) . '</div>';
            } else {
                $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> ' . esc_html__( 'Please select a supplier and enter a valid deposit amount.', 'ifs-travel-erp' ) . '</div>';
            }
        }

        $suppliers = $wpdb->get_results( "SELECT id, supplier_name, current_balance, supplier_type FROM {$table_suppliers} WHERE status = 'Active' ORDER BY supplier_name ASC" );
        ?>
        <div class="wrap ifs-topup-workspace">
            <?php echo wp_kses_post( $message ); ?>

            <form method="post" action="<?php echo esc_url( add_query_arg( 'sub', 'topup', $base_url ) ); ?>">
                <?php wp_nonce_field( 'ifs_supp_pay_action', 'ifs_supp_pay_nonce' ); ?>
                
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">+</div>
                        <div>
                            <h3 class="ifs-card-title"><?php esc_html_e( 'Add Supplier Deposit', 'ifs-travel-erp' ); ?></h3>
                            <p class="ifs-card-desc"><?php esc_html_e( 'Credit supplier balance, update ledger book, and track bank transaction references', 'ifs-travel-erp' ); ?></p>
                        </div>
                    </div>

                    <div class="ifs-grid-2">
                        <div class="ifs-field-block col-span-2">
                            <label class="ifs-field-label" for="sel_topup_supplier"><?php esc_html_e( 'Supplier / Portal', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <select name="supplier_id" id="sel_topup_supplier" required class="ifs-input-field ifs-select2">
                                    <option value=""><?php esc_html_e( '-- Select Supplier / GDS Portal --', 'ifs-travel-erp' ); ?></option>
                                    <?php if ( ! empty( $suppliers ) ) : foreach ( $suppliers as $s ) : ?>
                                        <option value="<?php echo esc_attr( $s->id ); ?>" 
                                                data-bal="<?php echo esc_attr( $s->current_balance ); ?>">
                                            <?php echo esc_html( $s->supplier_name ); ?> (<?php esc_html_e( 'Current Balance:', 'ifs-travel-erp' ); ?> ৳<?php echo esc_html( number_format( (float) $s->current_balance, 2 ) ); ?>)
                                        </option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_topup_amount"><?php esc_html_e( 'Deposit Amount (৳)', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <input type="number" step="0.01" name="amount" id="inp_topup_amount" required 
                                       placeholder="0.00" class="ifs-input-field font-mono font-bold color-emerald">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_payment_method"><?php esc_html_e( 'Payment Method', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <select name="payment_method" id="inp_payment_method" class="ifs-input-field">
                                    <option value="Bank Transfer"><?php esc_html_e( 'Bank Transfer', 'ifs-travel-erp' ); ?></option>
                                    <option value="Cheque Deposit"><?php esc_html_e( 'Cheque Deposit', 'ifs-travel-erp' ); ?></option>
                                    <option value="Credit Card"><?php esc_html_e( 'Credit Card', 'ifs-travel-erp' ); ?></option>
                                    <option value="Cash Deposit"><?php esc_html_e( 'Cash Deposit', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-2">
                            <label class="ifs-field-label" for="inp_topup_note"><?php esc_html_e( 'Reference / Deposit Note', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <input type="text" name="note" id="inp_topup_note" 
                                       placeholder="<?php esc_attr_e( 'e.g. Bank CD A/C #1102983 | Trx ID: TXN-881928', 'ifs-travel-erp' ); ?>" class="ifs-input-field">
                            </div>
                        </div>
                    </div>

                    <div class="ifs-action-strip" style="margin-top: 22px;">
                        <a href="<?php echo esc_url( add_query_arg( 'sub', 'list', $base_url ) ); ?>" class="ifs-btn-secondary"><?php esc_html_e( 'Cancel', 'ifs-travel-erp' ); ?></a>
                        <button type="submit" name="ifs_supplier_payment_submit" class="ifs-btn-primary">
                            <span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Post Deposit', 'ifs-travel-erp' ); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Stylesheet: Flat Minimal UI, Zero Shadows & Strict 42px Uniform Field Heights -->
        <style>
            .ifs-topup-workspace { 
                max-width: 900px; 
                margin: 20px auto; 
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; 
                color: #0f172a; 
                box-sizing: border-box; 
            }
            .ifs-topup-workspace *, 
            .ifs-topup-workspace *::before, 
            .ifs-topup-workspace *::after { 
                box-sizing: border-box; 
                box-shadow: none !important; 
                text-shadow: none !important; 
            }

            .ifs-toast { 
                padding: 13px 18px; 
                border-radius: 10px; 
                font-size: 13.5px; 
                font-weight: 600; 
                display: flex; 
                align-items: center; 
                gap: 10px; 
                margin-bottom: 22px; 
            }
            .ifs-toast.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
            .ifs-toast.danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
            .ifs-toast .dashicons { font-size: 18px; width: 18px; height: 18px; }

            .ifs-panel-card { 
                background: #ffffff; 
                border: 1px solid #e2e8f0; 
                border-radius: 14px; 
                padding: 26px; 
                margin-bottom: 24px; 
            }
            .ifs-card-header { 
                display: flex; 
                align-items: center; 
                gap: 14px; 
                margin-bottom: 22px; 
                padding-bottom: 14px; 
                border-bottom: 1px solid #f1f5f9; 
            }
            .ifs-step-num { 
                width: 34px; 
                height: 34px; 
                border-radius: 9px; 
                background: #003376; 
                color: #ffffff; 
                font-weight: 800; 
                font-size: 15px; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                flex-shrink: 0; 
            }
            .ifs-card-title { margin: 0; font-size: 15.5px; font-weight: 800; color: #0f172a; }
            .ifs-card-desc { margin: 2px 0 0 0; font-size: 12.5px; color: #64748b; }

            .ifs-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; }
            .col-span-2 { grid-column: span 2; }
            @media (max-width: 768px) { .ifs-grid-2 { grid-template-columns: 1fr; } .col-span-2 { grid-column: span 1; } }

            .ifs-field-block { 
                display: flex; 
                flex-direction: column; 
                justify-content: flex-start;
                gap: 6px; 
                width: 100%;
            }
            .ifs-field-label { 
                font-size: 11px; 
                font-weight: 700; 
                color: #475569; 
                text-transform: uppercase; 
                letter-spacing: 0.5px; 
                line-height: 1.2;
            }
            .ifs-field-label .req { color: #e11d48; margin-left: 2px; }

            .ifs-field-wrap { 
                position: relative; 
                display: flex; 
                align-items: center; 
                width: 100%; 
                height: 42px; 
            }

            .ifs-input-field { 
                width: 100% !important; 
                height: 42px !important; 
                max-height: 42px !important; 
                min-height: 42px !important; 
                line-height: 40px !important; 
                padding: 0 14px !important; 
                border: 1px solid #cbd5e1 !important; 
                border-radius: 8px !important; 
                font-size: 13.5px !important; 
                color: #0f172a !important; 
                background-color: #ffffff !important; 
                outline: none !important; 
                transition: border-color 0.2s ease, background-color 0.2s ease; 
                margin: 0 !important; 
                display: block; 
            }
            select.ifs-input-field { 
                appearance: none; 
                -webkit-appearance: none; 
                background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e") !important; 
                background-repeat: no-repeat !important; 
                background-position: right 12px center !important; 
                background-size: 14px !important; 
                padding-right: 36px !important; 
                cursor: pointer; 
            }
            .ifs-input-field:focus { 
                border-color: #003376 !important; 
                background-color: #f8fafc !important; 
            }

            /* Select2 Flat 42px Alignment */
            .select2-container { width: 100% !important; }
            .select2-container .select2-selection--single { 
                height: 42px !important; 
                border: 1px solid #cbd5e1 !important; 
                border-radius: 8px !important; 
                padding: 0 12px !important; 
                display: flex !important; 
                align-items: center !important; 
                background: #ffffff !important; 
                outline: none !important; 
                transition: border-color 0.2s ease, background-color 0.2s ease; 
            }
            .select2-container--default .select2-selection--single .select2-selection__rendered { 
                color: #0f172a !important; 
                font-size: 13.5px !important; 
                line-height: 40px !important; 
                padding-left: 0 !important; 
            }
            .select2-container--default .select2-selection--single .select2-selection__arrow { 
                height: 40px !important; 
                right: 10px !important; 
            }
            .select2-container--default.select2-container--focus .select2-selection--single { 
                border-color: #003376 !important; 
                background-color: #f8fafc !important; 
            }
            .select2-dropdown { 
                border: 1px solid #cbd5e1 !important; 
                border-radius: 8px !important; 
                font-size: 13.5px !important; 
                z-index: 99999 !important; 
            }
            .select2-container--default .select2-results__option--highlighted[aria-selected] { 
                background-color: #003376 !important; 
                color: #ffffff !important; 
            }

            .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important; }
            .font-bold { font-weight: 700; }
            .color-emerald { color: #059669 !important; }

            .ifs-action-strip { 
                display: flex; 
                justify-content: space-between; 
                align-items: center; 
                padding: 18px 24px; 
                background: #ffffff; 
                border: 1px solid #e2e8f0; 
                border-radius: 12px; 
            }
            .ifs-btn-primary { 
                background: #003376; 
                color: #ffffff !important; 
                border: none; 
                height: 42px; 
                padding: 0 24px; 
                border-radius: 8px; 
                font-size: 13.5px; 
                font-weight: 700; 
                text-decoration: none; 
                cursor: pointer; 
                display: inline-flex; 
                align-items: center; 
                gap: 8px; 
                transition: background-color 0.2s ease; 
            }
            .ifs-btn-primary:hover { background: #0284c7; }
            .ifs-btn-secondary { 
                background: #f8fafc; 
                color: #475569 !important; 
                border: 1px solid #cbd5e1; 
                height: 42px; 
                padding: 0 20px; 
                border-radius: 8px; 
                font-size: 13px; 
                font-weight: 600; 
                text-decoration: none; 
                display: inline-flex; 
                align-items: center; 
                gap: 6px; 
                transition: background-color 0.2s ease, color 0.2s ease; 
            }
            .ifs-btn-secondary:hover { background: #f1f5f9; color: #0f172a; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            if ($.fn.select2) {
                $('.ifs-select2').select2({ width: '100%', allowClear: false });
            }
        });
        </script>
        <?php
    }
}