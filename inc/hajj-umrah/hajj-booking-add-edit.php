<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_hajj_booking_add_edit_page' ) ) {
    /**
     * Enterprise Pilgrim Registration Console
     * Flat Minimal UI - No Shadows & Strict 42px Uniform Field Heights
     */
    function ifs_terp_hajj_booking_add_edit_page() {
        global $wpdb;
        $table_bookings  = $wpdb->prefix . 'iterp_hajj_bookings';
        $table_customers = $wpdb->prefix . 'iterp_customers';
        $table_packages  = $wpdb->prefix . 'iterp_hajj_packages';
        $table_agents    = $wpdb->prefix . 'iterp_agents';
        $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
        
        $id       = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
        $is_edit  = ( $id > 0 );
        $message  = '';
        $errors   = array();
        $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=hajj_umrah' );

        // Enqueue Media Uploader and Select2
        if ( function_exists( 'wp_enqueue_media' ) ) {
            wp_enqueue_media();
        }
        if ( ! wp_script_is( 'select2', 'registered' ) ) {
            wp_register_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), '4.1.0-rc.0', true );
            wp_register_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0-rc.0' );
        }
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'select2' );
        wp_enqueue_style( 'select2' );

        // Process Form Submission
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_hajj_submit'] ) ) {
            check_admin_referer( 'ifs_hajj_save_action', 'ifs_hajj_nonce' );

            $customer_id        = isset( $_POST['customer_id'] ) ? absint( wp_unslash( $_POST['customer_id'] ) ) : 0;
            $agent_id           = isset( $_POST['agent_id'] ) ? absint( wp_unslash( $_POST['agent_id'] ) ) : 0;
            $supplier_id        = isset( $_POST['supplier_id'] ) ? absint( wp_unslash( $_POST['supplier_id'] ) ) : 0;
            $package_id         = isset( $_POST['package_id'] ) ? absint( wp_unslash( $_POST['package_id'] ) ) : 0;
            $mahram_customer_id = isset( $_POST['mahram_customer_id'] ) ? absint( wp_unslash( $_POST['mahram_customer_id'] ) ) : 0;
            $mahram_relation    = isset( $_POST['mahram_relation'] ) ? sanitize_text_field( wp_unslash( $_POST['mahram_relation'] ) ) : '';
            $room_sharing       = isset( $_POST['room_sharing'] ) ? sanitize_text_field( wp_unslash( $_POST['room_sharing'] ) ) : 'Quad';
            $pilgrim_type       = isset( $_POST['pilgrim_type'] ) ? sanitize_text_field( wp_unslash( $_POST['pilgrim_type'] ) ) : 'Adult';
            
            $pilgrim_id         = isset( $_POST['pilgrim_id'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['pilgrim_id'] ) ) ) : '';
            $brn_no             = isset( $_POST['brn_no'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['brn_no'] ) ) ) : '';
            $mofaza_no          = isset( $_POST['mofaza_no'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['mofaza_no'] ) ) ) : '';
            $tracking_id        = isset( $_POST['tracking_id'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['tracking_id'] ) ) ) : '';
            $nusuk_id           = isset( $_POST['nusuk_id'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['nusuk_id'] ) ) ) : '';
            $passport_expiry    = isset( $_POST['passport_expiry'] ) ? sanitize_text_field( wp_unslash( $_POST['passport_expiry'] ) ) : '';
            
            $flight_airline     = isset( $_POST['flight_airline'] ) ? sanitize_text_field( wp_unslash( $_POST['flight_airline'] ) ) : '';
            $flight_pnr         = isset( $_POST['flight_pnr'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['flight_pnr'] ) ) ) : '';
            $flight_date        = isset( $_POST['flight_date'] ) ? sanitize_text_field( wp_unslash( $_POST['flight_date'] ) ) : '';
            $return_flight_date = isset( $_POST['return_flight_date'] ) ? sanitize_text_field( wp_unslash( $_POST['return_flight_date'] ) ) : '';
            $return_flight_no   = isset( $_POST['return_flight_no'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['return_flight_no'] ) ) ) : '';
            $transport_provider = isset( $_POST['transport_provider'] ) ? sanitize_text_field( wp_unslash( $_POST['transport_provider'] ) ) : '';
            $group_leader       = isset( $_POST['group_leader'] ) ? sanitize_text_field( wp_unslash( $_POST['group_leader'] ) ) : '';
            
            $saudi_mobile       = isset( $_POST['saudi_mobile'] ) ? sanitize_text_field( wp_unslash( $_POST['saudi_mobile'] ) ) : '';
            $emergency_contact  = isset( $_POST['emergency_contact'] ) ? sanitize_text_field( wp_unslash( $_POST['emergency_contact'] ) ) : '';
            $hotel_makkah       = isset( $_POST['hotel_makkah'] ) ? sanitize_text_field( wp_unslash( $_POST['hotel_makkah'] ) ) : '';
            $hotel_madinah      = isset( $_POST['hotel_madinah'] ) ? sanitize_text_field( wp_unslash( $_POST['hotel_madinah'] ) ) : '';
            $nights_makkah      = isset( $_POST['nights_makkah'] ) ? intval( wp_unslash( $_POST['nights_makkah'] ) ) : 0;
            $nights_madinah     = isset( $_POST['nights_madinah'] ) ? intval( wp_unslash( $_POST['nights_madinah'] ) ) : 0;
            $tent_zone          = isset( $_POST['tent_zone'] ) ? sanitize_text_field( wp_unslash( $_POST['tent_zone'] ) ) : '';

            $vaccine_status     = isset( $_POST['vaccine_status'] ) ? sanitize_text_field( wp_unslash( $_POST['vaccine_status'] ) ) : 'Verified';
            $haramain_train     = isset( $_POST['haramain_train'] ) ? 1 : 0;
            $ziyarah_included   = isset( $_POST['ziyarah_included'] ) ? 1 : 0;
            $kit_delivered      = isset( $_POST['kit_delivered'] ) ? 1 : 0;
            $visa_doc_url       = isset( $_POST['visa_doc_url'] ) ? esc_url_raw( wp_unslash( $_POST['visa_doc_url'] ) ) : '';
            
            $buy_price          = isset( $_POST['buy_price'] ) ? (float) wp_unslash( $_POST['buy_price'] ) : 0;
            $sell_price         = isset( $_POST['sell_price'] ) ? (float) wp_unslash( $_POST['sell_price'] ) : 0;
            $paid_amount        = isset( $_POST['paid_amount'] ) ? (float) wp_unslash( $_POST['paid_amount'] ) : 0;
            $due_amount         = max( 0, $sell_price - $paid_amount );
            $profit             = $sell_price - $buy_price;

            $payment_status     = isset( $_POST['payment_status'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_status'] ) ) : 'Paid';
            $payment_method     = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : 'Bank Transfer';
            $transaction_id     = isset( $_POST['transaction_id'] ) ? sanitize_text_field( wp_unslash( $_POST['transaction_id'] ) ) : '';
            $visa_status        = isset( $_POST['visa_status'] ) ? sanitize_text_field( wp_unslash( $_POST['visa_status'] ) ) : 'Pending';
            $status             = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'Booked';
            $remarks            = isset( $_POST['remarks'] ) ? sanitize_textarea_field( wp_unslash( $_POST['remarks'] ) ) : '';

            if ( empty( $customer_id ) ) {
                $errors[] = esc_html__( 'Please select a registered pilgrim.', 'ifs-travel-erp' );
            }
            if ( empty( $package_id ) ) {
                $errors[] = esc_html__( 'Please select a package plan.', 'ifs-travel-erp' );
            }

            if ( empty( $errors ) ) {
                $data = array(
                    'customer_id'        => $customer_id,
                    'agent_id'           => $agent_id,
                    'supplier_id'        => $supplier_id,
                    'package_id'         => $package_id,
                    'mahram_customer_id' => $mahram_customer_id,
                    'mahram_relation'    => $mahram_relation,
                    'room_sharing'       => $room_sharing,
                    'pilgrim_type'       => $pilgrim_type,
                    'pilgrim_id'         => $pilgrim_id,
                    'brn_no'             => $brn_no,
                    'mofaza_no'          => $mofaza_no,
                    'tracking_id'        => $tracking_id,
                    'nusuk_id'           => $nusuk_id,
                    'passport_expiry'    => ! empty( $passport_expiry ) ? $passport_expiry : '1970-01-01',
                    'flight_airline'     => $flight_airline,
                    'flight_pnr'         => $flight_pnr,
                    'flight_date'        => ! empty( $flight_date ) ? $flight_date : '1970-01-01',
                    'return_flight_date' => ! empty( $return_flight_date ) ? $return_flight_date : '1970-01-01',
                    'return_flight_no'   => $return_flight_no,
                    'transport_provider' => $transport_provider,
                    'group_leader'       => $group_leader,
                    'saudi_mobile'       => $saudi_mobile,
                    'emergency_contact'  => $emergency_contact,
                    'hotel_makkah'       => $hotel_makkah,
                    'hotel_madinah'      => $hotel_madinah,
                    'nights_makkah'      => $nights_makkah,
                    'nights_madinah'     => $nights_madinah,
                    'tent_zone'          => $tent_zone,
                    'vaccine_status'     => $vaccine_status,
                    'haramain_train'     => $haramain_train,
                    'ziyarah_included'   => $ziyarah_included,
                    'kit_delivered'      => $kit_delivered,
                    'visa_doc_url'       => $visa_doc_url,
                    'buy_price'          => $buy_price,
                    'sell_price'         => $sell_price,
                    'paid_amount'        => $paid_amount,
                    'due_amount'         => $due_amount,
                    'profit'             => $profit,
                    'payment_status'     => $payment_status,
                    'payment_method'     => $payment_method,
                    'transaction_id'     => $transaction_id,
                    'visa_status'        => $visa_status,
                    'status'             => $status,
                    'remarks'            => $remarks,
                );

                if ( $is_edit ) {
                    $wpdb->update( $table_bookings, $data, array( 'id' => $id ) );
                    $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Pilgrim booking updated successfully.', 'ifs-travel-erp' ) . '</div>';
                } else {
                    $data['created_at'] = current_time( 'mysql' );
                    $data['created_by'] = get_current_user_id();
                    $wpdb->insert( $table_bookings, $data );
                    $id      = $wpdb->insert_id;
                    $is_edit = true;
                    $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . sprintf( esc_html__( 'Pilgrim registered (#HB-%s).', 'ifs-travel-erp' ), esc_html( str_pad( (string) $id, 5, '0', STR_PAD_LEFT ) ) ) . '</div>';
                }

                if ( function_exists( 'ifs_terp_log_activity' ) ) {
                    ifs_terp_log_activity( "Pilgrim Booking Saved #HB-{$id} | Customer: #CUS-{$customer_id} | Package: #PKG-{$package_id}" );
                }
            } else {
                $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> ' . implode( '<br>', $errors ) . '</div>';
            }
        }

        $row = false;
        if ( $is_edit ) {
            $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_bookings} WHERE id = %d", $id ) );
        }

        $customers = $wpdb->get_results( "SELECT id, title, full_name, mobile, passport_no, passport_expiry, gender, blood_group FROM {$table_customers} ORDER BY full_name ASC" );
        $packages  = $wpdb->get_results( "SELECT id, package_name, package_type, cost_bdt, selling_price, total_days, hotel_makkah, hotel_madinah FROM {$table_packages} ORDER BY package_name ASC" );
        $agents    = $wpdb->get_results( "SELECT id, agency_name, current_balance FROM {$table_agents} WHERE status = 'Active' ORDER BY agency_name ASC" );
        $suppliers = $wpdb->get_results( "SELECT id, supplier_name, current_balance FROM {$table_suppliers} WHERE status = 'Active' ORDER BY supplier_name ASC" );

        // Field Defaults
        $val_customer      = $is_edit ? absint( $row->customer_id ) : 0;
        $val_agent         = $is_edit ? absint( $row->agent_id ?? 0 ) : 0;
        $val_supplier      = $is_edit ? absint( $row->supplier_id ?? 0 ) : 0;
        $val_package       = $is_edit ? absint( $row->package_id ) : 0;
        $val_mahram        = $is_edit ? absint( $row->mahram_customer_id ?? 0 ) : 0;
        $val_mahram_rel    = $is_edit ? esc_attr( $row->mahram_relation ?? '' ) : '';
        $val_sharing       = $is_edit ? esc_attr( $row->room_sharing ) : 'Quad';
        $val_pilgrim_type  = $is_edit ? esc_attr( $row->pilgrim_type ?? 'Adult' ) : 'Adult';
        
        $val_pilgrim_id    = $is_edit ? esc_attr( $row->pilgrim_id ?? '' ) : '';
        $val_brn           = $is_edit ? esc_attr( $row->brn_no ?? '' ) : '';
        $val_mofaza        = $is_edit ? esc_attr( $row->mofaza_no ?? '' ) : '';
        $val_tracking      = $is_edit ? esc_attr( $row->tracking_id ?? '' ) : '';
        $val_nusuk         = $is_edit ? esc_attr( $row->nusuk_id ?? '' ) : '';
        $val_pass_expiry   = ( $is_edit && ! empty( $row->passport_expiry ) && $row->passport_expiry !== '1970-01-01' ) ? esc_attr( $row->passport_expiry ) : '';
        
        $val_airline       = $is_edit ? esc_attr( $row->flight_airline ?? '' ) : '';
        $val_pnr           = $is_edit ? esc_attr( $row->flight_pnr ?? '' ) : '';
        $val_flight        = ( $is_edit && ! empty( $row->flight_date ) && $row->flight_date !== '1970-01-01' ) ? esc_attr( $row->flight_date ) : '';
        $val_return        = ( $is_edit && ! empty( $row->return_flight_date ) && $row->return_flight_date !== '1970-01-01' ) ? esc_attr( $row->return_flight_date ) : '';
        $val_return_flight = $is_edit ? esc_attr( $row->return_flight_no ?? '' ) : '';
        $val_transport     = $is_edit ? esc_attr( $row->transport_provider ?? '' ) : '';
        $val_leader        = $is_edit ? esc_attr( $row->group_leader ?? '' ) : '';
        
        $val_saudi_mob     = $is_edit ? esc_attr( $row->saudi_mobile ?? '' ) : '';
        $val_emergency     = $is_edit ? esc_attr( $row->emergency_contact ?? '' ) : '';
        $val_makkah        = $is_edit ? esc_attr( $row->hotel_makkah ?? '' ) : '';
        $val_madinah       = $is_edit ? esc_attr( $row->hotel_madinah ?? '' ) : '';
        $val_n_makkah      = $is_edit ? intval( $row->nights_makkah ?? 0 ) : '';
        $val_n_madinah     = $is_edit ? intval( $row->nights_madinah ?? 0 ) : '';
        $val_tent_zone     = $is_edit ? esc_attr( $row->tent_zone ?? '' ) : '';

        $val_vaccine       = $is_edit ? esc_attr( $row->vaccine_status ?? 'Verified' ) : 'Verified';
        $val_train         = $is_edit ? (bool) ( $row->haramain_train ?? 0 ) : false;
        $val_ziyarah       = $is_edit ? (bool) ( $row->ziyarah_included ?? 1 ) : true;
        $val_kit           = $is_edit ? (bool) ( $row->kit_delivered ?? 0 ) : false;
        $val_doc_url       = $is_edit ? esc_url( $row->visa_doc_url ?? '' ) : '';

        $val_buy           = $is_edit ? (float) $row->buy_price : '';
        $val_sell          = $is_edit ? (float) $row->sell_price : '';
        $val_paid          = $is_edit ? (float) ( $row->paid_amount ?? 0 ) : '';
        $val_due           = $is_edit ? (float) ( $row->due_amount ?? 0 ) : 0;
        $val_profit        = $is_edit ? (float) $row->profit : 0;
        
        $val_pay_status    = $is_edit ? esc_attr( $row->payment_status ?? 'Paid' ) : 'Paid';
        $val_pay_method    = $is_edit ? esc_attr( $row->payment_method ?? 'Bank Transfer' ) : 'Bank Transfer';
        $val_txn_id        = $is_edit ? esc_attr( $row->transaction_id ?? '' ) : '';
        $val_visa_status   = $is_edit ? esc_attr( $row->visa_status ) : 'Pending';
        $val_status        = $is_edit ? esc_attr( $row->status ) : 'Booked';
        $val_remarks       = $is_edit ? esc_textarea( $row->remarks ?? '' ) : '';
        ?>

        <div class="wrap ifs-hajj-workspace">
            <?php echo wp_kses_post( $message ); ?>

            <form method="post" action="" id="ifsHajjForm" class="ifs-split-hajj-editor">
                <?php wp_nonce_field( 'ifs_hajj_save_action', 'ifs_hajj_nonce' ); ?>

                <div class="ifs-hajj-form-body">
                    
                    <!-- Section 1: Pilgrim & Package -->
                    <div class="ifs-panel-card">
                        <div class="ifs-card-header">
                            <div class="ifs-step-num">01</div>
                            <div>
                                <h3 class="ifs-card-title"><?php esc_html_e( 'Pilgrim & Package', 'ifs-travel-erp' ); ?></h3>
                                <p class="ifs-card-desc"><?php esc_html_e( 'Assign customer profile, package template, room sharing, and Mahram relation', 'ifs-travel-erp' ); ?></p>
                            </div>
                        </div>

                        <div class="ifs-grid-3">
                            <div class="ifs-field-block col-span-2">
                                <label class="ifs-field-label" for="inp_customer"><?php esc_html_e( 'Pilgrim', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                                <div class="ifs-field-wrap">
                                    <select name="customer_id" id="inp_customer" required class="ifs-input-field ifs-select2">
                                        <option value=""><?php esc_html_e( '-- Search Registered Pilgrim --', 'ifs-travel-erp' ); ?></option>
                                        <?php if ( ! empty( $customers ) ) : foreach ( $customers as $cus ) : 
                                            $t_prefix   = ! empty( $cus->title ) ? $cus->title . '. ' : '';
                                            $p_num      = ! empty( $cus->passport_no ) ? ' [PPT: ' . $cus->passport_no . ']' : '';
                                            $gender     = ! empty( $cus->gender ) ? $cus->gender : 'Male';
                                            $pass_exp   = ( ! empty( $cus->passport_expiry ) && $cus->passport_expiry !== '1970-01-01' ) ? $cus->passport_expiry : '';
                                        ?>
                                            <option value="<?php echo esc_attr( $cus->id ); ?>" 
                                                    data-name="<?php echo esc_attr( $t_prefix . $cus->full_name ); ?>"
                                                    data-passport="<?php echo esc_attr( $cus->passport_no ?: 'NOT SET' ); ?>"
                                                    data-expiry="<?php echo esc_attr( $pass_exp ); ?>"
                                                    data-gender="<?php echo esc_attr( $gender ); ?>"
                                                    data-blood="<?php echo esc_attr( $cus->blood_group ?: 'Unknown' ); ?>"
                                                    <?php selected( $val_customer, $cus->id ); ?>>
                                                <?php echo esc_html( $t_prefix . $cus->full_name . ' (' . $cus->mobile . ')' . $p_num . ' [' . $gender . ']' ); ?>
                                            </option>
                                        <?php endforeach; endif; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_pilgrim_type"><?php esc_html_e( 'Category', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <select name="pilgrim_type" id="inp_pilgrim_type" class="ifs-input-field">
                                        <option value="Adult" <?php selected( $val_pilgrim_type, 'Adult' ); ?>><?php esc_html_e( 'Adult Pilgrim', 'ifs-travel-erp' ); ?></option>
                                        <option value="Child" <?php selected( $val_pilgrim_type, 'Child' ); ?>><?php esc_html_e( 'Child (2-11 Yrs)', 'ifs-travel-erp' ); ?></option>
                                        <option value="Infant" <?php selected( $val_pilgrim_type, 'Infant' ); ?>><?php esc_html_e( 'Infant (Under 2 Yrs)', 'ifs-travel-erp' ); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block col-span-2">
                                <label class="ifs-field-label" for="inp_package"><?php esc_html_e( 'Package Plan', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                                <div class="ifs-field-wrap">
                                    <select name="package_id" id="inp_package" required class="ifs-input-field ifs-select2">
                                        <option value=""><?php esc_html_e( '-- Choose Package Plan --', 'ifs-travel-erp' ); ?></option>
                                        <?php if ( ! empty( $packages ) ) : foreach ( $packages as $pkg ) : ?>
                                            <option value="<?php echo esc_attr( $pkg->id ); ?>" 
                                                    data-name="<?php echo esc_attr( $pkg->package_name ); ?>"
                                                    data-type="<?php echo esc_attr( $pkg->package_type ); ?>"
                                                    data-cost="<?php echo esc_attr( $pkg->cost_bdt ?? 0 ); ?>"
                                                    data-sell="<?php echo esc_attr( $pkg->selling_price ?? $pkg->cost_bdt ); ?>"
                                                    data-days="<?php echo esc_attr( $pkg->total_days ?? 15 ); ?>"
                                                    data-makkah="<?php echo esc_attr( $pkg->hotel_makkah ?? 'Makkah Hotel' ); ?>"
                                                    data-madinah="<?php echo esc_attr( $pkg->hotel_madinah ?? 'Madinah Hotel' ); ?>"
                                                    <?php selected( $val_package, $pkg->id ); ?>>
                                                <?php echo esc_html( $pkg->package_name . ' (' . $pkg->package_type . ') | ' . ( $pkg->total_days ?? 15 ) . ' Days | Rate: ৳' . number_format( (float) ( $pkg->selling_price ?? $pkg->cost_bdt ), 0 ) ); ?>
                                            </option>
                                        <?php endforeach; endif; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_mahram"><?php esc_html_e( 'Mahram Guide', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <select name="mahram_customer_id" id="inp_mahram" class="ifs-input-field ifs-select2">
                                        <option value="0"><?php esc_html_e( 'None / Self / Male', 'ifs-travel-erp' ); ?></option>
                                        <?php if ( ! empty( $customers ) ) : foreach ( $customers as $cus ) : ?>
                                            <option value="<?php echo esc_attr( $cus->id ); ?>" <?php selected( $val_mahram, $cus->id ); ?>>
                                                <?php echo esc_html( $cus->full_name . ' (' . $cus->mobile . ')' ); ?>
                                            </option>
                                        <?php endforeach; endif; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_mahram_rel"><?php esc_html_e( 'Mahram Relation', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <select name="mahram_relation" id="inp_mahram_rel" class="ifs-input-field">
                                        <option value=""><?php esc_html_e( 'None / Not Applicable', 'ifs-travel-erp' ); ?></option>
                                        <option value="Spouse" <?php selected( $val_mahram_rel, 'Spouse' ); ?>><?php esc_html_e( 'Husband / Wife', 'ifs-travel-erp' ); ?></option>
                                        <option value="Father" <?php selected( $val_mahram_rel, 'Father' ); ?>><?php esc_html_e( 'Father', 'ifs-travel-erp' ); ?></option>
                                        <option value="Mother" <?php selected( $val_mahram_rel, 'Mother' ); ?>><?php esc_html_e( 'Mother', 'ifs-travel-erp' ); ?></option>
                                        <option value="Son" <?php selected( $val_mahram_rel, 'Son' ); ?>><?php esc_html_e( 'Son', 'ifs-travel-erp' ); ?></option>
                                        <option value="Daughter" <?php selected( $val_mahram_rel, 'Daughter' ); ?>><?php esc_html_e( 'Daughter', 'ifs-travel-erp' ); ?></option>
                                        <option value="Brother" <?php selected( $val_mahram_rel, 'Brother' ); ?>><?php esc_html_e( 'Brother', 'ifs-travel-erp' ); ?></option>
                                        <option value="Sister" <?php selected( $val_mahram_rel, 'Sister' ); ?>><?php esc_html_e( 'Sister', 'ifs-travel-erp' ); ?></option>
                                        <option value="Uncle" <?php selected( $val_mahram_rel, 'Uncle' ); ?>><?php esc_html_e( 'Uncle', 'ifs-travel-erp' ); ?></option>
                                        <option value="Nephew" <?php selected( $val_mahram_rel, 'Nephew' ); ?>><?php esc_html_e( 'Nephew', 'ifs-travel-erp' ); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_sharing"><?php esc_html_e( 'Room Sharing', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                                <div class="ifs-field-wrap">
                                    <select name="room_sharing" id="inp_sharing" class="ifs-input-field">
                                        <option value="Quad" <?php selected( $val_sharing, 'Quad' ); ?>><?php esc_html_e( 'Quad (4 Beds)', 'ifs-travel-erp' ); ?></option>
                                        <option value="Triple" <?php selected( $val_sharing, 'Triple' ); ?>><?php esc_html_e( 'Triple (3 Beds)', 'ifs-travel-erp' ); ?></option>
                                        <option value="Double" <?php selected( $val_sharing, 'Double' ); ?>><?php esc_html_e( 'Double (2 Beds)', 'ifs-travel-erp' ); ?></option>
                                        <option value="Single" <?php selected( $val_sharing, 'Single' ); ?>><?php esc_html_e( 'Single Private Room', 'ifs-travel-erp' ); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_agent"><?php esc_html_e( 'Sub-Agent', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <select name="agent_id" id="inp_agent" class="ifs-input-field ifs-select2">
                                        <option value="0"><?php esc_html_e( 'Direct Retail Pilgrim', 'ifs-travel-erp' ); ?></option>
                                        <?php if ( ! empty( $agents ) ) : foreach ( $agents as $ag ) : ?>
                                            <option value="<?php echo esc_attr( $ag->id ); ?>" <?php selected( $val_agent, $ag->id ); ?>>
                                                <?php echo esc_html( $ag->agency_name . ' (Bal: ৳' . number_format( (float) $ag->current_balance, 0 ) . ')' ); ?>
                                            </option>
                                        <?php endforeach; endif; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_supplier"><?php esc_html_e( 'Supplier', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <select name="supplier_id" id="inp_supplier" class="ifs-input-field ifs-select2">
                                        <option value="0"><?php esc_html_e( 'Direct Ministry Account', 'ifs-travel-erp' ); ?></option>
                                        <?php if ( ! empty( $suppliers ) ) : foreach ( $suppliers as $sup ) : ?>
                                            <option value="<?php echo esc_attr( $sup->id ); ?>" <?php selected( $val_supplier, $sup->id ); ?>>
                                                <?php echo esc_html( $sup->supplier_name . ' (Bal: ৳' . number_format( (float) $sup->current_balance, 0 ) . ')' ); ?>
                                            </option>
                                        <?php endforeach; endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Ministry Identifiers, Hotels & Logistics -->
                    <div class="ifs-panel-card">
                        <div class="ifs-card-header">
                            <div class="ifs-step-num">02</div>
                            <div>
                                <h3 class="ifs-card-title"><?php esc_html_e( 'Ministry Credentials & Stay', 'ifs-travel-erp' ); ?></h3>
                                <p class="ifs-card-desc"><?php esc_html_e( 'e-Hajj PID, Nusuk group code, MoFA/BRN numbers, hotel nights, and flight logistics', 'ifs-travel-erp' ); ?></p>
                            </div>
                        </div>

                        <div class="ifs-grid-3">
                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_tracking_id"><?php esc_html_e( 'Gov PID No', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="text" name="tracking_id" id="inp_tracking_id" value="<?php echo esc_attr( $val_tracking ); ?>" placeholder="e.g. PID-2026-98124" class="ifs-input-field uppercase font-mono font-bold">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_pilgrim_id"><?php esc_html_e( 'Badge / Serial No', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="text" name="pilgrim_id" id="inp_pilgrim_id" value="<?php echo esc_attr( $val_pilgrim_id ); ?>" placeholder="e.g. BD-014" class="ifs-input-field uppercase font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_nusuk"><?php esc_html_e( 'Nusuk Group ID', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="text" name="nusuk_id" id="inp_nusuk" value="<?php echo esc_attr( $val_nusuk ); ?>" placeholder="e.g. NSK-9908" class="ifs-input-field uppercase font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_mofaza"><?php esc_html_e( 'Saudi MoFA No', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="text" name="mofaza_no" id="inp_mofaza" value="<?php echo esc_attr( $val_mofaza ); ?>" placeholder="e.g. MOF-1029384" class="ifs-input-field uppercase font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_brn"><?php esc_html_e( 'Hotel BRN No', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="text" name="brn_no" id="inp_brn" value="<?php echo esc_attr( $val_brn ); ?>" placeholder="e.g. BRN-884920" class="ifs-input-field uppercase font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_pass_expiry"><?php esc_html_e( 'Passport Expiry', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="date" name="passport_expiry" id="inp_pass_expiry" value="<?php echo esc_attr( $val_pass_expiry ); ?>" class="ifs-input-field font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_airline"><?php esc_html_e( 'Flight Airline', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="text" name="flight_airline" id="inp_airline" value="<?php echo esc_attr( $val_airline ); ?>" placeholder="e.g. Saudia / Biman" class="ifs-input-field">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_pnr"><?php esc_html_e( 'Flight PNR', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="text" name="flight_pnr" id="inp_pnr" value="<?php echo esc_attr( $val_pnr ); ?>" placeholder="e.g. SV-98K2L" class="ifs-input-field uppercase font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_flight_date"><?php esc_html_e( 'Departure Date', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="date" name="flight_date" id="inp_flight_date" value="<?php echo esc_attr( $val_flight ); ?>" class="ifs-input-field">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_return_flight_date"><?php esc_html_e( 'Return Date', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="date" name="return_flight_date" id="inp_return_flight_date" value="<?php echo esc_attr( $val_return ); ?>" class="ifs-input-field">
                                </div>
                            </div>

                            <!-- Added Logistics Fields -->
                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_return_flight_no"><?php esc_html_e( 'Return Flight No', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="text" name="return_flight_no" id="inp_return_flight_no" value="<?php echo esc_attr( $val_return_flight ); ?>" placeholder="e.g. SV-802" class="ifs-input-field uppercase font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_transport_provider"><?php esc_html_e( 'Transport Provider / Bus No', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="text" name="transport_provider" id="inp_transport_provider" value="<?php echo esc_attr( $val_transport ); ?>" placeholder="e.g. SAPTCO Bus #12" class="ifs-input-field">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_group_leader"><?php esc_html_e( 'Group Leader / Guide', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="text" name="group_leader" id="inp_group_leader" value="<?php echo esc_attr( $val_leader ); ?>" placeholder="e.g. Maulana Ibrahim" class="ifs-input-field">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_saudi_mobile"><?php esc_html_e( 'Saudi SIM', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="text" name="saudi_mobile" id="inp_saudi_mobile" value="<?php echo esc_attr( $val_saudi_mob ); ?>" placeholder="e.g. +966 50 123 4567" class="ifs-input-field font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_emergency_contact"><?php esc_html_e( 'KSA Emergency Contact', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="text" name="emergency_contact" id="inp_emergency_contact" value="<?php echo esc_attr( $val_emergency ); ?>" placeholder="Moallem / Tent Guide No" class="ifs-input-field font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-block col-span-2">
                                <label class="ifs-field-label" for="inp_hotel_makkah"><?php esc_html_e( 'Hotel Makkah', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="text" name="hotel_makkah" id="inp_hotel_makkah" value="<?php echo esc_attr( $val_makkah ); ?>" placeholder="e.g. Swissotel Al Maqam" class="ifs-input-field">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_nights_makkah"><?php esc_html_e( 'Nights Makkah', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="number" min="0" name="nights_makkah" id="inp_nights_makkah" value="<?php echo esc_attr( $val_n_makkah ); ?>" placeholder="e.g. 10" class="ifs-input-field font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-block col-span-2">
                                <label class="ifs-field-label" for="inp_hotel_madinah"><?php esc_html_e( 'Hotel Madinah', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="text" name="hotel_madinah" id="inp_hotel_madinah" value="<?php echo esc_attr( $val_madinah ); ?>" placeholder="e.g. Anwar Al Madinah" class="ifs-input-field">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_nights_madinah"><?php esc_html_e( 'Nights Madinah', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="number" min="0" name="nights_madinah" id="inp_nights_madinah" value="<?php echo esc_attr( $val_n_madinah ); ?>" placeholder="e.g. 5" class="ifs-input-field font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_tent_zone"><?php esc_html_e( 'Mina Tent / Maktab', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="text" name="tent_zone" id="inp_tent_zone" value="<?php echo esc_attr( $val_tent_zone ); ?>" placeholder="e.g. Zone-2 Maktab 34" class="ifs-input-field">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_vaccine_status"><?php esc_html_e( 'Vaccine Status', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <select name="vaccine_status" id="inp_vaccine_status" class="ifs-input-field">
                                        <option value="Verified" <?php selected( $val_vaccine, 'Verified' ); ?>><?php esc_html_e( 'Vaccination Verified', 'ifs-travel-erp' ); ?></option>
                                        <option value="Pending" <?php selected( $val_vaccine, 'Pending' ); ?>><?php esc_html_e( 'Pending Medical Report', 'ifs-travel-erp' ); ?></option>
                                        <option value="Exempted" <?php selected( $val_vaccine, 'Exempted' ); ?>><?php esc_html_e( 'Medically Exempted', 'ifs-travel-erp' ); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_visa_status"><?php esc_html_e( 'Visa Status', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <select name="visa_status" id="inp_visa_status" class="ifs-input-field">
                                        <option value="Pending" <?php selected( $val_visa_status, 'Pending' ); ?>><?php esc_html_e( 'Pending (Docs Collection)', 'ifs-travel-erp' ); ?></option>
                                        <option value="Submitted" <?php selected( $val_visa_status, 'Submitted' ); ?>><?php esc_html_e( 'Submitted to MoFA', 'ifs-travel-erp' ); ?></option>
                                        <option value="Issued" <?php selected( $val_visa_status, 'Issued' ); ?>><?php esc_html_e( 'Visa Issued & Stamped', 'ifs-travel-erp' ); ?></option>
                                        <option value="Rejected" <?php selected( $val_visa_status, 'Rejected' ); ?>><?php esc_html_e( 'Rejected / Flagged', 'ifs-travel-erp' ); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block col-span-3">
                                <label class="ifs-field-label"><?php esc_html_e( 'Add-On Services & Kit Handover', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-checkbox-pill-grid">
                                    <label class="ifs-pill-label">
                                        <input type="checkbox" name="haramain_train" value="1" <?php checked( $val_train ); ?>>
                                        <span><span class="dashicons dashicons-car"></span> <?php esc_html_e( 'Haramain Train Included', 'ifs-travel-erp' ); ?></span>
                                    </label>
                                    <label class="ifs-pill-label">
                                        <input type="checkbox" name="ziyarah_included" value="1" <?php checked( $val_ziyarah ); ?>>
                                        <span><span class="dashicons dashicons-location-alt"></span> <?php esc_html_e( 'Guided Ziyarah Included', 'ifs-travel-erp' ); ?></span>
                                    </label>
                                    <label class="ifs-pill-label">
                                        <input type="checkbox" name="kit_delivered" value="1" <?php checked( $val_kit ); ?>>
                                        <span><span class="dashicons dashicons-portfolio"></span> <?php esc_html_e( 'Pilgrim Kit & ID Delivered', 'ifs-travel-erp' ); ?></span>
                                    </label>
                                </div>
                            </div>

                            <div class="ifs-field-block col-span-3">
                                <label class="ifs-field-label" for="inp_visa_doc_url"><?php esc_html_e( 'Visa / Nusuk Document URL', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-upload-field-row">
                                    <div class="ifs-field-wrap" style="flex: 1;">
                                        <input type="url" name="visa_doc_url" id="inp_visa_doc_url" value="<?php echo esc_url( $val_doc_url ); ?>" placeholder="https://..." class="ifs-input-field">
                                    </div>
                                    <button type="button" class="ifs-btn-upload-file" id="btn_upload_visa_doc">
                                        <span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Upload', 'ifs-travel-erp' ); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Commercial Pricing, Margin & Ledger -->
                    <div class="ifs-panel-card">
                        <div class="ifs-card-header">
                            <div class="ifs-step-num">03</div>
                            <div>
                                <h3 class="ifs-card-title"><?php esc_html_e( 'Fare & Pricing', 'ifs-travel-erp' ); ?></h3>
                                <p class="ifs-card-desc"><?php esc_html_e( 'Ground arrangement cost, pilgrim billing rate, paid collections, and calculated due balance', 'ifs-travel-erp' ); ?></p>
                            </div>
                        </div>

                        <div class="ifs-grid-3">
                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="hajj_buy"><?php esc_html_e( 'Cost Price (৳)', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                                <div class="ifs-field-wrap">
                                    <input type="number" step="0.01" name="buy_price" id="hajj_buy" required value="<?php echo esc_attr( $val_buy ); ?>" placeholder="0.00" class="ifs-input-field font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="hajj_sell"><?php esc_html_e( 'Sale Price (৳)', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                                <div class="ifs-field-wrap">
                                    <input type="number" step="0.01" name="sell_price" id="hajj_sell" required value="<?php echo esc_attr( $val_sell ); ?>" placeholder="0.00" class="ifs-input-field font-mono font-bold color-emerald">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label"><?php esc_html_e( 'Profit (৳)', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="text" id="hajj_profit" readonly value="<?php echo esc_attr( number_format( (float) $val_profit, 2 ) ); ?>" class="ifs-input-field font-mono font-bold bg-light">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="hajj_paid"><?php esc_html_e( 'Paid Amount (৳)', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="number" step="0.01" name="paid_amount" id="hajj_paid" value="<?php echo esc_attr( $val_paid ); ?>" placeholder="0.00" class="ifs-input-field font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label"><?php esc_html_e( 'Due Amount (৳)', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="text" id="hajj_due" readonly value="<?php echo esc_attr( number_format( (float) $val_due, 2 ) ); ?>" class="ifs-input-field font-mono font-bold <?php echo ( $val_due > 0 ) ? 'color-rose' : 'color-emerald'; ?>">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_pay_method"><?php esc_html_e( 'Pay Method', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <select name="payment_method" id="inp_pay_method" class="ifs-input-field">
                                        <option value="Bank Transfer" <?php selected( $val_pay_method, 'Bank Transfer' ); ?>><?php esc_html_e( 'Bank Transfer (BEFTN/RTGS)', 'ifs-travel-erp' ); ?></option>
                                        <option value="Cash" <?php selected( $val_pay_method, 'Cash' ); ?>><?php esc_html_e( 'Cash at Counter', 'ifs-travel-erp' ); ?></option>
                                        <option value="bKash / MFS" <?php selected( $val_pay_method, 'bKash / MFS' ); ?>><?php esc_html_e( 'bKash / Nagad / Rocket', 'ifs-travel-erp' ); ?></option>
                                        <option value="Cheque" <?php selected( $val_pay_method, 'Cheque' ); ?>><?php esc_html_e( 'Cheque', 'ifs-travel-erp' ); ?></option>
                                        <option value="POS Card" <?php selected( $val_pay_method, 'POS Card' ); ?>><?php esc_html_e( 'POS Card', 'ifs-travel-erp' ); ?></option>
                                        <option value="Agent Credit" <?php selected( $val_pay_method, 'Agent Credit' ); ?>><?php esc_html_e( 'Agent Credit Ledger', 'ifs-travel-erp' ); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_txn_id"><?php esc_html_e( 'Txn ID', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <input type="text" name="transaction_id" id="inp_txn_id" value="<?php echo esc_attr( $val_txn_id ); ?>" placeholder="e.g. TXN-198273 / Chq#12" class="ifs-input-field font-mono">
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_pay_status"><?php esc_html_e( 'Pay Status', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <select name="payment_status" id="inp_pay_status" class="ifs-input-field">
                                        <option value="Paid" <?php selected( $val_pay_status, 'Paid' ); ?>><?php esc_html_e( 'Fully Paid', 'ifs-travel-erp' ); ?></option>
                                        <option value="Partial" <?php selected( $val_pay_status, 'Partial' ); ?>><?php esc_html_e( 'Partially Paid', 'ifs-travel-erp' ); ?></option>
                                        <option value="Due" <?php selected( $val_pay_status, 'Due' ); ?>><?php esc_html_e( 'Due / Unpaid', 'ifs-travel-erp' ); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block">
                                <label class="ifs-field-label" for="inp_status"><?php esc_html_e( 'Status', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-field-wrap">
                                    <select name="status" id="inp_status" class="ifs-input-field">
                                        <option value="Booked" <?php selected( $val_status, 'Booked' ); ?>><?php esc_html_e( 'Booked (Token Paid)', 'ifs-travel-erp' ); ?></option>
                                        <option value="Confirmed" <?php selected( $val_status, 'Confirmed' ); ?>><?php esc_html_e( 'Confirmed & Visa Ready', 'ifs-travel-erp' ); ?></option>
                                        <option value="Completed" <?php selected( $val_status, 'Completed' ); ?>><?php esc_html_e( 'Completed (Returned)', 'ifs-travel-erp' ); ?></option>
                                        <option value="Cancelled" <?php selected( $val_status, 'Cancelled' ); ?>><?php esc_html_e( 'Cancelled / Refunded', 'ifs-travel-erp' ); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="ifs-field-block col-span-3">
                                <label class="ifs-field-label" for="inp_remarks"><?php esc_html_e( 'Remarks', 'ifs-travel-erp' ); ?></label>
                                <div class="ifs-textarea-wrap">
                                    <textarea name="remarks" id="inp_remarks" rows="2" placeholder="<?php esc_attr_e( 'e.g. Wheelchair service in Haram, Diabetic Diet, Ground Floor room request...', 'ifs-travel-erp' ); ?>" class="ifs-input-field"><?php echo esc_textarea( $val_remarks ); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Toolbar -->
                    <div class="ifs-action-strip">
                        <a href="<?php echo esc_url( add_query_arg( 'sub', 'list', $base_url ) ); ?>" class="ifs-btn-back">
                            <span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'Cancel', 'ifs-travel-erp' ); ?>
                        </a>
                        <button type="submit" name="ifs_hajj_submit" class="ifs-btn-primary">
                            <span class="dashicons dashicons-saved"></span> 
                            <?php echo $is_edit ? esc_html__( 'Update Pilgrim Booking', 'ifs-travel-erp' ) : esc_html__( 'Confirm Registration', 'ifs-travel-erp' ); ?>
                        </button>
                    </div>
                </div>

                <!-- Right Sidebar: Live Digital Pilgrim Pass Card Preview -->
                <div class="ifs-preview-sidebar">
                    <div class="ifs-preview-sticky">
                        <div class="ifs-card-preview-header">
                            <span class="dashicons dashicons-id"></span> <?php esc_html_e( 'Live Pilgrim Pass', 'ifs-travel-erp' ); ?>
                        </div>

                        <!-- Pilgrim Card Widget -->
                        <div class="ifs-pilgrim-card">
                            <div class="pilgrim-head-strip">
                                <span class="pilgrim-brand-tag" id="prev_pkg_type"><?php esc_html_e( 'HAJJ / UMRAH', 'ifs-travel-erp' ); ?></span>
                                <span class="pilgrim-sharing-badge" id="prev_sharing"><?php esc_html_e( 'QUAD ROOM', 'ifs-travel-erp' ); ?></span>
                            </div>

                            <div class="pilgrim-bio-hero">
                                <div class="pilgrim-avatar" id="prev_avatar">MR</div>
                                <div>
                                    <h4 class="pilgrim-name" id="prev_name"><?php esc_html_e( 'MOHAMMED RAHIM', 'ifs-travel-erp' ); ?></h4>
                                    <div class="pilgrim-submeta" id="prev_meta"><?php esc_html_e( 'PPT: NOT SET &bull; BLOOD: A+', 'ifs-travel-erp' ); ?></div>
                                </div>
                            </div>

                            <div class="pilgrim-package-strip">
                                <span class="pkg-label"><?php esc_html_e( 'PACKAGE PLAN', 'ifs-travel-erp' ); ?></span>
                                <strong class="pkg-val" id="prev_pkg_name"><?php esc_html_e( 'SELECT PACKAGE', 'ifs-travel-erp' ); ?></strong>
                            </div>

                            <div class="pilgrim-grid-specs font-mono">
                                <div>
                                    <span class="spec-lbl"><?php esc_html_e( 'GOV PID NO', 'ifs-travel-erp' ); ?></span>
                                    <strong class="spec-val color-amber" id="prev_pid"><?php esc_html_e( 'PENDING', 'ifs-travel-erp' ); ?></strong>
                                </div>
                                <div>
                                    <span class="spec-lbl"><?php esc_html_e( 'SAUDI MOFA', 'ifs-travel-erp' ); ?></span>
                                    <strong class="spec-val color-cyan" id="prev_mofa"><?php esc_html_e( 'PENDING', 'ifs-travel-erp' ); ?></strong>
                                </div>
                                <div>
                                    <span class="spec-lbl"><?php esc_html_e( 'HOTEL BRN', 'ifs-travel-erp' ); ?></span>
                                    <strong class="spec-val" id="prev_brn"><?php esc_html_e( '------', 'ifs-travel-erp' ); ?></strong>
                                </div>
                                <div>
                                    <span class="spec-lbl"><?php esc_html_e( 'FLIGHT DATE', 'ifs-travel-erp' ); ?></span>
                                    <strong class="spec-val color-green" id="prev_flight_disp"><?php esc_html_e( 'TBD', 'ifs-travel-erp' ); ?></strong>
                                </div>
                            </div>

                            <div class="pilgrim-fee-footer">
                                <div class="fee-row">
                                    <span><?php esc_html_e( 'TOTAL FARE:', 'ifs-travel-erp' ); ?></span>
                                    <strong class="color-green font-mono" id="prev_sell">৳0.00</strong>
                                </div>
                                <div class="fee-row" style="font-size: 11px; opacity: 0.9;">
                                    <span><?php esc_html_e( 'BALANCE DUE:', 'ifs-travel-erp' ); ?></span>
                                    <strong class="color-amber font-mono" id="prev_due">৳0.00</strong>
                                </div>
                                <span class="pilgrim-barcode font-mono" id="prev_barcode">H&lt;BGD&lt;&lt;PILGRIM&lt;&lt;MAKKAH&lt;MADINAH&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;</span>
                            </div>
                        </div>

                        <!-- Commercial Intelligence Box -->
                        <div class="ifs-intel-box">
                            <div class="intel-head"><span class="dashicons dashicons-chart-pie"></span> <?php esc_html_e( 'Margin Matrix', 'ifs-travel-erp' ); ?></div>
                            <div class="intel-body">
                                <div class="intel-row">
                                    <span><?php esc_html_e( 'Gross Margin:', 'ifs-travel-erp' ); ?></span>
                                    <strong id="intel_profit" class="color-green">৳0.00</strong>
                                </div>
                                <div class="intel-row">
                                    <span><?php esc_html_e( 'Yield Ratio:', 'ifs-travel-erp' ); ?></span>
                                    <strong id="intel_ratio">0.0%</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <style>
            .ifs-hajj-workspace { 
                max-width: 1420px; 
                margin: 20px auto; 
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; 
                color: #0f172a; 
                box-sizing: border-box; 
            }
            .ifs-hajj-workspace *, 
            .ifs-hajj-workspace *::before, 
            .ifs-hajj-workspace *::after { 
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

            .ifs-split-hajj-editor { display: grid; grid-template-columns: 1fr 390px; gap: 28px; align-items: flex-start; }
            @media (max-width: 1140px) { .ifs-split-hajj-editor { grid-template-columns: 1fr; } }

            .ifs-panel-card { 
                background: #ffffff; 
                border: 1px solid #e2e8f0; 
                border-radius: 14px; 
                padding: 26px; 
                margin-bottom: 22px; 
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
                font-size: 13px; 
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
            @media (max-width: 768px) { 
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
                text-transform: capitalize; 
                letter-spacing: 0.3px; 
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
            input[type="date"].ifs-input-field {
                cursor: pointer;
            }
            input[type="date"].ifs-input-field::-webkit-calendar-picker-indicator {
                opacity: 0.6;
                cursor: pointer;
                margin-right: -4px;
                transition: opacity 0.2s ease;
            }
            input[type="date"].ifs-input-field::-webkit-calendar-picker-indicator:hover {
                opacity: 1;
            }

            .ifs-textarea-wrap { width: 100%; }
            .ifs-textarea-wrap textarea.ifs-input-field {
                width: 100% !important;
                height: auto !important;
                min-height: 70px !important;
                padding: 10px 14px !important;
                line-height: 1.5 !important;
                border: 1px solid #cbd5e1 !important;
                border-radius: 8px !important;
                font-size: 13.5px !important;
                color: #0f172a !important;
                background-color: #ffffff !important;
                outline: none !important;
                transition: border-color 0.2s ease, background-color 0.2s ease;
                font-family: inherit;
                resize: vertical;
                display: block;
            }

            .ifs-input-field:focus { 
                border-color: #003376 !important; 
                background-color: #f8fafc !important; 
            }

            /* Select2 Flat (Height: 42px) */
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

            /* Upload Row */
            .ifs-upload-field-row { display: flex; gap: 8px; align-items: center; width: 100%; }
            .ifs-btn-upload-file {
                background: #f1f5f9;
                border: 1px solid #cbd5e1;
                height: 42px;
                padding: 0 16px;
                border-radius: 8px;
                font-size: 12.5px;
                font-weight: 600;
                color: #334155;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                white-space: nowrap;
                transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
            }
            .ifs-btn-upload-file:hover { background: #003376; color: #ffffff; border-color: #003376; }

            /* Checkbox Pills */
            .ifs-checkbox-pill-grid { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 4px; }
            .ifs-pill-label { 
                display: inline-flex; 
                align-items: center; 
                gap: 6px; 
                background: #f8fafc; 
                border: 1px solid #cbd5e1; 
                height: 38px;
                padding: 0 16px; 
                border-radius: 8px; 
                font-size: 12.5px; 
                font-weight: 600; 
                color: #334155; 
                cursor: pointer; 
                user-select: none; 
                transition: background-color 0.2s ease, border-color 0.2s ease;
            }
            .ifs-pill-label input[type="checkbox"] { margin: 0; }
            .ifs-pill-label:has(input:checked) { background: #eff6ff; border-color: #003376; color: #003376; }
            .ifs-pill-label .dashicons { font-size: 16px; width: 16px; height: 16px; }

            .uppercase { text-transform: uppercase; }
            .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important; }
            .font-bold { font-weight: 700; }
            .bg-light { background: #f8fafc !important; }
            .color-emerald { color: #059669 !important; }
            .color-rose { color: #dc2626 !important; }

            .profit-positive { background: #f0fdf4 !important; color: #166534 !important; border-color: #bbf7d0 !important; }
            .profit-negative { background: #fef2f2 !important; color: #dc2626 !important; border-color: #fecaca !important; }

            /* Action Toolbar */
            .ifs-action-strip { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; }
            .ifs-btn-back { color: #64748b; text-decoration: none; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; transition: color 0.2s ease; }
            .ifs-btn-back:hover { color: #0f172a; }
            .ifs-btn-primary {
                background: #003376;
                color: #ffffff !important;
                border: none;
                height: 42px;
                padding: 0 26px;
                border-radius: 8px;
                font-size: 13.5px;
                font-weight: 700;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: background-color 0.2s ease;
            }
            .ifs-btn-primary:hover { background: #0284c7; }

            /* Right Preview Sidebar */
            .ifs-preview-sticky { position: sticky; top: 30px; }
            .ifs-card-preview-header { font-size: 12px; font-weight: 800; text-transform: capitalize; letter-spacing: 0.3px; color: #475569; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }

            .ifs-pilgrim-card {
                background: #00224f;
                border-radius: 16px;
                padding: 22px;
                color: #ffffff;
                border: 1px solid #1e3a8a;
                position: relative;
                overflow: hidden;
            }

            .pilgrim-head-strip { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px dashed rgba(255, 255, 255, 0.2); }
            .pilgrim-brand-tag { font-size: 11px; font-weight: 800; letter-spacing: 0.8px; color: #7dd3fc; text-transform: uppercase; }
            .pilgrim-sharing-badge { background: rgba(255, 255, 255, 0.18); padding: 2px 8px; border-radius: 6px; font-size: 9.5px; font-weight: 800; text-transform: uppercase; }

            .pilgrim-bio-hero { display: flex; align-items: center; gap: 14px; margin-bottom: 16px; }
            .pilgrim-avatar { width: 46px; height: 46px; border-radius: 12px; background: rgba(255, 255, 255, 0.15); border: 2px solid rgba(255, 255, 255, 0.4); display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 15px; flex-shrink: 0; }
            .pilgrim-name { margin: 0; font-size: 14px; font-weight: 800; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 210px; }
            .pilgrim-submeta { font-size: 11px; color: #bae6fd; margin-top: 2px; }

            .pilgrim-package-strip { background: rgba(0, 0, 0, 0.35); padding: 8px 12px; border-radius: 8px; margin-bottom: 14px; border: 1px solid rgba(255, 255, 255, 0.08); }
            .pkg-label { font-size: 8px; font-weight: 800; color: #7dd3fc; letter-spacing: 0.5px; display: block; margin-bottom: 2px; }
            .pkg-val { font-size: 12px; font-weight: 700; color: #ffffff; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

            .pilgrim-grid-specs { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding-bottom: 14px; margin-bottom: 12px; border-bottom: 1px dashed rgba(255, 255, 255, 0.2); }
            .spec-lbl { font-size: 8px; font-weight: 800; color: #7dd3fc; letter-spacing: 0.5px; display: block; margin-bottom: 2px; }
            .spec-val { font-size: 11.5px; font-weight: 700; color: #ffffff; display: block; }
            .color-cyan { color: #38bdf8 !important; }
            .color-amber { color: #fde047 !important; }
            .color-green { color: #86efac !important; }

            .pilgrim-fee-footer { display: flex; flex-direction: column; gap: 5px; }
            .fee-row { display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #d1fae5; }
            .fee-row strong { font-size: 13.5px; }
            .pilgrim-barcode { font-size: 8px; color: #7dd3fc; letter-spacing: 1px; text-align: center; margin-top: 4px; }

            /* Intelligence Card */
            .ifs-intel-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-top: 18px; }
            .intel-head { font-size: 12px; font-weight: 800; color: #003376; text-transform: capitalize; letter-spacing: 0.3px; display: flex; align-items: center; gap: 6px; margin-bottom: 10px; }
            .intel-head .dashicons { color: #0284c7; font-size: 16px; width: 16px; height: 16px; }
            .intel-body { display: flex; flex-direction: column; gap: 6px; }
            .intel-row { display: flex; justify-content: space-between; font-size: 12.5px; color: #475569; }
            .intel-row strong { font-weight: 800; font-size: 13.5px; }
        </style>

        <!-- Real-Time Interactive Script -->
        <script>
        jQuery(document).ready(function($) {
            if ($.fn.select2) {
                $('.ifs-select2').select2({ width: '100%', allowClear: false });
            }

            $('#btn_upload_visa_doc').on('click', function(e) {
                e.preventDefault();
                const customUploader = wp.media({
                    title: 'Select or Upload Document',
                    button: { text: 'Attach Document' },
                    multiple: false
                }).on('select', function() {
                    const attachment = customUploader.state().get('selection').first().toJSON();
                    $('#inp_visa_doc_url').val(attachment.url);
                }).open();
            });

            const inpCustomer   = $('#inp_customer');
            const inpPackage    = $('#inp_package');
            const inpSharing    = $('#inp_sharing');
            const inpBrn        = $('#inp_brn');
            const inpMofaza     = $('#inp_mofaza');
            const inpPid        = $('#inp_tracking_id');
            const inpFlight     = $('#inp_flight_date');
            const inpPassExpiry = $('#inp_pass_expiry');
            const inpHotelMak   = $('#inp_hotel_makkah');
            const inpHotelMad   = $('#inp_hotel_madinah');
            
            const inpBuy        = $('#hajj_buy');
            const inpSell       = $('#hajj_sell');
            const inpPaid       = $('#hajj_paid');
            const dueDisplay    = $('#hajj_due');
            const payStatus     = $('#inp_pay_status');

            const prevName      = $('#prev_name');
            const prevAvatar    = $('#prev_avatar');
            const prevMeta      = $('#prev_meta');
            const prevPkgType   = $('#prev_pkg_type');
            const prevPkgName   = $('#prev_pkg_name');
            const prevSharing   = $('#prev_sharing');
            const prevPid       = $('#prev_pid');
            const prevMofa      = $('#prev_mofa');
            const prevBrn       = $('#prev_brn');
            const prevFlightDisp = $('#prev_flight_disp');
            const prevSell      = $('#prev_sell');
            const prevDue       = $('#prev_due');
            const profitDisplay = $('#hajj_profit');
            const intelProfit   = $('#intel_profit');
            const intelRatio    = $('#intel_ratio');

            // Auto-fill pricing & hotels on package select
            inpPackage.on('change', function() {
                const opt = $(this).find(':selected');
                if (opt.val() && opt.val() !== '') {
                    const costVal    = opt.attr('data-cost');
                    const sellVal    = opt.attr('data-sell');
                    const makkahVal  = opt.attr('data-makkah');
                    const madinahVal = opt.attr('data-madinah');
                    
                    if (inpBuy.length && (!inpBuy.val() || inpBuy.val() == '0' || inpBuy.val() == '0.00')) {
                        inpBuy.val(costVal || '0.00');
                    }
                    if (inpSell.length && (!inpSell.val() || inpSell.val() == '0' || inpSell.val() == '0.00')) {
                        inpSell.val(sellVal || '0.00');
                    }
                    if (inpHotelMak.length && !inpHotelMak.val()) {
                        inpHotelMak.val(makkahVal || '');
                    }
                    if (inpHotelMad.length && !inpHotelMad.val()) {
                        inpHotelMad.val(madinahVal || '');
                    }
                }
                updatePilgrimCard();
            });

            // Auto-fill passport expiry from customer profile
            inpCustomer.on('change', function() {
                const opt = $(this).find(':selected');
                if (opt.val() && opt.val() !== '') {
                    const expVal = opt.attr('data-expiry');
                    if (expVal && inpPassExpiry.length && !inpPassExpiry.val()) {
                        inpPassExpiry.val(expVal);
                    }
                }
                updatePilgrimCard();
            });

            function updatePilgrimCard() {
                const cusOpt = inpCustomer.find(':selected');
                if (cusOpt.val() && cusOpt.val() !== '') {
                    const paxName = cusOpt.attr('data-name') || 'MOHAMMED RAHIM';
                    const ppt     = cusOpt.attr('data-passport') || 'NOT SET';
                    const blood   = cusOpt.attr('data-blood') || 'Unknown';

                    prevName.text(paxName.toUpperCase());
                    prevMeta.text('PPT: ' + ppt + ' • BLOOD: ' + blood);

                    const parts = paxName.trim().split(' ');
                    prevAvatar.text(parts.length > 1 ? (parts[0][0] + parts[parts.length-1][0]).toUpperCase() : parts[0].slice(0, 2).toUpperCase());
                } else {
                    prevName.text('SELECT PILGRIM');
                    prevMeta.text('PPT: NOT SET • BLOOD: N/A');
                    prevAvatar.text('HP');
                }

                const pkgOpt = inpPackage.find(':selected');
                if (pkgOpt.val() && pkgOpt.val() !== '') {
                    prevPkgName.text(pkgOpt.attr('data-name') || 'PACKAGE');
                    prevPkgType.text((pkgOpt.attr('data-type') || 'HAJJ / UMRAH').toUpperCase());
                } else {
                    prevPkgName.text('SELECT PACKAGE');
                    prevPkgType.text('HAJJ / UMRAH');
                }

                prevSharing.text((inpSharing.val() || 'QUAD').toUpperCase() + ' ROOM');
                prevPid.text(inpPid.val().trim() ? inpPid.val().trim().toUpperCase() : 'PENDING');
                prevMofa.text(inpMofaza.val().trim() ? inpMofaza.val().trim().toUpperCase() : 'PENDING');
                prevBrn.text(inpBrn.val().trim() ? inpBrn.val().trim().toUpperCase() : '------');

                if (inpFlight.val()) {
                    const d = new Date(inpFlight.val());
                    if (!isNaN(d)) {
                        prevFlightDisp.text(d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }).toUpperCase());
                    }
                } else {
                    prevFlightDisp.text('TBD');
                }

                const buyVal  = parseFloat(inpBuy.val()) || 0;
                const sellVal = parseFloat(inpSell.val()) || 0;
                const paidVal = parseFloat(inpPaid.val()) || 0;
                
                const profit = sellVal - buyVal;
                const due    = Math.max(0, sellVal - paidVal);
                const ratio  = sellVal > 0 ? ((profit / sellVal) * 100).toFixed(1) : '0.0';

                prevSell.text('৳' + sellVal.toLocaleString('en-US', { minimumFractionDigits: 2 }));
                prevDue.text('৳' + due.toLocaleString('en-US', { minimumFractionDigits: 2 }));
                dueDisplay.val(due.toFixed(2));
                dueDisplay.css('color', due > 0 ? '#dc2626' : '#059669');

                profitDisplay.val(profit.toFixed(2));
                profitDisplay.attr('class', 'ifs-input-field font-mono font-bold ' + (profit >= 0 ? 'profit-positive' : 'profit-negative'));

                intelProfit.text('৳' + profit.toLocaleString('en-US', { minimumFractionDigits: 2 }));
                intelProfit.attr('class', profit >= 0 ? 'color-green' : 'color-rose');
                intelRatio.text(ratio + '%');

                if (sellVal > 0 && payStatus.length) {
                    if (paidVal >= sellVal) {
                        payStatus.val('Paid');
                    } else if (paidVal > 0 && paidVal < sellVal) {
                        payStatus.val('Partial');
                    } else {
                        payStatus.val('Due');
                    }
                }
            }

            const watchList = [inpCustomer, inpPackage, inpSharing, inpBrn, inpMofaza, inpPid, inpFlight, inpBuy, inpSell, inpPaid];
            watchList.forEach(el => {
                if (el.length) {
                    el.on('input change keyup', updatePilgrimCard);
                }
            });

            updatePilgrimCard();
        });
        </script>
        <?php
    }
}