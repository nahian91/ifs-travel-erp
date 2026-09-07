<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_suppliers_add_edit_page' ) ) {
    /**
     * Register / Edit Supplier Form Page
     * Flat Minimal UI: No Shadows, Strict 42px Equal Field Heights, Normalized Controls
     */
    function ifs_terp_suppliers_add_edit_page() {
        global $wpdb;
        $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
        $table_ledger    = $wpdb->prefix . 'iterp_supplier_ledger';
        $base_url        = admin_url( 'admin.php?page=ifs_travel_erp&tab=suppliers' );

        $edit_id   = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
        $edit_data = false;
        if ( $edit_id > 0 ) {
            $edit_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_suppliers} WHERE id = %d", $edit_id ) );
        }

        $message = '';
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_add_supplier_submit'] ) ) {
            check_admin_referer( 'ifs_add_supplier_action', 'ifs_add_supplier_nonce' );

            $is_edit_mode = ( $edit_id > 0 );
            $data_array = array(
                'supplier_name'  => isset( $_POST['supplier_name'] ) ? sanitize_text_field( wp_unslash( $_POST['supplier_name'] ) ) : '',
                'supplier_type'  => isset( $_POST['supplier_type'] ) ? sanitize_text_field( wp_unslash( $_POST['supplier_type'] ) ) : 'GDS / IATA',
                'contact_person' => isset( $_POST['contact_person'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_person'] ) ) : '',
                'phone'          => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
                'email'          => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
                'status'         => isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'Active',
            );

            if ( $is_edit_mode ) {
                $wpdb->update( $table_suppliers, $data_array, array( 'id' => $edit_id ) );
                $message   = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Supplier details updated successfully.', 'ifs-travel-erp' ) . '</div>';
                $edit_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_suppliers} WHERE id = %d", $edit_id ) );
            } else {
                $initial_bal = isset( $_POST['initial_balance'] ) ? (float) wp_unslash( $_POST['initial_balance'] ) : 0;
                $data_array['current_balance'] = $initial_bal;
                $data_array['created_at']      = current_time( 'mysql' );
                $wpdb->insert( $table_suppliers, $data_array );
                $new_id = $wpdb->insert_id;

                if ( $initial_bal > 0 ) {
                    $wpdb->insert(
                        $table_ledger,
                        array(
                            'supplier_id'    => $new_id,
                            'reference_type' => 'Opening Balance',
                            'debit'          => 0,
                            'credit'         => $initial_bal,
                            'balance_after'  => $initial_bal,
                            'note'           => 'Initial deposit during supplier registration',
                            'created_at'     => current_time( 'mysql' )
                        ),
                        array( '%d', '%s', '%f', '%f', '%f', '%s', '%s' )
                    );
                }
                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Supplier added successfully.', 'ifs-travel-erp' ) . '</div>';
            }
        }

        $val_name   = $edit_data ? esc_attr( $edit_data->supplier_name ) : '';
        $val_type   = $edit_data ? esc_attr( $edit_data->supplier_type ) : 'B2B Air Portal';
        $val_person = $edit_data ? esc_attr( $edit_data->contact_person ) : '';
        $val_phone  = $edit_data ? esc_attr( $edit_data->phone ) : '';
        $val_email  = $edit_data ? esc_attr( $edit_data->email ) : '';
        $val_bal    = $edit_data ? (float) $edit_data->current_balance : '';
        $val_status = $edit_data ? esc_attr( $edit_data->status ) : 'Active';
        ?>
        
        <div class="wrap ifs-suppliers-workspace">
            <?php echo wp_kses_post( $message ); ?>

            <form method="post" action="<?php echo esc_url( add_query_arg( array( 'sub' => ( $edit_id ? 'edit' : 'add' ), 'id' => ( $edit_id ? $edit_id : false ) ), $base_url ) ); ?>">
                <?php wp_nonce_field( 'ifs_add_supplier_action', 'ifs_add_supplier_nonce' ); ?>
                <?php if ( $edit_data ) : ?>
                    <input type="hidden" name="edit_id" value="<?php echo esc_attr( $edit_data->id ); ?>">
                <?php endif; ?>

                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num"><?php echo $edit_data ? '✎' : '+'; ?></div>
                        <div>
                            <h3 class="ifs-card-title"><?php echo $edit_data ? esc_html__( 'Edit Supplier Profile', 'ifs-travel-erp' ) : esc_html__( 'Register Supplier', 'ifs-travel-erp' ); ?></h3>
                            <p class="ifs-card-desc"><?php esc_html_e( 'Manage consortia credentials, vendor type, communication channels, and ledger balance', 'ifs-travel-erp' ); ?></p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <div class="ifs-field-block col-span-2">
                            <label class="ifs-field-label" for="inp_supplier_name"><?php esc_html_e( 'Supplier / Company Name', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <input type="text" name="supplier_name" id="inp_supplier_name" required value="<?php echo esc_attr( $val_name ); ?>" placeholder="e.g. Sabre, Amadeus, FlyHub" class="ifs-input-field font-bold">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_supplier_type"><?php esc_html_e( 'Category', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <select name="supplier_type" id="inp_supplier_type" class="ifs-input-field">
                                    <option value="GDS / IATA" <?php selected( $val_type, 'GDS / IATA' ); ?>><?php esc_html_e( 'GDS / IATA Consortia', 'ifs-travel-erp' ); ?></option>
                                    <option value="B2B Air Portal" <?php selected( $val_type, 'B2B Air Portal' ); ?>><?php esc_html_e( 'B2B Air Portal', 'ifs-travel-erp' ); ?></option>
                                    <option value="Visa Vendor" <?php selected( $val_type, 'Visa Vendor' ); ?>><?php esc_html_e( 'Visa Processing Vendor', 'ifs-travel-erp' ); ?></option>
                                    <option value="Hotel Wholesaler" <?php selected( $val_type, 'Hotel Wholesaler' ); ?>><?php esc_html_e( 'Hotel Wholesaler', 'ifs-travel-erp' ); ?></option>
                                    <option value="Hajj / Moallem" <?php selected( $val_type, 'Hajj / Moallem' ); ?>><?php esc_html_e( 'Hajj & Umrah Vendor', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_contact_person"><?php esc_html_e( 'Contact Person', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <input type="text" name="contact_person" id="inp_contact_person" value="<?php echo esc_attr( $val_person ); ?>" placeholder="Account Manager name" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_phone"><?php esc_html_e( 'Phone / Hotline', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <input type="text" name="phone" id="inp_phone" required value="<?php echo esc_attr( $val_phone ); ?>" placeholder="+880 ..." class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_email"><?php esc_html_e( 'Email Address', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <input type="email" name="email" id="inp_email" value="<?php echo esc_attr( $val_email ); ?>" placeholder="support@supplier.com" class="ifs-input-field">
                            </div>
                        </div>

                        <?php if ( ! $edit_data ) : ?>
                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_initial_balance"><?php esc_html_e( 'Opening Deposit (৳)', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="number" step="0.01" name="initial_balance" id="inp_initial_balance" value="<?php echo esc_attr( $val_bal ); ?>" placeholder="0.00" class="ifs-input-field font-mono font-bold color-emerald">
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="ifs-field-block <?php echo $edit_data ? 'col-span-3' : 'col-span-2'; ?>">
                            <label class="ifs-field-label" for="inp_status"><?php esc_html_e( 'Status', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <select name="status" id="inp_status" class="ifs-input-field">
                                    <option value="Active" <?php selected( $val_status, 'Active' ); ?>><?php esc_html_e( 'Active', 'ifs-travel-erp' ); ?></option>
                                    <option value="Suspended" <?php selected( $val_status, 'Suspended' ); ?>><?php esc_html_e( 'Suspended', 'ifs-travel-erp' ); ?></option>
                                    <option value="Inactive" <?php selected( $val_status, 'Inactive' ); ?>><?php esc_html_e( 'Inactive', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="ifs-action-strip" style="margin-top: 22px;">
                        <a href="<?php echo esc_url( add_query_arg( 'sub', 'list', $base_url ) ); ?>" class="ifs-btn-secondary"><?php esc_html_e( 'Cancel', 'ifs-travel-erp' ); ?></a>
                        <button type="submit" name="ifs_add_supplier_submit" class="ifs-btn-primary">
                            <span class="dashicons dashicons-saved"></span> <?php echo $edit_data ? esc_html__( 'Update Supplier', 'ifs-travel-erp' ) : esc_html__( 'Save Supplier', 'ifs-travel-erp' ); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Stylesheet: Flat Minimal UI, Zero Shadows & Strict 42px Uniform Field Heights -->
        <style>
            .ifs-suppliers-workspace { 
                max-width: 1200px; 
                margin: 20px auto; 
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; 
                color: #0f172a; 
                box-sizing: border-box; 
            }
            .ifs-suppliers-workspace *, 
            .ifs-suppliers-workspace *::before, 
            .ifs-suppliers-workspace *::after { 
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
                font-size: 14px; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                flex-shrink: 0; 
            }
            .ifs-card-title { margin: 0; font-size: 15.5px; font-weight: 800; color: #0f172a; }
            .ifs-card-desc { margin: 2px 0 0 0; font-size: 12.5px; color: #64748b; }

            .ifs-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
            .col-span-2 { grid-column: span 2; }
            .col-span-3 { grid-column: span 3; }
            @media (max-width: 820px) { 
                .ifs-grid-3 { grid-template-columns: 1fr; } 
                .col-span-2, .col-span-3 { grid-column: span 1; } 
            }

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
        <?php
    }
}