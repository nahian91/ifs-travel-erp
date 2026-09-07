<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_hotels_add_edit_page' ) ) {
    /**
     * Issue / Edit Hotel Voucher Form Page
     * Features: Flat Minimal UI, No Shadows, Strict 42px Equal Field Heights, 
     * Select2 Normalization, Payment Ledger & Live Financial Calculation
     */
    function ifs_terp_hotels_add_edit_page() {
        global $wpdb;
        $table_hotels    = $wpdb->prefix . 'iterp_hotel_bookings';
        $table_props     = $wpdb->prefix . 'iterp_hotel_properties';
        $table_customers = $wpdb->prefix . 'iterp_customers';
        $table_agents    = $wpdb->prefix . 'iterp_agents';
        $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
        $table_ledger    = $wpdb->prefix . 'iterp_ledger';
        $base_url        = admin_url( 'admin.php?page=ifs_travel_erp&tab=hotels' );

        // Enqueue Select2 assets
        if ( ! wp_script_is( 'select2', 'registered' ) ) {
            wp_register_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), '4.1.0-rc.0', true );
            wp_register_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0-rc.0' );
        }
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'select2' );
        wp_enqueue_style( 'select2' );

        $edit_id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
        $is_edit = ( $edit_id > 0 );
        $message = '';
        $errors  = array();

        /* =========================================================================
           1. BACKEND FORM SUBMISSION HANDLER (ADD / UPDATE)
           ========================================================================= */
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_hotel_submit'] ) ) {
            check_admin_referer( 'ifs_hotel_action', 'ifs_hotel_nonce' );

            $customer_id     = isset( $_POST['customer_id'] ) ? absint( wp_unslash( $_POST['customer_id'] ) ) : 0;
            $agent_id        = isset( $_POST['agent_id'] ) ? absint( wp_unslash( $_POST['agent_id'] ) ) : 0;
            $supplier_id     = isset( $_POST['supplier_id'] ) ? absint( wp_unslash( $_POST['supplier_id'] ) ) : 0;
            $hotel_name      = isset( $_POST['hotel_name'] ) ? sanitize_text_field( wp_unslash( $_POST['hotel_name'] ) ) : '';
            $city            = isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '';
            $room_type       = isset( $_POST['room_type'] ) ? sanitize_text_field( wp_unslash( $_POST['room_type'] ) ) : '';
            $meal_plan       = isset( $_POST['meal_plan'] ) ? sanitize_text_field( wp_unslash( $_POST['meal_plan'] ) ) : '';
            $check_in        = isset( $_POST['check_in'] ) ? sanitize_text_field( wp_unslash( $_POST['check_in'] ) ) : '';
            $check_out       = isset( $_POST['check_out'] ) ? sanitize_text_field( wp_unslash( $_POST['check_out'] ) ) : '';
            $no_of_rooms     = isset( $_POST['no_of_rooms'] ) ? max( 1, intval( wp_unslash( $_POST['no_of_rooms'] ) ) ) : 1;
            $adult_count     = isset( $_POST['adult_count'] ) ? max( 1, intval( wp_unslash( $_POST['adult_count'] ) ) ) : 1;
            $child_count     = isset( $_POST['child_count'] ) ? max( 0, intval( wp_unslash( $_POST['child_count'] ) ) ) : 0;
            $voucher_no      = isset( $_POST['voucher_no'] ) ? sanitize_text_field( wp_unslash( $_POST['voucher_no'] ) ) : '';
            $confirmation_no = isset( $_POST['confirmation_no'] ) ? sanitize_text_field( wp_unslash( $_POST['confirmation_no'] ) ) : '';
            
            $buy_price       = isset( $_POST['buy_price'] ) ? (float) wp_unslash( $_POST['buy_price'] ) : 0;
            $sell_price      = isset( $_POST['sell_price'] ) ? (float) wp_unslash( $_POST['sell_price'] ) : 0;
            $paid_amount     = isset( $_POST['paid_amount'] ) ? (float) wp_unslash( $_POST['paid_amount'] ) : 0;
            $due_amount      = max( 0, $sell_price - $paid_amount );
            $profit          = $sell_price - $buy_price;

            $payment_status  = isset( $_POST['payment_status'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_status'] ) ) : 'Unpaid';
            $payment_method  = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : 'Bank Transfer';
            $status          = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'Confirmed';
            $special_req     = isset( $_POST['special_req'] ) ? sanitize_textarea_field( wp_unslash( $_POST['special_req'] ) ) : '';

            if ( empty( $customer_id ) ) {
                $errors[] = esc_html__( 'Please select a registered guest.', 'ifs-travel-erp' );
            }
            if ( empty( $hotel_name ) || empty( $city ) ) {
                $errors[] = esc_html__( 'Hotel name and city location are required.', 'ifs-travel-erp' );
            }
            if ( empty( $check_in ) || empty( $check_out ) ) {
                $errors[] = esc_html__( 'Check-in and check-out dates are required.', 'ifs-travel-erp' );
            }

            if ( empty( $errors ) ) {
                $data = array(
                    'customer_id'     => $customer_id,
                    'agent_id'        => $agent_id,
                    'supplier_id'     => $supplier_id,
                    'hotel_name'      => $hotel_name,
                    'city'            => $city,
                    'room_type'       => $room_type,
                    'meal_plan'       => $meal_plan,
                    'check_in'        => $check_in,
                    'check_out'       => $check_out,
                    'no_of_rooms'     => $no_of_rooms,
                    'adult_count'     => $adult_count,
                    'child_count'     => $child_count,
                    'voucher_no'      => $voucher_no,
                    'confirmation_no' => $confirmation_no,
                    'buy_price'       => $buy_price,
                    'sell_price'      => $sell_price,
                    'paid_amount'     => $paid_amount,
                    'due_amount'      => $due_amount,
                    'profit'          => $profit,
                    'payment_status'  => $payment_status,
                    'payment_method'  => $payment_method,
                    'status'          => $status,
                    'special_req'     => $special_req,
                );

                if ( $is_edit ) {
                    $wpdb->update( $table_hotels, $data, array( 'id' => $edit_id ) );
                    $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Hotel reservation updated successfully.', 'ifs-travel-erp' ) . '</div>';
                } else {
                    $data['created_at'] = current_time( 'mysql' );
                    $wpdb->insert( $table_hotels, $data );
                    $edit_id = $wpdb->insert_id;
                    $is_edit = true;

                    // Auto-post payment to General Ledger if paid > 0
                    if ( $paid_amount > 0 ) {
                        $wpdb->insert(
                            $table_ledger,
                            array(
                                'transaction_type' => 'Income',
                                'category'         => 'Hotel Reservation Payment',
                                'amount'           => $paid_amount,
                                'payment_method'   => $payment_method,
                                'reference_no'     => $voucher_no ?: 'HTL-' . $edit_id,
                                'description'      => 'Payment for Hotel ' . $hotel_name . ' (Voucher #' . $edit_id . ')',
                                'transaction_date' => current_time( 'mysql' ),
                                'logged_by'        => get_current_user_id()
                            ),
                            array( '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%d' )
                        );
                    }

                    $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . sprintf( esc_html__( 'Hotel voucher confirmed (#HTL-%s).', 'ifs-travel-erp' ), esc_html( str_pad( (string) $edit_id, 5, '0', STR_PAD_LEFT ) ) ) . '</div>';
                }

                if ( function_exists( 'ifs_terp_log_activity' ) ) {
                    ifs_terp_log_activity( "Processed Hotel Booking #HTL-{$edit_id} at {$hotel_name} ({$city})" );
                }
            } else {
                $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> ' . implode( '<br>', $errors ) . '</div>';
            }
        }

        $edit_data = false;
        if ( $edit_id > 0 ) {
            $edit_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_hotels} WHERE id = %d", $edit_id ) );
        }

        $customers = $wpdb->get_results( "SELECT id, full_name, mobile, passport_no FROM {$table_customers} ORDER BY full_name ASC" );
        $agents    = $wpdb->get_results( "SELECT id, agency_name FROM {$table_agents} WHERE status = 'Active' ORDER BY agency_name ASC" );
        $suppliers = $wpdb->get_results( "SELECT id, supplier_name FROM {$table_suppliers} WHERE status = 'Active' ORDER BY supplier_name ASC" );
        $props     = $wpdb->get_results( "SELECT * FROM {$table_props} ORDER BY property_name ASC" );
        
        $val_customer = $edit_data ? absint( $edit_data->customer_id ) : 0;
        $val_agent    = $edit_data ? absint( $edit_data->agent_id ?? 0 ) : 0;
        $val_supplier = $edit_data ? absint( $edit_data->supplier_id ?? 0 ) : 0;
        $val_hotel    = $edit_data ? esc_attr( $edit_data->hotel_name ) : '';
        $val_city     = $edit_data ? esc_attr( $edit_data->city ) : '';
        $val_room     = $edit_data ? esc_attr( $edit_data->room_type ) : 'Deluxe Double Room';
        $val_meal     = $edit_data ? esc_attr( $edit_data->meal_plan ?? 'Bed & Breakfast (BB)' ) : 'Bed & Breakfast (BB)';
        $val_in       = $edit_data ? esc_attr( $edit_data->check_in ) : gmdate( 'Y-m-d', strtotime( '+2 days' ) );
        $val_out      = $edit_data ? esc_attr( $edit_data->check_out ) : gmdate( 'Y-m-d', strtotime( '+7 days' ) );
        $val_rooms    = $edit_data && isset( $edit_data->no_of_rooms ) ? intval( $edit_data->no_of_rooms ) : 1;
        $val_adults   = $edit_data && isset( $edit_data->adult_count ) ? intval( $edit_data->adult_count ) : 2;
        $val_child    = $edit_data && isset( $edit_data->child_count ) ? intval( $edit_data->child_count ) : 0;
        $val_voucher  = $edit_data ? esc_attr( $edit_data->voucher_no ) : 'VCR-' . wp_rand( 100000, 999999 );
        $val_confirm  = $edit_data ? esc_attr( $edit_data->confirmation_no ?? '' ) : '';
        
        $val_buy      = $edit_data ? (float) $edit_data->buy_price : '';
        $val_sell     = $edit_data ? (float) $edit_data->sell_price : '';
        $val_paid     = $edit_data ? (float) $edit_data->paid_amount ?? 0 : '';
        $val_due      = $edit_data ? (float) $edit_data->due_amount ?? 0 : 0;
        $val_profit   = $edit_data ? (float) $edit_data->profit : 0;
        
        $val_status   = $edit_data ? esc_attr( $edit_data->status ) : 'Confirmed';
        $val_pay_stat = $edit_data ? esc_attr( $edit_data->payment_status ?? 'Unpaid' ) : 'Unpaid';
        $val_pay_meth = $edit_data ? esc_attr( $edit_data->payment_method ?? 'Bank Transfer' ) : 'Bank Transfer';
        $val_req      = $edit_data ? esc_textarea( $edit_data->special_req ?? '' ) : '';
        ?>
        
        <div class="wrap ifs-hotels-workspace">
            <?php echo wp_kses_post( $message ); ?>

            <form method="post" action="">
                <?php wp_nonce_field( 'ifs_hotel_action', 'ifs_hotel_nonce' ); ?>
                <?php if ( $edit_id > 0 ) : ?>
                    <input type="hidden" name="edit_id" value="<?php echo esc_attr( $edit_id ); ?>">
                <?php endif; ?>

                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num"><?php echo $edit_data ? '✎' : '+'; ?></div>
                        <div>
                            <h3 class="ifs-card-title"><?php echo $edit_data ? esc_html__( 'Edit Hotel Reservation', 'ifs-travel-erp' ) : esc_html__( 'Issue New Hotel Voucher', 'ifs-travel-erp' ); ?></h3>
                            <p class="ifs-card-desc"><?php esc_html_e( 'Select registered guest, choose contracted property template or customize room booking', 'ifs-travel-erp' ); ?></p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <div class="ifs-field-block col-span-3">
                            <label class="ifs-field-label" for="inp_prop_template"><?php esc_html_e( 'Auto-Fill from Contracted Hotel Property', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <select id="inp_prop_template" class="ifs-input-field ifs-select2">
                                    <option value=""><?php esc_html_e( '-- Select Contracted Hotel or Fill Manually --', 'ifs-travel-erp' ); ?></option>
                                    <?php if ( ! empty( $props ) ) : foreach ( $props as $pr ) : ?>
                                        <option value="<?php echo esc_attr( $pr->id ); ?>"
                                                data-name="<?php echo esc_attr( $pr->property_name ); ?>"
                                                data-city="<?php echo esc_attr( $pr->city . ', ' . $pr->country ); ?>"
                                                data-rate="<?php echo esc_attr( $pr->contract_rate ); ?>"
                                                data-sell="<?php echo esc_attr( $pr->standard_sell ); ?>">
                                            <?php echo esc_html( $pr->property_name . ' (' . $pr->city . ') - ৳' . number_format( (float) $pr->standard_sell, 0 ) . '/N' ); ?>
                                        </option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-2">
                            <label class="ifs-field-label" for="inp_customer"><?php esc_html_e( 'Guest Name', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <select name="customer_id" id="inp_customer" required class="ifs-input-field ifs-select2">
                                    <option value=""><?php esc_html_e( '-- Search & Choose Guest --', 'ifs-travel-erp' ); ?></option>
                                    <?php if ( ! empty( $customers ) ) : foreach ( $customers as $cus ) : ?>
                                        <option value="<?php echo esc_attr( $cus->id ); ?>" <?php selected( $val_customer, $cus->id ); ?>>
                                            <?php echo esc_html( $cus->full_name . ' (' . $cus->mobile . ')' . ( ! empty( $cus->passport_no ) ? ' [PPT: ' . $cus->passport_no . ']' : '' ) ); ?>
                                        </option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_agent"><?php esc_html_e( 'B2B Sub-Agent', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <select name="agent_id" id="inp_agent" class="ifs-input-field ifs-select2">
                                    <option value="0"><?php esc_html_e( 'Direct Retail Customer', 'ifs-travel-erp' ); ?></option>
                                    <?php if ( ! empty( $agents ) ) : foreach ( $agents as $ag ) : ?>
                                        <option value="<?php echo esc_attr( $ag->id ); ?>" <?php selected( $val_agent, $ag->id ); ?>>
                                            <?php echo esc_html( $ag->agency_name ); ?>
                                        </option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-2">
                            <label class="ifs-field-label" for="inp_hotel_name"><?php esc_html_e( 'Hotel Name', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <input type="text" name="hotel_name" id="inp_hotel_name" required value="<?php echo esc_attr( $val_hotel ); ?>" placeholder="e.g. Swissotel Makkah" class="ifs-input-field font-bold">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_city"><?php esc_html_e( 'City & Country', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <input type="text" name="city" id="inp_city" required value="<?php echo esc_attr( $val_city ); ?>" placeholder="e.g. Makkah, Saudi Arabia" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_room_type"><?php esc_html_e( 'Room Category', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <input type="text" name="room_type" id="inp_room_type" required value="<?php echo esc_attr( $val_room ); ?>" placeholder="e.g. Deluxe Double Room" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_meal_plan"><?php esc_html_e( 'Meal Plan', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <select name="meal_plan" id="inp_meal_plan" class="ifs-input-field">
                                    <option value="Room Only (RO)" <?php selected( $val_meal, 'Room Only (RO)' ); ?>><?php esc_html_e( 'Room Only (RO)', 'ifs-travel-erp' ); ?></option>
                                    <option value="Bed & Breakfast (BB)" <?php selected( $val_meal, 'Bed & Breakfast (BB)' ); ?>><?php esc_html_e( 'Bed & Breakfast (BB)', 'ifs-travel-erp' ); ?></option>
                                    <option value="Half Board (HB)" <?php selected( $val_meal, 'Half Board (HB)' ); ?>><?php esc_html_e( 'Half Board (HB)', 'ifs-travel-erp' ); ?></option>
                                    <option value="Full Board (FB)" <?php selected( $val_meal, 'Full Board (FB)' ); ?>><?php esc_html_e( 'Full Board (FB)', 'ifs-travel-erp' ); ?></option>
                                    <option value="All Inclusive (AI)" <?php selected( $val_meal, 'All Inclusive (AI)' ); ?>><?php esc_html_e( 'All Inclusive (AI)', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_supplier"><?php esc_html_e( 'Wholesaler / Supplier', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <select name="supplier_id" id="inp_supplier" class="ifs-input-field ifs-select2">
                                    <option value="0"><?php esc_html_e( 'Direct Contracting', 'ifs-travel-erp' ); ?></option>
                                    <?php if ( ! empty( $suppliers ) ) : foreach ( $suppliers as $sup ) : ?>
                                        <option value="<?php echo esc_attr( $sup->id ); ?>" <?php selected( $val_supplier, $sup->id ); ?>>
                                            <?php echo esc_html( $sup->supplier_name ); ?>
                                        </option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_check_in"><?php esc_html_e( 'Check-In Date', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <input type="date" name="check_in" id="inp_check_in" required value="<?php echo esc_attr( $val_in ); ?>" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_check_out"><?php esc_html_e( 'Check-Out Date', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <input type="date" name="check_out" id="inp_check_out" required value="<?php echo esc_attr( $val_out ); ?>" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_no_of_rooms"><?php esc_html_e( 'Number of Rooms', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <input type="number" name="no_of_rooms" id="inp_no_of_rooms" min="1" value="<?php echo esc_attr( $val_rooms ); ?>" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_adult_count"><?php esc_html_e( 'Adult Guests', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <input type="number" name="adult_count" id="inp_adult_count" min="1" value="<?php echo esc_attr( $val_adults ); ?>" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_child_count"><?php esc_html_e( 'Children Guests', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <input type="number" name="child_count" id="inp_child_count" min="0" value="<?php echo esc_attr( $val_child ); ?>" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_voucher_no"><?php esc_html_e( 'Voucher Number', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <input type="text" name="voucher_no" id="inp_voucher_no" value="<?php echo esc_attr( $val_voucher ); ?>" class="ifs-input-field font-mono uppercase">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_confirmation_no"><?php esc_html_e( 'Hotel Confirmation Ref', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <input type="text" name="confirmation_no" id="inp_confirmation_no" value="<?php echo esc_attr( $val_confirm ); ?>" placeholder="e.g. CRS-992109" class="ifs-input-field font-mono uppercase">
                            </div>
                        </div>

                        <!-- Commercial Ledger Breakdown -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_buy_price"><?php esc_html_e( 'Cost Rate (৳)', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <input type="number" step="0.01" name="buy_price" id="inp_buy_price" required value="<?php echo esc_attr( $val_buy ); ?>" placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_sell_price"><?php esc_html_e( 'Sell Price (৳)', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <input type="number" step="0.01" name="sell_price" id="inp_sell_price" required value="<?php echo esc_attr( $val_sell ); ?>" placeholder="0.00" class="ifs-input-field font-mono font-bold color-emerald">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label"><?php esc_html_e( 'Net Profit (৳)', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <input type="text" id="inp_profit" readonly value="<?php echo esc_attr( number_format( $val_profit, 2 ) ); ?>" class="ifs-input-field font-mono font-bold bg-light">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_paid_amount"><?php esc_html_e( 'Paid Amount (৳)', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <input type="number" step="0.01" name="paid_amount" id="inp_paid_amount" value="<?php echo esc_attr( $val_paid ); ?>" placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label"><?php esc_html_e( 'Remaining Due Balance (৳)', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <input type="text" id="inp_due_amount" readonly value="<?php echo esc_attr( number_format( $val_due, 2 ) ); ?>" class="ifs-input-field font-mono font-bold <?php echo ( $val_due > 0 ) ? 'color-rose' : 'color-emerald'; ?>">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_pay_method"><?php esc_html_e( 'Payment Method', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <select name="payment_method" id="inp_pay_method" class="ifs-input-field">
                                    <option value="Bank Transfer" <?php selected( $val_pay_meth, 'Bank Transfer' ); ?>><?php esc_html_e( 'Bank Transfer', 'ifs-travel-erp' ); ?></option>
                                    <option value="Cash" <?php selected( $val_pay_meth, 'Cash' ); ?>><?php esc_html_e( 'Cash at Counter', 'ifs-travel-erp' ); ?></option>
                                    <option value="bKash / MFS" <?php selected( $val_pay_meth, 'bKash / MFS' ); ?>><?php esc_html_e( 'bKash / Nagad', 'ifs-travel-erp' ); ?></option>
                                    <option value="Agent Credit" <?php selected( $val_pay_meth, 'Agent Credit' ); ?>><?php esc_html_e( 'Agent Credit Ledger', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_pay_status"><?php esc_html_e( 'Payment Status', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <select name="payment_status" id="inp_pay_status" class="ifs-input-field">
                                    <option value="Paid" <?php selected( $val_pay_stat, 'Paid' ); ?>><?php esc_html_e( 'Fully Paid', 'ifs-travel-erp' ); ?></option>
                                    <option value="Partial" <?php selected( $val_pay_stat, 'Partial' ); ?>><?php esc_html_e( 'Partially Paid', 'ifs-travel-erp' ); ?></option>
                                    <option value="Unpaid" <?php selected( $val_pay_stat, 'Unpaid' ); ?>><?php esc_html_e( 'Unpaid / Due', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_status"><?php esc_html_e( 'Reservation Status', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <select name="status" id="inp_status" class="ifs-input-field">
                                    <option value="Confirmed" <?php selected( $val_status, 'Confirmed' ); ?>><?php esc_html_e( 'Confirmed', 'ifs-travel-erp' ); ?></option>
                                    <option value="Reserved" <?php selected( $val_status, 'Reserved' ); ?>><?php esc_html_e( 'Reserved', 'ifs-travel-erp' ); ?></option>
                                    <option value="Cancelled" <?php selected( $val_status, 'Cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-3">
                            <label class="ifs-field-label" for="inp_special_req"><?php esc_html_e( 'Special Requests / Notes', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-textarea-wrap">
                                <textarea name="special_req" id="inp_special_req" rows="2" class="ifs-input-field" placeholder="<?php esc_attr_e( 'e.g. Non-smoking, High floor, Early check-in', 'ifs-travel-erp' ); ?>"><?php echo esc_textarea( $val_req ); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="ifs-action-strip" style="margin-top: 22px;">
                        <a href="<?php echo esc_url( add_query_arg( 'sub', 'list', $base_url ) ); ?>" class="ifs-btn-secondary"><?php esc_html_e( 'Cancel', 'ifs-travel-erp' ); ?></a>
                        <button type="submit" name="ifs_hotel_submit" class="ifs-btn-primary">
                            <span class="dashicons dashicons-saved"></span> <?php echo $edit_data ? esc_html__( 'Update Reservation', 'ifs-travel-erp' ) : esc_html__( 'Save Reservation', 'ifs-travel-erp' ); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Stylesheet: Flat Minimal UI, Zero Shadows & Strict 42px Equal Height -->
        <style>
            .ifs-hotels-workspace { 
                max-width: 1200px; 
                margin: 20px auto; 
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; 
                color: #0f172a; 
                box-sizing: border-box; 
            }
            .ifs-hotels-workspace *, 
            .ifs-hotels-workspace *::before, 
            .ifs-hotels-workspace *::after { 
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
            input[type="date"].ifs-input-field { cursor: pointer; }

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

            .uppercase { text-transform: uppercase; }
            .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important; }
            .font-bold { font-weight: 700; }
            .bg-light { background: #f8fafc !important; }
            .color-emerald { color: #059669 !important; }
            .color-rose { color: #e11d48 !important; }

            .ifs-action-strip { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; }
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

            const selProp     = $('#inp_prop_template');
            const inpHotel    = $('#inp_hotel_name');
            const inpCity     = $('#inp_city');
            const inpIn       = $('#inp_check_in');
            const inpOut      = $('#inp_check_out');
            const inpBuy      = $('#inp_buy_price');
            const inpSell     = $('#inp_sell_price');
            const inpPaid     = $('#inp_paid_amount');
            const dueDisplay  = $('#inp_due_amount');
            const payStatus   = $('#inp_pay_status');
            const profitInput = $('#inp_profit');

            if (selProp.length) {
                selProp.on('change', function() {
                    const opt = $(this).find(':selected');
                    if (opt.val() && opt.val() !== '') {
                        if (inpHotel.length) inpHotel.val(opt.attr('data-name') || '');
                        if (inpCity.length)  inpCity.val(opt.attr('data-city') || '');

                        const d1 = new Date(inpIn.val());
                        const d2 = new Date(inpOut.val());
                        const nights = Math.max(1, Math.round((d2 - d1) / (1000 * 60 * 60 * 24)) || 1);

                        const rRate = parseFloat(opt.attr('data-rate')) || 0;
                        const sRate = parseFloat(opt.attr('data-sell')) || 0;

                        if (inpBuy.length)  inpBuy.val((rRate * nights).toFixed(2));
                        if (inpSell.length) inpSell.val((sRate * nights).toFixed(2));
                        calculateLedger();
                    }
                });
            }

            function calculateLedger() {
                const buy  = parseFloat(inpBuy.val()) || 0;
                const sell = parseFloat(inpSell.val()) || 0;
                const paid = parseFloat(inpPaid.val()) || 0;
                
                const p   = sell - buy;
                const due = Math.max(0, sell - paid);

                if (profitInput.length) {
                    profitInput.val(p.toFixed(2));
                    profitInput.css('color', p >= 0 ? '#059669' : '#e11d48');
                }

                if (dueDisplay.length) {
                    dueDisplay.val(due.toFixed(2));
                    dueDisplay.css('color', due > 0 ? '#e11d48' : '#059669');
                }

                if (sell > 0 && payStatus.length) {
                    if (paid >= sell) {
                        payStatus.val('Paid');
                    } else if (paid > 0 && paid < sell) {
                        payStatus.val('Partial');
                    } else {
                        payStatus.val('Unpaid');
                    }
                }
            }

            $(document).on('input change keyup', '#inp_buy_price, #inp_sell_price, #inp_paid_amount, #inp_check_in, #inp_check_out', calculateLedger);
            calculateLedger();
        });
        </script>
        <?php
    }
}