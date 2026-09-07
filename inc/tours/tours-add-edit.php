<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_tours_add_edit_page' ) ) {
    /**
     * Tour Booking Form Page (Add / Edit) - Comprehensive Agency Edition
     * Flat Minimal UI: No Shadows, Strict 42px Equal Field Heights, Normalized Select2
     */
    function ifs_terp_tours_add_edit_page() {
        global $wpdb;
        $table_tours     = $wpdb->prefix . 'iterp_tours';
        $table_plans     = $wpdb->prefix . 'iterp_tour_packages';
        $table_customers = $wpdb->prefix . 'iterp_customers';
        $table_agents    = $wpdb->prefix . 'iterp_agents';
        $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
        $base_url        = admin_url( 'admin.php?page=ifs_travel_erp&tab=tours' );

        // Enqueue Select2
        if ( ! wp_script_is( 'select2', 'registered' ) ) {
            wp_register_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), '4.1.0-rc.0', true );
            wp_register_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0-rc.0' );
        }
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'select2' );
        wp_enqueue_style( 'select2' );

        $edit_id   = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
        $edit_data = false;
        if ( $edit_id > 0 ) {
            $edit_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_tours} WHERE id = %d", $edit_id ) );
        }

        // Save/Update Handler
        $message = '';
        $errors  = array();
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_tour_submit'] ) ) {
            check_admin_referer( 'ifs_tour_action', 'ifs_tour_nonce' );

            $customer_id      = isset( $_POST['customer_id'] ) ? absint( wp_unslash( $_POST['customer_id'] ) ) : 0;
            $package_plan_id  = isset( $_POST['package_plan_id'] ) ? absint( wp_unslash( $_POST['package_plan_id'] ) ) : 0;
            $agent_id         = isset( $_POST['agent_id'] ) ? absint( wp_unslash( $_POST['agent_id'] ) ) : 0;
            $supplier_id      = isset( $_POST['supplier_id'] ) ? absint( wp_unslash( $_POST['supplier_id'] ) ) : 0;
            $package_title    = isset( $_POST['package_title'] ) ? sanitize_text_field( wp_unslash( $_POST['package_title'] ) ) : '';
            $destination      = isset( $_POST['destination'] ) ? sanitize_text_field( wp_unslash( $_POST['destination'] ) ) : '';
            $duration         = isset( $_POST['duration'] ) ? sanitize_text_field( wp_unslash( $_POST['duration'] ) ) : '';
            $travel_date      = isset( $_POST['travel_date'] ) ? sanitize_text_field( wp_unslash( $_POST['travel_date'] ) ) : '';
            $return_date      = isset( $_POST['return_date'] ) ? sanitize_text_field( wp_unslash( $_POST['return_date'] ) ) : '';
            $adults_count     = isset( $_POST['adults_count'] ) ? max( 1, intval( wp_unslash( $_POST['adults_count'] ) ) ) : 1;
            $child_count      = isset( $_POST['child_count'] ) ? max( 0, intval( wp_unslash( $_POST['child_count'] ) ) ) : 0;
            $room_sharing     = isset( $_POST['room_sharing'] ) ? sanitize_text_field( wp_unslash( $_POST['room_sharing'] ) ) : 'Twin Sharing';
            
            $buy_price        = isset( $_POST['buy_price'] ) ? (float) wp_unslash( $_POST['buy_price'] ) : 0;
            $sell_price       = isset( $_POST['sell_price'] ) ? (float) wp_unslash( $_POST['sell_price'] ) : 0;
            $paid_amount      = isset( $_POST['paid_amount'] ) ? (float) wp_unslash( $_POST['paid_amount'] ) : 0;
            $due_amount       = max( 0, $sell_price - $paid_amount );
            $profit           = $sell_price - $buy_price;

            $payment_status   = isset( $_POST['payment_status'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_status'] ) ) : 'Paid';
            $payment_method   = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : 'Bank Transfer';
            $status           = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'Reserved';
            $remarks          = isset( $_POST['remarks'] ) ? sanitize_textarea_field( wp_unslash( $_POST['remarks'] ) ) : '';
            
            // Inclusions Array to CSV
            $inclusions_arr   = isset( $_POST['inclusions'] ) && is_array( $_POST['inclusions'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['inclusions'] ) ) : array();
            $inclusions       = implode( ', ', $inclusions_arr );

            if ( empty( $customer_id ) ) {
                $errors[] = esc_html__( 'Customer selection is required.', 'ifs-travel-erp' );
            }
            if ( empty( $package_title ) ) {
                $errors[] = esc_html__( 'Package title is required.', 'ifs-travel-erp' );
            }
            if ( empty( $travel_date ) ) {
                $errors[] = esc_html__( 'Travel departure date is required.', 'ifs-travel-erp' );
            }

            if ( empty( $errors ) ) {
                $data = array(
                    'customer_id'     => $customer_id,
                    'package_plan_id' => $package_plan_id,
                    'agent_id'        => $agent_id,
                    'supplier_id'     => $supplier_id,
                    'package_title'   => $package_title,
                    'destination'     => $destination,
                    'duration'        => $duration,
                    'travel_date'     => $travel_date,
                    'return_date'     => ! empty( $return_date ) ? $return_date : $travel_date,
                    'adults_count'    => $adults_count,
                    'child_count'     => $child_count,
                    'room_sharing'    => $room_sharing,
                    'buy_price'       => $buy_price,
                    'sell_price'      => $sell_price,
                    'paid_amount'     => $paid_amount,
                    'due_amount'      => $due_amount,
                    'profit'          => $profit,
                    'payment_status'  => $payment_status,
                    'payment_method'  => $payment_method,
                    'status'          => $status,
                    'inclusions'      => $inclusions,
                    'remarks'         => $remarks,
                );

                if ( $edit_id > 0 ) {
                    $wpdb->update( $table_tours, $data, array( 'id' => $edit_id ) );
                    $message   = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Tour booking updated successfully.', 'ifs-travel-erp' ) . '</div>';
                    $edit_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_tours} WHERE id = %d", $edit_id ) );
                } else {
                    $data['created_at'] = current_time( 'mysql' );
                    $wpdb->insert( $table_tours, $data );
                    $edit_id   = $wpdb->insert_id;
                    $edit_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_tours} WHERE id = %d", $edit_id ) );
                    $message   = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . sprintf( esc_html__( 'New Tour booking saved (#TR-%s).', 'ifs-travel-erp' ), esc_html( str_pad( (string) $edit_id, 5, '0', STR_PAD_LEFT ) ) ) . '</div>';
                }
            } else {
                $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> ' . implode( '<br>', $errors ) . '</div>';
            }
        }

        $customers = $wpdb->get_results( "SELECT id, full_name, mobile, passport_no FROM {$table_customers} ORDER BY full_name ASC" );
        $agents    = $wpdb->get_results( "SELECT id, agency_name FROM {$table_agents} WHERE status = 'Active' ORDER BY agency_name ASC" );
        $suppliers = $wpdb->get_results( "SELECT id, supplier_name FROM {$table_suppliers} WHERE status = 'Active' ORDER BY supplier_name ASC" );
        $plans     = $wpdb->get_results( "SELECT * FROM {$table_plans} ORDER BY package_name ASC" );

        // Prefill Variables
        $val_plan_id      = $edit_data ? absint( $edit_data->package_plan_id ?? 0 ) : 0;
        $val_customer     = $edit_data ? absint( $edit_data->customer_id ?? 0 ) : 0;
        $val_agent        = $edit_data ? absint( $edit_data->agent_id ?? 0 ) : 0;
        $val_supplier     = $edit_data ? absint( $edit_data->supplier_id ?? 0 ) : 0;
        $val_title        = $edit_data ? esc_attr( $edit_data->package_title ?? '' ) : '';
        $val_destination  = $edit_data ? esc_attr( $edit_data->destination ?? '' ) : '';
        $val_duration     = $edit_data ? esc_attr( $edit_data->duration ?? '' ) : '';
        $val_travel_date  = $edit_data ? esc_attr( $edit_data->travel_date ?? gmdate( 'Y-m-d' ) ) : gmdate( 'Y-m-d' );
        $val_return_date  = $edit_data ? esc_attr( $edit_data->return_date ?? gmdate( 'Y-m-d', strtotime( '+4 days' ) ) ) : gmdate( 'Y-m-d', strtotime( '+4 days' ) );
        $val_adults       = $edit_data ? intval( $edit_data->adults_count ?? 1 ) : 1;
        $val_child        = $edit_data ? intval( $edit_data->child_count ?? 0 ) : 0;
        $val_room_sharing = $edit_data ? esc_attr( $edit_data->room_sharing ?? 'Twin Sharing' ) : 'Twin Sharing';
        
        $val_buy          = $edit_data ? (float) $edit_data->buy_price : '';
        $val_sell         = $edit_data ? (float) $edit_data->sell_price : '';
        $val_paid         = $edit_data ? (float) $edit_data->paid_amount : '';
        $val_due          = $edit_data ? (float) $edit_data->due_amount : 0;
        $val_profit       = $edit_data ? (float) $edit_data->profit : 0;
        
        $val_pay_status   = $edit_data ? esc_attr( $edit_data->payment_status ?? 'Paid' ) : 'Paid';
        $val_pay_method   = $edit_data ? esc_attr( $edit_data->payment_method ?? 'Bank Transfer' ) : 'Bank Transfer';
        $val_status       = $edit_data ? esc_attr( $edit_data->status ?? 'Reserved' ) : 'Reserved';
        $val_remarks      = $edit_data ? esc_textarea( $edit_data->remarks ?? '' ) : '';
        
        $active_inclusions = array();
        if ( $edit_data && ! empty( $edit_data->inclusions ) ) {
            $active_inclusions = array_map( 'trim', explode( ',', $edit_data->inclusions ) );
        }
        ?>

        <div class="wrap ifs-tour-workspace">
            <?php echo wp_kses_post( $message ); ?>

            <form method="post" action="">
                <?php wp_nonce_field( 'ifs_tour_action', 'ifs_tour_nonce' ); ?>
                <?php if ( $edit_id > 0 ) : ?>
                    <input type="hidden" name="edit_id" value="<?php echo esc_attr( $edit_id ); ?>">
                <?php endif; ?>

                <!-- Section 1: Customer & Base Package -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">01</div>
                        <div>
                            <h3 class="ifs-card-title"><?php echo $edit_data ? esc_html__( 'Edit Holiday Tour Booking', 'ifs-travel-erp' ) : esc_html__( 'New Holiday Tour Booking', 'ifs-travel-erp' ); ?></h3>
                            <p class="ifs-card-desc"><?php esc_html_e( 'Select registered customer, choose package template, or build bespoke itinerary', 'ifs-travel-erp' ); ?></p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <div class="ifs-field-block col-span-3">
                            <label class="ifs-field-label" for="inp_plan_template"><?php esc_html_e( 'Package Template (Auto-Fills Details & Pricing)', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <select name="package_plan_id" id="inp_plan_template" class="ifs-input-field ifs-select2">
                                    <option value="0"><?php esc_html_e( '-- Select Template or Enter Manually --', 'ifs-travel-erp' ); ?></option>
                                    <?php if ( ! empty( $plans ) ) : foreach ( $plans as $pl ) : ?>
                                        <option value="<?php echo esc_attr( $pl->id ); ?>" 
                                                data-title="<?php echo esc_attr( $pl->package_name ); ?>"
                                                data-dest="<?php echo esc_attr( $pl->destination ); ?>"
                                                data-dur="<?php echo esc_attr( $pl->total_days . ' Days, ' . $pl->total_nights . ' Nights' ); ?>"
                                                data-cost="<?php echo esc_attr( $pl->cost_bdt ); ?>"
                                                data-sell="<?php echo esc_attr( $pl->selling_price ); ?>"
                                                <?php selected( $val_plan_id, $pl->id ); ?>>
                                            <?php echo esc_html( $pl->package_name . ' (' . $pl->destination . ') — ৳' . number_format( (float) $pl->selling_price, 0 ) ); ?>
                                        </option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-2">
                            <label class="ifs-field-label" for="inp_customer"><?php esc_html_e( 'Primary Customer', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <select name="customer_id" id="inp_customer" required class="ifs-input-field ifs-select2">
                                    <option value=""><?php esc_html_e( '-- Search Traveler by Name, Mobile, Passport --', 'ifs-travel-erp' ); ?></option>
                                    <?php if ( ! empty( $customers ) ) : foreach ( $customers as $cus ) : 
                                        $meta_str = $cus->full_name . ' | Mob: ' . $cus->mobile . ( ! empty( $cus->passport_no ) ? ' | PPT: ' . $cus->passport_no : '' );
                                    ?>
                                        <option value="<?php echo esc_attr( $cus->id ); ?>" <?php selected( $val_customer, $cus->id ); ?>>
                                            <?php echo esc_html( $meta_str ); ?>
                                        </option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_agent"><?php esc_html_e( 'Booking Agent', 'ifs-travel-erp' ); ?></label>
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
                            <label class="ifs-field-label" for="inp_package_title"><?php esc_html_e( 'Package Title', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <input type="text" name="package_title" id="inp_package_title" required value="<?php echo esc_attr( $val_title ); ?>" placeholder="e.g. 5D/4N Maldives Luxury Getaway" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_supplier"><?php esc_html_e( 'Tour Operator / DMC Supplier', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <select name="supplier_id" id="inp_supplier" class="ifs-input-field ifs-select2">
                                    <option value="0"><?php esc_html_e( 'In-House Self Managed', 'ifs-travel-erp' ); ?></option>
                                    <?php if ( ! empty( $suppliers ) ) : foreach ( $suppliers as $sup ) : ?>
                                        <option value="<?php echo esc_attr( $sup->id ); ?>" <?php selected( $val_supplier, $sup->id ); ?>>
                                            <?php echo esc_html( $sup->supplier_name ); ?>
                                        </option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_destination"><?php esc_html_e( 'Destination City/Country', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <input type="text" name="destination" id="inp_destination" required value="<?php echo esc_attr( $val_destination ); ?>" placeholder="e.g. Male, Maldives" class="ifs-input-field uppercase">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_duration"><?php esc_html_e( 'Duration Format', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <input type="text" name="duration" id="inp_duration" value="<?php echo esc_attr( $val_duration ); ?>" placeholder="e.g. 5 Days, 4 Nights" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_room_sharing"><?php esc_html_e( 'Room Sharing Type', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <select name="room_sharing" id="inp_room_sharing" class="ifs-input-field">
                                    <option value="Twin Sharing" <?php selected( $val_room_sharing, 'Twin Sharing' ); ?>><?php esc_html_e( 'Twin Sharing', 'ifs-travel-erp' ); ?></option>
                                    <option value="Triple Sharing" <?php selected( $val_room_sharing, 'Triple Sharing' ); ?>><?php esc_html_e( 'Triple Sharing', 'ifs-travel-erp' ); ?></option>
                                    <option value="Single Supplement" <?php selected( $val_room_sharing, 'Single Supplement' ); ?>><?php esc_html_e( 'Single Supplement', 'ifs-travel-erp' ); ?></option>
                                    <option value="Quad Sharing" <?php selected( $val_room_sharing, 'Quad Sharing' ); ?>><?php esc_html_e( 'Quad Sharing', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Schedules, Pax Count & Inclusions -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">02</div>
                        <div>
                            <h3 class="ifs-card-title"><?php esc_html_e( 'Itinerary Schedule, Pax & Package Inclusions', 'ifs-travel-erp' ); ?></h3>
                            <p class="ifs-card-desc"><?php esc_html_e( 'Travel dates, passenger breakdowns, and included package components', 'ifs-travel-erp' ); ?></p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_travel_date"><?php esc_html_e( 'Tour Start Date', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <input type="date" name="travel_date" id="inp_travel_date" required value="<?php echo esc_attr( $val_travel_date ); ?>" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_return_date"><?php esc_html_e( 'Tour Return Date', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <input type="date" name="return_date" id="inp_return_date" required value="<?php echo esc_attr( $val_return_date ); ?>" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label"><?php esc_html_e( 'Passenger Breakdown', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-pax-dual-row">
                                <div class="ifs-pax-item">
                                    <div class="ifs-field-wrap">
                                        <input type="number" min="1" name="adults_count" id="inp_adults" value="<?php echo esc_attr( $val_adults ); ?>" class="ifs-input-field font-mono" placeholder="Adults">
                                    </div>
                                    <small class="ifs-field-hint"><?php esc_html_e( 'Adults (12+)', 'ifs-travel-erp' ); ?></small>
                                </div>
                                <div class="ifs-pax-item">
                                    <div class="ifs-field-wrap">
                                        <input type="number" min="0" name="child_count" id="inp_child" value="<?php echo esc_attr( $val_child ); ?>" class="ifs-input-field font-mono" placeholder="Child">
                                    </div>
                                    <small class="ifs-field-hint"><?php esc_html_e( 'Children', 'ifs-travel-erp' ); ?></small>
                                </div>
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-3">
                            <label class="ifs-field-label"><?php esc_html_e( 'Voucher Inclusions & Services', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-checkbox-pill-grid">
                                <?php 
                                $inclusion_options = array( 'Air Tickets', 'Hotel Stay', 'Daily Breakfast', 'All Meals (FB)', 'Airport Transfers', 'Visa Processing', 'Sightseeing Tours', 'Tour Guide' );
                                foreach ( $inclusion_options as $inc ) : 
                                    $checked = in_array( $inc, $active_inclusions, true );
                                ?>
                                    <label class="ifs-pill-label">
                                        <input type="checkbox" name="inclusions[]" value="<?php echo esc_attr( $inc ); ?>" <?php checked( $checked ); ?>>
                                        <span><?php echo esc_html( $inc ); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Commercial Accounting, Payment & Ledger -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">03</div>
                        <div>
                            <h3 class="ifs-card-title"><?php esc_html_e( 'Commercial Pricing, Settlement & Ledger Status', 'ifs-travel-erp' ); ?></h3>
                            <p class="ifs-card-desc"><?php esc_html_e( 'Cost price, client selling fare, paid collection, live calculated due & net profit', 'ifs-travel-erp' ); ?></p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_buy_price"><?php esc_html_e( 'Supplier Cost Rate (৳)', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <input type="number" step="0.01" name="buy_price" id="inp_buy_price" required value="<?php echo esc_attr( $val_buy ); ?>" placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_sell_price"><?php esc_html_e( 'Client Selling Rate (৳)', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <input type="number" step="0.01" name="sell_price" id="inp_sell_price" required value="<?php echo esc_attr( $val_sell ); ?>" placeholder="0.00" class="ifs-input-field font-mono font-bold color-emerald">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label"><?php esc_html_e( 'Net Profit Margin (৳)', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <input type="text" id="inp_profit" readonly value="<?php echo esc_attr( number_format( (float) $val_profit, 2 ) ); ?>" class="ifs-input-field font-mono font-bold bg-light">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_paid_amount"><?php esc_html_e( 'Paid Amount (৳)', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <input type="number" step="0.01" name="paid_amount" id="inp_paid_amount" value="<?php echo esc_attr( $val_paid ); ?>" placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label"><?php esc_html_e( 'Remaining Balance / Due (৳)', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <input type="text" id="inp_due_display" readonly value="<?php echo esc_attr( number_format( (float) $val_due, 2 ) ); ?>" class="ifs-input-field font-mono font-bold <?php echo ( $val_due > 0 ) ? 'color-rose' : 'color-slate'; ?>">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_pay_method"><?php esc_html_e( 'Payment Collection Method', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <select name="payment_method" id="inp_pay_method" class="ifs-input-field">
                                    <option value="Bank Transfer" <?php selected( $val_pay_method, 'Bank Transfer' ); ?>><?php esc_html_e( 'Bank Transfer (BEFTN/RTGS)', 'ifs-travel-erp' ); ?></option>
                                    <option value="Cash" <?php selected( $val_pay_method, 'Cash' ); ?>><?php esc_html_e( 'Cash at Counter', 'ifs-travel-erp' ); ?></option>
                                    <option value="bKash / MFS" <?php selected( $val_pay_method, 'bKash / MFS' ); ?>><?php esc_html_e( 'bKash / Nagad / Rocket', 'ifs-travel-erp' ); ?></option>
                                    <option value="Cheque" <?php selected( $val_pay_method, 'Cheque' ); ?>><?php esc_html_e( 'Cheque', 'ifs-travel-erp' ); ?></option>
                                    <option value="POS Card" <?php selected( $val_pay_method, 'POS Card' ); ?>><?php esc_html_e( 'POS / Credit Card', 'ifs-travel-erp' ); ?></option>
                                    <option value="Agent Credit" <?php selected( $val_pay_method, 'Agent Credit' ); ?>><?php esc_html_e( 'Agent Credit Ledger', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_pay_status"><?php esc_html_e( 'Payment Status', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <select name="payment_status" id="inp_pay_status" class="ifs-input-field">
                                    <option value="Paid" <?php selected( $val_pay_status, 'Paid' ); ?>><?php esc_html_e( 'Fully Paid', 'ifs-travel-erp' ); ?></option>
                                    <option value="Partial" <?php selected( $val_pay_status, 'Partial' ); ?>><?php esc_html_e( 'Partially Paid', 'ifs-travel-erp' ); ?></option>
                                    <option value="Due" <?php selected( $val_pay_status, 'Due' ); ?>><?php esc_html_e( 'Unpaid / Due', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_status"><?php esc_html_e( 'Tour Booking Lifecycle', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <select name="status" id="inp_status" class="ifs-input-field">
                                    <option value="Reserved" <?php selected( $val_status, 'Reserved' ); ?>><?php esc_html_e( 'Reserved (Inquiry)', 'ifs-travel-erp' ); ?></option>
                                    <option value="Confirmed" <?php selected( $val_status, 'Confirmed' ); ?>><?php esc_html_e( 'Confirmed & Guaranteed', 'ifs-travel-erp' ); ?></option>
                                    <option value="Pending" <?php selected( $val_status, 'Pending' ); ?>><?php esc_html_e( 'Pending Confirmation', 'ifs-travel-erp' ); ?></option>
                                    <option value="Cancelled" <?php selected( $val_status, 'Cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'ifs-travel-erp' ); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-3">
                            <label class="ifs-field-label" for="inp_remarks"><?php esc_html_e( 'Special Remarks & Tour Notes', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-textarea-wrap">
                                <textarea name="remarks" id="inp_remarks" rows="2" placeholder="<?php esc_attr_e( 'e.g. Honeymoon cake required on arrival, early check-in requested at hotel...', 'ifs-travel-erp' ); ?>" class="ifs-input-field"><?php echo esc_textarea( $val_remarks ); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Toolbar -->
                <div class="ifs-action-strip">
                    <a href="<?php echo esc_url( add_query_arg( 'sub', 'list', $base_url ) ); ?>" class="ifs-btn-secondary">
                        <span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'Back to Bookings', 'ifs-travel-erp' ); ?>
                    </a>
                    <button type="submit" name="ifs_tour_submit" class="ifs-btn-primary">
                        <span class="dashicons dashicons-saved"></span> <?php echo $edit_data ? esc_html__( 'Update Tour Booking', 'ifs-travel-erp' ) : esc_html__( 'Save & Issue Tour Booking', 'ifs-travel-erp' ); ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Stylesheet: Zero Shadow & Strict 42px Uniform Height -->
        <style>
            .ifs-tour-workspace { 
                max-width: 1200px; 
                margin: 20px auto; 
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; 
                color: #0f172a; 
                box-sizing: border-box; 
            }
            .ifs-tour-workspace *, 
            .ifs-tour-workspace *::before, 
            .ifs-tour-workspace *::after { 
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

            /* Passenger Dual Row Grouping */
            .ifs-pax-dual-row { display: flex; gap: 12px; width: 100%; }
            .ifs-pax-item { flex: 1; display: flex; flex-direction: column; gap: 4px; }
            .ifs-field-hint { color: #64748b; font-size: 10.5px; font-weight: 600; }

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

            /* Checklist Grid */
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

            .uppercase { text-transform: uppercase; }
            .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important; }
            .font-bold { font-weight: 700; }
            .bg-light { background: #f8fafc !important; }
            .color-emerald { color: #059669 !important; }
            .color-rose { color: #dc2626 !important; }
            .color-slate { color: #64748b !important; }

            .ifs-action-strip { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; }
            .ifs-btn-secondary { background: #f8fafc; color: #475569 !important; border: 1px solid #cbd5e1; height: 42px; padding: 0 20px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; transition: background-color 0.2s ease, color 0.2s ease; }
            .ifs-btn-secondary:hover { background: #f1f5f9; color: #0f172a; }
            .ifs-btn-primary {
                background: #003376;
                color: #ffffff !important;
                border: none;
                height: 42px;
                padding: 0 26px;
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
        </style>

        <script>
        jQuery(document).ready(function($) {
            if ($.fn.select2) {
                $('.ifs-select2').select2({ width: '100%', allowClear: false });
            }

            const selTemplate = $('#inp_plan_template');
            const inpTitle    = $('#inp_package_title');
            const inpDest     = $('#inp_destination');
            const inpDur      = $('#inp_duration');
            const inpBuy      = $('#inp_buy_price');
            const inpSell     = $('#inp_sell_price');
            const inpPaid     = $('#inp_paid_amount');
            const profitInput = $('#inp_profit');
            const dueDisplay  = $('#inp_due_display');
            const payStatus   = $('#inp_pay_status');

            // Package Template Auto-fill
            selTemplate.on('change', function() {
                const opt = $(this).find(':selected');
                if (opt.val() && opt.val() !== '0') {
                    if (inpTitle.length) inpTitle.val(opt.attr('data-title') || '');
                    if (inpDest.length)  inpDest.val(opt.attr('data-dest') || '');
                    if (inpDur.length)   inpDur.val(opt.attr('data-dur') || '');
                    if (inpBuy.length)   inpBuy.val(opt.attr('data-cost') || '');
                    if (inpSell.length)  inpSell.val(opt.attr('data-sell') || '');
                    calculateAccounts();
                }
            });

            // Live Financial Calculation
            function calculateAccounts() {
                const buy  = parseFloat(inpBuy.val()) || 0;
                const sell = parseFloat(inpSell.val()) || 0;
                const paid = parseFloat(inpPaid.val()) || 0;
                
                const profit = sell - buy;
                const due    = Math.max(0, sell - paid);

                if (profitInput.length) {
                    profitInput.val(profit.toFixed(2));
                    profitInput.css('color', profit >= 0 ? '#059669' : '#dc2626');
                }

                if (dueDisplay.length) {
                    dueDisplay.val(due.toFixed(2));
                    dueDisplay.css('color', due > 0 ? '#dc2626' : '#64748b');
                }

                // Auto-align payment status based on paid amount
                if (payStatus.length && sell > 0) {
                    if (paid >= sell) {
                        payStatus.val('Paid');
                    } else if (paid > 0 && paid < sell) {
                        payStatus.val('Partial');
                    } else {
                        payStatus.val('Due');
                    }
                }
            }

            inpBuy.on('input change', calculateAccounts);
            inpSell.on('input change', calculateAccounts);
            inpPaid.on('input change', calculateAccounts);
        });
        </script>
        <?php
    }
}